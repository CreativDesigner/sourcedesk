<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['DOIT'] = "Bitte logge Dich jetzt bei Amazon ein und f&uuml;hre die Zahlung &uuml;ber <b>%a</b> durch.";
$addonlang['ERR'] = "Bitte zahle mindestens %m.";
$addonlang['PERR'] = "Deine Zahlung ist fehlgeschlagen. Dein Konto wurde nicht belastet.";
$addonlang['PSUC'] = "Deine Zahlung war erfolgreich, wir haben Dir den Betrag gutgeschrieben.";
