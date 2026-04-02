<?php
$l = $lang['BUNDLES'];
title($l['TITLE']);
menu("products");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(24)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "bundles");
} else {
    if (isset($_GET['id']) && $db->query("SELECT * FROM product_bundles WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->num_rows == 1) {
        $i = $db->query("SELECT * FROM product_bundles WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();

        if (isset($_POST['amount'])) {
            try {
                $n = array();
                foreach ($languages as $k => $v) {
                    if (empty($_POST['name_' . $k])) {
                        throw new Exception(str_replace("%v", $v, $l['ERR1']));
                    }

                    $n[$k] = $_POST['name_' . $k];
                }

                $a = $nfo->phpize($_POST['amount']);
                if (empty($a) || (doubleval($a) != $a && intval($a) != $a) || doubleval($a) < 0) {
                    throw new Exception($l['ERR2']);
                }

                if (!isset($_POST['affiliate']) || trim($_POST['affiliate']) == "") {
                    $affiliate = -1;
                } else {
                    $affiliate = $nfo->phpize($_POST['affiliate']);
                    if ((!is_numeric($affiliate) && !is_double($affiliate)) || $affiliate < 0) {
                        throw new Exception($err['ERR3']);
                    }

                }

                $p = $_POST['product'];
                if (empty($p) || !is_array($p) || count($p) < 1) {
                    throw new Exception($l['ERR4']);
                }

                $db->query("UPDATE product_bundles SET name = '" . $db->real_escape_string(serialize($n)) . "', products = '" . $db->real_escape_string(serialize($p)) . "', price = " . doubleval($a) . ", affiliate = " . doubleval($affiliate) . " WHERE ID = " . $i->ID);

                $msg = $l['EDITED'];
                alog("bundle", "changed", $i->ID);
                unset($_POST);
                $i = $db->query("SELECT * FROM product_bundles WHERE ID = '" . $db->real_escape_string($_GET['id']) . "'")->fetch_object();
            } catch (Exception $ex) {
                $err = $ex->getMessage();
            }
        }

        $iName = unserialize($i->name);
        ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE_EDIT'];?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<?php if (isset($err)) {?><div class="alert alert-danger"><b><?=$lang['GENERAL']['ERROR'];?></b> <?=$err;?></div><?php }?>
        <?php if (isset($msg)) {?><div class="alert alert-success"><?=$msg;?></div><?php }?>

        <form role="form" method="POST">
        	<div class="form-group">
        		<label><?=$l['NAME'];?> <span style="font-weight: normal;">(<?php $i4 = 0;foreach ($languages as $k => $v) {if ($i4 != 0) {
            echo ' | ';
        }

            $i4++;
            echo "<a href='#' data-lang='$k' class='choose'>$v</a>";}?>)</span></label>
        		<?php $i4 = 0;foreach ($languages as $k => $v) {?>
        		<input type="text" class="form-control name" name="name_<?=$k;?>" value="<?=isset($_POST['name_' . $k]) ? $_POST['name_' . $k] : $iName[$k];?>" placeholder="<?=$v;?>"<?php if ($i4 != 0) {
            echo ' style="display: none;"';
        }
            ?> />
        		<?php $i4++;}?>
        	</div>

			<div class="form-group">
				<label><?=$l['PRICE'];?></label>
				<div class="input-group">
	        		<?php
$p = $cur->getPrefix($cur->getBaseCurrency());
        $s = $cur->getSuffix($cur->getBaseCurrency());
        if (!empty($p)) {
            echo '<span class="input-group-addon">' . $p . '</span>';
        }

        ?>
	        		<input type="text" class="form-control" name="amount" value="<?=isset($_POST['amount']) ? $_POST['amount'] : $nfo->format($i->price);?>" placeholder="<?=$nfo->placeholder();?>" />
	        		<?php if (!empty($s)) {
            echo '<span class="input-group-addon">' . $s . '</span>';
        }
        ?>
	        	</div>
	        </div>

	        <div class="form-group">
			    <label><?=$lang['AFFILIATE'];?></label>
			    <div class="input-group">
			      <input type="text" name="affiliate" class="form-control" value="<?=isset($_POST['affiliate']) ? str_replace("-1.00", "", $_POST['affiliate']) : $nfo->format(str_replace("-1.00", "", $i->affiliate));?>" placeholder="<?=$l['AFFILIATEP'];?>">
			      <span class="input-group-addon">%</span>
			    </div>
			  </div>

        	<div class="form-group">
        		<label><?=$l['PRODUCTS'];?></label>
        		<select name="product[]" style="width: 100%; height: 200px; resize: none;" multiple="multiple" class="form-control">
        			<?php
$sql = $db->query("SELECT ID, name FROM product_categories");
        $cats = array();
        while ($row = $sql->fetch_object()) {
            $cats[$row->ID] = unserialize($row->name)[$CFG['LANG']];
        }

        asort($cats);

        foreach ($cats as $i3 => $c) {
            echo '<option disabled="disabled" style="font-weight: bold;"># ' . $c . '</option>';

            $sql = $db->query("SELECT ID, name FROM products WHERE category = $i3");
            $prod = array();
            while ($row = $sql->fetch_object()) {
                $prod[$row->ID] = unserialize($row->name)[$CFG['LANG']];
            }

            asort($prod);

            foreach ($prod as $i2 => $p) {
                if (isset($_POST['product']) && is_array($_POST['product']) && in_array($i2, $_POST['product'])) {
                    echo '<option value="' . $i2 . '" selected="selected">' . $p . '</option>';
                } else if (!isset($_POST['product']) && is_array(unserialize($i->products)) && in_array($i2, unserialize($i->products))) {
                    echo '<option value="' . $i2 . '" selected="selected">' . $p . '</option>';
                } else {
                    echo '<option value="' . $i2 . '">' . $p . '</option>';
                }

            }
        }
        ?>
        		</select>
        		<p class="help-block"><?=$l['PRODUCTSH'];?></p>
        	</div>

        	<input type="submit" value="<?=$l['EDIT'];?>" class="btn btn-primary btn-block" />
        </form>

        <script>
$(".choose").click(function(e) {
	e.preventDefault();
	var l = $(this).data("lang");
	$(".name").hide();
	$("[name=name_" + l + "]").show();
})
</script>

<?php

    } else {
        ?>

            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?> <a href="?p=new_bundle" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="row">
				<div class="col-lg-12">
				<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM  product_bundles WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['DELETED'] . '</div>';
                alog("bundle", "delete", $_GET['delete']);
            }
        }
        ?>

					<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th><?=$l['NAME'];?></th>
		<th><?=$l['PRODUCTS'];?></th>
		<th><?=$l['OP'];?></th>
		<th><?=$l['BP'];?></th>
		<th><?=$l['SELLS'];?></th>
		<th width="56px"></th>
	</tr>
	<form method="POST">
	<?php

        $sql = $db->query("SELECT * FROM product_bundles");

        if ($sql->num_rows == 0) {
            echo "<tr><td colspan=\"6\"><center>{$l['NT']}</center></td></tr>";
        } else {
            $cats = array();
            while ($d = $sql->fetch_object()) {
                $cats[unserialize($d->name)[$CFG['LANG']] . "_" . rand(1000000, 9999999)] = $d;
            }

            ksort($cats);

            foreach ($cats as $d) {
                $org = 0;
                $products = unserialize($d->products);
                foreach ($products as $pid) {
                    $sql = $db->query("SELECT price FROM products WHERE ID = " . intval($pid));
                    if ($sql->num_rows != 1) {
                        continue;
                    }

                    $org += $sql->fetch_object()->price;
                }

                ?>
				<td><?=unserialize($d->name)[$CFG['LANG']];?></td>
				<td><?=count(unserialize($d->products));?></td>
				<td><?=$cur->infix($nfo->format($org), $cur->getBaseCurrency());?></td>
				<td><?=$cur->infix($nfo->format($d->price), $cur->getBaseCurrency());?></td>
				<td><?=$nfo->format($d->sells, 0);?> (<?=$cur->infix($nfo->format($d->price * $d->sells), $cur->getBaseCurrency());?>)</td>
				<td>
					<a href="?p=bundles&id=<?=$d->ID;?>" title="<?=$l['TITLE_EDIT'];?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;
					<a href="?p=bundles&delete=<?=$d->ID;?>" title="L&ouml;schen" onclick="return confirm('<?=$l['REALDEL'];?>');"><i class="fa fa-times fa-lg"></i></a>
		</td></tr>
			<?php
}
        }
        ?>
</table></div></form>
				</div>
            </div>
            <!-- /.row -->

<?php }}?>