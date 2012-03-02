<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

/**
 * Simple registry subsystem without using global variables
 *
 * @param string $name Variable name or config option
 * @param mixed $value Associate value to given config option (not required)
 * @return mixed Value of config option or null if variable missing
 */
function CFG($name, $value = null)
{
        static $cfg = array();

	if (is_array($name) && count($name) > 0) {
		foreach ($name as $var => $value) {
			$cfg[$var] = $value;
		}
		return;
	}

        if ($value !== null)
                return ($cfg[$name] = $value);

        if (!isset($cfg[$name]))
                return null;

        return $cfg[$name];
}

/**
 * Generate Salt for encrypted passwords
 *
 * @param integer $size Length of generated string
 * @return string Password salt
 */
function GenSalt($size = 10)
{
	$possible = "~!@#$%^&*()_+|`-=\[]{};:',.<>/?123456789abcdfghjkmnpqrstvwxyzACDEFGHJKLMNPQRSTVWXYZ";
	$code = '';

	for ($i = 0; $i < $size; $i++) {
		$code .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
	}
	return $code;
}


/**
 * Random generated password (forgot passwords...)
 *
 * @param integer $size Length of generated string
 * @return string Password salt
 */
function GenPassword($size = 8)
{
	$possible = "123456789abcdfghjkmnpqrstvwxyzACDEFGHJKLMNPQRSTVWXYZ";
	$code = '';

	for ($i = 0; $i < $size; $i++) {
		$code .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
	}
	return $code;
}


/**
 * Custom algorithm for salting passwords
 *
 * @param string $password Password to salting
 * @param string $salt Salt string
 * @param string $constsalt Salt for specific web site (constant)
 * @return string MD5 Crypted password with salt
 */
function SaltPassword($password, $salt, $constsalt = '')
{
	$password = SlashString($password);
	return md5($salt.$constsalt.$password.$salt);
}

/**
 * Autogenerate range of copyright year
 *
 * @param mixed $yearFirst Start Year (as string or integer)
 * @param mixed $separator Symbol between years
 * @return string Years range.
 */
function CopyRight($yearFirst, $separator = ' - ')
{
	$yearFirst = abs(intval($yearFirst, 10));
	$yearCurrent = abs(intval(date("Y"), 10));

	if ($yearFirst > 0 && $yearFirst > $yearCurrent) {
		return $yearCurrent . $separator . $yearFirst;
	}
	else if ($yearFirst === $yearCurrent || $yearFirst === 0) {
		return $yearCurrent;
	}

	return $yearFirst . $separator . $yearCurrent;
}


/**
 * Return non-escaping string, depends on "Magic Quotes" parameter of PHP configuration
 *
 * @param string $string String to be not escaped
 * @param bool $scaping True for escaping, normal operation otherwise
 * @return string Escaped string
 */
function SlashString($string, $escaping = false)
{
	if ($escaping == true) {
		if (get_magic_quotes_gpc()) return $string;
		else return addslashes($string);
	} else {
		if (get_magic_quotes_gpc()) return stripslashes($string);
		else return $string;
	}
}


/**
 * Print Variables. Show Variable content in <pre> ... </pre> tags via print_r
 * Show only if DEBUG Constant set to true!
 *
 * @param mixed $var Variable to show
 * @param bool $error_log If true, send output via error_log()
 */
function P($var, $error_log = false)
{
	if ($error_log === true) {
		error_log(print_r($var, true));
		return;
	}

	echo "<pre>\n";
	print_r($var);
	echo "\n</pre>";
}

/**
 * Generate unique token string for forms
 *
 * @return string sha-1 encoded random string
 */
function GenToken()
{
	return sha1(uniqid(mt_rand(), true));
}

/**
 * Generate hash for activation link after registration
 *
 * @param string $password Hash password of user
 * @return string cutted password
 */
function GenActHash($password)
{
	global $cfg;

	$size = $cfg['ACTIVATE_HASH_SIZE'];
	$offset = mb_strlen($password) - $size;

	return substr($password, $offset, $size);
}

/**
 * Show CronJob executable output log
 *
 * @param $msg string Message to output
 * @param $stop_exec bool True if script end execution
 */
function CronLog($msg, $stop_exec = false)
{
	$time = "[".date("Y-m-d H:i:s")."]\t";

	if ($stop_exec === true) {
		die($time.$msg."\n");
	}
	else {
		echo $time.$msg."\n";
	}
}

/**
 * Check if redirected link is CSRF attack
 *
 * @return bool True if CSRF attack exist, false otherwise
 */
function IsCSRF()
{
	$token = isset($_SESSION['token']) ? $_SESSION['token'] : '';
	$request = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';

	if ($token === $request) {
		return false;
	}

	return true;
}


/**
 * Fixing damaged super global arrays if Magic Quotes is ON
 *
 */
function FixMagicQuotes()
{
	if (!get_magic_quotes_gpc()) {
		return;
	}

	$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
	foreach ($process as $key => $val) {
		foreach ($val as $k => $v) {
			unset($process[$key][$k]);

			if (is_array($v)) {
				$process[$key][stripslashes($k)] = $v;
				$process[] = &$process[$key][stripslashes($k)];
				continue;
			}

			$process[$key][stripslashes($k)] = stripslashes($v);
		}
	}
}


/**
 * Simple translation tool like gettext
 *
 * @param string $original Original string AS IS
 * @param string $translation Translation of original message (not required)
 * @return string Return Translated string if found, or original string otherhwise
 */
function L($original, $translation = null)
{
	static $locals = array();

	if ($translation !== null)
		return ($locals[$original] = $translation);

	if (!isset($locals[$original]))
		return $original;

	return $locals[$original];
}
