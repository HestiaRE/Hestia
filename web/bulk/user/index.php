<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

if (empty($_POST["user"])) {
	header("Location: /list/user");
	exit();
}
if (empty($_POST["action"])) {
	header("Location: /list/user");
	exit();
}
$user = $_POST["user"];
$action = $_POST["action"];

if ($_SESSION["userContext"] === "admin") {
	switch ($action) {
		case "delete":
			$cmd = "h-delete-user";
			$restart = "no";
			break;
		case "suspend":
			$cmd = "h-suspend-user";
			$restart = "yes";
			break;
		case "unsuspend":
			$cmd = "h-unsuspend-user";
			$restart = "yes";
			break;
		case "update counters":
			$cmd = "h-update-user-counters";
			break;
		case "rebuild":
			$cmd = "h-rebuild-all";
			$restart = "no";
			break;
		case "rebuild user":
			$cmd = "h-rebuild-user";
			$restart = "no";
			break;
		case "rebuild web":
			$cmd = "h-rebuild-web-domains";
			break;
		case "rebuild mail":
			$cmd = "h-rebuild-mail-domains";
			break;
		case "rebuild db":
			$cmd = "h-rebuild-databases";
			break;
		case "rebuild cron":
			$cmd = "h-rebuild-cron-jobs";
			break;
		default:
			header("Location: /list/user/");
			exit();
	}
} else {
	switch ($action) {
		case "update counters":
			$cmd = "h-update-user-counters";
			break;
		default:
			header("Location: /list/user/");
			exit();
	}
}

$failed = [];
foreach ($user as $value) {
	exec(HESTIA_CMD . $cmd . " " . quoteshellarg($value) . " " . $restart, $output, $return_var);
	if ($return_var != 0) {
		$failed[] = $value;
	}
	$changes = "yes";
}
bulk_note_failures($failed, count($user));

header("Location: /list/user/");
