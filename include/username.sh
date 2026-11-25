#!/bin/bash
# The rules for a new account name, without dependencies: include/main.sh and the install wizard, which runs before
# hestia.conf exists, read the same ones. Each prints why a name is refused and returns 0, or prints nothing and
# returns 1.

# Names a service account owns, or will own once its component is installed. passwd and group know only today's box,
# so a curated list is the only way to keep a later component's name free.
login_name_reserved_reason() {
	local name="${1,,}" reserved r
	reserved=(
		# ours
		hestia hestia-users procvis sftp-jailed
		# always installed
		nginx apache2 www-data caddy php mysql mariadb phpmyadmin
		# standard profile + optional components (h-add-sys-*, h-add-user-*)
		exim dovecot dovenull rspamd roundcube tachyon crowdsec fail2ban
		docker containerd proftpd ftp clamav postgres postgresql redis opensearch filemanager
		# MariaDB/MySQL database names, and sudo (that group always has sudo rights)
		aria aria_log mysql_upgrade ib ib_buffer ddl ddl_recovery performance sudo
		# with a one-letter database, mys and m_s make a grant that covers mysql while _ is a wildcard; mysq by decision
		mys m_s mysq
		# h-backup-server writes server.*.tar into the same /backup namespace as customer archives
		server
		# the user name the log commands read as the global activity log
		system
	)
	for r in "${reserved[@]}"; do
		if [ "$name" = "$r" ]; then
			echo 'is reserved for system use'
			return 0
		fi
	done
	# h-add-user-docker names the companion account <user>-docker
	if [ "${name%-docker}" != "$name" ]; then
		echo 'is reserved: -docker is the companion suffix'
		return 0
	fi
	return 1
}

# Length and form of a new user name; the reserved names are login_name_reserved_reason's.
new_user_name_refusal() {
	# A database is <user>_<name>: with a one- or two-letter prefix, a wildcard grant reaches far more names.
	if [ ${#1} -lt 3 ]; then
		echo 'is too short: at least 3 characters'
		return 0
	fi
	if ! [[ "$1" =~ ^[a-zA-Z][-_[:alnum:]]{0,28}[[:alnum:]]$ ]] || [ "$1" != "${1//[^[:ascii:]]/}" ]; then
		echo 'is not valid: 3 to 30 characters, a letter first, a letter or digit last, letters, digits, - or _ between'
		return 0
	fi
	return 1
}
