<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;


function GetGalleryPath($what = 'events', $id = null)
{
	$path = BASE_PATH . CFG('dir.gallery') . DS;

	switch($what) {
	case 'events':
		$path .= CFG('dir.events') . DS;
		break;

	case 'albums':
		$path .= CFG('dir.albums') . DS;
		if (intval($id) > 0) {
			$path .= intval($id) . DS;
		}
		break;

	case 'menu':
		$path .= CFG('dir.menu') . DS;
		break;
	}

	return $path;
}

function GetGalleryUrl($what = 'events', $id = null)
{
	$url = BASE_URL . CFG('dir.gallery') . DS;

	switch($what) {
	case 'events':
		$url .= CFG('dir.events') . DS;
		break;

	case 'albums':
		$url .= CFG('dir.albums') . DS;
		if (intval($id) > 0) {
			$url .= intval($id) . DS;
		}
		break;

	case 'menu':
		$url .= CFG('dir.menu') . DS;
		break;
	}

	return $url;
}


/**
 * Get Valid not exists filename at specific path
 *
 * @param stirng $path Path to checking
 * @param string $filename Filename suggestion with extension
 * @param string $suffix Suffix name of filename, before file extension
 * @return array
 */
function GetValidFilename($path, $filename, $suffix = '')
{
	$fileParts = SplitFileExt($filename, true);
	$filename = $fileParts[0] . $suffix . '.' . $fileParts[1];

	clearstatcache();
	if (!file_exists($path . $filename )) {
		return array(
			'filename' => $filename,
			'name' => $fileParts[0],
			'ext' => $fileParts[1]
		);
	}

	do {
		$random = '_' . substr(md5(time() + mt_rand()), 0, 8);
		$name = $fileParts[0] . $random . $suffix;
		$filename = $name . '.' . $fileParts[1];
		clearstatcache();
	} while (file_exists($path . $filename));

	return array(
		'filename' => $filename,
		'name' => $name,
		'ext' => $fileParts[1]
	);
}


function HumanSize($bytes, $precision = 0, $si = false)
{
	$units = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB' );
	$amount = 1024.0;

	if ($si === true) {
		$units = array( 'B', 'kiB', 'MiB', 'GiB', 'TiB', 'PiB' );
		$amount = 1000.0;
	}

	for ($unit = 0; $bytes > $amount; $unit++) {
		$bytes = round($bytes / $amount, $precision);
	}

	return $bytes . ' ' . $units[$unit];
}


function SplitFileExt($file, $lowerExt = true)
{
	$file = explode('.', $file);
	$ext = mb_strtolower(end($file));
	unset($file[count($file) - 1]);
	$file = implode('.', $file);

	if ($lowerExt === true) {
		$ext = mb_strtolower($ext);
	}

	return array($file, $ext);
}


function Attributes($data)
{
	$add = isset($data['added']) ? $data['added'] : 0;
	$add_by = isset($data['added_by']) ? $data['added_by'] : '';
	$mod = isset($data['modified']) ? $data['modified'] : 0;
	$mod_by = isset($data['modified_by']) ? $data['modified_by'] : '';

	if ($add > 0) {
		$add = 'на '.LocaleDate(CFG('date.fmt.datenicefull'), $add).' ';
	}

	if (mb_strlen($add_by) > 0) {
		$add .= 'от '. $add_by;
	}

	if ($mod > 0) {
		$mod = 'на '.LocaleDate(CFG('date.fmt.datenicefull'), $mod).' ';
	}

	if (mb_strlen($mod_by) > 0) {
		$mod .= 'от '. $mod_by;
	}

	return array($add, $mod);
}

function GetMaxUploadLimits()
{
	$filesize = array('size' => PHP_INT_MAX, 'qty' => 'G');

	$maxsize = array(
		@ini_get('upload_max_filesize'),
		@ini_get('post_max_size'),
		@ini_get('memory_limit')
	);

	foreach ($maxsize as $index => $val) {
		$qty = preg_replace('/[^a-z]+/i', '', $val);

		if ((int) $val < $filesize['size'] || $qty !== $filesize['qty']) {
			$filesize['size'] = (int) $val;
			$filesize['qty'] = $qty;
		}
	}

	switch (mb_strtolower($filesize['qty'])) {
	case 'm': $filesize['qty'] = 'MB'; break;
	case 'g': $filesize['qty'] = 'GB'; break;
	case 'k': $filesize['qty'] = 'kB'; break;
	default: $filesize['qty'] = 'B'; break;
	}

	return $filesize['size'] . ' ' . $filesize['qty'];
}



