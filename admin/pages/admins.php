<?php
$l = $lang['ADMINS'];
$al = $lang['ADD_ADMIN'];

menu("settings");
title($l['TITLE']);
$adminRights = AdminRights::get();

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(35)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "admins");} else {?>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$l['TITLE'];?></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>

<?php ob_start();if (isset($_GET['id']) && $db->query("SELECT * FROM admins WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->num_rows == 1) {
    $info = $db->query("SELECT * FROM admins WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->fetch_object();
    ?>


<?php
if (isset($_POST['submit'])) {

        class AdminException extends Exception
            {}

        try {

            $name = $_POST['name'];
            if (count(explode(' ', $name)) < 2) {
                throw new AdminException($al['ERR1']);
            }

            if (!isset($_POST['username']) || $_POST['username'] == "" || $db->query("SELECT * FROM admins WHERE username = '" . $db->real_escape_string($_POST['username']) . "' AND ID != '" . $db->real_escape_string($_GET['id']) . "'")->num_rows != 0) {
                throw new AdminException($al['ERR2']);
            }

            if (!$val->email($_POST['email']) || $db->query("SELECT * FROM admins WHERE email = '" . $db->real_escape_string($_POST['email']) . "' AND ID != '" . $db->real_escape_string($_GET['id']) . "'")->num_rows != 0) {
                throw new AdminException($al['ERR3']);
            }

            $password = $_POST['password'];
            if (strlen(trim($password)) < 8 && !empty($password) && $password != "x") {
                throw new AdminException($al['ERR4']);
            }

            if (empty($password) || $password == "x") {
                $salt = $db->real_escape_string($info->salt);
                $password = $db->real_escape_string($info->password);
            } else {
                $salt = $sec->generateSalt();
                $password = $db->real_escape_string($sec->adminHash($password, $salt, $_POST['pw_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1));
            }

            $amLanguage = $db->real_escape_string($_POST['language']);
            if (!isset($_POST['language']) || trim($amLanguage) == "" || !file_exists(__DIR__ . "/../../languages/admin.$amLanguage.php")) {
                throw new AdminException($al['ERR5']);
            }

            if ($_POST['tfa'] == "") {
                $_POST['tfa'] = "none";
            }

            $notes = $_POST['notes'];
            $escapeVar = array("name", "username", "tfa", "notes", "email", "language", "call_info");
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

            $ci = "";
            if (!empty($_POST['telephone']) && array_key_exists($_POST['telephone'], $telephone->get())) {
                $ci = $db->real_escape_string($_POST['telephone']);
            }

            $ci .= "|" . $call_info;
            $pp = max(10, intval($_POST['per_page']));

            $hide_sidebar = (int) (isset($_POST['hide_sidebar']) && $_POST['hide_sidebar'] == "1");
            $open_menu = (int) (isset($_POST['open_menu']) && $_POST['open_menu'] == "1");
            $next_ticket = (int) (isset($_POST['next_ticket']) && $_POST['next_ticket'] == "1");
            if (!$db->query("UPDATE admins SET next_ticket = $next_ticket, name = '$name', rights = '$rights', notifications = '$notifications', password = '$password', salt = '$salt', tfa = '$tfa', per_page = $pp, language = '$language', notes = '$notes', email = '$email', hide_sidebar = $hide_sidebar, open_menu = $open_menu, call_info = '$ci' WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")) {
                throw new AdminException($l['ERR6'] . " " . $db->error);
            }

            $msg = '<div class="alert alert-success">' . $l['SUC'] . '</div>';

            alog("admins", "changed", $_GET['id']);

            if ($adminInfo->ID == $_GET['id']) {
                // The current administrator changes himself, change his session and - if set - his cookie
                $tfaCdt = "";
                if ($_POST['tfa'] != "none") {
                    $tfaCdt = ":" . $_POST['tfa'];
                }

                $session->set("credentials", $adminInfo->username . ":" . $password . $tfaCdt);

                if (isset($_COOKIE['admin_auth']) && $db->query("SELECT ID FROM admin_cookie WHERE user = '" . $adminInfo->ID . "' AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' LIMIT 1")->num_rows == 1) {
                    // Update cookie in database
                    $auth = $password . $tfaCdt;
                    $db->query("UPDATE admin_cookie SET auth = '$auth' WHERE user = '" . $adminInfo->ID . "' AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' LIMIT 1");
                }
            }

            unset($_POST);
        } catch (AdminException $ex) {

            $msg = '<div class="alert alert-danger">
		<b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $ex->getMessage() . '
		</div>';

        }

    }
    $info = $db->query("SELECT * FROM admins WHERE ID = '" . $db->real_escape_string($_GET['id']) . "' LIMIT 1")->fetch_object();
    ?>

<div class="row">
<div class="col-lg-12">
<h1 class="page-header"><?=$l['EA'];?></h1>
        </div></div>
<?=isset($msg) ? $msg : "";?>

<?php
$authRequired = "AND (auth = '" . $info->password;
    if (trim($info->tfa) == "" || $info->tfa == "none") {
        $authRequired .= "' OR auth LIKE '" . $info->password . ":%')";
    } else {
        $authRequired .= ":" . $info->tfa . "')";
    }

    $myCookie = isset($_COOKIE['admin_auth']) && trim($_COOKIE['admin_auth']) != "" ? $db->query("SELECT * FROM admin_cookie WHERE user = '" . $db->real_escape_string($_GET['id']) . "' AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' AND valid >= " . time() . " $authRequired")->num_rows : false;

    if (isset($_GET['cookie_delete'])) {
        $deleted = 0;
        if ($_GET['cookie_delete'] == "all") {
            $deleted += $db->query("SELECT * FROM admin_cookie WHERE user = '" . $db->real_escape_string($_GET['id']) . "' AND valid >= " . time() . " $authRequired")->num_rows;
            $db->query("DELETE FROM admin_cookie WHERE user = '" . $db->real_escape_string($_GET['id']) . "' AND valid >= " . time() . " $authRequired");
            alog("admins", "cookies_deleted", $_GET['id']);
        } else if ($_GET['cookie_delete'] == "all_others") {
            $deleted += $db->query("SELECT * FROM admin_cookie WHERE string != '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' AND user = '" . $db->real_escape_string($_GET['id']) . "' AND valid >= " . time() . " $authRequired")->num_rows;
            $db->query("DELETE FROM admin_cookie WHERE string != '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' AND user = '" . $db->real_escape_string($_GET['id']) . "' AND valid >= " . time() . " $authRequired");
            alog("admins", "other_cookies_deleted", $_GET['id']);
        }

        if ($deleted <= 0) {
            echo "<div class=\"alert alert-danger\">" . $l['NC'] . "</div>";
        } else if ($deleted == 1) {
            echo "<div class=\"alert alert-success\">{$l['1C']}</div>";
        } else {
            echo "<div class=\"alert alert-success\">" . str_replace("%d", $deleted, $l['XC']) . "</div>";
        }

    }

    $cookieSql = $db->query("SELECT * FROM admin_cookie WHERE user = '" . $db->real_escape_string($_GET['id']) . "' AND valid >= " . time() . " $authRequired");
    if ($cookieSql->num_rows == 1) {
        ?>
<?php
if (!$myCookie) {
            ?>
<div class="alert alert-warning"><?=$l['WH1C'];?> <a href="?p=admins&id=<?=$_GET['id'];?>&cookie_delete=all"><?=$l['DELC'];?></a>?</div>
<?php } else {?>
<div class="alert alert-warning"><?=$l['WH1CI'];?> <a href="?p=admins&id=<?=$_GET['id'];?>&cookie_delete=all"><?=$l['DELCI'];?></a>?</div>
<?php }?>
<?php
} else if ($cookieSql->num_rows > 1) {
        ?>
<?php
if ($myCookie) {
            ?>
<div class="alert alert-warning"><?=str_replace("%d", $cookieSql->num_rows, $l['WHXCI']);?> <a href="?p=admins&id=<?=$_GET['id'];?>&cookie_delete=all"><?=$l['DELAC'];?></a> <?=$l['OR'];?> <a href="?p=admins&id=<?=$_GET['id'];?>&cookie_delete=all_others"><?=$l['DELOC'];?></a>?</div>
<?php } else {?>
<div class="alert alert-warning"><?=str_replace("%d", $cookieSql->num_rows, $l['WHXC']);?> <a href="?p=admins&id=<?=$_GET['id'];?>&cookie_delete=all"><?=$l['DELAC'];?></a>?</div>
<?php }?>
<?php
}
    ?>

    <form role="form" method="POST" id="edit_admin">

  <div class="form-group">
    <label><?=$al['NAME'];?></label>
   <input type="text" name="name" class="form-control" value="<?=isset($_POST['name']) ? $_POST['name'] : $info->name;?>">
  </div>

  <div class="form-group">
    <label><?=$al['EMAIL'];?></label>
   <input type="text" name="email" class="form-control" value="<?=isset($_POST['email']) ? $_POST['email'] : $info->email;?>">
  </div>

<div class="form-group">
    <label><?=$al['USERNAME'];?></label>
   <input type="text" name="username" class="form-control" value="<?=isset($_POST['username']) ? $_POST['username'] : $info->username;?>">
  </div>

  <div class="form-group">
    <label><?=$al['PASSWORD'];?></label>
      <input type="text" name="password" id="pwd"
             value="<?=$sec->adminHash("123") == "123" ? $info->password : (isset($_POST['password']) && ($_POST['pw_type'] != "hashed" || $CFG['CLIENTSIDE_HASHING_ADMIN'] != 1) ? $_POST['password'] : "");?>"
             class="form-control" placeholder="<?=$l['NEWPW'];?>"
             value="<?=isset($_POST['password']) ? $_POST['password'] : "";?>">
      <input type="hidden" id="pwd_hashed" value=""/><input type="hidden" name="pw_type" id="pw_type" value="plain"/>
  </div>

  <div class="form-group">
    <label><?=$al['PER_PAGE'];?></label>
   <input type="number" min="10" name="per_page" class="form-control" value="<?=isset($_POST['per_page']) ? max(10, intval($_POST['per_page'])) : $info->per_page;?>">
  </div>

  <div class="form-group">
    <label><?=$al['TFA'];?></label>
   <input type="text" name="tfa" class="form-control" value="<?=isset($_POST['tfa']) && $_POST['tfa'] != "none" ? $_POST['tfa'] : ($info->tfa != "none" ? $info->tfa : "");?>">
  </div>

  <?php $phone = false;?>

  <div class="form-group">
    <label><?=$l['TEL'];?></label>
   <select name="telephone" class="form-control">
   <option value=""><?=$l['NOTEL'];?></option>
   <option disabled="">----------</option>
	<?php foreach ($telephone->get() as $key => $obj) {$name = $obj->getName();?>
	<option value="<?=$key;?>"<?php if ((!isset($_POST['telephone']) && explode("|", $info->call_info)[0] == $key) || (isset($_POST['telephone']) && $_POST['telephone'] == $key)) {echo " selected=\"selected\"";
        $phone = true;}?>><?=$name;?></option>
	<?php }?>
   </select>
  </div>

  <script>
  $("[name=telephone]").change(function(){
  	if($(this).val() != ""){
  		$("#call_info").show();
  	} else {
  		$("#call_info").hide();
  	}
  });
  </script>

  <div class="form-group" id="call_info" style="<?=!$phone ? ' display: none' : "";?>">
  	<label><?=$l['STEL'];?></label>
  	<input type="text" name="call_info" class="form-control" value="<?php if (isset($_POST['call_info'])) {echo htmlentities($_POST['call_info']);} else {
        $ex = explode("|", $info->call_info);
        array_shift($ex);
        echo htmlentities(implode("|", $ex));
    }?>">
  </div>

  <div class="form-group">
    <label><?=$al['LANGUAGE'];?></label>
   <select name="language" class="form-control">
	<?php foreach ($adminLanguages as $key => $name) {?>
	<option value="<?=$key;?>"<?php if ((!isset($_POST['language']) && $info->language == $key) || (isset($_POST['language']) && $_POST['language'] == $key)) {
        echo " selected=\"selected\"";
    }
        ?>><?=$name;?></option>
	<?php }?>
   </select>
  </div>

  <div class="checkbox">
  	<label>
  	  <input type="checkbox" name="hide_sidebar" value="1"<?=isset($_POST['hide_sidebar']) || (!isset($_POST['submit']) && $info->hide_sidebar == 1) ? ' checked="checked"' : '';?>> <?=$al['SIDEBAR'];?>
  	</label>
  </div>

  <div class="checkbox">
  	<label>
  	  <input type="checkbox" name="open_menu" value="1"<?=isset($_POST['open_menu']) || (!isset($_POST['submit']) && $info->open_menu == 1) ? ' checked="checked"' : '';?>> <?=$al['MENU'];?>
  	</label>
  </div>

  <div class="checkbox">
  	<label>
  	  <input type="checkbox" name="next_ticket" value="1"<?=isset($_POST['next_ticket']) || (!isset($_POST['submit']) && $info->next_ticket == 1) ? ' checked="checked"' : '';?>> <?=$al['NEXT_TICKET'];?>
  	</label>
  </div>

  <?php if ($adminInfo->ID != $_GET['id']) {?>
  <div class="form-group">
    <label><?=$l['NOTES'];?></label>
   <textarea name="notes" class="form-control" style="resize:none; height:200px;"><?=$info->notes;?></textarea>
  </div>
  <?php }?>

  <div class="form-group">
    <label><?=$al['RIGHTS'];?></label> (<a href="javascript:select_all()"><?=$al['SELECT_ALL'];?></a> | <a href="javascript:deselect_all()"><?=$al['DESELECT_ALL'];?></a>)
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
$info->rights = unserialize($info->rights);
    if (!is_array($info->rights)) {
        $info->rights = array();
    }

    $i = 1;
    foreach ($adminRights as $id => $nm) {
        if ($i == 1) {
            echo '<div class="row">';
        }

        ?>
		<div class="col-md-4"><label style="font-weight:normal;"><input type="checkbox" <?php if (in_array($id, $info->rights)) {
            echo "checked=\"checked\"";
        }
        ?> name="rights[]" value="<?=$id;?>"> <?=$nm;?></label></div>
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
    <label><?=$al['NOTIFICATIONS'];?></label> (<a href="javascript:select_alln()"><?=$al['SELECT_ALL'];?></a> | <a href="javascript:deselect_alln()"><?=$al['DESELECT_ALL'];?></a>)
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
$info->notifications = unserialize($info->notifications);
    if (!is_array($info->notifications)) {
        $info->notifications = array();
    }

    $i = 1;
    $mailSql = $db->query("SELECT name FROM email_templates WHERE admin_notification = 1");
    while ($row = $mailSql->fetch_object()) {
        $name = trim($row->name);
        if ($i == 1) {
            echo '<div class="row">';
        }

        ?>
		<div class="col-md-4"><label style="font-weight:normal;"><input type="checkbox" <?php if (in_array($name, $info->notifications)) {
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

  <center><button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['SAVE'];?></button></center></form>
<?php }
    $edit = ob_get_contents();
    ob_end_clean();?>

			<div class="row">
				<div class="col-lg-12">
				<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0 && $db->query("SELECT * FROM admins")->num_rows > 1) {
        $db->query("DELETE FROM  admins WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' AND ID != '" . $adminInfo->ID . "' LIMIT 1");
        if ($db->affected_rows > 0) {
            echo "<div class=\"alert alert-success\">{$l['DELETED']}</div>";
            alog("admins", "deleted", $_GET['delete']);
        }
    }

    if (isset($_POST['delete_selected']) && is_array($_POST['admin'])) {
        $d = 0;
        foreach ($_POST['admin'] as $id) {
            $db->query("DELETE FROM  admins WHERE ID = '" . $db->real_escape_string($id) . "' AND ID != '" . $adminInfo->ID . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                alog("admins", "deleted", $id);
            }
        }

        if ($d == 1) {
            echo "<div class=\"alert alert-success\">{$l['DEL1']}</div>";
        } else if ($d > 0) {
            echo "<div class=\"alert alert-success\">" . str_replace("%d", $d, $l['DELX']) . "</div>";
        }

    }

    if (isset($_POST['free_selected']) && is_array($_POST['admin'])) {
        $d = 0;
        foreach ($_POST['admin'] as $id) {
            $sql = $db->query("SELECT rights FROM admins WHERE ID = '" . $db->real_escape_string($id) . "' AND ID != '" . $adminInfo->ID . "'");
            if ($sql->num_rows != 1) {
                continue;
            }

            $drights = $rights = unserialize($sql->fetch_object()->rights);
            if ($drights == null) {
                $drights = $rights = array();
            }

            array_push($rights, 1);
            $rights = array_unique($rights);

            if ($drights == $rights) {
                continue;
            }

            $rights = serialize($rights);

            $db->query("UPDATE  admins SET rights = '" . $db->real_escape_string($rights) . "' WHERE ID = '" . $db->real_escape_string($id) . "' AND ID != '" . $adminInfo->ID . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                alog("admins", "unlocked", $id);
            }
        }

        if ($d == 1) {
            echo "<div class=\"alert alert-success\">{$l['FREE1']}</div>";
        } else if ($d > 0) {
            echo "<div class=\"alert alert-success\">" . str_replace("%d", $d, $l['FREEX']) . "</div>";
        }

    }

    if (isset($_POST['lock_selected']) && is_array($_POST['admin'])) {
        $d = 0;
        foreach ($_POST['admin'] as $id) {
            $sql = $db->query("SELECT rights FROM admins WHERE ID = '" . $db->real_escape_string($id) . "' AND ID != '" . $adminInfo->ID . "'");
            if ($sql->num_rows != 1) {
                continue;
            }

            $drights = $rights = array_unique(unserialize($sql->fetch_object()->rights));
            if ($drights == null) {
                $drights = $rights = array();
            }

            if (($key = array_search(1, $rights)) !== false) {
                unset($rights[$key]);
            }

            if ($drights == $rights) {
                continue;
            }

            $rights = serialize($rights);

            $db->query("UPDATE  admins SET rights = '" . $db->real_escape_string($rights) . "' WHERE ID = '" . $db->real_escape_string($id) . "' AND ID != '" . $adminInfo->ID . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                alog("admins", "locked", $id);
            }
        }

        if ($d == 1) {
            echo "<div class=\"alert alert-success\">{$l['LOCK1']}</div>";
        } else if ($d > 0) {
            echo "<div class=\"alert alert-success\">" . str_replace("%d", $d, $l['LOCKX']) . "</div>";
        }

    }
    ?>

				<a href="?p=add_admin" class="btn btn-success"><?=$l['ADDA'];?></a><br /><br /><div class="table-responsive">
					<table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
		<th><?=$al['NAME'];?></th>
		<th><?=$al['USERNAME'];?></th>
		<th><?=$al['RIGHTS'];?></th>
		<th width="60px"></th>
	</tr>

	<form method="POST">
	<?php

    $sql = $db->query("SELECT * FROM admins ORDER BY name ASC");

    if ($sql->num_rows == 0) {
        echo "<tr><td colspan=\"4\"><center>{$l['NT']}</center></td></tr>";
    } else {
        while ($d = $sql->fetch_object()) {
            $us = unserialize($d->rights);
            ?>
			<tr>
				<td><?php if ($adminInfo->ID == $d->ID) {?><center>-</center><?php } else {?><input type="checkbox" class="checkbox" name="admin[]" value="<?=$d->ID;?>" onchange="javascript:toggle();" /><?php }?></td>
				<td><a href="?p=admin&id=<?=$d->ID;?>"><?=$d->name;?></a> <?php if (!$ari->check(1, $d->ID)) {?><small><font color="red"><?=$l['NA'];?></font></small><?php }?></td>
				<td><?=$d->username;?></td>
				<td><?=!$us ? 0 : (count($us) > count($adminRights) ? count($adminRights) : count($us));?> (<?=count($adminRights);?>)</td>
				<td width="60px">
			<a href="?p=admins&id=<?=$d->ID;?>" title="Bearbeiten"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;
			<a href="?p=admins&delete=<?=$d->ID;?>" title="L&ouml;schen" onclick="return confirm('<?=$l['RD'];?>');"><i class="fa fa-times fa-lg"></i></a>
		</td>
			<?php
}
    }
    ?>
</table></div><?=$l['SELECTED'];?>: <input type="submit" name="free_selected" class="btn btn-success" value="<?=$l['FREE'];?>"> <input type="submit" name="lock_selected" class="btn btn-warning" value="<?=$l['LOCK'];?>"> <input type="submit" name="delete_selected" class="btn btn-danger" value="<?=$l['DELETE'];?>"></form><?=$edit;?>
				</div>
            </div>
            <!-- /.row --><?php }?>