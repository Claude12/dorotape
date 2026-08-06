<?php
/**
 * Ensure Local Pickup exists as a shipping method, so orders can be collected.
 *
 * Shipping zones and methods live in WooCommerce's own tables, not the options
 * table and not git, so like bin/store-settings.php this does not travel with a
 * deploy and has to be run per environment.
 *
 * SCOPE. This adds one method and nothing else. It deliberately does not design
 * the zone layout, which is DR-3 (Shipping zones and delivery methods) and a
 * commercial decision about where Dorotape ships and at what price. There are
 * currently no zones at all on this site, so everything falls into WooCommerce's
 * built-in "Locations not covered by your other zones", and that is where the
 * method goes. When DR-3 builds a real zone structure it can move it; this
 * script will see a local_pickup already present and leave it alone.
 *
 *   php bin/shipping-collection.php            show what differs, change nothing
 *   php bin/shipping-collection.php --apply    create the method
 *
 * @package dorotape
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

$dir = __DIR__;
$wp_load = null;
while ($dir !== dirname($dir)) {
    if (file_exists($dir . '/wp-load.php')) {
        $wp_load = $dir . '/wp-load.php';
        break;
    }
    $dir = dirname($dir);
}
if ($wp_load === null) {
    exit("Could not find wp-load.php above " . __DIR__ . "\n");
}
require $wp_load;

if (!class_exists('WC_Shipping_Zones')) {
    exit("WooCommerce is not active.\n");
}

$apply = in_array('--apply', $argv, true);

echo home_url() . "\n";
echo $apply ? "APPLY\n\n" : "DRY RUN, nothing will be written. Add --apply to write.\n\n";

/** Every zone including the catch-all, which get_zones() omits. */
$zones = array_map(
    static fn(array $z): WC_Shipping_Zone => WC_Shipping_Zones::get_zone($z['zone_id']),
    WC_Shipping_Zones::get_zones()
);
$zones[] = WC_Shipping_Zones::get_zone(0);

echo "Current shipping setup:\n";
$existing = [];
foreach ($zones as $zone) {
    $methods = $zone->get_shipping_methods();
    printf("  %-46s %s\n", $zone->get_zone_name(), $methods ? '' : '(no methods)');
    foreach ($methods as $method) {
        printf("      %-18s enabled=%-4s %s\n", $method->id, $method->enabled, $method->get_title());
        if ($method->id === 'local_pickup') {
            $existing[] = $zone->get_zone_name();
        }
    }
}

echo "\n";

if ($existing !== []) {
    printf("Local Pickup already exists in: %s\nNothing to do.\n", implode(', ', $existing));
    exit(0);
}

$target = WC_Shipping_Zones::get_zone(0);

printf(
    "%s Local Pickup on \"%s\"\n",
    $apply ? 'ADDING' : 'would add',
    $target->get_zone_name()
);
echo "        Collection from the trade counter. Customers choosing it get the\n";
echo "        Ready for Collection journey in inc/collection.php.\n";

if (!$apply) {
    echo "\n1 would change.\n";
    exit(0);
}

$instance_id = $target->add_shipping_method('local_pickup');

if (!$instance_id) {
    exit("Failed to add the method.\n");
}

$target->save();

// Title it something a customer understands, and leave the cost at zero.
$method = WC_Shipping_Zones::get_shipping_method($instance_id);
if ($method) {
    $method->instance_settings['title'] = 'Collection from Dorotape';
    $method->instance_settings['cost']  = '0';
    update_option(
        $method->get_instance_option_key(),
        apply_filters('woocommerce_shipping_' . $method->id . '_instance_settings_values', $method->instance_settings, $method)
    );
}

printf("\nAdded, instance id %d. 1 changed.\n", $instance_id);
