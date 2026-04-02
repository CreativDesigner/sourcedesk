<?php
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started, fetching list\n", FILE_APPEND);

// Cronjob for getting proxy IP list
IdentifyProxy::update();

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);

?>