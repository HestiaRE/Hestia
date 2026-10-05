#!/bin/bash

#===========================================================================#
#                                                                           #
# Hestia Control Panel - Firewall Function Library                          #
#                                                                           #
#===========================================================================#

# The one place that knows nft syntax: callers speak object model, open a batch, append, apply. Buffered in sections
# because the emit order is not the ruleset order; one `nft -f` swap leaves no instant with an open policy.
# One inet table for both families, and nothing may REQUIRE v6: it has to load v4-only and with disable_ipv6=1.

FW_NFT="/usr/sbin/nft"
FW_FAMILY="inet"
FW_TABLE="hestia"
FW_RULESET="/etc/hestia/firewall/ruleset.nft"
FW_WORK=""
FW_INPUT_POLICY="drop"

# One address pattern for the whole library, so the grep form and the test form cannot drift apart.
FW_ADDR_RE='^[0-9]{1,3}(\.[0-9]{1,3}){3}(/[0-9]{1,2})?$'
# Deliberately loose: a sanity filter that keeps nft grammar out, not a parser; nft validates the rest.
FW_ADDR6_RE='^[0-9A-Fa-f:]*:[0-9A-Fa-f:.]*(/[0-9]{1,3})?$'
# What no blocklist and no CrowdSec ban may drop (h-update-crowdsec-mesh reads it too). FireHOL level1 carries 10/8
# through fullbogons, and a DROP on it locks the admin network out of a NAT'd box; 100.64/10 is CGNAT (Tailscale).
FW_KEEP_V4=(127.0.0.0/8 10.0.0.0/8 100.64.0.0/10 172.16.0.0/12 192.168.0.0/16)
FW_KEEP_V6=(::1 fe80::/10 fc00::/7)

# Sections, not one buffer: jail rules are added last but must match first.
fw_batch_begin() {
	FW_WORK="$(mktemp -d)"
	: > "$FW_WORK/exclude"
	: > "$FW_WORK/jail"
	: > "$FW_WORK/base"
	: > "$FW_WORK/setjump"
	: > "$FW_WORK/rules"
	: > "$FW_WORK/local"
	FW_INPUT_POLICY="drop"
}

fw_batch_discard() {
	[ -n "${FW_WORK:-}" ] && rm -rf "$FW_WORK"
	FW_WORK=""
}

# Refuses without an open batch: $FW_WORK is empty then and the append would land on /$1 at the root.
fw_sec() {
	[ -n "${FW_WORK:-}" ] || {
		echo "firewall: fw_sec '$1' without an open batch" >&2
		return 1
	}
	echo "$2" >> "$FW_WORK/$1"
}

# The empty declaration before the delete keeps a box with no table yet from failing the whole transaction.
fw_batch_render() {
	local f
	# share/updates/0.24.json looks for this line. A fixed string: callers run with IFS=newline, which splits a list.
	echo "# keep-private"
	echo "table $FW_FAMILY $FW_TABLE {}"
	echo "delete table $FW_FAMILY $FW_TABLE"
	echo "table $FW_FAMILY $FW_TABLE {"
	fw_render_sets
	echo "	chain input {"
	echo "		type filter hook input priority filter; policy $FW_INPUT_POLICY;"
	# Jails above the conntrack accept, so a ban drops live connections too.
	cat "$FW_WORK/exclude" "$FW_WORK/jail" "$FW_WORK/base" "$FW_WORK/setjump" "$FW_WORK/rules"
	echo "	}"
	# Only when something asks, always policy accept: this restricts loopback ports, it does not filter egress.
	if [ -s "$FW_WORK/local" ]; then
		echo "	chain output {"
		echo "		type filter hook output priority filter; policy accept;"
		cat "$FW_WORK/local"
		echo "	}"
	fi
	for f in "$FW_WORK"/chain.*; do
		[ -e "$f" ] || continue
		echo "	chain ${f##*/chain.} {"
		cat "$f"
		echo "	}"
	done
	echo "}"
}

