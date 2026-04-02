<?php

class AhsayCbsProv extends Provisioning
{
    protected $name = "AhsayCBS";
    protected $short = "ahsaycbs";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $usernameMgmt = true;
    protected $version = "1.1";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<div class="row">
		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">
            <div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("sysurl");?></label>
					<input type="text" data-setting="sysurl" value="<?=$this->getOption("sysurl");?>" placeholder="https://ahsay.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("sysuser");?></label>
					<input type="text" data-setting="sysuser" value="<?=$this->getOption("sysuser");?>" placeholder="admin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("syspwd");?></label>
					<input type="password" data-setting="syspwd" value="<?=$this->getOption("syspwd");?>" placeholder="<?=$this->getLang("secret");?>" class="form-control prov_settings" />
				</div>
			</div>

            <div mgmt="0">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("quota");?></label>
                        <input type="text" data-setting="quota" value="<?=$this->getOption("quota");?>" placeholder="10" class="form-control prov_settings" />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=$this->getLang("destination");?></label>
                        <input type="text" data-setting="destination" value="<?=$this->getOption("destination");?>" placeholder="<?=$this->getLang("EG");?> -1526366187317" class="form-control prov_settings" />
                    </div>
                </div>
            </div>

            <div class="col-md-12" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("client_type");?></label>
                    <select data-setting="ClientType" class="form-control prov_settings">
                        <option>ACB</option>
                        <option<?=$this->getOption("ClientType") == "OBM" ? ' selected=""' : '';?>>OBM</option>
                    </select>
				</div>
			</div>

            <div class="col-md-6" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("addons");?></label>
					<div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_Mobile" value="1" class="prov_check"<?=$this->getOption("addon_Mobile") === "yes" ? ' checked=""' : '';?>>
                            Mobile
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_ExchangeMailbox" value="1" class="prov_check"<?=$this->getOption("addon_ExchangeMailbox") === "yes" ? ' checked=""' : '';?>>
                            ExchangeMailbox
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_Oracle" value="1" class="prov_check"<?=$this->getOption("addon_Oracle") === "yes" ? ' checked=""' : '';?>>
                            Oracle
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_LotusNotes" value="1" class="prov_check"<?=$this->getOption("addon_LotusNotes") === "yes" ? ' checked=""' : '';?>>
                            LotusNotes
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_MySQL" value="1" class="prov_check"<?=$this->getOption("addon_MySQL") === "yes" ? ' checked=""' : '';?>>
                            MySQL
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_NASClient" value="1" class="prov_check"<?=$this->getOption("addon_NASClient") === "yes" ? ' checked=""' : '';?>>
                            NASClient
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_MsVm" value="1" class="prov_check"<?=$this->getOption("addon_MsVm") === "yes" ? ' checked=""' : '';?>>
                            MsVm
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_VMware" value="1" class="prov_check"<?=$this->getOption("addon_VMware") === "yes" ? ' checked=""' : '';?>>
                            VMware
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_WinServer2008BareMetal" value="1" class="prov_check"<?=$this->getOption("addon_WinServer2008BareMetal") === "yes" ? ' checked=""' : '';?>>
                            WinServer2008BareMetal
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_MsWinSysState" value="1" class="prov_check"<?=$this->getOption("addon_MsWinSysState") === "yes" ? ' checked=""' : '';?>>
                            MsWinSysState
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_MsHyperV" value="1" class="prov_check"<?=$this->getOption("addon_MsHyperV") === "yes" ? ' checked=""' : '';?>>
                            MsHyperV
                        </label>
                    </div>
				</div>
			</div>

