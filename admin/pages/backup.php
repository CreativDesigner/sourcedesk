<?php
$l = $lang['BACKUP'];
title($l['TITLE']);
menu("settings");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(48)) {
    require realpath(__DIR__ . "/backup.php");
    alog("general", "insufficient_page_rights", "backup");
} else {

    $tab = isset($_GET['tab']) ? $_GET['tab'] : "log";
    ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE2'];?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

 			<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$tab == "log" ? " active" : "";?>" href="./?p=backup"><?=$l['TAB1'];?></a>
					<a class="list-group-item<?=$tab == "settings" ? " active" : "";?>" href="./?p=backup&tab=settings"><?=$l['TAB2'];?></a>
					<a class="list-group-item<?=$tab == "file" ? " active" : "";?>" href="./?p=backup&tab=file"><?=$l['TAB3'];?></a>
					<a class="list-group-item<?=$tab == "ftp" ? " active" : "";?>" href="./?p=backup&tab=ftp"><?=$l['TAB5'];?></a>
				</div>
			</div>

			<div class="col-md-9">

			<?php if ($tab == "log") {?>

			<?php
$table = new Table("SELECT * FROM backup_log", [], ["time", "DESC"], "backup_log");

        echo $table->getHeader();
        ?>

			<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th><?=$table->orderHeader("time", $l['DATE']);?></th>
					<th><?=$l['LOG'];?></th>
				</tr>

				<?php $sql = $table->qry("time DESC");if ($sql->num_rows <= 0) {?>

				<tr>
					<td colspan="2">
						<center><?=$l['NLE'];?></center>
					</td>
				</tr>

				<?php } else {while ($r = $sql->fetch_object()) {?>

				<tr>
					<td><?=$dfo->format($r->time, true, true);?></td>
					<td><a data-toggle="modal" data-target="#log_<?=$r->ID;?>" href="#"><?=$l['VL'];?></a></td>
				</tr>

				<div class="modal fade" id="log_<?=$r->ID;?>" tabindex="-1" role="dialog" aria-hidden="true">
				  <div class="modal-dialog">
					<div class="modal-content">
					  <div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel"><?=$l['PREDATE'];?> <?=$dfo->format($r->time, true, true);?></h4>
					  </div>
					  <div class="modal-body">
						<?=nl2br($r->log);?>
					  </div>
					  <div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
					  </div>
					</div>
				  </div>
				</div>

				<?php }}?>
			</table>
			</div>

			<?php echo $table->getFooter(); ?>
			<?php } else if ($tab == "settings") {?>

            <div class="alert alert-info"><?=$l['CRONJOB'];?></i></div>

			<?php
if (isset($_POST['save_settings']) && isset($_POST['backup_method'])) {
        $active = 0;
        if (isset($_POST['backup_active'])) {
            $active = 1;
        }

        $aff = 0;

        $db->query("UPDATE cronjobs SET `active` = $active WHERE `key` = 'backup' LIMIT 1");
        $aff += $db->affected_rows;

        $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['backup_method']) . "' WHERE `key` = 'backup_method' LIMIT 1");
        $aff += $db->affected_rows;

        $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['backup_data']) . "' WHERE `key` = 'backup_data' LIMIT 1");

        $db->query("UPDATE settings SET `value` = '" . max(0, intval($_POST['backup_retention'])) . "' WHERE `key` = 'backup_retention' LIMIT 1");

        if (($aff + $db->affected_rows) > 0) {
            echo '<div class="alert alert-success">' . $l['SAVED'] . '</div>';
            $CFG['BACKUP_METHOD'] = $_POST['backup_method'];
            $CFG['BACKUP_DATA'] = $_POST['backup_data'];
            alog("backup", "settings_changed");
        }
    }

        $active = (bool) $db->query("SELECT ID FROM cronjobs WHERE `active` = 1 AND `key` = 'backup' LIMIT 1")->num_rows;
        ?>

			<form method="POST" role="form">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="backup_active" value="1" <?=$active ? 'checked="checked"' : "";?>> <?=$l['CJA'];?>
						<p class="help-block"><?=$l['CJAH'];?><p>
					</label>
				</div>

				<div class="form-group">
					<label><?=$l['WTB'];?></label>
					<select name="backup_data" class="form-control">
						<option value="files" <?=$CFG['BACKUP_DATA'] == "files" ? "selected" : "";?>><?=$l['FILES'];?></option>
						<option value="db" <?=$CFG['BACKUP_DATA'] == "db" ? "selected" : "";?>><?=$l['DB'];?></option>
						<option value="all" <?=$CFG['BACKUP_DATA'] == "all" ? "selected" : "";?>><?=$l['FILESDB'];?></option>
					</select>
				</div>

				<div class="form-group">
					<label><?=$l['HTB'];?></label>
					<select name="backup_method" class="form-control">
						<option value="file" <?=$CFG['BACKUP_METHOD'] == "file" ? "selected" : "";?>><?=$l['FS'];?></option>
						<option value="ftp" <?=$CFG['BACKUP_METHOD'] == "ftp" ? "selected" : "";?>><?=$l['FTP'];?></option>
					</select>
				</div>

				<div class="form-group">
					<label><?=$l['RETENTION'];?></label>
					<div class="input-group">
						<input type="text" name="backup_retention" value="<?=intval($CFG['BACKUP_RETENTION']);?>" class="form-control">
						<span class="input-group-addon"><?=$l['DAYS'];?></span>
					</div>
					<p class="help-block"><?=$l['RETENTIONH'];?></p>
				</div>

				<center><input type="submit" name="save_settings" value="<?=$l['SAVES'];?>" class="btn btn-primary btn-block"></center>
			</form>

			<?php } else if ($tab == "file") {?>

			<?php
if (isset($_POST['save_file'])) {

        try {
            $dir = realpath($_POST['backup_file_path']);
            if (!$dir) {
                throw new Exception($l['ERR1']);
            }

            if (!is_readable($dir)) {
                throw new Exception($l['ERR2']);
            }

            if (!is_writeable($dir)) {
                throw new Exception($l['ERR3']);
            }

            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($dir) . "' WHERE `key` = 'backup_file_path' LIMIT 1");

            ?><div class="alert alert-success"><?=$l['FSSAVED'];?></div><?php

            $CFG['BACKUP_FILE_PATH'] = $dir;
            unset($_POST);

            alog("backup", "path_changed");
        } catch (Exception $ex) {
            ?><div class="alert alert-danger"><b><?=$lang['GENERAL']['ERROR'];?></b> <?=$ex->getMessage();?></div><?php
}

    }
        ?>

			<form method="POST" role="form">
				<div class="form-group">
					<label><?=$l['PATH'];?></label>
					<input name="backup_file_path" class="form-control" type="text" value="<?=isset($_POST['backup_file_path']) ? $_POST['backup_file_path'] : ($CFG['BACKUP_FILE_PATH'] != "" ? $CFG['BACKUP_FILE_PATH'] : realpath(__DIR__ . "/../../files/backups/"));?>" placeholder="<?=$l['PATHP'];?>">
					<p class="help-block"><?=$l['PATHH'];?></p>
				</div>

				<center><input type="submit" name="save_file" value="<?=$l['SAVES'];?>" class="btn btn-primary btn-block"></center>
			</form>

			<?php } else if ($tab == "ftp") {?>

			<?php
if (isset($_POST['save_ftp'])) {

        try {
            $ex = explode(":", $_POST['backup_ftp_host']);
            if (!$ex || count($ex) == 1) {
                $port = 21;
            } else {
                $port = $ex[1];
            }

            if ($_POST['backup_ftp_encryption'] != "ssl") {
                $ftp = ftp_connect($_POST['backup_ftp_host'], $port);
            } else {
                $ftp = ftp_ssl_connect($_POST['backup_ftp_host'], $port);
            }

            if (!$ftp) {
                throw new Exception(str_replace("%p", $port, $l['ERR5']));
            }

            if (!ftp_login($ftp, $_POST['backup_ftp_user'], $_POST['backup_ftp_password'])) {
                throw new Exception($l['ERR6']);
            }

            ftp_mkdir($ftp, $_POST['backup_ftp_path']);

            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['backup_ftp_host']) . "' WHERE `key` = 'backup_ftp_host' LIMIT 1");
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['backup_ftp_user']) . "' WHERE `key` = 'backup_ftp_user' LIMIT 1");
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string(encrypt($_POST['backup_ftp_password'])) . "' WHERE `key` = 'backup_ftp_password' LIMIT 1");
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['backup_ftp_path']) . "' WHERE `key` = 'backup_ftp_path' LIMIT 1");
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['backup_ftp_encryption']) . "' WHERE `key` = 'backup_ftp_encryption' LIMIT 1");

            ?><div class="alert alert-success"><?=$l['SAVED'];?></div><?php

            $cfg_sql = $db->query("SELECT * FROM settings");
            while ($c = $cfg_sql->fetch_object()) {
                $CFG[strtoupper($c->key)] = $c->value;
            }

            $CFG['BACKUP_FTP_PASSWORD'] = decrypt($CFG['BACKUP_FTP_PASSWORD']);
            alog("backup", "ftp_changed");
            unset($_POST);
        } catch (Exception $ex) {
            ?><div class="alert alert-danger"><b><?=$lang['GENERAL']['ERROR'];?></b> <?=$ex->getMessage();?></div><?php
}

    }
        ?>

			<form method="POST" role="form">
				<div class="form-group">
					<label><?=$l['FTPHOST'];?></label>
					<input name="backup_ftp_host" class="form-control" type="text" value="<?=isset($_POST['backup_ftp_host']) ? $_POST['backup_ftp_host'] : $CFG['BACKUP_FTP_HOST'];?>" placeholder="<?=$l['FTPHOSTP'];?>">
					<p class="help-block"><?=$l['FTPHOSTH'];?></p>
				</div>

				<div class="form-group">
					<label><?=$l['ENC'];?></label>
					<select name="backup_ftp_encryption" class="form-control">
						<option value="none" <?=$CFG['BACKUP_FTP_ENCRYPTION'] == "none" ? "selected" : "";?>><?=$l['ENCNONE'];?></option>
						<option value="ssl" <?=isset($_POST['backup_ftp_encryption']) ? ($_POST['backup_ftp_encryption'] == "ssl" ? "selected" : "") : ($CFG['BACKUP_FTP_ENCRYPTION'] == "ssl" ? "selected" : "");?>><?=$l['ENCSSL'];?></option>
					</select>
				</div>

				<div class="form-group">
					<label><?=$l['FTPUSER'];?></label>
					<input name="backup_ftp_user" class="form-control" type="text" value="<?=isset($_POST['backup_ftp_user']) ? $_POST['backup_ftp_user'] : $CFG['BACKUP_FTP_USER'];?>" placeholder="<?=$l['FTPUSERP'];?>">
				</div>

				<div class="form-group">
					<label><?=$l['FTPPWD'];?></label>
					<input name="backup_ftp_password" class="form-control" type="text" value="<?=isset($_POST['backup_ftp_password']) ? $_POST['backup_ftp_password'] : $CFG['BACKUP_FTP_PASSWORD'];?>" placeholder="<?=$l['FTPPWDP'];?>">
				</div>

				<div class="form-group">
					<label><?=$l['PATH'];?></label>
					<input name="backup_ftp_path" class="form-control" type="text" value="<?=isset($_POST['backup_ftp_path']) ? $_POST['backup_ftp_path'] : ($CFG['BACKUP_FTP_PATH'] != "" ? $CFG['BACKUP_FTP_PATH'] : '/');?>" placeholder="<?=$l['PATHP'];?>">
					<p class="help-block"><?=$l['PATHH'];?></p>
				</div>

				<center><input type="submit" name="save_ftp" value="<?=$l['SAVES'];?>" class="btn btn-primary btn-block"></center><br />

				<?php } else {echo '<div class="alert alert-danger">' . $l['PNF'] . '</div>';}?>
			</form>
        </div></div>
<?php }?>