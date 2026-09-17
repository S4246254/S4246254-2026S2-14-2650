<?php
/** Category overview: counts come from the in-memory product list. */

declare(strict_types=1);

require_once __DIR__ . '/shared/bootstrap.php';

$products = collection('products');
$cards = [
    'kits'    => ['summary' => 'Plastic and resin kits from 15 mm skirmish models to 32 mm characters. Supplied unassembled and unpainted.', 'badge' => '15–32 mm'],
    'paints'  => ['summary' => 'Acrylic bases, layers and washes in 30 ml and 60 ml pots, including the full metallic range.', 'badge' => 'Acrylic'],
    'tools'   => ['summary' => 'Kolinsky sable brushes, sprue cutters, files, pin vices and everything else that lives on the desk.', 'badge' => 'Tools'],
    'terrain' => ['summary' => 'Pre-textured resin ruins, modular walls, hills and basing materials for 28 mm tables.', 'badge' => '28 mm'],
    'books'   => ['summary' => 'Core rules, campaign supplements and army lists, including the current third edition with errata folded in.', 'badge' => 'Hardback'],
];

$page_title = 'Categories';
$page_description = 'Browse Grimdark Forge by category: miniature kits, paints and shades, brushes and tools, terrain and scenery, and rulebooks.';
require __DIR__ . '/shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="welcome.php"><?= te('Home') ?></a></li>
          <li aria-current="page"><?= te('Categories') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Categories') ?></h1>
          <p class="page-head__lede"><?= te('%1$s categories across %2$s products. Every category opens in the catalogue, where you can narrow further by colour, scale and price.', [number(count($cards)), number(count($products))]) ?></p>
        </div>
        <div class="page-head__actions">
          <a class="button" href="shop/catalogue.php"><?= te('See everything') ?></a>
        </div>
      </div>

      <section class="section" aria-labelledby="categories-heading">
        <h2 class="visually-hidden" id="categories-heading"><?= te('All categories') ?></h2>

        <div class="grid-cards">
<?php foreach ($cards as $key => $card):
    $in = array_filter($products, function ($p) use ($key) {
        return $p['category'] === $key;
    });
    $first = reset($in); ?>
          <article class="card">
            <img class="card__media" src="<?= e($first['image'] ?? 'assets/img/mark-anvil.svg') ?>" alt="<?= e($first['alt'] ?? '') ?>">
            <div class="card__body">
              <h3 class="card__title"><a href="shop/catalogue.php?category=<?= e($key) ?>"><?= te(GF_CATEGORIES[$key]) ?></a></h3>
              <p class="card__summary"><?= te($card['summary']) ?></p>
              <div class="card__footer">
                <span class="text-muted text-small"><?= te('%1$s products', [number(count($in))]) ?></span>
                <span class="badge"><?= te($card['badge']) ?></span>
              </div>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </section>

<?php require __DIR__ . '/shared/footer.php'; ?>
