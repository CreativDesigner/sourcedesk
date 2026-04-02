<?php
menu("customers");
title($lang['MENU']['CUSTOMERS']);
$l = $lang['CUSTOMERS'];

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(7)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "customers");} else {

    $_GET['edit'] = intval($_GET['edit']);
    $uI = User::getInstance($_GET['edit'] ?: 0, "ID");
    if ($uI) {
        title($uI->get()['name']);
    }
    $activeFields = array();

    if ($uI !== false) {
        foreach ($addons->runHook("AdminClientProfileTicketLink", ["user" => $uI]) as $l2) {
            $customSupportTicketsLink = $l2;
        }
    }

    $sql = $db->query("SELECT name FROM client_fields WHERE active = 1");
    while ($row = $sql->fetch_object()) {
        array_push($activeFields, $row->name);
    }

    $birthday_active = $db->query("SELECT active FROM cronjobs WHERE `key` = 'birthday' LIMIT 1")->fetch_object()->active;

    if (isset($_GET['login']) && is_numeric($_GET['login']) && $ari->check(11)) {
        $loginUserId = $db->real_escape_string($_GET['login']);
        $sql = $db->query("SELECT * FROM clients WHERE ID = '$loginUserId' LIMIT 1");
        if ($sql->num_rows == 1) {
            $loginUser = $sql->fetch_object();

            if (empty($loginUser->pwd)) {
                $loginUser->pwd = $db->real_escape_string($sec->generatePassword(64, false, "ld"));
                $db->query("UPDATE clients SET pwd = '{$loginUser->pwd}' WHERE ID = " . $loginUserId . " LIMIT 1");
            }

            $session->set('mail', $loginUser->mail);
            if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                $session->set('pwd', md5($loginUser->pwd));
            } else {
                $session->set('pwd', $loginUser->pwd);
            }

            $session->set('admin_login', 1);
            $session->set('tfa', true);

            alog("customers", "login_as", $_GET['login']);

            $addons->runHook("CustomerLogin", [
                "user" => User::getInstance($loginUserId, "ID"),
                "source" => "admin",
            ]);

            header('Location: ../');
            exit;
        }
    }

    if (isset($_GET['stopAutoPayment']) && $uI->autoPaymentStatus() && $ari->check(16)) {
        $uI->cancelAutoPayment();
        header('Location: ?p=customers&edit=' . $uI->get()['ID']);
        exit;
    }

    if (isset($_GET['receipt']) && $ari->check(16) && $db->query("SELECT 1 FROM client_transactions WHERE ID = " . intval($_GET['receipt']) . " AND deposit = 1")->num_rows == 1) {
        alog("customers", "receipt_download", $_GET['receipt']);
        $uI = User::getInstance($db->query("SELECT user FROM client_transactions WHERE ID = " . intval($_GET['receipt']) . " AND deposit = 1")->fetch_object()->user, "ID");
        $uI->loadLanguage();
        $r = new PDFReceipt($db->query("SELECT * FROM client_transactions WHERE ID = " . intval($_GET['receipt']))->fetch_object());
        $r->output();
        exit;
    }

    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

    if ($ari->check(62) && $tab == "scoring" && isset($_POST['newscore'])) {
        $db->query("INSERT INTO client_scoring (time, user, rating, entry, details) VALUES (" . time() . ", {$_GET['edit']}, '" . $db->real_escape_string($_POST['rating']) . "', '" . $db->real_escape_string($_POST['entry']) . "', '')");
        alog("customers", "new_scoring", $db->insert_id, $_GET['edit'], $_POST['rating']);
        header('Location: ?p=customers&edit=' . $_GET['edit'] . "&tab=scoring");
        exit;
    }

    if ($ari->check(62) && $tab == "scoring" && isset($_GET['provider']) && array_key_exists($_GET['provider'], ScoringHandler::getDrivers()) && is_object($d = ScoringHandler::getDrivers()[$_GET['provider']]) && isset($_GET['method']) && array_key_exists($_GET['method'], $d->getMethods())) {
        User::getInstance($_GET['edit'], "ID")->fetchScore($d, $_GET['method']);
        alog("customers", "fetch_scoring", $_GET['edit'], $_GET['provider'], $_GET['method']);
        header('Location: ?p=customers&edit=' . $_GET['edit'] . "&tab=scoring");
        exit;
    }

    if ($tab == "files" && $ari->check(10) && isset($_GET['download']) && file_exists(__DIR__ . '/../../files/customers/' . basename($_GET['download']))) {
        alog("customers", "file_download", $_GET['edit'], basename($_GET['download']));

        // Get the file from download directory
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . substr(basename($_GET['download']), 9) . "\"");
        readfile(__DIR__ . '/../../files/customers/' . basename($_GET['download']));

        // Exit the script to prevent output
        exit;
    }

    if ($tab == "files" && $ari->check(10) && isset($_GET['send']) && file_exists(__DIR__ . '/../../files/customers/' . basename($_GET['send']))) {
        $fileName = substr(basename($_GET['send']), 9);
        $filePath = __DIR__ . '/../../files/customers/' . basename($_GET['send']);

        $uI = User::getInstance($_GET['edit'], "ID");
        if ($uI) {
            $t = new MailTemplate("Dateiversand");
            $title = $t->getTitle($uI->getLanguage());
            $mail = $t->getMail($uI->getLanguage(), $uI->get()['name']);

            $id = $maq->enqueue([
                "file" => $fileName,
            ], $t, $uI->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $uI->get()['ID'], false, 0, 0, array($fileName => $filePath));
            $maq->send(1, $id, true, false);
            $maq->delete($id);

            alog("customers", "file_sent", $_GET['edit'], basename($_GET['send']));

            $suc = $l['SUC1'];
        }
    }

    if ($uI) {
        $u = (object) $uI->get();
        if (!empty($_GET['download_file']) && $tab == "notes" && $ari->check(20) && isset($_GET['note']) && is_object($sql = $db->query("SELECT * FROM client_notes WHERE ID = '" . $db->real_escape_string($_GET['note']) . "' AND user = " . $u->ID)) && $sql->num_rows == 1 && file_exists($path = __DIR__ . "/../../files/notes/" . intval($_GET['note'])) && file_exists($path . "/" . basename($_GET['download_file']))) {
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . basename($_GET['download_file']) . "\"");
            readfile($path . "/" . basename($_GET['download_file']));
            exit;
        }

        if (!empty($_GET['download_file']) && $tab == "telephone" && $ari->check(51) && isset($_GET['id']) && is_object($sql = $db->query("SELECT * FROM client_calls WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' AND user = " . $u->ID)) && $sql->num_rows == 1 && file_exists($path = __DIR__ . "/../../files/calls/" . intval($_GET['id'])) && file_exists($path . "/" . basename($_GET['download_file']))) {
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . basename($_GET['download_file']) . "\"");
            readfile($path . "/" . basename($_GET['download_file']));
            exit;
        }
    }

    function escape_mysqli(&$value)
    {
        global $db;
        $value = $db->real_escape_string($value);
    }

    if (isset($_GET['edit']) && $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1")->num_rows == 1 && $ari->check(8)) {

        $u = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1")->fetch_object();
        $userInstance = new User($u->mail);

        if (isset($_GET['telephone_pin'])) {
            if ($u->telephone_pin == $_GET['telephone_pin'] && $u->telephone_pin_set > time() - 600) {
                alog("customers", "telephone_pin_verification_ok", $u->ID);
                die("true");
            }

            alog("customers", "telephone_pin_verification_fail", $u->ID);
            die("false");
        }

        if ($ari->check(13) && isset($_GET['group_recurring'])) {
            alog("customers", "group_recurring", $u->ID);
            $db->query("UPDATE clients SET group_recurring = " . intval(boolval($_GET['group_recurring'])) . " WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
        }

        if (isset($_POST['exclude_mail_templates'])) {
            foreach ($_POST['exclude_mail_templates'] as &$v) {
                $v = intval($v);
            }
            unset($v);
            $emt = implode(",", $_POST['exclude_mail_templates']);
            $db->query("UPDATE clients SET exclude_mail_templates = '" . $db->real_escape_string($emt) . "' WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
            alog("customers", "exclude_mail_templates", $u->ID, $emt);
            exit;
        }

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {

                case 'save_data':

                    if ($ari->check(10)) {
                        if (is_array($userInstance->get())) {
                            if (!isset($_POST['verified'])) {
                                $_POST['verified'] = 0;
                            }

                            if (!isset($_POST['social_login']) && ($CFG['FACEBOOK_LOGIN'] || $CFG['TWITTER_LOGIN'])) {
                                $_POST['social_login'] = 0;
                            }

                            if (!isset($_POST['login_notify'])) {
                                $_POST['login_notify'] = 0;
                            }

                            if (!isset($_POST['reseller'])) {
                                $_POST['reseller'] = 0;
                            }

                            if (!isset($_POST['withdrawal_rules'])) {
                                $_POST['withdrawal_rules'] = 0;
                            }

                            if (!isset($_POST['privacy_policy'])) {
                                $_POST['privacy_policy'] = 0;
                            }

                            if (empty($_POST['inv_diff'])) {
                                $_POST['inv_street'] = "";
                                $_POST['inv_street_number'] = "";
                                $_POST['inv_postcode'] = "";
                                $_POST['inv_city'] = "";
                                $_POST['inv_tthof'] = "";
                            }

                            if (empty($_POST['inv_diff_par']) || trim($_POST['inv_due']) === "" || !is_numeric($_POST['inv_due'])) {
                                $_POST['inv_due'] = -1;
                            } else {
                                $_POST['inv_due'] = max(0, intval($_POST['inv_due']));
                            }

                            if ($birthday_active && !isset($_POST['birthday_mail'])) {
                                $_POST['birthday_mail'] = 0;
                            }

                            if (!isset($_POST['cashbox_active'])) {
                                $_POST['cashbox_active'] = $userInstance->get()['cashbox_active'];
                            }

                            if ($_POST['tfa'] == "") {
                                $_POST['tfa'] = "none";
                            }

                            if (!isset($_POST['tos'])) {
                                $_POST['tos'] = -1;
                            }

                            $nl = array();
                            if (is_array($_POST['newsletter'])) {
                                foreach ($_POST['newsletter'] as $id) {
                                    array_push($nl, $id);
                                }
                            }

                            $_POST['newsletter'] = implode("|", $nl);

                            if (isset($_POST['status'])) {
                                switch ($_POST['status']) {
                                    case 'ok':
                                        $_POST['locked'] = 0;
                                        $_POST['confirmed'] = 1;
                                        break;

                                    case 'locked':
                                        $_POST['locked'] = 1;
                                        $_POST['confirmed'] = 1;
                                        break;

                                    case 'waiting':
                                        $_POST['locked'] = 0;
                                        $_POST['confirmed'] = 0;
                                        break;
                                }
                            }

                            $pwchanged = false;
                            if ($ari->check(9) && strlen($_POST['pwd']) > 0) {
                                $_POST['salt'] = $sec->generateSalt();
                                $_POST['pwd'] = $sec->hash($_POST['pwd'], $_POST['salt']);

                                $pwchanged = true;
                            } else if (!$ari->check(9) || trim($_POST['pwd']) == "") {
                                $_POST['pwd'] = $u->pwd;
                                $_POST['salt'] = $u->salt;
                            }

                            if (false !== strtotime($_POST['birthday']) && strtotime($_POST['birthday']) < time()) {
                                $_POST['birthday'] = date("Y-m-d", strtotime($_POST['birthday']));
                            } else {
                                $_POST['birthday'] = "0000-00-00";
                            }

                            if (!in_array("Geburtstag", $activeFields)) {
                                unset($_POST['birthday']);
                            }

                            $counSql = $db->query("SELECT `name`, `alpha2` FROM `client_countries` WHERE active = 1 AND `ID` = '" . $db->real_escape_string($_POST['country']) . "'");
                            if ($counSql->num_rows != 1) {
                                unset($_POST['country']);
                            }

                            $counInfo = $counSql->fetch_object();

                            if (!in_array("Land", $activeFields)) {
                                unset($_POST['country']);
                            }

                            if (!empty($_POST['vatid']) && in_array("USt-IdNr.", $activeFields)) {
                                $obj = new EuVAT($_POST['vatid']);
                                if (!$obj->isValid()) {
                                    $_POST['vatid'] = "";
                                }

                                if ($obj->getCountry() != $counInfo->alpha2) {
                                    $_POST['vatid'] = "";
                                }

                            } else {
                                unset($_POST['vatid']);
                            }

                            if (isset($_POST['nickname'])) {
                                $_POST['nickname'] = trim($_POST['nickname']);
                                if (!empty($_POST['nickname'])) {
                                    if ($db->query("SELECT 1 FROM clients WHERE LIKE '" . $db->real_escape_string($_POST['nickname']) . "' AND ID != " . $userInstance->get()['ID'])->num_rows) {
                                        unset($_POST['nickname']);
                                    }
                                }
                            }

                            if (in_array("Postleitzahl", $activeFields) && in_array("Ort", $activeFields)) {
                                if ($_POST['postcode'] != $userInstance->get()['postcode'] || $_POST['city'] != $userInstance->get()['city'] || $_POST['street'] != $userInstance->get()['street'] || $_POST['street_number'] != $userInstance->get()['street_number'] || $_POST['country'] != $userInstance->get()['country']) {
                                    $country = isset($_POST['country']) ? ", " . $counInfo->name : "";
                                    $loc = GeoLocation::getLocation($_POST['street'] . " " . $_POST['street_number'] . ", " . $_POST['postcode'] . " " . $_POST['city'] . $country);
                                    $_POST['coordinates'] = !$loc ? "" : serialize($loc);
                                }
                            }

                            if (isset($_POST['fields']) && is_array($_POST['fields'])) {
                                foreach ($_POST['fields'] as $id => $value) {
                                    if ($db->query("SELECT 1 FROM client_fields WHERE active = 1 AND ID = " . intval($id))->num_rows == 1) {
                                        $userInstance->setField($id, $value);
                                    }
                                }
                            }

                            unset($_POST['fields']);

                            $locked = $userInstance->get()['locked'];

                            unset($_POST['csrf_token'], $_POST['status'], $_POST['action'], $_POST['change']);

                            $userInstance->set($_POST);
                            $userInstance->saveChanges("admin" . $adminInfo->ID);

                            if ($locked != $userInstance->get()['locked']) {
                                $addons->runHook("Customer" . ($userInstance->get()['locked'] ? "L" : "Unl") . "ocked", [
                                    "user" => $userInstance,
                                ]);
                            }

                            if ($pwchanged) {
                                $addons->runHook("CustomerChangePassword", [
                                    "user" => $userInstance,
                                    "source" => "admin_enter",
                                ]);
                            }

                            alog("customers", "save_profile", $u->ID);

                            $addons->runHook("CustomerEdit", [
                                "user" => $userInstance,
                                "source" => "admin",
                            ]);

                            $suc = $l['SUC2'];
                        }
                    }

                    break;

                case 'lockprios':
                    $disabled = implode(",", $_POST['disabled_support_prio']);
                    $uI->set(["disabled_support_prio" => $disabled]);
                    alog("customers", "disabled_support_prio", $u->ID, $disabled);
                    $uI->disabled_support_prio = $disabled;
                    break;

                case 'add_transaction':

                    if ($ari->check(16)) {

                        $amount = $nfo->phpize($_POST['amount']);
                        $operator = "+";

                        if (strpos($_POST['amount'], "-") !== false) {
                            $operator = "-";
                            $amount = str_replace('-', '', $amount);
                        }

                        if (is_numeric($amount) && $amount > 0) {
                            if (isset($_POST['do_credit']) && $_POST['do_credit'] == "yes") {
                                $db->query("UPDATE clients SET credit = credit $operator $amount WHERE ID = " . $u->ID . " LIMIT 1");
                                if (isset($_POST['special_credit']) && $_POST['special_credit'] == "yes") {
                                    $db->query("UPDATE clients SET special_credit = special_credit $operator $amount WHERE ID = " . $u->ID . " LIMIT 1");
                                }

                            }
                            if ($db->affected_rows > 0 || !isset($_POST['do'])) {
                                $db->query("INSERT INTO client_transactions (user, time, amount, subject, who) VALUES (" . $u->ID . ", " . time() . ", '$operator$amount', '" . $db->real_escape_string($_POST['description']) . "', {$adminInfo->ID})");
                                if ($db->affected_rows > 0) {
                                    $suc = $l['SUC3'];
                                }

                            }
                        }

                        alog("customers", "add_transaction", $u->ID, $operator . $amount);

                    }

                    break;

            }
        }

        $u = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1")->fetch_object();

        if (isset($_GET['free']) && $CFG['AFFILIATE_ACTIVE'] == "1" && $tab == "affiliate" && $db->query("SELECT ID FROM clients WHERE ID = " . intval($_GET['free']) . " AND affiliate = " . $u->ID)->num_rows == 1) {
            $db->query("UPDATE clients SET affiliate = 0, affiliate_source = '' WHERE ID = " . intval($_GET['free']) . " LIMIT 1");
            alog("customers", "free_affiliate", $u->ID);
            $suc = $l['SUC4'];
        }

        if (isset($_POST['coordinates']) && $_POST['coordinates'] == "reset") {
            $counSql = $db->query("SELECT `name`, `alpha2` FROM `client_countries` WHERE active = 1 AND `ID` = '" . $u->country . "'");
            if ($counSql->num_rows == 1) {
                $counInfo = $counSql->fetch_object();
            }

            $country = isset($counInfo) ? ", " . $counInfo->name : "";
            $loc = GeoLocation::getLocation($u->street . " " . $u->street_number . ", " . $u->postcode . " " . $u->city . $country);
            User::getInstance($u->ID, "ID")->set(array("coordinates" => !$loc ? "" : serialize($loc)));

            alog("customers", "coordinate_reset", $u->ID);

            die($loc ? "ok" : "fail");
        }

        if (in_array("to_credit", array_keys($_GET)) && $u->affiliate_credit != 0 && $CFG['AFFILIATE_ACTIVE'] == "1" && $tab == "affiliate") {
            $db->query("UPDATE clients SET affiliate_credit = 0, credit = credit + {$u->affiliate_credit} WHERE ID = " . $u->ID . " LIMIT 1");
            $transactions->insert("affiliate", 0, $u->affiliate_credit, $u->ID);
            $suc = $l['SUC5'];
            alog("customers", "affiliate_credit_to_normal", $u->ID, $u->affiliate_credit);
        }

        if (isset($_POST['free_selected']) && is_array($_POST['afcusts']) && $CFG['AFFILIATE_ACTIVE'] == "1" && $tab == "affiliate") {
            $d = 0;
            foreach ($_POST['afcusts'] as $cust) {
                if ($db->query("SELECT ID FROM clients WHERE ID = " . intval($cust) . " AND affiliate = " . $u->ID)->num_rows != 1) {
                    continue;
                }

                $db->query("UPDATE clients SET affiliate = 0, affiliate_source = '' WHERE ID = " . intval($cust) . " LIMIT 1");
                alog("customers", "free_affiliate", $cust);
                $d++;
            }

            if ($d == 0) {
                $err = $l['SUC6'];
            } else if ($d == 1) {
                $suc = $l['SUC6O'];
            } else {
                $suc = str_replace("%d", $d, $l['SUC6X']);
            }

        }

        if (isset($_POST['do_affiliate_change']) && $CFG['AFFILIATE_ACTIVE'] == "1" && $tab == "affiliate") {
            try {
                if (!isset($_POST['affiliate_customer']) || !is_numeric($_POST['affiliate_customer']) || ($_POST['affiliate_customer']) != "0" && $db->query("SELECT ID FROM clients WHERE ID = " . intval($_POST['affiliate_customer']))->num_rows != 1) {
                    throw new Exception($l['ERR1']);
                }

                $source = isset($_POST['affiliate_source']) ? $db->real_escape_string($_POST['affiliate_source']) : "";

                $db->query("UPDATE clients SET affiliate = " . intval($_POST['affiliate_customer']) . ", affiliate_source = '$source' WHERE ID = " . $u->ID . " LIMIT 1");

                alog("customers", "affiliate_changed", $u->ID, $u->affiliate, $_POST['affiliate_customer']);

                if ($_POST['affiliate_customer'] != "0") {
                    unset($_POST);
                    if ($u->affiliate == "0") {
                        $suc = $l['SUC7'];
                    } else {
                        $suc = $l['SUC8'];
                    }

                } else {
                    unset($_POST);
                    if ($u->affiliate != "0") {
                        $suc = $l['SUC9'];
                    } else {
                        throw new Exception($l['SUC10']);
                    }

                }
            } catch (Exception $ex) {
                $err = $ex->getMessage();
            }
        }

        if (isset($_POST['do_affiliate_add']) && $CFG['AFFILIATE_ACTIVE'] == "1" && $tab == "affiliate") {
            try {
                if (!isset($_POST['affiliate_customer']) || !is_numeric($_POST['affiliate_customer']) || !is_object($afInfo = $db->query("SELECT affiliate FROM clients WHERE ID = " . intval($_POST['affiliate_customer']))) || $afInfo->num_rows != 1) {
                    throw new Exception($l['SUC11']);
                }

                if (($affiliate = $afInfo->fetch_object()->affiliate) != "0" && $affiliate != $u->ID && (empty($_POST['affiliate_force']) || $_POST['affiliate_force'] != "1")) {
                    throw new Exception($l['SUC12']);
                }

                $source = isset($_POST['affiliate_source']) ? $db->real_escape_string($_POST['affiliate_source']) : "";

                $db->query("UPDATE clients SET affiliate = " . $u->ID . ", affiliate_source = '$source' WHERE ID = " . intval($_POST['affiliate_customer']) . " LIMIT 1");

                alog("customers", "affiliate_set", $_POST['affiliate_customer'], $u->ID);

                unset($_POST);
                $suc = $l['SUC13'];
            } catch (Exception $ex) {
                $err = $ex->getMessage();
            }
        }

        if (isset($_POST['revert_affiliate_transactions']) && $CFG['AFFILIATE_ACTIVE'] == "1" && $tab == "affiliate") {
            $d = 0;

            if (isset($_POST['t']) && is_array($_POST['t'])) {
                foreach ($_POST['t'] as $id) {
                    $db->query("UPDATE client_affiliate SET cancelled = 1 WHERE ID = " . intval($id));

                    if ($db->affected_rows) {
                        $amount = $db->query("SELECT amount FROM client_affiliate WHERE ID = " . intval($id))->fetch_object()->amount;
                        $affiliate = $db->query("SELECT affiliate FROM client_affiliate WHERE ID = " . intval($id))->fetch_object()->affiliate;
                        $db->query("UPDATE clients SET affiliate_credit = affiliate_credit - " . doubleval($amount) . " WHERE ID = {$affiliate} LIMIT 1");
                        alog("customers", "revert_affiliate_transaction", $id, $amount, $affiliate);
                        $d++;
                    }
                }
            }

            if ($d == 1) {
                $suc = $l['SUC14O'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC14X']);
            }

        }

        if (isset($_POST['do_affiliate_transaction']) && $CFG['AFFILIATE_ACTIVE'] == "1" && $tab == "affiliate") {
            try {
                if (!isset($_POST['time']) || !strtotime($_POST['time'])) {
                    throw new Exception($l['ERR2']);
                }

                $a = $nfo->phpize($_POST['amount']);
                if (!is_double($a) && !is_numeric($a)) {
                    throw new Exception($l['ERR3']);
                }

                $db->query("INSERT INTO client_affiliate (time, user, affiliate, amount) VALUES (" . strtotime($_POST['time']) . ", {$u->ID}, {$u->affiliate}, " . doubleval($a) . ")");
                $db->query("UPDATE clients SET affiliate_credit = affiliate_credit + " . doubleval($a) . " WHERE ID = {$u->affiliate} LIMIT 1");

                alog("customers", "affiliate_followup", $u->ID, $u->affiliate, $a);
                unset($_POST);
                $suc = $l['SUC15'];
            } catch (Exception $ex) {
                $err = $ex->getMessage();
            }
        }

        if (isset($_POST['save_credit']) && $ari->check(16)) {
            $credit = $db->real_escape_string($nfo->phpize($_POST['credit']));
            $special_credit = $db->real_escape_string($nfo->phpize($_POST['special_credit']));
            $db->query("UPDATE clients SET credit = '$credit', special_credit = '$special_credit' WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");

            alog("customers", "save_credit", $u->ID, $credit, $special_credit);

            $suc_trans = $l['SUC16'];
        }

        if ($tab == "domains" && $ari->check(13)) {
            if (isset($_GET['contacts']) && is_numeric($_GET['contacts']) && ($_GET['contacts'] == "0" || $_GET['contacts'] == "1")) {
                $db->query("UPDATE clients SET domain_contacts = " . intval($_GET['contacts']) . " WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
                alog("customers", "domain_contacts", $u->ID, $_GET['contacts']);
            }

            if (isset($_GET['authlock']) && is_numeric($_GET['authlock']) && ($_GET['authlock'] == "0" || $_GET['authlock'] == "1" || $_GET['authlock'] == "-1")) {
                $db->query("UPDATE clients SET auth_lock = " . intval($_GET['authlock']) . " WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
                alog("customers", "domain_authlock", $u->ID, $_GET['authlock']);
            }

            if (isset($_GET['api']) && is_numeric($_GET['api']) && ($_GET['api'] == "0" || $_GET['api'] == "1")) {
                $db->query("UPDATE clients SET domain_api = " . intval($_GET['api']) . " WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
                alog("customers", "domain_api", $u->ID, $_GET['api']);
            }

            if (isset($_POST['ns1'])) {
                $ns = array();
                $ns[0] = !empty($_POST['ns1']) ? $_POST['ns1'] : $CFG['NS1'];
                $ns[1] = !empty($_POST['ns2']) ? $_POST['ns2'] : $CFG['NS2'];
                $ns[2] = isset($_POST['ns3']) ? $_POST['ns3'] : $CFG['NS3'];
                $ns[3] = isset($_POST['ns4']) ? $_POST['ns4'] : $CFG['NS4'];
                $ns[4] = isset($_POST['ns5']) ? $_POST['ns5'] : $CFG['NS5'];

                $ns = serialize($ns);
                $userInstance->set(array("dns_server" => $ns));
                alog("customers", "dns_servers", $u->ID);
                $suc = $l['SUC17'];
            }

            if (isset($_POST['registrar_settings']) && is_array($_POST['registrar_settings'])) {
                $registrar_settings = serialize($_POST['registrar_settings']);
                $userInstance->set(array("registrar_settings" => $registrar_settings));
                alog("customers", "registrar_settings", $u->ID);
                $suc = $l['SUC18'];
            }
        }

        if ($tab == "invoices" && $ari->check(25)) {
            if (isset($_GET['no_reminders']) && is_numeric($_GET['no_reminders']) && ($_GET['no_reminders'] == "0" || $_GET['no_reminders'] == "1")) {
                $db->query("UPDATE clients SET no_reminders = " . intval($_GET['no_reminders']) . " WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
                alog("customers", "no_reminders", $u->ID, $_GET['no_reminders']);
            }
        }

        if ($tab == "laterinvoice" && $ari->check(13)) {
            if (isset($_GET['do']) && $_GET['do'] == "1") {
                $userInstance->invoiceNow(true);
                alog("customers", "laterinvoice_now", $u->ID);
                $suc = $l['SUC19'];
            }

            if (isset($_POST['ids']) && is_array($_POST['ids'])) {
                $d = 0;
                foreach ($_POST['ids'] as $id) {
                    if ($db->query("DELETE FROM invoicelater WHERE ID = " . intval($id) . " AND user = {$u->ID} LIMIT 1") && $db->affected_rows > 0) {
                        $d++;
                        alog("customers", "laterinvoice_delete_item", $u->ID, $id);
                    }
                }

                if ($d == 1) {
                    $suc = $l['SUC20O'];
                } else if ($d > 0) {
                    $suc = str_replace("%d", $d, $l['SUC20X']);
                }

            }

            if (isset($_POST['invoicelater']) && is_numeric($_POST['invoicelater']) && $_POST['invoicelater'] >= 1 && $_POST['invoicelater'] <= 31) {
                $u->invoicelater = intval($_POST['invoicelater']);
                $userInstance->set(array("invoicelater" => $u->invoicelater));
                alog("customers", "laterinvoice_day", $u->ID, $u->invoicelater);
                $suc = $l['SUC21'];
            }
        }

        if ($tab == "invoices" && $ari->check(13)) {
            $inv = new Invoice;
            if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
                $d = 0;

                if (isset($_POST['mark_paid'])) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id) && $inv->getStatus() != 1) {
                            $inv->setStatus(1);
                            $inv->save();
                            alog("invoice", "mark_paid", $id);
                            $d++;
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC22O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC22X']);
                    }

                } else if (isset($_POST['mark_unpaid'])) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id) && $inv->getStatus() != 0 && $inv->getAmount() != 0) {
                            $inv->setStatus(0);
                            $inv->save();
                            alog("invoice", "mark_unpaid", $id);
                            $d++;
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC23O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC23X']);
                    }

                } else if (isset($_POST['cancel'])) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id) && $inv->getStatus() != 2) {
                            $inv->setStatus(2);
                            $inv->save();
                            alog("invoice", "cancel", $id);
                            $d++;
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC24O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC24X']);
                    }

                } else if (isset($_POST['delete'])) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id) && $inv->delete() !== false) {
                            $d++;
                            alog("invoice", "delete", $id);
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC25O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC25X']);
                    }

                } else if (isset($_POST['clear_data'])) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id)) {
                            $inv->clearClientData();
                            $inv->save();
                            alog("invoice", "clear_data", $id);
                            $d++;
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC26O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC26X']);
                    }

                } else if (isset($_POST['credit_pay'])) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id)) {
                            if ($inv->applyCredit() === false) {
                                continue;
                            }

                            alog("invoice", "credit_pay", $id);
                            $d++;
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC27O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC27X']);
                    }

                } else if (isset($_POST['split'])) {
                    $d = 0;

                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id)) {
                            $items = $inv->getItems();
                            if (count($items) <= 1) {
                                continue;
                            }

                            $items = array_splice($items, 1);
                            $d += count($items);

                            foreach ($items as $i) {
                                $ni = new Invoice;
                                $ni->setDate($inv->getDate());
                                $ni->setClient($inv->getClient());
                                $ni->setDueDate($inv->getDueDate());
                                $ni->setStatus($inv->getStatus());
                                $ni->setClientData($inv->getClientData());
                                $ni->save();

                                $i->setInvoice($ni);
                                $i->save();
                            }

                            alog("invoice", "split_and_delete", $id);
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC28O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC28X']);
                    }

                } else if (isset($_POST['cancel_invoice'])) {
                    $d = 0;

                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id)) {
                            if ($inv->cancel()) {
                                alog("invoice", "cancelled", $id);
                                $d++;
                            }
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC29O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC29X']);
                    }

                } else if (isset($_POST['send_mail'])) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id)) {
                            $inv->send("send");
                            alog("invoice", "sent_mail", $id);
                            $d++;
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC30O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC30X']);
                    }

                } else if (isset($_POST['send_letter'])) {
                    $oldLang = $lang;

                    $sql = $db->query("SELECT alpha2 FROM client_countries WHERE ID = {$u->country}");
                    if ($sql->num_rows == 1) {
                        $alpha2 = $sql->fetch_object()->alpha2;

                        foreach ($_POST['invoices'] as $id) {
                            if ($inv->load($id)) {
                                $uI = User::getInstance($inv->getClient(), "ID");
                                if (!$uI) {
                                    continue;
                                }

                                $uI->loadLanguage();

                                $pdf = new PDFInvoice();
                                $pdf->add($inv);
                                if (file_exists(__DIR__ . "/tmp.pdf")) {
                                    unlink(__DIR__ . "/tmp.pdf");
                                }

                                $pdf->output(__DIR__ . "/tmp", "F", false);

                                $ex = explode("#", $_POST['send_letter'], 2);

                                if (LetterHandler::myDrivers()[$ex[0]]->sendLetter(__DIR__ . "/tmp.pdf", true, $alpha2, $ex[1]) === true) {
                                    $d++;
                                    $inv->setLetterSent(1);
                                    $inv->save();
                                    alog("invoice", "sent_letter", $id, $_POST['send_letter']);
                                }

                                if (file_exists(__DIR__ . "/tmp.pdf")) {
                                    unlink(__DIR__ . "/tmp.pdf");
                                }

                            }
                        }

                        if ($d == 1) {
                            $msg = $l['SUC31O'];
                        } else if ($d > 0) {
                            $msg = str_replace("%d", $d, $l['SUC31X']);
                        }

                    }

                    $lang = $oldLang;
                } else if (isset($_POST['no_reminders']) && in_array($_POST['no_reminders'], array("0", "1"))) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id) && $inv->getReminders() != ($_POST['no_reminders'] ? false : true)) {
                            $inv->setReminders($_POST['no_reminders'] ? false : true);
                            $inv->save();
                            alog("invoice", "set_reminders", $id, $_POST['no_reminders']);
                            $d++;
                        }
                    }

                    if ($_POST['no_reminders']) {
                        if ($d == 1) {
                            $msg = $l['SUC33O'];
                        } else if ($d > 0) {
                            $msg = str_replace("%d", $d, $l['SUC33X']);
                        }

                    } else {
                        if ($d == 1) {
                            $msg = $l['SUC34O'];
                        } else if ($d > 0) {
                            $msg = str_replace("%d", $d, $l['SUC34X']);
                        }

                    }
                } else if (isset($_POST['reminder']) && ($_POST['reminder'] == "0" || $db->query("SELECT 1 FROM reminders WHERE ID = " . intval($_POST['reminder']))->num_rows == 1)) {
                    foreach ($_POST['invoices'] as $id) {
                        if ($inv->load($id) && $inv->getReminder() != $_POST['reminder']) {
                            $inv->setReminder($_POST['reminder']);
                            $inv->save();
                            alog("invoice", "set_reminder_level", $id, $_POST['reminder']);
                            $d++;
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['SUC35O'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['SUC35X']);
                    }

                }
            }
        }

        if (isset($_POST['save_postpaid']) && $ari->check(16)) {
            $n = $nfo->phpize($_POST['postpaid']);
            if (is_double($n) || is_numeric($n)) {
                $n = $n < 0 ? 0 : $n;
                User::getInstance($u->mail)->set(array("postpaid" => $n));
                alog("customer", "postpaid", $u->ID, $n);
                $suc_trans = $l['SUC36'];
            }
        }

        if (isset($_POST['save_payment_methods']) && $ari->check(16)) {
            $disabled = array_keys($gateways->get());
            foreach ($disabled as $k => $v) {
                if (!$gateways->get()[$v]->isActive()) {
                    unset($disabled[$k]);
                }
            }

            if (is_array($_POST['disabled_payment'])) {
                foreach ($_POST['disabled_payment'] as $k) {
                    unset($disabled[array_search($k, $disabled)]);
                }
            }

            User::getInstance($u->mail)->set(array("disabled_payment" => implode(",", $disabled)));
            alog("customer", "payment_methods", $u->ID);
            $suc_trans = $l['SUC37'];
        }

        if (!empty($_POST['autoPaymentAmount']) && $ari->check(16)) {
            $amount = doubleval($nfo->phpize($_POST['autoPaymentAmount']));
            if ($amount <= 0) {
                $err_trans = $l['ERR4'];
            } else {
                if ($uI->autoPayment($amount)) {
                    $suc_trans = $l['SUC38'];
                } else {
                    $err_trans = $l['ERR5'];
                }
            }
        }

        if (isset($_POST['insert_payment']) && $ari->check(16)) {
            try {
                if (!isset($_POST['payment_method']) || !isset($gateways->get()[$_POST['payment_method']])) {
                    throw new Exception($l['ERR6']);
                }

                $gI = $gateways->get()[$_POST['payment_method']];

                if (empty($_POST['transaction_id'])) {
                    throw new Exception($l['ERR7']);
                }

                $gateway = str_replace("|", "", $_POST['payment_method']);
                if (count($transactions->get(array("subject" => "$gateway|" . $_POST['transaction_id']))) > 0) {
                    throw new Exception($l['ERR8']);
                }

                if (!isset($_POST['amount']) || $nfo->phpize($_POST['amount']) <= 0) {
                    throw new Exception($l['ERR9']);
                }

                $amount = $nfo->phpize($_POST['amount']);

                $email = "";
                if (isset($_POST['send_email']) && $_POST['send_email'] == "yes") {
                    $email = " " . $l['SUC39'];

                    $sendLang = $userInstance->getLanguage();
                    $currency = $userInstance->getCurrency();

                    $camount = $cur->convertAmount(null, $amount, $currency);

                    $mtObj = new MailTemplate("Guthabenaufladung");
                    $title = $mtObj->getTitle($sendLang);
                    $mail = $mtObj->getMail($sendLang, $u->firstname . " " . $u->lastname);
                    $maq->enqueue([
                        "amount" => $cur->infix($nfo->format($camount, 2, 0, $userInstance->getNumberFormat()), $currency),
                        "processor" => html_entity_decode($gateways->get()[$_POST['payment_method']]->getLang('name', $sendLang)),
                    ], $mtObj, $u->mail, $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->ID);
                }

                $amount = doubleval($amount);
                $transactions->insert($gateway, $_POST['transaction_id'], $amount, $u->ID, "", 1, 0, $_POST['date'] ?? "");
                $db->query("UPDATE clients SET credit = credit + $amount WHERE ID = " . $u->ID . " LIMIT 1");

                $fees = "";
                $fa = $gI->getFees($amount);
                if ($fa > 0 && isset($_POST['invoice_fees']) && $_POST['invoice_fees'] == "yes") {
                    $uI = User::getInstance($u->mail);
                    $uI->invoiceFees($fa, true, isset($_POST['email_fees']) && $_POST['email_fees'] == "yes");
                    $fees = " " . str_replace("%f", $cur->infix($nfo->format($fa), $cur->getBaseCurrency()), $l['SUC40']);
                }

                alog("customer", "insert_payment", $u->ID, $amount, $_POST['transaction_id']);

                $suc_trans = $l['SUC41'] . "$fees$email";
            } catch (Exception $ex) {
                $err_trans = $ex->getMessage();
            }
        }

        if ($tab == "cart" && isset($_GET['orders']) && in_array($_GET['orders'], array("0", "1")) && $ari->check(48)) {
            User::getInstance($u->ID, "ID")->set(array("orders_active" => $_GET['orders']));
            alog("customer", "orders_active", $u->ID, $_GET['orders']);
        }

        if (isset($_GET['delete_license']) && is_numeric($_GET['delete_license']) && ($lid = intval($_GET['delete_license'])) > 0 && $ari->check(13)) {
            $db->query("DELETE FROM  client_products WHERE user = " . $u->ID . " AND ID = $lid LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['SUC42'];
                alog("license", "delete", $lid);
            }
        }

        if (isset($_POST['delete_selected_products']) && is_array($_POST['products']) && $ari->check(13)) {
            $d = 0;
            foreach ($_POST['products'] as $pid) {
                $sql = $db->query("DELETE FROM  client_products WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($pid) . "' LIMIT 1");
                if ($sql && $db->affected_rows > 0) {
                    $d++;
                    alog("license", "delete", $pid);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC43O'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC43X']);
            }

        }

        if (isset($_POST['delete_selected_domains']) && is_array($_POST['domains']) && $ari->check(13)) {
            $d = 0;
            foreach ($_POST['domains'] as $pid) {
                $sql = $db->query("DELETE FROM  domains WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($pid) . "' LIMIT 1");
                if ($sql && $db->affected_rows > 0) {
                    $d++;
                    alog("domain", "delete", $pid);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC980'];
            } else if ($d > 0) {
                $suc = str_replace("%x", $d, $l['SUC98X']);
            }

        }

        if (isset($_POST['lock_selected_products']) && is_array($_POST['products']) && $ari->check(13)) {
            $d = 0;
            foreach ($_POST['products'] as $pid) {
                $sql = $db->query("UPDATE  client_products SET active = 0 WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($pid) . "' LIMIT 1");
                if ($sql && $db->affected_rows > 0) {
                    $d++;
                    alog("license", "lock", $pid);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC44O'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC44X']);
            }

        }

        if (isset($_POST['unlock_selected_products']) && is_array($_POST['products']) && $ari->check(13)) {
            $d = 0;
            foreach ($_POST['products'] as $pid) {
                $sql = $db->query("UPDATE  client_products SET active = 1 WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($pid) . "' LIMIT 1");
                if ($sql && $db->affected_rows > 0) {
                    $d++;
                    alog("license", "unlock", $pid);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC45O'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC45X']);
            }

        }

        if (isset($_GET['deletereq']) && is_numeric($_GET['deletereq']) && $ari->check(10)) {
            $db->query("DELETE FROM  client_mailchanges WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($_GET['deletereq']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['SUC46'];
                alog("customers", "delete_email_change_req", $u->ID, $_GET['deletereq']);
            }
        }

        if (isset($_POST['revert_selected_transactions']) && is_array($_POST['transactions']) && $ari->check(16)) {
            $d = 0;
            foreach ($_POST['transactions'] as $id) {
                $trans = $transactions->get(array("ID" => $id), 1);
                if (count($trans) == 1) {
                    $i = (object) $trans[0];
                    $db->query("DELETE FROM  client_transactions WHERE waiting = 0 AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                    if ($db->affected_rows > 0) {
                        $db->query("UPDATE clients SET credit = credit - '" . $i->amount . "' WHERE ID = " . $i->user . " LIMIT 1");
                        $u->credit = $u->credit - $i->amount;

                        if ($db->affected_rows > 0) {
                            $d++;
                            alog("transaction", "revert", $id, $i->amount, $i->user);
                        }
                    }
                }
            }

            if ($d == 1) {
                $suc = $l['SUC47O'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC47X']);
            }

        }

        if (isset($_GET['pay_undone']) && $ari->check(16)) {
            $trans = $transactions->get(array("ID" => $_GET['pay_undone']), 1);
            if (count($trans) == 1) {
                $i = (object) $trans[0];
                $db->query("DELETE FROM  client_transactions WHERE waiting = 0 AND ID = '" . $db->real_escape_string($_GET['pay_undone']) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $db->query("UPDATE clients SET credit = credit - '" . $i->amount . "' WHERE ID = " . $i->user . " LIMIT 1");
                    $u->credit = $u->credit - $i->amount;

                    if ($db->affected_rows > 0) {
                        $suc = $l['SUC48'];
                        alog("transaction", "revert", $_GET['pay_undone'], $i->amount, $i->user);
                    }
                }
            }
        }

        if (isset($_GET['refund']) && $ari->check(16)) {
            $trans = $transactions->get(array("ID" => $_GET['refund']), 1);
            if (count($trans) == 1) {
                $b = (object) $trans[0];
                if ($b->deposit && $b->amount > 0 && !$b->waiting) {
                    $ex = explode("|", $b->raw_subject);
                    if (array_key_exists($ex[0], $gateways->get()) && $gateways->get()[$ex[0]]->canRefund()) {
                        if (!$gateways->get()[$ex[0]]->refundPayment($ex[1])) {
                            $err = $l['ERR10'];
                        } else {
                            $usr = User::getInstance($b->user, "ID");
                            $usr->set(array("credit" => $usr->get()['credit'] - $b->amount));
                            $db->query("DELETE FROM client_transactions WHERE ID = " . $b->ID . " LIMIT 1");
                            $suc = $l['SUC49'];
                            alog("transactions", "refund", $b->ID);
                        }
                    }
                }
            }
        }

        if (isset($_GET['pay_ok']) && $ari->check(16)) {
            $trans = $transactions->get(array("ID" => $_GET['pay_ok']), 1);
            if (count($trans) == 1) {
                $i = (object) $trans[0];
                if ($i->waiting) {
                    $db->query("UPDATE  client_transactions SET waiting = 0 WHERE ID = '" . $db->real_escape_string($_GET['pay_ok']) . "' LIMIT 1");
                    if ($i->waiting == 1) {
                        $db->query("UPDATE clients SET credit = credit + '" . $i->amount . "' WHERE ID = " . $i->user . " LIMIT 1");
                        $u->credit = $u->credit + $i->amount;
                    }

                    if ($db->affected_rows > 0) {
                        $suc = $l['SUC50'];
                        alog("transactions", "done", $_GET['pay_ok']);
                    }
                }
            }
        }

        if (isset($_POST['delete_selected_transactions']) && is_array($_POST['transactions']) && $ari->check(16)) {
            $d = 0;
            foreach ($_POST['transactions'] as $id) {
                $res = $transactions->delete($id);
                if ($res) {
                    $d++;
                    alog("transactions", "delete", $id);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC51O'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC51X']);
            }

        }

        if (isset($_GET['pay_delete']) && $ari->check(16)) {
            $trans = $transactions->get(array("ID" => $_GET['pay_delete']), 1);
            if (count($trans) == 1) {
                $i = (object) $trans[0];
                if ($i->waiting == 2) {
                    $db->query("UPDATE clients SET credit = credit - '" . $i->amount . "' WHERE ID = " . $i->user . " LIMIT 1");
                    $u->credit = $u->credit + $i->amount;
                }

                $transactions->delete($_GET['pay_delete']);
                alog("transactions", "pay_delete", $_GET['pay_delete']);
                $suc = $l['SUC52'];
            }
        }

        if (isset($_GET['new_api_key']) && $ari->check(10)) {
            // Generate a new API key for this user and save it to the database
            $key = md5(uniqid(mt_rand(), true));
            $db->query("UPDATE clients SET api_key = '$key' WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['SUC53'];
                alog("customers", "new_api_key", $u->ID);
            }
        }

        if ($ari->check(10) && ($tab == "profile" || $tab == "history") && in_array("new_pw", array_keys($_GET))) {
            $pwd = $userInstance->generatePassword();

            $language = $userInstance->getLanguage();

            $mtObj = new MailTemplate("Neues Passwort");
            $title = $mtObj->getTitle($language);

            $mail = $mtObj->getMail($language, $userInstance->get()['name']);

            $maq->enqueue([
                "mail" => $userInstance->get()['mail'],
                "pwd" => $pwd,
                "password" => $pwd,
            ], $mtObj, $userInstance->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $userInstance->get()['ID'], true, 0, 0, $mtObj->getAttachments($language));

            alog("customers", "new_pw_sent", $u->ID);

            $addons->runHook("CustomerChangePassword", [
                "user" => User::getInstance($u->ID, "ID"),
                "source" => "admin_newpw",
            ]);

            $suc = $l['SUC54'];
        }

        $userInstance = new User($u->mail);
        $u = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1")->fetch_object();

        $openEmailChangeRequestsSql = $db->query("SELECT * FROM client_mailchanges WHERE user = " . $userInstance->get()['ID']);
        $openEmailChangeRequests = $openEmailChangeRequestsSql->num_rows;

        $create = $createModal = "";
        if ($tab == "contacts") {
            $create = "?p=add_contact&user=" . $u->ID;
        } else if ($ari->check(25) && $tab == "products") {
            $create = "?p=new_product&user=" . $u->ID;
        } else if ($ari->check(30) && $tab == "projects") {
            $create = "?p=add_project&user=" . $u->ID;
        } else if ($ari->check(25) && $tab == "recurring") {
            $create = "?p=new_recurring_invoice&user=" . $u->ID;
        } else if ($ari->check(25) && $tab == "invoices") {
            $create = "?p=new_invoice&user=" . $u->ID;
        } else if ($ari->check(7) && $tab == "quotes") {
            $create = "?p=new_quote&client=" . $u->ID;
        } else if ($ari->check(7) && $tab == "letters") {
            $create = "?p=new_letter&client=" . $u->ID;
        } else if ($ari->check(10) && $tab == "files") {
            $createModal = "#fileModal";
        } else if ($ari->check(16) && $tab == "transactions") {
            $createModal = "#transactionModal";
        } else if ($ari->check(62) && $tab == "scoring") {
            $createModal = "#newscore";
        } else if ($ari->check(47) && $tab == "mails") {
            $create = "?p=customers&tab=send_mail&edit=" . $u->ID;
            $noblank = true;
        } else if ($ari->check(20) && $tab == "notes") {
            $create = "?p=customers&tab=notes&new_note=1&edit=" . $u->ID;
            $noblank = true;
        } else if ($tab == "tickets") {
            $create = "?p=new_ticket&client=" . $u->ID;
        } else if ($tab == "affiliate" && $CFG['AFFILIATE_ACTIVE'] == "1") {
            $createModal = "#affiliateAddModal";
        } else if ($tab == "telephone" && $ari->check(51)) {
            $create = "?p=customers&tab=telephone&action=new&edit=" . $u->ID . "\" class=\"pull-right\"><i class=\"fa fa-play-circle\"></i></a> <a style=\"margin-right: 5px;\" href=\"?p=customers&tab=telephone&action=import&edit=" . $u->ID;
            $noblank = true;
        } else if ($tab == "domains" && $ari->check(13)) {
            $create = "?p=add_domain&user=" . $u->ID;
        }

        ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header">
				<small><?=htmlentities($CFG['CNR_PREFIX']);?><?=$userInstance->get()['ID'];?></small> <a href="<?=$userInstance->getAvatar(500);?>"><img src="<?=$userInstance->getAvatar();?>" style="border-radius: 50%; margin-top: -7px; height: 40px;" title="<?=htmlentities($u->firstname . " " . $u->lastname);?>" alt="<?=htmlentities($u->firstname . " " . $u->lastname);?>"></a> <?=$userInstance->getfName();?><?php if (trim($u->company) != "") {
            echo " <small>" . htmlentities(trim($u->company)) . "</small>";
        }
        ?><?php if ($u->locked == 1) {
            echo " <small><font color=\"red\">" . $l['LOCKED'] . "</font></small>";
        }
        ?>
				<?php if ($create) {?><a href="<?=$create;?>"<?=empty($noblank) ? ' target="_blank"' : '';?> class="pull-right"><i class="fa fa-plus-circle"></i></a><?php }?>
				<?php if ($createModal) {?><a data-target="<?=$createModal;?>" onclick="return false;" href="#" data-toggle="modal" class="pull-right"><i class="fa fa-plus-circle"></i></a><?php }?>
			</h1>

        <div class="row">

        <div class="col-md-12">

        <?php if ($u->credit < 0 && $ari->check(15)) {?>
        <div class="alert alert-danger"><?=str_replace("%a", $cur->infix($nfo->format($u->credit / -1), $cur->getBaseCurrency()), $l['DEBTOR']);?></div>
        <?php }?>

        <?php
$open = 0;
        $count = 0;
        $inv = new Invoice;
        $sql = $db->query("SELECT ID FROM invoices WHERE client = " . $u->ID . " AND status = 0");
        while ($row = $sql->fetch_object()) {
            $inv->load($row->ID);
            $open += $inv->getAmount();
            if ($inv->getAmount() != 0) {
                $count++;
            }

        }

        $later_sum = $db->query("SELECT SUM(amount) AS s FROM invoicelater WHERE user = " . $u->ID)->fetch_object()->s;

        if ($open > 0 && ($ari->check(13) || $ari->check(14))) {?>
        <div class="alert alert-warning">
				<?php
if ($count == 1) {
            echo str_replace("%a", $cur->infix($nfo->format($open), $cur->getBaseCurrency()), $l['ONEOPENINVOICE']);
        } else {
            echo str_replace(["%i", "%a"], [$count, $cur->infix($nfo->format($open), $cur->getBaseCurrency())], $l['XOPENINVOICES']);
        }
            ?>
				</div>
        <?php }if ($later_sum != 0 && ($ari->check(13) || $ari->check(14))) {?>
        <div class="alert alert-info">
				<?php
echo str_replace("%a", $cur->infix($nfo->format($later_sum), $cur->getBaseCurrency()), $l['INVOICELATER']);
            ?>
				</div>
        <?php }?>
        <?php if ($ari->check(19) && $tab != "notes") {$nsql = $db->query("SELECT * FROM client_notes WHERE user = " . $u->ID . " AND sticky = 1 ORDER BY ID DESC");while ($row = $nsql->fetch_object()) {?>
        <div class="alert alert-info">
			<a href="#" class="note_link" style="text-decoration: none;">
				<b><?=$row->title;?></b>
				<i class="fa fa-play pull-right"></i>
			</a>
			<p class="note_body" style="display: none;"><?=nl2br(htmlentities($row->text));?></p>
		</div>
        <?php }}?>

        </div>

		<script>
		$(document).ready(function() {
			$(".note_link").click(function(e) {
				e.preventDefault();

				if ($(this).find("i").hasClass("fa-rotate-90")) {
					$(this).find("i").removeClass("fa-rotate-90");
					$(this).parent().find(".note_body").slideUp();
				} else {
					$(this).find("i").addClass("fa-rotate-90");
					$(this).parent().find(".note_body").slideDown();
				}
			});
		});
		</script>

        <?php
$cartSql = $db->query("SELECT SUM(qty) as sum FROM client_cart WHERE type != 'voucher' AND user = " . $u->ID)->fetch_object();
        if ($cartSql->sum === null) {
            $cart = 0;
        } else {
            $cart = $cartSql->sum;
        }

        ?>

        <div class="col-md-3">
            <div class="list-group">
             <a class="list-group-item<?=$tab == 'profile' || $tab == 'history' || $tab == 'contacts' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=profile"><?=$l['T1'];?></a>
             <?php if ($u->reseller && $ari->check(13)) {?> <a class="list-group-item<?=$tab == 'reseller' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=reseller"><?=$l['TRES'];?> (<?=$db->query("SELECT ID FROM client_customers WHERE uid = " . $u->ID)->num_rows;?>)</a><?php }?>
            <?php if ($ari->check(13)) {?><a class="list-group-item<?=$tab == 'products' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=products"><?=$l['T2'];?> (<?=$db->query("SELECT ID FROM client_products WHERE user = " . $u->ID)->num_rows;?>)</a>
            <a class="list-group-item<?=$tab == 'domains' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=domains"><?=$l['T3'];?> (<?=$db->query("SELECT ID FROM domains WHERE status IN ('REG_OK', 'KK_OK', 'REG_WAITING', 'KK_WAITING', 'KK_ERROR', 'REG_ERROR') AND user = " . $u->ID)->num_rows;?>)</a><?php }?>
            <?php if ($ari->check(30)) {?><a class="list-group-item<?=$tab == 'projects' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=projects"><?=$l['T4'];?> (<?=$db->query("SELECT ID FROM projects WHERE user = " . $u->ID)->num_rows;?>)</a><?php }?>
            <?php if ($ari->check(13)) {?><a class="list-group-item<?=$tab == 'recurring' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=recurring"><?=$l['T5'];?> (<?=$db->query("SELECT ID FROM invoice_items_recurring WHERE status = 1 AND user = " . $u->ID)->num_rows;?>)</a><?php }?>
			<?php if ($ari->check(13) && !$CFG['NO_INVOICING']) {?><a class="list-group-item<?=$tab == 'invoices' || $tab == 'laterinvoice' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=invoices"><?=$l['T6'];?> (<?=count($userInstance->getInvoices());?>)</a><?php }?>
            <?php if ($ari->check(7)) {?><a class="list-group-item<?=$tab == 'quotes' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=quotes"><?=$l['T7'];?> (<?=$db->query("SELECT 1 FROM client_quotes WHERE client = {$u->ID}")->num_rows;?>)</a><?php }?>
            <?php if ($ari->check(10)) {?><a class="list-group-item<?=$tab == 'files' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=files"><?=$l['T9'];?> (<?=$db->query("SELECT ID FROM client_files WHERE user = " . $u->ID)->num_rows;?>)</a><?php }?>
            <?php if ($ari->check(15)) {?><a class="list-group-item<?=$tab == 'transactions' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=transactions"><?=$l['T10'];?> (<?=$cur->infix($nfo->format($u->credit), $cur->getBaseCurrency());?>)</a><?php }?>
              <?php if ($ari->check(62)) {?><a class="list-group-item<?=$tab == 'scoring' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=scoring"><?=$l['T11'];?> (<?=$nfo->format(User::getInstance($_GET['edit'], "ID")->getScore(), 0);?>%)</a><?php }?>
            <?php if ($ari->check(17)) {?><a class="list-group-item<?=$tab == 'ip' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=ip"><?=$l['T12'];?></a><?php }?>
            <?php if ($ari->check(49)) {?><a class="list-group-item<?=$tab == 'log' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=log"><?=$l['T13'];?></a><?php }?>
            <?php if ($ari->check(18)) {?><a class="list-group-item<?=$tab == 'cart' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=cart"><?=$l['T14'];?> (<?=$cart;?>)</a><?php }?>
            <a class="list-group-item<?=$tab == 'cookies' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=cookies"><?=$l['T15'];?></a>
			<?php if ($ari->check(47)) {?><a class="list-group-item<?=$tab == 'mails' || $tab == 'send_mail' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=mails"><?=$l['T16'];?> (<?=$db->query("SELECT ID FROM client_mails WHERE user = " . $u->ID)->num_rows;?>)</a><?php }?>
      <?php
if ($ari->check(65)) {
            if (!isset($customSupportTicketsLink)) {?>
      <a class="list-group-item<?=$tab == 'tickets' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=tickets"><?=$l['T17'];?> (<?=$db->query("SELECT ID FROM support_tickets WHERE customer = " . $u->ID)->num_rows;?>)</a>
      <?php if ($ari->check(68)) {?><a class="list-group-item<?=$tab == 'abuse' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=abuse"><?=$lang['ABUSE']['TITLE'];?> (<?=$db->query("SELECT ID FROM abuse WHERE user = " . $u->ID)->num_rows;?>)</a><?php }?>
			<?php } else {echo $customSupportTicketsLink;}}if ($ari->check(7)) {?><a class="list-group-item<?=$tab == 'letters' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=letters"><?=$l['T18'];?> (<?=$db->query("SELECT ID FROM client_letters WHERE client = " . $u->ID)->num_rows;?>)</a><?php }?>
			<?php if ($ari->check(51)) {?><a class="list-group-item<?=$tab == 'telephone' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=telephone"><?=$l['T19'];?> (<?=$db->query("SELECT ID FROM client_calls WHERE user = " . $u->ID)->num_rows;?>)</a><?php }?>
                <?php if ($CFG['AFFILIATE_ACTIVE'] == "1") {?><a
                    class="list-group-item<?=$tab == 'affiliate' ? ' active' : '';?>"
                    href="?p=customers&edit=<?=$_GET['edit'];?>&tab=affiliate"><?=$l['T20'];?> (<?=$db->query("SELECT ID FROM clients WHERE affiliate = " . $u->ID)->num_rows;?>)</a><?php }?>
            <?php if ($ari->check(19)) {?><a class="list-group-item<?=$tab == 'notes' ? ' active' : '';?>" href="?p=customers&edit=<?=$_GET['edit'];?>&tab=notes"><?=$l['T21'];?> (<?=$db->query("SELECT ID FROM client_notes WHERE user = " . $u->ID)->num_rows;?>)</a><?php }?>
            <?php $addons->runHook("AdminCustomerSidebar", ["user" => User::getInstance($u->ID, "ID"), "tab" => $tab]);?>
            </div>

            <div class="list-group">
			<?php $addons->runHook("AdminCustomerSidebarActions", ["user" => User::getInstance($u->ID, "ID"), "tab" => $tab]);?>
             <?php if ($tab == "scoring" && $ari->check(62)) {?>
               <div class="modal fade" id="newscore" tabindex="-1" role="dialog">
         			  <div class="modal-dialog" role="document">
         			    <div class="modal-content"><form method="POST" role="form">
         			      <div class="modal-header">
         			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
         			        <h4 class="modal-title"><?=$l['NEWSCORE'];?></h4>
         			      </div>
         			      <div class="modal-body">
         			      	<div class="form-group">
                        <label><?=$l['SCORING'];?></label>
         					    <select name="rating" class="form-control" required="required">
											 <?php foreach (["A", "B", "C", "D", "E", "F"] as $let) {?>
         					    	<option value="<?=$let;?>"><?=$l['SCORING' . $let];?> (<?=$let;?>)</option>
											 <?php }?>
         					    </select>
         					      </div>
         			      	<div class="form-group">
                        <label><?=$l['SCOENTRY'];?></label>
             					    <input type="text" class="form-control" placeholder="<?=$l['SCOENTRYP'];?>" name="entry" required="required">
             					</div>
         			      </div>
         			      <div class="modal-footer">
         			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
         			        <button type="submit" name="newscore" class="btn btn-primary"><?=$l['SCODO'];?></button>
         			      </div></form>
         			    </div>
         			  </div>
					 </div>
			<?php
$additionalJS .= "function scoring(prov, method, name) {
				swal({
					title: name,
					text: '{$l['SCOREDO']}',
					type: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#DD6B55',
					confirmButtonText: '{$l['YES']}',
					cancelButtonText: '{$l['NO']}',
					closeOnConfirm: false
				}, function(){
					window.location = '?p=customers&edit={$u->ID}&tab=$tab&provider=' + prov + '&method=' + method;
				});
			}";
            ?>
              <?php foreach (ScoringHandler::getDrivers() as $short => $obj) {foreach ($obj->getMethods() as $m => $n) {?>
               <a class="list-group-item" href="#" onclick="scoring('<?=$short;?>', '<?=$m;?>', '<?=$n;?>'); return false;"><?=$n;?></a>
             <?php }}}?>
             <?php if ($tab == "profile" || $tab == "history" || $tab == "contacts") {?>
			 <a class="list-group-item" href="?p=vcard&user=<?=$u->ID;?>" target="_blank"><?=$l['VCARDDOWN'];?></a>
             <a class="list-group-item" href="?p=bdsg&user=<?=$u->ID;?>" target="_blank"><?=$l['BDSGDOWN'];?></a>
             <?php }?>
             <?php if ($ari->check(11) && ($tab == "profile" || $tab == "history" || $tab == "contacts")) {?>
             <a class="list-group-item" href="?p=customers&login=<?=$u->ID;?>" target="_blank"><?=$l['LOGASCLI'];?></a>
             <?php }?>
						 <?php if ($tab == "tickets") {?>
			<div class="modal fade" id="lockPrios" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['LOCKPRIOS'];?></h4>
			      </div>
			      <div class="modal-body">
						<?php
$disabled = explode(",", $u->disabled_support_prio);
            foreach (Ticket::getPriorityText(false) as $id => $text) {
                ?>

				    <div class="checkbox">
					  <label>
					    <input type="checkbox" name="disabled_support_prio[]" value="<?=$id;?>"<?=in_array($id, $disabled) ? ' checked="checked"' : '';?>> <?=$text;?>
					  </label>
					</div>

						<?php }?>

			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <input type="hidden" name="action" value="lockprios" />
			        <button type="submit" class="btn btn-primary"><?=$l['SAVE'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>

             <a class="list-group-item" href="#" data-toggle="modal" data-target="#lockPrios"><?=$l['LOCKPRIOS'];?></a>
             <?php }?>
             <?php if ($ari->check(10) && ($tab == "profile" || $tab == "history" || $tab == "contacts")) {?>
			 <a class="list-group-item" href="#" onclick="newPw(<?=$u->ID;?>); return false;"><?=$l['SENDNEWPW'];?></a>
			 <?php
$additionalJS .= "function newPw(id) {
				swal({
					title: '{$l['NEWPW']}',
					text: '{$l['NEWPWREALLY']}',
					type: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#DD6B55',
					confirmButtonText: '{$l['YES']}',
					cancelButtonText: '{$l['NO']}',
					closeOnConfirm: false
				}, function(){
					window.location = '?p=customers&edit=' + id + '&tab=$tab&new_pw';
				});
			}";
            ?>
             <?php }?>
             <?php if ($ari->check(10) && ($tab == "profile" || $tab == "history" || $tab == "contacts")) {?>
             <a class="list-group-item" href="?p=customers&merge=<?=$u->ID;?>"><?=$l['MERGECLI'];?></a>
             <a class="list-group-item" href="#" onclick="deleteCustomer(<?=$u->ID;?>); return false;"><?=$l['DELCLI'];?></a>
             <?php
$additionalJS .= "function deleteCustomer(id) {
				swal({
					title: '{$l['REALLYDEL']}',
					text: '{$l['REALLYDELCLI']}',
					type: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#DD6B55',
					confirmButtonText: '{$l['YES']}',
					cancelButtonText: '{$l['NO']}',
					closeOnConfirm: false
				}, function(){
					window.location = '?p=customers&delete=' + id;
				});
			}";
            ?>
             <?php }?>
             <?php if ($ari->check(16) && $tab == "transactions") {?>
             <div class="modal fade" id="transactionModal" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['DOTRANS'];?></h4>
			      </div>
			      <div class="modal-body">
				    <div class="form-group input-group">
						<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=trim($cur->getPrefix());?></span><?php }?>
						<input type="text" class="form-control" placeholder="<?=$nfo->format(-50);?> <?=$l['OR'];?> <?=$nfo->format(50);?>" name="amount">
						<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=trim($cur->getSuffix());?></span><?php }?>
					</div>
					<div class="form-group">
					    <input type="text" class="form-control" placeholder="<?=$l['DESC'];?>" name="description">
					</div>
				    <div class="checkbox">
					  <label>
					    <input type="checkbox" name="do_credit" value="yes" checked="checked"> <?=$l['DOCREDIT'];?>
					  </label>
					</div>
					<div class="checkbox">
					  <label>
					    <input type="checkbox" name="special_credit" value="yes" checked="checked"> <?=$l['DOSPECCRE'];?>
					  </label>
					</div>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <input type="hidden" name="action" value="add_transaction" />
			        <button type="submit" name="do_upload" class="btn btn-primary"><?=$l['DOTRANS2'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>

             <a class="list-group-item" href="#" data-toggle="modal" data-target="#creditModal" onclick="return false;"><?=$l['CREDITMODAL'];?></a>
             <div class="modal fade" id="creditModal" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['CREDITMODAL'];?></h4>
			      </div>
			      <div class="modal-body">
				    <div class="form-group">
				    	<label><?=$l['CREDIT'];?></label>
				    	<div class="input-group">
				    	<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=trim($cur->getPrefix());?></span><?php }?>
						<input type="text" class="form-control" placeholder="<?=$nfo->format(-50);?> <?=$l['OR'];?> <?=$nfo->format(50);?>" value="<?=$nfo->format($u->credit);?>" name="credit">
						<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=trim($cur->getSuffix());?></span><?php }?>
						</div>
					</div>

					<div class="form-group">
						<label><?=$l['SCOFC'];?></label>
						<div class="input-group">
				    	<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=trim($cur->getPrefix());?></span><?php }?>
						<input type="text" class="form-control" placeholder="<?=$nfo->format(-50);?> <?=$l['OR'];?> <?=$nfo->format(50);?>" value="<?=$nfo->format($u->special_credit);?>" name="special_credit">
						<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=trim($cur->getSuffix());?></span><?php }?>
						</div>
					</div>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <button type="submit" name="save_credit" class="btn btn-primary"><?=$l['CREDITMODAL2'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>

			<a class="list-group-item" href="#" data-toggle="modal" data-target="#paymentModal" onclick="return false;"><?=$l['PAYMENTMODAL'];?></a>
			<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['PAYMENTMODAL'];?></h4>
			      </div>
			      <div class="modal-body">
			      	<div class="form-group">
					    <select name="payment_method" class="form-control" required="required">
					    	<?php foreach ($gateways->get() as $key => $obj) {
            if ($obj->isActive()) {
                echo '<option value="' . $key . '">' . $obj->getLang("name") . '</option>';
            }
        }

            ?>
					    </select>
					</div>
                    <div class="form-group" style="position: relative;">
					    <input type="text" class="form-control datepicker" placeholder="<?=$dfo->format(time(), false, false, false);?>" value="<?=$dfo->format(time(), false, false, false);?>" name="date" required="required">
					</div>
			      	<div class="form-group">
					    <input type="text" class="form-control" placeholder="<?=$l['TID'];?>" name="transaction_id" required="required">
					</div>
				    <div class="form-group input-group">
				    	<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=trim($cur->getPrefix());?></span><?php }?>
						<input type="text" class="form-control" placeholder="<?=$l['EG'];?> <?=$nfo->format(50);?>" value="<?=$nfo->format(50);?>" name="amount" required="required">
						<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=trim($cur->getSuffix());?></span><?php }?>
					</div>
					<div class="checkbox">
				      <label>
				        <input type="checkbox" name="send_email" value="yes"> <?=$l['SENDMAILNOT'];?>
				      </label>
				    </div>
				    <div class="checkbox">
				      <label>
				        <input type="checkbox" name="invoice_fees" value="yes" onchange="if(this.checked) $('#fee_mail').show(); else $('#fee_mail').hide();"> <?=$l['INVOICEFEES'];?>
				      </label>
				    </div>
				    <div class="checkbox" id="fee_mail" style="display: none;">
				      <label>
				        <input type="checkbox" name="email_fees" value="yes"> <?=$l['EMAILFEES'];?>
				      </label>
				    </div>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <button type="submit" name="insert_payment" class="btn btn-primary"><?=$l['PAYMENTMODAL'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>

			<?php if ($uI->autoPaymentStatus()) {?>
			<a class="list-group-item" href="#" data-toggle="modal" data-target="#autoPayment" onclick="return false;"><?=$l['AUTOPAYMENT'];?></a>
             <div class="modal fade" id="autoPayment" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['AUTOPAYMENT'];?></h4>
			      </div>
			      <div class="modal-body">
				    <div class="form-group">
				    	<label><?=$l['AMOUNT'];?></label>
				    	<div class="input-group">
				    	<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=trim($cur->getPrefix());?></span><?php }?>
						<input type="text" class="form-control" placeholder="<?=$nfo->placeholder();?>" value="<?=$u->credit < 0 ? $nfo->format($u->credit / -1) : "";?>" name="autoPaymentAmount">
						<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=trim($cur->getSuffix());?></span><?php }?>
						</div>
					</div>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <button type="submit" class="btn btn-primary"><?=$l['AUTOPAYMENT'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>
			<?php }?>

			<?php if (SepaDirectDebit::active()) {?>
			<a class="list-group-item" href="?p=customers_sepa&id=<?=$u->ID;?>"><?=$l['MANAGESDDMANDATES'];?></a>
			<?php }?>

			<a class="list-group-item" href="#" data-toggle="modal" data-target="#paymentMethods" onclick="return false;"><?=$l['MANAGEPMS'];?></a>
			<div class="modal fade" id="paymentMethods" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['MANAGEPMS'];?></h4>
			      </div>
			      <div class="modal-body">
			      	<div class="row">
			      		<?php $disabled = explode(",", $u->disabled_payment);foreach ($gateways->get() as $n => $g) {if (!$g->isActive()) {
                continue;
            }
                ?><div class="col-md-4">
					      	<div class="checkbox">
						      <label>
						        <input type="checkbox" name="disabled_payment[]" value="<?=$n;?>"<?php if (!in_array($n, $disabled)) {
                    echo ' checked="checked"';
                }
                ?>> <?=$g->getLang('name');?>
						      </label>
						    </div>
						</div><?php }?>
					</div>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <button type="submit" name="save_payment_methods" class="btn btn-primary"><?=$lang['GENERAL']['SAVE'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>

			<a class="list-group-item" href="#" data-toggle="modal" data-target="#postpaid" onclick="return false;"><?=$l['POSTPAID'];?></a>
			<div class="modal fade" id="postpaid" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['POSTPAID'];?></h4>
			      </div>
			      <div class="modal-body">
			      	<label for="postpaid"><?=$l['PPLIMIT'];?></label>
			      	<div class="input-group">
			      		<?php if (!empty($cur->getPrefix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getPrefix($cur->getBaseCurrency());?></span><?php }?>
			      		<input type="text" name="postpaid" id="postpaid" value="<?=$nfo->format($u->postpaid);?>" class="form-control" />
			      		<?php if (!empty($cur->getSuffix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getSuffix($cur->getBaseCurrency());?></span><?php }?>
			      	</div>
			      	<p class="help-block" style="margin-bottom: 0;"><?=$l['0TODISABLE'];?></p>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <button type="submit" name="save_postpaid" class="btn btn-primary"><?=$lang['GENERAL']['SAVE'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>
             <?php }?>
             <?php if ($ari->check(10) && $tab == "files") {?>
             <div class="modal fade" id="fileModal" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content"><form method="POST" enctype="multipart/form-data" role="form">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['UPLFILES'];?></h4>
			      </div>
			      <div class="modal-body">
				    <div class="form-group">
				      <input type="file" class="form-control" name="upload_files[]" multiple>
				    </div>
				    <div class="checkbox">
					  <label>
					    <input type="checkbox" name="customer_access" value="yes"> <?=$l['FILECUSTAC'];?>
					  </label>
					</div>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
			        <button type="submit" name="do_upload" class="btn btn-primary"><?=$l['UPLFILES'];?></button>
			      </div></form>
			    </div>
			  </div>
			</div>
             <?php }?>
			 <?php if ($ari->check(48) && $tab == "cart") {?>
			 <a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=cart&orders=<?=$u->orders_active ? "0" : "1";?>"><?=$u->orders_active ? $l['DEACTORDERS'] : $l['ACTORDERS'];?></a>
			 <?php }?>
             <?php if ($ari->check(25) && $tab == "recurring") {?>
			 <a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=recurring&group_recurring=<?=$u->group_recurring ? '0' : '1';?>"><?=$u->group_recurring ? $l['DEACTPOSGROUP'] : $l['ACTPOSGROUP'];?></a>
             <?php }?>
             <?php if ($ari->check(13) && $tab == "domains") {?>
             <a class="list-group-item" href="?p=domain_pricing&customer=<?=$u->ID;?>"><?=$l['EDITPRICING'];?></a>
             <a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=domains&contacts=<?=$u->domain_contacts ? "0" : "1";?>"><?=$u->domain_contacts ? $l['DEACTDOMCON'] : $l['ACTDOMCON'];?></a>
             <a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=domains&api=<?=$u->domain_api ? "0" : "1";?>"><?=$u->domain_api ? $l['DEACTAPI'] : $l['ACTAPI'];?></a>
             <?php if ($CFG['CUSTOMER_AUTHCODE']) {?><a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=domains&authlock=<?=$u->auth_lock ? "0" : "1";?>"><?=!$u->auth_lock ? $l['DEACTAUTHLOCK'] : $l['ACTAUTHLOCK'];?></a><?php }?>
             <?php if (!$CFG['CUSTOMER_AUTHCODE']) {?><a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=domains&authlock=<?=$u->auth_lock == "-1" ? "0" : "-1";?>"><?=$u->auth_lock == "-1" ? $l['DEACTAUTHLOCK'] : $l['ACTAUTHLOCK'];?></a><?php }?>
             <a class="list-group-item" href="#" data-toggle="modal" data-target="#clientNameserver" onclick="return false;"><?=$l['OWNNS'];?></a>
			 <a class="list-group-item" href="#" data-toggle="modal" data-target="#udd" onclick="return false;"><?=$l['REGSET'];?></a>

			 <div class="modal fade" id="udd" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" role="form">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"
                                        aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title"><?=$l['REGSET'];?></h4>
                            </div>

							<div class="modal-body">
								<?php
$registrar_settings = @unserialize($userInstance->get()['registrar_settings']);
            if (!is_array($registrar_settings)) {
                $registrar_settings = [];
            }

            foreach (DomainHandler::getRegistrars() as $r) {
                if ($r->isActive() && method_exists($r, "getUserDefined") && is_array($arr = $r->getUserDefined()) && count($arr)) {
                    foreach ($arr as $k => $i) {
                        $val = "";
                        if (array_key_exists($r->getShort(), $registrar_settings) && array_key_exists($k, $registrar_settings[$r->getShort()])) {
                            $val = $registrar_settings[$r->getShort()][$k];
                        }
                        ?>
								<div class="form-group">
									<label><?=$r->getName();?>: <?=$i['name'];?></label>
									<input type="<?=$i['type'];?>" name="registrar_settings[<?=$r->getShort();?>][<?=$k;?>]" placeholder="<?=!empty($i['placeholder']) ? $i['placeholder'] : "";?>" value="<?=htmlentities($val);?>" class="form-control">
								</div>
								<?php }}}?>
							</div>

							<div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
                                <button type="submit" class="btn btn-primary"><?=$lang['GENERAL']['SAVE'];?></button>
                            </div>
						</form>
					</div>
				</div>
			</div>

             <div class="modal fade" id="clientNameserver" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" role="form">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"
                                        aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">
                                    <?=$l['OWNNS'];?></h4>
                            </div>

                            <?php
$ns = $userInstance->getNS();
            ?>

                            <div class="modal-body">
                                <div class="form-group">
                                	<label><?=str_replace("%i", "1", $l['NSX']);?></label>
                                	<input type="text" name="ns1" value="<?=$ns[0];?>" class="form-control" placeholder="<?=$CFG['NS1'];?>" />
                                </div>

                                <div class="form-group">
                                	<label><?=str_replace("%i", "2", $l['NSX']);?></label>
                                	<input type="text" name="ns2" value="<?=$ns[1];?>" class="form-control" placeholder="<?=$CFG['NS2'];?>" />
                                </div>

                                <div class="form-group">
                                	<label><?=str_replace("%i", "3", $l['NSX']);?></label>
                                	<input type="text" name="ns3" value="<?=$ns[2];?>" class="form-control" placeholder="<?=$CFG['NS3'];?>" />
                                </div>

                                <div class="form-group">
                                	<label><?=str_replace("%i", "4", $l['NSX']);?></label>
                                	<input type="text" name="ns4" value="<?=$ns[3];?>" class="form-control" placeholder="<?=$CFG['NS4'];?>" />
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                	<label><?=str_replace("%i", "5", $l['NSX']);?></label>
                                	<input type="text" name="ns5" value="<?=$ns[4];?>" class="form-control" placeholder="<?=$CFG['NS5'];?>" />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
                                <button type="submit" class="btn btn-primary"><?=$lang['GENERAL']['SAVE'];?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
             <?php }?>
             <?php if ($ari->check(25) && $tab == "laterinvoice") {?>
             <a class="list-group-item" href="?p=new_laterinvoice&user=<?=$u->ID;?>"><?=$l['NEWLATERINV'];?></a>
             <?php if ($later_sum != 0) {?><a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=laterinvoice&do=1"><?=$l['LATERINVNOW'];?></a><?php }?>
             <?php }?>
             <?php if ($ari->check(25) && $tab == "invoices") {?>
						 <a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=laterinvoice"><?=$l['LATERINVPOS'];?> (<?=$cur->infix($nfo->format($later_sum), $cur->getBaseCurrency());?>)</a>
             <a class="list-group-item" href="?p=yearly_invoice&cid=<?=$u->ID;?>"><?=$l['YEARLYINV'];?></a>
             <a class="list-group-item" href="?p=customers&edit=<?=$u->ID;?>&tab=invoices&no_reminders=<?=$u->no_reminders ? "0" : "1";?>"><?=!$u->no_reminders ? $l['DEACTREM'] : $l['ACTREM'];?></a>
             <?php }?>
                <?php if ($tab == "affiliate" && $CFG['AFFILIATE_ACTIVE'] == "1") {?>
                    <a class="list-group-item" href="#" data-toggle="modal" data-target="#affiliateChangeModal"
                       onclick="return false;"><?=$u->affiliate == 0 ? $l['ADDAFF'] : $l['CHAAFF'];?></a>

                    <div class="modal fade" id="affiliateChangeModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" role="form">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title">
																				<?=$u->affiliate == 0 ? $l['ADDAFF'] : $l['CHAAFF'];?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label><?=$l['CUSTOMER'];?></label>

                                            <select class="form-control" name="affiliate_customer">
                                                <option selected="selected" value="0"><?=$l['NOTASSIGNED'];?></option>
                                                <?php
$sql = $db->query("SELECT ID, firstname, lastname, company FROM clients ORDER BY firstname ASC, lastname ASC");
            while ($row = $sql->fetch_object()) {
                echo "<option value=\"{$row->ID}\"" . ((isset($_POST['affiliate_customer']) && $_POST['affiliate_customer'] == $row->ID) || (!isset($_POST['affiliate_customer']) && $row->ID == $u->affiliate) ? " selected=\"selected\"" : "") . ">" . htmlentities($row->firstname) . " " . htmlentities($row->lastname) . (!empty($row->company) ? " (" . htmlentities($row->company) . ")" : "") . "</option>";
            }

            ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label><?=$l['SOURCE'];?></label>

                                            <input type="text" name="affiliate_source" placeholder="<?=$l['OPTIONAL'];?>"
                                                   class="form-control"
                                                   value="<?=isset($_POST['affiliate_source']) ? $_POST['affiliate_source'] : $u->affiliate_source;?>"/>

                                            <p class="help-block" style="text-align: justify;"><?=$l['AFFSOURCEH'];?></p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
                                        <button type="submit" name="do_affiliate_change"
                                                class="btn btn-primary"><?=$u->affiliate == 0 ? $l['ADDAFF'] : $l['CHAAFF'];?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="affiliateAddModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" role="form">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title"><?=$l['ASSIGNCUST'];?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label><?=$l['CUSTOMER'];?></label>

                                            <select class="form-control" name="affiliate_customer">
                                                <option selected="selected" disabled="disabled"><?=$l['PCC'];?></option>
                                                <?php
$sql = $db->query("SELECT ID, firstname, lastname, company FROM clients ORDER BY firstname ASC, lastname ASC");
            while ($row = $sql->fetch_object()) {
                echo "<option value=\"{$row->ID}\"" . (isset($_POST['affiliate_customer']) && $_POST['affiliate_customer'] == $row->ID ? " selected=\"selected\"" : "") . ">" . htmlentities($row->firstname) . " " . htmlentities($row->lastname) . (!empty($row->company) ? " (" . htmlentities($row->company) . ")" : "") . "</option>";
            }

            ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label><?=$l['SOURCE'];?></label>

                                            <input type="text" name="affiliate_source" placeholder="<?=$l['OPTIONAL'];?>"
                                                   class="form-control"
                                                   value="<?=isset($_POST['affiliate_source']) ? $_POST['affiliate_source'] : "";?>"/>

                                            <p class="help-block" style="text-align: justify;"><?=$l['AFFSOURCEH'];?></p>
                                        </div>

                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="affiliate_force" value="1"> <?=$l['AFFASSFORCE'];?>
                                                <p class="help-block" style="text-align: justify;"><?=$l['AFFASSFORCEH'];?></p>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
                                        <button type="submit" name="do_affiliate_add" class="btn btn-primary"><?=$l['ASSIGN'];?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($db->query("SELECT 1 FROM client_affiliate WHERE affiliate = {$u->ID} LIMIT 1")->num_rows > 0) {?>
                    <a class="list-group-item" href="#" data-toggle="modal" data-target="#affiliateTransactionsIn" onclick="return false;"><?=$l['AFFINCOMING'];?> (<?=$db->query("SELECT 1 FROM client_affiliate WHERE affiliate = {$u->ID}")->num_rows;?>)</a>

                    <div class="modal fade" id="affiliateTransactionsIn" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" role="form">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title"><?=$l['AFFINCOMING'];?></h4>
                                    </div>
                                    <div class="modal-body">
                                    	<div class="table-responsive">
                                    		<table class="table table-bordered table-striped" style="margin-bottom: 0;">
                                    			<tr>
                                    				<th width="30px"></th>
                                    				<th><?=$l['TIMEPOINT'];?></th>
                                    				<th><?=$l['WASREF'];?></th>
                                    				<th><?=$l['PROVISION'];?></th>
                                    			</tr>

                                    			<?php
$sql = $db->query("SELECT * FROM client_affiliate WHERE affiliate = {$u->ID} ORDER BY `time` DESC, `ID` DESC");
                while ($row = $sql->fetch_object()) {
                    ?>
                                    			<tr<?php if ($row->cancelled) {
                        echo ' style="text-decoration: line-through;"';
                    }
                    ?>>
                                    				<td><input type="checkbox" name="t[]" value="<?=$row->ID;?>" /></td>
                                    				<td><?=$dfo->format($row->time, true, true);?></td>
                                    				<td><a href="?p=customers&edit=<?=$row->user;?>"><?=User::getInstance($row->user, "ID")->getfName();?></a></td>
                                    				<td><font color="<?=$row->amount >= 0 ? "green" : "red";?>"><?=$row->amount >= 0 ? "+" : "-";?> <?=$cur->infix($nfo->format(abs($row->amount)), $cur->getBaseCurrency());?></font></td>
                                    			</tr>
                                    			<?php }if ($sql->num_rows == 0) {?>
                                    			<tr><td colspan="4"><center><?=$l['AFFINNT'];?></center></td></tr>
                                    			<?php }?>
                                    		</table>
                                    	</div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
                                        <button type="submit" name="revert_affiliate_transactions" class="btn btn-danger"><?=$l['CANCELSELECTED'];?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><?php }?>

                    <?php if ($db->query("SELECT 1 FROM client_affiliate WHERE user = {$u->ID} LIMIT 1")->num_rows > 0) {?>
                    <a class="list-group-item" href="#" data-toggle="modal" data-target="#affiliateTransactions" onclick="return false;"><?=$l['AFFOUTGOING'];?> (<?=$db->query("SELECT 1 FROM client_affiliate WHERE user = {$u->ID}")->num_rows;?>)</a>

                    <div class="modal fade" id="affiliateTransactions" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" role="form">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title"><?=$l['AFFOUTGOING'];?></h4>
                                    </div>
                                    <div class="modal-body">
                                    	<div class="table-responsive">
                                    		<table class="table table-bordered table-striped" style="margin-bottom: 0;">
                                    			<tr>
                                    				<th width="30px"></th>
                                    				<th><?=$l['TIMEPOINT'];?></th>
                                    				<th><?=$l['HASREF'];?></th>
                                    				<th><?=$l['PROVISION'];?></th>
                                    			</tr>

                                    			<?php
$sql = $db->query("SELECT * FROM client_affiliate WHERE user = {$u->ID} ORDER BY `time` DESC, `ID` DESC");
                while ($row = $sql->fetch_object()) {
                    ?>
                                    			<tr<?php if ($row->cancelled) {
                        echo ' style="text-decoration: line-through;"';
                    }
                    ?>>
                                    				<td><input type="checkbox" name="t[]" value="<?=$row->ID;?>" /></td>
                                    				<td><?=$dfo->format($row->time, true, true);?></td>
                                    				<td><a href="?p=customers&edit=<?=$row->affiliate;?>"><?=User::getInstance($row->affiliate, "ID")->getfName();?></a></td>
                                    				<td><font color="<?=$row->amount >= 0 ? "green" : "red";?>"><?=$row->amount >= 0 ? "+" : "-";?> <?=$cur->infix($nfo->format(abs($row->amount)), $cur->getBaseCurrency());?></font></td>
                                    			</tr>
                                    			<?php }if ($sql->num_rows == 0) {?>
                                    			<tr><td colspan="4"><center><?=$l['AFFINNT'];?></center></td></tr>
                                    			<?php }?>
                                    		</table>
                                    	</div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
                                        <button type="submit" name="revert_affiliate_transactions" class="btn btn-danger"><?=$l['CANCELSELECTED'];?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><?php }?>

                    <?php if ($u->affiliate > 0) {?><a class="list-group-item" href="#" data-toggle="modal"
                                                        data-target="#affiliateAddTransaction" onclick="return false;"><?=$l['AFFADDT'];?></a>

                    <div class="modal fade" id="affiliateAddTransaction" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" role="form">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title"><?=$l['AFFADDT'];?></h4>
                                    </div>
                                    <div class="modal-body">
                                    	<p style="text-align: justify;"><?=$l['AFFADDTI'];?></p>

                                        <div class="form-group" style="position: relative;">
                                            <label><?=$l['TIMEPOINT'];?></label>

                                            <input type="text" name="time" class="form-control datetimepicker" value="<?=$dfo->placeholder(1, 1, "");?>" placeholder="<?=$dfo->placeholder(1, 1, "");?>">
                                        </div>

                                        <div class="form-group">
                                            <label><?=$l['AMOUNT'];?></label>

                                            <input type="text" name="amount" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
                                        <button type="submit" name="do_affiliate_transaction" class="btn btn-primary"><?=$l['AFFADDDO'];?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><?php }?>
                <?php }?>
            </div>
        </div>

        <div class="col-md-9">
		<?php if (isset($suc)) {?>
<div class="alert alert-success"><b><?=$l['SUCCESS'];?></b> <?=$suc;?></div>
<?php unset($suc);}?>

            <?php if (isset($err)) {?>
                <div class="alert alert-danger"><b><?=$lang['GENERAL']['ERROR'];?></b> <?=$err;?></div>
            <?php }?>

<?php if ($tab == "profile" || $tab == "history" || $tab == "contacts") {?>
<div>

<div class="row">

<div class="col-md-12">

<ul class="nav nav-tabs nav-justified">
  <li<?=$tab == "profile" ? ' class="active"' : "";?>><a href="?p=customers&edit=<?=$u->ID;?>"><?=$l['CURRENT'];?></a></li>
  <li<?=$tab == "history" ? ' class="active"' : "";?>><a href="?p=customers&edit=<?=$u->ID;?>&tab=history"><?=$l['HISTORY'];?></a></li>
  <li<?=$tab == "contacts" ? ' class="active"' : "";?>><a href="?p=customers&edit=<?=$u->ID;?>&tab=contacts"><?=$l['CONTACTS'];?> (<?=count(is_object($uI = User::getInstance($u->ID, "ID")) ? $uI->getContacts() : array());?>)</a></li>
</ul><br />

    <?php if ($tab == "profile") {?>

    <?php if ($openEmailChangeRequests == 1 && $tab == "profile") {?>
<div class="alert alert-warning"><?=$l['ONEMAILCHANGEREQ'];?>
<ul>
<?php while ($r = $openEmailChangeRequestsSql->fetch_object()) {?>
<li><?=htmlentities($r->new);?> <?php if ($ari->check(10)) {?>[ <a href="?p=customers&amp;edit=<?=$_GET['edit'];?>&amp;deletereq=<?=$r->ID;?>"><?=$l['CANCEL'];?></a> ]<?php }?></li>
<?php }?>
</ul>
</div>
<?php } elseif ($openEmailChangeRequests > 1 && $tab == "profile") {?>
<div class="alert alert-warning"><?=str_replace("%i", $openEmailChangeRequests, $l['XMAILCHANGEREQ']);?>
<ul>
<?php while ($r = $openEmailChangeRequestsSql->fetch_object()) {?>
<li><?=htmlentities($r->new);?> <?php if ($ari->check(10)) {?>[ <a href="?p=customers&amp;edit=<?=$_GET['edit'];?>&amp;deletereq=<?=$r->ID;?>"><?=$l['CANCEL'];?></a> ]<?php }?></li>
<?php }?>
</ul>
</div>
<?php }?>

  <div>
  <form accept-charset="UTF-8" role="form" id="login-form" method="post">
      <fieldset>
        <div class="row">
            <div class="col-sm-2"><div class="form-group">
                <select class="form-control" name="salutation">
                    <option value="MALE"<?=$u->salutation == "MALE" ? ' selected=""' : '';?>><?=$l['MALE'];?></option>
                    <option value="FEMALE"<?=$u->salutation == "FEMALE" ? ' selected=""' : '';?>><?=$l['FEMALE'];?></option>
                    <option value="DIVERS"<?=$u->salutation == "DIVERS" ? ' selected=""' : '';?>><?=$l['DIVERS'];?></option>
                    <option value=""<?=$u->salutation == "" ? ' selected=""' : '';?>><?=$l['NA'];?></option>
                </select>
	        </div></div>

	        <div class="col-sm-5"><div class="form-group">
                <input class="form-control" placeholder="<?=$l['FIRSTNAME'];?>" name="firstname" type="text" value="<?=htmlentities($u->firstname);?>">
	        </div></div>

			<div class="col-sm-5"><div class="form-group">
                <input class="form-control" placeholder="<?=$l['LASTNAME'];?>" name="lastname" type="text" value="<?=htmlentities($u->lastname);?>">
	        </div></div>
        </div>

        <div class="form-group input-group">
          <span class="input-group-addon">
            <i class="fa fa-address-card-o">
            </i>
          </span>
          <input class="form-control" placeholder="<?=$l['NICKNAME'];?>" name="nickname" type="text" value="<?=htmlentities($u->nickname);?>">
        </div>

		<div class="form-group input-group">
          <span class="input-group-addon">
            <i class="fa fa-suitcase">
            </i>
          </span>
          <input class="form-control" placeholder="<?=$l['COMPANYOPT'];?>" name="company" type="text" value="<?=htmlentities($u->company);?>">
        </div>

		<div class="form-group input-group">
          <span class="input-group-addon">
            <a href="mailto:<?=$u->mail;?>"><i class="glyphicon glyphicon-inbox" style="width: 14px;"></i></a>
          </span>
          <input class="form-control" placeholder="<?=$l['MAILADDRESS'];?>" name="mail" type="text" value="<?=htmlentities($u->mail);?>">
          <span class="input-group-addon">
			<a href="#" data-toggle="modal" data-target="#mt_choose"><i class="fa fa-wrench fa-fw"></i></a>
		  </span>
		</div>

		<div class="modal fade" id="mt_choose" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$l['MAILTEMPS'];?></h4>
              </div>
              <div class="modal-body">
                <div class="row">
					<?php
$ignored = explode(",", $u->exclude_mail_templates);

            $ignore = [
                "Bereits registriert",
                "E-Mailadresse geändert",
                "Benutzerdaten geändert",
                "E-Mailänderung (neue Adresse)",
                "E-Mailänderung (alte Adresse)",
                "E-Mailänderung storniert",
                "Gast-Bestellung",
                "Neues Passwort",
                "Neuregistrierung",
                "Passwort angefordert",
                "Passwort vergessen?",
                "Passwort zurückgesetzt",
                "Registrierung per sozialem Login",
                "Zwei-Faktor aktiviert",
                "Zwei-Faktor deaktiviert",
            ];

            $sql = $db->query("SELECT category FROM email_templates WHERE admin_notification = 0 AND category != 'System' AND category != 'Administrator' AND category != 'Reseller' GROUP BY category ORDER BY category = 'Eigene' ASC, category ASC");
            while ($row = $sql->fetch_object()) {?>
					<div class="col-md-6">
						<div class="checkbox">
							<label>
								<input type="checkbox" class="mt_checkall" data-category="<?=htmlentities($row->category);?>" />
								<b><?=htmlentities($row->category);?></b>
							</label>
						</div>

						<?php
$sql2 = $db->query("SELECT ID, name FROM email_templates WHERE category = '" . $db->real_escape_string($row->category) . "' ORDER BY name ASC");
                $all = true;
                while ($e = $sql2->fetch_object()) {if (in_array($e->name, $ignore)) {
                    continue;
                }
                    if (in_array($e->ID, $ignored)) {
                        $all = false;
                    }
                    ?>
						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" class="mt_checkbox" data-category="<?=htmlentities($row->category);?>" data-id="<?=$e->ID;?>"<?=!in_array($e->ID, $ignored) ? ' checked=""' : '';?> />
								<?=htmlentities($e->name);?>
							</label>
						</div>
						<?php }if ($all) {?><script>$(".mt_checkall[data-category=<?=htmlentities($row->category);?>]").prop("checked", true);</script><?php }?>
					</div>
					<?php }?>
				</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="mt_submit"><?=$lang['GENERAL']['SAVE'];?></button>
              </div>
            </div>
          </div>
		</div>

		<script>
		var mt_doing = 0;

		$("#mt_submit").click(function(e) {
			e.preventDefault();

			if (mt_doing) {
				return false;
			}
			mt_doing = 1;

			var btn = $(this);
			btn.prop("disabled", true);
			var label = btn.html();
			btn.html("<i class='fa fa-spinner fa-pulse'></i> <?=$l['PW'];?>");

			var exclude_templates = [0];

			$(".mt_checkbox").each(function() {
				if (!$(this).is(":checked")) {
					exclude_templates.push($(this).data("id"));
				}
			});

			$.post("", {
				"exclude_mail_templates": exclude_templates,
				"csrf_token": "<?=CSRF::raw();?>"
			}, function(r) {
				btn.prop("disabled", false).html(label);
				$("#mt_choose").modal("hide");
				mt_doing = 0;
			});
		});

		$(".mt_checkall").click(function(e) {
			$(".mt_checkbox[data-category=" + $(this).data("category") + "]").prop("checked", e.target.checked);
		});

		$(".mt_checkbox").click(function(e) {
			var cat = $(this).data("category");
			var chk = true;

			$(".mt_checkbox[data-category=" + cat + "]").each(function() {
				if (!$(this).is(":checked")) {
					chk = false;
				}
			});

			$(".mt_checkall[data-category=" + cat + "]").prop("checked", chk);
		});
		</script>

        <?php if (in_array("Telefonnummer", $activeFields)) {?><div class="form-group input-group">
          <span class="input-group-addon">
            <?php if ($adminInfo->can_call) {?><a href="#" id="call"><?php }?><i class="fa fa-phone" style="width:14px;"></i><?php if ($adminInfo->can_call) {?></a><?php }?>
          </span>
          <input class="form-control" placeholder="<?=$l['TELEPHONE'];?>" name="telephone" type="text" value="<?=$u->telephone != '' ? htmlentities($u->telephone) : "";?>">
          <?php if (SMSHandler::getDriver()) {?>
          <span class="input-group-addon">
            <a href="#" onclick="smsModal(); return false;"><i class="fa fa-envelope-o"></i></a>
          </span>
          <?php }?>
          <span class="input-group-addon">
            <a href="#" id="tvToggle"><i class="fa fa-fw fa<?php if ($u->telephone_verified) {?>-check<?php }?>-square-o" id="tvCheck"<?php if ($u->telephone_verified) {?> style="color: green;"<?php }?>></i></a>
            <input type="hidden" name="telephone_verified" value="<?=$u->telephone_verified;?>" />
          </span>
        </div>

        <div class="modal fade" id="sms" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$l['SENDSMS'];?></h4>
              </div>
              <div class="modal-body">
                <div id="sms_status" style="display: none;"></div>

                <select id="sms_type" class="form-control">
                  <?php if (SMSHandler::getDriver()) {
                foreach (SMSHandler::getDriver()->getTypes() as $id => $name) {
                    echo '<option value="' . $id . '">' . strip_tags($name) . '</option>';
                }
            }

                ?>
                </select>
                <br />
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                  <input type="text" id="sms_number" value="" placeholder="0049157712345678" class="form-control">
                </div>
                <br />
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                  <input type="text" id="sms_message" value="" placeholder="<?=$l['YOURMSG'];?>" class="form-control">
                  <span class="input-group-addon" id="sms_count">0</span>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="sms_submit"><?=$l['SENDSMS'];?></button>
              </div>
            </div>
          </div>
        </div>

        <script>
        function smsModal(){
          $("#sms_number").val($("[name=telephone]").val());
          $("#sms").modal("show");
        }

        var doingsend = 0;
        $("#sms_submit").click(function(e){
          if(!doingsend){
            doingsend = 1;
            $("#sms_status").slideUp(function(){
              $("#sms_status").removeClass().addClass("alert alert-info").html("<i class='fa fa-spinner fa-spin'></i> <?=$l['WASYSMS'];?>").slideDown();

              $.post("?p=ajax&action=send_sms", {
                "t": $("#sms_type").val(),
                "n": $("#sms_number").val(),
                "m": $("#sms_message").val(),
				"csrf_token": "<?=CSRF::raw();?>",
              }, function(r){
                if(r == "ok")
                  $("#sms_status").removeClass().addClass("alert alert-success").html("<?=$l['SMSSENT'];?>");
                else
                  $("#sms_status").removeClass().addClass("alert alert-danger").html(r);
                doingsend = 0;
              });
            });
          }
        });

        $("#sms_message").keyup(function(){
          $("#sms_count").html($("#sms_message").val().length);
        });

        $("#tvToggle").click(function(e){
          e.preventDefault();
          if($("[name=telephone_verified]").val() == "1"){
            $("[name=telephone_verified]").val("0");
            $("#tvCheck").removeClass("fa-check-square-o").addClass("fa-square-o").css("color", "");
          } else {
            $("[name=telephone_verified]").val("1");
            $("#tvCheck").addClass("fa-check-square-o").removeClass("fa-square-o").css("color", "green");
          }
        });

        var cdoing = 0;

        $("#call").click(function(e){
        	e.preventDefault();

        	if(cdoing) return;
        	cdoing = 1;

        	var b = $("#call").find(".fa");
        	b.removeClass("fa-phone fa-times fa-check").addClass("fa-spin fa-spinner").css("color", "");

        	$.get("?p=call&number=" + encodeURIComponent($("[name=telephone]").val()), function(r){
        		b.removeClass("fa-spin fa-spinner");
        		if(r == "ok") b.addClass("fa-check").css("color", "green");
        		else b.addClass("fa-times").css("color", "red");

        		cdoing = 0;
        	});
        });
        </script><?php }?>

        <?php if (in_array("Faxnummer", $activeFields)) {?><div class="form-group input-group">
          <span class="input-group-addon">
            <i class="fa fa-fax"></i>
          </span>
          <input class="form-control" placeholder="<?=$l['FAXNUM'];?>" name="fax" type="text" value="<?=$u->fax != '' ? htmlentities($u->fax) : "";?>">
        </div>
        <?php }?>

        <?php if (in_array("Geburtstag", $activeFields)) {?>
        <div class="form-group input-group">
          <span class="input-group-addon">
            <i class="fa fa-birthday-cake" style="width:14px;">
            </i>
          </span>
          <input class="form-control datepicker" placeholder="<?=$l['BIRTHDATE'];?>" name="birthday" type="text" value="<?=$u->birthday != '0000-00-00' ? $dfo->format(strtotime($u->birthday), false) : "";?>">
          <?php if ($u->birthday != '0000-00-00' && strtotime($u->birthday) !== false) {?>
          <span class="input-group-addon">
            <?php $birthDate = explode("/", date("m/d/Y", strtotime($u->birthday)));
                echo (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md") ? ((date("Y") - $birthDate[2]) - 1) : (date("Y") - $birthDate[2]));?>
          </span>
          <?php }?>
        </div><?php }?>

        <input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

       <?php if ($ari->check(9)) {?> <div class="form-group input-group">
          <span class="input-group-addon">
            <i class="glyphicon glyphicon-lock">
            </i>
          </span>
          <input class="form-control" placeholder="<?=$l['NEWPWOPT'];?>" name="pwd" type="text" value="<?=$CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "" ? htmlentities($u->pwd) : "";?>" autocomplete="false">
        </div><?php }?>
        <div class="form-group input-group">
          <span class="input-group-addon">
            <i class="glyphicon glyphicon-phone">
            </i>
          </span>
          <input class="form-control" placeholder="<?=$l['TFATOKEN'];?>" name="tfa" type="password" autocomplete="false" value="<?=$u->tfa != 'none' ? htmlentities($u->tfa) : "";?>" id="tfa_field">
          <span class="input-group-addon">
          	<a href="#" id="tfa_link"><i class="fa fa-eye fa-fw" id="tfa_btn"></i></a>
          </span>
        </div>

        <script>
        $("#tfa_link").click(function(e){
        	e.preventDefault();
        	var btn = $("#tfa_btn");
        	if(btn.hasClass("fa-eye")){
        		btn.removeClass("fa-eye").addClass("fa-eye-slash");
        		$("#tfa_field").attr("type", "text");
        	} else {
        		btn.addClass("fa-eye").removeClass("fa-eye-slash");
        		$("#tfa_field").attr("type", "password");
        	}
        });
        </script>

        <?php if (in_array("Straße", $activeFields) || in_array("Hausnummer", $activeFields)) {?><div class="row">
	        <div class="col-sm-<?=in_array("Hausnummer", $activeFields) ? "10" : "12";?>">
				<div class="form-group">
		          <input class="form-control" placeholder="<?=$l['STREET'];?>" name="street" type="text" value="<?=htmlentities($u->street);?>">
		        </div>
	        </div>

			<div class="col-sm-<?=in_array("Straße", $activeFields) ? "2" : "12";?>">
	        	<div class="form-group">
		          <input class="form-control" placeholder="<?=$l['STREETNR'];?>" maxlength="25" name="street_number" type="text" value="<?=htmlentities($u->street_number);?>">
		        </div>
	        </div>
        </div><?php }?>

        <?php if (in_array("Postleitzahl", $activeFields) || in_array("Ort", $activeFields)) {?>
        <div class="row">
	        <div class="col-sm-<?=in_array("Ort", $activeFields) ? "2" : "12";?>">
				<div class="form-group">
		          <input class="form-control" placeholder="<?=$l['POSTCODE'];?>" name="postcode" maxlength="10" type="text" value="<?=htmlentities($u->postcode);?>">
		        </div>
	        </div>

			<div class="col-sm-<?=in_array("Postleitzahl", $activeFields) ? "10" : "12";?>">
	        	<div class="form-group">
		          <input class="form-control" placeholder="<?=$l['CITY'];?>" name="city" type="text" value="<?=htmlentities($u->city);?>">
		        </div>
	        </div>
        </div><?php }?>

        <?php if (empty($u->coordinates) && !empty($u->street) && !empty($u->street_number) && !empty($u->postcode) && !empty($u->city) && $u->country != 0 && in_array("Straße", $activeFields) && in_array("Hausnummer", $activeFields) && in_array("Postleitzahl", $activeFields) && in_array("Ort", $activeFields) && in_array("Land", $activeFields)) {
                ?>
                <div class='alert alert-info' id='ukca-alert' style='margin-bottom: 15px;'><i class="fa fa-spinner fa-spin"></i> <?=$lang['GENERAL']['PLEASEWAIT'];?>...</div>

                <script>
                $(document).ready(function() {
                    $.post("", {
                        "coordinates": "reset",
                        "csrf_token": "<?=CSRF::raw();?>"
                    }, function(r) {
                        if (r == "ok") {
                            $("#ukca-alert").slideUp();
                        } else {
                            $("#ukca-alert").html("<?=$l['UKCA'];?>");
                        }
                    });
                });
                </script>
                <?php
}
            ?>

        <div class="row">

        <?php
$count = 2;
            if (in_array("Land", $activeFields)) {
                $count++;
            }

            $md = 12 / $count;
            ?>

        <?php if (in_array("Land", $activeFields)) {?>
        <div class="col-md-<?=$md;?>">
		<div class="form-group">
          <select class="form-control" name="country">
			<option value="0"><?=$l['CHOOSECOUNTRY'];?></option>
			<?php $sql = $db->query("SELECT ID, name FROM client_countries WHERE active = 1 ORDER BY name ASC");
                while ($r = $sql->fetch_object()) {?>
			<option value="<?=$r->ID;?>" <?php if ($r->ID == $u->country) {
                    echo "selected";
                }
                    ?>><?=$r->name;?></option>
			<?php }?>
		  </select>
        </div>
        </div>
        <?php }?>

		<div class="col-md-<?=$md;?>">
        <div class="form-group">
          <select class="form-control" name="language">
			<option value="0"><?=$l['CHOOSELANG'];?></option>
			<?php foreach ($languages as $k => $v) {?>
			<option value="<?=$k;?>" <?php if ($k == $u->language || (!isset($languages[$u->language]) && $k == $CFG['LANG'])) {
                echo "selected";
            }
                ?>><?=$v;?></option>
			<?php }?>
		  </select>
        </div>
        </div>

        <div class="col-md-<?=$md;?>">
        <div class="form-group">
          <select class="form-control" name="currency">
			<option value="0"><?=$l['CHOOSECURRENCY'];?></option>
			<?php $sql = $db->query("SELECT * FROM currencies ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
			<option value="<?=$row->currency_code;?>" <?php if ($row->currency_code == $u->currency || (empty($u->currency) && $cur->getBaseCurrency() == $row->currency_code)) {
                echo "selected";
            }
                ?>><?=$row->name;?></option>
			<?php }?>
		  </select>
        </div>
        </div>

        </div>
        <?php if (in_array("Webseite", $activeFields)) {?>
        <script type="text/javascript">
        function urlChanged(value) {
        	if(value.trim() != ""){
        		value = value.trim();
        		if(value.substr(0, 4) != "http")
        			value = "http://" + value;
        		document.getElementById("link_a").href = value;
        	} else {
        		document.getElementById("link_a").href = "";
        	}
        }

        function urlOk() {
        	var url = document.getElementById("link_a").getAttribute("href");
        	if(url.trim() == "")
        		return false;
        	return true;
        }
        </script>

        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-cloud"></i></span>
                <input type="text" name="website" placeholder="<?=$l['WEBSITE'];?>" onkeyup="javascript:urlChanged(this.value);" class="form-control" value="<?=htmlentities($u->website);?>" />
                <span class="input-group-addon"><a id="link_a" onclick="return urlOk();" href="<?php if (!empty($u->website)) {?><?=substr($u->website, 0, 4) != "http" ? "http://" : "";?><?=htmlentities($u->website);?><?php }?>" target="_blank"><i class="fa fa-fw fa-external-link"></i></a></span>
            </div>
        </div><?php }?>

        <div class="form-group row" style="margin-bottom: 0 !important;">
        	<?php if ($CFG['EU_VAT'] && $CFG['TAXES'] && in_array("USt-IdNr.", $activeFields)) {?>
        	<div class="col-sm-4">
	            <div class="input-group">
	                <span class="input-group-addon"><?=$l['EUVATID'];?></span>
	                <input type="text" name="vatid" placeholder="DE111111111" class="form-control" value="<?=$u->vatid;?>" />
	            </div>
	        </div>
        	<?php }?>

            <div class="col-sm-<?=$CFG['EU_VAT'] && $CFG['TAXES'] && in_array("USt-IdNr.", $activeFields) ? "4" : "6";?>">
	            <div class="input-group">
	                <span class="input-group-addon"><?=$l['PRICELEVEL'];?></span>
	                <input type="text" name="pricelevel" placeholder="100" class="form-control" value="<?=$nfo->format($u->pricelevel);?>" />
	                <span class="input-group-addon">%</span>
	            </div>
	        </div>

	        <div class="col-sm-<?=$CFG['EU_VAT'] && $CFG['TAXES'] && in_array("USt-IdNr.", $activeFields) ? "4" : "6";?>">
	            <div class="input-group">
	                <span class="input-group-addon"><?=$l['CGROUP'];?></span>
	                <select name="cgroup" class="form-control">
	                	<option value="0"><?=$l['NOCGROUP'];?></option>
	                	<?php
$sql = $db->query("SELECT * FROM client_groups ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
	                	<option value="<?=$row->ID;?>"<?php if ($row->ID == $u->cgroup) {
                    echo ' selected="selected"';
                }
                ?>><?=$row->name;?></option>
	                	<?php }?>
	                </select>
	            </div>
	        </div>
        </div>

        <?php
$sql = $db->query("SELECT * FROM client_fields WHERE `system` = 0 AND active = 1 ORDER BY position ASC, name ASC, ID ASC");
            while ($row = $sql->fetch_object()) {
                ?>
        	<div class="form-group" style="margin-top: 15px;">
	        	<div class="input-group">
	        		<div class="input-group-addon"><?=$row->name;?></div>
	        		<input name="fields[<?=$row->ID;?>]" value="<?=$uI->getField($row->ID);?>" class="form-control" />
	        	</div>
	        </div>
        	<?php
}

            $hasDifferentInv = !empty($u->inv_street) || !empty($u->inv_street_number) || !empty($u->inv_postcode) || !empty($u->inv_city) || !empty($u->inv_tthof);
            $hasDifferentInvParams = $u->inv_due != -1;
            ?>

<div class="panel panel-default" style="margin-top: 15px; margin-bottom: 0px;">
    <div class="panel-heading">
        <?=$lang['CUSTOMERS']['DIFFINV'];?>
        <a href="#" class="pull-right" id="diffinvbtn"><i class="fa fa-<?=!$hasDifferentInv ? 'plus' : 'times';?>"></i></a>
    </div>
    <div class="panel-body" id="diffinvpanel"<?=!$hasDifferentInv ? ' style="display: none;"' : '';?>>
        <div class="row">
            <div class="col-sm-10">
                <div class="form-group">
                    <input type="text" placeholder="<?=$lang['CUSTOMERS']['STREET'];?>" name="inv_street" class="form-control" value="<?=htmlentities($u->inv_street);?>">
                </div>
            </div>

            <div class="col-sm-2">
                <div class="form-group">
                <input type="text" placeholder="<?=$lang['CUSTOMERS']['STREETNR'];?>" name="inv_street_number" class="form-control" value="<?=htmlentities($u->inv_street_number);?>" maxlength="25">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-2">
                <div class="form-group">
                <input type="text" placeholder="<?=$lang['CUSTOMERS']['POSTCODE'];?>" name="inv_postcode" class="form-control" value="<?=htmlentities($u->inv_postcode);?>" maxlength="10">
                </div>
            </div>

            <div class="col-sm-10">
                <div class="form-group">
                <input type="text" placeholder="<?=$lang['CUSTOMERS']['CITY'];?>" name="inv_city" class="form-control" value="<?=htmlentities($u->inv_city);?>">
                </div>
            </div>
        </div>

        <div class="form-group">
            <input type="text" placeholder="<?=$lang['CUSTOMERS']['TTHOF'];?>" name="inv_tthof" class="form-control" value="<?=htmlentities($u->inv_tthof);?>">
        </div>

        <input type="hidden" name="inv_diff" value="<?=$hasDifferentInv ? '1' : '0';?>">
    </div>
</div>

<script>
$("#diffinvbtn").click(function(e) {
    e.preventDefault();

    var i = $(this).find("i");
    var p = $("#diffinvpanel");
    if (i.hasClass("fa-times")) {
        i.removeClass("fa-times").addClass("fa-plus");
        $("[name=inv_diff]").val("0");
        p.slideUp();
    } else {
        i.addClass("fa-times").removeClass("fa-plus");
        $("[name=inv_diff]").val("1");
        p.slideDown();
    }
});
</script>

<div class="panel panel-default" style="margin-top: 15px; margin-bottom: 0px;">
    <div class="panel-heading">
        <?=$lang['CUSTOMERS']['DIFFINVPAR'];?>
        <a href="#" class="pull-right" id="diffinvparbtn"><i class="fa fa-<?=!$hasDifferentInvParams ? 'plus' : 'times';?>"></i></a>
    </div>
    <div class="panel-body" id="diffinvparpanel"<?=!$hasDifferentInvParams ? ' style="display: none;"' : '';?>>
        <div class="form-group" style="margin-bottom: 0;">
            <label><?=$lang['CUSTOMERS']['INVDUE'];?></label>
            <div class="input-group">
                <input type="text" placeholder="<?=$CFG['INVOICE_DUEDATE'];?>" name="inv_due" class="form-control" value="<?=htmlentities($u->inv_due == -1 ? "" : $u->inv_due);?>">
                <span class="input-group-addon"><?=$lang['CUSTOMERS']['DAY_S'];?></span>
            </div>
        </div>

        <input type="hidden" name="inv_diff_par" value="<?=$hasDifferentInvParams ? '1' : '0';?>">
    </div>
</div>

<script>
$("#diffinvparbtn").click(function(e) {
    e.preventDefault();

    var i = $(this).find("i");
    var p = $("#diffinvparpanel");
    if (i.hasClass("fa-times")) {
        i.removeClass("fa-times").addClass("fa-plus");
        $("[name=inv_diff_par]").val("0");
        p.slideUp();
    } else {
        i.addClass("fa-times").removeClass("fa-plus");
        $("[name=inv_diff_par]").val("1");
        p.slideDown();
    }
});
</script>

        <?php
$status = "ok";
            if ($CFG['USER_CONFIRMATION'] == 1 && $u->confirmed != 1) {
                $status = "waiting";
            }

            if ($u->locked == 1) {
                $status = "locked";
            }

            ?>

        <div class="row">
        	<div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<?php
$q = $db->query("SELECT * FROM `terms_of_service` ORDER BY `ID` DESC LIMIT 1");
            if ($q->num_rows == 0) {
                $aktTOS = 0;
            } else {
                $aktTOS = $q->fetch_object()->ID;
            }

            ?>
				<input type="checkbox" name="tos" value="<?=$aktTOS;?>" <?php if ($u->tos >= $aktTOS) {
                echo "checked";
            }
            ?>> <?=$l['CONFTOS'];?>
				</label>
				</div>
        	</div>

        	<div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<input type="checkbox" name="withdrawal_rules" value="1" <?php if ($u->withdrawal_rules == 1) {
                echo "checked";
            }
            ?>> <?=$l['CONFWITHD'];?>
				</label>
				</div>
        	</div>

        	<div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<input type="checkbox" name="privacy_policy" value="1" <?php if ($u->privacy_policy == 1) {
                echo "checked";
            }
            ?>> <?=$l['CONFPRIVA'];?>
				</label>
				</div>
        	</div>
        </div>

        <div class="row">
        	<div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<input type="checkbox" name="verified" value="1" <?php if ($u->verified == 1) {
                echo "checked";
            }
            ?>> <?=$l['VERICUST'];?>
				</label>
				</div>
        	</div>

        	<?php if ($CFG['FACEBOOK_LOGIN'] || $CFG['TWITTER_LOGIN']) {?><div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<input type="checkbox" name="social_login" value="1" <?php if ($u->social_login == 1) {
                echo "checked";
            }
                ?>> <?=$l['SOCLOG'];?>
				</label>
				</div>
        	</div><?php }?>

        	<div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<input type="checkbox" name="login_notify" value="1" <?php if ($u->login_notify == 1) {
                echo "checked";
            }
            ?>> <?=$l['LOGINNOT'];?>
				</label>
				</div>
        	</div>

            <div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<input type="checkbox" name="reseller" value="1" <?php if ($u->reseller == 1) {
                echo "checked";
            }
            ?>> <?=$l['ISRESELLER'];?>
				</label>
				</div>
        	</div>

        	<?php if ($birthday_active) {?>
        	<div class="col-md-4">
        		<div class="checkbox" style="margin-bottom: 0;">
				<label>
				<input type="checkbox" name="birthday_mail" value="1" <?php if ($u->birthday_mail == 1) {
                echo "checked";
            }
                ?>> <?=$l['BIDAMAIL'];?>
				</label>
				</div>
        	</div>
        	<?php }?>
        </div>

		<?php if ($CFG['CASHBOX_ACTIVE']) {?>
		<?php
$sql = $db->query("SELECT ID, name FROM newsletter_categories ORDER BY name ASC");
                $my = explode("|", $u->newsletter);
                if ($sql->num_rows > 0) {?>
		<div class="checkbox">
		<span style="margin-right:20px;"><?=$l['NEWSLETTER'];?></span>
		<?php while ($row = $sql->fetch_object()) {?>
		<label class="checkbox-inline">
		  <input type="checkbox" name="newsletter[]" value="<?=$row->ID;?>"<?=in_array($row->ID, $my) ? ' checked=""' : '';?>> <?=htmlentities($row->name);?>
		</label>
		<?php }?>
		</div>
		<?php }?>

		<div class="checkbox">
		<span style="margin-right:20px;"><?=$l['CASHBOX'];?></span>
		<label class="radio-inline">
			  <input type="radio" name="cashbox_active" <?php if ($u->cashbox_active == 0) {
                    echo "checked";
                }
                ?> value="0"> <?=$l['NO'];?>
			</label>
			<label class="radio-inline">
			  <input type="radio" name="cashbox_active" <?php if ($u->cashbox_active == 1) {
                    echo "checked";
                }
                ?> value="1"> <?=$l['YES'];?>
			</label>
			<label class="radio-inline">
			  <input type="radio" name="cashbox_active" <?php if ($u->cashbox_active == 2) {
                    echo "checked";
                }
                ?> value="2"> <?=ucfirst($l['LOCKED']);?>
			</label>
		</div><?php } else {?><input type="hidden" name="cashbox_active" value="<?=$u->cashbox_active;?>" /><?php }?>

		<div class="checkbox">
		<span style="margin-right:20px;"><?=$l['ACCOUNT'];?></span>
		<?php if ($CFG['USER_CONFIRMATION'] == 1) {?><label class="radio-inline">
  <input type="radio" name="status" <?php if ($status == "waiting") {
                echo "checked";
            }
                ?> value="waiting"> <?=$l['WAITING'];?>
</label><?php }?>
<label class="radio-inline">
  <input type="radio" name="status" <?php if ($status == "ok") {
                echo "checked";
            }
            ?> value="ok"> <?=$l['FREE'];?>
</label>
<label class="radio-inline">
  <input type="radio" name="status" <?php if ($status == "locked") {
                echo "checked";
            }
            ?> value="locked"> <?=lcfirst($l['LOCKED']);?>
</label>
		</div>

		<input type="hidden" name="action" value="save_data">
		<div class="form-group">
          <button type="submit" name="change" <?php if (!$ari->check(10)) {
                echo "disabled";
            }
            ?> class="btn btn-primary btn-block">
            <?=$l['CHANGEDATA'];?>
          </button>
        </div>
      </fieldset>
    </form>
	<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<?php if ($CFG['TAXES']) {?>
		<tr>
			<td><?=$l['TAXING'];?></td>
			<td><?php
$userInstance = User::getInstance($u->mail);
                $tax = $userInstance->getVAT();
                if (is_array($tax)) {
                    echo $nfo->format($tax[1]) . " % " . $tax[0];
                } else if ($tax == "reverse") {
                    echo $l['RCTAX'];
                } else {
                    echo $l['NOTAXPOS'];
                }

                ?></td>
		</tr>
		<?php }if ($ari->check(16)) {?>
		<tr>
			<td><?=$l['AUTPAY'];?></td>
			<td><?php if ($userInstance->autoPaymentStatus()) {?><?=$l['ACTIVE'];?> [ <a href="?p=customers&edit=<?=$userInstance->get()['ID'];?>&stopAutoPayment=1"><?=lcfirst($l['DEACTIVATE']);?></a> ]<?php } else {?><?=$l['INACTIVE'];?><?php }?></td>
		</tr><?php }?>
		<tr>
			<td><?=$l['REGISTRATION'];?></td>
			<td><?=$dfo->format($u->registered);?></td>
		</tr>
		<tr>
			<td><?=$l['LASTLOGIN'];?></td>
			<td><?=$dfo->format($u->last_login != 0 ? $u->last_login : $u->registered);?></td>
		</tr>
		<tr>
			<td><?=$l['LASTACTIVE'];?></td>
			<td><?=$dfo->format($u->last_active);?></td>
		</tr>
		<tr>
			<td><?=$l['LASTIP'];?></td>
			<td><?php $ipLogSql = $db->query("SELECT country, city FROM ip_logs WHERE ip = '" . $db->real_escape_string($u->last_ip) . "' LIMIT 1");if ($ipLogSql->num_rows == 1) {
                $l2 = $ipLogSql->fetch_object();
            }
            if (!isset($l2) || (($l2->country == "" || $l2->country == "no") && ($l2->city == "" || $l2->city == "no"))) {?><?=$u->last_ip;?><?php } else {?><a href="#" data-toggle="tooltip" onclick="return false;" data-original-title="<?php if ($l2->city != "" && $l2->city != "no") {?><?=$l2->city;?>, <?php }if ($l2->country != "" && $l2->country != "no") {?><?=$l2->country;?><?php }?>"><?=$u->last_ip;?></a><?php }?></td>
		</tr>
        <tr>
			<td><?=$l['CUST_SOURCE'];?></td>
			<td><?=htmlentities($u->cust_source ?: "-");?></td>
		</tr>
		<tr>
			<td><?=$l['APIKEY'];?></td>
			<td><?=$u->api_key;?><?php if ($ari->check(10)) {?> [ <a href="?p=customers&edit=<?=$_GET['edit'];?>&new_api_key=1"><?=$l['REGENERATE'];?></a> ]<?php }?></td>
		</tr>
		<?php if ($CFG['CASHBOX_ACTIVE']) {?><tr>
			<td><?=$l['CBLINK'];?></td>
			<td><?=$u->cashbox_active == 1 ? "<a href='" . $CFG['PAGEURL'] . "cashbox/" . $u->ID . "/" . substr(hash("sha512", $u->ID . $CFG['HASH']), 0, 10) . "' target='_blank'>" . $CFG['PAGEURL'] . "cashbox/" . $u->ID . "/" . substr(hash("sha512", $u->ID . $CFG['HASH']), 0, 10) . "</a>" : "<font color='red'>{$l['NOTACTIVE']}</font>";?></td>
		</tr><?php }?>

		<?php
$hook = $addons->runHook("AdminCustomerDetailTable", [
                "userinfo" => $u,
            ]);
            foreach ($hook as $return) {
                echo $return;
            }
            ?>
	</table>
	</div>
  </div>
<?php } else if ($tab == "history") {

            if (isset($_GET['revert']) && is_numeric($_GET['revert']) && is_object($sql = $db->query("SELECT * FROM client_changes WHERE ID = " . intval($_GET['revert']) . " AND user = {$u->ID}")) && $sql->num_rows == 1) {
                $info = $sql->fetch_object();
                $diff = unserialize($info->diff);
                foreach ($diff as $k => $v) {
                    $userInstance->set(array($k => $v[0]));
                }

                $userInstance->resetChanges();
                $db->query("DELETE FROM client_changes WHERE ID = " . intval($info->ID) . " AND user = {$u->ID}");
                $session->set('history_revert', 1);
                alog("customers", "history_revert", $u->ID, $info->ID);
                header('Location: ?p=customers&edit=' . $u->ID . '&tab=history');
                exit;
            }

            if ($session->get('history_revert') == 1) {
                $session->set('history_revert', "");
                echo "<div class='alert alert-success'>{$l['SUC57']}</div>";
            }

            $options = [
                "0" => $l['CUSTOMER'],
                "1" => $l['STAFFMEMBER'],
            ];

            $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                $options["admin" . $row->ID] = htmlentities($row->name);
            }

            function formatChanger($who)
                {
                global $options, $l;

                if (array_key_exists($who, $options)) {
                    return $options[$who];
                }

                return $l['STAFFMEMBER'];
            }

            $t = new Table("SELECT * FROM client_changes WHERE user = {$u->ID}", [
                "who" => [
                    "name" => $l['CHANGEDBY'],
                    "type" => "select",
                    "options" => $options,
                ],
            ], ["time", "DESC"]);

            echo $t->getHeader();

            ?>
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th><?=$t->orderHeader("time", $l['DATE']);?></th>
			<th><?=$l['CHANGES'];?></th>
			<th><?=$t->orderHeader("who", $l['CHANGEDBY']);?></th>
			<?php if ($ari->check(10)) {?><th width="30px"></th><?php }?>
		</tr>

		<?php
$sql = $t->qry("`time` DESC");
            if ($sql->num_rows == 0) {
                ?>
		<tr>
			<td colspan="4"><center><?=$l['CHANGESNT'];?></center></td>
		</tr>
		<?php } else {
                while ($row = $sql->fetch_object()) {
                    $row->diff = unserialize($row->diff);
                    foreach ($row->diff as $k => $v) {
                        if (substr($k, 0, 6) != "field:") {
                            if (!in_array($k, User::displayChanges())) {
                                unset($row->diff[$k]);
                            }

                        } else {
                            if ($db->query("SELECT 1 FROM client_fields WHERE ID = " . intval(substr($k, 6)))->num_rows != 1) {
                                unset($row->diff[$k]);
                            }

                        }
                    }

                    if (count($row->diff) == 0) {
                        continue;
                    }

                    ?>
		<tr>
			<td><?=$dfo->format($row->time, true, true);?></td>
			<td><a href="#" data-toggle="modal" data-target="#change_<?=$row->ID;?>" onclick="return false;"><?=count($row->diff) . " " . (count($row->diff) != 1 ? $l['FIELDS'] : $l['FIELD']);?></a></td>
			<td><?=formatChanger($row->who);?></td>
			<?php if ($ari->check(10)) {?><td><a href="?p=customers&edit=<?=$u->ID;?>&tab=history&revert=<?=$row->ID;?>"><i class="fa fa-undo"></i></a></td><?php }?>
		</tr>
		<?php }}?>
	</table>
</div>
<?php

            echo $t->getFooter();

            $fields = array(
                "mail" => $l['MAILADDRESS'],
                "salutation" => $l['SALUTATION'],
                "firstname" => $l['FIRSTNAME'],
                "lastname" => $l['LASTNAME'],
                "nickname" => $l['NICKNAME'],
                "company" => $l['COMPANY'],
                "street" => $l['STREET'],
                "street_number" => $l['STREETNR'],
                "postcode" => $l['POSTCODE'],
                "city" => $l['CITY'],
                "country" => $l['COUNTRY'],
                "telephone" => $l['TELEPHONE'],
                "fax" => $l['FAXNUM'],
                "birthday" => $l['BIRTHDATE'],
                "login_notify" => $l['LOGNOTSH'],
                "reseller" => $l['ISRESELLERSH'],
                "tfa" => $l['TFACODE'],
                "newsletter" => $l['NEWSLETTER'],
                "website" => $l['WEBSITE'],
                "verified" => $l['VERICUST'],
                "pwd" => $l['PASSWORD'],
                "birthday_mail" => $l['BIDACONGRATS'],
                "vatid" => $l['EUVATID'],
                "telephone_verified" => $l['TELVERI'],
                "inv_street" => $l['INV_STREET'],
                "inv_street_number" => $l['INV_STREETNR'],
                "inv_postcode" => $l['INV_POSTCODE'],
                "inv_city" => $l['INV_CITY'],
                "inv_tthof" => $l['INV_TTHOF'],
                "inv_due" => $l['INVDUE'],
            );

            $checkboxes = array("reseller", "login_notify", "verified", "birthday_mail", "telephone_verified");
            $dates = array("birthday");

            function cformat($k, $v)
                {
                global $checkboxes, $dates, $dfo, $db, $CFG, $l;

                if ($k == "country") {
                    if (empty($v) || !is_numeric($v) || $v == 0) {
                        return "";
                    }

                    $cSql = $db->query("SELECT name FROM client_countries WHERE ID = " . intval($v));
                    if ($cSql->num_rows != 1) {
                        return "<i>{$l['NOTEXISTINGNOW']}</i>";
                    }

                    return $cSql->fetch_object()->name;
                } else if (in_array($k, $checkboxes)) {
                    if ($v) {
                        return $l['YES'];
                    } else {
                        return $l['NO'];
                    }

                } else if (in_array($k, $dates)) {
                    if (empty($v) || $v == "0000-00-00") {
                        return "";
                    }

                    if (!is_numeric($v)) {
                        $v = strtotime($v);
                    }

                    return $dfo->format($v, false, false);
                } else if ($k == "pwd") {
                    if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                        return $v;
                    }

                    return "<i>{$l['HASHVAL']}</i>";
                } else if ($k == "salutation") {
                    return array_key_exists($v, $l) ? $l[$v] : $l["NA"];
                } else if ($k == "inv_due" && $v < 0) {
                    return $CFG['INVOICE_DUEDATE'];
                }
                return $v;
            }

            $sql->data_seek(0);while ($row = $sql->fetch_object()) {$diff = unserialize($row->diff);
                foreach ($diff as $k => $v) {
                    if (substr($k, 0, 6) == "field:" && !isset($fields[$k])) {
                        $fields[$k] = $db->query("SELECT name FROM client_fields WHERE ID = " . intval(substr($k, 6)))->fetch_object()->name;
                    }
                }

                ?>
<div class="modal fade" tabindex="-1" role="dialog" id="change_<?=$row->ID;?>">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$l['CHANGESON'];?> <?=$dfo->format($row->time, false, false);?></h4>
      </div>
      <div class="modal-body">
       	<div class="table-responsive">
	        <table class="table table-bordered table-striped">
	    		<tr>
	    			<th><?=$l['FIELD'];?></th>
	    			<th><?=$l['OLDDATA'];?></th>
	    			<th><?=$l['NEWDATA'];?></th>
	    		</tr>

	    		<?php foreach ($diff as $k => $v) {if (count($v) != 2 || (!in_array($k, User::displayChanges()) && substr($k, 0, 6) != "field:")) {
                    continue;
                }
                    ?>
	    		<tr>
	    			<td><?=isset($fields[$k]) ? $fields[$k] : $k;?></td>
	    			<td><?=htmlentities(cformat($k, $v[0]));?></td>
	    			<td><?=htmlentities(cformat($k, $v[1]));?></td>
	    		</tr>
	    		<?php }?>
	        </table>
	    </div>
      </div>
    </div>
  </div>
</div>
<?php }?>
<?php } else {
            if (isset($_GET['dc']) && is_numeric($_GET['dc']) && is_object($sql = $db->query("SELECT * FROM client_contacts WHERE ID = " . intval($_GET['dc']) . " AND client = {$u->ID}")) && $sql->num_rows == 1) {
                $db->query("DELETE FROM client_contacts WHERE ID = " . intval($_GET['dc']) . " AND client = {$u->ID}");
                alog("customers", "contact_delete", $_GET['dc']);
                echo '<div class="alert alert-success">' . $l['CONTACTDELETED'] . '</div>';
            }

            $contacts = $uI->getContacts();?>
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="30%"><?=$l['CONNAME'];?></th>
			<th width="20%"><?=$l['CONPOS'];?></th>
			<th width="25%"><?=$l['MAILADDRESS'];?></th>
			<th width="20%"><?=$l['CONPHONE'];?></th>
			<th width="30px"><center><i class="fa fa-user"></i></center></th>
			<th width="30px"><i class="fa fa-envelope-o"></i></th>
			<th width="30px" style="width: 30px;"></th>
			<th width="30px" style="width: 30px;"></th>
		</tr>

		<?php if (count($contacts) == 0) {?>
		<tr>
			<td colspan="8"><center><?=$l['CONNT'];?></center></td>
		</tr>
		<?php } else {foreach ($contacts as $id => $c) {?>
		<tr>
			<td><?=$c->get("firstname") . " " . $c->get("lastname") . (!empty($c->get("company")) ? " ({$c->get('company')})" : "");?></td>
			<td><?=$c->get("type") ?: "<i>{$l['NOTSUPPLIED']}</i>";?></td>
			<td><?=filter_var($c->get("mail"), FILTER_VALIDATE_EMAIL) ? "<a href='mailto:{$c->get('mail')}'>{$c->get('mail')}</a>" : "<i>{$l['NOTSUPPLIED']}</i>";?></td>
			<td><?php if (empty($c->get("telephone"))) {echo "<i>{$l['NOTSUPPLIED']}</i>";} else {?><?php if ($adminInfo->can_call) {?><a href="#" class="callNumber"><?php }?><?=$c->get("telephone");?><?php if ($adminInfo->can_call) {?></a><?php }?><?php }?></td>
        <td><i class="fa fa-<?=$c->get("rights") ? "check" : "times";?>"></i></td>
			<td><i class="fa fa-<?=$c->get("mail_templates") ? "check" : "times";?>"></i></td>
			<td><a href="?p=contact&edit=<?=$id;?>"><i class="fa fa-edit"></i></a></td>
			<td><a href="?p=customers&edit=<?=$u->ID;?>&tab=contacts&dc=<?=$id;?>" onclick="return confirm('<?=$l['CONREALLYDEL'];?>');"><i class="fa fa-times"></i></a></td>
		</tr>
		<?php }}?>
	</table>
</div>

<script>
var cdoing = 0;

$(".callNumber").click(function(e){
	e.preventDefault();

	if(cdoing) return;
	cdoing = 1;

	var b = $(".callNumber");
	var n = b.html().trim();
	b.css("color", "").html('<i class="fa fa-spinner fa-spin"></i> <?=$l['BEINGCALLED'];?>');

	$.get("?p=call&number=" + encodeURIComponent(n), function(r){
		if(r == "ok") b.html(n).css("color", "green");
		else b.html(n).css("color", "red");

		cdoing = 0;
	});
});
</script>
<?php }?>
</div>

</div>

</div>
<?php } else if ($ari->check(18) && $tab == "cart") {?>
<div class="tab-pane" id="tab_cart">

<?php
if (isset($_GET['delete_cart_item'])) {
            $item = abs(intval($_GET['delete_cart_item']));
            $sql = $db->query("DELETE FROM client_cart WHERE ID = '$item' AND user = " . $u->ID . " LIMIT 1");

            if ($db->affected_rows >= 1) {
                echo "<div class='alert alert-success'>{$l['SUC58']}</div>";
                alog("cart", "deleted", $item, $u->ID);
            }
        } else if (isset($_POST['delete_selected_cart']) && is_array($_POST['cart'])) {
            $d = 0;
            foreach ($_POST['cart'] as $id) {
                $item = abs(intval($id));
                $sql = $db->query("DELETE FROM client_cart WHERE ID = '$item' AND user = " . $u->ID . " LIMIT 1");

                if ($db->affected_rows >= 1) {
                    $d++;
                    alog("cart", "deleted", $item, $u->ID);
                }
            }

            if ($d == 1) {
                echo "<div class='alert alert-success'>{$l['SUC58O']}</div>";
            } else if ($d > 0) {
                echo "<div class='alert alert-success'>" . str_replace("%d", $d, $l['SUC58X']) . "</div>";
            }

        }

            $uCart = new Cart($u->ID);

            $display = array();
            $sum = 0.00;

            $elements = $uCart->get();
            foreach ($elements as $e) {
                $customerName = "??? ???";
                $customerInfoQuery = $db->query("SELECT firstname, lastname FROM clients WHERE ID = " . $e['user']);
                if ($customerInfoQuery->num_rows == 1) {
                    $customerInfo = $customerInfoQuery->fetch_object();
                    $customerName = $customerInfo->firstname . " " . $customerInfo->lastname;
                }

                switch ($e['type']) {

                    case 'product':
                        $sum += $e['sum'];

                        $customerName = "??? ???";
                        $customerInfoQuery = $db->query("SELECT firstname, lastname FROM clients WHERE ID = " . $e['user']);
                        if ($customerInfoQuery->num_rows == 1) {
                            $customerInfo = $customerInfoQuery->fetch_object();
                            $customerName = $customerInfo->firstname . " " . $customerInfo->lastname;
                        }

                        $l2 = strtoupper($e['license']);

                        if ($customerName != "??? ???") {
                            $display[] = array("ID" => $e['ID'], "qty" => $e['qty'], "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => "{$l['PRODUCT']} ($l2)", "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount'], "oldprice" => $e['oldprice']);
                        } else {
                            $display[] = array("ID" => $e['ID'], "qty" => $e['qty'], "customer" => "<i>{$l['NOTEXISTINGNOW']}</i>", "added" => $e['added'], "type" => "{$l['PRODUCT']} ($l2)", "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount'], "oldprice" => $e['oldprice']);
                        }

                        break;

                    case 'domain_reg':
                    case 'domain_in':
                        $sum += $e['sum'];
                        if ($customerName != "??? ???") {
                            $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => ($e['type'] == "domain_reg" ? $l['DOMREG'] : $l['DOMKK']), "name" => unserialize($e['license'])['domain'], "price" => $e['amount']);
                        } else {
                            $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<i>{$l['NOTEXISTINGNOW']}</i>", "added" => $e['added'], "type" => ($e['type'] == "domain_reg" ? $l['DOMREG'] : $l['DOMKK']), "name" => unserialize($e['license'])['domain'], "price" => $e['amount']);
                        }

                        break;

                    case 'update':
                        $sum += $e['sum'];

                        if ($customerName != "??? ???") {
                            $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => $l['UPDATECART'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                        } else {
                            $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<i>{$l['NOTEXISTINGNOW']}</i>", "added" => $e['added'], "type" => $l['UPDATECART'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                        }

                        break;

                    case 'bundle':
                        $sum += $e['sum'];

                        if ($customerName != "??? ???") {
                            $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => $l['PRODBUNDCART'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                        } else {
                            $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<i>{$l['NOTEXISTINGNOW']}</i>", "added" => $e['added'], "type" => $l['PRODBUNDCART'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                        }

                        break;
                }
            }
            ?>

<?php if (count($display) > 0) {?><div class="alert alert-info">
<?=$l['CURCARTVAL'];?> <?=$cur->infix($nfo->format($sum), $cur->getBaseCurrency());?>.
</div><?php }?>

<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$l['ADDED'];?></th>
		<th><?=$l['TYPE'];?></th>
		<th><?=$l['BEZ'];?></th>
		<th width="40px"><center><?=$l['QTY'];?></center></th>
		<th><?=$l['SINGLEPRICE'];?></th>
		<th><?=$l['TOTALPRICE'];?></th>
        <th width="30px"></th>
	</tr>

	<form method="POST">
	<?php
if (count($display) == 0) {
                echo "<tr><td colspan=\"8\"><center>{$l['CARTEMPTY']}</center></td></tr>";
            } else {
                foreach ($display as $d) {
                    ?>
			<tr>
				<td><input type="checkbox" name="cart[]" value="<?=$d['ID'];?>" class="checkbox" onchange="javascript:toggle();" /></td>
				<td><?=$dfo->format($d['added']);?></td>
				<td><?=$d['type'];?></td>
				<td><?=$d['name'];?></td>
				<td width="40px"><center><?=$d['qty'];?></center></td>
				<td><?php if ($d['oldprice'] != 0 && $d['oldprice'] != $d['price']) {?><s style="color:red;"><span style="color:#444444;"><?=$cur->infix($nfo->format($d['oldprice']), $cur->getBaseCurrency());?></span></s> <?php }?><?=$cur->infix($nfo->format($d['price']), $cur->getBaseCurrency());?></td>
				<td><?php if ($d['oldprice'] != 0 && $d['oldprice'] != $d['price']) {?><s style="color:red;"><span style="color:#444444;"><?=$cur->infix($nfo->format($d['oldprice'] * $d['qty']), $cur->getBaseCurrency());?></span></s> <?php }?><?=$cur->infix($nfo->format($d['price'] * $d['qty']), $cur->getBaseCurrency());?></td>
                <td width="30px"><a href="?p=customers&edit=<?=$_GET['edit'];?>&tab=cart&delete_cart_item=<?=$d['ID'];?>"><i class="fa fa-times fa-lg"></a></td>
			<?php
}
            }
            ?>
</table></div>
<?=$l['SELECTED'];?>: <input type="submit" name="delete_selected_cart" value="<?=$l['DELETE'];?>" class="btn btn-danger" />
</form>

</div><?php } else if ($tab == "telephone" && $ari->check(51)) {?>
<div class="tab-pane" id="tab_cart">

<?php
if (isset($_GET['id']) && is_numeric($_GET['id']) && is_object($sql = $db->query("SELECT * FROM client_calls WHERE user = {$u->ID} AND ID = " . intval($_GET['id']))) && $sql->num_rows == 1) {
            $callInfo = $sql->fetch_object();

            if (isset($_POST['edit_call'])) {
                try {
                    foreach ($_POST as $k => $v) {
                        $k = "post" . ucfirst(strtolower($k));
                        $$k = $db->real_escape_string(trim($v));
                    }

                    if (isset($postTime) && !is_numeric($postTime)) {
                        $postTime = strtotime($postTime);
                    }

                    if (empty($postTime) || !is_numeric($postTime)) {
                        throw new Exception($l['CALLERR1']);
                    }

                    if (empty($postSubject) || strlen($postSubject) > 255) {
                        throw new Exception($l['CALLERR2']);
                    }

                    if (!isset($postContent)) {
                        $postContent = "";
                    }

                    if (empty($postAdmin) || !is_numeric($postAdmin) || $db->query("SELECT ID FROM admins WHERE ID = " . intval($postAdmin))->num_rows != 1) {
                        throw new Exception($l['CALLERR3']);
                    }

                    if (!isset($postEndtime)) {
                        $postEndtime = time();
                    }

                    if (!is_numeric($postEndtime)) {
                        $postEndtime = strtotime($postEndtime);
                    }

                    if (empty($postEndtime) || !is_numeric($postEndtime)) {
                        throw new Exception($l['CALLERR4']);
                    }

                    $billed = 0;
                    if (isset($postBilled) && $callInfo->billed == 0) {
                        if (empty($postBilling_hour) || !is_numeric($nfo->phpize($postBilling_hour)) || $nfo->phpize($postBilling_hour) <= 0) {
                            throw new Exception($l['CALLERR5']);
                        }

                        $postBilling_hour = $nfo->phpize($postBilling_hour);
                        $curObj = new Currency($cur->getBaseCurrency());
                        $prefix = $curObj->getPrefix();
                        $suffix = $curObj->getSuffix();

                        switch ($postBilling_type) {
                            case '1':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $billing_amount = round($postBilling_hour * ($minutes / 60), 2);
                                $billing_name = $l['CALLSUP'] . " ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$l['CALLEXACTMIN']} ({$prefix}" . $nfo->format($postBilling_hour) . "{$suffix} / {$l['CALL1']})</i><br /><br />{$postSubject}";
                                break;

                            case '2':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = ceil(($postEndtime - $postTime) / 3600);
                                $billing_amount = round($postBilling_hour * $hours, 2);
                                $billing_name = $l['CALLSUP'] . " ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour) . "{$suffix} / {$l['CALL2']}</i><br /><br />{$postSubject}";
                                break;

                            case '3':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = floor(($postEndtime - $postTime) / 3600);
                                $billing_amount = round($postBilling_hour * $hours, 2);
                                $billing_name = $l['CALLSUP'] . " ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour) . "{$suffix} / {$l['CALL3']}</i><br /><br />{$postSubject}";
                                break;

                            case '4':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = ceil(($postEndtime - $postTime) / 900);
                                $billing_amount = round($postBilling_hour * $hours / 4, 2);
                                $billing_name = $l['CALLSUP'] . " ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour / 4) . "{$suffix} / {$l['CALL4']}/i><br /><br />{$postSubject}";
                                break;

                            case '5':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = floor(($postEndtime - $postTime) / 900);
                                $billing_amount = round($postBilling_hour * $hours / 4, 2);
                                $billing_name = $l['CALLSUP'] . " ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour / 4) . "{$suffix} / {$l['CALL5']}</i><br /><br />{$postSubject}";
                                break;

                            default:
                                throw new Exception($l['CALLERR6']);
                        }

                        if (isset($billing_amount)) {
                            $inv = new Invoice;
                            $item = new InvoiceItem;

                            $item->setDescription("<b>" . $billing_name . "</b><br />" . $billing_text);
                            $item->setAmount($billing_amount);

                            $inv->setDate(date("Y-m-d", $postEndtime));
                            $inv->setClient($u->ID);
                            $inv->setDueDate(date("Y-m-d", $postEndtime + 86400 * $CFG['INVOICE_DUEDATE']));
                            $inv->addItem($item);

                            $inv->save();
                            $billed = 1;

                            $paid = 0;
                            if (isset($postCredit)) {
                                $inv->applyCredit();
                                $paid = 1;
                            }
                        }
                    }

                    $wasBilled = $billed == 1 || $callInfo->billed != 0 ? 1 : 0;
                    $db->query("UPDATE client_calls SET `time` = $postTime, `subject` = '$postSubject', `content` = '$postContent', `endtime` = $postEndtime, `billed` = $wasBilled, `admin` = $postAdmin WHERE ID = {$callInfo->ID} LIMIT 1");

                    $billtext = "";
                    if ($billed == 1) {
                        if ($paid) {
                            $billtext = " " . $l['CALLBILL1'];
                        } else {
                            $billtext = " " . $l['CALLBILL2'];
                        }

                        if (isset($postEmail)) {
                            $inv->send();
                            $billtext .= " " . $l['CALLBILL3'];
                        }
                    }

                    alog("customers", "call_edited", $_GET['id'], $u->ID);
                    echo "<div class='alert alert-success'>{$l['CALLEDITED']}$billtext</div>";
                    $callInfo = $sql = $db->query("SELECT * FROM client_calls WHERE user = {$u->ID} AND ID = " . intval($_GET['id']))->fetch_object();
                } catch (Exception $ex) {
                    echo "<div class='alert alert-danger'>{$ex->getMessage()}</div>";
                }
            }

            $path = __DIR__ . "/../../files/calls/" . $callInfo->ID;

            if (!empty($_GET['delete_file'])) {
                @unlink($path . "/" . basename($_GET['delete_file']));
                header("Location: ?p=customers&edit={$u->ID}&tab=telephone&id=" . $callInfo->ID);
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

                header("Location: ?p=customers&edit={$u->ID}&tab=telephone&id=" . $callInfo->ID);
                exit;
            }
            ?>
<div class="row"><div class="col-md-9"><div class="panel panel-primary"><div class="panel-heading"><?=$l['CALL'];?></div><div class="panel-body">
	<form method="POST">
	<div class="form-group">
	    <label><?=$l['CALLSUB'];?></label>
	    <input type="text" class="form-control" placeholder="<?=$l['CALLSUBP'];?>" name="subject" maxlength="255" value="<?=isset($_POST['subject']) ? $_POST['subject'] : $callInfo->subject;?>">
    </div>

    <div class="form-group">
	    <label><?=$l['CALLRES'];?></label>
	    <textarea class="form-control" style="width:100%; height:150px; resize:none;" placeholder="<?=$l['CALLRESP'];?>" name="content"><?=isset($_POST['content']) ? $_POST['content'] : ($callInfo->content);?></textarea>
    </div>

    <div class="form-group">
	    <label><?=$l['STAFFMEMBER'];?></label>
	    <?php $selected = isset($_POST['admin']) ? $_POST['admin'] : $callInfo->admin;?>
	    <select class="form-control" name="admin">
	    	<option value="0" disabled><?=$l['CPCSM'];?></option>
	    	<?php $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {?>
	    	<option value="<?=$row->ID;?>"<?php if ($selected == $row->ID) {
                echo " selected='selected'";
            }
                ?>><?=$row->name;?></option>
	    	<?php }?>
	    </select>
	</div>

    <div class="form-group">
        <label><?=$l['CALLTIM'];?> <?php if (count(unserialize($CFG['TELEPHONE_LOG'])) > 0) {?><a href="#" class="btn btn-default btn-xs" id="show_telephone_log"><?=$l['CALLLOG'];?></a><?php }?></label>
        <div class="row">
            <div class="col-xs-6">
                <div class="input-group" style="position: relative;">
                    <span class="input-group-addon"><?=$l['CALLSTART'];?></span>
                    <input type="text" class="form-control datetimepicker" name="time" value="<?=isset($_POST['time']) ? $_POST['time'] : $dfo->format($callInfo->time, true, true, "");?>" placeholder="<?=$dfo->format(time(), true, true, "");?>">
                </div>
            </div>

            <div class="col-xs-6">
                <div class="input-group" style="position: relative;">
                    <span class="input-group-addon"><?=$l['CALLEND'];?></span>
                    <input type="text" class="form-control datetimepicker" name="endtime" value="<?=isset($_POST['endtime']) ? $_POST['endtime'] : $dfo->format($callInfo->endtime, true, true, "");?>" placeholder="<?=$dfo->format(time(), true, true, "");?>">
                </div>
            </div>
        </div>
    </div>

    <?php if ($callInfo->billed == 0) {?>
    <div class="checkbox">
    	<label>
    		<input type="checkbox" name="billed" value="1"<?=isset($_POST['billed']) ? " checked='checked'" : "";?> onchange="javascript:billing(this.checked);"> <?=$l['CALLBILLLATER'];?>
    	</label>
    </div>

    <script type="text/javascript">
    	function billing(display) {
    		if(display)
    			document.getElementById("billing").style.display = "block";
    		else
    			document.getElementById("billing").style.display = "none";
    	}
    </script>

    <div id="billing"<?=!isset($_POST['billed']) ? ' style="display:none;"' : '';?>>
    	<div class="form-group">
        <label><?=$l['CALLBILLTYPE'];?></label>
        <div class="row">
            <div class="col-xs-8">
                <select class="form-control" name="billing_type">
                	<option disabled<?=!isset($_POST['billing_type']) ? ' selected="selected"' : '';?>><?=$l['CPCSM'];?></option>
                	<option value="1"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "1" ? ' selected="selected"' : "";?>><?=$l['CBT1'];?></option>
                	<option value="2"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "2" ? ' selected="selected"' : "";?>><?=$l['CBT2'];?></option>
                	<option value="3"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "3" ? ' selected="selected"' : "";?>><?=$l['CBT3'];?></option>
                	<option value="4"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "4" ? ' selected="selected"' : "";?>><?=$l['CBT4'];?></option>
                	<option value="5"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "5" ? ' selected="selected"' : "";?>><?=$l['CBT5'];?></option>
                </select>
            </div>

            <div class="col-xs-4">
                <div class="input-group">
                	<?php $curObj = new Currency($cur->getBaseCurrency());?>
                    <?php if (!empty($curObj->getPrefix())) {?><span class="input-group-addon"><?=$curObj->getPrefix();?></span><?php }?>
                    <input type="text" class="form-control" name="billing_hour" value="<?=isset($_POST['billing_hour']) ? $_POST['billing_hour'] : "";?>" placeholder="<?=$nfo->placeholder();?>">
                    <?php if (!empty($curObj->getSuffix())) {?><span class="input-group-addon"><?=$curObj->getSuffix();?></span><?php }?>
                </div>
            </div>
        </div>

        <div class="checkbox">
	    	<label>
	    		<input type="checkbox" name="credit" value="1"<?=isset($_POST['credit']) ? " checked='checked'" : "";?>> <?=$l['PAYINVBYCREDIT'];?>
	    	</label>
	    </div>

	    <div class="checkbox">
	    	<label>
	    		<input type="checkbox" name="email" value="1"<?=!isset($_POST['edit_call']) || isset($_POST['email']) ? " checked='checked'" : "";?>> <?=$l['SENDINVBYMAIL'];?>
	    	</label>
	    </div>
    </div>
    </div>
    <?php }?>

	<input type="submit" name="edit_call" value="<?=$l['EDITCALL'];?>" class="btn btn-primary btn-block" />
	</form>
</div></div></div>

<div class="col-md-3">
<div class="panel panel-default">
	<div class="panel-heading"><?=$l['FILES'];?><a href="#" data-toggle="modal" data-target="#uploadDomainFile" class="pull-right"><i class="fa fa-plus"></i></a></div>
	<div class="panel-body" style="text-align: justify;">
		<?php
if (file_exists($path) && is_dir($path)) {
                $files = [];
                foreach (glob($path . "/*") as $f) {
                    array_push($files, basename($f));
                }
                if (!count($files)) {
                    echo "<i>{$l['CALLNOFILES']}</i>";
                } else {
                    echo "<ul style='margin-bottom: 0;'>";

                    foreach ($files as $file) {
                        echo "<li>";
                        echo "<a href='?p=customers&edit={$u->ID}&tab=telephone&id={$callInfo->ID}&download_file=" . urlencode($file) . "' target='_blank'>" . htmlentities($file) . "</a>";
                        echo "<a href='?p=customers&edit={$u->ID}&tab=telephone&id={$callInfo->ID}&delete_file=" . urlencode($file) . "' class='pull-right'><i class='fa fa-times'></i></a>";
                        echo "</li>";
                    }

                    echo "</ul>";
                }
            } else {
                echo "<i>{$l['CALLNOFILES']}</i>";
            }
            ?>
	</div>
</div>

<div class="modal fade" id="uploadDomainFile" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="POST" enctype="multipart/form-data" role="form">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?=$l['UPLFILES'];?></h4>
				</div>
				<div class="modal-body">
					<div class="form-group" style="margin-bottom: 0;">
						<input type="file" class="form-control" name="upload_files[]" multiple>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
					<button type="submit" class="btn btn-primary"><?=$l['UPLFILES'];?></button>
				</div>
			</form>
		</div>
	</div>
</div>
</div>

</div>
	<?php
} else if (isset($_GET['action']) && ($_GET['action'] == "new" || $_GET['action'] == "import")) {
            if (isset($_POST['add_call'])) {
                try {
                    foreach ($_POST as $k => $v) {
                        $k = "post" . ucfirst(strtolower($k));
                        $$k = $db->real_escape_string(trim($v));
                    }

                    if (isset($postTime) && !is_numeric($postTime)) {
                        $postTime = strtotime($postTime);
                    }

                    if (empty($postTime) || !is_numeric($postTime)) {
                        throw new Exception($l['CALLERR1']);
                    }

                    if (empty($postSubject) || strlen($postSubject) > 255) {
                        throw new Exception($l['CALLERR2']);
                    }

                    if (!isset($postContent)) {
                        $postContent = "";
                    }

                    if (empty($postAdmin) || !is_numeric($postAdmin) || $db->query("SELECT ID FROM admins WHERE ID = " . intval($postAdmin))->num_rows != 1) {
                        throw new Exception($l['CALLERR3']);
                    }

                    if (!isset($postEndtime)) {
                        $postEndtime = time();
                    }

                    if (!is_numeric($postEndtime)) {
                        $postEndtime = strtotime($postEndtime);
                    }

                    if (empty($postEndtime) || !is_numeric($postEndtime)) {
                        throw new Exception($l['CALLERR4']);
                    }

                    $billed = 0;
                    if (isset($postBilled)) {
                        if (empty($postBilling_hour) || !is_numeric($nfo->phpize($postBilling_hour)) || $nfo->phpize($postBilling_hour) <= 0) {
                            throw new Exception($l['CALLERR5']);
                        }

                        $postBilling_hour = $nfo->phpize($postBilling_hour);
                        $curObj = new Currency($cur->getBaseCurrency());
                        $prefix = $curObj->getPrefix();
                        $suffix = $curObj->getSuffix();

                        switch ($postBilling_type) {
                            case '1':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $billing_amount = round($postBilling_hour * ($minutes / 60), 2);
                                $billing_name = $l['CALLSUP'] . " ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$l['CALLEXACTMIN']} ({$prefix}" . $nfo->format($postBilling_hour) . "{$suffix} / {$l['CALL1']})</i><br /><br />{$postSubject}";
                                break;

                            case '2':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = ceil(($postEndtime - $postTime) / 3600);
                                $billing_amount = round($postBilling_hour * $hours, 2);
                                $billing_name = "{$l['CALLEXACTMIN']} ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour) . "{$suffix} / {$l['CALL2']}</i><br /><br />{$postSubject}";
                                break;

                            case '3':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = floor(($postEndtime - $postTime) / 3600);
                                $billing_amount = round($postBilling_hour * $hours, 2);
                                $billing_name = "{$l['CALLEXACTMIN']} ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour) . "{$suffix} / {$l['CALL3']}</i><br /><br />{$postSubject}";
                                break;

                            case '4':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = ceil(($postEndtime - $postTime) / 900);
                                $billing_amount = round($postBilling_hour * $hours / 4, 2);
                                $billing_name = "{$l['CALLEXACTMIN']} ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour / 4) . "{$suffix} / {$l['CALL4']}</i><br /><br />{$postSubject}";
                                break;

                            case '5':
                                $minutes = round(($postEndtime - $postTime) / 60);
                                $hours = floor(($postEndtime - $postTime) / 900);
                                $billing_amount = round($postBilling_hour * $hours / 4, 2);
                                $billing_name = "{$l['CALLEXACTMIN']} ($minutes " . ($minutes == 1 ? $l['MINUTE'] : $l['MINUTES']) . ")";
                                $billing_text = "<i>{$prefix}" . $nfo->format($postBilling_hour / 4) . "{$suffix} / {$l['CALL5']}</i><br /><br />{$postSubject}";
                                break;

                            default:
                                throw new Exception($l['CALLERR6']);
                        }

                        if (isset($billing_amount)) {
                            $inv = new Invoice;
                            $item = new InvoiceItem;

                            $item->setDescription("<b>" . $billing_name . "</b><br />" . $billing_text);
                            $item->setAmount($billing_amount);

                            $inv->setDate(date("Y-m-d", $postEndtime));
                            $inv->setClient($u->ID);
                            $inv->setDueDate(date("Y-m-d", $postEndtime + 86400 * $CFG['INVOICE_DUEDATE']));
                            $inv->addItem($item);

                            $inv->save();
                            $billed = 1;

                            $paid = 0;
                            if (isset($postCredit)) {
                                $inv->applyCredit();
                                $paid = 1;
                            }

                            $sent = 0;
                            if (isset($postEmail)) {
                                $inv->send();
                                $sent = 1;
                            }
                        }
                    }
                    $session->set('call_billed', $billed);
                    $session->set('call_paid', $paid);
                    $session->set('call_sent', $sent);

                    $db->query("INSERT INTO client_calls (`time`, `user`, `subject`, `content`, `endtime`, `billed`, `admin`) VALUES ($postTime, {$u->ID}, '$postSubject', '$postContent', $postEndtime, $billed, $postAdmin)");

                    alog("customers", "call_created", $id = $db->insert_id, $u->ID);

                    $path = __DIR__ . "/../../files/calls/" . $id;

                    if (!empty($_FILES['upload_files'])) {
                        if (!file_exists($path)) {
                            mkdir($path);
                        }

                        foreach ($_FILES["upload_files"]["name"] as $k => $name) {
                            $tmp_name = $_FILES["upload_files"]["tmp_name"][$k];
                            move_uploaded_file($tmp_name, $path . "/" . basename($name));
                        }
                    }

                    $session->set('call_added', '1');
                    header('Location: ?p=customers&edit=' . $u->ID . '&tab=telephone');
                    exit;
                } catch (Exception $ex) {
                    echo "<div class='alert alert-danger'>{$ex->getMessage()}</div>";
                }
            }

            ?>
	<form method="POST" onsubmit="window.onbeforeunload = null;" enctype="multipart/form-data">
	<?php if ($_GET['action'] == "new") {?><script type="text/javascript">
	window.onbeforeunload = function() {
   		return '<?=$l['REALLYLEAVECALL'];?>';
	};

	function validate(pin) {
		var xmlHttp = new XMLHttpRequest();
	    xmlHttp.open( "GET", "?p=customers&edit=<?=$u->ID;?>&telephone_pin=" + pin, false );
	    xmlHttp.send( null );

		if(xmlHttp.responseText == "true")
			lastColor = "green";
		else
			lastColor = "red";
		lastPIN = pin;
		document.getElementById('validity').style.color = lastColor;
	}
	</script>
	<input type="hidden" name="time" id="time" value="<?=isset($_POST['time']) ? $_POST['time'] : time();?>" />

	<div class="form-group">
		<label><?=$l['TELPIN'];?></label>
		<div class="input-group" style="max-width: 180px;">
			<input type="text" class="form-control" placeholder="123456" onkeydown="if(event.keyCode == 13){ javascript:validate(this.value); return false; }" onkeyup="if(this.value != lastPIN) { document.getElementById('validity').style.color = null; } else { document.getElementById('validity').style.color = lastColor; }" id="telephone_pin" maxlength="6">
		    <span class="input-group-btn">
		        <button class="btn btn-default" id="validity" onclick="javascript:validate(document.getElementById('telephone_pin').value);" type="button"><?=$l['CHECK'];?></button>
	        </span>
	    </div>
	    <p class="help-block"><?=$l['TELPINH'];?></p>
	</div><?php }?>

	<div class="form-group">
	    <label><?=$l['CALLSUB'];?></label>
	    <input type="text" class="form-control" placeholder="<?=$l['CALLSUBP'];?>" name="subject" maxlength="255" value="<?=isset($_POST['subject']) ? $_POST['subject'] : "";?>">
    </div>

    <div class="form-group">
	    <label><?=$l['CALLRES'];?></label>
	    <textarea class="form-control" style="width:100%; height:150px; resize:none;" placeholder="<?=$l['CALLRESP'];?>" name="content"><?=isset($_POST['content']) ? $_POST['content'] : "";?></textarea>
    </div>

    <div class="form-group">
	    <label><?=$l['STAFFMEMBER'];?></label>
	    <?php $selected = isset($_POST['admin']) ? $_POST['admin'] : $adminInfo->ID;?>
	    <select class="form-control" name="admin">
	    	<option value="0" disabled><?=$l['CPCSM'];?></option>
	    	<?php $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {?>
	    	<option value="<?=$row->ID;?>"<?php if ($selected == $row->ID) {
                echo " selected='selected'";
            }
                ?>><?=$row->name;?></option>
	    	<?php }?>
	    </select>
	</div>

	<div class="form-group">
		<label><?=$l['FILES'];?></label>
		<input type="file" class="form-control" name="upload_files[]" multiple>
	</div>

    <?php
if ($_GET['action'] == "import") {?>
    <div class="form-group">
        <label><?=$l['CALLTIM'];?> <?php if (count(unserialize($CFG['TELEPHONE_LOG'])) > 0) {?><a href="#" class="btn btn-default btn-xs" id="show_telephone_log"><?=$l['CALLLOG'];?></a><?php }?></label>
        <div class="row">
            <div class="col-xs-6">
                <div class="input-group" style="position: relative;">
                    <span class="input-group-addon"><?=$l['CALLSTART'];?></span>
                    <input type="text" class="form-control datetimepicker" name="time" value="<?=isset($_POST['time']) ? $_POST['time'] : "";?>" placeholder="<?=$dfo->format(time(), true, true, "");?>">
                </div>
            </div>

            <div class="col-xs-6">
                <div class="input-group" style="position: relative;">
                    <span class="input-group-addon"><?=$l['CALLEND'];?></span>
                    <input type="text" class="form-control datetimepicker" name="endtime" value="<?=isset($_POST['endtime']) ? $_POST['endtime'] : "";?>" placeholder="<?=$dfo->format(time(), true, true, "");?>">
                </div>
            </div>
        </div>
    </div>
    <?php } else {?>
    <div class="form-group">
        <label><?=$l['CALLTIM'];?></label><br />
        <div id="counter">0 <?=$l['CALSECONDS'];?></div>
    </div>
    <script type="text/javascript">
    starttime = document.getElementById("time").value;

    function counter(){
    	var diff = Math.round(+new Date()/1000) - starttime;
    	var formatted = "";

    	if(Math.floor(diff / 60) == 0){
            if(diff == 1) formatted = "1 <?=$l['CALSECOND'];?>";
            else formatted = diff + " <?=$l['CALSECONDS'];?>";
        } else if(Math.floor(diff / 3600) == 0) {
            if(Math.floor(diff / 60) == 1) formatted = "1 <?=$l['CALMINUTE'];?>";
            else formatted = Math.floor(diff / 60) + " <?=$l['CALMINUTES'];?>";

            var secs = diff % 60;
            if(secs == 1) formatted += ", 1 <?=$l['CALSECOND'];?>";
            else if(secs > 0) formatted += ", " + secs + " <?=$l['CALSECONDS'];?>";
        } else {
            if(Math.floor(diff / 3600) == 1) formatted = "1 <?=$l['CALHOUR'];?>";
            else formatted = Math.floor(diff / 3600) + " <?=$l['CALHOURS'];?>";

            var mins = Math.floor(diff % 3600 / 60);
            if(mins == 1) formatted += ", 1 <?=$l['CALMINUTE'];?>";
            else if(mins > 0) formatted += ", " + mins + " <?=$l['CALMINUTES'];?>";

            var secs = diff % 60;
            if(secs == 1) formatted += ", 1 <?=$l['CALSECOND'];?>";
            else if(secs > 0) formatted += ", " + secs + " <?=$l['CALSECONDS'];?>";
        }

		document.getElementById("counter").innerHTML = formatted;
	 	window.setTimeout('counter()', 1000);
	}

	counter();
    </script>
    <?php }?>

    <div class="checkbox">
    	<label>
    		<input type="checkbox" name="billed" value="1"<?=isset($_POST['billed']) ? " checked='checked'" : "";?> onchange="javascript:billing(this.checked);"> <?=$l['CALLBILLNOW'];?>
    	</label>
    </div>

    <script type="text/javascript">
    	function billing(display) {
    		if(display)
    			document.getElementById("billing").style.display = "block";
    		else
    			document.getElementById("billing").style.display = "none";
    	}
    </script>

    <div id="billing"<?=!isset($_POST['billed']) ? ' style="display:none;"' : '';?>>
    	<div class="form-group">
        <label><?=$l['CALLBILLTYPE'];?></label>
        <div class="row">
            <div class="col-xs-8">
                <select class="form-control" name="billing_type">
                	<option disabled<?=!isset($_POST['billing_type']) ? ' selected="selected"' : '';?>><?=$l['CPCSM'];?></option>
                	<option value="1"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "1" ? ' selected="selected"' : "";?>><?=$l['CBT1'];?></option>
                	<option value="2"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "2" ? ' selected="selected"' : "";?>><?=$l['CBT2'];?></option>
                	<option value="3"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "3" ? ' selected="selected"' : "";?>><?=$l['CBT3'];?></option>
                	<option value="4"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "4" ? ' selected="selected"' : "";?>><?=$l['CBT4'];?></option>
                	<option value="5"<?=isset($_POST['billing_type']) && $_POST['billing_type'] == "5" ? ' selected="selected"' : "";?>><?=$l['CBT5'];?></option>
                </select>
            </div>

            <div class="col-xs-4">
                <div class="input-group">
                	<?php $curObj = new Currency($cur->getBaseCurrency());?>
                    <?php if (!empty($curObj->getPrefix())) {?><span class="input-group-addon"><?=$curObj->getPrefix();?></span><?php }?>
                    <input type="text" class="form-control" name="billing_hour" value="<?=isset($_POST['billing_hour']) ? $_POST['billing_hour'] : "";?>" placeholder="<?=$nfo->placeholder();?>">
                    <?php if (!empty($curObj->getSuffix())) {?><span class="input-group-addon"><?=$curObj->getSuffix();?></span><?php }?>
                </div>
            </div>
        </div>

        <div class="checkbox">
	    	<label>
	    		<input type="checkbox" name="credit" value="1"<?=isset($_POST['credit']) ? " checked='checked'" : "";?>> <?=$l['PAYINVBYCREDIT'];?>
	    	</label>
	    </div>

	    <div class="checkbox">
	    	<label>
	    		<input type="checkbox" name="email" value="1"<?=!isset($_POST['add_call']) || isset($_POST['email']) ? " checked='checked'" : "";?>> <?=$l['SENDINVBYMAIL'];?>
	    	</label>
	    </div>
    </div>
    </div>

	<input type="submit" name="add_call" value="<?=$_GET['action'] == "new" ? $l['EXITCALL'] : $l['SAVECALL'];?>" class="btn btn-primary btn-block" />
	</form>
	<?php
} else {

            if (isset($_POST['delete_telephone'])) {
                $d = 0;
                if (is_array($_POST['telephone'])) {
                    foreach ($_POST['telephone'] as $id => $value) {
                        if ($db->query("DELETE FROM client_calls WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1") && $db->affected_rows > 0) {
                            $d++;
                            alog("customers", "call_deleted", $id, $u->ID);
                        }
                    }
                }

                if ($d == 1) {
                    echo "<div class=\"alert alert-success\">{$l['CALLDELO']}</div>";
                } else if ($d > 0) {
                    echo "<div class=\"alert alert-success\">" . str_replace("%d", $d, $l['CALLDELX']) . "</div>";
                }

            }

            if (isset($_POST['bill_telephone'])) {
                $d = 0;
                if (is_array($_POST['telephone'])) {
                    foreach ($_POST['telephone'] as $id => $value) {
                        if ($db->query("UPDATE client_calls SET billed = 1 WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($id) . "' AND billed = 0 LIMIT 1") && $db->affected_rows > 0) {
                            $d++;
                            alog("customers", "call_mark_billed", $id, $u->ID);
                        }
                    }
                }

                if ($d == 1) {
                    echo "<div class=\"alert alert-success\">{$l['CALLBILO']}</div>";
                } else if ($d > 0) {
                    echo "<div class=\"alert alert-success\">" . str_replace("%d", $d, $l['CALLBILX']) . "</div>";
                }

            }

            if (isset($_POST['unbill_telephone'])) {
                $d = 0;
                if (is_array($_POST['telephone'])) {
                    foreach ($_POST['telephone'] as $id => $value) {
                        if ($db->query("UPDATE client_calls SET billed = 0 WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($id) . "' AND billed = 1 LIMIT 1") && $db->affected_rows > 0) {
                            $d++;
                            alog("customers", "call_mark_unbilled", $id, $u->ID);
                        }
                    }
                }

                if ($d == 1) {
                    echo "<div class=\"alert alert-success\">{$l['CALLUNBILO']}</div>";
                } else if ($d > 0) {
                    echo "<div class=\"alert alert-success\">" . str_replace("%d", $d, $l['CALLUNBILX']) . "</div>";
                }

            }

            if ($session->get('call_added') == 1) {
                $billed = "";
                if ($session->get('call_billed') == 1) {
                    if ($session->get('call_paid') == 1) {
                        $billed = " " . $l['CALLBILL4'];
                    } else {
                        $billed = " " . $l['CALLBILL5'];
                    }

                    if ($session->get('call_sent') == 1) {
                        $billed .= " " . $l['CALLBILL3'];
                    }

                }
                echo "<div class=\"alert alert-success\">{$l['CALLADDED']}$billed</div>";
                $session->remove('call_billed');
                $session->remove('call_paid');
                $session->remove('call_sent');
            }

            $admins = [];
            $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                $admins[$row->ID] = $row->name;
            }

            $t = new Table("SELECT * FROM client_calls WHERE user = " . $u->ID, [
                "subject" => [
                    "name" => $l['THEMA'],
                    "type" => "like",
                ],
                "admin" => [
                    "name" => $l['STAFFMEMBER'],
                    "type" => "select",
                    "options" => $admins,
                ],
                "billed" => [
                    "name" => $l['BILLED'],
                    "type" => "select",
                    "options" => [
                        "0" => $l['NO'],
                        "1" => $l['YES'],
                    ],
                ],
            ], ["time", "DESC"]);

            echo $t->getHeader();
            $sql = $t->qry("time DESC");
            ?>

<form method="POST">
<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$t->orderHeader("time", $l['DATE']);?></th>
		<th><?=$t->orderHeader("subject", $l['THEMA']);?></th>
		<th><?=$t->orderHeader("admin", $l['STAFFMEMBER']);?></th>
		<th><?=$l['DURATION'];?></th>
		<th width="30px"></th>
	</tr>
	<?php
if ($sql->num_rows <= 0) {
                echo "<tr><td colspan=\"6\"><center>{$l['NOCALLS']}</center></td></tr>";
            } else {
                while ($d = $sql->fetch_object()) {
                    $adminSql = $db->query("SELECT name FROM admins WHERE ID = " . intval($d->admin));
                    if ($adminSql->num_rows == 1) {
                        $admin = $adminSql->fetch_object()->name;
                    }

                    ?>
			<tr>
				<td width="30px"><input type="checkbox" name="telephone[<?=$d->ID;?>]" class="checkbox" onchange="javascript:toggle();" value="true"></td>
				<td><?=$dfo->format($d->time, true, true);?></td>
				<td><?=$d->subject;?></td>
				<td><?=isset($admin) ? '<a href="?p=admin&id=' . $d->admin . '">' . $admin . '</a>' : "<i>{$l['NOTEXISTINGNOW']}</i>";?></td>
				<td><?=formatTime($d->endtime - $d->time, $d->billed);?></td>
				<td><a href="?p=customers&edit=<?=$u->ID;?>&tab=telephone&id=<?=$d->ID;?>"><i class="fa fa-edit"></i></a></td>
			</tr>
			<?php
}
            }
            ?>
</table></div>

<?php if ($sql->num_rows > 0) {?>
<?=$l['SELECTED'];?>: <input type="submit" name="bill_telephone" value="<?=$l['MARKBILLED'];?>" class="btn btn-success" /> <input type="submit" name="unbill_telephone" value="<?=$l['MARKUNBILLED'];?>" class="btn btn-warning" /> <input type="submit" name="delete_telephone" value="<?=$l['DELETE'];?>" class="btn btn-danger" onclick="return confirm('<?=$l['READELCALLS'];?>');" />
<?php }?></form>

</div><?php echo $t->getFooter();} ?>
<div class="modal fade" id="tlModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$l['LASTCALLS'];?></h4>
      </div>
      <div class="modal-body">
        <select id="tlProv" class="form-control">
        	<option value=""><?=$l['LCPCS'];?></option>
        	<?php
$h = new TelephoneLogHandler;
            foreach ($h->get() as $short => $obj) {
                if (!$obj->isActive()) {
                    continue;
                }

                echo '<option value="' . $short . '">' . $obj->getName() . '</option>';
            }
            ?>
        </select>

        <div id="tlDiv" class="table-responsive" style="display: none; margin-top: 15px;">
        	<table class="table table-bordered table-striped" id="tlTable" style="margin-bottom: 0;">
        		<tr>
        			<th width="200px"><?=$l['CALLSTART2'];?></th>
        			<th width="200px"><?=$l['CALLEND2'];?></th>
        			<th><?=$l['CALLDESC'];?></th>
        			<th width="150px"></th>
        		</tr>

        		<tr id="tlLoading">
        			<td colspan="4">
        				<center><i class="fa fa-spinner fa-spin"></i> <?=$l['DATABEINGLOADED'];?></center>
        			</td>
        		</tr>

        		<tr id="tlNone" style="display: none;">
        			<td colspan="4">
        				<center><?=$l['TANC'];?></center>
        			</td>
        		</tr>
        	</table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$("#show_telephone_log").click(function(e){
	e.preventDefault();
	$("#tlModal").modal("show");
});

$("#tlProv").change(function(){
	if($(this).val() == "") return;
	var prov = $(this).val();

	$("#tlDiv").show();
	$("#tlLoading").show();
	$(".tlBtn").unbind("click");
	$(".tlRow").remove();

	$.post("?p=ajax", {
		"action": "load_telephone_log",
		"provider": prov,
		"csrf_token": "<?=CSRF::raw();?>",
	}, function(r){
		r = JSON.parse(r);
		$("#tlLoading").hide();
		if(r.length == 0)
			$("#tlNone").show();

		r.forEach(function(i, x){
			$("#tlTable tr:last").after('<tr class="tlRow" id="tlRow' + x + '"><td>' + i.start + '</td><td>' + i.end + '</td><td>' + i.info + '</td><td><a href="#" class="tlBtn btn btn-xs btn-default" data-id="' + x + '"><?=$l['CALLTAKETIME'];?></a></td></tr>');
		});

		$(".tlBtn").bind("click", function(e){
			e.preventDefault();
			var td = $("#tlRow" + $(this).data("id")).find("td");
			$("[name=time]").val(td[0].innerHTML);
			$("[name=endtime]").val(td[1].innerHTML);
			$("#tlModal").modal("hide");
		});
	});
});
</script>
<?php } else if ($tab == "cookies") {?>
<div class="tab-pane" id="tab_cart">

<?php
if (isset($_POST['delete_cookies'])) {
            $deleted = 0;
            if (is_array($_POST['delete'])) {
                foreach ($_POST['delete'] as $id => $value) {
                    if ($db->query("DELETE FROM client_cookie WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1")) {
                        $deleted++;
                        alog("customers", "cookie_deleted", $id, $u->ID);
                    }
                }
            }

            if ($deleted <= 0) {
                echo "<div class=\"alert alert-warning\">{$l['COOKDEL0']}</div>";
            } else if ($deleted == 1) {
                echo "<div class=\"alert alert-success\">{$l['COOKDEL1']}</div>";
            } else {
                echo "<div class=\"alert alert-success\">" . str_replace("%d", $deleted, $l['COOKDELX']) . "</div>";
            }

        }
            ?>

<form method="POST">
<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<?php if ($ari->check(10)) {?><th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th><?php }?>
		<th><?=$l['COOKCREATED'];?></th>
		<th><?=$l['VALIDTO'];?></th>
		<th><?=$l['PASSWORD'];?></th>
		<?php if (trim($u->tfa) != "" && $u->tfa != "none") {?><th><?=$l['COOKTF'];?></th><?php }?>
	</tr>
	<?php
$sql = $db->query("SELECT * FROM client_cookie WHERE user = " . $u->ID . " AND valid >= " . time() . " ORDER BY valid DESC");

            if ($sql->num_rows <= 0) {
                echo "<tr><td colspan=\"5\"><center>{$l['COOKNT']}</center></td></tr>";
            } else {
                while ($d = $sql->fetch_object()) {
                    $ex = explode(":", $d->auth);
                    if (count($ex) == 1 || !$ex) {
                        $pw = $d->auth;
                        $tfa = "";
                    } else {
                        $pw = $ex[0];
                        $tfa = $ex[1];
                    }
                    ?>
			<tr>
				<?php if ($ari->check(10)) {?><td width="30px"><input type="checkbox" name="delete[<?=$d->ID;?>]" class="checkbox" onchange="javascript:toggle();" value="true"></td><?php }?>
				<td><?=$dfo->format($d->valid - 60 * 60 * 24 * 30);?></td>
				<td><?=$dfo->format($d->valid);?></td>
				<td><?=$pw == $u->pwd ? "<font color=\"green\">{$l['COOK1']}</font>" : "<font color=\"red\">{$l['COOK0']}</font>";?></td>
				<?php if (trim($u->tfa) != "" && $u->tfa != "none") {?><td><?=$tfa == $u->tfa ? "<font color=\"green\">{$l['COOK1']}</font>" : "<font color=\"red\">{$l['COOK0']}</font>";?></td><?php }?>
			</tr>
			<?php
}
            }
            ?>
</table></div>

<?php if ($sql->num_rows > 0) {?>
<?=$l['SELECTED'];?>: <input type="submit" name="delete_cookies" value="<?=$l['DELETE'];?>" class="btn btn-warning" onclick="return confirm('<?=$l['READELCOOK'];?>');" />
<?php }?></form>

</div><?php } else if ($tab == "tickets") {

            $depts = array();
            $sql2 = $db->query("SELECT ID, name FROM admins");
            while ($row = $sql2->fetch_object()) {
                $depts[$row->ID / -1] = $row->name;
            }

            $sql2 = $db->query("SELECT ID, name FROM support_departments");
            while ($row = $sql2->fetch_object()) {
                $depts[$row->ID] = $row->name;
            }

            $upgrades = [];
            $sql55 = $db->query("SELECT ID, name FROM support_upgrades ORDER BY name ASC");
            while ($row55 = $sql55->fetch_object()) {
                $upgrades[$row55->ID] = $row55->name;
            }

            $table = new Table("SELECT * FROM support_tickets WHERE customer = " . $u->ID, [
                "subject" => [
                    "name" => $l['SUBJECT'],
                    "type" => "like",
                ],
                "dept" => [
                    "name" => $l['DEPARTMENT'],
                    "type" => "select",
                    "options" => $depts,
                ],
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "0" => $lang['TICKET_CLASS']['S0'],
                        "1" => $lang['TICKET_CLASS']['S1'],
                        "2" => $lang['TICKET_CLASS']['S2'],
                        "3" => $lang['TICKET_CLASS']['S3'],
                    ],
                ],
                "priority" => [
                    "name" => $l['PRIORITY'],
                    "type" => "select",
                    "options" => [
                        "1" => $lang['TICKET_CLASS']['P1'],
                        "2" => $lang['TICKET_CLASS']['P2'],
                        "3" => $lang['TICKET_CLASS']['P3'],
                        "4" => $lang['TICKET_CLASS']['P4'],
                        "5" => $lang['TICKET_CLASS']['P5'],
                    ],
                ],
                "upgrade_id" => [
                    "name" => $lang['SUPPORT_TICKETS']['UPGRADE'],
                    "type" => "select",
                    "options" => $upgrades,
                ],
            ], ["created", "DESC"]);

            echo $table->getHeader();
            $sql = $table->qry("created DESC");
            ?>
<div class="tab-pane" id="tab_tickets">
<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="10%">#</th>
		<th width="12%"><?=$table->orderHeader("created", $l['DATE']);?></th>
		<th><?=$table->orderHeader("subject", $l['SUBJECT']);?></th>
		<th width="12%"><?=$table->orderHeader("dept", $l['DEPARTMENT']);?></th>
		<th width="12%"><?=$table->orderHeader("status", $l['STATUS']);?></th>
		<th width="14%"><?=$table->orderHeader("priority", $l['PRIORITY']);?></th>
		<th width="15%"><?=$l['LASTANSWER'];?></th>
	</tr>
	<?php
$my_depts = array($adminInfo->ID / -1);
            $sql2 = $db->query("SELECT dept FROM support_department_staff WHERE staff = " . intval($adminInfo->ID));
            while ($row = $sql2->fetch_object()) {
                $ds = $db->query("SELECT name, ID FROM support_departments WHERE ID = " . $row->dept);
                while ($sd = $ds->fetch_object()) {
                    array_push($my_depts, $sd->ID);
                }

            }

            if ($sql->num_rows <= 0) {
                echo "<tr><td colspan=\"7\"><center>{$l['NOTICKETS']}</center></td></tr>";
            } else {
                while ($r = $sql->fetch_object()) {
                    $t = new Ticket($r->ID);
                    ?>
			<tr>
				<td>T#<?=str_pad($r->ID, 6, "0", STR_PAD_LEFT);?></td>
				<td><?=$dfo->format($r->created);?></td>
				<td><?php if (in_array($r->dept, $my_depts) || $ari->check(61)) {?><a href="?p=support_ticket&id=<?=$r->ID;?>"><?php }?><?=$t->html();?><?php if (in_array($r->dept, $my_depts) || $ari->check(61)) {?></a><?php }?></td>
				<td><?=$depts[$r->dept] ?: "<i>{$l['NOTEXISTINGNOW']}</i>";?></td>
				<td><?=$t->getStatusStr();?></td>
				<td><?=$t->getPriorityStr();?></td>
				<td><?=$t->getLastAnswerStr();?></td>
			</tr>
			<?php
}
            }
            ?>
</table>
</div>
</div>
<?php echo $table->getFooter();} else if ($ari->check(47) && $tab == "mails") { ?>
<div class="tab-pane" id="tab_mails">
<?php
if ($session->get("mail_sent")) {
            $session->remove("mail_sent");
            echo "<div class='alert alert-success'>{$l['MAILSENT']}</div>";
        }

            if (isset($_GET['delete_email'])) {
                $item = abs(intval($_GET['delete_email']));
                $sql = $db->query("DELETE FROM client_mails WHERE ID = '$item' AND user = " . $u->ID . " LIMIT 1");

                if ($db->affected_rows >= 1) {
                    echo "<div class='alert alert-success'>{$l['MAILDELETED']}</div>";
                    alog("email", "deleted", $item);
                }
            }

            if (isset($_GET['resend_email'])) {
                $item = abs(intval($_GET['resend_email']));
                if ($maq->resend($item)) {
                    echo "<div class='alert alert-success'>{$l['MAILREQUEUED']}</div>";
                    alog("email", "resent", $item);
                }
            }

            if (isset($_POST['resend_selected_emails']) && is_array($_POST['email'])) {
                $d = 0;
                foreach ($_POST['email'] as $id) {
                    $item = abs(intval($id));
                    if ($maq->resend($item)) {
                        $d++;
                        alog("email", "resent", $item);
                    }
                }

                if ($d == 1) {
                    echo "<div class='alert alert-success'>{$l['MAILREQUEUED1']}</div>";
                } else if ($d > 0) {
                    echo "<div class='alert alert-success'>" . str_replace("%d", $d, $l['MAILREQUEUEDX']) . "</div>";
                }

            }

            if (isset($_POST['delete_selected_emails']) && is_array($_POST['email'])) {
                $d = 0;
                foreach ($_POST['email'] as $id) {
                    $item = abs(intval($id));
                    $sql = $db->query("DELETE FROM client_mails WHERE ID = '$item' AND user = " . $u->ID . " LIMIT 1");
                    if ($db->affected_rows >= 1) {
                        $d++;
                        alog("email", "deleted", $item);
                    }
                }

                if ($d == 1) {
                    echo "<div class='alert alert-success'>{$l['MAILDELETED1']}.</div>";
                } else if ($d > 0) {
                    echo "<div class='alert alert-success'>" . str_replace("%d", $d, $l['MAILDELETEDX']) . "</div>";
                }

            }

            $t = new Table("SELECT * FROM client_mails WHERE user = " . $u->ID, [
                "subject" => [
                    "name" => $l['SUBJECT'],
                    "type" => "like",
                ],
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "0" => $l['MS0'],
                    ],
                ],
            ], ["time", "DESC"]);

            echo $t->getHeader();
            ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$t->orderHeader("time", $l['DATE']);?></th>
		<th><?=$t->orderHeader("subject", $l['SUBJECT']);?></th>
		<th><?=$t->orderHeader("sent", $l['STATUS']);?></th>
        <th width="48px"></td>
	</tr>
	<form method="POST"><?php
$sql = $t->qry("time DESC");
            if ($sql->num_rows <= 0) {
                echo "<tr><td colspan=\"6\"><center>{$l['NOMAILSYET']}</center></td></tr>";
            } else {
                while ($r = $sql->fetch_object()) {
                    $sent = $r->sent > 1 ? "(" . $dfo->format($r->sent, true, true) . ")" : "";
                    ?>
			<tr>
				<td><input type="checkbox" class="checkbox" name="email[]" value="<?=$r->ID;?>" onchange="javascript:toggle();" /></td>
				<td><?=$r->time > time() ? '<i class="fa fa-clock-o"></i>' : '';?> <?=$dfo->format($r->time);?></td>
				<td><a href="<?=$raw_cfg['PAGEURL'];?>email/<?=$r->ID;?>/<?=substr(hash("sha512", "email_view" . $r->ID . $CFG['HASH']), 0, 10);?>" target="_blank"><?=htmlentities($r->subject);?></a> <?php if ($r->resend == 1) {?><i class="fa fa-undo"></i> <?php }?><?php if ($r->seen == 1) {?><i class="fa fa-eye"></i><?php }?></td>
				<td><?=$r->sent != "0" ? "<font color=\"green\">{$l['MAILSENT2']}</font> $sent" : "<font color=\"red\">{$l['MAILNOTSENT']}</font>";?></td>
                <td width="30px"><a href="#" onclick="resendE(<?=$r->ID;?>); return false;"><i class="fa fa-undo"></i></a>&nbsp;<a href="#" onclick="deleteE(<?=$r->ID;?>); return false;"><i class="fa fa-times fa-lg"></i></a></td>
			</tr>
			<?php
}

                $additionalJS .= "function resendE(id) {
			swal({
				title: '" . $l['EMAILAGAIN'] . "',
				text: '" . $l['AREYOUSURE'] . "',
				type: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#DD6B55',
				confirmButtonText: '" . $l['YES'] . "',
				cancelButtonText: '" . $l['NO'] . "',
				closeOnConfirm: false
			}, function(){
				window.location = '?p=customers&edit={$u->ID}&tab=$tab&resend_email=' + id;
			});
		}";

                $additionalJS .= "function deleteE(id) {
			swal({
				title: '" . $l['DELMAIL'] . "',
				text: '" . $l['AREYOUSURE'] . "',
				type: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#DD6B55',
				confirmButtonText: '" . $l['YES'] . "',
				cancelButtonText: '" . $l['NO'] . "',
				closeOnConfirm: false
			}, function(){
				window.location = '?p=customers&edit={$u->ID}&tab=$tab&delete_email=' + id;
			});
		}";
            }
            ?>
</table>
</div>
<?=$l['SELECTED'];?>: <input type="submit" name="resend_selected_emails" class="btn btn-warning" value="<?=$l['MAILRESEND'];?>" /> <input type="submit" name="delete_selected_emails" class="btn btn-danger" value="<?=$l['DELETE'];?>" />
</form>
</div>
<?php echo $t->getFooter();} else if ($ari->check(7) && $tab == "quotes") {

            if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
                $d = 0;

                if (isset($_POST['status']) && in_array($_POST['status'], array("0", "1", "2", "3"))) {
                    $newStatus = intval($_POST['status']);
                    foreach ($_POST['invoices'] as $id) {
                        $db->query("UPDATE client_quotes SET status = $newStatus WHERE ID = " . intval($id));
                        if ($db->affected_rows) {
                            $d++;
                            alog("quote", "status_changed", $id, $newStatus);
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['QUOTEST1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['QUOTESTX']);
                    }

                } else if (isset($_POST['delete'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $db->query("DELETE FROM client_quotes WHERE ID = " . intval($id));
                        if ($db->affected_rows) {
                            $d++;
                            alog("quote", "delete", $id);
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['QUOTEDELE1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['QUOTEDELEX']);
                    }

                } else if (isset($_POST['send_letter'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $pdf = new PDFQuote($id);
                        if (!$pdf->wasFound()) {
                            continue;
                        }

                        if (file_exists(__DIR__ . "/tmp.pdf")) {
                            unlink(__DIR__ . "/tmp.pdf");
                        }

                        $pdf->output(__DIR__ . "/tmp.pdf");

                        $ex = explode("#", $_POST['send_letter'], 2);

                        if (LetterHandler::myDrivers()[$ex[0]]->sendLetter(__DIR__ . "/tmp.pdf", true, $pdf->getCountry(), $ex[1]) === true) {
                            $d++;
                            $db->query("UPDATE client_quotes SET status = 1 WHERE status = 0 AND ID = " . intval($id));
                            alog("quote", "sent_letter", $id, $_POST['send_letter']);
                        }

                        if (file_exists(__DIR__ . "/tmp.pdf")) {
                            unlink(__DIR__ . "/tmp.pdf");
                        }

                    }

                    if ($d == 1) {
                        $msg = $l['QUOTELETTER1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['QUOTELETTERX']);
                    }

                } else if (isset($_POST['send_mail'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $pdf = new PDFQuote($id);
                        if (!$pdf->wasFound()) {
                            continue;
                        }

                        if (!$pdf->getMail() || !$pdf->getName()) {
                            continue;
                        }

                        if (file_exists(__DIR__ . "/tmp.pdf")) {
                            unlink(__DIR__ . "/tmp.pdf");
                        }

                        $pdf->output(__DIR__ . "/tmp.pdf");

                        $mt = new MailTemplate("Ihr Angebot");
                        $title = $mt->getTitle($pdf->getLanguage());
                        $mail = $mt->getMail($pdf->getLanguage(), $pdf->getName());

                        $nr = $id;
                        while (strlen($nr) < 6) {
                            $nr = "0" . $nr;
                        }

                        $prefix = $CFG['OFFER_PREFIX'];
                        $date = strtotime($pdf->getDate());
                        $prefix = str_replace("{YEAR}", date("Y", $date), $prefix);
                        $prefix = str_replace("{MONTH}", date("m", $date), $prefix);
                        $prefix = str_replace("{DAY}", date("d", $date), $prefix);

                        $nr = $prefix . $nr;

                        $id = $maq->enqueue([
                            "nr" => $nr,
                            "amount" => $cur->infix($nfo->format($pdf->getSum()), $cur->getBaseCurrency()),
                            "valid" => $dfo->format(strtotime($pdf->getValid()), "", false, false),
                        ], $mt, $pdf->getMail(), $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $u->ID, false, 0, 0, array($nr . ".pdf" => __DIR__ . "/tmp.pdf"));
                        $maq->send(1, $id, true, false);
                        $maq->delete($id);

                        $d++;
                        alog("quote", "sent_mail", $id);
                        $db->query("UPDATE client_quotes SET status = 1 WHERE status = 0 AND ID = " . intval($id));

                        if (file_exists(__DIR__ . "/tmp.pdf")) {
                            unlink(__DIR__ . "/tmp.pdf");
                        }

                    }

                    if ($d == 1) {
                        $msg = $l['QUOTEMAI1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['QUOTEMAIX']);
                    }

                } else if (isset($_POST['invoice'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $pdf = new PDFQuote($id);
                        if (!$pdf->wasFound()) {
                            continue;
                        }

                        if (($cid = $pdf->getClient()) <= 0) {
                            continue;
                        }

                        $items = $pdf->getItems();

                        $stages = [];
                        $stageSql = $db->query("SELECT * FROM client_quote_stages WHERE quote = " . intval($id) . " ORDER BY days ASC");

                        if ($stageSql->num_rows == 0) {
                            $stages = [
                                [$CFG['INVOICE_DUEDATE'], 100],
                            ];
                        } else {
                            while ($stageRow = $stageSql->fetch_object()) {
                                $stages[] = [$stageRow->days, $stageRow->percent];
                            }
                        }

                        foreach ($stages as $stage) {
                            $inv = new Invoice;
                            $inv->setClient($cid);
                            $inv->setDate(date("Y-m-d"));
                            $inv->setDueDate(date("Y-m-d", strtotime("+" . $stage[0] . " days")));

                            $user = User::getInstance($cid, "ID");

                            foreach ($items as $i) {
                                if (!$pdf->getVat()) {
                                    $i[2] = $user->addTax($i[2]);
                                }

                                $item = new InvoiceItem;
                                $item->setDescription($i[0]);
                                $item->setAmount(round($i[2] * $stage[1] / 100, 2));
                                $item->save();
                                $inv->addItem($item);
                            }

                            $inv->save();
                        }

                        $d++;
                        $db->query("UPDATE client_quotes SET status = 2 WHERE ID = " . intval($id));
                        alog("quote", "invoice", $id);
                    }

                    if ($d == 1) {
                        $msg = $l['QUOTEINV1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['QUOTEINVX']);
                    }

                }
            }

            if (isset($msg)) {
                echo '<div class="alert alert-success">' . $msg . '</div>';
            }

            $t = new Table("SELECT * FROM client_quotes WHERE client = " . $u->ID, [
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "0" => $l['QUOTESTAT1'],
                        "1" => $l['QUOTESTAT2'],
                    ],
                ],
            ], ["date", "DESC"]);

            echo $t->getHeader();
            ?>
<div class="tab-pane" id="tab_send_mail">
<form method="POST" id="invoice_form">
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
				<th><?=$t->orderHeader("date", $l['DATE']);?></th>
				<th><?=$t->orderHeader("valid", $l['VALIDTO']);?></th>
				<th><?=$l['SUM'];?></th>
				<th><?=$t->orderHeader("status", $l['STATUS']);?></th>
				<th width="30px"></th>
				<th width="30px"></th>
			</tr>

			<?php
$sql = $t->qry("`date` DESC, `valid` DESC, `ID` DESC");
            while ($row = $sql->fetch_object()) {
                $sum = 0;
                $items = unserialize($row->items);
                foreach ($items as $i) {
                    $sum += $i[2];
                }

                $sum = $cur->infix($nfo->format($sum), $cur->getBaseCurrency());
                ?>
			<tr>
				<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$row->ID;?>" /></td>
				<td><?=$dfo->format($row->date, false);?></td>
				<td><?=$dfo->format($row->valid, false);?></td>
				<td><?=$sum;?> <?=$row->vat ? $lang['QUOTES']['GROSS'] : $lang['QUOTES']['NET'];?></td>
				<td><?php if ($row->status == 2) {?><font color="green"><?=$l['QS1'];?></font><?php } else if ($row->status == 3) {?><?=$l['QS2'];?><?php } else if ($row->valid < date("Y-m-d")) {?><font color="red"><?=$l['QS3'];?></font><?php } else if ($row->status == 0) {?><font color="orange"><?=$l['QS4'];?></font><?php } else if ($row->status == 1) {?><font color="orange"><?=$l['QS5'];?></font><?php }?></td>
				<td><a href="?p=quotes&id=<?=$row->ID;?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
				<td><a href="?p=quote&id=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
			</tr>
			<?php }if ($sql->num_rows == 0) {?>
			<tr><td colspan="8"><center><?=$l['NOQUOTES'];?></center></td></tr>
			<?php }?>
		</table>
	</div>

	<?=$l['SELECTED'];?>: <div class="btn-group">
	  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	    <?=$l['CHANGESTATUS'];?> <span class="caret"></span>
	  </button>
	  <ul class="dropdown-menu">
	  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['QS4'];?></a></li>
	  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['QS5'];?></a></li>
	  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '2' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['QS1'];?></a></li>
	  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '3' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['QS2'];?></a></li>
	  </ul>
	</div>

	<input type="submit" name="invoice" class="btn btn-success" value="<?=$l['INVQUOTE'];?>">

	<div class="btn-group">
	  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	    <?=$l['SENDQUOTE'];?> <span class="caret"></span>
	  </button>
	  <ul class="dropdown-menu">
	  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_mail', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['SENDVIAMAIL'];?></a></li>
	  	<?php foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {?>
        <li role="separator" class="divider"></li>
        <li class="dropdown-header"><?=$drivObj->getName();?></li>
        <?php foreach ($drivObj->getTypes() as $code => $name) {?>
        <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_letter', value: '<?=$drivKey;?>#<?=$code;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$name;?></a></li>
        <?php }}?>
	  </ul>
	</div>

	<input type="submit" name="delete" class="btn btn-danger" value="<?=$l['DELETE'];?>">
</form>
</div><br />
<?php echo $t->getFooter();} else if ($ari->check(7) && $tab == "letters") {
            if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
                $d = 0;

                if (isset($_POST['mark_sent'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $db->query("UPDATE client_letters SET sent = 1 WHERE ID = " . intval($id));
                        if ($db->affected_rows) {
                            $d++;
                            alog("letter", "mark_sent", $id);
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['LETMS1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['LETMSX']);
                    }

                } else if (isset($_POST['mark_unsent'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $db->query("UPDATE client_letters SET sent = 0 WHERE ID = " . intval($id));
                        if ($db->affected_rows) {
                            $d++;
                            alog("letter", "mark_unsent", $id);
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['LETMW1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['LETMWX']);
                    }

                } else if (isset($_POST['delete'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $db->query("DELETE FROM client_letters WHERE ID = " . intval($id));
                        if ($db->affected_rows) {
                            $d++;
                            alog("letter", "delete", $id);
                        }
                    }

                    if ($d == 1) {
                        $msg = $l['LETDEL1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['LETDELX']);
                    }

                } else if (isset($_POST['send_letter'])) {
                    foreach ($_POST['invoices'] as $id) {
                        $pdf = new PDFLetter($id);
                        if (!$pdf->wasFound()) {
                            continue;
                        }

                        if (file_exists(__DIR__ . "/tmp.pdf")) {
                            unlink(__DIR__ . "/tmp.pdf");
                        }

                        $pdf->output(__DIR__ . "/tmp.pdf");

                        $ex = explode("#", $_POST['send_letter'], 2);

                        if (LetterHandler::myDrivers()[$ex[0]]->sendLetter(__DIR__ . "/tmp.pdf", true, $pdf->getCountry(), $ex[1]) === true) {
                            $d++;
                            $db->query("UPDATE client_letters SET sent = 1 WHERE ID = " . intval($id));
                            alog("letter", "sent_to_provider", $id, $_POST['send_letter']);
                        }

                        if (file_exists(__DIR__ . "/tmp.pdf")) {
                            unlink(__DIR__ . "/tmp.pdf");
                        }

                    }

                    if ($d == 1) {
                        $msg = $l['LETLET1'];
                    } else if ($d > 0) {
                        $msg = str_replace("%d", $d, $l['LETLETX']);
                    }

                }
            }

            if (isset($msg)) {
                echo "<div class='alert alert-success'>$msg</div>";
            }

            $t = new Table("SELECT * FROM client_letters WHERE client = " . $u->ID, [
                "subject" => [
                    "name" => $l['SUBJECT'],
                    "type" => "like",
                ],
                "sent" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "0" => $l['LS0'],
                        "1" => $l['LS1'],
                    ],
                ],
            ], ["date", "DESC"]);

            echo $t->getHeader();
            $sql = $t->qry("date DESC, ID DESC");
            ?>
<div class="tab-pane" id="tab_send_mail">
<form method="POST" id="invoice_form"><div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
			<th><?=$t->orderHeader("date", $l['DATE']);?></th>
			<th><?=$t->orderHeader("subject", $l['SUBJECT']);?></th>
			<th><?=$t->orderHeader("sent", $l['STATUS']);?></th>
			<th width="30px"></th>
		</tr>

		<?php
while ($row = $sql->fetch_object()) {
                ?>
		<tr>
			<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$row->ID;?>" /></td>
			<td><?=$dfo->format($row->date, false);?></td>
			<td><?=$row->subject;?> <a href="?p=letters&id=<?=$row->ID;?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
			<td><?php if ($row->sent) {?><font color="green"><?=$l['LS1'];?></font><?php } else {?><font color="orange"><?=$l['LS0'];?></font><?php }?></td>
			<td><a href="?p=letter&id=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
		</tr>
		<?php }if ($sql->num_rows == 0) {?>
		<tr><td colspan="4"><center><?=$l['NOLETTERS'];?></center></td></tr>
		<?php }?>
	</table>
</div>

<?=$l['SELECTED'];?>: <div class="btn-group">
<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
<?=$l['CHANGESTATUS'];?> <span class="caret"></span>
</button>
<ul class="dropdown-menu">
<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_unsent', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['LS0'];?></a></li>
<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_sent', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['LS1'];?></a></li>
</ul>
</div>

<?php if (LetterHandler::myDrivers()) {?>
<div class="btn-group">
<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
<?=$l['SENDQUOTE'];?> <span class="caret"></span>
</button>
<ul class="dropdown-menu">
<?php $i = 0;foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {?>
<?php if ($i++ != 0) {?><li role="separator" class="divider"></li><?php }?>
<li class="dropdown-header"><?=$drivObj->getName();?></li>
<?php foreach ($drivObj->getTypes() as $code => $name) {?>
<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_letter', value: '<?=$drivKey;?>#<?=$code;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$name;?></a></li>
<?php }}?>
</ul>
</div>
<?php }?>

<input type="submit" name="delete" class="btn btn-danger" value="<?=$l['DELETE'];?>">
</form>
</div>
<?php echo $t->getFooter();} else if ($ari->check(68) && $tab == "abuse") {
            $t = new Table("SELECT * FROM abuse WHERE user = " . $u->ID, [
                "subject" => [
                    "name" => $lang['ABUSE']['SUBJECT'],
                    "type" => "like",
                ],
                "status" => [
                    "name" => $lang['ABUSE']['STATUS'],
                    "type" => "select",
                    "options" => [
                        "open" => $lang['ABUSE']['OPEN'],
                        "resolved" => $lang['ABUSE']['RESOLVED'],
                    ],
                ],
            ]);

            echo $t->getHeader();
            $sql = $t->qry("status = 'open' DESC, `deadline` DESC, `time` DESC, `ID` DESC");
            ?>
<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="200px"><?=$lang['ABUSE']['TIME'];?></th>
					<th width="200px"><?=$lang['ABUSE']['DEADLINE'];?></th>
					<th><?=$lang['ABUSE']['SUBJECT'];?></th>
					<th width="150px"><?=$lang['ABUSE']['STATUS'];?></th>
				</tr>

				<?php while ($row = $sql->fetch_object()) {?>
				<tr>
					<td><?=$dfo->format($row->time);?></td>
                <td><?=$dfo->format($row->deadline);?><?php if ($row->status == "open" && time() >= strtotime($row->deadline)) {?> <i class="fa fa-exclamation-triangle" style="color: red;"></i><?php }?></td>
					<td><a href="?p=abuse&id=<?=$row->ID;?>"><?=htmlentities($row->subject);?></a></td>
					<td>
                        <?php if ($row->status == "open") {?>
                        <span class="label label-warning"><?=$lang['ABUSE']['OPEN'];?></span>
                        <?php } else {?>
                        <span class="label label-success"><?=$lang['ABUSE']['RESOLVED'];?></span>
                        <?php }?>
                    </td>
				</tr>
                <?php }if (!$sql->num_rows) {?>
				<tr>
					<td colspan="4"><center><?=$lang['ABUSE']['NOTHING'];?></center></td>
				</tr>
                <?php }?>
			</table>
		</div>
<?php echo $t->getFooter();} else if ($ari->check(47) && $tab == "send_mail") { ?>
<div class="tab-pane" id="tab_send_mail">
<?php
if (isset($_POST['title']) && isset($_POST['text'])) {
            try {
                if (empty($_POST['sender_name'])) {
                    throw new Exception($l['SMERR1']);
                }

                if (empty($_POST['sender_email']) || !$val->email($_POST['sender_email'])) {
                    throw new Exception($l['SMERR2']);
                }

                if (empty($_POST['title'])) {
                    throw new Exception($l['SMERR3']);
                }

                if (empty($_POST['text'])) {
                    throw new Exception($l['SMERR4']);
                }

                $attachments = array();
                if (is_array($_FILES['files'])) {
                    foreach ($_FILES['files']['tmp_name'] as $k => $file) {
                        $fileName = $_FILES['files']['name'][$k];
                        move_uploaded_file($file, __DIR__ . "/" . $fileName);
                        array_push($attachments, __DIR__ . "/" . $fileName);
                    }
                }

                $maq->enqueue([], null, $u->mail, $_POST['title'], $_POST['text'], "From: {$_POST['sender_name']} <{$_POST['sender_email']}>", $u->ID, true, 0, 0, $attachments);
                $session->set("mail_sent", "1");

                foreach ($attachments as $attachment) {
                    if (is_file($attachment) && file_exists($attachment)) {
                        unlink($attachment);
                    }
                }

                alog("customers", "send_email", $u->ID);

                header("Location: ?p=customers&edit=" . $u->ID . "&tab=mails");
                exit;
            } catch (Exception $ex) {
                echo "<div class='alert alert-danger'>{$ex->getMessage()}</div>";
            }
        }

            $headerTemplate = new MailTemplate("Header");
            $footerTemplate = new MailTemplate("Footer");

            $lang_terms = str_replace("%name%", $u->firstname . " " . $u->lastname, $headerTemplate->getContent($u->language ?: $CFG['LANG']));
            $lang_terms .= "\n\n\n\n";
            $lang_terms .= $footerTemplate->getContent($u->language ?: $CFG['LANG']);
            $lang_terms = str_replace("%salutation%", $uI->getSalutation(), $lang_terms);
            ?>
<form method="POST" enctype="multipart/form-data">
<div class="row">
<div class="col-sm-6">
<input type="text" name="sender_name" value="<?=isset($_POST['sender_name']) ? $_POST['sender_name'] : $CFG['PAGENAME'];?>" placeholder="<?=$l['SENDERNAME'];?>" class="form-control"><br />
</div>
<div class="col-sm-6">
<input type="text" name="sender_email" value="<?=isset($_POST['sender_email']) ? $_POST['sender_email'] : $CFG['MAIL_SENDER'];?>" placeholder="<?=$l['SENDERMAIL'];?>" class="form-control"><br />
</div>
</div>
<input type="text" name="title" value="<?=isset($_POST['title']) ? $_POST['title'] : "";?>" id="title" placeholder="<?=$l['SUBJECT'];?>" class="form-control"><br />
<textarea class="form-control" id="text" style="width:100%;height:450px;resize:none;" name="text"><?=isset($_POST['text']) ? $_POST['text'] : ($lang_terms);?></textarea><br />
<input type="file" name="files[]" multiple="multiple" class="form-control"><br />
<div class="row">
<div class="col-xs-6"><button type="button" class="btn btn-default btn-block" onclick="window.open('<?=$raw_cfg['PAGEURL'];?>email?subject=' + $('#title').val() + '&text=' + encodeURIComponent($('#text').val()), null);"><?=$l['PREVIEW'];?></button></div>
<div class="col-xs-6"><button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['SENDMAILNOW'];?></button></div>
</div>
</form>
</div>
<?php } else if ($ari->check(13) && $tab == "domains") {?>
<div class="tab-pane" id="tab2">

<?php
$registrars = [];
            foreach (DomainHandler::getRegistrars() as $short => $obj) {
                if ($obj->isActive()) {
                    $registrars[$short] = $obj->getName();
                }
            }

            $t = new Table("SELECT * FROM domains WHERE user = '" . $u->ID . "'", [
                "domain" => [
                    "name" => $l['DOMAIN'],
                    "type" => "like",
                ],
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => $lang['DOMAIN_STATUS'],
                ],
                "registrar" => [
                    "name" => $l['REGISTRAR'],
                    "type" => "select",
                    "options" => $registrars,
                ],
            ]);

            echo $t->getHeader();
            ?>
<form method="POST">
<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
        <th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$l['DOMAIN'];?></th>
		<th><?=$l['REGISTRATION'];?></th>
		<th><?=$l['EXPIRATION'];?></th>
		<th><?=$l['STATUS'];?></th>
		<th><?=$l['PRICE'];?></th>
		<th width="30px"></th>
	</tr>
	<?php
$s = array(
                "REG_WAITING" => array("orange", ""),
                "KK_WAITING" => array("orange", ""),
                "REG_OK" => array("limegreen", ""),
                "KK_OK" => array("limegreen", ""),
                "KK_OUT" => array("", ""),
                "EXPIRED" => array("", ""),
                "DELETED" => array("", ""),
                "TRANSIT" => array("", ""),
                "KK_ERROR" => array("red", ""),
                "REG_ERROR" => array("red", ""),
            );
            foreach ($s as $k => &$v) {
                $v[1] = $lang['DOMAIN_STATUS'][$k];
            }
            unset($v);

            $sql = $t->qry("status = 'REG_ERROR' OR status = 'KK_ERROR' DESC, status = 'REG_WAITING' OR status = 'KK_WAITING' DESC, status = 'REG_OK' OR status = 'KK_OK' DESC, domain ASC");
            if (!$sql->num_rows) {
                echo "<tr><td colspan=\"7\"><center>{$l['NODOMAINS']}</center></td></tr>";
            } else {
                while ($row = $sql->fetch_object()) {?>
	<tr>
        <td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="domains[]" value="<?=$row->ID;?>" /></td>
		<td><?=$row->domain;?><?php if ($row->privacy) {?> <i class="fa fa-shield"></i><?php }?></td>
		<td><?=date("d.m.Y", strtotime($row->created));?></td>
		<td><?=date("d.m.Y", strtotime($row->expiration));?></td>
		<td><font color="<?=$s[$row->status][0];?>"><?=$row->payment ? $lang['HOSTING']['WAIT_PAY'] : $s[$row->status][1];?></font></td>
        <td>
                <?php if (!empty($row->inclusive_id)) {?><span class="label label-<?=$db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1 ? 'success' : 'warning';?>"><?=$l['INCLDOMAIN'];?></span><?php } else {?><?=$cur->infix($nfo->format($row->recurring), $cur->getBaseCurrency());?><?php if (!empty($row->addon_id)) {?> <span class="label label-primary"><?=$l['ADDONDOMAIN'];?></span><?php }}?></td>
		<td><a href="?p=domain&d=<?=$row->domain;?>&u=<?=$u->ID;?>"><i class="fa fa-arrow-circle-o-right"></i></a></td>
	</tr>
	<?php }}?>
</table>
</div>
</div>

<?=$l['SELECTED'];?>: <input type="submit" name="delete_selected_domains" value="<?=$l['DELETE'];?>" class="btn btn-danger" />
</form>

<?php echo $t->getFooter();} else if ($ari->check(13) && $tab == "products") { ?>
<div class="tab-pane" id="tab2">

<?php
$products = [];
            $sql = $db->query("SELECT `ID`, `name` FROM products");
            while ($row = $sql->fetch_object()) {
                $name = @unserialize($row->name);
                $products[$row->ID] = $name ? $name[$CFG['LANG']] : $row->name;
            }
            asort($products);

            $t = new Table("SELECT * FROM client_products WHERE user = {$u->ID}", [
                "product" => [
                    "name" => $l['PRODUCT'],
                    "type" => "select",
                    "options" => $products,
                ],
                "active" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "-1" => $l['PS0'],
                        "0" => $l['PS1'],
                        "1" => $l['PS2'],
                    ],
                ],
            ], ["date", "DESC"]);

            echo $t->getHeader();
            ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th width="28px"></th>
		<th><?=$t->orderHeader("date", $l['CONTRACTDATE']);?></th>
		<th><?=$t->orderHeader("product", $l['PRODUCT']);?></th>
		<th><?=$t->orderHeader("price", $l['PRICE']);?></th>
		<th width="56px"></th>
	</tr>

	<form method="POST">
	<?php
$sql = $t->qry("`date` DESC, `ID` DESC");

            if ($sql->num_rows == 0) {
                echo "<tr><td colspan=\"6\"><center>{$l['NOPRODUCTS']}</center></td></tr>";
            }

            $sl = array(
                "-2" => "default",
                "-1" => "warning",
                "0" => "danger",
                "1" => "success",
            );

            $st = array(
                "-2" => $l['PS3'],
                "-1" => $l['PS0'],
                "0" => $l['PS1'],
                "1" => $l['PS2'],
            );

            $billing = array(
                "onetime" => "",
                "monthly" => $l['PSBM'],
                "quarterly" => $l['PSBQ'],
                "semiannually" => $l['PSBS'],
                "annually" => $l['PSBA'],
                "biennially" => $l['PSBB'],
                "trinnially" => $l['PSBT'],
                "minutely" => $l['PSBMI'],
                "hourly" => $l['PSBHO'],
            );

            while ($l2 = $sql->fetch_array()) {
                $i = $l2['ID'];
                if (!empty($l2['name'])) {
                    $name = $l2['name'];
                } else {
                    $sql2 = $db->query("SELECT name FROM products WHERE ID = {$l2['product']}");
                    if ($sql2->num_rows == 0) {
                        continue;
                    }

                    $name = unserialize($sql2->fetch_object()->name)[$CFG['LANG']];
                }

                $len = in_array($l2['billing'], ["minutely", "hourly"]) ? max(2, strlen(substr(strrchr(rtrim($l2['price'], "0"), "."), 1))) : 2;
                ?>

<?php
$data = [];

                if ($l2['last_billed'] != "0000-00-00") {
                    $data[$l['NEXTINV']] = $dfo->format($l2['last_billed'], false, false, false);
                }

                $module_settings = unserialize(decrypt($l2['module_settings']));
                $mgmt_server = intval(is_array($module_settings) ? ($module_settings["_mgmt_server"] ?? 0) : 0);

                if ($mgmt_server) {
                    $mssql = $db->query("SELECT name FROM monitoring_server WHERE ID = $mgmt_server");
                    if ($mssql->num_rows) {
                        $data[$l['SERVERNAME']] = $mssql->fetch_object()->name;
                    }
                }

                $domsql = $db->query("SELECT domain FROM domains WHERE inclusive_id = {$l2['ID']}");
                if ($domsql->num_rows) {
                    $doms = [];
                    while ($domrow = $domsql->fetch_object()) {
                        $doms[] = $domrow->domain;
                    }

                    $data[$l['INCLDOM']] = implode(", ", $doms);
                }

                $domsql = $db->query("SELECT domain FROM domains WHERE addon_id = {$l2['ID']}");
                if ($domsql->num_rows) {
                    $doms = [];
                    while ($domrow = $domsql->fetch_object()) {
                        $doms[] = $domrow->domain;
                    }

                    $data[$l['ADDONDOM']] = implode(", ", $doms);
                }

                if ($l2['module'] && array_key_exists($l2['module'], $provisioning->get()) && is_object($mymod = $provisioning->get()[$l2['module']]) && method_exists($mymod, "ContractInfo")) {
                    foreach ($mymod->ContractInfo($l2['ID']) as $k => $v) {
                        $data[$k] = $v;
                    }
                }
                ?>
		<tr>
			<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="products[]" value="<?=$i;?>" /></td>
			<td><?php if (count($data)) {?><a href="#" class="prdet" data-pid="<?=$l2['ID'];?>"><i class="fa fa-plus"></i></a><?php }?></td>
			<td><?=$dfo->format($l2['date'], false);?></td>
                <td><?=$name;?><?=!empty($l2['description']) ? " <small>{$l2['description']}</small>" : "";?> <div class="label label-<?=!empty($l2['error']) ? "danger" : $sl[$l2['active']];?>"><?=!empty($l2['error']) ? $l['ERRORHERE'] : ($l2['payment'] ? $lang['HOSTING']['WAIT_PAY'] : $st[$l2['active']]);?></div><?php if ($l2['prepaid']) {?> <div class="label label-primary"><?=$lang['GENERAL']['PREPAID'];?></div><?php }?><?php if ($l2['active'] != "-2" && $l2['cancellation_date'] > "0000-00-00") {?> <div class="label label-default"><?=$l['CANCELLEDTO'];?> <?=$dfo->format($l2['cancellation_date'], false);?></div><?php }?></td>
			<td><?=$l2['billing'] == "" || $l2['billing'] == "onetime" ? $l['ONETIME'] . " " : "";?><?=$cur->infix($nfo->format($l2['price'], $len), $cur->getBaseCurrency());?> <?=$billing[$l2['billing']] ?: "";?></td>
			<td><a href="?p=hosting&id=<?=$l2['ID'];?>"><i class="fa fa-wrench fa-lg"></i></a> <a style="float: right;" href="?p=customers&edit=<?=$u->ID;?>&delete_license=<?=$i;?>&tab=products" title="L&ouml;schen" onclick="return confirm('<?=$l['HPREADEL'];?>');"><i class="fa fa-times fa-lg"></i></a></td>
		</tr>

        <tr style="display: none; padding-bottom: -20px;" id="prdet<?=$l2['ID'];?>">
            <td colspan="6">
                <div class="row">
                    <?php foreach ($data as $k => $v) {
                    echo '<div class="col-md-4"><b>' . $k . '</b><br />' . $v . '</div>';
                }
                ?>
                </div>
            </td>
        </tr>
		<?php
}?>
</table></div>
<?=$l['SELECTED'];?>: <input type="submit" name="lock_selected_products" value="<?=$l['LOCK'];?>" class="btn btn-warning" /> <input type="submit" name="unlock_selected_products" value="<?=$l['UNLOCK'];?>" class="btn btn-success" /> <input type="submit" name="delete_selected_products" value="<?=$l['DELETE'];?>" class="btn btn-danger" />
</form>

<script>
$(".prdet").click(function(e) {
    e.preventDefault();

    var i = $(this).find("i");
    if (i.hasClass("fa-plus")) {
        $("#prdet" + $(this).data("pid")).show();
        i.removeClass("fa-plus").addClass("fa-minus");
    } else {
        $("#prdet" + $(this).data("pid")).hide();
        i.addClass("fa-plus").removeClass("fa-minus");
    }
});
</script>

<?php
echo $t->getFooter();
            ?><br /><br />

</div><?php } else if ($ari->check(13) && $tab == "laterinvoice") {?>

<form method="POST" class="form-inline"><?=$l['LATERINV1'];?> <input type="text" name="invoicelater" class="form-control input-xs" value="<?=$u->invoicelater;?>" placeholder="1" style="max-width: 50px;" /> <?=$l['LATERINV2'];?> <input type="submit" class="btn btn-xs btn-primary" value="<?=$l['SAVE'];?>" /><br /><small><?=$l['LATERINV3'];?></small></form>

<form method="POST"><div class="table-responsive" style="margin-top: 10px;">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="20px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
			<th><?=$l['DESCRIPTION'];?></th>
			<th><?=$l['AMOUNT'];?></th>
		</tr>

		<?php
$sql = $db->query("SELECT * FROM invoicelater WHERE user = " . $u->ID);
            if ($sql->num_rows == 0) {?>
		<tr>
			<td colspan="3"><center><?=$l['NOLATERINV'];?></center></td>
		</tr>
		<?php } else {while ($row = $sql->fetch_object()) {?>
		<tr>
			<td style="vertical-align: middle;"><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="ids[]" value="<?=$row->ID;?>"></td>
			<td><?=nl2br($row->description);?></td>
			<td><font color="<?=$row->paid ? "green" : "red";?>"><?=$cur->infix($nfo->format($row->amount), $cur->getBaseCurrency());?></font></td>
		</tr>
		<?php }}?>
	</table>
</div>

<?=$l['SELECTED'];?>: <input type="submit" value="<?=$l['DELETE'];?>" class="btn btn-danger" /></form>

<?php } else if ($ari->check(13) && $tab == "invoices") {

            if (!isset($msg) && isset($_GET['send']) && is_numeric($_GET['send']) && is_object($inv = new Invoice) && $inv->load($_GET['send']) && $inv->getStatus() != "3") {
                $inv->send("send");
                $session->set("invoice_sent", $inv->getShortNo());
                alog("invoice", "sent_email", $_GET['send']);
                header('Location: ?p=customers&tab=invoices&edit=' . $u->ID);
                exit;
            }

            if (!isset($msg) && isset($_GET['pay']) && is_numeric($_GET['pay']) && is_object($inv = new Invoice) && $inv->load($_GET['pay']) && $inv->applyCredit() !== false) {
                $session->set("invoice_paid", $inv->getShortNo());
                alog("invoice", "pay_by_credit", $_GET['pay']);
                header('Location: ?p=customers&tab=invoices&edit=' . $u->ID);
                exit;
            }

            if ($session->get("invoice_sent")) {
                $msg = str_replace("%i", $session->get("invoice_sent"), $l['INVSENT11']);
                $session->remove("invoice_sent");
            }

            if ($session->get("invoice_paid")) {
                $msg = str_replace("%i", $session->get("invoice_paid"), $l['INVPAID11']);
                $session->remove("invoice_paid");
            }

            $paid = $db->query("SELECT SUM(amount * qty) AS s FROM invoiceitems p INNER JOIN invoices i ON i.ID = p.invoice WHERE i.client = {$u->ID} AND status = 1")->fetch_object()->s;
            $unpaid = $db->query("SELECT SUM(amount * qty) AS s FROM invoiceitems p INNER JOIN invoices i ON i.ID = p.invoice WHERE i.client = {$u->ID} AND status = 0")->fetch_object()->s;

            if ($u->no_reminders) {
                echo '<div class="alert alert-warning">' . $l['REMDISABLED'] . '</div>';
            }

            ?>
<div class="tab-pane" id="tab2">
<?=isset($msg) ? '<div class="alert alert-success">' . $msg . '</div>' : "";?>

<div class="row">
	<div class="col-lg-4 col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($paid + $unpaid), $cur->getBaseCurrency());?></div>
						<div><?=$l['SUM'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($paid), $cur->getBaseCurrency());?></div>
						<div><?=$l['ISPAID'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-danger">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($unpaid), $cur->getBaseCurrency());?></div>
						<div><?=$l['ISUNPAID'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$t = new Table("SELECT * FROM invoices WHERE client = " . $u->ID, [
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "3" => $l['INVS0'],
                        "0" => $l['INVS1'],
                        "1" => $l['INVS2'],
                        "2" => $l['INVS3'],
                    ],
                ],
            ], ["date", "DESC"]);

            echo $t->getHeader();
            ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th>#</th>
		<th><?=$t->orderHeader("date", $l['DATE']);?></th>
		<th><?=$t->orderHeader("duedate", $l['DUE']);?></th>
		<?php if ($CFG['TAXES']) {?>
		<th><?=$l['NET'];?></th>
		<th><?=$l['GROSS'];?></th>
		<?php } else {?><th><?=$l['AMOUNT'];?></th><?php }?>
		<th><?=$t->orderHeader("status", $l['STATUS']);?></th>
		<th><?=$t->orderHeader("reminder", $l['REMLEVEL']);?></th>
		<th width="35px"><center><?=$t->orderHeader("letter_sent", $l['INVPOST']);?></center></th>
		<th width="35px"><center><?=$t->orderHeader("encashment_provider", $l['INVENC']);?></center></th>
		<th width="30px"></th>
	</tr>

	<form method="POST" id="invoice_form">
	<?php $sql = $t->qry("date DESC, ID DESC");while ($row = $sql->fetch_object()) {$invoice = new Invoice;
                $invoice->load($row->ID);?>
	<tr>
		<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$invoice->getId();?>" /></td>
		<td><?php if (!empty($invoice->getAttachment())) {?><span class="label label-primary"><?=array_shift(explode(".", $invoice->getAttachment()));?></span><?php }?> <a href="#" class="invoiceDetails" data-id="<?=$invoice->getId();?>"><?=$invoice->getShortNo();?></a></td>
		<td><?=$dfo->format(strtotime($invoice->getDate()), false);?></td>
		<td><?=$dfo->format(strtotime($invoice->getDueDate()), false);?></td>
		<?php if ($CFG['TAXES']) {?>
		<td><?=$cur->infix($nfo->format($invoice->getNet()), $cur->getBaseCurrency());?></td>
		<td><?=$cur->infix($nfo->format($invoice->getGross()), $cur->getBaseCurrency());?></td>
		<?php } else {?><td><?=$cur->infix($nfo->format($invoice->getAmount()), $cur->getBaseCurrency());?></td><?php }?>
		<td><?php if ($invoice->getStatus() == 0) {?><font color="red"><?=$l['INVS1'];?></font> <a href="?p=customers&edit=<?=$u->ID;?>&tab=invoices&pay=<?=$invoice->getId();?>"><i class="fa fa-money"></i></a><?php } else if ($invoice->getStatus() == 1) {?><font color="green"><?=$l['INVS2'];?></font><?php } else if ($invoice->getStatus() == 2) {?><?=$l['INVS3'];?><?php } else {?><?=$l['INVS0'];?><?php }?></td>
		<td><?php if (!$invoice->getReminders()) {?><i class="fa fa-ban"></i> <?php }?><?php if (!$invoice->reminderLevel()) {?><i><?=$l['NOREMLEVEL'];?></i><?php } else {?><?=$invoice->reminderLevel();?><?php }?></td>
		<td><center><i class="fa fa-<?=$invoice->getLetterSent() ? "check" : "times";?>"></i></center></td>
		<td><center><a href="?p=encashment&invoice=<?=$invoice->getId();?>"><i class="fa fa-<?=$invoice->isEncashment() ? "check" : "times";?>"></i></a></center></td>
		<td>
			<a href="?p=invoice&id=<?=$invoice->getId();?>" title="<?=$l['EDIT'];?>"><i class="fa fa-edit"></i></a>
		</td>
	</tr>
	<?php }if (count($userInstance->getInvoices()) == 0) {
                echo "<tr><td colspan=\"12\"><center>{$l['NOINVOICES']}</center></td></tr>";
            }
            ?>
</table></div>
<?=$l['SELECTED'];?>:
<div class="btn-group">
  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <?=$l['CHANGESTATUS'];?> <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_paid', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['INVS2'];?></a></li>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_unpaid', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['INVS1'];?></a></li>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'cancel', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['INVS3'];?></a></li>
    <li role="separator" class="divider"></li>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'delete', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['DELETE'];?></a></li>
  </ul>
</div>

<input type="submit" name="credit_pay" value="<?=$l['INVMAKECREDIT'];?>" class="btn btn-success" />

<div class="btn-group">
  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <?=$l['REMSYS'];?> <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">
  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'reminder', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['SETNOREMLEVEL'];?></a></li>
    <?php
$sql = $db->query("SELECT * FROM reminders ORDER BY days ASC, name ASC");
            while ($row = $sql->fetch_object()) {?>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'reminder', value: '<?=$row->ID;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?php if (!empty($row->color)) {
                echo '<font color="' . $row->color . '">';
            }
                ?><?php if ($row->bold) {
                    echo "<b>";
                }
                ?><?=$row->name;?><?php if ($row->bold) {
                    echo "</b>";
                }
                ?><?php if (!empty($row->color)) {
                    echo '</font>';
                }
                ?></a></li>
   	<?php }?>
    <li role="separator" class="divider"></li>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'no_reminders', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['ACTIVATE'];?></a></li>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'no_reminders', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['DEACTIVATE'];?></a></li>
  </ul>
</div>

<div class="btn-group">
  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <?=$l['PDF'];?> <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">
	<li><a href="#" onclick="$('#invoice_form').attr('action', '?p=invoices').attr('target', '_blank').submit().attr('target', '').attr('action', ''); return false;"><?=$l['INVOICEFILE'];?></a></li>
	<li><a href="#" onclick="$('#invoice_form').attr('action', '?p=delivery_notes').attr('target', '_blank').submit().attr('target', '').attr('action', ''); return false;"><?=$l['DELIVERYNOTE'];?></a></li>
  </ul>
</div>

<div class="btn-group">
  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <?=$l['SENDTO'];?> <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">
  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_mail', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['SENDVIAMAIL'];?></a></li>
    <?php foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {?>
    <li role="separator" class="divider"></li>
    <li class="dropdown-header"><?=$drivObj->getName();?></li>
    <?php foreach ($drivObj->getTypes() as $code => $name) {?>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_letter', value: '<?=$drivKey;?>#<?=$code;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$name;?></a></li>
    <?php }}?>
  </ul>
</div>

<input type="submit" name="split" value="<?=$l['SPLIT'];?>" class="btn btn-default" />

<input type="submit" name="cancel_invoice" value="<?=$l['CANCELINV'];?>" class="btn btn-default" />

<input type="submit" name="clear_data" value="<?=$l['INVCLEARDATA'];?>" class="btn btn-default" />
</form><br /><br />

<?=$t->getFooter();?>

</div><?php } else if ($ari->check(13) && $tab == "recurring") {

            if (isset($_POST['activate']) && isset($_POST['invoices']) && is_array($_POST['invoices'])) {
                $d = 0;
                foreach ($_POST['invoices'] as $i) {
                    $db->query("UPDATE invoice_items_recurring SET status = 1 WHERE status = 0 AND ID = " . intval($i));
                    if ($db->affected_rows > 0) {
                        $d++;
                        alog("recurring", "activated", $i);
                    }
                }

                if ($d == 1) {
                    $msg = $l['RECACTO'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['RECACTX']);
                }

            }

            if (isset($_POST['deactivate']) && isset($_POST['invoices']) && is_array($_POST['invoices'])) {
                $d = 0;
                foreach ($_POST['invoices'] as $i) {
                    $db->query("UPDATE invoice_items_recurring SET status = 0 WHERE status = 1 AND ID = " . intval($i));
                    if ($db->affected_rows > 0) {
                        $d++;
                        alog("recurring", "deactivated", $i);
                    }
                }

                if ($d == 1) {
                    $msg = $l['RECDEACTO'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['RECDEACTX']);
                }

            }

            if (isset($_POST['delete']) && isset($_POST['invoices']) && is_array($_POST['invoices'])) {
                $d = 0;
                foreach ($_POST['invoices'] as $i) {
                    $db->query("DELETE FROM invoice_items_recurring WHERE ID = " . intval($i));
                    if ($db->affected_rows > 0) {
                        $d++;
                        alog("recurring", "delete", $i);
                    }
                }

                if ($d == 1) {
                    $msg = $l['RECDELO'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['RECDELX']);
                }

            }

            if (isset($_GET['pay']) && is_numeric($_GET['pay']) && ($obj = RecurringInvoice::getInstance($_GET['pay'])) !== false) {
                $obj->bill(true, null, true);
                $session->set('recurring_paid', '1');
                alog("recurring", "bill", $_GET['pay']);
                header('Location: ?p=customers&edit=' . $u->ID . '&tab=recurring');
                exit;
            }

            if ($session->get('recurring_paid') == "1") {
                $msg = $l['RECBILLED'];
                $session->set('recurring_paid', '0');
            }

            $paid = $db->query("SELECT SUM(amount) AS s FROM invoiceitems p INNER JOIN invoices i ON i.ID = p.invoice WHERE i.client = {$u->ID} AND status = 1 AND p.recurring != 0")->fetch_object()->s;
            $unpaid = $db->query("SELECT SUM(amount) AS s FROM invoiceitems p INNER JOIN invoices i ON i.ID = p.invoice WHERE i.client = {$u->ID} AND status = 0 AND p.recurring != 0")->fetch_object()->s;

            ?>
<div class="tab-pane" id="tab2">
<?=isset($msg) ? '<div class="alert alert-success">' . $msg . '</div>' : "";?>

<div class="row">
	<div class="col-lg-4 col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($paid + $unpaid), $cur->getBaseCurrency());?></div>
						<div><?=$l['RECOVBILL'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($paid), $cur->getBaseCurrency());?></div>
						<div><?=$l['RECOVPAID'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-danger">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($unpaid), $cur->getBaseCurrency());?></div>
						<div><?=$l['RECOVUNPAID'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$t = new Table("SELECT * FROM invoice_items_recurring WHERE user = " . $u->ID, [
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "0" => $l['INACTIVE'],
                        "1" => $l['ACTIVE'],
                    ],
                ],
            ], ["first", "ASC"]);

            echo $t->getHeader();
            ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$t->orderHeader("first", $l['FIRSTBILL']);?></th>
		<th><?=$t->orderHeader("last", $l['NEXTDUE']);?></th>
		<th><?=$t->orderHeader("period", $l['INTERVAL']);?></th>
		<th><?=$t->orderHeader("amount", $l['AMOUNT']);?></th>
		<th><?=$l['RECOVBILL'];?></th>
		<th><?=$l['STATUS'];?></th>
		<th width="30px"></th>
	</tr>

	<form method="POST" id="invoice_form" >
	<?php
$sql = $t->qry("first ASC, ID ASC");
            while ($row = $sql->fetch_object()) {
                $invoice = new RecurringInvoice($row->ID);
                $interval = $invoice->getInterval();
                $ex = explode(" ", $interval);
                $int1 = $ex[0];
                $int2 = $ex[1];

                if ($int1 == 1) {
                    $a = array("day" => $l['DAY'], "week" => $l['WEEK'], "month" => $l['MONTH'], "year" => $l['YEAR']);
                    $int2 = $a[$int2];
                } else {
                    $a = array("day" => $l['DAYS'], "week" => $l['WEEKS'], "month" => $l['MONTHS'], "year" => $l['YEARS']);
                    $int2 = $a[$int2];
                }

                $interval = $int1 . " " . $int2;
                ?>
	<tr>
		<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$invoice->getId();?>" /></td>
		<td><?=$dfo->format(strtotime($invoice->getFirst()), false);?></td>
		<td<?=strtotime($invoice->getNext()) < time() && !$invoice->hasExpired() ? ' style="color: red;"' : "";?>>
            <?php if ($invoice->hasExpired()) {
                    echo "-";
                } else {?>
            <?=$dfo->format(strtotime($invoice->getNext()), false);?> <a href="?p=customers&edit=<?=$u->ID;?>&tab=recurring&pay=<?=$invoice->getId();?>"><i class="fa fa-money"></i></a>
            <?php }?>
        </td>
		<td><?=$interval;?></td>
		<td><?=$cur->infix($nfo->format($invoice->getAmount()), $cur->getBaseCurrency());?></td>
		<td><?=$cur->infix($nfo->format($invoice->getInvoicedAmount()), $cur->getBaseCurrency());?></td>
            <td><?php if ($invoice->getStatus() == 0) {?><font color="red"><?=$l['INACTIVE'];?></font><?php } elseif (!$invoice->hasExpired()) {?><font color="green"><?=$l['ACTIVE'];?></font><?php } else {?><font color="orange"><?=$l['FINISHED'];?></font><?php }?></td>
		<td>
			<a href="?p=recurring_invoice&id=<?=$invoice->getId();?>" title="<?=$l['EDIT'];?>"><i class="fa fa-edit"></i></a>
		</td>
	</tr>
	<?php }if (count($userInstance->getRecurringInvoices()) == 0) {
                echo "<tr><td colspan=\"8\"><center>{$l['RECNT']}</center></td></tr>";
            }
            ?>
</table></div>
<?=$l['SELECTED'];?>:
<input type="submit" name="activate" value="<?=$l['ACTIVATE'];?>" class="btn btn-success" /> <input type="submit" name="deactivate" value="<?=$l['DEACTIVATE'];?>" class="btn btn-warning" /> <input type="submit" name="delete" value="<?=$l['DELETE'];?>" class="btn btn-danger" /></form>

</div><?php echo $t->getFooter();} else if ($ari->check(15) && $tab == "transactions") { ?>
<div class="tab-pane" id="tab3">
<?php
$saldo = $u->credit;
            ?>

<?=isset($suc_trans) ? "<div class=\"alert alert-success\">{$suc_trans}</div>" : "";?>
<?=isset($err_trans) ? "<div class=\"alert alert-danger\">{$err_trans}</div>" : "";?>

<div class="row">
	<div class="col-lg-3 col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($u->credit), $cur->getBaseCurrency());?></div>
						<div><?=$l['ACCBALANCE'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-3 col-md-3">
		<div class="panel panel-warning">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($u->special_credit), $cur->getBaseCurrency());?></div>
						<div><?=$l['CONTSPECRE'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-3 col-md-3">
		<div class="panel panel-success">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($transactions->getPositiveSum($u->ID)), $cur->getBaseCurrency());?></div>
						<div><?=$l['DEPOSITED'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-3 col-md-3">
		<div class="panel panel-danger">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($transactions->getNegativeSum($u->ID) / -1), $cur->getBaseCurrency());?></div>
						<div><?=$l['USEDCREDIT'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$t = new Table("SELECT * FROM client_transactions WHERE user = " . $u->ID, [], ["time", "DESC"]);

            echo $t->getHeader();
            ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th><?=$t->orderHeader("time", $l['DATE']);?></th>
					<th><?=$t->orderHeader("subject", $l['DESCRIPTION']);?></th>
					<th><?=$t->orderHeader("amount", $l['AMOUNT']);?></th>
					<th><?=$l['CREDIT'];?></th>
					<th><?=$l['WHO'];?></th>
					<?php if ($ari->check(16)) {?><th width="60px"></th><?php }?>
				</tr>

				<form method="POST">
				<?php
$sql = $t->qry("`time` DESC, ID DESC");
            if (!$sql->num_rows) {
                ?>
					<tr>
						<td colspan="7"><center><?=$l['NOCRETRAN'];?></center></td>
					</tr>
				<?php
} else {
                $additionalJS .= "function reverseT(id) {
						swal({
							title: '{$l['TRANSREVERSE']}',
							text: '{$l['TRANSREVERSE2']}',
							type: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: '{$l['YES']}',
							cancelButtonText: '{$l['NO']}',
							closeOnConfirm: false
						}, function(){
							window.location = '?p=customers&edit={$u->ID}&tab=$tab&pay_undone=' + id;
						});
					}";

                $additionalJS .= "function deleteT(id) {
						swal({
							title: '{$l['TRANSDEL']}',
							text: '{$l['TRANSDEL2']}',
							type: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: '{$l['YES']}',
							cancelButtonText: '{$l['NO']}',
							closeOnConfirm: false
						}, function(){
							window.location = '?p=customers&edit={$u->ID}&tab=$tab&pay_delete=' + id;
						});
                    }";

                $sql2 = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
                while ($row2 = $sql2->fetch_object()) {
                    $options[$row2->ID] = htmlentities($row2->name);
                }

                function formatChanger($who)
                {
                    global $options, $l;

                    if (array_key_exists($who, $options)) {
                        return $options[$who];
                    }

                    return $l['STAFFMEMBER'];
                }

                while ($b = $sql->fetch_object()) {
                    $b->raw_subject = $b->subject;
                    $b->subject = Transactions::subject($b->subject);

                    $refundable = false;
                    if ($b->deposit && $b->amount > 0 && !$b->waiting) {
                        $ex = explode("|", $b->raw_subject);
                        if (array_key_exists($ex[0], $gateways->get()) && $gateways->get()[$ex[0]]->canRefund()) {
                            $refundable = true;
                        }

                    }
                    ?>
						<tr>
							<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="transactions[]" value="<?=$b->ID;?>" /></td>
							<td><?=$dfo->format($b->time);?></td>
                <td><?php if (!empty($b->cashbox_subject)) {?><a href="#" onclick="return false;" data-toggle="tooltip" data-original-title="<?=$lang['GENERAL']['CASHBOX'];?>: <?=$b->cashbox_subject;?>"><?php }?><?=htmlentities($b->subject);?><?php if (!empty($b->cashbox_subject)) {?></a><?php }?><?php if ($b->deposit && !$b->waiting) {?> <a href="?p=customers&receipt=<?=$b->ID;?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a><?php }?><?php if ($refundable) {?><?php }?><?php if ($refundable) {?> <a href="?p=customers&edit=<?=$u->ID;?>&refund=<?=$b->ID;?>&tab=transactions" onclick="return confirm('<?=$l['TRANSREFUND'];?>');"><i class="fa fa-rotate-left"></i></a><?php }?><?php if ($b->waiting) {?> <small><font color="red">(<?=$l['TRANSPRE'];?><?=$b->waiting == 2 ? " " . $l['ANDBOOKED'] : "";?>)</font></small><?php }?><?php if ($b->amount > 0 && $ari->check(16) && !$b->chargeback) {?> <a href="?p=chargeback&id=<?=$b->ID;?>"><i class="fa fa-exclamation-triangle"></i></a><?php }?></td>
							<td><font color="<?=$b->waiting ? "orange" : ($b->amount < 0 ? "red" : "green");?>"><?=$cur->infix($nfo->format($b->amount), $cur->getBaseCurrency());?></font></td>
							<td><font color="<?=$saldo < 0 ? "red" : "green";?>"><?=$cur->infix($nfo->format($saldo), $cur->getBaseCurrency());?></font></td>
							<td><?=!$b->who ? $l['SYSTEM'] : formatChanger($b->who);?></td>
							<?php if ($ari->check(16)) {?><td width="60px"><?php if (!$b->waiting) {?><a href="#" onclick="reverseT(<?=$b->ID;?>); return false;"><i class="fa fa-undo fa-lg"></i></a><?php } else {?><a href="?p=customers&edit=<?=$u->ID;?>&pay_ok=<?=$b->ID;?>&tab=transactions"><i class="fa fa-check-circle-o fa-lg"></i></a><?php }?> <a style="margin-left: 5px;" href="#" onclick="deleteT(<?=$b->ID;?>); return false;"><i class="fa fa-times-circle-o fa-lg"></i></a></td><?php }?>
						</tr>

						<div class="modal fade" id="payment_<?=$b->ID;?>" tabindex="-1" role="dialog" aria-hidden="true">
						  <div class="modal-dialog">
							<div class="modal-content">
							  <div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
								<h4 class="modal-title" id="myModalLabel"><?=$l['PAYMENTNR'];?> #<?=$b->ID;?></h4>
							  </div>
							  <div class="modal-body">
								<?=nl2br($b->data);?>
							  </div>
							  <div class="modal-footer">
								<button type="button" class="btn btn-primary" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
							  </div>
							</div>
						  </div>
						</div>
					<?php
if (!$b->waiting) {
                        $saldo -= $b->amount;
                    }

                }
            }
            ?>
			</table></div>
<?=$l['SELECTED'];?>: <input type="submit" name="revert_selected_transactions" class="btn btn-warning" value="<?=$l['REVERSEIT'];?>" /> <input type="submit" name="delete_selected_transactions" class="btn btn-danger" value="<?=$l['DELETE'];?>" />
			</form>


</div><?php echo $t->getFooter();} else if ($tab == "affiliate" && $CFG['AFFILIATE_ACTIVE']) { ?>
<?php if ($u->affiliate_credit != 0) {?>
<div class="alert alert-info"><?=$l['HASAFFCRE'];?> <?=$cur->infix($nfo->format($u->affiliate_credit), $cur->getBaseCurrency());?>. [ <a href="?p=customers&edit=<?=$u->ID;?>&tab=affiliate&to_credit" onclick="return confirm('<?=$l['AFFTOCRE2'];?>');"><?=$l['AFFTOCRE'];?></a> ]</div>
<?php }?>

<div class="row">
	<div class="col-lg-4 col-md-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($in = $db->query("SELECT SUM(amount) AS sum FROM client_affiliate WHERE affiliate = " . $u->ID)->fetch_object()->sum), $cur->getBaseCurrency());?></div>
						<div><?=$l['FORREFS'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($out = $db->query("SELECT SUM(amount) AS sum FROM client_affiliate WHERE user = " . $u->ID)->fetch_object()->sum), $cur->getBaseCurrency());?></div>
						<div><?=$l['TOTHE'];?> <?php if ($u->affiliate > 0) {
            echo '<a href="?p=customers&edit=' . $u->affiliate . '">' . $l['HASREF'] . '</a>';
        } else {
            echo $l['HASREF'];
        }
            ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-danger">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($in + $out), $cur->getBaseCurrency());?></div>
						<div><?=$l['COSTS'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="table-responsive">
	<form method="POST"><table class="table table-bordered table-striped">
		<tr>
			<th width="25px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
			<th><?=$l['CUSTOMER'];?></th>
			<th><?=$l['SOURCE'];?></th>
			<th><?=$l['INCOME'];?></th>
			<th width="25px"></th>
		</tr>

		<?php
$afSql = $db->query("SELECT ID, firstname, lastname, affiliate_source FROM clients WHERE affiliate = " . $u->ID . " ORDER BY firstname ASC, lastname ASC");
            if ($afSql->num_rows == 0) {
                ?>
		<tr>
			<td colspan="5"><center><?=$l['NOREFS'];?></center></td>
		</tr>
		<?php } else {while ($row = $afSql->fetch_object()) {?>
		<tr>
			<td><input type="checkbox" name="afcusts[]" value="<?=$row->ID;?>" class="checkbox" onchange="javascript:toggle();" /></td>
			<td><a href="?p=customers&edit=<?=$row->ID;?>"><?=User::getInstance($row->ID, "ID")->getfName();?></a></td>
			<td><?=!empty($row->affiliate_source) ? htmlentities($row->affiliate_source) : "-";?></td>
			<td><?=$cur->infix($nfo->format($db->query("SELECT SUM(amount) AS sum FROM client_affiliate WHERE user = {$row->ID} AND affiliate = " . $u->ID)->fetch_object()->sum), $cur->getBaseCurrency());?></td>
			<td><a href="?p=customers&edit=<?=$u->ID;?>&tab=affiliate&free=<?=$row->ID;?>" onclick="return confirm('<?=$l['AFFREAREL'];?>');"><i class="fa fa-times"></i></a></td>
		</tr>
		<?php }}?>
	</table><?=$l['SELECTED'];?>: <input type="submit" name="free_selected" value="<?=$l['RELEASE'];?>" class="btn btn-warning" /></form>
</div>
<?php } else if ($ari->check(17) && $tab == "ip") {?>
<div class="tab-pane" id="tab4">

<?php
if (isset($_GET['delete_ip'])) {
            $item = abs(intval($_GET['delete_ip']));
            $sql = $db->query("DELETE FROM ip_logs WHERE ID = '$item' AND user = " . $u->ID . " LIMIT 1");

            if ($db->affected_rows >= 1) {
                echo "<div class='alert alert-success'>{$l['IPDEL']}</div>";
                alog("customers", "delete_ip", $u->ID, $item);
            }
        } else if (isset($_POST['delete_selected_ips']) && is_array($_POST['ip'])) {
            $d = 0;
            foreach ($_POST['ip'] as $id) {
                $sql = $db->query("DELETE FROM ip_logs WHERE ID = '" . $db->real_escape_string($id) . "' AND user = " . $u->ID . " LIMIT 1");

                if ($db->affected_rows >= 1) {
                    $d++;
                    alog("customers", "delete_ip", $u->ID, $id);
                }
            }

            if ($d == 1) {
                $suc = "{$l['IPDEL1']}";
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['IPDELX']);
            }

            if (isset($suc)) {
                echo "<div class='alert alert-success'>$suc</div>";
            }

        }

            $t = new Table("SELECT * FROM ip_logs WHERE user = " . $u->ID, [], ["time", "DESC"]);

            echo $t->getHeader();
            ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
		<th><?=$t->orderHeader("time", $l['FIRSTOCC']);?></th>
		<th><?=$t->orderHeader("ip", $l['IPADDR']);?></th>
        <th width="30px"></th>
	</tr>

	<form method="POST">
	<?php
$sql = $t->qry("time DESC");
            while ($r = $sql->fetch_object()) {
                ?>
	<tr>
		<td><input class="checkbox" name="ip[]" value="<?=$r->ID;?>" type="checkbox" onchange="javascript:toggle();" /></td>
		<td><?=$dfo->format($r->time);?></td>
		<td><?php if (($r->country == "" || $r->country == "no") && ($r->city == "" || $r->city == "no")) {?><?=$r->ip;?><?php } else {?><a href="#" data-toggle="tooltip" onclick="return false;" data-original-title="<?php if ($r->city != "" && $r->city != "no") {?><?=$r->city;?>, <?php }if ($r->country != "" && $r->country != "no") {?><?=$r->country;?><?php }?>"><?=$r->ip;?></a><?php }?></td>
        <th width="30px"><a href="?p=customers&edit=<?=$_GET['edit'];?>&tab=ip&delete_ip=<?=$r->ID;?>"><i class="fa fa-times fa-lg"></i></a></th>
	</tr>
	<?php }if ($sql->num_rows <= 0) {?>
	<tr>
		<td colspan="4"><center><?=$l['NOIPS'];?></center></td>
	</tr>
	<?php }?>
</table></div>
<?=$l['SELECTED'];?>: <input type="submit" name="delete_selected_ips" value="<?=$l['DELETE'];?>" class="btn btn-danger" />
</form>

</div><?php echo $t->getFooter();} else if ($ari->check(13) && $tab == "reseller" && $u->reseller) { ?>
<div class="tab-pane" id="tab4">

<?php

            $t = new Table("SELECT * FROM client_customers WHERE uid = " . $u->ID, [], ["mail", "ASC"]);

            echo $t->getHeader();
            ?>
<style>
.tooltip-inner {
    white-space:pre-wrap;
}
</style>
<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th><?=$t->orderHeader("mail", $l['RESE_MAIL']);?></th>
		<th width="30%" style="text-align: center;"><?=$l['RESE_CONTRACTS'];?></th>
	</tr>

	<?php
$sql = $t->qry("mail ASC");
            while ($r = $sql->fetch_object()) {
                $con = [];
                $sql2 = $db->query("SELECT ID FROM client_products WHERE reseller_customer = {$r->ID}");
                while ($row2 = $sql2->fetch_object()) {
                    array_push($con, "#" . $row2->ID . " " . unserialize($uI->get()['products_info'])[$row2->ID]["name"]);
                }

                ?>
	<tr>
		<td><?=htmlentities($r->mail);?></td>
		<td style="text-align: center;"><a href="#" onclick="return false;" title="<?=implode("\n", $con);?>" data-toggle="tooltip"><?=count($con);?></a></td>
	</tr>
	<?php }if ($sql->num_rows <= 0) {?>
	<tr>
		<td colspan="2"><center><?=$l['RESE_NONE'];?></center></td>
	</tr>
	<?php }?>
</table></div>

</div><?php echo $t->getFooter();} else if ($ari->check(49) && $tab == "log") { ?>
<div class="tab-pane" id="tab_logs">

<?php
if (isset($_GET['delete_entry'])) {
            $item = intval($_GET['delete_entry']);
            $sql = $db->query("DELETE FROM client_log WHERE ID = $item AND user = " . $u->ID . " LIMIT 1");

            if ($db->affected_rows >= 1) {
                echo "<div class='alert alert-success'>{$l['LOGDEL']}</div>";
                alog("customers", "delete_log", $u->ID, $item);
            }
        } else if (isset($_POST['delete_selected_logs']) && is_array($_POST['log'])) {
            $d = 0;
            foreach ($_POST['log'] as $id) {
                $item = intval($id);
                $sql = $db->query("DELETE FROM client_log WHERE ID = $item AND user = " . $u->ID . " LIMIT 1");

                if ($db->affected_rows >= 1) {
                    $d++;
                    alog("customers", "delete_log", $u->ID, $item);
                }
            }

            if ($d == 1) {
                echo "<div class='alert alert-success'>{$l['LOGDEL1']}</div>";
            } else if ($d > 0) {
                echo "<div class='alert alert-success'>" . str_replace("%d", $d, $l['LOGDELX']) . "</div>";
            }

        }

            $t = new Table("SELECT * FROM client_log WHERE user = " . $u->ID, [
                "action" => [
                    "name" => $l['LOGENTRY'],
                    "type" => "like",
                ],
                "ip" => [
                    "name" => $l['IPADDR'],
                    "type" => "like",
                ],
            ], ["time", "DESC"]);

            echo $t->getHeader();
            ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
		<th><?=$t->orderHeader("time", $l['DATE']);?></th>
		<th><?=$l['LOGENTRY'];?></th>
		<th><?=$t->orderHeader("ip", $l['IPADDR']);?></th>
        <th width="30px"></th>
	</tr>

	<form method="POST">
	<?php
$sql = $t->qry("time DESC");
            while ($r = $sql->fetch_object()) {
                $ipLogSql = $db->query("SELECT country, city FROM ip_logs WHERE ip = '" . $db->real_escape_string($r->ip) . "' LIMIT 1");
                if ($ipLogSql->num_rows == 1) {
                    $l2 = $ipLogSql->fetch_object();
                }

                ?>
	<tr>
		<td style="vertical-align: middle"><input type="checkbox" class="checkbox" name="log[]" value="<?=$r->ID;?>" onchange="javascript:toggle();"></td>
		<td style="vertical-align: middle"><?=$dfo->format($r->time);?></td>
		<td style="vertical-align: middle"><?=htmlentities($r->action);?></td>
		<td style="vertical-align: middle"><?php if (!isset($l2) || (($l2->country == "" || $l2->country == "no") && ($l2->city == "" || $l2->city == "no"))) {?><?=$r->ip;?><?php } else {?><a href="#" data-toggle="tooltip" onclick="return false;" data-original-title="<?php if ($l2->city != "" && $l2->city != "none") {?><?=$l2->city;?>, <?php }if ($l2->country != "" && $l2->country != "no") {?><?=$l2->country;?><?php }?>"><?=$r->ip;?></a><?php }?>
    <?php if (!empty($r->ua)) {?><a href="#" data-toggle="tooltip" onclick="return false;" data-original-title="<?=$r->ua;?>"><i class="fa fa-desktop"></i></a><?php }?>
    </td>
        <td style="vertical-align: middle" width="30px"><a href="?p=customers&edit=<?=$_GET['edit'];?>&tab=log&delete_entry=<?=$r->ID;?>"><i class="fa fa-times fa-lg"></i></a></td>
	</tr>
	<?php }if ($sql->num_rows <= 0) {?>
	<tr>
		<td colspan="5"><center><?=$l['LOGNT'];?></center></td>
	</tr>
	<?php }?>
</table></div>
<?=$l['SELECTED'];?>: <input type="submit" name="delete_selected_logs" value="<?=$l['DELETE'];?>" class="btn btn-danger" />
</form>
</div>
<?php echo $t->getFooter();} else if ($tab == "scoring" && $ari->check(62)) {

            if (isset($_GET['del'])) {
                $db->query("DELETE FROM client_scoring WHERE ID = " . intval($_GET['del']) . " AND user = " . intval($_GET['edit']) . " LIMIT 1");
                alog("customers", "delete_scoring", $u->ID, $_GET['del']);
            }

            $positive = $db->query("SELECT 1 FROM client_scoring WHERE user = " . intval($_GET['edit']) . " AND (rating = 'A' OR rating = 'B' OR rating = 'C')")->num_rows;
            $neutral = $db->query("SELECT 1 FROM client_scoring WHERE user = " . intval($_GET['edit']) . " AND (rating = 'D')")->num_rows;
            $negative = $db->query("SELECT 1 FROM client_scoring WHERE user = " . intval($_GET['edit']) . " AND (rating = 'E' OR rating = 'F')")->num_rows;
            $score = User::getInstance($_GET['edit'], "ID")->getScore();

            ?>
<div class="row">
	<div class="col-lg-3 col-md-3">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$nfo->format($score, 0);?> %</div>
						<div><?=$l['SCORECALC'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-3 col-md-3">
		<div class="panel panel-success">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$positive;?></div>
						<div><?=$l['SCOREPOS'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-3 col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$neutral;?></div>
						<div><?=$l['SCORENEU'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-3 col-md-3">
		<div class="panel panel-danger">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$negative;?></div>
						<div><?=$l['SCORENEG'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="table-responsive">
  <table class="table table-bordered table-striped">
    <tr>
      <th width="200px"><?=$l['DATE'];?></th>
      <th><?=$l['SCOREENTRY'];?></th>
      <th width="100px"><center><?=$l['SCORERATING'];?></center></th>
      <th width="30px"></th>
      <th width="28px"></th>
    </tr>

    <?php
$sql = $db->query("SELECT * FROM client_scoring WHERE user = " . intval($_GET['edit']) . " ORDER BY time DESC");
            if ($sql->num_rows > 0) {
                while ($row = $sql->fetch_object()) {
                    ?>
      <tr>
        <td><?=$dfo->format($row->time);?></td>
        <td><?=htmlentities($row->entry);?></td>
        <td><center><?php if ($row->rating == "F" || $row->rating == "A") {?><b><?php }?><font color="<?=array("A" => "green", "B" => "green", "C" => "green", "D" => "", "E" => "red", "F" => "red")[$row->rating];?>"><?=$row->rating;?></font><?php if ($row->rating == "F" || $row->rating == "A") {?></b><?php }?></center></td>
        <td><?php if (!empty($row->details)) {?><a href="#" data-toggle="modal" data-target="#scoring_<?=$row->ID;?>"><i class="fa fa-info-circle"></i></a><?php }?></td>
        <td><a href="?p=customers&edit=<?=$_GET['edit'];?>&tab=scoring&del=<?=$row->ID;?>" onclick="return confirm('<?=$l['REALLYDEL'];?>');"><i class="fa fa-times-circle"></i></a></td>
      </tr>
      <?php }
            } else {?>
    <tr>
      <td colspan="5"><center><?=$l['SCORENT'];?></center></td>
    </tr>
    <?php }?>
  </table>
</div>

<?php
$sql = $db->query("SELECT * FROM client_scoring WHERE user = " . intval($_GET['edit']) . " AND details != '' ORDER BY time DESC");
            while ($row = $sql->fetch_object()) {
                ?>
<div class="modal fade" id="scoring_<?=$row->ID;?>" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=htmlentities($row->entry);?></h4>
      </div>
      <div class="modal-body">
        <?=$row->details;?>
      </div>
    </div>
  </div>
</div>
<?php }} else if ($ari->check(19) && $tab == "notes") {?>
<div class="tab-pane" id="tab5">
<?php
if (isset($_POST['save_note']) && $ari->check(20) && ($sql = $db->query("SELECT * FROM client_notes WHERE ID = '" . $db->real_escape_string($_POST['save_note']) . "' AND user = " . $u->ID)) && $sql->num_rows == 1) {
            try {
                foreach ($_POST as $k => $v) {
                    $variable = "post" . ucfirst(strtolower($k));
                    $$variable = $db->real_escape_string($v);
                }

                if (!isset($postTitle) || strlen(trim($postTitle)) <= 0) {
                    throw new Exception($l['NERR1']);
                }

                if (!isset($postPriority) || !is_numeric($postPriority) || $postPriority > 3 || $postPriority < 0) {
                    throw new Exception($l['NERR2']);
                }

                if (!isset($postAdmin) || ($postAdmin != 0 && $db->query("SELECT ID FROM admins WHERE ID = '$postAdmin'")->num_rows != 1)) {
                    throw new Exception($l['NERR3']);
                }

                if (!isset($postDisplay) || ($postDisplay != "none" && $postDisplay != "info" && $postDisplay != "warning" && $postDisplay != "success" && $postDisplay != "error")) {
                    throw new Exception($l['NERR4']);
                }

                if (!isset($postText) || strlen(trim($postText)) <= 0) {
                    throw new Exception($l['NERR5']);
                }

                $sticky = isset($postSticky) && $postSticky ? 1 : 0;

                $noteInfo = $sql->fetch_object();
                $db->query("UPDATE client_notes SET `display` = '$postDisplay', `text` = '$postText', `title` = '$postTitle', `admin` = '$postAdmin', `priority` = '$postPriority', `last_changed` = " . time() . ", `sticky` = $sticky WHERE ID = " . $noteInfo->ID . " LIMIT 1");
                alog("customers", "note_saved", $u->ID, $postTitle, $noteInfo->ID);

                echo "<div class=\"alert alert-success\">{$l['NOTESAVED']}</div>";
            } catch (Exception $ex) {
                // If there is any error, send user back to note
                $_GET['note'] = intval($_POST['save_note']);
                echo "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
            }
        }

            if (isset($_POST['new_note']) && $ari->check(20)) {
                try {
                    foreach ($_POST as $k => $v) {
                        $variable = "post" . ucfirst(strtolower($k));
                        $$variable = $db->real_escape_string($v);
                    }

                    if (!isset($postTitle) || strlen(trim($postTitle)) <= 0) {
                        throw new Exception($l['NERR1']);
                    }

                    if (!isset($postPriority) || !is_numeric($postPriority) || $postPriority > 3 || $postPriority < 0) {
                        throw new Exception($l['NERR2']);
                    }

                    if (!isset($postAdmin) || ($postAdmin != 0 && $db->query("SELECT ID FROM admins WHERE ID = '$postAdmin'")->num_rows != 1)) {
                        throw new Exception($l['NERR3']);
                    }

                    if (!isset($postDisplay) || ($postDisplay != "none" && $postDisplay != "info" && $postDisplay != "warning" && $postDisplay != "success" && $postDisplay != "error")) {
                        throw new Exception($l['NERR4']);
                    }

                    if (!isset($postText) || strlen(trim($postText)) <= 0) {
                        throw new Exception($l['NERR5']);
                    }

                    $sticky = isset($postSticky) && $postSticky ? 1 : 0;

                    $time = time();
                    $db->query("INSERT INTO client_notes (`user`, `time`, `title`, `text`, `last_changed`, `admin`, `priority`, `display`, `sticky`) VALUES (" . $u->ID . ", $time, '$postTitle', '$postText', $time, '$postAdmin', '$postPriority', '$postDisplay', $sticky)");
                    alog("customers", "note_created", $u->ID, $postTitle, $id = $db->insert_id);

                    $path = __DIR__ . "/../../files/notes/" . $id;

                    if (!empty($_FILES['upload_files'])) {
                        if (!file_exists($path)) {
                            mkdir($path);
                        }

                        foreach ($_FILES["upload_files"]["name"] as $k => $name) {
                            $tmp_name = $_FILES["upload_files"]["tmp_name"][$k];
                            move_uploaded_file($tmp_name, $path . "/" . basename($name));
                        }
                    }

                    echo "<div class=\"alert alert-success\">{$l['NOTECREATED']}</div>";
                } catch (Exception $ex) {
                    // If there is any error, send user back to note
                    $_GET['new_note'] = "1";
                    echo "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
                }
            }

            if (isset($_GET['note']) && ($sql = $db->query("SELECT * FROM client_notes WHERE ID = '" . $db->real_escape_string($_GET['note']) . "' AND user = " . $u->ID)) && $sql->num_rows == 1) {
                $note = $sql->fetch_object();

                $path = __DIR__ . "/../../files/notes/" . $note->ID;

                if (!empty($_GET['delete_file'])) {
                    @unlink($path . "/" . basename($_GET['delete_file']));
                    header("Location: ?p=customers&edit=" . $u->ID . "&tab=notes&note={$note->ID}");
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

                    header("Location: ?p=customers&edit=" . $u->ID . "&tab=notes&note={$note->ID}");
                    exit;
                }
                ?>
	<div class="row">
	<div class="col-md-9">
	  <div class="panel panel-primary">
		<div class="panel-heading"><?=$l['NOTE'];?></div>
		<div class="panel-body">
			<form accept-charset="UTF-8" role="form" id="login-form" action="./?p=customers&edit=<?=$_GET['edit'];?>&tab=notes" method="post">
				<input type="text" name="title" value="<?=isset($postTitle) ? $postTitle : $note->title;?>" placeholder="<?=$l['NOTETITLEP'];?>" class="form-control"><br />
				<select name="priority" class="form-control">
					<option><?=$l['NOTEPRIORITYC'];?></option>
					<option value="0"<?=isset($postPriority) ? ($postPriority == 0 ? " selected=\"selected\"" : "") : ($note->priority == 0 ? " selected=\"selected\"" : "");?>><?=$l['NP0'];?></option>
					<option value="1"<?=isset($postPriority) ? ($postPriority == 1 ? " selected=\"selected\"" : "") : ($note->priority == 1 ? " selected=\"selected\"" : "");?>><?=$l['NP1'];?></option>
					<option value="2"<?=isset($postPriority) ? ($postPriority == 2 ? " selected=\"selected\"" : "") : ($note->priority == 2 ? " selected=\"selected\"" : "");?>><?=$l['NP2'];?></option>
					<option value="3"<?=isset($postPriority) ? ($postPriority == 3 ? " selected=\"selected\"" : "") : ($note->priority == 3 ? " selected=\"selected\"" : "");?>><?=$l['NP3'];?></option>
				</select><br />
				<select name="admin" class="form-control">
					<option value="0"><?=$l['NOTENAA'];?></option>
					<?php $adminSql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC, ID ASC");
                while ($admin = $adminSql->fetch_object()) {
                    ?>
					<option value="<?=$admin->ID;?>"<?=isset($postAdmin) ? ($postAdmin == $admin->ID ? " selected=\"selected\"" : "") : ($note->admin == $admin->ID ? " selected=\"selected\"" : "");?>><?=$admin->name;?></option>
					<?php }?>
				</select><br />
				<select name="display" class="form-control">
					<option value="none"<?=isset($postDisplay) && $postDisplay == "none" ? " selected=\"selected\"" : ($note->display == "none" ? " selected=\"selected\"" : "");?>><?=$l['ND0'];?></option>
					<option value="info"<?=isset($postDisplay) && $postDisplay == "info" ? " selected=\"selected\"" : ($note->display == "info" ? " selected=\"selected\"" : "");?>><?=$l['ND1'];?></option>
					<option value="success"<?=isset($postDisplay) && $postDisplay == "success" ? " selected=\"selected\"" : ($note->display == "success" ? " selected=\"selected\"" : "");?>><?=$l['ND2'];?></option>
					<option value="warning"<?=isset($postDisplay) && $postDisplay == "warning" ? " selected=\"selected\"" : ($note->display == "warning" ? " selected=\"selected\"" : "");?>><?=$l['ND3'];?></option>
					<option value="error"<?=isset($postDisplay) && $postDisplay == "error" ? " selected=\"selected\"" : ($note->display == "error" ? " selected=\"selected\"" : "");?>><?=$l['ND4'];?></option>
				</select><br />
				<input type="hidden" name="save_note" value="<?=$note->ID;?>">
				<textarea name="text" style="width:100%;height:250px;resize:none;" class="form-control"><?=isset($postText) ? str_replace('\r\n', '&#13;&#10;', $postText) : ($note->text);?></textarea>
				<div class="checkbox">
					<label>
					<input type="checkbox" name="sticky" value="1"<?=(isset($postSticky) && $postSticky) || (!isset($postText) && $note->sticky) ? ' checked="checked"' : '';?>> <?=$l['NOTESTICKY'];?>
					</label>
				</div>
				<input type="submit" value="<?=$l['SAVENOTE'];?>" class="btn btn-primary btn-block">
			</form>
		</div>
	 </div>
	</div>

	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['FILES'];?><a href="#" data-toggle="modal" data-target="#uploadDomainFile" class="pull-right"><i class="fa fa-plus"></i></a></div>
				<div class="panel-body" style="text-align: justify;">
					<?php
if (file_exists($path) && is_dir($path)) {
                    $files = [];
                    foreach (glob($path . "/*") as $f) {
                        array_push($files, basename($f));
                    }
                    if (!count($files)) {
                        echo "<i>{$l['NOTENOFILES']}</i>";
                    } else {
                        echo "<ul style='margin-bottom: 0;'>";

                        foreach ($files as $file) {
                            echo "<li>";
                            echo "<a href='?p=customers&edit={$u->ID}&tab=notes&note={$note->ID}&download_file=" . urlencode($file) . "' target='_blank'>" . htmlentities($file) . "</a>";
                            echo "<a href='?p=customers&edit={$u->ID}&tab=notes&note={$note->ID}&delete_file=" . urlencode($file) . "' class='pull-right'><i class='fa fa-times'></i></a>";
                            echo "</li>";
                        }

                        echo "</ul>";
                    }
                } else {
                    echo "<i>{$l['NOTENOFILES']}</i>";
                }
                ?>
				</div>
			</div>

			<div class="modal fade" id="uploadDomainFile" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<form method="POST" enctype="multipart/form-data" role="form">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title"><?=$l['UPLFILES'];?></h4>
							</div>
							<div class="modal-body">
								<div class="form-group" style="margin-bottom: 0;">
									<input type="file" class="form-control" name="upload_files[]" multiple>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
								<button type="submit" class="btn btn-primary"><?=$l['UPLFILES'];?></button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>

	</div><br />
	<?php
} else if (isset($_GET['new_note']) && $ari->check(20)) {
                ?>
	<div class="row">
	<div class="col-md-12">
	  <div>
	  <form accept-charset="UTF-8" role="form" id="login-form" action="./?p=customers&edit=<?=$_GET['edit'];?>&tab=notes" method="post" enctype="multipart/form-data">
		<input type="text" name="title" value="<?=isset($postTitle) ? $postTitle : "";?>" placeholder="<?=$l['NOTETITLEP'];?>" class="form-control"><br />
		<select name="priority" class="form-control">
			<option><?=$l['NOTEPRIORITYC'];?></option>
			<option value="0"<?=isset($postPriority) ? ($postPriority == 0 ? " selected=\"selected\"" : "") : "";?>><?=$l['NP0'];?></option>
			<option value="1"<?=isset($postPriority) ? ($postPriority == 1 ? " selected=\"selected\"" : "") : "";?>><?=$l['NP1'];?></option>
			<option value="2"<?=isset($postPriority) ? ($postPriority == 2 ? " selected=\"selected\"" : "") : "";?>><?=$l['NP2'];?></option>
			<option value="3"<?=isset($postPriority) ? ($postPriority == 3 ? " selected=\"selected\"" : "") : "";?>><?=$l['NP3'];?></option>
		</select><br />
		<select name="admin" class="form-control">
			<option value="0"><?=$l['NOTENAA'];?></option>
			<?php $adminSql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC, ID ASC");
                while ($admin = $adminSql->fetch_object()) {
                    ?>
			<option value="<?=$admin->ID;?>"<?=isset($postAdmin) ? ($postAdmin == $admin->ID ? " selected=\"selected\"" : "") : "";?>><?=$admin->name;?></option>
			<?php }?>
		</select><br />
		<select name="display" class="form-control">
			<option value="none"<?=isset($postDisplay) && $postDisplay == "none" ? " selected=\"selected\"" : "";?>><?=$l['ND0'];?></option>
			<option value="info"<?=isset($postDisplay) && $postDisplay == "info" ? " selected=\"selected\"" : "";?>><?=$l['ND1'];?></option>
			<option value="success"<?=isset($postDisplay) && $postDisplay == "success" ? " selected=\"selected\"" : "";?>><?=$l['ND2'];?></option>
			<option value="warning"<?=isset($postDisplay) && $postDisplay == "warning" ? " selected=\"selected\"" : "";?>><?=$l['ND3'];?></option>
			<option value="error"<?=isset($postDisplay) && $postDisplay == "error" ? " selected=\"selected\"" : "";?>><?=$l['ND4'];?></option>
		</select><br />
		<textarea name="text" style="width:100%;height:250px;resize:none;" class="form-control"><?=isset($postText) ? str_replace('\r\n', '&#13;&#10;', $postText) : "";?></textarea>

		<input type="file" class="form-control" name="upload_files[]" multiple style="margin-top: 20px;">

		<div class="checkbox">
		    <label>
		      <input type="checkbox" name="sticky" value="1"<?=isset($postSticky) && $postSticky ? ' checked="checked"' : '';?>> <?=$l['NOTESTICKY'];?>
		    </label>
		</div>
		<center><a href="./?p=customers&edit=<?=$_GET['edit'];?>&tab=notes" class="btn btn-default"><?=$l['BACKTOLIST'];?></a>&nbsp;<input type="submit" <?php if (!$ari->check(20)) {
                    echo "disabled";
                }
                ?> value="<?=$l['CREATENOTE'];?>" name="new_note" class="btn btn-primary"></center>
	  </form>
	 </div>
	</div></div><br />
	<?php
} else {
                ?>

<?php if (isset($_GET['delete_note']) && $ari->check(20) && $db->query("DELETE FROM client_notes WHERE ID = '" . $db->real_escape_string($_GET['delete_note']) . "' AND user = " . $u->ID) && $db->affected_rows > 0) {
                    echo "<div class=\"alert alert-success\">{$l['NOTEDEL']}</div>";
                    alog("customers", "note_deleted", $u->ID, $_GET['delete_note']);
                }

                if (isset($_POST['delete_selected_notes']) && $ari->check(20) && is_array($_POST['note'])) {
                    $d = 0;
                    foreach ($_POST['note'] as $id) {
                        if ($db->query("DELETE FROM client_notes WHERE ID = '" . $db->real_escape_string($id) . "' AND user = " . $u->ID) && $db->affected_rows > 0) {
                            $d++;
                            alog("customers", "note_deleted", $u->ID, $id);
                        }
                    }

                    if ($d == 1) {
                        echo "<div class=\"alert alert-success\">{$l['NOTEDEL1']}</div>";
                    } else if ($d > 0) {
                        echo "<div class=\"alert alert-success\">" . str_replace("%d", $d, $l['NOTEDELX']) . "</div>";
                    }

                }?>
<div class="table-responsive">
<table class="table table-bordered table-striped">
<tr>
<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
<th><?=$l['NOTETITLE'];?></th>
<th><?=$l['DATE'];?></th>
<th><?=$l['LASTCHANGE'];?></th>
<th><?=$l['STAFFMEMBER'];?></th>
<th width="<?=$ari->check(20) ? "60px" : "35px";?>"></th>
</tr>

<form method="POST">
<?php
$sql = $db->query("SELECT * FROM client_notes WHERE user = " . $u->ID . " ORDER BY priority DESC, title DESC, ID DESC");

                if ($sql->num_rows <= 0) {
                    echo "<tr><td colspan=\"6\"><center>{$l['NOTESNT']}</center></td></tr>";
                } else {
                    while ($note = $sql->fetch_object()) {
                        $adminSql = $db->query("SELECT name FROM admins WHERE ID = " . $note->admin);

                        switch ($note->priority) {
                            case "0":
                                $title = $note->title;
                                break;

                            case "1":
                                $title = "<font color=\"blue\">" . $note->title . "</font>";
                                break;

                            case "2":
                                $title = "<font color=\"orange\">" . $note->title . "</font>";
                                break;

                            case "3":
                                $title = "<font color=\"red\">" . $note->title . "</font>";
                                break;

                            default:
                                $title = $note->title;
                        }
                        ?>
		<tr>
			<td><input type="checkbox" class="checkbox" name="note[]" value="<?=$note->ID;?>" onchange="javascript:toggle();" /></td>
			<td><?=$title;?><?php if ($note->sticky) {?> <i class="fa fa-thumb-tack"></i><?php }?></td>
			<td><?=$dfo->format($note->time);?></td>
			<td><?=$dfo->format($note->last_changed);?></td>
			<td><?=$adminSql->num_rows == 1 ? "<a href='?p=admin&id={$note->admin}'>" . $adminSql->fetch_object()->name . "</a>" : "<i>{$l['NOTENOADMIN']}</i>";?></td>
			<td width="<?=$ari->check(20) ? "60px" : "35px";?>"><a href="./?p=customers&edit=<?=$_GET['edit'];?>&tab=notes&note=<?=$note->ID;?>"><i class="fa fa-pencil-square-o fa-lg"></i></a><?php if ($ari->check(20)) {?>&nbsp;&nbsp;<a href="#" onclick="deleteN(<?=$note->ID;?>); return false;"><i class="fa fa-times fa-lg"></i></a><?php }?></td>
		</tr>
		<?php
}

                    $additionalJS .= "function deleteN(id) {
		swal({
			title: '{$l['NOTEDELDIA']}',
			text: '{$l['AREYOUSURE']}',
			type: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#DD6B55',
			confirmButtonText: '{$l['YES']}',
			cancelButtonText: '{$l['NO']}',
			closeOnConfirm: false
		}, function(){
			window.location = '?p=customers&edit={$u->ID}&tab=$tab&delete_note=' + id;
		});
	}";
                }
                ?>

</table>
</div><?=$l['SELECTED'];?>: <input type="submit" name="delete_selected_notes" class="btn btn-danger" value="<?=$l['DELETE'];?>" />
</form><?php }?>
</div><?php } else if ($ari->check(10) && $tab == "files") {

            if (isset($_POST['do_upload'])) {
                $access = isset($_POST['customer_access']) && $_POST['customer_access'] == "yes" ? 1 : 0;
                $sentFiles = $_FILES['upload_files'];
                $done = 0;
                foreach ($sentFiles['tmp_name'] as $k => $v) {
                    if (is_uploaded_file($sentFiles['tmp_name'][$k])) {
                        $rand = rand(10000000, 99999999);
                        $filePath = __DIR__ . "/../../files/customers/" . $rand . "_" . basename($sentFiles['name'][$k]);
                        if (move_uploaded_file($sentFiles['tmp_name'][$k], $filePath)) {
                            $db->query("INSERT INTO client_files (`user`, `filename`, `filepath`, `user_access`) VALUES (" . $u->ID . ", '" . $db->real_escape_string(basename($sentFiles['name'][$k])) . "', '" . $db->real_escape_string($rand . "_" . basename($sentFiles['name'][$k])) . "', $access)");
                            alog("customers", "file_uploaded", $u->ID, $db->insert_id, basename($sentFiles['name'][$k]));
                            $done++;
                        }
                    }
                }

                if ($done == 1) {
                    $suc = $l['UPL1'];
                } else {
                    $suc = str_replace("%d", $done, $l['UPLX']);
                }

            } else if (isset($_GET['unlock'])) {
                $db->query("UPDATE client_files SET user_access = 1 WHERE ID = '" . $db->real_escape_string($_GET['unlock']) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $suc = $l['CUSTCANSEEFILE'];
                    alog("customers", "file_access", $u->ID, $_GET['unlock'], "1");
                }
            } else if (isset($_GET['lock'])) {
                $db->query("UPDATE client_files SET user_access = 0 WHERE ID = '" . $db->real_escape_string($_GET['lock']) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $suc = $l['CUSTCANNOTSEEFILE'];
                    alog("customers", "file_access", $u->ID, $_GET['unlock'], "0");
                }
            } else if (isset($_GET['delete'])) {
                $sql = $db->query("SELECT * FROM client_files WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
                if ($sql->num_rows == 1) {
                    $info = $sql->fetch_object();
                    $sql = $db->query("DELETE FROM client_files WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
                    if ($db->affected_rows > 0 && unlink(__DIR__ . "/../../files/customers/" . basename($info->filepath))) {
                        $suc = $l['FILEDELETED'];
                        alog("customers", "file_deleted", $u->ID, $_GET['delete']);
                    }
                }
            } else if (isset($_POST['delete_selected_files']) && is_array($_POST['files'])) {
                $d = 0;
                foreach ($_POST['files'] as $id) {
                    $sql = $db->query("SELECT * FROM client_files WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                    if ($sql->num_rows == 1) {
                        $info = $sql->fetch_object();
                        $sql = $db->query("DELETE FROM client_files WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                        if ($db->affected_rows > 0 && unlink(__DIR__ . "/../../files/customers/" . basename($info->filepath))) {
                            $d++;
                            alog("customers", "file_deleted", $u->ID, $id);
                        }
                    }
                }

                if ($d == 1) {
                    $suc = $l['FILEDELETED1'];
                } else if ($d > 0) {
                    $suc = str_replace("%d", $d, $l['FILEDELETEDX']);
                }

            } else if (isset($_POST['lock_selected_files']) && is_array($_POST['files'])) {
                $d = 0;
                foreach ($_POST['files'] as $id) {
                    $db->query("UPDATE client_files SET user_access = 0 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                    if ($db->affected_rows > 0) {
                        $d++;
                        alog("customers", "file_access", $u->ID, $id, "0");
                    }
                }

                if ($d == 1) {
                    $suc = $l['FILELOCKED1'];
                } else if ($d > 0) {
                    $suc = str_replace("%d", $d, $l['FILELOCKEDX']);
                }

            } else if (isset($_POST['unlock_selected_files']) && is_array($_POST['files'])) {
                $d = 0;
                foreach ($_POST['files'] as $id) {
                    $db->query("UPDATE client_files SET user_access = 1 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                    if ($db->affected_rows > 0) {
                        $d++;
                        alog("customers", "file_access", $u->ID, $id, "1");
                    }
                }

                if ($d == 1) {
                    $suc = $l['FILEUNLOCKED1'];
                } else if ($d > 0) {
                    $suc = str_replace("%d", $d, $l['FILEUNLOCKEDX']);
                }

            }

            if (isset($_POST['ID']) && isset($_POST['expiry'])) {
                $expire = !empty($_POST['expiry']) ? intval(strtotime($_POST['expiry'])) : "-1";
                $db->query("UPDATE client_files SET expire = " . $expire . " WHERE ID = " . intval($_POST['ID']) . " AND user = " . intval($_GET['edit']));
                die("exdate_ok");
            }

            $sql = $db->query("SELECT * FROM client_files WHERE user = " . $u->ID . " ORDER BY ID DESC");
            ?>
<div class="tab-pane" id="tab3">

<?=isset($suc) ? "<div class=\"alert alert-success\">$suc</div>" : "";?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$l['FILE'];?></th>
		<th width="30%"><?=$l['EXPIRYDATE'];?></th>
		<th><?=$l['ACCESSRIGHTS'];?></th>
		<th width="60px"></th>
	</tr>
	<form method="POST">
	<?php
if ($sql->num_rows <= 0) {
                ?>
		<tr>
			<td colspan="5"><center><?=$l['FILENT'];?></center></td>
		</tr>
	<?php
} else {
                while ($file = $sql->fetch_object()) {
                    ?>
			<tr>
				<td><input type="checkbox" class="checkbox" name="files[]" value="<?=$file->ID;?>" onchange="javascript:toggle();" /></td>
				<td><a href="./?p=customers&edit=<?=intval($_GET['edit']);?>&tab=files&download=<?=urlencode($file->filepath);?>" target="_blank"><?=htmlentities($file->filename);?></a> <a href="./?p=customers&edit=<?=intval($_GET['edit']);?>&tab=files&send=<?=urlencode($file->filepath);?>"><i class="fa fa-envelope-o"></i></a></td>
				<td>
					<div class="input-group" data-id="<?=$file->ID;?>">
						<span class="input-group-addon"><i class="fa fa-calendar exdate_icon"></i></span>
						<input type="text" class="form-control datepicker exdate" placeholder="<?=$l['NEVER'];?>" value="<?=$file->expire >= 0 ? date("d.m.Y", $file->expire) : "";?>">
						<span class="input-group-addon"><a href="#" class="remove_exdate"><i class="fa fa-times"></i></a></span>
					</div>
				</td>
				<td><?=$file->user_access == 1 ? "<font color=\"green\">{$l['CUSTOMER']}</font>" : "<font color=\"red\">{$l['STAFFMEMBER']}</font>";?></td>
				<td width="60px"><a href="./?p=customers&edit=<?=intval($_GET['edit']);?>&tab=files&<?=$file->user_access == 1 ? "" : "un";?>lock=<?=$file->ID;?>"><i class="fa fa-lg fa-<?=$file->user_access == 1 ? "" : "un";?>lock"></i></a>&nbsp;&nbsp;<a onclick="return confirm('<?=$l['READELFILEIR'];?>');" href="./?p=customers&edit=<?=intval($_GET['edit']);?>&tab=files&delete=<?=$file->ID;?>"><i class="fa fa-lg fa-times"></i></a></td>
			</tr>
			<?php
}
            }
            ?>

	<script>
	function save_exdate(td, val) {
		if (!td.find(".exdate_icon").hasClass("fa-calendar")) {
			return false;
		}

		td.find(".exdate").val(val).prop("disabled", true);
		td.find(".exdate_icon").removeClass("fa-calendar").addClass("fa-spinner fa-pulse");

		$.post("?p=customers&edit=<?=intval($_GET['edit']);?>&tab=files", {
			"ID": td.data("id"),
			"expiry": val,
			"csrf_token": "<?=CSRF::raw();?>",
		}, function (r) {
			if (r.substr(-9) == "exdate_ok") {
				td.find(".exdate").prop("disabled", false);
				td.find(".exdate_icon").addClass("fa-calendar").removeClass("fa-spinner fa-pulse");
			}
		});
	}

	$(".remove_exdate").click(function(e) {
		e.preventDefault();
		save_exdate($(this).parent().parent(), "");
	});

	$(".exdate").on('dp.hide', function() {
		save_exdate($(this).parent(), $(this).val());
	});
	</script>
</table></div>
<?=$l['SELECTED'];?>: <input type="submit" name="lock_selected_files" class="btn btn-warning" value="<?=$l['LOCKCA'];?>" /> <input type="submit" name="unlock_selected_files" class="btn btn-success" value="<?=$l['FREECA'];?>" /> <input type="submit" name="delete_selected_files" class="btn btn-danger" value="<?=$l['DELETE'];?>" />
	</form>
</div><?php } else if ($ari->check(30) && $tab == "projects") {

            $paid = $sql = $db->query("SELECT SUM(entgelt) AS s FROM projects WHERE user = " . $u->ID . " AND status = 1")->fetch_object()->s;
            $unpaid = $sql = $db->query("SELECT SUM(entgelt) AS s FROM projects WHERE user = " . $u->ID . " AND status != 1")->fetch_object()->s;
            ?>
<div class="tab-pane" id="tab5">

<div class="row">
	<div class="col-lg-4 col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($paid + $unpaid), $cur->getBaseCurrency());?></div>
						<div><?=$l['SUM'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($paid), $cur->getBaseCurrency());?></div>
						<div><?=$l['DONE'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-4">
		<div class="panel panel-warning">
			<div class="panel-heading">
				<div class="row">
					<div class="col-xs-12 text-right">
						<div class="huge"><?=$cur->infix($nfo->format($unpaid), $cur->getBaseCurrency());?></div>
						<div><?=$l['UNDONE'];?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row">
<div class="col-md-12">
<?php
if ($ari->check(31) && isset($_GET['project_ok'])) {
                $db->query("UPDATE projects SET status = 1 WHERE status = 0 AND user = " . $u->ID . " AND ID = '" . $db->real_escape_string($_GET['project_ok']) . "' LIMIT 1");
                alog("project", "mark_done", $_GET['project_ok']);
                $suc = $l['PROJDONE'];
            }

            if ($ari->check(32) && isset($_GET['project_del'])) {
                $db->query("DELETE FROM  projects WHERE user = " . $u->ID . " AND ID = '" . $db->real_escape_string($_GET['project_del']) . "' LIMIT 1");
                $db->query("DELETE FROM  project_tasks WHERE project = '" . $db->real_escape_string($_GET['project_del']) . "'");
                alog("project", "delete", $_GET['project_del']);
                $suc = $l['PROJDEL'];
            }

            if (isset($suc)) {?><div class="alert alert-success"><?=$suc;?></div><?php }?>

<?php
$t = new Table("SELECT * FROM projects WHERE user = " . $u->ID, [
                "name" => [
                    "name" => $l['PROJNAME'],
                    "type" => "like",
                ],
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => [
                        "0" => $lang['VIEW_PROJECT']['S3'],
                        "2" => $lang['VIEW_PROJECT']['S1'],
                        "3" => $lang['VIEW_PROJECT']['S2'],
                        "1" => $lang['VIEW_PROJECT']['S4'],
                    ],
                ],
            ]);

            echo $t->getHeader();
            ?>
  <div class="table table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th><?=$l['ISDUE'];?></th>
			<th><?=$l['PROJNAME'];?></th>
			<th><?=$l['STAFFMEMBER'];?></th>
			<th><?=$l['TASKS'];?></th>
			<th><?=$l['ENTGELT'];?></th>
			<?php if ($ari->check(31) || $ari->check(32)) {?><th width="80px"></th><?php }?>
		</tr>

		<?php $e = false;

            $sql = $t->qry("status ASC, due ASC");

            while ($r = $sql->fetch_object()) {$e = true;

                $style = "";
                if ($r->status == 1) {
                    $style = "background-color:palegreen !important;";
                } else if (strtotime($r->due) < time() && $r->status == 0) {
                    $style = "background-color: khaki !important;";
                }

                $ins = $db->query("SELECT ID FROM project_tasks WHERE project = " . $r->ID)->num_rows;
                $ok = $db->query("SELECT ID FROM project_tasks WHERE project = " . $r->ID . " AND status = 1")->num_rows;

                if ($r->admin == 0) {
                    $adm = "<i>{$l['PROJNA']}</i>";
                } else if ($db->query("SELECT name FROM admins WHERE ID = " . $r->admin . " LIMIT 1")->num_rows == 1) {
                    $adm = "<a href='?p=admin&id={$r->admin}'>" . htmlentities($db->query("SELECT name FROM admins WHERE ID = " . $r->admin . " LIMIT 1")->fetch_object()->name) . "</a>";
                } else {
                    $adm = "<i>{$l['PROJNE']}</i>";
                }

                ?>
		<tr>
			<td style="<?=$style;?>"><?=$dfo->format(strtotime($r->due), false);?></td>
			<td style="<?=$style;?>"><?=$r->name;?><?php if ($r->status == "2") {?> <small><font color="orange">(<?=$l['PJS0'];?>)</font></small><?php }?><?php if ($r->status == "3") {?> <small><font color="orange">(<?=$l['PJS1'];?>)</font></small><?php }?></td>
			<td style="<?=$style;?>"><?=$adm;?></td>
			<td style="<?=$style;?>"><?=$ok;?> (<?=$ins;?>)</td>
			<td style="<?=$style;?>"><?=$cur->infix($nfo->format($r->entgelt), $cur->getBaseCurrency());?></td>
			<?php if ($ari->check(31) || $ari->check(32)) {?><td width="80px"><?php if ($ari->check(31)) {?><a href="?p=view_project&id=<?=$r->ID;?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;<?php }if ($r->status == 0 && $ari->check(31)) {?><a href="?p=customers&edit=<?=$u->ID;?>&tab=projects&project_ok=<?=$r->ID;?>"><i class="fa fa-check-square-o"></i></a>&nbsp;&nbsp;<?php }if ($ari->check(32)) {?><a onclick="return confirm('<?=$l['PROJREADEL'];?>');" href="?p=customers&edit=<?=$u->ID;?>&tab=projects&project_del=<?=$r->ID;?>"><i class="fa fa-minus-square-o"></i></a><?php }?></td><?php }?>
		</tr>
		<?php }if (!$e) {?>
		<tr>
			<td colspan="7"><center><?=$l['PROJNT'];?></center></td>
		</tr><?php }?>
	</table></div>
</div></div>
</div><?php echo $t->getFooter();} else {
            $r = $addons->runHook("AdminCustomerContent", ["tab" => $tab, "user" => User::getInstance($u->ID, "ID")]);
            $f = false;
            foreach ($r as $i) {
                if (empty($i)) {
                    continue;
                }

                echo $i;
                $f = true;
            }
            if (!$f) {
                echo '<div class="alert alert-danger">' . $l['TPWNF'] . '</div>';
            }

        } ?>
</div>

		</div>
</div>
<?php

    } else {

        if (isset($_GET['lock']) && $ari->check(10)) {
            $db->query("UPDATE clients SET locked = 1 WHERE ID = '" . $db->real_escape_string($_GET['lock']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['CUSTLOCKED'];
                alog("customers", "lock", $_GET['lock']);
                $addons->runHook("CustomerLocked", [
                    "user" => User::getInstance($_GET['lock'], "ID"),
                ]);
            }
        }

        if (isset($_POST['lock_selected_customers']) && is_array($_POST['customer']) && $ari->check(10)) {
            $d = 0;
            foreach ($_POST['customer'] as $id) {
                $db->query("UPDATE clients SET locked = 1 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("customers", "lock", $id);
                    $addons->runHook("CustomerLocked", [
                        "user" => User::getInstance($id, "ID"),
                    ]);
                }
            }

            if ($d == 1) {
                $suc = $l['CUSTLOCKED1'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['CUSTLOCKEDX']);
            }

        }

        if (isset($_GET['unlock']) && $ari->check(10)) {
            $db->query("UPDATE clients SET locked = 0 WHERE ID = '" . $db->real_escape_string($_GET['unlock']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['CUSTUNLOCKED'];
                alog("customers", "unlock", $_GET['unlock']);
                $addons->runHook("CustomerUnlocked", [
                    "user" => User::getInstance($_GET['unlock'], "ID"),
                ]);
            }
        }

        if (isset($_POST['pw_selected_customers']) && is_array($_POST['customer']) && $ari->check(10)) {
            $d = 0;
            foreach ($_POST['customer'] as $id) {
                $userInstance = User::getInstance($id, "ID");
                if (!$userInstance) {
                    continue;
                }

                $pwd = $userInstance->generatePassword();
                $language = $userInstance->getLanguage();

                $mtObj = new MailTemplate("Neues Passwort");
                $title = $mtObj->getTitle($language);
                $mail = $mtObj->getMail($language, $userInstance->get()['name']);

                $maq->enqueue([
                    "mail" => $userInstance->get()['mail'],
                    "pwd" => $pwd,
                    "password" => $pwd,
                ], $mtObj, $userInstance->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $userInstance->get()['ID'], false, 0, 0, $mtObj->getAttachments($language));
                alog("customers", "new_pw_sent", $id);

                $addons->runHook("CustomerChangePassword", [
                    "user" => User::getInstance($u->ID, "ID"),
                    "source" => "admin_newpw",
                ]);

                $d++;
            }

            if ($d == 1) {
                $suc = $l['CUSTPWSENT1'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['CUSTPWSENTX']);
            }

        }

        if (isset($_POST['unlock_selected_customers']) && is_array($_POST['customer']) && $ari->check(10)) {
            $d = 0;
            foreach ($_POST['customer'] as $id) {
                $db->query("UPDATE clients SET locked = 0 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("customers", "unlock", $id);
                    $addons->runHook("CustomerUnlocked", [
                        "user" => User::getInstance($id, "ID"),
                    ]);
                }
            }

            if ($d == 1) {
                $suc = $l['CUSTUNLOCKED1'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['CUSTUNLOCKEDX']);
            }

        }

        if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0 && $ari->check(12)) {
            $oldId = $db->real_escape_string($_GET['delete']);

            // Delete old user records
            foreach (User::getRelations() as $table => $key) {
                $db->query("DELETE FROM $table WHERE `$key` = '$oldId'");
            }

            $db->query("UPDATE clients SET affiliate = 0 WHERE affiliate = '$oldId'");
            $db->query("DELETE FROM  clients WHERE ID = '$oldId' LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['CUSTDEL'];
                alog("customers", "delete", $_GET['delete']);

                $addons->runHook("CustomerDeleted", [
                    "id" => $_GET['delete'],
                ]);
            }
        }

        if (isset($_POST['delete_selected_customers']) && is_array($_POST['customer']) && $ari->check(12)) {
            $d = 0;
            foreach ($_POST['customer'] as $id) {
                // Delete old user records
                foreach (User::getRelations() as $table => $key) {
                    $db->query("DELETE FROM $table WHERE `$key` = '$oldId'");
                }

                $db->query("UPDATE clients SET affiliate = 0 WHERE affiliate = '$oldId'");
                $db->query("DELETE FROM  clients WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("customers", "delete", $id);

                    $addons->runHook("CustomerDeleted", [
                        "id" => $id,
                    ]);
                }
            }

            if ($d == 1) {
                $suc = $l['CUSTDEL1'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['CUSTDELX']);
            }

        }

        // Merge two clients
        if (isset($_POST['do_merge'])) {
            try {
                if (!isset($_POST['merge_old']) || !isset($_POST['merge_new']) || !is_numeric($_POST['merge_old']) || !is_numeric($_POST['merge_new'])) {
                    throw new Exception($l['MERERR1']);
                }

                if ($_POST['merge_new'] == $_POST['merge_old']) {
                    throw new Exception($l['MERERR2']);
                }

                $okSql = $db->query("SELECT ID FROM clients WHERE ID = '" . $db->real_escape_string($_POST['merge_old']) . "' OR ID = '" . $db->real_escape_string($_POST['merge_new']) . "'");
                if ($okSql->num_rows != 2) {
                    throw new Exception($l['MERERR3']);
                }

                $oldId = $db->real_escape_string(intval($_POST['merge_old']));
                $newId = $db->real_escape_string(intval($_POST['merge_new']));

                // Change user relationships
                foreach (User::getRelations(false) as $table => $key) {
                    $db->query("UPDATE $table SET `$key` = '$newId' WHERE `$key` = '$oldId'");
                }

                // Copy cart items
                $oldCartSql = $db->query("SELECT * FROM client_cart WHERE user = '$oldId'");
                if ($oldCartSql->num_rows > 0) {
                    while ($oldCart = $oldCartSql->fetch_object()) {
                        $newCartSql = $db->query("SELECT ID, qty FROM client_cart WHERE user = '$newId' AND type = '" . $oldCart->type . "' AND relid = '" . $oldCart->relid . "' AND license = '" . $oldCart->license . "' LIMIT 1");
                        if ($newCartSql->num_rows == 1) {
                            $dataset = $newCartSql->fetch_object()->ID;
                            $db->query("UPDATE client_cart SET qty = qty + " . $oldCart->qty . " WHERE ID = $dataset LIMIT 1");
                            $db->query("DELETE FROM client_cart WHERE ID = $dataset LIMIT 1");
                        } else {
                            $db->query("UPDATE cart SET user = '$newId' WHERE ID = " . $oldCart->ID . " LIMIT 1");
                        }
                    }
                }

                // Merge credit and delete old customer
                $oldCredit = $db->query("SELECT credit FROM clients WHERE ID = '$oldId' LIMIT 1")->fetch_object()->credit;
                $oldAfCredit = $db->query("SELECT affiliate_credit FROM clients WHERE ID = '$oldId' LIMIT 1")->fetch_object()->affiliate_credit;
                $db->query("UPDATE clients SET credit = credit + $oldCredit, affiliate_credit = affiliate_credit + $affiliateCredit WHERE ID = '$newId' LIMIT 1");
                $db->query("DELETE FROM clients WHERE ID = '$oldId' LIMIT 1");

                alog("customers", "merge", $newId, $oldId);

                $suc = $l['MERGEDSUC'];
            } catch (Exception $ex) {
                $err = $ex->getMessage() . '<br /><br /><a data-toggle="modal" data-target="#merge" href="#">' . $l['BACKTOFORM'] . '</a>';
            }
        }

        ?>

<?php
$sql = $db->query("SELECT * FROM clients ORDER BY ID ASC");
        ?>
	<div class="row">
		<div class="col-lg-12">
<h1 class="page-header"><?=$l['CUSTOMERS'];?> <small><?=$sql->num_rows;?></small><?php if ($ari->check(10)) {?><a href="?p=add_customer" class="pull-right"><i class="fa fa-plus-circle"></i></a><?php }?></h1>
			<?php
$countries = [];
        $sql = $db->query("SELECT ID, name FROM client_countries ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            $countries[$row->ID] = $row->name;
        }

        $currencies = [];
        $sql = $db->query("SELECT currency_code, name FROM currencies ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            $currencies[$row->currency_code] = $row->name;
        }

        $cso = [];
        foreach (unserialize($CFG['CUST_SOURCE']) as $cs) {
            foreach ($cs as $n) {
                if (!empty($n)) {
                    $n = htmlentities($n);
                    $cso[$n] = $n;
                }
            }
        }

        $t = new Table("SELECT * FROM clients", [
            "ID" => [
                "name" => $l['CUSTNR'],
                "type" => "like",
                "trim" => $CFG['CNR_PREFIX'] ?: "#",
            ],
            "salutation" => [
                "name" => $l['SALUTATION'],
                "type" => "select",
                "options" => [
                    "MALE" => $l['MR'],
                    "FEMALE" => $l['MRS'],
                    "DIVERS" => $l['DIVERS'],
                    "" => $l['NA'],
                ],
            ],
            "firstname" => [
                "name" => $l['FIRSTNAME'],
                "type" => "like",
            ],
            "lastname" => [
                "name" => $l['LASTNAME'],
                "type" => "like",
            ],
            "mail" => [
                "name" => $l['MAILADDRESS'],
                "type" => "like",
            ],
            "company" => [
                "name" => $l['COMPANY'],
                "type" => "like",
            ],
            "vatid" => [
                "name" => $l['EUVATID'],
                "type" => "like",
            ],
            "street" => [
                "name" => $l['STREET'],
                "type" => "like",
            ],
            "street_number" => [
                "name" => $l['STREETNR'],
                "type" => "like",
            ],
            "postcode" => [
                "name" => $l['POSTCODE'],
                "type" => "like",
            ],
            "city" => [
                "name" => $l['CITY'],
                "type" => "like",
            ],
            "country" => [
                "name" => $l['COUNTRY'],
                "type" => "select",
                "options" => $countries,
            ],
            "telephone" => [
                "name" => $l['TELEPHONE'],
                "type" => "like",
            ],
            "fax" => [
                "name" => $l['FAXNUM'],
                "type" => "like",
            ],
            "language" => [
                "name" => $l['LANGUAGE'],
                "type" => "select",
                "options" => $languages,
            ],
            "currency" => [
                "name" => $l['CURRENCY'],
                "type" => "select",
                "options" => $currencies,
            ],
            "locked" => [
                "name" => $l['STATUS'],
                "type" => "select",
                "options" => [
                    "0" => $l['ACTIVE'],
                    "1" => ucfirst($l['LOCKED']),
                ],
            ],
            "verified" => [
                "name" => $l['VERIFIED'],
                "type" => "select",
                "options" => [
                    "1" => $l['YES'],
                    "0" => $l['NO'],
                ],
            ],
            "cust_source" => [
                "name" => $l['CUST_SOURCE'],
                "type" => "select",
                "options" => $cso,
            ],
        ], ["ID", "ASC"], "clients");

        echo $t->getHeader();
        ?>

			<?php if (isset($err)) {?><div class="alert alert-danger"><b><?=$lang['GENERAL']['ERROR'];?></b> <?=$err;?></div><?php }?>
			<?php if (isset($suc)) {?><div class="alert alert-success"><?=$suc;?></div><?php }?>
			<div class="table-responsive"><table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
					<th width="50px"><center><?=$t->orderHeader("ID", "#");?></center></th>
					<th><?=$t->orderHeader("CONCAT(firstname, lastname)", $l['CUSTNAME']);?></th>
					<th><?=$t->orderHeader("company", $l['COMPANY']);?></th>
					<th><?=$t->orderHeader("mail", $l['MAILADDRESS']);?></th>
					<th><?=$t->orderHeader("credit", $l['ACCBALANCE']);?></th>
					<th><?=$l['OPENAMOUNTS'];?></th>
					<th><?=$l['PRODUCTS'];?></th>
					<th><?=$l['DOMAINS'];?></th>
					<?php if ($ari->check(8) || $ari->check(10) || $ari->check(11) || $ari->check(12)) {?><th width="125px"></th><?php }?>
				</tr>

				<form method="POST">
				<?php
$sql = $t->qry("ID ASC");
        if ($sql->num_rows == 0) {
            ?>
					<tr>
						<td colspan="10"><center><?=$l['NOCUSTOMERS'];?></center></td>
					</tr>
				<?php
} else {
            $additionalJS .= "function deleteCustomer(id) {
						swal({
							title: '{$l['RELDELCUST']}',
							text: '{$l['RELDELCUST2']}',
							type: 'warning',
							showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: '{$l['YES']}',
							cancelButtonText: '{$l['NO']}',
							closeOnConfirm: false
						}, function(){
							window.location = '?p=customers&page=" . $page . "&delete=' + id;
						});
					}";

            while ($u = $sql->fetch_object()) {
                $open = 0;
                $inv = new Invoice;
                $sql2 = $db->query("SELECT ID FROM invoices WHERE client = " . $u->ID . " AND status = 0");
                while ($row = $sql2->fetch_object()) {
                    $inv->load($row->ID);
                    $open += $inv->getAmount();
                }
                $uI = User::getInstance($u->ID, "ID");
                ?>
						<tr>
							<td><input type="checkbox" class="checkbox" name="customer[]" value="<?=$u->ID;?>" onchange="javascript:toggle();"></td>
							<td><center><a href="?p=customers&edit=<?=$u->ID;?>" style="color: inherit;"><?=htmlentities($CFG['CNR_PREFIX']);?><?=$u->ID;?></a></center></td>
							<td><a href="?p=customers&edit=<?=$u->ID;?>" style="color: inherit;"><?=$u->verified == 1 ? "<i class='fa fa-star'></i> " : "";?><?php if ($u->locked == 1) {
                    echo "<font color=\"red\">";
                }
                ?><?=$uI->getfName();?><?php if ($u->locked == 1) {
                    echo "</font>";
                }
                ?></a></td>
							<td><?=trim($u->company) == "" ? "<i>{$l['NOCOMPANY']}</i>" : htmlentities($u->company);?></td>
							<td><a href="mailto:<?=$u->mail;?>"><?=$u->mail;?></a></td>
							<td><?php if ($u->credit < 0) {
                    echo "<font color=\"red\">";
                } else if ($u->credit > 0) {
                    echo "<font color=\"green\">";
                }
                ?><?=$cur->infix($nfo->format($u->credit), $cur->getBaseCurrency());?><?php if ($u->credit != 0) {
                    echo "</font>";
                }
                ?></td>
							<td><?php if ($open > 0) {
                    echo "<font color=\"red\">";
                }
                ?><?=$cur->infix($nfo->format($open), $cur->getBaseCurrency());?><?php if ($open > 0) {
                    echo "</font>";
                }
                ?></td>
							<td><?php
$furtherSQL = $db->query("SELECT * FROM client_products WHERE user = " . $u->ID);
                $free = 0;
                $locked = 0;

                while ($v = $furtherSQL->fetch_array()) {
                    if ($v['active'] == 1) {
                        $free++;
                    } else {
                        $locked++;
                    }

                }

                echo $free . " (" . ($free + $locked) . ")";
                ?></td>
							<td><?php echo $db->query("SELECT COUNT(*) AS c FROM domains WHERE user = {$u->ID} AND status IN ('KK_OK', 'REG_OK')")->fetch_object()->c . " (" . $db->query("SELECT COUNT(*) AS c FROM domains WHERE user = {$u->ID}")->fetch_object()->c . ")"; ?></td>
							<?php if ($ari->check(8) || $ari->check(10) || $ari->check(11) || $ari->check(12)) {?><td width="125px"><?php if ($ari->check(8)) {?><a href="?p=customers&edit=<?=$u->ID;?>"><i class="fa fa-edit fa-lg"></i></a>&nbsp;&nbsp;<?php }?>
							<?php if ($ari->check(10)) {?><a href="?p=customers&page=<?=$page;?>&<?php if ($u->locked == 1) {
                    echo "un";
                }
                    ?>lock=<?=$u->ID;?>"><i class="fa fa-<?php if ($u->locked == 1) {
                        echo "un";
                    }
                    ?>lock fa-lg"></i></a>&nbsp;&nbsp;<?php }?>
							<?php if ($ari->check(11)) {?><a onclick="return confirm('<?=$l['REALLOGIN'];?>');" href="?p=customers&login=<?=$u->ID;?>" target="_blank"><i class="fa fa-key fa-lg"></i></a>&nbsp;&nbsp;<?php }?>
							<?php if ($ari->check(12)) {?><a onclick="deleteCustomer(<?=$u->ID;?>); return false;" href="#"><i class="fa fa-times fa-lg"></i></a><?php }?></td><?php }?>
						</tr>
					<?php
}
        }
        ?>
			</table></div><?=$l['SELECTED'];?>: <input type="submit" name="lock_selected_customers" class="btn btn-warning" value="<?=$l['LOCKC'];?>" /> <input type="submit" name="unlock_selected_customers" class="btn btn-success" value="<?=$l['UNLOCKC'];?>" /> <input type="submit" name="pw_selected_customers" class="btn btn-default" value="<?=$l['SENDNEWPW'];?>" /> <input type="submit" name="delete_selected_customers" class="btn btn-danger" value="<?=$l['DELETE'];?>" /><br /><br /></form></div>

			<?php echo $t->getFooter(); ?>
		</div></div>
		<!-- /.col-lg-12 -->
	</div>

<?php if ($ari->check(10)) {?>

<div class="modal fade" id="merge" tabindex="-1" role="dialog" aria-labelledby="editLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['MERGECLIENTS'];?></h4>
      </div>
      <form method="POST" action="./?p=customers">
      <div class="modal-body">
		<div class="alert alert-warning"><?=$l['MERGECLIENTSHINT'];?></div>
		<label><?=$l['SOURCECUST'];?></label>
		<select name="merge_old" class="form-control">
			<option><?=$l['PCC'];?></option>
			<?php
$custSql = $db->query("SELECT ID, firstname, lastname, company FROM clients ORDER BY firstname ASC, lastname ASC");
            if ($custSql->num_rows > 0) {
                while ($cust = $custSql->fetch_object()) {
                    echo '<option value="' . $cust->ID . '"' . (((isset($_POST['merge_old']) && $_POST['merge_old'] == $cust->ID) || (!isset($_POST['merge_old']) && isset($_GET['merge']) && $_GET['merge'] == $cust->ID)) ? " selected=\"selected\"" : "") . '>' . htmlentities($cust->firstname) . " " . htmlentities($cust->lastname) . (trim($cust->company) != "" ? " (" . htmlentities($cust->company) . ")" : "") . '</option>';
                }
            }

            ?>
		</select>
		<br />
		<label><?=$l['TARGETCUST'];?></label>
		<select name="merge_new" class="form-control">
			<option><?=$l['PCC'];?></option>
			<?php
$custSql = $db->query("SELECT ID, firstname, lastname, company FROM clients ORDER BY firstname ASC, lastname ASC");
            if ($custSql->num_rows > 0) {
                while ($cust = $custSql->fetch_object()) {
                    echo '<option value="' . $cust->ID . '"' . (((isset($_POST['merge_new']) && $_POST['merge_new'] == $cust->ID) || (!isset($_POST['merge_new']) && isset($_GET['merge']) && $_GET['merge'] == $cust->ID)) ? " selected=\"selected\"" : "") . '>' . htmlentities($cust->firstname) . " " . htmlentities($cust->lastname) . (trim($cust->company) != "" ? " (" . htmlentities($cust->company) . ")" : "") . '</option>';
                }
            }

            ?>
		</select>
	  </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button> <input style="margin-left: 0;" type="submit" name="do_merge" class="btn btn-primary" value="<?=$l['DOMERGE'];?>" />
      </div>
      </form>
    </div>
  </div>
<?php if (isset($_GET['merge'])) {
                $additionalJS .= "$('#merge').modal();";
            }?>
<?php }
    }
}
?>
