<?php
/** Product detail with the add-to-cart form (the cart's "create"). */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_DEFAULT);
$p = product(is_string($id) ? $id : null);

if (!$p) {
    http_response_code(404);
    $page_title = 'Product not found';
    $page_description = 'The requested product does not exist.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('Product not found') . '</h1><p class="page-head__lede">' . te('That product does not exist.') . '</p></div></div>';
    echo '<p><a class="button" href="catalogue.php">' . te('Back to the catalogue') . '</a></p>';
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$p['id'] = $id;
$rating = product_rating($id);
[$stockClass, $stockText] = stock_status($p);
$maxQty = max(1, min((int) $p['max_qty'], (int) $p['stock']));

/* Values for the add-to-cart form; errors come back from cart.php via the session. */
$errors = $_SESSION['cart_errors'] ?? [];
$values = $_SESSION['cart_values'] ?? [];
unset($_SESSION['cart_errors'], $_SESSION['cart_values']);
$values += ['variant' => variant_keys($p)[0], 'quantity' => '1', 'note' => ''];

/* Related: other products, same category first. */
$related = [];
foreach (collection('products') as $rid => $r) {
    if ($rid !== $id) {
        $related[$rid] = $r;
    }
}
uasort($related, function ($a, $b) use ($p) {
    return ($b['category'] === $p['category']) <=> ($a['category'] === $p['category']);
});
$related = array_slice($related, 0, 3, true);

$product_reviews = reviews_query(['product' => $id, 'q' => '', 'text' => '', 'rating' => '', 'reviewer' => '', 'from' => '', 'to' => '', 'sort' => 'date-desc']);

$page_title = $p['name'];
$page_description = $p['summary'];
$page_scripts = ['assets/js/shop.js'];
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="catalogue.php"><?= te('Shop') ?></a></li>
          <li aria-current="page"><?= e($p['name']) ?></li>
        </ol>
      </nav>

      <div class="product-layout">
        <section aria-label="<?= te('Product images') ?>">
          <div class="product-gallery">
            <img class="product-gallery__main" src="<?= e(image_src($p['image'])) ?>" alt="<?= e($p['alt']) ?>">
<?php if ($p['gallery']): ?>
            <ul class="product-gallery__thumbs">
<?php foreach ($p['gallery'] as [$src, $alt]): ?>
              <li><img class="product-gallery__thumb" src="<?= e(image_src($src)) ?>" alt="<?= e($alt) ?>"></li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
          </div>
        </section>

        <div>
          <h1><?= e($p['name']) ?></h1>

          <p class="rating mt-s">
            <?= rating_html($rating['avg'], $rating['count']) ?>
            <a href="../reviews/reviews.php?product=<?= e($id) ?>"><?= te('Read the reviews') ?></a>
          </p>

          <p class="price mt-s"><?= money_html((int) $p['price_cents']) ?><?php if ($p['was_cents']): ?> <span class="price--was"><?= money_html((int) $p['was_cents']) ?></span><?php endif; ?></p>
          <p class="stock <?= $stockClass ?>"><?= e($stockText) ?><?= (int) $p['stock'] > 0 ? ' — ' . te('dispatched within two business days') : '' ?></p>

          <div class="prose mt-m"><?= paragraphs($p['description']) ?></div>

<?php if ((int) $p['stock'] > 0): ?>
          <?= error_summary_html($errors) ?>
          <form class="form mt-l" method="post" action="cart.php" data-validate id="add-form" data-base-cents="<?= (int) $p['price_cents'] ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product" value="<?= e($id) ?>">
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Choose your options') ?></legend>

              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="variant"><?= te($p['variant_label']) ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <select id="variant" name="variant" data-required<?= invalid_attrs($errors, 'variant', 'variant-hint') ?>>
<?php foreach ($p['variants'] as $key => $v): ?>
                    <option value="<?= e($key) ?>" data-extra-cents="<?= (int) $v['extra_cents'] ?>"<?= $values['variant'] === (string) $key ? ' selected' : '' ?>><?= te($v['label']) ?><?= $v['extra_cents'] ? ' (+' . e(money((int) $v['extra_cents'])) . ')' : '' ?></option>
<?php endforeach; ?>
                  </select>
                  <?= error_html($errors, 'variant') ?>
                  <span class="field__hint" id="variant-hint"><?= te('This can be changed later from the cart.') ?></span>
                </p>

                <p class="field">
                  <label class="field__label" for="quantity"><?= te('Quantity') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="text" inputmode="numeric" id="quantity" name="quantity" value="<?= e($values['quantity']) ?>" data-required data-type="int" data-min="1" data-max="<?= $maxQty ?>"<?= invalid_attrs($errors, 'quantity', 'quantity-hint') ?>>
                  <?= error_html($errors, 'quantity') ?>
                  <span class="field__hint" id="quantity-hint"><?= te('Between 1 and %1$s per order while stock is limited.', [number($maxQty)]) ?></span>
                </p>
              </div>

              <p class="field">
                <label class="field__label" for="note"><?= te('Note for this item') ?></label>
                <input type="text" id="note" name="note" value="<?= e($values['note']) ?>" data-maxlength="80"<?= invalid_attrs($errors, 'note', 'note-hint') ?>>
                <?= error_html($errors, 'note') ?>
                <span class="field__hint" id="note-hint"><?= te('Optional, up to 80 characters — for example a colour preference or "gift, no receipt".') ?></span>
              </p>

              <p class="text-small text-muted"><?= te('Line total') ?>: <strong id="line-total"><?= money_html(unit_cents($p, (string) $values['variant']) * max(1, (int) $values['quantity'])) ?></strong></p>
            </fieldset>

            <div class="form__actions">
              <button type="submit" class="button button--primary">
                <svg class="button__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
                  <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 4h2.2l2.3 10.5h9.4L19 7H6.2"/>
                    <circle cx="9" cy="19" r="1.4"/>
                    <circle cx="17" cy="19" r="1.4"/>
                  </g>
                </svg>
                <?= te('Add to cart') ?>
              </button>
              <a class="button button--quiet" href="catalogue.php"><?= te('Back to catalogue') ?></a>
            </div>
          </form>
<?php else: ?>
          <div class="callout mt-l">
            <h2 class="callout__title"><?= te('Out of stock') ?></h2>
            <p><?= te('This product cannot be added to a cart until it is restocked.') ?></p>
          </div>
<?php endif; ?>
        </div>
      </div>

      <section class="section" aria-labelledby="specs-heading">
        <div class="section__head">
          <h2 id="specs-heading"><?= te('Specifications') ?></h2>
        </div>
        <dl class="spec-list">
<?php foreach ($p['specs'] as [$k, $v]): ?>
          <dt><?= te($k) ?></dt>
          <dd><?= e($v) ?></dd>
<?php endforeach; ?>
          <dt><?= te('Product code') ?></dt>
          <dd><?= e($p['code']) ?></dd>
        </dl>
      </section>

      <section class="section" aria-labelledby="product-reviews-heading">
        <div class="section__head">
          <h2 id="product-reviews-heading"><?= te('%1$s reviews', [number(count($product_reviews))]) ?></h2>
          <a href="../reviews/new-review.php?product=<?= e($id) ?>"><?= te('Write a review') ?></a>
        </div>
<?php if (!$product_reviews): ?>
        <p class="text-muted"><?= te('No reviews yet. Be the first.') ?></p>
<?php else: ?>
        <ul class="comment-list">
<?php foreach (array_slice($product_reviews, 0, 3) as $r): ?>
          <li>
            <article class="comment">
              <p class="rating"><?= rating_html((float) $r['rating']) ?></p>
              <h3 class="card__title"><a href="../reviews/review.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></h3>
              <p class="byline">
                <img class="byline__avatar" src="../<?= e(user_avatar($r['author'])) ?>" alt="">
                <span class="byline__name"><?= e(user_name($r['author'])) ?></span>
                <?= time_html($r['date']) ?>
              </p>
              <p><?= e(excerpt($r['body'])) ?></p>
            </article>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </section>

      <section class="section" aria-labelledby="related-heading">
        <div class="section__head">
          <h2 id="related-heading"><?= te('Often bought with this') ?></h2>
          <a href="catalogue.php"><?= te('See all products') ?></a>
        </div>

        <div class="grid-cards">
<?php foreach ($related as $rid => $r):
    [$rc, $rt] = stock_status($r); ?>
          <article class="card">
            <img class="card__media" src="<?= e(image_src($r['image'])) ?>" alt="<?= e($r['alt']) ?>">
            <div class="card__body">
              <h3 class="card__title"><a href="product.php?id=<?= e($rid) ?>"><?= e($r['name']) ?></a></h3>
              <p class="card__summary"><?= e($r['summary']) ?></p>
              <div class="card__footer">
                <p class="price"><?= money_html((int) $r['price_cents']) ?></p>
                <span class="stock <?= $rc ?>"><?= e($rt) ?></span>
              </div>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </section>

<?php require __DIR__ . '/../shared/footer.php'; ?>
