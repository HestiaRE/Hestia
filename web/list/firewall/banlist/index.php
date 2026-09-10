<?php

$TAB = "FIREWALL";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Data: fail2ban bans (banlist.conf) plus, when CrowdSec is present, its local L3 decisions - so an admin
// can see and lift a CrowdSec ban here too, including in the crowdsec-only model where there is no
// fail2ban banlist at all. Keyed with a source prefix so a shared IP does not collide between the two.
$data = [];

foreach (cli_json("h-list-firewall-ban json") as $ip => $v) {
	$v["IP"] = $ip;
	$v["SOURCE"] = "fail2ban";
	$data["f2b:" . $ip] = $v;
}

// CROWDSEC_SYSTEM, the registry key (#938), replaces the synthetic CROWDSEC the emitter used to compute
// from the L3 marker file. Not the same predicate: the key says an engine is there and in which model,
// the marker said the L3 feeder was wired. An engine without L3 now counts, a marker without an engine
// no longer does, and the latter is the state that used to offer a banlist nothing could fill (#945).
if (!empty($_SESSION["CROWDSEC_SYSTEM"])) {
	foreach (cli_json("h-list-firewall-crowdsec-ban json") as $ip => $v) {
		$v["IP"] = $ip;
		$v["SOURCE"] = "crowdsec";
		$data["cs:" . $ip] = $v;
	}
}

$data = array_reverse($data, true);

// Render page
render_page($user, $TAB, "list_firewall_banlist");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
