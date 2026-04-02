<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SUPPORT_CONFIG'];

title($l['TITLE']);
menu("support");

if (!$ari->check(61)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "support_config");} else {

    $t = isset($_GET['t']) ? $_GET['t'] : "dept";

    if ($t == "email" && isset($_GET['check']) && $_GET['check'] == "pop") {
        $imap = imap_open("{" . $_POST['host'] . ":" . $_POST['port'] . "/pop3" . ($_POST['ssl'] ? "/ssl/novalidate-cert" : "") . "}INBOX", $_POST['user'], $_POST['password']);
        if ($imap === false) {
            die($l['CONFAIL']);
        }

        die($l['CONSUC']);
    }

    if ($t == "email" && isset($_GET['check']) && $_GET['check'] == "smtp") {
        $mail = new PHPMailer(true);
        $mail->SMTPAuth = true;
        $mail->Username = $_POST['user'];
        $mail->Password = $_POST['password'];
        $mail->Host = $_POST['host'];
        $mail->Port = $_POST['port'];
        if ($_POST['ssl']) {
            $mail->SMTPSecure = 'ssl';
        }

        if (!$mail->SmtpConnect()) {
            die($l['CONFAIL']);
        }

        die($l['CONSUC']);
    }
    ?>

<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE2'];?></h1>

		<?php if (!function_exists("mailparse_msg_get_part")) {?>
		<div class="alert alert-warning"><?=$l['MAILPARSE'];?></div>
		<?php }?>

		<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$t == "dept" ? " active" : "";?>" href="./?p=support_config&t=dept"><?=$l['T1'];?></a>
					<a class="list-group-item<?=$t == "email" ? " active" : "";?>" href="./?p=support_config&t=email"><?=$l['T2'];?></a>
					<a class="list-group-item<?=$t == "staff" ? " active" : "";?>" href="./?p=support_config&t=staff"><?=$l['T3'];?></a>
					<a class="list-group-item<?=$t == "attachments" ? " active" : "";?>" href="./?p=support_config&t=attachments"><?=$l['T4'];?></a>
				</div>

				<div class="list-group">
					<a class="list-group-item<?=$t == "categories" ? " active" : "";?>" href="./?p=support_config&t=categories"><?=$l['T5'];?></a>
					<a class="list-group-item<?=$t == "answer" ? " active" : "";?>" href="./?p=support_config&t=answer"><?=$l['T6'];?></a>
				</div>

				<div class="list-group">
					<a class="list-group-item<?=$t == "signatures" ? " active" : "";?>" href="./?p=support_config&t=signatures"><?=$l['T7'];?></a>
				</div>

				<div class="list-group">
					<a class="list-group-item<?=$t == "filter" ? " active" : "";?>" href="./?p=support_config&t=filter"><?=$l['T8'];?></a>
					<a class="list-group-item<?=$t == "automation" ? " active" : "";?>" href="./?p=support_config&t=automation"><?=$l['T9'];?></a>
					<a class="list-group-item<?=$t == "escalation" ? " active" : "";?>" href="./?p=support_config&t=escalation"><?=$l['T10'];?></a>
					<a class="list-group-item<?=$t == "upgrades" ? " active" : "";?>" href="./?p=support_config&t=upgrades"><?=$l['T11'];?></a>
				</div>
			</div>

			<div class="col-md-9">
				<?php if ($t == "dept") {?>

				<?php
if (isset($_POST['name'])) {
        $db->query("INSERT INTO support_departments (name) VALUES ('" . $db->real_escape_string($_POST['name']) . "')");
        alog("support", "dept_created", $_POST['name'], $db->insert_id);
    }
        if (isset($_GET['d']) && is_numeric($_GET['d']) && $db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE dept = " . intval($_GET['d']))->fetch_object()->c == 0) {
            $db->query("DELETE FROM support_departments WHERE ID = " . intval($_GET['d']));
            alog("support", "dept_del", $_GET['d']);
        }
        if (isset($_GET['public']) && is_numeric($_GET['public'])) {
            $db->query("UPDATE support_departments SET public = 1 WHERE ID = " . intval($_GET['public']));
            alog("support", "dept_public", $_GET['public']);
        }
        if (isset($_GET['unpublic']) && is_numeric($_GET['unpublic'])) {
            $db->query("UPDATE support_departments SET public = 0 WHERE ID = " . intval($_GET['unpublic']));
            alog("support", "dept_unpublic", $_GET['unpublic']);
        }

        if (isset($_POST['confval']) && isset($_POST['confdept'])) {
            $db->query("UPDATE support_departments SET confirmation = " . intval($_POST['confval']) . " WHERE ID = " . intval($_POST['confdept']));
            alog("support", "dept_conf", intval($_POST['confdept']), intval($_POST['confval']));
        }
        ?>

				<form method="POST" class="form-inline">
					<input type="text" name="name" placeholder="<?=$l['DEPTNAMEP'];?>" class="form-control" />
					<input type="submit" value="<?=$l['DEPTCREA'];?>" class="btn btn-primary" />
				</form><br />

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="65%"><?=$l['DEPT'];?></th>
							<th width="15%"><center><?=$l['DEPTCONF'];?></center></th>
							<th width="15%"><center><?=$l['DEPTPUB'];?></center></th>
							<th><center><?=$l['DEPTCO'];?></center></th>
							<th><center><?=$l['DEPTST'];?></center></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="6"><center><?=$l['DEPTNT'];?></center></td>
						</tr>
						<?php }
        $sum = 0;while ($row = $sql->fetch_object()) {$count = $db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE dept = " . $row->ID)->fetch_object()->c;
            $sum += $count;?>
						<tr>
							<td><?=$row->name;?></td>
							<td><center><a href="#" data-toggle="modal" data-target="#changeConfirmation" onclick="$('[name=confdept]').val(<?=$row->ID;?>);"><?php
$field = $lang['ISOCODE'] != 'de' ? 'foreign_name' : 'name';
            $cetSql = $db->query("SELECT `$field` FROM email_templates WHERE ID = {$row->confirmation} AND category = 'Eigene'");
            if ($cetSql->num_rows) {
                echo htmlentities($cetSql->fetch_object()->$field);
            } else {
                echo $l['DEPTCONFNO'];
            }
            ?></a></center></td>
							<td><center><a href="?p=support_config&<?=$row->public ? 'un' : '';?>public=<?=$row->ID;?>"><?=$row->public ? $l['YES'] : $l['NO'];?></a></center></td>
							<td><center><?=$count;?></center></td>
							<td><center><?=$db->query("SELECT COUNT(*) AS c FROM support_department_staff WHERE dept = " . intval($row->ID))->fetch_object()->c;?></center></td>
							<td><?php if ($count == 0) {?><a href="?p=support_config&t=dept&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a><?php }?></td>
						</tr>
						<?php }?>

						<tr>
							<th colspan="3" style="text-align: right;"><?=$l['SUM'];?></th>
							<th><center><?=$sum;?></center></th>
							<th colspan="2"></th>
						</tr>
					</table>
				</div>

				<div class="modal fade" id="changeConfirmation" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-body">
								<form method="POST">
									<input type="hidden" name="confdept" value="0">
									<div class="form-group" style="margin-bottom: -15px;">
										<select name="confval" class="form-control" onchange="form.submit();">
											<option value="" selected="" disabled=""><?=$l['DCMPC'];?></option>
											<option value="0"><?=$l['DCMNC'];?></option>
											<?php
$field = $lang['ISOCODE'] != 'de' ? 'foreign_name' : 'name';
        $cetSql = $db->query("SELECT `$field`, ID FROM email_templates WHERE category = 'Eigene' ORDER BY `$field` ASC");
        while ($row = $cetSql->fetch_object()) {
            echo '<option value="' . $row->ID . '">' . htmlentities($row->$field) . '</option>';
        }
        ?>
										</select>
										<span class="help-block"><?=$l['DCMVAR'];?>: %ticket_id%, %ticket_subject%, %ticket_department%</span>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>

				<?php } else if ($t == "email") {?>

				<?php
if (!empty($_POST['email']) && !empty($_POST['dept'])) {
        $depts = array();
        $sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            array_push($depts, $row->ID);
        }

        $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            array_push($depts, $row->ID / -1);
        }

        if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) && in_array($_POST['dept'], $depts)) {
            $db->query("INSERT INTO support_email (email, dept) VALUES ('" . $db->real_escape_string($_POST['email']) . "', " . intval($_POST['dept']) . ")");
            alog("support", "email_add", $_POST['email'], $_POST['dept'], $db->insert_id);
        }
    }

        if (isset($_GET['d']) && is_numeric($_GET['d'])) {
            $db->query("DELETE FROM support_email WHERE ID = " . intval($_GET['d']));
            alog("support", "email_del", $_GET['d']);
        }
        ?>

				<?php
