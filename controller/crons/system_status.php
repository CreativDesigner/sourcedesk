<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Cronjob for system status
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Optimizing DB\n", FILE_APPEND);
SystemStatus::optimizeDb();

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Checking system\n", FILE_APPEND);
SystemStatus::check();

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Checking version\n", FILE_APPEND);
Versioning::actualVersion();

// Apply micro patches
if ($CFG['MICROPATCHES']) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Checking and applying micropatches\n", FILE_APPEND);
    Micropatch::apply();
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
