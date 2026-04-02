<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Cronjob for provisioning of new products
if (!isset($force_id)) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);
}

// Prov function
function do_prov($row)
{
    global $provisioning, $maq, $db, $CFG, $maq, $force_id, $addons, $dfo;

    if (!isset($force_id)) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Create #{$row->ID}\n", FILE_APPEND);
    }

    $modules = $provisioning->get();
    if (!array_key_exists($row->module, $modules)) {
        $r = [true, []];
    } else {
        $m = $modules[$row->module];
        $r = $m->Create($row->ID);

        $addons->runHook("ProvCreate", [
            "contract" => $row,
            "result" => $r,
            "module" => $m,
        ]);
    }

    if ($r[0] == false) {
        $db->query("UPDATE client_products SET error = '" . $db->real_escape_string($r[1]) . "' WHERE ID = {$row->ID}");
    } else if ($r[0] === "wait") {
        $db->query("UPDATE client_products SET module_data = '" . $db->real_escape_string(encrypt(serialize($r[1]))) . "' WHERE ID = {$row->ID}");
    } else {
        $db->query("UPDATE client_products SET active = 1, paid_until = " . time() . " WHERE ID = {$row->ID}");
        if (is_array($r[1])) {
            $db->query("UPDATE client_products SET module_data = '" . $db->real_escape_string(encrypt(serialize($r[1]))) . "' WHERE ID = {$row->ID}");
        }

        if (!empty($r[2]) && method_exists($m, $r[2])) {
            $c = $r[2];
            $m->$c($row->ID);
        }

        $uI = User::getInstance($row->user, "ID");
        if (!$uI) {
            return;
        }

        $pInfo = $db->query("SELECT welcome_mail, `name` FROM products WHERE ID = {$row->product} LIMIT 1")->fetch_object();

        $pName = strval($pInfo->name);
        $pArr = @unserialize($pName);
        if (is_array($pArr) && count($pArr)) {
            if (array_key_exists($uI->getLanguage(), $pArr)) {
                $pName = strval($pArr[$uI->getLanguage()]);
            } else {
                $pName = strval(array_shift($pArr));
            }
        }

        $mt = new MailTemplate($pInfo->welcome_mail);
        $title = $mt->getTitle($uI->getLanguage());
        $mail = $mt->getMail($uI->getLanguage(), $uI->get()['name']);

        $metVars = [
            "product" => $pName,
            "inclusive_domains" => [],
            "addon_domains" => [],
            "cancellation_date" => $row->cancellation_date == "0000-00-00" ? "" : $dfo->format($row->cancellation_date, 0, 0, "-", $uI->getDateFormat()),
        ];

        if (is_object($m)) {
            $vars = $m->EmailVariables($row->ID);
            foreach ($vars as $k => $v) {
                $metVars[$k] = $v;
            }

            $m->LoadOptions($row->ID);
            if ($sid = $m->getOption("_mgmt_server")) {
                $srvSql = $db->query("SELECT * FROM monitoring_server WHERE ID = " . intval($sid));
                if ($srvSql->num_rows) {
                    $metVars["server"] = $srvSql->fetch_assoc();
                }
            }
        }

        $domSql = $db->query("SELECT domain FROM domains WHERE inclusive_id = " . intval($row->ID));
        while ($row2 = $domSql->fetch_object()) {
            $metVars["inclusive_domains"][] = $row2->domain;
        }

        $domSql = $db->query("SELECT domain FROM domains WHERE addon_id = " . intval($row->ID));
        while ($row2 = $domSql->fetch_object()) {
            $metVars["addon_domains"][] = $row2->domain;
        }

        $maq->enqueue($metVars, $mt, $uI->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $uI->get()['ID'], false, 0, 0, $mt->getAttachments($uI->getLanguage()));

        // Inclusive/addon domains
        if (is_object($m) && method_exists($m, "AssignDomain")) {
            $sql = $db->query("SELECT domain FROM domains WHERE inclusive_id = " . $row->ID . " OR addon_id = " . $row->ID);
            while ($row2 = $sql->fetch_object()) {
                $m->AssignDomain($row->ID, $row2->domain);
            }
        }
    }
}

