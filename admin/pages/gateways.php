<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['GATEWAYS'];
title($l['TITLE']);
menu("payments");

if (!$ari->check(41)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "gateways");} else {

    if (isset($_POST['save']) || isset($_POST['save_2'])) {
        foreach ($gateways->get() as $gateway => $obj) {
            if (isset($_POST[$gateway]) && is_array($_POST[$gateway])) {
                $_POST[$gateway]['excl'] = isset($_POST[$gateway]['excl']) ? 1 : 0;
                if (!isset($_POST['save'])) {
                    unset($_POST[$gateway]['excl']);
                }

                if (isset($_POST[$gateway]['system_verification']) && !isset($_POST[$gateway]['allowed_cgroups'])) {
                    $_POST[$gateway]['allowed_cgroups'] = [];
                }

                foreach ($_POST[$gateway] as $k => $v) {
                    if ($k == "percent" || $k == "fix") {
                        $v = doubleval($nfo->phpize($v)) !== false ? doubleval($nfo->phpize($v)) : 0;
                    }

                    if ($k == "allowed_cgroups") {
                        $v = implode(",", $v);
                    }

                    $obj->setOption($k, $v);
                    if ($k == "order") {
                        $changed = true;
                    }

                }

                foreach ($obj->getReqOptions() as $k => $v) {
                    if ($v['type'] != "checkbox") {
                        continue;
                    }

                    if (!isset($_POST[$gateway][$k])) {
                        $obj->setOption($k, "false");
                    }

                }
            }
        }

        alog("gateway", "changed");

        $_SESSION['gatsav'] = true;
        header('Location: ./?p=gateways');
        exit;
    }

    if (array_key_exists("gatsav", $_SESSION) && $_SESSION['gatsav']) {
        $suc = $l['SAVED'];
        $_SESSION['gatsav'] = false;
    }

    if (isset($_GET['activate'])) {
        if (isset($gateways->get()[$_GET['activate']]) && $gateways->get()[$_GET['activate']]->activate()) {
            $suc = $l['ACT'];
            alog("gateway", "activated", $_GET['activate']);
        }
    }

    if (isset($_GET['deactivate'])) {
        if (isset($gateways->get()[$_GET['deactivate']]) && $gateways->get()[$_GET['deactivate']]->deactivate()) {
            $suc = $l['DEACT'];
            alog("gateway", "deactivated", $_GET['deactivate']);
        }
    }
    ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<?php if (isset($suc)) {?><div class="alert alert-success"><?=$suc;?></div><?php }?>
    <div class="table table-responsive">
      <table class="table table-bordered table-striped">
				<tr>
          <th width="30%"><?=$l['PT'];?></th>
          <th width="5%"><?=$lang['SETTINGS']['VERSION'];?></th>
          <th width="10%"><?=$l['FIXED'];?></th>
          <th width="15%"><?=$l['PERCENT'];?></th>
          <th width="5%"><?=$l['EXCL'];?></th>
					<th width="5%"><?=$l['ORDER'];?></th>
					<th width="30%"></th>
				</tr>

        <form method="POST" class="form-inline">
        <?php
$i = 0;
    foreach ($gateways->get() as $gateway => $obj) {
        $i++;
        ?>
				<tr>
					<td><?=$obj->getLang('name');?></td>
					<td><?=$obj->getVersion();?></td>
          <td><?php if (!$obj->isActive() || !$obj->feesAllowed()) {?><center>-</center><?php } else {?><div class="input-group"><?php if (!empty($cur->getPrefix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getPrefix($cur->getBaseCurrency());?></span><?php }?><input type="text" name="<?=$gateway;?>[fix]; ?>" value="<?=!empty($obj->getSettings()['fix']) ? $nfo->format($obj->getSettings()['fix'], 2, true) : 0;?>" class="form-control" style="text-align:center;" /><?php if (!empty($cur->getSuffix($cur->getBaseCurrency()))) {?><span class="input-group-addon"><?=$cur->getSuffix($cur->getBaseCurrency());?></span><?php }?></div><?php }?></td>
          <td><?php if (!$obj->isActive() || !$obj->feesAllowed()) {?><center>-</center><?php } else {?><center><div class="input-group" style="max-width:100px;"><input type="text" name="<?=$gateway;?>[percent]; ?>" value="<?=!empty($obj->getSettings()['percent']) ? $nfo->format($obj->getSettings()['percent'], 2, true) : 0;?>" class="form-control" style="text-align:center;" /><span class="input-group-addon">%</span></div></center><?php }?></td>
          <td><?php if (!$obj->isActive() || !$obj->feesAllowed()) {?><center>-</center><?php } else {?><center><input type="checkbox" name="<?=$gateway;?>[excl]" value="1"<?=$obj->getSettings()['excl'] == 1 ? " checked='checked'" : "";?> /></center><?php }?></td>
          <td><?php if (!$obj->isActive()) {?><center>-</center><?php } else {?><center><input type="text" name="<?=$gateway;?>[order]; ?>" value="<?=!empty($obj->getSettings()['order']) ? $obj->getSettings()['order'] : 0;?>" class="form-control" style="width:50px; text-align:center;" /></center><?php }?></td>
					<td><?php if (!$obj->isActive()) {?><a href="?p=gateways&activate=<?=$gateway;?>" class="btn btn-success btn-xs"><?=$l['AC'];?></a><?php } else {?><a href="#" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#<?=$gateway;?>"><?=$l['ST'];?></a><?php if ($obj->haveLog() && $ari->check(43)) {?>&nbsp;<a href="?p=payment_log&gateway=<?=$gateway;?>" class="btn btn-default btn-xs"><?=$l['LOG'];?></a><?php }?>&nbsp;<a href="?p=gateways&deactivate=<?=$gateway;?>" class="btn btn-warning btn-xs"><?=$l['DE'];?></a>
					<?php }?></td>
				</tr>
        <?php }?>
        </tr>
        <?php if ($i == 0) {?>
        <tr><td colspan="7"><center><?=$l['NT'];?></center></td></tr>
        <?php }?>
			</table>
			</div>
      <center><input type="submit" name="save" class="btn btn-primary" value="<?=$l['SAVE'];?>"></center></form>

<?php foreach ($gateways->get() as $gateway => $obj) {if (!$obj->isActive()) {
        continue;
    }
        ?>
          <div class="modal fade" id="<?=$gateway;?>" tabindex="-1" role="dialog">
           <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$obj->getLang('name');?></h4>
              </div>
              <form role="form" method="POST">
                <div class="modal-body">
                  <?php foreach ($obj->getReqOptions() as $k => $v) {?>
                  <?php if ($v['type'] == "text") {?>
                  <div class="form-group">
                    <label><?=$v['name'];?></label>
                    <input type="text" name="<?=$gateway;?>[<?=$k;?>]" class="form-control" value="<?=$v['value'] ?? $obj->getSettings()[$k];?>" placeholder="<?=!empty($v['placeholder']) ? $v['placeholder'] : "";?>">
                    <?php if (!empty($v['help'])) {?><p class="help-block"><?=$v['help'];?></p><?php }?>
                  </div>
                  <?php } else if ($v['type'] == "select") {?>
                  <div class="form-group">
                    <label><?=$v['name'];?></label>
                    <select name="<?=$gateway;?>[<?=$k;?>]" class="form-control">
                      <?php foreach ($v['options'] as $a => $b) {?>
                      <option value="<?=$a;?>"<?=$obj->getSettings()[$k] == $a ? ' selected="selected"' : "";?>><?=$b;?></option>
                      <?php }?>
                    </select>
                    <?php if (!empty($v['help'])) {?><p class="help-block"><?=$v['help'];?></p><?php }?>
                  </div>
                  <?php } else if ($v['type'] == "checkbox") {?>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="<?=$gateway;?>[<?=$k;?>]" value="true"<?=$obj->getSettings()[$k] === "true" ? " checked='checked'" : "";?>> <?=$v['description'];?>
                      <?php if (!empty($v['help'])) {?><p class="help-block"><?=$v['help'];?></p><?php }?>
                    </label>
                  </div>
                  <?php } else if ($v['type'] == "textarea") {?>
                  <div class="form-group">
                    <label><?=$v['name'];?></label>
                    <textarea name="<?=$gateway;?>[<?=$k;?>]" class="form-control" style="height: 100px; width: 100%; resize: none;" placeholder="<?=!empty($v['placeholder']) ? $v['placeholder'] : "";?>"><?=$obj->getSettings()[$k];?></textarea>
                    <?php if (!empty($v['help'])) {?><p class="help-block"><?=$v['help'];?></p><?php }?>
                  </div>
                  <?php }}?>

                  <?php if (isset($obj->admin_warning)) {
            echo "<b>{$obj->admin_warning}</b>";
        }
        ?>

                  <hr style="margin-bottom: 15px;" />

                  <div class="form-group">
                    <label><?=$l['SCOREQ'];?></label>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="input-group">
                          <input type="text" name="<?=$gateway;?>[system_scoring_show]" class="form-control" placeholder="<?=$l['TS'];?>" value="<?=array_key_exists("system_scoring_show", $obj->getSettings()) ? intval($obj->getSettings()["system_scoring_show"]) : "";?>">
                          <span class="input-group-addon">%</span>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="input-group">
                          <input type="text" name="<?=$gateway;?>[system_scoring_pay]" class="form-control" placeholder="<?=$l['TP'];?>" value="<?=array_key_exists("system_scoring_pay", $obj->getSettings()) ? intval($obj->getSettings()["system_scoring_pay"]) : "";?>">
                          <span class="input-group-addon">%</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <label><?=$l['VERIFICATION'];?></label>
                    <select class="form-control" name="<?=$gateway;?>[system_verification]">
                      <option value="0"><?=$l['V0'];?></option>
                      <option value="1"<?=array_key_exists("system_verification", $obj->getSettings()) && $obj->getSettings()["system_verification"] == "1" ? 'selected=""' : '';?>><?=$l['V1'];?></option>
                      <option value="2"<?=array_key_exists("system_verification", $obj->getSettings()) && $obj->getSettings()["system_verification"] == "2" ? 'selected=""' : '';?>><?=$l['V2'];?></option>
                    </select>
                  </div>

                  <?php
$current = explode(",", array_key_exists("allowed_cgroups", $obj->getSettings()) ? $obj->getSettings()['allowed_cgroups'] : "");
        $current = array_filter($current, function ($v) {return $v !== '';});
        ?>

                  <div class="form-group" style="margin-bottom: 0;">
                    <label><?=$l['CGROUPS'];?></label>
                    <select class="form-control" name="<?=$gateway;?>[allowed_cgroups][]" multiple="">
                      <option value="0"<?=in_array("0", $current) ? ' selected=""' : '';?>><?=$l['NOCGROUP'];?></option>
                      <?php
$cgSql = $db->query("SELECT ID, name FROM client_groups ORDER BY name ASC");
        while ($cgRow = $cgSql->fetch_object()) {
            echo '<option value="' . $cgRow->ID . '"' . (in_array($cgRow->ID, $current) ? ' selected=""' : '') . '>' . htmlentities($cgRow->name) . '</option>';
        }
        ?>
                    </select>
                    <p class="help-block" style="margin-bottom: 0;"><?=$l['CGROUP_HINT'];?></p>
                  </div>
                </div>
              <div class="modal-footer">
                <input type="submit" name="save_2" class="btn btn-primary" value="<?=$l['SAVEMD'];?>">
              </div>
            </form>
          </div>
        </div>
      </div>
        <?php }?>
<?php }?>