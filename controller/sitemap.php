<?php
global $lang, $var, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$title = $lang['SITEMAP']['TITLE'];
$tpl = "sitemap";

if (isset($pars[0]) && $pars[0] == "xml") {
    header("Content-type: text/xml; charset=UTF-8");
    echo Sitemap::xml();
    exit;
}