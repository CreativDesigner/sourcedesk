<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['PROJECTS'];
title($l['TITLE']);

if (!$ari->check(30)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "projects");} else {

    if ($ari->check(31) && isset($_GET['ok'])) {
        $db->query("UPDATE projects SET status = 1 WHERE status = 0 AND ID = '" . $db->real_escape_string($_GET['ok']) . "' LIMIT 1");
        alog("project", "done", $_GET['ok']);
        $suc = $l['SUC1'];
    }

    if ($ari->check(31) && isset($_POST['change_status']) && is_array($_POST['project'])) {
        $status = isset($_POST['change_status']) && is_numeric($_POST['change_status']) && $_POST['change_status'] >= 0 && $_POST['change_status'] <= 3 ? intval($_POST['change_status']) : null;

        if ($status !== null) {
            $d = 0;
            foreach ($_POST['project'] as $id) {
                $db->query("UPDATE projects SET status = $status WHERE status != $status AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("project", "change_status", $status, $id);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC2'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC2X']);
            }

        }
    }

    if ($ari->check(31) && isset($_POST['set_duedate']) && isset($_POST['duedate']) && is_array($_POST['project'])) {
        if (strtotime($_POST['duedate']) !== false) {
            $t = date("Y-m-d", strtotime($_POST['duedate']));
            $d = 0;
            foreach ($_POST['project'] as $id) {
                $db->query("UPDATE projects SET due = '$t' WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("project", "set_duedate", $t, $id);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC3'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC3X']);
            }

        }
    }

    if ($ari->check(32) && isset($_GET['del'])) {
        $db->query("DELETE FROM  projects WHERE ID = '" . $db->real_escape_string($_GET['del']) . "' LIMIT 1");
        $db->query("DELETE FROM  project_tasks WHERE project = '" . $db->real_escape_string($_GET['del']) . "'");
        alog("project", "delete", $_GET['del']);
        $suc = $l['SUC4'];
    }

    if ($ari->check(32) && isset($_GET['star'])) {
        $db->query("UPDATE projects SET star = 1 WHERE ID = " . intval($_GET['star']));
        alog("project", "star", $_GET['del']);
    }
    if ($ari->check(32) && isset($_GET['unstar'])) {
        $db->query("UPDATE projects SET star = 0 WHERE ID = " . intval($_GET['unstar']));
        alog("project", "unstar", $_GET['del']);
    }

    if ($ari->check(32) && isset($_POST['delete_selected']) && is_array($_POST['project'])) {
        $d = 0;
        foreach ($_POST['project'] as $id) {
            $db->query("DELETE FROM  projects WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                $db->query("DELETE FROM  project_tasks WHERE project = '" . $db->real_escape_string($id) . "'");
                alog("project", "delete", $id);
            }
        }

        if ($d == 1) {
            $suc = $l['SUC5'];
        } else if ($d > 0) {
            $suc = str_replace("%d", $d, $l['SUC5X']);
        }

    }

    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header">
				<?=$l['TITLE'];?>

				<?php if ($ari->check(31)) {?>
				<a href="?p=add_project" class="pull-right"><i class="fa fa-plus-circle"></i></a>
				<?php }?>
				<?php if ($ari->check(46)) {?><small><a href="?p=ptask_templates"><?=$l['PTT'];?></a></small><?php }?>
			</h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>

	<?php if (isset($suc)) {?><div class="alert alert-success"><?=$suc;?></div><?php }?>

	<?php
$type = isset($_GET['type']) ? $_GET['type'] : "working";
    ?>

	<ul class="nav nav-pills nav-justified">
	  <li<?php if ($type == "working") {?> class="active"<?php }?>><a href="?p=projects&type=working"><?=$l['S1'];?> (<?=$db->query("SELECT 1 FROM projects WHERE status = 0")->num_rows;?>)</a></li>
	  <li<?php if ($type == "due") {?> class="active"<?php }?>><a href="?p=projects&type=due"><?=$l['S2'];?> (<?=$db->query("SELECT 1 FROM projects WHERE status = 0 AND due < '" . date("Y-m-d") . "'")->num_rows;?>)</a></li>
	  <li<?php if ($type == "waiting") {?> class="active"<?php }?>><a href="?p=projects&type=waiting"><?=$l['S3'];?> (<?=$db->query("SELECT 1 FROM projects WHERE status = 2")->num_rows;?>)</a></li>
	  <li<?php if ($type == "info") {?> class="active"<?php }?>><a href="?p=projects&type=info"><?=$l['S4'];?> (<?=$db->query("SELECT 1 FROM projects WHERE status = 3")->num_rows;?>)</a></li>
	  <li<?php if ($type == "finished") {?> class="active"<?php }?>><a href="?p=projects&type=finished"><?=$l['S5'];?> (<?=$db->query("SELECT 1 FROM projects WHERE status = 1")->num_rows;?>)</a></li>
	</ul><br />

	<?php
if ($type == "working") {
        $where .= "status = 0";
    } else if ($type == "due") {
        $where .= "status = 0 AND due < '" . date("Y-m-d") . "'";
    } else if ($type == "waiting") {
        $where .= "status = 2";
    } else if ($type == "info") {
        $where .= "status = 3";
    } else if ($type == "finished") {
        $where .= "status = 1";
    }

    $t = new Table("SELECT * FROM projects WHERE $where", [
        "name" => [
            "name" => $l['NAME'],
            "type" => "like",
        ],
    ]);

    echo $t->getHeader();
    ?>

	<form method="POST" class="form-inline" id="project_form" style="position: relative;">

	<div class="table table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
			<th width="30px"></th>
			<th width="50px"><center>#</center></th>
			<th><?=$l['DUE'];?></th>
			<th><?=$l['NAME'];?></th>
			<th><?=$l['CUST'];?></th>
			<th><?=$l['ADMIN'];?></th>
			<th><?=$l['EXERCISES'];?></th>
			<th><?=$l['LOAN'];?></th>
			<?php if ($ari->check(31) || $ari->check(32)) {?><th width="80px"></th><?php }?>
		</tr>
		<?php $e = false;

    $star = $type != "finished" ? ' star DESC,' : "";
    $sql = $t->qry("$star status ASC, due ASC");
    $sum = $sum_h = $i_h = $real_projects = $real_sum = 0;

    while ($r = $sql->fetch_object()) {$e = true;

        if ($r->entgelt_type != "1") {
            $sum += $r->entgelt;

            $workMinutes = 0;

            $timesSql = $db->query("SELECT `start`, `end` FROM project_times a INNER JOIN project_tasks b ON b.ID = a.task WHERE b.project = " . $r->ID);

            while ($ti = $timesSql->fetch_object()) {
                if ($ti->end == "0000-00-00 00:00:00") {
                    continue;
                }

                if (($subTime = strtotime($ti->end) - strtotime($ti->start)) > 0) {
                    $workMinutes += $subTime / 60;
                }

            }

            $workMinutes = round($workMinutes);
            if ($workMinutes > 0) {
                $real_sum += $r->entgelt / $workMinutes * 60;
                $real_projects++;
            }
        } else {
            $sum_h += $r->entgelt;
            $i_h++;
        }

        $cus = "<i>{$l['NA']}</i>";
        if ($r->user != 0) {
            $userSql = $db->query("SELECT ID FROM clients WHERE ID = " . $r->user . " LIMIT 1");
            if ($userSql->num_rows == 1) {
                $user = $userSql->fetch_object();
                $cus = '<a href="?p=customers&edit=' . $r->user . '">' . User::getInstance($user->ID, "ID")->getfName() . '</a>';
            }
        }

        $style = "";
        if ($r->status == 1) {
            $style = "background-color:palegreen !important;";
        } else if (strtotime($r->due) < strtotime(date("Y-m-d")) && $r->status == 0) {
            $style = "background-color: khaki !important;";
        }

        $ins = $db->query("SELECT ID FROM project_tasks WHERE project = " . $r->ID)->num_rows;
        $ok = $db->query("SELECT ID FROM project_tasks WHERE project = " . $r->ID . " AND status = 1")->num_rows;

        if ($r->admin == 0) {
            $adm = "<i>{$l['NA']}</i>";
        } else if ($db->query("SELECT name FROM admins WHERE ID = " . $r->admin . " LIMIT 1")->num_rows == 1) {
            $adm = "<a href='?p=admin&id={$r->admin}'>" . htmlentities($db->query("SELECT name FROM admins WHERE ID = " . $r->admin . " LIMIT 1")->fetch_object()->name) . "</a>";
        } else {
            $adm = "<i>{$l['UK']}</i>";
        }

        ?>
		<tr>
			<td style="<?=$style;?>"><input type="checkbox" class="checkbox" name="project[]" value="<?=$r->ID;?>" onchange="javascript:toggle();" /></td>
			<td style="<?=$style;?>"><a<?=$r->star ? ' style="color: #EAC117;"' : "";?> href="?p=projects&type=<?=$type;?>&<?=$r->star ? "un" : "";?>star=<?=$r->ID;?>"><i class="fa fa-star<?=!$r->star ? "-o" : "";?>"></i></a></td>
			<td style="<?=$style;?>"><center><?=$CFG['PNR_PREFIX'];?><?=$r->ID;?></center></td>
			<td style="<?=$style;?>"><?=$dfo->format(strtotime($r->due), false);?></td>
			<td style="<?=$style;?>"><?=$r->name;?></td>
			<td style="<?=$style;?>"><?=$cus;?></td>
			<td style="<?=$style;?>"><?=$adm;?></td>
			<td style="<?=$style;?>"><?=$ok;?> (<?=$ins;?>)</td>
			<td style="<?=$style;?>"><?=$cur->infix($nfo->format($r->entgelt), $cur->getBaseCurrency());?> <?=$r->entgelt_type == "1" ? " " . $l['PERHOUR'] : "";?></td>
			<?php if ($ari->check(31) || $ari->check(32)) {?><td style="<?=$style;?>" width="80px"><?php if ($ari->check(31)) {?><a href="?p=view_project&id=<?=$r->ID;?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;<?php }if ($r->status == 0 && $ari->check(31)) {?><a href="?p=projects&type=<?=$type;?>&ok=<?=$r->ID;?>"><i class="fa fa-check-square-o"></i></a>&nbsp;&nbsp;<?php }if ($ari->check(32)) {?><a onclick="return confirm('Soll das Projekt wirklich mit allen Aufgaben entfernt werden?');" href="?p=projects&type=<?=$type;?>&del=<?=$r->ID;?>"><i class="fa fa-minus-square-o"></i></a><?php }?></td><?php }?>
		</tr>
		<?php }if (!$e) {?>
		<tr>
			<td colspan="10"><center><?=$l['NT'];?></center></td>
		</tr><?php }?>
	</table></div>
	<?=$l['SELECTED'];?>: <div class="btn-group">
  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <?=$l['CS'];?> <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">
  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'change_status', value: '2' }).appendTo('#project_form'); $('#project_form').submit(); return false;"><?=$l['S3'];?></a></li>
  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'change_status', value: '3' }).appendTo('#project_form'); $('#project_form').submit(); return false;"><?=$l['S4'];?></a></li>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'change_status', value: '0' }).appendTo('#project_form'); $('#project_form').submit(); return false;"><?=$l['IP'];?></a></li>
    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'change_status', value: '1' }).appendTo('#project_form'); $('#project_form').submit(); return false;"><?=$l['S5'];?></a></li>
  </ul>
</div>
 <input type="submit" name="delete_selected" value="<?=$l['DEL'];?>" class="btn btn-danger" /> <input type="text" name="duedate" value="<?=$dfo->format(time(), false);?>" placeholder="<?=$dfo->format(time(), false);?>" class="form-control datepicker" style="max-width: 100px;"> <input type="submit" name="set_duedate" value="<?=$l['SETDUE'];?>" class="btn btn-default" />
	</form>

<?php echo $t->getFooter();} ?><br /><br />