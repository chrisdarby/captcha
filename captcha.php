<?php

/**
 * Captcha Library and Helpers
 * 
 * @author Christopher Darby
 * @version 1.0
 * @copyright 2013, Chris Darby
 * @license http://www.gnu.org/copyleft/gpl.html GPL2
 */
class Captcha_Core {

    private $width, $height, $complexity, $challenge, $font;

    public function __construct($width = 150, $height = 40, $complexity = 4) {
	setcookie("captcha", "", time() - 3600);

	$this->width = $width;
	$this->height = $height;
	$this->complexity = $complexity;
	$this->font = "fonts/DejaVuSerif.ttf";
    }

    /**
     * Fills the background with a gradient.
     *
     * @param resource  $color1
     * @param resource  $color2
     * @param string $direction
     * @return  void
     */
    public function imageGradient($color1, $color2, $direction = NULL) {
	$directions = array('horizontal', 'vertical');

	// Pick a random direction if needed
	if (!in_array($direction, $directions)) {
	    $direction = $directions[array_rand($directions)];

	    // Switch colors
	    if (mt_rand(0, 1) === 1) {
		$temp = $color1;
		$color1 = $color2;
		$color2 = $temp;
	    }
	}

	// Extract RGB values
	$color1 = imagecolorsforindex($this->image, $color1);
	$color2 = imagecolorsforindex($this->image, $color2);

	// Prepare the gradient loop
	$steps = ($direction === 'horizontal') ? $this->width : $this->height;

	$r1 = ($color1['red'] - $color2['red']) / $steps;
	$g1 = ($color1['green'] - $color2['green']) / $steps;
	$b1 = ($color1['blue'] - $color2['blue']) / $steps;

	if ($direction === 'horizontal') {
	    $x1 = & $i;
	    $y1 = 0;
	    $x2 = & $i;
	    $y2 = $this->height;
	} else {
	    $x1 = 0;
	    $y1 = & $i;
	    $x2 = $this->width;
	    $y2 = & $i;
	}

	// Run the gradient loop
	for ($i = 0; $i <= $steps; $i++) {
	    $r2 = $color1['red'] - floor($i * $r1);
	    $g2 = $color1['green'] - floor($i * $g1);
	    $b2 = $color1['blue'] - floor($i * $b1);
	    $color = imagecolorallocate($this->image, $r2, $g2, $b2);

	    imageline($this->image, $x1, $y1, $x2, $y2, $color);
	}
    }

    public function generateImage() {
	$this->challenge = $this->generateChallenge();

	$this->createImage();
	$primary_colour = imagecolorallocate($this->image, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
	$secondary_colour = imagecolorallocate($this->image, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
	$this->imageGradient($primary_colour, $secondary_colour);

	for ($i = 0, $count = mt_rand(10, $this->complexity * 3); $i < $count; $i++) {
	    $color = imagecolorallocatealpha($this->image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(80, 120));
	    $size = mt_rand(5, $this->height / 3);
	    imagefilledellipse($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), $size, $size, $color);
	}

	$default_size = min($this->width, $this->height * 2) / strlen($this->challenge);
	$spacing = (int) ($this->width * 0.9 / strlen($this->challenge));

	// Background alphabetic character attributes
	$color_limit = mt_rand(96, 160);
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXY';

	// Draw each Captcha character with differing attributes
	for ($i = 0, $strlen = strlen($this->challenge); $i < $strlen; $i++) {

	    $font = $this->font;

	    $angle = mt_rand(-40, 20);

	    // Scale the character size to the image height
	    $size = $default_size / 10 * mt_rand(8, 12);
	    $box = imageftbbox($size, $angle, $font, $this->challenge[$i]);

	    // Calculate string and character starting coordinates
	    $x = $spacing / 4 + $i * $spacing;
	    $y = $this->height / 2 + ($box[2] - $box[5]) / 4;

	    // Set random color, size and rotation attributes to text
	    $color = imagecolorallocate($this->image, mt_rand(150, 255), mt_rand(200, 255), mt_rand(0, 255));

	    // Write text characters to image
	    imagefttext($this->image, $size, $angle, $x, $y, $color, $font, $this->challenge[$i]);

	    // Draw "ghosted" alphabetic character
	    $text_color = imagecolorallocatealpha($this->image, mt_rand($color_limit + 8, 255), mt_rand($color_limit + 8, 255), mt_rand($color_limit + 8, 255), mt_rand(70, 120));
	    $char = $chars[mt_rand(0, 13)];
	    imagettftext($this->image, $size * 2, mt_rand(-45, 45), ($x - (mt_rand(5, 10))), ($y + (mt_rand(5, 10))), $text_color, $font, $char);
	}

	// Output the image
	return $this->renderImage();
    }

    /*
     * Outputs the image to the browser
     * @return void
     */

    public function renderImage() {
	header('Content-Type: image/jpeg');

	imagejpeg($this->image);
	imagedestroy($this->image);
    }

    /**
     * Creates a new image object
     * @return void
     */
    public function createImage() {
	if (!function_exists('imagegd2')) {
	    die("Captcha requires GD2");
	}

	$this->image = imagecreatetruecolor($this->width, $this->height);
    }

    /**
     * Generate a new challenge string
     * 
     * @return string
     */
    private function generateChallenge() {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$count = mb_strlen($chars);

	for ($i = 0, $result = ''; $i < $this->complexity; $i++) {
	    $index = rand(0, $count - 1);
	    $result .= mb_substr($chars, $index, 1);
	}

	setcookie("captcha", $result);
	return $result;
    }

    /**
     * Checks if a captcha is valid
     * 
     * @param string $string
     * @return boolean
     */
    public function isCaptchaValid($string) {
	$stored = $_COOKIE["captcha"];

	if ($string == $stored) {
	    return true;
	} else {
	    return false;
	}
    }

}

/**
 * Captcha Helpers
 * 
 * @author Christopher Darby
 * @version 1.0
 * @copyright 2013, Chris Darby
 * @license http://www.gnu.org/copyleft/gpl.html GPL2
 */
class captcha {

    /**
     * Generates the Captcha Image and Challenge String
     * @return  void
     */
    public static function image() {
	$captcha = new Captcha_Core();
	$captcha->generateImage();
    }

    /**
     * Checks if the captcha response is valid
     * 
     * @param string $string
     * @return boolean
     */
    public static function valid($string) {
	$captcha = new Captcha_Core();
	return $captcha->isCaptchaValid($string);
    }

}


if (isset($_GET["render"])) {
    captcha::image();
    die;
}