# `-c -f`, never `-f -c`: that order takes -c as the filename and reports a syntax error against the real path.
fw_batch_apply() {
	local doc="$FW_WORK/ruleset.nft"
	fw_batch_render > "$doc"
	if ! "$FW_NFT" -c -f "$doc" 2> "$FW_WORK/err"; then
		echo "firewall: rendered ruleset rejected, keeping the running one" >&2
		sed 's/^/  /' "$FW_WORK/err" >&2
		return 1
	fi
	"$FW_NFT" -f "$doc" 2> "$FW_WORK/err" || {
		echo "firewall: apply failed, ruleset unchanged" >&2
		sed 's/^/  /' "$FW_WORK/err" >&2
		return 1
	}
	cp -f "$doc" "$FW_RULESET"
	return 0
}

# A record nft rejects makes every later render fail, so a command that writes one renders with a way back.
fw_conf_checkpoint() {
	FW_CHECKPOINT_OF="$1"
	FW_CHECKPOINT="$(mktemp)"
	cat "$1" > "$FW_CHECKPOINT" 2> /dev/null
}

fw_update_or_rollback() {
	if "$HESTIA/bin/h-update-firewall"; then
		rm -f "$FW_CHECKPOINT"
		return 0
	fi
	cat "$FW_CHECKPOINT" > "$FW_CHECKPOINT_OF"
	rm -f "$FW_CHECKPOINT"
	return 1
}

# Policy is a property of the chain in nft, so this records it for the render instead of emitting.
fw_policy() {
	[ "$1" = 'INPUT' ] && FW_INPUT_POLICY="$(echo "$2" | tr '[:upper:]' '[:lower:]')"
	return 0
}

# nft chain names are lower case; one shared conversion, or a lookup silently misses a chain that exists.
fw_chain_id() {
	echo "$1" | tr '[:upper:]' '[:lower:]'
}

# Chains are declared by being written to; replacing the table is the flush.
fw_chain_create() { [ "$(fw_chain_id "$1")" = 'input' ] || touch "$FW_WORK/chain.$(fw_chain_id "$1")"; }

# Schema-versioned JSON, not the text rendering, whose layout shifts between nft versions. jq is a prereq.
fw_policy_get() {
	"$FW_NFT" -j list chain "$FW_FAMILY" "$FW_TABLE" "$(fw_chain_id "$1")" 2> /dev/null \
		| jq -r 'first(.nftables[] | select(.chain) | .chain.policy) // empty' 2> /dev/null \
		| tr '[:lower:]' '[:upper:]'
}

fw_accept_established() {
	fw_sec base "		ct state established,related accept"
}

# Family from the source, as in fw_rule: `ip saddr <v6>` is invalid nft and fails the WHOLE document.
fw_accept_source() {
	case "$(fw_addr_family "$1")" in
		4) fw_sec base "		ip saddr $1 accept" ;;
		6) fw_sec base "		ip6 saddr $1 accept" ;;
		*)
			command -v log_event > /dev/null 2>&1 \
				&& log_event "${E_PARSING:-}" "firewall: own-IP accept skipped, unparseable address '$1'"
			;;
	esac
	return 0
}

# By interface, not address: `ip saddr 127.0.0.1` is v4-only and leaves ::1 (redis binds it) without an accept.
fw_accept_loopback() {
	fw_sec base "		iif lo accept"
}

# Infrastructure, not a user rule: NDP and PMTUD are NEW packets, so under the drop policy v6 loses its gateway.
fw_accept_icmpv6() {
	fw_sec base "		meta l4proto ipv6-icmp accept"
}

# Ahead of the ban matches, so it releases an existing lockout too; one file feeds both family sets.
fw_accept_excludes() {
	[ -s "$CONF_DIR/firewall/excludes.conf" ] || return 0
	fw_set_declare excludes interval
	fw_set_declare excludes6 v6interval
	fw_sec exclude "		ip saddr @excludes accept"
	fw_sec exclude "		ip6 saddr @excludes6 accept"
}

fw_join() {
	local IFS=,
	echo "$*" | sed 's/,/, /g'
}

fw_return_source() {
	fw_sec "chain.$(fw_chain_id "$1")" "		ip saddr $2 return"
}

fw_return_source6() {
	fw_sec "chain.$(fw_chain_id "$1")" "		ip6 saddr $2 return"
}

fw_chain_tail() {
	fw_sec "chain.$(fw_chain_id "$1")" "		$(echo "$2" | tr '[:upper:]' '[:lower:]')"
}

# Declared here so the document never references a missing set.
fw_set_jump() {
	fw_set_declare "$2" interval
	fw_sec setjump "		ip saddr @$(fw_set_id "$2") jump $(fw_chain_id "$1")"
}

