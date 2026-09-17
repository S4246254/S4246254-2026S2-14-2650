<?php
/** Create a blog post. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

if (!is_logged_in()) {
    flash('danger', t('Log in to write a post.'));
    redirect('../account.php?return=' . rawurlencode('blog/new-post.php'));
}

$rules = blog_rules();
$errors = [];
$values = array_fill_keys(array_keys($rules), '');
$values['post-tags'] = [];
$values['post-date'] = date('Y-m-d');
$values['post-comments'] = 'yes';

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('new-post.php');
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    [$values['post-tags'], $tagError] = blog_tags_from_post();
    if ($tagError) {
        $errors['post-tags'] = $tagError;
    }
    [$image, $alt, $caption] = resolve_image($values, $errors);

    if (!$errors) {
        $posts = &collection('blog');
        $newId = next_id('blog');
        $posts[$newId] = [
            'title'          => $values['post-title'],
            'author'         => current_user(),
            'date'           => $values['post-date'],
            'updated'        => null,
            'tags'           => $values['post-tags'],
            'allow_comments' => $values['post-comments'] !== '',
            'summary'        => $values['post-summary'],
            'body'           => $values['post-body'],
            'image'          => $image,
            'image_alt'      => $alt,
            'caption'        => $caption,
        ];
        unset($posts);
        flash('success', t('Your post has been published.'));
        redirect('post.php?id=' . $newId);
    }
}

$action = 'new-post.php';
$submit_label = 'Publish post';
$current_image = '';

$page_title = 'Write a post';
$page_description = 'Publish a new post on the Grimdark Forge blog.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="posts.php"><?= te('Blog') ?></a></li>
          <li aria-current="page"><?= te('Write a post') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Write a post') ?></h1>
          <p class="page-head__lede"><?= te('Publishing as %1$s. Posts appear in the blog index immediately.', [user_name(current_user())]) ?></p>
        </div>
      </div>

      <div class="layout-aside">
        <div>
<?php require __DIR__ . '/form.php'; ?>
        </div>

        <aside class="layout-aside__side" aria-labelledby="guide-heading">
          <div class="panel">
            <h2 class="panel__title" id="guide-heading"><?= te('Writing a good tutorial') ?></h2>
            <div class="prose">
              <ul>
                <li><?= te('Say what the reader will be able to do at the end.') ?></li>
                <li><?= te('One technique per post. Split anything longer.') ?></li>
                <li><?= te('Photograph in daylight, and show the failure as well as the success.') ?></li>
                <li><?= te('Name the exact paints and brushes you used.') ?></li>
              </ul>
            </div>
          </div>

          <div class="callout callout--primary">
            <h2 class="callout__title"><?= te('Image descriptions are required') ?></h2>
            <p class="text-small"><?= te('Every attached image needs a description. It is what readers using a screen reader get instead of the picture, and it is also what appears if the image fails to load.') ?></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
