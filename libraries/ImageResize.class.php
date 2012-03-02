<?php
/**
 * Image resizing with GD extension.
 * Source code is based on Jarrod Oberto's resize_class.php
 * Require PHP5
 *
 * @author           Mikhail Kyosev <mialygk@gmail.com>
 * @version          1.0
 * @copyright        2011
 * @license          BSD-new
 */

// exceptions
class ImageResizeExGDUnavailable extends Exception {};
class ImageResizeExIFNotFound extends Exception {};
class ImageResizeExIFInvalidFormat extends Exception {};
class ImageResizeExOFInvalidFormat extends Exception {};
class ImageResizeExDynamicProperty extends Exception {};

class ImageResize {

	// image types
	const FORMAT_JPG = 0x0001;
	const FORMAT_GIF = 0x0002;
	const FORMAT_PNG = 0x0004;

	// errors
	const ERR_IF_INVALID_FORMAT = 0x0001; // input (source) file - invalid format
	const ERR_IF_NOT_FOUND      = 0x0002; // input (source) file not found
	const ERR_GD_UNAVAILABLE    = 0x0004; // GD library is not loaded
	const ERR_OF_INVALID_FORMAT = 0x0008; // output (dest) file - invalid format
	const ERR_DYNAMIC_PROPERTY  = 0x0010; // access to non-defined property or method

	// properties
	private $_image;
	private $_type;
	private $_width;
	private $_height;
	private $_output;

	/**
	 * Initialize class and check for GD
	 *
	 * @param string $fileName Path and image's filename to open
	 */
	function __construct($fileName)
	{
		if (!extension_loaded('gd')) {
			throw new ImageResizeExGDUnavailable('GD Extension Not Loaded', self::ERR_GD_UNAVAILABLE);
		}
		$this->_image = $this->_openImage($fileName);
		$this->_width = @imagesx($this->_image);
		$this->_height = @imagesy($this->_image);

		$this->_output = false;
	}

	/**
	 * Destroy output image if exists
	 */
	function __destruct()
	{
		if ($this->_output === false) return;

		imagedestroy($this->_output);
	}

	/**
	 * Disable dynamic properties
	 *
	 * @param string $name Name of property
	 */
	function __get($name)
	{
		throw new ImageResizeExDynamicProperty('Dynamic property not allowed: '.$name, self::ERR_DYNAMIC_PROPERTY);
	}

	/**
	 * Disable dynamic properties
	 *
	 * @param string $name Name of property
	 * @param mied $value Value of property
	 */
	function __set($name, $value)
	{
		throw new ImageResizeExDynamicProperty('Dynamic property not allowed: '.$name, self::ERR_DYNAMIC_PROPERTY);
	}

	/**
	 * Resize opened image
	 *
	 * @param integer $newWidth New width of image
	 * @param integer $newHeight New height of image
	 * @param string $option How to be converted
	 *     (auto, portrait, landspace, crop or exact)
	 * @param mixed $color Color for background (HTML #rrggbb, css rgba() or array)
	 */
	public function resize($newWidth, $newHeight, $option = 'auto', $color = null)
	{
		list($width, $height) = $this->_getDimensions($newWidth, $newHeight, $option);

		$this->_output = @imagecreatetruecolor($width, $height);


		if ($color !== null) {
			// Add background color
			imagefilledrectangle($this->_output, 0, 0, $width, $height, $this->_parseColorValue($color));
		}
		else if ($this->_type === self::FORMAT_PNG) {
			// For PNG we must save alpha channel :)
			imagealphablending($this->_output, false);
			imagesavealpha($this->_output, true);
		}


		// Resize source to destination image
		imagecopyresampled($this->_output, $this->_image, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);

		// excat crop by given size
		if ($option == 'crop' || $option == 'c') {
			$this->_crop($width, $height, $newWidth, $newHeight);
		}
	}

