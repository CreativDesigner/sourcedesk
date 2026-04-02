<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

$addonlang['TIME_WARNING'] = "Wir wickeln PayPal-Transaktionen &uuml;ber einen externen Dienstleister ab. Leider kann die Gutschrift erst erfolgen, sobald die Zahlung durch den Anbieter vollst&auml;ndig best&auml;tigt wurde. Dies ist nach sp&auml;testens 48 Stunden der Fall. Selbstverst&auml;ndlich wirst Du &uuml;ber die Gutschrift per E-Mail informiert.";

?>