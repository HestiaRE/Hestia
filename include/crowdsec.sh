#!/bin/bash
# Engine + nginx Layer-A bouncer for the current web model. Idempotent; no-op when nginx is not the front.

# Sourced here rather than in each caller. Guarded: re-sourcing would reset an in-flight batch.
# shellcheck source=/usr/local/hestia/include/firewall.sh
declare -F fw_set_chain_destroy > /dev/null 2>&1 || source "$HESTIA/include/firewall.sh"

# The EXPOSED web server (PROXY_SYSTEM fronts in 'both'), not "is nginx installed".
crowdsec_public_web() {
	if [ -n "$PROXY_SYSTEM" ]; then
		echo "$PROXY_SYSTEM"
	else
		echo "$WEB_SYSTEM"
	fi
}

# Local-only engine: no central blocklist pull and no signal sharing.
crowdsec_disable_capi() {
	local cfg="/etc/crowdsec/config.yaml"
	[ -f "$cfg" ] || return 0
	grep -qE '^[[:space:]]*credentials_path:.*online_api_credentials' "$cfg" || return 0
	sed -i -E \
		-e 's|^([[:space:]]*)(online_client:.*)|\1# \2 (local-only, #186)|' \
		-e 's|^([[:space:]]*)(credentials_path:[[:space:]]*/etc/crowdsec/online_api_credentials.*)|\1# \2|' \
		"$cfg"
}

# The packages leave both credential files 0644 though they hold machine passwords, and customers have shells here.
crowdsec_secure_credentials() {
	chmod 600 /etc/crowdsec/local_api_credentials.yaml /etc/crowdsec/online_api_credentials.yaml 2> /dev/null || true
}

# Inverse of crowdsec_disable_capi, for a runtime switch back to the community blocklist.
crowdsec_enable_capi() {
	local cfg="/etc/crowdsec/config.yaml" creds="/etc/crowdsec/online_api_credentials.yaml"
	[ -f "$cfg" ] || return 0
	local uncommented=0
	if ! grep -qE '^[[:space:]]*credentials_path:.*online_api_credentials' "$cfg"; then
		# Anchored on disable_capi's own marker, so a hand-commented config is not silently rewritten.
		sed -i -E \
			-e 's|^([[:space:]]*)# (online_client:.*) \(local-only, #186\)$|\1\2|' \
			-e 's|^([[:space:]]*)# (credentials_path:[[:space:]]*/etc/crowdsec/online_api_credentials.*)$|\1\2|' \
			"$cfg"
		grep -qE '^[[:space:]]*credentials_path:.*online_api_credentials' "$cfg" || {
			echo "CrowdSec: could not re-enable the CAPI online_client in $cfg - edit it by hand." >&2
			return 1
		}
		uncommented=1
	fi
	# `cscli capi register` loads the file before writing it: an empty one is enough, an absent one fails.
	if [ ! -f "$creds" ]; then
		: > "$creds"
		chmod 600 "$creds"
		if ! cscli capi register -f "$creds" > /dev/null 2>&1 || ! grep -q '^login:' "$creds" 2> /dev/null; then
			# Config pointing at absent credentials keeps the engine running but never reaching the CAPI.
			rm -f "$creds"
			[ "$uncommented" = 1 ] && crowdsec_disable_capi
			echo "CrowdSec: CAPI registration failed - left in local mode. Check egress and 'cscli capi status'." >&2
			return 1
		fi
	elif ! grep -q '^login:' "$creds" 2> /dev/null; then
		[ "$uncommented" = 1 ] && crowdsec_disable_capi
		echo "CrowdSec: $creds carries no CAPI login - fix or delete it, then retry." >&2
		return 1
	fi
	crowdsec_secure_credentials
}

# Derived from the box, never stored: install.conf is the recipe, not the state. mesh+capi is a legacy state that
# the callers map onto one model.
crowdsec_current_mode() {
	local mesh=0 capi=0
	# No engine is "none", not "local". The config alone is not the engine: a delete without PURGE_DATA keeps it.
	{ [ -f /etc/crowdsec/config.yaml ] && command -v cscli > /dev/null 2>&1; } || {
		echo "none"
		return 0
	}
	[ -f "$CONF_DIR/crowdsec/mesh.conf" ] && mesh=1
	grep -qE '^[[:space:]]*credentials_path:.*online_api_credentials' /etc/crowdsec/config.yaml 2> /dev/null && capi=1
	if [ "$mesh" = 1 ] && [ "$capi" = 1 ]; then
		echo "mesh+capi"
	elif [ "$mesh" = 1 ]; then
		echo "mesh"
	elif [ "$capi" = 1 ]; then
		echo "capi"
	else
		echo "local"
	fi
}