if (isset($_GET['e']) && is_numeric($_GET['e']) && $db->query("SELECT 1 FROM support_email WHERE ID = " . intval($_GET['e']))->num_rows == 1) {$info = $db->query("SELECT * FROM support_email WHERE ID = " . intval($_GET['e']))->fetch_object();
            alog("support", "email_view", $_GET['e']);
            ?>

				<form method="POST" action="?p=support_config&t=email">
					<h3 style="margin-top: 0;"><?=$info->email;?></h3>

					<div class="form-group">
						<label><?=$l['DEPTORSTAFF'];?></label>
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

					<div class="checkbox">
						<label>
							<input type="checkbox" id="pop3" name="pop3"<?=$info->pop3 ? ' checked="checked"' : '';?>>
							<?=$l['ENABLEPOP'];?>
						</label>

						<a href="#" class="btn btn-default btn-xs pop3" id="pop3_check"><?=$l['CHECKSET'];?></a>
					</div>

					<div class="row pop3">
						<div class="col-md-6">
							<div class="form-group">
								<label><?=$l['P3H'];?></label>
								<input type="text" name="pop3_host" value="<?=$info->pop3_host;?>" placeholder="<?=$l['P3HP'];?>" class="form-control">
							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								<label><?=$l['P3P'];?></label>
								<input type="text" name="pop3_port" value="<?=$info->pop3_port ?: "110";?>" placeholder="<?=$l['P3PP'];?>" class="form-control">
							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								<label><?=$l['ENCRYPTION'];?></label>
								<select name="pop3_ssl" class="form-control">
									<option value="0"><?=$l['NOENC'];?></option>
									<option value="1"<?=$info->pop3_ssl ? ' selected="selected"' : '';?>><?=$l['SSLR'];?></option>
								</select>
							</div>
						</div>
					</div>

					<div class="row pop3">
						<div class="col-md-6">
							<div class="form-group">
								<label><?=$l['P3U'];?></label>
								<input type="text" name="pop3_user" value="<?=$info->pop3_user;?>" placeholder="<?=$l['P3UP'];?>" class="form-control">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label><?=$l['P3PWD'];?></label>
								<input type="text" name="pop3_password" value="<?=decrypt($info->pop3_password);?>" placeholder="<?=$l['P3PWDP'];?>" class="form-control">
							</div>
						</div>
					</div>

					<div class="checkbox pop3" style="margin-top: 0; margin-bottom: 20px;">
						<label>
							<input type="checkbox" name="catchall"<?=$info->catchall ? ' checked="checked"' : '';?>>
							<?=$l['CAA'];?>
						</label>
					</div>

					<?php if (!$info->pop3) {?>
					<style>
					.pop3 {
						display: none;
					}
					</style>
					<?php }?>

					<script>
					$("#pop3").click(function(){
						if($(this).is(":checked")) $(".pop3").show();
						else $(".pop3").hide();
					});

					var doingpop = 0;
					$("#pop3_check").click(function(e){
						e.preventDefault();

						if(doingpop) return;
						doingpop = 1;

						var t = $("#pop3_check");
						var h = t.html();
						t.html("<i class='fa fa-spinner fa-spin'></i> " + h);

						$.post("?p=support_config&t=email&check=pop", {
							host: $("[name=pop3_host]").val(),
							port: $("[name=pop3_port]").val(),
							ssl: $("[name=pop3_ssl]").val(),
							user: $("[name=pop3_user]").val(),
							password: $("[name=pop3_password]").val(),
							csrf_token: "<?=CSRF::raw();?>",
						}, function(r){
							alert(r);
							t.html(h);
							doingpop = 0;
						});
					})
					</script>

					<div class="checkbox">
						<label>
							<input type="checkbox" id="send" name="send"<?=$info->send ? ' checked="checked"' : '';?>>
							<?=$l['ENABLESEND'];?>
						</label>
					</div>

					<div class="form-group send">
						<label><?=$l['SENDER'];?></label>
						<input type="text" name="sender_name" value="<?=$info->sender_name;?>" placeholder="<?=$l['SENDERP'];?>" class="form-control">
					</div>

					<div class="checkbox send">
						<label>
							<input type="checkbox" id="smtp" name="smtp"<?=$info->smtp ? ' checked="checked"' : '';?>>
							<?=$l['ENABLESMTP'];?>
						</label>

						<a href="#" class="btn btn-default btn-xs smtp" id="smtp_check"><?=$l['CHECKSET'];?></a>
					</div>

					<div class="row smtp">
						<div class="col-md-6">
							<div class="form-group">
								<label><?=$l['S3H'];?></label>
								<input type="text" name="smtp_host" value="<?=$info->smtp_host;?>" placeholder="<?=$l['S3HP'];?>" class="form-control">
							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								<label><?=$l['S3P'];?></label>
								<input type="text" name="smtp_port" value="<?=$info->smtp_port ?: "25";?>" placeholder="<?=$l['S3PP'];?>" class="form-control">
							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								<label><?=$l['ENCRYPTION'];?></label>
								<select name="smtp_ssl" class="form-control">
									<option value="0"><?=$l['NOENC'];?></option>
									<option value="1"<?=$info->smtp_ssl ? ' selected="selected"' : '';?>><?=$l['SSLR'];?></option>
								</select>
							</div>
						</div>
					</div>

					<div class="row smtp">
						<div class="col-md-6">
							<div class="form-group">
								<label><?=$l['S3U'];?></label>
								<input type="text" name="smtp_user" value="<?=$info->smtp_user;?>" placeholder="<?=$l['P3UP'];?>" class="form-control">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label><?=$l['S3PWD'];?></label>
								<input type="text" name="smtp_password" value="<?=decrypt($info->smtp_password);?>" placeholder="<?=$l['P3PWDP'];?>" class="form-control">
							</div>
						</div>
					</div>

					<?php if (!$info->send) {?>
					<style>
					.send {
						display: none;
					}
					</style>
					<?php }?>

					<script>
					$("#send").click(function(){
						if($(this).is(":checked")) $(".send").show();
						else $(".send").hide();
					});
					</script>

					<?php if (!$info->smtp) {?>
					<style>
					.smtp {
						display: none;
					}
					</style>
					<?php }?>

					<script>
					$("#smtp").click(function(){
						if($(this).is(":checked")) $(".smtp").show();
						else $(".smtp").hide();
					});

					var doingsmtp = 0;
					$("#smtp_check").click(function(e){
						e.preventDefault();

						if(doingsmtp) return;
						doingsmtp = 1;

						var t = $("#smtp_check");
						var h = t.html();
						t.html("<i class='fa fa-spinner fa-spin'></i> " + h);

						$.post("?p=support_config&t=email&check=smtp", {
							host: $("[name=smtp_host]").val(),
							port: $("[name=smtp_port]").val(),
							ssl: $("[name=smtp_ssl]").val(),
							user: $("[name=smtp_user]").val(),
							password: $("[name=smtp_password]").val(),
							csrf_token: "<?=CSRF::raw();?>",
						}, function(r){
							alert(r);
							t.html(h);
							doingsmtp = 0;
						});
					})
					</script>

					<input type="hidden" name="email" value="<?=$_GET['e'];?>" />
					<input type="submit" class="btn btn-primary btn-block" value="<?=$l['SAVEMAIL'];?>" />

					<hr />
				</form>

				<?php } else if (isset($_POST['email'])) {
            $data = array();

            if (isset($_POST['pop3_password'])) {
                $_POST['pop3_password'] = encrypt($_POST['pop3_password']);
            }

            if (isset($_POST['smtp_password'])) {
                $_POST['smtp_password'] = encrypt($_POST['smtp_password']);
            }

            foreach (array("pop3", "smtp", "send", "catchall") as $f) {
                $data[$f] = isset($_POST[$f]) ? 1 : 0;
            }

            foreach (array("dept", "pop3_host", "pop3_port", "pop3_ssl", "pop3_user", "pop3_password", "smtp_host", "smtp_port", "smtp_ssl", "smtp_user", "smtp_password", "sender_name") as $f) {
                $data[$f] = $db->real_escape_string($_POST[$f]);
            }

            $f = "";
            foreach ($data as $k => $v) {
                $f .= "`$k` = '$v',";
            }

            $f = rtrim($f, ",");

            $db->query("UPDATE support_email SET $f WHERE ID = " . intval($_POST['email']));

            alog("support", "email_upd", $_POST['email']);
        }?>

				<form method="POST" class="form-inline">
					<input type="text" name="email" placeholder="<?=$l['MAILP'];?>" class="form-control" />

					<select name="dept" class="form-control">
						<option disabled="disabled" selected="selected"><?=$l['CHDEPT'];?></option>
						<?php
$sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . $row->ID . '">' . $row->name . '</option>';
        }

        ?>
						<option disabled="disabled"><?=$l['CHSTAFF'];?></option>
						<?php
$sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . ($row->ID / -1) . '">' . $row->name . '</option>';
        }

        ?>
					</select>

					<input type="submit" value="<?=$l['MAILADD'];?>" class="btn btn-primary" />
				</form><br />

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="40%"><?=$l['MAIL'];?></th>
							<th><?=$l['DEPTORSTAFF'];?></th>
							<th width="10%"><center><?=$l['INCO'];?></center></th>
							<th width="10%"><center><?=$l['OUTG'];?></center></th>
							<th width="30px"></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_email ORDER BY email ASC");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="6"><center><?=$l['MAILNT'];?></center></td>
						</tr>
						<?php }
        while ($row = $sql->fetch_object()) {?>
						<tr>
							<td><a href="mailto:<?=$row->email;?>"><?=$row->email;?></a></td>
							<td><?=$row->dept > 0 ? ($db->query("SELECT name FROM support_departments WHERE ID = " . intval($row->dept))->fetch_object()->name ?: "<i>{$l['UK']}</i>"): ($db->query("SELECT name FROM admins WHERE ID = " . abs(intval($row->dept)))->fetch_object()->name ?: "<i>{$l['UK']}</i>");?></td>
							<td><center><i class="fa fa-<?=$row->pop3 ? 'check' : 'times';?>"></i></center></td>
							<td><center><i class="fa fa-<?=$row->send ? 'check' : 'times';?>"></i></center></td>
							<td><a href="?p=support_config&t=email&e=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
							<td><a href="?p=support_config&t=email&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						</tr>
						<?php }?>
					</table>
				</div>

				<?php } else if ($t == "escalation") {
        if (!empty($_POST['name'])) {
            $name = $db->real_escape_string($_POST['name']);
            $te = max(0, intval($_POST['time_elapsed']));
            $db->query("INSERT INTO support_escalations (name, time_elapsed) VALUES ('$name', $te)");
            if ($db->affected_rows) {
                echo '<div class="alert alert-success">' . $l['ESCSUC1'] . '</div>';
            }
        }

        if (!empty($_GET['d'])) {
            $db->query("DELETE FROM support_escalations WHERE ID = " . intval($_GET['d']));
            if ($db->affected_rows) {
                echo '<div class="alert alert-success">' . $l['ESCSUC2'] . '</div>';
            }
        }

        if (!empty($_GET['e']) && $db->query("SELECT 1 FROM support_escalations WHERE ID = " . intval($_GET['e']))->num_rows) {
            if (!empty($_POST['name2'])) {
                $set = [
                    "name" => $_POST['name2'],
                    "time_elapsed" => intval($_POST['time_elapsed']),
                    "department" => implode(",", $_POST['department']),
                    "status" => implode(",", $_POST['status']),
                    "priority" => implode(",", $_POST['priority']),
                    "cgroup" => implode(",", $_POST['cgroup']),
                    "upgrade" => implode(",", $_POST['upgrade']),
                    "new_department" => $_POST['new_department'],
                    "new_status" => $_POST['new_status'],
                    "new_priority" => $_POST['new_priority'],
                    "realtime_notification" => $_POST['realtime_notification'],
                    "webhook_url" => $_POST['webhook_url'],
                ];

                $qry = "UPDATE support_escalations SET ";
                foreach ($set as $k => $v) {
                    $qry .= "`" . $db->real_escape_string($k) . "` = '" . $db->real_escape_string($v) . "', ";
                }
                $qry = rtrim($qry, ", ") . " WHERE ID = " . intval($_GET['e']);

                $db->query($qry);
                echo '<div class="alert alert-success">' . $l['ESCSUC3'] . '</div>';
                unset($_POST);
            }

            $rule = $db->query("SELECT * FROM support_escalations WHERE ID = " . intval($_GET['e']))->fetch_object();
            ?>
						<form method="POST">
							<div class="form-group">
								<label><?=$l['NAME'];?></label>
								<input type="text" name="name2" value="<?=htmlentities($rule->name);?>" class="form-control">
							</div>

							<div class="form-group">
								<label><?=$l['ESCAFT'];?></label>
								<div class="input-group">
									<input type="text" name="time_elapsed" value="<?=strval($rule->time_elapsed);?>" class="form-control">
									<span class="input-group-addon"><?=$l['ESCMIN'];?></span>
								</div>
							</div>

							<div class="form-group">
								<label><?=$l['DEPTS'];?></label>
								<select name="department[]" class="form-control" multiple="multiple">
									<?php
$sql = $db->query("SELECT ID, name FROM support_departments ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
                                    <option value="<?=$row->ID;?>"<?=in_array($row->ID, explode(",", $rule->department)) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
                                    <?php }

            $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
                                    <option value="-<?=$row->ID;?>"<?=in_array($row->ID / -1, explode(",", $rule->department)) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
                                    <?php }?>
								</select>
							</div>

							<div class="form-group">
								<label><?=$l['STATUS'];?></label>
								<select name="status[]" class="form-control" multiple="multiple">
                                    <?php foreach (Ticket::getStatusNames() as $k => $n) {?>
									<option value="<?=$k;?>"<?=in_array($k, explode(",", $rule->status)) ? ' selected=""' : '';?>><?=$n;?></option>
                                    <?php }?>
								</select>
							</div>

                            <div class="form-group">
								<label><?=$l['PRIORITY'];?></label>
								<select name="priority[]" class="form-control" multiple="multiple">
                                    <?php foreach (Ticket::getPriorityText(false) as $k => $n) {?>
									<option value="<?=$k;?>"<?=in_array($k, explode(",", $rule->priority)) ? ' selected=""' : '';?>><?=$n;?></option>
                                    <?php }?>
								</select>
							</div>

							<div class="form-group">
								<label><?=$l['UPGRADE'];?></label>
								<select name="upgrade[]" class="form-control" multiple="multiple">
									<?php
$ugSql = $db->query("SELECT ID, name FROM support_upgrades ORDER BY price ASC, name ASC");
            while ($ugRow = $ugSql->fetch_object()) {$k = $ugRow->ID;
                $n = htmlentities($ugRow->name);?>
									<option value="<?=$k;?>"<?=in_array($k, explode(",", $rule->upgrade)) ? ' selected=""' : '';?>><?=$n;?></option>
                                    <?php }?>
								</select>
							</div>

                            <div class="form-group">
								<label><?=$l['CGROUP'];?></label>
								<select name="cgroup[]" class="form-control" multiple="multiple">
                                    <?php
$sql = $db->query("SELECT ID, name FROM client_groups ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
                                    <option value="<?=$row->ID;?>"<?=in_array($row->ID, explode(",", $rule->cgroup)) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
                                    <?php }?>
								</select>
							</div>

                            <div class="form-group">
								<label><?=$l['NEWDEPT'];?></label>
								<select name="new_department" class="form-control">
                                    <option value=""><?=$l['ESCDNC'];?></option>
									<?php
$sql = $db->query("SELECT ID, name FROM support_departments ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
                                    <option value="<?=$row->ID;?>"<?=strval($rule->new_department) === strval($row->ID) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
                                    <?php }

            $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
                                    <option value="-<?=$row->ID;?>"<?=strval($rule->new_department) === strval($row->ID / -1) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
                                    <?php }?>
								</select>
							</div>

                            <div class="form-group">
								<label><?=$l['NEWSTATUS'];?></label>
								<select name="new_status" class="form-control">
                                    <option value=""><?=$l['ESCDNC'];?></option>
                                    <?php foreach (Ticket::getStatusNames() as $k => $n) {?>
									<option value="<?=$k;?>"<?=strval($k) === strval($rule->new_status) ? ' selected=""' : '';?>><?=$n;?></option>
                                    <?php }?>
								</select>
							</div>

                            <div class="form-group">
								<label><?=$l['NEWPRIO'];?></label>
								<select name="new_priority" class="form-control">
                                    <option value=""><?=$l['ESCDNC'];?></option>
                                    <?php foreach (Ticket::getPriorityText(false) as $k => $n) {?>
									<option value="<?=$k;?>"<?=strval($k) === strval($rule->new_priority) ? ' selected=""' : '';?>><?=$n;?></option>
                                    <?php }?>
								</select>
							</div>

                            <div class="form-group">
								<label><?=$l['RTN'];?></label>
								<input type="text" name="realtime_notification" value="<?=htmlentities($rule->realtime_notification);?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control">
                                <p class="help-block"><?=$l['VARIABLES'];?>: %ID%, %url%, %sender%, %subject%</p>
							</div>

                            <div class="form-group">
								<label><?=$l['WHU'];?></label>
								<input type="text" name="webhook_url" value="<?=htmlentities($rule->webhook_url);?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control">
                                <p class="help-block"><?=$l['VARIABLES'];?>: %ID%, %url%, %sender%, %subject%</p>
							</div>

                            <input type="submit" class="btn btn-primary btn-block" value="<?=$l['ESCSAVE'];?>" />
						</form>
						<?php
} else {
            ?>

				<form method="POST" class="form-inline">
					<input type="text" name="name" placeholder="<?=$l['ESCNAME'];?>" class="form-control" />

					<input type="text" name="time_elapsed" placeholder="<?=$l['ESCAFTM'];?>" class="form-control" />

					<input type="submit" value="<?=$l['ADDESCR'];?>" class="btn btn-primary" />
				</form><br />

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th><?=$l['ESCR'];?></th>
							<th width="10%"><center><?=$l['ESCM'];?></center></th>
							<th width="10%"><center><?=$l['ESCREQ'];?></center></th>
							<th width="10%"><center><?=$l['ESCACT'];?></center></th>
							<th width="30px"></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_escalations ORDER BY name ASC");
            if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="6"><center><?=$l['ESCNT'];?></center></td>
						</tr>
						<?php }
            while ($row = $sql->fetch_object()) {
                $cond = $acts = 0;

                if ($row->department !== "") {
                    $cond++;
                }

                if ($row->status !== "") {
                    $cond++;
                }

                if ($row->priority !== "") {
                    $cond++;
                }

                if ($row->cgroup !== "") {
                    $cond++;
                }

                if ($row->upgrade !== "") {
                    $cond++;
                }

                if ($row->new_department !== "") {
                    $acts++;
                }

                if ($row->new_status !== "") {
                    $acts++;
                }

                if ($row->new_priority !== "") {
                    $acts++;
                }

                if (!empty($row->realtime_notification)) {
                    $acts++;
                }

                if (!empty($row->webhook_url)) {
                    $acts++;
                }

                ?>
						<tr>
							<td><?=htmlentities($row->name);?></td>
							<td><center><?=strval($row->time_elapsed);?></center></td>
							<td><center><?=strval($cond);?></center></td>
							<td><center><?=strval($acts);?></center></td>
							<td><a href="?p=support_config&t=escalation&e=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
							<td><a href="?p=support_config&t=escalation&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						</tr>
						<?php }?>
					</table>
				</div>

				<?php }} else if ($t == "staff") {?>

				<?php
if (isset($_GET['e']) && is_numeric($_GET['e']) && $db->query("SELECT 1 FROM admins WHERE ID = " . intval($_GET['e']))->num_rows == 1) {?>

				<form method="POST" action="?p=support_config&t=staff">
					<h3 style="margin-top: 0;"><?=$db->query("SELECT name FROM admins WHERE ID = " . intval($_GET['e']))->fetch_object()->name;?></h3>

					<div class="row">
						<div class="col-md-6">
							<?php
$sql = $db->query("SELECT ID, name FROM support_departments ORDER BY name ASC");
        if ($sql->num_rows == 0) {
            echo $l['NODEPTS'];
        }

        while ($row = $sql->fetch_object()) {
            ?>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="dept[]" value="<?=$row->ID;?>"<?php if ($db->query("SELECT 1 FROM support_department_staff WHERE staff = " . intval($_GET['e']) . " AND dept = " . $row->ID)->num_rows > 0) {
                echo ' checked="checked"';
            }
            ?>>
										<?=$row->name;?>
									</label>
								</div>
								<?php
}
        ?>
						</div>

						<div class="col-md-6">
							<?php
$sql = $db->query("SELECT ID, name FROM support_signatures ORDER BY name ASC");
        if ($sql->num_rows == 0) {
            echo $l['NOSIGS'];
        }

        while ($row = $sql->fetch_object()) {
            ?>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="sigs[]" value="<?=$row->ID;?>"<?php if ($db->query("SELECT 1 FROM support_signature_staff WHERE staff = " . intval($_GET['e']) . " AND signature = " . $row->ID)->num_rows > 0) {
                echo ' checked="checked"';
            }
            ?>>
										<?=$row->name;?>
									</label>
								</div>
								<?php
}
        ?>
						</div>
					</div>

					<input type="hidden" name="staff" value="<?=$_GET['e'];?>" />
					<input type="submit" class="btn btn-primary btn-block" value="<?=$l['SAVEST'];?>" />

					<hr />
				</form>

				<?php } else if (isset($_POST['staff'])) {
        alog("support", "staff_changed", $_POST['staff']);

        $db->query("DELETE FROM support_department_staff WHERE staff = " . intval($_POST['staff']));
        if (isset($_POST['dept']) && is_array($_POST['dept'])) {
            foreach ($_POST['dept'] as $i) {
                $db->query("INSERT INTO support_department_staff (staff, dept) VALUES (" . intval($_POST['staff']) . ", " . intval($i) . ")");
            }
        }

        $db->query("DELETE FROM support_signature_staff WHERE staff = " . intval($_POST['staff']));
        if (isset($_POST['sigs']) && is_array($_POST['sigs'])) {
            foreach ($_POST['sigs'] as $i) {
                $db->query("INSERT INTO support_signature_staff (staff, signature) VALUES (" . intval($_POST['staff']) . ", " . intval($i) . ")");
            }
        }

    }?>

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="60%"><?=$l['STAFFM'];?></th>
							<th><center><?=$l['SIGS'];?></center></th>
							<th><center><?=$l['DEPTS'];?></center></th>
							<th><center><?=$l['MAILS'];?></center></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="6"><center><?=$l['STAFFNT'];?></center></td>
						</tr>
						<?php }
        while ($row = $sql->fetch_object()) {?>
						<tr>
							<td><?=$row->name;?></td>
							<td><center><?=$db->query("SELECT COUNT(*) AS c FROM support_signature_staff WHERE staff = " . intval($row->ID))->fetch_object()->c;?></center></td>
							<td><center><?=$db->query("SELECT COUNT(*) AS c FROM support_department_staff WHERE staff = " . intval($row->ID))->fetch_object()->c;?></center></td>
							<td><center><?=$db->query("SELECT COUNT(*) AS c FROM support_email WHERE dept = " . (intval($row->ID) / -1))->fetch_object()->c;?></center></td>
							<td><a href="?p=support_config&t=staff&e=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
						</tr>
						<?php }?>
					</table>
				</div>

				<?php } else if ($t == "categories") {?>

				<?php
if (isset($_POST['name'])) {
        $db->query("INSERT INTO support_answer_categories (name) VALUES ('" . $db->real_escape_string($_POST['name']) . "')");
        alog("support", "ac_created", $_POST['name'], $db->insert_id);
    }
        if (isset($_GET['d']) && is_numeric($_GET['d']) && $db->query("SELECT COUNT(*) AS c FROM support_answers WHERE cat = " . intval($_GET['d']))->fetch_object()->c == 0) {
            $db->query("DELETE FROM support_answer_categories WHERE ID = " . intval($_GET['d']));
            alog("support", "ac_deleted", $_GET['d']);
        }
        ?>

				<form method="POST" class="form-inline">
					<input type="text" name="name" placeholder="<?=$l['CATP'];?>" class="form-control" />
					<input type="submit" value="<?=$l['CREACAT'];?>" class="btn btn-primary" />
				</form><br />

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="90%"><?=$l['CAT'];?></th>
							<th><center><?=$l['ANSWERS'];?></center></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_answer_categories ORDER BY name ASC");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="3"><center><?=$l['NOCATS'];?></center></td>
						</tr>
						<?php }
        $sum = 0;while ($row = $sql->fetch_object()) {$count = $db->query("SELECT COUNT(*) AS c FROM support_answers WHERE cat = " . $row->ID)->fetch_object()->c;
            $sum += $count;?>
						<tr>
							<td><?=$row->name;?></td>
							<td><center><?=$count;?></center></td>
							<td><?php if ($count == 0) {?><a href="?p=support_config&t=categories&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a><?php }?></td>
						</tr>
						<?php }?>

						<tr>
							<th style="text-align: right;"><?=$l['SUM'];?></th>
							<th><center><?=$sum;?></center></th>
							<th></th>
						</tr>
					</table>
				</div>

				<?php } else if ($t == "answer") {?>

				<?php
if (isset($_GET['e']) && is_numeric($_GET['e']) && $db->query("SELECT 1 FROM support_answers WHERE ID = " . intval($_GET['e']))->num_rows == 1) {?>

				<form method="POST" action="?p=support_config&t=answer">
					<h3 style="margin-top: 0;"><?=$db->query("SELECT * FROM support_answers WHERE ID = " . intval($_GET['e']))->fetch_object()->name;?></h3>

					<textarea name="message" style="width: 100%; height: 250px; resize: none; margin-bottom: 10px;" class="form-control"><?=$db->query("SELECT * FROM support_answers WHERE ID = " . intval($_GET['e']))->fetch_object()->message;?></textarea>

					<input type="hidden" name="answer" value="<?=$_GET['e'];?>" />
					<input type="submit" class="btn btn-primary btn-block" value="<?=$l['SAVEANS'];?>" />

					<hr />
				</form>

				<?php
} else if (isset($_POST['answer'])) {
        $db->query("UPDATE support_answers SET message = '" . $db->real_escape_string($_POST['message']) . "' WHERE ID = " . intval($_POST['answer']));
        alog("support", "answer_saved", $_POST['answer']);
    }

        if (isset($_POST['name']) && isset($_POST['cat']) && $db->query("SELECT 1 FROM support_answer_categories WHERE ID = " . intval($_POST['cat']))->num_rows == 1) {
            $db->query("INSERT INTO support_answers (cat, name) VALUES (" . intval($_POST['cat']) . ", '" . $db->real_escape_string($_POST['name']) . "')");
            alog("support", "answer_created", $_POST['cat'], $_POST['name'], $db->insert_id);
        }
        if (isset($_GET['d']) && is_numeric($_GET['d'])) {
            $db->query("DELETE FROM support_answers WHERE ID = " . intval($_GET['d']));
            alog("support", "answer_deleted", $_GET['d']);
        }
        ?>

				<form method="POST" class="form-inline">
					<select name="cat" class="form-control">
						<option selected="selected" disabled="disabled"><?=$l['CHCAT'];?></option>
						<?php
$sql = $db->query("SELECT * FROM support_answer_categories ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . $row->ID . '">' . $row->name . '</option>';
        }

        ?>
					</select>
					<input type="text" name="name" placeholder="<?=$l['ANSNP'];?>" class="form-control" />
					<input type="submit" value="<?=$l['CREANS'];?>" class="btn btn-primary" />
				</form><br />

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th><?=$l['CAT'];?></th>
							<th width="60%"><?=$l['ANSWER'];?></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_answers ORDER BY name ASC");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="3"><center><?=$l['ANSNT'];?></center></td>
						</tr>
						<?php }
        while ($row = $sql->fetch_object()) {$cat = $db->query("SELECT name FROM support_answer_categories WHERE ID = " . $row->cat)->fetch_object()->name ?: "<i>Nicht (mehr) vorhanden</i>";?>
						<tr>
							<td><?=$cat;?></td>
							<td><a href="?p=support_config&t=answer&e=<?=$row->ID;?>"><?=$row->name;?></a></td>
							<td><a href="?p=support_config&t=answer&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						</tr>
						<?php }?>
					</table>
				</div>
				<?php } else if ($t == "automation") {
        if (isset($_POST['support_autoclose'])) {
            $db->query("UPDATE settings SET value = '" . abs(intval($_POST['support_autoclose'])) . "' WHERE `key` = 'support_autoclose' LIMIT 1");
            $db->query("UPDATE settings SET value = '" . abs(intval($_POST['support_rating'])) . "' WHERE `key` = 'support_rating' LIMIT 1");
            $db->query("UPDATE settings SET value = '" . abs(intval($_POST['support_rating_mail'])) . "' WHERE `key` = 'support_rating_mail' LIMIT 1");
            echo '<div class="alert alert-success">' . $l['SETSUC1'] . '</div>';

            alog("support", "automation_saved");

            $sql = $db->query("SELECT `key`, value FROM settings WHERE `key` IN ('support_autoclose', 'support_rating', 'support_rating_mail')");
            while ($r = $sql->fetch_object()) {
                $CFG[strtoupper($r->key)] = $r->value;
            }

        }
        ?>

				<form method="POST">
					<div class="form-group">
						<label><?=$l['SETCLOSE'];?></label>
						<div class="input-group">
							<span class="input-group-addon"><?=$l['SETCLOSE1'];?></span>
							<input type="text" class="form-control" name="support_autoclose" value="<?=intval($CFG['SUPPORT_AUTOCLOSE']);?>">
							<span class="input-group-addon"><?=$l['SETCLOSE2'];?></span>
						</div>
						<p class="help-block"><?=$l['SETCLOSED'];?></p>
					</div>

					<div class="form-group">
						<label><?=$l['SETRR'];?></label>
						<div class="input-group">
							<span class="input-group-addon"><?=$l['SETCLOSE1'];?></span>
							<input type="text" class="form-control" name="support_rating" value="<?=intval($CFG['SUPPORT_RATING']);?>">
							<span class="input-group-addon"><?=$l['SETCLOSE2'];?></span>
						</div>
						<p class="help-block"><?=$l['SETCLOSED'];?></p>
					</div>

					<div class="form-group">
						<label><?=$l['SETRM'];?></label>
						<select name="support_rating_mail" class="form-control">
							<option disabled="disabled" selected="selected"><?=$l['PC'];?></option>
							<?php
$sql = $db->query("SELECT ID, name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            ?>
							<option value="<?=$row->ID;?>"<?=$CFG['SUPPORT_RATING_MAIL'] == $row->ID ? ' selected="selected"' : '';?>><?=htmlentities($row->name);?></option>
							<?php }?>
						</select>
						<p class="help-block"><?=$l['VARIABLES'];?>: %subject%, %link_good%, %link_bad%</p>
					</div>

					<input type="submit" class="btn btn-primary btn-block" value="<?=$l['SETSAVE'];?>">
				</form>

				<?php } else if ($t == "filter") {
        if (!empty($_POST['value'])) {
            $db->query("INSERT INTO support_filter (field, type, value, action) VALUES ('" . $db->real_escape_string($_POST['field']) . "', '" . $db->real_escape_string($_POST['type']) . "', '" . $db->real_escape_string($_POST['value']) . "', '" . $db->real_escape_string($_POST['action']) . "')");
            alog("support", "filter_add", $_POST['field'], $_POST['type'], $_POST['value'], $_POST['action'], $db->insert_id);
        }
        if (isset($_GET['d']) && is_numeric($_GET['d'])) {
            $db->query("DELETE FROM support_filter WHERE ID = " . intval($_GET['d']));
            alog("support", "filter_del", $_GET['d']);
        }
        ?>

				<form method="POST" class="form-inline">
					<select name="field" class="form-control">
						<option value="subject"><?=$l['FSUBJ'];?></option>
						<option value="email"><?=$l['FMAIL'];?></option>
					</select>
					<select name="type" class="form-control">
						<option value="contains"><?=$l['FCONT'];?></option>
						<option value="is"><?=$l['FIS'];?></option>
					</select>
					<input type="text" name="value" placeholder="Inhalt" class="form-control" />
					<select name="action" class="form-control">
						<option value="delete"><?=$l['FDEL'];?></option>
						<option value="close"><?=$l['FCLOSE'];?></option>
					</select>
					<input type="submit" value="<?=$l['FADD'];?>" class="btn btn-primary" />
				</form><br />

				<div class="row">
					<div class="col-md-6">
						<div class="panel panel-default">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-12 text-right">
										<div class="huge"><?=$db->query("SELECT COUNT(*) c FROM support_filter WHERE `field` = 'subject'")->fetch_object()->c;?></div>
										<div><?=$l['SUBJECT_FILTER'];?></div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-md-6">
						<div class="panel panel-default">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-12 text-right">
										<div class="huge"><?=$db->query("SELECT COUNT(*) c FROM support_filter WHERE `field` = 'email'")->fetch_object()->c;?></div>
										<div><?=$l['EMAIL_FILTER'];?></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="15%"><?=$l['FFIELD'];?></th>
							<th width="15%"><?=$l['FCOMPA'];?></th>
							<th><?=$l['FVALU'];?></th>
							<th width="20%"><?=$l['FACTI'];?></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_filter");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="5"><center><?=$l['FNT'];?></center></td>
						</tr>
						<?php }
        while ($row = $sql->fetch_object()) {?>
						<tr>
							<td><?=$row->field == "subject" ? $l['FSUBJ'] : $l['FMAIL'];?></td>
							<td><?=$row->type == "is" ? $l['FIS'] : $l['FCONT'];?></td>
							<td><?=htmlentities($row->value);?></td>
							<td><?=$row->action == "delete" ? $l['FDEL'] : $l['FCLOSE'];?></td>
							<td><a href="?p=support_config&t=filter&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						</tr>
						<?php }?>
					</table>
				</div>

		<?php } else if ($t == "upgrades") {

        if (!empty($_GET['edit']) && ($id = intval($_GET['edit'])) && $db->query("SELECT 1 FROM support_upgrades WHERE ID = $id")->num_rows) {
            if (isset($_POST['name'])) {
                try {
                    $sql = $db->prepare("UPDATE support_upgrades SET name = ?, icon = ?, link = ?, department = ?, status = ?, price = ?, valid = ?, color = ?, new_priority = ? WHERE ID = ?");
                    $sql->bind_param("sssssdssii", $name, $icon, $link, $department, $status, $price, $valid, $color, $new_priority, $id);

                    foreach (["name", "icon", "link", "price", "valid", "color", "new_priority"] as $p) {
                        $$p = $_POST[$p] ?? "";
                    }

                    $price = max(doubleval($nfo->phpize($price)), 0);

                    if (empty($name)) {
                        throw new Exception($l['EXNAME']);
                    }

                    $color = "#" . substr(trim($color), 1, 6);

                    $department = is_array($_POST['department']) ? implode(",", $_POST['department']) : "";
                    $status = is_array($_POST['status']) ? implode(",", $_POST['status']) : "";

                    $valid = $valid == "answer" ? "answer" : "unlimited";
                    $new_priority = intval($new_priority);

                    if (!in_array($new_priority, ["-1", "1", "2", "3", "4", "5"])) {
                        $new_priority = -1;
                    }

                    $sql->execute();
                    header('Location: ?p=support_config&t=upgrades');
                    exit;
                } catch (Exception $ex) {
                    echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
                }
            }

            $info = $db->query("SELECT * FROM support_upgrades WHERE ID = $id")->fetch_object();
            foreach (["name", "icon", "link", "valid", "color", "new_priority"] as $p) {
                if (!isset($_POST[$p])) {
                    $_POST[$p] = $info->$p;
                }
            }

            if (!isset($_POST['department'])) {
                $_POST['department'] = explode(",", $info->department);
            }

            if (!isset($_POST['status'])) {
                $_POST['status'] = explode(",", $info->status);
            }

            if (!isset($_POST['price'])) {
                $_POST['price'] = $nfo->format($info->price);
            }

            ?>
<form method="POST">
	<div class="form-group">
		<label><?=$l['UPGRADE'];?></label>
		<div class="row">
			<div class="col-md-3">
				<select name="icon" class="form-control fa">
					<option value="star">&#xf005; (star)</option>
					<option value="heart"<?=($_POST['icon'] ?? "") == "heart" ? ' selected=""' : '';?>>&#xf004; (heart)</option>
					<option value="signal"<?=($_POST['icon'] ?? "") == "signal" ? ' selected=""' : '';?>>&#xf012; (signal)</option>
					<option value="clock-o"<?=($_POST['icon'] ?? "") == "clock-o" ? ' selected=""' : '';?>>&#xf017; (clock)</option>
					<option value="upload"<?=($_POST['icon'] ?? "") == "upload" ? ' selected=""' : '';?>>&#xf01b; (upload)</option>
					<option value="refresh"<?=($_POST['icon'] ?? "") == "refresh" ? ' selected=""' : '';?>>&#xf021; (refresh)</option>
					<option value="flag"<?=($_POST['icon'] ?? "") == "flag" ? ' selected=""' : '';?>>&#xf024; (flag)</option>
					<option value="headphones"<?=($_POST['icon'] ?? "") == "headphones" ? ' selected=""' : '';?>>&#xf025; (headphones)</option>
					<option value="exclamation-sign"<?=($_POST['icon'] ?? "") == "exclamation-sign" ? ' selected=""' : '';?>>&#xf06a; (exclamation-sign)</option>
					<option value="fire"<?=($_POST['icon'] ?? "") == "fire" ? ' selected=""' : '';?>>&#xf06d; (fire)</option>
					<option value="lightbulb"<?=($_POST['icon'] ?? "") == "lightbulb" ? ' selected=""' : '';?>>&#xf0eb; (lightbulb)</option>
					<option value="ambulance"<?=($_POST['icon'] ?? "") == "ambulance" ? ' selected=""' : '';?>>&#xf0f9; (ambulance)</option>
					<option value="medkit"<?=($_POST['icon'] ?? "") == "medkit" ? ' selected=""' : '';?>>&#xf0fa; (medkit)</option>
					<option value="exclamation"<?=($_POST['icon'] ?? "") == "exclamation" ? ' selected=""' : '';?>>&#xf12a; (exclamation)</option>
					<option value="ticket"<?=($_POST['icon'] ?? "") == "ticket" ? ' selected=""' : '';?>>&#xf145; (ticket)</option>
					<option value="level-up"<?=($_POST['icon'] ?? "") == "level-up" ? ' selected=""' : '';?>>&#xf148; (level-up)</option>
				</select>
			</div>

			<div class="col-md-9">
				<div class="input-group" id="colorpicker_upgrade">
					<span class="input-group-addon"><i></i></span>
					<input type="text" name="name" placeholder="<?=$l['NAME'];?>" class="form-control" value="<?=htmlentities(($_POST['name'] ?? "") ?: "");?>">
					<input type="hidden" name="color" id="color_upgrade" value="#<?=($_POST['color'] ?? "") ?: "333333";?>" />
				</div>
			</div>

		            <?php
$var['additionalJS'] .= "$('#colorpicker_upgrade').colorpicker({
		            	format: 'hex',
		            	input: '#color_upgrade',
		            	colorSelectors: {
					        '#333333': '#333333',
					        '#0000ff': '#0000ff',
					        '#ffa500': '#ffa500',
					        '#ff0000': '#ff0000',
					    },
					    align: 'left',
		            });";
            ?>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['INFO_LINK'];?></label>
		<input type="text" name="link" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" value="<?=htmlentities(($_POST['link'] ?? "") ?: "");?>">
	</div>

	<div class="form-group">
		<label><?=$l['DEPARTMENTS'];?></label>
		<select name="department[]" class="form-control" multiple="">
			<?php
