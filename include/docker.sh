#!/bin/bash

# Shared companion lifecycle. The rootless daemon runs as its own account under
# user@<uid>.service with linger, so stopping it is more than one command and the
# order matters - callers that get it wrong leave processes holding the home.
# Every function here takes the CUSTOMER, never the companion account: the naming
# convention lives in companion_name and nowhere else.

# Usage: companion_name <user>   ->  the customer's companion account name
companion_name() {
	echo "${1}-docker"
}

# Stop a customer's companion and wait for its processes to go.
# Usage: companion_stop <user>  ->  1 if anything is still running, so a caller that is
# about to write into the companion home can refuse. Linger is NOT touched: that is an
# autostart property, not part of stopping, and only the delete path wants it gone.
companion_stop() {
	local companion companion_uid _
	companion="$(companion_name "$1")"
	companion_uid="$(id -u "$companion" 2> /dev/null)" || return 0
	[ -n "$companion_uid" ] || return 0
	if [ "$(id -u)" != '0' ]; then
		echo "ERROR: stopping the companion needs root" >&2
		return 1
	fi

	loginctl terminate-user "$companion" > /dev/null 2>&1
	systemctl stop "user@${companion_uid}.service" "user-runtime-dir@${companion_uid}.service" > /dev/null 2>&1
	for _ in $(seq 1 20); do
		pgrep -u "$companion_uid" > /dev/null 2>&1 || break
		sleep 1
	done
	pkill -KILL -u "$companion_uid" > /dev/null 2>&1
	sleep 1
	# A KILL does not clear an uninterruptible process, and "stopped" has to mean stopped -
	# the whole point of the caller stopping first is that nothing writes to the home.
	if pgrep -u "$companion_uid" > /dev/null 2>&1; then
		echo "ERROR: companion $companion still has processes ($(pgrep -u "$companion_uid" | tr '\n' ' '))" >&2
		return 1
	fi
	return 0
}

# Autostart, separate from stopping: the delete path wants it gone, a restore does not.
# Usage: companion_disable_linger <user>
companion_disable_linger() {
	loginctl disable-linger "$(companion_name "$1")" > /dev/null 2>&1
	return 0
}

# Preset -> systemd properties for a companion slice. Percentages are native systemd syntax
# (of physical RAM, and 100% CPUQuota is one core), so a package value maps 1:1 with no maths.
# Usage: docker_slice_properties <unlimited|low|medium|high>  ->  1 on an unknown preset,
# which must not read as "no cap": a typo would silently uncap the customer.
docker_slice_properties() {
	case "$1" in
		unlimited | '') echo "" ;;
		low) echo "MemoryMax=10% CPUQuota=50% TasksMax=512" ;;
		medium) echo "MemoryMax=25% CPUQuota=100% TasksMax=1024" ;;
		high) echo "MemoryMax=50% CPUQuota=200% TasksMax=2048" ;;
		*) return 1 ;;
	esac
}

# Cap the companion slice, which is where the daemon AND every container of that customer live -
# their own user slice never sees them. Enforced against the customer's own daemon, so no compose
# file can talk its way out. A missing companion is not an error: docker is simply not enabled.
# Usage: docker_slice_apply <user> <preset>
docker_slice_apply() {
	local user="$1" preset="${2:-unlimited}" companion_uid slice props prop name shown
	companion_uid="$(id -u "$(companion_name "$user")" 2> /dev/null)" || return 0
	[ -n "$companion_uid" ] || return 0
	slice="user-${companion_uid}.slice"

	if ! props="$(docker_slice_properties "$preset")"; then
		echo "Warning: unknown docker resource preset '$preset' for $user - slice left as it is" >&2
		return 1
	fi
	if [ -z "$props" ]; then
		systemctl set-property "$slice" MemoryMax= CPUQuota= TasksMax= > /dev/null 2>&1
		return 0
	fi
	# shellcheck disable=SC2086  # each property is its own argument on purpose
	systemctl set-property "$slice" $props > /dev/null 2>&1
	for prop in $props; do
		name="${prop%%=*}"
		# CPUQuota is write-only: systemd reports it back as CPUQuotaPerSecUSec
		[ "$name" = 'CPUQuota' ] && name='CPUQuotaPerSecUSec'
		shown="$(systemctl show "$slice" -p "$name" --value 2> /dev/null)"
		case "$shown" in
			'' | infinity) echo "Warning: ${prop%%=*} did not take on $slice" >&2 ;;
		esac
	done
	return 0
}

