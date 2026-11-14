<?php

// Desired against actual records of one zone, for h-check-dns-zone.
//   dns-compare.php ZONE DESIRED ACTUAL APEX
// DESIRED: dns_sections rows (ZONE STATE SECTION OBJECT OWNER REL TYPE PRIORITY TTL VALUE); ACTUAL: driver rows
// (REL TYPE PRIORITY TTL VALUE FLAG); APEX yes when an object on the box holds the zone apex.
// Out: SECTION OBJECT OWNER REL TYPE PRIORITY TTL VALUE STATUS ACTUAL NOTE: the zone's delegation (SECTION
// delegation: public name servers against the provider's), the desired rows, then every record no desired row
// consumed (SECTION foreign, or hidden outside the box's names in a zone without apex).
// Per type: A/AAAA as an address, MX as priority and target, SPF by whether it authorises the desired addresses,
// DMARC on existence, DKIM and other TXT exactly after joining; TTL never. Public DNS gets ten seconds per run in all,
// a check it cut short is "unchecked", never "different".

if (PHP_SAPI !== "cli" || $argc < 5) {
	fwrite(STDERR, "usage: dns-compare.php ZONE DESIRED ACTUAL APEX\n");
	exit(1);
}
[, $zone, $desiredFile, $actualFile, $apex] = $argv;
$zone = strtolower(rtrim($zone, "."));

/** @return list<list<string>> */
function tsv(string $file, int $n): array
{
	$rows = [];
	foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
		if ($line !== "") {
			$rows[] = array_pad(explode("\t", $line), $n, "");
		}
	}
	return $rows;
}

function fqdn(string $rel, string $zone): string
{
	return $rel === "@" ? $zone : "$rel.$zone";
}

function addr(string $v): string
{
	$p = @inet_pton(trim($v));
	return $p === false ? strtolower(trim($v)) : inet_ntop($p);
}

function host(string $v): string
{
	return rtrim(strtolower(trim($v)), ".") . ".";
}

// TXT as one string: a zone file form ("a" "b") is joined, a bare value stays as it is.
function txt(string $v): string
{
	$v = trim($v);
	if (preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $v, $m) && str_starts_with($v, '"')) {
		return stripcslashes(implode("", $m[1]));
	}
	return $v;
}

$desired = tsv($desiredFile, 10);
$actual = tsv($actualFile, 6);
$used = [];
// The time public DNS has left in this run, and whether a lookup since the last reset went unanswered.
final class Dig
{
	public static float $deadline = 0;
	public static bool $failed = false;

	/** @phpstan-impure */
	public static function failed(): bool
	{
		return self::$failed;
	}
}
Dig::$deadline = microtime(true) + 10;

// Indexes of actual rows at REL, optionally of one type.
/** @return list<int> */
function at(string $rel, ?string $type = null): array
{
	global $actual;
	$out = [];
	foreach ($actual as $i => $r) {
		if ($r[0] === $rel && ($type === null || $r[1] === $type)) {
			$out[] = $i;
		}
	}
	return $out;
}

function rel_of(string $name, string $zone): ?string
{
	$name = rtrim(strtolower($name), ".");
	if ($name === $zone) {
		return "@";
	}
	return str_ends_with($name, ".$zone") ? substr($name, 0, -strlen($zone) - 1) : null;
}

// Addresses of TYPE a name has: from the zone's own records (following one CNAME inside it), else public DNS.
/** @return list<string> */
function resolve(string $name, string $type): array
{
	global $actual, $zone;
	$rel = rel_of($name, $zone);
	if ($rel !== null) {
		$vals = array_map(fn ($i) => addr($actual[$i][4]), at($rel, $type));
		if (!$vals) {
			foreach (at($rel, "CNAME") as $i) {
				$t = rel_of($actual[$i][4], $zone);
				if ($t !== null) {
					$vals = array_map(fn ($j) => addr($actual[$j][4]), at($t, $type));
				}
			}
		}
		return $vals;
	}
	return array_map("addr", dig($name, $type));
}

