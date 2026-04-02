<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur, $maq;
$l = $lang['QUOTE'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($l['TITLEC']);
menu("customers");

if ($ari->check(7)) {
    $sql = $db->query("SELECT * FROM client_countries WHERE active = 1 ORDER BY name ASC");
    $var['countries'] = array();
    while ($row = $sql->fetch_object()) {
        $var['countries'][$row->ID] = $row->name;
    }

    if (isset($_POST['date'])) {
        try {
            // Dates
            if (empty($_POST['date']) || strtotime($_POST['date']) === false) {
                throw new Exception($l['ERR1']);
            }

            if (empty($_POST['valid']) || strtotime($_POST['valid']) === false) {
                throw new Exception($l['ERR2']);
            }

            // Client data
            if (empty($_POST['firstname'])) {
                throw new Exception($l['ERR3']);
            }

            if (empty($_POST['lastname'])) {
                throw new Exception($l['ERR4']);
            }

            if (empty($_POST['street'])) {
                throw new Exception($l['ERR5']);
            }

            if (empty($_POST['street_number'])) {
                throw new Exception($l['ERR6']);
            }

            if (empty($_POST['postcode'])) {
                throw new Exception($l['ERR7']);
            }

            if (empty($_POST['city'])) {
                throw new Exception($l['ERR8']);
            }

            if (!empty($_POST['mail']) && !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception($l['ERR9']);
            }

            if (empty($_POST['country']) || !array_key_exists($_POST['country'], $var['countries'])) {
                throw new Exception($l['ERR10']);
            }

            if (empty($_POST['language']) || !array_key_exists($_POST['language'], $GLOBALS['languages'])) {
                throw new Exception($l['ERR11']);
            }

            $client = isset($_GET['client']) && User::getInstance($_GET['client'], "ID") ? intval($_GET['client']) : 0;
            $recipient = array($_POST['firstname'], $_POST['lastname'], $_POST['street'], $_POST['street_number'], $_POST['postcode'], $_POST['city'], $var['countries'][$_POST['country']], $_POST['language'], $_POST['company'], $_POST['mail'] ?: "");
            $recipient = serialize($recipient);

            $duration = isset($_POST['duration']) ? ($_POST['duration'] ? "1" : "0") : "1";

            // Positions
            $sum = 0;
            $items = array();
            foreach ($_POST['invoiceitem_description'] as $k => $desc) {
                $days = $_POST['invoiceitem_time'][$k];
                $amount = $nfo->phpize($_POST['invoiceitem_amount'][$k]);

                if (empty($desc)) {
                    throw new Exception($l['ERR12']);
                }

                if ($duration && (!is_numeric($days) || $days < 0)) {
                    throw new Exception($l['ERR13']);
                }

                if (!is_numeric($amount) && !is_double($amount)) {
                    throw new Exception($l['ERR14']);
                }

                $items[] = array($desc, $days, $amount);
                $sum += $amount;
            }

            if (count($items) == 0) {
                throw new Exception($l['ERR15']);
            }

            $items = serialize($items);

            // Texts
            $intro = $_POST['intro'];
            $extro = $_POST['extro'];
            $terms = $_POST['terms'];

            // Status & VAT
            $status = isset($_POST['send_invoice']) && $_POST['send_invoice'] == "yes" && !empty($_POST['mail']) ? 1 : 0;
            $vat = isset($_POST['no_vat']) && $_POST['no_vat'] == "yes" ? 0 : 1;

            // Stage check
            $stages = [];
            $stageSum = 0;

            if (!is_array($_POST['stage_percent'])) {
                throw new Exception($l['NOT100']);
            }

            foreach ($_POST['stage_percent'] as $k => $v) {
                $v = max(0, intval($v));
                $stageSum += $v;

                $stages[] = [max(0, intval($_POST['stage_days'][$k])), $v];
            }

            if ($stageSum != 100) {
                throw new Exception($l['NOT100']);
            }

            // Insert
            $sql = $db->prepare("INSERT INTO client_quotes (client, intro, extro, terms, status, recipient, `date`, valid, items, duration, vat) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $sql->bind_param("isssissssii", $client = $_GET['client'] ?: 0, $intro, $extro, $terms, $status, $recipient, $date = date("Y-m-d", strtotime($_POST['date'])), $date2 = date("Y-m-d", strtotime($_POST['valid'])), $items, $duration, $vat);
            $sql->execute();
            if ($db->errno) {
                throw new Exception($db->error);
            }
            $sql->close();

            alog("quote", "created", $id = $db->insert_id);

            // Insert stages
            $sql = $db->prepare("INSERT INTO client_quote_stages (quote, days, percent) VALUES (?,?,?)");
            $sql->bind_param("iii", $id, $days, $percent);

            foreach ($stages as $s) {
                $days = $s[0];
                $percent = $s[1];
                $sql->execute();
            }

            $sql->close();

            // Send mail
            $send = "";
            if ($status) {
                $nr = $db->insert_id;
                $pdf = new PDFQuote($nr);
                if (file_exists(__DIR__ . "/tmp.pdf")) {
                    unlink(__DIR__ . "/tmp.pdf");
                }

                $pdf->output(__DIR__ . "/tmp.pdf");

                $mt = new MailTemplate("Ihr Angebot");
                $title = $mt->getTitle($_POST['language']);
                $mail = $mt->getMail($_POST['language'], $_POST['firstname'] . " " . $_POST['lastname']);

                while (strlen($nr) < $CFG['MIN_QUOLEN']) {
                    $nr = "0" . $nr;
                }

                $prefix = $CFG['OFFER_PREFIX'];
                $date = strtotime($_POST['date']);
                $prefix = str_replace("{YEAR}", date("Y", $date), $prefix);
                $prefix = str_replace("{MONTH}", date("m", $date), $prefix);
                $prefix = str_replace("{DAY}", date("d", $date), $prefix);

                $nr = $prefix . $nr;

                $id = $maq->enqueue([
                    "nr" => $nr,
                    "amount" => $cur->infix($nfo->format($sum), $cur->getBaseCurrency()),
                    "valid" => $dfo->format(strtotime($_POST['valid']), "", false, false),
                ], $mt, $_POST['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $client, false, 0, 0, array($nr . ".pdf" => __DIR__ . "/tmp.pdf"));
                $maq->send(1, $id, true, false);
                $maq->delete($id);

                $send = " " . $l['SENT'];
            }

            $var['success'] = $l['CREATED'] . "{$send}. " . str_replace("%i", $id, $l['LINKS']);
            unset($_POST);
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

    if (isset($_GET['client'])) {
        $user = User::getInstance($_GET['client'], "ID");
        if ($user) {
            $var['user'] = $user->get();

            foreach ($var['user'] as $k => $v) {
                if (!isset($_POST[$k])) {
                    $_POST[$k] = $v;
                }
            }

            alog("quote", "client_details", $user->get()['ID']);
        }
    }

    array_push($var['customJSFiles'], "invoice");
    $tpl = "new_quote";

    $var['cur_prefix'] = $cur->getPrefix();
    $var['cur_suffix'] = $cur->getSuffix();
    $var['languages'] = $GLOBALS['languages'];
    $var['valid'] = $dfo->format(strtotime("+7 days"), false);
    $var['positions'] = isset($_POST['invoiceitem_description']) && is_array($_POST['invoiceitem_description']) ? count($_POST['invoiceitem_description']) : 1;
    $var['stages'] = isset($_POST['stage_percent']) && is_array($_POST['stage_percent']) ? count($_POST['stage_percent']) : 1;
    $var['products'] = [];

    $sql = $db->query("SELECT name, price, description FROM products");
    while ($row = $sql->fetch_object()) {
        array_push($var['products'], [
            unserialize($row->name)[$CFG['LANG']] . " - " . $nfo->format($row->price),
            unserialize($row->description)[$CFG['LANG']],
        ]);
    }
} else {
    alog("general", "insufficient_page_rights", "new_quote");
    $tpl = "error";
}
