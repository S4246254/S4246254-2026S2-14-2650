<?php
/** Update a post (and, for an opening post, its thread). Only the author may do this. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/render.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$post = forum_post($id ? (int) $id : null);
$thread = $post ? thread((int) $post['thread_id']) : null;

/* Ownership enforcement: the server refuses, not just the UI. */
if (!$post || !$thread || !empty($post['deleted']) || !owns($post['author'])) {
    http_response_code($post && $thread ? 403 : 404);
    $page_title = 'You cannot edit this post';
    $page_description = 'Only the author of a post can edit it.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('You cannot edit this post') . '</h1>';
    if ($post && $thread && empty($post['deleted'])) {
        echo '<p class="page-head__lede">' . te('This post was written by %1$s. Only the account that wrote a post can edit it.', [user_name($post['author'])]) . '</p></div></div>';
        echo '<p><a class="button" href="thread.php?id=' . (int) $thread['id'] . '">' . te('Back to the thread') . '</a>';
        if (!is_logged_in()) {
            echo ' <a class="button button--primary" href="../account.php?return=' . e(rawurlencode('forum/edit-post.php?id=' . (int) $post['id'])) . '">' . te('Log in') . '</a>';
        }
        echo '</p>';
    } else {
        echo '<p class="page-head__lede">' . te('That post does not exist or has been removed.') . '</p></div></div>';
        echo '<p><a class="button" href="boards.php">' . te('Back to the forum') . '</a></p>';
    }
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$is_opening = $post['parent_id'] === null;

$rules = [
    'post-title'  => ['label' => 'Post title', 'required' => true, 'min' => 3, 'max' => 90],
    'post-body'   => ['label' => 'Post text', 'required' => true, 'min' => 10, 'max' => 5000],
    'edit-reason' => ['label' => 'Reason for the edit', 'max' => 120],
] + image_rules();
if ($is_opening) {
    $rules['thread-title'] = ['label' => 'Thread title', 'required' => true, 'min' => 10, 'max' => 90];
    $rules['thread-board'] = ['label' => 'Board', 'required' => true, 'in' => array_keys(GF_BOARDS)];
    $rules['thread-tag']   = ['label' => 'Tag', 'in' => array_keys(GF_THREAD_TAGS)];
}
$errors = [];
$values = [
    'post-title'    => $post['title'],
    'post-body'     => $post['body'],
    'edit-reason'   => '',
    'image-library' => $post['image'] !== '' ? 'keep' : '',
    'image-alt'     => $post['image_alt'],
    'image-caption' => $post['caption'],
    'thread-title'  => $thread['title'],
    'thread-board'  => $thread['board'],
    'thread-tag'    => $thread['tag'],
];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('edit-post.php?id=' . $post['id']);
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    [$image, $alt, $caption] = resolve_image($values, $errors, $post['image'], $post['image_alt']);

    if (!$errors) {
        $posts = &collection('posts');
        $p = &$posts[$post['id']];
        $p['title']       = $values['post-title'];
        $p['body']        = $values['post-body'];
        $p['image']       = $image;
        $p['image_alt']   = $alt;
        $p['caption']     = $caption;
        $p['edited']      = now_iso();
        $p['edit_reason'] = $values['edit-reason'];
        unset($p, $posts);
        if ($is_opening) {
            $threads = &collection('threads');
            $threads[$thread['id']]['title'] = $values['thread-title'];
            $threads[$thread['id']]['board'] = $values['thread-board'];
            $threads[$thread['id']]['tag']   = $values['thread-tag'];
            unset($threads);
        }
        flash('success', t('Your post has been updated.'));
        redirect('thread.php?id=' . $thread['id'] . '#post-' . $post['id']);
    }
}

$page_title = 'Edit your post';
$page_description = 'Edit a forum post you wrote.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="boards.php"><?= te('Forum') ?></a></li>
          <li><a href="thread.php?id=<?= $thread['id'] ?>"><?= e(excerpt($thread['title'], 40)) ?></a></li>
          <li aria-current="page"><?= te('Edit post') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Edit your post') ?></h1>
          <p class="page-head__lede"><?= te('Originally posted') ?> <?= time_html($post['created'], 'datetime') ?> <?= te('in "%1$s".', [$thread['title']]) ?></p>
        </div>
      </div>

      <div class="callout callout--primary">
        <h2 class="callout__title"><?= te('You are editing your own post') ?></h2>
        <p><?= te('This post was written by %1$s, the account you are logged in as, so it can be edited here. Posts written by other members cannot be edited or deleted by you, and the server refuses such requests.', [user_name(current_user())]) ?></p>
      </div>

      <div class="layout-aside">
        <div>
          <?= error_summary_html($errors) ?>
          <form class="form" method="post" action="edit-post.php?id=<?= $post['id'] ?>" enctype="multipart/form-data" data-validate>
            <?= csrf_field() ?>
