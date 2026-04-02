<?php
global $var, $db, $CFG, $lang, $pars, $dfo, $nfo, $addons, $cur, $user;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['TICKET']['TITLE'];
$tpl = "ticket";

$id = isset($pars[0]) ? intval($pars[0]) : 0;
$hash = isset($pars[1]) ? $pars[1] : "";

if (empty($id) || !$db->query("SELECT 1 FROM support_tickets WHERE ID = $id AND customer_access = 1")->num_rows || strlen($hash) != 16 || $hash != substr(hash("sha512", $CFG['HASH'] . "ticketview" . $id . "ticketview" . $CFG['HASH']), -16)) {
    $tpl = "error";
    $title = $lang['ERROR']['TITLE'];
    $var['error'] = $lang['TICKET']['AUTHERR'];
} else {
    $var['ti'] = $db->query("SELECT * FROM support_tickets WHERE ID = $id")->fetch_array();

    if ($var['ti']['fake_status'] >= 0) {
        $var['ti']['status'] = $var['ti']['fake_status'];
    }

    $var['ti']['lid'] = str_pad($var['ti']['ID'], 6, "0", STR_PAD_LEFT);

    $ti = $info = (object) $var['ti'];

    if (isset($_POST['rating'])) {
        if (!in_array($_POST['rating'], ["1", "2"])) {
            exit;
        }

        if ($ti->rating == -1) {
            exit;
        }

        $db->query("UPDATE support_tickets SET rating = " . intval($_POST['rating']) . " WHERE ID = $id");
        die($_POST['rating']);
    }

    if (isset($pars[2]) && $pars[2] == "close" && $ti->can_closed) {
        $db->query("UPDATE support_tickets SET status = 3 WHERE ID = $id");

        $addons->runHook("TicketClose", [
            "id" => $id,
            "ticket" => ($t = new Ticket($id)),
            "url" => $t->getURL(),
            "source" => "clientarea",
        ]);

        header('Location: ./');
        exit;
    } else if ($ti->rating != -1 && isset($pars[2])) {
        if ($pars[2] == "good") {
            $db->query("UPDATE support_tickets SET rating = 1 WHERE ID = $id");
            $var['ratingModal'] = 1;
            $var['ti']['rating'] = 1;
        } else if ($pars[2] == "bad") {
            $db->query("UPDATE support_tickets SET rating = 2 WHERE ID = $id");
            $var['badModal'] = 1;
            $var['ti']['rating'] = 2;
        }
    }

    if (!empty($_POST['answer'])) {
        $sql = $db->prepare("INSERT INTO support_ticket_answers (ticket, `time`, subject, message, priority, sender, staff) VALUES (?,?,?,?,?,?,?)");
        $sql->bind_param("isssisi", $info->ID, $a = date("Y-m-d H:i:s"), $b = "Re: [T#" . str_pad($info->ID, 6, "0", STR_PAD_LEFT) . "] " . $info->subject, $_POST['answer'], $c = 3, $d = "Webinterface", $e = 0);
        $sql->execute();
        $mid = $db->insert_id;

        $db->query("UPDATE support_tickets SET fake_status = -1, status = 0, admins_read = '', escalations = '', updated = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$info->ID}");

        $t = new Ticket($info->ID);

        $addons->runHook("TicketAnswer", [
            "aid" => $mid,
            "tid" => $info->ID,
            "ticket" => $t,
            "url" => $t->getURL(),
            "source" => "clientarea",
            "who" => "client",
        ]);

        $t->notify("answer");

        $var['ti']['status'] = 0;

        if (is_array($_FILES['attachments']) && count($_FILES['attachments']['name']) > 0) {
            foreach ($_FILES['attachments']['name'] as $k => $name) {
                if (empty($name)) {
                    continue;
                }

                $path = basename(time() . "-" . rand(10000000, 99999999) . "-" . $name);
                file_put_contents(__DIR__ . "/../files/tickets/$path", file_get_contents($_FILES['attachments']['tmp_name'][$k]));
                $db->query("INSERT INTO support_ticket_attachments (message, name, file) VALUES ($mid, '" . $db->real_escape_string($name) . "', 'file#" . $db->real_escape_string($path) . "')");
            }
        }

        header('Location: ./' . $hash);
        exit;
    }

    if (isset($pars[2]) && is_numeric($pars[2]) && $pars[2] > 0) {
        $sql = $db->query("SELECT * FROM support_ticket_attachments WHERE ID = " . intval($pars[2]));
        if ($sql->num_rows == 1) {
            $f = $sql->fetch_object();
            if ($db->query("SELECT 1 FROM support_ticket_answers WHERE ID = {$f->message} AND ticket = $id AND staff >= 0")->num_rows == 1) {
                header('Content-disposition: attachment; filename="' . $f->name . '"');
                header('Content-type: application/octet-stream');
                header("Pragma: no-cache");
                header("Expires: 0");

                if (substr($f->file, 0, 5) == "file#") {
                    echo file_get_contents(__DIR__ . "/../files/tickets/" . basename(substr($f->file, 5)));
                } else {
                    echo $f->file;
                }

                exit;
            }
        }
    }

    require_once __DIR__ . "/../lib/HTMLPurifier/HTMLPurifier.auto.php";
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);

    $sql = $db->query("SELECT * FROM support_ticket_answers WHERE ticket = $id AND staff >= 0 ORDER BY `time` DESC");
    $a = [];
    while ($row = $sql->fetch_object()) {
        $attachments = [];

        $sql2 = $db->query("SELECT ID, name, file FROM support_ticket_attachments WHERE message = " . $row->ID);
        while ($row2 = $sql2->fetch_object()) {
            $fileLen = strlen($row2->file);
            if (substr($row2->file, 0, 5) == "file#") {
                $fileLen = strlen(file_get_contents(__DIR__ . "/../files/tickets/" . basename(substr($row2->file, 5))));
            }

            $attachments[$row2->ID] = array($row2->name, $nfo->format($fileLen / 1024, 2));
        }

        $a[] = [
            $dfo->format($row->time),
            $row->staff != 0,
            $purifier->purify($row->message),
            trim(explode("<", $row->sender)[0]),
            $attachments,
        ];
    }
    $var['a'] = $a;
    $var['pars'] = $pars;

    $var['above'] = implode("", $addons->runHook("TicketViewAbove", [
        "tid" => $info->ID,
        "ticket" => ($t = new Ticket($id)),
    ]));

    $var['upgrades'] = [];
    $sql = $db->query("SELECT * FROM support_upgrades ORDER BY price ASC, name ASC");
    while ($row = $sql->fetch_object()) {
        if (!in_array($info->status, explode(",", $row->status))) {
            continue;
        }

        if (!in_array($info->dept, explode(",", $row->department))) {
            continue;
        }

        if ($row->link && substr($row->link, 0, 7) != "http://" && substr($row->link, 0, 8) != "https://") {
            $row->link = $CFG['PAGEURL'] . $row->link;
        }

        $row->price_formatted = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $row->price)));

        $var['upgrades'][$row->ID] = (array) $row;
    }

    if ($info->upgrade_id) {
        $sql = $db->query("SELECT name FROM support_upgrades WHERE ID = {$info->upgrade_id}");
        if ($sql->num_rows) {
            $var['ugn'] = htmlentities($sql->fetch_object()->name);
        }
    }

    if ($var['logged_in'] && isset($_GET['order_upgrade']) && array_key_exists($_GET['order_upgrade'], $var['upgrades'])) {
        $upgrade = (object) $var['upgrades'][$_GET['order_upgrade']];

        try {
            if ($user->getLimit() < $upgrade->price) {
                throw new Exception($lang['TICKET']['UGE1']);
            }

            $inv = new Invoice;
            $inv->setClient($user->get()['ID']);
            $inv->setDate(date("Y-m-d"));
            $inv->setDueDate();

            $item = new InvoiceItem;
            $item->setDescription("<b>" . $lang['TICKET']['UPGRADE_POS1'] . " T#" . $var['ti']['lid'] . "</b><br />" . $lang['TICKET']['UPGRADE_POS2'] . " " . htmlentities($upgrade->name));
            $item->setAmount($upgrade->price);
            $item->save();

            $inv->addItem($item);
            $inv->save();

            $inv->applyCredit(false);
            $inv->save();
            $inv->send();

            $var['ti']['upgrade_id'] = $info->upgrade_id = $upgrade->ID;
            $db->query("UPDATE support_tickets SET upgrade_id = {$info->upgrade_id} WHERE ID = {$info->ID}");

            if ($upgrade->new_priority != -1) {
                $var['ti']['upgrade_prio_before'] = $info->upgrade_prio_before = $info->priority;
                $var['ti']['priority'] = $info->priority = $upgrade->new_priority;
                $db->query("UPDATE support_tickets SET priority = {$info->priority}, upgrade_prio_before = {$info->upgrade_prio_before} WHERE ID = {$info->ID}");
            }

            $var['ugn'] = htmlentities($upgrade->name);

            $var['sucmsg'] = $lang['TICKET']['UPGRADE_OK'];
        } catch (Exception $ex) {
            $var['errormsg'] = $ex->getMessage();
        }
    }
}
