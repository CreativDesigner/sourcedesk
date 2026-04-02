<?php

// Class for handling captcha request

abstract class CaptchaModel {
	public static function getCaptcha() {
	}

	public static function verifyCaptcha() {
	}
}

class noneCaptcha extends CaptchaModel {
	public static function getCaptcha() {
		return false;
	}

	public static function verifyCaptcha() {
		return true;
	}
}

class calcCaptcha extends CaptchaModel {
	public static function verifyCaptcha() {
		global $_POST, $session;

		if (isset($_POST['captcha']) && $_POST['captcha'] == $session->get('captcha') && calcCaptcha::getCaptcha() !== false) {
			return true;
		}

		return false;
	}

	public static function getCaptcha() {
		global $session, $lang;

		$n1 = mt_rand(0, 15);
		$n2 = mt_rand(0, 15);
		$op = mt_rand(0, 2);
		$er = 0;

		if ($op == 0) {
			$er = $n1 + $n2;
			$captcha = $n1 . " + " . $n2;
		} else if ($op == 1) {
			$er = $n1 - $n2;
			$captcha = $n1 . " - " . $n2;
		} else if ($op == 2) {
			$er = $n1 * $n2;
			$captcha = $n1 . " * " . $n2;
		} else if ($op == 3) {
			while ($n1 % $n2 != 0) {
				$n1++;
			}

			$er = $n1 / $n2;
			$captcha = $n1 . " / " . $n2;
		}

		if ($er == 0) {
			calcCaptcha::getCaptcha();
		} else {
			$session->set('captcha', $er);
			return Array("type" => "text", "value" => str_replace("%c", $captcha, $lang['REGISTER']['CAPTCHA']));
		}
	}
}

class reCaptchaInvisible extends CaptchaModel {
	public static function getCaptcha() {
		global $CFG;
		$code = "<script type='text/javascript'>
        function reCaCa(key){
		  $('.captcha-form').unbind('submit');
          $('.captcha-form').submit();
        }
        </script>";
		$code .= '<div class="g-recaptcha" data-sitekey="' . $CFG['RECAPTCHA_PUBLIC'] . '" data-callback="reCaCa" data-size="invisible" style="display: none;"></div>';
		$code .= "<script src='https://www.google.com/recaptcha/api.js'></script>";

		return Array("type" => "code", "value" => $code, "exec" => "grecaptcha.execute(); return false;");
	}

	public static function verifyCaptcha() {
		global $_POST, $CFG;

		if (!isset($_POST['g-recaptcha-response']) || !is_string($_POST['g-recaptcha-response'])) {
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_POST, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "secret=" . urlencode($CFG['RECAPTCHA_PRIVATE']) . "&response=" . urlencode($_POST['g-recaptcha-response']));
		$result = curl_exec($ch);
		curl_close($ch);

		if (!json_decode($result)->success) {
			return false;
		}

		return true;
	}
}

class reCaptcha extends CaptchaModel {
	public static function getCaptcha() {
		global $CFG;

		$code = "<script src='https://www.google.com/recaptcha/api.js'></script>";
		$code .= '<center><div class="g-recaptcha" data-sitekey="' . $CFG['RECAPTCHA_PUBLIC'] . '"></div></center><br />';

		return Array("type" => "modal", "value" => $code);
	}

	public static function verifyCaptcha() {
		global $_POST, $CFG;

		if (!isset($_POST['g-recaptcha-response']) || !is_string($_POST['g-recaptcha-response'])) {
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_POST, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "secret=" . urlencode($CFG['RECAPTCHA_PRIVATE']) . "&response=" . urlencode($_POST['g-recaptcha-response']));
		$result = curl_exec($ch);
		curl_close($ch);

		if (!json_decode($result)->success) {
			return false;
		}

		return true;
	}
}

class Captcha {
	protected $available = Array("noneCaptcha", "calcCaptcha", "reCaptcha", "reCaptchaInvisible");
	protected $default = 'calcCaptcha';
	protected $class;

	public function __construct() {
		global $CFG;

		$captchaType = $CFG['CAPTCHA_TYPE'];
		if (!class_exists($captchaType) || !is_subclass_of($captchaType, 'CaptchaModel')) {
			$captchaType = $this->default;
		}

		$this->class = $captchaType;
	}

	public function getAvailable() {
		foreach ($this->available as $k => $class) {
			if (!class_exists($class)) {
				unset($this->available[$k]);
			}
		}

		return $this->available;
	}

	public function get() {
		$className = $this->class;
		return $className::getCaptcha();
	}

	public function verify() {
		$className = $this->class;
		return $className::verifyCaptcha();
	}

	public function getDefault() {
		return $this->default;
	}
}