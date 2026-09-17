<?php
/**
 * Shopping cart: create (add a line), retrieve, update (variant, quantity,
 * note), delete (remove a line), apply a discount code, and sort/filter the
 * lines shown. The cart is saved against the logged-in user, or held for a
 * guest and merged in when they log in.
 */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$errors = [];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('cart.php');
    }
    $action = (string) (filter_input(INPUT_POST, 'action', FILTER_DEFAULT) ?? '');

    /* --- Create: add a product line ------------------------------------ */
    if ($action === 'add') {
        $productId = (string) (filter_input(INPUT_POST, 'product', FILTER_DEFAULT) ?? '');
        $p = product($productId);
        if (!$p) {
            flash('danger', t('That product does not exist.'));
            redirect('catalogue.php');
        }
        $maxQty = max(1, min((int) $p['max_qty'], (int) $p['stock']));
        $rules = [
            'variant'  => ['label' => $p['variant_label'], 'required' => true, 'in' => variant_keys($p)],
            'quantity' => ['label' => 'Quantity', 'required' => true, 'type' => 'int', 'int_min' => 1, 'int_max' => $maxQty],
            'note'     => ['label' => 'Note for this item', 'max' => 80],
        ];
        $values = form_values($rules);
        $errors = validate($rules, $values);
        if ((int) $p['stock'] <= 0) {
            $errors['quantity'] = t('This product is out of stock.');
        }
        if ($errors) {
            $_SESSION['cart_errors'] = $errors;
            $_SESSION['cart_values'] = $values;
            redirect('product.php?id=' . rawurlencode($productId));
        }
        cart_add($productId, $values['variant'], (int) $values['quantity'], $values['note']);
        flash('success', t('%1$s added to your cart.', [$p['name']]));
        redirect('cart.php');
    }

    /* --- Update: every line's variant, quantity and note ---------------- */
    if ($action === 'update') {
        $variants = filter_input(INPUT_POST, 'variant', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
        $qtys     = filter_input(INPUT_POST, 'qty', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
        $notes    = filter_input(INPUT_POST, 'note', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
        $c = &cart();
        foreach ($c['items'] as &$line) {
            $lid = (int) $line['id'];
            $p = product($line['product_id']);
            if (!$p) {
                continue;
            }
            $maxQty = max(1, min((int) $p['max_qty'], (int) $p['stock']));
            $rules = [
                'variant-' . $lid => ['label' => $p['variant_label'], 'required' => true, 'in' => variant_keys($p)],
                'qty-' . $lid     => ['label' => t('Quantity of %1$s', [$p['name']]), 'required' => true, 'type' => 'int', 'int_min' => 1, 'int_max' => $maxQty],
                'note-' . $lid    => ['label' => 'Note', 'max' => 80],
            ];
            $values = [
                'variant-' . $lid => trim((string) ($variants[$lid] ?? $line['variant'])),
                'qty-' . $lid     => trim((string) ($qtys[$lid] ?? $line['quantity'])),
                'note-' . $lid    => trim((string) ($notes[$lid] ?? ($line['note'] ?? ''))),
            ];
            $lineErrors = validate($rules, $values);
            if ($lineErrors) {
                $errors += $lineErrors;
                continue;
            }
            $line['variant']  = $values['variant-' . $lid];
            $line['quantity'] = (int) $values['qty-' . $lid];
            $line['note']     = $values['note-' . $lid];
        }
        unset($line, $c);
        if (!$errors) {
            flash('success', t('Your cart has been updated.'));
            redirect('cart.php');
        }
    }

    /* --- Delete: remove one line ---------------------------------------- */
    if ($action === 'remove') {
        $lid = filter_input(INPUT_POST, 'line', FILTER_VALIDATE_INT);
        $line = $lid ? cart_line((int) $lid) : null;
        if ($line) {
            $p = product($line['product_id']);
            cart_remove((int) $lid);
            flash('success', t('%1$s removed from your cart.', [$p['name'] ?? '']));
        }
        redirect('cart.php');
    }

    if ($action === 'clear') {
        cart_clear();
        flash('success', t('Your cart is now empty.'));
        redirect('cart.php');
    }

    /* --- Discount code -------------------------------------------------- */
    if ($action === 'promo') {
        $rules = ['promo-code' => ['label' => 'Discount code', 'required' => true, 'max' => 20]];
        $values = form_values($rules);
        $errors = validate($rules, $values);
        if (!$errors) {
            $code = strtoupper($values['promo-code']);
            if (!isset(GF_PROMO_CODES[$code])) {
                $errors['promo-code'] = t('That code is not recognised. Try FORGE10.');
            } else {
                $c = &cart();
                $c['promo'] = $code;
                unset($c);
                flash('success', t('Code %1$s applied: %2$s.', [$code, t(GF_PROMO_CODES[$code]['label'])]));
                redirect('cart.php');
            }
        }
    }
    if ($action === 'promo-remove') {
        $c = &cart();
        $c['promo'] = null;
        unset($c);
        redirect('cart.php');
    }
}

/* --- Retrieve, with sort/filter for the display ------------------------- */
$sorts = [
    'added'      => 'Order added',
    'title-asc'  => 'Title, A to Z',
    'title-desc' => 'Title, Z to A',
    'qty-desc'   => 'Quantity, high to low',
    'qty-asc'    => 'Quantity, low to high',
    'price-desc' => 'Line total, high to low',
    'price-asc'  => 'Line total, low to high',
];
$f = [
    'q'    => trim((string) (filter_input(INPUT_GET, 'q', FILTER_DEFAULT) ?? '')),
    'sort' => (string) (filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'added'),
];
if (!isset($sorts[$f['sort']])) {
    $f['sort'] = 'added';
}

$all_lines = cart_lines();
$lines = array_filter($all_lines, function ($l) use ($f) {
    if ($f['q'] === '') {
        return true;
    }
    return mb_stripos($l['product']['name'], $f['q']) !== false
        || mb_stripos(variant_label($l['product'], $l['variant']), $f['q']) !== false
        || mb_stripos((string) ($l['note'] ?? ''), $f['q']) !== false;
});
$sort = $f['sort'];
usort($lines, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'title-asc':  return strcasecmp($a['product']['name'], $b['product']['name']);
        case 'title-desc': return strcasecmp($b['product']['name'], $a['product']['name']);
        case 'qty-desc':   return $b['quantity'] <=> $a['quantity'];
        case 'qty-asc':    return $a['quantity'] <=> $b['quantity'];
        case 'price-desc': return $b['line_cents'] <=> $a['line_cents'];
        case 'price-asc':  return $a['line_cents'] <=> $b['line_cents'];
        default:           return $a['id'] <=> $b['id'];
    }
});

