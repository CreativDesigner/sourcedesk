<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

class_loader2("Backup");
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

// Check which method is used to save the backup (filesystem, ftp, mail) and initialize the necessary object
if ($CFG['BACKUP_METHOD'] == "file" || $CFG['BACKUP_METHOD'] == "") {
    $bc = new FilesystemBackup();
} else if ($CFG['BACKUP_METHOD'] == "ftp") {
    $bc = new FTPBackup();
} else {
    die("Backup method unknown.");
}

// Check which things should be included within the backup (database and/or files)
$d = $CFG['BACKUP_DATA'];
if ($d == "all" || $d == "db") {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Database dump started\n", FILE_APPEND);
    $bc->saveDump();
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Database dump finished\n", FILE_APPEND);
}

if ($d == "all" || $d == "files") {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] File backup started\n", FILE_APPEND);
    $bc->saveFiles();
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] File backup finished\n", FILE_APPEND);
}

// Send the backup to specified medium
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Backup saving started\n", FILE_APPEND);
$bc->send();
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Backup saving finished\n", FILE_APPEND);

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
$bc = null;