# CROWDSEC_SYSTEM from the box, never from the recipe; "none" is an empty key. Called wherever the model can change:
# apply, mode switch, mesh on/off, delete.
crowdsec_status_record() {
	local m
	m=$(crowdsec_current_mode)
	[ "$m" = 'none' ] && m=''
	change_sys_value "CROWDSEC_SYSTEM" "$m"
}

# SSH detection only when fail2ban is absent, or the two double up. Per scenario, not collection: crowdsecurity/linux
# would re-pull sshd, while dropping the two scenarios keeps its parsers. fail2ban is read from the FILE because the
# installer shell never sees the key it just wrote.
CS_BF_SCENARIOS="crowdsecurity/ssh-bf crowdsecurity/ssh-slow-bf"
crowdsec_gate_bruteforce() {
	command -v cscli > /dev/null 2>&1 || return 0
	local f2b changed='no' s f
	f2b="$(sed -n "s/^FIREWALL_EXTENSION='\([^']*\)'.*/\1/p" "$HESTIA/conf/hestia.conf" 2> /dev/null)"
	for s in $CS_BF_SCENARIOS; do
		f="/etc/crowdsec/scenarios/${s##*/}.yaml"
		if [ "$f2b" = 'fail2ban' ]; then
			[ -e "$f" ] && {
				cscli scenarios remove "$s" > /dev/null 2>&1
				changed='yes'
			}
		else
			[ -e "$f" ] || {
				cscli scenarios install "$s" > /dev/null 2>&1
				changed='yes'
			}
		fi
	done
	[ "$changed" = 'yes' ] && systemctl reload crowdsec > /dev/null 2>&1
	return 0
}

# libnginx-mod-http-lua requires the distribution's nginx ABI, which only the distribution's nginx provides; the
# nginx.org package (preset latest) has none, so the bouncer cannot load there.
crowdsec_l7_capable() {
	dpkg-query -W -f='${Provides}' nginx 2> /dev/null | grep -q 'nginx-abi-'
}

# Install and wire CrowdSec detection and the nginx Layer-A bouncer. Safe to re-run.
# Usage: crowdsec_apply [MODE]   MODE = capi (default) | local | mesh
# The caller passes the mode: only it knows whether the recipe (installer) or the box (a command) is the truth.
crowdsec_apply() {
	local share="$HESTIA/share/crowdsec" mode="${1:-capi}"

	if [ "$(crowdsec_public_web)" != "nginx" ]; then
		echo "CrowdSec: nginx is not the public front - nothing to apply."
		crowdsec_status_record
		return 0
	fi

	local l7='yes' pkgs='crowdsec libnginx-mod-http-lua'
	if ! crowdsec_l7_capable; then
		l7='no'
		pkgs='crowdsec'
		# Without the bouncer only the box's own detections reach L3, so central and peer bans would go unenforced.
		if [ "$mode" != 'local' ]; then
			echo "CrowdSec: this nginx has no lua module, so only local detection with the L3 feeder applies - mode local."
			mode='local'
		fi
	fi

	# The lua module auto-loads and pulls lua-resty-core itself.
	# shellcheck disable=SC2086 # deliberate package list
	DEBIAN_FRONTEND=noninteractive apt-get -y -qq install $pkgs > /dev/null 2>&1 \
		|| {
			echo "CrowdSec: package install failed" >&2
			return 1
		}

	# Hub failures are non-fatal: a network hiccup must not abort the setup.
	cscli hub update > /dev/null 2>&1 || true
	local col
	while read -r col; do
		case "$col" in '' | \#*) continue ;; esac
		cscli collections install "$col" > /dev/null 2>&1 || true
	done < "$share/collections.list"
	# nginx-req-limit-exceeded turns OUR Layer-B 429 into a ban. Removing taints the collection, so re-runs keep it out.
	cscli scenarios remove crowdsecurity/nginx-req-limit-exceeded > /dev/null 2>&1 || true
	# The nginx front logs under /var/log/$WEB_SYSTEM/domains with the real client IP, in 'both' on the apache2 path.
	mkdir -p /etc/crowdsec/acquis.d
	sed "s|%WEB_SYSTEM%|$WEB_SYSTEM|g" "$share/acquis.d/hestia-nginx.yaml" \
		> /etc/crowdsec/acquis.d/hestia-nginx.yaml

	if [ "$l7" = 'yes' ]; then
		crowdsec_l7_wire || return 1
	else
		# A box that had the bouncer keeps no fragment that requires the missing module.
		crowdsec_remove_nginx
	fi
	# Layer B (bot rate limiting) is include/botpolicy.sh; CrowdSec owns Layer A only.

	# Only 'capi' keeps the central blocklist. mesh is local plus peer exchange, so it must not enrol either.
	[ "$mode" = "capi" ] || crowdsec_disable_capi

	crowdsec_secure_credentials

	systemctl restart crowdsec > /dev/null 2>&1 || true
	if nginx -t > /dev/null 2>&1; then
		systemctl reload nginx > /dev/null 2>&1 || systemctl restart nginx > /dev/null 2>&1
	else
		echo "CrowdSec: nginx config test failed after wiring - not reloading" >&2
		return 1
	fi

	# L3 bans the same decisions at SYN level; non-fatal, so L7 stays up if its wiring fails.
	crowdsec_l3_setup || echo "CrowdSec: L3 feeder setup reported an issue" >&2

	# fail2ban owns brute force when present and CrowdSec owns Layer 7, so each side drops the other's jobs.
	crowdsec_gate_bruteforce
	if [ "$(sed -n "s/^FIREWALL_EXTENSION='\([^']*\)'.*/\1/p" "$HESTIA/conf/hestia.conf" 2> /dev/null)" = 'fail2ban' ] \
		&& [ -f /etc/fail2ban/jail.d/hestia.local ]; then
		# shellcheck source=/usr/local/hestia/include/fail2ban.sh
		declare -F fail2ban_gate_web_jail > /dev/null 2>&1 || source "$HESTIA/include/fail2ban.sh"
		fail2ban_gate_web_jail
		systemctl reload-or-restart fail2ban > /dev/null 2>&1
	fi

	crowdsec_status_record
	if [ "$l7" = 'yes' ]; then
		echo "CrowdSec: applied (nginx front, L7 bouncer hestia-nginx + L3 set feeder)."
	else
		echo "CrowdSec: applied (nginx front, L3 set feeder; no L7 bouncer on this nginx)."
	fi
}

