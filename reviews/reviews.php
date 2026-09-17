<?php
/** Reviews index: retrieve, search and filter reviews by every main component. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$sorts = [
    'date-desc'    => 'Newest first',
    'date-asc'     => 'Oldest first',
    'rating-desc'  => 'Highest rated first',
    'rating-asc'   => 'Lowest rated first',
    'helpful-desc' => 'Most helpful first',
    'title-asc'    => 'Title, A to Z',
];

$f = [
    'product'  => trim((string) (filter_input(INPUT_GET, 'product', FILTER_DEFAULT) ?? '')),
    'q'        => trim((string) (filter_input(INPUT_GET, 'q', FILTER_DEFAULT) ?? '')),
    'text'     => trim((string) (filter_input(INPUT_GET, 'text', FILTER_DEFAULT) ?? '')),
    'rating'   => (string) (filter_input(INPUT_GET, 'rating', FILTER_DEFAULT) ?? ''),
    'reviewer' => (string) (filter_input(INPUT_GET, 'reviewer', FILTER_DEFAULT) ?? ''),
    'from'     => trim((string) (filter_input(INPUT_GET, 'from', FILTER_DEFAULT) ?? '')),
    'to'       => trim((string) (filter_input(INPUT_GET, 'to', FILTER_DEFAULT) ?? '')),
    'sort'     => (string) (filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'date-desc'),
];
if (!isset($sorts[$f['sort']])) {
    $f['sort'] = 'date-desc';
}
if (!in_array($f['rating'], ['', '1', '2', '3', '4', '5'], true)) {
    $f['rating'] = '';
}
if ($f['reviewer'] !== '' && !user($f['reviewer'])) {
    $f['reviewer'] = '';
}
foreach (['from', 'to'] as $k) {
    if ($f[$k] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f[$k])) {
        $f[$k] = '';
    }
}
$page = (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);

$all = reviews_query($f);
[$reviews, $page, $pages, $total] = paginate($all, $page, 6);
$breakdown = reviews_breakdown();

$page_title = 'Reviews and ratings';
$page_description = 'Star ratings and long-form reviews of Grimdark Forge products, written by customers.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li aria-current="page"><?= te('Reviews') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Reviews and ratings') ?></h1>
          <p class="page-head__lede"><?= te('Written by customers, not by us. %1$s reviews across %2$s products.', [number($breakdown['count']), number(count(collection('products')))]) ?></p>
        </div>
        <div class="page-head__actions">
<?php if (is_logged_in()): ?>
          <a class="button button--primary" href="new-review.php"><?= te('Write a review') ?></a>
<?php else: ?>
          <a class="button button--primary" href="../account.php?return=<?= e(rawurlencode('reviews/new-review.php')) ?>"><?= te('Log in to write a review') ?></a>
<?php endif; ?>
        </div>
      </div>

      <div class="layout-aside layout-aside--side-first">
        <aside class="layout-aside__side" aria-labelledby="breakdown-heading">
          <div class="panel">
            <h2 class="panel__title" id="breakdown-heading"><?= te('Overall rating') ?></h2>
            <p class="rating"><?= rating_html($breakdown['avg'], $breakdown['count']) ?></p>

            <dl class="rating-bars mt-m">
<?php foreach ($breakdown['counts'] as $stars => $n): ?>
              <div class="rating-bars__row">
                <dt><?= $stars === 1 ? te('1 star') : te('%1$s stars', [$stars]) ?></dt>
                <dd class="rating-bars__track"><span class="rating-bars__fill <?= pct_class($n, $breakdown['count']) ?>"></span></dd>
                <dd><?= number($n) ?></dd>
              </div>
<?php endforeach; ?>
            </dl>
          </div>

          <div class="callout callout--primary">
            <h2 class="callout__title"><?= te('Your reviews are yours') ?></h2>
            <p class="text-small"><?= te('Log in with any username to write a review. Only the account that wrote a review can edit or delete it.') ?></p>
          </div>
        </aside>

        <div>
          <section class="filter-bar" aria-labelledby="filter-heading">
            <h2 class="visually-hidden" id="filter-heading"><?= te('Filter reviews') ?></h2>
            <form class="filter-bar__form" action="reviews.php" method="get" data-validate>
              <div class="filter-bar__grid">
                <p class="field">
                  <label class="field__label" for="filter-product"><?= te('Product title contains') ?></label>
                  <input type="search" id="filter-product" name="product" value="<?= e($f['product']) ?>" placeholder="<?= te('e.g. oxide') ?>" aria-describedby="filter-product-hint">
                  <span class="field__hint" id="filter-product-hint"><?= te('Matches the product the review is about.') ?></span>
                </p>

                <p class="field">
                  <label class="field__label" for="filter-q"><?= te('Review title contains') ?></label>
                  <input type="search" id="filter-q" name="q" value="<?= e($f['q']) ?>" placeholder="<?= te('e.g. holding a point') ?>">
                </p>

                <p class="field">
                  <label class="field__label" for="filter-text"><?= te('Review text contains') ?></label>
                  <input type="search" id="filter-text" name="text" value="<?= e($f['text']) ?>" placeholder="<?= te('e.g. drying time') ?>" aria-describedby="filter-text-hint">
                  <span class="field__hint" id="filter-text-hint"><?= te('Searches the description and photograph descriptions.') ?></span>
                </p>

                <p class="field">
                  <label class="field__label" for="filter-rating"><?= te('Minimum rating') ?></label>
                  <select id="filter-rating" name="rating">
                    <option value=""<?= $f['rating'] === '' ? ' selected' : '' ?>><?= te('Any rating') ?></option>
                    <option value="5"<?= $f['rating'] === '5' ? ' selected' : '' ?>><?= te('5 stars only') ?></option>
<?php foreach ([4, 3, 2] as $n): ?>
                    <option value="<?= $n ?>"<?= $f['rating'] === (string) $n ? ' selected' : '' ?>><?= te('%1$s stars and above', [$n]) ?></option>
<?php endforeach; ?>
                  </select>
                </p>

                <p class="field">
                  <label class="field__label" for="filter-reviewer"><?= te('Reviewer') ?></label>
                  <select id="filter-reviewer" name="reviewer">
                    <option value=""<?= $f['reviewer'] === '' ? ' selected' : '' ?>><?= te('Anyone') ?></option>
<?php foreach (collection('users') as $username => $u): ?>
                    <option value="<?= e($username) ?>"<?= $f['reviewer'] === $username ? ' selected' : '' ?>><?= e($u['name']) ?></option>
<?php endforeach; ?>
                  </select>
                </p>

                <p class="field">
                  <label class="field__label" for="filter-from"><?= te('Written from') ?></label>
                  <input type="date" id="filter-from" name="from" value="<?= e($f['from']) ?>" data-type="date">
                  <?= error_html([], 'from') ?>
                </p>

                <p class="field">
                  <label class="field__label" for="filter-to"><?= te('Written to') ?></label>
                  <input type="date" id="filter-to" name="to" value="<?= e($f['to']) ?>" data-type="date">
                  <?= error_html([], 'to') ?>
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
                  <a class="button button--quiet" href="reviews.php"><?= te('Reset') ?></a>
                </div>
              </div>
            </form>
          </section>

          <div class="result-summary">
            <p><?= te('Showing %1$s of %2$s reviews, %3$s.', [number(count($reviews)), number($total), mb_strtolower(t($sorts[$f['sort']]))]) ?></p>
            <p><?= te('Page %1$s of %2$s', [number($page), number($pages)]) ?></p>
          </div>

          <section aria-labelledby="list-heading">
            <h2 class="visually-hidden" id="list-heading"><?= te('Reviews') ?></h2>

<?php if (!$reviews): ?>
            <div class="callout">
              <h3 class="callout__title"><?= te('No reviews match') ?></h3>
              <p><?= te('Try fewer filters, or write the first review yourself.') ?></p>
            </div>
<?php else: ?>
            <ul class="comment-list">
<?php foreach ($reviews as $r): $mine = owns($r['author']); ?>
              <li>
                <article class="comment<?= $mine ? ' post--mine' : '' ?>">
                  <p class="rating"><?= rating_html((float) $r['rating']) ?></p>
                  <h3 class="card__title"><a href="review.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></h3>
                  <p class="card__meta">
                    <a href="../shop/product.php?id=<?= e($r['product_id']) ?>"><?= e($r['product_name']) ?></a>
<?php if (!empty($r['bought'])): ?>
                    <span class="badge badge--success"><?= te('Verified purchase') ?></span>
<?php endif; ?>
<?php if ($mine): ?>
                    <span class="badge badge--primary"><?= te('Your review') ?></span>
<?php endif; ?>
                  </p>
                  <p class="byline">
                    <img class="byline__avatar" src="../<?= e(user_avatar($r['author'])) ?>" alt="">
                    <span class="byline__name"><?= e(user_name($r['author'])) ?></span>
                    <?= time_html($r['date']) ?>
                  </p>
<?php if ($r['image'] !== ''): ?>
                  <img class="thread-row__thumb" src="<?= e(image_src($r['image'])) ?>" alt="<?= e($r['image_alt']) ?>">
<?php endif; ?>
                  <div class="prose"><p><?= e(excerpt($r['body'])) ?></p></div>
                  <div class="post__actions">
                    <a href="review.php?id=<?= $r['id'] ?>"><?= te('Read the full review') ?></a>
<?php if ($mine): ?>
                    <a class="button button--small" href="edit-review.php?id=<?= $r['id'] ?>"><?= te('Edit') ?></a>
                    <a class="button button--small button--danger" href="delete-review.php?id=<?= $r['id'] ?>"><?= te('Delete') ?></a>
                    <span class="ownership-note"><?= te('By you') ?></span>
<?php else: ?>
                    <span class="ownership-note"><?= te('By %1$s', [user_name($r['author'])]) ?></span>
<?php endif; ?>
                  </div>
                </article>
              </li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>

<?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="<?= te('Review pages') ?>">
              <ul class="pagination__list">
<?php for ($i = 1; $i <= $pages; $i++): ?>
                <li><a class="pagination__link" href="reviews.php<?= e(query_with(['page' => $i])) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= number($i) ?></a></li>
<?php endfor; ?>
<?php if ($page < $pages): ?>
                <li><a class="pagination__link" href="reviews.php<?= e(query_with(['page' => $page + 1])) ?>"><?= te('Next page') ?></a></li>
<?php endif; ?>
              </ul>
            </nav>
<?php endif; ?>
          </section>
        </div>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
