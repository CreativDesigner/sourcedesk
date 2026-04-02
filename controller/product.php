<?php
// Global some variables for security reasons
global $db, $_GET, $buy, $CFG, $var, $lang, $pars, $noHeader, $noCurrency, $age;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Gather information about the requested product
$pid = isset($pars[0]) ? $db->real_escape_string($pars[0]) : -1;
$sql = $db->query("SELECT ID, name, description, category, price, type FROM products WHERE ID = '$pid' AND status = 1");

// Product should exist to view the page
if ($sql->num_rows != 1 || !should_show_product($pid)) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";

} else {

    $age->product($pid);

    $info = $sql->fetch_array();
    $info['description'] = unserialize($info['description'])[$CFG['LANG']];
    $var['pinfo'] = $info;

    $title = unserialize($info['name'])[$CFG['LANG']];
    $tpl = "product_view";

    // Gather information about the category
    $catsql = $db->query("SELECT name, cast FROM product_categories WHERE ID = " . $info['category']);
    if ($catsql->num_rows == 1) {
        // Category found
        $catinfo = $catsql->fetch_object();
        $var['shortname'] = str_replace(unserialize($catinfo->cast)[$CFG['LANG']], "", unserialize($info['name'])[$CFG['LANG']]);
        $var['category'] = unserialize($catinfo->name)[$CFG['LANG']];
    } else {
        // Category not found
        $var['shortname'] = unserialize($info['name'])[$CFG['LANG']];
    }

    // Make buy link
    $var['link'] = str_replace("href=", "class=\"btn btn-primary\" href=", $buy[$info['ID']]);

    // Check if header
    $var['header'] = isset($noHeader) && $noHeader ? 0 : 1;

    // Check if currency
    $var['currency'] = isset($noCurrency) && $noCurrency ? 0 : 1;

    // Get images
    $var['images'] = array();
    foreach (glob(__DIR__ . "/../files/product_images/" . $info['ID'] . "-*") as $f) {
        array_push($var['images'], basename($f));
    }

    // Piwik eCommerce
    $id = "PRODUCT-" . $info['ID'];
    $name = $title;
    $cat = isset($var['category']) ? $var['category'] : "";
    $price = $info['price'];
    $var['pcomm'] .= "_paq.push(['setEcommerceView','$id','$name','$cat',$price]);";
}
