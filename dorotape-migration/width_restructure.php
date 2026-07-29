<?php
/**
 * Width-first restructure (Michael's model, piloted on 7901 Rainbow Silver).
 *
 * Converts part-roll/full-roll variation pairs into ONE variation per width:
 *   - priced PER METRE (the old part-roll rate)
 *   - the full-roll rate becomes an automatic quantity tier at roll length
 *     (e.g. "1-49:11.54;50:10.16" — 50m+ = £508/50)
 *   - variation SKU = the Sage per-width item code (79011220 pattern from
 *     Michael's email; PROVISIONAL until verified against the Sage export)
 *
 * A customer ordering 60m at 1220mm enters qty 60 and pays 60 × £10.16
 * (£609.60) on one order line with one Sage code — matching how orders are
 * keyed into Sage manually. The theme's tier table / live price / cart
 * repricing engine already handles the rest.
 *
 * Preserves the parent's non-variation attributes (colour family, finish...)
 * and swaps only the variation attribute to "Width". Old variations are
 * TRASHED (restorable). Set the config per product below.
 *
 * Usage:
 *   php width_restructure.php --sku=7901 --dry-run
 *   php width_restructure.php --sku=7901
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts     = getopt('', ['dry-run', 'sku:']);
$dry_run  = isset($opts['dry-run']);
$only_sku = trim($opts['sku'] ?? '');

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

/* ---------- restructure configs ---------- */
// widths: label => [sage_sku, per-metre price, full-roll price, roll length m]
// 'step' (optional): _dt_qty_step for the parent, so quantities move in
// multiples. Must divide the roll length, or the volume tier is unreachable.
$configs = [
    '7901' => [
        'attribute' => 'Width',
        'unit'      => 'metre',
        'widths'    => [
            '610mm'  => ['7901610',  5.77,  254.00, 50],
            '760mm'  => ['7901760',  7.21,  317.50, 50],
            '1220mm' => ['79011220', 11.54, 508.00, 50],
            '1520mm' => ['79011520', 14.42, 635.00, 50],
        ],
    ],

    // Ri-Jet C50 #1334 — "needs to be formatted in the same way as [Ri-Lam C30]
    // but with the quantity steps of 5's".
    //
    // Every figure below comes from an original source, not from the current WP
    // rows:
    //   - per-metre rates are the Sage sales prices for the width-level stock
    //     codes (sage_skus.csv: RJ50STA/F760 7.360, RJ50STA/F1370 12.040,
    //     RJ50STA/F1524 13.360) and match the old site's 5m option prices
    //     exactly (36.80/5, 60.20/5, 66.80/5).
    //   - full-roll prices are the old Kryptronic option set 'rj50'
    //     (ecom_prodoptions_dataexport.csv): 760mm x 50m 331.20, 1370mm x 50m
    //     541.80, 1520mm x 50m 601.20.
    //
    // Width labels follow the Sage stock codes, which is the convention Ri-Lam
    // C30 already ships with — its old option labels read 760/1370/1520 but its
    // WP terms are 750mm/1370mm/1524mm, matching VEH750/VEH1370/VEH1524. So
    // RJ50STA/F1524 is labelled 1524mm here, not the old site's 1520mm.
    //
    // The tier rule is reconstructed from Ri-Lam C30 and reproduces its stored
    // tiers exactly: break at the roll length, tier price = roll price / length
    // rounded to 2dp. (C30 760mm: 331.20/46 = 7.20 -> '1-45:7.36;46:7.20'.)
    // That rounding means a 50m order here comes to £331.00 rather than the old
    // £331.20 — the same few-pence drift C30 already carries. Flagged to
    // Michael; swap in exact rates if he wants the roll price held to the penny.
    'RJ50STA/F' => [
        'attribute' => 'Width',
        'unit'      => 'metre',
        'step'      => 5,
        'widths'    => [
            '760mm'  => ['RJ50STA/F760',  7.36,  331.20, 50],
            '1370mm' => ['RJ50STA/F1370', 12.04, 541.80, 50],
            '1524mm' => ['RJ50STA/F1524', 13.36, 601.20, 50],
        ],
    ],

    // ASLAN DFP07 #6569 — "the options need to be just the roll width i.e.
    // 1370mm & 1370mm Grey Adhesive".
    //
    // The five current variations are length-first (1370mm x 10m, 1370mm x 50m,
    // the same two in grey adhesive, 500mm x 25m), so a straight relabel to the
    // width is impossible: two options would both read "1370mm". Restructuring
    // to one row per width is what actually delivers the labels Michael asked
    // for, and it puts DFP07 on the same footing as Ri-Jet C50 and Ri-Lam C30.
    //
    // Every figure comes from the Sage export (sage_skus.csv), not the WP rows:
    //   DFP071370 8.400, DFP07500 4.200, DFP07G1370 8.740.
    // Each reconciles exactly against the current whole-roll prices:
    //   1370mm  8.40 x 10 = £84,     50m roll £378.00 -> 7.56/m (a 10% break)
    //   1370mm grey 8.74 x 10 = £87.40, 50m roll £393.30 -> 7.866/m (10%)
    //   500mm   4.20 x 25 = £105.00 — the 25m roll carries NO discount, so no
    //           tier is written for it (see the roll_rate check below).
    //
    // Step 10, per the client's decision to keep 500mm on the same step as the
    // 1370mm widths rather than run two different steps on one product. The
    // 500mm roll is 25m, which 10 does not divide — but with no tier on that
    // width there is nothing to become unreachable. Flagged to Michael: a
    // customer wanting exactly one 25m roll has to order 20m or 30m.
    'DFP07' => [
        'attribute' => 'Width',
        'unit'      => 'metre',
        'step'      => 10,
        'widths'    => [
            '500mm'                => ['DFP07500',   4.20, 105.00, 25],
            '1370mm'               => ['DFP071370',  8.40, 378.00, 50],
            '1370mm Grey Adhesive' => ['DFP07G1370', 8.74, 393.30, 50],
        ],
    ],
];

