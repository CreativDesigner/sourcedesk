<?php

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$data = [];

foreach(["username", "name", "email", "notes", "online", "language"] as $k)
    $data[$k] = $admin->$k;

echo json_encode($data);