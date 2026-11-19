<?php

$TAB = "IP";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Data
$data = cli_json("h-list-sys-ips json");
// Reverse DNS checked live, so a fixed PTR shows on reload. The check sets the banner key, read back for this page.
$ptr_check = !empty($_SESSION["MAIL_SYSTEM"]);
$ptr = [];
if ($ptr_check) {
	$ptr = cli_json("h-check-sys-ip-ptr json");
	load_hestia_config();
}
if ($_SESSION["userSortOrder"] == "name") {
	ksort($data);
} else {
	$data = array_reverse($data, true);
}

// Render page
render_page($user, $TAB, "list_ip");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
