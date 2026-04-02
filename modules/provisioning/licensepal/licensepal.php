<?php

class LicensePalProv extends Provisioning
{
    protected $name = "LicensePal";
    protected $short = "licensepal";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<div class="row" mgmt="1">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("MAIL");?></label>
					<input type="text" data-setting="apiuser" value="<?=$this->getOption("apiuser");?>" placeholder="mail@sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("APIKEY");?></label>
					<input type="password" data-setting="apikey" value="<?=$this->getOption("apikey");?>" placeholder="GlMo1KIl019Kj3Nb" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="form-group" mgmt="0">
			<label><?=$this->getLang("PRODUCT");?></label>
			<select class="form-control prov_settings" data-setting="product">
				<?php foreach ($this->getProducts() as $name => $info) {?>
				<option<?php if ($this->getOption("product") == $name) {
            echo ' selected="selected"';
        }
            ?>><?=$name;?></option>
				<?php }?>
			</select>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Create($id)
    {
        $this->loadOptions($id);

        if (empty($this->getOption("product")) || !array_key_exists($this->getOption("product"), $this->getProducts())) {
            return array(false, $this->getLang("UNKPRO"));
        }

        $product = $this->getProducts()[$this->getOption("product")];

        if (!empty($product['customer'])) {
            return array(true, array("lid" => "", $product['customer'] => ""));
        }

        $post = array_merge(array(
            "apiuser" => $this->getOption("apiuser"),
            "apikey" => $this->getOption("apikey"),
            "action" => "create",
        ), $product);

        $ch = curl_init("https://licensepal.com/manage/api/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($res->status != "success") {
            return array(false, $res->message);
        }

        return array(true, array("lid" => $res->lid));
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        if (empty($this->getData("lid"))) {
            return array(false, $this->getLang("NOLID"));
        }

        $post = array(
            "apiuser" => $this->getOption("apiuser"),
            "apikey" => $this->getOption("apikey"),
            "action" => "terminate",
            "lid" => $this->getData("lid"),
        );

        $ch = curl_init("https://licensepal.com/manage/api/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($res->status != "success") {
            return array(false, $res->message);
        }

        return array(true);
    }

    public function Suspend($id)
    {
        $this->loadOptions($id);

        if (empty($this->getData("lid"))) {
            return array(false, $this->getLang("NOLID"));
        }

        $post = array(
            "apiuser" => $this->getOption("apiuser"),
            "apikey" => $this->getOption("apikey"),
            "action" => "suspend",
            "lid" => $this->getData("lid"),
        );

        $ch = curl_init("https://licensepal.com/manage/api/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($res->status != "success") {
            return array(false, $res->message);
        }

        return array(true);
    }

