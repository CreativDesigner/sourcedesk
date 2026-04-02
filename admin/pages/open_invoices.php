<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$el = $lang['EINVOICES'];
$l = $lang['OPEN_INVOICES'];
title($l['TITLE']);
menu("payments");

if (!$ari->check(16)) {require __DIR__ . "/error.php";if (!$ari->check(16)) {
    alog("general", "insufficient_page_rights", "open_invoices");
}
} else {

    $status = isset($_GET['status']) ? $_GET['status'] : "-1";

    $inv = new Invoice;
    if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
        $d = 0;

        if (isset($_POST['mark_paid'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getStatus() != 1) {
                    $inv->setStatus(1);
                    $inv->save();
                    alog("invoice", "mark_paid", $id);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $el['P1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $el['PX']);
            }

        } else if (isset($_POST['mark_unpaid'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getStatus() != 0 && $inv->getAmount() != 0) {
                    $inv->setStatus(0);
                    $inv->save();
                    alog("invoice", "mark_unpaid", $id);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $el['U1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $el['UX']);
            }

        } else if (isset($_POST['cancel'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getStatus() != 2) {
                    $inv->setStatus(2);
                    $inv->save();
                    alog("invoice", "cancel", $id);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $el['C1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $el['CX']);
            }

        } else if (isset($_POST['delete'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->delete() !== false) {
                    $d++;
                    alog("invoice", "delete", $id);
                }
            }

            if ($d == 1) {
                $msg = $el['R1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $el['RX']);
            }

        } else if (isset($_POST['clear_data'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id)) {
                    if ($inv->getClient() == "0") {
                        continue;
                    }

                    $inv->clearClientData();
                    $inv->save();
                    alog("invoice", "clear_data", $id);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $l['CD1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['CDX']);
            }

        } else if (isset($_POST['credit_pay'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id)) {
                    if ($inv->getClient() == "0") {
                        continue;
                    }

                    if ($inv->applyCredit() === false) {
                        continue;
                    }

                    alog("invoice", "credit_pay", $id);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $l['CR1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['CRX']);
            }

        } else if (isset($_POST['send_mail'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id)) {
                    if ($inv->send("send")) {
                        $d++;
                        alog("invoice", "send", $id);
                    }
                }
            }

            if ($d == 1) {
                $msg = $el['E1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $el['EX']);
            }

        } else if (isset($_POST['send_letter'])) {
            $oldLang = $lang;

            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id)) {
                    $uI = User::getInstance($inv->getClient(), "ID");
                    if (!$uI) {
                        continue;
                    }

                    $uI->loadLanguage();

                    $sql = $db->query("SELECT alpha2 FROM client_countries WHERE ID = " . $uI->get()['country']);
                    if ($sql->num_rows != 1) {
                        continue;
                    }

                    $alpha2 = $sql->fetch_object()->alpha2;

                    $pdf = new PDFInvoice();
                    $pdf->add($inv);
                    if (file_exists(__DIR__ . "/tmp.pdf")) {
                        unlink(__DIR__ . "/tmp.pdf");
                    }

                    $pdf->output(__DIR__ . "/tmp", "F", false);

                    $ex = explode("#", $_POST['send_letter'], 2);

                    if (LetterHandler::myDrivers()[$ex[0]]->sendLetter(__DIR__ . "/tmp.pdf", true, $alpha2, $ex[1]) === true) {
                        $d++;
                        $inv->setLetterSent(1);
                        $inv->save();
                        alog("invoice", "sent_letter", $id, $_POST['send_letter']);
                    }

                    if (file_exists(__DIR__ . "/tmp.pdf")) {
                        unlink(__DIR__ . "/tmp.pdf");
                    }

                }
            }

            if ($d == 1) {
                $msg = $el['L1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $el['LX']);
            }

            $lang = $oldLang;
        } else if (isset($_POST['no_reminders']) && in_array($_POST['no_reminders'], array("0", "1"))) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getReminders() != ($_POST['no_reminders'] ? false : true)) {
                    $inv->setReminders($_POST['no_reminders'] ? false : true);
                    alog("invoice", "set_reminders", $_POST['no_reminders']);
                    $inv->save();
                    $d++;
                }
            }

            if ($_POST['no_reminders']) {
                if ($d == 1) {
                    $msg = $l['NR1'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['NRX']);
                }

            } else {
                if ($d == 1) {
                    $msg = $l['RE1'];
                } else if ($d > 0) {
                    $msg = str_replace("%d", $d, $l['REX']);
                }

            }
        } else if (isset($_POST['reminder']) && ($_POST['reminder'] == "0" || $db->query("SELECT 1 FROM reminders WHERE ID = " . intval($_POST['reminder']))->num_rows == 1)) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getReminder() != $_POST['reminder']) {
                    $inv->setReminder($_POST['reminder']);
                    $inv->save();
                    alog("invoice", "set_reminder_level", $id, $_POST['reminder']);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $el['RL1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $el['RLX']);
            }

        }
    }
    ?>

<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE'];?></h1>

		<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$status == "-1" ? " active" : "";?>" href="./?p=open_invoices"><?=$l['ALL'];?> (<?=$db->query("SELECT 1 FROM invoices WHERE status = 0")->num_rows;?>)</a>
				</div>
				<div class="list-group">
					<a class="list-group-item<?=$status == "0" ? " active" : "";?>" href="./?p=open_invoices&status=0"><?=$lang['EINVOICE']['STATUS1'];?> (<?=$db->query("SELECT 1 FROM invoices WHERE status = 0 AND reminder = 0")->num_rows;?>)</a>
					<?php $sql = $db->query("SELECT * FROM reminders ORDER BY days ASC, name ASC");
    while ($row = $sql->fetch_object()) {?>
					<a class="list-group-item<?=$status == $row->ID ? " active" : "";?>" href="./?p=open_invoices&status=<?=$row->ID;?>"><?=$row->bold && $row->ID != $status ? "<b>" : "";?><?=!empty($row->color) && $status != $row->ID ? "<font color=\"{$row->color}\">" : "";?><?=$row->name;?><?=!empty($row->color) && $status != $row->ID ? "</font>" : "";?><?=$row->bold && $row->ID != $status ? "</b>" : "";?> (<?=$db->query("SELECT 1 FROM invoices WHERE status = 0 AND reminder = " . intval($row->ID))->num_rows;?>)</a>
					<?php }?>
				</div>
			</div>

			<div class="col-md-9">
				<?php if (!empty($msg)) {?><div class="alert alert-success"><?=$msg;?></div><?php }?>

				<?php
$r = $status != "-1" ? " AND reminder = " . intval($status) : "";

    $t = new Table("SELECT * FROM invoices WHERE status = 0$r", [], ["duedate", "DESC"], "open_invoices");

    echo $t->getHeader();
    ?>

				<form method="POST" id="invoice_form"><div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
							<th>#</th>
							<th><?=$t->orderHeader("client", $lang['RECURRING_INVOICE']['CUSTOMER']);?></th>
							<th><?=$t->orderHeader("date", $lang['QUOTE']['DATE']);?></th>
							<th><?=$t->orderHeader("duedate", $el['DUE']);?></th>
							<?php if ($CFG['TAXES']) {?>
							<th><?=$el['NET'];?></th><th><?=$el['GROSS'];?></th>
							<?php } else {?><th><?=$el['AMOUNT'];?></th><?php }?>
							<th><?=$t->orderHeader("status", $el['STATUS']);?></th>
							<th><?=$t->orderHeader("reminder", $el['RL']);?></th>
							<th width="35px"><center><?=$t->orderHeader("letter_sent", $el['POSTAL']);?></center></th>
							<th width="35px"><center><?=$t->orderHeader("encashment_provider", $el['ENCASHMENT']);?></center></th>
							<th width="30px"></th>
						</tr>

						<?php
$invoice = new Invoice;
    $sql = $t->qry("duedate DESC, ID DESC");
    $net_sum = $gross_sum = 0;
    while ($row = $sql->fetch_object()) {$invoice->load($row->ID);?>
						<tr>
							<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$invoice->getId();?>" /></td>
							<td><?php if (!empty($invoice->getAttachment())) {?><span class="label label-primary"><?=array_shift(explode(".", $invoice->getAttachment()));?></span><?php }?> <a href="#" class="invoiceDetails" data-id="<?=$invoice->getId();?>"><?=$invoice->getShortNo();?></a></td>
							<?php $u = User::getInstance($invoice->getClient(), "ID");?>
							<td><?php if ($u) {?><a href="?p=customers&edit=<?=$invoice->getClient();?>"><?=$u->getfName();?></a><?php if ($u->get()['no_reminders']) {?> <i class="fa fa-ban" style="color: red;"></i><?php }} else if ($invoice->getClient() == "0") {$d = (object) unserialize($invoice->getClientData());?><?=$d->firstname . " " . $d->lastname . (!empty($d->company) ? " ({$d->company})" : "");?><?php } else {?><i><?=$l['UKC'];?></i><?php }?></td>
							<td><?=$dfo->format(strtotime($invoice->getDate()), false);?></td>
							<td><?=$dfo->format(strtotime($invoice->getDueDate()), false);?></td>
							<?php $gross_sum += $invoice->getAmount();if ($CFG['TAXES']) {$net_sum += $invoice->getNet();?><td><?=$cur->infix($nfo->format($invoice->getNet()), $cur->getBaseCurrency());?></td><?php }?><td><?=$cur->infix($nfo->format($invoice->getAmount()), $cur->getBaseCurrency());?></td>
							<td><?php if ($invoice->getStatus() == 0) {if ($invoice->getPaidAmount() > 0) {?><font color="orange"><?=$el['S2'];?> (<?=$cur->infix($nfo->format($invoice->getPaidAmount()), $cur->getBaseCurrency());?>)</font><?php } else {?><font color="red"><?=$el['S1'];?></font><?php }} else if ($invoice->getStatus() == 1) {?><font color="green"><?=$el['S3'];?></font><?php } else {?><?=$el['S4'];?><?php }?></td>
							<td><?php if (!$invoice->getReminders()) {?><i class="fa fa-ban"></i> <?php }?><?php if (!$invoice->reminderLevel()) {?><i><?=$l['NRL'];?></i><?php } else {?><?=$invoice->reminderLevel();?><?=$invoice->getLateFees() > 0 ? " (" . $cur->infix($nfo->format($invoice->getLateFees()), $cur->getBaseCurrency()) . ")" : "";?><?php }?></td>
							<td><center><i class="fa fa-<?=$invoice->getLetterSent() ? "check" : "times";?>"></i></center></td>
							<td><center><a href="?p=encashment&invoice=<?=$invoice->getId();?>"><i class="fa fa-<?=$invoice->isEncashment() ? "check" : "times";?>"></i></a></center></td>
							<td>
								<a href="?p=<?=$invoice->getClient() == "0" ? "e" : "";?>invoice&id=<?=$invoice->getId();?>" title="Bearbeiten"><i class="fa fa-edit"></i></a>
							</td>
						</tr>
						<?php }if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="12"><center><?=$l['NT'];?></center></td>
						</tr>
						<?php } else {?>
                        <tr>
                            <th colspan="5" style="text-align: right;"><?=$lang['QUOTES']['SUM'];?></th>
                            <?php if ($CFG['TAXES']) {?><th><?=$cur->infix($nfo->format($net_sum), $cur->getBaseCurrency());?></th><?php }?><th><?=$cur->infix($nfo->format($gross_sum), $cur->getBaseCurrency());?></th>
                            <th colspan="5"></th>
                        </tr>
                        <?php }?>
					</table>
				</div>

				<?=$el['SELECTED'];?>: <div class="btn-group">
				  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$el['CS'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_paid', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['S3'];?></a></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_unpaid', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['S1'];?></a></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'cancel', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['S4'];?></a></li>
				    <li role="separator" class="divider"></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'delete', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['DEL'];?></a></li>
				  </ul>
				</div>

				<input type="submit" name="credit_pay" value="<?=$l['PAYCREDIT'];?>" class="btn btn-success" />

				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$el['REMINDSYSTEM'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'reminder', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['NRL'];?></a></li>
				    <?php
$sql = $db->query("SELECT * FROM reminders ORDER BY days ASC, name ASC");
    while ($row = $sql->fetch_object()) {?>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'reminder', value: '<?=$row->ID;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?php if (!empty($row->color)) {
        echo '<font color="' . $row->color . '">';
    }
        ?><?php if ($row->bold) {
            echo "<b>";
        }
        ?><?=$row->name;?><?php if ($row->bold) {
            echo "</b>";
        }
        ?><?php if (!empty($row->color)) {
            echo '</font>';
        }
        ?></a></li>
				   	<?php }?>
				    <li role="separator" class="divider"></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'no_reminders', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['ACT'];?></a></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'no_reminders', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['DEACT'];?></a></li>
				  </ul>
				</div>

				<div class="btn-group">
					<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?=$el['PDF'];?> <span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
						<li><a href="#" onclick="$('#invoice_form').attr('action', '?p=invoices').attr('target', '_blank').submit().attr('target', '').attr('action', ''); return false;"><?=$el['INV'];?></a></li>
						<li><a href="#" onclick="$('#invoice_form').attr('action', '?p=delivery_notes').attr('target', '_blank').submit().attr('target', '').attr('action', ''); return false;"><?=$el['DN'];?></a></li>
					</ul>
				</div>

				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$el['SENDDD'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_mail', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$el['TOEM'];?></a></li>
                      <?php foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {?>
                    <li role="separator" class="divider"></li>
                    <li class="dropdown-header"><?=$drivObj->getName();?></li>
                    <?php foreach ($drivObj->getTypes() as $code => $name) {?>
                    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_letter', value: '<?=$drivKey;?>#<?=$code;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$name;?></a></li>
                    <?php }}?>
				  </ul>
				</div>

				<input type="submit" name="clear_data" value="<?=$l['RCD'];?>" class="btn btn-default" />
				</form>

				<br /><?php echo $t->getFooter(); ?>
			</div>
<br />
		</div>
<?php }