<?php
/**
 * Grimdark Forge — shared bootstrap.
 *
 * Included first by every page. Starts the session, loads the in-memory data
 * store, resolves the signed-in user and active locale, and defines the small
 * set of helpers the modules share (escaping, translation, locale-aware
 * formatting, CSRF, redirects).
 *
 * Nothing here is tied to a particular server: paths are computed relative to
 * this file, so the site can be dropped into any web root.
 */

declare(strict_types=1);

date_default_timezone_set('Australia/Melbourne');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('grimdarkforge');
    session_start();
}

/* Minimal fallbacks so the site still runs on a PHP build without mbstring. */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s): int { return (int) preg_match_all('/./us', $s); }
    function mb_stripos(string $h, string $n, int $o = 0) { return stripos($h, $n, $o); }
    function mb_strrpos(string $h, string $n, int $o = 0) { return strrpos($h, $n, $o); }
    function mb_substr(string $s, int $start, ?int $len = null): string
    {
        preg_match_all('/./us', $s, $m);
        return implode('', $len === null ? array_slice($m[0], $start) : array_slice($m[0], $start, $len));
    }
    function mb_strtolower(string $s): string { return strtolower($s); }
}

require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/store.php';
require_once __DIR__ . '/validation.php';

/* ---------------------------------------------------------------------------
   Paths
   ------------------------------------------------------------------------ */

/** Root of the site on disk (the folder containing welcome.php). */
define('GF_ROOT', dirname(__DIR__));

/**
 * Relative prefix from the executing script back to the site root, e.g. ''
 * at the root and '../' inside a module folder. Computed from the file
 * system, so it holds wherever the site is hosted.
 */
function gf_base(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', (string) realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
        $root   = str_replace('\\', '/', (string) realpath(GF_ROOT));
        $depth  = substr_count($script, '/') - substr_count($root, '/');
        $base   = str_repeat('../', max(0, $depth));
    }
    return $base;
}

/** Name of the current script without its directory, e.g. "boards.php". */
function gf_script_name(): string
{
    return basename((string) $_SERVER['SCRIPT_NAME']);
}

/** Directory of the current script relative to the root ('' | 'forum' | ...). */
function gf_module(): string
{
    $script = str_replace('\\', '/', (string) realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
    $root   = str_replace('\\', '/', (string) realpath(GF_ROOT));
    return trim(substr($script, strlen($root)), '/');
}

/* ---------------------------------------------------------------------------
   Output escaping
   ------------------------------------------------------------------------ */

/** Escape a value for insertion into HTML text or an attribute. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/**
 * Render plain text as HTML paragraphs: escaped, with blank lines splitting
 * paragraphs. Used for every piece of user-written body text.
 */
function paragraphs(string $text): string
{
    $blocks = preg_split('/\R{2,}/', trim($text)) ?: [];
    $out = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $out .= '<p>' . nl2br(e($block)) . "</p>\n";
    }
    return $out;
}

/* ---------------------------------------------------------------------------
   In-memory data store: seeded from data.php, shared by every visitor
   (see store.php for how it survives between requests without a database).
   ------------------------------------------------------------------------ */

/** Return a reference to the whole in-memory store. */
function &db(): array
{
    return gf_store();
}

/** Return a reference to one collection ('users', 'products', 'threads', ...). */
function &collection(string $name): array
{
    $db = &gf_store();
    if (!isset($db[$name])) {
        $db[$name] = [];
    }
    return $db[$name];
}

/** Allocate the next integer id for a collection. */
function next_id(string $name): int
{
    $db = &db();
    $db['next_id'][$name] = ($db['next_id'][$name] ?? 0) + 1;
    return $db['next_id'][$name];
}

/** Throw away every change and reload the seed data. */
function reset_demo_data(): void
{
    gf_store_replace(gf_seed_data());
}

/* ---------------------------------------------------------------------------
   Users and login
   ------------------------------------------------------------------------ */

/** Username of the signed-in user, or null. */
function current_user(): ?string
{
    return isset($_SESSION['user']) ? (string) $_SESSION['user'] : null;
}

/** Whether a user is signed in. */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/** The user record for a username, or null. */
function user(?string $username): ?array
{
    if ($username === null) {
        return null;
    }
    $users = collection('users');
    return $users[$username] ?? null;
}

/** Display name for a username (falls back to the username itself). */
function user_name(?string $username): string
{
    $u = user($username);
    return $u ? (string) $u['name'] : (string) $username;
}

/** Avatar path (relative to the root) for a username. */
function user_avatar(?string $username): string
{
    $u = user($username);
    return $u && !empty($u['avatar']) ? (string) $u['avatar'] : 'assets/img/avatar-default.svg';
}

