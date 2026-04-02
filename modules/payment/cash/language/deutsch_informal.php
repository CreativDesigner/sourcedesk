<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['CASHBOX'] = "Bitte stecke den Betrag von <b>%a</b> nebst einem Zettel mit dem Text <b>%t</b> in einen Briefumschlag und verschicke diesen an folgende Adresse:";
$addonlang['INTRO'] = "Du kannst Bargeld an uns senden. Dazu stecke den gew&uuml;nschten Betrag nebst einem Zettel mit dem Text <b>%t</b> in einen Briefumschlag und verschicke diesen an folgende Adresse:";
$addonlang['MAKE_IN_PERSON'] = "Alternativ kannst Du <b>nach Vereinbarung</b> an unserer Adresse vorbeikommen und das Geld vorbeibringen.";

?>