/**
 * The discounted per-metre rate for a full roll, or 0 when the roll carries no
 * discount at all.
 *
 * Some widths are priced so that the full roll is exactly length x per-metre
 * (DFP07's 500mm: 4.20 x 25 = £105.00). Writing a tier there would put a
 * "25m+ £4.20" row on a £4.20/m product — a discount row offering no discount.
 *
 * @param float $roll_price
 * @param int   $roll_len
 * @param float $per_m
 * @return float
 */
function wr_roll_rate(float $roll_price, int $roll_len, float $per_m): float {
    $rate = round($roll_price / $roll_len, 2);
    return $rate < $per_m ? $rate : 0.0;
}

if ($only_sku === '' || !isset($configs[$only_sku])) {
    die("Pass --sku= one of: " . implode(', ', array_keys($configs)) . "\n");
}
$cfg = $configs[$only_sku];

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE RESTRUCTURE\n");

$parent_id = wc_get_product_id_by_sku($only_sku);
$parent    = $parent_id ? wc_get_product($parent_id) : null;
if (!$parent || !$parent->is_type('variable')) {
    die("$only_sku is not a variable product\n");
}
printf("%s  #%d  %s\n", $only_sku, $parent_id, $parent->get_name());

/* ---------- attribute helpers (same pattern as import_missing_products) ---- */

function ensure_global_attribute(string $label, bool $dry): string {
    $slug = rtrim(substr(wc_sanitize_taxonomy_name($label), 0, 28), '-');
    $taxonomy = wc_attribute_taxonomy_name($slug);
    if (!taxonomy_exists($taxonomy)) {
        if (!wc_attribute_taxonomy_id_by_name($slug)) {
            if ($dry) {
                echo "  [dry] would create attribute '$label' ($taxonomy)\n";
            } else {
                $res = wc_create_attribute(['name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order']);
                if (is_wp_error($res)) {
                    die("attribute '$label': " . $res->get_error_message() . "\n");
                }
            }
        }
        register_taxonomy($taxonomy, ['product'], ['hierarchical' => false, 'show_ui' => false]);
    }
    return $taxonomy;
}

