<?php
global $ari, $var, $db, $CFG, $lang, $session;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['CMS_LINKS']['TITLE']);
menu("cms");

// Check rights
if (!$ari->check(50)) {

    alog("general", "insufficient_page_rights", "cms_links");
    $tpl = "error";

} else {

    $tpl = "cms_links";
    $var['tab'] = $tab = !empty($_GET['tab']) ? $_GET['tab'] : 'active';

    if ($tab == "inactive" || $tab == "active") {
        if ($session->get('link_added')) {
            $session->remove('link_added');
            $var['success'] = $lang['CMS_LINKS']['CDONE'];
        }

        if (isset($_POST['links']) && is_array($_POST['links']) && count($_POST['links']) > 0) {
            $done = 0;

            if (isset($_POST['activate'])) {
                foreach ($_POST['links'] as $slug) {
                    $db->query("UPDATE cms_links SET status = 1 WHERE status = 0 AND slug = '" . $db->real_escape_string($slug) . "' LIMIT 1");
                    if ($db->affected_rows) {
                        alog("cms_links", "activated", $slug);
                    }

                    $done += $db->affected_rows;
                }

                if ($done == 1) {
                    $var['success'] = $lang['CMS_LINKS']['ONE_ACTIVATED'];
                } else if ($done > 0) {
                    $var['success'] = str_replace("%x", $done, $lang['CMS_LINKS']['X_ACTIVATED']);
                }

            } else if (isset($_POST['deactivate'])) {
                foreach ($_POST['links'] as $slug) {
                    $db->query("UPDATE cms_links SET status = 0 WHERE status = 1 AND slug = '" . $db->real_escape_string($slug) . "' LIMIT 1");
                    if ($db->affected_rows) {
                        alog("cms_links", "deactivated", $slug);
                    }

                    $done += $db->affected_rows;
                }

                if ($done == 1) {
                    $var['success'] = $lang['CMS_LINKS']['ONE_DEACTIVATED'];
                } else if ($done > 0) {
                    $var['success'] = str_replace("%x", $done, $lang['CMS_LINKS']['X_DEACTIVATED']);
                }

            } else if (isset($_POST['delete'])) {
                foreach ($_POST['links'] as $slug) {
                    $db->query("DELETE FROM cms_links WHERE slug = '" . $db->real_escape_string($slug) . "' LIMIT 1");
                    if ($db->affected_rows) {
                        alog("cms_links", "deleted", $slug);
                    }

                    $done += $db->affected_rows;
                }

                if ($done == 1) {
                    $var['success'] = $lang['CMS_LINKS']['ONE_DELETED'];
                } else if ($done > 0) {
                    $var['success'] = str_replace("%x", $done, $lang['CMS_LINKS']['X_DELETED']);
                }

            }
        }

        $var['links'] = [];

        $t = new Table("SELECT * FROM cms_links WHERE status = " . ($tab == "active" ? "1" : "0"), [
            "slug" => [
                "name" => "Name",
                "type" => "like",
            ],
            "target" => [
                "name" => "Ziel",
                "type" => "like",
            ],
        ], ["slug", "ASC"], "cms_links");
        $var['th'] = $t->getHeader();
        $var['tf'] = $t->getFooter();

        $var['table_order'] = [
            $t->orderHeader("slug", $lang["CMS_LINKS"]["LINK_SLUG"]),
            $t->orderHeader("calls", $lang["CMS_LINKS"]["LINK_CALLS"]),
        ];

        $sql = $t->qry("slug ASC");
        while ($row = $sql->fetch_array()) {
            array_push($var['links'], $row);
        }
    } else if ($tab == "create" && isset($_POST['action']) && $_POST['action'] == "add") {
        try {
            if (empty($_POST['slug'])) {
                throw new Exception($lang['CMS_LINKS']['ESLUG']);
            }

            if (!ctype_alnum(str_replace(array("_", "-"), "", $_POST['slug']))) {
                throw new Exception($lang['CMS_LINKS']['EFAIL']);
            }

            if (empty($_POST['target'])) {
                throw new Exception($lang['CMS_LINKS']['ETARGET']);
            }

            if (!isset($_POST['status']) || !in_array($_POST['status'], [0, 1])) {
                throw new Exception($lang['CMS_LINKS']['ESTATUS']);
            }

            if (!$db->query("INSERT INTO cms_links (`slug`, `target`, `status`) VALUES ('" . $db->real_escape_string($_POST['slug']) . "', '" . $db->real_escape_string($_POST['target']) . "', " . intval($_POST['status']) . ")")) {
                throw new Exception($lang['CMS_LINKS']['EXISTS']);
            }

            $session->set("link_added", true);
            alog("cms_links", "created", $_POST['slug']);
            header('Location: ./?p=cms_links&tab=' . ($_POST['status'] == "1" ? "active" : "inactive"));
            exit;
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
        }
    }

}
