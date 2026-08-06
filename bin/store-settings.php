<?php
/**
 * Apply the store settings this project has deliberately chosen.
 *
 * Everything WooCommerce keeps in the options table lives in the database, not
 * in git, so it does not travel with a deploy. Configured by hand it has to be
 * repeated identically on local, dev and live, which is where a box quietly goes
 * unticked on one of the three and nobody finds out until a customer does.
 *
 * This file is the record of those choices, and re-running it on any of the
 * three environments makes that environment match, or reports what differs.
 *
 * It is NOT a mirror of every WooCommerce setting. Only settings this project
 * made a decision about belong here. Anything absent is left exactly as it is.
 *
 *   php bin/store-settings.php            show what differs, change nothing
 *   php bin/store-settings.php --apply    write the differences
 *
 * @package dorotape
 */

declare(strict_types=1);

// This directory is inside the webroot, because deploy is `git pull` in
// public_html. .htaccess denies it over HTTP; this is the guard that still holds
// if that file is ever lost or the server ignores it.
if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

/**
 * Settings, as [option => [value, reason]].
 *
 * The reason is the point of the file. A bare list of option names is no better
 * than the admin screens it replaces - the question in six months is never what
 * the value is, it is why it was chosen.
 */
$settings = [
    // ── DR-5: customer registration and login ───────────────────────────────
    'woocommerce_enable_myaccount_registration' => [
        'yes',
        'Spec: customers register directly, no manual approval. Was "no", which '
        . 'left the My Account page with a login form and no way to sign up.',
    ],
    'woocommerce_enable_signup_and_login_from_checkout' => [
        'yes',
        'A first order should be able to create the account that carries it, '
        . 'rather than sending a new trade customer back to register and start again.',
    ],
    'woocommerce_registration_generate_password' => [
        'no',
        'The customer sets their own password on the form. Auto-generating one '
        . 'emails it, and transactional email is not working yet (DR-7), so every '
        . 'registration would produce an account whose password never arrives.',
    ],
    'woocommerce_enable_checkout_login_reminder' => [
        'yes',
        '10,242 customers were migrated from Kryptronic and their passwords still '
        . 'work. Without the reminder a returning customer sees a blank checkout, '
        . 'registers again, and the duplicate has none of their history or pricing.',
    ],

    // ── DR-9: purchase order numbers ────────────────────────────────────────
    // The theme owns this field. inc/purchase-order.php has the full reasoning;
    // the short version is that Invoice Gateway renders its PO field inside its
    // own payment box, so it exists only for customers paying on account and a
    // card or BACS order could never carry one. Both of these stay off so there
    // is exactly one PO field on the site.
    'igfw_enable_purchase_order_number' => [
        'no',
        'Invoice Gateway PO field, superseded by the theme field, which every '
        . 'payment method gets rather than only pay-on-account. Two fields '
        . 'collecting one value means half the POs never reach Sage.',
    ],
    'igfw_require_purchase_order_number' => [
        'no',
        'Moot while the field above is off, but set explicitly so switching that '
        . 'one on by accident cannot also make a hidden field mandatory and block '
        . 'checkout.',
    ],
];

/**
 * Deliberately NOT set here, so nobody has to re-derive why:
 *
 *  users_can_register (core)        stays 0. WooCommerce registration does not
 *                                   read it, so leaving it off keeps wp-login.php
 *                                   closed without costing anything.
 *  woocommerce_enable_guest_checkout stays as found. Forcing account creation is
 *                                   a commercial decision, not a technical one.
 *  woocommerce_registration_generate_username
 *                                   stays "yes": the form asks for an email and
 *                                   derives the username, which is one less field.
 *  Woosage settings                 not here. They live inside the single
 *                                   `woosage_settings` option as an array, not as
 *                                   one option each, so this file's get/update
 *                                   pattern does not reach them. The plugin is
 *                                   also inactive. DR-15 turns it on and must set
 *                                   use_po_ref_for_customer_order_num on, which is
 *                                   what puts the PO in Sage's Customer Order No.,
 *                                   and must leave purchase_order_checkout_field_label
 *                                   empty, which is what keeps Woosage from adding
 *                                   a second PO field.
 */

// ── Bootstrap ───────────────────────────────────────────────────────────────
// Climb to wp-load.php rather than hardcoding a path, so this runs unchanged on
// a server whose directory layout is not the local one.
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

$apply = in_array('--apply', $argv, true);

echo home_url() . "\n";
echo $apply ? "APPLY\n\n" : "DRY RUN, nothing will be written. Add --apply to write.\n\n";

$changed = 0;
$already = 0;

foreach ($settings as $option => [$wanted, $reason]) {
    $current = get_option($option);

    if ($current === $wanted) {
        printf("  ok    %-52s %s\n", $option, $wanted);
        $already++;
        continue;
    }

    printf(
        "  %s %-52s %s -> %s\n",
        $apply ? 'SET  ' : 'diff ',
        $option,
        var_export($current, true),
        $wanted
    );
    echo '        ' . wordwrap($reason, 92, "\n        ") . "\n";

    if ($apply) {
        update_option($option, $wanted);
        $changed++;
    }
}

echo "\n";
if ($apply) {
    printf("%d changed, %d already correct.\n", $changed, $already);
} else {
    printf("%d would change, %d already correct.\n", count($settings) - $already, $already);
}