	/**
	 * Print output (resized or source) file directly to browser
	 *
	 * @param integer $quality JPEG's quality (0 to 100) or PNG's compression ratio (0 to 9)
	 * @param integer $type Destination file type
	 */
	public function output($quality = 100, $type = null)
	{
		// by default image is source
		$image = $this->_image;

		// resized image?
		if ($this->_output !== false) {
			$image = $this->_output;
		}

		if ($type === null) {
			$type = $this->_type;
		}

		switch($type) {
		case self::FORMAT_PNG:
			// PNG translate quality to compression ratio
			$quality = $this->_invertQualityPNG($quality);

			if (imagetypes() & IMG_PNG) {
				header('Last-Modified: '.date('r'));
				header('Content-Type: image/png');
				imagepng($image, null, $quality);
			}

			break;

		case self::FORMAT_GIF: // GIF doen't have quality ratio
			if (imagetypes() & IMG_GIF) {
				header('Last-Modified: '.date('r'));
				header('Content-Type: image/gif');
				imagegif($image, null);
			}

			break;

		case self::FORMAT_JPG:
			if (imagetypes() & IMG_JPG) {
				header('Last-Modified: '.date('r'));
				header('Content-Type: image/jpeg');
				imagejpeg($image, null, $quality);
			}

			break;
		default:
			throw new ImageResizeExOFInvalidFormat('Unsupported format', self::ERR_OF_INVALID_FORMAT);
			break;
		}
	}

	/**
	 * Print output (resized or source) file directly to browser
	 *
	 * @param string $savePath Path and Filename for destination image
	 * @param integer $quality JPEG's quality (0 to 100) or PNG's compression ratio (0 to 9)
	 */
	public function save($savePath, $quality = 100)
	{
		$ext = ltrim(strtolower(strrchr($savePath, '.')), '.');
		$image = $this->_image;

		if ($this->_output !== false) {
			$image = $this->_output;
		}

		switch($ext) {
		case 'jpg':
		case 'jpeg':
			if (imagetypes() & IMG_JPG) {
				imagejpeg($image, $savePath, $quality);
			}
			break;

		case 'gif':
			if (imagetypes() & IMG_GIF) {
				imagegif($image, $savePath);
			}
			break;

		case 'png':
			$quality = $this->_invertQualityPNG($quality);

			if (imagetypes() & IMG_PNG) {
				imagepng($image, $savePath, $quality);
			}
			break;
		default:
			throw new ImageResizeExOFInvalidFormat('Unsupported format', self::ERR_OF_INVALID_FORMAT);
			break;
		}
	}

	/**
	 * Open Image file
	 *
	 * @param string $filename (with path)
	 */
	private function _openImage($file)
	{
		$img = '';
		$info = @getimagesize($file);
		$format = $info[2];

		if (!is_file($file)) {
			throw new ImageResizeExIFNotFound('File not found', self::ERR_IF_NOT_FOUND);
		}

		switch($format) {
		case IMAGETYPE_JPEG:
			$img = @imagecreatefromjpeg($file);
			$this->_type = self::FORMAT_JPG;
			break;
		case IMAGETYPE_GIF:
			$img = @imagecreatefromgif($file);
			$this->_type = self::FORMAT_GIF;
			break;
		case IMAGETYPE_PNG:
			$img = @imagecreatefrompng($file);
			$this->_type = self::FORMAT_PNG;
			break;
		default:
			throw new ImageResizeExIFInvalidFormat('Invalid Image Format', self::ERR_IF_INVALID_FORMAT);
		}
		return $img;
	}

	/**
	 * Get usuble image dimensions depending of $option parameter
	 *
	 * @param integer $newWidth request image width
	 * @param integer $newHeight request image height
	 * @param integer $option How to be calucalate new sizes:
	 *   [portrait|p]: fixed height, variable width
	 *   [landscape|l]: fixed width, variable height
	 *   [exact|e]: exact width and height (no calculation)
	 *   [crop|c]: specify optimal width and height to crop image
	 *   [auto|a]: determing which side is higher (default)
	 */
	private function _getDimensions($newWidth, $newHeight, $option)
	{

		switch ($option) {
		case 'exact':
		case 'e':
			$width = $newWidth;
			$height = $newHeight;
			break;
		case 'portrait':
		case 'p':
			$width = $this->_getSizeByFixedHeight($newHeight);
			$height = $newHeight;
			break;
		case 'landscape':
		case 'l':
			$width = $newWidth;
			$height = $this->_getSizeByFixedWidth($newWidth);
			break;
		case 'crop':
		case 'c':
			list($width, $height) = $this->_getOptimalCrop($newWidth, $newHeight);
			break;
		case 'auto':
		case 'a':
		default:
			list($width, $height) = $this->_getSizeByAuto($newWidth, $newHeight);
			break;
		}

		return array($width, $height);
	}

	/**
	 * Get Width by fixed Height
	 *
	 * @return integer Width
	 */
	private function _getSizeByFixedHeight($newHeight)
	{
		$ratio = $this->_width / $this->_height;
		return $newHeight * $ratio;
	}

