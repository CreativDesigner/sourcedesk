<?php
$l = $lang['ADD_FAQ'];
title($l['TITLE']);
menu("cms");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(50)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_cms_faq");} else {

    if (isset($_POST['add'])) {

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

            $db->query("INSERT INTO cms_faq (`question`, `answer`) VALUES ('$q', '$a')");
            if ($db->errno) {
                throw new Exception($l['ERR3']);
            }

            $error = "<div class=\"alert alert-success\">{$l['SUC']} <a href=\"?p=cms_faq\">{$l['OVERVIEW']}</a></div>";
            alog("general", "faq_entry_created", $db->insert_id);
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

  <?php $i = 0;foreach ($languages as $k => $v) {if ($i != 0) {
        echo "<hr />";
    }
        ?>
  <h3 style="margin-top:0"><?=$v;?></h3>
  <div class="form-group">
    <label><?=$l['QUESTION'];?></label>
	<input type="text" name="question_<?=$k;?>" id="question_<?=$k;?>" value="<?=isset($_POST['question_' . $k]) ? $_POST['question_' . $k] : '';?>" placeholder="<?=$l['QUESTIONP'];?>" class="form-control">
  </div>
   <div class="form-group">
    <label><?=$l['ANSWER'];?></label>
	<textarea style="width:100%; height:100px; resize:none;" name="answer_<?=$k;?>" id="answer_<?=$k;?>" class="form-control summernote" placeholder="<?=$l['ANSWERP'];?>"><?=isset($_POST['answer_' . $k]) ? $_POST['answer_' . $k] : '';?></textarea>
  </div>
  <?php $i++;}?>

  <center><button type="submit" class="btn btn-primary btn-block" name="add"><?=$l['ADD'];?></button></center></form>


<?php }?>