<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

/**
 * Return translate (localized) date
 *
 * @param string $format PHP's Date format
 * @param integer $time timestamp
 * @return string Date format
 */
function LocaleDate($format, $time = false)
{
	global $ml;
	$date = '';

	// names
	$_days = (isset($ml['L_DAYS']) && count($ml['L_DAYS']) == 7)
		? $ml['L_DAYS']
		: array();
	$_months = (isset($ml['L_MONTHS']) && count($ml['L_MONTHS']) == 12)
		? $ml['L_MONTHS']
		: array();
	$_days_s = (isset($ml['L_DAYS_ABBR']) && count($ml['L_DAYS_ABBR']) == 7)
		? $ml['L_DAYS_ABBR']
		: array();
	$_months_s = (isset($ml['L_MONTHS_ABBR']) && count($ml['L_MONTHS_ABBR']) == 12)
		? $ml['L_MONTHS_ABBR']
		: array();

	if ($time == false || !is_numeric($time)) {
		$time = time();
	}

	for ($x = 0; $x <= mb_strlen($format); $x++) {
		$char = mb_substr($format, $x, 1);

		switch($char) {
		case "\\": // escaping
			if (($x + 1) <= mb_strlen($format)) {
				$x++;
				$date[] = mb_substr($format, $x, 1);
			}
			break;
		case 'l': // day of week (fullname)
			if (count($_days)) {
				$date[] = $_days[date("N", $time) - 1];
				break;
			}

			$date[] = date($char, $time);
			break;
		case 'D': // day of week (short name)
			if (count($_days_s)) {
				$date[] = $_days_s[date("N", $time) - 1];
				break;
			}

			$date[] = date($char, $time);
			break;

		case 'F': // months fullname
			if (count($_months)) {
				$date[] = $_months[date("n", $time) - 1];
				break;
			}

			$date[] = date($char, $time);
			break;
		case 'M': // months short names
			if ($_months_s) {
				$date[] = $_months_s[date("n", $time) - 1];
				break;
			}

			$date[] = date($char, $time);
			break;
		case 'r': // RFC 2822
			if (count($_months_s) && count($_days_s)) {
				$date[] = $_days_s[date("N", $time) - 1];
				$date[] = ", ".date("d")." ";
				$date[] = $_months_s[date("n", $time) - 1]." ";
				$date[] = date("Y H:i:s O");
			}
			break;
		default:
			if (mb_strlen($fmt = date($char, $time)) > 0) {
				$date[] = $fmt;
				break;
			}
			$date[] = $char;

		}

	}
	return implode("", $date);
}


/**
 * Get Elapsed Time between two dates, in "human" kind
 *
 * @param integer $start Start Date of period in UNIX seconds
 * @param integer $end End Date of period UNIX seconds, default current time
 * @param bool $timeonly True for show time-only, w/o "before/after" prefix
 * @param string $direction s=>($start-$end), e=>($end-$start), a=>auto max-min
 * @return string Elapsed Time string
 */
function ShortDate($start, $end = -1, $timeonly = false, $direction = 'a')
{
	$period = array('секунда', 'минута', 'час', 'ден', 'седмица', 'месец', 'година', 'десетилетие');
	$periods = array('секунди', 'минути', 'часа', 'дни', 'седмици', 'месеца', 'години', 'десетилетия');
	$lengths = array(60, 60, 24, 7, 4.35, 12, 10);

	if (empty($start)) return "n/a";
	if ($end == -1) $end = time();

	switch($direction)
	{
		case "s":
			$diff = intval($start) - $end - 3600;
			if ($diff < 0) return "n/a";
		break;
		case "e":
			$diff = $end - intval($start);
			if ($diff < 0) return "n/a";
		break;
		case "a":
		default:
			if(($start > $end)) $diff = intval($start) - $end - 3600;
			else $diff = $end - intval($start);
	}

	// $diff2 = 0;
	for($x = 0; $diff >= $lengths[$x] && $x < count($lengths) - 1; $x++) {
			$diff /= $lengths[$x];
			// $diff2 = ($diff - floor($diff)) * $lengths[$x];
	}

	$diff = floor($diff);
	// $diff2 = round($diff2);

	$diff .= ' '.($diff == 1 ? $period[$x] : $periods[$x]);
	// if ($x > 0) $diff2 .= ' '.($diff2 == 1 ? $period[$x-1] : $periods[$x-1]);

	//return (empty($timeonly) ? (' '.(($diff2 > 0) ? $diff.' и '.$diff2 : $diff)) : (($diff2 > 0) ? $diff.' и '.$diff2 : $diff));
	return $diff;
}


