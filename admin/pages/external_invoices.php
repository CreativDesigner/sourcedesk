<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['EINVOICES'];
$sl = $lang['EINVOICE'];
title($l['TITLE']);
menu("payments");

if (!$ari->check(16)) {require __DIR__ . "/error.php";if (!$ari->check(16)) {
    alog("general", "insufficient_page_rights", "external_invices");
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
                    alog("invoice", "mark_paid", $inv->getID());
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $l['P1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['PX']);
            }

        } else if (isset($_POST['mark_unpaid'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getStatus() != 0 && $inv->getAmount() != 0) {
                    $inv->setStatus(0);
                    $inv->save();
                    alog("invoice", "mark_unpaid", $inv->getID());
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $l['U1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['UX']);
            }

        } else if (isset($_POST['cancel'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getStatus() != 2) {
                    $inv->setStatus(2);
                    $inv->save();
                    alog("invoice", "cancel", $inv->getID());
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $l['C1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['CX']);
            }

        } else if (isset($_POST['delete'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->delete() !== false) {
                    $d++;
                    alog("invoice", "delete", $inv->getID());
                }
            }

            if ($d == 1) {
                $msg = $l['R1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['RX']);
            }

        } else if (isset($_POST['send_mail'])) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->send("send") !== false) {
                    $d++;
                    alog("invoice", "send", $inv->getID());
                }
            }

            if ($d == 1) {
                $msg = $l['E1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['EX']);
            }

        } else if (isset($_POST['send_letter'])) {
            $oldLang = $lang;

            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id)) {
                    require __DIR__ . "/../../languages/" . basename($inv->getLanguage()) . ".php";

                    $pdf = new PDFInvoice();
                    $pdf->add($inv);
                    if (file_exists(__DIR__ . "/tmp.pdf")) {
                        unlink(__DIR__ . "/tmp.pdf");
                    }

                    $pdf->output(__DIR__ . "/tmp", "F", false);

                    $ex = explode("#", $_POST['send_letter'], 2);

                    if (LetterHandler::myDrivers()[$ex[0]]->sendLetter(__DIR__ . "/tmp.pdf", true, $inv->getCountry("alpha2"), $ex[1]) === true) {
                        $d++;
                        $inv->setLetterSent(1);
                        $inv->save();
                        alog("invoice", "send_letter", $inv->getID());
                    }

                    if (file_exists(__DIR__ . "/tmp.pdf")) {
                        unlink(__DIR__ . "/tmp.pdf");
                    }

                }
            }

            if ($d == 1) {
                $msg = $l['L1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['LX']);
            }

            $lang = $oldLang;
        } else if (isset($_POST['no_reminders']) && in_array($_POST['no_reminders'], array("0", "1"))) {
            foreach ($_POST['invoices'] as $id) {
                if ($inv->load($id) && $inv->getReminders() != ($_POST['no_reminders'] ? false : true)) {
                    $inv->setReminders($_POST['no_reminders'] ? false : true);
                    $inv->save();
                    alog("invoice", "set_reminder", $inv->getID(), $_POST['no_reminders']);
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
                    alog("invoice", "set_reminder_step", $inv->getID(), $_POST['reminder']);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $l['RL1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['RLX']);
            }

        }
    }
    ?>

<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE'];?> <a class="pull-right" href="./?p=new_einvoice"><i class="fa fa-plus-circle"></i></a></h1>

		<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$status == "-1" ? " active" : "";?>" href="./?p=external_invoices"><?=$l['ALL'];?> (<?=$db->query("SELECT 1 FROM invoices WHERE client = 0")->num_rows;?>)</a>
				</div>
				<div class="list-group">
					<a class="list-group-item<?=$status == "0" ? " active" : "";?>" href="./?p=external_invoices&status=0"><?=$l['S1'];?> (<?=$db->query("SELECT 1 FROM invoices WHERE status = 0 AND paid_amount = 0 AND client = 0")->num_rows;?>)</a>
					<a class="list-group-item<?=$status == "3" ? " active" : "";?>" href="./?p=external_invoices&status=3"><?=$l['S2'];?> (<?=$db->query("SELECT 1 FROM invoices WHERE status = 0 AND paid_amount > 0 AND client = 0")->num_rows;?>)</a>
					<a class="list-group-item<?=$status == "1" ? " active" : "";?>" href="./?p=external_invoices&status=1"><?=$l['S3'];?> (<?=$db->query("SELECT 1 FROM invoices WHERE status = 1 AND client = 0")->num_rows;?>)</a>
					<a class="list-group-item<?=$status == "2" ? " active" : "";?>" href="./?p=external_invoices&status=2"><?=$l['S4'];?> (<?=$db->query("SELECT 1 FROM invoices WHERE status = 2 AND client = 0")->num_rows;?>)</a>
				</div>
			</div>

			<div class="col-md-9">
				<?php if (!empty($msg)) {?><div class="alert alert-success"><?=$msg;?></div><?php }?>

				<?php
$r = $status != "-1" ? " AND status = " . intval($status) : "";
    if ($status == "0") {
        $r = " AND status = 0 AND paid_amount = 0";
    }

    if ($status == "3") {
        $r = " AND status = 0 AND paid_amount > 0";
    }

    $t = new Table("SELECT * FROM invoices WHERE client = 0$r", [], ["date", "DESC"], "invoices");

    echo $t->getHeader();
    ?>

				<form method="POST" id="invoice_form"><div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
							<th>#</th>
							<th><?=$l['RECIP'];?></th>
							<th><?=$t->orderHeader("date", $el['DATE']);?></th>
							<th><?=$t->orderHeader("duedate", $l['DUE']);?></th>
							<?php if ($CFG['TAXES']) {?>
							<th><?=$l['NET'];?></th><th><?=$l['GROSS'];?></th>
							<?php } else {?><th><?=$l['AMOUNT'];?></th><?php }?>
							<th><?=$t->orderHeader("paid_amount", $l['PAID']);?></th>
							<th><?=$t->orderHeader("status", $l['STATUS']);?></th>
							<th><?=$t->orderHeader("reminder", $l['RL']);?></th>
							<th width="35px"><center><?=$t->orderHeader("letter_sent", $l['POSTAL']);?></center></th>
							<th width="35px"><center><?=$t->orderHeader("encashment_provider", $l['ENCASHMENT']);?></center></th>
							<th width="30px"></th>
						</tr>

						<?php
$invoice = new Invoice;
    $sql = $t->qry("date DESC, ID DESC");
    while ($row = $sql->fetch_object()) {$invoice->load($row->ID);
        $info = unserialize($invoice->getClientData());?>
						<tr>
							<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$invoice->getId();?>" /></td>
							<td><?php if (!empty($invoice->getAttachment())) {?><span class="label label-primary"><?=array_shift(explode(".", $invoice->getAttachment()));?></span><?php }?> <a href="#" class="invoiceDetails" data-id="<?=$invoice->getId();?>"><?=$invoice->getShortNo();?></a></td>
							<td><?=$info['firstname'] . " " . $info['lastname'] . (!empty($info['company']) ? " ({$info['company']})" : "");?></td>
							<td><?=$dfo->format(strtotime($invoice->getDate()), false);?></td>
							<td><?=$dfo->format(strtotime($invoice->getDueDate()), false);?></td>
							<?php if ($CFG['TAXES']) {?><td><?=$cur->infix($nfo->format($invoice->getNet()), $cur->getBaseCurrency());?></td><?php }?><td><?=$cur->infix($nfo->format($invoice->getAmount()), $cur->getBaseCurrency());?></td>
							<td><?=$cur->infix($nfo->format($invoice->getPaidAmount()), $cur->getBaseCurrency());?></td>
							<td><?php if ($invoice->getStatus() == 0) {if ($invoice->getPaidAmount() > 0) {?><font color="orange"><?=$l['S2'];?></font><?php } else {?><font color="red"><?=$l['S1'];?></font><?php }} else if ($invoice->getStatus() == 1) {?><font color="green"><?=$l['S3'];?></font><?php } else {?><?=$l['S4'];?><?php }?></td>
							<td><?php if (!$invoice->getReminders()) {?><i class="fa fa-ban"></i> <?php }?><?php if (!$invoice->reminderLevel()) {?><i>keine</i><?php } else {?><?=$invoice->reminderLevel();?> (<?=$cur->infix($nfo->format($invoice->getLateFees()), $cur->getBaseCurrency());?>)<?php }?></td>
							<td><center><i class="fa fa-<?=$invoice->getLetterSent() ? "check" : "times";?>"></i></center></td>
							<td><center><a href="?p=encashment&invoice=<?=$invoice->getId();?>"><i class="fa fa-<?=$invoice->isEncashment() ? "check" : "times";?>"></i></a></center></td>
							<td>
								<a href="?p=einvoice&id=<?=$invoice->getId();?>" title="Bearbeiten"><i class="fa fa-edit"></i></a>
							</td>
						</tr>
						<?php }if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="13"><center><?=$l['NT'];?></center></td>
						</tr>
						<?php }?>
					</table>
				</div>

				<?=$l['SELECTED'];?>: <div class="btn-group">
				  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$l['CS'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_paid', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S3'];?></a></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_unpaid', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S1'];?></a></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'cancel', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S4'];?></a></li>
				    <li role="separator" class="divider"></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'delete', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['DEL'];?></a></li>
				  </ul>
				</div>

				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				  <?=$l['REMINDSYSTEM'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'reminder', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['NRL'];?></a></li>
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
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'no_reminders', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['ACT'];?></a></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'no_reminders', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['DEACT'];?></a></li>
				  </ul>
				</div>

				<div class="btn-group">
					<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?=$l['PDF'];?> <span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
						<li><a href="#" onclick="$('#invoice_form').attr('action', '?p=invoices').attr('target', '_blank').submit().attr('target', '').attr('action', ''); return false;"><?=$l['INV'];?></a></li>
						<li><a href="#" onclick="$('#invoice_form').attr('action', '?p=delivery_notes').attr('target', '_blank').submit().attr('target', '').attr('action', ''); return false;"><?=$l['DN'];?></a></li>
					</ul>
				</div>

				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$l['SENDDD'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_mail', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['TOEM'];?></a></li>
                    <?php foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {?>
                    <li role="separator" class="divider"></li>
                    <li class="dropdown-header"><?=$drivObj->getName();?></li>
                    <?php foreach ($drivObj->getTypes() as $code => $name) {?>
                    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_letter', value: '<?=$drivKey;?>#<?=$code;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$name;?></a></li>
                    <?php }}?>
				  </ul>
				</div>
				</form>

				<br /><?php echo $t->getFooter(); ?>
			</div>
		</div>

<?php }