<?php
// Global variables for security reasons
global $var, $lang, $user, $pars, $cur, $nfo, $db, $CFG, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['KNOWLEDGEBASE']['TITLE'];
$tpl = "knowledgebase";
$var['l'] = $lang['KNOWLEDGEBASE'];

$step = "home";

if (!empty($_POST['searchword'])) {
    $step = "search";
    $var['res'] = [];

    $query = "";
    $query2 = "";
    $ex = explode(" ", trim($_POST['searchword']));
    foreach ($ex as $sp) {
        if (empty($sp)) {
            continue;
        }

        $query .= "`article` LIKE '%" . $db->real_escape_string($sp) . "%' AND";
        $query2 .= "`title` LIKE '%" . $db->real_escape_string($sp) . "%' AND";
    }
    $query = rtrim($query, " AND");
    $query2 = rtrim($query2, " AND");

    $sql = $db->query("SELECT ID FROM knowledgebase WHERE status = 1 AND (($query) OR ($query2))");
    while ($row = $sql->fetch_object()) {
        array_push($var['res'], new KBQuestion($row->ID));
    }
}

if (isset($pars[0]) && is_numeric($pars[0]) && ((new KBCategory($pars[0]))->ID ?? 0) == $pars[0]) {
    $step = "category";
    $var['cat'] = new KBCategory($pars[0]);
    $title = $var['cat']->name;
    $var['qs'] = $var['cat']->getQuestions(true);

    if (isset($pars[1]) && is_numeric($pars[1]) && array_key_exists($pars[1], $var['qs'])) {
        $step = "question";
        $var['q'] = $var['qs'][$pars[1]];
        $title = $var['q']->title;

        if (!is_array($_SESSION['kb_rated'])) {
            $_SESSION['kb_rated'] = [];
        }

        if (!is_array($_SESSION['kb_viewed'])) {
            $_SESSION['kb_viewed'] = [];
        }
        if (!in_array($pars[1], $_SESSION['kb_viewed'])) {
            array_push($_SESSION['kb_viewed'], $pars[1]);
            $db->query("UPDATE knowledgebase SET views = views + 1 WHERE ID = " . intval($pars[1]));
        }

        $var['can_rate'] = !in_array($pars[1], $_SESSION['kb_rated']);

        if ($var['can_rate'] && isset($_POST['rating'])) {
            array_push($_SESSION['kb_rated'], $pars[1]);
            $db->query("UPDATE knowledgebase SET ratings = ratings + 1 WHERE ID = " . intval($pars[1]));
            if ($_POST['rating']) {
                $db->query("UPDATE knowledgebase SET positive = positive + 1 WHERE ID = " . intval($pars[1]));
            }
        }
    }
}

if ($step == "home") {
    $var['cats'] = KBCategory::getAll($CFG['LANG']);
    $var['popq'] = KBQuestion::getPop($CFG['LANG']);
}

$var['step'] = $step;
