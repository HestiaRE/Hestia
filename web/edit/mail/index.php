<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "MAIL";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

if (empty($_GET["domain"])) {
	header("Location: /list/mail/");
	exit();
}

if ($_SESSION["userContext"] === "admin" && !empty($_GET["user"])) {
	$user = quoteshellarg($_GET["user"]);
	$user_plain = htmlentities($_GET["user"]);
}

$v_username = $user;

if (!empty($_GET["domain"]) && empty($_GET["account"])) {
	$v_domain = $_GET["domain"];

	$webmail_clients = cli_json("h-list-sys-webmail json");

	exec(
		HESTIA_CMD . "h-list-mail-domain " . $user . " " . quoteshellarg($v_domain) . " json",
		$output,
		$return_var,
	);
	$data = json_decode(implode("", $output), true);
	check_return_code_redirect($return_var, $output, "/list/mail/");
	unset($output);

	$v_antispam = $data[$v_domain]["ANTISPAM"];
	$v_reject = $data[$v_domain]["REJECT"];
	$v_antivirus = $data[$v_domain]["ANTIVIRUS"];
	$v_dkim = $data[$v_domain]["DKIM"];
	$v_catchall = $data[$v_domain]["CATCHALL"];
	$v_rate = $data[$v_domain]["RATE_LIMIT"];
	$v_date = $data[$v_domain]["DATE"];
	$v_time = $data[$v_domain]["TIME"];
	$v_suspended = $data[$v_domain]["SUSPENDED"];
	$v_webmail_alias = $data[$v_domain]["WEBMAIL_ALIAS"];
	$v_webmail = $data[$v_domain]["WEBMAIL"];
	$v_smtp_relay = $data[$v_domain]["U_SMTP_RELAY"];
	$v_smtp_relay_host = $data[$v_domain]["U_SMTP_RELAY_HOST"];
	$v_smtp_relay_port = $data[$v_domain]["U_SMTP_RELAY_PORT"];
	$v_smtp_relay_user = $data[$v_domain]["U_SMTP_RELAY_USERNAME"];

	$exclude_data = cli_json("h-list-mail-domain-relay-exclude " . $user . " " . quoteshellarg($v_domain) . " json");
	$v_smtp_relay_exclude = str_replace(
		",",
		"\n",
		$exclude_data[$v_domain]["RELAY_EXCLUDE"] ?? "",
	);

	// Empty means the server default applies.
	$v_spam_score = $data[$v_domain]["U_SPAM_SCORE"] ?? "";
	$v_spam_reject_score = $data[$v_domain]["U_SPAM_REJECT_SCORE"] ?? "";
	$v_spam_subject_tag = $data[$v_domain]["U_SPAM_SUBJECT_TAG"] ?? "";

	$whitelist_data = cli_json("h-list-mail-domain-spam-whitelist " . $user . " " . quoteshellarg($v_domain) . " json");
	$v_spam_whitelist = str_replace(
		",",
		"\n",
		$whitelist_data[$v_domain]["SPAM_WHITELIST"] ?? "",
	);
	$blacklist_data = cli_json("h-list-mail-domain-spam-blacklist " . $user . " " . quoteshellarg($v_domain) . " json");
	$v_spam_blacklist = str_replace(
		",",
		"\n",
		$blacklist_data[$v_domain]["SPAM_BLACKLIST"] ?? "",
	);

	if ($v_suspended == "yes") {
		$v_status = "suspended";
	} else {
		$v_status = "active";
	}

	$v_ssl = $data[$v_domain]["SSL"];
	if (!empty($v_ssl)) {
		$ssl_str = cli_json("h-list-mail-domain-ssl " . $user . " " . quoteshellarg($v_domain) . " json");
		$v_ssl_crt = $ssl_str[$v_domain]["CRT"];
		$v_ssl_key = $ssl_str[$v_domain]["KEY"];
		$v_ssl_ca = $ssl_str[$v_domain]["CA"];
		$v_ssl_subject = $ssl_str[$v_domain]["SUBJECT"];
		$v_ssl_aliases = $ssl_str[$v_domain]["ALIASES"];
		$v_ssl_not_before = $ssl_str[$v_domain]["NOT_BEFORE"];
		$v_ssl_not_after = $ssl_str[$v_domain]["NOT_AFTER"];
		$v_ssl_signature = $ssl_str[$v_domain]["SIGNATURE"];
		$v_ssl_pub_key = $ssl_str[$v_domain]["PUB_KEY"];
		$v_ssl_issuer = $ssl_str[$v_domain]["ISSUER"];
	}
	$v_letsencrypt = $data[$v_domain]["LETSENCRYPT"];
	if (empty($v_letsencrypt)) {
		$v_letsencrypt = "no";
	}
}

