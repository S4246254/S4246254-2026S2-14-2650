<?php
/**
 * Account page: log in (username only, as the brief specifies), log out,
 * switch between the demonstration accounts, edit the profile, and reset
 * the in-memory demo data.
 */

declare(strict_types=1);

require_once __DIR__ . '/shared/bootstrap.php';

$errors = [];
$values = ['username' => ''];
$return = safe_return(filter_input(INPUT_GET, 'return', FILTER_DEFAULT) ?: null, 'account.php');

$login_rules = [
    'username' => ['label' => 'Username', 'required' => true, 'type' => 'username'],
];
$profile_rules = [
    'display-name' => ['label' => 'Display name', 'required' => true, 'min' => 2, 'max' => 40],
    'email'        => ['label' => 'Email address', 'required' => true, 'type' => 'email', 'max' => 120],
    'bio'          => ['label' => 'About you', 'max' => 300],
];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('account.php');
    }
    $action = filter_input(INPUT_POST, 'action', FILTER_DEFAULT) ?: '';
    $return = safe_return(filter_input(INPUT_POST, 'return', FILTER_DEFAULT) ?: null, 'account.php');

    if ($action === 'logout') {
        $name = user_name(current_user());
        logout();
        flash('success', t('You have been logged out. See you next time, %1$s.', [$name]));
        redirect($return);
    }

    if ($action === 'login') {
        $values = form_values($login_rules);
        $errors = validate($login_rules, $values);
        if (!$errors) {
            login(strtolower($values['username']));
            flash('success', t('You are now logged in as %1$s.', [user_name(current_user())]));
            redirect($return);
        }
    }

    if ($action === 'reset') {
        reset_demo_data();
        flash('success', t('The demonstration data has been reset to its starting state.'));
        redirect('account.php');
    }

    if ($action === 'profile' && is_logged_in()) {
        $pv = form_values($profile_rules);
        $errors = validate($profile_rules, $pv);
        if (!$errors) {
            $users = &collection('users');
            $u = &$users[current_user()];
            $u['name']  = $pv['display-name'];
            $u['email'] = $pv['email'];
            $u['bio']   = $pv['bio'];
            unset($u, $users);
            flash('success', t('Your profile has been saved.'));
            redirect('account.php');
        }
        $profile = $pv;
    }
}

$me = user(current_user());
if (!isset($profile)) {
    $profile = [
        'display-name' => $me['name'] ?? '',
        'email'        => $me['email'] ?? '',
        'bio'          => $me['bio'] ?? '',
    ];
}

$page_title = 'Your account';
$page_description = 'Log in to Grimdark Forge with a username, switch between the demonstration accounts, and update your profile.';
require __DIR__ . '/shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="welcome.php"><?= te('Home') ?></a></li>
          <li aria-current="page"><?= te('Your account') ?></li>
        </ol>
      </nav>

<?php if (!is_logged_in()): ?>
      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Log in') ?></h1>
          <p class="page-head__lede"><?= te('Enter a username to log in. No password is needed: this is a prototype, and the username is only used to show which content is yours.') ?></p>
        </div>
      </div>

      <div class="layout-aside">
        <div>
          <?= error_summary_html($errors) ?>
          <form class="form login-panel" method="post" action="account.php" data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="return" value="<?= e($return) ?>">
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Who are you?') ?></legend>
              <p class="field">
                <label class="field__label" for="username"><?= te('Username') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="username" name="username" value="<?= e($values['username']) ?>" autocomplete="username" autocapitalize="none" data-required data-type="username"<?= invalid_attrs($errors, 'username', 'username-hint') ?>>
                <?= error_html($errors, 'username') ?>
                <span class="field__hint" id="username-hint"><?= te('Try kaya, toma, renn or oksana to see existing content as its owner, or any new name to start fresh.') ?></span>
              </p>
            </fieldset>
            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te('Log in') ?></button>
              <a class="button button--quiet" href="welcome.php"><?= te('Cancel') ?></a>
            </div>
          </form>
        </div>

        <aside class="layout-aside__side" aria-labelledby="test-accounts-heading">
          <div class="panel">
            <h2 class="panel__title" id="test-accounts-heading"><?= te('Test accounts') ?></h2>
            <ul class="footer-nav__list">
<?php foreach (collection('users') as $username => $u): ?>
              <li>
                <form method="post" action="account.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="login">
                  <input type="hidden" name="username" value="<?= e($username) ?>">
                  <input type="hidden" name="return" value="<?= e($return) ?>">
                  <button type="submit" class="button button--small"><?= te('Log in as %1$s', [$u['name']]) ?></button>
                  <span class="sitemap-tree__note"><?= te('username') ?>: <code><?= e($username) ?></code></span>
                </form>
              </li>
<?php endforeach; ?>
            </ul>
          </div>
        </aside>
      </div>

<?php else: ?>
      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Your account') ?></h1>
          <p class="page-head__lede"><?= te('You are logged in as %1$s. Choose a different account to see how the site changes for another user.', [$me['name']]) ?></p>
        </div>
        <div class="page-head__actions">
          <form method="post" action="account.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="return" value="account.php">
            <button type="submit" class="button"><?= te('Log out') ?></button>
          </form>
        </div>
      </div>

      <div class="callout callout--primary">
        <h2 class="callout__title"><?= te('What you can edit') ?></h2>
        <p><?= te('You can only change your own contributions. Posts, reviews, comments and cart lines that belong to the account you are logged in as carry Edit and Delete controls; everyone else\'s content does not, and the server refuses any edit or delete request for content you do not own.') ?></p>
      </div>

      <section class="section" aria-labelledby="switch-heading">
        <div class="section__head">
          <h2 id="switch-heading"><?= te('Switch account') ?></h2>
        </div>

        <ul class="account-grid">
