<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Cronjob for server monitoring
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

$start = time();
$sql = $db->query("SELECT `server` FROM monitoring_services GROUP BY `server` ORDER BY `last_called` ASC");

while ($row = $sql->fetch_object()) {
    $obj = MonitoringServer::getInstance($row->server);
    if ($obj) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Checking server #{$obj->ID}\n", FILE_APPEND);
        $obj->check(true);

        if (time() - $start >= 20) {
            break;
        }
    }
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);

// Cronjob for server updates
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Check for updates\n", FILE_APPEND);

$start = time();
$sql = $db->query("SELECT `ID` FROM monitoring_server WHERE ssh_host != '' ORDER BY `ssh_last` ASC LIMIT 3");

while ($row = $sql->fetch_object()) {
    $obj = MonitoringServer::getInstance($row->ID);
    if ($obj) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Checking server #{$obj->ID}\n", FILE_APPEND);
        $obj->checkUpdates();

        if (time() - $start >= 20) {
            break;
        }
    }
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