# IPv6 counterpart: declared v6 so the render types the set and filters its source file to v6 addresses.
fw_set_jump6() {
	fw_set_declare "$2" v6
	fw_sec setjump "		ip6 saddr @$(fw_set_id "$2") jump $(fw_chain_id "$1")"
}

# Cosmetic (nft takes dashes), but it is the name every existing box carries: do not widen it into a rename.
fw_set_id() {
	echo "${1//-/_}"
}

# nft re-parses what it is handed: an element with extra grammar could append more, and one bad one fails it all.
fw_is_addr() {
	[[ "$1" =~ $FW_ADDR_RE ]]
}

fw_is_addr6() {
	[[ "$1" =~ $FW_ADDR6_RE ]]
}

# 4, 6, or nothing. The nothing case is what keeps a malformed value out of nft entirely.
fw_addr_family() {
	if fw_is_addr "$1"; then
		echo 4
	elif fw_is_addr6 "$1"; then
		echo 6
	fi
}

# Cache file, set type and match qualifier must agree, or a v6 list renders as an empty set that blocks nothing.
fw_ipset_family() {
	case "$(grep -F "LISTNAME='$1' " "$CONF_DIR/firewall/ipset.conf" 2> /dev/null | sed -n "s/.*IP_VERSION='\([^']*\)'.*/\1/p" | head -1)" in
		v6) echo 6 ;;
		*) echo 4 ;;
	esac
}

# Replacing the table drops every element, so a set always renders from a file. The CrowdSec feeder and the
# blocklist refresh each own one.
fw_set_src() {
	case "$1" in
		crowdsec-blacklists) echo "$CONF_DIR/firewall/crowdsec.iplist" ;;
		crowdsec6-blacklists) echo "$CONF_DIR/firewall/crowdsec6.iplist" ;;
		excludes | excludes6) echo "$CONF_DIR/firewall/excludes.conf" ;;
		*)
			if [ "$(fw_ipset_family "$1")" = 6 ]; then
				echo "$CONF_DIR/firewall/ipset/$1.v6.iplist"
			else
				echo "$CONF_DIR/firewall/ipset/$1.v4.iplist"
			fi
			;;
	esac
}

# Metadata, not a finished line, so elements fold in at assembly time; a set referenced twice declares once.
# `interval` is required for CIDR members and brings auto-merge, so overlaps collapse instead of failing.
fw_set_declare() {
	local id
	id="$(fw_set_id "$1")"
	echo "${2:-plain}" > "$FW_WORK/set.$id"
	echo "$1" > "$FW_WORK/name.$id"
}

# Buffered bans plus the source file, filtered to the set's own family: one bad element fails the whole document.
fw_set_elements() {
	local id="$1" src
	[ -s "$FW_WORK/elem.$id" ] && cat "$FW_WORK/elem.$id"
	src="$(fw_set_src "$(cat "$FW_WORK/name.$id")")"
	case "$(cat "$FW_WORK/set.$id" 2> /dev/null)" in
		v6*) [ -s "$src" ] && grep -oE "$FW_ADDR6_RE" "$src" ;;
		*) [ -s "$src" ] && grep -oE "$FW_ADDR_RE" "$src" ;;
	esac
	return 0
}

fw_render_sets() {
	local f id kind elems
	for f in "$FW_WORK"/set.*; do
		[ -e "$f" ] || continue
		id="${f##*/set.}"
		kind="$(cat "$f")"
		elems="$(fw_set_elements "$id" | sort -u | paste -sd, - | sed 's/,/, /g')"
		[ -n "$elems" ] && elems=" elements = { $elems };"
		case "$kind" in
			interval) echo "	set $id { type ipv4_addr; flags interval; auto-merge;${elems} }" ;;
			# Blocklists and jails take prefixes; the CrowdSec v6 set holds single addresses and stays plain.
			v6interval) echo "	set $id { type ipv6_addr; flags interval; auto-merge;${elems} }" ;;
			v6) echo "	set $id { type ipv6_addr;${elems} }" ;;
			*) echo "	set $id { type ipv4_addr;${elems} }" ;;
		esac
	done
}

# By the literal name: a dot in a list name is a regex wildcard, and a.b would answer for aXb.
fw_ipset_known() {
	grep -qF "LISTNAME='$1' " "$CONF_DIR/firewall/ipset.conf" 2> /dev/null
}

