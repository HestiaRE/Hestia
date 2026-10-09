#!/bin/bash

# The web-serving model, shared by the installer and the live switch (h-add/delete-sys-nginx/-apache2) so they cannot drift.

# KEY=VALUE per model, in the installer's order. The switch clears every model key not emitted here.
web_model_keyset() {
	case "$1" in
		apache | APACHE)
			printf '%s\n' \
				"WEB_SYSTEM=apache2" \
				"WEB_RGROUPS=www-data" \
				"WEB_PORT=80" \
				"WEB_SSL_PORT=443" \
				"WEB_SSL=mod_ssl" \
				"PROXY_SYSTEM="
			;;
		both | BOTH)
			printf '%s\n' \
				"WEB_SYSTEM=apache2" \
				"WEB_RGROUPS=www-data" \
				"WEB_PORT=8080" \
				"WEB_SSL_PORT=8443" \
				"WEB_SSL=mod_ssl" \
				"PROXY_SYSTEM=nginx" \
				"PROXY_PORT=80" \
				"PROXY_SSL_PORT=443"
			;;
		mailfront | MAILFRONT)
			# Empty WEB_SYSTEM makes every web command refuse; the ports stay for the webmail templates.
			printf '%s\n' \
				"WEB_SYSTEM=" \
				"WEB_PORT=80" \
				"WEB_SSL_PORT=443" \
				"WEB_SSL=openssl" \
				"PROXY_SYSTEM=" \
				"WEBMAIL_FRONT=nginx"
			;;
		nginx | NGINX | *)
			printf '%s\n' \
				"WEB_SYSTEM=nginx" \
				"WEB_PORT=80" \
				"WEB_SSL_PORT=443" \
				"WEB_SSL=openssl" \
				"PROXY_SYSTEM="
			;;
	esac
}

# hestia_apt at install time, plain apt-get from the live switch, which has no $LOG.
_web_apt_install() {
	if declare -F hestia_apt > /dev/null 2>&1 && [ -n "${LOG:-}" ]; then
		hestia_apt -y install "$@"
	else
		DEBIAN_FRONTEND=noninteractive apt-get -y \
			-o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold install "$@"
	fi
}
_web_apt_purge() {
	if declare -F hestia_apt > /dev/null 2>&1 && [ -n "${LOG:-}" ]; then
		hestia_apt -y purge "$@"
	else
		DEBIAN_FRONTEND=noninteractive apt-get -y purge "$@"
	fi
}

# ports.conf stays empty: Listen comes per IP, and the hestia-status listener keeps apache startable until then.
configure_apache2() {
	echo "[ * ] Installing apache2..."
	_web_apt_install apache2 apache2-suexec-custom libapache2-mod-fcgid

	echo "[ * ] Configuring apache2..."
	mkdir -p /etc/apache2/conf.d/domains
	cp -f "$HESTIA/share/apache2/apache2.conf" /etc/apache2/
	cp -f "$HESTIA/share/apache2/status.conf" /etc/apache2/mods-available/hestia-status.conf
	cp -f /etc/apache2/mods-available/status.load /etc/apache2/mods-available/hestia-status.load
	cp -f "$HESTIA/share/apache2/logrotate" /etc/logrotate.d/apache2

	local m failed=''
	for m in rewrite suexec ssl actions headers; do
		a2enmod -q "$m" > /dev/null 2>&1 || failed="$failed $m"
	done
	a2dismod -q status > /dev/null 2>&1 || true
	a2enmod -q hestia-status > /dev/null 2>&1 || failed="$failed hestia-status"

	a2dismod -q mpm_prefork > /dev/null 2>&1 || true
	# proxy_http: the webmail vhosts proxy to Caddy.
	for m in mpm_event proxy_fcgi setenvif proxy_http; do
		a2enmod -q "$m" > /dev/null 2>&1 || failed="$failed $m"
	done
	cp -f "$HESTIA/share/apache2/hestia-event.conf" /etc/apache2/conf.d/

	a2dissite -q 000-default > /dev/null 2>&1 || true
	echo "# Powered by hestia" > /etc/apache2/ports.conf

	echo -e "/home\npublic_html/cgi-bin" > /etc/apache2/suexec/www-data
	touch /var/log/apache2/access.log /var/log/apache2/error.log
	mkdir -p /var/log/apache2/domains
	chmod a+x /var/log/apache2
	chmod 640 /var/log/apache2/access.log /var/log/apache2/error.log
	chmod 751 /var/log/apache2/domains

	update-rc.d apache2 defaults > /dev/null 2>&1
	if [ -n "$failed" ]; then
		echo "Error: apache2 modules not enabled:$failed" >&2
		return 1
	fi
	# restart, not start: the package already started it on the distro config
	systemctl restart apache2
}

# apache_remoteip_enable [IP...]
# Only in "both": apache trusts X-Real-IP from nginx. The caller restarts.
apache_remoteip_enable() {
	local ip seen=""
	{
		echo "<IfModule mod_remoteip.c>"
		echo "  RemoteIPHeader X-Real-IP"
		echo "  RemoteIPInternalProxy 127.0.0.1"
		for ip in "$@"; do
			[ -n "$ip" ] || continue
			case " $seen " in *" $ip "*) continue ;; esac
			seen="$seen $ip"
			echo "  RemoteIPInternalProxy $ip"
		done
		echo "</IfModule>"
	} > /etc/apache2/mods-available/remoteip.conf
	sed -i 's/LogFormat "%h/LogFormat "%a/g' /etc/apache2/apache2.conf
	a2enmod -q remoteip > /dev/null 2>&1 || true
}

