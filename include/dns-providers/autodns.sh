#!/bin/bash
# DNS provider driver: InterNetX AutoDNS, JSON API. Sourced by include/dns-provider.sh, which sets DNSP_KEY,
# DNSP_CONTEXT and DNSP_ACCOUNT. Key rights: zone query, 0202002 (stream), zone update bulk (PATCH); never the
# zone update itself, whose PUT replaces the whole zone without locking.

DNSP_AUTODNS_URL=${DNSP_AUTODNS_URL:-https://api.autodns.com/v1}

# dnsp_autodns_req METHOD PATH [BODY]: the answer's JSON into DNSP_BODY. rc 1 when the key is refused, 2 for any
# other failure, with the provider's text in DNSP_ERROR. Globals, not stdout: a $() would lose DNSP_ERROR with its
# subshell. The key travels on stdin, never in the process arguments.
dnsp_autodns_req() {
	local out code body=() json text
	[ -n "${3-}" ] && body=(-H 'Content-Type: application/json' --data-binary "$3")
	dnsp_throttle 0.4
	out=$(printf 'header = "X-Domainrobot-Api-Key: %s"\n' "$DNSP_KEY" \
		| curl -sS -m 30 -K - -X "$1" -A "HestiaRE/${VERSION:-0}" -H "X-Domainrobot-Context: ${DNSP_CONTEXT:-4}" \
			"${body[@]}" -w '\n%{http_code}' "$DNSP_AUTODNS_URL$2" 2>&1)
	code=${out##*$'\n'}
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
		jq -r '.data[]? | "\(.origin | ascii_downcase)\t\(.virtualNameServer)"' <<< "$page"
		n=$(jq '.data | length' <<< "$page")
		[ "$n" -ge 100 ] || return 0
		off=$((off + 100))
	done
}

# dnsp_autodns_records ZONE REF: "REL TYPE PRIORITY TTL VALUE FLAG", REL relative to the zone and @ for the apex,
# targets as absolute names. The main IP is no record but the apex address (FLAG main); wwwInclude serves the
# whole apex at www as well (FLAG include), next to any www record of its own.
dnsp_autodns_records() {
	dnsp_autodns_req GET "/zone/$1/$2" || return
	jq -r --arg z "$1" '
		def abs: if endswith(".") then ascii_downcase elif . == "@" or . == "" then "\($z)." else "\(. | ascii_downcase).\($z)." end;
		.data[0] as $d
		| [ (if ($d.main.address // "") != "" then
				{rel: "@", type: (if ($d.main.address | test(":")) then "AAAA" else "A" end), prio: "",
				 ttl: ($d.main.ttl // ""), value: $d.main.address, flag: "main"} else empty end),
			($d.resourceRecords[]? | {rel: (if (.name // "") == "" then "@" else (.name | ascii_downcase) end),
				type: .type, prio: (.pref // ""), ttl: (.ttl // ""),
				value: (if (.type | IN("MX", "CNAME", "NS", "PTR")) then (.value | abs) else .value end), flag: ""}) ]
		| . + (if $d.wwwInclude then [ .[] | select(.rel == "@" and (.type | IN("A", "AAAA")))
				| .rel = "www" | .flag = "include" ] else [] end)
		| .[] | [.rel, .type, (.prio | tostring), (.ttl | tostring), .value, .flag] | join("\t")' <<< "$DNSP_BODY"
}
