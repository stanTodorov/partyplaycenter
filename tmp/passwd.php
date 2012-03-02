<?php
// MAGIC constant
define('PROGRAM', 1);
define('SITE', 'client');

// Required classes and libraries
chdir(dirname(__FILE__));
require_once('../location.php');
require_once(BASE_PATH.'common.php'); // bootstrap

$salt = GenSalt(10);

$password = 'password';
$hash = SaltPassword($password, $salt, CFG('login.salt'));

echo 'salt: '. $salt . "<br />";
echo 'pass: '. $password . '<br />';
echo 'hash: '. $hash . '<br />';