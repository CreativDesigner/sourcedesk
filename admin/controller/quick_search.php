<?php
global $db, $CFG, $lang;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$id = $id2 = "";
$mysw = $_POST['searchword'];

if (strtolower(substr($mysw, 0, strlen($CFG['CNR_PREFIX']))) == strtolower($CFG['CNR_PREFIX'])) {
    $mysw = substr($mysw, strlen($CFG['CNR_PREFIX']));
}

if (is_numeric($mysw)) {
    $id = "OR ID = " . intval($mysw) . " ";
    $id2 = "ID = " . intval($mysw) . " DESC, ";
}
$sql = $db->query("SELECT ID, company, firstname, lastname, mail, telephone_pin FROM clients WHERE CONCAT(firstname, ' ', lastname) LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR company LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR telephone LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR mail LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR (telephone_pin = " . intval($_POST['searchword']) . " AND telephone_pin_set > " . (time() - 600) . ") {$id}ORDER BY {$id2}firstname ASC, lastname ASC, company ASC, mail ASC LIMIT 10");

if ($sql->num_rows == 0) {
    echo '<li class="search-result" style="padding-top: 15px; padding-bottom: 15px;"><center>' . $lang['QUICKSEARCH']['NOTFOUND'] . '</center></li>';
} else {
    while ($row = $sql->fetch_object()) {
        if ($row->telephone_pin == $_POST['searchword']) {
            $tp = "<font color=\"green\"><b>{$lang['SEARCH']['TPMATCH']}</b></font><br />";
        }

        $uI = User::getInstance($row->ID, "ID");
        echo '<li class="search-result"><a href="?p=customers&edit=' . $row->ID . '">' . $tp . $uI->getfName() . (!empty($row->company) ? "<br />{$row->company}" : "") . "<br />" . $row->mail . '</a></li>';
    }
}

alog("search", "quicksearch", $_POST['searchword']);

exit;
