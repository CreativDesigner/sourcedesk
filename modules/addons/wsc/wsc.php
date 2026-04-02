<?php
// Addon for adding customer group to WSC users

class WSCGroup extends Addon {
	public static $shortName = "wsc";

	public function __construct($language) {
		$this->language = $language;
		$this->name = self::$shortName;
		parent::__construct();

		if (!include (__DIR__ . "/language/$language.php")) {
			throw new ModuleException();
		}

		if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
			throw new ModuleException();
		}

		$this->lang = $addonlang;

		$this->info = Array(
			'name' => $addonlang["NAME"],
			'version' => "1.0",
			'company' => "sourceWAY.de",
			'url' => "https://sourceway.de/",
		);
	}

	public function delete() {
		return $this->deleteDir(realpath(__DIR__));
	}

	public function getSettings() {
		return Array(
			"forum_url" => Array("placeholder" => "https://forum.sourceway.de", "label" => "Foren-URL", "type" => "text"),
			"acp_url" => Array("placeholder" => "https://forum.sourceway.de/app/acp", "label" => "ACP-URL", "type" => "text"),
			"acp_user" => Array("placeholder" => "admin", "label" => "ACP-Benutzername", "type" => "text"),
			"acp_password" => Array("placeholder" => "kL19Ok2Q", "label" => "ACP-Passwort", "type" => "password"),
			"db_host" => Array("placeholder" => "localhost", "label" => "Datenbank-Host", "type" => "text", "default" => "localhost"),
			"db_user" => Array("placeholder" => "root", "label" => "Datenbank-Benutzer", "type" => "text"),
			"db_password" => Array("placeholder" => "kL19Ok2Q", "label" => "Datenbank-Passwort", "type" => "password"),
			"db_name" => Array("placeholder" => "forum", "label" => "Datenbank", "type" => "text"),
			"db_prefix" => Array("placeholder" => "wcf1_", "label" => "Datenbank-Präfíx", "type" => "text"),
			"group_id" => Array("placeholder" => "7", "label" => "Gruppen-ID", "type" => "text"),
			"field_id" => Array("placeholder" => "30", "label" => "Kundennummer: Feld-ID", "type" => "text"),
		);
	}

	public function clientPages() {
		return Array(
			"wsc" => "clientPage",
		);
	}

	public function clientPage() {
		global $title, $tpl, $var, $lang, $user;

		User::status();

		$var['username'] = false;
		$db = new MySQLi($this->options['db_host'], $this->options['db_user'], $this->options['db_password'], $this->options['db_name']);
		if ($db->connect_errno) {
			$title = $lang['ERROR']['TITLE'];
			$var['debug'] = $db->connect_error;
			$tpl = "error";
			return;
		}

		$sql = $db->query("SELECT userID FROM " . $this->options['db_prefix'] . "user_option_value WHERE userOption" . $this->options['field_id'] . " = " . $user->get()['ID']);
		if ($sql->num_rows == 1) {
			$uid = $sql->fetch_object()->userID;
			$sql = $db->query("SELECT username FROM " . $this->options['db_prefix'] . "user WHERE userID = $uid");
			if ($sql->num_rows == 1) {
				$var['username'] = $sql->fetch_object()->username;
			}

		}

		if (isset($_POST['assign']) && !$var['username']) {
			$sql = $db->query("SELECT userID FROM " . $this->options['db_prefix'] . "user WHERE username = '" . $db->real_escape_string($_POST['assign']) . "'");
			if ($sql->num_rows == 1) {
				$uid = $sql->fetch_object()->userID;

				$sql = $db->query("SELECT userOption" . $this->options['field_id'] . " AS o FROM " . $this->options['db_prefix'] . "user_option_value WHERE userID = " . $uid);
				if ($sql->num_rows == 1 && !empty($sql->fetch_object()->o)) {
					$var['error'] = $this->getLang('E2');
				} else {
					$db->query("UPDATE " . $this->options['db_prefix'] . "user_option_value SET userOption" . $this->options['field_id'] . " = " . $user->get()['ID'] . " WHERE userID = " . $uid);

					if (file_exists(__DIR__ . "/.cookie")) {
						unlink(__DIR__ . "/.cookie");
					}

					touch(__DIR__ . "/.cookie");

					$ch = curl_init($this->options['acp_url'] . "/index.php?login/");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36"));
					curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . "/.cookie");
					curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . "/.cookie");

					$res = curl_exec($ch);

					$search = '<input type="hidden" name="t" value="';
					$start = strpos($res, $search);
					if ($start === false) {
						return false;
					}

					$token = substr($res, $start + strlen($search), 40);

					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
						"username" => $this->options['acp_user'],
						"password" => $this->options['acp_password'],
						"url" => "",
						"t" => $token,
					]));
					curl_setopt($ch, CURLOPT_HEADER, true);

					$res = curl_exec($ch);

					preg_match('/^Set-Cookie:\s*([^;]*)/mi', $res, $m);
					parse_str($m[1], $cookies);

					curl_setopt($ch, CURLOPT_URL, $this->options['acp_url'] . "/index.php?user-bulk-processing/");
					curl_setopt($ch, CURLOPT_POST, false);

					$res = curl_exec($ch);

					$search = '<input type="hidden" name="t" value="';
					$start = strpos($res, $search);
					if ($start === false) {
						return false;
					}

					$token = substr($res, $start + strlen($search), 40);

					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
						"action" => "assignToUserGroups",
						"assignToUserGroupIDs" => Array($this->options['group_id']),
						"username" => $_POST['assign'],
						"userAvatar" => "-1",
						"t" => $token,
					]));

					$res = curl_exec($ch);
					curl_close($ch);
					unlink(__DIR__ . "/.cookie");

					if (strpos($res, "Die gewählte Aktion wurde auf 1 Benutzer ausgeführt.") === false) {
						$var['error'] = $this->getLang('E3');
					} else {
						header('Location: ' . $this->options['forum_url']);
						exit;
					}
				}
			} else {
				$var['error'] = $this->getLang('E1');
			}
		}

		$var['l'] = $this->getLang();
		$var['u'] = $this->options['forum_url'];
		$title = $this->getLang("TITLE");
		$tpl = __DIR__ . "/frontend.tpl";
	}
}