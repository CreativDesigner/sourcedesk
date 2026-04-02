<?php
global $db, $CFG, $raw_cfg;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$s = empty($_REQUEST['domain']) ? "" : " AND domain LIKE '%" . $db->real_escape_string($_REQUEST['domain']) . "%'";
$sql = $db->query("SELECT * FROM domains WHERE user = " . $user->get()['ID'] . " AND status IN ('KK_OK', 'REG_OK')$s");

$domains = Array();
while ($row = $sql->fetch_assoc()) {
	$info = unserialize($row['reg_info']);
	if ($info !== false) {
		$row['ns'] = Array();
		if (count($info['ns']) == 2 && filter_var($info['ns'][0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			array_push($domains, $row['domain']);
		} else {
			if ($info['ns'][0] == $CFG['NS1'] && $info['ns'][1] == $CFG['NS2'] && $info['ns'][2] == $CFG['NS3'] && $info['ns'][3] == $CFG['NS4'] && $info['ns'][4] == $CFG['NS5']) {
				array_push($domains, $row['domain']);
			} else if ($info['ns'][0] == $raw_CFG['NS1'] && $info['ns'][1] == $raw_CFG['NS2'] && $info['ns'][2] == $raw_CFG['NS3'] && $info['ns'][3] == $raw_CFG['NS4'] && $info['ns'][4] == $raw_CFG['NS5']) {
				array_push($domains, $row['domain']);
			}

		}
	}
}

die(json_encode(Array("code" => "100", "message" => "Zones fetched.", "data" => $domains)));

?>
