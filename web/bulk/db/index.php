<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

if (empty($_POST["database"])) {
	header("Location: /list/db/");
	exit();
}

if (empty($_POST["action"])) {
	header("Location: /list/db/");
	exit();
}
$database = $_POST["database"];
$action = $_POST["action"];

if ($_SESSION["userContext"] === "admin") {
	switch ($action) {
		case "rebuild":
			$cmd = "h-rebuild-database";
			break;
		case "delete":
			$cmd = "h-delete-database";
			break;
		case "suspend":
			$cmd = "h-suspend-database";
			break;
		case "unsuspend":
			$cmd = "h-unsuspend-database";
			break;
		default:
			header("Location: /list/db/");
			exit();
	}
} else {
	switch ($action) {
		case "delete":
			$cmd = "h-delete-database";
			break;
		case "suspend":
			$cmd = "h-suspend-database";
			break;
		case "unsuspend":
			$cmd = "h-unsuspend-database";
			break;
		default:
			header("Location: /list/db/");
			exit();
	}
}

$failed = [];
foreach ($database as $value) {
	exec(HESTIA_CMD . $cmd . " " . $user . " " . quoteshellarg($value), $output, $return_var);
	if ($return_var != 0) {
		$failed[] = $value;
	}
}
bulk_note_failures($failed, count($database));

header("Location: /list/db/");
