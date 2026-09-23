<?php

// Out-of-office in a mailbox's active Sieve script (#784), in the format of the webmail that owns it:
// Roundcube's managesieve rules or Tachyon's filter set. Only the one vacation rule is touched; every
// other byte of the script is carried over. A script in neither format, or with a second vacation rule, is read,
// never written.
//
//   state   FILE              -> JSON {format, enabled, message, subject}; FILE may not exist
//   states  FILE...           -> JSON {FILE: state, ...}
//   enable  FORMAT FILE MSGFILE -> the new script on stdout; FORMAT only decides an absent/empty FILE
//   disable FILE              -> the new script on stdout
//   enabled FILE...           -> yes|no per FILE, one per line
//   message FILE OUT          -> yes (message written to OUT), no, or custom
// Exit 3, silent: the script is in no known format, or has more than one vacation rule, so it is not changed here.
//
// Tachyon rebuilds its script from the base64 JSON header of each filter, so that JSON is what it
// reads back; the Sieve text below it is written by the same rules as its sieve.js (4.2.x), so a save
// in Tachyon reproduces it. Re-check both against a new Tachyon pin.

const RULE_NAME = "Autoreply";
const DAYS = 7;
// Sieve's vacation answers spam as readily as mail; exim's router skips it on the same header.
const SPAM_HEADER = "X-Spam-Status";

function fail(int $rc, string $msg): never
{
	$rc === 3 || fwrite(STDERR, "Error: $msg\n");
	exit($rc);
}

function sieve_quote(string $s): string
{
	return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"';
}

// One quoted string or text: literal starting at $i; returns [value, end offset] or null.
function sieve_string(string $s, int $i): ?array
{
	if (($s[$i] ?? '') === '"') {
		$out = '';
		for ($j = $i + 1, $n = strlen($s); $j < $n; $j++) {
			if ($s[$j] === '\\' && $j + 1 < $n) {
				$out .= $s[++$j];
			} elseif ($s[$j] === '"') {
				return [$out, $j + 1];
			} else {
				$out .= $s[$j];
			}
		}
		return null;
	}
	if (substr($s, $i, 5) === 'text:' && preg_match('/\Gtext:[ \t]*(?:#[^\n]*)?\r?\n(.*?)^\.\r?$/ms', $s, $m, 0, $i)) {
		$body = preg_replace('/^\.\./m', '.', str_replace("\r\n", "\n", $m[1]));
		return [preg_replace('/\r?\n$/', '', $body), $i + strlen($m[0])];
	}
	return null;
}

// The vacation command in $s: [start, end, reason, subject] with end after its ';', or null.
function find_vacation(string $s): ?array
{
	if (!preg_match('/(?<![\w:.-])vacation(?=\s)/', $s, $m, PREG_OFFSET_CAPTURE)) {
		return null;
	}
	$start = $m[0][1];
	$i = $start + strlen('vacation');
	$n = strlen($s);
	$subject = '';
	$reason = null;
	while ($i < $n) {
		$c = $s[$i];
		if (ctype_space($c)) {
			$i++;
		} elseif ($c === ';') {
			return $reason === null ? null : [$start, $i + 1, $reason, $subject];
		} elseif ($c === ':' && substr($s, $i, 5) !== 'text:') {
			preg_match('/\G:(\w+)/', $s, $t, 0, $i);
			$tag = $t[1] ?? '';
			$i += strlen($t[0] ?? ':');
			while ($i < $n && ctype_space($s[$i])) {
				$i++;
			}
			if (in_array($tag, ['days', 'seconds'], true)) {
				preg_match('/\G\d+/', $s, $d, 0, $i);
				$i += strlen($d[0] ?? '');
			} elseif (in_array($tag, ['subject', 'from', 'handle'], true)) {
				$v = sieve_string($s, $i) ?? throw new UnexpectedValueException("unreadable vacation :$tag");
				$tag === 'subject' && ($subject = $v[0]);
				$i = $v[1];
			} elseif ($tag === 'addresses') {
				if (($s[$i] ?? '') === '[') {
					$close = strpos($s, ']', $i);
					$i = $close === false ? $n : $close + 1;
				} else {
					$v = sieve_string($s, $i) ?? throw new UnexpectedValueException('unreadable vacation :addresses');
					$i = $v[1];
				}
			}
		} else {
			$v = sieve_string($s, $i);
			if ($v === null) {
				return null;
			}
			[$reason, $i] = $v;
		}
	}
	return null;
}

