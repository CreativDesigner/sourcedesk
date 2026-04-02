<?php
$l = $lang['ADD_PAGE'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_cms_page");} else {

    if (isset($_POST['add'])) {

        try {
            foreach ($_POST as $k => $v) {
                $vari = "post" . ucfirst(strtolower($k));
                $$vari = $db->real_escape_string($v);
            }

            $titles = array();
            $contents = array();

            foreach ($languages as $lang_key => $lang_name) {
                if (empty($_POST['title_' . $lang_key])) {
                    throw new Exception($l['ERR1']);
                }

                $titles[$lang_key] = $_POST['title_' . $lang_key];

                if (empty($_POST['content_' . $lang_key])) {
                    throw new Exception($l['ERR2']);
                }

                $contents[$lang_key] = base64_encode($_POST['content_' . $lang_key]);
            }

            $postName = $db->real_escape_string(serialize($titles));
            $content = $db->real_escape_string(serialize($contents));

            $slug = strtolower($postSlug);
            if (!isset($postSlug) || !ctype_alnum(str_replace("-", "", $postSlug)) || $db->query("SELECT ID FROM cms_pages WHERE slug = '$postSlug' LIMIT 1")->num_rows == 1) {
                throw new Exception($l['ERR3']);
            }

            if (!isset($postStatus) || ($postStatus != 0 && $postStatus != 1 && $postStatus != 2 && $postStatus != 3)) {
                throw new Exception($l['ERR4']);
            }

            $seo = $db->real_escape_string(serialize(is_array($_POST['seo']) ? $_POST['seo'] : []));

            $ma = max(0, intval($_POST['min_age'] ?? 0));

            $db->query("INSERT INTO cms_pages (`title`, `slug`, `content`, `active`, `container`, `seo`, `min_age`) VALUES ('$postName', '$postSlug', '$content', '$postStatus', " . (isset($_POST['container']) && $_POST['container'] == 0 ? 0 : 1) . ", '$seo', $ma)");
            if ($db->errno) {
                throw new Exception($l['ERR5']);
            }

            alog("general", "page_created", $db->insert_id);

            $error = "<div class=\"alert alert-success\">{$l['SUC']} <a href=\"?p=cms_pages\">{$l['OVERVIEW']}</a></div>";
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

  <label><?=$l['TITLECONTENT'];?></label><?php foreach ($languages as $lang_key => $lang_name) {?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
                <?php }?><br />
<?php foreach ($languages as $lang_key => $lang_name) {
        $name = $desc = "";
        ?>
    <div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>>
          	<input type="text" name="title_<?=$lang_key;?>" class="form-control" placeholder="<?=$l['TITLE3'];?>" value="<?=isset($_POST['title_' . $lang_key]) ? $_POST['title_' . $lang_key] : $name;?>">
            <textarea name="content_<?=$lang_key;?>" class="form-control" placeholder="<?=$l['CONTENT'];?>" style="resize:none; height:300px; margin-top: 10px; margin-bottom: 15px;"><?=isset($_POST['content_' . $lang_key]) ? $_POST['content_' . $lang_key] : nl2br($desc);?></textarea>
            <input type="text" name="seo[<?=$isoCodes[$lang_key];?>][desc]" class="form-control" placeholder="<?=$l['DESCRIPTION'];?>" style="margin-top: -5px; margin-bottom: 15px;" value="<?=isset($_POST['seo'][$isoCodes[$lang_key]]['desc']) ? $_POST['seo'][$isoCodes[$lang_key]]['desc'] : "";?>">
            <input type="text" name="seo[<?=$isoCodes[$lang_key];?>][keywords]" class="form-control" placeholder="<?=$l['KEYWORDS'];?>" style="margin-top: -5px; margin-bottom: 15px;" value="<?=isset($_POST['seo'][$isoCodes[$lang_key]]['keywords']) ? $_POST['seo'][$isoCodes[$lang_key]]['keywords'] : "";?>">
          </div>
<?php }?>

  <div class="form-group">
    <label><?=$l['SLUG'];?></label>
	<input type="text" name="slug" value="<?=isset($_POST['slug']) ? $_POST['slug'] : "";?>" placeholder="" class="form-control">
  	<p class="help-block"><?=$l['SLUGH'];?></p>
  </div>

  <div class="form-group">
    <label><?=$lang['SETTINGS']['MIN_AGE'];?></label>
	<input type="text" name="min_age" value="<?=isset($_POST['min_age']) ? max(0, intval($_POST['min_age'])) : "";?>" placeholder="<?=$lang['SETTINGS']['MIN_AGEP'];?>" class="form-control">
  </div>

  <div class="form-group">
<label><?=$lang['ADD_MENU']['STATUS'];?></label><br />
<label class="radio-inline">
<input type="radio" name="status" value="1" <?=isset($_POST['status']) ? ($_POST['status'] == "1" ? "checked" : "") : "";?>>
<?=$lang['ADD_MENU']['ACTIVE'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="2" <?=isset($_POST['status']) ? ($_POST['status'] == "2" ? "checked" : "") : "";?>>
<?=$lang['ADD_MENU']['LOGGED_IN'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="3" <?=isset($_POST['status']) ? ($_POST['status'] == "3" ? "checked" : "") : "";?>>
<?=$lang['ADD_MENU']['LOGGED_OUT'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="0" <?=isset($_POST['status']) ? ($_POST['status'] == "0" ? "checked" : "") : "";?>>
<?=$lang['ADD_MENU']['INACTIVE'];?>
</label>
</div>
        <div class="form-group">
            <label><?=$l['CONTAINER'];?></label><br />
            <label class="radio-inline">
                <input type="radio" name="container" value="1" <?=isset($_POST['container']) ? ($_POST['container'] == "1" ? "checked" : "") : "checked";?>>
                <?=$l['DISPLAY'];?>
            </label>
            <label class="radio-inline">
                <input type="radio" name="container" value="0" <?=isset($_POST['container']) ? ($_POST['container'] == "0" ? "checked" : "") : "";?>>
                <?=$l['DND'];?>
            </label>
        </div>

  <center><button type="submit" class="btn btn-primary btn-block" name="add"><?=$l['ADD'];?></button></center></form>


<?php }?>