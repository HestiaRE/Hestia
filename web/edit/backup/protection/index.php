<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

if ($_SESSION["userContext"] === "admin" && !empty($_GET["user"])) {
	$user = quoteshellarg($_GET["user"]);
}

verify_csrf($_GET);

if (!empty($_GET["backup"]) && in_array($_GET["state"] ?? "", ["yes", "no"], true)) {
	$v_backup = quoteshellarg($_GET["backup"]);
	exec(
		HESTIA_CMD . "h-change-user-backup-protection " . $user . " " . $v_backup . " " . quoteshellarg($_GET["state"]),
		$output,
		$return_var,
	);
	check_return_code($return_var, $output);
}
unset($output);

$back = $_SESSION["back"];
if (!empty($back)) {
	header("Location: " . $back);
	exit();
}

header("Location: /list/backup/");
exit();
