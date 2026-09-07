<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_POST);

$action = $_POST["action"];
$snapshot = quoteshellarg($_POST["snapshot"]);

// One scheduler call per object: h-schedule-user-restore-restic takes a single value and
// validates it as one domain or database, and its validator rejects a comma.
function schedule_restic($user, $snapshot, $object, $values, array &$failed)
{
	foreach ((array) $values as $value) {
		exec(
			HESTIA_CMD .
				"h-schedule-user-restore-restic " .
				$user .
				" " .
				$snapshot .
				" " .
				$object .
				" " .
				quoteshellarg($value),
			$output,
			$return_var,
		);
		if ($return_var != 0) {
			$failed[] = $object . " " . $value;
		}
	}
}

$failed = [];
$total = 0;
if ($action == "restore") {
	foreach (["web", "mail", "db"] as $object) {
		$total += count((array) ($_POST[$object] ?? []));
		schedule_restic($user, $snapshot, $object, $_POST[$object] ?? [], $failed);
	}
	if (!empty($_POST["cron"])) {
		exec(
			HESTIA_CMD . "h-schedule-user-restore-restic " . $user . " " . $snapshot . " " . "cron",
			$output,
			$return_var,
		);
		$total++;
		if ($return_var != 0) {
			$failed[] = "cron";
		}
	}
}

// The last call's code said nothing about the ones before it; name what failed, count what ran.
if ($failed) {
	bulk_note_failures($failed, $total);
} else {
	$_SESSION["error_msg"] = _(
		"Task has been added to the queue. You will receive an email notification when your restore has been completed.",
	);
}
header("Location: /list/backup/incremental/?snapshot=" . $_POST["snapshot"]);
