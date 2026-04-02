<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SUPPLIER'];

title($l['TITLE']);
menu("payments");

if (!$ari->check(60) || !is_object($sql = $db->query("SELECT * FROM suppliers WHERE ID = " . intval($_GET['id']))) || $sql->num_rows != 1) {
    require __DIR__ . "/error.php";
    if (!$ari->check(60)) {
        alog("general", "insufficient_page_rights", "suppliers");
    }

} else {
    $monthly = 0;
    $quarterly = 0;
    $semiannually = 0;
    $annually = 0;

    $info = $sql->fetch_object();

    title($info->name);
    menu("payments");

    if (isset($_POST['edit'])) {
        try {
            foreach ($_POST as $k => $v) {
                $vari = "post" . ucfirst(strtolower($k));
                $$vari = $db->real_escape_string($v);
            }

            if (!empty($postSupplier)) {
                $db->query("UPDATE suppliers SET name = '$postSupplier' WHERE ID = {$info->ID}");
            }

            if (!empty($postProducts)) {
                $db->query("UPDATE suppliers SET products = '$postProducts' WHERE ID = {$info->ID}");
            }

            $db->query("UPDATE suppliers SET street = '$postStreet', street_number = '$postStreet_number', city = '$postCity', postcode = '$postPostcode' WHERE ID = {$info->ID}");

            $postNotes = encrypt($postNotes);
            $db->query("UPDATE suppliers SET notes = '$postNotes' WHERE ID = {$info->ID}");
            alog("supplier", "change", $info->ID);
            $info = $db->query("SELECT * FROM suppliers WHERE ID = " . intval($_GET['id']))->fetch_object();
        } catch (Exception $ex) {
            $error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
        }
    }

    if (isset($_GET['delete'])) {
        $db->query("DELETE FROM supplier_contracts WHERE ID = " . intval($_GET['delete']) . " AND supplier = " . intval($_GET['id']) . " LIMIT 1");
        if ($db->affected_rows) {
            $error = "<div class=\"alert alert-success\">{$l['SUC1']}</div>";
        }
    }
    ?>

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=htmlentities($info->name);?>
                <a href="?p=add_supplier_contract&supplier=<?=$info->ID;?>" class="pull-right"><i class="fa fa-plus-circle"></i></a>
            </h1>
        </div>
    </div>

    <?php if (isset($error)) {
        echo $error;
    }
    ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <tr>
                <th width="40%"><?=$l['CONTRACT'];?></th>
                <th width="30%"><?=$l['COSTS'];?></th>
                <th width="30%"><?=$l['CM'];?></th>
            </tr>

            <?php
$sql = $db->query("SELECT * FROM supplier_contracts WHERE supplier = {$info->ID} ORDER BY (cancellation_date > '" . date("Y-m-d") . "' OR cancellation_date = '0000-00-00') ASC, name ASC");
    if ($sql->num_rows == 0) {
        ?>
                <tr>
                    <td colspan="3">
                        <center><?=$l['NT'];?></center>
                    </td>
                </tr>
            <?php } else {
        while ($row = $sql->fetch_object()) {?>
                    <tr>
                        <td style="vertical-align: middle;"><a
                                href="?p=supplier_contract&id=<?=$row->ID;?>"><?=$row->name;?></a></td>
                        <td style="vertical-align: middle;">
                            <?php
if ($row->period == 1) {
            if ($row->cancellation_date == "0000-00-00" || $row->cancellation_date > date("Y-m-d")) {
                $monthly += $row->price;
            }

            echo $cur->infix($nfo->format($row->price), $cur->getBaseCurrency()) . " " . $l['MONTHLY'];
        }

            if ($row->period == 3) {
                if ($row->cancellation_date == "0000-00-00" || $row->cancellation_date > date("Y-m-d")) {
                    $quarterly += $row->price;
                }

                echo $cur->infix($nfo->format($row->price), $cur->getBaseCurrency()) . " " . $l['QUARTERLY'];
            }

            if ($row->period == 6) {
                if ($row->cancellation_date == "0000-00-00" || $row->cancellation_date > date("Y-m-d")) {
                    $semiannually += $row->price;
                }

                echo $cur->infix($nfo->format($row->price), $cur->getBaseCurrency()) . " " . $l['SEMIANNUALLY'];
            }

            if ($row->period == 12) {
                if ($row->cancellation_date == "0000-00-00" || $row->cancellation_date > date("Y-m-d")) {
                    $annually += $row->price;
                }

                echo $cur->infix($nfo->format($row->price), $cur->getBaseCurrency()) . " " . $l['ANNUALLY'];
            }
            ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <?php
if ($row->cancellation_date > '0000-00-00') {
                echo '<span style="color: red;">' . $l['CANCELLED'] . ' ' . $dfo->format($row->cancellation_date, false) . '</span>';
            } else {
                $count = 0;
                $ex = explode(" ", $row->ct);
                $i = array("days" => $l['DAY'], "months" => $l['MONTH'], "years" => $l['YEAR'], "dayss" => $l['DAYS'], "monthss" => $l['MONTHS'], "yearss" => $l['YEARS']);

                if (!empty($ex[0])) {
                    echo $ex[0] . " " . $i[$ex[1] . ($ex[0] > 1 ? "s" : "")] . " " . $l['CT'];
                }

                $eo = $ex;
                $ex = explode(" ", $row->np);
                if (!empty($ex[0])) {
                    echo (!empty($eo[0]) ? "<br />" : "") . $ex[0] . " " . $i[$ex[1] . ($ex[0] > 1 ? "s" : "")] . " " . $l['NP'];
                }

                if (empty($ex[0]) && empty($eo[0])) {
                    echo "<i>- {$l['NF']} -</i>";
                }

            }
            ?>
                        </td>
                    </tr>
                <?php }
    }?>

            <tr>
                <th colspan="1" style="text-align: right;">
                    <?=ucfirst($l['MONTHLY']);?>
                </th>
                <td colspan="2"><?=$cur->infix($nfo->format($monthly), $cur->getBaseCurrency());?></td>
            </tr>

            <tr>
                <th colspan="1" style="text-align: right;">
                    <?=ucfirst($l['QUARTERLY']);?>
                </th>
                <td colspan="2"><?=$cur->infix($nfo->format($quarterly), $cur->getBaseCurrency());?>
                    (<?=$cur->infix($nfo->format($quarterly + 3 * $monthly), $cur->getBaseCurrency());?>)
                </td>
            </tr>

            <tr>
                <th colspan="1" style="text-align: right;">
                    <?=ucfirst($l['SEMIANNUALLY']);?>
                </th>
                <td colspan="2"><?=$cur->infix($nfo->format($semiannually), $cur->getBaseCurrency());?>
                    (<?=$cur->infix($nfo->format($semiannually + 6 * $monthly + 2 * $quarterly), $cur->getBaseCurrency());?>
                    )
                </td>
            </tr>

            <tr>
                <th colspan="1" style="text-align: right;">
                    <?=ucfirst($l['ANNUALLY']);?>
                </th>
                <td colspan="2"><?=$cur->infix($nfo->format($annually), $cur->getBaseCurrency());?>
                    (<?=$cur->infix($nfo->format($annually + 12 * $monthly + 4 * $quarterly + 2 * $semiannually), $cur->getBaseCurrency());?>
                    )
                </td>
            </tr>
        </table>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading"><?=$l['DATA'];?></div>
        <div class="panel-body">
            <form method="POST">
                <div class="form-group">
                    <label><?=$l['TITLE'];?></label>
                    <input type="text" name="supplier"
                           value="<?=isset($_POST['supplier']) ? htmlentities($_POST['supplier']) : htmlentities($info->name);?>"
                           placeholder="<?=$l['COMPANY'];?>" class="form-control">
                </div>

                <div class="form-group" style="margin-bottom: 5px;">
                    <label><?=$l['ADDRESS'];?></label>
                    <div class="row">
                        <div class="col-md-8">
                            <input type="text" name="street" value="<?=isset($_POST['street']) ? htmlentities($_POST['street']) : htmlentities($info->street);?>"
                            placeholder="<?=$l['STREET'];?>" class="form-control" style="margin-bottom: 10px;">
                        </div>

                        <div class="col-md-4">
                            <input type="text" name="street_number" value="<?=isset($_POST['street_number']) ? htmlentities($_POST['street_number']) : htmlentities($info->street_number);?>"
                            placeholder="<?=$l['STREETNR'];?>" class="form-control" style="margin-bottom: 10px;">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="postcode" value="<?=isset($_POST['postcode']) ? htmlentities($_POST['postcode']) : htmlentities($info->postcode);?>"
                            placeholder="<?=$l['POSTCODE'];?>" class="form-control" style="margin-bottom: 10px;">
                        </div>

                        <div class="col-md-8">
                            <input type="text" name="city" value="<?=isset($_POST['city']) ? htmlentities($_POST['city']) : htmlentities($info->city);?>"
                            placeholder="<?=$l['CITY'];?>" class="form-control" style="margin-bottom: 10px;">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><?=$l['PRODUCTS'];?></label>
                    <input type="text" name="products"
                           value="<?=isset($_POST['products']) ? $_POST['products'] : htmlentities($info->products);?>"
                           placeholder="<?=$l['PRODUCTSP'];?>" class="form-control">
                </div>

                <div class="form-group">
                    <label><?=$l['NOTES'];?></label>
                    <textarea name="notes" class="form-control" style="resize: none; width: 100%; height: 150px;"
                              placeholder="<?=$l['OPTIONAL'];?>"><?=isset($_POST['notes']) ? $_POST['notes'] : htmlentities(decrypt($info->notes));?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary btn-block" name="edit"><?=$l['SAVE'];?></button>
                    </div>

                    <div class="col-md-6">
                        <a href="?p=suppliers&delete=<?=$info->ID;?>" class="btn btn-warning btn-block"><?=$l['DELETE'];?></a>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php }?>