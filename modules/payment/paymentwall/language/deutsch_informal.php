<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['INTRO'] = "Bitte führe nun Deine Zahlung bei Paymentwall aus. Es kann mehrere Minuten dauern, bis Dir das Guthaben zur Verfügung steht.";