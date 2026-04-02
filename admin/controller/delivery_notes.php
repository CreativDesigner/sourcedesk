<?php
global $ari, $lang, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title("Lieferscheine");
menu("payments");

if (!$ari->check(13)) {
	alog("general", "insufficient_page_rights", "delivery_notes");
	$tpl = "error";
} else if ((!isset($_GET['id']) || !is_numeric($_GET['id'])) && (!isset($_POST['invoices']) || !is_array($_POST['invoices']) || count($_POST['invoices']) == 0)) {
	$tpl = "error";
} else {
	$inv = new Invoice;
	$d = 0;
	if (!isset($_POST['invoices'])) {
		$_POST['invoices'] = Array($_GET['id']);
	}

	asort($_POST['invoices']);
	foreach ($_POST['invoices'] as $id) {
		if (!$inv->load($id)) {
			continue;
		}

		if ($inv->getClient() != "0") {
			if (!($uI instanceof User) && !($uI = User::getInstance($inv->getClient(), "ID"))) {
				continue;
			}

			require __DIR__ . "/../../languages/" . basename($uI->getLanguage()) . ".php";
		} else {
			$il = $CFG['LANG'];
			$cd = unserialize($inv->getClientData());
			$cl = isset($cd['language']) ? $cd['language'] : $il;
			if (file_exists(__DIR__ . "/../../languages/" . basename($cl) . ".php")) {
				$il = $cl;
			}

			require __DIR__ . "/../../languages/" . basename($il) . ".php";
		}

		if (!isset($pdf)) {
			$pdf = new PDFDeliveryNote;
		}

		if (!$pdf->add($inv)) {
			continue;
		}

		alog("delivery_note", $output, $id);
		$d++;
	}

	if ($d == 0) {
		$tpl = "error";
	} else {
		if ($d == 1) {
			$pdf->output($inv->getShortNo(), "I");
		} else {
			$pdf->output("Lieferscheine-" . $inv->getClient(), "I");
		}

	}
}