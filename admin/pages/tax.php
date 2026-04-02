<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['TAX'];

title($l['TITLE']);
menu("statistics");

if (!$ari->check(40) || !$CFG['TAXES']) {require __DIR__ . "/error.php";if (!$ari->check(40)) {
    alog("general", "insufficient_page_rights", "tax");
}
} else {

    $countries = array();
    $sql = $db->query("SELECT ID, name FROM client_countries WHERE active = 1 AND percent > 0 ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        $countries[$row->ID] = $row->name;
    }

    $tab = isset($_GET['t']) ? $_GET['t'] : "monthly";
    $country = isset($_GET['c']) && isset($countries[$_GET['c']]) ? $_GET['c'] : $CFG['DEFAULT_COUNTRY'];

    ?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?></h1>
    </div>
</div>

<div class="row">
	<div class="col-md-3">
		<form method="GET">
			<input type="hidden" name="p" value="tax" />
			<input type="hidden" name="t" value="<?=$tab;?>" />
			<select name="c" onchange="form.submit();" class="form-control">
				<option value="0" disabled="disabled"><?=$l['CC'];?></option>
				<?php foreach ($countries as $id => $name) {
        echo '<option value="' . $id . '"' . ($country == $id ? ' selected="selected"' : '') . '>' . $name . '</option>';
    }
    ?>
			</select>
		</form><br />

		<div class="list-group">
			<a class="list-group-item<?=$tab == 'monthly' ? ' active' : '';?>" href="./?p=tax&c=<?=$country;?>"><?=$l['MONTHLY'];?></a>
			<a class="list-group-item<?=$tab == 'yearly' ? ' active' : '';?>" href="./?p=tax&t=yearly&c=<?=$country;?>"><?=$l['YEARLY'];?></a>
			<a class="list-group-item<?=$tab == 'deduct' ? ' active' : '';?>" href="./?p=tax&t=deduct&c=<?=$country;?>"><?=$l['DEDUCT'];?></a>
		</div>
	</div>

	<div class="col-md-9">
		<?php if ($tab == "monthly" || $tab == "yearly") {
        if ($tab == "monthly") {
            $month = isset($_GET['m']) && is_numeric($_GET['m']) && $_GET['m'] > 0 && $_GET['m'] <= 12 ? $_GET['m'] : ltrim(date("m"), "0");
        }

        $year = isset($_GET['y']) && is_numeric($_GET['y']) && $_GET['y'] >= 12 && $_GET['y'] <= date("Y") ? $_GET['y'] : date("Y");

        if (isset($month) && $year == date("Y") && $month > date("m")) {
            $month = ltrim(date("m"), "0");
        }

        $first = mktime(0, 0, 0, isset($month) ? $month : 1, 1, $year);
        $firstLast = mktime(0, 0, 0, 12, 1, $year);
        $last = mktime(0, 0, 0, isset($month) ? $month : 12, date("t", isset($month) ? $first : $firstLast), $year);

        $invoices = 0;

        $statusReq = "1" . ($CFG['TAX_WISE'] == "soll" ? ",0" : "");
        $sql = $db->query("SELECT ID FROM invoices WHERE date >= '" . date("Y-m-d", $first) . "' AND date <= '" . date("Y-m-d", $last) . "' AND status IN ($statusReq)");
        while ($row = $sql->fetch_object()) {
            $obj = new Invoice;
            $obj->load($row->ID);

            if ($obj->getCountry() != $country) {
                continue;
            }

            $invoices += $obj->getTaxAmount();
        }

        $deduct = 0;
        ?>
			<form method="GET" class="form-inline">
				<input type="hidden" name="p" value="tax" />
				<input type="hidden" name="t" value="<?=$tab;?>" />
				<input type="hidden" name="c" value="<?=$country;?>" />

				<?php if ($tab == "monthly") {?><select name="m" class="form-control">
					<?php for ($i = 1; $i <= 12; $i++) {$long = $i;while (strlen($long) < 2) {
            $long = "0" . $long;
        }
            ?>
					<option value="<?=$i;?>"<?=$i == $month ? ' selected="selected"' : '';?>><?=$long;?></option>
					<?php }?>
				</select><?php }?>

				<select name="y" class="form-control">
					<?php $sql = $db->query("SELECT YEAR(`date`) as year FROM invoices GROUP BY YEAR(`date`) ORDER BY YEAR(`date`) ASC");while ($row = $sql->fetch_object()) {?>
					<option<?=$year == $row->year ? ' selected="selected"' : '';?>><?=$row->year;?></option>
					<?php }?>
				</select>

				<input type="submit" class="btn btn-primary" value="<?=$l['GENERATE'];?>" />
			</form>
			<br />
			<?php if (isset($_GET['d']) && is_numeric($_GET['d'])) {
            $db->query("DELETE FROM tax_deduct WHERE ID = " . intval($_GET['d']) . " LIMIT 1");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['DEDEL'] . '</div>';
                alog("tax", "deduct_delete", $_GET['d']);
            }
        }?>

			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tr>
						<th><?=$l['TH1'];?></th>
						<td width="30%"><font color="red"><?=$cur->infix($nfo->format($invoices), $cur->getBaseCurrency());?></font></td>
					</tr>

					<?php
$sql = $db->query("SELECT ID, description, amount FROM tax_deduct WHERE time >= $first AND time <= $last AND country = $country ORDER BY time ASC, ID ASC");
        while ($row = $sql->fetch_object()) {$deduct += abs($row->amount);
            ?>
					<tr>
						<td><?=$row->description;?> <a href="?p=tax&t=<?=$tab;?>&c=<?=$country;?><?=isset($month) ? '&m=' . $month : "";?>&y=<?=$year;?>&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						<td width="30%"><font color="green"><?=$cur->infix($nfo->format($row->amount), $cur->getBaseCurrency());?></font></td>
					</tr>
					<?php }if ($sql->num_rows == 0) {?>
					<tr>
						<td colspan="2"><center><?=$l['DENT'];?></center></td>
					</tr>
					<?php }?>

					<?php $sum = $invoices - $deduct;?>
					<tr>
						<th><?=$l['SUMFOR'];?> <?=isset($month) ? $month . "." : "";?><?=$year;?></th>
						<th width="30%">
							<font color="<?=$sum > 0 ? 'red' : 'green';?>"><?=$cur->infix($nfo->format(abs($sum)), $cur->getBaseCurrency());?></font>
						</th>
					</tr>
				</table>
			</div>
		<?php } else {
        if (isset($_POST['time'])) {
            try {
                $time = strtotime($_POST['time']);
                if (!is_numeric($time)) {
                    throw new Exception($l['ERR1']);
                }

                if (empty($_POST['description'])) {
                    throw new Exception($l['ERR2']);
                }

                $amount = $nfo->phpize($_POST['amount']);
                if ((!is_numeric($amount) && !is_double($amount)) || $amount <= 0) {
                    throw new Exception($l['ERR3']);
                }

                $db->query("INSERT INTO tax_deduct (`country`, `time`, `description`, `amount`) VALUES (" . intval($country) . ", " . intval($time) . ", '" . $db->real_escape_string($_POST['description']) . "', " . doubleval($amount) . ")");

                alog("tax", "deduct", $db->insert_id);

                echo "<div class='alert alert-success'>{$l['DESUC']}</div>";
                unset($_POST);
            } catch (Exception $ex) {
                echo "<div class='alert alert-danger'>{$ex->getMessage()}</div>";
            }
        }
        ?>
			<form method="POST">
			  <div class="form-group" style="position: relative;">
			    <label><?=$l['DATE'];?></label>
			    <input type="text" class="form-control datepicker" name="time" style="max-width: 150px;" placeholder="<?=$dfo->placeholder(false);?>" value="<?=isset($_POST['time']) ? $_POST['time'] : $dfo->placeholder(false);?>">
			  </div>
			  <div class="form-group">
			    <label><?=$l['DESCAMO'];?></label>
			    <div class="row">
			    	<div class="col-xs-8">
			    		<input type="text" class="form-control" name="description" placeholder="<?=$l['DESC'];?>" value="<?=isset($_POST['description']) ? $_POST['description'] : '';?>">
			    	</div>

			    	<div class="col-xs-4">
			    		<input type="text" class="form-control" name="amount" placeholder="<?=$nfo->placeholder();?>" value="<?=isset($_POST['amount']) ? $_POST['amount'] : '';?>">
			    	</div>
			  </div><br />
			  <button type="submit" class="btn btn-primary btn-block"><?=$l['DEDO'];?></button>
			</form>
		<?php }?>
	</div>
</div>

<?php }?>