if (!empty($_GET["domain"]) && !empty($_GET["account"])) {
	$v_domain = $_GET["domain"];

	$v_account = $_GET["account"];
	exec(
		HESTIA_CMD .
			"h-list-mail-account " .
			$user .
			" " .
			quoteshellarg($v_domain) .
			" " .
			quoteshellarg($v_account) .
			" 'json'",
		$output,
		$return_var,
	);
	$data = json_decode(implode("", $output), true);
	check_return_code_redirect($return_var, $output, "/list/mail/");
	unset($output);

	$v_username = $user;
	$v_password = "";
	$v_aliases = str_replace(",", "\n", $data[$v_account]["ALIAS"]);
	$valiases = explode(",", $data[$v_account]["ALIAS"]);
	$v_fwd = str_replace(",", "\n", $data[$v_account]["FWD"]);
	if ($v_fwd == ":blackhole:") {
		$v_fwd = "";
		$v_blackhole = "yes";
	} else {
		$v_blackhole = "no";
	}
	$vfwd = explode(",", $data[$v_account]["FWD"]);
	$v_fwd_only = $data[$v_account]["FWD_ONLY"];
	$v_rate = $data[$v_account]["RATE_LIMIT"];
	$v_quota = $data[$v_account]["QUOTA"];
	$v_autoreply = $data[$v_account]["AUTOREPLY"];
	$v_suspended = $data[$v_account]["SUSPENDED"];
	$v_webmail_alias = $data[$v_account]["WEBMAIL_ALIAS"];
	if (empty($v_send_email)) {
		$v_send_email = "";
	}
	if ($v_suspended == "yes") {
		$v_status = "suspended";
	} else {
		$v_status = "active";
	}
	$v_date = $data[$v_account]["DATE"];
	$v_time = $data[$v_account]["TIME"];

	// Always asked: with Sieve the source (webmail format or a custom script) decides the form, not the switch.
	$autoreply_str = cli_json(
		"h-list-mail-account-autoreply " . $user . " " . quoteshellarg($v_domain) . " " . quoteshellarg($v_account) . " json",
	);
	$v_autoreply_source = $autoreply_str[$v_account]["SOURCE"] ?? "exim";
	if ($v_autoreply == "yes") {
		$v_autoreply_message = $autoreply_str[$v_account]["MSG"];
	} else {
		$v_autoreply_message = "";
	}
}

// One gate per conditional control, for render and POST alike; webmail also needs IMAP.
$offer_webmail = !empty($_SESSION["IMAP_SYSTEM"]) && !empty($_SESSION["WEBMAIL_SYSTEM"]);
$offer_antispam = !empty($_SESSION["ANTISPAM_SYSTEM"]);
$offer_antivirus = !empty($_SESSION["ANTIVIRUS_SYSTEM"]);
$spam_tuning_allowed =
	$_SESSION["userContext"] === "admin" ||
	($_SESSION["POLICY_SPAM_CUSTOMER_TUNING"] ?? "yes") !== "no";