// require ["a","b"]; gains NAME unless it is already there; a script without one gets it first.
function require_add(string $script, string $name, string $eol): string
{
	if (preg_match('/^require\s+(\[[^\]]*\]|"[^"]*")\s*;/m', $script, $m, PREG_OFFSET_CAPTURE)) {
		$list = $m[1][0];
		if (preg_match('/"' . preg_quote($name, '/') . '"/', $list)) {
			return $script;
		}
		$items = $list[0] === '[' ? trim(substr($list, 1, -1)) : $list;
		$new = 'require [' . ($items === '' ? '' : $items . ',') . '"' . $name . '"];';
		return substr_replace($script, $new, $m[0][1], strlen($m[0][0]));
	}
	return 'require ["' . $name . '"];' . $eol . $script;
}

// ---------------------------------------------------------------- Roundcube

// Preamble plus one chunk per "# rule:[...]" line, each chunk running to the next one.
function rc_split(string $s): array
{
	$parts = preg_split('/^(?=# rule:\[)/m', $s);
	return [array_shift($parts), $parts];
}

function rc_rule_enabled(string $rule): bool
{
	return !preg_match('/^# rule:\[[^\n]*\]\r?\nif false # /', $rule);
}

function rc_state(string $s): array
{
	[, $rules] = rc_split($s);
	$found = null;
	foreach ($rules as $r) {
		if (($v = find_vacation($r)) === null) {
			continue;
		}
		$st = ['enabled' => rc_rule_enabled($r), 'message' => $v[2], 'subject' => $v[3]];
		if ($st['enabled']) {
			return $st;
		}
		$found ??= $st;
	}
	return $found ?? ['enabled' => false, 'message' => '', 'subject' => ''];
}

// Roundcube writes CRLF, and so does a new script; one that came with LF keeps it.
function rc_eol(string $s): string
{
	return trim($s) === '' || str_contains($s, "\r\n") ? "\r\n" : "\n";
}

function rc_reason(string $msg, string $eol): string
{
	if (!str_contains($msg, "\n")) {
		return sieve_quote($msg);
	}
	return "text:" . $eol . str_replace("\n", $eol, preg_replace('/^\./m', '..', $msg)) . $eol . "." . $eol;
}

function rc_enable(string $s, string $msg): string
{
	[$pre, $rules] = rc_split($s);
	$eol = rc_eol($s);
	$target = null;
	foreach ($rules as $k => $r) {
		if (find_vacation($r) !== null && ($target === null || (rc_rule_enabled($r) && !rc_rule_enabled($rules[$target])))) {
			$target = $k;
		}
	}
	if ($target === null) {
		$rule = implode($eol, ['# rule:[' . RULE_NAME . ']', 'if not header :contains ' . sieve_quote(SPAM_HEADER) . ' "Yes"', '{',
			"\tvacation :days " . DAYS . ' ' . rc_reason($msg, $eol) . ';', '}', '']);
		if ($pre !== '' && !str_ends_with($pre, "\n")) {
			$pre .= $eol;
		}
		return require_add($pre, 'vacation', $eol) . $rule . implode('', $rules);
	}
	$r = $rules[$target];
	$v = find_vacation($r);
	$cmd = substr($r, $v[0], $v[1] - $v[0]);
	// Only the reason changes: the tags the customer set (:days, :subject, :addresses) stay.
	$r = substr_replace($r, rc_command_with_reason($cmd, $msg, $eol), $v[0], $v[1] - $v[0]);
	$r = preg_replace('/^(# rule:\[[^\n]*\]\r?\n)if false # /', '$1if ', $r, 1);
	$rules[$target] = $r;
	return require_add($pre, 'vacation', $eol) . implode('', $rules);
}

