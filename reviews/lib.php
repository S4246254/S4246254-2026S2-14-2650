<?php
/**
 * Product review and rating module — data access.
 */

declare(strict_types=1);

const GF_RATING_LABELS = [
    1 => '1 star — poor',
    2 => '2 stars — below average',
    3 => '3 stars — acceptable',
    4 => '4 stars — good',
    5 => '5 stars — excellent',
];

function review(?int $id): ?array
{
    if ($id === null) {
        return null;
    }
    $reviews = collection('reviews');
    return isset($reviews[$id]) ? $reviews[$id] + ['id' => $id] : null;
}

/** Overall average and per-star counts across every review. */
function reviews_breakdown(): array
{
    $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $sum = 0;
    $n = 0;
    foreach (collection('reviews') as $r) {
        $counts[(int) $r['rating']]++;
        $sum += (int) $r['rating'];
        $n++;
    }
    return ['avg' => $n ? $sum / $n : 0.0, 'count' => $n, 'counts' => $counts];
}

/** CSS width class for a proportion, rounded to the nearest 5%. */
function pct_class(int $part, int $whole): string
{
    $pct = $whole > 0 ? (int) (round($part / $whole * 100 / 5) * 5) : 0;
    return 'is-pct-' . $pct;
}

/** A short excerpt of a body for preview cards. */
function excerpt(string $text, int $chars = 180): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if (mb_strlen($text) <= $chars) {
        return $text;
    }
    $cut = mb_substr($text, 0, $chars);
    $space = mb_strrpos($cut, ' ');
    return ($space ? mb_substr($cut, 0, $space) : $cut) . '…';
}

/**
 * Reviews matching the filters. Filters: product (title contains), q (review
 * title), text (body / image description), rating (minimum), reviewer,
 * from, to. Sort: date-desc|date-asc|rating-desc|rating-asc|helpful-desc|title-asc.
 */
function reviews_query(array $f): array
{
    $rows = [];
    foreach (collection('reviews') as $id => $r) {
        $p = product($r['product_id']);
        $productName = $p['name'] ?? '';
        if ($f['product'] !== '' && $f['product'] !== $r['product_id'] && mb_stripos($productName, $f['product']) === false) {
            continue;
        }
        if ($f['q'] !== '' && mb_stripos($r['title'], $f['q']) === false) {
            continue;
        }
        if ($f['text'] !== '' && mb_stripos($r['body'], $f['text']) === false && mb_stripos($r['image_alt'], $f['text']) === false) {
            continue;
        }
        if ($f['rating'] !== '' && (int) $r['rating'] < (int) $f['rating']) {
            continue;
        }
        if ($f['reviewer'] !== '' && $r['author'] !== $f['reviewer']) {
            continue;
        }
        if ($f['from'] !== '' && $r['date'] < $f['from']) {
            continue;
        }
        if ($f['to'] !== '' && $r['date'] > $f['to']) {
            continue;
        }
        $rows[] = $r + ['id' => (int) $id, 'product_name' => $productName];
    }
    $sort = $f['sort'];
    usort($rows, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'date-asc':     return strcmp($a['date'], $b['date']) ?: $a['id'] <=> $b['id'];
            case 'rating-desc':  return $b['rating'] <=> $a['rating'] ?: strcmp($b['date'], $a['date']);
            case 'rating-asc':   return $a['rating'] <=> $b['rating'] ?: strcmp($b['date'], $a['date']);
            case 'helpful-desc': return $b['helpful'] <=> $a['helpful'];
            case 'title-asc':    return strcasecmp($a['title'], $b['title']);
            default:             return strcmp($b['date'], $a['date']) ?: $b['id'] <=> $a['id'];
        }
    });
    return $rows;
}

/** Rules for the review form, shared by create and edit. */
function review_rules(): array
{
    return [
        'review-product'    => ['label' => 'Product', 'required' => true, 'in' => array_keys(collection('products'))],
        'rating'            => ['label' => 'Your rating', 'required' => true, 'in' => ['1', '2', '3', '4', '5']],
        'review-title'      => ['label' => 'Headline', 'required' => true, 'min' => 5, 'max' => 90],
        'review-body'       => ['label' => 'Full review', 'required' => true, 'min' => 50, 'max' => 5000],
        'review-guidelines' => ['label' => 'This review is my own experience of the product', 'checked' => true],
    ] + image_rules();
}
