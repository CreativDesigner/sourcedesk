<?php
global $db, $CFG, $lang;

$l = $lang['DNS_TEMPLATE'];

$id = intval($_GET['id'] ?? 0);
$sql = $db->query("SELECT * FROM dns_templates WHERE ID = $id");

if ($sql->num_rows) {
    $info = $sql->fetch_object();

    if ($info->ID == 1) {
        $info->name = $lang['DOMAINS']['DNSDEF'];
    }

    title($l['TITLE'] . ": " . htmlentities($info->name));
    menu("products");

    $tpl = "dns_template";
    $var['l'] = $l;
    $var['tname'] = $info->name;
    $var['ns_set'] = $info->ns_set;

    if (isset($_POST['ns_set'])) {
        $ns = (int) !empty($_POST['ns_set']);
        $db->query("UPDATE dns_templates SET ns_set = $ns WHERE ID = {$info->ID}");
        exit;
    }

    if (isset($_POST['del'])) {
        $id = intval($_POST['del']);
        $db->query("DELETE FROM dns_template_records WHERE ID = $id AND template_id = {$info->ID}");
        exit;
    }

    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);

        $data = [
            "name" => $_POST['name'] ?? "",
            "type" => $_POST['type'] ?? "",
            "content" => $_POST['content'] ?? "",
            "ttl" => intval($_POST['ttl'] ?? 3600),
            "priority" => intval($_POST['priority'] ?? 0),
            "hidden" => intval(!empty($_POST['hidden'])),
        ];

        if ($id > 0) {
            $str = "";
            foreach ($data as $k => $v) {
                $str .= "`$k` = '" . $db->real_escape_string($v) . "', ";
            }
            $str = trim($str, ", ");

            $db->query("UPDATE dns_template_records SET $str WHERE ID = $id AND template_id = {$info->ID}");
        } else {
            $data["template_id"] = $info->ID;

            $str = "";
            foreach ($data as $k => $v) {
                $str .= "'" . $db->real_escape_string($v) . "', ";
            }
            $str = trim($str, ", ");

            $db->query("INSERT INTO dns_template_records (`" . implode("`,`", array_keys($data)) . "`) VALUES ($str)");
        }
    }

    $var['records'] = [];
    $sql = $db->query("SELECT * FROM dns_template_records WHERE template_id = {$info->ID}");
    while ($row = $sql->fetch_assoc()) {
        $var['records'][] = $row;
    }
} else {
    title($lang['ERROR']['TITLE']);
}