function MsgPush($type = 'info', $message = '')
{

	if (!in_array($type, array('error', 'success', 'info', 'log'))) $type = 'info';

	if ($type === 'log') $message = print_r($message, true);

	$_SESSION['sys.messages'][] = array(
		'type' => $type,
		'message' => $message
	);
}

function MsgPop()
{
	global $skin;

	if (!isset($_SESSION['sys.messages']) || count($_SESSION['sys.messages']) == 0) return;

	foreach ($_SESSION['sys.messages'] as $item => $data) {
		$skin->assign(mb_strtoupper($data['type']), $data['message']);
		unset($_SESSION['sys.messages'][$item]);
	}
}

function RedirectSite($address = '')
{
	header('Location: '.BASE_URL.$address);
	exit;
}



function DeleteEmptyTree($path, $depth = 1)
{
	$tree = explode('/', rtrim($path, '/'));

	for ($i = count($tree) - 1; $i > 0 && $depth >= 0; $i--, $depth--) {
		$node = $tree[$i];
		unset($tree[$i]);

		$dir = implode('/', $tree).'/'.$node.'/';
		$files = @scandir($dir);

		// directory is not empty or error occurrence
		if (count($files) > 2 || !@rmdir($dir)) break;
	}
}

function FormatNumber($number, $currencySign = false)
{
	static $setup = false;
	static $currency = '';
	static $prefix = false;
	static $num = 3;
	static $point = '.';
	static $sep = ',';

	if (!$setup) {
		$currency = CFG('currency');
		$prefix = CFG('currency.prefix');
		$num = CFG('currency.decimal.number');
		$point = CFG('currency.decimal.point');
		$sep = CFG('currency.thousand.separator');
		$setup = true;
	}

	$number = number_format($number, $num, $point, $sep);

	if (!$currencySign)
		return $number;

	if ($prefix)
		return $currency . ' ' . $number;

	return $number . ' ' . $currency;
}

function FormatPrice($price)
{
	return FormatNumber($price, true);
}


function LoadEvents()
{
	global $db, $skin;
	$sql = "SELECT `id` FROM `".TABLE_CLUBS."`";
	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$sql = array();
	while ($row = $db->getAssoc()) {
		$sql[] = "
			(SELECT
				e.`comment`,
				e.`url`,
				e.`image`,
				e.`tv_thumb` AS 'thumb',
				UNIX_TIMESTAMP(e.`date`) AS 'date',
				c.`name` AS 'category',
				c.`id` AS 'cat_id'
			FROM
				`".TABLE_EVENTS."` e,
				`".TABLE_CLUBS."` c
			WHERE
				e.`club_id` = c.`id`
				AND c.`id` = '".$row['id']."'
				AND e.`date` >= NOW()
			ORDER BY
				e.`date` DESC
			LIMIT 5)
		";
	}

	$sql = implode(" union ", $sql);

	if (!$db->query($sql) || !$db->getCount()) {
		return;
	}

	$url = GetGalleryUrl('events');

	$result = array();
	while ($row = $db->getAssoc()) {
		$row['image'] = str_replace(" ", "%20", $url . $row['image']);
		$row['thumb'] = str_replace(" ", "%20", $url . $row['thumb']);

		$row['datefull'] = date("на d.m от H:i", $row['date']);
		$row['date'] = date("d.m", $row['date']);

		$row['logo'] = 'category.'.$row['cat_id'].'.png';
		$result[] = $row;
	}

	shuffle($result);

	$skin->assign('TV_EVENTS', $result);
}

function PkgSave($data)
{
	return base64_encode(serialize($data));
}

function PkgLoad($data)
{
	return unserialize(base64_decode($data));
}