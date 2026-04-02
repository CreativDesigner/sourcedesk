<?php
$l = $lang['ADD_ADMIN'];
title($l['TITLE']);
menu("settings");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(35)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_admin");} else {?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>
		<?php
if (isset($_POST['submit'])) {

    class AdminException extends Exception
        {}

    try {

        $name = $_POST['name'];
        if (count(explode(' ', $name)) < 2) {
            throw new AdminException($l['ERR1']);
        }

        if (!isset($_POST['username']) || $_POST['username'] == "" || $db->query("SELECT ID FROM admins WHERE username = '" . $db->real_escape_string($_POST['username']) . "'")->num_rows != 0) {
            throw new AdminException($l['ERR2']);
        }

        if (!$val->email($_POST['email']) || $db->query("SELECT ID FROM admins WHERE email = '" . $db->real_escape_string($_POST['email']) . "'")->num_rows != 0) {
            throw new AdminException($l['ERR3']);
        }

        $password = $_POST['password'];
        if (strlen($password) < 8) {
            throw new AdminException($l['ERR4']);
        }

        $salt = $sec->generateSalt();
        $password = $db->real_escape_string($sec->adminHash($password, $salt, $_POST['pw_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1));

        $amLanguage = $db->real_escape_string($_POST['language']);
        if (!isset($_POST['language']) || trim($amLanguage) == "" || !file_exists(__DIR__ . "/../../languages/admin.$amLanguage.php")) {
            throw new AdminException($l['ERR5']);
        }

        if ($_POST['tfa'] == "") {
            $_POST['tfa'] = "none";
        }

        $escapeVar = array("name", "username", "tfa", "email", "language");
        foreach ($escapeVar as $k) {
            $$k = $db->real_escape_string($_POST[$k]);
        }

        if (!is_array($_POST['rights'])) {
            $_POST['rights'] = array();
        }

        $rights = $db->real_escape_string(serialize($_POST['rights']));
        if (!is_array($_POST['notifications'])) {
            $_POST['notifications'] = array();
        }

        $notifications = $db->real_escape_string(serialize($_POST['notifications']));
        $hide_sidebar = (int) (isset($_POST['hide_sidebar']) && $_POST['hide_sidebar'] == "1");
        $open_menu = (int) (isset($_POST['open_menu']) && $_POST['open_menu'] == "1");
        $next_ticket = (int) (isset($_POST['next_ticket']) && $_POST['next_ticket'] == "1");
        $pp = max(10, intval($_POST['per_page']));
        if (!$db->query("INSERT INTO admins (`name`, `username`, `email`, `password`, `rights`, `notifications`, `tfa`, `language`, `salt`, `hide_sidebar`, `open_menu`, `per_page`, `next_ticket`) VALUES ('$name', '$username', '$email', '$password', '$rights', '$notifications', '$tfa', '$language', '$salt', $hide_sidebar, $open_menu, $pp, $next_ticket)")) {
            throw new AdminException($l['ERR6'] . " " . $db->error);
        }

        ?>
		<div class="alert alert-success"><?=$l['SUC'];?> <a href="?p=admins"><?=$l['OVERVIEW'];?></a></div>
		<?php
alog("general", "admin_created", $username);
        unset($_POST);
    } catch (AdminException $ex) {

        ?>
		<div class="alert alert-danger">
		<b><?=$lang['GENERAL']['ERROR'];?></b> <?=$ex->getMessage();?>
		</div>
		<?php

    }

}

    if (!isset($_POST['rights'])) {
        $_POST['rights'] = [];
    }

    if (!isset($_POST['notifications'])) {
        $_POST['notifications'] = [];
    }

    ?>
            <form role="form" method="POST" id="edit_admin">

  <div class="form-group">
    <label><?=$l['NAME'];?></label>
   <input type="text" name="name" class="form-control" value="<?php if (isset($_POST['name'])) {
        echo $_POST['name'];
    }
    ?>">
  </div>

  <div class="form-group">
    <label><?=$l['EMAIL'];?></label>
   <input type="text" name="email" class="form-control" value="<?php if (isset($_POST['email'])) {
        echo $_POST['email'];
    }
    ?>">
  </div>

<div class="form-group">
    <label><?=$l['USERNAME'];?></label>
   <input type="text" name="username" class="form-control" value="<?php if (isset($_POST['username'])) {
        echo $_POST['username'];
    }
    ?>">
  </div>

  <div class="form-group">
    <label><?=$l['PASSWORD'];?></label>
      <input type="text" name="password" id="pwd" class="form-control"
             value="<?php if (isset($_POST['password']) && ($CFG['CLIENTSIDE_HASHING_ADMIN'] != 1 || $_POST['pw_type'] != "hashed")) {
        echo $_POST['password'];
    }
    ?>">
      <input type="hidden" id="pwd_hashed" value=""/><input type="hidden" name="pw_type" id="pw_type" value="plain"/>
  </div>

  <div class="form-group">
    <label><?=$l['PER_PAGE'];?></label>
   <input type="number" min="10" name="per_page" class="form-control" value="<?=isset($_POST['per_page']) ? max(10, intval($_POST['per_page'])) : "25";?>">
  </div>

  <div class="form-group">
    <label><?=$l['TFA'];?></label>
   <input type="text" name="tfa" class="form-control" value="<?php if (isset($_POST['tfa']) && $_POST['tfa'] != "none") {
        echo $_POST['tfa'];
    }
    ?>">
  </div>

  <div class="form-group">
    <label><?=$l['LANGUAGE'];?></label>
   <select name="language" class="form-control">
	<?php foreach ($adminLanguages as $key => $name) {?>
	<option value="<?=$key;?>"<?php if (isset($_POST['language']) && $_POST['language'] == $key) {
        echo " selected=\"selected\"";
    }
        ?>><?=$name;?></option>
	<?php }?>
   </select>
  </div>

  <div class="checkbox">
  	<label>
  	  <input type="checkbox" name="hide_sidebar" value="1"<?=isset($_POST['hide_sidebar']) && $_POST['hide_sidebar'] == "1" ? ' checked="checked"' : '';?>> <?=$l['SIDEBAR'];?>
  	</label>
  </div>

  <div class="checkbox">
  	<label>
  	  <input type="checkbox" name="open_menu" value="1"<?=isset($_POST['open_menu']) && $_POST['open_menu'] == "1" ? ' checked="checked"' : '';?>> <?=$l['MENU'];?>
  	</label>
  </div>

  <div class="checkbox">
  	<label>
  	  <input type="checkbox" name="next_ticket" value="1"<?=isset($_POST['next_ticket']) && $_POST['next_ticket'] == "1" ? ' checked="checked"' : '';?>> <?=$l['NEXT_TICKET'];?>
  	</label>
  </div>

  <div class="form-group">
    <label><?=$l['RIGHTS'];?></label> (<a href="javascript:select_all()"><?=$l['SELECT_ALL'];?></a> | <a href="javascript:deselect_all()"><?=$l['DESELECT_ALL'];?></a>)
	<script type="text/javascript">
	function select_all() {
		for ( i = 0; i <= document.getElementsByName("rights[]").length; i++ ){
			document.getElementsByName("rights[]")[i].checked = "checked";
		}
	}

	function deselect_all() {
		for ( i = 0; i <= document.getElementsByName("rights[]").length; i++ ){
			document.getElementsByName("rights[]")[i].checked = "";
		}
	}
	</script>
	<?php
