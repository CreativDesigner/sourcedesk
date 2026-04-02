<?php
$l = $lang['DOMAINS'];
title($l['TITLE']);
menu("products");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(24)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "domains");} else {

    $tab = isset($_GET['tab']) && in_array($_GET['tab'], array('top', 'registrars', 'action', 'dns', 'pricing', 'auth2', 'csv')) ? $_GET['tab'] : "";

    if ($tab == "csv") {
        if (!empty($_POST['csv']) && isset($_POST['col']) && is_array($_POST['col']) && !empty($_POST['registrar']) && array_key_exists($_POST['registrar'], DomainHandler::getRegistrars())) {
            if (empty($_POST['row']) || !is_array($_POST['row']) || !count($_POST['row'])) {
                die($l['ERR1']);
            }

            $defined = [];
            $needed = ["tld" => $l['TLDLONG'], "reg" => $l['REGCOST'], "kk" => $l['KKCOST'], "renew" => $l['RENEWCOST']];

            foreach ($_POST['col'] as $i => $v) {
                if (!empty($v) && in_array($i, array_merge(["years", "reg_ek", "kk_ek", "renew_ek"], array_keys($needed)))) {
                    $defined[$i] = $v;
                }

                if (array_key_exists($v, $needed)) {
                    unset($needed[$v]);
                }
            }

            if (count($needed)) {
                die($l['ERR2'] . " " . array_values($needed)[0]);
            }

            if ($defined !== array_unique($defined)) {
                die($l['ERR3']);
            }

            $csv = new ParseCsv\Csv;
            $csv->auto($_POST['csv']);

            function cleanPrice($p)
            {
                $cp = "";
                for ($i = 0; $i < strlen($p); $i++) {
                    $char = substr($p, $i, 1);
                    if (is_numeric($char) || in_array($char, [",", "."])) {
                        $cp .= $char;
                    }
                }

                $cp = str_replace(",", ".", $cp);

                return $cp;
            }

            $updateStmt = $db->prepare("UPDATE domain_pricing SET register = ?, transfer = ?, renew = ?, period = ?, registrar = ?, register_ek = ?, transfer_ek = ?, renew_ek = ? WHERE ID = ?");
            $updateStmt->bind_param("dddisdddi", $reg, $kk, $renew, $years, $_POST['registrar'], $reg_ek, $kk_ek, $renew_ek, $id);

            $insertStmt = $db->prepare("INSERT INTO domain_pricing (tld, register, transfer, renew, period, registrar, register_ek, transfer_ek, renew_ek) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sdddisddd", $tld, $reg, $kk, $renew, $years, $_POST['registrar'], $reg_ek, $kk_ek, $renew_ek);

            foreach ($_POST['row'] as $i) {
                $row = array_values($csv->data[$i]);

                $years = 1;
                $reg_ek = $kk_ek = $renew_ek = 0;
                foreach ($defined as $col => $var) {
                    $$var = $row[$col];
                }

                $tld = trim($tld, ".");
                $reg = cleanPrice($reg);
                $kk = cleanPrice($kk);
                $renew = cleanPrice($renew);

                $sql = $db->query("SELECT ID FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
                if ($sql->num_rows) {
                    $id = $sql->fetch_object()->ID;
                    $updateStmt->execute();
                } else {
                    $insertStmt->execute();
                }
            }

            $updateStmt->close();
            $insertStmt->close();

            die("ok");
        }
    }

    if ($tab == "pricing") {
        if (!empty($_GET['star'])) {
            $_GET['star'] = str_replace("-", ".", $_GET['star']);
            $sql = $db->query("SELECT `top` FROM domain_pricing WHERE tld = '" . $db->real_escape_string($_GET['star']) . "'");
            if ($sql->num_rows != 1) {
                die("0");
            }

            $top = $sql->fetch_object()->top;

            if ($top > 0) {
                $db->query("UPDATE domain_pricing SET `top` = 0 WHERE tld = '" . $db->real_escape_string($_GET['star']) . "'");
                alog("domain", "star_domain", $_GET['star']);
                die("0");
            } else {
                $db->query("UPDATE domain_pricing SET `top` = 1 WHERE tld = '" . $db->real_escape_string($_GET['star']) . "'");
                alog("domain", "unstar_domain", $_GET['star']);
                die("1");
            }
        }

        if (!empty($_GET['delete'])) {
            $_GET['delete'] = str_replace("-", ".", $_GET['delete']);
            $sql = $db->query("SELECT 1 FROM domain_pricing WHERE tld = '" . $db->real_escape_string($_GET['delete']) . "'");
            if ($sql->num_rows != 1) {
                die("0");
            }

            $db->query("DELETE FROM domain_pricing WHERE tld = '" . $db->real_escape_string($_GET['delete']) . "'");
            alog("domain", "delete_tld", $_GET['delete']);
            die("1");
        }

        if (isset($_GET['add'])) {
            foreach (array("tld", "top", "period", "registrar", "lock", "dns_provider") as $k) {
                if (!isset($_POST[$k])) {
                    die($k);
                }

                $$k = $db->real_escape_string($_POST[$k]);
            }

            $tld = ltrim($tld, ".");
            $ex = explode(".", $tld);
            foreach ($ex as $k) {
                if (!ctype_alnum($k)) {
                    die($k);
                }
            }

            if ($db->query("SELECT 1 FROM domain_pricing WHERE tld = '" . $tld . "'")->num_rows > 0) {
                die("exists");
            }

            $top = $top ? 1 : 0;
            if (!is_numeric($period) || $period < 1 || $period > 10) {
                die("period");
            }

            if (!array_key_exists($registrar, DomainHandler::getRegistrarNames())) {
                die("registrar");
            }

            if (!empty($dns_provider) && !array_key_exists($dns_provider, DNSHandler::getDrivers()) && $dns_provider != "-none-") {
                die("dns_provider");
            }

            foreach (array("register", "register_ek", "transfer", "transfer_ek", "renew", "renew_ek", "trade", "privacy") as $k) {
                if (!isset($_POST[$k])) {
                    exit;
                }

                $i = $_POST[$k];
                $i = str_replace(array($cur->getSuffix($cur->getBaseCurrency()), $cur->getPrefix($cur->getBaseCurrency())), "", $i);
                $i = $nfo->phpize($i);

                if ((string) doubleval($i) !== rtrim($i, "0") && (string) doubleval($i) !== rtrim(rtrim($i, "0"), ".") && (string) doubleval($i) !== $i) {
                    die($k);
                }

                $$k = $i;
            }

            $lock = $lock == "true" ? 1 : 0;
            $db->query("INSERT INTO domain_pricing (`tld`, `top`, `register`, `register_ek`, `transfer`, `transfer_ek`, `renew`, `renew_ek`, `period`, `registrar`, `dns_provider`, `domain_lock`, `trade`, `privacy`) VALUES ('$tld', $top, $register, $register_ek, $transfer, $transfer_ek, $renew, $renew_ek, $period, '$registrar', '$dns_provider', $lock, $trade, $privacy)");
            alog("domain", "add_tld", $tld);
            die("ok");
        }

        if (isset($_GET['save']) && isset($_POST['name']) && isset($_POST['value']) && isset($_POST['pk'])) {
            if ($_POST['name'] == "registrar") {
                if (!array_key_exists($_POST['value'], DomainHandler::getRegistrarNames())) {
                    http_response_code("403");
                    die($l['ERR4']);
                }

                $db->query("UPDATE domain_pricing SET `registrar` = '" . $db->real_escape_string($_POST['value']) . "' WHERE ID = " . intval($_POST['pk']));
                alog("domain", "tld_registrar_changed", $_POST['pk'], $_POST['value']);
                exit;
            } else if ($_POST['name'] == "dns_provider") {
                if (!empty($_POST['value']) && !array_key_exists($_POST['value'], DNSHandler::getDrivers()) && $_POST['value'] != "-none-") {
                    http_response_code("403");
                    die($l['ERR4']);
                }

                $db->query("UPDATE domain_pricing SET `dns_provider` = '" . $db->real_escape_string($_POST['value']) . "' WHERE ID = " . intval($_POST['pk']));
                alog("domain", "tld_dns_provider_changed", $_POST['pk'], $_POST['value']);
                exit;
            } else if (in_array($_POST['name'], array("register", "register_ek", "transfer", "transfer_ek", "renew", "renew_ek", "trade", "privacy"))) {
                $i = $_POST['value'];
                $i = str_replace(array($cur->getSuffix($cur->getBaseCurrency()), $cur->getPrefix($cur->getBaseCurrency())), "", $i);
                $i = $nfo->phpize($i);

                if ((string) doubleval($i) !== rtrim($i, "0") && (string) doubleval($i) !== $i) {
                    http_response_code("403");
                    die($l['ERR5']);
                }

                $db->query("UPDATE domain_pricing SET `{$_POST['name']}` = '" . doubleval($i) . "' WHERE ID = " . intval($_POST['pk']));
                alog("domain", "tld_" . $_POST['name'] . "_changed", $_POST['pk'], doubleval($i));
                exit;
            } else if ($_POST['name'] == "domain_lock") {
                $db->query("UPDATE domain_pricing SET `domain_lock` = '" . ($_POST['value'] == "Ja" ? 1 : 0) . "' WHERE ID = " . intval($_POST['pk']));
                alog("domain", "tld_lock_changed", $_POST['pk'], $_POST['value']);
            } else {
                if (!is_numeric($_POST['value']) || $_POST['value'] < 1 || $_POST['value'] > 10) {
                    http_response_code("403");
                    die($l['ERR6']);
                }

                $db->query("UPDATE domain_pricing SET `period` = '" . $db->real_escape_string($_POST['value']) . "' WHERE ID = " . intval($_POST['pk']));
                alog("domain", "tld_period_changed", $_POST['pk'], $_POST['value']);
                exit;
            }
        }

        if (isset($_GET['list'])) {
            $r = DomainHandler::getRegistrarNames();
            $d = DNSHandler::getDrivers();

            foreach ($d as &$v) {
                $v = $v->getName();
            }
            unset($v);
            ?>
		<style>
		#pricing-table {
			table-layout: fixed;
			width: 100% !important;
		}

		#pricing-table td {
			width: auto !important;
			text-overflow: ellipsis;
			overflow: hidden;
		}

		#pricing-table th {
			width: auto !important;
			white-space: normal;
			text-overflow: ellipsis;
			overflow: hidden;
			white-space: nowrap;
		}
		</style>

		<div class="table-responsive">
			<table class="table table-bordered table-striped" id="pricing-table">
				<thead>
				<tr id="row_new">
					<td style="vertical-align: middle; width: 20px !important;"><center><a href="#" class="star_new"><i class="fa fa-star-o" id="star_new"></i></a></center></td>
					<td><input type="text" id="tld_new" class="form-control input-sm" style="width: 100% !important;" placeholder=".de" /></td>
					<td><select id="period_new" class="form-control input-sm" style="width: 100% !important;"><option value="1">1 <?=$l['YEAR'];?></option><option value="2">2 <?=$l['YEARS'];?></option><option value="3">3 <?=$l['YEARS'];?></option><option value="4">4 <?=$l['YEARS'];?></option><option value="5">5 <?=$l['YEARS'];?></option><option value="6">6 <?=$l['YEARS'];?></option><option value="7">7 <?=$l['YEARS'];?></option><option value="8">8 <?=$l['YEARS'];?></option><option value="9">9 <?=$l['YEARS'];?></option><option value="10">10 <?=$l['YEARS'];?></option></select></td>
					<td><input type="text" id="register_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(10, 4), $cur->getBaseCurrency());?>" /></td>
					<td><input type="text" id="register_ek_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(10, 4), $cur->getBaseCurrency());?>" /></td>
					<td><input type="text" id="transfer_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(10, 4), $cur->getBaseCurrency());?>" /></td>
					<td><input type="text" id="transfer_ek_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(10, 4), $cur->getBaseCurrency());?>" /></td>
					<td><input type="text" id="renew_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(10, 4), $cur->getBaseCurrency());?>" /></td>
					<td><input type="text" id="renew_ek_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(10, 4), $cur->getBaseCurrency());?>" /></td>
					<td><input type="text" id="trade_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(0, 4), $cur->getBaseCurrency());?>" value="<?=$cur->infix($nfo->format(0, 4), $cur->getBaseCurrency());?>" /></td>
					<td><input type="text" id="privacy_new" style="width: 100% !important;" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(-1, 4), $cur->getBaseCurrency());?>" value="<?=$cur->infix($nfo->format(-1, 4), $cur->getBaseCurrency());?>" /></td>
					<td><select id="registrar_new" style="width: 100% !important;" class="form-control input-sm"><?php foreach ($r as $v => $n) {?><option value="<?=$v;?>"><?=$n;?></option><?php }?></select></td>
					<td><select id="dns_provider_new" style="width: 100% !important;" class="form-control input-sm"><option value=""><?=$lang['DOMAIN']['DNSPROVSTANDARD'];?></option><option value="-none-"><?=$lang['DOMAIN']['DNSPROVNONE'];?></option><?php foreach ($d as $v => $n) {?><option value="<?=$v;?>"><?=$n;?></option><?php }?></select></td>
					<td style="vertical-align: middle; width: 40px !important;"><center><input type="checkbox" id="lock_new" value="yes" checked="checked" /></center></td>
					<td style="vertical-align: middle; width: 20px !important;"><center><a href="#" class="tld_add"><i class="fa fa-plus"></i></a></center></td>
				</tr>

				<tr>
					<th style="width: 20px !important;"></th>
					<th><?=$l['TLD'];?></th>
					<th><?=$l['YEARS'];?></th>
					<th><?=$l['REG'];?></th>
					<th><?=$l['EK'];?></th>
					<th><?=$l['KK'];?></th>
					<th><?=$l['EK'];?></th>
					<th><?=$l['RENEW'];?></th>
					<th><?=$l['EK'];?></th>
					<th><?=$l['TRADE'];?></th>
					<th><?=$l['PRIVACY'];?></th>
					<th><?=$l['REGISTRAR'];?></th>
					<th><?=$lang['DOMAIN']['DNSPROV'];?></th>
					<th style="width: 40px !important;"><?=$l['LOCK'];?></th>
					<th style="width: 20px !important;"></th>
				</tr>
				</thead>

				<tbody>
				<?php
