<?php

$TAB = "SERVER";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Check POST request
if (!empty($_POST["save"])) {
	// Check token
	verify_csrf($_POST);

	// Set restart flag
	$v_restart = "yes";
	if (empty($_POST["v_restart"])) {
		$v_restart = "no";
	}

	// Update config
	if (!empty($_POST["v_config"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config1
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config1"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config1"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-1 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config2
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config2"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config2"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-2 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config3
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config3"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config3"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-3 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config4
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config4"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config4"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-4 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config5
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config5"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config5"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-5 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config6
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config6"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config6"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-6 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config7
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config7"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config7"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-7 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Update config8
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_config8"])) {
		$new_conf = private_tmpfile();
		if ($new_conf !== false) {
			$fp = fopen($new_conf, "w");
			fwrite($fp, str_replace("\r\n", "\n", $_POST["v_config8"]));
			fclose($fp);
			exec(
				HESTIA_CMD . "h-change-sys-service-config " . $new_conf . " dovecot-8 " . $v_restart,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($new_conf);
		}
	}

	// Set success message
	if (empty($_SESSION["error_msg"])) {
		$_SESSION["ok_msg"] = _("Changes have been saved.");
	}
}

$v_service_name = strtoupper("dovecot");

// Read config
[$v_config_path, $v_config] = server_config("dovecot");
[$v_config_path1, $v_config1] = server_config("dovecot-1");
[$v_config_path2, $v_config2] = server_config("dovecot-2");
[$v_config_path3, $v_config3] = server_config("dovecot-3");
[$v_config_path4, $v_config4] = server_config("dovecot-4");
[$v_config_path5, $v_config5] = server_config("dovecot-5");
[$v_config_path6, $v_config6] = server_config("dovecot-6");
[$v_config_path7, $v_config7] = server_config("dovecot-7");
[$v_config_path8, $v_config8] = server_config("dovecot-8");

// Render page
render_page($user, $TAB, "edit_server_dovecot");

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
