<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Cronjob for POP3 import
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

// Recall handling
$sql = $db->query("SELECT ID FROM support_tickets WHERE recall != 0 AND recall <= " . time());
while ($row = $sql->fetch_object()) {
    $db->query("UPDATE support_tickets SET status = 0, admins_read = '' WHERE ID = {$row->ID} LIMIT 1");
}

// Get filters
$filters = [];
$sql = $db->query("SELECT * FROM support_filter");
while ($row = $sql->fetch_object()) {
    $filters[] = $row;
}

// Connection routine
imap_timeout(IMAP_OPENTIMEOUT, 5);
imap_timeout(IMAP_READTIMEOUT, 5);
imap_timeout(IMAP_WRITETIMEOUT, 5);
imap_timeout(IMAP_CLOSETIMEOUT, 5);

$sql = $db->query("SELECT * FROM support_email WHERE pop3 = 1");
while ($row = $sql->fetch_object()) {
    @$imap = imap_open("{" . $row->pop3_host . ":" . $row->pop3_port . "/pop3" . ($row->pop3_ssl ? "/ssl/novalidate-cert" : "") . "}INBOX", $row->pop3_user, decrypt($row->pop3_password));
    if (!$imap) {
        continue;
    }

    $num = imap_num_msg($imap);

    for ($i = 0; $i < $num; $i++) {
        $p = new PhpMimeMailParser\Parser();
        $p->setText(imap_fetchbody($imap, $i + 1, ""));
        $header = imap_header($imap, $i + 1);
        $status = 0;

        // Apply filters
        foreach ($filters as $f) {
            // Check for criteria
            $v = $f->field == "subject" ? $p->getHeader('subject') : $header->from[0]->mailbox . "@" . $header->from[0]->host;
            if ($f->type == "is" && $v != $f->value) {
                continue;
            }

            if ($f->type == "contains" && strpos($v, $f->value) === false) {
                continue;
            }

            // Perform action
            if ($f->action == "close") {
                $status = 3;
            } else if ($f->action == "delete") {
                imap_delete($imap, $i + 1);
                continue 2;
            }
        }

        // Catchall routine for determining department
        if ($row->catchall) {
            $recipients = array();
            $ex = explode(",", $p->getHeader('to') . "," . $p->getHeader('cc'));
            foreach ($ex as $e) {
                $ex2 = explode("<", $e);
                $e = array_pop($ex2);
                $e = trim(trim($e, ">"));
                if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $e = strtolower($e);

                if (!in_array($e, $recipients)) {
                    array_push($recipients, $e);
                }

            }

            $f = false;
            foreach ($recipients as $r) {
                $sql2 = $db->query("SELECT dept FROM support_email WHERE email LIKE '" . $db->real_escape_string($r) . "'");
                if (!$sql2->num_rows) {
                    continue;
                }

                $row->dept = $sql2->fetch_object()->dept;
                unset($recipients[array_search(strtolower($r), array_map('strtolower', $recipients))]);
                $f = true;
                break;
            }

            if (!$f) {
                imap_delete($imap, $i + 1);
                continue;
            }

            $cc = $recipients;
        } else {
            $cc = array();
            $ex = explode(",", $p->getHeader('to') . "," . $p->getHeader('cc'));
            foreach ($ex as $e) {
                $ex2 = explode("<", $e);
                $e = array_pop($ex2);
                $e = trim(trim($e, ">"));
                if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $e = strtolower($e);

                if (!in_array($e, $cc)) {
                    array_push($cc, $e);
                }
            }
        }

        $subject = $p->getHeader('subject');
        $time = $header->udate;

        $fromArr = $p->getAddresses('from');
        $fromArr = array_values($fromArr)[0];

        $from = $fromArr["address"];
        $name = $fromArr["display"];

        $fromc = (!empty($name) ? $name . " <" : "") . $from . (!empty($name) ? ">" : "");

        $body = $p->getMessageBody('text');
        $html = $p->getMessageBody('html');
        if (strlen($html) > strlen($body)) {
            $body = $html;
        }

        $customerAccess = null;
        $userOverwrite = 0;
        $rating = 0;
        foreach ($addons->runHook("SupportMessageImport", [
            "from" => $fromc,
            "fromAddress" => $from,
            "fromName" => $name,
            "body" => $body,
            "subject" => $subject,
            "time" => $time,
        ]) as $l) {
            if (!empty($l) && is_array($l)) {
                if (isset($l['stop'])) {
                    continue 2;
                }

                if (isset($l['fromAddress'])) {
                    $from = $l['fromAddress'];
                }

                if (isset($l['fromName'])) {
                    $name = $l['fromName'];
                }

                if (isset($l['body'])) {
                    $body = $l['body'];
                }

                if (isset($l['subject'])) {
                    $subject = $l['subject'];
                }

                if (isset($l['time'])) {
                    $time = $l['time'];
                }

                if (isset($l['user'])) {
                    $userOverwrite = $l['user'];
                }

                if (isset($l['customer_access'])) {
                    $customerAccess = $l['customer_access'];
                }

                if (isset($l['rating'])) {
                    $rating = $l['rating'];
                }

                break;
            }
        }

        $fromc = (!empty($name) ? $name . " <" : "") . $from . (!empty($name) ? ">" : "");

        $ph = imap_fetchheader($imap, $i + 1);
        $ex = explode("\n", $ph);
        $priority = 3;
        for ($i2 = 0; $i2 < count($ex); $i2++) {
            $l = trim($ex[$i2]);
            if (substr($l, 0, 12) != "X-Priority: ") {
                continue;
            }

            if (intval(substr($l, 12, 1)) == substr($l, 12, 1)) {
                $priority = substr($l, 12, 1);
            }

        }

        $message_number = $i + 1;
        $connection = $imap;

        $d = false;
        if (strpos($subject, "[T#") !== false) {
            $id = ltrim(intval(substr($subject, strpos($subject, "[T#") + 3)), "0");
            if ($db->query("SELECT 1 FROM support_tickets WHERE ID = $id")->num_rows == 1) {
                if (!is_numeric($priority) || $priority > $db->query("SELECT priority FROM support_tickets WHERE ID = $id")->fetch_object()->priority) {
                    $priority = $db->query("SELECT priority FROM support_tickets WHERE ID = $id")->fetch_object()->priority;
                }

                $cc_old = $db->query("SELECT cc FROM support_tickets WHERE ID = $id")->fetch_object()->cc;
                $cc_old = explode(",", $cc_old);
                foreach ($cc_old as &$v) {
                    $v = trim($v);
                }

                $sender = $db->query("SELECT sender FROM support_tickets WHERE ID = $id")->fetch_object()->sender;
                $ex = explode("<", $sender);
                $sender = array_pop($ex);
                $sender = trim(trim($sender, ">"));

                foreach ($cc as $v) {
                    $v = trim($v);
                    if (filter_var($v, FILTER_VALIDATE_EMAIL) && !in_array($v, $cc_old) && strtolower($v) != strtolower($sender)) {
                        array_push($cc_old, $v);
                    }
                }

                $cc_old = array_unique($cc_old);
                if (false !== ($pos = array_search($sender, $cc_old))) {
                    unset($cc_old[$sender]);
                }

                $cc = implode(", ", $cc_old);
                $cc = trim($cc, ", ");

                $customer = $db->query("SELECT customer FROM support_tickets WHERE ID = $id")->fetch_object()->customer;
                if ($user = User::getInstance($customer, "ID")) {
                    $priority = $user->getSupportPrio($priority);
                }

                $db->query("UPDATE support_tickets SET status = $status, fake_status = -1, updated = '" . date("Y-m-d H:i:s", $time) . "', priority = $priority, " . ($status == 0 ? "admins_read = ''," : "") . " cc = '" . $db->real_escape_string($cc) . "', escalations = '' WHERE ID = $id");
                $db->query("INSERT INTO support_ticket_answers (ticket, subject, message, priority, sender, time) VALUES ($id, '" . $db->real_escape_string($subject) . "', '" . $db->real_escape_string($body) . "', $priority, '" . $db->real_escape_string($fromc) . "', '" . date("Y-m-d H:i:s", $time) . "')");
                $iid = $db->insert_id;

                $t = new Ticket($id);

                $addons->runHook("TicketAnswer", [
                    "aid" => $iid,
                    "tid" => $id,
                    "ticket" => $t,
                    "url" => $t->getURL(),
                    "source" => "email",
                    "who" => "client",
                ]);

                $t->notify("answer");

                $d = true;
            }
        }

        if (!$d) {
            if ($userOverwrite) {
                $customer = $userOverwrite;
            } else {
                $customer = 0;
                $sql = $db->query("SELECT ID FROM clients WHERE mail = '" . $db->real_escape_string($from) . "'");
                if ($sql->num_rows == 1) {
                    $customer = $sql->fetch_object()->ID;
                }

                if (!$customer) {
                    $sql = $db->query("SELECT client FROM client_contacts WHERE mail = '" . $db->real_escape_string($from) . "'");
                    if ($sql->num_rows == 1) {
                        $customer = $sql->fetch_object()->client;
                    }

                }
            }

            $cc = array_unique($cc);
            $cc = implode(", ", $cc);
            $cc = trim($cc, ", ");

            if ($customerAccess === null || $customerAccess) {
                $customerAccess = 1;
            } else {
                $customerAccess = 0;
            }

            if ($user = User::getInstance($customer, "ID")) {
                $priority = $user->getSupportPrio($priority);
            }

            $db->query("INSERT INTO support_tickets (subject, dept, created, updated, priority, sender, customer, cc, status, customer_access, rating) VALUES ('" . $db->real_escape_string($subject) . "', {$row->dept}, '" . date("Y-m-d H:i:s", $time) . "', '" . date("Y-m-d H:i:s", $time) . "', $priority, '" . $db->real_escape_string($fromc) . "', $customer, '" . $db->real_escape_string($cc) . "', $status, $customerAccess, $rating)");
            $id = $db->insert_id;
            $db->query("INSERT INTO support_ticket_answers (ticket, subject, message, priority, sender, time) VALUES ($id, '" . $db->real_escape_string($subject) . "', '" . $db->real_escape_string($body) . "', $priority, '" . $db->real_escape_string($fromc) . "', '" . date("Y-m-d H:i:s", $time) . "')");
            $iid = $db->insert_id;

            $ex = explode("<", $fromc);
            $sender = trim(array_pop($ex), "<>");

            $t = new Ticket($id);

            $newTicket = true;
        }

        foreach ($p->getAttachments() as $attachment) {
            $path = basename(time() . "-" . rand(10000000, 99999999) . "-" . basename($attachment->getFilename()));
            file_put_contents(__DIR__ . "/../../files/tickets/$path", $attachment->getContent());
            $db->query("INSERT INTO support_ticket_attachments (message, name, file) VALUES ($iid, '" . $db->real_escape_string($attachment->getFilename()) . "', 'file#" . $db->real_escape_string($path) . "')");
        }

        $deptInfo = $t->getDepartmentInfo();
        if ($deptInfo && $deptInfo->confirmation) {
            $cetSql = $db->query("SELECT 1 FROM email_templates WHERE ID = {$deptInfo->confirmation} AND category = 'Eigene'");
            if ($cetSql->num_rows) {
                $lang = $CFG['LANG'];
                if (isset($user) && $user instanceof User) {
                    $lang = $user->getLanguage();
                    $name = $user->get()['name'];
                }

                $mtObj = new MailTemplate($deptInfo->confirmation);
                $title = $mtObj->getTitle($lang);
                $mail = $mtObj->getMail($lang, $name);

                $maq->enqueue([
                    "ticket_id" => $id,
                    "ticket_subject" => $t->getSubject(),
                    "ticket_department" => $t->getDepartmentName(),
                ], $mtObj, $sender, $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", isset($user) && is_object($user) ? $user->get()['ID'] : 0, false, 0, 0, $mtObj->getAttachments($lang));
            }
        }

        if (isset($newTicket)) {
            $addons->runHook("TicketCreated", [
                "id" => $id,
                "ticket" => $t,
                "url" => $t->getURL(),
                "source" => "email",
            ]);

            $t->notify();
        }

        imap_delete($imap, $message_number);
    }

    imap_close($imap, CL_EXPUNGE);
}

// Execute auto-close
if (intval($CFG['SUPPORT_AUTOCLOSE']) == $CFG['SUPPORT_AUTOCLOSE'] && $CFG['SUPPORT_AUTOCLOSE'] > 0) {
    $hours = intval($CFG['SUPPORT_AUTOCLOSE']);
    $sql = $db->query("SELECT ID FROM support_tickets WHERE status = 2 AND can_closed = 1");
    while ($row = $sql->fetch_object()) {
        $t = new Ticket($row->ID);
        $t = $t->getLastAnswer();
        if ($t <= time() - 3600 * $hours) {
            $db->query("UPDATE support_tickets SET status = 3 WHERE ID = {$row->ID}");

            $addons->runHook("TicketClose", [
                "id" => $row->ID,
                "ticket" => ($t = new Ticket($row->ID)),
                "url" => $t->getURL(),
                "source" => "autoclose",
            ]);
        }
    }
}

// Send rating mail
if (intval($CFG['SUPPORT_RATING']) == $CFG['SUPPORT_RATING'] && $CFG['SUPPORT_RATING'] > 0 && intval($CFG['SUPPORT_RATING_MAIL']) == $CFG['SUPPORT_RATING_MAIL'] && $CFG['SUPPORT_RATING_MAIL'] > 0) {
    $hours = intval($CFG['SUPPORT_RATING']);
    $sql = $db->query("SELECT ID, customer, sender, subject FROM support_tickets WHERE status = 3 AND rating = 0");
    while ($row = $sql->fetch_object()) {
        $t = new Ticket($row->ID);
        $t = $t->getLastAnswer();
        if ($t <= time() - 3600 * $hours) {
            $db->query("UPDATE support_tickets SET rating = 3 WHERE ID = {$row->ID}");
            $language = $CFG['LANG'];

            if ($row->customer > 0 && ($u = User::getInstance($row->customer, "ID")) && !empty($l = $u->get()['language']) && file_exists(__DIR__ . "/../../languages/" . basename($l) . ".php")) {
                $language = $l;
            }

            if ($db->query("SELECT 1 FROM testimonials WHERE author = " . intval($row->customer))->num_rows) {
                continue;
            }

            $mt = new MailTemplate($CFG['SUPPORT_RATING_MAIL']);
            $title = $mt->getTitle($language);
            $mail = $mt->getMail($language, (isset($u) && $u instanceof User ? $u->get()['name'] : trim(explode("<", $row->sender)[0])));

            $link = $CFG['PAGEURL'] . "ticket/" . $row->ID . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $row->ID . "ticketview" . $CFG['HASH']), -16);

            $maq->enqueue([
                "subject" => $row->subject,
                "link_good" => $link . "/good",
                "link_bad" => $link . "/bad",
            ], $mt, isset($u) && $u instanceof User ? $u->get()['mail'] : trim(trim(explode("<", $row->sender)[1], ">")), $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $row->customer);
        }
    }
}

// Send escalations
Ticket::escalateTickets();

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
