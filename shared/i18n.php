<?php
/**
 * Locale configuration and UI translations.
 *
 * en-AU is the source language: every UI string in the templates is written
 * in English and used as its own key. gf_dictionary() returns the map for a
 * locale; anything missing falls back to the English key, so an incomplete
 * translation degrades to English rather than to a blank.
 *
 * The formatting tables are only used when the PHP intl extension is absent;
 * with intl present (and always in the browser, via Intl) the locale data
 * comes from ICU/CLDR instead.
 */

declare(strict_types=1);

const GF_DEFAULT_LOCALE = 'en-AU';

const GF_LOCALES = [
    'en-AU' => [
        'name'             => 'English (Australia)',
        'short'            => 'English',
        'decimal'          => '.',
        'group'            => ',',
        'currency_symbol'  => ['AUD' => '$'],
        'currency_after'   => false,
        'months'           => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'date_pattern'     => '{d} {month} {y}',
        'datetime_pattern' => '{date} at {time}',
        'hour24'           => false,
    ],
    'de-DE' => [
        'name'             => 'Deutsch (Deutschland)',
        'short'            => 'Deutsch',
        'decimal'          => ',',
        'group'            => '.',
        'currency_symbol'  => ['AUD' => 'AU$'],
        'currency_after'   => true,
        'months'           => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
        'date_pattern'     => '{d}. {month} {y}',
        'datetime_pattern' => '{date} um {time}',
        'hour24'           => true,
    ],
];

/** Dictionary for a locale: English key => translated string. */
function gf_dictionary(string $tag): array
{
    static $cache = [];
    if (isset($cache[$tag])) {
        return $cache[$tag];
    }
    $file = __DIR__ . '/lang/' . $tag . '.php';
    $cache[$tag] = is_file($file) ? (require $file) : [];
    return $cache[$tag];
}
