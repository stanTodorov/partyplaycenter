<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

/**
 * Fill string with zeros
 *
 * @param mixed $index String to fill
 * @param integer $size Number of zeros
 * @return Zero filled string
 */
function ZeroFill($index, $size)
{
	while (strlen($index) < $size)
		$index = "0".$index;
	return $index;
}

/**
 * Validate Field from form
 *
 * @param string $field Field to check
 * @param mixed $ctrlArray Control (min.length,max.length,require,regexp) of $filed data
 * @return string Error message if found, or true otherwise
 */
function IsValidate($field, $ctrlArray = array())
{
	$field = trim($field);

	if (empty($ctrlArray) && !is_array($ctrlArray)) {
		return 'Невалидно извикване!';
	}

	$minlength = $ctrlArray[0];
	$maxlength = $ctrlArray[1];
	$require = $ctrlArray[2];
	$regexp = $ctrlArray[3];


	if (mb_strlen($field) == 0 && $require == false) {
		return true;
	}
	else if (mb_strlen($field) == 0 && $require == true) {
		return 'Полето е задължително!';
	}
	else if (mb_strlen($field) < $minlength) {
		return 'Минималната дължина е '.$minlength.' символа!';
	}
	else if (mb_strlen($field) > $maxlength) {
		return 'Максималната дължина е '.$maxlength.' символа!';
	}
	else if (!empty($regexp) && mb_strlen($field) > 0 && !preg_match($regexp, $field)) {
		return 'Невалидни символи!';
	}

	return true; // everything is fine ;)
}

/**
 * Validate forms by using rules for every fieldname
 *
 * @param array $raw Raw Input array (usualy $_GET or $_POST)
 * @param array $data Returned values of fields after validation
 * @param array $fields Array with rules for validating
 * @return array List of Errors ('field-name' => 'error')
 */
function FormValidate($raw, &$data, $fields)
{
	global $ml;

	$msg['L_FORM_MIN'] = isset($ml['L_FORM_MIN'])
		? $ml['L_FORM_MIN']
		: 'Min allowed length is %s chars!';

	$msg['L_FORM_MAX'] = isset($ml['L_FORM_MAX'])
		? $ml['L_FORM_MAX']
		: 'Max allowed length is %s chars!';

	$msg['L_FORM_REQ'] = isset($ml['L_FORM_REQ'])
		? $ml['L_FORM_REQ']
		: 'Required field!';

	$msg['L_FORM_REGEX'] = isset($ml['L_FORM_REGEX'])
		? $ml['L_FORM_REGEX']
		: 'Invalid chars!';

	$msg['L_FORM_REGEX_ALLOWED'] = isset($ml['L_FORM_REGEX_ALLOWED'])
		? $ml['L_FORM_REGEX_ALLOWED']
		: 'Allowed chars: %s!';

	$msg['L_FORM_BETWEEN'] = isset($ml['L_FORM_BETWEEN'])
		? $ml['L_FORM_BETWEEN']
		: 'Value between %s and %s!';

	$errors = array();

	$regex = array(
		'int' => '#^-?[\d]+$#',
		'float' => '#^-?(?:[\d]+|[\d]*[\.\,][\d]+)$#',
		'email' => '/^([a-zA-Z0-9_\-\.!#$%&\*\+\/=\\\?\^`{}\|~]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/'
	);

	// fill $data with all listened fields (keys) in $fileds
	foreach ($fields as $field => $value) {
		if (isset($value['default'])) {
			$data[$field] = $value['default'];
			continue;
		}
		$data[$field] = '';
	}

	// fill $data with values from $raw, if key in $raw exist in $data
	foreach($raw as $key => $val) {
		if (array_key_exists($key, $fields)) {
			if (is_array($val)) {
				$data[$key] = $val;
				continue;
			}

			$data[$key] = trim($val);
		}
	}

	// validate every value from $data
	foreach ($data as $key => $value) {
		if (count($fields[$key]) == 0) continue; // next validation

		// first check if required rule exists
		$required = isset($fields[$key]['req'])
			? $fields[$key]['req']
			: ( isset($fields[$key]['required'])
				? $fields[$key]['required']
				: false);

		if (mb_strlen($value) == 0 && !$required) {
			continue;
		} else if (mb_strlen($value) == 0 && $required) {
			$errors[$key] = $msg['L_FORM_REQ'];
			continue;
		}

		// next check rest of found rules
		foreach ($fields[$key] as $type => $checker) {
			switch($type) {
			case 'min':
			case 'minlength':
				if (mb_strlen($value) < $checker) {
					$errors[$key] = sprintf($msg['L_FORM_MIN'], $checker);
				}
				break;
			case 'max':
			case 'maxlength':
				if (mb_strlen($value) > $checker) {
					$errors[$key] = sprintf($msg['L_FORM_MAX'], $checker);
				}
				break;

			case 'regex':
			case 'regexp':
				if (preg_match($checker, $value)) {
					break;
				}

				$errors[$key] = $msg['L_FORM_REGEX'];

				if (isset($fields[$key]['err_allowed'])) {
					$errors[$key] = sprintf($msg['L_FORM_REGEX_ALLOWED'], $fields[$key]['err_allowed']);
				}

				break;
			case 'is':
				if (isset($regex[$checker]) && !preg_match($regex[$checker], $value)) {
					$errors[$key] = $msg['L_FORM_REGEX'];
				}
				break;
			case 'between':
				list($a, $b) = $checker;

				if ($value < $a || $b < $value) {
					$errors[$key] = sprintf($msg['L_FORM_BETWEEN'], $a, $b);
				}
			default:
			}

			// that's enough for this field
			if (isset($errors[$key])) break;
		}
	}

	return $errors;
}


