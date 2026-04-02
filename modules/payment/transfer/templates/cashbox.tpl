<div class="container">
	<h1>{$gateLang.NAME} <small>{$lang.CASHBOX.PAYMENT}</small></h1><hr />
	
	<p style="float: left;">{$gateLang.CASHBOX|replace:"%a":$amount}<br />{if isset($qr)}{$qr}{/if}<br />{$instructions}</p>
</div>