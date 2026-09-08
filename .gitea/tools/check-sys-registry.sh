#!/bin/bash
# The system key registry gate for CI: the SAME function the smoke runs on a box (sysreg_check in
# include/sysreg.sh), called on the checkout. One implementation, twice called - a forker brings the
# smoke, not our runner (E21, #932). Needs jq on the runner host and nothing else.
set -euo pipefail
cd "$(dirname "$0")/../.."
command -v jq > /dev/null 2>&1 || {
	echo "jq is missing on this host"
	exit 1
}
n=0
while IFS= read -r f; do
	jq empty "$f" || {
		echo "invalid JSON: $f"
		exit 1
	}
	n=$((n + 1))
done < <(find share -name '*.json' -type f)
[ "$n" -gt 0 ] || {
	echo "no JSON under share/ - nothing was checked"
	exit 1
}
echo "jq empty: $n file(s) under share/ parse"
# shellcheck source=include/sysreg.sh
HESTIA="$PWD" source include/sysreg.sh
if sysreg_check share/hestia/sys-keys.json; then
	echo "sys-keys.json: schema holds ($(HESTIA="$PWD" sysreg_keys | wc -l) keys)"
else
	echo "sys-keys.json: schema violated (lines above)"
	exit 1
fi
