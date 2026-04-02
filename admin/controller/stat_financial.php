<?php
global $ari, $lang, $CFG, $db, $var, $_GET, $nfo, $cur;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['FINANCIAL_OVERVIEW']['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
    $tpl = "stat_financial";

    // Get open invoices
    $inv = new Invoice;
    $to = date("Y-m-d", $to);

    $openInvoices = 0;
    $sql = $db->query("SELECT ID FROM invoices WHERE status = 0");
    while ($row = $sql->fetch_object()) {
        $inv->load($row->ID);
        $openInvoices += $inv->getAmount();
    }

    $credit = $db->query("SELECT SUM(credit) FROM clients WHERE credit < 0")->fetch_array()['SUM(credit)'] / -1;
    $var['liabilities'] = $CFG['STEM'] + $CFG['LOAN'] + $db->query("SELECT SUM(credit) FROM clients WHERE credit > 0")->fetch_array()['SUM(credit)'];
    if ($CFG['AFFILIATE_ACTIVE']) {
        $var['liabilities'] += $db->query("SELECT SUM(affiliate_credit) FROM clients WHERE affiliate_credit > 0")->fetch_array()['SUM(affiliate_credit)'];
    }

    $var['debtors'] = $credit + $openInvoices + $CFG['DEBTORS_OTHER'];
    if ($CFG['AFFILIATE_ACTIVE']) {
        $var['debtors'] += $db->query("SELECT SUM(affiliate_credit) FROM clients WHERE affiliate_credit < 0")->fetch_array()['SUM(affiliate_credit)'] / -1;
    }

    $var['cfg']['BANK_CREDIT'] = 0;
    $var['accounts'] = array();
    $sql = $db->query("SELECT bank, account, balance FROM payment_accounts WHERE balance != 0");
    while ($row = $sql->fetch_object()) {
        $var['cfg']['BANK_CREDIT'] += $row->balance;
        $var['accounts'][] = array(
            isset(BankCSV::getAvailableBanks(true, true)[$row->bank]) ? BankCSV::getAvailableBanks(true, true)[$row->bank] : "",
            $row->account,
            $cur->infix($nfo->format($row->balance > 0 ? $row->balance : $row->balance / -1), $cur->getBaseCurrency()),
            $row->balance,
        );
    }
} else {
    alog("general", "insufficient_page_rights", "stat_financial");
    $tpl = "error";
}
