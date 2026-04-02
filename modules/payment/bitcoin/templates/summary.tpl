<div class="container">
    <h1>{$gateLang.TITLE}</h1><hr>
    <div class="alert alert-info">{$gateLang.PAYMENT_HINT|replace:"%c":$confirmations}</div>

    <img src="{$qrcode}" alt="{$address}" title="{$address}" style="float:left;margin-right:20px;"><b>{$fiat_amount}</b> = <b>{$btc_amount} BTC</b><br /><br />
    {$address}<br /><br />
    <a href="bitcoin:{$address}?amount={$btc_amount_raw}" class="btn btn-primary">{$gateLang.DO_HINT}</a>
    {literal}<script>function shapeshift_click(a,e){e.preventDefault();var link=a.href;window.open(link,'1418115287605','width=700,height=500,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=0,left=0,top=0');return false;}</script>{/literal}<a onclick="shapeshift_click(this, event);" href="https://shapeshift.io/shifty.html?destination={$address}&amp;output=BTC&amp;amount={$btc_amount_raw}"><img src="https://shapeshift.io/images/shifty/xs_light_altcoins.png" class="ss-button"></a>
</div>
