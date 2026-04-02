<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['NO_JS'] = "Du hast JavaScript nicht aktiviert. Eine Zahlung per Kreditkarte ist ohne JavaScript nicht m&ouml;glich.";
$addonlang['DONE'] = "Vielen Dank f&uuml;r Deine Aufladung! Der Betrag wurde Deinem Kundenkonto gutgeschrieben.";
$addonlang['FAILED'] = "Die Zahlung ist leider fehlgeschlagen und wurde nicht durchgef&uuml;hrt. Deine Kreditkarte wurde nicht belastet.";

?>