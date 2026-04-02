<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['HOSTING'];

title($l['TITLE']);
menu("customers");

$sql = $db->query("SELECT * FROM client_products WHERE type = 'h' AND ID = '" . $db->real_escape_string($_GET['id']) . "'");
if (!$ari->check(13) || $sql->num_rows != 1) {require __DIR__ . "/error.php";if (!$ari->check(13)) {
    alog("general", "insufficient_page_rights", "hosting");
}
} else {

    $info = $sql->fetch_object();
    $m = $provisioning->get()[$info->module];

    $pSql = $db->query("SELECT * FROM products WHERE ID = {$info->product}");
    $pInfo = $pSql->fetch_object();

    if (!empty($info->name)) {
        $pInfo->name = $info->name;
    }

    $f = [];
    if (is_object($m)) {
        $f = array_merge($m->OwnFunctions($info->ID), $m->AdminFunctions($info->ID));
    }

    if (isset($_GET['module'])) {
        die($m->Config($info->ID, false));
    }

    if (!empty($_POST['bill_now'])) {
        $uI = User::getInstance($info->user, "ID");
        if (!$uI) {
            die($l['ERR1']);
        }

        $billarr = array(
            "monthly" => "1 month",
            "quarterly" => "3 months",
            "semiannually" => "6 months",
            "annually" => "1 year",
            "biennially" => "2 year",
            "trinnially" => "3 year",
        );

        $invDesc = "<b>" . unserialize($pInfo->name)[$uI->getLanguage()] . "</b>";
        if (!empty($info->name)) {
            $invDesc = "<b>" . $info->name . "</b>";
        }

        if (!empty($i->description)) {
            $invDesc .= " (" . htmlentities($info->description) . ")";
        }

        $from = strtotime($info->last_billed);
        $to = strtotime("+" . $billarr[$info->billing], strtotime($info->last_billed));

        $amount = $info->price;
        if ($info->cancellation_date != "0000-00-00" && date("Y-m-d", $to) > $info->cancellation_date) {
            $origTo = $to;
            $to = strtotime($info->cancellation_date);

            $periodBefore = floor(($origTo - $from) / 86400);
            $periodNow = floor(($to - $from) / 86400);
            $factor = $periodNow / $periodBefore;
            $amount *= $factor;

            if ($periodNow <= 0) {
                die($dfo->format(strtotime($info->cancellation_date), false, false));
            }
        }

        if ($pInfo->desc_on_invoice) {
            $invDesc .= "<br />" . (@unserialize($pInfo->description) ? unserialize($pInfo->description)[$uI->getLanguage()] : $pInfo->description);
        }

        $myCf = unserialize($info->cf);

        $invCf = [];
        foreach ($myCf as $fieldId => $fieldVal) {
            $cfSql = $db->query("SELECT name FROM products_cf WHERE ID = " . intval($fieldId));
            if ($cfSql->num_rows) {
                $invCf[$cfSql->fetch_object()->name] = $fieldVal;
            }
        }

        if (count($invCf)) {
            $invDesc .= "<br />";
            foreach ($invCf as $fieldName => $fieldVal) {
                $invDesc .= "<br />" . $fieldName . ": " . $fieldVal;
            }
        }

        $invDesc .= "<br /><br />" . $dfo->format($from, false, false, "", $uI->getDateFormat()) . " - " . $dfo->format($to, false, false, "", $uI->getDateFormat());

        $item = new InvoiceItem;
        $item->setDescription($invDesc);
        $item->setAmount($amount);
        $item->setRelid($info->ID);
        $item->save();

        $invoice = new Invoice;
        $invoice->setDate(date("Y-m-d"));
        $invoice->setClient($uI->get()['ID']);
        $invoice->setDueDate();
        $invoice->addItem($item);

        $invoice->save();
        $invoice->applyCredit(false);
        $invoice->save();

        $db->query("UPDATE client_products SET last_billed = '" . date("Y-m-d", strtotime("+1 day", $to)) . "' WHERE ID = {$info->ID} LIMIT 1");

        die($dfo->format(strtotime("+1 day", $to), false, false));
    }

    if (isset($_GET['command'])) {
        if ($_GET['command'] == "SendWelcomeEmail" && !empty($pInfo->welcome_mail)) {
            $uI = User::getInstance($info->user, "ID");
            if (!$uI) {
                die($l['ERR1']);
            }

            $pName = strval($pInfo->name);
            $pArr = @unserialize($pName);
            if (is_array($pArr) && count($pArr)) {
                if (array_key_exists($uI->getLanguage(), $pArr)) {
                    $pName = strval($pArr[$uI->getLanguage()]);
                } else {
                    $pName = strval(array_shift($pArr));
                }
            }

            $mt = new MailTemplate($pInfo->welcome_mail);
            $title = $mt->getTitle($uI->getLanguage());
            $mail = $mt->getMail($uI->getLanguage(), $uI->get()['name']);

            $metVars = [
                "product" => $pName,
                "inclusive_domains" => [],
                "addon_domains" => [],
                "cancellation_date" => $info->cancellation_date == "0000-00-00" ? "" : $dfo->format($info->cancellation_date, 0, 0, "-", $uI->getDateFormat()),
            ];

            if (is_object($m)) {
                $vars = $m->EmailVariables($info->ID);
                foreach ($vars as $k => $v) {
                    $metVars[$k] = $v;
                }

                $m->LoadOptions($info->ID);
                if ($sid = $m->getOption("_mgmt_server")) {
                    $srvSql = $db->query("SELECT * FROM monitoring_server WHERE ID = " . intval($sid));
                    if ($srvSql->num_rows) {
                        $metVars["server"] = $srvSql->fetch_assoc();
                    }
                }
            }

            $domSql = $db->query("SELECT domain FROM domains WHERE inclusive_id = " . intval($info->ID));
            while ($row = $domSql->fetch_object()) {
                $metVars["inclusive_domains"][] = $row->domain;
            }

            $domSql = $db->query("SELECT domain FROM domains WHERE addon_id = " . intval($info->ID));
            while ($row = $domSql->fetch_object()) {
                $metVars["addon_domains"][] = $row->domain;
            }

            $maq->enqueue($metVars, $mt, $uI->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $info->user, false, 0, 0, $mt->getAttachments($uI->getLanguage()));

            alog("hosting", "welcome_email", $info->ID);

            die("ok");
        } else {
            if (!method_exists($m, $_GET['command'])) {
                die($l['ERR2']);
            }

            $c = $_GET['command'];
            $r = $m->$c($info->ID);

            if ($c == "Create") {
                $addons->runHook("ProvCreate", [
                    "contract" => $info,
                    "result" => $r,
                    "module" => $m,
                ]);
            }

            alog("hosting", "command", $c, $info->ID);
            if ($r[0] === true) {
                if ($c == "Create") {
                    $db->query("UPDATE client_products SET module_data = '" . $db->real_escape_string(encrypt(serialize($r[1]))) . "', error = '' WHERE ID = {$info->ID}");
                    $db->query("UPDATE client_products SET paid_until = " . time() . " WHERE ID = {$info->ID} AND paid_until = 0");
                }

                if (isset($r[2])) {
                    $c = $r[2];
                    $r = $m->$c($info->ID);
                    if ($r[0]) {
                        die("ok");
                    } else {
                        die($r[1] ?: $l['ERR3']);
                    }

                }
                die("ok");
            } else if ($r[0] == "wait") {
                if ($c == "Create") {
                    $db->query("UPDATE client_products SET module_data = '" . $db->real_escape_string(encrypt(serialize($r[1]))) . "' WHERE ID = {$info->ID}");
                }

                die($l['ERR4']);
            }
            die($r[1] ?: $l['ERR5']);
        }
    }

    if (!empty($_POST['new_module_key'])) {
        $d = unserialize(decrypt($info->module_data));
        if (!is_array($d)) {
            $d = [];
        }

        if (!array_key_exists($_POST['new_module_key'], $d)) {
            $d[$_POST['new_module_key']] = "";
            $db->query("UPDATE client_products SET module_data = '" . $db->real_escape_string(encrypt(serialize($d))) . "' WHERE ID = {$info->ID} LIMIT 1");
            header('Refresh: 0');
            exit;
        }
    }

    if (isset($_GET['save_module_data'])) {
        $data = $_POST['data'];
        if (is_array($_POST['sdata'])) {
            foreach ($_POST['sdata'] as $k => $v) {
                $data[$k] = serialize($v);
            }
        }

        alog("hosting", "save_module_data", $info->ID);
        $db->query("UPDATE client_products SET module_data = '" . $db->real_escape_string(encrypt(serialize($data))) . "' WHERE ID = {$info->ID} LIMIT 1");
        die("ok");
    }

    if (isset($_GET['save_cf_data'])) {
        $data = $_POST['data'];
        alog("hosting", "save_cf_data", $info->ID);
        $db->query("UPDATE client_products SET cf = '" . $db->real_escape_string((serialize($data))) . "' WHERE ID = {$info->ID} LIMIT 1");
        die("ok");
    }

    if (isset($_GET['save_module_settings'])) {
        $data = $_POST['data'];
        alog("hosting", "save_module_settings", $info->ID);
        $db->query("UPDATE client_products SET module_settings = '" . $db->real_escape_string(encrypt(serialize($data))) . "', server_id = -1 WHERE ID = {$info->ID} LIMIT 1");
        die("ok");
    }

    if (isset($_GET['add_domain'])) {
        $r = $m->AssignDomain($info->ID, $_GET['add_domain']);
        alog("hosting", "add_domain", $info->ID, $_GET['add_domain']);
        if (!$r[0]) {
            die($r[1]);
        }

        die("ok");
    }

    if (isset($_GET['cancel_dates'])) {
        ob_start();
        if (is_array($cds = $provisioning->getCancellationDates($info->ID))) {
            foreach ($cds as $i => $d) {?>
	<a href="#" class="cancel_date"><?=$dfo->format($d, false);?></a><?=$i < 4 ? ", " : "";?>
	<?php }}
        $r = ob_get_contents();
        ob_end_clean();
        die($r);
    }

    if (isset($_GET['new_ip'])) {
        if (ProvisioningHandler::getDedicatedIP($info->ID, $info->product)) {
            die("ok");
        }
        exit;
    }

    if (isset($_GET['delete_ip'])) {
        ProvisioningHandler::releaseDedicatedIP($info->ID);
        exit;
    }

    if (isset($_GET['save'])) {
        foreach ($_POST as $k => $v) {
            $v2 = "p_" . strtolower($k);
            $$v2 = $db->real_escape_string($v);
        }

        if (empty($p_date) || strtotime($p_date) === false) {
            die($l['ERR6']);
        }

        if (date("Y-m-d", strtotime($p_date)) == substr(date("Y-m-d", $info->date), 0, 10)) {
            $p_date = $info->date;
        } else {
            $p_date = strtotime($p_date);
        }

        $p_price = $nfo->phpize($p_price);
        if ((!is_double($p_price) && !is_numeric($p_price)) || $p_price < 0) {
            die($l['ERR7']);
        }

        if (!in_array($p_billing, array("onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "trinnially", "minutely", "hourly"))) {
            die($l['ERR8']);
        }

        if (!in_array($p_active, array("-2", "-1", "0", "1"))) {
            die($l['ERR9']);
        }

        if (empty($p_cancellation_date)) {
            $p_cancellation_date = "0000-00-00";
        } else if (strtotime($p_cancellation_date) === false) {
            die($l['ERR10']);
        } else {
            $p_cancellation_date = date("Y-m-d", strtotime($p_cancellation_date));
        }

        if (empty($p_last_billed)) {
            $p_last_billed = "0000-00-00";
        } else if (strtotime($p_last_billed) === false) {
            die($l['ERR11']);
        } else {
            $p_last_billed = date("Y-m-d", strtotime($p_last_billed));
        }

        if (empty($p_ct1)) {
            $ct = "";
        } else {
            if (!is_numeric($p_ct1) || $p_ct1 < 0 || !in_array($p_ct2, array("days", "months", "years"))) {
                die($l['ERR12']);
            }

            $ct = $p_ct1 . " " . $p_ct2;
        }

        if (empty($p_mct1)) {
            $mct = "";
        } else {
            if (!is_numeric($p_mct1) || $p_mct1 < 0 || !in_array($p_mct2, array("days", "months", "years"))) {
                die($lang['PRODUCT_HOSTING']['ERRMCT']);
            }

            $mct = $p_mct1 . " " . $p_mct2;
        }

        if (empty($p_np1)) {
            $np = "";
        } else {
            if (!is_numeric($p_np1) || $p_np1 < 0 || !in_array($p_np2, array("days", "months", "years"))) {
                die($l['ERR13']);
            }

            $np = $p_np1 . " " . $p_np2;
        }

        $sql = $db->prepare("UPDATE client_products SET name = ?, date = ?, active = ?, description = ?, price = ?, billing = ?, last_billed = ?, ct = ?, np = ?, cancellation_date = ?, error = ?, mct = ? WHERE ID = ?");
        $sql->bind_param("ssisdsssssssi", $p_name, $p_date, $p_active, $p_description, $p_price, $p_billing, $p_last_billed, $ct, $np, $p_cancellation_date, $p_error, $mct, $info->ID);
        $sql->execute();

        if (isset($_POST['cancellation_allowed'])) {
            $db->query("UPDATE client_products SET cancellation_allowed = " . intval($_POST['cancellation_allowed']) . " WHERE ID = " . intval($info->ID));
        }

        $cd = $p_cancellation_date == "0000-00-00" ? "0000-00-00" : date("Y-m-d", strtotime($p_cancellation_date));
        if ($cd != $info->cancellation_date && method_exists($m, "Cancellation")) {
            $m->Cancellation($info->ID, $cd);
        }

        if ($info->cancellation_date == "0000-00-00" && $p_cancellation_date != "0000-00-00") {
            $addons->runHook("CancellationRequest", [
                "id" => $info->ID,
                "source" => "admin",
            ]);
        } else if ($info->cancellation_date != "0000-00-00" && $p_cancellation_date == "0000-00-00") {
            $addons->runHook("CancellationRevoke", [
                "id" => $info->ID,
                "source" => "admin",
            ]);
        }

        alog("hosting", "save", $info->ID);

        die("ok");
    }

    $output = "";
    if (is_object($m)) {
        $output = $m->Output($info->ID, isset($_GET['task']) ? $_GET['task'] : "");
    }

    $path = __DIR__ . "/../../files/contracts/" . $info->ID;

    if (!empty($_GET['delete_file'])) {
        @unlink($path . "/" . basename($_GET['delete_file']));
        header("Location: ?p=hosting&id=" . $info->ID);
        exit;
    }

    if (!empty($_GET['download_file']) && file_exists($path . "/" . basename($_GET['download_file']))) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($_GET['download_file']) . "\"");
        readfile($path . "/" . basename($_GET['download_file']));
        exit;
    }

    if (!empty($_FILES['upload_files'])) {
        if (!file_exists($path)) {
            mkdir($path);
        }

        foreach ($_FILES["upload_files"]["name"] as $k => $name) {
            $tmp_name = $_FILES["upload_files"]["tmp_name"][$k];
            move_uploaded_file($tmp_name, $path . "/" . basename($name));
        }

        header("Location: ?p=hosting&id=" . $info->ID);
        exit;
    }
    ?>

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE2'];?> <small><?=(unserialize($pInfo->name) ? unserialize($pInfo->name)[$CFG['LANG']] : $pInfo->name);?><?php if (!empty($info->description)) {
        echo " - {$info->description}";
    }
    ?></small></h1>
	</div>
