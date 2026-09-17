<?php
/** Landing page. Featured products and the latest community activity come from the in-memory store. */

declare(strict_types=1);

require_once __DIR__ . '/shared/bootstrap.php';

$featured = ['sentinel' => product('sentinel'), 'oxide' => product('oxide'), 'brushes' => product('brushes')];

$latest_threads = threads_query(['title' => '', 'text' => '', 'author' => '', 'board' => '', 'sort' => 'activity-desc']);
$latest_thread = $latest_threads[0] ?? null;
$latest_posts = blog_query(['q' => '', 'text' => '', 'author' => '', 'tag' => '', 'from' => '', 'to' => '', 'sort' => 'date-desc']);
$latest_post = $latest_posts[0] ?? null;
$latest_reviews = reviews_query(['product' => '', 'q' => '', 'text' => '', 'rating' => '', 'reviewer' => '', 'from' => '', 'to' => '', 'sort' => 'date-desc']);
$latest_review = $latest_reviews[0] ?? null;

$page_title = 'Miniature wargaming store and community';
$page_description = 'Grimdark Forge is an independent miniature wargaming store and hobby community: resin and plastic kits, paints, brushes, terrain, rules discussion, painting tutorials and product reviews.';
require __DIR__ . '/shared/header.php';
?>

      <div class="hero">
        <div>
          <h1 class="hero__title"><?= te('Everything for the painting desk and the battlefield') ?></h1>
          <p class="hero__lede"><?= te('Grimdark Forge is an independent hobby store and community for miniature wargamers. Buy kits, paints and terrain, argue about list-building in the forum, follow along with painting tutorials, and read reviews written by people who actually put the paint on.') ?></p>
          <div class="button-row mt-m">
            <a class="button button--primary" href="shop/catalogue.php"><?= te('Browse the catalogue') ?></a>
            <a class="button" href="forum/boards.php"><?= te('Visit the forum') ?></a>
          </div>
        </div>
        <!-- Photograph by Robert Coelho, via Unsplash (Unsplash Licence).
             https://unsplash.com/photos/six-assorted-color-dice-laNNTAth9vs
             Stored locally so the site renders with no network access. -->
        <img class="hero__art" src="assets/img/hero-tabletop.jpg" width="1200" height="900" alt="<?= te('Six-sided dice in red, white and yellow scattered across an illustrated game board, with blue, orange, green and natural wooden playing pieces racked behind them.') ?>">
      </div>

      <section class="section" aria-labelledby="start-heading">
        <div class="section__head">
          <h2 id="start-heading"><?= te('Four places to start') ?></h2>
        </div>

        <div class="grid-cards grid-cards--four">
          <article class="module-card">
            <svg class="module-card__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
              <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 3h12a2 2 0 0 1 2 2v6.5a8 8 0 0 1-8 8 8 8 0 0 1-8-8V5a2 2 0 0 1 2-2z"/>
                <path d="M9 8h6M9 12h4"/>
              </g>
            </svg>
            <h3 class="module-card__title"><a href="shop/catalogue.php"><?= te('Shop') ?></a></h3>
            <p><?= te('Plastic and resin kits, acrylic paints, sable brushes, scenery and rulebooks. Filter by title, colour, scale, price and quantity, then build a cart and walk it through to checkout.') ?></p>
          </article>

          <article class="module-card">
            <svg class="module-card__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
              <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 5.5h13v9H8.5L5 18v-3.5H3z"/>
                <path d="M8 5.5V3.5h13v9h-2V16l-3.5-3.5H16"/>
              </g>
            </svg>
            <h3 class="module-card__title"><a href="forum/boards.php"><?= te('Discussion forum') ?></a></h3>
            <p><?= te('Rules arguments, army lists, work-in-progress threads and terrain builds. Start a thread, reply to one, and edit or delete the posts that belong to you.') ?></p>
          </article>

          <article class="module-card">
            <svg class="module-card__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
              <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 3.5h9l5 5V20a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1z"/>
                <path d="M14 3.5V9h5M8 13h8M8 16.5h5"/>
              </g>
            </svg>
            <h3 class="module-card__title"><a href="blog/posts.php"><?= te('Blog') ?></a></h3>
            <p><?= te('Painting tutorials, basing recipes and event write-ups from the staff and the community. Search by title, author, tag or date.') ?></p>
          </article>

          <article class="module-card">
            <svg class="module-card__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
              <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M12 3.5l2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.9l6.1-.8z"/>
            </svg>
            <h3 class="module-card__title"><a href="reviews/reviews.php"><?= te('Reviews & ratings') ?></a></h3>
            <p><?= te('Star ratings and long-form reviews of the things we sell, written by customers. Filter by product, rating, reviewer or date, and add your own.') ?></p>
          </article>
        </div>
      </section>

      <section class="section" aria-labelledby="featured-heading">
        <div class="section__head">
          <h2 id="featured-heading"><?= te('On the shelf this week') ?></h2>
          <a href="shop/catalogue.php"><?= te('See all products') ?></a>
        </div>

        <div class="grid-cards">
