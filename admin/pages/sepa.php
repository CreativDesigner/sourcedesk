<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SEPADD'];
title($l['TITLE']);
menu("payments");

if (!$ari->check(7)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "sepa");} else {
    if (isset($_POST["hbci"])) {
        $acc = intval($_POST["hbci"]);
        $sql = $db->query("SELECT account, credentials FROM payment_accounts WHERE bank = 'hbci' AND ID = $acc");
        if (!$sql->num_rows) {
            die("Account invalid");
        }

        $info = $sql->fetch_object();
        $account_nr = $info->account;

        $info = unserialize(decrypt($info->credentials));
        $info = array_map("base64_decode", $info);

        $options = new \Fhp\FinTsOptions;
        $options->url = $info["HBCI-URL"] ?? "";
        $options->bankCode = $info["Bankleitzahl"] ?? "";
        $options->productName = '7C066410D3E77C97ACA0EBC9E';
        $options->productVersion = $CFG['VERSION'];
        $credentials = \Fhp\Credentials::create($info["HBCI-Benutzer"] ?? "", $info["HBCI-PIN"] ?? "");

        if (isset($_POST['tan'])) {
            $_SESSION['sepa_persist'] = base64_encode(serialize([$persistedFints, $persistedAction]));
            list($persistedFints, $persistedAction) = unserialize(base64_decode($_SESSION['sepa_persist']));
            $fints = new \Fhp\FinTsNew($options, $credentials, $persistedFints);
            $action = unserialize($persistedAction);

            $fints->submitTan($action, $_POST['tan']);
            $fints->end();

            foreach ($_SESSION['transactions'] as $id) {
                $db->query("UPDATE client_transactions SET sepa_done = 1 WHERE ID = " . intval($id));
                alog("sepa", "sepa_to_bank", $id);
            }
            $_SESSION['transactions'] = [];

            die("<center><h3 style=\"margin-top: 10px;\"><i class=\"fa fa-check\"></i> " . $l['SUCCESSFUL'] . "</h3></center>");
        }

        $fints = new \Fhp\FinTsNew($options, $credentials);

        $tan = array_shift($fints->getTanModes());
        $medium = null;
        if ($tan->needsTanMedium()) {
            $medium = array_shift($fints->getTanMedia($tan));
        }

        $fints->selectTanMode($tan, $medium);

        $login = $fints->login();
        if ($login->needsTan()) {
            die("Login TAN not supported");
        }

        $sdd = new AbcAeffchen\Sephpa\SephpaDirectDebit($CFG['PAGENAME'], $id = "haseDESK-" . time(), AbcAeffchen\Sephpa\SephpaDirectDebit::SEPA_PAIN_008_002_02);

        $collections = [];

        $d = 0;
        foreach ($_SESSION['transactions'] as $id) {
            $t = SepaDirectDebit::transaction($id);
            if (!$t) {
                continue;
            }

            alog("sepa", "xml_download", $id);

            $m = $t->getMandate();

            $due = date("Y-m-d", $t->getDueDate());

            if (array_key_exists($due, $collections)) {
                $sddCollection = $collections[$due];
            } else {
                $sddCollection = $collections[$due] = $sdd->addCollection([
                    "pmtInfId" => $id,
                    "lclInstrm" => AbcAeffchen\SepaUtilities\SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
                    "seqTp" => AbcAeffchen\SepaUtilities\SepaUtilities::SEQUENCE_TYPE_RECURRING,
                    "cdtr" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'sender'")->fetch_object()->value) ?: "",
                    "iban" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'iban'")->fetch_object()->value) ?: "",
                    "bic" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'bic'")->fetch_object()->value) ?: "",
                    "ci" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'ci'")->fetch_object()->value) ?: "",
                    "reqdColltnDt" => $due,
                ]);
            }

            $sddCollection->addPayment([
                "pmtId" => $t->getTID(),
                "instdAmt" => $t->getAmount(),
                "mndtId" => $m->getID(),
                "dtOfSgntr" => $m->getDate(),
                "bic" => str_replace(" ", "", $m->getBIC()),
                "iban" => str_replace(" ", "", $m->getIBAN()),
                "dbtr" => $m->getAccountHolder(),
                "rmtInf" => $t->getSubject(),
            ]);

            $d++;
        }

        if (!$d) {
            die("No transactions");
        }

        $xml = $sdd->generateXml();

        $getSepaAccounts = \Fhp\Action\GetSEPAAccounts::create();
        $fints->execute($getSepaAccounts);

        if ($getSepaAccounts->needsTan()) {
            die("Account list TAN not supported");
        }

        $account = null;

        foreach ($getSepaAccounts->getAccounts() as $acc) {
            if ($account_nr == $acc->getAccountNumber()) {
                $account = $acc;
            }
        }

        if (!$account) {
            die("Account unknown");
        }

        $sendDebit = \Fhp\Action\SendSEPADirectDebit::create($account, $xml);
        $fints->execute($sendDebit);

        if ($sendDebit->needsTan()) {
            $persistedAction = serialize($sendDebit);
            $persistedFints = $fints->persist();

            $_SESSION['sepa_persist'] = base64_encode(serialize([$persistedFints, $persistedAction]));

            die('<div class="form-group" style="margin-bottom: 5px;"><label>' . $l['TAN'] . '</label><input type="text" id="hbci_tan" class="form-control"></div><script>$("#hbci_now").prop("disabled", false).unbind("click").click(function() {
                $("#hbci_body").html(\'<center><h3 style="margin-top: 10px;"><i class="fa fa-spinner fa-spin"></i> ' . $lang['GENERAL']['PLEASEWAIT'] . '...</h3></center>\');
                $(this).prop("disabled", true);

                $.post("", {
                    "csrf_token": "' . CSRF::raw() . '",
                    "hbci": account,
                    "tan": $("#hbci_tan").val()
                }, function(r) {
                    $("#hbci_body").html(r);
                });
            });</script>');
        }

        $fints->end();

        foreach ($_SESSION['transactions'] as $id) {
            $db->query("UPDATE client_transactions SET sepa_done = 1 WHERE ID = " . intval($id));
            alog("sepa", "sepa_to_bank", $id);
        }
        $_SESSION['transactions'] = [];

        die("<center><h3 style=\"margin-top: 10px;\"><i class=\"fa fa-check\"></i> " . $l['SUCCESSFUL'] . "</h3></center>");
    }

    $g = $gateways->get()['sdd'];

    if (isset($_GET['download']) && !$_SESSION['conflicts'] && is_array($_SESSION['transactions']) && count($_SESSION['transactions']) > 0) {
        $sdd = new AbcAeffchen\Sephpa\SephpaDirectDebit($CFG['PAGENAME'], $id = "haseDESK-" . time(), AbcAeffchen\Sephpa\SephpaDirectDebit::SEPA_PAIN_008_002_02);

        $collections = [];

        $d = 0;
        foreach ($_SESSION['transactions'] as $id) {
            $t = SepaDirectDebit::transaction($id);
            if (!$t) {
                continue;
            }

            alog("sepa", "xml_download", $id);

            $m = $t->getMandate();

            $due = date("Y-m-d", $t->getDueDate());

            if (array_key_exists($due, $collections)) {
                $sddCollection = $collections[$due];
            } else {
                $sddCollection = $collections[$due] = $sdd->addCollection([
                    "pmtInfId" => $id,
                    "lclInstrm" => AbcAeffchen\SepaUtilities\SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
                    "seqTp" => AbcAeffchen\SepaUtilities\SepaUtilities::SEQUENCE_TYPE_RECURRING,
                    "cdtr" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'sender'")->fetch_object()->value) ?: "",
                    "iban" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'iban'")->fetch_object()->value) ?: "",
                    "bic" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'bic'")->fetch_object()->value) ?: "",
                    "ci" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'ci'")->fetch_object()->value) ?: "",
                    "reqdColltnDt" => $due,
                ]);
            }

            $sddCollection->addPayment([
                "pmtId" => $t->getTID(),
                "instdAmt" => $t->getAmount(),
                "mndtId" => $m->getID(),
                "dtOfSgntr" => $m->getDate(),
                "bic" => str_replace(" ", "", $m->getBIC()),
                "iban" => str_replace(" ", "", $m->getIBAN()),
                "dbtr" => $m->getAccountHolder(),
                "rmtInf" => $t->getSubject(),
            ]);

            $d++;
        }

        if ($d) {
            $time = time();
            $_SESSION['download'] = true;

            header('Content-type: text/xml');
            header('Content-Disposition: attachment; filename="haseDESK-' . $time . '.xml"');
            die($sdd->generateXml());
        }
    }

    ?>
<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE'];?></h1>

		<?php
if (isset($_GET['ok'])) {
        if (!isset($_SESSION['download']) || !$_SESSION['download'] || !is_array($_SESSION['transactions']) || count($_SESSION['transactions']) == 0) {
            echo '<div class="alert alert-danger">' . $l['ERR1'] . '</div>';
        } else {
            foreach ($_SESSION['transactions'] as $id) {
                $db->query("UPDATE client_transactions SET sepa_done = 1 WHERE ID = " . intval($id));
                alog("sepa", "xml_finish", $id);
            }
        }
    }

    if (isset($_GET['r']) && is_object($sql = $db->query("SELECT ID, amount, user FROM client_transactions WHERE sepa_done = 0 AND ID = " . intval($_GET['r']))) && $sql->num_rows == 1) {
        $info = $sql->fetch_object();
        $uI = User::getInstance($info->user, "ID");
        if ($uI) {
            $db->query("DELETE FROM client_transactions WHERE ID = " . $info->ID);
            $uI->set(array("credit" => $uI->get()['credit'] - $info->amount));
            alog("sepa", "transaction_revert", $info->ID, $info->amount, $uI->get()['ID']);
            echo '<div class="alert alert-success">' . $l['REVERSED'] . ' ' . $cur->infix($nfo->format($uI->get()['credit']), $cur->getBaseCurrency()) . ' [ <a href="?p=customers&edit=' . $info->user . '"">' . $l['CUSTPROF'] . '</a> ]</div>';
        }
    }

    $conflicts = false;
    $_SESSION['transactions'] = array();
    $sql = $db->query("SELECT * FROM client_transactions WHERE sepa_done = 0 ORDER BY time ASC, ID ASC");
    if ($sql->num_rows == 0) {
        echo $l['NT'];
    } else {
        ?>
			<p style="text-align: justify;"><?=$l['INTRO'];?></p>

			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tr>
						<th width="15%"><?=$l['TIME'];?></th>
						<th width="15%"><?=$l['DUE'];?></th>
						<th><?=$l['CUST'];?></th>
						<th width="10%"><?=$l['TID'];?></th>
						<th width="10%"><?=$l['MANDATE'];?></th>
						<th width="15%"><?=$l['AMOUNT'];?></th>
						<th width="30px"></th>
					</tr>

                    <?php
$sum = 0;
        while ($row = $sql->fetch_object()) {
            $t = SepaDirectDebit::transaction($row->ID);
            $m = $t->getMandate();
            $uI = User::getInstance($row->user, "ID");
            array_push($_SESSION['transactions'], $row->ID);
            $sum += $row->amount;
            ?>
						<tr>
							<td><?=$dfo->format($row->time);?></td>
							<td><?=$dfo->format($t->getDueDate(), false);?></td>
							<td>
								<?php if ($uI) {?><a href="?p=customers&edit=<?=$row->user;?>"><?=$uI->getfName();?></a><?php } else { $conflicts = true;?><font color="red"><?=$l['UK'];?></font><?php }?>
							</td>
							<td><?=$t->getTID();?></td>
							<td>
								<?php if ($m) {?><a href="?p=customers_sepa&id=<?=$row->user;?>&mandate=<?=$m->getID();?>"><?=$m->getID();?></a> <?php if (!$m->isActive()) {$conflicts = true;if ($m->expired()) {
                echo '<font color="red">(' . $l['EXPIRED'] . ')</font>';
            } else {
                echo '<font color="red">(' . $l['INACTIVE'] . ')</font>';
            }
            }?><?php } else { $conflicts = true;?><font color="red"><?=$l['UK'];?></font><?php }?>
							</td>
							<td><?=$cur->infix($nfo->format($row->amount), $cur->getBaseCurrency());?></td>
							<td><a href="?p=sepa&r=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
						</tr>
						<?php
}

        $_SESSION['conflicts'] = $conflicts;
        ?>
        <tr>
            <th colspan="5" style="text-align: right;"><?=$l['SUM'];?></th>
            <th><?=$cur->infix($nfo->format($sum), $cur->getBaseCurrency());?></th>
            <th></th>
        </tr>
				</table>
			</div>

        <a href="#" data-toggle="modal" data-target="#hbci" class="btn btn-primary"<?php if ($conflicts) {
            echo ' disabled="disabled"';
        }
        ?>><?=$l['TRANSFER'];?></a>
            <div class="modal fade" id="hbci" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$l['TRANSFER'];?></h4>
      </div>
      <div class="modal-body" id="hbci_body">
        <?php
$sql = $db->query("SELECT ID, account FROM payment_accounts WHERE bank = 'hbci' ORDER BY account ASC");
        if (!$sql->num_rows) {
            $cc = false;
            echo $l['NOHBCI'];
        } else {
            $cc = true;
            echo '<div class="form-group" style="margin-bottom: 5px;"><label>' . $l['ACCOUNT'] . '</label><select id="hbci_account" class="form-control">';

            while ($row = $sql->fetch_object()) {
                echo '<option value="' . $row->ID . '">' . htmlentities($row->account) . '</option>';
            }

            echo '</select></div>';
        }
        ?>
      </div>
      <?php if ($cc) {?>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="hbci_now"><?=$l['TRANSFERNOW'];?></button>

        <script>
        $("#hbci_now").click(function() {
            account = $("#hbci_account").val();
            $("#hbci_body").html('<center><h3 style="margin-top: 10px;"><i class="fa fa-spinner fa-spin"></i> <?=$lang['GENERAL']['PLEASEWAIT'];?>...</h3></center>');
            $(this).prop("disabled", true);

            $.post("", {
                "csrf_token": "<?=CSRF::raw();?>",
                "hbci": account
            }, function(r) {
                $("#hbci_body").html(r);
            });
        });
        </script>
      </div>
      <?php }?>
    </div>
  </div>
</div>
        <a href="?p=sepa&download=1" target="_blank" class="btn btn-default"<?php if ($conflicts) {
            echo ' disabled="disabled"';
        }
        ?>><?=$l['DOWNLOAD'];?></a> <a href="?p=sepa&ok=1" class="btn btn-success"<?php if ($conflicts) {
            echo ' disabled="disabled"';
        }
        ?>><?=$l['DONE'];?></a>
			<?php
}
    ?>
	</div>
</div>
<?php }