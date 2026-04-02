<?php
// Class to create a sitemap

class Sitemap
{
    public static function sites()
    {
        global $lang, $CFG;

        $arr = [
            $CFG['PAGEURL'] => $lang['INDEX']['TITLE'],
            $CFG['PAGEURL'] . "testimonials" => $lang['TESTIMONIALS']['TITLE'],
            $CFG['PAGEURL'] . "faq" => $lang['FAQ']['TITLE'],
            $CFG['PAGEURL'] . "cart" => $lang['CART']['TITLE'],
            $CFG['PAGEURL'] . "blog" => $lang['BLOG']['TITLE'],
            $CFG['PAGEURL'] . "forum" => $lang['FORUM']['TITLE'],
            $CFG['PAGEURL'] . "knowledgebase" => $lang['KNOWLEDGEBASE']['TITLE'],
            $CFG['PAGEURL'] . "newsletter" => $lang['NEWSLETTER']['TITLE'],
        ];

        $entries = CMS::getEntries() ?: [];

        foreach ($entries as $entry) {
            $id = $entry['id'];
            $obj = CMS::getObject($entry);
            if (!is_object($obj)) {
                continue;
            }

            $childs = CMS::getEntries($id);

            if (is_array($childs) && count($childs) > 0) {
                $submenu = [];
                foreach ($childs as $entry2) {
                    $obj2 = CMS::getObject($entry2);
                    if (!is_object($obj2)) {
                        continue;
                    }

                    $submenu[$obj2->getLink()] = $obj2->getName();
                }
                $arr[$obj->getLink()] = [$obj->getMenuName(), $submenu];
            } else {
                $arr[$obj->getLink()] = $obj->getMenuName();
            }
        }

        return array_merge($arr, [
            "domains" => [
                $lang['DOMAINS']['TITLE'],
                [
                    $CFG['PAGEURL'] . "domains/pricing" => $lang['DOMAINS']['PRICING'],
                    $CFG['PAGEURL'] . "auth2" => $lang['DOMAINS']['AUTH2'],
                    $CFG['PAGEURL'] . "domains/dyndns" => $lang['DOMAINS']['DYNDNS'],
                    $CFG['PAGEURL'] . "domains/api" => $lang['DOMAINS']['API'],
                ],
            ],
            "dashboard" => [
                $lang['NAV']['ACCOUNT'],
                [
                    $CFG['PAGEURL'] . "dashboard" => $lang['NAV']['DASHBOARD'],
                    $CFG['PAGEURL'] . "products" => $lang['PRODUCTS']['TITLE'],
                    $CFG['PAGEURL'] . "projects" => $lang['NAV']['PROJECTS'],
                    $CFG['PAGEURL'] . "invoices" => $lang['INVOICES']['TITLE'],
                    $CFG['PAGEURL'] . "credit" => $lang['CREDIT']['TITLE'],
                    $CFG['PAGEURL'] . "file" => $lang['NAV']['FILES'],
                    $CFG['PAGEURL'] . "mails" => $lang['NAV']['MAILS'],
                    $CFG['PAGEURL'] . "affiliate" => $lang['AFFILIATE']['TITLE'],
                    $CFG['PAGEURL'] . "bugtracker" => $lang['BUGTRACKER']['TITLE'],
                    $CFG['PAGEURL'] . "wishlist" => $lang['WISHLIST']['TITLE'],
                    $CFG['PAGEURL'] . "tickets" => $lang['TICKETS']['TITLE'],
                    $CFG['PAGEURL'] . "profile" => $lang['NAV']['PROFILE'],
                    $CFG['PAGEURL'] . "contacts" => $lang['CONTACTS']['TITLE'],
                ],
            ],
            $CFG['PAGEURL'] . "contact" => $lang['CONTACT']['TITLE'],
            $CFG['PAGEURL'] . "terms" => $lang['TOS']['LONG'],
            $CFG['PAGEURL'] . "withdrawal" => $lang['WITHDRAWAL']['TITLE'],
            $CFG['PAGEURL'] . "license" => [
                $lang['GENERAL']['LICENSE_TERMS'],
                [
                    $CFG['PAGEURL'] . "license/e" => $lang['PRODUCTS']['LICENSE_E'],
                    $CFG['PAGEURL'] . "license/r" => $lang['PRODUCTS']['LICENSE_R'],
                ],
            ],
            $CFG['PAGEURL'] . "privacy" => $lang['PRIVACY']['TITLE'],
            $CFG['PAGEURL'] . "imprint" => $lang['IMPRINT']['TITLE'],
        ]);
    }

    private static function allLinks()
    {
        $sites = self::sites();

        $links = [];

        foreach ($sites as $u => $e) {
            if (!is_array($e)) {
                array_push($links, $u);
            } else {
                foreach ($e[1] as $u2 => $e2) {
                    array_push($links, $u2);
                }
            }
        }

        return $links;
    }

    public static function xml()
    {
        ob_start();
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        ?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><?php foreach (self::allLinks() as $u) {?><url><loc><?=$u;?></loc></url><?php }?></urlset>
        <?php
$xml = ob_get_contents();
        ob_end_clean();

        return $xml;
    }
}