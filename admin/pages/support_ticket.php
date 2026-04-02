<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SUPPORT_TICKET'];

title($l['TITLE']);
menu("support");

if (isset($_POST['preview'])) {
    $parsedown = new Parsedown;
    $parsedown->setSafeMode(true);
    $html = $parsedown->line($_POST['preview']);
    die($html);
}

$my_depts = array($adminInfo->ID / -1);
$sql = $db->query("SELECT dept FROM support_department_staff WHERE staff = " . intval($adminInfo->ID));
while ($row = $sql->fetch_object()) {
    $ds = $db->query("SELECT ID FROM support_departments WHERE ID = " . $row->dept);
    while ($sd = $ds->fetch_object()) {
        array_push($my_depts, $sd->ID);
    }

}

$hasNext = boolval($db->query("SELECT 1 FROM support_tickets WHERE dept IN (" . implode(",", $my_depts) . ") AND status = 0 AND ID != " . strval(intval($_GET['id'] ?? 0)))->num_rows);

$sql = $db->query("SELECT * FROM support_tickets WHERE ID = " . intval($_GET['id'] ?: 0));
if ($sql->num_rows == 1) {
    $info = $sql->fetch_object();
}

function customerNotesHtml($cid)
{
    global $db, $CFG;

    ob_start();

    $nsql = $db->query("SELECT * FROM client_notes WHERE user = " . $cid . " AND sticky = 1 ORDER BY ID DESC");

    while ($row = $nsql->fetch_object()) {
        ?>
		<div class="alert alert-info">
			<a href="#" class="note_link" style="text-decoration: none;">
				<b><?=$row->title;?></b>
				<i class="fa fa-play pull-right"></i>
			</a>
			<p class="note_body" style="display: none;"><?=nl2br(htmlentities($row->text));?></p>
		</div>
		<?php
}

    ?>
	<script>
	$(document).ready(function() {
		$(".note_link").click(function(e) {
			e.preventDefault();

			if ($(this).find("i").hasClass("fa-rotate-90")) {
				$(this).find("i").removeClass("fa-rotate-90");
				$(this).parent().find(".note_body").slideUp();
			} else {
				$(this).find("i").addClass("fa-rotate-90");
				$(this).parent().find(".note_body").slideDown();
			}
		});
	});
	</script>
	<?php

    $res = ob_get_contents();
    ob_end_clean();

    return $res;
}

function customerStatsHtml($cid)
{
    global $db, $CFG, $dfo, $nfo, $cur, $l;

    $user = User::getInstance($cid, "ID");
    if (!$user) {
        return "";
    }

    $sales = 0;
    $openpos = 0;
    foreach ($user->getInvoices() as $inv) {
        $sales += $inv->getAmount();

        if (!$inv->getStatus()) {
            $openpos += $inv->getAmount();
        }
    }

    $openpos -= $user->get()['credit'];

    $furtherSQL = $db->query("SELECT * FROM client_products WHERE user = " . $cid);
    $free = 0;
    $locked = 0;

    while ($v = $furtherSQL->fetch_array()) {
        if ($v['active'] == 1) {
            $free++;
        } else {
            $locked++;
        }
    }

    ob_start();
    ?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php if ($user->get()['locked']) {?><i class="fa fa-lock" style="color: red;"></i> <?php }?><?=$user->getfName();?> <small><?=$l['CUSTOMERSINCE'];?> <?=$dfo->format($user->get()['registered'], "", false, false);?></small></h3>
		</div>
		<div class="panel-body">
			<b><?=$l['SALES'];?>:</b> <?=$cur->infix($nfo->format($sales), $cur->getBaseCurrency());?><br />
			<b><?=$l['PRODUCTCOUNT'];?>:</b> <a href="?p=customers&edit=<?=$cid;?>&tab=products"><?=$free;?> (<?=$free + $locked;?>)</a><br />
			<?php if ($openpos > 0) {?>
			<b><?=$l['OPENPOS'];?>:</b> <font color="red"><?=$cur->infix($nfo->format($openpos), $cur->getBaseCurrency());?></font><br />
			<?php }?>
			<b><?=$l['SCORING'];?>:</b> <a href="?p=customers&edit=<?=$cid;?>&tab=scoring"><?=$nfo->format($user->getScore(), 0);?> %</a><br />
			<b><?=$l['CALLS'];?>:</b> <a href="?p=customers&edit=<?=$cid;?>&tab=telephone"><?=$db->query("SELECT ID FROM client_calls WHERE user = " . $cid)->num_rows;?></a>
		</div>
		<div class="panel-footer">
			<a href="?p=customers&edit=<?=$cid;?>"><?=$l['CUSTPROF'];?></a>
		</div>
	</div>
	<?php
$res = ob_get_contents();
    ob_end_clean();

    return $res;
}

