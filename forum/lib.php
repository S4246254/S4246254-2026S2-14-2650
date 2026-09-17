<?php
/**
 * Discussion forum module — data access.
 *
 * Threads are subjects; every post (the opening post and each reply) is a
 * row in the 'posts' collection with a parent_id that builds the tree.
 * Deleting a post sets 'deleted' => true: it disappears from view but stays
 * in memory for auditing, as the brief requires.
 */

declare(strict_types=1);

const GF_BOARDS = [
    'rules'    => 'Rules and errata',
    'lists'    => 'Army lists',
    'painting' => 'Painting',
    'terrain'  => 'Terrain',
    'wip'      => 'Work in progress',
    'events'   => 'Events and tournaments',
];

const GF_THREAD_TAGS = [
    'question'   => 'Question',
    'discussion' => 'Discussion',
    'showcase'   => 'Showcase',
    'tutorial'   => 'Tutorial',
];

const GF_DELETE_REASONS = [
    'mistake'   => 'Posted by mistake',
    'duplicate' => 'Duplicate of another post',
    'wrong'     => 'The information was wrong',
    'resolved'  => 'No longer relevant',
];

function thread(?int $id): ?array
{
    if ($id === null) {
        return null;
    }
    $threads = collection('threads');
    return isset($threads[$id]) ? $threads[$id] + ['id' => $id] : null;
}

function forum_post(?int $id): ?array
{
    if ($id === null) {
        return null;
    }
    $posts = collection('posts');
    return isset($posts[$id]) ? $posts[$id] + ['id' => $id] : null;
}

/** Every post in a thread, in creation order, including deleted ones. */
function thread_posts_all(int $threadId): array
{
    $out = [];
    foreach (collection('posts') as $id => $p) {
        if ((int) $p['thread_id'] === $threadId) {
            $out[$id] = $p + ['id' => $id];
        }
    }
    uasort($out, function ($a, $b) {
        return strcmp($a['created'], $b['created']);
    });
    return $out;
}

/** Visible (not deleted) posts in a thread. */
function thread_posts(int $threadId): array
{
    return array_filter(thread_posts_all($threadId), function ($p) {
        return empty($p['deleted']);
    });
}

/** The opening post of a thread (visible or not). */
function opening_post(int $threadId): ?array
{
    foreach (thread_posts_all($threadId) as $p) {
        if ($p['parent_id'] === null) {
            return $p;
        }
    }
    return null;
}

/**
 * Children of a post among visible posts. A reply whose parent was deleted
 * is re-attached to the nearest visible ancestor so it stays readable.
 */
function post_children(array $visible, array $all, ?int $parentId): array
{
    $children = [];
    foreach ($visible as $p) {
        if ($p['parent_id'] === null) {
            continue;
        }
        // Walk up past deleted ancestors.
        $anc = (int) $p['parent_id'];
        while (isset($all[$anc]) && !empty($all[$anc]['deleted']) && $all[$anc]['parent_id'] !== null) {
            $anc = (int) $all[$anc]['parent_id'];
        }
        if ($anc === $parentId) {
            $children[] = $p;
        }
    }
    return $children;
}

/** Summary figures for a thread: visible replies, first and last activity, thumbnail. */
function thread_summary(int $threadId): array
{
    $all = thread_posts_all($threadId);
    $visible = array_values(array_filter($all, function ($p) {
        return empty($p['deleted']);
    }));
    $opening = null;
    foreach ($all as $p) {
        if ($p['parent_id'] === null) {
            $opening = $p;
            break;
        }
    }
    $dates = array_map(function ($p) {
        return $p['created'];
    }, $visible);
    sort($dates);
    return [
        'replies'   => max(0, count($visible) - ($opening && empty($opening['deleted']) ? 1 : 0)),
        'oldest'    => $dates[0] ?? null,
        'newest'    => $dates ? $dates[count($dates) - 1] : null,
        'image'     => $opening['image'] ?? '',
        'image_alt' => $opening['image_alt'] ?? '',
        'opening_deleted' => $opening ? !empty($opening['deleted']) : true,
    ];
}

/** Number of visible direct replies to a post. */
function direct_reply_count(int $postId): int
{
    $n = 0;
    foreach (collection('posts') as $p) {
        if ((int) ($p['parent_id'] ?? 0) === $postId && empty($p['deleted'])) {
            $n++;
        }
    }
    return $n;
}

/**
 * Threads matching the filters, sorted. Filters: title, text, author, board.
 * Sort: activity-desc|activity-asc|created-desc|created-asc|title-asc|title-desc|replies-desc.
 */
function threads_query(array $f): array
{
    $rows = [];
    foreach (collection('threads') as $id => $t) {
        $s = thread_summary((int) $id);
        if ($s['opening_deleted']) {
            continue; // a thread whose opening post was deleted is hidden
        }
        if ($f['title'] !== '' && mb_stripos($t['title'], $f['title']) === false) {
            continue;
        }
        if ($f['author'] !== '' && $t['author'] !== $f['author']) {
            continue;
        }
        if ($f['board'] !== '' && $t['board'] !== $f['board']) {
            continue;
        }
        if ($f['text'] !== '') {
            $hit = false;
            foreach (thread_posts((int) $id) as $p) {
                if (mb_stripos($p['body'], $f['text']) !== false || mb_stripos($p['title'], $f['text']) !== false) {
                    $hit = true;
                    break;
                }
            }
            if (!$hit) {
                continue;
            }
        }
        $rows[] = $t + ['id' => (int) $id] + $s;
    }

    $sort = $f['sort'];
    usort($rows, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'activity-asc':  return strcmp($a['newest'], $b['newest']);
            case 'created-desc':  return strcmp($b['oldest'], $a['oldest']);
            case 'created-asc':   return strcmp($a['oldest'], $b['oldest']);
            case 'title-asc':     return strcasecmp($a['title'], $b['title']);
            case 'title-desc':    return strcasecmp($b['title'], $a['title']);
            case 'replies-desc':  return $b['replies'] <=> $a['replies'];
            default:              return strcmp($b['newest'], $a['newest']);
        }
    });
    return $rows;
}
