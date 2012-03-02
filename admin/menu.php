<?php
if (!defined('PROGRAM') && PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'menu');
	$title .= ' – Събития';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'add':
		AddMenu();
		break;
	case 'edit':
		EditMenu();
		break;
	case 'delete':
		DeleteMenu();
		break;
	case 'list':
	default:
		ListMenu();
	}

}

function DeleteMenu()
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

	$sql = "SELECT `image`, `thumb`
		FROM `".TABLE_MENU."`
		WHERE `id` = '".$id."'
		LIMIT 1
	";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	$result = $db->getAssoc();

	// delete pictures from disk
	if ($result['image'] !== '' || $result['thumb'] !== '') {
		$path = GetGalleryPath('menu');
		@unlink($path . $result['image']);
		@unlink($path . $result['thumb']);
	}

	// delete menu
	$sql = "DELETE FROM `".TABLE_MENU."`
		WHERE `id` = '".$id."'";
	if (!$db->query($sql) || $db->getCount() !== 1) {
		MsgPush('error', 'Възникна грешка при изтриване на записа!');
		RedirectSite('admin/?page=' . $page);
	}

	MsgPush('success', 'Успешно изтрит запис!');
	RedirectSite('admin/?page=' . $page);
}


function AddMenu()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'add');

	$skin->assign('UPLOAD_LIMIT', GetMaxUploadLimits());

	// get categories
	$sql = "SELECT `id`, `name`
		FROM `".TABLE_MENU_CATS."`
		ORDER BY `name` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$skin->assign('CATEGORIES', $db->getAssocArray());

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'category' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => true, 'min' => 1, 'max' => 256),
		'comment' => array('req' => false, 'min' => 1, 'max' => 256),
		'price' => array('req' => true, 'min' => 1, 'max' => 7, 'is' => 'float'),
		'amount' => array('req' => true, 'min' => 1, 'max' => 64)
	));

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$data['price'] = str_replace(',', '.', $data['price']);

	// create new album and get id
	$sql = "INSERT INTO `".TABLE_MENU."` (
			`id`, `category_id`, `image`, `thumb`, `name`,
			`comment`, `amount`, `price`, `added`, `added_by`,
			`modified`, `modified_by`
		) VALUE (
			NULL,
			'".$db->escapeString($data['category'])."',
			'', '',
			'".$db->escapeString($data['name'])."',
			'".$db->escapeString($data['comment'])."',
			'".$db->escapeString($data['amount'])."',
			'".$db->escapeString($data['price'])."',
			NOW(),
			'".$user['id']."',
			NOW(),
			'".$user['id']."'
		)";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	$id = $db->getLastId();

	$path = GetGalleryPath('menu');

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

	if ($dirsOk && $_FILES['image']['error'] == 0) {

		$image = GetValidFilename($path, $_FILES['image']['name']);
		$thumb = GetValidFilename($path, 'thumbs/' . $image['filename']);

		try {
			// image check && upload
			$img = new ImageResize($_FILES['image']['tmp_name']);
			$img->save($path . $image['filename']);

			// thumb
			$img = new ImageResize($path . $image['filename']);
			$img->resize(CFG('thumb.width'), CFG('thumb.height'), 'portrait');
			$img->save($path . $thumb['filename']);


			$sql = "UPDATE `".TABLE_MENU."` SET
					`image` = '".$db->escapeString($image['filename'])."',
					`thumb` = '".$db->escapeString($thumb['filename'])."'
				WHERE `id` = '".$id."'
				LIMIT 1
			";

			$db->query($sql);

		} catch(ImageResizeExIFInvalidFormat $e) {
			$errors['image'] = 'Невалиден файлов формат! Използвайте PNG, JPG или GIF!';
		} catch(Exception $e) {
			$errors['image'] = 'Възникна неизвестна грешка №'.$e->getCode();
		}

		// no need enymore
		@unlink($_FILES['image']['tmp_name']);

		if (count($errors) > 0) {
			MsgPush('error', 'Възникна грешка при качването на снимката!');
			RedirectSite('admin/?page='.$page); // returned
		}
	}

	MsgPush('success', 'Успешно добавен запис към менюто!');
	RedirectSite('admin/?page='.$page);
}


