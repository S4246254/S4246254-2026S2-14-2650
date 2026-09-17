<?php
/**
 * Review form, shared by new-review.php (create) and edit-review.php (update).
 * Expects $values, $errors, $action (URL), $submit_label, $current_image and
 * optionally $review (when editing).
 */

declare(strict_types=1);
?>
          <?= error_summary_html($errors) ?>
          <form class="form" method="post" action="<?= e($action) ?>" enctype="multipart/form-data" data-validate>
            <?= csrf_field() ?>
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('What are you reviewing?') ?></legend>

              <p class="field">
                <label class="field__label" for="review-product"><?= te('Product') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <select id="review-product" name="review-product" data-required<?= invalid_attrs($errors, 'review-product', 'review-product-hint') ?>>
                  <option value=""<?= $values['review-product'] === '' ? ' selected' : '' ?>><?= te('Choose a product') ?></option>
<?php foreach (collection('products') as $pid => $prod): ?>
                  <option value="<?= e($pid) ?>"<?= $values['review-product'] === $pid ? ' selected' : '' ?>><?= e($prod['name']) ?></option>
<?php endforeach; ?>
                </select>
                <?= error_html($errors, 'review-product') ?>
                <span class="field__hint" id="review-product-hint"><?= te('Products you have ordered on this account are marked as verified purchases.') ?></span>
              </p>
            </fieldset>

            <!-- The star rating is a radio group with text labels, so the value
                 is available to a screen reader and can be chosen by keyboard. -->
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Your rating') ?> <span class="field__required" aria-hidden="true">*</span></legend>
              <div class="star-choice">
<?php foreach (GF_RATING_LABELS as $n => $label): ?>
                <span class="star-choice__option">
                  <input type="radio" id="rating-<?= $n ?>" name="rating" value="<?= $n ?>"<?= $values['rating'] === (string) $n ? ' checked' : '' ?><?= $n === 1 ? ' data-required' : '' ?><?= $n === 1 ? invalid_attrs($errors, 'rating', 'rating-hint') : '' ?>>
                  <label for="rating-<?= $n ?>"><?= te($label) ?></label>
                </span>
<?php endforeach; ?>
              </div>
              <?= error_html($errors, 'rating') ?>
              <p class="field__hint" id="rating-hint"><?= te('Required. Half stars are averaged across reviews and cannot be chosen individually.') ?></p>
            </fieldset>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Your review') ?></legend>

              <p class="field">
                <label class="field__label" for="review-title"><?= te('Headline') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="review-title" name="review-title" value="<?= e($values['review-title']) ?>" data-required data-minlength="5" data-maxlength="90"<?= invalid_attrs($errors, 'review-title', 'review-title-hint') ?>>
                <?= error_html($errors, 'review-title') ?>
                <span class="field__hint" id="review-title-hint"><?= te('One line summarising your verdict. This is what appears in the review list.') ?></span>
              </p>

              <p class="field">
                <label class="field__label" for="review-body"><?= te('Full review') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <textarea id="review-body" name="review-body" data-required data-minlength="50" data-maxlength="5000"<?= invalid_attrs($errors, 'review-body', 'review-body-hint') ?>><?= e($values['review-body']) ?></textarea>
                <?= error_html($errors, 'review-body') ?>
                <span class="field__hint" id="review-body-hint"><?= te('At least 50 characters. The most useful reviews say how much you used, what you compared it against, and what you would tell someone before they buy.') ?></span>
              </p>
            </fieldset>

            <?= image_fields_html($values, $errors, $current_image, 'Add a photograph') ?>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Publishing') ?></legend>
              <p class="field field--inline">
                <input type="checkbox" id="review-guidelines" name="review-guidelines" value="yes" data-checked<?= $values['review-guidelines'] !== '' ? ' checked' : '' ?><?= invalid_attrs($errors, 'review-guidelines', 'review-guidelines-hint') ?>>
                <label class="field__label" for="review-guidelines"><?= te('This review is my own experience of the product') ?></label>
              </p>
              <?= error_html($errors, 'review-guidelines') ?>
              <span class="field__hint" id="review-guidelines-hint"><?= te('Required. Reviews written for products you have not used are removed.') ?></span>
            </fieldset>

            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te($submit_label) ?></button>
              <a class="button button--quiet" href="reviews.php"><?= te('Cancel') ?></a>
<?php if (isset($review)): ?>
              <a class="button button--danger" href="delete-review.php?id=<?= $review['id'] ?>"><?= te('Delete this review') ?></a>
<?php endif; ?>
            </div>
          </form>
