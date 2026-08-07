<?php
/**
 * Apply the transactional email settings this project has chosen. (DR-7)
 *
 * Companion to bin/store-settings.php, and the same idea: WooCommerce keeps
 * this in the options table, so it does not travel with a deploy and has to be
 * repeated identically on local, dev and live.
 *
 * Email gets its own file rather than more rows in store-settings.php because
 * three of the things below cannot be expressed as "option => value". The
 * header image is computed from whatever the site logo is on THIS environment,
 * the internal recipients live nested inside per-email arrays, and the SMTP
 * check reads constants rather than options.
 *
 * What this does NOT do is put SMTP credentials anywhere. See the report this
 * prints at the end.
 *
 *   php bin/email-settings.php            show what differs, change nothing
 *   php bin/email-settings.php --apply    write the differences
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
 * The address the shop speaks as.
 *
 * Not a guess and not a placeholder. The old Kryptronic system carried 49
 * transactional email templates, and in every one of them that goes to a
 * customer the sender is sales@dorotape.co.uk, while every internal copy is
 * addressed to it. It is the address their customers already recognise and the
 * inbox their staff already watch.
 */
const DOROTAPE_SALES_EMAIL = 'sales@dorotape.co.uk';

/**
 * Brand pink, taken from --dt-accent in css/scaffold.css.
 *
 * Kept as a literal rather than parsed out of the stylesheet: an email client
 * cannot read CSS variables, so this value has to be inlined somewhere no
 * matter what, and a silent mismatch is better caught by eye than by a regex
 * that quietly stops matching when the stylesheet is reformatted.
 */
const DOROTAPE_BRAND_COLOR = '#d7328a';

// The logo as this environment addresses it. Local, dev and live each store
// their own uploads URL, so a hardcoded value would be wrong on two of the
// three. Empty is handled: an empty header image is WooCommerce's own default
// and simply renders the site title instead.
$logo_id  = (int) get_theme_mod('custom_logo');
$logo_url = $logo_id ? (string) wp_get_attachment_url($logo_id) : '';

/**
 * Settings, as [option => [value, reason]].
 *
 * The reason is the point of the file, exactly as in store-settings.php.
 */
$settings = [
    // ── Who the mail comes from ─────────────────────────────────────────────
    'woocommerce_email_from_address' => [
        DOROTAPE_SALES_EMAIL,
        'Was csachinda@gmail.com, the developer\'s personal Gmail. Live, every '
        . 'order confirmation would have arrived from a stranger\'s address, and '
        . 'replies would have gone to someone who cannot answer them.',
    ],
    'woocommerce_email_from_name' => [
        'Dorotape',
        'Never set, so it fell back to the site title. That happens to be right '
        . 'today, which is exactly why it is worth pinning: renaming the site '
        . 'should not silently rename the sender on 3,000 order emails.',
    ],

    // ── Branding ────────────────────────────────────────────────────────────
    'woocommerce_email_auto_sync_with_theme' => [
        'no',
        'Must be off, and this is the setting that makes the rest of this '
        . 'section stick. With it on, WooCommerce re-derives the email colours '
        . 'from the theme on customize_save_after and on any global styles save. '
        . 'This is a classic theme with no theme.json, so what it derives is '
        . 'WooCommerce\'s own defaults, which is where the #8526ff purple below '
        . 'came from. Left on, the next person to save anything in the '
        . 'Customizer would silently un-brand every email.',
    ],
    'woocommerce_email_base_color' => [
        DOROTAPE_BRAND_COLOR,
        'Was #8526ff, WooCommerce purple, which appears nowhere on the site. '
        . 'This is --dt-accent, the pink in the logo.',
    ],
    'woocommerce_email_header_image' => [
        $logo_url,
        'Was empty, so emails led with the site title as plain text. Resolved '
        . 'from the site logo on this environment rather than hardcoded, since '
        . 'local, dev and live each serve uploads from a different host.',
    ],
    'woocommerce_email_header_image_width' => [
        '175',
        'The logo is 350px wide. Half of that renders it at 2x density, so it '
        . 'stays sharp on a phone and the strapline stays readable. '
        . 'WooCommerce\'s default of 120 would shrink a wordmark with a '
        . 'strapline under it into an illegible smudge.',
    ],
    'woocommerce_email_footer_text' => [
        '{site_title}<br />{store_address}<br />' . DOROTAPE_SALES_EMAIL,
        'Adds the sales address to the site title and postal address already '
        . 'there. A customer who wants to reply about an order should not have '
        . 'to work out how, and trade customers reply to order emails constantly.',
    ],

    // ── Where the shop\'s own copies go ──────────────────────────────────────
    'woocommerce_stock_email_recipient' => [
        DOROTAPE_SALES_EMAIL,
        'Low stock and out of stock alerts. Defaulted to admin_email, so stock '
        . 'warnings for the client\'s warehouse would have gone to the '
        . 'developer\'s Gmail and nowhere else.',
    ],
];

