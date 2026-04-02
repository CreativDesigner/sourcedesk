<?php

class PushoverProv extends Provisioning
{
    protected $name = "Pushover";
    protected $short = "pushover";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $version = "1.1";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);
        ob_start();
        ?>
		<div class="row">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("USER");?></label>
					<input type="password" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="6y95iHJMr7xT8fZFcvmaPb9wfJtn35" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("TOKEN");?></label>
					<input type="password" data-setting="token" value="<?=$this->getOption("token");?>" placeholder="463t7c59d8gzfp26xuks7iq3r59j2d" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-4" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("TITLE");?></label>
					<input type="text" data-setting="title" value="<?=$this->getOption("title");?>" placeholder="Lorem ipsum" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-4" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("MESSAGE");?></label>
					<input type="text" data-setting="message" value="<?=$this->getOption("message");?>" placeholder="Lorem ipsum" class="form-control prov_settings" />
				</div>
            </div>

            <div class="col-md-4" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("PRIORITY");?></label>
					<select data-setting="priority" class="form-control prov_settings">
                        <option value="-2"><?=$this->getLang("P-2"); ?></option>
                        <option value="-1"<?=$this->getOption("priority") == "-1" ? ' selected=""' : ''; ?>><?=$this->getLang("P-1"); ?></option>
                        <option value="0"<?=$this->getOption("priority") == "0" ? ' selected=""' : ''; ?>><?=$this->getLang("P0"); ?></option>
                        <option value="1"<?=$this->getOption("priority") == "1" ? ' selected=""' : ''; ?>><?=$this->getLang("P1"); ?></option>
                        <option value="2"<?=$this->getOption("priority") == "2" ? ' selected=""' : ''; ?>><?=$this->getLang("P2"); ?></option>
                    </select>
				</div>
            </div>
		</div>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    protected function parse($msg) {
        foreach ($this->cf as $name => $value) {
            $msg = str_replace("[" . $name . "]", $value, $msg);
        }

        return $msg;
    }

    public function Create($id)
    {
        global $sec;
        $this->loadOptions($id);
        
        $ch = curl_init("https://api.pushover.net/1/messages.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "token" => $this->getOption("token"),
            "user" => $this->getOption("user"),
            "title" => $this->parse($this->getOption("title")),
            "message" => $this->parse($this->getOption("message")),
            "priority" => $this->getOption("priority"),
            "retry" => "30",
            "expire" => "600",
        ]));
        $res = curl_exec($ch);

        if (!$res) {
            return [false, curl_error($ch)];
        }

        curl_close($ch);

        $data = @json_decode($res, true);

        if (!$data) {
            return [false, "JSON decoding failed"];
        }

        if (!($data['status'] ?? false)) {
            return [false, implode(", ", $data['errors'] ?? [])];
        }

        return [true, [
            "request" => $data['request'] ?? "",
        ]];
    }

    public function Delete($id)
    {
        return [true];
    }

    public function Output($id, $task = "")
    {
        return "";
    }

    public function AllEmailVariables()
    {
        return array();
    }

    public function EmailVariables($id)
    {
        return array();
    }
}