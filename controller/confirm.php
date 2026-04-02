<?php
// Global some variables for security reasons
global $var, $session, $user, $db, $CFG, $_GET, $maq, $lang, $sec, $pars, $val, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (isset($pars[0]) && isset($pars[1]) && isset($pars[2]) && isset($pars[3]) && isset($pars[4])) {
    $_GET['firstname'] = $pars[0];
    $_GET['lastname'] = $pars[1];
    $_POST['email'] = $_GET['mail'] = str_replace(",", ".", $pars[2]);
    $_GET['ts'] = $pars[3];
    $_GET['hash'] = $pars[4];
}

// This page can only be accessed from guests
if ($var['logged_in'] != 0) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";

} else if ($var['ca_disabled']) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
    $var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

    $title = $lang['REGISTER']['BTITLE'];
    $tpl = "confirm";

    // We have to get the email from parameters and use it as $_POST for compatibility reasons
    $_POST['email'] = urldecode($_GET['mail']);
    $q = $db->query("SELECT * FROM clients WHERE mail LIKE '" . $db->real_escape_string(urldecode($_GET['mail'])) . "'");
    if (md5($CFG['HASH'] . urldecode($_GET['firstname']) . urldecode($_GET['lastname']) . $_POST['email'] . $_GET['ts']) != $_GET['hash'] || $_GET['ts'] < time()) {
        // The security hash is not correct
        $var['error'] = $lang['CONFIRM']['LINK'];
        $var['donotshow'] = 1;
    } else if ($q->num_rows > 0) {
        // It is not possible to use one email address more than one time
        $var['error'] = $lang['CONFIRM']['ALREADY_USED'];
        $var['donotshow'] = 1;
    } else if (!isset($_POST['email']) || !$val->email($_POST['email'])) {
        // The passed email address is not correct / cannot be resolved
        $var['error'] = $lang['CONFIRM']['INCORRECT'];
        $var['donotshow'] = 1;
    } else {
        $var['cust_source'] = $db->query("SELECT 1 FROM client_fields WHERE ID = 100 AND active = 1")->num_rows;
        $var['cust_source_duty'] = $db->query("SELECT 1 FROM client_fields WHERE ID = 100 AND duty = 1")->num_rows;
        $var['cso'] = [];

        foreach (unserialize($CFG['CUST_SOURCE']) as $cs) {
            $mycs = $cs[$CFG['LANG']] ?? "";
            if (!empty($mycs)) {
                $var['cso'][] = $mycs;
            }
        }

        if (isset($_POST['setpw'])) {
            $pcs = $_POST['cust_source'] ?? "";
            $cso = $var['cso'];
            if (!$var['cust_source_duty']) {
                $cso[] = "";
            }

            // The customer have entered the password
            if ($_POST['pwd'] != $_POST['pwd2']) {
                // The passwords are not the same
                $var['error'] = $lang['CONFIRM']['PW_INEQUAL'];
            } else if (strlen($_POST['pwd']) < 8) {
                // The password is not long enough (limit of 8 is hardcoded)
                $var['error'] = $lang['CONFIRM']['PW_LENGTH'];
            } else if ($var['cust_source'] && !in_array($pcs, $cso)) {
                $var['error'] = $lang['CUST_SOURCE']['FAIL'];
            } else {
                // Get client IP address
                $ip = ip();

                // Affiliate
                $affiliate = 0;
                if (!empty($_COOKIE['affiliate']) && is_numeric($_COOKIE['affiliate']) && User::getInstance($_COOKIE['affiliate'], "ID")) {
                    $affiliate = intval($_COOKIE['affiliate']);
                }

                // Get standard newsletter
                $sql = $db->query("SELECT ID FROM newsletter_categories WHERE standard = 1");
                $nl = array();
                while ($row = $sql->fetch_object()) {
                    array_push($nl, $row->ID);
                }

                $nl = $db->real_escape_string(implode("|", $nl));
                if (empty($_POST['newsletter'])) {
                    $nl = "";
                }

                $cgroup = 0;
                if ($CFG['DEFAULT_CGROUP'] && $db->query("SELECT 1 FROM client_groups WHERE ID = " . intval($CFG['DEFAULT_CGROUP']))->num_rows) {
                    $cgroup = intval($CFG['DEFAULT_CGROUP']);
                }

                // If all is correct, we insert the new user into database and get the new ID for further use
                $salt = $sec->generateSalt();
                $limit = doubleval($CFG['POSTPAID_DEF']);
                $db->query("INSERT INTO clients (`cgroup`, `mail`, `firstname`, `lastname`, `pwd`, `salt`, `registered`, `last_login`, `last_ip`, `affiliate`, newsletter, postpaid, cust_source) VALUES ($cgroup, '" . $db->real_escape_string($_POST['email']) . "', '" . $db->real_escape_string(urldecode($_GET['firstname'])) . "', '" . $db->real_escape_string(urldecode($_GET['lastname'])) . "', '" . $db->real_escape_string($sec->hash($_POST['pwd'], $salt)) . "', '" . $db->real_escape_string($salt) . "', '" . time() . "', '" . time() . "', '" . $db->real_escape_string($ip) . "', $affiliate, '$nl', $limit, '" . $db->real_escape_string($pcs) . "')");
                $newID = $db->insert_id;
                $db->query("INSERT INTO ip_logs (time, user, ip) VALUES (" . time() . ", " . $newID . ", '" . $db->real_escape_string($ip) . "')");

                // We will assign all mail sent before to the customers email
                $db->query("UPDATE client_mails SET user = $newID WHERE recipient = '" . $db->real_escape_string($_POST['email']) . "'");

                $hash = $sec->hash($_POST['pwd'], $salt, $_POST['password_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING'] == 1);
                if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                    $hash = md5($_POST['pwd']);
                }

                // Get customer informationen into template
                $user = new User($_POST['email']);
                $cart = new Cart($user->get()['ID']);
                $cart->importSession();

                if ($CFG['USER_CONFIRMATION'] != 1) {
                    $var['logged_in'] = 1;
                    $var['user'] = $user->get();
                    $var['cart'] = $cart->get();
                    $var['cart_count'] = $cart->count();
                }

                // Set a few variables for confirm.tpl
                $var['success'] = $CFG['USER_CONFIRMATION'] != 1 ? $lang['CONFIRM']['CREATED'] : $lang['CONFIRM']['WAITING'];
                $var['donotshow'] = 1;

                // Insert into log and enqueue a mail to new customer
                $mtObj = new MailTemplate("Konto wurde aktiviert");

                $titlex = $mtObj->getTitle($CFG['LANG']);
                $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

                $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                $user->log("Hat sich registriert");

                // Login the customer if no user confirmation is required
                if ($CFG['USER_CONFIRMATION'] != 1) {
                    $session->set('mail', $user->get()['mail']);
                    if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                        $session->set('pwd', md5($user->get()['pwd']));
                    } else {
                        $session->set('pwd', $user->get()['pwd']);
                    }

                }

                // Send admin notification/s
                if (($ntf = AdminNotification::getInstance("Neuer Kunde")) !== false) {
                    $ntf->set("name", $user->get()['name']);
                    $ntf->set("email", $user->get()['mail']);
                    $ntf->set("cid", $user->get()['ID']);
                    $ntf->send();
                }

                $addons->runHook("CustomerCreated", [
                    "user" => $user,
                ]);
            }
        }
    }

}
