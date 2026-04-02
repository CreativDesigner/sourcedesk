<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['ERR1'] = "Die Transaktion konnte wegen Verbindungsproblemen nicht initiiert werden. Bitte wende Dich an unseren Support, falls das Problem längere Zeit bestehen bleibt.";
$addonlang['ERR2'] = "Bitte w&auml;hle einen Betrag zwischen 0,01 &euro; und 1.000 &euro;.";

?>