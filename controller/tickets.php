<?php
global $var, $db, $CFG, $lang, $pars, $dfo, $nfo, $user, $addons, $maq;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$title = $lang['TICKETS']['TITLE'];
$tpl = "tickets";

$var['step'] = "";
if (isset($pars[0]) && $pars[0] == "add") {
    $var['step'] = "add";
    $var['depts'] = [];
    $var['priorities'] = [];

    $var['owners'] = [$user->get()['ID'] => $CFG['CNR_PREFIX'] . $user->get()['ID'] . " - " . $user->get()['name'] . (($c = $user->get()['company']) ? " ($c)" : "")];

    foreach ($user->impersonate("tickets") as $im) {
        $imObj = User::getInstance($im, "ID");
        $var['owners'][$im] = $CFG['CNR_PREFIX'] . $imObj->get()['ID'] . " - " . $imObj->get()['name'] . ($c = $imObj->get()['company'] ? " ($c)" : "");
    }

    for ($i = 5; $i > 0; $i--) {
        $var['priorities'][$i] = $lang['TICKETS']['PRIO' . $i];
    }

    $sql = $db->query("SELECT ID, name FROM support_departments WHERE public = 1 ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        $var['depts'][$row->ID] = $row->name;
    }

    if (isset($_POST['subject'])) {
        try {
            if (empty($_POST['dept']) || !array_key_exists($_POST['dept'], $var['depts'])) {
                throw new Exception($lang['TICKETS']['ERRDEPT']);
            }

            if (empty($_POST['priority']) || !in_array($_POST['priority'], ["1", "2", "3", "4", "5"])) {
                throw new Exception($lang['TICKETS']['ERRPRIO']);
            }

            if (empty($_POST['subject'])) {
                throw new Exception($lang['TICKETS']['ERRSUBJ']);
            }

            if (empty($_POST['answer']) || strlen($_POST['answer']) < 30) {
                throw new Exception($lang['TICKETS']['ERRANSW']);
            }

            $priority = intval($user->getSupportPrio($_POST['priority']));

            $owner = !empty($_POST['owner']) && array_key_exists($_POST['owner'], $var['owners']) ? $_POST['owner'] : $user->get()['ID'];

            $fromc = $user->get()['name'] . " <" . $user->get()['mail'] . ">";
            $db->query("INSERT INTO support_tickets (subject, dept, created, updated, priority, sender, customer, cc, status) VALUES ('" . $db->real_escape_string($_POST['subject']) . "', " . intval($_POST['dept']) . ", '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', " . $priority . ", '" . $db->real_escape_string($fromc) . "', " . intval($owner) . ", '', 0)");
            $tid = $db->insert_id;

            $sql = $db->prepare("INSERT INTO support_ticket_answers (ticket, `time`, subject, message, priority, sender, staff) VALUES (?,?,?,?,?,?,?)");
            $sql->bind_param("isssisi", $tid, $a = date("Y-m-d H:i:s"), $b = $_POST['subject'], $_POST['answer'], $_POST['priority'], $d = "Webinterface", $e = 0);
            $sql->execute();
            $mid = $db->insert_id;

            $t = new Ticket($tid);
            $t->notify("new");

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

            $url = $CFG['PAGEURL'] . "ticket/" . $tid . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $tid . "ticketview" . $CFG['HASH']), -16);

            $deptInfo = $t->getDepartmentInfo();
            if ($deptInfo && $deptInfo->confirmation) {
                $cetSql = $db->query("SELECT 1 FROM email_templates WHERE ID = {$deptInfo->confirmation} AND category = 'Eigene'");
                if ($cetSql->num_rows) {
                    $lang = $CFG['LANG'];
                    if (isset($user) && $user instanceof User) {
                        $lang = $user->getLanguage();
                    }

                    $mtObj = new MailTemplate($deptInfo->confirmation);
                    $title = $mtObj->getTitle($lang);
                    $mail = $mtObj->getMail($lang, $user->get()['name']);

                    $maq->enqueue([
                        "ticket_id" => $tid,
                        "ticket_subject" => $t->getSubject(),
                        "ticket_department" => $t->getDepartmentName(),
                    ], $mtObj, $user->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $user->get()['ID'], false, 0, 0, $mtObj->getAttachments($lang));
                }
            }

            $addons->runHook("TicketCreated", [
                "id" => $tid,
                "ticket" => $t,
                "url" => $url,
                "source" => "clientarea",
            ]);

            header('Location: ' . $url);
            exit;
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }
} else {
    $var['t'] = [];

    if (!empty($_POST['searchword']) && strlen($_POST['searchword']) >= 4) {
        $var['search'] = true;
        $sw = $db->real_escape_string($_POST['searchword']);

        $sql = $db->query("SELECT * FROM support_tickets WHERE customer = " . $user->get()['ID'] . " AND customer_access = 1 AND `subject` LIKE '%$sw%' ORDER BY ID DESC");
    } else {
        $var['search'] = false;
        $sql = $db->query("SELECT * FROM support_tickets WHERE customer = " . $user->get()['ID'] . " AND customer_access = 1 ORDER BY ID DESC");
    }

    while ($row = $sql->fetch_array()) {
        $row['url'] = $CFG['PAGEURL'] . "ticket/" . $row['ID'] . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $row['ID'] . "ticketview" . $CFG['HASH']), -16);
        $row['t'] = new Ticket($row['ID']);
        $row['lid'] = str_pad($row['ID'], 6, "0", STR_PAD_LEFT);
        array_push($var['t'], $row);
    }
}