/** Does the signed-in user own a record with this author? */
function owns(?string $author): bool
{
    return $author !== null && current_user() !== null && current_user() === $author;
}

/**
 * Sign in. Unknown usernames are created on the fly (the brief asks only for
 * a username, no password). A guest cart is merged into the user's cart.
 */
function login(string $username): void
{
    $users = &collection('users');
    if (!isset($users[$username])) {
        $users[$username] = [
            'name'   => $username,
            'avatar' => 'assets/img/avatar-default.svg',
            'joined' => date('Y-m-d'),
            'bio'    => '',
            'email'  => $username . '@example.com',
        ];
    }
    $_SESSION['user'] = $username;
    gf_merge_guest_cart($username);
}

/** Sign out. The cart stays with the user record it was saved against. */
function logout(): void
{
    unset($_SESSION['user']);
}

/* ---------------------------------------------------------------------------
   Locale
   ------------------------------------------------------------------------ */

/** The active locale tag, e.g. 'en-AU'. */
function locale(): string
{
    $tag = $_SESSION['locale'] ?? GF_DEFAULT_LOCALE;
    return isset(GF_LOCALES[$tag]) ? $tag : GF_DEFAULT_LOCALE;
}

/** Change the active locale (only to a supported one). */
function set_locale(string $tag): bool
{
    if (!isset(GF_LOCALES[$tag])) {
        return false;
    }
    $_SESSION['locale'] = $tag;
    return true;
}

/**
 * Translate a UI string. The English (en-AU) text is the key; other locales
 * supply a dictionary in i18n.php. Positional arguments are substituted with
 * vsprintf, so "%1$s of %2$s" works in any word order.
 */
function t(string $text, array $args = []): string
{
    $dict = gf_dictionary(locale());
    $out = $dict[$text] ?? $text;
    if ($args) {
        $out = vsprintf($out, $args);
    }
    return $out;
}

/** Translate and escape in one step for templates. */
function te(string $text, array $args = []): string
{
    return e(t($text, $args));
}

/* ---------------------------------------------------------------------------
   Locale-aware formatting. Every date, price and count on the site goes
   through these. The output is wrapped so that assets/js/site.js can re-format
   it client-side with Intl, which keeps server and browser output identical.
   ------------------------------------------------------------------------ */

/** Format integer cents as a currency string for the active locale. */
function money(int $cents, string $currency = 'AUD'): string
{
    $tag = locale();
    if (class_exists('NumberFormatter')) {
        $nf = new NumberFormatter(str_replace('-', '_', $tag), NumberFormatter::CURRENCY);
        $out = $nf->formatCurrency($cents / 100, $currency);
        if ($out !== false) {
            return $out;
        }
    }
    // Manual fallback for servers without the intl extension.
    $conf = GF_LOCALES[$tag];
    $amount = number_format($cents / 100, 2, $conf['decimal'], $conf['group']);
    $symbol = $conf['currency_symbol'][$currency] ?? $currency;
    return $conf['currency_after'] ? $amount . "\u{00A0}" . $symbol : $symbol . $amount;
}

/** Money wrapped in a span the client can re-format. */
function money_html(int $cents, string $currency = 'AUD'): string
{
    return '<span data-money="' . $cents . '" data-currency="' . e($currency) . '">' . e(money($cents, $currency)) . '</span>';
}

/** Format an integer or float count (e.g. views) with locale separators. */
function number(float $value, int $decimals = 0): string
{
    $tag = locale();
    if (class_exists('NumberFormatter')) {
        $nf = new NumberFormatter(str_replace('-', '_', $tag), NumberFormatter::DECIMAL);
        $nf->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $nf->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        $out = $nf->format($value);
        if ($out !== false) {
            return $out;
        }
    }
    $conf = GF_LOCALES[$tag];
    return number_format($value, $decimals, $conf['decimal'], $conf['group']);
}

/** Number wrapped for client-side re-formatting. */
function number_html(float $value, int $decimals = 0): string
{
    return '<span data-number="' . e((string) $value) . '" data-decimals="' . $decimals . '">' . e(number($value, $decimals)) . '</span>';
}

/**
 * Format an ISO 8601 date or date-time for the active locale.
 * $style is 'date' (13 August 2026) or 'datetime' (13 August 2026 at 9:40 pm).
 */
