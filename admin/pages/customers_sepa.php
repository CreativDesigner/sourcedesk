<?php
$l = $lang['SEPA'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(7)) {require __DIR__ . "/error.php";if (!$ari->check(7)) {
    alog("general", "insufficient_page_rights", "customers_sepa");
}
} else {
    $uI = false;
    if (isset($_GET['id'])) {
        $uI = User::getInstance($_GET['id'], "ID");
    }

    if (!$uI) {
        menu("payments");
    }

    if (isset($_GET['mandate']) && ($mandate = SepaDirectDebit::mandate($_GET['mandate'])) && is_object($uI = User::getInstance($mandate->getUser(), "ID"))) {

        if (isset($_GET['download'])) {
            alog("sepa", "mandate_download", $mandate->getID());

            if (count(glob(__DIR__ . "/../../files/sepa_mandates/" . $mandate->getID() . ".*")) == 1) {
                $file = glob(__DIR__ . "/../../files/sepa_mandates/" . $mandate->getID() . ".*")[0];

                $ex = explode(".", basename($file));
                array_shift($ex);

                header("Content-Type: application/file");
                header("Content-Disposition: attachment; filename=\"{$l['MANDATE']}." . implode(".", $ex) . "\"");
                readfile($file);
                exit;
            } else {
                $mandate->downloadPdf();
                exit;
            }
        }
        ?>
<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['MANDATE2'];?> <small><?=$mandate->getID();?> (<a href="?p=customers&edit=<?=$uI->get()['ID'];?>"><?=$uI->getfName();?></a>)</small></h1>

		<?php if ($mandate->expired()) {?>
		<div class="alert alert-danger"><?=$l['ERREX'];?></div>
		<?php } else if (isset($_POST['action']) && $_POST['action'] == "payment") {
            $amount = $nfo->phpize($_POST['amount']);
            if ((!is_double($amount) && !is_numeric($amount)) || $amount <= 0) {
                echo '<div class="alert alert-danger">' . $l['ERR1'] . '</div>';
            } else if (!SepaDirectDebit::create($mandate, $amount)) {
                echo '<div class="alert alert-danger">' . $l['ERR2'] . '</div>';
            } else {
                if (isset($_POST['credit']) && $_POST['credit'] == "1") {
                    $uI->set(array("credit" => $uI->get()['credit'] + $amount));
                    $uI->applyCredit();
                }

                alog("sepa", "debit", $mandate->getID(), $amount);

                echo '<div class="alert alert-success">' . $l['SUC1'] . '</div>';
                unset($_POST);
            }
        }

        if (isset($_POST['action']) && $_POST['action'] == "save") {
            if (isset($_FILES['file'])) {
                $ex = explode(".", $_FILES['file']['name']);
                $ex[0] = $mandate->getID();
                $name = implode(".", $ex);
                move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . "/../../files/sepa_mandates/" . basename($name));
                alog("sepa", "mandate_uploaded", $mandate->getID(), basename($name));
            }

            try {
                foreach ($_POST as $k => $v) {
                    $vari = "pt" . strtolower($k);
                    $$vari = $v;
                }

                if (!isset($ptdate) || strtotime($ptdate) === false) {
                    throw new Exception($l['ERR3']);
                }

                if (!isset($ptstatus) || !in_array($ptstatus, array("0", "1", "2"))) {
                    throw new Exception($l['ERR4']);
                }

                if ($uI->get()['sepa_fav'] == $id) {
                    $uI->set(array("sepa_fav" => 0));
                }

                if ($ptstatus == 1 && $uI->get()['sepa_fav'] == 0) {
                    $uI->set(array("sepa_fav" => $mandate->getID()));
                }

                if ($ptstatus != 1 && $uI->get()['sepa_fav'] == $mandate->getID()) {
                    $uI->set(array("sepa_fav" => 0));
                }

                $sql = $db->prepare("UPDATE client_sepa SET iban = ?, bic = ?, date = ?, account_holder = ?, status = ? WHERE ID = ?");
                $sql->bind_param("ssssii", $ptiban, $ptbic, $a = date("Y-m-d", strtotime($ptdate)), $ptaccount_holder, $ptstatus, $b = $mandate->getID());
                $sql->execute();

                echo '<div class="alert alert-success">' . $l['SUC2'] . '</div>';
                unset($_POST);
                $mandate = SepaDirectDebit::mandate($mandate->getID());

                alog("sepa", "mandate_changed", $mandate->getID());
            } catch (Exception $ex) {
                echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }
        ?>

		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-primary">
					<div class="panel-heading">
						<?=$l['DET'];?>
					</div>
					<div class="panel-body">
						<form method="POST" enctype="multipart/form-data">
							<div class="form-group">
								<label><?=$l['DATE'];?></label>
								<input type="text" name="date" class="form-control datepicker" value="<?=isset($_POST['date']) ? $_POST['date'] : $dfo->format($mandate->getDate(), false);?>" placeholder="<?=$dfo->placeholder(false);?>">
							</div>

							<div class="form-group">
								<label><?=$l['STATUS'];?></label>
								<select name="status" class="form-control">
									<option value="0"><?=$l['S1'];?></option>
									<option value="1"<?=(isset($_POST['status']) ? $_POST['status'] : $mandate->getStatus()) == 1 ? ' selected="selected"' : '';?>><?=$l['S2'];?></option>
									<option value="2"<?=(isset($_POST['status']) ? $_POST['status'] : $mandate->getStatus()) == 2 ? ' selected="selected"' : '';?>><?=$l['S3'];?></option>
								</select>
							</div>

							<div class="form-group">
								<label><?=$l['AH'];?></label>
								<input type="text" name="account_holder" class="form-control" value="<?=isset($_POST['account_holder']) ? $_POST['account_holder'] : $mandate->getAccountHolder();?>" placeholder="<?=$l['AHP'];?>">
							</div>

							<div class="form-group">
								<label><?=$l['IBAN'];?></label>
								<input type="text" name="iban" class="form-control" value="<?=isset($_POST['iban']) ? $_POST['iban'] : $mandate->getIBAN();?>" placeholder="<?=$l['IBANP'];?>">
							</div>

							<div class="form-group">
								<label><?=$l['BIC'];?></label>
								<input type="text" name="bic" class="form-control" value="<?=isset($_POST['bic']) ? $_POST['bic'] : $mandate->getBIC();?>" placeholder="<?=$l['BICP'];?>">
							</div>

							<?php if (count(glob(__DIR__ . "/../../files/sepa_mandates/" . $mandate->getID() . ".*")) == 1) {?>
							<div class="form-group">
								<label><?=$l['MANDATE3'];?></label><br />
								<a href="?p=customers_sepa&id=<?=$_GET['id'];?>&mandate=<?=$_GET['mandate'];?>&download=1" target="_blank"><?=$l['DOWNLOAD'];?></a>
							</div>
							<?php } else {?>
							<div class="form-group">
								<label><?=$l['FORM'];?></label><br />
								<a href="?p=customers_sepa&id=<?=$_GET['id'];?>&mandate=<?=$_GET['mandate'];?>&download=1" target="_blank"><?=$l['DOWNLOAD'];?></a>
							</div>

							<div class="form-group">
								<label><?=$l['UPLOAD'];?></label>
								<input type="file" name="file" class="form-control" />
							</div>
							<?php }?>

							<input type="hidden" name="action" value="save" />
							<input type="submit" value="<?=$l['SAVE'];?>" class="btn btn-primary btn-block" />
						</form>
					</div>
				</div>
			</div>

			<div class="col-md-6">
				<?php if (!$mandate->expired()) {?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<?=$l['TRANSACT'];?>
					</div>
					<div class="panel-body">
						<form method="POST">
							<div class="form-group">
								<label><?=$l['TD'];?></label><br />
								<?=$dfo->format(SepaDirectDebit::getPaymentDate(), false);?>
							</div>

							<div class="form-group">
								<label><?=$l['ID'];?></label><br />
								<?=$mandate->getID() . "-" . ($mandate->getLastTID() + 1);?>
							</div>

							<div class="form-group">
								<label><?=$l['AM'];?></label>
								<div class="input-group">
									<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
									<input type="text" name="amount" value="<?=isset($_POST['amount']) ? $_POST['amount'] : "";?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
									<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
								</div>
							</div>

							<div class="checkbox">
								<label>
									<input type="checkbox" name="credit" value="1"<?php if (!isset($_POST['credit']) && isset($_POST['amount'])) {
            echo '';
        } else {
            echo ' checked="checked"';
        }
            ?>>
									<?=$l['APPLY'];?>
								</label>
							</div>

							<input type="hidden" name="action" value="payment" />
							<input type="submit" value="<?=$l['DOT'];?>" class="btn btn-default btn-block" />
						</form>
					</div>
				</div>
				<?php }?>

				<div class="panel panel-default">
					<div class="panel-heading">
						<?=$l['TRANSACTIONS'];?>
					</div>
					<div class="panel-body">
						<?php
$sql = $db->query("SELECT * FROM client_transactions WHERE subject LIKE 'sdd|" . intval($mandate->getID()) . "-%' ORDER BY time DESC, ID DESC");
        if ($sql->num_rows == 0) {
            echo '<i>' . $l['NT'] . '</i>';
        } else {
            echo '<ul class="list-group" style="margin-bottom: 0;">';
        }

        $sum = 0;while ($row = $sql->fetch_object()) {?>
						<li class="list-group-item"><?=explode("|", $row->subject)[1];?> <span class="label label-<?=$row->sepa_done ? "success" : "warning";?>"><?=$cur->infix($nfo->format($row->amount), $cur->getBaseCurrency());?></span><span class="pull-right text-muted small"><em><?=$dfo->format($row->time, false);?> / <?=$dfo->format(SepaDirectDebit::transaction($row->ID)->getDueDate(), false);?></em></span></li>
						<?php $sum += $row->amount;}
        if ($sql->num_rows > 0) {
            echo '</ul>';
        }

        if ($sum) {
            echo '<br /><b>' . $l['SUM'] . ':</b> ' . $cur->infix($nfo->format($sum), $cur->getBaseCurrency());
        }

        ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
} else {

        if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
            $d = 0;

            if (isset($_POST['mark_active'])) {
                foreach ($_POST['invoices'] as $id) {
                    $uid = $db->query("SELECT client FROM client_sepa WHERE ID = " . intval($id))->fetch_object()->client;
                    $db->query("UPDATE client_sepa SET status = 1 WHERE ID = " . intval($id));
                    if ($db->affected_rows) {
                        $d++;
                        $uI2 = User::getInstance($uid, "ID");
                        if ($uI2 && $uI2->get()['sepa_fav'] == "0") {
                            $uI2->set(array("sepa_fav" => $id));
                        }

                        alog("sepa", "mandate_activated", $id);
                    }
                }

                if ($d == 1) {
                    $msg = $l['A1'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['AX']);
                }

            } else if (isset($_POST['mark_inactive'])) {
                foreach ($_POST['invoices'] as $id) {
                    $uid = $db->query("SELECT client FROM client_sepa WHERE ID = " . intval($id))->fetch_object()->client;
                    $db->query("UPDATE client_sepa SET status = 2 WHERE ID = " . intval($id));
                    if ($db->affected_rows) {
                        $d++;
                        $uI2 = User::getInstance($uid, "ID");
                        if ($uI2 && $uI2->get()['sepa_fav'] == $id) {
                            $uI2->set(array("sepa_fav" => 0));
                        }

                        alog("sepa", "mandate_deactivated", $id);
                    }
                }

                if ($d == 1) {
                    $msg = $l['I1'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['IX']);
                }

            } else if (isset($_POST['mark_waiting'])) {
                foreach ($_POST['invoices'] as $id) {
                    $uid = $db->query("SELECT client FROM client_sepa WHERE ID = " . intval($id))->fetch_object()->client;
                    $db->query("UPDATE client_sepa SET status = 0 WHERE ID = " . intval($id));
                    if ($db->affected_rows) {
                        $d++;
                        $uI2 = User::getInstance($uid, "ID");
                        if ($uI2 && $uI2->get()['sepa_fav'] == $id) {
                            $uI2->set(array("sepa_fav" => 0));
                        }

                        alog("sepa", "mandate_waiting", $id);
                    }
                }

                if ($d == 1) {
                    $msg = $l['W1'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['WX']);
                }

            } else if (isset($_POST['delete'])) {
                foreach ($_POST['invoices'] as $id) {
                    $uid = $db->query("SELECT client FROM client_sepa WHERE ID = " . intval($id))->fetch_object()->client;
                    $db->query("DELETE FROM client_sepa WHERE ID = " . intval($id));
                    if ($db->affected_rows) {
                        $d++;
                        $uI2 = User::getInstance($uid, "ID");
                        if ($uI2 && $uI2->get()['sepa_fav'] == $id) {
                            $uI2->set(array("sepa_fav" => 0));
                        }

                        alog("sepa", "mandate_deleted", $id);
                    }
                }

                if ($d == 1) {
                    $msg = $l['R1'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['RX']);
                }

            }
        }

        if (isset($_POST['action']) && $_POST['action'] == "add" && $uI) {
            $sql = $db->prepare("INSERT INTO client_sepa (client, iban, bic, account_holder, date) VALUES (?,?,?,?,?)");
            $sql->bind_param("issss", $a = $uI->get()['ID'], $_POST['iban'], $_POST['bic'], $_POST['account_holder'], $b = date("Y-m-d"));
            $sql->execute();

            alog("sepa", "mandate_add", $iid = $db->insert_id, $uI->get()['ID']);

            header('Location: ?p=customers_sepa&id=' . $_GET['id'] . '&mandate=' . $iid);
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] == "limit" && $uI) {
            $limit = $nfo->phpize($_POST['limit']);
            if (is_double($limit) || is_numeric($limit)) {
                $uI->set(array("sepa_limit" => $limit));
                $msg = $l['DLS'];
                alog("sepa", "daily_limit", $uI->get()['ID'], $limit);
            }
        }

        if (isset($_GET['fav']) && ($m = SepaDirectDebit::mandate($_GET['fav'])) && $uI && $m->getUser() == $uI->get()['ID'] && $m->isActive()) {
            $uI->set(array("sepa_fav" => $m->getID()));
            alog("sepa", "fav", $m->getID(), $uI->get()['ID']);
        }
        ?>

