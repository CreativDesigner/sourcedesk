<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['VIEW_PROJECT'];

title($l['TITLE']);
menu("");

$sql = $db->query("SELECT * FROM projects WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'");if (!$ari->check(31) || $sql->num_rows != 1) {require __DIR__ . "/error.php";if (!$ari->check(31)) {
    alog("general", "insufficient_page_rights", "view_project");
}
} else {
    $info = $sql->fetch_object();
    array_push($var['customJSFiles'], "projects");

    title($info->name);

    if (isset($_GET['file']) && unserialize($info->files) !== false && in_array($_GET['file'], unserialize($info->files)) && file_exists(__DIR__ . '/../../files/projects/' . basename($_GET['file']))) {
        alog("project", "file_download", $info->ID, basename($_GET['file']));

        // Get the file from download directory
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . substr(basename($_GET['file']), 9) . "\"");
        readfile(__DIR__ . '/../../files/projects/' . basename($_GET['file']));

        // Exit the script to prevent output
        exit;
    }

    if (!empty($_POST['file']) && isset($_POST['expire'])) {
        $expire = strtotime($_POST['expire']) ?: 0;
        $files = unserialize($info->files);
        $expire_dates = unserialize($info->files_expiry);

        if (in_array($_POST['file'], $files)) {
            $expire_dates[$_POST['file']] = $expire;
            $ed = $db->real_escape_string(serialize($expire_dates));
            $db->query("UPDATE projects SET files_expiry = '$ed' WHERE ID = {$info->ID}");

            if ($expire) {
                die($l['VALIDUNTIL'] . " " . date("d.m.Y", $expire));
            } else {
                die($l['DNE']);
            }
        }

        die($l['TECHERR']);
    }

    if (isset($_POST['save_data'])) {
        try {
            foreach ($_POST as $k => $v) {
                $vari = "p_" . strtolower($k);
                $$vari = $db->real_escape_string($v);
            }

            if (!isset($p_name) || strlen($p_name) < 1) {
                throw new Exception($l['ERR1']);
            }

            $p_due = strtotime($p_due);
            if (!isset($_POST['due']) || !$p_due) {
                throw new Exception($l['ERR2']);
            }

            $p_due = date("Y-m-d", $p_due);

            $p_entgelt = $nfo->phpize($p_entgelt);
            if (!isset($_POST['entgelt']) || !is_numeric($p_entgelt) || $p_entgelt < 0) {
                throw new Exception($l['ERR3']);
            }

            if (!isset($p_user) || ($p_user != 0 && $db->query("SELECT ID FROM clients WHERE ID = '$p_user'")->num_rows != 1)) {
                throw new Exception($l['ERR4']);
            }

            if (!isset($p_product) || ($p_product != 0 && $db->query("SELECT ID FROM products WHERE ID = '$p_product'")->num_rows != 1)) {
                throw new Exception($l['ERR5']);
            }

            if (!isset($p_admin) || ($p_admin != 0 && $db->query("SELECT ID FROM admins WHERE ID = '$p_admin'")->num_rows != 1)) {
                throw new Exception($l['ERR6']);
            }

            if (!isset($p_status) || !is_numeric($p_status) || $p_status > 3 || $p_status < 0) {
                $p_status = 0;
            }

            $p_status = intval($p_status);

            if (isset($p_show_details)) {
                $p_show_details = 1;
            } else {
                $p_show_details = 0;
            }

            $entgelt_type = !empty($p_entgelt_type) ? 1 : 0;
            $entgelt_done = $info->entgelt_done;
            if ($entgelt_type != $info->entgelt_type) {
                $entgelt_done = 0;
            }

            if (!isset($_POST['time_contingent']) || !is_numeric($p_time_contingent) || $p_time_contingent < 0) {
                throw new Exception($l['ERR7']);
            }

            if (!$db->query("UPDATE projects SET name = '$p_name', description = '$p_description', admin = '$p_admin', status = $p_status, entgelt = '$p_entgelt', user = '$p_user', due = '$p_due', show_details = '$p_show_details', entgelt_type = $entgelt_type, entgelt_done = $entgelt_done, product = '$p_product', time_tracking = '$p_time_tracking', time_contingent = '$p_time_contingent' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")) {
                throw new Exception($l['TECHERR']);
            }

            if ($db->affected_rows > 0) {
                $msg = "<div class=\"alert alert-success\">{$l['SUC']}</div>";
                alog("project", "update", $p_name, $_GET['id']);
            }
            unset($_POST);

            $info = $db->query("SELECT * FROM projects WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();
        } catch (Exception $ex) {
            $msg = "<div class=\"alert alert-danger\">" . $ex->getMessage() . " {$l['CORRERR']}</div>";
        }
    }
    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><small><?=$CFG['PNR_PREFIX'] ?: "#";?><?=$info->ID;?></small> <?=$info->name;?> <small><a data-toggle="modal" href="#" data-target="#edit"><?=$l['EDIT'];?></a></small></h1>
			<input type="hidden" id="project_name" value="<?=$info->name;?>" />
			<input type="hidden" id="actTime" value="0" />
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php
if (isset($msg)) {
        echo $msg;
    }

    if (isset($_GET['ok'])) {
        $db->query("UPDATE project_tasks SET status = 1 WHERE ID = '" . $db->real_escape_string($_GET['ok']) . "' AND project = " . $info->ID . " LIMIT 1");
        if ($db->affected_rows > 0) {
            $suc = $l['SUC1'];
            alog("project", "task_ok", $_GET['ok'], $_GET['id']);
        }
    }

    if (isset($_POST['done_selected']) && is_array($_POST['task'])) {
        $d = 0;
        foreach ($_POST['task'] as $id) {
            $db->query("UPDATE project_tasks SET status = 1 WHERE ID = '" . $db->real_escape_string($id) . "' AND project = " . $info->ID . " LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                alog("project", "task_ok", $id, $_GET['id']);
            }
        }

        if ($d == 1) {
            $suc = $l['SUC1O'];
        } else if ($d > 0) {
            $suc = str_replace("%d", $d, $l['SUC1X']);
        }

    }

    if (isset($_POST['undone_selected']) && is_array($_POST['task'])) {
        $d = 0;
        foreach ($_POST['task'] as $id) {
            $db->query("UPDATE project_tasks SET status = 0 WHERE ID = '" . $db->real_escape_string($id) . "' AND project = " . $info->ID . " LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                alog("project", "task_nok", $id, $_GET['id']);
            }
        }

        if ($d == 1) {
            $suc = $l['SUC2O'];
        } else if ($d > 0) {
            $suc = str_replace("%d", $d, $l['SUC2X']);
        }

    }

    if (isset($_GET['del'])) {
        $db->query("DELETE FROM  project_tasks WHERE ID = '" . $db->real_escape_string($_GET['del']) . "' AND project = " . $info->ID . " LIMIT 1");
        if ($db->affected_rows > 0) {
            $db->query("DELETE FROM project_times WHERE task = " . intval($_GET['del']));
            alog("project", "task_del", $_GET['del'], $_GET['id']);
            $suc = $l['SUC3'];
        }
    }

    if (isset($_POST['delete_selected']) && is_array($_POST['task'])) {
        $d = 0;
        foreach ($_POST['task'] as $id) {
            $db->query("DELETE FROM  project_tasks WHERE ID = '" . $db->real_escape_string($id) . "' AND project = " . $info->ID . " LIMIT 1");
            if ($db->affected_rows > 0) {
                $db->query("DELETE FROM project_times WHERE task = " . intval($id));
                alog("project", "task_del", $id, $_GET['id']);
                $d++;
            }
        }

        if ($d == 1) {
            $suc = $l['SUC3O'];
        } else if ($d > 0) {
            $suc = str_replace("%d", $d, $l['SUC3X']);
        }

    }

    if (isset($_POST['save_task'])) {
        try {
            foreach ($_POST as $key => $value) {
                $vari = 'p_' . strtolower($key);
                $$vari = $db->real_escape_string($value);
            }

            if ($db->query("SELECT ID FROM project_tasks WHERE ID = '" . $p_task . "'")->num_rows != 1) {
                throw new Exception($l['ERR8']);
            }

            if (!isset($p_name) || strlen($p_name) < 1) {
                throw new Exception($l['ERR9']);
            }

            if (!isset($p_color) || strlen($p_color) != 7) {
                $p_color = "333333";
            } else {
                $p_color = substr($p_color, 1);
            }

            if (!isset($p_description)) {
                $p_description = "";
            }

            if (!isset($p_status)) {
                $p_status = 0;
            }

            $entgelt = -1;
            $entgelt_type = 0;
            if (!empty($_POST['other_entgelt_' . $p_task])) {
                $entgelt = $nfo->phpize($p_entgelt);
                $entgelt_type = in_array($p_entgelt_type, ["0", "1"]) ? $p_entgelt_type : 0;
            }

            $entgelt = doubleval($entgelt);
            $entgelt_type = intval($entgelt_type);

            if (!$db->query("UPDATE project_tasks SET name = '$p_name', description = '$p_description', status = '$p_status', color = '$p_color', entgelt = $entgelt, entgelt_type = $entgelt_type WHERE ID = '" . $p_task . "' LIMIT 1")) {
                throw new Exception("Fehler beim Speichern.");
            }

            if ($db->affected_rows > 0) {
                echo "<div class=\"alert alert-success\">{$l['SUC4']}</div>";
                alog("project", "task_edit", $p_task, $_GET['id']);
            }
            unset($_POST);
        } catch (Exception $ex) {
            echo "<div class=\"alert alert-danger\">" . $ex->getMessage() . "</div>";
        }
    }

    if (isset($_POST['add_task'])) {
        try {
            foreach ($_POST as $k => $v) {
                $vari = "p_" . strtolower($k);
                $$vari = $db->real_escape_string($v);
            }

            if (!isset($p_name) || strlen($p_name) < 1) {
                throw new Exception($l['ERR9']);
            }

            if (!isset($p_color_new) || strlen($p_color_new) != 7) {
                $p_color_new = "333333";
            } else {
                $p_color_new = substr($p_color_new, 1);
            }

            if (!isset($p_description)) {
                $p_description = "";
            }

            if (!isset($p_status)) {
                $p_status = 0;
            }

            $entgelt = -1;
            $entgelt_type = 0;
            if (!empty($p_other_entgelt_new)) {
                $entgelt = $nfo->phpize($p_entgelt);
                $entgelt_type = in_array($p_entgelt_type, ["0", "1"]) ? $p_entgelt_type : 0;
            }

            $entgelt = doubleval($entgelt);
            $entgelt_type = intval($entgelt_type);

            if (!$db->query("INSERT INTO project_tasks (`project`, `name`, `description`, `status`, `color`, `entgelt`, `entgelt_type`) VALUES (" . $info->ID . ", '$p_name', '$p_description', '$p_status', '$p_color_new', $entgelt, $entgelt_type)")) {
                throw new Exception("Fehler beim Speichern.");
            }

            if ($db->affected_rows > 0) {
                echo "<div class=\"alert alert-success\">{$l['SUC5']}</div>";
                alog("project", "task_add", $db->insert_id, $_GET['id']);
            }
            unset($_POST);
        } catch (Exception $ex) {
            echo "<div class=\"alert alert-danger\">" . $ex->getMessage() . "</div>";
        }
    }

    if (isset($_POST['import_template']) && isset($_POST['template']) && $db->query("SELECT ID FROM project_templates WHERE ID = '" . $db->real_escape_string($_POST['template']) . "' LIMIT 1")->num_rows == 1) {
        $tinfo = $db->query("SELECT * FROM project_templates WHERE ID = '" . $db->real_escape_string($_POST['template']) . "' LIMIT 1")->fetch_object();

        $tasks = unserialize($tinfo->tasks);
        $price = unserialize($tinfo->price);
        foreach ($tasks as $k => $v) {
            $e = -1;
            $et = 0;

            if (is_array($price) && array_key_exists($k, $price)) {
                $e = $nfo->phpize($price[$k][0]);
                if ($price[$k][1]) {
                    $et = 1;
                }
            }

            $db->query("INSERT INTO project_tasks (`project`, `name`, `description`, `entgelt`, `entgelt_type`) VALUES ('" . $db->real_escape_string($_GET['id']) . "', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string($v) . "', $e, $et)");
        }

        if ($db->affected_rows > 0) {
            echo "<div class=\"alert alert-success\">" . str_replace("%n", $tinfo->name, $l['SUC6']) . "</div>";
            alog("project", "template_import", $_POST['template'], $tinfo->name, $_GET['id']);
        }
    }

    $info = $db->query("SELECT * FROM projects WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();

    if (isset($_POST['do_upload'])) {
        $sentFiles = $_FILES['upload_files'];
        $done = 0;
        foreach ($sentFiles['tmp_name'] as $k => $v) {
            if (is_uploaded_file($sentFiles['tmp_name'][$k])) {
                $rand = rand(10000000, 99999999);
                $filePath = __DIR__ . "/../../files/projects/" . $rand . "_" . basename($sentFiles['name'][$k]);
                if (move_uploaded_file($sentFiles['tmp_name'][$k], $filePath)) {
                    $fileArr = unserialize($info->files);
                    if (!$fileArr) {
                        $fileArr = array();
                    }

                    array_push($fileArr, $rand . "_" . basename($sentFiles['name'][$k]));
                    $info->files = serialize($fileArr);
                    $done++;
                    alog("project", "file_uploaded", basename($sentFiles['name'][$k]), $_GET['id']);
                }
            }
        }

        $db->query("UPDATE projects SET files = '" . $db->real_escape_string($info->files) . "' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1");

        if ($done == 1) {
            $suc = $l['SUC7O'];
        } else {
            $suc = str_replace("%d", $done, $l['SUC7X']);
        }

    } else if (isset($_GET['delete_file'])) {
        if (unserialize($info->files) !== false && in_array($_GET['delete_file'], unserialize($info->files))) {
            $arr = unserialize($info->files);
            $pos = array_search($_GET['delete_file'], $arr);
            unset($arr[$pos]);
            $info->files = serialize($arr);

            $db->query("UPDATE projects SET files = '" . $db->real_escape_string($info->files) . "' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1");
            unlink(__DIR__ . "/../../files/projects/" . str_replace("..", ".", $_GET['delete_file']));

            alog("project", "file_deleted", $_GET['id'], $_GET['delete_file']);

            $suc = $l['SUC8'];
        }
    } else if (isset($_GET['send_file'])) {
        if (unserialize($info->files) !== false && in_array($_GET['send_file'], unserialize($info->files))) {
            $fileName = substr(basename($_GET['send_file']), 9);
            $filePath = __DIR__ . '/../../files/projects/' . basename($_GET['send_file']);

            $uI = User::getInstance($info->user, "ID");
            if ($uI) {
                $t = new MailTemplate("Dateiversand");
                $title = $t->getTitle($uI->getLanguage());
                $mail = $t->getMail($uI->getLanguage(), $uI->get()['name']);

                $id = $maq->enqueue([
                    "file" => $fileName,
                ], $t, $uI->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $info->user, false, 0, 0, array($fileName => $filePath));
                $maq->send(1, $id, true, false);
                $maq->delete($id);

                alog("project", "file_sent", $_GET['id'], $fileName);

                $suc = $l['SUC9'];
            }
        }
    }

    if (isset($_GET['show_details']) && in_array($_GET['show_details'], array("0", "1"))) {
        $db->query("UPDATE projects SET show_details = '" . intval($_GET['show_details']) . "' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1");
        $info->show_details = $_GET['show_details'];
        alog("project", "show_details", $_GET['id'], $_GET['show_details']);
    }

    if (isset($_GET['status']) && in_array($_GET['status'], array("0", "1", "2", "3"))) {
        $db->query("UPDATE projects SET status = '" . intval($_GET['status']) . "' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1");
        $info->status = $_GET['status'];
        alog("project", "status", $_GET['id'], $_GET['status']);
    }
    ?>

	<?php if (isset($suc)) {?><div class="alert alert-success"><?=$suc;?></div><?php }?>

	<div class="modal fade" id="upload" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['UPLOADFILES'];?></h4>
      </div>
      <div class="modal-body">
		<form enctype="multipart/form-data" role="form" action="./?p=view_project&id=<?=$_GET['id'];?>" method="POST">
          <input type="file" class="form-control" name="upload_files[]" multiple>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
        <input type="hidden" name="task" value="<?=$r->ID;?>">
        <button type="submit" name="do_upload" class="btn btn-primary"><?=$l['DOUPLOAD'];?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<a href="?p=projects" class="btn btn-default btn-block">&laquo; <?=$l['BACKTOLIST'];?></a><br />

	<div class="panel-group" role="tablist" aria-multiselectable="true">
	  <div class="panel panel-default">
	    <div class="panel-heading" role="tab" id="headingOne">
	      <h4 class="panel-title">
	        <a role="button" data-toggle="collapse" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
	          <?=$l['PINFO'];?>
	        </a>
	      </h4>
	    </div>
	    <div id="collapseOne" class="panel-collapse in" role="tabpanel" aria-labelledby="headingOne">
	      <div class="panel-body">
	        <div class="table-responsive">
				<table class="table table-bordered table-striped" style="margin-bottom: 0;">
					<tr>
						<th><?=$l['TASKS'];?></th>
						<td><?=$db->query("SELECT COUNT(*) AS n FROM project_tasks WHERE status = 1 AND project = " . intval($_GET['id']))->fetch_object()->n;?> / <?=$db->query("SELECT COUNT(*) AS n FROM project_tasks WHERE project = " . intval($_GET['id']))->fetch_object()->n;?> <a data-toggle="modal" data-target="#new" href="#" class="btn btn-default btn-xs">Hinzuf&uuml;gen</a> <a data-toggle="modal" data-target="#template" href="#" class="btn btn-default btn-xs">Vorlage</a></td>
					</tr>

					<tr>
						<th><?=$l['DUE'];?></th>
						<td><?=$dfo->format($info->due, false);?></td>
					</tr>

					<?php if ($info->user > 0 && $obj = User::getInstance($info->user, "ID")) {$uInfo = (object) $obj->get();?>
					<tr>
						<th><?=$l['CUST'];?></th>
						<td><a href="?p=customers&edit=<?=$info->user;?>" class="btn btn-default btn-xs"><?=$obj->getfName() . (!empty($uInfo->company) ? " (" . htmlentities($uInfo->company) . ")" : "");?></a> <a href="?p=view_project&id=<?=$info->ID;?>&show_details=<?=$info->show_details ? 0 : 1;?>" class="btn btn-<?=$info->show_details ? 'primary' : 'default';?> btn-xs"><?=$l['VIEWRIGHTS'];?></a></td>
					</tr>
					<?php }?>

					<?php if ($info->admin > 0 && is_object($sql = $db->query("SELECT name FROM admins WHERE ID = " . $info->admin)) && $sql->num_rows == 1) {?>
					<tr>
						<th><?=$l['ADMIN'];?></th>
						<td><a href="?p=admin&id=<?=$info->admin;?>" class="btn btn-default btn-xs"><?=$sql->fetch_object()->name;?></a></td>
					</tr>
					<?php }?>

					<tr>
						<th><?=$l['STATUS'];?></th>
						<td>
							<a href="?p=view_project&id=<?=$info->ID;?>&status=2" class="btn btn-<?=$info->status == "2" ? 'primary' : 'default';?> btn-xs"><?=$l['S1'];?></a>
							<a href="?p=view_project&id=<?=$info->ID;?>&status=3" class="btn btn-<?=$info->status == "3" ? 'primary' : 'default';?> btn-xs"><?=$l['S2'];?></a>
							<a href="?p=view_project&id=<?=$info->ID;?>&status=0" class="btn btn-<?=$info->status == "0" ? 'primary' : 'default';?> btn-xs"><?=$l['S3'];?></a>
							<a href="?p=view_project&id=<?=$info->ID;?>&status=1" class="btn btn-<?=$info->status == "1" ? 'primary' : 'default';?> btn-xs"><?=$l['S4'];?></a>
						</td>
					</tr>

					<tr>
						<th><?=$l['PRODUCT'];?></th>
						<td><?php if ($info->product && is_object($sql = $db->query("SELECT name FROM products WHERE ID = {$info->product}")) && $sql->num_rows == 1) {$name = unserialize($sql->fetch_object()->name)[$CFG['LANG']];?><?=$name;?> // <?=$l['REVENUE'];?>: <?php
$sqlL = $db->query("SELECT ID, active FROM client_products WHERE product = " . $info->product);
        $price = 0.00;
        while ($r = $sqlL->fetch_object()) {
            $price += $db->query("SELECT SUM(amount) AS sum FROM invoiceitems WHERE relid = " . $r->ID)->fetch_object()->sum;
        }

        echo $cur->infix($nfo->format($price), $cur->getBaseCurrency());
        ?><?php } else {?><i><?=$l['NPA'];?></i><?php }?></td>
					</tr>

					<tr>
						<th><?=$l['ENTGELT'];?></th>
						<td><?=$cur->infix($nfo->format($info->entgelt), $cur->getBaseCurrency());?>
						<?=$info->entgelt_type == "1" ? "/ Stunde" : "pauschal";?> <?php if (count(Project::invoice($info->ID))) {?> <a href="./?p=new_invoice&user=<?=$info->user;?>&project=<?=$info->ID;?>" class="btn btn-default btn-xs"><?=$l['CREATEINV'];?></a><?php }?></td>
					</tr>

					<?php
$gesTime = 0;
    $sql = $db->query("SELECT * FROM project_tasks WHERE project = " . $info->ID . " ORDER BY ID = " . intval(Project::working()) . " DESC, status ASC, color != '' DESC, color != '333333' DESC, color ASC, name ASC");
    $tasks = [];

    while ($r = $sql->fetch_object()) {
        $timesSql = $db->query("SELECT `start`, `end` FROM project_times WHERE task = " . $r->ID);
        $r->rawTime = 0;

        if ($timesSql->num_rows) {
            while ($t = $timesSql->fetch_object()) {
                if ($t->end == "0000-00-00 00:00:00") {
                    $t->end = date("Y-m-d H:i:s", time() + 1);
                }

                if (($subTime = strtotime($t->end) - strtotime($t->start)) > 0) {
                    $r->rawTime += $subTime;
                }

            }

            $gesTime += $r->rawTime;
        }

        array_push($tasks, $r);
    }

    $timesSql = $db->query("SELECT `start`, `end` FROM project_times WHERE task = -" . $info->ID);
    $projRawTime = 0;

    if ($timesSql->num_rows) {
        while ($t = $timesSql->fetch_object()) {
            if ($t->end == "0000-00-00 00:00:00") {
                $t->end = date("Y-m-d H:i:s", time() + 1);
            }

            if (($subTime = strtotime($t->end) - strtotime($t->start)) > 0) {
                $projRawTime += $subTime;
            }

        }

        $gesTime += $projRawTime;
    }
    ?>

					<tr>
						<th><?=$l['TIMEA'];?></th>
						<td><span id="gesTimec"><?=Project::time($gesTime, $gesTime > 0);?> <a href="?p=project_time_pdf&id=<?=$_GET['id'];?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a></span><input type="hidden" id="gesTime" value="<?=$gesTime;?>" /></td>
					</tr>

					<?php if ($info->entgelt_type != "1") {
        $workMinutes = 0;

        $timesSql = $db->query("SELECT `start`, `end` FROM project_times a INNER JOIN project_tasks b ON b.ID = a.task WHERE b.project = " . $info->ID);

        while ($t = $timesSql->fetch_object()) {
            if ($t->end == "0000-00-00 00:00:00") {
                continue;
            }

            if (($subTime = strtotime($t->end) - strtotime($t->start)) > 0) {
                $workMinutes += $subTime / 60;
            }

        }

        $workMinutes = round($workMinutes);
        if ($workMinutes > 0) {
            ?>
					<tr>
						<th><?=$l['HOURRATE'];?></th>
						<td><?=$cur->infix($nfo->format($info->entgelt / $workMinutes * 60), $cur->getBaseCurrency());?></td>
					</tr>
					<?php }}?>

					<tr>
						<th style="vertical-align: middle;"><?=$l['FILES'];?> <a data-toggle="modal" href="#" data-target="#upload" class="btn btn-default btn-xs">+</a></th>
						<td>
							<?php
$files = unserialize($info->files);
    $expire_dates = unserialize($info->files_expiry);
    $i = 0;
    if (is_array($files) && count($files) > 0) {
        foreach ($files as $file) {
            $fileName = substr($file, 9);
            $link = "./?p=view_project&id=" . $_GET['id'] . "&file=" . urlencode($file);
            $deleteLink = "./?p=view_project&id=" . $_GET['id'] . "&delete_file=" . urlencode($file);
            $sendLink = "./?p=view_project&id=" . $_GET['id'] . "&send_file=" . urlencode($file);

            $expiry = $l['DNE'];
            $expiry_raw = "";
            if (array_key_exists($file, $expire_dates)) {
                $expiry_raw = date("d.m.Y", $expire_dates[$file]);
                $expiry = $l['VALIDUNTIL'] . " " . $expiry_raw;
            }

            if ($i++) {
                echo '<br /><div style="height: 5px;">&nbsp;</div>';
            }

            echo '<a href="' . $deleteLink . '" onClick="return confirm(\'' . $l['REALLYDELFILE'] . '\');" class="btn btn-danger btn-xs">' . $l['DEL'] . '</a> <span style="position: relative;"><a href="#" class="btn btn-warning btn-xs extimepicker" data-file="' . $file . '">' . $expiry . '</a><input type="text" style="width: 0.1px; display: inline-block; height: 0.1px; border: none !important;" class="datepicker extp" data-fid="' . $file . '" value="' . $expiry_raw . '"></span> <a href="' . $sendLink . '" class="btn btn-default btn-xs">' . $l['SENDVIAMAIL'] . '</a> <a href="' . $link . '" target="_blank">' . $fileName . '</a>';
        }
    } else {echo "<i>{$l['NOFILES']}</i>";}?>

							<script>
							var waiting_html = '<i class="fa fa-spinner fa-pulse"></i> <?=$l['PW'];?>';

							$(".extimepicker").click(function(e) {
								e.preventDefault();
								if ($(this).html() == waiting_html) {
									return;
								}

								var id = $(this).data("file");
								$("[data-fid='" + id + "']").trigger('focus');
							});

							$(".extp").on('dp.hide', function() {
								var btn = $(this).parent().find(".extimepicker");

								if (btn.html() == waiting_html) {
									return;
								}

								btn.html(waiting_html);

								$.post("", {
									"file": $(this).data("fid"),
									"expire": $(this).val(),
									"csrf_token": "<?=CSRF::raw();?>",
								}, function(r) {
									btn.html(r);
								});
							});
							</script>
						</td>
					</tr>
				</table>
			</div>
	      </div>
	    </div>
	  </div>
	  <div class="panel panel-default">
	    <div class="panel-heading" role="tab" id="headingTwo">
	      <h4 class="panel-title">
	        <a role="button" data-toggle="collapse" href="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
	          <?=$l['TASKS'];?>
	        </a>
	      </h4>
	    </div>
	    <div id="collapseTwo" class="panel-collapse in" role="tabpanel" aria-labelledby="headingTwo">
	      <div class="panel-body">
	        <div class="table table-responsive">
				<table class="table table-bordered table-striped" style="margin-bottom: 0;">
					<?php ob_start();?>
					<form method="POST">
					<?php

    if (count($tasks) > 0) {
        foreach ($tasks as $r) {
            $style = "";
            if ($r->status == 1) {
                $style = "background-color: palegreen !important;";
            }

            $kurzDesc = htmlentities(str_replace(array("<br>", "<br />", "<br/>"), " ", nl2br(strip_tags($r->description))));
            if (strlen($kurzDesc) <= 30) {
                $desc = make_clickable($kurzDesc);
            } else {
                $desc = '<a data-toggle="modal" href=\"#\" data-target="#desc_' . $r->ID . '">' . trim(htmlentities(substr($kurzDesc, 0, 30))) . '...</a>';
            }

            ob_start();
            ?>
		<div class="modal fade modal-edit" id="edit_<?=$r->ID;?>" tabindex="-1" role="dialog" aria-labelledby="editLabel" aria-hidden="true">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
		        <h4 class="modal-title"><?=$l['EDITTASK'];?></h4>
		      </div>
		      <div class="modal-body">
				<form role="form" action="./?p=view_project&id=<?=$_GET['id'];?>" method="POST">
				  <div class="form-group">
		            <label class="control-label"><?=$l['TASK'];?></label>
		            <div class="input-group" id="colorpicker_<?=$r->ID;?>">
		            	<span class="input-group-addon"><i></i></span>
		            	<input type="text" class="form-control" placeholder="<?=$l['NAMEOFTASK'];?>" value="<?=isset($_POST['name']) ? htmlentities($_POST['name']) : htmlentities($r->name);?>" name="name">
		          		<input type="hidden" name="color" id="color_<?=$r->ID;?>" value="#<?=!empty($r->color) ? $r->color : "333333";?>" />
		            </div>

		            <?php
$var['additionalJS'] .= "$('#colorpicker_{$r->ID}').colorpicker({
		            	format: 'hex',
		            	input: '#color_{$r->ID}',
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
		          <div class="form-group">
		            <label class="control-label"><?=$l['DESC'];?></label>
		            <textarea name="description" style="resize: none; height:150px;" class="form-control"><?=isset($_POST['description']) ? htmlentities($_POST['description']) : nl2br($r->description);?></textarea>
		          </div>
				   <div class="checkbox">
					<label>
					  <input type="checkbox" name="status" value="1" <?=(isset($_POST['status']) || (!isset($_POST['save_task']) && $r->status == 1)) ? "checked" : "";?>> <?=$l['TASKDONE'];?>
					</label>
					</div>
					<div class="checkbox">
			<label>
			  <input type="checkbox" name="other_entgelt_<?=$r->ID;?>" value="1"<?=!empty($_POST['other_entgelt_' . $r->ID]) || (!isset($_POST['save_task']) && $r->entgelt != -1) ? ' checked=""' : '';?>> <?=$l['OTHER_ENTGELT'];?>
			</label>
			</div>

			<div id="other_entgelt_<?=$r->ID;?>" style="display: none;">
				<div class="form-group">
					<label><?=$l['ENTGELT'];?></label><br />
					<div><input type="text" size="5" name="entgelt" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder();?>" class="form-control" value="<?php if (isset($_POST['entgelt'])) {
                echo $_POST['entgelt'];
            } else if ($r->entgelt != -1) {
                echo $nfo->format($r->entgelt);
            } else {
                echo $nfo->format($info->entgelt);
            }
            ?>">
					<select name="entgelt_type" class="form-control" style="display: inline; max-width: 150px;">
						<option value="0"><?=$l['PAUSCH'];?></option>
						<option value="1"<?php if ((isset($_POST['entgelt_type']) && $_POST['entgelt_type'] == "1") || (!isset($_POST['entgelt_type']) && $r->entgelt_type == "1")) {
                echo ' selected="selected"';
            }
            ?>><?=$l['PERHOUR'];?></option>
					</select></div>
				</div>
			</div>

			<script>
			$(document).ready(function() {
				function other_entgelt_<?=$r->ID;?>() {
					if ($("[name=other_entgelt_<?=$r->ID;?>]").is(":checked")) {
						$("#other_entgelt_<?=$r->ID;?>").show();
					} else {
						$("#other_entgelt_<?=$r->ID;?>").hide();
					}
				}

				other_entgelt_<?=$r->ID;?>();
				$("[name=other_entgelt_<?=$r->ID;?>]").change(other_entgelt_<?=$r->ID;?>);
			});
			</script>
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
		        <input type="hidden" name="task" value="<?=$r->ID;?>">
		        <button type="submit" name="save_task" class="btn btn-primary"><?=$l['SAVETASK'];?></button>
		        </form>
		      </div>
	      </div>
	    </div>
	  </div></div>

			<?php
$modals .= ob_get_contents();
            ob_end_clean();

            $time = Project::time($rawTime = $r->rawTime, $rawTime > 0);
            ?>

			<tr>
				<td style="<?=$style;?>"><input type="checkbox" class="checkbox" name="task[]" value="<?=$r->ID;?>" onchange="javascript:toggle();" /></td>
				<td style="<?=$style;?>"><font color="#<?=!empty($r->color) && $r->status != 1 ? $r->color : "333";?>"><?=make_clickable(htmlentities($r->name));?></font></td>
				<td style="<?=$style;?>"><?=$desc;?></td>
				<td style="<?=$style;?>">
					<?php
if ($r->entgelt == -1) {
                if ($info->entgelt_type == "1") {
                    echo $cur->infix($nfo->format($info->entgelt), $cur->getBaseCurrency()) . " / " . $l['HOUR'];
                } else {
                    echo $cur->infix($nfo->format(0), $cur->getBaseCurrency());
                }
            } else {
                echo $cur->infix($nfo->format($r->entgelt), $cur->getBaseCurrency());
                if ($r->entgelt_type == "1") {
                    echo " / Stunde";
                }
            }
            ?>
				</td>
				<td style="<?=$style;?>">
					<a href="#" data-toggle="modal" data-target="#time_modal" onclick="loadTaskTime(<?=$r->ID;?>, true); return false;" id="time_link_<?=$r->ID;?>"><?=$time;?></a>

                    <input type="hidden" id="rawtime_<?=$r->ID;?>" value="<?=$rawTime;?>" />
					<span id="start_btn_<?=$r->ID;?>" class="start_btn"<?=Project::working() !== false ? " style=\"display: none;\"" : "";?>>&nbsp;&nbsp;<a href="#" onclick="startTaskTime(<?=$r->ID;?>); return false;"><i class="fa fa-clock-o"></i></a></span>
				    <span id="stop_btn_<?=$r->ID;?>" class="stop_btn"<?=Project::working() !== $r->ID ? " style=\"display: none;\"" : "";?>>&nbsp;&nbsp;<a href="#" onclick="pauseTaskTime(<?=$r->ID;?>); return false;"><i class="fa fa-pause" style="color: orange;"></i></a>
				</td>
				<td style="<?=$style;?>"><a data-toggle="modal" href="#" data-target="#edit_<?=$r->ID;?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;<?php if ($r->status == 0) {?><a href="?p=view_project&id=<?=$r->project;?>&ok=<?=$r->ID;?>"><i class="fa fa-check-square-o"></i></a>&nbsp;&nbsp;<?php } else {?>&nbsp;&nbsp;<span style="margin-left: 5px;"></span>&nbsp;&nbsp;<?php }?><a onclick="return confirm('<?=$l['REALLYDELTASK'];?>');" href="?p=view_project&id=<?=$r->project;?>&del=<?=$r->ID;?>"><i class="fa fa-minus-square-o"></i></a></td>
			</tr>

			<?php if (strlen($kurzDesc) > 30) {?>

<div class="modal fade" id="desc_<?=$r->ID;?>" tabindex="-1" role="dialog" aria-labelledby="editLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
        <h4 class="modal-title"><?=$l['TASK'];?>: <?=make_clickable($r->name);?></h4>
      </div>
      <div class="modal-body">
		<?=make_clickable(nl2br(htmlentities($r->description)));?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
      </div>
    </div>
  </div>
</div>

			<?php }}
    } else {?>
			<tr><td colspan="6"><center><?=$l['NOTASKS'];?></center></td></tr>
			<?php }?>
			<?php $res = ob_get_contents();
    ob_end_clean();?>

			<tr>
				<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
				<th width="30%"><?=$l['TASK'];?></th>
				<th width="35%"><?=$l['DESC'];?></th>
				<th><?=$l['ENTGELT'];?></th>
				<th>
					<a href="#" data-toggle="modal" data-target="#time_modal" onclick="loadTaskTime(-<?=$info->ID;?>, true); return false;" id="time_link_-<?=$info->ID;?>"><?=Project::time($projRawTime, $projRawTime > 0);?></a>
					<input type="hidden" id="rawtime_-<?=$info->ID;?>" value="<?=$projRawTime;?>" />
					<span id="start_btn_-<?=$info->ID;?>" class="start_btn"<?=Project::working() !== false ? " style=\"display: none;\"" : "";?>>&nbsp;&nbsp;<a href="#" onclick="startTaskTime(-<?=$info->ID;?>); return false;"><i class="fa fa-clock-o"></i></a></span>
				   <span id="stop_btn_-<?=$info->ID;?>" class="stop_btn"<?=Project::working() !== "-" . $info->ID ? " style=\"display: none;\"" : "";?>>&nbsp;&nbsp;<a href="#" onclick="pauseTaskTime(-<?=$info->ID;?>); return false;"><i class="fa fa-pause" style="color: orange;"></i></a>
				</th>
				<th width="73px"></th>
			</tr>
            <input type="hidden" id="working_project" value="<?=(int) Project::working();?>" />

			<?=$res;?>
		</table>
	</div><?=$l['SELECTED'];?>: <input type="submit" name="done_selected" value="<?=$l['MARKDONE'];?>" class="btn btn-success" /> <input type="submit" name="undone_selected" value="<?=$l['MARKUNDONE'];?>" class="btn btn-warning" /> <input type="submit" name="delete_selected" value="<?=$l['DEL'];?>" class="btn btn-danger" /></form>
	</div>
</div>

<?=$modals;?>

<form method="POST" action="./?p=view_project&id=<?=$_GET['id'];?>">
<div class="modal fade" id="edit" tabindex="-1" role="dialog" aria-labelledby="editLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['EDITPROJ'];?></h4>
      </div>
      <div class="modal-body">
          <div class="form-group">
            <label class="control-label"><?=$l['PROJNAME'];?></label>
            <input type="text" class="form-control" placeholder="<?=$l['PROJNAMEP'];?>" value="<?=isset($_POST['name']) ? $_POST['name'] : $info->name;?>" name="name">
          </div>
					<div class="form-group">
						<label><?=$l['DESC'];?></label>
						<textarea name="description" class="form-control" placeholder="<?=$l['OPTIONAL'];?>" style="width: 100%; height: 120px; resize: vertical;"><?php if (isset($_POST['description'])) {
        echo htmlentities($_POST['description']);
    } else {
        echo htmlentities($info->description);
    }
    ?></textarea>
					</div>
          <div class="form-group">
            <label class="control-label"><?=$l['DUE'];?></label>
            <input type="text" class="form-control datepicker" placeholder="<?=$l['EG'];?> <?=$dfo->placeholder(false);?>" value="<?=isset($_POST['due']) ? $_POST['due'] : $dfo->format(strtotime($info->due), false);?>" name="due">
          </div>
          <div class="form-group">
		    <label><?=$l['ENTGELT'];?></label><br />
		   	<div><input type="text" size="5" name="entgelt" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder();?>" class="form-control" value="<?php if (isset($_POST['entgelt'])) {
        echo $_POST['entgelt'];
    } else {
        echo $nfo->format($info->entgelt);
    }
    ?>">
		   	<select name="entgelt_type" class="form-control" style="display: inline; max-width: 150px;">
		   		<option value="0"><?=$l['PAUSCH'];?></option>
		   		<option value="1"<?php if ((isset($_POST['entgelt_type']) && $_POST['entgelt_type'] == "1") || (!isset($_POST['entgelt_type']) && $info->entgelt_type == "1")) {
        echo ' selected="selected"';
    }
    ?>><?=$l['PERHOUR'];?></option>
		   	</select></div>
			</div>
			<?php
$tt = isset($_POST['time_tracking']) ? $_POST['time_tracking'] : $info->time_tracking;
    ?>
			<div class="form-group">
				<label><?=$l['TTPT'];?></label>
				<select name="time_tracking" class="form-control">
					<option value="exact"><?=$l['TT1'];?></option>
					<option value="ceil10"<?=$tt == "ceil10" ? ' selected=""' : '';?>><?=$l['TT2'];?></option>
					<option value="floor10"<?=$tt == "floor10" ? ' selected=""' : '';?>><?=$l['TT3'];?></option>
					<option value="ceil15"<?=$tt == "ceil15" ? ' selected=""' : '';?>><?=$l['TT4'];?></option>
					<option value="floor15"<?=$tt == "floor15" ? ' selected=""' : '';?>><?=$l['TT5'];?></option>
					<option value="ceil30"<?=$tt == "ceil30" ? ' selected=""' : '';?>><?=$l['TT6'];?></option>
					<option value="floor30"<?=$tt == "floor30" ? ' selected=""' : '';?>><?=$l['TT7'];?></option>
					<option value="ceil60"<?=$tt == "ceil60" ? ' selected=""' : '';?>><?=$l['TT8'];?></option>
					<option value="floor60"<?=$tt == "floor60" ? ' selected=""' : '';?>><?=$l['TT9'];?></option>
				</select>
			</div>

			<div class="form-group">
				<label><?=$l['INCMIN'];?></label>
				<input type="text" class="form-control customer-input" placeholder="0" value="<?=isset($_POST["time_contingent"]) ? $_POST["time_contingent"] : $info->time_contingent;?>" name="time_contingent">
			</div>

			<div class="form-group">
				<label><?=$l['CUST'];?></label>
				<input type="text" class="form-control customer-input" placeholder="<?=$l['NOCUST'];?>" value="<?=ci(!empty($_POST["user"]) ? $_POST["user"] : $info->user);?>">
				<input type="hidden" name="user" value="<?=!empty($_POST["user"]) ? $_POST["user"] : $info->user;?>">
				<div class="customer-input-results"></div>
			</div>

          <?php
$pSql = $db->query("SELECT * FROM products");
    ?>
		  <div class="form-group">
            <label class="control-label"><?=$l['PRODUCT'];?></label>
            <select name="product" class="form-control">
				<option value="0"><?=$l['NOCUST'];?></option>
				<?php if ($pSql->num_rows > 0) {?>
				<option disabled>--------------------</option>
				<?php }
    $pro = array();
    while ($c = $pSql->fetch_object()) {
        $pro[$c->ID] = unserialize($c->name)[$CFG['LANG']];
    }

    asort($pro);
    foreach ($pro as $id => $name) {$c = (object) array("ID" => $id, "name" => $name)?>
				<option value="<?=$c->ID;?>" <?php if (isset($_POST['product']) && $_POST['product'] == $c->ID) {
        echo "selected=\"selected\"";
    } else if (!isset($_POST['product']) && $c->ID == $info->product) {
        echo "selected=\"selected\"";
    }
        ?>><?=$c->name;?></option>
				<?php }?>
			</select>
          </div>

          <div class="checkbox">
			<label>
			  <input type="checkbox" name="show_details" value="1" <?=(!isset($_POST['submit']) && $info->show_details == 1) || (isset($_POST['submit']) && isset($_POST['show_details'])) ? "checked" : "";?>> <?=$l['CUSTCANVIEW'];?>
			</label>
		  </div>

		  <?php
$aSql = $db->query("SELECT * FROM admins ORDER BY name");
    ?>
		  <div class="form-group">
            <label class="control-label"><?=$l['ADMIN'];?></label>
            <select name="admin" class="form-control">
				<option value="0"><?=$l['NOCUST'];?></option>
				<?php if ($aSql->num_rows > 0) {?>
				<option disabled>--------------------</option>
				<?php }
    while ($c = $aSql->fetch_object()) {?>
				<option value="<?=$c->ID;?>" <?php if (isset($_POST['admin']) && $_POST['admin'] == $c->ID) {
        echo "selected=\"selected\"";
    } else if (!isset($_POST['admin']) && $c->ID == $info->admin) {
        echo "selected=\"selected\"";
    }
        ?>><?=$c->name;?> (<?=$c->username;?>)</option>
				<?php }?>
			</select>
          </div>

          <div class="form-group">
          	<?php $status = isset($_POST['submit']) ? $_POST['status'] : $info->status;?>
            <label class="control-label"><?=$l['STATUS'];?></label>
            <select name="status" class="form-control">
            	<option value="2"<?=$status == "2" ? ' selected="selected"' : "";?>><?=$l['S1'];?></option>
				<option value="3"<?=$status == "3" ? ' selected="selected"' : "";?>><?=$l['S2'];?></option>
				<option value="0"<?=$status == "0" ? ' selected="selected"' : "";?>><?=$l['S3'];?></option>
				<option value="1"<?=$status == "1" ? ' selected="selected"' : "";?>><?=$l['S4'];?></option>
			</select>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
        <button type="submit" name="save_data" class="btn btn-primary"><?=$l['SAVEPROJ'];?></button>
      </div>
    </div>
  </div>
</div>
</form>

<form action="./?p=view_project&id=<?=$_GET['id'];?>" method="POST">
<div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="editLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['ADDTASK'];?></h4>
      </div>
      <div class="modal-body">
		  <div class="form-group">
            <label class="control-label"><?=$l['TASK'];?></label>
            <div class="input-group" id="colorpicker_new">
            	<span class="input-group-addon"><i></i></span>
            	<input type="text" class="form-control" placeholder="<?=$l['NAMEOFTASK'];?>" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>" name="name">
          		<input type="hidden" name="color_new" id="color_new" value="#<?=isset($_POST['color_new']) ? $_POST['color_new'] : "333333";?>" />
            </div>

            <?php
$var['additionalJS'] .= "$('#colorpicker_new').colorpicker({
            	format: 'hex',
            	input: '#color_new',
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
          <div class="form-group">
            <label class="control-label"><?=$l['DESC'];?></label>
            <textarea name="description" style="resize: none; height:150px;" class="form-control"><?=isset($_POST['description']) ? $_POST['description'] : "";?></textarea>
          </div>
		   <div class="checkbox">
			<label>
			  <input type="checkbox" name="status" value="1" <?=isset($_POST['status']) ? "checked" : "";?>> <?=$l['TASKDONE'];?>
			</label>
			</div>
			<div class="checkbox">
			<label>
			  <input type="checkbox" name="other_entgelt_new" value="1"<?=!empty($_POST['other_entgelt_new']) ? ' checked=""' : '';?>> <?=$l['OTHER_ENTGELT'];?>
			</label>
			</div>

			<div id="other_entgelt_new_container" style="display: none;">
				<div class="form-group">
					<label><?=$l['ENTGELT'];?></label><br />
					<div><input type="text" size="5" name="entgelt" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder();?>" class="form-control" value="<?php if (isset($_POST['entgelt'])) {
        echo $_POST['entgelt'];
    } else {
        echo $nfo->format($info->entgelt);
    }
    ?>">
					<select name="entgelt_type" class="form-control" style="display: inline; max-width: 150px;">
						<option value="0"><?=$l['PAUSCH'];?></option>
						<option value="1"<?php if ((isset($_POST['entgelt_type']) && $_POST['entgelt_type'] == "1") || (!isset($_POST['entgelt_type']) && $info->entgelt_type == "1")) {
        echo ' selected="selected"';
    }
    ?>><?=$l['PERHOUR'];?></option>
					</select></div>
				</div>
			</div>

			<script>
			$(document).ready(function() {
				function other_entgelt_new() {
					if ($("[name=other_entgelt_new]").is(":checked")) {
						$("#other_entgelt_new_container").show();
					} else {
						$("#other_entgelt_new_container").hide();
					}
				}

				other_entgelt_new();
				$("[name=other_entgelt_new]").change(other_entgelt_new);
			});
			</script>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
        <button type="submit" name="add_task" class="btn btn-primary"><?=$l['ADDTASK'];?></button>
      </div>
    </div>
  </div>
</div>
</form>

<form action="./?p=view_project&id=<?=$_GET['id'];?>" method="POST">
<div class="modal fade" id="template" tabindex="-1" role="dialog" aria-labelledby="editLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['IMPORTTPL'];?></h4>
      </div>
      <div class="modal-body">
          <div class="form-group">
            <label class="control-label"><?=$l['TPL'];?></label>
            <?php
$sql = $db->query("SELECT * FROM project_templates");
    if ($sql->num_rows == 0) {
        ?><br /><?=$l['NOTPL'];?><?php
} else {
        ?>
			<select name="template" class="form-control">
			<?php while ($r = $sql->fetch_object()) {?>
			<option value="<?=$r->ID;?>"><?=$r->name;?></option>
			<?php }?>
			</select>
			<?php
}
    ?>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
        <button type="submit" name="import_template" class="btn btn-primary" <?=$sql->num_rows == 0 ? "disabled" : "";?>><?=$l['IMPORT'];?></button>
      </div>
    </div>
  </div>
</div>
</form>

<div class="modal fade" id="time_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE'];?></span></button>
        <h4 class="modal-title"><?=$l['TIMEINV'];?>: <span id="task_name"></span></span></h4>
      </div>
      <div class="modal-body">
		<div class="table-responsive">
			<table class="table table-bordered table-striped" style="margin-bottom: 0;">
				<tr>
					<th width="20%"><?=$l['TIMESTART'];?></th>
					<th width="20%"><?=$l['TIMEEND'];?></th>
					<th width="40%"><?=$l['TIMEDUR'];?></th>
					<th><?=$l['TIMESTAFF'];?></th>
					<th width="30px"></th>
				</tr>

				<tr id="taskTimeWaiting">
					<td colspan="5"><center><i><?=$l['PW'];?></i></center></td>
				</tr>

				<tr id="taskTimeTableNew">
					<td style="position: relative;"><input class="form-control input-sm datetimepicker" type="text" id="from" onchange="calculateEnteredTime();" placeholder="<?=$dfo->placeholder(true, true, '');?>" /></td>
					<td style="position: relative;"><input class="form-control input-sm datetimepicker" type="text" id="to" onchange="calculateEnteredTime();" placeholder="<?=$dfo->placeholder(true, true, '');?>" /></td>
					<td><span id="new_task_duration_default"><i><?=$l['NVT'];?></i></span><span id="new_task_duration"></span></td>
					<td>
						<select class="form-control input-sm" id="staff">
							<option disabled="disabled" selected="selected"><?=$l['PCSM'];?></option>
							<?php
$adminSql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
    while ($row = $adminSql->fetch_object()) {
        echo "<option value=\"{$row->ID}\">{$row->name}</option>";
    }

    ?>
						</select>
					</td>
					<td>
						<input type="hidden" id="task_id" value="0" />
						<a href="#" onclick="addTaskTime(); return false;"><i class="fa fa-plus-square-o"></i></a>
					</td>
				</tr>
			</table>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
      </div>
    </div>
  </div>
</div>

<?php }?>