<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $db, $ml, $skin, $user, $categories;

	$result = array();
	$action = isset($_GET['action']) ? $_GET['action'] : '';

	$default = array(
		'status' => 'error',
		'message' => ''
	);

	switch($action) {
	case 'CalcReservation':
		$result = CalcReservation();
		break;
	}


	if (!is_array($result) || !count($result)) {
		echo json_encode($default);
		exit;
	}

	echo json_encode(array_merge($default, $result));
	exit;
}

function CalcReservation()
{
	global $db, $skin, $ml;

	$times = array(
		array('id' => 1, 'name' => '10:00 - 14:00 ч.', 'time' => '10:00:00'),
		array('id' => 2, 'name' => '14:00 - 16:00 ч.', 'time' => '14:00:00'),
		array('id' => 3, 'name' => '16:00 - 20:00 ч.', 'time' => '16:00:00')
	);

	$errors = FormValidate($_POST, $data, array(
		'date' => array(
			'req' => true,
			'regex' => '/^[\d]{2}\.[\d]{2}\.[\d]{4}$/'
		),
		'time' => array(
			'req' => true,
			'is' => 'int'
		),
		'name' => array(
			'req' => true,
			'max' => '255'
		),
		'phone' => array(
			'req' => true,
			'max' => '255'
		),
		'email' => array(
			'req' => true,
			'is' => 'email'
		),
		'kid' => array(
			'req' => true,
			'max' => 255
		),
		'age' => array(
			'req' => true,
			'is' => 'int',
			'between' => array(
				1, 100
			)
		),
		'count' => array(
			'req' => true,
			'is' => 'int',
			'between' => array(
				1, 100
			)
		),
		'zone' => array(),
		'duration' => array(),
		'kidsMenu' => array(),
		'subject' => array(),
		'subjectDuration1' => array(),
		'subjectDuration2' => array(),
		'partySubject1' => array(),
		'partySubject2' => array(),
		'cateringCategories' => array(),
		'catering' => array(),
		'cakeUrl' => array(),
		'photoUrl' => array(),
		'note' => array(),
	));

	/**
	 * Important data is not ok?
	 */
	if (count($errors)) {
		return array(
			'status' => 'error',
			'message' => 'Моля, първо попълнете задължителните полета!'
		);
	}

	/**
	 * Date/Time checking
	 */
	$time = isset($times[$data['time'] - 1])
		? $times[$data['time'] - 1]['time']
		: $times[0]['time'];

	$date = ParseInputDate($data['date'] . ' ' . $time);

	if ($date <= time()) {
		return array(
			'status' => 'error',
			'message' =>  'Датата и часът трябва да бъдат след текущите!'
		);
	}
	else {
		$date = date("Y/m/d H:i:s", $date);

		// last check
		$sql = "SELECT `id`
			FROM `".TABLE_PARTIES."`
			WHERE `date` = '".$db->escapeString($date)."'
			LIMIT 1";
		if ($db->query($sql) && $db->getCount()) {
			return array(
				'status' => 'error',
				'message' => $ml['L_ERROR_PARTY_DATE_BUSY']
			);
		}
	}

	/**
	 * Calculate total price
	 *
	 */
	$total = 0.00;

	// check zones (disco, booth, table)
	if ($data['zone'] == 3) { // disco, 3h, 100 lv
		$total += 100.00;
	}

	// calc kids' menu
	if ($data['zone'] == 3) { // disco, 3h => 8 lv per child
		$total += ($data['count'] * 8.00);
	}
	else if ($data['duration'] == 2) {  // booth/table, 2h => 10 lv per child
		$total += ($data['count'] * 10.00);
	}
	else if ($data['duration'] == 3) {  // booth/table, 3h => 12 lv per child
		$total += ($data['count'] * 12.00);
	}

	// party subject check
	if ($data['subject'] == 1) { // party play
		if ($data['subjectDuration1'] == 1) { // 1h => 40 lv
			$total += 40.00;
		}
		else if ($data['subjectDuration1'] == 2) { // 1h30m => 60 lv
			$total += 60.00;
		}
	}
	else if ($data['subject'] == 2) { // Jivko Dragostinov
		if ($data['subjectDuration2'] == 1) { // 1h => 60 lv
			$total += 60.00;
		}
		else if ($data['subjectDuration2'] == 2) { // 1h30m => 90 lv
			$total += 90.00;
		}
	}


	// catering
	$catering = array();
	foreach ($data['catering'] as $id => $count) {
		if ($count > 0 && $count <= 100) {
			$catering[$id] = $count;
		}
	}

	if (count($catering) > 0) {
		$cateringIds =  "'" . implode("', '", array_keys($catering)) . "'";

		$sql = "SELECT `id`, `price`
			FROM `".TABLE_MENU."`
			WHERE `id` IN (".$cateringIds.")
		";
		if ($db->query($sql) && $db->getCount()) {
			while ($row = $db->getAssoc()) {
				$total += ($catering[$row['id']] * $row['price']);
			}
		}
	}

	// final
	$earnest = 0.30 * $total;

	return array(
		'status' => 'success',
		'message' => '',
		'nodes' => array(
			array(
				'inside' => true,
				'where' => '#price',
				'content' => FormatPrice($total)
			),
			array(
				'inside' => true,
				'where' => '#earnest',
				'content' => FormatPrice($earnest)
			)
		)
	);
}
