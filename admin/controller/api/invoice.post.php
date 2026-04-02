<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

parse_str(file_get_contents("php://input"), $in);

if (empty($in['client']) || !User::getInstance($in['client'], "ID")) {
    throw new Exception("Invalid client specified.");
}

$date = isset($in['date']) ? strtotime($in['date']) : time();

$invoice = new Invoice;
$invoice->setDate(date("Y-m-d", $date));
$invoice->setClient($in['client']);

if (isset($in['duedate'])) {
    $invoice->setDueDate(date("Y-m-d", strtotime($in['duedate'])));
} else {
    $invoice->setDueDate();
}

$invoice->save();

foreach ($in['items'] as $i) {
    $item = new InvoiceItem;
    $item->setInvoice($invoice);
    $item->setDescription($i['description']);
    $item->setAmount(doubleval($i['amount']));
    $item->setTax(isset($i['tax']) && $i['tax'] == "0" ? 0 : 1);
    $item->save();
}

$invoice->save();

die(json_encode(["id" => $invoice->getId()]));
