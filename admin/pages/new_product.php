<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$params = "";
unset($_GET['p']);

if (count($_GET)) {
    $params = "&" . http_build_query($_GET);
}

header('Location: ?p=new_hosting' . $params);
exit;
