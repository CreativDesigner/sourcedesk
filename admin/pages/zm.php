<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['ZM'];

title($l['TITLE']);
menu("statistics");

if (!$ari->check(40) || !$CFG['TAXES']) {require __DIR__ . "/error.php";if (!$ari->check(40)) {
    alog("general", "insufficient_page_rights", "zm");
}
} else {
    ?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?></h1>
    </div>
</div>

<?php if (isset($_POST['from']) && strtotime($_POST['from']) !== false && isset($_POST['until']) && strtotime($_POST['until']) !== false) {
        $statusReq = "1" . ($CFG['TAX_WISE'] == "soll" ? ",0" : "");
        $sql = $db->query("SELECT ID, client_data FROM invoices WHERE date >= '" . date("Y-m-d", strtotime($_POST['from'])) . "' AND date <= '" . date("Y-m-d", strtotime($_POST['until'])) . "' AND status IN ($statusReq)");
        $ust = array();

        while ($row = $sql->fetch_object()) {
            $d = unserialize($row->client_data);
            if ($d === false || !isset($d['ptax']) || $d['ptax'] != "reverse_vatid" || empty($d['vatid'])) {
                continue;
            }

            $i = new Invoice();
            $i->load($row->ID);
            if (isset($ust[$d['vatid']])) {
                $ust[$d['vatid']] += $i->getAmount();
            } else {
                $ust[$d['vatid']] = $i->getAmount();
            }

        }

        alog("tax", "zm_generated", $_POST['from'], $_POST['until']);

        ?>
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th><?=$l['ID'];?></th>
				<th><?=$l['AMOUNT'];?></th>
				<th><?=$l['ROUNDED'];?></th>
			</tr>
			<?php if (count($ust) == 0) {?>
			<tr>
				<td colspan="3"><center><?=$l['NT'];?></center></td>
			</tr>
			<?php } else { $sum = $sum_2 = 0;foreach ($ust as $i => $a) {$sum += $a;
            $sum_2 += round($a);?>
			<tr>
				<td><?=$i;?></td>
				<td><?=$cur->infix($nfo->format($a), $cur->getBaseCurrency());?></td>
				<td><?=$cur->infix($nfo->format(round($a)), $cur->getBaseCurrency());?></td>
			</tr>
			<?php }}if (isset($sum)) {?>
			<tr>
				<th style="text-align: right;">Summe</th>
				<th><?=$cur->infix($nfo->format($sum), $cur->getBaseCurrency());?></th>
				<th><?=$cur->infix($nfo->format($sum_2), $cur->getBaseCurrency());?></th>
			</tr>
			<?php }?>
		</table>
	</div>
	<?php
} else {?>

<?=$l['INTRO'];?>
<br /><br />
<form accept-charset="UTF-8" role="form" method="post">
    <fieldset>
        <div class="form-group">
            <div class="row">
                <div class="col-xs-6">
                    <div class="input-group" style="position: relative;">
                        <span class="input-group-addon"><?=$l['START'];?></span>
                        <input type="text" class="form-control datepicker" name="from" placeholder="<?=$dfo->placeholder(false);?>">
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="input-group" style="position: relative;">
                        <span class="input-group-addon"><?=$l['END'];?></span>
                        <input type="text" class="form-control datepicker" name="until" placeholder="<?=$dfo->placeholder(false);?>">
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
    <input type="submit" value="<?=$l['DO'];?>" class="btn btn-primary btn-block" />
</form>

<?php }}?>