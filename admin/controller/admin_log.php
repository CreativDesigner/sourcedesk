<?php
global $ari, $lang, $CFG, $db, $var, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

menu("settings");
title($lang['ADMIN_LOG']['TITLE']);

// Check admin rights
if ($ari->check(52)) {
    $tpl = "admin_log";

    $var['staff'] = array();
    $sql = $db->query("SELECT ID, name FROM `admins`");
    while ($row = $sql->fetch_object()) {
        $var['staff'][$row->ID] = $row->name;
    }

    $t = new Table("SELECT * FROM admin_log", [
        "admin" => [
            "name" => $lang['ADMIN_LOG']['ADMIN'],
            "type" => "select",
            "options" => $var['staff'],
        ],
        "action" => [
            "name" => $lang['ADMIN_LOG']['ACTION'],
            "type" => "like",
        ],
    ], ["time", "DESC"], "admin_log");
    $var['th'] = $t->getHeader();
    $var['tf'] = $t->getFooter();

    $var['logs'] = array();

    $var['table_order'] = [
        $t->orderHeader("time", $lang["ADMIN_LOG"]["TIME"]),
        $t->orderHeader("admin", $lang["ADMIN_LOG"]["ADMIN"]),
        $t->orderHeader("ip", $lang["ADMIN_LOG"]["IP"]),
    ];

    $sql = $t->qry("time DESC, ID DESC");
    while ($row = $sql->fetch_object()) {
        $actArr = unserialize($row->action);
        $action = $lang[strtoupper(array_shift($actArr))][strtoupper(array_shift($actArr))];

        for ($i = 0;!empty($actArr); $i++) {
            $action = str_replace('%' . ($i + 1), htmlentities(array_shift($actArr)), $action);
        }

        if ($action == "") {
            $action = implode(" ", unserialize($row->action));
        }

        $var['logs'][$row->ID] = array(
            "time" => $dfo->format($row->time, true, true),
            "admin" => array_key_exists($row->admin, $var['staff']) ? $var['staff'][$row->admin] : ($row->admin > 0 ? $lang['ADMIN_LOG']['NOT_FOUND'] : $lang['ADMIN_LOG']['SYSTEM']),
            "adminId" => $row->admin,
            "action" => $action,
            "ip" => $row->ip,
        );
    }
} else {
    alog("general", "insufficient_page_rights", "admin_log");
    $tpl = "error";
}
