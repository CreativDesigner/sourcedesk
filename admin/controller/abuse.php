<?php
global $tpl, $lang, $var, $db, $CFG, $maq, $ari;

$l = $lang['ABUSE'];

if ($ari->check(68)) {
    menu("support");
    title($l['TITLE']);

    $tpl = "abuse";
    $var['l'] = $l;

    function tid($aid)
    {
        global $db, $CFG, $adminInfo;

        $sql = $db->query("SELECT ticket FROM support_ticket_answers WHERE ID = " . intval($aid));
        if (!$sql->num_rows) {
            return false;
        }
        $tid = $sql->fetch_object()->ticket;

        $sql = $db->query("SELECT dept FROM support_tickets WHERE ID = " . intval($tid));
        if (!$sql->num_rows) {
            return false;
        }
        $dept = $sql->fetch_object()->dept;

        $my_depts = array($adminInfo->ID / -1);
        $sql = $db->query("SELECT dept FROM support_department_staff WHERE staff = " . intval($adminInfo->ID));
        while ($row = $sql->fetch_object()) {
            $ds = $db->query("SELECT ID FROM support_departments WHERE ID = " . $row->dept);
            while ($sd = $ds->fetch_object()) {
                array_push($my_depts, $sd->ID);
            }
        }

        return in_array($dept, $my_depts) ? $tid : false;
    }

    if (!empty($_GET['id']) && $db->query("SELECT 1 FROM abuse WHERE ID = " . intval($_GET['id']))->num_rows) {
        $info = $db->query("SELECT * FROM abuse WHERE ID = " . intval($_GET['id']))->fetch_object();
        $tpl = "abuse_details";

        $var['a'] = (array) $info;
        $var['ci'] = ci($info->user);

        if (!empty($_POST['answer'])) {
            $db->query("INSERT INTO abuse_messages (report, author, `time`, `text`) VALUES ({$info->ID}, 'staff', '" . date("Y-m-d H:i:s") . "', '" . $db->real_escape_string($_POST['answer']) . "')");

            if (is_object($u = User::getInstance($info->user, "ID"))) {
                $mtObj = new MailTemplate("Abuse-Meldung aktualisiert");
                $titlex = $mtObj->getTitle($u->getLanguage());
                $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);
                $maq->enqueue([
                    "subject" => $info->subject,
                    "url" => $CFG['PAGEURL'] . "abuse/" . $info->ID,
                ], $mtObj, $u->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], false, 0, 0, $u->getLanguage());
            }

            header('Location: ?p=abuse&id=' . $info->ID);
            exit;
        }

        if (!empty($_POST['subject'])) {
            $subject = $_POST['subject'] ?? "";
            $status = $_POST['status'] ?? "open";
            $time = date("Y-m-d H:i:s", strtotime($_POST['time'] ?? date("d.m.Y H:i:s")));
            $deadline = date("Y-m-d H:i:s", strtotime($_POST['deadline'] ?? date("d.m.Y H:i:s")));
            $customer = $_POST['user'] ?? 0;
            $service = $_POST['service'] ?? 0;

            $sql = $db->prepare("UPDATE abuse SET user = ?, contract = ?, status = ?, time = ?, deadline = ?, subject = ? WHERE ID = ?");
            $sql->bind_param("iissssi", $customer, $service, $status, $time, $deadline, $subject, $info->ID);
            $sql->execute();
            $sql->close();

            if (is_object($u = User::getInstance($customer, "ID"))) {
                $mtObj = new MailTemplate("Abuse-Meldung aktualisiert");
                $titlex = $mtObj->getTitle($u->getLanguage());
                $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);
                $maq->enqueue([
                    "subject" => $info->subject,
                    "url" => $CFG['PAGEURL'] . "abuse/" . $info->ID,
                ], $mtObj, $u->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], false, 0, 0, $u->getLanguage());
            }

            header('Location: ?p=abuse&id=' . $info->ID);
            exit;
        }

        $var['messages'] = [];
        $sql2 = $db->query("SELECT * FROM abuse_messages WHERE report = " . $info->ID . " ORDER BY `time` DESC, `ID` DESC");
        while ($msg = $sql2->fetch_assoc()) {
            $var['messages'][] = $msg;
        }

        if (isset($_POST['user_services'])) {
            echo '<option value="0">- ' . $l['NOTASSIGNED'] . ' -</option>';

            if ($user = User::getInstance(intval($_POST['user_services']), "ID")) {
                $sql2 = $db->query("SELECT ID, product, name, description FROM client_products WHERE user = " . $user->get()['ID']);
                while ($cInfo = $sql2->fetch_object()) {
                    $service = "#{$cInfo->ID}";

                    if ($cInfo->name) {
                        $service .= " " . $cInfo->name;
                    } else {
                        $sql3 = $db->query("SELECT name FROM products WHERE ID = {$cInfo->product}");
                        if ($sql3->num_rows) {
                            $name = $sql3->fetch_object()->name;
                            if (@unserialize($name)) {
                                $name = unserialize($name);
                                $name = $name[$CFG['LANG']] ?? (array_values($name)[0] ?? "");
                                if ($name) {
                                    $service .= " " . $name;
                                }
                            }
                        }
                    }

                    if ($cInfo->description) {
                        $service .= " (" . trim($cInfo->description) . ")";
                    }

                    $service = htmlentities($service);
                    echo "<option value=\"" . $cInfo->ID . "\"" . ($cInfo->ID == $info->contract ? ' selected=""' : '') . ">" . $service . "</option>";
                }
            }

            exit;
        }
    } elseif (!empty($_GET['msg']) && $tid = tid($_GET['msg'])) {
        $tpl = "abuse_add";
        $var['warning'] = "";

        $aInfo = $db->query("SELECT subject, message FROM support_ticket_answers WHERE ID = " . intval($_GET['msg']))->fetch_object();
        $var['subject'] = htmlentities($aInfo->subject);
        $var['report'] = strip_tags(str_replace(["<br />", "<br/>", "<br>"], "\n", nl2br($aInfo->message)));

        if (isset($_POST['subject'])) {
            $subject = $db->real_escape_string($_POST['subject'] ?? "");
            $report = $db->real_escape_string($_POST['answer'] ?? "");
            $reactiontime = intval($_POST['reaction_time'] ?: 48);

            $time = date("Y-m-d H:i:s");
            $deadline = date("Y-m-d H:i:s", strtotime("+$reactiontime hours"));

            $db->query("INSERT INTO abuse (`time`, deadline, subject, ticket) VALUES ('$time', '$deadline', '$subject', $tid)");
            $iid = $db->insert_id;

            if (!empty($report)) {
                $db->query("INSERT INTO abuse_messages (report, author, `time`, `text`) VALUES ($iid, 'reporter', '$time', '" . $report . "')");
            }

            header('Location: ?p=abuse&id=' . $iid);
            exit;
        }

        $sql = $db->query("SELECT subject, ID FROM abuse WHERE ticket = " . intval($tid));
        if ($sql->num_rows) {
            $var['warning'] = '<div class="alert alert-warning">' . $l['WARNING'] . '<ul>';
            while ($row = $sql->fetch_object()) {
                $var['warning'] .= '<li><a href="?p=abuse&id=' . $row->ID . '">' . htmlentities($row->subject) . '</a></li>';
            }
            $var['warning'] .= '</ul></div>';
        }
    } else {
        $var['reports'] = [];

        $t = new Table("SELECT * FROM abuse", [
            "subject" => [
                "name" => $l['SUBJECT'],
                "type" => "like",
            ],
            "status" => [
                "name" => $l['STATUS'],
                "type" => "select",
                "options" => [
                    "open" => $l['OPEN'],
                    "resolved" => $l['RESOLVED'],
                ],
            ],
        ]);

        $var['th'] = $t->getHeader();
        $var['tf'] = $t->getFooter();

        $sql = $t->qry("status = 'open' DESC, `deadline` DESC, `time` DESC, `ID` DESC");
        while ($row = $sql->fetch_assoc()) {
            $row["userlink"] = "-";
            if (is_object($user = User::getInstance($row["user"], "ID"))) {
                $row["userlink"] = '<a href="?p=customers&edit=' . $row["user"] . '">' . $user->getfName() . '</a>';
            }

            $var['reports'][] = $row;
        }
    }
}
