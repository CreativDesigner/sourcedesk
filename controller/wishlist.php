<?php
global $user, $db, $CFG, $pars, $session, $cur, $nfo, $maq;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

$l = $lang['WISHLIST'];
$title = $l['TITLE'];
$tpl = "wishlist";

$task = "home";

$var['search'] = false;

$var['products'] = array();
foreach (unserialize($user->get()['software_products']) as $pid) {
    if (is_object($sql = $db->query("SELECT `ID`, `name`, `uses_wishlist` FROM products WHERE ID = $pid")) && $sql->num_rows == 1 && is_object($opInfo = $sql->fetch_object())) {
        if ($opInfo->uses_wishlist) {
            $sql = $db->query("SELECT `ID`, `name` FROM products WHERE ID = " . $opInfo->uses_wishlist);
            if ($sql->num_rows) {
                $opInfo = $sql->fetch_object();
            }
        }

        if (!is_array($names = unserialize($opInfo->name)) || !array_key_exists($CFG['LANG'], $names)) {
            continue;
        }

        $ids = implode(",", wlIds($opInfo->ID));
        $var['products'][$opInfo->ID] = array("name" => $names[$CFG['LANG']], "count" => $db->query("SELECT 1 FROM wishlist WHERE product IN ($ids)")->num_rows);
    }
}

if (array_key_exists(268, $var['products'])) {
    $var['products'][268]["name"] = trim(str_replace(["Mietversion", "Leased"], "", $var['products'][268]["name"]));
}

$var['wishes'] = array();
$sql = $db->query("SELECT `ID`, `title` FROM wishlist WHERE user = " . $user->get()['ID']);
while ($row = $sql->fetch_object()) {
    $var['wishes'][$row->ID] = array("title" => $row->title, "likes" => $db->query("SELECT 1 FROM wishlist_likes WHERE wish = " . $row->ID)->num_rows);
}

function wlIds($pid)
{
    global $CFG, $db;
    $pid = intval($pid);

    $ids = [$pid];

    $sql = $db->query("SELECT `ID` FROM products WHERE uses_wishlist = $pid");
    while ($row = $sql->fetch_object()) {
        array_push($ids, $row->ID);
    }

    $uw = $db->query("SELECT `uses_wishlist` FROM products WHERE ID = $pid")->fetch_object()->uses_wishlist;
    if ($uw) {
        array_push($ids, $uw);
    }

    return $ids;
}

function wlId($pid)
{
    global $CFG, $db;
    $pid = intval($pid);
    return $db->query("SELECT `uses_wishlist` FROM products WHERE ID = $pid")->fetch_object()->uses_wishlist ?: $pid;
}

function wlAc($pid, $ids)
{
    $allowed = wlIds($pid);
    $ids = array_keys($ids);

    foreach ($ids as $id) {
        if (in_array($id, $allowed)) {
            return true;
        }
    }

    return false;
}

