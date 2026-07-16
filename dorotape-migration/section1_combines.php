<?php
/**
 * Section 1 combine batch — remaining 5 structural merges after the 9
 * true-duplicate BN-20 products were trashed and the 3 pure SKU-swaps
 * were done earlier:
 *
 *   A. SE71 Aurora   — #6480 (simple, standard) + #6481 (simple, PET) -> variable
 *   B. SE71 Alpha    — #6482 (simple, standard) + #6483 (simple, PET) -> variable
 *   C. Banner Bungee — #6534 (variable, White) + #6537 (variable, Black)
 *                      merge Black's variations into White, adding pa_colour
 *   D. DFP07 (#6569) — add missing 500mm x 25m Roll variation
 *   E. Digi-Fab (#6580) — add missing 500mm x 15m Roll variation (term already exists)
 *   F. Orajet 3268 (#6496, simple) -> variable, add missing 500mm width variation
 *
 * All Sage prices/codes verified against sage_skus.csv. All order-history
 * checks (via woocommerce_order_itemmeta._product_id) confirmed 0 for every
 * id touched here before this script was written.
 *
 * Usage: php section1_combines.php --dry-run | php section1_combines.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts    = getopt('', ['dry-run']);
$dry_run = isset($opts['dry-run']);

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';
global $wpdb;

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE RUN\n");

function ordercheck($wpdb, $id) {
    $c = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = %s AND meta_value = %s",
        '_product_id', $id
    ));
    if ($c > 0) {
        die("#$id has $c order line item(s) — aborting, needs manual review\n");
    }
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

function clear_sku_safely($product) {
    $product->set_sku('');
    $product->save();
    global $wpdb;
    $wpdb->update("{$wpdb->prefix}wc_product_meta_lookup", ['sku' => ''], ['product_id' => $product->get_id()]);
}

/* ============================================================
 * A + B. SE71 Aurora / Alpha: simple+simple -> variable
 * ============================================================ */
$se71_jobs = [
    'Aurora' => ['parent' => 6480, 'old' => 6481, 'std_sku' => '13142S1200', 'std_price' => 30.20, 'pet_sku' => '13142SPET1350', 'pet_price' => 33.68],
    'Alpha'  => ['parent' => 6482, 'old' => 6483, 'std_sku' => '13143S1200', 'std_price' => 30.20, 'pet_sku' => '13143SPET1350', 'pet_price' => 33.00],
];

foreach ($se71_jobs as $label => $j) {
    ordercheck($wpdb, $j['parent']);
    ordercheck($wpdb, $j['old']);
}

$backing_tax = wc_attribute_taxonomy_name('backing');
if (!$backing_tax || !taxonomy_exists($backing_tax)) {
    if ($dry_run) {
        echo "  [dry] would create global attribute 'Backing'\n";
        $backing_tax = 'pa_backing';
    } else {
        $attr_id = wc_create_attribute([
            'name'         => 'Backing',
            'slug'         => 'backing',
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ]);
        if (is_wp_error($attr_id)) {
            die('create Backing attribute: ' . $attr_id->get_error_message() . "\n");
        }
        $backing_tax = wc_attribute_taxonomy_name('backing');
        register_taxonomy($backing_tax, 'product', ['hierarchical' => false]);
    }
}

