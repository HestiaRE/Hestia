<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "DB";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check database id
if (empty($_GET["database"])) {
	header("Location: /list/db/");
	exit();
}

// Edit as someone else?
if ($_SESSION["userContext"] === "admin" && !empty($_GET["user"])) {
	$user = quoteshellarg($_GET["user"]);
	$user_plain = htmlentities($_GET["user"]);
}

// List datbase
$v_database = $_GET["database"];
exec(
	HESTIA_CMD . "h-list-database " . $user . " " . quoteshellarg($v_database) . " 'json'",
	$output,
	$return_var,
);
check_return_code_redirect($return_var, $output, "/list/db/");
$data = json_decode(implode("", $output), true);
unset($output);

// Parse database
$v_username = $user;
$v_dbuser = preg_replace("/^" . $user_plain . "_/", "", $data[$v_database]["DBUSER"]);
$v_password = "";
$v_host = $data[$v_database]["HOST"];
$v_type = $data[$v_database]["TYPE"];
$v_charset = $data[$v_database]["CHARSET"];
$v_date = $data[$v_database]["DATE"];
$v_time = $data[$v_database]["TIME"];
$v_suspended = $data[$v_database]["SUSPENDED"];
if ($v_suspended == "yes") {
	$v_status = "suspended";
} else {
	$v_status = "active";
}

// Shared and second users (#725) exist for MySQL only. The form offers, the CLI enforces every rule.
$offer_second = $v_type === "mysql";
$v_dbuser_second = preg_replace("/^" . $user_plain . "_/", "", $data[$v_database]["DBUSER_SECOND"] ?? "");
$v_dbuser_second_ro = ($data[$v_database]["DBUSER_SECOND_RO"] ?? "") === "yes" ? "yes" : "no";
$db_users = [];
$shared_main = [];
$shared_second = [];
if ($offer_second) {
	exec(HESTIA_CMD . "h-list-databases " . $user . " json", $output, $return_var);
	$all_dbs = $return_var === 0 ? json_decode(implode("", $output), true) : null;
	unset($output);
	foreach (is_array($all_dbs) ? $all_dbs : [] as $name => $db) {
		// A MySQL user lives on one server, so only the users of this database's host can be shared here.
		if ($db["TYPE"] !== "mysql" || $db["HOST"] !== $v_host) {
			continue;
		}
		$slots = array_filter([$db["DBUSER"], $db["DBUSER_SECOND"] ?? ""]);
		foreach ($slots as $slot_user) {
			$db_users[preg_replace("/^" . $user_plain . "_/", "", $slot_user)] = true;
		}
		if ($name === $v_database) {
			continue;
		}
		if (in_array($data[$v_database]["DBUSER"], $slots, true)) {
			$shared_main[] = $name;
		}
		if ($v_dbuser_second !== "" && in_array($data[$v_database]["DBUSER_SECOND"], $slots, true)) {
			$shared_second[] = $name;
		}
	}
	$db_users = array_keys($db_users);
	sort($db_users);
}

// Check POST request
if (!empty($_POST["save"])) {
	$v_username = $user;

	// Check token
	verify_csrf($_POST);

	// Change database user. A password given with it goes along: a record without a password of its own needs one
	// for a new name, and an existing name refuses one because that user keeps its own.
	$main_pass_done = false;
	if ($v_dbuser != $_POST["v_dbuser"] && empty($_SESSION["error_msg"])) {
		$pass_arg = "";
		if (!empty($_POST["v_password"])) {
			if (!validate_password($_POST["v_password"])) {
				$_SESSION["error_msg"] = _("Password does not match the minimum requirements.");
			} else {
				$pass_arg = secret_tmpfile($_POST["v_password"]);
			}
		}
		if (empty($_SESSION["error_msg"]) && $pass_arg !== false) {
			$cmd = implode(" ", [
				HESTIA_CMD . "h-change-database-user",
				// $user is already shell-quoted
				$user,
				quoteshellarg($v_database),
				quoteshellarg($_POST["v_dbuser"]),
				quoteshellarg($pass_arg),
			]);
			exec($cmd, $output, $return_var);
			check_return_code($return_var, $output);
			unset($output);
			$main_pass_done = $pass_arg !== "";
		}
		if (!empty($pass_arg)) {
			unlink($pass_arg);
		}
	}

	// Change database password
	if (!empty($_POST["v_password"]) && !$main_pass_done && empty($_SESSION["error_msg"])) {
		if (!validate_password($_POST["v_password"])) {
			$_SESSION["error_msg"] = _("Password does not match the minimum requirements.");
		} else {
			$v_password = tempnam("/tmp", "vst");
			$fp = fopen($v_password, "w");
			fwrite($fp, $_POST["v_password"] . "\n");
			fclose($fp);
			exec(
				HESTIA_CMD .
					"h-change-database-password " .
					$user .
					" " .
					quoteshellarg($v_database) .
					" " .
					$v_password,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			unlink($v_password);
			$v_password = quoteshellarg($_POST["v_password"]);
		}
	}

	// Second user: an emptied name removes it; a new name, a password or a flipped read-only box changes it.
	$post_second = trim((string) post_or_keep("v_dbuser_second", $offer_second, $v_dbuser_second));
	$post_second_ro = post_checkbox("v_dbuser_second_ro", $offer_second, $v_dbuser_second_ro, "yes", "no");
	$post_second_pass = $offer_second ? (string) ($_POST["v_password_second"] ?? "") : "";
	if ($offer_second && empty($_SESSION["error_msg"])) {
		if ($post_second === "" && $v_dbuser_second !== "") {
			$cmd = implode(" ", [HESTIA_CMD . "h-delete-database-second-user", $user, quoteshellarg($v_database)]);
			exec($cmd, $output, $return_var);
			check_return_code($return_var, $output);
			unset($output);
		} elseif (
			$post_second !== "" &&
			($post_second !== $v_dbuser_second || $post_second_pass !== "" || $post_second_ro !== $v_dbuser_second_ro)
		) {
			$pass_arg = "";
			if ($post_second_pass !== "") {
				if (!validate_password($post_second_pass)) {
					$_SESSION["error_msg"] = _("Password does not match the minimum requirements.");
				} else {
					$pass_arg = secret_tmpfile($post_second_pass);
				}
			}
			if (empty($_SESSION["error_msg"]) && $pass_arg !== false) {
				$cmd = implode(" ", [
					HESTIA_CMD . "h-change-database-second-user",
					$user,
					quoteshellarg($v_database),
					quoteshellarg($post_second),
					quoteshellarg($pass_arg),
					quoteshellarg($post_second_ro),
				]);
				exec($cmd, $output, $return_var);
				check_return_code($return_var, $output);
				unset($output);
			}
			if (!empty($pass_arg)) {
				unlink($pass_arg);
			}
		}
	}

	// Set success message
	if (empty($_SESSION["error_msg"])) {
		$_SESSION["ok_msg"] = _("Changes have been saved.");
	}
	// if the mysql username was changed, render_page() below will render with the OLD mysql username,
	// to prvent that, make the browser refresh the page.
	http_response_code(303);
	header("Location: " . $_SERVER["REQUEST_URI"]);
	die();
}

// Render page
render_page($user, $TAB, "edit_db");

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
