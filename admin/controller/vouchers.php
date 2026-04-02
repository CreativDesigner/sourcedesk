<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $adminInfo, $lang, $sec, $cur;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['VOUCHERS']['TITLE']);
menu("payments");

// Function for get possibilities for use voucher
function getPossibilities()
{
    global $CFG, $lang, $db;

    $valid_for_possibilities = array();
    $sql1 = $db->query("SELECT ID, name FROM `products` ORDER BY ID ASC");
    $sql3 = $db->query("SELECT ID, tld FROM `domain_pricing` ORDER BY `top` > 0 DESC, `top` ASC, `tld` ASC");

    while ($product = $sql1->fetch_object()) {
        $valid_for_possibilities["product" . $product->ID] = $lang['VOUCHERS']['PRODUCT'] . " - " . unserialize($product->name)[$CFG['LANG']];
    }
    asort($valid_for_possibilities);

    $valid_for_domains = array();
    while ($domain = $sql3->fetch_object()) {
        $valid_for_domains["reg" . $domain->ID] = $lang['VOUCHERS']['DOMAIN'] . " - ." . $domain->tld . " - " . $lang['VOUCHERS']['REG'];
        $valid_for_domains["kk" . $domain->ID] = $lang['VOUCHERS']['DOMAIN'] . " - ." . $domain->tld . " - " . $lang['VOUCHERS']['KK'];
    }

    $valid_for_possibilities = array_merge($valid_for_possibilities, $valid_for_domains);

    return $valid_for_possibilities;
}

