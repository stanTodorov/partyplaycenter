<?php
// bootstrap code

if (!defined('PROGRAM') || PROGRAM !== 1) exit;

define('DS', '/');
define('APP_NAME', 'PartyPlayCenter');
define('APP_VERSION', '0.01');
define('CONFIG', BASE_PATH.'config'.DS);
define('LIBS', BASE_PATH.'libraries'.DS);

header('Cache-control: private');
header('Content-type: text/html; charset=utf-8');

if (!defined('SITE')) {
	define('SITE', '');
}

// set-up UTF8 codepage for all mb_* functions
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

// setup umask for files
umask(0022);

// session settings
@ini_set('session.hash_function', 1);
@ini_set('session.hash_bits_per_character', 5);
session_name(APP_NAME);
session_set_cookie_params(0, COOKIE_PATH, COOKIE_DOMAIN);
session_start();

// required libraries and other important files
require_once(CONFIG.'database.php');
require_once(CONFIG.'settings.php');
require_once(CONFIG.'messages.php');
require_once(CONFIG.'tables.php');
require_once(LIBS.'Core.libs.php');
require_once(LIBS.'Date.libs.php');
require_once(LIBS.'MySQL.libs.php');
require_once(LIBS.'Login.libs.php');
require_once(LIBS.'String.libs.php');
require_once(LIBS.'Application.libs.php');
require_once(LIBS.'MySQL.class.php');
require_once(LIBS.'Paging.class.php');
require_once(LIBS.'ImageResize.class.php');
require_once(LIBS.'Smarty'.DS.'Smarty.class.php');
require_once(LIBS.'phpmailer'.DS.'class.phpmailer.php');
require_once(LIBS.'securimage'.DS.'securimage.php');

// database
$db = MYSQL::getInstance($DB_CONN);
if (!$db->getStatus()) die('Error db connection!');
unset($DB_CONN);

// Load all configurations
if ($db->query("SELECT `name`, `value`, `type` FROM `".TABLE_SETTINGS."`")) {
	while ($row = $db->getAssoc()) {
		switch($row['type']) {
		case 'boolean':
			if ($row['value'] === 'true') {
				$row['value'] = true;
				break;
			}
			$row['value'] = false;
			break;
		case 'integer':
			$row['value'] = intval($row['value']);
			break;
		default:
			$row['value'] = $row['value'];
		}

		$cfg[$row['name']] = $row['value'];
	}
	unset($row);
}

CFG($cfg);
unset($cfg);

// "MAGIC" quotes fix
FixMagicQuotes();

// Skin images/scripts/css/templates
$tmpl = array();
$tmpl['path'] = BASE_PATH.CFG('smarty.templates').DS.SITE.DS;
$tmpl['compile'] = BASE_PATH.CFG('smarty.compile').DS.SITE.DS;
$tmpl['url'] = BASE_URL.CFG('smarty.templates').DS.SITE.DS;
$tmpl['common'] = BASE_URL.CFG('smarty.templates').DS;
$tmpl['img'] = $tmpl['url'].'images'.DS;
$tmpl['css'] = $tmpl['url'].'styles'.DS;
$tmpl['js'] = $tmpl['url'].'scripts'.DS;
$tmpl['common.img'] = $tmpl['common'].'common'.DS.'images'.DS;
$tmpl['common.css'] = $tmpl['common'].'common'.DS.'styles'.DS;
$tmpl['common.js'] = $tmpl['common'].'common'.DS.'scripts'.DS;

if (!file_exists($tmpl['compile'])) {
	@mkdir($tmpl['compile'], 0777, true);
}

/**
 * set-up template system - Smarty
 */
$skin = new Smarty();
$skin->left_delimiter  = '<!--{';
$skin->right_delimiter = '}-->';
$skin->error_reporting = (E_ALL & ~E_NOTICE);
$skin->template_dir    = $tmpl['path'];
$skin->compile_dir     = $tmpl['compile'];

$skin->assign('URL', BASE_URL);
// images/styles/scripts for admin or client parts of skin
$skin->assign('SKIN', $tmpl['url']);
$skin->assign('IMG', $tmpl['img']);
$skin->assign('CSS', $tmpl['css']);
$skin->assign('JS', $tmpl['js']);
// common for all skin
$skin->assign('IMG_COMMON', $tmpl['common.img']);
$skin->assign('CSS_COMMON', $tmpl['common.css']);
$skin->assign('JS_COMMON', $tmpl['common.js']);

unset($tmpl); // no need enymore

// global commons settings in all templates
$skin->assign('COPYRIGHT', CopyRight(CFG('copyright')));
$skin->assign('LANG', CFG('language'));
$skin->assign('LAST_UPDATE', CFG('lastupdate'));
$skin->assign('MAX_UPLOAD_COUNT', CFG('upload.max.count'));

// is debug mode on?
if (CFG('debug') !== true) {
	error_reporting(0);
	ini_set('display_errors', 'Off');
	$skin->error_reporting = 0; // hide errors in Smarty
} else {
	$skin->assign('DEBUG', true);
}

// setup correct time zone
if (CFG('date.timezone') != '') {
	date_default_timezone_set(CFG('date.timezone'));
} else {
	date_default_timezone_set("Europe/Sofia");
}

// user session
$user = isset($_SESSION['user']) ? $_SESSION['user'] : array();
