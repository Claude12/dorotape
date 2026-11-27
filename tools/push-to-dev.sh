#!/usr/bin/env bash
#
# Push the local site to dev: the whole database, then the uploads.
#
# Local is where the work happens and dev is a copy of it, so this replaces
# dev's database outright. Users, orders, form entries and settings on dev are
# all overwritten. Dev's database is backed up on the server first, every time,
# and `--restore` puts the newest backup back.
#
# Usage:
#   tools/push-to-dev.sh                  database and uploads
#   tools/push-to-dev.sh --skip-uploads   database only
#   tools/push-to-dev.sh --skip-db        uploads only
#   tools/push-to-dev.sh --dry-run        run the checks, change nothing
#   tools/push-to-dev.sh --yes            no typed confirmation (the git hook uses this)
#   tools/push-to-dev.sh --restore [file] put a backup back on dev (newest by default)
#
# DEV_KEEP_OPTIONS and DEV_KEEP_TABLES name the parts of dev's database that
# belong to dev, such as a sync plugin set up against a live system there. They
# are set aside before the import and put back exactly as they were after it.
#
# DEV_ID_FLOOR keeps the orders and accounts made on dev, such as a test
# team's. See "Dev's own orders and accounts" below.
#
# Settings come from .env.push in the theme root (see .env.push.example). It
# is gitignored, and nothing site-specific is written in this file, so the same
# script works on any WordPress site with WP-CLI on both ends.
#
# Code is not part of this. The theme deploys through git (deploy.yml);
# plugins and WordPress core are whatever is installed on each side.

set -euo pipefail

THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONFIG="${PUSH_CONFIG:-$THEME_DIR/.env.push}"

die() { printf '\n\033[31mStopped:\033[0m %s\n' "$*" >&2; exit 1; }
step() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
note() { printf '    %s\n' "$*"; }
warn() { printf '    \033[33m%s\033[0m\n' "$*"; }

# ── Options ─────────────────────────────────────────────────────────────────

DO_DB=1
DO_UPLOADS=1
DRY_RUN=0
ASSUME_YES=0
RESTORE=0
RESTORE_FILE=""

while [ $# -gt 0 ]; do
	case "$1" in
		--skip-uploads) DO_UPLOADS=0 ;;
		--skip-db) DO_DB=0 ;;
		--dry-run) DRY_RUN=1 ;;
		--yes) ASSUME_YES=1 ;;
		--restore)
			RESTORE=1
			if [ $# -gt 1 ] && [ "${2#--}" = "$2" ]; then
				RESTORE_FILE="$2"
				shift
			fi
			;;
		-h | --help) sed -n '2,28p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
		*) die "Unknown option: $1 (try --help)" ;;
	esac
	shift
done

# ── Settings ────────────────────────────────────────────────────────────────

[ -f "$CONFIG" ] || die "No $CONFIG. Copy .env.push.example to .env.push and fill it in."
# shellcheck disable=SC1090
. "$CONFIG"

for name in DEV_SSH_HOST DEV_SSH_USER DEV_SSH_PORT DEV_WP_PATH LOCAL_URL DEV_URL; do
	[ -n "${!name:-}" ] || die "$name is not set in .env.push."
done

