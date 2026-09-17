<?php
/**
 * Shared page head and site header. Included by every page after
 * bootstrap.php, once the page has set:
 *   $page_title       — the <title> text (untranslated English, passed through t())
 *   $page_description — the meta description
 * Optional:
 *   $page_scripts     — array of extra script paths relative to the root
 */

declare(strict_types=1);

$base = gf_base();
$page_title = $page_title ?? 'Grimdark Forge';
$page_description = $page_description ?? '';
$page_scripts = $page_scripts ?? [];
$current_module = gf_module();
$current_script = gf_script_name();
$return_to = (string) ($_SERVER['REQUEST_URI'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t($page_title)) ?> — Grimdark Forge</title>
  <meta name="description" content="<?= e(t($page_description)) ?>">
  <link rel="stylesheet" href="<?= $base ?>assets/css/base.css">
  <link rel="stylesheet" href="<?= $base ?>assets/css/layout.css">
  <link rel="stylesheet" href="<?= $base ?>assets/css/components.css">
  <script src="<?= $base ?>assets/js/site.js" defer></script>
  <script src="<?= $base ?>assets/js/validate.js" defer></script>
<?php foreach ($page_scripts as $script): ?>
  <script src="<?= $base . e($script) ?>" defer></script>
<?php endforeach; ?>
</head>
<body>
  <a class="skip-link" href="#main"><?= te('Skip to main content') ?></a>

  <header class="site-header">
    <div class="shell site-header__bar">
      <a class="wordmark" href="<?= $base ?>welcome.php">Grimdark Forge</a>

<?php require __DIR__ . '/nav.php'; ?>

      <div class="site-header__utility">
        <!-- Locale toggle: a button that expands a list of locales, as agreed
             in the group charter. Each entry is a submit button so the switch
             works without JavaScript; site.js adds the open/close behaviour. -->
        <form class="locale-switch" method="post" action="<?= $base ?>shared/locale.php">
          <?= csrf_field() ?>
          <input type="hidden" name="return" value="<?= e($return_to) ?>">
          <button type="button" class="locale-toggle" id="locale-toggle" aria-expanded="false" aria-controls="locale-menu" aria-label="<?= te('Change the site language. The site is currently shown in %1$s.', [GF_LOCALES[locale()]['short']]) ?>">
            <svg class="locale-toggle__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
              <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/>
                <path d="M3 12h18"/>
                <path d="M12 3c2.4 2.5 3.8 5.6 3.8 9s-1.4 6.5-3.8 9c-2.4-2.5-3.8-5.6-3.8-9s1.4-6.5 3.8-9z"/>
              </g>
            </svg>
            <span class="locale-toggle__name"><?= e(GF_LOCALES[locale()]['short']) ?></span>
          </button>
          <ul class="locale-menu" id="locale-menu" aria-label="<?= te('Site language') ?>">
<?php foreach (GF_LOCALES as $tag => $conf): ?>
            <li>
              <button type="submit" class="locale-menu__option" name="locale" value="<?= e($tag) ?>" lang="<?= e($tag) ?>"<?= $tag === locale() ? ' aria-current="true"' : '' ?>>
                <?= e($conf['name']) ?>
              </button>
            </li>
<?php endforeach; ?>
          </ul>
        </form>

        <?php $count = cart_count(); ?>
        <a class="cart-link" href="<?= $base ?>shop/cart.php" aria-label="<?= te('Cart, %1$s items', [$count]) ?>">
          <svg class="cart-link__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
            <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 4h2.2l2.3 10.5h9.4L19 7H6.2"/>
              <circle cx="9" cy="19" r="1.4"/>
              <circle cx="17" cy="19" r="1.4"/>
            </g>
          </svg>
          <span class="cart-link__count" aria-hidden="true" data-cart-count><?= e(number($count)) ?></span>
        </a>

<?php if (is_logged_in()): ?>
        <a class="account-link" href="<?= $base ?>account.php"<?= $current_script === 'account.php' ? ' aria-current="page"' : '' ?> aria-label="<?= te('Your account. You are signed in as %1$s.', [user_name(current_user())]) ?>">
          <img class="account-link__avatar" src="<?= $base . e(user_avatar(current_user())) ?>" alt="" width="28" height="28">
          <span class="account-link__name"><?= e(user_name(current_user())) ?></span>
        </a>
        <form method="post" action="<?= $base ?>account.php" class="logout-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="logout">
          <input type="hidden" name="return" value="<?= e($return_to) ?>">
          <button type="submit" class="button button--small button--quiet"><?= te('Log out') ?></button>
        </form>
<?php else: ?>
        <a class="button button--small button--primary" href="<?= $base ?>account.php?return=<?= e(rawurlencode($return_to)) ?>"><?= te('Log in') ?></a>
<?php endif; ?>
      </div>
    </div>
  </header>

  <main class="site-main" id="main">
    <div class="shell">
<?php foreach (take_flashes() as $gf_flash): ?>
      <div class="callout callout--<?= e($gf_flash['kind']) ?> flash" role="status">
        <p><?= e($gf_flash['message']) ?></p>
      </div>
<?php endforeach; ?>
