#!/bin/bash

#===========================================================================#
#                                                                           #
# HestiaRE: ionCube Loader (#1069)                                          #
#                                                                           #
# The loader archive pinned in share/manifest.json (software_versions.      #
# ioncube + ioncube_sha256 per arch), kept unpacked in IONCUBE_DIR so a PHP #
# version added later gets its loader without a download. Each customer     #
# version gets <extension_dir>/ioncube.so and mods-available/ioncube.ini at #
# priority 10: a zend_extension ionCube must load before OPcache, and       #
# 10-ioncube sorts before 10-opcache. The panel pool builds its conf.d from #
# a curated list (sbin/hestia-php-confd) and never loads it.                #
#                                                                           #
#===========================================================================#

IONCUBE_DIR="/usr/local/lib/ioncube"
# Holds the pin the unpacked archive came from; pin_differs in the update manifest reads it.
IONCUBE_PIN_FILE="$IONCUBE_DIR/hestia-pin"
IONCUBE_URL="https://downloads.ioncube.com/loader_downloads"
# downloads.ioncube.com has no AAAA. Byte-identical copies, verified against the same pin.
IONCUBE_MIRROR="https://hestiare.com/ioncube"

# ionCube's archive names, not dpkg's.
ioncube_arch() {
	case "$(dpkg --print-architecture 2> /dev/null)" in
		amd64) echo "x86-64" ;;
		arm64) echo "aarch64" ;;
		*) return 1 ;;
	esac
}

# Fetch, verify and unpack the pinned archive into IONCUBE_DIR. rc 0 also when it is already there.
ioncube_fetch() {
	local ver arch sum name tmp
	ver=$(manifest_get '.software_versions.ioncube')
	arch=$(ioncube_arch) || {
		echo "ERROR: no ionCube loader for architecture $(dpkg --print-architecture 2> /dev/null)"
		return 1
	}
	sum=$(manifest_get ".software_versions.ioncube_sha256[\"$arch\"]")
	{ [ -n "$ver" ] && [ "$ver" != null ] && [ -n "$sum" ] && [ "$sum" != null ]; } || {
		echo "ERROR: share/manifest.json carries no ionCube pin for $arch"
		return 1
	}
	[ "$(cat "$IONCUBE_PIN_FILE" 2> /dev/null)" = "$ver" ] && return 0
	name="ioncube_loaders_lin_${arch}_${ver}.tar.gz"
	tmp=$(mktemp -d) || return 1
	# Bounded like fetch_release_asset: wget's defaults cost ~45 minutes on a host that drops SYNs.
	if ! wget "$IONCUBE_URL/$name" --timeout=30 --tries=3 --retry-connrefused --quiet -O "$tmp/$name" \
		|| ! [ -s "$tmp/$name" ]; then
		wget "$IONCUBE_MIRROR/$name" --timeout=30 --tries=3 --retry-connrefused --quiet -O "$tmp/$name"
	fi
	if [ "$(sha256sum "$tmp/$name" 2> /dev/null | cut -d' ' -f1)" != "$sum" ]; then
		echo "ERROR: $name missing or not matching its pinned sha256"
		rm -rf "$tmp"
		return 1
	fi
	# The archive ships its loaders group-writable and owned by uid "dev".
	tar -xzf "$tmp/$name" -C "$tmp" --no-same-owner --no-same-permissions || {
		rm -rf "$tmp"
		return 1
	}
	chmod 0755 "$tmp/ioncube" && find "$tmp/ioncube" -type f -exec chmod 0644 {} + || {
		rm -rf "$tmp"
		return 1
	}
	printf '%s\n' "$ver" > "$tmp/ioncube/hestia-pin"
	rm -rf "$IONCUBE_DIR"
	mkdir -p "${IONCUBE_DIR%/*}"
	mv "$tmp/ioncube" "$IONCUBE_DIR" || {
		rm -rf "$tmp"
		return 1
	}
	rm -rf "$tmp"
}

# -n: a broken ioncube.ini from an earlier run must not decide where the extension dir is.
ioncube_ext_dir() {
	[ -x "/usr/bin/php$1" ] || return 1
	"/usr/bin/php$1" -n -r 'echo PHP_EXTENSION_DIR;' 2> /dev/null
}

# rc 0 enabled, rc 1 failed, rc 3 no loader exists for this version (8.0): the caller says so and goes on.
ioncube_version_apply() {
	local v="$1" ext
	[ -f "$IONCUBE_DIR/ioncube_loader_lin_$v.so" ] || return 3
	ext=$(ioncube_ext_dir "$v") && [ -d "$ext" ] || return 1
	install -m 0644 "$IONCUBE_DIR/ioncube_loader_lin_$v.so" "$ext/ioncube.so" || return 1
	printf '; priority=10\nzend_extension=ioncube.so\n' > "/etc/php/$v/mods-available/ioncube.ini" || return 1
	phpenmod -v "$v" ioncube || return 1
	# A loader PHP refuses leaves every script of this version dead, so it goes straight back out.
	if ! "/usr/bin/php$v" -v 2> /dev/null | grep -q 'ionCube'; then
		ioncube_version_remove "$v"
		return 1
	fi
	systemctl reload-or-restart "php$v-fpm" > /dev/null 2>&1 || true
}

# The .so is ours, not a package's: a purge of the version leaves it behind unless it goes first.
ioncube_version_remove() {
	local v="$1" ext
	[ -f "/etc/php/$v/mods-available/ioncube.ini" ] && phpdismod -v "$v" ioncube
	rm -f "/etc/php/$v/mods-available/ioncube.ini"
	ext=$(ioncube_ext_dir "$v") && rm -f "$ext/ioncube.so"
	return 0
}
