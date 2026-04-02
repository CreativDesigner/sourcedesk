<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (empty($currentAccount)) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);
}

if (empty($currentAccount)) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Getting accounts\n", FILE_APPEND);
}

BankCSV::getAvailableBanks();

$where = "";
if (!empty($currentAccount)) {
    $where .= " AND ID = " . $currentAccount;
}

$sql = $db->query("SELECT * FROM payment_accounts WHERE credentials != ''$where");
$balance = 0;

while ($row = $sql->fetch_object()) {
    if (empty($currentAccount)) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching CSV for {$row->account} ({$row->bank})\n", FILE_APPEND);
    }

    $c = unserialize(decrypt($row->credentials));
    if ($c === false) {
        continue;
    }

    foreach ($c as &$v) {
        $v = base64_decode($v);
    }

    $class = "BankCSV_" . ucfirst(strtolower($row->bank));
    $obj = new $class;

    $chResult = $obj->getCSV($row->account, $c);

    if (empty($currentAccount)) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Handling CSV for {$row->account} ({$row->bank})\n", FILE_APPEND);
    }

    // Save CSV
    $handle = fopen(__DIR__ . "/transfer_import.tmp.csv", "w+");
    if (!$handle) {
        die("I/O Error");
    }

    fwrite($handle, is_array($chResult) ? serialize($chResult) : $chResult);
    fclose($handle);

    // Insert transactions
    $readHandle = fopen(__DIR__ . "/transfer_import.tmp.csv", "r");
    try {
        $res = CSVImport::doImport($readHandle, $row->bank, true, true, $c['copy'], $c['copy_wait']);
    } catch (BankCSV_Exception $bankExc) {
        // Do nothing
    }
    fclose($readHandle);

    // Delete CSV
    unlink(__DIR__ . "/transfer_import.tmp.csv");

    if (empty($currentAccount)) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching balance for {$row->account} ({$row->bank})\n", FILE_APPEND);
    }

    $balance = doubleval($obj->getBalance($row->account, $c));
    $db->query("UPDATE payment_accounts SET `balance` = '" . $balance . "' WHERE `ID` = " . $row->ID . " LIMIT 1");
}

if (empty($currentAccount)) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
}
