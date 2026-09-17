<?php
/** Update a blog post. Only its author may do this. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$post = blog_post($id ? (int) $id : null);

if (!$post || !owns($post['author'])) {
    http_response_code($post ? 403 : 404);
    $page_title = 'You cannot edit this post';
    $page_description = 'Only the author of a blog post can edit it.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('You cannot edit this post') . '</h1>';
    if ($post) {
        echo '<p class="page-head__lede">' . te('This post was written by %1$s. Only the account that wrote a post can edit it.', [user_name($post['author'])]) . '</p></div></div>';
        echo '<p><a class="button" href="post.php?id=' . (int) $post['id'] . '">' . te('Back to the post') . '</a>';
        if (!is_logged_in()) {
            echo ' <a class="button button--primary" href="../account.php?return=' . e(rawurlencode('blog/edit-post.php?id=' . (int) $post['id'])) . '">' . te('Log in') . '</a>';
        }
        echo '</p>';
    } else {
        echo '<p class="page-head__lede">' . te('That post does not exist or has been deleted.') . '</p></div></div>';
        echo '<p><a class="button" href="posts.php">' . te('Back to all posts') . '</a></p>';
    }
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$rules = blog_rules();
$errors = [];
$values = [
    'post-title'    => $post['title'],
    'post-summary'  => $post['summary'],
    'post-body'     => $post['body'],
    'post-date'     => $post['date'],
    'post-comments' => $post['allow_comments'] ? 'yes' : '',
    'post-tags'     => $post['tags'],
    'image-library' => $post['image'] !== '' ? 'keep' : '',
    'image-alt'     => $post['image_alt'],
    'image-caption' => $post['caption'],
];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('edit-post.php?id=' . $post['id']);
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    [$values['post-tags'], $tagError] = blog_tags_from_post();
    if ($tagError) {
        $errors['post-tags'] = $tagError;
    }
    [$image, $alt, $caption] = resolve_image($values, $errors, $post['image'], $post['image_alt']);

    if (!$errors) {
        $posts = &collection('blog');
        $p = &$posts[$post['id']];
        $p['title']          = $values['post-title'];
        $p['summary']        = $values['post-summary'];
        $p['body']           = $values['post-body'];
        $p['date']           = $values['post-date'];
        $p['tags']           = $values['post-tags'];
        $p['allow_comments'] = $values['post-comments'] !== '';
        $p['image']          = $image;
        $p['image_alt']      = $alt;
        $p['caption']        = $caption;
        $p['updated']        = date('Y-m-d');
        unset($p, $posts);
        flash('success', t('Your post has been updated.'));
        redirect('post.php?id=' . $post['id']);
    }
}

$action = 'edit-post.php?id=' . $post['id'];
$submit_label = 'Save changes';
$current_image = $post['image'];

$page_title = 'Edit your post';
$page_description = 'Edit a blog post you wrote.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="posts.php"><?= te('Blog') ?></a></li>
          <li><a href="post.php?id=<?= $post['id'] ?>"><?= e(excerpt($post['title'], 40)) ?></a></li>
          <li aria-current="page"><?= te('Edit post') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Edit your post') ?></h1>
          <p class="page-head__lede"><?= te('Published') ?> <?= time_html($post['date']) ?>. <?= te('Changes are shown immediately and the post is marked as updated.') ?></p>
        </div>
      </div>

      <div class="callout callout--primary">
        <h2 class="callout__title"><?= te('You are editing your own post') ?></h2>
        <p><?= te('This post was written by %1$s, the account you are logged in as. Posts by other authors cannot be edited or deleted by you.', [user_name(current_user())]) ?></p>
      </div>

      <div class="layout-aside">
        <div>
<?php require __DIR__ . '/form.php'; ?>
        </div>

        <aside class="layout-aside__side" aria-labelledby="preview-heading">
          <div class="panel">
            <h2 class="panel__title" id="preview-heading"><?= te('Current version') ?></h2>
            <p class="byline">
              <img class="byline__avatar" src="../<?= e(user_avatar($post['author'])) ?>" alt="">
              <span class="byline__name"><?= e(user_name($post['author'])) ?></span>
              <?= time_html($post['date']) ?>
            </p>
            <p class="text-small text-muted mt-s"><?= te('This post has %1$s comments. Editing it does not affect them.', [number(blog_comment_count($post['id']))]) ?></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