/**
 * Get Simple date from elapsed time
 *
 * @param integer $start date
 * @param integer $end end date
 */
function SimpleDate($start, $end = -1)
{
	$months = array('януари', 'февруари', 'март', 'април', 'май', 'юни', 'юли', 'август', 'септември', 'октомври', 'ноември', 'декември');

	if (empty($start)) return "n/a";
	if ($end == -1) $end = time();


	if (!preg_match('/^[\d]+$/', $start) && mb_strlen($start) > 0) {
		$start = strtotime($start);
	}
	else {
		$start = intval($start);
	}

	$hour = date("H:i", $start);
	$day = date("d", $start);
	$month = date("n", $start);
	$year = date("Y", $start);

	$diff = array(
		'day' => date("d", $end) - date("d", $start),
		'month' => date("m", $end) - date("m", $start),
		'year' => date("y", $end) - date("y", $start)
	);

	if ($diff['year'] > 0) {
		$out = $day.' '.$months[$month - 1].', '.$year;
	}
	else if ($diff['day'] == 1 && $diff['month'] == 0) {
		$out = 'вчера';
	}
	else if ($diff['day'] < 1 && $diff['month'] == 0) {
		$out = $hour;
	}
	else {
		$out = $day.' '.$months[$month - 1];
	}

	return $out;
}

/**
 * Get Diff between $endtime and time()
 *
 * @param mixed $endtime end time
 * @return string Hour difference
 */
function HoursDiff($endtime)
{
	$time = time();

	if (intval($endtime) == 0 && mb_strlen($endtime) > 0) {
		$endtime = strtotime($endtime);
	}
	else if (mb_strlen($endtime) == 0) {
		return '00:00:00';
	}

	if ($time >= $endtime) {
		return '00:00:00';
	}

	$diff = $endtime - $time;
	$diff_hour = intval($diff / 3600.0);
	$diff_min = intval(($diff - ($diff_hour * 3600)) / 60.0);
	$diff_sec = intval($diff - (($diff_hour * 3600) + ($diff_min * 60)));

	return sprintf("%02s:%02s:%02s", $diff_hour, $diff_min, $diff_sec);
}


/**
 * Convert UNIX seconds to Human
 */
function Seconds2Duration($secs)
{
	$names = array(
		"w" => array( 0 => 'седмица', 1 => 'седмици'),
		"d" => array( 0 => 'ден', 1 => 'дена'),
		"h" => array( 0 => 'час', 1 => 'часа'),
		"m" => array( 0 => 'месец', 1 => 'месеца'),
		"s" => array( 0 => 'секунда', 1 => 'секунди')
	);

        $units = array(
                "w" => 7*24*3600,
                "d" =>   24*3600,
                "h" =>      3600,
                "m" =>        60,
                "s" =>         1,
        );

        if ( $secs <= 0 ) return "0 секунди";

        $s = "";

        foreach ( $units as $name => $divisor ) {
                if ( $quot = intval($secs / $divisor) ) {
                        $s .= $quot.' ';
			if (abs($quot) == 1) {
				$s .= $names[$name][0];
			}
			else {
				$s .= $names[$name][1];
			}
                        $s .= ", ";
                        $secs -= $quot * $divisor;
                }
        }

        return substr($s, 0, -2);
}


function Duration2mmss ($duration) {
	return sprintf("%d:%02d", ($duration / 60), $duration % 60);
}

/**
 * Check if date $current is between $start and $end (all unix timestamp!)
 *
 * @param integer $current current time
 * @param integer $start start time
 * @param integer $end end time
 * @return bool True if $current is in range between $start and $end, false otherwise
 *
 */
function DateRange($current, $start, $end)
{
	return ($start < $current && $end > time());
}


/**
 * Parse common date/time fields and return unix timestamp equivalent
 *
 * @param string $date Human-style date time
 * @return integer UNIX timestamp if success, or false
 */
