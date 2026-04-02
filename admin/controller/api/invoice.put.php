<?php
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$inv = new Invoice;

if (empty($pars[0]) || (!$inv->load($pars[0]))) {
    throw new Exception("Invoice not found");
}

parse_str(file_get_contents("php://input"), $in);

$inv->set($in);

$item = new InvoiceItem;

foreach ($in['items'] as $i) {
    if (isset($i['id'])) {
        if(!$item->load($i['id']) || $item->getInvoice() != $inv->getId()) {
            $item = new InvoiceItem;
        }
    }

    $item->setInvoice($inv);
    $item->setDescription($i['description']);
    $item->setAmount(doubleval($i['amount']));
    $item->setTax(isset($i['tax']) && $i['tax'] == "0" ? 0 : 1);
    $item->save();
}

$inv->save();

die(json_encode(["status" => "ok"]));