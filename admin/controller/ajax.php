<?php
// Controller for handling ajax requests
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Global some variables for security reasons
global $adminInfo, $db, $CFG, $_POST, $ari, $dfo, $lang, $session, $addons, $nfo, $cur;

// Handle requested action
switch ($_REQUEST['action']) {
    case "customer_search":
        $id = $id2 = "";

        if (is_numeric($_POST['searchword'])) {
            $id = "OR ID = " . intval($_POST['searchword']) . " ";
            $id2 = "ID = " . intval($_POST['searchword']) . " DESC, ";
        }

        $sql = $db->query("SELECT ID, company, firstname, lastname, mail FROM clients WHERE CONCAT(firstname, ' ', lastname) LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR company LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR mail LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' {$id}ORDER BY {$id2}firstname ASC, lastname ASC, company ASC, mail ASC LIMIT 10");

        $limit = 10 - $sql->num_rows;

        if ($limit) {
            $sql2 = $db->query("SELECT client, firstname, lastname, company, mail FROM client_contacts WHERE CONCAT(firstname, ' ', lastname) LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR company LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' OR mail LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' ORDER BY firstname ASC, lastname ASC, company ASC, mail ASC LIMIT $limit");
        }

        if (!$sql->num_rows && !$sql2->num_rows) {
            die("<i class='fa fa-times fa-fw'></i> " . $lang['AJAX']['NO_RESULT']);
        }
        ?>
		<div class="list-group" style="margin-bottom: 0;">
			<?php while ($u = $sql->fetch_object()) {?>
			<a href="#" class="list-group-item customer-input-result" data-id="<?=$u->ID;?>" data-text="<?=$CFG['CNR_PREFIX'] ?: "#";?><?=$u->ID;?> <?=htmlentities($u->firstname) . " " . htmlentities($u->lastname);?>">
				<b><?=User::getInstance($u->ID, "ID")->getfName();?></b> <?=!empty($u->company) ? "(" . htmlentities($u->company) . ")" : "";?><br />
				<small><?=$CFG['CNR_PREFIX'] ?: "#";?><?=$u->ID;?> | <?=htmlentities($u->mail);?></small>
			</a>
			<?php }if (isset($sql2)) {while ($c = $sql2->fetch_object()) {
            $u = User::getInstance($c->client, "ID");
            if (!$u) {
                continue;
            }
            $u = (object) $u->get();
            ?>
			<a href="#" class="list-group-item customer-input-result" data-id="<?=$c->client;?>" data-text="<?=$CFG['CNR_PREFIX'] ?: "#";?><?=$u->ID;?> <?=htmlentities($u->firstname) . " " . htmlentities($u->lastname);?>">
				<b><?=htmlentities($c->firstname . " " . $c->lastname);?></b> <?=!empty($c->company) ? "(" . htmlentities($c->company) . ")" : "";?><br />
				<small><?=$CFG['CNR_PREFIX'] ?: "#";?><?=$u->ID;?> | <?=User::getInstance($u->ID, "ID")->getfName();?><?=!empty($u->company) ? " (" . htmlentities($u->company) . ")" : "";?></small>
			</a>
			<?php }}?>
		</div>

		<script>
		$(".customer-input-result").click(function(e) {
			e.preventDefault();
			var g = $(this).parent().parent().parent();
			g.find("[type=hidden]").val($(this).data("id")).trigger("change");
			g.find(".customer-input").val($(this).data("text"));
			g.find(".customer-input-results").html("").hide();
		});
		</script>
		<?php
break;

case "invoice_search":
        if (substr($_POST['searchword'], 0, strlen($CFG['INVOICE_PREFIX'])) == $CFG['INVOICE_PREFIX']) {
            $_POST['searchword'] = substr($_POST['searchword'], strlen($CFG['INVOICE_PREFIX']));
        }

        $sql = $db->query("SELECT ID FROM invoices WHERE ID = " . intval($_POST['searchword']) . " OR customno LIKE '%" . $db->real_escape_string($_POST['searchword']) . "%' LIMIT 10");

        if (!$sql->num_rows) {
            die("<i class='fa fa-times fa-fw'></i> " . $lang['AJAX']['NO_RESULT']);
        }

        $inv = new Invoice;
        ?>
		<div class="list-group" style="margin-bottom: 0;">
			<?php while ($u = $sql->fetch_object()) { $inv->load($u->ID); ?>
			<a href="#" class="list-group-item invoice-input-result" data-id="<?=$u->ID;?>" data-text="<?=$inv->getInvoiceNo();?>">
				<b><?=$inv->getInvoiceNo();?></b> <?php if ($inv->getStatus() == 0) {?><font color="red"><?=$lang['CUSTOMERS']['INVS1'];?></font><?php } else if ($inv->getStatus() == 1) {?><font color="green"><?=$lang['CUSTOMERS']['INVS2'];?></font><?php } else if ($inv->getStatus() == 2) {?><?=$lang['CUSTOMERS']['INVS3'];?><?php } else {?><?=$lang['CUSTOMERS']['INVS0'];?><?php }?><br />
				<small><?=$cur->infix($nfo->format($inv->getOpenAmount()), $cur->getBaseCurrency()); ?><?=$inv->getOpenAmount() != $inv->getAmount() ? " (" . $cur->infix($nfo->format($inv->getAmount()), $cur->getBaseCurrency()) . ")" : ""; ?> | <?=$dfo->format($inv->getDate(), false); ?></small>
			</a>
			<?php } ?>
		</div>

		<script>
		$(".invoice-input-result").click(function(e) {
			e.preventDefault();
			var g = $(this).parent().parent().parent();
			g.find("[type=hidden]").val($(this).data("id")).trigger("change");
			g.find(".invoice-input").val($(this).data("text"));
			g.find(".invoice-input-results").html("").hide();
		});
		</script>
		<?php
break;

    case 'user_logout':
        alog("customers", "logged_out", $session->get('mail'));
        $session->remove('mail');
        break;

    case 'toggle_shortcut':
        if (!isset($_POST['parameters'])) {
            die("parameters_missing");
        }

        if ($adminInfo->ID <= 0) {
            die("admin_failed");
        }

        parse_str($_POST['parameters'], $parameters);
        ksort($parameters);
        $parameters = http_build_query($parameters);

        $sql = $db->query("SELECT ID FROM admin_shortcut WHERE admin = " . $adminInfo->ID . " AND url = '" . $db->real_escape_string($parameters) . "'");
        if ($sql->num_rows > 0) {
            while ($row = $sql->fetch_object()) {
                $db->query("DELETE FROM admin_shortcut WHERE ID = " . $row->ID . " LIMIT 1");
            }

            die("false");
        }

        $db->query("INSERT INTO admin_shortcut (`admin`, `url`, `text`) VALUES (" . $adminInfo->ID . ", '" . $db->real_escape_string($parameters) . "', '" . html_entity_decode($lang['QUICKLINKS']['NEW']) . "')");
        die("true");
        break;

    case 'delete_shortcut':
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            die("id_missing");
        }

        if ($adminInfo->ID <= 0) {
            die("admin_failed");
        }

        $db->query("DELETE FROM admin_shortcut WHERE ID = " . intval($_POST['id']) . " AND admin = " . $adminInfo->ID . " LIMIT 1");
        break;

    case 'change_shortcut':
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            die("id_missing");
        }

        if (!isset($_POST['text'])) {
            die("text_missing");
        }

        if ($adminInfo->ID <= 0) {
            die("admin_failed");
        }

        $db->query("UPDATE admin_shortcut SET text = '" . $db->real_escape_string($_POST['text']) . "' WHERE ID = " . intval($_POST['id']) . " AND admin = " . $adminInfo->ID . " LIMIT 1");
        break;

    case 'save_link':
        if (!$ari->check(40)) {
            alog("cms_links", "save_insuff");
            die("failed");
        }

        if (!isset($_POST['slug'])) {
            die("slug_missing");
        }

        if (!isset($_POST['target'])) {
            die("target_missing");
        }

        $db->query("UPDATE cms_links SET target = '" . $db->real_escape_string($_POST['target']) . "' WHERE slug = '" . $db->real_escape_string($_POST['slug']) . "' LIMIT 1");
        alog("cms_links", "save_ok");
        break;

    case 'save_notes':
        if (isset($_POST['text']) && $db->query("UPDATE admins SET notes = '" . $db->real_escape_string($_POST['text']) . "' WHERE ID = " . $adminInfo->ID . " LIMIT 1")) {
            echo "saved";
            alog("general", "notes_saved");
        } else {
            echo "failed";
            alog("general", "notes_failed");
        }
        break;

    case 'save_stem':
        if (!$ari->check(40)) {
            alog("liabilities", "stem_changed_insuff");
            die("failed");
        }
        if (isset($_POST['stem_auto'])) {
            $stem_auto = str_replace(",", ".", $_POST['stem_auto']);
        }

        if (isset($stem_auto) && is_numeric($stem_auto) && $db->query("UPDATE settings SET value = '" . $db->real_escape_string($stem_auto) . "' WHERE `key` = 'stem_auto' LIMIT 1")) {
            echo "saved";
            alog("liabilities", "stem_changed", $stem_auto);
        } else {
            echo "failed";
            alog("liabilities", "stem_changed_failed");
        }
        break;

    case 'save_cron':
        try {
            if (!$ari->check(34)) {
                alog("cronjob", "pw_changed_insuff");
                throw new Exception();
            }

            if (!isset($_POST['cron_id']) || !isset($_POST['cron_pw'])) {
                throw new Exception();
            }

            if ($db->query("SELECT ID FROM cronjobs WHERE ID = '" . $db->real_escape_string($_POST['cron_id']) . "'")->num_rows != 1) {
                throw new Exception();
            }

            $info = $db->query("SELECT `key` FROM cronjobs WHERE ID = '" . $db->real_escape_string($_POST['cron_id']) . "'")->fetch_object();

            $db->query("UPDATE cronjobs SET password = '" . $db->real_escape_string($_POST['cron_pw']) . "' WHERE ID = '" . $db->real_escape_string($_POST['cron_id']) . "' LIMIT 1");
            echo $CFG['PAGEURL'] . "cron?job=" . $info->key;
            if (trim($_POST['cron_pw']) != "") {
                echo "&pw=" . $_POST['cron_pw'];
            }

            alog("cronjob", "pw_changed", $info->key);
        } catch (Exception $ex) {
            echo "failed";
        }
        break;

    case 'load_task_times':
        if (!$ari->check(31)) {
            alog("project", "times_insuff", $_POST['id']);
            die("failed");
        }

        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            die("failed");
        }

        if ($_POST['id'] < 0) {
            if (!($sql = $db->query("SELECT `name` FROM projects WHERE ID = " . (intval($_POST['id']) / -1))) || !$sql->num_rows) {
                die("failed");
            }
        } else {
            if (!($sql = $db->query("SELECT `name` FROM project_tasks WHERE ID = " . intval($_POST['id']))) || !$sql->num_rows) {
                die("failed");
            }
        }

        $ret = array();
        $ret['name'] = $sql->fetch_object()->name;
        $ret['times'] = array();

        $timesSql = $db->query("SELECT * FROM project_times WHERE task = " . intval($_POST['id']) . " ORDER BY ID ASC");
        while ($row = $timesSql->fetch_object()) {
            $from = $dfo->format(strtotime($row->start), true, true);
            $to = $row->end == "0000-00-00 00:00:00" ? Project::time(time() - strtotime($row->start), true) : $dfo->format(strtotime($row->end), true, true);
            $done = 1;
            if ($row->end == "0000-00-00 00:00:00") {
                $done = 0;
            } else {
                $duration = Project::time(strtotime($row->end) - strtotime($row->start), true);
            }

            $adminSql = $db->query("SELECT name FROM admins WHERE ID = " . intval($row->admin));
            if ($adminSql->num_rows == 1) {
                $adminName = htmlentities($adminSql->fetch_object()->name);
            } else {
                $adminName = "<i>" . $lang['AJAX']['UNKNWON'] . "</i>";
            }

            array_push($ret['times'], array("ID" => $row->ID, "from" => $from, "to" => $to, "duration" => $duration, "staff" => $adminName, "done" => $done, "secs" => time() - strtotime($row->start)));
        }
        alog("project", "times_fetched", $_POST['id']);
        die(json_encode($ret));
        break;

    case 'delete_task_time':
        if (!$ari->check(31)) {
            alog("project", "times_insuff", $_POST['id']);
            die("failed");
        }

        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            die("failed");
        }

        if ($db->query("DELETE FROM project_times WHERE ID = " . intval($_POST['id']) . " LIMIT 1")) {
            die("ok");
        }

        alog("project", "times_deleted", $_POST['id']);
        die("failed");
        break;

    case 'add_task_time':
        if (!$ari->check(31)) {
            alog("project", "times_insuff", $_POST['task']);
            die("failed");
        }

        if (empty($_POST['task']) || !is_numeric($_POST['task'])) {
            die("failed");
        }

        if ($_POST['task'] < 0) {
            if ($db->query("SELECT ID FROM projects WHERE ID = " . (intval($_POST['task']) / -1))->num_rows != 1) {
                die("failed");
            }
        } else {
            if ($db->query("SELECT ID FROM project_tasks WHERE ID = " . intval($_POST['task']))->num_rows != 1) {
                die("failed");
            }
        }

        if (empty($_POST['from'])) {
            die("failed");
        }

        if (empty($_POST['to'])) {
            die("failed");
        }

        if (empty($_POST['staff'])) {
            die("failed");
        }

        if ($db->query("SELECT ID FROM admins WHERE ID = " . intval($_POST['staff']))->num_rows != 1) {
            die("failed");
        }

        $start = date("Y-m-d H:i:s", strtotime(str_replace("-", "", $_POST['from'])));
        $end = date("Y-m-d H:i:s", strtotime(str_replace("-", "", $_POST['to'])));
        if ($start > $end) {
            die("failed");
        }

        if (!$db->query("INSERT INTO project_times (`admin`, `task`, `start`, `end`) VALUES (" . intval($_POST['staff']) . ", " . intval($_POST['task']) . ", '$start', '$end')")) {
            die("failed");
        }

        $duration = Project::time(strtotime($end) - strtotime($start), true);
        $adminSql = $db->query("SELECT name FROM admins WHERE ID = " . intval($_POST['staff']));
        if ($adminSql->num_rows == 1) {
            $adminName = htmlentities($adminSql->fetch_object()->name);
        } else {
            $adminName = "<i>" . $lang['AJAX']['UNKNOWN'] . "</i>";
        }

        alog("project", "times_added", $_POST['task']);
        die(json_encode(array("ID" => $db->insert_id, "from" => $dfo->format(strtotime($start), true, true), "to" => $dfo->format(strtotime($end), true, true), "duration" => $duration, "staff" => $adminName)));
        break;

    case 'get_task_time':
        if (!$ari->check(31)) {
            alog("project", "times_insuff", $_POST['task']);
            die("failed");
        }

        if (empty($_POST['task']) || !is_numeric($_POST['task'])) {
            die("failed");
        }

        $timesSql = $db->query("SELECT start, end FROM project_times WHERE task = " . intval($_POST['task']));
        if ($timesSql->num_rows == 0) {
            $time = Project::time(0);
        } else {
            $ttime = 0;

            while ($t = $timesSql->fetch_object()) {
                if ($t->end == "0000-00-00 00:00:00") {
                    $t->end = date("Y-m-d H:i:s");
                }

                if (($subTime = strtotime($t->end) - strtotime($t->start)) > 0) {
                    $ttime += $subTime;
                }

            }

            $time = Project::time($ttime, true);

            $gtime = 0;
            $projectSql = $db->query("SELECT project FROM project_tasks WHERE ID = " . intval($_POST['task']));
            if ($projectSql->num_rows == 1) {
                $projectId = $projectSql->fetch_object()->project;
                $gSql = $db->query("SELECT start, end FROM project_times INNER JOIN project_tasks ON project_times.task = project_tasks.ID WHERE project_tasks.project = " . intval($projectId));

                while ($t = $gSql->fetch_object()) {
                    if ($t->end == "0000-00-00 00:00:00") {
                        $t->end = date("Y-m-d H:i:s");
                    }

                    if (($subTime = strtotime($t->end) - strtotime($t->start)) > 0) {
                        $gtime += $subTime;
                    }

                }
            }
        }
        alog("project", "times_got", $_POST['task']);
        die(json_encode(array("formatted" => $time, "raw" => $ttime ?: 0, "formatted_all" => Project::time($gtime, true), "raw_all" => $gtime ?: 0)));
        break;

    case 'start_task_time':
        if (!$ari->check(31)) {
            alog("project", "times_insuff", $_POST['task']);
            die("failed");
        }

        if (Project::working() !== false) {
            die("failed");
        }

        if (empty($_POST['task']) || !is_numeric($_POST['task'])) {
            die("failed");
        }

        if ($_POST['task'] < 0) {
            if ($db->query("SELECT ID FROM projects WHERE ID = " . (intval($_POST['task']) / -1))->num_rows != 1) {
                die("failed");
            }
        } else {
            if ($db->query("SELECT ID FROM project_tasks WHERE ID = " . intval($_POST['task']))->num_rows != 1) {
                die("failed");
            }
        }

        if ($db->query("SELECT ID FROM admins WHERE ID = " . intval($adminInfo->ID))->num_rows != 1) {
            die("failed");
        }

        $start = date("Y-m-d H:i:s");
        $end = "0000-00-00 00:00:00";
        alog("project", "times_started", $_POST['task']);
        if (!$db->query("INSERT INTO project_times (`admin`, `task`, `start`, `end`) VALUES (" . intval($adminInfo->ID) . ", " . intval($_POST['task']) . ", '$start', '$end')")) {
            die("failed");
        }

        die("ok");
        break;

    case 'pause_task_time':
        if (!$ari->check(31)) {
            alog("project", "times_insuff", $_POST['task']);
            die("failed");
        }

        if (empty($_POST['task']) || !is_numeric($_POST['task'])) {
            die("failed");
        }

        if (Project::working() != $_POST['task']) {
            die("failed");
        }

        if ($_POST['task'] < 0) {
            if ($db->query("SELECT ID FROM projects WHERE ID = " . (intval($_POST['task']) / -1))->num_rows != 1) {
                die("failed");
            }
        } else {
            if ($db->query("SELECT ID FROM project_tasks WHERE ID = " . intval($_POST['task']))->num_rows != 1) {
                die("failed");
            }
        }

        if ($db->query("SELECT ID FROM admins WHERE ID = " . intval($adminInfo->ID))->num_rows != 1) {
            die("failed");
        }

        $search = "0000-00-00 00:00:00";
        $end = date("Y-m-d H:i:s");
        alog("project", "times_stopped", $_POST['task']);
        if (!$db->query("UPDATE project_times SET `end` = '$end' WHERE `end` = '$search' AND `admin` = " . intval($adminInfo->ID) . " AND `task` = " . intval($_POST['task']) . " LIMIT 1") || $db->affected_rows != 1) {
            die("failed");
        }

        die("ok");
        break;

    case 'load_testimonial':
        if (!$ari->check(58)) {
            alog("general", "insufficient_page_rights", "testimonials");
            die("failed");
        }

        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            die("failed");
        }

        $testimonial = Testimonials::fetch($_POST['id']);
        echo json_encode(array("title" => htmlentities($testimonial->getSubject()), "text" => htmlentities($testimonial->getText())));
        alog("testimonials", "loaded", $_POST['id']);
        break;

    case 'load_testimonial_answer':
        if (!$ari->check(58)) {
            alog("general", "insufficient_page_rights", "testimonials");
            die("failed");
        }

        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            die("failed");
        }

        $testimonial = Testimonials::fetch($_POST['id']);
        ?>
        <form method="POST">
            <textarea name="answer" style="height: 150px; resize: vertical; margin-bottom: 10px;" class="form-control"><?=htmlentities($testimonial->getAnswer()); ?></textarea>
            <input type="submit" class="btn btn-primary btn-block" value="<?=$lang['TESTIMONIALS']['ANSWER']; ?>">
            <input type="hidden" name="id" value="<?=intval($_POST['id']); ?>">
        </form>
        <?php
        alog("testimonials", "answer", $_POST['id']);
        break;

    case 'online_status':
        if (!isset($_POST['status']) || !is_numeric($_POST['status'])) {
            die("missing");
        }

        $s = $_POST['status'] == "1" ? "1" : ($_POST['status'] == "2" ? "2" : "0");
        $db->query("UPDATE admins SET online = $s WHERE ID = " . $adminInfo->ID);

        $db->query("UPDATE admin_times SET `end` = '" . date("Y-m-d H:i:s") . "' WHERE `end` = '0000-00-00 00:00:00' AND `admin` = {$adminInfo->ID}");
        if ($s == "1") {
            $db->query("INSERT INTO admin_times (`admin`, `start`) VALUES ({$adminInfo->ID}, '" . date("Y-m-d H:i:s") . "')");
        }

        $addons->runHook("AdminOnlineStatusChanged");

        alog("general", "online_status_changed", $s == 1 ? "green" : ($s == 2 ? "orange" : "red"));
        die($s == 1 ? "green" : ($s == 2 ? "orange" : "red"));
        break;

    case 'invoice_details':
        if (!$ari->check(13)) {
            alog("general", "insufficient_page_rights", "invoice_details_ajax");
            die("failed");
        }

        if (!isset($_POST['invoiceid'])) {
            die(json_encode(array("An error occured", "Invalid AJAX call")));
        }

        $inv = new Invoice;
        if (!$inv->load($_POST['invoiceid'])) {
            die(json_encode(array("An error occured", "Invalid invoice number specified")));
        }

        $html = '<div class="table-responsive">';
        $html .= '<table class="table table-bordered table-striped" style="margin-bottom: 0;">';
        $html .= '<tr><th width="10%"></th><th width="60%">' . $lang['AJAX']['POSITION'] . '</th><th width="15%">' . $lang['AJAX']['NET'] . '</th><th width="15%">' . $lang['AJAX']['GROSS'] . '</th></tr>';

        foreach ($inv->getItems() as $i) {
            $qtyUnit = $nfo->format($i->getQty(), strlen(substr(strrchr($i->getQty(), "."), 1))) . " " . $i->getUnit();

            if (($articleName = $i->getDescription()) == "special_credit") {
                $articleName = $lang['INVOICE']['SPECIAL_CREDIT'];
            }

            $html .= '<tr><td>' . $qtyUnit . '<td>' . nl2br($articleName) . '</td><td><center>' . $cur->infix($nfo->format($i->getNet() * $i->getQty()), $cur->getBaseCurrency()) . '</center></td><td><center>' . $cur->infix($nfo->format($i->getGross() * $i->getQty()), $cur->getBaseCurrency()) . '</center></td></tr>';
        }

        $html .= '<tr><th>' . $lang['AJAX']['SUM'] . '</th><th></th><th><center>' . $cur->infix($nfo->format($inv->getNet()), $cur->getBaseCurrency()) . '</center></th><th><center>' . $cur->infix($nfo->format($inv->getGross()), $cur->getBaseCurrency()) . '</center></th></tr>';

        $html .= '</table>';
        $html .= '</div>';

        alog("invoices", "details_got", $_POST['invoiceid']);
        die(json_encode(array($inv->getInvoiceNo(), $html)));
        break;

    case 'external_newsletter_recipient_save':
        if (!$ari->check(21)) {
            alog("general", "insufficient_page_rights", "ajax_extnlrs");
            die("failed");
        }

        if (!isset($_POST['recipientid'])) {
            die(json_encode(array("An error occured", "Invalid AJAX call")));
        }

        $sql = $db->query("SELECT 1 FROM newsletter WHERE ID = " . intval($_POST['recipientid']));
        if ($sql->num_rows != 1) {
            die(json_encode(array("An error occured", "Invalid recipient number specified")));
        }

        $db->query("UPDATE newsletter SET lists = '" . $db->real_escape_string(implode("|", $_POST['nl'])) . "' WHERE ID = " . intval($_POST['recipientid']));

        alog("newsletter", "enrs");
        die("ok");
        break;

    case 'external_newsletter_recipient_details':
        if (!$ari->check(21)) {
            alog("general", "insufficient_page_rights", "ajax_extnlrd");
            die("failed");
        }

        if (!isset($_POST['recipientid'])) {
            die(json_encode(array("An error occured", "Invalid AJAX call")));
        }

        $sql = $db->query("SELECT * FROM newsletter WHERE ID = " . intval($_POST['recipientid']));
        if ($sql->num_rows != 1) {
            die(json_encode(array("An error occured", "Invalid recipient number specified")));
        }

        $info = $sql->fetch_object();

        $html = "<div class='row' style='margin-top: -10px;'>";

        $sql = $db->query("SELECT * FROM newsletter_categories ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            $html .= "<div class='col-md-4'>";
            $html .= '<div class="checkbox"><label>';
            $html .= '<input type="checkbox" value="' . $row->ID . '" class="checkfornl"' . (in_array($row->ID, explode("|", $info->lists)) ? ' checked=""' : '') . '>';
            $html .= htmlentities($row->name);
            $html .= '</label></div>';
            $html .= "</div>";
        }

        $html .= "</div>";

        $html .= '<input type="button" class="btn btn-primary btn-block" id="savenl" value="' . $lang['AJAX']['SAVE_ABOS'] . '">';

        $html .= '<script>
		$("#savenl").click(function(){
			var nl = [];
			$(".checkfornl").each(function(){
				if($(this).is(":checked"))
					nl.push($(this).val());
			});

			$.post("?p=ajax", {
				"action": "external_newsletter_recipient_save",
				"recipientid": "' . $info->ID . '",
				"nl": nl,
				"csrf_token": "' . CSRF::raw() . '",
			}, function(r){
				if(r == "ok")
					$("#ajaxModal").modal("hide");
			});
		});
		</script>';

        alog("newsletter", "enrd");
        die(json_encode(array(htmlentities($info->name), $html)));
        break;

    case 'load_telephone_log':
        if (!$ari->check(51)) {
            exit;
        }

        $prov = isset($_REQUEST['provider']) ? $_REQUEST['provider'] : "";
        $h = new TelephoneLogHandler;
        if (empty($prov) || !array_key_exists($prov, $h->get()) || !$h->get()[$prov]->isActive()) {
            exit;
        }

        $calls = $h->get()[$prov]->getLogs();

        foreach ($calls as &$c) {
            $c['start'] = $dfo->format($c['start'], true, true);
            $c['end'] = $dfo->format($c['end'], true, true);
        }

        echo json_encode($calls);
        alog("telephone_log", "got", $_REQUEST['provider']);
        break;

    case 'send_sms':
        if (!$ari->check(7)) {
            exit;
        }

        if (!SMSHandler::getDriver()) {
            exit;
        }

        $d = SMSHandler::getDriver();
        if (!array_key_exists($_POST['t'], $d->getTypes())) {
            die($lang['AJAX']['INVALID_SMS_TYPE']);
        }

        if (empty($_POST['n'])) {
            die($lang['AJAX']['INVALID_SMS_NUMBER']);
        }

        if (empty($_POST['m'])) {
            die($lang['AJAX']['INVALID_SMS_MESSAGE']);
        }

        $r = $d->sendMessage($_POST['n'], $_POST['m'], $_POST['t']);
        if ($r !== true) {
            alog("sms", "failed", $_POST['n'], $_POST['m'], $_POST['t']);
            die($lang['AJAX']['INVALID_SMS_STATE'] . (!empty($r) ? ": " . $r : ""));
        }
        alog("sms", "sent", $_POST['n'], $_POST['m'], $_POST['t']);
        die("ok");

        break;

    case 'get_tags':
        if (!$ari->check(24)) {
            exit;
        }

        $r = GitLab::getTags($_GET['id']);
        alog("git", "tags_got", $_GET['id']);

        if (count($r) == 0) {
            die("<div class='alert alert-warning'>" . $lang['AJAX']['NO_TAGS'] . "</div>");
        }

        $html = '<select name="gltag" class="form-control">';
        foreach ($r as $t => $null) {
            $html .= '<option>' . $t . '</option>';
        }

        die($html . "</select>");
        break;

    case 'telegram_test':
        if (!$ari->check(34)) {
            exit;
        }

        alog("telegram", "test_message_submitted");
        $CFG['TELEGRAM_CHAT'] = $_POST['chat'];
        $CFG['TELEGRAM_TOKEN'] = $_POST['token'];
        die(Telegram::sendMessage("Test <a href=\"{$CFG['PAGEURL']}\">Link</a>") ? $lang['AJAX']['TEST_SUC'] : $lang['AJAX']['TEST_FAIL']);
        break;

    case 'refresh_balance':
        if (!$ari->check(33)) {
            exit;
        }

        $currentAccount = intval($_GET['id']);
        alog("transfer", "refresh_balance", $currentAccount);
        require __DIR__ . "/../../controller/crons/transfer_import.php";
        exit;
        break;

    case 'fibu_accounts':
        if (!$ari->check(40)) {
            exit;
        }

        $searchword = $db->real_escape_string(trim($_REQUEST['searchword']));
        $sql = $db->query("SELECT ID, description FROM fibu_accounts WHERE ID = '$searchword' OR description LIKE '%$searchword%' ORDER BY ID = '$searchword' DESC, ID ASC LIMIT 10");

        if ($sql->num_rows) {
            echo "<ul class='acctlist'>";

            while ($row = $sql->fetch_object()) {
                echo '<li>' . $row->ID . ' ' . htmlentities($row->description) . '</li>';
            }

            echo "</ul>";
        }
        break;

    case 'fibu_saldo':
        if (!$ari->check(40)) {
            exit;
        }

        $account = intval(trim($_REQUEST['account']));
        $obj = \Fibu\Account::getInstance($account, false);

        if (!$obj) {
            die("-");
        }

        $saldo = $obj->getSaldo();
        $sh = "H";

        if ($saldo < 0) {
            $saldo = abs($saldo);
            $sh = "S";
        }

        echo $nfo->format($saldo) . " " . $sh;
        break;

    default:
        echo "action unknown";
}

exit;
