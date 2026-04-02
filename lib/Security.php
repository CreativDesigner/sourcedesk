<?php

// Class for all security related things

class Security {

	// Method to hash passwords
	public static function generatePassword($length = 8, $add_dashes = false, $available_sets = 'luds') {
		$sets = array();
		if (strpos($available_sets, 'l') !== false) {
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		}

		if (strpos($available_sets, 'u') !== false) {
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		}

		if (strpos($available_sets, 'd') !== false) {
			$sets[] = '23456789';
		}

		if (strpos($available_sets, 's') !== false) {
			$sets[] = '!@#$%&*?';
		}

		$all = '';
		$password = '';
		foreach ($sets as $set) {
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}

		$all = str_split($all);
		for ($i = 0; $i < $length - count($sets); $i++) {
			$password .= $all[array_rand($all)];
		}

		$password = str_shuffle($password);

		if (!$add_dashes) {
			return $password;
		}

		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while (strlen($password) > $dash_len) {
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}

	// Method to generate salt

	public static function generateSalt() {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$count = mb_strlen($chars);

		for ($i = 0, $result = ''; $i < 20; $i++) {
			$index = rand(0, $count - 1);
			$result .= mb_substr($chars, $index, 1);
		}

		return $result;
	}

	// Method to hash admin passwords
	public final function adminHash($p, $salt = "", $hashed = false) {
		global $CFG;
		return $this->hash($p, $salt, $hashed, $CFG['HASH_METHOD_ADMIN']);
	}

	// Method to hash admin passwords like client hash would do
	public final function adminHashClient($p) {
		global $CFG;

		// Case analysis for different hash methods
		switch ($CFG['HASH_METHOD_ADMIN']) {
		case 'md5':
			return md5($p);
			break;

		case 'sha1':
			return sha1($p);
			break;

		case 'sha256':
			return hash("sha256", $p);
			break;

		case 'sha512':
			return hash("sha512", $p);
			break;

		case 'sha512salt':
			return hash("sha512", $p);
			break;

		// Nothing of these or plain
		default:
			return $p;
		}
	}

	// Method to generate password

	public final function hash($p, $salt = "", $hashed = false, $method = null) {
		// Global @var CFG for security reasons
		global $CFG;

		// Case analysis for different hash methods
		switch ($method ?: $CFG['HASH_METHOD']) {
		case 'md5':
			return $hashed ? $p : md5($p);
			break;

		case 'sha1':
			return $hashed ? $p : sha1($p);
			break;

		case 'sha256':
			return $hashed ? $p : hash("sha256", $p);
			break;

		case 'sha512':
			return $hashed ? $p : hash("sha512", $p);
			break;

		case 'sha512salt':
			if (!$hashed) {
				$p = hash("sha512", $p);
			}

			return hash("sha512", $CFG['GLOBAL_SALT'] . $p . $salt);
			break;

		// Nothing of these or plain
		default:
			return $p;
		}
	}

}

function encrypt($text) {
	global $CFG;
	if (trim($text) === "") {
		return "";
	}

	return AesCtr::encrypt($text, isset($CFG['HASH']) ? $CFG['HASH'] : "", 256);
}

function decrypt($text) {
	global $CFG;
	if (trim($text) === "") {
		return "";
	}

	return AesCtr::decrypt($text, isset($CFG['HASH']) ? $CFG['HASH'] : "", 256);
}