if (!isset($force_id)) {
    // Find waiting products
    $sql = $db->query("SELECT ID, module, product, user FROM client_products WHERE active = -1 AND error = '' AND payment = 0 ORDER BY ID ASC LIMIT 3");
    while ($row = $sql->fetch_object()) {
        do_prov($row);
    }

    // Handle cancellations
    $sql = $db->query("SELECT ID, module FROM client_products WHERE type = 'h' AND active != -2 AND active != -1 AND error = '' AND cancellation_date <= '" . date("Y-m-d") . "' AND cancellation_date > '0000-00-00' ORDER BY ID ASC LIMIT 3");
    while ($row = $sql->fetch_object()) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cancel/delete #{$row->ID}\n", FILE_APPEND);

        if ($row->ID) {
            $db->query("UPDATE clients SET `cgroup` = `cgroup_before` WHERE `cgroup_contract` = {$row->ID}");
        }

        $modules = $provisioning->get();
        if (!array_key_exists($row->module, $modules)) {
            $db->query("UPDATE client_products SET error = 'Module not found' WHERE ID = {$row->ID}");
            continue;
        }

        $m = $modules[$row->module];
        if (method_exists($m, "Delete")) {
            $r = $m->Delete($row->ID);

            if ($r[0] == false) {
                $db->query("UPDATE client_products SET error = '" . $db->real_escape_string($r[1]) . "' WHERE ID = {$row->ID}");
            } else {
                $db->query("UPDATE client_products SET active = -2 WHERE ID = {$row->ID}");
            }
        } else {
            $db->query("UPDATE client_products SET active = -2 WHERE ID = {$row->ID}");
        }
    }

    // Handle prepaid expiration
    $sql = $db->query("SELECT ID, module FROM client_products WHERE type = 'h' AND active != -2 AND active != -1 AND error = '' AND prepaid = 1 AND last_billed <= '" . date("Y-m-d") . "' AND last_billed > '0000-00-00' ORDER BY ID ASC LIMIT 3");
    while ($row = $sql->fetch_object()) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cancel/delete #{$row->ID}\n", FILE_APPEND);

        if ($row->ID) {
            $db->query("UPDATE clients SET `cgroup` = `cgroup_before` WHERE `cgroup_contract` = {$row->ID}");
        }

        $modules = $provisioning->get();
        if (array_key_exists($row->module, $modules)) {
            $m = $modules[$row->module];
            if (method_exists($m, "Delete")) {
                $r = $m->Delete($row->ID);

                if ($r[0] == false) {
                    $db->query("UPDATE client_products SET error = '" . $db->real_escape_string($r[1]) . "' WHERE ID = {$row->ID}");
                } else {
                    $db->query("UPDATE client_products SET active = -2 WHERE ID = {$row->ID}");
                }
            } else {
                $db->query("UPDATE client_products SET active = -2 WHERE ID = {$row->ID}");
            }
        } else {
            $db->query("UPDATE client_products SET active = -2 WHERE ID = {$row->ID}");
        }
    }

    $sql = $db->query("SELECT ID FROM client_products WHERE type != 'h' AND active != 0 AND cancellation_date <= '" . date("Y-m-d") . "' AND cancellation_date > '0000-00-00' ORDER BY ID ASC LIMIT 3");
    while ($row = $sql->fetch_object()) {
        file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cancel/delete #{$row->ID}\n", FILE_APPEND);

        $db->query("UPDATE client_products SET active = 0 WHERE ID = {$row->ID}");
    }

    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
} else {
    do_prov($db->query("SELECT * FROM client_products WHERE active = -1 AND error = '' AND ID = " . intval($force_id))->fetch_object());
}
