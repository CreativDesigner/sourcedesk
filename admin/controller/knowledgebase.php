<?php
global $ari, $var, $db, $CFG, $lang, $session;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['KNOWLEDGEBASE']['TITLE']);
menu("cms");

// Check rights
if (!$ari->check(50)) {

    alog("general", "insufficient_page_rights", "knowledgebase");
    $tpl = "error";

} else {
    $tpl = "knowledgebase";
    $var['l'] = $lang['KNOWLEDGEBASE'];
    $var['step'] = "overview";
    $var['cats'] = KBCategory::getAll();

    if (isset($_GET['del_cat']) && is_object($cat = new KBCategory($_GET['del_cat'])) && $cat->ID == $_GET['del_cat'] && !count($cat->getQuestions())) {
        $db->query("DELETE FROM knowledgebase_categories WHERE ID = " . intval($cat->ID));
        header('Location: ?p=knowledgebase');
        exit;
    }

    if (isset($_GET['cat']) && is_numeric($_GET['cat']) && $db->query("SELECT 1 FROM knowledgebase_categories WHERE ID = " . intval($_GET['cat']))->num_rows) {
        $var['step'] = "cat";
        $var['cat'] = new KBCategory($_GET['cat']);

        if (isset($_GET['del_art']) && is_numeric($_GET['del_art']) && array_key_exists($_GET['del_art'], $var['cat']->getQuestions())) {
            $db->query("DELETE FROM knowledgebase WHERE ID = " . intval($_GET['del_art']));
            header('Location: ?p=knowledgebase&cat=' . intval($var['cat']->ID));
            exit;
        }

        $var['languages'] = [];

        foreach (Language::getClientLanguages() as $file) {
            $oldLang = $lang;
            $file = basename($file);
            require __DIR__ . "/../../languages/$file.php";
            $name = $lang['MANAGEMENT_NAME'];
            $lang = $oldLang;

            $var['languages'][basename($file)] = $name;
        }

        if (!empty($_POST['name'])) {
            $name = $db->real_escape_string(strval($_POST['name']));
            $status = ($_POST['public'] ?? 1) ? "1" : "0";
            $language = array_key_exists($_POST['language'] ?? "", $var['languages']) ? $_POST['language'] : array_keys($var['languages'])[0];
            $order = intval($_POST['order'] ?? 0);

            $db->query("UPDATE knowledgebase_categories SET `name` = '$name', `status` = $status, `language` = '$language', `order` = $order WHERE ID = " . intval($_GET['cat']));
            header('Location: ?p=knowledgebase&cat=' . $var['cat']->ID);
            exit;
        }
    } else if (isset($_GET['add_cat'])) {
        $var['step'] = "add_cat";
        $var['languages'] = [];

        foreach (Language::getClientLanguages() as $file) {
            $oldLang = $lang;
            $file = basename($file);
            require __DIR__ . "/../../languages/$file.php";
            $name = $lang['MANAGEMENT_NAME'];
            $lang = $oldLang;

            $var['languages'][basename($file)] = $name;
        }

        if (!empty($_POST['name'])) {
            $name = $db->real_escape_string(strval($_POST['name']));
            $status = ($_POST['public'] ?? 1) ? "1" : "0";
            $language = array_key_exists($_POST['language'] ?? "", $var['languages']) ? $_POST['language'] : array_keys($var['languages'])[0];
            $order = intval($_POST['order'] ?? 0);

            $db->query("INSERT INTO knowledgebase_categories (`name`, `status`, `language`, `order`) VALUES ('$name', $status, '$language', $order)");
            header('Location: ?p=knowledgebase&cat=' . $db->insert_id);
            exit;
        }
    } else if (isset($_GET['add_question']) && is_numeric($_GET['add_question']) && $db->query("SELECT 1 FROM knowledgebase_categories WHERE ID = " . intval($_GET['add_question']))->num_rows) {
        $var['step'] = "add_question";
        $var['cat'] = new KBCategory($_GET['add_question']);

        if (!empty($_POST['title'])) {
            $title = $db->real_escape_string(strval($_POST['title']));
            $article = $db->real_escape_string(strval($_POST['article']));
            $status = ($_POST['public'] ?? 1) ? "1" : "0";
            $order = intval($_POST['order'] ?? 0);

            $db->query("INSERT INTO knowledgebase (`title`, `status`, `article`, `order`, `category`) VALUES ('$title', $status, '$article', $order, " . intval($var['cat']->ID) . ")");
            header('Location: ?p=knowledgebase&cat=' . strval(intval($var['cat']->ID)));
            exit;
        }
    } else if (isset($_GET['question']) && is_numeric($_GET['question']) && $db->query("SELECT 1 FROM knowledgebase WHERE ID = " . intval($_GET['question']))->num_rows) {
        $var['step'] = "question";
        $var['q'] = new KBQuestion($_GET['question']);

        if (!empty($_POST['title'])) {
            $title = $db->real_escape_string(strval($_POST['title']));
            $article = $db->real_escape_string(strval($_POST['article']));
            $status = ($_POST['public'] ?? 1) ? "1" : "0";
            $order = intval($_POST['order'] ?? 0);

            $db->query("UPDATE knowledgebase SET `title` = '$title', `status` = $status, `article` = '$article', `order` = $order WHERE ID = " . intval($_GET['question']));
            header('Location: ?p=knowledgebase&cat=' . strval(intval($var['q']->category)));
            exit;
        }
    }
}
