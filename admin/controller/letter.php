<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['LETTER_EDIT'];

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title("Briefe");
menu("customers");

if ($ari->check(7) && isset($_GET['id']) && is_object($sql = $db->query("SELECT 1 FROM client_letters WHERE ID = " . intval($_GET['id']))) && $sql->num_rows == 1) {
	$sql2 = $db->query("SELECT * FROM client_countries WHERE active = 1 ORDER BY name ASC");
	$var['countries'] = Array();
	while ($row = $sql2->fetch_object()) {
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

			$sql3 = $db->prepare("UPDATE client_letters SET subject = ?, `text` = ?, sent = ?, recipient = ?, `date` = ? WHERE ID = ?");
			$sql3->bind_param("ssissi", $_POST['subject'], $_POST['text'], $sent, $recipient, $a = date("Y-m-d", strtotime($_POST['date'])), $_GET['id']);
			$sql3->execute();

			if (empty($_POST['dnra']) && file_exists($path = __DIR__ . "/../../files/letter_attachments/" . intval($_GET['id']) . ".pdf")) {
				unlink($path);
			}

			if (!empty($_FILES['attachment'])) {
				$ex = explode(".", basename($_FILES['attachment']['name']));
				if (array_pop($ex) == "pdf") {
					move_uploaded_file($_FILES['attachment']['tmp_name'], __DIR__ . "/../../files/letter_attachments/" . intval($_GET['id']) . ".pdf");
				}
			}

			alog("letter", "saved", $_GET['id']);
			$var['success'] = str_replace("%i", $_GET['id'], $l['SUC']);
			unset($_POST);
		} catch (Exception $ex) {
			$var['error'] = $ex->getMessage();
		}
	}

	$sql = $db->query("SELECT * FROM client_letters WHERE ID = " . intval($_GET['id']));
	$i = $sql->fetch_object();
	$r = $var['r'] = unserialize($i->recipient);
	$var['letter'] = (array) $i;
	$var['user'] = User::getInstance($i->client, "ID");
	if ($var['user']) {
		$var['user'] = $var['user']->get();
	}

	$tpl = "letter";

	alog("letter", "viewed", $_GET['id']);
	$var['languages'] = $GLOBALS['languages'];
	$var['attached'] = file_exists(__DIR__ . "/../../files/letter_attachments/" . intval($_GET['id']) . ".pdf");
} else {
	if (!$ari->check(7)) {
		alog("general", "insufficient_page_rights", "letter");
	}

	$tpl = "error";
}