<?php

// Class for formating numbers

class NumberFormat {

	private $nfo = null;

	// Constructor gets format
	public function __construct() {
		// Global @var CFG for security reasons
		global $CFG;

		$this->nfo = $CFG['NUMBER_FORMAT'];
	}

	// Method to format number
	// @var int: number (float, int)
	// @var dp: decimal places
	// @var return_int: set to 1 for give back a commaless number if it is an integer
	// @var format: overwrites the default format

	public function format_smarty($params) {
		return $this->format(isset($params["i"]) ? $params["i"] : 0, isset($params['d']) ? $params['d'] : 2, isset($params['r']) ? $params['r'] : 0);
	}

	// Method to format number from Smarty

	public function format($int, $dp = 2, $return_int = 0, $format = "") {
		$int = floatval($int);
		$format = !empty($format) && is_string($format) ? $format : $this->nfo;
		switch ($format) {
		case 'de2':
			$f = number_format($int, $dp, ',', '');
			$e = explode(",", $f);
			break;

		case 'us':
			$f = number_format($int, $dp, '.', ',');
			$e = explode(".", $f);
			break;

		case 'us2':
			$f = number_format($int, $dp, '.', '');
			$e = explode(".", $f);
			break;

		default:
			$f = number_format($int, $dp, ',', '.');
			$e = explode(",", $f);
		}

		if ($return_int && $dp > 0 && count($e) == 2 && $e[1] == 0) {
			return $e[0];
		}

		return $f;
	}

	// Method to modify number that PHP can understand it
	// @var int: number to modify

	public function phpize($int) {
		switch ($this->nfo) {
		case 'de2':
			return str_replace(",", ".", $int);
			break;

		case 'us':
		case 'us2':
			return $int;
			break;

		default:
			return str_replace(Array('.', ','), Array('', '.'), $int);
		}
	}

	// Method to generate a placeholder
	public function placeholder() {
		return $this->format(100, 2, 0);
	}

}

?>