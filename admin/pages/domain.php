<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(13)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "domain");} else {

    $sql = $db->query("SELECT * FROM domains WHERE domain = '" . $db->real_escape_string($_GET['d']) . "' AND user = " . intval($_GET['u']));
    if ($sql->num_rows != 1) {require __DIR__ . "/error.php";} else { $info = $row = $sql->fetch_object();

        $l = $lang['DOMAIN'];

        title($info->domain);
        menu("customers");

        $labels = array(
            "REG_WAITING" => "warning",
            "KK_WAITING" => "warning",
            "REG_OK" => "success",
            "KK_OK" => "success",
            "KK_OUT" => "default",
            "EXPIRED" => "default",
            "DELETED" => "default",
            "TRANSIT" => "default",
            "KK_ERROR" => "danger",
            "REG_ERROR" => "danger",
        );

        $status = $lang['DOMAIN_STATUS'];

        $reg = DomainHandler::getRegistrars()[$info->registrar];
        $user = User::getInstance($info->user, "ID");
        if ($reg && $user) {
            $reg->setUser($user);
        }

        if (isset($_GET['reg_status'])) {
            die($reg->syncDomain($info->domain)['status'] ? '<font color="green">' . $l['OK'] . '</font>' : '<font color="red">' . $l['NOK'] . '</font>');
        }

        if (isset($_GET['expiration'])) {
            die($dfo->format($reg->syncDomain($info->domain)['expiration'], false));
        }

        if (isset($_GET['auto_renew'])) {
            die($reg->syncDomain($info->domain)['auto_renew'] ? '1' : '0');
        }

        if (isset($_GET['transfer_lock'])) {
            die($reg->syncDomain($info->domain)['transfer_lock'] ? '1' : '0');
        }

        if (isset($_GET['privacy'])) {
            die($reg->syncDomain($info->domain)['privacy'] ? '1' : '0');
        }

        if (isset($_GET['ssl_sync'])) {
            die($reg->sslSync($info->domain) ?: "");
        }

        if (isset($_GET['csr_sync'])) {
            die($reg->csrSync($info->domain) ?: "");
        }

        if (method_exists($reg, "deleteDomain")) {
            if (!empty($_POST['transit'])) {
                $r = $reg->deleteDomain($info->domain, 1);
                if ($r !== true) {
                    die($l['ERR1'] . " " . htmlentities($r));
                }

                $u = new User($row->user, "ID");
                if ($u->get()["ID"] == $row->user) {
                    $mtObj = new MailTemplate("Domain zurückgegeben");

                    $title = $mtObj->getTitle($u->getLanguage());
                    $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                    $maq->enqueue([
                        "domain" => $row->domain,
                    ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
                }

                alog("domain", "transit", $info->domain);

                $db->query("UPDATE domains SET `status` = 'TRANSIT', `customer_wish` = 0 WHERE ID = {$info->ID}");
                die("ok");
            }

            if (!empty($_POST['detransit'])) {
                $r = $reg->deleteDomain($info->domain, 2);
                if ($r !== true) {
                    die($l['ERR1'] . " " . htmlentities($r));
                }

                $u = new User($row->user, "ID");
                if ($u->get()["ID"] == $row->user) {
                    $mtObj = new MailTemplate("Domain zurückgegeben");

                    $title = $mtObj->getTitle($u->getLanguage());
                    $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                    $maq->enqueue([
                        "domain" => $row->domain,
                    ], $mtObj, $u->get()['mail'], $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], true, 0, 0, $mtObj->getAttachments($u->getLanguage()));
                }

                alog("domain", "detransit", $info->domain);

                $db->query("UPDATE domains SET `status` = 'TRANSIT', `customer_wish` = 0 WHERE ID = {$info->ID}");
                die("ok");
            }

            if (!empty($_POST['delete'])) {
                $r = $reg->deleteDomain($info->domain, 0);
                if ($r !== true) {
                    die($l['ERR2'] . " " . htmlentities($r));
                }

                alog("domain", "delete", $info->domain);

                $db->query("UPDATE domains SET `status` = 'DELETED', `customer_wish` = 0 WHERE ID = {$info->ID}");
                die("ok");
            }
        }

        if (isset($_GET['save_ssl'])) {
            $sql = $db->prepare("UPDATE domains SET `ssl_cert` = ?, `csr` = ? WHERE `ID` = ?");
            $sql->bind_param("ssi", $a = base64_encode($_POST['ssl_cert']), $b = base64_encode($_POST['csr']), $info->ID);
            $sql->execute();
            alog("domain", "save_ssl", $info->domain);
            exit;
        }

        if (isset($_GET['save_dns']) && method_exists($reg, "changeNameserver")) {
            if (empty($_POST['ns1'])) {
                die($l['ERR3']);
            }

            if (empty($_POST['ns2'])) {
                die($l['ERR4']);
            }

            if (empty($_POST['local'])) {
                $r = $reg->changeNameserver($info->domain, array($_POST['ns1'], $_POST['ns2'], $_POST['ns3'], $_POST['ns4'], $_POST['ns5']));
            } else {
                $r = true;
            }

            $reg_info = unserialize($info->reg_info);
            $reg_info["ns"] = array($_POST['ns1'], $_POST['ns2'], $_POST['ns3'], $_POST['ns4'], $_POST['ns5']);
            $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($reg_info)) . "' WHERE ID = {$info->ID}");

            alog("domain", "save_dns", $info->domain);

            if ($r !== true) {
                die($l['ERR5'] . " " . htmlentities($r));
            }

            die("ok");
        }

        if (isset($_GET['save_contact'])) {
            $info->reg_info = unserialize($info->reg_info);

            foreach (array("owner" => "Owner-C", "admin" => "Admin-C", "tech" => "Tech-C", "zone" => "Zone-C") as $key => $name) {
                if (empty($_POST[$key]) || !is_array($_POST[$key]) || count($_POST[$key]) != 11) {
                    throw new Exception(str_replace("%n", $name, $l['ERR6']));
                }

                $countries = array();
                $sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY alpha2 ASC");
                while ($row2 = $sql->fetch_object()) {
                    $countries[$row2->alpha2] = $row2->name;
                }

                if (empty($_POST[$key][0])) {
                    die("$name: " . $l['NOFIRST']);
                }

                if (empty($_POST[$key][1])) {
                    die("$name: " . $l['NOLAST']);
                }

                if (empty($_POST[$key][3])) {
                    die("$name: " . $l['NOADDR']);
                }

                if (empty($_POST[$key][4]) || !array_key_exists($_POST[$key][4], $countries)) {
                    die("$name: " . $l['NOLAND']);
                }

                if (empty($_POST[$key][5])) {
                    die("$name: " . $l['NOPOSTC']);
                }

                if (empty($_POST[$key][6])) {
                    die("$name: " . $l['NOCITY']);
                }

                if (empty($_POST[$key][7])) {
                    die("$name: " . $l['NOPHONE']);
                }

                $_POST[$key][7] = str_replace(".", "", $_POST[$key][7]);
                if (substr($_POST[$key][7], 0, 2) == "00") {
                    $_POST[$key][7] = "+" . ltrim($_POST[$key][7], "0");
                    $_POST[$key][7] = substr($_POST[$key][7], 0, 3) . "." . substr($_POST[$key][7], 3);
                } else if (substr($_POST[$key][7], 0, 1) == "0") {
                    $_POST[$key][7] = "+49." . ltrim($_POST[$key][7], "0");
                } else if (substr($_POST[$key][7], 0, 1) == "+") {
                    $_POST[$key][7] = substr($_POST[$key][7], 0, 3) . "." . substr($_POST[$key][7], 3);
                } else {
                    $_POST[$key][7] = "+49.0" . $_POST[$key][7];
                }

                if (!empty($_POST[$key][8])) {
                    $_POST[$key][8] = str_replace(".", "", $_POST[$key][8]);
                    if (substr($_POST[$key][8], 0, 2) == "00") {
                        $_POST[$key][8] = "+" . ltrim($_POST[$key][8], "0");
                        $_POST[$key][8] = substr($_POST[$key][8], 0, 3) . "." . substr($_POST[$key][8], 3);
                    } else if (substr($_POST[$key][8], 0, 1) == "0") {
                        $_POST[$key][8] = "+49." . ltrim($_POST[$key][8], "0");
                    } else if (substr($_POST[$key][8], 0, 1) == "+") {
                        $_POST[$key][8] = substr($_POST[$key][8], 0, 3) . "." . substr($_POST[$key][8], 3);
                    } else {
                        $_POST[$key][8] = "+49.0" . $_POST[$key][8];
                    }
                }

                if (empty($_POST[$key][9])) {
                    die("$name: " . $l['NOMAIL']);
                }

                if (!filter_var($_POST[$key][9], FILTER_VALIDATE_EMAIL)) {
                    die("$name: " . $l['INMAIL']);
                }

                $info->reg_info[$key] = $_POST[$key];
            }

            if (!isset($_GET['force'])) {
                $r = $reg->changeContact($info->domain, $info->reg_info['owner'], $info->reg_info['admin'], $info->reg_info['tech'], $info->reg_info['zone']);
                if ($r !== true) {
                    die($l['ERR7'] . " " . htmlentities($r));
                }

            }

            alog("domain", "save_contact", $info->domain);

            $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info->reg_info)) . "' WHERE ID = {$info->ID} LIMIT 1");

            die("ok");
        }

        if (isset($_GET['restart'])) {
            $info->reg_info = unserialize($info->reg_info);
            if ($info->status == "KK_ERROR") {
                $info->reg_info['transfer'][0] = $_POST['auth'];
            }

            alog("domain", "restart", $info->domain);

            $db->query("UPDATE domains SET reg_info = '" . $db->real_escape_string(serialize($info->reg_info)) . "', status = '" . ($info->status == "KK_ERROR" ? "KK_WAITING" : "REG_WAITING") . "', sent = 0 WHERE ID = {$info->ID} LIMIT 1");
            exit;
        }

        if (isset($_GET['auth']) && method_exists($reg, "getAuthCode")) {
            $a = $reg->getAuthCode($info->domain);
            alog("domain", "authcode_requested", $info->domain);
            if (substr($a, 0, 5) == "AUTH:") {
                die(substr($a, 5));
            }

            die('<font color="red">' . $a . '</font>');
        }

        if (isset($_GET['get_ssl'])) {
            if (!method_exists($reg, "freeSSL")) {
                die($l['ERR8']);
            }

            $csr = openssl_csr_get_subject($_POST['csr']);
            if (false === $csr) {
                die($l['ERR27']);
            }

            if ($csr['CN'] != $info->domain) {
                die(str_replace(["%c", "%d"], [$csr['CN'], $info->domain], $l['ERR9']));
            }

            $dns_info = $reg->freeSSL($info->domain, $_POST['csr']);
            if (!is_array($dns_info) || count($dns_info) != "3") {
                die($l['ERR10']);
            }

            $ns = $info->reg_info['ns'];

            if ((count($ns) == 2 && filter_var($ns[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) || ($ns[0] == $CFG['NS1'] && $ns[1] == $CFG['NS2'] && $ns[2] == $CFG['NS3'] && $ns[3] == $CFG['NS4'] && $ns[4] == $CFG['NS5'])) {
                $dns = DNSHandler::getDriver($info->domain);
                if (($zone = $dns->getZone($info->domain)) === false) {
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

                    $user = new User($info->user, "ID");
                    $dns->addZone($info->domain, $user->getNS());
                    $dns->applyTemplate($info->domain, $user->getNS(), $ip, $ip6);

                    $addons->runHook("DnsZoneCreated", [
                        "driver" => $dns,
                        "domain" => $info->domain,
                        "client" => $user,
                    ]);
                }

                $r = $dns->addRecord($info->domain, array($dns_info[0], $dns_info[1], $dns_info[2], 3600, 0), true);
                if ($r === false) {
                    die($l['ERR11']);
                }

                $die = "ok|" . $l['SUC1'];
            } else {
                $die = "ok|" . str_replace(["%1", "%2", "%3"], [$dns_info[1], $dns_info[0], $dns_info[2]], $l['SUC2']);
            }

            $_POST['csr'] = base64_encode($_POST['csr']);
            $db->query("UPDATE domains SET csr = '" . $db->real_escape_string($_POST['csr']) . "' WHERE ID = {$info->ID} LIMIT 1");
            alog("domain", "ssl_requested", $info->domain);
            die($die);
        }

        if (isset($_GET['delete_error'])) {
            $db->query("UPDATE domains SET error = '' WHERE ID = {$info->ID} LIMIT 1");
            alog("domain", "delete_error", $info->domain);
            header('Location: ?p=domain&d=' . $info->domain . "&u=" . $info->user);
            exit;
        }

        if (isset($_GET['save'])) {
            if (empty($_POST['domain'])) {
                die($l['ERR12']);
            }

            if (empty($_POST['user'])) {
                die($l['ERR13']);
            }

            if ($db->query("SELECT 1 FROM clients WHERE ID = " . intval($_POST['user']))->num_rows != 1) {
                die($l['ERR14']);
            }

            if (empty($_POST['status']) || !array_key_exists($_POST['status'], $status)) {
                die($l['ERR15']);
            }

            if (empty($_POST['created']) || strtotime($_POST['created']) === false) {
                die($l['ERR16']);
            }

            if (empty($_POST['expiration']) || strtotime($_POST['expiration']) === false) {
                die($l['ERR17']);
            }

            if (empty($_POST['expiration_prov']) || strtotime($_POST['expiration_prov']) === false) {
                die($l['ERR17']);
            }

            $auto_renew = isset($_POST['auto_renew']) && $_POST['auto_renew'] == "true" ? 1 : 0;
            $transfer_lock = isset($_POST['transfer_lock']) && $_POST['transfer_lock'] == "true" ? 1 : 0;
            $privacy = isset($_POST['privacy']) && $_POST['privacy'] == "true" ? 1 : 0;
            if (empty($_POST['registrar']) || !array_key_exists($_POST['registrar'], DomainHandler::getRegistrars()) || !DomainHandler::getRegistrars()[$_POST['registrar']]->isActive()) {
                die($l['ERR18']);
            }

            if (!empty($_POST['dns_provider']) && !array_key_exists($_POST['dns_provider'], DNSHandler::getDrivers()) && $_POST['dns_provider'] != "-none-") {
                die($l['ERR19']);
            }

            if (empty($_POST['recurring']) || !is_numeric($nfo->phpize($_POST['recurring']))) {
                die($l['ERR20']);
            }

            if (empty($_POST['trade']) || !is_numeric($nfo->phpize($_POST['trade']))) {
                die($l['ERR21']);
            }

            if (empty($_POST['privacy_price']) || !is_numeric($nfo->phpize($_POST['privacy_price']))) {
                die($l['ERR22']);
            }

            if (empty($_GET['force'])) {
                $reg2 = DomainHandler::getRegistrars()[$_POST['registrar']];

                $user2 = User::getInstance($_POST['user'], "ID");
                if ($reg2 && $user2) {
                    $reg2->setUser($user2);
                }

                if (!$reg2->syncDomain($_POST['domain'])['status']) {
                    die($l['ERR23']);
                }
            }

            $sql = $db->prepare("UPDATE domains SET `user` = ?, `domain` = ?, `status` = ?, `created` = ?, `expiration` = ?, `auto_renew` = ?, `transfer_lock` = ?, `registrar` = ?, `recurring` = ?, `trade` = ?, `privacy` = ?, `privacy_price` = ?, `dns_provider` = ?, `inclusive_id` = ?, `addon_id` = ?, `expiration_prov` = ? WHERE `ID` = ?");
            $sql->bind_param("issssiisddidsiisi", $_POST['user'], $_POST['domain'], $_POST['status'], $a = date("Y-m-d", strtotime($_POST['created'])), $b = date("Y-m-d", strtotime($_POST['expiration'])), $auto_renew, $transfer_lock, $_POST['registrar'], $c = doubleval($nfo->phpize($_POST['recurring'])), $d = doubleval($nfo->phpize($_POST['trade'])), $privacy, $e = doubleval($nfo->phpize($_POST['privacy_price'])), $_POST['dns_provider'], $_POST['inclusive_id'], $_POST['addon_id'], $f = date("Y-m-d", strtotime($_POST['expiration_prov'])), $info->ID);
            $sql->execute();

            alog("domain", "data_updated", $info->domain);

            if (empty($_GET['force'])) {
                $r = $reg2->changeValues($_POST['domain'], (bool) $transfer_lock, (bool) $auto_renew, (bool) $privacy);
                if ($r !== true) {
                    die($l['ERR24'] . " " . htmlentities($r));
                }

            }

            die("ok");
        }

        $path = __DIR__ . "/../../files/domains/" . basename($info->domain);

        if (!empty($_GET['delete_file'])) {
            @unlink($path . "/" . basename($_GET['delete_file']));
            header("Location: ?p=domain&d=" . urlencode($info->domain) . "&u=" . $info->user);
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

            header("Location: ?p=domain&d=" . urlencode($info->domain) . "&u=" . $info->user);
            exit;
        }

        ?>
<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$info->domain;?> <small><a href="http://www.<?=$row->domain;?>" target="_blank"><i class="fa fa-external-link"></i></a> <div class="label label-<?=$labels[$info->status];?>"><?=$info->payment ? $lang['HOSTING']['WAIT_PAY'] : $status[$info->status];?></div></small></h1>

		<?php
if (empty($info->registrar) || !$reg || !($reg instanceof DomainRegistrar)) {
            echo '<div class="alert alert-warning">' . $l['ERR25'] . '</div>';
        }

        if ($reg instanceof DomainRegistrar && !$reg->isActive()) {
            echo '<div class="alert alert-warning">' . $l['ERR26'] . '</div>';
        }

        $what = array(
            "1" => $l['DEL1'],
            "2" => $l['DEL2'],
            "3" => $l['DEL3'],
        );
        if ($info->customer_wish > 0) {
            echo '<div class="alert alert-danger">' . $l['DELMSG1'] . ' ' . $dfo->format($row->customer_when) . ' ' . $what[$info->customer_wish] . ' ' . $l['DELMSG2'] . ' ' . (time() - strtotime($row->customer_when) < 86400 ? '<b>' . $l['DELMSG3'] . '</b>' : '') . ' ' . $l['DELMSG4'] . '.</div>';
        }

        if (!empty($row->error)) {
            echo '<div class="alert alert-info">' . $l['ERR28'] . ' [ <a href="?p=domain&d=' . $info->domain . '&u=' . $info->user . '&delete_error=1">' . $l['REMERR'] . '</a> ]:<br />' . htmlentities($row->error) . '</div>';
        }

        if ($info->changed == -1) {
            echo '<div class="alert alert-info">' . $l['ERR29'] . '</div>';
        }

        if ($info->changed == 1) {
            echo '<div class="alert alert-info">' . $l['ERR30'] . '</div>';
        }

        if ($info->trade == -1) {
            echo '<div class="alert alert-info">' . $l['ERR31'] . '</div>';
        }

        ?>

		<?php if (!empty($info->inclusive_id)) {?>
		<div class="alert alert-info"><?=$l['INCLDOM1'];?> <a href="?p=hosting&id=<?=$info->inclusive_id;?>"><?=$l['INCLDOM2'];?></a>.</div>
		<?php }?>

		<?php if (!empty($info->addon_id)) {?>
		<div class="alert alert-info"><?=$l['ADDONDOM1'];?> <a href="?p=hosting&id=<?=$info->addon_id;?>"><?=$l['ADDONDOM2'];?></a>.</div>
		<?php }?>

		<div class="row">
			<div class="col-md-4">
				<div class="panel panel-primary">
					<div class="panel-heading">
						<?=$l['DOMDATA'];?>
						<?php
if ($db->query("SELECT 1 FROM domain_log WHERE domain = '" . $db->real_escape_string($row->domain) . "' LIMIT 1")->num_rows) {
            ?>
						<span class="pull-right">
							<a href="?p=domain_log&d=<?=urlencode($row->domain);?>" style="color: white;">
								<i class="fa fa-file-text-o"></i>
							</a>
						</span>
						<?php }?>
					</div>
					<div class="panel-body">
						<div class="alert alert-danger" id="domain-err" style="display: none;"></div>
						<div class="alert alert-success" id="domain-suc" style="display: none;"><?=$l['DOMDATASAVED'];?></div>

						<div class="form-group">
							<label><?=$l['DOMAIN'];?></label>
							<input type="text" id="domain" value="<?=$row->domain;?>" placeholder="<?=$l['DOMAINP'];?>" class="form-control" />
						</div>

						<div class="form-group">
							<label><?=$l['CUSTOMER'];?></label>
							<input type="text" class="form-control customer-input" placeholder="<?=$l['CUSTOMERP'];?>" value="<?=ci($row->user);?>">
							<input type="hidden" id="user" value="<?=$row->user;?>">
							<div class="customer-input-results"></div>
						</div>

						<div class="form-group">
							<label><?=$l['STATUS'];?></label>
							<select id="status" class="form-control">
								<?php foreach ($status as $k => $v) {
            echo "<option value='$k'" . ($k == $row->status ? ' selected="selected"' : "") . ">$v</option>";
        }
        ?>
							</select>
						</div>

						<div class="form-group">
							<label><?=$l['CREATED'];?></label>
							<input type="text" id="created" value="<?=$row->created != "0000-00-00" ? $dfo->format($row->created, false) : "";?>" placeholder="<?=$dfo->placeholder(false);?>" class="form-control datepicker" />
						</div>

						<div class="form-group">
							<label><?=$l['EXPIRE'];?></label>
							<input type="text" id="expiration" value="<?=$row->expiration != "0000-00-00" ? $dfo->format($row->expiration, false) : "";?>" placeholder="<?=$dfo->placeholder(false);?>" class="form-control datepicker" />
						</div>

						<div class="form-group">
							<label><?=$l['EXPIRE_PROV'];?></label>
							<div class="input-group">
								<input type="text" id="expiration_prov" value="<?=$row->expiration_prov != "0000-00-00" ? $dfo->format($row->expiration_prov, false) : "";?>" placeholder="<?=$dfo->placeholder(false);?>" class="form-control datepicker" />
								<span class="input-group-addon"><a href="#" id="expiration_link"><i class="fa fa-refresh" id="expiration_icon"></i></a></span>
							</div>
						</div>

						<div class="form-group">
							<label><?=$l['AUTORENEW'];?> <a href="#" id="auto_renew_link"><i class="fa fa-refresh" id="auto_renew_icon"></i></a></label>
							<div class="checkbox" style="margin-top: 0;">
								<label>
									<input type="checkbox" id="auto_renew" value="1"<?=$info->auto_renew ? ' checked="checked"' : "";?> />
									<?=$l['ACTIVATE'];?>
								</label>
							</div>
						</div>

						<div class="form-group">
							<label><?=$l['TRANSFERLOCK'];?> <a href="#" id="transfer_lock_link"><i class="fa fa-refresh" id="transfer_lock_icon"></i></a></label>
							<div class="checkbox" style="margin-top: 0;">
								<label>
									<input type="checkbox" id="transfer_lock" value="1"<?=$info->transfer_lock ? ' checked="checked"' : "";?> />
									<?=$l['ACTIVATE'];?>
								</label>
							</div>
						</div>

						<div class="form-group">
							<label><?=$l['PRIVACY'];?> <a href="#" id="privacy_link"><i class="fa fa-refresh" id="privacy_icon"></i></a></label>
							<div class="checkbox" style="margin-top: 0;">
								<label>
									<input type="checkbox" id="privacy" value="1"<?=$info->privacy ? ' checked="checked"' : "";?> />
									<?=$l['ACTIVATE'];?>
								</label>
							</div>
						</div>

						<?php if (method_exists($reg, "getAuthCode")) {?>
						<div class="form-group">
							<label><?=$l['AUTHCODE'];?></label><br />
							<a href="#" id="auth_link"><i class="fa fa-refresh" id="auth_icon"></i> <span id="auth_result"><?=$l['FETCH'];?></span></a>
						</div>
						<?php }?>

						<div class="form-group">
							<label><?=$l['REGISTRAR'];?></label>
							<select id="registrar" class="form-control">
								<?php foreach (DomainHandler::getRegistrars() as $k => $v) {
            if ($v->isActive()) {
                echo "<option value='$k'" . ($k == $row->registrar ? ' selected="selected"' : "") . ">" . $v->getName() . "</option>";
            }
        }

        ?>
							</select>
						</div>

						<div class="form-group">
							<label><?=$l['DNSPROV'];?></label>
							<select id="dns_provider" class="form-control">
								<option value=""><?=$l['DNSPROVSTANDARD'];?></option>
								<option value="-none-"<?=$row->dns_provider == "-none-" ? ' selected="selected"' : '';?>><?=$l['DNSPROVNONE'];?></option>
								<?php foreach (DNSHandler::getDrivers() as $k => $v) {
            echo "<option value='$k'" . ($k == $row->dns_provider ? ' selected="selected"' : "") . ">" . $v->getName() . "</option>";
        }
        ?>
							</select>
						</div>

						<div class="form-group">
							<label><?=$l['PRIVACYPRICE'];?> <span class="font-weight: normal;">(<?=$l['NET'];?>)</span></label>
							<div class="input-group">
								<?php if (!empty($cur->getPrefix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getPrefix($cur->getBaseCurrency());?></span><?php }?>
								<input type="text" id="privacy_price" value="<?=$nfo->format($row->privacy_price);?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
								<?php if (!empty($cur->getSuffix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getSuffix($cur->getBaseCurrency());?></span><?php }?>
							</div>
						</div>

						<div class="form-group">
							<label><?=$l['RECURRING'];?> <span class="font-weight: normal;">(<?=$l['NET'];?>)</span></label>
							<div class="input-group">
								<?php if (!empty($cur->getPrefix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getPrefix($cur->getBaseCurrency());?></span><?php }?>
								<input type="text" id="recurring" value="<?=$nfo->format($row->recurring);?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
								<?php if (!empty($cur->getSuffix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getSuffix($cur->getBaseCurrency());?></span><?php }?>
							</div>
						</div>

						<div class="form-group">
							<label><?=$l['INCLUSIVE_DOMAIN'];?></label>
							<select class="form-control" id="inclusive_id" disabled="">
								<option value="0"><?=$l['NO_INCLUSIVE_DOMAIN'];?></option>
								<?php
$sql = $db->query("SELECT ID, product, name, description FROM client_products WHERE user = {$user->get()['ID']} ORDER BY ID ASC");
        while ($ipr = $sql->fetch_object()) {
            $name = $ipr->name;
            if (!$ipr->name) {
                $prodSql = $db->query("SELECT name FROM products WHERE ID = {$ipr->product}");
                if (!$prodSql->num_rows) {
                    continue;
                }
                $name = $prodSql->fetch_object()->name;
                if (@unserialize($name)) {
                    $name = unserialize($name)[$CFG['LANG']];
                }
            }

            $active = "";
            if ($ipr->ID == $row->inclusive_id) {
                $active = ' selected=""';
            }

            $text = "#" . $ipr->ID . " | " . htmlentities($name);

            if ($ipr->description) {
                $text .= " | " . htmlentities($ipr->description);
            }

            echo '<option value="' . $ipr->ID . '"' . $active . '>' . $text . '</option>';
        }
        ?>
							</select>
						</div>

						<div class="form-group">
							<label><?=$l['ADDON_DOMAIN'];?></label>
							<select class="form-control" id="addon_id" disabled="">
								<option value="0"><?=$l['NO_ADDON_DOMAIN'];?></option>
								<?php
$sql = $db->query("SELECT ID, product, name, description FROM client_products WHERE user = {$user->get()['ID']} ORDER BY ID ASC");
        while ($ipr = $sql->fetch_object()) {
            $name = $ipr->name;
            if (!$ipr->name) {
                $prodSql = $db->query("SELECT name FROM products WHERE ID = {$ipr->product}");
                if (!$prodSql->num_rows) {
                    continue;
                }
                $name = $prodSql->fetch_object()->name;
                if (@unserialize($name)) {
                    $name = unserialize($name)[$CFG['LANG']];
                }
            }

            $active = "";
            if ($ipr->ID == $row->addon_id) {
                $active = ' selected=""';
            }

            $text = "#" . $ipr->ID . " | " . htmlentities($name);

            if ($ipr->description) {
                $text .= " | " . htmlentities($ipr->description);
            }

            echo '<option value="' . $ipr->ID . '"' . $active . '>' . $text . '</option>';
        }
        ?>
							</select>
						</div>
<script>
function disableOtherAddonInclusive() {
	$("#addon_id").prop("disabled", false);
	$("#inclusive_id").prop("disabled", false);

	if ($("#addon_id").val() != "0") {
		$("#inclusive_id").prop("disabled", true).val("0");
	}

	if ($("#inclusive_id").val() != "0") {
		$("#addon_id").prop("disabled", true).val("0");
	}
}

$(document).ready(disableOtherAddonInclusive);
$("#addon_id").change(disableOtherAddonInclusive);
$("#inclusive_id").change(disableOtherAddonInclusive);
</script>
						<div class="form-group">
							<label><?=$l['TRADEPRICE'];?> <span class="font-weight: normal;">(<?=$l['NET'];?>)</span></label>
							<div class="input-group">
								<?php if (!empty($cur->getPrefix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getPrefix($cur->getBaseCurrency());?></span><?php }?>
								<input type="text" id="trade" value="<?=$nfo->format($row->trade);?>" placeholder="<?=$nfo->placeholder();?>" class="form-control" />
								<?php if (!empty($cur->getSuffix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getSuffix($cur->getBaseCurrency());?></span><?php }?>
							</div>
						</div>

						<div class="form-group">
							<label><?=$l['REGSTATUS'];?></label><br />
							<a href="#" id="reg_status_link"><i class="fa fa-refresh" id="reg_status_icon"></i> <span id="reg_status_result"><?=$l['FETCH'];?></span></a>
						</div>

						<div class="form-group">
							<label><?=$l['LASTSYNC'];?></label><br />
							<?=$row->last_sync != '0000-00-00 00:00:00' ? $dfo->format($row->last_sync) : "<i>{$l['NOTYET']}</i>";?>
						</div>

						<a class="btn btn-primary btn-block" href="#" id="save_domain"><?=$l['SAVEDATA'];?></a>
						<small><a href="#" id="save_domain_local"><?=$l['SAVELOCAL'];?></a><br /><?=$l['DATAHINT'];?></small>
					</div>
				</div>
			</div>

			<script>
			$("#reg_status_link").click(function(e) {
				e.preventDefault();
				$("#reg_status_icon").addClass("fa-spin");
				$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&reg_status=1", function(r){
					$("#reg_status_icon").removeClass("fa-spin");
					$("#reg_status_result").html(r);
				});
			});

			$("#expiration_link").click(function(e) {
				e.preventDefault();
				$("#expiration_icon").addClass("fa-spin");
				$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&expiration=1", function(r){
					$("#expiration_icon").removeClass("fa-spin");
					$("#expiration_prov").val(r);
				});
			});

			$("#auto_renew_link").click(function(e) {
				e.preventDefault();
				$("#auto_renew_icon").addClass("fa-spin");
				$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&auto_renew=1", function(r){
					$("#auto_renew_icon").removeClass("fa-spin");
					$("#auto_renew").prop("checked", r == "1");
				});
			});

			$("#transfer_lock_link").click(function(e) {
				e.preventDefault();
				$("#transfer_lock_icon").addClass("fa-spin");
				$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&transfer_lock=1", function(r){
					$("#transfer_lock_icon").removeClass("fa-spin");
					$("#transfer_lock").prop("checked", r == "1");
				});
			});

			$("#privacy_link").click(function(e) {
				e.preventDefault();
				$("#privacy_icon").addClass("fa-spin");
				$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&privacy=1", function(r){
					$("#privacy_icon").removeClass("fa-spin");
					$("#privacy").prop("checked", r == "1");
				});
			});

			$("#auth_link").click(function(e) {
				e.preventDefault();
				$("#auth_icon").addClass("fa-spin");
				$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&auth=1", function(r){
					$("#auth_icon").removeClass("fa-spin");
					$("#auth_result").html(r);
				});
			});

			function save_domain(force){
				$("#domain-suc").slideUp();
				$("#domain-err").slideUp();
				$("#save_domain").html("<i class='fa fa-spin fa-spinner'></i> <?=$l['BEINGSAVED'];?>");
				$("#save_domain_local").hide();

				if ($("#dns_provider").val() == "-none-") {
					$("#manage_dns").prop("disabled", true);
				} else {
					$("#manage_dns").prop("disabled", false);
				}

				$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&save=1" + (force ? '&force=1' : ''), {
					domain: $("#domain").val(),
					user: $("#user").val(),
					status: $("#status").val(),
					created: $("#created").val(),
					expiration: $("#expiration").val(),
					expiration_prov: $("#expiration_prov").val(),
					auto_renew: $("#auto_renew").is(":checked"),
					transfer_lock: $("#transfer_lock").is(":checked"),
					registrar: $("#registrar").val(),
					dns_provider: $("#dns_provider").val(),
					recurring: $("#recurring").val(),
					inclusive_id: $("#inclusive_id").val(),
					addon_id: $("#addon_id").val(),
					trade: $("#trade").val(),
					privacy: $("#privacy").is(":checked"),
					privacy_price: $("#privacy_price").val(),
					csrf_token: "<?=CSRF::raw();?>",
				}, function(r){
					$("#save_domain").html("<?=$l['SAVEDATA'];?>");
					$("#save_domain_local").show();
					if(r == "ok") $("#domain-suc").slideDown();
					else $("#domain-err").html(r).slideDown();
				});
			}

			$("#save_domain").click(function(e) {
				e.preventDefault();
				save_domain(false);
			});

			$("#save_domain_local").click(function(e) {
				e.preventDefault();
				save_domain(true);
			});
			</script>

			<div class="col-md-4">
				<?php if (method_exists($reg, "changeNameserver")) {?>
				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['NAMESERVER'];?></div>
					<div class="panel-body">
						<form method="POST">
							<?php
$ns = unserialize($info->reg_info)['ns'];
            ?>

							<div class="alert alert-danger" style="display: none;" id="dns-error"></div>
							<div class="alert alert-success" style="display: none;" id="dns-success"><?=$l['SAVENS'];?></div>

							<div class="form-group">
								<label><?=str_replace("%n", "1", $l['XDNS']);?></label>
								<input type="text" id="ns1" value="<?=count($ns) == 2 ? $CFG['NS1'] : $ns[0];?>" placeholder="<?=$CFG['NS1'];?>" class="form-control" />
							</div>

							<div class="form-group">
								<label><?=str_replace("%n", "2", $l['XDNS']);?></label>
								<input type="text" id="ns2" value="<?=count($ns) == 2 ? $CFG['NS2'] : $ns[1];?>" placeholder="<?=$CFG['NS1'];?>" class="form-control" />
							</div>

							<div class="form-group">
								<label><?=str_replace("%n", "3", $l['XDNS']);?></label>
								<input type="text" id="ns3" value="<?=count($ns) == 2 ? $CFG['NS3'] : $ns[2];?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" />
							</div>

							<div class="form-group">
								<label><?=str_replace("%n", "4", $l['XDNS']);?></label>
								<input type="text" id="ns4" value="<?=count($ns) == 2 ? $CFG['NS4'] : $ns[3];?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" />
							</div>

							<div class="form-group">
								<label><?=str_replace("%n", "5", $l['XDNS']);?></label>
								<input type="text" id="ns5" value="<?=count($ns) == 2 ? $CFG['NS5'] : $ns[4];?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" />
							</div>

							<div class="row"><div class="col-sm-6">
								<a href="?p=dns&d=<?=$row->domain;?>&u=<?=$row->user;?>" class="btn btn-default btn-block" id="manage_dns"<?=$row->dns_provider == "-none-" ? ' disabled=""' : '';?>><?=$l['DNSZONE'];?></a>
							</div>
							<div class="col-sm-6">
								<a href="#" id="save_dns" class="btn btn-primary btn-block"><?=$l['SAVEDNS'];?></a>
							</div></div>
							<small><a href="#" id="save_dns_local"><?=$l['SAVELOCAL'];?></a></small>
						</form>
					</div>
				</div>

				<script>
				$("#save_dns").click(function(e) {
					e.preventDefault();
					$("#save_dns").html("<i class='fa fa-spin fa-spinner'></i> <?=$l['DNSBEINGSAVED'];?>");
					$("#dns-error").slideUp();
					$("#dns-success").slideUp();
					$("#save_dns_local").hide();
					$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&save_dns=1", {
						ns1: $("#ns1").val(),
						ns2: $("#ns2").val(),
						ns3: $("#ns3").val(),
						ns4: $("#ns4").val(),
						ns5: $("#ns5").val(),
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						$("#save_dns").html("<?=$l['SAVEDNS'];?>");
						if(r == "ok") $("#dns-success").slideDown();
						else $("#dns-error").html(r).slideDown();

						$("#save_dns_local").show();
					});
				});

				$("#save_dns_local").click(function(e) {
					e.preventDefault();
					$("#save_dns").html("<i class='fa fa-spin fa-spinner'></i> <?=$l['DNSBEINGSAVED'];?>");
					$("#dns-error").slideUp();
					$("#dns-success").slideUp();
					$("#save_dns_local").hide();
					$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&save_dns=1", {
						ns1: $("#ns1").val(),
						ns2: $("#ns2").val(),
						ns3: $("#ns3").val(),
						ns4: $("#ns4").val(),
						ns5: $("#ns5").val(),
						local: 1,
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						$("#save_dns").html("<?=$l['SAVEDNS'];?>");
						if(r == "ok") $("#dns-success").slideDown();
						else $("#dns-error").html(r).slideDown();

						$("#save_dns_local").show();
					});
				});
				</script>
				<?php }?>

				<?php if (method_exists($reg, "freeSSL")) {?>
				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['SSL'];?></div>
					<div class="panel-body">
						<form method="POST">
							<div class="alert alert-success" id="ssl-success" style="display: none;"><?=$l['SSLSAVED'];?></div>
							<div class="alert alert-success" id="ssl-success2" style="display: none;"></div>
							<div class="alert alert-danger" id="ssl-error" style="display: none;"></div>

							<div class="form-group">
								<label><?=$l['CSR'];?> <a href="#" id="csr_sync_link"><i class="fa fa-refresh" id="csr_sync_icon"></i></a></label>
								<textarea id="csr" style="resize: none; height: 70px;" class="form-control"><?=base64_decode($row->csr);?></textarea>
							</div>

							<div class="form-group">
								<label><?=$l['CERT'];?> <a href="#" id="ssl_sync_link"><i class="fa fa-refresh" id="ssl_sync_icon"></i></a></label>
								<textarea id="ssl_cert" style="resize: none; height: 70px;" class="form-control"><?=base64_decode($row->ssl_cert);?></textarea>
							</div>

							<div class="form-group">
								<label><?=$l['LASTSYNC'];?></label><br />
								<?=$row->ssl_sync != '0000-00-00 00:00:00' ? $dfo->format($row->ssl_sync) : "<i>{$l['NOTYET']}</i>";?>
							</div>

							<input type="hidden" name="action" value="ssl" />

							<div class="row">
								<div class="col-sm-6">
									<a href="#" class="btn btn-default btn-block" id="get_ssl"><?=$l['GETSSL'];?></a>
								</div>
								<div class="col-sm-6">
									<a href="#" class="btn btn-primary btn-block" id="save_ssl"><?=$l['SAVESSL'];?></a>
								</div>
							</div>
						</form>
					</div>
				</div>

				<script>
				$("#ssl_sync_link").click(function(e) {
					e.preventDefault();
					$("#ssl_sync_icon").addClass("fa-spin");
					$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&ssl_sync=1", function(r){
						$("#ssl_sync_icon").removeClass("fa-spin");
						$("#ssl_cert").val(r);
					});
				});

				$("#csr_sync_link").click(function(e) {
					e.preventDefault();
					$("#csr_sync_icon").addClass("fa-spin");
					$.get("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&csr_sync=1", function(r){
						$("#csr_sync_icon").removeClass("fa-spin");
						$("#csr").val(r);
					});
				});

				$("#save_ssl").click(function(e) {
					e.preventDefault();
					$("#ssl-success").slideUp();
					$("#ssl-success3").slideUp();
					$("#ssl-error").slideUp();
					$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&save_ssl=1", {
						csr: $("#csr").val(),
						ssl_cert: $("#ssl_cert").val(),
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						$("#ssl-success").slideDown();
					});
				});

				$("#get_ssl").click(function(e) {
					e.preventDefault();
					$("#get_ssl").html("<i class='fa fa-spin fa-spinner'></i> <?=$l['GETTINGSSL'];?>");
					$("#ssl-success").slideUp();
					$("#ssl-success2").slideUp();
					$("#ssl-error").slideUp();
					$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&get_ssl=1", {
						csr: $("#csr").val(),
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						$("#get_ssl").html("<?=$l['GETSSL'];?>");
						if(r.split("|")[0] == "ok") $("#ssl-success2").html(r.split("|")[1]).slideDown();
						else $("#ssl-error").html(r).slideDown();
					});
				});
				</script>
				<?php }?>
			</div>

			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['CONTACTS'];?></div>
					<div class="panel-body">
						<div id="contact-suc" class="alert alert-success" style="display: none;"><?=$l['CTSSAVED'];?></div>
						<div id="contact-err" class="alert alert-danger" style="display: none;"></div>

						<ul class="nav nav-tabs nav-justified" role="tablist">
						    <li class="active"><a href="#owner" data-toggle="tab" role="tab"><?=$l['CTS1'];?></a></li>
						    <li><a href="#admin" data-toggle="tab" role="tab"><?=$l['CTS2'];?></a></li>
						    <li><a href="#tech" data-toggle="tab" role="tab"><?=$l['CTS3'];?></a></li>
						    <li><a href="#zone" data-toggle="tab" role="tab"><?=$l['CTS4'];?></a></li>
						</ul><br />

						<div class="tab-content">
	    					<div role="tabpanel" class="tab-pane active" id="owner">
	    						<?php if ($info->trade > 0) {
            echo '<div class="alert alert-info" style="margin-bottom: 10px;">' . $l['CTSTRADE'] . '</div>';
        }
        ?>

	    						<div class="row">
					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="owner-firstname" placeholder="<?=$l['FIRSTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][0]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>

					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="owner-lastname" placeholder="<?=$l['LASTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][1]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="owner-company" placeholder="<?=$l['COMPANYO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][2]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="owner-street" placeholder="<?=$l['ADDRESS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][3]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-2 col-xs-12">
					                    <select id="owner-country" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?>>
					                    	<?php