# Without nginx in front a trusted X-Real-IP lets any client spoof its address. The caller restarts.
apache_remoteip_disable() {
	a2dismod -q remoteip > /dev/null 2>&1 || true
	rm -f /etc/apache2/mods-available/remoteip.conf
	sed -i 's/LogFormat "%a/LogFormat "%h/g' /etc/apache2/apache2.conf
}

# rebuild_ip_web_config IP
# The per-IP catch-all configs for the current model. Needs process_http2_directive from include/domain.sh.
rebuild_ip_web_config() {
	local ip="$1" web_sys="$WEB_SYSTEM" family addr
	family=$(ip_family "$ip")
	if [ "$family" = 6 ]; then
		addr="[$ip]"
	else
		addr="$ip"
	fi
	# mailfront: the front still owns 80/443 for webmail and ACME.
	[ -z "$web_sys" ] && web_sys="${WEBMAIL_FRONT:-}"
	if [ -n "$web_sys" ]; then
		local web_conf="/etc/$web_sys/conf.d/$ip.conf"
		rm -f "$web_conf"

		if [ "$web_sys" = 'httpd' ] || [ "$web_sys" = 'apache2' ]; then
			if ! /usr/sbin/apachectl -v 2> /dev/null | grep -q "Apache/2.4"; then
				echo "NameVirtualHost $addr:$WEB_PORT" > "$web_conf"
			fi
			echo "Listen $addr:$WEB_PORT" >> "$web_conf"
			cat "$HESTIA/share/apache2/unassigned.conf" >> "$web_conf"
			# ServerName takes no bracketed v6 literal.
			if [ "$family" = 6 ]; then
				sed -i "s/directNAME/${ip//:/-}.invalid/g" "$web_conf"
			else
				sed -i "s/directNAME/$ip/g" "$web_conf"
			fi
			sed -i "s/directIP/$addr/g" "$web_conf"
			sed -i "s/directPORT/$WEB_PORT/g" "$web_conf"

		elif [ "$web_sys" = 'nginx' ]; then
			cp -f "$HESTIA/share/nginx/unassigned.inc" "$web_conf"
			sed -i "s/directIP/$addr/g" "$web_conf"
			process_http2_directive "$web_conf"
		fi

		if [ "$WEB_SSL" = 'mod_ssl' ]; then
			if ! /usr/sbin/apachectl -v 2> /dev/null | grep -q "Apache/2.4"; then
				sed -i "1s/^/NameVirtualHost $addr:$WEB_SSL_PORT\n/" "$web_conf"
			fi
			sed -i "1s/^/Listen $addr:$WEB_SSL_PORT\n/" "$web_conf"
			sed -i "s/directSSLPORT/$WEB_SSL_PORT/g" "$web_conf"
		fi
	fi

	if [ -n "$PROXY_SYSTEM" ]; then
		# Family-split, so the template keeps only this address's listen line.
		local _r_ip='' _r_ip6='' _r_domain='' _r_domain_idn='' _r_root_domain='' \
			_r_alias='' _r_alias_idn='' _r_web_system='' _r_vhost='' _r_vhost_ssl='' \
			_r_backend_addr="$addr"
		if [ "$family" = 6 ]; then
			_r_ip6="$ip"
		else
			_r_ip="$ip"
		fi
		web_render_template < "$SHARETPL/$PROXY_SYSTEM/proxy_ip.tpl" > "/etc/$PROXY_SYSTEM/conf.d/$ip.conf"

		process_http2_directive "/etc/$PROXY_SYSTEM/conf.d/$ip.conf"
	fi
}

# Mail-only: the default server takes the hostname's ACME location. The directory is the done-marker, since the
# per-IP files cannot be named in a condition; h-add-letsencrypt-domain removes only the tokens inside it.
acme_host_location_apply() {
	# A subshell: the sourced files and hestia.conf stay out of the update run.
	(
		# shellcheck source=/usr/local/hestia/include/domain.sh
		source "$HESTIA/include/domain.sh" || exit 1
		source_conf "$HESTIA/conf/hestia.conf"
		[ -z "$WEB_SYSTEM" ] && [ "${WEBMAIL_FRONT:-}" = 'nginx' ] || exit 0
		bak=$(mktemp -d) || exit 1
		trap 'rm -rf "$bak"' EXIT
		mapfile -t ips < <(web_sys_ips)
		for ip in "${ips[@]}"; do
			[ -f "/etc/nginx/conf.d/$ip.conf" ] && cp -p "/etc/nginx/conf.d/$ip.conf" "$bak/"
		done
		ok=yes
		for ip in "${ips[@]}"; do
			[ -n "$ip" ] || continue
			rebuild_ip_web_config "$ip" || ok=no
		done
		[ "$ok" = yes ] && nginx -t > /dev/null 2>&1 || ok=no
		# Put back, as the model switch does: the running nginx still has the old files, the next reload would not.
		if [ "$ok" = no ]; then
			for ip in "${ips[@]}"; do
				[ -n "$ip" ] || continue
				if [ -f "$bak/$ip.conf" ]; then
					cp -p "$bak/$ip.conf" /etc/nginx/conf.d/
				else
					rm -f "/etc/nginx/conf.d/$ip.conf"
				fi
			done
			exit 1
		fi
		mkdir -p "$ACME_APACHE_DIR/host" && chmod 755 "$ACME_APACHE_DIR" "$ACME_APACHE_DIR/host" || exit 1
		systemctl -q is-active nginx 2> /dev/null || exit 0
		systemctl reload nginx
	)
}

