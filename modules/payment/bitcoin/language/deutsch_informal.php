<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['PAYMENT_HINT'] = "Wir warten %c Bestätigungen ab, der Kurs kann sich bis dahin noch zu Deinen Gunsten oder Lasten entwickeln, sodass Dir am Ende mehr oder weniger Geld als geplant zur Verfügung stehen kann. Bitte bedenke dies bei der Wahl des Betrages.";

?>