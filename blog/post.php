<?php
/**
 * Full blog post with its comments. Comments can be created, edited and
 * deleted here by their own author; the post itself is edited on
 * edit-post.php and deleted on delete-post.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$post = blog_post($id ? (int) $id : null);

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found';
    $page_description = 'The requested blog post does not exist.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('Post not found') . '</h1><p class="page-head__lede">' . te('That post does not exist or has been deleted.') . '</p></div></div>';
    echo '<p><a class="button" href="posts.php">' . te('Back to all posts') . '</a></p>';
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$rules = ['comment-body' => ['label' => 'Your comment', 'required' => true, 'min' => 5, 'max' => 2000]];
$errors = [];
$values = ['comment-body' => ''];
$editing = filter_input(INPUT_GET, 'edit-comment', FILTER_VALIDATE_INT);
$editing = $editing ? blog_comment((int) $editing) : null;
if ($editing && (!owns($editing['author']) || (int) $editing['post_id'] !== $post['id'])) {
    $editing = null;
}

if (is_post()) {
    if (!is_logged_in()) {
        flash('danger', t('Log in to comment.'));
        redirect('post.php?id=' . $post['id']);
    }
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('post.php?id=' . $post['id']);
    }
    $action = (string) (filter_input(INPUT_POST, 'action', FILTER_DEFAULT) ?? '');
    $comments = &collection('comments');

    if ($action === 'comment' && $post['allow_comments']) {
        $values = form_values($rules);
        $errors = validate($rules, $values);
        if (!$errors) {
            $cid = next_id('comments');
            $comments[$cid] = ['post_id' => $post['id'], 'author' => current_user(), 'created' => now_iso(), 'body' => $values['comment-body']];
            flash('success', t('Your comment has been posted.'));
            redirect('post.php?id=' . $post['id'] . '#comment-' . $cid);
        }
    }

    if ($action === 'comment-edit') {
        $cid = (int) (filter_input(INPUT_POST, 'comment', FILTER_VALIDATE_INT) ?: 0);
        $c = blog_comment($cid);
        if (!$c || !owns($c['author']) || (int) $c['post_id'] !== $post['id']) {
            http_response_code(403);
            flash('danger', t('You can only edit your own comments.'));
            redirect('post.php?id=' . $post['id']);
        }
        $values = form_values($rules);
        $errors = validate($rules, $values);
        if (!$errors) {
            $comments[$cid]['body'] = $values['comment-body'];
            $comments[$cid]['edited'] = now_iso();
            flash('success', t('Your comment has been updated.'));
            redirect('post.php?id=' . $post['id'] . '#comment-' . $cid);
        }
        $editing = $c;
    }

    if ($action === 'comment-delete') {
        $cid = (int) (filter_input(INPUT_POST, 'comment', FILTER_VALIDATE_INT) ?: 0);
        $c = blog_comment($cid);
        if (!$c || !owns($c['author']) || (int) $c['post_id'] !== $post['id']) {
            http_response_code(403);
            flash('danger', t('You can only delete your own comments.'));
            redirect('post.php?id=' . $post['id']);
        }
        unset($comments[$cid]);
        flash('success', t('Your comment has been deleted.'));
        redirect('post.php?id=' . $post['id'] . '#comments-heading');
    }
    unset($comments);
}

$comments = blog_comments($post['id']);
$mine = owns($post['author']);
$more = array_filter(blog_query(['q' => '', 'text' => '', 'author' => '', 'tag' => $post['tags'][0] ?? '', 'from' => '', 'to' => '', 'sort' => 'date-desc']), function ($p) use ($post) {
    return $p['id'] !== $post['id'];
});

$page_title = $post['title'];
$page_description = $post['summary'];
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="posts.php"><?= te('Blog') ?></a></li>
          <li aria-current="page"><?= e(excerpt($post['title'], 40)) ?></li>
        </ol>
      </nav>

      <div class="layout-aside">
        <article>
          <div class="page-head">
            <div class="page-head__text">
              <h1><?= e($post['title']) ?></h1>
              <p class="page-head__lede"><?= e($post['summary']) ?></p>
            </div>
<?php if ($mine): ?>
            <div class="page-head__actions">
              <a class="button" href="edit-post.php?id=<?= $post['id'] ?>"><?= te('Edit') ?></a>
              <a class="button button--danger" href="delete-post.php?id=<?= $post['id'] ?>"><?= te('Delete') ?></a>
            </div>
<?php endif; ?>
          </div>

          <p class="byline">
            <img class="byline__avatar" src="../<?= e(user_avatar($post['author'])) ?>" alt="">
            <span class="byline__name"><?= e(user_name($post['author'])) ?></span>
            <span><?= te('Published') ?> <?= time_html($post['date']) ?></span>
<?php if (!empty($post['updated'])): ?>
            <span><?= te('Updated') ?> <?= time_html($post['updated']) ?></span>
<?php endif; ?>
            <span><?= te('About %1$s minutes to read', [number(reading_minutes($post['body']))]) ?></span>
<?php if ($mine): ?>
            <span class="badge badge--primary"><?= te('Your post') ?></span>
<?php endif; ?>
          </p>

          <ul class="tag-list mt-s">
<?php foreach ($post['tags'] as $tag): ?>
            <li><a class="tag" href="posts.php?tag=<?= e($tag) ?>"><?= te(GF_BLOG_TAGS[$tag] ?? $tag) ?></a></li>
<?php endforeach; ?>
          </ul>

<?php if ($post['image'] !== ''): ?>
          <figure class="post__figure mt-m">
            <img class="post__image" src="<?= e(image_src($post['image'])) ?>" alt="<?= e($post['image_alt']) ?>">
<?php if ($post['caption'] !== ''): ?>
            <figcaption><?= e($post['caption']) ?></figcaption>
<?php endif; ?>
          </figure>
<?php endif; ?>

          <div class="prose mt-l"><?= paragraphs($post['body']) ?></div>

          <div class="post__actions mt-l">
            <a class="button" href="posts.php"><?= te('Back to all posts') ?></a>
            <a class="button button--quiet" href="#comments-heading"><?= te('Jump to comments') ?></a>
            <span class="ownership-note"><?= $mine ? te('Written by you') : te('Written by %1$s', [user_name($post['author'])]) ?></span>
          </div>
        </article>

        <aside class="layout-aside__side" aria-labelledby="about-author-heading">
          <div class="panel">
            <h2 class="panel__title" id="about-author-heading"><?= te('About the author') ?></h2>
            <p class="byline">
              <img class="byline__avatar" src="../<?= e(user_avatar($post['author'])) ?>" alt="">
              <span class="byline__name"><?= e(user_name($post['author'])) ?></span>
            </p>
<?php $u = user($post['author']); if ($u && $u['bio'] !== ''): ?>
            <p class="text-small mt-s"><?= e($u['bio']) ?></p>
<?php endif; ?>
<?php $st = gf_user_stats($post['author']); ?>
            <p class="text-small text-muted"><?= te('%1$s blog posts · %2$s forum posts', [number($st['blog']), number($st['posts'])]) ?></p>
          </div>

<?php if ($more): ?>
          <div class="panel">
            <h2 class="panel__title"><?= te('More like this') ?></h2>
            <ul class="footer-nav__list">
<?php foreach (array_slice($more, 0, 3) as $m): ?>
              <li><a href="post.php?id=<?= $m['id'] ?>"><?= e($m['title']) ?></a></li>
<?php endforeach; ?>
            </ul>
          </div>
<?php endif; ?>
        </aside>
      </div>

      <section class="section" aria-labelledby="comments-heading">
        <div class="section__head">
          <h2 id="comments-heading"><?= te('%1$s comments', [number(count($comments))]) ?></h2>
        </div>

<?php if (!$comments): ?>
        <p class="text-muted"><?= te('No comments yet.') ?></p>
<?php else: ?>
        <ul class="comment-list">
<?php foreach ($comments as $c): $cmine = owns($c['author']); ?>
          <li>
            <article class="comment<?= $cmine ? ' post--mine' : '' ?>" id="comment-<?= $c['id'] ?>">
              <p class="byline">
                <img class="byline__avatar" src="../<?= e(user_avatar($c['author'])) ?>" alt="">
                <span class="byline__name"><?= e(user_name($c['author'])) ?></span>
                <?= time_html($c['created'], 'datetime') ?>
<?php if ($c['author'] === $post['author']): ?>
                <span class="badge"><?= te('Author') ?></span>
<?php endif; ?>
<?php if ($cmine): ?>
                <span class="badge badge--primary"><?= te('Your comment') ?></span>
<?php endif; ?>
<?php if (!empty($c['edited'])): ?>
                <span class="text-muted text-small"><?= te('Edited') ?> <?= time_html($c['edited'], 'datetime') ?></span>
<?php endif; ?>
              </p>
<?php if ($editing && $editing['id'] === $c['id']): ?>
              <?= error_summary_html($errors) ?>
              <form class="form" method="post" action="post.php?id=<?= $post['id'] ?>" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="comment-edit">
                <input type="hidden" name="comment" value="<?= $c['id'] ?>">
                <p class="field">
                  <label class="field__label" for="comment-body"><?= te('Edit your comment') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <textarea id="comment-body" name="comment-body" data-required data-minlength="5" data-maxlength="2000"<?= invalid_attrs($errors, 'comment-body') ?>><?= e($values['comment-body'] !== '' ? $values['comment-body'] : $c['body']) ?></textarea>
                  <?= error_html($errors, 'comment-body') ?>
                </p>
                <div class="form__actions">
                  <button type="submit" class="button button--primary button--small"><?= te('Save comment') ?></button>
                  <a class="button button--quiet button--small" href="post.php?id=<?= $post['id'] ?>#comment-<?= $c['id'] ?>"><?= te('Cancel') ?></a>
                </div>
              </form>
<?php else: ?>
              <div class="prose"><?= paragraphs($c['body']) ?></div>
              <div class="post__actions">
<?php if ($cmine): ?>
                <a class="button button--small" href="post.php?id=<?= $post['id'] ?>&amp;edit-comment=<?= $c['id'] ?>#comment-<?= $c['id'] ?>"><?= te('Edit') ?></a>
                <form method="post" action="post.php?id=<?= $post['id'] ?>" class="logout-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="comment-delete">
                  <input type="hidden" name="comment" value="<?= $c['id'] ?>">
                  <button type="submit" class="button button--small button--danger"><?= te('Delete') ?></button>
                </form>
                <span class="ownership-note"><?= te('Posted by you') ?></span>
<?php else: ?>
                <span class="ownership-note"><?= te('Posted by %1$s', [user_name($c['author'])]) ?></span>
<?php endif; ?>
              </div>
<?php endif; ?>
            </article>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </section>

<?php if (!$post['allow_comments']): ?>
      <p class="text-muted"><?= te('Comments are closed on this post.') ?></p>
<?php elseif (is_logged_in() && !$editing): ?>
      <section class="section" aria-labelledby="add-comment-heading">
        <div class="section__head">
          <h2 id="add-comment-heading"><?= te('Leave a comment') ?></h2>
        </div>

        <?= error_summary_html($errors) ?>
        <form class="form" method="post" action="post.php?id=<?= $post['id'] ?>" data-validate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="comment">
          <p class="field">
            <label class="field__label" for="comment-body"><?= te('Your comment') ?> <span class="field__required" aria-hidden="true">*</span></label>
            <textarea id="comment-body" name="comment-body" data-required data-minlength="5" data-maxlength="2000"<?= invalid_attrs($errors, 'comment-body', 'comment-body-hint') ?>><?= e($values['comment-body']) ?></textarea>
            <?= error_html($errors, 'comment-body') ?>
            <span class="field__hint" id="comment-body-hint"><?= te('Posting as %1$s. Comments appear straight away and can be edited afterwards.', [user_name(current_user())]) ?></span>
          </p>
          <div class="form__actions">
            <button type="submit" class="button button--primary"><?= te('Post comment') ?></button>
            <a class="button button--quiet" href="posts.php"><?= te('Cancel') ?></a>
          </div>
        </form>
      </section>
<?php elseif (!is_logged_in()): ?>
      <div class="callout callout--primary">
        <h2 class="callout__title"><?= te('Want to comment?') ?></h2>
        <p><a href="../account.php?return=<?= e(rawurlencode('blog/post.php?id=' . $post['id'])) ?>"><?= te('Log in') ?></a> <?= te('with any username to leave a comment.') ?></p>
      </div>
<?php endif; ?>

<?php require __DIR__ . '/../shared/footer.php'; ?>
