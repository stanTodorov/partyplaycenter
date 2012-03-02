<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $title, $db, $ml;
	$skin->assign('PAGE', 'gallery');

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

	$sql = "SELECT
			`name`,
			UNIX_TIMESTAMP(`added`) AS 'date',
			`club_id`
		FROM `".TABLE_ALBUMS."`
		WHERE `id` = '".$id."'
		LIMIT 1";
	if (!$db->query($sql) || !$db->getCount()) {
		RedirectSite();
	}

	$album = $db->getAssoc();
	$album['date'] = date("d.m.Y г.", $album['date']);

	$skin->assign('ALBUM', $album);

	$url = GetGalleryUrl('albums', $id);

	// get album's pictures
	$sql = "SELECT
			`image`,
			`thumb`
		FROM `".TABLE_ALBUMS_PICS."`
		WHERE `album_id` = '".$id."'
	";

	$result = array();
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$row['image'] = $url . $row['image'];
			$row['thumb'] = $url . $row['thumb'];
			$result[] = $row;
		}
	}

	$skin->assign('RESULT', $result);


	// get all others albums, except selected
	$sql = "SELECT
			a.`id`,
			ap.`thumb`
		FROM
			`".TABLE_ALBUMS."` a,
			`".TABLE_ALBUMS_PICS."` ap
		WHERE
			a.`club_id` = '".$album['club_id']."'
			AND a.`id` <> '".$id."'
			AND ap.`id` = a.`default_picture_id`
	";
	$albums = array();
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$url = GetGalleryUrl('albums', $row['id']);
			$row['thumb'] = $url . $row['thumb'];
			$albums[] = $row;
		}
	}

	$skin->assign('ALBUMS', $albums);

}