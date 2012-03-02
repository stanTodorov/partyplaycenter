<?php
if (!defined('PROGRAM') && PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'events');
	$title .= ' – Събития';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'add':
		AddEvent();
		break;
	case 'edit':
		EditEvent();
		break;
	case 'delete':
		DeleteEvent();
		break;
	case 'list':
	default:
		ListEvents();
	}

}

function DeleteEvent()
{
	global $db, $skin, $user, $ml, $page;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$sql = "SELECT `thumb`, `image`, `tv_thumb`
		FROM `".TABLE_EVENTS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$path = GetGalleryPath('events');

	$files = $db->getAssoc();
	@unlink($path . $files['image']);
	@unlink($path . $files['thumb']);
	@unlink($path . $files['tv_thumb']);

	$sql = "DELETE FROM `".TABLE_EVENTS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY']);
		RedirectSite('admin/?page=' . $page);
	}

	MsgPush('success', 'Успешно изтрито събитие!');
	RedirectSite('admin/?page=' . $page);
}


function AddEvent()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'add');

	// Get Current Club
	$club = isset($_SESSION['club'])
		? intval($_SESSION['club'], 10)
		: 0;

	$sql = "SELECT `id`, `name`
		FROM `".TABLE_CLUBS."`
		ORDER BY `name` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$clubs = array();
	$found = false;
	while ($row = $db->getAssoc()) {
		if (!$found && $row['id'] == $club) $found = true;
		$clubs[] = $row;
	}

	$skin->assign('CLUBS', $clubs);
	if (!$found) $club = $clubs[0]['id'];

	$skin->assign('RESULT', array('club' => $club));


	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'club' => array('req' => true, 'is' => 'int'),
		'comment' => array('req' => false, 'min' => 2, 'max' => 256),
		'date' => array('req' => true, 'regex' => '/^[\d]{2}\.[\d]{2}\.[\d]{4}$/'),
		'url' => array('min' => 7, 'max' => 256),
		'hour' => array('req' => true, 'min' => 1, 'max' => 2, 'is' => 'int'),
		'minute' => array('req' => true, 'min' => 1, 'max' => 2, 'is' => 'int')
	));

	$path = GetGalleryPath('events');

	$dirsOk = true;

	if (!file_exists($path)) {
		$dirsOk = mkdir($path, 0755, true);
		$dirsOk = chmod($path, 0755);
	}

	if (!file_exists($path . 'thumbs/')) {
		$dirsOk = mkdir($path . 'thumbs/', 0755, true);
		$dirsOk = chmod($path . 'thumbs/', 0755);
	}

	if (!file_exists($path . 'tv/')) {
		$dirsOk = mkdir($path . 'tv/', 0755, true);
		$dirsOk = chmod($path . 'tv/', 0755);
	}


	if (!$dirsOk) {
		$errors['image'] = 'Проблем със сървъра!';
	}

	if ($dirsOk && $_FILES['image']['error'] !== 0) {
		$errors['image'] = 'Възникна проблем с качването на снимката!';
	}
	else if ($dirsOk) {

		$image = GetValidFilename($path, $_FILES['image']['name']);
		$thumb = GetValidFilename($path, 'thumbs/' . $image['filename']);
		$tvThumb = GetValidFilename($path, 'tv/' . $image['filename']);

		try {
			// image check && upload
			$img = new ImageResize($_FILES['image']['tmp_name']);
			$img->save($path . $image['filename']);

			// thumb
			$img = new ImageResize($path . $image['filename']);
			$img->resize(CFG('thumb.width'), CFG('thumb.height'), 'portrait');
			$img->save($path . $thumb['filename']);

			// tv thumb
			$img = new ImageResize($path . $image['filename']);
			$img->resize(CFG('thumb.tv.width'), CFG('thumb.tv.height'), 'portrait');
			$img->save($path . $tvThumb['filename']);

		} catch(ImageResizeExIFInvalidFormat $e) {
			$errors['image'] = 'Невалиден файлов формат! Използвайте PNG, JPG или GIF!';
		} catch(Exception $e) {
			$errors['image'] = 'Възникна неизвестна грешка №'.$e->getCode();
		}
	}

	// no need enymore
	@unlink($_FILES['image']['tmp_name']);

	// check hour
	if ($data['hour'] < 0 || $data['hour'] > 23) {
		$data['hour'] = 'Грешка в часа!';
	} else if ($data['minute'] < 0 || $data['minute'] > 59) {
		$data['hour'] = 'Грешка в минутите!';
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);

		// remove uploaded files
		@unlink($_FILES['image']['tmp_name']);
		@unlink($path . $image['filename']);
		@unlink($path . $thumb['filename']);
		@unlink($path . $tvThumb['filename']);
		return;
	}

	$date = $data['date'];
	$date .= ' ' . sprintf("%02d", $data['hour']) . ':';
	$date .= sprintf("%02d", $data['minute']) . ':00';
	$date = ParseInputDate($date);
	$date = date("Y-m-d H:i:s", $date);

	$sql = "INSERT INTO `".TABLE_EVENTS."` (
			`id`, `club_id`, `comment`, `url`,
			`image`, `thumb`, `tv_thumb`, `date`,
			`added`, `added_by`
		) VALUE (
			NULL,
			'".$db->escapeString($data['club'])."',
			'".$db->escapeString($data['comment'])."',
			'".$db->escapeString($data['url'])."',
			'".$db->escapeString($image['filename'])."',
			'".$db->escapeString($thumb['filename'])."',
			'".$db->escapeString($tvThumb['filename'])."',
			'".$db->escapeString($date)."',
			NOW(),
			'".$user['id']."'
		)";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);

		// remove uploaded files
		@unlink($path . $image['filename']);
		@unlink($path . $thumb['filename']);
		@unlink($path . $tvThumb['filename']);
		return;
	}

	MsgPush('success', 'Успешно добавено събитие!');
	RedirectSite('admin/?page='.$page);
}

