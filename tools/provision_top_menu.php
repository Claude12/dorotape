<?php
/**
 * Create the top menu and put the WooCommerce account pages in it.
 *
 * Why this exists as a script rather than as theme code.
 *
 * The header has three menu locations and only Primary had a menu assigned to
 * it. An unassigned location used to fall through to wp_page_menu(), which
 * lists every published page alphabetically, so Cart, Checkout, My account,
 * Wishlist and all three policy pages appeared in the header whether anyone
 * wanted them there or not. That fallback has now been turned off, which was
 * the right fix but left the account pages with nowhere to live. This gives
 * them a real menu.
 *
 * It has to be a menu and not a hardcoded list in header.php. The complaint
 * that started this was that those links could not be found or changed
 * anywhere in the CMS, and hardcoding them would recreate exactly that. Menu
 * items are editable in Appearance > Menus; template markup is not.
 *
 * Menus live in the database, and the deploy only carries theme files, so a
 * menu built by hand on one site does not travel to the others. Hence a script
 * that can be run again on dev and on live to produce the same result.
 *
 * It sits in tools/ and not in dorotape-migration/ with the other one-off
 * scripts because that directory is gitignored, deliberately: it held 113 CSV
 * exports of the old CMS including 10,358 customer rows, and deploying is a git
 * pull inside public_html on a public repository. A script placed there would
 * never reach dev or live, which is the entire reason this one exists. The
 * ignore rule is about raw data, so scripts that carry none get their own home.
 *
 * Portable on purpose: every page is resolved through its own option, so this
 * works on any WordPress site and simply skips whatever is not installed. A
 * site without the wishlist plugin gets a two item menu and no errors.
 *
 * Idempotent. Run it twice and nothing is duplicated: an existing menu of the
 * same name is reused, and a page already in the menu is left alone rather
 * than added a second time.
 *
 * Checkout is deliberately not in the list. It is reached from the cart, and a
 * header link to it lands a customer on an empty checkout, which is a dead
 * end. If it is wanted it is now one tick in Appearance > Menus.
 *
 * The labels match the page titles rather than being reworded. "Basket" reads
 * better on a UK trade site, but the cart page, the cart block and every
 * WooCommerce notice all say "Cart", so renaming it in the header alone would
 * only look like an inconsistency. That is a content decision for the client.
 *
 * Usage:
 *   php provision_top_menu.php --dry-run
 *   php provision_top_menu.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts    = getopt('', ['dry-run']);
$dry_run = isset($opts['dry-run']);

$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

const DT_MENU_NAME     = 'Top Menu';
const DT_MENU_LOCATION = 'secondary';

/**
 * The pages to place, in the order they should appear.
 *
 * Each is looked up by the option the owning plugin writes, falling back to
 * the slug. The option is tried first because a store can move its account
 * page to a different post and the option is what WooCommerce actually
 * follows; the slug is only there for a site where the option was never set.
 */
$wanted = [
    ['label' => 'My account', 'option' => 'woocommerce_myaccount_page_id', 'slug' => 'my-account'],
    ['label' => 'Wishlist',   'option' => 'yith_wcwl_wishlist_page_id',    'slug' => 'wishlist'],
    ['label' => 'Cart',       'option' => 'woocommerce_cart_page_id',      'slug' => 'cart'],
];

echo ($dry_run ? "DRY RUN - no writes\n\n" : "LIVE\n\n");

// Resolve first, so a missing page is reported before anything is created.
$resolved = [];
foreach ($wanted as $w) {
    $id   = (int) get_option($w['option']);
    $page = $id ? get_post($id) : null;

    if (!$page) {
        $page = get_page_by_path($w['slug']);
    }
    if (!$page || $page->post_status !== 'publish') {
        echo "  skip  {$w['label']} - no published page (option {$w['option']}, slug /{$w['slug']})\n";
        continue;
    }

    $resolved[] = ['label' => $w['label'], 'page' => $page];
    echo "  found {$w['label']} -> #{$page->ID} '{$page->post_title}' /{$page->post_name}\n";
}

if (!$resolved) {
    echo "\nNothing to place. Stopping without touching the menus.\n";
    exit(1);
}

// Reuse an existing menu of this name rather than making a second one with a
// suffixed slug, which is what wp_create_nav_menu would silently do.
$menu = wp_get_nav_menu_object(DT_MENU_NAME);

if ($menu) {
    echo "\nmenu '" . DT_MENU_NAME . "' already exists (#{$menu->term_id})\n";
} elseif ($dry_run) {
    echo "\n  [dry] would create menu '" . DT_MENU_NAME . "'\n";
} else {
    $menu_id = wp_create_nav_menu(DT_MENU_NAME);
    if (is_wp_error($menu_id)) {
        die('! could not create menu: ' . $menu_id->get_error_message() . "\n");
    }
    $menu = wp_get_nav_menu_object($menu_id);
    echo "\nmenu created (#{$menu->term_id})\n";
}

// Which pages are in there already, by the post they point at. Comparing on
// object_id and not on title means a renamed menu item is still recognised.
$existing = [];
if ($menu) {
    foreach (wp_get_nav_menu_items($menu->term_id) ?: [] as $item) {
        if ($item->type === 'post_type') {
            $existing[(int) $item->object_id] = $item->title;
        }
    }
}

$added = $already = 0;
$order = count($existing);

foreach ($resolved as $r) {
    $page = $r['page'];

    if (isset($existing[$page->ID])) {
        echo "  have  {$r['label']} (already in the menu as '{$existing[$page->ID]}')\n";
        $already++;
        continue;
    }
    if ($dry_run || !$menu) {
        echo "  [dry] would add {$r['label']}\n";
        $added++;
        continue;
    }

    $res = wp_update_nav_menu_item($menu->term_id, 0, [
        'menu-item-title'     => $r['label'],
        'menu-item-object'    => 'page',
        'menu-item-object-id' => $page->ID,
        'menu-item-type'      => 'post_type',
        'menu-item-status'    => 'publish',
        'menu-item-position'  => ++$order,
    ]);

    if (is_wp_error($res)) {
        echo "  !     {$r['label']}: " . $res->get_error_message() . "\n";
        continue;
    }
    echo "  added {$r['label']}\n";
    $added++;
}

// Merge into the location map. Assigning the whole array back would drop
// Primary, which is the menu the client has actually built.
$locations = get_theme_mod('nav_menu_locations', []);
$current   = $locations[DT_MENU_LOCATION] ?? 0;

if ($menu && (int) $current === (int) $menu->term_id) {
    echo "\nlocation '" . DT_MENU_LOCATION . "' already points at this menu\n";
} elseif ($dry_run || !$menu) {
    echo "\n  [dry] would assign to location '" . DT_MENU_LOCATION . "'"
        . ($current ? " (replacing #{$current})" : '') . "\n";
} else {
    $locations[DT_MENU_LOCATION] = $menu->term_id;
    set_theme_mod('nav_menu_locations', $locations);
    echo "\nassigned to location '" . DT_MENU_LOCATION . "'"
        . ($current ? " (was #{$current})" : '') . "\n";
}

echo "\nadded=$added | already present=$already\n";
echo "Edit it in Appearance > Menus, under '" . DT_MENU_NAME . "'.\n";
