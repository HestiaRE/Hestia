<?php

// $panel is keyed by the plain name; $user is shell-quoted and found no row.
if (
	($_SESSION["userContext"] === "user" &&
		($panel[$user_plain]["SUSPENDED"] ?? "") === "yes" &&
		($_SESSION["POLICY_USER_VIEW_SUSPENDED"] ?? "") === "yes") ||
	($_SESSION["adminContext"] === "admin" &&
		is_root_user($_SESSION["look"]) &&
		$_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] === "yes")
) {
	$read_only = "true";
} else {
	$read_only = "";
}

if ($read_only === "true") {
	$display_mode = "disabled";
} else {
	$display_mode = "";
}
