<?php
// Global some variables for security reasons
global $var, $db, $lang, $CFG, $dfo, $raw_cfg;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$tpl = "search";
$title = !empty($_REQUEST['searchword']) ? $_REQUEST['searchword'] : $lang['GENERAL']['SEARCH'];

if (!empty($_REQUEST['searchword'])) {
    $var['searchword'] = $_REQUEST['searchword'];
    $var['blacklist'] = $blacklist = strpos($_REQUEST['searchword'], "%") !== false;

    if (strlen(trim($_REQUEST['searchword'])) >= 3 && !$blacklist) {
        $s = $db->real_escape_string($var['searchword']);
        $hidden = explode(",", $CFG['SEARCH_HIDDEN']);

        $results = array();
        $sql = $db->query("SELECT ID, name, description FROM `products` WHERE status = 1 AND (name LIKE '%$s%' OR description LIKE '%$s%')");
        while ($row = $sql->fetch_object()) {
            $name = unserialize($row->name)[$CFG['LANG']];
            $description = unserialize($row->description)[$CFG['LANG']];
            if ((stripos($name, $s) === false && stripos($description, $s) === false) || empty($description)) {
                continue;
            }

            if (in_array("art" . $row->ID, $hidden)) {
                continue;
            }

            if (!should_show_product($row->ID)) {
                continue;
            }

            $results[] = array("name" => $name, "description" => $description, "url" => $CFG['PAGEURL'] . "product/" . $row->ID, "btn" => $lang['SEARCH']['GO_PRODUCT']);
        }

        $sql = $db->query("SELECT ID, name FROM `product_categories` WHERE name LIKE '%$s%'");
        while ($row = $sql->fetch_object()) {
            $name = unserialize($row->name)[$CFG['LANG']];
            if (stripos($name, $s) === false) {
                continue;
            }

            if (in_array("cat" . $row->ID, $hidden)) {
                continue;
            }

            $results[] = array("name" => $name, "description" => "<i>{$lang['SEARCH']['IS_CATEGORY']}</i>", "url" => $CFG['PAGEURL'] . "cat/" . $row->ID, "btn" => $lang['SEARCH']['GO_CATEGORY']);
        }

        $sql = $db->query("SELECT ID, title, slug FROM `cms_pages` WHERE active = 1 AND (title LIKE '%$s%' OR slug LIKE '%$s%')");
        while ($row = $sql->fetch_object()) {
            if (in_array("page" . $row->ID, $hidden)) {
                continue;
            }

            if (unserialize($row->title) !== false) {
                $row->title = unserialize($row->title);
                $row->title = array_key_exists($CFG['LANG'], $row->title) ? $row->title[$CFG['LANG']] : $row->title[$raw_cfg['LANG']];
            }
            $results[] = array("name" => $row->title, "description" => "<i>{$lang['SEARCH']['IS_PAGE']}</i>", "url" => $CFG['PAGEURL'] . $row->slug, "btn" => $lang['SEARCH']['GO_PAGE']);
        }

        $sql = $db->query("SELECT ID, title, text, time FROM `cms_blog` WHERE title LIKE '%$s%'");
        while ($row = $sql->fetch_object()) {
            $results[] = array("name" => $row->title, "description" => "<i>" . str_replace("%d", $dfo->format($row->time, false), $lang['SEARCH']['IS_BLOG']) . "</i><br /><br />" . array_shift(explode("<br />", nl2br($row->text))), "url" => $CFG['PAGEURL'] . "blog/" . $row->ID, "btn" => $lang['SEARCH']['READ_BLOG']);
        }

        $var['results'] = $results;
    }
}