# An unquoted ~/ in .env.push is expanded when the file is sourced, into this
# machine's home folder, which does not exist on dev. Put it back as ~/ so the
# remote shell expands it to the right home instead.
case "$DEV_WP_PATH" in
	"$HOME"/*) TILDE='~'; DEV_WP_PATH="$TILDE/${DEV_WP_PATH#"$HOME"/}" ;;
esac

LOCAL_WP_PATH="${LOCAL_WP_PATH:-$(cd "$THEME_DIR/../../.." && pwd)}"
LOCAL_DUMP_DIR="${LOCAL_DUMP_DIR:-$HOME/wp-db-dumps}"
LOCAL_PHP="${LOCAL_PHP:-php}"
DEV_BACKUP_DIR="${DEV_BACKUP_DIR:-db-backups}"
DEV_BACKUP_KEEP="${DEV_BACKUP_KEEP:-10}"
LOCAL_ONLY_PLUGINS="${LOCAL_ONLY_PLUGINS:-}"
DEV_ONLY_PLUGINS="${DEV_ONLY_PLUGINS:-}"
DEV_KEEP_OPTIONS="${DEV_KEEP_OPTIONS:-}"
DEV_KEEP_TABLES="${DEV_KEEP_TABLES:-}"
DEV_ID_FLOOR="${DEV_ID_FLOOR:-}"

case "$DEV_ID_FLOOR" in
	"" | [1-9][0-9][0-9][0-9][0-9]*) ;;
	*) die "DEV_ID_FLOOR must be a whole number of 10000 or more (it is '$DEV_ID_FLOOR')." ;;
esac

# Both go straight into SQL, so only the characters a name can sensibly have.
for name in $DEV_KEEP_OPTIONS $DEV_KEEP_TABLES; do
	case "$name" in
		*[!A-Za-z0-9_%-]*) die "DEV_KEEP_* entry '$name' has characters this script will not put in SQL." ;;
	esac
done

# mysqldump and mysql, for `wp db export` on this machine. XAMPP and MAMP keep
# them in their own bin folder rather than on the PATH.
if [ -n "${LOCAL_BIN_PATH:-}" ]; then
	PATH="$LOCAL_BIN_PATH:$PATH"
fi

LOCAL_URL="${LOCAL_URL%/}"
DEV_URL="${DEV_URL%/}"
STAMP="$(date +%Y%m%d-%H%M%S)"

# ── Local WP-CLI ────────────────────────────────────────────────────────────
#
# display_errors goes to stderr: PHP in CLI mode prints notices to stdout by
# default, and a deprecation notice from any plugin would otherwise land in
# the middle of whatever WP-CLI was printing.

if [ -n "${LOCAL_WP_CLI:-}" ]; then
	read -r -a LWP <<<"$LOCAL_WP_CLI"
elif command -v wp >/dev/null 2>&1; then
	LWP=(wp)
else
	PHAR="$HOME/.wp-cli/wp-cli.phar"
	if [ ! -f "$PHAR" ]; then
		step "WP-CLI is not installed here; fetching it to $PHAR"
		mkdir -p "$(dirname "$PHAR")"
		curl -fsSL -o "$PHAR" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	fi
	LWP=("$LOCAL_PHP" -d display_errors=stderr "$PHAR")
fi

lwp() { "${LWP[@]}" --path="$LOCAL_WP_PATH" "$@"; }

# ── SSH ─────────────────────────────────────────────────────────────────────
#
# One connection for the whole run, so a passphrase or a 2FA prompt is
# answered once rather than at every step.

CONTROL="${TMPDIR:-/tmp}/push-to-dev-$$.sock"
SSH_OPTS=(-p "$DEV_SSH_PORT" -o ConnectTimeout=20 -o ControlMaster=auto -o "ControlPath=$CONTROL" -o ControlPersist=120)
# A key file of its own, if the login is not one the SSH agent already holds.
# On a Mac, UseKeychain fetches its passphrase from the login keychain once
# `ssh-add --apple-use-keychain` has stored it there.
if [ -n "${DEV_SSH_KEY:-}" ]; then
	SSH_OPTS+=(-i "${DEV_SSH_KEY/#\~/$HOME}" -o IdentitiesOnly=yes -o AddKeysToAgent=yes)
	[ "$(uname)" = Darwin ] && SSH_OPTS+=(-o UseKeychain=yes)
fi
REMOTE="$DEV_SSH_USER@$DEV_SSH_HOST"
trap 'ssh "${SSH_OPTS[@]}" -O exit "$REMOTE" >/dev/null 2>&1 || true' EXIT

rsh() { ssh "${SSH_OPTS[@]}" "$REMOTE" "$@"; }

# Every remote command starts in the WordPress root. A leading ~/ is left for
# the remote shell to expand, since shared hosts hand out paths in that form
# and ~ quoted is just a directory called "~".
if [ "${DEV_WP_PATH#\~/}" != "$DEV_WP_PATH" ]; then
	REMOTE_CD="cd \"\$HOME\"/$(printf %q "${DEV_WP_PATH#\~/}")"
else
	REMOTE_CD="cd $(printf %q "$DEV_WP_PATH")"
fi
# `cd` into the WordPress root, then WP-CLI. The backup folder is relative to
# the home directory, which is outside the web root on most hosts.
rwp() { rsh "$REMOTE_CD && wp $*"; }
BACKUPS="\"\$HOME\"/$(printf %q "$DEV_BACKUP_DIR")"

purge_dev_caches() {
	rsh "$REMOTE_CD && {
		wp cache flush >/dev/null && echo '    Object cache flushed.'
		# A hard flush, the same as pressing Save on Settings > Permalinks: it
		# rewrites the WordPress block in .htaccess too. A soft flush left dev
		# serving a 500 on every pretty URL after the 12 Nov 2026 push until
		# Permalinks was saved by hand. got_rewrite is forced on because WP-CLI
		# cannot detect mod_rewrite, and only the WordPress block is touched.
		wp eval 'require_once ABSPATH . \"wp-admin/includes/file.php\"; require_once ABSPATH . \"wp-admin/includes/misc.php\"; add_filter(\"got_rewrite\", \"__return_true\"); flush_rewrite_rules(true);' >/dev/null 2>&1 && echo '    Permalinks flushed (.htaccess rewritten).' || echo '    Permalink flush failed: save Settings > Permalinks on dev.'
		if wp plugin is-active sg-cachepress 2>/dev/null; then wp sg purge >/dev/null && echo '    SiteGround cache purged.'; fi
		if wp plugin is-active w3-total-cache 2>/dev/null; then wp w3-total-cache flush all >/dev/null && echo '    W3 Total Cache flushed.'; fi
		true
	}"
}

# ── Keep on dev ─────────────────────────────────────────────────────────────
#
# Held in tables of dev's database without the site's prefix, which the import
# never touches (the dump only drops tables it is about to create) and the
# search-replace never reads (it only walks prefixed tables). Copies rather
# than renames, so a run that stops halfway leaves dev's own tables where they
# were, and the backup has everything anyway.

KEEP_STASH=_push_keep

# Option names as one SQL condition; % is a wildcard, as in LIKE.
keep_options_where() {
	local cond="" name
	for name in $DEV_KEEP_OPTIONS; do
		cond="${cond:+$cond OR }option_name LIKE '$name'"
	done
	printf '%s' "$cond"
}

# Table names, with the prefix added where it was left off.
keep_tables() {
	local t
	for t in $DEV_KEEP_TABLES; do
		case "$t" in "$DEV_PREFIX"*) echo "$t" ;; *) echo "$DEV_PREFIX$t" ;; esac
	done
}

dev_sql() { rsh "$REMOTE_CD && wp db query" 2>/dev/null; }

keep_save() {
	local i=0 t sql=""
	if [ -n "$DEV_KEEP_OPTIONS" ]; then
		sql+="DROP TABLE IF EXISTS ${KEEP_STASH}_options;"
		sql+="CREATE TABLE ${KEEP_STASH}_options AS SELECT option_name, option_value, autoload FROM ${DEV_PREFIX}options WHERE $(keep_options_where);"
	fi
	for t in $KEEP_TABLES_FOUND; do
		i=$((i + 1))
		sql+="DROP TABLE IF EXISTS ${KEEP_STASH}_$i;"
		sql+="CREATE TABLE ${KEEP_STASH}_$i LIKE \`$t\`;"
		sql+="INSERT INTO ${KEEP_STASH}_$i SELECT * FROM \`$t\`;"
	done
	[ -n "$sql" ] && printf '%s\n' "$sql" | dev_sql
}

# Safe to run more than once: the held copies stay until keep_drop.
keep_restore() {
	local i=0 t sql=""
	if [ -n "$DEV_KEEP_OPTIONS" ]; then
		sql+="DELETE FROM ${DEV_PREFIX}options WHERE $(keep_options_where);"
		sql+="INSERT INTO ${DEV_PREFIX}options (option_name, option_value, autoload) SELECT option_name, option_value, autoload FROM ${KEEP_STASH}_options;"
	fi
	for t in $KEEP_TABLES_FOUND; do
		i=$((i + 1))
		sql+="DROP TABLE IF EXISTS \`$t\`;"
		sql+="CREATE TABLE \`$t\` LIKE ${KEEP_STASH}_$i;"
		sql+="INSERT INTO \`$t\` SELECT * FROM ${KEEP_STASH}_$i;"
	done
	[ -n "$sql" ] && printf '%s\n' "$sql" | dev_sql
}

keep_drop() {
	local i=0 t sql=""
	[ -n "$DEV_KEEP_OPTIONS" ] && sql+="DROP TABLE IF EXISTS ${KEEP_STASH}_options;"
	for t in $KEEP_TABLES_FOUND; do
		i=$((i + 1))
		sql+="DROP TABLE IF EXISTS ${KEEP_STASH}_$i;"
	done
	[ -n "$sql" ] && printf '%s\n' "$sql" | dev_sql
}

# What is being kept, as rows and bytes, so a before and after can be compared.
keep_summary() {
	local t
	if [ -n "$DEV_KEEP_OPTIONS" ]; then
		echo "SELECT option_name, LENGTH(option_value) FROM ${DEV_PREFIX}options WHERE $(keep_options_where) ORDER BY option_name;" | dev_sql | sed 1d | awk -F'\t' 'NF {printf "      option %s (%s bytes)\n", $1, $2}'
	fi
	for t in $KEEP_TABLES_FOUND; do
		echo "SELECT COUNT(*) FROM \`$t\`;" | dev_sql | sed 1d | awk -v t="$t" 'NF {printf "      table %s (%s rows)\n", t, $1}'
	done
}

# ── Dev's own orders and accounts ───────────────────────────────────────────
#
# Testing on dev makes orders and accounts that local never has, and a full
# import would wipe them. With DEV_ID_FLOOR set, dev numbers everything it
# makes from that floor up, and local's IDs never get that high, so "made on
# dev" is simply "ID at or above the floor". Those rows are set aside before
# the import and put back after it; everything below the floor comes from
# local as before.
#
# Each line: table (no prefix), the column that names a row, and which rows
# belong to dev. A table's rows are chosen from what was already set aside
# for the table it hangs off, so an order's items, notes and meta come with
# the order and nothing else. Tables dev does not have are skipped, along
# with any that hang off them.

ROWS_STASH=${KEEP_STASH}_rows_

rows_spec() {
	local F="$DEV_ID_FLOOR" R="$ROWS_STASH"
	cat <<-EOF
		posts	ID	ID >= $F AND post_type IN ('shop_order', 'shop_order_placehold', 'shop_order_refund') AND (post_parent = 0 OR post_parent >= $F)
		postmeta	meta_id	post_id IN (SELECT ID FROM ${R}posts)
		comments	comment_ID	comment_post_ID IN (SELECT ID FROM ${R}posts)
		commentmeta	meta_id	comment_id IN (SELECT comment_ID FROM ${R}comments)
		wc_orders	id	id IN (SELECT ID FROM ${R}posts)
		wc_orders_meta	id	order_id IN (SELECT ID FROM ${R}posts)
		wc_order_addresses	id	order_id IN (SELECT ID FROM ${R}posts)
		wc_order_operational_data	id	order_id IN (SELECT ID FROM ${R}posts)
		wc_order_stats	order_id	order_id IN (SELECT ID FROM ${R}posts)
		wc_order_tax_lookup	order_id	order_id IN (SELECT ID FROM ${R}posts)
		wc_order_coupon_lookup	order_id	order_id IN (SELECT ID FROM ${R}posts)
		woocommerce_order_items	order_item_id	order_id IN (SELECT ID FROM ${R}posts)
		woocommerce_order_itemmeta	meta_id	order_item_id IN (SELECT order_item_id FROM ${R}woocommerce_order_items)
		wc_order_product_lookup	order_item_id	order_item_id IN (SELECT order_item_id FROM ${R}woocommerce_order_items)
		users	ID	ID >= $F
		usermeta	umeta_id	user_id IN (SELECT ID FROM ${R}users)
		wc_customer_lookup	customer_id	customer_id >= $F AND (user_id IS NULL OR user_id IN (SELECT ID FROM ${R}users))
	EOF
}

# Tables whose next ID is moved up to the floor after every import. The import
# brings local's counters, so this has to be done each time.
ROWS_FLOORED="posts postmeta comments commentmeta users usermeta wc_orders_meta wc_order_addresses wc_order_operational_data woocommerce_order_items woocommerce_order_itemmeta wc_customer_lookup"

rows_sql() { rsh "$REMOTE_CD && wp db query"; }

# Sets ROWS_SPEC to the lines whose table dev has. Run once, in the checks.
rows_find() {
	local have t pk where dep
	have=" $(echo "SHOW TABLES LIKE '${DEV_PREFIX}%';" | dev_sql | sed 1d | tr '\n' ' ') "
	ROWS_SPEC=""
	while IFS=$'\t' read -r t pk where; do
		[ -n "$t" ] || continue
		case "$have" in *" $DEV_PREFIX$t "*) ;; *) continue ;; esac
		dep="$(printf '%s' "$where" | grep -o "${ROWS_STASH}[a-z_]*" | sed "s/^$ROWS_STASH//" | sort -u || true)"
		for d in $dep; do
			printf '%s\n' "$ROWS_SPEC" | grep -q "^$d	" || continue 2
		done
		ROWS_SPEC+="$t	$pk	$where"$'\n'
	done < <(rows_spec)
}

rows_save() {
	local sql="" t pk where
	while IFS=$'\t' read -r t pk where; do
		[ -n "$t" ] || continue
		sql+="DROP TABLE IF EXISTS ${ROWS_STASH}$t;"
		sql+="CREATE TABLE ${ROWS_STASH}$t LIKE \`$DEV_PREFIX$t\`;"
		sql+="INSERT INTO ${ROWS_STASH}$t SELECT * FROM \`$DEV_PREFIX$t\` WHERE $where;"
	done <<<"$ROWS_SPEC"
	printf '%s\n' "$sql" | rows_sql
}

# Safe to run more than once: each run deletes what the last one put back.
# Columns are named rather than SELECT *, and only the ones both copies of a
# table have, so a plugin update that added a column on one side still works.
rows_restore() {
	local sql="SET SESSION group_concat_max_len = 65535;" t pk where
	while IFS=$'\t' read -r t pk where; do
		[ -n "$t" ] || continue
		sql+="DELETE FROM \`$DEV_PREFIX$t\` WHERE \`$pk\` IN (SELECT \`$pk\` FROM ${ROWS_STASH}$t);"
		sql+="SET @c = (SELECT GROUP_CONCAT(CONCAT('\`', s.COLUMN_NAME, '\`') ORDER BY s.ORDINAL_POSITION) FROM information_schema.COLUMNS s JOIN information_schema.COLUMNS d ON d.TABLE_SCHEMA = s.TABLE_SCHEMA AND d.TABLE_NAME = '$DEV_PREFIX$t' AND d.COLUMN_NAME = s.COLUMN_NAME WHERE s.TABLE_SCHEMA = DATABASE() AND s.TABLE_NAME = '${ROWS_STASH}$t');"
		sql+="SET @q = CONCAT('INSERT INTO \`$DEV_PREFIX$t\` (', @c, ') SELECT ', @c, ' FROM ${ROWS_STASH}$t');"
		sql+="PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;"
	done <<<"$ROWS_SPEC"
	printf '%s\n' "$sql" | rows_sql
}

# The table's next ID becomes the floor, or one past its highest row if that
# is already above it.
rows_floor() {
	local sql="" t
	for t in $ROWS_FLOORED; do
		printf '%s\n' "$ROWS_SPEC" | grep -q "^$t	" && sql+="ALTER TABLE \`$DEV_PREFIX$t\` AUTO_INCREMENT = $DEV_ID_FLOOR;"
	done
	[ -z "$sql" ] || printf '%s\n' "$sql" | rows_sql
}

rows_drop() {
	local sql="" t pk where
	while IFS=$'\t' read -r t pk where; do
		[ -n "$t" ] && sql+="DROP TABLE IF EXISTS ${ROWS_STASH}$t;"
	done <<<"$ROWS_SPEC"
	printf '%s\n' "$sql" | rows_sql
}

# Counts the kept rows: from dev's tables in the checks, from the held copies
# once they exist. With HPOS a refund is a placeholder post like any order, so
# orders are counted from the orders table when there is one.
rows_summary() {
	local otable=posts ocol=post_type id=ID orders users
	if printf '%s\n' "$ROWS_SPEC" | grep -q "^wc_orders	"; then
		otable=wc_orders ocol=type id=id
	fi
	if [ "${1:-}" = held ]; then
		orders="SELECT COUNT(*) FROM ${ROWS_STASH}$otable WHERE $ocol = 'shop_order';"
		users="SELECT COUNT(*) FROM ${ROWS_STASH}users;"
	else
		orders="SELECT COUNT(*) FROM $DEV_PREFIX$otable WHERE $ocol = 'shop_order' AND $id >= $DEV_ID_FLOOR;"
		users="SELECT COUNT(*) FROM ${DEV_PREFIX}users WHERE ID >= $DEV_ID_FLOOR;"
	fi
	orders="$(echo "$orders" | dev_sql | sed -n 2p)"
	users="$(echo "$users" | dev_sql | sed -n 2p)"
	printf '      %s orders and %s accounts made on dev (ID %s and up)\n' "${orders:-0}" "${users:-0}" "$DEV_ID_FLOOR"
}

confirm() {
	[ "$ASSUME_YES" = 1 ] && return 0
	printf '\n    Type the dev host (%s) to go ahead: ' "$DEV_SSH_HOST"
	read -r answer
	[ "$answer" = "$DEV_SSH_HOST" ] || die "Not confirmed. Nothing was changed."
}

# ── Restore ─────────────────────────────────────────────────────────────────

if [ "$RESTORE" = 1 ]; then
	step "Backups on dev"
	rsh "ls -1t $BACKUPS/*.sql.gz 2>/dev/null | sed -n 1,${DEV_BACKUP_KEEP}p" || true

	if [ -z "$RESTORE_FILE" ]; then
		RESTORE_FILE="$(rsh "ls -1t $BACKUPS/*.sql.gz 2>/dev/null | sed -n 1p")"
	fi
	[ -n "$RESTORE_FILE" ] || die "No backups found in ~/$DEV_BACKUP_DIR on dev."

	note "Restoring: $RESTORE_FILE"
	[ "$DRY_RUN" = 1 ] && { note "Dry run: stopping here."; exit 0; }
	confirm

	step "Importing the backup"
	rsh "$REMOTE_CD && gunzip -c $(printf %q "$RESTORE_FILE") | wp db import -"
	purge_dev_caches
	step "Done. Dev is back to $RESTORE_FILE"
	note "Uploads are not rolled back: a push only ever adds or updates files."
	exit 0
fi

# ── Checks ──────────────────────────────────────────────────────────────────
#
# All read-only. Anything that would leave dev half-done stops the run here,
# before a single thing has changed.

step "Checking local"
lwp core is-installed 2>/dev/null || die "WordPress at $LOCAL_WP_PATH is not answering WP-CLI. Is MySQL running?"
LOCAL_SITEURL="$(lwp option get siteurl 2>/dev/null)"
[ "${LOCAL_SITEURL%/}" = "$LOCAL_URL" ] || die "Local siteurl is $LOCAL_SITEURL, but LOCAL_URL says $LOCAL_URL."
LOCAL_PREFIX="$(lwp db prefix 2>/dev/null)"
note "$LOCAL_URL (prefix $LOCAL_PREFIX, $(lwp db size --human-readable --format=csv 2>/dev/null | sed -n 2p | cut -d, -f2 | tr -d '"'))"

step "Checking dev over SSH"
rsh true || die "Could not connect to $REMOTE on port $DEV_SSH_PORT."
rwp core is-installed 2>/dev/null || die "No WordPress answering WP-CLI at $DEV_WP_PATH on dev."
DEV_SITEURL="$(rwp option get siteurl 2>/dev/null)"
[ "${DEV_SITEURL%/}" = "$DEV_URL" ] || die "Dev siteurl is $DEV_SITEURL, but DEV_URL says $DEV_URL. Is DEV_WP_PATH the right site?"
DEV_PREFIX="$(rwp db prefix 2>/dev/null)"

# wp-config.php is not copied, so dev keeps its own prefix. A dump with another
# prefix would import as a second set of tables that dev never reads.
[ "$LOCAL_PREFIX" = "$DEV_PREFIX" ] || die "Table prefixes differ (local $LOCAL_PREFIX, dev $DEV_PREFIX). Set \$table_prefix the same on both."
note "$DEV_URL (prefix $DEV_PREFIX)"

# A kept table that dev does not have is left out with a warning rather than
# stopping the run: there is nothing of dev's to lose.
KEEP_TABLES_FOUND=""
if [ "$DO_DB" = 1 ]; then
	for t in $(keep_tables); do
		if [ -n "$(echo "SHOW TABLES LIKE '$t';" | dev_sql)" ]; then
			KEEP_TABLES_FOUND="$KEEP_TABLES_FOUND $t"
		else
			warn "DEV_KEEP_TABLES: $t is not in dev's database, so there is nothing to keep."
		fi
	done
fi

# Dev's own rows only stay apart from local's while local's IDs stay below the
# floor. Held copies left on dev mean a push stopped halfway: running again
# would overwrite them with the half-imported state, so that is for a person.
ROWS_SPEC=""
if [ "$DO_DB" = 1 ] && [ -n "$DEV_ID_FLOOR" ]; then
	for t in $ROWS_FLOORED; do
		next="$(lwp db query "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$LOCAL_PREFIX$t';" --skip-column-names 2>/dev/null || true)"
		case "$next" in '' | NULL) continue ;; esac
		[ "$next" -lt "$DEV_ID_FLOOR" ] || die "Local $LOCAL_PREFIX$t is already numbering at $next, at or above DEV_ID_FLOOR ($DEV_ID_FLOOR). Raise the floor in .env.push."
	done
	[ -z "$(echo "SHOW TABLES LIKE '${ROWS_STASH}%';" | dev_sql | sed 1d)" ] || die "Dev still holds ${ROWS_STASH}* tables from a push that stopped halfway. Dev's orders and accounts are in them (and in the backup). Put them back by hand, or run --restore, before pushing again."
	rows_find
fi

if [ "$DO_UPLOADS" = 1 ]; then
	rsh "command -v rsync >/dev/null" || die "rsync is not installed on dev. Run with --skip-uploads."
fi

# Plugins are not copied, only the record of which ones are switched on. One
# that is on here and missing on dev simply stays off there, which is worth
# knowing before a page that depends on it looks broken.
if [ "$DO_DB" = 1 ]; then
	LOCAL_ACTIVE="$(lwp plugin list --status=active --field=name 2>/dev/null | sort)"
	DEV_INSTALLED="$(rwp plugin list --field=name 2>/dev/null | sort)"
	MISSING="$(comm -23 <(echo "$LOCAL_ACTIVE") <(echo "$DEV_INSTALLED") | grep -vxF -f <(printf '%s\n' $LOCAL_ONLY_PLUGINS "") || true)"
	if [ -n "$MISSING" ]; then
		warn "Active here but not installed on dev, so they will be off there:"
		echo "$MISSING" | sed 's/^/      /'
	fi

	# The other direction is the one that bites: something dev runs and local
	# does not, such as a host's cache plugin or a sync to another system,
	# goes off without a word unless it is in DEV_ONLY_PLUGINS.
	DEV_ACTIVE="$(rwp plugin list --status=active --field=name 2>/dev/null | sort)"
	LOSING="$(comm -23 <(echo "$DEV_ACTIVE") <(echo "$LOCAL_ACTIVE") | grep -vxF -f <(printf '%s\n' $DEV_ONLY_PLUGINS "") || true)"
	if [ -n "$LOSING" ]; then
		warn "Active on dev but not here, so the push switches them off on dev"
		warn "(add any that should stay on to DEV_ONLY_PLUGINS):"
		echo "$LOSING" | sed 's/^/      /'
	fi
fi

step "About to push"
[ "$DO_DB" = 1 ] && note "Database: local replaces dev completely; dev is backed up first."
[ "$DO_UPLOADS" = 1 ] && note "Uploads: new and changed files go up; nothing on dev is deleted."
[ -n "$LOCAL_ONLY_PLUGINS" ] && note "Switched off on dev afterwards: $LOCAL_ONLY_PLUGINS"
[ -n "$DEV_ONLY_PLUGINS" ] && note "Switched on on dev afterwards: $DEV_ONLY_PLUGINS"
if [ "$DO_DB" = 1 ] && { [ -n "$DEV_KEEP_OPTIONS" ] || [ -n "$KEEP_TABLES_FOUND" ]; }; then
	note "Kept exactly as they are on dev:"
	keep_summary
fi
if [ -n "$ROWS_SPEC" ]; then
	note "Kept on dev, alongside local's:"
	rows_summary
fi

if [ "$DRY_RUN" = 1 ]; then
	step "Dry run: every check passed, nothing was changed."
	exit 0
fi

confirm

# ── Database ────────────────────────────────────────────────────────────────

if [ "$DO_DB" = 1 ]; then
	step "Backing up dev's database"
	BACKUP="$DEV_BACKUP_DIR/dev-$STAMP.sql.gz"
	rsh "mkdir -p $BACKUPS && $REMOTE_CD && wp db export - | gzip > \"\$HOME\"/$(printf %q "$BACKUP")"
	note "~/$BACKUP"
	# Oldest first out, keeping the newest DEV_BACKUP_KEEP.
	rsh "ls -1t $BACKUPS/dev-*.sql.gz | sed -n '$((DEV_BACKUP_KEEP + 1)),\$p' | xargs rm -f --"

	if [ -n "$DEV_KEEP_OPTIONS" ] || [ -n "$KEEP_TABLES_FOUND" ]; then
		step "Setting aside what stays on dev"
		keep_save
		note "Held in ${KEEP_STASH}_* tables on dev until the import is done."
	fi
	if [ -n "$ROWS_SPEC" ]; then
		step "Setting aside dev's own orders and accounts"
		rows_save
		rows_summary held
	fi

	step "Exporting the local database"
	mkdir -p "$LOCAL_DUMP_DIR"
	DUMP="$LOCAL_DUMP_DIR/local-$STAMP.sql"
	lwp db export "$DUMP" >/dev/null
	gzip -f "$DUMP"
	DUMP="$DUMP.gz"
	note "$DUMP ($(du -h "$DUMP" | cut -f1))"

	step "Uploading it"
	INCOMING="$DEV_BACKUP_DIR/incoming-$STAMP.sql.gz"
	scp -q -o "ControlPath=$CONTROL" -P "$DEV_SSH_PORT" "$DUMP" "$REMOTE:$INCOMING"

	step "Importing on dev"
	rsh "$REMOTE_CD && gunzip -c \"\$HOME\"/$(printf %q "$INCOMING") | wp db import - && rm -f \"\$HOME\"/$(printf %q "$INCOMING")"

	# The site address is written into options, post content, ACF fields and
	# block attributes. WP-CLI's search-replace unpicks serialized values and
	# re-counts their lengths; a plain SQL REPLACE breaks every one it touches.
	# Block markup stores URLs JSON-escaped (http:\/\/...), which is a second
	# string, and the bare host/path pass catches anything written without
	# the scheme or with the other one.
	step "Rewriting $LOCAL_URL to $DEV_URL"
	LOCAL_HOSTPATH="${LOCAL_URL#*://}"
	DEV_HOSTPATH="${DEV_URL#*://}"
	esc() { printf '%s' "$1" | sed 's#/#\\/#g'; }
	for pair in \
		"$LOCAL_URL|$DEV_URL" \
		"$(esc "$LOCAL_URL")|$(esc "$DEV_URL")" \
		"$LOCAL_HOSTPATH|$DEV_HOSTPATH" \
		"$(esc "$LOCAL_HOSTPATH")|$(esc "$DEV_HOSTPATH")"; do
		from="${pair%%|*}"
		to="${pair#*|}"
		count="$(rwp search-replace "$(printf %q "$from")" "$(printf %q "$to")" --all-tables-with-prefix --precise --skip-columns=guid --format=count 2>/dev/null)"
		note "$from  ->  $to  ($count)"
	done

	# Put back before any dev-only plugin is switched on, so it never runs with
	# local's copy of its settings, and again afterwards, in case its
	# activation wrote defaults over them.
	KEEPING=0
	if [ -n "$DEV_KEEP_OPTIONS" ] || [ -n "$KEEP_TABLES_FOUND" ]; then
		KEEPING=1
		step "Putting back what stays on dev"
		keep_restore
	fi
	if [ -n "$ROWS_SPEC" ]; then
		KEEPING=1
		step "Putting back dev's own orders and accounts"
		rows_restore || die "Dev's orders and accounts did not go back in. They are still held in ${ROWS_STASH}* tables on dev, and in the backup above."
		rows_floor
	fi

	step "Plugins"
	for plugin in $LOCAL_ONLY_PLUGINS; do
		rwp plugin deactivate "$(printf %q "$plugin")" >/dev/null 2>&1 && note "Off: $plugin" || true
	done
	for plugin in $DEV_ONLY_PLUGINS; do
		rwp plugin activate "$(printf %q "$plugin")" >/dev/null 2>&1 && note "On: $plugin" || warn "Could not switch on $plugin (is it installed on dev?)"
	done

	if [ "$KEEPING" = 1 ]; then
		if [ -n "$DEV_KEEP_OPTIONS" ] || [ -n "$KEEP_TABLES_FOUND" ]; then
			keep_restore
			keep_drop
			note "Kept on dev:"
			keep_summary
		fi
		if [ -n "$ROWS_SPEC" ]; then
			rows_restore || die "Dev's orders and accounts did not go back in. They are still held in ${ROWS_STASH}* tables on dev, and in the backup above."
			rows_floor
			rows_drop
			note "Kept on dev:"
			rows_summary
		fi
	fi
fi

# ── Uploads ─────────────────────────────────────────────────────────────────
#
# No --delete: dev has files local does not, and a media row on either side
# can still point at them. Logs and caches stay where they were made.

if [ "$DO_UPLOADS" = 1 ]; then
	step "Uploads"
	rsync -rlptz --exclude 'wc-logs/' --exclude 'cache/' --exclude '*.log' \
		-e "ssh -p $DEV_SSH_PORT -o ControlPath=$CONTROL" \
		"$LOCAL_WP_PATH/wp-content/uploads/" \
		"$REMOTE:$DEV_WP_PATH/wp-content/uploads/" \
		--stats | grep -E 'Number of (regular )?files transferred|Total transferred file size' | sed 's/^/    /'
fi

step "Caches"
purge_dev_caches

step "Done. $DEV_URL now matches local."
[ "$DO_DB" = 1 ] && note "Undo the database with: tools/push-to-dev.sh --restore"