foreach ($se71_jobs as $label => $j) {
    echo "SE71 $label: merge #{$j['old']} into #{$j['parent']}\n";
    if ($dry_run) {
        ensure_attribute_term($backing_tax, 'Standard', true);
        ensure_attribute_term($backing_tax, 'PET Backed', true);
        echo "  [dry] would convert #{$j['parent']} to variable, add Standard (£{$j['std_sku']}) + PET Backed ({$j['pet_sku']}) variations, trash #{$j['old']}\n";
        continue;
    }

    $std_term = ensure_attribute_term($backing_tax, 'Standard', false);
    $pet_term = ensure_attribute_term($backing_tax, 'PET Backed', false);

    wp_set_object_terms($j['parent'], 'variable', 'product_type');
    $parent = wc_get_product($j['parent']);
    clear_sku_safely($parent);

    $attributes = [];
    foreach ($parent->get_attributes() as $attr) {
        if ($attr instanceof WC_Product_Attribute) {
            $attributes[] = $attr;
        }
    }
    $backing_attr = new WC_Product_Attribute();
    $backing_attr->set_id(wc_attribute_taxonomy_id_by_name($backing_tax));
    $backing_attr->set_name($backing_tax);
    $backing_attr->set_options([$std_term->term_id, $pet_term->term_id]);
    $backing_attr->set_position(0);
    $backing_attr->set_visible(true);
    $backing_attr->set_variation(true);
    array_unshift($attributes, $backing_attr);
    $parent->set_attributes($attributes);
    $parent->save();

    $std_var = new WC_Product_Variation();
    $std_var->set_parent_id($j['parent']);
    $std_var->set_sku($j['std_sku']);
    $std_var->set_regular_price((string) $j['std_price']);
    $std_var->set_price((string) $j['std_price']);
    $std_var->set_manage_stock(false);
    $std_var->set_stock_status('instock');
    $std_var->set_status('publish');
    $std_var->set_attributes([$backing_tax => $std_term->slug]);
    $vid1 = $std_var->save();
    echo "  created #$vid1 {$j['std_sku']} (Standard) £{$j['std_price']}\n";

    $pet_var = new WC_Product_Variation();
    $pet_var->set_parent_id($j['parent']);
    $pet_var->set_sku($j['pet_sku']);
    $pet_var->set_regular_price((string) $j['pet_price']);
    $pet_var->set_price((string) $j['pet_price']);
    $pet_var->set_manage_stock(false);
    $pet_var->set_stock_status('instock');
    $pet_var->set_status('publish');
    $pet_var->set_attributes([$backing_tax => $pet_term->slug]);
    $vid2 = $pet_var->save();
    echo "  created #$vid2 {$j['pet_sku']} (PET Backed) £{$j['pet_price']}\n";

    wp_trash_post($j['old']);
    echo "  trashed #{$j['old']}\n";

    WC_Product_Variable::sync($j['parent']);
    wc_delete_product_transients($j['parent']);
}

/* ============================================================
 * C. Banner Bungee: merge Black (#6537) variations into White (#6534)
 * ============================================================ */
echo "\nBanner Bungee: merge #6537 (Black) into #6534 (White)\n";
foreach ([6534, 6535, 6536, 6537, 6538, 6539] as $id) {
    ordercheck($wpdb, $id);
}

$colour_tax = wc_attribute_taxonomy_name('colour'); // pa_colour, already exists (empty)

if ($dry_run) {
    ensure_attribute_term($colour_tax, 'White', true);
    ensure_attribute_term($colour_tax, 'Black', true);
    echo "  [dry] would add pa_colour to #6534, set existing variations to White,\n";
    echo "        recreate Black's 2 variations under #6534, fix SKUs to Sage codes\n";
    echo "        (BUNGTEND/BUNGBALL/BUNGTENDBLACK/BUNGBALLBLACK), trash #6537\n";
} else {
    $white_term = ensure_attribute_term($colour_tax, 'White', false);
    $black_term = ensure_attribute_term($colour_tax, 'Black', false);

    $parent = wc_get_product(6534);
    $attributes = [];
    foreach ($parent->get_attributes() as $attr) {
        if ($attr instanceof WC_Product_Attribute) {
            $attributes[] = $attr;
        }
    }
    $colour_attr = new WC_Product_Attribute();
    $colour_attr->set_id(wc_attribute_taxonomy_id_by_name($colour_tax));
    $colour_attr->set_name($colour_tax);
    $colour_attr->set_options([$white_term->term_id, $black_term->term_id]);
    $colour_attr->set_position(0);
    $colour_attr->set_visible(true);
    $colour_attr->set_variation(true);
    array_unshift($attributes, $colour_attr);
    $parent->set_attributes($attributes);
    $parent->save();

    // Existing White variations: add colour=White, fix SKU to Sage code.
    $sku_map = [6535 => 'BUNGTEND', 6536 => 'BUNGBALL'];
    foreach ($sku_map as $vid => $sku) {
        $v = wc_get_product($vid);
        $attrs = $v->get_attributes();
        $attrs[$colour_tax] = $white_term->slug;
        $v->set_attributes($attrs);
        clear_sku_safely($v);
        $v->set_sku($sku);
        $v->save();
        echo "  updated #$vid -> sku=$sku colour=White\n";
    }

    // Recreate Black's 2 variations under the White parent.
    $black_source = ['t-bar-end' => ['old' => 6538, 'sku' => 'BUNGTENDBLACK'], 'ball-end' => ['old' => 6539, 'sku' => 'BUNGBALLBLACK']];
    foreach ($black_source as $type_slug => $info) {
        $old_v = wc_get_product($info['old']);
        $new_v = new WC_Product_Variation();
        $new_v->set_parent_id(6534);
        $new_v->set_sku($info['sku']);
        $new_v->set_regular_price($old_v->get_regular_price());
        $new_v->set_price($old_v->get_price());
        $new_v->set_manage_stock(false);
        $new_v->set_stock_status('instock');
        $new_v->set_status('publish');
        $new_v->set_attributes(['pa_choose-type' => $type_slug, $colour_tax => $black_term->slug]);
        $nvid = $new_v->save();
        echo "  created #$nvid {$info['sku']} (Black, $type_slug) £" . $old_v->get_price() . "\n";
    }

    wp_trash_post(6537);
    echo "  trashed #6537 (old Black parent, cascades to its variations)\n";

    WC_Product_Variable::sync(6534);
    wc_delete_product_transients(6534);
}