# Drop a companion's cap and the drop-in systemd persisted for it. The uid is reused by a later
# customer's companion, and an inherited limit with no visible cause is worse than no limit.
# Usage: docker_slice_clear <user>
docker_slice_clear() {
	local companion_uid
	companion_uid="$(id -u "$(companion_name "$1")" 2> /dev/null)" || return 0
	[ -n "$companion_uid" ] || return 0
	systemctl set-property "user-${companion_uid}.slice" MemoryMax= CPUQuota= TasksMax= > /dev/null 2>&1
	rm -rf "/etc/systemd/system.control/user-${companion_uid}.slice.d"
	systemctl daemon-reload > /dev/null 2>&1
	return 0
}

# Customers with a companion account, one per line.
companion_users() {
	local name
	while IFS=: read -r name _; do
		[ "${name%-docker}" != "$name" ] && [ -f "$CONF_DIR/users/${name%-docker}/user.conf" ] && echo "${name%-docker}"
	done < <(getent passwd)
	return 0
}

# The companion's lines in /etc/subuid and /etc/subgid in the order newuidmap maps them (helper ids for 1-999, the
# customer at 1000, the rest after), and a running daemon mapping 1000 to the customer. useradd sorts both files
# when it hands out a range of its own, and the next daemon start then maps the customer at 1.
# Usage: companion_map_ok <user>  ->  1 on drift
companion_map_ok() {
	local companion f id lines pid
	companion="$(companion_name "$1")"
	pid="$(pgrep -u "$companion" -x dockerd 2> /dev/null | head -1)"
	for f in uid gid; do
		id="$(id -"${f:0:1}" "$1" 2> /dev/null)" || return 1
		lines="$(awk -F: -v c="$companion" '$1 == c { printf "%s:%s ", $2, $3 }' "/etc/sub$f")"
		[[ "$lines" =~ ^([0-9]+):999\ ([0-9]+):1\ ([0-9]+):64536\ $ ]] || return 1
		[ "${BASH_REMATCH[2]}" = "$id" ] && [ "${BASH_REMATCH[3]}" = "$((BASH_REMATCH[1] + 999))" ] || return 1
		[ -z "$pid" ] || [ "$(awk '$1 == 1000 { print $2 ":" $3 }' "/proc/$pid/${f}_map" 2> /dev/null)" = "$id:1" ] \
			|| return 1
	done
	return 0
}

# The companion's three lines back in the order newuidmap needs, at the end of each file. 1 when they are not the
# three h-add-user-docker writes: only a re-run of it knows the values then.
# Usage: companion_subid_order <user>
companion_subid_order() {
	local companion f tmp rc=0
	companion="$(companion_name "$1")"
	for f in /etc/subuid /etc/subgid; do
		tmp="$(mktemp)" || return 1
		if awk -F: -v c="$companion" '
			$1 != c { print; next }
			$3 == 999 && a == "" { a = $0; next }
			$3 == 1 && b == "" { b = $0; next }
			$3 == 64536 && r == "" { r = $0; next }
			{ bad = 1 }
			END { if (bad || a == "" || b == "" || r == "") exit 1; print a; print b; print r }' "$f" > "$tmp"; then
			cat "$tmp" > "$f" || rc=1
		else
			rc=1
		fi
		rm -f "$tmp"
	done
	return "$rc"
}

# Update entry: every companion's lines back in order, and a daemon running on the wrong map restarted through
# h-add-user-docker, which brings its containers back. A suspended customer's daemon is stopped and stays so.
docker_companion_map_apply() {
	local u
	while IFS= read -r u; do
		[ -n "$u" ] || continue
		companion_map_ok "$u" && continue
		if ! companion_subid_order "$u"; then
			echo "Warning: the subordinate ids of $(companion_name "$u") are not as h-add-user-docker writes them," \
				"run h-add-user-docker $u" >&2
			continue
		fi
		companion_map_ok "$u" && continue
		echo "restarting the docker daemon of $u on the corrected id map"
		"$BIN/h-add-user-docker" "$u" > /dev/null 2>&1 || echo "Warning: h-add-user-docker $u failed, run it by hand" >&2
	done <<< "$(companion_users)"
	return 0
}
