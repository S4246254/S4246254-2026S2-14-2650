<?php
/**
 * Locale switch handler. The header's locale toggle posts here; the chosen
 * locale is stored in the PHP session and the visitor is sent back to the
 * page they were on.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!is_post() || !csrf_ok()) {
    redirect('../welcome.php');
}

$tag = filter_input(INPUT_POST, 'locale', FILTER_DEFAULT);
if (is_string($tag)) {
    set_locale($tag);
}

$return = filter_input(INPUT_POST, 'return', FILTER_DEFAULT);
$return = safe_return(is_string($return) ? $return : null, '../welcome.php');

// The return value is the original request URI (an absolute path on this
// host), which is safe to send back to directly.
redirect($return);
