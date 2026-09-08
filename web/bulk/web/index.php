<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

if (empty($_POST["domain"])) {
	header("Location: /list/web/");
	exit();
}
if (empty($_POST["action"])) {
	header("Location: /list/web");
	exit();
}

$domain = $_POST["domain"];
$action = $_POST["action"];

if ($_SESSION["userContext"] === "admin") {
	switch ($action) {
		case "delete":
			$cmd = "h-delete-web-domain";
			break;
		case "rebuild":
			$cmd = "h-rebuild-web-domain";
			break;
		case "suspend":
			$cmd = "h-suspend-web-domain";
			break;
		case "unsuspend":
			$cmd = "h-unsuspend-web-domain";
			break;
		case "purge":
			$cmd = "h-purge-nginx-cache";
			break;
		default:
			header("Location: /list/web/");
			exit();
	}
} else {
	switch ($action) {
		case "delete":
			$cmd = "h-delete-web-domain";
			break;
		case "suspend":
			$cmd = "h-suspend-web-domain";
			break;
		case "unsuspend":
			$cmd = "h-unsuspend-web-domain";
			break;
		case "purge":
			$cmd = "h-purge-nginx-cache";
			break;
		default:
			header("Location: /list/web/");
			exit();
	}
}

$failed = [];
foreach ($domain as $value) {
	exec(HESTIA_CMD . $cmd . " " . $user . " " . quoteshellarg($value) . " no", $output, $return_var);
	if ($return_var != 0) {
		$failed[] = $value;
	}
	$restart = "yes";
}
bulk_note_failures($failed, count($domain));

if (isset($restart)) {
	// exec() appends: the loop's lines must not land in a restart's error message
	$output = [];
	exec(HESTIA_CMD . "h-restart-web", $output, $return_var);
	check_return_code($return_var, $output);
	$output = [];
	exec(HESTIA_CMD . "h-restart-proxy", $output, $return_var);
	check_return_code($return_var, $output);
	$output = [];
	exec(HESTIA_CMD . "h-restart-web-backend", $output, $return_var);
	check_return_code($return_var, $output);
}

header("Location: /list/web/");
