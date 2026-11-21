<?php

use function Hestiacp\quoteshellarg\quoteshellarg;

// Functions for internationalization
// I18N support information here

putenv("LANGUAGE=" . detect_user_language());
// Not C first: glibc 2.39+ ignores LANGUAGE under C.UTF-8 (#1157), panel_locale_apply generates en_US.UTF-8.
setlocale(LC_ALL, "en_US.UTF-8", "C.UTF-8", "C");

// Also named in .gitea/tools/i18n.sh and check_panel_catalogs in .gitea/tools/check-tree.sh.
$domain = "hestia";
$localedir = "/usr/local/hestia/web/locale";
bindtextdomain($domain, $localedir);
textdomain($domain);

/**
 * Detects user language from Accept-Language HTTP header.
 * @param string Fallback language (default: 'en')
 * @return string Language code (such as 'en' and 'ja')
 */
function detect_user_language()
{
	if (!empty($_SESSION["language"])) {
		return $_SESSION["language"];
	} elseif (!empty($_SESSION["LANGUAGE"])) {
		return $_SESSION["LANGUAGE"];
	} else {
		return "en";
	}
}
/**
 * Translate ISO2 to "Language"
 * nl = Dutch, de = German
 * @param string iso2 code
 * @return string Language
 */
function translate_json($string)
{
	$json = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/locale/languages.json");
	$json_a = json_decode($json, true);
	return $json_a[$string][0] . " (" . $json_a[$string . "_locale"][0] . ")";
}
/**
 * Support translation strings that contains html
 */
function htmlify_trans($string, $closingTag)
{
	$arguments = func_get_args();
	return preg_replace_callback(
		"/{(.*?)}/", // Ungreedy (*?)
		function ($matches) use ($arguments, $closingTag) {
			static $i = 1;
			$i++;
			return $arguments[$i] . $matches[1] . $closingTag;
		},
		$string,
	);
}

// The operator's override of a notification mail from /etc/hestia/templates/email/, per language first,
// or false so the caller uses its inline default. Through the CLI: the panel cannot read /etc/hestia.
function get_email_template($file, $language)
{
	$language = preg_match('/^[A-Za-z0-9_-]+$/', (string) $language) ? (string) $language : "";
	$data = cli_json(
		"h-open-sys-email-template " . quoteshellarg((string) $file) . ($language !== "" ? " " . quoteshellarg($language) : ""),
	);
	return isset($data["content"]) ? (string) $data["content"] : false;
}

function translate_email($string, $replace)
{
	$array1 = $array2 = [];
	foreach ($replace as $key => $value) {
		$array1[] = "{{" . $key . "}}";
		$array2[] = $value;
	}
	return str_replace($array1, $array2, $string);
}
/**
 * Detects user language .
 * @param string Fallback language (default: 'en')
 * @return string Language code (such as 'en' and 'ja')
 */

function detect_login_language()
{
}
