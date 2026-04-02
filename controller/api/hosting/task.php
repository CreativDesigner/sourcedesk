<?php
global $db, $CFG, $provisioning;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$id = $_REQUEST['id'];
if (empty($id)) {
    die(json_encode(array("code" => "803", "message" => "No account specified.", "data" => array())));
}

$sql = $db->query("SELECT * FROM client_products WHERE user = {$user->get()['ID']} AND ID = " . intval($id));
if ($sql->num_rows != 1) {
    die(json_encode(array("code" => "804", "message" => "Invalid account specified.", "data" => array())));
}

$info = $sql->fetch_object();

$m = $provisioning->get()[$info->module];

if (empty($_REQUEST['task'])) {
    die(json_encode(array("code" => "805", "message" => "No task specified.", "data" => array())));
}

if ($_REQUEST['task'] == "AssignDomain") {
    if (empty($_REQUEST['domain'])) {
        die(json_encode(array("code" => "808", "message" => "No domain specified.", "data" => array())));
    }

    $d = $_REQUEST['domain'];

    function is_valid_domain_name($domain_name)
    {
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name)
            && preg_match("/^.{1,253}$/", $domain_name)
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name));
    }

    if (!is_valid_domain_name($d)) {
        die(json_encode(array("code" => "810", "message" => "Invalid domain specified.", "data" => array())));
    }

    if (!$m->AssignDomain($info->ID, $d)[0]) {
        die(json_encode(array("code" => "811", "message" => "Assigning domain failed.", "data" => array())));
    }

    die(json_encode(array("code" => "100", "message" => "Domain assigned.", "data" => array())));
} else {
    if (!array_key_exists($_REQUEST['task'], $m->ApiTasks($info->ID))) {
        die(json_encode(array("code" => "806", "message" => "Invalid task specified.", "data" => array())));
    }

    $params = $m->ApiTasks($info->ID)[$_REQUEST['task']];
    $ex = explode(",", $params);
    foreach ($ex as $p) {
        if (!empty($p) && !isset($_REQUEST[$p])) {
            die(json_encode(array("code" => "807", "message" => "Parameter missing.", "data" => array("parameter" => $p))));
        }
    }

    $t = $_REQUEST['task'];
    $m->$t($id, $_REQUEST);
}
