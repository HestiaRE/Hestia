<?php

$TAB = "FIREWALL";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

if (empty($_SESSION["CROWDSEC_SYSTEM"])) {
	header("Location: /list/firewall/");
	exit();
}

$data = cli_json("h-list-sys-crowdsec-scenarios json");
ksort($data);

// Render page
render_page($user, $TAB, "list_firewall_crowdsec");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
