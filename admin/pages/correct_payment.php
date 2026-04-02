<?php
$l = $lang['CORRECT_PAYMENT'];
title($l['TITLE']);
menu("payments");

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if(!$ari->check(33)){ require __DIR__ . "/error.php"; alog("general", "insufficient_page_rights", "correct_payment"); } else {

$sql = $db->query("SELECT * FROM csv_import WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'");
if($sql->num_rows != 1){
	$hardcoreError = $l['ERR1'];
} else {
	$info = $sql->fetch_object();

	if($info->done != 0 || count($transactions->get(Array("subject" => "transfer|" . $info->transactionId))) > 0)
		$hardcoreError = $l['ERR2'];
}

// Save transaction
if(isset($_POST['insert']) && !isset($hardcoreError)){
	try {
		$amount = $nfo->phpize($_POST['amount']);
		if(!isset($_POST['amount']) || !is_numeric($amount))
			throw new Exception($l['ERR3']);
		
		if ($_POST['type'] == "inv") {
			$_POST['customer'] = 0;
			
			$inv = new Invoice;
			if ($inv->load($_POST['invoice'])) {
				$_POST['customer'] = $inv->getClient();
			}
		}

		$uSql = $db->query("SELECT firstname, lastname FROM clients WHERE ID = '" . $db->real_escape_string($_POST['customer']) . "'");
		if($uSql->num_rows != 1)
			throw new Exception($l['ERR4']);
		
		$clientId = $_POST['customer'];
		
		$db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($amount) . "' WHERE ID = '" . $db->real_escape_string($clientId) . "' LIMIT 1");

		$transactions->insert("transfer", $info->transactionId, $amount, $clientId);
		
		$sql = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($clientId) . "' LIMIT 1");
		$userinfo = $sql->fetch_object();
		
		$betrag = $nfo->format($amount);

		$mtObj = new MailTemplate("Guthabenaufladung");
		$titlex = $mtObj->getTitle($CFG['LANG']);
		$mail = $mtObj->getMail($CFG['LANG'], $userinfo->firstname . " " . $userinfo->lastname);
		
		$maq->enqueue([
			"amount" => $betrag . ' €', # TODO
			"processor" => $l['GATEWAY'],
		], $mtObj, $userinfo->mail, $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userinfo->ID, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));
		
		$db->query("UPDATE csv_import SET done = 1, clientId = '" . $db->real_escape_string($clientId) . "' WHERE done = 0 AND ID = " . $info->ID . " LIMIT 1");
		$suc = 1;

		$user = new User($userinfo->mail);
		$user->applyCredit();

		alog("transaction", "inserted", $info->ID, $clientId);
	} catch (Exception $ex) {
		$error = $ex->getMessage();
	}
}

?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE2']; ?> <?php if($info->transactionId != "-" && $info->transactionId != ""){ ?><small><?=$info->transactionId; ?></small><?php } ?></h1>

			<?=isset($error) ? "<div class='alert alert-danger'>$error</div>" : ""; ?>
			
			<?php
			if(isset($hardcoreError)){
				echo '<div class="alert alert-danger">' . $hardcoreError . '</div>';
			} else if (isset($suc)) { ?>
			<div class="alert alert-success"><?=$l['SUC']; ?></div>
			<?php
			} else {
			?>
			
			<form method="POST">
			
			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tr>
						<td width="40%"><b><?=$l['DATE']; ?></b></td>
						<td><?=$dfo->format($info->time, false); ?></td>
					</tr>
					
					<tr>
						<td><b><?=$l['SUBJECT']; ?></b></td>
						<td><?=$info->subject; ?></td>
					</tr>
					
					<tr>
						<td><b><?=$l['SENDER']; ?></b></td>
						<td><?=$info->sender; ?></td>
					</tr>
					
					<tr>
						<td><b><?=$l['AMOUNT']; ?></b></td>
						<td><input style="max-width: 80px;" type="text" name="amount" value="<?=isset($_POST['amount']) ? $_POST['amount'] : $nfo->format($info->amount); ?>" class="form-control"></td>
					</tr>

					<tr>
						<td><b><?=$l['TYPE']; ?></b></td>
						<td>
							<label class="radio-inline">
								<input type="radio" name="type" value="cust"<?=!isset($_POST['type']) || $_POST['type'] == "cust" ? ' checked=""' : ''; ?>> <?=$l['CUST']; ?>
							</label>
							<label class="radio-inline">
								<input type="radio" name="type" value="inv"<?=isset($_POST['type']) && $_POST['type'] == "inv" ? ' checked=""' : ''; ?>> <?=$l['INV']; ?>
							</label>
						</td>
					</tr>

					<script>
					$("[name=type]").click(function() {
						$("#choose_inv").hide();
						$("#choose_cust").hide();

						$("[name=type]").each(function() {
							if ($(this).is(":checked")) {
								$("#choose_" + $(this).val()).show();
							}
						});
					});
					</script>
					
					<tr id="choose_cust" style="background-color: #fff;<?=isset($_POST['type']) && $_POST['type'] == "inv" ? ' display: none;' : ''; ?>">
						<td style="background-color: #fff;"><b><?=$l['CUST']; ?></b></td>
						<td style="background-color: #fff;">
							<div>
								<input type="text" class="form-control customer-input" placeholder="<?=$l['CUSTP']; ?>" value="<?=ci(!empty($_POST['customer']) ? $_POST['customer'] : "0"); ?>">
								<input type="hidden" name="customer" value="<?=!empty($_POST['customer']) ? $_POST['customer'] : "0"; ?>">
								<div class="customer-input-results"></div>
							</div>
						</td>
					</tr>

					<tr id="choose_inv"<?=!isset($_POST['type']) || $_POST['type'] == "cust" ? ' style="display: none;"' : ''; ?>>
						<td><b><?=$l['INV']; ?></b></td>
						<td>
							<div>
								<input type="text" class="form-control invoice-input" placeholder="<?=$l['SEARCHINV']; ?>" value="<?=ii(!empty($_POST['invoice']) ? $_POST['invoice'] : "0"); ?>">
								<input type="hidden" name="invoice" value="<?=!empty($_POST['invoice']) ? $_POST['invoice'] : "0"; ?>">
								<div class="invoice-input-results"></div>
							</div>
						</td>
					</tr>
				</table>
			</div>
			
			<center><input type="submit" value="<?=$l['DO']; ?>" class="btn btn-primary btn-block" name="insert"></center><br />
			</form><?php } ?></div></div><?php } ?>