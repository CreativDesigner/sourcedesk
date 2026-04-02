<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['INTRO'] = "Um Dein Guthaben aufzuladen, t&auml;tige bitte eine &Uuml;berweisung an folgendes Konto:";
$addonlang['SUBJECT_HINT'] = "Bitte achte auf eine genaue Angabe des Verwendungszweckes, damit eine schnelle Zuordnung der Zahlung gew&auml;hrleistet werden kann.";
$addonlang['CASHBOX'] = "Bitte &uuml;berweise den Betrag von %a unter Ber&uuml;cksichtigung des Verwendungszweckes auf folgendes Bankkonto:";

?>