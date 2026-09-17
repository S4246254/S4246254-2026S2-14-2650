<?php
/** Order confirmation: summarises a placed order, formatted for the active locale. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$orders = collection('orders');
$order = $id && isset($orders[$id]) ? $orders[$id] : null;

/* An order can only be viewed by the account that placed it. */
if (!$order || !owns($order['user'])) {
    http_response_code($order ? 403 : 404);
    $page_title = 'Order not found';
    $page_description = 'The requested order could not be shown.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('Order not found') . '</h1><p class="page-head__lede">' . te('That order does not exist, or it was placed by a different account.') . '</p></div></div>';
    echo '<p><a class="button" href="catalogue.php">' . te('Back to the catalogue') . '</a></p>';
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$totals = $order['totals'];
$ship = GF_SHIPPING[$order['shipping']] ?? GF_SHIPPING['standard'];
$placed = strtotime($order['placed']);
$eta_from = date('Y-m-d', strtotime('+3 weekdays', $placed));
$eta_to = date('Y-m-d', strtotime('+6 weekdays', $placed));
if ($order['shipping'] === 'express') {
    $eta_from = date('Y-m-d', strtotime('+1 weekday', $placed));
    $eta_to = date('Y-m-d', strtotime('+2 weekdays', $placed));
}
$states = ['act' => 'ACT', 'nsw' => 'NSW', 'nt' => 'NT', 'qld' => 'QLD', 'sa' => 'SA', 'tas' => 'TAS', 'vic' => 'VIC', 'wa' => 'WA'];
$countries = ['au' => 'Australia', 'nz' => 'New Zealand'];
$a = $order['address'];

$page_title = 'Order confirmation';
$page_description = 'Your Grimdark Forge order has been placed.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="catalogue.php"><?= te('Shop') ?></a></li>
          <li aria-current="page"><?= te('Order confirmation') ?></li>
        </ol>
      </nav>

      <ol class="steps">
        <li class="steps__item steps__item--done">1. <?= te('Cart') ?></li>
        <li class="steps__item steps__item--done">2. <?= te('Checkout') ?></li>
        <li class="steps__item" aria-current="step">3. <?= te('Confirmation') ?></li>
      </ol>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Thank you — your order is confirmed') ?></h1>
          <p class="page-head__lede"><?= t('Order %1$s, placed', ['<strong>' . e($order['reference']) . '</strong>']) ?> <?= time_html($order['placed'], 'datetime') ?>.</p>
        </div>
        <div class="page-head__actions">
          <a class="button" href="catalogue.php"><?= te('Continue shopping') ?></a>
        </div>
      </div>

      <div class="callout callout--success">
        <h2 class="callout__title"><?= te('A confirmation email is on its way') ?></h2>
<?php if ($order['shipping'] === 'pickup'): ?>
        <p><?= t('We have sent the receipt to %1$s. Your order will be ready to collect in store within four hours.', [e($order['contact']['email'])]) ?></p>
<?php else: ?>
        <p><?= t('We have sent the receipt and a tracking link to %1$s. Estimated delivery between %2$s and %3$s.', [e($order['contact']['email']), time_html($eta_from), time_html($eta_to)]) ?></p>
<?php endif; ?>
      </div>

      <div class="layout-aside">
        <div>
          <section aria-labelledby="items-heading">
            <div class="section__head">
              <h2 id="items-heading"><?= te('What you ordered') ?></h2>
            </div>

            <div class="table-scroll">
              <table class="data-table">
                <caption><?= te('%1$s items in order %2$s.', [number(count($order['items'])), $order['reference']]) ?></caption>
                <thead>
                  <tr>
                    <th scope="col"><?= te('Product') ?></th>
                    <th scope="col"><?= te('Variant') ?></th>
                    <th scope="col" class="is-numeric"><?= te('Unit price') ?></th>
                    <th scope="col" class="is-numeric"><?= te('Quantity') ?></th>
                    <th scope="col" class="is-numeric"><?= te('Line total') ?></th>
                  </tr>
                </thead>
                <tbody>
<?php foreach ($order['items'] as $it): ?>
                  <tr>
                    <th scope="row">
                      <span class="cart-line__identity">
                        <img class="cart-line__media" src="<?= e(image_src($it['image'])) ?>" alt="<?= e($it['alt']) ?>">
                        <span>
                          <a class="cart-line__name" href="product.php?id=<?= e($it['product_id']) ?>"><?= e($it['name']) ?></a>
                          <span class="text-small text-muted"><?= e($it['code']) ?></span>
                        </span>
                      </span>
                    </th>
                    <td><?= te($it['variant']) ?><?= $it['note'] !== '' ? '<br><span class="text-small text-muted">' . e($it['note']) . '</span>' : '' ?></td>
                    <td class="is-numeric"><?= money_html((int) $it['unit_cents']) ?></td>
                    <td class="is-numeric"><?= number((int) $it['quantity']) ?></td>
                    <td class="is-numeric"><?= money_html((int) $it['line_cents']) ?></td>
                  </tr>
<?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <th scope="row" colspan="4"><?= te('Total paid') ?></th>
                    <td class="is-numeric"><?= money_html((int) $totals['total']) ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </section>

          <section class="section" aria-labelledby="details-heading">
            <div class="section__head">
              <h2 id="details-heading"><?= te('Order details') ?></h2>
            </div>

            <dl class="spec-list">
              <dt><?= te('Delivery address') ?></dt>
              <dd><?= e($order['contact']['name']) ?>, <?= e($a['street']) ?><?= $a['street2'] !== '' ? ', ' . e($a['street2']) : '' ?>, <?= e($a['suburb']) ?> <?= e($states[$a['state']] ?? $a['state']) ?> <?= e($a['postcode']) ?>, <?= te($countries[$a['country']] ?? $a['country']) ?></dd>

              <dt><?= te('Delivery method') ?></dt>
              <dd><?= te($ship['label']) ?></dd>

<?php if ($order['notes'] !== ''): ?>
              <dt><?= te('Delivery instructions') ?></dt>
              <dd><?= e($order['notes']) ?></dd>
<?php endif; ?>

              <dt><?= te('Payment method') ?></dt>
              <dd><?= te('%1$s ending %2$s', [$order['card_brand'], $order['card_last4']]) ?></dd>

              <dt><?= te('Billing address') ?></dt>
              <dd><?= $order['billing_same'] ? te('Same as the delivery address') : te('Different from the delivery address') ?></dd>

              <dt><?= te('Order reference') ?></dt>
              <dd><?= e($order['reference']) ?></dd>

              <dt><?= te('Placed by') ?></dt>
              <dd><?= e(user_name($order['user'])) ?></dd>
            </dl>
          </section>
        </div>

        <aside class="layout-aside__side" aria-labelledby="payment-heading">
          <div class="panel">
            <h2 class="panel__title" id="payment-heading"><?= te('Payment summary') ?></h2>
            <dl class="summary-list">
              <div class="summary-list__row">
                <dt><?= te('Subtotal') ?></dt>
                <dd><?= money_html((int) $totals['subtotal']) ?></dd>
              </div>
<?php if ($totals['discount'] > 0): ?>
              <div class="summary-list__row">
                <dt><?= te('Discount (%1$s)', [$totals['promo']]) ?></dt>
                <dd>−<?= money_html((int) $totals['discount']) ?></dd>
              </div>
<?php endif; ?>
              <div class="summary-list__row">
                <dt><?= te('Delivery') ?></dt>
                <dd><?= $totals['delivery'] === 0 ? te('Free') : money_html((int) $totals['delivery']) ?></dd>
              </div>
              <div class="summary-list__row">
                <dt><?= te('GST included') ?></dt>
                <dd><?= money_html((int) $totals['gst']) ?></dd>
              </div>
              <div class="summary-list__row summary-list__row--total">
                <dt><?= te('Total paid') ?></dt>
                <dd><?= money_html((int) $totals['total']) ?></dd>
              </div>
            </dl>
          </div>

          <div class="panel">
            <h2 class="panel__title"><?= te('What happens next') ?></h2>
            <div class="prose">
              <ol>
                <li><?= te('We pick and pack your order, usually the next business day.') ?></li>
                <li><?= te('You get an email with a tracking number once it ships.') ?></li>
                <li><?= te('When it arrives, come back and review what you bought.') ?></li>
              </ol>
            </div>
            <p class="mt-m">
              <a class="button button--block" href="../reviews/new-review.php?product=<?= e($order['items'][0]['product_id']) ?>"><?= te('Write a review') ?></a>
            </p>
          </div>

          <div class="callout">
            <h2 class="callout__title"><?= te('Need to change something?') ?></h2>
            <p class="text-small"><?= te('Orders can be amended until they are packed. Reply to your confirmation email or ask in the forum.') ?></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
