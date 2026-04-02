<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SUPPLIER_CONTRACT'];

title($l['TITLE']);
menu("payments");

if (!$ari->check(60) || !is_object($sql = $db->query("SELECT * FROM supplier_contracts WHERE ID = " . intval($_GET['id']))) || $sql->num_rows != 1) {
    require __DIR__ . "/error.php";
    if (!$ari->check(60)) {
        alog("general", "insufficient_page_rights", "supplier_contract");
    }

} else {

    $info = $sql->fetch_object();
    $supplier = $db->query("SELECT * FROM suppliers WHERE ID = {$info->supplier}")->fetch_object();

    if (isset($_POST['edit'])) {

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
            $db->query("UPDATE supplier_contracts SET name = '$postName', price = '$price', period = '$postPeriod', ct = '$ct', np = '$np', cancellation_date = '$postCancellation_date', notes = '$postNotes' WHERE ID = {$info->ID}");
            if ($db->errno) {
                throw new Exception($l['ERR6']);
            }

            alog("supplier", "contract_change", $postName, $info->ID);

            $error = "<div class=\"alert alert-success\">{$l['SUC']}</div>";
            unset($_POST);
            $info = $db->query("SELECT * FROM supplier_contracts WHERE ID = " . intval($_GET['id']))->fetch_object();
        } catch (Exception $ex) {
            $error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
        }

    }
    ?>

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$info->name;?>
                <small><a href="?p=supplier&id=<?=$supplier->ID;?>"><?=$supplier->name;?></a>
                </small>
            </h1>
        </div>
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
            <input type="text" name="name" value="<?=isset($_POST['name']) ? $_POST['name'] : $info->name;?>"
                   placeholder="<?=$l['NAMEP'];?>" class="form-control">
        </div>

        <div class="row">
            <div class="col-md-9">
                <div class="form-group">
                    <label><?=$l['COSTS'];?></label>
                    <div class="input-group">
                        <?php if (!empty($cur->getPrefix())) {?><span
                            class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
                        <input type="text" name="price"
                               value="<?=isset($_POST['price']) ? $_POST['price'] : $nfo->format($info->price);?>"
                               placeholder="<?=$nfo->placeholder();?>" class="form-control">
                        <?php if (!empty($cur->getSuffix())) {?><span
                            class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label><?=$l['INT'];?></label>
                    <?php if (!isset($_POST['period'])) {
        $_POST['period'] = $info->period;
    }
    ?>
                    <select name="period" class="form-control">
                        <option value="1"><?=ucfirst($l['MONTHLY']);?></option>
                        <option
                            value="3"<?=isset($_POST['period']) && $_POST['period'] == "3" ? " selected='selected'" : "";?>>
                            <?=ucfirst($l['QUARTERLY']);?>
                        </option>
                        <option
                            value="6"<?=isset($_POST['period']) && $_POST['period'] == "6" ? " selected='selected'" : "";?>>
                            <?=ucfirst($l['SEMIANNUALLY']);?>
                        </option>
                        <option
                            value="12"<?=isset($_POST['period']) && $_POST['period'] == "12" ? " selected='selected'" : "";?>>
                            <?=ucfirst($l['ANNUALLY']);?>
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
                           value="<?=isset($_POST['ct1']) ? $_POST['ct1'] : explode(" ", $info->ct)[0];?>"
                           class="form-control">
                </div>
            </div>

            <div class="col-md-3 col-xs-6">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <?php if (!isset($_POST['ct2'])) {
        $_POST['ct2'] = explode(" ", $info->ct)[1];
    }
    ?>
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
                           value="<?=isset($_POST['np1']) ? $_POST['np1'] : explode(" ", $info->np)[0];?>"
                           class="form-control">
                </div>
            </div>

            <div class="col-md-3 col-xs-6">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <?php if (!isset($_POST['np2'])) {
        $_POST['np2'] = explode(" ", $info->np)[1];
    }
    ?>
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
                   value="<?=isset($_POST['cancellation_date']) ? $_POST['cancellation_date'] : ($info->cancellation_date != "0000-00-00" ? $dfo->format($info->cancellation_date, false) : "");?>"
                   class="form-control datepicker">
        </div>

        <div class="form-group">
            <label><?=$l['NOTES'];?></label>
            <textarea name="notes" class="form-control" style="resize: none; width: 100%; height: 150px;"
                      placeholder="<?=$l['OPTIONAL'];?>"><?=isset($_POST['notes']) ? $_POST['notes'] : decrypt($info->notes);?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary btn-block" name="edit"><?=$l['DO'];?></button>
            </div>

            <div class="col-md-6">
                <a href="?p=supplier&id=<?=$info->supplier;?>&delete=<?=$info->ID;?>" class="btn btn-warning btn-block"><?=$l['DELETE'];?></a>
            </div>
        </div>
    </form>

<?php }?>