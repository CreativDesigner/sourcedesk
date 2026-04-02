<?php
// Starting database connection to sourceDESK
// Providing decrypt function
error_reporting(0);

require __DIR__ . "/../../../../config.php";
require_once __DIR__ . "/../../../../lib/AesCtr.php";

@$db = new MySQLi($CFG['DB']['HOST'], $CFG['DB']['USER'], $CFG['DB']['PASSWORD'], $CFG['DB']['DATABASE']);
if ($db->connect_errno) {
    exit;
}
$db->set_charset("UTF8");

function decrypt($text)
{
    global $CFG;
    if (trim($text) === "") {
        return "";
    }

    return AesCtr::decrypt($text, isset($CFG['HASH']) ? $CFG['HASH'] : "", 256);
}

$sql = $db->query("SELECT `value` FROM addons WHERE `addon` = 'orgamax' AND `setting` = 'key'");
if (!$sql->num_rows) {
    exit;
}

$key = decrypt($sql->fetch_object()->value);
