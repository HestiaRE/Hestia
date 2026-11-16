<?php

// The panel's forward in a mailbox's active Sieve script (#1337), in the format of the webmail that owns it, as
// vacation.php does for the out-of-office: Roundcube's one rule "Forward" with a redirect per target, or one Tachyon
// forward filter per target, since its action takes one address. Only those are touched; every other byte of the
// script is carried over. Each redirect sits under a test that keeps exim's bounce about a target in the mailbox:
// forwarded again it is refused once more and freezes, and a forward-only mailbox would never see it.
//
//   state FILE                        -> JSON {format, rule, targets, keep}; FILE may be empty or absent
//   set   FORMAT FILE KEEP TARGET...  -> the new script on stdout; KEEP yes|no; FORMAT only decides an absent/empty FILE
//   clear FILE                        -> the new script on stdout, without the forward
// Exit 3, silent: the script is not one this file can write (an own script, or a "Forward" rule or filter changed
// beyond its targets and keep), so it is left to the webmail. Exit 1: anything else, nothing printed.
//
// The Tachyon text is written by the same rules as its sieve.js (4.6.0) writes a forward filter, so a save there
// reproduces it. Re-check against a new Tachyon pin, as for vacation.php.

require __DIR__ . '/sieve-rules.php';

const RULE_NAME = 'Forward';
const FAILED_HEADER = 'X-Failed-Recipients';
// Pigeonhole's sieve_max_redirects on both dovecot versions: one more and it runs none of them.
const MAX_TARGETS = 4;

function is_word(string $s, array $tok, string $w): bool
{
	return $tok[0] === 'word' && substr($s, $tok[1], $tok[2] - $tok[1]) === $w;
}

// ---------------------------------------------------------------- Roundcube

function rc_forward_rules(array $rules): array
{
	return array_keys(array_filter($rules, fn ($r) => preg_match('/^# rule:\[' . RULE_NAME . '\]\r?\n/', $r)));
}

// [enabled, targets, keep] of a "Forward" rule, or null when it is more than a forward: a condition other than the
// bounce test, or an action other than redirect.
function rc_forward(string $rule): ?array
{
	$t = array_values(array_filter(sieve_tokens($rule), fn ($x) => $x[0] !== 'comment'));
	$i = 0;
	$at = function (string $type) use (&$i, $t): bool {
		return ($t[$i][0] ?? '') === $type;
	};
	$test = function () use (&$i, $t, $rule, $at): bool {
		if (!is_word($rule, $t[$i] ?? ['', 0, 0], 'not') || !is_word($rule, $t[$i + 1] ?? ['', 0, 0], 'header')) {
			return false;
		}
		$i += 2;
		if (!$at('tag') || substr($rule, $t[$i][1], $t[$i][2] - $t[$i][1]) !== ':contains') {
			return false;
		}
		$i++;
		$list = $at('[');
		$list && $i++;
		if (!$at('string') || strcasecmp(sieve_value($rule, $t[$i]), FAILED_HEADER) !== 0) {
			return false;
		}
		$i++;
		if ($list) {
			if (!$at(']')) {
				return false;
			}
			$i++;
		}
		if (!$at('string')) {
			return false;
		}
		$i++;
		return true;
	};
	if (!is_word($rule, $t[$i++] ?? ['', 0, 0], 'if')) {
		return null;
	}
	$enabled = true;
	if (is_word($rule, $t[$i] ?? ['', 0, 0], 'true')) {
		$i++;
	} elseif (is_word($rule, $t[$i] ?? ['', 0, 0], 'false')) {
		$enabled = false;
		$i++;
	} elseif (is_word($rule, $t[$i] ?? ['', 0, 0], 'allof') || is_word($rule, $t[$i] ?? ['', 0, 0], 'anyof')) {
		$i++;
		if (!$at('(')) {
			return null;
		}
		do {
			$i++;
			if (!$test()) {
				return null;
			}
		} while ($at(','));
		if (!$at(')')) {
			return null;
		}
		$i++;
	} elseif (!$test()) {
		return null;
	}
	if (!$at('{')) {
		return null;
	}
	$i++;
	$targets = [];
	$keep = true;
	while (is_word($rule, $t[$i] ?? ['', 0, 0], 'redirect')) {
		$i++;
		$copy = $at('tag') && substr($rule, $t[$i][1], $t[$i][2] - $t[$i][1]) === ':copy';
		$copy && $i++;
		if (!$at('string')) {
			return null;
		}
		$targets[] = sieve_value($rule, $t[$i++]);
		$keep = $keep && $copy;
		if (!$at(';')) {
			return null;
		}
		$i++;
	}
	if (!$at('}') || isset($t[$i + 1])) {
		return null;
	}
	return [$enabled, $targets, $keep];
}