</div>

<div class="row">
	<div class="col-md-4">
		<div class="panel panel-primary">
		  <div class="panel-heading"><?=$l['CONDET'];?></div>
		  <div class="panel-body">
		  <div class="alert alert-success" id="pd_suc" style="display: none;"><?=$l['SUC1'];?></div>
		  <div class="alert alert-danger" id="pd_err" style="display: none;"></div>
		  	<div class="form-group">
		  		<label><?=$l['CDATE'];?></label>
		  		<input type="text" name="date" class="form-control datepicker" value="<?=$dfo->format($info->date, false);?>" placeholder="<?=$dfo->placeholder(false);?>" />
		  	</div>

			<div class="form-group">
		  		<label><?=$l['CNAME'];?></label>
		  		<input type="text" name="name" class="form-control" value="<?=htmlentities($info->name);?>" placeholder="<?=unserialize($pInfo->name)[$CFG['LANG']];?>" />
		  	</div>

		  	<div class="form-group">
		  		<label><?=$l['CDESC'];?></label>
		  		<input type="text" name="sdescription" class="form-control" maxlength="25" value="<?=htmlentities($info->description);?>" placeholder="<?=$l['NODESC'];?>" />
		  	</div>

			<?php
$uI = User::getInstance($info->user, "ID");
    if ($uI) {
        ?>
    		<div class="form-group">
		  		<label><?=$l['CLIENT'];?></label><br />
				<a href="?p=customers&edit=<?=$info->user;?>"><?=$uI->getfName();?></a>
		  	</div>
	<?php }?>
		  	<div class="form-group">
		  		<label><?=$l['PRICE'];?></label>
		  		<div class="row">
		  		<div class="col-xs-8">
				<?php $len = in_array($info->billing, ["hourly", "minutely"]) ? max(2, strlen(substr(strrchr(rtrim($info->price, "0"), "."), 1))) : 2;?>
		  		<input type="text" name="price" class="form-control" value="<?=$nfo->format($info->price, $len);?>" placeholder="<?=$nfo->placeholder();?>" />
		  		</div>
		  		<div class="col-xs-4">
		  		<select name="billing" class="form-control">
		  			<option value="onetime"><?=$l['B1'];?></option>
		  			<option value="monthly"<?=$info->billing == "monthly" ? ' selected="selected"' : "";?>><?=$l['B2'];?></option>
		  			<option value="quarterly"<?=$info->billing == "quarterly" ? ' selected="selected"' : "";?>><?=$l['B3'];?></option>
		  			<option value="semiannually"<?=$info->billing == "semiannually" ? ' selected="selected"' : "";?>><?=$l['B4'];?></option>
		  			<option value="annually"<?=$info->billing == "annually" ? ' selected="selected"' : "";?>><?=$l['B5'];?></option>
					<option value="biennially"<?=$info->billing == "biennially" ? ' selected="selected"' : "";?>><?=$l['B6'];?></option>
					<option value="trinnially"<?=$info->billing == "trinnially" ? ' selected="selected"' : "";?>><?=$l['B7'];?></option>
					<option value="minutely"<?=$info->billing == "minutely" ? ' selected="selected"' : "";?>><?=$l['B8'];?></option>
					<option value="hourly"<?=$info->billing == "hourly" ? ' selected="selected"' : "";?>><?=$l['B9'];?></option>
		  		</select>
		  		</div>
		  		</div>
		  	</div>

		  	<div class="form-group">
		  		<label><?=$l['STATUS'];?></label>
		  		<select name="active" class="form-control">
		  			<option value="1"><?=$l['S1'];?></option>
		  			<option value="0"<?=$info->active == "0" ? ' selected="selected"' : "";?>><?=$l['S2'];?></option>
		  			<option value="-1"<?=$info->active == "-1" ? ' selected="selected"' : "";?>><?=$l['S3'];?></option>
		  			<option value="-2"<?=$info->active == "-2" ? ' selected="selected"' : "";?>><?=$l['S4'];?></option>
		  		</select>
		  	</div>

		  	<?php if (!empty($info->billing) && !in_array($info->billing, ["onetime", "minutely", "hourly"]) && !$info->prepaid) {?>
		    <div class="form-group">
		    	<label><?=$l['CANDTS'];?></label><br />
		    	<div id="cancel_dates"><i class="fa fa-spin fa-spinner"></i> <?=$l['PW'];?></div>
		    </div>

			<div class="form-group">
		    	<label><?=$l['CCC'];?></label><br />
		    	<div class="checkbox" style="margin-top: 0;">
					<label>
						<input type="checkbox" name="cancellation_allowed" value="1"<?=$info->cancellation_allowed ? ' checked=""' : '';?>>
						<?=$l['YES'];?>
					</label>
				</div>
		    </div>
		    <?php }?>

			<?php
