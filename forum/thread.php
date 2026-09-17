<?php
/** One thread: the opening post, the reply tree, and the reply form (create). */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';
require_once __DIR__ . '/render.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$thread = thread($id === false || $id === null ? null : (int) $id);
$opening = $thread ? opening_post($thread['id']) : null;

if (!$thread || !$opening || !empty($opening['deleted'])) {
    http_response_code(404);
    $page_title = 'Thread not found';
    $page_description = 'The requested forum thread does not exist.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('Thread not found') . '</h1><p class="page-head__lede">' . te('That thread does not exist or has been removed.') . '</p></div></div>';
    echo '<p><a class="button" href="boards.php">' . te('Back to the forum') . '</a></p>';
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$rules = [
    'reply-title' => ['label' => 'Reply title', 'required' => true, 'min' => 3, 'max' => 90],
    'reply-body'  => ['label' => 'Your reply', 'required' => true, 'min' => 10, 'max' => 5000],
    'parent'      => ['label' => 'Replying to', 'type' => 'int'],
] + image_rules();
$errors = [];
$values = ['reply-title' => '', 'reply-body' => '', 'parent' => '', 'image-library' => '', 'image-alt' => '', 'image-caption' => ''];

$all_posts = thread_posts_all($thread['id']);

/* Which post is being replied to (from ?reply= or the submitted form). */
$reply_to = filter_input(INPUT_GET, 'reply', FILTER_VALIDATE_INT);
$reply_to = $reply_to ? (int) $reply_to : (int) $opening['id'];

if (is_post()) {
    if (!is_logged_in()) {
        flash('danger', t('Log in to reply.'));
        redirect('thread.php?id=' . $thread['id']);
    }
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('thread.php?id=' . $thread['id']);
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);

    $parent = (int) ($values['parent'] ?: $opening['id']);
    if (!isset($all_posts[$parent]) || !empty($all_posts[$parent]['deleted'])) {
        $errors['parent'] = t('The post you are replying to no longer exists.');
    }
    $reply_to = $parent;

    [$image, $alt, $caption] = resolve_image($values, $errors);

    if (!$errors) {
        $posts = &collection('posts');
        $newId = next_id('posts');
        $posts[$newId] = [
            'thread_id'   => $thread['id'],
            'parent_id'   => $parent,
            'author'      => current_user(),
            'created'     => now_iso(),
            'edited'      => null,
            'edit_reason' => '',
            'deleted'     => false,
            'title'       => $values['reply-title'],
            'body'        => $values['reply-body'],
            'image'       => $image,
            'image_alt'   => $alt,
            'caption'     => $caption,
        ];
        unset($posts);
        flash('success', t('Your reply has been posted.'));
        redirect('thread.php?id=' . $thread['id'] . '#post-' . $newId);
    }
} else {
    // Count a view on a plain page load.
    $threads = &collection('threads');
    $threads[$thread['id']]['views'] = (int) $threads[$thread['id']]['views'] + 1;
    $thread['views']++;
    unset($threads);
}

$visible = thread_posts($thread['id']);
$summary = thread_summary($thread['id']);
$reply_target = $all_posts[$reply_to] ?? $opening;

$page_title = $thread['title'];
$page_description = excerpt($opening['body'], 150);
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="boards.php"><?= te('Forum') ?></a></li>
          <li aria-current="page"><?= e(excerpt($thread['title'], 40)) ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= e($thread['title']) ?></h1>
          <p class="page-head__lede">
            <?= te('%1$s replies', [number($summary['replies'])]) ?> &middot;
            <?= te('%1$s views', [number((int) $thread['views'])]) ?> &middot;
            <?= te('last active') ?> <?= time_html($summary['newest'], 'datetime') ?> &middot;
            <span class="badge"><?= te(GF_BOARDS[$thread['board']] ?? $thread['board']) ?></span>
          </p>
        </div>
        <div class="page-head__actions">
<?php if (is_logged_in()): ?>
          <a class="button button--primary" href="#reply-form"><?= te('Reply to thread') ?></a>
