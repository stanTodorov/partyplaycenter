<?php

/**
* Shop Cart functionallity (+ $_SESSION)
* Require: PHP 5.3+
*
* @author Mikhail Kysoev <mialygk@gmail.com>
* @version 1.1
* @copyright Feb 2011, Jan 2012
* @license BSD-new
*/

class Cart {
	private $cart = array();        // cart container
	private $name = '';             // name of cart variable name
	private $price = 0.00;          // total price of all items
	private $count = 0;             // total count of items
	private $id = null;             // integer product id only
	private $item = null;           // array index in cart (id + attributes)
	private $attributes = array();  // attributes array

	/**
	 * Construction
	 * Load Cart from Session (if exists)
	 *
	 * @param string $name Session name
	 */
	public function __construct($name = 'Cart')
	{
		if (mb_strlen($name) >= 0) {
			$this->name = $name;
			$this->load();
		}
	}

	/**
	 * Save current Cart
	 */
	public function __destruct()
	{
		$this->save();
	}

	public function __set($name, $value)
	{
		throw new Exception("Invalid property: ".$name);
	}

	public function __get($name)
	{
		throw new Exception("Invalid property: ".$name);
	}

	/**
	 * Load cart from session
	 *
	 */
	private function load()
	{
		if (!isset($_SESSION[$this->name])) {
			$_SESSION[$this->name] = array();
		} else {
			$this->cart = $_SESSION[$this->name];
		}

		$this->calcTotalPrice();
	}

	/**
	 * Save cart to session
	 */
	private function save()
	{
		$_SESSION[$this->name] = $this->cart;

	}

	/**
	 * select product
	 *
	 * @param integer $id Product ID
	 * @param array $attributes Additional attributes for product
	 * @return object Instance of class
	 */
	public function product($id, $attributes = array())
	{
		$id = intval($id);
		$item = $id;

		if (count($attributes)) {
			// json-like attributes for id key
			foreach ($attributes as $attribute => $option) {
				$item .= ',' . $attribute . ':' . $option;
			}
		}

		$this->id = $id;
		$this->item = $item;
		$this->attributes = $attributes;

		return $this;
	}

	/**
	 * select product by item name
	 *
	 * @param integer $id Product ID
	 * @return object Instance of class
	 */
	public function item($item)
	{
		if (isset($this->cart[$item])) {
			$this->item = $item;
			$this->id = $this->cart[$item]['id'];
			$this->attributes = $this->cart[$item]['attributes'];
		}

		return $this;
	}


	/**
	 * get current product id
	 *
	 * @return object Instance of class
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Add new item to Cart
	 *
	 * @param float $price Price per single product
	 * @param integer $qty Quantity to increment
	 * @param integer $pMaxQty Max Quantity of product (-1 means infinity)
	 * @return object Instance of class
	 */
	public function add($price, $qty = 1, $pMaxQty = -1)
	{
		$item = $this->item;
		if ($item === null) return false;

		$price = floatval(str_replace(',', '.', $price));

		// increment count of existing product
		if (isset($this->cart[$item])) {
			$this->cart[$item]['qty'] += $qty;

			if ($this->cart[$item]['qty'] > $pMaxQty) {
				$this->cart[$item]['qty'] = $pMaxQty;
			}

			if ($price != $this->cart[$item]['price']) {
				$this->cart[$item]['price'] = $price;
			}

			$this->calcTotalPrice();
			return $this;
		}

		// add new product to cart
		$this->cart[$item] = array(
			'id' => $this->id,
			'qty' => (($qty > $pMaxQty) ? $pMaxQty : $qty),
			'price' => $price,
			'attributes' => $this->attributes
		);

		$this->calcTotalPrice();
		return $this;
	}


	/**
	 * Get quantity of given article id
	 *
	 * @return integer Qty of product in cart, or 0 if product missing
	 */
	function getQty()
	{
		$item = $this->item;
		if ($item === null) return false;

		if (isset($this->cart[$item])) {
			return $this->cart[$item]['qty'];
		}

		return 0;
	}


	/**
	 * Set exact quantity of product
	 *
	 * @param integer $qty Quantity of product
	 * @param integer $pMaxQty Max Quantity of product (-1 means infinity)
	 * @return object Instance of class
	 */
	function setQty($qty, $pMaxQty = -1)
	{
		$item = $this->item;
		if ($item === null) return false;

		if ($qty > $pMaxQty && $pMaxQty >= 0) {
			$qty = $pMaxQty;
		}

		if ($qty <= 0 || !isset($this->cart[$item])) {
			unset($this->cart[$item]);
			$this->calcTotalPrice();
			return;
		}

		$this->cart[$item]['qty'] = $qty;
		$this->calcTotalPrice();

		return $this;
	}


	/**
	 * Remove existing item from Cart
	 *
	 * @param integer $qty Number of items to remove; default value remove all items
	 * @return bool True on success, False on error
	 */
	public function remove($qty = -1)
	{
		$item = $this->item;
		if ($item === null) return false;

		$qty = intval($qty);

		$removeAll = ($qty < 0) ? true : false;

		if (!isset($this->cart[$item])) {
			return true;
		}

		$total = $this->cart[$item]['qty'];
		$total -= $qty;

		if ($total > 0 && !$removeAll) {
			$this->cart[$item]['qty'] = $total;
			$this->calcTotalPrice();
			return true;
		}

		unset($this->cart[$item]);
		$this->id = null;
		$this->item = null;
		$this->attributes = null;

		$this->calcTotalPrice();
		return true;
	}

	/**
	 * Remove all items by product id
	 *
	 * @return bool True on success, False on error
	 */
	public function removeAll()
	{
		$this->cart = array();
		$this->calcTotalPrice();
		return true;
	}

	/**
	 * Get Price of all products in cart
	 *
	 * @return integer Price
	 */
	public function getPrice()
	{
		return $this->price;
	}


	/**
	 * Get All Cart content as Array
	 *
	 * @return array
	 */
	public function getCart()
	{

		return $this->cart;
	}


	/**
	 * Get Count of articles in cart
	 *
	 * @return integer
	 */
	public function getItemCount()
	{
		return $this->count;
	}


	private function calcTotalPrice()
	{

		$total = 0;
		$count = 0;

		if (empty($this->cart)) {
			$this->price = $total;
			return;
		}

		foreach ($this->cart as $id => $product) {
			$count += $product['qty'];
			$total += ($product['qty'] * $product['price']);
		}

		$this->price = $total;
		$this->count = $count;
	}

	/**
	 * Debug Cart, ie show array of cart
	 */
	public function debug()
	{
		echo '<pre>';
		print_r($this->cart);
		echo '</pre>';
	}

}