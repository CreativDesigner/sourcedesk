<?php
global $db, $CFG, $nfo, $provisioning;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$id = $_REQUEST['id'];
if (empty($id)) {
    die(json_encode(array("code" => "803", "message" => "No product specified.", "data" => array())));
}

$sql = $db->query("SELECT * FROM products WHERE type = 'HOSTING' AND status = 1 AND ID = " . intval($id));
if ($sql->num_rows != 1) {
    die(json_encode(array("code" => "804", "message" => "Invalid product specified.", "data" => array())));
}

$info = $sql->fetch_object();

if ($info->only_verified && !$user->get()['verified']) {
    die(json_encode(array("code" => "804", "message" => "Invalid product specified.", "data" => array())));
}

if ($info->customer_groups && !in_array($user->get()['cgroup'], explode(",", $info->customer_groups))) {
    die(json_encode(array("code" => "804", "message" => "Invalid product specified.", "data" => array())));
}

if ($user->get()['credit'] < $info->price) {
    die(json_encode(array("code" => "805", "message" => "Not enough credit.", "data" => array())));
}

$module_settings = array();
$sql = $db->query("SELECT setting, value FROM product_provisioning WHERE module = '" . $db->real_escape_string($info->module) . "' AND pid = " . intval($info->ID));
while ($row = $sql->fetch_object()) {
    $module_settings[$row->setting] = decrypt($row->value);
}

$module_settings = $db->real_escape_string(encrypt(serialize($module_settings)));

$name = unserialize($info->name)[$user->getLanguage()];

if ($info->billing != "" && $info->billing != "onetime") {
    $billarr = array(
        "monthly" => "1 month",
        "quarterly" => "3 months",
        "semiannually" => "6 months",
        "annually" => "1 year",
        "biennially" => "2 years",
        "trinnially" => "3 years",
    );
    $bill = date("Y-m-d", strtotime("+" . $billarr[$info->billing]));
} else {
    $bill = "0000-00-00";
}

$cd = '0000-00-00';
if ($info->autodelete) {
    $cd = date("Y-m-d", strtotime("+" . $info->autodelete . " days"));
}

$price = $info->price;
$discount -= $info->setup;
$db->query("INSERT INTO client_products (`date`, `user`, `product`, `active`, `type`, `description`, `billing`, `module`, `module_settings`, `last_billed`, `ct`, `mct`, `np`, `price`, `cancellation_date`) VALUES (" . time() . ", {$user->get()['ID']}, {$info->ID}, -1, 'h', '" . $db->real_escape_string(@$_REQUEST['note'] ?: "") . "', '{$info->billing}', '{$info->module}', '$module_settings', '" . $bill . "', '{$info->ct}', '{$info->mct}', '{$info->np}', $price, '$cd')");
$id = $db->insert_id;

if ($info->new_cgroup >= 0) {
    $user->set(["cgroup" => $info->new_cgroup, "cgroup_before" => $user->get()['cgroup'], "cgroup_contract" => $id]);
}

if ($info->price != 0 || $info->setup != 0) {
    $inv = new Invoice;
    $inv->setDate(date("Y-m-d"));
    $inv->setClient($user->get()['ID']);
    $inv->setDueDate();

    if ($info->price != 0) {
        $item = new InvoiceItem;
        $item->setDescription($name);
        $item->setAmount($info->price);
        $item->setRelid($id);
        $inv->addItem($item);
    }

    if ($info->setup != 0) {
        $user->loadLanguage();
        $item = new InvoiceItem;
        $item->setDescription($name . " (" . ($info->setup > 0 ? $lang['CART']['SETUP'] : $lang['CART']['DISCOUNT']) . ")");
        $item->setAmount($info->setup);
        $item->setRelid($id);
        $inv->addItem($item);
    }

    $inv->save();
    $inv->applyCredit();
    $inv->save();
}

$db->query("UPDATE products SET available = available - 1 WHERE ID = " . $info->ID);

$data = array("id" => $id);

$user->log("[API] [#" . $id . "] Produkt bestellt");

if (empty($_REQUEST['async'])) {
    $force_id = $id;
    require __DIR__ . "/../../crons/provisioning.php";

    $m = $provisioning->get()[$info->module];
    $vars = $m->EmailVariables($id);
    foreach ($vars as $k => $v) {
        if (!isset($data[$k])) {
            $data[$k] = $v;
        }
    }

}

die(json_encode(array("code" => "100", "message" => "Product order successful.", "data" => $data)));
