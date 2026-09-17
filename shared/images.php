<?php
/**
 * Image attachment fields shared by the forum, blog and review forms.
 *
 * A member can pick an image from the site library or upload their own
 * (held in memory as a data URI). Every attached image needs a text
 * description, enforced by both JavaScript and PHP.
 */

declare(strict_types=1);

/**
 * Render the image fields. $current is the stored image reference when
 * editing (so "keep the current image" is offered), or '' when creating.
 */
function image_fields_html(array $values, array $errors, string $current = '', string $legend = 'Image'): string
{
    $choice = $values['image-library'] ?? ($current !== '' ? 'keep' : '');
    $previewSrc = $current !== '' ? image_src($current) : gf_base() . 'assets/img/photo-workbench.svg';
    $previewAlt = $current !== '' ? t('The image currently attached.') : t('Preview of the selected image.');

    $out  = '<fieldset class="fieldset"><legend class="fieldset__legend">' . te($legend) . '</legend>';
    $out .= '<div class="form__grid form__grid--two">';

    // Library / keep / none
    $out .= '<p class="field"><label class="field__label" for="image-library">' . te('Choose an image') . '</label>';
    $out .= '<select id="image-library" name="image-library"' . invalid_attrs($errors, 'image-library', 'image-library-hint') . '>';
    if ($current !== '') {
        $out .= '<option value="keep"' . ($choice === 'keep' ? ' selected' : '') . '>' . te('Keep the current image') . '</option>';
    }
    $out .= '<option value=""' . ($choice === '' ? ' selected' : '') . '>' . te('No image') . '</option>';
    foreach (GF_IMAGE_LIBRARY as $path => $desc) {
        $out .= '<option value="' . e($path) . '"' . ($choice === $path ? ' selected' : '') . '>' . e($desc) . '</option>';
    }
    $out .= '</select>' . error_html($errors, 'image-library');
    $out .= '<span class="field__hint" id="image-library-hint">' . te('Pick one of the site\'s images, or upload your own below.') . '</span></p>';

    // Upload
    $out .= '<div class="field"><label class="field__label" for="image-upload">' . te('Or upload a photograph') . '</label>';
    $out .= '<span class="file-field"><img class="file-field__preview" id="image-preview" src="' . e($previewSrc) . '" alt="' . e($previewAlt) . '">';
    $out .= '<input type="file" id="image-upload" name="image-upload" accept="image/jpeg,image/png,image/gif,image/webp" data-preview="#image-preview" data-preview-alt="' . te('Preview of the file you chose.') . '" data-max-bytes="1048576" data-accept="image/jpeg,image/png,image/gif,image/webp"' . invalid_attrs($errors, 'image-upload', 'image-upload-hint') . '></span>';
    $out .= error_html($errors, 'image-upload');
    $out .= '<span class="field__hint" id="image-upload-hint">' . te('JPEG, PNG, GIF or WebP up to 1 MB. An upload replaces the library choice.') . '</span></div>';
    $out .= '</div>';

    // Alt text
    $out .= '<p class="field"><label class="field__label" for="image-alt">' . te('Image description') . '</label>';
    $out .= '<input type="text" id="image-alt" name="image-alt" value="' . e($values['image-alt'] ?? '') . '" data-maxlength="200" data-required-if-file="image-upload"' . invalid_attrs($errors, 'image-alt', 'image-alt-hint') . '>';
    $out .= error_html($errors, 'image-alt');
    $out .= '<span class="field__hint" id="image-alt-hint">' . te('Required whenever an image is attached. Describe what it shows for readers using a screen reader.') . '</span></p>';

    // Caption
    $out .= '<p class="field"><label class="field__label" for="image-caption">' . te('Caption') . '</label>';
    $out .= '<input type="text" id="image-caption" name="image-caption" value="' . e($values['image-caption'] ?? '') . '" data-maxlength="160"' . invalid_attrs($errors, 'image-caption', 'image-caption-hint') . '>';
    $out .= error_html($errors, 'image-caption');
    $out .= '<span class="field__hint" id="image-caption-hint">' . te('Optional, shown beneath the image.') . '</span></p>';

    return $out . '</fieldset>';
}

/** Rules for the image fields, merged into a form's rule set. */
function image_rules(): array
{
    return [
        'image-library' => ['label' => 'Image'],
        'image-alt'     => ['label' => 'Image description', 'max' => 200],
        'image-caption' => ['label' => 'Caption', 'max' => 160],
    ];
}

/**
 * Work out the image to store from the submitted fields. Returns
 * [image, alt, caption] and adds to $errors on a problem.
 */
function resolve_image(array $values, array &$errors, string $current = '', string $currentAlt = ''): array
{
    $choice = $values['image-library'] ?? '';
    $image = '';

    $upload = validate_image_upload('image-upload');
    if (isset($upload['error'])) {
        $errors['image-upload'] = $upload['error'];
    } elseif (isset($upload['data'])) {
        $image = $upload['data'];
    } elseif ($choice === 'keep' && $current !== '') {
        $image = $current;
    } elseif ($choice !== '' && $choice !== 'keep') {
        if (!isset(GF_IMAGE_LIBRARY[$choice])) {
            $errors['image-library'] = t('Choose one of the listed images.');
        } else {
            $image = $choice;
        }
    }

    $alt = trim((string) ($values['image-alt'] ?? ''));
    if ($image !== '' && $alt === '') {
        if ($choice === 'keep' && $currentAlt !== '') {
            $alt = $currentAlt;
        } elseif (isset(GF_IMAGE_LIBRARY[$image])) {
            $alt = GF_IMAGE_LIBRARY[$image];
        } else {
            $errors['image-alt'] = t('Add a description of the image for readers who cannot see it.');
        }
    }
    if ($image === '') {
        $alt = '';
    }
    return [$image, $alt, trim((string) ($values['image-caption'] ?? ''))];
}