/* ============================================================
 * D. DFP07 (#6569): add missing 500mm x 25m Roll variation
 * ============================================================ */
echo "\nDFP07: add 500mm x 25m Roll variation to #6569\n";
ordercheck($wpdb, 6531); // the BN-20 duplicate this replaces, already confirmed 0 above

$len_tax = 'pa_select-length-of-roll';
if ($dry_run) {
    ensure_attribute_term($len_tax, '500mm x 25m Roll', true);
    echo "  [dry] would add variation DFP07-sz05 £105.00\n";
} else {
    $term = ensure_attribute_term($len_tax, '500mm x 25m Roll', false);
    $parent = wc_get_product(6569);
    $attributes = $parent->get_attributes();
    foreach ($attributes as $name => $attr) {
        if ($name === $len_tax && $attr instanceof WC_Product_Attribute) {
            $opts = $attr->get_options();
            $opts[] = $term->term_id;
            $attr->set_options($opts);
            $attributes[$name] = $attr;
        }
    }
    $parent->set_attributes($attributes);
    $parent->save();

    $var = new WC_Product_Variation();
    $var->set_parent_id(6569);
    $var->set_sku('DFP07-sz05');
    $var->set_regular_price('105.00');
    $var->set_price('105.00');
    $var->set_manage_stock(false);
    $var->set_stock_status('instock');
    $var->set_status('publish');
    $var->set_attributes([$len_tax => $term->slug]);
    $vid = $var->save();
    echo "  created #$vid DFP07-sz05 (500mm x 25m Roll) £105.00\n";

    WC_Product_Variable::sync(6569);
    wc_delete_product_transients(6569);
}

/* ============================================================
 * E. Digi-Fab (#6580): add missing 500mm x 15m Roll variation
 *    (term "500mm x 15m Roll" already exists in the shared taxonomy)
 * ============================================================ */
echo "\nDigi-Fab: add 500mm x 15m Roll variation to #6580\n";
ordercheck($wpdb, 6533); // the BN-20 duplicate this replaces, already confirmed 0 above