# The L7 bouncer: key, lua code and the nginx init. Only where crowdsec_l7_capable.
crowdsec_l7_wire() {
	local share="$HESTIA/share/crowdsec"
	# cscli shows the key only at creation, so it lives in the lua config and is recreated only when missing.
	mkdir -p /etc/crowdsec/bouncers
	local keyfile="/etc/crowdsec/bouncers/hestia-nginx.lua"
	if ! cscli bouncers list -o raw 2> /dev/null | grep -q '^hestia-nginx,' || [ ! -s "$keyfile" ]; then
		cscli bouncers delete hestia-nginx > /dev/null 2>&1 || true
		local key
		key=$(cscli bouncers add hestia-nginx -o raw 2> /dev/null)
		[ -n "$key" ] || {
			echo "CrowdSec: bouncer registration failed" >&2
			return 1
		}
		# umask, not only the chmod below: the file holds the key from its first byte on.
		(
			umask 077
			cat > "$keyfile" <<- EOF
				-- CrowdSec nginx bouncer config. Generated - do not edit.
				return {
					host = "127.0.0.1", port = 8054,
					api_key = "$key",
					cache_ttl = 30, ban_ttl = 60, timeout = 1000, fail_open = true,
					dict = "crowdsec_cache",
				}
			EOF
		)
		# Only nginx's master (root) reads it, at (re)load before the workers fork.
		chmod 600 "$keyfile"
	fi

	# The code sits next to its config: share/ is a copy source, never a runtime include path. The update path never
	# runs this setup, so the code is refreshed only when the CrowdSec setup itself runs again.
	cp -f "$share/lua/hestia_bouncer.lua" /etc/crowdsec/bouncers/hestia_bouncer.lua
	cp -f "$share/nginx/crowdsec_init.conf" /etc/nginx/conf.d/crowdsec_init.conf
}

# Own feeder fills the set, h-update-firewall owns the DROP. Not the OS firewall bouncer: it panics.
crowdsec_l3_setup() {
	local share="$HESTIA/share/crowdsec"
	local marker="$CONF_DIR/firewall/crowdsec.conf"

	command -v cscli > /dev/null 2>&1 || {
		echo "CrowdSec: cscli missing, L3 skipped" >&2
		return 1
	}
	# jq drives the feeder's decision filter.
	command -v jq > /dev/null 2>&1 || DEBIAN_FRONTEND=noninteractive apt-get -y -qq install jq > /dev/null 2>&1

	mkdir -p "$CONF_DIR/firewall"
	if [ ! -f "$marker" ]; then
		cat > "$marker" <<- EOF
			# HestiaRE CrowdSec L3 marker: presence enables the set, the DROP chain and the feeder timer.
			# Managed by include/crowdsec.sh; do not edit.
			SET='crowdsec-blacklists'
		EOF
		chmod 640 "$marker"
	fi

	# h-update-firewall self-guards mid-install: no rules.conf yet, the configure stage rebuilds later.
	cp -f "$share/systemd/hestia-crowdsec-l3.service" /etc/systemd/system/hestia-crowdsec-l3.service
	cp -f "$share/systemd/hestia-crowdsec-l3.timer" /etc/systemd/system/hestia-crowdsec-l3.timer
	systemctl daemon-reload
	systemctl enable --now hestia-crowdsec-l3.timer > /dev/null 2>&1 || true
	"$BIN/h-update-firewall-crowdsec" > /dev/null 2>&1 || true
	"$BIN/h-update-firewall" > /dev/null 2>&1 || true
}

