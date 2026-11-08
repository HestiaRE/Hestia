#!/bin/bash
# DNS target records of the objects on this box, and the zone each name lives in. Read-only: nothing
# here talks to a provider. h-list-dns-zones, h-list-dns-zone and h-list-mail-domain-dns read it.

DNS_ZONE_CACHE=/var/cache/hestia/dns-zones
DNS_TTL=14400

# dns_kv KEY LINE: one value of a record line. Values carry no single quote, so no parser is needed.
dns_kv() {
	[[ " $2" =~ \ $1=\'([^\']*)\' ]] && echo "${BASH_REMATCH[1]}"
}

# dns_nat IP: the address that belongs into public DNS, the NAT one where the IP record has it.
dns_nat() {
	local nat=''
	[ -f "$CONF_DIR/ips/$1" ] && nat=$(sed -n "s/^NAT='\([^']*\)'.*/\1/p" "$CONF_DIR/ips/$1" | head -n1)
	echo "${nat:-$1}"
}

# dns_ascii NAME: lower case, an IDN in its ACE form, which is what DNS carries.
dns_ascii() {
	local n="${1,,}" a
	# A failed conversion keeps the name: an empty one would match every zone.
	[[ "$n" == *[![:ascii:]]* ]] && a=$(idn2 -- "$n" 2> /dev/null) && [ -n "$a" ] && n="$a"
	echo "$n"
}

# Rows are tab separated: KIND OBJECT OWNER NAME TYPE PRIORITY TTL VALUE, NAME as an ASCII FQDN.
dns_row() {
	printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n' "$1" "$2" "$3" "$(dns_ascii "$4")" "$5" "$6" "$7" "$8"
}

# dns_web_rows USER: A and AAAA for each web domain and every alias, each name in whatever zone it lives in.
dns_web_rows() {
	local u="$1" line d ip ip6 alias n
	[ -f "$CONF_DIR/users/$u/web.conf" ] || return 0
	while IFS= read -r line; do
		d=$(dns_kv DOMAIN "$line")
		[ -n "$d" ] || continue
		ip=$(dns_kv IP "$line")
		ip6=$(dns_kv IP6 "$line")
		alias=$(dns_kv ALIAS "$line")
		[ "$ip6" = 'none' ] && ip6=''
		for n in "$d" ${alias//,/ }; do
			[ -n "$ip" ] && dns_row web "$d" "$u" "$n" A '' "$DNS_TTL" "$(dns_nat "$ip")"
			[ -n "$ip6" ] && dns_row web "$d" "$u" "$n" AAAA '' "$DNS_TTL" "$ip6"
		done
	done < "$CONF_DIR/users/$u/web.conf"
	return 0
}

# dns_first_addrs USER: "v4 v6", the user's first address per family as get_user_ip picks them, "-" for none.
dns_first_addrs() {
	local a v4='' v6=''
	# get_user_ips reads the global $user.
	for a in $(user="$1" get_user_ips); do
		case "$(ip_family "$a")" in
			4) [ -n "$v4" ] || v4="$a" ;;
			6) [ -n "$v6" ] || v6="$a" ;;
		esac
	done
	echo "${v4:--} ${v6:--}"
}

# dns_mail_addrs USER DOMAIN: "v4 v6" the domain sends from, its webmail follows them. The pins exim sends
# from first; without them (a relay sends) the user's first address per family.
dns_mail_addrs() {
	local u="$1" d="$2" v4='' v6=''
	{ read -r v4 < "$HOMEDIR/$u/conf/mail/$d/ip"; } 2> /dev/null
	{ read -r v6 < "$HOMEDIR/$u/conf/mail/$d/ipv6"; } 2> /dev/null
	[[ "$v4" =~ ^[0-9.]+$ ]] || v4=''
	[[ "$v6" =~ ^[0-9A-Fa-f:]+$ ]] || v6=''
	if [ -z "$v4" ] && [ -z "$v6" ]; then
		read -r v4 v6 <<< "$(dns_first_addrs "$u")"
		[ "$v4" = '-' ] && v4=''
		[ "$v6" = '-' ] && v6=''
	fi
	[ -n "$v4" ] && v4=$(dns_nat "$v4")
	echo "${v4:--} ${v6:--}"
}

# dns_mail_rows USER [DOMAIN]: what a mail domain needs, the values the mail "DNS records" page has shown.
dns_mail_rows() {
	local u="$1" line d v4 v6 sel pub spf n
	[ -f "$CONF_DIR/users/$u/mail.conf" ] || return 0
	while IFS= read -r line; do
		d=$(dns_kv DOMAIN "$line")
		[ -n "$d" ] || continue
		[ -z "$2" ] || [ "$d" = "$2" ] || continue
		read -r v4 v6 <<< "$(dns_mail_addrs "$u" "$d")"
		[ "$v4" = '-' ] && v4=''
		[ "$v6" = '-' ] && v6=''
		for n in mail ${WEBMAIL_SYSTEM:+"${WEBMAIL_ALIAS:-webmail}"}; do
			[ -n "$v4" ] && dns_row mail "$d" "$u" "$n.$d" A '' "$DNS_TTL" "$v4"
			[ -n "$v6" ] && dns_row mail "$d" "$u" "$n.$d" AAAA '' "$DNS_TTL" "$v6"
		done
		dns_row mail "$d" "$u" "$d" MX 10 "$DNS_TTL" "mail.$(dns_ascii "$d")."
		# Both families in one record: exim sends from whichever family the receiving MX offers.
		spf="v=spf1 a mx${v4:+ ip4:$v4}${v6:+ ip6:$v6} -all"
		dns_row mail "$d" "$u" "$d" TXT '' "$DNS_TTL" "$spf"
		dns_row mail "$d" "$u" "_dmarc.$d" TXT '' "$DNS_TTL" 'v=DMARC1; p=quarantine; pct=100'
		[ -e "$CONF_DIR/users/$u/mail/$d.pub" ] || continue
		# What exim signs with: the rebuild falls back to mail for a selector that is no DNS label.
		sel=$(dns_kv DKIM_SELECTOR "$line")
		dkim_selector_ok "$sel" || sel=mail
		pub=$(grep -v -- '-----' "$CONF_DIR/users/$u/mail/$d.pub" | tr -d '\n')
		dns_row mail "$d" "$u" "$sel._domainkey.$d" TXT '' 3600 "v=DKIM1; k=rsa; p=$pub"
	done < "$CONF_DIR/users/$u/mail.conf"
	return 0
}

# dns_server_rows: the hostname on the admin's first address per family.
dns_server_rows() {
	local h v4 v6
	h=$(hostname -f 2> /dev/null)
	[[ "$h" == *.* ]] || return 0
	read -r v4 v6 <<< "$(dns_first_addrs "$ROOT_USER")"
	[ "$v4" != '-' ] && dns_row server "$h" "$ROOT_USER" "$h" A '' "$DNS_TTL" "$(dns_nat "$v4")"
	[ "$v6" != '-' ] && dns_row server "$h" "$ROOT_USER" "$h" AAAA '' "$DNS_TTL" "$v6"
	return 0
}

# dns_rows: every row of every user on the box, the server's under the admin.
dns_rows() {
	local d u
	for d in "$CONF_DIR"/users/*; do
		[ -e "$d" ] || continue
		[ -f "$d/user.conf" ] || continue
		u=${d##*/}
		dns_web_rows "$u"
		dns_mail_rows "$u"
	done
	dns_server_rows
}