$sql = $db->query("SELECT ID, name FROM support_departments ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                echo '<option value="' . $row->ID . '"' . (is_array($_POST['department'] ?? "") && in_array($row->ID, $_POST['department']) ? ' selected=""' : '') . '>' . htmlentities($row->name) . '</option>';
            }
            ?>
		</select>
		<span class="help-block"><?=$lang['BUNDLES']['PRODUCTSH'];?></span>
	</div>

	<div class="form-group">
		<label><?=$l['STATUS'];?></label>
		<select name="status[]" class="form-control" multiple="">
			<?php
for ($i = 0; $i < 4; $i++) {
                echo '<option value="' . $i . '"' . (is_array($_POST['status'] ?? "") && in_array($i, $_POST['status']) ? ' selected=""' : '') . '>' . $lang['TICKET_CLASS']['S' . $i] . '</option>';
            }
            ?>
		</select>
		<span class="help-block"><?=$lang['BUNDLES']['PRODUCTSH'];?></span>
	</div>

	<div class="form-group">
		<label><?=$l['PRICE'];?></label>
		<div class="input-group">
	<?php if ($cur->getPrefix()) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
			<input type="text" name="price" placeholder="<?=$nfo->placeholder();?>" class="form-control" value="<?=($_POST['price'] ?? "") ? $nfo->format($nfo->phpize($_POST['price'])) : "";?>">
			<?php if ($cur->getSuffix()) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['VALID'];?></label>
		<select name="valid" class="form-control">
			<option value="answer"><?=$l['VALID_ANSWER'];?></option>
			<option value="unlimited"<?=($_POST['valid'] ?? "") == "unlimited" ? ' selected=""' : '';?>><?=$l['VALID_UNLIMITED'];?></option>
		</select>
	</div>

	<div class="form-group">
		<label><?=$l['NEW_PRIORITY'];?></label>
		<select name="new_priority" class="form-control">
			<option value="-1"><?=$l['NOCHANGE'];?></option>
			<?php
