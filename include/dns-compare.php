<?php

// Desired against actual records of one zone, for h-check-dns-zone and h-sync-dns-zone.
//   dns-compare.php ZONE DESIRED ACTUAL APEX KEPT WRITTEN [SECTION OBJECT OWNER [CONFIRM [MOVE]]]
//   dns-compare.php ZONE ACTUAL WRITTEN
// DESIRED: dns_sections rows (ZONE STATE SECTION OBJECT OWNER REL TYPE PRIORITY TTL VALUE ORIGIN); ACTUAL: driver rows
// (REL TYPE PRIORITY TTL VALUE FLAG RAW); APEX yes when an object on the box holds the zone apex; KEPT: the
// exceptions (OWNER REL TYPE VALUE, one row per accepted value, an empty VALUE alone for "kept absent"); WRITTEN: the
// derived records HestiaRE wrote (OWNER REL TYPE VALUE SECTION OBJECT).
// Out: SECTION OBJECT OWNER REL TYPE PRIORITY TTL VALUE STATUS ACTUAL NOTE FOUND EXCEPTION KEPT ORIGIN: the zone's
// delegation (SECTION delegation: public name servers against the provider's), the desired rows, then every record
// no desired row consumed: SECTION orphan when HestiaRE wrote it (OBJECT and OWNER those it wrote it for), else
// foreign, or hidden outside the box's names in a zone without apex. FOUND is what
// answers for a desired row at the provider, KEPT the values its exception accepts (both \x1f separated), EXCEPTION
// yes when it has one. A row whose FOUND matches its exception is "exception", one that drifted from it "different".
// Per type: A/AAAA as an address, MX as priority and target, SPF by whether it authorises the desired addresses,
// DMARC on existence, DKIM and other TXT exactly after joining; TTL never. Public DNS gets ten seconds per run in all,
// a check it cut short is "unchecked", never "different".
// A row of ORIGIN added (a record the user added or adopted) consumes only the record with its own value, a CNAME or
// ALIAS the one at its name; it is "shadowed" where a derived row holds the name and type, or a CNAME's name at all.
// With SECTION OBJECT OWNER the writes that bring that section in line instead, "OP REL TYPE PRIORITY TTL VALUE
// NOTE [CONFIRM]": add, rem (VALUE as stored), main (the apex address), include (VALUE off or on: whether www follows
// the apex), skip (left alone, NOTE says why; CONFIRM names the confirmation that would write it instead). A rem only
// ever names a record a desired row of the section consumed, or for SECTION orphan an orphan written for OBJECT and
// OWNER, so no foreign record is touched, and nothing is written to a zone the public name servers do not point at.
// CONFIRM (comma separated) allows what is never done unasked: cname replaces a CNAME or ALIAS at a name by the
// section's records or by an added one to another target, include switches the www include off so that www can
// differ from the apex, follow switches it on again where www derives what the apex holds, normalize puts the SPF
// record back to the standard "v=spf1 a mx ~all".
// MOVE (OLD=NEW, comma separated) plans only the rows that carry an OLD address: an A/AAAA holding it, an SPF naming
// it in an ip4/ip6 term or covering it only through a and mx. Only that address goes from an A/AAAA; an SPF keeps it
// beside the new one, or as a term of its own beside a and mx. A row with an exception or whose derived value is not
// the NEW one is left alone. SPF is judged by the zone once it is in line, so a and mx cover what the box derives,
// except at a CNAME, an ALIAS or a delegation, where nothing is written.
// With ZONE ACTUAL WRITTEN only the WRITTEN rows no longer at the provider, "OWNER REL TYPE VALUE".

if (PHP_SAPI !== "cli" || !in_array($argc, [4, 7, 10, 11, 12], true)) {
	fwrite(STDERR, "usage: dns-compare.php ZONE DESIRED ACTUAL APEX KEPT WRITTEN [SECTION OBJECT OWNER [CONFIRM [MOVE]]]\n" .
		"       dns-compare.php ZONE ACTUAL WRITTEN\n");
	exit(1);
}
if ($argc === 4) {
	[, $zone, $actualFile, $writtenFile] = $argv;
	$desiredFile = $apex = $keptFile = "";
} else {
	[, $zone, $desiredFile, $actualFile, $apex, $keptFile, $writtenFile] = $argv;
}
$planFor = $argc >= 10 ? [$argv[7], strtolower($argv[8]), $argv[9]] : null;
$confirm = $argc >= 11 ? array_values(array_filter(explode(",", $argv[10]))) : [];
/** @var ?array<string, string> $move old address => new address */
$move = null;
if ($argc === 12) {
	$move = [];
	foreach (explode(",", $argv[11]) as $pair) {
		[$old, $new] = explode("=", $pair, 2) + [1 => ""];
		$o = @inet_pton($old);
		$n = @inet_pton($new);
		if ($o === false || $n === false || strlen($o) !== strlen($n)) {
			fwrite(STDERR, "invalid move: $pair\n");
			exit(1);
		}
		$move[inet_ntop($o)] = inet_ntop($n);
	}
}
$zone = strtolower(rtrim($zone, "."));
const SPF_NORMAL = "v=spf1 a mx ~all";

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

// One value as an exception compares it: an address, priority and host, a TXT without blanks; what answers through
// a CNAME, an ALIAS or a wildcard by that.
function canon(string $type, string $v): string
{
	if (preg_match('/^(CNAME|ALIAS) (.*)$/', $v, $m)) {
		return $m[1] . " " . host($m[2]);
	}
	if (str_starts_with($v, "wildcard ")) {
		return strtolower($v);
	}
	return match ($type) {
		"A", "AAAA" => addr($v),
		"MX" => preg_match('/^(\d+)\s+(\S+)$/', trim($v), $m) ? (int) $m[1] . " " . host($m[2]) : strtolower(trim($v)),
		"TXT" => (string) preg_replace('/\s+/', "", $v),
		default => strtolower(trim($v)),
	};
}

