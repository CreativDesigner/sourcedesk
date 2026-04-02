<?php
// Global some variables for security reasons
global $buy, $var, $db, $CFG, $nfo, $lang, $cur, $pars, $noHeader, $noCurrency;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// We get the requested category ID and gather a few information about this cat
$id = isset($pars[0]) ? $db->real_escape_string($pars[0]) : -1;
$sql = $db->query("SELECT * FROM product_categories WHERE ID = '$id' AND view = 1");

// If the category does not exist, we only show an error page
if ($sql->num_rows != 1) {

    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";

} else {

    $cat = $sql->fetch_object();
    $title = unserialize($cat->name)[$CFG['LANG']];
    $tpl = "category_view";

    // We now need to get all active articles within this category
    $articlesSql = $db->query("SELECT * FROM products WHERE status = 1 AND category = " . $cat->ID . " ORDER BY `order` ASC, ID ASC");

    // We make an array with article information and iterate the database entries
    $articles = array();
    while ($a = $articlesSql->fetch_object()) {
        $img = array();
        foreach (glob(__DIR__ . "/../files/product_images/" . $a->ID . "-*") as $f) {
            array_push($img, basename($f));
        }

        if (should_show_product($a->ID)) {
            $raw_desc = trim(unserialize($a->description)[$CFG['LANG']]);

            $parameters = [];
            $parsed_desc = "";
            $ex = explode("\n", $raw_desc);

            foreach ($ex as $line) {
                $ex2 = explode(":", $line, 2);
                if (count($ex2) == 2 && !empty($ex2[0])) {
                    $parameters[$ex2[0]] = $ex2[1];
                } else {
                    $parsed_desc .= $ex2[0] . "\n";
                }
            }

            $articles[$a->order . trim(str_replace(unserialize($cat->cast)[$CFG['LANG']], "", unserialize($a->name)[$CFG['LANG']])) . rand(10000, 99999)] = array(
                "ID" => $a->ID,
                "name" => trim(str_replace(unserialize($cat->cast)[$CFG['LANG']], "", unserialize($a->name)[$CFG['LANG']])),
                "images" => $img,
                "description" => nl2br($raw_desc),
                "parsed_desc" => nl2br(trim($parsed_desc)),
                "parameters" => $parameters,
                "demo" => $a->demo,
                "price" => $cur->convertAmount(null, $a->price, null, true, true),
                "price_f" => $nfo->format($cur->convertAmount(null, $a->price, null, true, true)),
                "link" => $buy[$a->ID],
                "type" => $a->type,
                "billing" => $a->billing,
            );
        }
    }
    ksort($articles);

    // Now we set a few information for template
    $var['articles'] = $articles;
    $var['row'] = 1;
    $var['cat'] = $cat->ID;

    // Check if header
    $var['header'] = isset($noHeader) && $noHeader ? 0 : 1;

    // Check if currency
    $var['currency'] = isset($noCurrency) && $noCurrency ? 0 : 1;

    // Piwik eCommerce
    $var['pcomm'] .= "_paq.push(['setEcommerceView',false,false,'$title']);";

    // Order template handling
    $theme = basename($cat->template);

    if (!is_dir(__DIR__ . "/../themes/order/" . $theme)) {
        $theme = "standard";
    }

    if (!file_exists(__DIR__ . "/../themes/order/$theme/order.tpl")) {
        $theme = "standard";
    }

    $var['file'] = realpath(__DIR__ . "/../themes/order/$theme/order.tpl");
}
