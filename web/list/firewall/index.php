<?php

$TAB = "FIREWALL";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Data
$data = cli_json("h-list-firewall json");
// Evaluation order, always - the renderer emits rules by descending RULE id into one chain and
// nft takes the first match, so the top row is the one that actually wins. A firewall ruleset is
// ordered data, not a sortable table: honouring userSortOrder here made the list agree with
// precedence under one setting and invert it under the other, which silently flipped what the
// move arrows appeared to do.
krsort($data, SORT_NUMERIC);

// What a rule reaches: a CIDR or a list fixes its family, a rule for any source names its own.
$ipsets = cli_json("h-list-firewall-ipset json");
foreach ($data as $key => $rule) {
	$ip = $rule["IP"] ?? "";
	if (str_starts_with($ip, "ipset:")) {
		$family = ($ipsets[substr($ip, 6)]["IP_VERSION"] ?? "v4") === "v6" ? "IPv6" : "IPv4";
	} elseif (in_array($ip, ["0.0.0.0/0", "::/0", ""], true)) {
		$family = ["v4" => "IPv4", "v6" => "IPv6"][$rule["IP_VERSION"] ?? ""] ?? "IPv4 + IPv6";
	} else {
		$family = str_contains($ip, ":") ? "IPv6" : "IPv4";
	}
	$data[$key]["FAMILY"] = $family;
}

// Render page
render_page($user, $TAB, "list_firewall");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