$sql = $db->query("SELECT * FROM client_countries ORDER BY alpha2 ASC");
        while ($row2 = $sql->fetch_object()) {
            echo "<option" . ($row2->alpha2 == unserialize($info->reg_info)['owner'][4] ? ' selected="selected"' : '') . ">{$row2->alpha2}</option>";
        }

        ?>
					                    </select>
					                </div>

					                <div class="col-sm-2 col-xs-12">
					                    <input type="text" id="owner-postcode" placeholder="<?=$l['POSTCODE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][5]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>

					                <div class="col-sm-8 col-xs-12">
					                    <input type="text" id="owner-city" placeholder="<?=$l['CITY'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][6]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-4">
					                    <input type="text" id="owner-telephone" placeholder="<?=$l['TELEPHONE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][7]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="owner-telefax" placeholder="<?=$l['FAXO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][8]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="owner-email" placeholder="<?=$l['EMAILAD'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][9]);?>" class="form-control"<?=$info->trade > 0 ? ' disabled="disabled"' : "";?> />
					                </div>
					            </div>
											<input type="text" id="owner-remarks" style="margin-top: 10px;" placeholder="<?=$l['REMARKS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['owner'][10]);?>" class="form-control" />
	    					</div>

	    					<div role="tabpanel" class="tab-pane" id="admin">
	    						<div class="row">
					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="admin-firstname" placeholder="<?=$l['FIRSTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][0]);?>" class="form-control" />
					                </div>

					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="admin-lastname" placeholder="<?=$l['LASTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][1]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="admin-company" placeholder="<?=$l['COMPANYO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][2]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="admin-street" placeholder="<?=$l['ADDRESS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][3]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-2 col-xs-12">
					                    <select id="admin-country" class="form-control">
					                    	<?php
$sql = $db->query("SELECT * FROM client_countries ORDER BY alpha2 ASC");
        while ($row2 = $sql->fetch_object()) {
            echo "<option" . ($row2->alpha2 == unserialize($info->reg_info)['admin'][4] ? ' selected="selected"' : '') . ">{$row2->alpha2}</option>";
        }

        ?>
					                    </select>
					                </div>

					                <div class="col-sm-2 col-xs-12">
					                    <input type="text" id="admin-postcode" placeholder="<?=$l['POSTCODE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][5]);?>" class="form-control" />
					                </div>

					                <div class="col-sm-8 col-xs-12">
					                    <input type="text" id="admin-city" placeholder="<?=$l['CITY'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][6]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-4">
					                    <input type="text" id="admin-telephone" placeholder="<?=$l['TELEPHONE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][7]);?>" class="form-control" />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="admin-telefax" placeholder="<?=$l['FAXO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][8]);?>" class="form-control" />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="admin-email" placeholder="<?=$l['EMAILAD'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][9]);?>" class="form-control" />
					                </div>
					            </div>
											<input type="text" id="admin-remarks" style="margin-top: 10px;" placeholder="<?=$l['REMARKS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['admin'][10]);?>" class="form-control" />
	    					</div>

	    					<div role="tabpanel" class="tab-pane" id="tech">
	    						<div class="row">
					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="tech-firstname" placeholder="<?=$l['FIRSTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][0]);?>" class="form-control" />
					                </div>

					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="tech-lastname" placeholder="<?=$l['LASTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][1]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="tech-company" placeholder="<?=$l['COMPANYO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][2]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="tech-street" placeholder="<?=$l['ADDRESS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][3]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-2 col-xs-12">
					                    <select id="tech-country" class="form-control">
					                    	<?php
$sql = $db->query("SELECT * FROM client_countries ORDER BY alpha2 ASC");
        while ($row2 = $sql->fetch_object()) {
            echo "<option" . ($row2->alpha2 == unserialize($info->reg_info)['tech'][4] ? ' selected="selected"' : '') . ">{$row2->alpha2}</option>";
        }

        ?>
					                    </select>
					                </div>

					                <div class="col-sm-2 col-xs-12">
					                    <input type="text" id="tech-postcode" placeholder="<?=$l['POSTCODE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][5]);?>" class="form-control" />
					                </div>

					                <div class="col-sm-8 col-xs-12">
					                    <input type="text" id="tech-city" placeholder="<?=$l['CITY'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][6]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-4">
					                    <input type="text" id="tech-telephone" placeholder="<?=$l['TELEPHONE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][7]);?>" class="form-control" />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="tech-telefax" placeholder="<?=$l['FAXO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][8]);?>" class="form-control" />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="tech-email" placeholder="<?=$l['EMAILAD'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][9]);?>" class="form-control" />
					                </div>
					            </div>
											<input type="text" id="tech-remarks" style="margin-top: 10px;" placeholder="<?=$l['REMARKS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['tech'][10]);?>" class="form-control" />
	    					</div>

	    					<div role="tabpanel" class="tab-pane" id="zone">
	    						<div class="row">
					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="zone-firstname" placeholder="<?=$l['FIRSTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][0]);?>" class="form-control" />
					                </div>

					                <div class="col-sm-6 col-xs-12">
					                    <input type="text" id="zone-lastname" placeholder="<?=$l['LASTNAME'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][1]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="zone-company" placeholder="<?=$l['COMPANYO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][2]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-12">
					                    <input type="text" id="zone-street" placeholder="<?=$l['ADDRESS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][3]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-2 col-xs-12">
					                    <select id="zone-country" class="form-control">
					                    	<?php
$sql = $db->query("SELECT * FROM client_countries ORDER BY alpha2 ASC");
        while ($row2 = $sql->fetch_object()) {
            echo "<option" . ($row2->alpha2 == unserialize($info->reg_info)['zone'][4] ? ' selected="selected"' : '') . ">{$row2->alpha2}</option>";
        }

        ?>
					                    </select>
					                </div>

					                <div class="col-sm-2 col-xs-12">
					                    <input type="text" id="zone-postcode" placeholder="<?=$l['POSTCODE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][5]);?>" class="form-control" />
					                </div>

					                <div class="col-sm-8 col-xs-12">
					                    <input type="text" id="zone-city" placeholder="<?=$l['CITY'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][6]);?>" class="form-control" />
					                </div>
					            </div>
					            <div class="row" style="margin-top: 10px;">
					                <div class="col-sm-4">
					                    <input type="text" id="zone-telephone" placeholder="<?=$l['TELEPHONE'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][7]);?>" class="form-control" />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="zone-telefax" placeholder="<?=$l['FAXO'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][8]);?>" class="form-control" />
					                </div>
					                <div class="col-sm-4">
					                    <input type="text" id="zone-email" placeholder="<?=$l['EMAILAD'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][9]);?>" class="form-control" />
					                </div>
					            </div>
											<input type="text" id="zone-remarks" style="margin-top: 10px;" placeholder="<?=$l['REMARKS'];?>" value="<?=htmlentities(unserialize($info->reg_info)['zone'][10]);?>" class="form-control" />
	    					</div>
	    				</div>

	    				<a href="#" style="margin-top: 10px;" class="btn btn-primary btn-block" id="save_contact"><?=$l['CTSSAVE'];?></a><small><a href="#" id="save_contact_local"><?=$l['SAVELOCAL'];?></a></small>
					</div>
				</div>

				<script>
				function saveContact(force){
					$("#contact-suc").slideUp();
					$("#contact-err").slideUp();

					$("#save_contact_local").hide();
					$("#save_contact").html("<i class='fa fa-spin fa-spinner'></i> <?=$l['CTSBEINGSAVED'];?>");

					var owner = new Array($("[id='owner-firstname']").val(), $("[id='owner-lastname']").val(), $("[id='owner-company']").val(), $("[id='owner-street']").val(), $("[id='owner-country']").val(), $("[id='owner-postcode']").val(), $("[id='owner-city']").val(), $("[id='owner-telephone']").val(), $("[id='owner-telefax']").val(), $("[id='owner-email']").val(), $("[id='owner-remarks']").val());
					var admin = new Array($("[id='admin-firstname']").val(), $("[id='admin-lastname']").val(), $("[id='admin-company']").val(), $("[id='admin-street']").val(), $("[id='admin-country']").val(), $("[id='admin-postcode']").val(), $("[id='admin-city']").val(), $("[id='admin-telephone']").val(), $("[id='admin-telefax']").val(), $("[id='admin-email']").val(), $("[id='admin-remarks']").val());
					var tech = new Array($("[id='tech-firstname']").val(), $("[id='tech-lastname']").val(), $("[id='tech-company']").val(), $("[id='tech-street']").val(), $("[id='tech-country']").val(), $("[id='tech-postcode']").val(), $("[id='tech-city']").val(), $("[id='tech-telephone']").val(), $("[id='tech-telefax']").val(), $("[id='tech-email']").val(), $("[id='tech-remarks']").val());
					var zone = new Array($("[id='zone-firstname']").val(), $("[id='zone-lastname']").val(), $("[id='zone-company']").val(), $("[id='zone-street']").val(), $("[id='zone-country']").val(), $("[id='zone-postcode']").val(), $("[id='zone-city']").val(), $("[id='zone-telephone']").val(), $("[id='zone-telefax']").val(), $("[id='zone-email']").val(), $("[id='zone-remarks']").val());

					$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&save_contact=1" + (force ? '&force=1' : ''), {
						owner: owner,
						admin: admin,
						tech: tech,
						zone: zone,
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						$("#save_contact_local").show();
						$("#save_contact").html("<?=$l['CTSSAVE'];?>");
						if(r == "ok") $("#contact-suc").slideDown();
						else $("#contact-err").html(r).slideDown();
					});
				}

				$("#save_contact").click(function(e) {
					e.preventDefault();
					saveContact(false);
				});

				$("#save_contact_local").click(function(e) {
					e.preventDefault();
					saveContact(true);
				});
				</script>

				<?php if ($info->status == "KK_ERROR" || $info->status == "REG_ERROR") {?><div class="panel panel-warning">
					<div class="panel-heading"><?=$l['ACTRES'];?></div>
					<div class="panel-body" style="text-align: justify;">
						<?=$info->status == "REG_ERROR" ? $l['ACTF1'] : $l['ACTF2'];?> <?=$l['ACTFH'];?>

						<?php if ($info->status == "KK_ERROR") {?>
						<input type="text" id="authcode" style="margin-top: 10px;" value="<?=unserialize($info->reg_info)['transfer'][0];?>" placeholder="<?=$l['AUTHCODE'];?>" class="form-control" />
						<?php }?>

						<a href="#" id="restart" class="btn btn-warning btn-block" style="margin-top: 10px;"><?=$l['ACTRESDO'];?></a>
					</div>
				</div>

				<script>
				$("#restart").click(function(e) {
					e.preventDefault();

					$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>&restart=1", {
						auth: $("#authcode").val(),
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						location.reload();
					});
				});
				</script>
				<?php }?>

				<?php if (method_exists($reg, "deleteDomain")) {?>
				<div class="panel panel-danger">
					<div class="panel-heading"><?=$l['DOMACT'];?></div>
					<div class="panel-body" style="text-align: justify;">
						<div class="alert alert-danger" id="acterr" style="display: none"></div>

						<div class="row">
							<div class="col-sm-4">
								<a href="#" class="btn btn-warning btn-block" id="transit-connected"><?=$l['DOMACT1'];?></a>
							</div>

							<div class="col-sm-4">
								<a href="#" class="btn btn-warning btn-block" id="transit-deconnected"><?=$l['DOMACT2'];?></a>
							</div>

							<div class="col-sm-4">
								<a href="#" class="btn btn-danger btn-block" id="delete-now"><?=$l['DOMACT3'];?></a>
							</div>
						</div>
					</div>
				</div>
				<?php }?>

				<script>
				$("#transit-connected").click(function(e) {
					e.preventDefault();
					if(confirm("<?=$l['DOMCON1'];?>")){
						$("#transit-connected").html('<i class="fa fa-spin fa-spinner"></i> <?=$l['IP'];?>');
						$("#actsuc").slideUp();
						$("#acterr").slideUp();
						$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>", {
							"transit": "1",
							"csrf_token": "<?=CSRF::raw();?>"
						}, function(r) {
							if(r == "ok") location.reload();
							else $("#acterr").html(r).slideDown();
							$("#transit-connected").html("<?=$l['DOMACT1'];?>");
						});
					}
				});

				$("#transit-deconnected").click(function(e) {
					e.preventDefault();
					if(confirm("<?=$l['DOMCON2'];?>")){
						$("#transit-deconnected").html('<i class="fa fa-spin fa-spinner"></i> <?=$l['IP'];?>');
						$("#actsuc").slideUp();
						$("#acterr").slideUp();
						$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>", {
							"detransit": "1",
							"csrf_token": "<?=CSRF::raw();?>"
						}, function(r) {
							if(r == "ok") location.reload();
							else $("#acterr").html(r).slideDown();
							$("#transit-deconnected").html("<?=$l['DOMACT2'];?>");
						});
					}
				});

				$("#delete-now").click(function(e) {
					e.preventDefault();
					if(confirm("<?=$l['DOMCON3'];?>")){
						$("#delete-now").html('<i class="fa fa-spin fa-spinner"></i> <?=$l['IP'];?>');
						$("#actsuc").slideUp();
						$("#acterr").slideUp();
						$.post("?p=domain&d=<?=$_GET['d'];?>&u=<?=$_GET['u'];?>", {
							"delete": "1",
							"csrf_token": "<?=CSRF::raw();?>"
						}, function(r) {
							if(r == "ok") location.reload();
							else $("#acterr").html(r).slideDown();
							$("#delete-now").html("<?=$l['DOMACT3'];?>");
						});
					}
				});
				</script>

				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['INVOICES'];?></div>
					<div class="panel-body" style="text-align: justify;">
						<?php
