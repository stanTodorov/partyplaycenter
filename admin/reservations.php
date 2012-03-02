<?php
if (!defined('PROGRAM') && PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;
	$skin->assign('PAGE', 'reservations');

	$title .= ' – Резервации';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'edit':
		EditReservations();
		break;
	case 'view':
		ViewReservations();
		break;
	case 'delete':
		DeleteReservations();
		break;
	case 'list':
	default:
		ListReservations();
	}

}


function EditReservations()
{
	global $db, $skin, $user, $ml, $page;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page=reservations');
	}

	$status = isset($_POST['status']) ? intval($_POST['status']) : 0;
	if ($status !== 0 && $status !== 1) $status = 0;

	$sql = "UPDATE `".TABLE_PARTIES."`
		SET `confirmed` = '".$status."'
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql)) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND'].$sql);
		RedirectSite('admin/?page=reservations');
	}

	MsgPush('success', 'Успешно променена резеврация!');
	RedirectSite('admin/?page=reservations');
}


function ViewReservations()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'view');

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$sql = "SELECT
			`id`,
			`content`,
			`price`,
			`earnest`,
			UNIX_TIMESTAMP(`date`) AS 'date',
			`confirmed`
		FROM `".TABLE_PARTIES."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	$result = $db->getAssoc();

	$result['content'] = PkgLoad($result['content']);
	$result['date'] = LocaleDate(CFG('date.fmt.datetime'), $result['date']);

	$result['price'] = FormatPrice($result['price']);
	$result['earnest'] = FormatPrice($result['earnest']);

	$skin->assign('RESULT', $result);
}

function DeleteReservations()
{
	global $db, $skin, $user, $ml, $page;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	if (IsCSRF()) {
		MsgPush('error', $ml['L_ERROR_BAD_ID']);
		RedirectSite('admin/?page='.$page);
	}

	$sql = "DELETE FROM `".TABLE_PARTIES."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		MsgPush('error', $ml['L_ERROR_NOT_FOUND']);
		RedirectSite('admin/?page='.$page);
	}

	MsgPush('success', 'Успешно изтрита резервация!');
	RedirectSite('admin/?page='.$page);
}


function ListReservations()
{
	global $db, $skin, $user, $ml, $page;
	$skin->assign('ACTION', 'list');

	$where = FilterSearch($skin, 'SEARCH', 'search',
		array("`id`", "`price`", "`earnest`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'code' => array('field' => '`id`', 'name' => 'Код'),
		'date' => array('field' => '`date`', 'name' => 'Резервация'),
		'price' => array('field' => '`price`', 'name' => 'Дължима сума'),
		'earnest' => array('field' => '`earnest`', 'name' => 'Капаро'),
		'added' => array('field' => '`added`', 'name' => 'Дата на добавяне'),
		'confirmed' => array('field' => '`confirmed`', 'name' => 'Състояние на резервацията'),
	));

	if ($order == '') {
		$order = 'ORDER BY `added` DESC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM `".TABLE_PARTIES."`
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
			`id`, `confirmed`, `earnest`, `price`,
			UNIX_TIMESTAMP(`added`) AS 'added',
			UNIX_TIMESTAMP(`date`) AS 'date'
		FROM `".TABLE_PARTIES."`
		".$where."
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
		if ($row['confirmed'] == 1) {
			$row['status'] = 'confirmed';
		} else if ($row['confirmed'] == 0 && (time() - $row['added']) >  86400) {
			$row['status'] = 'dead';
		}
		
		$row['price'] = FormatPrice($row['price']);
		$row['earnest'] = FormatPrice($row['earnest']);

		$row['date'] = LocaleDate(CFG('date.fmt.datetime'), $row['date']);
		$row['added'] = LocaleDate(CFG('date.fmt.datetime'), $row['added']);

		$result[] = $row;
	}
	$skin->assign('RESULT', $result);
}