WEB_MODEL_SNAP_DIR="/var/lib/hestia/web-model-switch"

# From the keyset, not dpkg: a stopped but installed server must not mask the model.
web_current_model() {
	if [ "$WEB_SYSTEM" = "nginx" ]; then
		echo "nginx"
	elif [ "$WEB_SYSTEM" = "apache2" ] && [ "$PROXY_SYSTEM" = "nginx" ]; then
		echo "both"
	elif [ "$WEB_SYSTEM" = "apache2" ]; then
		echo "apache"
	elif [ -z "$WEB_SYSTEM" ] && [ -n "${WEBMAIL_FRONT:-}" ]; then
		echo "mailfront"
	else
		echo "unknown"
	fi
}

web_model_uses_apache() { case "$1" in both | apache) return 0 ;; *) return 1 ;; esac }
web_model_uses_nginx() { case "$1" in both | nginx | mailfront) return 0 ;; *) return 1 ;; esac }

web_model_label() {
	case "$1" in
		nginx) echo "nginx-only" ;;
		both) echo "both - nginx front, apache2 backend" ;;
		apache) echo "apache-only" ;;
		mailfront) echo "mailfront - nginx for webmail/ACME only, no customer web" ;;
		*) echo "$1" ;;
	esac
}

# The 9 keys the model owns; flip = change each emitted key, clear the rest.
WEB_MODEL_KEYS="WEB_SYSTEM WEB_RGROUPS WEB_PORT WEB_SSL_PORT WEB_SSL PROXY_SYSTEM PROXY_PORT PROXY_SSL_PORT WEBMAIL_FRONT"

web_model_flip_keyset() {
	local target="$1" kv key emitted=""
	while IFS= read -r kv; do
		key="${kv%%=*}"
		change_sys_value "$key" "${kv#*=}"
		emitted="$emitted $key"
	done < <(web_model_keyset "$target")
	local k
	for k in $WEB_MODEL_KEYS; do
		case " $emitted " in *" $k "*) ;; *) clear_sys_value "$k" ;; esac
	done
}

web_model_sentinel() { echo "$WEB_MODEL_SNAP_DIR/IN_PROGRESS"; }

web_model_sentinel_check() {
	local s op
	s="$(web_model_sentinel)"
	[ -f "$s" ] || return 0
	op=$(sed -n 's/^op=//p' "$s")
	echo "Error: a previous web-model switch did not finish:" >&2
	sed 's/^/       /' "$s" >&2
	echo "       Recover from its snapshot with:  ${op:-h-<that-command>} --recover" >&2
	return 1
}

# The snapshots hold every customer's TLS and DKIM keys.
web_model_snap_dir_apply() {
	[ -d "$WEB_MODEL_SNAP_DIR" ] || return 0
	chmod 700 "$WEB_MODEL_SNAP_DIR"
}

web_model_snapshot() {
	local snap="$1" u
	mkdir -p "$WEB_MODEL_SNAP_DIR" && chmod 700 "$WEB_MODEL_SNAP_DIR" && mkdir -p "$snap" || return 1
	cp -a "$HESTIA/conf/hestia.conf" "$snap/hestia.conf"
	local -a paths=()
	[ -d /etc/nginx/conf.d ] && paths+=("etc/nginx/conf.d")
	[ -d /etc/apache2/conf.d ] && paths+=("etc/apache2/conf.d")
	# configure_apache2 rewrites these and flips modules.
	[ -f /etc/apache2/apache2.conf ] && paths+=("etc/apache2/apache2.conf")
	[ -d /etc/apache2/mods-available ] && paths+=("etc/apache2/mods-available")
	[ -d /etc/apache2/mods-enabled ] && paths+=("etc/apache2/mods-enabled")
	[ -f /etc/apache2/ports.conf ] && paths+=("etc/apache2/ports.conf")
	[ -d /etc/apache2/suexec ] && paths+=("etc/apache2/suexec")
	[ -f /etc/logrotate.d/apache2 ] && paths+=("etc/logrotate.d/apache2")
	[ -f /etc/logrotate.d/nginx ] && paths+=("etc/logrotate.d/nginx")
	while IFS= read -r u; do
		[ -n "$u" ] || continue
		[ -d "$HOMEDIR/$u/conf/web" ] && paths+=("home/$u/conf/web")
		[ -d "$HOMEDIR/$u/conf/mail" ] && paths+=("home/$u/conf/mail")
	done < <(web_users)
	# The snapshot is the rollback: an empty archive would surface only when it is needed.
	if ! (umask 077 && tar czf "$snap/state.tar.gz" -C / "${paths[@]}" 2> /dev/null); then
		echo "Error: snapshot tar failed (disk full? permissions? a path vanished mid-run?)." >&2
		return 1
	fi
	if [ ! -s "$snap/state.tar.gz" ] || ! tar tzf "$snap/state.tar.gz" > /dev/null 2>&1; then
		echo "Error: snapshot archive is empty or unreadable; refusing to switch." >&2
		return 1
	fi
	printf '%s\n' "${paths[@]}" > "$snap/paths.list"
}

