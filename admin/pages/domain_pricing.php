<?php
$l = $lang['DOMAIN_PRICING'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(13) || $db->query("SELECT 1 FROM clients WHERE ID = " . intval($_GET['customer']))->num_rows != 1) {require __DIR__ . "/error.php";if (!$ari->check(13)) {
    alog("general", "insufficient_page_rights", "domain_pricing");
}
} else {

    $c = $db->query("SELECT * FROM clients WHERE ID = " . intval($_GET['customer']))->fetch_object();

    if (isset($_GET['save']) && isset($_POST['name']) && isset($_POST['value']) && isset($_POST['pk']) && in_array($_POST['name'], array("register", "transfer", "renew", "trade", "privacy"))) {
        $i = $_POST['value'];
        $i = str_replace(array($cur->getSuffix($cur->getBaseCurrency()), $cur->getPrefix($cur->getBaseCurrency())), "", $i);
        $i = $nfo->phpize($i);

        if ((string) doubleval($i) !== rtrim($i, "0") && (string) doubleval($i) !== rtrim(rtrim($i, "0"), ".") && (string) doubleval($i) !== $i) {
            http_response_code("403");
            die($l['ERR1']);
        }

        $sql = $db->query("SELECT * FROM domain_pricing WHERE tld = '" . $db->real_escape_string($_POST['pk']) . "'");
        if ($sql->num_rows != 1) {
            http_response_code("403");
            die($l['ERR2']);
        }

        $sql = $sql->fetch_object();
        $k = $_POST['pk'];
        $normal = $sql->$k;

        if ($db->query("SELECT * FROM domain_pricing_override WHERE user = " . intval($_GET['customer']) . " AND tld = '" . $db->real_escape_string($k) . "'")->num_rows == 1) {
            $db->query("UPDATE domain_pricing_override SET `{$_POST['name']}` = $i WHERE user = " . intval($_GET['customer']) . " AND tld = '" . $db->real_escape_string($k) . "'");
        } else {
            $register = $_POST['name'] == "register" ? $i : $sql->register;
            $transfer = $_POST['name'] == "transfer" ? $i : $sql->transfer;
            $renew = $_POST['name'] == "renew" ? $i : $sql->renew;
            $trade = $_POST['name'] == "trade" ? $i : $sql->trade;
            $privacy = $_POST['name'] == "privacy" ? $i : $sql->privacy;
            $db->query("INSERT INTO domain_pricing_override (user, tld, register, transfer, renew, trade, privacy) VALUES (" . intval($_GET['customer']) . ", '" . $db->real_escape_string($k) . "', $register, $transfer, $renew, $trade, $privacy)");
        }

        alog("general", "domain_pricing_changed", $_POST['name'], $_POST['value'], $_POST['pk'], intval($_GET['customer']));
        exit;
    }

    if (isset($_GET['content'])) {
        ?>
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th><?=$l['TLD'];?></th>
				<th width="18%"><?=$l['REG'];?></th>
				<th width="18%"><?=$l['KK'];?></th>
				<th width="18%"><?=$l['RENEW'];?></th>
				<th width="18%"><?=$l['TRADE'];?></th>
				<th width="18%"><?=$l['PRIVACY'];?></th>
				<th width="10%"><?=$l['PERIOD'];?></th>
			</tr>

			<?php
$sql = $db->query("SELECT * FROM domain_pricing ORDER BY tld ASC");
        while ($row = $sql->fetch_object()) {
            $register = $row->register;
            $transfer = $row->transfer;
            $renew = $row->renew;
            $trade = $row->trade;
            $privacy = $row->privacy;
            $rc = $tc = $nc = $pc = $wc = false;

            $s = $db->query("SELECT * FROM domain_pricing_override WHERE user = " . intval($_GET['customer']) . " AND tld = '" . $row->tld . "'");
            if ($s->num_rows == 1) {
                $o = $s->fetch_object();
                if ($o->register != $register) {
                    $register = $o->register;
                    $rc = true;
                }
                if ($o->transfer != $transfer) {
                    $transfer = $o->transfer;
                    $tc = true;
                }
                if ($o->renew != $renew) {
                    $renew = $o->renew;
                    $nc = true;
                }
                if ($o->trade != $trade) {
                    $trade = $o->trade;
                    $wc = true;
                }
                if ($o->privacy != $privacy) {
                    $privacy = $o->privacy;
                    $pc = true;
                }
            }
            ?>
			<tr>
				<td>.<?=$row->tld;?></td>
				<td<?=$rc ? ' style="background-color: khaki;"' : "";?>>
					<a href="#" class="price" data-name="register" data-pk="<?=$row->tld;?>"><?=$cur->infix($nfo->format($register, 4), $cur->getBaseCurrency());?></a>
				</td>
				<td<?=$tc ? ' style="background-color: khaki;"' : "";?>>
					<a href="#" class="price" data-name="transfer" data-pk="<?=$row->tld;?>"><?=$cur->infix($nfo->format($transfer, 4), $cur->getBaseCurrency());?></a>
				</td>
				<td<?=$nc ? ' style="background-color: khaki;"' : "";?>>
					<a href="#" class="price" data-name="renew" data-pk="<?=$row->tld;?>"><?=$cur->infix($nfo->format($renew, 4), $cur->getBaseCurrency());?></a>
				</td>
				<td<?=$wc ? ' style="background-color: khaki;"' : "";?>>
					<a href="#" class="price" data-name="trade" data-pk="<?=$row->tld;?>"><?=$cur->infix($nfo->format($trade, 4), $cur->getBaseCurrency());?></a>
				</td>
				<td<?=$pc ? ' style="background-color: khaki;"' : "";?>>
					<a href="#" class="price" data-name="privacy" data-pk="<?=$row->tld;?>"><?=$cur->infix($nfo->format($privacy, 4), $cur->getBaseCurrency());?></a>
				</td>
				<td><?=$row->period;?> <?=$row->period != 1 ? $l['YEARS'] : $l['YEAR'];?></td>
			</tr>
			<?php }?>
		</table>
	</div>
	<?php
exit;
    }
    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE2'];?> <small><?=User::getInstance($c->ID, "ID")->getfName();?></small></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>

<?php
if (isset($_POST['copy']) && isset($_POST['percent']) && is_numeric($_POST['percent'])) {
        if ($_POST['percent'] == "100") {
            $db->query("DELETE FROM domain_pricing_override WHERE user = " . intval($_GET['customer']));
            alog("general", "individual_domain_pricing_deleted", $_GET['customer']);
            echo "<div class='alert alert-success'>{$l['SUC1']}</div>";
        } else {
            $db->query("DELETE FROM domain_pricing_override WHERE user = " . intval($_GET['customer']));

            $sql = $db->query("SELECT * FROM domain_pricing");
            while ($row = $sql->fetch_object()) {
                $db->query("INSERT INTO domain_pricing_override (user, tld, register, transfer, renew) VALUES (" . intval($_GET['customer']) . ", '" . $row->tld . "', " . ($row->register * $_POST['percent'] / 100) . ", " . ($row->transfer * $_POST['percent'] / 100) . ", " . ($row->renew * $_POST['percent'] / 100) . ")");
                alog("general", "percentual_domain_pricing", $_GET['customer'], $_POST['percent']);
            }

            echo "<div class='alert alert-success'>{$l['SUC2']}</div>";
        }
    }

    if (isset($_POST['load_template']) && isset($_POST['template'])) {
        if ($db->query("SELECT 1 FROM domain_pricing_template WHERE name = '" . $db->real_escape_string($_POST['template']) . "'")->num_rows != 1) {
            echo '<div class="alert alert-danger">' . $l['ERR3'] . '</div>';
        } else {
            $p = unserialize($db->query("SELECT pricing FROM domain_pricing_template WHERE name = '" . $db->real_escape_string($_POST['template']) . "'")->fetch_object()->pricing);

            $db->query("DELETE FROM domain_pricing_override WHERE user = " . intval($_GET['customer']));

            foreach ($p as $t => $a) {
                $db->query("INSERT INTO domain_pricing_override (user, tld, register, transfer, renew) VALUES (" . intval($_GET['customer']) . ", '" . $t . "', " . $a[0] . ", " . $a[1] . ", " . $a[2] . ")");
            }

            alog("general", "predefined_domain_pricing", $_GET['customer'], $_POST['template']);

            echo '<div class="alert alert-success">' . $l['SUC3'] . '</div>';
        }
    }

    if (isset($_POST['save_template']) && isset($_POST['template'])) {
        if ($_POST['template'] == "new") {
            if (!isset($_POST['new_template'])) {
                $_POST['new_template'] = "";
            }

            if (empty($_POST['new_template'])) {
                echo '<div class="alert alert-danger">' . $l['ERR4'] . '</div>';
            } else {
                if ($db->query("SELECT 1 FROM domain_pricing_template WHERE name = '" . $db->real_escape_string($_POST['new_template']) . "'")->num_rows > 0) {
                    echo '<div class="alert alert-danger">' . $l['ERR5'] . '</div>';
                } else {
                    $sql = $db->query("SELECT * FROM domain_pricing_override WHERE user = " . intval($_GET['customer']));
                    $pricing = array();
                    while ($row = $sql->fetch_object()) {
                        $pricing[$row->tld] = array($row->register, $row->transfer, $row->renew);
                    }

                    $db->query("INSERT INTO domain_pricing_template (`name`, `pricing`) VALUES ('" . $db->real_escape_string($_POST['new_template']) . "', '" . $db->real_escape_string(serialize($pricing)) . "')");

                    echo '<div class="alert alert-success">' . $l['SUC4'] . '</div>';

                    alog("general", "domain_pricing_template_created", $_POST['new_template'], $db->insert_id);
                }
            }
        } else {
            if ($db->query("SELECT 1 FROM domain_pricing_template WHERE name = '" . $db->real_escape_string($_POST['template']) . "'")->num_rows != 1) {
                echo '<div class="alert alert-danger">' . $l['ERR3'] . '</div>';
            } else {
                $sql = $db->query("SELECT * FROM domain_pricing_override WHERE user = " . intval($_GET['customer']));
                $pricing = array();
                while ($row = $sql->fetch_object()) {
                    $pricing[$row->tld] = array($row->register, $row->transfer, $row->renew);
                }

                $db->query("UPDATE domain_pricing_template SET pricing = '" . $db->real_escape_string(serialize($pricing)) . "' WHERE name = '" . $db->real_escape_string($_POST['template']) . "'");

                alog("general", "domain_pricing_template_edited", $_POST['template']);

                echo '<div class="alert alert-success">' . $l['SUC5'] . '</div>';
            }
        }
    }
    ?>
	<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true" style="margin-bottom: 0;">
	  <div class="panel panel-default">
	    <div class="panel-heading" role="tab" id="headingOne">
	      <h4 class="panel-title">
	        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
	          <?=$l['TEMPLATES'];?>
	        </a>
	      </h4>
	    </div>
	    <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
	      <div class="panel-body"><div class="row">
	        <div class="col-md-5"><form class="form-inline" method="POST">
			  <div class="form-group">
			    <label><?=$l['LOAD'];?>:</label>
			    <select name="template" class="form-control">
			    	<option disabled="disabled" selected="selected"><?=$l['PC'];?></option>
			    	<?php $sql = $db->query("SELECT name FROM domain_pricing_template ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {?>
			    	<option><?=$row->name;?></option>
			    	<?php }?>
			    </select>
					<button type="submit" name="load_template" class="btn btn-primary"><?=$l['LOADNOW'];?></button>
			  </div>
			</form></div>

			<div class="col-md-7"><form class="form-inline" method="POST">
			  <div class="form-group">
			    <label><?=$l['SAVE'];?>:</label>
			    <select name="template" class="form-control" onchange="if(this.value == 'new'){ document.getElementById('new_template').style.display = 'inline'; } else { document.getElementById('new_template').style.display = 'none'; }">
			    	<option disabled="disabled" selected="selected"><?=$l['PC'];?></option>
			    	<option value="new"><?=$l['CNT'];?></option>
			    	<?php $sql = $db->query("SELECT name FROM domain_pricing_template ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {?>
			    	<option><?=$row->name;?></option>
			    	<?php }?>
			    </select>
			    <input type="text" class="form-control" name="new_template" id="new_template" placeholder="Stammkunden-Rabatt" style="display: none;" />
					<button type="submit" name="save_template" class="btn btn-primary"><?=$l['CET'];?></button>
			  </div>
			</form></div>
	      </div></div>
	    </div>
	  </div>
	  <div class="panel panel-default">
	    <div class="panel-heading" role="tab" id="headingTwo">
	      <h4 class="panel-title">
	        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
	          <?=$l['CSP'];?>
	        </a>
	      </h4>
	    </div>
	    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
	      <div class="panel-body">
	        <form method="POST">
			  <div class="input-group" style="margin-bottom: 10px;">
			    <input type="text" class="form-control" name="percent" value="100">
			    <span class="input-group-addon">%</span>
			  </div>
			  <button type="submit" name="copy" class="btn btn-primary btn-block"><?=$l['COPY'];?></button>
			</form>
	      </div>
	    </div>
	  </div>
	</div>

	<center class="waiting"><br /><h2><i class="fa fa-spin fa-spinner"></i> <?=$l['PW'];?></h2></center>

	<script src="res/js/bootstrap.min.js"></script>
	<link href="res/xedit/css/bootstrap-editable.css" rel="stylesheet">
	<script src="res/xedit/js/bootstrap-editable.min.js"></script>
	<script>
	$(document).ready(function(){
		$.get('?p=domain_pricing&customer=<?=$_GET['customer'];?>&content=1', function(r){
			$(".waiting").hide().parent().append(r);

			$('.price').editable({
				type: 'text',
				url: '?p=domain_pricing&customer=<?=$_GET['customer'];?>&save=1',
                params: function(params) {
                    params.csrf_token = "<?=CSRF::raw();?>";
                    return params;
                }
			});
		});
	});
	</script>
<?php }?>