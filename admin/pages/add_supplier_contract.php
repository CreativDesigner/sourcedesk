<?php
$l = $lang['ADD_SUPPLIER_CONTRACT'];
title($l['TITLE']);
menu("payments");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(60) || !is_object($sql = $db->query("SELECT * FROM suppliers WHERE ID = " . intval($_GET['supplier']))) || $sql->num_rows != 1) {
    require __DIR__ . "/error.php";
    if (!$ari->check(60)) {
        alog("general", "insufficient_page_rights", "add_supplier_contract");
    }

} else {
    $supplier = $sql->fetch_object();

    if (isset($_POST['add'])) {

        try {
            foreach ($_POST as $k => $v) {
                $vari = "post" . ucfirst(strtolower($k));
                $$vari = $db->real_escape_string($v);
            }

            if (empty($postName)) {
                throw new Exception($l['ERR1']);
            }

            $price = $nfo->phpize($postPrice);
            if (!is_numeric($price) && !is_double($price)) {
                throw new Exception($l['ERR2']);
            }

            if (!in_array($postPeriod, array("1", "3", "6", "12"))) {
                throw new Exception($l['ERR3']);
            }

            if (empty($postCt1)) {
                $postCt1 = "0";
            }

            if (!is_numeric($postCt1) || !in_array($postCt2, array("days", "months", "years"))) {
                throw new Exception($l['ERR4']);
            }

            $ct = $postCt1 . " " . $postCt2;

            if (empty($postNp1)) {
                $postNp1 = "0";
            }

            if (!is_numeric($postNp1) || !in_array($postNp2, array("days", "months", "years"))) {
                throw new Exception($l['ERR5']);
            }

            $np = $postNp1 . " " . $postNp2;

            if (empty($postCancellation_date) || strtotime($postCancellation_date) === false) {
                $postCancellation_date = "0000-00-00";
            } else {
                $postCancellation_date = date("Y-m-d", strtotime($postCancellation_date));
            }

            $postNotes = encrypt($postNotes);
            $db->query("INSERT INTO supplier_contracts (`name`, `supplier`, `price`, `period`, `ct`, `np`, `cancellation_date`, `notes`) VALUES ('$postName', '{$supplier->ID}', '$price', '$postPeriod', '$ct', '$np', '$postCancellation_date', '$postNotes')");
            if ($db->errno) {
                throw new Exception($l['ERR6']);
            }

            alog("suppliers", "contract_created", $db->insert_id);

            $error = "<div class=\"alert alert-success\">{$l['SUC']}</div>";
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
            <label><?=$l['SUPPLIER'];?></label><br/>
            <span class="control-label"><a
                    href="?p=supplier&id=<?=$supplier->ID;?>"><?=$supplier->name;?></a></span>
        </div>

        <div class="form-group">
            <label><?=$l['NAME'];?></label>
            <input type="text" name="name" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>"
                   placeholder="<?=$l['NAMEP'];?>" class="form-control">
        </div>

        <div class="row">
            <div class="col-md-9">
                <div class="form-group">
                    <label><?=$l['COSTS'];?></label>
                    <div class="input-group">
                        <?php if (!empty($cur->getPrefix())) {?><span
                            class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
                        <input type="text" name="price" value="<?=isset($_POST['price']) ? $_POST['price'] : "";?>"
                               placeholder="<?=$nfo->placeholder();?>" class="form-control">
                        <?php if (!empty($cur->getSuffix())) {?><span
                            class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label><?=$l['INT'];?></label>
                    <select name="period" class="form-control">
                        <option value="1"><?=$lang['ADD_PRODUCT']['INTMO'];?></option>
                        <option
                            value="3"<?=isset($_POST['period']) && $_POST['period'] == "3" ? " selected='selected'" : "";?>>
                            <?=$lang['ADD_PRODUCT']['INTQU'];?>
                        </option>
                        <option
                            value="6"<?=isset($_POST['period']) && $_POST['period'] == "6" ? " selected='selected'" : "";?>>
                            <?=$lang['ADD_PRODUCT']['INTSE'];?>
                        </option>
                        <option
                            value="12"<?=isset($_POST['period']) && $_POST['period'] == "12" ? " selected='selected'" : "";?>>
                            <?=$lang['ADD_PRODUCT']['INTAN'];?>
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 col-xs-6">
                <div class="form-group">
                    <label><?=$l['CT'];?></label>
                    <input type="text" name="ct1" placeholder="<?=$l['OPTIONAL'];?>"
                           value="<?=isset($_POST['ct1']) ? $_POST['ct1'] : "";?>" class="form-control">
                </div>
            </div>

            <div class="col-md-3 col-xs-6">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <select name="ct2" class="form-control">
                        <option value="days"><?=$l['CTD'];?></option>
                        <option
                            value="months"<?=isset($_POST['ct2']) && $_POST['ct2'] == "months" ? ' selected="selected"' : "";?>>
                            <?=$l['CTM'];?>
                        </option>
                        <option
                            value="years"<?=isset($_POST['ct2']) && $_POST['ct2'] == "years" ? ' selected="selected"' : "";?>>
                            <?=$l['CTY'];?>
                        </option>
                    </select>
                </div>
            </div>

            <div class="col-md-3 col-xs-6">
                <div class="form-group">
                    <label><?=$l['NP'];?></label>
                    <input type="text" name="np1" placeholder="<?=$l['OPTIONAL'];?>"
                           value="<?=isset($_POST['np1']) ? $_POST['np1'] : "";?>" class="form-control">
                </div>
            </div>

            <div class="col-md-3 col-xs-6">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <select name="np2" class="form-control">
                        <option value="days"><?=$l['CTD'];?></option>
                        <option
                            value="months"<?=isset($_POST['np2']) && $_POST['np2'] == "months" ? ' selected="selected"' : "";?>>
                            <?=$l['CTM'];?>
                        </option>
                        <option
                            value="years"<?=isset($_POST['np2']) && $_POST['np2'] == "years" ? ' selected="selected"' : "";?>>
                            <?=$l['CTY'];?>
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group" style="position: relative;">
            <label><?=$l['CD'];?></label>
            <input type="text" name="cancellation_date" placeholder="<?=$l['CDN'];?>"
                   value="<?=isset($_POST['cancellation_date']) ? $_POST['cancellation_date'] : "";?>"
                   class="form-control datepicker">
        </div>

        <div class="form-group">
            <label><?=$l['NOTES'];?></label>
            <textarea name="notes" class="form-control" style="resize: none; width: 100%; height: 150px;"
                      placeholder="<?=$l['OPTIONAL'];?>"><?=isset($_POST['notes']) ? $_POST['notes'] : "";?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block" name="add"><?=$l['DO'];?></button>
    </form>


<?php }?>