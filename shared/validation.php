<?php
/**
 * Server-side form validation shared by every module.
 *
 * Each page describes its form as a rule set and calls validate(). The rules
 * mirror the data-* attributes that assets/js/validate.js reads, so the
 * browser and the server enforce the same constraints; the server is the one
 * that counts. No HTML validation attributes are used anywhere on the site.
 *
 * A rule set looks like:
 *   'title' => ['label' => 'Thread title', 'required' => true, 'min' => 10, 'max' => 90]
 *
 * Supported keys: label, required, min, max (string length), type
 * (int|email|date|card|expiry|csc|postcode|phone|username), int_min, int_max,
 * in (allowed values), match (regex), same (name of another field that must
 * be equal), checked (checkbox must be ticked).
 */

declare(strict_types=1);

/** Read every declared field from $_POST, trimmed, as strings. */
function form_values(array $rules): array
{
    $values = [];
    foreach ($rules as $name => $rule) {
        $raw = filter_input(INPUT_POST, $name, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
        $values[$name] = is_string($raw) ? trim($raw) : '';
    }
    return $values;
}

/** Validate values against rules. Returns [field => message] for failures. */
function validate(array $rules, array $values): array
{
    $errors = [];
    foreach ($rules as $name => $rule) {
        $value = (string) ($values[$name] ?? '');
        $label = t($rule['label'] ?? ucfirst(str_replace(['-', '_'], ' ', $name)));

        if (!empty($rule['checked'])) {
            if ($value === '') {
                $errors[$name] = t('You need to tick "%1$s" to continue.', [$label]);
            }
            continue;
        }

        if ($value === '') {
            if (!empty($rule['required'])) {
                $errors[$name] = t('%1$s is required.', [$label]);
            }
            continue; // optional and empty: nothing more to check
        }

        $len = mb_strlen($value);
        if (isset($rule['min']) && $len < $rule['min']) {
            $errors[$name] = t('%1$s must be at least %2$s characters.', [$label, $rule['min']]);
            continue;
        }
        if (isset($rule['max']) && $len > $rule['max']) {
            $errors[$name] = t('%1$s must be no more than %2$s characters.', [$label, $rule['max']]);
            continue;
        }
        if (isset($rule['in']) && !in_array($value, $rule['in'], true)) {
            $errors[$name] = t('Choose one of the listed options for %1$s.', [$label]);
            continue;
        }
        if (isset($rule['match']) && !preg_match($rule['match'], $value)) {
            $errors[$name] = $rule['match_message'] ?? t('%1$s is not in the expected format.', [$label]);
            continue;
        }
        if (isset($rule['same']) && $value !== (string) ($values[$rule['same']] ?? '')) {
            $errors[$name] = t('%1$s does not match.', [$label]);
            continue;
        }

        switch ($rule['type'] ?? 'text') {
            case 'int':
                if (!preg_match('/^-?\d+$/', $value)) {
                    $errors[$name] = t('%1$s must be a whole number.', [$label]);
                    break;
                }
                $n = (int) $value;
                if (isset($rule['int_min']) && $n < $rule['int_min']) {
                    $errors[$name] = t('%1$s must be at least %2$s.', [$label, $rule['int_min']]);
                } elseif (isset($rule['int_max']) && $n > $rule['int_max']) {
                    $errors[$name] = t('%1$s must be no more than %2$s.', [$label, $rule['int_max']]);
                }
                break;

            case 'email':
                if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[$name] = t('Enter a valid email address, for example name@example.com.');
                }
                break;

            case 'date':
                $d = DateTime::createFromFormat('Y-m-d', $value);
                if (!$d || $d->format('Y-m-d') !== $value) {
                    $errors[$name] = t('%1$s must be a date in the form YYYY-MM-DD.', [$label]);
                }
                break;

            case 'username':
                if (!preg_match('/^[a-z][a-z0-9_]{2,19}$/i', $value)) {
                    $errors[$name] = t('A username is 3 to 20 letters, digits or underscores and starts with a letter.');
                }
                break;

            case 'postcode':
                if (!preg_match('/^\d{4}$/', $value)) {
                    $errors[$name] = t('A postcode is four digits, for example 3000.');
                }
                break;

            case 'phone':
                if (!preg_match('/^\+?[\d\s()-]{8,20}$/', $value)) {
                    $errors[$name] = t('Enter a phone number using digits, spaces and an optional leading +.');
                }
                break;

            case 'card':
                $digits = preg_replace('/\D/', '', $value);
                if (strlen($digits) < 13 || strlen($digits) > 19 || !luhn_ok($digits)) {
                    $errors[$name] = t('Enter a valid card number. The test number 4111 1111 1111 1111 works.');
                }
                break;

            case 'expiry':
                if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $value, $m)) {
                    $errors[$name] = t('Expiry must be MM/YY, for example 04/29.');
                    break;
                }
                $expiry = mktime(0, 0, 0, (int) $m[1] + 1, 1, 2000 + (int) $m[2]);
                if ($expiry < time()) {
                    $errors[$name] = t('This card has expired.');
                }
                break;

            case 'csc':
                if (!preg_match('/^\d{3,4}$/', $value)) {
                    $errors[$name] = t('The security code is the three or four digits on the card.');
                }
                break;
        }
    }
    return $errors;
}

