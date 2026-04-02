<?php
// Global some variables for security reasons
global $db, $CFG, $var, $lang;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$title = $lang['FAQ']['TITLE'];
$tpl = "faq";

$faq = $q = Array();

$sql = $db->query("SELECT * FROM cms_faq");
while ($row = $sql->fetch_object()) {
	array_push($faq, Array("q" => unserialize($row->question)[$CFG['LANG']], "a" => nl2br(base64_decode(unserialize($row->answer)[$CFG['LANG']]))));
	array_push($q, unserialize($row->question)[$CFG['LANG']]);
}
array_multisort($q, $faq);

$var['faq'] = $faq;

?>