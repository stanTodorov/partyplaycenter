<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'albums');
	$title .= ' – Събития';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'add':
		AddAlbum();
		break;
	case 'edit':
		EditAlbum();
		break;
	case 'delete':
		DeleteAlbum();
		break;
	case 'view':
		ViewAlbum();
		break;
	case 'default':
		DefaultPicture();
		break;
	case 'removePicture':
		RemovePicture();
		break;
	case 'list':
	default:
		ListAlbums();
	}

}

function RemovePicture()
{
	global $db, $skin, $user, $ml, $page;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	if (IsCSRF()) {
		MsgPush('error', $ml['L_ERROR_BAD_ID']);
		RedirectSite('admin/?page=' . $page);
	}

	// delete from disk
	$sql = "SELECT `image`, `thumb`, `album_id`
		FROM `".TABLE_ALBUMS_PICS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$result = $db->getAssoc();
	$path = GetGalleryPath('albums', $result['album_id']);

	@unlink($path . $result['image']);
	@unlink($path . $result['thumb']);

	// delete from DB
	$sql = "DELETE FROM `".TABLE_ALBUMS_PICS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	$db->query($sql);

	// update album
	$sql = "UPDATE `".TABLE_ALBUMS."` a
		SET a.`default_picture_id` = (
			SELECT p.`id`
			FROM `".TABLE_ALBUMS_PICS."` p
			WHERE p.`album_id` = a.`id`
			LIMIT 1
		)
		WHERE
			a.`id` = '".intval($result['album_id'])."'
		";
	$db->query($sql);

	MsgPush('success', 'Успешно изтрита снимка!');
	RedirectSite('admin/?page=' . $page . '&action=view&id=' . $result['album_id']);
}


