#!/bin/bash

#===========================================================================#
#                                                                           #
# Hestia Control Panel - fail2ban Function Library                          #
#                                                                           #
#===========================================================================#

# One "configured fail2ban", shared by the installer and the addon commands. fail2ban is a CLIENT of the
# hestia firewall: action.d/hestia.conf maps its actions onto h-*-firewall-*, so bans survive a rebuild.

F2B_DIR="/etc/fail2ban"
F2B_OURS="$F2B_DIR/jail.d/hestia.local"
# Own file, not a block in hestia.local: a broken sed range over a delimited block deletes to EOF. Sorts last.
F2B_WHITELIST="$F2B_DIR/jail.d/hestia-zz-whitelist.local"

# The L7 jails on the per-domain web access logs. Gating, logpath repointing and the per-domain add/del all
# iterate this one list, so a new signature jail is added here only.
F2B_WEB_JAILS="web-botsearch web-badactor web-exploit web-authprobe"

# fail2ban takes a config it cannot parse with rc 0 and dies after, so the rc alone proves nothing: test first,
# then ask the server. 'reload' keeps a running daemon's bans where a reload suffices.
fail2ban_restart() {
	local i verb=restart
	[ "${1:-}" = 'reload' ] && verb=reload-or-restart
	fail2ban-client -t > /dev/null 2>&1 || return 2
	systemctl "$verb" fail2ban > /dev/null 2>&1 || return 1
	for i in 1 2 3 4 5 6 7 8 9 10; do
		sleep 1
		fail2ban-client ping > /dev/null 2>&1 && return 0
	done
	return 1
}

# jail.d/hestia.local is read last and wins; jail.local stays the admin's. hestia.local is only seeded when absent,
# everything else is overwritten from share/.
fail2ban_install_config() {
	local stage
	mkdir -p "$F2B_DIR/filter.d" "$F2B_DIR/action.d" "$F2B_DIR/jail.d"
	# The whole tree, not a file list, so no action or filter is left behind. Staged so share/'s jail.local
	# never reaches /etc, where being read first would resurrect a jail block an admin deleted.
	stage="$(mktemp -d)"
	cp -rf "$HESTIA/share/fail2ban/." "$stage/"
	[ -f "$F2B_OURS" ] || cp -f "$stage/jail.local" "$F2B_OURS"
	rm -f "$stage/jail.local"
	cp -rf "$stage/." "$F2B_DIR/"
	rm -rf "$stage"
	chmod 644 "$F2B_OURS" 2> /dev/null
}

# The panel jail bans on 80/443 as well, where a panel-proxy domain takes the same login. Update path: the action
# file comes from the tree, the jail line is ours to swap.
fail2ban_panel_action_apply() {
	[ -f "$F2B_OURS" ] || return 1
	cp -f "$HESTIA/share/fail2ban/action.d/hestia-panel.conf" "$F2B_DIR/action.d/" || return 1
	sed -i 's/^action[[:space:]]*=[[:space:]]*hestia\[name=HESTIA\][[:space:]]*$/action   = hestia-panel/' "$F2B_OURS" || return 1
	grep -q '^action   = hestia-panel$' "$F2B_OURS" || return 1
	systemctl -q is-active fail2ban 2> /dev/null || return 0
	# Restart, not reload: a reload drops the old action and never loads the new one.
	fail2ban_restart
}

# Update path of the dovecot filter and the mail password grace: hestia.local is only copied on a box without one,
# so the two mail jails are edited in place. Substitutions and appends only, never a delete in a range.
fail2ban_mail_grace_apply() {
	local grace="/usr/local/hestia/sbin/hestia-mail-grace"
	[ -f "$F2B_OURS" ] || return 1
	cp -f "$HESTIA/share/fail2ban/filter.d/hestia-dovecot.conf" "$F2B_DIR/filter.d/" || return 1
	sed -i '/^\[dovecot-iptables\]/,/^\[/s/^filter[[:space:]]*=[[:space:]]*dovecot[[:space:]]*$/filter   = hestia-dovecot/' "$F2B_OURS" || return 1
	if ! grep -q "^ignorecommand = $grace mailbox " "$F2B_OURS"; then
		sed -i "/^\[dovecot-iptables\]/,/^\[/{/^logpath[[:space:]]*=/a ignorecommand = $grace mailbox <ip> <F-USER>
}" "$F2B_OURS" || return 1
	fi
	if ! grep -q "^ignorecommand = $grace ip " "$F2B_OURS"; then
		sed -i "/^\[exim-iptables\]/,/^\[/{/^logpath[[:space:]]*=/a ignorecommand = $grace ip <ip>
}" "$F2B_OURS" || return 1
	fi
	[ "$(sed -n '/^\[dovecot-iptables\]/,/^\[/p' "$F2B_OURS" | grep -c "^filter   = hestia-dovecot$\|^ignorecommand = $grace mailbox ")" = 2 ] || return 1
	[ "$(sed -n '/^\[exim-iptables\]/,/^\[/p' "$F2B_OURS" | grep -c "^ignorecommand = $grace ip ")" = 1 ] || return 1
	systemctl -q is-active fail2ban 2> /dev/null || return 0
	fail2ban_restart
}

