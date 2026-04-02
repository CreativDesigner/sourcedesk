<?php

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$inv = new Invoice;

if (isset($pars[0])) {
    if (!$inv->load($pars[0])) {
        throw new Exception("Invoice not found");
    }

    $info = $inv->getInfo();

    $items = $inv->getItems();

    $info->items = [];

    foreach ($items as $i) {
        array_push($info->items, $i->getInfo());
    }

    die(json_encode($info));
} else {
    $arr = [];

    $sql = $db->query("SELECT ID, client, date, duedate, customno, status FROM invoices");
    while ($row = $sql->fetch_object()) {
        if (empty($row->customno)) {
            unset($row->customno);
        }

        $inv->load($row->ID);
        $row->amount = $inv->getAmount();

        array_push($arr, $row);
    }

    die(json_encode($arr));
}