<?php

class SslManagerProv extends Provisioning
{
    protected $name = "SSL-Manager (InterNetX)";
    protected $short = "sslmanager";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("APIUSR");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="sourceway" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("APIPWD");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("APICON");?></label>
					<input type="text" data-setting="context" value="<?=$this->getOption("context");?>" placeholder="9" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="row" mgmt="0">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("PRODUCT");?></label>
					<select data-setting="product" class="form-control prov_settings">
						<option value="SSL123"<?=$this->getOption("product") == "SSL123" ? ' selected="selected"' : "";?>>Thawte SSL123</option>
						<option value="SSLWEB"<?=$this->getOption("product") == "SSLWEB" ? ' selected="selected"' : "";?>>Thawte SSL Web</option>
						<option value="SSLWEBWILDCARD"<?=$this->getOption("product") == "SSLWEBWILDCARD" ? ' selected="selected"' : "";?>>Thawte SSL Web (Wildcard)</option>
						<option value="SSLWEBSERVEREV"<?=$this->getOption("product") == "SSLWEBSERVEREV" ? ' selected="selected"' : "";?>>Thawte SSL Webserver EV</option>
						<option value="THAWTECODESIGNING"<?=$this->getOption("product") == "THAWTECODESIGNING" ? ' selected="selected"' : "";?>>Thawte Code Signing</option>

						<option value="BASIC_SSL"<?=$this->getOption("product") == "BASIC_SSL" ? ' selected="selected"' : "";?>>GeoTrust Basic SSL</option>
						<option value="QUICKSSLPREMIUM"<?=$this->getOption("product") == "QUICKSSLPREMIUM" ? ' selected="selected"' : "";?>>GeoTrust Quick SSL Premium</option>
						<option value="TRUEBIZID"<?=$this->getOption("product") == "TRUEBIZID" ? ' selected="selected"' : "";?>>GeoTrust TrueBusiness ID</option>
						<option value="TRUEBIZIDWILDCARD"<?=$this->getOption("product") == "TRUEBIZIDWILDCARD" ? ' selected="selected"' : "";?>>GeoTrust TrueBusiness ID (Wildcard)</option>
						<option value="TRUEBIZIDEV"<?=$this->getOption("product") == "TRUEBIZIDEV" ? ' selected="selected"' : "";?>>GeoTrust TrueBusiness ID EV</option>

						<option value="SECURESITE"<?=$this->getOption("product") == "SECURESITE" ? ' selected="selected"' : "";?>>Symantec Secure Site</option>
						<option value="SECURESITEPRO"<?=$this->getOption("product") == "SECURESITEPRO" ? ' selected="selected"' : "";?>>Symantec Secure Site Pro</option>
						<option value="SECURESITEEV"<?=$this->getOption("product") == "SECURESITEEV" ? ' selected="selected"' : "";?>>Symantec Secure Site EV</option>
						<option value="SECURESITEPROEV"<?=$this->getOption("product") == "SECURESITEPROEV" ? ' selected="selected"' : "";?>>Symantec Secure Site Pro EV</option>

						<option value="GLOBALSIGN_PERSONALSIGN_1"<?=$this->getOption("product") == "GLOBALSIGN_PERSONALSIGN_1" ? ' selected="selected"' : "";?>>GlobalSign PersonalSign Class 1</option>
						<option value="GLOBALSIGN_PERSONALSIGN_2"<?=$this->getOption("product") == "GLOBALSIGN_PERSONALSIGN_2" ? ' selected="selected"' : "";?>>GlobalSign PersonalSign Class 2</option>
						<option value="GLOBALSIGN_PERSONALSIGN_2_PRO"<?=$this->getOption("product") == "GLOBALSIGN_PERSONALSIGN_2_PRO" ? ' selected="selected"' : "";?>>GlobalSign PersonalSign Class 2 Pro</option>
						<option value="GLOBALSIGN_PERSONALSIGN_2_DEPT"<?=$this->getOption("product") == "GLOBALSIGN_PERSONALSIGN_2_DEPT" ? ' selected="selected"' : "";?>>GlobalSign PersonalSign Class 2 Department</option>
					</select>
				</div>
			</div>