	/**
	 * Get Height by fixed Width
	 *
	 * @return integer Height
	 */
	private function _getSizeByFixedWidth($newWidth)
	{
		$ratio = $this->_height / $this->_width;
		return $newWidth * $ratio;
	}

	/**
	 * Get optimal dimensions for Auto mode (highest side of image are fixed)
	 *
	 * @return array Width and Height
	 */
	private function _getSizeByAuto($newWidth, $newHeight)
	{
		if ($this->_height < $this->_width) {
			$width = $newWidth;
			$height = $this->_getSizeByFixedWidth($newWidth);
		}
		elseif ($this->_height > $this->_width) {
			$width = $this->_getSizeByFixedHeight($newHeight);
			$height = $newHeight;
		}
		else {
			if ($newHeight < $newWidth) {
				$width = $newWidth;
				$height = $this->_getSizeByFixedWidth($newWidth);
			} else if ($newHeight > $newWidth) {
				$width = $this->_getSizeByFixedHeight($newHeight);
				$height = $newHeight;
			} else {
				$width = $newWidth;
				$height = $newHeight;
			}
		}

		return array($width, $height);
	}

	/**
	 * Get optimal dimensions for cropping
	 *
	 * @return array Width and Height
	 */
	private function _getOptimalCrop($newWidth, $newHeight)
	{

		$hRatio = $this->_height / $newHeight;
		$wRatio = $this->_width / $newWidth;
		$ratio = $wRatio;

		if ($hRatio < $wRatio) {
			$ratio = $hRatio;
		}

		$width  = $this->_width  / $ratio;
		$height = $this->_height / $ratio;

		return array($width, $height);
	}

