<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['LETTERS'];
title($l['TITLE']);
menu("customers");

if (!$ari->check(7)) {require __DIR__ . "/error.php";if (!$ari->check(7)) {
    alog("general", "insufficient_page_rights", "letters");
}
} else {

    if (isset($_GET['id'])) {
        $pdf = new PDFLetter($_GET['id']);
        $pdf->output();
        exit;
    }

    $status = isset($_GET['status']) ? $_GET['status'] : "-1";

    if (isset($_POST['invoices']) && is_array($_POST['invoices'])) {
        $d = 0;

        if (isset($_POST['mark_sent'])) {
            foreach ($_POST['invoices'] as $id) {
                $db->query("UPDATE client_letters SET sent = 1 WHERE ID = " . intval($id));
                if ($db->affected_rows) {
                    $d++;
                    alog("letter", "mark_sent", $id);
                }
            }

            if ($d == 1) {
                $msg = $l['S1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['SX']);
            }

        } else if (isset($_POST['mark_unsent'])) {
            foreach ($_POST['invoices'] as $id) {
                $db->query("UPDATE client_letters SET sent = 0 WHERE ID = " . intval($id));
                if ($db->affected_rows) {
                    $d++;
                    alog("letter", "mark_unsent", $id);
                }
            }

            if ($d == 1) {
                $msg = $l['W1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['WX']);
            }

        } else if (isset($_POST['delete'])) {
            foreach ($_POST['invoices'] as $id) {
                $db->query("DELETE FROM client_letters WHERE ID = " . intval($id));
                if ($db->affected_rows) {
                    $d++;
                    alog("letter", "delete", $id);
                }
            }

            if ($d == 1) {
                $msg = $l['D1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['DX']);
            }

        } else if (isset($_POST['send_letter'])) {
            foreach ($_POST['invoices'] as $id) {
                $pdf = new PDFLetter($id);
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
                    $db->query("UPDATE client_letters SET sent = 1 WHERE ID = " . intval($id));
                    alog("letter", "sent_to_provider", $id, $_POST['send_letter']);
                }

                if (file_exists(__DIR__ . "/tmp.pdf")) {
                    unlink(__DIR__ . "/tmp.pdf");
                }

            }

            if ($d == 1) {
                $msg = $l['P1'];
            } else if ($d > 0) {
                $msg = str_replace("%d", $d, $l['PX']);
            }

        }
    }
    ?>

<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE'];?> <a class="pull-right" href="?p=new_letter"><i class="fa fa-plus-circle"></i></a></h1>
				<?php if (!empty($msg)) {?><div class="alert alert-success"><?=$msg;?></div><?php }?>

				<?php
$t = new Table("SELECT * FROM client_letters", [
        "subject" => [
            "name" => $l['SUBJECT'],
            "type" => "like",
        ],
        "sent" => [
            "name" => $l['STATUS'],
            "type" => "select",
            "options" => [
                "0" => $l['STAT0'],
                "1" => $l['STAT1'],
            ],
        ],
    ], ["date", "DESC"], "letters");

    echo $t->getHeader();
    ?>

				<form method="POST" id="invoice_form"><div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
							<th><?=$t->orderHeader("date", $l['DATE']);?></th>
							<th><?=$l['RECIP'];?></th>
							<th><?=$t->orderHeader("subject", $l['SUBJECT']);?></th>
							<th><?=$t->orderHeader("sent", $l['STATUS']);?></th>
							<th width="30px"></th>
						</tr>

						<?php
$sql = $t->qry("date DESC, ID DESC");
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
        ?>
						<tr>
							<td><input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="invoices[]" value="<?=$row->ID;?>" /></td>
							<td><?=$dfo->format($row->date, false);?></td>
							<td><?=$rec;?></td>
							<td><?=$row->subject;?> <a href="?p=letters&id=<?=$row->ID;?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
							<td><?php if ($row->sent) {?><font color="green"><?=$l['STAT1'];?></font><?php } else {?><font color="orange"><?=$l['STAT0'];?></font><?php }?></td>
							<td><a href="?p=letter&id=<?=$row->ID;?>"><i class="fa fa-edit"></i></a></td>
						</tr>
						<?php }if ($sql->num_rows == 0) {?>
						<tr><td colspan="6"><center><?=$l['NT'];?></center></td></tr>
						<?php }?>
					</table>
				</div>

				<?=$l['SELECTED'];?>: <div class="btn-group">
				  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$l['CS'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_unsent', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['STAT0'];?></a></li>
				    <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'mark_sent', value: 'true' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$l['STAT1'];?></a></li>
				  </ul>
				</div>

				<?php if (LetterHandler::myDrivers()) {?>
				<div class="btn-group">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<?=$l['SENDDD'];?> <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
				<?php $i = 0;foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {?>
                <?php if ($i++) {?><li role="separator" class="divider"></li><?php }?>
                <li class="dropdown-header"><?=$drivObj->getName();?></li>
                <?php foreach ($drivObj->getTypes() as $code => $name) {?>
                <li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'send_letter', value: '<?=$drivKey;?>#<?=$code;?>' }).appendTo('#invoice_form'); $('#invoice_form').submit(); return false;"><?=$name;?></a></li>
                <?php }}?>
				</ul>
				</div>
				<?php }?>

				<input type="submit" name="delete" class="btn btn-danger" value="<?=$l['DEL'];?>">
				</form>
<?php echo $t->getFooter();}