# For the feeder and the blocklist refresh, which must not rebuild the ruleset. Flush and refill in ONE
# transaction, so the set is never observably empty.
fw_set_replace() {
	local id doc re="$FW_ADDR_RE"
	# $3=6 loads an ipv6_addr set: filter the source to v6 literals (a v4 element fails the whole document).
	[ "${3:-}" = '6' ] && re="$FW_ADDR6_RE"
	id="$(fw_set_id "$1")"
	doc="$(mktemp)"
	{
		echo "flush set $FW_FAMILY $FW_TABLE $id"
		grep -oE "$re" "$2" 2> /dev/null \
			| sort -u | sed "s|^|add element $FW_FAMILY $FW_TABLE $id { |;s|$| }|"
	} > "$doc"
	"$FW_NFT" -f "$doc" 2> /dev/null
	local rc=$?
	rm -f "$doc"
	return $rc
}

fw_set_exists() {
	"$FW_NFT" list set "$FW_FAMILY" "$FW_TABLE" "$(fw_set_id "$1")" > /dev/null 2>&1
}

# Non-zero while a rule still matches it: nft refuses to delete a referenced set, and that is a useful answer.
fw_set_destroy() {
	"$FW_NFT" delete set "$FW_FAMILY" "$FW_TABLE" "$(fw_set_id "$1")" 2> /dev/null
}

# A port list becomes an anonymous nft set. iptables writes ranges with a colon, nft with a dash.
fw_port_expr() {
	local spec="${1//:/-}"
	case "$spec" in
		*,* | *-*) echo "{ ${spec//,/, } }" ;;
		*) echo "$spec" ;;
	esac
}

# A TCP loopback listener has no filesystem permissions: every local user reaches it, and only the connecting UID
# tells them apart. `reject` so a wrong caller fails at once instead of hanging.
fw_restrict_local_port() {
	local port="$1" uids="$2"
	fw_sec local "		oif lo tcp dport $port meta skuid != { $uids } reject with tcp reset"
}

# By name, the uid number is not the contract. Root is not optional: smoke probes these listeners over HTTP.
fw_local_allowed_uids() {
	local web
	web="$(id -u www-data 2> /dev/null)"
	if [ -n "$web" ]; then
		echo "0, $web"
	else
		echo "0"
	fi
}