function rc_forward_state(string $s): array
{
	[, $rules] = rc_split($s);
	$k = rc_forward_rules($rules)[0] ?? null;
	$f = $k === null ? null : rc_forward($rules[$k]);
	if ($f === null || !$f[0] || !$f[1]) {
		return ['targets' => [], 'keep' => true];
	}
	return ['targets' => $f[1], 'keep' => $f[2]];
}

function rc_forward_text(array $targets, bool $keep, string $eol): string
{
	$tests = array_map(fn ($a) => 'not header :contains ' . sieve_quote(FAILED_HEADER) . ' ' . sieve_quote($a), $targets);
	$lines = ['# rule:[' . RULE_NAME . ']', 'if allof (' . implode(', ', $tests) . ')', '{'];
	foreach ($targets as $a) {
		$lines[] = "\tredirect " . ($keep ? ':copy ' : '') . sieve_quote($a) . ';';
	}
	return implode($eol, array_merge($lines, ['}', '']));
}

// The rule goes where the old one was, else behind the vacation, else first.
function rc_forward_set(string $s, array $targets, bool $keep): string
{
	[$pre, $rules] = rc_split($s);
	$eol = rc_eol($s);
	$new = rc_forward_text($targets, $keep, $eol);
	$k = rc_forward_rules($rules)[0] ?? null;
	if ($k !== null) {
		$rules[$k] = $new;
	} else {
		$at = 0;
		foreach ($rules as $j => $r) {
			if (array_filter(sieve_tokens($r), fn ($x) => is_word($r, $x, 'vacation'))) {
				$at = $j + 1;
				break;
			}
		}
		array_splice($rules, $at, 0, [$new]);
		if ($pre !== '' && !str_ends_with($pre, "\n")) {
			$pre .= $eol;
		}
	}
	return rc_copy_require($keep ? rc_require_copy($pre, $eol) : $pre, implode('', $rules));
}

// Roundcube writes its require list sorted.
function rc_require_copy(string $pre, string $eol): string
{
	if (!preg_match('/^require\s+(\[[^\]]*\]|"[^"]*")\s*;/m', $pre, $m, PREG_OFFSET_CAPTURE)) {
		return 'require ["copy"];' . $eol . $pre;
	}
	$items = array_filter(array_map('trim', explode(',', trim($m[1][0], '[]'))), fn ($x) => $x !== '');
	if (in_array('"copy"', $items, true)) {
		return $pre;
	}
	$items[] = '"copy"';
	sort($items);
	return substr_replace($pre, 'require [' . implode(',', $items) . '];', $m[0][1], strlen($m[0][0]));
}

function rc_forward_clear(string $s): string
{
	[$pre, $rules] = rc_split($s);
	foreach (rc_forward_rules($rules) as $k) {
		unset($rules[$k]);
	}
	return rc_copy_require($pre, implode('', $rules));
}

