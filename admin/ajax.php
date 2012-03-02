<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $db, $ml, $skin, $user, $categories;

	$result = array();
	$action = isset($_GET['action']) ? $_GET['action'] : '';
	
	$default = array(
		'status' => 'error',
		'message' => ''
	);

	switch($action) {
	case 'setting':
		$result = Setting();
		break;
	case 'albums':
		$result = Albums();
		break;
	}


	if (!is_array($result) || !count($result)) {
		echo json_encode($default);
		exit;
	}

	echo json_encode(array_merge($default, $result));
	exit;
}

function Albums()
{
	global $db, $skin, $ml;
	$skin->assign('ACTION', 'albums');

	$club = isset($_POST['club']) ? intval($_POST['club']) : 0;
	if ($club <= 0) return array();


	$sql = "SELECT `name`
		FROM `".TABLE_CLUBS."`
		WHERE `id` = '".$club."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		return array('message' => 'клубове');
	}

	$sql = "SELECT `id`, `name`
		FROM `".TABLE_ALBUMS."`
		WHERE `club_id` = '".$club."'
		ORDER BY `added` DESC
	";

	$result = array();
	if ($db->query($sql) && $db->getCount()) {
		$result = $db->getAssocArray();
	}

	$skin->assign("RESULT", $result);

	return array(
		'status' => 'success',
		'node' => array(
			'where' => '#addAlbum',
			'inside' => true,
			'content' => $skin->fetch('ajax.html')
		)
	);

}

function Setting()
{
	global $db, $skin, $ml;

	// get setting and value
	$name  = isset($_POST['name'])  ? $_POST['name']  : '';
	$value = isset($_POST['value']) ? $_POST['value'] : '';

	// get variable type if setting exists
	$sql = "SELECT `type`, `value`
		FROM `".TABLE_SETTINGS."`
		WHERE `name` = '".$db->escapeString($name)."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		return array();
	}

	$setting = $db->getAssoc();

	// parse value by type
	switch($setting['type']) {
	case 'integer':
		if (!preg_match('/^[\-]*[\d]+$/', $value)) {
			return array(
				'status' => 'error',
				'message' => 'Невалидна целочислена стойност!',
				'input' => array(
					'element' => 'input[name="' . $name . '"]',
					'value' => preg_replace('/[^0-9\-]/', '', $value)
				)
			);
		}

		$value = intval($value);
		$field = $value;
		break;
	case 'string':
		// all symbols are accepted; length is to 255 chars
		if (mb_strlen($value) > 255) {
			return array(
				'status' => 'error',
				'message' => 'Стойността надвишава 255 символа!',
				'input' => array(
					'element' => 'input[name="' . $name . '"]',
					'value' => mb_substr($value, 0, 255)
				)
			);
		}

		$field = $value;
		break;
	case 'boolean':
		// true or false are possibles values
		if ($value !== 'false' && $value !== 'true') {
			return array(
				'status' => 'error',
				'message' => 'Стойността на полето не е от булев тип (true/false)!'
			);
		}

		if ($value === 'true') {
			$field = true;
			break;
		}

		$field = false;
	}

	// save new value
	$sql = "UPDATE `".TABLE_SETTINGS."`
		SET `value` = '".$db->escapeString($value)."'
		WHERE `name` = '".$db->escapeString($name)."'
		LIMIT 1";
	if (!$db->query($sql)) {
		return array(
			'status' => 'error',
			'message' => $ml['L_ERROR_DB_QUERY']
		);
	}

	return array(
		'status' => 'success',
		'input' => array(
			'element' => 'input[name="' . $name . '"]',
			'value' => $field
		)
	);
}
