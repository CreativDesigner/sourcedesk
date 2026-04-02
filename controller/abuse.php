<?php
global $title, $tpl, $db, $CFG, $pars, $var, $user;

User::status();

$title = $lang['ERROR']['TITLE'];
$tpl = "error";

$id = intval($pars[0] ?? 0);
$sql = $db->query("SELECT * FROM abuse WHERE ID = $id AND user = " . $user->get()['ID']);
if ($sql->num_rows) {
    $title = $lang['ABUSE']['TITLE'];
    $tpl = "abuse";

    $info = $sql->fetch_object();
    $var['abuse'] = (array) $info;
    $var['service'] = "";

    if (!empty($_POST['answer'])) {
        $db->query("INSERT INTO abuse_messages (report, author, `time`, `text`) VALUES ({$info->ID}, 'client', '" . date("Y-m-d H:i:s") . "', '" . $db->real_escape_string($_POST['answer']) . "')");

        header('Location: ' . $CFG['PAGEURL'] . 'abuse/' . $info->ID);
        exit;
    }

    $sql2 = $db->query("SELECT product, name, description FROM client_products WHERE ID = " . $info->contract . " AND user = " . $user->get()['ID']);
    if ($sql2->num_rows) {
        $cInfo = $sql2->fetch_object();

        $var['service'] = "#{$info->contract}";

        if ($cInfo->name) {
            $var['service'] .= " " . $cInfo->name;
        } else {
            $sql2 = $db->query("SELECT name FROM products WHERE ID = {$cInfo->product}");
            if ($sql2->num_rows) {
                $name = $sql2->fetch_object()->name;
                if (@unserialize($name)) {
                    $name = unserialize($name);
                    $name = $name[$CFG['LANG']] ?? (array_values($name)[0] ?? "");
                    if ($name) {
                        $var['service'] .= " " . $name;
                    }
                }
            }
        }

        if ($cInfo->description) {
            $var['service'] .= " (" . trim($cInfo->description) . ")";
        }
    }

    $var['messages'] = [];
    $sql2 = $db->query("SELECT * FROM abuse_messages WHERE report = " . $info->ID . " ORDER BY `time` DESC, `ID` DESC");
    while ($msg = $sql2->fetch_assoc()) {
        $var['messages'][] = $msg;
    }
}
