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
	local ver arch sum
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
	ioncube_intact "$ver" && return 0
	# Asked again under the lock: another run may have fetched the same pin meanwhile.
	ioncube_lock -x || return 1
	if ioncube_intact "$ver"; then
		ioncube_unlock
		return 0
	fi
	ioncube_fetch_locked "$ver" "$arch" "$sum"
	local rc=$?
	ioncube_unlock
	return "$rc"
}

# The pin file alone would vouch for itself, and a loader damaged after the fetch would never be fetched again.
# The sums are taken from the verified archive at unpack time.
ioncube_intact() {
	[ "$(cat "$IONCUBE_PIN_FILE" 2> /dev/null)" = "$1" ] || return 1
	(cd "$IONCUBE_DIR" && sha256sum --quiet -c hestia-sums > /dev/null 2>&1)
}

# A pin change swaps IONCUBE_DIR (exclusive) while a panel h-add-web-php may be copying a loader out of it
# (shared). $1 = -x or -s.
ioncube_lock() {
	mkdir -p /run/hestia || return 1
	exec {IONCUBE_LOCK_FD}> /run/hestia/ioncube.lock || return 1
	# Long enough for a fetch that falls back to the mirror; a timeout is an error, never a go-ahead.
	flock "$1" -w 900 "$IONCUBE_LOCK_FD" && return 0
	echo "ERROR: the ionCube lock is held by another run"
	ioncube_unlock
	return 1
}

ioncube_unlock() {
	exec {IONCUBE_LOCK_FD}>&-
}

ioncube_fetch_locked() {
	local ver="$1" arch="$2" sum="$3" name tmp src ok=""
	name="ioncube_loaders_lin_${arch}_${ver}.tar.gz"
	# Next to the target, so both moves below are renames and not a copy across filesystems.
	mkdir -p "${IONCUBE_DIR%/*}" && tmp=$(mktemp -d "${IONCUBE_DIR%/*}/.ioncube.XXXXXX") || return 1
	# Each source is judged by the sum, not by wget: a portal or a stale cache answers 200 too. Bounded like
	# fetch_release_asset, wget's defaults cost ~45 minutes on a host that drops SYNs.
	for src in "$IONCUBE_URL" "$IONCUBE_MIRROR"; do
		wget "$src/$name" --timeout=30 --tries=3 --retry-connrefused --quiet -O "$tmp/$name" || continue
		if [ "$(sha256sum "$tmp/$name" | cut -d' ' -f1)" = "$sum" ]; then
			ok=yes
			break
		fi
		echo "WARNING: $src/$name does not match its pinned sha256"
	done
	if [ -z "$ok" ]; then
		echo "ERROR: no source delivered $name with its pinned sha256"
		rm -rf "$tmp"
		return 1
	fi
	# The archive ships its loaders group-writable and owned by uid "dev".
	if ! tar -xzf "$tmp/$name" -C "$tmp" --no-same-owner --no-same-permissions \
		|| ! chmod 0755 "$tmp/ioncube" || ! find "$tmp/ioncube" -type f -exec chmod 0644 {} + \
		|| ! (cd "$tmp/ioncube" && sha256sum ioncube_loader_lin_*.so > hestia-sums); then
		rm -rf "$tmp"
		return 1
	fi
	printf '%s\n' "$ver" > "$tmp/ioncube/hestia-pin" || {
		rm -rf "$tmp"
		return 1
	}
	# With a way back: a failed swap must not leave the box without the loaders it had.
	if [ -e "$IONCUBE_DIR" ] && ! mv "$IONCUBE_DIR" "$tmp/old"; then
		rm -rf "$tmp"
		return 1
	fi
	if ! mv "$tmp/ioncube" "$IONCUBE_DIR"; then
		[ -e "$tmp/old" ] && mv "$tmp/old" "$IONCUBE_DIR"
		rm -rf "$tmp"
		return 1
	fi
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
	ext=$(ioncube_ext_dir "$v") && [ -d "$ext" ] || return 1
	ioncube_lock -s || return 1
	if [ ! -f "$IONCUBE_DIR/ioncube_loader_lin_$v.so" ]; then
		ioncube_unlock
		return 3
	fi
	install -m 0644 "$IONCUBE_DIR/ioncube_loader_lin_$v.so" "$ext/ioncube.so" || {
		ioncube_unlock
		return 1
	}
	ioncube_unlock
	# JIT cannot run next to the loader's opcode handlers; with a buffer left, 8.4 warns on every FPM (re)load.
	printf '; priority=10\nzend_extension=ioncube.so\nopcache.jit_buffer_size=0\n' \
		> "/etc/php/$v/mods-available/ioncube.ini" || return 1
	phpenmod -v "$v" ioncube || return 1
	# A loader PHP refuses leaves every script of this version dead, so it goes straight back out. Both SAPIs:
	# FPM reads its own conf.d, and its reload is a USR2 that returns before the new master has loaded anything.
	if ! "/usr/bin/php$v" -v 2> /dev/null | grep -q 'ionCube' \
		|| ! "/usr/sbin/php-fpm$v" -v 2> /dev/null | grep -q 'ionCube'; then
		ioncube_version_remove "$v"
		return 1
	fi
	ioncube_fpm_reload "$v"
}

# Only a running master: one that was stopped on purpose stays stopped, and a purged version has none.
ioncube_fpm_reload() {
	systemctl is-active --quiet "php$1-fpm" || return 0
	systemctl reload "php$1-fpm" > /dev/null 2>&1
}

# The .so is ours, not a package's: a purge of the version leaves it behind unless it goes first.
ioncube_version_remove() {
	local v="$1" ext
	[ -f "/etc/php/$v/mods-available/ioncube.ini" ] && phpdismod -v "$v" ioncube
	rm -f "/etc/php/$v/mods-available/ioncube.ini"
	ext=$(ioncube_ext_dir "$v") && [ -n "$ext" ] && rm -f "$ext/ioncube.so"
	# The running master keeps a loaded loader until it reloads.
	ioncube_fpm_reload "$v"
}