// The script may still use :copy elsewhere; only an unused "copy" leaves the require line, as Roundcube writes it.
function rc_copy_require(string $pre, string $body): string
{
	$copy = array_filter(sieve_tokens($body), fn ($x) => $x[0] === 'tag' && substr($body, $x[1], $x[2] - $x[1]) === ':copy');
	if (!$copy && preg_match('/^require\s+(\[[^\]]*\]|"[^"]*")\s*;(\r?\n)?/m', $pre, $m, PREG_OFFSET_CAPTURE)) {
		$items = array_values(array_filter(array_map('trim', explode(',', trim($m[1][0], '[]'))), fn ($x) => $x !== '' && $x !== '"copy"'));
		$line = $items ? 'require [' . implode(',', $items) . '];' . ($m[2][0] ?? '') : '';
		$pre = substr_replace($pre, $line, $m[0][1], strlen($m[0][0]));
	}
	return $pre . $body;
}

// ---------------------------------------------------------------- Tachyon

function tx_forward_blocks(array $blocks): array
{
	return array_keys(array_filter($blocks, fn ($b) => ($b['json']['Name'] ?? null) === RULE_NAME
		&& ($b['json']['ActionType'] ?? '') === 'Forward'));
}

function tx_forward_ok(array $j): bool
{
	foreach ($j['Conditions'] ?? [] as $c) {
		if (($c['Field'] ?? '') !== 'Header' || ($c['Type'] ?? '') !== 'NotContains'
			|| strcasecmp((string) ($c['ValueSecond'] ?? ''), FAILED_HEADER) !== 0) {
			return false;
		}
	}
	return str_contains((string) ($j['ActionValue'] ?? ''), '@');
}

function tx_forward_state(string $s): array
{
	[, $blocks] = tx_split($s);
	$targets = [];
	$keep = false;
	foreach (tx_forward_blocks($blocks) as $k) {
		$j = $blocks[$k]['json'];
		if (!empty($j['Enabled'])) {
			$targets[] = (string) $j['ActionValue'];
			$keep = $keep || !empty($j['Keep']);
		}
	}
	return ['targets' => $targets, 'keep' => $keep || !$targets];
}

// sieve.js filterToString for a forward filter whose conditions are all the bounce test.
function tx_forward_body(array $j): string
{
	$tab = '    ';
	$cond = array_map(fn ($c) => 'not header :contains [' . sieve_quote($c['ValueSecond']) . '] ' . sieve_quote($c['Value']), $j['Conditions']);
	$out = count($cond) > 1 ? ['if allof(', implode(',' . TX_EOL, array_map(fn ($c) => $tab . $c, $cond)), ')'] : ['if ' . $cond[0]];
	$out[] = '{';
	$j['Keep'] && ($out[] = $tab . 'fileinto "INBOX";');
	$out[] = $tab . 'redirect ' . sieve_quote($j['ActionValue']) . ';';
	$out[] = '}';
	return implode(TX_EOL, $out);
}

function tx_forward_new(array $targets, bool $keep): array
{
	$cond = array_map(fn ($a) => ['Field' => 'Header', 'Type' => 'NotContains', 'Value' => $a, 'ValueSecond' => FAILED_HEADER], $targets);
	$out = [];
	foreach ($targets as $a) {
		$j = [
			'ID' => bin2hex(random_bytes(8)),
			'Enabled' => true,
			'Name' => RULE_NAME,
			'Conditions' => $cond,
			'ConditionsType' => 'All',
			'ActionType' => 'Forward',
			'ActionValue' => $a,
			'ActionValueSecond' => '',
			'ActionValueThird' => '',
			'ActionValueFourth' => '',
			'Keep' => $keep,
			// Stop would end the customer's filters behind it.
			'Stop' => false,
			'MarkAsRead' => false,
		];
		$out[] = ['gap' => TX_EOL, 'text' => tx_block($j, tx_forward_body($j)), 'json' => $j];
	}
	return $out;
}

