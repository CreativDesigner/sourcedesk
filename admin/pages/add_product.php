<?php
$l = $lang['ADD_PRODUCT'];
title($l['TITLE']);
menu("products");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(25)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_product");} else {

    ob_start();
    if (isset($_POST['submit'])) {
        try {
            $name = array();
            $desc = array();

            $type = "hosting";

            foreach ($languages as $lang_key => $lang_name) {
                if (!isset($_POST['name_' . $lang_key]) || $_POST['name_' . $lang_key] == "") {
                    throw new Exception($l['ERR1']);
                }

                $name[$lang_key] = $_POST['name_' . $lang_key];
                $desc[$lang_key] = isset($_POST['description_' . $lang_key]) ? $_POST['description_' . $lang_key] : "";
            }

            if (!isset($_POST['category']) || ($_POST['category'] != 0 && $db->query("SELECT ID FROM product_categories WHERE ID = '" . $db->real_escape_string($_POST['category'] . "' LIMIT 1")->num_rows != 1))) {
                throw new Exception($l['ERR6']);
            }

            $price = $nfo->phpize($_POST['price']);
            if (!isset($_POST['price']) || !is_numeric($price) || $price < 0) {
                throw new Exception($l['ERR7']);
            }

            if (empty($_POST['interval']) || !in_array($_POST['interval'], array("onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "trinnially", "minutely", "hourly"))) {
                throw new Exception($l['ERR8']);
            }

            if (!isset($_POST['affiliate']) || trim($_POST['affiliate']) == "") {
                $affiliate = -1;
            } else {
                $affiliate = $nfo->phpize($_POST['affiliate']);
                if ((!is_numeric($affiliate) && !is_double($affiliate)) || $affiliate < 0) {
                    throw new Exception($l['ERR12']);
                }

            }

            if (!isset($_POST['status']) || ($_POST['status'] != 0 && $_POST['status'] != 1)) {
                throw new Exception($l['ERR13']);
            }

            $sql = "INSERT INTO products ";
            $sql .= "(`name`, `status`, `price`, `category`, `description`, `affiliate`, `billing`, `type`) VALUES ";
            $sql .= "('" . $db->real_escape_string(serialize($name)) . "', '" . $db->real_escape_string($_POST['status']) . "', '" . $db->real_escape_string($price) . "', '" . $db->real_escape_string($_POST['category']) . "', '" . $db->real_escape_string(serialize($desc)) . "', $affiliate, '" . $db->real_escape_string($_POST['interval']) . "', 'HOSTING')";

            if (!$db->query($sql)) {
                throw new Exception($l['ERR14']);
            }

            $iid = $db->insert_id;

            alog("general", "product_created", $iid);

            header('Location: ?p=product_hosting&id=' . $iid);
            exit;
        } catch (Exception $ex) {
            echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . htmlentities($ex->getMessage()) . '</div>';
        }
    }

    $ob = ob_get_contents();
    ob_end_clean();

    ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<?php
$files = array();
    $handle = opendir(__DIR__ . "/../../files/downloads/");
    while ($datei = readdir($handle)) {
        if (substr($datei, 0, 1) == ".") {
            continue;
        }

        array_push($files, $datei);
    }
    asort($files);

    echo $ob;
    ?>

			<form role="form" method="POST" enctype="multipart/form-data">

                <label><?=$l['ND'];?></label>
                <?php foreach ($languages as $lang_key => $lang_name) {?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
                <?php }?>
                <br />
                <?php foreach ($languages as $lang_key => $lang_name) {
        $name = "";
        $desc = "";
        ?>
                <div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>>
                    <input type="text" name="name_<?=$lang_key;?>" class="form-control" placeholder="<?=$l['PNL'];?>" value="<?=isset($_POST['name_' . $lang_key]) ? $_POST['name_' . $lang_key] : $name;?>">
                    <textarea name="description_<?=$lang_key;?>" class="form-control" style="resize:none; height:200px;margin-top: 10px; margin-bottom: 15px;"><?=isset($_POST['description_' . $lang_key]) ? $_POST['description_' . $lang_key] : ($desc);?></textarea>
                </div>
                <?php }?>

   <div class="form-group">
    <label><?=$l['CAT'];?></label>
   <select name="category" class="form-control">
   	<option value="0" <?php if ((isset($_POST['category']) && $_POST['category'] == 0)) {
        echo "selected=\"selected\"";
    }
    ?>><?=$l['NOCAT'];?></option>
   	<?php
$catSql = $db->query("SELECT * FROM product_categories");
    $cats = array();
    while ($c = $catSql->fetch_object()) {
        $cats[unserialize($c->name)[$CFG['LANG']]] = $c;
    }

    ksort($cats);

    foreach ($cats as $c) {
        ?>
	<option value="<?=$c->ID;?>" <?php if ((isset($_POST['category']) && $_POST['category'] == $c->ID)) {
            echo "selected=\"selected\"";
        }
        ?>><?=unserialize($c->name)[$CFG['LANG']];?></option>
   	<?php
}
    ?>
   </select>
  </div>

  <div class="form-group">
    <label><?=$l['PRICE'];?></label>
   <input type="text" name="price" class="form-control" value="<?=isset($_POST['price']) ? $_POST['price'] : "";?>" placeholder="<?=$nfo->placeholder();?>">
  </div>

  <div class="form-group">
    <label><?=$l['INT'];?></label>
    <select name="interval" class="form-control">
      <option value="onetime"><?=$l['INTOT'];?></option>
      <option value="monthly"<?=isset($_POST['interval']) && $_POST['interval'] == "monthly" ? ' selected="selected"' : "";?>><?=$l['INTMO'];?></option>
      <option value="quarterly"<?=isset($_POST['interval']) && $_POST['interval'] == "quarterly" ? ' selected="selected"' : "";?>><?=$l['INTQU'];?></option>
      <option value="semiannually"<?=isset($_POST['interval']) && $_POST['interval'] == "semiannually" ? ' selected="selected"' : "";?>><?=$l['INTSE'];?></option>
      <option value="annually"<?=isset($_POST['interval']) && $_POST['interval'] == "annually" ? ' selected="selected"' : "";?>><?=$l['INTAN'];?></option>
      <option value="biennially"<?=isset($_POST['interval']) && $_POST['interval'] == "biennially" ? ' selected="selected"' : "";?>><?=$l['INTBI'];?></option>
      <option value="trinnially"<?=isset($_POST['interval']) && $_POST['interval'] == "trinnially" ? ' selected="selected"' : "";?>><?=$l['INTTR'];?></option>
      <option value="minutely"<?=isset($_POST['interval']) && $_POST['interval'] == "minutely" ? ' selected="selected"' : "";?>><?=$l['INTMI'];?></option>
      <option value="hourly"<?=isset($_POST['interval']) && $_POST['interval'] == "hourly" ? ' selected="selected"' : "";?>><?=$l['INTHO'];?></option>
    </select>
  </div>

  <div class="form-group">
    <label><?=$l['AFF'];?></label>
    <div class="input-group">
      <input type="text" name="affiliate" class="form-control" value="<?=isset($_POST['affiliate']) ? str_replace("-1.00", "", $_POST['affiliate']) : $nfo->format(str_replace("-1.00", "", "-1.00"));?>" placeholder="leer lassen f&uuml;r Standard-Wert">
      <span class="input-group-addon">%</span>
    </div>
  </div>

  <div class="form-group">
<label><?=$l['STATUS'];?></label><br />
<label class="radio-inline">
<input type="radio" name="status" value="1" <?=isset($_POST['status']) ? ($_POST['status'] == "1" ? "checked" : "") : "";?>>
<?=$l['ACTIVE'];?>
</label>
<label class="radio-inline">
<input type="radio" name="status" value="0" <?=isset($_POST['status']) ? ($_POST['status'] == "0" ? "checked" : "") : "";?>>
<?=$l['INACTIVE'];?>
</label>
</div>

  <center><button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['BTN2'];?></button></center></form>


<?php }?>