<?php else: ?>
          <a class="button button--primary" href="../account.php?return=<?= e(rawurlencode('forum/thread.php?id=' . $thread['id'])) ?>"><?= te('Log in to reply') ?></a>
<?php endif; ?>
        </div>
      </div>

      <section class="section" aria-labelledby="opening-heading">
        <h2 class="visually-hidden" id="opening-heading"><?= te('Opening post') ?></h2>
        <?= render_forum_post($opening, $thread, true) ?>
      </section>

      <section class="section" aria-labelledby="replies-heading">
        <div class="section__head">
          <h2 id="replies-heading"><?= te('%1$s replies', [number($summary['replies'])]) ?></h2>
        </div>
<?php $tree = render_reply_tree($visible, $all_posts, (int) $opening['id'], $thread);
if ($tree === ''): ?>
        <p class="text-muted"><?= te('No replies yet. Be the first.') ?></p>
<?php else: ?>
        <?= $tree ?>
<?php endif; ?>
      </section>

<?php if (is_logged_in()): ?>
      <section class="section" aria-labelledby="reply-heading">
        <div class="section__head">
          <h2 id="reply-heading"><?= te('Post a reply') ?></h2>
        </div>

        <?= error_summary_html($errors) ?>
        <form class="form" id="reply-form" method="post" action="thread.php?id=<?= $thread['id'] ?>" enctype="multipart/form-data" data-validate>
          <?= csrf_field() ?>
          <input type="hidden" name="parent" value="<?= (int) $reply_target['id'] ?>">

          <p class="text-small text-muted">
            <?= te('Replying as %1$s to "%2$s" by %3$s.', [user_name(current_user()), $reply_target['title'], user_name($reply_target['author'])]) ?>
<?php if ((int) $reply_target['id'] !== (int) $opening['id']): ?>
            <a href="thread.php?id=<?= $thread['id'] ?>#reply-form"><?= te('Reply to the thread instead') ?></a>
<?php endif; ?>
          </p>

          <p class="field">
            <label class="field__label" for="reply-title"><?= te('Reply title') ?> <span class="field__required" aria-hidden="true">*</span></label>
            <input type="text" id="reply-title" name="reply-title" value="<?= e($values['reply-title']) ?>" data-required data-minlength="3" data-maxlength="90"<?= invalid_attrs($errors, 'reply-title', 'reply-title-hint') ?>>
            <?= error_html($errors, 'reply-title') ?>
            <span class="field__hint" id="reply-title-hint"><?= te('A short summary of your point, between 3 and 90 characters.') ?></span>
          </p>

          <p class="field">
            <label class="field__label" for="reply-body"><?= te('Your reply') ?> <span class="field__required" aria-hidden="true">*</span></label>
            <textarea id="reply-body" name="reply-body" data-required data-minlength="10" data-maxlength="5000"<?= invalid_attrs($errors, 'reply-body', 'reply-body-hint') ?>><?= e($values['reply-body']) ?></textarea>
            <?= error_html($errors, 'reply-body') ?>
            <span class="field__hint" id="reply-body-hint"><?= te('Plain text, at least 10 characters. Leave a blank line between paragraphs.') ?></span>
          </p>

          <?= image_fields_html($values, $errors, '', 'Attach an image') ?>

          <div class="form__actions">
            <button type="submit" class="button button--primary"><?= te('Post reply') ?></button>
            <a class="button button--quiet" href="boards.php"><?= te('Cancel') ?></a>
          </div>
        </form>
      </section>
<?php else: ?>
      <div class="callout callout--primary">
        <h2 class="callout__title"><?= te('Want to reply?') ?></h2>
        <p><a href="../account.php?return=<?= e(rawurlencode('forum/thread.php?id=' . $thread['id'])) ?>"><?= te('Log in') ?></a> <?= te('with any username to post a reply. Only the account that wrote a post can edit or delete it.') ?></p>
      </div>
<?php endif; ?>

<?php require __DIR__ . '/../shared/footer.php'; ?>