web_model_rollback() {
	local snap="$1" restored pfx u dir ip
	[ -d "$snap" ] || return 1
	cp -a "$snap/hestia.conf" "$HESTIA/conf/hestia.conf"
	tar xzf "$snap/state.tar.gz" -C / 2> /dev/null || true
	source_conf "$HESTIA/conf/hestia.conf"
	restored="$(web_current_model)"
	# Re-asserted, not restored: tar does not delete, so a failed apache-only to both would keep trusting X-Real-IP.
	if [ "$restored" = "both" ]; then
		local rips
		rips=$(web_sys_proxy_ips | tr '\n' ' ')
		# shellcheck disable=SC2086
		apache_remoteip_enable $rips
	elif [ -d /etc/apache2/mods-available ]; then
		apache_remoteip_disable
	fi
	# tar does not delete what the failed target already wrote.
	local keep=" $WEB_SYSTEM $PROXY_SYSTEM "
	for pfx in nginx apache2; do
		case "$keep" in *" $pfx "*) continue ;; esac
		while IFS=$'\t' read -r u dir; do
			rm -f "$dir/$pfx.conf" "$dir/$pfx.ssl.conf"
		done < <(web_domain_dirs)
		while IFS=$'\t' read -r u dir; do
			rm -f "$dir/$pfx.conf" "$dir/$pfx.ssl.conf"
		done < <(web_mail_dirs)
		rm -f "/etc/$pfx/conf.d/domains/"*.conf 2> /dev/null
		while IFS= read -r ip; do
			[ -n "$ip" ] || continue
			rm -f "/etc/$pfx/conf.d/$ip.conf"
		done < <(web_sys_ips)
		# Written for the target before validation; a server installed later would load it.
		rm -f "/etc/$pfx/conf.d/phpmyadmin.inc"
	done
	# Both directions: in both the limit sits on nginx, so a failed both to apache-only leaves it on a kept apache.
	web_model_botlimit_sync "$restored"
	# The failed target's listeners stay behind otherwise, secret included, and would claim the port from its tree.
	# shellcheck source=/usr/local/hestia/include/filemanager.sh
	source "$HESTIA/include/filemanager.sh"
	fm_refresh no || echo "Warning: file manager listeners not restored" >&2
	# reload-or-restart, never a hard restart: a server still serving its loaded config must stay up when the config on
	# disk does not load.
	if web_model_uses_apache "$restored"; then
		systemctl enable apache2 > /dev/null 2>&1
		systemctl reload-or-restart apache2 > /dev/null 2>&1
	else
		systemctl disable --now apache2 > /dev/null 2>&1 || true
	fi
	if web_model_uses_nginx "$restored"; then
		systemctl enable nginx > /dev/null 2>&1
		systemctl reload-or-restart nginx > /dev/null 2>&1
	else
		systemctl disable --now nginx > /dev/null 2>&1 || true
	fi
}

# The bot rate-limit server config belongs to the public front, and the per-domain fragments rebuilt after it name
# its zones. The departing front's copy goes; a rollback restores it from the snapshot.
web_model_botlimit_sync() {
	# shellcheck source=/usr/local/hestia/include/botpolicy.sh
	source "$HESTIA/include/botpolicy.sh"
	botpolicy_seed_families
	if [ "$1" = 'apache' ]; then
		rm -f /etc/nginx/conf.d/hestia_botlimit.conf
		if _web_apt_install libapache2-mod-qos > /dev/null 2>&1 && a2enmod -q qos > /dev/null 2>&1; then
			botpolicy_render_apache
		else
			echo "Warning: mod_qos is not available, bot rate limiting stays off" >&2
		fi
	else
		rm -f /etc/apache2/conf.d/hestia_botlimit.conf
		web_model_uses_nginx "$1" && [ -d /etc/nginx/conf.d ] && botpolicy_render_nginx
	fi
	return 0
}

web_sys_ips() { "$BIN/h-list-sys-ips" plain 2> /dev/null | cut -f1; }
web_users() { "$BIN/h-list-users" list 2> /dev/null; }
# Each sys IP and its NAT address, the installer's trusted-proxy set.
web_sys_proxy_ips() {
	"$BIN/h-list-sys-ips" plain 2> /dev/null | awk -F'\t' '{print $1; if ($9 != "" && $9 != $1) print $9}'
}
# USER<TAB>DIR for every web domain conf dir.
web_domain_dirs() {
	local u dir
	while IFS= read -r u; do
		[ -n "$u" ] || continue
		for dir in "$HOMEDIR/$u/conf/web/"*/; do
			[ -d "$dir" ] || continue
			printf '%s\t%s\n' "$u" "${dir%/}"
		done
	done < <(web_users)
}

# Same for the mail conf dirs: the webmail vhosts live there, and the web rebuild never touches them.
web_mail_dirs() {
	local u dir
	while IFS= read -r u; do
		[ -n "$u" ] || continue
		for dir in "$HOMEDIR/$u/conf/mail/"*/; do
			[ -d "$dir" ] || continue
			printf '%s\t%s\n' "$u" "${dir%/}"
		done
	done < <(web_users)
}

