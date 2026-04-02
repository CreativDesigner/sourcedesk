<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['QUOTES'];
title($l['TITLE']);
menu("customers");

if (!$ari->check(7)) {require __DIR__ . "/error.php";if (!$ari->check(7)) {
    alog("general", "insufficient_page_rights", "quotes");
}
} else {

    if (isset($_GET['id'])) {
        alog("quote", "download", $_GET['id']);
        $pdf = new PDFQuote($_GET['id']);
        $pdf->output();
        exit;
    }

    $status = isset($_GET['status']) ? $_GET['status'] : "-1";

    if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
        $d = 0;

        if (isset($_POST['status']) && in_array($_POST['status'], array("0", "1", "2", "3"))) {
            $newStatus = intval($_POST['status']);
            foreach ($_POST['invoices'] as $id) {
                $db->query("UPDATE client_quotes SET status = $newStatus WHERE ID = " . intval($id));
                if ($db->affected_rows) {
                    alog("quote", "status_changed", $id, $newStatus);
                    $d++;
                }
            }

            if ($d == 1) {
                $msg = $l['SUC1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['SUC1X']);
            }

        } else if (isset($_POST['delete'])) {
            foreach ($_POST['invoices'] as $id) {
                $db->query("DELETE FROM client_quotes WHERE ID = " . intval($id));
                if ($db->affected_rows) {
                    $d++;
                    alog("quote", "deleted", $id);
                }
            }

            if ($d == 1) {
                $msg = $l['SUC2'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['SUC2X']);
            }

        } else if (isset($_POST['send_letter'])) {
            foreach ($_POST['invoices'] as $id) {
                $pdf = new PDFQuote($id);
                if (!$pdf->wasFound()) {
                    continue;
                }

                if (file_exists(__DIR__ . "/tmp.pdf")) {
                    unlink(__DIR__ . "/tmp.pdf");
                }

                $pdf->output(__DIR__ . "/tmp.pdf");

                $ex = explode("#", $_POST['send_letter'], 2);

                if (LetterHandler::myDrivers()[$ex[0]]->sendLetter(__DIR__ . "/tmp.pdf", true, $pdf->getCountry(), $ex[1]) === true) {
                    $d++;
                    $db->query("UPDATE client_quotes SET status = 1 WHERE status = 0 AND ID = " . intval($id));
                    alog("quote", "sent_letter", $id, $_POST['send_letter']);
                }

                if (file_exists(__DIR__ . "/tmp.pdf")) {
                    unlink(__DIR__ . "/tmp.pdf");
                }

            }

            if ($d == 1) {
                $msg = $l['SUC4'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['SUC4X']);
            }

        } else if (isset($_POST['send_mail'])) {
            foreach ($_POST['invoices'] as $id) {
                $pdf = new PDFQuote($id);
                if (!$pdf->wasFound()) {
                    continue;
                }

                if (!$pdf->getMail() || !$pdf->getName()) {
                    continue;
                }

                if (file_exists(__DIR__ . "/tmp.pdf")) {
                    unlink(__DIR__ . "/tmp.pdf");
                }

                $pdf->output(__DIR__ . "/tmp.pdf");

                $mt = new MailTemplate("Ihr Angebot");
                $title = $mt->getTitle($pdf->getLanguage());
                $mail = $mt->getMail($pdf->getLanguage(), $pdf->getName());

                $nr = $id;
                while (strlen($nr) < $CFG['MIN_QUOLEN']) {
                    $nr = "0" . $nr;
                }

                $prefix = $CFG['OFFER_PREFIX'];
                $date = strtotime($pdf->getDate());
                $prefix = str_replace("{YEAR}", date("Y", $date), $prefix);
                $prefix = str_replace("{MONTH}", date("m", $date), $prefix);
                $prefix = str_replace("{DAY}", date("d", $date), $prefix);

                $number = $prefix . $nr;

                $id = $maq->enqueue([
                    "nr" => $nr,
                    "amount" => $cur->infix($nfo->format($pdf->getSum()), $cur->getBaseCurrency()),
                    "valid" => $dfo->format(strtotime($pdf->getValid()), "", false, false),
                ], $mt, $pdf->getMail(), $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", 0, false, 0, 0, array($nr . ".pdf" => __DIR__ . "/tmp.pdf"));
                $maq->send(1, $id, true, false);
                $maq->delete($id);

                alog("quote", "sent_mail", $id);

                $d++;
                $db->query("UPDATE client_quotes SET status = 1 WHERE status = 0 AND ID = " . intval($id));

                if (file_exists(__DIR__ . "/tmp.pdf")) {
                    unlink(__DIR__ . "/tmp.pdf");
                }

            }

            if ($d == 1) {
                $msg = $l['SUC5'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['SUC5X']);
            }

        } else if (isset($_POST['invoice'])) {
            foreach ($_POST['invoices'] as $id) {
                $pdf = new PDFQuote($id);
                if (!$pdf->wasFound()) {
                    continue;
                }

                if (($cid = $pdf->getClient()) <= 0) {
                    continue;
                }

                $items = $pdf->getItems();

                $stages = [];
                $stageSql = $db->query("SELECT * FROM client_quote_stages WHERE quote = " . intval($id) . " ORDER BY days ASC");

                if ($stageSql->num_rows == 0) {
                    $stages = [
                        [$CFG['INVOICE_DUEDATE'], 100],
                    ];
                } else {
                    while ($stageRow = $stageSql->fetch_object()) {
                        $stages[] = [$stageRow->days, $stageRow->percent];
                    }
                }

                foreach ($stages as $stage) {
                    $inv = new Invoice;
                    $inv->setClient($cid);
                    $inv->setDate(date("Y-m-d"));
                    $inv->setDueDate(date("Y-m-d", strtotime("+" . $stage[0] . " days")));

                    $user = User::getInstance($cid, "ID");

                    foreach ($items as $i) {
                        if (!$pdf->getVat()) {
                            $i[2] = $user->addTax($i[2]);
                        }

                        $item = new InvoiceItem;
                        $item->setDescription($i[0]);
                        $item->setAmount(round($i[2] * $stage[1] / 100, 2));
                        $item->save();
                        $inv->addItem($item);
                    }

                    $inv->save();
                }

                $d++;
                $db->query("UPDATE client_quotes SET status = 2 WHERE ID = " . intval($id));
                alog("quote", "invoiced", $id);
            }

            if ($d == 1) {
                $msg = $l['SUC6'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['SUC6X']);
            }

        }
    }
    ?>

<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE'];?> <a class="pull-right" href="?p=new_quote"><i class="fa fa-plus-circle"></i></a></h1>

		<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$status == "-1" ? " active" : "";?>" href="./?p=quotes"><?=$l['S1'];?> (<?=$db->query("SELECT 1 FROM client_quotes")->num_rows;?>)</a>
				</div>

				<div class="list-group">
					<a class="list-group-item<?=$status == "0" ? " active" : "";?>" href="./?p=quotes&status=0"><?=$l['S2'];?> (<?=$db->query("SELECT 1 FROM client_quotes WHERE status = 0 AND valid >= '" . date("Y-m-d") . "'")->num_rows;?>)</a>
					<a class="list-group-item<?=$status == "1" ? " active" : "";?>" href="./?p=quotes&status=1"><?=$l['S3'];?> (<?=$db->query("SELECT 1 FROM client_quotes WHERE status = 1 AND valid >= '" . date("Y-m-d") . "'")->num_rows;?>)</a>
					<a class="list-group-item<?=$status == "2" ? " active" : "";?>" href="./?p=quotes&status=2"><?=$l['S4'];?> (<?=$db->query("SELECT 1 FROM client_quotes WHERE status = 2")->num_rows;?>)</a>
					<a class="list-group-item<?=$status == "4" ? " active" : "";?>" href="./?p=quotes&status=4"><?=$l['S5'];?> (<?=$db->query("SELECT 1 FROM client_quotes WHERE valid < '" . date("Y-m-d") . "' AND status != 2 AND status != 3")->num_rows;?>)</a>
					<a class="list-group-item<?=$status == "3" ? " active" : "";?>" href="./?p=quotes&status=3"><?=$l['S6'];?> (<?=$db->query("SELECT 1 FROM client_quotes WHERE status = 3")->num_rows;?>)</a>
				</div>
			</div>

			<div class="col-md-9">
				<?php if (!empty($msg)) {?><div class="alert alert-success"><?=$msg;?></div><?php }?>

				<?php
$sql = "SELECT * FROM client_quotes";
    if ($status == "0") {
        $sql = "SELECT * FROM client_quotes WHERE status = 0 AND valid >= '" . date("Y-m-d") . "'";
    } else if ($status == "1") {
        $sql = "SELECT * FROM client_quotes WHERE status = 1 AND valid >= '" . date("Y-m-d") . "'";
    } else if ($status == "2") {
        $sql = "SELECT * FROM client_quotes WHERE status = 2";
    } else if ($status == "3") {
        $sql = "SELECT * FROM client_quotes WHERE status = 3";
    } else if ($status == "4") {
        $sql = "SELECT * FROM client_quotes WHERE valid < '" . date("Y-m-d") . "' AND status != 2 AND status != 3";
    }

    $table = new Table($sql, [], ["date", "DESC"], "quotes");

    echo $table->getHeader();
    ?>

				<form method="POST" id="invoice_form"><div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
							<th><?=$table->orderHeader("date", $l['DATE']);?></th>
							<th><?=$table->orderHeader("valid", $l['VALID']);?></th>
							<th><?=$l['RECIP'];?></th>
							<th><?=$l['SUM'];?></th>
							<th><?=$l['STATUS'];?></th>
							<th width="30px"></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $table->qry("`date` DESC, `valid` DESC, `ID` DESC");
    while ($row = $sql->fetch_object()) {
        $i = unserialize($row->recipient);
        if ($row->client > 0) {
            $uI = User::getInstance($row->client, "ID");
            if (!$uI) {
                $rec = $i[0] . " " . $i[1];
            } else {
                $rec = '<a href="?p=customers&edit=' . $row->client . '">' . $uI->getfName() . '</a>';
            }

        } else {
            $rec = $i[0] . " " . $i[1];
        }

        $sum = 0;
        $items = unserialize($row->items);
        foreach ($items as $i) {
            $sum += $i[2];
        }

        $sum = $cur->infix($nfo->format($sum), $cur->getBaseCurrency());
        ?>
						<tr>
							<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$row->ID;?>" /></td>
							<td><?=$dfo->format($row->date, false);?></td>
							<td><?=$dfo->format($row->valid, false);?></td>
							<td><?=$rec;?></td>
							<td><?=$sum;?> <?=$row->vat ? $lang['QUOTES']['GROSS'] : $lang['QUOTES']['NET'];?></td>
							<td><?php if ($row->status == 2) {?><font color="green"><?=$l['S4'];?></font><?php } else if ($row->status == 3) {?><?=$l['S6'];?><?php } else if ($row->valid < date("Y-m-d")) {?><font color="red"><?=$l['S5'];?></font><?php } else if ($row->status == 0) {?><font color="orange"><?=$l['S2'];?></font><?php } else if ($row->status == 1) {?><font color="orange"><?=$l['S3'];?></font><?php }?></td>
							<td><a href="?p=quotes&id=<?=$row->ID;?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
							<td><a href="?p=quote&id=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
						</tr>
						<?php }if ($sql->num_rows == 0) {?>
						<tr><td colspan="8"><center><?=$l['NT'];?></center></td></tr>
						<?php }?>
					</table>
				</div>

				<?=$l['SELECTED'];?>: <div class="btn-group">
				  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				  <?=$l['CS'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '0' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S2'];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '1' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S3'];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '2' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S4'];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '3' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['S6'];?></a></li>
				  </ul>
				</div>

				<input type="submit" name="invoice" class="btn btn-success" value="<?=$l['INVOICE'];?>">

				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				  <?=$l['SEND'];?> <span class="caret"></span>
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

				<input type="submit" name="delete" class="btn btn-danger" value="<?=$l['DELETE'];?>">
				</form>

				<br /><?php echo $table->getFooter(); ?>
			</div>
		</div>

<?php }