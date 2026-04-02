<?php
$l = $lang['CMS_MENU'];
$al = $lang['ADD_MENU'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "cms_menu");} else {

    function _l($e, $l = "")
    {
        global $CFG;
        if (empty($l)) {
            $l = $CFG['LANG'];
        }

        if (unserialize($e) !== false) {
            $arr = unserialize($e);
            $pos = array_key_exists($l, $arr) ? $l : array_keys($arr)[0];
            return $arr[$pos];
        }

        return $e;
    }

    if (!empty($_GET['id']) && is_numeric($_GET['id']) && $db->query("SELECT ID FROM cms_menu WHERE ID = " . intval($_GET['id']))->num_rows == 1) {
        $info = $db->query("SELECT * FROM cms_menu WHERE ID = " . intval($_GET['id']))->fetch_object();

        if (isset($_POST['edit'])) {
            try {
                foreach ($_POST as $k => $v) {
                    $vari = "post" . ucfirst(strtolower($k));
                    $$vari = $db->real_escape_string($v);
                }

                $names = array();
                foreach ($languages as $lang_key => $lang_name) {
                    if (empty($_POST['name_' . $lang_key])) {
                        throw new Exception($al['ERR1']);
                    }

                    $names[$lang_key] = $_POST['name_' . $lang_key];
                }

                $postName = $db->real_escape_string(serialize($names));

                if (empty($postContent) || !in_array($postContent, ["page", "link", "menu", "divider"])) {
                    throw new Exception($al['ERR2']);
                }

                if ($postContent == "page") {
                    if (empty($postPage)) {
                        throw new Exception($al['ERR3']);
                    }

                    $postPage = strip_tags($postPage);
                }

                if ($postContent == "link") {
                    if (empty($postLink)) {
                        throw new Exception($al['ERR4']);
                    }

                    if (!isset($postTarget) || !in_array($postTarget, ["", "_blank"])) {
                        throw new Exception($al['ERR5']);
                    }
                }

                function is_menu($parent)
                {
                    global $db, $CFG;

                    $sql = $db->query("SELECT type FROM cms_menu WHERE ID = " . abs(intval($parent)) . " AND parent = 0");
                    if ($sql->num_rows != 1) {
                        return false;
                    }

                    $type = unserialize($sql->fetch_object()->type);
                    return is_array($type) && $type["type"] == "menu";
                }

                if ($postContent == "page" || $postContent == "link") {
                    if (!isset($postParent) || !is_numeric($postParent) || $postParent < 0 || ($postParent != 0 && !is_menu($postParent))) {
                        throw new Exception($al['ERR6']);
                    }

                } else if ($postContent == "menu") {
                    $postParent = "0";
                } else if ($postContent == "divider") {
                    if (!isset($postParent) || !is_numeric($postParent) || $postParent <= 0 || !is_menu($postParent)) {
                        throw new Exception($al['ERR7']);
                    }

                }

                if (!isset($postPrio) || !is_numeric($postPrio)) {
                    throw new Exception($al['ERR8']);
                }

                if (!isset($postStatus) || ($postStatus != 0 && $postStatus != 1 && $postStatus != 2 && $postStatus != 3)) {
                    throw new Exception($al['ERR9']);
                }

                $type = [
                    "type" => $postContent,
                ];

                switch ($postContent) {
                    case "page":
                        $type["page"] = $postPage;
                        break;

                    case "link":
                        $type["link"] = $postLink;
                        $type["target"] = $postTarget;
                        break;
                }

                if ($type["type"] != "menu" && $db->query("SELECT 1 FROM cms_menu WHERE parent = " . $info->ID)->num_rows) {
                    throw new Exception($l['ERR1']);
                }

                $relid = 0;
                $type = serialize($type);

                $db->query("UPDATE cms_menu SET name = '$postName', parent = '$postParent', prio = '$postPrio', type = '$type', relid = '$relid', status = '$postStatus' WHERE ID = {$info->ID} LIMIT 1");
                if ($db->errno) {
                    throw new Exception($al['ERR10']);
                }

                alog("cms", "menu_entry_changed", $_GET['id']);

                $error = "<div class=\"alert alert-success\">{$l['SUC']} <a href=\"?p=cms_menu\">{$al['OVERVIEW']}</a></div>";
                $info = $db->query("SELECT * FROM cms_menu WHERE ID = " . intval($_GET['id']))->fetch_object();
                unset($_POST);
            } catch (Exception $ex) {
                $error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
            }
        }

        ?>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=_l($info->name);?> <small><?=$l['SUBT'];?></small></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>

    <?=isset($error) ? $error : "";?>

    <form role="form" method="POST">

  <label><?=$al['NAME'];?></label>
  <?php foreach ($languages as $lang_key => $lang_name) {?>
<a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
<?php }?>
<br />
<?php foreach ($languages as $lang_key => $lang_name) {
            $name = isset($_POST['name_' . $lang_key]) ? $_POST['name_' . $lang_key] : _l($info->name, $lang_key);
            ?>
    <div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>><input type="text" name="name_<?=$lang_key;?>" class="form-control" placeholder="<?=$al['NAME'];?>" value="<?=isset($_POST['name_' . $lang_key]) ? $_POST['name_' . $lang_key] : $name;?>">
          </div>
<?php }?>
  <?php
$type = unserialize($info->type);

        if (!isset($_POST['content'])) {
            $_POST['content'] = $type['type'];
        }
        ?>
  <div class="form-group" style="margin-top: 10px;">
    <label><?=$al['TYPE'];?></label>
		<select name="content" class="form-control">
			<option value="page"><?=$al['TPAGE'];?></option>
			<option value="link" <?php if (isset($_POST['content']) && $_POST['content'] == "link") {
            echo "selected";
        }
        ?>><?=$al['TLINK'];?></option>
			<option value="menu" <?php if (isset($_POST['content']) && $_POST['content'] == "menu") {
            echo "selected";
        }
        ?>><?=$al['TMENU'];?></option>
			<option value="divider" <?php if (isset($_POST['content']) && $_POST['content'] == "divider") {
            echo "selected";
        }
        ?>><?=$al['TDIVIDER'];?></option>
		</select>
  </div>

	<script>
	function showAdd() {
		var val = $("[name=content]").val();

		if (val == "link") {
			$("#page").hide();
			$("#link").show();
		} else if (val == "page") {
			$("#page").show();
			$("#link").hide();
		} else {
			$("#page").hide();
			$("#link").hide();
		}

		if (val == "menu") {
			$("#parent").hide();
		} else {
			$("#parent").show();
		}
	}

	$(document).ready(showAdd);

	$("[name=content]").change(showAdd);
	</script>

    <?php
if (!isset($_POST['page']) && array_key_exists("page", $type)) {
            $_POST['page'] = $type['page'];
        }
        ?>

	<div class="form-group" style="display: none;" id="page">
    <label><?=$al['PAGE'];?></label>
		<input class="form-control" name="page" value="<?=isset($_POST['page']) ? htmlentities($_POST['page']) : "/";?>" placeholder="/">
		<p class="help-block"><?=$al['PAGEH'];?></p>
	</div>

    <?php
if (!isset($_POST['link']) && array_key_exists("link", $type)) {
            $_POST['link'] = $type['link'];
        }

        if (!isset($_POST['target']) && array_key_exists("target", $type)) {
            $_POST['target'] = $type['target'];
        }
        ?>

	<div style="display: none;" id="link">
		<div class="form-group">
			<label><?=$al['URL'];?></label>
			<input class="form-control" name="link" value="<?=isset($_POST['link']) ? htmlentities($_POST['link']) : "";?>" placeholder="https://sourceway.de/">
		</div>

		<div class="form-group">
			<label><?=$al['LINKTYPE'];?></label>
			<select name="target" class="form-control">
				<option value=""><?=$al['LINKSAME'];?></option>
				<option value="_blank"<?php if (isset($_POST['target']) && $_POST['target'] == "_blank") {
            echo " selected";
        }
        ?>><?=$al['LINKNEW'];?></option>
			</select>
		</div>
	</div>

    <?php
if (!isset($_POST['parent'])) {
            $_POST['parent'] = $info->parent;
        }
        ?>

	<div class="form-group" id="parent">
    <label><?=$al['PARENT'];?></label>
	<select name="parent" class="form-control">
		<option value="0"><?=$al['NOPARENT'];?></option>
        <?php $parentSql = $db->query("SELECT * FROM cms_menu WHERE parent = 0");
        while ($parent = $parentSql->fetch_object()) {
            $type = unserialize($parent->type);
            if (!is_array($type) || $type['type'] != "menu") {
                continue;
            }

            if (isset($_POST['parent']) && $parent->ID == $_POST['parent']) {
                echo "<option value=\"" . $parent->ID . "\" selected>" . unserialize($parent->name)[$CFG['LANG']] . "</option>";
            } else {
                echo "<option value=\"" . $parent->ID . "\">" . unserialize($parent->name)[$CFG['LANG']] . "</option>";
            }

        }
        ?>
	</select>
  	<p class="help-block"><?=$al['PARENTH'];?></p>
  </div>

	<div class="form-group">
    <label><?=$al['PRIO'];?></label>
    <input type="text" name="prio" value="<?=isset($_POST['prio']) ? $_POST['prio'] : intval($info->prio);?>" style="max-width: 80px" class="form-control">
	<p class="help-block"><?=$al['PRIOH'];?></p>
  </div>

  <div class="form-group">
<label><?=$al['STATUS'];?></label><br />
<label class="radio-inline">
<input type="radio" name="status" value="1" <?=isset($_POST['status']) ? ($_POST['status'] == "1" ? "checked" : "") : ($info->status == "1" ? "checked" : "");?>>
<?=$al['ACTIVE'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="2" <?=isset($_POST['status']) ? ($_POST['status'] == "2" ? "checked" : "") : ($info->status == "2" ? "checked" : "");?>>
<?=$al['LOGGED_IN'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="3" <?=isset($_POST['status']) ? ($_POST['status'] == "3" ? "checked" : "") : ($info->status == "3" ? "checked" : "");?>>
<?=$al['LOGGED_OUT'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="0" <?=isset($_POST['status']) ? ($_POST['status'] == "0" ? "checked" : "") : ($info->status == "3" ? "checked" : "");?>>
<?=$al['INACTIVE'];?>
</label>
</div>

  <center><button type="submit" class="btn btn-primary btn-block" name="edit"><?=$l['SAVE'];?></button></center></form>
    <?php
} else {
        ?>

            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE2'];?> <a href="?p=add_cms_menu" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="row">
				<div class="col-lg-12">
				<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM  cms_menu WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' OR parent = '" . $db->real_escape_string($_GET['delete']) . "'");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['DELETED'] . '</div>';
                alog("cms", "menu_entry_deleted", $_GET['delete']);
            }
        }
        ?>

<?php
$sql = $db->query("SELECT * FROM cms_menu WHERE parent = 0 ORDER BY prio ASC");
        if ($sql->num_rows <= 0) {
            echo "<i>{$l['NT']}</i>";
        } else {

            ?>
<div class="tree">
    <ul>
<?php

            while ($parent = $sql->fetch_object()) {
                if ($parent->status == 0) {
                    $parent->name = '<font color="red">' . _l($parent->name) . '</font>';
                }

                if ($parent->status == 2) {
                    $parent->name = '<font color="blue">' . _l($parent->name) . '</font>';
                }

                ?>
        <li>
            <span><?=_l($parent->name);?> &nbsp; <a href="?p=cms_menu&id=<?=$parent->ID;?>"><i class="fa fa-edit"></i></a></span> <a href="?p=cms_menu&delete=<?=$parent->ID;?>" onclick="return confirm('<?=$l['RDD'];?>');"><i class="fa fa-times fa-lg"></i></a>
            <?php
$childSql = $db->query("SELECT * FROM cms_menu WHERE parent = " . $parent->ID . " ORDER BY prio ASC");
                if ($childSql->num_rows > 0) {
                    ?>
            <ul>
            <?php while ($child = $childSql->fetch_object()) {
                        if ($child->status == 0) {
                            $child->name = '<font color="red">' . _l($child->name) . '</font>';
                        }

                        if ($child->status == 2) {
                            $child->name = '<font color="blue">' . _l($child->name) . '</font>';
                        }

                        if ($child->type == "divider") {
                            $child->name = '<font color="green">' . _l($child->name) . '</font>';
                        }

                        ?>
                <li>
                	<span><?=_l($child->name);?> &nbsp; <a href="?p=cms_menu&id=<?=$child->ID;?>"><i class="fa fa-edit"></i></a></span> <a href="?p=cms_menu&delete=<?=$child->ID;?>" onclick="return confirm('<?=$l['RDD'];?>');"><i class="fa fa-times fa-lg"></i></a>
                </li>
            <?php }?>
            </ul>
            <?php }?>
        </li>
<?php }?>
    </ul>
</div>
<?php }?>
<style>
.tree {
    min-height:20px;
    margin-bottom:20px;
}
.tree li {
    list-style-type:none;
    margin:0;
    padding:10px 5px 0 5px;
    position:relative
}
.tree li::before, .tree li::after {
    content:'';
    left:-20px;
    position:absolute;
    right:auto
}
.tree li::before {
    border-left:1px solid #999;
    bottom:50px;
    height:100%;
    top:0;
    width:1px
}
.tree li::after {
    border-top:1px solid #999;
    height:20px;
    top:25px;
    width:25px
}
.tree li span {
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border:1px solid #999;
    border-radius:5px;
    display:inline-block;
    padding:3px 8px;
    text-decoration:none
}
.tree li.parent_li>span {
    cursor:pointer
}
.tree>ul>li::before, .tree>ul>li::after {
    border:0
}
.tree li:last-child::before {
    height:30px
}
.tree li.parent_li>span:hover, .tree li.parent_li>span:hover+ul li span {
    background:#eee;
    border:1px solid #94a0b4;
    color:#000
}
</style>
				</div>
            </div>
            <!-- /.row -->
<?php }}?>