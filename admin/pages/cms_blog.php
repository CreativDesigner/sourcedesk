<?php
$l = $lang['BLOG'];
$al = $lang['ADD_BLOG'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "cms_blog");} else {

    function _l($s)
    {
        global $CFG;
        return unserialize($s)[$CFG['LANG']];
    }

    if (isset($_GET['id']) && $db->query("SELECT ID FROM cms_blog WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->num_rows == 1) {

        $i = $db->query("SELECT * FROM cms_blog WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();

        if (isset($_POST['edit'])) {

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
                        throw new Exception($al['ERR1']);
                    }

                    if (empty($_POST['text'][$lang_key])) {
                        throw new Exception($al['ERR4']);
                    }

                    $postTitle[$lang_key] = $_POST['title'][$lang_key];
                    $postText[$lang_key] = $_POST['text'][$lang_key];
                }

                $postTitle = $db->real_escape_string(serialize($postTitle));
                $postText = $db->real_escape_string(serialize($postText));

                if (empty($postTime) || !strtotime($postTime)) {
                    throw new Exception($al['ERR2']);
                }

                if (!isset($postAdmin) || ($postAdmin != 0 && $db->query("SELECT 1 FROM admins WHERE ID = " . intval($postAdmin))->num_rows != 1)) {
                    throw new Exception($al['ERR3']);
                }

                $db->query("UPDATE cms_blog SET `title` = '$postTitle', `text` = '$postText', `time` = " . strtotime($postTime) . ", `admin` = " . intval($postAdmin) . " WHERE ID = " . $i->ID . " LIMIT 1");
                if ($db->errno) {
                    throw new Exception($al['ERR5']);
                }

                alog("blog", "post_changed", $postTitle);

                $error = "<div class=\"alert alert-success\">{$l['SUC']}</div>";
                unset($_POST);

                $i = $db->query("SELECT * FROM cms_blog WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();
            } catch (Exception $ex) {
                $error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
            }

        }
        ?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=@unserialize($i->title) ? unserialize($i->title)[$CFG['LANG']] : $i->title;?> <small><?=$l['SUBTITLE'];?></small></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
            echo $error;
        }
        ?>
	<form role="form" method="POST">

	<?php
$text = [];
        $title = [];

        if (@unserialize($i->title)) {
            $title = unserialize($i->title);

            foreach ($languages as $lang_key => $lang_name) {
                if (!array_key_exists($lang_key, $title)) {
                    if (count($title)) {
                        $title[$lang_key] = array_values($title)[0];
                    } else {
                        $title[$lang_key] = "";
                    }
                }
            }
        } else {
            foreach ($languages as $lang_key => $lang_name) {
                $title[$lang_key] = $i->title;
            }
        }

        if (@unserialize($i->text)) {
            $text = unserialize($i->text);

            foreach ($languages as $lang_key => $lang_name) {
                if (!array_key_exists($lang_key, $text)) {
                    if (count($text)) {
                        $text[$lang_key] = array_values($text)[0];
                    } else {
                        $text[$lang_key] = "";
                    }
                }
            }
        } else {
            foreach ($languages as $lang_key => $lang_name) {
                $text[$lang_key] = $i->text;
            }
        }
        ?>

	<div class="form-group">
		<label><?=$al['FTITLE'];?></label><?php foreach ($languages as $lang_key => $lang_name) {?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
                <?php }?><br />
		<?php foreach ($languages as $lang_key => $lang_name) {?>
			<div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-newspaper-o"></i></span>
			<input type="text" class="form-control" name="title[<?=$lang_key;?>]" value="<?=isset($_POST['title']) ? htmlentities($_POST['title']) : htmlentities($title[$lang_key]);?>" placeholder="<?=$al['TITLEP'];?>">
		</div>
		<textarea class="form-control summernote" style="resize: none; height: 250px;" name="text[<?=$lang_key;?>]" placeholder="Lorem ipsum"><?=isset($_POST['text']) ? $_POST['text'] : nl2br($text[$lang_key]);?></textarea>
		</div>
		<?php }?>
	</div>

	<div class="form-group">
		<label><?=$al['DATE'];?></label>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
			<input type="text" class="form-control" name="time" value="<?=isset($_POST['time']) ? htmlentities($_POST['time']) : $dfo->format($i->time, false);?>" placeholder="<?=$dfo->format(time(), false);?>">
		</div>
	</div>

	<div class="form-group">
		<label><?=$al['STAFF'];?></label>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-user-md"></i></span>
			<select name="admin" class="form-control">
				<?php $v = isset($_POST['admin']) ? intval($_POST['admin']) : $i->admin;?>
				<option value="0"><?=$al['NOSTAFF'];?></option>
				<?php $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");while ($r = $sql->fetch_object()) {?>
				<option value="<?=$r->ID;?>"<?=$v == $r->ID ? ' selected="selected"' : '';?>><?=$r->name;?></option>
				<?php }?>
			</select>
		</div>
	</div>

  	<center><button type="submit" class="btn btn-primary btn-block" name="edit"><?=$l['SAVE'];?></button></center>

  	</form>

