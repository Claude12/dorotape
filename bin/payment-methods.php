<?php
/**
 * Apply the payment method settings this project has chosen. (DR-4)
 *
 * Third of the same family as bin/store-settings.php and bin/email-settings.php,
 * for the same reason: WooCommerce keeps all of this in the options table, so it
 * does not travel with a deploy and has to be repeated identically on local, dev
 * and live. A gateway configured by hand on one environment is a gateway
 * configured differently everywhere else.
 *
 * Payment gets its own file rather than more rows in store-settings.php because
 * gateway settings are serialised arrays nested inside one option each, the
 * Invoice Gateway keeps half its configuration in loose options outside its own
 * settings array, and one of the values here decides whether a gateway may be
 * switched on at all.
 *
 *   php bin/payment-methods.php            show what differs, change nothing
 *   php bin/payment-methods.php --apply    write the differences
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

// ── Bootstrap ───────────────────────────────────────────────────────────────
// Climb to wp-load.php rather than hardcoding a path, so this runs unchanged on
// a server whose directory layout is not the local one.
//
// Not named $wp. WordPress keeps its own global by that name, and overwriting it
// here fatals inside create_initial_taxonomies() before wp-load even returns.
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
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$apply = in_array('--apply', $argv, true);

/**
 * Dorotape's bank details, for BACS.
 *
 * Deliberately empty in the repository. These are not secret - they are printed
 * on every invoice and shown to any customer who picks bank transfer - but this
 * repository is public today, and a sort code and account number sitting in a
 * public git history is the raw material for an invoice redirection fraud
 * against the client's own customers. Making the repository private is already
 * a pre-launch item; fill these in after that, not before.
 *
 * Until they are filled in, BACS is switched OFF further down rather than left
 * on. See the reason there.
 */
$bacs_account = [
    'account_name'   => '',
    'account_number' => '',
    'sort_code'      => '',
    'bank_name'      => '',
    // Optional, and only needed if Dorotape are ever paid from outside the UK.
    'iban'           => '',
    'bic'            => '',
];

/**
 * Can BACS be offered at all?
 *
 * WooCommerce prints whichever of the account fields are filled and silently
 * prints nothing when they are all empty, so an enabled-but-unconfigured BACS
 * renders a payment option that tells the customer to make a bank transfer and
 * then does not say to whom. The order is placed, the money never arrives, and
 * the first anyone knows is a chase call.
 */
$bacs_required = ['account_name', 'account_number', 'sort_code', 'bank_name'];
$bacs_ready    = true;
foreach ($bacs_required as $field) {
    if (trim((string) $bacs_account[$field]) === '') {
        $bacs_ready = false;
    }
}

/**
 * The address the shop speaks as. Same value as bin/email-settings.php, which
 * explains where it comes from.
 */
const DOROTAPE_SALES_EMAIL = 'sales@dorotape.co.uk';

/**
 * Gateway settings, as [option => [gateway label, [key => [value, reason]]]].
 *
 * Each gateway keeps its settings in one serialised array. Only the keys named
 * here are written: WC_Settings_API merges a stored array over its form field
 * defaults, so writing three keys leaves every other setting on that gateway at
 * its default rather than blanking it.
 */
