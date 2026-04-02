<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['EDIT_MAIL_TEMPLATE'];
title($l['TITLE']);
menu("settings");

$sql = $db->query("SELECT * FROM email_templates WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'");

if (!$ari->check(47)) {
    require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "edit_mail_template");
} else if (!isset($_GET['id']) || empty($_GET['id']) || $sql->num_rows != 1) {
    require __DIR__ . "/error.php";
} else {
    $i = $sql->fetch_object();

    if (isset($_GET['l']) && isset($_GET['d']) && file_exists(__DIR__ . "/../../files/email_templates/{$i->ID}/" . basename($_GET['l']) . "/" . basename($_GET['d']))) {
        unlink(__DIR__ . "/../../files/email_templates/{$i->ID}/" . basename($_GET['l']) . "/" . basename($_GET['d']));
    }

    if (isset($_POST['save'])) {
        try {
            $save_title = array();
            $save_content = array();

            foreach ($_POST as $k => $v) {
                $ex = explode("_", $k);

                if (count($ex) > 1) {
                    for ($x = 2; $x < count($ex); $x++) {
                        $ex[1] .= "_" . $ex[$x];
                    }

                    if ($ex[0] == "title") {
                        if ($i->name != "Header" && $i->name != "Footer") {
                            $save_title[$ex[1]] = $v;
                            if (empty(trim($k))) {
                                throw new Exception($l['ERR1']);
                            }

                        }
                    }

                    if ($ex[0] == "content") {
                        $save_content[$ex[1]] = $v;
                        if (empty(trim($k))) {
                            throw new Exception($l['ERR2']);
                        }

                    }
                }
            }

            if ($i->name == "Header" || $i->name == "Footer") {
                $title = "";
            } else {
                $title = $db->real_escape_string(serialize($save_title));
            }

            $content = $db->real_escape_string(serialize($save_content));

            $change_sql = $db->query("UPDATE email_templates SET title = '$title', content = '$content' WHERE ID = " . $i->ID . " LIMIT 1");

            if ($i->name != "Header" && $i->name != "Footer") {
                foreach ($_FILES as $l2 => $d) {
                    $ex = explode("_", $l2);
                    if ($ex[0] != "upload") {
                        continue;
                    }

                    foreach ($_FILES[$l2]['name'] as $k => $n) {
                        if ($_FILES[$l2]['size'][$k] <= 0) {
                            continue;
                        }

                        $d1 = __DIR__ . "/../../files/email_templates/" . intval($_GET['id']);
                        $d2 = $d1 . "/" . basename($ex[1]);
                        $p = $d2 . "/" . basename($n) . ".txt";
                        if (file_exists($p)) {
                            unlink($p);
                        }

                        if (!is_dir($d1)) {
                            mkdir($d1);
                        }

                        if (!is_dir($d2)) {
                            mkdir($d2);
                        }

                        move_uploaded_file($_FILES[$l2]['tmp_name'][$k], $p);
                    }
                }
            }

            if (!$change_sql) {
                throw new Exception($l['ERR3']);
            }

            alog("general", "mail_template_edited", $_GET['id']);
            $error = "<div class='alert alert-success'>{$l['SUC']}</div>";
            unset($_POST);

            $sql = $db->query("SELECT * FROM email_templates WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'");
            $i = $sql->fetch_object();
        } catch (Exception $ex) {
            $error = "<div class='alert alert-danger'>" . $ex->getMessage() . "</div>";
        }
    }

    function lastArrayElement($arr, $key)
    {
        end($arr);
        return $key === key($arr);
    }
    ?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=strip_tags($lang['ISOCODE'] != 'de' && $i->foreign_name ? $i->foreign_name : $i->name);?> <small><?=$l['TITLE'];?></small></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
        echo $error;
    }
    ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <?php if ($i->category != "Eigene") {?>
        <tr>
            <th width="25%" style="vertical-align: middle;"><?=$l['VARIABLES'];?></th>
            <td><?=$i->vars ? nl2br($i->vars) : "<i>" . $l['NOVARIABLES'] . "</i>";?></td>
        </tr>
        <?php }?>

        <tr>
            <th width="25%" style="vertical-align: middle;"><?=$l['GVARIABLES'];?></th>
            <td>%salutation%<br />%pagename%<br />%pageurl%<br />%date%<br />%clientid% (<?=$l['ISTC'];?>)</td>
        </tr>

        <tr>
            <th width="25%" style="vertical-align: middle;"><?=$l['CVARIABLES'];?><br /><span style="font-weight: normal;"><?=$l['CVARIABLES2'];?></span></th>
            <td>%c_firstname%<br />%c_lastname%<br />%c_company%<br />%c_street%<br />%c_streetnumber%<br />%c_postcode%<br />%c_city%<br />%c_country%<br />%c_telephone%<br />%c_mail%</td>
        </tr>
    </table>
