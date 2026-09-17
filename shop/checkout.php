<?php
/**
 * Checkout: delivery details and (simulated) card payment. Validated by
 * JavaScript and again by PHP. Card details are checked and then discarded —
 * only the last four digits are kept on the order record.
 */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

if (!is_logged_in()) {
    flash('danger', t('Log in to check out. Your cart will be kept.'));
    redirect('../account.php?return=' . rawurlencode('shop/checkout.php'));
}

$lines = cart_lines();
if (!$lines) {
    flash('danger', t('Your cart is empty, so there is nothing to check out.'));
    redirect('cart.php');
}

const GF_STATES = [
    'act' => 'Australian Capital Territory', 'nsw' => 'New South Wales', 'nt' => 'Northern Territory',
    'qld' => 'Queensland', 'sa' => 'South Australia', 'tas' => 'Tasmania', 'vic' => 'Victoria', 'wa' => 'Western Australia',
];
const GF_COUNTRIES = ['au' => 'Australia', 'nz' => 'New Zealand'];

$rules = [
    'contact-name'      => ['label' => 'Full name', 'required' => true, 'min' => 2, 'max' => 80],
    'contact-email'     => ['label' => 'Email address', 'required' => true, 'type' => 'email', 'max' => 120],
    'contact-phone'     => ['label' => 'Phone number', 'type' => 'phone'],
    'delivery-street'   => ['label' => 'Street address', 'required' => true, 'min' => 3, 'max' => 120],
    'delivery-street2'  => ['label' => 'Apartment, unit or level', 'max' => 60],
    'delivery-suburb'   => ['label' => 'Suburb', 'required' => true, 'min' => 2, 'max' => 60],
    'delivery-state'    => ['label' => 'State or territory', 'required' => true, 'in' => array_keys(GF_STATES)],
    'delivery-postcode' => ['label' => 'Postcode', 'required' => true, 'type' => 'postcode'],
    'delivery-country'  => ['label' => 'Country', 'required' => true, 'in' => array_keys(GF_COUNTRIES)],
    'delivery-notes'    => ['label' => 'Delivery instructions', 'max' => 300],
    'shipping'          => ['label' => 'Delivery method', 'required' => true, 'in' => array_keys(GF_SHIPPING)],
    'card-name'         => ['label' => 'Name on card', 'required' => true, 'min' => 2, 'max' => 80],
    'card-number'       => ['label' => 'Card number', 'required' => true, 'type' => 'card'],
    'card-expiry'       => ['label' => 'Expiry date', 'required' => true, 'type' => 'expiry'],
    'card-csc'          => ['label' => 'Security code', 'required' => true, 'type' => 'csc'],
    'billing-same'      => ['label' => 'Billing address is the same'],
    'terms'             => ['label' => 'I accept the terms of sale and the returns policy', 'checked' => true],
    'newsletter'        => ['label' => 'Newsletter'],
];
$errors = [];
$me = user(current_user());
$values = array_fill_keys(array_keys($rules), '');
$values['contact-name'] = $me['name'] ?? '';
$values['contact-email'] = $me['email'] ?? '';
$values['delivery-country'] = 'au';
$values['shipping'] = 'standard';
$values['billing-same'] = 'yes';

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('checkout.php');
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);

    // Re-check stock at order time, not just at add-to-cart.
    foreach ($lines as $l) {
        if ((int) $l['quantity'] > (int) $l['product']['stock']) {
            $errors['stock'] = t('Only %1$s of %2$s are in stock. Please reduce the quantity in your cart.', [number((int) $l['product']['stock']), $l['product']['name']]);
        }
    }

    if (!$errors) {
        $totals = cart_totals($values['shipping']);
        $orders = &collection('orders');
        $products = &collection('products');
        $orderId = next_id('orders');
        $digits = preg_replace('/\D/', '', $values['card-number']);
        $items = [];
        foreach ($lines as $l) {
            $items[] = [
                'product_id' => $l['product_id'],
                'name'       => $l['product']['name'],
                'code'       => $l['product']['code'],
                'image'      => $l['product']['image'],
                'alt'        => $l['product']['alt'],
                'variant'    => variant_label($l['product'], $l['variant']),
                'note'       => $l['note'] ?? '',
                'quantity'   => (int) $l['quantity'],
                'unit_cents' => $l['unit_cents'],
                'line_cents' => $l['line_cents'],
            ];
            $products[$l['product_id']]['stock'] = max(0, (int) $products[$l['product_id']]['stock'] - (int) $l['quantity']);
        }
        $orders[$orderId] = [
            'reference'   => 'GF-' . date('Y') . '-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT),
            'user'        => current_user(),
            'placed'      => now_iso(),
            'status'      => 'paid',
            'items'       => $items,
            'totals'      => $totals,
            'contact'     => ['name' => $values['contact-name'], 'email' => $values['contact-email'], 'phone' => $values['contact-phone']],
            'address'     => [
                'street' => $values['delivery-street'], 'street2' => $values['delivery-street2'], 'suburb' => $values['delivery-suburb'],
                'state' => $values['delivery-state'], 'postcode' => $values['delivery-postcode'], 'country' => $values['delivery-country'],
            ],
            'notes'       => $values['delivery-notes'],
            'shipping'    => $values['shipping'],
            'card_last4'  => substr($digits, -4),
            'card_brand'  => $digits[0] === '4' ? 'Visa' : ($digits[0] === '5' ? 'Mastercard' : t('Card')),
            'billing_same' => $values['billing-same'] !== '',
            'newsletter'  => $values['newsletter'] !== '',
        ];
        unset($orders, $products);
        cart_clear();
        redirect('order-confirmation.php?id=' . $orderId);
    }
}