// The command with its last string argument (the reason) replaced.
function rc_command_with_reason(string $cmd, string $msg, string $eol): string
{
	$i = strlen('vacation');
	$n = strlen($cmd);
	$last = null;
	while ($i < $n) {
		$c = $cmd[$i];
		if ($c === ':' && substr($cmd, $i, 5) !== 'text:') {
			preg_match('/\G:(\w+)\s*/', $cmd, $t, 0, $i);
			$i += strlen($t[0]);
			if (in_array($t[1], ['subject', 'from', 'handle'], true) || ($t[1] === 'addresses' && ($cmd[$i] ?? '') !== '[')) {
				$i = sieve_string($cmd, $i)[1];
			} elseif ($t[1] === 'addresses') {
				$i = strpos($cmd, ']', $i) + 1;
			} else {
				preg_match('/\G\d*/', $cmd, $d, 0, $i);
				$i += strlen($d[0]);
			}
		} elseif ($c === '"' || substr($cmd, $i, 5) === 'text:') {
			$v = sieve_string($cmd, $i);
			$last = [$i, $v[1]];
			$i = $v[1];
		} else {
			$i++;
		}
	}
	// A text: literal carries its own line end before the ';'.
	return substr($cmd, 0, $last[0]) . rc_reason($msg, $eol) . ltrim(substr($cmd, $last[1]), "\r\n");
}

function rc_disable(string $s): string
{
	[$pre, $rules] = rc_split($s);
	foreach ($rules as $k => $r) {
		if (find_vacation($r) !== null && rc_rule_enabled($r)) {
			$rules[$k] = preg_replace('/^(# rule:\[[^\n]*\]\r?\n)if /', '$1if false # ', $r, 1);
		}
	}
	return $pre . implode('', $rules);
}

// ---------------------------------------------------------------- Tachyon

const TX_EOL = "\r\n";

// [head, [[block, json], ...], tail]: blocks are "/*\r\nBEGIN:FILTER:..." through "/* END:FILTER */\r\n".
function tx_split(string $s): array
{
	preg_match_all('#/\*\r?\nBEGIN:FILTER:.*?/\* END:FILTER \*/\r?\n#s', $s, $m, PREG_OFFSET_CAPTURE);
	if (!$m[0]) {
		return [$s, [], ''];
	}
	$head = substr($s, 0, $m[0][0][1]);
	$blocks = [];
	$pos = $m[0][0][1];
	foreach ($m[0] as [$text, $off]) {
		$gap = substr($s, $pos, $off - $pos);
		preg_match('/BEGIN:HEADER(.+?)END:HEADER/s', $text, $h);
		$json = json_decode(base64_decode(preg_replace('/\s+/', '', $h[1] ?? '')), true);
		$blocks[] = ['gap' => $gap, 'text' => $text, 'json' => is_array($json) ? $json : null];
		$pos = $off + strlen($text);
	}
	return [$head, $blocks, substr($s, $pos)];
}

function tx_state(string $s): array
{
	[, $blocks] = tx_split($s);
	$found = null;
	foreach ($blocks as $b) {
		$j = $b['json'];
		if (($j['ActionType'] ?? '') !== 'Vacation') {
			continue;
		}
		$st = ['enabled' => (bool) ($j['Enabled'] ?? false), 'message' => (string) ($j['ActionValue'] ?? ''), 'subject' => (string) ($j['ActionValueSecond'] ?? '')];
		if ($st['enabled']) {
			return $st;
		}
		$found ??= $st;
	}
	return $found ?? ['enabled' => false, 'message' => '', 'subject' => ''];
}

// sieve.js quote() and its StripSpaces, which replaces only the FIRST run of white space.
function tx_strip(string $s): string
{
	return preg_replace('/\s+/', ' ', $s, 1);
}

function tx_vacation_line(array $j): string
{
	$days = 1;
	$third = (string) ($j['ActionValueThird'] ?? '');
	if ($third !== '') {
		$days = max(1, (int) $third);
	}
	$subject = (string) ($j['ActionValueSecond'] ?? '');
	$subject = $subject !== '' ? ':subject ' . sieve_quote(tx_strip($subject)) . ' ' : '';
	$addresses = '';
	$fourth = (string) ($j['ActionValueFourth'] ?? '');
	if ($fourth !== '') {
		$list = array_map('sieve_quote', array_values(array_filter(explode(',', $fourth), fn ($a) => $a !== '')));
		$list && ($addresses = ':addresses [' . implode(', ', $list) . '] ');
	}
	return 'vacation :days ' . $days . ' ' . $addresses . $subject . sieve_quote((string) $j['ActionValue']) . ';';
}

