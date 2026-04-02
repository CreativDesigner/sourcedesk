<?php
// File for handling cronjob requests

// Global some variables for security reasons
global $CFG, $db, $maq, $transactions, $nfo, $dfo, $sec, $cur, $provisioning, $addons, $argv;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Check if requested cronjob exists
if (php_sapi_name() == "cli") {
    $_GET['job'] = $argv[2] ?? "";

    $ex = explode("/", $argv[1]);
    if (count($ex) == 2 && $ex[0] == "cron") {
        $_GET['job'] = $ex[1];
    }

    // Threaded cronjobs
    if ($_GET['job'] == "_all") {
        gc_enable();

        class CronjobWorker extends Thread
        {
            protected $cronjob;

            public function __construct($cronjob)
            {
                global $executing;
                $this->cronjob = $cronjob;
                array_push($executing, $cronjob);
            }

            public function run()
            {
                global $executing;

                $command = (PHP_BINARY ?? "") ?: "php ";
                $command .= realpath(__DIR__ . "/../index.php");
                $command .= " cron " . escapeshellarg($this->cronjob);
                `$command`;

                unset($executing[array_search($this->cronjob, $executing)]);
            }
        }

        $executing = [];

        while (true) {
            $sql = $db->query("SELECT * FROM cronjobs");
            while ($row = $sql->fetch_object()) {
                if (in_array($row->key, $executing)) {
                    continue;
                }

                if ($row->last_call > time() - $row->intervall) {
                    continue;
                }

                $thread = new CronjobWorker($row->key);
                $thread->start();
            }

            sleep(60);
        }

        exit;
    }
}

if (!file_exists(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".php")) {
    die("Cronjob file not found.");
}

// Select cronjob information from database
$sql = $db->query("SELECT * FROM cronjobs WHERE `key` = '" . $db->real_escape_string($_GET['job']) . "' LIMIT 1");
if ($sql->num_rows != 1) {
    die("Cronjob not found.");
}

$cronInfo = $sql->fetch_object();

// Check for password if any is requested
if (trim($cronInfo->password) != "" && (!isset($_GET['pw']) || trim($_GET['pw']) != trim($cronInfo->password)) && php_sapi_name() != "cli") {
    die("Wrong password for cronjob.");
}

// Check if interval is okay (10% tolerance)
$intOkay = $cronInfo->intervall;
$intOkay -= $intOkay / 10;
if (time() - $intOkay < $cronInfo->last_call && empty($_GET['force']) && $cronInfo->key != "queue") {
    die("Cronjob was already called in this intervall");
}

// Check if cronjob is active
if ($cronInfo->active != 1) {
    die("Cronjob is not active");
}

// Check if cronjob is running
if (file_exists(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".lock") && empty($_GET['force'])) {
    $ex = explode("\n", file_get_contents(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".lock"));

    if (strpos($ex[0], "[") !== 0) {
        filemtime(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".lock");
    } else {
        $time = strtotime(substr($ex[0], 0, 19));
    }

    if (time() - $time > 1800) {
        unlink(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".lock");
    } else {
        die("Cronjob is running already");
    }

}
touch(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".lock");

// Do requested cron action
require __DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".php";

// Run hook
$addons->runHook("Cronjob", [
    "cron" => basename($_GET['job'], ".php"),
]);

// Set last cronjob call in database if cronjob was okay / does not stop the script
$db->query("UPDATE cronjobs SET last_call = " . time() . " WHERE ID = $cronInfo->ID LIMIT 1");
if (file_exists(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".lock")) {
    unlink(__DIR__ . "/crons/" . basename($_GET['job'], ".php") . ".lock");
}

// Do not output anything using Smarty
exit;