# web_model_run CURRENT TARGET MODE OPLABEL PURGE
# MODE=yes applies, anything else previews.
web_model_run() {
	local current="$1" target="$2" mode="$3" oplabel="$4" purge="$5"

	if [ "$current" = "$target" ]; then
		echo "Already $(web_model_label "$current"); nothing to do."
		return 0
	fi

	echo "Web model: $(web_model_label "$current")  ->  $(web_model_label "$target")"
	web_model_uses_apache "$target" && ! web_model_uses_apache "$current" \
		&& echo "  - apache2 will be installed/configured"
	web_model_uses_apache "$current" && ! web_model_uses_apache "$target" \
		&& { [ "$purge" = "yes" ] && echo "  - apache2 will be PURGED (/etc/apache2 incl. custom includes + fm--listen.conf)" || echo "  - apache2 will be stopped+disabled (package kept)"; }
	web_model_uses_apache "$current" && web_model_uses_apache "$target" \
		&& echo "  - apache2.conf + module config are rewritten from share/ (existing customizations are snapshotted, not merged)"
	web_model_uses_nginx "$current" && ! web_model_uses_nginx "$target" \
		&& { [ "$purge" = "yes" ] && echo "  - nginx will be PURGED (/etc/nginx incl. custom includes)" || echo "  - nginx will be stopped+disabled (package kept)"; }
	[ "$target" = "both" ] && echo "  - mod_remoteip enabled (apache trusts nginx X-Real-IP)"
	web_model_uses_apache "$current" && [ "$target" != "both" ] \
		&& echo "  - mod_remoteip disabled"
	echo "  - every web + webmail vhost is regenerated; brief downtime; domain ops are frozen during the switch"

	if [ "$mode" != "yes" ]; then
		echo "Preview only. Re-run with 'yes' to apply."
		return 0
	fi

	web_lock_acquire 300 || return 1

	# A switch we waited behind may have moved the model, and the target was derived before the lock.
	local now
	source_conf "$HESTIA/conf/hestia.conf"
	now=$(web_current_model)
	if [ "$now" != "$current" ]; then
		echo "State changed while waiting for the lock (now $(web_model_label "$now")). Re-run the command." >&2
		web_lock_release
		return 0
	fi

	local snap OLD_WEB OLD_PROXY
	snap="$WEB_MODEL_SNAP_DIR/$(date +%Y%m%d-%H%M%S)-$$"
	OLD_WEB="$WEB_SYSTEM"
	OLD_PROXY="$PROXY_SYSTEM"

	_wm_fail() {
		echo "ERROR: $1 - rolling back to $(web_model_label "$current")..." >&2
		web_model_rollback "$snap"
		rm -f "$(web_model_sentinel)"
		web_lock_release
		echo "       Rolled back (site kept serving the old config). Snapshot: $snap" >&2
		echo "       Manual restore if needed: cp -a $snap/hestia.conf $HESTIA/conf/hestia.conf; tar xzf $snap/state.tar.gz -C /; systemctl restart nginx apache2" >&2
		"$BIN/h-log-action" "system" "Error" "System" "Web model switch to $(web_model_label "$target") failed ($1), rolled back." > /dev/null 2>&1
		log_event "$E_RESTART" "$oplabel $current -> $target: $1"
	}

	echo "[ * ] Snapshotting current state..."
	web_model_snapshot "$snap" || {
		web_lock_release
		return 1
	}
	mkdir -p "$(dirname "$(web_model_sentinel)")"
	printf 'op=%s\nfrom=%s\nto=%s\nsnapshot=%s\nstarted=%s\n' \
		"$oplabel" "$current" "$target" "$snap" "$(date '+%F %T')" > "$(web_model_sentinel)"

	if web_model_uses_apache "$target"; then
		echo "[ * ] Setting up apache2..."
		if ! configure_apache2 || ! command -v apache2ctl > /dev/null 2>&1; then
			_wm_fail "apache2 setup failed"
			return 1
		fi
	fi

	# The target's log dir starts clean; the old logs stay beside it.
	if [ -f "/etc/logrotate.d/$OLD_WEB" ]; then
		logrotate -f "/etc/logrotate.d/$OLD_WEB" > /dev/null 2>&1 || true
	fi

	echo "[ * ] Flipping web model keyset..."
	web_model_flip_keyset "$target"
	source_conf "$HESTIA/conf/hestia.conf"

	if [ "$target" = "both" ]; then
		local ips
		ips=$(web_sys_proxy_ips | tr '\n' ' ')
		# shellcheck disable=SC2086
		apache_remoteip_enable $ips
	elif [ -d /etc/apache2/mods-available ]; then
		apache_remoteip_disable
	fi

	# The customer vhosts include it optionally, so a server that arrives without it silently loses the route.
	web_model_uses_nginx "$target" && pma_proxy_include_write nginx
	web_model_uses_apache "$target" && pma_proxy_include_write apache2

	web_model_botlimit_sync "$target"

	echo "[ * ] Rebuilding per-IP + per-domain + webmail configs for $target..."
	local ip u
	while IFS= read -r ip; do
		[ -n "$ip" ] || continue
		rebuild_ip_web_config "$ip"
	done < <(web_sys_ips)
	# Nothing downstream checks the mail rebuild.
	while IFS= read -r u; do
		[ -n "$u" ] || continue
		"$BIN/h-rebuild-web-domains" "$u" no > /dev/null 2>&1 \
			|| {
				_wm_fail "web rebuild failed for $u"
				return 1
			}
		if [ -n "$MAIL_SYSTEM" ]; then
			"$BIN/h-rebuild-mail-domains" "$u" > /dev/null 2>&1 \
				|| {
					_wm_fail "mail/webmail rebuild failed for $u"
					return 1
				}
		fi
	done < <(web_users)
	# shellcheck source=/usr/local/hestia/include/filemanager.sh
	source "$HESTIA/include/filemanager.sh"
	fm_refresh no || echo "Warning: file manager listeners not rebuilt for the new model" >&2

	echo "[ * ] Validating..."
	if web_model_uses_nginx "$target"; then
		nginx -t > /dev/null 2>&1 || {
			_wm_fail "nginx configtest failed"
			return 1
		}
	fi
	if web_model_uses_apache "$target"; then
		apache2ctl configtest > /dev/null 2>&1 || {
			_wm_fail "apache2 configtest failed"
			return 1
		}
	fi
	# The departing files are still here; cleanup waits for a proven restart.
	web_model_inventory_assert "$target" "$OLD_WEB" "$OLD_PROXY" || {
		_wm_fail "inventory assertion failed (mixed tree)"
		return 1
	}

	echo "[ * ] Restarting web services..."
	# The departing server stops first, so the port it frees can be taken over.
	if ! web_model_uses_apache "$target"; then
		# Only stopped here: the rollback can bring back a stopped apache, not a purged one.
		if [ "$purge" = "yes" ]; then
			systemctl stop apache2 > /dev/null 2>&1 || true
		else
			systemctl disable --now apache2 > /dev/null 2>&1 || true
		fi
	fi
	if ! web_model_uses_nginx "$target"; then
		systemctl disable --now nginx > /dev/null 2>&1 || true
	fi

	# An arriving server may have been disabled by an earlier switch.
	if web_model_uses_apache "$target"; then systemctl enable apache2 > /dev/null 2>&1 || true; fi
	if web_model_uses_nginx "$target"; then systemctl enable nginx > /dev/null 2>&1 || true; fi
	"$BIN/h-restart-web" > /dev/null 2>&1
	"$BIN/h-restart-proxy" > /dev/null 2>&1
	"$BIN/h-restart-web-backend" > /dev/null 2>&1

	# A green configtest is not a started server.
	web_model_verify_up "$target" || {
		_wm_fail "target server(s) did not come up after restart"
		return 1
	}

	# The running jails follow the rebuild, but their persisted logpath would lose the new logs at the next restart.
	if [ "${FIREWALL_EXTENSION:-}" = 'fail2ban' ] && [ -f "$HESTIA/include/fail2ban.sh" ]; then
		# shellcheck source=/usr/local/hestia/include/fail2ban.sh
		source "$HESTIA/include/fail2ban.sh"
		fail2ban_gate_web_jail
		systemctl reload-or-restart fail2ban > /dev/null 2>&1
	fi

	if [ "$purge" = "yes" ] && ! web_model_uses_apache "$target"; then
		_web_apt_purge apache2 apache2-suexec-custom libapache2-mod-fcgid > /dev/null 2>&1 || true
		rm -f /etc/logrotate.d/apache2
	fi
	if [ "$purge" = "yes" ] && ! web_model_uses_nginx "$target"; then
		_web_apt_purge nginx > /dev/null 2>&1 || true
		rm -f /etc/logrotate.d/nginx
	fi

	echo "[ * ] Cleaning old-model artifacts..."
	web_model_cleanup "$OLD_WEB" "$OLD_PROXY" "$target"

	rm -f "$(web_model_sentinel)"
	web_lock_release
	"$BIN/h-log-action" "system" "Info" "System" "Web model switched to $(web_model_label "$target")." > /dev/null 2>&1
	log_event "$OK" "$oplabel $current -> $target"
	echo "[ ok ] Web model is now $(web_model_label "$target"). Snapshot kept at: $snap"
	return 0
}
# Daemons active and the ports held. Without ss only the daemons are checked, never a false rollback.
web_model_verify_up() {
	local target="$1" waited=0 deadline=10 p held bad
	# Polled: the bind can lag the restart, and a single shot would roll back a good switch.
	while :; do
		bad=""
		if web_model_uses_apache "$target"; then
			systemctl is-active --quiet apache2 || bad="$bad apache2:inactive"
		fi
		if web_model_uses_nginx "$target"; then
			systemctl is-active --quiet nginx || bad="$bad nginx:inactive"
		fi
		if command -v ss > /dev/null 2>&1; then
			held=$(ss -H -tln 2> /dev/null | awk '{print $4}')
			for p in 80 443; do
				grep -qE "[:.]$p\$" <<< "$held" || bad="$bad port-$p:down"
			done
			# nginx holding 80/443 does not prove apache listens behind it.
			if [ "$target" = "both" ]; then
				for p in 8080 8443; do
					grep -qE "[:.]$p\$" <<< "$held" || bad="$bad backend-$p:down"
				done
			fi
		fi
		if [ -n "$WEB_BACKEND" ]; then
			local v
			while IFS= read -r v; do
				[ -n "$v" ] || continue
				systemctl is-active --quiet "php$v-fpm" || bad="$bad php$v-fpm:inactive"
			done < <("$BIN/h-list-sys-php" plain 2> /dev/null)
		fi
		[ -z "$bad" ] && return 0
		[ "$waited" -ge "$deadline" ] && break
		sleep 1
		waited=$((waited + 1))
	done
	echo "  target not serving after ${deadline}s:$bad" >&2
	return 1
}