function tx_block(array $j, string $body): string
{
	$b64 = base64_encode(json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS));
	$enabled = !empty($j['Enabled']);
	// A disabled filter sits in a bracket comment, which the first */ ends even inside a string, and the rest of the text
	// would run as Sieve. Inside a quoted string \/ reads as / (RFC 5228 2.4.2), and Tachyon rebuilds the text from the header.
	// sieve.js 4.2.4 has the same hole in its own quote().
	if (!$enabled) {
		$body = str_replace('*/', '*\\/', $body);
	}
	return implode(TX_EOL, [
		'/*',
		'BEGIN:FILTER:' . $j['ID'],
		'BEGIN:HEADER',
		implode(TX_EOL, str_split($b64, 74)) . TX_EOL . 'END:HEADER',
		'*/',
		$enabled ? '' : '/* @Filter is disabled ',
		$body,
		$enabled ? '' : '*/',
		'/* END:FILTER */',
		'',
	]);
}

// The Sieve text of a block, between the enabled/disabled marker lines.
function tx_body(string $text): string
{
	$lines = explode(TX_EOL, $text);
	$k = array_search('END:HEADER', $lines, true);
	return implode(TX_EOL, array_slice($lines, $k + 3, count($lines) - $k - 6));
}

function tx_new_script(): string
{
	return 'require ["vacation"];' . TX_EOL . TX_EOL . implode(TX_EOL, ['# This is Tachyon sieve script.', "# Please don't change anything here.", '# RAINLOOP:SIEVE', '']) . TX_EOL;
}

function tx_enable(string $s, string $msg): string
{
	if (trim($s) === '') {
		$s = tx_new_script();
	}
	[$head, $blocks, $tail] = tx_split($s);
	$target = null;
	foreach ($blocks as $k => $b) {
		if (($b['json']['ActionType'] ?? '') === 'Vacation' && ($target === null || (!empty($b['json']['Enabled']) && empty($blocks[$target]['json']['Enabled'])))) {
			$target = $k;
		}
	}
	if ($target === null) {
		$j = [
			'ID' => bin2hex(random_bytes(8)),
			'Enabled' => true,
			'Name' => RULE_NAME,
			'Conditions' => [['Field' => 'Header', 'Type' => 'NotContains', 'Value' => 'Yes', 'ValueSecond' => SPAM_HEADER]],
			'ConditionsType' => 'Any',
			'ActionType' => 'Vacation',
			'ActionValue' => $msg,
			'ActionValueSecond' => '',
			// A string, as Tachyon writes a filter it re-saves unopened; opening the filter turns it into a number.
			'ActionValueThird' => (string) DAYS,
			'ActionValueFourth' => '',
			'Keep' => true,
			// Stop would end the customer's filters behind it; this one comes first.
			'Stop' => false,
			'MarkAsRead' => false,
		];
		$body = implode(TX_EOL, ['if not header :contains [' . sieve_quote(SPAM_HEADER) . '] "Yes"', '{', '    ' . tx_vacation_line($j), '}']);
		$new = ['gap' => '', 'text' => tx_block($j, $body), 'json' => $j];
		if ($blocks) {
			// sieve.js joins blocks with one extra line end; the first one takes over the old gap.
			$new['gap'] = $blocks[0]['gap'];
			$blocks[0]['gap'] = TX_EOL;
		}
		array_unshift($blocks, $new);
	} else {
		$b = $blocks[$target];
		$j = $b['json'];
		$j['ActionValue'] = $msg;
		$j['Enabled'] = true;
		$body = tx_body($b['text']);
		$v = find_vacation($body) ?? fail(3, 'the Tachyon vacation filter carries no readable vacation command');
		$body = substr_replace($body, tx_vacation_line($j), $v[0], $v[1] - $v[0]);
		$blocks[$target]['text'] = tx_block($j, $body);
	}
	return tx_join(require_add($head, 'vacation', TX_EOL), $blocks, $tail);
}

function tx_disable(string $s): string
{
	[$head, $blocks, $tail] = tx_split($s);
	foreach ($blocks as $k => $b) {
		if (($b['json']['ActionType'] ?? '') === 'Vacation' && !empty($b['json']['Enabled'])) {
			$j = $b['json'];
			$j['Enabled'] = false;
			$blocks[$k]['text'] = tx_block($j, tx_body($b['text']));
		}
	}
	return tx_join($head, $blocks, $tail);
}

function tx_join(string $head, array $blocks, string $tail): string
{
	$out = $head;
	foreach ($blocks as $b) {
		$out .= $b['gap'] . $b['text'];
	}
	return $out . $tail;
}

