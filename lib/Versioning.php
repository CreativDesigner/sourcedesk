<?php

// Class for version management of shop system

class Versioning {
	// Tell the actual version installed
	static public function actualVersion() {
		global $db, $CFG;

		if ($CFG['MASTER']) {
			$res = $CFG['VERSION'];
		} else if (!($res = file_get_contents("https://sourceway.de/issue"))) {
			return false;
		}

		// This is a hard-codened URL of shop system development company
		$db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($res) . "' WHERE `key` = 'actual_version' LIMIT 1");
		$db->query("UPDATE settings SET `value` = '" . time() . "' WHERE `key` = 'last_version_update' LIMIT 1");

		return $res;
	}

	// Check if the provided version is higher

	static public function newerVersion() {
		global $CFG;

		if (self::isHigher($CFG['ACTUAL_VERSION'])) {
			return true;
		}

		return false;
	}

	// Check for the actual version of shop system (only processed by daily cron)

	static public function isHigher($version) {
		return strcmp($version, self::getVersion()) > 0;
	}

	// Check if there is any newer version

	static public function getVersion() {
		global $CFG;
		return $CFG['VERSION'];
	}
}

?>