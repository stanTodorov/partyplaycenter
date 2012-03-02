<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

$cfg = array();

// common
$cfg['debug'] = true;
$cfg['lastupdate'] = '1';
$cfg['copyright'] = '2012';
$cfg['language'] = 'bg';
$cfg['captcha.length'] = 4;

// currency options
$cfg['currency'] = 'лв.';
$cfg['currency.prefix'] = false;
$cfg['currency.decimal.number'] = 2;
$cfg['currency.decimal.point'] = ',';
$cfg['currency.thousand.separator'] = ' ';

// date formats
$cfg['date.fmt'] = 'd.m.Y H:i:s O';
$cfg['date.fmt.date'] = 'd.m.Y';
$cfg['date.fmt.datefull'] = 'd.m.Y H:i:s';
$cfg['date.fmt.datenice'] = 'D, d F Y H:i:s O';
$cfg['date.fmt.datenicefull'] = 'd M Y H:i:s';
$cfg['date.fmt.timefull'] = 'H:i:s';
$cfg['date.fmt.time'] = 'H:i';
$cfg['date.fmt.datetime'] = 'd.m.Y H:i';

// login and security related
$cfg['login.salt'] = '!$#5m#@1SD31v3sad340!@#a@$)+(`1!@123`s*As@?#-`0$^`v11_s{}4';
$cfg['login.saltsize'] = 10;
$cfg['login.timeout'] = 2700; // 45 min.
$cfg['login.lostpass.timeout'] = 86400; // 24 h

// paging
$cfg['paging.count'] = 25;
$cfg['paging.groups'] = 7;

// smarty template engine
$cfg['smarty.skin'] = '';
$cfg['smarty.templates'] = 'template';
$cfg['smarty.compile'] = 'cache';

// pictures
$cfg['dir.gallery'] = 'gallery';
$cfg['dir.events'] = 'events';
$cfg['dir.albums'] = 'albums';
$cfg['dir.menu'] = 'menu';
$cfg['thumb.width'] = 90;
$cfg['thumb.height'] = 110;
$cfg['thumb.tv.height'] = 164;
$cfg['thumb.tv.height'] = 200;

// smtp mail settings
$cfg['smtp.hostname'] = 'magnumshop.bg';
$cfg['smtp.hostport'] = 26;
$cfg['smtp.is_auth'] = true;
$cfg['smtp.is_html'] = true;
$cfg['smtp.authtype'] = 'tls';
$cfg['smtp.username'] = 'noreply@magnumshop.bg';
$cfg['smtp.password'] = 'T0nG*dfm]1[f';
$cfg['smtp.frommail'] = 'noreply@magnumshop.bg';
$cfg['smtp.fromname'] = 'MagnumShop.bg';
$cfg['smtp.codepage'] = 'UTF-8';

// Mail addresses for common tasks
$cfg['mail.contact'] = 'mialygk@gmail.com';
$cfg['mail.contact.name'] = 'Contact Form';