# A mixed tree passes every configtest.
web_model_inventory_assert() {
	local target="$1" old_web="$2" old_proxy="$3" u d dir pfx bad=""
	local keep=" $WEB_SYSTEM $PROXY_SYSTEM "
	local tolerate=" $old_web $old_proxy "
	while IFS=$'\t' read -r u dir; do
		d=$(basename "$dir")
		[ -f "$dir/$WEB_SYSTEM.conf" ] || bad="$bad $u/$d:missing-$WEB_SYSTEM.conf"
		for pfx in nginx apache2; do
			case "$keep" in *" $pfx "*) continue ;; esac
			case "$tolerate" in *" $pfx "*) continue ;; esac
			{ [ -f "$dir/$pfx.conf" ] || [ -f "$dir/$pfx.ssl.conf" ]; } && bad="$bad $u/$d:foreign-$pfx"
		done
	done < <(web_domain_dirs)
	[ -z "$bad" ] && return 0
	echo "  mixed-tree files:$bad" >&2
	return 1
}

web_model_cleanup() {
	local old_web="$1" old_proxy="$2" target="$3" u dir ip pfx
	local keep=" $WEB_SYSTEM $PROXY_SYSTEM "
	for pfx in "$old_web" "$old_proxy"; do
		[ -n "$pfx" ] || continue
		case "$keep" in *" $pfx "*) continue ;; esac
		while IFS=$'\t' read -r u dir; do
			rm -f "$dir/$pfx.conf" "$dir/$pfx.ssl.conf"
		done < <(web_domain_dirs)
		while IFS=$'\t' read -r u dir; do
			rm -f "$dir/$pfx.conf" "$dir/$pfx.ssl.conf"
		done < <(web_mail_dirs)
		rm -f "/etc/$pfx/conf.d/domains/"*.conf 2> /dev/null
		while IFS= read -r ip; do
			[ -n "$ip" ] || continue
			rm -f "/etc/$pfx/conf.d/$ip.conf"
		done < <(web_sys_ips)
	done
}

