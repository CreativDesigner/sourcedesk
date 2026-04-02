<?php
global $db, $CFG, $var, $user, $lang, $pars;

$title = $lang['FORUM']['TITLE'];
$tpl = "forum";
$var['l'] = $l = $lang['FORUM'];

function forumNotify($tid)
{
    global $db, $CFG, $maq;

    $tid = intval($tid);
    $sql = $db->query("SELECT * FROM forum_threads WHERE ID = " . $tid);
    if (!$sql->num_rows) {
        return;
    }
    $t = $sql->fetch_object();

    $subscribers = [];

    $sql = $db->query("SELECT * FROM forum_entries WHERE thread = " . $tid . " ORDER BY `time` DESC, `ID` DESC");
    $author = 0;
    while ($row = $sql->fetch_object()) {
        if (empty($author)) {
            $author = $row->user;
        }

        if ($row->user == $author) {
            continue;
        }

        if (!in_array($row->user, $subscribers)) {
            array_push($subscribers, $row->user);
        }
    }

    if (empty($author)) {
        return;
    }

    $sql = $db->query("SELECT user_id FROM forum_moderators WHERE forum_id = " . $t->forum);
    while ($row = $sql->fetch_object()) {
        if ($row->user_id == $author) {
            continue;
        }

        if (!in_array($row->user_id, $subscribers)) {
            array_push($subscribers, $row->user_id);
        }
    }

    foreach ($subscribers as $uid) {
        $u = User::getInstance($uid, "ID");
        if (!$u) {
            continue;
        }

        $mtObj = new MailTemplate("Antwort im Forum");
        $titlex = $mtObj->getTitle($u->getLanguage());
        $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);
        
        $maq->enqueue([
            "author" => $author,
            "thread" => $t->title,
            "url" => $CFG['PAGEURL'] . "forum/" . $t->forum . "/" . $t->ID,
        ], $mtObj, $u->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], false, 0, 0, $mtObj->getAttachments($CFG['LANG']));
    }
}

