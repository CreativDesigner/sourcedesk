<?php

// Class to validate user inputs

class Validate {

	// Check an email address
	public function email($email) {
		global $CFG, $db;

		// Check that email is not locked
		$lmSql = $db->query("SELECT email FROM blacklist_mail");
		while ($lm = $lmSql->fetch_object()) {
			if (str_replace($lm->email, "", $email) != $email) {
				return false;
			}
		}

		// Check for disposable email if activated
		if ($CFG['MOGELMAIL']) {
			$ex = explode("@", $email);
			$host = array_pop($ex);
			@$r = file_get_contents("https://trugmail.de/email/sourcedesk/" . urlencode($host));
			$json = json_decode($r, true);
			if (is_array($json) && $json['trugmail']) {
				return false;
			}

		}

		// E-Mail should be correct in syntax and should resolve to an MX record
		if ($email == "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || !checkdnsrr(explode('@', $email)[1], "MX")) {
			return false;
		}

		return true;
	}

	// Check an IP address
	public function ip($ip, $v6 = true) {
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return true;
		}

		if ($v6 && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return true;
		}

		return false;
	}

	// Check an IBAN
	public function iban($iban) {
		$iban = strtolower(str_replace(' ', '', $iban));
		if (!ctype_alnum($iban)) {
			return false;
		}

		$Countries = array('al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27, 'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24, 'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24);
		$Chars = array('a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35);

		if (strlen($iban) == $Countries[substr($iban, 0, 2)]) {

			$MovedChar = substr($iban, 4) . substr($iban, 0, 4);
			$MovedCharArray = str_split($MovedChar);
			$NewString = "";

			foreach ($MovedCharArray AS $key => $value) {
				if (!is_numeric($MovedCharArray[$key])) {
					$MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
				}
				$NewString .= $MovedCharArray[$key];
			}

			if (bcmod($NewString, '97') == 1) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

}

?>