function DefaultPicture()
{
	global $db, $skin, $user, $ml, $page;

	if (!isset($_POST['submit'])) {
		RedirectSite('admin/?page=' . $page);
	}

	$id = isset($_POST['default']) ? intval($_POST['default']) : 0;

	$sql = "UPDATE
			`".TABLE_ALBUMS."` a,
			`".TABLE_ALBUMS_PICS."` p
		SET
			a.`default_picture_id` = p.`id`
		WHERE
			a.`id` = p.`album_id`
			AND p.`id` = '".$id."'
	";
	if (!$db->query($sql)) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$sql = "SELECT `album_id`
		FROM `".TABLE_ALBUMS_PICS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if ($db->query($sql)) {
		$album = $db->getAssoc();
		$album = $album['album_id'];

		MsgPush('success', 'Успешно променена албумна снимка!');
		RedirectSite('admin/?page=' . $page . '&action=view&id=' . $album);
	}

	RedirectSite('admin/?page=' . $page);
}


function ViewAlbum()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'view');

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	// get album name
	$sql = "SELECT
			`id`,
			`name`,
			`default_picture_id` AS 'default'
		FROM `".TABLE_ALBUMS."`
		WHERE `id` = '".$id."'
	";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$album = $db->getAssoc();
	$skin->assign('ALBUM', $album);

	$url = GetGalleryUrl('albums', $id);
	$result = array();
	$index = 0;

	// get album's pictures
	$sql = "SELECT `id`, `image`, `thumb`
		FROM `".TABLE_ALBUMS_PICS."`
		WHERE `album_id` = '".$id."'";
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['image'] = $url . $row['image'];
			$row['thumb'] = $url . $row['thumb'];

			if ($album['default'] == $row['id']) {
				$row['checked'] = true;

				// current array index = count of all elements
				$index = count($result);
			}

			$result[] = $row;
		}
	}

	// move default album image at first
	if ($index > 0) {
		$tmp = $result[0];
		$result[0] = $result[$index];
		$result[$index] = $tmp;
	}

	$skin->assign('RESULT', $result);
}


function DeleteAlbum()
{
	global $db, $skin, $user, $ml, $page;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	if (IsCSRF()) {
		MsgPush('error', $ml['L_ERROR_BAD_ID']);
		RedirectSite('admin/?page=' . $page);
	}

	$path = GetGalleryPath('albums', $id);

	// delete pictures from disk
	$sql = "SELECT `image`, `thumb`
		FROM `".TABLE_ALBUMS_PICS."`
		WHERE `album_id` = '".$id."'
	";
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			@unlink($path . $row['image']);
			@unlink($path . $row['thumb']);
		}
	}

	DeleteEmptyTree($path . 'thumbs/', 1);

	// delete pictures from db
	$sql = "DELETE FROM `".TABLE_ALBUMS_PICS."`
		WHERE `album_id` = '".$id."'";
	$db->query($sql);

	// delete album
	$sql = "DELETE FROM `".TABLE_ALBUMS."`
		WHERE `id` = '".$id."'
	";
	if (!$db->query($sql) || $db->getCount() !== 1) {
		MsgPush('error', 'Възникна грешка при изтриване на албума!');
		RedirectSite('admin/?page=' . $page);
	}

	MsgPush('success', 'Успешно изтрит албум!');
	RedirectSite('admin/?page=' . $page);
}


function AddAlbum()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'add');

	$skin->assign('UPLOAD_LIMIT', GetMaxUploadLimits());

	// Get Current Club
	$club = isset($_SESSION['club'])
		? intval($_SESSION['club'], 10)
		: 0;

	$album = 0;

	$id = isset($_GET['id'])
		? intval($_GET['id'])
		: 0;

	if ($id > 0) {
		// get album's club id, if exists
		$sql = "SELECT c.`id`
			FROM
				`".TABLE_CLUBS."` c,
				`".TABLE_ALBUMS."` a
			WHERE
				c.`id` = a.`club_id`
				AND a.`id` = '".$id."'
			LIMIT 1";
		if ($db->query($sql) && $db->getCount()) {
			$club = $db->getAssoc();
			$club = $club['id'];
			$ablum = $id;
		}
	}

	$sql = "SELECT `id`, `name`
		FROM `".TABLE_CLUBS."`
		ORDER BY `name` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$forms['clubs'] = array();
	$found = false;
	while ($row = $db->getAssoc()) {
		if (!$found && $row['id'] == $club) $found = true;
		$forms['clubs'][] = $row;
	}

	if (!$found) $club = $clubs[0]['id'];

	$skin->assign('RESULT', array(
		'club' => $club,
		'album' => $id
	));


	// list of exist albums
	$sql = "SELECT `id`, `name`
		FROM `".TABLE_ALBUMS."`
		WHERE `club_id` = '".$club."'
		ORDER BY `added` DESC
	";
	if ($db->query($sql) && $db->getCount()) {
		$forms['albums'] = $db->getAssocArray();
	}

	$skin->assign('FORMS', $forms);

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'club' => array('req' => true, 'is' => 'int'),
		'album' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => false, 'min' => 5, 'max' => 256),
	));

	if ($data['album'] == 0 && mb_strlen($data['name']) == 0) {
		$errors['name'] = 'Това поле е задължително!';
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	// save club option
	$_SESSION['club'] = $data['club'];

	$id = 0;
	$isNewAlbum = false;
	if ($data['album'] > 0) {
		// check if album exist
		$sql = "SELECT `club_id`, `default_picture_id`
			FROM `".TABLE_ALBUMS."`
			WHERE `id` = '".$data['album']."'
			LIMIT 1";

		$found = true;
		if (!$db->query($sql) || !$db->getCount()) {
			$found = false;
		}
		$result = $db->getAssoc();

		// this album is not for selected club
		if ($result['club_id'] != $data['club']) {
			$found = false;
		}

		if (!$found) {
			$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
			$skin->assign('RESULT', $data);
			return;
		}

		$id = $data['album'];
	}
	else {
		// create new album and get id
		$sql = "INSERT INTO `".TABLE_ALBUMS."` (
				`id`, `club_id`, `name`,
				`default_picture_id`, `added`, `added_by`
			) VALUE (
				NULL,
				'".$db->escapeString($data['club'])."',
				'".$db->escapeString($data['name'])."',
				'0',
				NOW(),
				'".$user['id']."'
			)";
		if (!$db->query($sql)) {
			$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
			$skin->assign('RESULT', $data);
			return;
		}

		$id = $db->getLastId();
		$isNewAlbum = true;
	}

	$path = GetGalleryPath('albums', $id);

	$dirsOk = true;

	if (!file_exists($path)) {
		$dirsOk = mkdir($path, 0755, true);
		$dirsOk = chmod($path, 0755);
	}

	if (!file_exists($path . 'thumbs/')) {
		$dirsOk = mkdir($path . 'thumbs/', 0755, true);
		$dirsOk = chmod($path . 'thumbs/', 0755);
	}

	if (!$dirsOk) {
		$errors['image'] = 'Проблем със сървъра!';
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$uploadedCount = count($_FILES['pictures']['name']);
	$filesCount = 0;
	$insertValues = '';

	for ($i = 0; $i < $uploadedCount; $i++) {
		if ($_FILES['pictures']['error'][$i] !== 0) continue;

		$image = GetValidFilename($path, $_FILES['pictures']['name'][$i]);
		$thumb = GetValidFilename($path, 'thumbs/' . $image['filename']);

		try {
			// image check && upload
			$img = new ImageResize($_FILES['pictures']['tmp_name'][$i]);
			$img->save($path . $image['filename']);

			// thumb
			$img = new ImageResize($path . $image['filename']);
			$img->resize(CFG('thumb.width'), CFG('thumb.height'), 'portrait');
			$img->save($path . $thumb['filename']);

		} catch(Exception $e) {
			@unlink($path . $image['filename']);
			@unlink($path . $thumb['filename']);
			@unlink($_FILES['pictures']['tmp_name'][$i]);
			continue;
		}

		// no need enymore
		@unlink($_FILES['pictures']['tmp_name'][$i]);

		$insertValues .= "(NULL, '".$id."', '";
		$insertValues .= $db->escapeString($image['filename']) . "', '";
		$insertValues .= $db->escapeString($thumb['filename']) . "'),\n";

		$filesCount++;
	}

	// add files to database
	$count = 0;
	if ($filesCount > 0) {
		$sql = "INSERT INTO `".TABLE_ALBUMS_PICS."` (
				`id`, `album_id`, `image`, `thumb`
			) VALUES ".rtrim($insertValues, ",\n");
		$db->query($sql);
		$count = $db->getCount();
	}

	// update album pictures
	$sql = "UPDATE `".TABLE_ALBUMS."` a
		SET a.`default_picture_id` = (
			SELECT p.`id`
			FROM `".TABLE_ALBUMS_PICS."` p
			WHERE p.`album_id` = a.`id`
			LIMIT 1
		)
		WHERE a.`default_picture_id` = 0
	";
	$db->query($sql);

	if ($isNewAlbum) {
		$type = 'success';
		$msg = 'Успешно добавен албум, но с ';

		if ($count == 1) $msg .= 'една снимка ';
		else $msg .= $count.' снимки ';

		$msg .= 'в него от общо '.$filesCount.'!';
	}
	else {
		if ($count == 0) {
			$msg = 'Възникна грешка с всички качени снимки!';
			$type = 'error';
		}
		else {
			$type = 'success';
			$msg = 'Успешно качени са '.$count;

			if ($count == 1) $msg .= ' снимка ';
			else $msg .= ' снимки ';

			$msg .= 'в албума от общо '.$filesCount.'!';
		}
	}

	MsgPush($type, $msg);
	RedirectSite('admin/?page='.$page); // returned
}


function EditAlbum()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'edit');

	// Get Current Club
	$id = isset($_GET['id'])
		? intval($_GET['id'], 10)
		: 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$skin->assign('ID', $id);

	// album parameters
	$sql = "SELECT `id`, `name`, `club_id` AS 'club'
		FROM `".TABLE_ALBUMS."`
		WHERE `id` = '".$id."'
		ORDER BY `added` DESC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$result = $db->getAssoc();
	$skin->assign('RESULT', $result);

	// clubs list
	$sql = "SELECT `id`, `name`
		FROM `".TABLE_CLUBS."`
		ORDER BY `name` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$skin->assign('CLUBS', $db->getAssocArray());

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'club' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => true, 'min' => 5, 'max' => 256),
	));

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	// update club
	$_SESSION['club'] = $data['club'];

	// update
	$sql = "UPDATE `".TABLE_ALBUMS."` SET
			`name` = '".$db->escapeString($data['name'])."',
			`club_id` = '".intval($data['club'])."'
		WHERE
			`id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	MsgPush('success', 'Успешна редакция на албум!');
	RedirectSite('admin/?page='.$page); // returned
}


