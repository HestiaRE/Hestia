<?php

$TAB = "SERVER";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Check POST request
if (!empty($_POST["save"])) {
	// Set success message
	$_SESSION["ok_msg"] = _("Info (read-only mode): Crontab can only be edited via SSH.");
}

$v_service_name = strtoupper("cron");

// Read config
[$v_config_path, $v_config] = server_config("cron");

// Render page
render_page($user, $TAB, "edit_server_service");

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
