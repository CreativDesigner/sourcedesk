<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching domains in waiting status\n", FILE_APPEND);

// Helper function
if (!function_exists("sanitize_for_serialization")) {
    function sanitize_for_serialization($obj)
    {
        if (is_array($obj)) {
            foreach ($obj as $k => $v) {
                $obj[$k] = sanitize_for_serialization($v);
            }
        } else if (is_object($obj)) {
            foreach ($obj as $k => $v) {
                $obj->$k = sanitize_for_serialization($v);
            }
        }

        try {
            $test = serialize($obj);
        } catch (Exception $ex) {
            $obj = strval($obj);
        }

        return $obj;
    }
}

// Sync new domains
$sql = $db->query("SELECT * FROM domains WHERE status IN ('REG_WAITING', 'KK_WAITING') AND sent = 1 ORDER BY ID ASC LIMIT 5");
while ($row = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Try to sync {$row->domain}\n", FILE_APPEND);
    $reg = DomainHandler::getRegistrars()[$row->registrar];
    if (!$reg || !$reg->isActive()) {
        continue;
    }

    $u = User::getInstance($row->user, "ID");
    if ($u) {
        $reg->setUser($u);
    }

    $res = $reg->syncDomain($row->domain, true);

    if (!$res || empty($res['status'])) {
        $db->query("UPDATE domains SET status = '" . ($row->status == "KK_WAITING" ? "KK_ERROR" : "REG_ERROR") . "', ignore_failed = 0, sent = 0, last_sync = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$row->ID}");
        Domain::sendMail($row->status == "KK_WAITING" ? "Domain-Transfer fehlgeschlagen" : "Domain-Registrierung fehlgeschlagen", $row->user, $row->domain);
        continue;
    }

    if ($res['status'] === "waiting_kk") {
        continue;
    }

    if (isset($res['creation']) && $res['creation'] == $res['expiration']) {
        $res['expiration'] = "0000-00-00";
    }

    $db->query("UPDATE domains SET last_sync = '" . date("Y-m-d H:i:s") . "', expiration_prov = '{$res['expiration']}', auto_renew = " . intval($res['auto_renew']) . ", transfer_lock = " . intval($res['transfer_lock']) . ", created = '" . date("Y-m-d") . "', status = '" . ($row->status == "KK_WAITING" ? "KK_OK" : "REG_OK") . "', privacy = " . ($res['privacy'] ? "1" : "0") . " WHERE ID = {$row->ID} LIMIT 1");
    Domain::sendMail($row->status == "KK_WAITING" ? "Domain transferiert" : "Domain registriert", $row->user, $row->domain);
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Status of domain {$row->domain} changed\n", FILE_APPEND);
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching existing domains for sync\n", FILE_APPEND);

// Sync existing domains (single sync)
$shouldMail = [];

$date = date("Y-m-d H:i:s", strtotime("-2 hours"));
$sql = $db->query("SELECT * FROM domains WHERE status IN ('REG_OK', 'KK_OK') AND changed != 1 AND last_sync <= '$date' ORDER BY last_sync ASC LIMIT 5");
while ($row = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Try to sync domain {$row->domain}\n", FILE_APPEND);

    $reg = DomainHandler::getRegistrars()[$row->registrar];
    if (!$reg || !$reg->isActive() || method_exists($reg, "massDomainSync")) {
        if (!$reg || !$reg->isActive()) {
            $db->query("UPDATE domains SET last_sync = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$row->ID} LIMIT 1");
        }

        continue;
    }

    $db->query("UPDATE domains SET last_sync = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$row->ID} LIMIT 1");

    $u = new User($row->user, "ID");
    if ($u) {
        $reg->setUser($u);
    }

    try {
        $res = $reg->syncDomain($row->domain);
        $reg_info = unserialize($row->reg_info);
        if ($res === false) {
            if ($row->expiration <= date("Y-m-d")) {
                $db->query("UPDATE domains SET status = 'EXPIRED' WHERE ID = {$row->ID}");

                $u = new User($row->user, "ID");
                if ($u->get()["ID"] != $row->user) {
                    continue;
                }

                $mtObj = new MailTemplate("Domain ausgelaufen");

                $title = $mtObj->getTitle($u->getLanguage());
                $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                $maq->enqueue([
                    "domain" => $row->domain,
                ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
            } else {
                $db->query("UPDATE domains SET status = 'KK_OUT' WHERE ID = {$row->ID}");

                $u = new User($row->user, "ID");
                if ($u->get()["ID"] != $row->user) {
                    continue;
                }

                $mtObj = new MailTemplate("Ausgehender Domain-Transfer");

                $title = $mtObj->getTitle($u->getLanguage());
                $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                $maq->enqueue([
                    "domain" => $row->domain,
                ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
            }

            continue;
        }
    } catch (Exception $ex) {
        echo $ex->getMessage . " ({$row->domain})" . PHP_EOL;
    }

    if (isset($res['creation']) && $res['expiration'] == date("Y-m-d") && $res['creation'] == $res['expiration'] && ($row->expiration == "0000-00-00" || $row->expiration == "1970-01-01")) {
        $res['expiration'] = "0000-00-00";
    }

    if (is_array($res)) {
        foreach (["admin", "owner", "tech", "zone"] as $h) {
            if (!isset($res[$h])) {
                continue;
            }

            $reg_info[$h] = $res[$h];
        }

        if (isset($res["ns"]) && is_array($res["ns"]) && count($res["ns"]) >= 2) {
            $reg_info["ns"] = $res["ns"];

            for ($i = 1; $i <= 5; $i++) {
                if ($CFG['NS' . $i] != $res["ns"][$i - 1] && !empty($CFG['NS' . $i])) {
                    break;
                }

                if ($i == 5) {
                    $reg_info["ns"] = [$CFG['DEFAULT_IP'], ""];
                }

            }

            $uns = $u->getNS();
            for ($i = 1; $i <= 5; $i++) {
                if ($uns[$i - 1] != $res["ns"][$i - 1] && !empty($uns[$i - 1])) {
                    break;
                }

                if ($i == 5) {
                    $reg_info["ns"] = [$CFG['DEFAULT_IP'], ""];
                }

            }
        }

        $reg_info = serialize(sanitize_for_serialization($reg_info));
        $db->query("UPDATE domains SET reg_info = '$reg_info', expiration_prov = '{$res['expiration']}', transfer_lock = " . intval($res['transfer_lock']) . ", privacy = " . ($res['privacy'] ? "1" : "0") . " WHERE ID = {$row->ID} LIMIT 1");
    }

    $addons->runHook("DomainSyncFinished", ["domain" => $row->domain, "result" => $res]);

    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Finished sync of domain {$row->domain}\n", FILE_APPEND);
}

// Sync existing domains (mass sync)
foreach (DomainHandler::getRegistrars() as $reg => $obj) {
    if (!$obj || !$obj->isActive() || !method_exists($obj, "massDomainSync")) {
        continue;
    }

    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Started mass sync for $reg\n", FILE_APPEND);

    $date = date("Y-m-d H:i:s", strtotime("-4 hours"));
    $sql = $db->query("SELECT ID, domain, last_sync2 FROM domains WHERE status IN ('REG_OK', 'KK_OK') AND changed != 1 AND registrar = '$reg' AND last_sync <= '$date' ORDER BY last_sync ASC LIMIT {$obj->getBatchSize()}");
    if (!$sql->num_rows) {
        continue;
    }

    $domains = [];
    while ($row = $sql->fetch_object()) {
        $domains[$row->ID] = $row->domain;
    }

    $res = $obj->massDomainSync($domains);

    $ids = "(" . implode(",", array_keys($domains)) . ")";

    $db->query("UPDATE domains SET last_sync = '" . date("Y-m-d H:i:s") . "' WHERE ID IN $ids");

    if (is_array($res)) {
        foreach ($res as $id => $det) {
            $domain = $domains[$id];

            $row = $db->query("SELECT * FROM domains WHERE ID = $id")->fetch_object();
            $reg_info = unserialize($row->reg_info);

            if ($det["status"] === false) {
                if ($row->expiration <= date("Y-m-d")) {
                    $db->query("UPDATE domains SET status = 'EXPIRED' WHERE ID = {$row->ID}");

                    $u = new User($row->user, "ID");
                    if ($u->get()["ID"] != $row->user) {
                        continue;
                    }

                    $mtObj = new MailTemplate("Domain ausgelaufen");

                    $title = $mtObj->getTitle($u->getLanguage());
                    $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                    $maq->enqueue([
                        "domain" => $row->domain,
                    ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
                } else {
                    $db->query("UPDATE domains SET status = 'KK_OUT' WHERE ID = {$row->ID}");

                    $u = new User($row->user, "ID");
                    if ($u->get()["ID"] != $row->user) {
                        continue;
                    }

                    $mtObj = new MailTemplate("Ausgehender Domain-Transfer");

                    $title = $mtObj->getTitle($u->getLanguage());
                    $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                    $maq->enqueue([
                        "domain" => $row->domain,
                    ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
                }

                continue;
            }

            if (isset($det['creation']) && $det['expiration'] == date("Y-m-d") && $det['creation'] == $det['expiration'] && ($row->expiration == "0000-00-00" || $row->expiration == "1970-01-01")) {
                $det['expiration'] = "0000-00-00";
            }

            if (is_array($det)) {
                if (isset($det["ns"]) && is_array($det["ns"]) && count($det["ns"]) >= 2) {
                    $reg_info["ns"] = $det["ns"];

                    for ($i = 1; $i <= 5; $i++) {
                        if ($CFG['NS' . $i] != $det["ns"][$i - 1] && !empty($CFG['NS' . $i])) {
                            break;
                        }

                        if ($i == 5) {
                            $reg_info["ns"] = [$CFG['DEFAULT_IP'], ""];
                        }
                    }

                    $uns = $u->getNS();
                    for ($i = 1; $i <= 5; $i++) {
                        if ($uns[$i - 1] != $det["ns"][$i - 1] && !empty($uns[$i - 1])) {
                            break;
                        }

                        if ($i == 5) {
                            $reg_info["ns"] = [$CFG['DEFAULT_IP'], ""];
                        }

                    }
                }

                $reg_info = serialize(sanitize_for_serialization($reg_info));
                $db->query("UPDATE domains SET reg_info = '$reg_info', expiration_prov = '{$det['expiration']}', transfer_lock = " . intval($det['transfer_lock']) . ", privacy = " . ($det['privacy'] ? "1" : "0") . " WHERE ID = {$row->ID} LIMIT 1");
            }
        }
    }

    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Finished mass sync for $reg\n", FILE_APPEND);
}

// Send renew mail
if (count($shouldMail)) {
    $mtObj = new MailTemplate("Domain verlängert");

    foreach ($shouldMail as $uid => $domains) {
        $u = User::getInstance($uid, "ID");

        $title = $mtObj->getTitle($u->getLanguage());
        $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

        $maq->enqueue([
            "domains" => implode("\n", $domains),
        ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
    }
}

// Sync missing SSL certificates
$sql = $db->query("SELECT * FROM domains WHERE status IN ('REG_OK', 'KK_OK') AND csr != '' AND ssl_cert = '' ORDER BY ssl_sync ASC LIMIT 5");
while ($row = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Try to sync SSL certificate for domain {$row->domain}\n", FILE_APPEND);

    $reg = DomainHandler::getRegistrars()[$row->registrar];
    if (!$reg && !$reg->isActive()) {
        $db->query("UPDATE domains SET ssl_sync = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$row->ID}");
        continue;
    }
    if (!method_exists($reg, "sslSync")) {
        $db->query("UPDATE domains SET ssl_sync = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$row->ID}");
        continue;
    }
    $res = $reg->sslSync($row->domain);
    if (!$res) {
        $db->query("UPDATE domains SET ssl_sync = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$row->ID}");
        continue;
    }

    $db->query("UPDATE domains SET ssl_sync = '" . date("Y-m-d H:i:s") . "', ssl_cert = '" . $db->real_escape_string(base64_encode($res)) . "' WHERE ID = {$row->ID}");

    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Finished sync of SSL certificate for domain {$row->domain}\n", FILE_APPEND);
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