function EditEvent()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'edit');

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}
	$skin->assign('ID', $id);

	$sql = "SELECT
			`club_id` AS 'club',
			`comment`,
			`url`,
			`image`,
			`thumb`,
			`tv_thumb`,
			UNIX_TIMESTAMP(`date`) AS 'date'

		FROM `".TABLE_EVENTS."`
		WHERE `id` = '".$id."'
		LIMIT 1
	";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$event = $db->getAssoc();
	$event['hour'] = date("H", $event['date']);
	$event['minute'] = date("i", $event['date']);
	$event['date'] = date("d.m.Y", $event['date']);

	$club = $event['club'];

	// list of clubs
	$sql = "SELECT `id`, `name`
		FROM `".TABLE_CLUBS."`
		ORDER BY `name` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$url = GetGalleryUrl('events');

	$skin->assign('CLUBS', $db->getAssocArray());
	$skin->assign('RESULT', array(
		'club' => $event['club'],
		'image' => $url . $event['image'],
		'thumb' => $url . $event['thumb'],
		'comment' => $event['comment'],
		'url' => $event['url'],
		'date' => $event['date'],
		'minute' => $event['minute'],
		'hour' => $event['hour']
	));

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'club' => array('req' => true, 'is' => 'int'),
		'comment' => array('req' => false, 'min' => 2, 'max' => 256),
		'date' => array('req' => true),
		'url' => array('min' => 7, 'max' => 256),
		'hour' => array('req' => true, 'min' => 1, 'max' => 2, 'is' => 'int'),
		'minute' => array('req' => true, 'min' => 1, 'max' => 2, 'is' => 'int')
	));

	$path = GetGalleryPath('events');
	$dirsOk = true;

	if (!file_exists($path)) {
		$dirsOk = mkdir($path, 0755, true);
		$dirsOk = chmod($path, 0755);
	}

	if (!file_exists($path . 'thumbs/')) {
		$dirsOk = mkdir($path . 'thumbs/', 0755, true);
		$dirsOk = chmod($path . 'thumbs/', 0755);
	}

	if (!file_exists($path . 'tv/')) {
		$dirsOk = mkdir($path . 'tv/', 0755, true);
		$dirsOk = chmod($path . 'tv/', 0755);
	}

	if (!$dirsOk) {
		$errors['image'] = 'Проблем със сървъра!';
	}

	$sqlImages = '';

	if ($dirsOk && $_FILES['image']['error'] == 0) { // only if no errors
		$image = GetValidFilename($path, $_FILES['image']['name']);
		$thumb = GetValidFilename($path, 'thumbs/' . $image['filename']);
		$tvThumb = GetValidFilename($path, 'tv/' . $image['filename']);

		try {
			// image check && upload
			$img = new ImageResize($_FILES['image']['tmp_name']);
			$img->save($path . $image['filename']);

			// thumb
			$img = new ImageResize($path . $image['filename']);
			$img->resize(CFG('thumb.width'), CFG('thumb.height'), 'portrait');
			$img->save($path . $thumb['filename']);

			// tv thumb
			$img = new ImageResize($path . $image['filename']);
			$img->resize(CFG('thumb.tv.width'), CFG('thumb.tv.height'), 'portrait');
			$img->save($path . $tvThumb['filename']);

			// no need enymore
			@unlink($_FILES['image']['tmp_name']);

			$sqlImages = "
				`image` = '".$db->escapeString($image['filename'])."',
				`thumb` = '".$db->escapeString($thumb['filename'])."',
				`tv_thumb` = '".$db->escapeString($tvThumb['filename'])."',
			";
		} catch(ImageResizeExIFInvalidFormat $e) {
			$errors['image'] = 'Невалиден файлов формат! Използвайте PNG, JPG или GIF!';
		} catch(Exception $e) {
			$errors['image'] = 'Възникна неизвестна грешка №'.$e->getCode();
		}
	}

	// check hour
	if ($data['hour'] < 0 || $data['hour'] > 23) {
		$data['hour'] = 'Грешка в часа!';
	} else if ($data['minute'] < 0 || $data['minute'] > 59) {
		$data['hour'] = 'Грешка в минутите!';
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);

		// remove new photos
		if ($sqlImages) {
			@unlink($path . $image['filename']);
			@unlink($path . $thumb['filename']);
			@unlink($path . $tvThumb['filename']);
		}
		return;
	}

	$date = $data['date'];
	$date .= ' ' . sprintf("%02d", $data['hour']) . ':';
	$date .= sprintf("%02d", $data['minute']) . ':00';
	$date = ParseInputDate($date);
	$date = date("Y-m-d H:i:s", $date);

	$sql = "UPDATE `".TABLE_EVENTS."` SET

			`club_id` = '".$db->escapeString($data['club'])."',
			`comment` = '".$db->escapeString($data['comment'])."',
			`url` = '".$db->escapeString($data['url'])."',
			".$sqlImages."
			`date` = '".$db->escapeString($date)."'
		WHERE
			`id` = '".$id."'
	";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);

		// remove new photos
		if ($sqlImages) {
			@unlink($path . $image['filename']);
			@unlink($path . $thumb['filename']);
			@unlink($path . $tvThumb['filename']);
		}

		return;
	}

	// remove old photos
	if ($sqlImages) {
		@unlink($path . $event['image']);
		@unlink($path . $event['thumb']);
		@unlink($path . $event['tv_thumb']);
	}

	MsgPush('success', 'Успешно редактирано събитие!');
	RedirectSite('admin/?page='.$page);

}

