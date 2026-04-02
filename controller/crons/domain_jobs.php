<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);
file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching waiting domains\n", FILE_APPEND);

$sql = $db->query("SELECT * FROM domains WHERE (status = 'KK_WAITING' OR status = 'REG_WAITING') AND sent = 0 AND payment = 0 ORDER BY ID ASC LIMIT 3");
while ($row = $sql->fetch_object()) {
    try {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Try to process {$row->domain}\n", FILE_APPEND);
        if (empty($row->reg_info) || !is_array($info = unserialize($row->reg_info))) {
            $db->query("UPDATE domains SET status = '" . ($row->status == "KK_WAITING" ? "KK_ERROR" : "REG_ERROR") . "' WHERE ID = " . $row->ID);
            Domain::sendMail($row->status == "KK_WAITING" ? "Domain-Transfer fehlgeschlagen" : "Domain-Registrierung fehlgeschlagen", $row->user, $row->domain);
            continue;
        }

        if (empty($info['domain'])) {
            $info['domain'] = $row->domain;
            $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info)) . "' WHERE ID = " . $row->ID);
        }

        if ($row->status == "KK_WAITING" && $db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string($row->domain) . "' AND status IN ('KK_OK', 'REG_OK')")->num_rows > 0) {
            $ii = $db->query("SELECT * FROM domains WHERE domain = '" . $db->real_escape_string($row->domain) . "' AND status IN ('KK_OK', 'REG_OK')")->fetch_object();

            $reg = DomainHandler::getRegistrars()[$ii->registrar];
            if (empty($ii->registrar) || !$reg || !$reg->isActive()) {
                $db->query("UPDATE domains SET status = 'KK_ERROR' WHERE ID = " . $row->ID);
                Domain::sendMail("Domain-Transfer fehlgeschlagen", $row->user, $row->domain);
                continue;
            }

            if ($ii->transfer_lock) {
                $db->query("UPDATE domains SET status = 'KK_ERROR' WHERE ID = " . $row->ID);
                Domain::sendMail("Domain-Transfer fehlgeschlagen", $row->user, $row->domain);
                continue;
            }

            if ($iu = User::getInstance($ii->user, "ID")) {
                $reg->setUser($iu);
            }

            if (!method_exists($reg, "getAuthCode")) {
                $db->query("UPDATE domains SET status = 'KK_ERROR' WHERE ID = " . $row->ID);
                Domain::sendMail("Domain-Transfer fehlgeschlagen", $row->user, $row->domain);
                continue;
            }

            $ac = $reg->getAuthCode($ii->domain);
            if (substr($ac, 0, 5) != "AUTH:" || empty($ac) || substr($ac, 5) != $info['transfer'][0]) {
                $db->query("UPDATE domains SET status = 'KK_ERROR' WHERE ID = " . $row->ID);
                Domain::sendMail("Domain-Transfer fehlgeschlagen", $row->user, $row->domain);
                continue;
            }

            $dns = DNSHandler::getDriver($ii->domain);
            $dns->removeZone($ii->domain);

            if (count($info['ns']) != 2 && filter_var($info['ns'][0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && (empty($info['ns'][1]) || filter_var($info['ns'][1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
                $info['ns'] = [
                    $info['ns'][0],
                    $info['ns'][1],
                ];
                $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info)) . "' WHERE ID = " . $row->ID);
            }

            if (count($info['ns']) == 2) {
                $u = new User($row->user, "ID");

                if (!$row->sent_dns) {
                    $dns = DNSHandler::getDriver($info['domain']);
                    $dns->addZone($info['domain'], $ns = $u->getNS());
                    $dns->applyTemplate($info['domain'], $ns, $info['ns'][0], $info['ns'][1] ?: false);
                    $addons->runHook("DnsZoneCreated", [
                        "driver" => $dns,
                        "domain" => $info['domain'],
                        "client" => $u,
                    ]);
                    $db->query("UPDATE domains SET sent_dns = 1 WHERE ID = " . $row->ID);
                    continue;
                }

                $info['ns'] = $u->getNS();
            }

            $reg->changeAll($info['domain'], $info['owner'], $info['admin'], $info['tech'], $info['zone'], $info['ns'], $row->transfer_lock, $row->auto_renew, $row->privacy);
            $db->query("UPDATE domains SET registrar = '" . $db->real_escape_string($ii->registrar) . "', status = 'KK_OK' WHERE ID = " . $row->ID);
            $db->query("UPDATE domains SET status = 'KK_OUT' WHERE ID = " . $ii->ID);
            Domain::sendMail("Domain transferiert", $row->user, $row->domain);
            Domain::sendMail("Ausgehender Domain-Transfer", $ii->user, $row->domain);
        } else {
            $reg = DomainHandler::getRegistrars()[$row->registrar];
            if (empty($row->registrar) || !$reg || !$reg->isActive()) {
                $db->query("UPDATE domains SET status = '" . ($row->status == "KK_WAITING" ? "KK_ERROR" : "REG_ERROR") . "' WHERE ID = " . $row->ID);
                Domain::sendMail($row->status == "KK_WAITING" ? "Domain-Transfer fehlgeschlagen" : "Domain-Registrierung fehlgeschlagen", $row->user, $row->domain);
                continue;
            }

            $u = new User($row->user, "ID");
            if ($u) {
                $reg->setUser($u);
            }

            if (count($info['ns']) == 2 || (filter_var($info['ns'][0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && (empty($info['ns'][1] || (filter_var($info['ns'][1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && empty($info['ns'][2])))))) {
                if (!$row->sent_dns && !$reg->delayDns) {
                    $dns = DNSHandler::getDriver($info['domain']);
                    $dns->addZone($info['domain'], $ns = $u->getNS());
                    $dns->applyTemplate($info['domain'], $ns, $info['ns'][0], $info['ns'][1] ?: false);
                    $addons->runHook("DnsZoneCreated", [
                        "driver" => $dns,
                        "domain" => $info['domain'],
                        "client" => $u,
                    ]);
                    $db->query("UPDATE domains SET sent_dns = 1 WHERE ID = " . $row->ID);
                    continue;
                }

                $info['ns'] = $u->getNS();
            }

            foreach ($info['ns'] as $i => $n) {
                if (empty($n)) {
                    unset($info['ns'][$i]);
                }
            }

            $reg->setUser(User::getInstance($row->user, "ID"));
            if ($row->status == "REG_WAITING") {
                $res = $reg->registerDomain($info['domain'], $info['owner'], $info['admin'], $info['tech'], $info['zone'], $info['ns'], $row->privacy);
            } else {
                $res = $reg->transferDomain($info['domain'], $info['owner'], $info['admin'], $info['tech'], $info['zone'], $info['transfer'][0], $info['ns'], $row->privacy);
            }

            if ($res !== true) {
                $err = $res;
                $res = false;
            }

            if (!$res) {
                $error = "";
                if (isset($err)) {
                    if (is_string($err)) {
                        $error = $db->real_escape_string($err);
                    } else {
                        $error = $db->real_escape_string(serialize($err));
                    }

                }

                $db->query("UPDATE domains SET status = '" . ($row->status == "KK_WAITING" ? "KK_ERROR" : "REG_ERROR") . "', error = '$error' WHERE ID = " . $row->ID);
                Domain::sendMail($row->status == "KK_WAITING" ? "Domain-Transfer fehlgeschlagen" : "Domain-Registrierung fehlgeschlagen", $row->user, $row->domain);
            } else {
                $db->query("UPDATE domains SET sent = 1, error = '' WHERE ID = " . $row->ID);

                if (!$row->sent_dns && $reg->delayDns) {
                    $dns = DNSHandler::getDriver($info['domain']);
                    $dns->addZone($info['domain'], $ns = $u->getNS());
                    $dns->applyTemplate($info['domain'], $ns, $info['ns'][0], $info['ns'][1] ?: false);
                    $addons->runHook("DnsZoneCreated", [
                        "driver" => $dns,
                        "domain" => $info['domain'],
                        "client" => $u,
                    ]);
                    $db->query("UPDATE domains SET sent_dns = 1 WHERE ID = " . $row->ID);
                }
            }
        }

        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] {$row->domain} finished\n", FILE_APPEND);
    } catch (Exception $ex) {
        echo $ex->getMessage() . " ({$row->domain})" . PHP_EOL;
    } catch (SoapException $ex) {
        echo $ex->getMessage() . " ({$row->domain})" . PHP_EOL;
    }
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching domains with waiting changes\n", FILE_APPEND);

$sql = $db->query("SELECT * FROM domains WHERE (status = 'REG_OK' OR status = 'KK_OK') AND changed = 1 ORDER BY ID ASC LIMIT 3");
while ($row = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Try to process {$row->domain}\n", FILE_APPEND);
    $reg = DomainHandler::getRegistrars()[$row->registrar];
    if (empty($row->registrar) || !$reg || !$reg->isActive()) {
        $db->query("UPDATE domains SET changed = -1 WHERE ID = " . $row->ID);
        continue;
    }

    if (empty($row->reg_info) || !is_array($info = unserialize($row->reg_info))) {
        $db->query("UPDATE domains SET changed = -1 WHERE ID = " . $row->ID);
        continue;
    }

    $u = new User($row->user, "ID");
    if ($u) {
        $reg->setUser($u);
    }

    if (count($info['ns']) == 2) {
        $dns = DNSHandler::getDriver($info['domain']);
        if (!$dns->getZone($info['domain'])) {
            $dns->addZone($info['domain'], $ns = $u->getNS());
            $dns->applyTemplate($info['domain'], $ns, $info['ns'][0], $info['ns'][1] ?: false);
            $addons->runHook("DnsZoneCreated", [
                "driver" => $dns,
                "domain" => $info['domain'],
                "client" => $u,
            ]);
        }

        $info['ns'] = $u->getNS();
    }

    foreach ($info['ns'] as $i => $n) {
        if (empty($n)) {
            unset($info['ns'][$i]);
        }
    }

    $res = $reg->changeAll($info['domain'], $row->trade > 0 ? array() : $info['owner'], $info['admin'], $info['tech'], $info['zone'], $info['ns'], $row->transfer_lock, $row->auto_renew, $row->privacy);
    if ($res !== true) {
        $err = is_string($res) ? $res : serialize($res);
        $res = false;
    }

    $error = isset($err) ? $db->real_escape_string($err) : "";
    $db->query("UPDATE domains SET changed = " . ($res ? "0" : "-1") . ", error = '$error' WHERE ID = " . $row->ID);

    if ($res) {
        Domain::sendMail("Domain aktualisiert", $row->user, $row->domain);
    } else {
        Domain::sendMail("Fehlgeschlagene Aktualisierung", $row->user, $row->domain);
    }

    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Changes for {$row->domain} propagated\n", FILE_APPEND);
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Fetching domains with waiting trade\n", FILE_APPEND);

$sql = $db->query("SELECT * FROM domains WHERE (status = 'REG_OK' OR status = 'KK_OK') AND trade_waiting = 1 ORDER BY ID ASC LIMIT 3");
while ($row = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Try to process {$row->domain}\n", FILE_APPEND);
    $reg = DomainHandler::getRegistrars()[$row->registrar];
    if (empty($row->registrar) || !$reg || !$reg->isActive()) {
        continue;
    }

    if (empty($row->reg_info) || !is_array($info = unserialize($row->reg_info))) {
        continue;
    }

    $u = User::getInstance($row->user, "ID");
    if ($u) {
        $reg->setUser($u);
    }

    $res = $reg->trade($info['domain'], $info['owner']);
    if ($res !== true) {
        $err = is_string($res) ? $res : serialize($res);
        $res = false;
    }

    $error = isset($err) ? $db->real_escape_string($err) : "";
    $db->query("UPDATE domains SET error = '$error' WHERE ID = " . $row->ID);

    if ($res) {
        Domain::sendMail("Domain aktualisiert", $row->user, $row->domain);
    } else {
        Domain::sendMail("Fehlgeschlagene Aktualisierung", $row->user, $row->domain);
    }

    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Trade for {$row->domain} processed\n", FILE_APPEND);
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