# Remove the L3 wiring; leaves the engine and /etc/crowdsec.
crowdsec_l3_teardown() {
	systemctl disable --now hestia-crowdsec-l3.timer > /dev/null 2>&1 || true
	systemctl stop hestia-crowdsec-l3.service > /dev/null 2>&1 || true
	rm -f /etc/systemd/system/hestia-crowdsec-l3.service /etc/systemd/system/hestia-crowdsec-l3.timer
	systemctl daemon-reload
	rm -f "$CONF_DIR/firewall/crowdsec.conf"
	# Directly: with the marker gone, h-update-firewall skips the crowdsec chain.
	fw_set_chain_destroy hestia-crowdsec crowdsec-blacklists
	rm -f "$CONF_DIR/firewall/crowdsec.iplist"
	"$BIN/h-update-firewall" > /dev/null 2>&1 || true
}

# The CROWDSEC field is intent, this is capability: an nginx without the bouncer fails the whole config or answers
# 500 per request. Keyed on the artefact the apply step installs; shared by the renderer and the restore's report.
crowdsec_domain_capable() {
	local sys
	if [ -n "$PROXY_SYSTEM" ]; then sys="$PROXY_SYSTEM"; else sys="$WEB_SYSTEM"; fi
	[ "$sys" = "nginx" ] && [ -f /etc/nginx/conf.d/crowdsec_init.conf ]
}

# Per-domain Layer-A ban check in the public nginx vhost dir (apache has no CrowdSec). Removed when off, so the
# vhost's `include ...nginx.crowdsec.conf*;` glob is a no-op for that domain.
crowdsec_render_domain_fragment() {
	local user="$1" domain="$2"

	local frag="$HOMEDIR/$user/conf/web/$domain/nginx.crowdsec.conf"
	# Leftovers of a removal go at the next rebuild.
	if ! crowdsec_domain_capable; then
		rm -f "$frag"
		return 0
	fi

	local rec cs
	rec=$(grep -m1 -F "DOMAIN='$domain'" "$CONF_DIR/users/$user/web.conf" 2> /dev/null)
	[ -n "$rec" ] || return 0
	cs=$(sed -n "s/.*CROWDSEC='\([^']*\)'.*/\1/p" <<< "$rec")

	# Rewrite phase, i.e. ahead of auth_basic 401, the forcessl 301 and limit_req: banned is refused first.
	if [ "$cs" = "yes" ]; then
		echo 'rewrite_by_lua_block { require("hestia_bouncer").allow() }' > "$frag"
		chown root:"$user" "$frag"
		chmod 640 "$frag"
	else
		rm -f "$frag"
	fi
}

# A removal deletes every fragment while the records keep CROWDSEC, so an add renders them again from the records.
crowdsec_render_all_fragments() {
	local wc user d
	for wc in "$CONF_DIR"/users/*/web.conf; do
		[ -e "$wc" ] || continue
		user=$(basename "$(dirname "$wc")")
		for d in $(sed -n "s/^DOMAIN='\([^']*\)'.*CROWDSEC='yes'.*/\1/p" "$wc"); do
			crowdsec_render_domain_fragment "$user" "$d"
		done
	done
	nginx -t > /dev/null 2>&1 && systemctl reload nginx > /dev/null 2>&1
	return 0
}

# Remove the nginx-side wiring (leaves the engine + /etc/crowdsec saved state).
crowdsec_remove_nginx() {
	rm -f /etc/nginx/conf.d/crowdsec_init.conf /etc/crowdsec/bouncers/hestia_bouncer.lua
	# Fragments left behind require the module just removed and answer 500, yet nginx -t passes while the lua module
	# is installed. Found from the tree, not the records: a fragment can outlive the record that asked for it.
	find "${HOMEDIR:-/home}" -mindepth 5 -maxdepth 5 -path '*/conf/web/*' -name 'nginx.crowdsec.conf' \
		-delete 2> /dev/null
	nginx -t > /dev/null 2>&1 && { systemctl reload nginx > /dev/null 2>&1 || true; }
}