			<div class="col-md-6">
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
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    private function CreateContact($data)
    {
        $phone = "+49-2406-9950020";
        if (!empty($data->telephone)) {
            $phone = $data->telephone;

            if (substr($phone, 0, 1) == "+") {
                $phone = substr($phone, 0, 3) . "-" . substr($phone, 3, 4) . "-" . substr($phone, 7);
            } else if (substr($phone, 0, 2) == "00") {
                $phone = "+" . substr($phone, 2, 2) . "-" . substr($phone, 4, 4) . "-" . substr($phone, 8);
            } else {
                $phone = "+49-" . substr(ltrim($phone, "0"), 0, 4) . "-" . substr(ltrim($phone, "0"), 4);
            }

        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
		<request>
			<auth>
		 		<user>' . $this->getOption("user") . '</user>
		 		<password>' . $this->getOption("password") . '</password>
		 		<context>' . $this->getOption("context") . '</context>
		 	</auth>
		 	<language>de</language>
		 	<task>
		 		<code>400201</code>
				<contact>
					<first>' . $data->firstname . '</first>
			        <last>' . $data->lastname . '</last>
			        <title></title>
			        <organization>' . $data->company . '</organization>
			        <address>' . $data->street . ' ' . $data->street_number . '</address>
			        <postal_code>' . $data->postcode . '</postal_code>
			        <city>' . $data->city . '</city>
			        <state>' . $data->country_name . '</state>
			        <country>' . $data->country_alpha2 . '</country>
			        <phone>' . $phone . '</phone>
			        <fax></fax>
			        <email>' . $data->mail . '</email>
			    </contact>
			    <force_contact_create>1</force_contact_create>
		 	</task>
		</request>';

        $ch = curl_init("https://gateway.autodns.com");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $xml = new SimpleXMLElement($res);
        $id = $xml->result->status->object->value;
        if (empty($id)) {
            return false;
        }

        return (string) $id;
    }

    public function Create($id)
    {
        $this->loadOptions($id);

        $contact = $this->CreateContact((object) $this->getClient($id)->get());
        if (false === $contact) {
            return array(false, "Contact creation failed");
        }

        return array(true, array(
            "contact" => $contact,
            "order" => "",
            "certificate" => "",
            "common" => "",
        ));
    }

    public function Output($id, $task = "")
    {
        global $dfo;
        $this->loadOptions($id);

        ob_start();

        if (isset($_POST['details'])) {
            if (empty($this->getData("order"))) {
                die($this->getLang("NOORDER"));
            }

            if (empty($this->getData("certificate"))) {
                $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->getOption("user") . '</user>
				 		<password>' . $this->getOption("password") . '</password>
				 		<context>' . $this->getOption("context") . '</context>
				 	</auth>
				 	<language>de</language>
				 	<task>
				 		<code>400114</code>
						<certificate_job>
							<job>
								<id>' . $this->getData("order") . '</id>
			                </job>
			             </certificate_job>
				 	</task>
				</request>';

                $ch = curl_init("https://gateway.autodns.com");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                $res = curl_exec($ch);
                curl_close($ch);

                $xml = new SimpleXMLElement($res);

                if ($xml->result->msg->code == "EF300101") {
                    $xml = '<?xml version="1.0" encoding="utf-8"?>
					<request>
						<auth>
					 		<user>' . $this->getOption("user") . '</user>
				 		<password>' . $this->getOption("password") . '</password>
				 		<context>' . $this->getOption("context") . '</context>
					 	</auth>
					 	<language>de</language>
					 	<task>
					 		<code>400105</code>
					 	</task>
					</request>';

                    $ch = curl_init("https://gateway.autodns.com");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                    $res = curl_exec($ch);
                    curl_close($ch);

                    $xml = new SimpleXMLElement($res);
                    foreach ($xml->result->data->certificate as $c) {
                        if ($c->name != $this->getData("common")) {
                            continue;
                        }

                        $this->setData("certificate", (string) $c->id);
                        break;
                    }
                }
            }

            if (empty($this->getData("certificate"))) {
                if ($xml->result->data->certificate_job->authentication->status == "WAIT") {
                    die($this->getLang("INP"));
                }

                if ($xml->result->data->certificate_job->authentication->step == "DOMAIN_VERIFICATION") {
                    $auth = $xml->result->data->certificate_job->certificate->authentication;
                    if ($auth->method == "DNS") {
                        die($this->getLang("VALDNS") . "<br /><br /><i>{$auth->dns}</i>");
                    }

                }

                die($this->getLang("UKS"));
            } else {
                $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->getOption("user") . '</user>
			 		<password>' . $this->getOption("password") . '</password>
			 		<context>' . $this->getOption("context") . '</context>
				 	</auth>
				 	<language>de</language>
				 	<task>
				 		<code>400104</code>
				 		<certificate>
				 			<id>' . $this->getData("certificate") . '</id>
				 		</certificate>
				 	</task>
				</request>';

                $ch = curl_init("https://gateway.autodns.com");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                $res = curl_exec($ch);
                curl_close($ch);

                $xml = new SimpleXMLElement($res);

                $pos = stripos($res, "-----BEGIN CERTIFICATE-----");
                $end = stripos($res, "-----END CERTIFICATE-----");
                $crt = substr($res, $pos, $end - $pos + strlen("-----END CERTIFICATE-----"));

                $valid = $xml->result->data->certificate->expire;

                ?>
				<label><?=$this->getLang("CERT");?></label>
	    		<textarea class="form-control" readonly="readonly" onclick="this.focus();this.select()" style="resize: none; width: 100%; height: 200px;"><?=$crt;?></textarea>

	    		<br /><label><?=$this->getLang("VALID");?></label><br />
	    		<?=$dfo->format($valid);?>
				<?php

                exit;
            }
        }

        if (isset($_POST['csr'])) {
            if (!empty($this->getData("order"))) {
                die($this->getLang("HASO"));
            }

            $contact = $this->getData("contact");
            if (empty($_POST['csr'])) {
                die($this->getLang("NOCSR"));
            }

            $csr = openssl_csr_get_subject($_POST['csr']);
            if (false === $csr) {
                die($this->getLang("INVCSR"));
            }

            $commonName = $csr['CN'];

            $ex = explode(".", $commonName);
            if ($ex[0] == "www") {
                die($this->getLang("WWWFAIL"));
            }

            if (strpos($this->getOption("product"), "WILDCARD") !== false && $ex[0] != "*") {
                die($this->getLang("WCFAIL"));
            }

            if (strpos($this->getOption("product"), "WILDCARD") === false && $ex[0] == "*") {
                die($this->getLang("NWCFAIL"));
            }

            $xml = '<?xml version="1.0" encoding="utf-8"?>
			<request>
				<auth>
			 		<user>' . $this->getOption("user") . '</user>
			 		<password>' . $this->getOption("password") . '</password>
			 		<context>' . $this->getOption("context") . '</context>
			 	</auth>
			 	<language>de</language>
			 	<task>
			 		<code>400101</code>
					<certificate>
					 	<admin><id>' . $contact . '</id></admin>
					 	<technical><id>' . $contact . '</id></technical>
					 	<name>' . $commonName . '</name>
					 	<product>' . $this->getOption("product") . '</product>
					 	<lifetime>' . $this->getOption("period") . '</lifetime>
					 	<software>APACHESSL</software>
					 	<csr><![CDATA[' . $_POST['csr'] . ']]></csr>
					</certificate>
			 	</task>
			</request>';

            $ch = curl_init("https://gateway.autodns.com");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $xml = new SimpleXMLElement($res);
            $id2 = $xml->result->data->certificate_job->job->id;
            if (empty($id2)) {
                die($this->getLang("TECERR"));
            }

            $this->setData("order", (string) $id2);
            $this->setData("common", $commonName);

            die("ok");
        }

        if ($task == "revoke") {
            $xml = '<?xml version="1.0" encoding="utf-8"?>
			<request>
				<auth>
			 		<user>' . $this->getOption("user") . '</user>
			 		<password>' . $this->getOption("password") . '</password>
			 		<context>' . $this->getOption("context") . '</context>
			 	</auth>
			 	<language>de</language>
			 	<task>
			 		<code>4001031</code>
					<certificate>
					 	<id>' . $this->getData("order") . '</id>
					</certificate>
			 	</task>
			</request>';

            $ch = curl_init("https://gateway.autodns.com");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $xml = new SimpleXMLElement($res);
            echo '<div class="alert alert-info">' . ($xml->result->msg->text ?: $xml->result->status->text) . '</div>';
        }

        if ($task == "renew") {
            $xml = '<?xml version="1.0" encoding="utf-8"?>
			<request>
				<auth>
			 		<user>' . $this->getOption("user") . '</user>
			 		<password>' . $this->getOption("password") . '</password>
			 		<context>' . $this->getOption("context") . '</context>
			 	</auth>
			 	<language>de</language>
			 	<task>
			 		<code>400106</code>
					<certificate>
					 	<id>' . $this->getData("order") . '</id>
					</certificate>
			 	</task>
			</request>';

            $ch = curl_init("https://gateway.autodns.com");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $xml = new SimpleXMLElement($res);
            echo '<div class="alert alert-info">' . ($xml->result->msg->text ?: $xml->result->status->text) . '</div>';
        }

        if ($task == "reissue") {
            if (isset($_POST['csr2'])) {
                $csr = openssl_csr_get_subject($_POST['csr2']);
                if (false === $csr) {
                    echo "<div class='alert alert-danger'>{$this->getLang("INVCSR")}</div>";
                } else {
                    $commonName = $csr['CN'];

                    $xml = '<?xml version="1.0" encoding="utf-8"?>
					<request>
						<auth>
					 		<user>' . $this->getOption("user") . '</user>
					 		<password>' . $this->getOption("password") . '</password>
					 		<context>' . $this->getOption("context") . '</context>
					 	</auth>
					 	<language>de</language>
					 	<task>
					 		<code>400102</code>
							<certificate>
							 	<id>' . $this->getData("order") . '</id>
							 	<csr><![CDATA[' . $_POST['csr2'] . ']]></csr>
							 	<name>' . $commonName . '</name>
							</certificate>
					 	</task>
					</request>';

                    $ch = curl_init("https://gateway.autodns.com");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                    $res = curl_exec($ch);
                    curl_close($ch);

                    $xml = new SimpleXMLElement($res);
                    echo '<div class="alert alert-info">' . ($xml->result->msg->text ?: $xml->result->status->text) . '</div>';
                }
            }

            ?>
			<div class="panel panel-default">
		  	<div class="panel-heading"><?=$this->getLang("CHACSR");?></div>
		  		<div class="panel-body">
		  			<form method="POST">
		  				<div class="form-group">
					    	<label><?=$this->getLang("CSR");?></label>
					    	<textarea name="csr2" class="form-control" style="resize: none; height: 150px;" placeholder="----- BEGIN CERTIFICATE REQUEST -----"><?=isset($_POST['csr2']) ? $_POST['csr2'] : "";?></textarea>
					    </div>

				    	<input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("REGET");?>" />
		  			</form>
		  		</div>
		  	</div>
			<?php
} else {
            ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("CERT");?></div>
		  <div class="panel-body">
		    <?php if (empty($this->getData("order"))) {?>
		    <p style="text-align: justify;"><?=$this->getLang("ORDERTY");?></p>

		    <div id="error" style="display: none;" class="alert alert-danger"></div>

		    <div class="form-group">
		    	<label><?=$this->getLang("CSR");?></label>
		    	<textarea id="csr" class="form-control" style="resize: none; height: 150px;" placeholder="----- BEGIN CERTIFICATE REQUEST -----"></textarea>
		    </div>

		    <a href="#" id="sign" class="btn btn-primary btn-block"><?=$this->getLang("GETCERT");?></a>

		    <script>
		    var doing = 0;

		    $("#sign").click(function(e){
		    	e.preventDefault();

		    	if(doing) return;
		    	doing = 1;
		    	$("#error").html("").slideUp();
		    	$(this).removeClass('btn-primary').addClass('btn-warning').html("<i class='fa fa-spinner fa-spin'></i> <?=$this->getLang("GETTINGCERT");?>");

		    	$.post("", {
					csr: $("#csr").val(),
					"csrf_token": "<?=CSRF::raw();?>",
		    	}, function(r){
		    		if(r == "ok"){
		    			location.reload();
		    		} else {
			    		$("#error").html(r).slideDown(function(){
			    			$("#sign").removeClass('btn-warning').addClass('btn-primary').html("<?=$this->getLang("GETCERT");?>");
			    		});
			    		doing = 0;
			    	}
		    	});
		    });
		    </script>
			<?php } else {?>
			<div id="details"><i class="fa fa-spinner fa-spin"></i> <?=$this->getLang("LOADINGORDER");?></div>

			<script>
			$.post("", {
				details: 1,
				"csrf_token": "<?=CSRF::raw();?>",
			}, function(r){
				$("#details").html(r);
			});
			</script>
			<?php }?>
		  </div>
		</div>
		<?php
}

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
        return array();
    }

    public function AdminFunctions($id)
    {
        return array(
            "reissue" => $this->getLang("REISSUE"),
            "renew" => $this->getLang("RENEW"),
            "revoke" => $this->getLang("REVOKE"),
        );
    }

    public function ApiTasks($id)
    {
        return array(
            "SetCSR" => "csr",
            "GetCert" => "",
        );
    }

    public function SetCSR($id, $req)
    {
        $this->loadOptions($id);

        if (!empty($this->getData("order"))) {
            die(json_encode(array("code" => "810", "message" => "CSR already set.", "data" => array())));
        }

        $contact = $this->getData("contact");
        if (empty($contact)) {
            die(json_encode(array("code" => "811", "message" => "No contact created - please wait a few minutes.", "data" => array())));
        }

        $csr = openssl_csr_get_subject($req['csr']);
        if (false === $csr) {
            die(json_encode(array("code" => "812", "message" => "Invalid CSR submitted.", "data" => array())));
        }

        $commonName = $csr['CN'];

        $ex = explode(".", $commonName);
        if ($ex[0] == "www") {
            die(json_encode(array("code" => "813", "message" => "Do not include www in CSR.", "data" => array())));
        }

        if (strpos($this->getOption("product"), "WILDCARD") !== false && $ex[0] != "*") {
            die(json_encode(array("code" => "814", "message" => "Use * as subdomain for wildcard certificates in CSR.", "data" => array())));
        }

        if (strpos($this->getOption("product"), "WILDCARD") === false && $ex[0] == "*") {
            die(json_encode(array("code" => "815", "message" => "Use * as subdomain in CSR only for wildcard certificates.", "data" => array())));
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>
		<request>
			<auth>
		 		<user>' . $this->getOption("user") . '</user>
		 		<password>' . $this->getOption("password") . '</password>
		 		<context>' . $this->getOption("context") . '</context>
		 	</auth>
		 	<language>de</language>
		 	<task>
		 		<code>400101</code>
				<certificate>
				 	<admin><id>' . $contact . '</id></admin>
				 	<technical><id>' . $contact . '</id></technical>
				 	<name>' . $commonName . '</name>
				 	<product>' . $this->getOption("product") . '</product>
				 	<lifetime>' . $this->getOption("period") . '</lifetime>
				 	<software>APACHESSL</software>
				 	<csr><![CDATA[' . $req['csr'] . ']]></csr>
				</certificate>
		 	</task>
		</request>';

        $ch = curl_init("https://gateway.autodns.com");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        curl_close($ch);

        $xml = new SimpleXMLElement($res);
        $id2 = $xml->result->data->certificate_job->job->id;
        if (empty($id2)) {
            die(json_encode(array("code" => "816", "message" => "Technical error occured.", "data" => array())));
        }

        $this->setData("order", (string) $id2);
        $this->setData("common", $commonName);

        die(json_encode(array("code" => "100", "message" => "CSR submitted successfully.", "data" => array())));
    }

    public function GetCert($id, $req)
    {
        $this->loadOptions($id);

        if (empty($this->getData("order"))) {
            die(json_encode(array("code" => "810", "message" => "CSR not submitted yet.", "data" => array())));
        }

        if (empty($this->getData("certificate"))) {
            $xml = '<?xml version="1.0" encoding="utf-8"?>
			<request>
				<auth>
			 		<user>' . $this->getOption("user") . '</user>
			 		<password>' . $this->getOption("password") . '</password>
			 		<context>' . $this->getOption("context") . '</context>
			 	</auth>
			 	<language>de</language>
			 	<task>
			 		<code>400114</code>
					<certificate_job>
						<job>
							<id>' . $this->getData("order") . '</id>
		                </job>
		             </certificate_job>
			 	</task>
			</request>';

            $ch = curl_init("https://gateway.autodns.com");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $xml = new SimpleXMLElement($res);

            if ($xml->result->msg->code == "EF300101") {
                $xml = '<?xml version="1.0" encoding="utf-8"?>
				<request>
					<auth>
				 		<user>' . $this->getOption("user") . '</user>
			 		<password>' . $this->getOption("password") . '</password>
			 		<context>' . $this->getOption("context") . '</context>
				 	</auth>
				 	<language>de</language>
				 	<task>
				 		<code>400105</code>
				 	</task>
				</request>';

                $ch = curl_init("https://gateway.autodns.com");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                $res = curl_exec($ch);
                curl_close($ch);

                $xml = new SimpleXMLElement($res);
                foreach ($xml->result->data->certificate as $c) {
                    if ($c->name != $this->getData("common")) {
                        continue;
                    }

                    $this->setData("certificate", (string) $c->id);
                    break;
                }
            }
        }

        if (empty($this->getData("certificate"))) {
            if ($xml->result->data->certificate_job->authentication->status == "WAIT") {
                die(json_encode(array("code" => "811", "message" => "We are waiting for CA.", "data" => array())));
            }

            if ($xml->result->data->certificate_job->authentication->step == "DOMAIN_VERIFICATION") {
                $auth = $xml->result->data->certificate_job->certificate->authentication;
                if ($auth->method == "DNS") {
                    die(json_encode(array("code" => "812", "message" => "DNS verification missing.", "data" => array("dns" => $auth->dns))));
                }

            }

            die(json_encode(array("code" => "813", "message" => "Unknown certificate status.", "data" => array())));
        } else {
            $xml = '<?xml version="1.0" encoding="utf-8"?>
			<request>
				<auth>
			 		<user>' . $this->getOption("user") . '</user>
		 		<password>' . $this->getOption("password") . '</password>
		 		<context>' . $this->getOption("context") . '</context>
			 	</auth>
			 	<language>de</language>
			 	<task>
			 		<code>400104</code>
			 		<certificate>
			 			<id>' . $this->getData("certificate") . '</id>
			 		</certificate>
			 	</task>
			</request>';

            $ch = curl_init("https://gateway.autodns.com");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $res = curl_exec($ch);
            curl_close($ch);

            $xml = new SimpleXMLElement($res);

            $pos = stripos($res, "-----BEGIN CERTIFICATE-----");
            $end = stripos($res, "-----END CERTIFICATE-----");
            $crt = substr($res, $pos, $end - $pos + strlen("-----END CERTIFICATE-----"));

            $valid = $xml->result->data->certificate->expire;

            die(json_encode(array("code" => "100", "message" => "Certificate found.", "data" => array("crt" => $crt, "valid" => $valid))));
        }
    }
}

?>