/**
 * Upper First Letter and lower all other (Unicode)
 *
 * @param string $word Word (or Words) to UpperCase
 * @param string $sep Separator betweeen words (none by default)
 * @return string Upper String;
 */
function UpperFirstLetter($word, $sep = '')
{
	if ($sep != '') {
		$words = explode($sep, $word);
		$word = '';
		foreach ($words as $val) {
			$word .= mb_strtoupper(mb_substr(trim($val), 0, 1)) . mb_strtolower(mb_substr(trim($val), 1)) . $sep;
		}
		return trim($word);
	}
	else {
		$word = trim($word);
		return mb_strtoupper(mb_substr($word, 0, 1)) . mb_strtolower(mb_substr($word, 1));
	}
}

/**
 * Abbreviation of the name with dot, ie Anastasia -> An.
 * Cyrillic and Unicode Only
 *
 * @param string $name Name to convert
 * @return string Abbr. Name
 */
function AbbrDot($name)
{
	$output = '';
	for($x = 0, $count = mb_strlen($name); $x < $count; $x++) {
		if ($x > 0 && preg_match('/[аъоуеи]/iu', mb_substr($name, $x, 1))) {
			$output .= '.';
			break;
		}
		$output .= mb_substr($name, $x, 1);
	}

	return $output;
}

/**
 * Search for text-based http:// links and convert to <a href=""></a> links
 *
 * @param string $string Content with text links
 * @return string
 */
function ReplaceLinks($string)
{
	return preg_replace('/(http|https):\/\/[^<>\s][\w\d\.\-\/;=&\?\+]+/iu', '<a href="\0">\0</a>', $string);
}

/**
 * Format User Cash
 */
function FormatMoney($money, $sep = ' ')
{
	return number_format($money, 0, '.', $sep);

}

/**
 * trims text to a space then adds ellipses if desired (unicode version)
 *
 * @param string $input text to trim
 * @param int $length in characters to trim to
 * @param bool $ellipses if ellipses (...) are to be added
 * @param bool $strip_html if html tags are to be stripped
 * @return string
 */
function ShortText($input, $length, $ellipses = true, $strip_html = true)
{
	//strip tags, if desired
	if ($strip_html) {
		$input = strip_tags($input);
	}

	//no need to trim, already shorter than trim length
	if (mb_strlen($input, 'UTF-8') <= $length) {
		return $input;
	}


	//find last space within length
	$last_space = mb_strrpos(mb_substr($input, 0, $length, 'UTF-8'), ' ', 0, 'UTF-8');
	$trimmed_text = mb_substr($input, 0, $last_space, 'UTF-8');

	// if no space found? wrap text and cut off
	if (mb_strlen($trimmed_text, 'UTF-8') == 0 && $length > 0) {
		$input = wordwrap($input, round($length / 3), ' ', true);
		$trimmed_text = mb_substr($input, 0, $length, 'UTF-8');
	}

	//add ellipses
	if ($ellipses) {
		$trimmed_text .= '...';
	}

	return $trimmed_text;
}


