<?php
/**
 * Delete a post. Only the author may do this. The post is removed from view
 * but kept in memory ('deleted' => true) for auditing.
 */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/render.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$post = forum_post($id ? (int) $id : null);
$thread = $post ? thread((int) $post['thread_id']) : null;

if (!$post || !$thread || !empty($post['deleted']) || !owns($post['author'])) {
    http_response_code($post && $thread ? 403 : 404);
    $page_title = 'You cannot delete this post';
    $page_description = 'Only the author of a post can delete it.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('You cannot delete this post') . '</h1>';
    if ($post && $thread && empty($post['deleted'])) {
        echo '<p class="page-head__lede">' . te('This post was written by %1$s. Only the account that wrote a post can delete it.', [user_name($post['author'])]) . '</p></div></div>';
        echo '<p><a class="button" href="thread.php?id=' . (int) $thread['id'] . '">' . te('Back to the thread') . '</a></p>';
    } else {
        echo '<p class="page-head__lede">' . te('That post does not exist or has been removed.') . '</p></div></div>';
        echo '<p><a class="button" href="boards.php">' . te('Back to the forum') . '</a></p>';
    }
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$is_opening = $post['parent_id'] === null;
$replies = direct_reply_count((int) $post['id']);

$rules = [
    'delete-reason'  => ['label' => 'Reason', 'in' => array_keys(GF_DELETE_REASONS)],
    'delete-confirm' => ['label' => 'I understand this removes the post from the thread', 'checked' => true],
];
$errors = [];
$values = ['delete-reason' => '', 'delete-confirm' => ''];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('delete-post.php?id=' . $post['id']);
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    if (!$errors) {
        $posts = &collection('posts');
        $posts[$post['id']]['deleted'] = true;
        $posts[$post['id']]['deleted_at'] = now_iso();
        $posts[$post['id']]['deleted_by'] = current_user();
        $posts[$post['id']]['delete_reason'] = $values['delete-reason'];
        unset($posts);
        if ($is_opening) {
            flash('success', t('Your thread has been deleted.'));
            redirect('boards.php');
        }
        flash('success', t('Your post has been deleted.'));
        redirect('thread.php?id=' . $thread['id']);
    }
}

$page_title = 'Delete your post?';
$page_description = 'Confirm the deletion of a forum post you wrote.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="boards.php"><?= te('Forum') ?></a></li>
          <li><a href="thread.php?id=<?= $thread['id'] ?>"><?= e(excerpt($thread['title'], 40)) ?></a></li>
          <li aria-current="page"><?= te('Delete post') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= $is_opening ? te('Delete your thread?') : te('Delete your post?') ?></h1>
          <p class="page-head__lede"><?= te('This cannot be undone from the site. Read the post below and make sure it is the one you meant.') ?></p>
        </div>
      </div>

<?php if ($is_opening): ?>
      <div class="callout callout--danger">
        <h2 class="callout__title"><?= te('This is the opening post') ?></h2>
        <p><?= te('Deleting the opening post removes the whole thread, including %1$s replies, from the forum.', [number(count(thread_posts($thread['id'])) - 1)]) ?></p>
      </div>
<?php elseif ($replies > 0): ?>
      <div class="callout callout--danger">
        <h2 class="callout__title"><?= te('%1$s replies will be orphaned', [number($replies)]) ?></h2>
        <p><?= te('This post has %1$s direct replies. Deleting it keeps those replies in the thread but removes the post they were answering, so they will be shown beneath the post above it. Editing the post is usually the better option.', [number($replies)]) ?></p>
      </div>
<?php endif; ?>

      <section class="section" aria-labelledby="target-heading">
        <div class="section__head">
          <h2 id="target-heading"><?= te('The post you are about to delete') ?></h2>
        </div>
        <?= render_forum_post($post, $thread, $is_opening, false) ?>
      </section>

      <section class="section" aria-labelledby="confirm-heading">
        <div class="section__head">
          <h2 id="confirm-heading"><?= te('Confirm deletion') ?></h2>
        </div>

        <?= error_summary_html($errors) ?>
        <form class="form" method="post" action="delete-post.php?id=<?= $post['id'] ?>" data-validate>
          <?= csrf_field() ?>
          <fieldset class="fieldset">
            <legend class="fieldset__legend"><?= te('Why are you deleting this?') ?></legend>

            <p class="field">
              <label class="field__label" for="delete-reason"><?= te('Reason') ?></label>
              <select id="delete-reason" name="delete-reason"<?= invalid_attrs($errors, 'delete-reason', 'delete-reason-hint') ?>>
                <option value=""<?= $values['delete-reason'] === '' ? ' selected' : '' ?>><?= te('Prefer not to say') ?></option>
<?php foreach (GF_DELETE_REASONS as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $values['delete-reason'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
              </select>
              <?= error_html($errors, 'delete-reason') ?>
              <span class="field__hint" id="delete-reason-hint"><?= te('Only visible to moderators. Kept with the post in the audit record.') ?></span>
            </p>

            <p class="field field--inline">
              <input type="checkbox" id="delete-confirm" name="delete-confirm" value="yes" data-checked<?= invalid_attrs($errors, 'delete-confirm', 'delete-confirm-hint') ?>>
              <label class="field__label" for="delete-confirm"><?= te('I understand this removes the post from the thread') ?></label>
            </p>
            <?= error_html($errors, 'delete-confirm') ?>
            <span class="field__hint" id="delete-confirm-hint"><?= te('Required. The post is retained for auditing but can no longer be seen by members.') ?></span>
          </fieldset>

          <div class="form__actions">
            <button type="submit" class="button button--danger-solid">
              <svg class="button__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">
                <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 6.5h16M9.5 6.5V4h5v2.5"/>
                  <path d="M6.5 6.5 7.5 20h9l1-13.5"/>
                  <path d="M10.5 10v6M13.5 10v6"/>
                </g>
              </svg>
              <?= $is_opening ? te('Delete thread') : te('Delete post') ?>
            </button>
            <a class="button" href="edit-post.php?id=<?= $post['id'] ?>"><?= te('Edit it instead') ?></a>
            <a class="button button--quiet" href="thread.php?id=<?= $thread['id'] ?>"><?= te('Cancel and go back') ?></a>
          </div>
        </form>
      </section>

<?php require __DIR__ . '/../shared/footer.php'; ?>
