<?php
global $CFG, $db;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

header('Access-Control-Allow-Origin: *');
if ($db->query("SELECT 1 FROM admins WHERE online = 1")->num_rows > 0) {
	die("1");
}

die("0");