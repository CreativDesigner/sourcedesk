<?php
global $adminInfo, $db, $CFG;

$notifications = [];

$sql = $db->query("SELECT * FROM notifications WHERE admin = " . intval($adminInfo->ID));
while ($row = $sql->fetch_object()) {
    array_push($notifications, $row->text);
    $db->query("DELETE FROM notifications WHERE ID = " . intval($row->ID));
}

die(json_encode($notifications));