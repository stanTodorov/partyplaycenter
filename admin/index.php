<?php
/**
 * FantasticTravels
 * (c) 2012 NAS Technology (http://nasbg.com)
 */

// MAGIC constant
define('PROGRAM', 1);
define('SITE', 'admin');

// Required classes and libraries
chdir(dirname(__FILE__));
require_once('./../location.php');
require_once(BASE_PATH.'common.php');

$page = isset($_GET['page']) ? $_GET['page'] : '';
$title = '';

// Valid web pages
$pages = array (
	'reservations', //default
	'events',
	'albums',
	'settings',
	'profile',
	'ajax',
	'menu'
);

MsgPop();

if ( !CheckLogin() ) {
	if ($page === 'ajax') {
		echo json_encode(array(
			'status' => 'error',
			'refresh' => true,
			'message' => '',
			'url' => BASE_URL.'admin/'
		));
		exit;
	}

	$skin->assign('PAGE', 'login');
	$skin->assign('TITLE', $title);
}
else {
	define('LOGGED', true);
	$skin->assign('LOGGED', LOGGED);

	if ($page == 'logout') {
		Logout();
	}
	else if ($page == '' || in_array($page, $pages)) {
		if ($page == '') $page = $pages[0];

		$action = BASE_PATH . 'admin'. DS . $page . '.php';

		if (file_exists($action)) {
			$skin->assign('PAGE', $page);
			require_once($action);
			Main();
		}
	}
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