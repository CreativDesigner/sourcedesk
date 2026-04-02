<?php
global $pars, $db, $CFG, $var, $user, $cur, $nfo, $dfo, $raw_cfg, $lang;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

if (empty($pars)) {
    header('Location: ' . $CFG['PAGEURL'] . "products");
    exit;
}

$title = implode(".", $pars);
$tpl = "domain";

$var['domain'] = $title;

$im = $user->impersonate("domains");
array_push($im, $user->get()['ID']);
$im = array_unique($im);

$userIds = implode(",", $im);

$sql = $db->query("SELECT * FROM domains WHERE domain = '" . $db->real_escape_string($title) . "' AND user IN ($userIds) LIMIT 1");
if ($sql->num_rows == 1) {
    $info = $sql->fetch_object();
    $info->reg_info = unserialize($info->reg_info);
    $var['di'] = &$info;
    $var['dns'] = DNSHandler::getDriver($title);

    if ($info->ssl_cert) {
        $crt = base64_decode($info->ssl_cert);
        $ssl = openssl_x509_parse($crt);
        $validTo = substr($ssl['validTo'], 4, 2) . "." . substr($ssl['validTo'], 2, 2) . ".20" . substr($ssl['validTo'], 0, 2);
        $diff = round((strtotime($validTo) - time()) / 86400);
        $var['ssl_days_left'] = $diff;

        if (isset($_GET['ssl_reset']) && $diff <= 14) {
            $var['hasSslReset'] = true;
            $info->ssl_cert = $info->csr = "";
            $db->query("UPDATE domains SET ssl_cert = '', csr = '' WHERE ID = {$info->ID}");
        }
    }

    $var['customer_when'] = $dfo->format($info->customer_when, false);
    $var['customer_when2'] = date("H:i:s", strtotime($info->customer_when));

    $ex = explode(".", $info->domain);
    $var['sld'] = $sld = array_shift($ex);
    $var['tld'] = $tld = implode(".", $ex);

    $var['trade'] = $user->addTax($info->trade);
    $var['trade_f'] = $cur->infix($nfo->format($user->addTax($cur->convertAmount($cur->getBaseCurrency(), $info->trade))));

    $var['privacy_money'] = $user->addTax($info->privacy_price);
    $var['privacy_money_f'] = $cur->infix($nfo->format($user->addTax($cur->convertAmount($cur->getBaseCurrency(), $info->privacy_price))));

    $var['pars'] = implode("/", $pars);

    $reg = DomainHandler::getRegistrars()[$info->registrar];
    if (!$reg || !$reg->isActive()) {
        $tpl = "error";
        $var['error'] = $lang['DOMAIN']['TECHERR'];
    } else {
        $reg->setUser($user);
        $var['auth_available'] = method_exists($reg, "getAuthCode");

        $dns = DNSHandler::getDriver($title);
        $var['dyndns'] = $dyndns = method_exists($dns, "addDynDNS");

        $var['freessl'] = method_exists($reg, "freeSSL") && (!method_exists($reg, "allowedSSL") || $reg->allowedSSL($user->get()['ID']));

        if ($info->status == "REG_OK" || $info->status == "KK_OK") {
            if (isset($_GET['image'])) {
                $ip = gethostbyname("www." . $info->domain);
                if ($ip === false) {
                    exit;
                }

                if (!@fsockopen("www." . $info->domain, 80, $errno, $errstr, 10)) {
                    exit;
                }

                if (file_exists(__DIR__ . "/tmp.jpg")) {
                    unlink(__DIR__ . "/tmp.jpg");
                }

                $idn = new IdnaConvert;
                $info->domain = $idn->encode($info->domain);

                $programPath = escapeshellcmd($CFG['WKHTMLTOIMAGE']);
                $outputFile = escapeshellarg(__DIR__ . "/tmp.jpg");
                $url = escapeshellarg("http://www." . $info->domain);
                $format = escapeshellarg("jpg");
                $quality = escapeshellarg("70");

                $command = "$programPath --format $format --quality $quality $url $outputFile";
                exec($command);

                if (!file_exists(__DIR__ . "/tmp.jpg")) {
                    exit;
                }

                $img = file_get_contents(__DIR__ . "/tmp.jpg");
                unlink(__DIR__ . "/tmp.jpg");

                header('Content-Type: image/jpeg');
                die($img);
            }

            if (isset($_GET['dyn_pw'])) {
                die(md5(uniqid(rand(10000000, 99999999), true)));
            }

            if (isset($_GET['add_dyn']) && $dyndns) {
                $_POST['subdomain'] = strtolower($_POST['subdomain']);
                if (!ctype_alnum(str_replace("-", "", $_POST['subdomain']))) {
                    die($lang['DOMAIN']['ERR1']);
                }

                if (empty($_POST['password']) || !ctype_alnum($_POST['password']) || strlen($_POST['password']) != 32) {
                    die($lang['DOMAIN']['ERR2']);
                }

                if (!$dns = DNSHandler::getDriver($title)->addDynDNS($title, $_POST['subdomain'], $_POST['password'])) {
                    die($lang['DOMAIN']['ERR3']);
                }

                die("ok");
            }

            if (isset($_GET['delete_dyn']) && $dyndns) {
                DNSHandler::getDriver($title)->delDynDNS($title, $_POST['subdomain']);
                exit;
            }

            function dyn_table()
            {
                global $title, $lang;
                $a = DNSHandler::getDriver($title)->getDynDNS($title);

                ob_start();
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th width="20%"><?=$lang['DOMAIN']['SUBDOMAIN'];?></th>
                            <th width="15%"><?=$lang['DOMAIN']['IP4'];?></th>
                            <th width="30%"><?=$lang['DOMAIN']['IP6'];?></th>
                            <th width="35%"><?=$lang['DOMAIN']['PASSWORD'];?></th>
                        </tr>

                        <?php if (count($a) == 0) {?>
                        <tr><td colspan="4"><center><?=$lang['DOMAIN']['NODYN'];?></center></td></tr>
                        <?php } else {foreach ($a as $d) {?>
                        <tr>
                            <td><?=$d[0];?> <a href="#" class="delete_dyn" data-sub="<?=$d[0];?>"><i class="fa fa-times"></i></a></td>
                            <td><?=$d[1];?></td>
                            <td><?=$d[2];?></td>
                            <td><?=$d[3];?></td>
                        </tr>
                        <?php }}?>
                    </table>
                </div>

                <script>
                $(".delete_dyn").unbind("click");

                $(".delete_dyn").click(function(e){
                    e.preventDefault();
                    $(this).find("i").removeClass("fa-times").addClass("fa-spinner fa-spin");
                    $.post("?delete_dyn=1", {
                        subdomain: $(this).data("sub"),
                        csrf_token: "<?=CSRF::raw();?>",
                    }, function(){
                        $.get("?dyn_table=1", function(r){
                            $("#dyn_table").html(r);
                        });
                    });
                });
                </script>
                <?php
$r = ob_get_contents();
                ob_end_clean();
                return $r;
            }

            if (isset($_GET['dyn_table']) && $dyndns) {
                die(dyn_table());
            }

            if (isset($_GET['delete_revoke'])) {
                $user->log("[" . $title . "] Löschung storniert");
                $db->query("UPDATE domains SET customer_wish = 0, customer_when = '0000-00-00 00:00:00' WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
                die("ok");
            }

            if (isset($_GET['delete_action']) && is_numeric($_GET['delete_action']) && in_array($_GET['delete_action'], array(1, 2, 3)) && isset($_POST['confirm']) && $_POST['confirm'] == "yes") {
                $a = $_GET['delete_action'] == "1" ? "Löschung" : "Transit";
                $user->log("[" . $title . "] $a eingeleitet");

                if ($CFG['DOMAIN_ACTION_CONF']) {
                    $db->query("UPDATE domains SET customer_wish = '" . $db->real_escape_string($_GET['delete_action']) . "', customer_when = '" . date("Y-m-d H:i:s") . "' WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
                } else {
                    $action = [
                        "1" => "0",
                        "2" => "1",
                        "3" => "2",
                    ];

                    if (!array_key_exists($_GET['delete_action'], $action)) {
                        exit;
                    }

                    $reg->deleteDomain($title, $action[$_GET['delete_action']]);
                    $status = $a == "Löschung" ? "DELETED" : "TRANSIT";
                    $db->query("UPDATE domains SET `status` = '$status', `customer_wish` = 0 WHERE ID = {$info->ID}");
                }

                die("ok");
            }

            if (isset($_POST['action']) && $_POST['action'] == "save_ns" && isset($_POST['ns-option'])) {
                if ($_POST['ns-option'] == "isp") {
                    $ns = DNSHandler::getDriver($title)->getNs();
                } else {
                    $ns = array($_POST['ns1'], $_POST['ns2'], $_POST['ns3'], $_POST['ns4'], $_POST['ns5']);
                }

                $info->reg_info['ns'] = $ns;
                $user->log("[" . $title . "] Nameserver-Konfiguration geändert");
                $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info->reg_info)) . "', changed = 1 WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
                $var['suc'] = $lang['DOMAIN']['OK1'];
            }

            if (isset($_GET['dns_delete']) && is_numeric($_GET['dns_delete'])) {
                $dns = DNSHandler::getDriver($title);
                if ($dns->removeRecord($title, $_GET['dns_delete'])) {
                    echo "ok";
                }

                $user->log("[" . $title . "] DNS-Zone geändert");
                exit;
            }

            if (isset($_GET['dns_add'])) {
                $dns = DNSHandler::getDriver($title);
                $r = $dns->addRecord($title, $_POST);
                $user->log("[" . $title . "] DNS-Zone geändert");
                if ($r) {
                    echo "ok";
                } else {
                    echo "fail";
                }

                exit;
            }

            if (isset($_GET['dns_edit']) && is_numeric($_GET['dns_edit'])) {
                $dns = DNSHandler::getDriver($title);
                $r = $dns->editRecord($title, $_GET['dns_edit'], $_POST);
                $user->log("[" . $title . "] DNS-Zone geändert");
                if ($r) {
                    echo "ok";
                } else {
                    echo "fail";
                }

                exit;
            }

            if (isset($_GET['privacy'])) {
                if (!in_array($_GET['privacy'], array("0", "1"))) {
                    die("fail");
                }

                if ($info->privacy == $_GET['privacy']) {
                    die("ok");
                }

                if ($_GET['privacy'] == "1") {
                    if ($info->privacy_price < 0) {
                        die("fail");
                    }

                    if ($user->get()['credit'] < $user->addTax($info->privacy_price)) {
                        die("fail");
                    }

                    $db->query("UPDATE domains SET privacy = 1, changed = 1, recurring = recurring + privacy_price WHERE ID = {$info->ID}");

                    if ($user->addTax($info->privacy_price) > 0) {
                        $inv = new Invoice;
                        $inv->setDate(date("Y-m-d"));
                        $inv->setClient($user->get()['ID']);
                        $inv->setDueDate();
                        $inv->setStatus(0);

                        $item = new InvoiceItem;
                        $item->setDescription("<b>{$info->domain}</b><br />" . $lang['DOMAIN']['PRIVACYINVOICE']);
                        $item->setAmount($user->addTax($info->privacy_price));

                        $inv->addItem($item);
                        $inv->applyCredit();
                        $inv->save();
                        $inv->send();
                    }
                } else {
                    $db->query("UPDATE domains SET privacy = 0, changed = 1, recurring = recurring - privacy_price WHERE ID = {$info->ID}");
                }

                die("ok");
            }

            if (isset($_GET['csr'])) {
                if (!$var['freessl']) {
                    die($lang['DOMAIN']['SSL1']);
                }

                if (!empty($info->csr)) {
                    die($lang['DOMAIN']['SSL2']);
                }

                $csr = openssl_csr_get_subject($_POST['csr']);
                if (false === $csr) {
                    die($lang['DOMAIN']['SSL3']);
                }

                if ($csr['CN'] != $info->domain) {
                    die(str_replace(array("%1", "%2"), array($csr["CN"], $info->domain), $lang['DOMAIN']['SSL4']));
                }

                $dns_info = $reg->freeSSL($info->domain, $_POST['csr']);
                if (!is_array($dns_info) || count($dns_info) != "3") {
                    die($lang['DOMAIN']['SSL5']);
                }

                $ns = $info->reg_info['ns'];

                if ((count($ns) == 2 && filter_var($ns[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) || $ns == DNSHandler::byDomain($title)->getNs()) {
                    $dns = DNSHandler::getDriver($title);
                    if (($zone = $dns->getZone($title)) === false) {
                        $ip = $ip6 = null;
                        $ns = $info->reg_info['ns'];
                        if (count($ns) == 2) {
                            $ip = $ns[0];
                            $ip6 = $ns[1];
                        }

                        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $ip = $CFG['DEFAULT_IP'];
                        }

                        if (!filter_var($ip6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                            $ip6 = null;
                        }

                        $dns->addZone($title, $ns = $user->getNS());
                        $dns->applyTemplate($title, $ns, $ip, $ip6);

                        $addons->runHook("DnsZoneCreated", [
                            "driver" => $dns,
                            "domain" => $title,
                            "client" => $user,
                        ]);
                    }

                    $r = $dns->addRecord($title, array($dns_info[0], $dns_info[1], $dns_info[2], 3600, 0), true);
                    if ($r === false) {
                        die($lang['DOMAIN']['SSL6']);
                    }

                    $die = "ok";
                } else {
                    $die = "ok|{$dns_info[0]}|{$dns_info[1]}|{$dns_info[2]}";
                }

                $_POST['csr'] = base64_encode($_POST['csr']);
                $db->query("UPDATE domains SET csr = '" . $db->real_escape_string($_POST['csr']) . "' WHERE ID = {$info->ID} LIMIT 1");
                die($die);
            }

            if (isset($_GET['dns'])) {
                $dns = DNSHandler::getDriver($title);
                if (($zone = $dns->getZone($title)) === false) {
                    $ip = $ip6 = null;
                    $ns = $info->reg_info['ns'];
                    if (count($ns) == 2) {
                        $ip = $ns[0];
                        $ip6 = $ns[1];
                    }

                    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $ip = $CFG['DEFAULT_IP'];
                    }

                    if (!filter_var($ip6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $ip6 = null;
                    }

                    $dns->addZone($title, $ns = $user->getNS());
                    $dns->applyTemplate($title, $ns, $ip, $ip6);
                    $addons->runHook("DnsZoneCreated", [
                        "driver" => $dns,
                        "domain" => $title,
                        "client" => $user,
                    ]);
                    $zone = $dns->getZone($title);
                    $user->log("[" . $title . "] DNS-Zone angelegt");
                    if ($zone === false) {
                        die('<div class="alert alert-danger">' . $lang['DOMAIN']['DNSERR'] . '</div>');
                    }

                }

                ?>
                <span class="pull-right"><a href="#" data-toggle="modal" data-target="#help" class="btn btn-default"><?=$lang['DOMAIN']['HELP'];?></a> <a href="#" data-toggle="modal" data-target="#add" class="btn btn-primary"><?=$lang['DOMAIN']['DNSADD'];?></a></span><br /><br />

                <div class="modal fade" id="help" tabindex="-1">
                <div class="modal-dialog modal-lg" style="min-width: 50%;" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?=$lang['DOMAIN']['HELP2'];?></h4>
                    </div>
                    <div class="modal-body" style="text-align: justify;">
                        <?=str_replace(array("%p", "%d"), array($CFG['PAGENAME'], $title), $lang['DOMAIN']['HELP3']);?>
                    </div>
                    </div>
                </div>
                </div>

                <div class="modal fade" id="add" tabindex="-1"><form onsubmit="return false;">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?=$lang['DOMAIN']['DNSADD'];?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger" style="display: none; text-align: justify; margin-bottom: 10px;"><?=$lang['DOMAIN']['DNSERR1'];?></div>
                        <div class="row">
                            <div class="col-xs-8">
                                <input type="text" class="form-control" id="add_subdomain" placeholder="<?=$lang['DOMAIN']['SUBDOMAIN'];?>">
                            </div>

                            <div class="col-xs-4">
                                <select class="form-control" id="add_type">
                                    <?php $ts = $dns->recordTypes();foreach ($ts as $t) {
                    echo '<option>' . $t . '</option>';
                }
                ?>
                                </select>
                            </div>
                        </div>

                        <input type="text" class="form-control" id="add_content" placeholder="<?=$lang['DOMAIN']['CONTENT'];?>" style="margin-top: 10px;">

                        <div class="row" style="margin-top: 10px;">
                            <div class="col-xs-6"><div class="input-group">
                                <span class="input-group-addon"><?=$lang['DOMAIN']['TTL'];?></span>
                                <input type="text" class="form-control" id="add_ttl" placeholder="3600" value="3600">
                            </div></div>

                            <div class="col-xs-6"><div class="input-group">
                                <span class="input-group-addon"><?=$lang['DOMAIN']['PRIO'];?></span>
                                <input type="text" class="form-control" id="add_prio" placeholder="10" value="0">
                            </div></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-add" id="add_dns"><?=$lang['DOMAIN']['ADD'];?></button>
                    </div>
                    </div>
                </div>
                </form></div>

                <table id="dns-table" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th><?=$lang['DOMAIN']['SUBDOMAIN'];?></th>
                            <th><?=$lang['DOMAIN']['TYPE'];?></th>
                            <th><?=$lang['DOMAIN']['CONTENT'];?></th>
                            <th><?=$lang['DOMAIN']['TTL'];?></th>
                            <th><?=$lang['DOMAIN']['PRIO'];?></th>
                        </tr>
                    </thead>
                    <tbody id="entries">
                        <?php foreach ($zone as $i => $r) {
                    if ($r[0] == "_domainconnect") {
                        continue;
                    }
                    ?>
                        <tr id="entry_<?=$i;?>" data-toggle="modal" data-target="#dns_<?=$i;?>" style="cursor: pointer;">
                            <td><?=substr($r[0], 0, 45) . (strlen($r[0]) > 45 ? "..." : "");?></td>
                            <td><?=$r[1];?></td>
                            <td><?=substr($r[2], 0, 45) . (strlen($r[2]) > 45 ? "..." : "");?></td>
                            <td><?=$r[3];?></td>
                            <td><?=$r[4];?></td>
                        </tr>
                        <?php }?>
                    </tbody>
                </table>

                <?php foreach ($zone as $i => $r) {?>
                <div class="modal fade" id="dns_<?=$i;?>" tabindex="-1"><form>
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?=$lang['DOMAIN']['EDIT'];?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger" style="display: none; text-align: justify; margin-bottom: 10px;"><?=$lang['DOMAIN']['DNSERR1'];?></div>
                        <div class="row">
                            <div class="col-xs-8">
                                <input type="text" class="form-control subdomain" placeholder="<?=$lang['DOMAIN']['SUBDOMAIN'];?>" value="<?=htmlentities($r[0]);?>">
                            </div>

                            <div class="col-xs-4">
                                <select class="form-control type">
                                    <?php $ts = $dns->recordTypes();foreach ($ts as $t) {
                    echo '<option' . ($r[1] == $t ? ' selected="selected"' : "") . '>' . $t . '</option>';
                }
                    ?>
                                </select>
                            </div>
                        </div>

                        <input type="text" class="form-control content" placeholder="<?=$lang['DOMAIN']['CONTENT'];?>" value="<?=htmlentities($r[2]);?>"" style="margin-top: 10px;">

                        <div class="row" style="margin-top: 10px;">
                            <div class="col-xs-6"><div class="input-group">
                                <span class="input-group-addon"><?=$lang['DOMAIN']['TTL'];?></span>
                                <input type="text" class="form-control ttl" placeholder="3600" value="<?=htmlentities($r[3]);?>">
                            </div></div>

                            <div class="col-xs-6"><div class="input-group">
                                <span class="input-group-addon"><?=$lang['DOMAIN']['PRIO'];?></span>
                                <input type="text" class="form-control prio" placeholder="10" value="<?=htmlentities($r[4]);?>">
                            </div></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default delete-question" data-container="body" data-toggle="popover" data-placement="top" data-html="true" data-content="<?=$lang['DOMAIN']['DNSDELCONF'];?> <button class='btn btn-primary delete-record' data-i='<?=$i;?>' style='height: 18px; padding: 0 8px; line-height: 10px;'><?=$lang['DOMAIN']['DNSDELYES'];?></button> <button class='btn btn-default dismiss-popover' style='height: 18px; padding: 0 8px !important; line-height: 10px; border-bottom: 1px solid #ccc;'><?=$lang['DOMAIN']['DNSDELNO'];?></button>"><?=$lang['DOMAIN']['DNSDEL'];?></button>
                        <button type="submit" class="btn btn-primary save-dns" data-i="<?=$i;?>"><?=$lang['DOMAIN']['DNSSAVE'];?></button>
                    </div>
                    </div>
                </div>
                </form></div>
                <?php }?>

                <script>
                $(document).ready(function() {
                    $(document).off("click", "#add_dns");
                    $(document).off("click", ".delete-record");
                    $(document).off("click", ".dismiss-popover");
                    $(document).off("click", ".save-dns");

                    $('[data-toggle="popover"]').popover();

                    $(document).on("click", ".dismiss-popover", function(){
                        $(this).parents(".popover").popover('hide');
                    });

                    $(document).on("click", ".delete-record", function(){
                        var i = $(this).data("i");
                        $(this).parents(".popover").popover('hide');
                        $("#dns_" + i).find('.delete-question').html('<i class="fa fa-spinner fa-spin" style="color: black; padding: 0; margin: 0;"></i> <?=$lang['DOMAIN']['DNSDELDO'];?>');
                        $("#dns_" + i).find('.btn-save').prop("disabled", true);

                        $.get("?dns_delete=" + i, function(r){
                            if(r == "ok"){
                                $('#dns_' + i).modal('hide');
                                table.row('#entry_' + i).remove().draw();
                            } else {
                                alert("<?=$lang['DOMAIN']['DNSDELERR'];?>");
                                location.reload();
                            }
                        });
                    });

                    $(document).on("click", "#add_dns", function(){
                        $("#add_dns").html('<i class="fa fa-spinner fa-spin" style="padding: 0; margin: 0;"></i> <?=$lang['DOMAIN']['DNSADDDO'];?>').prop("disabled", true);
                        $("#add").find(".alert").slideUp();
                        $.post("?dns_add=1", {
                            0: $("#add_subdomain").val(),
                            1: $("#add_type").val(),
                            2: $("#add_content").val(),
                            3: $("#add_ttl").val(),
                            4: $("#add_prio").val(),
                            csrf_token: "<?=CSRF::raw();?>",
                        }, function(r){
                            if(r == "ok"){
                                $.get("?dns=1", function(r){
                                    var s = r.split("Starting\ DynDNS");
                                    $("#dns").html(s[0]);
                                    $("#dyndns").html(s[1]);

                                    $('.modal').modal('hide');
                                    $("body").removeClass("modal-open");
                                    $(".modal-backdrop").remove();
                                });
                            } else {
                                $("#add").find(".alert").slideDown();
                                $("#add_dns").html('<?=$lang['DOMAIN']['ADD'];?>').prop("disabled", false);
                            }
                        });
                    });

                    $(document).on("click", ".save-dns", function(){
                        var i = $(this).data("i");
                        var f = $("#dns_" + i);
                        $(this).html('<i class="fa fa-spinner fa-spin" style="padding: 0; margin: 0;"></i> <?=$lang['DOMAIN']['DNSSAVEDO'];?>').prop("disabled", true);
                        f.find(".alert").slideUp();
                        $.post("?dns_edit=" + i, {
                            0: f.find(".subdomain").val(),
                            1: f.find(".type").val(),
                            2: f.find(".content").val(),
                            3: f.find(".ttl").val(),
                            4: f.find(".prio").val(),
                            csrf_token: "<?=CSRF::raw();?>",
                        }, function(r){
                            if(r == "ok"){
                                $.get("?dns=1", function(r){
                                    var s = r.split("Starting\ DynDNS");
                                    $("#dns").html(s[0]);
                                    $("#dyndns").html(s[1]);

                                    $('.modal').modal('hide');
                                    $("body").removeClass("modal-open");
                                    $(".modal-backdrop").remove();
                                });
                            } else {
                                f.find(".alert").slideDown();
                                f.find('.save-dns').html("<?=$lang['DOMAIN']['DNSSAVE'];?>").prop("disabled", false);
                            }
                        });
                    });

                    var table = $('#dns-table').DataTable({
                        language: {
                            "sEmptyTable":      "<?=$lang['DATATABLES'][0];?>",
                            "sInfo":            "<?=$lang['DATATABLES'][1];?>",
                            "sInfoEmpty":       "<?=$lang['DATATABLES'][2];?>",
                            "sInfoFiltered":    "<?=$lang['DATATABLES'][3];?>",
                            "sInfoPostFix":     "",
                            "sInfoThousands":   "<?=$lang['DATATABLES'][4];?>",
                            "sLengthMenu":      "<?=$lang['DATATABLES'][5];?>",
                            "sLoadingRecords":  "<?=$lang['DATATABLES'][6];?>",
                            "sProcessing":      "<?=$lang['DATATABLES'][7];?>",
                            "sSearch":          "<?=$lang['DATATABLES'][8];?>",
                            "sZeroRecords":     "<?=$lang['DATATABLES'][9];?>",
                            "oPaginate": {
                                "sFirst":       "<?=$lang['DATATABLES'][10];?>",
                                "sPrevious":    "<?=$lang['DATATABLES'][11];?>",
                                "sNext":        "<?=$lang['DATATABLES'][12];?>",
                                "sLast":        "<?=$lang['DATATABLES'][13];?>"
                            },
                            "oAria": {
                                "sSortAscending":  "<?=$lang['DATATABLES'][14];?>",
                                "sSortDescending": "<?=$lang['DATATABLES'][15];?>"
                            }
                        },
                    });
                });
                </script>
                Starting DynDNS
                <span class="pull-right"><a href="#" data-toggle="modal" data-target="#setup" class="btn btn-default"><?=$lang['DOMAIN']['DYNSETUP'];?></a> <a href="#" data-toggle="modal" data-target="#add_dyn" class="btn btn-primary"><?=$lang['DOMAIN']['DYNADD'];?></a></span><br /><br />

                <div id="dyn_table"><?=dyn_table();?></div>

                <div class="modal fade" id="add_dyn" tabindex="-1"><form onsubmit="return false;">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?=$lang['DOMAIN']['DYNADD2'];?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger" id="add_dyn_err" style="display: none; text-align: justify; margin-bottom: 10px;"></div>

                        <div class="input-group">
                            <input type="text" class="form-control" id="dyn_subdomain" placeholder="<?=$lang['DOMAIN']['SUBDOMAIN'];?>">
                            <span class="input-group-addon">.<?=$title;?></span>
                        </div>

                        <div class="input-group" style="margin-top: 10px;">
                            <span class="input-group-addon"><?=$lang['DOMAIN']['DYNPASSWORD'];?></span>
                            <input type="text" class="form-control" id="dyn_password" value="" readonly="readonly" style="background-color: white;" onfocus="this.select();">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-add" id="add_dyn_do"><?=$lang['DOMAIN']['DYNADDBTN'];?></button>
                    </div>
                    </div>
                </div>
                </form></div>

                <script>
                $("#add_dyn").on('show.bs.modal', function(){
                    $.get("?dyn_pw=1", function(r){
                        $("#dyn_password").val(r);
                    });
                });

                $("#add_dyn_do").click(function(){
                    $("#add_dyn_err").slideUp();
                    $("#add_dyn_do").html("<i class='fa fa-spinner fa-spin'></i><?=$lang['DOMAIN']['DYNADDDO'];?>").prop("disabled", true);

                    $.post("?add_dyn=1", {
                        subdomain: $("#dyn_subdomain").val(),
                        password: $("#dyn_password").val(),
                        csrf_token: "<?=CSRF::raw();?>",
                    }, function(r) {
                        if(r == "ok"){
                            $.get("?dyn_table=1", function(r){
                                $("#dyn_table").html(r);
                                $("#add_dyn").modal('toggle');
                                $("#add_dyn_do").html("<?=$lang['DOMAIN']['DYNADDBTN'];?>").prop("disabled", false);
                            });
                        } else {
                            $("#add_dyn_err").html(r).slideDown();
                            $("#add_dyn_do").html("<?=$lang['DOMAIN']['DYNADDBTN'];?>").prop("disabled", false);
                        }
                    });
                });
                </script>

                <div class="modal fade" id="setup" tabindex="-1">
                <div class="modal-dialog modal-lg" style="min-width: 50%;" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?=$lang['DOMAIN']['DYNSETUPTITLE'];?></h4>
                    </div>
                    <div class="modal-body" style="text-align: justify;">
                    <?=str_replace("%p", $CFG['PAGEURL'], $lang['DOMAIN']['DYNSETUPINFO']);?><br /><br />
                            <img src="<?=$raw_cfg['PAGEURL'];?>images/fritzdns.png" alt="<?=$lang['DOMAIN']['DYNSETTINGS'];?>" title="<?=$lang['DOMAIN']['DYNSETTINGS'];?>" style="width: 100%; height: auto;" />
                    </div>
                    </div>
                </div>
                </div>
                <?php
exit;
            }

            if (isset($_GET['trade']) && $_GET['trade'] == "owner" && $var['trade'] > 0) {
                try {
                    $errors = array();
                    $new = $_POST['data'];

                    if (empty($_POST['data']) || !is_array($_POST['data']) || count($_POST['data']) != 10) {
                        throw new Exception($lang['DOMAIN']['TRANSMITERR']);
                    }

                    $countries = array();
                    $sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY alpha2 ASC");
                    while ($row = $sql->fetch_object()) {
                        $countries[$row->alpha2] = $row->name;
                    }

                    if (empty($_POST['data'][0])) {
                        array_push($errors, $lang['DOMAINS']['ERR6']);
                    }

                    if (empty($_POST['data'][1])) {
                        array_push($errors, $lang['DOMAINS']['ERR7']);
                    }

                    if (empty($_POST['data'][3])) {
                        array_push($errors, $lang['DOMAINS']['ERR8']);
                    }

                    if (empty($_POST['data'][4]) || !array_key_exists($_POST['data'][4], $countries)) {
                        array_push($errors, $lang['DOMAINS']['ERR9']);
                    }

                    if (empty($_POST['data'][5])) {
                        array_push($errors, $lang['DOMAINS']['ERR10']);
                    }

                    if (empty($_POST['data'][6])) {
                        array_push($errors, $lang['DOMAINS']['ERR11']);
                    }

                    if (empty($_POST['data'][7])) {
                        array_push($errors, $lang['DOMAINS']['ERR15']);
                    }

                    $_POST['data'][7] = str_replace(".", "", $_POST['data'][7]);
                    if (substr($_POST['data'][7], 0, 2) == "00") {
                        $_POST['data'][7] = "+" . ltrim($_POST['data'][7], "0");
                        $_POST['data'][7] = substr($_POST['data'][7], 0, 3) . "." . substr($_POST['data'][7], 3);
                    } else if (substr($_POST['data'][7], 0, 1) == "0") {
                        $_POST['data'][7] = "+49." . ltrim($_POST['data'][7], "0");
                    } else if (substr($_POST['data'][7], 0, 1) == "+") {
                        $_POST['data'][7] = substr($_POST['data'][7], 0, 3) . "." . substr($_POST['data'][7], 3);
                    } else {
                        $_POST['data'][7] = "+49.0" . $_POST['data'][7];
                    }

                    if (!empty($_POST['data'][8])) {
                        $_POST['data'][8] = str_replace(".", "", $_POST['data'][8]);
                        if (substr($_POST['data'][8], 0, 2) == "00") {
                            $_POST['data'][8] = "+" . ltrim($_POST['data'][8], "0");
                            $_POST['data'][8] = substr($_POST['data'][8], 0, 3) . "." . substr($_POST['data'][8], 3);
                        } else if (substr($_POST['data'][8], 0, 1) == "0") {
                            $_POST['data'][8] = "+49." . ltrim($_POST['data'][8], "0");
                        } else if (substr($_POST['data'][8], 0, 1) == "+") {
                            $_POST['data'][8] = substr($_POST['data'][8], 0, 3) . "." . substr($_POST['data'][8], 3);
                        } else {
                            $_POST['data'][8] = "+49.0" . $_POST['data'][8];
                        }
                    }

                    if (empty($_POST['data'][9])) {
                        array_push($errors, $lang['DOMAINS']['ERR13']);
                    }

                    if (!filter_var($_POST['data'][9], FILTER_VALIDATE_EMAIL)) {
                        array_push($errors, $lang['DOMAINS']['ERR14']);
                    }

                    if (count($errors) > 0) {
                        echo "<li>" . implode("</li><li>", $errors) . "</li>";
                    } else {
                        if ($info->reg_info["owner"] != $_POST['data']) {
                            if ($var['trade'] > $user->get()['credit']) {
                                throw new Exception($lang['DOMAIN']['NOTCREDIT']);
                            }

                            $invoice = new Invoice;
                            $invoice->setDate(date("Y-m-d"));
                            $invoice->setClient($user->get()['ID']);
                            $invoice->setDueDate();
                            $invoice->setStatus(0);

                            $item = new InvoiceItem;
                            $item->setDescription("<b>" . $title . "</b><br />" . $lang['DOMAIN']['TRADEINVOICE']);
                            $item->setAmount($var['trade']);
                            $invoice->addItem($item);

                            $invoice->save();
                            $invoice->applyCredit();
                            $invoice->send();

                            $info->reg_info["owner"] = $_POST['data'];
                            $user->log("[" . $title . "] Trade durchgef&uuml;hrt");
                            $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info->reg_info)) . "', trade_waiting = 1 WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");

                            echo "ok";
                        } else {
                            echo "<li>" . $lang['DOMAIN']['NOTRADE'] . "</li>";
                        }
                    }
                    exit;
                } catch (Exception $ex) {
                    die("<li>{$ex->getMessage()}</li>");
                }
            }

            if (isset($_GET['change']) && in_array($_GET['change'], array("owner", "admin", "tech", "zone"))) {
                try {
                    $errors = array();
                    $new = $_POST['data'];
                    if (!$user->get()['domain_contacts'] && in_array($_GET['change'], array("tech", "zone"))) {
                        throw new Exception($lang['DOMAIN']['NOTSUPPORTED']);
                    }

                    if (empty($_POST['data']) || !is_array($_POST['data']) || (count($_POST['data']) != 10 && count($_POST['data']) != 11)) {
                        throw new Exception($lang['DOMAIN']['TRANSMITERR']);
                    }

                    $countries = array();
                    $sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY alpha2 ASC");
                    while ($row = $sql->fetch_object()) {
                        $countries[$row->alpha2] = $row->name;
                    }

                    if (empty($_POST['data'][0])) {
                        array_push($errors, $lang['DOMAINS']['ERR6']);
                    }

                    if (empty($_POST['data'][1])) {
                        array_push($errors, $lang['DOMAINS']['ERR7']);
                    }

                    if (empty($_POST['data'][3])) {
                        array_push($errors, $lang['DOMAINS']['ERR8']);
                    }

                    if (empty($_POST['data'][4]) || !array_key_exists($_POST['data'][4], $countries)) {
                        array_push($errors, $lang['DOMAINS']['ERR9']);
                    }

                    if (empty($_POST['data'][5])) {
                        array_push($errors, $lang['DOMAINS']['ERR10']);
                    }

                    if (empty($_POST['data'][6])) {
                        array_push($errors, $lang['DOMAINS']['ERR11']);
                    }

                    if (empty($_POST['data'][7])) {
                        array_push($errors, $lang['DOMAINS']['ERR15']);
                    }

                    $_POST['data'][7] = str_replace(".", "", $_POST['data'][7]);
                    if (substr($_POST['data'][7], 0, 2) == "00") {
                        $_POST['data'][7] = "+" . ltrim($_POST['data'][7], "0");
                        $_POST['data'][7] = substr($_POST['data'][7], 0, 3) . "." . substr($_POST['data'][7], 3);
                    } else if (substr($_POST['data'][7], 0, 1) == "0") {
                        $_POST['data'][7] = "+49." . ltrim($_POST['data'][7], "0");
                    } else if (substr($_POST['data'][7], 0, 1) == "+") {
                        $_POST['data'][7] = substr($_POST['data'][7], 0, 3) . "." . substr($_POST['data'][7], 3);
                    } else {
                        $_POST['data'][7] = "+49.0" . $_POST['data'][7];
                    }

                    if (!empty($_POST['data'][8])) {
                        $_POST['data'][8] = str_replace(".", "", $_POST['data'][8]);
                        if (substr($_POST['data'][8], 0, 2) == "00") {
                            $_POST['data'][8] = "+" . ltrim($_POST['data'][8], "0");
                            $_POST['data'][8] = substr($_POST['data'][8], 0, 3) . "." . substr($_POST['data'][8], 3);
                        } else if (substr($_POST['data'][8], 0, 1) == "0") {
                            $_POST['data'][8] = "+49." . ltrim($_POST['data'][8], "0");
                        } else if (substr($_POST['data'][8], 0, 1) == "+") {
                            $_POST['data'][8] = substr($_POST['data'][8], 0, 3) . "." . substr($_POST['data'][8], 3);
                        } else {
                            $_POST['data'][8] = "+49.0" . $_POST['data'][8];
                        }
                    }

                    if (empty($_POST['data'][9])) {
                        array_push($errors, $lang['DOMAINS']['ERR13']);
                    }

                    if (!filter_var($_POST['data'][9], FILTER_VALIDATE_EMAIL)) {
                        array_push($errors, $lang['DOMAINS']['ERR14']);
                    }

                    if (count($errors) > 0) {
                        echo "<li>" . implode("</li><li>", $errors) . "</li>";
                    } else {
                        if ($info->reg_info[$_GET['change']] != $_POST['data']) {
                            $info->reg_info[$_GET['change']] = $_POST['data'];
                            $user->log("[" . $title . "] Kontakt-Daten geändert");
                            $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info->reg_info)) . "', changed = 1 WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
                        }
                        echo "ok";
                    }
                    exit;
                } catch (Exception $ex) {
                    die("<li>{$ex->getMessage()}</li>");
                }
            }

            if (isset($_GET['auth']) && method_exists($reg, "getAuthCode")) {
                if ($user->get()['auth_lock'] == "0" && !$CFG['CUSTOMER_AUTHCODE']) {
                    die("fail");
                }

                if ($user->get()['auth_lock'] == "1") {
                    die("fail");
                }

                $ai = $reg->getAuthCode($title);
                if (!is_string($ai) || substr($ai, 0, 5) != "AUTH:") {
                    $user->log("[" . $title . "] Fehler bei AuthCode-Anforderung");
                    die("fail");
                }
                $user->log("[" . $title . "] AuthCode angefordert");
                die(substr($ai, 5));
            }

            if (isset($_GET['renew']) && in_array($_GET['renew'], array("0", "1"))) {
                if ($_GET['renew'] != $di->auto_renew) {
                    $user->log("[" . $title . "] Verlängerungs-Einstellungen geändert");
                    $db->query("UPDATE domains SET auto_renew = '" . intval($_GET['renew']) . "', changed = 1 WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
                }
                die("ok");
            }

            if (isset($_GET['lock']) && in_array($_GET['lock'], array("0", "1")) && method_exists($reg, "setRegLock")) {
                if ($_GET['lock'] != $di->transfer_lock) {
                    $user->log("[" . $title . "] Transfer-Sperre geändert");
                    $db->query("UPDATE domains SET transfer_lock = '" . intval($_GET['lock']) . "', changed = 1 WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
                }
                die("ok");
            }

            $t = $user->getVAT();
            if (is_array($t) && count($t) == 2 && doubleval($t[1]) == $t[1]) {
                $info->recurring = $info->recurring * (1 + $t[1] / 100);
            }

            $var['lockAvailable'] = method_exists($reg, "setRegLock") && is_object($sql = $db->query("SELECT domain_lock FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'")) && $sql->num_rows == 1 && $sql->fetch_object()->domain_lock;

            $countries = array();
            $sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                $countries[$row->alpha2] = $row->name;
            }

            $var['countries'] = $countries;
        }

        if (isset($_POST['action']) && $_POST['action'] == "restart_kk" && isset($_POST['authcode']) && $info->status == "KK_ERROR") {
            $info->reg_info['transfer'][0] = $_POST['authcode'];
            $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info->reg_info)) . "', status = 'KK_WAITING', sent = 0 WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
            $user->log("[" . $title . "] KK erneut gestartet");

            $info->status = "KK_WAITING";
            $info->sent = 0;
        }

        if (isset($_POST['action']) && $_POST['action'] == "restart_reg" && $info->status == "REG_ERROR") {
            $db->query("UPDATE domains SET status = 'REG_WAITING', sent = 0 WHERE domain = '" . $db->real_escape_string($title) . "' AND user = '" . intval($user->get()['ID']) . "' LIMIT 1");
            $user->log("[" . $title . "] REG erneut gestartet");

            $info->status = "REG_WAITING";
            $info->sent = 0;
        }
    }
}

$var['cur'] = $cur;
$var['nfo'] = $nfo;