# Host-local containers have no owner, so every local user reaches them: one rule per customer /24, webserver allowed.
# v4 only on purpose: the model lives in 127.20.0.0/16 on loopback, unreachable from outside even on a v6-only box.
fw_restrict_docker_nets() {
	local uconf u net uid cuid web uids
	web="$(id -u www-data 2> /dev/null)"
	for uconf in "$CONF_DIR"/users/*/user.conf; do
		[ -f "$uconf" ] || continue
		net=$(grep -o "^DOCKER_IP='[^']*'" "$uconf" | cut -d"'" -f2)
		[ -n "$net" ] || continue
		u=$(basename "$(dirname "$uconf")")
		uid=$(id -u "$u" 2> /dev/null) || continue
		# the companion runs the daemon and rootlesskit, which does the actual binding
		cuid=$(id -u "${u}-docker" 2> /dev/null)
		uids="0, $uid${cuid:+, $cuid}${web:+, $web}"
		fw_sec local "		ip daddr ${net%.*}.0/24 meta skuid != { $uids } reject"
	done
}

# One rules.conf record. Custom PassivePorts get neither FTP range.
fw_rule() {
	local action="$1" protocol="$2" port_val="$3" source="$4" conntrack_ftp="${5:-}"
	local proto expr=""

	proto="$(echo "$protocol" | tr '[:upper:]' '[:lower:]')"

	case "$source" in
		ipset:*)
			# Per the list's own family: a v6 list in an ipv4_addr set matched nothing and said nothing.
			if [ "$(fw_ipset_family "${source#ipset:}")" = 6 ]; then
				fw_set_declare "${source#ipset:}" v6interval
				expr="ip6 saddr @$(fw_set_id "${source#ipset:}") "
				[ "$action" = 'DROP' ] && expr="${expr}ip6 saddr != { $(fw_join "${FW_KEEP_V6[@]}") } "
			else
				fw_set_declare "${source#ipset:}" interval
				expr="ip saddr @$(fw_set_id "${source#ipset:}") "
				[ "$action" = 'DROP' ] && expr="${expr}ip saddr != { $(fw_join "${FW_KEEP_V4[@]}") } "
			fi
			;;
		0.0.0.0/0 | ::/0 | '') ;; # match everything: no family qualifier, so the rule covers v4 and v6
		*)
			# A malformed source is skipped, never rendered wide open; the validators prevent it, this is depth.
			case "$(fw_addr_family "$source")" in
				4) expr="ip saddr $source " ;;
				6) expr="ip6 saddr $source " ;;
				*)
					command -v log_event > /dev/null 2>&1 \
						&& log_event "${E_PARSING:-}" "firewall rule skipped: unparseable source '$source'"
					return 0
					;;
			esac
			;;
	esac

	# meta l4proto, not ip protocol: that one is v4 only, and after an ip6 saddr nft rejects the whole document.
	if [ "$proto" = 'icmp' ] || [ "$port_val" = '0' ]; then
		expr="${expr}meta l4proto $proto "
	elif [ "$port_val" = '21' ]; then
		if [ "$conntrack_ftp" != 'no' ]; then
			expr="${expr}${proto} dport $(fw_port_expr "$port_val") ct state new "
		else
			expr="${expr}${proto} dport $(fw_port_expr '20,21,12000-12100') "
		fi
	else
		expr="${expr}${proto} dport $(fw_port_expr "$port_val") "
	fi

	fw_sec rules "		${expr}$(echo "$action" | tr '[:upper:]' '[:lower:]')"
}

# A jail is a set plus one rule, not a chain of per-IP rules: constant-time match, a ban is an element add.
fw_jail_set() {
	echo "f2b_$1"
}

# nft cannot hold both families in one set, so a jail is two sets and two rules; v6 banning stays additive.
fw_jail_set6() {
	echo "f2b6_$1"
}

# Which of a jail's two sets an address belongs in. Empty for anything that is neither.
fw_jail_set_for() {
	case "$(fw_addr_family "$2")" in
		4) fw_jail_set "$1" ;;
		6) fw_jail_set6 "$1" ;;
	esac
}

# Scanner jails guard no login, so nobody legitimate lands there and a silent drop is cheap. Every other chain
# guards a login, where a phone with a stale password is normal and a visible reject lets the owner notice.
fw_jail_verdict() {
	case "$1" in
		WEBSCAN) echo "drop" ;;
		*) echo "reject with icmpx type port-unreachable" ;;
	esac
}

fw_jail_rebuild() {
	local chain="$1" protocol="$2" port_val="$3" proto verdict
	proto="$(echo "$protocol" | tr '[:upper:]' '[:lower:]')"
	verdict="$(fw_jail_verdict "$chain")"
	fw_set_declare "$(fw_jail_set "$chain")" interval
	fw_set_declare "$(fw_jail_set6 "$chain")" v6interval
	fw_sec jail "		ip saddr @$(fw_jail_set "$chain") ${proto} dport $(fw_port_expr "$port_val") $verdict"
	# Unconditional: it loads with ipv6 off, and the family-less service accepts let v6 reach the jailed ports.
	fw_sec jail "		ip6 saddr @$(fw_jail_set6 "$chain") ${proto} dport $(fw_port_expr "$port_val") $verdict"
}

# At the head of the chain, so a ban outranks the service accepts. fail2ban's actionstart lands here, so the
# verdict comes from fw_jail_verdict exactly as in fw_jail_rebuild.
fw_jail_attach() {
	local chain="$1" protocol="$2" port_val="$3" proto
	local -a verdict
	proto="$(echo "$protocol" | tr '[:upper:]' '[:lower:]')"
	read -r -a verdict <<< "$(fw_jail_verdict "$chain")"
	"$FW_NFT" add set "$FW_FAMILY" "$FW_TABLE" "$(fw_jail_set "$chain")" '{ type ipv4_addr; flags interval; auto-merge; }' 2> /dev/null
	"$FW_NFT" add set "$FW_FAMILY" "$FW_TABLE" "$(fw_jail_set6 "$chain")" '{ type ipv6_addr; flags interval; auto-merge; }' 2> /dev/null
	"$FW_NFT" list chain "$FW_FAMILY" "$FW_TABLE" input 2> /dev/null \
		| grep -q "@$(fw_jail_set "$chain") " && return 0
	"$FW_NFT" insert rule "$FW_FAMILY" "$FW_TABLE" input index 0 \
		ip saddr "@$(fw_jail_set "$chain")" "$proto" dport "$(fw_port_expr "$port_val")" \
		"${verdict[@]}" 2> /dev/null
	"$FW_NFT" insert rule "$FW_FAMILY" "$FW_TABLE" input index 0 \
		ip6 saddr "@$(fw_jail_set6 "$chain")" "$proto" dport "$(fw_port_expr "$port_val")" \
		"${verdict[@]}" 2> /dev/null
}

# Exact token match, not a regex from the jail name: a metacharacter would leave the handle empty and "succeed".
fw_jail_detach() {
	local handle tok
	for tok in "@$(fw_jail_set "$1")" "@$(fw_jail_set6 "$1")"; do
		handle=$("$FW_NFT" -a list chain "$FW_FAMILY" "$FW_TABLE" input 2> /dev/null \
			| awk -v tok="$tok" '{ for (i = 1; i < NF; i++) if ($i == tok) { print $NF; exit } }')
		[ -n "$handle" ] && "$FW_NFT" delete rule "$FW_FAMILY" "$FW_TABLE" input handle "$handle" 2> /dev/null
	done
	return 0
}

fw_jail_destroy() {
	"$FW_NFT" delete set "$FW_FAMILY" "$FW_TABLE" "$(fw_jail_set "$1")" 2> /dev/null
	"$FW_NFT" delete set "$FW_FAMILY" "$FW_TABLE" "$(fw_jail_set6 "$1")" 2> /dev/null
	return 0
}

# Wired means the set exists and the input chain still matches it.
fw_jail_wired() {
	"$FW_NFT" list set "$FW_FAMILY" "$FW_TABLE" "$(fw_jail_set "$1")" > /dev/null 2>&1 || return 1
	"$FW_NFT" list chain "$FW_FAMILY" "$FW_TABLE" input 2> /dev/null | grep -q "@$(fw_jail_set "$1") "
}

fw_set_chain_destroy() {
	"$FW_NFT" delete set "$FW_FAMILY" "$FW_TABLE" "$(fw_set_id "$2")" 2> /dev/null
	"$FW_NFT" delete chain "$FW_FAMILY" "$FW_TABLE" "$(fw_chain_id "$1")" 2> /dev/null
	return 0
}

fw_ban_add() {
	local set
	set="$(fw_jail_set_for "$1" "$2")"
	[ -n "$set" ] || return 1
	"$FW_NFT" add element "$FW_FAMILY" "$FW_TABLE" "$set" "{ $2 }" 2> /dev/null
	return 0
}

# Declared on demand, since a banlist row can name a chain that chains.conf no longer has.
fw_ban_emit() {
	local set
	set="$(fw_jail_set_for "$1" "$2")"
	[ -n "$set" ] || return 0
	# Interval like the jail declaration: a ban takes a CIDR, and a plain set fails the document on one.
	if [ "$set" = "$(fw_jail_set6 "$1")" ]; then
		fw_set_declare "$set" v6interval
	else
		fw_set_declare "$set" interval
	fi
	echo "$2" >> "$FW_WORK/elem.$set"
}

fw_ban_delete() {
	local set
	set="$(fw_jail_set_for "$1" "$2")"
	[ -n "$set" ] || return 1
	"$FW_NFT" delete element "$FW_FAMILY" "$FW_TABLE" "$set" "{ $2 }" 2> /dev/null
	return 0
}

# fw_batch_apply already wrote the ruleset file, so persisting is only the boot unit; the applied document reloads.
# Own file and unit, not the dpkg conffile /etc/nftables.conf: writing to one is how the distro jail returned.
fw_boot_unit_path() {
	echo "/lib/systemd/system/hestia-nftables.service"
}

# Always rendered, never appended: a template change reaches a box that already has the unit, and a second
# call cannot bolt on a second [Unit] block. daemon-reload only when the content actually moved.
fw_boot_unit_write() {
	local sd_unit tmp
	sd_unit="$(fw_boot_unit_path)"
	tmp="$(mktemp)"
	{
		echo "[Unit]"
		echo "Description=Loading Hestia firewall rules"
		echo "DefaultDependencies=no"
		echo "Wants=network-pre.target local-fs.target"
		echo "Before=network-pre.target"
		echo "After=local-fs.target"
		echo ""
		echo "[Service]"
		echo "Type=oneshot"
		echo "RemainAfterExit=yes"
		# No separate set-provisioning ExecStartPre: sets and their rules load in one transaction.
		echo "ExecStart=$FW_NFT -f $FW_RULESET"
		echo ""
		echo "[Install]"
		echo "WantedBy=multi-user.target"
	} > "$tmp"
	if cmp -s "$tmp" "$sd_unit" 2> /dev/null; then
		rm -f "$tmp"
		return 0
	fi
	mv -f "$tmp" "$sd_unit"
	chmod 644 "$sd_unit"
	systemctl -q daemon-reload
}

fw_persist_enable() {
	fw_boot_unit_write
	systemctl -q is-enabled hestia-nftables 2> /dev/null || systemctl -q enable hestia-nftables
	return 0
}

fw_persist_disable() {
	fw_boot_unit_write
	systemctl -q is-enabled hestia-nftables 2> /dev/null && systemctl -q disable hestia-nftables
	if [ -z "$FIREWALL_SYSTEM" ]; then
		rm -f "$(fw_boot_unit_path)"
		systemctl -q daemon-reload
	fi
	return 0
}

fw_table_destroy() {
	"$FW_NFT" delete table "$FW_FAMILY" "$FW_TABLE" 2> /dev/null
	rm -f "$FW_RULESET"
	return 0
}

# Also drops the cron line older installs appended to the daily queue, or such a box refreshes twice.
fw_blocklist_timer_install() {
	local src="$HESTIA/share/firewall/systemd"
	[ -d "$src" ] || return 0
	cp -f "$src/hestia-blocklist.service" "$src/hestia-blocklist.timer" /etc/systemd/system/ 2> /dev/null || return 0
	fw_blocklist_interval_apply "${BLOCKLIST_INTERVAL:-1d}"
	sed -i '/h-update-firewall-ipset/d' "$CONF_DIR/queue/daily.pipe" 2> /dev/null
	systemctl -q daemon-reload
	systemctl -q enable --now hestia-blocklist.timer 2> /dev/null
	return 0
}

# One global interval, not one per list. Re-validated here, not only in the command: the install path feeds
# BLOCKLIST_INTERVAL straight from hestia.conf into sed.
fw_blocklist_interval_apply() {
	local unit=/etc/systemd/system/hestia-blocklist.timer
	[ -f "$unit" ] || return 0
	[[ "$1" =~ ^[0-9]+(s|m|min|h|d|w)$ ]] || return 1
	sed -i "s|^OnUnitActiveSec=.*|OnUnitActiveSec=${1}|" "$unit"
	return 0
}

# Update entry firewall-keep-private (share/updates/0.24.json): existing rules only get the backstop with a new render.
firewall_keep_private_apply() {
	"$HESTIA/bin/h-update-firewall" > /dev/null 2>&1
}

fw_blocklist_timer_remove() {
	systemctl -q disable --now hestia-blocklist.timer 2> /dev/null
	rm -f /etc/systemd/system/hestia-blocklist.service /etc/systemd/system/hestia-blocklist.timer
	systemctl -q daemon-reload
	return 0
}

# Required, not tidy-up: iptables here is xtables-nft-multi, so its rules sit in our kernel backend and keep being
# evaluated. Driven off the live ruleset, not chains.conf, so a fail2ban chain whose record is gone goes too.
fw_legacy_teardown() {
	local ipt=/sbin/iptables c
	[ -x "$ipt" ] || return 0
	"$ipt" -S INPUT > /dev/null 2>&1 || return 0

	"$ipt" -P INPUT ACCEPT 2> /dev/null
	"$ipt" -F INPUT 2> /dev/null
	for c in $("$ipt" -S 2> /dev/null | sed -n 's/^-N \(fail2ban-[A-Za-z0-9_]*\|hestia\|hestia-crowdsec\)$/\1/p'); do
		"$ipt" -F "$c" 2> /dev/null
		"$ipt" -X "$c" 2> /dev/null
	done

	# The old boot unit and its saved dump would otherwise reinstate all of it on the next reboot.
	if [ -e /lib/systemd/system/hestia-iptables.service ]; then
		systemctl -q disable hestia-iptables 2> /dev/null
		rm -f /lib/systemd/system/hestia-iptables.service
		systemctl -q daemon-reload
	fi
	rm -f /etc/iptables.rules
	return 0
}
