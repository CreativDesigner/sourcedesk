<?php
session_start();

require __DIR__ . "/../config.php";

$db = new MySQLi($CFG['DB']['HOST'], $CFG['DB']['USER'], $CFG['DB']['PASSWORD'], $CFG['DB']['DATABASE']);
if ($db->connect_errno) {
    die("Internal Server Error");
}

if (isset($_GET['resid'])) {
    $_SESSION['resid'] = intval($_GET['resid']);

    if (isset($_GET['login'])) {
        $ex = explode("-", $_GET['key'] ?? "");
        if (count($ex) == 2 && is_numeric($ex[0]) && $ex[0] > time() - 10) {
            $key = $db->real_escape_string($_GET['key']);
            $sql = $db->query("SELECT mail FROM client_customers WHERE ID = " . intval($_GET['login']) . " AND `uid` = " . intval($_GET['resid']) . " AND `login` = '$key'");
            if ($sql->num_rows) {
                $_SESSION['reseller_login'] = $sql->fetch_object()->mail;
            }
        }
    }

    header('Location: ./');
    exit;
}

if (empty($_SESSION['resid'])) {
    $host = $_SERVER['HTTP_HOST'] ?? "";

    if (empty($host)) {
        die("No hostname found");
    }

    $dns = dns_get_record($host, DNS_TXT);

    if (!$dns) {
        die("No valid TXT record");
    }

    $uid = 0;
    foreach ($dns as $entry) {
        if ($entry["type"] == "TXT" && $entry["host"] == $host) {
            $uid = intval(trim(trim($entry["txt"]), '"'));
            break;
        }
    }

    if (!$uid) {
        die("Invalid ID");
    }

    $_SESSION['resid'] = $uid;
}

$sql = $db->query("SELECT * FROM clients WHERE ID = " . intval($_SESSION['resid']));
if (!$sql->num_rows) {
    die("Reseller not found");
}

$resellerInfo = $sql->fetch_object();

if ($resellerInfo->locked || !$resellerInfo->reseller) {
    die("Reseller not active");
}

$pageName = htmlentities($resellerInfo->reseller_pagename ?: ($resellerInfo->company ?: ($resellerInfo->firstname . " " . $resellerInfo->lastname)));
$page = basename($_GET['p'] ?? "home");

if (!file_exists(__DIR__ . "/pages/$page.php")) {
    $page = "home";
}

if (empty($_SESSION['reseller_login'])) {
    $page = "login";
} else {
    if ($page == "login") {
        $page = "home";
    }

    $sql = $db->query("SELECT * FROM client_customers WHERE uid = {$resellerInfo->ID} AND mail = '" . $db->real_escape_string($_SESSION['reseller_login']) . "'");
    if (!$sql->num_rows) {
        $_SESSION['reseller_login'] = "";
        header('Location: ./');
        exit;
    }

    $customerInfo = $sql->fetch_object();

    $sql = $db->query("SELECT * FROM client_products WHERE reseller_customer = {$customerInfo->ID}");
    $contracts = [];

    while ($row = $sql->fetch_object()) {
        $contracts[$row->ID] = $row;
    }
}

$sdUrl = $db->query("SELECT `value` FROM settings WHERE `key` = 'pageurl'")->fetch_object()->value;

ob_start();
require_once __DIR__ . "/pages/$page.php";
$pageCon = ob_get_contents();
ob_end_clean();

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?=$pageName;?></title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous">

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <?=$pageCon;?>

    <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>
  </body>
</html>