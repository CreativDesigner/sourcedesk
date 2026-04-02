<?php
// For security reasons, global all needed objects and variables
global $db, $user, $var, $_POST, $session, $CFG, $lang, $_REQUEST, $maq, $pars, $provisioning;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$_REQUEST['id'] = $_POST['id'] = $_GET['id'] = isset($pars[0]) ? intval($pars[0]) : 0;

// The product have to exist and the user must have a license of the product
if (!isset(unserialize($user->get()['products_info'])[$_REQUEST['id']]) || unserialize($user->get()['products_info'])[$_REQUEST['id']]['active'] != 1) {

    $tpl = "error";
    $title = $lang['ERROR']['TITLE'];

} else {

    $tpl = "help";
    $title = $lang['HELP']['TITLE'];

    // Get information about the selected license
    $userLicenses = unserialize($user->get()['products_info']);
    $license = $userLicenses[$_REQUEST['id']];
    $var['license'] = $license;

    if (isset($_POST['send_message'])) {
        // Send message to support
        if ($_POST['aid'] != $session->get('aid')) {
            // Wrong token
            $var['msg'] = "<div class=\"alert alert-danger\">" . $lang['GENERAL']['FORM_TOKEN_ERROR'] . "</div>";
        } else if (trim($_POST['message']) == "") {
            // Nothing entered
            $var['msg'] = "<div class=\"alert alert-danger\">" . $lang['HELP']['NO_MESSAGE'] . "</div>";
        } else {
            $info = $db->query("SELECT `name` FROM products WHERE ID = " . $license["product"]);
            if ($info->num_rows) {
                $info = $info->fetch_object();
                $obj = $provisioning->get()['software'];
                $obj->loadOptions($license['product'], true);
                $obj->bugtracker_dept = $obj->getOption("bugtracker_dept");
            } else {
                $info = (object) ["bugtracker_dept" => 0, "name" => ""];
            }

            $name = @unserialize($info->name) ? unserialize($info->name)[$CFG['LANG']] : $name;

            $dept = intval($info->bugtracker_dept != 0 ? $info->bugtracker_dept : $CFG['BUGTRACKER_DEPT']);
            $subject = "Nachricht: $name";
            $fromc = $user->get()['name'] . " <" . $user->get()['mail'] . ">";
            $text = str_replace("\r\n", "", nl2br($_POST['message']));

            $db->query("INSERT INTO support_tickets (subject, dept, created, updated, priority, sender, customer, cc, status, rating, customer_access) VALUES ('" . $db->real_escape_string($subject) . "', " . intval($dept) . ", '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', 3, '" . $db->real_escape_string($fromc) . "', " . intval($user->get()['ID']) . ", '', 0, -1, 0)");
            $tid = $db->insert_id;

            $sql = $db->prepare("INSERT INTO support_ticket_answers (ticket, `time`, subject, message, priority, sender, staff) VALUES (?,?,?,?,?,?,?)");
            $sql->bind_param("isssisi", $tid, $a = date("Y-m-d H:i:s"), $subject, $text, $prio = 3, $d = "Webinterface", $e = 0);
            $sql->execute();
            $mid = $db->insert_id;

            $var['msg'] = "<div class=\"alert alert-success\">" . $lang['HELP']['SENT'] . "</div>";
            unset($_POST);
        }
    } else if (isset($_POST['save_credentials'])) {
        // Save encrypted credentials
        if ($_POST['aid'] != $session->get('aid')) {
            // Wrong token
            $var['msg'] = "<div class=\"alert alert-danger\">" . $lang['GENERAL']['FORM_TOKEN_ERROR'] . "</div>";
        } else if (trim($_POST['credentials']) == "") {
            // Nothing entered
            $var['msg'] = "<div class=\"alert alert-danger\">" . $lang['HELP']['NO_CREDENTIALS'] . "</div>";
        } else {
            $info = $db->query("SELECT `name` FROM products WHERE ID = " . $license["product"]);
            if ($info->num_rows) {
                $info = $info->fetch_object();
                $obj = $provisioning->get()['software'];
                $obj->loadOptions($license['product'], true);
                $obj->bugtracker_dept = $obj->getOption("bugtracker_dept");
            } else {
                $info = (object) ["bugtracker_dept" => 0, "name" => ""];
            }

            $name = @unserialize($info->name) ? unserialize($info->name)[$CFG['LANG']] : $name;

            $dept = intval($info->bugtracker_dept != 0 ? $info->bugtracker_dept : $CFG['BUGTRACKER_DEPT']);
            $subject = "Zugangsdaten: $name";
            $fromc = $user->get()['name'] . " <" . $user->get()['mail'] . ">";
            $text = str_replace("\r\n", "", nl2br($_POST['credentials']));

            $db->query("INSERT INTO support_tickets (subject, dept, created, updated, priority, sender, customer, cc, status, rating, customer_access) VALUES ('" . $db->real_escape_string($subject) . "', " . intval($dept) . ", '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', 3, '" . $db->real_escape_string($fromc) . "', " . intval($user->get()['ID']) . ", '', 0, -1, 0)");
            $tid = $db->insert_id;

            $sql = $db->prepare("INSERT INTO support_ticket_answers (ticket, `time`, subject, message, priority, sender, staff) VALUES (?,?,?,?,?,?,?)");
            $sql->bind_param("isssisi", $tid, $a = date("Y-m-d H:i:s"), $subject, $text, $prio = 3, $d = "Webinterface", $e = 0);
            $sql->execute();
            $mid = $db->insert_id;

            // Insert cipher
            $db->query("INSERT INTO credentials (`user`, `product`, `text`, `time`) VALUES (" . $user->get()['ID'] . ", " . $license['product'] . ", '$encrypted', " . time() . ")");
            $id = $db->insert_id;

            $var['msg'] = "<div class=\"alert alert-success\">" . $lang['HELP']['SENT_INFO'] . "</div>";
            unset($_POST);
        }
    }

    // Generate token
    $var['aid'] = rand(10000000, 99999999);
    $session->set('aid', $var['aid']);
}