$sql = $db->query("SELECT * FROM domain_pricing ORDER BY tld ASC");
            while ($row = $sql->fetch_object()) {
                ?>
					<tr id="tld_<?=str_replace('.', '-', $row->tld);?>">
						<td style="width: 20px !important;"><center><a href="#" class="star" id="star_<?=str_replace('.', '-', $row->tld);?>" data-tld="<?=str_replace('.', '-', $row->tld);?>"<?php if ($row->top > 0) {
                    echo ' style="color: #EAC117"';
                }
                ?>><i class="fa fa-star<?php if ($row->top == 0) {
                    echo '-o';
                }
                ?>"></i></a></center></td>
						<td>.<?=$row->tld;?></td>
						<td><a href="#" class="period" data-name="period" data-pk="<?=$row->ID;?>"><?=$row->period;?> <?=$row->period != 1 ? $l['YEARS'] : $l['YEAR'];?></a></td>
						<td><a href="#" class="price" data-name="register" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->register, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="price" data-name="register_ek" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->register_ek, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="price" data-name="transfer" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->transfer, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="price" data-name="transfer_ek" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->transfer_ek, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="price" data-name="renew" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->renew, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="price" data-name="renew_ek" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->renew_ek, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="price" data-name="trade" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->trade, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="price" data-name="privacy" data-pk="<?=$row->ID;?>"><?=$cur->infix($nfo->format($row->privacy, 4), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="registrar" data-name="registrar" data-pk="<?=$row->ID;?>"><?=$r[$row->registrar];?></a></td>
						<td><a href="#" class="dns_provider" data-name="dns_provider" data-pk="<?=$row->ID;?>"><?=empty($row->dns_provider) ? $lang['DOMAIN']['DNSPROVSTANDARD'] : ($row->dns_provider == "-none-" ? $lang['DOMAIN']['DNSPROVNONE'] : $d[$row->dns_provider]);?></a></td>
						<td style="width: 40px !important;"><center><a href="#" class="lock" data-name="domain_lock" data-pk="<?=$row->ID;?>"><?=$row->domain_lock ? $l['YES'] : $l['NO'];?></a></center></td>
						<td style="width: 20px !important;"><center><a href="#" class="delete" data-tld="<?=str_replace('.', '-', $row->tld);?>"><i class="fa fa-times"></i></a></center></td>
					</tr>
					<?php
}
            ?>
				</tbody>
			</table>
		</div>

		<script>
		$(document).ready(function() {
			var table = $('#pricing-table').DataTable({
				"columnDefs": [
					{ "orderable": false, "targets": [0, 13, 14] }
				],
				"order": [],
				paging: false,
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
		<?php
exit;
        }
    } else if ($tab == "auth2") {
        if (!empty($_GET['delete'])) {
            $_GET['delete'] = str_replace("-", ".", $_GET['delete']);
            $sql = $db->query("SELECT 1 FROM domain_auth2 WHERE tld = '" . $db->real_escape_string($_GET['delete']) . "'");
            if ($sql->num_rows != 1) {
                die("0");
            }

            $db->query("DELETE FROM domain_auth2 WHERE tld = '" . $db->real_escape_string($_GET['delete']) . "'");
            alog("domain", "tld_auth2_deleted", $_GET['delete']);
            die("1");
        }

        if (isset($_GET['add'])) {
            foreach (array("tld", "price", "registrar") as $k) {
                if (!isset($_POST[$k])) {
                    die($k);
                }

                $$k = $db->real_escape_string($_POST[$k]);
            }

            $tld = ltrim($tld, ".");
            $ex = explode(".", $tld);
            foreach ($ex as $k) {
                if (!ctype_alnum($k)) {
                    die($k);
                }
            }

            if ($db->query("SELECT 1 FROM domain_auth2 WHERE tld = '" . $tld . "'")->num_rows > 0) {
                die("exists");
            }

            if (!array_key_exists($registrar, DomainHandler::getRegistrarNames())) {
                die("registrar");
            }

            $price = str_replace(array($cur->getSuffix($cur->getBaseCurrency()), $cur->getPrefix($cur->getBaseCurrency())), "", $price);
            $price = $nfo->phpize($price);

            if ((string) doubleval($price) !== rtrim($price, "0") && (string) doubleval($price) !== rtrim(rtrim($price, "0"), ".") && (string) doubleval($price) !== $price) {
                die("price");
            }

            $db->query("INSERT INTO domain_auth2 (`tld`, `price`, `registrar`) VALUES ('$tld', $price, '$registrar')");
            alog("domain", "tld_auth2_added", $tld, $price, $registrar);
            die("ok");
        }

        if (isset($_GET['save']) && isset($_POST['name']) && isset($_POST['value']) && isset($_POST['pk'])) {
            if ($_POST['name'] == "registrar") {
                if (!array_key_exists($_POST['value'], DomainHandler::getRegistrarNames())) {
                    http_response_code("403");
                    die($l['ERR4']);
                }

                $db->query("UPDATE domain_auth2 SET `registrar` = '" . $db->real_escape_string($_POST['value']) . "' WHERE tld = '" . $db->real_escape_string($_POST['pk']) . "'");
                alog("domain", "tld_auth2_regchange", $_POST['pk'], $_POST['value']);
                exit;
            } else if ($_POST['name'] == "price") {
                $i = $_POST['value'];
                $i = str_replace(array($cur->getSuffix($cur->getBaseCurrency()), $cur->getPrefix($cur->getBaseCurrency())), "", $i);
                $i = $nfo->phpize($i);

                if ((string) doubleval($i) !== rtrim($i, "0") && (string) doubleval($i) !== rtrim(rtrim($i, "0"), ".") && (string) doubleval($i) !== $i) {
                    http_response_code("403");
                    die($l['ERR5']);
                }

                alog("domain", "tld_auth2_pricechange", $_POST['pk'], doubleval($i));
                $db->query("UPDATE domain_auth2 SET `price` = '" . doubleval($i) . "' WHERE tld = '" . $db->real_escape_string($_POST['pk']) . "'");
                exit;
            }
        }

        if (isset($_GET['list'])) {
            $r = DomainHandler::getRegistrarNames();
            ?>
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="33%"><?=$l['TLD'];?></th>
					<th width="33%"><?=$l['PRICE'];?></th>
					<th width="34%"><?=$l['REGISTRAR'];?></th>
					<th width="20px"></th>
				</tr>

				<tr id="row_new">
					<td><input type="text" id="tld_new" class="form-control input-sm" placeholder=".de" /></td>
					<td><input type="text" id="price_new" class="form-control input-sm" placeholder="<?=$cur->infix($nfo->format(10), $cur->getBaseCurrency());?>" /></td>
					<td><select id="registrar_new" class="form-control input-sm"><?php foreach ($r as $v => $n) {?><option value="<?=$v;?>"><?=$n;?></option><?php }?></select></td>
					<td style="vertical-align: middle;"><a href="#" class="tld_add"><i class="fa fa-plus"></i></a></td>
				</tr>

				<?php
$sql = $db->query("SELECT * FROM domain_auth2 ORDER BY tld ASC");
            while ($row = $sql->fetch_object()) {
                ?>
					<tr id="tld_<?=str_replace('.', '-', $row->tld);?>">
						<td>.<?=$row->tld;?></td>
						<td><a href="#" class="price" data-name="price" data-pk="<?=$row->tld;?>"><?=$cur->infix($nfo->format($row->price), $cur->getBaseCurrency());?></a></td>
						<td><a href="#" class="registrar" data-name="registrar" data-pk="<?=$row->tld;?>"><?=$r[$row->registrar];?></a></td>
						<td><a href="#" class="delete" data-tld="<?=str_replace('.', '-', $row->tld);?>"><i class="fa fa-times"></i></a></td>
					</tr>
					<?php
}
            ?>
			</table>
		</div>
		<?php
exit;
        }
    } else if ($tab == "top") {
        if (isset($_GET['save']) && isset($_POST['order'])) {
            $i = 1;
            $o = explode(",", $_POST['order']);
            foreach ($o as $t) {
                if (!empty($t)) {
                    $db->query("UPDATE domain_pricing SET top = " . ($i++) . " WHERE tld = '" . $db->real_escape_string(ltrim($t, ".")) . "'");
                }
            }

            alog("domain", "top_tld_order_changed");
            die("ok");
        }
    } else if ($tab == "registrars") {
        if (isset($_GET['activate']) && array_key_exists($_GET['activate'], DomainHandler::getRegistrars())) {
            $db->query("INSERT INTO domain_registrars (`registrar`, `setting`, `value`) VALUES ('" . $db->real_escape_string($_GET['activate']) . "', 'active', '" . encrypt(1) . "')");
            $db->query("UPDATE domain_registrars SET `value` = '" . encrypt(1) . "' WHERE `registrar` = '" . $db->real_escape_string($_GET['activate']) . "' AND `setting` = 'active'");
            alog("domain", "registrar_activated", $_GET['activate']);
        }

        if (isset($_GET['deactivate']) && array_key_exists($_GET['deactivate'], DomainHandler::getRegistrars())) {
            $tlds = $db->query("SELECT COUNT(*) AS num FROM domain_pricing WHERE registrar = '" . $db->real_escape_string($_GET['deactivate']) . "'")->fetch_object()->num;
            $domains = $db->query("SELECT COUNT(*) AS num FROM domains WHERE registrar = '" . $db->real_escape_string($_GET['deactivate']) . "' AND status IN ('REG_OK', 'KK_OK')")->fetch_object()->num;

            if ($tlds + $domains == 0) {
                $db->query("DELETE FROM domain_registrars WHERE `registrar` = '" . $db->real_escape_string($_GET['deactivate']) . "' AND `setting` = 'active'");
                alog("domain", "registrar_deactivated", $_GET['deactivate']);
            }
        }

        if (isset($_POST['save']) && array_key_exists($_POST['save'], DomainHandler::getRegistrars()) && DomainHandler::getRegistrars()[$_POST['save']]->isActive() && is_array($_POST[$_POST['save']])) {
            $r = $db->real_escape_string($_POST['save']);
            $db->query("DELETE FROM domain_registrars WHERE registrar = '$r' AND setting != 'active'");
            foreach ($_POST[$r] as $k => $v) {
                $db->query("INSERT INTO domain_registrars (`registrar`, `setting`, `value`) VALUES ('$r', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string(encrypt($v)) . "')");
            }

            alog("domain", "registrar_changed", $_POST['save']);
        }
    } else if ($tab == "dns") {
        if (isset($_POST['ns1'])) {
            $CFG['NS1'] = $_POST['ns1'];
            $CFG['NS2'] = $_POST['ns2'];
            $CFG['NS3'] = $_POST['ns3'];
            $CFG['NS4'] = $_POST['ns4'];
            $CFG['NS5'] = $_POST['ns5'];
            $CFG['DEFAULT_IP'] = $_POST['default_ip'];

            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($_POST['ns1']) . "' WHERE `key` = 'ns1'");
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($_POST['ns2']) . "' WHERE `key` = 'ns2'");
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($_POST['ns3']) . "' WHERE `key` = 'ns3'");
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($_POST['ns4']) . "' WHERE `key` = 'ns4'");
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($_POST['ns5']) . "' WHERE `key` = 'ns5'");
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($_POST['default_ip']) . "' WHERE `key` = 'default_ip'");

            alog("domain", "ns_changed", $_POST['ns1'], $_POST['ns2'], $_POST['ns3'], $_POST['ns4'], $_POST['ns5'], $_POST['default_ip']);
        }

        if (isset($_POST['dns_driver'])) {
            $CFG['DNS_DRIVER'] = $_POST['dns_driver'];
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($_POST['dns_driver']) . "' WHERE `key` = 'dns_driver'");
            alog("domain", "ns_driver", $_POST['dns_driver']);
        }

        if (isset($_POST['save']) && array_key_exists($_POST['save'], DNSHandler::getDrivers()) && is_array($_POST[$_POST['save']])) {
            $r = $db->real_escape_string($_POST['save']);
            $db->query("DELETE FROM domain_dns_drivers WHERE driver = '$r'");
            foreach ($_POST[$r] as $k => $v) {
                $db->query("INSERT INTO domain_dns_drivers (`driver`, `setting`, `value`) VALUES ('$r', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string(encrypt($v)) . "')");
            }

            alog("domain", "ns_driver_edit", $r);
        }
    }
    ?>
