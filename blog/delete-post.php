<?php
/** Delete a blog post (and its comments). Only its author may do this. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$post = blog_post($id ? (int) $id : null);

if (!$post || !owns($post['author'])) {
    http_response_code($post ? 403 : 404);
    $page_title = 'You cannot delete this post';
    $page_description = 'Only the author of a blog post can delete it.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('You cannot delete this post') . '</h1>';
    if ($post) {
        echo '<p class="page-head__lede">' . te('This post was written by %1$s. Only the account that wrote a post can delete it.', [user_name($post['author'])]) . '</p></div></div>';
        echo '<p><a class="button" href="post.php?id=' . (int) $post['id'] . '">' . te('Back to the post') . '</a></p>';
    } else {
        echo '<p class="page-head__lede">' . te('That post does not exist or has been deleted.') . '</p></div></div>';
        echo '<p><a class="button" href="posts.php">' . te('Back to all posts') . '</a></p>';
    }
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$comment_count = blog_comment_count($post['id']);
$rules = ['delete-confirm' => ['label' => 'I understand this permanently deletes the post and its comments', 'checked' => true]];
$errors = [];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('delete-post.php?id=' . $post['id']);
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    if (!$errors) {
        $posts = &collection('blog');
        unset($posts[$post['id']]);
        $comments = &collection('comments');
        foreach ($comments as $cid => $c) {
            if ((int) $c['post_id'] === $post['id']) {
                unset($comments[$cid]);
            }
        }
        unset($posts, $comments);
        flash('success', t('Your post "%1$s" has been deleted.', [$post['title']]));
        redirect('posts.php');
    }
}

$page_title = 'Delete your post?';
$page_description = 'Confirm the deletion of a blog post you wrote.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="posts.php"><?= te('Blog') ?></a></li>
          <li><a href="post.php?id=<?= $post['id'] ?>"><?= e(excerpt($post['title'], 40)) ?></a></li>
          <li aria-current="page"><?= te('Delete post') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Delete your post?') ?></h1>
          <p class="page-head__lede"><?= te('This cannot be undone from the site. Check the preview below and make sure it is the post you meant.') ?></p>
        </div>
      </div>

<?php if ($comment_count > 0): ?>
      <div class="callout callout--danger">
        <h2 class="callout__title"><?= te('%1$s comments will be deleted with it', [number($comment_count)]) ?></h2>
        <p><?= te('Comments belong to the post they were written on. Deleting the post removes them too, including comments written by other members.') ?></p>
      </div>
<?php endif; ?>

      <section class="section" aria-labelledby="target-heading">
        <div class="section__head">
          <h2 id="target-heading"><?= te('The post you are about to delete') ?></h2>
        </div>

        <article class="card post--mine">
<?php if ($post['image'] !== ''): ?>
          <img class="card__media" src="<?= e(image_src($post['image'])) ?>" alt="<?= e($post['image_alt']) ?>">
<?php endif; ?>
          <div class="card__body">
            <h3 class="card__title"><?= e($post['title']) ?></h3>
            <p class="byline">
              <img class="byline__avatar" src="../<?= e(user_avatar($post['author'])) ?>" alt="">
              <span class="byline__name"><?= e(user_name($post['author'])) ?></span>
              <?= time_html($post['date']) ?>
              <span class="badge badge--primary"><?= te('Your post') ?></span>
            </p>
            <p class="card__summary"><?= e($post['summary']) ?></p>
          </div>
        </article>
      </section>

      <section class="section" aria-labelledby="confirm-heading">
        <div class="section__head">
          <h2 id="confirm-heading"><?= te('Confirm deletion') ?></h2>
        </div>

        <?= error_summary_html($errors) ?>
        <form class="form" method="post" action="delete-post.php?id=<?= $post['id'] ?>" data-validate>
          <?= csrf_field() ?>
          <fieldset class="fieldset">
            <legend class="fieldset__legend"><?= te('Are you sure?') ?></legend>
            <p class="field field--inline">
              <input type="checkbox" id="delete-confirm" name="delete-confirm" value="yes" data-checked<?= invalid_attrs($errors, 'delete-confirm', 'delete-confirm-hint') ?>>
              <label class="field__label" for="delete-confirm"><?= te('I understand this permanently deletes the post and its comments') ?></label>
            </p>
            <?= error_html($errors, 'delete-confirm') ?>
            <span class="field__hint" id="delete-confirm-hint"><?= te('Required.') ?></span>
          </fieldset>

          <div class="form__actions">
            <button type="submit" class="button button--danger-solid"><?= te('Delete post permanently') ?></button>
            <a class="button" href="edit-post.php?id=<?= $post['id'] ?>"><?= te('Edit it instead') ?></a>
            <a class="button button--quiet" href="post.php?id=<?= $post['id'] ?>"><?= te('Cancel and go back') ?></a>
          </div>
        </form>
      </section>

<?php require __DIR__ . '/../shared/footer.php'; ?>
