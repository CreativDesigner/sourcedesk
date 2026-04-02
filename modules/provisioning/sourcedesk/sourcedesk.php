<?php

class SourceDeskProv extends Provisioning
{
    protected $name = "haseDESK Lizenzen";
    protected $short = "sourcedesk";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $url = "https://sourceway.de";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<div class="row">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("CUSTID");?></label>
					<input type="text" data-setting="custid" value="<?=$this->getOption("custid");?>" placeholder="123" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("APIKEY");?></label>
					<input type="password" data-setting="apikey" value="<?=$this->getOption("apikey");?>" placeholder="<?=$this->getLang("secret");?>" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("PRODUCT");?></label>
                    <select data-setting="product" class="form-control prov_settings">
                        <option value="268"><?=$this->getLang("sdleased");?></option>
                        <option value="277"<?=$this->getOption("product") == "277" ? ' selected=""' : '';?>><?=$this->getLang("sdstartup");?></option>
                        <option value="278"<?=$this->getOption("product") == "278" ? ' selected=""' : '';?>><?=$this->getLang("sdstartupp");?></option>
                    </select>
				</div>
			</div>
		</div>

        <div class="alert alert-info" mgmt="0"><?=$this->getLang("pricing");?></div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        if (empty($this->getData("license"))) {
            return [false, "No license ordered"];
        }

        $this->Cancellation(date("Y-m-d"));
        return [true];
    }

    public function Create($id)
    {
        $this->loadOptions($id);

        if (!empty($this->getData("license"))) {
            return [false, "Already ordered license"];
        }

        $note = "Contract #" . $id;
        $res = file_get_contents($this->url . "/api/" . intval($this->getOption("custid")) . "/" . urlencode($this->getOption("apikey")) . "/hosting/order?id=" . intval($this->getOption("product")) . "&note=" . urlencode($note));

        $res = json_decode($res, true);
        if (!$res || !is_array($res) || $res["code"] != "100") {
            return [false];
        }

        return [true, [
            "license" => strval($res["data"]["id"]),
        ]];
    }

    public function Cancellation($id, $date = "0000-00-00")
    {
        $this->loadOptions($id);

        if ($date != "0000-00-00") {
            $res = file_get_contents($this->url . "/api/" . intval($this->getOption("custid")) . "/" . urlencode($this->getOption("apikey")) . "/hosting/cancel?id=" . $this->getData("license"));
            $res = json_decode($res, true);
            if (!$res || !is_array($res) || $res["code"] != "100") {
                return;
            }

            $dates = $res["data"]["dates"];
            $found = false;

            foreach ($dates as $myDate) {
                if ($myDate >= $date) {
                    $date = $myDate;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $date = array_pop($myDate);
            }
        }

        file_get_contents($this->url . "/api/" . intval($this->getOption("custid")) . "/" . urlencode($this->getOption("apikey")) . "/hosting/cancel?id=" . $this->getData("license") . "&date=" . urlencode($date));
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        if ($task == "download") {
            $ch = curl_init($this->url . "/api/" . intval($this->getOption("custid")) . "/" . urlencode($this->getOption("apikey")) . "/hosting/task?id=" . $this->getData("license") . "&task=DownloadFile");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            curl_close($ch);

            $ex = explode("\n", $header);
            foreach ($ex as $line) {
                if (substr($line, 0, 7) == "Content") {
                    header($line);
                }
            }

            die($body);
        }

        if ($task == "reissue") {
            $data = file_get_contents($this->url . "/api/" . intval($this->getOption("custid")) . "/" . urlencode($this->getOption("apikey")) . "/hosting/task?id=" . $this->getData("license") . "&task=ReissueLicense");
            $data = json_decode($data, true);

            if ($data["status"] == "ok") {
                $suc = $this->getLang("REISSUE_OK");
            } else {
                $err = $this->getLang("REISSUE_FAIL");
            }
        }

        $data = file_get_contents($this->url . "/api/" . intval($this->getOption("custid")) . "/" . urlencode($this->getOption("apikey")) . "/hosting/task?id=" . $this->getData("license") . "&task=GetLicenseData");
        $data = json_decode($data, true);

        ob_start();
        if (isset($err)) {
            echo '<div class="alert alert-danger">' . $err . '</div>';
        }

        if (isset($suc)) {
            echo '<div class="alert alert-success">' . $suc . '</div>';
        }

        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("LICENSEDATA");?></div>
		  <div class="panel-body">
		    <b><?=$this->getLang("LICENSEKEY");?>:</b> <?=$data['key'];?><br />
            <?php if ($data['host']) {?>
            <b><?=$this->getLang("LICENSE_HOST");?>:</b> <?=$data['host'] == "all" ? '<i>' . $this->getLang("all") . '</i>' : htmlentities($data['host']);?><br />
            <?php }if ($data['dir']) {?>
            <b><?=$this->getLang("LICENSE_DIR");?>:</b> <?=$data['dir'] == "all" ? '<i>' . $this->getLang("all") . '</i>' : htmlentities($data['dir']);?><br />
            <?php }if ($data['ip']) {?>
            <b><?=$this->getLang("LICENSE_IP");?>:</b> <?=$data['ip'] == "all" ? '<i>' . $this->getLang("all") . '</i>' : htmlentities($data['ip']);?><br />
            <?php }?>
		  </div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function AllEmailVariables()
    {
        return array(
            "config_url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "config_url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }

    public function OwnFunctions($id)
    {
        global $adminInfo;
        $this->loadOptions($id);

        if (empty($adminInfo)) {
            $actions["download\" target=\"_blank"] = $this->getLang("DOWNLOAD");
            $actions["download"] = "";
        }

        $actions["reissue"] = $this->getLang("REISSUE");

        return $actions;
    }
}