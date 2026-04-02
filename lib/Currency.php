<?php

// Class for each currency

class Currency extends CurrencyAbstract {
	// Basic information about currency
	public $conversion_rate = null;
	private $prefix = null;
	private $suffix = null;
	private $currency_code = null;
	private $name = null;
	private $round = null;

	// Constructor get information from database
	public function __construct($currency) {
		$cInfo = new CurrencyStorage($currency);
		$cInfo = $cInfo->fetch();

		$this->prefix = $cInfo->prefix;
		$this->suffix = $cInfo->suffix;
		$this->conversion_rate = $cInfo->conversion_rate;
		$this->currency_code = $cInfo->currency_code;
		$this->name = $cInfo->name;
		$this->round = $cInfo->round;
	}

	public function getName() {
		return $this->name;
	}

	// Converts base currency to this currency
	public function convertTo($base_conversion_amount, $round = true, $round2 = false) {
		$x = $base_conversion_amount / $this->conversion_rate;
		if ($round) {
			$x = round($x, 2);
		}

		if ($round2 && $this->round >= 0) {
			if (round($x) != $x) {
				$x  = ceil($x);
				$x -= $this->round;
			}
		}
		
		return $x;
	}

	// Converts this currency into base currency

	public function convertBack($currency_conversion_amount) {
		return $this->convertFrom($currency_conversion_amount);
	}

	public function convertFrom($currency_conversion_amount, $round = true) {
		$x = $currency_conversion_amount * $this->conversion_rate;
		if ($round) {
			$x = round($x, 2);
		}

		return $x;
	}

	// Formats an amount

	public function formatAmount($amount, $decimal_plates = 2, $reduce_dp = 0) {
		// Global NumberFormat for security reasons
		global $nfo;

		return $this->prefix . $nfo->format($amount, $decimal_plates, $reduce_dp) . $this->suffix;
	}

	// Get prefix
	public function getPrefix() {
		return $this->prefix;
	}

	// Get suffix
	public function getSuffix() {
		return $this->suffix;
	}
}

// Abstract class for currencies

abstract class CurrencyAbstract {
	private $prefix;
	private $suffix;
	private $conversion_rate;

	abstract public function convertTo($base_conversion_amount);

	abstract public function convertFrom($currency_conversion_amount);

	abstract public function formatAmount($amount, $decimal_plates, $reduce_dp);
}