if (!empty($_POST["save"]) && !empty($_GET["domain"]) && empty($_GET["account"])) {
	verify_csrf($_POST);

	exec(
		HESTIA_CMD . "h-list-mail-domain " . $user . " " . quoteshellarg($v_domain) . " json",
		$output,
		$return_var,
	);
	$data = json_decode(implode("", $output), true);
	check_return_code_redirect($return_var, $output, "/list/mail/");
	unset($output);

	$post_antispam = post_checkbox("v_antispam", $offer_antispam, $v_antispam, "yes", "no");
	$post_antivirus = post_checkbox("v_antivirus", $offer_antivirus, $v_antivirus, "yes", "no");
	$post_reject = post_checkbox("v_reject", $offer_antispam, $v_reject, "yes", "no");

	if ($v_antispam == "yes" && $post_antispam != "yes" && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-delete-mail-domain-antispam " .
				$v_username .
				" " .
				quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_antispam = "no";
		$v_reject = "no";
		unset($output);
	}

	if ($v_antispam == "no" && $post_antispam == "yes" && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-add-mail-domain-antispam " .
				$v_username .
				" " .
				quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_antispam = "yes";
		unset($output);
	}

	if ($v_antivirus == "yes" && $post_antivirus != "yes" && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-delete-mail-domain-antivirus " .
				$v_username .
				" " .
				quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_antivirus = "no";
		unset($output);
	}

	if ($v_antivirus == "no" && $post_antivirus == "yes" && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-add-mail-domain-antivirus " .
				$v_username .
				" " .
				quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_antivirus = "yes";
		unset($output);
	}

	if ($v_dkim == "yes" && empty($_POST["v_dkim"]) && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-delete-mail-domain-dkim " .
				$v_username .
				" " .
				quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_dkim = "no";
		unset($output);
	}

	if ($v_dkim == "no" && !empty($_POST["v_dkim"]) && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD . "h-add-mail-domain-dkim " . $v_username . " " . quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_dkim = "yes";
		unset($output);
	}

	if (!empty($v_catchall) && empty($_POST["v_catchall"]) && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-delete-mail-domain-catchall " .
				$v_username .
				" " .
				quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_catchall = "";
		unset($output);
	}

	if (
		$_SESSION["userContext"] == "admin" &&
		$v_rate != $_POST["v_rate"] &&
		empty($_SESSION["error_msg"])
	) {
		if (empty($_POST["v_rate"])) {
			$v_rate = "system";
		} else {
			$v_rate = quoteshellarg($_POST["v_rate"]);
		}
		exec(
			HESTIA_CMD .
				"h-change-mail-domain-rate-limit " .
				$v_username .
				" " .
				quoteshellarg($v_domain) .
				" " .
				$v_rate,
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		if ($v_rate == "system") {
			$v_rate = "";
		}
		unset($output);
	}

	if ($post_reject == "yes" && $v_antispam == "yes" && $v_reject != "yes") {
		exec(
			HESTIA_CMD . "h-add-mail-domain-reject " . $user . " " . quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_reject = "yes";
		unset($output);
	}
	if ($post_reject != "yes" && $v_reject == "yes") {
		exec(
			HESTIA_CMD . "h-delete-mail-domain-reject " . $user . " " . quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		$v_reject = "";
		unset($output);
	}

	// The sensitivity field marks the tuning UI as rendered; without it nothing here may reset a value.
	if (empty($_SESSION["error_msg"]) && isset($_POST["v_spam_sensitivity"])) {
		// Keep in step with the presets in web/templates/pages/edit_mail.php.
		$spam_presets = ["tolerant" => "7.0", "normal" => "5.0", "strict" => "3.5"];
		$spam_is_admin = $_SESSION["userContext"] === "admin";
		if ($offer_antispam && $spam_tuning_allowed) {
			// "default" is no preset, so it yields "" and clears the override.
			$spam_sensitivity = $_POST["v_spam_sensitivity"];
			$new_spam_score = $spam_presets[$spam_sensitivity] ?? "";
			if ($spam_sensitivity === "custom") {
				$new_spam_score = trim($_POST["v_spam_score"] ?? "");
				if (!preg_match('/^\d{1,2}(\.\d)?$/', $new_spam_score)) {
					$_SESSION["error_msg"] = _("Invalid spam mark threshold.");
				}
			}
			// Only a changed value is held to the policy, so one an admin set outside it does not block the form.
			if (
				empty($_SESSION["error_msg"]) &&
				$new_spam_score !== "" &&
				$new_spam_score !== $v_spam_score &&
				!$spam_is_admin
			) {
				$score_min = (float) ($_SESSION["POLICY_SPAM_SCORE_MIN"] ?? "3.0");
				$score_max = (float) ($_SESSION["POLICY_SPAM_SCORE_MAX"] ?? "10.0");
				if ((float) $new_spam_score < $score_min || (float) $new_spam_score > $score_max) {
					$_SESSION["error_msg"] = sprintf(
						_("Spam mark threshold must be between %s and %s."),
						$score_min,
						$score_max,
					);
				}
			}
			if (empty($_SESSION["error_msg"]) && $new_spam_score !== $v_spam_score) {
				if ($new_spam_score === "") {
					$output = [];
					exec(
						HESTIA_CMD .
							"h-delete-mail-domain-spam-score " .
							$v_username .
							" " .
							quoteshellarg($v_domain),
						$output,
						$return_var,
					);
					check_return_code($return_var, $output);
				} else {
					exec(
						HESTIA_CMD .
							"h-change-mail-domain-spam-score " .
							$v_username .
							" " .
							quoteshellarg($v_domain) .
							" " .
							quoteshellarg($new_spam_score),
						$output,
						$return_var,
					);
				}
				check_return_code($return_var, $output);
				unset($output);
				if (empty($_SESSION["error_msg"])) {
					$v_spam_score = $new_spam_score;
				}
			}

			// Stored even while reject is off: exim applies it only with reject_spam on.
			$new_reject_score = trim($_POST["v_spam_reject_score"] ?? "");
			if (
				empty($_SESSION["error_msg"]) &&
				$new_reject_score !== "" &&
				!preg_match('/^\d{1,2}(\.\d)?$/', $new_reject_score)
			) {
				$_SESSION["error_msg"] = _("Invalid spam reject threshold.");
			}
			if (
				empty($_SESSION["error_msg"]) &&
				$new_reject_score !== "" &&
				$new_reject_score !== $v_spam_reject_score &&
				!$spam_is_admin
			) {
				$reject_min = (float) ($_SESSION["POLICY_SPAM_REJECT_SCORE_MIN"] ?? "8.0");
				$reject_max = (float) ($_SESSION["POLICY_SPAM_REJECT_SCORE_MAX"] ?? "20.0");
				if (
					(float) $new_reject_score < $reject_min ||
					(float) $new_reject_score > $reject_max
				) {
					$_SESSION["error_msg"] = sprintf(
						_("Spam reject threshold must be between %s and %s."),
						$reject_min,
						$reject_max,
					);
				}
			}
			if (empty($_SESSION["error_msg"]) && $new_reject_score !== $v_spam_reject_score) {
				if ($new_reject_score === "") {
					$output = [];
					exec(
						HESTIA_CMD .
							"h-delete-mail-domain-spam-reject-score " .
							$v_username .
							" " .
							quoteshellarg($v_domain),
						$output,
						$return_var,
					);
					check_return_code($return_var, $output);
				} else {
					exec(
						HESTIA_CMD .
							"h-change-mail-domain-spam-reject-score " .
							$v_username .
							" " .
							quoteshellarg($v_domain) .
							" " .
							quoteshellarg($new_reject_score),
						$output,
						$return_var,
					);
				}
				check_return_code($return_var, $output);
				unset($output);
				if (empty($_SESSION["error_msg"])) {
					$v_spam_reject_score = $new_reject_score;
				}
			}

			// Charset kept in step with bin/h-change-mail-domain-spam-subject-tag.
			$new_subject_tag = trim($_POST["v_spam_subject_tag"] ?? "");
			if (
				empty($_SESSION["error_msg"]) &&
				$new_subject_tag !== "" &&
				!preg_match(
					'/^[A-Za-z0-9\[\]()*+._:!#=-]([A-Za-z0-9\[\]()*+._:!#= -]{0,30}[A-Za-z0-9\[\]()*+._:!#=-])?$/',
					$new_subject_tag,
				)
			) {
				$_SESSION["error_msg"] = _("Invalid spam subject tag.");
			}
			if (empty($_SESSION["error_msg"]) && $new_subject_tag !== $v_spam_subject_tag) {
				if ($new_subject_tag === "") {
					$output = [];
					exec(
						HESTIA_CMD .
							"h-delete-mail-domain-spam-subject-tag " .
							$v_username .
							" " .
							quoteshellarg($v_domain),
						$output,
						$return_var,
					);
					check_return_code($return_var, $output);
				} else {
					exec(
						HESTIA_CMD .
							"h-change-mail-domain-spam-subject-tag " .
							$v_username .
							" " .
							quoteshellarg($v_domain) .
							" " .
							quoteshellarg($new_subject_tag),
						$output,
						$return_var,
					);
				}
				check_return_code($return_var, $output);
				unset($output);
				if (empty($_SESSION["error_msg"])) {
					$v_spam_subject_tag = $new_subject_tag;
				}
			}

			foreach (
				[
					"whitelist" => "v_spam_whitelist",
					"blacklist" => "v_spam_blacklist",
				] as $spam_list_kind => $spam_list_var
			) {
				if (!empty($_SESSION["error_msg"])) {
					break;
				}
				$current_entries = array_filter(explode("\n", $$spam_list_var));
				$wentries = preg_replace("/\n/", " ", $_POST[$spam_list_var] ?? "");
				$wentries = preg_replace("/,/", " ", $wentries);
				$wentries = preg_replace("/\s+/", " ", $wentries);
				$wentries = strtolower(trim($wentries));
				$entries = array_filter(explode(" ", $wentries));
				foreach (array_diff($current_entries, $entries) as $spam_sender) {
					if (empty($_SESSION["error_msg"])) {
						exec(
							HESTIA_CMD .
								"h-delete-mail-domain-spam-" .
								$spam_list_kind .
								" " .
								$v_username .
								" " .
								quoteshellarg($v_domain) .
								" " .
								quoteshellarg($spam_sender),
							$output,
							$return_var,
						);
						check_return_code($return_var, $output);
						unset($output);
					}
				}
				foreach (array_diff($entries, $current_entries) as $spam_sender) {
					if (empty($_SESSION["error_msg"])) {
						exec(
							HESTIA_CMD .
								"h-add-mail-domain-spam-" .
								$spam_list_kind .
								" " .
								$v_username .
								" " .
								quoteshellarg($v_domain) .
								" " .
								quoteshellarg($spam_sender),
							$output,
							$return_var,
						);
						check_return_code($return_var, $output);
						unset($output);
					}
				}
				if (empty($_SESSION["error_msg"])) {
					$$spam_list_var = implode("\n", $entries);
				}
			}
		}
	}

	if (!empty($v_catchall) && !empty($_POST["v_catchall"]) && empty($_SESSION["error_msg"])) {
		if ($v_catchall != $_POST["v_catchall"]) {
			$v_catchall = quoteshellarg($_POST["v_catchall"]);
			exec(
				HESTIA_CMD .
					"h-change-mail-domain-catchall " .
					$v_username .
					" " .
					quoteshellarg($v_domain) .
					" " .
					$v_catchall,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
		}
	}

	if (empty($v_catchall) && !empty($_POST["v_catchall"]) && empty($_SESSION["error_msg"])) {
		$v_catchall = quoteshellarg($_POST["v_catchall"]);
		exec(
			HESTIA_CMD .
				"h-add-mail-domain-catchall " .
				$v_username .
				" " .
				quoteshellarg($v_domain) .
				" " .
				$v_catchall,
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
	}

	$post_webmail = post_or_keep("v_webmail", $offer_webmail, $v_webmail);
	if ($offer_webmail) {
		if (empty($_SESSION["error_msg"])) {
			if (!empty($post_webmail) && $post_webmail !== $v_webmail) {
				exec(
					HESTIA_CMD .
						"h-add-mail-domain-webmail " .
						$user .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($post_webmail) .
						" yes",
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				if ($return_var == 0) {
					$v_webmail = $post_webmail;
				}
				unset($output);
			}
		}
	}

	if ($offer_webmail) {
		if (empty($post_webmail) && !empty($v_webmail)) {
			if (empty($_SESSION["error_msg"])) {
				exec(
					HESTIA_CMD .
						"h-delete-mail-domain-webmail " .
						$user .
						" " .
						quoteshellarg($v_domain) .
						" yes",
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				$v_webmail = "";
				unset($output);
			}
		}
	}

	if (
		$v_letsencrypt == "no" &&
		empty($_POST["v_letsencrypt"]) &&
		$v_ssl == "yes" &&
		!empty($_POST["v_ssl"]) &&
		empty($_SESSION["error_msg"])
	) {
		if (
			$v_ssl_crt != str_replace("\r\n", "\n", $_POST["v_ssl_crt"]) ||
			$v_ssl_key != str_replace("\r\n", "\n", $_POST["v_ssl_key"]) ||
			$v_ssl_ca != str_replace("\r\n", "\n", $_POST["v_ssl_ca"])
		) {
			$tmpdir = private_tmpdir();
			if ($tmpdir !== false) {
				if (!empty($_POST["v_ssl_crt"])) {
					$fp = fopen($tmpdir . "/" . $v_domain . ".crt", "w");
					fwrite($fp, str_replace("\r\n", "\n", $_POST["v_ssl_crt"]));
					fwrite($fp, "\n");
					fclose($fp);
				}

				if (!empty($_POST["v_ssl_key"])) {
					$fp = fopen($tmpdir . "/" . $v_domain . ".key", "w");
					fwrite($fp, str_replace("\r\n", "\n", $_POST["v_ssl_key"]));
					fwrite($fp, "\n");
					fclose($fp);
				}

				if (!empty($_POST["v_ssl_ca"])) {
					$fp = fopen($tmpdir . "/" . $v_domain . ".ca", "w");
					fwrite($fp, str_replace("\r\n", "\n", $_POST["v_ssl_ca"]));
					fwrite($fp, "\n");
					fclose($fp);
				}

				exec(
					HESTIA_CMD .
						"h-change-mail-domain-sslcert " .
						$user .
						" " .
						quoteshellarg($v_domain) .
						" " .
						$tmpdir .
						" 'no'",
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
				$restart_web = "yes";
				$restart_proxy = "yes";

				$ssl_str = cli_json("h-list-mail-domain-ssl " . $user . " " . quoteshellarg($v_domain) . " json");
				$v_ssl_crt = $ssl_str[$v_domain]["CRT"];
				$v_ssl_key = $ssl_str[$v_domain]["KEY"];
				$v_ssl_ca = $ssl_str[$v_domain]["CA"];
				$v_ssl_subject = $ssl_str[$v_domain]["SUBJECT"];
				$v_ssl_aliases = $ssl_str[$v_domain]["ALIASES"];
				$v_ssl_not_before = $ssl_str[$v_domain]["NOT_BEFORE"];
				$v_ssl_not_after = $ssl_str[$v_domain]["NOT_AFTER"];
				$v_ssl_signature = $ssl_str[$v_domain]["SIGNATURE"];
				$v_ssl_pub_key = $ssl_str[$v_domain]["PUB_KEY"];
				$v_ssl_issuer = $ssl_str[$v_domain]["ISSUER"];

				if (!empty($_POST["v_ssl_crt"])) {
					unlink($tmpdir . "/" . $v_domain . ".crt");
				}
				if (!empty($_POST["v_ssl_key"])) {
					unlink($tmpdir . "/" . $v_domain . ".key");
				}
				if (!empty($_POST["v_ssl_ca"])) {
					unlink($tmpdir . "/" . $v_domain . ".ca");
				}
				rmdir($tmpdir);
			}
		}
	}

	if (
		$v_letsencrypt == "yes" &&
		(empty($_POST["v_letsencrypt"]) || empty($_POST["v_ssl"])) &&
		empty($_SESSION["error_msg"])
	) {
		exec(
			HESTIA_CMD .
				"h-delete-letsencrypt-domain " .
				$user .
				" " .
				quoteshellarg($v_domain) .
				" '' 'yes'",
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
		$v_ssl_crt = "";
		$v_ssl_key = "";
		$v_ssl_ca = "";
		$v_letsencrypt = "no";
		$v_letsencrypt_deleted = "yes";
		$v_ssl = "no";
		$restart_mail = "yes";
	}

	if ($v_ssl == "yes" && empty($_POST["v_ssl"]) && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD . "h-delete-mail-domain-ssl " . $v_username . " " . quoteshellarg($v_domain),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
		$v_ssl_crt = "";
		$v_ssl_key = "";
		$v_ssl_ca = "";
		$v_ssl = "no";
		$restart_mail = "yes";
	}

	if (
		!empty($_POST["v_ssl"]) &&
		$v_letsencrypt == "no" &&
		!empty($_POST["v_letsencrypt"]) &&
		empty($_SESSION["error_msg"])
	) {
		exec(
			HESTIA_CMD .
				"h-add-letsencrypt-domain " .
				$user .
				" " .
				quoteshellarg($v_domain) .
				" ' ' 'yes'",
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
		$v_letsencrypt = "yes";
		$v_ssl = "yes";
		$restart_mail = "yes";
	}

	if (
		$v_ssl == "no" &&
		!empty($_POST["v_ssl"]) &&
		empty($v_letsencrypt_deleted) &&
		empty($_SESSION["error_msg"])
	) {
		if (empty($_POST["v_ssl_crt"])) {
			$errors[] = "ssl certificate";
		}
		if (empty($_POST["v_ssl_key"])) {
			$errors[] = "ssl key";
		}
		if (!empty($errors[0])) {
			foreach ($errors as $i => $error) {
				if ($i == 0) {
					$error_msg = $error;
				} else {
					$error_msg = $error_msg . ", " . $error;
				}
			}
			$_SESSION["error_msg"] = sprintf(_('Field "%s" can not be blank.'), $error_msg);
		} else {
			$tmpdir = private_tmpdir();
			if ($tmpdir !== false) {
				if (!empty($_POST["v_ssl_crt"])) {
					$fp = fopen($tmpdir . "/" . $v_domain . ".crt", "w");
					fwrite($fp, str_replace("\r\n", "\n", $_POST["v_ssl_crt"]));
					fclose($fp);
				}

				if (!empty($_POST["v_ssl_key"])) {
					$fp = fopen($tmpdir . "/" . $v_domain . ".key", "w");
					fwrite($fp, str_replace("\r\n", "\n", $_POST["v_ssl_key"]));
					fclose($fp);
				}

				if (!empty($_POST["v_ssl_ca"])) {
					$fp = fopen($tmpdir . "/" . $v_domain . ".ca", "w");
					fwrite($fp, str_replace("\r\n", "\n", $_POST["v_ssl_ca"]));
					fclose($fp);
				}
				exec(
					HESTIA_CMD .
						"h-add-mail-domain-ssl " .
						$user .
						" " .
						quoteshellarg($v_domain) .
						" " .
						$tmpdir .
						" 'no'",
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
				$v_ssl = "yes";
				$restart_web = "yes";
				$restart_proxy = "yes";

				$ssl_str = cli_json("h-list-mail-domain-ssl " . $user . " " . quoteshellarg($v_domain) . " json");
				$v_ssl_crt = $ssl_str[$v_domain]["CRT"];
				$v_ssl_key = $ssl_str[$v_domain]["KEY"];
				$v_ssl_ca = $ssl_str[$v_domain]["CA"];
				$v_ssl_subject = $ssl_str[$v_domain]["SUBJECT"];
				$v_ssl_aliases = $ssl_str[$v_domain]["ALIASES"];
				$v_ssl_not_before = $ssl_str[$v_domain]["NOT_BEFORE"];
				$v_ssl_not_after = $ssl_str[$v_domain]["NOT_AFTER"];
				$v_ssl_signature = $ssl_str[$v_domain]["SIGNATURE"];
				$v_ssl_pub_key = $ssl_str[$v_domain]["PUB_KEY"];
				$v_ssl_issuer = $ssl_str[$v_domain]["ISSUER"];

				if (!empty($_POST["v_ssl_crt"])) {
					unlink($tmpdir . "/" . $v_domain . ".crt");
				}
				if (!empty($_POST["v_ssl_key"])) {
					unlink($tmpdir . "/" . $v_domain . ".key");
				}
				if (!empty($_POST["v_ssl_ca"])) {
					unlink($tmpdir . "/" . $v_domain . ".ca");
				}
				rmdir($tmpdir);
			}
		}
	}

	if (empty($_SESSION["error_msg"])) {
		if (isset($_POST["v_smtp_relay"]) && !empty($_POST["v_smtp_relay_host"])) {
			if (
				$_POST["v_smtp_relay_host"] != $v_smtp_relay_host ||
				$_POST["v_smtp_relay_user"] != $v_smtp_relay_user ||
				$_POST["v_smtp_relay_port"] != $v_smtp_relay_port ||
				$_POST["v_smtp_relay_pass"] != ""
			) {
				$v_smtp_relay = true;
				$v_smtp_relay_host = quoteshellarg($_POST["v_smtp_relay_host"]);
				$v_smtp_relay_user = quoteshellarg($_POST["v_smtp_relay_user"]);
				$relay_pass_file = secret_tmpfile($_POST["v_smtp_relay_pass"]);
				if ($relay_pass_file !== false) {
					if (!empty($_POST["v_smtp_relay_port"])) {
						$v_smtp_relay_port = quoteshellarg($_POST["v_smtp_relay_port"]);
					} else {
						$v_smtp_relay_port = "587";
					}
					exec(
						HESTIA_CMD .
							"h-add-mail-domain-smtp-relay " .
							$v_username .
							" " .
							quoteshellarg($v_domain) .
							" " .
							$v_smtp_relay_host .
							" " .
							$v_smtp_relay_user .
							" " .
							quoteshellarg($relay_pass_file) .
							" " .
							$v_smtp_relay_port,
						$output,
						$return_var,
					);
					unlink($relay_pass_file);
					check_return_code($return_var, $output);
					unset($output);
				}
			}
		}
		if (!isset($_POST["v_smtp_relay"]) && $v_smtp_relay == true) {
			$v_smtp_relay = false;
			$v_smtp_relay_host = $v_smtp_relay_user = $v_smtp_relay_pass = $v_smtp_relay_port = "";
			exec(
				HESTIA_CMD .
					"h-delete-mail-domain-smtp-relay " .
					$v_username .
					" " .
					quoteshellarg($v_domain),
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
		}
	}

	// With the relay off the exclude list stays untouched, kept for a later re-enable.
	if (empty($_SESSION["error_msg"]) && isset($_POST["v_smtp_relay"])) {
		$current_excludes = array_filter(explode("\n", $v_smtp_relay_exclude));
		$wexcludes = preg_replace("/\n/", " ", $_POST["v_smtp_relay_exclude"] ?? "");
		$wexcludes = preg_replace("/,/", " ", $wexcludes);
		$wexcludes = preg_replace("/\s+/", " ", $wexcludes);
		$wexcludes = strtolower(trim($wexcludes));
		$excludes = array_filter(explode(" ", $wexcludes));
		foreach (array_diff($current_excludes, $excludes) as $exclude_domain) {
			if (empty($_SESSION["error_msg"])) {
				exec(
					HESTIA_CMD .
						"h-delete-mail-domain-relay-exclude " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($exclude_domain),
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
			}
		}
		foreach (array_diff($excludes, $current_excludes) as $exclude_domain) {
			if (empty($_SESSION["error_msg"])) {
				exec(
					HESTIA_CMD .
						"h-add-mail-domain-relay-exclude " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($exclude_domain),
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
			}
		}
		if (empty($_SESSION["error_msg"])) {
			$v_smtp_relay_exclude = implode("\n", $excludes);
		}
	}

	if (empty($_SESSION["error_msg"])) {
		$_SESSION["ok_msg"] = _("Changes have been saved.");
	}
}

if (!empty($_POST["save"]) && !empty($_GET["domain"]) && !empty($_GET["account"])) {
	verify_csrf($_POST);

	if (!empty($_POST["v_send_email"]) && empty($_SESSION["error_msg"])) {
		if (!filter_var($_POST["v_send_email"], FILTER_VALIDATE_EMAIL)) {
			$_SESSION["error_msg"] = _("Please enter a valid email address.");
		}
	}

	$v_account = $_POST["v_account"];
	$v_send_email = $_POST["v_send_email"];

	exec(
		HESTIA_CMD .
			"h-list-mail-account " .
			$user .
			" " .
			quoteshellarg($v_domain) .
			" " .
			quoteshellarg($v_account) .
			" json",
		$output,
		$return_var,
	);
	$data = json_decode(implode("", $output), true);
	check_return_code_redirect($return_var, $output, "/list/mail/");
	unset($output);

	if (!empty($_POST["v_password"]) && empty($_SESSION["error_msg"])) {
		if (!validate_password($_POST["v_password"])) {
			$_SESSION["error_msg"] = _("Password does not match the minimum requirements.");
		} else {
			$v_password = secret_tmpfile($_POST["v_password"]);
			if ($v_password !== false) {
				exec(
					HESTIA_CMD .
						"h-change-mail-account-password " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($v_account) .
						" " .
						$v_password,
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
				unlink($v_password);
				// Devices behind this IP still poll the old password; best effort, the change itself has succeeded.
				if ($return_var == 0) {
					exec(
						HESTIA_CMD .
							"h-add-mail-account-grace " .
							$v_username .
							" " .
							quoteshellarg($v_domain) .
							" " .
							quoteshellarg($v_account) .
							" " .
							quoteshellarg(get_real_user_ip()),
						$output,
						$return_var,
					);
					unset($output);
				}
			}
			$v_password = quoteshellarg($_POST["v_password"]);
		}
	}

	if ($v_quota != $_POST["v_quota"] && empty($_SESSION["error_msg"])) {
		if (empty($_POST["v_quota"])) {
			$v_quota = 0;
		} else {
			$v_quota = quoteshellarg($_POST["v_quota"]);
		}
		exec(
			HESTIA_CMD .
				"h-change-mail-account-quota " .
				$v_username .
				" " .
				quoteshellarg($v_domain) .
				" " .
				quoteshellarg($v_account) .
				" " .
				$v_quota,
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
	}
	if (
		$_SESSION["userContext"] == "admin" &&
		$v_rate != $_POST["v_rate"] &&
		empty($_SESSION["error_msg"])
	) {
		if (empty($_POST["v_rate"])) {
			$v_rate = "system";
		} else {
			$v_rate = quoteshellarg($_POST["v_rate"]);
		}
		exec(
			HESTIA_CMD .
				"h-change-mail-account-rate-limit " .
				$v_username .
				" " .
				quoteshellarg($v_domain) .
				" " .
				quoteshellarg($v_account) .
				" " .
				$v_rate,
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		if ($v_rate == "system") {
			$v_rate = "";
		}
		unset($output);
	}

	if (empty($_SESSION["error_msg"])) {
		$waliases = preg_replace("/\n/", " ", $_POST["v_aliases"]);
		$waliases = preg_replace("/,/", " ", $waliases);
		$waliases = preg_replace("/\s+/", " ", $waliases);
		$waliases = trim($waliases);
		$aliases = explode(" ", $waliases);
		$v_aliases = str_replace(" ", "\n", $waliases);
		$result = array_diff($valiases, $aliases);
		foreach ($result as $alias) {
			if (empty($_SESSION["error_msg"]) && !empty($alias)) {
				exec(
					HESTIA_CMD .
						"h-delete-mail-account-alias " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($v_account) .
						" " .
						quoteshellarg($alias),
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
			}
		}
		$result = array_diff($aliases, $valiases);
		foreach ($result as $alias) {
			if (empty($_SESSION["error_msg"]) && !empty($alias)) {
				exec(
					HESTIA_CMD .
						"h-add-mail-account-alias " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($v_account) .
						" " .
						quoteshellarg($alias),
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
			}
		}
	}
	if (empty($_SESSION["error_msg"]) && !empty($_POST["v_blackhole"])) {
		// Added before the old ones go: with no forward left, forward-only ends.
		if (!in_array(":blackhole:", $vfwd)) {
			exec(
				HESTIA_CMD .
					"h-add-mail-account-forward " .
					$v_username .
					" " .
					quoteshellarg($v_domain) .
					" " .
					quoteshellarg($v_account) .
					" :blackhole:",
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
		}
		foreach ($vfwd as $forward) {
			if (empty($_SESSION["error_msg"]) && !empty($forward) && $forward != ":blackhole:") {
				exec(
					HESTIA_CMD .
						"h-delete-mail-account-forward " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($v_account) .
						" " .
						quoteshellarg($forward),
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
			}
		}
		$v_fwd = "";
		$v_blackhole = "yes";
	}
	if (empty($_SESSION["error_msg"]) && empty($_POST["v_blackhole"])) {
		$wfwd = preg_replace("/\n/", " ", $_POST["v_fwd"]);
		$wfwd = preg_replace("/,/", " ", $wfwd);
		$wfwd = preg_replace("/\s+/", " ", $wfwd);
		$wfwd = trim($wfwd);
		$fwd = explode(" ", $wfwd);
		$v_fwd = str_replace(" ", "\n", $wfwd);
		// New ones first, for the same reason as above.
		$result = array_diff($fwd, $vfwd);
		foreach ($result as $forward) {
			if (empty($_SESSION["error_msg"]) && !empty($forward)) {
				exec(
					HESTIA_CMD .
						"h-add-mail-account-forward " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($v_account) .
						" " .
						quoteshellarg($forward),
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
			}
		}
		$result = array_diff($vfwd, $fwd);
		foreach ($result as $forward) {
			if (empty($_SESSION["error_msg"]) && !empty($forward)) {
				exec(
					HESTIA_CMD .
						"h-delete-mail-account-forward " .
						$v_username .
						" " .
						quoteshellarg($v_domain) .
						" " .
						quoteshellarg($v_account) .
						" " .
						quoteshellarg($forward),
					$output,
					$return_var,
				);
				check_return_code($return_var, $output);
				unset($output);
			}
		}
		$v_blackhole = "no";
	}

	if ($v_fwd_only == "yes" && empty($_POST["v_fwd_only"]) && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-delete-mail-account-fwd-only " .
				$v_username .
				" " .
				quoteshellarg($v_domain) .
				" " .
				quoteshellarg($v_account),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
		$v_fwd_only = "";
	}

	if ($v_fwd_only != "yes" && !empty($_POST["v_fwd_only"]) && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-add-mail-account-fwd-only " .
				$v_username .
				" " .
				quoteshellarg($v_domain) .
				" " .
				quoteshellarg($v_account),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
		$v_fwd_only = "yes";
	}

	// A custom Sieve script renders the switch read-only and posts nothing, which must not read as "off".
	$autoreply_editable = $v_autoreply_source != "custom";

	if ($autoreply_editable && $v_autoreply == "yes" && empty($_POST["v_autoreply"]) && empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"h-delete-mail-account-autoreply " .
				$v_username .
				" " .
				quoteshellarg($v_domain) .
				" " .
				quoteshellarg($v_account),
			$output,
			$return_var,
		);
		check_return_code($return_var, $output);
		unset($output);
		$v_autoreply = "no";
		$v_autoreply_message = "";
	}

	if ($autoreply_editable && !empty($_POST["v_autoreply"]) && empty($_SESSION["error_msg"])) {
		if ($v_autoreply_message != str_replace("\r\n", "\n", $_POST["v_autoreply_message"])) {
			$v_autoreply_message = str_replace("\r\n", "\n", $_POST["v_autoreply_message"]);
			$v_autoreply_message = quoteshellarg($v_autoreply_message);
			exec(
				HESTIA_CMD .
					"h-add-mail-account-autoreply " .
					$v_username .
					" " .
					quoteshellarg($v_domain) .
					" " .
					quoteshellarg($v_account) .
					" " .
					$v_autoreply_message,
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
			$v_autoreply = "yes";
			$v_autoreply_message = $_POST["v_autoreply_message"];
		}
	}

	$hostname = get_hostname();
	$webmail = "http://" . $hostname . "/" . $v_webmail_alias . "/";
	if (!empty($_SESSION["WEBMAIL_ALIAS"])) {
		$webmail = $_SESSION["WEBMAIL_ALIAS"];
	}

	if (!empty($_POST["v_send_email"]) && empty($_SESSION["error_msg"])) {
		$to = $_POST["v_send_email"];
		$template = get_email_template("email_credentials", $_SESSION["language"]);
		if (!empty($template)) {
			preg_match("/<subject>(.*?)<\/subject>/si", $template, $matches);
			$subject = $matches[1];
			$subject = str_replace(
				["{{hostname}}", "{{appname}}", "{{account}}", "{{domain}}"],
				[
					get_hostname(),
					$_SESSION["APP_NAME"],
					htmlentities(strtolower($_POST["v_account"])),
					htmlentities($_POST["v_domain"]),
				],
				$subject,
			);
			$template = str_replace($matches[0], "", $template);
		} else {
			$template = _(
				"Mail account has been created.\n" .
					"\n" .
					"Common Account Settings:\n" .
					"Username: {{account}}@{{domain}}\n" .
					"Password: {{password}}\n" .
					"Webmail: {{webmail}}\n" .
					"Hostname: {{hostname}}\n" .
					"\n" .
					"IMAP Settings\n" .
					"Authentication: Normal Password\n" .
					"SSL/TLS: Port 993\n" .
					"STARTTLS: Port 143\n" .
					"No encryption: Port 143\n" .
					"\n" .
					"POP3 Settings\n" .
					"Authentication: Normal Password\n" .
					"SSL/TLS: Port 995\n" .
					"STARTTLS: Port 110\n" .
					"No encryption: Port 110\n" .
					"\n" .
					"SMTP Settings\n" .
					"Authentication: Normal Password\n" .
					"SSL/TLS: Port 465\n" .
					"STARTTLS: Port 587\n" .
					"No encryption: Port 25\n" .
					"\n" .
					"Best regards,\n" .
					"\n" .
					"--\n" .
					"{{appname}}",
			);
		}
		if (empty($subject)) {
			$subject = str_replace(
				["{{subject}}", "{{hostname}}", "{{appname}}"],
				[
					sprintf(
						_("Email Credentials: %s@%s"),
						htmlentities(strtolower($_POST["v_account"])),
						htmlentities($_POST["v_domain"]),
					),
					get_hostname(),
					$_SESSION["APP_NAME"],
				],
				$_SESSION["SUBJECT_EMAIL"],
			);
		}

		$hostname = get_hostname();
		$from = !empty($_SESSION["FROM_EMAIL"]) ? $_SESSION["FROM_EMAIL"] : "noreply@" . $hostname;
		$from_name = !empty($_SESSION["FROM_NAME"])
			? $_SESSION["FROM_NAME"]
			: $_SESSION["APP_NAME"];

		$mailtext = translate_email($template, [
			"domain" => htmlentities($_POST["v_domain"]),
			"account" => htmlentities(strtolower($_POST["v_account"])),
			"password" => htmlentities($_POST["v_password"]),
			"webmail" => $webmail . "." . htmlentities($_POST["v_domain"]),
			"hostname" => "mail." . htmlentities($_POST["v_domain"]),
			"appname" => $_SESSION["APP_NAME"],
		]);

		send_email($to, $subject, $mailtext, $from, $from_name);
	}

	if (empty($_SESSION["error_msg"])) {
		$_SESSION["ok_msg"] = _("Changes have been saved.");
	}
}

if (empty($_GET["account"])) {
	render_page($user, $TAB, "edit_mail");
} else {
	render_page($user, $TAB, "edit_mail_acc");
}

unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
