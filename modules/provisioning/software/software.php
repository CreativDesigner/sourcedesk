<?php

class SoftwareProv extends Provisioning
{
    protected $name = "Software";
    protected $short = "software";
    protected $lang;
    protected $options = array();

    public function Config($id, $product = true)
    {
        global $lang, $db, $CFG, $sec;

        $this->loadOptions($id, $product);

        if (isset($_FILES['software_file'])) {
            $file = $_FILES['software_file'];

            if (is_array($file) && !empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
                if (move_uploaded_file($file['tmp_name'], __DIR__ . "/../../../files/downloads/" . basename($file['name']))) {
                    die(json_encode(["status" => "ok", "file" => $file['name']]));
                }
            }

            die(json_encode(["status" => "fail"]));
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute; display: none;">
        <input type="password" autocomplete="new-password" style="display: none;">

        <div class="form-group">
    <label><?=$this->getLang("FILE");?></label><?php if ($product) {?> <a href="#" data-toggle="modal" data-target="#software_upload" class="btn btn-default btn-xs"><?=$this->getLang("UPLOAD");?></a><?php }?>
            <select data-setting="file" class="form-control prov_settings">
                <option value="" selected="" disabled=""><?=$this->getLang("CHOOSE_FILE");?></option>
                <?php
$files = array();
        $handle = opendir(__DIR__ . "/../../../files/downloads/");
        while ($file = readdir($handle)) {
            if (substr($file, 0, 1) == ".") {
                continue;
            }

            array_push($files, $file);
        }

        sort($files, SORT_STRING);

        foreach ($files as $file) {
            ?>
                <option<?=$this->getOption("file") == $file ? ' selected=""' : '';?>><?=htmlentities($file);?></option>
                <?php }?>
            </select>
        </div>

        <div class="form-group">
            <label><?=$this->getLang("BUGTRACKER_DEPT");?></label>
            <select data-setting="bugtracker_dept" class="form-control prov_settings">
                <?=$dept = $this->getOption("bugtracker_dept");?>
                <option value="0" selected=""><?=$lang['PRODUCTS']['BTDEPTD'];?></option>
				<option disabled="disabled"><?=$lang['NEW_TICKET']['PCDEPT'];?></option>
			<?php
$sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . $row->ID . '"' . ($dept == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
        }
        ?>
			<option disabled="disabled"><?=$lang['NEW_TICKET']['PCSTAFF'];?></option>
			<?php
$sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . ($row->ID / -1) . '"' . ($dept / -1 == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
        }
        ?>
            </select>
        </div>

        <div class="checkbox">
        <label>
        <input type="checkbox" class="prov_check" data-setting="licensing_active"<?=$this->getOption('licensing_active') && $this->getOption('licensing_active') != "no" ? ' checked="checked"' : "";?>>
        <?=$lang['PRODUCTS']['LICENSING'];?>
        </label>
        </div>

        <script>
        $("[data-setting=licensing_active]").click(function(){
            if($(this).is(":checked")) $(".licensing").show();
            else $(".licensing").hide();
        });
        </script>

        <div class="licensing"<?=$this->getOption("licensing_active") && $this->getOption('licensing_active') != "no" ? '' : ' style="display: none;"';?>>
        <div class="checkbox">
        <label>
        <input type="checkbox" class="prov_check" data-setting="licensing_reset"<?=$this->getOption("licensing_reset") && $this->getOption('licensing_reset') != "no" ? ' checked="checked"' : "";?>>
        <?=$lang['PRODUCTS']['LICENSINGRESET'];?>
        </label>
        </div>

        <div class="row">
  <div class="col-md-4">
  <div class="form-group">
  <label><?=$lang['PRODUCTS']['LICSEC'];?></label>
  <input type="text" data-setting="licensing_secret" value="<?=$this->getOption("licensing_secret") ?: $sec->generatePassword(64, false, "ld");?>" class="form-control prov_settings" onclick="this.select();">
  </div>
  </div>
  <div class="col-md-4">
  <div class="form-group">
  <label><?=$lang['PRODUCTS']['LICCAC'];?></label>
  <div class="input-group">
  <input type="text" data-setting="licensing_cache" value="<?=($this->getOption("licensing_cache") ?: 0);?>" class="form-control prov_settings">
  <span class="input-group-addon"><?=$lang['PRODUCTS']['DAYS'];?></span>
  </div>
  </div>
  </div>
   <div class="col-md-4">
  <div class="form-group">
  <label><?=$lang['PRODUCTS']['LICREI'];?></label>
  <div class="input-group">
  <input type="text" data-setting="licensing_reissue" value="<?=($this->getOption("licensing_reissue") ?: 0);?>" class="form-control prov_settings">
  <span class="input-group-addon"><?=$lang['PRODUCTS']['LICREIA'];?></span>
  </div>
  </div>
  </div>
  </div>

  <div class="form-group">
  <label><?=$this->getLang("KEY_ADDITIONAL");?></label>
  <input type="text" data-setting="key_additional" value="<?=$this->getOption("key_additional");?>" class="form-control prov_settings">
  </div>
        </div>

        <?php if ($product) {?>
        <div class="modal fade" id="software_upload" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <input type="file" class="form-control" id="upload_file">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->getLang("CANCEL");?></button>
                        <button type="button" id="upload_now" class="btn btn-primary"><?=$this->getLang("UPLOAD");?></button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        $("#upload_now").click(function() {
            $("#page-wrapper").block();
            $("#software_upload").modal("hide");

            var form = new FormData();
            form.append('software_file', $('#upload_file').prop('files')[0]);
            form.append('csrf_token', '<?=CSRF::raw();?>')

            $.ajax({
                url: '?p=product_hosting&id=<?=$id;?>&module=software',
                cache: false,
                contentType: false,
                processData: false,
                data: form,
                type: 'post',
                success: function(r){
                    $("#page-wrapper").unblock();
                    $('#upload_file').val("");

                    var d = JSON.parse(r);

                    if (d.status == "ok") {
                        $('[data-setting=file]').append('<option selected="selected">' + d.file + '</option>');
                    }
                }
            });
        });
        </script>
        <?php }?>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Create($id)
    {
        global $sec, $db, $CFG;

        $id = intval($id);
        $key = $db->real_escape_string($sec->generatePassword(32, false, "ld"));

        $db->query("UPDATE client_products SET `key` = '$key' WHERE `key` = '' AND ID = $id");

        return [true, []];
    }

    public function Delete($id)
    {
        return [true];
    }

    public function ChangePackage($id)
    {
        return [true];
    }

    public function Output($id, $task = "")
    {
        global $db, $CFG, $adminInfo, $sec, $lang, $user, $addons;
        $this->loadOptions($id);

        $id = intval($id);
        $key = $db->query("SELECT `key` FROM client_products WHERE ID = $id")->fetch_object()->key;
        $pid = $db->query("SELECT `product` FROM client_products WHERE ID = $id")->fetch_object()->product;

        $data = @unserialize(decrypt($db->query("SELECT `data` FROM client_products WHERE ID = $id")->fetch_object()->data));

        if ($task == "reset") {
            if ($this->getOption("licensing_active") && $this->getOption("licensing_active") != "no") {
                if (($this->getOption("licensing_reset") && $this->getOption("licensing_reset") != "no") || !empty($adminInfo)) {
                    $key = $db->real_escape_string($sec->generatePassword(32, false, "ld"));
                    $db->query("UPDATE client_products SET `key` = '$key' WHERE ID = $id");
                    $suc = $this->getLang("KEY_RESET");
                }
            }
        }

        if ($task == "reissue") {
            if ($this->getOption("licensing_active") && $this->getOption("licensing_active") != "no") {
                $attemps = $db->query("SELECT `key_reissue` FROM client_products WHERE ID = $id")->fetch_object()->key_reissue;
                if (empty($adminInfo) && $attemps >= $this->getOption("licensing_reissue")) {
                    $err = $this->getLang("NOREISSUEPOS");
                } else {
                    if (empty($adminInfo)) {
                        $db->query("UPDATE client_products SET `key_reissue` = `key_reissue` + 1 WHERE ID = $id");
                    }

                    $db->query("UPDATE client_products SET `key_host` = '' WHERE `key_host` != 'all' AND ID = $id");
                    $db->query("UPDATE client_products SET `key_ip` = '' WHERE `key_ip` != 'all' AND ID = $id");
                    $db->query("UPDATE client_products SET `key_dir` = '' WHERE `key_dir` != 'all' AND ID = $id");

                    $suc = $this->getLang("REISSUEDONE");
                }
            }
        }

        if ($task == "bugtracker") {
            header('Location: ' . $CFG['PAGEURL'] . 'bugtracker/report/' . $pid);
            exit;
        }

        if ($task == "wishlist") {
            header('Location: ' . $CFG['PAGEURL'] . 'wishlist/product/' . $pid);
            exit;
        }

        if ($task == "help") {
            header('Location: ' . $CFG['PAGEURL'] . 'help/' . $id);
            exit;
        }

        if ($task == "download") {
            if (!is_file($path = __DIR__ . '/../../../files/downloads/' . basename($this->getOption("file")))) {
                die($lang['PRODUCTS']['FILE_MISSING']);
            }

            $user->log("Produkt #$id heruntergeladen");

            $doing = true;

            $pid = $db->query("SELECT product FROM client_products WHERE ID = $id")->fetch_object()->product;

            foreach ($addons->runHook("ProductDownload", ["product" => $pid, "license" => $id]) as $v) {
                if ($v === false) {
                    $doing = false;
                }
            }

            if ($doing) {
                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=\"" . basename($path) . "\"");
                readfile($path);
                exit;
            }
        }

        if ($task == "reset_reissue" && !empty($adminInfo)) {
            $db->query("UPDATE client_products SET `key_reissue` = 0 WHERE ID = $id");
        }

        if ($task == "host_all" && !empty($adminInfo)) {
            $db->query("UPDATE client_products SET `key_host` = 'all' WHERE ID = $id");
        }

        if ($task == "host_none" && !empty($adminInfo)) {
            $db->query("UPDATE client_products SET `key_host` = '' WHERE ID = $id");
        }

        if ($task == "ip_all" && !empty($adminInfo)) {
            $db->query("UPDATE client_products SET `key_ip` = 'all' WHERE ID = $id");
        }

        if ($task == "ip_none" && !empty($adminInfo)) {
            $db->query("UPDATE client_products SET `key_ip` = '' WHERE ID = $id");
        }

        if ($task == "dir_all" && !empty($adminInfo)) {
            $db->query("UPDATE client_products SET `key_dir` = 'all' WHERE ID = $id");
        }

        if ($task == "dir_none" && !empty($adminInfo)) {
            $db->query("UPDATE client_products SET `key_dir` = '' WHERE ID = $id");
        }

        ob_start();
        if (isset($err)) {
            echo '<div class="alert alert-danger">' . $err . '</div>';
        }

        if (isset($suc)) {
            echo '<div class="alert alert-success">' . $suc . '</div>';
        }

        $license = $db->query("SELECT `key_host`, `key_ip`, `key_dir` FROM client_products WHERE ID = $id")->fetch_object();

        ?>
		<div class="panel panel-default">
		 	<div class="panel-heading"><?=$this->getLang("LICENSE");?></div>
		  	<div class="panel-body">
                <?php
if (!$this->getOption("licensing_active") || $this->getOption("licensing_active") == "no") {
            echo $this->getLang("NO_LICENSE");
        } else {
            ?>
				<b><?=$this->getLang("LICENSE_KEY");?>:</b> <?=$key;?><br />
				<?php if ($license->key_host) {?>
                <b><?=$this->getLang("LICENSE_HOST");?>:</b> <?=$license->key_host == "all" ? '<i>' . $this->getLang("all") . '</i>' : htmlentities($license->key_host);?><br />
                <?php }if ($license->key_dir) {?>
				<b><?=$this->getLang("LICENSE_DIR");?>:</b> <?=$license->key_dir == "all" ? '<i>' . $this->getLang("all") . '</i>' : htmlentities($license->key_dir);?><br />
                <?php }if ($license->key_ip) {?>
				<b><?=$this->getLang("LICENSE_IP");?>:</b> <?=$license->key_ip == "all" ? '<i>' . $this->getLang("all") . '</i>' : htmlentities($license->key_ip);?><br />
                <?php }}?>
			</div>
		</div>

        <?php if (!empty($adminInfo) && is_array($data) && count($data)) {?>
        <div class="panel panel-default">
		 	<div class="panel-heading"><?=$this->getLang("DATA");?></div>
		  	<div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <?php foreach ($data as $k => $v) {?>
                        <tr>
                            <th><?=htmlentities($k);?></th>
                            <td><?=htmlentities(strval($v));?></td>
                        </tr>
                        <?php }?>
                    </table>
                </div>
            </div>
        </div>
        <?php
}
        $res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function OwnFunctions($id)
    {
        global $adminInfo, $db, $CFG;
        $this->loadOptions($id);

        $res = [];

        if (empty($adminInfo)) {
            $res["download\" target=\"_blank"] = $this->getLang("download");
            $res["download"] = "";
            $res["help"] = $this->getLang("help");
            $res["bugtracker"] = $this->getLang("bugtracker");
            $res["wishlist"] = $this->getLang("wishlist");
        }

        if ($this->getOption("licensing_active") && $this->getOption("licensing_active") != "no") {
            $id = intval($id);
            $license = $db->query("SELECT `key_host`, `key_ip`, `key_dir` FROM client_products WHERE ID = $id")->fetch_object();

            $pos = false;
            foreach (["key_host", "key_ip", "key_dir"] as $k) {
                if ($license->$k && $license->$k != "all") {
                    $pos = true;
                    break;
                }
            }

            if ($pos) {
                $res["reissue"] = $this->getLang("reissue");
            }

            if ($this->getOption("licensing_reset") && $this->getOption("licensing_reset") != "no") {
                $res["reset"] = $this->getLang("reset");
            }
        }

        return $res;
    }

    public function AdminFunctions($id)
    {
        global $db, $CFG;
        $this->loadOptions($id);

        $res = [];

        if ($this->getOption("licensing_active") && $this->getOption("licensing_active") != "no") {
            $license = $db->query("SELECT `key_host`, `key_reissue`, `key_ip`, `key_dir` FROM client_products WHERE ID = $id")->fetch_object();

            $x = $license->key_reissue;
            $y = $this->getOption("licensing_reissue");

            $res["reset"] = $this->getLang("reset");
            $res["reset_reissue"] = $this->getLang("reset_reissue") . " ($x/$y)";
            $res[$license->key_host != "all" ? "host_all" : "host_none"] = $license->key_host != "all" ? $this->getLang("HOST_ALL") : $this->getLang("HOST_NONE");
            $res[$license->key_dir != "all" ? "dir_all" : "dir_none"] = $license->key_dir != "all" ? $this->getLang("DIR_ALL") : $this->getLang("DIR_NONE");
            $res[$license->key_ip != "all" ? "ip_all" : "ip_none"] = $license->key_ip != "all" ? $this->getLang("IP_ALL") : $this->getLang("IP_NONE");
        }

        return $res;
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;

        return array(
            "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }

    public function ApiTasks($id)
    {
        return [
            "GetLicenseData" => "",
            "ReissueLicense" => "",
            "DownloadFile" => "",
        ];
    }

    public function ReissueLicense($id, $req)
    {
        global $db, $CFG;
        $this->loadOptions($id);

        $id = intval($id);

        $attemps = $db->query("SELECT `key_reissue` FROM client_products WHERE ID = $id")->fetch_object()->key_reissue;
        if ($attemps >= $this->getOption("licensing_reissue")) {
            die(json_encode(["status" => "fail"]));
        } else {
            $db->query("UPDATE client_products SET `key_reissue` = `key_reissue` + 1 WHERE ID = $id");
            $db->query("UPDATE client_products SET `key_host` = '' WHERE `key_host` != 'all' AND ID = $id");
            $db->query("UPDATE client_products SET `key_ip` = '' WHERE `key_ip` != 'all' AND ID = $id");
            $db->query("UPDATE client_products SET `key_dir` = '' WHERE `key_dir` != 'all' AND ID = $id");
            die(json_encode(["status" => "ok"]));
        }
    }

    public function GetLicenseData($id, $req)
    {
        global $db, $CFG;
        $this->loadOptions($id);

        $id = intval($id);
        $license = $db->query("SELECT `key`, `key_host`, `key_ip`, `key_dir` FROM client_products WHERE ID = $id")->fetch_object();

        die(json_encode([
            "key" => $license->key,
            "host" => $license->key_host,
            "ip" => $license->key_ip,
            "dir" => $license->key_dir,
        ]));
    }

    public function DownloadFile($id, $req)
    {
        global $db, $CFG, $addons, $lang;
        $this->loadOptions($id);

        if (!is_file($path = __DIR__ . '/../../../files/downloads/' . basename($this->getOption("file")))) {
            die($lang['PRODUCTS']['FILE_MISSING']);
        }

        $user = $this->getClient($id);
        if ($user) {
            $user->log("Produkt #$id heruntergeladen");
        }

        $doing = true;

        $pid = $db->query("SELECT product FROM client_products WHERE ID = $id")->fetch_object()->product;

        foreach ($addons->runHook("ProductDownload", ["product" => $pid, "license" => $id]) as $v) {
            if ($v === false) {
                $doing = false;
            }
        }

        if ($doing) {
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=\"" . basename($path) . "\"");
            readfile($path);
            exit;
        }
    }
}