<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $lang, $cur;
$l = $lang['QUOTE'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($l['TITLE']);
menu("customers");

if ($ari->check(7) && is_object($sql = $db->query("SELECT * FROM client_quotes WHERE ID = " . intval($_GET['id']))) && $sql->num_rows == 1) {
    $info = $sql->fetch_object();
    $var['quote'] = (array) $info;

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

            // VAT
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

            // Update
            $sql = $db->prepare("UPDATE client_quotes SET vat = ?, intro = ?, extro = ?, terms = ?, recipient = ?, `date` = ?, valid = ?, items = ?, duration = ? WHERE id = ?");
            $sql->bind_param("isssssssii", $vat, $intro, $extro, $terms, $recipient, $date = date("Y-m-d", strtotime($_POST['date'])), $date2 = date("Y-m-d", strtotime($_POST['valid'])), $items, $duration, $_GET['id']);
            $sql->execute();
            if ($db->errno) {
                throw new Exception($db->error);
            }

            // Update stages
            $db->query("DELETE FROM client_quote_stages WHERE quote = " . intval($_GET['id']));

            $sql = $db->prepare("INSERT INTO client_quote_stages (quote, days, percent) VALUES (?,?,?)");
            $sql->bind_param("iii", $_GET['id'], $days, $percent);

            foreach ($stages as $s) {
                $days = $s[0];
                $percent = $s[1];
                $sql->execute();
            }

            $sql->close();

            alog("quote", "changed", $_GET['id']);
            $var['success'] = $l['CHANGED'];
            unset($_POST);
            $info = $db->query("SELECT * FROM client_quotes WHERE ID = " . intval($_GET['id']))->fetch_object();
            $var['quote'] = (array) $info;
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

    $user = User::getInstance($info->client, "ID");
    if ($user) {
        $var['user'] = $user->get();
    }

    $items = $var['items'] = unserialize($info->items);
    $var['recipient'] = unserialize($info->recipient);

    $stages = [];
    $stageSql = $db->query("SELECT * FROM client_quote_stages WHERE quote = " . intval($_GET['id']) . " ORDER BY days ASC");
    while ($stageRow = $stageSql->fetch_object()) {
        $stages[] = [$stageRow->days, $stageRow->percent];
    }

    array_push($var['customJSFiles'], "invoice");
    $tpl = "quote";

    $var['cur_prefix'] = $cur->getPrefix();
    $var['cur_suffix'] = $cur->getSuffix();
    $var['languages'] = $GLOBALS['languages'];
    $var['positions'] = isset($_POST['invoiceitem_description']) && is_array($_POST['invoiceitem_description']) ? count($_POST['invoiceitem_description']) : count($items);
    $var['stages'] = $stages;

    $path = __DIR__ . "/../../files/quotes/" . $info->ID;

    if (!empty($_GET['delete_file'])) {
        @unlink($path . "/" . basename($_GET['delete_file']));
        header("Location: ?p=quote&id=" . $info->ID);
        exit;
    }

    if (!empty($_GET['download_file']) && file_exists($path . "/" . basename($_GET['download_file']))) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($_GET['download_file']) . "\"");
        readfile($path . "/" . basename($_GET['download_file']));
        exit;
    }

    if (!empty($_FILES['upload_files'])) {
        if (!file_exists($path)) {
            mkdir($path);
        }

        foreach ($_FILES["upload_files"]["name"] as $k => $name) {
            $tmp_name = $_FILES["upload_files"]["tmp_name"][$k];
            move_uploaded_file($tmp_name, $path . "/" . basename($name));
        }

        header("Location: ?p=quote&id=" . $info->ID);
        exit;
    }

    alog("quote", "viewed", $_GET['id']);

    $var['files'] = [];
    foreach (glob($path . "/*") as $f) {
        array_push($var['files'], basename($f));
    }

    $var['products'] = [];

    $sql = $db->query("SELECT name, price, description FROM products");
    while ($row = $sql->fetch_object()) {
        array_push($var['products'], [
            unserialize($row->name)[$CFG['LANG']] . " - " . $nfo->format($row->price),
            unserialize($row->description)[$CFG['LANG']],
        ]);
    }
} else {
    if (!$ari->check(7)) {
        alog("general", "insufficient_page_rights", "quote");
    }

    $tpl = "error";
}
