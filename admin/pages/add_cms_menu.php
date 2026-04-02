<?php
$l = $lang['ADD_MENU'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_cms_menu");} else {

    if (isset($_POST['add'])) {

        try {
            foreach ($_POST as $k => $v) {
                $vari = "post" . ucfirst(strtolower($k));
                $$vari = $db->real_escape_string($v);
            }

            $names = array();
            foreach ($languages as $lang_key => $lang_name) {
                if (empty($_POST['name_' . $lang_key])) {
                    throw new Exception($l['ERR1']);
                }

                $names[$lang_key] = $_POST['name_' . $lang_key];
            }

            $postName = $db->real_escape_string(serialize($names));

            if (empty($postContent) || !in_array($postContent, ["page", "link", "menu", "divider"])) {
                throw new Exception($l['ERR2']);
            }

            if ($postContent == "page") {
                if (empty($postPage)) {
                    throw new Exception($l['ERR3']);
                }

                $postPage = strip_tags($postPage);
            }

            if ($postContent == "link") {
                if (empty($postLink)) {
                    throw new Exception($l['ERR4']);
                }

                if (!isset($postTarget) || !in_array($postTarget, ["", "_blank"])) {
                    throw new Exception($l['ERR5']);
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
                    throw new Exception($l['ERR6']);
                }

            } else if ($postContent == "menu") {
                $postParent = "0";
            } else if ($postContent == "divider") {
                if (!isset($postParent) || !is_numeric($postParent) || $postParent <= 0 || !is_menu($postParent)) {
                    throw new Exception($l['ERR7']);
                }

            }

            if (!isset($postPrio) || !is_numeric($postPrio)) {
                throw new Exception($l['ERR8']);
            }

            if (!isset($postStatus) || ($postStatus != 0 && $postStatus != 1 && $postStatus != 2 && $postStatus != 3)) {
                throw new Exception($l['ERR9']);
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

            $relid = 0;
            $type = serialize($type);

            $db->query("INSERT INTO cms_menu (`name`, `parent`, `prio`, `type`, `relid`, `status`) VALUES ('$postName', '$postParent', '$postPrio', '$type', '$relid', '$postStatus')");
            if ($db->errno) {
                throw new Exception($l['ERR10']);
            }

            alog("general", "menu_entry_created", $db->insert_id);

            $error = "<div class=\"alert alert-success\">{$l['ERR11']} <a href=\"?p=cms_menu\">{$l['OVERVIEW']}</a></div>";
            unset($_POST);
        } catch (Exception $ex) {
            $error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
        }

    }

    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE2'];?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
        echo $error;
    }
    ?>
	<form role="form" method="POST">

  <label><?=$l['NAME'];?></label>
    <?php foreach ($languages as $lang_key => $lang_name) {?>
    <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
    <?php }?><br />

<?php foreach ($languages as $lang_key => $lang_name) {
        $name = "";
        ?>
    <div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>>
        <input type="text" name="name_<?=$lang_key;?>" class="form-control" placeholder="<?=$l['NAME'];?>" value="<?=isset($_POST['name_' . $lang_key]) ? $_POST['name_' . $lang_key] : $name;?>">
    </div>
<?php }?>

  <div class="form-group" style="margin-top: 10px;">
    <label><?=$l['TYPE'];?></label>
		<select name="content" class="form-control">
			<option value="page"><?=$l['TPAGE'];?></option>
			<option value="link" <?php if (isset($_POST['content']) && $_POST['content'] == "link") {
        echo "selected";
    }
    ?>><?=$l['TLINK'];?></option>
			<option value="menu" <?php if (isset($_POST['content']) && $_POST['content'] == "menu") {
        echo "selected";
    }
    ?>><?=$l['TMENU'];?></option>
			<option value="divider" <?php if (isset($_POST['content']) && $_POST['content'] == "divider") {
        echo "selected";
    }
    ?>><?=$l['TDIVIDER'];?></option>
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

	<div class="form-group" style="display: none;" id="page">
    <label><?=$l['PAGE'];?></label>
		<input class="form-control" name="page" value="<?=isset($_POST['page']) ? htmlentities($_POST['page']) : "/";?>" placeholder="/">
		<p class="help-block"><?=$l['PAGEH'];?></p>
	</div>

	<div style="display: none;" id="link">
		<div class="form-group">
			<label><?=$l['URL'];?></label>
			<input class="form-control" name="link" value="<?=isset($_POST['link']) ? htmlentities($_POST['link']) : "";?>" placeholder="https://sourceway.de/">
		</div>

		<div class="form-group">
			<label><?=$l['LINKTYPE'];?></label>
			<select name="target" class="form-control">
				<option value=""><?=$l['LINKSAME'];?></option>
				<option value="_blank"<?php if (isset($_POST['target']) && $_POST['target'] == "_blank") {
        echo " selected";
    }
    ?>><?=$l['LINKNEW'];?></option>
			</select>
		</div>
	</div>

	<div class="form-group" id="parent">
    <label><?=$l['PARENT'];?></label>
	<select name="parent" class="form-control">
		<option value="0"><?=$l['NOPARENT'];?></option>
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
  	<p class="help-block"><?=$l['PARENTH'];?></p>
  </div>

	<div class="form-group">
    <label><?=$l['PRIO'];?></label>
    <input type="text" name="prio" value="<?=isset($_POST['prio']) ? $_POST['prio'] : "1";?>" style="max-width: 80px" class="form-control">
	<p class="help-block"><?=$l['PRIOH'];?></p>
  </div>

  <div class="form-group">
<label><?=$l['STATUS'];?></label><br />
<label class="radio-inline">
<input type="radio" name="status" value="1" <?=isset($_POST['status']) ? ($_POST['status'] == "1" ? "checked" : "") : "";?>>
<?=$l['ACTIVE'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="2" <?=isset($_POST['status']) ? ($_POST['status'] == "2" ? "checked" : "") : "";?>>
<?=$l['LOGGED_IN'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="3" <?=isset($_POST['status']) ? ($_POST['status'] == "3" ? "checked" : "") : "";?>>
<?=$l['LOGGED_OUT'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="0" <?=isset($_POST['status']) ? ($_POST['status'] == "0" ? "checked" : "") : "";?>>
<?=$l['INACTIVE'];?>
</label>
</div>

  <center><button type="submit" class="btn btn-primary btn-block" name="add"><?=$l['ADD'];?></button></center></form>


<?php }?>