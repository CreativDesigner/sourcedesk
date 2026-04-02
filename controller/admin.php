<?php
global $raw_cfg;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

header('Location: ' . $raw_cfg['PAGEURL'] . 'admin');
exit;