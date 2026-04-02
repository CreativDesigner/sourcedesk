<?php
// Global @var lang for security reasons
global $lang;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

// This file is only for prevent overwriting through CMS (/error is required from .htaccess)

$title = $lang['ERROR']['TITLE'];
$tpl = "error";

?>