<input style="opacity: 0;position: absolute;">
<input type="password" autocomplete="new-password" style="display: none;">
<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE'];?></h1>
	</div>
</div>

<div class="row">
	<?php if ($tab != "pricing") {?>
	<div class="col-md-3">
		<div class="list-group">
			<?php $i = 0;?>
			<a class="list-group-item<?=empty($tab) ? ' active' : "";?>" href="./?p=domains"><?=$l["T" . ++$i];?></a>
			<a class="list-group-item<?=$tab == "pricing" ? ' active' : "";?>" href="./?p=domains&tab=pricing"><?=$l["T" . ++$i];?></a>
			<a class="list-group-item<?=$tab == "csv" ? ' active' : "";?>" href="./?p=domains&tab=csv"><?=$l["T" . ++$i];?></a>
			<a class="list-group-item<?=$tab == "top" ? " active" : "";?>" href="./?p=domains&tab=top"><?=$l["T" . ++$i];?></a>
			<a class="list-group-item<?=$tab == "auth2" ? " active" : "";?>" href="./?p=domains&tab=auth2"><?=$l["T" . ++$i];?></a>
			<a class="list-group-item<?=$tab == "registrars" ? " active" : "";?>" href="./?p=domains&tab=registrars"><?=$l["T" . ++$i];?></a>
			<a class="list-group-item<?=$tab == "dns" ? " active" : "";?>" href="./?p=domains&tab=dns"><?=$l["T" . ++$i];?></a>
			<a class="list-group-item<?=$tab == "action" ? ' active' : "";?>" href="./?p=domains&tab=action"><?=$l["T" . ++$i];?></a>
		</div>
	</div>
	<?php }?>

	<?php if (empty($tab)) {?>
	<div class="col-md-9">
		<p style="text-align: justify;"><?=$l['WHOISIN'];?></p>

		<?php
if (isset($_POST['wd']) && is_array($_POST['wd']) && count($_POST['wd']) == 10) {
        $CFG['WHOIS_DATA'] = serialize($_POST['wd']);
        $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($CFG['WHOIS_DATA']) . "' WHERE `key` = 'whois_data'");
        alog("domain", "whois_data_changed");
        ?>
		<div class="alert alert-success" style="margin-bottom: 10px;"><?=$l['SUC1'];?></div>
		<?php
}
        $wd = unserialize($CFG['WHOIS_DATA']);
        ?>

		<form method="POST">
		  	<div class="row">
                <div class="col-sm-6 col-xs-12">
                    <input type="text" name="wd[0]" placeholder="<?=$lang['QUOTE']['FN'];?>" value="<?=$wd[0];?>" class="form-control" />
                </div>

                <div class="col-sm-6 col-xs-12">
                    <input type="text" name="wd[1]" placeholder="<?=$lang['QUOTE']['LN'];?>" value="<?=$wd[1];?>" class="form-control" />
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-sm-12">
                    <input type="text" name="wd[2]" placeholder="<?=$lang['QUOTE']['CP'];?>" value="<?=$wd[2];?>" class="form-control" />
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-sm-12">
                    <input type="text" name="wd[3]" placeholder="<?=$l['ADDRESS'];?>" value="<?=$wd[3];?>" class="form-control" />
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-sm-2 col-xs-12">
                    <select name="wd[4]" class="form-control">
                        <?php