$sql = $db->query("SELECT ID, domain, user FROM domains WHERE inclusive_id = {$info->ID}");
    if ($sql->num_rows > 0) {?>
		    <div class="form-group">
		    	<label><?=$l['INCLDOM'];?></label><br />
		    	<?php $i = 1;while ($row = $sql->fetch_object()) {?>
				<a href="?p=domain&d=<?=$row->domain;?>&u=<?=$row->user;?>"><?=$row->domain;?></a><?php if ($i < $sql->num_rows) {
        echo ", ";
    }

        $i++;}?>
		    </div>
		    <?php }?>

			<?php
$sql = $db->query("SELECT ID, domain, user FROM domains WHERE addon_id = {$info->ID}");
    if ($sql->num_rows > 0) {?>
		    <div class="form-group">
		    	<label><?=$l['ADDONDOM'];?></label><br />
		    	<?php $i = 1;while ($row = $sql->fetch_object()) {?>
				<a href="?p=domain&d=<?=$row->domain;?>&u=<?=$row->user;?>"><?=$row->domain;?></a><?php if ($i < $sql->num_rows) {
        echo ", ";
    }

        $i++;}?>
		    </div>
		    <?php }?>

			<div class="form-group">
		    	<label><?php if (!empty($info->billing) && $info->billing != "onetime" && !$info->prepaid) {?><?=$l['CANCELDATE'];?><?php } else {?><?=$l['DELDATE'];?><?php }?></label>
		    	<input type="text" name="cancellation_date" class="form-control datepicker" value="<?=$info->cancellation_date == "0000-00-00" ? "" : $dfo->format($info->cancellation_date, false);?>" placeholder="<?=$l['NOCDATE'];?>">
		    </div>

		    <?php if (!empty($info->billing) && !in_array($info->billing, ["onetime", "minutely", "hourly"]) && !$info->prepaid) {?>
		    <div class="form-group">
		  		<label><?=$l['NEXTDUE'];?></label> <a href="#" id="invoice_now"><i class="fa fa-money"></i></a>
		  		<input type="text" name="last_billed" class="form-control datepicker" value="<?=$info->last_billed == "0000-00-00" ? "" : $dfo->format($info->last_billed, false);?>" placeholder="<?=$l['NOTDUE'];?>" />
		  	</div>

			<script>
			var doinginvnow = 0;

			$("#invoice_now").click(function(e) {
				e.preventDefault();

				if (doinginvnow) {
					return;
				}

				var i = $(this).find("i");
				i.removeClass("fa-money").addClass("fa-spinner fa-spin");

				$("[name=last_billed]").prop("disabled", true);

				$.post("", {
					"bill_now": true,
					"csrf_token": "<?=CSRF::raw();?>",
				}, function(r) {
					$("[name=last_billed]").prop("disabled", false).val(r);
					i.removeClass("fa-spinner fa-spin").addClass("fa-money");
				});
			});
			</script>

		  	<div class="form-group">
		  		<label><?=$l['CT'];?></label>
		  		<div class="row">
		  		<div class="col-xs-8">
		  		<input type="text" name="ct1" class="form-control" value="<?=explode(" ", $info->ct)[0];?>" placeholder="<?=$l['NOCT'];?>" />
		  		</div>
		  		<div class="col-xs-4">
		  		<select name="ct2" class="form-control">
		  			<option value="days"><?=$l['DAYOS'];?></option>
		  			<option value="months"<?=explode(" ", $info->ct)[1] == "months" ? ' selected="selected"' : "";?>><?=$l['MONTHOS'];?></option>
		  			<option value="years"<?=explode(" ", $info->ct)[1] == "years" ? ' selected="selected"' : "";?>><?=$l['YEAROS'];?></option>
		  		</select>
		  		</div>
		  		</div>
		  	</div>

			  <div class="form-group">
		  		<label><?=$lang['PRODUCT_HOSTING']['MCT'];?></label>
		  		<div class="row">
		  		<div class="col-xs-8">
		  		<input type="text" name="mct1" class="form-control" value="<?=explode(" ", $info->mct)[0];?>" placeholder="<?=$l['NOCT'];?>" />
		  		</div>
		  		<div class="col-xs-4">
		  		<select name="mct2" class="form-control">
		  			<option value="days"><?=$l['DAYOS'];?></option>
		  			<option value="months"<?=explode(" ", $info->mct)[1] == "months" ? ' selected="selected"' : "";?>><?=$l['MONTHOS'];?></option>
		  			<option value="years"<?=explode(" ", $info->mct)[1] == "years" ? ' selected="selected"' : "";?>><?=$l['YEAROS'];?></option>
		  		</select>
		  		</div>
		  		</div>
		  	</div>

		  	<div class="form-group">
		  		<label><?=$l['NP'];?></label>
		  		<div class="row">
		  		<div class="col-xs-8">
		  		<input type="text" name="np1" class="form-control" value="<?=explode(" ", $info->np)[0];?>" placeholder="<?=$l['NOCT'];?>" />
		  		</div>
		  		<div class="col-xs-4">
		  		<select name="np2" class="form-control">
		  			<option value="days"><?=$l['DAYOS'];?></option>
		  			<option value="months"<?=explode(" ", $info->np)[1] == "months" ? ' selected="selected"' : "";?>><?=$l['MONTHOS'];?></option>
		  			<option value="years"<?=explode(" ", $info->np)[1] == "years" ? ' selected="selected"' : "";?>><?=$l['YEAROS'];?></option>
		  		</select>
		  		</div>
		  		</div>
		  	</div>
		  	<?php } else {if (!$info->prepaid) {?>
		  	<input type="hidden" name="last_billed" value="<?=$info->last_billed == "0000-00-00" ? "" : $dfo->format($info->last_billed, false);?>" />
			<?php } else {?>
			<div class="form-group">
		  		<label><?=$l['PAIDUNTIL'];?></label>
		  		<input type="text" name="last_billed" class="form-control datepicker" value="<?=$info->last_billed == "0000-00-00" ? "" : $dfo->format($info->last_billed, false);?>" placeholder="<?=$l['NOTDUE'];?>" />
		  	</div>
			<?php }?>
		  	<input type="hidden" name="ct1" value="<?=explode(" ", $info->ct)[0];?>" />
		  	<input type="hidden" name="ct2" value="<?=explode(" ", $info->ct)[1];?>" />
		  	<input type="hidden" name="np1" value="<?=explode(" ", $info->np)[0];?>" />
		  	<input type="hidden" name="np2" value="<?=explode(" ", $info->np)[1];?>" />
			<?php }?>

			<div class="form-group">
		  		<label><?=$l['DEDIP'];?></label><br />
				<?php