$totals = cart_totals($values['shipping'] ?: 'standard');
$goods = $totals['subtotal'] - $totals['discount'];

$page_title = 'Checkout';
$page_description = 'Enter delivery details and simulated payment to complete your Grimdark Forge order.';
$page_scripts = ['assets/js/shop.js'];
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="catalogue.php"><?= te('Shop') ?></a></li>
          <li><a href="cart.php"><?= te('Cart') ?></a></li>
          <li aria-current="page"><?= te('Checkout') ?></li>
        </ol>
      </nav>

      <ol class="steps">
        <li class="steps__item steps__item--done">1. <?= te('Cart') ?></li>
        <li class="steps__item" aria-current="step">2. <?= te('Checkout') ?></li>
        <li class="steps__item">3. <?= te('Confirmation') ?></li>
      </ol>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Checkout') ?></h1>
          <p class="page-head__lede"><?= te('Fields marked with an asterisk are required.') ?></p>
        </div>
      </div>

      <div class="callout">
        <h2 class="callout__title"><?= te('No payment is taken on this page') ?></h2>
        <p><?= te('This checkout does not charge a card and does not send your details anywhere. Please do not enter real card details — use the test values shown beneath each field.') ?></p>
      </div>

      <div class="layout-aside">
        <div>
          <?= error_summary_html($errors) ?>
          <form class="form" method="post" action="checkout.php" data-validate>
            <?= csrf_field() ?>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Contact details') ?></legend>
              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="contact-name"><?= te('Full name') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="contact-name" name="contact-name" value="<?= e($values['contact-name']) ?>" autocomplete="name" data-required data-minlength="2" data-maxlength="80"<?= invalid_attrs($errors, 'contact-name') ?>>
                  <?= error_html($errors, 'contact-name') ?>
                </p>
                <p class="field">
                  <label class="field__label" for="contact-email"><?= te('Email address') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" inputmode="email" id="contact-email" name="contact-email" value="<?= e($values['contact-email']) ?>" autocomplete="email" data-required data-type="email"<?= invalid_attrs($errors, 'contact-email', 'contact-email-hint') ?>>
                  <?= error_html($errors, 'contact-email') ?>
                  <span class="field__hint" id="contact-email-hint"><?= te('Your order confirmation and tracking link are sent here.') ?></span>
                </p>
                <p class="field">
                  <label class="field__label" for="contact-phone"><?= te('Phone number') ?></label>
                  <input type="text" inputmode="tel" id="contact-phone" name="contact-phone" value="<?= e($values['contact-phone']) ?>" autocomplete="tel" data-type="phone"<?= invalid_attrs($errors, 'contact-phone', 'contact-phone-hint') ?>>
                  <?= error_html($errors, 'contact-phone') ?>
                  <span class="field__hint" id="contact-phone-hint"><?= te('Used only if the courier cannot find the address.') ?></span>
                </p>
              </div>
            </fieldset>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Delivery address') ?></legend>
              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="delivery-street"><?= te('Street address') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="delivery-street" name="delivery-street" value="<?= e($values['delivery-street']) ?>" autocomplete="address-line1" data-required data-minlength="3" data-maxlength="120"<?= invalid_attrs($errors, 'delivery-street') ?>>
                  <?= error_html($errors, 'delivery-street') ?>
                </p>
                <p class="field">
                  <label class="field__label" for="delivery-street2"><?= te('Apartment, unit or level') ?></label>
                  <input type="text" id="delivery-street2" name="delivery-street2" value="<?= e($values['delivery-street2']) ?>" autocomplete="address-line2" data-maxlength="60"<?= invalid_attrs($errors, 'delivery-street2') ?>>
                  <?= error_html($errors, 'delivery-street2') ?>
                </p>
                <p class="field">
                  <label class="field__label" for="delivery-suburb"><?= te('Suburb') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="delivery-suburb" name="delivery-suburb" value="<?= e($values['delivery-suburb']) ?>" autocomplete="address-level2" data-required data-minlength="2" data-maxlength="60"<?= invalid_attrs($errors, 'delivery-suburb') ?>>
                  <?= error_html($errors, 'delivery-suburb') ?>
                </p>
                <p class="field">
                  <label class="field__label" for="delivery-state"><?= te('State or territory') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <select id="delivery-state" name="delivery-state" autocomplete="address-level1" data-required<?= invalid_attrs($errors, 'delivery-state') ?>>
                    <option value=""<?= $values['delivery-state'] === '' ? ' selected' : '' ?>><?= te('Choose a state or territory') ?></option>
