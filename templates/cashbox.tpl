<div class="container">
	<h1>{$lang.CASHBOX.TITLE} <small>{$lang.CASHBOX.FROM|replace:"%u":{$cbuser.firstname|cat:" "|cat:$cbuser.lastname|htmlentities}}</small></h1><hr />
	
	{if !isset($smarty.get.okay)}
	<p>{$lang.CASHBOX.INTRO|replace:"%u":{$cbuser.firstname|cat:" "|cat:$cbuser.lastname|htmlentities}}</p>

	<div class="row" style="padding-top:20px;">
    	<div class="col-md-6 col-md-offset-3">
    		<div class="panel panel-default">
			  	<div class="panel-heading">
			    	<h3 class="panel-title">{$lang.CASHBOX.PAYMENT} <small>{$lang.CASHBOX.INFORMATION}</small></h3>
			 	</div>
			  	<div class="panel-body">
			  		{if isset($msg)}{$msg}{/if}

			  		<form method="POST">
			  			<div class="form-group">
							<div class="input-group">
								{if is_object($curObj) && !empty($curObj->getPrefix())}<span class="input-group-addon">{$curObj->getPrefix()}</span>{/if}
								<input type="text" name="amount" class="form-control" value="{if isset($smarty.request.amount)}{$smarty.request.amount}{/if}" placeholder="{$lang.CASHBOX.AMOUNT}">
								{if is_object($curObj) && !empty($curObj->getSuffix())}<span class="input-group-addon">{$curObj->getSuffix()}</span>{/if}
							</div>
						</div>

						<div class="form-group">
							<select name="payment_method" id="payment_method" onchange="javascript:checkFees(this.value);" class="form-control">
								<option value="" disabled{if !isset($smarty.request.payment_method) || $smarty.request.payment_method == ""} selected="selected"{/if}>- {$lang.CASHBOX.CHOOSE_PM} -</option>
								{foreach from=$gateways key=gateway item=obj}
      							{if $obj->isActive() && $obj->cashbox()}
								<option value="{$gateway}"{if isset($smarty.request.payment_method) && $smarty.request.payment_method == $gateway} selected="selected"{/if}>{if $obj->getLang('frontend_name')|is_string}{$obj->getLang('frontend_name')}{else}{$obj->getLang('name')}{/if}</option>
								{assign var=config value=$obj->getSettings()}
								{if $config.fix != 0}
								<script type="text/javascript">
									var fix_{$gateway} = '{infix n={nfo i={$curObj->convertTo($config.fix)}} c="choosed"}';
								</script>
								{/if}
								{if $config.percent != 0}
								<script type="text/javascript">
									var percent_{$gateway} = '{nfo i=$config.percent r=1}';
								</script>
								{/if}
								<script type="text/javascript">
									var excl_{$gateway} = {$config.excl};
								</script>
								{/if}
								{/foreach}
							</select>
						</div>

						<div class="alert alert-info" id="fees" style="display: none; margin-bottom: 15px;"></div>
						<div class="alert alert-warning" id="waiting" style="{if !isset($smarty.request.payment_method)}display: none; {/if}margin-bottom:15px;">{$lang.CASHBOX.PLEASE_WAIT}</div>

						<script type="text/javascript">
							function checkFees(gateway){ldelim}
								document.getElementById("waiting").style.display = "none";
								if(typeof(window["fix_" + gateway]) !== 'undefined' && typeof(window["percent_" + gateway]) !== 'undefined'){
									var str = '{$lang.CASHBOX.FEE_BOTH}';
									if(window["excl_" + gateway] == 1)
										var str = '{$lang.CASHBOX.FEE_BOTH_EXCL}';
									document.getElementById("fees").style.display = "block";
									document.getElementById("fees").innerHTML = str.replace('%p', window["percent_" + gateway]).replace('%f', window["fix_" + gateway]);
								} else if(typeof(window["fix_" + gateway]) !== 'undefined') {
									var str = '{$lang.CASHBOX.FEE_FIX}';
									if(window["excl_" + gateway] == 1)
										var str = '{$lang.CASHBOX.FEE_FIX_EXCL}';
									document.getElementById("fees").style.display = "block";
									document.getElementById("fees").innerHTML = str.replace('%f', window["fix_" + gateway]);
								} else if(typeof(window["percent_" + gateway]) !== 'undefined') {
									var str = '{$lang.CASHBOX.FEE_PERCENT}';
									if(window["excl_" + gateway] == 1)
										var str = '{$lang.CASHBOX.FEE_PERCENT_EXCL}';
									document.getElementById("fees").style.display = "block";
									document.getElementById("fees").innerHTML = str.replace('%p', window["percent_" + gateway]);
								} else {
									document.getElementById("fees").style.display = "none";
								}
							{rdelim}{if isset($smarty.request.payment_method)}
							checkFees(document.getElementById("payment_method").value);
							{/if}
						</script>

						<div class="form-group">
							<input type="text" maxlength="255" name="subject" value="{if isset($smarty.request.subject)}{$smarty.request.subject|htmlentities}{/if}" placeholder="{$lang.CASHBOX.SUBJECT}" class="form-control" />
						</div>

						<input type="hidden" name="token" value="{$token}" />
						<input type="submit" name="make_payment" value="{$lang.CASHBOX.DO}" class="btn btn-primary btn-block" />
					</form>
			    </div>
			</div>
		</div>
	</div>

	{if $currencies|@count > 1}
	<form method="POST" class="form-inline" style="float: right; display: inline; padding-bottom: 20px;"><select name="currency" class="form-control" onchange="form.submit()"><option disabled>- {$lang.GENERAL.CHOOSE_CURRENCY} -</option>{foreach from=$currencies item=info key=code}<option value="{$code}"{if $myCurrency == $code} selected="selected"{/if}>{$info.name}</option>{/foreach}</select></form>
	{/if}
	{else}
	<div class="alert alert-success">{$lang.CASHBOX.SUCCESS}</div>
	<p>{$lang.CASHBOX.SUCCESS_INTRO}</p>
	<div class="table-responsive">
		<table class="table table-bordered">
			<tr>
				<th>{$lang.CASHBOX.ID}</th>
				<td>{$cbInfo.hash|htmlentities}</td>
			</tr>
			<tr>
				<th>{$lang.CASHBOX.RECIPIENT}</th>
				<td>{$cbuser.firstname|htmlentities} {$cbuser.lastname|htmlentities}</td>
			</tr>
			<tr>
				<th>{$lang.CASHBOX.AMOUNT}</th>
				<td>{$amount}</td>
			</tr>
			{if !empty($cbInfo.subject)}<tr>
				<th>{$lang.CASHBOX.SUBJECT}</th>
				<td>{$cbInfo.subject|htmlentities}</td>
			</tr>{/if}
			<tr>
				<th>{$lang.CASHBOX.GATEWAY}</th>
				<td>{$gateway}</td>
			</tr>
		</table>
	</div>
	<p style="text-align: justify;">{$lang.CASHBOX.SUCCESS_HINT}</p>
	{/if}
</div>