$ip = ProvisioningHandler::getDedicatedIP($info->ID, $info->product, false);
    if ($ip) {
        ?>
					<?=htmlentities($ip);?>
					<span class="pull-right"><a href="#" id="delete_ip"><i class="fa fa-times"></i></a></span>
					<script>
					$("#delete_ip").click(function(e) {
						e.preventDefault();

						var parent = $(this).parent();
						$(this).find("i").removeClass("fa-times").addClass("fa-spinner fa-pulse");
						parent.html($(this).html());

						$.get("?p=hosting&id=<?=$info->ID;?>&delete_ip=1", function(r) {
							window.location.reload();
						});
					});
					</script>
					<?php
} else {
        ?>
					<i><?=$l['NODEDIP'];?></i>
					<span class="pull-right"><a href="#" id="create_ip"><i class="fa fa-plus"></i></a></span>
					<script>
					$("#create_ip").click(function(e) {
						e.preventDefault();

						var parent = $(this).parent();
						$(this).find("i").removeClass("fa-plus").addClass("fa-spinner fa-pulse");
						parent.html($(this).html());

						$.get("?p=hosting&id=<?=$info->ID;?>&new_ip=1", function(r) {
							if (r == "ok") {
								window.location.reload();
							} else {
								parent.remove();
								alert("<?=$l['NODEDIPAVA'];?>");
							}
						});
					});
					</script>
					<?php
}
    ?>
		  	</div>

		  	<div class="form-group">
		  		<label><?=$l['ERROR'];?></label>
		  		<input type="text" name="error" class="form-control" value="<?=$info->error;?>" placeholder="<?=$l['NOERROR'];?>" />
		  	</div>

		  	<a href="#" id="save_product" class="btn btn-primary btn-block"><?=$l['SAVEDET'];?></a>
		  </div>
		</div>

		<script>
		var wait_cancel = $("#cancel_dates").html();

		function getCancelDates(){
			$("#cancel_dates").html(wait_cancel);
			$(".cancel_date").unbind("click");

			$.get("?p=hosting&id=<?=$info->ID;?>&cancel_dates=1", function(r){
				$("#cancel_dates").html(r);
				$(".cancel_date").click(function(e){
					e.preventDefault();
					$("[name=cancellation_date]").val($(this).html());
				});
			});
		}
		getCancelDates();

		$("#save_product").click(function(e){
			e.preventDefault();
			$("#pd_suc").slideUp();
			$("#pd_err").slideUp();

			var btn = $(this);
			var old = btn.html();
			btn.html("<i class='fa fa-spinner fa-spin'></i> " + old);

			var data = {
				date: $("[name=date]").val(),
				name: $("[name=name]").val(),
				description: $("[name=sdescription]").val(),
				cancellation_date: $("[name=cancellation_date]").val(),
				price: $("[name=price]").val(),
				billing: $("[name=billing]").val(),
				price: $("[name=price]").val(),
				active: $("[name=active]").val(),
				billed: $("[name=billed]").val(),
				ct1: $("[name=ct1]").val(),
				ct2: $("[name=ct2]").val(),
				mct1: $("[name=mct1]").val(),
				mct2: $("[name=mct2]").val(),
				np1: $("[name=np1]").val(),
				np2: $("[name=np2]").val(),
				error: $("[name=error]").val(),
				last_billed: $("[name=last_billed]").val(),
				csrf_token: "<?=CSRF::raw();?>",
			};

			if ($("[name=cancellation_allowed]").length) {
				data["cancellation_allowed"] = $("[name=cancellation_allowed]").is(":checked") ? "1" : "0";
			}

			$.post("?p=hosting&id=<?=$info->ID;?>&save=1", data, function(r){
				if(r == "ok"){
					$("#pd_suc").slideDown();
					getCancelDates();
				} else {
					$("#pd_err").html(r).slideDown();
				}
				btn.html(old);
			});
		});
		</script>

		<?php if (method_exists($m, "AssignDomain")) {?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['ASSDOM'];?></div>
		  <div class="panel-body">
		  <div class="alert alert-success" id="ad_suc" style="display: none;"><?=$l['SUC2'];?></div>
		  <div class="alert alert-danger" id="ad_err" style="display: none;"></div>
		  <div class="row"><div class="col-xs-8">
		  	<input type="text" id="domain" placeholder="<?=$l['DOMAP'];?>" class="form-control" />
		  </div><div class="col-xs-4"><a href="#" id="add_domain" class="btn btn-default btn-block"><?=$l['ADD'];?></a>
		  </div></div>
		  </div>
		</div>
		<?php }?>

		<script>
		$("#add_domain").click(function(e){
			e.preventDefault();
			$("#ad_suc").slideUp();
			$("#ad_err").slideUp();
			var btn = $(this);
			var old = btn.html();
			btn.html("<i class='fa fa-spinner fa-spin'></i> " + old);

			$.post("?p=hosting&id=<?=$info->ID;?>&add_domain=" + encodeURIComponent($("#domain").val()), {
				csrf_token: "<?=CSRF::raw();?>",
			}, function(r){
				if(r == "ok") $("#ad_suc").slideDown();
				else $("#ad_err").html(r).slideDown();
				btn.html(old);
			});
		});
		</script>

		<?php