function ListAlbums()
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
		array("a.`id`", "a.`name`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	if ($where == '') {
		$where = "WHERE a.`club_id` = '".$club."'";
	}
	else {
		$where .= " AND a.`club_id` = '".$club."'";
	}

	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'name' => array('field' => 'a.`name`', 'name' => 'Заглавие'),
		'added' => array('field' => 'a.`added`', 'name' => 'Добавено'),
		'count' => array('field' => 'count', 'name' => 'Снимки в албума'),
	));

	if ($order == '') {
		$order = 'ORDER BY `added` DESC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM `".TABLE_ALBUMS."` a
		LEFT JOIN `".TABLE_ALBUMS_PICS."` p ON
			p.`id` = a.`default_picture_id`
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
			a.`id`,
			a.`name`,
			UNIX_TIMESTAMP(a.`added`) AS 'added',
			p.`image`,
			p.`thumb`,
			count(pc.`id`) AS 'count'

		FROM `".TABLE_ALBUMS."` a
		LEFT JOIN `".TABLE_ALBUMS_PICS."` p ON
			p.`id` = a.`default_picture_id`
		LEFT JOIN `".TABLE_ALBUMS_PICS."` pc ON
			pc.`album_id` = a.`id`

		".$where."
		GROUP BY a.`id`
		".$order."
	".$paging->GetMysqlLimits();

	if (!$db->query($sql)) {

		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$result = array();
	for ($x = 1, $odd = 1; $row = $db->getAssoc(); $x++, $odd ^= 1) {
		$row['nr'] = $x;
		$row['odd'] = $odd;

		$row['status'] = 'wait';

		$url = GetGalleryUrl('albums', $row['id']);

		$row['image'] = ($row['image'] != '')
			? $url . $row['image']
			: BASE_URL . 'template/common/images/no-photo.png';

		$row['thumb'] = ($row['thumb'] != '')
			? $url . $row['thumb']
			: BASE_URL . 'template/common/images/no-photo.png';

		$row['added'] = LocaleDate(CFG('date.fmt.datetime'), $row['added']);

		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}
