#!/bin/bash

#===========================================================================#
#                                                                           #
# Hestia Control Panel - Domain Function Library                            #
#                                                                           #
#===========================================================================#

# Global
database_set_default_ports() {

	# Set default ports for MySQL and PostgreSQL
	mysql_default="3306"
	pgsql_default="5432"

	# Handle missing values for both $PORT and $port
	# however don't override both at once or custom ports will be overridden.

	if [ -z "$PORT" ]; then
		if [ "$type" = 'mysql' ]; then
			PORT="$mysql_default"
		fi
		if [ "$type" = 'pgsql' ]; then
			PORT="$pgsql_default"
		fi
	fi
	if [ -z "$port" ]; then
		if [ "$type" = 'mysql' ]; then
			port="$mysql_default"
		fi
		if [ "$type" = 'pgsql' ]; then
			port="$pgsql_default"
		fi
	fi
}

# MySQL
# mysql_cnf_sync: $mycnf for the host just parsed. It carries the admin password, so it is 0600 from its
# creation; rewritten when the stored password changed.
mysql_cnf_sync() {
	mycnf="$HESTIA/conf/.mysql.$HOST"
	if [ ! -e "$mycnf" ] || [ "$(grep password "$mycnf" | cut -f 2 -d \')" != "$PASSWORD" ]; then
		(
			umask 077
			printf "[client]\nhost='%s'\nuser='%s'\npassword='%s'\nport='%s'\n" \
				"$HOST" "$USER" "$PASSWORD" "${PORT:-3306}" > "$mycnf"
			chmod 600 "$mycnf"
		)
	fi
}

