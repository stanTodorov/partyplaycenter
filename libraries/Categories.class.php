<?php
/**
 * Load and Find node in Categories tree
 *
 * @package App
 */

 class Categories {
	private static $list = array();
	private static $instance = null;
	private $parents = array();
	private $parentsId = array();
	private $isParentsReversed = false;
	private $childs = array();
	private $childsId = array();
	private $id = 0;
	private $found = array();

	private function __construct()
	{
		global $db;
		$refs = array();

		// Load All categories tree
		$sql = "SELECT
				c.`id`,
				c.`parent_id`,
				lc.`name`,
				c.`directory`
			FROM
				`".TABLE_CATEGORIES."` c,
				`".TABLE_CATEGORIES_LANG."` lc,
				`".TABLE_LANGUAGES."` l
			WHERE
				lc.`category_id` = c.`id`
				AND lc.`language_id` = l.`id`
				AND l.`iso` = '".$db->escapeString(CFG('language'))."'
			ORDER BY
				lc.`name` ASC,
				c.`id` ASC
		";
		if (!$db->query($sql) || !$db->getCount()) {
			return false;

		}

		while ($rows = $db->getAssoc()) {
			$thisref = &$refs[$rows['id']];

			$thisref['id'] = $rows['id'];
			$thisref['name'] = $rows['name'];
			$thisref['directory'] = $rows['directory'];

			if ($rows['parent_id'] == 0) {
				self::$list[$rows['id']] = &$thisref;
				continue;
			}

			$refs[$rows['parent_id']]['child'][$rows['id']] = &$thisref;
		}
	}

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function find($id = 0, $list = array())
	{
		$list = count($list) > 0 ? $list : self::$list;
		$this->id = $id;

		if (count($list) == 0) return false;
		if ($id === 0) return $list;

		foreach ($list as $key => $data) {

			if ($id == $key) {
				$this->childs = array($key => $data);

				unset($data['child']);

				$this->parents[$key] = $data;
				return $data;
			}

			if (!isset($data['child']) || !count($data['child'])) {
				continue;
			}

			$child = $this->find($id, $data['child']);
			if ($child !== false) {
				unset($child['child'], $data['child']);
				$this->parents[$key] = $data;
				$this->parentsId[] = $key;
				return $child;
			}
		}

		return false;
	}

	public function parents()
	{
		return array_reverse($this->parents, true);
	}

	public function parentsId()
	{
		return $this->parentsId;
	}

	public function childs()
	{
		return $this->childs;
	}

	public function childsId()
	{
		return $this->_findChildsId($this->childs);
	}

	private function _findChildsId($list)
	{
		$ids = array();

		foreach ($list as $key => $data) {
			$ids[] = $key;

			if (isset($data['child'])) {
				$ids = array_merge($ids, $this->_findChildsId($data['child']));
			}
		}

		return $ids;
	}

	public function renderCategories($baseUrl = '')
	{
		$parent = $this->parents();
		$id = current($parent);
		$id = $id['id'];

		$this->find($id);

		return $this->_renderCategories($this->childs, $baseUrl);
	}

	private function _renderCategories($list = array(), $baseUrl, $level = 0)
	{
		$html = '';
		$html .= '<ul>';

		$li = ($level == 0) ? '<li class="main">' : '<li>';

		foreach ($list as $key => $data) {
			$html .= $li;
			$html .= '<a href="'.$baseUrl.$key.'">';
			$html .= $data['name'];
			$html .= '</a>';

			if (isset($data['child'])) {
				$html .= $this->_renderCategories($data['child'], $baseUrl, $level + 1);
			}

			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}
}