$gateways = [
    // ── Bank transfer ───────────────────────────────────────────────────────
    'woocommerce_bacs_settings' => [
        'BACS - Direct bank transfer',
        array_merge(
            [
                'enabled' => [
                    $bacs_ready ? 'yes' : 'no',
                    $bacs_ready
                        ? 'Bank details are present, so the method can be offered.'
                        : 'Currently ON with every bank detail field empty, which is '
                        . 'the worst of the three possible states. A customer can pick '
                        . '"Direct bank transfer" today, complete the order, and be '
                        . 'shown no account to pay into. Off until $bacs_account at the '
                        . 'top of this file is filled in, at which point this flips to '
                        . 'yes on its own.',
                ],
                'title' => [
                    'Bank transfer (BACS)',
                    'Trade customers pay by BACS and call it that. WooCommerce\'s '
                    . 'default "Direct bank transfer" is the same thing described to '
                    . 'someone who has never done it.',
                ],
            ],
            // The account fields themselves, each carrying its own reason only
            // when it is empty, so the dry run names what is missing.
            array_map(
                static fn($value) => [$value, 'From the client. See $bacs_account at the top of this file.'],
                $bacs_account
            )
        ),
    ],

    // ── Cash on delivery, repurposed as payment on collection ───────────────
    'woocommerce_cod_settings' => [
        'COD - payment on collection',
        [
            'enabled' => [
                'yes',
                'Stays on, but see enable_for_methods below. On its own this row is '
                . 'not the decision.',
            ],
            'title' => [
                'Pay on collection',
                'Was "Cash on delivery". Nobody delivering vinyl collects cash at the '
                . 'door, and DR-14 has just added Local Pickup, which is the one case '
                . 'where paying on the spot is real.',
            ],
            'description' => [
                'Pay when you collect your order from us.',
                'Was "Pay with cash upon delivery", which describes something this '
                . 'shop does not do.',
            ],
            'instructions' => [
                'Please pay when you collect. We accept card and cash.',
                'Shown on the order confirmation and in the customer email, so it is '
                . 'the last thing they read before turning up.',
            ],
            'enable_for_methods' => [
                ['local_pickup'],
                'The change that matters. This was empty, meaning cash on delivery was '
                . 'offered on every order and every shipping method: a customer could '
                . 'order several thousand pounds of material for nationwide delivery '
                . 'and undertake to pay the courier in cash. Restricting it to Local '
                . 'Pickup turns it into what it should be. Bare "local_pickup" rather '
                . 'than an instance id, so it covers the method wherever DR-3 ends up '
                . 'putting it.',
            ],
            'enable_for_virtual' => [
                'no',
                'Was yes. A virtual order has nothing to collect, so there is no '
                . 'moment at which this method makes sense.',
            ],
        ],
    ],

    // ── Pay on account ──────────────────────────────────────────────────────
    // The gateway DR-11 gates. Until this is enabled, DR-11 cannot be
    // demonstrated either way: the method is unavailable to every customer,
    // approved or not, and for a reason that has nothing to do with the gate.
    'woocommerce_igfw_invoice_gateway_settings' => [
        'Invoice Gateway - pay on account',
        [
            'enabled' => [
                'yes',
                'Never configured, so no settings row existed at all and the gateway '
                . 'defaulted off. This is what has been blocking DR-11 from being '
                . 'shown to work.',
            ],
            'title' => [
                'Pay on account',
                'Was "Invoice Payment". "Pay on account" is what a trade customer with '
                . 'credit terms calls it, and it matches the language in the ticket '
                . 'and on the My Account screens.',
            ],
            'description' => [
                'Pay against your account. We will invoice you and payment is due within '
                . '30 days.',
                'Says the terms at the point of choosing, rather than leaving the '
                . 'customer to find out when the invoice arrives. Kept consistent with '
                . 'igfw_payment_terms_days below.',
            ],
            'instructions' => [
                'Your invoice will follow by email. Payment is due within 30 days.',
                'Shown after the order is placed and in the confirmation email.',
            ],
            'enable_for_virtual' => [
                'yes',
                'Already the default; pinned so it survives a plugin update changing '
                . 'its mind.',
            ],
        ],
    ],
];

/**
 * Invoice Gateway options that live outside its settings array.
 *
 * The plugin keeps its feature switches as loose options rather than gateway
 * settings, so they cannot join the table above.
 */
$igfw_options = [
    'igfw_enable_payment_terms' => [
        'yes',
        'The "including terms" half of DR-4. Off, so an invoice carried no due date '
        . 'and "pay on account" meant pay whenever. On, orders get a Net 30 due date '
        . 'that Sage can chase against in Stage 3.',
    ],
    'igfw_payment_terms_days' => [
        '30',
        'Net 30. Already the plugin\'s default, pinned here because the gateway '
        . 'description above states 30 days in words: if one moves and the other '
        . 'does not, the shop promises one thing and invoices another.',
    ],
    'igfw_default_order_status' => [
        'processing',
        'Already correct, pinned deliberately. An order paid on account is an order '
        . 'to be picked and despatched now, and invoiced after. Anything else here '
        . 'leaves approved trade orders sitting unfulfilled waiting for money that '
        . 'is not due for a month.',
    ],
    'igfw_enable_purchase_order_number' => [
        'no',
        'Deliberately left OFF, against the wording of the ticket. DR-9 already '
        . 'collects a purchase order number at checkout, for every payment method, '
        . 'and writes it to the meta key Woosage reads so it reaches Sage without '
        . 'mapping code. Switching this on adds a SECOND purchase order field inside '
        . 'the invoice payment panel - the plugin supports block checkout, so it does '
        . 'render - storing it under a different meta key that Sage never reads. Two '
        . 'fields collecting one value, one of which silently goes nowhere. If this '
        . 'is ever wanted instead of DR-9\'s field, remove DR-9\'s registration in '
        . 'inc/purchase-order.php rather than running both.',
    ],
];

// ── Report ──────────────────────────────────────────────────────────────────

echo home_url() . "\n";
echo $apply ? "APPLY\n\n" : "DRY RUN, nothing will be written. Add --apply to write.\n\n";

$already = 0;
$pending = 0;

// Counted whether or not we are writing, so a dry run can say how much work it
// found rather than reporting the zero things it did.
$diffs = 0;