/** @return list<string> */
function dig(string $name, string $type): array
{
	// The names come from records of third parties: dig would read "@host" as a server and "-x" as an option.
	if (strlen($name) > 253 || str_contains($name, "..") || !preg_match('/^[a-z0-9_]([a-z0-9_.-]*[a-z0-9_])?\.?$/i', $name)) {
		return [];
	}
	$left = (int) ceil(Dig::$deadline - microtime(true));
	if ($left <= 0) {
		Dig::$failed = true;
		return [];
	}
	$out = [];
	exec("timeout " . min(5, $left) . " dig +short +time=2 +tries=1 " . escapeshellarg($name) . " " . escapeshellarg($type) . " 2>/dev/null", $out, $rc);
	if ($rc !== 0) {
		Dig::$failed = true;
	}
	return array_values(array_filter($out, fn ($l) => $l !== "" && !str_starts_with($l, ";")));
}

function in_cidr(string $ip, string $net, ?int $len): bool
{
	$a = @inet_pton($ip);
	$b = @inet_pton($net);
	if ($a === false || $b === false || strlen($a) !== strlen($b)) {
		return false;
	}
	$len ??= strlen($a) * 8;
	$bytes = intdiv($len, 8);
	if (substr($a, 0, $bytes) !== substr($b, 0, $bytes)) {
		return false;
	}
	$bits = $len % 8;
	if ($bits === 0) {
		return true;
	}
	$mask = (0xff << (8 - $bits)) & 0xff;
	return (ord($a[$bytes]) & $mask) === (ord($b[$bytes]) & $mask);
}

// Whether SPF record REC of DOMAIN passes IP: the first matching mechanism decides, only "+" passes. include and
// redirect are followed through public DNS, at most ten lookups as RFC 7208 allows.
function spf_pass(string $ip, string $rec, string $domain, int &$lookups, int $depth = 0): bool
{
	$v6 = str_contains($ip, ":");
	$redirect = null;
	foreach (preg_split('/\s+/', trim($rec)) as $term) {
		if ($term === "" || strcasecmp($term, "v=spf1") === 0) {
			continue;
		}
		if (preg_match('/^redirect=(.+)$/i', $term, $m)) {
			$redirect = $m[1];
			continue;
		}
		$q = "+";
		if (strspn($term, "+-~?") === 1) {
			$q = $term[0];
			$term = substr($term, 1);
		}
		if (!preg_match('/^([a-z0-9]+)(?:[:=]([^\/]*))?(?:\/(\d+))?(?:\/\/(\d+))?$/i', $term, $m)) {
			continue;
		}
		$mech = strtolower($m[1]);
		$arg = $m[2] ?? "";
		$len4 = isset($m[3]) && $m[3] !== "" ? (int) $m[3] : null;
		$len6 = isset($m[4]) ? (int) $m[4] : null;
		$len = $v6 ? $len6 : $len4;
		$hit = false;
		switch ($mech) {
			case "all":
				$hit = true;
				break;
			case "ip4":
			case "ip6":
				$hit = ($mech === "ip6") === $v6 && in_cidr($ip, $arg, $len4);
				break;
			case "a":
				if (++$lookups > 10) {
					return false;
				}
				foreach (resolve($arg !== "" ? $arg : $domain, $v6 ? "AAAA" : "A") as $a) {
					$hit = $hit || in_cidr($ip, $a, $len);
				}
				break;
			case "mx":
				if (++$lookups > 10) {
					return false;
				}
				foreach (mx_hosts($arg !== "" ? $arg : $domain) as $h) {
					foreach (resolve($h, $v6 ? "AAAA" : "A") as $a) {
						$hit = $hit || in_cidr($ip, $a, $len);
					}
				}
				break;
			case "include":
				if (++$lookups > 10 || $depth > 5) {
					return false;
				}
				$inner = spf_record($arg);
				$hit = $inner !== null && spf_pass($ip, $inner, $arg, $lookups, $depth + 1);
				break;
		}
		if ($hit) {
			return $q === "+";
		}
	}
	if ($redirect !== null && ++$lookups <= 10 && $depth <= 5) {
		$inner = spf_record($redirect);
		return $inner !== null && spf_pass($ip, $inner, $redirect, $lookups, $depth + 1);
	}
	return false;
}

/** @return list<string> */
function mx_hosts(string $name): array
{
	global $actual, $zone;
	$rel = rel_of($name, $zone);
	if ($rel !== null) {
		return array_map(fn ($i) => $actual[$i][4], at($rel, "MX"));
	}
	return array_map(fn ($l) => preg_replace('/^\d+\s+/', "", $l), dig($name, "MX"));
}