$countries = array();
        $sql = $db->query("SELECT alpha2 FROM client_countries WHERE active = 1 ORDER BY ID = " . $CFG['DEFAULT_COUNTRY'] . " DESC, alpha2 ASC");
        while ($row = $sql->fetch_object()) {
            ?>
                        <option value="<?=$row->alpha2;?>"<?php if ($row->alpha2 == $wd[4]) {
                echo ' selected="selected"';
            }
            ?>><?=$row->alpha2;?></option>
                        <?php }?>
                    </select>
                </div>

                <div class="col-sm-2 col-xs-12">
                    <input type="text" name="wd[5]" placeholder="<?=$lang['QUOTE']['PC'];?>" value="<?=$wd[5];?>" class="form-control" />
                </div>

                <div class="col-sm-8 col-xs-12">
                    <input type="text" name="wd[6]" placeholder="<?=$lang['QUOTE']['CT'];?>" value="<?=$wd[6];?>" class="form-control" />
                </div>
            </div>
            <div class="row" style="margin-top: 10px; margin-bottom: 10px;">
                <div class="col-sm-4">
                    <input type="text" name="wd[7]" placeholder="<?=$l['PHONE'];?>" value="<?=$wd[7];?>" class="form-control" />
                </div>
                <div class="col-sm-4">
                    <input type="text" name="wd[8]" placeholder="<?=$l['FAX'];?>" value="<?=$wd[8];?>" class="form-control" />
                </div>
                <div class="col-sm-4">
                    <input type="text" name="wd[9]" placeholder="<?=$l['MAIL'];?>" value="<?=$wd[9];?>" class="form-control" />
                </div>
            </div>

		  	<button type="submit" class="btn btn-primary btn-block"><?=$l['SAVEDATA'];?></button>
		</form>
	</div>
	<?php } else if ($tab == "action") {
        if (isset($_GET['del'])) {
            $db->query("DELETE FROM domain_actions WHERE ID = " . intval($_GET['del']));
        }

        if (!empty($_POST['tld'])) {
            $sql = $db->prepare("INSERT INTO domain_actions (`tld`, `type`, `start`, `end`, `price`) VALUES (?,?,?,?,?)");
            $sql->bind_param("ssssd", $_POST['tld'], $_POST['type'], $start = date("Y-m-d H:i:s", strtotime($_POST['start'])), $end = date("Y-m-d H:i:s", strtotime($_POST['end'])), $price = doubleval($nfo->phpize($_POST['price'])));
            $sql->execute();
            $sql->close();
        }
        ?>
	<div class="col-md-9">
	<form method="POST" class="form-inline">
		<select name="tld" class="form-control">
			<option value="" selected="" disabled=""><?=$l['PCTLD'];?></option>
			<?php
$sql = $db->query("SELECT tld FROM domain_pricing ORDER BY top DESC, tld ASC");
        while ($row = $sql->fetch_object()) {
            ?>
			<option><?=ltrim($row->tld, ".");?></option>
			<?php }?>
		</select>

		<select name="type" class="form-control">
			<option value="" selected="" disabled=""><?=$l['PCTYPE'];?></option>
			<option value="REG"><?=$l['REG'];?></option>
			<option value="KK"><?=$l['KK'];?></option>
			<option value="RENEW"><?=$l['RENEW'];?></option>
		</select>

		<div class="form-group" style="position: relative; display: inline-block;">
			<div class="input-group">
				<span class="input-group-addon"><?=$l['FROM'];?></span>
				<input type="text" name="start" value="<?=$dfo->format(time(), true, true, "");?>" class="form-control datetimepicker">
			</div>
		</div>

		<div class="form-group" style="position: relative; display: inline-block;">
			<div class="input-group">
				<span class="input-group-addon"><?=$l['UNTIL'];?></span>
				<input type="text" name="end" value="<?=$dfo->format(time(), true, true, "");?>" class="form-control datetimepicker">
			</div>
		</div>

		<div class="form-group" style="display: inline-block;">
			<div class="input-group">
				<?=$cur->getPrefix() ? '<span class="input-group-addon">' . $cur->getPrefix() . '</span>' : '';?>
				<input type="text" name="price" class="form-control" placeholder="<?=$nfo->placeholder();?>" style="max-width: 100px;">
				<?=$cur->getSuffix() ? '<span class="input-group-addon">' . $cur->getSuffix() . '</span>' : '';?>
			</div>
		</div>

		<input type="submit" class="btn btn-primary" value="<?=$l['CREATE'];?>">
	</form>

	<br />
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th><?=$l['TLD'];?></th>
				<th><?=$l['FROM'];?></th>
				<th><?=$l['UNTIL'];?></th>
				<th><?=$l['TYPE'];?></th>
				<th><?=$l['PRICE'];?></th>
				<th width="27px"></th>
			</tr>

			<?php
$sql = $db->query("SELECT * FROM domain_actions ORDER BY `start` <= '" . date("Y-m-d H:i:s") . "' AND `end` >= '" . date("Y-m-d H:i:s") . "' DESC, `end` ASC");
        if (!$sql->num_rows) {
            ?>
	<tr>
		<td colspan="6"><center><?=$l['NOACTIONS'];?></center></td>
	</tr>
	<?php
}
        while ($row = $sql->fetch_object()) {
            ?>
<tr>
	<td><?=htmlentities($row->tld);?></td>
	<td><?=$dfo->format($row->start, true, true, "-");?></td>
	<td><?=$dfo->format($row->end, true, true, "-");?></td>
	<td><?=$l[$row->type];?></td>
	<td><?=$cur->infix($nfo->format($row->price), $cur->getBaseCurrency());?></td>
	<td><a href="?p=domains&tab=action&del=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
</tr>
<?php }?>
		</table>
	</div>
	</div>
	<?php } else if ($tab == "pricing") {?>
	<script src="res/js/bootstrap.min.js"></script>
	<link href="res/xedit/css/bootstrap-editable.css" rel="stylesheet">
	<script src="res/xedit/js/bootstrap-editable.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/responsive.bootstrap.min.js"></script>
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.1.0/css/responsive.bootstrap.min.css">

	<div class="col-md-12">
		<a href="?p=domains" class="btn btn-default btn-block">&laquo; <?=$l['BACK'];?></a><br />
		<center class="waiting"><br /><h2><i class="fa fa-spin fa-spinner"></i> <?=$l['PW'];?></h2></center>
	</div>

	<script>
	$(document).ready(function(){
		function onLoad(){
			$.get('?p=domains&tab=pricing&list=1', function(r){
				$(".waiting").hide().parent().append(r);

				$(".star_new").click(function(e){
					e.preventDefault();
					if($(this).find('.fa').hasClass('fa-star')){
						$(this).css('color', '#428BCA').find('.fa').addClass('fa-star-o').removeClass('fa-star');
					} else {
						$(this).css('color', '#EAC117').find('.fa').removeClass('fa-star-o').addClass('fa-star');
					}
				});

				$(".tld_add").click(function(e){
					e.preventDefault();

					$.post('?p=domains&tab=pricing&add=1', {
						top: $(".star_new").find('.fa').hasClass('fa-star') ? 1 : 0,
						tld: $("#tld_new").val(),
						period: $("#period_new").val(),
						register: $("#register_new").val(),
						register_ek: $("#register_ek_new").val(),
						transfer: $("#transfer_new").val(),
						transfer_ek: $("#transfer_ek_new").val(),
						renew: $("#renew_new").val(),
						renew_ek: $("#renew_ek_new").val(),
						trade: $("#trade_new").val(),
						privacy: $("#privacy_new").val(),
						registrar: $("#registrar_new").val(),
						dns_provider: $("#dns_provider_new").val(),
						lock: $("#lock_new").is(":checked"),
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						if(r == "ok"){
							$(".table-responsive").remove();
							$(".waiting").show();
							onLoad();
						} else {
							$("#row_new").css('backgroundColor', 'orangered');
							setTimeout(function() { $("#row_new").css('backgroundColor', ''); }, 500);
						}
					});
				});

				$.fn.editable.defaults.mode = 'popup';
				$('.period').editable({
					type: 'select',
					source: [
						{value: 1, text: "1 <?=$l['YEAR'];?>"},
						{value: 2, text: "2 <?=$l['YEARS'];?>"},
						{value: 3, text: "3 <?=$l['YEARS'];?>"},
						{value: 4, text: "4 <?=$l['YEARS'];?>"},
						{value: 5, text: "5 <?=$l['YEARS'];?>"},
						{value: 6, text: "6 <?=$l['YEARS'];?>"},
						{value: 7, text: "7 <?=$l['YEARS'];?>"},
						{value: 8, text: "8 <?=$l['YEARS'];?>"},
						{value: 9, text: "9 <?=$l['YEARS'];?>"},
						{value: 10, text: "10 <?=$l['YEARS'];?>"},
					],
					url: '?p=domains&tab=pricing&save=1',
					params: function(params) {
						params.csrf_token = "<?=CSRF::raw();?>";
						return params;
					}
				});
				$('.price').editable({
					type: 'text',
					url: '?p=domains&tab=pricing&save=1',
					params: function(params) {
						params.csrf_token = "<?=CSRF::raw();?>";
						return params;
					}
				});
				$('.registrar').editable({
					type: 'select',
					source: [
						<?php foreach (DomainHandler::getRegistrarNames() as $v => $t) {?>
						{value: '<?=$v;?>', text: "<?=$t;?>"},
						<?php }?>
					],
					url: '?p=domains&tab=pricing&save=1',
					params: function(params) {
						params.csrf_token = "<?=CSRF::raw();?>";
						return params;
					}
				});
				$('.dns_provider').editable({
					type: 'select',
					source: [
						{value: '', text: "<?=$lang['DOMAIN']['DNSPROVSTANDARD'];?>"},
						{value: '-none-', text: "<?=$lang['DOMAIN']['DNSPROVNONE'];?>"},
						<?php foreach (DNSHandler::getDrivers() as $v => $t) {?>
						{value: '<?=$v;?>', text: "<?=$t->getName();?>"},
						<?php }?>
					],
					url: '?p=domains&tab=pricing&save=1',
					params: function(params) {
						params.csrf_token = "<?=CSRF::raw();?>";
						return params;
					}
				});
				$('.lock').editable({
					type: 'select',
					source: [
						{value: '<?=$l['YES'];?>', text: "<?=$l['YES'];?>"},
						{value: '<?=$l['NO'];?>', text: "<?=$l['NO'];?>"},
					],
					url: '?p=domains&tab=pricing&save=1',
					params: function(params) {
						params.csrf_token = "<?=CSRF::raw();?>";
						return params;
					}
				});

				$(".star").click(function(e) {
					e.preventDefault();
					var tld = $(this).data('tld');
					$.get('?p=domains&tab=pricing&star=' + tld, function(r) {
						if(r == "1"){
							$("#star_" + tld).css('color', '#EAC117').find('.fa').removeClass('fa-star-o').addClass('fa-star');
						} else {
							$("#star_" + tld).css('color', '#428BCA').find('.fa').addClass('fa-star-o').removeClass('fa-star');
						}
					});
				});

				$(".delete").click(function(e) {
					e.preventDefault();
					var tld = $(this).data('tld');
					$.get('?p=domains&tab=pricing&delete=' + tld, function(r) {
						if(r == "1"){
							$("#tld_" + tld).fadeOut();
						}
					});
				});
			});
		} onLoad();
	});
	</script>
	<?php } else if ($tab == "auth2") {?>
	<script src="res/js/bootstrap.min.js"></script>
	<link href="res/xedit/css/bootstrap-editable.css" rel="stylesheet">
	<script src="res/xedit/js/bootstrap-editable.min.js"></script>

	<div class="col-md-9">
		<p style="text-align: justify;"><?=$l['A2IN'];?></p>

		<center class="waiting"><br /><h2><i class="fa fa-spin fa-spinner"></i> <?=$l['PW'];?></h2></center>
	</div>

	<script>
	$(document).ready(function(){
		function onLoad(){
			$.get('?p=domains&tab=auth2&list=1', function(r){
				$(".waiting").hide().parent().append(r);

				$(".tld_add").click(function(e){
					e.preventDefault();

					$.post('?p=domains&tab=auth2&add=1', {
						tld: $("#tld_new").val(),
						price: $("#price_new").val(),
						registrar: $("#registrar_new").val(),
						csrf_token: "<?=CSRF::raw();?>",
					}, function(r){
						if(r == "ok"){
							$(".table-responsive").remove();
							$(".waiting").show();
							onLoad();
						} else {
							$("#row_new").css('backgroundColor', 'orangered');
							setTimeout(function() { $("#row_new").css('backgroundColor', ''); }, 500);
						}
					});
				});

				$.fn.editable.defaults.mode = 'popup';
				$('.price').editable({
					type: 'text',
					url: '?p=domains&tab=auth2&save=1',
					params: function(params) {
						params.csrf_token = "<?=CSRF::raw();?>";
						return params;
					}
				});
				$('.registrar').editable({
					type: 'select',
					source: [
						<?php foreach (DomainHandler::getRegistrarNames() as $v => $t) {?>
						{value: '<?=$v;?>', text: "<?=$t;?>"},
						<?php }?>
					],
					url: '?p=domains&tab=auth2&save=1',
					params: function(params) {
						params.csrf_token = "<?=CSRF::raw();?>";
						return params;
					}
				});

				$(".delete").click(function(e) {
					e.preventDefault();
					var tld = $(this).data('tld');
					$.get('?p=domains&tab=auth2&delete=' + tld, function(r) {
						if(r == "1"){
							$("#tld_" + tld).fadeOut();
						}
					});
				});
			});
		} onLoad();
	});
	</script>
	<?php } else if ($tab == "top") {?>
	<script src="res/js/plugins/jquery-sortable.js"></script>
	<div class="col-md-9">
		<p style="text-align: justify;">
		<?=$l['TOPIN'];?>
		</p>

		<ul class="list-group">
			<?php $sql = $db->query("SELECT ID, tld FROM domain_pricing WHERE top > 0 ORDER BY top ASC, tld ASC");while ($row = $sql->fetch_object()) {?>
		  	<li class="list-group-item tldorder" style="cursor: move;">.<?=$row->tld;?></li>
		  	<?php }?>
		</ul>

		<input type="button" class="save btn btn-block btn-primary" value="<?=$l['TOPSAVE'];?>" />

		<style>
		body.dragging, body.dragging * {
		  cursor: move !important;
		}

		.dragged {
		  position: absolute;
		  opacity: 0.5;
		  z-index: 2000;
		}
		</style>

		<script>
		$("ul.list-group").sortable();

		$(".save").click(function () {
			var tld = "";
			$(".tldorder").each(function() {
				tld += $(this).html() + ",";
			});

			$.post("?p=domains&tab=top&save=1", {
				order: tld,
				csrf_token: "<?=CSRF::raw();?>",
			}, function(r){
				if(r == "ok") location.reload();
			});
		});
		</script>
	</div>
	<?php } else if ($tab == "registrars") {?>
	<div class="col-md-9">
	<p style="text-align: justify;"><?=$l['REGSIN'];?></p>
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th width="30%"><?=$l['REGISTRAR'];?></th>
				<th width="10%"><?=$l['VERSION'];?></th>
				<th width="10%"><?=$l['TLDS'];?></th>
				<th width="10%"><?=$l['DOMAINS'];?></th>
				<th><?=$l['ACTIONS'];?></th>
			</tr>

			<?php $sum_t = $sum_d = 0;foreach (DomainHandler::getRegistrars() as $short => $obj) {?>
			<tr>
				<td><?=$obj->getName();?><?=$obj->hasAvailibilityStatus() ? ' <span class="label label-primary">' . $l['WHOIS'] . '</span>' : '';?></td>
				<td><?=$obj->getVersion();?></td>
				<td><?=$nfo->format($tlds = $db->query("SELECT COUNT(*) AS num FROM domain_pricing WHERE registrar = '" . $db->real_escape_string($short) . "'")->fetch_object()->num, 0);?></td>
				<td><?=$nfo->format($domains = $db->query("SELECT COUNT(*) AS num FROM domains WHERE registrar = '" . $db->real_escape_string($short) . "' AND status IN ('REG_OK', 'KK_OK')")->fetch_object()->num, 0);?></td>
				<td><?php if (!$obj->isActive()) {?><a href="?p=domains&tab=registrars&activate=<?=$short;?>" class="btn btn-success btn-xs"><?=$l['ACTIVATE'];?></a><?php } else {?><a href="#" data-toggle="modal" data-target="#<?=$short;?>" class="btn btn-default btn-xs"><?=$l['CONFIGURE'];?></a> <a href="?p=domains&tab=registrars&deactivate=<?=$short;?>" class="btn btn-danger btn-xs"<?php if ($tlds + $domains > 0) {?> disabled="disabled"<?php }?>><?=$l['DEACTIVATE'];?></a><?php }?></td>
			</tr>
			<?php $sum_t += $tlds;
        $sum_d += $domains;}?>

			<tr>
				<th colspan="2" style="text-align: right;"><?=$l['SUM'];?></th>
				<th><?=$nfo->format($sum_t, 0);?></th>
				<th colspan="2"><?=$nfo->format($sum_d, 0);?></th>
			</tr>
		</table>
	</div></div>

	<?php foreach (DomainHandler::getRegistrars() as $short => $obj) {if (!$obj->isActive()) {
        continue;
    }
        ?>
	<form method="POST"><div class="modal fade" id="<?=$short;?>" tabindex="-1" role="dialog">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title"><?=$obj->getName();?></h4>
	      </div>
	      <div class="modal-body">
	        <?php foreach ($obj->getSettings() as $k => $i) {if ($i['type'] == "checkbox") {?>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="<?=$short;?>[<?=$k;?>]" value="1"<?=($obj->options->$k ?? false) ? ' checked=""' : '';?>> <?=$i['name'];?>
				</label>
				<?php if ($i['hint'] ?? "") {?><p class="help-block"><?=$i['hint'];?></p><?php }?>
			</div>
			<?php } else {?>
			<div class="form-group">
	        	<label><?=$i['name'];?></label>
	        	<input type="<?=$i['type'];?>" class="form-control" name="<?=$short;?>[<?=$k;?>]" value="<?=isset($obj->options->$k) ? $obj->options->$k : $i['default'];?>" autocomplete="off" />
				<?php if ($i['hint'] ?? "") {?><p class="help-block"><?=$i['hint'];?></p><?php }?>
	        </div>
			<?php }}?>
	      </div>
	      <div class="modal-footer">
	      	<input type="hidden" name="save" value="<?=$short;?>" />
	        <button type="submit" class="btn btn-primary"><?=$l['SAVE'];?></button>
	      </div>
	    </div>
	  </div>
	</div></form>
	<?php }} else if ($tab == "csv") {
        if (!empty($_FILES['csv']) && $_FILES['csv']['size'] && !empty($_POST['registrar']) && array_key_exists($_POST['registrar'], DomainHandler::getRegistrars())) {
            if (in_array($_FILES['csv']['type'], array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv'))) {
                $csvC = file_get_contents($_FILES['csv']['tmp_name']);

                $ex = explode("\n", $csvC);

                foreach ($ex as $line) {
                    if (empty($line) || strpos(trim($line), "#") !== false) {
                        array_shift($ex);
                        continue;
                    }

                    break;
                }
                $ex = array_values($ex);

                $header = $ex[0];

                $ex2 = explode(";", $header);
                $headerCount = count($ex2);
                $header2 = "";
                foreach ($ex2 as $i => $n) {
                    $header2 .= $i . ";";
                }
                $header2 = rtrim($header2, ";");
                $ex[0] = $header2;

                $csvC = implode("\n", array_slice($ex, 1));

                $csv = new ParseCsv\Csv;
                $csv->auto($csvC);
                if (is_array($csv->data) && count($csv->data)) {
                    ?><div class="col-md-9">
					<div id="importingError" class="alert alert-danger"></div>
					<form onsubmit="return false;" id="importForm">
					<input type="hidden" name="registrar" value="<?=htmlentities($_POST['registrar']);?>">
					<input type="hidden" name="csv" value="<?=htmlentities($csvC);?>">
					<div class="table-responsive">
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<td width="20px" style="vertical-align: middle;"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></td>
									<?php for ($i = 0; $i < count($csv->data[0]); $i++) {?>
									<td>
										<select name="col[<?=$i;?>]" class="form-control input-sm">
											<option value=""><?=$l['CSVDNK'];?></option>
											<option value="tld"><?=$l['TLDLONG'];?></option>
											<option value="reg"><?=$l['REGCOST'];?></option>
											<option value="reg_ek"><?=$l['EK'];?>: <?=$l['REGCOST'];?></option>
											<option value="kk"><?=$l['KKCOST'];?></option>
											<option value="kk_ek"><?=$l['EK'];?>: <?=$l['KKCOST'];?></option>
											<option value="renew"><?=$l['RENEWCOST'];?></option>
											<option value="renew_ek"><?=$l['EK'];?>: <?=$l['RENEWCOST'];?></option>
											<option value="years"><?=$l['LIVEYEARS'];?></option>
										</select>
									</td>
									<?php }?>
								</tr>
							</thead>

							<tbody>
								<?php foreach ($csv->data as $index => $row) {?>
								<tr>
									<td width="20px"><input type="checkbox" name="row[]" value="<?=strval(intval($index));?>" class="checkbox" onchange="javascript:toggle();"></td>
									<?php foreach ($row as $col) {?>
									<td><?=htmlentities($col);?></td>
									<?php }?>
								</tr>
								<?php }?>
							</tbody>
						</table>
					</div>

					<?=CSRF::html();?>
					<input type="submit" id="importNow" class="btn btn-primary btn-block" value="<?=$l['CSVIMPORTNOW'];?>">
					<div id="importing"><i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?></div>
					</form></div>

<style>
#importing {
	font-size: 18pt;
	text-align: center;
	display: none;
}

#importingError {
	display: none;
}
</style>

<script>
$(document).ready(function() {
	var doing = 0;

	$("#importNow").click(function(e) {
		e.preventDefault();

		if (doing) {
			return;
		}
		doing = 1;

		$("#importNow").hide();
		$("#importing").show();

		$("#importingError").slideUp(function () {
			$.post("", $("#importForm").serialize(), function(r) {
				if (r == "ok") {
					window.location = "?p=domains&tab=pricing";
				} else {
					$("#importingError").html(r).slideDown(function() {
						doing = 0;
						$("#importNow").show();
						$("#importing").hide();
					});
				}
			});
		});
	});
});
</script>
<?php
} else {
                    ?><div class="col-md-9"><div class="alert alert-danger"><?=$l['CERR1'];?></div></div><?php
}
            } else {
                ?><div class="col-md-9"><div class="alert alert-danger"><?=$l['CERR2'];?></div></div><?php
}
        } else {
            ?>
	<div class="col-md-9">
		<p style="text-align: justify;"><?=$l['CSVINTRO'];?></p>

		<form method="POST" enctype="multipart/form-data">
			<div class="row">
				<div class="col-md-6">
					<input type="file" name="csv" class="form-control">
				</div>
				<div class="col-md-6">
					<select name="registrar" class="form-control">
						<?php foreach (DomainHandler::getRegistrars() as $short => $obj) {?>
						<option value="<?=htmlentities($short);?>"><?=htmlentities($obj->getName());?></option>
						<?php }?>
					</select>
				</div>
			</div>
			<input type="submit" class="btn btn-primary btn-block" value="<?=$l['CSVIMPORTDO'];?>" style="margin-top: 10px;">
		</form>
	</div>
	<?php }} else if ($tab == "dns") {?>
	<div class="col-md-9">
		<p style="text-align: justify;"><?=$l['NSIN'];?></p>

		<form method="POST"><div class="row">
			<div class="col-md-2 col-sm-12">
				<input type="text" name="ns1" value="<?=$CFG['NS1'];?>" placeholder="<?=$lang['DOMAIN']['NAMESERVER'];?> 1" class="form-control" />
			</div>
			<div class="col-md-2 col-sm-12">
				<input type="text" name="ns2" value="<?=$CFG['NS2'];?>" placeholder="<?=$lang['DOMAIN']['NAMESERVER'];?> 2" class="form-control" />
			</div>
			<div class="col-md-2 col-sm-12">
				<input type="text" name="ns3" value="<?=$CFG['NS3'];?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" />
			</div>
			<div class="col-md-2 col-sm-12">
				<input type="text" name="ns4" value="<?=$CFG['NS4'];?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" />
			</div>
			<div class="col-md-2 col-sm-12">
				<input type="text" name="ns5" value="<?=$CFG['NS5'];?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control" />
			</div>
			<div class="col-md-2 col-sm-12">
				<input type="text" name="default_ip" value="<?=$CFG['DEFAULT_IP'];?>" placeholder="<?=$l['DEFAULT_IP'];?>" class="form-control" />
			</div>
		</div><input type="submit" value="<?=$l['SAVE'];?>" style="margin-top: 10px;" class="btn btn-primary btn-block" /></form><hr />

		<?php
if (!empty($_POST['new_dns_template'])) {
        $db->query("INSERT INTO dns_templates (`name`) VALUES ('" . $db->real_escape_string($_POST['new_dns_template']) . "')");

        $tplid = $db->insert_id;

        $db->query("INSERT INTO dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES ($tplid, '', 'A', '%ip%', 3600, 0)");
        $db->query("INSERT INTO dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES ($tplid, 'www', 'A', '%ip%', 3600, 0)");
        $db->query("INSERT INTO dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES ($tplid, '*', 'A', '%ip%', 3600, 0)");
        $db->query("INSERT INTO dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES ($tplid, '', 'MX', '%hostname%', 3600, 10)");
    }

        $dnstpls = [];
        ?>

		<form method="POST" class="form-inline">
			<input type="text" name="new_dns_template" placeholder="<?=$l['DNSTPLP'];?>" class="form-control">
			<input type="submit" class="btn btn-primary" value="<?=$l['DNSTPLA'];?>">
		</form>

		<div class="table-responsive" style="margin-top: 10px;">
			<table class="table table-bordered table-striped">
				<tr>
					<th><?=$l['DNSTPLN'];?></th>
					<th width="200px"><center><?=$l['RECORDS'];?></center></th>
					<th width="30px"></th>
				</tr>

				<?php
if (!empty($_GET['del_tpl']) && ($val = intval($_GET['del_tpl'])) != 1) {
            $db->query("DELETE FROM dns_template_records WHERE template_id = $val");
            $db->query("DELETE FROM dns_templates WHERE ID = $val");
        }

        $sql = $db->query("SELECT * FROM dns_templates ORDER BY ID = 1 DESC, name ASC");
        while ($row = $sql->fetch_object()) {
            if ($row->ID != 1) {
                $dnstpls[$row->ID] = $row->name;
            }
            ?>
				<tr>
					<td><a href="?p=dns_template&id=<?=$row->ID;?>"><?=$row->ID == 1 ? $l['DNSDEF'] : htmlentities($row->name);?></a></td>
					<td><center><?=$db->query("SELECT COUNT(*) c FROM dns_template_records WHERE template_id = {$row->ID}")->fetch_object()->c;?></center></td>
					<td><center><?php if ($row->ID != 1) {?><a href="?p=domains&tab=dns&del_tpl=<?=$row->ID;?>"><i class="fa fa-times"></i></a><?php }?></center></td>
				</tr>
				<?php }?>
			</table>
		</div>

		<hr />

		<form method="POST"><div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="20px"></th>
					<th width="50%"><?=$l['DRIVER'];?></th>
					<th width="20%"><?=$l['VERSION'];?></th>
					<th></th>
				</tr>

				<?php foreach (DNSHandler::getDrivers() as $short => $obj) {?>
				<tr>
					<td><input type="radio" name="dns_driver" value="<?=$short;?>"<?php if ($CFG['DNS_DRIVER'] == $short) {
            echo ' checked="checked"';
        }
            ?> /></td>
					<td><?=$obj->getName();?></td>
					<td><?=$obj->getVersion();?></td>
					<td><a href="#" data-toggle="modal" data-target="#<?=$short;?>" class="btn btn-default btn-xs"><?=$l['CONFIGURE'];?></a></td>
				</tr>
				<?php }?>
			</table>
		</div>

		<input type="submit" value="<?=$l['CHANGEDRIVER'];?>" class="btn btn-warning btn-block" /></form>

		<small style="text-align: justify;"><?=$l['DRIVERHINT'];?></small>
	</div>

	<?php foreach (DNSHandler::getDrivers() as $short => $obj) {?>
	<form method="POST"><div class="modal fade" id="<?=$short;?>" tabindex="-1" role="dialog">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title"><?=$obj->getName();?></h4>
	      </div>
	      <div class="modal-body">
		  	<input style="opacity: 0;position: absolute;">
        	<input type="password" autocomplete="new-password" style="display: none;">
	        <?php foreach ($obj->getSettings() as $k => $i) {?><div class="form-group">
	        	<label><?=$i['name'];?></label>
	        	<?php if ($i['type'] != "hint" && $i['type'] != "textarea") {?><input type="<?=$i['type'];?>" class="form-control" name="<?=$short;?>[<?=$k;?>]" value="<?=htmlentities(isset($obj->options->$k) ? $obj->options->$k : $i['default']);?>" placeholder="<?=isset($i['placeholder']) ? htmlentities($i['placeholder']) : '';?>" autocomplete="off" /><?php if (!empty($i['help'])) {
            echo '<p class="help-block">' . $i['help'] . '</p>';
        }
            ?><?php } else if ($i['type'] == "hint") {echo "<br />" . $i['help'];} else {?>
		<textarea class="form-control" name="<?=$short;?>[<?=$k;?>]" placeholder="<?=isset($i['placeholder']) ? htmlentities($i['placeholder']) : '';?>" autocomplete="off" style="height: 80px; resize: vertical;"><?=htmlentities(isset($obj->options->$k) ? $obj->options->$k : $i['default']);?></textarea>
		<?php }?>
	        </div><?php }?>
		<div class="_different_ns">
	      <div class="checkbox">
			<label>
				<input type="checkbox" class="_different_ns_checkbox" name="<?=$short;?>[_different_ns]" value="1"<?=!empty($obj->options->_different_ns) ? ' checked=""' : '';?>>
				<?=$l['DIFFERENT_NS'];?>
			</label>
		  </div>
		  <div class="_different_ns_config" style="display: none;">
			<?php for ($i = 1; $i <= 5; $i++) {
            $k = "_different_ns" . $i;
            ?>
			<div class="form-group">
				<label><?=$lang['DOMAIN']['NAMESERVER'];?> <?=$i;?></label>
				<input type="text" name="<?=$short;?>[<?=$k;?>]" value="<?=$obj->options->$k ?? "";?>" placeholder="<?=$i < 3 ? ($lang['DOMAIN']['NAMESERVER'] . " " . $i) : $l['OPTIONAL'];?>" class="form-control" />
			</div>
			<?php }?>
		  </div>
		</div>
		<?php if (count($dnstpls)) {?>
		<div class="_different_template">
	      <div class="checkbox">
			<label>
				<input type="checkbox" class="_different_template_checkbox" name="<?=$short;?>[_different_template]" value="1"<?=!empty($obj->options->_different_template) ? ' checked=""' : '';?>>
				<?=$l['DIFFERENT_TEMPLATE'];?>
			</label>
		  </div>
		  <div class="_different_template_config" style="display: none;">
			<div class="form-group">
				<label><?=$l['DNSTPLN'];?></label>
				<select class="form-control" name="<?=$short;?>[_different_template_id]">
					<?php foreach ($dnstpls as $id => $name) {?>
					<option value="<?=$id;?>"<?=($obj->options->_different_template_id ?? 0) == $id ? ' selected=""' : '';?>><?=htmlentities($name);?></option>
					<?php }?>
				</select>
			</div>
		  </div>
		</div>
		<?php }?>
	      </div>
		  <div class="modal-footer">
	      	<input type="hidden" name="save" value="<?=$short;?>" />
	        <button type="submit" class="btn btn-primary"><?=$l['SAVE'];?></button>
	      </div>
	    </div>
	  </div>
	</div></form>
	<?php }}?>
</div>

<script>
function different_ns() {
	$("._different_ns").each(function() {
		if ($(this).find("._different_ns_checkbox").is(":checked")) {
			$(this).find("._different_ns_config").show();
		} else {
			$(this).find("._different_ns_config").hide();
		}
	});
}

$(document).ready(different_ns);
$("._different_ns_checkbox").click(different_ns);

function different_template() {
	$("._different_template").each(function() {
		if ($(this).find("._different_template_checkbox").is(":checked")) {
			$(this).find("._different_template_config").show();
		} else {
			$(this).find("._different_template_config").hide();
		}
	});
}

$(document).ready(different_template);
$("._different_template_checkbox").click(different_template);
</script>
<?php }?>