web_model_recover() {
	local s
	s="$(web_model_sentinel)"
	if [ ! -f "$s" ]; then
		echo "No interrupted web-model switch to recover."
		return 0
	fi
	local snap
	snap=$(sed -n 's/^snapshot=//p' "$s")
	echo "Recovering from interrupted switch:"
	sed 's/^/  /' "$s"
	[ -d "$snap" ] || {
		echo "Error: snapshot dir $snap missing; recover by hand." >&2
		return 1
	}
	web_lock_acquire 300 || return 1
	web_model_rollback "$snap"
	rm -f "$s"
	web_lock_release
	echo "[ ok ] Restored to the pre-switch model from $snap."
}

# web_component_op add|delete nginx|apache2 MODE PURGE FORCE
web_component_op() {
	local action="$1" comp="$2" mode="$3" purge="$4" force="$5"
	local current
	current=$(web_current_model)
	[ "$current" = "unknown" ] && {
		echo "Refused: no web stack is configured (empty WEB_SYSTEM); this is not the axis a web-model command changes." >&2
		return 1
	}
	[ "$current" = "mailfront" ] && {
		echo "Refused: this box is mailfront (webmail/ACME nginx only, no customer web, #193) - the model switch does not add a customer web stack to a mailonly box." >&2
		return 1
	}

	local has_nginx=no has_apache=no
	web_model_uses_nginx "$current" && has_nginx=yes
	web_model_uses_apache "$current" && has_apache=yes
	case "$action:$comp" in
		add:nginx) has_nginx=yes ;;
		add:apache2) has_apache=yes ;;
		delete:nginx) has_nginx=no ;;
		delete:apache2) has_apache=no ;;
	esac

	if [ "$has_nginx" = no ] && [ "$has_apache" = no ]; then
		echo "Refused: that would remove the last web server. A host must serve web with nginx and/or apache2 (not --force-able)." >&2
		return 1
	fi

	local target
	if [ "$has_nginx" = yes ] && [ "$has_apache" = yes ]; then
		target="both"
	elif [ "$has_nginx" = yes ]; then
		target="nginx"
	else
		target="apache"
	fi

	# Findings are the point of a preview; only an apply aborts on them, and a preview never logs an override.
	if [ "$current" != "$target" ]; then
		if [ "$mode" = "yes" ]; then
			"web_precheck_${action}_${comp}" "$current" "$target" "$force" || return 1
		else
			"web_precheck_${action}_${comp}" "$current" "$target" "no" || true
		fi
	fi

	web_model_run "$current" "$target" "$mode" "h-$action-sys-$comp" "$purge"
}

# Only public_html, deliberately: the rest is never served, and the scan is advisory, not a gate.
_web_htaccess_files() {
	local u
	echo "  scanning customer .htaccess under public_html..." >&2
	while IFS= read -r u; do
		[ -n "$u" ] || continue
		# Regular files only: the root grep on a customer FIFO named .htaccess would block the switch forever.
		find "$HOMEDIR/$u/web/"*/public_html -name .htaccess -type f 2> /dev/null
	done < <(web_users)
}

