<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$s = empty($_REQUEST['domain']) ? "" : " AND domain LIKE '%" . $db->real_escape_string($_REQUEST['domain']) . "%'";
$sql = $db->query("SELECT * FROM domains WHERE user = " . $user->get()['ID'] . "$s");

$domains = Array();
while ($row = $sql->fetch_assoc()) {
	$info = unserialize($row['reg_info']);
	if ($info !== false) {
		foreach (Array("owner", "admin", "tech", "zone") as $h) {
			$row[$h] = Array(
				"firstname" => $info[$h][0],
				"lastname" => $info[$h][1],
				"company" => $info[$h][2],
				"street" => $info[$h][3],
				"country" => $info[$h][4],
				"postcode" => $info[$h][5],
				"city" => $info[$h][6],
				"telephone" => $info[$h][7],
				"telefax" => $info[$h][8],
				"email" => $info[$h][9],
				"remarks" => $info[$h][10],
			);
		}

		$row['ns'] = Array();
		if (count($info['ns']) != 2 || !filter_var($info['ns'][0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			foreach ($info['ns'] as $n) {
				if (!empty($n)) {
					array_push($row['ns'], $n);
				}
			}

		} else {
			for ($i = 1; $i <= 5; $i++) {
				$n = $CFG['NS' . $i];
				if (!empty($n)) {
					array_push($row['ns'], $n);
				}

			}
		}
	}

	unset($row['reg_info'], $row['sent'], $row['sent_dns'], $row['registrar'], $row['changed'], $row['error'], $row['customer_wish'], $row['customer_when'], $row['sent_dns']);
	array_push($domains, $row);
}

die(json_encode(Array("code" => "100", "message" => "Domains fetched.", "data" => $domains)));

?>
