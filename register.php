<?php
/**
 * Create an account: username, display name and email address. No password,
 * as the brief specifies. On success the new account is logged in straight
 * away and any guest cart is carried across.
 */

declare(strict_types=1);

require_once __DIR__ . '/shared/bootstrap.php';

if (is_logged_in()) {
    redirect('account.php');
}

$errors = [];
$return = safe_return(filter_input(INPUT_GET, 'return', FILTER_DEFAULT) ?: null, 'account.php');

$rules = [
    'username'     => ['label' => 'Username', 'required' => true, 'type' => 'username'],
    'display-name' => ['label' => 'Display name', 'required' => true, 'min' => 2, 'max' => 40],
    'email'        => ['label' => 'Email address', 'required' => true, 'type' => 'email', 'max' => 120],
];
$values = form_values($rules);

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('register.php');
    }
    $return = safe_return(filter_input(INPUT_POST, 'return', FILTER_DEFAULT) ?: null, 'account.php');
    $errors = validate($rules, $values);
    $username = strtolower($values['username']);
    if (!isset($errors['username']) && user($username)) {
        $errors['username'] = t('That username is already taken. Choose another, or log in if it is yours.');
    }
    if (!$errors) {
        create_account($username, $values['display-name'], $values['email']);
        flash('success', t('Welcome, %1$s. Your account has been created and you are now logged in.', [$values['display-name']]));
        redirect($return);
    }
}

$page_title = 'Create an account';
$page_description = 'Create a Grimdark Forge account with a username, display name and email address. No password is needed.';
require __DIR__ . '/shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="welcome.php"><?= te('Home') ?></a></li>
          <li><a href="account.php"><?= te('Your account') ?></a></li>
          <li aria-current="page"><?= te('Create an account') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Create an account') ?></h1>
          <p class="page-head__lede"><?= te('Pick a username and tell us what to call you. No password is needed: this is a prototype, and the username is only used to show which content is yours.') ?></p>
        </div>
      </div>

      <div class="layout-aside">
        <div>
          <?= error_summary_html($errors) ?>
          <form class="form login-panel" method="post" action="register.php" data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="<?= e($return) ?>">
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Your details') ?></legend>
              <p class="field">
                <label class="field__label" for="username"><?= te('Username') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="username" name="username" value="<?= e($values['username']) ?>" autocomplete="username" autocapitalize="none" data-required data-type="username"<?= invalid_attrs($errors, 'username', 'username-hint') ?>>
                <?= error_html($errors, 'username') ?>
                <span class="field__hint" id="username-hint"><?= te('3 to 20 letters, digits or underscores, starting with a letter. This is what you log in with.') ?></span>
              </p>

              <p class="field">
                <label class="field__label" for="display-name"><?= te('Display name') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="display-name" name="display-name" value="<?= e($values['display-name']) ?>" autocomplete="nickname" data-required data-minlength="2" data-maxlength="40"<?= invalid_attrs($errors, 'display-name', 'display-name-hint') ?>>
                <?= error_html($errors, 'display-name') ?>
                <span class="field__hint" id="display-name-hint"><?= te('Shown beside every post, review and comment you write.') ?></span>
              </p>

              <p class="field">
                <label class="field__label" for="email"><?= te('Email address') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" inputmode="email" id="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" data-required data-type="email" data-maxlength="120"<?= invalid_attrs($errors, 'email', 'email-hint') ?>>
                <?= error_html($errors, 'email') ?>
                <span class="field__hint" id="email-hint"><?= te('Used for order confirmations only. Never shown publicly.') ?></span>
              </p>
            </fieldset>
            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te('Create account') ?></button>
              <a class="button button--quiet" href="account.php?return=<?= rawurlencode($return) ?>"><?= te('Cancel') ?></a>
            </div>
            <p class="text-muted"><?= t('Already have an account? <a href="account.php?return=%1$s">Log in</a>.', [rawurlencode($return)]) ?></p>
          </form>
        </div>

        <aside class="layout-aside__side" aria-labelledby="why-account-heading">
          <div class="panel">
            <h2 class="panel__title" id="why-account-heading"><?= te('What an account gives you') ?></h2>
            <ul>
              <li><?= te('Start threads and reply in the forum.') ?></li>
              <li><?= te('Write blog posts and product reviews.') ?></li>
              <li><?= te('Edit or delete anything you have written.') ?></li>
              <li><?= te('Keep your cart between visits.') ?></li>
            </ul>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/shared/footer.php'; ?>