$sql = $db->query("SELECT invoice FROM invoiceitems WHERE relid = " . $info->ID . " GROUP BY invoice ORDER BY invoice DESC");
    ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['INVOICES'];?></div>
		  <div class="panel-body" style="padding-bottom: <?php if ($sql->num_rows == 0) {
        echo 15;
    } else {
        echo 7;
    }
    ?>px;">
		    <?php
if ($sql->num_rows == 0) {
        echo "<i>{$l['NOINV']}</i>";
    } else {
        echo "<ul>";
    }

    $inv = new Invoice;
    while ($row = $sql->fetch_object()) {
        $inv->load($row->invoice);
        echo '<li>' . $dfo->format($inv->getDate(), false) . ' - <a href="?p=invoice&id=' . $inv->getId() . '">' . $inv->getInvoiceNo() . '</a> - <a href="?p=invoices&id=' . $inv->getId() . '" target="_blank">' . $l['PDF'] . '</a></li>';
    }
    if ($sql->num_rows > 0) {
        echo "</ul>";
    }

    ?>
		  </div>
		</div>
	</div>

	<div class="col-md-4">
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['MODOUT'];?></div>
		  <div class="panel-body">
		    <?=$output;?>
		  </div>
		</div>

		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['MODCOM'];?></div>
		  <div class="panel-body">
		  	<div class="alert alert-success" id="command_ok" style="display: none;"><?=$l['SUC3'];?></div>
		  	<div class="alert alert-danger" id="command_fail" style="display: none;"></div>

		    <a href="#" class="btn btn-default command" data-command="Create"><?=$l['COMCRE'];?></a>
		    <?php if (method_exists($m, "Suspend")) {
        echo '<a href="#" class="btn btn-default command" data-command="Suspend">' . $l['COMLOC'] . '</a>';
    }
    ?>
		    <?php if (method_exists($m, "Unsuspend")) {
        echo '<a href="#" class="btn btn-default command" data-command="Unsuspend">' . $l['COMUNL'] . '</a>';
    }
    ?>
		    <?php if (method_exists($m, "ChangePackage")) {
        echo '<a href="#" class="btn btn-default command" data-command="ChangePackage">' . $l['COMCHA'] . '</a>';
    }
    ?>
		    <?php if (method_exists($m, "Delete")) {
        echo '<a href="#" class="btn btn-default command" data-command="Delete">' . $l['COMDEL'] . '</a>';
    }
    ?>
		    <?php if (!empty($pInfo->welcome_mail)) {echo '<a href="#" class="btn btn-info command" data-command="SendWelcomeEmail">' . $l['COMWEL'] . '</a>';}?>
		  </div>
		</div>

		<script>
		$(".command").click(function(e){
			e.preventDefault();
			$("#command_ok").slideUp();
			$("#command_fail").slideUp();

			var btn = $(this);
			var old = btn.html();
			btn.html("<i class='fa fa-spin fa-spinner'></i> " + old);

			$.get("?p=hosting&id=<?=$_GET['id'];?>&command=" + btn.data("command"), function(r){
				btn.html(old);
				if(r == "ok") $("#command_ok").slideDown();
				else $("#command_fail").html(r).slideDown();
			});
		});
		</script>

		<?php if (count($f) > 0) {?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['MYCOM'];?></div>
		  <div class="panel-body">
		  	<?php foreach ($f as $k => $n) {?>
		    <a href="?p=hosting&id=<?=$info->ID;?>&task=<?=$k;?>" class="btn btn-default"><?=$n;?></a>
		    <?php }?>
		  </div>
		</div>
		<?php }?>
	</div>

	<div class="col-md-4">
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['MODSET'];?></div>
		  <div class="panel-body">
		  	<div class="alert alert-success" id="sms_suc" style="display: none;"><?=$l['SUC4'];?></div>
			<?php if (is_object($m)) {
        if ($m->getServerMgmt()) {
            $m->loadOptions($info->ID, false);

            ?>
			<div class="form-group">
				<label><?=$lang['MONITORING']['CHOOSE_SERVER'];?></label>
				<select name="_mgmt_server" data-setting="_mgmt_server" class="form-control prov_settings">
					<option value="0"><?=$lang['MONITORING']['NO_SERVER'];?></option>
					<?php
$serverId = intval($m->getOption("_mgmt_server"));
            $short = $db->real_escape_string($m->getShort());
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
				</select>
			</div>

			<script>
			mgmt = 0;

			function mgmtServer() {
				var sid = $("[name=_mgmt_server]").val();
				if (sid > 0) {
					$("[mgmt=1]").hide();
				} else {
					$("[mgmt=1]").show();
				}
			}

			$("[name=_mgmt_server]").change(mgmtServer);
			</script>
			<?php
}

        echo $m->Config($info->ID, false);
        if ($m->getServerMgmt()) {
            echo "<script>mgmtServer();</script>";
        }
    }
    ?>

		    <a href="#" id="save_module_settings" class="btn btn-default btn-block"><?=$l['SAVE'];?></a>
		  </div>
		</div>

		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['MODDAT'];?><a href="#" class="pull-right" id="add_module_data"><i class="fa fa-plus"></i></a></div>
		  <div class="panel-body">
		  	<div class="alert alert-success" id="smd_suc" style="display: none;"><?=$l['SUC4'];?></div>

		    <?php