if (isset($pars[0])) {
    switch ($pars[0]) {
        case 'wish':
            if (!isset($pars[1]) || !is_numeric($pars[1])) {
                break;
            }

            $sql = $db->query("SELECT * FROM wishlist WHERE ID = " . $pars[1]);
            if ($sql->num_rows != 1) {
                break;
            }

            $info = $sql->fetch_object();

            if (!wlAc($info->product, $var['products']) && $info->user != $user->get()['ID']) {
                break;
            }

            if (isset($pars[2]) && $pars[2] == "buy" && (substr($info->answer, 0, 1) == "2" || (substr($info->answer, 0, 1) == "3" && $info->user == $user->get()['ID'])) && $user->get()['credit'] >= substr($info->answer, 1)) {
                $inv = new Invoice;
                $inv->setDate(date("Y-m-d"));
                $inv->setClient($user->get()['ID']);
                $inv->setDueDate();

                $item = new InvoiceItem;
                $item->setDescription("<b>{$l['INVDESC']}</b><br />" . $info->title);
                $item->setAmount(substr($info->answer, 1));
                $item->save();

                $inv->addItem($item);
                $inv->save();
                $inv->applyCredit();
                $inv->send();

                $new = substr($info->answer, 0, 1) == "2" ? "5" : "6";

                $db->query("UPDATE wishlist SET answer = '$new', ack = 0 WHERE ID = " . $pars[1]);
                $info->answer = $new;

                $var['suc'] = $l['INVSUC'];
            }

            $uw = wlId($info->product);
            if ($uw) {
                $info->product = $uw;
            }

            $info->product_name = @unserialize($db->query("SELECT `name` FROM products WHERE ID = {$info->product}")->fetch_object()->name)[$CFG['LANG']];

            if ($info->product == 268) {
                $info->product_name = trim(str_replace(["Mietversion", "Leased"], "", $info->product_name));
            }

            $var['wish'] = $info;
            $task = "wish";

            $var['like'] = $db->query("SELECT 1 FROM wishlist_likes WHERE user = {$user->get()['ID']} AND wish = {$pars[1]}")->num_rows;
            $var['abo'] = $db->query("SELECT 1 FROM wishlist_wish_abo WHERE user = {$user->get()['ID']} AND wish = {$pars[1]}")->num_rows;
            $var['likes'] = $db->query("SELECT 1 FROM wishlist_likes WHERE wish = {$pars[1]}")->num_rows;
            $lnSql = $db->query("SELECT author FROM wishlist_comments WHERE user = {$user->get()['ID']} ORDER BY time DESC, ID DESC LIMIT 1");
            $var['lastname'] = $lnSql->num_rows == 1 ? $lnSql->fetch_object()->author : "";

            $var['comments'] = array();
            $sql = $db->query("SELECT * FROM wishlist_comments WHERE wish = {$pars[1]} ORDER BY time ASC, ID ASC");
            while ($row = $sql->fetch_object()) {
                $var['comments'][$row->ID] = $row;
            }

            $var['additionalJS'] .= '$("#like").click(function(e) { e.preventDefault(); $.post("' . $CFG['PAGEURL'] . 'wishlist/like", {id: ' . $info->ID . ', csrf_token: "' . CSRF::raw() . '"}, function(resp) { if(resp == "ok"){ $("#like").hide(); $("#unlike").show(); $("#likes").html(parseInt($("#likes").html()) + 1); } }); });';
            $var['additionalJS'] .= '$("#unlike").click(function(e) { e.preventDefault(); $.post("' . $CFG['PAGEURL'] . 'wishlist/unlike", {id: ' . $info->ID . ', csrf_token: "' . CSRF::raw() . '"}, function(resp) { if(resp == "ok"){ $("#like").show(); $("#unlike").hide(); $("#likes").html(parseInt($("#likes").html()) - 1); } }); });';
            $var['additionalJS'] .= '$("#send").click(function(e) { $.post("' . $CFG['PAGEURL'] . 'wishlist/comment", {id: ' . $info->ID . ', csrf_token: "' . CSRF::raw() . '", name: $("#name").val(), comment: $("#text").val(), confirm: $("#confirm").is(":checked") }, function(resp) { if(resp == "ok"){ window.location = "' . $CFG['PAGEURL'] . 'wishlist/wish/' . $info->ID . '"; } else { $(".alert-danger").show().html(resp); } }); });';
            $var['additionalJS'] .= '$("#abo").click(function(e) { e.preventDefault(); $.post("' . $CFG['PAGEURL'] . 'wishlist/wabo", {id: ' . $info->ID . ', csrf_token: "' . CSRF::raw() . '"}, function(resp) { if(resp == "ok"){ $("#abo").hide(); $("#deabo").show(); } }); });';
            $var['additionalJS'] .= '$("#deabo").click(function(e) { e.preventDefault(); $.post("' . $CFG['PAGEURL'] . 'wishlist/wdeabo", {id: ' . $info->ID . ', csrf_token: "' . CSRF::raw() . '"}, function(resp) { if(resp == "ok"){ $("#abo").show(); $("#deabo").hide(); } }); });';

            $var['amount'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), substr($info->answer, 1))));
            break;

        case 'like':
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                break;
            }

            if ($db->query("SELECT * FROM wishlist WHERE ID = " . $_POST['id'])->num_rows != 1) {
                break;
            }

            $product = $db->query("SELECT product FROM wishlist WHERE ID = " . $_POST['id'])->fetch_object()->product;
            if (!wlAc($product, $var['products'])) {
                break;
            }

            $db->query("INSERT INTO wishlist_likes (`wish`, `user`) VALUES ({$_POST['id']}, {$user->get()['ID']})");
            die("ok");
            break;

        case 'unlike':
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                break;
            }

            if ($db->query("SELECT * FROM wishlist WHERE ID = " . $_POST['id'])->num_rows != 1) {
                break;
            }

            $product = $db->query("SELECT product FROM wishlist WHERE ID = " . $_POST['id'])->fetch_object()->product;
            if (!wlAc($product, $var['products'])) {
                break;
            }

            $db->query("DELETE FROM wishlist_likes WHERE wish = {$_POST['id']} AND user = {$user->get()['ID']}");
            die("ok");
            break;

        case 'wabo':
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                break;
            }

            if ($db->query("SELECT * FROM wishlist WHERE ID = " . $_POST['id'])->num_rows != 1) {
                break;
            }

            $product = $db->query("SELECT product FROM wishlist WHERE ID = " . $_POST['id'])->fetch_object()->product;
            if (!wlAc($product, $var['products'])) {
                break;
            }

            $db->query("INSERT INTO wishlist_wish_abo (`wish`, `user`) VALUES ({$_POST['id']}, {$user->get()['ID']})");
            die("ok");
            break;

        case 'wdeabo':
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                break;
            }

            if ($db->query("SELECT * FROM wishlist WHERE ID = " . $_POST['id'])->num_rows != 1) {
                break;
            }

            $product = $db->query("SELECT product FROM wishlist WHERE ID = " . $_POST['id'])->fetch_object()->product;
            if (!wlAc($product, $var['products'])) {
                break;
            }

            $db->query("DELETE FROM wishlist_wish_abo WHERE wish = {$_POST['id']} AND user = {$user->get()['ID']}");
            die("ok");
            break;

        case 'abo':
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                break;
            }

            if (!wlAc($product, $var['products'])) {
                break;
            }

            $db->query("INSERT INTO wishlist_product_abo (`product`, `user`) VALUES ({$_POST['id']}, {$user->get()['ID']})");
            die("ok");
            break;

        case 'deabo':
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                break;
            }

            if (!array_key_exists($_POST['id'], $var['products'])) {
                break;
            }

            $db->query("DELETE FROM wishlist_product_abo WHERE product = {$_POST['id']} AND user = {$user->get()['ID']}");
            die("ok");
            break;

        case 'comment':
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                die($l['COMERR1']);
            }

            if ($db->query("SELECT * FROM wishlist WHERE ID = " . intval($_POST['id']))->num_rows != 1) {
                die($l['COMERR2']);
            }

            $product = $db->query("SELECT product FROM wishlist WHERE ID = " . $_POST['id'])->fetch_object()->product;
            if (!wlAc($product, $var['products'])) {
                die($l['COMERR3']);
            }

            if (empty($_POST['name'])) {
                die($l['COMERR4']);
            }

            if (in_array(strtolower($_POST['name']), ["admin", "support", "webmaster", "staff"]) || $db->query("SELECT 1 FROM wishlist_comments WHERE author = '" . $db->real_escape_string($_POST['name']) . "' AND user != {$user->get()['ID']}")->num_rows > 0) {
                die($l['COMERR5']);
            }

            if (empty($_POST['comment'])) {
                die($l['COMERR6']);
            }

            if (empty($_POST['confirm']) || $_POST['confirm'] == "false") {
                die($l['COMERR7']);
            }

            if ($db->query("SELECT time FROM wishlist_comments WHERE user = {$user->get()['ID']} ORDER BY time DESC LIMIT 1")->fetch_object()->time > time() - 10) {
                die($l['COMERR8']);
            }

            $fields = "wish, user, time, message, author";
            $vs = array($_POST['id'], $user->get()['ID'], time(), $_POST['comment'], $_POST['name']);
            $values = "";
            foreach ($vs as $v) {
                $values .= "'" . $db->real_escape_string($v) . "', ";
            }

            $values = rtrim($values, ", ");

            $db->query("INSERT INTO wishlist_comments ($fields) VALUES ($values)");

            $sql = $db->query("SELECT user FROM wishlist_wish_abo WHERE wish = " . intval($_POST['id']) . " AND user != " . $user->get()['ID']);
            while ($row = $sql->fetch_object()) {
                $u = new User($row->user, "ID");
                if ($u->get()['ID'] != $row->user) {
                    continue;
                }

                $mtObj = new MailTemplate("Neuer Kommentar (Abo)");
                $titlex = $mtObj->getTitle($u->getLanguage());
                $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                $maq->enqueue([
                    "author" => $_POST['name'],
                    "wish" => $db->query("SELECT title FROM wishlist WHERE ID = " . $_POST['id'])->fetch_object()->title,
                    "url" => $CFG['PAGEURL'] . "wishlist/wish/" . $_POST['id'],
                ], $mtObj, $u->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], false, 0, 0, $mtObj->getAttachments($CFG['LANG']));
            }

            die("ok");
            break;

        case 'product':
            if (!isset($pars[1]) || !is_numeric($pars[1])) {
                break;
            }

            if (wlId($pars[1]) != $pars[1]) {
                header('Location: ' . $CFG['PAGEURL'] . "wishlist/product/" . wlId($pars[1]));
                exit;
            }

            if (!array_key_exists($pars[1], $var['products'])) {
                break;
            }
            $pars[1] = intval($pars[1]);

            $var['product_name'] = @unserialize($db->query("SELECT `name` FROM products WHERE ID = " . $pars[1])->fetch_object()->name)[$CFG['LANG']];

            if ($pars[1] == 268) {
                $var['product_name'] = trim(str_replace(["Mietversion", "Leased"], "", $var['product_name']));
            }

            $var['pid'] = $pars[1];

            $var['additionalJS'] .= '$("#abo").click(function(e) { e.preventDefault(); $.post("' . $CFG['PAGEURL'] . 'wishlist/abo", {id: ' . $pid . ', csrf_token: "' . CSRF::raw() . '"}, function(resp) { if(resp == "ok"){ $("#abo").hide(); $("#deabo").show(); } }); });';
            $var['additionalJS'] .= '$("#deabo").click(function(e) { e.preventDefault(); $.post("' . $CFG['PAGEURL'] . 'wishlist/deabo", {id: ' . $pid . ', csrf_token: "' . CSRF::raw() . '"}, function(resp) { if(resp == "ok"){ $("#abo").show(); $("#deabo").hide(); } }); });';

            $var['abo'] = $db->query("SELECT 1 FROM wishlist_product_abo WHERE product = {$pars[1]} AND user = {$user->get()['ID']}")->num_rows;

            $var['page'] = !empty($pars[2]) ? intval($pars[2]) : 1;

            $perPage = 15;
            $ids = implode(",", wlIds($pars[1]));
            $pages = ceil($db->query("SELECT COUNT(*) c FROM wishlist WHERE product IN ($ids)")->fetch_object()->c / $perPage);

            if ($var['page'] > $pages) {
                $var['page'] = $pages;
            }

            if ($var['page'] < 1) {
                $var['page'] = 1;
            }

            $offset = ($var['page'] - 1) * $perPage;
            $offset = max(0, $offset);
            $var['pages'] = $pages;

            $var['pwishes'] = array();

            if (!empty($_POST['searchword']) && strlen($_POST['searchword']) >= 5) {
                $var['search'] = true;
                $var['pages'] = 1;
                $var['page'] = 1;
                $sw = $db->real_escape_string($_POST['searchword']);

                $sql = $db->query("SELECT * FROM wishlist WHERE product IN ($ids) AND (title LIKE '%$sw%' OR description LIKE '%$sw%') ORDER BY date DESC, ID DESC");
            } else {
                $sql = $db->query("SELECT * FROM wishlist WHERE product IN ($ids) ORDER BY date DESC, ID DESC LIMIT $offset,$perPage");
            }

            while ($row = $sql->fetch_object()) {
                $row->likes = $db->query("SELECT 1 FROM wishlist_likes WHERE wish = {$row->ID}")->num_rows;
                $row->comments = $db->query("SELECT 1 FROM wishlist_comments WHERE wish = {$row->ID}")->num_rows;
                $row->abo = $db->query("SELECT 1 FROM wishlist_wish_abo WHERE wish = {$row->ID} AND user = {$user->get()['ID']}")->num_rows;
                $row->like = $db->query("SELECT 1 FROM wishlist_likes WHERE wish = {$row->ID} AND user = {$user->get()['ID']}")->num_rows;
                $row->commented = $db->query("SELECT 1 FROM wishlist_comments WHERE wish = {$row->ID} AND user = {$user->get()['ID']} LIMIT 1")->num_rows;
                $var['pwishes'][$row->ID] = $row;
            }

            $task = "product";
            break;

        case 'add':
            if (!isset($pars[1]) || !is_numeric($pars[1]) || !array_key_exists($pars[1], $var['products'])) {
                break;
            }

            $var['product_name'] = $var['products'][$pars[1]]['name'];
            $var['pid'] = $pars[1];

            if (isset($_POST['title'])) {
                try {
                    if (empty($_POST['title'])) {
                        throw new Exception($l['ADDERR1']);
                    }

                    if (empty($_POST['description'])) {
                        throw new Exception($l['ADDERR2']);
                    }

                    $vs = array($user->get()['ID'], $pars[1], $_POST['title'], $_POST['description'], date("Y-m-d"));
                    $values = "";
                    foreach ($vs as $v) {
                        $values .= "'" . $db->real_escape_string($v) . "', ";
                    }

                    $values = rtrim($values, ", ");

                    $db->query("INSERT INTO wishlist (`user`, `product`, `title`, `description`, `date`) VALUES ($values)");
                    $id = $db->insert_id;

                    $db->query("INSERT INTO wishlist_likes (`wish`, `user`) VALUES ($id, " . $user->get()['ID'] . ")");
                    $db->query("INSERT INTO wishlist_wish_abo (`wish`, `user`) VALUES ($id, " . $user->get()['ID'] . ")");

                    $sql = $db->query("SELECT user FROM wishlist_product_abo WHERE product = " . intval($pars[1]) . " AND user != " . $user->get()['ID']);
                    while ($row = $sql->fetch_object()) {
                        $u = new User($row->user, "ID");
                        if ($u->get()['ID'] != $row->user) {
                            continue;
                        }

                        $mtObj = new MailTemplate("Neuer Wunsch (Abo)");
                        $titlex = $mtObj->getTitle($u->getLanguage());
                        $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                        $maq->enqueue([
                            "product" => $var['product_name'],
                            "wish" => $_POST['title'],
                            "url" => $CFG['PAGEURL'] . "wishlist/wish/" . $id,
                        ], $mtObj, $u->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], false, 0, 0, $mtObj->getAttachments($CFG['LANG']));
                    }

                    if (($ntf = AdminNotification::getInstance("Neuer Wunsch")) !== false) {
                        $ntf->set("product", $var['product_name']);
                        $ntf->send();
                    }

                    header('Location: ' . $CFG['PAGEURL'] . 'wishlist/wish/' . $id);
                    exit;
                } catch (Exception $ex) {
                    $var['error'] = $ex->getMessage();
                }
            }

            $task = "add";
            break;
    }
}

$var['task'] = $task;
