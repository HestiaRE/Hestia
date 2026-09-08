<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

if (empty($_POST["package"])) {
	header("Location: /list/package");
	exit();
}
if (empty($_POST["action"])) {
	header("Location: /list/package");
	exit();
}

$package = $_POST["package"];
$action = $_POST["action"];

if ($_SESSION["userContext"] === "admin") {
	switch ($action) {
		case "delete":
			$cmd = "h-delete-user-package";
			break;
		default:
			header("Location: /list/package/");
			exit();
	}
} else {
	header("Location: /list/package/");
	exit();
}

$failed = [];
foreach ($package as $value) {
	exec(HESTIA_CMD . $cmd . " " . quoteshellarg($value), $output, $return_var);
	if ($return_var != 0) {
		$failed[] = $value;
	}
	$restart = "yes";
}
bulk_note_failures($failed, count($package));

header("Location: /list/package/");
