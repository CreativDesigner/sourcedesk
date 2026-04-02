<?php 
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$l = $lang['BUNDLES'];
title($l['CREATE']);
menu("products");

if(!$ari->check(24)){ require __DIR__ . "/error.php"; alog("general", "insufficient_page_rights", "new_bundle"); } else {

if(isset($_POST['amount'])){
	try {
		$n = Array();
		foreach($languages as $k => $v){
			if(empty($_POST['name_' . $k])) throw new Exception(str_replace("%v", $v, $l['ERR1']));
			$n[$k] = $_POST['name_' . $k];
		}

		$a = $nfo->phpize($_POST['amount']);
		if(empty($a) || (doubleval($a) != $a && intval($a) != $a) || doubleval($a) < 0) throw new Exception($l['ERR2']);

        if(!isset($_POST['affiliate']) || trim($_POST['affiliate']) == ""){
          $affiliate = -1;
        } else {
          $affiliate = $nfo->phpize($_POST['affiliate']);
          if((!is_numeric($affiliate) && !is_double($affiliate)) || $affiliate < 0) throw new Exception($l['ERR3']);
        }

		$p = $_POST['product'];
		if(empty($p) || !is_array($p) || count($p) < 1) throw new Exception($l['ERR4']);

		$db->query("INSERT INTO product_bundles (`name`, `products`, `price`, `affiliate`) VALUES ('" . $db->real_escape_string(serialize($n)) . "', '" . $db->real_escape_string(serialize($p)) . "', " . doubleval($a) . ", " . doubleval($affiliate) . ")");
		alog("bundle", "created", $db->insert_id);
		$msg = $l['CREATED'] . " [ <a href='?p=bundles'>{$l['OVERVIEW']}</a> ]";
		unset($_POST);
	} catch (Exception $ex) {
		$err = $ex->getMessage();
	}
}

?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['CREATET']; ?></h1>

        <?php if(isset($err)){ ?><div class="alert alert-danger"><b><?=$l['GENERAL']['ERROR']; ?></b> <?=$err; ?></div><?php } ?>
        <?php if(isset($msg)){ ?><div class="alert alert-success"><?=$msg; ?></div><?php } ?>

        <form role="form" method="POST">
        	<div class="form-group">
        		<label><?=$l['BN']; ?></label> <?php foreach($languages as $lang_key => $lang_name) { ?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : ''; ?>" show-lang="<?=$lang_key; ?>"><?=$lang_name; ?></a>
                <?php } ?><br />
        		<?php $i = 0; foreach($languages as $k => $v){ ?>
						<div is-lang="<?=$k; ?>"<?=$k != $CFG['LANG'] ? ' style="display: none;"' : ''; ?>>
        		<input type="text" class="form-control name" name="name_<?=$k; ?>" value="<?=isset($_POST['name_' . $k]) ? $_POST['name_' . $k] : ""; ?>" placeholder="<?=$v; ?>" />
						</div>
        		<?php $i++; } ?>
        	</div>

			<div class="form-group">
				<label><?=$l['BOTP']; ?></label>
				<div class="input-group">
	        		<?php
	        		$p = $cur->getPrefix($cur->getBaseCurrency());
	        		$s = $cur->getSuffix($cur->getBaseCurrency());
	        		if(!empty($p)) echo '<span class="input-group-addon">' . $p . '</span>';
	        		?>
	        		<input type="text" class="form-control" name="amount" value="<?=isset($_POST['amount']) ? $_POST['amount'] : ""; ?>" placeholder="<?=$nfo->placeholder(); ?>" />
	        		<?php if(!empty($s)) echo '<span class="input-group-addon">' . $s . '</span>'; ?>
	        	</div>
	        </div>

            <div class="form-group">
                <label><?=$l['AFFILIATE']; ?></label>
                <div class="input-group">
                  <input type="text" name="affiliate" class="form-control" value="<?=isset($_POST['affiliate']) ? str_replace("-1.00", "", $_POST['affiliate']) : $nfo->format(str_replace("-1.00", "", "-1.00")); ?>" placeholder="<?=$l['AFFILIATEP']; ?>">
                  <span class="input-group-addon">%</span>
                </div>
              </div>

        	<div class="form-group">
        		<label><?=$l['PRODUCTS']; ?></label>
        		<select name="product[]" style="width: 100%; height: 200px; resize: none;" multiple="multiple" class="form-control">
        			<?php
        			$sql = $db->query("SELECT ID, name FROM product_categories");
        			$cats = Array();
        			while($row = $sql->fetch_object())
        				$cats[$row->ID] = unserialize($row->name)[$CFG['LANG']];
        			asort($cats);

        			foreach($cats as $i => $c){
        				echo '<option disabled="disabled" style="font-weight: bold;"># ' . $c . '</option>';

        				$sql = $db->query("SELECT ID, name FROM products WHERE category = $i");
        				$prod = Array();
        				while($row = $sql->fetch_object())
        					$prod[$row->ID] = unserialize($row->name)[$CFG['LANG']];
        				asort($prod);

        				foreach($prod as $i => $p){
        					if(isset($_POST['product']) && is_array($_POST['product']) && in_array($i, $_POST['product'])) echo '<option value="' . $i . '" selected="selected">' . $p . '</option>';
							else echo '<option value="' . $i . '">' . $p . '</option>';
        				}
        			}
        			?>
        		</select>
        		<p class="help-block"><?=$l['PRODUCTSH']; ?></p>
        	</div>

        	<input type="submit" value="<?=$l['CREATE']; ?>" class="btn btn-primary btn-block" />
        </form>
    </div>
</div>

<script>
$(".choose").click(function(e) {
	e.preventDefault();
	var l = $(this).data("lang");
	$(".name").hide();
	$("[name=name_" + l + "]").show();
})
</script>

<?php } ?>