            <div class="col-md-6" mgmt="0">
				<div class="form-group">
					<label>&nbsp;</label>
					<div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_MSSQL" value="1" class="prov_check"<?=$this->getOption("addon_MSSQL") === "yes" ? ' checked=""' : '';?>>
                            MSSQL
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_MSExchange" value="1" class="prov_check"<?=$this->getOption("addon_MSExchange") === "yes" ? ' checked=""' : '';?>>
                            MSExchange
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_LotusDomino" value="1" class="prov_check"<?=$this->getOption("addon_LotusDomino") === "yes" ? ' checked=""' : '';?>>
                            LotusDomino
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_InFileDelta" value="1" class="prov_check"<?=$this->getOption("addon_InFileDelta") === "yes" ? ' checked=""' : '';?>>
                            InFileDelta
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_ShadowCopy" value="1" class="prov_check"<?=$this->getOption("addon_ShadowCopy") === "yes" ? ' checked=""' : '';?>>
                            ShadowCopy
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_Qnap" value="1" class="prov_check"<?=$this->getOption("addon_Qnap") === "yes" ? ' checked=""' : '';?>>
                            Qnap
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_CDP" value="1" class="prov_check"<?=$this->getOption("addon_CDP") === "yes" ? ' checked=""' : '';?>>
                            CDP
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_ShadowProtectBareMetal" value="1" class="prov_check"<?=$this->getOption("addon_ShadowProtectBareMetal") === "yes" ? ' checked=""' : '';?>>
                            ShadowProtectBareMetal
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_OpenDirect" value="1" class="prov_check"<?=$this->getOption("addon_OpenDirect") === "yes" ? ' checked=""' : '';?>>
                            OpenDirect
                        </label>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" data-setting="addon_Office365Mail" value="1" class="prov_check"<?=$this->getOption("addon_Office365Mail") === "yes" ? ' checked=""' : '';?>>
                            Office365Mail
                        </label>
                    </div>
				</div>
			</div>

            <div class="col-md-12 addon-options" data-addon="OpenDirect">
				<div class="form-group">
					<label>OpenDirectQuota</label>
					<input type="text" data-setting="OpenDirectQuota" value="<?=$this->getOption("OpenDirectQuota");?>" placeholder="0" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-12 addon-options" data-addon="Office365Mail">
				<div class="form-group">
					<label>Office365MailQuota</label>
					<input type="text" data-setting="Office365MailQuota" value="<?=$this->getOption("Office365MailQuota");?>" placeholder="0" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-12 addon-options" data-addon="Mobile">
				<div class="form-group">
					<label>MobileQuota</label>
					<input type="text" data-setting="MobileQuota" value="<?=$this->getOption("MobileQuota");?>" placeholder="0" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-12 addon-options" data-addon="ExchangeMailbox">
				<div class="form-group">
					<label>ExchangeMailboxQuota</label>
					<input type="text" data-setting="ExchangeMailboxQuota" value="<?=$this->getOption("ExchangeMailboxQuota");?>" placeholder="0" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-6 addon-options" data-addon="VMware">
				<div class="form-group">
					<label>VmwareQuota</label>
					<input type="text" data-setting="VmwareQuota" value="<?=$this->getOption("VmwareQuota");?>" placeholder="0" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-6 addon-options" data-addon="VMware">
				<div class="form-group">
					<label>VmwareQuotaType</label>
					<select data-setting="VmwareQuotaType" class="form-control prov_settings">
                        <option>GUESTVM</option>
                        <option<?=$this->getOption("VmwareQuotaType") === "SOCKET" ? ' selected=""' : '';?>>SOCKET</option>
                    </select>
				</div>
			</div>

            <div class="col-md-6 addon-options" data-addon="MsHyperV">
				<div class="form-group">
					<label>MsHyperVQuota</label>
					<input type="text" data-setting="MsHyperVQuota" value="<?=$this->getOption("MsHyperVQuota");?>" placeholder="0" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-6 addon-options" data-addon="MsHyperV">
				<div class="form-group">
					<label>MsHyperVQuotaType</label>
					<select data-setting="MsHyperVQuotaType" class="form-control prov_settings">
                        <option>GUESTVM</option>
                        <option<?=$this->getOption("MsHyperVQuotaType") === "SOCKET" ? ' selected=""' : '';?>>SOCKET</option>
                    </select>
				</div>
			</div>
		</div>

