<?php

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (empty($pars[0]) || (!$user = User::getInstance($pars[0], "ID"))) {
    throw new Exception("User not found");
}

parse_str(file_get_contents("php://input"), $in);

$user->set($in);
$user->saveChanges("api");

die(json_encode(["status" => "ok"]));