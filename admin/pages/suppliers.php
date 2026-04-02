<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SUPPLIERS'];

title($l['TITLE']);
menu("payments");

if (!$ari->check(60)) {
    require __DIR__ . "/error.php";
    if (!$ari->check(60)) {
        alog("general", "insufficient_page_rights", "suppliers");
    }

} else {

    $monthly = 0;
    $quarterly = 0;
    $semiannually = 0;
    $annually = 0;

    if (isset($_GET['delete'])) {
        $db->query("DELETE FROM suppliers WHERE ID = " . intval($_GET['delete']) . " LIMIT 1");
        if ($db->affected_rows) {
            $db->query("DELETE FROM supplier_contracts WHERE supplier = " . intval($_GET['delete']));
            $error = "<div class=\"alert alert-success\">{$l['SUC1']}</div>";
        }
    }

    ?>

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$l['TITLE'];?>
                <a href="?p=add_supplier" class="pull-right"><i class="fa fa-plus-circle"></i></a>
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
                <th width="40%"><?=$l['SUPPLIER'];?></th>
                <th width="30%"><?=$l['PRODUCTS'];?></th>
                <th><?=$l['COSTS'];?></th>
            </tr>

            <?php
$sql = $db->query("SELECT * FROM suppliers ORDER BY name ASC");
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
                        <td style="vertical-align: middle;"><a href="?p=supplier&id=<?=$row->ID;?>"><?=$row->name;?></a></td>
                        <td style="vertical-align: middle;"><?=$row->products;?></td>
                        <td style="vertical-align: middle;">
                            <?php
$myM = $db->query("SELECT SUM(price) AS s FROM supplier_contracts WHERE period = '1' AND supplier = {$row->ID} AND (cancellation_date = '0000-00-00' OR cancellation_date > '" . date("Y-m-d") . "')")->fetch_object()->s;
            $myQ = $db->query("SELECT SUM(price) AS s FROM supplier_contracts WHERE period = '3' AND supplier = {$row->ID} AND (cancellation_date = '0000-00-00' OR cancellation_date > '" . date("Y-m-d") . "')")->fetch_object()->s;
            $myS = $db->query("SELECT SUM(price) AS s FROM supplier_contracts WHERE period = '6' AND supplier = {$row->ID} AND (cancellation_date = '0000-00-00' OR cancellation_date > '" . date("Y-m-d") . "')")->fetch_object()->s;
            $myA = $db->query("SELECT SUM(price) AS s FROM supplier_contracts WHERE period = '12' AND supplier = {$row->ID} AND (cancellation_date = '0000-00-00' OR cancellation_date > '" . date("Y-m-d") . "')")->fetch_object()->s;

            $count = 0;
            if ($myM) {
                $count++;
                echo $cur->infix($nfo->format($myM), $cur->getBaseCurrency()) . " " . $l['MONTHLY'];
            }

            if ($myQ) {
                $count++;
                echo ($count - 1 > 0 ? "<br />" : "") . $cur->infix($nfo->format($myQ), $cur->getBaseCurrency()) . " " . $l['QUARTERLY'];
            }

            if ($myS) {
                $count++;
                echo ($count - 1 > 0 ? "<br />" : "") . $cur->infix($nfo->format($myS), $cur->getBaseCurrency()) . " " . $l['SEMIANNUALLY'];
            }

            if ($myA) {
                $count++;
                echo ($count - 1 > 0 ? "<br />" : "") . $cur->infix($nfo->format($myA), $cur->getBaseCurrency()) . " " . $l['ANNUALLY'];
            }

            if (!$count) {
                echo "<i>- {$l['NC']} -</i>";
            }

            $monthly += $myM;
            $quarterly += $myQ;
            $semiannually += $myS;
            $annually += $myA;
            ?>
                        </td>
                    </tr>
                <?php }
    }?>

            <tr>
                <th colspan="2" style="text-align: right;">
                    <?=ucfirst($l['MONTHLY']);?>
                </th>
                <td><?=$cur->infix($nfo->format($monthly), $cur->getBaseCurrency());?></td>
            </tr>

            <tr>
                <th colspan="2" style="text-align: right;">
                    <?=ucfirst($l['QUARTERLY']);?>
                </th>
                <td><?=$cur->infix($nfo->format($quarterly), $cur->getBaseCurrency());?>
                    (<?=$cur->infix($nfo->format($quarterly + 3 * $monthly), $cur->getBaseCurrency());?>)
                </td>
            </tr>

            <tr>
                <th colspan="2" style="text-align: right;">
                    <?=ucfirst($l['SEMIANNUALLY']);?>
                </th>
                <td><?=$cur->infix($nfo->format($semiannually), $cur->getBaseCurrency());?>
                    (<?=$cur->infix($nfo->format($semiannually + 6 * $monthly + 2 * $quarterly), $cur->getBaseCurrency());?>
                    )
                </td>
            </tr>

            <tr>
                <th colspan="2" style="text-align: right;">
                    <?=ucfirst($l['ANNUALLY']);?>
                </th>
                <td><?=$cur->infix($nfo->format($annually), $cur->getBaseCurrency());?>
                    (<?=$cur->infix($nfo->format($annually + 12 * $monthly + 4 * $quarterly + 2 * $semiannually), $cur->getBaseCurrency());?>
                    )
                </td>
            </tr>
        </table>
    </div>
<?php }?>