<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

if (empty($_POST["ip"])) {
	header("Location: /list/ip");
	exit();
}
if (empty($_POST["action"])) {
	header("Location: /list/ip");
	exit();
}

$ip = $_POST["ip"];
$action = $_POST["action"];

if ($_SESSION["userContext"] === "admin") {
	switch ($action) {
		case "reread IP":
			exec(HESTIA_CMD . "h-update-sys-ip", $output, $return_var);
			check_return_code($return_var, $output);
			header("Location: /list/ip/");
			exit();
			break;
		case "delete":
			$cmd = "h-delete-sys-ip";
			break;
		default:
			header("Location: /list/ip/");
			exit();
	}
} else {
	header("Location: /list/ip/");
	exit();
}

$failed = [];
foreach ($ip as $value) {
	exec(HESTIA_CMD . $cmd . " " . quoteshellarg($value), $output, $return_var);
	if ($return_var != 0) {
		$failed[] = $value;
	}
}
bulk_note_failures($failed, count($ip));

header("Location: /list/ip/");
