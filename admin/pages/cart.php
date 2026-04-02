<?php
$l = $lang['CART'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(18)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "cart");} else {?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<?php
$uCart = new Cart;

    $display = array();
    $sum = 0.00;

    if (isset($_POST['delete_selected']) && is_array($_POST['cart'])) {
        $d = 0;
        foreach ($_POST['cart'] as $id) {
            if ($uCart->removeElement($id)) {
                $d++;
                alog("cart", "element_removed", $id);
            }
        }

        if ($d == 1) {
            echo '<div class="alert alert-success">' . $l['R1'] . '</div>';
        } else if ($d > 0) {
            echo '<div class="alert alert-success">' . str_replace('%d', $d, $l['RX']) . '</div>';
        }

    }

    if (isset($_POST['remind_selected']) && is_array($_POST['cart'])) {
        $users = array();
        foreach ($_POST['cart'] as $id) {
            $uid = $uCart->getElementsUser($id);
            if (!$uid) {
                continue;
            }

            if (!array_search($uid, $users)) {
                array_push($users, $uid);
            }

        }

        $d = 0;
        foreach ($users as $uid) {
            $userInstance = User::getInstance($uid, "ID");
            if (!$userInstance) {
                continue;
            }

            $cartInstance = new Cart($uid);
            $items = $cartInstance->get();
            if (count($items) == 0) {
                continue;
            }

            $lastAdded = 0;
            $products = "";
            foreach ($items as $i) {
                $names = unserialize($i['name']);
                if ($names === false) {
                    continue;
                }

                $products .= (array_key_exists($language, $names) ? $names[$language] : $names[$CFG['LANG']]) . "\n";
                if ($i['added'] > $lastAdded) {
                    $lastAdded = $i['added'];
                }

            }
            if (empty($products)) {
                continue;
            }

            $language = $userInstance->getLanguage();

            $mtObj = new MailTemplate("Warenkorb-Erinnerung");
            $title = $mtObj->getTitle($language);

            $mail = $mtObj->getMail($language, $userInstance->get()['name']);

            $maq->enqueue([
                "date" => $dfo->format($lastAdded, false, false, "", $userInstance->getDateFormat()),
                "products" => rtrim($products, "\n"),
            ], $mtObj, $userInstance->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $userInstance->get()['ID'], false, 0, 0, $mtObj->getAttachments($language));

            alog("cart", "reminder", $userInstance->get()['ID']);

            $d++;
        }

        if ($d == 1) {
            echo '<div class="alert alert-success">' . $l['M1'] . '</div>';
        } else if ($d > 0) {
            echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['MX']) . '</div>';
        }

    }

    $elements = $uCart->get();
    foreach ($elements as $id => $e) {
        $customerName = "??? ???";
        $customerInfoQuery = $db->query("SELECT ID FROM clients WHERE ID = " . $e['user']);
        if ($customerInfoQuery->num_rows == 1) {
            $customerInfo = $customerInfoQuery->fetch_object();
            $customerName = User::getInstance($customerInfo->ID, "ID")->getfName();
        }

        switch ($e['type']) {

            case 'product':
                $sum += $e['sum'];

                $pn = strtoupper($e['license']);

                if ($customerName != "??? ???") {
                    $display[] = array("id" => $id, "qty" => $e['qty'], "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => $l['PRODUCT'] . " ($pn)", "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount'], "oldprice" => $e['oldprice']);
                } else {
                    $display[] = array("id" => $id, "qty" => $e['qty'], "customer" => "<i>{$l['UC']}</i>", "added" => $e['added'], "type" => $l['PRODUCT'] . " ($pn)", "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount'], "oldprice" => $e['oldprice']);
                }

                break;

            case 'domain_reg':
            case 'domain_in':
                $sum += $e['sum'];

                if ($customerName != "??? ???") {
                    $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => $e['type'] == "domain_reg" ? $l['DR'] : $l['DT'], "name" => unserialize($e['license'])['domain'], "price" => $e['amount']);
                } else {
                    $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<i>{$l['UC']}</i>", "added" => $e['added'], "type" => $e['type'] == "domain_reg" ? $l['DR'] : $l['DT'], "name" => unserialize($e['license'])['domain'], "price" => $e['amount']);
                }

                break;

            case 'update':
                $sum += $e['sum'];

                if ($customerName != "??? ???") {
                    $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => $l['UPDATE'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                } else {
                    $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<i>{$l['UC']}</i>", "added" => $e['added'], "type" => $l['UPDATE'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                }

                break;

            case 'bundle':
                $sum += $e['sum'];

                if ($customerName != "??? ???") {
                    $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<a href=\"?p=customers&edit=" . $e['user'] . "\">$customerName</a>", "added" => $e['added'], "type" => $l['PB'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                } else {
                    $display[] = array("ID" => $e['ID'], "qty" => 1, "customer" => "<i>{$l['UC']}</i>", "added" => $e['added'], "type" => $l['PB'], "name" => unserialize($e['name'])[$CFG['LANG']], "price" => $e['amount']);
                }

                break;
        }
    }
    ?>

			<?php if (count($display) > 0) {?><div class="alert alert-info">
			<?=$l['CC'];?> <?=$cur->infix($nfo->format($sum), $cur->getBaseCurrency());?>.
			</div><?php }?>

            <div class="row">
				<div class="col-lg-12">
					<div class="table-responsive"><table class="table table-bordered table-striped">
	<tr>
		<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
		<th><?=$l['ADDED'];?></th>
		<th><?=$l['CUST'];?></th>
		<th><?=$l['TYPE'];?></th>
		<th><?=$l['DESC'];?></th>
		<th width="40px"><center><?=$l['QTY'];?></center></th>
		<th><?=$l['SP'];?></th>
		<th><?=$l['WP'];?></th>
	</tr>
	<form method="POST">
	<?php
if (count($display) == 0) {
        echo "<tr><td colspan=\"8\"><center>{$l['NT']}</center></td></tr>";
    } else {
        foreach ($display as $d) {
            ?>
			<tr>
				<td><input type="checkbox" class="checkbox" onchange="javascript:toggle();" name="cart[]" value="<?=$d['id'];?>"></td>
				<td><?=$dfo->format($d['added']);?></td>
				<td><?=$d['customer'];?></td>
				<td><?=$d['type'];?></td>
				<td><?=$d['name'];?></td>
				<td width="40px"><center><?=$d['qty'];?></center></td>
				<td><?php if ($d['oldprice'] != 0 && $d['oldprice'] != $d['price']) {?><s style="color:red;"><span style="color:#444444;"><?=$cur->infix($nfo->format($d['oldprice']), $cur->getBaseCurrency());?></span></s> <?php }?><?=$cur->infix($nfo->format($d['price']), $cur->getBaseCurrency());?></td>
				<td><?php if ($d['oldprice'] != 0 && $d['oldprice'] != $d['price']) {?><s style="color:red;"><span style="color:#444444;"><?=$cur->infix($nfo->format($d['oldprice'] * $d['qty']), $cur->getBaseCurrency());?></span></s> <?php }?><?=$cur->infix($nfo->format($d['price'] * $d['qty']), $cur->getBaseCurrency());?></td>
			</tr>
			<?php
}
    }
    ?>
</table></div><?=$l['SELECTED'];?>: <input type="submit" name="remind_selected" value="<?=$l['REMIND'];?>" class="btn btn-primary"> <input type="submit" name="delete_selected" value="<?=$l['DEL'];?>" class="btn btn-danger"><br /></form>
				</div>
            </div>
            <!-- /.row --><?php }?>