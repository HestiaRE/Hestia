#!/bin/bash
# File manager: which web server carries the private listener, and what it needs besides the per-customer vhosts.
# Keep in step with share/filemanager/{nginx,apache}.tpl.

FM_CODE_SRC="$HESTIA/share/filemanager/fm"
FM_CODE_DIR="/usr/share/filemanager/fm"
FM_SECRET_FILE="/etc/hestia/conf/.filemanager.key"

# fm_render TEMPLATE [USER]: the template with port, secret and user filled in. Builtins only, so the secret
# never shows in a process's argv.
fm_render() {
	local text p_user='%user%' p_port='%FILE_MANAGER_PORT%' p_secret='%FM_SECRET%' secret
	secret=$(< "$FM_SECRET_FILE") || return 1
	text=$(< "$1") || return 1
	text=${text//"$p_user"/"${2-}"}
	text=${text//"$p_port"/"$FILE_MANAGER_PORT"}
	text=${text//"$p_secret"/"$secret"}
	printf '%s\n' "$text"
}

# nginx wherever it fronts the stack, apache2 in apache-only, nothing on mail-only.
fm_front() {
	if [ "$WEB_SYSTEM" = 'nginx' ] || [ "$PROXY_SYSTEM" = 'nginx' ]; then
		echo nginx
	elif [ "$WEB_SYSTEM" = 'apache2' ]; then
		echo apache2
	fi
}

# fm_sock_listened SOCKET: whether a php-fpm master listens on it. A second master refuses to start while one does.
fm_sock_listened() {
	ss -Hxl 2> /dev/null | awk -v p="$1" '$5 == p { f = 1 } END { exit !f }'
}

fm_code_install() {
	mkdir -p "$FM_CODE_DIR" || return 1
	cp -rf "$FM_CODE_SRC/." "$FM_CODE_DIR/" || return 1
	chown -R root:root "$FM_CODE_DIR"
	find "$FM_CODE_DIR" -type d -exec chmod 755 {} +
	find "$FM_CODE_DIR" -type f -exec chmod 644 {} +
}

# The catch-all refuses a Host no customer vhost carries and holds the port from install on, so nothing else can bind
# it. Apache takes the first vhost of a port as its default: the name sorts before every fm-<user>.conf, and no user
# name starts with '-', which also keeps fm--listen.conf apart from them. Root-only like the customer listeners, so the
# update's mode check covers every fm-*.conf.
fm_front_write() {
	local port="${FILE_MANAGER_PORT:-8092}" front
	front=$(fm_front)
	rm -f /etc/nginx/conf.d/fm--default.conf /etc/apache2/conf.d/fm--default.conf /etc/apache2/conf.d/fm--listen.conf
	# The Listen file's old name is also a user's vhost name, so only a file that is just the Listen line goes.
	grep -qx 'Listen 127\.0\.0\.1:[0-9]*' /etc/apache2/conf.d/fm-listen.conf 2> /dev/null \
		&& rm -f /etc/apache2/conf.d/fm-listen.conf
	(
		umask 077
		case "$front" in
			nginx)
				cat > /etc/nginx/conf.d/fm--default.conf <<- EOF
					server {
					    listen 127.0.0.1:$port default_server;
					    server_name _;
					    return 403;
					}
				EOF
				;;
			apache2)
				echo "Listen 127.0.0.1:$port" > /etc/apache2/conf.d/fm--listen.conf
				cat > /etc/apache2/conf.d/fm--default.conf <<- EOF
					<VirtualHost 127.0.0.1:$port>
					    ServerName fm-default.invalid
					    <Location />
					        Require all denied
					    </Location>
					</VirtualHost>
				EOF
				;;
			*) exit 1 ;;
		esac
	)
}

# Code, front files and every enabled customer's listener, rendered for the current web model. The departing model's
# files go first, or its listener keeps the port.
fm_refresh() { # RESTART
	local uconf u
	[ -s "$FM_SECRET_FILE" ] && [ -f "$FM_CODE_DIR/index.php" ] || return 0
	fm_code_install || return 1
	rm -f /etc/nginx/conf.d/fm-*.conf /etc/apache2/conf.d/fm-*.conf
	fm_front_write || return 1
	for uconf in "$CONF_DIR"/users/*/user.conf; do
		[ -e "$uconf" ] || continue
		grep -q "^FILE_MANAGER='yes'" "$uconf" || continue
		u=$(basename "$(dirname "$uconf")")
		"$BIN/h-add-user-filemanager" "$u" no > /dev/null 2>&1 || echo "Warning: file manager for $u not restored" >&2
	done
	[ "$1" = 'no' ] && return 0
	"$BIN/h-restart-service" "$(fm_front)" > /dev/null 2>&1
}

filemanager_refresh_apply() {
	fm_refresh yes
}
