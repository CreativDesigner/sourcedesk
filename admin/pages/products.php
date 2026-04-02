<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['PRODUCTS'];

title($l['TITLE']);
menu("products");

if (!$ari->check(24)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "products");} else {

    ?>
            <div class="row">
                <div class="col-lg-12">
					<h1 class="page-header"><?=$l['TITLE'];?><?php if (empty($_GET['archive'])) {?><a href="?p=products&archive=1" class="pull-right"><i class="fa fa-archive"></i></a><?php }?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="row">
				<div class="col-lg-12">
				<?php
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0 && $ari->check(26)) {
        if (!$db->query("SELECT COUNT(*) c FROM client_products WHERE product = " . intval($_GET['delete']))->fetch_object()->c) {
        $db->query("DELETE FROM  products WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
        if ($db->affected_rows > 0) {
            $db->query("DELETE FROM  client_products WHERE product = '" . $db->real_escape_string($_GET['delete']) . "'");
            alog("product", "delete", $_GET['delete']);
            ?>
						<div class="alert alert-success"><?=$l['SUC5'];?></div>
						<?php
} }
    }

    if (isset($_GET['copy']) && is_numeric($_GET['copy']) && $_GET['copy'] > 0 && $ari->check(26)) {
        $sql = $db->query("SELECT * FROM  products WHERE ID = '" . $db->real_escape_string($_GET['copy']) . "' LIMIT 1");
        if ($sql->num_rows > 0) {
            $info = $sql->fetch_object();
            unset($info->ID);

            $fields = $values = "";
            foreach ($info as $k => $v) {
                $fields .= "`" . $db->real_escape_string($k) . "`, ";
                $values .= "'" . $db->real_escape_string($v) . "', ";
            }
            $fields = rtrim($fields, ", ");
            $values = rtrim($values, ", ");

            $db->query("INSERT INTO products ($fields) VALUES ($values)");
            $id = $db->insert_id;

            alog("product", "copy", $_GET['copy'], $id);

            $sql = $db->query("SELECT * FROM product_provisioning WHERE pid = '" . $db->real_escape_string($_GET['copy']) . "'");
            while ($row = $sql->fetch_object()) {
                $db->query("INSERT INTO product_provisioning (`module`, `setting`, `value`, `pid`) VALUES ('" . $db->real_escape_string($row->module) . "', '" . $db->real_escape_string($row->setting) . "', '" . $db->real_escape_string($row->value) . "', $id)");
            }

            $sql = $db->query("SELECT * FROM products_cf WHERE product = '" . $db->real_escape_string($_GET['copy']) . "'");
            while ($row = $sql->fetch_object()) {
                $db->query("INSERT INTO products_cf (`name`, `type`, `options`, `product`) VALUES ('" . $db->real_escape_string($row->name) . "', '" . $db->real_escape_string($row->type) . "', '" . $db->real_escape_string($row->options) . "', $id)");
            }

            header('Location: ?p=product_hosting&id=' . $id);
            exit;
        }
    }

    if (isset($_POST['delete_selected']) && is_array($_POST['product']) && $ari->check(26)) {
        $d = 0;
        foreach ($_POST['product'] as $id) {
            if ($db->query("SELECT COUNT(*) c FROM client_products WHERE product = " . intval($id))->fetch_object()->c) {
                continue;
            }

            $db->query("DELETE FROM  products WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $db->query("DELETE FROM  client_products WHERE product = '" . $db->real_escape_string($id) . "'");
                alog("product", "delete", $id);
                $d++;
            }
        }

        if ($d == 1) {
            echo '<div class="alert alert-success">' . $l['SUC6'] . '</div>';
        } else if ($d > 0) {
            echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC6X']) . '</div>';
        }

    }

    if (isset($_POST['activate_selected']) && is_array($_POST['product']) && $ari->check(26)) {
        $d = 0;
        foreach ($_POST['product'] as $id) {
            $db->query("UPDATE  products SET status = 1 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                alog("product", "activate", $id);
            }
        }

        if ($d == 1) {
            echo '<div class="alert alert-success">' . $l['SUC7'] . '</div>';
        } else if ($d > 0) {
            echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC7X']) . '</div>';
        }

    }

    if (isset($_POST['save_order']) && is_array($_POST['order']) && $ari->check(26)) {
        foreach ($_POST['order'] as $id => $order) {
            $db->query("UPDATE  products SET `order` = " . intval($order) . " WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
        }

        echo '<div class="alert alert-success">' . $l['SAVED_ORDER'] . '</div>';
    }

    if (isset($_POST['deactivate_selected']) && is_array($_POST['product']) && $ari->check(26)) {
        $d = 0;
        foreach ($_POST['product'] as $id) {
            $db->query("UPDATE  products SET status = 0 WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                $d++;
                alog("product", "deactivate", $id);
            }
        }

        if ($d == 1) {
            echo '<div class="alert alert-success">' . $l['SUC8'] . '</div>';
        } else if ($d > 0) {
            echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC8X']) . '</div>';
        }

    }
    ?>

	<?php
function interval($billing)
    {
        global $l;
        switch ($billing) {
            case "monthly":
                return " " . strtolower($l['B2']);

            case "quarterly":
                return " " . strtolower($l['B3']);

            case "semiannually":
                return " " . strtolower($l['B4']);

            case "annually":
                return " " . strtolower($l['B5']);

            case "biennially":
                return " " . strtolower($l['B6']);

            case "trinnially":
                return " " . strtolower($l['B7']);

            case "minutely":
                return " " . strtolower($l['B8']);

            case "hourly":
                return " " . strtolower($l['B9']);

            default:
                return "";
        }
    }
    ?>

					<?php if ($ari->check(25)) {?><a href="?p=add_product" class="btn btn-success"><?=$l['ANP'];?></a>&nbsp;<?php }?><?php if ($ari->check(45)) {?><a href="?p=categories" class="btn btn-default"><?=$l['MANAGECATS'];?></a><?php }?>
					<?=$ari->check(25) || $ari->check(45) ? "<br /><br />" : "";?>
					<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
		<th><?=$l['NAME'];?></th>
        <th width="100px"><?=$l['ORDER'];?></th>
        <th width="50px"><?=$l['MODULE'];?></th>
		<th width="50px"><?=$l['AVA'];?></th>
		<th><?=$l['ACT'];?></th>
		<th><?=$l['REV'];?></th>
		<th><?=$l['PRICE'];?></th>
		<?php if ($ari->check(25) || $ari->check(26)) {?><th width="74px"></th><?php }?>
	</tr>

	<form method="POST">
	<?php

    $sql = $db->query("SELECT * FROM products WHERE category = 0 ORDER BY `order` ASC, name ASC");

    $products = array();
    if ($sql->num_rows > 0) {
        while ($d = $sql->fetch_object()) {
            $products[$d->ID] = $d->order . unserialize($d->name)[$CFG['LANG']];
        }

        asort($products);
    }

    foreach ($products as $pId => $pName) {
        $sql = $db->query("SELECT * FROM products WHERE ID = " . $pId);
        if ($sql->num_rows != 1) {
            continue;
        }

        $d = $sql->fetch_object();
        $module = array_key_exists($d->module, $provisioning->get()) ? $provisioning->get()[$d->module]->getName() : "-";
        ?>
		<tr>
			<td><input type="checkbox" class="checkbox" name="product[]" value="<?=$d->ID;?>" onchange="javascript:toggle();"></td>
			<td><?=unserialize($d->name)[$CFG['LANG']];?><?php if ($d->status == 0) {?> <font color="red">(inaktiv)</font><?php }?><?php if ($d->prepaid) {?> <font color="blue">(<?=$lang['GENERAL']['PREPAID']; ?>)</font><?php }?><?php if ($d->type == "SOFTWARE") {?> <a href="./?p=products&download=<?=$d->ID;?>" target="_blank"><i class="fa fa-download"></i></a><?php }?></td>
            <td><input type="text" class="form-control input-sm" style="text-align: center;" name="order[<?=$d->ID; ?>]" value="<?=intval($d->order); ?>"></td>
            <td style="white-space: nowrap;"><?=$module;?></td>
			<td<?=$d->available == 0 ? ' style="font-weight: bold; color: red;"' : "";?>><center><?=$d->available < 0 ? "-" : $d->available;?></center></td>
			<td><a href="?p=search&searchword=product_<?=$d->ID;?>"><?php
$sqlL = $db->query("SELECT ID, active FROM client_products WHERE product = " . $d->ID);
        $free = 0;
        $locked = 0;
        $price = 0.00;
        while ($r = $sqlL->fetch_object()) {
            if ($r->active == 1) {
                $free++;
            } else {
                $locked++;
            }

            $price += $db->query("SELECT SUM(amount) AS sum FROM invoiceitems WHERE relid = " . $r->ID)->fetch_object()->sum;
        }

        echo $free . " (" . ($locked + $free) . ")";
        ?></a></td>
			<td><?=$cur->infix($nfo->format($price), $cur->getBaseCurrency());?></td>
			<td><?=$cur->infix($nfo->format($d->price, $d->billing != "hourly" && $d->billing != "minutely" ? 2 : 6), $cur->getBaseCurrency());?><?=interval($d->billing);?></td>
        <?php if ($ari->check(25) || $ari->check(26) || $ari->check(27)) {?>
            <td>
		        <?php if ($ari->check(25)) {?><a href="?p=product_hosting&id=<?=$d->ID;?>" title="<?=$l['EDIT'];?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;<a href="?p=products&copy=<?=$d->ID;?>" title="<?=$l['COPY'];?>" onclick="return confirm('<?=$l['COPYR'];?>');"><i class="fa fa-files-o"></i></a>&nbsp;<?php }?>
		        <?php if ($ari->check(26) && $locked + $free == 0) {?><a href="?p=products&delete=<?=$d->ID;?>" title="<?=$l['DEL'];?>" onclick="return confirm('<?=$l['DELR'];?>');"><i class="fa fa-times fa-lg"></i></a><?php }?>
	        </td>
		<?php }
    }

    $sql = $db->query("SELECT * FROM products");

    if ($sql->num_rows == 0) {
        echo "<tr><td colspan=\"9\"><center>{$l['NT']}</center></td></tr>";
    } else {

        $categorySql = $db->query("SELECT ID, name, cast FROM product_categories");

        $cats = array();
        while ($c = $categorySql->fetch_object()) {
            if (unserialize($c->name)[$CFG['LANG']] == "ZZZ - Legacy" && empty($_GET['archive'])) {
                continue;
            }

            $cats[unserialize($c->name)[$CFG['LANG']]] = $c;
        }
        ksort($cats);

        foreach ($cats as $c) {

            $sql = $db->query("SELECT * FROM products WHERE category = " . $c->ID . " ORDER BY `order` ASC, name ASC");
            if ($sql->num_rows < 1) {
                continue;
            }

            $products = array();
            while ($d = $sql->fetch_object()) {
                $products[$d->ID] = $d->order . unserialize($d->name)[$CFG['LANG']];
            }

            asort($products);
            ?>
		<tr>
			<td colspan="9" style="background-color:#ffffdd;"><b><?=unserialize($c->name)[$CFG['LANG']];?></b></td>
		</tr>
		<?php

            foreach ($products as $pId => $pName) {
                $sql = $db->query("SELECT * FROM products WHERE ID = " . $pId);
                if ($sql->num_rows < 1) {
                    continue;
                }

                $d = $sql->fetch_object();
                $module = array_key_exists($d->module, $provisioning->get()) ? $provisioning->get()[$d->module]->getName() : "-";
                ?>
			<tr>
				<td><input type="checkbox" class="checkbox" name="product[]" value="<?=$d->ID;?>" onchange="javascript:toggle();"></td>
				<td><?=str_replace(unserialize($c->cast)[$CFG['LANG']], "", unserialize($d->name)[$CFG['LANG']]);?> <?php if ($d->status == 0) {?><font color="red"><?=$l['HINAC'];?></font><?php }?><?php if ($d->type == "SOFTWARE") {?> <a href="./?p=products&download=<?=$d->ID;?>" target="_blank"><i class="fa fa-download"></i></a><?php }?></td>
                <td><input type="text" class="form-control input-sm" style="text-align: center;" name="order[<?=$d->ID; ?>]" value="<?=intval($d->order); ?>"></td>
                <td style="white-space: nowrap;"><?=$module;?></td>
                <td<?=$d->available == 0 ? ' style="font-weight: bold; color: red;"' : "";?>><center><?=$d->available < 0 ? "-" : $d->available;?></center></td>
                <td><a href="?p=search&searchword=product_<?=$d->ID;?>" target="_blank"><?php
$sqlL = $db->query("SELECT ID, active FROM client_products WHERE product = " . $d->ID);
                $free = 0;
                $locked = 0;
                $price = 0.00;
                while ($r = $sqlL->fetch_object()) {
                    if ($r->active == 1) {
                        $free++;
                    } else {
                        $locked++;
                    }

                    $price += $db->query("SELECT SUM(amount) AS sum FROM invoiceitems WHERE relid = " . $r->ID)->fetch_object()->sum;
                }

                echo $free . " (" . ($locked + $free) . ")";
                ?></a></td>
				<td><?=$cur->infix($nfo->format($price), $cur->getBaseCurrency());?></td>
            <td><?=$cur->infix($nfo->format($d->price, $d->billing != "hourly" && $d->billing != "minutely" ? 2 : 6), $cur->getBaseCurrency());?><?=interval($d->billing);?></td>
            <td>
                <?php if ($ari->check(25)) {?><a href="?p=product_hosting&id=<?=$d->ID;?>" title="<?=$l['EDIT'];?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;<a href="?p=products&copy=<?=$d->ID;?>" title="<?=$l['COPY'];?>" onclick="return confirm('<?=$l['COPYR'];?>');"><i class="fa fa-files-o"></i></a>&nbsp;<?php }?>
                <?php if ($ari->check(26) && $locked + $free == 0) {?><a href="?p=products&delete=<?=$d->ID;?>" title="<?=$l['DEL'];?>" onclick="return confirm('<?=$l['DELR'];?>');"><i class="fa fa-times fa-lg"></i></a><?php }?>
		    </td>
			<?php
}}
    }
    ?>
</table></div>

<input type="submit" name="save_order" class="btn btn-primary btn-block" value="<?=$l['SAVE_ORDER']; ?>"><br />
<?=$l['SELECTED'];?>: <input type="submit" name="activate_selected" value="<?=$l['ACTIVATE'];?>" class="btn btn-success" /> <input type="submit" name="deactivate_selected" value="<?=$l['DEACTIVATE'];?>" class="btn btn-warning" /> <input type="submit" formaction="?p=new_bundle" name="bundle" class="btn btn-default" value="<?=$l['CREATEBUNDLE'];?>" /> <input type="submit" name="delete_selected" value="<?=$l['DEL'];?>" class="btn btn-danger" /><br /></form>
				</div>
            </div>
            <!-- /.row --><?php }?>