# From our own config, not by deleting the dpkg conffile jail.d/defaults-debian.conf, which an update restores.
fail2ban_disable_distro_jails() {
	grep -q '^\[sshd\]' "$F2B_OURS" 2> /dev/null && return 0
	{
		echo ""
		echo "# The distro's own sshd jail, disabled here rather than by deleting"
		echo "# jail.d/defaults-debian.conf: that is a dpkg conffile, so removing it is undone by the next"
		echo "# package update. Its banaction writes to a ruleset HestiaRE does not manage."
		echo "[sshd]"
		echo "enabled = false"
	} >> "$F2B_OURS"
}

# Both spellings: install.conf writes "true", the installer's locals "yes".
fail2ban_flag_on() {
	case "${1:-}" in
		true | yes | 1) return 0 ;;
		*) return 1 ;;
	esac
}

# Disable jails whose service is absent: a jail on a missing logpath never fires, silently.
fail2ban_gate_jails() {
	local mail="${1:-no}" ftp="${2:-no}"
	[ -f "$F2B_OURS" ] || return 0
	if ! fail2ban_flag_on "$mail"; then
		fail2ban_set_enabled 'exim-iptables' 'false'
		fail2ban_set_enabled 'dovecot-iptables' 'false'
	fi
	fail2ban_flag_on "$ftp" || fail2ban_set_enabled 'proftpd-iptables' 'false'
}

# Read WEB_SYSTEM from the FILE: the installer's own shell never sees the key it just wrote.
fail2ban_web_logdir() {
	local ws
	ws="$(sed -n "s/^WEB_SYSTEM='\([^']*\)'.*/\1/p" "$HESTIA/conf/hestia.conf" 2> /dev/null)"
	# Mailfront: the webmail vhosts log into the same domains dir, so the web jails watch that login on purpose.
	# Keep the fallback order in step with webmail_front() in include/main.sh.
	[ -n "$ws" ] || ws="$(sed -n "s/^WEBMAIL_FRONT='\([^']*\)'.*/\1/p" "$HESTIA/conf/hestia.conf" 2> /dev/null)"
	[ -n "$ws" ] || return 1
	echo "/var/log/$ws/domains"
}

# For the domain lifecycle: the first domain must arm the web jails and the last must disarm them, or protection is
# silently off or fail2ban cannot start. Rebuilds do not call it, since no domain appears or disappears there.
fail2ban_regate_web_apply() {
	[ -f "$F2B_OURS" ] || return 0
	local before after
	before=$(md5sum "$F2B_OURS" 2> /dev/null)
	fail2ban_gate_web_jail
	after=$(md5sum "$F2B_OURS" 2> /dev/null)
	if [ "$before" != "$after" ]; then
		systemctl reload-or-restart fail2ban 2> /dev/null || true
	fi
}