	/**
	 * Crop image by given width and height
	 *
	 * @param integer $optimalWidth
	 * @param integer $optimalHeight
	 * @param integer $newWidth
	 * @param integer $newHeight
	 */
	private function _crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
	{
		$cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
		$cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

		$crop = $this->_output;

		$this->_output = imagecreatetruecolor($newWidth , $newHeight);
		imagecopyresampled($this->_output, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
	}


	/**
	 * Fix JPEG's quality (from 0 to 100) to PNG compression ratio (0 to 9)
	 *
	 * @param integer $quality
	 * @return integer PNG compression ratio
	 */
	private function _invertQualityPNG($quality = 100)
	{
		return 9 - round(($quality/100) * 9);
	}

	/**
	 * Parse given color by $color (as array, HTML color or css rgb/rgba)
	 *
	 * @param mixed $color Color for background
	 * @return integer Color, based on output image type
	 */
	private function _parseColorValue($color)
	{
		$hasAlphaChannel = false;
		$floatPrecision = 0.001;

		// Regular Expressions to Match/Replace
		$regexHex       = '/[^a-f0-9]+/i';
		$regexHexFull   = '/^[#]*[a-f0-9]{6}$/i';
		$regexHexShort  = '/^[#]*[a-f0-9]{3}$/i';
		$regexRGB       = '/rgb\(([\d ]+),([\d ]+),([\d ]+)\)/i';
		$regexRGBA      = '/rgba\(([\d ]+),([\d ]+),([\d ]+),';
		$regexRGBA     .= '[^\d,]*([01]{1}[\d\.]*[^\d]*)\)/i';

		// 17 standard css color names
		$colors = array(
			'aqua'     => array(   0, 255, 255 ),
			'black'    => array(   0,   0,   0 ),
			'blue'     => array(   0,   0, 255 ),
			'fuchsia'  => array( 255,   0, 255 ),
			'gray'     => array( 128, 128, 128 ),
			'grey'     => array( 128, 128, 128 ),
			'green'    => array(   0, 128,   0 ),
			'lime'     => array(   0, 255,   0 ),
			'maroon'   => array( 128,   0,   0 ),
			'navy'     => array(   0,   0, 128 ),
			'olive'    => array( 128, 128,   0 ),
			'purple'   => array( 128,   0, 128 ),
			'red'      => array( 255,   0,   0 ),
			'silver'   => array( 192, 192, 192 ),
			'teal'     => array(   0, 128, 128 ),
			'white'    => array( 255, 255, 255 ),
			'yellow'   => array( 255, 255,   0 )
		);

		if (is_array($color) && isset($color['red'], $color['green'], $color['blue'])) {
			// array('red' => red, 'blue' => blue, 'green' => green)

			$r = (preg_match('/[a-f]+/i', $color['red']))
				? hexdec($color['red'])
				: intval($color['red']);

			$g = (preg_match('/[a-f]+/i', $color['green']))
				? hexdec($color['green'])
				: intval($color['green']);

			$b = (preg_match('/[a-f]+/i', $color['blue']))
				? hexdec($color['blue'])
				: intval($color['blue']);

		}
		else if (is_array($color) && count($color) == 3) {
			// array('red', 'green', 'blue')

			list($r, $g, $b) = $color;

			$r = (preg_match('/[a-f]+/i', $r)) ? hexdec($r) : intval($r);
			$g = (preg_match('/[a-f]+/i', $g)) ? hexdec($g) : intval($g);
			$b = (preg_match('/[a-f]+/i', $b)) ? hexdec($b) : intval($b);
		}
		else if (is_string($color) && trim($color) === 'transparent') {
			list($r, $g, $b, $a) = array(0, 0, 0, 127);
			$hasAlphaChannel = true;
		}
		else if (is_string($color) && isset($colors[mb_strtolower(trim($color))]) ) {
			// css color names, ie red, blue, green, etc.

			list($r, $g, $b) = $colors[mb_strtolower(trim($color))];
		}
		else if (is_string($color) && preg_match($regexHexFull, $color)) {
			// HTML/CSS style: #rrggbb
			$color = preg_replace($regexHex, '', $color);
			$r = hexdec(substr($color, 0, 2));
			$g = hexdec(substr($color, 2, 2));
			$b = hexdec(substr($color, 4, 2));
		}
		else if (is_string($color) && preg_match($regexHexShort, $color)) {
			// HTML/CSS style: #rgb

			$color = preg_replace($regexHex, '', $color);
			$r = substr($color, 0, 1);
			$g = substr($color, 1, 1);
			$b = substr($color, 2, 1);

			$r = hexdec($r.$r);
			$g = hexdec($g.$g);
			$b = hexdec($b.$b);
		}
		else if (is_string($color) && preg_match($regexRGB, $color, $match)) {
			// css style: rgb(red, blue, green)

			$r = intval($match[1]);
			$g = intval($match[2]);
			$b = intval($match[3]);
		}
		else if (is_string($color) && preg_match($regexRGBA, $color, $match)) {
			// css3 style: rgba(red, blue, green, alpha)

			$r = intval($match[1]);
			$g = intval($match[2]);
			$b = intval($match[3]);
			$opacity = floatval($match[4]);

			if ((abs($opacity) - 0.001) < $floatPrecision) {
				$opacity = 0;
			}
			else if ((abs($opacity) - 1.000) < $floatPrecision) {
				$opacity = 1;
			}

			$a = intval(127 - round($opacity * 127), 10);
			$hasAlphaChannel = true;
		}
		else {
			return null;
		}

		$r = ($r < 0) ? 0 : (($r > 255) ? 255 : $r);
		$g = ($g < 0) ? 0 : (($g > 255) ? 255 : $g);
		$b = ($b < 0) ? 0 : (($b > 255) ? 255 : $b);

		if ($hasAlphaChannel === true) {
			return imagecolorallocatealpha($this->_output, $r, $g, $b, $a);
		}

		return imagecolorallocate($this->_output, $r, $g, $b);
	}
}

################################################################################
# Usage
/*******************************************************************************

// Resize image and save to file

$filename = 'myImage.png';
$output = 'uploads/';
$quality = 100; // 100% quality (best), for PNG => compression 9 (max)

try {
	$img = new ImageResize($filename);
	$img->resize($width, $height, 'auto', "rgba(0, 0, 0, 0)");
	$img->save($output.$filename, 100);
	@unlink($filename);
}
catch(ImageResizeExIFInvalidFormat $e) {
	echo "Invalid input image format!";
}
catch(Exception $e) {
	echo $e->getMessage();
}

////////////////////////////////////////////////////////////////////////////////

// Resize image and output to browser

$filename = 'myImage.png';
$quality = 100; // 100% quality (best)

try {
	$img = new ImageResize($filename);
	$img->resize($width, $height, 'auto', "rgb(255, 255, 255)");
	$img->output(100, ImageResize::FORMAT_JPG);
	exit;
}
catch(ImageResizeExIFInvalidFormat $e) {
	echo "Invalid input image format!";
}
catch(ImageResizeExOFInvalidFormat $e) {
	echo "Invalid output image format!";
}
catch(Exception $e) {
	echo $e->getMessage();
}


*******************************************************************************/