if (empty($_GET['id']) || !isset($info) || (!in_array($info->dept, $my_depts) && !$ari->check(61))) {require __DIR__ . "/error.php";if (isset($info) && !in_array($info->dept, $my_depts) && !$ari->check(61)) {
    alog("general", "insufficient_page_rights", "support_ticket");
}
} else {

    $addons->runHook("TicketView", $info);

    title($info->subject);

    $t = new Ticket($_GET['id']);
    $t->read();

    if ($info->recall > 0 && $info->recall <= time()) {
        $db->query("UPDATE support_tickets SET recall = 0 WHERE ID = " . $info->ID);
        $info->recall = 0;
    }

    $sign = $adminInfo->last_sign;
    if (empty($sign)) {
        $sql = $db->query("SELECT signature FROM support_signature_staff WHERE staff = " . $adminInfo->ID . " LIMIT 1");
        if ($sql->num_rows) {
            $sign = $sql->fetch_object()->signature;
        }

    }

    require_once __DIR__ . "/../../lib/HTMLPurifier/HTMLPurifier.auto.php";
    $config = HTMLPurifier_Config::createDefault();

    if (isset($_POST['action']) && $_POST['action'] == "wantToAnswer") {
        $sql = $db->query("SELECT draft_owner FROM support_tickets WHERE ID = " . intval($_GET['id']) . " AND draft_owner > 0 AND draft_owner != " . $adminInfo->ID);
        if ($sql->num_rows == 1) {
            $admin = $sql->fetch_object()->draft_owner;
            $sql = $db->query("SELECT name FROM admins WHERE ID = $admin");
            if ($sql->num_rows == 1) {
                $admin = htmlentities($sql->fetch_object()->name);
            }
            die(str_replace("%a", $admin, $l['IAA']));
        }

        die("ok");
    }

    if (isset($_POST['action']) && $_POST['action'] == "saveDraft") {
        $db->query("UPDATE support_tickets SET draft_owner = {$adminInfo->ID}, draft = '" . $db->real_escape_string($_POST['draft']) . "' WHERE ID = " . intval($_GET['id']) . " LIMIT 1");
        exit;
    }

    if (isset($_GET['img_msg']) && is_object($sql = $db->query("SELECT ID, message, ticket FROM support_ticket_answers WHERE ID = " . intval($_GET['img_msg']) . " AND ticket = " . $info->ID)) && $sql->num_rows == 1) {
        $row = $sql->fetch_object();

        // Inline image handling start
        $minPos = 0;
        while (($pos = strpos($row->message, "<img", $minPos)) !== false) {
            $minPos = $pos + 1;
            $tag = substr($row->message, $pos);
            $tag = substr($tag, 0, intval(strpos($tag, ">")) + 1);

            foreach (["'", '"'] as $sep) {
                $cidPos = strpos($tag, "src={$sep}cid:");
                if ($cidPos !== false) {
                    $cid = substr($tag, $cidPos + 9);
                    $endPos = strpos($cid, $sep);
                    $addLen = strpos($tag, ">") - $endPos - $cidPos;

                    if ($endPos !== false) {
                        $cid = substr($cid, 0, $endPos);

                        for ($i = strlen($cid) - 1; $i >= 0; $i--) {
                            $char = substr($cid, -1);
                            $cid = substr($cid, 0, -1);
                            if ($char == "@") {
                                break;
                            }
                        }

                        if (!empty($cid)) {
                            $cid = $CFG['PAGEURL'] . "admin/?p=support_ticket&id=" . $row->ticket . "&msg=" . $row->ID . "&cid=" . urlencode($cid);

                            $myMsg = substr($row->message, 0, $pos);
                            $myMsg .= '<img src="' . $cid . '">';
                            $myMsg .= substr($row->message, $pos + $cidPos + $endPos + $addLen + 1);

                            $row->message = $myMsg;
                        }
                    }
                }
            }
        }
        // Inline image handling end

        $purifier = new HTMLPurifier($config);
        die(nl2br(trim($purifier->purify($row->message))));
    }

    $sender = $info->sender;
    $ex = explode("<", $sender);
    if (count($ex) == 2) {
        $sender = rtrim($ex[1], ">");
    } else {
        $sender = $ex[0];
    }

    if (isset($_GET['lock_subject'])) {
        $db->query("INSERT INTO support_filter (field, type, value, action) VALUES ('subject', 'is', '" . $db->real_escape_string($info->subject) . "', 'delete')");
        alog("support", "lock_subject", $info->subject, $info->ID);
        header('Location: ?p=support_ticket&id=' . $info->ID);
        exit;
    } else if (isset($_GET['unlock_subject'])) {
        $db->query("DELETE FROM support_filter WHERE `field` = 'subject' AND `action` = 'delete' AND `type` = 'is' AND `value` = '" . $db->real_escape_string($info->subject) . "'");
        alog("support", "unlock_subject", $info->subject, $info->ID);
        header('Location: ?p=support_ticket&id=' . $info->ID);
        exit;
    }

    if (isset($_GET['lock_sender'])) {
        $db->query("INSERT INTO support_filter (field, type, value, action) VALUES ('email', 'is', '" . $db->real_escape_string($sender) . "', 'delete')");
        alog("support", "lock_sender", $info->sender, $info->ID);
        header('Location: ?p=support_ticket&id=' . $info->ID);
        exit;
    } else if (isset($_GET['unlock_sender'])) {
        $db->query("DELETE FROM support_filter WHERE `field` = 'email' AND `action` = 'delete' AND `type` = 'is' AND `value` = '" . $db->real_escape_string($sender) . "'");
        alog("support", "unlock_sender", $info->sender, $info->ID);
        header('Location: ?p=support_ticket&id=' . $info->ID);
        exit;
    }

    if (isset($_GET['redirect_msg']) && $db->query("SELECT 1 FROM support_ticket_answers WHERE ID = " . intval($_GET['redirect_msg']) . " AND ticket = " . $info->ID)->num_rows == 1) {
        $i = $db->query("SELECT * FROM support_ticket_answers WHERE ID = " . intval($_GET['redirect_msg']) . " AND ticket = " . $info->ID)->fetch_object();
        $mid = $i->ID;

        $recipient = $_GET['recipient'] ?? "";
        if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            die("Invalid email.");
        }

        $sql = $db->query("SELECT * FROM support_email WHERE send = 1 AND dept = " . $info->dept);
        $mInfo = null;
        while ($row = $sql->fetch_object()) {
            $mInfo = $row;
            if ($row->sender_name == $sender_name && $row->email == $sender_mail) {
                break;
            }
        }

        $lid = $info->ID;
        while (strlen($lid) < 6) {
            $lid = "0" . $lid;
        }

        $subject = "Fwd: [T#$lid] " . $info->subject;

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $mail->SetFrom($CFG['MAIL_SENDER'], $CFG['PAGENAME']);
        $mail->addAddress($recipient);
        $mail->Subject = $subject;

        if ($mInfo->smtp) {
            $mail->isSMTP();
            $mail->Host = $mInfo->smtp_host;
            $mail->Port = $mInfo->smtp_port;
            $mail->SMTPAuth = true;
            $mail->Username = $mInfo->smtp_user;
            $mail->Password = decrypt($mInfo->smtp_password);
            if ($mInfo->smtp_ssl) {
                $mail->SMTPSecure = "ssl";
            }
        }

        $mail->msgHTML($i->message . $ltt);
        $mail->AltBody = strip_tags(str_replace(array("<br>", "<br />", "<br/>", "<p>", "</p>"), "\n", $i->message));

        $mail->Priority = $i->priority;

        $sql = $db->query("SELECT * FROM support_ticket_attachments WHERE message = " . $i->ID);
        while ($row = $sql->fetch_object()) {
            if (substr($row->file, 0, 5) == "file#") {
                $path = __DIR__ . "/../../files/tickets/" . basename(substr($row->file, 5));
                $mail->addAttachment($path, $row->name);
            } else {
                $mail->addStringAttachment($row->file, $row->name);
            }
        }

        try {
            $mail->Send();
        } catch (Exception $ex) {
            die($ex->getMessage());
        }

        alog("support", "message_redirected", $i->ID, $info->ID, $recipient);

        die("ok");
    }

    if (isset($_GET['resend_msg']) && $db->query("SELECT 1 FROM support_ticket_answers WHERE ID = " . intval($_GET['resend_msg']) . " AND ticket = " . $info->ID)->num_rows == 1) {
        $i = $db->query("SELECT * FROM support_ticket_answers WHERE ID = " . intval($_GET['resend_msg']) . " AND ticket = " . $info->ID)->fetch_object();
        $mid = $i->ID;

        $ex = explode("<", $i->sender);
        $sender_name = trim($ex[0]);
        $sender_mail = trim(trim($ex[1]), ">");

        $sql = $db->query("SELECT * FROM support_email WHERE send = 1 AND dept = " . $info->dept);
        $mInfo = null;
        while ($row = $sql->fetch_object()) {
            $mInfo = $row;
            if ($row->sender_name == $sender_name && $row->email == $sender_mail) {
                break;
            }

        }

        $lid = $info->ID;
        while (strlen($lid) < 6) {
            $lid = "0" . $lid;
        }

        $subject = "Re: [T#$lid] " . $info->subject;

        $_POST['priority'] = $i->priority;

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $ex = explode("<", $info->sender);
        if (count($ex) == 2) {
            $name = trim($ex[0]);
            $email = trim(rtrim($ex[1], ">"));
        } else {
            $email = trim($ex[0]);
            $name = "";
        }

        $mail->SetFrom($sender_mail, $sender_name);
        $mail->addAddress($email, $name);
        $mail->Subject = $subject;

        if ($mInfo->smtp) {
            $mail->isSMTP();
            $mail->Host = $mInfo->smtp_host;
            $mail->Port = $mInfo->smtp_port;
            $mail->SMTPAuth = true;
            $mail->Username = $mInfo->smtp_user;
            $mail->Password = decrypt($mInfo->smtp_password);
            if ($mInfo->smtp_ssl) {
                $mail->SMTPSecure = "ssl";
            }

        }

        $ltt = "";
        if ($info->customer_access) {
            $ltt = "<br /><br />{$l['TICKET_LINK']}<br />" . $CFG['PAGEURL'] . "ticket/" . $info->ID . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $info->ID . "ticketview" . $CFG['HASH']), -16) . "<img src='{$CFG['PAGEURL']}tt/$mid/" . substr(hash("sha512", $mid . $CFG['HASH'] . $mid), -8) . "'>";
        }

        $mail->msgHTML(($i->message . $ltt));
        $mail->AltBody = (strip_tags(str_replace(array("<br>", "<br />", "<br/>", "<p>", "</p>"), "\n", $i->message . "<br /><br />{$l['TICKET_LINK']}<br />" . $CFG['PAGEURL'] . "ticket/" . $info->ID . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $info->ID . "ticketview" . $CFG['HASH']), -16))));

        $mail->Priority = $_POST['priority'];

        $sql = $db->query("SELECT * FROM support_ticket_attachments WHERE message = " . $i->ID);
        while ($row = $sql->fetch_object()) {
            if (substr($row->file, 0, 5) == "file#") {
                $path = __DIR__ . "/../../files/tickets/" . basename(substr($row->file, 5));
                $mail->addAttachment($path, $row->name);
            } else {
                $mail->addStringAttachment($row->file, $row->name);
            }
        }

        try {
            $mail->Send();
        } catch (Exception $ex) {
            die($ex->getMessage());
        }

        alog("support", "message_resent", $i->ID, $info->ID);

        die("ok");
    }

    if (isset($_GET['delete_msg']) && $db->query("DELETE FROM support_ticket_answers WHERE ID = " . intval($_GET['delete_msg']) . " AND ticket = " . $info->ID) && $db->affected_rows) {
        $id = intval($_GET['delete_msg']);
        $sql = $db->query("SELECT `file` FROM support_ticket_attachments WHERE message = $id");
        while ($row = $sql->fetch_object()) {
            if (substr($row->file, 0, 5) == "file#") {
                unlink(__DIR__ . "/../../files/tickets/" . basename(substr($row->file, 5)));
            }
        }

        $db->query("DELETE FROM support_ticket_attachments WHERE message = $id");
        alog("support", "message_delete", $id, $info->ID);
        die("ok");
    }

    if (isset($_GET['delete_ticket']) && $_GET['delete_ticket'] == $info->ID && $db->query("DELETE FROM support_tickets WHERE ID = " . intval($_GET['delete_ticket'])) && $db->affected_rows) {
        $id = intval($_GET['delete_ticket']);
        $sql = $db->query("SELECT ID FROM support_ticket_answers WHERE ticket = $id");
        while ($row = $sql->fetch_object()) {
            $sql2 = $db->query("SELECT `file` FROM support_ticket_attachments WHERE message = {$row->ID}");
            while ($row2 = $sql2->fetch_object()) {
                if (substr($row2->file, 0, 5) == "file#") {
                    unlink(__DIR__ . "/../../files/tickets/" . basename(substr($row2->file, 5)));
                }
            }

            $db->query("DELETE FROM support_ticket_attachments WHERE message = {$row->ID}");
        }

        $db->query("DELETE FROM support_ticket_answers WHERE ticket = $id");
        alog("support", "delete_ticket", $info->ID);
        die("ok");
    }

    $config->set('HTML.ForbiddenElements', array('img'));
    $purifier = new HTMLPurifier($config);

    if (isset($_POST['rating']) && isset($_POST['save'])) {
        $db->query("UPDATE support_tickets SET rating = " . ($_POST['rating'] == "0" ? "-1" : "0") . " WHERE ID = " . $info->ID . " AND rating < 1");
        alog("support", "ticket_update", $info->ID);
        exit;
    }

    if (isset($_POST['customer_access']) && isset($_POST['save'])) {
        $db->query("UPDATE support_tickets SET customer_access = " . ($_POST['customer_access'] ? "1" : "0") . " WHERE ID = " . $info->ID);
        alog("support", "ticket_update", $info->ID);
        exit;
    }

    if (isset($_POST['can_closed']) && isset($_POST['save'])) {
        $db->query("UPDATE support_tickets SET can_closed = " . ($_POST['can_closed'] ? "1" : "0") . " WHERE ID = " . $info->ID);
        alog("support", "ticket_update", $info->ID);
        exit;
    }

    if (isset($_POST['subject']) && isset($_POST['save'])) {
        $db->query("UPDATE support_tickets SET subject = '" . $db->real_escape_string($_POST['subject']) . "' WHERE ID = " . $info->ID);
        alog("support", "ticket_update", $info->ID);

        $info = $db->query("SELECT * FROM support_tickets WHERE ID = " . $info->ID)->fetch_object();
        $sql = $db->query("SELECT 1 FROM support_filter WHERE `field` = 'subject' AND `action` = 'delete' AND `type` = 'is' AND `value` = '" . $db->real_escape_string($info->subject) . "'");
        echo $sql->num_rows ? "unlock" : "lock";
        exit;
    }

    if (isset($_POST['dept']) && isset($_POST['save'])) {
        $depts = array();
        $sql = $db->query("SELECT ID FROM support_departments ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            array_push($depts, $row->ID);
        }

        $sql = $db->query("SELECT ID FROM admins ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            array_push($depts, $row->ID / -1);
        }

        if (!empty($_POST['dept']) && in_array($_POST['dept'], $depts)) {
            $db->query("UPDATE support_tickets SET dept = " . intval($_POST['dept']) . " WHERE ID = " . $info->ID);
            alog("support", "ticket_update", $info->ID);
        }
        exit;
    }

    if (isset($_POST['status']) && isset($_POST['save'])) {
        $oldStatus = $info->status;
        if (in_array($_POST['status'], array("0", "1", "2", "3"))) {
            $db->query("UPDATE support_tickets SET status = " . intval($_POST['status']) . " WHERE ID = " . $info->ID);

            $info = $db->query("SELECT * FROM support_tickets WHERE ID = " . $info->ID)->fetch_object();

            if ($info->status != $oldStatus) {
                alog("support", "ticket_update", $info->ID);

                if ($info->status == 3) {
                    $addons->runHook("TicketClose", [
                        "id" => $info->ID,
                        "ticket" => ($t = new Ticket($info->ID)),
                        "url" => $t->getURL(),
                        "source" => "adminarea",
                    ]);
                }
            }
        }
        exit;
    }

    if (isset($_POST['fake_status']) && isset($_POST['save'])) {
        if (in_array($_POST['fake_status'], array("-1", "0", "1", "2", "3"))) {
            $db->query("UPDATE support_tickets SET fake_status = " . intval($_POST['fake_status']) . " WHERE ID = " . $info->ID);
            alog("support", "ticket_update", $info->ID);
        }
        exit;
    }

    if (isset($_POST['priority']) && isset($_POST['save'])) {
        if (in_array($_POST['priority'], array("1", "2", "3", "4", "5"))) {
            $db->query("UPDATE support_tickets SET priority = " . intval($_POST['priority']) . " WHERE ID = " . $info->ID);
            alog("support", "ticket_update", $info->ID);
        }
        exit;
    }

    if (isset($_POST['sender']) && isset($_POST['save'])) {
        $db->query("UPDATE support_tickets SET sender = '" . $db->real_escape_string($_POST['sender']) . "' WHERE ID = " . $info->ID);
        alog("support", "ticket_update", $info->ID);

        $info = $db->query("SELECT * FROM support_tickets WHERE ID = " . $info->ID)->fetch_object();
        $sender = $info->sender;
        $ex = explode("<", $sender);
        if (count($ex) == 2) {
            $sender = rtrim($ex[1], ">");
        } else {
            $sender = $ex[0];
        }
        $sql = $db->query("SELECT 1 FROM support_filter WHERE `field` = 'email' AND `action` = 'delete' AND `type` = 'is' AND `value` = '" . $db->real_escape_string($sender) . "'");
        echo $sql->num_rows ? "unlock" : "lock";
        exit;
    }

    if (isset($_POST['cc']) && isset($_POST['save'])) {
        $db->query("UPDATE support_tickets SET cc = '" . $db->real_escape_string($_POST['cc']) . "' WHERE ID = " . $info->ID);
        alog("support", "ticket_update", $info->ID);
        exit;
    }

    if (isset($_POST['recall']) && isset($_POST['save'])) {
        $recall = (!empty($_POST['recall']) && strtotime($_POST['recall']) !== false ? strtotime($_POST['recall']) : 0);
        if ($recall <= time()) {
            $recall = 0;
        }

        $db->query("UPDATE support_tickets SET recall = " . $recall . " WHERE ID = " . $info->ID);
        echo $recall > 0 ? $dfo->format($recall, true, true, '') : "";
        exit;
    }

    if (isset($_POST['customer']) && isset($_POST['save'])) {
        if (intval($_POST['customer']) == 0 || $db->query("SELECT 1 FROM clients WHERE ID = " . intval($_POST['customer']))->num_rows == 1) {
            $db->query("UPDATE support_tickets SET customer = " . intval($_POST['customer']) . " WHERE ID = " . $info->ID);
            alog("support", "ticket_update", $info->ID);

            if (intval($_POST['customer'])) {
                $data = [
                    "link" => "?p=customers&edit=" . ($cid = intval($_POST['customer'])),
                    "stats" => customerStatsHtml($cid),
                    "notes" => customerNotesHtml($cid),
                ];

                echo json_encode($data);
            } else {
                echo json_encode([]);
            }
        }
        exit;
    }

    if ($info->draft == "undefined-0") {
        $info->draft = "";
        $info->draft_owner = 0;
    }

    if (isset($_POST['delete_draft']) && $info->draft_owner == $adminInfo->ID) {
        $db->query("UPDATE support_tickets SET draft_owner = 0 WHERE ID = " . $info->ID);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] == "answer") {
        $_POST['answer'] = str_replace("%clientid%", $info->customer, $_POST['answer']);
        $_POST['answer'] = str_replace("%client_id%", $info->customer, $_POST['answer']);

        $parsedown = new Parsedown;
        $parsedown->setSafeMode(true);
        $_POST['answer'] = nl2br($parsedown->line($_POST['answer']));
        if (!empty($_POST['signature_id'])) {
            if ($db->query("SELECT 1 FROM support_signatures WHERE ID = " . intval($_POST['signature_id']))->num_rows == 1) {
                if ($db->query("SELECT 1 FROM support_signature_staff WHERE signature = " . intval($_POST['signature_id']) . " AND staff = " . $adminInfo->ID)->num_rows == 1) {
                    $_POST['answer'] .= "<br /><br />" . $db->query("SELECT text FROM support_signatures WHERE ID = " . intval($_POST['signature_id']))->fetch_object()->text;
                }
            }
        }
        $_POST['answer'] = str_replace(["\n", "\r"], "", $_POST['answer']);

        $newprio = $info->priority;
        if (!empty($_POST['new_priority']) && $_POST['new_priority'] != "-" && in_array($_POST['new_priority'], ["5", "4", "3", "2", "1"])) {
            $newprio = intval($_POST['new_priority']);
        }

        $newstatus = 2;
        if (isset($_POST['new_status']) && $_POST['new_status'] != "-" && in_array($_POST['new_status'], ["0", "1", "2", "3"])) {
            $newstatus = intval($_POST['new_status']);
        }

        $info->draft = "";
        $info->draft_owner = 0;
        if (isset($_POST['answer_btn'])) {
            $email = $_POST['email'];
            $sql = $db->query("SELECT * FROM support_email WHERE ID = " . intval($email) . " AND send = 1 AND dept = " . $info->dept);
            if ($sql->num_rows == 1) {
                $mInfo = $sql->fetch_object();
                $sender_name = $mInfo->sender_name;
                $sender_mail = $mInfo->email;

                $lid = $info->ID;
                while (strlen($lid) < 6) {
                    $lid = "0" . $lid;
                }

                $subject = "Re: [T#$lid] " . $info->subject;

                if (empty($_POST['priority']) || !is_numeric($_POST['priority'])) {
                    $_POST['priority'] = 3;
                }

                if ($_POST['priority'] > 5) {
                    $_POST['priority'] = 5;
                }

                if ($_POST['priority'] < 1) {
                    $_POST['priority'] = 1;
                }

                $sql = $db->prepare("INSERT INTO support_ticket_answers (ticket, `time`, subject, message, priority, sender, staff) VALUES (?,?,?,?,?,?,?)");
                $sql->bind_param("isssisi", $info->ID, $a = date("Y-m-d H:i:s"), $subject, $_POST['answer'], $_POST['priority'], $b = "$sender_name <$sender_mail>", $adminInfo->ID);
                $sql->execute();
                $mid = $db->insert_id;

                alog("support", "answer", $mid, $info->ID);

                $addons->runHook("TicketAnswer", [
                    "aid" => $mid,
                    "tid" => $info->ID,
                    "ticket" => ($t = new Ticket($info->ID)),
                    "url" => $t->getURL(),
                    "source" => "adminarea",
                    "who" => "staff",
                ]);

                $db->query("UPDATE support_tickets SET fake_status = -1, status = 2, draft = '', draft_owner = 0, escalations = '', priority = $newprio, status = $newstatus, updated = '" . date("Y-m-d H:i:s") . "' WHERE ID = {$info->ID}");
                $info->status = 2;
                $info->draft = "";
                $info->draft_owner = 0;

                if (is_array($_FILES['attachments']) && count($_FILES['attachments']['name']) > 0) {
                    foreach ($_FILES['attachments']['name'] as $k => $name) {
                        if (empty($name)) {
                            continue;
                        }

                        $path = basename(time() . "-" . rand(10000000, 99999999) . "-" . $name);
                        file_put_contents(__DIR__ . "/../../files/tickets/$path", file_get_contents($_FILES['attachments']['tmp_name'][$k]));
                        $db->query("INSERT INTO support_ticket_attachments (message, name, file) VALUES ($mid, '" . $db->real_escape_string($name) . "', 'file#" . $db->real_escape_string($path) . "')");
                    }
                }

                $t = new Ticket($info->ID);
                $t->resetUpgrade();

                $rc = isset($_POST['read_confirmation']) && $_POST['read_confirmation'];
                $dr = isset($_POST['delivery_receipt']) && $_POST['delivery_receipt'];

                $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';

                $ex = explode("<", $_POST['to']);
                if (count($ex) == 2) {
                    $name = trim($ex[0]);
                    $email = trim(rtrim($ex[1], ">"));
                } else {
                    $email = trim($ex[0]);
                    $name = "";
                }

                $mails = [$email, $sender_mail];

                $mail->SetFrom($sender_mail, $sender_name);
                $mail->addAddress($email, $name);

                foreach (explode(",", $_POST['cc']) as $cc) {
                    $cc = trim($cc);
                    if (filter_var($cc, FILTER_VALIDATE_EMAIL) && !in_array($cc, $mails)) {
                        $mail->AddCC($cc);
                        array_push($mails, $cc);
                    }
                }

                foreach (explode(",", $_POST['bcc']) as $cc) {
                    $cc = trim($cc);
                    if (filter_var($cc, FILTER_VALIDATE_EMAIL) && !in_array($cc, $mails)) {
                        $mail->AddBCC($cc);
                        array_push($mails, $bcc);
                    }

                }

                $mail->Subject = $subject;

                if ($mInfo->smtp) {
                    $mail->isSMTP();
                    $mail->Host = $mInfo->smtp_host;
                    $mail->Port = $mInfo->smtp_port;
                    $mail->SMTPAuth = true;
                    $mail->Username = $mInfo->smtp_user;
                    $mail->Password = decrypt($mInfo->smtp_password);
                    if ($mInfo->smtp_ssl) {
                        $mail->SMTPSecure = "ssl";
                    }

                }

                $ltt = "";
                if ($info->customer_access) {
                    $ltt = "<br /><br />{$l['TICKET_LINK']}<br />" . $CFG['PAGEURL'] . "ticket/" . $info->ID . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $info->ID . "ticketview" . $CFG['HASH']), -16);
                }

                $previous = "";
                $previousSql = $db->query("SELECT `message`, `time` FROM support_ticket_answers WHERE ticket = {$info->ID} AND ID < $mid AND staff >= 0 ORDER BY ID DESC, `time` DESC LIMIT 1");
                if ($previousSql->num_rows) {
                    $preInfo = $previousSql->fetch_object();
                    $time = $dfo->format($preInfo->time, true, true);
                    $previous = $previousHtml = "<br /><br />$time<br />";
                    $previousHtml .= "<blockquote type=\"cite\">" . $preInfo->message . "</blockquote>";

                    $msg = str_replace(["<br/>", "<br>", "<br />"], "<br />", nl2br($preInfo->message));
                    $ex = explode("<br />", $msg);

                    foreach ($ex as $line) {
                        $previous .= "> " . $line . "<br />";
                    }
                }

                $mail->msgHTML(($_POST['answer'] . $ltt . "<img src='{$CFG['PAGEURL']}tt/$mid/" . substr(hash("sha512", $mid . $CFG['HASH'] . $mid), -8) . "'>") . $previousHtml);
                $mail->AltBody = (strip_tags(str_replace(array("<br>", "<br />", "<br/>", "<p>", "</p>"), "\n", $_POST['answer'] . $ltt . $previous)));

                if ($rc) {
                    $mail->ConfirmReadingTo = $sender_mail;
                }

                if ($dr) {
                    $mail->addCustomHeader("Disposition-Notification-To: $sender_mail");
                }

                $mail->Priority = $_POST['priority'];

                if (is_array($_FILES['attachments']) && count($_FILES['attachments']['name']) > 0) {
                    foreach ($_FILES['attachments']['name'] as $k => $name2) {
                        if (!empty($name2)) {
                            $mail->AddAttachment($_FILES['attachments']['tmp_name'][$k], $name2);
                        }
                    }
                }

                try {
                    $mail->Send();

                    if ($CFG['LOG_SUPPORT_MAILS']) {
                        $to = $db->real_escape_string($name . " <$email>");
                        $subject = $db->real_escape_string($subject);
                        $text = $db->real_escape_string($_POST['answer'] . $ltt);
                        $headers = "From: $sender_name <$sender_mail>";
                        $user = intval($info->customer);

                        $db->query("INSERT INTO client_mails (recipient, subject, text, headers, time, user, sent) VALUES ('$to', '$subject', '$text', '$headers', " . time() . ", $user, " . time() . ")");
                    }
                } catch (Exception $ex) {
                    die($ex->getMessage());
                }
            }
        } else {
            if (empty($_POST['priority']) || !is_numeric($_POST['priority'])) {
                $_POST['priority'] = 3;
            }

            if ($_POST['priority'] > 5) {
                $_POST['priority'] = 5;
            }

            if ($_POST['priority'] < 1) {
                $_POST['priority'] = 1;
            }

            $subject = $l['NOTE'];
            $sql = $db->prepare("INSERT INTO support_ticket_answers (ticket, `time`, subject, message, priority, sender, staff) VALUES (?,?,?,?,?,?,?)");
            $sql->bind_param("isssisi", $info->ID, $a = date("Y-m-d H:i:s"), $subject, $_POST['answer'], $_POST['priority'], $b = "$sender_name <$sender_mail>", $c = $adminInfo->ID / -1);
            $sql->execute();
            $mid = $db->insert_id;

            alog("support", "note", $mid, $info->ID);

            if (is_array($_FILES['attachments']) && count($_FILES['attachments']['name']) > 0) {
                foreach ($_FILES['attachments']['name'] as $k => $name) {
                    if (empty($name)) {
                        continue;
                    }

                    $path = basename(time() . "-" . rand(10000000, 99999999) . "-" . $name);
                    file_put_contents(__DIR__ . "/../../files/tickets/$path", file_get_contents($_FILES['attachments']['tmp_name'][$k]));
                    $db->query("INSERT INTO support_ticket_attachments (message, name, file) VALUES ($mid, '" . $db->real_escape_string($name) . "', 'file#" . $db->real_escape_string($path) . "')");
                }
            }

            $db->query("UPDATE support_tickets SET draft = '', draft_owner = 0, priority = $newprio, status = $newstatus WHERE ID = {$info->ID}");
        }

        switch ($_POST['new_action'] ?? "") {
            case 'list':
                header('Location: ?p=support_tickets&dept=' . $info->dept);
                exit;

            case 'next':
                header('Location: ?p=support_next' . (($_GET['last'] ?? '') ? '&last=' . urlencode($_GET['last']) : ''));
                exit;
        }
    }

    if (isset($_GET['support_cat'])) {
        if ($db->query("SELECT 1 FROM support_answer_categories WHERE ID = " . intval($_GET['support_cat']))->num_rows != 1) {
            die($l['ERR1']);
        }

        if ($db->query("SELECT 1 FROM support_answers WHERE cat = " . intval($_GET['support_cat']))->num_rows == 0) {
            die($l['ERR2']);
        }

        ?>
	<ul class="list-group" style="margin-bottom: 0;">
		<?php
$sql = $db->query("SELECT name, ID FROM support_answers WHERE cat = " . intval($_GET['support_cat']));
        while ($row = $sql->fetch_object()) {
            ?>
		<a href="#" class="list-group-item" onclick="add_html(<?=$row->ID;?>); return false;"><?=$row->name;?></a>
		<?php }?>
	</ul>
	<?php
exit;
    }

    if (isset($_GET['answer_id'])) {
        if ($db->query("SELECT 1 FROM support_answers WHERE ID = " . intval($_GET['answer_id']))->num_rows == 0) {
            exit;
        }

        die($db->query("SELECT message FROM support_answers WHERE ID = " . intval($_GET['answer_id']))->fetch_object()->message);
    }

    if (isset($_GET['signature_id'])) {
        if ($db->query("SELECT 1 FROM support_signatures WHERE ID = " . intval($_GET['signature_id']))->num_rows == 0) {
            exit;
        }

        if ($db->query("SELECT 1 FROM support_signature_staff WHERE signature = " . intval($_GET['signature_id']) . " AND staff = " . $adminInfo->ID)->num_rows == 0) {
            exit;
        }

        $db->query("UPDATE admins SET last_sign = " . intval($_GET['signature_id']) . " WHERE ID = " . $adminInfo->ID);
        die($db->query("SELECT text FROM support_signatures WHERE ID = " . intval($_GET['signature_id']))->fetch_object()->text);
    }

    if (isset($_GET['msg']) && isset($_GET['cid'])) {
        header('Content-type: image/png');

        if (!($sql = $db->query("SELECT file FROM support_ticket_attachments WHERE name LIKE '" . $db->real_escape_string($_GET['cid']) . "' AND message = " . intval($_GET['msg'])))->num_rows) {
            exit;
        }
        $info = $sql->fetch_object();

        if (substr($info->file, 0, 5) == "file#") {
            echo file_get_contents(__DIR__ . "/../../files/tickets/" . basename(substr($info->file, 5)));
        } else {
            echo $info->file;
        }

        exit;
    }

    if (isset($_GET['file'])) {
        if ($db->query("SELECT 1 FROM support_ticket_attachments WHERE ID = " . intval($_GET['file']))->num_rows != 1) {
            exit;
        }

        $message = $db->query("SELECT message FROM support_ticket_attachments WHERE ID = " . intval($_GET['file']))->fetch_object()->message;
        if ($db->query("SELECT 1 FROM support_ticket_answers WHERE ID = " . intval($message))->num_rows != 1) {
            exit;
        }

        $ticket = $db->query("SELECT ticket FROM support_ticket_answers WHERE ID = " . intval($message))->fetch_object()->ticket;
        if ($ticket != $info->ID) {
            exit;
        }

        $info = $db->query("SELECT * FROM support_ticket_attachments WHERE ID = " . intval($_GET['file']))->fetch_object();
        alog("support", "file_download", $_GET['file'], $info->name, $ticket);
        header("Content-Type: application/file");
        header("Content-Disposition: attachment; filename=\"" . $info->name . "\"");

        if (substr($info->file, 0, 5) == "file#") {
            echo file_get_contents(__DIR__ . "/../../files/tickets/" . basename(substr($info->file, 5)));
        } else {
            echo $info->file;
        }
        exit;
    }
    ?>

