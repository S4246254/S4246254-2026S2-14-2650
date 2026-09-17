<?php
/** About page. The "at a glance" figures are live counts from the store. */

declare(strict_types=1);

require_once __DIR__ . '/shared/bootstrap.php';

$product_count = count(collection('products'));
$thread_count = count(threads_query(['title' => '', 'text' => '', 'author' => '', 'board' => '', 'sort' => 'activity-desc']));

$page_title = 'About Grimdark Forge';
$page_description = 'Grimdark Forge is an independent miniature wargaming store in Brunswick, Victoria, with a forum, blog and customer reviews.';
require __DIR__ . '/shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="welcome.php"><?= te('Home') ?></a></li>
          <li aria-current="page"><?= te('About') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('About Grimdark Forge') ?></h1>
          <p class="page-head__lede"><?= te('An independent hobby store for miniature wargamers, run out of a shopfront in Brunswick since 2019.') ?></p>
        </div>
      </div>

      <div class="layout-aside">
        <div class="prose">
          <h2><?= te('What we sell') ?></h2>
          <p><?= te('Grimdark Forge stocks the things you need between buying a box and putting a finished model on the table: plastic and resin kits, acrylic paints and shades, natural-hair brushes, pre-textured terrain, and the rulebooks that make sense of all of it.') ?></p>
          <p><?= te('The range is deliberately narrow. We would rather carry three brushes that hold a point than thirty that do not, and everything on the shelf has been used by someone who works here.') ?></p>

          <h2><?= te('The community') ?></h2>
          <p><?= te('The shop is only half of the site. The forum is where people argue about points costs and post work-in-progress photographs, the blog is where staff write up painting techniques in more detail than a product page allows, and the reviews are written by customers rather than by us.') ?></p>
          <p><?= te('You need to be logged in to post, and you can only edit or delete your own contributions. The server checks ownership on every edit and delete request.') ?></p>

          <h2><?= te('Languages') ?></h2>
          <p><?= te('The site can be read in English (Australia) or German. Use the language control in the header: it changes every label, button and heading, and dates and prices are re-formatted for the chosen locale.') ?></p>

          <h2><?= te('Credits') ?></h2>
          <p><?= te('The product illustrations across the site are hand-drawn SVG. The one photograph, on the landing page, is by Robert Coelho and comes from Unsplash, used under the Unsplash Licence.') ?></p>

          <h2><?= te('Accessibility') ?></h2>
          <p><?= te('Every page uses one level-one heading and an unbroken heading order, landmark elements rather than styled containers, a visible focus indicator that is never removed, a skip link, and a text label bound to every form control. Colour is never the only way information is conveyed. Images that carry meaning have descriptive alternative text; decorative images have empty alternative text so screen readers skip them.') ?></p>
        </div>

        <aside class="layout-aside__side" aria-labelledby="facts-heading">
          <div class="panel">
            <h2 class="panel__title" id="facts-heading"><?= te('At a glance') ?></h2>
            <dl class="spec-list">
              <dt><?= te('Founded') ?></dt>
              <dd>2019</dd>

              <dt><?= te('Location') ?></dt>
              <dd><?= te('Brunswick, Victoria') ?></dd>

              <dt><?= te('Range') ?></dt>
              <dd><?= te('%1$s products', [number($product_count)]) ?></dd>

              <dt><?= te('Community') ?></dt>
              <dd><?= te('%1$s forum threads', [number($thread_count)]) ?></dd>
            </dl>
          </div>

          <div class="callout callout--primary">
            <h2 class="callout__title"><?= te('Checkout is a simulation') ?></h2>
            <p class="text-small"><?= te('The checkout page accepts sample values only and takes no payment. Please do not enter real card details.') ?></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/shared/footer.php'; ?>