<div class="row">
	<div class="col-md-12">
	<h1 class="page-header"><?=$l['TITLE'];?><?php if ($uI) {?> <small><a href="?p=customers&edit=<?=$uI->get()['ID'];?>"><?=$uI->getfName();?></a></small><?php }?></h1>

		<?php if (!empty($msg)) {?><div class="alert alert-success"><?=$msg;?></div><?php }?>

		<?php if ($uI) {?>
		<a href="#" data-toggle="modal" data-target="#add" class="btn btn-success"><?=$l['CNM'];?></a> <a href="#" data-toggle="modal" data-target="#limit" class="btn btn-default"><?=$l['SDL'];?></a><br /><br />

		<div class="modal fade" id="add" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content"><form method="POST">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title"><?=$l['CNM'];?></h4>
		      </div>
		      <div class="modal-body">
		      	<div class="form-group">
		      		<label><?=$l['AH'];?></label>
		      		<input type="text" name="account_holder" placeholder="<?=$l['AHP'];?>" value="<?=$uI->get()['company'] ?: $uI->get()['name'];?>" class="form-control" />
		      	</div>

		      	<div class="form-group">
		      		<label><?=$l['IBAN'];?></label>
		      		<input type="text" name="iban" placeholder="<?=$l['IBANP'];?>" value="" class="form-control" />
		      	</div>

		      	<div class="form-group">
		      		<label><?=$l['BIC'];?></label>
		      		<input type="text" name="bic" placeholder="<?=$l['BICP'];?>" value="" class="form-control" />
		      	</div>

		        <input type="hidden" name="action" value="add" />
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
		        <button type="submit" class="btn btn-primary"><?=$l['CNM'];?></button>
		      </div>
		    </form></div>
		  </div>
		</div>

		<div class="modal fade" id="limit" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content"><form method="POST">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title"><?=$l['DL'];?></h4>
		      </div>
		      <div class="modal-body">
		      	<div class="form-group">
		      		<label><?=$l['DL'];?></label>
		      		<input type="text" name="limit" placeholder="<?=$nfo->placeholder();?>" value="<?=$nfo->format($uI->get()['sepa_limit']);?>" class="form-control" />
		      	</div>

		        <input type="hidden" name="action" value="limit" />
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
		        <button type="submit" class="btn btn-primary"><?=$l['SAVES'];?></button>
		      </div>
		    </form></div>
		  </div>
		</div>
		<?php }?>

        <?php
