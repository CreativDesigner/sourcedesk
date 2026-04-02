<?php
global $var, $db, $CFG, $lang, $dfo, $pars;

$title = $lang["STATUS"]["TITLE"];
$tpl = "status";

$tab = $pars[0] ?? "";

if ($tab == "archive") {
    $var['archive'] = true;

    $var['status'] = [];
    $sql = $db->query("SELECT * FROM monitoring_announcements WHERE status = 2 ORDER BY last_changed DESC");
    while ($row = $sql->fetch_assoc()) {
        $var['status'][] = $row;
    }
} else {
    $var['archive'] = false;

    $var['servers'] = [];
    $sql = $db->query("SELECT * FROM monitoring_server WHERE visible = 1 ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        $obj = MonitoringServer::getInstance($row->ID);

        if ($obj->shouldShow()) {
            $var['servers'][] = [$row->name, $obj->getFormattedStatus(), $dfo->format($obj->lastCheck(), true, false)];
        }
    }

    $var['status'] = [];
    $sql = $db->query("SELECT * FROM monitoring_announcements WHERE status IN (0,1) ORDER BY last_changed DESC");
    while ($row = $sql->fetch_assoc()) {
        $var['status'][] = $row;
    }

    $var['archiveNum'] = $db->query("SELECT COUNT(*) c FROM monitoring_announcements WHERE status = 2")->fetch_object()->c;
}