function ensure_attribute_term(string $taxonomy, string $value, bool $dry): ?WP_Term {
    $term = get_term_by('name', $value, $taxonomy);
    if ($term) {
        return $term;
    }
    if ($dry) {
        echo "  [dry] would create term '$value' in $taxonomy\n";
        return null;
    }
    $res = wp_insert_term($value, $taxonomy);
    if (is_wp_error($res)) {
        die("term '$value': " . $res->get_error_message() . "\n");
    }
    return get_term($res['term_id'], $taxonomy);
}

/* ---------- plan ---------- */

$step = (int) ($cfg['step'] ?? 0);
if ($step > 1) {
    foreach ($cfg['widths'] as $label => [, $per_m, $roll_price, $roll_len]) {
        // Only widths that actually get a tier need the step to divide their
        // roll length. A width with no discount has nothing to become
        // unreachable, so an awkward roll length there is not a blocker.
        if (wr_roll_rate($roll_price, $roll_len, $per_m) > 0 && 0 !== $roll_len % $step) {
            die("step $step does not divide the $label roll length ({$roll_len}m) — "
                . "the volume tier would be unreachable\n");
        }
    }
}

$old_vids = $parent->get_children();
echo "  trash " . count($old_vids) . " old variations, create " . count($cfg['widths']) . " per-metre width variations:\n";
foreach ($cfg['widths'] as $label => [$sku, $per_m, $roll_price, $roll_len]) {
    $roll_rate = wr_roll_rate($roll_price, $roll_len, $per_m);
    if ($roll_rate <= 0) {
        printf("    %-22s %-14s £%.2f/m, no tier  (roll £%.2f = %dm x £%.2f, no discount)\n",
            $label, $sku, $per_m, $roll_price, $roll_len, $per_m);
        if ($step > 1 && 0 !== $roll_len % $step) {
            printf("    %-22s %-14s NOTE: a single %dm roll is not orderable at step %d — nearest are %dm and %dm\n",
                '', '', $roll_len, $step,
                intdiv($roll_len, $step) * $step, (intdiv($roll_len, $step) + 1) * $step);
        }
        continue;
    }
    $max = $roll_len - 1;
    printf("    %-22s %-14s £%.2f/m, tiers 1-%d:%.2f;%d:%.2f  (roll £%.2f, tier x%d = £%.2f)\n",
        $label, $sku, $per_m, $max, $per_m, $roll_len, $roll_rate,
        $roll_price, $roll_len, $roll_rate * $roll_len);
}
if ($step > 1) {
    echo "  quantity step: $step\n";
}
/*
 * Check every SKU against the Sage stock-code export before touching anything.
 * A restructure that invents a code Sage does not hold produces orders nobody
 * can key in, so this is worth failing loudly over rather than warning about.
 */
$sage_file = __DIR__ . '/sage_skus.csv';
$sage      = [];
if (is_readable($sage_file) && ($fh = fopen($sage_file, 'r'))) {
    fgetcsv($fh); // header
    while (($r = fgetcsv($fh)) !== false) {
        if (isset($r[0], $r[1], $r[3])) {
            $sage[trim($r[0])] = ['desc' => trim($r[1]), 'price' => (float) $r[3]];
        }
    }
    fclose($fh);
}

echo "  Sage stock codes:\n";
$sage_problems = [];
foreach ($cfg['widths'] as $label => [$sku, $per_m]) {
    if (!$sage) {
        printf("    %-14s ?  sage_skus.csv not readable — cannot verify\n", $sku);
        continue;
    }
    if (!isset($sage[$sku])) {
        printf("    %-14s MISSING from sage_skus.csv\n", $sku);
        $sage_problems[] = "$sku not in Sage";
        continue;
    }
    $row      = $sage[$sku];
    $obsolete = false !== stripos($row['desc'], 'DO NOT USE');
    $mismatch = abs($row['price'] - $per_m) >= 0.005;
    printf("    %-14s £%.3f  %s%s%s\n", $sku, $row['price'], $row['desc'],
        $obsolete ? '   << OBSOLETE' : '',
        $mismatch ? sprintf('   << price differs from config £%.2f', $per_m) : '');
    if ($obsolete) {
        $sage_problems[] = "$sku is marked DO NOT USE";
    }
    if ($mismatch) {
        $sage_problems[] = sprintf('%s Sage £%.3f vs config £%.2f', $sku, $row['price'], $per_m);
    }
}
if ($sage_problems && !$dry_run) {
    die("\nRefusing to apply — Sage problems:\n  " . implode("\n  ", $sage_problems) . "\n");
}

