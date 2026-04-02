<?php
session_start();
if (empty($_SESSION['admin'])) {
    exit;
}

$ch = curl_init("https://sourceway.de/de/sourcedesk_bug");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
$res = curl_exec($ch);
curl_close($ch);

echo $res;