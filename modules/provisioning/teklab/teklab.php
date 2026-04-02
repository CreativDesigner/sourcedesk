<?php

class TekLabProv extends Provisioning
{
    protected $name = "TekBase Lizenzen";
    protected $short = "teklab";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $version = "1.1";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);
        ob_start();?>

		<div class="row">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("USERNAME");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="12345" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="29y8765z34katxhgqma0f00e180f8e325" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-4" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("VERSION");?></label>
					<select data-setting="version" class="form-control prov_settings">
                        <option value="lite">Lite</option>
                        <option value="privat"<?=$this->getOption("version") == "privat" ? ' selected=""' : '';?>><?=$this->getLang("PRIVAT");?></option>
                        <option value="std"<?=$this->getOption("version") == "std" ? ' selected=""' : '';?>>Business</option>
                        <option value="adv"<?=$this->getOption("version") == "adv" ? ' selected=""' : '';?>>Business + Billing</option>
                    </select>
				</div>
			</div>

            <div class="col-md-4" mgmt="0">
				<div class="form-group">
					<label>CMS</label>
                    <input type="text" data-setting="cms" class="form-control prov_settings" value="<?=htmlentities($this->getOption("cms"));?>">
                    <p class="help-block"><?=$this->getLang("A01");?></p>
				</div>
			</div>

            <div class="col-md-4" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("SHOP");?></label>
					<input type="text" data-setting="shop" class="form-control prov_settings" value="<?=htmlentities($this->getOption("shop"));?>">
                    <p class="help-block"><?=$this->getLang("A01");?></p>
				</div>
			</div>

            <div class="col-md-3" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("GWI");?></label>
                    <input type="text" data-setting="gwi" class="form-control prov_settings" value="<?=htmlentities($this->getOption("gwi"));?>">
                    <p class="help-block"><?=$this->getLang("A0102050100200500999999");?></p>
				</div>
			</div>

            <div class="col-md-3" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("RWI");?></label>
					<input type="text" data-setting="rwi" class="form-control prov_settings" value="<?=htmlentities($this->getOption("rwi"));?>">
                    <p class="help-block"><?=$this->getLang("A0102050100200500999999");?></p>
				</div>
			</div>

            <div class="col-md-3" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("SWI");?></label>
					<input type="text" data-setting="swi" class="form-control prov_settings" value="<?=htmlentities($this->getOption("swi"));?>">
                    <p class="help-block"><?=$this->getLang("A0102050100200500999999");?></p>
				</div>
			</div>

            <div class="col-md-3" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("VWI");?></label>
					<input type="text" data-setting="vwi" class="form-control prov_settings" value="<?=htmlentities($this->getOption("vwi"));?>">
                    <p class="help-block"><?=$this->getLang("A0102050100200500999999");?></p>
				</div>
			</div>

            <div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("TYPE");?></label>
					<select data-setting="type" class="form-control prov_settings">
                        <option value="0"><?=$this->getLang('RENTAL');?></option>
                        <option value="1"<?=$this->getOption("type") == "1" ? ' selected=""' : '';?>><?=$this->getLang("PURCHASE");?></option>
                    </select>
				</div>
			</div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    protected function call($action = "", $method = "GET", array $data = [])
    {
        $ch = curl_init("https://api.tekbase.de/v1/reseller/" . $this->getOption("user") . "/" . $action . ($action ? "/" : ""));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $header = [
            "authenticate: apikey=" . $this->getOption("password"),
        ];

        if (is_array($data) && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $header[] = "Content-Type: application/json; charset=utf-8";
        }

        if ($method != "GET") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        if (200 != ($code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE))) {
            $data = @json_decode($res, true);
            if (is_array($data) && "" != ($data["message"] ?? "")) {
                throw new Exception($data["message"]);
            }

            throw new Exception("Response code $code: $res");
        }

        curl_close($ch);

        $data = @json_decode($res, true);

        if (!is_array($data)) {
            throw new Exception("Decoding response failed: " . $res);
        }

        return $data;
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        try {
            $res = $this->call($this->getData("license"), "DELETE");
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        if ("SUCCESSFUL" != ($res["message"] ?? "")) {
            return [false, $res["message"] ?? ""];
        }

        return [true];
    }

    public function ChangePackage($id)
    {
        $this->loadOptions($id);

        try {
            $res = $this->call($this->getData("license"), "PUT", [
                "version" => $this->getOption("version"),
                "cms" => $this->getOption("cms"),
                "shop" => $this->getOption("shop"),
                "gwislots" => $this->getOption("gwi"),
                "rwislots" => $this->getOption("rwi"),
                "swislots" => $this->getOption("swi"),
                "vwislots" => $this->getOption("vwi"),
            ]);
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        if ("SUCCESSFUL" != ($res["message"] ?? "")) {
            return [false, $res["message"] ?? ""];
        }

        return [true];
    }

    public function Create($id)
    {
        $this->loadOptions($id);

        if (!empty($this->getData("license"))) {
            return array(false, "There is a license ID already. Clear the module data first.");
        }

        try {
            $res = $this->call("", "POST", [
                "customer" => "c" . $id,
                "version" => $this->getOption("version"),
                "cms" => $this->getOption("cms"),
                "shop" => $this->getOption("shop"),
                "gwislots" => $this->getOption("gwi"),
                "rwislots" => $this->getOption("rwi"),
                "swislots" => $this->getOption("swi"),
                "vwislots" => $this->getOption("vwi"),
                "type" => $this->getOption("type"),
            ]);
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }

        if ("SUCCESSFUL" != ($res["message"] ?? "")) {
            return [false, $res["message"] ?? ""];
        }

        return [true, [
            "license" => $res["id"],
        ]];
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        if (isset($_POST['tlip']) && $_POST['tlpath']) {
            try {
                $this->call($this->getData("license"), "PUT", [
                    "siteip" => $_POST['tlip'],
                    "siteurl" => $_POST['tlip'],
                    "sitepath" => $_POST['tlpath'],
                    "status" => "1",
                ]);

                $suc = $this->getLang("SUC");
            } catch (Exception $ex) {
                $err = $ex->getMessage();
            }
        }

        ob_start();
        if (isset($err)) {
            echo '<div class="alert alert-danger">' . $err . '</div>';
        }

        if (isset($suc)) {
            echo '<div class="alert alert-success">' . $suc . '</div>';
        }

        $data = [];
        if ($this->getData("license")) {
            $data = $this->call($this->getData("license"));
        }

        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("LICDATA");?></div>
		  <div class="panel-body">
            <b><?=$this->getLang("LICKEY");?> (TekBASE 7.X)</b>
            <pre><?php if ($data) {try { $res = $this->call($this->getData("license") . "/7");
            echo ($res["key"] ?? "") ?: "-";} catch (Exception $ex) {echo "-";}} else {echo "-";}?></pre>

            <b><?=$this->getLang("LICKEY");?> (TekBASE 8.X)</b>
            <pre><?php if ($data) {try { $res = $this->call($this->getData("license") . "/8");
            echo ($res["key"] ?? "") ?: "-";} catch (Exception $ex) {echo "-";}} else {echo "-";}?></pre>

            <form method="POST">
            <b><?=$this->getLang("IP");?></b>
            <div class="form-group">
                <input type="text" name="tlip" placeholder="1.2.3.4" class="form-control" value="<?=htmlentities($data["siteip"]);?>">
            </div>

            <b><?=$this->getLang("PATH");?></b>
            <div class="form-group">
                <input type="text" name="tlpath" placeholder="/var/www/html" class="form-control" value="<?=htmlentities($data["sitepath"]);?>">
            </div>

            <b><?=$this->getLang("HINT");?></b>
            <div style="margin-bottom: 10px;"><?=$this->getLang("HINT2");?></div>

            <input type="submit" value="<?=$this->getLang("SAVE");?>" class="btn btn-primary btn-block">
            </form>
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
}