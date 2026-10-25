<?php

/**
 * HestiaRE Adminer plugin configuration.
 *
 * Deployed by h-add-sys-adminer next to the served adminer.php (as
 * /usr/share/adminer/adminer-plugins.php). Adminer's bootstrap auto-detects
 * this file and calls `new Plugins(null)`, which includes adminer-plugins/*.php
 * (the plugin classes) and then this file, expecting an array of configured
 * plugin instances.
 *
 * We load ONLY the login-servers plugin, pinned to the LOCAL server. This turns
 * the login form's free-text "Server" field into a fixed dropdown of localhost
 * entries - so the panel's Adminer cannot be pointed at an arbitrary remote host
 * (SSRF hardening, #350). Username/password login is unchanged; there is no SSO
 * (out of scope by decision - the regular DB login is enough for this niche).
 *
 * This is HestiaRE configuration, not vendored upstream code - the plugin class
 * it instantiates (adminer-plugins/login-servers.php) is the vendored artifact.
 */

require_once __DIR__ . '/adminer-plugins/login-servers.php';

// verify_token() splits the posted token unchecked, so an array or a non-numeric half ends in a Fatal before any
// login. The shape get_token() writes passes; anything else becomes a token that never verifies.
if (isset($_POST["token"]) && !(is_string($_POST["token"]) && preg_match('/^[0-9]+:[0-9]+$/', $_POST["token"]))) {
	$_POST["token"] = "-1:0";
}

return array(
	new AdminerLoginServers(array(
		'PostgreSQL (local)' => array('server' => 'localhost', 'driver' => 'pgsql'),
		'MySQL / MariaDB (local)' => array('server' => 'localhost', 'driver' => 'server'),
	)),
);