</div>

	<form role="form" enctype="multipart/form-data" method="POST">
    <?php foreach ($languages as $lang_key => $lang_title) {
        $db_title = unserialize($i->title)[$lang_key];
        $db_content = unserialize($i->content)[$lang_key];
        ?>
<h3 style="margin-top:0"><?=$lang_title;?><?php if ($i->name != "Header" && $i->name != "Footer" & $i->name != "Newsletter-Disclaimer") {?> <a href="#" class="btn btn-primary btn-xs" onclick="window.open('<?=$raw_cfg['PAGEURL'];?>email?add=1&subject=' + $('#title_<?=$lang_key;?>').val() + '&text=' + encodeURIComponent($('#text_<?=$lang_key;?>').val()), null); return false;"><?=$l['PREVIEW'];?></a><?php }?></h3>

  <?php if ($i->name != "Header" && $i->name != "Footer" & $i->name != "Newsletter-Disclaimer") {?><div class="form-group">
    <label><?=$l['SUBJECT'];?></label>
	<input type="text" name="title_<?=$lang_key;?>" id="title_<?=$lang_key;?>" value="<?=isset($_POST['title_' . $lang_key]) ? $_POST['title_' . $lang_key] : $db_title;?>" placeholder="<?=$l['SUBJECTP'];?>" class="form-control">
  </div><?php }?>

   <div class="form-group">
    <label><?=$l['CONTENT'];?></label>
	<textarea style="width:100%; height:400px; resize:none;" name="content_<?=$lang_key;?>" id="text_<?=$lang_key;?>" class="form-control"><?=isset($_POST['content_' . $lang_key]) ? $_POST['content_' . $lang_key] : ($db_content);?></textarea>
  </div>

  <?php if ($i->name != "Header" && $i->name != "Footer") {?><label><?=$l['ATTACHMENTS'];?></label><br />
  <?php $files = glob(__DIR__ . "/../../files/email_templates/{$i->ID}/{$lang_key}/*.txt");if (count($files) > 0) {?>
  <div class="row" style="margin-bottom: 10px;">
  <?php foreach ($files as $f) {?>
  <div class="col-md-3">
    <i class="fa fa-paperclip"></i> <?=substr(basename($f), 0, -4);?> <a href="./?p=edit_mail_template&id=<?=$_GET['id'];?>&l=<?=$lang_key;?>&d=<?=basename($f);?>" style="color:darkred;" onClick="return confirm('<?=$l['DELATT'];?>');"><i class="fa fa-times"></i></a>
  </div>
  <?php }?>
  </div>
  <?php }?>
  <input type="file" name="upload_<?=$lang_key;?>[]" class="form-control" multiple="multiple" /><?php }?>
  <?php if (!lastArrayElement($languages, $lang_key)) {
            echo "<hr />";
        }
        ?>
  <?php }?>
  <center><button type="submit" class="btn btn-primary btn-block" name="save" style="margin-top: 10px;"><?=$l['SAVE'];?></button></center></form><br />
<?php }?>