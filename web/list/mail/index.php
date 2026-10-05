<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "MAIL";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

if (empty($_GET["domain"])) {
	$data = cli_json("h-list-mail-domains $user json");
	if ($_SESSION["userSortOrder"] == "name") {
		ksort($data);
	} else {
		$data = array_reverse($data, true);
	}

	render_page($user, $TAB, "list_mail");
} elseif (!empty($_GET["dns"])) {
	$data = array_reverse(cli_json("h-list-mail-domain " . $user . " " . quoteshellarg($_GET["domain"]) . " json"), true);
	$ips = array_reverse(cli_json("h-list-user-ips " . $user . " json"), true);
	$dkim = array_reverse(
		cli_json("h-list-mail-domain-dkim-dns " . $user . " " . quoteshellarg($_GET["domain"]) . " json"),
		true,
	);

	render_page($user, $TAB, "list_mail_dns");
} else {
	$data = cli_json("h-list-mail-accounts " . $user . " " . quoteshellarg($_GET["domain"]) . " json");
	// A domain's suspension leaves its account records alone, so an account suspended on its own stays so.
	$v_mail_domain = cli_json("h-list-mail-domain " . $user . " " . quoteshellarg($_GET["domain"]) . " json");
	$v_domain_webmail = is_array($v_mail_domain) ? reset($v_mail_domain)["WEBMAIL"] ?? "" : "";
	if (is_array($v_mail_domain) && (reset($v_mail_domain)["SUSPENDED"] ?? "") == "yes" && is_array($data)) {
		foreach ($data as $key => $value) {
			$data[$key]["SUSPENDED"] = "yes";
		}
	}
	if ($_SESSION["userSortOrder"] == "name") {
		ksort($data);
	} else {
		$data = array_reverse($data, true);
	}

	render_page($user, $TAB, "list_mail_acc");
}

$_SESSION["back"] = $_SERVER["REQUEST_URI"];
