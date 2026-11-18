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
verify_csrf($_GET);

$state = $_GET["state"] ?? "";
if (!empty($_GET["scenario"]) && in_array($state, ["on", "off"], true)) {
	exec(
		HESTIA_CMD . "h-change-sys-crowdsec-scenario " . quoteshellarg($_GET["scenario"]) . " " . quoteshellarg($state),
		$output,
		$return_var,
	);
	check_return_code($return_var, $output);
	unset($output);
}

header("Location: /list/firewall/crowdsec/");
exit();