/** One-line rendering, so an array setting does not wrap the diff over four lines. */
$show = static function ($value): string {
    if ($value === null) {
        return 'unset';
    }
    if (is_array($value)) {
        return $value === [] ? '[]' : '[' . implode(', ', array_map(static fn($v) => var_export($v, true), $value)) . ']';
    }
    return var_export($value, true);
};

/**
 * Compare two stored settings values.
 *
 * enable_for_methods is an array, and everything else is a string that may have
 * been stored as one of several equivalent things. Comparing loosely here would
 * report '' and null as different on every run.
 */
$same = static function ($current, $wanted): bool {
    if (is_array($wanted) || is_array($current)) {
        return is_array($current) && is_array($wanted)
            && array_values($current) === array_values($wanted);
    }
    return (string) $current === (string) $wanted;
};

foreach ($gateways as $option => [$label, $keys]) {
    printf("%s\n", $label);

    $stored = get_option($option, []);
    $stored = is_array($stored) ? $stored : [];
    $write  = $stored;
    $dirty  = false;

    foreach ($keys as $key => [$wanted, $reason]) {
        $current = $stored[$key] ?? null;

        if ($same($current, $wanted)) {
            printf("  ok    %-22s %s\n", $key, $show($wanted));
            $already++;
            continue;
        }

        printf(
            "  %s %-22s %s -> %s\n",
            $apply ? 'SET  ' : 'diff ',
            $key,
            $show($current),
            $show($wanted)
        );
        echo '        ' . wordwrap($reason, 88, "\n        ") . "\n";

        $write[$key] = $wanted;
        $dirty = true;
        $diffs++;
    }

    if ($dirty && $apply) {
        update_option($option, $write);
    }

    echo "\n";
}

echo "Invoice Gateway feature switches\n";

foreach ($igfw_options as $option => [$wanted, $reason]) {
    $current = get_option($option);

    if ((string) $current === (string) $wanted) {
        printf("  ok    %-38s %s\n", $option, $wanted);
        $already++;
        continue;
    }

    printf(
        "  %s %-38s %s -> %s\n",
        $apply ? 'SET  ' : 'diff ',
        $option,
        $show($current === false ? null : $current),
        $show($wanted)
    );
    echo '        ' . wordwrap($reason, 88, "\n        ") . "\n";

    $diffs++;

    if ($apply) {
        update_option($option, $wanted);
    }
}

// ── What this file cannot do ────────────────────────────────────────────────

echo "\nStill outstanding\n";

if (!$bacs_ready) {
    $missing = [];
    foreach ($bacs_required as $field) {
        if (trim((string) $bacs_account[$field]) === '') {
            $missing[] = $field;
        }
    }
    printf("  BLOCKED  BACS is off. Needs from the client: %s\n", implode(', ', $missing));
    echo "           Fill in \$bacs_account at the top of this file and re-run.\n";
    echo "           Not before the repository is private: see the note there.\n";
    $pending++;
}

// A card gateway is the one thing in DR-4 that cannot be done from this
// repository at all. Deploy is `git pull` of the theme, so it moves no plugins,
// and neither Stripe nor Elavon is installed to configure.
$card = [
    'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php' => 'Stripe',
    'woocommerce-gateway-elavon/woocommerce-gateway-elavon.php' => 'Elavon Converge',
];
$card_found = false;
foreach ($card as $file => $name) {
    if (file_exists(WP_PLUGIN_DIR . '/' . $file)) {
        $card_found = true;
        printf(
            "  %s %s is installed but not configured here. Sandbox keys go in its own settings screen.\n",
            is_plugin_active($file) ? 'TODO    ' : 'INACTIVE',
            $name
        );
        $pending++;
    }
}
if (!$card_found) {
    echo "  BLOCKED  No card gateway installed. DR-4 asks for Elavon or Stripe in\n";
    echo "           sandbox, and neither plugin is present. Installing one is a\n";
    echo "           plugin decision and a credentials request, not a theme change,\n";
    echo "           so it cannot happen in this repository.\n";
    $pending++;
}

// Legacy PayPal Standard. Present as an option row, disabled, and best left so.
$paypal = get_option('woocommerce_paypal_settings', []);
if (is_array($paypal) && ($paypal['enabled'] ?? 'no') === 'no') {
    echo "  note     PayPal Standard is present and off. Left off: it is the retired\n";
    echo "           gateway, closed to new accounts, and superseded by PayPal Payments.\n";
}

if ($apply) {
    printf(
        "\nApplied: %d settings written, %d already correct, %d outstanding\n",
        $diffs,
        $already,
        $pending
    );
} else {
    printf(
        "\nDry run: %d settings would change, %d already correct, %d outstanding\n",
        $diffs,
        $already,
        $pending
    );
    if ($diffs > 0) {
        echo "Add --apply to write the differences above.\n";
    }
}