$i = 1;
    foreach ($adminRights as $id => $nm) {
        if ($i == 1) {
            echo '<div class="row">';
        }

        ?>
		<div class="col-md-4"><label style="font-weight:normal;"><input type="checkbox" name="rights[]" value="<?=$id;?>" <?php if (in_array($id, $_POST['rights'])) {
            echo "checked=\"checked\"";
        }
        ?>> <?=$nm;?></label></div>
	<?php if ($i == 3) {
            echo '</div>';
        }
        ?>
	<?php $i++;if ($i == 4) {
            $i = 1;
        }
    }?>
  </div><br />

  <div class="form-group">
    <label><?=$l['NOTIFICATIONS'];?></label> (<a href="javascript:select_alln()"><?=$l['SELECT_ALL'];?></a> | <a href="javascript:deselect_alln()"><?=$l['DESELECT_ALL'];?></a>)
	<script type="text/javascript">
	function select_alln() {
		for ( i = 0; i <= document.getElementsByName("notifications[]").length; i++ ){
			document.getElementsByName("notifications[]")[i].checked = "checked";
		}
	}

	function deselect_alln() {
		for ( i = 0; i <= document.getElementsByName("notifications[]").length; i++ ){
			document.getElementsByName("notifications[]")[i].checked = "";
		}
	}
	</script>
	<?php
$i = 1;
    $mailSql = $db->query("SELECT name FROM email_templates WHERE admin_notification = 1");
    while ($row = $mailSql->fetch_object()) {
        $name = trim($row->name);
        if ($i == 1) {
            echo '<div class="row">';
        }

        ?>
		<div class="col-md-4"><label style="font-weight:normal;"><input type="checkbox" <?php if (in_array($name, $_POST['notifications'])) {
            echo "checked=\"checked\"";
        }
        ?> name="notifications[]" value="<?=$name;?>"> <?=$name;?></label></div>
	<?php if ($i == 3) {
            echo '</div>';
        }
        ?>
	<?php $i++;if ($i == 4) {
            $i = 1;
        }
    }?>
  </div>

  <center><button type="submit" class="btn btn-success btn-block" name="submit"><?=$l['DO'];?></button></center></form>
		</div>
		<!-- /.col-lg-12 -->
	</div>
<?php }?>