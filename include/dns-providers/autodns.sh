#!/bin/bash
# DNS provider driver: InterNetX AutoDNS, JSON API. Sourced by include/dns-provider.sh, which sets DNSP_KEY,
# DNSP_CONTEXT and DNSP_ACCOUNT. Key rights: zone query, 0202002 (stream), zone update bulk (PATCH); never the
# zone update itself, whose PUT replaces the whole zone without locking.

# Fixed, never from the environment: whoever sets the URL is sent the key.
DNSP_AUTODNS_URL=https://api.autodns.com/v1

# dnsp_autodns_req METHOD PATH [BODY]: the answer's JSON into DNSP_BODY. rc 1 when the key is refused, 2 for any
# other failure, with the provider's text in DNSP_ERROR. Globals, not stdout: a $() would lose DNSP_ERROR with its
# subshell. The key travels on stdin, never in the process arguments. A 429 (the limit is per IP, shared behind a
# NAT) is tried once more.
dnsp_autodns_req() {
	local out code body=() json text try
	[ -n "${3-}" ] && body=(-H 'Content-Type: application/json' --data-binary "$3")
	for try in 1 2; do
		dnsp_throttle 0.4
		out=$(printf 'header = "X-Domainrobot-Api-Key: %s"\n' "$DNSP_KEY" \
			| curl -sS --connect-timeout 5 -m 20 -K - -X "$1" -A "HestiaRE/${VERSION:-0}" \
				-H "X-Domainrobot-Context: ${DNSP_CONTEXT:-4}" "${body[@]}" -w '\n%{http_code}' "$DNSP_AUTODNS_URL$2" 2>&1)
		code=${out##*$'\n'}
		[ "$code" = 429 ] && [ "$try" = 1 ] || break
		sleep 1
	done
	json=${out%$'\n'*}
	DNSP_BODY=''
	text=$(jq -r '[.messages[]?.text, .status.text] | map(select(. != null and . != "")) | first // empty' \
		<<< "$json" 2> /dev/null)
	case "$code" in
		2??) ;;
		401 | 403)
			DNSP_ERROR="key refused: ${text:-HTTP $code}"
			return 1
			;;
		*)
			DNSP_ERROR="${text:-HTTP ${code:-none}: ${json:0:200}}"
			return 2
			;;
	esac
	if [ "$(jq -r '.status.type // empty' <<< "$json" 2> /dev/null)" != 'SUCCESS' ]; then
		DNSP_ERROR="${text:-no SUCCESS in the answer}"
		return 2
	fi
	DNSP_BODY=$json
}

dnsp_autodns_hello() {
	dnsp_autodns_req GET /hello
}

# Zones of the account, "ZONE<TAB>REF" per line; REF is the virtual name server the record calls need.
dnsp_autodns_zones() {
	local off=0 page n
	while :; do
		dnsp_autodns_req POST /zone/_search "{\"view\":{\"limit\":100,\"offset\":$off}}" || return
		page=$DNSP_BODY
		# A name that is no host name would end up in file names, arguments and the table columns.
		jq -r '.data[]? | [(.origin | ascii_downcase), (.virtualNameServer | ascii_downcase)]
			| select(all(test("^[a-z0-9_]([a-z0-9_.-]*[a-z0-9])?$"))) | join("\t")' <<< "$page"
		n=$(jq '.data | length' <<< "$page")
		[ "$n" -ge 100 ] || return 0
		off=$((off + 100))
	done
}

# dnsp_autodns_records ZONE REF: "REL TYPE PRIORITY TTL VALUE FLAG RAW", REL relative to the zone and @ for the
# apex, targets as absolute names, RAW the value as stored, which a rem has to name. The main IP is no record but
# the apex address (FLAG main); wwwInclude serves the whole apex at www as well (FLAG include), next to any www
# record of its own; the zone's name servers come as apex NS (FLAG ns). A control character in a field would shift
# the columns or start a row: it becomes a blank and the row FLAG ctl, which no write may take as the stored value.
dnsp_autodns_records() {
	dnsp_autodns_req GET "/zone/$1/$2" || return
	jq -r --arg z "$1" '
		def abs: if endswith(".") then ascii_downcase elif . == "@" or . == "" then "\($z)." else "\(. | ascii_downcase).\($z)." end;
		def ctl: "[[:cntrl:]]";
		.data[0] as $d
		| [ (if ($d.main.address // "") != "" then
				{rel: "@", type: (if ($d.main.address | test(":")) then "AAAA" else "A" end), prio: "",
				 ttl: ($d.main.ttl // ""), value: $d.main.address, flag: "main", raw: $d.main.address} else empty end),
			($d.resourceRecords[]? | {rel: (if (.name // "") == "" then "@" else (.name | ascii_downcase) end),
				type: .type, prio: (.pref // ""), ttl: (.ttl // ""),
				value: (if (.type | IN("MX", "CNAME", "NS", "PTR", "ALIAS")) then (.value | abs) else .value end), flag: "",
				raw: .value}),
			($d.nameServers[]? | {rel: "@", type: "NS", prio: "", ttl: "", value: (.name | ascii_downcase | rtrimstr(".") + "."),
				flag: "ns", raw: .name}) ]
		| . + (if $d.wwwInclude then [ .[] | select(.rel == "@" and (.type | IN("A", "AAAA")))
				| .rel = "www" | .flag = "include" ] else [] end)
		| .[] | [.rel, .type, (.prio | tostring), (.ttl | tostring), (.value | tostring), (.raw | tostring)] as $f
		| ($f | map(gsub(ctl; " "))) as $c
		| $c[0:5] + [if ($f | any(test(ctl))) then "ctl" else .flag end, $c[5]] | join("\t")' <<< "$DNSP_BODY"
}

# dnsp_autodns_change ZONE REF FILE: the "add|rem REL TYPE PRIORITY TTL VALUE" lines of FILE as one stream. A rem
# names the RAW value and no TTL: the provider matches it literally, a stored relative target is no absolute one,
# and a rem matching nothing still answers SUCCESS, so only a re-read proves it.
dnsp_autodns_change() {
	local body
	body=$(jq -R -n '[inputs | select(length > 0) | split("\t")
		| {op: .[0], r: ({name: (if .[1] == "@" then "" else .[1] end), type: .[2], value: .[5]}
			+ (if .[2] == "MX" and .[3] != "" then {pref: (.[3] | tonumber)} else {} end)
			+ (if .[0] == "add" and .[4] != "" then {ttl: (.[4] | tonumber)} else {} end))}]
		| {adds: [.[] | select(.op == "add") | .r], rems: [.[] | select(.op == "rem") | .r]}' < "$3") || {
		DNSP_ERROR='unreadable change list'
		return 2
	}
	dnsp_autodns_req POST "/zone/$1/_stream" "$body"
}

# dnsp_autodns_main ZONE REF ADDRESS TTL: the main IP. A PATCH changes only the fields it names.
dnsp_autodns_main() {
	local body
	body=$(jq -n --arg z "$1" --arg a "$3" --arg t "$4" '{origin: $z, main: ({address: $a}
		+ (if $t != "" then {ttl: ($t | tonumber)} else {} end))}') || return 2
	dnsp_autodns_req PATCH "/zone/$1/$2" "$body"
}
