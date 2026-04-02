<?php
// Class for using Google Pay

class GooglePayPG extends PaymentGateway
{
    public static $shortName = "google_pay";

    public function __construct($language)
    {
        parent::__construct(self::$shortName);
        $this->language = $language;

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;
        $this->options = array(
            "gateway" => array("type" => "text", "name" => $this->getLang('gateway'), "default" => "example", "placeholder" => "example"),
            "merchant" => array("type" => "text", "name" => $this->getLang('merchant'), "default" => "exampleGatewayMerchantId", "placeholder" => "exampleGatewayMerchantId"),
            "environment" => array("type" => "text", "name" => $this->getLang('environment'), "default" => "TEST", "placeholder" => "TEST"),
        );
        $this->log = true;
        $this->cashbox = false;
        $this->payment_handler = true;
    }

    public function getPaymentHandler()
    {
        $token = $_POST['paymentToken'];
        $amount = doubleval($_POST['amount']);
        $currency = $_POST['currencyCode'];

        // Custom payment processor integration goes here
    }

    public function getPaymentForm($amount = null)
    {
        global $user, $CFG, $var, $nfo, $cur, $lang;
        $c = $var['currencyObj'];

        $append = "";
        if ($this->settings['excl'] == "1") {
            $append = "_EXCL";
        }

        $fees = "";
        if ($this->settings['fix'] != 0 && $this->settings['percent'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_BOTH' . $append]);
        } else if ($this->settings['fix'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_FIX' . $append]);
        } else if ($this->settings['percent'] != 0) {
            $fees = " " . str_replace(array("%p", "%a"), array($nfo->format($this->settings['percent'], 2, true), $cur->infix($nfo->format($cur->convertAmount(null, $this->settings['fix']), 2))), $lang['CREDIT']['FEES_PERCENT' . $append]);
        }

        ob_start();?>
		<script type="text/javascript">
        function initGPay() {
            const baseRequest = {
                apiVersion: 2,
                apiVersionMinor: 0
            };

            const tokenizationSpecification = {
                type: 'PAYMENT_GATEWAY',
                parameters: {
                    'gateway': '<?=$this->settings['gateway'];?>',
                    'gatewayMerchantId': '<?=$this->settings['merchant'];?>'
                }
            };

            const allowedCardNetworks = ["AMEX", "DISCOVER", "JCB", "MASTERCARD", "VISA"];
            const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];

            const baseCardPaymentMethod = {
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: allowedCardAuthMethods,
                    allowedCardNetworks: allowedCardNetworks
                }
            };

            const cardPaymentMethod = Object.assign(
                {tokenizationSpecification: tokenizationSpecification},
                baseCardPaymentMethod
            );

            const paymentsClient = new google.payments.api.PaymentsClient({environment: '<?=$this->settings['environment'];?>'});

            const isReadyToPayRequest = Object.assign({}, baseRequest);
            isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];

            paymentsClient.isReadyToPay(isReadyToPayRequest).then(function(response) {
                if (response.result) {
                    document.write("<p>");
                    document.write("<form method=\"POST\" style=\"margin-top: -10px; margin-bottom: -10px;\" class=\"form-inline\" onsubmit=\"return false;\" id=\"gpay_form\"><div class=\"input-group\" style=\"height: 40px; margin-top: -13px;\" id=\"payment_amount_group\"><?php if (!empty($c->getPrefix())) {?><span class=\"input-group-addon\"><?=$c->getPrefix();?></span> <?php }?><input type=\"text\" id=\"gpay_amount\" name=\"gpay_amount\" value=\"<?=$amount !== null ? $amount : "";?>\" placeholder=\"<?=$this->getLang('amount');?>\" onkeydown=\"gpayFeesAdded = 0;\" style=\"max-width:80px; height: 40px;\" class=\"form-control\"><?php if (!empty($c->getSuffix())) {?> <span class=\"input-group-addon\"><?=$c->getSuffix();?></span><?php }?><\/div>&nbsp;<div id=\"gpay_div\"></div><\/form>");
                    document.write("<\/p>");

                    const button = paymentsClient.createButton({onClick: () => doGPay()});
                    document.getElementById('gpay_div').appendChild(button);
                }
            }).catch(function(err) {
                console.log(err);
            });

	        gpayFeesAdded = 0;
	        function doGPay() {
	        	<?php if ($this->settings['excl'] == 1) {?>
	        	if(!gpayFeesAdded){
		        	var percent = <?=$this->settings['percent'];?>;
		        	var fix  	= Number(<?=$cur->convertAmount(null, $this->settings['fix']);?>);
		        	var value   = Number(document.getElementById("gpay_amount").value.replace(',', '.'));

					value += value * percent / 100;
					value += fix;
					value  = String(Number(Math.ceil(value * 100) / 100).toFixed(2));
					document.getElementById("gpay_amount").value = value<?=$CFG['NUMBER_FORMAT'] == "de" ? ".replace('.', ',')" : "";?>;
					gpayFeesAdded = 1;
				}
				<?php }?>

                const paymentDataRequest = Object.assign({}, baseRequest);
                paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];

                paymentDataRequest.transactionInfo = {
                    totalPriceStatus: 'FINAL',
                    totalPrice: document.getElementById("gpay_amount").value.replace(',', '.'),
                    currencyCode: '<?=$var['myCurrency'];?>'
                };

                paymentDataRequest.merchantInfo = {
                    merchantName: '<?=$CFG['PAGENAME'];?>'
                };

                paymentsClient.loadPaymentData(paymentDataRequest).then(function(paymentData){
                    paymentToken = paymentData.paymentMethodData.tokenizationData.token;

                    $.post("./credit/pay/google_pay", {
                        paymentToken: paymentToken,
                        amount: document.getElementById("gpay_amount").value.replace(',', '.'),
                        currencyCode: '<?=$var['myCurrency'];?>',
                        csrf_token: "<?=CSRF::raw();?>"
                    }, function () {
                        location.reload();
                    });
                }).catch(function(err){
                    console.error(err);
                });
	        }
        }
		</script>
        <style>
        #gpay_div, #gpay_div > div {
            display: inline;
        }

        .gpay-button {
            display: inline;
        }
        </style>
        <script src="https://pay.google.com/gp/p/js/pay.js" onload="initGPay()"></script>

		<noscript><div class="alert alert-info"><?=$this->getLang('no_js');?></div></noscript>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function getIpnHandler()
    {
        return false;
    }

}