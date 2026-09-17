<?php
/** Primary navigation. Pulled into the header on every page. */

declare(strict_types=1);

$nav_items = [
    ['shop/catalogue.php', 'Shop', 'shop'],
    ['forum/boards.php',   'Forum', 'forum'],
    ['blog/posts.php',     'Blog', 'blog'],
    ['reviews/reviews.php', 'Reviews', 'reviews'],
    ['categories.php',     'Categories', 'categories.php'],
    ['about.php',          'About', 'about.php'],
];
?>
      <nav class="primary-nav" aria-label="<?= te('Primary') ?>">
        <ul class="primary-nav__list">
<?php foreach ($nav_items as [$href, $label, $match]):
    $is_current = ($match === $current_module) || ($current_module === '' && $match === $current_script); ?>
          <li><a class="primary-nav__link" href="<?= $base . $href ?>"<?= $is_current ? ' aria-current="page"' : '' ?>><?= te($label) ?></a></li>
<?php endforeach; ?>
        </ul>
      </nav>
