<?php

if (!class_exists("OvhProv")) {
    class OvhProv extends Provisioning
    {
        protected $name = "OVH";
        protected $short = "ovh";

        protected $host = "ovh.com";
        protected $appkey = "cI4xvWEZxiHAFtB6";
        protected $appsecret = "7y4oAJ7CXpah2s5KHQRwGdSoGAJJQc7y";

        protected $lang;
        protected $options = array();

        protected $serverMgmt = true;

        public function Config($id, $product = true)
        {
            global $raw_cfg;
            $this->loadOptions($id, $product);

            $keyurl = "#";

            $_SESSION['ovh_secret'] = rand(10000000, 99999999);

            $ch = curl_init("https://eu.api.ovh.com/1.0/auth/credential");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Ovh-Application: {$this->appkey}",
                "Content-Type: application/json",
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                "accessRules" => [
                    ["method" => "GET", "path" => "/*"],
                    ["method" => "POST", "path" => "/*"],
                    ["method" => "PUT", "path" => "/*"],
                    ["method" => "DELETE", "path" => "/*"],
                ],
                "redirection" => $raw_cfg['PAGEURL'] . "modules/provisioning/ovh/key.php?os=" . $_SESSION['ovh_secret'],
            ]));
            $res = curl_exec($ch);
            curl_close($ch);

            @$res = json_decode($res, true);

            if (is_array($res) && array_key_exists("validationUrl", $res)) {
                $keyurl = $res['validationUrl'];
                $_SESSION['ovh_key'] = $res['consumerKey'];
            }

            ob_start();?>

            <input style="opacity: 0;position: absolute;">
            <input type="password" autocomplete="new-password" style="display: none;">

            <div class="row" mgmt="1">
                <div class="col-md-12">
                    <div class="form-group">
                        <label><?=$this->getLang("APIKEY");?></label> (<a href="<?=$keyurl;?>" target="_blank"><?=$this->getLang("GETKEY");?></a>)
                        <input type="password" data-setting="key" value="<?=$this->getOption("key");?>" placeholder="7yvo1wsJLPp8TkHRRUOWh31o0Ol9sORO" class="form-control prov_settings" />
                    </div>
                </div>
            </div>
            <div mgmt="0">
            <?php
    if (!$product) {
                $method = "GET";
                $query = "https://eu.api.{$this->host}/1.0/dedicated/server";
                $body = "";
                $res = $this->call($method, $query, $body);
                $data = @json_decode($res, true);

                if (!is_array($data)) {
                    echo '<font color="red">' . htmlentities($res) . '</font>';
                } else {
                    ?>
            <div class="form-group">
                <label><?=$this->getLang("SERVER");?></label>
                <select data-setting="server" class="form-control prov_settings">
                    <option value="" disabled="" selected=""><?=$this->getLang("SNA");?></option>
                    <?php foreach ($data as $s) {?>
                    <option <?php echo $s == $this->getOption("server") ? 'selected=""' : ''; ?>><?=$s;?></option>
                    <?php }?>
                </select>
            </div>
                    <?php }}echo "</div>";

            $res = ob_get_contents();
            ob_end_clean();
            return $res;
        }

        protected function call($method, $query, $body)
        {
            $time = time();
            $sign = $this->sign($method, $query, $body, $time);

            $ch = curl_init($query);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Ovh-Application: " . $this->appkey,
                "X-Ovh-Timestamp: " . $time,
                "X-Ovh-Signature: " . $sign,
                "X-Ovh-Consumer: " . $this->getOption("key"),
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            if ($method != "GET") {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $res = curl_exec($ch);
            curl_close($ch);

            return $res;
        }

        protected function sign($method, $query, $body, $time)
        {
            $str = $this->appsecret . "+" . $this->getOption("key") . "+" . $method . "+" . $query . "+" . $body . "+" . $time;
            $hash = sha1($str);
            return '$1$' . $hash;
        }

        public function Create($id)
        {
            return [true, []];
        }

        public function Output($id, $task = "")
        {
            $this->loadOptions($id);

            ob_start();

            if (empty($this->getOption("server"))) {
                return '<div class="alert alert-warning" style="margin-bottom: 0;">' . $this->getLang("NSA") . '</div>';
            }

            $method = "GET";
            $query = "https://eu.api.{$this->host}/1.0/dedicated/server/" . urlencode($this->getOption("server"));
            $body = "";
            $res = $this->call($method, $query, $body);
            $data = @json_decode($res, true);

            if (!is_array($data)) {
                return '<font color="red">' . htmlentities($res) . '</font>';
            } else {
                ?>
            <div class="panel panel-default">
            <div class="panel-heading"><?=$this->getLang("YOURSERVER");?></div>
            <div class="panel-body">
                <b><?=$this->getLang("HOSTNAME");?>:</b> <?=htmlentities($data["name"]);?><br />
                <b><?=$this->getLang("IPA");?>:</b> <?=htmlentities($data["ip"]);?> (<?=htmlentities($data["reverse"]);?>)<br />
                <b><?=$this->getLang("STATUS");?>:</b> <?=htmlentities($data["state"]);?><br />
                <b><?=$this->getLang("COLOC");?>:</b> <?=htmlentities($data["datacenter"]);?><br />
                <b><?=$this->getLang("LINK");?>:</b> <?=htmlentities($data["linkSpeed"]);?> Mbit/s<br />
                <b><?=$this->getLang("OS");?>:</b> <?=htmlentities($data["os"]);?>
            </div>
            </div>
            <?php
    $res = ob_get_contents();
                ob_end_clean();
                return $res;

            }
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
            $this->loadOptions($id);

            return array(
                "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
            );
        }
    }
}