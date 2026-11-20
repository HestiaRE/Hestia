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
				$_SESSION["ok_msg"] = tohtml(sprintf(_("Account %s has been added."), (string) $_POST["v_account"]));
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
	// Every zone of the shown user an account holds, one request each at the provider's pace: in the background,
	// thirty zones take most of a minute.
	verify_csrf($_GET);
	exec(HESTIA_CMD . "h-check-dns-zones " . $user . " background", $output, $return_var);
	check_return_code($return_var, $output);
	unset($output);
	if (empty($_SESSION["error_msg"])) {
		$_SESSION["ok_msg"] = tohtml(_("The zones are being compared in the background. Each result shows up here as it lands."));
	}
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
} elseif (isset($_GET["zone"], $_GET["exception"])) {
	// One row kept as an exception with what the provider holds for it as the page showed it, or lifted again. The
	// owner is the section's; nothing goes to the provider.
	verify_csrf($_POST);
	$v_zone = strtolower((string) $_GET["zone"]);
	$v_record = (string) ($_POST["v_record"] ?? "");
	$v_type = (string) ($_POST["v_type"] ?? "");
	$lift = ($_POST["v_action"] ?? "") === "lift";
	$args = [(string) ($_POST["v_owner"] ?? ""), $v_zone, $v_record, $v_type];
	if (!$lift) {
		foreach ((array) ($_POST["v_values"] ?? []) as $v) {
			$args[] = (string) $v;
		}
	}
	$command = $lift ? "h-delete-dns-zone-exception " : "h-add-dns-zone-exception ";
	exec(HESTIA_CMD . $command . implode(" ", array_map(fn ($a) => quoteshellarg($a), $args)), $output, $return_var);
	check_return_code($return_var, $output);
	unset($output);
	if (empty($_SESSION["error_msg"])) {
		$msg = $lift ? _("The exception for %s %s has been lifted.") : _("%s %s is kept as an exception.");
		$_SESSION["ok_msg"] = tohtml(sprintf($msg, $v_record, $v_type));
	}
	$anchor = (string) ($_POST["v_anchor"] ?? "");
	header("Location: /list/dns/?" . http_build_query(["zone" => $v_zone]) . ($anchor !== "" ? "#" . rawurlencode($anchor) : ""));
	exit();
} elseif (isset($_GET["zone"], $_GET["orphan"])) {
	// An orphan kept: HestiaRE forgets it wrote the record, which stays at the provider as a foreign one.
	verify_csrf($_POST);
	$v_zone = strtolower((string) $_GET["zone"]);
	$v_record = (string) ($_POST["v_record"] ?? "");
	$v_type = (string) ($_POST["v_type"] ?? "");
	$args = [(string) ($_POST["v_owner"] ?? ""), $v_zone, $v_record, $v_type];
	exec(HESTIA_CMD . "h-delete-dns-zone-written " . implode(" ", array_map(fn ($a) => quoteshellarg($a), $args)), $output, $return_var);
	check_return_code($return_var, $output);
	unset($output);
	if (empty($_SESSION["error_msg"])) {
		$_SESSION["ok_msg"] = tohtml(sprintf(_("%s %s is kept as a foreign record."), $v_record, $v_type));
	}
	header("Location: /list/dns/?" . http_build_query(["zone" => $v_zone]));
	exit();
} elseif (isset($_GET["zone"], $_GET["sync"])) {
	// One section written to the provider: a preview with its plan id, then the write of exactly that plan. The
	// owner is the section's, which may be a customer the real admin is looking at.
	$v_zone = strtolower((string) $_GET["zone"]);
	$v_section = (string) $_GET["sync"];
	$v_object = (string) ($_GET["object"] ?? "");
	$v_owner = (string) ($_GET["owner"] ?? "");
	// What the admin confirmed on the preview: the write carries the same, or its plan id no longer matches.
	$v_confirm = array_values(array_intersect(["cname", "include"], explode(",", (string) ($_GET["confirm"] ?? ""))));
	$args = implode(" ", array_map(fn ($a) => quoteshellarg($a), [$v_owner, $v_zone, $v_section, $v_object]));
	$confirm_arg = quoteshellarg(implode(",", $v_confirm));
	$prefix = ["mail" => "obj-mail-", "orphan" => "obj-orphan-"][$v_section] ?? "obj-";
	$back = "/list/dns/?" . http_build_query(["zone" => $v_zone]) . "#" . rawurlencode($prefix . $v_object);
	if (!empty($_POST["v_plan"])) {
		verify_csrf($_POST);
		exec(HESTIA_CMD . "h-sync-dns-zone " . $args . " " . quoteshellarg((string) $_POST["v_plan"]) . " plain " . $confirm_arg, $output, $return_var);
		check_return_code($return_var, $output);
		if (empty($_SESSION["error_msg"])) {
			// ok_msg is shown as HTML.
			$_SESSION["ok_msg"] = tohtml(sprintf(_("%s %s has been written to the provider and read back."), $v_section, $v_object));
		}
		unset($output);
		header("Location: " . $back);
		exit();
	}
	exec(HESTIA_CMD . "h-sync-dns-zone " . $args . " preview json " . $confirm_arg, $output, $return_var);
	$preview = json_decode(implode("", $output), true);
	if ($return_var !== 0 || !is_array($preview)) {
		check_return_code($return_var ?: 1, $output);
		unset($output);
		header("Location: " . $back);
		exit();
	}
	unset($output);
	$v_plan = (string) ($preview["PLAN"] ?? "");
	$ops = $preview["OPS"] ?? [];
	$v_writes = count(array_filter($ops, fn ($o) => ($o["OP"] ?? "") !== "skip"));
	// The confirmations the plan offers, and those already given.
	$v_confirmable = array_values(array_unique(array_filter(array_map(fn ($o) => (string) ($o["CONFIRM"] ?? ""), $ops))));
	$v_confirm_query = fn (array $c) => "/list/dns/?" . http_build_query(["zone" => $v_zone, "sync" => $v_section,
		"object" => $v_object, "owner" => $v_owner] + ($c ? ["confirm" => implode(",", $c)] : []));
	render_page($user, $TAB, "list_dns_sync");
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
	$orphans = [];
	$v_delegation = null;
	if ($v_account !== "") {
		exec(HESTIA_CMD . "h-check-dns-zone " . $user . " " . quoteshellarg($v_zone) . " json", $output, $return_var);
		$checked = json_decode(implode("", $output), true);
		if ($return_var === 0 && is_array($checked)) {
			$v_checked = true;
			$records = [];
			foreach ($checked as $r) {
				if ($r["SECTION"] === "delegation") {
					$v_delegation = $r;
				} elseif ($r["SECTION"] === "foreign") {
					$foreign[] = $r;
				} elseif ($r["SECTION"] === "hidden") {
					$hidden[] = $r;
				} elseif ($r["SECTION"] === "orphan") {
					// Grouped as they are removed: per object and owner the records were written for.
					$key = $r["OBJECT"] . "\t" . $r["OWNER"];
					$orphans[$key] ??= ["object" => $r["OBJECT"], "owner" => $r["OWNER"], "rows" => []];
					$orphans[$key]["rows"][] = $r;
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
	// Counted as DNSP_DIFFS counts, so the page and the zone list agree.
	$v_differences = count(array_filter($records, fn ($r) => !in_array($r["STATUS"] ?? "ok", ["ok", "exception"], true)))
		+ ($v_delegation !== null && $v_delegation["STATUS"] !== "ok" ? 1 : 0)
		+ array_sum(array_map(fn ($o) => count($o["rows"]), $orphans));

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