fail2ban_gate_web_jail() {
	local dir jail
	[ -f "$F2B_OURS" ] || return 0
	# CrowdSec owns L7 when present and these would double its http scenarios; without the marker they come back.
	if [ -f "$CONF_DIR/firewall/crowdsec.conf" ]; then
		for jail in $F2B_WEB_JAILS; do fail2ban_set_enabled "$jail" 'false'; done
		return 0
	fi
	if ! dir="$(fail2ban_web_logdir)"; then
		for jail in $F2B_WEB_JAILS; do fail2ban_set_enabled "$jail" 'false'; done
		return 0
	fi
	# Enable only when the glob can match: fail2ban refuses to start over a jail whose logpath matches nothing.
	local have_logs='false'
	ls "$dir"/*.log > /dev/null 2>&1 && have_logs='true'
	for jail in $F2B_WEB_JAILS; do
		fail2ban_set_enabled "$jail" "$have_logs"
		awk -v jail="[$jail]" -v path="$dir/*.log" '
			$0 == jail { inj = 1; print; next }
			/^\[/ { inj = 0 }
			inj && /^logpath[[:space:]]*=/ { print "logpath  = " path; next }
			{ print }
		' "$F2B_OURS" > "$F2B_OURS.tmp" && mv -f "$F2B_OURS.tmp" "$F2B_OURS"
	done
}

# fail2ban globs a logpath once at jail start, so a later domain goes unwatched. Never fails its caller.
fail2ban_watch_domain() {
	local verb="$1" domain="$2" dir jail
	[ -n "${FIREWALL_EXTENSION:-}" ] || return 0
	systemctl -q is-active fail2ban 2> /dev/null || return 0
	# The web jails are disabled under crowdsec, so arming here would flip a deliberately-off jail back on.
	[ -f "$CONF_DIR/firewall/crowdsec.conf" ] && return 0
	dir="$(fail2ban_web_logdir)" || return 0
	for jail in $F2B_WEB_JAILS; do
		case "$verb" in
			add)
				# First domain re-arms a jail pruned at install; later ones just add the new file.
				if fail2ban_jail_enabled "$jail"; then
					fail2ban-client set "$jail" addlogpath "$dir/$domain.log" tail > /dev/null 2>&1
				else
					fail2ban_rearm_jail "$jail"
				fi
				;;
			del) fail2ban-client set "$jail" dellogpath "$dir/$domain.log" > /dev/null 2>&1 ;;
		esac
	done
	return 0
}

# Flip one jail's `enabled` without disturbing the rest of its block.
fail2ban_set_enabled() {
	awk -v jail="[$1]" -v val="$2" '
		$0 == jail { inj = 1; print; next }
		/^\[/ { inj = 0 }
		inj && /^enabled[[:space:]]*=/ { print "enabled  = " val; next }
		{ print }
	' "$F2B_OURS" > "$F2B_OURS.tmp" && mv -f "$F2B_OURS.tmp" "$F2B_OURS"
}

# Is a jail currently enabled in our config?
fail2ban_jail_enabled() {
	[ "$(sed -n "/^\[$1\]/,/^\[/{/^enabled[[:space:]]*=/p}" "$F2B_OURS" 2> /dev/null | head -1 | tr -d ' ')" = 'enabled=true' ]
}

# fail2ban aborts startup on an enabled jail whose logpath matches no file, as proftpd's does on a fresh box.
# Run last; h-add-sys-proftpd and fail2ban_watch_domain re-arm later.
fail2ban_prune_empty_jails() {
	[ -f "$F2B_OURS" ] || return 0
	local j lp
	for j in $(fail2ban_enabled_jails); do
		lp="$(fail2ban_jail_logpath "$j")"
		[ -n "$lp" ] || continue
		compgen -G "$lp" > /dev/null 2>&1 || fail2ban_set_enabled "$j" 'false'
	done
}

# Re-enable a jail prune switched off, once its log exists; the reload re-globs.
fail2ban_rearm_jail() {
	[ "${FIREWALL_EXTENSION:-}" = 'fail2ban' ] || return 0
	systemctl -q is-active fail2ban 2> /dev/null || return 0
	fail2ban_jail_enabled "$1" && return 0
	fail2ban_set_enabled "$1" 'true'
	systemctl reload-or-restart fail2ban > /dev/null 2>&1
}

# Debian logs auth to the journal; create /var/log/auth.log so the sshd/phpmyadmin filters have a file.
fail2ban_ensure_authlog() {
	[ -e /var/log/auth.log ] && return 0
	touch /var/log/auth.log
	chmod 640 /var/log/auth.log
	chown root:adm /var/log/auth.log 2> /dev/null
}

# One jail per client in WEBMAIL_SYSTEM, read from the FILE for the installer's sake, plus the log it watches.
fail2ban_gate_webmail_jails() {
	local wm
	[ -f "$F2B_OURS" ] || return 0
	wm="$(sed -n "s/^WEBMAIL_SYSTEM='\([^']*\)'.*/\1/p" "$HESTIA/conf/hestia.conf" 2> /dev/null)"
	case ",$wm," in
		*,roundcube,*)
			fail2ban_set_enabled 'roundcube-auth' 'true'
			fail2ban_ensure_webmail_log /var/log/roundcube/userlogins.log
			;;
		*) fail2ban_set_enabled 'roundcube-auth' 'false' ;;
	esac
	case ",$wm," in
		*,tachyon,*)
			fail2ban_set_enabled 'tachyon-auth' 'true'
			fail2ban_ensure_webmail_log /var/log/tachyon/fail2ban/auth.txt
			;;
		*) fail2ban_set_enabled 'tachyon-auth' 'false' ;;
	esac
}

