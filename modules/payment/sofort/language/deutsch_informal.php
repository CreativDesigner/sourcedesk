<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['INCOMING_HINT'] = "Wenn Du Deine eingehenden Überweisungen automatisch vom System bearbeiten lässt, kannst Du überprüfen lassen, ob die gutgeschriebenen Sofort-Überweisungen auch auf Deinem Konto ankommen. Andernfalls erhältst Du eine Benachrichtigung per E-Mail.";
$addonlang['ADMIN_WARNING'] = "Bitte deaktiviere unbedingt in den Projekteinstellungen bei SOFORT für Sofort-Überweisung den Testmodus! Andernfalls kann jeder beliebig Zahlungen vorgaukeln.";

?>