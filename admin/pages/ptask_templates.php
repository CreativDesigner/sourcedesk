<?php 
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$l = $lang['PTASK_TEMPLATES'];
title($l['TITLE']);

if(!$ari->check(46)){ require __DIR__ . "/error.php"; alog("general", "insufficient_page_rights", "ptask_templates"); } else { 

$wysia = Array();

if(isset($_GET['a']) && $_GET['a'] == "c"){
?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE2']; ?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php 
	if(isset($_POST['create'])){
	
		try {
			if(!isset($_POST['name']) || $_POST['name'] == "") throw new Exception($l['ERR1']);
			if($db->query("SELECT ID FROM project_templates WHERE name = '" . $db->real_escape_string($_POST['name']) . "' LIMIT 1")->num_rows == 1) throw new Exception($l['ERR2']);
		
			$tasks = Array();
			$price = Array();
			foreach($_POST['task_name'] as $k => $v){
				if(!isset($_POST['task'][$k]) || trim($v) == "") continue;
				$tasks[$v] = $_POST['task'][$k];

				if (!empty($_POST['other_entgelt'][$k])) {
					$price[$v] = [$_POST['entgelt'][$k], $_POST['entgelt_type'][$k]];
				}
			}
			$tasks = $db->real_escape_string(serialize($tasks));
			$price = $db->real_escape_string(serialize($price));
			
			$db->query("INSERT INTO project_templates (`name`, `tasks`, `price`) VALUES ('" . $db->real_escape_string($_POST['name']) . "', '$tasks', '$price')");
			if($db->affected_rows <= 0) throw new Exception($l['ERR3']);
		
			alog("project", "template_create", $_POST['name'], $db->insert_id);

			?><div class="alert alert-success"><?=$l['SUC']; ?> <a href="?p=ptask_templates"><?=$l['OVERVIEW']; ?></a> | <a href="?p=projects"><?=$l['PROJECTS']; ?></a></div><?php
			
			unset($_POST);
		} catch (Exception $ex) {
			?><div class="alert alert-danger"><b><?=$lang['GENERAL']['ERROR']; ?></b> <?=$ex->getMessage(); ?></div><?php
		}
	
	}
	?>
	
	<form role="form" method="POST">
	
	<div class="form-group">
	<label><?=$l['NAME']; ?></label>
	<input type="text" class="form-control" name="name" value="<?=isset($_POST['name']) ? $_POST['name'] : ""; ?>" placeholder="<?=$l['NAMEP']; ?>">
	</div>
	
	<div class="form-group">
	<label><?=$l['TASK']; ?> 1</label>
	
	<input type="text" class="form-control" name="task_name[0]" value="<?=isset($_POST['task_name'][0]) ? $_POST['task_name'][0] : ""; ?>" placeholder="<?=$l['TASKNAME']; ?>"><br />
	<textarea class="form-control" name="task[0]" style="resize:vertical; height: 120px;" placeholder="<?=$l['TASKDESC']; ?>"><?=isset($_POST['task'][0]) ? $_POST['task'][0] : ""; ?></textarea>

	<div class="checkbox">
	<label>
		<input type="checkbox" class="other_entgelt" data-id="0" name="other_entgelt[0]" value="1"<?=!empty($_POST['other_entgelt'][0]) ? ' checked=""' : ''; ?>> <?=$l['OTHER_ENTGELT']; ?>
	</label>
	</div>

	<div id="other_entgelt_0"<?=empty($_POST['other_entgelt'][0]) ? ' style="display: none;"' : ""; ?>>
		<div class="form-group">
			<label><?=$l['ENTGELT']; ?></label><br />
			<div><input type="text" size="5" name="entgelt[0]" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder(); ?>" class="form-control" value="<?php if(isset($_POST['entgelt'][0])) echo $_POST['entgelt'][0]; ?>">
			<select name="entgelt_type[0]" class="form-control" style="display: inline; max-width: 150px;">
				<option value="0"><?=$l['ENTGELT0']; ?></option>
				<option value="1"<?php if(isset($_POST['entgelt_type'][0]) && $_POST['entgelt_type'][0] == "1") echo ' selected="selected"'; ?>><?=$l['ENTGELT1']; ?></option>
			</select></div>
		</div>
	</div>
	</div>
	
	<div id="moreExercises">
	<?php
	if(isset($_POST['task']) && count($_POST['task']) > 1){
		foreach($_POST['task'] as $k => $v){
			if($k == 0) continue;
			
			?>
			<div class="form-group">
			<label><?=$l['TASK']; ?> <?=$k+1; ?></label>

			<input type="text" class="form-control" name="task_name[<?=$k; ?>]" value="<?=isset($_POST['task_name'][$k]) ? $_POST['task_name'][$k] : ""; ?>" placeholder="<?=$l['TASKNAME']; ?>"><br />
			<textarea class="form-control" name="task[<?=$k; ?>]" style="resize:none; height:80px;" placeholder="<?=$l['TASKDESC']; ?>"><?=isset($_POST['task'][$k]) ? $_POST['task'][$k] : ""; ?></textarea>

			<div class="checkbox">
			<label>
				<input type="checkbox" class="other_entgelt" data-id="<?=$k; ?>" name="other_entgelt[<?=$k; ?>]" value="1"<?=!empty($_POST['other_entgelt'][$k]) ? ' checked=""' : ''; ?>> <?=$l['OTHER_ENTGELT']; ?>
			</label>
			</div>

			<div id="other_entgelt_<?=$k; ?>"<?=empty($_POST['other_entgelt'][$k]) ? ' style="display: none;"' : ""; ?>>
				<div class="form-group">
					<label><?=$l['ENTGELT']; ?></label><br />
					<div><input type="text" size="5" name="entgelt[<?=$k; ?>]" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder(); ?>" class="form-control" value="<?php if(isset($_POST['entgelt'][$k])) echo $_POST['entgelt'][$k]; ?>">
					<select name="entgelt_type[<?=$k; ?>]" class="form-control" style="display: inline; max-width: 150px;">
						<option value="0"><?=$l['ENTGELT0']; ?></option>
						<option value="1"<?php if(isset($_POST['entgelt_type'][$k]) && $_POST['entgelt_type'][$k] == "1") echo ' selected="selected"'; ?>><?=$l['ENTGELT1']; ?></option>
					</select></div>
				</div>
			</div>
			</div>

			<?php
		}
	}
	?>
	</div>
	
	<center><a href="javascript:add_exercise();"><i class="fa fa-plus-square-o"></i> <?=$l['ADDMORE']; ?></a><br /><br /><input type="submit" name="create" class="btn btn-success" value="<?=$l['CREATENOW']; ?>"></center>
	
	<script type="text/javascript">
	function bindoe() {
		$(".other_entgelt").unbind("change").change(function(e) {
			if (e.target.checked) {
				$("#other_entgelt_" + $(this).data("id")).show();
			} else {
				$("#other_entgelt_" + $(this).data("id")).hide();
			}
		});
	}
	bindoe();
	
	var i = <?=isset($_POST['task_name']) ? count($_POST['task_name']) + 1 : 2; ?>;
	
	function add_exercise() {	
		var new_exercise = "<div class=\"form-group\">";
		new_exercise = new_exercise + "<label><?=$l['TASK']; ?> " + i + "</label>";
		new_exercise = new_exercise + "<input type=\"text\" class=\"form-control\" name=\"task_name[" + (i-1) + "]\" placeholder=\"<?=$l['TASKNAME']; ?>\"><br />";
		new_exercise = new_exercise + "<textarea class=\"form-control\" name=\"task[" + (i-1) + "]\" style=\"resize:none; height:80px;\" placeholder=\"<?=$l['TASKDESC']; ?>\"></textarea>";
		new_exercise = new_exercise + "<div class=\"checkbox\"><label><input type=\"checkbox\" class=\"other_entgelt\" data-id=\"" + (i-1) + "\" name=\"other_entgelt[" + (i-1) + "]\" value=\"1\"> <?=$l['OTHER_ENTGELT']; ?></label></div>";
		new_exercise = new_exercise + "<div id=\"other_entgelt_" + (i-1) + "\" style=\"display: none;\"><div class=\"form-group\"><label><?=$l['ENTGELT']; ?></label><br /><div><input type=\"text\" size=\"5\" name=\"entgelt[" + (i-1) + "]\" style=\"max-width:100px; display: inline;\" placeholder=\"<?=$nfo->placeholder(); ?>\" class=\"form-control\"> <select name=\"entgelt_type[" + (i-1) + "]\" class=\"form-control\" style=\"display: inline; max-width: 150px;\"><option value=\"0\"><?=$l['ENTGELT0']; ?></option><option value=\"1\"><?=$l['ENTGELT1']; ?></option></select></div></div></div>";
		new_exercise = new_exercise + "</div>";
		
		var div = document.getElementById("moreExercises");
		var newdiv = document.createElement('div');
		newdiv.innerHTML = new_exercise;
		
		while(newdiv.firstChild)
			div.appendChild(newdiv.firstChild);
		bindoe();
		
		i++;
	}
	
	</script>
	
	</form>
<?php
} else if(isset($_GET['id']) && $db->query("SELECT ID FROM project_templates WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->num_rows == 1){

$info = $db->query("SELECT * FROM project_templates WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->fetch_object();

if(isset($_POST['edit'])){

	try {
		if(!isset($_POST['name']) || $_POST['name'] == "") throw new Exception($l['ERR1']);
		if($db->query("SELECT ID FROM project_templates WHERE name = '" . $db->real_escape_string($_POST['name']) . "' AND ID != '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->num_rows == 1) throw new Exception($l['ERR2']);
	
		$tasks = $price = Array();
		foreach($_POST['task_name'] as $k => $v){
			if(!isset($_POST['task'][$k]) || trim($v) == "") continue;
			$tasks[$v] = $_POST['task'][$k];

			if (!empty($_POST['other_entgelt'][$k])) {
				$price[$v] = [$_POST['entgelt'][$k], $_POST['entgelt_type'][$k]];
			}
		}
		$tasks = $db->real_escape_string(serialize($tasks));
		$price = $db->real_escape_string(serialize($price));
		
		$db->query("UPDATE project_templates SET name = '" . $db->real_escape_string($_POST['name']) . "', tasks = '$tasks', price = '$price' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1");
		if($db->affected_rows <= 0) throw new Exception($l['ERR3']);
	
		alog("project", "template_change", $_GET['id'], $_POST['name']);

		$msg = '<div class="alert alert-success">' . $l['SUC2'] . ' <a href="?p=ptask_templates">' . $l['OVERVIEW'] . '</a> | <a href="?p=projects">' . $l['PROJECTS'] . '</a></div>';
		
		unset($_POST);
	} catch (Exception $ex) {
		$msg = '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $ex->getMessage() . '</div>';
	}
	
	$info = $db->query("SELECT * FROM project_templates WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->fetch_object();

}

$task_name = $task = $oe = $oee = $oet = Array();

if(isset($_POST['task_name']) && isset($_POST['task'])){
	foreach($_POST['task_name'] as $k => $v){
		$task_name[$k] = $v;
		$task[$k] = $_POST['task'][$k];
		$oe[$k] = !empty($_POST['other_entgelt'][$k]);
		$oee[$k] = $_POST['entgelt'][$k];
		$oet[$k] = $_POST['entgelt_type'][$k];
	}
} else {
	$uns = unserialize($info->tasks);
	foreach($uns as $k => $v){
		array_push($task_name, $k);
		array_push($task, $v);
	}
	$price = unserialize($info->price);
	foreach ($price as $k => $v) {
		$oe[array_search($k, $task_name)] = true;
		$oee[array_search($k, $task_name)] = $v[0];
		$oet[array_search($k, $task_name)] = $v[1];
	}
}
?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$info->name; ?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	
	<?=$msg; ?>
	
	<form role="form" method="POST">
	
	<div class="form-group">
	<label><?=$l['NAME']; ?></label>
	<input type="text" class="form-control" name="name" value="<?=isset($_POST['name']) ? $_POST['name'] : $info->name; ?>" placeholder="<?=$l['NAMEP']; ?>">
	</div>
	
	<div class="form-group">
	<label><?=$l['TASK']; ?> 1</label>
	
	<input type="text" class="form-control" name="task_name[0]" value="<?=$task_name[0]; ?>" placeholder="<?=$l['TASKNAME']; ?>"><br />
	<textarea class="form-control" name="task[0]" style="resize:none; height:80px;" placeholder="<?=$l['TASKDESC']; ?>"><?=nl2br($task[0]); ?></textarea>

	<div class="checkbox">
	<label>
		<input type="checkbox" class="other_entgelt" data-id="0" name="other_entgelt[0]" value="1"<?=!empty($oe[0]) ? ' checked=""' : ''; ?>> <?=$l['OTHER_ENTGELT']; ?>
	</label>
	</div>

	<div id="other_entgelt_0"<?=empty($oe[0]) ? ' style="display: none;"' : ""; ?>>
		<div class="form-group">
			<label><?=$l['ENTGELT']; ?></label><br />
			<div><input type="text" size="5" name="entgelt[0]" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder(); ?>" class="form-control" value="<?php if (isset($oee[0])) echo $nfo->format($oee[0]); ?>">
			<select name="entgelt_type[0]" class="form-control" style="display: inline; max-width: 150px;">
				<option value="0"><?=$l['ENTGELT0']; ?></option>
				<option value="1"<?php if(isset($oet[0]) && $oet[0] == "1") echo ' selected="selected"'; ?>><?=$l['ENTGELT1']; ?></option>
			</select></div>
		</div>
	</div>
	</div>
	
	<div id="moreExercises">
	<?php
	if(count($task) > 1){
		foreach($task as $k => $v){
			if($k == 0) continue;
			
			?>
			<div class="form-group">
			<label><?=$l['TASK']; ?> <?=$k+1; ?></label>

			<input type="text" class="form-control" name="task_name[<?=$k; ?>]" value="<?=$task_name[$k]; ?>" placeholder="<?=$l['TASKNAME']; ?>"><br />
			<textarea class="form-control" name="task[<?=$k; ?>]" style="resize:none; height:80px;" placeholder="<?=$l['TASKDESC']; ?>"><?=nl2br($task[$k]); ?></textarea>

			<div class="checkbox">
			<label>
				<input type="checkbox" class="other_entgelt" data-id="<?=$k; ?>" name="other_entgelt[<?=$k; ?>]" value="1"<?=!empty($oe[$k]) ? ' checked=""' : ''; ?>> <?=$l['OTHER_ENTGELT']; ?>
			</label>
			</div>

			<div id="other_entgelt_<?=$k; ?>"<?=empty($oe[$k]) ? ' style="display: none;"' : ""; ?>>
				<div class="form-group">
					<label><?=$l['ENTGELT']; ?></label><br />
					<div><input type="text" size="5" name="entgelt[<?=$k; ?>]" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder(); ?>" class="form-control" value="<?php if (isset($oee[$k])) echo $nfo->format($oee[$k]); ?>">
					<select name="entgelt_type[<?=$k; ?>]" class="form-control" style="display: inline; max-width: 150px;">
						<option value="0"><?=$l['ENTGELT0']; ?></option>
						<option value="1"<?php if(isset($oet[$k]) && $oet[$k] == "1") echo ' selected="selected"'; ?>><?=$l['ENTGELT1']; ?></option>
					</select></div>
				</div>
			</div>
			</div>
			<?php
		}
	}
	?>
	</div>
	
	<center><a href="javascript:add_exercise();"><i class="fa fa-plus-square-o"></i> <?=$l['ADDMORE']; ?></a><br /><br /><input type="submit" name="edit" class="btn btn-success" value="<?=$l['SAVENOW']; ?>"></center>
	
	<script type="text/javascript">
	function bindoe() {
		$(".other_entgelt").unbind("change").change(function(e) {
			if (e.target.checked) {
				$("#other_entgelt_" + $(this).data("id")).show();
			} else {
				$("#other_entgelt_" + $(this).data("id")).hide();
			}
		});
	}
	bindoe();
	
	var i = <?=count($task_name) + (count($task_name) == 0 ? 2 : 1); ?>;
	
	function add_exercise() {	
		var new_exercise = "<div class=\"form-group\">";
		new_exercise = new_exercise + "<label><?=$l['TASK']; ?> " + i + "</label>";
		new_exercise = new_exercise + "<input type=\"text\" class=\"form-control\" name=\"task_name[" + (i-1) + "]\" placeholder=\"<?=$l['TASKNAME']; ?>\"><br />";
		new_exercise = new_exercise + "<textarea class=\"form-control\" name=\"task[" + (i-1) + "]\" style=\"resize:none; height:80px;\" placeholder=\"<?=$l['TASKDESK']; ?>\"></textarea>";
		new_exercise = new_exercise + "<div class=\"checkbox\"><label><input type=\"checkbox\" class=\"other_entgelt\" data-id=\"" + (i-1) + "\" name=\"other_entgelt[" + (i-1) + "]\" value=\"1\"> <?=$l['OTHER_ENTGELT']; ?></label></div>";
		new_exercise = new_exercise + "<div id=\"other_entgelt_" + (i-1) + "\" style=\"display: none;\"><div class=\"form-group\"><label><?=$l['ENTGELT']; ?></label><br /><div><input type=\"text\" size=\"5\" name=\"entgelt[" + (i-1) + "]\" style=\"max-width:100px; display: inline;\" placeholder=\"<?=$nfo->placeholder(); ?>\" class=\"form-control\"> <select name=\"entgelt_type[" + (i-1) + "]\" class=\"form-control\" style=\"display: inline; max-width: 150px;\"><option value=\"0\"><?=$l['ENTGELT0']; ?></option><option value=\"1\"><?=$l['ENTGELT1']; ?></option></select></div></div></div>";
		new_exercise = new_exercise + "</div>";
		
		var div = document.getElementById("moreExercises");
		var newdiv = document.createElement('div');
		newdiv.innerHTML = new_exercise;
		
		while(newdiv.firstChild)
			div.appendChild(newdiv.firstChild);
		bindoe();
		
		i++;
	}
	
	</script>
	
	</form>
<?php
} else {
?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header">
				<?=$l['TITLE']; ?>
				<a href="?p=ptask_templates&a=c" class="pull-right"><i class="fa fa-plus-circle"></i></a>
			</h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	
	<?php if(isset($_GET['del']) && $db->query("DELETE FROM  project_templates WHERE ID = '" . $db->real_escape_string($_GET['del']) . "' LIMIT 1") && $db->affected_rows > 0){
		alog("project", "template_deleted", $_GET['del']);
	?>
	<div class="alert alert-success"><?=$l['DELETED']; ?></div>
	<?php } ?>
	
	<div class="table table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th><?=$l['TEMPLATE']; ?></th>
				<th><?=$l['EXERCISES']; ?></th>
				<th width="60px"></th>
			</tr>
			
			<?php
			$sql = $db->query("SELECT * FROM project_templates");
			
			if($sql->num_rows <= 0){
			?>
			<tr>
				<td colspan="3"><center><?=$l['NT']; ?></center></td>
			</tr>
			<?php } else { while($r = $sql->fetch_object()){ ?>
			<tr>
				<td><?=$r->name; ?></td>
				<td><?=trim($r->tasks) == "" || !unserialize($r->tasks) ? "0" : count(unserialize($r->tasks)); ?></td>
				<td width="60px"><a href="?p=ptask_templates&id=<?=$r->ID; ?>"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;<a onclick="return confirm('<?=$l['RD']; ?>');" href="?p=ptask_templates&del=<?=$r->ID; ?>"><i class="fa fa-minus-square-o"></i></a></td>
			</tr>
			<?php } } ?>
		</table>
	</div>

<?php } } ?>