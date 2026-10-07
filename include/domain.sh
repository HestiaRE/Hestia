#!/bin/bash

#===========================================================================#
#                                                                           #
# Hestia Control Panel - Domain Function Library                            #
#                                                                           #
#===========================================================================#

#----------------------------------------------------------#
#                        WEB                               #
#----------------------------------------------------------#

# One resolution for validator and renderer: if they disagree, a template is either rejected
# though it would render, or passes and then writes an empty vhost. The ROLE picks the directory,
# not the service name - both roles hold a file called default and they are not interchangeable.
web_template_file() {
	local system="$1" name="$2" ext="$3" loc
	if [ -n "$PROXY_SYSTEM" ] && [ "$system" = "$PROXY_SYSTEM" ]; then
		echo "$SHARETPL/$system/$name.$ext"
		return
	fi
	for loc in "$WEBTPL/$system" "$SHARETPL/$system"; do
		[ -f "$loc/$name.$ext" ] && break
	done
	echo "$loc/$name.$ext"
}

# One resolution for the docker template, so validation and render cannot disagree.
web_docker_template_file() {
	echo "$WEBTPL/docker/$1/$2.tpl"
}

# Echoes "<name> <side-effect>" and returns 0 only for a KNOWN legacy value: that distinction
# lets a caller still reject a typo while accepting a value that merely aged out.
# Side effects: cache = turn the proxy cache switch on, - = none.
map_legacy_template() {
	case "$2" in
		# caching only ever existed as a proxy template, so a value left by a model switch maps
		# WITHOUT the effect - outside the proxy role it would promise a switch that needs a proxy
		caching) [ "$1" = 'proxy' ] && echo "default cache" || echo "default -" ;;
		# these differ from default only by mod_php-era behaviour that no longer exists
		hosting | phpcgi | phpfcgid | www-data) echo "default -" ;;
		# suspension is driven by the SUSPENDED flag now, never by a template value
		suspended) echo "default -" ;;
		# a per-version FPM pool template whose version is no longer installed
		PHP-[0-9]_[0-9] | *-PHP-[0-9]_[0-9]) echo "default -" ;;
		# no-php is a version now (PHP_VERSION='none'), not a template; the profile is just default
		no-php) echo "default -" ;;
		# http3 is a per-domain switch now: map to the base template and signal the switch through
		# the effect channel. Literal arms, not a suffix strip - the name is joined into a path.
		wordpress-http3) echo "wordpress http3" ;;
		wordpress-disable-xmlrpc-http3) echo "wordpress-disable-xmlrpc http3" ;;
		wordpress_mu_subdir-http3) echo "wordpress_mu_subdir http3" ;;
		*) return 1 ;;
	esac
}

# Resolvable values pass through, the rest are mapped and reported on stderr - never silent,
# a reset nobody sees is how a customer loses a feature. Echoes one line rather than setting a
# global, so it stays usable inside $(), where the assignment would be lost.
accept_web_template() {
	local role="$1" value="$2" strict="$3" file mapped effect
	# A role this model does not have carries no template - pass the stored value through
	# untouched, the same gating the validators use
	case "$role" in
		web) [ -n "$WEB_SYSTEM" ] || {
			echo "$value -"
			return 0
		} ;;
		proxy) [ -n "$PROXY_SYSTEM" ] || {
			echo "$value -"
			return 0
		} ;;
		backend) [ -n "$WEB_BACKEND" ] || {
			echo "$value -"
			return 0
		} ;;
	esac
	case "$role" in
		web) file=$(web_template_file "$WEB_SYSTEM" "$value" 'tpl') ;;
		proxy) file=$(web_template_file "$PROXY_SYSTEM" "$value" 'tpl') ;;
		backend) file="$PHPTPL/$value.tpl" ;;
	esac
	# A template name from an archive is untrusted: it is joined into a path and cat'd into the
	# vhost. A plain name only, so path segments cannot escape the template dir and read an
	# arbitrary .tpl; anything else falls through to the mapping below.
	if [ -n "$value" ] && [[ "$value" =~ ^[a-zA-Z0-9._-]+$ ]] && [ -f "$file" ]; then
		echo "$value -"
		return 0
	fi
	if read -r mapped effect <<< "$(map_legacy_template "$role" "$value")" && [ -n "$mapped" ]; then
		echo "Warning: $role template '$value' was replaced, using '$mapped'." >&2
		echo "$mapped $effect"
		return 0
	fi
	# Unknown, not merely aged out. A restore cannot abort a whole archive over one
	# record, so it falls back; a caller that asked for this by name gets the error.
	if [ "$strict" = 'strict' ]; then
		return 1
	fi
	echo "Warning: $role template '$value' is not available, using 'default'." >&2
	echo "default -"
}

# Separates a merged template (both server blocks in one file) from a legacy pair. Renderer and
# validators read this one constant so they cannot drift apart.
WEB_TPL_SSL_MARKER='#=HESTIARE-SSL-VHOST=#'

# A pair without the marker is not a leftover to sunset: mail templates ship as pairs by design,
# so detecting the format stays a permanent capability.
web_template_is_merged() {
	grep -qxF "$WEB_TPL_SSL_MARKER" "$1" 2> /dev/null
}
is_web_template_valid() {
	if [ -n "$WEB_SYSTEM" ]; then
		tpl=$(web_template_file "$WEB_SYSTEM" "$1" 'tpl')
		if [ ! -e "$tpl" ]; then
			check_result "$E_NOTEXIST" "$1 web template doesn't exist"
		fi
		# a merged template holds the SSL block itself; a legacy pair still needs its .stpl
		if ! web_template_is_merged "$tpl"; then
			stpl=$(web_template_file "$WEB_SYSTEM" "$1" 'stpl')
			[ -e "$stpl" ] || check_result "$E_NOTEXIST" "$1 web template doesn't exist"
		fi
	fi
}

is_proxy_template_valid() {
	if [ -n "$PROXY_SYSTEM" ]; then
		tpl=$(web_template_file "$PROXY_SYSTEM" "$1" 'tpl')
		if [ ! -e "$tpl" ]; then
			check_result "$E_NOTEXIST" "$1 proxy template doesn't exist"
		fi
		if ! web_template_is_merged "$tpl"; then
			stpl=$(web_template_file "$PROXY_SYSTEM" "$1" 'stpl')
			[ -e "$stpl" ] || check_result "$E_NOTEXIST" "$1 proxy template doesn't exist"
		fi
	fi
}

is_backend_template_valid() {
	if [ -n "$WEB_BACKEND" ]; then
		if [ ! -e "$PHPTPL/$1.tpl" ]; then
			check_result "$E_NOTEXIST" "$1 backend template doesn't exist"
		fi
	fi
}

