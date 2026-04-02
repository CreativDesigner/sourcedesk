<?php
global $adminInfo, $db, $CFG;

$id = 0;
$last = explode(",", $_GET['last'] ?? "");
$last = array_map("intval", $last);
$last = implode(",", $last);

$my_depts = array($adminInfo->ID / -1);
$sql = $db->query("SELECT dept FROM support_department_staff WHERE staff = " . intval($adminInfo->ID));
while ($row = $sql->fetch_object()) {
    $ds = $db->query("SELECT ID FROM support_departments WHERE ID = " . $row->dept);
    while ($sd = $ds->fetch_object()) {
        array_push($my_depts, $sd->ID);
    }
}

$sql = $db->query("SELECT ID FROM support_tickets WHERE dept IN (" . implode(",", $my_depts) . ") AND status = 0 AND ID NOT IN ($last) ORDER BY priority ASC, updated ASC LIMIT 1");
if ($sql->num_rows) {
    $id = $sql->fetch_object()->ID;
}

if ($id) {
    header('Location: ?p=support_ticket&id=' . $id . ($last ? '&last=' . urlencode($last) : ''));
} else {
    header('Location: ?p=support_tickets');
}

exit;