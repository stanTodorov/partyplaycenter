<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $db;
	$skin->assign('PAGE', 'home');

	$sql = "SELECT
			a.`id`,
			ap.`thumb`
		FROM
			`".TABLE_ALBUMS."` a,
			`".TABLE_ALBUMS_PICS."` ap
		WHERE
			ap.`id`  = a.`default_picture_id`
		ORDER BY a.`added` DESC
		LIMIT 35
	";
	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$result = array();

	while ($row = $db->getAssoc()) {
		$path = GetGalleryUrl('albums', $row['id']);
		$row['thumb'] = $path . $row['thumb'];
		$result[] = $row;
	}

	$skin->assign('RESULT', $result);

}