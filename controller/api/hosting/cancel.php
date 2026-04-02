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

$dates = $provisioning->getCancellationDates($info->ID);

if (!is_array($dates)) {
    die(json_encode(array("code" => "805", "message" => "No cancellation possible because of onetime.", "data" => array())));
}

if (isset($_REQUEST['cancel'])) {
    $_REQUEST['date'] = $_REQUEST['cancel'];
}

if (isset($_REQUEST['date'])) {
    if (!in_array($_REQUEST['date'], $dates) && $_REQUEST['date'] != "0000-00-00") {
        die(json_encode(array("code" => "806", "message" => "Provided cancellation date is invalid.", "data" => array())));
    }

    $db->query("UPDATE client_products SET cancellation_date = '" . $db->real_escape_string($_REQUEST['date']) . "' WHERE ID = " . intval($id));

    if (($ntf = AdminNotification::getInstance("Neue Kündigung")) !== false) {
        $name = "";
        $furtherSQL = $db->query("SELECT name FROM products WHERE ID = " . $info->product);
        if ($furtherSQL->num_rows) {
            $furtherInfo = $furtherSQL->fetch_object();
            $name = unserialize($furtherInfo->name)[$CFG['LANG']];
        }

        $ntf->set("name", $user->get()['name']);
        $ntf->set("product", $name);
        $ntf->set("cid", $user->get()['ID']);
        $ntf->set("hid", intval($id));
        $ntf->send();
    }

    die(json_encode(array("code" => "100", "message" => "Cancellation date set successful.", "data" => array("date" => $_REQUEST['date']))));
} else {
    die(json_encode(array("code" => "100", "message" => "Cancellation date query successful.", "data" => array("dates" => $dates))));
}
