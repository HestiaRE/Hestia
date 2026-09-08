<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

if (empty($_POST["service"])) {
	header("Location: /list/server/");
	exit();
}
if (empty($_POST["action"])) {
	header("Location: /list/server/");
	exit();
}

$service = $_POST["service"];
$action = $_POST["action"];

if ($_SESSION["userContext"] === "admin") {
	switch ($action) {
		case "stop":
			$cmd = "h-stop-service";
			break;
		case "start":
			$cmd = "h-start-service";
			break;
		case "restart":
			$cmd = "h-restart-service";
			break;
		default:
			header("Location: /list/server/");
			exit();
	}

	if (!empty($_POST["system"]) && $action == "restart") {
		$_SESSION["error_srv"] = _("The system is going down for reboot NOW!");
		exec(HESTIA_CMD . "h-restart-system yes", $output, $return_var);
		check_return_code($return_var, $output);
		unset($output);
		header("Location: /list/server/");
		exit();
	}

	$failed = [];
	foreach ($service as $value) {
		exec(HESTIA_CMD . $cmd . " " . quoteshellarg($value), $output, $return_var);
		if ($return_var != 0) {
			$failed[] = $value;
		}
	}
	bulk_note_failures($failed, count($service));
}

header("Location: /list/server/");
