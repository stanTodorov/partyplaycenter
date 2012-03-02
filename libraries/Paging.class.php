<?php
/**
 * Paging Class
 * Require: PHP 5+
 *
 * @author Mikhail Kyosev <mialygk@gmail.com>
 * @version 1.1
 * @copyright March 2009 - June 2010
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @package Paging
 */

class Paging {

	/**
	 * Pages grouping
	 * @var integer
	 */
	public $grouping;

	/**
	 * Total records (count)
	 * @var integer
	 */
	public $total;

	/**
	 * Number of records per page
	 * @var integer
	 */
	public $count;

	/**
	 * Back Link content (<)
	 * @var string
	 */
	public $back = '&lsaquo; Предишна';

	/**
	 * Next Link content (>)
	 * @var string
	 */
	public $next = 'Следваща &rsaquo;';

	/**
	 * First Link content (<<)
	 * @var string
	 */
	public $first = '&laquo; Първа';

	/**
	 * Last link content (>>)
	 * @var string
	 */
	public $last = 'Последна &raquo;';

	/**
	 * Back Link content Tooltip text
	 * @var string
	 */
	public $back_tooltip = 'Предишна страница';

	/**
	 * Next Link content Tooltip text
	 * @var string
	 */
	public $next_tooltip = 'Следваща страница';

	/**
	 * First Link content Tooltip text
	 * @var string
	 */
	public $first_tooltip = 'Първа страница';

	/**
	 * Last Link content Tooltip text
	 * @var string
	 */
	public $last_tooltip = 'Последна страница';

	/**
	 * Base URL for links (ie $_SERVER['PHP_SELF'])
	 * @var string
	 */
	private $filename;

	/**
	 * Current pagenumber
	 * @var integer
	 */
	private $page;

	/**
	 * $_GET page name for current page
	 * @var string
	 */
	private $page_name;

	/**
	 * First and Next link exists to output
	 * @var bool
	 */
	private $show_first_last;

	/**
	 * Back and Next link exists to output
	 * @var bool
	 */
	private $show_back_next;

	/**
	 * Whitespace exists to output
	 * @var bool
	 */
	private $whitespace;

	/**
	 * Constructor
	 */
	function __construct(
			$total = 0,
			$count = 1,
			$filename = "",
			$page_name = "page",
			$show_first_last = true,
			$show_back_next = true,
			$show_whitespace = true
	) {
		$this->total = intval($total);
		$this->SetCountPerPage($count);
		$this->SetVarName($page_name);
		if ($filename !== "") $this->filename = $filename;
		else $this->SetFilename($_SERVER['SCRIPT_NAME']);
		$this->ShowFirstLast($show_first_last);
		$this->ShowBackNext($show_back_next);
		$this->whitespace = ($show_whitespace == true) ? ' ' : '';
	}