# dns_owned ROWS: one owner per name and type. The most specific object wins (more labels in its own name),
# on a tie mail before web before the server, so a web domain mail.example.com beats the mail domain's row there.
dns_owned() {
	awk -F '\t' 'BEGIN { OFS = FS; r["mail"] = 1; r["web"] = 2; r["server"] = 3 }
		NF { print split($2, l, "."), r[$1], $0 }' <<< "$1" \
		| sort -t $'\t' -k6,6 -k7,7 -k1,1nr -k2,2n \
		| awk -F '\t' 'BEGIN { OFS = FS }
			{ k = $6 FS $7; o = $3 FS $4 FS $5; if (k in w && w[k] != o) next; w[k] = o
			  print $3, $4, $5, $6, $7, $8, $9, $10 }'
}

# dns_public_suffix ZONE: a public suffix is where a lookup for an unregistered name ends, never a zone of ours.
dns_public_suffix() {
	local f
	for f in /usr/share/publicsuffix/public_suffix_list.dat "$CONF_DIR/public_suffix_list.dat"; do
		[ -f "$f" ] || continue
		grep -qxF -- "$1" "$f"
		return
	done
	return 1
}

# dns_soa_lookup NAME: the zone NAME lives in, from public DNS; empty when that cannot be told.
# The answer's SOA counts only when owned by NAME itself: a CNAME is followed, and the SOA after it is the
# target's zone. A CNAME means NAME is no apex, so the parent is asked; otherwise the authority section names
# the closest enclosing zone, for a missing name as for an existing one. An answer with no SOA at all is the
# local resolver speaking for the box's own hostname (systemd-resolved): the parent is asked as well.
dns_soa_lookup() {
	local q="$1" out zone
	while [[ "$q" == *.* ]]; do
		out=$(timeout 5 dig +nosearch +time=2 +tries=1 +noall +comments +answer +authority SOA "$q" 2> /dev/null)
		[[ "$out" == *"status: NOERROR"* || "$out" == *"status: NXDOMAIN"* ]] || return 0
		zone=$(awk -v q="$q." '
			/^;; ANSWER SECTION/ { s = "a"; next } /^;; AUTHORITY SECTION/ { s = "u"; next } /^;/ || NF < 4 { next }
			s == "a" && $4 == "SOA" && tolower($1) == q { print "self"; exit }
			s == "a" && $4 == "CNAME" && tolower($1) == q { c = 1 }
			s == "u" && $4 == "SOA" && !u { u = tolower($1) }
			END { if (c) print "up"; else if (u) print u; else print "up" }' <<< "$out" | head -n1)
		case "$zone" in
			self) zone="$q" ;;
			up)
				q="${q#*.}"
				continue
				;;
		esac
		zone="${zone%.}"
		[[ "$zone" == *.* ]] || return 0
		[ "$1" = "$zone" ] || [[ "$1" == *".$zone" ]] || return 0
		dns_public_suffix "$zone" && return 0
		echo "$zone"
		return 0
	done
}