/**
 * @param list<string> $a
 * @param list<string> $b
 */
function same_set(string $type, array $a, array $b): bool
{
	$c = fn ($l) => array_values(array_unique(array_map(fn ($v) => canon($type, $v), $l)));
	$a = $c($a);
	$b = $c($b);
	sort($a);
	sort($b);
	return $a === $b;
}

// Whether actual row R is the record HestiaRE wrote as W; the www include and the name servers are no records.
/**
 * @param list<string> $w
 * @param list<string> $r
 */
function written_at(array $w, array $r): bool
{
	if ($r[0] !== $w[1] || $r[1] !== $w[2] || in_array($r[5], ["include", "ns"], true)) {
		return false;
	}
	$v = match ($w[2]) {
		"MX" => $r[2] . " " . $r[4],
		"TXT" => txt($r[4]),
		default => $r[4],
	};
	return canon($w[2], $v) === canon($w[2], $w[3]);
}

$actual = tsv($actualFile, 7);
$written = tsv($writtenFile, 6);
if ($argc === 4) {
	foreach ($written as $w) {
		$there = false;
		foreach ($actual as $r) {
			$there = $there || written_at($w, $r);
		}
		if (!$there) {
			echo implode("\t", array_slice($w, 0, 4)), "\n";
		}
	}
	exit(0);
}
$desired = tsv($desiredFile, 11);
/** @var array<string, list<string>> $kept */
$kept = [];
foreach (tsv($keptFile, 4) as [$owner, $rel, $type, $value]) {
	$kept["$owner\t$rel\t$type"] ??= [];
	if ($value !== "") {
		$kept["$owner\t$rel\t$type"][] = $value;
	}
}
$used = [];
// An added record's value as the provider's is compared with it: the priority only where the type has one.
function added_canon(string $type, string $prio, string $v): string
{
	$v = match ($type) {
		"A", "AAAA" => addr($v),
		"TXT" => (string) preg_replace('/\s+/', "", txt($v)),
		"MX", "NS", "PTR", "CNAME", "ALIAS" => host($v),
		default => strtolower(trim($v)),
	};
	return (in_array($type, ["MX", "SRV"], true) ? (int) $prio . " " : "") . $v;
}

function txt_kind(string $v): string
{
	return preg_match('/^v=(spf1|dmarc1|dkim1)\b/i', txt($v), $m) ? strtolower($m[1]) : "";
}

// The added rows a derived row wins against, by index, with the reason: the same name and type (for TXT the same
// kind, or a DKIM name), or any record at the name of a CNAME or ALIAS.
/** @var array<int, string> $shadowed */
$shadowed = [];
foreach ($desired as $k => $d) {
	if ($d[10] !== "added") {
		continue;
	}
	foreach ($desired as $e) {
		if ($e[10] !== "" || $e[5] !== $d[5]) {
			continue;
		}
		$txtClash = $d[6] === "TXT" && (txt_kind($e[9]) === "dkim1" || (txt_kind($d[9]) !== "" && txt_kind($d[9]) === txt_kind($e[9])));
		if (in_array($d[6], ["CNAME", "ALIAS"], true) || ($e[6] === $d[6] && ($d[6] !== "TXT" || $txtClash))) {
			$shadowed[$k] = "the $e[2] section $e[3] derives $e[6] at this name";
			break;
		}
	}
}

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

// Whether resolve() and mx_hosts() answer the zone's own names as the zone holds them once it is in line.
final class InLine
{
	public static bool $on = false;
}

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

// What the zone holds at REL TYPE once it is in line: the derived values, or those an exception keeps instead; null
// where the box derives nothing there, or the exception keeps a CNAME, ALIAS or wildcard.
/** @return ?list<string> */
function in_line_at(string $rel, string $type): ?array
{
	global $desired, $kept;
	// The plan writes nothing at a CNAME, an ALIAS or a delegation, so the provider's answer stays the one that counts.
	if (at($rel, "CNAME") || at($rel, "ALIAS") || delegated($rel) !== "") {
		return null;
	}
	$vals = [];
	$derived = false;
	foreach ($desired as $d) {
		if ($d[5] !== $rel || $d[6] !== $type) {
			continue;
		}
		$derived = true;
		foreach ($d[10] === "" ? ($kept["$d[4]\t$rel\t$type"] ?? [$d[9]]) : [$d[9]] as $v) {
			if (preg_match('/^(CNAME|ALIAS|wildcard) /', $v)) {
				return null;
			}
			$vals[] = $type === "MX" ? host((string) preg_replace('/^\d+\s+/', "", trim($v))) : addr($v);
		}
	}
	return $derived ? array_values(array_unique($vals)) : null;
}

