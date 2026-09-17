<?php
/** Product catalogue: retrieve, sort and filter products; add to cart from a card. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$sorts = [
    'relevance'   => 'Most relevant',
    'title-asc'   => 'Title, A to Z',
    'title-desc'  => 'Title, Z to A',
    'price-asc'   => 'Price, low to high',
    'price-desc'  => 'Price, high to low',
    'rating-desc' => 'Highest rated',
    'stock-desc'  => 'Most in stock',
];

$f = [
    'title'     => trim((string) (filter_input(INPUT_GET, 'title', FILTER_DEFAULT) ?? '')),
    'category'  => (string) (filter_input(INPUT_GET, 'category', FILTER_DEFAULT) ?? ''),
    'colour'    => (string) (filter_input(INPUT_GET, 'colour', FILTER_DEFAULT) ?? ''),
    'scale'     => (string) (filter_input(INPUT_GET, 'scale', FILTER_DEFAULT) ?? ''),
    'max-price' => trim((string) (filter_input(INPUT_GET, 'max-price', FILTER_DEFAULT) ?? '')),
    'min-qty'   => trim((string) (filter_input(INPUT_GET, 'min-qty', FILTER_DEFAULT) ?? '')),
    'sort'      => (string) (filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'relevance'),
];
if (!isset($sorts[$f['sort']])) {
    $f['sort'] = 'relevance';
}
if ($f['category'] !== '' && !isset(GF_CATEGORIES[$f['category']])) {
    $f['category'] = '';
}
if ($f['colour'] !== '' && !isset(GF_COLOURS[$f['colour']])) {
    $f['colour'] = '';
}
if ($f['scale'] !== '' && !isset(GF_SCALES[$f['scale']])) {
    $f['scale'] = '';
}
$maxPrice = preg_match('/^\d+(\.\d{1,2})?$/', $f['max-price']) ? (int) round((float) $f['max-price'] * 100) : null;
$minQty = preg_match('/^\d+$/', $f['min-qty']) ? (int) $f['min-qty'] : null;

$rows = [];
foreach (collection('products') as $id => $p) {
    if ($f['title'] !== '' && mb_stripos($p['name'], $f['title']) === false) {
        continue;
    }
    if ($f['category'] !== '' && $p['category'] !== $f['category']) {
        continue;
    }
    if ($f['colour'] !== '' && $p['colour'] !== $f['colour']) {
        continue;
    }
    if ($f['scale'] !== '' && $p['scale'] !== $f['scale']) {
        continue;
    }
    if ($maxPrice !== null && (int) $p['price_cents'] > $maxPrice) {
        continue;
    }
    if ($minQty !== null && (int) $p['stock'] < $minQty) {
        continue;
    }
    $rows[$id] = $p + ['id' => $id, 'rating' => product_rating($id)];
}
$sort = $f['sort'];
uasort($rows, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'title-asc':   return strcasecmp($a['name'], $b['name']);
        case 'title-desc':  return strcasecmp($b['name'], $a['name']);
        case 'price-asc':   return $a['price_cents'] <=> $b['price_cents'];
        case 'price-desc':  return $b['price_cents'] <=> $a['price_cents'];
        case 'rating-desc': return $b['rating']['avg'] <=> $a['rating']['avg'] ?: $b['rating']['count'] <=> $a['rating']['count'];
        case 'stock-desc':  return $b['stock'] <=> $a['stock'];
        default:            return 0;
    }
});
$page = (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
[$products, $page, $pages, $total] = paginate(array_values($rows), $page, 8);

$page_title = 'Shop catalogue';
$page_description = 'Kits, paints, brushes, terrain and rules. Filter by title, colour, scale, price and stock, then add to your cart.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li aria-current="page"><?= te('Shop') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Shop catalogue') ?></h1>
          <p class="page-head__lede"><?= te('Kits, paints, brushes, terrain and rules. Prices are in Australian dollars and include GST.') ?></p>
        </div>
        <div class="page-head__actions">
          <a class="button" href="cart.php">
            <svg class="button__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
              <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 4h2.2l2.3 10.5h9.4L19 7H6.2"/>
                <circle cx="9" cy="19" r="1.4"/>
                <circle cx="17" cy="19" r="1.4"/>
              </g>
            </svg>
            <?= te('Your cart (%1$s)', [number(cart_count())]) ?>
          </a>
        </div>
      </div>

      <section class="filter-bar" aria-labelledby="filter-heading">
        <h2 class="visually-hidden" id="filter-heading"><?= te('Sort and filter products') ?></h2>
        <form class="filter-bar__form" action="catalogue.php" method="get" data-validate>
          <div class="filter-bar__grid">
            <p class="field">
              <label class="field__label" for="filter-title"><?= te('Product title contains') ?></label>
              <input type="search" id="filter-title" name="title" value="<?= e($f['title']) ?>" placeholder="<?= te('e.g. sentinel') ?>" aria-describedby="filter-title-hint">
              <span class="field__hint" id="filter-title-hint"><?= te('Matches product names, not descriptions.') ?></span>
            </p>

            <p class="field">
              <label class="field__label" for="filter-category"><?= te('Category') ?></label>
              <select id="filter-category" name="category">
                <option value=""<?= $f['category'] === '' ? ' selected' : '' ?>><?= te('Any category') ?></option>
<?php foreach (GF_CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $f['category'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
              </select>
            </p>

            <p class="field">
              <label class="field__label" for="filter-colour"><?= te('Colour') ?></label>
              <select id="filter-colour" name="colour">
                <option value=""<?= $f['colour'] === '' ? ' selected' : '' ?>><?= te('Any colour') ?></option>
<?php foreach (GF_COLOURS as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $f['colour'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
              </select>
            </p>

            <p class="field">
              <label class="field__label" for="filter-scale"><?= te('Size or scale') ?></label>
              <select id="filter-scale" name="scale">
                <option value=""<?= $f['scale'] === '' ? ' selected' : '' ?>><?= te('Any size') ?></option>
<?php foreach (GF_SCALES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $f['scale'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
              </select>
            </p>

            <p class="field">
              <label class="field__label" for="filter-price"><?= te('Maximum price') ?></label>
              <input type="text" inputmode="decimal" id="filter-price" name="max-price" value="<?= e($f['max-price']) ?>" placeholder="100" aria-describedby="filter-price-hint">
              <span class="field__hint" id="filter-price-hint"><?= te('In Australian dollars.') ?></span>
            </p>

            <p class="field">
              <label class="field__label" for="filter-quantity"><?= te('Minimum quantity in stock') ?></label>
              <input type="text" inputmode="numeric" id="filter-quantity" name="min-qty" value="<?= e($f['min-qty']) ?>" placeholder="1" data-type="int" data-min="0" data-max="999" aria-describedby="filter-quantity-hint">
              <?= error_html([], 'min-qty') ?>
              <span class="field__hint" id="filter-quantity-hint"><?= te('Use 1 to hide everything that is out of stock.') ?></span>
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
              <button type="submit" class="button button--primary"><?= te('Apply filters') ?></button>
              <a class="button button--quiet" href="catalogue.php"><?= te('Reset') ?></a>
            </div>
          </div>
        </form>
      </section>

      <div class="result-summary">
        <p><?= te('Showing %1$s of %2$s products.', [number(count($products)), number($total)]) ?></p>
        <p><?= te('Page %1$s of %2$s', [number($page), number($pages)]) ?></p>
      </div>

      <section class="section" aria-labelledby="products-heading">
        <h2 class="visually-hidden" id="products-heading"><?= te('Products') ?></h2>

<?php if (!$products): ?>
        <div class="callout">
          <h3 class="callout__title"><?= te('No products match') ?></h3>
          <p><?= te('Try fewer filters or a higher maximum price.') ?></p>
        </div>
<?php else: ?>
        <div class="grid-cards">
<?php foreach ($products as $p):
    [$stockClass, $stockText] = stock_status($p);
    $firstVariant = array_key_first($p['variants']); ?>
          <article class="card">
            <img class="card__media" src="<?= e(image_src($p['image'])) ?>" alt="<?= e($p['alt']) ?>">
            <div class="card__body">
              <h3 class="card__title"><a href="product.php?id=<?= e($p['id']) ?>"><?= e($p['name']) ?></a></h3>
              <p class="card__meta">
<?php foreach ($p['meta'] as $m): ?>
                <span><?= te($m) ?></span>
<?php endforeach; ?>
              </p>
              <p class="card__summary"><?= e($p['summary']) ?></p>
              <p class="rating"><?= rating_html($p['rating']['avg'], $p['rating']['count']) ?></p>
              <div class="card__footer">
                <p class="price"><?= money_html((int) $p['price_cents']) ?><?php if ($p['was_cents']): ?> <span class="price--was"><?= money_html((int) $p['was_cents']) ?></span><?php endif; ?></p>
                <span class="stock <?= $stockClass ?>"><?= e($stockText) ?></span>
              </div>
<?php if ((int) $p['stock'] > 0): ?>
              <form method="post" action="cart.php" class="mt-s">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product" value="<?= e($p['id']) ?>">
                <input type="hidden" name="variant" value="<?= e((string) $firstVariant) ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="button button--small"><?= te('Add to cart') ?></button>
              </form>
<?php endif; ?>
            </div>
          </article>
<?php endforeach; ?>
        </div>
<?php endif; ?>

<?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="<?= te('Catalogue pages') ?>">
          <ul class="pagination__list">
<?php for ($i = 1; $i <= $pages; $i++): ?>
            <li><a class="pagination__link" href="catalogue.php<?= e(query_with(['page' => $i])) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= number($i) ?></a></li>
<?php endfor; ?>
<?php if ($page < $pages): ?>
            <li><a class="pagination__link" href="catalogue.php<?= e(query_with(['page' => $page + 1])) ?>"><?= te('Next page') ?></a></li>
<?php endif; ?>
          </ul>
        </nav>
<?php endif; ?>
      </section>

<?php require __DIR__ . '/../shared/footer.php'; ?>
