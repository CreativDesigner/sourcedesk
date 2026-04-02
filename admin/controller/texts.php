<?php
global $ari, $var, $db, $CFG, $languages, $lang, $adminLanguages;
header("X-XSS-Protection: 0");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['TEXTS']['TITLE']);
menu("settings");

// Check rights
if (!$ari->check(42) && !$ari->check(36) && !$ari->check(37)) {
    alog("general", "insufficient_page_rights", "texts");
    $tpl = "error";
} else {
    $tpl = "texts";
    $edit = array(
        "terms" => array("title" => $lang['TEXTS']['TERMS'], "rights" => 36),
        "withdrawal_rules" => array("title" => $lang['TEXTS']['WITHDRAWAL_RULES'], "rights" => 36),
        "privacy_policy" => array("title" => $lang['TEXTS']['PRIVACY_POLICY'], "rights" => 36),
        "license_texts" => array("title" => $lang['TEXTS']['LICENSE_TEXTS'], "rights" => 37),
        "imprint" => array("title" => $lang['TEXTS']['IMPRINT'], "rights" => 42),
	);
	
	if (isset($_GET['terms_date'])) {
		$new = $_GET['terms_date'] ? 1 : 0;
		$db->query("UPDATE settings SET `value` = $new WHERE `key` = 'terms_date'");
		alog("texts", "terms_date_changed", $new);
		die("ok");
	}

	if (isset($_GET['terms_history'])) {
		$new = $_GET['terms_history'] ? 1 : 0;
		$db->query("UPDATE settings SET `value` = $new WHERE `key` = 'terms_history'");
		alog("texts", "terms_history_changed", $new);
		die("ok");
	}

    foreach ($edit as $k => $v) {
        if (!$ari->check($v['rights'])) {
            unset($edit[$k]);
        }
    }

    $var['edit'] = $edit;

    $t = $var['t'] = isset($_GET['t']) && isset($edit[$_GET['t']]) ? $_GET['t'] : array_keys($edit)[0];

    if ($t == "terms") {
        if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM  terms_of_service WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $var['msg'] = "<div class='alert alert-success'>" . $lang['TEXTS']['TERM_DELETED'] . "</div>";
                alog("texts", "t_deleted", intval($_GET['delete']));
            }
        } else if (isset($_GET['action']) && $_GET['action'] == "add") {
            if (isset($_POST['add_terms'])) {
                $text = array();
                foreach ($adminLanguages as $lk => $ln) {
                    if (isset($_POST['terms_' . $lk]) && !empty(trim($_POST['terms_' . $lk]))) {
                        $text[$lk] = $_POST['terms_' . $lk];
                    }
                }

                if (count($text) > 0) {
                    $text = serialize($text);
                    $db->query("INSERT INTO terms_of_service (time, text) VALUES (" . time() . ", '" . $db->real_escape_string($text) . "')");
                    $var['msg'] = '<div class="alert alert-success">' . $lang['TEXTS']['TERMS_ADDED'] . '</div>';
                    unset($_GET['action']);
                    unset($_POST);
                    alog("texts", "t_added", intval($db->insert_id));
                }
            }
        } else if (isset($_GET['edit']) && $_GET['edit'] > 0 && $db->query("SELECT ID FROM terms_of_service WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->num_rows == 1) {
            if (isset($_POST['save_terms']) && $_POST['id'] > 0) {
                $text = array();
                foreach ($adminLanguages as $lk => $ln) {
                    if (isset($_POST['terms_' . $lk]) && !empty(trim($_POST['terms_' . $lk]))) {
                        $text[$lk] = $_POST['terms_' . $lk];
                    }
                }

                if (count($text) > 0) {
                    $text = serialize($text);
                    $db->query("UPDATE terms_of_service SET text = '" . $db->real_escape_string($text) . "' WHERE ID = '" . $db->real_escape_string($_POST['id']) . "' LIMIT 1");
                    if ($_POST['change_time']) {
                        $db->query("UPDATE terms_of_service SET time = '" . time() . "' WHERE ID = '" . $db->real_escape_string($_POST['id']) . "' LIMIT 1");
                    }

                    $var['msg'] = '<div class="alert alert-success">' . $lang['TEXTS']['TERMS_SAVED'] . '</div>';
                    unset($_POST);
                    alog("texts", "t_saved", intval($_POST['id']));
                }
            }

            $info = $db->query("SELECT * FROM terms_of_service WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->fetch_object();
            $var['texts'] = unserialize($info->text);
        }

        $sql = $db->query("SELECT * FROM terms_of_service ORDER BY ID DESC");
        $var['terms'] = array();
        while ($row = $sql->fetch_assoc()) {
            $row['text'] = unserialize($row['text'])[$CFG['LANG']];
            $row['excerpt'] = substr(strip_tags($row['text']), 0, 50) . (strlen(strip_tags($row['text'])) > 50 ? "..." : "");
            $var['terms'][$row['ID']] = $row;
        }
    } else if ($t == "license_texts") {
        if (isset($_POST['save'])) {
            $name_e = $text_e = $name_r = $text_r = array();
            foreach ($languages as $k => $v) {
                if (isset($_POST['e_' . $k])) {
                    $text_e[$k] = $_POST['e_' . $k];
                }

                if (isset($_POST['ename_' . $k])) {
                    $name_e[$k] = $_POST['ename_' . $k];
                }

                if (isset($_POST['r_' . $k])) {
                    $text_r[$k] = $_POST['r_' . $k];
                }

                if (isset($_POST['rname_' . $k])) {
                    $name_r[$k] = $_POST['rname_' . $k];
                }

            }

            $name_e = serialize($name_e);
            $text_e = serialize($text_e);
            $name_r = serialize($name_r);
            $text_r = serialize($text_r);

            $db->query("UPDATE license_texts SET text = '" . $db->real_escape_string($text_e) . "', name = '" . $db->real_escape_string($name_e) . "' WHERE type = 'e' LIMIT 1");
            $db->query("UPDATE license_texts SET text = '" . $db->real_escape_string($text_r) . "', name = '" . $db->real_escape_string($name_r) . "' WHERE type = 'r' LIMIT 1");
            $var['done'] = 1;
            unset($_POST);
            alog("texts", "license_saved");
        }

        // Select texts
        $single = $reseller = array();
        $sql = $db->query("SELECT * FROM license_texts");

        while ($r = $sql->fetch_object()) {
            foreach (unserialize($r->name) as $k => $v) {
                $v = htmlentities($v);
                if ($r->type == "r") {
                    $reseller[$k]['name'] = $v;
                } else {
                    $single[$k]['name'] = $v;
                }

            }
            foreach (unserialize($r->text) as $k => $v) {
                $v = htmlentities($v);
                if ($r->type == "r") {
                    $reseller[$k]['text'] = $v;
                } else {
                    $single[$k]['text'] = $v;
                }

            }
        }

        $var['single'] = $single;
        $var['reseller'] = $reseller;
    } else if ($t == "imprint") {
        // Check if the user wants to save the imprint
        if (isset($_POST['save'])) {
            // Write the language data into an Array
            $arr = array();
            foreach ($languages as $lang_key => $lang_name) {
                if (isset($_POST['text_' . $lang_key]) && !empty(trim($_POST['text_' . $lang_key]))) {
                    $arr[$lang_key] = $_POST['text_' . $lang_key];
                }
            }

            $arr = $db->real_escape_string(serialize($arr));

            $db->query("UPDATE settings SET `value` = '" . $arr . "' WHERE `key` = 'imprint' LIMIT 1");
            if ($db->errno) {
                die($db->error);
            }

            unset($_POST);
            $var['done'] = 1;

            $CFG['IMPRINT'] = $db->query("SELECT `value` FROM settings WHERE `key` = 'imprint' LIMIT 1")->fetch_object()->value;
            alog("texts", "imprint_saved");
        }

        // Iterate the language data into an Array
        $var['imprint'] = array();

        foreach ($languages as $lang_key => $lang_title) {
            $var['imprint'][$lang_key] = array(
                'lang_title' => $lang_title,
                'imprint' => unserialize($CFG['IMPRINT'])[$lang_key],
                'post_key' => "text_" . $lang_key,
            );
        }
    } else if ($t == "withdrawal_rules") {
        // Check if the user wants to save the withdrawal rules
        if (isset($_POST['save'])) {
            // Write the language data into an Array
            $arr = array();
            foreach ($languages as $lang_key => $lang_name) {
                if (isset($_POST['text_' . $lang_key]) && !empty(trim($_POST['text_' . $lang_key]))) {
                    $arr[$lang_key] = $_POST['text_' . $lang_key];
                }
            }

            $arr = $db->real_escape_string(serialize($arr));

            $db->query("UPDATE settings SET `value` = '" . $arr . "' WHERE `key` = 'withdrawal_rules' LIMIT 1");
            if ($db->errno) {
                die($db->error);
            }

            $var['done'] = 1;

            $CFG['WITHDRAWAL_RULES'] = $db->query("SELECT `value` FROM settings WHERE `key` = 'withdrawal_rules' LIMIT 1")->fetch_object()->value;

            if (!empty($_POST['reconfirm'])) {
                $db->query("UPDATE clients SET withdrawal_rules = 0");
            }

            unset($_POST);

            alog("texts", "wr_saved");
        }

        // Iterate the language data into an Array
        $var['imprint'] = array();

        foreach ($languages as $lang_key => $lang_title) {
            $var['imprint'][$lang_key] = array(
                'lang_title' => $lang_title,
                'imprint' => unserialize($CFG['WITHDRAWAL_RULES'])[$lang_key],
                'post_key' => "text_" . $lang_key,
            );
        }

    } else if ($t == "privacy_policy") {
        // Check if the user wants to save the withdrawal rules
        if (isset($_POST['save'])) {
            // Write the language data into an Array
            $arr = array();
            foreach ($languages as $lang_key => $lang_name) {
                if (isset($_POST['text_' . $lang_key]) && !empty(trim($_POST['text_' . $lang_key]))) {
                    $arr[$lang_key] = $_POST['text_' . $lang_key];
                }
            }

            $arr = $db->real_escape_string(serialize($arr));

            $db->query("UPDATE settings SET `value` = '" . $arr . "' WHERE `key` = 'privacy_policy' LIMIT 1");
            if ($db->errno) {
                die($db->error);
            }

            $var['done'] = 1;

            $CFG['PRIVACY_POLICY'] = $db->query("SELECT `value` FROM settings WHERE `key` = 'privacy_policy' LIMIT 1")->fetch_object()->value;

            if (!empty($_POST['reconfirm'])) {
                $db->query("UPDATE clients SET privacy_policy = 0");
            }

            unset($_POST);

            alog("texts", "pp_saved");
        }

        // Iterate the language data into an Array
        $var['imprint'] = array();

        foreach ($languages as $lang_key => $lang_title) {
            $var['imprint'][$lang_key] = array(
                'lang_title' => $lang_title,
                'imprint' => unserialize($CFG['PRIVACY_POLICY'])[$lang_key],
                'post_key' => "text_" . $lang_key,
            );
        }

    }

}
