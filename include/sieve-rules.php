<?php

// What vacation.php and forward.php share: a tokenizer for Sieve, and the two webmail formats split into their rules
// (Roundcube's "# rule:[...]" chunks, Tachyon's filter blocks with their base64 JSON header) and joined again.

const TX_EOL = "\r\n";

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

// Roundcube writes CRLF, and so does a new script; one that came with LF keeps it.
function rc_eol(string $s): string
{
	return trim($s) === '' || str_contains($s, "\r\n") ? "\r\n" : "\n";
}

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

function tx_join(string $head, array $blocks, string $tail): string
{
	$out = $head;
	foreach ($blocks as $b) {
		$out .= $b['gap'] . $b['text'];
	}
	return $out . $tail;
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