// ---------------------------------------------------------------- dispatch

// A second vacation rule, even a disabled one, makes the script custom: pigeonhole aborts the whole script when two
// vacation actions fire on one message, and which rule the panel means is no longer knowable.
function format_of(string $s): string
{
	$f = raw_format($s);
	if ($f === 'roundcube' || $f === 'tachyon') {
		return vacation_rules($f, $s) > 1 ? 'custom' : $f;
	}
	return $f;
}

function vacation_rules(string $f, string $s): int
{
	if ($f === 'tachyon') {
		return count(array_filter(tx_split($s)[1], fn ($b) => ($b['json']['ActionType'] ?? '') === 'Vacation'));
	}
	return count(array_filter(rc_split($s)[1], fn ($r) => find_vacation($r) !== null));
}

function raw_format(string $s): string
{
	if (trim($s) === '') {
		return 'none';
	}
	if (str_contains($s, 'RAINLOOP:SIEVE')) {
		return 'tachyon';
	}
	// Roundcube writes a "# rule:[...]" line above every rule, and nothing but its require line
	// before the first one; a script without rules is only that line.
	[$pre, $rules] = rc_split($s);
	if (preg_match('/^\s*(require\s+(\[[^\]]*\]|"[^"]*")\s*;\s*)?$/', $pre) && ($rules || trim($pre) !== '')) {
		return 'roundcube';
	}
	return 'custom';
}

function state_of(string $file): array
{
	$s = is_file($file) ? (string) file_get_contents($file) : '';
	try {
		$f = format_of($s);
		// Several rules in a known format still read like that format: the first enabled one is shown.
		$st = state_in(raw_format($s), $s);
	} catch (UnexpectedValueException) {
		// Unreadable to this parser means unwritable too: shown as custom, left to the webmail.
		return ['format' => 'custom', 'enabled' => true, 'message' => '', 'subject' => ''];
	}
	return ['format' => $f] + $st;
}

function state_in(string $f, string $s): array
{
	return match ($f) {
		'roundcube' => rc_state($s),
		'tachyon' => tx_state($s),
		'custom' => ($v = find_vacation($s)) ? ['enabled' => true, 'message' => $v[2], 'subject' => $v[3]] : ['enabled' => false, 'message' => '', 'subject' => ''],
		default => ['enabled' => false, 'message' => '', 'subject' => ''],
	};
}

set_exception_handler(fn () => exit(3));
$op = $argv[1] ?? '';
$json = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
switch ($op) {
	case 'state':
		echo json_encode(state_of($argv[2] ?? ''), $json), "\n";
		break;
	case 'states':
		$out = [];
		foreach (array_slice($argv, 2) as $f) {
			$out[$f] = state_of($f);
		}
		echo json_encode((object) $out, $json), "\n";
		break;
	case 'enabled':
		foreach (array_slice($argv, 2) as $f) {
			echo state_of($f)['enabled'] ? 'yes' : 'no', "\n";
		}
		break;
	case 'message':
		$st = state_of($argv[2] ?? '');
		if ($st['format'] === 'custom' && $st['enabled']) {
			echo "custom\n";
		} elseif ($st['enabled']) {
			file_put_contents($argv[3], $st['message'] . "\n");
			echo "yes\n";
		} else {
			echo "no\n";
		}
		break;
	case 'enable':
		[, , $want, $file, $msgfile] = $argv + [null, null, '', '', ''];
		$s = is_file($file) ? (string) file_get_contents($file) : '';
		$msg = rtrim((string) file_get_contents($msgfile), "\r\n");
		$f = format_of($s);
		$f === 'none' && ($f = $want === 'tachyon' ? 'tachyon' : 'roundcube');
		echo match ($f) {
			'roundcube' => rc_enable($s, $msg),
			'tachyon' => tx_enable($s, $msg),
			default => fail(3, 'the active Sieve script is not one the webmail manages; change the vacation there'),
		};
		break;
	case 'disable':
		$s = is_file($argv[2] ?? '') ? (string) file_get_contents($argv[2]) : '';
		echo match (format_of($s)) {
			'roundcube' => rc_disable($s),
			'tachyon' => tx_disable($s),
			'none' => '',
			default => fail(3, 'the active Sieve script is not one the webmail manages; change the vacation there'),
		};
		break;
	default:
		fail(1, 'usage: vacation.php state|states|enabled|message|enable|disable ...');
}
