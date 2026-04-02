<div class="container">
	<h1>{$gateLang.NAME}</h1><hr />

	<p style=\"text-align: justify;\">{$gateLang.DOIT|replace:"%a":$amountf}</p>

  {if isset($err)}<div class="alert alert-danger">{$gateLang.PERR}</div>{/if}

  {if isset($suc)}<div class="alert alert-success">{$gateLang.PSUC}</div>{else}
  <center><br /><form method="POST">
    {if !$logged}<div id="AmazonPayButton"></div>{else}
    <div id="addressBookWidgetDiv" style="width:400px; height:240px; display: inline-block;"></div>
    <div id="walletWidgetDiv" style="width:400px; height:240px; display: inline-block;"></div>
    <br /><input type="hidden" name="orderid" value=""><input type="submit" class="btn btn-primary btn-block hidden-sm hidden-xs" style="max-width: 805px; margin-left: -5px;" value="{$gateLang.PAYNOW}"><input type="submit" class="btn btn-primary btn-block hidden-md hidden-lg" style="max-width: 400px;" value="{$gateLang.PAYNOW}">{/if}<br />
  </form></center>{/if}
</div>

<script type='text/javascript'>
window.onAmazonLoginReady = function () {
    amazon.Login.setClientId('{$gateOpt.client_id}');
};
window.onAmazonLoginReady = function () {
    amazon.Login.setClientId('{$gateOpt.client_id}');
};
</script>
<script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js'></script>
<script type='text/javascript'>
    {if !$logged}
    var authRequest;
    OffAmazonPayments.Button("AmazonPayButton", "{$gateOpt.merchant_id}", {
        type: "PwA",
        authorization: function () {
            loginOptions = { scope: "profile postal_code payments:widget payments:shipping_address", popup: true };
            authRequest = amazon.Login.authorize(loginOptions, "{$cfg.PAGEURL}credit/pay/amazonpay?amount={$amount}");
        },
        onError: function (error) {}
    });
    {else}
    new OffAmazonPayments.Widgets.AddressBook({
        sellerId: '{$gateOpt.merchant_id}',
        onOrderReferenceCreate: function (orderReference) {
           $("[name=orderid]").val(orderReference.getAmazonOrderReferenceId());
        },
        onAddressSelect: function () {},
        design: {
            designMode: 'responsive'
        },
        onError: function (error) {}
    }).bind("addressBookWidgetDiv");

    new OffAmazonPayments.Widgets.Wallet({
        sellerId: '{$gateOpt.merchant_id}',
        onPaymentSelect: function () {
        },
        design: {
            designMode: 'responsive'
        },
        onError: function (error) {}
    }).bind("walletWidgetDiv");
    {/if}
</script>
