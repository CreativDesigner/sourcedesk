<?php
global $lang, $db, $CFG, $pars, $var, $dfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['BLOG']['TITLE'];
$tpl = "blog";

$view = "home";
$var['view'] = &$view;

$var['meta_tags'] .= '<link href="' . $CFG['PAGEURL'] . 'blog/rss" rel="alternate" type="application/rss+xml" title="' . $CFG['PAGENAME'] . ' ' . $lang['BLOG']['TITLE'] . '" />';

$post = isset($pars[0]) && is_numeric($pars[0]) ? $pars[0] : 0;

if (isset($pars[0]) && $pars[0] == "rss") {
    header("Content-Type: application/xml; charset=UTF-8");
    echo '<?xml version="1.0" encoding="UTF-8" ?>';
    ?>
	<rss version="2.0">
		<channel>
			<title><?=$CFG['PAGENAME'] . " " . $lang['BLOG']['TITLE'];?></title>
			<link><?=$CFG['PAGEURL'] . "blog";?></link>
			<language><?=$lang['NAME'];?></language>

			<?php
$sql = $db->query("SELECT * FROM cms_blog ORDER BY time DESC, ID DESC");
    while ($row = $sql->fetch_object()) {?>
			<item>
				<title><?=@unserialize($row->title) ? unserialize($row->title)[$CFG['LANG']] : $row->title;?></title>
				<link><?=$CFG['PAGEURL'] . "blog/" . $row->ID;?></link>
				<description><![CDATA[<?=array_shift(explode("<br>", str_replace(["<br/>", "<br />"], "<br>", nl2br(@unserialize($row->text) ? unserialize($row->text)[$CFG['LANG']] : $row->text))));?>]]></description>
				<pubdate><?=date("r", $row->time);?></pubdate>
			</item>
			<?php }?>
		</channel>
	</rss>
	<?php
exit;
}

if ($post != 0 && $db->query("SELECT 1 FROM cms_blog WHERE ID = " . intval($pars[0]))->num_rows == 1) {
    $view = "post";
    $row = $db->query("SELECT * FROM cms_blog WHERE ID = " . intval($pars[0]))->fetch_object();

    $e = array();
    $var['e'] = &$e;
    $e['ID'] = $row->ID;
    $e['admin'] = $db->query("SELECT 1 FROM admins WHERE ID = " . $row->admin)->num_rows == 1 ? $db->query("SELECT name FROM admins WHERE ID = " . $row->admin)->fetch_object()->name : "";
    $e['time'] = $dfo->format($row->time, false);
    $e['title'] = $title = (@unserialize($row->title) ? unserialize($row->title)[$CFG['LANG']] : $row->title);
    $e['text'] = @unserialize($row->text) ? unserialize($row->text)[$CFG['LANG']] : $row->text;

    $var['previous'] = array();
    $var['next'] = array();

    $offset = -1;

    do {
        $offset++;
        $sql = $db->query("SELECT * FROM cms_blog ORDER BY time DESC, ID DESC LIMIT $offset,1");
    } while ($sql->num_rows == 1 && $sql->fetch_object()->ID != $row->ID);

    $prevSql = $db->query("SELECT * FROM cms_blog ORDER BY time DESC, ID DESC LIMIT " . ($offset + 1) . ",1");
    if ($prevSql->num_rows == 1) {
        $prev = $prevSql->fetch_object();
        $var['previous'] = array("id" => $prev->ID, "title" => (@unserialize($prev->title) ? unserialize($prev->title)[$CFG['LANG']] : $prev->title));
    }

    $nextSql = $db->query("SELECT * FROM cms_blog ORDER BY time DESC, ID DESC LIMIT " . ($offset - 1) . ",1");
    if ($nextSql->num_rows == 1) {
        $next = $nextSql->fetch_object();
        $var['next'] = array("id" => $next->ID, "title" => (@unserialize($next->title) ? unserialize($next->title)[$CFG['LANG']] : $next->title));
    }
} else {
    $entriesPerPage = 5;

    $page = isset($pars[0]) && $pars[0] == "page" && isset($pars[1]) && is_numeric($pars[1]) ? $pars[1] : 1;
    if ($page < 1) {
        $page = 1;
    }

    $var['page'] = &$page;

    $num = $db->query("SELECT 1 FROM cms_blog")->num_rows;
    $page++;

    do {
        $offset = (--$page - 1) * $entriesPerPage;
    } while ($offset > $num);

    $sql = $db->query("SELECT * FROM cms_blog ORDER BY time DESC, ID DESC LIMIT $offset,$entriesPerPage");
    $var['entries'] = array();
    $entries = &$var['entries'];

    while ($row = $sql->fetch_object()) {
        $e = array();
        $e['ID'] = $row->ID;
        $e['admin'] = $db->query("SELECT 1 FROM admins WHERE ID = " . $row->admin)->num_rows == 1 ? $db->query("SELECT name FROM admins WHERE ID = " . $row->admin)->fetch_object()->name : "";
        $e['time'] = $dfo->format($row->time, false);
        $e['title'] = @unserialize($row->title) ? unserialize($row->title)[$CFG['LANG']] : $row->title;
        $e['text'] = array_shift(explode("<br>", str_replace(["<br/>", "<br />"], "<br>", nl2br(@unserialize($row->text) ? unserialize($row->text)[$CFG['LANG']] : $row->text))));

        array_push($entries, $e);
    }

    $var['pages'] = ceil($num / $entriesPerPage);
}