        <script>
        $(document).ready(function() {
            function sao() {
                $(".addon-options").hide();

                $(".addon-options").each(function() {
                    if ($("[data-setting='addon_" + $(this).data("addon") + "']").is(":checked")) {
                        $(this).show();
                    }
                });
            }

            sao();
            $(".prov_check").change(sao);
        });
        </script>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    private function getPackageArr()
    {
        $data = [
            "ClientType" => $this->getOption("ClientType") == "ACB" ? "ACB" : "OBM",
            "QuotaList" => [
                [
                    "Enabled" => true,
                    "Quota" => round($this->getOption("quota") * 1048576 * 1024),
                    "DestinationKey" => $this->getOption("destination") ?: "OBS",
                ],
            ],
            "ExchangeMailboxQuota" => intval($this->getOption("ExchangeMailboxQuota")),
            "MobileQuota" => intval($this->getOption("MobileQuota")),
            "OpenDirectQuota" => intval($this->getOption("OpenDirectQuota")),
            "VmwareQuota" => intval($this->getOption("VmwareQuota")),
            "VmwareQuotaType" => $this->getOption("VmwareQuotaType"),
            "MsHyperVQuota" => intval($this->getOption("MsHyperVQuota")),
            "MsHyperVQuotaType" => $this->getOption("MsHyperVQuotaType"),
            "Office365MailQuota" => intval($this->getOption("Office365MailQuota")),
        ];

        foreach ($this->options as $key => $value) {
            if (substr($key, 0, 6) == "addon_" && $value === "yes") {
                $data["Enable" . substr($key, 6)] = true;
            }
        }

        return $data;
    }

    public function ClientChanged($id, array $changedFields)
    {
        if (!count(array_intersect($changedFields, ["name", "mail"]))) {
            return;
        }

        $this->loadOptions($id);
        $c = $this->getClient($id);

        $this->Call("2/UpdateUser", [
            "LoginName" => $this->getData("username") ?: "c$id",
            "DisplayName" => $c->get()['name'],
            "Email" => $c->get()['mail'],
        ]);
    }

    public function Create($id)
    {
        $this->loadOptions($id);
        $c = $this->getClient($id);

        $res = $this->Call("2/AddUser", array_merge([
            "LoginName" => $username = $this->getUsername($id),
            "DisplayName" => $c->get()['name'],
            "Password" => $pwd = Security::generatePassword(20, false, "luds"),
            "Email" => $c->get()['mail'],
            "Type" => "PAID",
        ], $this->getPackageArr()));

        if (($res["Status"] ?? "") == "OK") {
            return [true, [
                "username" => $username,
                "pwd" => $pwd,
            ]];
        }

        return [false, $res["Message"] ?? ""];
    }

    private function Call($method, array $data = [])
    {
        $url = rtrim($this->getOption("sysurl"), "/") . "/obs/api/json/$method.do";

        $data["SysUser"] = $this->getOption("sysuser");
        $data["SysPwd"] = $this->getOption("syspwd");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $res = @json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $res;
    }

    public function Output($id, $task = "")
    {
        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("CRED");?></div>
		  <div class="panel-body">
		    <b><?=$this->getLang("sysurl");?>:</b> <a href="<?=$this->getOption("sysurl");?>" target="_blank"><?=$this->getOption("sysurl");?></a><br />
            <b><?=$this->getLang("sysuser");?>:</b> <?=$this->getData("username") ?: "c$id";?><br />
            <b><?=$this->getLang("syspwd");?>:</b> <?=htmlentities($this->getData("pwd"));?>
		  </div>
		</div>

        <form method="POST" action="<?=rtrim($this->getOption("sysurl"), "/");?>/cbs/Logon.do" target="_blank">
            <input type="hidden" name="systemLoginName" value="<?=$this->getData("username") ?: "c$id";?>">
            <input type="hidden" name="systemPassword" value="<?=htmlentities($this->getData("pwd"));?>">
            <input type="submit" class="btn btn-block btn-primary" value="<?=$this->getLang("login");?>">
        </form>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        $res = $this->Call("RemoveUser", [
            "LoginName" => $this->getData("username") ?: "c$id",
        ]);

        return [($res["Status"] ?? "") == "OK", $res["Message"] ?? ""];
    }

    public function Suspend($id, $status = "SUSPENDED")
    {
        $this->loadOptions($id);

        $res = $this->Call("2/UpdateUser", [
            "LoginName" => $this->getData("username") ?: "c$id",
            "Status" => $status,
        ]);

        return [($res["Status"] ?? "") == "OK", $res["Message"] ?? ""];
    }

    public function Unsuspend($id)
    {
        return $this->Suspend($id, "ENABLE");
    }

    public function AllEmailVariables()
    {
        return array(
            "url", "user", "pwd",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "url" => $this->getOption("sysurl"),
            "user" => $this->getData("username") ?: "c$id",
            "pwd" => $this->getData("pwd"),
        );
    }

    public function ContractInfo($id)
    {
        $this->loadOptions($id);

        return [
            $this->getLang("SYSUSER") => $this->getData("username"),
        ];
    }
}