$where = $uI ? "WHERE client = " . $uI->get()['ID'] : "";

        $t = new Table("SELECT * FROM client_sepa $where", [
            "status" => [
                "name" => $l['STATUS'],
                "type" => "select",
                "options" => [
                    "0" => $l['S1'],
                    "1" => $l['S2'],
                    "2" => $l['S5'],
                ],
            ],
        ], ["date", "DESC"], "client_sepa");

        echo $t->getHeader();
        ?>

		<form method="POST" id="invoice_form"><div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<?php if ($uI) {?><th width="30px"></th><?php }?>
					<th><?=$t->orderHeader("date", $l['DATE']);?></th>
					<?php if (!$uI) {?><th><?=$t->orderHeader("client", $l['CLIENT']);?></th><?php }?>
					<th><?=$t->orderHeader("ID", $l['REFERENCE']);?></th>
					<th><?=$t->orderHeader("account_holder", $l['AH']);?></th>
					<th><?=$t->orderHeader("iban", $l['IBAN']);?></th>
					<th><?=$t->orderHeader("status", $l['STATUS']);?></th>
					<th width="30px"></th>
				</tr>

				<?php
$sql = $t->qry("`date` DESC, ID DESC");
        while ($row = $sql->fetch_object()) {
            $mandate = SepaDirectDebit::mandate($row->ID);
            ?>
				<tr>
					<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$row->ID;?>" /></td>
					<?php if ($uI) {?><td><?php if ($mandate->isActive()) {?><a href="?p=customers_sepa&id=<?=$uI->get()['ID'];?>&fav=<?=$row->ID;?>"><?php }?><i class="fa fa-star<?=$uI->get()['sepa_fav'] == $row->ID ? '' : '-o';?>"<?=$uI->get()['sepa_fav'] == $row->ID ? ' style="color: rgb(234, 193, 23);"' : '';?>></i><?php if ($mandate->isActive()) {
                echo '</a>';
            }
                ?></td><?php }?>
					<td><?=$dfo->format($row->date, false);?></td>
		<?php if (!$uI && $tempUi = User::getInstance($row->client, "ID")) {?><td><a href="?p=customers&edit=<?=$row->client;?>"><?=$tempUi->getfName();?></a></td><?php } else if (!$uI) {?><td>-</td><?php }?>
					<td><?=$row->ID;?></td>
					<td><?=$row->account_holder;?></td>
					<td><?=$row->iban;?></td>
					<td>
						<?php
if ($mandate->expired()) {
                echo '<font color="red">' . $l['S4'] . '</font>';
            } else if ($row->status == 0) {
                echo '<font color="orange">' . $l['S1'] . '</font>';
            } else if ($row->status == 1) {
                echo '<font color="green">' . $l['S2'] . '</font>';
            } else if ($row->status == 2) {
                echo '<font color="red">' . $l['S5'] . '</font>';
            }

            ?>
					</td>
					<td><a href="?p=customers_sepa&mandate=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
				</tr>
				<?php }if ($sql->num_rows == 0) {?>
				<tr><td colspan="8"><center><?=$l['NMF'];?></center></td></tr>
				<?php }?>
			</table>
		</div>

		<?=$l['SELECTED'];?>: <div class="btn-group">
		  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		    <?=$l['CS'];?> <span class="caret"></span>
		  </button>
		  <ul class="dropdown-menu">
		  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_waiting', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S1'];?></a></li>
		  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_active', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S2'];?></a></li>
		    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_inactive', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S3'];?></a></li>
		  </ul>
		</div>

		<input type="submit" name="delete" value="<?=$l['DD'];?>" class="btn btn-danger" />
		</form>

        <?=$t->getFooter();?>
<?php }}