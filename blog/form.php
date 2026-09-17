<?php
/**
 * Blog post form, shared by new-post.php (create) and edit-post.php (update).
 * Expects $values, $errors, $action (URL), $submit_label, and $current_image.
 */

declare(strict_types=1);

?>
          <?= error_summary_html($errors) ?>
          <form class="form" method="post" action="<?= e($action) ?>" enctype="multipart/form-data" data-validate>
            <?= csrf_field() ?>
            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('The post') ?></legend>

              <p class="field">
                <label class="field__label" for="post-title"><?= te('Title') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="post-title" name="post-title" value="<?= e($values['post-title']) ?>" data-required data-minlength="10" data-maxlength="90"<?= invalid_attrs($errors, 'post-title', 'post-title-hint') ?>>
                <?= error_html($errors, 'post-title') ?>
                <span class="field__hint" id="post-title-hint"><?= te('Between 10 and 90 characters. Say what the reader will learn.') ?></span>
              </p>

              <p class="field">
                <label class="field__label" for="post-summary"><?= te('Summary') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <input type="text" id="post-summary" name="post-summary" value="<?= e($values['post-summary']) ?>" data-required data-minlength="20" data-maxlength="200"<?= invalid_attrs($errors, 'post-summary', 'post-summary-hint') ?>>
                <?= error_html($errors, 'post-summary') ?>
                <span class="field__hint" id="post-summary-hint"><?= te('One or two sentences (20 to 200 characters), shown on the preview card in the index.') ?></span>
              </p>

              <p class="field">
                <label class="field__label" for="post-body"><?= te('Post text') ?> <span class="field__required" aria-hidden="true">*</span></label>
                <textarea id="post-body" name="post-body" data-required data-minlength="50" data-maxlength="10000"<?= invalid_attrs($errors, 'post-body', 'post-body-hint') ?>><?= e($values['post-body']) ?></textarea>
                <?= error_html($errors, 'post-body') ?>
                <span class="field__hint" id="post-body-hint"><?= te('Plain text, at least 50 characters. Use a blank line between paragraphs.') ?></span>
              </p>
            </fieldset>

            <?= image_fields_html($values, $errors, $current_image, 'Header image') ?>

            <fieldset class="fieldset">
              <legend class="fieldset__legend"><?= te('Publishing') ?></legend>

              <fieldset class="fieldset">
                <legend class="fieldset__legend"><?= te('Tags') ?> <span class="field__required" aria-hidden="true">*</span></legend>
<?php $first = true; foreach (GF_BLOG_TAGS as $key => $label): ?>
                <p class="field field--inline">
                  <input type="checkbox" id="tag-<?= e($key) ?>" name="post-tags[]" value="<?= e($key) ?>"<?= in_array($key, $values['post-tags'], true) ? ' checked' : '' ?><?= $first ? ' data-required-group data-label="' . te('Tags') . '"' : '' ?><?= $first ? invalid_attrs($errors, 'post-tags', 'post-tags-hint') : '' ?>>
                  <label class="field__label" for="tag-<?= e($key) ?>"><?= te($label) ?></label>
                </p>
<?php $first = false; endforeach; ?>
                <?= error_html($errors, 'post-tags') ?>
                <span class="field__hint" id="post-tags-hint"><?= te('Choose one to three tags.') ?></span>
              </fieldset>

              <div class="form__grid form__grid--two">
                <p class="field">
                  <label class="field__label" for="post-date"><?= te('Publish date') ?> <span class="field__required" aria-hidden="true">*</span></label>
                  <input type="date" id="post-date" name="post-date" value="<?= e($values['post-date']) ?>" data-required data-type="date"<?= invalid_attrs($errors, 'post-date', 'post-date-hint') ?>>
                  <?= error_html($errors, 'post-date') ?>
                  <span class="field__hint" id="post-date-hint"><?= te('Leave as today to publish now.') ?></span>
                </p>
              </div>

              <p class="field field--inline">
                <input type="checkbox" id="post-comments" name="post-comments" value="yes"<?= $values['post-comments'] !== '' ? ' checked' : '' ?>>
                <label class="field__label" for="post-comments"><?= te('Allow comments on this post') ?></label>
              </p>
            </fieldset>

            <div class="form__actions">
              <button type="submit" class="button button--primary"><?= te($submit_label) ?></button>
              <a class="button button--quiet" href="posts.php"><?= te('Cancel') ?></a>
<?php if ($current_image !== null && isset($post)): ?>
              <a class="button button--danger" href="delete-post.php?id=<?= $post['id'] ?>"><?= te('Delete this post') ?></a>
<?php endif; ?>
            </div>
          </form>
