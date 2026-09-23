<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_GET);

// Delete as someone else?
if ($_SESSION["userContext"] === "admin" && !empty($_GET["user"])) {
	$user = quoteshellarg($user);
}

if (!empty($_GET["domain"])) {
	$v_domain = quoteshellarg($_GET["domain"]);
	// Empty RESTART is a reload: 'yes' restarts, and that cuts the request asking for it (#878).
	exec(
		HESTIA_CMD . "h-delete-web-domain " . $user . " " . $v_domain . " ''",
		$output,
		$return_var,
	);
	check_return_code($return_var, $output);
	unset($output);
}

$back = $_SESSION["back"];
if (!empty($back)) {
	header("Location: " . $back);
	exit();
}

header("Location: /list/web/");
exit();