/**
 * Convert BBCode to HTML tags
 *
 * @param string $message bbcode message to convert
 * @return string HTML replaced text
 */
function bbcode2html($message)
{
	$preg = array(
	  // color, size, font, align, i, u, center
		'/(?<!\\\\)\[color(?::\w+)?=(.*?)\](.*?)\[\/color(?::\w+)?\]/si'
			=> "<span style=\"color:\\1\">\\2</span>",
		'/(?<!\\\\)\[size(?::\w+)?=(.*?)\](.*?)\[\/size(?::\w+)?\]/si'
			=> "<span style=\"font-size:\\1\">\\2</span>",
		'/(?<!\\\\)\[font(?::\w+)?=(.*?)\](.*?)\[\/font(?::\w+)?\]/si'
			=> "<span style=\"font-family:\\1\">\\2</span>",
		'/(?<!\\\\)\[align(?::\w+)?=(.*?)\](.*?)\[\/align(?::\w+)?\]/si'
			=> "<div style=\"text-align:\\1\">\\2</div>",
		'/(?<!\\\\)\[b(?::\w+)?\](.*?)\[\/b(?::\w+)?\]/si'
			=> "<span style=\"font-weight:bold\">\\1</span>",
		'/(?<!\\\\)\[i(?::\w+)?\](.*?)\[\/i(?::\w+)?\]/si'
			=> "<span style=\"font-style:italic\">\\1</span>",
		'/(?<!\\\\)\[u(?::\w+)?\](.*?)\[\/u(?::\w+)?\]/si'
			=> "<span style=\"text-decoration:underline\">\\1</span>",
		'/(?<!\\\\)\[center(?::\w+)?\](.*?)\[\/center(?::\w+)?\]/si'
			=> "<div style=\"text-align:center\">\\1</div>",

		// email
		'/(?<!\\\\)\[email(?::\w+)?\](.*?)\[\/email(?::\w+)?\]/si'
			=> "<a href=\"mailto:\\1\" class=\"bb-email\">\\1</a>",
		'/(?<!\\\\)\[email(?::\w+)?=(.*?)\](.*?)\[\/email(?::\w+)?\]/si'
			=> "<a href=\"mailto:\\1\" class=\"bb-email\">\\2</a>",

		// url
		'/(?<!\\\\)\[url(?::\w+)?\]www\.(.*?)\[\/url(?::\w+)?\]/si'
			=> "<a href=\"http://www.\\1\" target=\"_blank\" class=\"bb-url\">\\1</a>",
		'/(?<!\\\\)\[url(?::\w+)?\](.*?)\[\/url(?::\w+)?\]/si'
			=> "<a href=\"\\1\" target=\"_blank\" class=\"bb-url\">\\1</a>",
		'/(?<!\\\\)\[url(?::\w+)?=(.*?)?\](.*?)\[\/url(?::\w+)?\]/si'
			=> "<a href=\"\\1\" target=\"_blank\" class=\"bb-url\">\\2</a>",
		// img
		'/(?<!\\\\)\[img(?::\w+)?\](.*?)\[\/img(?::\w+)?\]/si'
			=> "<img src=\"\\1\" alt=\"\\1\" class=\"bb-image\" />",
		'/(?<!\\\\)\[img(?::\w+)?=(.*?)x(.*?)\](.*?)\[\/img(?::\w+)?\]/si'
			=> "<img width=\"\\1\" height=\"\\2\" src=\"\\3\" alt=\"\\3\" class=\"bb-image\" />",

		// quote, code
		'@\[quote\](.*?)\[/quote\]@si'
			=> "<fieldset class=\"quoteStyle\"><legend>Цитат</legend>\\1</fieldset>",
		'@\[code\](.*?)\[/code\]@si'
			=> "<code class=\"codeStyle\">\\1</code>",

		//line breaks
		'/\n/'
			=> "<br>",
		// escaped tags like \[b], \[color], \[url], ...
		'/\\\\(\[\/?\w+(?::\w+)*\])/'
			=> "\\1"

	);
	$message = preg_replace(array_keys($preg), array_values($preg), strip_tags($message));
	return $message;
}



/**
 * Remove content between bbcode [quote] and [/quote]
 *
 * @param string $text Text contain quote bbcode tags
 * @return string clean text
 */
function RemoveNestedQuotes($text) {
	$text = preg_replace('@\[quote\](.*?)\[/quote\]@si', "", strip_tags($text));
	return $text;
}
