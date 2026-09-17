<?php
/**
 * Shopping cart module — data access and pricing.
 *
 * Every amount is integer cents; totals are computed here, server-side,
 * from the in-memory product list. The browser never supplies a price.
 */

declare(strict_types=1);

const GF_GST_DIVISOR = 11;          // Australian GST is 1/11 of a GST-inclusive price
const GF_FREE_DELIVERY_CENTS = 12000;
const GF_MAX_LINE_QTY = 99;

const GF_SHIPPING = [
    'standard' => ['label' => 'Standard, three to six business days', 'cents' => 995],
    'express'  => ['label' => 'Express, one to two business days', 'cents' => 1795],
    'pickup'   => ['label' => 'Collect in store, ready within four hours', 'cents' => 0],
];

const GF_PROMO_CODES = [
    'FORGE10' => ['percent' => 10, 'label' => '10% off your order'],
    'BRUNSWICK' => ['percent' => 5, 'label' => '5% off for locals'],
];

const GF_CATEGORIES = [
    'kits'    => 'Miniature kits',
    'paints'  => 'Paints and shades',
    'tools'   => 'Brushes and tools',
    'terrain' => 'Terrain and scenery',
    'books'   => 'Rulebooks',
];

const GF_COLOURS = [
    'bone'      => 'Bone and ivory',
    'rust'      => 'Rust and oxide',
    'steel'     => 'Steel and gunmetal',
    'green'     => 'Field green',
    'unpainted' => 'Unpainted / bare plastic',
];

const GF_SCALES = [
    '15mm' => '15 mm',
    '28mm' => '28 mm',
    '32mm' => '32 mm',
    '30ml' => '30 ml pot',
    '60ml' => '60 ml pot',
];

/** One product by id, or null. */
function product(?string $id): ?array
{
    if ($id === null) {
        return null;
    }
    $products = collection('products');
    return $products[$id] ?? null;
}

/** Variant keys as strings (PHP turns numeric array keys such as '30' into ints). */
function variant_keys(array $product): array
{
    return array_map('strval', array_keys($product['variants']));
}

/** Label for a product variant key. */
function variant_label(array $product, string $variant): string
{
    return (string) ($product['variants'][$variant]['label'] ?? $variant);
}

/** Unit price for a product in a given variant. */
function unit_cents(array $product, string $variant): int
{
    return (int) $product['price_cents'] + (int) ($product['variants'][$variant]['extra_cents'] ?? 0);
}

/** Stock label and modifier class for a product. */
function stock_status(array $product): array
{
    $n = (int) $product['stock'];
    if ($n <= 0) {
        return ['stock--out', t('Out of stock')];
    }
    if ($n <= 4) {
        return ['stock--low', t('Only %1$s left', [number($n)])];
    }
    return ['stock--in', t('%1$s in stock', [number($n)])];
}

/** Average rating and count for a product, computed from the reviews module. */
function product_rating(string $productId): array
{
    $sum = 0;
    $n = 0;
    foreach (collection('reviews') as $r) {
        if ($r['product_id'] === $productId) {
            $sum += (int) $r['rating'];
            $n++;
        }
    }
    return ['avg' => $n ? $sum / $n : 0.0, 'count' => $n];
}

/** CSS class for a star fill, rounding to the nearest half star. */
function rating_class(float $avg): string
{
    $half = (int) round($avg * 2);
    $half = max(0, min(10, $half));
    return 'is-rating-' . ($half % 2 === 0 ? (string) intdiv($half, 2) : intdiv($half, 2) . '5');
}

/** Star rating markup with its text value (never colour or shape alone). */
function rating_html(float $avg, ?int $count = null): string
{
    $value = $count === null
        ? t('%1$s out of 5', [number($avg, 1)])
        : t('%1$s out of 5, from %2$s reviews', [number($avg, 1), number($count)]);
    if ($count === 0) {
        $value = t('No reviews yet');
    }
    return '<span class="rating__stars" aria-hidden="true">★★★★★<span class="rating__stars-fill ' . rating_class($avg) . '">★★★★★</span></span>'
        . '<span class="rating__value">' . e($value) . '</span>';
}

/* ---------------------------------------------------------------------------
   Cart lines and totals
   ------------------------------------------------------------------------ */

/** Cart lines joined to their products, with unit and line totals. */
function cart_lines(): array
{
    $lines = [];
    $c = cart();
    foreach ($c['items'] as $item) {
        $p = product($item['product_id']);
        if (!$p) {
            continue;
        }
        $unit = unit_cents($p, $item['variant']);
        $lines[] = $item + [
            'product'    => $p,
            'unit_cents' => $unit,
            'line_cents' => $unit * (int) $item['quantity'],
        ];
    }
    return $lines;
}

function gf_cart_subtotal(): int
{
    $sum = 0;
    foreach (cart_lines() as $l) {
        $sum += $l['line_cents'];
    }
    return $sum;
}

/** All money figures for the cart with a chosen delivery method. */
function cart_totals(string $shipping = 'standard'): array
{
    $subtotal = gf_cart_subtotal();
    $c = cart();
    $promo = $c['promo'] ?? null;
    $discount = 0;
    if ($promo !== null && isset(GF_PROMO_CODES[$promo])) {
        $discount = (int) round($subtotal * GF_PROMO_CODES[$promo]['percent'] / 100);
    }
    $goods = $subtotal - $discount;
    $ship = GF_SHIPPING[$shipping] ?? GF_SHIPPING['standard'];
    $delivery = (int) $ship['cents'];
    if ($shipping === 'standard' && $goods >= GF_FREE_DELIVERY_CENTS) {
        $delivery = 0;
    }
    $total = $goods + $delivery;
    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'promo'    => $promo,
        'shipping' => $shipping,
        'delivery' => $delivery,
        'gst'      => (int) round($total / GF_GST_DIVISOR),
        'total'    => $total,
    ];
}

/** Add a line to the cart (or increase an identical line). Returns the line id. */
function cart_add(string $productId, string $variant, int $qty, string $note = ''): int
{
    $c = &cart();
    foreach ($c['items'] as &$line) {
        if ($line['product_id'] === $productId && $line['variant'] === $variant && ($line['note'] ?? '') === $note) {
            $line['quantity'] = min(GF_MAX_LINE_QTY, (int) $line['quantity'] + $qty);
            return (int) $line['id'];
        }
    }
    unset($line);
    $id = next_id('cart_items');
    $c['items'][] = ['id' => $id, 'product_id' => $productId, 'variant' => $variant, 'quantity' => $qty, 'note' => $note];
    return $id;
}

/** Find a cart line by id (reference), or null. */
function &cart_line(int $id): ?array
{
    $c = &cart();
    foreach ($c['items'] as &$line) {
        if ((int) $line['id'] === $id) {
            return $line;
        }
    }
    $null = null;
    return $null;
}

function cart_remove(int $id): bool
{
    $c = &cart();
    foreach ($c['items'] as $i => $line) {
        if ((int) $line['id'] === $id) {
            array_splice($c['items'], $i, 1);
            return true;
        }
    }
    return false;
}

function cart_clear(): void
{
    $c = &cart();
    $c['items'] = [];
    $c['promo'] = null;
}