# nginx-only to both: the backend ports must be free, not force-able.
web_precheck_add_apache2() {
	command -v ss > /dev/null 2>&1 \
		|| {
			echo "Refused: 'ss' (iproute2) unavailable - cannot verify the apache backend ports are free (not --force-able)." >&2
			return 1
		}
	local p occupied=""
	for p in 8080 8443; do
		ss -H -tln 2> /dev/null | awk '{print $4}' | grep -qE "[:.]$p\$" && occupied="$occupied $p"
	done
	if [ -n "$occupied" ]; then
		echo "Refused: apache backend port(s) already in use:$occupied (free them first)." >&2
		return 1
	fi
	return 0
}

# apache-only to both: nginx serves static assets itself, so their .htaccess rules go inert. Never blocks.
web_precheck_add_nginx() {
	local f
	local -a hits=()
	while IFS= read -r f; do
		[ -n "$f" ] || continue
		grep -qiE 'Header|ExpiresBy|deny from|Rewrite' "$f" 2> /dev/null && hits+=("${f#"$HOMEDIR/"}")
	done < <(_web_htaccess_files)
	if [ ${#hits[@]} -gt 0 ]; then
		echo "Note: in 'both', nginx serves static assets directly - .htaccess asset rules go inert in:" >&2
		local h
		for h in "${hits[@]}"; do echo "        $h" >&2; done
		echo "      (routing change, not a capability loss)" >&2
	fi
	return 0
}

# both to nginx-only: refuses on any apache-only feature unless --force, which logs what it overrode.
web_precheck_delete_apache2() {
	local force="$3" f u d dir
	local -a findings=()
	while IFS= read -r f; do
		[ -n "$f" ] || continue
		grep -qiE 'RewriteRule|php_value|php_flag|AuthType|Require |Options |ErrorDocument|Header ' "$f" 2> /dev/null \
			&& findings+=("htaccess:${f#"$HOMEDIR/"}")
	done < <(_web_htaccess_files)
	while IFS=$'\t' read -r u dir; do
		d=$(basename "$dir")
		ls "$dir"/apache2.conf_* > /dev/null 2>&1 && findings+=("apache-include:$u/$d")
	done < <(web_domain_dirs)
	if [ ${#findings[@]} -gt 0 ]; then
		echo "Removing apache2 would drop apache-only config for:" >&2
		local x
		for x in "${findings[@]}"; do echo "        $x" >&2; done
		if [ "$force" = "yes" ]; then
			echo "  --force: proceeding anyway (recorded in the action log)." >&2
			"$BIN/h-log-action" "${ROOT_USER:-admin}" "Warning" "Web" "h-delete-sys-apache2 --force overrode: ${findings[*]}" > /dev/null 2>&1 || true
			return 0
		fi
		echo "  Refused. Re-run with --force to override (its findings will be logged)." >&2
		return 1
	fi
	return 0
}

# both to apache-only: custom nginx includes and the caches go away.
web_precheck_delete_nginx() {
	local force="$3" u d dir
	local -a findings=()
	while IFS=$'\t' read -r u dir; do
		d=$(basename "$dir")
		ls "$dir"/nginx.conf_* > /dev/null 2>&1 && findings+=("nginx-include:$u/$d")
		# web.conf holds one line per domain, so a ^-anchored key never matches.
		grep -F "DOMAIN='$d'" "$CONF_DIR/users/$u/web.conf" 2> /dev/null | grep -q "FASTCGI_CACHE='yes'" \
			&& findings+=("fastcgi-cache-inert:$u/$d")
		grep -F "DOMAIN='$d'" "$CONF_DIR/users/$u/web.conf" 2> /dev/null | grep -q "PROXY_CACHE='yes'" \
			&& findings+=("proxy-cache-inert:$u/$d")
	done < <(web_domain_dirs)
	echo "Note: removing nginx also disables mod_remoteip (apache serves :80 directly)." >&2
	if [ ${#findings[@]} -gt 0 ]; then
		echo "Removing nginx would drop nginx-only config for:" >&2
		local x
		for x in "${findings[@]}"; do echo "        $x" >&2; done
		if [ "$force" = "yes" ]; then
			echo "  --force: proceeding anyway (recorded in the action log)." >&2
			"$BIN/h-log-action" "${ROOT_USER:-admin}" "Warning" "Web" "h-delete-sys-nginx --force overrode: ${findings[*]}" > /dev/null 2>&1 || true
			return 0
		fi
		echo "  Refused. Re-run with --force to override." >&2
		return 1
	fi
	return 0
}

# web_component_main ACTION COMPONENT "$@": the body of every thin command.
web_component_main() {
	local action="$1" comp="$2"
	shift 2
	local mode="" purge="no" force="no" recover="no" a
	for a in "$@"; do
		case "$a" in
			--purge) purge="yes" ;;
			--force) force="yes" ;;
			--recover) recover="yes" ;;
			yes) mode="yes" ;;
			# A typo must not become a preview someone reads as an apply.
			*)
				echo "Error: unknown argument '$a'. Use: [yes] [--force] [--purge] [--recover]." >&2
				return 1
				;;
		esac
	done

	if [ "$recover" = "yes" ]; then
		web_model_recover
		return $?
	fi
	web_model_sentinel_check || return 1
	web_component_op "$action" "$comp" "$mode" "$purge" "$force"
}
