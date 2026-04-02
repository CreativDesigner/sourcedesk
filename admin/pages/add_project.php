<?php
$l = $lang['ADD_PROJECT'];
title($l['TITLE']);

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(31)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_project");} else {

    ?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div><?php

    if (isset($_POST['submit'])) {

        try {

            foreach ($_POST as $k => $v) {
                $vari = "p_" . strtolower($k);
                $$vari = $db->real_escape_string($v);
            }

            if (!isset($p_name) || $p_name == "") {
                throw new Exception($l['ERR1']);
            }

            if (!isset($p_due) || !strtotime($p_due)) {
                throw new Exception($l['ERR2']);
            }

            $entgelt = $nfo->phpize($p_entgelt);
            if (!isset($_POST['entgelt']) || !is_numeric($entgelt) || $entgelt < 0) {
                throw new Exception($l['ERR3']);
            }

            if ($p_user != 0) {
                $sql = $db->query("SELECT ID FROM clients WHERE ID = '" . $p_user . "' LIMIT 1");
                if ($sql->num_rows != 1) {
                    throw new Exception($l['ERR4']);
                }

            }

            if ($p_admin != 0) {
                $sql = $db->query("SELECT ID FROM admins WHERE ID = '" . $p_admin . "' LIMIT 1");
                if ($sql->num_rows != 1) {
                    throw new Exception($l['ERR5']);
                }

            }

            $due = date("Y-m-d", strtotime($p_due));

            if (isset($p_show_details)) {
                $p_show_details = 1;
            } else {
                $p_show_details = 0;
            }

            $entgelt_type = !empty($p_entgelt_type) ? 1 : 0;
            if (!isset($_POST['time_contingent']) || !is_numeric($p_time_contingent) || $p_time_contingent < 0) {
                throw new Exception($l['ERR6']);
            }

            $db->query("INSERT INTO projects (`name`, `due`, `entgelt`, `entgelt_type`, `user`, `admin`, `show_details`, `description`, `time_tracking`, `time_contingent`) VALUES ('$p_name', '$due', '$entgelt', $entgelt_type, '$p_user', '$p_admin', '$p_show_details', '$p_description', '$p_time_tracking', '$p_time_contingent')");
            $iid = $db->insert_id;
            echo '<div class="alert alert-success">' . $l['SUC'] . '<br /><a href="?p=view_project&id=' . $iid . '">' . $l['TOP'] . '</a> | <a href="?p=projects">' . $l['OVERVIEW'] . '</a></div>';

            alog("general", "project_created", $iid);

            unset($_POST);
        } catch (Exception $ex) {
            echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $ex->getMessage() . '</div>';

        }

    }

    ?>


	<form method="POST" action="?p=add_project">

  <div class="form-group">
    <label><?=$l['PN'];?></label>
   <input type="text" name="name" class="form-control" value="<?php if (isset($_POST['name'])) {
        echo $_POST['name'];
    }
    ?>">
  </div>

	<div class="form-group">
    <label><?=$l['DESC'];?></label>
   <textarea name="description" class="form-control" placeholder="<?=$l['OPTIONAL'];?>" style="width: 100%; height: 120px; resize: vertical;"><?php if (isset($_POST['description'])) {
        echo htmlentities($_POST['description']);
    }
    ?></textarea>
  </div>

<div class="form-group" style="position: relative;">
    <label><?=$l['DUE'];?></label>
   <input type="text" name="due" placeholder="<?=date("d.m.Y");?>" class="form-control datepicker" value="<?php if (isset($_POST['due'])) {
        echo $_POST['due'];
    }
    ?>">
  </div>

  <div class="form-group">
    <label><?=$l['ENTGELT'];?></label><br />
   	<div><input type="text" size="5" name="entgelt" style="max-width:100px; display: inline;" placeholder="<?=$nfo->placeholder();?>" class="form-control" value="<?php if (isset($_POST['entgelt'])) {
        echo $_POST['entgelt'];
    }
    ?>">
   	<select name="entgelt_type" class="form-control" style="display: inline; max-width: 150px;">
   		<option value="0"><?=$l['ENTFIX'];?></option>
   		<option value="1"<?php if (isset($_POST['entgelt_type']) && $_POST['entgelt_type'] == "1") {
        echo ' selected="selected"';
    }
    ?>><?=$l['ENTHOUR'];?></option>
   	</select></div>
	</div>

	<div class="form-group">
		<label><?=$l['TT'];?></label>
		<select name="time_tracking" class="form-control">
			<option value="exact"><?=$l['TT1'];?></option>
			<option value="ceil10"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "ceil10" ? ' selected=""' : '';?>><?=$l['TT2'];?></option>
			<option value="floor10"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "floor10" ? ' selected=""' : '';?>><?=$l['TT3'];?></option>
			<option value="ceil15"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "ceil15" ? ' selected=""' : '';?>><?=$l['TT4'];?></option>
			<option value="floor15"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "floor15" ? ' selected=""' : '';?>><?=$l['TT5'];?></option>
			<option value="ceil30"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "ceil30" ? ' selected=""' : '';?>><?=$l['TT6'];?></option>
			<option value="floor30"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "floor30" ? ' selected=""' : '';?>><?=$l['TT7'];?></option>
			<option value="ceil60"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "ceil60" ? ' selected=""' : '';?>><?=$l['TT8'];?></option>
			<option value="floor60"<?=isset($_POST['time_tracking']) && $_POST['time_tracking'] == "floor60" ? ' selected=""' : '';?>><?=$l['TT9'];?></option>
		</select>
	</div>

	<div class="form-group">
		<label><?=$l['INCLMIN'];?></label>
		<input type="text" class="form-control customer-input" placeholder="0" value="<?=isset($_POST["time_contingent"]) ? $_POST["time_contingent"] : "0";?>" name="time_contingent">
	</div>

	<div class="form-group">
		<label><?=$l['CUST'];?></label>
		<input type="text" class="form-control customer-input" placeholder="<?=$l['CNA'];?>" value="<?=ci(!empty($_REQUEST["user"]) ? $_REQUEST["user"] : "0");?>">
		<input type="hidden" name="user" value="<?=!empty($_REQUEST["user"]) ? $_REQUEST["user"] : "0";?>">
		<div class="customer-input-results"></div>
	</div>

  <?php
$aSql = $db->query("SELECT * FROM admins ORDER BY name");
    ?>
  <div class="form-group">
    <label><?=$l['ADMIN'];?></label>
	<select name="admin" class="form-control">
		<option value="0"><?=$l['ANA'];?></option>
		<?php if ($aSql->num_rows > 0) {?>
		<option disabled>--------------------</option>
		<?php }
    while ($c = $aSql->fetch_object()) {?>
		<option value="<?=$c->ID;?>" <?php if ($_POST['admin'] == $c->ID) {
        echo "selected=\"selected\"";
    }
        ?>><?=$c->name;?> (<?=$c->username;?>)</option>
		<?php }?>
	</select>
  </div>

  <div class="checkbox">
	<label>
	  <input type="checkbox" name="show_details" value="1" <?=isset($_POST['submit']) && isset($_POST['show_details']) ? "checked" : "";?>> <?=$l['DET'];?>
	</label>
  </div>

  <center><button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['DO'];?></button></center></form>

<?php }?>