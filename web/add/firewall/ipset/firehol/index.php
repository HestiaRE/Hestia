<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "FIREWALL";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Written by h-update-firewall-blocklist-catalog, only on the refresh button (#510).
$catalog_file = "/var/cache/hestia/firehol-catalog.json";
$firehol_files = "https://iplists.firehol.org/files/";

function firehol_catalog(string $file): array
{
	$raw = @file_get_contents($file);
	$doc = $raw === false ? null : json_decode($raw, true);
	$lists = [];
	foreach (is_array($doc) && is_array($doc["lists"] ?? null) ? $doc["lists"] : [] as $l) {
		if (is_array($l) && is_string($l["name"] ?? null) && is_string($l["url"] ?? null)) {
			$l["entries"] = (int) ($l["entries"] ?? 0);
			$l["updated"] = (int) ($l["updated"] ?? 0);
			$l["includes"] = array_values(array_filter((array) ($l["includes"] ?? []), "is_string"));
			$lists[$l["name"]] = $l;
		}
	}
	return ["fetched" => (int) ($doc["fetched"] ?? 0), "lists" => $lists];
}

// A set this page manages is one whose every source is a FireHOL file, so another list of the same name stays untouched.
function firehol_sources(array $set, string $prefix): ?array
{
	$urls = preg_split("/\s+/", trim($set["SOURCE"] ?? ""), -1, PREG_SPLIT_NO_EMPTY);
	foreach ($urls as $url) {
		if (!str_starts_with($url, $prefix)) {
			return null;
		}
	}
	return $urls ?: null;
}

$catalog = firehol_catalog($catalog_file);
$ipsets = cli_json("h-list-firewall-ipset json");

$v_setname = trim($_POST["v_setname"] ?? ($_GET["name"] ?? "firehol"));
// ipset names are checked like domains, which rules out the underscore most FireHOL names carry
if (!preg_match('/^[A-Za-z](?:[A-Za-z0-9.-]{0,62}[A-Za-z0-9])?$/', $v_setname)) {
	$v_setname = "firehol";
}
$existing = $ipsets[$v_setname] ?? null;
$existing_urls = $existing ? firehol_sources($existing, $firehol_files) : null;

if (!empty($_POST["action"])) {
	verify_csrf($_POST);

	if ($_POST["action"] === "refresh") {
		exec(HESTIA_CMD . "h-update-firewall-blocklist-catalog", $output, $return_var);
		check_return_code($return_var, $output);
		unset($output);
		if (empty($_SESSION["error_msg"])) {
			$_SESSION["ok_msg"] = _("The FireHOL catalogue has been updated.");
		}
		$catalog = firehol_catalog($catalog_file);
	}

	if ($_POST["action"] === "save") {
		// Names only: the URL comes from the catalogue, never from the form.
		$picked = array_values(array_intersect(array_keys($catalog["lists"]), (array) ($_POST["v_lists"] ?? [])));
		// What a picked combined set already contains adds nothing, the same rule the page applies while picking.
		$covered = [];
		foreach ($picked as $name) {
			$covered = array_merge($covered, $catalog["lists"][$name]["includes"]);
		}
		$picked = array_values(array_diff($picked, $covered));
		$source = implode(" ", array_map(fn ($n) => $catalog["lists"][$n]["url"], $picked));

		if (empty($picked)) {
			$_SESSION["error_msg"] = _("Select at least one list.");
		} elseif ($existing && $existing_urls === null) {
			$_SESSION["error_msg"] = sprintf(_("The IP list %s exists and does not come from FireHOL."), $v_setname);
		} elseif ($existing) {
			exec(HESTIA_CMD . "h-change-firewall-ipset-source " . quoteshellarg($v_setname) . " " . quoteshellarg($source), $output, $return_var);
			check_return_code($return_var, $output);
			unset($output);
		} else {
			exec(
				HESTIA_CMD . "h-add-firewall-ipset " . quoteshellarg($v_setname) . " " . quoteshellarg($source) . " v4 yes",
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
		}

		// One set, one rule: made on the first save, and put back without asking if it has gone since.
		if (empty($_SESSION["error_msg"])) {
			$has_rule = false;
			foreach (cli_json("h-list-firewall json") as $rule) {
				if (($rule["IP"] ?? "") === "ipset:" . $v_setname) {
					$has_rule = true;
					break;
				}
			}
			if (!$has_rule) {
				exec(HESTIA_CMD . "h-add-firewall-rule DROP " . quoteshellarg("ipset:" . $v_setname) . " 0 TCP FireHOL", $output, $return_var);
				check_return_code($return_var, $output);
				unset($output);
			}
		}

		if (empty($_SESSION["error_msg"])) {
			$_SESSION["ok_msg"] = sprintf(_("IP list %s has been saved."), $v_setname);
			$ipsets = cli_json("h-list-firewall-ipset json");
			$existing = $ipsets[$v_setname] ?? null;
			$existing_urls = $existing ? firehol_sources($existing, $firehol_files) : null;
		}
	}
}

// What the set loads today, by catalogue name; a source the catalogue no longer offers is shown as such.
$by_url = [];
foreach ($catalog["lists"] as $name => $l) {
	$by_url[$l["url"]] = $name;
}
$v_selected = [];
$v_gone = [];
foreach ($existing_urls ?? [] as $url) {
	if (isset($by_url[$url])) {
		$v_selected[] = $by_url[$url];
	} else {
		$v_gone[] = basename($url);
	}
}

// Combined sets first, then the 25 largest of the rest; the small ones wait behind a button.
$v_combined = [];
$v_single = [];
foreach ($catalog["lists"] as $name => $l) {
	if (!empty($l["includes"])) {
		$v_combined[$name] = $l;
	} else {
		$v_single[$name] = $l;
	}
}
ksort($v_combined);
uasort($v_single, fn ($a, $b) => $b["entries"] <=> $a["entries"]);
$v_large = array_slice($v_single, 0, 25, true);
$v_small = array_slice($v_single, 25, null, true);
ksort($v_small);

// Render
render_page($user, $TAB, "add_firewall_ipset_firehol");

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
