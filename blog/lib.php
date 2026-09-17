<?php
/**
 * Blog module — data access.
 */

declare(strict_types=1);

const GF_BLOG_TAGS = [
    'painting' => 'Painting',
    'basing'   => 'Basing',
    'terrain'  => 'Terrain',
    'events'   => 'Events',
    'beginner' => 'Beginner',
];

function blog_post(?int $id): ?array
{
    if ($id === null) {
        return null;
    }
    $posts = collection('blog');
    return isset($posts[$id]) ? $posts[$id] + ['id' => $id] : null;
}

/** Comments on a post, oldest first. */
function blog_comments(int $postId): array
{
    $out = [];
    foreach (collection('comments') as $id => $c) {
        if ((int) $c['post_id'] === $postId) {
            $out[] = $c + ['id' => $id];
        }
    }
    usort($out, function ($a, $b) {
        return strcmp($a['created'], $b['created']);
    });
    return $out;
}

function blog_comment(?int $id): ?array
{
    if ($id === null) {
        return null;
    }
    $comments = collection('comments');
    return isset($comments[$id]) ? $comments[$id] + ['id' => $id] : null;
}

function blog_comment_count(int $postId): int
{
    return count(blog_comments($postId));
}

/** Rough reading time in minutes. */
function reading_minutes(string $text): int
{
    return max(1, (int) ceil(str_word_count(strip_tags($text)) / 200));
}

/**
 * Posts matching the filters. Filters: q (title/summary), text (body and
 * image description), author, tag, from, to. Sort: date-desc|date-asc|
 * title-asc|title-desc|comments-desc.
 */
function blog_query(array $f): array
{
    $rows = [];
    foreach (collection('blog') as $id => $p) {
        if ($f['q'] !== '' && mb_stripos($p['title'], $f['q']) === false && mb_stripos($p['summary'], $f['q']) === false) {
            continue;
        }
        if ($f['text'] !== '' && mb_stripos($p['body'], $f['text']) === false && mb_stripos($p['image_alt'], $f['text']) === false) {
            continue;
        }
        if ($f['author'] !== '' && $p['author'] !== $f['author']) {
            continue;
        }
        if ($f['tag'] !== '' && !in_array($f['tag'], $p['tags'], true)) {
            continue;
        }
        if ($f['from'] !== '' && $p['date'] < $f['from']) {
            continue;
        }
        if ($f['to'] !== '' && $p['date'] > $f['to']) {
            continue;
        }
        $rows[] = $p + ['id' => (int) $id, 'comments' => blog_comment_count((int) $id)];
    }
    $sort = $f['sort'];
    usort($rows, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'date-asc':      return strcmp($a['date'], $b['date']) ?: $a['id'] <=> $b['id'];
            case 'title-asc':     return strcasecmp($a['title'], $b['title']);
            case 'title-desc':    return strcasecmp($b['title'], $a['title']);
            case 'comments-desc': return $b['comments'] <=> $a['comments'];
            default:              return strcmp($b['date'], $a['date']) ?: $b['id'] <=> $a['id'];
        }
    });
    return $rows;
}

/** Rules for the post form (tags are validated separately because they are an array). */
function blog_rules(): array
{
    return [
        'post-title'    => ['label' => 'Title', 'required' => true, 'min' => 10, 'max' => 90],
        'post-summary'  => ['label' => 'Summary', 'required' => true, 'min' => 20, 'max' => 200],
        'post-body'     => ['label' => 'Post text', 'required' => true, 'min' => 50, 'max' => 10000],
        'post-date'     => ['label' => 'Publish date', 'required' => true, 'type' => 'date'],
        'post-comments' => ['label' => 'Allow comments'],
    ] + image_rules();
}

/** Read and validate the tag checkboxes. Returns [tags, error|null]. */
function blog_tags_from_post(): array
{
    $raw = filter_input(INPUT_POST, 'post-tags', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
    $tags = [];
    foreach ($raw as $tag) {
        if (is_string($tag) && isset(GF_BLOG_TAGS[$tag])) {
            $tags[] = $tag;
        }
    }
    $tags = array_values(array_unique($tags));
    if (!$tags) {
        return [[], t('Choose at least one tag.')];
    }
    if (count($tags) > 3) {
        return [$tags, t('Choose no more than three tags.')];
    }
    return [$tags, null];
}
