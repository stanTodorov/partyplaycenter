<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title, $db, $ml;

	$club = isset($_GET['club']) ? intval($_GET['club']) : 0;

	$skin->assign('PAGE', 'clubs');

	// get clubs list
	$sql = "SELECT `id`, `name`
		FROM `".TABLE_CLUBS."`
		ORDER BY `name` ASC";
	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$clubs = array();
	$found = false;
	while ($row = $db->getAssoc()) {
		if (!$found && $row['id'] == $club) {
			$found = true;
			$club = $row;
		}
		$clubs[] = $row;
	}

	if (!$found) $club = $clubs[0];

	$title .= ' – '.$club['name'];
	$skin->assign('CLUB', $club['name']);
	$skin->assign('CLUB_ID', $club['id']);

	$result = array();

	$url = GetGalleryUrl('events');

	// get events for the club
	$sql = "SELECT
			`image`,
			`thumb`,
			`comment`,
			`url`,
			UNIX_TIMESTAMP(`date`) AS 'date'
		FROM `".TABLE_EVENTS."`
		WHERE `club_id` = '".$club['id']."'
		ORDER BY `date` DESC
		LIMIT 14
	";

	$result['events'] = array();
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['image'] = $url . $row['image'];
			$row['thumb'] = $url . $row['thumb'];
			$row['datefull'] = date("d.m.Y от H:i", $row['date']);
			$row['date'] = date("d.m", $row['date']);
			$result['events'][] = $row;
		}
	}

	// get albums
	$sql = "SELECT
			a.`id`,
			ap.`thumb`
		FROM
			`".TABLE_ALBUMS."` a,
			`".TABLE_ALBUMS_PICS."` ap
		WHERE
			a.`club_id` = '".$club['id']."'
			AND ap.`id` = a.`default_picture_id`
	";

	$result['albums'] = array();
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$url = GetGalleryUrl('albums', $row['id']);
			$row['thumb'] = str_replace(" ", "%20", $url . $row['thumb']);

			$result['albums'][] = $row;
		}
	}

	$skin->assign('RESULT', $result);
}