// Addresses of TYPE a name has: from the zone's own records (following one CNAME inside it), else public DNS.
/** @return list<string> */
function resolve(string $name, string $type): array
{
	global $actual, $zone;
	$rel = rel_of($name, $zone);
	if ($rel !== null && InLine::$on && ($vals = in_line_at($rel, $type)) !== null) {
		return $vals;
	}
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
	$host = '/^[a-z0-9_]([a-z0-9_.-]*[a-z0-9_])?\.?$/i';
	if (strlen($name) > 253 || str_contains($name, "..") || !preg_match($host, $name)) {
		return [];
	}
	$left = (int) ceil(Dig::$deadline - microtime(true));
	if ($left <= 0) {
		Dig::$failed = true;
		return [];
	}
	$out = [];
	$cmd = "timeout " . min(5, $left) . " dig +noall +answer +comments +time=2 +tries=1 " . escapeshellarg($name)
		. " " . escapeshellarg($type);
	exec("$cmd 2>/dev/null", $out, $rc);
	// rc is 0 on SERVFAIL as well, only the header tells; the answer also carries the CNAMEs on the way.
	$status = "";
	$vals = [];
	foreach ($out as $l) {
		if (preg_match('/status: ([A-Z]+)/', $l, $m)) {
			$status = $m[1];
		} elseif ($l !== "" && $l[0] !== ";") {
			$f = preg_split('/\s+/', $l, 5) ?: [];
			if (count($f) === 5 && strtoupper($f[3]) === $type) {
				$vals[] = $f[4];
			}
		}
	}
	if ($rc !== 0 || ($status !== "NOERROR" && $status !== "NXDOMAIN")) {
		Dig::$failed = true;
	}
	return $vals;
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

// spf_pass on the zone once it is in line: a and mx of its own names resolve to what the box derives there, so the SPF
// is not judged by A and MX records a write of the same plan, or of another section, is about to replace.
function spf_pass_in_line(string $ip, string $rec, string $domain): bool
{
	$lookups = 0;
	InLine::$on = true;
	try {
		return spf_pass($ip, $rec, $domain, $lookups);
	} finally {
		InLine::$on = false;
	}
}

/** @return list<string> */
function mx_hosts(string $name): array
{
	global $actual, $zone;
	$rel = rel_of($name, $zone);
	if ($rel !== null && InLine::$on && ($hosts = in_line_at($rel, "MX")) !== null) {
		return $hosts;
	}
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

// SPF record REC with the addresses MISSED right behind v=spf1: the first matching mechanism decides, so anything
// later, a "-ip4" or "~a" as well as the all, could still reject them.
/** @param list<string> $missed */
function spf_merge(string $rec, array $missed): string
{
	$terms = preg_split('/\s+/', trim($rec)) ?: [];
	$add = array_map(fn ($ip) => (str_contains($ip, ":") ? "ip6:" : "ip4:") . $ip, $missed);
	array_splice($terms, 1, 0, $add);
	return implode(" ", $terms);
}

// The old addresses of MOVE an SPF record REC at REL authorises only through a and mx: once the zone is in line these
// answer with the new address, which REC then passes, and the old box would lose its cover while both run.
/**
 * @param array<string, string> $move
 * @return list<string>
 */
function spf_kept_old(string $rec, string $rel, string $section, string $object, array $move): array
{
	global $zone;
	if (txt_kind($rec) !== "spf1") {
		return [];
	}
	$named = preg_match_all('/(?:^|\s)[-+~?]?ip[46]:([0-9a-f.:]+)(?=[\s\/]|$)/i', $rec, $m) ? array_map("addr", $m[1]) : [];
	$keep = [];
	foreach ($move as $old => $new) {
		if (!in_array($old, $named, true) && in_array(addr($new), spf_addrs($section, $object), true)
			&& spf_pass_in_line($new, $rec, fqdn($rel, $zone)) && !spf_pass_in_line($old, $rec, fqdn($rel, $zone))) {
			$keep[] = $old;
		}
	}
	return $keep;
}

// SPF record REC of DOMAIN with the new address of MOVE right before each ip4/ip6 term on an old one, unless a and mx
// cover it once the zone is in line. The old term stays: mail may still leave from the old box while both run. KEEP
// are old addresses REC covered through a and mx: they go in as terms of their own before the first that can reject,
// or the all. REC itself once nothing is missing.
/**
 * @param array<string, string> $move
 * @param list<string> $keep
 */
function spf_move(string $rec, string $domain, array $move, array $keep = []): string
{
	$terms = preg_split('/\s+/', trim($rec)) ?: [];
	$have = array_map("strtolower", $terms);
	$out = [];
	foreach ($terms as $t) {
		if (preg_match('/^([-+~?]?ip[46]):([0-9a-f.:]+)$/i', $t, $m) && isset($move[addr($m[2])])
			&& !spf_pass_in_line($move[addr($m[2])], $rec, $domain)) {
			$n = $m[1] . ":" . $move[addr($m[2])];
			if (!in_array(strtolower($n), $have, true)) {
				$out[] = $n;
				$have[] = strtolower($n);
			}
		}
		$out[] = $t;
	}
	$at = count($out);
	foreach ($out as $j => $t) {
		if ($j > 0 && preg_match('/^([-~?]|\+?all$|redirect=|exp=)/i', $t)) {
			$at = $j;
			break;
		}
	}
	$add = array_map(fn ($ip) => (str_contains($ip, ":") ? "ip6:" : "ip4:") . $ip, $keep);
	array_splice($out, $at, 0, array_values(array_diff($add, $have)));
	return implode(" ", $out);
}

// The old address of MOVE a row's records carry, null for none: an A/AAAA holding it, an SPF naming it.
/**
 * @param array{d: list<string>, status: string, base: string, hits: list<int>, missed: list<string>, kept: ?list<string>} $x
 * @param array<string, string> $move
 */
function moved_from(array $x, array $move): ?string
{
	global $actual;
	$type = $x["d"][6];
	foreach ($x["hits"] as $i) {
		if ($type === "A" || $type === "AAAA") {
			if (isset($move[addr($actual[$i][4])])) {
				return addr($actual[$i][4]);
			}
		} elseif ($type === "TXT") {
			$rec = txt($actual[$i][4]);
			foreach (preg_match_all('/\bip[46]:([0-9a-f.:]+)(?=\s|$)/i', $rec, $m) ? $m[1] : [] as $a) {
				if (isset($move[addr($a)])) {
					return addr($a);
				}
			}
			$keep = spf_kept_old($rec, $x["d"][5], $x["d"][2], $x["d"][3], $move);
			if ($keep) {
				return $keep[0];
			}
		}
	}
	return null;
}

// The old address of MOVE on the wildcard answering REL, null for none or for a wildcard the box derives and moves.
/**
 * @param array<string, string> $move
 * @param array<int|string, array{d: list<string>}> $results
 */
function wildcard_from(string $rel, string $type, array $move, array $results): ?string
{
	global $actual;
	$wc = wildcard_for($rel);
	foreach ($results as $x) {
		if ($x["d"][5] === $wc && $x["d"][6] === $type) {
			return null;
		}
	}
	foreach ($wc !== "" ? at($wc, $type) : [] as $i) {
		if (isset($move[addr($actual[$i][4])])) {
			return addr($actual[$i][4]);
		}
	}
	return null;
}

// The writes that put an exception back: its values in place of the derived one, by the same verbs. A CNAME or a
// wildcard it keeps, or a main IP it cannot be, is left to the provider's page.
/**
 * @param array{d: list<string>, status: string, base: string, hits: list<int>, missed: list<string>, kept: ?list<string>} $x
 * @return list<list<string>>
 */
function kept_ops(array $x, bool $include, bool $mainV6): array
{
	global $actual;
	[, , , , , $rel, $type, , $ttl, $value] = $x["d"];
	$want = $x["kept"] ?? [];
	$skip = fn ($why) => [["skip", $rel, $type, "", $ttl, $value, $why]];
	foreach ($want as $w) {
		if (preg_match('/^(CNAME|ALIAS|wildcard) /', $w)) {
			return $skip("the exception keeps a CNAME, ALIAS or wildcard: put it back at the provider, or lift it");
		}
	}
	$wantC = array_values(array_unique(array_map(fn ($v) => canon($type, $v), $want)));
	$ops = [];
	$hits = $x["hits"];
	if ($type === "A" || $type === "AAAA") {
		if ($rel === "www" && $include) {
			return $skip("www follows the apex (www include), which differs");
		}
		$main = null;
		$own = [];
		foreach ($hits as $i) {
			if ($actual[$i][5] === "main") {
				$main = $i;
			} elseif ($actual[$i][5] === "") {
				$own[] = $i;
			}
		}
		if ($rel === "@" && $type === "AAAA" && $main !== null) {
			return $skip("the main IP holds an IPv6 address");
		}
		$have = [];
		if ($rel === "@" && $type === "A" && ($main !== null || (!$own && !$mainV6))) {
			if (count($wantC) !== 1) {
				return $skip("the main IP holds exactly one address: put the exception back at the provider, or lift it");
			}
			if ($main === null || addr($actual[$main][4]) !== $wantC[0]) {
				$ops[] = ["main", $rel, $type, "", $ttl, $wantC[0], "kept as an exception"];
			}
			$have[] = $wantC[0];
		}
		foreach ($own as $i) {
			if (in_array(addr($actual[$i][4]), $wantC, true)) {
				$have[] = addr($actual[$i][4]);
			} else {
				$ops[] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
			}
		}
		foreach ($wantC as $w) {
			if (!in_array($w, $have, true)) {
				$ops[] = ["add", $rel, $type, "", $ttl, $w, "kept as an exception"];
			}
		}
		return $ops;
	}
	$have = [];
	foreach ($hits as $i) {
		$c = $type === "MX" ? canon("MX", $actual[$i][2] . " " . $actual[$i][4]) : canon($type, txt($actual[$i][4]));
		if (in_array($c, $wantC, true)) {
			$have[] = $c;
		} else {
			$ops[] = ["rem", $rel, $type, $type === "MX" ? $actual[$i][2] : "", $actual[$i][3], $actual[$i][6], ""];
		}
	}
	foreach ($want as $w) {
		if (in_array(canon($type, $w), $have, true)) {
			continue;
		}
		$have[] = canon($type, $w);
		if ($type === "MX") {
			[$p, $h] = preg_split('/\s+/', trim($w), 2) + [1 => ""];
			$ops[] = ["add", $rel, $type, (string) (int) $p, $ttl, host($h), "kept as an exception"];
		} else {
			$ops[] = ["add", $rel, $type, "", $ttl, $w, "kept as an exception"];
		}
	}
	return $ops;
}

// Why www cannot follow the apex again, or "": the include serves the apex's A and AAAA at www, so www has to derive
// exactly those, and the apex has to hold them once the plan ran.
/**
 * @param list<array{d: list<string>, status: string, base: string, hits: list<int>, missed: list<string>, kept: ?list<string>}> $results
 * @param array<string, string> $apexWant
 * @param array<string, bool> $apexDone
 */
function follow_refused(
	array $results,
	string $section,
	string $object,
	string $delegation,
	array $apexWant,
	array $apexDone,
): string {
	if ($delegation !== "") {
		return $delegation;
	}
	foreach (["CNAME", "ALIAS", "NS"] as $type) {
		if (at("www", $type)) {
			return "a record of type $type holds www";
		}
	}
	$www = [];
	foreach ($results as $x) {
		[, , $s, $o, , $rel, $type, , , $value, $origin] = $x["d"];
		if ($rel !== "www" || $s !== $section || strtolower($o) !== $object || !in_array($type, ["A", "AAAA"], true)
			|| $origin !== "") {
			continue;
		}
		if ($x["kept"] !== null) {
			return "an exception holds www $type";
		}
		$www[$type] = addr($value);
	}
	if (!$www) {
		return "the section derives no www address";
	}
	foreach (["A", "AAAA"] as $type) {
		if (($www[$type] ?? null) !== ($apexWant[$type] ?? null)) {
			return "www and the apex derive different $type values";
		}
		if (isset($www[$type]) && !($apexDone[$type] ?? false)) {
			return "the apex $type is not in line";
		}
	}
	return "";
}

// The addresses a section's SPF has to authorise: its A/AAAA rows, as the SPF it derives names none.
/** @return list<string> */
function spf_addrs(string $section, string $object): array
{
	global $desired;
	$ips = [];
	foreach ($desired as $d) {
		if ($d[2] === $section && $d[3] === $object && ($d[6] === "A" || $d[6] === "AAAA") && $d[10] === "") {
			$ips[] = addr($d[9]);
		}
	}
	return array_values(array_unique($ips));
}

// The SPF record put back to the standard, which names no address: only where a and mx authorise every address the
// box derives, as a receiver resolves them now.
/**
 * @param array{d: list<string>, status: string, base: string, hits: list<int>, missed: list<string>, kept: ?list<string>} $x
 * @return list<list<string>>
 */
function spf_normal_ops(array $x): array
{
	global $actual, $zone;
	[, , $section, $object, , $rel, $type, , $ttl] = $x["d"];
	if (array_map(fn ($i) => txt($actual[$i][4]), $x["hits"]) === [SPF_NORMAL]) {
		return [];
	}
	$missed = [];
	Dig::$failed = false;
	foreach (spf_addrs($section, $object) as $ip) {
		$lookups = 0;
		if (!spf_pass($ip, SPF_NORMAL, fqdn($rel, $zone), $lookups)) {
			$missed[] = $ip;
		}
	}
	if ($missed) {
		$why = Dig::failed() ? "public DNS did not answer"
			: "a and mx do not authorise " . implode(", ", $missed) . ", bring the A and MX records in line first";
		return [["skip", $rel, $type, "", $ttl, SPF_NORMAL, $why]];
	}
	$ops = [];
	$keep = null;
	foreach ($x["hits"] as $i) {
		if ($keep === null && txt($actual[$i][4]) === SPF_NORMAL) {
			$keep = $i;
		} else {
			$ops[] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
		}
	}
	if ($keep === null) {
		$t = $x["hits"] && $actual[$x["hits"][0]][3] !== "" ? $actual[$x["hits"][0]][3] : $ttl;
		$ops[] = ["add", $rel, $type, "", $t, SPF_NORMAL, "normalized"];
	}
	return $ops;
}

// The writes that put an added record in place: its own value beside whatever else stands there, a CNAME or ALIAS in
// place of the one there on the cname confirmation only, never beside other records.
/**
 * @param array{d: list<string>, status: string, base: string, hits: list<int>, missed: list<string>, kept: ?list<string>} $x
 * @param list<string> $confirm
 * @return list<list<string>>
 */
function added_ops(array $x, array $confirm): array
{
	global $actual;
	[, , , , , $rel, $type, $prio, $ttl, $value] = $x["d"];
	if ($type !== "CNAME" && $type !== "ALIAS") {
		return [["add", $rel, $type, $prio, $ttl, $type === "TXT" ? txt($value) : $value, "an added record"]];
	}
	if (!$x["hits"] && array_diff(at($rel), at($rel, "CNAME"), at($rel, "ALIAS"))) {
		return [["skip", $rel, $type, "", $ttl, $value, "other records hold the name, no $type can stand beside them"]];
	}
	$held = array_merge(at($rel, "CNAME"), at($rel, "ALIAS"));
	if ($held && !in_array("cname", $confirm, true)) {
		return [["skip", $rel, $type, "", $ttl, $value, "a CNAME or ALIAS to another target holds the name", "cname"]];
	}
	$ops = [];
	foreach ($held as $i) {
		$ops[] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
	}
	$ops[] = ["add", $rel, $type, "", $ttl, host($value), "an added record"];
	return $ops;
}

// The writes for one section, as the header describes. $results as the compare loop collects them.
/**
 * @param list<array{d: list<string>, status: string, base: string, hits: list<int>, missed: list<string>, kept: ?list<string>}> $results
 * @param list<string> $confirm
 * @param ?array<string, string> $move
 */
function plan(array $results, string $section, string $object, string $delegation, array $confirm, ?array $move): void
{
	global $actual, $zone;
	$skip = [
		"delegated" => "a delegated name, the zone does not answer for it",
		"unchecked" => "public DNS did not answer, check again",
		"shadowed" => "a row the box derives holds the name, the added record stays out",
	];
	$include = false;
	foreach ($actual as $r) {
		$include = $include || $r[5] === "include";
	}
	$mainV6 = false;
	foreach ($actual as $r) {
		$mainV6 = $mainV6 || ($r[0] === "@" && $r[1] === "AAAA" && $r[5] === "main");
	}
	// The desired apex per type, and whether it will be in line once this plan ran: ok already, or planned here
	// without a skip. The apex rows are planned first so www can ask; the ops come out in the rows' order.
	$apexWant = [];
	$apexDone = [];
	foreach ($results as $x) {
		[, , , , , $rel, $type, , , $value, $origin] = $x["d"];
		if ($rel === "@" && $origin === "") {
			$apexWant[$type] = addr($value);
			if ($x["kept"] !== null) {
				unset($apexWant[$type]);
				if (count($x["kept"]) === 1) {
					$apexWant[$type] = canon($type, $x["kept"][0]);
				}
			}
			$apexDone[$type] = in_array($x["status"], ["ok", "exception"], true);
		}
	}
	$ops = [];
	$replaced = [];
	$includeOff = false;
	$follow = false;
	foreach ([true, false] as $apexPass) {
		if (!$apexPass) {
			foreach ($results as $k => $x) {
				[, , $s, $o, , $rel, $type] = $x["d"];
				if ($rel === "@" && $s === $section && strtolower($o) === $object && $x["d"][10] === ""
					&& !in_array($x["status"], ["ok", "exception"], true)) {
					$apexDone[$type] = !in_array("skip", array_column($ops[$k] ?? [], 0), true);
				}
			}
			if (in_array("follow", $confirm, true) && !$include && $move === null) {
				$why = follow_refused($results, $section, $object, $delegation, $apexWant, $apexDone);
				$follow = $why === "";
				if (!$follow) {
					$ops[-1] = [["skip", "www", "", "", "", "", "www cannot follow the apex: $why"]];
				}
			}
		}
		foreach ($results as $k => $x) {
			[, , $s, $o, , $rel, $type, $prio, $ttl, $value] = $x["d"];
			// www in line through the include breaks when this plan changes the apex, so it is planned as well.
			$follows = $rel === "www" && $include && $x["status"] === "ok" && $x["kept"] === null && $delegation === ""
				&& ($type === "A" || $type === "AAAA") && $x["d"][10] === "";
			$normal = in_array("normalize", $confirm, true) && $type === "TXT" && $x["kept"] === null && $move === null
				&& $x["d"][10] === ""
				&& preg_match('/^v=spf1\b/i', txt($value)) === 1;
			if (($rel === "@") !== $apexPass || $s !== $section || strtolower($o) !== $object
				|| (in_array($x["status"], ["ok", "exception"], true) && !$follows && !$normal && $move === null)
				|| ($follow && $rel === "www" && ($type === "A" || $type === "AAAA"))) {
				continue;
			}
			$status = $x["status"];
			$hits = $x["hits"];
			if ($move !== null) {
				$from = moved_from($x, $move);
				if ($from === null && $x["status"] === "wildcard" && $x["kept"] === null) {
					$from = wildcard_from($rel, $type, $move, $results);
					if ($from !== null) {
						$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value,
							"answered through a wildcard on $from the box does not derive: move it at the provider"];
					}
					continue;
				}
				if ($from === null || $x["kept"] !== null) {
					continue;
				}
				$to = $move[$from];
				if ($x["d"][10] === "added") {
					$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, "an added record keeps the value it was added with"];
					continue;
				}
				// The derived SPF names no address: it takes the new one when the section's A/AAAA rows do.
				$derives = $type === "TXT" ? in_array(addr($to), spf_addrs($s, $o), true) : addr($value) === $to;
				if (!$derives) {
					$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, "the box derives no $to for $from here"];
					continue;
				}
			}
			// The status a row had before its exception was weighed: what holds the name decides what can be written.
			$note = $delegation !== "" ? $delegation : ($skip[$x["base"]] ?? "");
			if ($x["base"] === "cname") {
				$note = "a CNAME or ALIAS holds the name";
			}
			foreach ($hits as $i) {
				if ($actual[$i][5] === "ctl") {
					$note = "a record here holds a control character, correct it at the provider";
				}
			}
			// A CNAME goes only on a confirmation, and never at the apex, where an ALIAS stands in for the main IP.
			$cnameOk = $x["base"] === "cname" && $delegation === "" && $x["kept"] === null && $rel !== "@" && !$hits;
			if ($cnameOk && in_array("cname", $confirm, true)) {
				foreach (at($rel, "CNAME") ?: at($rel, "ALIAS") as $i) {
					if (!isset($replaced[$i])) {
						$replaced[$i] = true;
						$ops[$k][] = ["rem", $rel, $actual[$i][1], "", $actual[$i][3], $actual[$i][6], "replaced by records of its own"];
					}
				}
				$v = match ($type) {
					"MX" => host($value),
					"TXT" => txt($value),
					default => $value,
				};
				$why = $x["d"][10] === "added" ? "an added record, in place of the CNAME" : "in place of the CNAME";
				$ops[$k][] = ["add", $rel, $type, $prio, $ttl, $v, $why];
				continue;
			}
			if ($note !== "") {
				$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, $note, $cnameOk ? "cname" : ""];
				continue;
			}
			if ($x["d"][10] === "added") {
				$ops[$k] = added_ops($x, $confirm);
				continue;
			}
			if ($x["kept"] !== null) {
				$ops[$k] = kept_ops($x, $include, $mainV6);
				continue;
			}
			if ($normal) {
				$ops[$k] = spf_normal_ops($x);
				continue;
			}
			if ($move !== null && $type === "TXT") {
				foreach ($hits as $i) {
					$old = txt($actual[$i][4]);
					$keep = spf_kept_old($old, $rel, $s, $o, $move);
					$new = spf_move($old, fqdn($rel, $zone), $move, $keep);
					if ($new !== $old) {
						$t = $actual[$i][3] !== "" ? $actual[$i][3] : $ttl;
						// Both notes keep the record out of the written ones in h-sync-dns-zone.
						$why = $keep ? "the old address kept beside a and mx" : "the new address beside the old one";
						$ops[$k][] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
						$ops[$k][] = ["add", $rel, $type, "", $t, $new, $why];
					}
				}
				continue;
			}
			if ($type === "A" || $type === "AAAA") {
				$want = addr($value);
				$ownAll = array_values(array_filter($hits, fn ($i) => $actual[$i][5] === ""));
				// A move removes only the old addresses, whatever else stands beside them.
				$own = array_values(array_filter($ownAll, fn ($i) => $move === null || isset($move[addr($actual[$i][4])])));
				if ($rel === "www" && $include) {
					// What the apex holds once this plan ran: its wanted value when it will be in line, else what it
					// holds now.
					$after = ($apexDone[$type] ?? false)
						? (isset($apexWant[$type]) ? [$apexWant[$type]] : [])
						: array_values(array_unique(array_map(fn ($i) => addr($actual[$i][4]), at("@", $type))));
					if ($after === [$want]) {
						foreach ($own as $i) {
							if (addr($actual[$i][4]) !== $want) {
								$ops[$k][] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
							}
						}
						continue;
					}
					// An apex without this type leaves www free for a record of its own, as below.
					if ($after) {
						if (!in_array("include", $confirm, true)) {
							$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, "www follows the apex (www include), which differs", "include"];
							continue;
						}
						// Confirmed: the include goes off, and www gets records of its own, as below.
						$includeOff = true;
					}
				}
				$main = null;
				foreach ($hits as $i) {
					if ($actual[$i][5] === "main") {
						$main = $i;
					}
				}
				if ($rel === "@" && $type === "AAAA" && $main !== null) {
					$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, "the main IP holds an IPv6 address"];
					continue;
				}
				$have = false;
				foreach ($ownAll as $i) {
					if (addr($actual[$i][4]) === $want) {
						$have = true;
					} elseif (in_array($i, $own, true)) {
						$ops[$k][] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
					}
				}
				$useMain = $rel === "@" && $type === "A" && ($main !== null || (!$ownAll && !$mainV6));
				if ($useMain) {
					$mainMoves = $move === null || $main === null || isset($move[addr($actual[$main][4])]);
					if ($mainMoves && ($main === null || addr($actual[$main][4]) !== $want)) {
						$ops[$k][] = ["main", $rel, $type, "", $ttl, $want, ""];
					}
				} elseif (!$have) {
					$why = $status === "wildcard" ? "a record of its own beats the wildcard" : "";
					$ops[$k][] = ["add", $rel, $type, "", $ttl, $value, $why];
				}
			} elseif ($type === "MX") {
				$want = (int) $prio . " " . host($value);
				$have = false;
				foreach ($hits as $i) {
					if ((int) $actual[$i][2] . " " . host($actual[$i][4]) === $want) {
						$have = true;
					} else {
						$ops[$k][] = ["rem", $rel, $type, $actual[$i][2], $actual[$i][3], $actual[$i][6], ""];
					}
				}
				if (!$have) {
					$ops[$k][] = ["add", $rel, $type, $prio, $ttl, host($value), ""];
				}
			} elseif ($type === "TXT") {
				$want = txt($value);
				$kind = preg_match('/^v=(spf1|dmarc1|dkim1)\b/i', $want, $m) ? strtolower($m[1]) : "";
				if (!$hits) {
					$ops[$k][] = ["add", $rel, $type, "", $ttl, $want, ""];
				} elseif ($kind === "spf1") {
					if (count($hits) > 1) {
						$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, "more than one SPF record, merge them by hand"];
						continue;
					}
					$i = $hits[0];
					$old = txt($actual[$i][4]);
					$t = $actual[$i][3] !== "" ? $actual[$i][3] : $ttl;
					$ops[$k][] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
					$ops[$k][] = ["add", $rel, $type, "", $t, spf_merge($old, $x["missed"]), "merged into the existing record"];
				} elseif ($kind === "dkim1") {
					foreach ($hits as $i) {
						if (preg_match('/^v=dkim1\b/i', txt($actual[$i][4]))) {
							$ops[$k][] = ["rem", $rel, $type, "", $actual[$i][3], $actual[$i][6], ""];
						}
					}
					$ops[$k][] = ["add", $rel, $type, "", $ttl, $want, ""];
				} elseif ($kind === "dmarc1") {
					$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, "more than one DMARC record, remove one by hand"];
				} else {
					$ops[$k][] = ["skip", $rel, $type, "", $ttl, $value, "a different record of its own holds the name"];
				}
			}
		}
	}
	// Without the include every www row of the section needs a record of its own, also those it served.
	if ($includeOff) {
		$ops[-1] = [["include", "www", "", "", "", "off", "www stops following the apex"]];
		foreach ($results as $k => $x) {
			[, , $s, $o, , $rel, $type, , $ttl, $value] = $x["d"];
			if ($rel !== "www" || $s !== $section || strtolower($o) !== $object || $x["kept"] !== null || $x["d"][10] !== ""
				|| !in_array($type, ["A", "AAAA"], true) || in_array("add", array_column($ops[$k] ?? [], 0), true)) {
				continue;
			}
			$own = array_filter(at("www", $type), fn ($i) => $actual[$i][5] === "" && addr($actual[$i][4]) === addr($value));
			if (!$own) {
				$ops[$k][] = ["add", "www", $type, "", $ttl, $value, "www stops following the apex"];
			}
		}
	}
	// The provider drops the own www records equal to the apex when the include goes on, any other stays beside it.
	if ($follow) {
		$ops[-1] = [["include", "www", "", "", "", "on", "www follows the apex again"]];
		foreach (["A", "AAAA"] as $type) {
			foreach (at("www", $type) as $i) {
				if ($actual[$i][5] === "" && addr($actual[$i][4]) !== ($apexWant[$type] ?? "")) {
					$ops[-1][] = ["rem", "www", $type, "", $actual[$i][3], $actual[$i][6], "would stand beside the apex"];
				}
			}
		}
	}
	ksort($ops);
	foreach ($ops as $list) {
		foreach ($list as $op) {
			out(array_slice($op, 0, 7) + (($op[7] ?? "") !== "" ? [7 => $op[7]] : []));
		}
	}
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
$delegation = match ($status) {
	"ok" => "",
	"unchecked" => "public DNS did not answer for the zone's name servers",
	default => "the public name servers are not the provider's, nothing is written",
};
if ($planFor === null) {
	out(["delegation", $zone, "", "@", "NS", "", "", implode(" ", $ns), $status, implode(" ", $public), $note]);
}

