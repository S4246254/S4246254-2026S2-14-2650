<?php
/**
 * Server-wide in-memory data store, shared by every visitor.
 *
 * The site keeps its data in PHP associative arrays rather than a database
 * (that arrives in Assessment Task 3). Because PHP starts fresh on every
 * request, the arrays have to be parked somewhere between requests so that
 * one visitor's new post is visible to the next visitor:
 *
 *   1. APCu shared memory, when the server has the extension — genuinely
 *      in memory, cleared whenever the web server restarts.
 *   2. Otherwise a single serialised scratch file in the system temp
 *      directory, guarded by an exclusive lock so concurrent requests cannot
 *      lose each other's changes. It is cleared by "Reset demo data" and by
 *      the operating system's temp clean-up; nothing is written under the
 *      web root and no SQL is involved.
 *
 * Every request loads the whole store once, works on a reference to it, and
 * writes it back at shutdown only if something changed. Per-visitor state
 * (who is logged in, the chosen locale, a guest's cart) stays in the session.
 */

declare(strict_types=1);

/** The live store for this request. Accessed only through gf_store(). */
$GLOBALS['gf_store'] = null;
$GLOBALS['gf_store_dirty'] = false;
$GLOBALS['gf_store_lock'] = null;
$GLOBALS['gf_store_hash'] = '';

/** Unique key for this copy of the site, so two deployments on one server do not share data. */
function gf_store_key(): string
{
    return 'grimdark-forge-' . md5(GF_ROOT);
}

function gf_store_uses_apcu(): bool
{
    return function_exists('apcu_fetch') && function_exists('apcu_enabled') && apcu_enabled();
}

function gf_store_file(): string
{
    return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . gf_store_key() . '.store';
}

/**
 * Return a reference to the store, loading it on first use. For the file
 * backend an exclusive lock is taken here and held until shutdown, so a
 * request sees a consistent snapshot and its write cannot interleave with
 * another request's.
 */
function &gf_store(): array
{
    if ($GLOBALS['gf_store'] !== null) {
        return $GLOBALS['gf_store'];
    }

    $data = null;
    if (gf_store_uses_apcu()) {
        $ok = false;
        $data = apcu_fetch(gf_store_key(), $ok);
        if (!$ok || !is_array($data)) {
            $data = null;
        }
    } else {
        $fh = fopen(gf_store_file(), 'c+');
        if ($fh !== false) {
            flock($fh, LOCK_EX);
            $raw = stream_get_contents($fh);
            if ($raw !== false && $raw !== '') {
                $data = @unserialize($raw, ['allowed_classes' => false]);
                if (!is_array($data)) {
                    $data = null;
                }
            }
            $GLOBALS['gf_store_lock'] = $fh;
        }
    }

    if ($data === null) {
        $data = gf_seed_data();
        $GLOBALS['gf_store_dirty'] = true;
    } else {
        // Remember what was loaded so an unchanged store is not rewritten.
        $GLOBALS['gf_store_hash'] = md5(serialize($data));
    }
    $GLOBALS['gf_store'] = $data;
    register_shutdown_function('gf_store_flush');
    return $GLOBALS['gf_store'];
}

/** Replace the whole store (used by reset). */
function gf_store_replace(array $data): void
{
    gf_store(); // ensure loaded / locked
    $GLOBALS['gf_store'] = $data;
    $GLOBALS['gf_store_dirty'] = true;
}

/** Write the store back (if changed) and release the lock. */
function gf_store_flush(): void
{
    if ($GLOBALS['gf_store'] === null) {
        return;
    }
    $raw = serialize($GLOBALS['gf_store']);
    if ($GLOBALS['gf_store_dirty'] || md5($raw) !== $GLOBALS['gf_store_hash']) {
        if (gf_store_uses_apcu()) {
            apcu_store(gf_store_key(), $GLOBALS['gf_store']);
        } elseif ($GLOBALS['gf_store_lock']) {
            $fh = $GLOBALS['gf_store_lock'];
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $raw);
            fflush($fh);
        }
        $GLOBALS['gf_store_dirty'] = false;
    }
    if ($GLOBALS['gf_store_lock']) {
        flock($GLOBALS['gf_store_lock'], LOCK_UN);
        fclose($GLOBALS['gf_store_lock']);
        $GLOBALS['gf_store_lock'] = null;
    }
}
