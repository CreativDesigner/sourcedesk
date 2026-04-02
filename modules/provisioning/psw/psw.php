<?php

class PswGroupProv extends Provisioning
{
    protected $name = "PSW Group (SSL)";
    protected $short = "psw";
    protected $lang;
    protected $options = array();
    protected $wsdl = "http://ssl-api.psw.net/service.php?class=PSWManager&wsdl";
    protected $serverMgmt = true;

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row" mgmt="1">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("username");?></label>
					<input type="text" data-setting="user" value="<?=$this->getOption("user");?>" placeholder="sourceway" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("password");?></label>
					<input type="password" data-setting="password" value="<?=$this->getOption("password");?>" placeholder="GlMo=1O!" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<div class="row" mgmt="0">
			<div class="col-md-4">
				<div class="form-group">
					<label><?=$this->getLang("CACODE");?></label>
					<input type="text" data-setting="ca" value="<?=$this->getOption("ca");?>" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
                    <label><?=$this->getLang("PRODCODE");?></label>
					<input type="text" data-setting="product" value="<?=$this->getOption("product");?>" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-4">
				<div class="form-group">
                    <label><?=$this->getLang("validity");?></label>
					<input type="text" data-setting="validity" value="<?=$this->getOption("validity");?>" class="form-control prov_settings" />
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

        return array(true, array(
            "order" => "",
            "certificate" => "",
        ));
    }

    public function Output($id, $task = "")
    {
        global $dfo;
        $this->loadOptions($id);
        $u = $this->getClient($id);

        ob_start();

        if (isset($_POST['details'])) {
            if (empty($this->getData("order"))) {
                die($this->getLang("NOORDER"));
            }

            if (empty($this->getData("certificate"))) {
                try {
                    $soap = new SoapClient($this->wsdl);
                    $res = $soap->getOrdersbyPartnerOrderId([
                        "UserName" => $this->getOption("user"),
                        "Password" => $this->getOption("password"),
                    ], $this->getData("order"));

                    if (empty($res->Order->Certificate->Personal)) {
                        die($this->getLang("PENDINGORDER"));
                    }

                    $res = $res->Order->Certificate->Personal;
                    $this->setData("certificate", $res);
                } catch (SoapFault $ex) {
                    die($ex->getMessage());
                } catch (SoapException $ex) {
                    die($ex->getMessage());
                }
            } else {
                $res = $this->getData("certificate");
            }

            $pos = stripos($res, "-----BEGIN CERTIFICATE-----");
            $end = stripos($res, "-----END CERTIFICATE-----");
            $crt = substr($res, $pos, $end - $pos + strlen("-----END CERTIFICATE-----"));

            ?>
            <label><?=$this->getLang("CERT");?></label>
            <textarea class="form-control" readonly="readonly" onclick="this.focus();this.select()" style="resize: none; width: 100%; height: 200px;"><?=$crt;?></textarea>

            <?php

            exit;
        }

        if (isset($_POST['csr'])) {
            if (!empty($this->getData("order"))) {
                die($this->getLang("ALORDER"));
            }

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

            try {
                $soap = new SoapClient($this->wsdl);
                $res = $soap->OrderCert([
                    "UserName" => $this->getOption("user"),
                    "Password" => $this->getOption("password"),
                ], [
                    "CACode" => $this->getOption("ca"),
                    "CSR" => $_POST['csr'],
                    "WildCard" => $ex[0] == "*",
                    "WebServerType" => "apache",
                    "ValidityPeriod" => $this->getOption("validity"),
                    "DomainName" => $commonName,
                    "ProductCode" => $this->getOption("product"),
                    "TestSystem" => false,
                ], [
                    "OrganizationName" => $u->get()['company'],
                    "Division" => $u->get()['company'],
                    "FirstName" => $u->get()['firstname'],
                    "LastName" => $u->get()['lastname'],
                    "AddressLine1" => $u->get()['street'] . " " . $u->get()['street_number'],
                    "PostalCode" => $u->get()['postcode'],
                    "City" => $u->get()['city'],
                    "Country" => $u->get()['country_alpha2'],
                    "Email" => $u->get()['mail'],
                    "Fax" => $u->get()['fax'],
                    "Phone" => $u->get()['telephone'],
                ]);
            } catch (SoapFault $ex) {
                die($ex->getMessage());
            } catch (SoapException $ex) {
                die($ex->getMessage());
            }

            $this->setData("order", $res->PartnerOrderID);
            die("ok");
        }

        ?>
		<div class="panel panel-default">
		  <div class="panel-heading"><?=$this->getLang("CERT");?></div>
		  <div class="panel-body">
		    <?php if (empty($this->getData("order"))) {?>
		    <p style="text-align: justify;"><?=$this->getLang("TYORDER");?></p>

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