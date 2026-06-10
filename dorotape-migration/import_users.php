<?php
/**
 * Kryptronic -> WooCommerce user import.
 *
 * Reads core_users_dataexport.csv and creates WP users with role "customer".
 * Passwords are carried over as the raw Kryptronic MD5 hash written directly
 * to wp_users.user_pass — WordPress validates legacy plain-MD5 hashes and
 * re-hashes them on the user's first login.
 *
 * Usage (CLI only):
 *   php import_users.php --dry-run          # parse + report, no DB writes
 *   php import_users.php --limit=20         # import first 20 eligible rows
 *   php import_users.php                    # full import
 *
 * Idempotent: rows whose email already exists in WP are skipped, so the
 * script can be re-run safely. Skips usergroups admin/superuser (staff
 * accounts are created manually) and rows without a valid email.
 *
 * Writes wc_users_import_log.csv next to this script.
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts    = getopt('', ['dry-run', 'limit::']);
$dry_run = isset($opts['dry-run']);
$limit   = isset($opts['limit']) ? (int) $opts['limit'] : 0;

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

wp_suspend_cache_invalidation(true);

$dir       = __DIR__;
$users_csv = $dir . '/core_users_dataexport.csv';
$log_csv   = $dir . '/wc_users_import_log.csv';

/* ---------- country name -> ISO2 map ---------- */
$country_map = [];
$ch = fopen($dir . '/core_country_dataexport.csv', 'r');
$hdr = fgetcsv($ch);
while (($row = fgetcsv($ch)) !== false) {
    $country_map[$row[0]] = $row[1];
}
fclose($ch);
$country_map['Not Listed'] = '';

function iso_country($name, $map)
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    $abb = $map[$name] ?? '';
    return ($abb === 'XX') ? '' : $abb;
}

/* Kryptronic exports occasionally contain Windows-1252 bytes. */
function to_utf8($v)
{
    return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
}

/* usergroup -> ACF customer_type */
$type_map = [
    'users'     => 'retail',
    'wholesale' => 'wholesale',
    'reseller'  => 'trade',
];

/* ---------- main loop ---------- */
$fh  = fopen($users_csv, 'r');
$hdr = fgetcsv($fh);
$col = array_flip($hdr);

$log = fopen($log_csv, 'w');
fputcsv($log, ['email', 'status', 'detail']);

$seen_logins = [];
$n_created = $n_skipped_staff = $n_skipped_exists = $n_skipped_invalid = $n_errors = 0;
$processed = 0;

