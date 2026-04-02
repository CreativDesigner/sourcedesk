<?php
$l = $lang['ADD_BLOG'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_cms_blog");} else {

    if (isset($_POST['add'])) {

        try {
            foreach ($_POST as $k => $v) {
                if (!in_array($k, ["time", "admin"])) {
                    continue;
                }

                $vari = "post" . ucfirst(strtolower($k));
                $$vari = $db->real_escape_string($v);
            }

            $postTitle = [];
            $postText = [];

            foreach ($languages as $lang_key => $lang_name) {
                if (empty($_POST['title'][$lang_key])) {
                    throw new Exception($l['ERR1']);
                }

                if (empty($_POST['text'][$lang_key])) {
                    throw new Exception($l['ERR4']);
                }

                $postTitle[$lang_key] = $_POST['title'][$lang_key];
                $postText[$lang_key] = $_POST['text'][$lang_key];
            }

            $postTitle = $db->real_escape_string(serialize($postTitle));
            $postText = $db->real_escape_string(serialize($postText));

            if (empty($postTime) || !strtotime($postTime)) {
                throw new Exception($l['ERR2']);
            }

            if (!isset($postAdmin) || ($postAdmin != 0 && $db->query("SELECT 1 FROM admins WHERE ID = " . intval($postAdmin))->num_rows != 1)) {
                throw new Exception($l['ERR3']);
            }

            $db->query("INSERT INTO cms_blog (`title`, `text`, `time`, `admin`) VALUES ('$postTitle', '$postText', " . strtotime($postTime) . ", " . intval($postAdmin) . ")");
            if ($db->errno) {
                throw new Exception($l['ERR5']);
            }

            $error = "<div class=\"alert alert-success\">{$l['SUC']} <a href=\"?p=cms_blog\">{$l['OVERVIEW']}</a></div>";
            alog("general", "blog_entry_created", $postTitle);
            unset($_POST);
        } catch (Exception $ex) {
            $error = "<div class=\"alert alert-danger\"><b>Fehler!</b> " . $ex->getMessage() . "</div>";
        }

    }

    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
        echo $error;
    }
    ?>
	<form role="form" method="POST">

  <div class="form-group">
		<label><?=$l['FTITLE'];?></label><?php foreach ($languages as $lang_key => $lang_name) {?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
                <?php }?><br />
		<?php foreach ($languages as $lang_key => $lang_name) {?>
		<div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>>
			<div class="input-group">
				<span class="input-group-addon"><i class="fa fa-newspaper-o"></i></span>
				<input type="text" class="form-control" name="title[<?=$lang_key;?>]" value="<?=isset($_POST['title']) ? htmlentities($_POST['title']) : "";?>" placeholder="<?=$l['TITLEP'];?>">
			</div>
			<textarea class="form-control summernote" style="resize: none; height: 250px;" name="text[<?=$lang_key;?>]" placeholder="Lorem ipsum"><?=isset($_POST['text']) ? htmlentities($_POST['text']) : "";?></textarea>
		</div>
		<?php }?>
	</div>

	<div class="form-group">
		<label><?=$l['DATE'];?></label>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
			<input type="text" class="form-control" name="time" value="<?=isset($_POST['time']) ? htmlentities($_POST['time']) : $dfo->format(time(), false);?>" placeholder="<?=$dfo->format(time(), false);?>">
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['STAFF'];?></label>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-user-md"></i></span>
			<select name="admin" class="form-control">
				<?php $v = isset($_POST['admin']) ? intval($_POST['admin']) : $adminInfo->ID;?>
				<option value="0"><?=$l['NOSTAFF'];?></option>
				<?php $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");while ($r = $sql->fetch_object()) {?>
				<option value="<?=$r->ID;?>"<?=$v == $r->ID ? ' selected="selected"' : '';?>><?=$r->name;?></option>
				<?php }?>
			</select>
		</div>
	</div>

  <center><button type="submit" class="btn btn-primary btn-block" name="add"><?=$l['ADD'];?></button></center></form>


<?php }?>