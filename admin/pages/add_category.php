<?php
$l = $lang['ADD_CATEGORY'];
title($l['TITLE']);
menu("products");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(45)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_category");
} else {
    ob_start();
    if (isset($_POST['submit'])) {
        try {
            $names = array();
            $casts = array();

            if (!isset($_POST['view']) || ($_POST['view'] != "0" && $_POST['view'] != "1")) {
                throw new Exception($l['ERR1']);
            }

            $themes = [];
            foreach (glob(__DIR__ . "/../../themes/order/*") as $theme) {
                if (is_dir($theme)) {
                    $theme = basename($theme);
                    array_push($themes, $theme);
                }
            }

            if (empty($_POST['template']) || !in_array($_POST['template'], $themes)) {
                $_POST['template'] = "standard";
            }

            foreach ($languages as $lang_key => $lang_title) {
                if (!isset($_POST['name_' . $lang_key]) || trim($_POST['name_' . $lang_key]) == "") {
                    throw new Exception($l['ERR3']);
                }

                if (!isset($_POST['cast_' . $lang_key])) {
                    $_POST['cast_' . $lang_key] = "";
                }

                $names[$lang_key] = $_POST['name_' . $lang_key];
                $casts[$lang_key] = $_POST['cast_' . $lang_key];
            }

            $sql = "INSERT INTO product_categories ";
            $sql .= "(`name`, `cast`, `template`, `view`) VALUES ";
            $sql .= "('" . $db->real_escape_string(serialize($names)) . "', '" . $db->real_escape_string(serialize($casts)) . "', '" . $db->real_escape_string($_POST['template']) . "', " . intval($_POST['view']) . ")";
            if (!$db->query($sql)) {
                throw new Exception($l['ERR4']);
            }

            $iid = $db->insert_id;

            echo '<div class="alert alert-success">' . $l['SUC'] . ' <a href="?p=categories&id=' . $iid . '">' . $l['EDIT'] . '</a> | <a href="?p=categories">' . $l['OVERVIEW'] . '</a></div>';
            alog("general", "category_created", $iid);
            unset($_POST);
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

			<?=$ob;?>

			<form role="form" method="POST">
                <label><?=$l['CV'];?></label><br /> <label class="radio-inline"> <input type="radio" name="view" value="1"<?=isset($_POST['view']) && $_POST['view'] == "1" ? " checked=\"checked\"" : "";?>>
                    <?=$l['ACTIVE'];?>
                </label> <label class="radio-inline"> <input type="radio" name="view" value="0"<?=isset($_POST['view']) && $_POST['view'] == "0" ? " checked=\"checked\"" : "";?>>
                    <?=$l['INACTIVE'];?>
                </label><br /><br />

                <label><?=$l['VT'];?></label><br />
                <?php
                foreach (glob(__DIR__ . "/../../themes/order/*") as $theme) {
                    if (is_dir($theme)) {
                        $theme = basename($theme);
                        ?>
                        <label class="radio-inline"> <input type="radio" name="template" value="<?=$theme; ?>"<?=isset($_POST['template']) && $_POST['template'] == $theme ? " checked=\"checked\"" : "";?>>
                            <?=ucfirst($theme);?>
                        </label>
                        <?php
                    }
                }
                ?>
<br /><br />
                <?php
function lastArrayElement($arr, $key)
    {
        end($arr);
        return $key === key($arr);
    }

    foreach ($languages as $lang_key => $lang_title) {
        ?>
<h3 style="margin-top:0"><?=$lang_title;?></h3>

   <div class="form-group">
    <label><?=$l['NAME'];?></label>
   <input type="text" name="name_<?=$lang_key;?>" class="form-control" placeholder="Kategorie" value="<?=isset($_POST['name_' . $lang_key]) ? $_POST['name_' . $lang_key] : "";?>">
  </div>

<div class="form-group">
    <label><?=$l['CAST'];?></label>
   <input type="text" name="cast_<?=$lang_key;?>" class="form-control" value="<?=isset($_POST['cast_' . $lang_key]) ? $_POST['cast_' . $lang_key] : "";?>" placeholder="<?=$l['CASTP'];?>">
   <p class="help-block"><?=$l['CASTH'];?></p>
  </div>
  <?php if (!lastArrayElement($languages, $lang_key)) {
            echo "<hr />";
        }
        ?>
  <?php }?>

  <center><button type="submit" class="btn btn-primary" name="submit"><?=$l['ADD'];?></button></center></form>


<?php }?>