$d = unserialize(decrypt($info->module_data));

    if (is_array($d) && count($d) > 0) {
        foreach ($d as $k => $v) {
            $n = "mdata";
            if (!is_string($v)) {
                $v = serialize($v);
                $n = "mdata_s";
            }

            ?>
					<div class="form-group">
						<label><?=$k;?></label>
						<input type="text" name="<?=$k;?>" value="<?=htmlentities($v);?>" class="form-control <?=$n;?>" />
					</div>
					<?php
}

        echo '<a href="#" id="save_module_data" class="btn btn-default btn-block">' . $l['SAVE'] . '</a>';
    } else {
        echo "<i>{$l['NOMODDAT']}</i>";
    }
    ?>
		  </div>
		</div>

		<form style="display: none;" method="POST">
			<input type="hidden" name="new_module_key" value="">
		</form>

		<script>
		$("#add_module_data").click(function(e) {
			e.preventDefault();

			if (key = prompt("<?=$l['MODDATPRO'];?>")) {
				$("[name=new_module_key]").val(key).parent().submit();
			}
		});
		</script>

		<?php if (is_array(unserialize($info->cf)) && count(unserialize(($info->cf))) > 0) {?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$l['MYFIE'];?></div>
		  <div class="panel-body">
		  	<div class="alert alert-success" id="scf_suc" style="display: none;"><?=$l['SUC4'];?></div>

		    <?php
$d = unserialize(($info->cf));

        foreach ($d as $k => $v) {
            $name = $k;

            $cfSql = $db->query("SELECT name FROM products_cf WHERE ID = " . intval($k));
            if ($cfSql->num_rows == 1) {
                $name = $cfSql->fetch_object()->name;
            }

            ?>
		    	<div class="form-group">
		    		<label><?=$name;?></label>
		    		<input type="text" name="<?=$k;?>" value="<?=htmlentities($v);?>" class="form-control cfdata" />
		    	</div>
		    	<?php
}
        ?>

		    <a href="#" id="save_custom_fields" class="btn btn-default btn-block"><?=$l['SAVE'];?></a>
		  </div>
		</div>
		<?php }?>

		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['NOTES'];?></div>
			<div class="panel-body">
				<form method="POST">
				<?php if (isset($_POST['save_notes'])) {
        $db->query("UPDATE client_products SET notes_public = '" . $db->real_escape_string($_POST['notes_public']) . "', notes_private = '" . $db->real_escape_string($_POST['notes_private']) . "' WHERE ID = {$info->ID}");
        $info->notes_public = $_POST['notes_public'];
        $info->notes_private = $_POST['notes_private'];
        echo '<div class="alert alert-success">' . $l['SAVED_NOTES'] . '</div>';
    }
    ?>

				<div class="form-group">
					<label><?=$l['PUBLIC'];?></label>
					<textarea name="notes_public" class="form-control" style="height: 150px; width: 100%; resize: vertical;"><?=htmlentities($info->notes_public);?></textarea>
				</div>

				<div class="form-group">
					<label><?=$l['PRIVATE'];?></label>
					<textarea name="notes_private" class="form-control" style="height: 150px; width: 100%; resize: vertical;"><?=htmlentities($info->notes_private);?></textarea>
				</div>
				<input type="submit" name="save_notes" value="<?=$l['SAVE_NOTES'];?>" class="btn btn-default btn-block">
				</form>
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['FILES'];?><a href="#" data-toggle="modal" data-target="#uploadDomainFile" class="pull-right"><i class="fa fa-plus"></i></a></div>
			<div class="panel-body" style="text-align: justify;">
				<?php
