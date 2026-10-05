#!/usr/bin/env bash
#
# Git pre-push hook: push the local database to dev along with each deploy.
#
# deploy.yml deploys dev when a push to main changes the Version line in
# style.css. The database only exists on this machine, so GitHub cannot bring
# it across; this does, from here, just before the code goes up. Pushes that
# do not deploy (other branches, or main without a version change) are left
# alone.
#
# Installed by .git/hooks/pre-push calling this file:
#   printf '#!/bin/sh\nexec "$(git rev-parse --show-toplevel)/tools/pre-push-db.sh" "$@"\n' > .git/hooks/pre-push
#   chmod +x .git/hooks/pre-push
#
# Skip it once with:  SKIP_DB_PUSH=1 git push
#
# A failed database push never blocks the code push. It says so loudly, and
# tools/push-to-dev.sh can be run again by hand.

set -u

[ "${SKIP_DB_PUSH:-}" = 1 ] && exit 0

ROOT="$(git rev-parse --show-toplevel)"
ZERO=0000000000000000000000000000000000000000
DEPLOYS=0

version_at() { git show "$1:style.css" 2>/dev/null | grep -m1 '^Version:' | awk '{print $2}'; }

# Git hands the hook one line per ref being pushed.
while read -r local_ref local_sha remote_ref remote_sha; do
	[ "$remote_ref" = refs/heads/main ] || continue
	[ "$local_sha" = "$ZERO" ] && continue # deleting the branch

	new="$(version_at "$local_sha")"
	old=""
	[ "$remote_sha" != "$ZERO" ] && old="$(version_at "$remote_sha")"

	if [ -n "$new" ] && [ "$new" != "$old" ]; then
		echo "Version ${old:-none} -> $new: this push deploys dev, so the database goes too."
		DEPLOYS=1
	fi
done

[ "$DEPLOYS" = 1 ] || exit 0

# The hook's stdin is git's ref list, not the terminal.
if "$ROOT/tools/push-to-dev.sh" --yes </dev/null; then
	exit 0
fi

printf '\n\033[31m!! The database did NOT reach dev.\033[0m The code push carries on.\n'
printf '   Fix the cause above, then run: tools/push-to-dev.sh\n\n'
exit 0