<?php foreach (collection('users') as $username => $u):
    $active = $username === current_user();
    $stats = gf_user_stats($username); ?>
          <li>
            <article class="account-card<?= $active ? ' account-card--active' : '' ?>">
              <img class="account-card__avatar" src="<?= e($u['avatar']) ?>" alt="">
              <h3 class="account-card__name"><?= e($u['name']) ?></h3>
              <p class="text-muted text-small"><?= te('Joined') ?> <?= time_html($u['joined']) ?></p>
<?php if ($u['bio'] !== ''): ?>
              <p class="text-small"><?= e($u['bio']) ?></p>
<?php endif; ?>
<?php if ($active): ?>
              <p><span class="badge badge--primary"><?= te('Currently logged in') ?></span></p>
<?php else: ?>
              <form method="post" action="account.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="username" value="<?= e($username) ?>">
                <input type="hidden" name="return" value="account.php">
                <p><button type="submit" class="button button--small"><?= te('Log in as %1$s', [explode(' ', $u['name'])[0]]) ?></button></p>
              </form>
<?php endif; ?>
              <p class="text-muted text-small"><?= te('%1$s forum posts · %2$s blog posts · %3$s reviews', [number($stats['posts']), number($stats['blog']), number($stats['reviews'])]) ?></p>
            </article>
          </li>
<?php endforeach; ?>
        </ul>
      </section>

      <section class="section" aria-labelledby="details-heading">
        <div class="section__head">
          <h2 id="details-heading"><?= te('Account details') ?></h2>
        </div>

        <div class="layout-aside">
          <div>
            <?= error_summary_html($errors) ?>
            <form class="form" method="post" action="account.php" data-validate>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="profile">
              <fieldset class="fieldset">
                <legend class="fieldset__legend"><?= te('Profile') ?></legend>

                <div class="form__grid form__grid--two">
                  <p class="field">
                    <label class="field__label" for="display-name"><?= te('Display name') ?> <span class="field__required" aria-hidden="true">*</span></label>
                    <input type="text" id="display-name" name="display-name" value="<?= e($profile['display-name']) ?>" autocomplete="nickname" data-required data-minlength="2" data-maxlength="40"<?= invalid_attrs($errors, 'display-name', 'display-name-hint') ?>>
                    <?= error_html($errors, 'display-name') ?>
                    <span class="field__hint" id="display-name-hint"><?= te('Shown beside every post, review and comment you write.') ?></span>
                  </p>

                  <p class="field">
                    <label class="field__label" for="email"><?= te('Email address') ?> <span class="field__required" aria-hidden="true">*</span></label>
                    <input type="text" inputmode="email" id="email" name="email" value="<?= e($profile['email']) ?>" autocomplete="email" data-required data-type="email" data-maxlength="120"<?= invalid_attrs($errors, 'email', 'email-hint') ?>>
                    <?= error_html($errors, 'email') ?>
                    <span class="field__hint" id="email-hint"><?= te('Used for order confirmations only. Never shown publicly.') ?></span>
                  </p>
                </div>

                <p class="field">
                  <label class="field__label" for="bio"><?= te('About you') ?></label>
                  <textarea id="bio" name="bio" data-maxlength="300"<?= invalid_attrs($errors, 'bio', 'bio-hint') ?>><?= e($profile['bio']) ?></textarea>
                  <?= error_html($errors, 'bio') ?>
                  <span class="field__hint" id="bio-hint"><?= te('Appears on your account card. Up to 300 characters.') ?></span>
                </p>
              </fieldset>

              <div class="form__actions">
                <button type="submit" class="button button--primary"><?= te('Save changes') ?></button>
                <a class="button button--quiet" href="welcome.php"><?= te('Cancel') ?></a>
              </div>
            </form>
          </div>

          <aside class="layout-aside__side" aria-labelledby="activity-heading">
            <div class="panel">
              <h3 class="panel__title" id="activity-heading"><?= te('Your recent activity') ?></h3>
<?php $activity = gf_user_activity(current_user());
if (!$activity): ?>
              <p class="text-muted text-small"><?= te('Nothing yet. Start a thread, write a post or review something you bought.') ?></p>
<?php else: ?>
              <ul class="footer-nav__list">
<?php foreach ($activity as $a): ?>
                <li>
                  <a href="<?= e($a['href']) ?>"><?= e($a['text']) ?></a>
                  <span class="sitemap-tree__note"><?= time_html($a['when']) ?></span>
                </li>
<?php endforeach; ?>
              </ul>
<?php endif; ?>
            </div>

            <div class="panel">
              <h3 class="panel__title"><?= te('Open cart') ?></h3>
              <p class="text-muted text-small"><?= te('%1$s items, saved against your account.', [number(cart_count())]) ?></p>
              <p class="price"><?= money_html(gf_cart_subtotal()) ?></p>
              <p><a class="button button--block" href="shop/cart.php"><?= te('View your cart') ?></a></p>
            </div>

            <div class="callout">
              <h3 class="callout__title"><?= te('Reset the demonstration data') ?></h3>
              <p class="text-small"><?= te('Everything created, edited or deleted during this session is held in memory. Reset it to return every module to its starting content.') ?></p>
              <form method="post" action="account.php" class="mt-s">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset">
                <button type="submit" class="button button--small button--danger"><?= te('Reset demo data') ?></button>
              </form>
            </div>
          </aside>
        </div>
      </section>
<?php endif; ?>

<?php require __DIR__ . '/shared/footer.php'; ?>
