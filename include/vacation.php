<?php

// Out-of-office in a mailbox's active Sieve script (#784), in the format of the webmail that owns it:
// Roundcube's managesieve rules or Tachyon's filter set. Only the one vacation command is touched; every
// other byte of the script is carried over. A script in neither format, or with a second vacation command, is read,
// never written.
//
//   state   FILE              -> JSON {format, enabled, message}; FILE may be empty or absent
//   enable  FORMAT FILE MSGFILE -> the new script on stdout; FORMAT only decides an absent/empty FILE
//   disable FILE              -> the new script on stdout
//   enabled FILE...           -> yes|no per FILE, one per line
//   message FILE OUT          -> yes (message written to OUT), no, or custom
// Exit 3, silent: the script is not one this file can write, so it is left to the webmail. Exit 1: anything else,
// including a result that fails its own re-read; nothing is printed then, so the caller writes nothing.
//
// Tachyon rebuilds its script from the base64 JSON header of each filter, so that JSON is what it
// reads back; the Sieve text below it is written by the same rules as its sieve.js (4.2.x), so a save
// in Tachyon reproduces it. Re-check both against a new Tachyon pin.

const RULE_NAME = "Autoreply";
const DAYS = 7;
// Sieve's vacation answers spam as readily as mail. Same test as exim's autoreply router, which skips X-Spam-Status
// beginning with Yes; change both together. :is rather than :matches "Yes*", because Tachyon has no :matches.
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

// The tokens of $s as [type, start, end]: comment, string, text, tag, word, number, or the character itself.
// Strings and comments are whole tokens, so nothing inside them reads as a command or a rule head.
function sieve_tokens(string $s): array
{
	$t = [];
	$n = strlen($s);
	$i = 0;
	while ($i < $n) {
		$c = $s[$i];
		if (ctype_space($c)) {
			$i++;
			continue;
		}
		if ($c === '#') {
			$type = 'comment';
			$e = strpos($s, "\n", $i);
			$e = $e === false ? $n : $e;
		} elseif ($c === '/' && ($s[$i + 1] ?? '') === '*') {
			$type = 'comment';
			$e = strpos($s, '*/', $i + 2);
			$e === false && throw new UnexpectedValueException('unterminated comment');
			$e += 2;
		} elseif ($c === '"') {
			$type = 'string';
			for ($e = $i + 1; $e < $n && $s[$e] !== '"'; $e++) {
				$s[$e] === '\\' && $e++;
			}
			$e >= $n && throw new UnexpectedValueException('unterminated string');
			$e++;
		} elseif (substr($s, $i, 5) === 'text:') {
			$type = 'text';
			preg_match('/\Gtext:[ \t]*(?:#[^\n]*)?\r?\n.*?^\.\r?$/ms', $s, $m, 0, $i) || throw new UnexpectedValueException('unterminated text:');
			$e = $i + strlen($m[0]);
		} elseif (preg_match('/\G(:?)[A-Za-z_]\w*/', $s, $m, 0, $i)) {
			$type = $m[1] === ':' ? 'tag' : 'word';
			$e = $i + strlen($m[0]);
		} elseif (preg_match('/\G\d+[KMG]?/', $s, $m, 0, $i)) {
			$type = 'number';
			$e = $i + strlen($m[0]);
		} else {
			$type = $c;
			$e = $i + 1;
		}
		$t[] = [$type, $i, $e];
		$i = $e;
	}
	return $t;
}

// The value of a string or text: token.
function sieve_value(string $s, array $tok): string
{
	$raw = substr($s, $tok[1], $tok[2] - $tok[1]);
	if ($tok[0] === 'string') {
		return preg_replace('/\\\\(.)/s', '$1', substr($raw, 1, -1));
	}
	preg_match('/^text:[^\n]*\n(.*)^\.\r?$/ms', $raw, $m);
	$body = preg_replace('/^\.\./m', '.', str_replace("\r\n", "\n", $m[1]));
	return preg_replace('/\n\z/', '', $body);
}

