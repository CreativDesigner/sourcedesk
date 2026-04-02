<?php
$l = $lang['DNS'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(13)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "dns");} else {
    $tab = isset($_GET['tab']) && in_array($_GET['tab'], array("waiting")) ? $_GET['tab'] : "";

    if (isset($_GET['d']) && DNSHandler::getDriver($_GET['d']) && (($zone = DNSHandler::getDriver($_GET['d'])->getZone($_GET['d'], true)) !== false || $db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string($_GET['d']) . "'")->num_rows > 0)) {
        if (isset($_GET['add'])) {
            $u = new User($info->user);

            $ip = $_REQUEST['ip'] ?? "";
            $ip6 = null;
            $hostname = $_REQUEST['hostname'] ?? "";

            if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = $CFG['DEFAULT_IP'];
            }

            $driver = DNSHandler::getDriver($_GET['d']);
            if ($driver->addZone($_GET['d'], $u->getNS()) !== false) {
                $driver->applyTemplate($_GET['d'], $u->getNS(), $ip, $ip6, $hostname);

                alog("dns", "new_zone", $_GET['d'], $u->get()['ID']);

                $addons->runHook("DnsZoneCreated", [
                    "driver" => $driver,
                    "domain" => $_GET['d'],
                    "client" => $u,
                ]);

                $_SESSION['dnssuc'] = $l['SUC1'];
            } else {
                $_SESSION['dnsfail'] = $l['ERR1'];
            }

            header('Location: ?p=dns&d=' . $_GET['d']);
            exit;
        }

        if (isset($_GET['save_record'])) {
            $name = $_POST['name'];
            $value = $_POST['value'];
            $id = $_POST['pk'];

            if (array_key_exists($id, $zone)) {
                if ($name == "hidden") {
                    DNSHandler::getDriver($_GET['d'])->editHidden($_GET['d'], $id, $value == "Ja" || $value == "1");
                } else {
                    $arr = $zone[$id];
                    if ($name == "subdomain") {
                        $arr[0] = $value;
                    }

                    if ($name == "type") {
                        $arr[1] = $value;
                    }

                    if ($name == "content") {
                        $arr[2] = $value;
                    }

                    if ($name == "ttl") {
                        $arr[3] = $value;
                    }

                    if ($name == "priority") {
                        $arr[4] = $value;
                    }

                    $arr[0] .= $_GET['d'];
                    DNSHandler::getDriver($_GET['d'])->editRecord($_GET['d'], $id, $arr, true);
                    alog("dns", "edit_record", $_GET['d'], $id, $name, $value);
                }
            }

            exit;
        }

        ?>
	<div class="row"><div class="col-md-12"><h1 class="page-header"><?=$_GET['d'];?> <small><?=$l['ZONE'];?></small><?php if (!empty($_GET['u']) && !empty($_GET['d'])) {?><span class="pull-right"><a href="?p=domain&d=<?=urlencode($_GET['d']);?>&u=<?=urlencode($_GET['u']);?>"><i class="fa fa-reply"></i></a></span><?php }?></h1></div></div>

	<div class="row"><div class="col-md-12">
		<?php if (!empty($_SESSION['dnssuc'])) {
            echo '<div class="alert alert-success">' . $_SESSION['dnssuc'] . '</div>';
            $_SESSION['dnssuc'] = "";
        }?>

		<?php if (!empty($_SESSION['dnsfail'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['dnsfail'] . '</div>';
            $_SESSION['dnsfail'] = "";
        }?>

		<?php if ($zone === false) {
            $ip = $CFG['DEFAULT_IP'];

            $uq = "";
            if (!empty($_GET['u'])) {
                $uq = " AND user = " . intval($_GET['u']);
            }

            $sql = $db->query("SELECT reg_info FROM domains WHERE domain = '" . $db->real_escape_string($_GET['d']) . "'$uq ORDER BY `status` IN ('REG_OK', 'KK_OK') DESC LIMIT 1");
            if ($sql->num_rows) {
                $reg_info = unserialize($sql->fetch_object()->reg_info);
                if (is_array($reg_info) && array_key_exists("ns", $reg_info) && count($reg_info["ns"]) == 2) {
                    $ip = $reg_info["ns"][0];
                }
            }

            $hostname = "";
            ?>
		<p style="text-align: justify;"><?=str_replace("%d", htmlentities($_GET['d']), $l['NDNSZ']);?></p>

        <form method="GET" class="form-inline">
            <select name="template" class="form-control">
                <option value="0" disabled="" selected=""><?=$l['CHOOSE_TEMPLATE'];?></option>
                <?php
$sql = $db->query("SELECT * FROM dns_templates ORDER BY ID = 1 DESC, name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
                <option value="<?=$row->ID;?>"><?=htmlentities($row->name);?></option>
                <?php }?>
            </select>

            <input type="text" class="form-control" name="ip" value="<?=$ip;?>" placeholder="<?=$CFG['DEFAULT_IP'];?>">
            <input type="text" class="form-control" name="hostname" value="<?=$hostname;?>" placeholder="s1.example.com">

            <input type="hidden" name="p" value="dns">
            <input type="hidden" name="d" value="<?=htmlentities($_GET['d']);?>">
            <?php if (!empty($_GET['u'])) {?><input type="hidden" name="u" value="<?=htmlentities($_GET['u']);?>"><?php }?>
            <input type="hidden" name="add" value="1">
            <button type="submit" class="btn btn-primary"><?=$l['CDNSZ'];?></button>
        </form>

		<?php } else {

            if (isset($_GET['delete'])) {
                DNSHandler::getDriver($_GET['d'])->removeZone($_GET['d']);
                $_SESSION['dnssuc'] = $l['DELETED'];
                alog("dns", "zone_delete", $_GET['d']);
                header('Location: ?p=dns&d=' . $_GET['d'] . (!empty($_GET['u']) ? '&u=' . urlencode($_GET['u']) : ''));
                exit;
            }

            if (isset($_GET['push']) && method_exists(DNSHandler::getDriver($_GET['d']), "pushToSlave")) {
                DNSHandler::getDriver($_GET['d'])->pushToSlave($_GET['d']);
                alog("dns", "zone_push", $_GET['d']);
                $_SESSION['dnssuc'] = $l['PUSHED'];
                header('Location: ?p=dns&d=' . $_GET['d'] . (!empty($_GET['u']) ? '&u=' . urlencode($_GET['u']) : ''));
                exit;
            }

            if (isset($_GET['rdel']) && DNSHandler::getDriver($_GET['d'])->removeRecord($_GET['d'], $_GET['rdel'], true)) {
                $_SESSION['dnssuc'] = $l['RDELETED'];
                alog("dns", "rr_delete", $_GET['d'], $_GET['rdel']);
                header('Location: ?p=dns&d=' . $_GET['d'] . (!empty($_GET['u']) ? '&u=' . urlencode($_GET['u']) : ''));
                exit;
            }

            if (isset($_POST['subdomain'])) {
                $r = DNSHandler::getDriver($_GET['d'])->addRecord($_GET['d'], array($_POST['subdomain'] . $_GET['d'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['priority']), isset($_POST['hidden']) && $_POST['hidden'] == "yes" ? "1" : "0", true);
                if (!$r) {
                    echo '<div class="alert alert-danger">' . $l['ERR2'] . '</div>';
                } else {
                    $_SESSION['dnssuc'] = $l['SUC2'];
                    alog("dns", "rr_add", $_GET['d'], $_POST['subdomain'], $_POST['type'], $_POST['content']);
                    header('Location: ?p=dns&d=' . $_GET['d'] . (!empty($_GET['u']) ? '&u=' . urlencode($_GET['u']) : ''));
                    exit;
                }
            }
            ?>
		<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item" href="#" data-toggle="modal" data-target="#add_record"><?=$l['AR'];?></a>
					<?php if (method_exists(DNSHandler::getDriver($_GET['d']), "pushToSlave")) {?><a class="list-group-item" href="?p=dns&d=<?=$_GET['d'];?>&push=1<?=!empty($_GET['u']) ? '&u=' . urlencode($_GET['u']) : '';?>"><?=$l['PTS'];?></a><?php }?>
					<a class="list-group-item" href="?p=dns&d=<?=$_GET['d'];?>&delete=1<?=!empty($_GET['u']) ? '&u=' . urlencode($_GET['u']) : '';?>" onclick="return confirm('<?=$l['RD'];?>');"><?=$l['DD'];?></a>
				</div>

				<div class="modal fade" id="add_record" tabindex="-1" role="dialog">
				  <div class="modal-dialog" role="document">
				    <div class="modal-content">
				      <div class="modal-header">
				        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
				        <h4 class="modal-title"><?=$l['AR'];?></h4>
				      </div>
				      <div class="modal-body">
				      	<form method="POST" role="form"><div class="form-group">
				      		<label><?=$l['SD'];?></label>
				      		<input type="text" name="subdomain" class="form-control" placeholder="www" value="<?=isset($_POST['subdomain']) ? $_POST['subdomain'] : "";?>" />
				      	</div>

				      	<div class="form-group">
				      		<label><?=$l['TYPE'];?></label>
				      		<select name="type" class="form-control">
				      			<?php foreach (DNSHandler::getDriver($_GET['d'])->recordTypes(true) as $t) {?>
				      			<option<?php if (isset($_POST['type']) && $_POST['type'] == $t) {
                echo ' selected="selected"';
            }
                ?>><?=$t;?></option>
								<?php }?>
				      		</select>
				      	</div>

				      	<div class="form-group">
				      		<label><?=$l['CONTENT'];?></label>
				      		<input type="text" name="content" class="form-control" placeholder="5.9.7.9" value="<?=isset($_POST['content']) ? $_POST['content'] : "";?>" />
				      	</div>

				      	<div class="form-group">
				      		<label>TTL</label>
				      		<input type="text" name="ttl" class="form-control" placeholder="3600" value="<?=isset($_POST['ttl']) ? $_POST['ttl'] : "3600";?>" />
				      	</div>

				      	<div class="form-group">
				      		<label><?=$l['PRIO'];?></label>
				      		<input type="text" name="priority" class="form-control" placeholder="0" value="<?=isset($_POST['priority']) ? $_POST['priority'] : "0";?>" />
				      	</div>

					    <div class="checkbox" style="margin-bottom: 0;">
						  <label>
						    <input type="checkbox" name="hidden" value="yes"<?=isset($_POST['hidden']) && $_POST['hidden'] == "yes" ? ' checked="checked"' : "";?>> <?=$l['ISHIDDEN'];?>
						  </label>
						</div>
				      </div>
				      <div class="modal-footer">
				        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$lang['GENERAL']['CLOSE'];?></button>
				        <button type="submit" class="btn btn-primary"><?=$l['AR'];?></button>
				      </div></form>
				    </div>
				  </div>
				</div>
			</div>

			<div class="col-md-9">
				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="35%"><?=$l['SD'];?></th>
							<th width="10%"><center><?=$l['TYPE'];?></center></th>
							<th width="35%"><?=$l['CONTENT'];?></th>
							<th width="10%"><center>TTL</center></th>
							<th width="10%"><center><?=$l['PRIO'];?></center></th>
							<th width="10px"><center><?=$l['HIDDEN'];?></center></th>
							<th width="28px"></th>
						</tr>

						<?php
if (count($zone) == 0) {
                ?>
							<tr>
								<td colspan="6"><center><?=$l['NORECORDS'];?></center></td>
							</tr>
							<?php
}
            foreach ($zone as $id => $r) {
                ?>
							<tr>
								<td><?=!empty($r[6]) ? '<div class="label label-info">DynDNS</div> ' : "";?><a href="#" class="text-edit" data-name="subdomain" data-value="<?=htmlentities($r[0]);?>" data-pk="<?=$id;?>"><?=substr($r[0], 0, 45) . (strlen($r[0]) > 45 ? "..." : "");?></a></td>
								<td><center><a href="#" class="type-edit" data-name="type" data-pk="<?=$id;?>" data-value="<?=htmlentities($r[1]);?>"><?=$r[1];?></a></center></td>
								<td><a href="#" class="text-edit" data-name="content" data-pk="<?=$id;?>" data-value="<?=htmlentities($r[2]);?>"><?=substr($r[2], 0, 45) . (strlen($r[2]) > 45 ? "..." : "");?></a></td>
								<td><center><a href="#" class="text-edit" data-name="ttl" data-pk="<?=$id;?>"><?=$r[3];?></a></center></td>
								<td><center><a href="#" class="text-edit" data-name="priority" data-pk="<?=$id;?>"><?=$r[4];?></a></center></td>
								<td><center><a href="#" class="hidden-edit" data-name="hidden" data-value="<?=$r[5] ? "1" : "0";?>" data-pk="<?=$id;?>"><?=$r[5] ? $l['YES'] : $l['NO'];?></a></center></td>
								<td><a href="?p=dns&d=<?=$_GET['d'];?>&rdel=<?=$id;?><?=!empty($_GET['u']) ? '&u=' . urlencode($_GET['u']) : '';?>"><i class="fa fa-times"></i></a></td>
							</tr>
							<?php
}
            ?>
					</table>
				</div>
			</div>

			<?php
$str = "";foreach (DNSHandler::getDriver($_GET['d'])->recordTypes(true) as $t) {
                $str .= "'$t':'$t',";
            }

            $additionalJS .= "</script><link href=\"res/xedit/css/bootstrap-editable.css\" rel=\"stylesheet\">
			<script src=\"res/xedit/js/bootstrap-editable.min.js\"></script><script type=\"text/javascript\">$(document).ready(function(){
				$('.text-edit').editable({
					type: 'text',
					url: '?p=dns&d=" . $_GET['d'] . "&save_record=1',
                    emptytext: '{$l['NE']}',
                    params: function(params) {
						params.csrf_token = \"" . CSRF::raw() . "\";
						return params;
					}
				});

				$('.hidden-edit').editable({
					type: 'select',
					source: {'0': '{$l['NO']}', '1': '{$l['YES']}'},
					url: '?p=dns&d=" . $_GET['d'] . "&save_record=1',
                    emptytext: '{$l['NE']}',
                    params: function(params) {
						params.csrf_token = \"" . CSRF::raw() . "\";
						return params;
					}
				});

				$('.type-edit').editable({
					type: 'select',
					source: {" . rtrim($str, ",") . "},
					url: '?p=dns&d=" . $_GET['d'] . "&save_record=1',
                    emptytext: '{$l['NE']}',
                    params: function(params) {
						params.csrf_token = \"" . CSRF::raw() . "\";
						return params;
					}
				});
			});";?>
		</div>
		<?php }?>
	</div></div>
	<?php
} else {
        if ($tab == "waiting") {
            if (isset($_GET['content'])) {
                ?>
			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tr>
						<th><?=$l['DATE'];?></th>
						<th><?=$l['DOMAIN'];?></th>
						<th><?=$l['CUST'];?></th>
						<th><?=$l['WISH'];?></th>
						<th style="width: 28px;"></th>
					</tr>

					<?php
$sql = $db->query("SELECT * FROM domains WHERE customer_wish > 0 AND status != 'TRANSIT' AND status != 'EXPIRED' AND status != 'DELETED' ORDER BY customer_when ASC");
                if ($sql->num_rows == 0) {?>
					<tr>
						<td colspan="5"><center><?=$l['NOWISHES'];?></center></td>
					</tr>
					<?php } else {while ($row = $sql->fetch_object()) {?>
					<tr>
						<td><?=$dfo->format($row->customer_when);?></td>
						<td><a href="http://www.<?=$row->domain;?>" target="_blank"><?=$row->domain;?></a></td>
						<td><a href="?p=customers&edit=<?=$row->user;?>"><?php $u = new User($row->user, "ID");
                    echo htmlentities($u->get()['name']);?></a></td>
						<td><?php if ($row->customer_wish == 1) {
                        echo '<font color="red">' . $l['DEL'] . '</font>';
                    } else if ($row->customer_wish == 2) {
                        echo '<font color="orange">' . $l['TC'] . '</font>';
                    } else if ($row->customer_wish == 3) {
                        echo '<font color="orange">' . $l['TD'] . '</font>';
                    }
                    ?></td>
						<td><a href="?p=domain&d=<?=$row->domain;?>&u=<?=$row->user;?>"><i class="fa fa-arrow-right"></i></a></td>
					</tr>
					<?php }}?>
				</table>
			</div>
			<?php
exit;
            }
        }
        ?>

	<div class="row"><div class="col-md-12"><h1 class="page-header"><?=$l['TITLE'];?></h1></div></div>

	<div class="row">
		<div class="col-md-3">
			<div class="list-group">
				<a class="list-group-item<?=empty($tab) ? ' active' : "";?>" href="./?p=dns"><?=$l['TITLE'];?></a>
				<a class="list-group-item<?=$tab == "waiting" ? ' active' : "";?>" href="./?p=dns&tab=waiting"><?=$l['WW'];?><?php if (($dw = $db->query("SELECT 1 FROM domains WHERE customer_wish > 0 AND customer_when <= '" . date("Y-m-d H:i:s", strtotime("-24 hours")) . "' AND status != 'TRANSIT' AND status != 'EXPIRED' AND status != 'DELETED'")->num_rows) > 0) {?> <span class="label label-warning"><?=$dw;?></span><?php }?></a>
			</div>
		</div>

		<div class="col-md-9">
			<?php if (empty($tab)) {
            $registrars = [];
            foreach (DomainHandler::getRegistrars() as $short => $obj) {
                if ($obj->isActive()) {
                    $registrars[$short] = $obj->getName();
                }
            }

            $t = new Table("SELECT * FROM domains", [
                "domain" => [
                    "name" => $l['DOMAIN'],
                    "type" => "like",
                ],
                "status" => [
                    "name" => $l['STATUS'],
                    "type" => "select",
                    "options" => $lang['DOMAIN_STATUS'],
                ],
                "registrar" => [
                    "name" => $l['REGISTRAR'],
                    "type" => "select",
                    "options" => $registrars,
                ],
            ]);

            echo $t->getHeader();
            $order = isset($_GET['order']) && in_array($_GET['order'], array("alt_domain", "created", "alt_created", "renew", "alt_renew", "yearly", "alt_yearly")) ? $_GET['order'] : "domain";
            ?>

			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<?php
unset($_GET['order']);
            $orderUrl = "?" . http_build_query($_GET) . "&order=";
            ?>
					<tr>
						<th><a href="<?=$orderUrl;?><?=$order == "domain" ? "alt_" : "";?>domain"><?php if ($order == "domain") {
                echo '<i class="fa fa-sort-up"></i>';
            } else if ($order == "alt_domain") {
                echo '<i class="fa fa-sort-down"></i>';
            } else {
                echo '<i class="fa fa-sort"></i>';
            }
            ?> <?=$l['DOMAIN'];?></a></th>
						<th><?=$l['CUST'];?></th>
						<th><?=$l['STATUS'];?></th>
						<th><a href="<?=$orderUrl;?><?=$order == "created" ? "alt_" : "";?>created"><?php if ($order == "created") {
                echo '<i class="fa fa-sort-up"></i>';
            } else if ($order == "alt_created") {
                echo '<i class="fa fa-sort-down"></i>';
            } else {
                echo '<i class="fa fa-sort"></i>';
            }
            ?> <?=$l['CREATED'];?></a></th>
						<th><a href="<?=$orderUrl;?><?=$order == "renew" ? "alt_" : "";?>renew"><?php if ($order == "renew") {
                echo '<i class="fa fa-sort-up"></i>';
            } else if ($order == "alt_renew") {
                echo '<i class="fa fa-sort-down"></i>';
            } else {
                echo '<i class="fa fa-sort"></i>';
            }
            ?> <?=$l['RENEW'];?></a></th>
						<th><a href="<?=$orderUrl;?><?=$order == "yearly" ? "alt_" : "";?>yearly"><?php if ($order == "yearly") {
                echo '<i class="fa fa-sort-up"></i>';
            } else if ($order == "alt_yearly") {
                echo '<i class="fa fa-sort-down"></i>';
            } else {
                echo '<i class="fa fa-sort"></i>';
            }
            ?> <?=$l['RECURRING'];?></a></th>
					</tr>

					<?php
if ($order == "domain") {
                $orderStr = "domain ASC";
            }

            if ($order == "alt_domain") {
                $orderStr = "domain DESC";
            }

            if ($order == "created") {
                $orderStr = "created ASC";
            }

            if ($order == "alt_created") {
                $orderStr = "created DESC";
            }

            if ($order == "renew") {
                $orderStr = "expiration ASC";
            }

            if ($order == "alt_renew") {
                $orderStr = "expiration DESC";
            }

            if ($order == "yearly") {
                $orderStr = "recurring ASC";
            }

            if ($order == "alt_yearly") {
                $orderStr = "recurring DESC";
            }

            $sql = $t->qry($orderStr);

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

            if ($sql->num_rows == 0) {
                ?>
						<tr><td colspan="6"><center><?=$l['NODOMAINS'];?></center></td></tr>
						<?php
} else {
                while ($row = $sql->fetch_object()) {
                    $u = new User($row->user, "ID");
                    ?>
							<tr>
                <td><a href="?p=domain&d=<?=$row->domain;?>&u=<?=$row->user;?>"><?=$row->domain;?></a><?php if ($row->privacy) {?> <i class="fa fa-shield"></i><?php }?></td>
								<td><a href="?p=customers&edit=<?=$row->user;?>"><?=htmlentities($u->get()['name']);?></a></td>
								<td><span class="label label-<?=$labels[$row->status];?>"><?=$row->payment ? $lang['HOSTING']['WAIT_PAY'] : $status[$row->status];?></span></td>
								<td><?=$dfo->format($row->created, false);?></td>
								<td><?=$dfo->format($row->expiration, false);?></td>
								<td><?=$cur->infix($nfo->format($row->recurring), $cur->getBaseCurrency());?> <?php if (!empty($row->inclusive_id)) {?><span class="label label-<?=$db->query("SELECT 1 FROM client_products WHERE active IN (-1,1) AND ID = " . intval($row->inclusive_id))->num_rows == 1 ? 'success' : 'warning';?>"><?=$l['INCLD'];?></span><?php }?></td>
							</tr>
							<?php
}
            }
            ?>
				</table>
			</div>

			<?php echo $t->getFooter();} else { ?><center class="waiting"><br /><h2><i class="fa fa-spin fa-spinner"></i> <?=$l['PW'];?></h2></center><?php }?>
		</div>
	</div>

	<?php if (!empty($tab)) {?><script>
	$(document).ready(function(){
		$.get("?p=dns&tab=<?=$tab;?>&content=1", function(r){
			$(".col-md-9").html(r);
		});
	});
	</script><?php }?>
<?php }}?>
