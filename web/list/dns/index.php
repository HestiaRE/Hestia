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

// Provider accounts are the box's, not the shown user's: each action takes the account name only.
if (isset($_GET["providers"])) {
	if (!empty($_POST["v_account"])) {
		verify_csrf($_POST);
		$key_file = secret_tmpfile((string) ($_POST["v_key"] ?? ""));
		if ($key_file !== false) {
			exec(
				HESTIA_CMD . "h-add-dns-provider " . quoteshellarg((string) $_POST["v_account"]) . " " .
					quoteshellarg((string) ($_POST["v_provider"] ?? "")) . " " . quoteshellarg($key_file) . " " .
					quoteshellarg((string) ($_POST["v_context"] ?? "4")),
				$output,
				$return_var,
			);
			@unlink($key_file);
			check_return_code($return_var, $output);
			unset($output);
			if (empty($_SESSION["error_msg"])) {
				$_SESSION["ok_msg"] = sprintf(_("Account %s has been added."), (string) $_POST["v_account"]);
			}
		}
		header("Location: /list/dns/?providers=1");
		exit();
	}
	foreach (["check" => "h-check-dns-provider", "delete" => "h-delete-dns-provider"] as $action => $command) {
		if (isset($_GET[$action])) {
			verify_csrf($_GET);
			exec(HESTIA_CMD . $command . " " . quoteshellarg((string) $_GET[$action]), $output, $return_var);
			check_return_code($return_var, $output);
			unset($output);
			header("Location: /list/dns/?providers=1");
			exit();
		}
	}
	$providers = cli_json("h-list-dns-providers json");
	$accounts = $providers["ACCOUNTS"] ?? [];
	$drivers = array_filter(explode(",", (string) ($providers["DRIVERS"] ?? "")));
	dns_provider_count($accounts);
	render_page($user, $TAB, "list_dns_providers");
} elseif (isset($_GET["check"])) {
	// Every zone of the shown user an account holds, one request each at the provider's pace.
	verify_csrf($_GET);
	exec(HESTIA_CMD . "h-check-dns-zones " . $user . " json", $output, $return_var);
	check_return_code($return_var, $output);
	unset($output);
	header("Location: /list/dns/");
	exit();
} elseif (isset($_GET["domain"])) {
	// The jump from a web or mail domain lands in the section of that domain.
	$domain = (string) $_GET["domain"];
	$zones = cli_json("h-list-dns-zones " . $user . " json");
	dns_zone_count($zones);
	$zone = dns_zone_for($domain, $zones);
	if ($zone === "") {
		$_SESSION["error_msg"] = sprintf(_("No DNS zone found for %s."), $domain);
		header("Location: /list/dns/");
		exit();
	}
	// From the mail list to the mail section: a domain with web and mail has its web section first.
	$anchor = (isset($_GET["mail"]) ? "obj-mail-" : "obj-") . $domain;
	header("Location: /list/dns/?" . http_build_query(["zone" => $zone]) . "#" . rawurlencode($anchor));
	exit();
} elseif (isset($_GET["zone"])) {
	$v_zone = strtolower((string) $_GET["zone"]);
	$zones = cli_json("h-list-dns-zones " . $user . " json");
	dns_zone_count($zones);
	if (!isset($zones[$v_zone])) {
		$_SESSION["error_msg"] = sprintf(_("No DNS zone found for %s."), $v_zone);
		header("Location: /list/dns/");
		exit();
	}
	$v_state = $zones[$v_zone]["STATE"] ?? "";
	$v_account = $zones[$v_zone]["ACCOUNT"] ?? "";
	// A zone an account holds is compared on every visit; the list shows the result of the last one.
	$v_checked = false;
	$v_check_error = "";
	$foreign = [];
	$hidden = [];
	if ($v_account !== "") {
		exec(HESTIA_CMD . "h-check-dns-zone " . $user . " " . quoteshellarg($v_zone) . " json", $output, $return_var);
		$checked = json_decode(implode("", $output), true);
		if ($return_var === 0 && is_array($checked)) {
			$v_checked = true;
			$records = [];
			foreach ($checked as $r) {
				if ($r["SECTION"] === "foreign") {
					$foreign[] = $r;
				} elseif ($r["SECTION"] === "hidden") {
					$hidden[] = $r;
				} else {
					$records[] = $r;
				}
			}
		} else {
			$v_check_error = trim(preg_replace("/^Error:\s*/", "", implode(" ", $output)));
		}
		unset($output);
	}
	if (!$v_checked) {
		$records = cli_json("h-list-dns-zone " . $user . " " . quoteshellarg($v_zone) . " json");
	}
	$v_differences = count(array_filter($records, fn ($r) => ($r["STATUS"] ?? "ok") !== "ok"));

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
	// The names are relative, so a provider import needs the origin.
	$v_zone_text = "\$ORIGIN " . $v_zone . ".\n\n" . implode(
		"\n\n",
		array_map(fn ($s) => "; " . $s["kind"] . " " . $s["object"] . "\n" . $s["text"], $sections),
	);

	render_page($user, $TAB, "list_dns_zone");
} else {
	$data = cli_json("h-list-dns-zones " . $user . " json");
	dns_zone_count($data);
	ksort($data);
	render_page($user, $TAB, "list_dns");
}

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
