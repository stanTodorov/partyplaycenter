<?php
/**
 *
 * (c) 2012 NAS Technology (http://nasbg.com)
 */

// MAGIC constant
define('PROGRAM', 1);
define('SITE', 'client');

// Required classes and libraries
chdir(dirname(__FILE__));
require_once('./location.php');
require_once(BASE_PATH.'common.php'); // bootstrap


// Load events on TV
LoadEvents();

$page = isset($_GET['page']) ? $_GET['page'] : '';
$pages = array(
	'home',  // default
	'clubs',
	'events',
	'reservations',
	'contacts',
	'gallery',
	'ajax'
);

if (!in_array($page, $pages)) {
	reset($pages);
	$page = current($pages);
}

$title = '';

MsgPop();

if (file_exists(BASE_PATH.$page.'.php') && is_file(BASE_PATH.$page.'.php')) {
	require_once(BASE_PATH.$page.'.php');
	Main();
}

$skin->assign('TITLE', $title);

// generate new token
$token = GenToken();
$_SESSION['token'] = $token;
$skin->assign('TOKEN', $token);

// save user session
$_SESSION['user'] = $user;

// output
$skin->display('index.html');