// Check admin rights
if ($ari->check(56)) {
    $tpl = "vouchers";
    $var['tab'] = isset($_GET['tab']) ? $_GET['tab'] : "active";

    $myCur = new Currency($cur->getBaseCurrency());
    $lang['VOUCHERS']['FIXED'] = $myCur->getName();
    $var['lang'] = $lang;

    $var['cid'] = isset($_POST['voucher_user']) ? $_POST['voucher_user'] : 0;

    if (isset($_GET['edit']) && is_object($sql2 = $db->query("SELECT * FROM vouchers WHERE ID = " . intval($_GET['edit']))) && $sql2->num_rows == 1) {
        alog("vouchers", "viewed", $_GET['edit']);
        $var['edit'] = true;
        $var['data'] = $sql2->fetch_assoc();
        $var['valid_for_possibilities'] = $valid_for_possibilities = getPossibilities();

        $users = array();
        $sql2 = $db->query("SELECT ID, firstname, lastname, mail FROM clients ORDER BY firstname ASC, lastname ASC");
        while ($row = $sql2->fetch_object()) {
            $users[$row->ID] = $row->firstname . " " . $row->lastname . " (" . $row->mail . ")";
        }

        $var['users'] = $users;

        if (!isset($_POST['voucher_user'])) {
            $var['cid'] = $var['data']->user;
        }

        if (isset($_POST['edit_voucher'])) {
            $data = array();
            foreach ($_POST as $k => $v) {
                if (substr(strtolower($k), 0, 8) != "voucher_") {
                    continue;
                }

                if (!is_array($v)) {
                    $data[strtolower(substr($k, 8))] = $db->real_escape_string(trim($v));
                } else {
                    $data[strtolower(substr($k, 8))] = $v;
                }

            }
            $data = (object) $data;

            try {
                if (!isset($data->code) || strlen($data->code) < 1) {
                    throw new Exception($lang['VOUCHERS']['NO_CODE']);
                }

                if ($db->query("SELECT ID FROM vouchers WHERE code = '{$data->code}' AND ID != {$var['data']['ID']} LIMIT 1")->num_rows != 0) {
                    throw new Exception($lang['VOUCHERS']['CODE_EXISTS']);
                }

                if (!isset($data->value) || !is_numeric($data->value = $nfo->phpize($data->value)) || $data->value <= 0) {
                    throw new Exception($lang['VOUCHERS']['INVALID_VALUE']);
                }

                if (!isset($data->type)) {
                    throw new Exception($lang['VOUCHERS']['TYPE_MISSING']);
                }

                if (!isset($data->max_uses) || !is_numeric($data->max_uses)) {
                    throw new Exception($lang['VOUCHERS']['MAX_USES_MISSING']);
                }

                $data->max_uses = intval($data->max_uses);

                if (!isset($data->max_per_user) || !is_numeric($data->max_per_user)) {
                    throw new Exception($lang['VOUCHERS']['MAX_PER_USER_MISSING']);
                }

                $data->max_per_user = intval($data->max_per_user);

                if ($data->user === "") {
                    $data->user = 0;
                }

                if (!isset($data->user) || !is_numeric($data->user) || ($data->user != 0 && !isset($users[$data->user]))) {
                    throw new Exception($lang['VOUCHERS']['WRONG_USER']);
                }

                if (!isset($data->valid_from) || !($data->valid_from = strtotime($data->valid_from)) || !is_numeric($data->valid_from)) {
                    throw new Exception($lang['VOUCHERS']['VALID_FROM_FAILED']);
                }

                if (isset($data->valid_to) && strlen($data->valid_to) <= 10) {
                    $data->valid_to .= " 23:59:59";
                }

                if (!isset($data->valid_to) || !($data->valid_to = strtotime($data->valid_to)) || !is_numeric($data->valid_to)) {
                    throw new Exception($lang['VOUCHERS']['VALID_TO_FAILED']);
                }

                if (isset($data->valid_for_all) && $data->valid_for_all == "yes") {
                    $data->valid_for = "all";
                } else {
                    if (!isset($data->valid_for) || !is_array($data->valid_for) || count($data->valid_for) == 0) {
                        throw new Exception($lang['VOUCHERS']['VALID_FOR_INVALID']);
                    }

                    foreach ($data->valid_for as $k => $v) {
                        if (!array_key_exists($v, $valid_for_possibilities)) {
                            unset($data->valid_for[$k]);
                        }
                    }

                    $data->valid_for = serialize($data->valid_for);
                }

                if (!isset($data->active) || !is_numeric($data->active)) {
                    throw new Exception($lang['VOUCHERS']['ACTIVE_INVALID']);
                }

                $data->active = $data->active == "1" ? 1 : 0;
                $db->query("UPDATE vouchers SET `code` = '$data->code', `user` = $data->user, `type` = '$data->type', `value` = '$data->value', `max_uses` = '$data->max_uses', `valid_for` = '$data->valid_for', `valid_from` = '$data->valid_from', `valid_to` = '$data->valid_to', `max_per_user` = '$data->max_per_user', `active` = '$data->active' WHERE ID = {$var['data']['ID']} LIMIT 1");

                unset($_POST);
                $sql2 = $db->query("SELECT * FROM vouchers WHERE ID = " . intval($_GET['edit']));
                $var['data'] = $sql2->fetch_assoc();
                $var['edit_msg'] = "<div class='alert alert-success'>" . $lang['VOUCHERS']['VOUCHER_EDITED'] . "</div>";
                alog("vouchers", "edited", intval($_GET['edit']));
            } catch (Exception $ex) {
                $var['edit_msg'] = "<div class='alert alert-danger'>" . $ex->getMessage() . "</div>";
            }
        }
    } else {
        if (isset($_GET['delete'])) {
            $db->query("DELETE FROM vouchers WHERE ID = " . intval($_GET['delete']) . " LIMIT 1");
            if ($db->affected_rows > 0) {
                $var['list_msg'] = "<div class='alert alert-success'>{$lang['VOUCHERS']['DELETED']}</div>";
                alog("vouchers", "deleted_log", intval($_GET['delete']));
            }
        }

        if (isset($_POST['delete_selected']) && is_array($_POST['voucher'])) {
            $d = 0;
            foreach ($_POST['voucher'] as $id) {
                $db->query("DELETE FROM vouchers WHERE ID = " . intval($id) . " LIMIT 1");
                if ($db->affected_rows > 0) {
                    alog("vouchers", "deleted_log", intval($id));
                    $d++;
                }
            }

            if ($d == 1) {
                $var['list_msg'] = "<div class='alert alert-success'>{$lang['VOUCHERS']['ONE_DELETED']}</div>";
            } else if ($d > 0) {
                $var['list_msg'] = "<div class='alert alert-success'>" . str_replace("%x", $d, $lang['VOUCHERS']['X_DELETED']) . "</div>";
            }

        }

        if (isset($_GET['pause']) || isset($_GET['resume'])) {
            $active = isset($_GET['pause']) ? 0 : 1;
            $id = isset($_GET['pause']) ? $_GET['pause'] : $_GET['resume'];
            $db->query("UPDATE vouchers SET active = $active WHERE ID = " . intval($id) . " LIMIT 1");
            if ($active && $db->affected_rows > 0) {
                $var['list_msg'] = "<div class='alert alert-success'>{$lang['VOUCHERS']['REACTIVATED']}</div>";
                alog("vouchers", "reactivated_log", intval($id));
            } else if ($db->affected_rows > 0) {
                $var['list_msg'] = "<div class='alert alert-success'>{$lang['VOUCHERS']['PAUSED']}</div>";
                alog("vouchers", "paused_log", intval($id));
            }
        }

        if ($var['tab'] == "active") {
            if (isset($_POST['deactivate_selected']) && is_array($_POST['voucher'])) {
                $d = 0;
                foreach ($_POST['voucher'] as $id) {
                    $db->query("UPDATE vouchers SET active = 0 WHERE ID = " . intval($id) . " LIMIT 1");
                    if ($db->affected_rows > 0) {
                        alog("vouchers", "paused_log", intval($id));
                        $d++;
                    }
                }

                if ($d == 1) {
                    $var['list_msg'] = "<div class='alert alert-success'>{$lang['VOUCHERS']['ONE_DEACTIVATED']}</div>";
                } else if ($d > 0) {
                    $var['list_msg'] = "<div class='alert alert-success'>" . str_replace("%x", $d, $lang['VOUCHERS']['X_DEACTIVATED']) . "</div>";
                }

            }

            $sql = "SELECT * FROM vouchers WHERE ((uses < max_uses OR max_uses = -1) AND (valid_to > " . time() . " OR valid_to <= 0) AND active = 1 AND valid_from <= " . time() . ")";
            $order = "valid_to DESC";
        } else if ($var['tab'] == "inactive") {
            if (isset($_POST['activate_selected']) && is_array($_POST['voucher'])) {
                $d = 0;
                foreach ($_POST['voucher'] as $id) {
                    $db->query("UPDATE vouchers SET active = 1 WHERE ID = " . intval($id) . " LIMIT 1");
                    if ($db->affected_rows > 0) {
                        alog("vouchers", "reactivated_log", intval($id));
                        $d++;
                    }
                }

                if ($d == 1) {
                    $var['list_msg'] = "<div class='alert alert-success'>{$lang['VOUCHERS']['ONE_ACTIVATED']}</div>";
                } else if ($d > 0) {
                    $var['list_msg'] = "<div class='alert alert-success'>" . str_replace("%x", $d, $lang['VOUCHERS']['X_ACTIVATED']) . "</div>";
                }

            }

            $filter = $var['filter'] = isset($_GET['filter']) ? $_GET['filter'] : "none";
            $sql = "SELECT * FROM vouchers WHERE ((uses > max_uses AND max_uses != -1) OR (valid_to <= " . time() . " AND valid_to > 0) OR active = 0 OR valid_from > " . time() . ")";
            $order = "valid_to DESC";

            if ($filter == "expired") {
                $sql = "SELECT * FROM vouchers WHERE valid_to <= " . time() . " AND valid_to > 0";
            } else if ($filter == "waiting") {
                $sql = "SELECT * FROM vouchers WHERE valid_from > " . time();
            } else if ($filter == "used") {
                $sql = "SELECT * FROM vouchers WHERE uses > max_uses AND max_uses != -1";
            } else if ($filter == "deactivated") {
                $sql = "SELECT * FROM vouchers WHERE active = 0";
            }
        } else if ($var['tab'] == "create") {
            $users = array();
            $sql2 = $db->query("SELECT ID, firstname, lastname, mail FROM clients ORDER BY firstname ASC, lastname ASC");
            while ($row = $sql2->fetch_object()) {
                $users[$row->ID] = $row->firstname . " " . $row->lastname . " (" . $row->mail . ")";
            }

            $var['users'] = $users;

            $var['generated_code'] = $sec->generatePassword(12, true, 'lud');
            $var['valid_for_possibilities'] = $valid_for_possibilities = getPossibilities();

            if (isset($_POST['create_voucher'])) {
                $data = array();
                foreach ($_POST as $k => $v) {
                    if (substr(strtolower($k), 0, 8) != "voucher_") {
                        continue;
                    }

                    if (!is_array($v)) {
                        $data[strtolower(substr($k, 8))] = $db->real_escape_string(trim($v));
                    } else {
                        $data[strtolower(substr($k, 8))] = $v;
                    }

                }
                $data = (object) $data;

                try {
                    if (!isset($data->code) || strlen($data->code) < 1) {
                        throw new Exception($lang['VOUCHERS']['NO_CODE']);
                    }

                    if ($db->query("SELECT ID FROM vouchers WHERE code = '{$data->code}' LIMIT 1")->num_rows != 0) {
                        throw new Exception($lang['VOUCHERS']['CODE_EXISTS']);
                    }

                    if (!isset($data->value) || !is_numeric($data->value = $nfo->phpize($data->value)) || $data->value <= 0) {
                        throw new Exception($lang['VOUCHERS']['INVALID_VALUE']);
                    }

                    if (!isset($data->type)) {
                        throw new Exception($lang['VOUCHERS']['TYPE_MISSING']);
                    }

                    if (!isset($data->max_uses) || !is_numeric($data->max_uses)) {
                        throw new Exception($lang['VOUCHERS']['MAX_USES_MISSING']);
                    }

                    $data->max_uses = intval($data->max_uses);

                    if (!isset($data->max_per_user) || !is_numeric($data->max_per_user)) {
                        throw new Exception($lang['VOUCHERS']['MAX_PER_USER_MISSING']);
                    }

                    $data->max_per_user = intval($data->max_per_user);

                    if (!isset($data->user) || !is_numeric($data->user) || ($data->user != 0 && !isset($users[$data->user]))) {
                        throw new Exception($lang['VOUCHERS']['WRONG_USER']);
                    }

                    if (!isset($data->valid_from) || !($data->valid_from = strtotime($data->valid_from)) || !is_numeric($data->valid_from)) {
                        throw new Exception($lang['VOUCHERS']['VALID_FROM_FAILED']);
                    }

                    if (isset($data->valid_to) && strlen($data->valid_to) <= 10) {
                        $data->valid_to .= " 23:59:59";
                    }

                    if (!isset($data->valid_to) || !($data->valid_to = strtotime($data->valid_to)) || !is_numeric($data->valid_to)) {
                        throw new Exception($lang['VOUCHERS']['VALID_TO_FAILED']);
                    }

                    if (isset($data->valid_for_all) && $data->valid_for_all == "yes") {
                        $data->valid_for = "all";
                    } else {
                        if (!isset($data->valid_for) || !is_array($data->valid_for) || count($data->valid_for) == 0) {
                            throw new Exception($lang['VOUCHERS']['VALID_FOR_INVALID']);
                        }

                        foreach ($data->valid_for as $k => $v) {
                            if (!array_key_exists($v, $valid_for_possibilities)) {
                                unset($data->valid_for[$k]);
                            }
                        }

                        $data->valid_for = serialize($data->valid_for);
                    }

                    if (!isset($data->active) || !is_numeric($data->active)) {
                        throw new Exception($lang['VOUCHERS']['ACTIVE_INVALID']);
                    }

                    $data->active = $data->active == "1" ? 1 : 0;
                    $db->query("INSERT INTO vouchers (`code`, `type`, `value`, `max_uses`, `valid_for`, `valid_from`, `valid_to`, `max_per_user`, `active`, `user`) VALUES ('$data->code', '$data->type', '$data->value', '$data->max_uses', '$data->valid_for', '$data->valid_from', '$data->valid_to', '$data->max_per_user', '$data->active', $data->user)");
                    alog("vouchers", "created", intval($db->insert_id));

                    unset($_POST);
                    $var['create_msg'] = "<div class='alert alert-success'>" . $lang['VOUCHERS']['VOUCHER_CREATED'] . "</div>";
                } catch (Exception $ex) {
                    $var['create_msg'] = "<div class='alert alert-danger'>" . $ex->getMessage() . "</div>";
                }
            }
        }

        if (isset($sql)) {
            $t = new Table($sql, [
                "code" => [
                    "name" => "Gutschein-Code",
                    "type" => "like",
                ],
            ]);
            $var['th'] = $t->getHeader();
            $var['tf'] = $t->getFooter();
            $sql = $t->qry($order);

            $vouchers = array();
            while ($voucher = $sql->fetch_assoc()) {
                $voucher['value_f'] = $nfo->format($voucher['value']);
                $voucher['value_f'] = $voucher['type'] == "percentage" ? $voucher['value_f'] . " %" : $cur->infix($voucher['value_f'], $cur->getBaseCurrency());
                array_push($vouchers, $voucher);
            }

            $var['vouchers'] = $vouchers;
        }
    }

    $var['ci'] = ci($var['cid']);
} else {
    alog("general", "insufficient_page_rights", "vouchers");
    $tpl = "error";
}
