<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $db, $ml;
	$skin->assign('PAGE', 'events');

	$sql = "SELECT `id`, `name`
		FROM `".TABLE_CLUBS."`
		ORDER BY `name` ASC";
	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$clubs = $db->getAssocArray();
	$result = array();

	$url = GetGalleryUrl('events');

	foreach ($clubs as $club) {
		// get last five events from each club
		$sql = "SELECT
				`image`, `thumb`, `comment`, `url`,
				UNIX_TIMESTAMP(`date`) AS 'date'
			FROM `".TABLE_EVENTS."`
			WHERE `club_id` = '".$club['id']."'
			ORDER BY `date` DESC
			LIMIT 5
		";

		$images = array();
		if ($db->query($sql) && $db->getCount()) {
			while ($row = $db->getAssoc()) {
				$row['image'] = $url . $row['image'];
				$row['thumb'] = $url . $row['thumb'];

				$row['datefull'] = date("d.m.Y от H:i", $row['date']);
				$row['date'] = date("d.m", $row['date']);

				$images[] = $row;
			}
		}

		$result[] = array(
			'name' => $club['name'],
			'images' => $images
		);
	}

	$skin->assign('RESULT', $result);
}