/** Luhn checksum for card numbers. */
function luhn_ok(string $digits): bool
{
    $sum = 0;
    $alt = false;
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $n = (int) $digits[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    return $sum % 10 === 0;
}

/**
 * Validate an uploaded image from $_FILES[$field]. Returns
 * ['data' => data-uri] on success, ['error' => message] on failure, or []
 * when no file was chosen. The image is kept in memory as a data URI because
 * this assessment stores nothing on disk.
 */
function validate_image_upload(string $field, int $maxBytes = 1048576): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return [];
    }
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [];
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['error' => t('The image could not be uploaded. Try a smaller file.')];
    }
    if ((int) $f['size'] > $maxBytes) {
        return ['error' => t('Images must be %1$s MB or smaller.', [round($maxBytes / 1048576, 1)])];
    }
    $info = @getimagesize((string) $f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpeg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!$info || !isset($allowed[$info['mime']])) {
        return ['error' => t('Attach a JPEG, PNG, GIF or WebP image.')];
    }
    $bytes = file_get_contents((string) $f['tmp_name']);
    if ($bytes === false) {
        return ['error' => t('The image could not be read.')];
    }
    return ['data' => 'data:' . $info['mime'] . ';base64,' . base64_encode($bytes)];
}

/* ---------------------------------------------------------------------------
   Rendering helpers for forms
   ------------------------------------------------------------------------ */

/** Attributes to put on a control that failed validation. */
function invalid_attrs(array $errors, string $name, string $hintId = ''): string
{
    if (!isset($errors[$name])) {
        return $hintId ? ' aria-describedby="' . e($hintId) . '"' : '';
    }
    $ids = trim($name . '-error ' . $hintId);
    return ' aria-invalid="true" aria-describedby="' . e($ids) . '"';
}

/** Inline error message element for a control. Always rendered so JS can fill it. */
function error_html(array $errors, string $name): string
{
    $msg = $errors[$name] ?? '';
    $hidden = $msg === '' ? ' hidden' : '';
    return '<span class="field__error" id="' . e($name) . '-error"' . $hidden . '>' . e($msg) . '</span>';
}

/** Summary of every error, linked to the failing control, shown above the form. */
function error_summary_html(array $errors): string
{
    $out = '<div class="form-errors" id="form-errors" role="alert" tabindex="-1"' . ($errors ? '' : ' hidden') . '>';
    $out .= '<h2 class="form-errors__title">' . te('Please fix the following before continuing') . '</h2><ul class="form-errors__list">';
    foreach ($errors as $name => $msg) {
        $out .= '<li><a href="#' . e($name) . '">' . e($msg) . '</a></li>';
    }
    return $out . '</ul></div>';
}
