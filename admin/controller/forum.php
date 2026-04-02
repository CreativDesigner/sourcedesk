<?php
global $ari, $var, $db, $CFG, $lang, $session;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['FORUM']['TITLE']);
menu("cms");

// Check rights
if (!$ari->check(50)) {

    alog("general", "insufficient_page_rights", "forum");
    $tpl = "error";

} else {
    $tpl = "forum";
    $var['l'] = $lang['FORUM'];

    if (!empty($_POST['new_name'])) {
        $db->query("INSERT INTO forum (`name`) VALUES ('" . $db->real_escape_string($_POST['new_name']) . "')");
        header('Location: ?p=forum');
        exit;
    }

    $forums = [];
    $sql = $db->query("SELECT * FROM forum ORDER BY `order` ASC, `name` ASC, `ID` ASC");
    while ($row = $sql->fetch_assoc()) {
        $row['threads'] = $db->query("SELECT COUNT(*) c FROM forum_threads WHERE forum = " . intval($row['ID']))->fetch_object()->c;
        $row['mods'] = $db->query("SELECT COUNT(*) c FROM forum_moderators WHERE forum_id = " . intval($row['ID']))->fetch_object()->c;
        $row['entries'] = $db->query("SELECT COUNT(*) c FROM forum_entries e, forum_threads t WHERE e.thread = t.ID AND t.forum = " . intval($row['ID']))->fetch_object()->c;
        $row['pids'] = empty($row['pids']) ? [] : explode(",", $row['pids']);

        $forums[$row['ID']] = $row;
    }

    if (isset($_GET['delete']) && array_key_exists($_GET['delete'], $forums) && $forums[$_GET['delete']]['threads'] == 0) {
        $db->query("DELETE FROM forum WHERE ID = " . intval($_GET['delete']));
        header('Location: ?p=forum');
        exit;
    }

    $var['step'] = "overview";
    if (isset($_GET['edit']) && array_key_exists($_GET['edit'], $forums)) {
        $var['step'] = "edit";
        $var['f'] = $forums[$_GET['edit']];

        if (!empty($_POST['name'])) {
            $name = trim($_POST['name']);
            $desc = trim($_POST['description'] ?? "");
            $pids = [];

            if (is_array($_POST['pids'] ?? "")) {
                foreach ($_POST['pids'] as $i) {
                    if (is_numeric($i)) {
                        array_push($pids, $i);
                    }
                }
            }

            $order = intval($_POST['order'] ?? 0);
            $public = boolval($_POST['public'] ?? 0) ? 1 : 0;
            if (count($pids)) {
                $public = 0;
            }
            $pids = implode(",", $pids);

            $stmt = $db->prepare("UPDATE forum SET `name` = ?, description = ?, public = ?, pids = ?, `order` = ? WHERE ID = ?");
            $stmt->bind_param("ssisii", $name, $desc, $public, $pids, $order, $_GET['edit']);
            $stmt->execute();
            $stmt->close();

            $db->query("DELETE FROM forum_moderators WHERE forum_id = " . intval($_GET['edit']));
            if (is_array($_POST['mods'])) {
                foreach ($_POST['mods'] as $m) {
                    if (is_numeric($m)) {
                        $db->query("INSERT INTO forum_moderators (forum_id, user_id) VALUES (" . intval($_GET['edit']) . ", " . intval($m) . ")");
                    }
                }
            }

            header('Location: ?p=forum');
            exit;
        }

        $var['users'] = [];
        $sql = $db->query("SELECT ID, firstname, lastname FROM clients ORDER BY ID ASC");
        while ($row = $sql->fetch_object()) {
            $var['users'][$row->ID] = "#" . $row->ID . " - " . $row->firstname . " " . $row->lastname;
        }

        $var['mods'] = [];
        $sql = $db->query("SELECT user_id FROM forum_moderators WHERE forum_id = " . intval($var['f']['ID']));
        while ($row = $sql->fetch_object()) {
            array_push($var['mods'], $row->user_id);
        }

        $var['products'] = [];
        $sql = $db->query("SELECT ID, `name` FROM products");
        while ($row = $sql->fetch_object()) {
            $name = @unserialize($row->name);
            if (is_array($name) && count($name)) {
                $name = array_key_exists($CFG['LANG'], $name) ? $name[$CFG['LANG']] : array_values($name)[0];
            } else {
                $name = $row->name;
            }

            $var['products'][$row->ID] = $name;
        }
        asort($var['products']);
    }

    $var['forums'] = $forums;
}
