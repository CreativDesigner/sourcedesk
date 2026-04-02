<?php
// For security reasons, global all needed objects and variables
global $db, $user, $var, $_POST, $session, $CFG, $lang, $dfo, $pars, $provisioning;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$tpl = "bugtracker";
$title = $lang['BUGTRACKER']['TITLE'];

$var['products'] = unserialize($user->get()['software_products_info']);
$already = array();
foreach ($var['products'] as $k => $info) {
    if (in_array($info['product'], $already)) {
        unset($var['products'][$k]);
    } else {
        array_push($already, $info['product']);
    }

}

$var['success'] = false;
$var['new'] = isset($pars[0]) && $pars[0] == "report" ? 1 : 0;
if (empty($_POST['pid']) && !empty($pars[1])) {
    $_POST['pid'] = $pars[1];
}

// If the user send something, we have to check all
if (isset($_POST['submit'])) {
    // Escape passed product ID and check if product in database exist
    $pid = $db->real_escape_string($_POST['pid']);
    $sql = $db->query("SELECT `name` FROM products WHERE ID = '$pid'");
    // Check if files are larger than allowed
    $maxSize = "3145728"; // 3 MB
    if (is_array($_FILES['files'])) {
        foreach ($_FILES['files']['size'] as $size) {
            if ($size > $maxSize) {
                $sizeFail = true;
            }
        }
    }

    $obj = $provisioning->get()['software'];
    $obj->loadOptions($pid, true);
    $bdept = $obj->getOption("bugtracker_dept");

    if ($session->get('aid') != $_POST['aid']) {
        // The form token is not correct
        $var['error'] = $lang['GENERAL']['FORM_TOKEN_ERROR'];
    } else if (trim($_POST['description']) == "" || trim($_POST['reproduce']) == "" || !is_numeric($_POST['pid'])) {
        // Not all required fields are filled
        $var['error'] = $lang['GENERAL']['FORM_INCOMPLETE_ERROR'];
    } else if ($sql->num_rows != 1 || !in_array($pid, unserialize($user->get()['software_products']))) {
        // There was an error with the selected product
        $var['error'] = $lang['BUGTRACKER']['PRODUCT_WRONG'];
    } else if (is_array($_FILES['files']['name']) && count($_FILES['files']['name']) > 5) {
        // There were to many files selected
        $var['error'] = $lang['BUGTRACKER']['TOO_MANY_FILES'];
    } else if (isset($sizeFail)) {
        // At least one of the choosed files was too large
        $var['error'] = $lang['BUGTRACKER']['TOO_LARGE_FILE'];
    } else {
        $info = $sql->fetch_object();
        $name = @unserialize($info->name)[$CFG['LANG']] ?: $info->name;

        // Create ticket
        $desc = nl2br($_POST['description'] ?? "");
        $repr = nl2br($_POST['reproduce'] ?? "");

        $desc = str_replace("\r\n", "", $desc);
        $repr = str_replace("\r\n", "", $repr);

        $title = $db->real_escape_string(str_replace("%n", $name, $lang['BUGTRACKER']['TICKETTITLE']));
        $dept = intval($bdept != 0 ? $bdept : $CFG['BUGTRACKER_DEPT']);

        $text = $db->real_escape_string("<b>" . $lang['GENERAL']['DESCRIPTION'] . ":</b><br />" . $desc . "<br /><br /><b>" . $lang['BUGTRACKER']['STEPS_REPRODUCE'] . ":</b><br />" . $repr);

        $fromc = $user->get()['name'] . " <" . $user->get()['mail'] . ">";
        $db->query("INSERT INTO support_tickets (subject, dept, created, updated, priority, sender, customer, cc, status) VALUES ('$title', $dept, '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', 3, '" . $db->real_escape_string($fromc) . "', " . intval($user->get()['ID']) . ", '', 0)");
        $tid = $db->insert_id;

        $sql = $db->prepare("INSERT INTO support_ticket_answers (ticket, `time`, subject, message, priority, sender, staff) VALUES (?,?,?,?,?,?,?)");
        $sql->bind_param("isssisi", $tid, $a = date("Y-m-d H:i:s"), $title, $text, $prio = "3", $d = "Bugtracker", $e = 0);
        $sql->execute();
        $mid = $db->insert_id;

        // Handle file uploads
        if (is_array($_FILES['files']) && count($_FILES['files']['name']) > 0) {
            foreach ($_FILES['files']['name'] as $k => $name2) {
                if (empty($name2) || !is_uploaded_file($_FILES['files']['tmp_name'][$k])) {
                    continue;
                }

                $path = basename(time() . "-" . rand(10000000, 99999999) . "-" . $name2);
                file_put_contents(__DIR__ . "/../files/tickets/$path", file_get_contents($_FILES['files']['tmp_name'][$k]));
                $db->query("INSERT INTO support_ticket_attachments (message, name, file) VALUES ($mid, '" . $db->real_escape_string($name2) . "', 'file#" . $db->real_escape_string($path) . "')");
            }
        }

        // Insert bug request into database and get insert ID for log purposes
        $db->query("INSERT INTO bugtracker (`user`, `date`, `product`, `ticket`) VALUES ('" . $user->get()['ID'] . "', '" . date('Y-m-d H:i:s') . "', '" . $db->real_escape_string($_POST['pid']) . "', $tid)");
        $var['success'] = true;
        $user->log("Bug gemeldet (Ticket #$tid)");
        $var['new'] = 0;

        // Send admin notification/s
        if (($ntf = AdminNotification::getInstance("Neuer Bug")) !== false) {
            $ntf->set("product", $name);
            $ntf->set("customer", $user->get()['name']);
            $ntf->send();
        }
    }
}

// Set form token for new call
$session->set('aid', mt_rand(10000000, 99999999));
$var['aid'] = $session->get('aid');

// Get all bugs for the logged in user and iterate it into an array
$sql = $db->query("SELECT * FROM bugtracker WHERE user = " . $user->get()['ID']);
$mybugs = array();

while ($r = $sql->fetch_object()) {
    // Get the product name
    $psql = $db->query("SELECT name FROM products WHERE ID = " . $r->product);
    if ($psql->num_rows == 1) {
        $r->product = unserialize($psql->fetch_object()->name)[$CFG['LANG']];
    } else {
        $r->product = "<i>" . $lang['GENERAL']['DELETED'] . "</i>";
    }

    $r->date = $dfo->format(strtotime($r->date));

    // Give the current state
    $t = new Ticket($r->ticket);
    $r->done = $t->getStatusStr();
    $r->url = $CFG['PAGEURL'] . "ticket/" . $r->ticket . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $r->ticket . "ticketview" . $CFG['HASH']), -16);

    // Make the ID six numbers long
    while (strlen($r->ticket) < 6) {
        $r->ticket = "0" . $r->ticket;
    }

    $r = (array) $r;
    array_push($mybugs, $r);
}
$var['bugs'] = $mybugs;
