<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

define("NO_AUTH_REQUIRED", true);
$TAB = "RESET PASSWORD";

if (isset($_SESSION["user"])) {
	header("Location: /list/user");
}

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

//Check values
if (!empty($_POST["user"]) && !empty($_POST["twofa"])) {
	// Check token
	verify_csrf($_POST);
	$error = true;
	$v_user = quoteshellarg($_POST["user"]);
	$user = $_POST["user"];
	$twofa = $_POST["twofa"];
	exec(HESTIA_CMD . "h-list-user " . $v_user . " json", $output, $return_var);
	if ($return_var == 0) {
		$data = json_decode(implode("", $output), true);
		if ($data[$user]["TWOFA"] == $twofa) {
			// exec() appends: without this the user record read above would land in the error message
			$output = [];
			exec(HESTIA_CMD . "h-delete-user-2fa " . $v_user, $output, $return_var);
			// success only once the key is really gone: a failed delete showed the success page
			if ($return_var == 0) {
				$success = true;
				session_destroy();
			} else {
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