# dns_zone NAME: dns_soa_lookup behind a box-local cache (a day, an hour for an unknown). Public data only,
# recomputable, and never part of a backup.
dns_zone() {
	local n now line zone
	n=$(dns_ascii "${1#\*.}")
	[ -n "$n" ] || return 0
	now=$(date +%s)
	if [ -f "$DNS_ZONE_CACHE" ]; then
		line=$(awk -F '\t' -v n="$n" -v now="$now" \
			'$1 == n && (($2 != "-" && now - $3 < 86400) || now - $3 < 3600) { print $2; exit }' "$DNS_ZONE_CACHE")
		if [ -n "$line" ]; then
			[ "$line" = '-' ] || echo "$line"
			return 0
		fi
	fi
	zone=$(dns_soa_lookup "$n")
	dns_zone_remember "$n" "${zone:--}" "$now"
	echo "$zone"
}

dns_zone_remember() {
	local tmp
	mkdir -p "${DNS_ZONE_CACHE%/*}"
	tmp=$(mktemp "$DNS_ZONE_CACHE.XXXXXX") || return 0
	{
		[ -f "$DNS_ZONE_CACHE" ] && awk -F '\t' -v n="$1" '$1 != n' "$DNS_ZONE_CACHE"
		printf '%s\t%s\t%s\n' "$1" "$2" "$3"
	} > "$tmp"
	chmod 644 "$tmp"
	mv -f "$tmp" "$DNS_ZONE_CACHE"
}

# dns_sections: zone rows (ZONE STATE KIND OBJECT OWNER NAME TYPE PRIORITY TTL VALUE) as ZONE STATE SECTION
# OBJECT OWNER REL TYPE PRIORITY TTL VALUE, sorted for display: the apex objects, then each sub with its web
# before its mail, then aliases, then the server. From inside its zone a web object named after the zone is
# "web", one below it "sub", one elsewhere "alias" (its aliases here); REL is @ for the apex.
dns_sections() {
	awk -F '\t' 'BEGIN { OFS = FS; t["A"] = 1; t["AAAA"] = 2; t["MX"] = 3; t["TXT"] = 4 }
		NF {
			z = $1; k = $3; n = $6; under = (substr($4, length($4) - length(z)) == "." z)
			if (k == "web") k = ($4 == z) ? "web" : (under ? "sub" : "alias")
			g = (k == "server") ? 3 : ($4 == z) ? 0 : under ? 1 : 2
			r = (n == z) ? "@" : substr(n, 1, length(n) - length(z) - 1)
			print z, g, $4, (k == "mail"), (n == $4 ? 0 : 1), r, t[$7], $2, k, $5, $7, $8, $9, $10
		}' \
		| sort -t $'\t' -k1,1 -k2,2n -k3,3 -k4,4n -k5,5n -k6,6 -k7,7n \
		| awk -F '\t' 'BEGIN { OFS = FS } { print $1, $8, $9, $3, $10, $6, $11, $12, $13, $14 }'
}

