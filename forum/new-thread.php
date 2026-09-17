<?php
/** Create a thread with its opening post. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

if (!is_logged_in()) {
    flash('danger', t('Log in to start a thread.'));
    redirect('../account.php?return=' . rawurlencode('forum/new-thread.php'));
}

$rules = [
    'thread-title' => ['label' => 'Thread title', 'required' => true, 'min' => 10, 'max' => 90],
    'thread-board' => ['label' => 'Board', 'required' => true, 'in' => array_keys(GF_BOARDS)],
    'thread-tag'   => ['label' => 'Tag', 'in' => array_keys(GF_THREAD_TAGS)],
    'post-title'   => ['label' => 'Post title', 'required' => true, 'min' => 3, 'max' => 90],
    'thread-body'  => ['label' => 'Post text', 'required' => true, 'min' => 20, 'max' => 5000],
] + image_rules();
$errors = [];
$values = array_fill_keys(array_keys($rules), '');

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('new-thread.php');
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    [$image, $alt, $caption] = resolve_image($values, $errors);

    if (!$errors) {
        $threads = &collection('threads');
        $posts = &collection('posts');
        $threadId = next_id('threads');
        $postId = next_id('posts');
        $now = now_iso();
        $threads[$threadId] = [
            'title'   => $values['thread-title'],
            'board'   => $values['thread-board'],
            'tag'     => $values['thread-tag'],
            'author'  => current_user(),
            'created' => $now,
            'views'   => 0,
        ];
        $posts[$postId] = [
            'thread_id'   => $threadId,
            'parent_id'   => null,
            'author'      => current_user(),
            'created'     => $now,
            'edited'      => null,
            'edit_reason' => '',
            'deleted'     => false,
            'title'       => $values['post-title'],
            'body'        => $values['thread-body'],
            'image'       => $image,
            'image_alt'   => $alt,
            'caption'     => $caption,
        ];
        unset($threads, $posts);
        flash('success', t('Your thread has been posted.'));
        redirect('thread.php?id=' . $threadId);
    }
}

$page_title = 'Start a thread';
$page_description = 'Start a new discussion thread on the Grimdark Forge forum.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="boards.php"><?= te('Forum') ?></a></li>
          <li aria-current="page"><?= te('Start a thread') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Start a thread') ?></h1>
          <p class="page-head__lede"><?= te('Posting as %1$s. Give the thread a title people can search for.', [user_name(current_user())]) ?></p>
        </div>
      </div>

      <div class="layout-aside">
        <div>
          <?= error_summary_html($errors) ?>
          <form class="form" method="post" action="new-thread.php" enctype="multipart/form-data" data-validate>
            <?= csrf_field() ?>
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Thread details') ?></legend>

              <p class="field">
                <label class="field__label" for="thread-title"><?= te('Thread title') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="thread-title" name="thread-title" value="<?= e($values['thread-title']) ?>" data-required data-minlength="10" data-maxlength="90"<?= invalid_attrs($errors, 'thread-title', 'thread-title-hint') ?>>
                <?= error_html($errors, 'thread-title') ?>
                <span class="field__hint" id="thread-title-hint"><?= te('Between 10 and 90 characters. Say what the thread is about, not how you feel about it.') ?></span>
              </p>

              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="thread-board"><?= te('Board') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <select id="thread-board" name="thread-board" data-required<?= invalid_attrs($errors, 'thread-board', 'thread-board-hint') ?>>
                    <option value=""<?= $values['thread-board'] === '' ? ' selected' : '' ?>><?= te('Choose a board') ?></option>
<?php foreach (GF_BOARDS as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $values['thread-board'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= error_html($errors, 'thread-board') ?>
                  <span class="field__hint" id="thread-board-hint"><?= te('Threads in the wrong board get moved, not deleted.') ?></span>
                </p>

                <p class="field">
                  <label class="field__label" for="thread-tag"><?= te('Optional tag') ?></label>
                  <select id="thread-tag" name="thread-tag"<?= invalid_attrs($errors, 'thread-tag') ?>>
                    <option value=""<?= $values['thread-tag'] === '' ? ' selected' : '' ?>><?= te('No tag') ?></option>
<?php foreach (GF_THREAD_TAGS as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $values['thread-tag'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= error_html($errors, 'thread-tag') ?>
                </p>
              </div>
            </fieldset>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Opening post') ?></legend>

              <p class="field">
                <label class="field__label" for="post-title"><?= te('Post title') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="post-title" name="post-title" value="<?= e($values['post-title']) ?>" data-required data-minlength="3" data-maxlength="90"<?= invalid_attrs($errors, 'post-title', 'post-title-hint') ?>>
                <?= error_html($errors, 'post-title') ?>
                <span class="field__hint" id="post-title-hint"><?= te('The heading of your opening post. It can repeat the thread title.') ?></span>
              </p>

              <p class="field">
                <label class="field__label" for="thread-body"><?= te('Post text') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <textarea id="thread-body" name="thread-body" data-required data-minlength="20" data-maxlength="5000"<?= invalid_attrs($errors, 'thread-body', 'thread-body-hint') ?>><?= e($values['thread-body']) ?></textarea>
                <?= error_html($errors, 'thread-body') ?>
                <span class="field__hint" id="thread-body-hint"><?= te('Plain text, at least 20 characters. If you are asking about a rule, quote the exact wording and the page number.') ?></span>
              </p>
            </fieldset>

            <?= image_fields_html($values, $errors, '', 'Attach an image') ?>

            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te('Post thread') ?></button>
              <a class="button button--quiet" href="boards.php"><?= te('Cancel') ?></a>
            </div>
          </form>
        </div>

        <aside class="layout-aside__side" aria-labelledby="guidelines-heading">
          <div class="panel">
            <h2 class="panel__title" id="guidelines-heading"><?= te('Before you post') ?></h2>
            <div class="prose">
              <ul>
                <li><?= te('Search first — rules questions are usually answered already.') ?></li>
                <li><?= te('One question per thread. Two questions get one answer.') ?></li>
                <li><?= te('Photograph models in daylight if you want useful painting feedback.') ?></li>
                <li><?= te('Disagree with the argument, not the person.') ?></li>
              </ul>
            </div>
          </div>

          <div class="callout callout--primary">
            <h2 class="callout__title"><?= te('You can edit this later') ?></h2>
            <p class="text-small"><?= te('Threads you start stay editable from the thread page. You cannot edit or delete posts written by anyone else.') ?></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
