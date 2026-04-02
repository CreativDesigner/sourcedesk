<?php
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

// Cronjob for sending mails from queue
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

// Send mails (the limit is set in class as property)
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Sending mails\n", FILE_APPEND);
$maq->send();

// Delete old sent mails if wanted so
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Deleting old mails\n", FILE_APPEND);
if ($CFG['MAIL_LEAD'] > 0) {
	$maxtime = time() - (60 * 60 * 24 * 30 * $CFG['MAIL_LEAD']);
	$db->query("DELETE FROM client_mails WHERE time < $maxtime LIMIT 100");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);