$tax = ensure_global_attribute($cfg['attribute'], $dry_run);

if ($dry_run) {
    foreach (array_keys($cfg['widths']) as $label) {
        ensure_attribute_term($tax, $label, true);
    }
    echo "dry run complete\n";
    exit;
}

/* ---------- execute ---------- */

// 1. Trash old variations.
foreach ($old_vids as $vid) {
    wp_trash_post($vid);
}

// 2. Terms.
$term_ids = [];
$term_by_label = [];
foreach (array_keys($cfg['widths']) as $label) {
    $t = ensure_attribute_term($tax, $label, false);
    $term_ids[] = (int) $t->term_id;
    $term_by_label[$label] = $t;
}

// 3. Parent attributes: keep non-variation attributes, swap the variation one.
$attributes = [];
foreach ($parent->get_attributes() as $key => $attr) {
    if ($attr instanceof WC_Product_Attribute && !$attr->get_variation()) {
        $attributes[] = $attr; // colour family, finish, adhesive... untouched
    } else {
        // drop old variation attribute; also clear its parent term links
        wp_set_object_terms($parent_id, [], $key);
    }
}
$width_attr = new WC_Product_Attribute();
$width_attr->set_id(wc_attribute_taxonomy_id_by_name($tax));
$width_attr->set_name($tax);
$width_attr->set_options($term_ids);
$width_attr->set_position(0);
$width_attr->set_visible(true);
$width_attr->set_variation(true);
array_unshift($attributes, $width_attr);
$parent->set_attributes($attributes);
$parent->save();

// 4. New variations.
foreach ($cfg['widths'] as $label => [$sku, $per_m, $roll_price, $roll_len]) {
    $roll_rate = wr_roll_rate($roll_price, $roll_len, $per_m);
    $tiers     = $roll_rate > 0
        ? sprintf('1-%d:%.2f;%d:%.2f', $roll_len - 1, $per_m, $roll_len, $roll_rate)
        : '';

    $var = new WC_Product_Variation();
    $var->set_parent_id($parent_id);
    $var->set_sku($sku);
    $var->set_regular_price((string) $per_m);
    $var->set_price((string) $per_m);
    $var->set_manage_stock(false);
    $var->set_stock_status('instock');
    $var->set_status('publish');
    $var->set_attributes([$tax => $term_by_label[$label]->slug]);
    $vid = $var->save();
    if ('' !== $tiers) {
        update_post_meta($vid, '_price_tiers', $tiers);
    } else {
        delete_post_meta($vid, '_price_tiers');
    }
    echo "  created #$vid $sku ($label) £$per_m/m tiers=" . ('' !== $tiers ? $tiers : '(none — roll carries no discount)') . "\n";
}

// 5. Unit, quantity step + sync.
update_post_meta($parent_id, '_dt_price_unit', $cfg['unit']);
if ($step > 1) {
    update_post_meta($parent_id, '_dt_qty_step', (string) $step);
    echo "  quantity step set to $step\n";
} else {
    delete_post_meta($parent_id, '_dt_qty_step');
}
WC_Product_Variable::sync($parent_id);
wc_delete_product_transients();
if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
}

$parent = wc_get_product($parent_id);
echo "done: £" . $parent->get_variation_price('min') . "–£" . $parent->get_variation_price('max')
    . "/m across " . count($parent->get_children()) . " widths\n";
if ($sage_problems) {
    echo "NOTE: variation SKUs are PROVISIONAL — not all matched the Sage export.\n";
} else {
    echo "All variation SKUs matched live codes in the Sage export.\n";
}