$size_tax = 'pa_select-size-of-roll';
if ($dry_run) {
    echo "  [dry] would add variation DIGI-FAB-sz07 £76.90 using existing term 500mm x 15m Roll\n";
} else {
    $term = get_term_by('slug', '500mm-x-15m-roll', $size_tax);
    if (!$term) {
        die("expected existing term '500mm x 15m Roll' not found in $size_tax\n");
    }
    $parent = wc_get_product(6580);
    $attributes = $parent->get_attributes();
    foreach ($attributes as $name => $attr) {
        if ($name === $size_tax && $attr instanceof WC_Product_Attribute) {
            $opts = $attr->get_options();
            $opts[] = $term->term_id;
            $attr->set_options($opts);
            $attributes[$name] = $attr;
        }
    }
    $parent->set_attributes($attributes);
    $parent->save();

    $var = new WC_Product_Variation();
    $var->set_parent_id(6580);
    $var->set_sku('DIGI-FAB-sz07');
    $var->set_regular_price('76.90');
    $var->set_price('76.90');
    $var->set_manage_stock(false);
    $var->set_stock_status('instock');
    $var->set_status('publish');
    $var->set_attributes([$size_tax => $term->slug]);
    $vid = $var->save();
    echo "  created #$vid DIGI-FAB-sz07 (500mm x 15m Roll) £76.90\n";

    WC_Product_Variable::sync(6580);
    wc_delete_product_transients(6580);
}

/* ============================================================
 * F. Orajet 3268 (#6496): simple -> variable, add missing 500mm width
 * ============================================================ */
echo "\nOrajet 3268: convert #6496 to variable, add 500mm width\n";
ordercheck($wpdb, 6496);
ordercheck($wpdb, 6532); // the BN-20 duplicate this replaces, already confirmed 0 above

$width_tax = wc_attribute_taxonomy_name('width'); // pa_width, already exists

if ($dry_run) {
    ensure_attribute_term($width_tax, '500mm', true);
    ensure_attribute_term($width_tax, '1370mm', true);
    echo "  [dry] would convert #6496 to variable, add 1370mm (existing sku 32681370, tiers 1-49:8.38;50:7.63)\n";
    echo "        and 500mm (new sku 3268500, tiers 1-49:4.00;50:3.64) variations\n";
} else {
    $old_sku   = $parent_sku_backup = '32681370';
    $old_price = 8.38;
    $old_tiers = '1-49:8.38;50:7.63';

    $w500  = ensure_attribute_term($width_tax, '500mm', false);
    $w1370 = ensure_attribute_term($width_tax, '1370mm', false);

    wp_set_object_terms(6496, 'variable', 'product_type');
    $parent = wc_get_product(6496);
    clear_sku_safely($parent);

    $attributes = [];
    foreach ($parent->get_attributes() as $attr) {
        if ($attr instanceof WC_Product_Attribute) {
            $attributes[] = $attr;
        }
    }
    $width_attr = new WC_Product_Attribute();
    $width_attr->set_id(wc_attribute_taxonomy_id_by_name($width_tax));
    $width_attr->set_name($width_tax);
    $width_attr->set_options([$w500->term_id, $w1370->term_id]);
    $width_attr->set_position(0);
    $width_attr->set_visible(true);
    $width_attr->set_variation(true);
    array_unshift($attributes, $width_attr);
    $parent->set_attributes($attributes);
    $parent->save();

    $v1370 = new WC_Product_Variation();
    $v1370->set_parent_id(6496);
    $v1370->set_sku($old_sku);
    $v1370->set_regular_price((string) $old_price);
    $v1370->set_price((string) $old_price);
    $v1370->set_manage_stock(false);
    $v1370->set_stock_status('instock');
    $v1370->set_status('publish');
    $v1370->set_attributes([$width_tax => $w1370->slug]);
    $vid1 = $v1370->save();
    update_post_meta($vid1, '_price_tiers', $old_tiers);
    echo "  created #$vid1 $old_sku (1370mm) £$old_price tiers=$old_tiers\n";

    $v500 = new WC_Product_Variation();
    $v500->set_parent_id(6496);
    $v500->set_sku('3268500');
    $v500->set_regular_price('4.00');
    $v500->set_price('4.00');
    $v500->set_manage_stock(false);
    $v500->set_stock_status('instock');
    $v500->set_status('publish');
    $v500->set_attributes([$width_tax => $w500->slug]);
    $vid2 = $v500->save();
    update_post_meta($vid2, '_price_tiers', '1-49:4.00;50:3.64');
    echo "  created #$vid2 3268500 (500mm) £4.00 tiers=1-49:4.00;50:3.64\n";

    WC_Product_Variable::sync(6496);
    wc_delete_product_transients(6496);
}

echo "\ndone\n";
