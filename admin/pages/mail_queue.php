<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['MAIL_QUEUE'];
title($l['TITLE']);
menu("settings");

if (!$ari->check(47)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "mail_queue");} else {

    $db->query("UPDATE email_templates SET category = 'Kunde' WHERE category = 'Abuse report update'");

    if (isset($_POST['mail_test'])) {
        parse_str($_POST['form_data'] ?? "", $data);

        try {
            $mail = new PHPMailer(true);

            $mail->IsSMTP();
            $mail->Host = $data['smtp_host'] ?? "";

            if (!empty($data['smtp_user']) || !empty($data['smtp_password'])) {
                $mail->SMTPAuth = true;
                $mail->Username = $data['smtp_user'] ?? "";
                $mail->Password = $data['smtp_password'] ?? "";
            }

            if (in_array($data['smtp_security'] ?? "", ["tls", "ssl"])) {
                $mail->SMTPSecure = $data['smtp_security'];
            }

            try {
                if (!$mail->SmtpConnect()) {
                    throw new Exception("");
                }

                die($l['CONNOK']);
            } catch (Exception $ex) {
                die($l['CONNFAIL']);
            }
        } catch (Exception $err) {
            die($err->getMessage());
        }
    }

    $tab = isset($_GET['tab']) ? $_GET['tab'] : "templates";
    ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$tab == "templates" ? " active" : "";?>" href="./?p=mail_queue"><?=$l['TEMPLATES'];?></a>
					<a class="list-group-item<?=$tab == "queue" ? " active" : "";?>" href="./?p=mail_queue&tab=queue"><?=$l['QUEUE'];?></a>
					<a class="list-group-item<?=$tab == "archive" ? " active" : "";?>" href="./?p=mail_queue&tab=archive"><?=$l['ARCHIVE'];?></a>
					<a class="list-group-item<?=$tab == "settings" ? " active" : "";?>" href="./?p=mail_queue&tab=settings"><?=$l['SETTINGS'];?></a>
				</div>
			</div>

			<div class="col-md-9">

			<?php if ($tab == "templates") {
        if (isset($_GET['activate']) || isset($_GET['deactivate'])) {
            $id = intval(isset($_GET['activate']) ? $_GET['activate'] : $_GET['deactivate']);
            $active = isset($_GET['activate']) ? 1 : 0;

            if ($db->query("UPDATE email_templates SET active = $active WHERE ID = $id") && $db->affected_rows) {
                echo '<div class="alert alert-success">' . $l[(!$active ? 'DE' : '') . 'ACTIVATED'] . '</div>';
            }
        }
        ?>

			<ul class="nav nav-tabs" role="tablist">
			    <?php
$i = 0;
        $sql = $db->query("SELECT category FROM email_templates WHERE category != 'Eigene' GROUP BY category ORDER BY category ASC");
        while ($row = $sql->fetch_object()) {
            ?>
			    <li<?=$i == 0 && !isset($_POST['new_name']) && empty($_GET['del']) ? ' class="active"' : '';?>><a href="#cat_<?=$i;?>" data-toggle="tab"><?=$row->category;?></a></li>
			    <?php $i++;}?>
			    <li<?=$i == 0 || isset($_POST['new_name']) || !empty($_GET['del']) ? ' class="active"' : '';?>><a href="#cat_<?=$i;?>" data-toggle="tab"><?=$l['OWN'];?></a></li>
			</ul>

			<br />
			<div class="tab-content">
				<?php
$i = 0;
        $sql = $db->query("SELECT category FROM email_templates WHERE category != 'Eigene' GROUP BY category ORDER BY category ASC");
        while ($row = $sql->fetch_object()) {
            ?>
			    <div role="tabpanel" class="tab-pane<?=$i == 0 && !isset($_POST['new_name']) && empty($_GET['del']) ? ' active' : '';?>" id="cat_<?=$i;?>">

				    <div class="table table-responsive">
		                <table class="table table-bordered table-striped">
		                    <tr>
		                        <th><?=$l['TEMPLATE'];?></th>
		                        <th width="20%"></th>
		                    </tr>

		                    <?php $sql2 = $db->query("SELECT * FROM email_templates WHERE category = '{$row->category}' ORDER BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '<b>Admin', '<b>XXX'), 'Footer', 'aaaab'), 'Header', 'aaaaa'), '<b>', 'zzzzz'), 'Newsletter-Disclaimer', 'aaaac') ASC");

            while ($r = $sql2->fetch_object()) {?>

		                    <tr>
		                        <td><?=$lang['ISOCODE'] != 'de' && $r->foreign_name ? $r->foreign_name : $r->name;?></td>
                                <td><a href="<?="?p=mail_queue&" . ($r->active ? 'de' : '') . "activate=" . $r->ID;?>" class="btn btn-<?=$r->active ? 'warning' : 'success';?> btn-xs"><?=$l[($r->active ? 'DE' : '') . 'ACTIVATE'];?></a> <a href="<?="?p=edit_mail_template&id=" . $r->ID;?>" class="btn btn-primary btn-xs"><?=$l['EDIT'];?></a></td>
		                    </tr>

		                    <?php }?>
		                </table>
		            </div>

			    </div>
			    <?php $i++;}?>

			    <div role="tabpanel" class="tab-pane<?=$i == 0 || isset($_POST['new_name']) || !empty($_GET['del']) ? ' active' : '';?>" id="cat_<?=$i;?>">

			    	<?php
if (isset($_POST['new_name'])) {
            if (empty($_POST['new_name'])) {
                echo '<div class="alert alert-danger">' . $l['ERR1'] . '</div>';
            } else {
                if ($db->query("SELECT 1 FROM email_templates WHERE name = '" . $db->real_escape_string($_POST['new_name']) . "'")->num_rows > 0) {
                    echo '<div class="alert alert-danger">' . $l['ERR2'] . '</div>';
                } else {
                    $db->query("INSERT INTO email_templates (`name`, `category`) VALUES ('" . $db->real_escape_string($_POST['new_name']) . "', 'Eigene')");
                    echo '<div class="alert alert-success">' . $l['SUC1'] . '</div>';
                    alog("general", "mail_template_created", $_POST['new_name'], $db->insert_id);
                    unset($_POST['new_name']);
                }
            }
        }

        if (isset($_GET['del']) && is_numeric($_GET['del']) && $db->query("DELETE FROM email_templates WHERE ID = " . intval($_GET['del'])) && $db->affected_rows > 0) {
            echo '<div class="alert alert-success">' . $l['SUC2'] . '</div>';
            alog("general", "mail_template_deleted", $_GET['del']);
        }
        ?>

			    	<form method="POST" class="form-inline">
			    		<input type="text" class="form-control" name="new_name" placeholder="<?=$l['TEMPLATENAME'];?>" value="<?=isset($_POST['new_name']) ? htmlentities($_POST['new_name']) : "";?>">
			    		<input type="submit" class="btn btn-primary" value="<?=$l['CREATE'];?>">
			    	</form><br />

				    <div class="table table-responsive">
		                <table class="table table-bordered table-striped">
		                    <tr>
		                        <th><?=$l['TEMPLATE'];?></th>
		                        <th width="30px"></th>
		                        <th width="30px"></th>
		                    </tr>

		                    <?php $sql2 = $db->query("SELECT * FROM email_templates WHERE category = 'Eigene' ORDER BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '<b>Admin', '<b>XXX'), 'Footer', 'aaaab'), 'Header', 'aaaaa'), '<b>', 'zzzzz'), 'Newsletter-Disclaimer', 'aaaac') ASC");

        if ($sql2->num_rows > 0) {
            while ($r = $sql2->fetch_object()) {?>

		                    <tr>
		                        <td><?=$lang['ISOCODE'] != 'de' && $r->foreign_name ? $r->foreign_name : $r->name;?></td>
		                        <td><a href="<?="?p=edit_mail_template&id=" . $r->ID;?>"><i class="fa fa-edit"></i></a></td>
		                        <td><a href="<?="?p=mail_queue&del=" . $r->ID;?>"><i class="fa fa-times fa-lg"></i></a></td>
		                    </tr>

		                    <?php }} else {?>
		                    <tr>
		                    	<td colspan="3"><center><?=$l['NOT'];?></center></td>
		                    </tr>
		                    <?php }?>
		                </table>
		            </div>

			    </div>
		  	</div>

            <?php } else if ($tab == "queue") {?>

			<div class="alert alert-info"><?=$l['CRONJOBHINT'];?></div>

			<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $db->query("DELETE FROM  client_mails WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' AND sent = 0 LIMIT 1");
        if ($db->affected_rows > 0) {
            echo '<div class="alert alert-success">' . $l['SUC3'] . '</div>';
            alog("general", "mail_deleted", $_GET['delete']);
        }
    } else if (isset($_GET['delete']) && $_GET['delete'] == "all") {
        $db->query("DELETE FROM  client_mails WHERE sent = 0");
        if ($db->affected_rows > 0) {
            echo '<div class="alert alert-success">' . $l['SUC4'] . '</div>';
            alog("general", "unsent_mails_deleted");
        }
    }

        if (isset($_POST['delete_selected']) && is_array($_POST['mail'])) {
            $d == 0;
            foreach ($_POST['mail'] as $id) {
                $db->query("DELETE FROM  client_mails WHERE ID = '" . $db->real_escape_string($id) . "' AND sent = 0 LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("general", "mail_deleted", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['OK1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['OK2']) . '</div>';
            }

        }

        if (isset($_GET['send']) && is_numeric($_GET['send'])) {
            if ($maq->send(1, $_GET['send'])) {
                echo '<div class="alert alert-success">' . $l['OK3'] . '</div>';
                alog("general", "mail_sent", $_GET['send']);
            }
        } else if (isset($_GET['send']) && $_GET['send'] == "all") {
            if ($maq->send($db->query("SELECT ID FROM client_mails WHERE sent = 0")->num_rows, 0, true)) {
                echo '<div class="alert alert-success">' . $l['OK4'] . '</div>';
                alog("general", "unsent_mails_sent");
            }
        }

        if (isset($_POST['send_selected']) && is_array($_POST['mail'])) {
            $d == 0;
            foreach ($_POST['mail'] as $id) {
                if ($maq->send(1, $id)) {
                    $d++;
                    alog("general", "mail_sent", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['OK5'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['OK6']) . '</div>';
            }

        }

        $table = new Table("SELECT * FROM client_mails WHERE sent = 0", [
            "subject" => [
                "name" => $l['SUBJECT'],
                "type" => "like",
            ],
            "recipient" => [
                "name" => $l['RECIPM'],
                "type" => "like",
            ],
        ], ["time", "DESC"], "mail_queue");

        echo $table->getHeader();
        ?>

			<div class="table table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th><?=$table->orderHeader("time", $l['DATE']);?></th>
					<th><?=$l['RECIPIENT'];?></th>
					<th><?=$table->orderHeader("subject", $l['SUBJECT']);?></th>
					<th width="54px"><a href="?p=mail_queue&tab=queue&send=all" onclick="return confirm('<?=$l['RSA'];?>');"><i class="fa fa-send"></i></a>&nbsp;&nbsp;<a href="?p=mail_queue&tab=queue&delete=all" onclick="return confirm('<?=$l['RDA'];?>');"><i class="fa fa-times fa-lg"></i></a></th>
				</tr>

				<form method="POST" action="?p=mail_queue&tab=queue">
				<?php
$sql = $table->qry("time DESC");
        if ($sql->num_rows <= 0) {?>
				<tr>
					<td colspan="5"><center><?=$l['NMIQ'];?></center></td>
				</tr>
				<?php } else {while ($r = $sql->fetch_object()) {

            if ($r->user != 0) {
                $sql2 = $db->query("SELECT ID FROM clients WHERE ID = " . $r->user);
                if ($sql2->num_rows == 1) {
                    $cusInfo = $sql2->fetch_object();
                }

                if (!isset($cusInfo)) {
                    $r->user = 0;
                } else {
                    $link = "<a href=\"?p=customers&edit=" . $r->user . "\">" . User::getInstance($cusInfo->ID, "ID")->getfName() . "</a>";
                }

            }

            ?>
				<tr>
					<td><input type="checkbox" class="checkbox" name="mail[]" value="<?=$r->ID;?>" onchange="javascript:toggle();" /></td>
					<td><?=$r->time > time() ? '<i class="fa fa-clock-o"></i>' : '';?> <?=$dfo->format($r->time);?></td>
					<td><?=$r->user == 0 ? $r->recipient : $link;?></td>
					<td><a href="<?=$raw_cfg['PAGEURL'];?>email/<?=$r->ID;?>/<?=substr(hash("sha512", "email_view" . $r->ID . $CFG['HASH']), 0, 10);?>" target="_blank"><?=htmlentities($r->subject);?></a> <?php if ($r->resend == 1) {?><i class="fa fa-undo"></i> <?php }?><?php if ($r->seen == 1) {?><i class="fa fa-eye"></i><?php }?></td>
					<td><a href="?p=mail_queue&tab=queue&send=<?=$r->ID;?>" onclick="return confirm('<?=$l['RSAM'];?>');"><i class="fa fa-send"></i></a>&nbsp;&nbsp;<a onclick="return confirm('<?=$l['RDAM'];?>');" href="?p=mail_queue&tab=queue&delete=<?=$r->ID;?>"><i class="fa fa-times fa-lg"></i></a></td>
				</tr>
				<?php }}?>
			</table>
			</div><?=$l['SELECTED'];?>: <input type="submit" name="send_selected" value="<?=$l['SENDNOW'];?>" class="btn btn-success" /> <input type="submit" name="delete_selected" value="<?=$l['DELETENOW'];?>" class="btn btn-danger" /></form>

			<?=$table->getFooter();?>
		 <?php } else if ($tab == "archive") {?>
			<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $db->query("DELETE FROM  client_mails WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' AND sent > 0 LIMIT 1");
        if ($db->affected_rows > 0) {
            echo '<div class="alert alert-success">' . $l['OK7'] . '</div>';
            alog("general", "mail_deleted", $_GET['delete']);
        }
    } else if (isset($_GET['delete']) && $_GET['delete'] == "all") {
        $db->query("DELETE FROM  client_mails WHERE sent > 0");
        if ($db->affected_rows > 0) {
            echo '<div class="alert alert-success">' . $l['OK8'] . '</div>';
            alog("general", "all_sent_mails_deleted");
        }
    }

        if (isset($_POST['delete_selected']) && is_array($_POST['mail'])) {
            $d == 0;
            foreach ($_POST['mail'] as $id) {
                $db->query("DELETE FROM  client_mails WHERE ID = '" . $db->real_escape_string($id) . "' AND sent > 0 LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("general", "mail_deleted", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['OK9'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['OK10']) . '</div>';
            }

        }

        if (isset($_GET['resend']) && is_numeric($_GET['resend'])) {
            if ($maq->resend($_GET['resend'])) {
                echo '<div class="alert alert-success">' . $l['OK11'] . '</div>';
                alog("general", "mail_resend", $_GET['resend']);
            }
        }

        if (isset($_POST['resend_selected']) && is_array($_POST['mail'])) {
            $d == 0;
            foreach ($_POST['mail'] as $id) {
                if ($maq->resend($id)) {
                    $d++;
                    alog("general", "mail_resend", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['OK12'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['OK13']) . '</div>';
            }

        }

        $table = new Table("SELECT * FROM client_mails WHERE sent > 0", [
            "subject" => [
                "name" => $l['SUBJECT'],
                "type" => "like",
            ],
            "recipient" => [
                "name" => $l['RECIPM'],
                "type" => "like",
            ],
        ], ["time", "DESC"], "mail_archive");

        echo $table->getHeader();
        ?>

			<div class="table table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th><?=$table->orderHeader("time", $l['DATE']);?></th>
					<th><?=$l['RECIPIENT'];?></th>
					<th><?=$table->orderHeader("subject", $l['SUBJECT']);?></th>
					<th width="52px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="?p=mail_queue&tab=archive&delete=all" onclick="return confirm('<?=$l['RDA'];?>');"><i class="fa fa-times fa-lg"></i></a></th>
				</tr>

				<form method="POST">
				<?php
$sql = $table->qry("time DESC");
        if ($sql->num_rows <= 0) {?>
				<tr>
					<td colspan="5"><center><?=$l['NMIA'];?></center></td>
				</tr>
				<?php } else {while ($r = $sql->fetch_object()) {

            if ($r->user != 0) {
                $sql2 = $db->query("SELECT ID FROM clients WHERE ID = " . $r->user);
                if ($sql2->num_rows == 1) {
                    $cusInfo = $sql2->fetch_object();
                }

                if (!isset($cusInfo)) {
                    $r->user = 0;
                } else {
                    $link = "<a href=\"?p=customers&edit=" . $r->user . "\">" . User::getInstance($cusInfo->ID, "ID")->getfName() . "</a>";
                }

            }

            ?>
				<tr>
					<td><input type="checkbox" class="checkbox" name="mail[]" value="<?=$r->ID;?>" onchange="javascript:toggle();" /></td>
					<td><?=$dfo->format($r->time);?></td>
					<td><?=$r->user == 0 ? $r->recipient : $link;?></td>
					<td><a href="<?=$raw_cfg['PAGEURL'];?>email/<?=$r->ID;?>/<?=substr(hash("sha512", "email_view" . $r->ID . $CFG['HASH']), 0, 10);?>" target="_blank"><?=htmlentities($r->subject);?></a> <?php if ($r->resend == 1) {?><i class="fa fa-undo"></i> <?php }?><?php if ($r->seen == 1) {?><i class="fa fa-eye"></i><?php }?></td>
					<td><a href="?p=mail_queue&tab=archive&resend=<?=$r->ID;?>&page=<?=$page;?>" onclick="return confirm('<?=$l['RSAMA'];?>');"><i class="fa fa-undo"></i></a>&nbsp;&nbsp;<a onclick="return confirm('<?=$l['RDAM'];?>');" href="?p=mail_queue&tab=archive&delete=<?=$r->ID;?>&page=<?=$page;?>"><i class="fa fa-times fa-lg"></i></a></td>
				</tr>
				<?php }}?>
			</table>
			</div><?=$l['SELECTED'];?>: <input type="submit" name="resend_selected" value="<?=$l['SENDANOW'];?>" class="btn btn-warning" /> <input type="submit" name="delete_selected" value="<?=$l['DELETENOW'];?>" class="btn btn-danger" /></form>

			<?=$table->getFooter();?>
		<?php } else if ($tab == "settings") {

        if (isset($_POST['save'])) {
            $update = array("mail_type", "mail_sender", "smtp_host", "smtp_password", "smtp_user", "mail_leadtime", "mailqueue_auto", "smtp_security", "ses_id", "ses_secret", "log_support_mails");
            if (!isset($_POST['mail_active']) || $_POST['mail_active'] != 1) {
                $active = 0;
            } else {
                $active = 1;
            }

            if (!isset($_POST['mailqueue_auto'])) {
                $_POST['mailqueue_auto'] = 0;
            }

            if (!isset($_POST['log_support_mails'])) {
                $_POST['log_support_mails'] = 0;
            }

            if (isset($_POST['smtp_password'])) {
                $_POST['smtp_password'] = encrypt($_POST['smtp_password']);
            }

            if (isset($_POST['ses_secret'])) {
                $_POST['ses_secret'] = encrypt($_POST['ses_secret']);
            }

            foreach ($_POST as $k => $v) {
                if (in_array($k, $update)) {
                    $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($v) . "' WHERE `key` = '" . $db->real_escape_string($k) . "' LIMIT 1");
                }
            }

            $db->query("UPDATE cronjobs SET `active` = $active WHERE `key` = 'queue'");

            echo '<div class="alert alert-success">' . $l['SETSAV'] . '</div>';

            alog("general", "mail_settings_saved");

            unset($_POST);

            $cfg_sql = $db->query("SELECT * FROM settings");
            while ($c = $cfg_sql->fetch_object()) {
                $CFG[strtoupper($c->key)] = $c->value;
                if ($c->key == "ses_secret" || $c->key == "smtp_password") {
                    $CFG[strtoupper($c->key)] = decrypt($CFG[strtoupper($c->key)]);
                }

            }
        }

        $active = (bool) $db->query("SELECT ID FROM cronjobs WHERE `active` = 1 AND `key` = 'queue' LIMIT 1")->num_rows;
        ?>
<form role="form" method="POST" id="mail_form">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

        <div class="form-group">
            <label class="control-label"><?=$l['MAIL_SENDER'];?></label>
            <input type="text" name="mail_sender" value="<?=htmlentities($CFG['MAIL_SENDER']);?>" placeholder="noreply@example.com" class="form-control">
          </div>

          <div class="form-group">
            <label class="control-label"><?=$l['MTYPE'];?></label>
            <select name="mail_type" class="form-control">
                <option value="mail"><?=$l['T1'];?></option>
                <option value="smtp" <?=$CFG['MAIL_TYPE'] == "smtp" ? 'selected="selected"' : '';?>><?=$l['T2'];?></option>
                <option value="ses" <?=$CFG['MAIL_TYPE'] == "ses" ? 'selected="selected"' : '';?>><?=$l['T3'];?></option>
			</select>
          </div>

          <style>
          .mail-option {
              display: none;
          }

          .mail-option.<?=$CFG['MAIL_TYPE'];?> {
              display: block;
          }
          </style>

          <script>
          $("[name=mail_type]").change(function() {
            $(".mail-option").hide();
            $(".mail-option." + $(this).val()).show();

            $("#test_mail").attr("disabled", $(this).val() != "smtp");
          });
          </script>

		  <div class="form-group mail-option smtp">
			<label><?=$l['SHOST'];?></label>
			<input type="text" class="form-control" name="smtp_host" value="<?=$CFG['SMTP_HOST'];?>" placeholder="<?=$l['SHOSTP'];?>">
		  </div>

		  <div class="form-group mail-option smtp">
			<label><?=$l['SMTPU'];?></label>
			<input type="text" class="form-control" name="smtp_user" value="<?=$CFG['SMTP_USER'];?>" placeholder="<?=$l['SMTPUP'];?>">
		  </div>

		  <div class="form-group mail-option smtp">
			<label><?=$l['SMTPP'];?></label>
			<input type="password" class="form-control" name="smtp_password" value="<?=$CFG['SMTP_PASSWORD'];?>" placeholder="<?=$l['SMTPPP'];?>">
		  </div>

		  <div class="form-group mail-option smtp">
            <label class="control-label"><?=$l['SMTPE'];?></label>
            <select name="smtp_security" class="form-control">
			<option value="no"><?=$l['E1'];?></option>
			<option value="ssl" <?=$CFG['SMTP_SECURITY'] == "ssl" ? 'selected="selected"' : '';?>><?=$l['E2'];?></option>
			<option value="tls" <?=$CFG['SMTP_SECURITY'] == "tls" ? 'selected="selected"' : '';?>><?=$l['E3'];?></option>
			</select>
          </div>

          <div class="form-group mail-option ses">
          	<label><?=$l['ASESID'];?></label>
			<input type="text" class="form-control" name="ses_id" value="<?=$CFG['SES_ID'];?>" placeholder="<?=$l['ASESIDP'];?>">
		  </div>

		  <div class="form-group mail-option ses">
			<label><?=$l['ASESKEY'];?></label>
			<input type="password" class="form-control" name="ses_secret" value="<?=$CFG['SES_SECRET'];?>" placeholder="<?=$l['ASESIDP'];?>">
		  </div>

		   <div class="checkbox">
			<label>
			  <input type="checkbox" value="1" name="mail_active" <?=$active ? "checked" : "";?>> <?=$l['CJA'];?>
			</label>
			<p class="help-block"><?=$l['CJAP'];?></p>
		  </div>

		  <div class="checkbox" style="margin-top: 15px;">
			<label>
			  <input type="checkbox" value="1" name="mailqueue_auto" <?=$CFG['MAILQUEUE_AUTO'] ? "checked" : "";?>> <?=$l['MQA'];?>
			</label>
			<p class="help-block"><?=$l['MQAP'];?></p>
		  </div>

			<div class="checkbox" style="margin-top: 15px;">
			<label>
			  <input type="checkbox" value="1" name="log_support_mails" <?=$CFG['LOG_SUPPORT_MAILS'] ? "checked" : "";?>> <?=$l['LSM'];?>
			</label>
			<p class="help-block"><?=$l['LSMP'];?></p>
		  </div>

		  <div class="form-group">
			<label><?=$l['MLT'];?></label>
			<input type="text" class="form-control" name="mail_leadtime" value="<?=$CFG['MAIL_LEADTIME'];?>" placeholder="<?=$l['MLTP'];?>">
			<p class="help-block"><?=$l['MLTH'];?></p>
		  </div>

		  <div class="row">
            <div class="col-md-6">
                <input type="submit" name="save" class="btn btn-primary btn-block" value="<?=$l['SETSAVENOW'];?>">
            </div>
            <div class="col-md-6">
                <a href="#" id="test_mail" class="btn btn-default btn-block"<?=$CFG['MAIL_TYPE'] == "smtp" ? "" : ' disabled=""';?>><?=$l['TESTNOW'];?></a>
            </div>
          </div>
        </form>

        <script>
        var doingTest = 0;

        $("#test_mail").click(function(e) {
            e.preventDefault();

            if (doingTest) {
                return;
            }
            doingTest = 1;

            $("#page-wrapper").block();

            $.post("", {
                "csrf_token": "<?=CSRF::raw();?>",
                "mail_test": "1",
                "form_data": $("#mail_form").serialize()
            }, function(r) {
                $("#page-wrapper").unblock();
                alert(r);
                doingTest = 0;
            });
        });
        </script>
		<?php } else {echo '<div class="alert alert-danger">' . $l['PNF'] . '</div>';}?>
<?php }?>
