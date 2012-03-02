<?php
/**
 * Get Website root location (URL and absolute paths) on server
 *
 * <pre>
 * STEPS:
 *  Place this file to ROOT directory of the web site;
 *  Include in .php files that needs path/url of site;
 *  Use defined constants;
 *
 * DEFINED CONSTANTS:
 *  BASE_URL - current URL of page (with relative path); slash ended
 *  BASE_URL_HTTP - current URL of page with http protocol; slash ended
 *  BASE_URL_HTTPS - current URL of page with https protocol; slash ended
 *  BASE_PATH - absolute path of this file, slash ended
 *  IS_HTTPS - true if https, otherwise - false (http)
 *  COOKIE_DOMAIN - domain (localhost => null)
 *  COOKIE_PATH - subpath for cookies
 *
 * KNOW PROBLEMS:
 *  Non-latin paths on Windows host (apache) not working;
 *
 * CHANGES:
 *  2011-08-26: Remove port for cookie domain
 *  2011-05-05: Fix subdirs
 *  2011-04-29: Fix /home/~user/ and /var/www/other/site/path bugs
 * </pre>
 *
 * @author Mikhail Kyosev (mikhail.kyosev@gmail.com)
 * @version 1.4
 * @license MIT (http://www.opensource.org/licenses/mit-license.php)
 */

/*
**
*/
$base_path = str_replace("\\", '/', dirname(__FILE__)).'/';
$base_path = str_replace("//", '/', $base_path);

// In cronjob/cli mode, there is no web server
if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['DOCUMENT_ROOT'])) {

	// cli/cgi-mode?
	define('BASE_PATH', $base_path);

	// set default values (avoid warnings, but no stupid errors!)
	define('BASE_URL', 'http://localhost/');
	define('BASE_URL_HTTPS', 'http://localhost/');
	define('BASE_URL_HTTP', 'http://localhost/');
	define('IS_HTTPS', 'false');
	define('COOKIE_DOMAIN', '');
	define('COOKIE_PATH', '/');

	chdir(BASE_PATH);
}
else {

	// https or simple http
	$is_https = false;
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $is_https = true;

	$protocol = $is_https == true ? 'https://' : 'http://';
	$hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

	$scriptname = dirname($_SERVER['SCRIPT_NAME']);

	// if server htdocs path != url path (mod_userdir or different subdirectory)
	if (stristr($scriptname, $_SERVER['DOCUMENT_ROOT']) === false
	    || stristr($base_path, $_SERVER['DOCUMENT_ROOT']) === false)
	{
		$base_url = '';
		$base_paths = array_values(array_filter(explode('/', $base_path)));

		// get last subdir
		$last_path = $base_paths[count($base_paths)-1];

		// get pach to this script
		$scriptname = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
		$scriptname = array_values(array_filter(explode('/', $scriptname)));

		$is_sub_dir = false;
		if (in_array($last_path, $scriptname)) {
			foreach($scriptname as $key => $val) {
				$base_url .= '/'.$val;

				if ($last_path == $val) {
					$is_sub_dir = true;
					$base_url .= '/'; // end slash
					break;
				}
			}
		}

		// maybe this file location is /~USER/ ?
		if (!$is_sub_dir && count($scriptname) > 0) {
			$base_url = '/'.$scriptname[0].'/';
		}
		else if (count($scriptname) == 0) {
			$base_url = '/';
		}

		$base_url = str_replace(array('//', '\\'), '/', $base_url);

		unset($last_path, $base_paths, $is_sub_dir);
	}

	else {
		// in case that path is \ or /
		if (mb_strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/')) > 1) {
			// absolute path => relative web path
			$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
			$base_url = str_replace($doc_root, '', $base_path);
			unset($doc_root);
		}
		else {
			$base_url = $base_path;
		}
	}

	$domain = (mb_strtolower($hostname) === 'localhost') ? "" : $hostname;

	// remove port number from domain
	$cookie_domain = explode(":", $domain);
	$cookie_domain = $cookie_domain[0];

	define('IS_HTTPS', $is_https);
	define('BASE_PATH', $base_path);
	define('BASE_URL', $protocol.$hostname.$base_url);
	define('BASE_URL_HTTPS', 'https://'.$hostname.$base_url);
	define('BASE_URL_HTTP', 'http://'.$hostname.$base_url);
	define('COOKIE_DOMAIN', $cookie_domain);
	define('COOKIE_PATH', $base_url);

	unset($document_root, $protocol, $hostname, $base_url, $is_https);
	unset($base_path, $domain, $scriptname, $cookie_domain);
}



//debug:
//	echo '<hr /><pre>Path: '.BASE_PATH,'<br />URL:  '.BASE_URL.'</pre><hr />';
