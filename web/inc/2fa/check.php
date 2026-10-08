<?php

require_once __DIR__ . "/../lib/totp.php";

// CLI only, secret and token on stdin: as arguments the seed would stand in /proc for every local user to read.
if (PHP_SAPI !== "cli") {
	exit();
}
$secret = trim((string) fgets(STDIN));
$token = trim((string) fgets(STDIN));
if ($secret === "" || $token === "") {
	echo "ERROR: Secret or Token is not set!";
	exit();
}

if (hestia_totp_verify($secret, $token)) {
	echo "ok";
}