while (($row = fgetcsv($fh)) !== false) {
    $email     = strtolower(trim(to_utf8($row[$col['id']])));
    $usergroup = trim($row[$col['usergroup']]);

    if (in_array($usergroup, ['admin', 'superuser'], true)) {
        $n_skipped_staff++;
        fputcsv($log, [$email, 'skipped', 'staff account (' . $usergroup . ')']);
        continue;
    }
    if (!is_email($email)) {
        $n_skipped_invalid++;
        fputcsv($log, [$email, 'skipped', 'invalid email']);
        continue;
    }
    if (email_exists($email)) {
        $n_skipped_exists++;
        fputcsv($log, [$email, 'skipped', 'email already in WP']);
        continue;
    }

    if ($limit && $processed >= $limit) {
        break;
    }
    $processed++;

    $fname = to_utf8(trim($row[$col['fname']]));
    $lname = to_utf8(trim($row[$col['lname']]));

    /* login: keep the Kryptronic username where possible, else email */
    $login = sanitize_user(to_utf8(trim($row[$col['username']])), true);
    if ($login === '' || isset($seen_logins[mb_strtolower($login)]) || username_exists($login)) {
        $login = $email;
    }
    $seen_logins[mb_strtolower($login)] = true;

    $md5 = trim($row[$col['password']]);

    if ($dry_run) {
        $n_created++;
        continue;
    }

    $user_id = wp_insert_user([
        'user_login'   => $login,
        'user_email'   => $email,
        'user_pass'    => wp_generate_password(24),
        'role'         => 'customer',
        'first_name'   => $fname,
        'last_name'    => $lname,
        'display_name' => trim($fname . ' ' . $lname) ?: $login,
    ]);

    if (is_wp_error($user_id)) {
        $n_errors++;
        fputcsv($log, [$email, 'error', $user_id->get_error_message()]);
        continue;
    }

    /* carry the Kryptronic MD5 hash over verbatim */
    if (preg_match('/^[a-f0-9]{32}$/i', $md5)) {
        global $wpdb;
        $wpdb->update($wpdb->users, ['user_pass' => $md5], ['ID' => $user_id]);
        clean_user_cache($user_id);
    } else {
        fputcsv($log, [$email, 'warning', 'password not a valid MD5 hash — reset required']);
    }

    /* ---------- billing (inline address) ---------- */
    $billing = [
        'billing_first_name' => $fname,
        'billing_last_name'  => $lname,
        'billing_company'    => to_utf8(trim($row[$col['company']])),
        'billing_phone'      => trim($row[$col['phone']]),
        'billing_email'      => $email,
        'billing_address_1'  => to_utf8(trim($row[$col['addone']])),
        'billing_address_2'  => to_utf8(trim($row[$col['addtwo']])),
        'billing_city'       => to_utf8(trim($row[$col['city']])),
        'billing_state'      => to_utf8(trim($row[$col['stateprov']])),
        'billing_postcode'   => strtoupper(trim($row[$col['postalcode']])),
        'billing_country'    => iso_country($row[$col['country']], $country_map),
    ];
    foreach ($billing as $k => $v) {
        if ($v !== '') {
            update_user_meta($user_id, $k, $v);
        }
    }

    /* ---------- shipping (first address-book entry) ---------- */
    $addbook = @unserialize($row[$col['addbook']], ['allowed_classes' => false]);
    if (is_array($addbook) && $addbook) {
        $a = reset($addbook);
        if (is_array($a)) {
            $shipping = [
                'shipping_first_name' => to_utf8($a['fname'] ?? ''),
                'shipping_last_name'  => to_utf8($a['lname'] ?? ''),
                'shipping_company'    => to_utf8($a['company'] ?? ''),
                'shipping_phone'      => $a['phone'] ?? '',
                'shipping_address_1'  => to_utf8($a['addone'] ?? ''),
                'shipping_address_2'  => to_utf8($a['addtwo'] ?? ''),
                'shipping_city'       => to_utf8($a['city'] ?? ''),
                'shipping_state'      => to_utf8($a['stateprov'] ?? ''),
                'shipping_postcode'   => strtoupper(trim($a['postalcode'] ?? '')),
                'shipping_country'    => iso_country($a['country'] ?? '', $country_map),
            ];
            foreach ($shipping as $k => $v) {
                if (trim($v) !== '') {
                    update_user_meta($user_id, $k, trim($v));
                }
            }
        }
    }

    /* ---------- ACF customer fields ---------- */
    $ctype = $type_map[$usergroup] ?? 'retail';
    update_user_meta($user_id, 'customer_type', $ctype);
    update_user_meta($user_id, '_customer_type', 'field_dorotape_customer_type');

    /* whlsalepct format: "MANU|pct1|pct2" lines; DEFAULT line carries the rate */
    if (preg_match('/DEFAULT\|([\d.]+)\|/', $row[$col['whlsalepct']], $m) && (float) $m[1] > 0) {
        update_user_meta($user_id, 'customer_discount_rate', (float) $m[1]);
        update_user_meta($user_id, '_customer_discount_rate', 'field_dorotape_discount_rate');
    }

    /* ---------- legacy reference meta ---------- */
    update_user_meta($user_id, '_kryptronic_username', to_utf8(trim($row[$col['username']])));
    update_user_meta($user_id, '_kryptronic_usergroup', $usergroup);
    if ((int) $row[$col['lastaccess']] > 0) {
        update_user_meta($user_id, '_kryptronic_lastaccess', (int) $row[$col['lastaccess']]);
    }
    $points = (int) $row[$col['loyaltypoints']];
    if ($points > 0) {
        update_user_meta($user_id, '_kryptronic_loyaltypoints', $points);
    }
    if (trim($row[$col['rescom']]) !== '') {
        update_user_meta($user_id, '_kryptronic_rescom', trim($row[$col['rescom']]));
    }

    $n_created++;
    if ($n_created % 500 === 0) {
        echo "  ... {$n_created} created\n";
    }
}

fclose($fh);
fclose($log);
wp_suspend_cache_invalidation(false);

echo ($dry_run ? "[DRY RUN] " : '') . "Done.\n";
echo "  created:          {$n_created}\n";
echo "  skipped staff:    {$n_skipped_staff}\n";
echo "  skipped existing: {$n_skipped_exists}\n";
echo "  skipped invalid:  {$n_skipped_invalid}\n";
echo "  errors:           {$n_errors}\n";
echo "Log: {$log_csv}\n";
