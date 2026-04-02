<?php

class TheSSSLStoreProv extends Provisioning
{
    protected $name = "The SSL Store";
    protected $short = "thesslstore";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        $products = [
            'securesiteproev' => 'Symantec Secure Site Pro with EV',
            'securesiteev' => 'Symantec Secure Site with EV',
            'securesitepro' => 'Symantec Secure Site Pro',
            'securesite' => 'Symantec Secure Site',
            'verisigncsc' => 'Symantec Code Signing Certificate',
            'securesitewildcard' => 'Symantec Secure Site Wildcard',
            'securesiteprowildcard' => 'Symantec Secure Site Pro Wildcard',
            'securesitemdwc' => 'Symantec Secure Site Multi-Domain Wildcard',
            'securesitepromdwc' => 'Symantec Secure Site Pro Multi-Domain Wildcard',
            'securesitepro_SHA1' => 'Symantec Secure Site Pro SHA-1 Private',
            'trustsealorg' => 'Symantec Safe Site',
            'quicksslpremium' => 'GeoTrust QuickSSL Premium',
            'truebusinessidev' => 'GeoTrust True BusinessID with EV',
            'truebizid' => 'GeoTrust True BusinessID',
            'truebizidmd' => 'GeoTrust True BusinessID Multi-Domain',
            'truebusinessidwildcard' => 'GeoTrust True BusinessID Wildcard',
            'truebusinessidevmd' => 'GeoTrust True BusinessID w/ EV Multi-Domain',
            'malwarescan' => 'GeoTrust Web Site Anti-Malware Scan',
            'quicksslpremiumwildcard' => 'GeoTrust QuickSSL Premium Wildcard',
            'truebizidmdwc' => 'GeoTrust True BusinessID Multi-Domain Wildcard',
            'malwarebasic' => 'Basic Web Site Anti-Malware Scan',
            'sslwebserverev' => 'Thawte SSL Web Server with EV',
            'ssl123' => 'Thawte SSL123',
            'sslwebserver' => 'Thawte SSL Web Server',
            'sslwebserverwildcard' => 'Thawte Wildcard SSL',
            'thawtecsc' => 'Thawte Code Signing Certificate',
            'ssl123wildcard' => 'Thawte SSL123 Wildcard',
            'sslwebservermdwc' => 'Thawte SSL Webserver Multi-Domain Wildcard',
            'rapidssl' => 'RapidSSL Certificate',
            'rapidsslwildcard' => 'RapidSSL Wildcard Certificate',
            'freessl' => 'FreeSSL (RapidSSL 30-Day Trial)',
            'essentialssl' => 'Essential SSL Certificate (DV)',
            'comodoevssl' => 'Comodo EV SSL',
            'comodomdc' => 'Comodo Multi-Domain SSL (OV)',
            'essentialwildcard' => 'EssentialSSL Wildcard (DV)',
            'instantssl' => 'InstantSSL (OV)',
            'instantsslpro' => 'InstantSSL Pro (OV)',
            'comodossl' => 'Comodo SSL Certificate (DV)',
            'hgpcicontrolscan' => 'HackerGuardian PCI Scan Control Center',
            'comodopremiumssl' => 'InstantSSL Premium (OV)',
            'comodoevmdc' => 'Comodo EV Multi-Domain',
            'comodoucc' => 'Comodo Unified Communications Certificate',
            'comododvucc' => 'Comodo Domain Validated UCC SSL',
            'positivessl' => 'Comodo PositiveSSL',
            'positivemdcssl' => 'Comodo PositiveSSL Multi-Domain',
            'positivesslwildcard' => 'Comodo PositiveSSL Wildcard',
            'comodowildcard' => 'Comodo Wildcard SSL Certificate (DV)',
            'hackerprooftm' => 'HackerProof Trust Mark',
            'elitessl' => 'Elite SSL (OV)',
            'comodopciscan' => 'PCI Scanning Enterprise Edition',
            'comodocsc' => 'Comodo Code Signing',
            'positivemdcwildcard' => 'Comodo PositiveSSL Multi-Domain Wildcard',
            'comodomdcwildcard' => 'Comodo Multi-Domain Wildcard (OV)',
            'comodouccwildcard' => 'Comodo OV Unified Communications Wildcard',
            'comodopremiumwildcard' => 'InstantSSL Premium Wildcard (OV)',
            'pacbasic' => 'Personal Authentication Certificate (CPAC)',
            'pacpro' => 'Comodo Personal Authentication Pro Certificate',
            'pacenterprise' => 'Comodo Personal Authentication Enterprise Certificate',
            'positiveevssl' => 'PositiveSSL EV',
            'positiveevmdc' => 'Positive SSL EV Multi-Domain',
            'comodoevcsc' => 'Comodo EV Code Signing',
            'enterprisessl' => 'Enterprise SSL',
            'enterprisepro' => 'Enterprise SSL Pro',
            'enterpriseprowc' => 'Enterprise SSL Pro Wildcard',
            'enterpriseproev' => 'Enterprise SSL Pro with EV',
            'enterpriseproevmdc' => 'Enterprise SSL Pro with EV Multi-Domain',
            'sectigocsc' => 'Sectigo Code Signing Certificate',
            'sectigodvucc' => 'Sectigo SSL Multi-Domain/UCC',
            'sectigoevcsc' => 'Sectigo EV Code Signing Certificate',
            'sectigoevmdc' => 'Sectigo EV Multi-Domain/UCC',
            'sectigoevssl' => 'Sectigo EV SSL',
            'sectigomdc' => 'Sectigo OV SSL Multi-Domain/UCC',
            'sectigomdcwildcard' => 'Sectigo OV SSL Multi-Domain Wildcard',
            'sectigoovssl' => 'Sectigo OV SSL',
            'sectigoovwildcard' => 'Sectigo OV Wildcard SSL',
            'sectigossl' => 'Sectigo SSL',
            'sectigowildcard' => 'Sectigo SSL Wildcard',
            'sectigouccwildcard' => 'Sectigo Multi-Domain/UCC Wildcard (DV)',
        ];

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
            <div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("APIURL");?></label>
					<input type="text" data-setting="url" value="<?=$this->getOption("url") ?: "https://api.thesslstore.com/rest";?>" placeholder="https://api.thesslstore.com/rest" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PARTNERCODE");?></label>
					<input type="text" data-setting="partnercode" value="<?=$this->getOption("partnercode");?>" placeholder="<?=$this->getLang("PARTNERCODE");?>" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("AUTHTOKEN");?></label>
					<input type="password" data-setting="authtoken" value="<?=$this->getOption("authtoken");?>" placeholder="<?=$this->getLang("AUTHTOKEN");?>" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="row" mgmt="0">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PRODUCT");?></label>
					<select data-setting="product" class="form-control prov_settings">
                        <?php foreach ($products as $code => $name) {?>
						<option value="<?=$code;?>"<?=$this->getOption("product") == $code ? ' selected="selected"' : "";?>><?=$name;?></option>
                        <?php }?>
					</select>
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("PERIOD");?></label>
					<select data-setting="period" class="form-control prov_settings">
						<option value="12"<?=$this->getOption("period") == "12" ? ' selected="selected"' : "";?>><?=$this->getLang("1Y");?></option>
						<option value="24"<?=$this->getOption("period") == "24" ? ' selected="selected"' : "";?>><?=$this->getLang("2Y");?></option>
						<option value="36"<?=$this->getOption("period") == "36" ? ' selected="selected"' : "";?>><?=$this->getLang("3Y");?></option>
					</select>
					<p class="help-block"><?=$this->getLang("PERIODHINT");?></p>
				</div>
			</div>

