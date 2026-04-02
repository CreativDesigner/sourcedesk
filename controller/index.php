<?php
// Global a few variables for security reasons
global $db, $CFG, $nfo, $var, $lang;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if ($CFG['REDIRECT_LOGIN']) {
    header('Location: ' . $CFG['PAGEURL'] . ($var['logged_in'] ? 'dashboard' : 'login'));
    exit;
}

$title = $lang['INDEX']['TITLE'];
$tpl = "index";

// Select new products from database and assign it to template
$np = array();
$npSql = $db->query("SELECT ID, name FROM products WHERE status = 1 AND public = 1 ORDER BY ID DESC LIMIT 50");
while ($r = $npSql->fetch_object()) {
    if (should_show_product($r->ID)) {
        $np[] = array("ID" => $r->ID, "name" => unserialize($r->name)[$CFG['LANG']]);

        if (count($np) >= 5) {
            break;
        }
    }
}

$var['np'] = $np;

// Select top 5 products from database
$sql = $db->query("SELECT COUNT(*) AS c, product AS p FROM `client_products` GROUP BY product ORDER BY c DESC");
$var['pp'] = array();
while ($row = $sql->fetch_object()) {
    $sql2 = $db->query("SELECT name FROM products WHERE status = 1 AND public = 1 AND ID = " . $row->p);
    if ($sql2->num_rows != 1) {
        continue;
    }

    if (!should_show_product($row->p)) {
        continue;
    }

    $var['pp'][] = $pp[] = array("ID" => $row->p, "name" => unserialize($sql2->fetch_object()->name)[$CFG['LANG']]);
    if (count($var['pp']) >= 5) {
        break;
    }
}

// Count products, customers and licenses in system
$var['productcount'] = $nfo->format($db->query("SELECT COUNT(*) AS c FROM products WHERE status = 1")->fetch_object()->c, 0);
$var['licensecount'] = $nfo->format($db->query("SELECT COUNT(*) AS c FROM client_products")->fetch_object()->c, 0);

// Get random testimonial
$testimonials = Testimonials::get("ID DESC", -1);
if (count($testimonials) > 0) {
    $testimonial = $testimonials[array_rand($testimonials)];
    $var['testimonial'] = $testimonial->getText();
    $var['testimonial_subject'] = $testimonial->getSubject();
    $var['rating'] = $testimonial->getRating();
    $var['testimonial_author'] = $testimonial->getAuthor();
}

foreach ($testimonials as &$t) {
    $t = null;
}

// Home intro
$var['intro'] = @unserialize($CFG['HOME_INTRO']) ? unserialize($CFG['HOME_INTRO'])[$lang['ISOCODE']] : $CFG['HOME_INTRO'];

// CMS handling
$operator = $var['logged_in'] ? ">=" : "=";
if ($db->query("SELECT 1 FROM cms_pages WHERE slug = 'index' AND active $operator 1")->num_rows) {
    $_GET['p'] = "index";
    require __DIR__ . "/cms.php";
}
