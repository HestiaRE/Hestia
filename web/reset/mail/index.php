<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

define("NO_AUTH_REQUIRED", true);
define("NO_AUTH_REQUIRED2", true);
header("Content-Type: text/plain; charset=utf-8");

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$ok = 0;
$ip = $_SERVER["REMOTE_ADDR"];

// cli_json(), not exec+decode: a failed lister decodes to null, and iterating null is a 500 on a sessionless endpoint.
$arr = cli_json("h-list-sys-ips json");
foreach ($arr as $arr_key => $arr_val) {
	if ($ip == $arr_key || $ip == ($arr_val["NAT"] ?? "")) {
		$ok = 1;
		break;
	}
}
// No SERVER_ADDR check: Caddy's php_fastcgi does not pass it, and the loop above covers the box's own addresses.
// Both loopbacks: "localhost" resolves to ::1 first on Debian.
if ($ip == "127.0.0.1" || $ip == "::1") {
	$ok = 1;
}
if ($ok == 0) {
	exit();
}
// A relayed request names its client in X-Real-IP. Not X-Forwarded-For: Caddy sets that on every request.
if (isset($_SERVER["HTTP_X_REAL_IP"])) {
	exit();
}

if (empty($_POST["email"])) {
	echo "error email address not provided";
	exit();
}
if (empty($_POST["password"])) {
	echo "error old password provided";
	exit();
}
if (empty($_POST["new"])) {
	echo "error new password not provided";
	exit();
}

[$v_account, $v_domain] = explode("@", $_POST["email"]);
$v_domain = quoteshellarg($v_domain);
$v_account = quoteshellarg($v_account);
$v_password = $_POST["password"];

exec(HESTIA_CMD . "h-search-domain-owner " . $v_domain . " 'mail'", $output, $return_var);
if ($return_var != 0 || empty($output[0])) {
	echo "error domain owner not found";
	exit();
}
$v_user = $output[0];
unset($output);

// "md5" is a legacy key name; the value is a hash in whatever scheme the box uses.
exec(
	HESTIA_CMD .
		"h-get-mail-account-value " .
		quoteshellarg($v_user) .
		" " .
		$v_domain .
		" " .
		$v_account .
		" 'md5'",
	$output,
	$return_var,
);
if ($return_var != 0 || empty($output[0])) {
	echo "error unable to get current account password hash";
	exit();
}
$v_hash = $output[0];
unset($output);

// A doveadm hash is {SCHEME}crypt, and password_verify takes only the crypt part.
$hash_for_password_verify = explode("}", $v_hash, 2);
$hash_for_password_verify = end($hash_for_password_verify);
if (!password_verify($v_password, $hash_for_password_verify)) {
	die("error old password does not match");
}

$fp = tmpfile();
$new_password_file = stream_get_meta_data($fp)["uri"];
fwrite($fp, $_POST["new"] . "\n");
exec(
	HESTIA_CMD .
		"h-change-mail-account-password " .
		quoteshellarg($v_user) .
		" " .
		$v_domain .
		" " .
		$v_account .
		" " .
		quoteshellarg($new_password_file),
	$output,
	$return_var,
);
fclose($fp);
if ($return_var == 0) {
	// Only a local caller that just proved the old password gets here, so the client grace grants nothing new.
	$client_ip = $_POST["ip"] ?? "";
	if (filter_var($client_ip, FILTER_VALIDATE_IP) !== false) {
		exec(
			HESTIA_CMD .
				"h-add-mail-account-grace " .
				quoteshellarg($v_user) .
				" " .
				$v_domain .
				" " .
				$v_account .
				" " .
				quoteshellarg($client_ip),
			$output,
			$return_var,
		);
	}
	echo "==ok==";
	exit();
}
echo "error h-change-mail-account-password returned non-zero: " . $return_var;
exit();
