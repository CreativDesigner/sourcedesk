<?php
$l = $lang['ADD_SUPPLIER'];
title($l['TITLE']);
menu("payments");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(60)) {
    require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_supplier");
} else {

    if (isset($_POST['add'])) {

        try {
            foreach ($_POST as $k => $v) {
                $vari = "post" . ucfirst(strtolower($k));
                $$vari = $db->real_escape_string($v);
            }

            if (empty($postSupplier)) {
                throw new Exception($l['ERR1']);
            }

            if (empty($postProducts)) {
                throw new Exception($l['ERR2']);
            }

            $postNotes = encrypt($postNotes);
            $db->query("INSERT INTO suppliers (`name`, `products`, `notes`, `street`, `street_number`, `postcode`, `city`) VALUES ('$postSupplier', '$postProducts', '$postNotes', '$postStreet', '$postStreet_number', '$postPostcode', '$postCity')");
            if ($db->errno) {
                throw new Exception($l['ERR3']);
            }

            alog("suppliers", "created", $iid = $db->insert_id);

            header('Location: ?p=supplier&id=' . $iid);
            exit;
        } catch (Exception $ex) {
            $error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}/b> " . $ex->getMessage() . "</div>";
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
            <label><?=$l['SUPPLIER'];?></label>
            <input type="text" name="supplier" value="<?=isset($_POST['supplier']) ? htmlentities($_POST['supplier']) : "";?>"
                   placeholder="<?=$l['COMPANY'];?>" class="form-control">
        </div>

        <div class="form-group" style="margin-bottom: 5px;">
            <label><?=$l['ADDRESS'];?></label>
            <div class="row">
                <div class="col-md-8">
                    <input type="text" name="street" value="<?=isset($_POST['street']) ? htmlentities($_POST['street']) : "";?>"
                    placeholder="<?=$lang['QUOTE']['ST'];?>" class="form-control" style="margin-bottom: 10px;">
                </div>

                <div class="col-md-4">
                    <input type="text" name="street_number" value="<?=isset($_POST['street_number']) ? htmlentities($_POST['street_number']) : "";?>"
                    placeholder="<?=$lang['QUOTE']['SN'];?>" class="form-control" style="margin-bottom: 10px;">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="postcode" value="<?=isset($_POST['postcode']) ? htmlentities($_POST['postcode']) : "";?>"
                    placeholder="<?=$lang['QUOTE']['PC'];?>" class="form-control" style="margin-bottom: 10px;">
                </div>

                <div class="col-md-8">
                    <input type="text" name="city" value="<?=isset($_POST['city']) ? htmlentities($_POST['city']) : "";?>"
                    placeholder="<?=$lang['QUOTE']['CT'];?>" class="form-control" style="margin-bottom: 10px;">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label><?=$l['PRODUCTS'];?></label>
            <input type="text" name="products" value="<?=isset($_POST['products']) ? htmlentities($_POST['products']) : "";?>"
                   placeholder="<?=$l['PRODUCTSP'];?>" class="form-control">
        </div>

        <div class="form-group">
            <label><?=$l['NOTES'];?></label>
            <textarea name="notes" class="form-control" style="resize: none; width: 100%; height: 150px;"
                      placeholder="<?=$l['NOTESP'];?>"><?=isset($_POST['notes']) ? htmlentities($_POST['notes']) : "";?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block" name="add"><?=$l['ADD'];?></button>
    </form>


<?php }?>