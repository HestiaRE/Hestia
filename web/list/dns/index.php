<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "DNS";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// The real admin, also while switched into a customer: these are the effective user's zones. Read-only, so the
// userContext gate of the admin routes would only hide a customer's zones from the admin looking at them.
if (($_SESSION["adminContext"] ?? "") !== "admin") {
	header("Location: /list/user/");
	exit();
}

// The zone a name lies in: the longest of the user's zones it is or lies under.
function dns_zone_for(string $name, array $zones): string
{
	$name = strtolower($name);
	if (function_exists("idn_to_ascii") && preg_match("/[^\x20-\x7e]/", $name)) {
		$name = idn_to_ascii($name) ?: $name;
	}
	$best = "";
	foreach (array_keys($zones) as $zone) {
		$zone = (string) $zone;
		if (($name === $zone || str_ends_with($name, "." . $zone)) && strlen($zone) > strlen($best)) {
			$best = $zone;
		}
	}
	return $best;
}

// One record as a zone file line. TXT goes quoted and in strings of at most 255 characters, as DNS carries it.
function dns_zone_line(array $r): string
{
	$value = $r["VALUE"];
	if ($r["TYPE"] === "TXT") {
		$value = implode(" ", array_map(fn ($s) => '"' . addcslashes($s, '"\\') . '"', str_split($value, 255)));
	}
	$fields = [$r["NAME"], $r["TTL"], "IN", $r["TYPE"], $r["PRIORITY"], $value];
	return implode("\t", array_filter($fields, fn ($v) => $v !== ""));
}

if (isset($_GET["providers"])) {
	render_page($user, $TAB, "list_dns_providers");
} elseif (isset($_GET["domain"])) {
	// The jump from a web or mail domain lands in the section of that domain.
	$domain = (string) $_GET["domain"];
	$zone = dns_zone_for($domain, cli_json("h-list-dns-zones " . $user . " json"));
	if ($zone === "") {
		$_SESSION["error_msg"] = sprintf(_("No DNS zone found for %s."), $domain);
		header("Location: /list/dns/");
		exit();
	}
	header("Location: /list/dns/?" . http_build_query(["zone" => $zone]) . "#" . rawurlencode("obj-" . $domain));
	exit();
} elseif (isset($_GET["zone"])) {
	$v_zone = strtolower((string) $_GET["zone"]);
	$zones = cli_json("h-list-dns-zones " . $user . " json");
	if (!isset($zones[$v_zone])) {
		$_SESSION["error_msg"] = sprintf(_("No DNS zone found for %s."), $v_zone);
		header("Location: /list/dns/");
		exit();
	}
	$v_state = $zones[$v_zone]["STATE"] ?? "";
	$records = cli_json("h-list-dns-zone " . $user . " " . quoteshellarg($v_zone) . " json");

	// Sections in the order the lister gives, one per kind, object and owner.
	$sections = [];
	foreach ($records as $r) {
		$key = $r["SECTION"] . "\t" . $r["OBJECT"] . "\t" . $r["OWNER"];
		if (!isset($sections[$key])) {
			$sections[$key] = [
				"kind" => $r["SECTION"],
				"object" => $r["OBJECT"],
				"owner" => $r["OWNER"],
				"rows" => [],
				// An alias section points at the zone of the domain it belongs to, where the user has it.
				"home" => $r["SECTION"] === "alias" ? dns_zone_for($r["OBJECT"], $zones) : "",
				// The zones carrying this web domain's aliases.
				"aliases" => [],
			];
		}
		$sections[$key]["rows"][] = $r;
	}
	foreach ($sections as &$s) {
		if (in_array($s["kind"], ["web", "sub"], true)) {
			foreach ($zones as $z => $info) {
				if ($z !== $v_zone && in_array($s["object"], explode(",", $info["ALIAS_OF"] ?? ""), true)) {
					$s["aliases"][] = (string) $z;
				}
			}
		}
		$s["text"] = implode("\n", array_map("dns_zone_line", $s["rows"]));
	}
	unset($s);
	$v_zone_text = implode(
		"\n\n",
		array_map(fn ($s) => "; " . $s["kind"] . " " . $s["object"] . "\n" . $s["text"], $sections),
	);

	render_page($user, $TAB, "list_dns_zone");
} else {
	$data = cli_json("h-list-dns-zones " . $user . " json");
	ksort($data);
	render_page($user, $TAB, "list_dns");
}

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
