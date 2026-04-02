<?php
// File for handling CLI requests
global $pars, $argv;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

// Check if request is done by CLI
if (php_sapi_name() != "cli") {
	die("Action only via CLI.");
}

if(empty($pars[0])) {
    $pars[0] = isset($argv[2]) ? $argv[2] : "";
}

if (!file_exists(__DIR__ . "/cli/" . basename($pars[0], ".php") . ".php")) {
	die("CLI file not found.");
}

// Do requested CLI action
require __DIR__ . "/cli/" . basename($pars[0], ".php") . ".php";

// Do not output anything using Smarty
exit;