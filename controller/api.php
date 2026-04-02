<?php
global $pars, $user, $CFG;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!isset($pars[0]) || $pars[0] != "license" || !isset($pars[1]) || $pars[1] != "info") {
    $user = new User(intval($pars[0]), "ID");
    if (empty($user->get()['ID']) || empty($user->get()['api_key']) || $user->get()['ID'] != $pars[0] || $user->get()['api_key'] != $pars[1]) {
        die(json_encode(array("code" => "800", "message" => "Authentication failed.", "data" => array())));
    }

    if (empty($pars[2]) || !is_dir(__DIR__ . "/api/" . basename($pars[2]))) {
        die(json_encode(array("code" => "801", "message" => "Group not found.", "data" => array())));
    }

    if (in_array(basename($pars[2]), array("domain", "dns")) && !$user->get()['domain_api']) {
        die(json_encode(array("code" => "801", "message" => "Group not allowed.", "data" => array())));
    }

    if (empty($pars[3]) || !is_file(__DIR__ . "/api/" . basename($pars[2]) . "/" . basename($pars[3]) . ".php")) {
        die(json_encode(array("code" => "802", "message" => "Action not found.", "data" => array())));
    }

    $ns = unserialize($user->get()['dns_server']);
    if ($ns !== false && count($ns) >= 2) {
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($ns[$i - 1])) {
                $CFG['NS' . $i] = $ns[$i - 1];
            } else {
                $CFG['NS' . $i] = "";
            }

        }
    }

    require __DIR__ . "/api/" . basename($pars[2]) . "/" . basename($pars[3]) . ".php";
} else {
    require __DIR__ . "/api/license/info.php";
}

exit;
