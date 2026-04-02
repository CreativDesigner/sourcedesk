<?php
$l = $lang['MONITORING'];
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($l['TITLE']);

if (!$ari->check(66)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "monitoring");} else {

    $services = $provisioning->get(true);

    if (isset($_GET['service_settings']) && $ari->check(67)) {
        $c = $_GET['service_settings'];
        if (!in_array($c, Monitoring::serviceTypes())) {
            die("<div class='alert alert-error'>{$l['ERR1']}</div>");
        }

        $s = $c::getSettings();
        $v = [];

        if (!empty($_GET['id'])) {
            $sql = $db->query("SELECT settings FROM monitoring_services WHERE ID = " . intval($_GET['id']));
            if ($sql->num_rows) {
                $settings = @unserialize($sql->fetch_object()->settings);
                if (is_array($settings)) {
                    $v = $settings;
                }
            }
        }

        foreach ($s as $k) {
            ?>
        <div class="form-group">
            <label><?=$k;?></label>
            <input type="text" name="settings[<?=$k;?>]" class="form-control"<?=array_key_exists($k, $v) ? ' value="' . htmlentities($v[$k]) . '"' : '';?>>
        </div>
        <?php
}

        exit;
    }

    if (!empty($_GET['add']) && $ari->check(67)) {
        title($l['ADDS']);

        if (isset($_POST['name'])) {
            try {
                if (empty($_POST['name'])) {
                    throw new Exception($l['ERR2']);
                }

                $name = $db->real_escape_string($_POST['name']);
                $visible = empty($_POST['visible']) ? "0" : "1";
                $group = intval($_POST['server_group'] ?? 0);

                if (!MonitoringServerGroup::getInstance($group)) {
                    $group = 0;
                }

                if (!$db->query("INSERT INTO monitoring_server (`name`, `visible`, `server_group`) VALUES ('$name', $visible, $group)")) {
                    throw new Exception($l['ERR3']);
                }

                unset($_POST);
                header('Location: ?p=monitoring');
                exit;
            } catch (Exception $ex) {
                $err = '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }
        ?>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$l['ADDS'];?></h1>

            <?=!empty($err) ? $err : "";?>

            <form method="POST">
                <div class="form-group">
                    <label><?=$l['SN'];?></label>
                    <input type="text" name="name" value="<?=isset($_POST['name']) ? htmlentities($_POST['name']) : "";?>" class="form-control">
                </div>

                <div class="form-group">
                    <label><?=$l['GROUP'];?></label>
                    <select name="server_group" class="form-control">
                        <option value="0"><?=$l['NSG'];?></option>
                        <?php
$sql = $db->query("SELECT * FROM monitoring_server_groups ORDER BY `name` ASC");
        while ($row = $sql->fetch_object()) {
            ?>
                            <option value="<?=$row->ID;?>"<?=$row->ID == ($_POST['server_group'] ?? 0) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
                            <?php
}
        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><?=$l['VISIBLE'];?></label><br />
                    <label class="radio-inline">
                        <input type="radio" name="visible" value="1" <?=isset($_POST['visible']) ? ($_POST['visible'] == "1" ? "checked" : "") : "checked";?>> <?=$l['V1'];?>
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="visible" value="0" <?=isset($_POST['visible']) ? ($_POST['visible'] == "0" ? "checked" : "") : "";?>> <?=$l['V2'];?>
                    </label>
                </div>

                <input type="submit" class="btn btn-primary btn-block" value="<?=$l['ADDS'];?>">
            </form>
        </div>
    </div>
    <?php
} else if (!empty($_GET['add_group']) && $ari->check(67)) {
        title($l['ADDSG']);

        if (isset($_POST['name'])) {
            try {
                if (empty($_POST['name'])) {
                    throw new Exception($l['ERR2']);
                }

                $name = $db->real_escape_string($_POST['name']);

                if (!$db->query("INSERT INTO monitoring_server_groups (`name`) VALUES ('$name')")) {
                    throw new Exception($l['ERR3']);
                }

                unset($_POST);
                header('Location: ?p=monitoring');
                exit;
            } catch (Exception $ex) {
                $err = '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }
        ?>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$l['ADDSG'];?></h1>

            <?=!empty($err) ? $err : "";?>

            <form method="POST">
                <div class="form-group">
                    <label><?=$l['SGN'];?></label>
                    <input type="text" name="name" value="<?=isset($_POST['name']) ? htmlentities($_POST['name']) : "";?>" class="form-control">
                </div>

                <input type="submit" class="btn btn-primary btn-block" value="<?=$l['ADDSG'];?>">
            </form>
        </div>
    </div>
    <?php
} else if (!empty($_GET['edit_group']) && is_object($obj = MonitoringServerGroup::getInstance($_GET['edit_group'])) && $ari->check(67)) {
        title($l['EDITSG']);

        if (isset($_POST['name'])) {
            try {
                if (empty($_POST['name'])) {
                    throw new Exception($l['ERR2']);
                }

                $name = $db->real_escape_string($_POST['name']);

                if (!$db->query("UPDATE monitoring_server_groups SET `name` = '$name' WHERE ID = {$obj->ID}")) {
                    throw new Exception($l['ERR3']);
                }

                unset($_POST);
                header('Location: ?p=monitoring');
                exit;
            } catch (Exception $ex) {
                $err = '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }
        ?>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$l['EDITSG'];?></h1>

            <?=!empty($err) ? $err : "";?>

            <form method="POST">
                <div class="form-group">
                    <label><?=$l['SGN'];?></label>
                    <input type="text" name="name" value="<?=isset($_POST['name']) ? htmlentities($_POST['name']) : htmlentities($obj->name);?>" class="form-control">
                </div>

                <input type="submit" class="btn btn-primary btn-block" value="<?=$l['EDITSG'];?>">
            </form>
        </div>
    </div>
    <?php
} else if (!empty($_GET['id']) && is_object($obj = MonitoringServer::getInstance($_GET['id'])) && $ari->check(67)) {
        if (!empty($_GET['delete_panel'])) {
            $db->query("DELETE FROM panels WHERE module = '" . $db->real_escape_string($_GET['delete_panel']) . "' AND server = {$obj->ID}");
            header('Location: ?p=monitoring&id=' . $obj->ID);
            exit;
        }

        if (!empty($_POST['add_panel'])) {
            $sse = $db->real_escape_string($_POST['add_panel']);
            if (!$db->query("SELECT 1 FROM panels WHERE server = {$obj->ID} AND module = '$sse'")->num_rows && array_key_exists($sse, $services)) {
                $db->query("INSERT INTO panels (server, module, `data`) VALUES ({$obj->ID}, '$sse', '')");
            }

            header('Location: ?p=monitoring&id=' . $obj->ID . '&panel=' . urlencode($_POST['add_panel']));
            exit;
        }

        $types = Monitoring::serviceTypes();

        if (!empty($_GET['service']) && is_object($sql = $db->query("SELECT * FROM monitoring_services WHERE server = {$obj->ID} AND ID = " . intval($_GET['service']))) && $sql->num_rows === 1) {
            $info = $sql->fetch_object();
            title("{$obj->name} - {$info->name}");

            if (isset($_POST['name'])) {
                try {
                    if (empty($_POST['name'])) {
                        throw new Exception($l['ERR2']);
                    }

                    $name = $db->real_escape_string($_POST['name']);
                    $internal = !empty($_POST['internal']) ? "1" : "0";
                    $active = !empty($_POST['active']) ? "1" : "0";

                    if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
                        throw new Exception($l['ERR4']);
                    }

                    $type = $db->real_escape_string($_POST['type']);
                    $settings = $db->real_escape_string(serialize(isset($_POST['settings']) && is_array($_POST['settings']) ? $_POST['settings'] : []));

                    if (!$db->query("UPDATE monitoring_services SET `name` = '$name', `type` = '$type', `settings` = '$settings', `internal` = $internal, `active` = $active WHERE ID = " . $info->ID)) {
                        throw new Exception($l['ERR3']);
                    }

                    die("ok");
                } catch (Exception $ex) {
                    die($ex->getMessage());
                }
            }
            ?>
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header"><?=htmlentities($info->name);?> <small><?=htmlentities($obj->name);?></small></h1>

                <div class="alert alert-danger" id="error" style="display: none;"></div>

                <form method="POST" id="add_service">
                    <div class="form-group">
                        <label><?=$l['NAME'];?></label>
                        <input type="text" name="name" class="form-control" value="<?=htmlentities($info->name);?>">
                    </div>

                    <div class="form-group">
                        <label><?=$l['INTERN'];?></label><br />
                        <label class="radio-inline">
                            <input type="radio" name="internal" value="1"<?=$info->internal ? " checked" : "";?>> <?=$l['YES'];?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="internal" value="0"<?=!$info->internal ? " checked" : "";?>> <?=$l['NO'];?>
                        </label>
                    </div>

                    <div class="form-group">
                        <label><?=$l['STATUS'];?></label><br />
                        <label class="radio-inline">
                            <input type="radio" name="active" value="1"<?=$info->active ? " checked" : "";?>> <?=$l['S1'];?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="active" value="0"<?=!$info->active ? " checked" : "";?>> <?=$l['S2'];?>
                        </label>
                    </div>

                    <div class="form-group">
                        <label><?=$l['STYPE'];?></label>
                        <select name="type" class="form-control">
                            <option value="" selected="" disabled=""><?=$l['PC'];?></option>
                            <?php
foreach ($types as $c) {
                $ch = $info->type == $c ? " selected" : "";
                echo "<option value='$c'$ch>{$c::getName()}</option>";
            }
            ?>
                        </select>
                    </div>

                    <div id="service_settings"></div>

                    <div class="row">
                        <div class="col-md-6">
                            <input type="submit" class="btn btn-primary btn-block" disabled="" value="<?=$l['SSAVE'];?>">
                        </div>

                        <div class="col-md-6">
                            <a href="?p=monitoring&id=<?=$obj->ID;?>&del_service=<?=$info->ID;?>" class="btn btn-danger btn-block"><?=$l['SDEL'];?></a>
                        </div>
                </form>
            </div>
        </div>

        <script>
        function servset() {
            $("#service_settings").html("<div class=\"alert alert-info\"><i class=\"fa fa-spinner fa-pulse\"></i> <?=$l['PW'];?></div>");

            $.get("?p=monitoring&service_settings=" + $("[name=type]").val() + "&id=<?=$info->ID;?>", function(r) {
                $("#service_settings").html(r);
                $(".btn-primary.btn-block").attr("disabled", false);
            });
        }

        servset();
        $("[name=type]").change(servset);

        $("#add_service").submit(function(e) {
            e.preventDefault();
            $("#error").slideUp();
            $(".btn-primary.btn-block").attr("disabled", true);

            $.post("", $(this).serialize(), function(r) {
                if (r == "ok") {
                    window.location = "?p=monitoring&id=<?=$obj->ID;?>";
                }
                 else {
                     $("#error").html(r).slideDown();
                     $(".btn-primary.btn-block").attr("disabled", false);
                 }
            });
        });
        </script>
        <?php
} else if (!empty($_GET['panel']) && array_key_exists($_GET['panel'], $services) && $db->query("SELECT 1 FROM panels WHERE server = {$obj->ID} AND module = '" . $db->real_escape_string($_GET['panel']) . "'")->num_rows && $ari->check(67)) {
            if (isset($_POST['settings'])) {
                $settings = $db->real_escape_string(encrypt(serialize($_POST['settings'])));
                $module = $db->real_escape_string($_GET['panel']);
                $db->query("INSERT INTO panels (`server`, `module`, `data`) VALUES ({$obj->ID}, '$module', '$settings') ON DUPLICATE KEY UPDATE `data` = '$settings'");
            }
            ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=htmlentities($services[$_GET['panel']]->getName());?> <small><?=htmlentities($obj->name);?><a href="?p=monitoring&id=<?=$obj->ID;?>" class="pull-right"><i class="fa fa-reply"></i></a></small></h1>

                    <script>
                    mgmt = 1;
                    </script>

                    <div id="mgmt_config" style="display: none;">
                        <?=$services[$_GET['panel']]->Config($obj->ID / -1);?>

                        <a href="#" id="mgmt_save" class="btn btn-primary btn-block"><?=$l['SAVE'];?></a>
                    </div>

                    <script>
                    $(document).ready(function() {
                        $("[mgmt=0]").remove();
                        $("#mgmt_config").show();

                        $("#mgmt_save").click(function(e) {
                            e.preventDefault();

                            if ($(this).html() != "<?=$l['SAVE'];?>") {
                                return;
                            }

                            $("[mgmt=1]").prop("disabled", true);
                            $(this).html('<i class="fa fa-spinner fa-spin"></i> <?=$l['PW'];?>');

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

                            $.post("", {
                                "csrf_token": "<?=CSRF::raw();?>",
                                "settings": prov,
                            }, function() {
                                window.location = "?p=monitoring&id=<?=$obj->ID;?>";
                            });
                        });
                    });
                    </script>
                </div>
            </div>
            <?php
} else if (!empty($_GET['add_service']) && $ari->check(67)) {
            if (isset($_POST['name'])) {
                try {
                    if (empty($_POST['name'])) {
                        throw new Exception($l['ERR5']);
                    }

                    $name = $db->real_escape_string($_POST['name']);
                    $internal = !empty($_POST['internal']) ? "1" : "0";
                    $active = !empty($_POST['active']) ? "1" : "0";

                    if (empty($_POST['type']) || !in_array($_POST['type'], $types)) {
                        throw new Exception($l['ERR6']);
                    }

                    $type = $db->real_escape_string($_POST['type']);
                    $settings = $db->real_escape_string(serialize(isset($_POST['settings']) && is_array($_POST['settings']) ? $_POST['settings'] : []));

                    if (!$db->query("INSERT INTO monitoring_services (`name`, `type`, `settings`, `internal`, `active`, `server`) VALUES ('$name', '$type', '$settings', $internal, $active, {$obj->ID})")) {
                        throw new Exception($l['ERR3']);
                    }

                    die("ok");
                } catch (Exception $ex) {
                    die($ex->getMessage());
                }
            }

            title($l['STITLE']);
            ?>
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header"><?=$l['STITLE'];?> <small><?=htmlentities($obj->name);?></small></h1>

                <div class="alert alert-danger" id="error" style="display: none;"></div>

                <form method="POST" id="add_service">
                    <div class="form-group">
                        <label><?=$l['NAME'];?></label>
                        <input type="text" name="name" class="form-control">
                    </div>

                    <div class="form-group">
                        <label><?=$l['V2'];?></label><br />
                        <label class="radio-inline">
                            <input type="radio" name="internal" value="1"> <?=$l['YES'];?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="internal" value="0" checked=""> <?=$l['NO'];?>
                        </label>
                    </div>

                    <div class="form-group">
                        <label><?=$l['STATUS'];?></label><br />
                        <label class="radio-inline">
                            <input type="radio" name="active" value="1" checked=""> <?=$l['S1'];?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="active" value="0"> <?=$l['S2'];?>
                        </label>
                    </div>

                    <div class="form-group">
                        <label><?=$l['STYPE'];?></label>
                        <select name="type" class="form-control">
                            <option value="" selected="" disabled=""><?=$l['PC'];?></option>
                            <?php
foreach ($types as $c) {
                echo "<option value='$c'>{$c::getName()}</option>";
            }
            ?>
                        </select>
                    </div>

                    <div id="service_settings">
                        <div class="alert alert-info"><?=$l['PCST'];?></div>
                    </div>

                    <input type="submit" class="btn btn-primary btn-block" disabled="" value="<?=$l['ADDSERVICE'];?>">
                </form>
            </div>
        </div>

        <script>
        $("[name=type]").change(function() {
            $("#service_settings").html("<div class=\"alert alert-info\"><i class=\"fa fa-spinner fa-pulse\"></i> <?=$l['PW'];?></div>");

            $.get("?p=monitoring&service_settings=" + $(this).val(), function(r) {
                $("#service_settings").html(r);
                $(".btn-primary.btn-block").attr("disabled", false);
            });
        });

        $("#add_service").submit(function(e) {
            e.preventDefault();
            $("#error").slideUp();
            $(".btn-primary.btn-block").attr("disabled", true);

            $.post("", $(this).serialize(), function(r) {
                if (r == "ok") {
                    window.location = "?p=monitoring&id=<?=$obj->ID;?>";
                } else {
                     $("#error").html(r).slideDown();
                     $(".btn-primary.btn-block").attr("disabled", false);
                 }
            });
        });
        </script>
        <?php
} else {
            if (isset($_POST['name']) && $ari->check(67)) {
                if (!empty($_POST['name'])) {
                    $obj->name = $_POST['name'];
                }

                $obj->server_group = intval($_POST['server_group'] ?? 0);

                if (!MonitoringServerGroup::getInstance($obj->server_group)) {
                    $obj->server_group = 0;
                }

                $obj->visible = !empty($_POST['visible']) ? "1" : "0";
                $obj->save();

                unset($_POST);
            }

            title($obj->name);

            if (!empty($_GET['del_service']) && $ari->check(67)) {
                $db->query("DELETE FROM monitoring_services WHERE ID = " . intval($_GET['del_service']));
            }
            ?>
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header"><?=htmlentities($obj->name);?></h1>

                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><?=$l['SERVICES'];?>
                                <?php if ($ari->check(67)) {?>
                                <form method="POST" class="pull-right" id="add_panel_form">
                                    <select name="add_panel" class="form-control input-sm" style="margin-top: -5px;">
                                        <option value="" selected="" disabled="">- <?=$l['ADD_SERVICE'];?> -</option>
                                        <?php
ksort($services);
                foreach ($provisioning->get() as $ss => $so) {
                    if (!$so->getServerMgmt()) {
                        continue;
                    }

                    $sse = $db->real_escape_string($ss);
                    if ($db->query("SELECT 1 FROM panels WHERE server = {$obj->ID} AND module = '$sse'")->num_rows) {
                        continue;
                    }
                    ?>
                                        <option value="<?=htmlentities($ss);?>"><?=htmlentities($so->getName());?></option>
                                        <?php }?>
                                    </select>
                                </form>

                                <script>
                                $("[name=add_panel]").change(function() {
                                    $("#add_panel_form").submit();
                                });
                                </script>
                            <?php }?></div>
                            <div class="panel-body">
                                <?php
$sql = $db->query("SELECT module FROM panels WHERE server = {$obj->ID}");
            if (!$sql->num_rows) {
                echo $l['NO_SERVICES'];
            } else {
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                        <tr>
                            <th><?=$l['SERVICE'];?></th>
                            <th style="width: 1px;"></th>
                        </tr>

                        <?php while ($row = $sql->fetch_object()) {?>
                        <tr>
                            <td><?=htmlentities(array_key_exists($row->module, $services) ? $services[$row->module]->getName() : $row->module);?></td>
                            <td style="white-space: nowrap;"><a href="?p=monitoring&id=<?=$obj->ID;?>&panel=<?=urlencode($row->module);?>" class="btn btn-default btn-xs"><?=$l['CONFIGURE'];?></a> <a href="?p=monitoring&id=<?=$obj->ID;?>&delete_panel=<?=urlencode($row->module);?>" class="btn btn-danger btn-xs"><?=$l['DELETE'];?></a></td>
                        </tr>
                        <?php }?>
                    </table>
                </div>
                <?php
}
            ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><?=$l['MONITORING'];?><?php if ($ari->check(67)) {?><a href="?p=monitoring&id=<?=$obj->ID;?>&add_service=1" class="pull-right"><i class="fa fa-plus-circle"></i></a><?php }?></div>
                            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                        <tr>
                            <th width="100px"><center><?=$l['STATUS'];?></center></th>
                            <th><?=$l['NAME'];?></th>
                            <th width="100px"><center><?=$l['TYPE'];?></center></th>
                            <th><?=$l['LASTCHECK'];?></th>
                            <th><?=$l['ERROR'];?></th>
                            <th width="100px"><center><?=$l['V1'];?></center></th>
                        </tr>

                        <?php
$sql = $db->query("SELECT * FROM monitoring_services WHERE server = {$obj->ID} ORDER BY active DESC, name ASC");
            while ($row = $sql->fetch_object()) {
                ?>
                            <tr>
                                <td>
                                    <center><?=$obj->getFormattedStatus(true, $row->ID);?></center>
                                </td>
                                <td>
                                    <?php if ($ari->check(67)) {?><a href="?p=monitoring&id=<?=$row->server;?>&service=<?=$row->ID;?>"><?php }?>
                                    <?=htmlentities($row->name);?>
                                    <?php if ($ari->check(67)) {?></a><?php }?>
                                </td>
                                <td><center>
                                    <?php
$type = $row->type;
                if (in_array($type, $types)) {
                    echo htmlentities($type::getName());
                } else {
                    echo "<i>{$l['UNKNOWN']}</i>";
                }
                ?>
                                </center></td>
                                <td>
                                    <?=$row->last_called ? $dfo->format($row->last_called) : "<i>{$l['NEVER']}</i>";?>
                                </td>
                                <td>
                                    <?=$row->last_result && $row->last_result !== "1" ? htmlentities(substr($row->last_result, 0, 40)) : "<i>{$l['NOFAULT']}</i>";?>
                                </td>
                                <td>
                                    <center><?=!$row->internal ? $l['YES'] : $l['NO'];?></center>
                                </td>
                            </tr>
                            <?php
}

            if (!$sql->num_rows) {
                echo '<tr><td colspan="6"><center>' . $l['NOSVS'] . '</center></td></tr>';
            }
            ?>
                    </table>
                </div>
                </div></div>
</div></div>

            <div class="panel panel-default">
                <div class="panel-heading"><?=$l['UPDATES'];?></div>
                <div class="panel-body">
                    <?php if (empty($obj->ssh_host)) {?>
                        <p><?=$l['NO_CONNECTION'];?></p>

                        <p><?=$l['SHOULD_RUN'];?></p>

                        <?php
if ($obj->ssh_valid < time() + 60) {
                $obj->ssh_hash = md5(uniqid("", true));
                $obj->ssh_valid = time() + 3600;

                $db->query("UPDATE monitoring_server SET ssh_hash = '{$obj->ssh_hash}', ssh_valid = {$obj->ssh_valid} WHERE ID = {$obj->ID}");
            }

                $link = $CFG['PAGEURL'] . "ssh_access/" . $obj->ID . "/" . $obj->ssh_hash;
                ?>

                        <div class="input-group">
                            <div class="input-group-btn">
                                <input type="button" class="btn btn-default active" id="curl" value="cURL">
                                <input type="button" class="btn btn-default" id="wget" value="Wget">
                            </div>
                            <input type="text" class="form-control" value="curl -s -o sourcedesk.sh -L <?=$link;?> && bash ./sourcedesk.sh && rm ./sourcedesk.sh" readonly="readonly" onclick="this.select();" id="url">
                            <script>
                            $('#wget').click(function(e){
                                $('#url').val('wget -q -O sourcedesk.sh <?=$link;?> && bash ./sourcedesk.sh && rm ./sourcedesk.sh');
                                $('#wget').addClass("active");
                                $('#curl').removeClass("active");
                            });

                            $('#curl').click(function(e){
                                $('#url').val('curl -s -o sourcedesk.sh -L <?=$link;?> && bash ./sourcedesk.sh && rm ./sourcedesk.sh');
                                $('#wget').removeClass("active");
                                $('#curl').addClass("active");
                            });
                            </script>
                        </div>
                    <?php } else if ($obj->ssh_error) {?>
                    <div class="alert alert-danger" style="margin-bottom: 0;"><?=htmlentities($obj->ssh_error);?></div>
                    <small><?=$l['LASTCHECK'];?>: <?=$dfo->format($obj->ssh_last, true, true, "-");?></small>
                    <?php } else if ($obj->ssh_fingerprint != $obj->ssh_fingerprint_last) {
                if (isset($_POST['accept_ssh_fingerprint'])) {
                    $db->query("UPDATE monitoring_server SET `ssh_fingerprint` = `ssh_fingerprint_last`, `ssh_error` = 'Fingerprint was updated, please wait for next connection attempt' WHERE ID = {$obj->ID}");
                    header('Location: ?p=monitoring&id=' . $obj->ID);
                    exit;
                }
                ?>
                    <p><?=$l['FPPROB'];?></p>

                    <b><?=$l['FPKNOWN'];?>:</b> <?=$obj->ssh_fingerprint ? substr(htmlentities(@array_pop(explode(" ", $obj->ssh_fingerprint, 2))), 0, 80) . "..." : $l['FPNONE'];?><br />
                    <b><?=$l['FPLAST'];?>:</b> <?=$obj->ssh_fingerprint_last ? substr(htmlentities(@array_pop(explode(" ", $obj->ssh_fingerprint_last, 2))), 0, 80) . "..." : $l['FPNONE'];?>

                    <form method="POST">
                        <input type="hidden" name="accept_ssh_fingerprint" value="1">
                        <input type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;" value="<?=$l['FPOK'];?>">
                    </form>
                    <small><?=$l['LASTCHECK'];?>: <?=$dfo->format($obj->ssh_last, true, true, "-");?></small>
                    <?php } else {
                $t = new Table("SELECT * FROM monitoring_updates WHERE status = 'new' AND server = {$obj->ID}", []);

                if (isset($_POST['updates']) && is_array($_POST['updates'])) {
                    $status = isset($_POST['ignore_updates']) ? "ignored" : "waiting";

                    foreach ($_POST['updates'] as $uid) {
                        $uid = intval($uid);
                        $db->query("UPDATE monitoring_updates SET status = '$status' WHERE server = {$obj->ID} AND ID = $uid");
                    }

                    header('Location: ?p=monitoring&id=' . $obj->ID);
                    exit;
                }

                $sql = $t->qry("package ASC");
                if (!$sql->num_rows) {
                    ?>
                <p><?=$l['NOUPD'];?></p>
                <?php } else {
                    echo ucfirst($t->getHeader());?>
                    <form method="POST">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th width="15px">
                                    <input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" />
                                </th>
                                <th><?=$l['PACKAGE'];?></th>
                                <th><?=$l['VERSIONAVA'];?></th>
                            </tr>

                            <?php while ($row = $sql->fetch_object()) {?>
                            <tr>
                                <td>
                                    <input type="checkbox" onchange="javascript:toggle();" class="checkbox" name="updates[]" value="<?=$row->ID;?>" />
                                </td>
                                <td><?=htmlentities($row->package);?></td>
                                <td><?=htmlentities($row->new);?></td>
                            </tr>
                            <?php }?>
                        </table>
                    </div>

                    <?=$lang['CUSTOMERS']['SELECTED'];?> <?=$l['UPDATES'];?>: <input type="submit" name="do_updates" value="<?=$l['DOUP'];?>" class="btn btn-xs btn-success"> <input type="submit" name="ignore_updates" value="<?=$l['IGUP'];?>" class="btn btn-xs btn-warning">
                    </form>
                <?php echo $t->getFooter();} ?>

                    <small><?=$l['LASTCHECK'];?>: <?=$dfo->format($obj->ssh_last, true, true, "-");?></small>
                    <?php }?>
                </div>
            </div>

                <?php if ($ari->check(67)) {?>
                <div class="panel panel-default">
                    <div class="panel-heading"><?=$l['SS'];?></div>
                    <div class="panel-body">
                        <form method="POST">
                            <div class="form-group">
                                <label><?=$l['SN'];?></label>
                                <input type="text" name="name" value="<?=isset($_POST['name']) ? htmlentities($_POST['name']) : htmlentities($obj->name);?>" class="form-control">
                            </div>

                            <div class="form-group">
                                <label><?=$l['GROUP'];?></label>
                                <select name="server_group" class="form-control">
                                    <option value="0"><?=$l['NSG'];?></option>
                                    <?php
$sql = $db->query("SELECT * FROM monitoring_server_groups ORDER BY `name` ASC");
                while ($row = $sql->fetch_object()) {
                    ?>
                                        <option value="<?=$row->ID;?>"<?=$row->ID == ($_POST['server_group'] ?? $obj->server_group) ? ' selected=""' : '';?>><?=htmlentities($row->name);?></option>
                                        <?php
}
                ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><?=$l['VISIBLE'];?></label><br />
                                <label class="radio-inline">
                                    <input type="radio" name="visible" value="1" <?=isset($_POST['visible']) ? ($_POST['visible'] == "1" ? "checked" : "") : ($obj->visible ? "checked" : "");?>> <?=$l['V1'];?>
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="visible" value="0" <?=isset($_POST['visible']) ? ($_POST['visible'] == "0" ? "checked" : "") : (!$obj->visible ? "checked" : "");?>> <?=$l['V2'];?>
                                </label>
                            </div>


                            <div class="row">
                                <div class="col-md-6">
                                    <input type="submit" class="btn btn-primary btn-block" value="<?=$l['DOSS'];?>">
                                </div>

                                <div class="col-md-6">
                                    <a href="?p=monitoring&del_server=<?=$obj->ID;?>" class="btn btn-danger btn-block"><?=$l['DELSRV'];?></a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php }?>
            </div>
        </div>
    <?php
}
    } else {?>
<style>
.label-group{
    text-align: center;
}

.label-group>.label:first-child {
	border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    margin-right: 0;
}

.label-group>.label:nth-child(2) {
	border-radius: 0;
    margin-left: 0;
}

.label-group>.label:nth-child(3) {
	border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    margin-left: 0;
    filter: alpha(opacity=70);
    opacity: 0.7;
}
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?> <small><a href="?p=monitoring_announcements"><?=$l['ANNOUNCEMENTS'];?></a></small><?php if ($ari->check(67)) {?><a href="?p=monitoring&add=1" class="pull-right"><i class="fa fa-plus-circle"></i></a><?php }?></h1>

        <?php
if ($type == "working") {
        $where .= "status = 0";
    } else if ($type == "due") {
        $where .= "status = 0 AND due < '" . date("Y-m-d") . "'";
    } else if ($type == "waiting") {
        $where .= "status = 2";
    } else if ($type == "info") {
        $where .= "status = 3";
    } else if ($type == "finished") {
        $where .= "status = 1";
    }

        $t = new Table("SELECT * FROM monitoring_server", [
            "name" => [
                "name" => $l['NAME'],
                "type" => "like",
            ],
        ], ["name", "ASC"], "monitoring_server");

        echo $t->getHeader();
        ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="150px"><center><?=$l['STATUS'];?></center></th>
                    <th width="150px"><center><?=$l['SERVICES'];?></center></th>
                    <th width="25%"><?=$t->orderHeader("server_group", $l['GROUP']);?></th>
                    <th><?=$t->orderHeader("name", $l['SERVER']);?></th>
                    <th width="150px"><center><?=$l['CONTRACTS'];?></center></th>
                    <th width="150px"><center><?=$t->orderHeader("visible", $l['V1']);?></center></th>
                </tr>

                <?php
if (!empty($_GET['del_server']) && $ari->check(67)) {
            $id = intval($_GET['del_server']);
            $db->query("DELETE FROM monitoring_server WHERE ID = $id");
            $db->query("DELETE FROM monitoring_services WHERE server = $id");
        }

        if (!empty($_GET['del_group']) && $ari->check(67)) {
            $id = intval($_GET['del_group']);
            $db->query("DELETE FROM monitoring_server_groups WHERE ID = $id");
            $db->query("UPDATE monitoring_server SET server_group = 0 WHERE server_group = $id");
        }

        $sql = $t->qry("name ASC");
        while ($row = $sql->fetch_object()) {
            $obj = MonitoringServer::getInstance($row->ID);
            ?>
                    <tr>
                        <td>
                            <center><?=$obj->getFormattedStatus(true);?></center>
                        </td>
                        <td>
                            <div class="label-group">
                                <span class="label label-success"><?=$obj->countByStatus(1);?></span><span class="label label-danger"><?=$obj->countByStatus(0);?></span><span class="label label-default"><?=$obj->countByStatus(-1);?></span>
                            </div>
                        </td>
                        <td>
                            <?php
$group = $obj->getGroup();
            echo $group ? htmlentities($group->name) : "-";
            ?>
                        <td>
                            <a href="?p=monitoring&id=<?=$row->ID;?>"><?=htmlentities($row->name);?></a>
                        </td>
                        <td><center><?=$obj->countContracts();?></center></td>
                        <td>
                            <center><?=$row->visible ? $l['YES'] : $l['NO'];?></center>
                        </td>
                    </tr>
                    <?php
}

        if (!$sql->num_rows) {
            echo '<tr><td colspan="4"><center>' . $l['NOSRVS'] . '</center></td></tr>';
        }
        ?>
            </table>
        </div>

        <?=$t->getFooter();?>

        <h1 class="page-header"><?=$l['GROUPS'];?><?php if ($ari->check(67)) {?><a href="?p=monitoring&add_group=1" class="pull-right"><i class="fa fa-plus-circle"></i></a><?php }?></h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th><?=$l['GROUP'];?></th>
                    <th><?=$l['NEXT_SERVER'];?></th>
                    <th width="20%"><center><?=$l['SERVERS'];?></center></th>
                    <th width="20%"></th>
                </tr>

                <?php
$sql = $db->query("SELECT * FROM monitoring_server_groups ORDER BY `name` ASC");
        if (!$sql->num_rows) {
            ?>
                <tr>
                    <td colspan="4"><center><?=$l['NO_GROUPS'];?></center></td>
                </tr>
                <?php }
         while ($row = $sql->fetch_object()) {$obj = MonitoringServerGroup::getInstance($row->ID);?>
                <tr>
                    <td><?=htmlentities($row->name);?></td>
                    <td><?php $srv = $obj->getLeastFullServer();
            echo $srv ? htmlentities($srv->name) : "-";?></td>
                    <td><center><?=count(MonitoringServer::getAllByGroup($obj));?></center></td>
                    <td><a href="?p=monitoring&edit_group=<?=$row->ID;?>" class="btn btn-primary btn-xs"><?=$l['EDIT'];?></a> <a href="?p=monitoring&del_group=<?=$row->ID;?>" class="btn btn-danger btn-xs"><?=$l['DELETE'];?></a></td>
                </tr>
                <?php }?>
            </table>
        </div>
    </div>
</div>
<?php }}?>