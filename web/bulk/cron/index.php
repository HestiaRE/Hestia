<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

if (empty($_POST["job"])) {
	header("Location: /list/cron/");
	exit();
}
$job = $_POST["job"];

if (empty($_POST["action"])) {
	header("Location: /list/cron/");
	exit();
}
$action = $_POST["action"];

if ($_SESSION["userContext"] === "admin") {
	switch ($action) {
		case "delete":
			$cmd = "h-delete-cron-job";
			break;
		case "suspend":
			$cmd = "h-suspend-cron-job";
			break;
		case "unsuspend":
			$cmd = "h-unsuspend-cron-job";
			break;
		case "delete-cron-reports":
			$cmd = "h-delete-cron-reports";
			exec(HESTIA_CMD . $cmd . " " . $user, $output, $return_var);
			if ($return_var == 0) {
				$_SESSION["error_msg"] = _("Cron job email reporting has been successfully disabled.");
			} else {
				check_return_code($return_var, $output);
			}
			unset($output);
			header("Location: /list/cron/");
			exit();
			break;
		case "add-cron-reports":
			$cmd = "h-add-cron-reports";
			$output = [];
			exec(HESTIA_CMD . $cmd . " " . $user, $output, $return_var);
			if ($return_var == 0) {
				$_SESSION["error_msg"] = _("Cron job email reporting has been successfully enabled.");
			} else {
				check_return_code($return_var, $output);
			}
			unset($output);
			header("Location: /list/cron/");
			exit();
			break;
		default:
			header("Location: /list/cron/");
			exit();
	}
} else {
	switch ($action) {
		case "delete":
			$cmd = "h-delete-cron-job";
			break;
		case "delete-cron-reports":
			$cmd = "h-delete-cron-reports";
			$output = [];
			exec(HESTIA_CMD . $cmd . " " . $user, $output, $return_var);
			if ($return_var == 0) {
				$_SESSION["error_msg"] = _("Cron job email reporting has been successfully disabled.");
			} else {
				check_return_code($return_var, $output);
			}
			unset($output);
			header("Location: /list/cron/");
			exit();
			break;
		case "add-cron-reports":
			$cmd = "h-add-cron-reports";
			$output = [];
			exec(HESTIA_CMD . $cmd . " " . $user, $output, $return_var);
			if ($return_var == 0) {
				$_SESSION["error_msg"] = _("Cron job email reporting has been successfully enabled.");
			} else {
				check_return_code($return_var, $output);
			}
			unset($output);
			header("Location: /list/cron/");
			exit();
			break;
		default:
			header("Location: /list/cron/");
			exit();
	}
}

$failed = [];
foreach ($job as $value) {
	exec(HESTIA_CMD . $cmd . " " . $user . " " . quoteshellarg($value) . " no", $output, $return_var);
	if ($return_var != 0) {
		$failed[] = $value;
	}
	$restart = "yes";
}
bulk_note_failures($failed, count($job));

if (!empty($restart)) {
	// exec() appends: the loop's lines must not land in the restart's error message
	$output = [];
	exec(HESTIA_CMD . "h-restart-cron", $output, $return_var);
	check_return_code($return_var, $output);
}

header("Location: /list/cron/");
