<?php
/** Full review view, with a "helpful" vote (one per session per review). */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$r = review($id ? (int) $id : null);

if (!$r) {
    http_response_code(404);
    $page_title = 'Review not found';
    $page_description = 'The requested review does not exist.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('Review not found') . '</h1><p class="page-head__lede">' . te('That review does not exist or has been deleted.') . '</p></div></div>';
    echo '<p><a class="button" href="reviews.php">' . te('Back to all reviews') . '</a></p>';
    require __DIR__ . '/../shared/footer.php';
    exit;
}

if (is_post() && csrf_ok() && filter_input(INPUT_POST, 'action', FILTER_DEFAULT) === 'helpful') {
    $voted = $_SESSION['helpful'] ?? [];
    if (!in_array($r['id'], $voted, true)) {
        $reviews = &collection('reviews');
        $reviews[$r['id']]['helpful'] = (int) $reviews[$r['id']]['helpful'] + 1;
        unset($reviews);
        $voted[] = $r['id'];
        $_SESSION['helpful'] = $voted;
        flash('success', t('Thanks — your vote has been counted.'));
    }
    redirect('review.php?id=' . $r['id']);
}

$p = product($r['product_id']);
$mine = owns($r['author']);
$voted = in_array($r['id'], $_SESSION['helpful'] ?? [], true);
$rating = $p ? product_rating($r['product_id']) : ['avg' => 0, 'count' => 0];
$others = array_filter(reviews_query(['product' => $r['product_id'], 'q' => '', 'text' => '', 'rating' => '', 'reviewer' => '', 'from' => '', 'to' => '', 'sort' => 'date-desc']), function ($o) use ($r) {
    return $o['id'] !== $r['id'];
});

$page_title = $r['title'];
$page_description = excerpt($r['body'], 150);
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="reviews.php"><?= te('Reviews') ?></a></li>
          <li aria-current="page"><?= e(excerpt($r['title'], 40)) ?></li>
        </ol>
      </nav>

      <div class="layout-aside">
        <article>
          <div class="page-head">
            <div class="page-head__text">
              <h1><?= e($r['title']) ?></h1>
              <p class="page-head__lede"><?= te('A review of') ?> <a href="../shop/product.php?id=<?= e($r['product_id']) ?>"><?= e($p['name'] ?? $r['product_id']) ?></a>.</p>
            </div>
<?php if ($mine): ?>
            <div class="page-head__actions">
              <a class="button" href="edit-review.php?id=<?= $r['id'] ?>"><?= te('Edit') ?></a>
              <a class="button button--danger" href="delete-review.php?id=<?= $r['id'] ?>"><?= te('Delete') ?></a>
            </div>
<?php endif; ?>
          </div>

          <p class="rating">
            <?= rating_html((float) $r['rating']) ?>
<?php if (!empty($r['bought'])): ?>
            <span class="badge badge--success"><?= te('Verified purchase') ?></span>
<?php endif; ?>
<?php if ($mine): ?>
            <span class="badge badge--primary"><?= te('Your review') ?></span>
<?php endif; ?>
          </p>

          <p class="byline mt-s">
            <img class="byline__avatar" src="../<?= e(user_avatar($r['author'])) ?>" alt="">
            <span class="byline__name"><?= e(user_name($r['author'])) ?></span>
            <span><?= te('Written') ?> <?= time_html($r['date']) ?></span>
<?php if (!empty($r['bought'])): ?>
            <span><?= te('Bought') ?> <?= time_html($r['bought']) ?></span>
<?php endif; ?>
<?php if (!empty($r['updated'])): ?>
            <span><?= te('Updated') ?> <?= time_html($r['updated']) ?></span>
<?php endif; ?>
          </p>

          <div class="prose mt-l"><?= paragraphs($r['body']) ?></div>

<?php if ($r['image'] !== ''): ?>
          <figure class="post__figure mt-m">
            <img class="post__image" src="<?= e(image_src($r['image'])) ?>" alt="<?= e($r['image_alt']) ?>">
<?php if ($r['caption'] !== ''): ?>
            <figcaption><?= e($r['caption']) ?></figcaption>
<?php endif; ?>
          </figure>
<?php endif; ?>

          <div class="post__actions mt-l">
            <form method="post" action="review.php?id=<?= $r['id'] ?>" class="logout-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="helpful">
              <button type="submit" class="button"<?= $voted ? ' disabled' : '' ?>><?= te('Helpful (%1$s)', [number((int) $r['helpful'])]) ?></button>
            </form>
            <span class="ownership-note"><?= $mine ? te('Written by you') : te('Written by %1$s', [user_name($r['author'])]) ?></span>
          </div>

<?php if (!$mine): ?>
          <div class="callout mt-m">
            <h2 class="callout__title"><?= te('This review is not yours') ?></h2>
            <p class="text-small"><?= is_logged_in()
                ? te('You are logged in as %1$s, so you can mark this review helpful but you cannot edit or delete it. Your own reviews carry Edit and Delete controls instead.', [user_name(current_user())])
                : te('Log in to write reviews of your own. Only the account that wrote a review can edit or delete it.') ?></p>
          </div>
<?php endif; ?>
        </article>

        <aside class="layout-aside__side" aria-labelledby="product-heading">
<?php if ($p): [$sc, $st] = stock_status($p); ?>
          <div class="panel">
            <h2 class="panel__title" id="product-heading"><?= te('The product') ?></h2>
            <img class="card__media" src="<?= e(image_src($p['image'])) ?>" alt="<?= e($p['alt']) ?>">
            <h3 class="card__title mt-s"><a href="../shop/product.php?id=<?= e($r['product_id']) ?>"><?= e($p['name']) ?></a></h3>
            <p class="rating"><?= rating_html($rating['avg'], $rating['count']) ?></p>
            <p class="price mt-s"><?= money_html((int) $p['price_cents']) ?><?php if ($p['was_cents']): ?> <span class="price--was"><?= money_html((int) $p['was_cents']) ?></span><?php endif; ?></p>
            <p class="stock <?= $sc ?>"><?= e($st) ?></p>
            <p class="mt-m"><a class="button button--primary button--block" href="../shop/product.php?id=<?= e($r['product_id']) ?>"><?= te('View product') ?></a></p>
          </div>
<?php endif; ?>

<?php if ($others): ?>
          <div class="panel">
            <h2 class="panel__title"><?= te('Other reviews of this product') ?></h2>
            <ul class="footer-nav__list">
<?php foreach (array_slice($others, 0, 3) as $o): ?>
              <li><a href="review.php?id=<?= $o['id'] ?>"><?= e($o['title']) ?> — <?= te('%1$s stars', [number((int) $o['rating'])]) ?></a></li>
<?php endforeach; ?>
            </ul>
          </div>
<?php endif; ?>

          <div class="panel">
            <h2 class="panel__title"><?= te('Have you bought this?') ?></h2>
            <p class="text-small text-muted"><?= te('Tell other customers what you thought.') ?></p>
            <p class="mt-s"><a class="button button--block" href="new-review.php?product=<?= e($r['product_id']) ?>"><?= te('Write a review') ?></a></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
