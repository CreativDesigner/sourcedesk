<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['PAYMENTS'];
title($l['TITLE']);
menu("payments");

if (!$ari->check(33) || !$gateways->get()['transfer']->isActive()) {require __DIR__ . "/error.php";if (!$ari->check(33)) {
    alog("general", "insufficient_page_rights", "payments");
}
} else {

    $tab = isset($_GET['tab']) ? $_GET['tab'] : "open";

    ?>

	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>
		<div class="row">
		<div class="col-md-3">
			<div class="list-group">
				<a class="list-group-item<?=$tab == "open" ? " active" : "";?>" href="./?p=payments"><?=$l['TAB1'];?></a>
				<a class="list-group-item<?=$tab == "done" ? " active" : "";?>" href="./?p=payments&tab=done"><?=$l['TAB2'];?></a>
				<a class="list-group-item<?=$tab == "upload" ? " active" : "";?>" href="./?p=payments&tab=upload"><?=$l['TAB3'];?></a>
				<a class="list-group-item<?=$tab == "accounts" ? " active" : "";?>" href="./?p=payments&tab=accounts"><?=$l['TAB4'];?></a>
			</div>

			<div class="list-group">
				<a class="list-group-item<?=$tab == "input" ? " active" : "";?>" href="./?p=payments&tab=input"><?=$l['TAB5'];?></a>
			</div>
		</div>

		<div class="col-md-9">

		<?php if ($tab == "accounts") {
        if (isset($_GET['d']) && $db->query("SELECT 1 FROM payment_accounts WHERE ID = " . intval($_GET['d']))->num_rows == 1) {
            $db->query("DELETE FROM payment_accounts WHERE ID = " . intval($_GET['d']));
            alog("payments", "account_deleted", $_GET['d']);
            echo '<div class="alert alert-success">' . $l['ACCDELETED'] . '</div>';
        }

        if (isset($_POST['bank'])) {
            try {
                if (!array_key_exists($_POST['bank'], BankCSV::getAvailableBanks(true, true))) {
                    throw new Exception($l['ERR1']);
                }

                if (empty($_POST['account'])) {
                    throw new Exception($l['ERR2']);
                }

                $db->query("INSERT INTO payment_accounts (`bank`, `account`) VALUES ('" . $db->real_escape_string($_POST['bank']) . "', '" . $db->real_escape_string($_POST['account']) . "')");

                alog("payments", "account_added", $_POST['bank'], $_POST['account']);

                unset($_POST);
                echo '<div class="alert alert-success">' . $l['ACCCREATED'] . '</div>';
            } catch (Exception $ex) {
                echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }

        if (isset($_POST)) {
            foreach ($_POST as $k => $v) {
                if (substr($k, 0, 5) == "save_") {
                    unset($_POST[$k]);
                    $i = array_pop(explode("_", $k));
                    if ($db->query("SELECT 1 FROM payment_accounts WHERE ID = " . intval($i))->num_rows != 1) {
                        break;
                    }

                    $d = $_POST;
                    foreach ($d as $k => &$v) {
                        unset($_POST[$k]);
                        $v = base64_encode($v);
                    }
                    $db->query("UPDATE payment_accounts SET credentials = '" . $db->real_escape_string(encrypt(serialize($d))) . "' WHERE ID = " . intval($i));
                    alog("payments", "account_changed", $i);
                    echo '<div class="alert alert-success">' . $l['ACCSAVED'] . '</div>';
                    break;
                }
            }
        }
        ?>

		<form class="form-inline" method="POST">
		  <div class="form-group">
		    <label class="sr-only"><?=$l['BANK'];?></label>
		    <select class="form-control" name="bank">
		    	<option value="" selected="selected" disabled="disabled"><?=$l['PCB'];?></option>
		    	<?php foreach (BankCSV::getAvailableBanks(true, true) as $k => $n) {?>
		    	<option value="<?=$k;?>"<?=isset($_POST['bank']) && $_POST['bank'] == $k ? ' selected="selected"' : "";?>><?=$n;?></option>
		    	<?php }?>
		    </select>
		    <label class="sr-only"><?=$l['ACCNR'];?></label>
		    <input type="text" name="account" class="form-control" placeholder="<?=$l['ACCNR'];?>" value="<?=isset($_POST['account']) ? $_POST['account'] : "";?>">
		  	<button type="submit" class="btn btn-primary"><?=$l['ACCCREATE'];?></button>
		  </div>
		</form>

		<br />
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th><?=$l['BANK'];?></th>
					<th><?=$l['ACCNR'];?></th>
					<th><?=$l['CREDIT'];?></th>
					<th><?=$l['AUTOMATION'];?></th>
					<th width="20px"></th>
				</tr>

				<?php
$sql = $db->query("SELECT * FROM payment_accounts");
        if ($sql->num_rows == 0) {?>
				<tr>
					<td colspan="5"><center><?=$l['ACCNT'];?></center></td>
				</tr>
				<?php } else { $b = 0;while ($row = $sql->fetch_object()) {$b += $row->balance;?>
				<tr>
					<td><?=BankCSV::getAvailableBanks(true, true)[$row->bank];?></td>
					<td><?=$row->account;?></td>
					<td><?=number_format($row->balance, 2, ',', '.');?> &euro;</td>
					<td><?php if (!class_exists("BankCSV_" . ucfirst(strtolower($row->bank))) || !method_exists("BankCSV_" . ucfirst(strtolower($row->bank)), "automationSettings")) {
            ?><?=$l['NA'];?><?php
} else {?>
					<a href="#" class="refresh-balance" data-id="<?=$row->ID;?>"><i class="fa fa-refresh"></i></a>
					<a href="#" data-toggle="modal" data-target="#automate_<?=$row->ID;?>"><?=$l['SETTINGS'];?></a>

					<div class="modal fade" id="automate_<?=$row->ID;?>" tabindex="-1">
					  <div class="modal-dialog" role="document">
					    <div class="modal-content">
					      <div class="modal-header">
					        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
					        <h4 class="modal-title"><?=$l['AUTOMATION'];?></h4>
					      </div>
					      <form method="POST"><div class="modal-body">
						  	<input style="opacity: 0;position: absolute;">
        					<input type="password" autocomplete="new-password" style="display: none;">

					        <?php
$c = unserialize(decrypt($row->credentials)) !== false ? (object) unserialize(decrypt($row->credentials)) : array();
            foreach ($c as &$a) {
                $a = base64_decode($a);
            }

            $class = "BankCSV_" . ucfirst(strtolower($row->bank));
            $method = "automationSettings";

            foreach ($class::$method() as $n) {
                $t = $n;
                $n = str_replace(" ", "_", $n);

                $type = in_array($t, ["Passwort", "Online-Passwort", "API-Token", "HBCI-PIN"]) ? "password" : "text";
                ?>
					        <div class="form-group">
					        	<label><?=$t;?></label>
					        	<input type="<?=$type;?>" class="form-control" name="<?=$n;?>" value="<?=isset($c->$n) ? $c->$n : "";?>" />
					        </div>
					        <?php
}
            ?>

					        <div class="form-group">
					        	<label><?=$l['COPY'];?></label>
					        	<input type="text" class="form-control" name="copy" value="<?=isset($c->copy) ? $c->copy : "";?>" placeholder="<?=$l['COPYP'];?>" />
					        </div>

					        <div class="checkbox">
					        	<label>
					        		<input type="checkbox" name="copy_wait" value="1"<?=isset($c->copy_wait) && $c->copy_wait ? ' checked="checked"' : '';?> /> <?=$l['COPYCHECK'];?>
					        	</label>
					        </div>
					      </div>
					      <div class="modal-footer">
					        <button type="reset" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
					        <button type="submit" name="save_<?=$row->ID;?>" class="btn btn-primary"><?=$l['SAVE'];?></button>
					      </div></form>
					    </div>
					  </div>
					</div>
					<?php }?></td>
					<td><a href="?p=payments&tab=accounts&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
				</tr>
				<?php }?>
				<tr>
					<th colspan="2" style="text-align: right"><?=$l['SUM'];?></th>
					<th colspan="3"><?=number_format($b, 2, ',', '.');?> &euro;</th>
				</tr>
				<?php }?>
			</table>
		</div>

		<script>
		$(".refresh-balance").click(function(e){
			e.preventDefault();
			var i = $(this).find("i");
			i.addClass("fa-spin");
			$.get("?p=ajax&action=refresh_balance&id=" + $(this).data("id"), function(r){
				i.removeClass("fa-spin");
			});
		});
		</script>

		<?php } else if ($tab == "open") {

        if (isset($_GET['ban_id'])) {
            $db->query("UPDATE csv_import SET done = -1 WHERE done = 0 AND ID = '" . $db->real_escape_string($_GET['ban_id']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['SUC1'];
                alog("payments", "transaction_hidden", $_GET['ban_id']);
            }
        }

        if (isset($_POST['hide_selected']) && is_array($_POST['trans'])) {
            $d = 0;
            foreach ($_POST['trans'] as $id) {
                $db->query("UPDATE csv_import SET done = -1 WHERE done = 0 AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("payments", "transaction_hidden", $id);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC2'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC2X']);
            }

        }

        if (isset($_GET['delete_id'])) {
            $db->query("DELETE FROM csv_import WHERE done = 0 AND ID = '" . $db->real_escape_string($_GET['delete_id']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $suc = $l['SUC3'];
                alog("payments", "transaction_delete", $_GET['delete_id']);
            }
        }

        if (isset($_POST['delete_selected']) && is_array($_POST['trans'])) {
            $d = 0;
            foreach ($_POST['trans'] as $id) {
                $db->query("DELETE FROM csv_import WHERE done = 0 AND ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("payments", "transaction_delete", $id);
                }
            }

            if ($d == 1) {
                $suc = $l['SUC4'];
            } else if ($d > 0) {
                $suc = str_replace("%d", $d, $l['SUC4X']);
            }

        }

        ?>

		<?=isset($suc) ? '<div class="alert alert-success">' . $suc . '</div>' : "";?>

		<?php
$t = new Table("SELECT * FROM csv_import WHERE done = 0", [
            "subject" => [
                "name" => $l['SUBJECT'],
                "type" => "like",
            ],
            "sender" => [
                "name" => $l['SENDER'],
                "type" => "like",
            ],
        ], ["ID", "DESC"], "csv_import_0");

        echo $t->getHeader();
        ?>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th><?=$t->orderHeader("time", $l['DATE']);?></th>
					<th><?=$t->orderHeader("transactionId", $l['TID']);?></th>
					<th><?=$t->orderHeader("subject", $l['SUBJECT']);?></th>
					<th><?=$t->orderHeader("sender", $l['SENDER']);?></th>
					<th><?=$t->orderHeader("amount", $l['AMOUNT']);?></th>
					<th width="60px"></th>
				</tr>

				<form method="POST">
				<?php
$undoneSql = $t->qry("ID DESC");

        if ($undoneSql->num_rows > 0) {
            while ($trans = $undoneSql->fetch_object()) {
                ?>
					<tr>
						<td><input type="checkbox" class="checkbox" name="trans[]" value="<?=$trans->ID;?>" onchange="javascript:toggle();" /></td>
						<td><?=$dfo->format($trans->time, false);?></td>
						<td><?=$trans->transactionId;?></td>
						<td><?=$trans->subject;?></td>
						<td><?=$trans->sender;?></td>
						<td><font color="green">+ <?=$cur->infix($nfo->format($trans->amount), $cur->getBaseCurrency());?></font></td>
						<td width="60px"><a href="./?p=correct_payment&id=<?=$trans->ID;?>"><i class="fa fa-arrow-circle-o-right"></i></a>&nbsp;<a href="./?p=payments&tab=open&ban_id=<?=$trans->ID;?>"><i class="fa fa-ban"></i></a>&nbsp;<a href="./?p=payments&tab=open&delete_id=<?=$trans->ID;?>"><i class="fa fa-times"></i></a></td>
					</tr>
					<?php }
        } else {
            ?>
				<tr>
					<td colspan="7"><center><?=$l['UTNT'];?></center></td>
				</tr>
				<?php }?>
			</table>
		</div><?=$l['SELECTED'];?>: <input type="submit" name="hide_selected" value="<?=$l['HIDE'];?>" class="btn btn-warning" /> <input type="submit" name="delete_selected" value="<?=$l['DEL'];?>" class="btn btn-danger" /><br /><br /></form>

		<?php echo $t->getFooter();} else if ($tab == "done") { ?>

		<?php
$t = new Table("SELECT * FROM csv_import WHERE done = 1", [
        "subject" => [
            "name" => $l['SUBJECT'],
            "type" => "like",
        ],
        "sender" => [
            "name" => $l['SENDER'],
            "type" => "like",
        ],
    ], ["ID", "DESC"], "csv_import_1");

        echo $t->getHeader();
        ?>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th><?=$t->orderHeader("time", $l['DATE']);?></th>
					<th><?=$t->orderHeader("transactionId", $l['TID']);?></th>
					<th><?=$t->orderHeader("subject", $l['SUBJECT']);?></th>
					<th><?=$t->orderHeader("sender", $l['SENDER']);?></th>
					<th><?=$t->orderHeader("amount", $l['AMOUNT']);?></th>
					<th><?=$l['CUST'];?></th>
				</tr>

				<?php
$doneSql = $t->qry("ID DESC");

        if ($doneSql->num_rows > 0) {
            while ($trans = $doneSql->fetch_object()) {
                $customerSql = $db->query("SELECT ID FROM clients WHERE ID = " . $trans->clientId);
                if ($customerSql->num_rows == 1) {$cusInfo = $customerSql->fetch_object();
                    $cus = "<a href=\"./?p=customers&edit=" . $trans->clientId . "\">" . User::getInstance($cusInfo->ID, "ID")->getfName() . "</a>";} else { $cus = "<i>{$l['UK']}</i>";}

                ?>
					<tr>
						<td><?=$dfo->format($trans->time, false);?></td>
						<td><?=$trans->transactionId;?></td>
						<td><?=$trans->subject;?></td>
						<td><?=$trans->sender;?></td>
						<td><font color="green">+ <?=$cur->infix($nfo->format($trans->amount), $cur->getBaseCurrency());?></font></td>
						<td><?=$cus;?></td>
					</tr>
					<?php }
        } else {
            ?>
				<tr>
					<td colspan="6"><center><?=$l['DTNT'];?></center></td>
				</tr>
				<?php }?>
			</table>
		</div>

		<?php echo $t->getFooter();} else if ($tab == "upload") {

        if ($_POST['upload']) {
            $bank = trim($_POST['bank']);
            $file = $_FILES['csv']['tmp_name'];
            $size = $_FILES['csv']['size'];

            try {
                $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($bank) . "' WHERE `key` = 'csv_import' LIMIT 1");
                $CFG['CSV_IMPORT'] = $bank;

                if ($size > 512000) {
                    throw new Exception($l['UERR1']);
                }

                $handle = fopen($file, "r");
                if (!$handle) {
                    throw new Exception($l['UERR2']);
                }

                $res = CSVImport::doImport($handle, $bank);
                if (!$res) {
                    throw new Exception($l['UERR3']);
                }

                $done = $res["done"]["count"];
                $doneAmount = $res["done"]["amount"];
                $undone = $res["undone"]["count"];
                $undoneAmount = $res["undone"]["amount"];

                fclose($handle);

                alog("payments", "csv_upload");

                ?><div class="alert alert-success"><?=$l['USUC'];?></div><?php
} catch (BankCSV_Exception $bankExc) {
                echo "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $bankExc->getErrorDescription() . " [" . $bankExc->getErrorCode() . "]</div>";
            } catch (Exception $ex) {
                echo "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
            }
        }

        ?>
		<p style="text-align:justify; padding-bottom:5px;"><?=$l['IMPORTINTRO'];?></p>
		<form method="POST" enctype="multipart/form-data">
			<select name="bank" class="form-control">
				<?php
$availableBanks = BankCSV::getAvailableBanks();
        foreach ($availableBanks as $k => $name) {
            if ($CFG['CSV_IMPORT'] == $k) {
                echo "<option value=\"$k\" selected=\"selected\">$name</option>";
            } else {
                echo "<option value=\"$k\">$name</option>";
            }

        }
        ?>
			</select><br />
			<input type="file" name="csv" class="form-control" /><br />
			<input type="submit" name="upload" value="<?=$l['DOIMPORT'];?>" class="btn btn-primary btn-block">
			<br /><br />
		</form>

		<?php } else if ($tab == "input") {

        $table = "";
        $txtarea = "";
        if (isset($_POST['einbuchen'])) {
            $date = strtotime($_POST['date']);
            if (!$date) {
                $date = time();
            }
            $date = date("Y-m-d", $date);

            if ($_POST['type'] == "cust") {
                $csv = $_POST['customer'] . ";" . $_POST['amount'] . ";" . $date;
                $res = CSVImport::manualImport($csv);
            } else if ($_POST['type'] == "cashbox") {
                $csv = $_POST['cashbox_hash'] . ";" . $_POST['amount'] . ";" . $date;
                $res = CSVImport::manualImport($csv);
            } else if ($_POST['type'] == "inv") {
                $cid = 0;
                $inv = new Invoice;
                if ($inv->load($_POST['invoice'])) {
                    $cid = $inv->getClient();

                    if ($cid) {
                        $csv = $cid . ";" . $_POST['amount'] . ";" . $date;

                        $res = CSVImport::manualImport($csv);
                    } else {
                        if ($_POST['amount'] <= $inv->getOpenAmount() && $inv->getStatus() == 0) {
                            $inv->setPaidAmount($inv->getPaidAmount() + $_POST['amount']);
                            if ($inv->getOpenAmount() <= 0) {
                                $inv->setStatus(1);
                            }

                            $inv->save();

                            $status = "ok";
                        } else {
                            $status = $l['TOOHIGH'];
                        }

                        $res = [[
                            "status" => $status,
                            "amount" => $nfo->phpize($_POST['amount']),
                            "user" => $l['EXTINV'] . " " . $inv->getInvoiceNo(),
                        ]];
                    }
                }
            }

            if (is_array($res) && count($res) > 0) {
                $importedValue = 0;

                foreach ($res as $importTransaction) {
                    switch ($importTransaction["status"]) {
                        case "invalid_amount":
                            $erg = "<font color=\"red\">{$l['MERR1']}</font>";
                            $amount = $importTransaction["amount"];
                            break;

                        case "invalid_user":
                            $erg = "<font color=\"red\">{$l['MERR2']}</font>";
                            $amount = $cur->infix($nfo->format($importTransaction['amount']), $cur->getBaseCurrency());
                            break;

                        case "ok":
                            $erg = "<font color=\"green\">{$l['MSUC']}</font>";
                            $importedValue += $importTransaction["amount"];
                            $amount = $cur->infix($nfo->format($importTransaction['amount']), $cur->getBaseCurrency());
                            unset($_POST);
                            break;

                        default:
                            $erg = "<font color=\"red\">{$importTransaction['status']}</font>";
                            $amount = $cur->infix($nfo->format($importTransaction['amount']), $cur->getBaseCurrency());
                            break;
                    }

                    $table .= "<tr><td>" . $importTransaction["user"] . "</td><td>" . $amount . "</td><td>" . $erg . "</td></tr>";
                }

                if ($table != "") {
                    $table .= "<tr><td colspan='2'><b>{$l['MSUM']}</b></td><td>" . $cur->infix($nfo->format($importedValue), $cur->getBaseCurrency()) . "</tr>";
                    alog("payments", "manual_import", $importedValue);
                }
            }
        }

        ?>

<?=isset($error) ? "<div class='alert alert-danger'>$error</div>" : "";?>

<?php if ($table != "") {?><div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th><?=$l['CUST'];?></th>
		<th><?=$l['AMOUNT'];?></th>
		<th><?=$l['RES'];?></th>
	</tr>
	<?=$table;?>
</table></div>
<?php }?>

<form accept-charset="UTF-8" role="form" method="post">
      <fieldset>
      <div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tr>
						<td width="30%"><b><?=$l['AMOUNT'];?></b></td>
						<td><input style="max-width: 160px;" type="text" name="amount" value="<?=isset($_POST['amount']) ? htmlentities($_POST['amount']) : "";?>" class="form-control"></td>
					</tr>

					<tr>
						<td width="30%"><b><?=$l['DATE'];?></b></td>
						<td style="position: relative;"><input style="max-width: 160px;" type="text" name="date" value="<?=isset($_POST['date']) ? htmlentities($_POST['date']) : $dfo->format(time(), "", false, false);?>" class="form-control datepicker"></td>
					</tr>

					<tr>
						<td><b><?=$lang['CORRECT_PAYMENT']['TYPE'];?></b></td>
						<td>
							<label class="radio-inline">
								<input type="radio" name="type" value="cust"<?=!isset($_POST['type']) || $_POST['type'] == "cust" ? ' checked=""' : '';?>> <?=$lang['CORRECT_PAYMENT']['CUST'];?>
							</label>
							<label class="radio-inline">
								<input type="radio" name="type" value="inv"<?=isset($_POST['type']) && $_POST['type'] == "inv" ? ' checked=""' : '';?>> <?=$lang['CORRECT_PAYMENT']['INV'];?>
							</label>
                            <label class="radio-inline">
								<input type="radio" name="type" value="cashbox"<?=isset($_POST['type']) && $_POST['type'] == "cashbox" ? ' checked=""' : '';?>> <?=$lang['CORRECT_PAYMENT']['CASHBOX'];?>
							</label>
						</td>
					</tr>

					<script>
					$("[name=type]").click(function() {
						$("#choose_inv").hide();
						$("#choose_cust").hide();
						$("#choose_cashbox").hide();

						$("[name=type]").each(function() {
							if ($(this).is(":checked")) {
								$("#choose_" + $(this).val()).show();
							}
						});
					});
					</script>

					<tr id="choose_cust" style="background-color: #fff;<?=isset($_POST['type']) && ($_POST['type'] == "inv" || $_POST['type'] == "cashbox") ? ' display: none;' : '';?>">
						<td style="background-color: #f9f9f9;"><b><?=$lang['CORRECT_PAYMENT']['CUST'];?></b></td>
						<td style="background-color: #f9f9f9;">
							<div>
								<input type="text" class="form-control customer-input" placeholder="<?=$lang['CORRECT_PAYMENT']['CUSTP'];?>" value="<?=ci(!empty($_POST['customer']) ? $_POST['customer'] : "0");?>">
								<input type="hidden" name="customer" value="<?=!empty($_POST['customer']) ? $_POST['customer'] : "0";?>">
								<div class="customer-input-results"></div>
							</div>
						</td>
					</tr>

					<tr id="choose_inv"<?=!isset($_POST['type']) || $_POST['type'] == "cust" || $_POST['type'] == "cashbox" ? ' style="display: none;"' : '';?>>
						<td style="background-color: #f9f9f9;"><b><?=$lang['CORRECT_PAYMENT']['INV'];?></b></td>
						<td style="background-color: #f9f9f9;">
							<div>
								<input type="text" class="form-control invoice-input" placeholder="<?=$lang['CORRECT_PAYMENT']['SEARCHINV'];?>" value="<?=ii(!empty($_POST['invoice']) ? $_POST['invoice'] : "0");?>">
								<input type="hidden" name="invoice" value="<?=!empty($_POST['invoice']) ? $_POST['invoice'] : "0";?>">
								<div class="invoice-input-results"></div>
							</div>
						</td>
					</tr>

                    <tr id="choose_cashbox"<?=!isset($_POST['type']) || $_POST['type'] == "cust" || $_POST['type'] == "inv" ? ' style="display: none;"' : '';?>>
						<td style="background-color: #f9f9f9;"><b><?=$lang['CORRECT_PAYMENT']['CASHBOX_HASH'];?></b></td>
						<td style="background-color: #f9f9f9;">
                            <input type="text" class="form-control" name="cashbox_hash" value="<?=ii(!empty($_POST['cashbox_hash']) ? $_POST['cashbox_hash'] : "");?>">
						</td>
					</tr>
				</table>
			</div>
		<div class="form-group">
          <button type="submit" name="einbuchen" class="btn btn-primary btn-block">
            <?=$l['DOM'];?>
          </button>
        </div>
      </fieldset>
    </form>

</div></div></div></div><?php } else {?>

<div class="alert alert-danger"><?=$l['PNF'];?></div>

<?php }}?>