<?php foreach ($featured as $id => $p):
    [$stockClass, $stockText] = stock_status($p); ?>
          <article class="card">
            <img class="card__media" src="<?= e($p['image']) ?>" alt="<?= e($p['alt']) ?>">
            <div class="card__body">
              <h3 class="card__title"><a href="shop/product.php?id=<?= e($id) ?>"><?= e($p['name']) ?></a></h3>
              <p class="card__meta">
<?php foreach ($p['meta'] as $m): ?>
                <span><?= te($m) ?></span>
<?php endforeach; ?>
              </p>
              <p class="card__summary"><?= e($p['summary']) ?></p>
              <div class="card__footer">
                <p class="price"><?= money_html((int) $p['price_cents']) ?></p>
                <span class="stock <?= $stockClass ?>"><?= e($stockText) ?></span>
              </div>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </section>

      <section class="section" aria-labelledby="community-heading">
        <div class="section__head">
          <h2 id="community-heading"><?= te('From the community') ?></h2>
        </div>

        <div class="grid-cards grid-cards--wide">
<?php if ($latest_thread): $op = opening_post($latest_thread['id']); ?>
          <article class="card">
            <div class="card__body">
              <p class="card__meta">
                <span class="badge badge--primary"><?= te('Forum') ?></span>
                <?= time_html($latest_thread['newest']) ?>
              </p>
              <h3 class="card__title"><a href="forum/thread.php?id=<?= $latest_thread['id'] ?>"><?= e($latest_thread['title']) ?></a></h3>
              <p class="card__summary"><?= e(excerpt($op['body'] ?? '', 160)) ?></p>
              <div class="card__footer">
                <span class="text-muted text-small"><?= te('%1$s replies', [number($latest_thread['replies'])]) ?></span>
                <a href="forum/thread.php?id=<?= $latest_thread['id'] ?>"><?= te('Read the thread') ?></a>
              </div>
            </div>
          </article>
<?php endif; ?>

<?php if ($latest_post): ?>
          <article class="card">
            <div class="card__body">
              <p class="card__meta">
                <span class="badge badge--info"><?= te('Blog') ?></span>
                <?= time_html($latest_post['date']) ?>
              </p>
              <h3 class="card__title"><a href="blog/post.php?id=<?= $latest_post['id'] ?>"><?= e($latest_post['title']) ?></a></h3>
              <p class="card__summary"><?= e($latest_post['summary']) ?></p>
              <div class="card__footer">
                <span class="text-muted text-small"><?= te('By %1$s', [user_name($latest_post['author'])]) ?></span>
                <a href="blog/post.php?id=<?= $latest_post['id'] ?>"><?= te('Read the post') ?></a>
              </div>
            </div>
          </article>
<?php endif; ?>

<?php if ($latest_review): ?>
          <article class="card">
            <div class="card__body">
              <p class="card__meta">
                <span class="badge badge--success"><?= te('Review') ?></span>
                <?= time_html($latest_review['date']) ?>
              </p>
              <h3 class="card__title"><a href="reviews/review.php?id=<?= $latest_review['id'] ?>"><?= e($latest_review['title']) ?></a></h3>
              <p class="card__summary"><?= e(excerpt($latest_review['body'], 160)) ?></p>
              <div class="card__footer">
                <p class="rating"><?= rating_html((float) $latest_review['rating']) ?></p>
                <a href="reviews/review.php?id=<?= $latest_review['id'] ?>"><?= te('Read the review') ?></a>
              </div>
            </div>
          </article>
<?php endif; ?>
        </div>
      </section>

<?php require __DIR__ . '/shared/footer.php'; ?>