for ($i = 1; $i <= 5; $i++) {
                echo '<option value="' . $i . '"' . (($_POST['new_priority'] ?? "") === strval($i) ? ' selected=""' : '') . '>' . $lang['TICKET_CLASS']['P' . $i] . '</option>';
            }
            ?>
		</select>
	</div>

	<input type="submit" value="<?=$l['EDIT_UP'];?>" class="btn btn-primary btn-block">
</form>
			<?php
} else if (!empty($_GET['add'])) {
            if (isset($_POST['name'])) {
                try {
                    $sql = $db->prepare("INSERT INTO support_upgrades (name, icon, link, department, status, price, valid, color, new_priority) VALUES (?, ? ,?, ?, ?, ?, ?, ?, ?)");
                    $sql->bind_param("sssssdssi", $name, $icon, $link, $department, $status, $price, $valid, $color, $new_priority);

                    foreach (["name", "icon", "link", "price", "valid", "color", "new_priority"] as $p) {
                        $$p = $_POST[$p] ?? "";
                    }

                    $price = max(doubleval($nfo->phpize($price)), 0);

                    if (empty($name)) {
                        throw new Exception($l['EXNAME']);
                    }

                    $color = "#" . substr(trim($color), 1, 6);

                    $department = is_array($_POST['department']) ? implode(",", $_POST['department']) : "";
                    $status = is_array($_POST['status']) ? implode(",", $_POST['status']) : "";

                    $valid = $valid == "answer" ? "answer" : "unlimited";
                    $new_priority = intval($new_priority);

                    if (!in_array($new_priority, ["-1", "1", "2", "3", "4", "5"])) {
                        $new_priority = -1;
                    }

                    $sql->execute();
                    header('Location: ?p=support_config&t=upgrades');
                    exit;
                } catch (Exception $ex) {
                    echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
                }
            }
            ?>

<form method="POST">
	<div class="form-group">
		<label><?=$l['UPGRADE'];?></label>
		<div class="row">
			<div class="col-md-3">
				<select name="icon" class="form-control fa">
					<option value="star">&#xf005; (star)</option>
					<option value="heart"<?=($_POST['icon'] ?? "") == "heart" ? ' selected=""' : '';?>>&#xf004; (heart)</option>
					<option value="signal"<?=($_POST['icon'] ?? "") == "signal" ? ' selected=""' : '';?>>&#xf012; (signal)</option>
					<option value="clock-o"<?=($_POST['icon'] ?? "") == "clock-o" ? ' selected=""' : '';?>>&#xf017; (clock)</option>
					<option value="upload"<?=($_POST['icon'] ?? "") == "upload" ? ' selected=""' : '';?>>&#xf01b; (upload)</option>
					<option value="refresh"<?=($_POST['icon'] ?? "") == "refresh" ? ' selected=""' : '';?>>&#xf021; (refresh)</option>
					<option value="flag"<?=($_POST['icon'] ?? "") == "flag" ? ' selected=""' : '';?>>&#xf024; (flag)</option>
					<option value="headphones"<?=($_POST['icon'] ?? "") == "headphones" ? ' selected=""' : '';?>>&#xf025; (headphones)</option>
					<option value="exclamation-sign"<?=($_POST['icon'] ?? "") == "exclamation-sign" ? ' selected=""' : '';?>>&#xf06a; (exclamation-sign)</option>
					<option value="fire"<?=($_POST['icon'] ?? "") == "fire" ? ' selected=""' : '';?>>&#xf06d; (fire)</option>
					<option value="lightbulb"<?=($_POST['icon'] ?? "") == "lightbulb" ? ' selected=""' : '';?>>&#xf0eb; (lightbulb)</option>
					<option value="ambulance"<?=($_POST['icon'] ?? "") == "ambulance" ? ' selected=""' : '';?>>&#xf0f9; (ambulance)</option>
					<option value="medkit"<?=($_POST['icon'] ?? "") == "medkit" ? ' selected=""' : '';?>>&#xf0fa; (medkit)</option>
					<option value="exclamation"<?=($_POST['icon'] ?? "") == "exclamation" ? ' selected=""' : '';?>>&#xf12a; (exclamation)</option>
					<option value="ticket"<?=($_POST['icon'] ?? "") == "ticket" ? ' selected=""' : '';?>>&#xf145; (ticket)</option>
					<option value="level-up"<?=($_POST['icon'] ?? "") == "level-up" ? ' selected=""' : '';?>>&#xf148; (level-up)</option>
				</select>
			</div>

			<div class="col-md-9">
				<div class="input-group" id="colorpicker_upgrade">
					<span class="input-group-addon"><i></i></span>
					<input type="text" name="name" placeholder="<?=$l['NAME'];?>" class="form-control" value="<?=htmlentities(($_POST['name'] ?? "") ?: "");?>">
					<input type="hidden" name="color" id="color_upgrade" value="#<?=($_POST['color'] ?? "") ?: "333333";?>" />
				</div>
			</div>

		            <?php
$var['additionalJS'] .= "$('#colorpicker_upgrade').colorpicker({
		            	format: 'hex',
		            	input: '#color_upgrade',
		            	colorSelectors: {
					        '#333333': '#333333',
					        '#0000ff': '#0000ff',
					        '#ffa500': '#ffa500',
					        '#ff0000': '#ff0000',
					    },
					    align: 'left',
		            });";
            ?>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['INFO_LINK'];?></label>
		<input type="text" name="link" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" value="<?=htmlentities(($_POST['link'] ?? "") ?: "");?>">
	</div>

	<div class="form-group">
		<label><?=$l['DEPARTMENTS'];?></label>
		<select name="department[]" class="form-control" multiple="">
			<?php
