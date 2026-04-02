<?php
$l = $lang['CMS_PAGES'];
$al = $lang['ADD_PAGE'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "cms_pages");} else {

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

    if (isset($_GET['id']) && $db->query("SELECT ID FROM cms_pages WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->num_rows == 1) {

        $i = $db->query("SELECT * FROM cms_pages WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();

        if (isset($_POST['edit'])) {

            try {
                foreach ($_POST as $k => $v) {
                    $vari = "p_" . strtolower($k);
                    $$vari = $db->real_escape_string($v);
                }

                $titles = array();
                $contents = array();

                foreach ($languages as $lang_key => $lang_name) {
                    if (empty($_POST['title_' . $lang_key])) {
                        throw new Exception($al['ERR1']);
                    }

                    $titles[$lang_key] = $_POST['title_' . $lang_key];

                    if (empty($_POST['content_' . $lang_key])) {
                        throw new Exception($al['ERR2']);
                    }

                    $contents[$lang_key] = base64_encode($_POST['content_' . $lang_key]);
                }

                $p_name = $db->real_escape_string(serialize($titles));
                $content = $db->real_escape_string(serialize($contents));

                $p_slug = strtolower($p_slug);
                if (!isset($p_slug) || !ctype_alnum(str_replace("-", "", $p_slug)) || $db->query("SELECT ID FROM cms_pages WHERE slug = '$p_slug' AND ID != " . $i->ID . " LIMIT 1")->num_rows == 1) {
                    throw new Exception($al['ERR3']);
                }

                if (!isset($p_status) || ($p_status != 0 && $p_status != 1 && $p_status != 2 && $p_status != 3)) {
                    throw new Exception($al['ERR4']);
                }

                $seo = $db->real_escape_string(serialize(is_array($_POST['seo']) ? $_POST['seo'] : []));

                $ma = max(0, intval($_POST['min_age'] ?? 0));

                $db->query("UPDATE cms_pages SET `title` = '$p_name', `slug` = '$p_slug', `content` = '$content', `seo` = '$seo', `active` = '$p_status', `container` = " . (isset($_POST['container']) && $_POST['container'] == 0 ? 0 : 1) . ", `min_age` = $ma WHERE ID = " . $i->ID . " LIMIT 1");
                if ($db->errno) {
                    throw new Exception($al['ERR5']);
                }

                $error = "<div class=\"alert alert-success\">{$l['SUC']}</div>";
                unset($_POST);

                alog("cms", "page_changed", $i->ID);

                $i = $db->query("SELECT * FROM cms_pages WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();
            } catch (Exception $ex) {
                $error = "<div class=\"alert alert-danger\"><b>Fehler!</b> " . $ex->getMessage() . "</div>";
            }

        }
        ?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=_l($i->title);?> <small><?=$l['SUBTITLE'];?></small></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
            echo $error;
        }
        ?>
	<form role="form" method="POST">

<label><?=$al['TITLECONTENT'];?></label><?php foreach ($languages as $lang_key => $lang_name) {?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
                <?php }?><br />
<?php
$seo = unserialize($i->seo);
        foreach ($languages as $lang_key => $lang_name) {
            $mySeo = array_key_exists($isoCodes[$lang_key], $seo) ? $seo[$isoCodes[$lang_key]] : [];
            $name = _l($i->title, $lang_key);
            $desc = base64_decode(_l($i->content, $lang_key));
            $seodesc = array_key_exists("desc", $mySeo) ? $mySeo["desc"] : "";
            $keywords = array_key_exists("keywords", $mySeo) ? $mySeo["keywords"] : "";
            ?>
      <div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>>
          	<input type="text" name="title_<?=$lang_key;?>" class="form-control" placeholder="<?=$al['TITLE2'];?>" value="<?=isset($_POST['title_' . $lang_key]) ? $_POST['title_' . $lang_key] : $name;?>">
            <textarea name="content_<?=$lang_key;?>" class="form-control" placeholder="<?=$al['CONTENT'];?>" style="resize:none; height:300px; margin-top: 10px; margin-bottom: 15px;"><?=isset($_POST['content_' . $lang_key]) ? $_POST['content_' . $lang_key] : ($desc);?></textarea>
						<input type="text" name="seo[<?=$isoCodes[$lang_key];?>][desc]" class="form-control" placeholder="<?=$al['DESCRIPTION'];?>" style="margin-top: -5px; margin-bottom: 15px;" value="<?=isset($_POST['seo'][$isoCodes[$lang_key]]['desc']) ? $_POST['seo'][$isoCodes[$lang_key]]['desc'] : $seodesc;?>">
            <input type="text" name="seo[<?=$isoCodes[$lang_key];?>][keywords]" class="form-control" placeholder="<?=$al['KEYWORDS'];?>" style="margin-top: -5px; margin-bottom: 15px;" value="<?=isset($_POST['seo'][$isoCodes[$lang_key]]['keywords']) ? $_POST['seo'][$isoCodes[$lang_key]]['keywords'] : $keywords;?>">
          </div>
<?php }?>

  <div class="form-group">
    <label><?=$al['SLUG'];?></label>
	<input type="text" name="slug" value="<?=isset($_POST['slug']) ? $_POST['slug'] : $i->slug;?>" placeholder="" class="form-control">
  	<p class="help-block"><?=$al['SLUGH'];?></p>
  </div>

  <div class="form-group">
    <label><?=$lang['SETTINGS']['MIN_AGE'];?></label>
	<input type="text" name="min_age" value="<?=(isset($_POST['min_age']) ? max(0, intval($_POST['min_age'])) : max(0, intval($i->min_age))) ?: "";?>" placeholder="<?=$lang['SETTINGS']['MIN_AGEP'];?>" class="form-control">
  </div>

  <div class="form-group">
<label><?=$l['STATUS'];?></label><br />
<label class="radio-inline">
<input type="radio" name="status" value="1" <?=isset($_POST['status']) ? ($_POST['status'] == "1" ? "checked" : "") : ($i->active == "1" ? "checked" : "");?>>
<?=$l['S1'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="2" <?=isset($_POST['status']) ? ($_POST['status'] == "2" ? "checked" : "") : ($i->active == "2" ? "checked" : "");?>>
<?=$l['S2'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="3" <?=isset($_POST['status']) ? ($_POST['status'] == "3" ? "checked" : "") : ($i->active == "3" ? "checked" : "");?>>
<?=$l['S4'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="0" <?=isset($_POST['status']) ? ($_POST['status'] == "0" ? "checked" : "") : ($i->active == "0" ? "checked" : "");?>>
<?=$l['S3'];?>
</label>
</div>

        <div class="form-group">
            <label><?=$al['CONTAINER'];?></label><br />
            <label class="radio-inline">
                <input type="radio" name="container" value="1" <?=isset($_POST['container']) ? ($_POST['container'] == "1" ? "checked" : "") : ($i->container == "1" ? "checked" : "");?>>
                <?=$al['DISPLAY'];?>
            </label>
            <label class="radio-inline">
                <input type="radio" name="container" value="0" <?=isset($_POST['container']) ? ($_POST['container'] == "0" ? "checked" : "") : ($i->container == "0" ? "checked" : "");?>>
                <?=$al['DND'];?>
            </label>
        </div>

  <center><button type="submit" class="btn btn-primary btn-block" name="edit"><?=$l['EDIT'];?></button></center></form>

<?php
} else {
        ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE2'];?> <a href="?p=add_cms_page" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="row">
				<div class="col-lg-12">
				<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM cms_pages WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['DELETED'] . '</div>';
                alog("cms", "page_deleted", $_GET['delete']);
            }
        }

        if (isset($_POST['delete_selected']) && is_array($_POST['page'])) {
            $d = 0;
            foreach ($_POST['page'] as $id) {
                $db->query("DELETE FROM cms_pages WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("cms", "page_deleted", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['D1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['DX']) . '</div>';
            }

        }

        if (isset($_POST['activate_selected']) && is_array($_POST['page'])) {
            $d = 0;
            foreach ($_POST['page'] as $id) {
                $db->query("UPDATE cms_pages SET active = 1 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("cms", "page_activated", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['A1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['AX']) . '</div>';
            }

        }

        if (isset($_POST['logged_selected']) && is_array($_POST['page'])) {
            $d = 0;
            foreach ($_POST['page'] as $id) {
                $db->query("UPDATE cms_pages SET active = 2 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("cms", "page_logged", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['L1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['LX']) . '</div>';
            }

        }

        if (isset($_POST['deactivate_selected']) && is_array($_POST['page'])) {
            $d = 0;
            foreach ($_POST['page'] as $id) {
                $db->query("UPDATE cms_pages SET active = 0 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("cms", "page_deactivated", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['E1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['EX']) . '</div>';
            }

        }

        $t = new Table("SELECT * FROM cms_pages", [
            "slug" => [
                "name" => $al['SLUG'],
                "type" => "like",
            ],
            "active" => [
                "name" => $l['STATUS'],
                "type" => "select",
                "options" => [
                    "1" => $l['S1'],
                    "2" => $l['S2'],
                    "0" => $l['S3'],
                ],
            ],
        ], ["slug", "ASC"], "cms_pages");

        echo $t->getHeader();
        ?>


					<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$al['TITLE3'];?></th>
		<th><?=$t->orderHeader("slug", $al['SLUG']);?></th>
		<th width="65px"></th>
	</tr>
	<form method="POST">
	<?php

        $sql = $t->qry("slug ASC");

        if ($sql->num_rows == 0) {
            echo "<tr><td colspan=\"4\"><center>{$l['NT']}</center></td></tr>";
        } else {
            while ($d = $sql->fetch_object()) {
                ?>
				<tr>
				<td><input type="checkbox" class="checkbox" name="page[]" value="<?=$d->ID;?>" onchange="javascript:toggle();" /></td>
				<td><?=_l($d->title);?> <?php if ($d->active != 1 && $d->active != 2) {?><font color="red">(<?=$l['INAC'];?>)</font><?php } else if ($d->active == 2) {?><font color="blue">(<?=$l['OLI'];?>)</font><?php }?></td>
				<td><?=$d->slug;?></td>
				<td width="65px">
			<a href="?p=cms_pages&id=<?=$d->ID;?>" title="<?=$l['DE'];?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;
			<a href="?p=cms_pages&delete=<?=$d->ID;?>" title="<?=$l['DD'];?>" onclick="return confirm('<?=$l['RDD'];?>');"><i class="fa fa-times fa-lg"></i></a>
		</td></tr>
			<?php
}
        }
        ?>
</table></div><?=$l['SELECTED'];?>: <input type="submit" name="deactivate_selected" value="<?=$l['SEL1'];?>" class="btn btn-warning"> <input type="submit" name="activate_selected" value="<?=$l['SEL2'];?>" class="btn btn-success"> <input type="submit" name="logged_selected" value="<?=$l['SEL3'];?>" class="btn btn-default"> <input type="submit" name="delete_selected" value="<?=$l['DD'];?>" class="btn btn-danger"><br /></form>
				</div>
            </div>
            <!-- /.row -->

<?php echo $t->getFooter();}} ?>