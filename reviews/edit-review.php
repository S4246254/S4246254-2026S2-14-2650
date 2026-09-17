<?php
/** Update a review. Only its author may do this. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$review = review($id ? (int) $id : null);

if (!$review || !owns($review['author'])) {
    http_response_code($review ? 403 : 404);
    $page_title = 'You cannot edit this review';
    $page_description = 'Only the author of a review can edit it.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('You cannot edit this review') . '</h1>';
    if ($review) {
        echo '<p class="page-head__lede">' . te('This review was written by %1$s. Only the account that wrote a review can edit it.', [user_name($review['author'])]) . '</p></div></div>';
        echo '<p><a class="button" href="review.php?id=' . (int) $review['id'] . '">' . te('Back to the review') . '</a>';
        if (!is_logged_in()) {
            echo ' <a class="button button--primary" href="../account.php?return=' . e(rawurlencode('reviews/edit-review.php?id=' . (int) $review['id'])) . '">' . te('Log in') . '</a>';
        }
        echo '</p>';
    } else {
        echo '<p class="page-head__lede">' . te('That review does not exist or has been deleted.') . '</p></div></div>';
        echo '<p><a class="button" href="reviews.php">' . te('Back to all reviews') . '</a></p>';
    }
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$rules = review_rules();
$errors = [];
$values = [
    'review-product'    => $review['product_id'],
    'rating'            => (string) $review['rating'],
    'review-title'      => $review['title'],
    'review-body'       => $review['body'],
    'review-guidelines' => 'yes',
    'image-library'     => $review['image'] !== '' ? 'keep' : '',
    'image-alt'         => $review['image_alt'],
    'image-caption'     => $review['caption'],
];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('edit-review.php?id=' . $review['id']);
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    [$image, $alt, $caption] = resolve_image($values, $errors, $review['image'], $review['image_alt']);

    if (!$errors) {
        $reviews = &collection('reviews');
        $r = &$reviews[$review['id']];
        $r['product_id'] = $values['review-product'];
        $r['rating']     = (int) $values['rating'];
        $r['title']      = $values['review-title'];
        $r['body']       = $values['review-body'];
        $r['image']      = $image;
        $r['image_alt']  = $alt;
        $r['caption']    = $caption;
        $r['updated']    = date('Y-m-d');
        unset($r, $reviews);
        flash('success', t('Your review has been updated.'));
        redirect('review.php?id=' . $review['id']);
    }
}

$action = 'edit-review.php?id=' . $review['id'];
$submit_label = 'Save changes';
$current_image = $review['image'];

$page_title = 'Edit your review';
$page_description = 'Edit a review you wrote.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="reviews.php"><?= te('Reviews') ?></a></li>
          <li><a href="review.php?id=<?= $review['id'] ?>"><?= e(excerpt($review['title'], 40)) ?></a></li>
          <li aria-current="page"><?= te('Edit review') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Edit your review') ?></h1>
          <p class="page-head__lede"><?= te('Written') ?> <?= time_html($review['date']) ?>. <?= te('Changes are shown immediately and the review is marked as updated.') ?></p>
        </div>
      </div>

      <div class="callout callout--primary">
        <h2 class="callout__title"><?= te('You are editing your own review') ?></h2>
        <p><?= te('This review was written by %1$s, the account you are logged in as. Reviews by other members cannot be edited or deleted by you.', [user_name(current_user())]) ?></p>
      </div>

      <div class="layout-aside">
        <div>
<?php require __DIR__ . '/form.php'; ?>
        </div>

        <aside class="layout-aside__side" aria-labelledby="preview-heading">
          <div class="panel">
            <h2 class="panel__title" id="preview-heading"><?= te('Current version') ?></h2>
            <p class="rating"><?= rating_html((float) $review['rating']) ?></p>
            <p class="byline">
              <img class="byline__avatar" src="../<?= e(user_avatar($review['author'])) ?>" alt="">
              <span class="byline__name"><?= e(user_name($review['author'])) ?></span>
              <?= time_html($review['date']) ?>
            </p>
            <p class="text-small text-muted mt-s"><?= te('%1$s people found this review helpful. Editing it keeps those votes.', [number((int) $review['helpful'])]) ?></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