$sql = $db->query("SELECT ID, name FROM support_departments ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                echo '<option value="' . $row->ID . '"' . (is_array($_POST['department'] ?? "") && in_array($row->ID, $_POST['department']) ? ' selected=""' : '') . '>' . htmlentities($row->name) . '</option>';
            }
            ?>
		</select>
		<span class="help-block"><?=$lang['BUNDLES']['PRODUCTSH'];?></span>
	</div>

	<div class="form-group">
		<label><?=$l['STATUS'];?></label>
		<select name="status[]" class="form-control" multiple="">
			<?php
for ($i = 0; $i < 4; $i++) {
                echo '<option value="' . $i . '"' . (is_array($_POST['status'] ?? "") && in_array($i, $_POST['status']) ? ' selected=""' : '') . '>' . $lang['TICKET_CLASS']['S' . $i] . '</option>';
            }
            ?>
		</select>
		<span class="help-block"><?=$lang['BUNDLES']['PRODUCTSH'];?></span>
	</div>

	<div class="form-group">
		<label><?=$l['PRICE'];?></label>
		<div class="input-group">
	<?php if ($cur->getPrefix()) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
			<input type="text" name="price" placeholder="<?=$nfo->placeholder();?>" class="form-control" value="<?=($_POST['price'] ?? "") ? $nfo->format($nfo->phpize($_POST['price'])) : "";?>">
			<?php if ($cur->getSuffix()) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['VALID'];?></label>
		<select name="valid" class="form-control">
			<option value="answer"><?=$l['VALID_ANSWER'];?></option>
			<option value="unlimited"<?=($_POST['valid'] ?? "") == "unlimited" ? ' selected=""' : '';?>><?=$l['VALID_UNLIMITED'];?></option>
		</select>
	</div>

	<div class="form-group">
		<label><?=$l['NEW_PRIORITY'];?></label>
		<select name="new_priority" class="form-control">
			<option value="-1"><?=$l['NOCHANGE'];?></option>
			<?php