function ParseInputDate($date)
{

	// m - match regexp
	// f - format date
	// example based on: Tue, 09 Feb 2010 11:10:29
	$valid = array(
		array(
			'm' => '#^([\d]{4})[/\-]([\d]{2})[/\-]([\d]{2})[^\d]*([\d]{2}):([\d]{2})$#',
			'f' => 'YmdHi' // 2010/02/09 11:10 or 2010-02-09 11:10
		),
		array(
			'm' => '#^([\d]{4})[/\-]([\d]{2})[/\-]([\d]{1,2})[^\d]*([\d]{2}):([\d]{2}):([\d]{2})$#',
			'f' => 'YmdHis' // 2010/02/09 11:10:29 or 2010-02-09 11:10:29
		),
		array(
			'm' => '#^([\d]{2})\.([\d]{2})\.([\d]{4})[^\d]*([\d]{2}):([\d]{2}):([\d]{2})$#',
			'f' => 'dmYHis' // 09.02.2010 11:10:29
		),
		array(
			'm' => '#^([\d]{2})\.([\d]{2})\.([\d]{4})[^\d]*([\d]{2}):([\d]{2})$#',
			'f' => 'dmYHi' // 09.02.2010 11:10
		),
		array(
			'm' => '#^([\d]{2})[\.\/\-]([\d]{2})[\.\/\-]([\d]{4})$#',
			'f' => 'dmY' // 09.02.2010 or 09-02-2010 or 09/02/2010
		),
		array(
			'm' => '#^([\d]{4})[\.\/\-]([\d]{2})[\.\/\-]([\d]{2})$#',
			'f' => 'Ymd' // 2010.02.09 or 2010/02/09 (USA) or 2010-02-09 (ISO 8601)
		),
		array(
			'm' => '#^([\d]{2})[/\-]([\d]{2})[/\-]([\d]{2})$#',
			'f' => 'ymd' // 10/02/09 or 10-02-09 ...avoid it at any cost!
		),
		array(
			'm' => '#^([\d]{2})\.([\d]{2})\.([\d]{2})$#',
			'f' => 'dmy' // 09.02.10 ...avoid it at any cost!
		),
		array(
			'm' => '#^([\d]{2}):([\d]{2}):([\d]{2})$#',
			'f' => 'His' // 11:10:29, 24-hours
		),
		array(
			'm' => '#^([\d]{2}):([\d]{2})$#',
			'f' => 'Hi' // 11:10, 24-hours
		)
	);

	// check valid input date/time
	$max = count($valid);
	for ($found = 0; $found < $max; $found++) {
		if (preg_match($valid[$found]['m'], trim($date), $out)) {
			break;
		}
	}

	if (!count($out) || count($out) != (mb_strlen($valid[$found]['f']) + 1)){
		return false;
	}

	$dt = array(
		'd' => 0,
		'y' => 0,
		'm' => 0,
		'h' => 0,
		'i' => 0, // minute
		's' => 0
	);

	// parse format
	$max = mb_strlen($valid[$found]['f']);
	for ($x = 1; $x <= $max; $x++) {
		$char = mb_substr($valid[$found]['f'], $x - 1, 1);
		switch($char) {
		case 'd': $dt['d'] = intval($out[$x], 10); break;
		case 'm': $dt['m'] = intval($out[$x], 10); break;
		case 'Y': $dt['y'] = intval($out[$x], 10); break;
		case 'y': // short year (i.e. 10, instead of 2010)
			$y = substr(date("Y"), 0, 2);
			$dt['y'] = intval($y.$out[$x], 10); break;
		case 'H': $dt['h'] = intval($out[$x], 10); break;
		case 'i': $dt['i'] = intval($out[$x], 10); break;
		case 's': $dt['s'] = intval($out[$x], 10); break;
		}
	}

	// no date? today...
	if ($dt['y'] == 0 && $dt['m'] == 0 && $dt['d'] == 0) {
		$dt['y'] = date("Y");
		$dt['m'] = date("m");
		$dt['d'] = date("d");
	}

	// valid date? or Hour?
	if (!checkdate($dt['m'], $dt['d'], $dt['y'])) return false;
	if ($dt['h'] > 23 || $dt['i'] > 59 || $dt['s'] > 59) return false;

	// return UNIX Timestamp
	return mktime($dt['h'], $dt['i'], $dt['s'], $dt['m'], $dt['d'], $dt['y']);
}
