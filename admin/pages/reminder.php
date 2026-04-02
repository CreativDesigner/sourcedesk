<?php
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$l = $lang['REMINDERS'];
title($l['TITLE']);
?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE']; ?></h1>
			
			<?php if(isset($_GET['action']) && $_GET['action'] == "add"){ 
			if(isset($_POST['add'])){
				try {
					foreach($_POST as $k => $v){
						$vari = "p_" . strtolower($k);
						$$vari = $db->real_escape_string($v);
					}
					
					$time = strtotime($p_time);
					if(!$time) throw new Exception($l['ERR1']);
					
					if(empty($p_title)) throw new Exception($l['ERR2']);
					if(empty($p_text)) throw new Exception($l['ERR3']);
					
					if(!$ari->check(4) || !isset($p_user) || !is_numeric($p_user)) $p_user = $adminInfo->ID;
					$db->query("INSERT INTO admin_reminders (time, description,title,user) VALUES ('$time', '$p_text', '$p_title', '$p_user')");
					unset($_POST);
					echo '<div class="alert alert-success">' . $l['SUC1'] . '</div>';
					alog("reminder", "added", $p_title, $p_user);
				} catch (Exception $ex) {
					echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $ex->getMessage() . '</div>';
				}
			}
			?>
			<form role="form" method="POST">
  <div class="form-group">
    <label><?=$l['TITLE2']; ?></label>
    <input type="text" value="<?=htmlentities($_POST['title']); ?>" name="title" class="form-control">
  </div>
  <div class="form-group">
    <label><?=$l['DESC']; ?></label>
    <textarea class="form-control summernote" name="text" rows="5"><?=htmlentities($_POST['text']); ?></textarea>
  </div>
  <div class="form-group" style="position: relative;">
    <label><?=$l['DUE']; ?></label>
    <input type="text" value="<?=htmlentities($_POST['time']); ?>" name="time" placeholder="<?=$dfo->placeholder(false); ?> <?=$l['OR']; ?> <?=$dfo->placeholder(true, true, ""); ?>" class="form-control datetimepicker">
  </div>
  <?php if($ari->check(4)){ ?><div class="form-group">
    <label><?=$l['ADMIN']; ?></label>
    <select name="user" class="form-control">
		<option value="<?=$adminInfo->ID; ?>"><?=$adminInfo->name; ?></option>
		<?php $sql = $db->query("SELECT ID, name FROM admins WHERE ID != " . $adminInfo->ID);
		while($r = $sql->fetch_object()){ ?>
		<option value="<?=$r->ID; ?>" <?php if($r->ID == $_POST['user']) echo "selected"; ?>><?=$r->name; ?></option>
		<?php } ?>
	</select>
  </div><?php } ?>
  <input type="hidden" name="id" value="new">
  <center><button type="submit" name="add" class="btn btn-primary"><?=$l['ADD']; ?></button></center></form><hr/><?php } ?>
  <?php if(isset($_GET['edit']) && $_GET['edit'] > 0 && $db->query("SELECT * FROM admin_reminders WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->num_rows == 1){ 
  if(isset($_POST['save'])){
	try {
		foreach($_POST as $k => $v){
			$vari = "p_" . strtolower($k);
			$$vari = $db->real_escape_string($v);
		}
					
		$time = strtotime($p_time);
		if(!$time) throw new Exception($l['ERR1']);
		
		if(empty($p_title)) throw new Exception($l['ERR2']);
		if(empty($p_text)) throw new Exception($l['ERR3']);
		
		if(!$ari->check(4) || !isset($p_user) || !is_numeric($p_user)) $user = $adminInfo->ID;
		$db->query("UPDATE admin_reminders SET title = '$p_title', description = '$p_text', time = '$time', user = '$p_user' WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "' LIMIT 1");
		unset($_POST);
		echo '<div class="alert alert-success">' . $l['SUC2'] . '</div>';
		alog("reminder", "edited", $p_title, $p_user, $_GET['edit']);
	} catch (Exception $ex) {
		echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $ex->getMessage() . '</div>';
	}
  }
  $info = $db->query("SELECT * FROM admin_reminders WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->fetch_object();
  
  $title = isset($_POST['title']) ? $_POST['title'] : $info->title;
  $text = isset($_POST['text']) ? $_POST['text'] : $info->description;
  $time = isset($_POST['time']) ? $_POST['time'] : $dfo->format($info->time, true, true, "");
  $user = isset($_POST['user']) ? $_POST['user'] : $info->user;
  ?>
			<form role="form" method="POST">
  <div class="form-group">
    <label><?=$l['TITLE2']; ?></label>
    <input type="text" value="<?=htmlentities($title); ?>" name="title" class="form-control">
  </div>
  <div class="form-group">
    <label><?=$l['DESC']; ?></label>
    <textarea class="form-control summernote" name="text" rows="5"><?=nl2br(htmlentities($text)); ?></textarea>
  </div>
  <div class="form-group" style="position: relative;">
    <label><?=$l['DUE']; ?></label>
    <input type="text" value="<?=htmlentities($time); ?>" name="time" placeholder="<?=$dfo->placeholder(false); ?> <?=$l['OR']; ?> <?=$dfo->format(true, true, ""); ?>" class="form-control datetimepicker">
  </div>
  <?php if($ari->check(4)){ ?><div class="form-group">
    <label><?=$l['ADMIN']; ?></label>
    <select name="user" class="form-control">
		<option value="<?=$adminInfo->ID; ?>"><?=$adminInfo->name; ?></option>
		<?php $sql = $db->query("SELECT ID, name FROM admins WHERE ID != " . $adminInfo->ID);
		while($r = $sql->fetch_object()){ ?>
		<option value="<?=$r->ID; ?>" <?php if($r->ID == $user) echo "selected"; ?>><?=$r->name; ?></option>
		<?php } ?>
	</select>
  </div><?php } ?>
  <input type="hidden" name="id" value="new">
  <center><button type="submit" name="save" class="btn btn-primary"><?=$l['SAVE']; ?></button></center></form><hr/><?php } ?>
			<a href="?p=reminder&action=add" class="btn btn-success"><?=$l['ADDNEW']; ?></a><?php if($ari->check(4)){ ?>&nbsp;<?php if(!isset($_GET['view'])){ ?><a href="?p=reminder&view=all" class="btn btn-default"><?=$l['SEEALL']; ?></a><?php } else { ?><a href="?p=reminder" class="btn btn-default"><?=$l['ONLYMY']; ?></a><?php } ?><?php } ?><br /><br />
			<?php
			if(isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0){
				$check = $ari->check(4) ? "" : " AND user = '" . $adminInfo->ID . "'";
				$db->query("DELETE FROM  admin_reminders WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "'$check LIMIT 1");
				if($db->affected_rows > 0) {
					echo "<div class='alert alert-success'>{$l['SUC3']}</div>";
					alog("reminder", "deleted", $_GET['delete']);
				}
			}

			if(isset($_POST['delete_selected']) && is_array($_POST['reminder'])){
				$d = 0;
				foreach($_POST['reminder'] as $id) {
					$sql = "DELETE FROM  admin_reminders WHERE ID = '" . $db->real_escape_string($id) . "'";
					if(!$ari->check(4)) {
						$sql .= " AND user = '" . $adminInfo->ID . "'";
					}
					$sql .= " LIMIT 1";

					$db->query($sql);
					if($db->affected_rows > 0) {
						$d++;
						alog("reminder", "deleted", $id);
					}
				}

				if($d == 1) echo "<div class='alert alert-success'>{$l['SUC4']}</div>";
				else if($d > 0) echo "<div class='alert alert-success'>" . str_replace("%d", $d, $l['SUC5']) . "</div>";
			}
			?>

			<div class="table-responsive"><table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th><?=$l['DUE2']; ?></th>
					<?php if(isset($_GET['view']) && !$ari->check(4)) unset($_GET['view']); if(isset($_GET['view'])){ ?><th><?=$l['ADMIN']; ?></th><?php } ?>
					<th><?=$l['SUBJ']; ?></th>
					<th><?=$l['DESC']; ?></th>
					<th width="61px"></th>
				</tr>
				
				<form method="POST">
				<?php		
				$where = "";
				if(!isset($_GET['view'])) $where = "WHERE user = " . $adminInfo->ID;
				$sql = $db->query("SELECT * FROM admin_reminders $where ORDER BY time ASC");
				if($sql->num_rows == 0){
				?>
					<tr>
						<td colspan="6"><center><?=$l['NT']; ?></center></td>
					</tr>
				<?php
				} else {
					while($u = $sql->fetch_object()){
					if($u->time > time()) $style = "";
					else $style = "background-color: khaki !important;";
					?>
						<tr style="<?=$style; ?>">
							<td style="<?=$style; ?>"><input type="checkbox" class="checkbox" name="reminder[]" value="<?=$u->ID; ?>" onchange="javascript:toggle();" /></td>
							<td style="<?=$style; ?>"><?php if(date("H:i", $u->time) == "00:00"){ ?><?=$dfo->format($u->time, false); ?><?php } else { ?><?=$dfo->format($u->time); ?><?php } ?></td>
							<?php if(isset($_GET['view'])){ ?><td><a href="?p=admin&id=<?=$u->user; ?>"><?php $uAdmin = $db->query("SELECT * FROM admins WHERE ID = " . $u->user . " LIMIT 1")->fetch_object(); echo $uAdmin->name . " (" . $uAdmin->username . ")"; ?></a></td><?php } ?>
							<td style="<?=$style; ?>"><?=make_clickable(htmlentities($u->title)); ?></td>
							<td style="<?=$style; ?>"><?php if(strlen($u->description) > 30){ ?><a role="button" href="#" data-toggle="modal" data-target="#id<?=$u->ID; ?>_description"><?=htmlentities(substr($u->description, 0, 30)); ?>...</a><?php } else { echo str_replace(Array("<br>", "<br />", "<br/>"), " ", make_clickable(nl2br(htmlentities($u->description)))); } ?></td>
							<td style="<?=$style; ?>" width="61px"><a href="?p=reminder&edit=<?=$u->ID; ?><?php if(isset($_GET['view'])) echo "&view=all"; ?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;&nbsp;<a href="?p=reminder<?php if(isset($_GET['view'])) echo "&view=all"; ?>&delete=<?=$u->ID; ?>"><i class="fa fa-times fa-lg"></i></a></td>
						</tr>
						
						<?php if(strlen($u->description) > 30) { ?>
						<div class="modal fade" id="id<?=$u->ID; ?>_description" tabindex="-1" role="dialog" aria-hidden="true">
						  <div class="modal-dialog">
							<div class="modal-content">
							  <div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?=$lang['GENERAL']['CLOSE']; ?></span></button>
								<h4 class="modal-title" id="myModalLabel"><?=make_clickable($u->title); ?>: <?=$l['DESC']; ?></h4>
							  </div>
							  <div class="modal-body">
								<?=make_clickable(nl2br($u->description)); ?>
							  </div>
							  <div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE']; ?></button>
							  </div>
							</div>
						  </div>
						</div>
						<?php } ?>
					<?php
					}
				}
				?>
			</table></div><?=$l['SELECTED']; ?>: <input type="submit" name="delete_selected" value="<?=$l['DELETE']; ?>" class="btn btn-danger" /><br /><br /></form>
		</div>
		<!-- /.col-lg-12 -->
	</div>           