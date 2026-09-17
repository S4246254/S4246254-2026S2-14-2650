<?php
/** Delete a review. Only its author may do this. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$review = review($id ? (int) $id : null);

if (!$review || !owns($review['author'])) {
    http_response_code($review ? 403 : 404);
    $page_title = 'You cannot delete this review';
    $page_description = 'Only the author of a review can delete it.';
    require __DIR__ . '/../shared/header.php';
    echo '<div class="page-head"><div class="page-head__text"><h1>' . te('You cannot delete this review') . '</h1>';
    if ($review) {
        echo '<p class="page-head__lede">' . te('This review was written by %1$s. Only the account that wrote a review can delete it.', [user_name($review['author'])]) . '</p></div></div>';
        echo '<p><a class="button" href="review.php?id=' . (int) $review['id'] . '">' . te('Back to the review') . '</a></p>';
    } else {
        echo '<p class="page-head__lede">' . te('That review does not exist or has been deleted.') . '</p></div></div>';
        echo '<p><a class="button" href="reviews.php">' . te('Back to all reviews') . '</a></p>';
    }
    require __DIR__ . '/../shared/footer.php';
    exit;
}

$p = product($review['product_id']);
$rules = ['delete-confirm' => ['label' => 'I understand this permanently deletes the review', 'checked' => true]];
$errors = [];

if (is_post()) {
    if (!csrf_ok()) {
        flash('danger', t('Your session has expired. Please try again.'));
        redirect('delete-review.php?id=' . $review['id']);
    }
    $values = form_values($rules);
    $errors = validate($rules, $values);
    if (!$errors) {
        $reviews = &collection('reviews');
        unset($reviews[$review['id']]);
        unset($reviews);
        flash('success', t('Your review "%1$s" has been deleted.', [$review['title']]));
        redirect('reviews.php');
    }
}

$page_title = 'Delete your review?';
$page_description = 'Confirm the deletion of a review you wrote.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li><a href="reviews.php"><?= te('Reviews') ?></a></li>
          <li><a href="review.php?id=<?= $review['id'] ?>"><?= e(excerpt($review['title'], 40)) ?></a></li>
          <li aria-current="page"><?= te('Delete review') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Delete your review?') ?></h1>
          <p class="page-head__lede"><?= te('This cannot be undone from the site. The product\'s average rating will be recalculated without it.') ?></p>
        </div>
      </div>

      <section class="section" aria-labelledby="target-heading">
        <div class="section__head">
          <h2 id="target-heading"><?= te('The review you are about to delete') ?></h2>
        </div>

        <article class="comment post--mine">
          <p class="rating"><?= rating_html((float) $review['rating']) ?></p>
          <h3 class="card__title"><?= e($review['title']) ?></h3>
          <p class="card__meta">
            <span><?= e($p['name'] ?? $review['product_id']) ?></span>
            <span class="badge badge--primary"><?= te('Your review') ?></span>
          </p>
          <p class="byline">
            <img class="byline__avatar" src="../<?= e(user_avatar($review['author'])) ?>" alt="">
            <span class="byline__name"><?= e(user_name($review['author'])) ?></span>
            <?= time_html($review['date']) ?>
          </p>
          <div class="prose"><?= paragraphs($review['body']) ?></div>
<?php if ($review['image'] !== ''): ?>
          <figure class="post__figure">
            <img class="post__image" src="<?= e(image_src($review['image'])) ?>" alt="<?= e($review['image_alt']) ?>">
            <figcaption><?= te('The attached photograph will be deleted with the review.') ?></figcaption>
          </figure>
<?php endif; ?>
        </article>
      </section>

      <section class="section" aria-labelledby="confirm-heading">
        <div class="section__head">
          <h2 id="confirm-heading"><?= te('Confirm deletion') ?></h2>
        </div>

        <?= error_summary_html($errors) ?>
        <form class="form" method="post" action="delete-review.php?id=<?= $review['id'] ?>" data-validate>
          <?= csrf_field() ?>
          <fieldset class="fieldset">
            <legend class="fieldset__legend"><?= te('Are you sure?') ?></legend>
            <p class="field field--inline">
              <input type="checkbox" id="delete-confirm" name="delete-confirm" value="yes" data-checked<?= invalid_attrs($errors, 'delete-confirm', 'delete-confirm-hint') ?>>
              <label class="field__label" for="delete-confirm"><?= te('I understand this permanently deletes the review') ?></label>
            </p>
            <?= error_html($errors, 'delete-confirm') ?>
            <span class="field__hint" id="delete-confirm-hint"><?= te('Required.') ?></span>
          </fieldset>

          <div class="form__actions">
            <button type="submit" class="button button--danger-solid"><?= te('Delete review permanently') ?></button>
            <a class="button" href="edit-review.php?id=<?= $review['id'] ?>"><?= te('Edit it instead') ?></a>
            <a class="button button--quiet" href="review.php?id=<?= $review['id'] ?>"><?= te('Cancel and go back') ?></a>
          </div>
        </form>
      </section>

<?php require __DIR__ . '/../shared/footer.php'; ?>
