<?php

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if (isset($pars[0])) {
    $user = User::getInstance($pars[0], "ID");
    if (!$user) {
        throw new Exception("User not found");
    }

    $info = $user->get();
    die(json_encode($info));
} else {
    $arr = [];

    $sql = $db->query("SELECT ID, firstname, lastname, company, mail FROM clients");
    while ($row = $sql->fetch_object()) {
        array_push($arr, $row);
    }

    die(json_encode($arr));
}