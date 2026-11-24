<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "USER";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

verify_csrf($_POST);
// The real admin only: while switched into a customer userContext is the customer's role, adminContext stays admin.
if (($_SESSION["adminContext"] ?? "") !== "admin") {
	header("Location: /login/");
	exit();
}
$back = (string) ($_POST["v_back"] ?? "");
$location = "Location: " . (in_array($back, USER_NOTES_PAGES, true) ? "/list/$back/" : "/list/web/");
// The user the form was for: another tab may have switched to someone else since, whose notes it must not overwrite.
if ((string) ($_POST["v_user"] ?? "") !== $user_plain) {
	$_SESSION["error_msg"] = _("The notes were for another user than the one shown now. Nothing was saved.");
	header($location);
	exit();
}
// The protected admin account is read only (policies.php), the form leaves out its button.
if (is_root_user($_SESSION["look"] ?? "") && ($_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] ?? "") === "yes") {
	header($location);
	exit();
}

$file = private_tmpfile("hst-notes-");
if ($file !== false) {
	if (file_put_contents($file, (string) ($_POST["v_notes"] ?? "")) === false) {
		$_SESSION["error_msg"] = _("An internal error occurred");
	} else {
		exec(HESTIA_CMD . "h-change-user-notes " . $user . " " . quoteshellarg($file), $output, $return_var);
		check_return_code($return_var, $output);
		unset($output);
		// The lists show no ok_msg, it would surface on a later page: the field confirms it itself.
		if (empty($_SESSION["error_msg"])) {
			$_SESSION["user_notes_saved"] = true;
		}
	}
	@unlink($file);
}

// Back to the list the form was on, never to an address the request names freely.
header($location);
exit();