$sql = $db->query("SELECT amount, invoice FROM invoiceitems WHERE description LIKE '%{$info->domain}%' ORDER BY ID DESC");
        if ($sql->num_rows == 0) {
            echo '<i>' . $l['NOINV'] . '</i>';
        } else {
            echo '<div class="list-group" style="margin-bottom: 0;">';
            while ($r = $sql->fetch_object()) {
                $inv = new Invoice;
                $inv->load($r->invoice);
                ?>
								<a href="?p=invoice&id=<?=$inv->getId();?>" class="list-group-item">
									<?=$cur->infix($nfo->format($r->amount), $cur->getBaseCurrency());?> (<?=$inv->getInvoiceNo();?>)
									<span class="pull-right text-muted small"><em><?=$dfo->format($inv->getDate(), false);?></em>
									</span>
								</a>
								<?php
}
            echo '</div>';
        }
        ?>
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
                echo "<ul>";

                foreach ($files as $file) {
                    echo "<li>";
                    echo "<a href='?p=domain&d=" . urlencode($info->domain) . "&u={$info->user}&download_file=" . urlencode($file) . "' target='_blank'>" . htmlentities($file) . "</a>";
                    echo "<a href='?p=domain&d=" . urlencode($info->domain) . "&u={$info->user}&delete_file=" . urlencode($file) . "' class='pull-right'><i class='fa fa-times'></i></a>";
                    echo "</li>";
                }

                echo "</ul>";
            }
        } else {
            echo "<i>{$l['NOFILES']}</i>";
        }
        ?>
					</div>
				</div>

				<div class="modal fade" id="uploadDomainFile" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<form method="POST" enctype="multipart/form-data" role="form" action="?p=domain&d=<?=urlencode($info->domain);?>&u=<?=$info->user;?>">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title"><?=$l['UPLOADFILES'];?></h4>
								</div>
								<div class="modal-body">
									<div class="form-group" style="margin-bottom: 0;">
										<input type="file" class="form-control" name="upload_files[]" multiple>
									</div>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
									<button type="submit" class="btn btn-primary"><?=$l['UPLOADFILES'];?></button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php }}?>
