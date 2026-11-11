<?php

declare(strict_types=1);

// The record parser behind parse_object_kv_list (include/main.sh): it validates a record and prints it as shell
// assignments, which the caller evals.
//
//   row STRING  -> KEY='value' KEY2='value' for one record
//   rows        -> one ":"-prefixed assignment line per stdin line, all or nothing; on a bad line only its
//                  number is printed, so the caller can name the record the way the single form does
//
// Supported syntax:
//   KEY1='value1' KEY2='value with spaces' KEY3=''
//   KEY=value
//   KEY=value\ with\ spaces
//   KEY=ab\ c'de f'ghi
//   KEY=
//   KEY= KEY2=value
//   KEY='abc'def
//   KEY=abc''
//   KEY=\'
//   KEY=\\
//
// Key names must match [a-zA-Z][a-zA-Z0-9_]*. Inside single quotes every character is literal except the
// closing quote; outside, a backslash escapes the next character.

final class RecordError extends Exception
{
}

function fail(string $message): never
{
	throw new RecordError($message);
}

/**
 * @param list<string> $protected
 * @return array<string, string>
 */
function parse_record(string $unparsed, array $protected): array
{
	$result = [];

	while ($unparsed !== '') {
		$unparsed = ltrim($unparsed);
		if ($unparsed === '') {
			break;
		}

		if (preg_match('/^([a-zA-Z][a-zA-Z0-9_]*)=/', $unparsed, $m) !== 1) {
			fail('Invalid key value format. Could not extract key name from: ' . $unparsed);
		}

		$key_name = $m[1];

		// Same protection as source_conf: the form of the key was checked, not the NAME, so a record could
		// bind PATH or BIN in the calling shell. Our own records never carry these.
		if ($protected === []) {
			fail('Protected name list is empty - refusing to parse.');
		}
		if (in_array($key_name, $protected, true)) {
			fail('Refused key name: ' . $key_name . ' is a protected shell or path name.');
		}

		$unparsed = substr($unparsed, strlen($m[0]));

		$key_value = '';
		$is_in_quote = false;

		while (true) {
			if ($is_in_quote) {
				$pos = strpos($unparsed, "'");
				if ($pos === false) {
					fail('Invalid key value format. No closing quote for key: ' . $key_name . ' in: ' . $unparsed);
				}

				$key_value .= substr($unparsed, 0, $pos);
				$unparsed = substr($unparsed, $pos + 1);
				$is_in_quote = false;

				if ($unparsed === '') {
					break;
				}
				continue;
			}

			if (preg_match('/\s|\'|\\\\/u', $unparsed, $m, PREG_OFFSET_CAPTURE) !== 1) {
				$key_value .= $unparsed;
				$unparsed = '';
				break;
			}

			$matched_char = $m[0][0];
			$pos = $m[0][1];

			$key_value .= substr($unparsed, 0, $pos);
			$unparsed = substr($unparsed, $pos + strlen($matched_char));

			if ($matched_char === "'") {
				if ($unparsed === '') {
					fail('Invalid key value format. No closing quote for key: ' . $key_name);
				}
				$is_in_quote = true;
				continue;
			}
			if ($matched_char === '\\') {
				if ($unparsed === '') {
					fail('Invalid key value format. Escape character cannot be the last character in value for key: ' . $key_name . ' in: ' . $unparsed);
				}

				$next_char = mb_substr($unparsed, 0, 1, 'UTF-8');
				$key_value .= $next_char;
				$unparsed = substr($unparsed, strlen($next_char));
				continue;
			}

			// Whitespace ends the pair.
			$unparsed = ltrim($unparsed);
			break;
		}
		if (array_key_exists($key_name, $result)) {
			$msg = 'Warning: Duplicate key name: ' . $key_name . '. ';
			if ($result[$key_name] === $key_value) {
				$msg .= 'value is identical.';
			} else {
				$msg .= var_export([
					'old_value' => $result[$key_name],
					'new_value' => $key_value,
				], true);
			}
			fwrite(STDERR, $msg . PHP_EOL);
		}
		$result[$key_name] = $key_value;
	}

	return $result;
}

/** @param array<string, string> $result */
function assignments(array $result): string
{
	$out = [];
	foreach ($result as $k => $v) {
		$out[] = $k . "='" . strtr($v, ["'" => "'\\''"]) . "'";
	}
	return implode(' ', $out);
}

$protected = preg_split('/\s+/', trim(getenv('SOURCE_CONF_PROTECTED') ?: ''), -1, PREG_SPLIT_NO_EMPTY);
$mode = $argv[1] ?? '';

if ($mode === 'row' && $argc === 3) {
	try {
		echo assignments(parse_record($argv[2], $protected));
	} catch (RecordError $e) {
		fwrite(STDERR, $e->getMessage() . PHP_EOL);
		exit(2);
	}
	exit(0);
}

if ($mode === 'rows' && $argc === 2) {
	$lines = explode("\n", (string) stream_get_contents(STDIN));
	// The caller's here-string ends in a newline; it is no row.
	if (end($lines) === '') {
		array_pop($lines);
	}
	$out = [];
	foreach ($lines as $n => $line) {
		try {
			$out[] = ':' . assignments(parse_record($line, $protected));
		} catch (RecordError $e) {
			fwrite(STDERR, $e->getMessage() . PHP_EOL);
			echo $n + 1;
			exit(2);
		}
	}
	echo implode("\n", $out), "\n";
	exit(0);
}

fwrite(STDERR, 'usage: object-parser.php row STRING | rows < RECORDS' . PHP_EOL);
exit(2);
