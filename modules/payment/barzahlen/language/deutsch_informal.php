<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['ADMIN_HINT'] = "Bitte setze in den Shop-Einstellungen bei Barzahlen die Benachrichtigungs-URL auf %u und leere das Feld für die Sandbox-URL.";
$addonlang['ERROR'] = "Die Zahlung mit Barzahlen konnte nicht initialisiert werden. Versuche es eventuell mit einem niedrigeren Betrag erneut.";

?>