/**
 * Internal recipients, as [email id => reason].
 *
 * These live inside a serialised array per email rather than as options of
 * their own, which is why they cannot join the table above. Every one of them
 * currently has no stored value at all, so WooCommerce falls back to
 * admin_email, and admin_email is the developer.
 *
 * Only the key being set is written. WC_Settings_API merges a stored array over
 * its form field defaults, so an array holding one key leaves every other
 * setting on that email at its default rather than blanking it.
 */
$recipients = [
    'new_order'       => 'The order notification. If this one is wrong nobody at Dorotape learns an order exists.',
    'cancelled_order' => 'A cancellation usually needs someone to stop a despatch, so it has to reach the people who pack.',
    'failed_order'    => 'A failed payment is a sale about to be lost. Worth a human seeing it the same day.',
];

/**
 * wp-mail-smtp's own sender, as [key => [value, reason]] inside its `mail` group.
 *
 * This is not optional tidying. The plugin hooks wp_mail_from at PHP_INT_MAX,
 * and out of the box `from_email_force` defaults to TRUE with `from_email`
 * defaulting to admin_email. So merely activating it overrides everything
 * WooCommerce decided and sends the whole site's mail from admin_email, which
 * here is the developer's Gmail. Activating without setting these is worse than
 * leaving the plugin off.
 */
$smtp_mail = [
    'from_email' => [
        DOROTAPE_SALES_EMAIL,
        'Defaults to admin_email, and is FORCED over everything else. Left '
        . 'alone, activating this plugin silently undoes woocommerce_email_'
        . 'from_address above.',
    ],
    'from_name' => [
        'Dorotape',
        'The fallback name for mail that is not WooCommerce\'s, such as WPForms '
        . 'and WordPress core notices.',
    ],
    'from_email_force' => [
        true,
        'Stays on, deliberately. Every mail leaving the site should come from '
        . 'the domain the server is authorised to send as, or SPF and DKIM fail '
        . 'and it is filed as spam. WPForms is the case that matters: a contact '
        . 'form that sends as the visitor\'s own address fails alignment every '
        . 'time. Forcing costs nothing here because the forced value and '
        . 'WooCommerce\'s value are the same address.',
    ],
    'from_name_force' => [
        false,
        'Off, so WooCommerce can still name a specific email if it ever needs '
        . 'to. The name has no bearing on deliverability, unlike the address.',
    ],
];

/**
 * Deliberately NOT set here, so nobody has to re-derive why:
 *
 *  admin_email (core)               stays as it is. WordPress emails a
 *                                   confirmation link to the NEW address and
 *                                   does not switch until someone clicks it, so
 *                                   a script cannot honestly claim to have
 *                                   changed it. It also carries core update and
 *                                   fatal error notices, which the developer
 *                                   should keep receiving on dev. Changing it is
 *                                   a go-live step, done by hand, and every
 *                                   WooCommerce recipient above is set
 *                                   explicitly precisely so nothing depends on
 *                                   it in the meantime.
 *  admin_payment_gateway_enabled    keeps going to admin_email, and has no
 *                                   recipient setting of its own. It fires when
 *                                   someone switches a payment gateway on,
 *                                   which is a site-admin event rather than a
 *                                   sales one. Whoever holds admin_email is the
 *                                   right person to notice it.
 *  woocommerce_email_reply_to_*     stays off. From is already a monitored
 *                                   mailbox, so a reply-to pointing at the same
 *                                   address would add a header and change
 *                                   nothing.
 *  SMTP credentials                 never here, and never in the database. The
 *                                   report below explains where they go.
 *  Per-email subjects and headings  stay at WooCommerce's defaults. Rewriting
 *                                   them is copy, not configuration, and the
 *                                   client has not been asked for it.
 */