// Every vacation command in $s outside comments and strings: [start, end after ';', reason, reason start, reason end].
// One this parser cannot read is an error, never "no vacation": that would let enable add a second one.
function find_vacations(string $s): array
{
	$tok = array_values(array_filter(sieve_tokens($s), fn ($t) => $t[0] !== 'comment'));
	$out = [];
	foreach ($tok as $k => $t) {
		if ($t[0] !== 'word' || substr($s, $t[1], $t[2] - $t[1]) !== 'vacation') {
			continue;
		}
		$next = function () use (&$k, $tok): array {
			return $tok[++$k] ?? throw new UnexpectedValueException('vacation runs to the end of the script');
		};
		$reason = null;
		while (($a = $next())[0] !== ';') {
			if ($reason !== null) {
				throw new UnexpectedValueException('vacation has more after its reason');
			}
			if ($a[0] === 'string' || $a[0] === 'text') {
				$reason = $a;
				continue;
			}
			if ($a[0] !== 'tag') {
				throw new UnexpectedValueException('unreadable vacation argument');
			}
			$arg = match (substr($s, $a[1] + 1, $a[2] - $a[1] - 1)) {
				'days', 'seconds' => ['number'],
				'subject', 'from', 'handle' => ['string', 'text'],
				'addresses' => ['string', 'text', '['],
				'mime' => [],
				default => throw new UnexpectedValueException('unknown vacation tag'),
			};
			if ($arg) {
				$v = $next();
				in_array($v[0], $arg, true) || throw new UnexpectedValueException('unreadable vacation tag argument');
				while ($v[0] === '[' || $v[0] === ',') {
					$v = $next();
					$v[0] === 'string' || throw new UnexpectedValueException('unreadable vacation :addresses');
					$v = $next();
					in_array($v[0], [',', ']'], true) || throw new UnexpectedValueException('unreadable vacation :addresses');
				}
			}
		}
		$reason ?? throw new UnexpectedValueException('vacation without a reason');
		$out[] = [$t[1], $a[2], sieve_value($s, $reason), $reason[1], $reason[2]];
	}
	return $out;
}

// require ["a","b"]; gains NAME unless it is already there; a script without one gets it first.
// Only ever given Roundcube's lone require line or Tachyon's head, which carry nothing else.
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

// Preamble plus one chunk per "# rule:[...]" comment at a line start, each chunk running to the next one. Only real
// comments count: a message line reading "# rule:[" inside a text: literal is no rule head.
function rc_split(string $s): array
{
	$cuts = [];
	foreach (sieve_tokens($s) as [$type, $a]) {
		if ($type === 'comment' && ($a === 0 || $s[$a - 1] === "\n") && substr($s, $a, 8) === '# rule:[') {
			$cuts[] = $a;
		}
	}
	$rules = [];
	foreach ($cuts as $k => $a) {
		$rules[] = substr($s, $a, ($cuts[$k + 1] ?? strlen($s)) - $a);
	}
	return [substr($s, 0, $cuts[0] ?? strlen($s)), $rules];
}

function rc_rule_enabled(string $rule): bool
{
	return !preg_match('/^# rule:\[[^\n]*\]\r?\nif false # /', $rule);
}

// The rules carrying a vacation command; format_of has made sure there is at most one when writing.
function rc_vacation_rules(array $rules): array
{
	return array_keys(array_filter($rules, fn ($r) => find_vacations($r) !== []));
}

// The enabled vacation rule, else the first one. Several only reach here to be shown, never written.
function rc_state(string $s): array
{
	[, $rules] = rc_split($s);
	$keys = rc_vacation_rules($rules);
	$on = array_values(array_filter($keys, fn ($k) => rc_rule_enabled($rules[$k])));
	$k = $on[0] ?? $keys[0] ?? null;
	if ($k === null) {
		return ['enabled' => false, 'message' => ''];
	}
	return ['enabled' => rc_rule_enabled($rules[$k]), 'message' => find_vacations($rules[$k])[0][2]];
}

// Roundcube writes CRLF, and so does a new script; one that came with LF keeps it.
function rc_eol(string $s): string
{
	return trim($s) === '' || str_contains($s, "\r\n") ? "\r\n" : "\n";
}

// A text: literal ends after its "." line end; the caller drops the one that followed the old reason.
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
	$k = rc_vacation_rules($rules)[0] ?? null;
	if ($k === null) {
		$rule = implode($eol, ['# rule:[' . RULE_NAME . ']', 'if not header :is ' . sieve_quote(SPAM_HEADER) . ' "Yes"', '{',
			"\tvacation :days " . DAYS . ' ' . rc_reason($msg, $eol) . ';', '}', '']);
		if ($pre !== '' && !str_ends_with($pre, "\n")) {
			$pre .= $eol;
		}
		return require_add($pre, 'vacation', $eol) . $rule . implode('', $rules);
	}
	// Only the reason changes: the tags the customer set (:days, :subject, :addresses) stay.
	$r = $rules[$k];
	$v = find_vacations($r)[0];
	$r = substr($r, 0, $v[3]) . rc_reason($msg, $eol) . ltrim(substr($r, $v[4]), "\r\n");
	$rules[$k] = preg_replace('/^(# rule:\[[^\n]*\]\r?\n)if false # /', '$1if ', $r, 1);
	return require_add($pre, 'vacation', $eol) . implode('', $rules);
}

