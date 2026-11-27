#!/bin/bash
# Customer PHP-FPM: one master per customer and version, as the systemd instance hestia-php<ver>-fpm@<user>. Its pools
# live in /etc/php/<ver>/fpm/users/<user>/, its [global] beside them; the version master php<ver>-fpm keeps only the
# dummy pool and anchors restart, reload and boot. Socket paths stay as they were, so no vhost knows about any of this.
# Keep in step with share/php-fpm/hestia-php-fpm@.service and share/php-fpm/php-fpm-user.conf.

CUSTOMER_FPM_UNIT_SRC="$HESTIA/share/php-fpm/hestia-php-fpm@.service"
CUSTOMER_FPM_GLOBAL_SRC="$HESTIA/share/php-fpm/php-fpm-user.conf"

customer_fpm_unit() { echo "hestia-php$1-fpm@$2.service"; }
customer_fpm_dir() { echo "/etc/php/$1/fpm/users/$2"; }

# customer_fpm_unit_write VERSION: the template unit of one version; returns 0 when it changed.
customer_fpm_unit_write() {
	local f="/etc/systemd/system/hestia-php$1-fpm@.service" new
	new=$(sed "s|%version%|$1|g" "$CUSTOMER_FPM_UNIT_SRC") || return 2
	[ -f "$f" ] && [ "$(< "$f")" = "$new" ] && return 1
	printf '%s\n' "$new" > "$f" && chmod 644 "$f"
}

# customer_fpm_users VERSION: every customer with a pool dir or an enabled instance of that version, so an instance
# whose last pool went is found as well.
customer_fpm_users() {
	local d w
	{
		for d in /etc/php/"$1"/fpm/users/*/; do
			[ -e "$d" ] || continue
			d=${d%/}
			echo "${d##*/}"
		done
		for w in /etc/systemd/system/php"$1"-fpm.service.wants/hestia-php"$1"-fpm@*.service; do
			[ -e "$w" ] || [ -L "$w" ] || continue
			w=${w##*@}
			echo "${w%.service}"
		done
	} | sort -u
}

# customer_fpm_versions USER: every version the customer has a pool dir or an enabled instance on.
customer_fpm_versions() {
	local d v w
	{
		for d in /etc/php/*/fpm/users/"$1"/; do
			[ -e "$d" ] || continue
			v=${d#/etc/php/}
			echo "${v%%/*}"
		done
		for w in /etc/systemd/system/php*-fpm.service.wants/hestia-php*-fpm@"$1".service; do
			[ -e "$w" ] || [ -L "$w" ] || continue
			w=${w##*/hestia-php}
			echo "${w%%-fpm@*}"
		done
	} | sort -u
}

# Sockets of the customer's pools that another process still listens on: the instance would refuse its whole start
# over one of them, every site of the customer with it.
customer_fpm_foreign_sockets() {
	local unit="$1" dir="$2" cg procs s pids p held=''
	cg=$(systemctl show -p ControlGroup --value "$unit" 2> /dev/null)
	procs=''
	[ -n "$cg" ] && procs=$(cat "/sys/fs/cgroup$cg/cgroup.procs" 2> /dev/null)
	for s in $(sed -n 's/^[[:space:]]*listen[[:space:]]*=[[:space:]]*\(\/[^[:space:]]*\).*/\1/p' "$dir"/*.conf 2> /dev/null); do
		pids=$(ss -Hxlp 2> /dev/null | awk -v p="$s" '$5 == p' | grep -o 'pid=[0-9]*' | cut -d= -f2 | sort -u)
		for p in $pids; do
			grep -qx "$p" <<< "$procs" || {
				held="$held $s"
				break
			}
		done
	done
	echo "${held# }"
}

# customer_fpm_test USER VERSION: write the customer's [global] for VERSION and test it with all their pools there.
customer_fpm_test() {
	local user="$1" ver="$2" global new out
	global="/etc/php/$ver/fpm/users/$user.conf"
	new=$(sed -e "s|%version%|$ver|g" -e "s|%user%|$user|g" "$CUSTOMER_FPM_GLOBAL_SRC") || return 1
	[ -f "$global" ] && [ "$(< "$global")" = "$new" ] || { printf '%s\n' "$new" > "$global" && chmod 644 "$global"; }
	if ! out=$("/usr/sbin/php-fpm$ver" -t --fpm-config "$global" 2>&1); then
		echo "Error: the PHP $ver master of $user fails its test, left as it runs: $(grep -m1 -iE 'error' <<< "$out")" >&2
		return 1
	fi
}

# customer_fpm_sync USER VERSION [ensure]: bring the customer's master of VERSION in line with their pools on disk.
# With pools: write [global], test, enable, and reload a running master or start it. Without: disable and stop, and the
# [global] goes. 'ensure' leaves a running master alone (a version restart has just restarted it through PartOf).
# A failed test or a socket still held elsewhere leaves the running master as it was and returns 1.
customer_fpm_sync() {
	local user="$1" ver="$2" mode="${3-}" dir unit global held _
	dir=$(customer_fpm_dir "$ver" "$user")
	unit=$(customer_fpm_unit "$ver" "$user")
	global="/etc/php/$ver/fpm/users/$user.conf"
	if ! compgen -G "$dir/*.conf" > /dev/null; then
		systemctl disable --now "$unit" > /dev/null 2>&1
		systemctl reset-failed "$unit" > /dev/null 2>&1
		rm -f "$global"
		rmdir "$dir" 2> /dev/null
		return 0
	fi
	[ -f "/etc/systemd/system/hestia-php$ver-fpm@.service" ] || {
		echo "Error: PHP $ver has no customer master unit, run h-add-web-php $ver" >&2
		return 1
	}
	customer_fpm_test "$user" "$ver" || return 1
	systemctl is-enabled --quiet "$unit" 2> /dev/null || systemctl enable --quiet "$unit" > /dev/null 2>&1
	if systemctl is-active --quiet "$unit"; then
		[ "$mode" = 'ensure' ] && return 0
	fi
	# A socket is free a moment after the master that held it reloaded.
	for _ in $(seq 1 50); do
		held=$(customer_fpm_foreign_sockets "$unit" "$dir")
		[ -z "$held" ] && break
		sleep 0.1
	done
	if [ -n "$held" ]; then
		echo "Error: another PHP-FPM master still listens on $held, the PHP $ver master of $user left as it runs" >&2
		return 1
	fi
	if systemctl is-active --quiet "$unit"; then
		systemctl reload "$unit" > /dev/null 2>&1 || {
			echo "Error: the PHP $ver master of $user did not reload: journalctl -u $unit" >&2
			return 1
		}
	else
		systemctl reset-failed "$unit" > /dev/null 2>&1
		systemctl start "$unit" > /dev/null 2>&1 || {
			echo "Error: the PHP $ver master of $user did not start: journalctl -u $unit" >&2
			return 1
		}
	fi
	return 0
}

# customer_fpm_sync_user USER [VERSION] [ensure]: every version of the customer, or only VERSION; one failure does not
# stop the others.
customer_fpm_sync_user() {
	local v rc=0
	for v in ${2:-$(customer_fpm_versions "$1")}; do
		customer_fpm_sync "$1" "$v" "${3-}" || rc=1
	done
	return "$rc"
}