mysql_connect() {
	unset PORT
	host_str=$(grep "HOST='$1'" $HESTIA/conf/mysql.conf)
	parse_object_kv_list "$host_str"
	if [ -z $PORT ]; then PORT=3306; fi
	if [ -z $HOST ] || [ -z $USER ] || [ -z $PASSWORD ]; then
		echo "Error: mysql config parsing failed"
		log_event "$E_PARSING" "$ARGUMENTS"
		exit $E_PARSING
	fi
	mysql_cnf_sync
	mysql_out=$(mktemp)
	if [ -f '/usr/bin/mariadb' ]; then
		mariadb --defaults-file=$mycnf -e 'SELECT VERSION()' > $mysql_out 2>&1
	else
		mysql --defaults-file=$mycnf -e 'SELECT VERSION()' > $mysql_out 2>&1
	fi
	if [ '0' -ne "$?" ]; then
		if [ "$notify" != 'no' ]; then
			email=$(grep CONTACT "$CONF_DIR/users/$ROOT_USER/user.conf" | cut -f 2 -d \')
			subj="MySQL connection error on $(hostname)"
			echo -e "Can't connect to MySQL $HOST:$PORT\n$(cat $mysql_out)" \
				| $SENDMAIL -s "$subj" $email
		fi
		rm -f $mysql_out
		echo "Error: Connection to $HOST failed"
		log_event "$E_CONNECT" "$ARGUMENTS"
		exit $E_CONNECT
	fi
	mysql_ver=$(cat $mysql_out | tail -n1 | cut -f 1 -d -)
	mysql_fork="mysql"
	check_mysql_fork=$(grep "MariaDB" $mysql_out)
	if [ "$check_mysql_fork" ]; then
		mysql_fork="mariadb"
	fi
	rm -f $mysql_out
}

# escape a value for a MariaDB/MySQL single-quoted string literal (GHSA-8w7m):
# double backslashes first, then single quotes (backslash is an escape char here)
mysql_sql_escape() {
	printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e "s/'/''/g"
}

# escape a value for a PostgreSQL single-quoted string literal (standard-conforming
# strings: backslash is NOT an escape char, so only quotes are doubled)
sql_escape() {
	printf '%s' "$1" | sed "s/'/''/g"
}

mysql_query() {
	sql_tmp=$(mktemp)
	echo "$1" > $sql_tmp
	if [ -f '/usr/bin/mariadb' ]; then
		mariadb --defaults-file=$mycnf < "$sql_tmp" 2> /dev/null
		return_code=$?
	else
		mysql --defaults-file=$mycnf < "$sql_tmp" 2> /dev/null
		return_code=$?
	fi
	rm -f "$sql_tmp"
	return $return_code
}

mysql_dump() {
	local err
	err=$(mktemp)
	mysqldmp="mysqldump"
	if [ -f '/usr/bin/mariadb-dump' ]; then
		mysqldmp="/usr/bin/mariadb-dump"
	fi
	$mysqldmp --defaults-file=$mycnf --single-transaction --routines -r $1 $2 2> $err
	if [ '0' -ne "$?" ]; then
		$mysqldmp --defaults-extra-file=$mycnf --single-transaction --routines -r $1 $2 2> $err
		if [ '0' -ne "$?" ]; then
			rm -rf $tmpdir
			if [ "$notify" != 'no' ]; then
				email=$(grep CONTACT "$CONF_DIR/users/$ROOT_USER/user.conf" | cut -f 2 -d \')
				subj="MySQL error on $(hostname)"
				echo -e "Can't dump database $database\n$(cat $err)" \
					| $SENDMAIL -s "$subj" $email
			fi
			rm -f "$err"
			echo "Error: dump $database failed"
			log_event "$E_DB" "$ARGUMENTS"
			exit "$E_DB"
		fi
	fi
	rm -f "$err"
}

# PostgreSQL

# psql_env HOST [TLS] - the connection settings every psql of this layer shares.
# A remote host must speak TLS with a trusted certificate (#980), else libpq falls back to plaintext unasked; the
# bundle, as libpq 15 (Debian 12) lacks sslrootcert=system. PGDATABASE: libpq would take the admin's name instead.
psql_env() {
	export PGDATABASE=postgres
	case "$1" in
		'' | localhost | 127.* | ::1 | /*) unset PGSSLMODE PGSSLROOTCERT ;;
		*)
			if [ "$2" = 'no' ]; then
				export PGSSLMODE=prefer
				unset PGSSLROOTCERT
			else
				export PGSSLMODE=verify-full PGSSLROOTCERT=/etc/ssl/certs/ca-certificates.crt
			fi
			;;
	esac
}

psql_connect() {
	unset PORT TLS
	host_str=$(grep "HOST='$1'" $HESTIA/conf/pgsql.conf)
	parse_object_kv_list "$host_str"
	export PGPASSWORD="$PASSWORD"
	psql_env "$HOST" "$TLS"
	if [ -z $PORT ]; then PORT=5432; fi
	if [ -z $HOST ] || [ -z $USER ] || [ -z $PASSWORD ] || [ -z $TPL ]; then
		echo "Error: postgresql config parsing failed"
		log_event "$E_PARSING" "$ARGUMENTS"
		exit $E_PARSING
	fi

	local err
	err=$(mktemp)
	psql -h $HOST -U $USER -p $PORT -c "SELECT VERSION()" > /dev/null 2> "$err"
	if [ '0' -ne "$?" ]; then
		if [ "$notify" != 'no' ]; then
			email=$(grep CONTACT "$CONF_DIR/users/$ROOT_USER/user.conf" | cut -f 2 -d \')
			subj="PostgreSQL connection error on $(hostname)"
			echo -e "Can't connect to PostgreSQL $HOST:$PORT\n$(cat "$err")" \
				| $SENDMAIL -s "$subj" $email
		fi
		rm -f "$err"
		echo "Error: Connection to $HOST failed"
		log_event "$E_CONNECT" "$ARGUMENTS"
		exit "$E_CONNECT"
	fi
	rm -f "$err"
}

psql_query() {
	local rc
	sql_tmp=$(mktemp)
	echo "$1" > $sql_tmp
	# ON_ERROR_STOP: without it psql exits 0 on a failed statement.
	psql -h $HOST -U $USER -p $PORT -v ON_ERROR_STOP=1 -f "$sql_tmp" 2> /dev/null
	rc=$?
	rm -f $sql_tmp
	return $rc
}

# psql_value QUERY - the value of a one-column, one-row SELECT, nothing else.
#
# Not psql_query: its heading, ruler and row-count footer are a display format, and the footer is
# translated. Reading values out of it cost every pgsql password, then the pgsql size on a German
# box. Nothing parses that output any more; keep it that way. -tAX drops heading, padding, .psqlrc.
psql_value() {
	local _tmp
	_tmp=$(mktemp)
	echo "$1" > "$_tmp"
	psql -h "$HOST" -U "$USER" -p "$PORT" -tAX -f "$_tmp" 2> /dev/null | head -n1
	rm -f "$_tmp"
}

# psql_owner_apply DATABASE ROLE - hand every object to the role the record names.
#
# The dump carries no owner (-O) and imports run as the admin role, so without this the customer
# cannot read a restored database (#1113). Enumerated, not REASSIGN OWNED BY, which would also take
# the admin's extensions; what another object carries (a serial's sequence) follows it and is skipped.
# NOT covered: extensions, event triggers, large objects, default privileges, and grants to other
# roles, which -x drops on purpose: a grant names a cluster-wide role with no record behind it.
psql_owner_apply() {
	local _db="$1" _role="$2" _tmp _err _rc
	if [ -z "$_db" ] || [ -z "$_role" ]; then
		echo "Warning!: no owner could be applied, the database or the role was not named"
		return 1
	fi
	_tmp=$(mktemp) || return 1
	_err=$(mktemp) || {
		rm -f "$_tmp"
		return 1
	}
	cat > "$_tmp" << 'SQL'
SET client_min_messages = warning;
ALTER DATABASE :"db" OWNER TO :"role";
SELECT format('ALTER %s %I.%I OWNER TO %I;',
		CASE c.relkind WHEN 'S' THEN 'SEQUENCE' WHEN 'v' THEN 'VIEW'
			WHEN 'm' THEN 'MATERIALIZED VIEW' ELSE 'TABLE' END,
		n.nspname, c.relname, :'role')
	FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
	WHERE c.relkind IN ('r', 'p', 'S', 'v', 'm', 'f')
		AND n.nspname NOT IN ('pg_catalog', 'information_schema') AND n.nspname NOT LIKE 'pg\_%'
		AND NOT EXISTS (SELECT 1 FROM pg_depend d WHERE d.classid = 'pg_class'::regclass
			AND d.objid = c.oid AND d.deptype IN ('a', 'i', 'e'))
		AND pg_get_userbyid(c.relowner) <> :'role'
\gexec
SELECT format('ALTER SCHEMA %I OWNER TO %I;', n.nspname, :'role')
	FROM pg_namespace n
	WHERE n.nspname NOT IN ('pg_catalog', 'information_schema', 'public')
		AND n.nspname NOT LIKE 'pg\_%'
		AND pg_get_userbyid(n.nspowner) <> :'role'
\gexec
SELECT format('ALTER ROUTINE %I.%I(%s) OWNER TO %I;',
		n.nspname, p.proname, pg_get_function_identity_arguments(p.oid), :'role')
	FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
	WHERE n.nspname NOT IN ('pg_catalog', 'information_schema') AND n.nspname NOT LIKE 'pg\_%'
		AND NOT EXISTS (SELECT 1 FROM pg_depend d WHERE d.classid = 'pg_proc'::regclass
			AND d.objid = p.oid AND d.deptype IN ('a', 'i', 'e'))
		AND pg_get_userbyid(p.proowner) <> :'role'
\gexec
SELECT format('ALTER %s %I.%I OWNER TO %I;',
		CASE t.typtype WHEN 'd' THEN 'DOMAIN' ELSE 'TYPE' END, n.nspname, t.typname, :'role')
	FROM pg_type t JOIN pg_namespace n ON n.oid = t.typnamespace
	WHERE t.typtype IN ('c', 'e', 'r', 'd')
		AND (t.typrelid = 0 OR (SELECT c.relkind FROM pg_class c WHERE c.oid = t.typrelid) = 'c')
		AND NOT EXISTS (SELECT 1 FROM pg_type e WHERE e.typarray = t.oid)
		AND n.nspname NOT IN ('pg_catalog', 'information_schema') AND n.nspname NOT LIKE 'pg\_%'
		AND NOT EXISTS (SELECT 1 FROM pg_depend d WHERE d.classid = 'pg_type'::regclass
			AND d.objid = t.oid AND d.deptype IN ('a', 'i', 'e'))
		AND pg_get_userbyid(t.typowner) <> :'role'
\gexec
SQL
	# Without ON_ERROR_STOP psql exits 0 after a failed statement.
	psql -h "$HOST" -U "$USER" -p "$PORT" -d "$_db" -v ON_ERROR_STOP=1 -v db="$_db" -v role="$_role" \
		-tAXq -f "$_tmp" > /dev/null 2> "$_err"
	_rc=$?
	if [ "$_rc" -ne 0 ]; then
		echo "Warning!: $_db was not handed to $_role, the customer has no rights to the data: $(head -n1 "$_err")"
	fi
	rm -f "$_tmp" "$_err"
	# The handover ends a suspension, so a suspended record gets it back. Read from the record, not
	# $SUSPENDED: the host line overwrites that.
	if [ "$_rc" -eq 0 ] && [ "$(record_field "$(grep -F "DB='$_db'" "$USER_DATA/db.conf")" SUSPENDED)" = 'yes' ]; then
		psql_suspend_apply "$_db" "$_role" || {
			echo "Warning!: $_db is suspended, but the suspension was not applied again"
			_rc=1
		}
	fi
	return "$_rc"
}

psql_dump() {
	local err
	err=$(mktemp)
	pg_dump -h $HOST -U $USER -p $PORT -c --inserts -O -x -f $1 $2 2> "$err"
	if [ '0' -ne "$?" ]; then
		rm -rf $tmpdir
		if [ "$notify" != 'no' ]; then
			email=$(grep CONTACT "$CONF_DIR/users/$ROOT_USER/user.conf" | cut -f 2 -d \')
			subj="PostgreSQL error on $(hostname)"
			echo -e "Can't dump database $database\n$(cat "$err")" \
				| $SENDMAIL -s "$subj" $email
		fi
		rm -f "$err"
		echo "Error: dump $database failed"
		log_event "$E_DB" "$ARGUMENTS"
		exit "$E_DB"
	fi
	rm -f "$err"
}

# Get database host
get_next_dbhost() {
	if [ -z "$host" ] || [ "$host" == 'default' ]; then
		IFS=$'\n'
		host='EMPTY_DB_HOST'
		config="$HESTIA/conf/$type.conf"
		host_str=$(grep "SUSPENDED='no'" $config)
		check_row=$(echo "$host_str" | wc -l)

		if [ 0 -lt "$check_row" ]; then
			if [ 1 -eq "$check_row" ]; then
				for db in $host_str; do
					parse_object_kv_list "$db"
					if [ "$MAX_DB" -gt "$U_DB_BASES" ]; then
						host=$HOST
					fi
				done
			else
				old_weight='100'
				for db in $host_str; do
					parse_object_kv_list "$db"
					let weight="$U_DB_BASES * 100 / $MAX_DB" > /dev/null 2>&1
					if [ "$old_weight" -gt "$weight" ]; then
						host="$HOST"
						old_weight="$weight"
					fi
				done
			fi
		fi
	fi
}

# Database charset validation
is_charset_valid() {
	host_str=$(grep "HOST='$host'" $HESTIA/conf/$type.conf)
	parse_object_kv_list "$host_str"

	if [ -z "$(echo $CHARSETS | grep -wi $charset)" ]; then
		echo "Error: charset $charset not exist"
		log_event "$E_NOTEXIST" "$ARGUMENTS"
		exit $E_NOTEXIST
	fi
}

# Increase database host value
increase_dbhost_values() {
	host_str=$(grep "HOST='$host'" $HESTIA/conf/$type.conf)
	parse_object_kv_list "$host_str"

	old_dbbases="U_DB_BASES='$U_DB_BASES'"
	new_dbbases="U_DB_BASES='$((U_DB_BASES + 1))'"
	if [ -z "$U_SYS_USERS" ]; then
		old_users="U_SYS_USERS=''"
		new_users="U_SYS_USERS='$user'"
	else
		old_users="U_SYS_USERS='$U_SYS_USERS'"
		new_users="U_SYS_USERS='$U_SYS_USERS'"
		if [ -z "$(echo $U_SYS_USERS | sed "s/,/\n/g" | grep -w $user)" ]; then
			old_users="U_SYS_USERS='$U_SYS_USERS'"
			new_users="U_SYS_USERS='$U_SYS_USERS,$user'"
		fi
	fi

	sed -i "s/$old_dbbases/$new_dbbases/g" $HESTIA/conf/$type.conf
	sed -i "s/$old_users/$new_users/g" $HESTIA/conf/$type.conf
}

# Decrease database host value
decrease_dbhost_values() {
	host_str=$(grep "HOST='$HOST'" $HESTIA/conf/$TYPE.conf)
	parse_object_kv_list "$host_str"

	old_dbbases="U_DB_BASES='$U_DB_BASES'"
	new_dbbases="U_DB_BASES='$((U_DB_BASES - 1))'"
	old_users="U_SYS_USERS='$U_SYS_USERS'"
	U_SYS_USERS=$(echo "$U_SYS_USERS" \
		| sed "s/,/\n/g" \
		| sed "s/^$user$//g" \
		| sed "/^$/d" \
		| sed ':a;N;$!ba;s/\n/,/g')
	new_users="U_SYS_USERS='$U_SYS_USERS'"

	sed -i "s/$old_dbbases/$new_dbbases/g" $HESTIA/conf/$TYPE.conf
	sed -i "s/$old_users/$new_users/g" $HESTIA/conf/$TYPE.conf
}

# mysql_read_md5 DBUSER: the hash the server keeps for DBUSER, into $md5, in the form each fork prints it.
mysql_read_md5() {
	mysql_ver_sub=$(echo $mysql_ver | cut -d '.' -f1)
	mysql_ver_sub_sub=$(echo $mysql_ver | cut -d '.' -f2)
	if [ "$mysql_fork" = "mysql" ]; then
		# mysql
		if [ "$mysql_ver_sub" -ge 8 ] || { [ "$mysql_ver_sub" -eq 5 ] && [ "$mysql_ver_sub_sub" -ge 7 ]; }; then
			if [ "$mysql_ver_sub" -ge 8 ]; then
				# mysql >= 8

				md5=$(mysql_query "SET print_identified_with_as_hex=ON; SHOW CREATE USER \`$1\`" 2> /dev/null)

				if [[ "$md5" =~ 0x([^ ]+) ]]; then
					md5=$(echo "$md5" | grep password | grep -E -o '0x([^ ]+)')
				else
					md5=$(echo "$md5" | grep password | cut -f4 -d \')
				fi
			else
				# mysql < 8
				md5=$(mysql_query "SHOW CREATE USER \`$1\`" 2> /dev/null)
				md5=$(echo "$md5" | grep password | cut -f8 -d \')
			fi
		else
			# mysql < 5.7
			md5=$(mysql_query "SHOW GRANTS FOR \`$1\`" 2> /dev/null)
			md5=$(echo "$md5" | grep PASSW | tr ' ' '\n' | tail -n1 | cut -f 2 -d \')
		fi
	else
		# mariadb
		md5=$(mysql_query "SHOW GRANTS FOR \`$1\`" 2> /dev/null)
		md5=$(echo "$md5" | grep PASSW | tr ' ' '\n' | tail -n1 | cut -f 2 -d \')
	fi
}

# Create MySQL database
add_mysql_database() {
	mysql_connect $host

	mysql_ver_sub=$(echo $mysql_ver | cut -d '.' -f1)
	mysql_ver_sub_sub=$(echo $mysql_ver | cut -d '.' -f2)

	# Checked before the database exists. A reused user must be there; a new one must not, or the GRANT below
	# would take it over with this password, another customer's included.
	if [ -n "${reuse:-}" ]; then
		mysql_user_exists "$dbuser" || check_result "$E_NOTEXIST" "database user $dbuser does not exist on $host"
	elif mysql_user_exists "$dbuser"; then
		check_result "$E_EXISTS" "DBUSER=$dbuser already exists"
	fi

	query="CREATE DATABASE \`$database\` CHARACTER SET $charset"
	mysql_query "$query"
	check_result $? "Unable to create database $database"

	# A reused user keeps its password, and its hash stays in the record that created it.
	if [ -n "${reuse:-}" ]; then
		mysql_query "GRANT ALL ON \`$database\`.* TO \`$dbuser\`@\`%\`" > /dev/null
		mysql_query "GRANT ALL ON \`$database\`.* TO \`$dbuser\`@localhost" > /dev/null
		md5=''
		return 0
	fi

	dbpass_esc=$(mysql_sql_escape "$dbpass")

	if [ "$mysql_fork" = "mysql" ] && [ "$mysql_ver_sub" -ge 8 ]; then
		query="CREATE USER \`$dbuser\`@\`%\`
            IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null

		query="CREATE USER \`$dbuser\`@localhost
            IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null

		query="GRANT ALL ON \`$database\`.* TO \`$dbuser\`@\`%\`"
		mysql_query "$query" > /dev/null

		query="GRANT ALL ON \`$database\`.* TO \`$dbuser\`@localhost"
		mysql_query "$query" > /dev/null
	else
		query="GRANT ALL ON \`$database\`.* TO \`$dbuser\`@\`%\`
            IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null

		query="GRANT ALL ON \`$database\`.* TO \`$dbuser\`@localhost
            IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null
	fi

	mysql_read_md5 "$dbuser"
}

# Create PostgreSQL database
add_pgsql_database() {
	psql_connect $host

	# Checked before anything is created: the GRANT below would hand an existing database or role to this customer.
	! pgsql_object_exists role "$dbuser" || check_result "$E_EXISTS" "DBUSER=$dbuser already exists"
	! pgsql_object_exists database "$database" || check_result "$E_EXISTS" "database $database already exists"

	dbpass_esc=$(sql_escape "$dbpass")
	query="CREATE ROLE $dbuser WITH LOGIN PASSWORD '$dbpass_esc'"
	psql_query "$query" > /dev/null
	check_result $? "Unable to create database user $dbuser"

	query="CREATE DATABASE $database OWNER $dbuser"
	if [ "$TPL" = 'template0' ]; then
		query="$query ENCODING '$charset' TEMPLATE $TPL"
	else
		query="$query TEMPLATE $TPL"
	fi
	if ! psql_query "$query" > /dev/null; then
		psql_query "DROP ROLE $dbuser" > /dev/null
		check_result "$E_DB" "Unable to create database $database"
	fi

	query="GRANT ALL PRIVILEGES ON DATABASE $database TO $dbuser"
	psql_query "$query" > /dev/null

	query="GRANT CONNECT ON DATABASE template1 to $dbuser"
	psql_query "$query" > /dev/null

	query="SELECT rolpassword FROM pg_authid WHERE rolname='$dbuser'"
	md5=$(psql_value "$query")
}

add_mysql_database_temp_user() {
	mysql_connect $host

	mysql_ver_sub=$(echo $mysql_ver | cut -d '.' -f1)
	mysql_ver_sub_sub=$(echo $mysql_ver | cut -d '.' -f2)

	dbpass_esc=$(mysql_sql_escape "$dbpass")

	if [ "$mysql_fork" = "mysql" ] && [ "$mysql_ver_sub" -ge 8 ]; then
		query="CREATE USER \`$dbuser\`@localhost
			IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null

		query="GRANT ALL ON \`$database\`.* TO \`$dbuser\`@localhost"
		mysql_query "$query" > /dev/null
	else
		query="GRANT ALL ON \`$database\`.* TO \`$dbuser\`@localhost
    		IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null
	fi
}

delete_mysql_database_temp_user() {
	mysql_connect $host
	query="REVOKE ALL ON \`$database\`.* FROM \`$dbuser\`@localhost"
	mysql_query "$query" > /dev/null
	query="DROP USER '$dbuser'@'localhost'"
	mysql_query "$query" > /dev/null
}

# Check if database host do not exist in config
is_dbhost_new() {
	if [ -e "$HESTIA/conf/$type.conf" ]; then
		check_host=$(grep "HOST='$host'" $HESTIA/conf/$type.conf)
		if [ "$check_host" ]; then
			echo "Error: db host exist"
			log_event "$E_EXISTS" "$ARGUMENTS"
			exit $E_EXISTS
		fi
	fi
}

# Get database values
get_database_values() {
	# A record from before #725 has no slot-2 keys, so a loop over databases would carry the last one's over.
	DBUSER_SECOND='' MD5_SECOND='' DBUSER_SECOND_RO=''
	parse_object_kv_list "$(grep -F "DB='$database'" $USER_DATA/db.conf)"
}

# Change MySQL database password
change_mysql_password() {
	mysql_connect $HOST

	mysql_ver_sub=$(echo $mysql_ver | cut -d '.' -f1)
	mysql_ver_sub_sub=$(echo $mysql_ver | cut -d '.' -f2)

	dbpass_esc=$(mysql_sql_escape "$dbpass")

	if [ "$mysql_fork" = "mysql" ]; then
		# mysql
		if [ "$mysql_ver_sub" -ge 8 ]; then
			# mysql >= 8
			query="SET PASSWORD FOR \`$DBUSER\`@\`%\` = '$dbpass_esc'"
			mysql_query "$query" > /dev/null
			query="SET PASSWORD FOR \`$DBUSER\`@localhost = '$dbpass_esc'"
			mysql_query "$query" > /dev/null
		else
			# mysql < 8
			query="GRANT ALL ON \`$database\`.* TO \`$DBUSER\`@\`%\`
                  IDENTIFIED BY '$dbpass_esc'"
			mysql_query "$query" > /dev/null

			query="GRANT ALL ON \`$database\`.* TO \`$DBUSER\`@localhost
                  IDENTIFIED BY '$dbpass_esc'"
			mysql_query "$query" > /dev/null
		fi
	else
		# mariadb
		query="GRANT ALL ON \`$database\`.* TO \`$DBUSER\`@\`%\`
              IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null

		query="GRANT ALL ON \`$database\`.* TO \`$DBUSER\`@localhost
              IDENTIFIED BY '$dbpass_esc'"
		mysql_query "$query" > /dev/null
	fi

	mysql_read_md5 "$DBUSER"
}

# Change PostgreSQL database password
change_pgsql_password() {
	psql_connect $HOST
	dbpass_esc=$(sql_escape "$dbpass")
	query="ALTER ROLE $DBUSER WITH LOGIN PASSWORD '$dbpass_esc'"
	psql_query "$query" > /dev/null

	query="SELECT rolpassword FROM pg_authid WHERE rolname='$DBUSER'"
	md5=$(psql_value "$query")
}

# db_is_owned_by_user DB - is this database one of the customer whose data directory is in play?
#
# Asked by the delete functions themselves, not by their callers. On the restore path the name in
# $database is the one out of the ARCHIVE, so a restore under a different customer name dropped the
# SOURCE customer's live database on the same box - and reported success. A guard placed in the
# callers is missing again at the next caller.
db_is_owned_by_user() {
	[ -n "$1" ] || return 1
	# Field-anchored and literal. A record's first field is DB='<name>', so matching it whole holds
	# on its own rather than on the quoting around it happening to bound the match; -F because the
	# name can come out of an archive, where a regex metacharacter would widen it.
	cut -d' ' -f1 "$USER_DATA/db.conf" 2> /dev/null | grep -qxF "DB='$1'"
}

# db_record_field LINE KEY: one field of a db.conf record, exact: a grep for DBUSER='x' also hits X_DBUSER='x'.
db_record_field() {
	[[ " $1" =~ \ $2=\'([^\']*)\' ]] && printf '%s' "${BASH_REMATCH[1]}"
}

# db_user_in_use DBUSER TYPE HOST [SELF_DB] [IGNORE_DBS]: does another record of this customer hold DBUSER in a
# slot? TYPE/HOST '*' match any. rc 0 in use, 1 free, 2 cannot tell; callers drop a user only on 1, so a doubt keeps
# it. SELF_DB must be seen: a file without the caller's own record is not the one it thinks it is reading.
db_user_in_use() {
	local u="$1" type="$2" host="$3" self="${4:-}" ignore=" ${5:-} " line db seen='' found=''
	[ -n "$u" ] && [ -r "$USER_DATA/db.conf" ] || return 2
	while IFS= read -r line || [ -n "$line" ]; do
		db=$(db_record_field "$line" DB)
		if [ -n "$self" ] && [ "$db" = "$self" ]; then
			seen=yes
			continue
		fi
		[[ "$ignore" == *" $db "* ]] && continue
		db_record_matches "$line" "$type" "$host" || continue
		if [ "$(db_record_field "$line" DBUSER)" = "$u" ] || [ "$(db_record_field "$line" DBUSER_SECOND)" = "$u" ]; then
			found=yes
		fi
	done < "$USER_DATA/db.conf"
	[ -z "$self" ] || [ -n "$seen" ] || return 2
	[ -n "$found" ]
}

# db_user_hash_elsewhere DBUSER TYPE HOST SELF_DB [IGNORE_DBS]: does a record other than SELF_DB, outside
# IGNORE_DBS, carry DBUSER's hash? Its password is then the one the user has on the server.
db_user_hash_elsewhere() {
	local ignore=" ${5:-} " line db
	[ -n "$1" ] && [ -r "$USER_DATA/db.conf" ] || return 1
	while IFS= read -r line || [ -n "$line" ]; do
		db=$(db_record_field "$line" DB)
		[ "$db" != "$4" ] && [[ "$ignore" != *" $db "* ]] || continue
		db_record_matches "$line" "$2" "$3" || continue
		if [ "$(db_record_field "$line" DBUSER)" = "$1" ] && [ -n "$(db_record_field "$line" MD5)" ]; then return 0; fi
		if [ "$(db_record_field "$line" DBUSER_SECOND)" = "$1" ] && [ -n "$(db_record_field "$line" MD5_SECOND)" ]; then return 0; fi
	done < "$USER_DATA/db.conf"
	return 1
}

# db_user_foreign DBUSER USER: does another customer's record hold DBUSER in a slot? Database users are server-wide,
# and the customer prefix does not keep names apart: customer a with b_x and customer a_b with x are both a_b_x.
db_user_foreign() {
	local conf line
	for conf in "$CONF_DIR"/users/*/db.conf; do
		[ -e "$conf" ] || continue
		[ "$conf" != "$CONF_DIR/users/$2/db.conf" ] || continue
		while IFS= read -r line || [ -n "$line" ]; do
			if [ "$(db_record_field "$line" DBUSER)" = "$1" ] || [ "$(db_record_field "$line" DBUSER_SECOND)" = "$1" ]; then
				return 0
			fi
		done < "$conf"
	done
	return 1
}

# db_name_foreign DB USER: does another customer's record hold the database name DB? rc 2 when USER's own directory
# was not among those read: a set without it is not the one this was asked about. Records only, the server's own
# names are pgsql_object_exists' and mysql_user_exists' part.
db_name_foreign() {
	local dir seen=''
	for dir in "$CONF_DIR"/users/*/; do
		[ -e "$dir" ] || continue
		if [ "$(basename "$dir")" = "$2" ]; then
			seen=yes
			continue
		fi
		[ -e "$dir/db.conf" ] || continue
		cut -d' ' -f1 "$dir/db.conf" | grep -qxF "DB='$1'" && return 0
	done
	[ -n "$seen" ] || return 2
	return 1
}

# db_namespace_foreign NAME USER: is the pgsql NAME inside the name space of another customer? A name belongs to
# the longest customer name that prefixes it with '_': a_b_x is customer a_b's, so customer a may not create it.
# rc 2 as in db_name_foreign.
db_namespace_foreign() {
	local dir other seen=''
	for dir in "$CONF_DIR"/users/*/; do
		[ -e "$dir" ] || continue
		other=$(basename "$dir")
		[ "$other" != "$2" ] || seen=yes
		[ "${#other}" -gt "${#2}" ] || continue
		[[ "${1,,}" != "${other,,}_"* ]] || return 0
	done
	[ -n "$seen" ] || return 2
	return 1
}

# db_names_free DB DBUSER TYPE USER: 0 only when DB and DBUSER are free of every rule above; a doubt (rc 2) is not free.
db_names_free() {
	db_name_foreign "$1" "$4"
	[ $? -eq 1 ] || return 1
	[ "$3" = 'pgsql' ] || return 0
	db_namespace_foreign "$1" "$4"
	[ $? -eq 1 ] || return 1
	db_namespace_foreign "$2" "$4"
	[ $? -eq 1 ]
}

# db_namespace_taken USER: does a shorter customer already hold pgsql names in USER's name space? Asked before USER
# exists, the same rule as db_namespace_foreign from the other side.
db_namespace_taken() {
	local dir other line key val
	for dir in "$CONF_DIR"/users/*/; do
		[ -e "$dir" ] || continue
		other=$(basename "$dir")
		[ "${#other}" -lt "${#1}" ] && [[ "${1,,}" == "${other,,}_"* ]] || continue
		[ -e "$dir/db.conf" ] || continue
		while IFS= read -r line || [ -n "$line" ]; do
			[ "$(db_record_field "$line" TYPE)" = 'pgsql' ] || continue
			for key in DB DBUSER DBUSER_SECOND; do
				val=$(db_record_field "$line" "$key")
				[[ -z "$val" || "${val,,}" != "${1,,}_"* ]] || return 0
			done
		done < "$dir/db.conf"
	done
	return 1
}

# pgsql_object_exists KIND NAME: the server's own answer (KIND database or role), which also knows names no record has.
# Lowercased: the names are created unquoted, and pgsql folds those.
pgsql_object_exists() {
	case "$1" in
		database) [ -n "$(psql_value "SELECT 1 FROM pg_database WHERE datname='${2,,}'")" ] ;;
		role) [ -n "$(psql_value "SELECT 1 FROM pg_roles WHERE rolname='${2,,}'")" ] ;;
		*) return 2 ;;
	esac
}

# mysql_user_exists DBUSER: the server's own answer, which also knows users no record names any more.
mysql_user_exists() {
	[ "$(mysql_query "SELECT COUNT(*) FROM mysql.user WHERE User='$1'" | tail -n1)" != '0' ]
}

db_record_matches() {
	{ [ "$2" = '*' ] || [ "$(db_record_field "$1" TYPE)" = "$2" ]; } \
		&& { [ "$3" = '*' ] || [ "$(db_record_field "$1" HOST)" = "$3" ]; }
}

# db_user_host DBUSER TYPE: the HOST of the records holding DBUSER; a shared user lives on exactly one server.
db_user_host() {
	local line
	[ -n "$1" ] && [ -r "$USER_DATA/db.conf" ] || return 1
	while IFS= read -r line || [ -n "$line" ]; do
		db_record_matches "$line" "$2" '*' || continue
		if [ "$(db_record_field "$line" DBUSER)" = "$1" ] || [ "$(db_record_field "$line" DBUSER_SECOND)" = "$1" ]; then
			db_record_field "$line" HOST
			return 0
		fi
	done < "$USER_DATA/db.conf"
	return 1
}

# db_user_canonical DBUSER TYPE HOST: "DB KEY" of the slot carrying DBUSER's hash (KEY is MD5 or MD5_SECOND). rc 1 none,
# rc 2 more than one: two records claiming one password is a state no command may build on.
db_user_canonical() {
	local line hit=''
	[ -n "$1" ] && [ -r "$USER_DATA/db.conf" ] || return 1
	while IFS= read -r line || [ -n "$line" ]; do
		db_record_matches "$line" "$2" "$3" || continue
		if [ "$(db_record_field "$line" DBUSER)" = "$1" ] && [ -n "$(db_record_field "$line" MD5)" ]; then
			[ -z "$hit" ] || return 2
			hit="$(db_record_field "$line" DB) MD5"
		fi
		if [ "$(db_record_field "$line" DBUSER_SECOND)" = "$1" ] && [ -n "$(db_record_field "$line" MD5_SECOND)" ]; then
			[ -z "$hit" ] || return 2
			hit="$(db_record_field "$line" DB) MD5_SECOND"
		fi
	done < "$USER_DATA/db.conf"
	[ -n "$hit" ] || return 1
	echo "$hit"
}

# Read-only (#725): no EXECUTE, DEFINER routines run with their creator's rights; no LOCK TABLES, a reader could stall
# the app. One REVOKE per privilege, so a name this server version does not know fails alone.
MYSQL_WRITE_PRIVS=(INSERT UPDATE DELETE CREATE DROP REFERENCES INDEX ALTER 'CREATE TEMPORARY TABLES' 'LOCK TABLES'
	EXECUTE 'CREATE VIEW' 'CREATE ROUTINE' 'ALTER ROUTINE' EVENT TRIGGER 'DELETE HISTORY' 'SHOW CREATE ROUTINE')

# mysql_db_privs DBUSER DB HOST: what DBUSER@HOST holds on DB, worded as SHOW GRANTS words it.
mysql_db_privs() {
	mysql_query "SHOW GRANTS FOR \`$1\`@\`$3\`" | sed -n "s/^GRANT \(.*\) ON \`$2\`\.\* TO .*/\1/p"
}

# mysql_grant_slot DBUSER DB RO: RO 'yes' read-only, else full rights. GRANT before REVOKE keeps SELECT through the
# switch; rc 1 unless SHOW GRANTS reads back exactly that.
mysql_grant_slot() {
	local h p grant='ALL' want='ALL PRIVILEGES'
	if [ "$3" = 'yes' ]; then
		grant='SELECT, SHOW VIEW'
		want='SELECT, SHOW VIEW'
	fi
	for h in '%' localhost; do
		mysql_query "GRANT $grant ON \`$2\`.* TO \`$1\`@\`$h\`" > /dev/null
		if [ "$3" = 'yes' ]; then
			for p in "${MYSQL_WRITE_PRIVS[@]}"; do
				mysql_query "REVOKE $p ON \`$2\`.* FROM \`$1\`@\`$h\`" > /dev/null
			done
		fi
		[ "$(mysql_db_privs "$1" "$2" "$h")" = "$want" ] || return 1
	done
}

# mysql_revoke_slot DBUSER DB
mysql_revoke_slot() {
	mysql_query "REVOKE ALL ON \`$2\`.* FROM \`$1\`@\`%\`" > /dev/null
	mysql_query "REVOKE ALL ON \`$2\`.* FROM \`$1\`@localhost" > /dev/null
}

# mysql_drop_user_if_free DBUSER [SELF_DB]: drops DBUSER once no record of the customer holds it in a slot.
mysql_drop_user_if_free() {
	db_user_in_use "$1" mysql "$HOST" "${2:-}"
	[ $? -eq 1 ] || return 0
	mysql_query "DROP USER '$1'@'%'" > /dev/null
	mysql_query "DROP USER '$1'@'localhost'" > /dev/null
}

# mysql_create_user DBUSER DBPASS: a new user with no rights yet; its hash lands in $md5.
mysql_create_user() {
	local pass_esc
	pass_esc=$(mysql_sql_escape "$2")
	mysql_query "CREATE USER \`$1\`@\`%\` IDENTIFIED BY '$pass_esc'" > /dev/null || return 1
	mysql_query "CREATE USER \`$1\`@localhost IDENTIFIED BY '$pass_esc'" > /dev/null || return 1
	mysql_read_md5 "$1"
	[ -n "$md5" ]
}

# mysql_set_password DBUSER DBPASS: both hosts, no grant touched; the new hash lands in $md5.
mysql_set_password() {
	local pass_esc
	pass_esc=$(mysql_sql_escape "$2")
	mysql_query "ALTER USER \`$1\`@\`%\` IDENTIFIED BY '$pass_esc'" > /dev/null || return 1
	mysql_query "ALTER USER \`$1\`@localhost IDENTIFIED BY '$pass_esc'" > /dev/null || return 1
	mysql_read_md5 "$1"
	[ -n "$md5" ]
}

# Delete MySQL database
delete_mysql_database() {
	local database="${1:-$database}"
	if ! db_is_owned_by_user "$database"; then
		echo "Error: $database is not a database of $user - refusing to drop it"
		return 1
	fi
	mysql_connect $HOST

	# IF EXISTS: a record whose database is already gone must stay deletable.
	query="DROP DATABASE IF EXISTS \`$database\`"
	mysql_query "$query" || return 2

	query="REVOKE ALL ON \`$database\`.* FROM \`$DBUSER\`@\`%\`"
	mysql_query "$query" > /dev/null

	query="REVOKE ALL ON \`$database\`.* FROM \`$DBUSER\`@localhost"
	mysql_query "$query" > /dev/null

	db_user_in_use "$DBUSER" mysql "$HOST" "$database"
	if [ $? -eq 1 ]; then
		query="DROP USER '$DBUSER'@'%'"
		mysql_query "$query" > /dev/null

		query="DROP USER '$DBUSER'@'localhost'"
		mysql_query "$query" > /dev/null
	fi
	if [ -n "${DBUSER_SECOND:-}" ]; then
		mysql_revoke_slot "$DBUSER_SECOND" "$database"
		mysql_drop_user_if_free "$DBUSER_SECOND" "$database"
	fi
	# Explicit: 1 is the guard, 2 the DROP; otherwise the status is whatever the last REVOKE gave.
	return 0
}

# Delete PostgreSQL database
delete_pgsql_database() {
	local database="${1:-$database}"
	if ! db_is_owned_by_user "$database"; then
		echo "Error: $database is not a database of $user - refusing to drop it"
		return 1
	fi
	psql_connect $HOST

	# No REVOKE first: a DROP refused for an open connection would leave the owner locked out.
	query="DROP DATABASE IF EXISTS $database"
	psql_query "$query" > /dev/null || return 2

	db_user_in_use "$DBUSER" pgsql "$HOST" "$database"
	if [ $? -eq 1 ]; then
		query="REVOKE CONNECT ON DATABASE template1 FROM $DBUSER"
		psql_query "$query" > /dev/null
		query="DROP ROLE $DBUSER"
		psql_query "$query" > /dev/null
	fi
	# Explicit, for the same reason as on the mysql side.
	return 0
}

# Dump MySQL database
dump_mysql_database() {
	mysql_connect $HOST

	mysql_dump $dump $database

	query="SHOW GRANTS FOR '$DBUSER'@'localhost'"
	mysql_query "$query" | grep -v "Grants for" > $grants

	query="SHOW GRANTS FOR '$DBUSER'@'%'"
	mysql_query "$query" | grep -v "Grants for" > $grants
}

# Dump PostgreSQL database
dump_pgsql_database() {
	psql_connect $HOST

	psql_dump $dump $database

	query="SELECT rolpassword FROM pg_authid WHERE rolname='$DBUSER'"
	md5=$(psql_value "$query")
	pw_str="UPDATE pg_authid SET rolpassword='$md5' WHERE rolname='$DBUSER'"
	gr_str="GRANT ALL PRIVILEGES ON DATABASE $database to '$DBUSER'"
	echo -e "$pw_str\n$gr_str" >> $grants
}

# Check if database server is in use
is_dbhost_free() {
	host_str=$(grep "HOST='$host'" $HESTIA/conf/$type.conf)
	parse_object_kv_list "$host_str"
	if [ 0 -ne "$U_DB_BASES" ]; then
		echo "Error: host $HOST is used"
		log_event "$E_INUSE" "$ARGUMENTS"
		exit $E_INUSE
	fi
}

# Suspend MySQL database
suspend_mysql_database() {
	mysql_connect $HOST
	mysql_revoke_slot "$DBUSER" "$database"
	[ -z "${DBUSER_SECOND:-}" ] || mysql_revoke_slot "$DBUSER_SECOND" "$database"
}

# Suspend PostgreSQL database
suspend_pgsql_database() {
	psql_connect $HOST
	psql_suspend_apply "$database" "$DBUSER"
}

# psql_suspend_apply DATABASE ROLE: the role owns the database and could grant itself back in, so the
# database goes to the admin first. Its tables stay the role's, which is all unsuspend has to undo.
psql_suspend_apply() {
	psql_query "BEGIN;
ALTER DATABASE $1 OWNER TO $USER;
REVOKE ALL ON DATABASE $1 FROM $2, PUBLIC;
COMMIT;
SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$1' AND usename = '$2';" > /dev/null
}

# Unsuspend MySQL database
unsuspend_mysql_database() {
	mysql_connect $HOST
	mysql_grant_slot "$DBUSER" "$database" '' || echo "Warning!: $DBUSER did not get its rights on $database back"
	if [ -n "${DBUSER_SECOND:-}" ]; then
		mysql_grant_slot "$DBUSER_SECOND" "$database" "${DBUSER_SECOND_RO:-}" \
			|| echo "Warning!: $DBUSER_SECOND did not get its rights on $database back"
	fi
}

# Unsuspend PostgreSQL database
unsuspend_pgsql_database() {
	psql_connect $HOST
	# PUBLIC gets back the CONNECT and TEMP a new database has, so the state is the one before the suspend.
	psql_query "BEGIN;
ALTER DATABASE $database OWNER TO $DBUSER;
GRANT ALL ON DATABASE $database TO $DBUSER;
GRANT CONNECT, TEMPORARY ON DATABASE $database TO PUBLIC;
COMMIT;" > /dev/null
}

# Get MySQL disk usage
get_mysql_disk_usage() {
	mysql_connect $HOST
	query="SELECT SUM( data_length + index_length ) / 1024 / 1024 'Size'
        FROM information_schema.TABLES WHERE table_schema='$database'"
	usage=$(mysql_query "$query" | tail -n1)
	if [ "$usage" == '' ] || [ "$usage" == 'NULL' ] || [ "${usage:0:1}" -eq '0' ]; then
		usage=1
	fi
	export LC_ALL=C
	usage=$(printf "%0.f\n" $usage)
}

# Get PostgreSQL disk usage
get_pgsql_disk_usage() {
	psql_connect $HOST

	# Not the aligned table: its footer is translated ("(1 Zeile)"), so a filter on "row" left it.
	usage=$(psql_value "SELECT pg_database_size('$database');")
	# Unreadable stays unreadable: an invented megabyte would let a space check pass on a number
	# nobody measured, and a bad one would end the caller's whole command substitution.
	case "$usage" in
		'' | *[!0-9]*)
			echo "Error: cannot read the size of $database" >&2
			usage=''
			return 1
			;;
	esac
	usage=$((usage / 1048576))
	if [ "$usage" -eq 0 ]; then
		usage=1
	fi
}

# Delete MySQL user
delete_mysql_user() {
	mysql_connect $HOST

	query="REVOKE ALL ON \`$database\`.* FROM \`$old_dbuser\`@\`%\`"
	mysql_query "$query" > /dev/null

	query="REVOKE ALL ON \`$database\`.* FROM \`$old_dbuser\`@localhost"
	mysql_query "$query" > /dev/null

	# The rights on this database go either way; the user only when no other database still names it.
	db_user_in_use "$old_dbuser" mysql "$HOST"
	[ $? -eq 1 ] || return 0

	query="DROP USER '$old_dbuser'@'%'"
	mysql_query "$query" > /dev/null

	query="DROP USER '$old_dbuser'@'localhost'"
	mysql_query "$query" > /dev/null
}

# Delete PostgreSQL user
delete_pgsql_user() {
	psql_connect $HOST

	query="REVOKE ALL PRIVILEGES ON DATABASE $database FROM $old_dbuser"
	psql_query "$query" > /dev/null

	query="REVOKE CONNECT ON DATABASE template1 FROM $old_dbuser"
	psql_query "$query" > /dev/null

	query="DROP ROLE $old_dbuser"
	psql_query "$query" > /dev/null
}
