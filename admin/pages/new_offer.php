<?php 
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$l = $lang['NEW_OFFER'];
title($l['TITLE']);
menu("products");

if(!$ari->check(24)){ require __DIR__ . "/error.php"; alog("general", "insufficient_page_rights", "new_offer"); } else {

if(isset($_POST['start'])){
	try {
		$title = $old = $price = $url = Array();
			
		foreach($languages as $s => $l2){
			if(empty($_POST['title_' . $s]) || empty($_POST['price_' . $s]) || empty($_POST['old_price_' . $s]) || empty($_POST['url_' . $s])) throw new Exception($l['ERR1']);
			$title[$s] = $_POST['title_' . $s];
			$price[$s] = $_POST['price_' . $s];
			$old[$s] = $_POST['old_price_' . $s];
			$url[$s] = $_POST['url_' . $s];
		}

		if(empty($_POST['start']) || strtotime(($_POST['start'])) === false)
			throw new Exception($l['ERR2']);

		if(empty($_POST['end']) || strtotime(($_POST['end'])) === false)
			throw new Exception($l['ERR3']);

		if(strtotime(($_POST['end'])) < strtotime(($_POST['start'])))
			throw new Exception($l['ERR4']);

		$status = isset($_POST['status']) && $_POST['status'] == "1" ? 1 : 0;

		$db->query("INSERT INTO offers (`title`, `start`, `end`, `status`, `old_price`, `price`, `url`) VALUES ('" . $db->real_escape_string(serialize($title)) . "', '" . date("Y-m-d", strtotime($_POST['start'])) . "', '" . date("Y-m-d", strtotime($_POST['end'])) . "', $status, '" . $db->real_escape_string(serialize($old)) . "', '" . $db->real_escape_string(serialize($price)) . "', '" . $db->real_escape_string(serialize($url)) . "')");
	
		alog("offer", "created", $db->insert_id);

		$error = "<div class=\"alert alert-success\">{$l['SUC']}</div>";
		unset($_POST);
	} catch (Exception $ex) {
		$error = "<div class=\"alert alert-danger\"><b>{$lang['GENERAL']['ERROR']}</b> " . $ex->getMessage() . "</div>";
	}
	
}

?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE']; ?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if(isset($error)) echo $error; ?>
	<form role="form" method="POST">

	<ul class="nav nav-pills" role="tablist">
	    <?php $i = 0; foreach($languages as $s => $l2){ ?>
	    <li<?php if($i++ == 0){ ?> class="active"<?php } ?>><a href="#<?=$s; ?>" aria-controls="<?=$s; ?>" role="tab" data-toggle="tab"><?=$l2; ?></a></li>
	    <?php } ?>
	</ul>

  <div class="tab-content">
  <?php $i = 0; foreach($languages as $s => $l2){ ?>
  <div role="tabpanel" class="tab-pane<?php if($i++ == 0) echo ' active'; ?>" id="<?=$s; ?>">
  <br />
  <div class="form-group">
    <label><?=$l['TITLE2']; ?></label>
	<input type="text" name="title_<?=$s; ?>" value="<?=isset($_POST['title_' . $s]) ? $_POST['title_' . $s] : ""; ?>" placeholder="<?=$l['TITLE2P']; ?>" class="form-control" maxlength="255">
	<p class="help-block"><?=$l['TITLE2H']; ?></p>
  </div>

  <div class="form-group">
    <label><?=$l['URL']; ?></label>
	<input type="text" name="url_<?=$s; ?>" value="<?=isset($_POST['url_' . $s]) ? $_POST['url_' . $s] : ""; ?>" placeholder="<?=$CFG['PAGEURL']; ?>offer" class="form-control" maxlength="255">
	<p class="help-block"><?=$l['URLH']; ?></p>
  </div>

  <div class="form-group">
    <label><?=$l['OPRICE']; ?></label>
	<input type="text" name="old_price_<?=$s; ?>" value="<?=isset($_POST['old_price_' . $s]) ? $_POST['old_price_' . $s] : ""; ?>" placeholder="<?=$l['OPRICEP']; ?>" class="form-control" maxlength="255">
  </div>

  <div class="form-group">
    <label><?=$l['APRICE']; ?></label>
	<input type="text" name="price_<?=$s; ?>" value="<?=isset($_POST['price_' . $s]) ? $_POST['price_' . $s] : ""; ?>" placeholder="<?=$l['APRICEP']; ?>" class="form-control" maxlength="255">
  </div></div>
  <?php } ?></div>
  <hr />

  <div class="form-group" style="position: relative;">
    <label><?=$l['START']; ?></label>
	<input type="text" style="max-width:150px" name="start" value="<?=isset($_POST['start']) ? $_POST['start'] : ""; ?>" placeholder="<?=$dfo->placeholder(false); ?>" class="form-control datepicker">
  </div>

  <div class="form-group" style="position: relative;">
    <label><?=$l['END']; ?></label>
	<input type="text" style="max-width:150px" name="end" value="<?=isset($_POST['end']) ? $_POST['end'] : ""; ?>" placeholder="<?=$dfo->placeholder(false); ?>" class="form-control datepicker">
  </div>
  
  <div class="form-group">
    <label><?=$l['STATUS']; ?></label><br />
	<label class="radio-inline">
		<input type="radio" name="status" value="0" <?=isset($_POST['status']) && $_POST['status'] == "0" ? "checked" : (!isset($_POST['status']) ? "checked" : ""); ?>>
		<?=$l['STATUSO']; ?>
	</label>
	<label class="radio-inline">
		<input type="radio" name="status" value="1" <?=isset($_POST['status']) && $_POST['status'] == "1" ? "checked" : ""; ?>>
		<?=$l['STATUSA']; ?>
	</label>
  </div>
  
  <center><button type="submit" class="btn btn-primary btn-block"><?=$l['ADD']; ?></button></center></form>	

<?php } ?>