function spf_record(string $name): ?string
{
	global $actual, $zone;
	$rel = rel_of($name, $zone);
	$all = $rel !== null ? array_map(fn ($i) => txt($actual[$i][4]), at($rel, "TXT")) : array_map("txt", dig($name, "TXT"));
	foreach ($all as $t) {
		if (preg_match('/^v=spf1(\s|$)/i', $t)) {
			return $t;
		}
	}
	return null;
}

// Whether REL is a node of the zone: a record at it or below it (an empty non-terminal answers too).
function node(string $rel): bool
{
	global $actual;
	if ($rel === "@") {
		return true;
	}
	foreach ($actual as $r) {
		if ($r[0] === $rel || str_ends_with($r[0], ".$rel")) {
			return true;
		}
	}
	return false;
}

// The wildcard that answers REL, "" when REL is a node itself: the one below the closest node above it.
function wildcard_for(string $rel): string
{
	if (node($rel)) {
		return "";
	}
	for ($p = $rel; ($i = strpos($p, ".")) !== false;) {
		$p = substr($p, $i + 1);
		if (node($p)) {
			return "*.$p";
		}
	}
	return "*";
}

// The delegated name REL is at or below, "" when the zone answers for it.
function delegated(string $rel): string
{
	global $actual;
	foreach ($actual as $r) {
		if ($r[1] === "NS" && $r[0] !== "@" && ($rel === $r[0] || str_ends_with($rel, "." . $r[0]))) {
			return $r[0];
		}
	}
	return "";
}

// A note for provider rows the driver blanked control characters in.
/** @param list<int> $rows */
function ctl_note(array $rows): string
{
	global $actual;
	foreach ($rows as $i) {
		if ($actual[$i][5] === "ctl") {
			return "holds a control character, shown as a blank";
		}
	}
	return "";
}

/** @param list<string|int> $f */
function out(array $f): void
{
	echo implode("\t", array_map(fn ($v) => str_replace(["\t", "\n"], " ", (string) $v), $f)), "\n";
}

// A zone the public name servers do not point at is no zone anyone reads, however well it matches.
$ns = [];
foreach (at("@", "NS") as $i) {
	if ($actual[$i][5] === "ns") {
		$used[$i] = true;
		$ns[] = host($actual[$i][4]);
	}
}
$ns = array_values(array_unique($ns));
sort($ns);
$public = array_values(array_unique(array_map("host", dig($zone, "NS"))));
sort($public);
if (Dig::failed()) {
	$status = "unchecked";
	$note = "public DNS did not answer";
} elseif (!$public) {
	$status = "different";
	$note = "public DNS names no name servers for the zone";
} else {
	$status = array_diff($public, $ns) ? "different" : "ok";
	$note = $status === "ok" ? "" : "the public name servers are not the provider's";
}
out(["delegation", $zone, "", "@", "NS", "", "", implode(" ", $ns), $status, implode(" ", $public), $note]);

