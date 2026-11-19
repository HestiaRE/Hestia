<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

define("NO_AUTH_REQUIRED", true);
$TAB = "RESET PASSWORD";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// After the include: the session starts there.
if (isset($_SESSION["user"])) {
	header("Location: /list/user");
	exit();
}

// The failed attempts below feed the auth log and fail2ban; unset, the arguments shifted and nothing was logged.
$v_ip = quoteshellarg(get_real_user_ip());
$v_session_id = quoteshellarg((string) ($_POST["token"] ?? ""));
$v_user_agent = quoteshellarg((string) ($_SERVER["HTTP_USER_AGENT"] ?? ""));

//Check values
if (!empty($_POST["user"]) && !empty($_POST["twofa"])) {
	// Check token
	verify_csrf($_POST);
	$error = _("Invalid username or code");
	$v_user = quoteshellarg($_POST["user"]);
	$user = $_POST["user"];
	$twofa = $_POST["twofa"];
	exec(HESTIA_CMD . "h-list-user " . $v_user . " json", $output, $return_var);
	if ($return_var == 0) {
		$data = json_decode(implode("", $output), true);
		$stored = (string) ($data[$user]["TWOFA"] ?? "");
		if ($stored !== "" && hash_equals($stored, (string) $twofa)) {
			// exec() appends: without this the user record read above would land in the error message
			$output = [];
			exec(HESTIA_CMD . "h-delete-user-2fa " . $v_user, $output, $return_var);
			// success only once the key is really gone: a failed delete showed the success page
			if ($return_var == 0) {
				$success = true;
				session_destroy();
			} else {
				$error = _("An internal error occurred");
				check_return_code($return_var, $output);
			}
		} else {
			cli_log("h-log-user-login " . $v_user . " " . $v_ip . " failed " . $v_session_id . " " . $v_user_agent . ' yes "Failed to enter correct 2FA reset key"');
			sleep(5);
		}
	} else {
		cli_log("h-log-user-login " . $v_user . " " . $v_ip . " failed " . $v_session_id . " " . $v_user_agent . ' yes "Failed to enter correct 2FA reset key"');
		sleep(5);
	}
}

require_once "../templates/header.php";
require_once "../templates/pages/login/reset2fa.php";
require_once "../templates/includes/login-footer.php";
