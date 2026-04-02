<?php
$l = $lang['ADD_DOMAIN'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(13)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_domain");} else {

    if (!empty($_GET['user']) && $u = User::getInstance($_GET['user'], "ID")) {

        ob_start();

        if (isset($_POST['domain'])) {
            try {
                if (empty($_POST['domain'])) {
                    throw new Exception($l['ERR1']);
                }
                $_POST['domain'] = strtolower($_POST['domain']);

                $ex = explode(".", $domain = $_POST['domain']);
                if (count($ex) < 2) {
                    throw new Exception($l['ERR2']);
                }

                $sld = array_shift($ex);
                $tld = implode(".", $ex);

                if (empty($_POST['status']) || !in_array($_POST['status'], [
                    "REG_WAITING",
                    "KK_WAITING",
                    "REG_OK",
                    "KK_OK",
                ])) {
                    throw new Exception($l['ERR3']);
                }

                if (empty($_POST['registrar'])) {
                    throw new Exception($l['ERR5']);
                }

                if ($_POST['registrar'] == "-auto-") {
                    $reg = DomainHandler::getRegistrarByTld($tld);

                    if (!is_object($reg) || !($reg instanceof DomainRegistrar) || !$reg->isActive()) {
                        throw new Exception($l['ERR6']);
                    }

                    $_POST['registrar'] = $reg->getShort();
                } else {
                    if (!array_key_exists($_POST['registrar'], DomainHandler::getRegistrars()) || !DomainHandler::getRegistrars()[$_POST['registrar']]->isActive()) {
                        throw new Exception($l['ERR7']);
                    }
                }

                $reg_info = [
                    "domain" => $domain,
                    "ns" => [
                        $CFG['DEFAULT_IP'],
                        "",
                    ],
                ];

                if ($_POST['status'] == "KK_WAITING") {
                    if (empty($_POST['authcode'])) {
                        throw new Exception($l['ERR4']);
                    }

                    $reg_info["transfer"] = [
                        $_POST['authcode'],
                        true,
                    ];
                }

                $recurring = doubleval($nfo->phpize($_POST['recurring']));
                $in = doubleval($nfo->phpize($_POST['invoice_now']));

                if ($in != 0) {
                    $in = $u->addTax($in);

                    $inv = new Invoice;
                    $inv->setClient($u->get()['ID']);
                    $inv->setDate(date("Y-m-d"));
                    $inv->setDueDate();

                    $item = new InvoiceItem;
                    $item->setDescription("<b>" . $domain . "</b><br />" . $dfo->format(time(), false, false) . " - " . $dfo->format(date("Y-m-d", strtotime("+1 year, -1 day"))));
                    $item->setAmount($in);
                    $item->save();

                    $inv->addItem($item);
                    $inv->save();

                    $inv->applyCredit(false);
                    $inv->save();
                    $inv->send();
                }

                $reg_info = serialize($reg_info);

                $sql = $db->prepare("INSERT INTO domains (`user`, `domain`, `reg_info`, `recurring`, `created`, `status`, `registrar`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $sql->bind_param("issdsss", $uid = $u->get()['ID'], $domain, $reg_info, $recurring, $created = date("Y-m-d"), $_POST['status'], $_POST['registrar']);
                if (!$sql->execute()) {
                    throw new Exception($l['ERR8']);
                }
                $sql->close();

                alog("domain", "created", $domain, $u->get()['ID']);
                header('Location: ?p=domain&d=' . urlencode($domain) . '&u=' . $u->get()['ID']);
                exit;
            } catch (Exception $ex) {
                $err = '<div class="alert alert-danger"><b>Fehler!</b> ' . htmlentities($ex->getMessage()) . '</div>';
            }
        }

        $ob = ob_get_contents();
        ob_end_clean();

        ?>
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?> <small><?=$u->getfName();?></small></h1>
    </div>
    <!-- /.col-lg-12 -->
</div>

<?=!empty($err) ? $err : "";?>

<form role="form" method="POST">
    <div class="form-group">
        <label><?=$l['DOMAIN'];?></label>
        <input type="text" name="domain" class="form-control" placeholder="<?=$l['DOMAINP'];?>" value="<?=isset($_POST['domain']) ? htmlentities($_POST['domain']) : '';?>" />
    </div>

    <div class="form-group">
        <label><?=$l['STATUS'];?></label>
        <select name="status" class="form-control" onchange="statusUpdate();">
            <option value="REG_WAITING"><?=$l['REG_WAITING'];?></option>
            <option value="KK_WAITING"<?=isset($_POST['status']) && $_POST['status'] == "KK_WAITING" ? ' selected=""' : '';?>><?=$l['KK_WAITING'];?></option>
            <option value="REG_OK"<?=isset($_POST['status']) && $_POST['status'] == "REG_OK" ? ' selected=""' : '';?>><?=$l['REG_OK'];?></option>
            <option value="KK_OK"<?=isset($_POST['status']) && $_POST['status'] == "KK_OK" ? ' selected=""' : '';?>><?=$l['KK_OK'];?></option>
        </select>
    </div>

    <div class="form-group" id="authcode" style="display: none;">
        <label><?=$l['AUTHCODE'];?></label>
        <input type="text" name="authcode" class="form-control" placeholder="<?=$l['AUTHCODEP'];?>" value="<?=isset($_POST['authcode']) ? htmlentities($_POST['authcode']) : '';?>" />
    </div>

    <script type="text/javascript">
    function statusUpdate() {
        if ($("[name=status]").val() == "KK_WAITING") {
            $("#authcode").show();
        } else {
            $("#authcode").hide();
        }
    }

    statusUpdate();
    $("[name=status]").change(statusUpdate);
    </script>

    <div class="form-group">
        <label><?=$l['REGISTRAR'];?></label>
        <select name="registrar" class="form-control">
            <option value="-auto-"><?=$l['REGAUTO'];?></option>
            <?php foreach (DomainHandler::getRegistrars() as $short => $obj) {if (!$obj->isActive()) {
            continue;
        }
            ?>
            <option value="<?=$short;?>"<?=isset($_POST['registrar']) && $_POST['registrar'] == $short ? ' selected=""' : '';?>><?=$obj->getName();?></option>
            <?php }?>
        </select>
    </div>

    <div class="form-group">
        <label><?=$l['RENEWP'];?></label>
        <div class="input-group">
            <?php if ($cur->getPrefix()) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
            <input type="text" name="recurring" class="form-control" placeholder="<?=$l['RENEWPP'];?>" value="<?=isset($_POST['recurring']) ? $nfo->format($nfo->phpize($_POST['recurring'])) : '';?>" />
            <?php if ($cur->getSuffix()) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
        </div>
    </div>

    <div class="form-group">
        <label><?=$l['INSTI'];?></label>
        <div class="input-group">
            <?php if ($cur->getPrefix()) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
            <input type="text" name="invoice_now" class="form-control" placeholder="<?=$l['RENEWPP'];?>" value="<?=isset($_POST['invoice_now']) ? $nfo->format($nfo->phpize($_POST['invoice_now'])) : '';?>" />
            <?php if ($cur->getSuffix()) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['DO'];?></button>
</form>


<?php } else {require __DIR__ . "/error.php";}}?>