$totals = cart_totals('standard');
$count = cart_count();

$page_title = 'Your cart';
$page_description = 'Review the items in your Grimdark Forge cart, change quantities and variants, and continue to checkout.';
$page_scripts = ['assets/js/shop.js'];
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="catalogue.php"><?= te('Shop') ?></a></li>
          <li aria-current="page"><?= te('Cart') ?></li>
        </ol>
      </nav>

      <ol class="steps">
        <li class="steps__item" aria-current="step">1. <?= te('Cart') ?></li>
        <li class="steps__item">2. <?= te('Checkout') ?></li>
        <li class="steps__item">3. <?= te('Confirmation') ?></li>
      </ol>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Your cart') ?></h1>
          <p class="page-head__lede">
<?php if (is_logged_in()): ?>
            <?= te('%1$s items, saved against %2$s\'s account.', [number($count), user_name(current_user())]) ?>
<?php else: ?>
            <?= te('%1$s items. You are not logged in: this cart is held for your visit and will be merged into your account when you log in.', [number($count)]) ?>
<?php endif; ?>
            <?= te('Change a quantity or variant and select Update cart to recalculate the totals.') ?>
          </p>
        </div>
        <div class="page-head__actions">
          <a class="button" href="catalogue.php"><?= te('Continue shopping') ?></a>
        </div>
      </div>

<?php if (!$all_lines): ?>
      <div class="callout">
        <h2 class="callout__title"><?= te('Your cart is empty') ?></h2>
        <p><?= te('Browse the catalogue and add something to it.') ?> <a href="catalogue.php"><?= te('Browse the catalogue') ?></a></p>
      </div>
<?php else: ?>
      <div class="layout-aside">
        <div>
          <section class="filter-bar" aria-labelledby="cart-filter-heading">
            <h2 class="visually-hidden" id="cart-filter-heading"><?= te('Sort and filter cart items') ?></h2>
            <form class="filter-bar__form" action="cart.php" method="get">
              <div class="filter-bar__grid">
                <p class="field">
                  <label class="field__label" for="filter-q"><?= te('Item contains') ?></label>
                  <input type="search" id="filter-q" name="q" value="<?= e($f['q']) ?>" placeholder="<?= te('e.g. brush') ?>" aria-describedby="filter-q-hint">
                  <span class="field__hint" id="filter-q-hint"><?= te('Matches the product title, variant or your note.') ?></span>
                </p>
                <p class="field">
                  <label class="field__label" for="filter-sort"><?= te('Sort by') ?></label>
                  <select id="filter-sort" name="sort">
