<?php
global $db, $CFG;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

// Select clients without salutation
$sql = $db->query("SELECT ID, firstname, lastname FROM clients WHERE salutation = ''");

$i = 1;
while ($row = $sql->fetch_object()) {
    $firstname = trim($row->firstname);
    $ex = explode(" ", $firstname);
    
    if (count($ex) > 1) {
        $firstname = $ex[0];
    } else {
        $ex = explode("-", $firstname);
        if (count($ex) > 1) {
            $firstname = $ex[0];
        }
    }

    @$c = file_get_contents("https://www.vorname.com/name," . urlencode($firstname) . ".html");

    if (strpos($c, "badge_w_big") !== false) {
        echo "[$i / {$sql->num_rows}] Frau " . $row->firstname . " " . $row->lastname . "\n";
        $salutation = "FEMALE";
    } else if (strpos($c, "badge_m_big") !== false) {
        echo "[$i / {$sql->num_rows}] Herr " . $row->firstname . " " . $row->lastname . "\n";
        $salutation = "MALE";
    } else {
        echo "[$i / {$sql->num_rows}] ???? " . $row->firstname . " " . $row->lastname . " ($firstname)\n";
        $salutation = "";
    }

    $db->query("UPDATE clients SET salutation = '$salutation' WHERE ID = " . $row->ID . " LIMIT 1");

    $i++;
}

exit;