	/**
	 * Set Base Link of anchor
	 *
	 * @param string $filename URL for anchors
	 */
	public function SetFilename($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * Set Count per page option
	 *
	 * @param string,integer $count Count records per page
	 */
	public function SetCountPerPage($count)
	{
		if (intval($count) > 0) {
			$this->count = intval($count);
		}
		else {
			$this->count = 1;
		}
	}

	/**
	 * Set $_GET variable for page name
	 *
	 * @param string $page_name Page name variable
	 */
	public function SetVarName($page_name)
	{
		$this->page_name = $page_name;
	}

	/**
	 * Get $_GET varibale for page name
	 *
	 * @return string Page name variable
	 */
	public function GetVarName()
	{
		return $this->page_name;
	}

	/**
	 * Set First and Next Link output
	 *
	 * @param bool $show_first_last True if First and Last links exists to output
	 */
	public function ShowFirstLast($show_first_last) {
		if (is_bool($show_first_last)) {
			$this->show_first_last = $show_first_last;
		}
		else {
			$this->show_first_last = true;
		}
	}

	/**
	 * Set Back and Next Link output
	 *
	 * @param bool $show_back_next True if Back and Next links exists to output
	 */
	public function ShowBackNext($show_back_next)
	{
		if (is_bool($show_back_next)) {
			$this->show_back_next = $show_back_next;
		}
		else {
			$this->show_back_next = true;
		}
	}

	/**
	 * Whitespace between links?
	 *
	 * @param bool $whitespace True if whitespace exists, false - otherwise
	 */
	public function SetWhitespace($whitespace)
	{
		if ($whitespace === true) {
			$this->whitespace = " ";
		}
		else {
			$this->whitespace = "";
		}
	}

	/**
	 * Get Current page number
	 *
	 * @return integer Current Page number
	 */
	public function GetCurrentPage()
	{
		$pages = ceil($this->total / $this->count);

		if (isset($_GET[$this->page_name]))
		{
			$this->page = intval($_GET[$this->page_name]);

			if ($this->page <= 0) {
				$this->page = 1;
			}
			else if ($this->page > $pages) {
				$this->page = $pages;
			}

		}
		else if (!is_null($this->page)) {
			if ($this->page <= 0) {
				$this->page = 1;
			}
			else if ($this->page > $pages) {
				$this->page = $pages;
			}
		}
		else {
			$this->page = 1;
		}
		return $this->page;
	}

	/**
	 * Set Current page number
	 *
	 * @param integer $page
	 */
	public function SetCurrentPage($page)
	{
		$this->page = intval($page);
	}

	/**
	 * Get Offset
	 *
	 * @return integer Offset
	 */
	public function GetOffset()
	{
		$this->GetCurrentPage();

		return (($this->page - 1) * $this->count);
	}

	/**
	 * Get Limit-Generated string for MySQL ("LIMIT offset, count")
	 *
	 * @return string MySQL Limit-generated string
	 */
	public function GetMysqlLimits()
	{
		$offset = $this->GetOffset();
		$sql = " LIMIT ".$offset.", ".$this->count;
		return $sql;
	}

	/**
	 * Show Navigation as anchor links
	 *
	 * @return string Generated HTML links
	 */
	public function ShowNavigation()
	{
		// pages = all elements / elements per page
		$pages = ceil($this->total / $this->count);

		$get_vars = $this->GetAllVars($this->page_name);
		$this->GetCurrentPage();
		$links = "";

		if ($this->page > $pages) {
			$this->page = $pages;
		}

		if ( $this->grouping > 2 && $this->grouping < $pages ) {
			$start = ($this->page - 1) * $this->count;

			$diff = floor($this->grouping / 2);

			if ($this->page == 1) $diff = 0;
			else if ($this->page == $pages) $diff = $this->grouping - 1;
//			else if ($this->page == $this->grouping) $diff += 1;

			$begin = $this->page - $diff;
			if ($begin < 2) $begin = 1;
			$end = $begin + $this->grouping;
			if ($end > $pages + 1) {
				$end = $pages + 1;
				$begin = $pages - $this->grouping + 1;
			}

		}
		else {
			$begin = 1;
			$end = $pages + 1;
		}

		if ( $pages > 1 ) {
			// First link
			if ($this->show_first_last !== false) {
				if ($this->page > 1 && $begin > 1) {
					$links .= $this->whitespace.'<a href="'.$this->filename.'?'.$get_vars.$this->page_name.'=1"';
					if ($this->first_tooltip != '') {
						$links .= ' title="'.$this->first_tooltip.'"';
					}
					$links .= '>'.$this->first.'</a>';
				}
			}
			// Back link
			if ($this->show_back_next !== false) {
				if ($this->page > 1) {
					$links .= $this->whitespace.'<a href="'.$this->filename.'?'.$get_vars.$this->page_name.'='.($this->page-1).'"';
					if ($this->back_tooltip != '') {
						$links .= ' title="'.$this->back_tooltip.'"';
					}
					$links .='>'.$this->back.'</a>';
				}
			}
			// Numbers
			for($x = $begin; $x < $end; $x++) {
				// No link for current page
				if ($x == $this->page)
					$links .= $this->whitespace.'<span>'.$this->page.'</span>';
				// Links for others
				else
					$links .= $this->whitespace.'<a href="'.$this->filename.'?'.$get_vars.$this->page_name.'='.$x.'">'.$x.'</a>';
			}
			// Next link
			if ($this->show_back_next !== false) {
				if ($this->page < $pages) {
					$links .= $this->whitespace.'<a href="'.$this->filename.'?'.$get_vars.$this->page_name.'='.($this->page+1).'"';
					if ($this->next_tooltip != '') {
						$links .= ' title="'.$this->next_tooltip.'"';
					}
					$links .= '>'.$this->next.'</a>';
				}
			}
			// Last link
			if ($this->show_first_last !== false) {
				if ($this->page < $pages && ($end - 1) < $pages) {
					$links .= $this->whitespace.'<a href="'.$this->filename.'?'.$get_vars.$this->page_name.'='.$pages.'"';
					if ($this->last_tooltip != '') {
						$links .= ' title="'.$this->last_tooltip.'"';
					}
					$links .= '>'.$this->last.'</a>';
				}
			}
		}
		return $links;
	}

	/**
	 * Show Page Navigation with <Select><option></option></select>
	 *
	 * @return string HTML Generated "<option></option>"'s
	 */
	public function ShowSelectNavigation()
	{
		$links = '';
		$pages = ceil($this->total / $this->count);
		$get_vars = $this->GetAllVars($this->page_name);
		$this->GetCurrentPage();
		if ( $pages > 1 ) {
			for($x = 1; $x <= $pages; $x++) {
				$links .= '<option value="'.$x.'"';

				if ($x == $this->page) {
					$links .= ' selected="selected" ';
				}

				$links .= '>'.$x.'</option>';
			}
		}
		return $links;
	}


	/**
	 *
	 */
	public function GetItemPosition($index_in_page = 1)
	{
		if (intval($this->page) == 0) {
			$this->GetCurrentPage();
		}

		return ($this->count * ($this->page - 1) + $index_in_page + 1);
	}

	/**
	 * Get All $_GET variables
	 *
	 * @param string $rm_var Exclude variable name (key from $_GET arary)
	 * @return mixed $_GET Variables
	 */
	private function GetAllVars($rm_var = '')
	{
		$get_vars = '';
		foreach($_GET as $key => $val) {
			if ($key != $rm_var) {
				$get_vars.= "$key=".htmlspecialchars($val)."&amp;";
			}
		}
		return $get_vars;
	}
}
################################################################################
# Usage
/*******************************************************************************
	// Calculate current page and all pages
	$paging = new Paging($counter, COUNT_PER_PAGE, "http://website.com", "page", true, true, true);

	$query = "SELECT * FROM table ".$paging->GetMysqlLimits();
	// Showing content

	// Navigation
	echo $paging->ShowNavigation();
*******************************************************************************/