echo home_url() . "\n";
echo $apply ? "APPLY\n\n" : "DRY RUN, nothing will be written. Add --apply to write.\n\n";

$changed = 0;
$already = 0;
$pending = 0;

// ── Plugin ──────────────────────────────────────────────────────────────────
// wp-mail-smtp ships with this site but has never been activated. Activating it
// is safe only alongside the sender block further down, and the order matters:
// the plugin's unconfigured defaults force every email to come from admin_email,
// so activating it on its own would undo the from address set above rather than
// leave things as they were. Both happen in the same run.
//
// Delivery itself does not change here. With no credentials it still uses PHP
// mail(), exactly as the site does today. This is so the environment is ready
// for whoever holds the mailbox details.
echo "wp-mail-smtp\n";

$smtp_plugin = 'wp-mail-smtp/wp_mail_smtp.php';

if (!file_exists(WP_PLUGIN_DIR . '/' . $smtp_plugin)) {
    printf("  MISSING %s is not installed\n\n", $smtp_plugin);
    $pending++;
} elseif (is_plugin_active($smtp_plugin)) {
    echo "  ok    active\n\n";
    $already++;
} else {
    printf("  %s inactive -> active\n", $apply ? 'ON   ' : 'diff ');
    if ($apply) {
        $error = activate_plugin($smtp_plugin);
        if (is_wp_error($error)) {
            printf("  FAILED %s\n", $error->get_error_message());
        } else {
            $changed++;
        }
    }
    echo "\n";
}

// ── Options ─────────────────────────────────────────────────────────────────
echo "Settings\n";

foreach ($settings as $option => [$wanted, $reason]) {
    $current = get_option($option);

    if ((string) $current === (string) $wanted) {
        printf("  ok    %-46s %s\n", $option, $wanted === '' ? "''" : $wanted);
        $already++;
        continue;
    }

    printf(
        "  %s %-46s %s -> %s\n",
        $apply ? 'SET  ' : 'diff ',
        $option,
        var_export($current, true),
        var_export($wanted, true)
    );
    echo '        ' . wordwrap($reason, 92, "\n        ") . "\n";

    if ($apply) {
        update_option($option, $wanted);
        $changed++;
    }
}

// ── Nested recipients ───────────────────────────────────────────────────────
echo "\nInternal recipients\n";

foreach ($recipients as $email_id => $reason) {
    $option  = 'woocommerce_' . $email_id . '_settings';
    $stored  = get_option($option, []);
    $stored  = is_array($stored) ? $stored : [];
    $current = $stored['recipient'] ?? null;

    if ($current === DOROTAPE_SALES_EMAIL) {
        printf("  ok    %-46s %s\n", $email_id, DOROTAPE_SALES_EMAIL);
        $already++;
        continue;
    }

    // Show what it actually resolves to today, not just that it is unset.
    // "null" reads as harmless; "csachinda@gmail.com" reads as the problem.
    $effective = $current === null || $current === ''
        ? sprintf("unset, so admin_email (%s)", get_option('admin_email'))
        : $current;

    printf(
        "  %s %-46s %s -> %s\n",
        $apply ? 'SET  ' : 'diff ',
        $email_id,
        $effective,
        DOROTAPE_SALES_EMAIL
    );
    echo '        ' . wordwrap($reason, 92, "\n        ") . "\n";

    if ($apply) {
        $stored['recipient'] = DOROTAPE_SALES_EMAIL;
        update_option($option, $stored);
        $changed++;
    }
}

// ── wp-mail-smtp's sender ───────────────────────────────────────────────────
echo "\nwp-mail-smtp sender\n";

