<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['PRODUCT_HOSTING'];
title($l['TITLE']);
menu("products");

if (!$ari->check(24) || !isset($_GET['id']) || !is_numeric($_GET['id']) || !is_object($sql = $db->query("SELECT * FROM products WHERE ID = " . intval($_GET['id']) . " AND type = 'HOSTING'")) || $sql->num_rows != 1) {
    require __DIR__ . "/error.php";
    if (!$ari->check(24)) {
        alog("general", "insufficient_page_rights", "product_hosting");
    }

} else {
    $info = $sql->fetch_object();

    if (isset($_GET['ip_product'])) {
        if ($_GET['ip_product']) {
            $ip_product = intval($_GET['ip_product']);

            if ($db->query("SELECT 1 FROM products WHERE ip_product = " . intval($info->ID))->num_rows) {
                die("<div class='alert alert-danger'>{$l['ERR1']}</div>");
            }

            if (!$db->query("SELECT 1 FROM products WHERE ID = $ip_product AND type = 'HOSTING' AND ip_product = 0")->num_rows) {
                die("<div class='alert alert-danger'>{$l['ERR2']}</div>");
            }

            die("<div class='alert alert-info'>" . str_replace("%i", $ip_product, $l['IPOTHER']) . "</div>");
        } else {
            require __DIR__ . "/ip_manager.php";
        }

        exit;
    }

    if (!empty($_POST['usage_bill_table'])) {
        if (!array_key_exists($_POST['module'], $provisioning->get())) {
            exit;
        }

        $pars = $provisioning->get()[$_POST['module']]->usagePars();
        if (!count($pars)) {
            exit;
        }

        $options = @unserialize($info->usage_billing) ?: [];

        foreach ($pars as $key => $name) {
            $is = array_key_exists($key, $options);

            ?>
			<tr style="height: 47px;" class="usagebill_row">
				<td><i class="fa fa-circle<?=!$is ? "-o" : "";?> usagebill_circle" style="color: #428bca; cursor: pointer;"></i> <?=htmlentities($name);?></td>
				<td><span class="usagebill_no" style="<?=$is ? 'display: none;' : '';?>"><?=$l['NO'];?></span><span class="usagebill_yes" style="<?=!$is ? 'display: none;' : '';?>"><input type="text" class="form-control input-sm usagebill_free" data-key="<?=$key;?>" placeholder="<?=$nfo->format(10, 0);?>" value="<?=$is ? $nfo->format($options[$key][0], 0) : "";?>"></span></td>
				<td><span class="usagebill_no" style="<?=$is ? 'display: none;' : '';?>"><?=$l['NO'];?></span><span class="usagebill_yes" style="<?=!$is ? 'display: none;' : '';?>"><span class="input-group"><?=$cur->getPrefix() ? '<span class="input-group-addon">' . $cur->getPrefix() . '</span>' : '';?><input type="text" class="form-control input-sm usagebill_price" data-key="<?=$key;?>" placeholder="<?=$nfo->placeholder();?>" value="<?=$is ? $nfo->format($options[$key][1], 2) : "";?>"><?=$cur->getSuffix() ? '<span class="input-group-addon">' . $cur->getSuffix() . '</span>' : '';?></span></span></td>
				<td><span class="usagebill_no" style="<?=$is ? 'display: none;' : '';?>"><?=$l['NO'];?></span><span class="usagebill_yes" style="<?=!$is ? 'display: none;' : '';?>"><input type="text" class="form-control input-sm usagebill_units" data-key="<?=$key;?>" placeholder="<?=$nfo->format(5, 0);?>" value="<?=$is ? $nfo->format($options[$key][2], 0) : "";?>"></span></td>
			</tr>
			<?php
}

        exit;
    }

    $currencies = array();
    $sql = $db->query("SELECT ID, name FROM currencies ORDER BY base DESC, name ASC");
    while ($row = $sql->fetch_object()) {
        $currencies[$row->ID] = $row->name;
    }

    if (!empty($_GET['username_tab'])) {
        $modules = $provisioning->get();
        if (!array_key_exists($_GET['username_tab'], $modules)) {
            die("no");
        }

        $module = $modules[$_GET['username_tab']];
        die($module->getUsernameMgmt() ? "yes" : "no");
    }

    if (isset($_GET['module'])) {
        if (empty($_GET['module'])) {
            die($l['ERR3'] . "<br /><br />");
        }

        $modules = $provisioning->get();
        if (!array_key_exists($_GET['module'], $modules)) {
            die($l['ERR4']);
        }

        $module = $modules[$_GET['module']];
        if ($module->getServerMgmt() && empty($_POST)) {
            $module->loadOptions($info->ID, true);
            ?>
			<div class="form-group">
				<label><?=$lang['MONITORING']['CHOOSE_SERVER'];?></label>
				<select name="_mgmt_server" data-setting="_mgmt_server" class="form-control prov_settings">
					<option value="0"><?=$lang['MONITORING']['NO_SERVER'];?></option>
					<?php
$serverId = $module->getOption("_mgmt_server");
            $short = $db->real_escape_string($module->getShort());
            $servers = [];
            $sql = $db->query("SELECT `server` FROM panels WHERE module = '$short'");
            while ($row = $sql->fetch_object()) {
                $name = $db->query("SELECT name FROM monitoring_server WHERE ID = {$row->server}");
                if (!$name->num_rows) {
                    continue;
                }

                $servers[$row->server] = $name->fetch_object()->name;
            }
            asort($servers);
            foreach ($servers as $id => $name) {
                ?>
				<option value="<?=$id;?>"<?=$serverId == $id ? ' selected=""' : '';?>><?=htmlentities($name);?></option>
				<?php
}
            ?>
			<?php
$sql = $db->query("SELECT * FROM monitoring_server_groups ORDER BY `name` ASC");
            if ($sql->num_rows) {
                echo '<option disabled="">' . $lang['MONITORING']['GROUPS'] . '</option>';
            }

            while ($row = $sql->fetch_object()) {
                ?>
				<option value="group<?=$row->ID;?>"<?=$serverId == ("group" . $row->ID) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
				<?php
}
            ?>
				</select>
			</div>

			<script>
			mgmt = 0;

			function mgmtServer() {
				var sid = $("[name=_mgmt_server]").val();
				if (sid != 0) {
					$("[mgmt=1]").hide();
				} else {
					$("[mgmt=1]").show();
				}
			}

			$("[name=_mgmt_server]").change(mgmtServer);
			mgmtServer();
			</script>
			<?php
}

        die($module->Config($info->ID));
    }

    if (isset($_GET['module_mail'])) {
        if (empty($_GET['module_mail'])) {
            die("product");
        }

        $modules = $provisioning->get();
        if (!array_key_exists($_GET['module_mail'], $modules)) {
            die("<i>{$l['ERR4']}</i>");
        }

        $vars = $modules[$_GET['module_mail']]->AllEmailVariables();
        if (!is_array($vars) || count($vars) == 0) {
            die("product");
        } else {
            die("product, inclusive_domains[], addon_domains[], server[], " . implode(", ", $vars));
        }

    }

    if (isset($_GET['save'])) {
        $name = array();
        $desc = array();

        foreach ($languages as $lang_key => $lang_name) {
            if (!isset($_POST['name'][$lang_key]) || $_POST['name'][$lang_key] == "") {
                die($l['ERR5']);
            }

            $name[$lang_key] = $_POST['name'][$lang_key];
            $desc[$lang_key] = isset($_POST['desc'][$lang_key]) ? $_POST['desc'][$lang_key] : "";
        }

        $name = serialize($name);
        $desc = serialize($desc);

        if (!isset($_POST['cat']) || ($_POST['cat'] != 0 && $db->query("SELECT ID FROM product_categories WHERE ID = '" . $db->real_escape_string($_POST['cat'] . "' LIMIT 1")->num_rows != 1))) {
            die("Die angegebene Kategorie ist ung&uuml;ltig.");
        }

        if (!isset($_POST['available']) || trim($_POST['available']) == "") {
            $available = -1;
        } else {
            $available = intval($_POST['available']);
            if (!is_numeric($available) || $available < 0) {
                die($l['ERR6']);
            }

        }

        if (!isset($_POST['maxpc']) || trim($_POST['maxpc']) == "") {
            $maxpc = -1;
        } else {
            $maxpc = intval($_POST['maxpc']);
            if (!is_numeric($maxpc) || $maxpc < 0) {
                die($l['ERR7']);
            }

        }

        if (!isset($_POST['status']) || !in_array($_POST['status'], array("0", "1"))) {
            die($l['ERR8']);
        }

        $price = $nfo->phpize($_POST['price']);
        if (!isset($_POST['price']) || (!is_numeric($price) && !is_double($price)) || $price < 0) {
            die($l['ERR9']);
        }

        $currency_id = $_POST['currency'];
        if (empty($currency_id) || !array_key_exists($currency_id, $currencies)) {
            die($l['ERR10']);
        }

        $currency_active = $currency_price = 0;
        if ($currency_id != array_keys($currencies)[0]) {
            $currency_active = 1;
            $currency_price = $price;
            $price = $cur->convertAmount($currency_id, $currency_price, $cur->getBaseCurrency());
        }

        if (!isset($_POST['billing']) || !in_array($_POST['billing'], array("onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "trinnially", "minutely", "hourly"))) {
            die($l['ERR11']);
        }

        $setup = $nfo->phpize($_POST['setup']);
        if (!isset($_POST['setup']) || !is_numeric($setup)) {
            die($l['ERR12']);
        }

        $affiliate = $nfo->phpize($_POST['affiliate']);
        if (isset($_POST['affiliate']) && trim($affiliate) == "") {
            $affiliate = -1;
        } else if (!isset($_POST['affiliate']) || (!is_numeric($affiliate) && !is_double($affiliate)) || $affiliate < 0) {
            die($l['ERR13']);
        }

        if (!empty($_POST['ct1']) && (!is_numeric($_POST['ct1']) || $_POST['ct1'] < 0)) {
            die($l['ERR14']);
        }

        if (empty($_POST['ct1'])) {
            $ct = "";
        } else {
            $ct = intval($_POST['ct1']) . " ";
            if (empty($_POST['ct2']) || !in_array($_POST['ct2'], array("days", "months", "years"))) {
                die($l['ERR14']);
            }

            $ct .= $_POST['ct2'];
        }

        if (!empty($_POST['mct1']) && (!is_numeric($_POST['mct1']) || $_POST['mct1'] < 0)) {
            die($l['ERRMCT']);
        }

        if (empty($_POST['mct1'])) {
            $mct = "";
        } else {
            $mct = intval($_POST['mct1']) . " ";
            if (empty($_POST['mct2']) || !in_array($_POST['mct2'], array("days", "months", "years"))) {
                die($l['ERRMCT']);
            }

            $mct .= $_POST['mct2'];
        }

        if (!empty($_POST['np1']) && (!is_numeric($_POST['np1']) || $_POST['np1'] < 0)) {
            die($l['ERR15']);
        }

        if (empty($_POST['np1'])) {
            $np = "";
        } else {
            $np = intval($_POST['np1']) . " ";
            if (empty($_POST['np2']) || !in_array($_POST['np2'], array("days", "months", "years"))) {
                die($l['ERR15']);
            }

            $np .= $_POST['np2'];
        }

        if (!empty($_POST['module']) && !array_key_exists($_POST['module'], $provisioning->get())) {
            die($l['ERR16']);
        }

        $ip_product = intval($_POST['ip_product']);
        if ($ip_product < 0) {
            $ip_product = 0;
        }

        if ($ip_product) {
            if ($db->query("SELECT 1 FROM products WHERE ip_product = " . intval($info->ID))->num_rows) {
                die($l['ERR1']);
            }

            if (!$db->query("SELECT 1 FROM products WHERE ID = $ip_product AND type = 'HOSTING' AND ip_product = 0")->num_rows) {
                die($l['ERR2']);
            }
        }

        if (empty($_POST['email'])) {
            $_POST['email'] = "0";
        }

        if (!isset($_POST['email']) || !is_numeric($_POST['email']) || ($_POST['email'] != "0" && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($_POST['email']))->num_rows == 0)) {
            die($l['ERR17']);
        }

        $status = isset($_POST['status']) && $_POST['status'] == "1" ? "1" : "0";
        $incldomains = max(intval($_POST['incldomains']), 0);
        $incltlds = serialize($_POST['incltlds'] ?: []);
        $ncg = intval($_POST['new_cgroup']);
        $dns_template = intval($_POST['dns_template']);

        parse_str($_POST['customer_groups'], $cg);
        if (isset($cg['customer_groups']) && is_array($cg['customer_groups'])) {
            $cg = $cg['customer_groups'];
        } else {
            $cg = [];
        }

        $cg = implode(",", $cg);

        $autodelete = max(0, intval($_POST['autodelete']));
        $ma = max(0, intval($_POST['min_age']));

        $pcg = isset($_POST['price_cgroups']) && is_array($_POST['price_cgroups']) ? $_POST['price_cgroups'] : [];
        foreach ($pcg as &$v) {
            $v[0] = doubleval($nfo->phpize($v[0]));
            $v[1] = doubleval($nfo->phpize($v[1]));
        }
        unset($v);
        $pcg = serialize($pcg);

        if (is_array($_POST['product_change'])) {
            $product_change = implode(",", array_values($_POST['product_change']));
        } else {
            $product_change = "";
        }

        if (is_array($_POST['usage_billing'])) {
            $ub = [];

            foreach ($_POST['usage_billing'] as $k => $v) {
                $v[0] = intval($nfo->phpize($v[0]));
                $v[1] = doubleval($nfo->phpize($v[1]));
                $v[2] = intval($nfo->phpize($v[2]));

                $ub[$k] = $v;
            }

            $ub = serialize($ub);
        } else {
            $ub = "";
        }

        $variants = [];
        @parse_str($_POST['variants'], $data);
        if (is_array($data) && array_key_exists("variants", $data)) {
            $data = $data['variants'];
            foreach ($data as $k => $v) {
                if (is_numeric($k)) {
                    $variants[] = [
                        "price" => doubleval($nfo->phpize($v['price'])),
                        "currency" => array_key_exists($v['currency'], $currencies) ? $v['currency'] : array_keys($currencies)[0],
                        "billing" => in_array($v['billing'], array("onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "trinnially")) ? $v['billing'] : "onetime",
                        "setup" => doubleval($nfo->phpize($v['setup'])),
                        "ct1" => is_numeric($v['ct1']) && $v['ct1'] > 0 ? intval($v['ct1']) : "",
                        "ct2" => in_array($v['ct2'], ["days", "months", "years"]) ? $v['ct2'] : "days",
                        "mct1" => is_numeric($v['mct1']) && $v['mct1'] > 0 ? intval($v['mct1']) : "",
                        "mct2" => in_array($v['mct2'], ["days", "months", "years"]) ? $v['mct2'] : "days",
                        "np1" => is_numeric($v['np1']) && $v['np1'] > 0 ? intval($v['np1']) : "",
                        "np2" => in_array($v['np2'], ["days", "months", "years"]) ? $v['np2'] : "days",
                        "affiliate" => !empty($v['affiliate']) ? doubleval($nfo->phpize($v['affiliate'])) : -1,
                    ];
                }
            }
        }
        $variants = serialize($variants);

        $uf = trim(strval($_POST['username_format']));
        $un = max(1, intval($_POST['username_next']));
        $us = max(1, intval($_POST['username_step']));

        $sql = $db->prepare("UPDATE products SET dns_template = ?, tax = ?, prepaid = ?, prorata = ?, min_age = ?, usage_billing = ?, customer_groups = ?, only_verified = ?, public = ?, hide = ?, preorder = ?, name = ?, status = ?, price = ?, incltlds = ?, incldomains = ?, category = ?, description = ?, affiliate = ?, billing = ?, module = ?, welcome_mail = ?, ct = ?, np = ?, setup = ?, available = ?, maxpc = ?, currency_active = ?, currency_price = ?, currency_id = ?, autodelete = ?, ip_product = ?, price_cgroups = ?, product_change = ?, new_cgroup = ?, mct = ?, desc_on_invoice = ?, domain_choose = ?, variants = ?, username_format = ?, username_step = ?, username_next = ? WHERE ID = ?");
        $sql->bind_param("isiiissiiiisidsiisdssissdiiidiiissisiissiii", $dns_template, $taxHl = $_POST['tax'] ?? "gross", $prepaid = !empty($_POST['prepaid']) ? 1 : 0, $prorata = (!empty($_POST['prorata']) && !$prepaid) ? 1 : 0, $ma, $ub, $cg, $ov = !empty($_POST['only_verified']) ? 1 : 0, $pl = !empty($_POST['public']) ? 1 : 0, $hidepar = !empty($_POST['hide']) ? 1 : 0, $po = !empty($_POST['preorder']) ? 1 : 0, $name, $status, $price, $incltlds, $incldomains, $_POST['cat'], $desc, $affiliate, $_POST['billing'], $_POST['module'], $_POST['email'], $ct, $np, $setup, $available, $maxpc, $currency_active, $currency_price, $currency_id, $autodelete, $ip_product, $pcg, $product_change, $ncg, $mct, $doi = !empty($_POST['desc_on_invoice']) ? 1 : 0, $dc = !empty($_POST['domain_choose']) ? 1 : 0, $variants, $uf, $us, $un, $info->ID);
        $sql->execute();

        parse_str($_POST['customfields'] ?? "", $customfields);
        if (is_array($customfields) && array_key_exists("fields", $customfields)) {
            $customfields = $customfields["fields"];
            unset($customfields["-#ID#"]);
        } else {
            $customfields = [];
        }

        $keys = array_keys($customfields);
        foreach ($keys as $k => $v) {
            if ($v <= 0) {
                unset($keys[$k]);
            } else {
                $keys[$k] = intval($v);
            }
        }

        if (count($keys) == 0) {
            $keys = [0];
        }

        $db->query("DELETE FROM products_cf WHERE product = {$info->ID} AND ID NOT IN (" . implode(",", $keys) . ")");

        foreach ($customfields as $i => $f) {
            if (empty($f['name'])) {
                if ($i > 0) {
                    $db->query("DELETE FROM products_cf WHERE ID = " . intval($i));
                }

                continue;
            }

            if ($f['type'] == "number") {
                if (intval($f['default']) != $f['default']) {
                    $f['default'] = "0";
                }

                if (intval($f['minimum']) != $f['minimum']) {
                    $f['minimum'] = "0";
                }

                if (trim($f['maximum']) == "" || intval($f['maximum']) != $f['maximum']) {
                    $f['maximum'] = "-1";
                }

                $f['amount'] = $nfo->phpize($f['amount']);
                if (doubleval($f['amount']) != $f['amount']) {
                    $f['amount'] = 0.00;
                }
            }

            if (array_key_exists("costs", $f)) {
                $ex = explode("|", $f["costs"]);

                if (count($ex) > 100) {
                    $ex = [];
                }

                foreach ($ex as &$val) {
                    $val = $nfo->phpize(trim($val));
                }
                unset($val);
                $f['costs'] = implode("|", $ex);
            }

            $name = $f['name'];
            $type = $f['type'];
            unset($f['name'], $f['type']);

            if ($db->query("SELECT 1 FROM products_cf WHERE ID = " . intval($i))->num_rows) {
                $db->query("UPDATE products_cf SET product = {$info->ID}, name = '" . $db->real_escape_string($name) . "', type = '" . $db->real_escape_string($type) . "', options = '" . $db->real_escape_string(serialize($f)) . "' WHERE ID = " . intval($i));
            } else {
                $db->query("INSERT INTO products_cf (product, name, type, options) VALUES ({$info->ID}, '" . $db->real_escape_string($name) . "', '" . $db->real_escape_string($type) . "', '" . $db->real_escape_string(serialize($f)) . "')");
            }

        }

        if (!empty($_POST['module']) && is_array($_POST['prov'])) {
            $db->query("DELETE FROM product_provisioning WHERE pid = {$info->ID} AND module = '" . $db->real_escape_string($_POST['module']) . "'");

            foreach ($_POST['prov'] as $k => $v) {
                $v = encrypt($v);
                $db->query("INSERT INTO product_provisioning (module, pid, setting, value) VALUES ('" . $db->real_escape_string($_POST['module']) . "', {$info->ID}, '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string($v) . "')");
            }
        }

        parse_str($_POST['prepaid_days'], $ppDays);

        $exPpDays = [];
        $newPpDays = [];
        if (is_array($ppDays) && array_key_exists("ppDay", $ppDays)) {
            $ppDays = $ppDays["ppDay"];

            foreach ($ppDays as $ppId => $ppDet) {
                if ($ppId == "new#i#") {
                    continue;
                }

                if (substr($ppId, 0, 3) == "new") {
                    $target = "newPpDays";
                    $ppId = substr($ppId, 3);
                } else {
                    $target = "exPpDays";
                }

                $$target[intval($ppId)] = [
                    intval($ppDet["days"] ?? 30),
                    round(doubleval($nfo->phpize($ppDet["bonus"]) ?? 0), 2),
                ];
            }
        }

        $ppIdList = implode(",", array_keys($exPpDays));
        $db->query("DELETE FROM products_prepaid WHERE product = {$info->ID} AND ID NOT IN ($ppIdList)");

        foreach ($exPpDays as $ppId => $ppDet) {
            $db->query("UPDATE products_prepaid SET days = {$ppDet[0]}, bonus = {$ppDet[1]} WHERE ID = $ppId AND product = {$info->ID}");
        }

        foreach ($newPpDays as $ppId => $ppDet) {
            $db->query("INSERT INTO products_prepaid (days, bonus, product) VALUES ({$ppDet[0]}, {$ppDet[1]}, {$info->ID})");
        }

        alog("product", "save", $info->ID);

        die("ok");
    }

    title(unserialize($info->name)[$CFG['LANG']]);
    ?>
<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE2'];?> <small><?=unserialize($info->name)[$CFG['LANG']];?></small></h1>

		<div class="alert alert-danger" id="err" style="display: none;"></div>
		<div class="alert alert-success" id="suc" style="display: none;"><?=$l['SAVEDSUC'];?></div>

		<div>
		  <ul class="nav nav-tabs nav-justified" role="tablist">
		    <li class="active"><a href="#general" aria-controls="general" role="tab" data-toggle="tab"><?=$l['TAB1'];?></a></li>
		    <li><a href="#pricing" aria-controls="pricing" role="tab" data-toggle="tab"><?=$l['TAB2'];?></a></li>
		    <li><a href="#fields" aria-controls="fields" role="tab" data-toggle="tab"><?=$l['TAB4'];?></a></li>
			<li><a href="#provisioning" aria-controls="provisioning" role="tab" data-toggle="tab"><?=$l['TAB5'];?></a></li>
			<li id="username_btn" style="display: none;"><a href="#username" aria-controls="username" role="tab" data-toggle="tab"><?=$l['TABUN'];?></a></li>
			<li id="ip_tab_btn" style="display: none;"><a href="#ip" aria-controls="ip" role="tab" data-toggle="tab"><?=$l['TAB6'];?></a></li>
		    <li><a href="#email" aria-controls="email" role="tab" data-toggle="tab"><?=$l['TAB7'];?></a></li>
		    <li><a href="#links" aria-controls="links" role="tab" data-toggle="tab"><?=$l['TAB_LINKS'];?></a></li>
		  </ul>

		  <br /><div class="tab-content">
		    <div role="tabpanel" class="tab-pane active" id="general">
		    	<div class="form-group">
		    		<label><?=$l['NAMEDESC'];?></label><?php foreach ($languages as $lang_key => $lang_name) {?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == $CFG['LANG'] ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
                <?php }?><br />

					<?php foreach ($languages as $lang_key => $lang_name) {
        $name = unserialize($info->name)[$lang_key];
        $desc = unserialize($info->description)[$lang_key];
        ?>
					    <div is-lang="<?=$lang_key;?>"<?=$lang_key != $CFG['LANG'] ? ' style="display: none;"' : '';?>>
					          	<input type="text" data-lang="<?=$lang_key;?>" class="form-control pname" placeholder="<?=$l['NAMEOFP'];?>" value="<?=htmlentities($name);?>">
					            <textarea data-lang="<?=$lang_key;?>" class="form-control pdesc" style="resize:none; height: 250px; margin-top: 10px;"><?=htmlentities($desc);?></textarea>
					    </div>
					<?php }?>
		    	</div>

		    	<div class="form-group">
				    <label><?=$l['CAT'];?></label>
				   <select name="category" class="form-control">
				   	<option value="0" <?php if ((isset($_POST['category']) && $_POST['category'] == 0) || (!isset($_POST['category']) && $info->category == 0)) {
        echo "selected=\"selected\"";
    }
    ?>><?=$l['NOCAT'];?></option>
				   	<?php
$catSql = $db->query("SELECT * FROM product_categories");

    $cats = array();
    while ($c = $catSql->fetch_object()) {
        $cats[unserialize($c->name)[$CFG['LANG']]] = $c;
    }

    ksort($cats);

    foreach ($cats as $c) {
        ?>
					<option value="<?=$c->ID;?>" <?php if ((isset($_POST['category']) && $_POST['category'] == $c->ID) || (!isset($_POST['category']) && $info->category == $c->ID)) {
            echo "selected=\"selected\"";
        }
        ?>><?=unserialize($c->name)[$CFG['LANG']];?></option>
				   	<?php
}
    ?>
				   </select>
				  </div>

				<div class="form-group">
					<label><?=$l['INCLDOMAINS'];?></label>
					<div class="form-group">
						<input type="text" name="incldomains" class="form-control" value="<?=isset($_POST['incldomains']) ? $_POST['incldomains'] : ($info->incldomains >= 0 ? $info->incldomains : "0");?>" placeholder="0">
					</div>
				</div>

				<div class="form-group">
					<label><?=$l['INCLTLDS'];?></label>
					<select name="incltlds" class="form-control" multiple="multiple">
						<?php
$sql = $db->query("SELECT ID, tld FROM domain_pricing ORDER BY top DESC, tld ASC");
    $incltlds = unserialize($info->incltlds) ?: [];
    while ($row = $sql->fetch_object()) {?>
						<option value="<?=$row->ID;?>"<?php if (in_array($row->ID, $incltlds)) {
        echo ' selected=""';
    }
        ?>><?=ltrim($row->tld, ".");?></option>
						<?php }?>
					</select>
				</div>

				<div class="form-group">
					<label><?=$l['DNSTPL'];?></label>
					<select name="dns_template" class="form-control">
						<option value="0"><?=$l['DNSTPLD'];?></option>
						<?php
$sql = $db->query("SELECT ID, `name` FROM dns_templates ORDER BY ID = 1 DESC, `name` ASC");
    while ($row = $sql->fetch_object()) {
        if ($row->ID == 1) {
            $row->name = $lang['DOMAINS']['DNSDEF'];
        }
        ?>
							<option value="<?=$row->ID;?>"<?=$row->ID == $info->dns_template ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
							<?php
}
    ?>
					</select>
				</div>

			   <div class="form-group">
			    <label><?=$l['AVAILABLE'];?></label>
			    <div class="form-group">
			   		<input type="text" name="available" class="form-control" value="<?=isset($_POST['available']) ? $_POST['available'] : ($info->available >= 0 ? $info->available : "");?>" placeholder="<?=$l['AVAILABLEP'];?>">
			   	</div>
			  </div>

			  <div class="form-group">
			    <label><?=$l['MAXPC'];?></label>
			    <div class="form-group">
			   		<input type="text" name="maxpc" class="form-control" value="<?=isset($_POST['maxpc']) ? $_POST['maxpc'] : ($info->maxpc >= 0 ? $info->maxpc : "");?>" placeholder="<?=$l['AVAILABLEP'];?>">
			   	</div>
			  </div>

			  <div class="form-group">
		    		<label><?=$l['AUTODELETE'];?></label>
		    		<div class="input-group">
						<span class="input-group-addon"><?=$l['AUTDEL1'];?></span>
						<input type="text" name="autodelete" class="form-control" placeholder="<?=$l['AUTDELO'];?>" value="<?=$info->autodelete ?: "";?>" />
						<span class="input-group-addon"><?=$l['AUTDEL2'];?></span>
					</div>
					<p class="help-block"><?=$l['AUTDELH'];?></p>
				</div>

			<div class="form-group">
				<label><?=$lang['SETTINGS']['MIN_AGE'];?></label>
				<input type="text" name="min_age" value="<?=max(0, intval($info->min_age)) ?: "";?>" placeholder="<?=$lang['SETTINGS']['MIN_AGEP'];?>" class="form-control">
			</div>

			  <div class="form-group">
					<label><?=$l['CGR'];?></label>
					<select name="customer_groups[]" class="form-control" id="cg" multiple="multiple">
						<?php $client_groups = explode(",", $info->customer_groups) ?: [];?>
						<option value="0"<?php if (in_array("0", $client_groups)) {
        echo ' selected=""';
    }
    ?>><?=$l['NOCG'];?></option>
						<?php
$sql = $db->query("SELECT ID, name FROM client_groups ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {?>
						<option value="<?=$row->ID;?>"<?php if (in_array($row->ID, $client_groups)) {
        echo ' selected=""';
    }
        ?>><?=htmlentities($row->name);?></option>
						<?php }?>
					</select>
					<p class="help-block"><?=$l['CGRH'];?></p>
				</div>

				<div class="form-group">
    <label><?=$lang['PRODUCTS']['NEW_CGROUP'];?></label>
    <div class="form-group">
        <?php $ncg = $_POST['new_cgroup'] ?? $info->new_cgroup;?>
   		<select name="new_cgroup" class="form-control">
            <option value="-1"><?=$lang['PRODUCTS']['CGNOCHANGE'];?></option>
            <option value="0"<?=$ncg == "0" ? ' selected=""' : '';?>><?=$lang['PRODUCTS']['CGREMOVE'];?></option>
            <?php
$sql = $db->query("SELECT ID, name FROM client_groups ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        ?>
            <option value="<?=$row->ID;?>"<?=$ncg == $row->ID ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
            <?php }?>
        </select>
   	</div>
  </div>

			  <div class="checkbox">
			  	<label>
				  <input type="checkbox" name="public" value="1"<?=$info->public ? ' checked=""' : '';?>>
				  <?=$l['PUBLIC'];?>
				</label>
			  </div>

			  <div class="checkbox">
			  	<label>
				  <input type="checkbox" name="only_verified" value="1"<?=$info->only_verified ? ' checked=""' : '';?>>
				  <?=$l['ONLY_VERIFIED'];?>
				</label>
			  </div>

			  <div class="checkbox">
			  	<label>
				  <input type="checkbox" name="hide" value="1"<?=$info->hide ? ' checked=""' : '';?>>
				  <?=$l['HIDE'];?>
				</label>
			  </div>

			  <div class="checkbox">
			  	<label>
				  <input type="checkbox" name="preorder" value="1"<?=$info->preorder ? ' checked=""' : '';?>>
				  <?=$l['PREORDER'];?>
				</label>
			  </div>

			  <div class="checkbox">
			  	<label>
				  <input type="checkbox" name="desc_on_invoice" value="1"<?=$info->desc_on_invoice ? ' checked=""' : '';?>>
				  <?=$l['DESCONINV'];?>
				</label>
			  </div>

			  <div class="checkbox">
			  	<label>
				  <input type="checkbox" name="domain_choose" value="1"<?=$info->domain_choose ? ' checked=""' : '';?>>
				  <?=$l['DOMAIN_CHOOSE'];?>
				</label>
			  </div>

		    	<div class="form-group">
				<label><?=$l['STATUS'];?></label><br />
				<label class="radio-inline">
				<input type="radio" name="status" value="1" <?=isset($_POST['status']) ? ($_POST['status'] == "1" ? "checked" : "") : ($info->status == "1" ? "checked" : "");?>>
				<?=$l['S1'];?>
				</label>
				<label class="radio-inline">
				<input type="radio" name="status" value="0" <?=isset($_POST['status']) ? ($_POST['status'] == "0" ? "checked" : "") : ($info->status == "0" ? "checked" : "");?>>
				<?=$l['S0'];?>
				</label>
				</div>
		    </div>

			<?php
$variants = @unserialize($info->variants);
    if (!is_array($variants)) {
        $variants = [];
    }
    ?>

			<script>
			var variants = <?=count($variants) + 1;?>;

			$(document).ready(function() {
				$(document).ajaxStop(function () {
					if (0 === $.active) {
						$("#save").attr("disabled", false);
					}
				});

				$("#add_variant").click(function(e) {
					e.preventDefault();
					variants++;
					$('<li><a href="#variant' + variants + '" data-toggle="tab"><?=$l['VARIANT'];?> ' + variants + '</a></li>').insertBefore($("#add_variant").parent());
					var html = $("#variant-ID-").clone().wrap('<div>').parent().html().replace(/-ID-/g, variants);
					$(html).appendTo("#variant_container");
					$("[href=#variant" + variants + "]").click();
					bindDelVariant();
				});

				function bindDelVariant() {
					$(".delete_variant").click(function(e) {
						e.preventDefault();
						$("#variant" + $(this).data("variant")).remove();
						$("[href='#variant" + $(this).data("variant") + "']").remove();
						$("[href='#variant1']").click();
					});
				}
				bindDelVariant();
			});
			</script>

		    <div role="tabpanel" class="tab-pane" id="pricing">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="prepaid" value="1"<?=$info->prepaid ? ' checked=""' : '';?>>
						<?=$l['PREPAID'];?>
					</label>
				</div>

				<script>
				function prepaidHandler() {
					if ($("[name=prepaid]").is(":checked")) {
						$(".ctf").val("");
						$(".ctg").hide();
						$("#prepaidDays").show();
					} else {
						$(".ctg").show();
						$("#prepaidDays").hide();

						var billing = $("[name=billing]").val();

						if (billing == "minutely" || billing == "hourly") {
							$(".no-cloud").hide();
						}
					}
				}

				$("[name=prepaid]").click(prepaidHandler);
				</script>

				<div class="panel panel-default" id="prepaidDays" style="display: none;">
					<div class="panel-body">
					<form id="prepaidDaysForm">
						<div class="table-responsive">
							<table class="table table-bordered table-striped" id="prepaidDaysTable">
								<tr>
									<th width="50%"><?=$l['PREPAID_OPTION'];?></th>
									<th><?=$l['PREPAID_BONUS'];?></th>
									<td width="28px"></td>
								</tr>

								<?php
$ppDaySql = $db->query("SELECT `ID`, `days`, `bonus` FROM products_prepaid WHERE product = {$info->ID}");
    while ($ppDay = $ppDaySql->fetch_object()) {
        ?>
								<tr class="ppDayRow">
									<td>
										<span class="input-group">
											<input type="text" name="ppDay[<?=$ppDay->ID;?>][days]" class="form-control input-sm" value="<?=$ppDay->days;?>">
											<span class="input-group-addon"><?=$l['PREPAID_DAYS'];?></span>
										</span>
									</td>
									<td>
										<span class="input-group">
										<input type="text" name="ppDay[<?=$ppDay->ID;?>][bonus]" class="form-control input-sm" value="<?=$nfo->format($ppDay->bonus);?>">
											<span class="input-group-addon">%</span>
										</span>
									</td>
									<td>
										<a href="#" onclick="$(this).parent().parent().remove(); return false;"><i class="fa fa-times"></i></a>
									</td>
								</tr>
								<?php
}
    ?>

								<tr id="ppDayNew" style="display: none;">
									<td>
										<span class="input-group">
											<input type="text" name="ppDay[new#i#][days]" value="30" class="form-control input-sm">
											<span class="input-group-addon"><?=$l['PREPAID_DAYS'];?></span>
										</span>
									</td>
									<td>
										<span class="input-group">
											<input type="text" name="ppDay[new#i#][bonus]" value="<?=$nfo->format(0);?>" class="form-control input-sm">
											<span class="input-group-addon">%</span>
										</span>
									</td>
									<td>
										<a href="#" onclick="$(this).parent().parent().remove(); return false;"><i class="fa fa-times"></i></a>
									</td>
								</tr>

								<tr style="display: none;"></tr>
							</table>
						</div>
</form>
						<a href="#" id="ppAddDay"><i class="fa fa-plus-circle"></i> <?=$l['PREPAID_ADD'];?></a>

						<script>
						var ppDayI = 0;

						$("#ppAddDay").click(function(e) {
							e.preventDefault();

							var clone = $("#ppDayNew").clone();
							clone.show().addClass("ppDayRow").removeAttr("id").html(clone.html().replace(/\#i\#/g, ppDayI++));
							$("#prepaidDaysTable").append(clone);
						});

						$(document).ready(function() {
							if (!$(".ppDayRow").length) {
								$("#ppAddDay").click();
							}
						});
						</script>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-body">
					<ul class="nav nav-pills" role="tablist">
						<li class="active"><a href="#variant1" data-toggle="tab"><?=$l['VARIANT'];?> 1</a></li>
						<?php for ($i = 2; $i <= count($variants) + 1; $i++) {?>
						<li><a href="#variant<?=$i;?>" data-toggle="tab"><?=$l['VARIANT'];?> <?=$i;?></a></li>
						<?php }?>
						<li><a href="#" id="add_variant"><i class="fa fa-plus-circle"></i></a></li>
					</ul><br />

				<div class="tab-content" id="variant_container">
    			<div role="tabpanel" class="tab-pane active" id="variant1">
		    	<div class="form-group">
					<label><?=$l['PRICE'];?></label>
		    		<div class="row">
		    			<div class="col-md-6">
		    				<input type="text" name="price" value="<?=$nfo->format($price = ($info->currency_active ? $info->currency_price : $info->price), max(2, strlen(substr(strrchr(rtrim($price, "0"), "."), 1))));?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
		    			</div>

		    			<div class="col-md-3">
		    				<select name="currency" class="form-control">
		    					<?php foreach ($currencies as $id => $name) {?>
		    					<option value="<?=$id;?>"<?php if ($info->currency_active && $info->currency_id == $id) {
        echo ' selected="selected"';
    }
        ?>><?=$name;?></option>
		    					<?php }?>
		    				</select>
		    			</div>

		    			<div class="col-md-3">
		    				<select name="billing" class="form-control">
		    					<option value="onetime"><?=$l['ONETIME'];?></option>
		    					<option value="monthly"<?=$info->billing == "monthly" ? ' selected="selected"' : '';?>><?=$l['PERMONTH'];?></option>
		    					<option value="quarterly"<?=$info->billing == "quarterly" ? ' selected="selected"' : '';?>><?=$l['PER3MONTHS'];?></option>
		    					<option value="semiannually"<?=$info->billing == "semiannually" ? ' selected="selected"' : '';?>><?=$l['PER6MONTHS'];?></option>
		    					<option value="annually"<?=$info->billing == "annually" ? ' selected="selected"' : '';?>><?=$l['PERYEAR'];?></option>
								<option value="biennially"<?=$info->billing == "biennially" ? ' selected="selected"' : '';?>><?=$l['PER2YEAR'];?></option>
								<option value="trinnially"<?=$info->billing == "trinnially" ? ' selected="selected"' : '';?>><?=$l['PER3YEAR'];?></option>
								<option value="minutely"<?=$info->billing == "minutely" ? ' selected="selected"' : '';?>><?=$l['PERMINUTE'];?></option>
								<option value="hourly"<?=$info->billing == "hourly" ? ' selected="selected"' : '';?>><?=$l['PERHOUR'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

				<script>
				$(document).ready(function() {
					function cloudHandler() {
						var billing = $("[name=billing]").val();

						if (billing == "minutely" || billing == "hourly") {
							$(".no-cloud").hide();
						} else {
							$(".no-cloud").show();
						}
					}

					$("[name=billing]").change(cloudHandler);
					prepaidHandler();
				});
				</script>

		    	<div class="form-group">
		    		<label><?=$l['SETUP'];?></label>
		    		<input type="text" name="setup" value="<?=$nfo->format($info->setup);?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
		    	</div>

				<div class="form-group no-cloud ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['CT'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="ct1" class="form-control ctf" placeholder="<?=$l['CTP'];?>" value="<?=explode(" ", $info->ct)[0];?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="ct2" class="form-control">
		    					<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"<?=explode(" ", $info->ct)[1] == "months" ? ' selected="selected"' : '';?>><?=$l['MONTHS'];?></option>
		    					<option value="years"<?=explode(" ", $info->ct)[1] == "years" ? ' selected="selected"' : '';?>><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

				<div class="form-group no-cloud ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['MCT'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="mct1" class="form-control ctf" placeholder="<?=$l['CTP'];?>" value="<?=explode(" ", $info->mct)[0];?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="mct2" class="form-control">
		    					<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"<?=explode(" ", $info->mct)[1] == "months" ? ' selected="selected"' : '';?>><?=$l['MONTHS'];?></option>
		    					<option value="years"<?=explode(" ", $info->mct)[1] == "years" ? ' selected="selected"' : '';?>><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group no-cloud ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['NP'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="np1" class="form-control ctf" placeholder="<?=$l['CTP'];?>" value="<?=explode(" ", $info->np)[0];?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="np2" class="form-control">
							<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"<?=explode(" ", $info->np)[1] == "months" ? ' selected="selected"' : '';?>><?=$l['MONTHS'];?></option>
		    					<option value="years"<?=explode(" ", $info->np)[1] == "years" ? ' selected="selected"' : '';?>><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group">
		    		<label><?=$l['AFFILIATE'];?></label>
		    		<div class="input-group">
		    			<input type="text" name="affiliate" value="<?=$info->affiliate < 0 ? "" : $nfo->format($info->affiliate);?>" placeholder="<?=$l['AFFILIATEP'];?>" class="form-control" />
		    			<span class="input-group-addon">%</span>
		    		</div>
				</div>

				<label><?=$l['CGP'];?></label>
				<div class="table-responsive">
					<table class="table table-bordered table-striped" style="margin-bottom: 0;">
						<tr>
							<th width="50%"><?=$l['CG'];?></th>
							<th width="25%"><?=$l['ACGP'];?></th>
							<th width="25%"><?=$l['ACGS'];?></th>
						</tr>

						<?php
$pcg = unserialize($info->price_cgroups);
    if (!is_array($pcg)) {
        $pcg = [];
    }
    $sql = $db->query("SELECT * FROM client_groups ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        $is = array_key_exists($row->ID, $pcg);

        $pcgp = $pcgs = "";
        if ($is) {
            $pcgp = $nfo->format($pcg[$row->ID][0]);
            $pcgs = $nfo->format($pcg[$row->ID][1]);
        }
        ?>
						<tr style="height: 47px;" class="pcg_row">
							<td><i class="fa fa-circle<?=!$is ? "-o" : "";?> other_pricing" style="color: <?=$row->color;?>; cursor: pointer;"></i> <?=htmlentities($row->name);?></td>
							<td><span class="pcg_no" style="<?=$is ? 'display: none;' : '';?>"><?=$l['NO'];?></span><span class="pcg_yes" style="<?=!$is ? 'display: none;' : '';?>"><span class="input-group"><?=$cur->getPrefix() ? '<span class="input-group-addon">' . $cur->getPrefix() . '</span>' : '';?><input type="text" class="form-control input-sm pcg_price" data-id="<?=$row->ID;?>" placeholder="<?=$nfo->placeholder();?>" value="<?=$pcgp;?>"><?=$cur->getSuffix() ? '<span class="input-group-addon">' . $cur->getSuffix() . '</span>' : '';?></span></span></td>
							<td><span class="pcg_no" style="<?=$is ? 'display: none;' : '';?>"><?=$l['NO'];?></span><span class="pcg_yes" style="<?=!$is ? 'display: none;' : '';?>"><span class="input-group"><?=$cur->getPrefix() ? '<span class="input-group-addon">' . $cur->getPrefix() . '</span>' : '';?><input type="text" class="form-control input-sm pcg_setup" data-id="<?=$row->ID;?>" placeholder="<?=$nfo->placeholder();?>" value="<?=$pcgs;?>"><?=$cur->getSuffix() ? '<span class="input-group-addon">' . $cur->getSuffix() . '</span>' : '';?></span></span></td>
						</tr>
						<?php }?>
					</table>
				</div>

				<p style="margin-bottom: 0;"><small><?=$l['CGPH'];?></small></p>
				</div>
				<?php
for ($i = 2; $i <= count($variants) + 1; $i++) {
        $variant = array_values($variants)[$i - 2];
        ?>
				<div role="tabpanel" class="tab-pane" id="variant<?=$i;?>">
				<a href="#" class="delete_variant btn btn-danger btn-block" data-variant="<?=$i;?>"><i class="fa fa-times"></i> <?=$lang['PRODUCTS']['DEL'];?></a><br />

		    	<div class="form-group">
					<label><?=$l['PRICE'];?></label>
		    		<div class="row">
		    			<div class="col-md-6">
		    				<input type="text" name="variants[<?=$i - 2;?>][price]" value="<?=$nfo->format($variant["price"]);?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
		    			</div>

		    			<div class="col-md-3">
		    				<select name="variants[<?=$i - 2;?>][currency]" class="form-control">
		    					<?php foreach ($currencies as $id => $name) {?>
		    					<option value="<?=$id;?>"<?php if ($variant["currency"] && $variant["currency"] == $id) {
            echo ' selected="selected"';
        }
            ?>><?=$name;?></option>
		    					<?php }?>
		    				</select>
		    			</div>

		    			<div class="col-md-3">
		    				<select name="variants[<?=$i - 2;?>][billing]" class="form-control">
		    					<option value="onetime"><?=$l['ONETIME'];?></option>
		    					<option value="monthly"<?=$variant["billing"] == "monthly" ? ' selected="selected"' : '';?>><?=$l['PERMONTH'];?></option>
		    					<option value="quarterly"<?=$variant["billing"] == "quarterly" ? ' selected="selected"' : '';?>><?=$l['PER3MONTHS'];?></option>
		    					<option value="semiannually"<?=$variant["billing"] == "semiannually" ? ' selected="selected"' : '';?>><?=$l['PER6MONTHS'];?></option>
		    					<option value="annually"<?=$variant["billing"] == "annually" ? ' selected="selected"' : '';?>><?=$l['PERYEAR'];?></option>
								<option value="biennially"<?=$variant["billing"] == "biennially" ? ' selected="selected"' : '';?>><?=$l['PER2YEAR'];?></option>
								<option value="trinnially"<?=$variant["billing"] == "trinnially" ? ' selected="selected"' : '';?>><?=$l['PER3YEAR'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group">
		    		<label><?=$l['SETUP'];?></label>
		    		<input type="text" name="variants[<?=$i - 2;?>][setup]" value="<?=$nfo->format($variant["setup"]);?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
		    	</div>

				<div class="form-group ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['CT'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="variants[<?=$i - 2;?>][ct1]" class="form-control ctf" placeholder="<?=$l['CTP'];?>" value="<?=intval($variant["ct1"]) ?: "";?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="variants[<?=$i - 2;?>][ct2]" class="form-control">
		    					<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"<?=$variant["ct2"] == "months" ? ' selected="selected"' : '';?>><?=$l['MONTHS'];?></option>
		    					<option value="years"<?=$variant["ct2"] == "years" ? ' selected="selected"' : '';?>><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

				<div class="form-group ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['MCT'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="variants[<?=$i - 2;?>][mct1]" class="form-control ctf" placeholder="<?=$l['CTP'];?>" value="<?=intval($variant["mct1"]) ?: "";?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="variants[<?=$i - 2;?>][mct2]" class="form-control">
		    					<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"<?=$variant["mct2"] == "months" ? ' selected="selected"' : '';?>><?=$l['MONTHS'];?></option>
		    					<option value="years"<?=$variant["mct2"] == "years" ? ' selected="selected"' : '';?>><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['NP'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="variants[<?=$i - 2;?>][np1]" class="form-control ctf" placeholder="<?=$l['CTP'];?>" value="<?=intval($variant["np1"]) ?: "";?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="variants[<?=$i - 2;?>][np2]" class="form-control">
							<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"<?=$variant["np2"] == "months" ? ' selected="selected"' : '';?>><?=$l['MONTHS'];?></option>
		    					<option value="years"<?=$variant["np2"] == "years" ? ' selected="selected"' : '';?>><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group">
		    		<label><?=$l['AFFILIATE'];?></label>
		    		<div class="input-group">
		    			<input type="text" name="variants[<?=$i - 2;?>][affiliate]" value="<?=!is_numeric($variant["affiliate"]) || $variant["affiliate"] < 0 ? "" : $nfo->format($variant["affiliate"]);?>" placeholder="<?=$l['AFFILIATEP'];?>" class="form-control" />
		    			<span class="input-group-addon">%</span>
		    		</div>
				</div>
				</div>
<?php }?>
				<div role="tabpanel" class="tab-pane" id="variant-ID-">
				<a href="#" class="delete_variant btn btn-danger btn-block" data-variant="-ID-"><i class="fa fa-times"></i> <?=$lang['PRODUCTS']['DEL'];?></a><br />
				<div class="form-group">
					<label><?=$l['PRICE'];?></label>
		    		<div class="row">
		    			<div class="col-md-6">
		    				<input type="text" name="variants[-ID-][price]" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
		    			</div>

		    			<div class="col-md-3">
		    				<select name="variants[-ID-][currency]" class="form-control">
		    					<?php foreach ($currencies as $id => $name) {?>
		    					<option value="<?=$id;?>"><?=$name;?></option>
		    					<?php }?>
		    				</select>
		    			</div>

		    			<div class="col-md-3">
		    				<select name="variants[-ID-][billing]" class="form-control">
		    					<option value="onetime"><?=$l['ONETIME'];?></option>
		    					<option value="monthly"><?=$l['PERMONTH'];?></option>
		    					<option value="quarterly"><?=$l['PER3MONTHS'];?></option>
		    					<option value="semiannually"><?=$l['PER6MONTHS'];?></option>
		    					<option value="annually"><?=$l['PERYEAR'];?></option>
								<option value="biennially"><?=$l['PER2YEAR'];?></option>
								<option value="trinnially"><?=$l['PER3YEAR'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group">
		    		<label><?=$l['SETUP'];?></label>
		    		<input type="text" name="variants[-ID-][setup]" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
		    	</div>

				<div class="form-group ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['CT'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="variants[-ID-][ct1]" class="form-control ctf" placeholder="<?=$l['CTP'];?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="variants[-ID-][ct2]" class="form-control">
		    					<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"><?=$l['MONTHS'];?></option>
		    					<option value="years"><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

				<div class="form-group ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['MCT'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="variants[-ID-][mct1]" class="form-control ctf" placeholder="<?=$l['CTP'];?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="variants[-ID-][mct2]" class="form-control">
		    					<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"><?=$l['MONTHS'];?></option>
		    					<option value="years"><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
		    		<label><?=$l['NP'];?></label>
		    		<div class="row">
		    			<div class="col-md-8">
		    				<input type="text" name="variants[-ID-][np1]" class="form-control ctf" placeholder="<?=$l['CTP'];?>" />
		    			</div>

		    			<div class="col-md-4">
		    				<select name="variants[-ID-][np2]" class="form-control">
							<option value="days"><?=$l['DAYS'];?></option>
		    					<option value="months"><?=$l['MONTHS'];?></option>
		    					<option value="years"><?=$l['YEARS'];?></option>
		    				</select>
		    			</div>
		    		</div>
		    	</div>

		    	<div class="form-group" style="margin-bottom: 0;">
		    		<label><?=$l['AFFILIATE'];?></label>
		    		<div class="input-group">
		    			<input type="text" name="variants[-ID-][affiliate]" placeholder="<?=$l['AFFILIATEP'];?>" class="form-control" />
		    			<span class="input-group-addon">%</span>
		    		</div>
				</div>

				</div>
				</div>
				</div></div>

				<div class="checkbox ctg"<?=$info->prepaid ? ' style="display:none;"' : '';?>>
					<label>
						<input type="checkbox" name="prorata" value="1"<?=$info->prorata ? ' checked=""' : '';?>>
						<?=$l['PRORATA'];?>
					</label>
				</div>

				<?php if ($CFG['TAXES']) {?>
				<div class="form-group">
					<label><?=$l['TAX_HANDLING'];?></label>
					<select class="form-control" name="tax">
						<option value="gross"<?=$info->tax == "gross" ? ' selected=""' : '';?>><?=$l['TAX_GROSS'];?></option>
						<option value="dynamic"<?=$info->tax == "dynamic" ? ' selected=""' : '';?>><?=$l['TAX_DYNAMIC'];?></option>
						<option value="net"<?=$info->tax == "net" ? ' selected=""' : '';?>><?=$l['TAX_NET'];?></option>
					</select>
				</div>
				<?php }?>

				<label><?=$l['USAGEBILL'];?></label>
				<div class="table-responsive">
					<table class="table table-bordered table-striped" style="margin-bottom: 0;">
						<tr>
							<th width="40%"><?=$l['USAGEBILL1'];?></th>
							<th width="20%"><?=$l['USAGEBILL2'];?></th>
							<th width="20%"><?=$l['USAGEBILL3'];?></th>
							<th width="20%"><?=$l['USAGEBILL4'];?></th>
						</tr>

						<tr style="height: 47px;" id="usagebill_waiting">
							<td colspan="4" style="text-align: center;"><i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?></td>
						</tr>

						<tr style="height: 47px;" id="usagebill_nothing" style="display: none;">
							<td colspan="4" style="text-align: center;"><?=$l['USAGEBILLNONE'];?></td>
						</tr>

						<tbody id="usagebill_rows"></tbody>
					</table>
				</div>

				<p><small><?=$l['USAGEBILLH'];?></small></p>

				<script>
				function fetchUsageBillTable() {
					$("#usagebill_nothing").hide();
					$("#usagebill_waiting").show();
					$("#usagebill_rows").html("");

					$.post("", {
						"usage_bill_table": true,
						"module": $("[name=provisioning]").val(),
						"csrf_token": "<?=CSRF::raw();?>"
					}, function(r) {
						$("#usagebill_waiting").hide();
						$("#usagebill_rows").html(r);

						if (r == "") {
							$("#usagebill_nothing").show();
						} else {
							$(".usagebill_circle").unbind("click").click(function() {
								if ($(this).hasClass("fa-circle")) {
									$(this).removeClass("fa-circle").addClass("fa-circle-o");
									$(this).parent().parent().find(".usagebill_no").show();
									$(this).parent().parent().find(".usagebill_yes").hide();
								} else {
									$(this).removeClass("fa-circle-o").addClass("fa-circle");
									$(this).parent().parent().find(".usagebill_no").hide();
									$(this).parent().parent().find(".usagebill_yes").show();
								}
							});
						}
					});
				}

				$(document).ready(function() {
					fetchUsageBillTable();
				});
				</script>

				<div class="form-group">
					<label><?=$l['PROCHA'];?></label>
					<select name="product_change" class="form-control" multiple="">
						<?php
$sql = $db->query("SELECT ID, name FROM products WHERE type = 'HOSTING' AND ID != {$info->ID}");
    while ($row = $sql->fetch_object()) {?>
						<option value="<?=$row->ID;?>"<?=in_array($row->ID, explode(",", $info->product_change)) ? ' selected=""' : '';?>><?=unserialize($row->name)[$CFG['LANG']];?></option>
						<?php }?>
					</select>
					<p class="help-block"><?=$l['PROCHAH'];?></p>
				</div>
			</div>

			<script>
			$(".other_pricing").click(function() {
				if ($(this).hasClass("fa-circle")) {
					$(this).removeClass("fa-circle").addClass("fa-circle-o");
					$(this).parent().parent().find(".pcg_no").show();
					$(this).parent().parent().find(".pcg_yes").hide();
				} else {
					$(this).removeClass("fa-circle-o").addClass("fa-circle");
					$(this).parent().parent().find(".pcg_no").hide();
					$(this).parent().parent().find(".pcg_yes").show();
				}
			});
			</script>

			<script>
			$(document).ready(function(){
				var id = 1;

				$("#create_field").click(function(e){
					e.preventDefault();

					$("#no_fields").hide();
					var c = $("#new_field").clone().prop("id", null).addClass("custom_field").show();

					c.html(c.html().replace(/#ID#/g, id++));
					$("#custom_fields_form").append(c);

					$(".remove_field").unbind("click").click(function(e){
						e.preventDefault();

						if(!confirm("<?=$l['RDF'];?>")) return;

						$(this).parent().parent().parent().parent().parent().remove();

						if($(".custom_field").length == 0)
							$("#no_fields").show();
					});

					bindFieldType();
				});

				$(".remove_field").unbind("click").click(function(e){
					e.preventDefault();

					if(!confirm("<?=$l['RDF'];?>")) return;

					$(this).parent().parent().parent().parent().parent().remove();

					if($(".custom_field").length == 0)
						$("#no_fields").show();
				});
			});
			</script>

			<div role="tabpanel" class="tab-pane" id="fields">
				<form id="custom_fields_form">
				<a href="#" id="create_field" class="pull-right btn btn-default"><i class="fa fa-plus-circle"></i> <?=$l['CCF'];?></a><br /><br />

				<?php
$sql = $db->query("SELECT * FROM products_cf WHERE product = {$info->ID}");
    ?>
				<p id="no_fields"<?=$sql->num_rows > 0 ? ' style="display: none;"' : '';?>><?=$l['CFNT'];?><br /></p>
				<?php while ($row = $sql->fetch_object()) {$o = unserialize($row->options);

        if ($row->type == "select" || $row->type == "radio") {
            $ex = explode("|", $o["costs"]);
            $ex = array_map([$nfo, "format"], $ex);
            $o["costs"] = implode("|", $ex);
        }
        ?>
				<div class="panel panel-default custom_field">
					<div class="panel-body">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFN'];?></label> <a href="#" class="remove_field"><i class="fa fa-times"></i></a>
									<input type="text" name="fields[<?=$row->ID;?>][name]" placeholder="<?=$l['CFNP'];?>" value="<?=htmlentities($row->name);?>" class="form-control">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFNTYPE'];?></label>
									<select name="fields[<?=$row->ID;?>][type]" class="form-control fieldtype">
										<option value="number"<?=$row->type == "number" ? ' selected=""' : '';?>><?=$l['CFNTN'];?></option>
										<option value="select"<?=$row->type == "select" ? ' selected=""' : '';?>><?=$l['CFNTS'];?></option>
										<option value="radio"<?=$row->type == "radio" ? ' selected=""' : '';?>><?=$l['CFNTR'];?></option>
										<option value="check"<?=$row->type == "check" ? ' selected=""' : '';?>><?=$l['CFNTC'];?></option>
										<option value="text"<?=$row->type == "text" ? ' selected=""' : '';?>><?=$l['CFNTT'];?></option>
									</select>
								</div>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-radio"<?=$row->type != "radio" ? ' style="display: none;"' : '';?>>
							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFVALUES'];?></label>
									<input type="text" name="fields[<?=$row->ID;?>][values]" placeholder="<?=$l['CFVALUESP'];?>" value="<?=$o['values'];?>" class="form-control">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFCOSTS'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
        if (!empty($c->getPrefix())) {
            echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
        }

        ?>
										<input type="text" name="fields[<?=$row->ID;?>][costs]" placeholder="<?=$l['EG'];?> <?=$nfo->format(50);?>|<?=$nfo->format(70);?>|..." value="<?=$o['costs'];?>" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
            echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
        }

        ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
									<input type="checkbox" name="fields[<?=$row->ID;?>][onetime]" value="1"<?=$o['onetime'] ? ' checked=""' : '';?>>
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-check"<?=$row->type != "check" ? ' style="display: none;"' : '';?>>
							<div class="col-md-12">
								<div class="form-group">
									<label><?=$l['CFCOSTS'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
        if (!empty($c->getPrefix())) {
            echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
        }

        ?>
										<input type="text" name="fields[<?=$row->ID;?>][costs]" placeholder="<?=$l['EG'];?> <?=$nfo->format(50);?>" value="<?=$o['costs'];?>" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
            echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
        }

        ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
								<input type="checkbox" name="fields[<?=$row->ID;?>][onetime]" value="1"<?=$o['onetime'] ? ' checked=""' : '';?>>
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-select"<?=$row->type != "select" ? ' style="display: none;"' : '';?>>
							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFVALUES'];?></label>
									<input type="text" name="fields[<?=$row->ID;?>][values]" placeholder="<?=$l['CFVALUESP'];?>" value="<?=$o['values'];?>" class="form-control">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFCOSTS'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
        if (!empty($c->getPrefix())) {
            echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
        }

        ?>
										<input type="text" name="fields[<?=$row->ID;?>][costs]" placeholder="<?=$l['EG'];?> <?=$nfo->format(50);?>|<?=$nfo->format(70);?>|..." value="<?=$o['costs'];?>" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
            echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
        }

        ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
								<input type="checkbox" name="fields[<?=$row->ID;?>][onetime]" value="1"<?=$o['onetime'] ? ' checked=""' : '';?>>
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-number"<?=$row->type != "number" ? ' style="display: none;"' : '';?>>
							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFDEFAULT'];?></label>
									<input type="text" name="fields[<?=$row->ID;?>][default]" placeholder="<?=$l['EG'];?> 10" value="<?=intval($o['default']);?>" class="form-control">
								</div>
							</div>

							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFMIN'];?></label>
									<input type="text" name="fields[<?=$row->ID;?>][minimum]" placeholder="<?=$l['CFUNLIMITED'];?>" value="<?=intval($o['minimum']) <= 0 ? '' : intval($o['minimum']);?>" class="form-control">
								</div>
							</div>

							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFMAX'];?></label>
									<input type="text" name="fields[<?=$row->ID;?>][maximum]" placeholder="<?=$l['CFUNLIMITED'];?>" value="<?=intval($o['maximum']) < 0 ? '' : intval($o['maximum']);?>" class="form-control">
								</div>
							</div>

							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFPE'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
        if (!empty($c->getPrefix())) {
            echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
        }

        ?>
										<input type="text" name="fields[<?=$row->ID;?>][amount]" placeholder="<?=$l['EG'];?> <?=$nfo->placeholder();?>" value="<?=$nfo->format(doubleval($o['amount']) ?: 0);?>" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
            echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
        }

        ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
								<input type="checkbox" name="fields[<?=$row->ID;?>][onetime]" value="1"<?=$o['onetime'] ? ' checked=""' : '';?>>
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>
					</div>
				</div>
				<?php }?>

				<script>
				function bindFieldType() {
					$(".fieldtype").change(function(){
						var type = $(this).val();
						var cont = $(this).parent().parent().parent().parent().parent();

						cont.find(".fieldsettings").hide();
						cont.find("#fieldsettings-" + type).show();
					});
				}

				bindFieldType();
				</script>

				<div class="panel panel-default" id="new_field" style="display: none;">
					<div class="panel-body">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFN'];?></label> <a href="#" class="remove_field"><i class="fa fa-times"></i></a>
									<input type="text" name="fields[-#ID#][name]" placeholder="<?=$l['CFNP'];?>" class="form-control">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFNTYPE'];?></label>
									<select name="fields[-#ID#][type]" class="form-control fieldtype">
										<option value="number"><?=$l['CFNTN'];?></option>
										<option value="select"><?=$l['CFNTS'];?></option>
										<option value="radio"><?=$l['CFNTR'];?></option>
										<option value="check"><?=$l['CFNTC'];?></option>
										<option value="text"><?=$l['CFNTT'];?></option>
									</select>
								</div>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-radio" style="display: none;">
							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFVALUES'];?></label>
									<input type="text" name="fields[-#ID#][values]" placeholder="<?=$l['CFVALUESP'];?>" value="" class="form-control">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFCOSTS'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
    if (!empty($c->getPrefix())) {
        echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
    }

    ?>
										<input type="text" name="fields[-#ID#][costs]" placeholder="<?=$l['EG'];?> <?=$nfo->format(50);?>|<?=$nfo->format(70);?>|..." value="" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
        echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
    }

    ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
									<input type="checkbox" name="fields[-#ID#][onetime]" value="1">
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-check" style="display: none;">
							<div class="col-md-12">
								<div class="form-group">
									<label><?=$l['CFCOSTS'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
    if (!empty($c->getPrefix())) {
        echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
    }

    ?>
										<input type="text" name="fields[-#ID#][costs]" placeholder="<?=$l['EG'];?> <?=$nfo->format(50);?>" value="<?=$o['costs'];?>" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
        echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
    }

    ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
									<input type="checkbox" name="fields[-#ID#][onetime]" value="1">
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-select" style="display: none;">
							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFVALUES'];?></label>
									<input type="text" name="fields[-#ID#][values]" placeholder="<?=$l['CFVALUESP'];?>" value="" class="form-control">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label><?=$l['CFCOSTS'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
    if (!empty($c->getPrefix())) {
        echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
    }

    ?>
										<input type="text" name="fields[-#ID#][costs]" placeholder="<?=$l['EG'];?> <?=$nfo->format(50);?>|<?=$nfo->format(70);?>|..." value="" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
        echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
    }

    ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
									<input type="checkbox" name="fields[-#ID#][onetime]" value="1">
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>

						<div class="row fieldsettings" id="fieldsettings-number">
							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFDEFAULT'];?></label>
									<input type="text" name="fields[-#ID#][default]" placeholder="<?=$l['EG'];?> 10" value="" class="form-control">
								</div>
							</div>

							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFMIN'];?></label>
									<input type="text" name="fields[-#ID#][minimum]" placeholder="<?=$l['CFUNLIMITED'];?>" value="" class="form-control">
								</div>
							</div>

							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFMAX'];?></label>
									<input type="text" name="fields[-#ID#][maximum]" placeholder="<?=$l['CFUNLIMITED'];?>" value="" class="form-control">
								</div>
							</div>

							<div class="col-md-3">
								<div class="form-group">
									<label><?=$l['CFPE'];?></label>
									<div class="input-group">
										<?php
$c = new Currency($cur->getBaseCurrency());
    if (!empty($c->getPrefix())) {
        echo '<span class="input-group-addon">' . $c->getPrefix() . '</span>';
    }

    ?>
										<input type="text" name="fields[-#ID#][amount]" placeholder="<?=$l['EG'];?> <?=$nfo->placeholder();?>" value="" class="form-control">
										<?php
if (!empty($c->getSuffix())) {
        echo '<span class="input-group-addon">' . $c->getSuffix() . '</span>';
    }

    ?>
									</div>
								</div>
							</div>

							<div class="checkbox col-md-12" style="margin-top: 0; margin-bottom: 0;">
								<label>
									<input type="checkbox" name="fields[-#ID#][onetime]" value="1">
									<?=$l['ONETIME_ONLY'];?>
								</label>
							</div>
						</div>
					</div>
				</div>
				</form>
			</div>

		    <div role="tabpanel" class="tab-pane" id="provisioning">
		    	<select name="provisioning" class="form-control" id="module_select">
		    		<option value=""><?=$l['NOMODULE'];?></option>
		    		<?php foreach ($provisioning->get() as $m) {?>
		    		<option value="<?=$m->getShort();?>"<?=$info->module == $m->getShort() ? ' selected="selected"' : '';?>><?=$m->getName();?></option>
		    		<?php }?>
		    	</select>

		    	<script>
		    	$.get("?p=product_hosting&id=<?=$info->ID;?>&module=<?=$info->module;?>", function(r){
	    			$("#module_output").html(r);
				});
				$.get("?p=product_hosting&id=<?=$info->ID;?>&username_tab=" + $("#module_select").val(), function(r){
					if (r == "yes") {
						$("#username_btn").show();
					}
				});

		    	$("#module_select").change(function(){
		    		$("#module_output").html('<i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?><br /><br />');
		    		$("#email_variables").html('<i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?>');
					$("#ip_tab_btn").hide();
		    		$.get("?p=product_hosting&id=<?=$info->ID;?>&module=" + $("#module_select").val(), function(r){
		    			$("#module_output").html(r);
					});
					fetchUsageBillTable();
		    		$.get("?p=product_hosting&id=<?=$info->ID;?>&module_mail=" + $("#module_select").val(), function(r){
		    			$("#email_variables").html(r);
					});

					$("#username_btn").hide();
					$.get("?p=product_hosting&id=<?=$info->ID;?>&username_tab=" + $("#module_select").val(), function(r){
						if (r == "yes") {
							$("#username_btn").show();
						}
					});
		    	});
		    	</script>

		    	<br />
		    	<div id="module_output"><i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?><br /><br /></div>
			</div>

			<div role="tabpanel" class="tab-pane" id="username">
				<div class="form-group">
					<label><?=$l['UNFORMAT'];?></label>
					<input type="text" name="username_format" value="<?=htmlentities($info->username_format);?>" placeholder="c{contractId}" class="form-control">
					<p class="help-block">{contractId}, {incrementing}, {customerId}, {firstName}, {lastName}, {firstNameFirstLetter}, {lastNameFirstLetter}, {email}, {emailLocalPart}, {year}, {month}, {day}</p>
				</div>

				<div class="form-group">
					<label><?=$l['UNNEXT'];?></label>
					<input type="number" name="username_next" value="<?=intval($info->username_next);?>" placeholder="1" class="form-control" min="1" step="1">
				</div>

				<div class="form-group">
					<label><?=$l['UNSTEP'];?></label>
					<input type="number" name="username_step" value="<?=intval($info->username_step);?>" placeholder="1" class="form-control" min="1" step="1">
				</div>
			</div>

			<div role="tabpanel" class="tab-pane" id="ip">
				<div class="form-group">
					<label><?=$l['IPMANER'];?></label>

					<select name="ip_product" class="form-control">
						<option value="0"><?=$l['IPMANERNO'];?></option>
						<?php
$sql = $db->query("SELECT ID, name FROM products WHERE type = 'HOSTING' AND ip_product = 0 AND ID != {$info->ID}");
    $products = [];
    while ($row = $sql->fetch_object()) {
        $products[$row->ID] = unserialize($row->name) ? unserialize($row->name)[$CFG['LANG']] : $row->name;
    }
    asort($products);

    foreach ($products as $id => $name) {
        $selected = $info->ip_product == $id ? " selected=\"selected\"" : "";
        echo '<option value="' . $id . '"' . $selected . '>' . $name . '</option>';
    }
    ?>
					</select><br />

					<span id="ip_wait"><i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?></span>
					<div id="ip_man"></div>

					<script>
					function load_ip() {
						$("#ip_man").hide();
						$("#ip_wait").show();

						$.get("?p=product_hosting&id=<?=$info->ID;?>&ip_product=" + $("[name=ip_product]").val(), function (r) {
							$("#ip_wait").hide();
							$("#ip_man").html(r).show();
						});
					}

					load_ip();
					$("[name=ip_product]").change(load_ip);
					</script>
				</div>
			</div>

			<div role="tabpanel" class="tab-pane" id="links">
				<?php
$url = $CFG['PAGEURL'] . "product/" . $info->ID;
    $url2 = $CFG['PAGEURL'] . "cart?add_product=" . $info->ID;
    ?>

				<div class="form-group">
					<label><?=$l['LINK'];?></label><br />
					<span class="control-label"><a href="<?=$url;?>" target="_blank"><?=$url;?></a></span>
				</div>

				<div class="form-group">
					<label><?=$l['LINK2'];?></label><br />
					<span class="control-label"><a href="<?=$url2;?>" target="_blank"><?=$url2;?></a></span>
				</div>
			</div>

		    <div role="tabpanel" class="tab-pane" id="email">
		    	<div class="form-group">
		    		<label><?=$l['WELMA'];?></label>
		    		<select name="email" class="form-control">
		    			<option value="0"><?=$l['WELMANO'];?></option>
		    			<?php $sql = $db->query("SELECT ID, name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
		    			<option value="<?=$row->ID;?>"<?php if ($info->welcome_mail == $row->ID) {
        echo ' selected="selected"';
    }
        ?>><?=$row->name;?></option>
		    			<?php }?>
		    		</select>
		    		<p class="help-block"><?=$l['WELMAH'];?></p>
		    	</div>

		    	<div class="form-group">
		    		<label><?=$l['WELMAV'];?></label>
		    		<div id="email_variables"><i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?></div>
		    		<p class="help-block"><?=$l['WELMAVH'];?></p>
		    	</div>

		    	<script>
		    	$.get("?p=product_hosting&id=<?=$info->ID;?>&module_mail=<?=$info->module;?>", function(r){
	    			$("#email_variables").html(r);
	    		});
		    	</script>
		    </div>
		  </div>

		</div>
	</div>
</div>
<br /><a href="#" id="save" class="btn btn-primary btn-block" style="margin-top: -20px;" disabled=""><?=$l['SAVE'];?></a>

<script>
var doing_save = false;
$("#save").click(function(e){
	e.preventDefault();

	if(doing_save) return;
	doing_save = !doing_save;
	$("#suc").slideUp();
	$("#err").slideUp();
	$(this).html("<i class='fa fa-spin fa-spinner'></i> <?=$l['BEINGSAVED'];?>");

	var status = 0;
	$("[name=status]").each(function(){
		if($(this).is(":checked")) status = $(this).val();
	});

	var prov = {};
	$(".prov_settings").each(function(){
		var s = $(this).data("setting");
		if(s.trim().length == 0) return;
		prov[s.trim()] = $(this).val();
	});
	$(".prov_check").each(function(){
		var s = $(this).data("setting");
		if($(this).is(":checked")) prov[s.trim()] = "yes";
		else prov[s.trim()] = "no";
	});

	var name = {};
	$(".pname").each(function(){
		var s = $(this).data("lang");
		if(s.trim().length == 0) return;
		name[s.trim()] = $(this).val();
	});

	var desc = {};
	$(".pdesc").each(function(){
		var s = $(this).data("lang");
		if(s.trim().length == 0) return;
		desc[s.trim()] = $(this).val();
	});

	var price_cgroups = {};
	$(".pcg_row").each(function() {
		if ($(this).find(".pcg_yes").css("display") != "none") {
			price_cgroups[$(this).find(".pcg_price").data("id")] = [$(this).find(".pcg_price").val(), $(this).find(".pcg_setup").val()];
		}
	});

	var usage_billing = {};
	$(".usagebill_row").each(function() {
		if ($(this).find(".usagebill_yes").css("display") != "none") {
			usage_billing[$(this).find(".usagebill_free").data("key")] = [$(this).find(".usagebill_free").val(), $(this).find(".usagebill_price").val(), $(this).find(".usagebill_units").val()];
		}
	});


	if (!$("#fields").is(":visible")) {
		$("#fields").addClass("active");
		var cfVals = $("#custom_fields_form").find("input[type='hidden'], :input:not(:hidden)").serialize();
		$("#fields").removeClass("active");
	} else {
		var cfVals = $("#custom_fields_form").find("input[type='hidden'], :input:not(:hidden)").serialize();
	}

	$.post("?p=product_hosting&id=<?=$_GET['id'];?>&save=1", {
		status: status,
		prov: prov,
		name: name,
		desc: desc,
		cat: $("[name=category]").val(),
		incldomains: $("[name=incldomains]").val(),
		incltlds: $("[name=incltlds]").val(),
		dns_template: $("[name=dns_template]").val(),
		price: $("[name=price]").val(),
		billing: $("[name=billing]").val(),
		currency: $("[name=currency]").val(),
		setup: $("[name=setup]").val(),
		affiliate: $("[name=affiliate]").val(),
		prepaid: $("[name=prepaid]").is(":checked") ? "1" : "0",
		prorata: $("[name=prorata]").is(":checked") ? "1" : "0",
		prepaid_days: $("#prepaidDaysForm").serialize(),
		ct1: $("[name=ct1]").val(),
		ct2: $("[name=ct2]").val(),
		mct1: $("[name=mct1]").val(),
		mct2: $("[name=mct2]").val(),
		np1: $("[name=np1]").val(),
		np2: $("[name=np2]").val(),
		autodelete: $("[name=autodelete]").val(),
		min_age: $("[name=min_age]").val(),
		email: $("[name=email]").val(),
		available: $("[name=available]").val(),
		maxpc: $("[name=maxpc]").val(),
		new_cgroup: $("[name=new_cgroup]").val(),
		module: $("#module_select").val(),
		customfields: cfVals,
		preorder: $("[name=preorder]").is(":checked") ? "1" : "0",
		desc_on_invoice: $("[name=desc_on_invoice]").is(":checked") ? "1" : "0",
		domain_choose: $("[name=domain_choose]").is(":checked") ? "1" : "0",
		only_verified: $("[name=only_verified]").is(":checked") ? "1" : "0",
		public: $("[name=public]").is(":checked") ? "1" : "0",
		hide: $("[name=hide]").is(":checked") ? "1" : "0",
		customer_groups: $("#cg").serialize(),
		ip_product: $("[name=ip_product]").val(),
		username_format: $("[name=username_format]").val(),
		username_step: $("[name=username_step]").val(),
		username_next: $("[name=username_next]").val(),
		product_change: $("[name=product_change]").val(),
		tax: $("[name=tax]").val(),
		"usage_billing": usage_billing,
		"price_cgroups": price_cgroups,
		"variants": $("[name*='variants[']").serialize(),
		csrf_token: "<?=CSRF::raw();?>"
	}, function(r){
		doing_save = false;
		$("#save").html("<?=$l['SAVE'];?>");

		if(r == "ok") $("#suc").slideDown();
		else $("#err").html(r).slideDown();
	});
});
</script>
<?php }?>