# dns_zone_of NAME: "ZONE STATE". A name public DNS cannot place falls to the shortest name on the box it is or
# lies under (DNS_NAMES), marked unknown: sub and www land with their parent, never guessed from the last labels.
dns_zone_of() {
	local z
	z=$(dns_zone "$1")
	if [ -n "$z" ]; then
		echo "$z resolved"
		return 0
	fi
	z=$(awk -v n="${1#\*.}" '{ m = $0; sub(/^\*\./, "", m) }
		(n == m || substr(n, length(n) - length(m)) == "." m) && (!b || length(m) < length(b)) { b = m }
		END { print b }' <<< "$DNS_NAMES")
	echo "${z:-${1#\*.}} unknown"
}

# dns_anchored: the owned rows of the box, each prefixed with its anchor, the name whose zone it shares: the
# longest object name on the box it is or lies under, so a sub domain delegated elsewhere still resolves on its
# own; an alias outside every object hangs on its shortest root. One lookup per anchor, not per name.
dns_anchored() {
	local all o
	all=$(dns_owned "$(dns_rows)")
	{
		cut -f 2 <<< "$all" | sort -u | while IFS= read -r o; do
			[ -n "$o" ] && printf 'O\t%s\n' "$(dns_ascii "$o")"
		done
		sed '/^$/d; s/^/R\t/' <<< "$all"
	} | awk -F '\t' '
		function up(m) { p = index(m, "."); return p ? substr(m, p + 1) : "" }
		$1 == "O" { obj[$2] = 1; next }
		{ row[++n] = substr($0, 3); nm = $5; sub(/^\*\./, "", nm); key[n] = nm; names[nm] = 1 }
		END {
			for (i = 1; i <= n; i++) {
				a = ""
				for (m = key[i]; m != "" && a == ""; m = up(m)) if (m in obj) a = m
				if (a == "") for (m = key[i]; m != ""; m = up(m)) if (m in names) a = m
				print a "\t" row[i]
			}
		}'
}

declare -A DNS_ZONE_MEMO

# dns_anchor_zone ANCHOR: dns_zone_of into DNS_ZONE_STATE, once per anchor and run. A global, not stdout: in a
# $() the memo would die with the subshell.
dns_anchor_zone() {
	[ -n "${DNS_ZONE_MEMO[$1]}" ] || DNS_ZONE_MEMO[$1]=$(dns_zone_of "$1")
	DNS_ZONE_STATE="${DNS_ZONE_MEMO[$1]}"
}

# dns_zone_rows ZONES: the owned rows of every user that live in one of ZONES (blank separated), with zone and
# state prefixed. Only names under one of them are looked up, so a delegated sub-zone drops out by its own SOA.
dns_zone_rows() {
	local zones=" $1 " z state hit anchor kind obj owner name rest rows
	rows=$(dns_anchored)
	DNS_NAMES=$(cut -f 5 <<< "$rows")
	while IFS=$'\t' read -r anchor kind obj owner name rest; do
		[ -n "$name" ] || continue
		hit=''
		for z in $1; do
			if [ "${name#\*.}" = "$z" ] || [[ "$name" == *".$z" ]]; then
				hit=1
				break
			fi
		done
		[ -n "$hit" ] || continue
		dns_anchor_zone "$anchor"
		read -r z state <<< "$DNS_ZONE_STATE"
		[[ "$zones" == *" $z "* ]] || continue
		printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\n' "$z" "$state" "$kind" "$obj" "$owner" "$name" "$rest"
	done <<< "$rows"
}

# dns_user_zones USER: the zones USER's rows live in, one "ZONE STATE" per line.
dns_user_zones() {
	local anchor kind obj owner rest rows
	rows=$(dns_anchored)
	DNS_NAMES=$(cut -f 5 <<< "$rows")
	while IFS=$'\t' read -r anchor kind obj owner rest; do
		[ "$owner" = "$1" ] || continue
		dns_anchor_zone "$anchor"
		echo "$DNS_ZONE_STATE"
	done <<< "$rows" | sort -u
}
