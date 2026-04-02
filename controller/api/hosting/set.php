<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$id = $_REQUEST['id'];
if (empty($id)) {
	die(json_encode(Array("code" => "803", "message" => "No account specified.", "data" => Array())));
}

$sql = $db->query("SELECT * FROM client_products WHERE user = {$user->get()['ID']} AND ID = " . intval($id));
if ($sql->num_rows != 1) {
	die(json_encode(Array("code" => "804", "message" => "Invalid account specified.", "data" => Array())));
}

$db->query("UPDATE client_products SET description = '" . $db->real_escape_string(@$_REQUEST['note'] ?: "") . "' WHERE user = {$user->get()['ID']} AND ID = " . intval($id));

die(json_encode(Array("code" => "100", "message" => "Description set successful.", "data" => $data)));

?>