$var['forums'] = [];
$sql = $db->query("SELECT * FROM forum ORDER BY `order` ASC, `name` ASC, ID ASC");
while ($row = $sql->fetch_object()) {
    if (!$row->public && !$var['logged_in']) {
        continue;
    }

    if (!empty($row->pids)) {
        $pids = explode(",", $row->pids);
        $cid = intval($var['logged_in'] ? $user->get()['ID'] : 0);
        $found = false;

        $row->mod = $var['logged_in'] && $db->query("SELECT 1 FROM forum_moderators WHERE forum_id = {$row->ID} AND user_id = " . $user->get()['ID'])->num_rows;

        if (!$row->mod) {
            $sql2 = $db->query("SELECT product FROM client_products WHERE user = $cid");
            while ($row2 = $sql2->fetch_object()) {
                if (in_array($row2->product, $pids)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                continue;
            }
        }
    }

    $row->threads = $db->query("SELECT COUNT(*) c FROM forum_threads WHERE forum = " . intval($row->ID))->fetch_object()->c;
    $row->entries = $db->query("SELECT COUNT(*) c FROM forum_entries e, forum_threads t WHERE e.thread = t.ID AND t.forum = " . intval($row->ID))->fetch_object()->c;

    $var['forums'][$row->ID] = (array) $row;
}

$var['step'] = "overview";
$var['nick_warning'] = false;

if (!empty($pars[0]) && array_key_exists($pars[0], $var['forums'])) {
    $var['step'] = "threads";
    $var['f'] = $var['forums'][$pars[0]];
    $title = htmlentities($var['f']['name']);
    $var['threads'] = [];

    $perPage = 20;
    $pages = ceil($db->query("SELECT COUNT(*) c FROM forum_threads WHERE forum = " . $var['f']['ID'])->fetch_object()->c / $perPage);
    $page = min($pages, max(1, intval(!empty($pars[1]) && $pars[1] == "p" && !empty($pars[2]) && is_numeric($pars[2]) ? $pars[2] : 1)));
    $offset = ($page - 1) * $perPage;

    $var['cPage'] = $page;
    $var['pages'] = $pages;

    $avatarSize = 40;

    $sql = $db->query("SELECT t.ID, t.title, t.lock, e.user, e.time FROM forum_threads t, forum_entries e WHERE t.forum = " . $var['f']['ID'] . " AND e.thread = t.ID GROUP BY t.ID ORDER BY e.time DESC, t.ID DESC LIMIT $offset,$perPage");
    if ($sql) {
        while ($row = $sql->fetch_assoc()) {
            $uI = User::getInstance($row['user'], "ID");
            if ($uI) {
                $row["author"] = [
                    $uI->getAvatar($avatarSize),
                    $uI->get()['nickname'],
                    $CFG['PAGEURL'] . "forum/profile/" . urlencode($uI->get()['nickname']),
                ];
            } else {
                $row["author"] = [
                    "https://www.gravatar.com/avatar/?s=$avatarSize&d=mp&r=g",
                    "-",
                ];
            }

            $row['entries'] = $db->query("SELECT COUNT(*) c FROM forum_entries WHERE thread = " . intval($row['ID']))->fetch_object()->c;

            $var['threads'][$row['ID']] = $row;
        }
    }

    if (!empty($pars[1]) && $pars[1] == "add" && $var['logged_in']) {
        if (empty($user->get()['nickname'])) {
            $var['nick_warning'] = true;
        } else {
            $var['step'] = "add_thread";

            if (isset($_POST['title'])) {
                if (empty(trim($_POST['title']))) {
                    $var['error'] = $l['NOTITLE'];
                } else {
                    if (empty($_POST['text']) || (strlen(trim($_POST['text'])) < 50 && !$var['f']['mod'])) {
                        $var['error'] = $l['NOTEXT'];
                    } else {
                        $db->query("INSERT INTO forum_threads (`title`, `forum`) VALUES ('" . $db->real_escape_string(trim($_POST['title'])) . "', {$var['f']['ID']})");
                        $tid = $db->insert_id;

                        $db->query("INSERT INTO forum_entries (`user`, `time`, `text`, `thread`) VALUES (" . $user->get()['ID'] . ", " . time() . ", '" . $db->real_escape_string($_POST['text']) . "', $tid)");

                        forumNotify($tid);

                        header('Location: ' . $CFG['PAGEURL'] . 'forum/' . $var['f']['ID'] . '/' . $tid);
                        exit;
                    }
                }
            }
        }
    }

    if (!empty($pars[1]) && is_numeric($pars[1]) && $db->query("SELECT 1 FROM forum_threads WHERE forum = " . intval($var['f']['ID']) . " AND ID = " . intval($pars[1]))->num_rows) {
        $var['step'] = "thread";
        $var['t'] = $db->query("SELECT * FROM forum_threads WHERE forum = " . intval($var['f']['ID']) . " AND ID = " . intval($pars[1]))->fetch_assoc();
        $title = htmlentities($var['t']['title']);

        if (isset($_POST['forum'])) {
            $forum = intval($_POST['forum']);

            if (array_key_exists($forum, $var['forums'])) {
                switch ($_POST['action'] ?? "") {
                    case "move":
                        if ($var['f']['mod']) {
                            $db->query("UPDATE forum_threads SET forum = $forum WHERE ID = " . $var['t']['ID']);
                            header('Location: ' . $CFG['PAGEURL'] . 'forum/' . $forum . '/' . $var['t']['ID']);
                            exit;
                        }

                    case "go":
                        header('Location: ' . $CFG['PAGEURL'] . 'forum/' . $forum);
                        exit;
                }
            }
        }

        if (isset($_POST['edit']) && isset($_POST['post']) && $var['f']['mod']) {
            $pid = intval($_POST['edit']);
            $post = $db->real_escape_string($_POST['post']);
            $db->query("UPDATE forum_entries SET `text` = '$post' WHERE ID = $pid AND thread = " . $var['t']['ID']);
        }

        $perPage = 10;
        $pages = ceil($db->query("SELECT COUNT(*) c FROM forum_entries WHERE thread = " . $var['t']['ID'])->fetch_object()->c / $perPage);
        $page = min($pages, max(1, intval(!empty($pars[2]) && $pars[2] == "p" && !empty($pars[3]) && is_numeric($pars[3]) ? $pars[3] : 1)));
        $offset = ($page - 1) * $perPage;

        if (!empty($pars[2]) && $pars[2] == "lock" && $var['f']['mod']) {
            $lock = ($pars[3] ?? "0") ? 1 : 0;
            $db->query("UPDATE forum_threads SET `lock` = $lock WHERE ID = " . $var['t']['ID']);
            header('Location: ' . $CFG['PAGEURL'] . 'forum/' . $var['f']['ID'] . '/' . $var['t']['ID']);
        }

        if (!empty($pars[2]) && $pars[2] == "delpost" && $var['f']['mod']) {
            $post = intval($pars[3] ?? 0);
            $db->query("DELETE FROM forum_entries WHERE ID = $post AND thread = " . $var['t']['ID']);
            header('Location: ' . $CFG['PAGEURL'] . 'forum/' . $var['f']['ID'] . '/' . $var['t']['ID']);
        }

        if (!empty($pars[2]) && $pars[2] == "delete" && $var['f']['mod']) {
            $db->query("DELETE FROM forum_threads WHERE ID = " . $var['t']['ID']);
            header('Location: ' . $CFG['PAGEURL'] . 'forum/' . $var['f']['ID']);
        }

        if (isset($_POST['answer']) && $var['logged_in'] && !empty($user->get()['nickname']) && (!$var['t']['lock'] || $var['f']['mod'])) {
            if (empty($_POST['answer']) || (strlen(trim($_POST['answer'])) < 50 && !$var['f']['mod'])) {
                $var['error'] = $l['NOTEXT'];
            } else {
                $tid = intval($var['t']['ID']);
                $db->query("INSERT INTO forum_entries (`user`, `time`, `text`, `thread`) VALUES (" . $user->get()['ID'] . ", " . time() . ", '" . $db->real_escape_string($_POST['answer']) . "', $tid)");

                forumNotify($tid);

                $pages = ceil($db->query("SELECT COUNT(*) c FROM forum_entries WHERE thread = " . $var['t']['ID'])->fetch_object()->c / $perPage);

                header('Location: ' . $CFG['PAGEURL'] . 'forum/' . $var['f']['ID'] . '/' . $tid . '/p/' . $pages);
                exit;
            }
        }

        $var['cPage'] = $page;
        $var['pages'] = $pages;

        $avatarSize = 120;

        $var['entries'] = [];

        $sql = $db->query("SELECT * FROM forum_entries WHERE thread = " . $var['t']['ID'] . " ORDER BY time ASC, ID ASC LIMIT $offset,$perPage");
        if ($sql) {
            while ($row = $sql->fetch_assoc()) {
                $uI = User::getInstance($row['user'], "ID");
                if ($uI) {
                    $row["author"] = [
                        $uI->getAvatar($avatarSize),
                        $uI->get()['nickname'],
                        $CFG['PAGEURL'] . "forum/profile/" . urlencode($uI->get()['nickname']),
                        $db->query("SELECT COUNT(*) c FROM forum_entries WHERE user = " . $uI->get()['ID'])->fetch_object()->c,
                        $db->query("SELECT 1 FROM forum_moderators WHERE user_id = " . $uI->get()['ID'] . " AND forum_id = " . intval($var['f']['ID']))->num_rows,
                    ];
                } else {
                    $row["author"] = [
                        "https://www.gravatar.com/avatar/?s=$avatarSize&d=mp&r=g",
                        "-",
                    ];
                }

                $var['entries'][$row['ID']] = $row;
            }
        }
    }
}

if (!empty($pars[0]) && $pars[0] == "profile" && !empty($pars[1]) && $db->query("SELECT 1 FROM clients WHERE nickname LIKE '" . $db->real_escape_string($pars[1]) . "'")->num_rows) {
    $var['step'] = "nickpage";
    $user = User::getInstance($db->query("SELECT ID FROM clients WHERE nickname LIKE '" . $db->real_escape_string($pars[1]) . "'")->fetch_object()->ID, "ID");
    $title = $l['PROFILEOF'] . " " . htmlentities($user->get()['nickname']);

    $var['avatar'] = $user->getAvatar(120);
    $var['nickname'] = $user->get()['nickname'];
    $var['mod'] = $db->query("SELECT 1 FROM forum_moderators WHERE user_id = " . $user->get()['ID'])->num_rows > 0;
    $var['count'] = $db->query("SELECT 1 FROM forum_entries WHERE user = " . $user->get()['ID'])->num_rows;
    $var['has_posts'] = $var['count'] > 0;

    $var['entries'] = [];
    $allowed = implode(",", array_keys($var['forums']));
    $sql = $db->query("SELECT * FROM forum_entries e, forum_threads t WHERE e.thread = t.ID AND e.user = " . $user->get()['ID'] . " AND t.forum IN ($allowed) ORDER BY e.time DESC, e.ID DESC LIMIT 10");
    while ($row = $sql->fetch_assoc()) {
        $var['entries'][] = $row;
    }
}

if (!empty($_POST['searchword'])) {
    $title = $l['SEARCHYAH'];
    $var['step'] = "search";
    $var['res'] = [];

    $var['threads'] = [];
    $allowed = implode(",", array_keys($var['forums']));

    $qry = "";
    $ex = explode(" ", trim($_POST['searchword']));
    foreach ($ex as $sp) {
        if (empty($sp)) {
            continue;
        }

        $qry .= "`title` LIKE '%" . $db->real_escape_string($sp) . "%' AND";
    }
    $qry = rtrim($qry, " AND");

    $sql = $db->query("SELECT * FROM forum_threads WHERE ($qry) AND forum IN ($allowed) ORDER BY ID DESC");
    while ($row = $sql->fetch_assoc()) {
        $var['threads'][] = $row;
    }
}