function fmt_date(string $iso, string $style = 'date'): string
{
    $ts = strtotime($iso);
    if ($ts === false) {
        return $iso;
    }
    $tag = locale();
    if (class_exists('IntlDateFormatter')) {
        $df = new IntlDateFormatter(
            str_replace('-', '_', $tag),
            IntlDateFormatter::LONG,
            $style === 'datetime' ? IntlDateFormatter::SHORT : IntlDateFormatter::NONE
        );
        $out = $df->format($ts);
        if ($out !== false) {
            return $out;
        }
    }
    $conf = GF_LOCALES[$tag];
    $month = $conf['months'][(int) date('n', $ts) - 1];
    $date = str_replace(['{d}', '{month}', '{y}'], [date('j', $ts), $month, date('Y', $ts)], $conf['date_pattern']);
    if ($style !== 'datetime') {
        return $date;
    }
    $time = $conf['hour24'] ? date('H:i', $ts) : date('g:i a', $ts);
    return str_replace(['{date}', '{time}'], [$date, $time], $conf['datetime_pattern']);
}

/** A <time> element the client can re-format. */
function time_html(string $iso, string $style = 'date'): string
{
    $attrIso = $style === 'datetime' ? $iso : substr($iso, 0, 10);
    return '<time datetime="' . e($attrIso) . '" data-format="' . e($style) . '">' . e(fmt_date($iso, $style)) . '</time>';
}

/** Current time as an ISO string, the format every timestamp is stored in. */
function now_iso(): string
{
    return date('Y-m-d\TH:i:s');
}

/* ---------------------------------------------------------------------------
   Requests, CSRF, redirects and flash messages
   ------------------------------------------------------------------------ */

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Per-session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/** Hidden input carrying the CSRF token, for every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** True when the submitted CSRF token matches the session's. */
function csrf_ok(): bool
{
    $sent = filter_input(INPUT_POST, 'csrf', FILTER_DEFAULT);
    return is_string($sent) && hash_equals(csrf_token(), $sent);
}

/** Redirect to a path relative to the current script and stop. */
function redirect(string $to): void
{
    header('Location: ' . $to, true, 303);
    exit;
}

/** Queue a one-time status message for the next page. */
function flash(string $kind, string $message): void
{
    $_SESSION['flash'][] = ['kind' => $kind, 'message' => $message];
}

/** Take (and clear) queued flash messages. */
function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** A safe same-site return path from a form field, or a default. */
function safe_return(?string $candidate, string $default): string
{
    if (!is_string($candidate) || $candidate === '') {
        return $default;
    }
    // Only allow relative paths within the site: no scheme, no protocol-relative.
    if (preg_match('#^(?:[a-z]+:)?//#i', $candidate) || strpos($candidate, "\n") !== false) {
        return $default;
    }
    return $candidate;
}

/* ---------------------------------------------------------------------------
   Pagination
   ------------------------------------------------------------------------ */

/**
 * Slice a list for the requested page. Returns [items, page, pages, total].
 */
function paginate(array $items, int $page, int $perPage): array
{
    $total = count($items);
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    $slice = array_slice($items, ($page - 1) * $perPage, $perPage);
    return [$slice, $page, $pages, $total];
}

/** Query string for the current filters with one key overridden. */
function query_with(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, function ($v) {
        return $v !== '' && $v !== null;
    });
    return $params ? '?' . http_build_query($params) : '';
}

/* ---------------------------------------------------------------------------
   Images (held in memory as data URIs, or chosen from the site library)
   ------------------------------------------------------------------------ */

/** Site images a member can attach without uploading. */
const GF_IMAGE_LIBRARY = [
    'assets/img/photo-workbench.svg'      => 'A painting workbench with brushes in a jar, open paint pots and a desk lamp.',
    'assets/img/photo-highlights.svg'     => 'A close-up of a miniature\'s shoulder plate with a fine edge highlight along the rim.',
    'assets/img/photo-basing.svg'         => 'Three finished bases side by side showing gravel, static grass and a broken paving slab.',
    'assets/img/photo-primer.svg'         => 'A miniature primed in flat grey, standing on a cork block.',
    'assets/img/photo-army.svg'           => 'A rank of eight painted infantry miniatures photographed from the front.',
    'assets/img/photo-table.svg'          => 'A gaming table seen from above with terrain laid out and two forces deployed.',
    'assets/img/photo-sentinel-scale.svg' => 'An assembled Sentinel walker beside three infantry miniatures for scale.',
    'assets/img/photo-sentinel-sprue.svg' => 'An unbuilt plastic sprue with leg, torso and weapon components attached.',
    'assets/img/kit-sentinel.svg'         => 'An armoured bipedal walker miniature with a slab shield and long-barrelled cannon.',
    'assets/img/kit-warden.svg'           => 'A broad-shouldered infantry miniature in layered plate armour holding a two-handed maul.',
    'assets/img/kit-scarab.svg'           => 'Three small six-legged mechanical constructs mounted on a single scenic base.',
    'assets/img/paint-oxide.svg'          => 'A hexagonal paint pot with a rust-brown label beside a swatch card.',
    'assets/img/paint-bone.svg'           => 'A hexagonal paint pot with a pale bone-coloured label beside a swatch card.',
    'assets/img/brush-set.svg'            => 'Three wooden-handled brushes laid side by side in descending size.',
    'assets/img/terrain-ruins.svg'        => 'A ruined stone wall section with a broken archway and rubble at its base.',
    'assets/img/rulebook.svg'             => 'A closed hardback rulebook with an embossed anvil emblem on the cover.',
];

