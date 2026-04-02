<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob started\n", FILE_APPEND);

// Select all todays birthdays from the database
$sql = $db->query("SELECT ID, mail, birthday, language, firstname, lastname FROM clients WHERE locked = 0 AND birthday_mail = 1 AND birthday LIKE '%" . date("-m-d") . "' AND last_birthday <= '" . date("Y-m-d", time() - 31104000) . "'");
while ($row = $sql->fetch_object()) {
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] User #{$row->ID}\n", FILE_APPEND);
    $user = new User($row->mail);
    $birthyear = date("Y", strtotime($row->birthday));
    $yearsold = date("Y") - $birthyear;
    if ($yearsold > 120) {
        continue;
    }

    $user->set(array('last_birthday' => date("Y-m-d")));

    // Get text to send
    $text = $CFG['BIRTHDAY_TEXT'];
    if (unserialize($text) !== false) {
        $text = unserialize($text);
        if ($user->language != "" && isset($text[$user->language])) {
            $text = $text[$user->language];
        } else if (isset($text[$CFG['LANG']])) {
            $text = $text[$CFG['LANG']];
        } else {
            continue;
        }

    }

    $lang = $user->language == "" ? $CFG['LANG'] : $user->language;
    // Gets the header
    $headerObj = new MailTemplate("Header");
    if ($headerObj->getContent($lang) !== false) {
        $text = $headerObj->getContent($lang) . "\r\n\r\n" . $text;
    }

    // Gets the footer
    $footerObj = new MailTemplate("Footer");
    if ($footerObj->getContent($lang) !== false) {
        $text .= "\r\n\r\n";
        $text .= $footerObj->getContent($lang);
    }

    // Replace name variable in template
    $text = str_replace("%name%", $row->firstname . " " . $row->lastname, $text);

    // Get title to send
    $title = $CFG['BIRTHDAY_TITLE'];
    if (unserialize($title) !== false) {
        $title = unserialize($title);
        if ($user->language != "" && isset($title[$user->language])) {
            $title = $title[$user->language];
        } else if (isset($title[$CFG['LANG']])) {
            $title = $title[$CFG['LANG']];
        } else {
            continue;
        }

    }

    if (!trim($title) || !trim($text)) {
        continue;
    }

    // Get birthday voucher
    if ($CFG['BIRTHDAY_VOUCHER'] != "" && $CFG['BIRTHDAY_VOUCHER'] != 0 && is_numeric($CFG['BIRTHDAY_VOUCHER'])) {
        if (is_object($sql = $db->query("SELECT * FROM vouchers WHERE ID = " . intval($CFG['BIRTHDAY_VOUCHER']))) && $sql->num_rows == 1) {
            $info = $sql->fetch_assoc();

            function array_quote(&$v, $k)
            {
                global $db;
                $v = is_numeric($v) ? $v : "'" . $db->real_escape_string($v) . "'";
            }

            $info['code'] .= "-" . $sec->generatePassword(8, false, 'lud');
            $info['user'] = $row->ID;
            $info['valid_from'] = strtotime(date("d.m.Y"));
            $info['valid_to'] = strtotime(date("d.m.Y")) + 604800; // One week
            $info['max_uses'] = 1;
            $info['max_per_user'] = 1;
            $info['active'] = 1;
            unset($info['ID']);
            array_walk($info, 'array_quote');

            $db->query("INSERT INTO vouchers (" . implode(', ', array_keys($info)) . ") VALUES (" . implode(', ', $info) . ")");
            $myVoucher = $info['code'];
            $text = str_replace("%c", $myVoucher, $text);
            $title = str_replace("%c", $myVoucher, $title);
        }
    }

    $title = str_replace("%y", $yearsold, $title);
    $title = str_replace("%j", $yearsold, $title);
    $text = str_replace("%y", $yearsold, $text);
    $text = str_replace("%j", $yearsold, $text);

    $maq->enqueue([
        "j" => $yearsold,
        "y" => $yearsold,
    ], null, $row->mail, $title, $text, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $row->ID, false);
    $user->log("Geburtstags-Glückwünsche wurden gesendet (Gutschein $myVoucher)", "System");
    file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] User #{$row->ID} finished\n", FILE_APPEND);
}

file_put_contents(__DIR__ . "/" . substr(basename(__FILE__), 0, -4) . ".lock", "[" . date("Y-m-d H:i:s") . "] Cronjob finished\n", FILE_APPEND);
