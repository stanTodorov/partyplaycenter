<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title;

	$skin->assign('PAGE', 'settings');
	$title .= ' – Настройки';

	$action = isset($_GET['action']) ? $_GET['action'] : '';
	switch($action) {
	case 'reset':
		ResetSettings();
		break;
	case 'list':
	default:
		ListSettings();
	}

}

function ListSettings()
{
	global $skin, $title, $user, $db, $ml, $page;

	$where = FilterSearch($skin, 'SEARCH', 'search',
		array("`name`", "`value`", "`default`", "`type`", "`description`"),
		array('/[^a-zа-я\-\.\s\d\_@]+/iu', '/[\s]+/'),
		array('', ' ')
	);

	$order = OrderByField($skin, $page, 'sort', 'order', 'delorder', array(
		'name' => array('field' => '`name`', 'name' => 'Настройка'),
		'value' => array('field' => '`value`', 'name' => 'Стойност'),
		'type' => array('field' => '`type`', 'name' => 'Тип')
	));

	if ($order == '') {
		$order = 'ORDER BY `name` DESC';
	}

	$count = 0;
	$sql = "SELECT
			COUNT(*) AS 'count'
		FROM `".TABLE_SETTINGS."`
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

	$sql = "SELECT `name`, `value`, `description`, `type`
		FROM `".TABLE_SETTINGS."`
		".$where."
		".$order."
	".$paging->GetMysqlLimits();

	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		return;
	}

	$result = array();
	while ($row = $db->getAssoc()) {
		$result[] = $row;
	}

	$skin->assign('RESULT', $result);
}


function ResetSettings()
{
	global $db, $ml, $page;

	if (!isset($_POST['submit'])) {
		RedirectSite('admin/?page='.$page);
	}

	$names = isset($_POST['reset'])
		? $_POST['reset']
		: array();

	if (($total = count($names)) == 0) {
		RedirectSite('admin/?page='.$page);
	}

	// filter
	foreach ($names as $key => $name) {
		$names[$key] = $db->escapeString($name);
	}

	$names = "'" . implode("', '", $names) . "'";

	$sql = "UPDATE `".TABLE_SETTINGS."`
		SET `value` = `default`
		WHERE `name` IN (".$names.")
		LIMIT ".$total."
	";

	if (!$db->query($sql)) {
		MsgPush('error', $ml['L_ERROR_DB_QUERY']);
		RedirectSite('admin/?page='.$page);
	}

	MsgPush('success', 'Успешно върнати '.$total.' настройки към първоначалното си състояние!');
	RedirectSite('admin/?page='.$page);
}