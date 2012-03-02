<?php
if (!defined('PROGRAM') || PROGRAM !== 1) exit;

function Main()
{
	global $skin, $ml, $db, $title, $data;
	$skin->assign('PAGE', 'reservations');
	$title .= ' – Резервации';

	$times = array(
		array('id' => 1, 'name' => '10:00 - 14:00 ч.', 'time' => '10:00:00'),
		array('id' => 2, 'name' => '14:00 - 16:00 ч.', 'time' => '14:00:00'),
		array('id' => 3, 'name' => '16:00 - 20:00 ч.', 'time' => '16:00:00')
	);

	$partyZones = array(
		array('id' => 1, 'name' => 'Сепарета'),
		array('id' => 2, 'name' => 'Маси'),
		array('id' => 3, 'name' => 'Дискотека')
	);

	$partyKidsMenu = array(
		array('id' => 1, 'name' => '1 бр. тост, бутерки, солети, натурален сок, моркови „Фреш“'),
		array('id' => 2, 'name' => '2 бр. мини хамбургери, бутерки, солети, натурален сок, моркови „Фреш“'),
		array('id' => 3, 'name' => '2 бр. мини пици, бутерки, солети, натурален сок, моркови „Фреш“'),
	);

	$partySubjects1 = array(
		array('id' => 1, 'name' => 'Фея'),
		array('id' => 2, 'name' => 'Принцеса'),
		array('id' => 3, 'name' => 'Русалка'),
		array('id' => 4, 'name' => 'Клоунка'),
		array('id' => 5, 'name' => 'Пипи'),
		array('id' => 6, 'name' => 'Спайдер мен'),
		array('id' => 7, 'name' => 'Пират'),
		array('id' => 8, 'name' => 'Прасчо (Мечо Пух)'),
		array('id' => 9, 'name' => 'Клоун'),
		array('id' => 10, 'name' => 'Индианско парти')
	);

	$partySubjects2 = array(
		array('id' => 1, 'name' => 'Купон с балон'),
		array('id' => 2, 'name' => 'Пиратски купон'),
		array('id' => 3, 'name' => 'Нинджа купон'),
		array('id' => 4, 'name' => 'Каубойски купон'),
		array('id' => 5, 'name' => 'Форт Бойар / Сървайвър')
	);

	$skin->assign('TIMES', $times);
	$skin->assign('ZONES', $partyZones);
	$skin->assign('KIDS_MENU', $partyKidsMenu);
	$skin->assign('PARTY_SUBJECT_1', $partySubjects1);
	$skin->assign('PARTY_SUBJECT_2', $partySubjects2);

	// get Catering
	$catering = array();
	$sql = "SELECT `id`, `name`
		FROM `".TABLE_MENU_CATS."`
		ORDER BY `name` ASC
	";
	if ($db->query($sql) && $db->getCount()) {
		while ($row = $db->getAssoc()) {
			$catering[$row['id']] = $row;
		}
	}

	// get menu content
	$sql = "SELECT
			`id`,
			`thumb`,
			`image`,
			`name`,
			`comment`,
			`amount`,
			`price`,
			`category_id`
		FROM `".TABLE_MENU."`
		ORDER BY `name` ASC
	";

	$url = GetGalleryUrl('menu');

	if ($db->query($sql) && $db->getCount()) {
		$odd = 1;
		while ($row = $db->getAssoc()) {

			$row['odd'] = $odd;
			if ($row['image'] !== '') {
				$row['image'] = $url . $row['image'];
			}

			if ($row['thumb'] !== '') {
				$row['thumb'] = $url . $row['thumb'];
			}

			if (!isset($catering[$row['category_id']])) continue;

			$row['price'] = FormatPrice($row['price']);

			$catering[$row['category_id']]['menu'][] = $row;

			$odd ^= 1;
		}
	}

	foreach ($catering as $id => $item) {
		if (isset($item['menu'])) continue;
		unset($catering[$id]);
	}

	$skin->assign("CATERING", array_values($catering));

	$skin->assign('PRICE', array(
		'total' => FormatPrice(0.00),
		'earnest' => FormatPrice(0.00)
	));

	if (!isset($_POST['submit'])) return;

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
		'note' => array(
			'max' => 8192
		)
	));

	$time = isset($times[$data['time'] - 1])
		? $times[$data['time'] - 1]['time']
		: $times[0]['time'];

	if (!isset($errors['date'])) {
		$date = ParseInputDate($data['date'] . ' ' . $time);

		if ($date <= time()) {
			$errors['date'] = 'Датата и часът трябва да бъдат след текущите!';
		}
		else {
			$date = date("Y/m/d H:i:s", $date);

			// last check
			$sql = "SELECT `id`
				FROM `".TABLE_PARTIES."`
				WHERE `date` = '".$db->escapeString($date)."'
				LIMIT 1";
			if ($db->query($sql) && $db->getCount()) {
				$errors['date'] = $ml['L_ERROR_PARTY_DATE_BUSY'];
			}
		}
	}

	if (IsCSRF()) {
		$skin->assign('ERROR', $ml['L_ERROR_BAD_ID']);
		$skin->assign('RESULT', $data);
		return;
	}

	if (count($errors) > 0) {
		$skin->assign('ERROR', $ml['L_ERROR_INPUT']);
		$skin->assign('ERRORS', $errors);
		$skin->assign('RESULT', $data);
		return;
	}

	/**
	 * Calculations and html generation
	 *
	 */
	$total = 0.00;
	$html = array(
		'Име'       => $data['name'],
		'Телефон'   => $data['phone'],
		'E-mail'    => $data['email'],
		'Дете'      => $data['kid'] . ' на ' . $data['age'] . ' години',
		'Брой деца' => $data['count']
	);

	if (($data['zone'] - 1) >= 0 && ($data['zone'] - 1) < count($partyZones)) {
		$html['Зона'] = $partyZones[$data['zone'] - 1]['name'];
	}

	// check zones (disco, booth, table)
	if ($data['zone'] == 3) { // disco, 3h, 100 lv
		$total += 100.00;
		$html['Зона'] .= ' + 100 лв.';
	}


	// calc kids' menu
	if ($data['zone'] == 3) { // disco, 3h => 8 lv per child
		$total += ($data['count'] * 8.00);
		$html['Продължителност'] = '3 часа; 8 лв./дете';
	}
	else if ($data['duration'] == 2) {  // booth/table, 2h => 10 lv per child
		$total += ($data['count'] * 10.00);
		$html['Продължителност'] = '2 часа; 10 лв./дете';
	}
	else if ($data['duration'] == 3) {  // booth/table, 3h => 12 lv per child
		$total += ($data['count'] * 12.00);
		$html['Продължителност'] = '3 часа; 12 лв./дете';
	}

	// kids menu
	if (($data['kidsMenu'] - 1) >= 0 && ($data['kidsMenu'] - 1) < count($partyKidsMenu)) {
		$html['Детско меню'] = $partyKidsMenu[$data['kidsMenu'] - 1]['name'];
	}

	// party subject check
	if ($data['subject'] == 1) { // party play
		$html['Тематично парти'] = 'Парти Плей';

		if ($data['subjectDuration1'] == 1) { // 1h => 40 lv
			$html['Продължителност тематично парти'] = '1 ч., 40 лв.';
			$total += 40.00;
		}
		else if ($data['subjectDuration1'] == 2) { // 1h30m => 60 lv
			$html['Продължителност тематично парти'] = '1.30 ч., 60 лв.';
			$total += 60.00;
		}

		if (($data['partySubject1'] - 1) >= 0 && ($data['partySubject1'] - 1) < count($partySubjects1)) {
			$html['Парти плей'] = $partySubjects1[$data['partySubject1'] - 1]['name'];
		}
	}
	else if ($data['subject'] == 2) { // Jivko Dragostinov
		$html['Тематично парти'] = 'Живко Драгостинов';
		if ($data['subjectDuration2'] == 1) { // 1h => 60 lv
			$html['Продължителност тематично парти'] = '1 ч., 60 лв.';
			$total += 60.00;
		}
		else if ($data['subjectDuration2'] == 2) { // 1h30m => 90 lv
			$html['Продължителност тематично парти'] = '1.30 ч., 90 лв.';
			$total += 90.00;
		}

		if (($data['partySubject2'] - 1) >= 0 && ($data['partySubject2'] - 1) < count($partySubjects2)) {
			$html['Парти плей'] = $partySubjects2[$data['partySubject2'] - 1]['name'];
		}
	}
