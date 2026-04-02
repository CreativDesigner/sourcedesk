<?php
$l = $lang['CATEGORIES'];
$cl = $lang['ADD_CATEGORY'];
title($l['TITLE']);
menu("products");

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if(!$ari->check(45)){ require __DIR__ . "/error.php"; alog("general", "insufficient_page_rights", "categories"); } else { 

if(isset($_GET['id']) && $db->query("SELECT * FROM product_categories WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->num_rows == 1){

$i = $db->query("SELECT * FROM product_categories WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();

$themes = [];
foreach (glob(__DIR__ . "/../../themes/order/*") as $theme) {
	if (is_dir($theme)) {
		$theme = basename($theme);
		array_push($themes, $theme);
	}
}

ob_start();
if(isset($_POST['submit'])){
	try {
		$names = Array();
		$casts = Array();

		if(!isset($_POST['view']) || ($_POST['view'] != "0" && $_POST['view'] != "1"))
			throw new Exception($l['ERR1']);
		
		foreach($languages as $lang_key => $lang_title){ 
			if(!isset($_POST['name_' . $lang_key]) || trim($_POST['name_' . $lang_key]) == "") throw new Exception($l['ERR3']);
			if(!isset($_POST['cast_' . $lang_key])) $_POST['cast_' . $lang_key] = "";
			
			$names[$lang_key] = $_POST['name_' . $lang_key];
			$casts[$lang_key] = $_POST['cast_' . $lang_key];
		}

		if (empty($_POST['template']) || !in_array($_POST['template'], $themes)) {
			$_POST['template'] = "standard";
		}
		
		$sql = "UPDATE product_categories SET name = '" . $db->real_escape_string(serialize($names)) . "', cast = '" . $db->real_escape_string(serialize($casts)) . "', template = '" . $db->real_escape_string($_POST['template']) . "', view = " . intval($_POST['view']) . " WHERE ID = '" . $i->ID . "' LIMIT 1";
		if(!$db->query($sql)) throw new Exception($l['ERR4']);
	
		echo '<div class="alert alert-success">' . $l['SUC'] . '</div>';

		alog("categories", "changed", $i->ID);
		unset($_POST);
	} catch (Exception $ex) {
		echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . htmlentities($ex->getMessage()) . '</div>';
	}
}

$ob = ob_get_contents();
ob_end_clean();

$i = $db->query("SELECT * FROM product_categories WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();

$iName = unserialize($i->name);
$iCast = unserialize($i->cast);

$url = $CFG['PAGEURL'] . "cat/" . $i->ID;
?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['EDIT']; ?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			
			<?=$ob; ?>
			
			<form role="form" method="POST">
				<div class="form-group">
					<label><?=$l['LINK']; ?></label><br />
					<span class="control-label"><a href="<?=$url; ?>" target="_blank"><?=$url; ?></a></span>
				</div>

				<label><?=$cl['CV']; ?></label><br /> <label class="radio-inline"> <input type="radio" name="view" value="1"<?=(isset($_POST['view']) && $_POST['view'] == "1") || (!isset($_POST['view']) & $i->view == 1) ? " checked=\"checked\"" : ""; ?>>
					<?=$cl['ACTIVE']; ?>
				</label> <label class="radio-inline"> <input type="radio" name="view" value="0"<?=(isset($_POST['view']) && $_POST['view'] == "0") || (!isset($_POST['view']) & $i->view == 0) ? " checked=\"checked\"" : ""; ?>>
					<?=$cl['INACTIVE']; ?>
				</label><br /><br />

				<label><?=$cl['VT']; ?></label><br /> 
				<?php foreach ($themes as $theme) { ?>
				<label class="radio-inline"> <input type="radio" name="template" value="<?=$theme; ?>"<?=(isset($_POST['template']) && $_POST['template'] == $theme) || (!isset($_POST['template']) & $i->template == $theme) ? " checked=\"checked\"" : ""; ?>>
					<?=ucfirst($theme); ?>
				</label>
				<?php } ?>
				
				<br /><br />
  
<?php 
            function lastArrayElement($arr, $key){
                end($arr);
                return $key === key($arr);
            }
            
            foreach($languages as $lang_key => $lang_title){ 
    ?>
<h3 style="margin-top:0"><?=$lang_title; ?></h3>
  
   <div class="form-group">
    <label><?=$cl['NAME']; ?></label>
   <input type="text" name="name_<?=$lang_key; ?>" class="form-control" placeholder="<?=$cl['NAMEP']; ?>" value="<?=isset($_POST['name_' . $lang_key]) ? $_POST['name_' . $lang_key] : $iName[$lang_key]; ?>">
  </div>
  
<div class="form-group">
    <label><?=$cl['CAST']; ?></label>
   <input type="text" name="cast_<?=$lang_key; ?>" class="form-control" value="<?=isset($_POST['cast_' . $lang_key]) ? $_POST['cast_' . $lang_key] : $iCast[$lang_key]; ?>" placeholder="<?=$cl['CASTP']; ?>">
   <p class="help-block"><?=$cl['CASTH']; ?></p>
  </div>
  <?php if(!lastArrayElement($languages, $lang_key)) echo "<hr />"; ?>
  <?php } ?>
  
  <center><button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['SAVE']; ?></button></center></form>
			
<?php

} else {
?>

            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE']; ?> <a href="?p=add_category" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
						
			<div class="row">
				<div class="col-lg-12">
				<?php
				if(isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0){
					$db->query("DELETE FROM  product_categories WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
					if($db->affected_rows > 0){
						$db->query("UPDATE products SET category = 0 WHERE category = '" . $db->real_escape_string($_GET['delete']) . "'");
						alog("categories", "deleted", $_GET['delete']);
						?>
						<div class="alert alert-success"><?=$l['DELETED']; ?></div>
						<?php
					}
				}

				if(isset($_POST['delete_selected']) && is_array($_POST['cat'])){
					$d = 0;
					foreach($_POST['cat'] as $id){
						$db->query("DELETE FROM  product_categories WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
						if($db->affected_rows > 0){
							$db->query("UPDATE products SET category = 0 WHERE category = '" . $db->real_escape_string($id) . "'");
							alog("categories", "deleted", $id);
							$d++;
						}
					}

					if($d == 1) echo "<div class='alert alert-success'>" . $l['D1'] . "</div>";
					else if($d > 0) echo "<div class='alert alert-success'>" . str_replace("%d", $d, $l['DX']) . "</div>";
				}
				?>
				
					<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked); " /></th>
		<th><?=$cl['NAME']; ?></th>
		<th width="56px"></th>
	</tr>
	<form method="POST">
	<?php
	
	$sql = $db->query("SELECT * FROM product_categories");
	
	if($sql->num_rows == 0){
		echo "<tr><td colspan=\"3\"><center>{$l['NT']}</center></td></tr>";
	} else {
		$lastCategory = 0;

		$cats = Array();
		while($d = $sql->fetch_object())
			$cats[unserialize($d->name)[$CFG['LANG']]] = $d;
		ksort($cats);
			
		foreach ($cats as $name => $d){
			?>
				<tr><td><input type="checkbox" class="checkbox" name="cat[]" value="<?=$d->ID; ?>" onchange="javascript:toggle();" /></td>
				<td><?=$name; ?></td>
				<td>
			<a href="?p=categories&id=<?=$d->ID; ?>" title="<?=$l['DE']; ?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;
			<a href="?p=categories&delete=<?=$d->ID; ?>" title="<?=$l['DD']; ?>" onclick="return confirm('<?=$l['RDD']; ?>');"><i class="fa fa-times fa-lg"></i></a>
		</td></tr>
			<?php
		}
	}
	?>
</table></div><?=$l['SELECTED']; ?>: <input type="submit" name="delete_selected" value="<?=$l['DD']; ?>" class="btn btn-danger" /><br /></form>
				</div>
            </div>
            <!-- /.row -->

<?php } } ?>