foreach ($desired as $d) {
	[, , $section, $object, $owner, $rel, $type, $prio, $ttl, $value] = $d;
	$status = "missing";
	$shown = [];
	$hits = [];
	$note = "";
	$cut = delegated($rel);
	Dig::$failed = false;
	if ($cut !== "") {
		$status = "delegated";
		$note = "delegated to " . implode(", ", array_map(fn ($i) => $actual[$i][4], at($cut, "NS"))) . ": not answered by this zone";
	} elseif ($type === "A" || $type === "AAAA") {
		$want = addr($value);
		$hits = at($rel, $type);
		if ($hits) {
			$vals = [];
			foreach ($hits as $i) {
				$used[$i] = true;
				$vals[] = addr($actual[$i][4]);
				$shown[] = $actual[$i][4];
				if ($actual[$i][5] === "main") {
					$note = "main IP";
				} elseif ($actual[$i][5] === "include") {
					$note = "follows the apex (www include)";
				}
			}
			$status = array_values(array_unique($vals)) === [$want] ? "ok" : "different";
		} else {
			$via = "";
			$vals = [];
			$cn = at($rel, "CNAME") ?: at($rel, "ALIAS");
			$wc = wildcard_for($rel);
			if ($cn) {
				$used[$cn[0]] = true;
				$via = $actual[$cn[0]][1];
				$target = $actual[$cn[0]][4];
				$shown[] = "$via $target";
				$vals = resolve($target, $type);
			} elseif ($wc !== "" && (at($wc, $type) || at($wc, "CNAME"))) {
				$via = "wildcard $wc";
				$w = at($wc, $type);
				if ($w) {
					$vals = array_map(fn ($i) => addr($actual[$i][4]), $w);
					$shown = array_map(fn ($i) => $actual[$i][4], $w);
				} else {
					$target = $actual[at($wc, "CNAME")[0]][4];
					$shown[] = "CNAME $target";
					$vals = resolve($target, $type);
				}
			}
			if ($via !== "") {
				$match = array_values(array_unique($vals)) === [$want];
				$status = $match ? "ok" : (str_starts_with($via, "wildcard") ? "wildcard" : "cname");
				$note = "via $via" . ($vals ? " -> " . implode(", ", $vals) : "");
			}
		}
	} elseif (at($rel, "CNAME")) {
		// Beside a CNAME no other record can stand, so this one cannot be added there.
		$cn = at($rel, "CNAME");
		$used[$cn[0]] = true;
		$shown[] = "CNAME " . $actual[$cn[0]][4];
		$status = "cname";
		$note = "a CNAME holds the name, no $type can stand beside it";
	} elseif ($type === "MX") {
		$want = (int) $prio . " " . host($value);
		$hits = at($rel, "MX");
		$vals = [];
		foreach ($hits as $i) {
			$used[$i] = true;
			$vals[] = (int) $actual[$i][2] . " " . host($actual[$i][4]);
		}
		$shown = $vals;
		if ($hits) {
			$status = array_values(array_unique($vals)) === [$want] ? "ok" : "different";
		}
	} elseif ($type === "TXT") {
		$want = txt($value);
		$kind = preg_match('/^v=(spf1|dmarc1|dkim1)\b/i', $want, $m) ? strtolower($m[1]) : "";
		$hits = [];
		foreach (at($rel, "TXT") as $i) {
			$t = txt($actual[$i][4]);
			if ($kind === "dkim1" || $kind === "" || preg_match('/^v=' . $kind . '\b/i', $t)) {
				$hits[] = $i;
			}
		}
		foreach ($hits as $i) {
			$used[$i] = true;
			$shown[] = txt($actual[$i][4]);
		}
		if ($hits) {
			if ($kind === "spf1") {
				if (count($hits) > 1) {
					$status = "different";
					$note = "more than one SPF record (permerror)";
				} else {
					preg_match_all('/\bip[46]:([0-9a-f.:]+)/i', $want, $ips);
					$missed = [];
					foreach ($ips[1] as $ip) {
						$lookups = 0;
						if (!spf_pass($ip, $shown[0], fqdn($rel, $zone), $lookups)) {
							$missed[] = $ip;
						}
					}
					$status = $missed ? (Dig::failed() ? "unchecked" : "different") : "ok";
					$note = !$missed ? "" : (Dig::failed() ? "public DNS did not answer in time" : "does not authorise " . implode(", ", $missed));
				}
			} elseif ($kind === "dmarc1") {
				$status = count($hits) > 1 ? "different" : "ok";
				$note = count($hits) > 1 ? "more than one DMARC record (receivers ignore all of them)" : "";
			} else {
				$norm = fn ($s) => preg_replace('/\s+/', "", $s);
				$status = "different";
				foreach ($shown as $t) {
					if ($norm($t) === $norm($want)) {
						$status = "ok";
					}
				}
			}
		}
	}
	// A blanked value is not the stored one, so it never counts as in place.
	$ctl = ctl_note($hits);
	if ($ctl !== "") {
		$status = $status === "ok" ? "different" : $status;
		$note = trim("$note; $ctl", "; ");
	}
	out([$section, $object, $owner, $rel, $type, $prio, $ttl, $value, $status, implode(" | ", $shown), $note]);
}

// The names the box derives in this zone (ASCII, unlike OBJECT): in a zone without apex only records at or below
// them are shown.
$own = [];
foreach ($desired as $d) {
	$own[fqdn($d[5], $zone)] = true;
}
foreach ($actual as $i => $r) {
	if (isset($used[$i])) {
		continue;
	}
	$name = fqdn($r[0], $zone);
	$mine = $apex === "yes";
	foreach (array_keys($own) as $o) {
		$mine = $mine || $name === $o || str_ends_with($name, ".$o");
	}
	$note = $r[5] === "main" ? "main IP" : ($r[5] === "include" ? "follows the apex (www include)" : ctl_note([$i]));
	out([$mine ? "foreign" : "hidden", "", "", $r[0], $r[1], $r[2], $r[3], $r[4], "foreign", "", $note]);
}
