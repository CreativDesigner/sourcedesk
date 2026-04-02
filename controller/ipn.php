<?php
// File for handling IPN request
global $gateways, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$gateway = isset($pars[0]) ? $pars[0] : "";
if (empty($gateway) || !isset($gateways->get()[$gateway]) || !$gateways->get()[$gateway]->isActive()) {
	exit;
}

$gateways->get()[$gateway]->getIpnHandler();
exit;