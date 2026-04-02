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

$tasks = [];

if (array_key_exists($info->module, $provisioning->get())) {
    try {
        $tasks = $provisioning->get()[$info->module]->ApiTasks($info->ID);
    } catch (Exception $ex) {
    } catch (SoapFault $ex) {}

    if (method_exists($provisioning->get()[$info->module], "AssignDomain")) {
        $tasks["AssignDomain"] = "domain";
    }
}

$name = "";

$furtherSQL = $db->query("SELECT name FROM products WHERE ID = " . $info->product);
if ($furtherSQL->num_rows) {
    $furtherInfo = $furtherSQL->fetch_object();
    $name = unserialize($furtherInfo->name)[$CFG['LANG']];
}

$output = "";
try {
    if (array_key_exists($info->module, $provisioning->get())) {
        $output = $provisioning->get()[$info->module]->Output($info->ID);
    }
} catch (Exception $ex) {
} catch (SoapFault $ex) {}

$data = array(
    "name" => $name,
    "status" => $info->active == 1,
    "description" => $info->description,
    "order_date" => date("Y-m-d H:i:s", $info->date),
    "product" => $info->product,
    "price" => $info->price,
    "period" => $info->billing,
    "next_invoice" => $info->last_billed == "0000-00-00" ? "0000-00-00" : date("Y-m-d", strtotime($info->last_billed)),
    "contract_time" => $info->ct,
    "notification_period" => $info->np,
    "cancellation_date" => $info->cancellation_date == "0000-00-00" ? "0000-00-00" : date("Y-m-d", strtotime($info->cancellation_date)),
    "login_data" => array_key_exists($info->module, $provisioning->get()) ? $provisioning->get()[$info->module]->EmailVariables($info->ID) : [],
    "tasks" => $tasks,
    "output" => $output,
);

die(json_encode(array("code" => "100", "message" => "Account query successful.", "data" => $data)));