# For a runtime client add/remove. No-op unless fail2ban is ours and running, so callers need no gate.
fail2ban_refresh_webmail() {
	[ "${FIREWALL_EXTENSION:-}" = 'fail2ban' ] || return 0
	systemctl -q is-active fail2ban 2> /dev/null || return 0
	fail2ban_gate_webmail_jails
	systemctl reload-or-restart fail2ban > /dev/null 2>&1
}

# Owned by caddy, the pool that writes it; created up front so the jail has a file to watch.
fail2ban_ensure_webmail_log() {
	local f="$1"
	[ -e "$f" ] && return 0
	mkdir -p "$(dirname "$f")"
	touch "$f"
	chown -R caddy:caddy "$(dirname "$f")" 2> /dev/null
	chmod 640 "$f" 2> /dev/null
}

# The jails our config enables, by name: the reference the smoke and h-list-firewall-jail hold the daemon to.
fail2ban_enabled_jails() {
	[ -f "$F2B_OURS" ] || return 0
	awk '/^\[/ { j = substr($0, 2, length($0) - 2) }
	     /^enabled[[:space:]]*=[[:space:]]*true/ { if (j != "") print j }' "$F2B_OURS"
}

# The logpath our config gives a jail, before fail2ban expands any glob in it.
fail2ban_jail_logpath() {
	awk -v jail="[$1]" '
		$0 == jail { inj = 1; next }
		/^\[/ { inj = 0 }
		inj && /^logpath[[:space:]]*=/ { sub(/^logpath[[:space:]]*=[[:space:]]*/, ""); print; exit }
	' "$F2B_OURS" 2> /dev/null
}

# Mirror the whitelist into ignoreip so a whitelisted address is never even counted. Own file, whole rewrite.
fail2ban_sync_ignoreip() {
	local excludes="$CONF_DIR/firewall/excludes.conf" ips='' rc=0
	[ -d "$F2B_DIR/jail.d" ] || return 0
	# shellcheck source=/usr/local/hestia/include/firewall.sh
	declare -F fw_is_addr > /dev/null 2>&1 || source "$HESTIA/include/firewall.sh"
	# grep rc 0/1 is the normal empty case; rc>=2 is a real read failure and must not vanish into `|| true`.
	if [ -f "$excludes" ]; then
		ips="$(grep -oE "$FW_ADDR_RE|$FW_ADDR6_RE" "$excludes")" || rc=$?
		[ "$rc" -le 1 ] || check_result "$E_PARSING" "fail2ban_sync_ignoreip: cannot read $excludes (grep rc=$rc)"
		ips="$(printf '%s' "$ips" | paste -sd' ' -)"
	fi
	# Written even when the whitelist is empty: loopback belongs in ignoreip regardless.
	{
		echo "# Generated by fail2ban_sync_ignoreip from firewall/excludes.conf - do not edit."
		echo "# Manage entries with h-add-firewall-exclude / h-delete-firewall-exclude."
		echo "[DEFAULT]"
		echo "ignoreip = 127.0.0.1/8 ::1 $ips"
	} > "$F2B_WHITELIST"
	chmod 644 "$F2B_WHITELIST" 2> /dev/null
}

fail2ban_apply() {
	local mail="${1:-no}" ftp="${2:-no}"
	fail2ban_install_config
	fail2ban_disable_distro_jails
	fail2ban_gate_jails "$mail" "$ftp"
	fail2ban_gate_web_jail
	fail2ban_gate_webmail_jails
	fail2ban_ensure_authlog
	fail2ban_sync_ignoreip
	fail2ban_prune_empty_jails
	systemctl -q enable fail2ban 2> /dev/null
	fail2ban_restart
}

# Chain names are captured before the loop, since h-delete-firewall-chain rewrites chains.conf as it goes.
# Without KEEP_RECORDS the banlist records go too: an admin removing the addon wants the bans gone.
fail2ban_teardown() {
	local chains="$CONF_DIR/firewall/chains.conf" chain
	systemctl -q disable --now fail2ban 2> /dev/null
	if [ -f "$chains" ]; then
		for chain in $(sed -n "s/.*CHAIN='\([^']*\)'.*/\1/p" "$chains"); do
			"$BIN/h-delete-firewall-chain" "$chain" > /dev/null 2>&1
		done
	fi
	rm -f "$F2B_OURS" "$F2B_WHITELIST"
}
