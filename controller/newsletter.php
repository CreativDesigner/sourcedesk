<?php
global $user, $var, $db, $CFG, $maq, $pars;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if ($var['ca_disabled']) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
    $var['error'] = $lang['GENERAL']['BLOCKED'];

} else {

    $title = $lang['NEWSLETTER']['TITLE'];
    $tpl = "newsletter";

    // Fill array with newsletter categories
    $sql = $db->query("SELECT ID, name FROM newsletter_categories ORDER BY name ASC");
    $var['nl'] = array();
    while ($row = $sql->fetch_object()) {
        $var['nl'][$row->ID] = $row->name;
    }

    try {
        if (!empty($pars[0]) && $db->query("SELECT 1 FROM newsletter WHERE hash = '" . substr($pars[0], 1) . "'")->num_rows > 0) {
            $info = $db->query("SELECT * FROM newsletter WHERE hash = '" . substr($pars[0], 1) . "'")->fetch_object();
            $status = substr($pars[0], 0, 1);
            if ($status != "0" && $status != "1") {
                throw new Exception($lang['NEWSLETTER']['INVLINK']);
            }

            if ($status == "0") {
                $db->query("DELETE FROM newsletter WHERE email = '" . $db->real_escape_string($info->email) . "'");
                $db->query("UPDATE clients SET newsletter = 0 WHERE mail = '" . $db->real_escape_string($info->email) . "'");
                $var['suc'] = $lang['NEWSLETTER']['END'];
            } else {
                if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($info->email) . "'")->num_rows == 0) {
                    $db->query("UPDATE newsletter SET lists = '" . implode(",", explode("-", $pars[1])) . "', hash = '', language = '" . $db->real_escape_string($CFG['LANG']) . "', conf_time = " . time() . ", conf_ip = '" . $db->real_escape_string(ip()) . "' WHERE hash = '" . $db->real_escape_string($info->hash) . "'");
                } else {
                    $db->query("UPDATE clients SET newsletter = '" . implode("|", explode("-", $pars[1])) . "' WHERE mail = '" . $db->real_escape_string($info->email) . "'");
                    $db->query("DELETE FROM newsletter WHERE email = '" . $db->real_escape_string($info->email) . "'");
                }

                $var['suc'] = $lang['NEWSLETTER']['START'];
            }
        }

        if ($var['logged_in']) {
            if (isset($_POST['save'])) {
                $nl = array();

                if (isset($_POST['nl']) && is_array($_POST['nl'])) {
                    foreach ($_POST['nl'] as $id => $null) {
                        if (array_key_exists($id, $var['nl'])) {
                            $nl[] = $id;
                        }
                    }
                }

                $user->set(array("newsletter" => implode("|", $nl)));
                $user->saveChanges();
                $var['user'] = $user->get();
            }
        } else {
            if (isset($_POST['save'])) {
                $nl = array();

                foreach ($_POST['nl'] as $id => $null) {
                    if (array_key_exists($id, $var['nl'])) {
                        $nl[] = $id;
                    }
                }

                if (count($nl) > 0 && empty($_POST['name'])) {
                    throw new Exception($lang['NEWSLETTER']['NN']);
                }

                if (empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception($lang['NEWSLETTER']['NM']);
                }

                if (empty($_POST['disclaimer'])) {
                    throw new Exception($lang['NEWSLETTER']['ND']);
                }

                if (count($nl) > 0) {
                    if ($db->query("SELECT 1 FROM newsletter WHERE email = '" . $db->real_escape_string($_POST['mail']) . "' AND hash = ''")->num_rows > 0) {
                        $db->query("UPDATE newsletter SET lists = '" . implode(",", $nl) . "' WHERE email = '" . $db->real_escape_string($_POST['mail']) . "' AND hash = ''");
                        $var['suc'] = $lang['NEWSLETTER']['GOTIT'];
                    } else if ($db->query("SELECT 1 FROM clients WHERE newsletter != '' AND mail = '" . $db->real_escape_string($_POST['mail']) . "'")->num_rows > 0) {
                        $db->query("UPDATE clients SET newsletter = '" . implode("|", $nl) . "' WHERE mail = '" . $db->real_escape_string($_POST['mail']) . "'");
                        $var['suc'] = $lang['NEWSLETTER']['GOTIT'];
                    } else {
                        $hash = md5(uniqid(mt_rand(), true));
                        $url = $CFG['PAGEURL'] . "newsletter/1$hash/" . implode("-", $nl);
                        $db->query("INSERT INTO newsletter (`name`, `email`, `hash`, `reg_time`, `reg_ip`) VALUES ('" . $db->real_escape_string($_POST['name']) . "', '" . $db->real_escape_string($_POST['mail']) . "', '" . $db->real_escape_string($hash) . "', " . time() . ", '" . $db->real_escape_string(ip()) . "')");

                        $uid = 0;
                        if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($_POST['mail']) . "'")->num_rows > 0) {
                            $uid = $db->query("SELECT ID FROM clients WHERE mail = '" . $db->real_escape_string($_POST['mail']) . "'")->fetch_object()->ID;
                        }

                        $mtObj = new MailTemplate("Newsletter bestätigen");
                        $titlex = $mtObj->getTitle($CFG['LANG']);
                        $mail = $mtObj->getMail($CFG['LANG'], $_POST['name']);
                        $maq->enqueue([
                            "name" => $_POST['name'],
                            "link" => $url, 
                        ], $mtObj, $_POST['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $uid, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                        $var['suc'] = $lang['NEWSLETTER']['SENTCONF'];
                    }
                } else {
                    if ($db->query("SELECT 1 FROM newsletter WHERE email = '" . $db->real_escape_string($_POST['mail']) . "' AND hash = ''")->num_rows == 0 && $db->query("SELECT 1 FROM clients WHERE newsletter != '' AND mail = '" . $db->real_escape_string($_POST['mail']) . "'")->num_rows == 0) {
                        throw new Exception($lang['NEWSLETTER']['ENF']);
                    }

                    $hash = md5(uniqid(mt_rand(), true));
                    $url = $CFG['PAGEURL'] . "newsletter/0$hash";
                    $db->query("INSERT INTO newsletter (`name`, `email`, `hash`, `reg_ip`, `reg_time`) VALUES ('" . $db->real_escape_string($_POST['name']) . "', '" . $db->real_escape_string($_POST['mail']) . "', '" . $db->real_escape_string($hash) . "', '" . $db->real_escape_string(ip()) . "', " . time() . ")");

                    $uid = 0;
                    if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($_POST['mail']) . "'")->num_rows > 0) {
                        $uid = $db->query("SELECT ID FROM clients WHERE mail = '" . $db->real_escape_string($_POST['mail']) . "'")->fetch_object()->ID;
                    }

                    $mtObj = new MailTemplate("Newsletter abbestellen");
                    $titlex = $mtObj->getTitle($CFG['LANG']);
                    $mail = $mtObj->getMail($CFG['LANG'], $_POST['name']);
                    $maq->enqueue([
                        "name" => $_POST['name'],
                        "link" => $url, 
                    ], $mtObj, $_POST['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $uid, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                    $var['suc'] = $lang['NEWSLETTER']['FUBAD'];
                }
            }
        }
    } catch (Exception $ex) {
        $var['err'] = $ex->getMessage();
    }
}