is_web_domain_new() {
	web=$(grep -F -H "DOMAIN='$1'" $CONF_DIR/users/*/web.conf)
	if [ -n "$web" ]; then
		if [ "$type" == 'web' ]; then
			check_result "$E_EXISTS" "Web domain $1 exists"
		fi
		web_user=$(echo "$web" | sed "s|^$CONF_DIR/users/||; s|/web.conf.*||")
		if [ "$web_user" != "$user" ]; then
			check_result "$E_EXISTS" "Web domain $1 exists"
		fi
	fi
}

# A web domain never reuses an alias, and any other type may not take one owned by a different
# customer. The owner needs its own variable: reusing $user here compares the caller with itself,
# which makes the foreign-owner half of the check dead.
is_web_alias_new() {
	local alias_name="$1" alias_type="$2" conf alias_user aliases a line
	for conf in "$CONF_DIR"/users/*/web.conf; do
		[ -f "$conf" ] || continue
		grep -qF -- "$alias_name" "$conf" || continue
		alias_user=$(basename "$(dirname "$conf")")
		# read ALIAS directly: parse_object_kv_list would eval the whole record into the caller's scope
		while IFS= read -r line || [ -n "$line" ]; do
			aliases=$(record_field "$line" ALIAS) || continue
			local -a _a_list
			IFS=, read -ra _a_list <<< "$aliases"
			for a in "${_a_list[@]}"; do
				[ -n "$a" ] || continue
				[ "$a" = "$alias_name" ] || continue
				if [ "$alias_type" = 'web' ] || [ "$alias_user" != "$user" ]; then
					check_result "$E_EXISTS" "Web alias $alias_name exists"
				fi
			done
		done < "$conf"
	done
}

# The PHP version a domain actually runs, one source for migration and backup so they never
# disagree. The socket the vhost points at decides, because that is what serves; the pool dir is
# only the fallback, or a stray pool of an older version wins a find-order race.
web_domain_pool_version() {
	# user is an explicit arg so a multi-user sweep reads the right home; depending on an ambient
	# $user would silently drop through to the pool-dir find
	local dom="$1" usr="${2:-$user}" ver='' dom_re
	# the domain is scoped to its own conf dir, but its dots are still regex here - escape them
	dom_re=$(sed 's/[.]/\\./g' <<< "$dom")
	ver=$(grep -rhoE "php[0-9]+\.[0-9]+-fpm-${dom_re}\.sock" "$HOMEDIR/$usr/conf/web/$dom/" 2> /dev/null \
		| head -1 | grep -oE '[0-9]+\.[0-9]+')
	if [ -z "$ver" ]; then
		ver=$(find -L /etc/php/ -name "$dom.conf" -printf '%h\n' 2> /dev/null | head -1 | grep -oE '[0-9]+\.[0-9]+')
	fi
	echo "$ver"
}

prepare_web_backend() {
	# PHP_VERSION carries the version, BACKEND only the pool profile. A legacy PHP-X_Y in the
	# argument is an old record met mid-rebuild, so it still wins over the system default.
	local backend_template=${1:-$template}
	backend_type="$domain"
	# 'none' means no pool, but the socket path stays deterministic so the vhost is still valid:
	# static is served and a PHP request gets a 502, rather than the whole config failing -t.
	if [ "$PHP_VERSION" = 'none' ]; then
		backend_version=$(multiphp_default_version)
		backend_lsnr="unix:/run/php/php${backend_version}-fpm-${domain}.sock"
		return
	fi
	if [[ $backend_template =~ ^.*PHP-([0-9]+)\_([0-9]+)$ ]]; then
		backend_version="${BASH_REMATCH[1]}.${BASH_REMATCH[2]}"
	elif [ -n "$PHP_VERSION" ]; then
		backend_version="$PHP_VERSION"
	else
		backend_version=$(multiphp_default_version)
	fi
	pool=$(find -L /etc/php/$backend_version -type d \( -name "pool.d" -o -name "*fpm.d" \))

	if [ ! -e "$pool" ]; then
		check_result $E_NOTEXIST "php-fpm pool doesn't exist"
	fi
	if [ -e "$pool/$backend_type.conf" ]; then
		backend_lsnr=$(grep "listen =" $pool/$backend_type.conf)
		backend_lsnr=$(echo "$backend_lsnr" | cut -f 2 -d = | sed "s/ //")
		if [ -n "$(echo $backend_lsnr | grep /)" ]; then
			backend_lsnr="unix:$backend_lsnr"
		fi
	fi
}

delete_web_backend() {
	find -L /etc/php/ -type f -name "$backend_type.conf" -exec rm -f {} \;
}

prepare_web_aliases() {
	i=1
	for tmp_alias in ${1//,/ }; do
		tmp_alias_idn="$tmp_alias"
		if [[ "$tmp_alias" = *[![:ascii:]]* ]]; then
			tmp_alias_idn=$(idn2 --quiet $tmp_alias)
		fi
		if [[ $i -eq 1 ]]; then
			aliases="$tmp_alias"
			aliases_idn="$tmp_alias_idn"
			alias_string="ServerAlias $tmp_alias_idn"
		else
			aliases="$aliases,$tmp_alias"
			aliases_idn="$aliases_idn,$tmp_alias_idn"
			if (($i % 100 == 0)); then
				alias_string="$alias_string\n    ServerAlias $tmp_alias_idn"
			else
				alias_string="$alias_string $tmp_alias_idn"
			fi
		fi
		alias_number=$i
		((i++))
	done
}

prepare_web_domain_values() {
	if [[ "$domain" = *[![:ascii:]]* ]]; then
		domain_idn=$(idn2 --quiet $domain)
	else
		domain_idn=$domain
	fi
	group="$user"
	docroot="$HOMEDIR/$user/web/$domain/public_html"

	if [ -n "$WEB_BACKEND" ]; then
		prepare_web_backend "$BACKEND"
	fi

	server_alias=''
	alias_string=''
	aliases_idn=''
	ssl_ca_str=''
	prepare_web_aliases $ALIAS

	ssl_crt="$HOMEDIR/$user/conf/web/$domain/ssl/$domain.crt"
	ssl_key="$HOMEDIR/$user/conf/web/$domain/ssl/$domain.key"
	ssl_pem="$HOMEDIR/$user/conf/web/$domain/ssl/$domain.pem"
	ssl_ca="$HOMEDIR/$user/conf/web/$domain/ssl/$domain.ca"
	if [ ! -e "$USER_DATA/ssl/$domain.ca" ]; then
		ssl_ca_str='#'
	fi

	# The stored record wins: the writer already resolved the path and checked containment, and
	# re-deriving it here could disagree with the value that was validated.
	if [ -n "$CUSTOM_DOCROOT" ]; then
		custom_docroot="$CUSTOM_DOCROOT"
		docroot="$custom_docroot"
	else
		docroot="$HOMEDIR/$user/web/$domain/public_html"
	fi

	# Rendered from share/ because the selectable tree has no apache variant. Admin suspension
	# outranks the customer switch, and unsuspending returns to the customer's offline state - an
	# admin action must not clear a choice. Reset per domain, or the NEXT one renders suspended too.
	WEBTPL_OVERRIDE=''
	if [ "$SUSPENDED" = 'yes' ]; then
		docroot="$SHARETPL/suspend/pages/admin"
		WEBTPL_OVERRIDE="$SHARETPL/suspend/admin"
	elif [ "$OFFLINE" = 'yes' ]; then
		docroot="$SHARETPL/suspend/pages/offline"
		WEBTPL_OVERRIDE="$SHARETPL/suspend/offline"
	fi
	if [ -n "$WEBTPL_OVERRIDE" ]; then
		TPL="$WEB_SYSTEM"
		[ -n "$PROXY_SYSTEM" ] && PROXY="proxy"
	fi
}

# All families for one apache VirtualHost tag: the tag is structural, deletion would
# orphan </VirtualHost>, so it resolves - apache takes several addresses in one tag.
web_vhost_addrs() {
	local out=''
	[ -n "$local_ip" ] && out="$local_ip:$1"
	[ -n "$local_ip6" ] && out="${out:+$out }[$local_ip6]:$1"
	echo "$out"
}

# nginx->backend hop: v4 when present, else bracketed v6 - a bare family token here
# would fall to the deletion rule on a single-family box.
web_backend_addr() {
	if [ -n "$local_ip" ]; then
		echo "$local_ip"
	else
		echo "[$local_ip6]"
	fi
}

# Stats password protection. nginx parses its fragment as root, so it lives in the root-owned conf folder; the
# rest sits in the customer's stats/ and is written as the customer. Keep in step with web_render_template.
web_stats_auth_write() { # STATS_USER STATS_CRYPT
	local stats_dir="$HOMEDIR/$user/web/$domain/stats" closed='' rc=0
	if [ "$WEB_SYSTEM" = 'nginx' ]; then
		printf '%s\n' 'auth_basic "Web Statistics";' "auth_basic_user_file $stats_dir/.htpasswd;" \
			> "$HOMEDIR/$user/conf/web/$domain/nginx.stats_auth.conf"
	fi
	user_exec test -w "$stats_dir" || {
		user_exec chmod u+w "$stats_dir"
		closed=yes
	}
	# Replaced, not overwritten: an older version left these root-owned, and the customer cannot write into those.
	user_exec rm -f "$stats_dir/auth.conf" "$stats_dir/.htaccess" "$stats_dir/.htpasswd"
	if [ "$WEB_SYSTEM" != 'nginx' ]; then
		printf '%s\n' "AuthUserFile $stats_dir/.htpasswd" 'AuthName "Web Statistics"' 'AuthType Basic' 'Require valid-user' \
			| user_exec tee "$stats_dir/.htaccess" > /dev/null || rc=1
	fi
	echo "$1:$2" | user_exec tee "$stats_dir/.htpasswd" > /dev/null || rc=1
	[ -z "$closed" ] || user_exec chmod u-w "$stats_dir"
	return $rc
}

web_stats_auth_clear() {
	local stats_dir="$HOMEDIR/$user/web/$domain/stats" closed=''
	rm -f "$HOMEDIR/$user/conf/web/$domain/nginx.stats_auth.conf"
	[ -d "$stats_dir" ] || return 0
	user_exec test -w "$stats_dir" || {
		user_exec chmod u+w "$stats_dir"
		closed=yes
	}
	user_exec rm -f "$stats_dir/auth.conf" "$stats_dir/.htaccess" "$stats_dir/.htpasswd"
	[ -z "$closed" ] || user_exec chmod u-w "$stats_dir"
}

# Drops one http auth account; the name is matched as a whole field, a dot in it is no wildcard.
web_htpasswd_drop() { # FILE NAME
	[ -f "$1" ] || return 0
	local rest
	rest=$(awk -F: -v u="$2" '$1 != u' "$1") || return 1
	printf '%s\n' "$rest" | sed '/^$/d' > "$1"
}

# Update entry: a vhost rendered before the fragment moved still includes it from the customer's folder.
stats_auth_fragment_apply() {
	local f rest seen=" " rc=0
	for f in "$HOMEDIR"/*/conf/web/*/nginx*.conf; do
		[ -e "$f" ] || continue
		grep -qF '/stats/auth.conf' "$f" || continue
		rest=${f#"$HOMEDIR"/}
		rest=${rest%/*}
		case $seen in *" $rest "*) continue ;; esac
		seen="$seen$rest "
		"$BIN/h-rebuild-web-domain" "${rest%%/*}" "${rest##*/}" no || rc=1
	done
	[ "$seen" = " " ] && return $rc
	"$BIN/h-restart-web" || rc=1
	"$BIN/h-restart-proxy" || rc=1
	return $rc
}

# The ONE substitution engine (#890): stdin template in, rendered text out. Divergent
# values arrive in _r_* (webmail renders %domain% as the alias, %web_system% as the
# front). Removing or renaming a token breaks every custom template that uses it.
# A repeatable line whose family placeholder has no value is deleted before substitution;
# structural lines carry resolved tokens (%vhost%, %backend_addr%) and are never deleted.
# %ip6% substitutes RAW - brackets are template text at the use site.
# Custom-template contract: %ip% and %ip6% never on one line (deleted once EITHER family
# is absent); an %ip%-only template on a v6-only box renders a listen-less server block,
# which nginx answers by silently binding the wildcard.
web_render_template() {
	local _del=''
	[ -z "$_r_ip" ] && _del="/%ip%/d; "
	[ -z "$_r_ip6" ] && _del="${_del}/%ip6%/d; "
	# An older or custom template still includes stats/auth.conf from the customer's folder: nginx must never parse it.
	sed -e "$_del" \
		-e "s|%home%/%user%/web/%domain%/stats/auth\.conf|%home%/%user%/conf/web/%domain%/nginx.stats_auth.conf|g" \
		-e "s|%ip%|$_r_ip|g" \
		-e "s|%ip6%|$_r_ip6|g" \
		-e "s|%vhost%|$_r_vhost|g" \
		-e "s|%vhost_ssl%|$_r_vhost_ssl|g" \
		-e "s|%backend_addr%|$_r_backend_addr|g" \
		-e "s|%domain%|$_r_domain|g" \
		-e "s|%domain_idn%|$_r_domain_idn|g" \
		-e "s|%root_domain%|$_r_root_domain|g" \
		-e "s|%alias%|$_r_alias|g" \
		-e "s|%alias_idn%|$_r_alias_idn|g" \
		-e "s|%alias_string%|$alias_string|g" \
		-e "s|%email%|info@$_r_root_domain|g" \
		-e "s|%web_system%|$_r_web_system|g" \
		-e "s|%web_port%|$WEB_PORT|g" \
		-e "s|%web_ssl_port%|$WEB_SSL_PORT|g" \
		-e "s|%backend_lsnr%|$backend_lsnr|g" \
		-e "s|%rgroups%|$WEB_RGROUPS|g" \
		-e "s|%proxy_system%|$PROXY_SYSTEM|g" \
		-e "s|%proxy_port%|$PROXY_PORT|g" \
		-e "s|%proxy_ssl_port%|$PROXY_SSL_PORT|g" \
		-e "s|%front_port%|${PROXY_PORT:-$WEB_PORT}|g" \
		-e "s|%front_ssl_port%|${PROXY_SSL_PORT:-$WEB_SSL_PORT}|g" \
		-e "s|%docker_port%|$DOCKER_PORT|g" \
		-e "s|%docker_ip%|$web_docker_ip|g" \
		-e "s|%panel_port%|${BACKEND_PORT:-8083}|g" \
		-e "s/%proxy_extentions%/${PROXY_EXT//,/|}/g" \
		-e "s/%proxy_extensions%/${PROXY_EXT//,/|}/g" \
		-e "s|%user%|$user|g" \
		-e "s|%group%|$user|g" \
		-e "s|%home%|$HOMEDIR|g" \
		-e "s|%docroot%|$docroot|g" \
		-e "s|%sdocroot%|$docroot|g" \
		-e "s|%ssl_crt%|$ssl_crt|g" \
		-e "s|%ssl_key%|$ssl_key|g" \
		-e "s|%ssl_pem%|$ssl_pem|g" \
		-e "s|%ssl_ca_str%|$ssl_ca_str|g" \
		-e "s|%ssl_ca%|$ssl_ca|g"
}

# conf_link_owner <path>: the customer a generated config belongs to, empty when the path is not
# under a customer home. Read from the path deliberately. The generated files are root-owned and
# carry no per-customer bit, while $HOMEDIR/<user>/conf/... is a contract HestiaCP compatibility
# keeps permanent, and it is the same segment the backup and the delete path key on.
conf_link_owner() {
	local _p="${1#"$HOMEDIR/"}"
	[ "$_p" = "$1" ] && return 0
	echo "${_p%%/*}"
}

# conf_link_drop <conf.d link> <customer>: the same rule for the removal side. Measured while
# building the setter guard: h-rebuild-web-domain deletes the conf.d links BEFORE it renders, so a
# guard that only sits at the setter sees no link at all and waves the hijack through. Guarding the
# removal is what makes the bottleneck real (#956).
conf_link_drop() {
	local _link="$1" _user="$2" _cur _own_cur
	if [ -L "$_link" ]; then
		_cur=$(readlink "$_link")
		if [ -n "$_cur" ] && [ -e "$_cur" ]; then
			_own_cur=$(conf_link_owner "$_cur")
			if [ -n "$_own_cur" ] && [ -n "$_user" ] && [ "$_own_cur" != "$_user" ]; then
				check_result "$E_FORBIDEN" \
					"$_link serves $_own_cur ($_cur), refusing to remove it on behalf of $_user"
			fi
		fi
	fi
	rm -f "$_link"
}

# conf_link_set <generated conf> <conf.d link>: never take a link away from another customer.
# Every web-config writer funnels through add_web_config, so this is the last place a domain that
# belongs to someone else can still be caught after is_domain_new refused it at the front (#956,
# the damage this produced is #925). A missing link, a dangling one, or one already pointing into
# the same home is replaced exactly as before; only a live link into a DIFFERENT home refuses, and
# the message names both customers. No supported command moves a domain between customers, so there
# is no legitimate path that needs a way around this.
conf_link_set() {
	local _conf="$1" _link="$2" _cur _own_new _own_cur
	if [ -L "$_link" ]; then
		_cur=$(readlink "$_link")
		if [ -n "$_cur" ] && [ -e "$_cur" ]; then
			_own_new=$(conf_link_owner "$_conf")
			_own_cur=$(conf_link_owner "$_cur")
			if [ -n "$_own_cur" ] && [ -n "$_own_new" ] && [ "$_own_cur" != "$_own_new" ]; then
				check_result "$E_FORBIDEN" \
					"$_link already serves $_own_cur ($_cur), refusing to point it at $_own_new"
			fi
		fi
	fi
	rm -f "$_link"
	ln -s "$_conf" "$_link"
}

add_web_config() {
	if [ ! -d "$HOMEDIR/$user/conf/web/$domain" ]; then
		mkdir -p "$HOMEDIR/$user/conf/web/$domain/"
	fi

	domain_idn=$domain
	format_domain_idn

	WEBTPL_LOCATION=$(dirname "$(web_template_file "$1" "${2%.*}" "${2##*.}")")

	if [ -n "$WEBTPL_OVERRIDE" ]; then
		WEBTPL_LOCATION="$WEBTPL_OVERRIDE"
	fi

	# Docker domain: the front renders from templates/docker/, a backend vhost would only shadow
	# the container. TPL/PROXY stay untouched so a model switch survives; suspend override wins.
	if [ -n "$DOCKER" ] && [ -z "$WEBTPL_OVERRIDE" ]; then
		# DOCKER without DOCKER_IP renders "http://:PORT", and nginx and apache refuse their WHOLE
		# configuration over such an upstream - one record would take the box's web front down.
		if [ -z "$(get_user_value '$DOCKER_IP')" ]; then
			echo "Error: $domain is a docker domain but $user has no DOCKER_IP - $1 vhost not written" >&2
			web_config_skipped=$((${web_config_skipped:-0} + 1))
			rm -f "$HOMEDIR/$user/conf/web/$domain/$1.conf" "$HOMEDIR/$user/conf/web/$domain/$1.ssl.conf"
			conf_link_drop "/etc/$1/conf.d/domains/$domain.conf" "$user"
			conf_link_drop "/etc/$1/conf.d/domains/$domain.ssl.conf" "$user"
			return "$E_NOTEXIST"
		fi
		if [ -n "$PROXY_SYSTEM" ] && [ "$1" = "$WEB_SYSTEM" ] && [ "$WEB_SYSTEM" != "$PROXY_SYSTEM" ]; then
			# reconcile away a stale backend vhost from the pre-docker life of the domain
			rm -f "$HOMEDIR/$user/conf/web/$domain/$1.conf" "$HOMEDIR/$user/conf/web/$domain/$1.ssl.conf"
			conf_link_drop "/etc/$1/conf.d/domains/$domain.conf" "$user"
			conf_link_drop "/etc/$1/conf.d/domains/$domain.ssl.conf" "$user"
			return 0
		fi
		# Per-system dir: after a model switch a custom template may have no variant here. Render
		# default instead of skipping (no front = outage); the field keeps the name.
		local web_docker_tpl
		web_docker_tpl=$(web_docker_template_file "$1" "$DOCKER")
		if [ ! -f "$web_docker_tpl" ] && [ -f "$(web_docker_template_file "$1" default)" ]; then
			echo "Warning: docker template '$DOCKER' has no $1 variant - rendering default for $domain" >&2
			web_docker_tpl=$(web_docker_template_file "$1" default)
		fi
		WEBTPL_LOCATION=$(dirname "$web_docker_tpl")
		set -- "$1" "$(basename "$web_docker_tpl")"
	fi

	# A missing template would become a 0-byte vhost that apache2 -t accepts. Warn +
	# skip, not check_result: an exit would abort a rebuild loop over one broken record.
	if [ ! -f "${WEBTPL_LOCATION}/$2" ]; then
		echo "Error: web template ${WEBTPL_LOCATION}/$2 doesn't exist - $domain vhost not written" >&2
		# Tallied for the rebuild summary - the stderr line alone drowns in a nightly run
		web_config_skipped=$((${web_config_skipped:-0} + 1))
		return "$E_NOTEXIST"
	fi

	# A merged template renders ONE vhost file: the HTTP block always, the SSL block only when SSL
	# is on, because a listen 443 without a cert fails -t. A pair renders its own .conf/.ssl.conf.
	local web_tpl_merged=0
	web_template_is_merged "${WEBTPL_LOCATION}/$2" && web_tpl_merged=1

	# From the owner record, not a sourced var: the rebuild path never sources user.conf and would
	# render it empty. front_port = the model's public listener (apache-only has no PROXY_* keys).
	local web_docker_ip=''
	web_docker_ip=$(get_user_value '$DOCKER_IP')
	# the domain picks its octet inside the customer /24; empty means the daemon-default .1
	if [ -n "$web_docker_ip" ] && [ -n "$DOCKER_OCTET" ]; then
		web_docker_ip="${web_docker_ip%.*}.$DOCKER_OCTET"
	fi

	conf="$HOMEDIR/$user/conf/web/$domain/$1.conf"
	if [ "$web_tpl_merged" = 0 ] && [[ "$2" =~ stpl$ ]]; then
		conf="$HOMEDIR/$user/conf/web/$domain/$1.ssl.conf"
	fi

	# Family twin of local_ip, derived from the record because ~15 re-render commands never
	# resolve addresses. A caller that SET it - even empty - wins. The variable is
	# process-wide by contract, so a DERIVED value is unset again at the end.
	local _derived_ip6=0
	if [ -z "${local_ip6+x}" ]; then
		_derived_ip6=1
		local_ip6=''
		if [ -n "$IP6" ] && [ -e "$CONF_DIR/ips/$IP6" ]; then
			local_ip6="$IP6"
		fi
	fi

	# Divergent values for the engine; everything else reads this scope
	local _r_ip="$local_ip" _r_ip6="$local_ip6" _r_domain="$domain" _r_domain_idn="$domain_idn" \
		_r_root_domain="$domain" _r_alias="${aliases//,/ }" _r_alias_idn="${aliases_idn//,/ }" \
		_r_web_system="$WEB_SYSTEM" _r_vhost _r_vhost_ssl _r_backend_addr
	_r_vhost="$(web_vhost_addrs "$WEB_PORT")"
	_r_vhost_ssl="$(web_vhost_addrs "$WEB_SSL_PORT")"
	_r_backend_addr="$(web_backend_addr)"
	{
		if [ "$web_tpl_merged" = 1 ]; then
			if [ "$SSL" = 'yes' ]; then
				sed "/^${WEB_TPL_SSL_MARKER}\$/d" "${WEBTPL_LOCATION}/$2"
			else
				sed "/^${WEB_TPL_SSL_MARKER}\$/,\$d" "${WEBTPL_LOCATION}/$2"
			fi
		else
			cat "${WEBTPL_LOCATION}/$2"
		fi
	} | web_render_template > $conf

	process_http2_directive "$conf"

	# DIR_LIST lives only in the GENERATED vhost - the template hardcodes -Indexes - so every
	# regeneration drops it while the record keeps saying yes. rebuild_web_domain_conf re-applies it;
	# the eleven commands that regenerate directly did not, and h-add-web-domain-ssl was merely the
	# one a roundtrip happened to hit (#831). Here rather than in each of them, so a twelfth cannot
	# forget. Not while suspended: that template hardcodes -Indexes on purpose.
	if [ "$DIR_LIST" = 'yes' ] && [ -z "$WEBTPL_OVERRIDE" ]; then
		sed -i "s/-Index/+Index/g" "$conf"
	fi

	chown root:$user $conf
	chmod 640 $conf

	if [ "$web_tpl_merged" = 1 ]; then
		# One vhost file holds both server blocks, so a separate .ssl.conf symlink is stale. The
		# custom-config migration the pair branches run is skipped: it predates the per-domain
		# conf dir, which a box carrying a merged template already has.
		# Through the guard, not a bare rm: stale describes the FORM, not the owner. A merged
		# template has no .ssl.conf source file, so the guarded removal in h-rebuild-web-domain
		# never runs for it, and this line was the one place where a live link of another customer
		# could still be deleted without a word (measured on the default template, which is merged).
		conf_link_drop "/etc/$1/conf.d/domains/$domain.ssl.conf" "$user"
		conf_link_set "$conf" "/etc/$1/conf.d/domains/$domain.conf"
	elif [[ "$2" =~ stpl$ ]]; then
		conf_link_set "$conf" "/etc/$1/conf.d/domains/$domain.ssl.conf"

		# Rename/Move extra SSL config files
		find=$(find $HOMEDIR/$user/conf/web/*.$domain.org* 2> /dev/null)
		for f in $find; do
			if [[ $f =~ .*/s(nginx|apache2)\.$domain\.conf(.*) ]]; then
				ServerType="${BASH_REMATCH[1]}"
				CustomConfigName="${BASH_REMATCH[2]}"
				if [ "$CustomConfigName" = "_letsencrypt" ]; then
					rm -f "$f"
					continue
				fi
				mv "$f" "$HOMEDIR/$user/conf/web/$domain/$ServerType.ssl.conf_old$CustomConfigName"
			fi
		done
	else
		conf_link_set "$conf" "/etc/$1/conf.d/domains/$domain.conf"
		# Rename/Move extra config files
		find=$(find $HOMEDIR/$user/conf/web/*.$domain.org* 2> /dev/null)
		for f in $find; do
			if [[ $f =~ .*/(nginx|apache2)\.$domain\.conf(.*) ]]; then
				ServerType="${BASH_REMATCH[1]}"
				CustomConfigName="${BASH_REMATCH[2]}"
				if [ "$CustomConfigName" = "_letsencrypt" ]; then
					rm -f "$f"
					continue
				fi
				mv "$f" "$HOMEDIR/$user/conf/web/$domain/$ServerType.conf_old$CustomConfigName"
			elif [[ $f =~ .*/forcessl\.(nginx|apache2)\.$domain\.conf ]]; then
				ServerType="${BASH_REMATCH[1]}"
				mv "$f" "$HOMEDIR/$user/conf/web/$domain/$ServerType.forcessl.conf"
			fi
		done
	fi

	trigger="${2/.*pl/.sh}"
	if [ -x "${WEBTPL_LOCATION}/$trigger" ]; then
		$WEBTPL_LOCATION/$trigger \
			$user $domain $local_ip $HOMEDIR \
			$HOMEDIR/$user/web/$domain/public_html
	fi
	[ "$_derived_ip6" = 1 ] && unset -v local_ip6
	return 0
}

get_web_config_lines() {
	tpl_lines=$(egrep -ni "name %domain_idn%" $1 | grep -w %domain_idn%)
	tpl_lines=$(echo "$tpl_lines" | cut -f 1 -d :)
	tpl_last_line=$(wc -l $1 | cut -f 1 -d ' ')
	if [ -z "$tpl_lines" ]; then
		check_result $E_PARSING "can't parse template $1"
	fi

	domain_idn=$domain
	format_domain_idn
	vhost_lines=$(grep -niF "name $domain_idn" $2)
	vhost_lines=$(echo "$vhost_lines" | egrep "$domain_idn($| |;)")
	vhost_lines=$(echo "$vhost_lines" | cut -f 1 -d :)
	if [ -z "$vhost_lines" ]; then
		check_result $E_PARSING "can't parse config $2"
	fi

	top_line=$((vhost_lines + 1 - tpl_lines))
	bottom_line=$((top_line - 1 + tpl_last_line))
	multi=$(sed -n "$top_line,$bottom_line p" $2 | grep ServerAlias | wc -l)
	if [ "$multi" -ge 2 ]; then
		bottom_line=$((bottom_line + multi - 1))
	fi
}

replace_web_config() {
	# Only an IP change calls this: both server blocks live in one .conf and carry the same IP, so
	# one value-replace covers them. Anything that must tell the two blocks apart re-renders
	# instead - a value-replace cannot, once their values coincide.
	conf="$HOMEDIR/$user/conf/web/$domain/$1.conf"

	if [ -e "$conf" ]; then
		# dots escaped: the old IP is a sed pattern here
		sed -i "s|${old//./\\.}|$new|g" $conf
	fi
}

del_web_config() {
	# A .stpl call clears only the SSL vhost; a .tpl call clears both, because a merged template
	# keeps both server blocks in one .conf. For a pair the SSL file has its own .stpl call, so
	# clearing it here as well is harmless.
	local confnames="$domain.conf $domain.ssl.conf"
	local conf="$HOMEDIR/$user/conf/web/$domain/$1.conf"
	if [[ "$2" =~ stpl$ ]]; then
		conf="$HOMEDIR/$user/conf/web/$domain/$1.ssl.conf"
		confnames="$domain.ssl.conf"
	fi

	# Clean up legacy configuration files
	if [ ! -e "$conf" ]; then
		local legacyconf="$HOMEDIR/$user/conf/web/$1.conf"
		if [[ "$2" =~ stpl$ ]]; then
			legacyconf="$HOMEDIR/$user/conf/web/s$1.conf"
		fi
		rm -f $legacyconf

		# Remove old global includes file
		rm -f /etc/$1/conf.d/hestia.conf
	fi

	# Remove domain configuration files and clean up symbolic links
	rm -f "$conf"
	[[ "$2" =~ stpl$ ]] || rm -f "$HOMEDIR/$user/conf/web/$domain/$1.ssl.conf"

	local cn
	for cn in $confnames; do
		if [ -n "$WEB_SYSTEM" ] && [ "$WEB_SYSTEM" = "$1" ]; then
			conf_link_drop "/etc/$WEB_SYSTEM/conf.d/domains/$cn" "$user"
		fi
		if [ -n "$PROXY_SYSTEM" ] && [ "$PROXY_SYSTEM" = "$1" ]; then
			conf_link_drop "/etc/$PROXY_SYSTEM/conf.d/domains/$cn" "$user"
		fi
	done
}

# http3 rides on a quic listen in an include fragment that every merged template already globs, so
# it works on any template without a renderer change. Needs an nginx front whose build carries
# http_v3, which the deb12 and ub24 OS packages do not.
nginx_has_http3() {
	command -v nginx > /dev/null 2>&1 && nginx -V 2>&1 | grep -q -- '--with-http_v3_module'
}

# the nginx front is the proxy in the both model, else the standalone nginx web system
web_http3_front_ssl_port() {
	if [ "$PROXY_SYSTEM" = 'nginx' ]; then echo "$PROXY_SSL_PORT"; else echo "$WEB_SSL_PORT"; fi
}

# write the quic fragment for the current domain; $1 is the resolved front IP (get_real_ip)
# The static-offload list nginx serves in front of apache. Shared with the restore, which needs the
# target's default when a proxy-less archive lands on a box that has a proxy.
default_proxy_ext() {
	local ext
	# Code
	ext="css,htm,html,js,mjs,json,xml"
	# Image (from https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Image_types)
	ext="$ext,apng,avif,bmp,cur,gif,ico,jfif,jpg,jpeg,pjp,pjpeg,png,svg,tif,tiff,webp"
	# Audio from (https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Audio_codecs)
	ext="$ext,aac,caf,flac,m4a,midi,mp3,ogg,opus,wav"
	# Video (from https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Video_codecs)
	ext="$ext,3gp,av1,avi,m4v,mkv,mov,mpg,mpeg,mp4,mp4v,webm"
	# Fonts
	ext="$ext,otf,ttf,woff,woff2"
	# Productivity
	ext="$ext,doc,docx,odf,odp,ods,odt,pdf,ppt,pptx,rtf,txt,xls,xlsx"
	# Archive
	ext="$ext,7z,bz2,gz,rar,tar,tgz,zip"
	# Binaries
	ext="$ext,apk,appx,bin,dmg,exe,img,iso,jar,msi"
	# Other
	ext="$ext,webmanifest"
	echo "$ext"
}

add_web_http3_config() {
	# plain quic, no reuseport: nginx accepts many quic listens on one ip:port, while reuseport
	# would need one-per-ip bookkeeping that a decoupled fragment must not own
	local ip="$1" ip6="$2" port frag
	# Soft rule as in the renderer: a vanished v6 object must not become a quic listen on
	# a dead address; one guard in the writer covers all three callers.
	if [ -n "$ip6" ] && [ ! -e "$CONF_DIR/ips/$ip6" ]; then
		ip6=''
	fi
	port=$(web_http3_front_ssl_port)
	frag="$HOMEDIR/$user/conf/web/$domain/nginx.ssl.conf_http3"
	# single quotes keep $server_port literal for nginx; one quic listen per configured family
	: > "$frag"
	[ -n "$ip" ] && printf 'listen      %s:%s quic;\n' "$ip" "$port" >> "$frag"
	[ -n "$ip6" ] && printf 'listen      [%s]:%s quic;\n' "$ip6" "$port" >> "$frag"
	printf 'add_header Alt-Svc '\''h3=":$server_port"; ma=86400'\'';\n' >> "$frag"
	chown root:"$user" "$frag"
	chmod 640 "$frag"
}

del_web_http3_config() {
	rm -f "$HOMEDIR/$user/conf/web/$domain/nginx.ssl.conf_http3"
}

# The field is intent and survives a restore or host move; the fragment is intent AND capability.
# A domain landing on a box without http_v3 therefore keeps HTTP3='yes' but grows no listen, and
# picks it up again on a capable box. Silent, so a batch rebuild cannot error per unsupported domain.
apply_web_http3_config() {
	if [ "$HTTP3" = 'yes' ] \
		&& { [ "$PROXY_SYSTEM" = 'nginx' ] || [ "$WEB_SYSTEM" = 'nginx' ]; } \
		&& nginx_has_http3; then
		add_web_http3_config "$(get_real_ip "$IP")" "$IP6"
	else
		del_web_http3_config
	fi
}

is_web_domain_cert_valid() {
	if [ ! -e "$ssl_dir/$domain.crt" ]; then
		check_result "$E_NOTEXIST" "$ssl_dir/$domain.crt not found"
	fi

	if [ ! -e "$ssl_dir/$domain.key" ]; then
		check_result "$E_NOTEXIST" "$ssl_dir/$domain.key not found"
	fi

	crt_vrf=$(openssl verify $ssl_dir/$domain.crt 2>&1)
	if [ -n "$(echo $crt_vrf | grep 'unable to load')" ]; then
		check_result "$E_INVALID" "SSL Certificate is not valid"
	fi

	if [ -n "$(echo $crt_vrf | grep 'unable to get local issuer')" ]; then
		if [ ! -e "$ssl_dir/$domain.ca" ]; then
			check_result "$E_NOTEXIST" "Certificate Authority not found"
		fi
	fi

	if [ -e "$ssl_dir/$domain.ca" ]; then
		s1=$(openssl x509 -noout -in $ssl_dir/$domain.crt -issuer 2> /dev/null | cut -d = -f2-)
		s2=$(openssl x509 -noout -in $ssl_dir/$domain.ca -subject 2> /dev/null | cut -d = -f2-)
		if [ "$s1" != "$s2" ]; then
			check_result "$E_NOTEXIST" "SSL intermediate chain is not valid"
		fi
	fi

	key_vrf=$(grep 'PRIVATE KEY' $ssl_dir/$domain.key | wc -l)
	if [ "$key_vrf" -ne 2 ]; then
		check_result "$E_INVALID" "SSL Key is not valid"
	fi
	if [ -n "$(grep 'ENCRYPTED' $ssl_dir/$domain.key)" ]; then
		check_result "$E_FORBIDEN" "SSL Key is protected (remove pass_phrase)"
	fi

	# no pkill openssl: it reaped every openssl on the box; our own $pid dies just below

	openssl s_server -quiet -cert $ssl_dir/$domain.crt \
		-key $ssl_dir/$domain.key >> /dev/null 2>&1 &
	pid=$!
	sleep 0.5
	disown &> /dev/null
	kill $pid &> /dev/null
	check_result $? "ssl certificate key pair is not valid" $E_INVALID
}

# http-01 on an apache front: the token sits in a root-owned directory per name, published by an Alias in the vhost's
# conf_letsencrypt fragment. Never a docroot, which the customer controls, nor a directory customers share.
ACME_APACHE_DIR=/var/lib/hestia-acme
acme_apache_publish() { # CONF_DIR NAME TOKEN KEY_AUTHORIZATION
	local _dir="$ACME_APACHE_DIR/$2"
	mkdir -p "$_dir"
	chmod 755 "$ACME_APACHE_DIR" "$(dirname "$_dir")" "$_dir"
	echo "$4" > "$_dir/$3"
	chmod 644 "$_dir/$3"
	cat > "$1/apache2.conf_letsencrypt" <<- EOF
		Alias /.well-known/acme-challenge/ $_dir/
		<Directory $_dir/>
		    Options None
		    AllowOverride None
		    Require all granted
		</Directory>
	EOF
	[ -e "$1/apache2.ssl.conf_letsencrypt" ] || ln -s "$1/apache2.conf_letsencrypt" "$1/apache2.ssl.conf_letsencrypt"
}

# The log files of a web domain and their rotations, one path per line. By name: $domain.* also matches
# example.com.au, which may belong to another customer.
web_domain_logs() { # DOMAIN
	local _d="/var/log/$WEB_SYSTEM/domains" _f
	for _f in "$_d/$1.log" "$_d/$1.error.log" "$_d/$1.bytes" "$_d/$1.log".* "$_d/$1.error.log".*; do
		[ -e "$_f" ] || continue
		echo "$_f"
	done
}

# FTP_USER, FTP_MD5 and FTP_PATH are parallel colon lists. Positions count from 1, an empty field keeps its place,
# and names compare as whole strings: an account name may carry a dot.
ftp_list_index() { # LIST NAME
	local _rest="$1:" _i=1
	while [ -n "$_rest" ]; do
		[ "${_rest%%:*}" = "$2" ] && echo "$_i" && return 0
		_rest="${_rest#*:}"
		_i=$((_i + 1))
	done
	return 1
}

ftp_list_get() { # LIST N
	local _rest="$1:" _i=1
	while [ -n "$_rest" ]; do
		[ "$_i" -eq "$2" ] && echo "${_rest%%:*}" && return 0
		_rest="${_rest#*:}"
		_i=$((_i + 1))
	done
}

# LIST with field N set to VALUE, or dropped when VALUE is not given.
ftp_list_edit() { # LIST N [VALUE]
	local _rest="$1:" _i=1 _out=()
	while [ -n "$_rest" ] || { [ $# -ge 3 ] && [ "$_i" -le "$2" ]; }; do
		if [ "$_i" -eq "$2" ]; then
			[ $# -lt 3 ] || _out+=("$3")
		else
			_out+=("${_rest%%:*}")
		fi
		_rest="${_rest#*:}"
		_i=$((_i + 1))
	done
	local IFS=:
	echo "${_out[*]}"
}

# Replaces the certificate of $domain with the one in SSL_DIR, in the user's store and in the copy the vhost reads,
# and re-renders the vhost: a chain file that came or went changes the apache template.
web_ssl_replace() {
	local ssl_dir="$1" ext store="$USER_DATA/ssl" live="$HOMEDIR/$user/conf/web/$domain/ssl"
	# By name, never $domain.*: that also matches the files of example.com.au.
	for ext in crt key pem ca; do
		rm -f "$store/$domain.$ext" "$live/$domain.$ext"
	done
	cp -f "$ssl_dir/$domain.crt" "$store/$domain.crt"
	cp -f "$ssl_dir/$domain.key" "$store/$domain.key"
	cp -f "$ssl_dir/$domain.crt" "$store/$domain.pem"
	if [ -e "$ssl_dir/$domain.ca" ]; then
		cp -f "$ssl_dir/$domain.ca" "$store/$domain.ca"
		echo >> "$store/$domain.pem"
		cat "$store/$domain.ca" >> "$store/$domain.pem"
	fi
	for ext in crt key pem ca; do
		[ -e "$store/$domain.$ext" ] || continue
		chmod 660 "$store/$domain.$ext"
		cp -f "$store/$domain.$ext" "$live/$domain.$ext"
	done

	get_domain_values 'web'
	local_ip=$(get_real_ip "$IP")
	prepare_web_domain_values
	del_web_config "$WEB_SYSTEM" "$TPL.tpl"
	add_web_config "$WEB_SYSTEM" "$TPL.tpl"
	if [ -n "$PROXY_SYSTEM" ] && [ -n "$PROXY" ]; then
		del_web_config "$PROXY_SYSTEM" "$PROXY.tpl"
		add_web_config "$PROXY_SYSTEM" "$PROXY.tpl"
	fi
}
#----------------------------------------------------------#
#                       MAIL                               #
#----------------------------------------------------------#

is_mail_domain_new() {
	mail=$(ls $CONF_DIR/users/*/mail/$1.conf 2> /dev/null)
	if [ -n "$mail" ]; then
		if [ "$2" == 'mail' ]; then
			check_result $E_EXISTS "Mail domain $1 exists"
		fi
		mail_user=$(echo "$mail" | sed "s|^$CONF_DIR/users/||; s|/mail/.*||")
		if [ "$mail_user" != "$user" ]; then
			check_result "$E_EXISTS" "Mail domain $1 exists"
		fi
	fi
	mail_sub=$(echo "$1" | cut -f 1 -d .)
	mail_nosub=$(echo "$1" | cut -f 1 -d . --complement)
	for mail_reserved in $(echo "mail $WEBMAIL_ALIAS"); do
		if [ -n "$(ls $CONF_DIR/users/*/mail/$mail_reserved.$1.conf 2> /dev/null)" ]; then
			if [ "$2" == 'mail' ]; then
				check_result "$E_EXISTS" "Required subdomain \"$mail_reserved.$1\" already exists"
			fi
		fi
		if [ -n "$(ls $CONF_DIR/users/*/mail/$mail_nosub.conf 2> /dev/null)" ] && [ "$mail_sub" = "$mail_reserved" ]; then
			if [ "$2" == 'mail' ]; then
				check_result "$E_INVALID" "The subdomain \"$mail_sub.\" is reserved by \"$mail_nosub\""
			fi
		fi
	done
}

is_mail_new() {
	local line a
	local -a als
	[ -f "$USER_DATA/mail/$domain.conf" ] || return 0
	# Per field and literal: a pattern over the whole file let a.b match axb and only weighed the last record it hit.
	while IFS= read -r line; do
		[ "$(record_field "$line" ACCOUNT)" != "$1" ] || check_result "$E_EXISTS" "mail account $1 already exists"
		IFS=, read -ra als <<< "$(record_field "$line" ALIAS)"
		for a in "${als[@]}"; do
			[ "$a" != "$1" ] || check_result "$E_EXISTS" "mail alias $1 already exists"
		done
	done < "$USER_DATA/mail/$domain.conf"
}

# Modes of a mail domain's certificate files: exim reads them through the mail group, dovecot as root, nobody else.
# Only this domain's SNI links: chmod on a glob over $MAIL_SNI_DIR followed every link and set the keys of every
# domain on the box to 644, and chown -h handed all links to the current user (#1135).
mail_ssl_modes() { # USER DOMAIN
	local d="$HOMEDIR/$1/conf/mail/$2/ssl" f sni
	sni=$(mail_sni_name "$2")
	chmod 0750 "$d"
	chown -R "$MAIL_USER:mail" "$d"
	for f in "$d"/*; do
		[ -e "$f" ] || continue
		chmod 0640 "$f"
		chown -h "$1:mail" "$f"
	done
	for f in "$MAIL_SNI_DIR/$sni.crt" "$MAIL_SNI_DIR/$sni.key" "$MAIL_SNI_DIR/mail.$sni.crt" "$MAIL_SNI_DIR/mail.$sni.key"; do
		[ -L "$f" ] && chown -h "$1:mail" "$f"
	done
	return 0
}

# SMTP relay credentials, in clear text for exim, so never with a world bit: box-wide 640 root:Debian-exim, per domain
# 660 Debian-exim:mail, the modes rebuild_mail_domain_conf gives every file of conf/mail/<domain>. cat > under the
# umask left both 644 (#1142).
smtp_relay_write() { # FILE OWNER MODE HOST PORT USER PASS
	local tmp
	tmp=$(mktemp "$1.XXXXXX") || return 1
	if printf 'host:%s\nport:%s\nuser:%s\npass:%s\n' "$4" "$5" "$6" "$7" > "$tmp" \
		&& chown "$2" "$tmp" && chmod "$3" "$tmp" && mv -f "$tmp" "$1"; then
		return 0
	fi
	rm -f "$tmp"
	return 1
}

# The addresses a mail domain sends from, as exim reads them: the web domain's, else the user's first. Needs ip.sh.
mail_outgoing_ip_write() { # USER DOMAIN
	local d="$HOMEDIR/$1/conf/mail/$2" v4 v6
	v4=$(get_object_value 'web' 'DOMAIN' "$2" '$IP')
	if [ -n "$v4" ]; then
		v4=$(get_real_ip "$v4")
	else
		v4=$(get_user_ips 4 | head -n1)
	fi
	v6=$(get_object_value 'web' 'DOMAIN' "$2" '$IP6')
	[ -n "$v6" ] || v6=$(get_user_ips 6 | head -n1)
	[ -n "$v6" ] && [ -e "$CONF_DIR/ips/$v6" ] || v6=''
	rm -f "$d/ip" "$d/ipv6"
	if [ -n "$v4" ]; then
		echo "$v4" > "$d/ip" && chown "${MAIL_USER:-Debian-exim}:mail" "$d/ip" && chmod 660 "$d/ip" || return 1
	fi
	if [ -n "$v6" ]; then
		echo "$v6" > "$d/ipv6" && chown "${MAIL_USER:-Debian-exim}:mail" "$d/ipv6" && chmod 660 "$d/ipv6" || return 1
	fi
	return 0
}

# Update 0.23: the relay files already on the box.
smtp_relay_modes_apply() {
	local f
	if [ -e /etc/exim4/smtp_relay.conf ]; then
		chown "root:${MAIL_USER:-Debian-exim}" /etc/exim4/smtp_relay.conf && chmod 640 /etc/exim4/smtp_relay.conf || return 1
	fi
	for f in "$HOMEDIR"/*/conf/mail/*/smtp_relay.conf; do
		[ -e "$f" ] || continue
		chown "${MAIL_USER:-Debian-exim}:mail" "$f" && chmod 660 "$f" || return 1
	done
	return 0
}

# Update 0.23: every mail certificate on the box, once, since each domain operation used to reset them all to 644.
mail_ssl_modes_apply() {
	local d rest
	for d in "$HOMEDIR"/*/conf/mail/*/ssl; do
		[ -e "$d" ] || continue
		rest=${d#"$HOMEDIR"/}
		rest=${rest%/ssl}
		mail_ssl_modes "${rest%%/*}" "${rest##*/}" || return 1
	done
}

# The name a client sends as SNI and dovecot's local_name: punycode for an IDN domain, while files keep the record's name.
mail_sni_name() { # DOMAIN
	if [[ "$1" = *[![:ascii:]]* ]]; then
		idn2 --quiet "$1"
	else
		printf '%s\n' "$1"
	fi
}

add_mail_ssl_config() {
	local sni
	sni=$(mail_sni_name "$domain")
	if [ ! -d "$HOMEDIR/$user/conf/mail/$domain/ssl/" ]; then
		mkdir -p $HOMEDIR/$user/conf/mail/$domain/ssl/
	fi

	if [ ! -d "$MAIL_SNI_DIR" ]; then
		mkdir -p $MAIL_SNI_DIR
	fi

	if [ ! -d /etc/dovecot/conf.d/domains ]; then
		mkdir -p /etc/dovecot/conf.d/domains
	fi

	# Add certificate to Hestia user configuration data directory
	if [ -f "$ssl_dir/$domain.crt" ]; then
		cp -f $ssl_dir/$domain.crt $USER_DATA/ssl/mail.$domain.crt
		cp -f $ssl_dir/$domain.key $USER_DATA/ssl/mail.$domain.key
		cp -f $ssl_dir/$domain.crt $USER_DATA/ssl/mail.$domain.pem
		if [ -e "$ssl_dir/$domain.ca" ]; then
			cp -f $ssl_dir/$domain.ca $USER_DATA/ssl/mail.$domain.ca
			echo >> $USER_DATA/ssl/mail.$domain.pem
			cat $USER_DATA/ssl/mail.$domain.ca >> $USER_DATA/ssl/mail.$domain.pem
		fi
	fi

	chmod 660 $USER_DATA/ssl/mail.$domain.*

	# Add certificate to user home directory
	cp -f $USER_DATA/ssl/mail.$domain.crt $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.crt
	cp -f $USER_DATA/ssl/mail.$domain.key $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key
	cp -f $USER_DATA/ssl/mail.$domain.pem $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem
	if [ -e "$USER_DATA/ssl/mail.$domain.ca" ]; then
		cp -f $USER_DATA/ssl/mail.$domain.ca $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.ca
	fi

	# Clean up dovecot configuration (if it exists)
	rm -f "/etc/dovecot/conf.d/domains/$domain.conf" "/etc/dovecot/conf.d/domains/$sni.conf"

	# Check if using custom / wildcard mail certificate
	wildcard_domain="\\*.$(echo "$sni" | cut -f 1 -d . --complement)"
	mail_cert_match=$($BIN/h-list-mail-domain-ssl $user $domain | awk '/SUBJECT|ALIASES/' | grep -wE " $sni| $wildcard_domain")

	if [ -n "$mail_cert_match" ]; then
		if dovecot_ge_24; then
			# Add domain SSL configuration to dovecot
			echo "" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "local_name $sni {" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "  ssl_server_cert_file = $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "  ssl_server_key_file = $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "}" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		else
			echo "" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "local_name $sni {" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "  ssl_cert = <$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "  ssl_key = <$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key" >> "/etc/dovecot/conf.d/domains/$sni.conf"
			echo "}" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		fi
		# Add domain SSL configuration to exim4
		ln -s $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem "$MAIL_SNI_DIR/$sni.crt"
		ln -s $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key "$MAIL_SNI_DIR/$sni.key"
	fi

	# Add domain SSL configuration to dovecot
	if dovecot_ge_24; then
		echo "" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "local_name mail.$sni {" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "  ssl_server_cert_file = $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "  ssl_server_key_file = $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "}" >> "/etc/dovecot/conf.d/domains/$sni.conf"
	else
		echo "" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "local_name mail.$sni {" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "  ssl_cert = <$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "  ssl_key = <$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key" >> "/etc/dovecot/conf.d/domains/$sni.conf"
		echo "}" >> "/etc/dovecot/conf.d/domains/$sni.conf"
	fi

	# Add domain SSL configuration to exim4
	ln -s $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem "$MAIL_SNI_DIR/mail.$sni.crt"
	ln -s $HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key "$MAIL_SNI_DIR/mail.$sni.key"

	mail_ssl_modes "$user" "$domain"
}

del_mail_ssl_config() {
	local sni
	sni=$(mail_sni_name "$domain")
	# Check to prevent accidental removal of mismatched certificate
	wildcard_domain="\\*.$(echo "$sni" | cut -f 1 -d . --complement)"
	mail_cert_match=$($BIN/h-list-mail-domain-ssl $user $domain | awk '/SUBJECT|ALIASES/' | grep -wE " $sni| $wildcard_domain")

	# Remove old mail certificates
	rm -f $HOMEDIR/$user/conf/mail/$domain/ssl/*

	# Remove dovecot configuration
	rm -f "/etc/dovecot/conf.d/domains/$domain.conf" "/etc/dovecot/conf.d/domains/$sni.conf"

	# Remove SSL certificates
	rm -f $HOMEDIR/$user/conf/mail/$domain/ssl/*
	if [ -n "$mail_cert_match" ]; then
		rm -f "$MAIL_SNI_DIR/$sni.crt" "$MAIL_SNI_DIR/$sni.key"
	fi
	rm -f "$MAIL_SNI_DIR/mail.$sni.crt" "$MAIL_SNI_DIR/mail.$sni.key" "$MAIL_SNI_DIR/mail.$domain.crt" "$MAIL_SNI_DIR/mail.$domain.key"
}

# A failed key leaves neither half behind, so nothing claims a DKIM it does not have.
mail_dkim_keys_write() { # DOMAIN SIZE
	local pem="$USER_DATA/mail/$1.pem" pub="$USER_DATA/mail/$1.pub"
	openssl genrsa -out "$pem" "$2" &> /dev/null && openssl rsa -pubout -in "$pem" -out "$pub" &> /dev/null && return 0
	rm -f "$pem" "$pub"
	return 1
}

del_mail_ssl_certificates() {
	rm -f $USER_DATA/ssl/mail.$domain.ca
	rm -f $USER_DATA/ssl/mail.$domain.crt
	rm -f $USER_DATA/ssl/mail.$domain.key
	rm -f $USER_DATA/ssl/mail.$domain.pem
	rm -f $HOMEDIR/$user/conf/mail/$domain/ssl/*
}

# Degrades to the 'disabled' vhost when the client is empty or not installed, so a rebuild after a
# webmailer is removed never leaves a dead proxy. Shared by the .tpl and .stpl callers.
select_webmail_template() {
	local client="$1" front
	front=$(webmail_front)
	if [ -z "$client" ] \
		|| { [ "$client" != "disabled" ] && ! grep -qw "$client" <<< "${WEBMAIL_SYSTEM//,/ }"; }; then
		client="disabled"
	fi
	if [ "$client" = "roundcube" ]; then
		WEBMAIL_TEMPLATE="default"
		[ "$front" = "nginx" ] && WEBMAIL_TEMPLATE="web_system"
		PROXY_TEMPLATE="default"
	elif [ -f "$HESTIA/share/$front/webmail/$client.tpl" ]; then
		WEBMAIL_TEMPLATE="$client"
		PROXY_TEMPLATE="default_$client"
	else
		WEBMAIL_TEMPLATE="disabled"
		PROXY_TEMPLATE="default_disabled"
	fi
}

add_webmail_config() {
	local front
	front=$(webmail_front)
	mkdir -p "$HOMEDIR/$user/conf/mail/$domain"
	conf="$HOMEDIR/$user/conf/mail/$domain/$1.conf"
	if [[ "$2" =~ stpl$ ]]; then
		conf="$HOMEDIR/$user/conf/mail/$domain/$1.ssl.conf"
	fi

	domain_idn=$domain
	format_domain_idn

	ssl_crt="$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.crt"
	ssl_key="$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.key"
	ssl_pem="$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.pem"
	ssl_ca="$HOMEDIR/$user/conf/mail/$domain/ssl/$domain.ca"

	# mail is a legal alias value, decided and not an oversight: the vhost otherwise carries the alias
	# name AND mail.<domain>, so with alias=mail the second name would just be the first one twice.
	override_alias=""
	if [ "$WEBMAIL_ALIAS" != "mail" ]; then
		override_alias="mail.$domain"
		override_alias_idn="mail.$domain_idn"
	fi

	# Derivation guard as in add_web_config: web domain's IP6, else default v6, else none;
	# derived values are unset at the end (process-wide variable).
	local _derived_ip6=0
	if [ -z "${local_ip6+x}" ]; then
		_derived_ip6=1
		local_ip6=$(get_object_value 'web' 'DOMAIN' "$domain" '$IP6')
		if [ -z "$local_ip6" ] && get_user_ip6; then
			local_ip6="$ip6"
		fi
		if [ -n "$local_ip6" ] && [ ! -e "$CONF_DIR/ips/$local_ip6" ]; then
			local_ip6=''
		fi
	fi

	# Divergent values: %domain% is the webmail alias, %web_system% the front
	local _r_ip="$local_ip" _r_ip6="$local_ip6" _r_domain="$WEBMAIL_ALIAS.$domain" _r_domain_idn="$WEBMAIL_ALIAS.$domain_idn" \
		_r_root_domain="$domain" _r_alias="$override_alias" _r_alias_idn="$override_alias_idn" \
		_r_web_system="$front" _r_vhost _r_vhost_ssl _r_backend_addr
	_r_vhost="$(web_vhost_addrs "$WEB_PORT")"
	_r_vhost_ssl="$(web_vhost_addrs "$WEB_SSL_PORT")"
	_r_backend_addr="$(web_backend_addr)"
	web_render_template < "$HESTIA/share/$1/webmail/$2" > $conf

	process_http2_directive "$conf"

	chown root:$user $conf
	chmod 640 $conf

	if [[ "$2" =~ stpl$ ]]; then
		# guard on $1 - the system whose conf.d receives the link - not on the front:
		# today they coincide in every model that reaches this line, but that is a
		# coincidence, not a meaning
		if [ -n "$1" ]; then
			conf_link_set "$conf" "/etc/$1/conf.d/domains/$WEBMAIL_ALIAS.$domain.ssl.conf"
		fi
		# no proxy link block here: it re-linked the same path, and the proxy side gets
		# its own add_webmail_config call with $1=$PROXY_SYSTEM at every call site

		# The forcessl file carries ITS OWN owner, independent of $1: the templates
		# include it by the name %web_system% renders to - the proxy where one
		# exists, the front otherwise. Empty owner = no file, not ".forcessl.conf".
		local fs_owner="${PROXY_SYSTEM:-$front}"
		if [ -n "$fs_owner" ]; then
			forcessl="$HOMEDIR/$user/conf/mail/$domain/$fs_owner.forcessl.conf"
			if [ -n "$PROXY_SYSTEM" ] || [ "$front" = 'nginx' ]; then
				echo 'return 301 https://$server_name$request_uri;' > $forcessl
			else
				echo 'RewriteEngine On' > $forcessl
				echo 'RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]' >> $forcessl
			fi
		fi

		# Remove old configurations
		find $HOMEDIR/$user/conf/mail/ -maxdepth 1 -type f \( -name "$domain.*" -o -name "ssl.$domain.*" -o -name "*nginx.$domain.*" \) -exec rm {} \;
	else
		if [ -n "$1" ]; then
			conf_link_set "$conf" "/etc/$1/conf.d/domains/$WEBMAIL_ALIAS.$domain.conf"
		fi
		# See the ssl branch: the former proxy block linked the same path twice.
		# Clear old configurations
		find $HOMEDIR/$user/conf/mail/ -maxdepth 1 -type f \( -name "$domain.*" \) -exec rm {} \;
	fi
	[ "$_derived_ip6" = 1 ] && unset -v local_ip6
	return 0
}

del_webmail_config() {
	local front
	front=$(webmail_front)
	if [ -n "$front" ]; then
		rm -f $HOMEDIR/$user/conf/mail/$domain/$front.conf
		conf_link_drop "/etc/$front/conf.d/domains/$WEBMAIL_ALIAS.$domain.conf" "$user"
	fi

	if [ -n "$PROXY_SYSTEM" ]; then
		rm -f $HOMEDIR/$user/conf/mail/$domain/$PROXY_SYSTEM.*conf
		conf_link_drop "/etc/$PROXY_SYSTEM/conf.d/domains/$WEBMAIL_ALIAS.$domain.conf" "$user"
	fi
}

del_webmail_ssl_config() {
	local front
	front=$(webmail_front)
	if [ -n "$front" ]; then
		rm -f $HOMEDIR/$user/conf/mail/$domain/$front.*ssl.conf
		conf_link_drop "/etc/$front/conf.d/domains/$WEBMAIL_ALIAS.$domain.ssl.conf" "$user"
	fi

	if [ -n "$PROXY_SYSTEM" ]; then
		rm -f $HOMEDIR/$user/conf/mail/$domain/$PROXY_SYSTEM.*ssl.conf
		conf_link_drop "/etc/$PROXY_SYSTEM/conf.d/domains/$WEBMAIL_ALIAS.$domain.ssl.conf" "$user"
	fi
}

# The hash for a mail password, in the strongest scheme this dovecot knows. A failed doveadm prints nothing, and an
# empty hash in passwd would be a login without a password.
mail_password_hash() { # PASSWORD
	local schemes scheme='' hash salt
	schemes=$(doveadm pw -l 2> /dev/null)
	if [[ " $schemes " == *" BLF-CRYPT "* ]]; then
		scheme=BLF-CRYPT
	elif [[ " $schemes " == *" ARGON2ID "* ]]; then
		scheme=ARGON2ID
	fi
	if [ -n "$scheme" ]; then
		# Twice on stdin as the prompt asks, -p would show it in the process list. A mismatch still prints a hash
		# with rc 0, so stderr goes into the result and the format check below refuses it.
		hash=$(printf '%s\n%s\n' "$1" "$1" | doveadm pw -s "$scheme" 2>&1)
	else
		salt=$(generate_password "$PW_MATRIX" "8")
		hash="{MD5}$($BIN/h-generate-password-hash md5 "$salt" <<< "$1")"
	fi
	[[ "$hash" =~ ^\{[A-Z0-9-]+\}[^[:space:]]{8,}$ ]] || return 1
	printf '%s\n' "$hash"
}

# dovecot 2.4 renamed the SSL and quota settings; every later version keeps the 2.4 names.
dovecot_ge_24() {
	local ver
	ver=$(dovecot --version 2> /dev/null | cut -d. -f1,2)
	[ -n "$ver" ] && [ "$(printf '%s\n2.4' "$ver" | sort -V | head -1)" = '2.4' ]
}

# The passwd line dovecot and exim read for an account, built here only: the quota syntax changed with dovecot 2.4.
mail_passwd_line() { # USER ACCOUNT HASH QUOTA
	local q="$4" rule
	[ "$q" != 'unlimited' ] && [ -n "$q" ] || q=0
	if dovecot_ge_24; then
		rule="userdb_quota_storage_size=${q}M"
	else
		rule="userdb_quota_rule=*:storage=${q}M"
	fi
	printf '%s\n' "$2:$3:$1:mail::$HOMEDIR/$1:$q:$rule"
}

# exim writes maildirsize through the mailbox's tmp/, which only the first INBOX delivery created: a mailbox whose
# first mail was spam had its Spam delivery deferred until then, and bounced if nothing else arrived.
mail_account_maildir_ensure() { # USER DOMAIN_IDN ACCOUNT
	local d="$HOMEDIR/$1/mail/$2/$3" sub
	mail_dir_trusted "$1" "$2" && [ ! -L "$d" ] || return 1
	[ -d "$d" ] || install -d -o "$1" -g mail -m 700 "$d" || return 1
	for sub in cur new tmp; do
		[ ! -L "$d/$sub" ] || return 1
		[ -d "$d/$sub" ] || install -d -o "$1" -g mail -m 770 "$d/$sub" || return 1
	done
}

# mail_dir_trusted USER [DOMAIN_IDN]: root may work below ~/mail. The customer owns /home/USER and the domain dir, so a
# link there, or a ~/mail of their own after renaming root's away, would hand root's chown to any target.
# Does not cover a swap between this check and the action that follows it.
mail_dir_trusted() {
	local m="$HOMEDIR/$1/mail"
	[ ! -L "$m" ] && [ -d "$m" ] && [ "$(stat -c %u "$m")" = 0 ] || return 1
	[ -z "$2" ] || { [ ! -L "$m/$2" ] && [ -d "$m/$2" ]; }
}

# exim's autoreply once-DB outlives the mailbox, so a recreated one stayed silent to earlier senders for 7 days.
# Without ACCOUNT it clears every mailbox of the domain. The names are exim's, see the userautoreply transport.
mail_autoreply_once_clear() { # DOMAIN_IDN [ACCOUNT]
	local db=/var/spool/exim4/db
	[ -n "$1" ] || return 0
	if [ -n "${2:-}" ]; then
		rm -f "$db/autoreply.$2@$1" "$db/autoreply.$2@$1.lockfile"
	else
		rm -f "$db/autoreply."*"@$1" "$db/autoreply."*"@$1.lockfile"
	fi
}

#----------------------------------------------------------#
#                        CMN                               #
#----------------------------------------------------------#

is_domain_new() {
	type=$1
	for object in ${2//,/ }; do
		if [ -n "$WEB_SYSTEM" ]; then
			is_web_domain_new $object $type
			is_web_alias_new $object $type
		fi
		if [ -n "$MAIL_SYSTEM" ]; then
			is_mail_domain_new $object $type
		fi
	done
}

get_domain_values() {
	parse_object_kv_list "$(grep -F "DOMAIN='$domain'" $USER_DATA/$1.conf)"
}

#----------------------------------------------------------#
# 2 Char domain name detection                             #
#----------------------------------------------------------#

is_valid_extension() {
	local psl
	psl="https://publicsuffix.org/list/public_suffix_list.dat"
	if [ ! -e "$CONF_DIR/public_suffix_list.dat" ]; then
		if /usr/bin/wget --tries=3 --timeout=15 --read-timeout=15 --waitretry=3 --no-dns-cache --quiet -O "$CONF_DIR/public_suffix_list.dat.tmp" "$psl"; then
			mv "$CONF_DIR/public_suffix_list.dat.tmp" "$CONF_DIR/public_suffix_list.dat"
		else
			rm -f "$CONF_DIR/public_suffix_list.dat.tmp"
		fi
	elif find "$CONF_DIR/public_suffix_list.dat" -mtime +7 2> /dev/null | grep -q .; then
		mv "$CONF_DIR/public_suffix_list.dat" "$CONF_DIR/public_suffix_list.dat.save"
		if /usr/bin/wget --tries=3 --timeout=15 --read-timeout=15 --waitretry=3 --no-dns-cache --quiet -O "$CONF_DIR/public_suffix_list.dat.tmp" "$psl"; then
			mv "$CONF_DIR/public_suffix_list.dat.tmp" "$CONF_DIR/public_suffix_list.dat"
			rm -f "$CONF_DIR/public_suffix_list.dat.save"
		else
			rm -f "$CONF_DIR/public_suffix_list.dat.tmp"
			mv "$CONF_DIR/public_suffix_list.dat.save" "$CONF_DIR/public_suffix_list.dat"
		fi
	fi
	if [ ! -e "$CONF_DIR/public_suffix_list.dat" ]; then
		check_result "$E_NOTEXIST" "public_suffix_list.dat not found"
	fi
	test_domain=$(idn2 -d "$1")
	extension="${test_domain##*.}"
	exten=$(grep -Fx "$extension" "$CONF_DIR/public_suffix_list.dat")
}

is_valid_2_part_extension() {
	local psl
	psl="https://publicsuffix.org/list/public_suffix_list.dat"
	if [ ! -e "$CONF_DIR/public_suffix_list.dat" ]; then
		if /usr/bin/wget --tries=3 --timeout=15 --read-timeout=15 --waitretry=3 --no-dns-cache --quiet -O "$CONF_DIR/public_suffix_list.dat.tmp" "$psl"; then
			mv "$CONF_DIR/public_suffix_list.dat.tmp" "$CONF_DIR/public_suffix_list.dat"
		else
			rm -f "$CONF_DIR/public_suffix_list.dat.tmp"
		fi
	elif find "$CONF_DIR/public_suffix_list.dat" -mtime +7 2> /dev/null | grep -q .; then
		mv "$CONF_DIR/public_suffix_list.dat" "$CONF_DIR/public_suffix_list.dat.save"
		if /usr/bin/wget --tries=3 --timeout=15 --read-timeout=15 --waitretry=3 --no-dns-cache --quiet -O "$CONF_DIR/public_suffix_list.dat.tmp" "$psl"; then
			mv "$CONF_DIR/public_suffix_list.dat.tmp" "$CONF_DIR/public_suffix_list.dat"
			rm -f "$CONF_DIR/public_suffix_list.dat.save"
		else
			rm -f "$CONF_DIR/public_suffix_list.dat.tmp"
			mv "$CONF_DIR/public_suffix_list.dat.save" "$CONF_DIR/public_suffix_list.dat"
		fi
	fi
	if [ ! -e "$CONF_DIR/public_suffix_list.dat" ]; then
		check_result "$E_NOTEXIST" "public_suffix_list.dat not found"
	fi
	test_domain=$(idn2 -d "$1")
	extension=$(/bin/echo "${test_domain}" | awk -F. '{print $(NF-1)"."$NF}')
	exten=$(grep -Fx "$extension" "$CONF_DIR/public_suffix_list.dat")
}

get_base_domain() {
	test_domain=$1
	is_valid_extension "$test_domain"
	if [ $? -ne 0 ]; then
		basedomain=$(/bin/echo "${test_domain}" | /usr/bin/rev | /usr/bin/cut -d "." --output-delimiter="." -f 1-2 | /usr/bin/rev)
	else
		is_valid_2_part_extension "$test_domain"
		if [ $? -ne 0 ]; then
			basedomain=$(/bin/echo "${test_domain}" | /usr/bin/rev | /usr/bin/cut -d "." --output-delimiter="." -f 1-2 | /usr/bin/rev)
		else
			extension=$(/bin/echo "${test_domain}" | /usr/bin/rev | /usr/bin/cut -d "." --output-delimiter="." -f 1-2 | /usr/bin/rev)
			partdomain=$(/bin/echo "${test_domain}" | /usr/bin/rev | /usr/bin/cut -d "." --output-delimiter="." -f 3 | /usr/bin/rev)
			basedomain="$partdomain.$extension"
		fi
	fi
}

# The base domain needs the public suffix list, which is fetched; without enforcement it is never looked at.
is_base_domain_owner() {
	[ "$ENFORCE_SUBDOMAIN_OWNERSHIP" = "yes" ] || return 0
	local object _list
	IFS=, read -ra _list <<< "$1"
	for object in "${_list[@]}"; do
		[ -n "$object" ] && [ "$object" != "none" ] || continue
		get_base_domain "$object"
		web=$(grep -F -H -h "DOMAIN='$basedomain'" "$CONF_DIR"/users/*/web.conf)
		if [ -n "$web" ]; then
			# Subshell: this is the PARENT's record - parsed in place, its keys (SSL, ...)
			# leaked into the caller's vhost rendering. Only ALLOW_USERS leaves this line.
			allow_users=$(
				parse_object_kv_list "$web" 2> /dev/null
				echo "${ALLOW_USERS:-}"
			)
			[ "$allow_users" = "yes" ] && continue
		fi
		# an existing $basedomain is fine as long as the current user owns it
		check=$(is_domain_new "" "$basedomain")
		if [ $? -ne 0 ]; then
			echo "Error: Unable to add $object. $basedomain belongs to a different user"
			exit 4
		fi
	done
}

#----------------------------------------------------------#
#           Process "http2" directive for NGINX            #
#----------------------------------------------------------#

# Rewrites by line number, never by matching the line's own text: a listen line contains
# [::] for IPv6, which is a character class in a sed pattern, so the line never matched
# itself and IPv6 vhosts silently kept the deprecated per-listener http2.
process_http2_directive() {
	if [ -e /etc/nginx/conf.d/http2-directive.conf ]; then
		while IFS= read -r lnr; do
			sed -i "${lnr}s/[[:space:]]http2//" "$1"
		done < <(grep -nE "listen.*(\bssl\b(\s|.+){1,}\bhttp2\b|\bhttp2\b(\s|.+){1,}\bssl\b).*;" "$1" | cut -f1 -d:)
	else
		# Fails closed: without the binary `nginx -v` prints an error carrying no '/', so cut
		# passes it through whole and sort -V ranks it above every real version - an apache-only
		# box would read as new nginx and write the marker into an /etc/nginx it does not have.
		local nginx_ver
		nginx_ver=$(nginx -v 2>&1 | cut -d'/' -f2)
		[[ "$nginx_ver" =~ ^[0-9]+\.[0-9]+ ]] || return 0
		# 1.25.1 is where nginx replaced the listen parameter with the http2 directive
		if version_ge "$nginx_ver" "1.25.1"; then
			echo "http2 on;" > /etc/nginx/conf.d/http2-directive.conf

			while IFS= read -r lnr; do
				sed -i "${lnr}s/[[:space:]]http2//" "$1"
			done < <(grep -nE "listen.*(\bssl\b(\s|.+){1,}\bhttp2\b|\bhttp2\b(\s|.+){1,}\bssl\b).*;" "$1" | cut -f1 -d:)
		else
			listen_ssl="$(grep -E "listen.*\s\bssl\b\s*.*;" "$1")"
			listen_http2="$(grep -E "listen.*(\bssl\b(\s|.+){1,}\bhttp2\b|\bhttp2\b(\s|.+){1,}\bssl\b).*;" "$1")"

			if [ -n "$listen_ssl" ] && [ -z "$listen_http2" ]; then
				while IFS= read -r lnr; do
					sed -i "${lnr}s/[[:space:]]ssl/ ssl http2/" "$1"
				done < <(grep -nE "listen.*\s\bssl\b\s*.*;" "$1" | cut -f1 -d:)
			fi
		fi
	fi
}
