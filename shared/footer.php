<?php
/** Shared site footer. Closes <main> and the document. */

declare(strict_types=1);

$base = gf_base();
?>
    </div>
  </main>

  <footer class="site-footer">
    <div class="shell site-footer__inner">
      <p class="site-footer__note"><?= te('Grimdark Forge - independent miniatures and hobby supplies, Brunswick, Victoria.') ?></p>

      <nav class="footer-nav" aria-label="<?= te('Footer') ?>">
        <ul class="footer-nav__list">
          <li><a href="<?= $base ?>forum/boards.php"><?= te('Forum') ?></a></li>
          <li><a href="<?= $base ?>blog/posts.php"><?= te('Blog') ?></a></li>
          <li><a href="<?= $base ?>reviews/reviews.php"><?= te('Reviews') ?></a></li>
          <li><a href="<?= $base ?>shop/catalogue.php"><?= te('Shop') ?></a></li>
          <li><a href="<?= $base ?>account.php"><?= te('Account') ?></a></li>
        </ul>
      </nav>
    </div>
  </footer>
</body>
</html>
