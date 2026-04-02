<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['FIBU'];

title($l['TITLE']);
menu("payments");

function formatAcct($id)
{
    if (!$id) {
        return "-";
    }

    $acct = \Fibu\Account::getInstance($id);
    if ($acct) {
        $title = htmlentities($acct->getName());
        return "<a href='#' onclick='return false;' data-toggle='tooltip' style='text-decoration: none !important; border-bottom: 1px dashed;' title='$title'>$id</a>";
    }

    $acct = \Fibu\Ledger::getInstance($id);
    if ($acct) {
        $title = htmlentities($acct->getName());
        return "<a href='#' onclick='return false;' data-toggle='tooltip' style='text-decoration: none !important; border-bottom: 1px dashed;' title='$title'>$id</a>";
    }

    return $id;
}

if (!$ari->check(40)) {require __DIR__ . "/error.php";if (!$ari->check(40)) {
    alog("general", "insufficient_page_rights", "fibu");
}
} else {
    $years = [date("Y")];
    $sql = $db->query("SELECT year FROM fibu_journal GROUP BY year ORDER BY year DESC");
    while ($row = $sql->fetch_object()) {
        if (!in_array($row->year, $years)) {
            array_push($years, $row->year);
        }
    }
    arsort($years);

    $tab = isset($_GET['t']) ? $_GET['t'] : "overview";
    $year = isset($_GET['y']) && is_numeric($_GET['y']) && $_GET['y'] > 0 && $_GET['y'] <= date("Y") && in_array($_GET['y'], $years) ? intval($_GET['y']) : date("Y");

    $startTime = strtotime($year . "-01-01");
    $endTime = strtotime($year . "-12-31 23:59:59");

    ?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?></h1>
    </div>
</div>

<?php
if (!$db->query("SELECT 1 FROM fibu_accounts")->num_rows) {
        if (isset($_GET['import']) && Fibu\Account::import($_GET['import'])) {
            echo '<div class="alert alert-success">' . $l['ACCTIMS'] . '</div>';
        } else {
            echo '<div class="alert alert-warning">' . $l['ACCTIM'] . ' [ <a href="?p=fibu&import=skr03">SKR03</a> | <a href="?p=fibu&import=skr04">SKR04</a> ]</div>';
        }
    }
    ?>

<div class="row">
	<div class="col-md-3">
		<form method="GET">
			<input type="hidden" name="p" value="fibu" />
			<input type="hidden" name="t" value="<?=$tab;?>" />
			<select name="y" class="form-control" onchange="form.submit();">
				<option value="0" disabled="disabled">- <?=$l['CHOOSEYEAR'];?> -</option>
                <?php foreach ($years as $year2) {?>
				<option<?=$year2 == $year ? ' selected="selected"' : '';?>><?=$year2;?></option>
                <?php }?>
			</select>
		</form><br />

		<div class="list-group">
			<a class="list-group-item<?=$tab == 'overview' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=overview"><?=$l['OVERVIEW'];?></a>
			<a class="list-group-item<?=$tab == 'journal' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=journal"><?=$l['JOURNAL'];?></a>
			<a class="list-group-item<?=$tab == 'insert' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=insert"><?=$l['BOOK'];?></a>
			<a class="list-group-item<?=$tab == 'insert_transactions' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=insert_transactions"><?=$l['BOOKT'];?></a>
		</div>

		<div class="list-group">
			<a class="list-group-item<?=$tab == 'depreciation' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=depreciation"><?=$l['DEPRECIATION'];?></a>
			<a class="list-group-item<?=$tab == 'accounts' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=accounts"><?=$l['ACCOUNTS'];?></a>
			<a class="list-group-item<?=$tab == 'ledgers' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=ledgers"><?=$l['LEDGERS'];?></a>
		</div>

		<div class="list-group">
			<a class="list-group-item<?=$tab == 'bwa' ? ' active' : '';?>" href="./?p=fibu&y=<?=$year;?>&t=bwa"><?=$l['BWA'];?></a>
		</div>
	</div>

	<div class="col-md-9">
		<?php if ($tab == "accounts" || $tab == "ledgers") {
        $class = 'Fibu\\' . ucfirst(rtrim($tab, "s"));
        ?>

		<?php
if (!empty($_POST['new_name'])) {
            if (!$class::create($_POST['new_name'])) {
                ?><div class="alert alert-danger">
					<b><?=$lang['GENERAL']['ERROR'];?></b>
                    <?php
if ($tab == "accounts") {
                    echo $l['ACCOUNTEXISTS'];
                } else {
                    echo $l['LEDGEREXISTS'];
                }
                ?>
				</div><?php
} else {unset($_POST);
                alog("fibu", "account_created", $_POST['new_name']);
                ?><div class="alert alert-success">
					<?php
if ($tab == "accounts") {
                    echo $l['ACCOUNTCREATED'];
                } else {
                    echo $l['LEDGERCREATED'];
                }
                ?>
				</div><?php
}
        } else if (isset($_GET['d']) && false !== ($obj = $class::getInstance($_GET['d'])) && $obj->delete()) {
            alog("fibu", "account_deleted", $_GET['d']);
            ?>
			<div class="alert alert-success">
				<?php
if ($tab == "accounts") {
                echo $l['ACCOUNTDELETED'];
            } else {
                echo $l['LEDGERDELETED'];
            }
            ?>
			</div>
			<?php
}
        ?>

		<form method="POST" class="form-inline">
			<input type="text" name="new_name" placeholder="<?=$l['NAME'];?>" value="<?=isset($_POST['new_name']) ? $_POST['new_name'] : "";?>" class="form-control" /> &nbsp;
			<input type="submit" class="btn btn-primary" value="<?=$l['ADDACCOUNT'];?>" />
		</form><br />

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="10%">#</th>
					<th><?=$l['NAME'];?></th>
					<th width="20%"><?=$l['SALDO'];?></th>
					<th width="20px"></th>
				</tr>

				<?php
$accounts = $class::getAll();
        if (count($accounts) == 0) {
            echo "<tr><td colspan=\"4\"><center>{$l['NOACCTS']}</center></td></tr>";
        }

        foreach ($accounts as $a) {
            $sh = "H";
            $saldo = $a->getSaldo();

            if ($saldo < 0) {
                $sh = "S";
                $saldo = abs($saldo);
            }
            ?>
					<tr>
						<td><?=$a->getId();?></td>
						<td><?=$a->getName();?></td>
						<td><?=$nfo->format($saldo);?> <?=$sh;?></td>
						<td><center><?php if ($a->countEntries() == 0) {?><a href="?p=fibu&y=<?=$year;?>&t=<?=$tab;?>&d=<?=$a->getId();?>"><i class="fa fa-times"></i></a><?php } else {echo "-";}?></center></td>
					</tr>
					<?php
}
        ?>
			</table>
		</div>
		<?php } else if ($tab == "depreciation") {?>
        <div class="alert alert-info"><?=$l['UC'];?></div>
		<!--
            Verschiedene Abschreibungsarten beachten!
            Abschreibegut hinzuf&uuml;gen
            Liste (Beschreibung, Ende, Kaufpreis, Restwert, Delete)
        -->
		<?php } else if ($tab == "bwa") {?>
        <div class="alert alert-info"><?=$l['UC'];?></div>
        <!--
		    BWA generieren (aktuelles Gesch&auml;ftsjahr)
        -->
		<?php } else if ($tab == "insert_transactions") {
        if (isset($_POST['ignore']) && is_array($_POST['txs'] ?? "")) {
            $d = 0;

            foreach ($_POST['txs'] as $id) {
                $db->query("UPDATE client_transactions SET fibu = -1 WHERE fibu != -1 AND ID = $id");
                if ($db->affected_rows) {
                    $d++;
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['I1'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%x", $d, $l['IX']) . '</div>';
            }
        }

        $t = new Table("SELECT * FROM client_transactions WHERE time >= $startTime AND time <= $endTime AND fibu = 0 AND waiting = 0", [
            "subject" => [
                "name" => $l['SUBJECT'],
                "type" => "like",
            ],
        ], ["time", "DESC"], "fibu_trans");

        echo $t->getHeader();
        ?>
            <div class="table-responsive"><form method="POST">
                <table class="table table-bordered table-striped">
                    <tr>
                        <th width="25px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
                        <th width="200px"><?=$t->orderHeader("time", $l['DATE']);?></th>
                        <th width="200px"><?=$t->orderHeader("user", $l['CUSTOMER']);?></th>
                        <th><?=$t->orderHeader("subject", $l['SUBJECT']);?></th>
                        <th width="180px"><?=$t->orderHeader("amount", $l['AMOUNT']);?></th>
                        <th width="29px"></th>
                    </tr>

                    <?php
$sql = $t->qry("ID DESC");

        if (!$sql->num_rows) {
            ?>
                    <tr>
                        <td colspan="6"><center><?=$l['NOOPT'];?></center></td>
                    </tr>
                    <?php }
        while ($row = $sql->fetch_object()) {$uI = User::getInstance($row->user, "ID");?>
                    <tr>
                        <td><input type="checkbox" name="txs[]" value="<?=$row->ID;?>" class="checkbox" onchange="javascript:toggle();" /></td>
                        <td><?=$dfo->format($row->time);?></td>
                        <td><?=$uI ? '<a href="?p=customers&edit=' . $row->user . '">' . $uI->getfName() . '</a>' : "-";?></td>
                        <td><?=htmlentities(Transactions::subject($row->subject));?></td>
                        <td><?=$cur->infix($nfo->format($row->amount), $cur->getBaseCurrency());?></td>
                        <td><a href="?p=fibu&y=<?=$year;?>&t=insert&transaction_id=<?=$row->ID;?>"><i class="fa fa-arrow-right"></i></a></td>
                    </tr>
                    <?php }?>
                </table>
                <?=$l['SELECTED'];?>: <input type="submit" name="ignore" value="<?=$l['IGNORE'];?>" class="btn btn-warning" /></form>
            </div>
            <?php
echo $t->getFooter();} else if ($tab == "insert") {
        $date = $description = $amount = $tid = "";

        if (!empty($_GET['transaction_id'])) {
            $tid = intval($_GET['transaction_id']);
            $sql = $db->query("SELECT * FROM client_transactions WHERE ID = $tid AND fibu = 0");
            if ($sql->num_rows) {
                $t = $sql->fetch_object();

                $date = $dfo->format($t->time, "", false);
                $description = Transactions::subject($t->subject) . " ({$l['TX']} $tid, {$l['CUSTOMER']} {$t->user})";
                $amount = $nfo->format(abs($t->amount));
            }
        }

        if (isset($_POST['date'])) {
            try {
                $time = strtotime($_POST['date']);
                if ($time === false || $time < $startTime || $time > $endTime) {
                    throw new Exception("err1");
                }

                $description = trim($_POST['description']);
                if (strlen($description) > 250) {
                    throw new Exception("err2");
                }

                $amount = doubleval($nfo->phpize($_POST['amount'] ?? 0));
                $account = intval($_POST['account']);

                if (!\Fibu\Account::getInstance($account, false)) {
                    throw new Exception("err3");
                }

                $amount2 = doubleval($nfo->phpize($_POST['amount2'] ?? 0));
                $account2 = intval($_POST['account2']);

                if (!\Fibu\Account::getInstance($account2, false)) {
                    throw new Exception("err4");
                }

                $tax = doubleval($nfo->phpize($_POST['tax'] ?? 0));
                $taxacct = intval($_POST['taxacct']);

                if ($tax != 0) {
                    if (!\Fibu\Account::getInstance($taxacct, false)) {
                        throw new Exception("err5");
                    }
                } else {
                    $taxacct = 0;
                }

                if ($amount + $tax != $amount2 && $amount2 + $tax != $amount) {
                    throw new Exception("err6");
                }

                $sql = $db->prepare("INSERT INTO fibu_journal (account, account2, year, month, day, amount, amount2, tax, taxacct, description) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $sql->bind_param("iiiiidddis", $account, $account2, $year, $month = date("m", $time), $day = date("d", $time), $amount, $amount2, $tax, $taxacct, $description);
                $sql->execute();
                $iid = $db->insert_id;
                $sql->close();

                if ($tid) {
                    $db->query("UPDATE client_transactions SET fibu = $iid WHERE ID = $tid");
                }

                echo '<div class="alert alert-success">' . $l['JC'] . '</div>';
            } catch (Exception $ex) {
                echo '<div class="alert alert-danger">' . $l[strtoupper($ex->getMessage())] . '</div>';
            }
        }
        ?>
<form method="POST">
        <div class="form-group">
            <label><?=$l['DATE'];?></label>
            <input type="text" name="date" class="form-control datepicker" value="<?=$_POST['date'] ?? ($date ?: $dfo->format(time(), "", false));?>">
        </div>

        <div class="form-group">
            <label><?=$l['DESCRIPTION'];?></label>
            <input type="text" name="description" class="form-control" value="<?=htmlentities($description);?>" placeholder="<?=$l['OPTIONAL'];?>">
        </div>

        <style>
        .autofiller {
            position: absolute;
            z-index: 1000;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px;
            display: none;
        }

        .acctlist {
            list-style: none;
            margin-left: 0;
            padding-left: 0;
            margin-bottom: 0;
        }

        .acctlist > li {
            margin-left: 0;
            padding-left: 0;
            margin-bottom: 3px;
        }

        .acctlist > li:hover {
            cursor: pointer;
            background-color: yellow;
        }
        </style>

        <label><?=$l['BS'];?></label>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th><?=$l['ACCOUNT'];?></th>
                    <th width="20%"><?=$l['SALDO'];?></th>
                    <th width="15%"><?=$l['SOLL'];?></th>
                    <th width="15%"><?=$l['HABEN'];?></th>
                    <th width="15%"><?=$l['TAX'];?></th>
                </tr>

                <tr>
                    <td><input type="text" name="account" class="form-control account_chooser"><span class="autofiller" id="account_chooser_account"></span></td>
                    <td><span id="saldo_account"></span></td>
                    <td><input type="text" name="amount" value="<?=$amount;?>" class="form-control"></td>
                    <td></td>
                    <td></td>
                </tr>

                <tr>
                    <td><input type="text" name="account2" class="form-control account_chooser"><span class="autofiller" id="account_chooser_account2"></span></td>
                    <td><span id="saldo_account2"></span></td>
                    <td></td>
                    <td><input type="text" name="amount2" value="<?=$amount;?>" class="form-control"></td>
                    <td></td>
                </tr>

                <tr>
                    <td><input type="text" name="taxacct" class="form-control account_chooser"><span class="autofiller" id="account_chooser_taxacct"></span></td>
                    <td><span id="saldo_taxacct"></span></td>
                    <td></td>
                    <td></td>
                    <td><input type="text" name="tax" class="form-control"></td>
                </tr>
            </table>
        </div>

        <input type="submit" class="btn btn-primary btn-block" value="<?=$l['BOOK'];?>">
</form>
        <script>
        var debouncer = null;
        var field = "";

        function bindLi() {
            $(".acctlist > li").unbind("click").click(function() {
                $("[name=" + field + "]").val($(this).html());
                $("#account_chooser_" + field).hide();
                $("#saldo_" + field).html("<i class='fa fa-spinner fa-spin'></i>");

                $.get("?p=ajax&action=fibu_saldo&account=" + encodeURIComponent($(this).html()), function(r) {
                    $("#saldo_" + field).html(r);
                });
            });
        }

        $(".account_chooser").on("keyup", function() {
            clearTimeout(debouncer);
            field = $(this).attr("name");
            $("#account_chooser_" + field).hide();

            if ($(this).val().length >= 2) {
                debouncer = setTimeout(function() {
                    $("#account_chooser_" + field).show().html("<i class='fa fa-spinner fa-spin'></i> <?=$lang['GENERAL']['PLEASEWAIT'];?>...");

                    $.get("?p=ajax&action=fibu_accounts&searchword=" + encodeURIComponent($("[name=" + field + "]").val()), function(r) {
                        $("#account_chooser_" + field).html(r);
                        bindLi();

                        if (r == "") {
                            $("#account_chooser_" + field).hide();
                        }
                    });
                }, 500);
            }
        });
        </script>
		<?php } else if ($tab == "journal") {
        $t = new Table("SELECT * FROM fibu_journal WHERE year = $year", [
            "description" => [
                "name" => $l['DESCRIPTION'],
                "type" => "like",
            ],
        ], ["ID", "DESC"], "fibu_journal");

        echo $t->getHeader();
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="100px"><?=$t->orderHeader("ID", $l['REFERENCE']);?></th>
                    <th width="200px"><?=$t->orderHeader("CONCAT(year,month,day)", $l['DATE']);?></th>
                    <th><?=$t->orderHeader("description", $l['DESCRIPTION']);?></th>
                    <th width="100px"><?=$l['RECEIPT'];?></th>
                    <th width="100px"><?=$t->orderHeader("amount", $l['SOLL']);?></th>
                    <th width="150px"><?=$t->orderHeader("account", $l['SOLLACCT']);?></th>
                    <th width="100px"><?=$t->orderHeader("amount2", $l['HABEN']);?></th>
                    <th width="150px"><?=$t->orderHeader("account2", $l['HABENACCT']);?></th>
                    <th width="100px"><?=$t->orderHeader("tax", $l['TAX']);?></th>
                    <th width="150px"><?=$t->orderHeader("taxacct", $l['TAXACCT']);?></th>
                </tr>

                <?php
$sql = $t->qry("ID DESC");

        if (!$sql->num_rows) {
            ?>
                <tr>
                    <td colspan="10"><center><?=$l['JOURNALEMP'];?></center></td>
                </tr>
                <?php }
        while ($row = $sql->fetch_object()) {?>
                <tr>
                    <td><?=$row->ID;?></td>
                    <td><?=$dfo->format($row->year . "-" . $row->month . "-" . $row->day, "", false);?></td>
                    <td><?=htmlentities($row->description);?></td>
                    <td><center>-</center></td>
                    <td><?=$nfo->format($row->amount);?></td>
                    <td><?=formatAcct($row->account);?></td>
                    <td><?=$nfo->format($row->amount2);?></td>
                    <td><?=formatAcct($row->account2);?></td>
                    <td><?=$nfo->format($row->tax);?></td>
                    <td><?=formatAcct($row->taxacct);?></td>
                </tr>
                <?php }?>
            </table>
        </div>
        <?php
echo $t->getFooter();
    } else { ?>
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-12 text-right">
                                <div class="huge"><?=$nfo->format($having = $db->query("SELECT COUNT(*) c FROM fibu_journal WHERE year = $year")->fetch_object()->c, 0);?></div>
                                <div><?=$l['BSS'];?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-12 text-right">
                                <div class="huge"><?=$nfo->format(max($db->query("SELECT SUM(amount), SUM(amount2), SUM(tax) FROM fibu_journal WHERE year = $year")->fetch_assoc()));?></div>
                                <div><?=$l['BSSUM'];?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-12 text-right">
                                <div class="huge"><?=$nfo->format($db->query("SELECT COUNT(*) c FROM client_transactions WHERE fibu = 0 AND waiting = 0 AND time >= $startTime AND time <= $endTime")->fetch_object()->c, 0);?></div>
                                <div><?=$l['TXS0'];?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-12 text-right">
                                <div class="huge"><?=$nfo->format($db->query("SELECT COUNT(*) c FROM client_transactions WHERE fibu > 0 AND time >= $startTime AND time <= $endTime")->fetch_object()->c, 0);?></div>
                                <div><?=$l['TXS1'];?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-12 text-right">
                                <div class="huge"><?=$nfo->format($db->query("SELECT COUNT(*) c FROM client_transactions WHERE fibu < 0 AND time >= $startTime AND time <= $endTime")->fetch_object()->c, 0);?></div>
                                <div><?=$l['TXSI'];?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-clock-o"></i> <?=$l['LAST'];?></div>
            <div class="panel-body">
                <?php if (!$having) {?>
                <?=$l['JOURNALEMP'];?>
                <?php } else {
        $sql = $db->query("SELECT * FROM fibu_journal WHERE year = $year ORDER BY ID DESC LIMIT 10");
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                <tr>
                    <th width="100px"><?=$l['REFERENCE'];?></th>
                    <th width="200px"><?=$l['DATE'];?></th>
                    <th><?=$l['DESCRIPTION'];?></th>
                    <th width="100px"><?=$l['SOLL'];?></th>
                    <th width="150px"><?=$l['SOLLACCT'];?></th>
                    <th width="100px"><?=$l['HABEN'];?></th>
                    <th width="150px"><?=$l['HABENACCT'];?></th>
                    <th width="100px"><?=$l['TAX'];?></th>
                    <th width="150px"><?=$l['TAXACCT'];?></th>
                </tr>

                <?php
while ($row = $sql->fetch_object()) {?>
                <tr>
                    <td><?=$row->ID;?></td>
                    <td><?=$dfo->format($row->year . "-" . $row->month . "-" . $row->day, "", false);?></td>
                    <td><?=htmlentities($row->description);?></td>
                    <td><?=$nfo->format($row->amount);?></td>
                    <td><?=formatAcct($row->account);?></td>
                    <td><?=$nfo->format($row->amount2);?></td>
                    <td><?=formatAcct($row->account2);?></td>
                    <td><?=$nfo->format($row->tax);?></td>
                    <td><?=formatAcct($row->taxacct);?></td>
                </tr>
                <?php }?>
            </table>
                </div><?php }?>
            </div>
            <div class="panel-footer">
                <a href="?p=fibu&y=<?=$year;?>&t=journal">
                    <i class="fa fa-arrow-right"></i>
                    <?=$l['JOURNAL'];?>
                </a>
            </div>
        </div>
		<?php }?>
	</div>
</div>

<?php }?>