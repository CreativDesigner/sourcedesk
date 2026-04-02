<?php
// For security reasons, global all needed objects and variables
global $db, $user, $var, $CFG, $lang, $pars, $sec, $raw_cfg;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();
if (!$user->get()['reseller']) {
    header('Location: ' . $CFG['PAGEURL']);
    exit;
}

$tpl = "reseller";
$tab = $pars[0] ?? "customers";

if (!in_array($tab, ["customers", "contracts", "config"])) {
    $tab = "customers";
}

$title = $lang['RESELLER']['TITLE_' . strtoupper($tab)];
$var['tab'] = $tab;
$l = $var['l'] = $lang['RESELLER'];
$var['action'] = $pars[1] ?? "";

if ($tab == "customers") {
    $var['customers'] = [];

    $sql = $db->query("SELECT ID, mail FROM client_customers WHERE uid = {$user->get()['ID']} ORDER BY mail ASC");
    while ($row = $sql->fetch_object()) {
        $var['customers'][$row->ID] = $row->mail;
    }

    if (isset($_GET['login']) && array_key_exists($_GET['login'], $var['customers'])) {
        $id = intval($_GET['login']);
        $key = $db->real_escape_string(time() . "-" . $sec->generatePassword(24, false, "lud"));
        $db->query("UPDATE client_customers SET login = '$key' WHERE ID = $id");
        header('Location: ' . $raw_cfg['PAGEURL'] . 'res/?resid=' . $user->get()['ID'] . '&login=' . $id . '&key=' . $key);
        exit;
    }

    if (isset($_GET['delete']) && array_key_exists($_GET['delete'], $var['customers'])) {
        $id = intval($_GET['delete']);
        $db->query("DELETE FROM client_customers WHERE ID = $id");
        header('Location: ' . $CFG['PAGEURL'] . 'reseller/customers');
        exit;
    }

    $var['contracts'] = [];

    foreach ($var['customers'] as $id => $mail) {
        $var['contracts'][$id] = [];

        $sql = $db->query("SELECT ID FROM client_products WHERE reseller_customer = $id ORDER BY ID ASC");
        while ($row = $sql->fetch_object()) {
            array_push($var['contracts'][$id], "#" . $row->ID . " " . unserialize($user->get()['products_info'])[$row->ID]['name']);
        }
    }

    if ($var['action'] == "add") {
        $var['defpw'] = $sec->generatePassword(8, false, "lud");

        if (isset($_POST['mail'])) {
            if (in_array($_POST['mail'], $var['customers'])) {
                $var['err'] = $l['ERR1'];
            } elseif (!filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
                $var['err'] = $l['ERR2'];
            } elseif (strlen(trim($_POST['password'])) < 8) {
                $var['err'] = $l['ERR3'];
            } else {
                $db->query("INSERT INTO client_customers (uid, mail, password) VALUES ({$user->get()['ID']}, '" . $db->real_escape_string($_POST['mail']) . "', '" . $db->real_escape_string(hash("sha512", $_POST['password'])) . "')");
                header('Location: ' . $CFG['PAGEURL'] . 'reseller/customers');
                exit;
            }
        }
    } elseif ($var['action'] == "edit") {
        $var['defpw'] = $sec->generatePassword(8, false, "lud");

        if (isset($_POST['password']) && !empty($pars[2]) && array_key_exists($pars[2], $var['customers'])) {
            $id = intval($pars[2]);
            if (strlen(trim($_POST['password'])) < 8) {
                $var['err'] = $l['ERR3'];
            } else {
                $db->query("UPDATE client_customers SET `password` = '" . $db->real_escape_string(hash("sha512", $_POST['password'])) . "' WHERE ID = $id");
                header('Location: ' . $CFG['PAGEURL'] . 'reseller/customers');
                exit;
            }
        }
    }
} elseif ($tab == "contracts") {
    $var['contracts'] = unserialize($user->get()['products_info']);
    $var['customers'] = [];

    $sql = $db->query("SELECT ID, mail FROM client_customers WHERE uid = {$user->get()['ID']} ORDER BY mail ASC");
    while ($row = $sql->fetch_object()) {
        $var['customers'][$row->ID] = $row->mail;
    }

    if (isset($_POST['reseller_customer'])) {
        if (!is_array($_POST['reseller_customer'])) {
            $_POST['reseller_customer'] = [];
        }

        foreach ($_POST['reseller_customer'] as $id => $assigned) {
            if (!array_key_exists($id, $var['contracts'])) {
                continue;
            }
            $id = intval($id);

            if (!array_key_exists($assigned, $var['customers'])) {
                $assigned = 0;
            }
            $assigned = intval($assigned);

            $db->query("UPDATE client_products SET reseller_customer = $assigned WHERE ID = $id");
            $var['contracts'][$id]['reseller_customer'] = $assigned;
        }

        $var['suc'] = $l['CSAVED'];
    }
} elseif ($tab == "config") {
    if (isset($_POST['reseller_pagename'])) {
        $user->set(["reseller_pagename" => substr($_POST['reseller_pagename'], 0, 35)]);
        header('Location: ' . $CFG['PAGEURL'] . 'reseller/config');
        exit;
    }
}