<?php
} else {
        ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['PT'];?> <small><a href="#" data-toggle="modal" data-target="#disqus"><?=$l['DISQUS'];?></a></small><a href="?p=add_cms_blog" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

            <?php
if (isset($_POST['disqus_submit'])) {
            if (!isset($_POST['activate']) || $_POST['activate'] != "yes") {
                $CFG['DISQUS'] = "";
                $_POST['disqus_name'] = "";
                $db->query("UPDATE settings SET `value` = '' WHERE `key` = 'disqus'");
                echo '<div class="alert alert-success">' . $l['DD'] . '</div>';
                alog("blog", "disqus_deactivated");
            } else {
                $_POST['disqus_name'] = strtolower($_POST['disqus_name']);
                if (!empty($_POST['disqus_name']) && ctype_alnum($_POST['disqus_name'])) {
                    $CFG['DISQUS'] = $_POST['disqus_name'];
                    $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['disqus_name']) . "' WHERE `key` = 'disqus'");
                    echo '<div class="alert alert-success">' . $l['DS'] . '</div>';
                    alog("blog", "disqus_activated");
                } else {
                    echo '<div class="alert alert-danger">' . $l['DE'] . '</div>';
                    alog("blog", "disqus_failed");
                }
            }
        }
        ?>

            <div class="modal fade" id="disqus" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['ERROR'];?>"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title"><?=$l['DISQUS'];?></h4>
			      </div>
			      <form method="POST">
			      <div class="modal-body">
			        <p style="text-align: justify;"><?=$l['DISQUSI'];?></p>

			        <div class="checkbox">
			        	<label>
			        		<input type="checkbox" name="activate" value="yes" onchange="if(this.checked){ document.getElementById('disqus_name').disabled = false; } else { document.getElementById('disqus_name').disabled = true; }"<?=!empty($CFG['DISQUS']) ? ' checked="checked"' : '';?>> <?=$l['DISQUSC'];?>
			        	</label>
			        </div>

			        <div class="input-group">
			        	<input type="text" name="disqus_name" value="<?=isset($_POST['disqus_name']) ? $_POST['disqus_name'] : $CFG['DISQUS'];?>" class="form-control" id="disqus_name"<?=empty($CFG['DISQUS']) ? ' disabled="disabled"' : '';?> />
			        	<span class="input-group-addon">.disqus.com</span>
			        </div>
			        <input type="hidden" name="disqus_submit" value="yes" />
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['ERROR'];?></button>
			        <button type="submit" class="btn btn-primary"><?=$l['SAVES'];?></button>
			      </div>
			      </form>
			    </div>
			  </div>
			</div>

			<div class="row">
				<div class="col-lg-12">
				<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM cms_blog WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['DELETED'] . '</div>';
                alog("blog", "deleted", $_GET['delete']);
            }
        }

        if (isset($_POST['delete_selected']) && is_array($_POST['page'])) {
            $d = 0;
            foreach ($_POST['page'] as $id) {
                $db->query("DELETE FROM cms_blog WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("blog", "deleted", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['D1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['DX']) . ' </div>';
            }

        }

        $admins = [];
        $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            $admins[$row->ID] = $row->name;
        }

        $t = new Table("SELECT * FROM cms_blog", [
            "title" => [
                "name" => $al['FTITLEONLY'],
                "type" => "like",
            ],
            "admin" => [
                "name" => $al['STAFF'],
                "type" => "select",
                "options" => $admins,
            ],
        ], ["time", "DESC"], "cms_blog");

        echo $t->getHeader();
        ?>

					<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$t->orderHeader("time", $al['DATE']);?></th>
		<th><?=$al['FTITLEONLY'];?></th>
		<th><?=$t->orderHeader("admin", $al['STAFF']);?></th>
		<th width="56px"></th>
	</tr>
	<form method="POST">
	<?php

        $sql = $t->qry("time DESC, ID DESC");

        if ($sql->num_rows == 0) {
            echo "<tr><td colspan=\"5\"><center>{$l['NT']}</center></td></tr>";
        } else {
            while ($d = $sql->fetch_object()) {
                ?>
				<tr>
				<td><input type="checkbox" class="checkbox" name="page[]" value="<?=$d->ID;?>" onchange="javascript:toggle();" /></td>
				<td><?=$dfo->format($d->time, false);?></td>
				<td><a href="<?=$CFG['PAGEURL'];?>blog/<?=$d->ID;?>" target="_blank"><?=@unserialize($d->title) ? unserialize($d->title)[$CFG['LANG']] : $d->title;?></a></td>
				<td><a href="?p=admin&id=<?=$d->admin;?>"><?=$db->query("SELECT name FROM admins WHERE ID = " . $d->admin)->num_rows == 1 ? $db->query("SELECT name FROM admins WHERE ID = " . $d->admin)->fetch_object()->name : "<i>{$l['UK']}</i>";?></a></td>
				<td width="56px">
			<a href="?p=cms_blog&id=<?=$d->ID;?>" title="<?=$l['DOEDIT'];?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;
			<a href="?p=cms_blog&delete=<?=$d->ID;?>" title="<?=$l['DODELETE'];?>" onclick="return confirm('<?=$l['RDD'];?>');"><i class="fa fa-times fa-lg"></i></a>
		</td></tr>
			<?php
}
        }
        ?>
</table></div><?=$l['SELECTED'];?>: <input type="submit" name="delete_selected" value="<?=$l['DODELETE'];?>" class="btn btn-danger"><br /><br /></form>
				</div>
            </div>
            <!-- /.row -->

<?php echo $t->getFooter();}} ?>