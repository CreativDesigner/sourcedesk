<?php
global $ari, $lang, $CFG, $db, $var;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['USERMAP']['TITLE']);
menu("customers");

// Check admin rights
if ($ari->check(8)) {
    $tpl = "usermap";

    $var['customers'] = array();
    $sql = $db->query("SELECT * FROM clients WHERE coordinates != '' ORDER BY ID ASC");
    while ($c = $sql->fetch_object()) {
        $counSql = $db->query("SELECT name FROM client_countries WHERE ID = " . $c->country);
        $country = $counSql->num_rows == 1 ? $counSql->fetch_object()->name : "";

        $coordinates = unserialize($c->coordinates);
        $var['customers'][$c->ID] = array(
            "lat" => $coordinates['lat'],
            "lng" => $coordinates['lng'],
            "name" => $c->firstname . " " . $c->lastname,
            "address" => $c->street . " " . $c->street_number . "###br###" . $c->postcode . " " . $c->city . (!empty($country) ? "###br###" . $country : ""),
            "cname" => $c->company,
        );
    }
} else {
    alog("general", "insufficient_page_rights", "usermap");
    $tpl = "error";
}