/*
	if ($data['hasDecoration'] !== '') {
		$html['Декорации'] = 'С украса';
	}
*/
	// catering
	$catering = array();
	foreach ($data['catering'] as $id => $count) {
		if ($count > 0 && $count <= 100) {
			$catering[$id] = $count;
		}
	}

	if (count($catering) > 0) {
		$cateringIds =  "'" . implode("', '", array_keys($catering)) . "'";

		$sql = "SELECT
				m.`id`,
				m.`price`,
				m.`comment`,
				m.`name`,
				m.`amount`,
				mc.`name` AS 'category'

			FROM `".TABLE_MENU."` m

			LEFT JOIN `".TABLE_MENU_CATS."` mc ON
				mc.`id` = m.`category_id`

			WHERE m.`id` IN (".$cateringIds.")

			ORDER BY mc.`name` ASC, m.`name` ASC
		";
		if ($db->query($sql) && $db->getCount()) {
			$html['Кетъринг'] = '<ul class="catering">';
			while ($row = $db->getAssoc()) {
				$tmp = '<li><span class="cat">'.$row['category'].'</span>';

				$tmp .= '<span class="name">'.$row['name'];
				if ($row['comment'] != '') {
					$tmp .= '<small>('.$row['comment'].')</small>';
				}
				$tmp .= '</span>';

				$tmp .= '<span class="amount">'.$row['amount'].'</span>';

				$tmp .= '<span class="price">'.FormatPrice($row['price']).'</span>';

				$tmp .= '<span class="count"> x '.$catering[$row['id']].'</span>';

				$tmp .= '<span class="total">'.FormatPrice($catering[$row['id']] * $row['price']).'</span></li>';

				$html['Кетъринг'] .= $tmp;

				$total += ($catering[$row['id']] * $row['price']);
			}

			$html['Кетъринг'] .= '</ul>';
		}
	}

	$html = array_merge($html, array(
		'Торта'     => $data['cakeUrl'],
		'Фотосесия' => $data['photoUrl'],
		'Бележка'   => strip_tags($data['note'])
	));


	$htmlTotal = '';
	foreach ($html as $name => $key) {
		$htmlTotal .= '<tr>';
		$htmlTotal .= '<th>'.$name.'</th>';
		$htmlTotal .= '<td>'.$key.'</td>';
		$htmlTotal .= '</tr>';
	}

	$htmlTotal = PkgSave($htmlTotal);

	// final
	$earnest = 0.30 * $total;



	$sql = "INSERT INTO `".TABLE_PARTIES."` (
			`id`,
			`date`,
			`content`,
			`price`,
			`earnest`,
			`added`,
			`confirmed`
		) VALUES (
			NULL,
			'".$db->escapeString($date)."',
			'".$db->escapeString($htmlTotal)."',
			'".$db->escapeString($total)."',
			'".$db->escapeString($earnest)."',
			NOW(),
			'0'
		)";
	if (!$db->query($sql) || !$db->getCount()) {
		$skin->assign('ERROR', $ml['L_ERROR_DB_QUERY']);
		$skin->assign('RESULT', $data);
		return;
	}

	$code = $db->getLastId();
	$skin->assign('SUCCESS', true);
	$skin->assign('CODE', $code);

}