// The blocks without the forward ones, $new spliced in where the first of them was, else behind the vacation, else
// first. The first block keeps the gap the script had there; sieve.js puts one line end between blocks.
function tx_forward_splice(array $blocks, array $new): array
{
	$first = $blocks[0]['gap'] ?? '';
	$ours = tx_forward_blocks($blocks);
	$at = null;
	$kept = [];
	foreach ($blocks as $k => $b) {
		if (in_array($k, $ours, true)) {
			$at ??= count($kept);
			continue;
		}
		$kept[] = $b;
	}
	if ($at === null) {
		$at = 0;
		foreach ($kept as $j => $b) {
			if (($b['json']['ActionType'] ?? '') === 'Vacation') {
				$at = $j + 1;
				break;
			}
		}
	}
	array_splice($kept, $at, 0, $new);
	foreach ($kept as $j => &$b) {
		$b['gap'] = $j === 0 ? $first : ($b['gap'] === '' ? TX_EOL : $b['gap']);
	}
	return $kept;
}

// sieve.js collects its require list while it writes the filters, the disabled ones too, in this order.
function tx_requires(array $blocks): array
{
	$req = [];
	foreach ($blocks as $b) {
		$j = $b['json'];
		if (!is_array($j)) {
			continue;
		}
		foreach ($j['Conditions'] ?? [] as $c) {
			if ((string) ($c['Value'] ?? '') === '' || (($c['Field'] ?? '') === 'Header' && (string) ($c['ValueSecond'] ?? '') === '')) {
				continue;
			}
			($c['Type'] ?? '') === 'Regex' && ($req['regex'] = 1);
			($c['Field'] ?? '') === 'Body' && ($req['body'] = 1);
		}
		$type = $j['ActionType'] ?? '';
		!empty($j['MarkAsRead']) && in_array($type, ['None', 'MoveTo', 'Forward'], true) && ($req['imap4flags'] = 1);
		if ((string) ($j['ActionValue'] ?? '') !== '') {
			$type === 'Vacation' && ($req['vacation'] = 1);
			$type === 'Reject' && ($req['reject'] = 1);
			($type === 'MoveTo' || ($type === 'Forward' && !empty($j['Keep']))) && ($req['fileinto'] = 1);
		}
	}
	return array_keys($req);
}

// The head with the require line sieve.js would write; without one a Tachyon script starts with a line end. Without
// filters there is no blank line after the marker either.
function tx_forward_write(string $head, array $blocks, string $tail): string
{
	$head = preg_replace('/^require\s+\[[^\]]*\];\r\n/', '', $head, 1);
	$req = tx_requires($blocks);
	$req && ($head = 'require ' . json_encode($req, JSON_UNESCAPED_SLASHES) . ';' . TX_EOL . $head);
	$blocks || ($head = preg_replace('/(\r\n)+\z/', TX_EOL, $head));
	return tx_join($head, $blocks, $tail);
}

function tx_forward_set(string $s, array $targets, bool $keep): string
{
	if (trim($s) === '') {
		$s = TX_EOL . implode(TX_EOL, ['# This is Tachyon sieve script.', "# Please don't change anything here.", '# RAINLOOP:SIEVE', '']) . TX_EOL;
	}
	[$head, $blocks, $tail] = tx_split($s);
	return tx_forward_write($head, tx_forward_splice($blocks, tx_forward_new($targets, $keep)), $tail);
}

function tx_forward_clear(string $s): string
{
	[$head, $blocks, $tail] = tx_split($s);
	return tx_forward_write($head, tx_forward_splice($blocks, []), $tail);
}

// ---------------------------------------------------------------- dispatch

