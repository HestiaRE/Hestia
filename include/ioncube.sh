#!/bin/bash

# ionCube loader (#1069). Loads before OPcache via priority 10; a new pin reaches the versions only through
# h-add-sys-ioncube, the fetch alone leaves every installed ioncube.so on the old loader.

IONCUBE_DIR="/usr/local/lib/ioncube"
IONCUBE_PIN_FILE="$IONCUBE_DIR/hestia-pin"
IONCUBE_URL="https://downloads.ioncube.com/loader_downloads"
# downloads.ioncube.com has no AAAA.
IONCUBE_MIRROR="https://hestiare.com/ioncube"

ioncube_arch() {
	case "$(dpkg --print-architecture 2> /dev/null)" in
		amd64) echo "x86-64" ;;
		arm64) echo "aarch64" ;;
		*) return 1 ;;
	esac
}

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
	# Again under the lock: a parallel run may have fetched it meanwhile.
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

# The payload, not just the pin file we wrote ourselves.
ioncube_intact() {
	[ "$(cat "$IONCUBE_PIN_FILE" 2> /dev/null)" = "$1" ] || return 1
	(cd "$IONCUBE_DIR" && sha256sum --quiet -c hestia-sums > /dev/null 2>&1)
}

# -x swaps IONCUBE_DIR, -s copies a loader out of it.
ioncube_lock() {
	mkdir -p /run/hestia || return 1
	exec {IONCUBE_LOCK_FD}> /run/hestia/ioncube.lock || return 1
	flock -n "$1" "$IONCUBE_LOCK_FD" && return 0
	# Said once, so a panel call stuck behind a slow download does not look hung.
	echo "Waiting for the ionCube lock held by another run..."
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
	# Left by a run killed mid-swap; nobody else can own one while we hold -x.
	rm -rf "${IONCUBE_DIR%/*}"/.ioncube.*
	# Same filesystem, so the swap below is two renames.
	mkdir -p "${IONCUBE_DIR%/*}" && tmp=$(mktemp -d "${IONCUBE_DIR%/*}/.ioncube.XXXXXX") || return 1
	# Judged by the sum, not by wget: a portal answers 200 too.
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
	# The archive ships group-writable files; modes last, so our two files do not follow the umask.
	if ! tar -xzf "$tmp/$name" -C "$tmp" --no-same-owner --no-same-permissions \
		|| ! (cd "$tmp/ioncube" && sha256sum ioncube_loader_lin_*.so > hestia-sums) \
		|| ! printf '%s\n' "$ver" > "$tmp/ioncube/hestia-pin" \
		|| ! chmod 0755 "$tmp/ioncube" || ! find "$tmp/ioncube" -type f -exec chmod 0644 {} +; then
		rm -rf "$tmp"
		return 1
	fi
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

# -n: a broken ioncube.ini must not break the lookup.
ioncube_ext_dir() {
	[ -x "/usr/bin/php$1" ] || return 1
	"/usr/bin/php$1" -n -r 'echo PHP_EXTENSION_DIR;' 2> /dev/null
}

# rc 3: ionCube ships no loader for this version (8.0).
ioncube_version_apply() {
	local rc
	ioncube_lock -s || return 1
	ioncube_version_apply_locked "$1"
	rc=$?
	ioncube_unlock
	return "$rc"
}

ioncube_version_apply_locked() {
	local v="$1" ext
	ext=$(ioncube_ext_dir "$v") && [ -d "$ext" ] || return 1
	[ -f "$IONCUBE_DIR/ioncube_loader_lin_$v.so" ] || return 3
	install -m 0644 "$IONCUBE_DIR/ioncube_loader_lin_$v.so" "$ext/ioncube.so" || return 1
	# JIT cannot run next to the loader anyway; with a buffer, 8.4 warns on every FPM reload.
	printf '; priority=10\nzend_extension=ioncube.so\nopcache.jit_buffer_size=0\n' \
		> "/etc/php/$v/mods-available/ioncube.ini" && chmod 0644 "/etc/php/$v/mods-available/ioncube.ini" || return 1
	phpenmod -v "$v" ioncube || return 1
	# FPM has its own conf.d, and its reload returns before the new master loads anything.
	if ! "/usr/bin/php$v" -v 2> /dev/null | grep -q 'ionCube' \
		|| ! "/usr/sbin/php-fpm$v" -v 2> /dev/null | grep -q 'ionCube'; then
		ioncube_version_remove "$v"
		return 1
	fi
	if ! ioncube_fpm_reload "$v"; then
		ioncube_version_remove "$v"
		return 1
	fi
}

# A master stopped on purpose stays stopped.
ioncube_fpm_reload() {
	systemctl is-active --quiet "php$1-fpm" || return 0
	systemctl reload "php$1-fpm" > /dev/null 2>&1
}

# The .so belongs to no package, so a purge would leave it behind.
ioncube_version_remove() {
	local v="$1" ext
	[ -f "/etc/php/$v/mods-available/ioncube.ini" ] && phpdismod -v "$v" ioncube
	rm -f "/etc/php/$v/mods-available/ioncube.ini"
	ext=$(ioncube_ext_dir "$v") && [ -n "$ext" ] && rm -f "$ext/ioncube.so"
	# rc is the reload's: the files are gone either way, a running master may still hold the loader.
	ioncube_fpm_reload "$v"
}
