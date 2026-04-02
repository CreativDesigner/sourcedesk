<?php
$l = $lang['CMS_FAQ'];
$al = $lang['ADD_FAQ'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "cms_faq");} else {

    function _l($s)
    {
        global $CFG;
        return unserialize($s)[$CFG['LANG']];
    }

    if (isset($_GET['id']) && $db->query("SELECT ID FROM cms_faq WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->num_rows == 1) {

        $i = $db->query("SELECT * FROM cms_faq WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();

        if (isset($_POST['edit'])) {

            try {
                foreach ($_POST as $k => $v) {
                    $vari = "post" . ucfirst(strtolower($k));
                    $$vari = $db->real_escape_string($v);
                }

                $q = array();
                $a = array();

                foreach ($languages as $lang_key => $lang_name) {
                    if (empty($_POST['question_' . $lang_key])) {
                        throw new Exception($l['ERR1']);
                    }

                    $q[$lang_key] = $_POST['question_' . $lang_key];

                    if (empty($_POST['answer_' . $lang_key])) {
                        throw new Exception($l['ERR2']);
                    }

                    $a[$lang_key] = base64_encode($_POST['answer_' . $lang_key]);
                }

                $q = $db->real_escape_string(serialize($q));
                $a = $db->real_escape_string(serialize($a));

                $db->query("UPDATE cms_faq SET `question` = '$q', `answer` = '$a' WHERE ID = " . $i->ID . " LIMIT 1");
                if ($db->errno) {
                    throw new Exception($l['ERR3']);
                }

                alog("faq", "edited", $i->ID);

                $error = "<div class=\"alert alert-success\">{$l['SUC']}</div>";
                unset($_POST);

                $i = $db->query("SELECT * FROM cms_faq WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();
            } catch (Exception $ex) {
                $error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
            }

        }
        ?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=_l($i->question);?> <small><?=$l['SUBTITLE'];?></small></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
            echo $error;
        }
        ?>
	<form role="form" method="POST">

<?php $s = 0;foreach ($languages as $k => $v) {if ($s != 0) {
            echo "<hr />";
        }

            ?>
   <h3 style="margin-top:0"><?=$v;?></h3>
  <div class="form-group">
    <label><?=$al['QUESTION'];?></label>
	<input type="text" name="question_<?=$k;?>" id="question_<?=$k;?>" value="<?=isset($_POST['question_' . $k]) ? $_POST['question_' . $k] : unserialize($i->question)[$k];?>" placeholder="<?=$al['QUESTIONP'];?>" class="form-control">
  </div>
   <div class="form-group">
    <label><?=$al['ANSWER'];?></label>
	<textarea style="width:100%; height:100px; resize:none;" name="answer_<?=$k;?>" id="answer_<?=$k;?>" class="form-control summernote" placeholder="<?=$al['ANSWERP'];?>"><?=isset($_POST['answer_' . $k]) ? $_POST['answer_' . $k] : nl2br(base64_decode(unserialize($i->answer)[$k]));?></textarea>
  </div>
<?php $s++;}?>

  <center><button type="submit" class="btn btn-primary btn-block" name="edit"><?=$l['SAVE'];?></button></center></form>

<?php
} else {
        ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE2'];?> <a href="?p=add_cms_faq" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="row">
				<div class="col-lg-12">
				<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM cms_faq WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['DELETED'] . '</div>';
                alog("faq", "deleted", $_GET['delete']);
            }
        }

        if (isset($_POST['delete_selected']) && is_array($_POST['page'])) {
            $d = 0;
            foreach ($_POST['page'] as $id) {
                $db->query("DELETE FROM cms_faq WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("faq", "deleted", $id);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['D1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['DX']) . '</div>';
            }

        }

        $t = new Table("SELECT * FROM cms_faq", []);

        echo $t->getHeader();
        ?>

					<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
		<th><?=$al['QUESTION'];?></th>
		<th width="56px"></th>
	</tr>
	<form method="POST">
	<?php

        $sql = $db->query("SELECT * FROM cms_faq");
        $faq = $q = array();
        while ($row = $sql->fetch_object()) {
            array_push($faq, $row);
            array_push($q, unserialize($row->question)[$CFG['LANG']]);
        }
        array_multisort($q, $faq);

        if ($sql->num_rows == 0) {
            echo "<tr><td colspan=\"4\"><center>{$l['NT']}</center></td></tr>";
        } else {
            foreach ($faq as $d) {
                ?>
				<tr>
				<td><input type="checkbox" class="checkbox" name="page[]" value="<?=$d->ID;?>" onchange="javascript:toggle();" /></td>
				<td><?=_l($d->question);?></td>
				<td width="56px">
			<a href="?p=cms_faq&id=<?=$d->ID;?>k" title="<?=$l['DE'];?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;
			<a href="?p=cms_faq&delete=<?=$d->ID;?>" title="<?=$l['DD'];?>" onclick="return confirm('<?=$l['RDD'];?>');"><i class="fa fa-times fa-lg"></i></a>
		</td></tr>
			<?php
}
        }
        ?>
</table></div><?=$l['SELECTED'];?>: <input type="submit" name="delete_selected" value="<?=$l['DD'];?>" class="btn btn-danger"><br /><br /></form>
				</div>
            </div>
            <!-- /.row -->

<?php echo $t->getFooter();}} ?>