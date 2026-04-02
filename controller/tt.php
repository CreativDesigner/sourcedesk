<?php
global $db, $CFG, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (!empty($pars[0]) && !empty($pars[1]) && $pars[0] > 0 && is_numeric($pars[0]) && strlen($pars[1]) == 8 && $pars[1] == substr(hash("sha512", $pars[0] . $CFG['HASH'] . $pars[0]), -8)) {
	$db->query("UPDATE support_ticket_answers SET customer_read = 1 WHERE ID = " . intval($pars[0]));
}

header("Content-type: image/png");
echo file_get_contents(__DIR__ . "/../images/tt.png");

exit;