<?php
// Global some variables for security reasons
global $ari, $CFG, $db;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$tpl = "error";

if ($ari->check(7)) {
	if (isset($_GET['user']) && is_numeric($_GET['user']) && $uI = User::getInstance($_GET['user'], "ID")) {
		$u = (object) $uI->get();

		$vcard = new VCard;

		$sql = $db->query("SELECT name FROM client_countries WHERE ID = " . intval($u->country));
		$country = $sql->num_rows == 1 ? $sql->fetch_object()->name : "";

		$vcard->set("first_name", $u->firstname);
		$vcard->set("last_name", $u->lastname);
		$vcard->set("display_name", $u->firstname . " " . $u->lastname);
		$vcard->set("company", $u->company);
		$vcard->set("work_address", $u->street . " " . $u->street_number);
		$vcard->set("work_city", $u->city);
		$vcard->set("work_postal_code", $u->postcode);
		$vcard->set("work_country", $country);
		$vcard->set("office_tel", $u->telephone);
		$vcard->set("email1", $u->mail);
		$vcard->set("url", $u->website);
		$vcard->set("birthday", $u->birthday);

		alog("vcard", "got", $_GET['user']);

		$vcard->download();
	}
} else {
	alog("general", "insufficient_page_rights", "vcard");
}