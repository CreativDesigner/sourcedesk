<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['LETTER_EDIT'];

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($lang['LETTER_CREATE']['TITLE']);
menu("customers");

if ($ari->check(7)) {
	$sql = $db->query("SELECT * FROM client_countries WHERE active = 1 ORDER BY name ASC");
	$var['countries'] = Array();
	while ($row = $sql->fetch_object()) {
		$var['countries'][$row->ID] = $row->name;
	}

	if (isset($_POST['subject'])) {
		try {
			if (empty($_POST['date']) || strtotime($_POST['date']) === false) {
				throw new Exception($l['ERR1']);
			}

			if (empty($_POST['firstname'])) {
				throw new Exception($l['ERR2']);
			}

			if (empty($_POST['lastname'])) {
				throw new Exception($l['ERR3']);
			}

			if (empty($_POST['street'])) {
				throw new Exception($l['ERR4']);
			}

			if (empty($_POST['street_number'])) {
				throw new Exception($l['ERR5']);
			}

			if (empty($_POST['postcode'])) {
				throw new Exception($l['ERR6']);
			}

			if (empty($_POST['city'])) {
				throw new Exception($l['ERR7']);
			}

			if (empty($_POST['country']) || !array_key_exists($_POST['country'], $var['countries'])) {
				throw new Exception($l['ERR8']);
			}

			if (empty($_POST['language']) || !array_key_exists($_POST['language'], $GLOBALS['languages'])) {
				throw new Exception($l['ERR9']);
			}

			if (empty($_POST['subject'])) {
				throw new Exception($l['ERR10']);
			}

			if (empty($_POST['text'])) {
				throw new Exception($l['ERR11']);
			}

			$sent = $_POST['sent'] == "1" ? 1 : 0;

			$client = isset($_GET['client']) && User::getInstance($_GET['client'], "ID") ? intval($_GET['client']) : 0;
			$recipient = Array($_POST['firstname'], $_POST['lastname'], $_POST['street'], $_POST['street_number'], $_POST['postcode'], $_POST['city'], $var['countries'][$_POST['country']], $_POST['language'], $_POST['company']);
			$recipient = serialize($recipient);

			$sql = $db->prepare("INSERT INTO client_letters (client, subject, `text`, sent, recipient, `date`) VALUES (?,?,?,?,?,?)");
			$sql->bind_param("ississ", $client, $_POST['subject'], $_POST['text'], $sent, $recipient, $a = date("Y-m-d", strtotime($_POST['date'])));
			$sql->execute();

			alog("letter", "created", $id = $db->insert_id);

			if (!empty($_FILES['attachment'])) {
				$ex = explode(".", basename($_FILES['attachment']['name']));
				if (array_pop($ex) == "pdf") {
					move_uploaded_file($_FILES['attachment']['tmp_name'], __DIR__ . "/../../files/letter_attachments/$id.pdf");
				}
			}

			$var['success'] = str_replace("%i", $id, $lang['LETTER_CREATE']['SUC']);
			unset($_POST);
		} catch (Exception $ex) {
			$var['error'] = $ex->getMessage();
		}
	}

	if (isset($_GET['client'])) {
		$user = User::getInstance($_GET['client'], "ID");
		if ($user) {
			$var['user'] = $user->get();

			foreach ($var['user'] as $k => $v) {
				if (!isset($_POST[$k])) {
					$_POST[$k] = $v;
				}
			}

			alog("letter", "client_details", $user->get()['ID']);
		}
	}

	$tpl = "new_letter";

	$var['languages'] = $GLOBALS['languages'];
} else {
	alog("general", "insufficient_page_rights", "new_letter");
	$tpl = "error";
}