if (file_exists($path) && is_dir($path)) {
        $files = [];
        foreach (glob($path . "/*") as $f) {
            array_push($files, basename($f));
        }
        if (!count($files)) {
            echo "<i>{$l['NOFILES']}</i>";
        } else {
            echo "<ul style='margin-bottom: 0;'>";

            foreach ($files as $file) {
                echo "<li>";
                echo "<a href='?p=hosting&id={$info->ID}&download_file=" . urlencode($file) . "' target='_blank'>" . htmlentities($file) . "</a>";
                echo "<a href='?p=hosting&id={$info->ID}&delete_file=" . urlencode($file) . "' class='pull-right'><i class='fa fa-times'></i></a>";
                echo "</li>";
            }

            echo "</ul>";
        }
    } else {
        echo "<i>" . $l['NOFILES'] . "</i>";
    }
    ?>
			</div>
		</div>

		<div class="modal fade" id="uploadDomainFile" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<form method="POST" enctype="multipart/form-data" role="form" action="?p=hosting&id=<?=$info->ID;?>">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><?=$l['UPLFILES'];?></h4>
						</div>
						<div class="modal-body">
							<div class="form-group" style="margin-bottom: 0;">
								<input type="file" class="form-control" name="upload_files[]" multiple>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
							<button type="submit" class="btn btn-primary"><?=$l['UPLFILES'];?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script>
	$("#save_module_settings").click(function(e){
		e.preventDefault();
		$("#sms_suc").slideUp();
		var btn = $(this);
		var old = btn.html();
		btn.html("<i class='fa fa-spinner fa-spin'></i> " + old);

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

		$.post("?p=hosting&id=<?=$info->ID;?>&save_module_settings=1", {
			data: prov,
			csrf_token: "<?=CSRF::raw();?>",
		}, function(r){
			if(r == "ok") $("#sms_suc").slideDown();
			btn.html(old);
		});
	});

	$("#save_module_data").click(function(e){
		e.preventDefault();
		$("#smd_suc").slideUp();
		var btn = $(this);
		var old = btn.html();
		btn.html("<i class='fa fa-spinner fa-spin'></i> " + old);

		var data = {};
		$(".mdata").each(function(){
			data[$(this).prop("name")] = $(this).val();
		});

		var sdata = {};
		$(".mdata_s").each(function(){
			sdata[$(this).prop("name")] = $(this).val();
		});

		$.post("?p=hosting&id=<?=$info->ID;?>&save_module_data=1", {
			data: data,
			sdata: sdata,
			csrf_token: "<?=CSRF::raw();?>",
		}, function(r){
			if(r == "ok") $("#smd_suc").slideDown();
			btn.html(old);
		});
	});

	$("#save_custom_fields").click(function(e){
		e.preventDefault();
		$("#scf_suc").slideUp();
		var btn = $(this);
		var old = btn.html();
		btn.html("<i class='fa fa-spinner fa-spin'></i> " + old);

		var data = {};
		$(".cfdata").each(function(){
			data[$(this).prop("name")] = $(this).val();
		});

		$.post("?p=hosting&id=<?=$info->ID;?>&save_cf_data=1", {
			data: data,
			csrf_token: "<?=CSRF::raw();?>",
		}, function(r){
			if(r == "ok") $("#scf_suc").slideDown();
			btn.html(old);
		});
	});
	</script>
</div>

<?php }?>
