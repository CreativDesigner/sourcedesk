<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Cronjob for sending invoice reminders
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Sending reminders\n", FILE_APPEND);

$inv = new Invoice;
$sql = $db->query("SELECT ID FROM invoices WHERE status = 0 AND no_reminders = 0 ORDER BY ID ASC");
while ($row = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Send reminder for invoice #{$row->ID}\n", FILE_APPEND);
    $inv->load($row->ID);
    if ($inv->getId() != $row->ID) {
        continue;
    }

    $inv->remind();
}

// Remind negative credit
if ($CFG['REMIND_CREDIT'] === date("N")) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Sending negative credit reminders\n", FILE_APPEND);

    $sql = $db->query("SELECT ID FROM clients WHERE credit < 0");
    while ($row = $sql->fetch_object()) {
        if (!($user = User::getInstance($row->ID, "ID"))) {
            continue;
        }

        $vars = [
            "credit" => $cur->infix($nfo->format($user->get()['credit'], 2, 0, $user->getNumberFormat()), $cur->getBaseCurrency()),
        ];

        $mtObj = new MailTemplate("Guthaben-Mahnung");
        $titlex = $mtObj->getTitle($user->getLanguage());
        $mail = $mtObj->getMail($user->getLanguage(), $user->get()['name']);

        $maq->enqueue($vars, $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($user->getLanguage()));
    }
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