    public function Unsuspend($id)
    {
        $this->loadOptions($id);

        if (empty($this->getData("lid"))) {
            return array(false, $this->getLang("NOLID"));
        }

        $post = array(
            "apiuser" => $this->getOption("apiuser"),
            "apikey" => $this->getOption("apikey"),
            "action" => "unsuspend",
            "lid" => $this->getData("lid"),
        );

        $ch = curl_init("https://licensepal.com/manage/api/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($res->status != "success") {
            return array(false, $res->message);
        }

        return array(true);
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        $license = false;
        if (!empty($this->getData("lid"))) {
            $post = array(
                "apiuser" => $this->getOption("apiuser"),
                "apikey" => $this->getOption("apikey"),
                "action" => "list",
            );

            $ch = curl_init("https://licensepal.com/manage/api/index.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            foreach ($res->license as $l) {
                if ($l->lid != $this->getData("lid")) {
                    continue;
                }

                $license = $l->domain;
            }
        }

        $ip = $domain = false;
        $products = $this->getProducts();
        if (array_key_exists($this->getOption("product"), $products)) {
            $pc = $products[$this->getOption("product")]['customer'] ?: "";
            if ($pc == "ip") {
                $ip = true;

                if (isset($_POST['ip'])) {
                    if (!filter_var($_POST['ip'], FILTER_VALIDATE_IP)) {
                        $err = $this->getLang("INVIP");
                    } else if (!$this->SetField($id, "ip", $_POST['ip'])) {
                        $err = $this->getLang("TECERR");
                    } else {
                        $suc = $this->getLang("IPCHANGED");
                        $this->setData("ip", $_POST['ip']);
                    }
                }
            }

            if ($pc == "domain") {
                $domain = true;

                if (isset($_POST['domain'])) {
                    if (!filter_var(gethostbyname($_POST['domain']), FILTER_VALIDATE_IP)) {
                        $err = $this->getLang("INVDOM");
                    } else if (!$this->SetField($id, "domain", $_POST['domain'])) {
                        $err = $this->getLang("TECERR");
                    } else {
                        $suc = $this->getLang("DOMCHANGED");
                        $this->setData("domain", $_POST['domain']);
                    }
                }
            }
        }

        ob_start();
        if (isset($err)) {
            echo '<div class="alert alert-danger">' . $err . '</div>';
        }

        if (isset($suc)) {
            echo '<div class="alert alert-success">' . $suc . '</div>';
        }

        ?>

		<?php if ($license) {?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("LICKEY");?></div>
		  <div class="panel-body">
		    <?=$this->getLang("YLKI");?>: <b><?=$license;?></b>
		  </div>
		</div>
		<?php }?>

		<?php if ($ip) {?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("IPA");?></div>
		  <div class="panel-body">
		    <p style="text-align: justify;"><?=$this->getLang("IPI");?></p>

		    <form method="POST">
		    	<input type="text" name="ip" value="<?=$this->getData("ip");?>" placeholder="5.9.7.9" class="form-control" />
		    	<input type="submit" value="<?=$this->getLang("SAVEIP");?>" class="btn btn-primary btn-block" />
		    </form>
		  </div>
		</div>
		<?php }?>

		<?php if ($domain) {?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("DOMAIN");?></div>
		  <div class="panel-body">
		    <p style="text-align: justify;"><?=$this->getLang("DOMAINI");?></p>

		    <form method="POST">
		    	<input type="text" name="domain" value="<?=$this->getData("domain");?>" placeholder="example.com" class="form-control" />
		    	<input type="submit" value="<?=$this->getLang("DOMSAVE");?>" class="btn btn-primary btn-block" />
		    </form>
		  </div>
		</div>
		<?php }?>

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

    protected function getProducts()
    {
        return array(
            "ArcticDesk (branded)" => array("product" => "arcticdesk", "nobranding" => false),
            "ArcticDesk (unbranded)" => array("product" => "arcticdesk", "nobranding" => true),
            "Blesta" => array("product" => "blesta"),
            "ClientExec" => array("product" => "clientexec", "customer" => "domain"),
            "CloudLinux (cPanel)" => array("product" => "cloudlinux", "type" => "0", "customer" => "ip"),
            "CloudLinux (other server)" => array("product" => "cloudlinux", "type" => "1", "customer" => "ip"),
            "cPanel VPS (monthly)" => array("product" => "cpanel", "type" => false, "vps" => true, "customer" => "ip"),
            "cPanel VPS (yearly)" => array("product" => "cpanel", "type" => true, "vps" => true, "customer" => "ip"),
            "cPanel dediziert (monthly)" => array("product" => "cpanel", "type" => false, "vps" => false, "customer" => "ip"),
            "cPanel dediziert (yearly)" => array("product" => "cpanel", "type" => true, "vps" => false, "customer" => "ip"),
            "Installatron VPS" => array("product" => "installatron", "vps" => true, "customer" => "ip"),
            "Installatron dedicated" => array("product" => "installatron", "vps" => false, "customer" => "ip"),
            "InterWorX VPS (limited)" => array("product" => "interworx", "limited" => true, "vps" => true),
            "InterWorX VPS (unlimited)" => array("product" => "interworx", "limited" => false, "vps" => true),
            "InterWorX dediziert (limited)" => array("product" => "interworx", "limited" => true, "vps" => false),
            "InterWorX dediziert (unlimited)" => array("product" => "interworx", "limited" => false, "vps" => false),
            "KernelCare" => array("product" => "kernelcare", "customer" => "ip"),
            "LiteSpeed VPS" => array("product" => "litespeed", "ultra" => true),
            "LiteSpeed dediziert (1 CPU)" => array("product" => "litespeed", "ultra" => false, "cpus" => 1),
            "LiteSpeed dediziert (2 CPU)" => array("product" => "litespeed", "ultra" => false, "cpus" => 2),
            "LiteSpeed dediziert (4 CPU)" => array("product" => "litespeed", "ultra" => false, "cpus" => 4),
            "LiteSpeed dediziert (8 CPU)" => array("product" => "litespeed", "ultra" => false, "cpus" => 8),
            "RVSiteBuilder VPS" => array("product" => "rvsitebuilder", "vps" => true, "customer" => "ip"),
            "RVSiteBuilder dedicated" => array("product" => "rvsitebuilder", "vps" => false, "customer" => "ip"),
            "RVSkin VPS" => array("product" => "rvskin", "vps" => true, "customer" => "ip"),
            "RVSkin dedicated" => array("product" => "rvskin", "vps" => false, "customer" => "ip"),
            "Softaculous VPS" => array("product" => "softaculous", "vps" => true, "customer" => "ip"),
            "Softaculous dedicated" => array("product" => "softaculous", "vps" => false, "customer" => "ip"),
            "SolusVM Master" => array("product" => "solusvm", "type" => 0),
            "SolusVM Slave Only" => array("product" => "solusvm", "type" => 1),
            "Virtualizor" => array("product" => "virtualizor", "customer" => "ip"),
            "WHMSonic" => array("product" => "whmsonic", "customer" => "ip"),
            "WHMXtra" => array("product" => "whmxtra"),
        );
    }

    public function ApiTasks($id)
    {
        $this->loadOptions($id);
        $actions = $this->OwnFunctions($id);

        $my = array(
            "GetLicenseKey" => "",
        );

        $products = $this->getProducts();
        if (array_key_exists($this->getOption("product"), $products)) {
            $pc = $products[$this->getOption("product")]['customer'] ?: "";
            if ($pc == "ip") {
                $my["SetIP"] = "ip";
            }

            if ($pc == "domain") {
                $my["SetDomain"] = "domain";
            }

        }

        return $my;
    }

    public function GetLicenseKey($id, $req)
    {
        $this->loadOptions($id);

        $post = array(
            "apiuser" => $this->getOption("apiuser"),
            "apikey" => $this->getOption("apikey"),
            "action" => "list",
        );

        $ch = curl_init("https://licensepal.com/manage/api/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        foreach ($res->license as $l) {
            if ($l->lid != $this->getData("lid")) {
                continue;
            }

            die(json_encode(array("code" => "100", "message" => "License key fetched.", "data" => array("key" => $l->domain))));
        }

        die(json_encode(array("code" => "810", "message" => "License not found.", "data" => array())));
    }

    public function SetIP($id, $req)
    {
        if ($this->SetField($id, "ip", $req['ip'])) {
            die(json_encode(array("code" => "100", "message" => "License updated.", "data" => array())));
        }

        die(json_encode(array("code" => "810", "message" => "License update failed.", "data" => array())));
    }

    public function SetDomain($id, $req)
    {
        if ($this->SetField($id, "domain", $req['domain'])) {
            die(json_encode(array("code" => "100", "message" => "License updated.", "data" => array())));
        }

        die(json_encode(array("code" => "810", "message" => "License update failed.", "data" => array())));
    }

    protected function SetField($id, $field, $value)
    {
        $this->loadOptions($id);

        if (empty($this->getData("lid"))) {
            if (empty($this->getOption("product")) || !array_key_exists($this->getOption("product"), $this->getProducts())) {
                return false;
            }

            $product = $this->getProducts()[$this->getOption("product")];

            $post = array_merge(array(
                "apiuser" => $this->getOption("apiuser"),
                "apikey" => $this->getOption("apikey"),
                "action" => "create",
                $field => $value,
            ), $product);

            $ch = curl_init("https://licensepal.com/manage/api/index.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            if ($res->status != "success") {
                return false;
            }

            $this->setData("lid", $res->lid);
            return true;
        }

        $post = array(
            "apiuser" => $this->getOption("apiuser"),
            "apikey" => $this->getOption("apikey"),
            "action" => "modify",
            "lid" => $this->getData("lid"),
            $field => $value,
        );

        $ch = curl_init("https://licensepal.com/manage/api/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        return $res->status == "success";
    }
}

?>