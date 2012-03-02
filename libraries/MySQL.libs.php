<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

/**
 * Check $_GET search field
 *
 * @global object $db MySQL Database
 * @global constant SEARCH_MAX_WORDS max words per search
 * @global constant SEARCH_MIN_LENGTH min word length
 * @param string $search_var Name of $_GET variable
 * @param array $fields Fields to searching
 * @param array $patterns Pattern for replacing
 * @param array $replaces Replace strings
 * @return string WHERE clause generated string, or null
 */
function FilterSearch($tmpl, $tmpl_var, $search_var, $fields, $patterns = '', $replaces = '')
{
	global $db;

	$max_words = defined('SEARCH_MAX_WORDS') ? SEARCH_MAX_WORDS : 6;
	$min_length = defined('SEARCH_MIN_LENGTH') ? SEARCH_MIN_LENGTH : 2;

	$where = '';
	$pattern = !empty($patterns) ? $patterns : array('/[^a-zа-я\*\?\+\-\.\s\d]+/iu', '/[\s]+/');
	$replace = !empty($replaces) ? $replaces : array('',	' ');

	if (!isset($_GET[$search_var]) || empty($_GET[$search_var])) {
		return;
	}

	$search = trim($_GET[$search_var]);
	$search = trim(preg_replace($pattern, $replace, $search));

	// replace ? with _ and * with %
	$search = preg_replace(array('/[\*]+/', '/[\?]+/'), array('%', '_'), $search);

	// spliting search to words
	$words = explode(" ", $search);

	// Up to three words with min. three letters or searching for numbers
	// (potential speed risk)
	for ($i = 0, $max = count($words); $i < $max && $i < $max_words; $i++)	{
		if (mb_strlen(trim($words[$i])) >= $min_length || intval($words[$i]) > 0)	{
			if (isset($where) && !empty($where)) {
				$where .= " AND ";
			}
			if (is_array($fields)) {
				$field = "";
				foreach($fields as $val) {
					$field .= "CAST($val AS CHAR), ";
				}
				$field = "CONCAT_WS(' ', ".trim($field, ", ").") ";
			}
			else if (mb_strlen($fields) > 0) {
				$field = "$fields ";
			}
			else {
				return $where;
			}
			$where .= "$field LIKE '%".$db->escapeString($words[$i])."%'";
		}
	}
	$tmpl->assign($tmpl_var, SlashString($_GET[$search_var]));
	return (empty($where) ? $where : ("WHERE $where"));
}

/**
 * Get ORDER BY clause via input data
 *
 * @param object $tmpl Smarty Template object
 * @param string $session Session name to store array
 * @param string $sort GET variable name for store current sorting field name
 * @param string $order GET variable name for type sorting - asc or desc
 * @param string $remove GET variable name for delete key
 * @param array $fields Array with fields name (array("key"=>array("field"=>'field', "name"=>'name')) format)
 *
 * @return ORDER BY generated string, or null on errors
 */
function OrderByField($tmpl, $session, $sort_var, $order_var, $remove_var, $fields)
{
	if (!isset($_SESSION['order'][$session])) {
		$_SESSION['order'][$session] = array();
	}

	$sort = isset($_GET[$sort_var]) ? mb_strtolower(trim($_GET[$sort_var])) : '';
	$order = isset($_GET[$order_var]) ? mb_strtolower(trim($_GET[$order_var])) : '';

	$ordering = 'asc';
	switch($order)
	{
		case 'desc':
			$order = 'DESC';
			$ordering = 'asc';
		break;
		case 'asc':
		default:
			$order = 'ASC';
			$ordering = 'desc';
		break;
	}

	foreach($fields as $key => $val) {
		if ($sort == $key) {
			if (isset($_GET[$remove_var])) {
				unset($_SESSION['order'][$session][$key]);
			} else {
				$_SESSION['order'][$session][$key] = array(
					"field" => $val['field'],
					"order" => $order,
					"name" => $val['name'],
					"key" => $key
				);
			}
		}
	}

	$order = '';
	foreach ($_SESSION['order'][$session] as $key => $val)
	{
		$order .= $val['field'] . ' ' . $val['order'] . ', ';
	}
	if (mb_strlen($order) > 0) $order = "ORDER BY ".trim($order, ', ');

	if (isset($_GET[$sort_var])) unset($_GET[$sort_var]);
	if (isset($_GET[$order_var])) unset($_GET[$order_var]);
	if (isset($_GET[$remove_var])) unset($_GET[$remove_var]);

	$tmpl->assign('ORDER', $ordering);
	$tmpl->assign('ORDERS', $_SESSION['order'][$session]);

	return $order;
}
