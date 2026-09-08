<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Check token
verify_csrf($_POST);

if (empty($_POST["rule"])) {
	header("Location: /list/firewall/");
	exit();
}
if (empty($_POST["action"])) {
	header("Location: /list/firewall/");
	exit();
}

$rule = $_POST["rule"];
$action = $_POST["action"];

switch ($action) {
	case "delete":
		$cmd = "h-delete-firewall-rule";
		break;
	case "suspend":
		$cmd = "h-suspend-firewall-rule";
		break;
	case "unsuspend":
		$cmd = "h-unsuspend-firewall-rule";
		break;
	default:
		header("Location: /list/firewall/");
		exit();
}

$failed = [];
foreach ($rule as $value) {
	exec(HESTIA_CMD . $cmd . " " . quoteshellarg($value), $output, $return_var);
	if ($return_var != 0) {
		$failed[] = $value;
	}
	$restart = "yes";
}
bulk_note_failures($failed, count($rule));

header("Location: /list/firewall/");