<?php foreach (GF_STATES as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $values['delivery-state'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= error_html($errors, 'delivery-state') ?>
                </p>
                <p class="field">
                  <label class="field__label" for="delivery-postcode"><?= te('Postcode') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="delivery-postcode" name="delivery-postcode" value="<?= e($values['delivery-postcode']) ?>" autocomplete="postal-code" inputmode="numeric" data-required data-type="postcode"<?= invalid_attrs($errors, 'delivery-postcode', 'delivery-postcode-hint') ?>>
                  <?= error_html($errors, 'delivery-postcode') ?>
                  <span class="field__hint" id="delivery-postcode-hint"><?= te('Four digits, for example 3000.') ?></span>
                </p>
                <p class="field">
                  <label class="field__label" for="delivery-country"><?= te('Country') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <select id="delivery-country" name="delivery-country" autocomplete="country" data-required<?= invalid_attrs($errors, 'delivery-country') ?>>
<?php foreach (GF_COUNTRIES as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $values['delivery-country'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= error_html($errors, 'delivery-country') ?>
                </p>
              </div>
              <p class="field">
                <label class="field__label" for="delivery-notes"><?= te('Delivery instructions') ?></label>
                <textarea id="delivery-notes" name="delivery-notes" data-maxlength="300"<?= invalid_attrs($errors, 'delivery-notes', 'delivery-notes-hint') ?>><?= e($values['delivery-notes']) ?></textarea>
                <?= error_html($errors, 'delivery-notes') ?>
                <span class="field__hint" id="delivery-notes-hint"><?= te('For example, a safe place to leave the parcel if nobody is home.') ?></span>
              </p>
            </fieldset>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Delivery method') ?></legend>
<?php foreach (GF_SHIPPING as $key => $ship):
    $cost = $ship['cents'];
    if ($key === 'standard' && $goods >= GF_FREE_DELIVERY_CENTS) {
        $cost = 0;
    } ?>
              <p class="field field--inline">
                <input type="radio" id="ship-<?= e($key) ?>" name="shipping" value="<?= e($key) ?>" data-cents="<?= (int) $ship['cents'] ?>"<?= $values['shipping'] === $key ? ' checked' : '' ?><?= $key === 'standard' ? ' data-required' : '' ?>>
                <label class="field__label" for="ship-<?= e($key) ?>"><?= te($ship['label']) ?> — <?= $cost === 0 ? te('free') : e(money($cost)) ?></label>
              </p>
<?php endforeach; ?>
              <?= error_html($errors, 'shipping') ?>
            </fieldset>

            <!-- Payment fields use autocomplete="off" so browsers never offer a
                 real stored card on this demonstration checkout. -->
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Payment') ?></legend>
              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="card-name"><?= te('Name on card') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="card-name" name="card-name" value="<?= e($values['card-name']) ?>" autocomplete="off" data-required data-minlength="2" data-maxlength="80"<?= invalid_attrs($errors, 'card-name') ?>>
                  <?= error_html($errors, 'card-name') ?>
                </p>
                <p class="field">
                  <label class="field__label" for="card-number"><?= te('Card number') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="card-number" name="card-number" value="" autocomplete="off" inputmode="numeric" data-required data-type="card"<?= invalid_attrs($errors, 'card-number', 'card-number-hint') ?>>
                  <?= error_html($errors, 'card-number') ?>
                  <span class="field__hint" id="card-number-hint"><?= te('Use the test number 4111 1111 1111 1111. Never enter a real card number.') ?></span>
                </p>
                <p class="field">
                  <label class="field__label" for="card-expiry"><?= te('Expiry date') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="card-expiry" name="card-expiry" value="<?= e($values['card-expiry']) ?>" autocomplete="off" placeholder="MM/YY" data-required data-type="expiry"<?= invalid_attrs($errors, 'card-expiry', 'card-expiry-hint') ?>>
                  <?= error_html($errors, 'card-expiry') ?>
                  <span class="field__hint" id="card-expiry-hint"><?= te('Two-digit month and year, for example 04/29.') ?></span>
                </p>
                <p class="field">
                  <label class="field__label" for="card-csc"><?= te('Security code') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" id="card-csc" name="card-csc" value="" autocomplete="off" inputmode="numeric" data-required data-type="csc"<?= invalid_attrs($errors, 'card-csc', 'card-csc-hint') ?>>
                  <?= error_html($errors, 'card-csc') ?>
                  <span class="field__hint" id="card-csc-hint"><?= te('Three digits on the back of the card. Use 123.') ?></span>
                </p>
              </div>
              <p class="field field--inline">
                <input type="checkbox" id="billing-same" name="billing-same" value="yes"<?= $values['billing-same'] !== '' ? ' checked' : '' ?>>
                <label class="field__label" for="billing-same"><?= te('My billing address is the same as my delivery address') ?></label>
              </p>
            </fieldset>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Confirm your order') ?></legend>
              <p class="field field--inline">
                <input type="checkbox" id="terms" name="terms" value="yes" data-checked<?= $values['terms'] !== '' ? ' checked' : '' ?><?= invalid_attrs($errors, 'terms', 'terms-hint') ?>>
                <label class="field__label" for="terms"><?= te('I accept the terms of sale and the returns policy') ?></label>
              </p>
              <?= error_html($errors, 'terms') ?>
              <span class="field__hint" id="terms-hint"><?= te('Required. Unopened kits may be returned within 30 days.') ?></span>
              <p class="field field--inline">
                <input type="checkbox" id="newsletter" name="newsletter" value="yes"<?= $values['newsletter'] !== '' ? ' checked' : '' ?>>
                <label class="field__label" for="newsletter"><?= te('Email me when new kits and paints arrive') ?></label>
              </p>
            </fieldset>

<?php if (isset($errors['stock'])): ?>
            <div class="callout callout--danger"><p><?= e($errors['stock']) ?></p></div>
<?php endif; ?>

            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te('Place order') ?> — <span data-place-total><?= money_html($totals['total']) ?></span></button>
              <a class="button button--quiet" href="cart.php"><?= te('Back to cart') ?></a>
            </div>
          </form>
        </div>

        <aside class="layout-aside__side" aria-labelledby="summary-heading">
          <div class="panel">
            <h2 class="panel__title" id="summary-heading"><?= te('Order summary') ?></h2>
            <ul class="footer-nav__list">
<?php foreach ($lines as $l): ?>
              <li>
                <span><?= e($l['product']['name']) ?> &times; <?= number((int) $l['quantity']) ?></span>
                <span class="sitemap-tree__note"><?= te(variant_label($l['product'], $l['variant'])) ?> — <?= money_html($l['line_cents']) ?></span>
              </li>
<?php endforeach; ?>
            </ul>

            <dl class="summary-list mt-m" data-goods-cents="<?= $goods ?>" data-free-over="<?= GF_FREE_DELIVERY_CENTS ?>" data-free-label="<?= te('Free') ?>">
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
                <dt><?= te('Delivery') ?></dt>
                <dd data-delivery><?= $totals['delivery'] === 0 ? te('Free') : money_html($totals['delivery']) ?></dd>
              </div>
              <div class="summary-list__row">
                <dt><?= te('GST included') ?></dt>
                <dd data-gst><?= money_html($totals['gst']) ?></dd>
              </div>
              <div class="summary-list__row summary-list__row--total">
                <dt><?= te('Total') ?></dt>
                <dd data-total><?= money_html($totals['total']) ?></dd>
              </div>
            </dl>

            <p class="mt-m"><a href="cart.php"><?= te('Edit your cart') ?></a></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