            <div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("SERVER");?></label>
					<input type="text" data-setting="server" value="<?=$this->getOption("server");?>" placeholder="<?=$this->getLang("SERVER");?>" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Create($id)
    {
        $this->loadOptions($id);
        $user = $this->getClient($id);

        $data = [
            "AuthRequest" => [
                "AuthToken" => $this->getOption("authtoken"),
                "PartnerCode" => $this->getOption("partnercode"),
            ],
            "PreferVendorLink" => false,
            "ProductCode" => $this->getOption("product"),
            "RequestorEmail" => $user->get()['mail'],
            "ServerCount" => intval($this->getOption("server")),
            "ValidityPeriod" => intval($this->getOption("period")),
        ];

        $ch = curl_init(rtrim($this->getOption("url"), "/") . "/order/inviteorder");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json; charset=utf-8",
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res || !is_array($res = @json_decode($res, true))) {
            return array(false, "Invalid response");
        }

        if (empty($oid = $res['TheSSLStoreOrderID']) || empty($link = $res['TinyOrderLink'])) {
            return array(false, "Order placement failed");
        }

        return array(true, array(
            "order_id" => $oid,
            "link" => $link,
        ));
    }

    public function Output($id, $task = "")
    {
        global $dfo;
        $this->loadOptions($id);

        ob_start();
        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("CERT");?></div>
		  <div class="panel-body">
		    <a href="<?=$this->getData("link");?>" target="_blank" class="btn btn-primary btn-block"><?=$this->getLang("DOCERT");?></a>
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