/** Resolve a stored image reference to a src attribute value. */
function image_src(?string $ref): string
{
    if ($ref === null || $ref === '') {
        return '';
    }
    if (strpos($ref, 'data:') === 0) {
        return $ref;
    }
    return gf_base() . $ref;
}

/* ---------------------------------------------------------------------------
   Shopping cart storage (shared because the header shows the item count)
   ------------------------------------------------------------------------ */

/** Key under which the current visitor's cart is stored: the username, or a per-session guest key. */
function cart_key(): string
{
    return current_user() ?? '_guest_' . session_id();
}

/** Reference to the current visitor's cart record. */
function &cart(): array
{
    $carts = &collection('carts');
    $key = cart_key();
    if (!isset($carts[$key])) {
        $carts[$key] = ['items' => [], 'promo' => null];
    }
    return $carts[$key];
}

/** Total number of units across all cart lines. */
function cart_count(): int
{
    $c = cart();
    $n = 0;
    foreach ($c['items'] as $item) {
        $n += (int) $item['quantity'];
    }
    return $n;
}

/** On login, fold the anonymous cart into the user's saved cart (quantities are summed). */
function gf_merge_guest_cart(string $username): void
{
    $carts = &collection('carts');
    $guestKey = '_guest_' . session_id();
    if (empty($carts[$guestKey]['items'])) {
        return;
    }
    if (!isset($carts[$username])) {
        $carts[$username] = ['items' => [], 'promo' => null];
    }
    foreach ($carts[$guestKey]['items'] as $line) {
        $merged = false;
        foreach ($carts[$username]['items'] as &$existing) {
            if ($existing['product_id'] === $line['product_id'] && $existing['variant'] === $line['variant']) {
                $existing['quantity'] = min(99, $existing['quantity'] + $line['quantity']);
                $merged = true;
                break;
            }
        }
        unset($existing);
        if (!$merged) {
            $line['id'] = next_id('cart_items');
            $carts[$username]['items'][] = $line;
        }
    }
    unset($carts[$guestKey]);
}

/* ---------------------------------------------------------------------------
   Module libraries. Loaded for every page because the shell and the welcome
   page draw on all four modules.
   ------------------------------------------------------------------------ */
require_once GF_ROOT . '/shop/lib.php';
require_once GF_ROOT . '/forum/lib.php';
require_once GF_ROOT . '/blog/lib.php';
require_once GF_ROOT . '/reviews/lib.php';

/** Contribution counts for an account card. */
function gf_user_stats(string $username): array
{
    $posts = 0;
    foreach (collection('posts') as $p) {
        if ($p['author'] === $username && empty($p['deleted'])) {
            $posts++;
        }
    }
    $blog = 0;
    foreach (collection('blog') as $b) {
        if ($b['author'] === $username) {
            $blog++;
        }
    }
    $reviews = 0;
    foreach (collection('reviews') as $r) {
        if ($r['author'] === $username) {
            $reviews++;
        }
    }
    return ['posts' => $posts, 'blog' => $blog, 'reviews' => $reviews];
}

/** The user's five most recent contributions across the modules. */
function gf_user_activity(string $username): array
{
    $items = [];
    foreach (collection('posts') as $id => $p) {
        if ($p['author'] === $username && empty($p['deleted'])) {
            $th = thread((int) $p['thread_id']);
            $items[] = [
                'when' => $p['created'],
                'href' => 'forum/thread.php?id=' . (int) $p['thread_id'] . '#post-' . $id,
                'text' => $p['parent_id'] === null
                    ? t('Started "%1$s"', [$th['title'] ?? ''])
                    : t('Replied in "%1$s"', [$th['title'] ?? '']),
            ];
        }
    }
    foreach (collection('blog') as $id => $b) {
        if ($b['author'] === $username) {
            $items[] = ['when' => $b['date'], 'href' => 'blog/post.php?id=' . $id, 'text' => t('Published "%1$s"', [$b['title']])];
        }
    }
    foreach (collection('reviews') as $id => $r) {
        if ($r['author'] === $username) {
            $p = product($r['product_id']);
            $items[] = ['when' => $r['date'], 'href' => 'reviews/review.php?id=' . $id, 'text' => t('Reviewed the %1$s', [$p['name'] ?? ''])];
        }
    }
    usort($items, function ($a, $b) {
        return strcmp($b['when'], $a['when']);
    });
    return array_slice($items, 0, 5);
}
require_once __DIR__ . '/images.php';