<?php if ($is_opening): ?>
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Thread details') ?></legend>
              <p class="field">
                <label class="field__label" for="thread-title"><?= te('Thread title') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="thread-title" name="thread-title" value="<?= e($values['thread-title']) ?>" data-required data-minlength="10" data-maxlength="90"<?= invalid_attrs($errors, 'thread-title') ?>>
                <?= error_html($errors, 'thread-title') ?>
              </p>
              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="thread-board"><?= te('Board') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <select id="thread-board" name="thread-board" data-required<?= invalid_attrs($errors, 'thread-board') ?>>
<?php foreach (GF_BOARDS as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $values['thread-board'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
                  </select>
                  <?= error_html($errors, 'thread-board') ?>
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
<?php endif; ?>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Post content') ?></legend>

              <p class="field">
                <label class="field__label" for="post-title"><?= te('Post title') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="post-title" name="post-title" value="<?= e($values['post-title']) ?>" data-required data-minlength="3" data-maxlength="90"<?= invalid_attrs($errors, 'post-title', 'post-title-hint') ?>>
                <?= error_html($errors, 'post-title') ?>
                <span class="field__hint" id="post-title-hint"><?= te('Shown in the reply list and in search results.') ?></span>
              </p>

              <p class="field">
                <label class="field__label" for="post-body"><?= te('Post text') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <textarea id="post-body" name="post-body" data-required data-minlength="10" data-maxlength="5000"<?= invalid_attrs($errors, 'post-body', 'post-body-hint') ?>><?= e($values['post-body']) ?></textarea>
                <?= error_html($errors, 'post-body') ?>
                <span class="field__hint" id="post-body-hint"><?= te('Edits are marked with the time they were made.') ?></span>
              </p>
            </fieldset>

            <?= image_fields_html($values, $errors, $post['image'], 'Attached image') ?>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Edit note') ?></legend>
              <p class="field">
                <label class="field__label" for="edit-reason"><?= te('Reason for the edit') ?></label>
                <input type="text" id="edit-reason" name="edit-reason" value="<?= e($values['edit-reason']) ?>" data-maxlength="120"<?= invalid_attrs($errors, 'edit-reason', 'edit-reason-hint') ?>>
                <?= error_html($errors, 'edit-reason') ?>
                <span class="field__hint" id="edit-reason-hint"><?= te('Optional, shown publicly beneath the post. Useful when you are correcting a figure someone has already replied to.') ?></span>
              </p>
            </fieldset>

            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te('Save changes') ?></button>
              <a class="button button--quiet" href="thread.php?id=<?= $thread['id'] ?>#post-<?= $post['id'] ?>"><?= te('Cancel') ?></a>
              <a class="button button--danger" href="delete-post.php?id=<?= $post['id'] ?>"><?= te('Delete this post') ?></a>
            </div>
          </form>
        </div>

        <aside class="layout-aside__side" aria-labelledby="preview-heading">
          <div class="panel">
            <h2 class="panel__title" id="preview-heading"><?= te('Current version') ?></h2>
            <p class="byline">
              <img class="byline__avatar" src="../<?= e(user_avatar($post['author'])) ?>" alt="">
              <span class="byline__name"><?= e(user_name($post['author'])) ?></span>
              <?= time_html($post['created'], 'datetime') ?>
            </p>
            <p class="text-small text-muted mt-s"><?= te('This post has %1$s direct replies. Editing it will not notify the people who replied.', [number(direct_reply_count((int) $post['id']))]) ?></p>
          </div>

          <div class="panel">
            <h2 class="panel__title"><?= te('Edit history') ?></h2>
            <ul class="footer-nav__list">
              <li>
                <span><?= te('Posted') ?></span>
                <span class="sitemap-tree__note"><?= time_html($post['created'], 'datetime') ?></span>
              </li>
<?php if (!empty($post['edited'])): ?>
              <li>
                <span><?= te('Edited') ?><?= $post['edit_reason'] !== '' ? ' — ' . e($post['edit_reason']) : '' ?></span>
                <span class="sitemap-tree__note"><?= time_html($post['edited'], 'datetime') ?></span>
              </li>
<?php endif; ?>
            </ul>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
