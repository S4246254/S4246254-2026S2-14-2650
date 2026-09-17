<?php
/** HTML rendering for forum posts (used by thread.php and delete-post.php). */

declare(strict_types=1);

/** One post as an <article>, with the controls the signed-in user is allowed to use. */
function render_forum_post(array $post, array $thread, bool $opening = false, bool $controls = true): string
{
    $base = gf_base();
    $mine = owns($post['author']);
    $classes = 'post' . ($opening ? ' post--opening' : '') . ($mine ? ' post--mine' : '');

    $out  = '<article class="' . $classes . '" id="post-' . (int) $post['id'] . '">';
    $out .= '<div class="post__head"><h3 class="post__title">' . e($post['title']) . '</h3>';
    if ($opening) {
        $out .= '<span class="badge badge--primary">' . te('Opening post') . '</span>';
    }
    if ($mine) {
        $out .= '<span class="badge badge--primary">' . te('Your post') . '</span>';
    }
    $out .= '</div>';

    $out .= '<p class="byline"><img class="byline__avatar" src="' . $base . e(user_avatar($post['author'])) . '" alt="">';
    $out .= '<span class="byline__name">' . e(user_name($post['author'])) . '</span> ';
    $out .= time_html($post['created'], 'datetime');
    if (!empty($post['edited'])) {
        $out .= '<span class="text-muted">' . te('Edited') . ' ' . time_html($post['edited'], 'datetime');
        if (!empty($post['edit_reason'])) {
            $out .= ' — ' . e($post['edit_reason']);
        }
        $out .= '</span>';
    }
    $out .= '</p>';

    $out .= '<div class="prose">' . paragraphs($post['body']) . '</div>';

    if (!empty($post['image'])) {
        $out .= '<figure class="post__figure"><img class="post__image" src="' . e(image_src($post['image'])) . '" alt="' . e($post['image_alt']) . '">';
        if (!empty($post['caption'])) {
            $out .= '<figcaption>' . e($post['caption']) . '</figcaption>';
        }
        $out .= '</figure>';
    }

    if ($controls) {
        $out .= '<div class="post__actions">';
        if (is_logged_in()) {
            $out .= '<a class="button button--small" href="thread.php?id=' . (int) $thread['id'] . '&amp;reply=' . (int) $post['id'] . '#reply-form">' . te('Reply') . '</a>';
        }
        if ($mine) {
            $out .= '<a class="button button--small" href="edit-post.php?id=' . (int) $post['id'] . '">' . te('Edit') . '</a>';
            $out .= '<a class="button button--small button--danger" href="delete-post.php?id=' . (int) $post['id'] . '">' . te('Delete') . '</a>';
            $out .= '<span class="ownership-note">' . te('Posted by you') . '</span>';
        } else {
            $out .= '<span class="ownership-note">' . te('Posted by %1$s', [user_name($post['author'])]) . '</span>';
        }
        $out .= '</div>';
    }

    return $out . '</article>';
}

/** Recursively render the replies beneath a post as a nested list. */
function render_reply_tree(array $visible, array $all, int $parentId, array $thread, int $depth = 0): string
{
    $children = post_children($visible, $all, $parentId);
    if (!$children) {
        return '';
    }
    $out = $depth === 0 ? '<ul class="reply-tree">' : '<ul>';
    foreach ($children as $child) {
        $out .= '<li>' . render_forum_post($child, $thread) . render_reply_tree($visible, $all, (int) $child['id'], $thread, $depth + 1) . '</li>';
    }
    return $out . '</ul>';
}