// none: no forward rule. ours: one this file writes. changed: a "Forward" rule or filter changed beyond its targets
// and keep, or two rules of that name. Changed is still the forward in Sieve, which exim must not send a second time;
// an own script is read as none, nothing in it can be told apart.
function forward_rule(string $f, string $s): string
{
	if ($f === 'roundcube') {
		[, $rules] = rc_split($s);
		$ours = rc_forward_rules($rules);
		if (!$ours) {
			return 'none';
		}
		return count($ours) > 1 || rc_forward($rules[$ours[0]]) === null ? 'changed' : 'ours';
	}
	if ($f === 'tachyon') {
		[, $blocks] = tx_split($s);
		$ours = tx_forward_blocks($blocks);
		if (!$ours) {
			return 'none';
		}
		foreach ($ours as $k) {
			if (!tx_forward_ok($blocks[$k]['json'])) {
				return 'changed';
			}
		}
		return 'ours';
	}
	return 'none';
}

function forward_state(string $f, string $s): array
{
	return match ($f) {
		'roundcube' => rc_forward_state($s),
		'tachyon' => tx_forward_state($s),
		default => ['targets' => [], 'keep' => true],
	};
}

function state_of(string $file): array
{
	$s = $file !== '' && is_file($file) ? file_get_contents($file) : '';
	try {
		$f = raw_format($s);
		$r = forward_rule($f, $s);
		return ['format' => $f, 'rule' => $r] + ($r === 'ours' ? forward_state($f, $s) : ['targets' => [], 'keep' => true]);
	} catch (UnexpectedValueException) {
		return ['format' => 'custom', 'rule' => 'none', 'targets' => [], 'keep' => true];
	}
}

// Read back before it is handed out: same format, and exactly the forward that was asked for. A Roundcube script that
// held nothing but the forward is empty without it.
function checked(string $want, string $out, array $targets, bool $keep): string
{
	$f = raw_format($out);
	$r = forward_rule($f, $out);
	$st = forward_state($f, $out);
	if (($f !== $want && !($f === 'none' && !$targets)) || $r !== ($targets ? 'ours' : 'none')
		|| $st['targets'] !== $targets || ($targets && $st['keep'] !== $keep)) {
		fail(1, 'the rewritten script does not read back as asked, left unchanged');
	}
	return $out;
}

function usage(): never
{
	fail(1, 'usage: forward.php state FILE | set roundcube|tachyon FILE yes|no TARGET... | clear FILE');
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
	case 'set':
		[, , $want, $file, $keep] = $argv + [null, null, '', '', ''];
		$targets = array_slice($argv, 5);
		in_array($want, ['roundcube', 'tachyon'], true) && in_array($keep, ['yes', 'no'], true) && $targets || usage();
		count($targets) <= MAX_TARGETS || fail(1, 'at most ' . MAX_TARGETS . ' forward targets');
		foreach ($targets as $a) {
			preg_match('/^[^\s"\\\\@]+@[^\s"\\\\@]+$/', $a) || fail(1, "not an address: $a");
		}
		count(array_unique(array_map('strtolower', $targets))) === count($targets) || fail(1, 'a forward target is named twice');
		$s = $file !== '' && is_file($file) ? file_get_contents($file) : '';
		$f = raw_format($s);
		$f === 'none' && ($f = $want);
		($f === 'custom' || forward_rule($f, $s) === 'changed') && fail(3, 'the active Sieve script is not one the webmail manages');
		$k = $keep === 'yes';
		echo checked($f, $f === 'tachyon' ? tx_forward_set($s, $targets, $k) : rc_forward_set($s, $targets, $k), $targets, $k);
		break;
	case 'clear':
		isset($argv[2]) || usage();
		$s = $argv[2] !== '' && is_file($argv[2]) ? file_get_contents($argv[2]) : '';
		$f = raw_format($s);
		forward_rule($f, $s) === 'changed' && fail(3, 'the active Sieve script is not one the webmail manages');
		echo match ($f) {
			'roundcube' => checked($f, rc_forward_clear($s), [], true),
			'tachyon' => checked($f, tx_forward_clear($s), [], true),
			'none' => '',
			default => fail(3, 'the active Sieve script is not one the webmail manages'),
		};
		break;
	default:
		usage();
}
