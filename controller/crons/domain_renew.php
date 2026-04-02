<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching domains which expires in 7 days\n", FILE_APPEND);

// Active domains expiring in 7 days (just a reminder)
$sql = $db->query("SELECT * FROM domains WHERE status IN ('REG_OK', 'KK_OK') AND expiration = '" . date("Y-m-d", strtotime("+7 days")) . "' ORDER BY ID ASC");
while ($row = $sql->fetch_object()) {
    // Skip inclusive domains for active products
    if ($row->inclusive_id > 0 && $db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1) {
        continue;
    }

    // Get user instance
    $u = new User($row->user, "ID");
    if ($u->get()["ID"] != $row->user) {
        continue;
    }

    // Find appropriate email
    $mtObj = null;
    if (!$row->auto_renew) {
        $mtObj = new MailTemplate("Auslauf-Warnung (manuell)");
    }

    // Send email
    if ($mtObj instanceof MailTemplate) {
        $title = $mtObj->getTitle($u->getLanguage());
        $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

        $maq->enqueue([
            "domain" => $row->domain,
            "url" => $CFG['PAGEURL'] . "domain/" . str_replace(".", "/", $row->domain),
            "date" => $dfo->format($row->expiration, false, false, "", $u->getDateFormat()),
        ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
    }
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching domains which expires in 3 days\n", FILE_APPEND);

// Active domains expiring within 3 days (just another reminder)
$sql = $db->query("SELECT * FROM domains WHERE status IN ('REG_OK', 'KK_OK') AND expiration = '" . date("Y-m-d", strtotime("+3 days")) . "' ORDER BY ID ASC");
while ($row = $sql->fetch_object()) {
    // Skip inclusive domains for active products
    if ($row->inclusive_id > 0 && $db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1) {
        continue;
    }

    // Get user instance
    $u = new User($row->user, "ID");
    if ($u->get()["ID"] != $row->user) {
        continue;
    }

    // Find appropriate email
    $mtObj = null;
    if (!$row->auto_renew) {
        $mtObj = new MailTemplate("Auslauf-Warnung (manuell)");
    }

    // Send email
    if ($mtObj instanceof MailTemplate) {
        $title = $mtObj->getTitle($u->getLanguage());
        $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

        $maq->enqueue([
            "domain" => $row->domain,
            "url" => $CFG['PAGEURL'] . "domain/" . str_replace(".", "/", $row->domain),
            "date" => $dfo->format($row->expiration, false, false, "", $u->getDateFormat()),
        ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
    }
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching domains which expires in 1 day\n", FILE_APPEND);

// Active domains expiring tomorrow (handle all the renew things)
$sql = $db->query("SELECT * FROM domains WHERE status IN ('REG_OK', 'KK_OK') AND expiration = '" . date("Y-m-d", strtotime("+1 days")) . "' ORDER BY ID ASC");
while ($row = $sql->fetch_object()) {
    // Skip inclusive domains for active products
    if ($row->inclusive_id > 0 && $db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1) {
        continue;
    }

    // Get user instance
    $u = new User($row->user, "ID");
    if ($u->get()["ID"] != $row->user) {
        continue;
    }
    $reg->setUser($u);

    // Send reminder
    $mtObj = null;
    if (!$row->auto_renew) {
        $mtObj = new MailTemplate("Auslauf-Warnung (manuell)");
    }

    // Send email
    if ($mtObj instanceof MailTemplate) {
        $title = $mtObj->getTitle($u->getLanguage());
        $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

        $maq->enqueue([
            "domain" => $row->domain,
            "url" => $CFG['PAGEURL'] . "domain/" . str_replace(".", "/", $row->domain),
            "date" => $dfo->format($row->expiration, false, false, "", $u->getDateFormat()),
        ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
    }
}

// Invoice due domains
$users = [];

$sql = $db->query("SELECT * FROM domains WHERE status IN ('REG_OK', 'KK_OK') AND expiration <= '" . date("Y-m-d") . "' AND expiration >= '" . date("Y-m-d", strtotime("-20 years")) . "' ORDER BY ID ASC");
while ($row = $sql->fetch_object()) {
    // Skip inclusive domains for active products
    if ($row->inclusive_id > 0 && $db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1) {
        continue;
    }

    // Get user instance
    if (!array_key_exists($row->user, $users)) {
        $users[$row->user] = new User($row->user, "ID");
        if (!$users[$row->user]) {
            continue;
        }
    }

    // Invoice domain
    list($sld, $tld) = explode(".", $row->domain, 2);
    $period = 1;
    $periodSql = $db->query("SELECT `period` FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
    if ($periodSql->num_rows) {
        $period = max(1, intval($periodSql->fetch_object()->period));
    }

    $from = $dfo->format($row->expiration, false, false);
    $to = $dfo->format(strtotime("+$period year, -1 day", strtotime($row->expiration)), false, false);
    $users[$row->user]->billDomain("<b>{$row->domain}</b><br />Renew<br /><br />$from - $to", $users[$row->user]->addTax($row->recurring), $row->domain);
    $db->query("UPDATE domains SET expiration = '" . date("Y-m-d", strtotime($to)) . "' WHERE ID = {$row->ID}");
}

unset($users);

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