for ($i = 1; $i <= 5; $i++) {
                echo '<option value="' . $i . '"' . (($_POST['new_priority'] ?? "") === strval($i) ? ' selected=""' : '') . '>' . $lang['TICKET_CLASS']['P' . $i] . '</option>';
            }
            ?>
		</select>
	</div>

	<input type="submit" value="<?=$l['CREATE_UP'];?>" class="btn btn-primary btn-block">
</form>

		<?php } else {?>

		<a href="?p=support_config&t=upgrades&add=1" class="btn btn-success"><?=$l['CREATE_UP'];?></a><br /><br />

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th><?=$l['UPGRADE'];?></th>
					<th><?=$l['PRICING'];?></th>
					<th width="30px"></th>
					<th width="30px"></th>
				</tr>

				<?php
if (!empty($_GET['del']) && $id = intval($_GET['del']) && !$db->query("SELECT 1 FROM support_tickets WHERE upgrade_id = $id")->num_rows) {
            $db->query("DELETE FROM support_upgrades WHERE ID = $id");
        }

            $sql = $db->query("SELECT * FROM support_upgrades ORDER BY price ASC");
            while ($row = $sql->fetch_object()) {
                ?>
<tr>
	<td><i class="fa fa-fw fa-<?=htmlentities($row->icon);?>"></i> <span style="background-color: <?=htmlentities($row->color);?>"><?=htmlentities($row->name);?></span></td>
	<td><?=$cur->infix($nfo->format($row->price), $cur->getBaseCurrency());?></td>
	<td><center><a href="?p=support_config&t=upgrades&edit=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></center></td>
		<td><?php if (!$db->query("SELECT 1 FROM support_tickets WHERE upgrade_id = {$row->ID}")->num_rows) {?><center><a href="?p=support_config&t=upgrades&del=<?=$row->ID;?>"><i class="fa fa-times"></i></a></center><?php } else {echo '<center>-</center>';}?></td>
</tr>
					<?php
}if (!$sql->num_rows) {echo '<tr><td colspan="4"><center>' . $l['NO_UPGRADES'] . '</center></td></tr>';}
            ?>
			</table>
		</div>

				<?php }} else if ($t == "signatures") {?>

				<?php
if (isset($_GET['e']) && is_numeric($_GET['e']) && $db->query("SELECT 1 FROM support_signatures WHERE ID = " . intval($_GET['e']))->num_rows == 1) {?>

				<form method="POST" action="?p=support_config&t=signatures">
					<h3 style="margin-top: 0;"><?=$db->query("SELECT * FROM support_signatures WHERE ID = " . intval($_GET['e']))->fetch_object()->name;?></h3>

					<textarea name="text" style="width: 100%; height: 250px; resize: none; margin-bottom: 10px;" class="form-control"><?=$db->query("SELECT * FROM support_signatures WHERE ID = " . intval($_GET['e']))->fetch_object()->text;?></textarea>

					<input type="hidden" name="signature" value="<?=$_GET['e'];?>" />
					<input type="submit" class="btn btn-primary btn-block" value="<?=$l['SAVEANS'];?>" />

					<hr />
				</form>

				<?php
} else if (isset($_POST['signature'])) {
        $db->query("UPDATE support_signatures SET text = '" . $db->real_escape_string($_POST['text']) . "' WHERE ID = " . intval($_POST['signature']));
        alog("support", "sigupdate", $_POST['signature']);
    }

        if (isset($_POST['name'])) {
            $db->query("INSERT INTO support_signatures (name) VALUES ('" . $db->real_escape_string($_POST['name']) . "')");
            alog("support", "sigadd", $_POST['name'], $db->insert_id);
        }
        if (isset($_GET['d']) && is_numeric($_GET['d'])) {
            $db->query("DELETE FROM support_signatures WHERE ID = " . intval($_GET['d']));
            alog("support", "sigdel", $_GET['d']);
        }
        ?>

				<form method="POST" class="form-inline">
					<input type="text" name="name" placeholder="<?=$l['SIGNP'];?>" class="form-control" />
					<input type="submit" value="<?=$l['CREASIG'];?>" class="btn btn-primary" />
				</form><br />

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="85%"><?=$l['SIGN'];?></th>
							<th><center><?=$l['SIGST'];?></center></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_signatures ORDER BY name ASC");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="3"><center><?=$l['SIGNT'];?></center></td>
						</tr>
						<?php }
        while ($row = $sql->fetch_object()) {?>
						<tr>
							<td><a href="?p=support_config&t=signatures&e=<?=$row->ID;?>"><?=$row->name;?></a></td>
							<td><center><?=$db->query("SELECT COUNT(*) AS c FROM support_signature_staff WHERE signature = " . $row->ID)->fetch_object()->c;?></center></td>
							<td><a href="?p=support_config&t=signatures&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						</tr>
						<?php }?>
					</table>
				</div>

				<?php } else {?>

				<?php
if (isset($_GET['d']) && is_numeric($_GET['d'])) {
        $sql = $db->query("SELECT file FROM support_ticket_attachments WHERE ID = " . intval($_GET['d']));
        if ($sql->num_rows) {
            $file = $sql->fetch_object()->file;
            if (substr($file, 0, 5) == "file#") {
                $file = substr($file, 5);
                unlink(__DIR__ . "/../../files/tickets/" . basename($file));
            }

            $db->query("DELETE FROM support_ticket_attachments WHERE ID = " . intval($_GET['d']));
            alog("support", "delete_attachment", $_GET['d']);
        }
    }
        ?>

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="40%"><?=$l['ATFN'];?></th>
							<th width="40%"><?=$l['ATTK'];?></th>
							<th><center><?=$l['ATFS'];?></center></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $db->query("SELECT * FROM support_ticket_attachments ORDER BY name ASC");
        if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="4"><center><?=$l['ATNT'];?></center></td>
						</tr>
						<?php }
        $sum = 0;while ($row = $sql->fetch_object()) {
            if (substr($row->file, 0, 5) == "file#") {
                $size = strlen(file_get_contents(__DIR__ . "/../../files/tickets/" . basename(substr($row->file, 5)))) / 1024;
            } else {
                $size = strlen($row->file) / 1024;
            }

            $sum += $size;
            ?>

						<?php
$ticket = "<i>{$l['UK']}</i>";

            $sql2 = $db->query("SELECT ticket FROM support_ticket_answers WHERE ID = " . $row->message);
            if ($sql2->num_rows == 1) {
                $sql3 = $db->query("SELECT ID, subject FROM support_tickets WHERE ID = " . $sql2->fetch_object()->ticket);
                if ($sql3->num_rows == 1) {
                    $sti = $sql3->fetch_object();
                    $ticket = '<a href="?p=support_ticket&id=' . $sti->ID . '">' . htmlentities($sti->subject) . '</a>';
                }
            }
            ?>

						<tr>
							<td><?=htmlentities($row->name);?></td>
							<td><?=$ticket;?></td>
							<td><center><?=number_format($size, 2, ',', '.');?> KB</center></td>
							<td><a href="?p=support_config&t=attachments&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						</tr>
						<?php }?>

						<tr>
							<th colspan="2" style="text-align: right;"><?=$l['SUM'];?></th>
							<th><center><?=number_format($sum, 2, ',', '.');?> KB</center></th>
							<th></th>
						</tr>
					</table>
				</div>

				<?php }?>
			</div>
		</div>
	</div>
</div>
<?php }