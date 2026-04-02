<?php 
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$l = $lang['ENCASHMENT'];
title($l['TITLE']);
menu("payments");

$inv = new Invoice;
if(!$ari->check(13) || !$inv->load($_GET['invoice'])){ require __DIR__ . "/error.php"; if(!$ari->check(13)) alog("general", "insufficient_page_rights", "encashment"); } else {

if(isset($_GET['status'])) die($inv->encashmentStatus());

$status_color = Array("0" => "red", "1" => "green", "2" => "black", "3" => "black");
$status_text = Array("0" => $l['S0'], "1" => $l['S1'], "2" => $l['S2'], "3" => $l['S3']);
?>
<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE']; ?> <small><?=$inv->getInvoiceNo(); ?></small></h1>
	</div>
</div>

<?php if($inv->getStatus() == 1 && !$inv->isEncashment()){ echo '<div class="alert alert-info">' . $l['AP'] . '</div>'; } else if($inv->getStatus() == 2 && !$inv->isEncashment()) { echo '<div class="alert alert-info">' . $l['IC'] . '</div>'; } else if($inv->getStatus() == 3 && !$inv->isEncashment()) { echo '<div class="alert alert-info">' . $l['ID'] . '</div>'; } else { 

if(!$inv->isEncashment() && isset($_POST['encashment_provider'])){
	try {
		if(!array_key_exists($_POST['encashment_provider'], EncashmentHandler::getDrivers())) throw new Exception($l['ERR1']);
		if(empty($_POST['reason'])) throw new Exception($l['ERR2']);
		$res = $inv->claimEncashment($_POST['encashment_provider'], $_POST['reason'], $_POST['note']);
		if(!$res[0]) throw new Exception($res[1]);
		alog("invoice", "encashment_transfer", $inv->getID(), $_POST['encashment_provider']);
		echo '<div class="alert alert-success">' . $l['SUC'] . '</div>';
		$inv->load($inv->getId());
	} catch (Exception $ex) {
		echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
	}
}

?>

<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingOne">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
          <?=$l['II']; ?>
        </a>
      </h4>
    </div>
    <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
      <div class="panel-body">
        <div class="table-responsive" style="margin-bottom: 0;">
			<table class="table table-bordered table-striped" style="margin-bottom: 0;">
				<tr>
					<th style="width: 30%;"><?=$l['DATE']; ?></th>
					<td><?=$dfo->format($inv->getDate(), false); ?></td>
				</tr>

				<tr>
					<th><?=$l['STATUS']; ?></th>
					<td><font color="<?=$status_color[$inv->getStatus()]; ?>"><?=$status_text[$inv->getStatus()]; ?></font></td>
				</tr>

				<tr>
					<th><?=$l['NET']; ?></th>
					<td><?=$cur->infix($nfo->format($inv->getNet()), $cur->getBaseCurrency()); ?></td>
				</tr>

				<tr>
					<th><?=$l['GROSS']; ?></th>
					<td><?=$cur->infix($nfo->format($inv->getGross()), $cur->getBaseCurrency()); ?></td>
				</tr>

				<?php if($inv->isEncashment()){ ?>
				<tr>
					<th><?=$l['ENCCOM']; ?></th>
					<td><?=array_key_exists($inv->getEncashmentProvider(), EncashmentHandler::getDrivers()) ? EncashmentHandler::getDrivers()[$inv->getEncashmentProvider()]->getName() : "<i>{$l['UNKNOWN']}</i>"; ?></td>
				</tr>

				<tr>
					<th><?=$l['ENCSTA']; ?></th>
					<td id="status"><i class="fa fa-spinner fa-spin"></i> <?=$l['PW']; ?></td>
				</tr>

				<tr>
					<th><?=$l['AZ']; ?></th>
					<td><?=$inv->getEncashmentFile(); ?></td>
				</tr>
				<script>
				$(document).ready(function() {
					$.get("?p=encashment&invoice=<?=$_GET['invoice']; ?>&status=1", function(r){
						$("#status").html(r);
					});
				});
				</script>
				<?php } ?>
			</table>
		</div>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingTwo">
      <h4 class="panel-title">
        <a class="collapsed" role="button" data-toggle="collapse" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
				<?=$l['POS']; ?>
        </a>
      </h4>
    </div>
    <div id="collapseTwo" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingTwo">
      <div class="panel-body">
        <div class="table-responsive" style="margin-bottom: 0;">
			<table class="table table-bordered table-striped" style="margin-bottom: 0;">
				<tr>
					<th><?=$l['BEZ']; ?></th>
					<th width="100px"><?=$l['NET']; ?></th>
					<th width="100px"><?=$l['GROSS']; ?></th>
				</tr>

				<?php foreach($inv->getItems() as $item){ $d = true; ?>
				<tr>
					<td><?=$item->getDescription(); ?></td>
					<td><?=$cur->infix($nfo->format($item->getNet()), $cur->getBaseCurrency()); ?></td>
					<td><?=$cur->infix($nfo->format($item->getGross()), $cur->getBaseCurrency()); ?></td>
				</tr>
				<?php } if(!isset($d)){ ?>
				<tr><td colspan="3"><center><?=$l['NPOS']; ?></center></td></tr>
				<?php } ?>
			</table>
		</div>
      </div>
    </div>
  </div>
  <?php if(!$inv->isEncashment()){ ?><div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingThree">
      <h4 class="panel-title">
        <a class="collapsed" role="button" data-toggle="collapse" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
				<?=$l['TFC']; ?>
        </a>
      </h4>
    </div>
    <div id="collapseThree" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingThree">
      <div class="panel-body">
        <form method="POST">
        	<div class="form-group">
				<label><?=$l['ENCCOM']; ?></label>
				<select name="encashment_provider" class="form-control">
					<?php foreach(EncashmentHandler::getDrivers() as $short => $obj){ ?>
					<option value="<?=$short; ?>"<?=isset($_POST['encashment_provider']) && $_POST['encashment_provider'] == $short ? ' selected="selected"' : ""; ?>><?=$obj->getName(); ?></option>
					<?php } ?>
				</select>
			</div>

			<div class="form-group">
				<label><?=$l['REASON']; ?></label>
				<input type="text" name="reason" value="<?=isset($_POST['reason']) ? $_POST['reason'] : ""; ?>" placeholder="<?=$l['REASONP']; ?>" class="form-control" maxlength="255" />
			</div>

			<div class="form-group">
				<label><?=$l['MSG']; ?></label>
				<textarea name="note" placeholder="<?=$l['MSGP']; ?>" class="form-control" style="height: 100px; resize: none"><?=isset($_POST['note']) ? $_POST['note'] : ""; ?></textarea>
			</div>

			<input type="submit" class="btn btn-primary btn-block" value="<?=$l['DO']; ?>" />
		</form>
      </div>
    </div>
  </div><?php } ?>
</div>
<?php } } ?>