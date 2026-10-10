#!/bin/bash
# Panel catalogs (#1160). `update` rebuilds web/locale/hestia.pot from the code, merges every hestia.po into it
# and compiles hestia.mo; `check` is read-only and fails when any of the three is stale or a maintained language
# misses a string. Local, not a CI gate: the runner host carries no gettext.
#
# Not covered: a _() whose argument is a variable. The service labels of h-list-sys-services are the one such set
# with translations, so the template takes them from there; the others (search result types, firewall protocol,
# dates) never had one. Nor does it judge whether a translation is right, only that it exists and keeps its
# format specifiers.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 2

# de is curated with the author, the rest is best effort and only reported.
MAINTAINED="de"
# The gettext domain, also named in web/inc/i18n.php and check_panel_catalogs in check-tree.sh.
DOMAIN="hestia"
LOCALE="web/locale"

mode=${1:-}
case "$mode" in
	update | check) ;;
	*)
		echo "usage: $0 update|check" >&2
		exit 2
		;;
esac
for t in xgettext msgcat msgmerge msgattrib msgfmt; do
	command -v "$t" > /dev/null || {
		echo "[ FAIL ] $t not found, install gettext" >&2
		exit 2
	}
done

tmp=$(mktemp -d) || exit 2
trap 'rm -rf "$tmp"' EXIT
fail=0

# Entries without the header, which is itself an entry with an empty msgid.
entries() { grep -c '^msgid "' "$1" | awk '{print ($1 > 0 ? $1 - 1 : 0)}'; }

git ls-files -- 'web/*.php' ":!$LOCALE" > "$tmp/files"
nfiles=$(wc -l < "$tmp/files")
xgettext --language=PHP --from-code=UTF-8 --add-location=file --sort-by-file --no-wrap --package-name=HestiaRE \
	-o "$tmp/php.pot" -f "$tmp/files" 2> "$tmp/xgettext.err" || {
	cat "$tmp/xgettext.err" >&2
	exit 2
}
# The labels travel as data (SYSTEM='...') and reach _() in list_services.php. Only data= lines: the file also
# assigns ANTIVIRUS_SYSTEM='clamd'.
grep -E '^\s*data=' bin/h-list-sys-services | grep -oE " SYSTEM='[^'\$\"]+'" | cut -d "'" -f 2 | sort -u > "$tmp/labels"
nlabels=$(wc -l < "$tmp/labels")
{
	printf 'msgid ""\nmsgstr "Content-Type: text/plain; charset=UTF-8\\n"\n'
	while IFS= read -r label; do
		printf '\n#: bin/h-list-sys-services\nmsgid "%s"\nmsgstr ""\n' "$label"
	done < "$tmp/labels"
} > "$tmp/labels.pot"
# No creation date: it changes on every run and would make every template look stale.
msgcat --use-first --add-location=file --sort-by-file --no-wrap "$tmp/php.pot" "$tmp/labels.pot" \
	| sed '/^"POT-Creation-Date: /d' > "$tmp/$DOMAIN.pot"
npot=$(entries "$tmp/$DOMAIN.pot")
# An empty reference set would pass every catalog against nothing.
if [ "$nfiles" -eq 0 ] || [ "$nlabels" -eq 0 ] || [ "$npot" -eq 0 ]; then
	echo "[ FAIL ] template is empty: $nfiles panel files, $nlabels service labels, $npot strings"
	exit 1
fi
echo "template: $npot strings from $nfiles panel files and $nlabels service labels"

# Writes in update mode, compares in check mode.
settle() {
	if cmp -s "$1" "$2"; then
		return 0
	elif [ "$mode" = update ]; then
		cp "$1" "$2" && echo "  updated $2"
	else
		echo "[ FAIL ] $2 is stale, run $0 update"
		fail=1
	fi
}
settle "$tmp/$DOMAIN.pot" "$LOCALE/$DOMAIN.pot"

langs=0
for dir in "$LOCALE"/*/; do
	[ -e "$dir" ] || continue
	dir=${dir%/}
	lang=${dir##*/}
	po="$dir/LC_MESSAGES/$DOMAIN.po"
	if [ ! -f "$po" ]; then
		echo "[ FAIL ] $lang: $po missing"
		fail=1
		continue
	fi
	for f in "$dir"/LC_MESSAGES/*; do
		[ -e "$f" ] || continue
		case "${f##*/}" in
			"$DOMAIN.po" | "$DOMAIN.mo") ;;
			*)
				echo "[ FAIL ] $lang: stray $f"
				fail=1
				;;
		esac
	done
	msgmerge --quiet --no-wrap --no-fuzzy-matching --add-location=file --sort-by-file -o - "$po" "$tmp/$DOMAIN.pot" \
		| msgattrib --no-obsolete --no-wrap | sed '/^"POT-Creation-Date: /d' > "$tmp/$lang.po"
	if ! msgfmt --check-format --check-domain -o "$tmp/$lang.mo" "$tmp/$lang.po"; then
		echo "[ FAIL ] $lang: msgfmt rejects the format specifiers or domain of $po"
		fail=1
		continue
	fi
	settle "$tmp/$lang.po" "$po"
	settle "$tmp/$lang.mo" "$dir/LC_MESSAGES/$DOMAIN.mo"
	missing=$(msgattrib --untranslated --no-wrap "$tmp/$lang.po" | entries /dev/stdin)
	fuzzy=$(msgattrib --only-fuzzy --no-wrap "$tmp/$lang.po" | entries /dev/stdin)
	if [[ " $MAINTAINED " == *" $lang "* ]] && [ $((missing + fuzzy)) -gt 0 ]; then
		echo "[ FAIL ] $lang: $missing of $npot untranslated, $fuzzy fuzzy"
		fail=1
	else
		echo "  $lang: $missing of $npot untranslated, $fuzzy fuzzy"
	fi
	langs=$((langs + 1))
done
if [ "$langs" -eq 0 ]; then
	echo "[ FAIL ] no catalog under $LOCALE"
	fail=1
fi
[ "$fail" -eq 0 ] && echo "[ OK ] $langs catalogs"
exit "$fail"