/** @var list<array{d: list<string>, status: string, base: string, hits: list<int>, missed: list<string>, kept: ?list<string>}> $results */
$results = [];
foreach ($desired as $dk => $d) {
	[, , $section, $object, $owner, $rel, $type, $prio, $ttl, $value, $origin] = $d;
	$status = "missing";
	$shown = [];
	$hits = [];
	$missed = [];
	$found = [];
	$note = "";
	$cut = delegated($rel);
	Dig::$failed = false;
	if (isset($shadowed[$dk])) {
		$status = "shadowed";
		$note = $shadowed[$dk];
	} elseif ($cut !== "" && !($origin === "added" && $type === "NS" && $cut === $rel)) {
		$status = "delegated";
		$to = implode(", ", array_map(fn ($i) => $actual[$i][4], at($cut, "NS")));
		$note = "delegated to $to: not answered by this zone";
	} elseif ($origin === "added" && ($type === "CNAME" || $type === "ALIAS")) {
		$hits = at($rel, $type);
		foreach ($hits as $i) {
			$used[$i] = true;
			$shown[] = $actual[$i][4];
			$found[] = host($actual[$i][4]);
		}
		if ($hits) {
			$status = $found === [host($value)] ? "ok" : "different";
		} elseif (array_diff(at($rel), at($rel, "CNAME"), at($rel, "ALIAS"))) {
			$note = "other records hold the name, no $type can stand beside them";
		}
	} elseif ($origin === "added" && !at($rel, "CNAME")) {
		$want = added_canon($type, $prio, $value);
		foreach (at($rel, $type) as $i) {
			if (added_canon($type, $actual[$i][2], $actual[$i][4]) === $want) {
				$hits[] = $i;
				$used[$i] = true;
				$shown[] = $type === "TXT" ? txt($actual[$i][4]) : $actual[$i][4];
			}
		}
		$found = $shown;
		$status = $hits ? "ok" : "missing";
	} elseif ($origin === "" && ($type === "A" || $type === "AAAA")) {
		$want = addr($value);
		$hits = at($rel, $type);
		if ($hits) {
			$vals = [];
			foreach ($hits as $i) {
				$used[$i] = true;
				$vals[] = addr($actual[$i][4]);
				$shown[] = $actual[$i][4];
				$found[] = addr($actual[$i][4]);
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
				$found[] = "$via " . host($target);
				$vals = resolve($target, $type);
			} elseif ($wc !== "" && (at($wc, $type) || at($wc, "CNAME"))) {
				$via = "wildcard $wc";
				$found[] = $via;
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
		$found[] = "CNAME " . host($actual[$cn[0]][4]);
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
		$found = $vals;
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
		$found = $shown;
		if ($hits) {
			if ($kind === "spf1") {
				if (count($hits) > 1) {
					$status = "different";
					$note = "more than one SPF record (permerror)";
				} else {
					$missed = [];
					foreach (spf_addrs($section, $object) as $ip) {
						if (!spf_pass_in_line($ip, $shown[0], fqdn($rel, $zone))) {
							$missed[] = $ip;
						}
					}
					$status = $missed ? (Dig::failed() ? "unchecked" : "different") : "ok";
					$why = Dig::failed() ? "public DNS did not answer" : "does not authorise " . implode(", ", $missed);
					$note = $missed ? $why : "";
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
	// An exception stands in for the derived value until it is lifted: matching it is "exception", and a row that
	// left it is different, even when the provider holds the derived value again.
	$base = $status;
	$accepted = $status === "delegated" || $origin !== "" ? null : ($kept["$owner\t$rel\t$type"] ?? null);
	if ($accepted !== null) {
		$keeps = $accepted ? implode(" | ", $accepted) : "no record";
		if (same_set($type, $found, $accepted)) {
			$status = "exception";
			$note = "kept as an exception";
		} else {
			$note = $status === "ok" ? "the provider holds the derived value, the exception keeps $keeps"
				: "differs from the exception, which keeps $keeps";
			$status = "different";
		}
	}
	// A blanked value is not the stored one, so it never counts as in place.
	$ctl = ctl_note($hits);
	if ($ctl !== "") {
		$status = in_array($status, ["ok", "exception"], true) ? "different" : $status;
		$note = trim("$note; $ctl", "; ");
	}
	$results[] = ["d" => $d, "status" => $status, "base" => $base, "hits" => $hits, "missed" => $missed,
		"kept" => $accepted];
	if ($planFor === null) {
		out([$section, $object, $owner, $rel, $type, $prio, $ttl, $value, $status, implode(" | ", $shown), $note,
			implode("\x1f", $found), $accepted === null ? "" : "yes", implode("\x1f", $accepted ?? []), $origin]);
	}
}

// Records no desired row consumed that HestiaRE wrote: what a deleted domain or alias, or a value no longer
// derived, left behind. Index of the actual row => the written row.
/** @var array<int, list<string>> $orphans */
$orphans = [];
foreach ($actual as $i => $r) {
	if (isset($used[$i])) {
		continue;
	}
	foreach ($written as $w) {
		if (written_at($w, $r)) {
			$orphans[$i] = $w;
			break;
		}
	}
}

if ($planFor !== null && $planFor[0] === "orphan") {
	foreach ($orphans as $i => $w) {
		$r = $actual[$i];
		if (strtolower($w[5]) !== $planFor[1] || $w[0] !== $planFor[2]) {
			continue;
		}
		if ($delegation !== "") {
			out(["skip", $r[0], $r[1], $r[2], $r[3], $r[4], $delegation]);
		} else {
			out(["rem", $r[0], $r[1], in_array($r[1], ["MX", "SRV"], true) ? $r[2] : "", $r[3], $r[6], "orphaned"]);
		}
	}
	exit(0);
}
if ($planFor !== null) {
	plan($results, $planFor[0], $planFor[1], $delegation, $confirm, $move);
	exit(0);
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
	if (isset($orphans[$i])) {
		$w = $orphans[$i];
		$note = "written by HestiaRE for $w[4] $w[5], which no longer derives it";
		out(["orphan", $w[5], $w[0], $r[0], $r[1], $r[2], $r[3], $r[4], "orphan", "", $note]);
		continue;
	}
	$note = $r[5] === "main" ? "main IP" : ($r[5] === "include" ? "follows the apex (www include)" : ctl_note([$i]));
	out([$mine ? "foreign" : "hidden", "", "", $r[0], $r[1], $r[2], $r[3], $r[4], "foreign", "", $note]);
}
