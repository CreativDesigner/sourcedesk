<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['NEW_HOSTING'];
$l2 = $lang['HOSTING'];
title($l['TITLE']);
menu("customers");

if (!$ari->check(13)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "new_hosting");} else {

    if (!empty($_POST['calc'])) {
        $activate = "<script>$('#order').prop('disabled', false);</script>";

        $pSql = $db->query("SELECT * FROM products WHERE ID = " . intval($_REQUEST['product']) . " AND type = 'HOSTING' LIMIT 1");
        if ($pSql->num_rows != 1) {
            die($l['NOTVALID']);
        }

        $info = $pSql->fetch_object();

        if (!empty($_POST['overwrite_setup']) && $_POST['overwrite_setup'] == "1") {
            $info->setup = doubleval($nfo->phpize($_POST['setup']));
        }

        if (!empty($_POST['overwrite_price']) && $_POST['overwrite_price'] == "1") {
            $info->price = doubleval($nfo->phpize($_POST['price']));

            if (empty($_POST['billing']) || !in_array($_POST['billing'], ["onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "trinnially"])) {
                die($l['NOTVALID']);
            }

            $info->billing = $_POST['billing'];
        } elseif ($CFG['TAXES']) {
            if ($info->tax == "net" || $info->tax == "dynamic") {
                if ($info->tax == "dynamic") {
                    $sql = $db->query("SELECT percent, alpha2 FROM client_countries WHERE ID = " . $CFG['DEFAULT_COUNTRY']);
                    if ($sql->num_rows) {
                        $couInfo = $sql->fetch_object();
                        $percent = $couInfo->percent;
                        $alpha2 = $couInfo->alpha2;

                        if ($tempVat = TempVat::rate($alpha2, $percent)) {
                            $percent = $tempVat;
                        }

                        $info->price = $info->price / (1 + $percent / 100);
                    }
                }

                if ($db->query("SELECT ID FROM clients WHERE ID = " . intval($_REQUEST['user']))->num_rows != 1) {
                    die($l['ERR1']);
                }
                $user = User::getInstance($_REQUEST['user'], "ID");

                $info->price = $user->addTax($info->price);
            }
        }

        $qty = max(1, intval($_REQUEST['qty'] ?? 1));

        $name = htmlentities(unserialize($info->name) ? unserialize($info->name)[$CFG['LANG']] : $info->name);

        $pricing = "";
        if ($info->price == 0 && $info->setup == 0) {
            echo "{$qty}x <b>$name</b><br />" . $l['FREE'];
        } else {
            $billArr = [
                "onetime" => $l2["B1"],
                "monthly" => $l2["B2"],
                "quarterly" => $l2["B3"],
                "semiannually" => $l2["B4"],
                "annually" => $l2["B5"],
                "biennially" => $l2["B6"],
                "trinnially" => $l2["B7"],
            ];
            $pricing = $cur->infix($nfo->format($info->price), $cur->getBaseCurrency()) . " " . lcfirst($billArr[$info->billing] ?? "");
            $sum = $cur->infix($nfo->format($info->price * $qty), $cur->getBaseCurrency()) . " " . lcfirst($billArr[$info->billing] ?? "");

            $pricing .= "<br />" . $cur->infix($nfo->format($info->setup), $cur->getBaseCurrency()) . " " . $l['SETUP'];
            $sum .= "<br />" . $cur->infix($nfo->format($info->setup * $qty), $cur->getBaseCurrency()) . " " . $l['SETUP'];

            if (empty($_POST['invoice'])) {
                $due = $cur->infix($nfo->format(0), $cur->getBaseCurrency());
            } else {
                $due = $cur->infix($nfo->format($info->price * $qty + $info->setup * $qty), $cur->getBaseCurrency());
            }

            echo "{$qty}x <b>$name</b><br />" . $pricing . "<br /><br /><b>" . $l['SUM'] . ":</b><br />" . $sum . "<br /><br /><b>" . $l['DUETODAY'] . ":</b> " . $due;
        }

        die($activate);
    }

    if (isset($_POST['user'])) {

        try {
            foreach ($_POST as $k => $v) {
                $vari = "p_" . strtolower($k);
                $$vari = $db->real_escape_string($v);
            }

            if ($db->query("SELECT ID FROM clients WHERE ID = '$p_user'")->num_rows != 1) {
                throw new Exception($l['ERR1']);
            }
            $user = User::getInstance($p_user, "ID");

            $pSql = $db->query("SELECT * FROM products WHERE ID = '$p_product' AND type = 'HOSTING' LIMIT 1");
            if ($pSql->num_rows != 1) {
                throw new Exception($l['ERR2']);
            }

            $info = $pSql->fetch_object();

            $module_settings = array();
            $sql = $db->query("SELECT setting, value FROM product_provisioning WHERE module = '" . $db->real_escape_string($info->module) . "' AND pid = " . intval($info->ID));
            while ($row = $sql->fetch_object()) {
                $module_settings[$row->setting] = decrypt($row->value);
            }

            $module_settings = $db->real_escape_string(encrypt(serialize($module_settings)));

            if (!empty($_POST['overwrite_setup']) && $_POST['overwrite_setup'] == "yes") {
                $info->setup = doubleval($nfo->phpize($_POST['setup']));
            }

            if (!empty($_POST['overwrite_price']) && $_POST['overwrite_price'] == "yes") {
                $info->price = doubleval($nfo->phpize($_POST['price']));

                if (empty($_POST['billing']) || !in_array($_POST['billing'], ["onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "trinnially"])) {
                    throw new Exception($l['ERR4']);
                }

                $info->billing = $_POST['billing'];
            } elseif ($CFG['TAXES']) {
                if ($info->tax == "net" || $info->tax == "dynamic") {
                    if ($info->tax == "dynamic") {
                        $sql = $db->query("SELECT percent FROM client_countries WHERE ID = " . $CFG['DEFAULT_COUNTRY']);
                        if ($sql->num_rows) {
                            $info->price = $info->price / (1 + $sql->fetch_object()->percent / 100);
                        }
                    }

                    $info->price = $user->addTax($info->price);
                }
            }

            if ($info->billing != "" && $info->billing != "onetime") {
                $billarr = array(
                    "monthly" => "1 month",
                    "quarterly" => "3 months",
                    "semiannually" => "6 months",
                    "annually" => "1 year",
                    "biennially" => "2 years",
                    "trinnially" => "3 years",
                );
                $bill = date("Y-m-d", strtotime("+" . $billarr[$info->billing]));
            } else {
                $bill = "0000-00-00";
            }

            $cd = '0000-00-00';
            if ($info->autodelete) {
                $cd = date("Y-m-d", strtotime("+" . $info->autodelete . " days"));
            }

            // Select custom fields
            $cf = [];

            $sql = $db->query("SELECT ID FROM products_cf WHERE product = {$info->ID}");
            while ($row = $sql->fetch_object()) {
                $cf[$row->ID] = "";
            }

            $cf = $db->real_escape_string(serialize($cf));

            $qty = max(1, intval($_REQUEST['qty'] ?? 1));

            $invItems = [];

            $inv = new Invoice;
            $inv->setDate(date("Y-m-d"));
            $inv->setClient($p_user);
            $inv->setDueDate();

            for ($i = 0; $i < $qty; $i++) {
                // Insert new contract in database
                $info->billing = $db->real_escape_string($info->billing);
                $db->query("INSERT INTO client_products (`date`, `user`, `product`, `active`, `type`, `description`, `billing`, `module`, `module_settings`, `last_billed`, `ct`, `mct`, `np`, `price`, `cancellation_date`, `cf`) VALUES (" . time() . ", {$p_user}, {$info->ID}, -1, 'h', '', '{$info->billing}', '{$info->module}', '$module_settings', '" . $bill . "', '{$info->ct}', '{$info->mct}', '{$info->np}', {$info->price}, '$cd', '$cf')");
                if ($db->errno) {
                    throw new Exception($l['ERR3']);
                }

                $lid = $db->insert_id;

                $uI = User::getInstance($p_user, "ID");

                if ($uI && $info->new_cgroup >= 0) {
                    $uI->set(["cgroup" => $info->new_cgroup, "cgroup_before" => $uI->get()['cgroup'], "cgroup_contract" => $lid]);
                }

                $more = "";
                if (isset($_POST['invoice']) && $_POST['invoice'] == "yes") {
                    // Invoice item text
                    $invDesc = "<b>" . unserialize($info->name)[$uI->getLanguage()] . "</b>";

                    $from = time();
                    $to = strtotime($bill);

                    if ($info->desc_on_invoice) {
                        $invDesc .= "<br />" . (@unserialize($info->description) ? unserialize($info->description)[$uI->getLanguage()] : $info->description);
                    }

                    if ($from && $to) {
                        $invDesc .= "<br /><br />" . $dfo->format($from, false, false, "", $uI->getDateFormat()) . " - " . $dfo->format($to, false, false, "", $uI->getDateFormat());
                    }

                    // Now we create the first invoice item
                    $item = new InvoiceItem;
                    $item->setDescription($invDesc ?: $info->name);
                    $item->setAmount($info->price);
                    $item->setRelid($lid);

                    array_push($invItems, $item);

                    // Load customers language
                    $alang = $lang;
                    $uI->loadLanguage();
                    $clang = $lang;
                    $lang = $alang;

                    // Create invoice item for setup fee/discount
                    if ($info->setup != 0) {
                        $item = new InvoiceItem;
                        $item->setDescription(unserialize($info->name)[$uI->getLanguage()] . " (" . ($info->setup > 0 ? $clang['CART']['SETUP'] : $clang['CART']['DISCOUNT']) . ")");
                        $item->setAmount($info->setup);
                        $item->setRelid($lid);
                        array_push($invItems, $item);
                    }
                }

                alog("customer", "abonnement_created", $p_user, $lid);
            }

            if (count($invItems)) {
                foreach ($invItems as $item) {
                    $inv->addItem($item);
                }

                $more = " " . $l['INVOICED'];
                if (isset($_POST['email']) && $_POST['email'] == "yes") {
                    $inv->send();
                    $more .= " " . $l['SENT'];
                }
                $more .= ".";

                if (isset($_POST['credit'])) {
                    $more .= " " . $l['CREDIT'];
                    $inv->applyCredit();
                }
            }

            $error = "<div class=\"alert alert-success\">" . $l['CREATED'] . "$more</div>";
            unset($_POST);
        } catch (Exception $ex) {
            $error = "<div class=\"alert alert-danger\"><b>Fehler!</b> " . $ex->getMessage() . "</div>";
        }

    }

    $productsSql = $db->query("SELECT * FROM products");
    $customerSql = $db->query("SELECT * FROM clients ORDER BY firstname ASC, lastname ASC");

    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
        echo $error;
    }
    ?>
	<form role="form" method="POST">
<div class="row"><div class="col-md-9">
<div class="panel panel-primary">
    <div class="panel-heading"><?=$l['DATA'];?></div>
    <div class="panel-body">
	<div class="form-group">
		<label><?=$l['CUST'];?></label>
		<input type="text" class="form-control customer-input" placeholder="<?=$l['CUSTP'];?>" value="<?=ci(!empty($_REQUEST["user"]) ? $_REQUEST["user"] : "0");?>">
		<input type="hidden" name="user" class="monitor" value="<?=!empty($_REQUEST["user"]) ? $_REQUEST["user"] : "0";?>">
		<div class="customer-input-results"></div>
	</div>

  <div class="form-group">
    <label><?=$l['PRODUCT'];?></label>
    <div class="row">
    <div class="col-md-2">
    <div class="input-group">
        <input name="qty" placeholder="1" type="number" min="1" class="form-control monitor" style="text-align: center;" value="<?=$_REQUEST['qty'] ?? 1;?>">
        <span class="input-group-addon">x</span>
    </div>
    </div>
    <div class="col-md-10">
   <select name="product" class="form-control monitor">
	<option value="0"><?=$l['NOPRODUCT'];?></option>
	<?php if ($productsSql->num_rows > 0) {?>
	<option disabled>--------------</option>
	<?php
$arr = array();
        while ($r = $productsSql->fetch_object()) {
            $arr[unserialize($r->name)[$CFG['LANG']]] = $r;
        }

        ksort($arr);

        foreach ($arr as $k => $r) {
            $style = "";
            if ($r->status == 0) {
                $style = "background-color:gold;";
            }

            ?>
	<option value="<?=$r->ID;?>" style="<?=$style;?>" <?php if (isset($_POST['product']) && $_POST['product'] == $r->ID) {
                echo "selected=\"selected\"";
            }
            ?>><?=unserialize($r->name)[$CFG['LANG']];?></option>
	<?php }}?>
   </select></div></div>
  </div>

  <div class="checkbox">
  	<label>
		<input type="checkbox" name="overwrite_price" onchange="if(this.checked){ $('#overwrite_price').show(); } else { $('#overwrite_price').hide(); }" value="yes" class="monitor" <?=empty($_POST['overwrite_price']) ? "" : "checked";?>>
		<?=$l['OVERWRITE_PRICE'];?>
  	</label>
  </div>

  <div id="overwrite_price"<?=empty($_POST['overwrite_price']) ? ' style="display: none;"' : '';?>>
    <div class="form-group">
        <label><?=$l2['PRICE'];?></label>
        <div class="row">
        <div class="col-xs-8">
        <input type="text" name="price" class="form-control monitor" value="<?=!empty($_POST['price']) ? $_POST['price'] : "";?>" placeholder="<?=$nfo->placeholder();?>" />
        </div>
        <div class="col-xs-4">
        <select name="billing" class="form-control monitor">
            <option value="onetime"><?=$l2['B1'];?></option>
            <option value="monthly"<?=!empty($_POST['billing']) && $_POST['billing'] == "monthly" ? ' selected="selected"' : "";?>><?=$l2['B2'];?></option>
            <option value="quarterly"<?=!empty($_POST['billing']) && $_POST['billing'] == "quarterly" ? ' selected="selected"' : "";?>><?=$l2['B3'];?></option>
            <option value="semiannually"<?=!empty($_POST['billing']) && $_POST['billing'] == "semiannually" ? ' selected="selected"' : "";?>><?=$l2['B4'];?></option>
            <option value="annually"<?=!empty($_POST['billing']) && $_POST['billing'] == "annually" ? ' selected="selected"' : "";?>><?=$l2['B5'];?></option>
            <option value="biennially"<?=!empty($_POST['billing']) && $_POST['billing'] == "biennially" ? ' selected="selected"' : "";?>><?=$l2['B6'];?></option>
            <option value="trinnially"<?=!empty($_POST['billing']) && $_POST['billing'] == "annually" ? ' selected="selected"' : "";?>><?=$l2['B7'];?></option>
        </select>
        </div>
        </div>
    </div>
  </div>

  <div class="checkbox">
  	<label>
		<input type="checkbox" class="monitor" name="overwrite_setup" onchange="if(this.checked){ $('#overwrite_setup').show(); } else { $('#overwrite_setup').hide(); }" value="yes" <?=empty($_POST['overwrite_setup']) ? "" : "checked";?>>
		<?=$l['OVERWRITE_SETUP'];?>
  	</label>
  </div>

  <div id="overwrite_setup"<?=empty($_POST['overwrite_setup']) ? ' style="display: none;"' : '';?>>
    <div class="form-group">
        <label><?=$l2['SETUP'];?></label>
        <input type="text" name="setup" class="form-control monitor" value="<?=!empty($_POST['setup']) ? $_POST['setup'] : "";?>" placeholder="<?=$nfo->placeholder();?>" />
    </div>
  </div>

  <div class="checkbox">
  	<label>
		<input type="checkbox" class="monitor" name="invoice" onchange="if(this.checked){ $('#credit').show(); $('#email').show(); } else { $('#credit').hide(); $('#email').hide(); }" value="yes" <?=isset($_POST['add']) && (!isset($_POST['invoice']) || $_POST['invoice'] != "yes") ? "" : "checked";?>>
		<?=$l['INVOICE'];?>
  	</label>
  </div>

  <div class="checkbox" id="credit"<?=isset($_POST['add']) && (!isset($_POST['invoice']) || $_POST['invoice'] != "yes") ? ' style="display: none;"' : '';?>>
  	<label>
		<input type="checkbox" name="credit" value="yes" <?=isset($_POST['add']) && (!isset($_POST['credit']) || $_POST['credit'] != "yes") ? "" : "checked";?>>
		<?=$l['CREDITCHECK'];?>
  	</label>
  </div>
  <div class="checkbox" id="email"<?=isset($_POST['add']) && (!isset($_POST['invoice']) || $_POST['invoice'] != "yes") ? ' style="display: none;"' : '';?>>
  	<label>
		<input type="checkbox" name="email" value="yes" <?=isset($_POST['add']) && (!isset($_POST['email']) || $_POST['email'] != "yes") ? "" : "checked";?>>
		<?=$l['EMAIL'];?>
  	</label>
  </div>
</div></div></div><div class="col-md-3">
  <div class="panel panel-default">
    <div class="panel-heading"><?=$l['ORDER'];?></div>
    <div class="panel-body"><div id="calc">
    <?=$l['NOTVALID'];?>
    </div><br /><button type="submit" id="order" disabled="" class="btn btn-primary btn-block"><?=$l['ADD'];?></button></form></div>
  </div>
</div></div>
<script>
$(".monitor").change(function() {
    $('#order').prop('disabled', true);
    $("#calc").html('<i class="fa fa-spinner fa-spin"></i> <?=$lang['GENERAL']['PLEASE_WAIT'];?>');

    var data = {};

    $(".monitor").each(function() {
        if ($(this).is("input") && $(this).prop("type") == "checkbox") {
            var val = $(this).is(":checked") ? "1" : "0";
        } else {
            var val = $(this).val();
        }

        data[$(this).prop("name")] = val;
    });

    data.calc = "1";
    data.csrf_token = "<?=CSRF::raw();?>";

    $.post("", data, function(r) {
        $("#calc").html(r);
    });
});
</script>
<?php }?>