function rc_disable(string $s): string
{
	[$pre, $rules] = rc_split($s);
	$k = rc_vacation_rules($rules)[0] ?? null;
	if ($k !== null) {
		$rules[$k] = preg_replace('/^(# rule:\[[^\n]*\]\r?\n)if (?!false # )/', '$1if false # ', $rules[$k], 1);
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

function tx_vacation_blocks(array $blocks): array
{
	return array_keys(array_filter($blocks, fn ($b) => ($b['json']['ActionType'] ?? '') === 'Vacation'));
}

// The enabled Vacation filter, else the first one. Several only reach here to be shown, never written.
function tx_state(string $s): array
{
	[, $blocks] = tx_split($s);
	$keys = tx_vacation_blocks($blocks);
	$on = array_values(array_filter($keys, fn ($k) => !empty($blocks[$k]['json']['Enabled'])));
	$k = $on[0] ?? $keys[0] ?? null;
	if ($k === null) {
		return ['enabled' => false, 'message' => ''];
	}
	$j = $blocks[$k]['json'];
	return ['enabled' => !empty($j['Enabled']), 'message' => (string) ($j['ActionValue'] ?? '')];
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
	$b64 = base64_encode(json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS | JSON_THROW_ON_ERROR));
	$enabled = !empty($j['Enabled']);
	// A disabled filter sits in a bracket comment, which the first */ ends even inside a string, and the rest
	// of the text would run as Sieve. Inside a quoted string \/ reads as / (RFC 5228 2.4.2), and Tachyon
	// rebuilds the text from the header. sieve.js 4.2.4 has the same hole in its own quote().
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
	$k = tx_vacation_blocks($blocks)[0] ?? null;
	if ($k === null) {
		$j = [
			'ID' => bin2hex(random_bytes(8)),
			'Enabled' => true,
			'Name' => RULE_NAME,
			'Conditions' => [['Field' => 'Header', 'Type' => 'NotEqualTo', 'Value' => 'Yes', 'ValueSecond' => SPAM_HEADER]],
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
		$body = implode(TX_EOL, ['if not header :is [' . sieve_quote(SPAM_HEADER) . '] "Yes"', '{', '    ' . tx_vacation_line($j), '}']);
		$new = ['gap' => '', 'text' => tx_block($j, $body), 'json' => $j];
		if ($blocks) {
			// sieve.js joins blocks with one extra line end; the first one takes over the old gap.
			$new['gap'] = $blocks[0]['gap'];
			$blocks[0]['gap'] = TX_EOL;
		}
		array_unshift($blocks, $new);
	} else {
		$j = $blocks[$k]['json'];
		$j['ActionValue'] = $msg;
		$j['Enabled'] = true;
		$body = tx_body($blocks[$k]['text']);
		$v = find_vacations($body)[0] ?? throw new UnexpectedValueException('the Tachyon vacation filter carries no vacation command');
		$blocks[$k]['text'] = tx_block($j, substr_replace($body, tx_vacation_line($j), $v[0], $v[1] - $v[0]));
	}
	return tx_join(require_add($head, 'vacation', TX_EOL), $blocks, $tail);
}

function tx_disable(string $s): string
{
	[$head, $blocks, $tail] = tx_split($s);
	$k = tx_vacation_blocks($blocks)[0] ?? null;
	if ($k !== null && !empty($blocks[$k]['json']['Enabled'])) {
		$j = $blocks[$k]['json'];
		$j['Enabled'] = false;
		$blocks[$k]['text'] = tx_block($j, tx_body($blocks[$k]['text']));
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

// A second vacation command, even a disabled one, makes the script custom: pigeonhole aborts the whole script when
// two vacation actions fire on one message, and which one the panel means is no longer knowable.
function format_of(string $s): string
{
	$f = raw_format($s);
	if ($f === 'roundcube' || $f === 'tachyon') {
		return vacation_count($f, $s) > 1 ? 'custom' : $f;
	}
	return $f;
}

// Tachyon's disabled filters are comments, so its header counts them; live commands count in any case.
function vacation_count(string $f, string $s): int
{
	$live = count(find_vacations($s));
	return $f === 'tachyon' ? max($live, count(tx_vacation_blocks(tx_split($s)[1]))) : $live;
}

function raw_format(string $s): string
{
	if (trim($s) === '') {
		return 'none';
	}
	if (str_contains($s, 'RAINLOOP:SIEVE')) {
		return 'tachyon';
	}
	// Roundcube writes a "# rule:[...]" line above every rule, and nothing but its one require line before the first;
	// a script without rules is only that line. Anything else is not Roundcube's own and stays custom.
	[$pre, $rules] = rc_split($s);
	if (preg_match('/^\s*(require\s+(\[[^\]]*\]|"[^"]*")\s*;\s*)?$/', $pre) && ($rules || trim($pre) !== '')) {
		return 'roundcube';
	}
	return 'custom';
}

function state_of(string $file): array
{
	$s = $file !== '' && is_file($file) ? file_get_contents($file) : '';
	try {
		$f = format_of($s);
		// Several commands in a known format still read like that format: the enabled one is shown.
		return ['format' => $f] + state_in(raw_format($s), $s);
	} catch (UnexpectedValueException) {
		// Unreadable to this parser means unwritable too: custom, left to the webmail. Enabled, because unknown
		// has to count as on: a sync that read it as off would drop a notice the customer set.
		return ['format' => 'custom', 'enabled' => true, 'message' => ''];
	}
}

function state_in(string $f, string $s): array
{
	return match ($f) {
		'roundcube' => rc_state($s),
		'tachyon' => tx_state($s),
		'custom' => ($v = find_vacations($s)) ? ['enabled' => true, 'message' => $v[0][2]] : ['enabled' => false, 'message' => ''],
		default => ['enabled' => false, 'message' => ''],
	};
}

// What was built is read back before it is handed out: same format, at most one vacation command, and the state
// that was asked for. A pattern that did not match leaves the script as it was, and exit 0 would call that success.
function checked(string $want, string $out, bool $on, string $msg = ''): string
{
	$f = format_of($out);
	$st = state_in($f, $out);
	$n = vacation_count($f, $out);
	$live = count(find_vacations($out));
	if ($f !== $want || $n > 1 || $st['enabled'] !== $on || ($on && ($n !== 1 || $st['message'] !== $msg))
		|| ($f === 'tachyon' && $live !== ($on ? 1 : 0))) {
		fail(1, 'the rewritten script does not read back as asked, left unchanged');
	}
	return $out;
}

function usage(): never
{
	fail(1, 'usage: vacation.php state FILE | enabled FILE... | message FILE OUT | enable roundcube|tachyon FILE MSGFILE | disable FILE');
}

ini_set('display_errors', 'stderr');
// A warning is a bug here, and on stdout it would land in the script.
set_error_handler(fn (int $no, string $str) => throw new ErrorException($str, 0, $no));
set_exception_handler(function (Throwable $e): void {
	$e instanceof UnexpectedValueException && exit(3);
	fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
	exit(1);
});
$op = $argv[1] ?? '';
switch ($op) {
	case 'state':
		isset($argv[2]) || usage();
		echo json_encode(state_of($argv[2]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
		break;
	case 'enabled':
		foreach (array_slice($argv, 2) as $f) {
			echo state_of($f)['enabled'] ? 'yes' : 'no', "\n";
		}
		break;
	case 'message':
		($argv[3] ?? '') !== '' || usage();
		$st = state_of($argv[2]);
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
		in_array($want, ['roundcube', 'tachyon'], true) && is_file($msgfile) || usage();
		$s = $file !== '' && is_file($file) ? file_get_contents($file) : '';
		// A browser sends CRLF, the CLI may carry a lone CR; Sieve text wants one line end of its own.
		$msg = rtrim(str_replace(["\r\n", "\r"], "\n", file_get_contents($msgfile)), "\n");
		$f = format_of($s);
		$f === 'none' && ($f = $want);
		$f === 'custom' && fail(3, 'the active Sieve script is not one the webmail manages');
		preg_match('//u', $msg) || fail(1, 'the message is not UTF-8');
		echo checked($f, $f === 'tachyon' ? tx_enable($s, $msg) : rc_enable($s, $msg), true, $msg);
		break;
	case 'disable':
		isset($argv[2]) || usage();
		$s = $argv[2] !== '' && is_file($argv[2]) ? file_get_contents($argv[2]) : '';
		$f = format_of($s);
		echo match ($f) {
			'roundcube' => checked($f, rc_disable($s), false),
			'tachyon' => checked($f, tx_disable($s), false),
			'none' => '',
			default => fail(3, 'the active Sieve script is not one the webmail manages'),
		};
		break;
	default:
		usage();
}
