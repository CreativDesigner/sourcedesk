<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$ip6 = !empty($_GET['ip6']) && filter_var($_GET['ip6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $_GET['ip6'] : "";

list($sld, $tld) = explode(".", $_GET['domain'], 2);

DNSHandler::getDriver($tld)->updateDynDNS($_GET['domain'], $_GET['key'], $_GET['ip'], $ip6);
exit;