function ListEvents()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'list');


	// Get Current Club
	$club = isset($_SESSION['club'])
		? intval($_SESSION['club'], 10)
		: 0;

	$club = isset($_POST['club'])
		? intval($_POST['club'], 10)
		: $club;

	$sql = "SELECT `id`, `name`
		FROM `".TABLE_CLUBS."`
		ORDER BY `name` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$clubs = array();
	$found = false;
	while ($row = $db->getAssoc()) {
		if (!$found && $row['id'] == $club) $found = true;

		$clubs[] = $row;
	}

	if (!$found) $club = $clubs[0]['id'];

	$skin->assign('CLUBS', $clubs);
	$skin->assign('CLUB', $club);
	$_SESSION['club'] = $club;

	$where = FilterSearch($skin, 'SEARCH', 'search',
		array("`id`", "`comment`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	if ($where == '') {
		$where = "WHERE `club_id` = '".$club."'";
	}
	else {
		$where .= " AND `club_id` = '".$club."'";
	}

	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'name' => array('field' => '`comment`', 'name' => 'Заглавие'),
		'date' => array('field' => '`date`', 'name' => 'Дата'),
		'added' => array('field' => '`added`', 'name' => 'Добавено'),

	));

	if ($order == '') {
		$order = 'ORDER BY `added` DESC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM `".TABLE_EVENTS."`
		".$where."
		".$order."
	";

	if ($db->query($sql) && $db->getCount()) {
		$count = $db->getAssoc();
		$count = $count['count'];
	}

	if ($count == 0) return false;
	$paging = new Paging($count, CFG('paging.count'), BASE_URL.'admin/', "pg", true, true, true);
	$paging->grouping = CFG('paging.groups');
	$skin->assign('PAGING', $paging->ShowNavigation());
	$skin->assign('COUNT', $count);

	$sql = "SELECT
			`id`,
			`comment`,
			`url`,
			`image`,
			`thumb`,
			UNIX_TIMESTAMP(`added`) AS 'added',
			UNIX_TIMESTAMP(`date`) AS 'date'
		FROM `".TABLE_EVENTS."`
		".$where."
		".$order."
	".$paging->GetMysqlLimits();

	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$url = GetGalleryUrl('events');

	$result = array();
	for ($x = 1, $odd = 1; $row = $db->getAssoc(); $x++, $odd ^= 1) {

		$row['nr'] = $x;
		$row['odd'] = $odd;

		$row['status'] = 'wait';

		$row['image'] = $url . $row['image'];
		$row['thumb'] = $url . $row['thumb'];

		$row['date'] = LocaleDate(CFG('date.fmt.datetime'), $row['date']);
		$row['added'] = LocaleDate(CFG('date.fmt.datetime'), $row['added']);

		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}
