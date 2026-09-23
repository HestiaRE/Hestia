#!/bin/sh
# Command help from the header of the running command ($0): main.sh sources it, the few commands that
# load nothing else source it for --help only. POSIX and dependency-free, because hestia-php-refresh
# is /bin/sh and h-learn-mail-message runs as the customer.

# Every value of "# KEY:" in the header, which ends at the first line that is not a comment.
cmd_header() {
	awk -v k="# $1:" 'NR == 1 { next } !/^#/ { exit }
		index($0, k) == 1 { v = substr($0, length(k) + 1); sub(/^[ \t]+/, "", v); if (v != "") print v }' "$0" 2> /dev/null
}

# FALLBACK is the options text a caller carries itself, for a header that has none.
cmd_usage() {
	_ch_opts=$(cmd_header options | head -n 1)
	[ -n "$_ch_opts" ] || _ch_opts="$1"
	case "$_ch_opts" in NONE | None | none | '(none)' | '[NONE]') _ch_opts='' ;; esac
	echo "Usage: ${0##*/}${_ch_opts:+ $_ch_opts}"
	cmd_header info | head -n 1 | sed 's/^/  /'
	cmd_header example | sed 's/^/  example: /'
	if cmd_header labels | grep -qw internal; then
		_ch_caller=$(cmd_header caller | head -n 1)
		echo "  internal${_ch_caller:+, called by $_ch_caller}: not meant to be run by hand"
	fi
}

# Only a lone -h/--help, and it exits before anything runs. A caller's value handed through as the
# only argument is therefore a no-op, and every check a login rests on takes two arguments or more.
# Only commands: update.sh sourcing main.sh keeps its own arguments.
case "${0##*/}" in
	h-* | v-* | hestia-*)
		if [ "$#" -eq 1 ] && { [ "$1" = '--help' ] || [ "$1" = '-h' ]; }; then
			cmd_usage ''
			exit 0
		fi
		;;
esac
