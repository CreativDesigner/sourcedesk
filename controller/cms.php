<?php
// Global some variables for security reasons
global $db, $_GET, $var, $CFG, $buy, $buy_short, $lang, $pars, $smarty, $age;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// First, we try to select the requested page from database (logged in users have access to level 2 also)
$in = $var['logged_in'] ? "1, 2" : "1, 3";
$sql = $db->query("SELECT * FROM cms_pages WHERE active IN ($in) AND slug = '" . $db->real_escape_string(explode("/", $_GET['p'])[0]) . "' LIMIT 1");

// If no page exist, we display an error, otherwise we continue
if ($sql->num_rows != 1) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";

} else {

    // We gather a few information about the page
    $info = $sql->fetch_object();
    $tpl = "cms";

    $age->cms($info->slug, $info->slug == "index" ? "https://google.com/" : "");

    // It is important to decode the content - we do this to prevent HTML attributes from being escaped
    if (unserialize($info->content) !== false) {
        $arr = unserialize($info->content);
        $pos = array_key_exists($CFG['LANG'], $arr) ? $CFG['LANG'] : array_keys($arr)[0];
        $con = base64_decode($arr[$pos]);
    } else {
        $con = base64_decode($info->content);
    }

    // Get the title
    if (unserialize($info->title) !== false) {
        $arr = unserialize($info->title);
        $pos = array_key_exists($CFG['LANG'], $arr) ? $CFG['LANG'] : array_keys($arr)[0];
        $title = $arr[$pos];
    } else {
        $title = $info->title;
    }

    // Set meta info
    if (is_array(@unserialize($info->seo))) {
        $var['cfg']['SEO'] = $info->seo;
    }

    // Extract JavaScript
    $con = str_replace('<script type="text/javascript">', '<script>', $con);
    preg_match_all("/<script>(.*)<\/script>/Uis", $con, $matches);
    foreach ($matches[0] as $m) {
        $var['additionalJS'] .= str_replace(array("<script>", "</script>"), "", $m);
    }

    while (preg_match("/<script>(.*)<\/script>/Uis", $con)) {
        $con = preg_replace("/<script>(.*)<\/script>/Uis", "", $con);
    }

    $var['content'] = $con;
    $var['page'] = $info->slug;
    $var['cms_page'] = $info->slug;
    $var['container'] = $info->container;
    $var['pars'] = $pars;
}