function EditMenu()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'edit');

	$skin->assign('UPLOAD_LIMIT', GetMaxUploadLimits());

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}

	// get old data
	$sql = "SELECT
			`category_id`,
			`image`,
			`thumb`,
			`name`,
			`comment`,
			`amount`,
			`price`
		FROM `".TABLE_MENU."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=' . $page);
	}
	$url = GetGalleryUrl('menu');
	$result = $db->getAssoc();
	$skin->assign('RESULT', array(
		'category' => $result['category_id'],
		'name' => $result['name'],
		'comment' => $result['comment'],
		'amount' => $result['amount'],
		'price' => $result['price'],
		'image' => ($result['image'] !== '') ? ($url . $result['image']) : $result['image'],
		'thumb' => ($result['thumb'] !== '') ? ($url . $result['thumb']) : $result['thumb']
	));

	$skin->assign('ID', $id);

	// get categories
	$sql = "SELECT `id`, `name`
		FROM `".TABLE_MENU_CATS."`
		ORDER BY `name` ASC
	";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY']);
		RedirectSite('admin/?page=' . $page);
	}

	$skin->assign('CATEGORIES', $db->getAssocArray());

	if (!isset($_POST['submit'])) return;

	$errors = FormValidate($_POST, $data, array(
		'category' => array('req' => true, 'is' => 'int'),
		'name' => array('req' => true, 'min' => 1, 'max' => 256),
		'comment' => array('req' => false, 'min' => 1, 'max' => 256),
		'price' => array('req' => true, 'min' => 1, 'max' => 7, 'is' => 'float'),
		'amount' => array('req' => true, 'min' => 1, 'max' => 64)
	));

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('RESULT', $data);
		$skin->assign('ERRORS', $errors);
		return;
	}

	$data['price'] = str_replace(',', '.', $data['price']);

	// create new album and get id
	$sql = "UPDATE `".TABLE_MENU."` SET
			`category_id` = '".$db->escapeString($data['category'])."',
			`name` = '".$db->escapeString($data['name'])."',
			`comment` = '".$db->escapeString($data['comment'])."',
			`amount` = '".$db->escapeString($data['amount'])."',
			`price` = '".$db->escapeString($data['price'])."',
			`modified` = NOW(),
			`modified_by` = '".$user['id']."'
		WHERE
			`id` = '".$id."'";
	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	$path = GetGalleryPath('menu');

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

	if ($dirsOk && $_FILES['image']['error'] == 0) {

		$image = GetValidFilename($path, $_FILES['image']['name']);
		$thumb = GetValidFilename($path, 'thumbs/' . $image['filename']);

		try {
			// image check && upload
			$img = new ImageResize($_FILES['image']['tmp_name']);
			$img->save($path . $image['filename']);

			// thumb
			$img = new ImageResize($path . $image['filename']);
			$img->resize(CFG('thumb.width'), CFG('thumb.height'), 'portrait');
			$img->save($path . $thumb['filename']);


			$sql = "UPDATE `".TABLE_MENU."` SET
					`image` = '".$db->escapeString($image['filename'])."',
					`thumb` = '".$db->escapeString($thumb['filename'])."'
				WHERE `id` = '".$id."'
				LIMIT 1
			";

			if (!$db->query($sql)) {
				// delete new files
				unlink($path . $image['filename']);
				unlink($path . $thumb['filename']);
			}
			else { // all is ok
				// delete old files
				unlink($path . $result['image']);
				unlink($path . $result['thumb']);
			}

		} catch(ImageResizeExIFInvalidFormat $e) {
			$errors['image'] = 'Невалиден файлов формат! Използвайте PNG, JPG или GIF!';
		} catch(Exception $e) {
			$errors['image'] = 'Възникна неизвестна грешка №'.$e->getCode();
		}

		// no need enymore
		@unlink($_FILES['image']['tmp_name']);

		if (count($errors) > 0) {
			MsgPush('error', 'Възникна грешка при качването на снимката!');
			RedirectSite('admin/?page='.$page); // returned
		}
	}

	MsgPush('success', 'Успешно променен запис към менюто!');
	RedirectSite('admin/?page='.$page);
}


function ListMenu()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'list');

	$where = FilterSearch($skin, 'SEARCH', 'search',
		array("m.`name`", "m.`comment`", "m.`price`", "mc.`name`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'name' => array('field' => 'm.`name`', 'name' => 'Наименование'),
		'category' => array('field' => 'mc.`name`', 'name' => 'Категория'),
		'image' => array('field' => 'm.`image`', 'name' => 'Снимка'),
		'price' => array('field' => 'm.`price`', 'name' => 'Цена'),
		'amount' => array('field' => 'm.`amount`', 'name' => 'Количество'),
	));

	if ($order == '') {
		$order = 'ORDER BY mc.`name` ASC, m.`added` DESC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM `".TABLE_MENU."` m
		LEFT JOIN `".TABLE_MENU_CATS."` mc ON
			mc.`id` = m.`category_id`
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
			m.`id`,
			m.`name`,
			m.`comment`,
			m.`amount`,
			m.`price`,
			m.`image`,
			m.`thumb`,
			mc.`name` AS 'category'
		FROM `".TABLE_MENU."` m
		LEFT JOIN `".TABLE_MENU_CATS."` mc ON
			mc.`id` = m.`category_id`
		".$where."
		".$order."
	".$paging->GetMysqlLimits();

	if (!$db->query($sql)) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$url = GetGalleryUrl('menu');

	$result = array();
	for ($x = 1, $odd = 1; $row = $db->getAssoc(); $x++, $odd ^= 1) {
		$row['nr'] = $x;
		$row['odd'] = $odd;

		if ($row['thumb'] !== '') {
			$row['thumb'] = $url . $row['thumb'];
		}

		if ($row['image'] !== '') {
			$row['image'] = $url . $row['image'];
		}

		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}