<?php foreach ($sorts as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $f['sort'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
                  </select>
                </p>
                <div class="filter-bar__actions">
                  <button type="submit" class="button button--primary"><?= te('Apply') ?></button>
                  <a class="button button--quiet" href="cart.php"><?= te('Reset') ?></a>
                </div>
              </div>
            </form>
          </section>

          <?= error_summary_html($errors) ?>
          <form method="post" action="cart.php" id="cart-form" data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <section aria-labelledby="lines-heading">
              <h2 class="visually-hidden" id="lines-heading"><?= te('Items in your cart') ?></h2>

              <div class="table-scroll">
                <table class="data-table">
                  <caption><?= te('%1$s of %2$s lines shown, with unit price, quantity and line total.', [number(count($lines)), number(count($all_lines))]) ?></caption>
                  <thead>
                    <tr>
                      <th scope="col"><?= te('Product') ?></th>
                      <th scope="col"><?= te('Variant and note') ?></th>
                      <th scope="col" class="is-numeric"><?= te('Unit price') ?></th>
                      <th scope="col"><?= te('Quantity') ?></th>
                      <th scope="col" class="is-numeric"><?= te('Line total') ?></th>
                      <th scope="col"><span class="visually-hidden"><?= te('Remove item') ?></span></th>
                    </tr>
                  </thead>

                  <tbody>
<?php foreach ($lines as $l):
    $p = $l['product'];
    $lid = (int) $l['id'];
    $maxQty = max(1, min((int) $p['max_qty'], (int) $p['stock'])); ?>
                    <tr data-line="<?= $lid ?>" data-base-cents="<?= (int) $p['price_cents'] ?>">
                      <th scope="row">
                        <span class="cart-line__identity">
                          <img class="cart-line__media" src="<?= e(image_src($p['image'])) ?>" alt="<?= e($p['alt']) ?>">
                          <span>
                            <a class="cart-line__name" href="product.php?id=<?= e($l['product_id']) ?>"><?= e($p['name']) ?></a>
                            <span class="text-small text-muted"><?= e($p['code']) ?></span>
                          </span>
                        </span>
                      </th>
                      <td>
                        <label class="visually-hidden" for="variant-<?= $lid ?>"><?= te('%1$s for %2$s', [t($p['variant_label']), $p['name']]) ?></label>
                        <select id="variant-<?= $lid ?>" name="variant[<?= $lid ?>]" data-required<?= invalid_attrs($errors, 'variant-' . $lid) ?>>
<?php foreach ($p['variants'] as $key => $v): ?>
                          <option value="<?= e($key) ?>" data-extra-cents="<?= (int) $v['extra_cents'] ?>"<?= $l['variant'] === (string) $key ? ' selected' : '' ?>><?= te($v['label']) ?></option>
<?php endforeach; ?>
                        </select>
                        <?= error_html($errors, 'variant-' . $lid) ?>
                        <label class="visually-hidden" for="note-<?= $lid ?>"><?= te('Note for %1$s', [$p['name']]) ?></label>
                        <input type="text" id="note-<?= $lid ?>" name="note[<?= $lid ?>]" value="<?= e($l['note'] ?? '') ?>" placeholder="<?= te('Note (optional)') ?>" data-maxlength="80" class="mt-s"<?= invalid_attrs($errors, 'note-' . $lid) ?>>
                        <?= error_html($errors, 'note-' . $lid) ?>
                      </td>
                      <td class="is-numeric" data-unit><?= money_html($l['unit_cents']) ?></td>
                      <td>
                        <span class="qty-control">
                          <label class="visually-hidden" for="qty-<?= $lid ?>"><?= te('Quantity of %1$s', [$p['name']]) ?></label>
                          <input type="text" inputmode="numeric" id="qty-<?= $lid ?>" name="qty[<?= $lid ?>]" value="<?= e((string) $l['quantity']) ?>" data-required data-type="int" data-min="1" data-max="<?= $maxQty ?>"<?= invalid_attrs($errors, 'qty-' . $lid) ?>>
                        </span>
                        <?= error_html($errors, 'qty-' . $lid) ?>
                      </td>
                      <td class="is-numeric" data-line-total><?= money_html($l['line_cents']) ?></td>
                      <td>
                        <button type="submit" class="button button--small button--icon button--danger" form="remove-<?= $lid ?>" aria-label="<?= te('Remove %1$s from cart', [$p['name']]) ?>">
                          <svg class="button__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
                            <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M4 6.5h16M9.5 6.5V4h5v2.5"/>
                              <path d="M6.5 6.5 7.5 20h9l1-13.5"/>
                              <path d="M10.5 10v6M13.5 10v6"/>
                            </g>
                          </svg>
                        </button>
                      </td>
                    </tr>
<?php endforeach; ?>
                  </tbody>

                  <tfoot>
                    <tr>
                      <th scope="row" colspan="4"><?= te('Subtotal') ?></th>
                      <td class="is-numeric" data-subtotal><?= money_html($totals['subtotal']) ?></td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </section>

            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te('Update cart') ?></button>
              <a class="button button--quiet" href="catalogue.php"><?= te('Continue shopping') ?></a>
              <button type="submit" class="button button--quiet button--danger" form="clear-form"><?= te('Empty the cart') ?></button>
            </div>
          </form>

<?php foreach ($lines as $l): ?>
          <form method="post" action="cart.php" id="remove-<?= (int) $l['id'] ?>" class="visually-hidden">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="line" value="<?= (int) $l['id'] ?>">
          </form>
<?php endforeach; ?>
          <form method="post" action="cart.php" id="clear-form" class="visually-hidden">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear">
          </form>

          <section class="section" aria-labelledby="promo-heading">
            <div class="section__head">
              <h2 id="promo-heading"><?= te('Discount code') ?></h2>
            </div>
<?php if ($totals['promo']): ?>
            <p><?= te('Code %1$s applied: %2$s.', [$totals['promo'], t(GF_PROMO_CODES[$totals['promo']]['label'])]) ?></p>
            <form method="post" action="cart.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="promo-remove">
              <button type="submit" class="button button--small button--quiet"><?= te('Remove the code') ?></button>
            </form>
<?php else: ?>
            <form class="form" method="post" action="cart.php" data-validate>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="promo">
              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="promo-code"><?= te('Discount code') ?></label>
                  <input type="text" id="promo-code" name="promo-code" autocomplete="off" data-required data-maxlength="20"<?= invalid_attrs($errors, 'promo-code', 'promo-code-hint') ?>>
                  <?= error_html($errors, 'promo-code') ?>
                  <span class="field__hint" id="promo-code-hint"><?= te('One code per order. Codes are not case sensitive. Try FORGE10.') ?></span>
                </p>
              </div>
              <div class="form__actions">
                <button type="submit" class="button"><?= te('Apply code') ?></button>
              </div>
            </form>
<?php endif; ?>
          </section>
        </div>

        <aside class="layout-aside__side" aria-labelledby="totals-heading">
          <div class="panel">
            <h2 class="panel__title" id="totals-heading"><?= te('Order summary') ?></h2>
            <dl class="summary-list">
              <div class="summary-list__row">
                <dt><?= te('Subtotal') ?></dt>
                <dd><?= money_html($totals['subtotal']) ?></dd>
              </div>
<?php if ($totals['discount'] > 0): ?>
              <div class="summary-list__row">
                <dt><?= te('Discount (%1$s)', [$totals['promo']]) ?></dt>
                <dd>−<?= money_html($totals['discount']) ?></dd>
              </div>
<?php endif; ?>
              <div class="summary-list__row">
                <dt><?= te('Delivery (standard)') ?></dt>
                <dd><?= $totals['delivery'] === 0 ? te('Free') : money_html($totals['delivery']) ?></dd>
              </div>
              <div class="summary-list__row">
                <dt><?= te('GST included') ?></dt>
                <dd><?= money_html($totals['gst']) ?></dd>
              </div>
              <div class="summary-list__row summary-list__row--total">
                <dt><?= te('Total') ?></dt>
                <dd><?= money_html($totals['total']) ?></dd>
              </div>
            </dl>
            <p class="mt-m">
              <a class="button button--primary button--block" href="checkout.php"><?= te('Continue to checkout') ?></a>
            </p>
          </div>

          <div class="callout">
            <h2 class="callout__title"><?= te('Delivery') ?></h2>
            <p class="text-small"><?= te('Standard delivery arrives in three to six business days. Orders over %1$s are delivered free within Australia.', [money(GF_FREE_DELIVERY_CENTS)]) ?></p>
          </div>
        </aside>
      </div>
<?php endif; ?>

<?php require __DIR__ . '/../shared/footer.php'; ?>
