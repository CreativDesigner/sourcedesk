<?php
global $db, $CFG, $adminInfo, $lang;

$id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
$sql = $db->query("SELECT * FROM admins WHERE ID = " . $id);

if ($sql->num_rows != 1) {
    $id = $adminInfo->ID;
    $sql = $db->query("SELECT * FROM admins WHERE ID = " . $id);
}

$info = $sql->fetch_object();

title($info->name);
menu("");

$tpl = "admin";
$var["admin"] = (array) $info;
$var["avatar"] = "../files/avatars/";

if (isset($_FILES['new_avatar']) && $info->ID == $adminInfo->ID) {
    $f = $_FILES['new_avatar'];

    try {
        if (!in_array($f["type"], ["image/jpeg", "image/jpg", "image/png"])) {
            throw new Exception($lang['ADMINPAGE']['ERR1']);
        }

        $ex = explode(".", $f["name"]);
        $ext = strtolower(array_pop($ex));
        if (!in_array($ext, ["jpg", "png", "jpeg"])) {
            throw new Exception($lang['ADMINPAGE']['ERR1']);
        }

        if ($f["size"] > 200000) {
            throw new Exception($lang['ADMINPAGE']['ERR2']);
        }

        $name = $info->ID . "_" . time() . "_" . rand(10000000, 99999999) . "." . $ext;
        if (!move_uploaded_file($f["tmp_name"], __DIR__ . "/../../files/avatars/" . $name)) {
            throw new Exception($lang['ADMINPAGE']['ERR3']);
        }

        $name = $db->real_escape_string($name);
        $db->query("UPDATE admins SET avatar = '$name' WHERE ID = {$info->ID} LIMIT 1");

        if (file_exists(__DIR__ . "/../../files/avatars/" . basename($info->avatar)) && is_file(__DIR__ . "/../../files/avatars/" . basename($info->avatar))) {
            unlink(__DIR__ . "/../../files/avatars/" . basename($info->avatar));
        }

        $info->avatar = $name;
    } catch (Exception $ex) {
        $var['err'] = $ex->getMessage();
    }
}

if (!empty($info->avatar) && file_exists(__DIR__ . "/../../files/avatars/" . basename($info->avatar))) {
    $var["avatar"] .= htmlentities(basename($info->avatar));
} else {
    $var["avatar"] .= "none.png";
}

$var["log"] = [];

$sql = $db->query("SELECT `time`, `action` FROM admin_log WHERE admin = $id ORDER BY `time` DESC, `ID` DESC LIMIT 10");
while ($row = $sql->fetch_object()) {
    $actArr = unserialize($row->action);
    $action = $lang[strtoupper(array_shift($actArr))][strtoupper(array_shift($actArr))];

    for ($i = 0; !empty($actArr); $i++) {
        $action = str_replace('%' . ($i + 1), htmlentities(array_shift($actArr)), $action);
    }

    if ($action == "") {
        $action = implode(" ", unserialize($row->action));
    }

    $var["log"][] = [
        $action,
        Widgets::time_userfriendly($row->time),
    ];
}