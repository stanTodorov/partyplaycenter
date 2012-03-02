<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

/**
 * Обработка на формуляра за вход в системата (потребител и парола)
 */
function LoginFromPost()
{
	global $ml, $db, $skin, $user;

	session_regenerate_id(true);

	if (!isset($_POST['login'])) {
		return;
	}

	$username = isset($_POST['username']) ? $db->escapeString($_POST['username']) : '';
	$password = isset($_POST['password']) ? trim($_POST['password']) : '';

	if ($username == '' || $password == '' ) {
		MsgPush('error', $ml['L_ERROR_LOGIN_REQUIRE']);
		RedirectSite('admin/');
	}

	$sql = "SELECT
			`id`,
			`password`,
			`salt`,
			`username`,
			`name`,
			`modified`
		FROM `".TABLE_USERS."`
		WHERE `username` = '".$username."'
		LIMIT 1";

	if (!$db->query($sql)) {
		$skin->assign('FATAL_ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	if (!$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_LOGIN']);
		RedirectSite('admin/');
	}

	$result = $db->getAssoc();

	if ( $result['password'] !== SaltPassword($password, $result['salt'], CFG('login.salt')) ) {
		MsgPush('error', $ml['L_ERROR_LOGIN']);
		RedirectSite('admin/');
	}

	$ip = $_SERVER['REMOTE_ADDR'];
	$ip = ip2long($ip);

	$user = array (
		'timeout'    => time(),
	        'id'         => intval($result['id']),
		'username'   => $result['username'],
		'name'       => $result['name'],
		'ip'         => $_SERVER['REMOTE_ADDR'],
		'browser'    => $_SERVER['HTTP_USER_AGENT'],
		'last_login' => $result['modified']
	);

	$_SESSION['user'] = $user;

	$sql = "UPDATE `".TABLE_USERS."`
		SET
			`session_id` = '".session_id()."',
			`modified` = NOW()
		WHERE `id` = '".$user['id']."'
		LIMIT 1";
	if (!$db->query($sql)) {
		return;
	}

	RedirectSite('admin/');
}


function Logout($timeout = 0, $notoken = false)
{
	$timeout = abs(intval($timeout));

	$token = !IsCSRF()
		|| $notoken;

	if (!$token) return;

	unset($_COOKIE[session_name()]);
	$_SESSION = array();
	session_destroy();

	if ($timeout < 0) return;

	if ($timeout == 0) {
		RedirectSite('admin/');
		exit;
	}

	header('Refresh: '.$timeout.'; url=' . BASE_URL . 'admin/');
}


function CheckLogin()
{
	global $db, $ml, $user, $skin;
	$page = isset($_GET['page']) ? $_GET['page'] : '';

	$isNotLogged = empty($user)
		|| (time() - $user['timeout'] > CFG('login.timeout'))
		|| $user['ip'] !== $_SERVER['REMOTE_ADDR']
		|| $user['browser'] !== $_SERVER['HTTP_USER_AGENT'];

	if ($isNotLogged) {
		LoginFromPost();
		return false;
	}

	$sql = "SELECT `session_id`
		FROM `".TABLE_USERS."`
		WHERE `id` = '".$user['id']."'
		LIMIT 1";

	if (!$db->query($sql) || $db->getCount() !== 1) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		Logout(0, true);
		return false;
	}

	$result = $db->getAssoc();
	$session = $result['session_id'];

	if ($session !== session_id()) {
		Logout(0, true);
		if ($page === 'login' || $page === '') {
			LoginFromPost();
		}
		return false;
	}

	$user['timeout'] = time();

	$skin->assign('LOGGED', true);
	$skin->assign('LOGIN_USERNAME', $user['name']);
	$skin->assign('LOGIN_TIME_LEFT', round(CFG('login.timeout') / 60.0));

	$lastLogin = strtotime($user['last_login']);
	if ($lastLogin > 0) {
		$lastLogin = ShortDate($lastLogin);
		$skin->assign('LOGIN_LASTTIME', $lastLogin);
	}

	$skin->assign('META_REFRESH', CFG('login.timeout') + 2);
	header('Refresh: '.(CFG('login.timeout') + 3).'; ' . BASE_URL.'admin/');

	return true;
}
