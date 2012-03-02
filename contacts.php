<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $ml;
	$skin->assign('PAGE', 'contacts');


	if (!isset($_POST['submit'])) {
		return;
	}

	$img = new Securimage();

	$errors = $data = array();
	$fields = array(
		'name' => array(1, 64, 1, ''),
		'email' => array(1, 255, 1, '/^([a-zA-Z0-9_\-\.!#$%&\*\+\/=\\\?\^`{}\|~]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/'),
		'content' => array(1, 65535, 1, ''),
		'code' => array(1, CFG('captcha.length'), 1, ''),
		'AreYouHuman' => array(0, 0, 0, '')
	);

	foreach($fields as $key => $val) $data[$key] = '';
	foreach($_POST as $key => $val) {
		if (array_key_exists($key, $fields)) {
			$data[$key] = trim($val);
		}
	}

	foreach ($data as $key => $val) {
		if (($tmp = IsValidate($val, $fields[$key])) !== true) {
			$errors[$key] = $tmp;
		}
	}

	if (IsCSRF()) {
		$skin->assign('ERROR', $ml['L_ERROR_BAD_ID']);
		$skin->assign('RESULT', $data);
		return;
	}

	if (!isset($errors['code']) && !$img->check($data['code'])) {
		$errors['code'] = 'Невалиден код!';
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('ERRORS', $errors);
		$skin->assign('RESULT', $data);
		return;
	}

	// smtp settings
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->SMTPDebug  = 0;
	$mail->Host       = CFG('smtp.hostname');
	$mail->Port       = CFG('smtp.hostport');
	$mail->SMTPSecure = CFG('smtp.authtype');
	$mail->SMTPAuth   = CFG('smtp.is_auth' );
	$mail->Username   = CFG('smtp.username');
	$mail->Password   = CFG('smtp.password');
	$mail->CharSet    = CFG('smtp.codepage');
	$mail->SetLanguage(CFG('language'));
	$mail->IsHTML(false);

	// send mail
	$mail->From       = $data['email'];
	$mail->FromName   = $data['name'];
	$mail->Subject    = ShortText(mb_substr($data['content'], 0, 64), 48, true, true);
	$mail->Body       = strip_tags($data['content']);
	$mail->AddReplyTo($data['email'], $data['name']);
	$mail->AddAddress(CFG('mail.contact'), CFG('mail.contact.name'));

	try {
		$mail->Send();
		$skin->assign('SUCCESS', true);
	}
	catch (Exception $e) {
		$skin->assign('ERROR', 'Възникна грешка при изпращане на съобщението! Моля, опитайте отново!');
		$skin->assign('RESULT', $data);
	}
}