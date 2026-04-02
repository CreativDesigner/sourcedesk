<?php
// Global some variables for security reasons
global $CFG, $var, $lang, $raw_cfg, $pars;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['IMPRINT']['TITLE'];
$tpl = "imprint";

// Get the text from config and format it for displaying
$text = nl2br(unserialize($CFG['IMPRINT'])[$CFG['LANG']]);

// Make email address links with encoding
function no_spam($mail)
{
    $str = "";
    foreach (str_split($mail) as $char) {
        $str .= "&#x" . bin2hex($char) . ";";
    }

    return $str;
}

$text = preg_replace("/([A-Za-z0-9\.\-\_]{2,})(\@)([A-Za-z0-9\.\-\_]{3,})(\.)([A-Za-z]{2,3})\b/", "<a href=\"mailto:\\0\">\\0</a>", $text);
while (($pos = strpos($text, "mailto:")) !== false) {
    $subs = substr($text, $pos + 7);
    $mail = substr($subs, 0, strpos($subs, '"'));
    $link = no_spam("mailto:" . $mail);
    $text = substr_replace($text, $link, $pos, strlen($mail) + 7);
    $npos = $pos + strlen($link);
    $mpos = strpos(substr($text, $npos), '">') + 2 + $npos;
    $text = substr_replace($text, no_spam($mail), $mpos, strlen($mail));
}

// Pass text to template engine
$var['text_imprint'] = $text;

if (!empty($pars[0]) && $pars[0] == "raw") {
    die($text);
}