<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE'];?> <small><?=$t->html();?></small><span class="pull-right"><a href="?p=support_next&last=<?=(($_GET['last'] ?? '') ? '&last=' . urlencode($_GET['last']) . ',' : '');?><?=$info->ID;?>" class="btn btn-default"<?=$hasNext ? '' : ' disabled=""';?>><?=$lang['MENU']['NEXT_TICKET'];?> &raquo;</a></span></h1>
<script>
function load_signature(id){
	$.get("?p=support_ticket&id=<?=$info->ID;?>&signature_id=" + id, function(code){
		if(code){
			$("#answer_signature").html(code).show();
			$("[name=signature_id]").val(id);
		}
	});
}
</script>
		<div class="row">
			<div class="col-md-3">
				<div class="panel panel-primary">
				  <div class="panel-heading">
				    <h3 class="panel-title"><?=$l['TICKET'];?> T#<?=str_pad($info->ID, 6, "0", STR_PAD_LEFT);?><span class="pull-right"><a href="#" class="delete_ticket"><i style="color: white;" class="fa fa-times"></i></a></span></h3>
				  </div>
				  <div class="panel-body">
				    <form method="POST">
						<div class="form-group">
				    		<label><?=$l['TID'];?></label><?php if ($info->customer_access == 1) {?> <a href="<?=$CFG['PAGEURL'];?>ticket/<?=$info->ID;?>/<?=substr(hash("sha512", $CFG['HASH'] . "ticketview" . $info->ID . "ticketview" . $CFG['HASH']), -16);?>" target="_blank"><i class="fa fa-link"></i></a><?php }?><br />
				    		<span class="control-label">T#<?=str_pad($info->ID, 6, "0", STR_PAD_LEFT);?></span>
				    	</div>
						<?php if ($CFG['SUPPORT_RATING']) {?>
						<div class="form-group">
				    		<label><?=$l['RATING'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="rating_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="rating_ok"></i></label><br />
				    		<?php if ($info->rating == 0 || $info->rating == -1) {?>
							<div class="checkbox" style="margin-top: 0;">
								<label>
									<input type="checkbox" name="rating" value="0"<?php if ($info->rating == 0) {
        echo ' checked=""';
    }
        ?>>
									<?=$l['AAF'];?>
								</label>
							</div>

							<script>
							$("[name=rating]").change(function(e) {
								$("#rating_ok").hide();
								$(this).prop("disabled", true);
								$("#rating_spin").show();

								$.post("", {
									"rating": e.target.checked ? "1" : "0",
									"save": "1",
									"csrf_token": "<?=CSRF::raw();?>"
								}, function () {
									$("#rating_spin").hide();
									$("#rating_ok").show();
									$("[name=rating]").prop("disabled", false);
									setTimeout(function() {
										$("#rating_ok").hide();
									}, 2000);
								});
							});
							</script>
							<?php } else if ($info->rating == 1) {?>
							<font color="green"><i class="fa fa-smile-o"></i> <?=$l['GOOD'];?></font>
							<?php } else if ($info->rating == 3) {?>
							<?=$l['WFR'];?>
							<?php } else {?>
							<font color="red"><i class="fa fa-frown-o"></i> <?=$l['BAD'];?></font>
							<?php }?>
				    	</div>
						<?php }?>

						<div class="form-group">
				    		<label><?=$l['CUSTA'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="ca_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="ca_ok"></i></label><br />
				    		<div class="checkbox" style="margin-top: 0;">
								<label>
									<input type="checkbox" name="customer_access" value="1"<?php if ($info->customer_access == 1) {
        echo ' checked=""';
    }
    ?>>
									<?=$l['ALLOWED'];?>
								</label>
							</div>
						</div>
						<script>
						$("[name=customer_access]").change(function(e) {
							$("#ca_ok").hide();
							$(this).prop("disabled", true);
							$("#ca_spin").show();

							$.post("", {
								"customer_access": e.target.checked ? "1" : "0",
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function () {
								$("#ca_spin").hide();
								$("#ca_ok").show();
								$("[name=customer_access]").prop("disabled", false);
								setTimeout(function() {
									$("#ca_ok").hide();
								}, 2000);
							});
						});
						</script>

<div class="form-group">
				    		<label><?=$l['CANCLOSED'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="cacl_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="cacl_ok"></i></label><br />
				    		<div class="checkbox" style="margin-top: 0;">
								<label>
									<input type="checkbox" name="can_closed" value="1"<?php if ($info->can_closed == 1) {
        echo ' checked=""';
    }
    ?>>
									<?=$l['ALLOWED'];?>
								</label>
							</div>
						</div>
						<script>
						$("[name=can_closed]").change(function(e) {
							$("#cacl_ok").hide();
							$(this).prop("disabled", true);
							$("#cacl_spin").show();

							$.post("", {
								"can_closed": e.target.checked ? "1" : "0",
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function () {
								$("#cacl_spin").hide();
								$("#cacl_ok").show();
								$("[name=can_closed]").prop("disabled", false);
								setTimeout(function() {
									$("#cacl_ok").hide();
								}, 2000);
							});
						});
						</script>

						<?php
$sql = $db->query("SELECT 1 FROM support_filter WHERE `field` = 'subject' AND `action` = 'delete' AND `type` = 'is' AND `value` = '" . $db->real_escape_string($info->subject) . "'");
    ?>

				    	<div class="form-group">
				    		<label><?=$l['SUBJECT'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="subject_spin"></i><a id="subject_lock" href="?p=support_ticket&id=<?=$info->ID;?>&lock_subject=1" <?=$sql->num_rows > 0 ? "style=\"display: none;\"" : "";?>><i class="fa fa-lock"></i></a><a <?=$sql->num_rows == 0 ? "style=\"display: none;\"" : "";?> id="subject_unlock" href="?p=support_ticket&id=<?=$info->ID;?>&unlock_subject=1"><i class="fa fa-unlock"></i></a></label>
				    		<input type="text" name="subject" value="<?=htmlentities($info->subject);?>" class="form-control" placeholder="<?=$l['NOSUBJECT'];?>" />
				    	</div>
						<script>
						$("[name=subject]").change(function(e) {
							$("#subject_lock").hide();
							$("#subject_unlock").hide();
							$(this).prop("disabled", true);
							$("#subject_spin").show();

							$.post("", {
								"subject": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function (r) {
								$("#subject_spin").hide();
								$("[name=subject]").prop("disabled", false);
								$("#subject_" + r).show();
							});
						});
						</script>

						<div class="form-group" style="position: relative;">
							<label><?=$l['RECALL'];?></label>
							<div class="input-group" data-id="<?=$file->ID;?>">
								<span class="input-group-addon"><i class="fa fa-calendar exdate_icon"></i></span>
								<input type="text" class="form-control datetimepicker exdate" placeholder="<?=$l['NORECALL'];?>" value="<?=$info->recall > 0 ? $dfo->format($info->recall, true, true, '') : "";?>" tabindex="-1">
								<span class="input-group-addon"><a href="#" class="remove_exdate"><i class="fa fa-times"></i></a></span>
							</div>

							<p class="help-block">
								<a href="#" tabindex="-1" id="tomorrow_recall"><?=$l['TOMORROW'];?></a>, <a href="#" tabindex="-1" id="nextweek_recall"><?=$l['NEXTWEEK'];?></a>
							</p>
				    	</div>

						<script>
						function save_exdate(td, val) {
							if (!td.find(".exdate_icon").hasClass("fa-calendar")) {
								return false;
							}

							td.find(".exdate").val(val).prop("disabled", true);
							td.find(".exdate_icon").removeClass("fa-calendar").addClass("fa-spinner fa-pulse");

							$.post("", {
								"save": "1",
								"recall": val,
								"csrf_token": "<?=CSRF::raw();?>",
							}, function (r) {
								td.find(".exdate").prop("disabled", false).val(r);
								td.find(".exdate_icon").addClass("fa-calendar").removeClass("fa-spinner fa-pulse");
							});
						}

						$(".remove_exdate").click(function(e) {
							e.preventDefault();
							save_exdate($(this).parent().parent(), "");
						});

						$(".exdate").on('dp.hide', function() {
							save_exdate($(this).parent(), $(this).val());
						});

						$("#tomorrow_recall").click(function(e) {
							e.preventDefault();
							var date = new Date();
							date.setDate(date.getDate() + 1);
							save_exdate($(this).parent().parent().find(".input-group"), date.toLocaleString());
						});

						$("#nextweek_recall").click(function(e) {
							e.preventDefault();
							var date = new Date();
							date.setDate(date.getDate() + 7);
							save_exdate($(this).parent().parent().find(".input-group"), date.toLocaleString());
						});
						</script>

				    	<div class="form-group">
				    		<label><?=$l['DEPTSTAFF'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="dept_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="dept_ok"></i></label>
				    		<select name="dept" class="form-control">
								<option disabled="disabled" selected="selected"><?=$l['CHDEPT'];?></option>
								<?php
$sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        echo '<option value="' . $row->ID . '"' . ($info->dept == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
    }

    ?>
								<option disabled="disabled"><?=$l['CHSTAFF'];?></option>
								<?php
$sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        echo '<option value="' . ($row->ID / -1) . '"' . ($info->dept / -1 == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
    }

    ?>
							</select>
				    	</div>
						<script>
						$("[name=dept]").change(function(e) {
							$("#dept_ok").hide();
							$(this).prop("disabled", true);
							$("#dept_spin").show();

							$.post("", {
								"dept": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function () {
								$("#dept_spin").hide();
								$("#dept_ok").show();
								$("[name=dept]").prop("disabled", false);
								setTimeout(function() {
									$("#dept_ok").hide();
								}, 2000);
							});
						});
						</script>

				    	<div class="form-group">
				    		<label><?=$l['STATUS'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="status_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="status_ok"></i></label>
				    		<select name="status" class="form-control">
				    			<option value="0"><?=Ticket::getStatusNames()["0"];?></option>
				    			<option value="1"<?php if ($info->status == "1") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getStatusNames()["1"];?></option>
				    			<option value="2"<?php if ($info->status == "2") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getStatusNames()["2"];?></option>
				    			<option value="3"<?php if ($info->status == "3") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getStatusNames()["3"];?></option>
				    		</select>
				    	</div>
						<script>
						$("[name=status]").change(function(e) {
							$("#status_ok").hide();
							$(this).prop("disabled", true);
							$("#status_spin").show();

							$.post("", {
								"status": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function () {
								$("#status_spin").hide();
								$("#status_ok").show();
								$("[name=status]").prop("disabled", false);
								setTimeout(function() {
									$("#status_ok").hide();
								}, 2000);
							});
						});
						</script>

<div class="form-group">
				    		<label><?=$l['FAKE_STATUS'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="fake_status_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="fake_status_ok"></i></label>
				    		<select name="fake_status" class="form-control">
								<option value="-1"><?=$l['NO_FAKE_STATUS'];?></option>
				    			<option value="0"<?php if ($info->fake_status == "0") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getStatusNames()["0"];?></option>
				    			<option value="1"<?php if ($info->fake_status == "1") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getStatusNames()["1"];?></option>
				    			<option value="2"<?php if ($info->fake_status == "2") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getStatusNames()["2"];?></option>
				    			<option value="3"<?php if ($info->fake_status == "3") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getStatusNames()["3"];?></option>
				    		</select>
				    	</div>
						<script>
						$("[name=fake_status]").change(function(e) {
							$("#fake_status_ok").hide();
							$(this).prop("disabled", true);
							$("#fake_status_spin").show();

							$.post("", {
								"fake_status": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function () {
								$("#fake_status_spin").hide();
								$("#fake_status_ok").show();
								$("[name=fake_status]").prop("disabled", false);
								setTimeout(function() {
									$("#fake_status_ok").hide();
								}, 2000);
							});
						});
						</script>

				    	<div class="form-group">
				    		<label><?=$l['PRIORITY'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="priority_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="priority_ok"></i></label>
				    		<select name="priority" class="form-control">
				    			<option value="5"<?php if ($info->priority == "5") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getPriorityText(false)["5"];?></option>
				    			<option value="4"<?php if ($info->priority == "4") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getPriorityText(false)["4"];?></option>
				    			<option value="3"<?php if ($info->priority == "3") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getPriorityText(false)["3"];?></option>
				    			<option value="2"<?php if ($info->priority == "2") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getPriorityText(false)["2"];?></option>
				    			<option value="1"<?php if ($info->priority == "1") {
        echo ' selected="selected"';
    }
    ?>><?=Ticket::getPriorityText(false)["1"];?></option>
				    		</select>
				    	</div>
						<script>
						$("[name=priority]").change(function(e) {
							$("#priority_ok").hide();
							$(this).prop("disabled", true);
							$("#priority_spin").show();

							$.post("", {
								"priority": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function () {
								$("#priority_spin").hide();
								$("#priority_ok").show();
								$("[name=priority]").prop("disabled", false);
								setTimeout(function() {
									$("#priority_ok").hide();
								}, 2000);
							});
						});
						</script>

				    	<div class="form-group">
				    		<label><?=$l['CUSTOMER'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="customer_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="customer_ok"></i></label> <a id="customer_link" href="?p=customers&edit=<?=$info->customer;?>" <?=!$info->customer ? 'style="display: none;"' : '';?>><i class="fa fa-user"></i></a>
				    		<input type="text" class="form-control customer-input" placeholder="<?=$l['NOTASSIGNED'];?>" value="<?=ci($info->customer);?>">
							<input type="hidden" name="customer" value="<?=$info->customer;?>">
							<div class="customer-input-results"></div>
						</div>
						<script>
						$("[name=customer]").change(function(e) {
							$("#customer_link").hide();
							$("#customer_ok").hide();
							$(this).prop("disabled", true);
							$("#customer_spin").show();

							$("#customer_stats").html("");
							$("#customer_notes").html("");

							$.post("", {
								"customer": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function (r) {
								$("#customer_spin").hide();
								$("[name=customer]").prop("disabled", false);

								if (r) {
									r = $.parseJSON(r);

									if (r.link) {
										$("#customer_link").prop("href", r.link).show();
									}

									if (r.stats) {
										$("#customer_stats").html(r.stats);
									}

									if (r.notes) {
										$("#customer_notes").html(r.notes);
									}
								} else {
									$("#customer_ok").show();
									setTimeout(function() {
										$("#customer_ok").hide();
									}, 2000);
								}
							});
						});
						</script>

						<?php
$sql = $db->query("SELECT 1 FROM support_filter WHERE `field` = 'email' AND `action` = 'delete' AND `type` = 'is' AND `value` = '" . $db->real_escape_string($sender) . "'");
    ?>

				    	<div class="form-group">
				    		<label><?=$l['SENDER'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="sender_spin"></i><a id="sender_lock" <?=$sql->num_rows > 0 ? 'style="display: none"' : '';?> href="?p=support_ticket&id=<?=$info->ID;?>&lock_sender=1"><i class="fa fa-lock"></i></a><a id="sender_unlock" <?=$sql->num_rows == 0 ? 'style="display: none"' : '';?> href="?p=support_ticket&id=<?=$info->ID;?>&unlock_sender=1"><i class="fa fa-unlock"></i></a></label>
				    		<input type="text" name="sender" value="<?=htmlentities($info->sender);?>" class="form-control" placeholder="<?=$l['SENDERP'];?>" />
				    	</div>
						<script>
						$("[name=sender]").change(function(e) {
							$("#sender_lock").hide();
							$("#sender_unlock").hide();
							$("#sender_ok").hide();
							$(this).prop("disabled", true);
							$("#sender_spin").show();

							$.post("", {
								"sender": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function (r) {
								$("#sender_spin").hide();
								$("#sender_" + r).show();
								$("[name=sender]").prop("disabled", false);
							});
						});
						</script>

						<div class="form-group">
				    		<label><?=$l['CC'];?> <i class="fa fa-spinner fa-spin" style="display: none;" id="cc_spin"></i><i class="fa fa-check" style="display: none; color: green;" id="cc_ok"></i></label>
				    		<input type="text" name="cc" value="<?=htmlentities($info->cc);?>" class="form-control" placeholder="<?=$l['CCP'];?>" />
				    	</div>
						<script>
						$("[name=cc]").change(function(e) {
							$("#cc_ok").hide();
							$(this).prop("disabled", true);
							$("#cc_spin").show();

							$.post("", {
								"cc": $(this).val(),
								"save": "1",
								"csrf_token": "<?=CSRF::raw();?>"
							}, function () {
								$("#cc_spin").hide();
								$("#cc_ok").show();
								$("[name=cc]").prop("disabled", false);
								setTimeout(function() {
									$("#cc_ok").hide();
								}, 2000);
							});
						});
						</script>

				    	<div class="form-group" style="margin-bottom: 0;">
				    		<label><?=$l['CREATED'];?></label><br />
				    		<span class="control-label"><?=$dfo->format($info->created, true, true);?></span>
				    	</div>
				    </form>
				  </div>
				</div>

				<div id="customer_stats">
					<?=$info->customer ? customerStatsHtml($info->customer) : "";?>
				</div>

				<?php $addons->runHook("BelowTicketInfo", $info);?>
			</div>

			<div class="col-md-9">
				<?php $addons->runHook("BeforeTicketAnswer", $info);?>

				<div id="customer_notes">
					<?=$info->customer ? customerNotesHtml($info->customer) : "";?>
				</div>

				<?php if ($info->draft_owner > 0 && $info->draft_owner != $adminInfo->ID) {
        $admin = $info->draft_owner;
        $sql = $db->query("SELECT name FROM admins WHERE ID = $admin");
        if ($sql->num_rows == 1) {
            $admin = htmlentities($sql->fetch_object()->name);
        }

        ?>
				<div class="alert alert-warning"><?=str_replace("%a", $admin, $l['IAA2']);?></div>
				<?php }?>

				<div class="panel panel-info">
				  <div class="panel-heading">
				    <h4 class="panel-title">
						<a href="#" role="button" id="answer_toggle">
							<?=$l['ANSWER'];?>
						</a><?php if ($info->draft_owner == $adminInfo->ID) {?>
						<span class="pull-right">
							<a href="#" id="delete_draft">
								<i class="fa fa-times"></i>
							</a>
						</span><?php }?>
					</h4>
				  </div>
				  <div id="answer_panel" class="panel-collapse collapse">
				  <form method="POST" enctype="multipart/form-data"><div class="panel-body">
				  	<?php
$sql = $db->query("SELECT ID, email, sender_name FROM support_email WHERE dept = " . $info->dept . " AND send = 1");
    if ($sql->num_rows == 0) {
        echo '<div class="alert alert-warning" style="margin-bottom: 0;">' . $l['NOMAIL'] . '</div>';
    } else {
        $mails = array();
        while ($row = $sql->fetch_object()) {
            $mails[$row->ID] = array($row->email, $row->sender_name);
        }

        ?>
				  	<input type="hidden" name="email" id="email" value="<?=array_keys($mails)[0];?>" />

				  	<div class="btn-group">
				  		<a href="#" class="btn btn-default btn-xs" id="predefined"><?=$l['PDA'];?></a>
				  		<a href="#" class="btn btn-default btn-xs" id="attachments"><?=$l['ATTACHMENTS'];?> (<span id="attcount">0</span>)</a>
				  	</div>
					<div class="btn-group pull-right">
					  <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					    <?=$l['SIGNATURE'];?> <span class="caret"></span>
					  </button>
					  <ul class="dropdown-menu">
					  	<?php
$sql = $db->query("SELECT * FROM support_signatures ORDER BY name ASC");
        $c = 0;
        while ($row = $sql->fetch_object()) {if ($db->query("SELECT 1 FROM support_signature_staff WHERE signature = " . $row->ID . " AND staff = " . $adminInfo->ID)->num_rows == 0) {
            continue;
        }

            $c++;?>
					  	<li><a href="#" onclick="load_signature(<?=$row->ID;?>); return false;"><?=$row->name;?></a></li>
					  	<?php }if ($c == 0) {?>
					  	<li><a href="#" onclick="return false;"><?=$l['NOSIGNATURES'];?></a></li>
					  	<?php }?>
					  </ul>
					</div>
					<div class="btn-group pull-right">
					  <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					    <span id="emailt"><?=htmlentities(array_values($mails)[0][1]);?> <?=htmlentities("<" . array_values($mails)[0][0] . ">");?></span> <span class="caret"></span>
					  </button>
					  <ul class="dropdown-menu">
					  	<?php foreach ($mails as $id => $i) {?>
					  	<li><a href="#" onclick="$('#email').val('<?=$id;?>'); $('#emailt').html('<?=htmlentities(htmlentities($i[1]));?> <?=htmlentities(htmlentities("<" . $i[0] . ">"));?>'); return false;"><?=htmlentities($i[1]);?> <?=htmlentities("<" . $i[0] . ">");?></a></li>
					  	<?php }?>
					  </ul> &nbsp;
					</div>

				  	<br /><br />

				  	<div id="predefined_container" style="display: none;">
						<div class="well">
							<?php
$sql = $db->query("SELECT ID, name FROM support_answer_categories ORDER BY name ASC");
        if ($sql->num_rows == 0) {
            echo $l['NOACAT'];
        } else {
            ?>
								<div class="row">
									<div class="col-md-3">
										<ul class="list-group" style="margin-bottom: 0;">
								<?php
while ($row = $sql->fetch_object()) {
                ?>
									<a class="list-group-item category-link" data-id="<?=$row->ID;?>"><?=$row->name;?></a>
									<?php
}
            ?>
										</ul>
									</div>

									<div class="col-md-9" id="category-content">
										<?=$l['PCCAT'];?>
									</div>
								</div>
								<?php
}
        ?>
						</div>
					</div>

					<script>
					var pd = 0;
					$("#predefined").click(function(e){
						e.preventDefault();

						if(pd){
							pd = 0;
							$(this).removeClass("active");
							$("#predefined_container").hide();
						} else {
							if(ad){
								ad = 0;
								$("#attachments").removeClass("active");
								$("#attachment_container").hide();
							}

							pd = 1;
							$(this).addClass("active");
							$("#predefined_container").show();
						}
					});

					$("#delete_draft").click(function(e){
						e.preventDefault();
						if(!confirm("<?=$l['DYWMD'];?>")) return;

						$.post("", {
							"delete_draft": 1,
							csrf_token: "<?=CSRF::raw();?>",
						}, function(r){
							location.reload();
						});
					});

					var doing = 0;
					$(".category-link").click(function(e){
						e.preventDefault();

						if(doing) return;
						doing = 1;

						var t = $(this);
						var i = t.data("id");
						var h = t.html();
						t.html('<i class="fa fa-spinner fa-spin"></i> ' + h);

						$.get("?p=support_ticket&id=<?=$info->ID;?>&support_cat=" + i, function(r){
							$("#category-content").html(r);
							$(".category-link").removeClass("active");
							t.html(h).addClass("active");
							doing = 0;
						});
					});

					jQuery.fn.extend({
						insertAtCaret: function(myValue, myValueE){
							return this.each(function(i) {
								if (document.selection) {
									this.focus();
									sel = document.selection.createRange();
									sel.text = myValue + myValueE;
									this.focus();
								}
								else if (this.selectionStart || this.selectionStart == '0') {
									var startPos = this.selectionStart;
									var endPos = this.selectionEnd;
									var scrollTop = this.scrollTop;
									this.value = this.value.substring(0,     startPos)+myValue+this.value.substring(startPos,endPos)+myValueE+this.value.substring(endPos,this.value.length);
									this.focus();
									this.selectionStart = startPos + myValue.length;
									this.selectionEnd = ((startPos + myValue.length) + this.value.substring(startPos,endPos).length);
									this.scrollTop = scrollTop;
								} else {
									this.value += myValue;
									this.focus();
								}
							})
						}
					});

					function add_html(id){
						$.get("?p=support_ticket&id=<?=$info->ID;?>&answer_id=" + id, function(code){
							$("[name=answer]").insertAtCaret("", code);
						});
					}
					</script>

				  	<div id="attachment_container" style="display: none;">
						<div class="well attachment_well">
							<div id="attachment_row">
								<div class="row">
									<div class="col-md-8">
										<input type="file" name="attachments[]" data-has="0" class="form-control input-sm stat" />
									</div>
									<div class="col-md-2" style="padding-top: 5px;">
										<a href="#" class="add_attachment"><i class="fa fa-plus"></i></a>
										<a href="#" class="add_attachment"><?=$l['ADDMORE'];?></a>
									</div>
									<div class="col-md-2 delete_attachment_col" style="padding-top: 5px; display: none;">
										<a href="#" class="delete_attachment"><i class="fa fa-times"></i></a>
										<a href="#" class="delete_attachment"><?=$l['DEL'];?></a>
									</div>
								</div>
							</div>
							<div id="attachment_row_template" style="display: none;">
								<div class="row">
									<div class="col-md-8">
										<input type="file" name="attachments[]" data-has="0" class="form-control input-sm stat" />
									</div>
									<div class="col-md-2" style="padding-top: 5px;">
										<a href="#" class="add_attachment"><i class="fa fa-plus"></i></a>
										<a href="#" class="add_attachment"><?=$l['ADDMORE'];?></a>
									</div>
									<div class="col-md-2 delete_attachment_col" style="padding-top: 5px; display: none;">
										<a href="#" class="delete_attachment"><i class="fa fa-times"></i></a>
										<a href="#" class="delete_attachment"><?=$l['DEL'];?></a>
									</div>
								</div>
							</div>
						</div>
					</div>

					<script>
					var ad = 0;
					$("#attachments").click(function(e){
						e.preventDefault();

						if(ad){
							ad = 0;
							$(this).removeClass("active");
							$("#attachment_container").hide();
						} else {
							if(pd){
								pd = 0;
								$("#predefined").removeClass("active");
								$("#predefined_container").hide();
							}

							ad = 1;
							$(this).addClass("active");
							$("#attachment_container").show();
						}
					});

					function bind_add_attachment(){
						$(".add_attachment").unbind('click').click(function(e){
							e.preventDefault();
							$("#attachment_row_template").clone().css("margin-top", "10px").show().appendTo(".attachment_well");
							bind_add_attachment();
						});

						$(".stat").unbind('change').change(function() {
							if ($(this).val() && !$(this).data("has")) {
								$("#attcount").html(parseInt($("#attcount").html()) + 1);
								$(this).parent().parent().find(".delete_attachment_col").show();
								$(this).data("has", 1);
							} else if (!$(this).val() && $(this).data("has")) {
								$(this).data("has", 0).val("");
								$("#attcount").html(parseInt($("#attcount").html()) - 1);
								$(this).parent().parent().find(".delete_attachment_col").hide();
							}
						});

						$(".delete_attachment").unbind('click').click(function(e) {
							e.preventDefault();
							$(this).parent().parent().find(".stat").data("has", 0).val("");
							$("#attcount").html(parseInt($("#attcount").html()) - 1);
							$(this).parent().hide();
						});
					}

					bind_add_attachment();
					</script>

					<div class="input-group" style="margin-bottom: 10px;">
						<span class="input-group-addon"><?=$l['ATO'];?></span>
						<input type="text" name="to" value="<?=htmlentities($info->sender);?>" class="form-control">
					</div>

					<div class="input-group" style="margin-bottom: 10px;">
						<span class="input-group-addon"><?=$l['ACC'];?></span>
						<input type="text" name="cc" value="<?=htmlentities($info->cc);?>" placeholder="<?=$l['CCP'];?>" class="form-control">
					</div>

					<div class="input-group" style="margin-bottom: 10px;">
						<span class="input-group-addon"><?=$l['ABCC'];?></span>
						<input type="text" name="bcc" value="" placeholder="<?=$l['CCP'];?>" class="form-control">
					</div>

					<textarea name="answer" class="form-control" id="answer_textarea" style="margin-bottom: 10px; width: 100%; height: 350px; resize: none;"><?=$t->getGreeting();?><?php $addons->runHook("TicketAnswerText", $info);?></textarea>
					<pre id="answer_preview" style="display: none;white-space:pre-wrap;"></pre>

					<input type="hidden" name="signature_id" value="0">
					<pre id="answer_signature" style="display: none;white-space:pre-wrap;"></pre>

				    <?=$l['PRIORITY'];?>:
				    <div class="btn-group" data-toggle="buttons">
					  <label class="btn btn-default btn-xs">
					    <input type="radio" name="priority" value="5" autocomplete="off"> <?=Ticket::getPriorityText(false)["5"];?>
					  </label>
					  <label class="btn btn-default btn-xs">
					    <input type="radio" name="priority" value="4" autocomplete="off"> <?=Ticket::getPriorityText(false)["4"];?>
					  </label>
					  <label class="btn btn-default btn-xs active">
					    <input type="radio" name="priority" value="3" autocomplete="off" checked> <?=Ticket::getPriorityText(false)["3"];?>
					  </label>
					  <label class="btn btn-default btn-xs">
					    <input type="radio" name="priority" value="2" autocomplete="off"> <?=Ticket::getPriorityText(false)["2"];?>
					  </label>
					  <label class="btn btn-default btn-xs">
					    <input type="radio" name="priority" value="1" autocomplete="off"> <?=Ticket::getPriorityText(false)["1"];?>
					  </label>
					</div>

					<span class="pull-right">
						<div class="btn-group" data-toggle="buttons">
						  <label class="btn btn-default btn-xs">
						    <input type="checkbox" autocomplete="off" name="read_confirmation" value="1"> <?=$l['READCONF'];?>
						  </label>
						  <label class="btn btn-default btn-xs">
						    <input type="checkbox" autocomplete="off" name="delivery_receipt" value="1"> <?=$l['DELICONF'];?>
						  </label>
						</div>
					</span>

					<div class="row" style="margin-top: 10px;">
						<div class="col-md-4">
							<select name="new_priority" class="form-control">
								<option value="-"><?=$l['NEWPRIO'];?></option>
								<option value="5"><?=Ticket::getPriorityText(false)["5"];?></option>
								<option value="4"><?=Ticket::getPriorityText(false)["4"];?></option>
								<option value="3"><?=Ticket::getPriorityText(false)["3"];?></option>
								<option value="2"><?=Ticket::getPriorityText(false)["2"];?></option>
								<option value="1"><?=Ticket::getPriorityText(false)["1"];?></option>
							</select>
						</div>

						<div class="col-md-4">
							<select name="new_status" class="form-control">
								<option value="-" disabled=""><?=$l['NEWSTATUS'];?></option>
								<option value="0"><?=Ticket::getStatusNames()["0"];?></option>
								<option value="1"><?=Ticket::getStatusNames()["1"];?></option>
								<option value="2" selected=""><?=Ticket::getStatusNames()["2"];?></option>
								<option value="3"><?=Ticket::getStatusNames()["3"];?></option>
							</select>
						</div>

						<div class="col-md-4">
							<select name="new_action" class="form-control">
								<option value="-" disabled=""><?=$l['NEWACTION'];?></option>
								<option value="list"><?=$l['ACTION_LIST'];?></option>
								<option value="keep"><?=$l['ACTION_KEEP'];?></option>
								<?php if ($hasNext) {?><option value="next"<?=$adminInfo->next_ticket ? ' selected=""' : '';?>><?=$l['ACTION_NEXT'];?><?php }?>
							</select>
						</div>
					</div>

					<a href="#" id="show_preview" class="btn btn-primary btn-block" style="margin-top: 10px;"><?=$l['PREVIEW'];?></a>

					<div id="send_actions" style="display: none;">
						<input type="hidden" name="action" value="answer">
						<div class="row" style="margin-top: 10px;">
						<div class="col-md-4">
								<input type="button" id="change_answer" value="<?=$l['EDIT'];?>" class="btn btn-default btn-block" />
							</div>
							<div class="col-md-4">
								<input type="submit" name="answer_btn" value="<?=$l['ANSWERDO'];?>" class="btn btn-primary btn-block btn-doit" />
							</div>
							<div class="col-md-4">
								<input type="submit" name="memo" value="<?=$l['CREATENOTE'];?>" class="btn btn-default btn-block btn-doit" />
							</div>
						</div>
					</div>
					<?php }?>
				  </div></form></div>
				</div>

				<?php
$j = 0;
    $sql = $db->query("SELECT * FROM support_ticket_answers WHERE ticket = " . $info->ID . " ORDER BY time DESC, ID DESC");
    while ($row = $sql->fetch_object()) {
        // Inline image handling start
        $minPos = 0;
        while (($pos = strpos($row->message, "<img", $minPos)) !== false) {
            $minPos = $pos + 1;
            $tag = substr($row->message, $pos);
            $tag = substr($tag, 0, intval(strpos($tag, ">")) + 1);

            foreach (["'", '"'] as $sep) {
                $cidPos = strpos($tag, "src={$sep}cid:");
                if ($cidPos !== false) {
                    $cid = substr($tag, $cidPos + 9);
                    $endPos = strpos($cid, $sep);
                    $addLen = strpos($tag, ">") - $endPos - $cidPos;

                    if ($endPos !== false) {
                        $cid = substr($cid, 0, $endPos);

                        for ($i = strlen($cid) - 1; $i >= 0; $i--) {
                            $char = substr($cid, -1);
                            $cid = substr($cid, 0, -1);
                            if ($char == "@") {
                                break;
                            }
                        }

                        if (!empty($cid)) {
                            $cid = $CFG['PAGEURL'] . "admin/?p=support_ticket&id=" . $row->ticket . "&msg=" . $row->ID . "&cid=" . urlencode($cid);

                            $myMsg = substr($row->message, 0, $pos);
                            $myMsg .= '<img src="' . $cid . '">';
                            $myMsg .= substr($row->message, $pos + $cidPos + $endPos + $addLen + 1);

                            $row->message = $myMsg;
                        }
                    }
                }
            }
        }
        // Inline image handling end

        $ch = $purifier->purify($row->message);

        $config2 = HTMLPurifier_Config::createDefault();
        $purifier2 = new HTMLPurifier($config2);
        $ch2 = $purifier2->purify($row->message);

        if ($row->staff) {
            $ch = $ch2;
        }

        $color = array(
            "1" => 'red',
            "2" => 'orange',
            "3" => 'black',
            "4" => 'lime',
            "5" => 'green',
        )[$row->priority];

        $text = array(
            "1" => Ticket::getPriorityText(false)["1"],
            "2" => Ticket::getPriorityText(false)["2"],
            "3" => Ticket::getPriorityText(false)["3"],
            "4" => Ticket::getPriorityText(false)["4"],
            "5" => Ticket::getPriorityText(false)["5"],
        )[$row->priority];
        ?>
				<div class="panel panel-<?=$row->staff > 0 ? "info" : ($row->staff < 0 ? "warning" : "default");?>">
				  <?php
$avatar = "";
        if ($row->staff != 0) {
            $sql2 = $db->query("SELECT name,avatar FROM admins WHERE ID = " . abs($row->staff));
            if ($sql2->num_rows) {
                $ai = $sql2->fetch_object();
                $row->sender = $ai->name;
                $avatar = "../files/avatars/";
                if (!empty($ai->avatar) && file_exists(__DIR__ . "/../../files/avatars/" . basename($ai->avatar))) {
                    $avatar .= htmlentities(basename($ai->avatar));
                } else {
                    $avatar .= "none.png";
                }
            }
        }
        ?>
				  <div class="panel-heading">
	<h3 class="panel-title"><a href="#" class="expand" data-id="<?=$row->ID;?>" style="color: #428bca;"><i class="fa fa-caret-square-o-<?=$j == 0 ? 'down' : 'right';?>"></i></a> <?php if ($avatar) {?><a href="?p=admin&id=<?=abs($row->staff);?>" style="text-decoration: none;"><img style="border-radius: 50%; height: 15px; width: 15px;" src="<?=$avatar;?>" title="<?=htmlentities($row->sender);?>" alt="<?=htmlentities($row->sender);?>" /> <?php }?><?=htmlentities($row->sender);?><?php if ($avatar) {?></a><?php }?> <small><a href="#" data-toggle="tooltip" title="<?=$text;?>"><i class="fa fa-exclamation" style="color: <?=$color;?>;"></i></a> <?=$dfo->format($row->time, true, true);?><?=$row->customer_read ? ' <i class="fa fa-eye"></i>' : '';?></small><span class="pull-right"><?php if ($ari->check(68)) {?><a href="?p=abuse&msg=<?=$row->ID;?>"><i class="fa fa-exclamation-triangle"></i></a><?php }?><?php if ($ch != $ch2) {?> <a href="#" class="fetch_images" data-id="<?=$row->ID;?>"><i class="fa fa-image"></i></a><?php }?> &nbsp;<a href="#" class="send_via_mail" data-id="<?=$row->ID;?>"><i class="fa fa-mail-forward"></i></a><?php if ($row->staff > 0 && $db->query("SELECT ID, email, sender_name FROM support_email WHERE dept = " . $info->dept . " AND send = 1")->num_rows > 0) {?> &nbsp;<a href="#" class="send_answer" data-id="<?=$row->ID;?>"><i class="fa fa-repeat"></i></a><?php }?> &nbsp;<a href="#" class="delete_answer" data-id="<?=$row->ID;?>"><i class="fa fa-times-circle"></i></a></span></h3>
				  </div>
				  <div class="panel-body" id="abstract-<?=$row->ID;?>" style="color: grey;<?php if ($j == 0) {?> display: none;<?php }?>">
				  	<?php
$short = nl2br($ch);
        $short = str_replace(["<br>", "<br />", "<br/>"], " ", $short);
        $short = strip_tags($short);
        if (strlen($short) > 100) {
            $short = substr($short, 0, 100) . "...";
        }

        echo $short;
        ?>
				  </div>
				  <div class="panel-body" id="content-<?=$row->ID;?>"<?php if ($j != 0) {?> style="display: none;"<?php }?>>
				  	<?php if ($ch2 != $ch) {
            echo '<div class="alert alert-warning" style="padding-top: 7px; padding-bottom: 7px;"><small>' . $l['IMGHINT'] . '</small></div>';
        }
        ?>

				    <?=nl2br(trim($ch));?>
				  </div>
				  <?php if ($db->query("SELECT 1 FROM support_ticket_attachments WHERE message = " . $row->ID)->num_rows > 0) {?>
				  <div class="panel-footer">
				  	<?php
$sql2 = $db->query("SELECT * FROM support_ticket_attachments WHERE message = " . $row->ID . " ORDER BY name ASC");
            $i = 0;while ($row2 = $sql2->fetch_object()) {
                if ($i > 0) {
                    echo '<br />';
                }

                $fileLen = strlen($row2->file);
                if (substr($row2->file, 0, 5) == "file#") {
                    $fileLen = strlen(file_get_contents(__DIR__ . "/../../files/tickets/" . basename(substr($row2->file, 5))));
                }

                ?>
				    	<i class="fa fa-paperclip"></i> <a href="?p=support_ticket&id=<?=$info->ID;?>&file=<?=$row2->ID;?>" target="_blank"><?=htmlentities($row2->name);?></a> (<?=number_format($fileLen / 1024, 2, ',', '.');?> KB)
				    	<?php
$i++;
            }
            ?>
				  </div>
				  <?php }?>
				</div>
				<?php $j++;}?>

				<script>
				$(document).ready(function(){
					var doingitsend = 0;
					$(".btn-doit").click(function(e) {
						if (doingitsend) {
							e.preventDefault();
						}
						doingitsend = 1;
					});

					$(".expand").click(function(e){
						e.preventDefault();
						if($(this).find("i").hasClass("fa-caret-square-o-right")){
							$(this).find("i").removeClass("fa-caret-square-o-right").addClass("fa-caret-square-o-down");
							$("#abstract-" + $(this).data("id")).slideUp();
							$("#content-" + $(this).data("id")).slideDown();
						} else {
							$(this).find("i").addClass("fa-caret-square-o-right").removeClass("fa-caret-square-o-down");
							$("#content-" + $(this).data("id")).slideUp();
							$("#abstract-" + $(this).data("id")).slideDown();
						}
					});

					var doing_preview = 0;
					$("#show_preview").click(function(e){
						e.preventDefault();
						if(!doing_preview){
							$(this).html("<i class='fa fa-spinner fa-spin'></i> <?=$l['PW'];?>");
							doing_preview = 1;
							$.post("", {
								"preview": $("#answer_textarea").val(),
								csrf_token: "<?=CSRF::raw();?>",
							}, function(r){
								$("#answer_textarea").hide();
								$("#answer_preview").html(r).show();
								$("#send_actions").show();
								doing_preview = 0;
								$("#show_preview").html("<?=$l['PREVIEW'];?>").hide();
							});
						}
					});

					$("#change_answer").click(function(e){
						e.preventDefault();
						$("#answer_preview").hide();
						$("#answer_textarea").show();
						$("#send_actions").hide();
						$("#show_preview").show();
					});

					<?php if ($info->draft_owner == $adminInfo->ID) {
        $draft = explode("-", $info->draft);
        $signature = array_pop($draft);
        $draft = str_replace("\n", "\\n", implode("-", $draft));
        ?>

					<?php if ($signature > 0) {?>
					load_signature(<?=$signature;?>);
					<?php }?>
					$("[name=answer]").val("<?=str_replace('"', '\"', $draft);?>");
					$("#answer_panel").slideDown();
					answer = 1;

					setInterval(function(){
						$.post("", {
							"action": "saveDraft",
							"draft": $("[name=answer]").val() + "-" + $("[name=signature_id]").val(),
							csrf_token: "<?=CSRF::raw();?>",
						});
					}, 15000);

					$.post("", {
						"action": "saveDraft",
						"draft": $("[name=answer]").val() + "-" + $("[name=signature_id]").val(),
						csrf_token: "<?=CSRF::raw();?>",
					});
					<?php } else if ($sign > 0) {?>
					load_signature(<?=$sign;?>);
					<?php }?>
				});

				$(".fetch_images").click(function(e){
					e.preventDefault();

					var i = $(this).find("i");
					i.removeClass("fa-image").addClass("fa-spinner fa-spin");
					var b = $("#content-" + $(this).data("id"));

					$.get("?p=support_ticket&id=<?=$info->ID;?>&img_msg=" + $(this).data("id"), function(r){
						i.parent().remove();
						b.html(r);
					});
				});

				$(".send_answer").click(function(e){
					e.preventDefault();

					var t = $(this).parent().parent().parent().parent();
					var m = $(this);

					swal({
						title: '<?=$l['DYWSA'];?>',
						text: '<?=$l['DYWSA2'];?>',
						type: 'warning',
						showCancelButton: true,
						confirmButtonColor: '#DD6B55',
						confirmButtonText: '<?=$l['YES'];?>',
						cancelButtonText: '<?=$l['NO'];?>',
						closeOnConfirm: true
					}, function(){
						$.get("?p=support_ticket&id=<?=$info->ID;?>&resend_msg=" + m.data("id"), function(r){
							if(r == "ok") alert("<?=$l['RSA'];?>");
							else alert(r);
						});
					});
				});

				$(".send_via_mail").click(function(e){
					e.preventDefault();

					var m = $(this);

					var recipient = prompt("<?=$l['DYWREDIR'];?>");

					if (recipient) {
						$.get("?p=support_ticket&id=<?=$info->ID;?>&redirect_msg=" + m.data("id") + "&recipient=" + encodeURIComponent(recipient), function(r){
							if(r == "ok") alert("<?=$l['DYWREDIRS'];?>");
							else alert(r);
						});
					}
				});

				$(".delete_answer").click(function(e){
					e.preventDefault();

					var t = $(this).parent().parent().parent().parent();
					var m = $(this);

					swal({
						title: '<?=$l['DYWRD'];?>',
						text: '<?=$l['DYWRD2'];?>',
						type: 'warning',
						showCancelButton: true,
						confirmButtonColor: '#DD6B55',
						confirmButtonText: '<?=$l['YES'];?>',
						cancelButtonText: '<?=$l['NO'];?>',
						closeOnConfirm: true
					}, function(){
						$.get("?p=support_ticket&id=<?=$info->ID;?>&delete_msg=" + m.data("id"), function(r){
							if(r == "ok") t.slideUp();
						});
					});
				});

				$(".delete_ticket").click(function(e){
					e.preventDefault();

					swal({
						title: '<?=$l['DYWRD'];?>',
						text: '<?=$l['DYWRD3'];?>',
						type: 'warning',
						showCancelButton: true,
						confirmButtonColor: '#DD6B55',
						confirmButtonText: '<?=$l['YES'];?>',
						cancelButtonText: '<?=$l['NO'];?>',
						closeOnConfirm: false
					}, function(){
						$.get("?p=support_ticket&id=<?=$info->ID;?>&delete_ticket=<?=$info->ID;?>", function(r){
							window.location = "?p=support_tickets&dept=<?=$info->dept > 0 ? $info->dept : -1;?>&s=<?=$info->status;?>";
						});
					});
				});

				var answer = 0;
				$("#answer_toggle").click(function(e){
					e.preventDefault();

					if(answer){
						$("#answer_panel").slideUp();
						answer = 0;
					} else {
						$.post("", {
							"action": "wantToAnswer",
							csrf_token: "<?=CSRF::raw();?>",
						}, function(r){
							if(r == "ok"){
								$("#answer_panel").slideDown();
								answer = 1;

								setInterval(function(){
									$.post("", {
										"action": "saveDraft",
										"draft": $("[name=answer]").val() + "-" + $("[name=signature_id]").val(),
										csrf_token: "<?=CSRF::raw();?>",
									});
								}, 15000);

								$.post("", {
									"action": "saveDraft",
									"draft": $("[name=answer]").val() + "-" + $("[name=signature_id]").val(),
									csrf_token: "<?=CSRF::raw();?>",
								});
							} else {
								swal({
									title: '<?=$l['DYRWTA'];?>',
									text: r,
									type: 'warning',
									showCancelButton: true,
									confirmButtonColor: '#DD6B55',
									confirmButtonText: 'Ja',
									cancelButtonText: 'Nein',
									closeOnConfirm: true
								}, function(){
									$("#answer_panel").slideDown();
									answer = 1;

									setInterval(function(){
										$.post("", {
											"action": "saveDraft",
											"draft": $("[name=answer]").val() + "-" + $("[name=signature_id]").val(),
											csrf_token: "<?=CSRF::raw();?>",
										});
									}, 15000);

									$.post("", {
										"action": "saveDraft",
										"draft": $("[name=answer]").val() + "-" + $("[name=signature_id]").val(),
										csrf_token: "<?=CSRF::raw();?>",
									});
								});
							}
						});
					}
				});
				</script>
			</div>
		</div>
	</div>
</div>
<?php }