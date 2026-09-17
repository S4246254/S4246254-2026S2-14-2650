<?php
/** Create a review. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

if (!is_logged_in()) {
    flash('danger', t('Log in to write a review.'));
    redirect('../account.php?return=' . rawurlencode('reviews/new-review.php'));
}

$rules = review_rules();
$errors = [];
$values = array_fill_keys(array_keys($rules), '');
$preset = filter_input(INPUT_GET, 'product', FILTER_DEFAULT);
if (is_string($preset) && product($preset)) {
    $values['review-product'] = $preset;
}

/** Has this account ordered the product? Marks the review as a verified purchase. */
function bought_on(string $productId): ?string
{
    foreach (collection('orders') as $o) {
        if ($o['user'] !== current_user()) {
            continue;
        }
        foreach ($o['items'] as $it) {
            if ($it['product_id'] === $productId) {
                return substr($o['placed'], 0, 10);
            }
        }
    }
    return null;
}

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('new-review.php');
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    [$image, $alt, $caption] = resolve_image($values, $errors);

    if (!$errors) {
        $reviews = &collection('reviews');
        $newId = next_id('reviews');
        $reviews[$newId] = [
            'product_id' => $values['review-product'],
            'author'     => current_user(),
            'rating'     => (int) $values['rating'],
            'date'       => date('Y-m-d'),
            'bought'     => bought_on($values['review-product']),
            'helpful'    => 0,
            'title'      => $values['review-title'],
            'body'       => $values['review-body'],
            'image'      => $image,
            'image_alt'  => $alt,
            'caption'    => $caption,
        ];
        unset($reviews);
        flash('success', t('Your review has been published.'));
        redirect('review.php?id=' . $newId);
    }
}

$action = 'new-review.php';
$submit_label = 'Publish review';
$current_image = '';

$page_title = 'Write a review';
$page_description = 'Write a review and star rating for a Grimdark Forge product.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="reviews.php"><?= te('Reviews') ?></a></li>
          <li aria-current="page"><?= te('Write a review') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Write a review') ?></h1>
          <p class="page-head__lede"><?= te('Reviewing as %1$s. Your review appears in the list immediately.', [user_name(current_user())]) ?></p>
        </div>
      </div>

      <div class="layout-aside">
        <div>
<?php require __DIR__ . '/form.php'; ?>
        </div>

        <aside class="layout-aside__side" aria-labelledby="tips-heading">
          <div class="panel">
            <h2 class="panel__title" id="tips-heading"><?= te('What makes a review useful') ?></h2>
            <div class="prose">
              <ul>
                <li><?= te('Say how much you used and over how long.') ?></li>
                <li><?= te('Compare it to something else you have tried.') ?></li>
                <li><?= te('Name the thing that nearly stopped you buying it.') ?></li>
                <li><?= te('Rate the product, not the delivery.') ?></li>
              </ul>
            </div>
          </div>

          <div class="callout callout--primary">
            <h2 class="callout__title"><?= te('You can edit this later') ?></h2>
            <p class="text-small"><?= te('Reviews you write stay editable from the review page. You cannot edit or delete reviews written by anyone else.') ?></p>
          </div>
        </aside>
      </div>

<?php require __DIR__ . '/../shared/footer.php'; ?>