$smtp_stored = get_option('wp_mail_smtp', []);
$smtp_stored = is_array($smtp_stored) ? $smtp_stored : [];
$smtp_dirty  = false;

foreach ($smtp_mail as $key => [$wanted, $reason]) {
    $current = $smtp_stored['mail'][$key] ?? null;

    if ($current === $wanted) {
        printf("  ok    %-46s %s\n", $key, var_export($wanted, true));
        $already++;
        continue;
    }

    // Say what it resolves to, not just that it is unset. The defaults are the
    // whole problem here, so "unset" would hide the finding.
    $effective = $current === null
        ? sprintf('unset, so %s', var_export(
            'from_email' === $key ? get_option('admin_email')
                : ('from_name' === $key ? get_bloginfo('name')
                : ('from_email_force' === $key ? true : false)),
            true
        ))
        : var_export($current, true);

    printf(
        "  %s %-46s %s -> %s\n",
        $apply ? 'SET  ' : 'diff ',
        $key,
        $effective,
        var_export($wanted, true)
    );
    echo '        ' . wordwrap($reason, 92, "\n        ") . "\n";

    if ($apply) {
        $smtp_stored['mail'][$key] = $wanted;
        $smtp_dirty = true;
        $changed++;
    }
}

if ($apply && $smtp_dirty) {
    update_option('wp_mail_smtp', $smtp_stored);
}

// ── SMTP report ─────────────────────────────────────────────────────────────
// Read only. Credentials are not this script's to hold: it lives in git, and
// git lives in public_html.
echo "\nSMTP delivery\n";

$constants = [
    'WPMS_ON'        => 'master switch, must be true for the rest to be read',
    'WPMS_MAILER'    => "'smtp' for a mail server, or a provider slug",
    'WPMS_SMTP_HOST' => 'mail server hostname',
    'WPMS_SMTP_PORT' => 'usually 587',
    'WPMS_SSL'       => "'tls' on 587, 'ssl' on 465",
    'WPMS_SMTP_AUTH' => 'true unless the server is IP allow-listed',
    'WPMS_SMTP_USER' => 'mailbox username',
    'WPMS_SMTP_PASS' => 'mailbox password or app password',
];

$missing = [];
foreach ($constants as $name => $note) {
    if (defined($name)) {
        // Never print the value. This output gets pasted into tickets.
        printf("  ok    %-18s defined\n", $name);
        continue;
    }
    $missing[] = $name;
    printf("  MISSING %-16s %s\n", $name, $note);
}

if ($missing) {
    $pending++;
    echo "\n";
    echo "  Mail is going out through PHP mail() with no authentication. On a live\n";
    echo "  site that means order emails land in spam or are dropped outright,\n";
    echo "  because the sending server has no right to send as dorotape.co.uk.\n\n";
    echo "  These go in wp-config.php on each server, ABOVE the \"stop editing\" line.\n";
    echo "  Not in this repository, not in the database, and not in the plugin's own\n";
    echo "  settings screen: deploy is `git pull` inside public_html, and the\n";
    echo "  database gets exported by All-in-One WP Migration. wp-config.php is the\n";
    echo "  one file that is neither.\n\n";
    echo "    define( 'WPMS_ON', true );\n";
    echo "    define( 'WPMS_MAILER', 'smtp' );\n";
    echo "    define( 'WPMS_SMTP_HOST', '' );\n";
    echo "    define( 'WPMS_SMTP_PORT', 587 );\n";
    echo "    define( 'WPMS_SSL', 'tls' );\n";
    echo "    define( 'WPMS_SMTP_AUTH', true );\n";
    echo "    define( 'WPMS_SMTP_USER', '' );\n";
    echo "    define( 'WPMS_SMTP_PASS', '' );\n\n";
    echo "  Whoever fills those in needs the mailbox details for "
        . DOROTAPE_SALES_EMAIL . ",\n  which only the client has.\n";
}

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n";
if ($apply) {
    printf("%d changed, %d already correct", $changed, $already);
} else {
    printf(
        "%d would change, %d already correct",
        count($settings) + count($recipients) + count($smtp_mail) + 1 - $already,
        $already
    );
}
echo $pending ? ", $pending needing someone else.\n" : ".\n";
