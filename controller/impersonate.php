<?php
global $db, $CFG, $user, $pars, $lang, $dfo, $nfo, $cur, $raw_cfg;

User::status();

$im = $user->impersonate();
$uid = intval($pars[0] ?? 0);

if (!empty($uid) && in_array($uid, $im)) {
    $imObj = User::getInstance($uid, "ID");
    $var['uid'] = $uid;

    $tpl = "impersonate";
    $title = htmlentities($imObj->get()['name']);

    $rights = [];

    $sql = $db->query("SELECT rights FROM client_contacts WHERE client = $uid AND mail LIKE '" . $db->real_escape_string($user->get()['mail']) . "'");
    while ($row = $sql->fetch_object()) {
        $mr = explode(",", $row->rights);
        foreach ($mr as $right) {
            if (!in_array($mr, $rights)) {
                array_push($rights, $right);
            }
        }
    }

    $var['rights'] = $rights;

    if (in_array("products", $rights)) {
        $var['hosting'] = array();
        $sql = $db->query("SELECT * FROM client_products WHERE user = " . $uid . " AND `type` = 'h' ORDER BY active = -2 ASC, active ASC, date DESC, ID DESC");
        while ($row = $sql->fetch_object()) {
            $pSql = $db->query("SELECT name FROM products WHERE ID = {$row->product}");
            if ($pSql->num_rows != 1) {
                continue;
            }

            $pInfo = $pSql->fetch_object();

            $var['hosting'][$row->ID] = array(
                $row->date,
                $row->name ?: unserialize($pInfo->name)[$CFG['LANG']],
                $row->description,
                $cur->infix($nfo->format($cur->convertAmount(null, $row->price, null))),
                $row->billing,
                !empty($row->error) ? -3 : $row->active,
                $row->cancellation_date,
                "payment" => $row->payment,
            );
        }
    }

    if (in_array("domains", $rights)) {
        $var['domains'] = array();
        $sql = $db->query("SELECT * FROM domains WHERE user = " . $uid . " ORDER BY domain ASC");
        while ($row = $sql->fetch_object()) {
            $t = $user->getVAT();
            if (is_array($t) && count($t) == 2 && doubleval($t[1]) == $t[1]) {
                $row->recurring = $row->recurring * (1 + $t[1] / 100);
            }

            $row->inclusive = false;
            if ($row->inclusive_id > 0 && $db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1) {
                $row->inclusive = true;
            }

            $var['domains'][$row->ID] = $row;
        }
        $var['cur'] = $cur;
        $var['nfo'] = $nfo;
    }

    if (in_array("tickets", $rights)) {
        $var['t'] = [];
        $sql = $db->query("SELECT * FROM support_tickets WHERE customer = " . $uid  . " AND customer_access = 1 ORDER BY ID DESC");
        while ($row = $sql->fetch_array()) {
            $row['real_url'] = $CFG['PAGEURL'] . "ticket/" . $row['ID'] . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $row['ID'] . "ticketview" . $CFG['HASH']), -16);
            $row['url'] = $CFG['PAGEURL'] . "impersonate/$uid/tickets/" . $row['ID'];
            $row['t'] = new Ticket($row['ID']);
            $var['t'][$row['ID']] = $row;
        }

        if (($pars[1] ?? "") == "tickets" && array_key_exists($tid = intval($pars[2] ?? 0), $var['t'])) {
            $t = $var['t'][$tid];

            while(strlen($tid) < 6) {
                $tid = "0$tid";
            }

            $user->log("[#$uid] Ticket T#$tid aufgerufen");
            $imObj->log("[#{$user->get()['ID']}] Ticket T#$tid aufgerufen");

            header('Location: ' . $t['real_url']);
            exit;
        }
    }

    if (in_array("invoices", $rights)) {
        $var['invoices'] = [];

        foreach ($imObj->getInvoices() as $inv) {
            $var['invoices'][$inv->getId()] = $inv;
        }

        if (($pars[1] ?? "") == "invoices" && array_key_exists($iid = intval($pars[2] ?? 0), $var['invoices'])) {
            $user->log("[#$uid] Rechnung #$iid heruntergeladen");
            $imObj->log("[#{$user->get()['ID']}] Rechnung #$iid heruntergeladen");

            $pdf = new PDFInvoice;
            $pdf->add($var['invoices'][$iid]);
            $pdf->output($var['invoices'][$iid]->getInvoiceNo());
        }
    }

    if (in_array("quotes", $rights)) {
        $var['quotes'] = [];
        $sql = $db->query("SELECT * FROM client_quotes WHERE client = " . $uid . " AND status = 0 AND valid >= '" . date("Y-m-d") . "' ORDER BY ID DESC");
        while ($row = $sql->fetch_object()) {
            $pdf = new PDFQuote($row->ID);
            $sum = $pdf->getSum();

            $prefix = $CFG['OFFER_PREFIX'];
            $date = strtotime($row->date);
            $prefix = str_replace("{YEAR}", date("Y", $date), $prefix);
            $prefix = str_replace("{MONTH}", date("m", $date), $prefix);
            $prefix = str_replace("{DAY}", date("d", $date), $prefix);

            $var['quotes'][$row->ID] = [
                $row->ID,
                $prefix . str_pad($row->ID, $CFG['MIN_QUOLEN'], "0", STR_PAD_LEFT),
                $dfo->format($row->date, "", false, false),
                $dfo->format($row->valid, "", false, false),
                $cur->infix($nfo->format($cur->convertAmount(null, $sum))),
            ];
        }

        if (($pars[1] ?? "") == "quotes" && array_key_exists($iid = intval($pars[2] ?? 0), $var['quotes'])) {
            $user->log("[#$uid] Angebot #$iid heruntergeladen");
            $imObj->log("[#{$user->get()['ID']}] Angebot #$iid heruntergeladen");

            $pdf = new PDFQuote($iid);
            $pdf->output("", true);
            exit;
        }
    }

    if (in_array("emails", $rights)) {
        $mails = Array();
        $sql = $db->query("SELECT * FROM client_mails WHERE sent >= time AND user = " . $uid . " AND resend = 0 ORDER BY sent DESC");
        if ($sql->num_rows > 0) {
            while ($mail = $sql->fetch_array()) {
                $mail['real_url'] = $raw_cfg['PAGEURL'] . "email/" . $mail['ID'] . "/" . substr(hash("sha512", "email_view" . $mail['ID'] . $CFG['HASH']), 0, 10);
                $mail['url'] = $CFG['PAGEURL'] . "impersonate/$uid/emails/" . $mail['ID'];
                $mails[$mail['ID']] = $mail;
            }
        }
        $var['mails'] = $mails;

        if (($pars[1] ?? "") == "emails" && array_key_exists($tid = intval($pars[2] ?? 0), $var['mails'])) {
            $t = $var['mails'][$tid];

            $user->log("[#$uid] E-Mail #$tid aufgerufen");
            $imObj->log("[#{$user->get()['ID']}] E-Mail #$tid aufgerufen");

            header('Location: ' . $t['real_url']);
            exit;
        }
    }
} else {
    $tpl = "error";
    $title = $lang['ERROR']['TITLE'];
}