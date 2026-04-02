<div class="container">
	<h1>{$pname}{if !empty($h.description)} <small>{$h.description|htmlentities}</small>{/if}</h1><hr>

	{if isset($suc)}<div class="alert alert-success">{$suc}</div>{/if}
	{if isset($err)}<div class="alert alert-danger">{$err}</div>{/if}

	{if $h.prepaid}
	<div class="panel panel-default">
		<div class="panel-body">
			<center>
				<h2>{$daysLeft}</h2>
				{if $daysLeft != 1}{$lang.HOSTING.DAYSLEFT}{else}{$lang.HOSTING.DAYLEFT}{/if}<br />
				<i>{$lang.HOSTING.EXPIRDATE} {dfo d=$h.last_billed m=0}</i>
			</center>

			{if $ppDays|@count}<br />
			<div class="row">
				{foreach from=$ppDays item=ppDay key=id}
				<div class="col-md-{$ppCol}">
					<a href="{$cfg.PAGEURL}prepaid/{$h.ID}/{$id}" class="btn btn-primary btn-block" style="padding-bottom: 13px;"{if $user_limit < $ppDay.1} disabled=""{/if}>
						<h3 style="margin: 10px;">{$ppDay.0} {$lang.HOSTING.RENEW}</h3>
						{$lang.HOSTING.COSTS} {infix n={nfo i=$ppDay.1}}
					</a>
				</div>
				{/foreach}
			</div>

			<small>{$lang.HOSTING.CREDITHINT|replace:"%u":{$cfg.PAGEURL|cat:"credit"}}</small>
			{/if}
		</div>
	</div>
	{/if}

	<div>
	  <ul class="nav nav-tabs nav-justified" role="tablist">
	    <li class="active"><a href="#info" aria-controls="info" role="tab" data-toggle="tab">{$lang.HOSTING.INFO}</a></li>
	    {if $cf}<li><a href="#cf" aria-controls="cf" role="tab" data-toggle="tab">{$lang.HOSTING.CF}</a></li>{/if}
	    <li><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">{$lang.HOSTING.SETTINGS}</a></li>
		  {if $maxincldomains || $incldomains || $addondomains}<li><a href="#incldomains" aria-controls="incldomains" role="tab" data-toggle="tab">{$lang.HOSTING.DOMAINS}</a></li>{/if}	
			{if $switchingProducts|count}<li><a href="#switch" aria-controls="switch" role="tab" data-toggle="tab">{$lang.HOSTING.SWITCH}</a></li>{/if}
	    {if !$onetime && !$p.autodelete && $h.cancellation_allowed && !$h.prepaid}<li><a href="#cancel" aria-controls="cancel" role="tab" data-toggle="tab">{$lang.HOSTING.CANCEL}</a></li>{/if}
	  </ul>
	  <br />
	  <div class="tab-content">
	    <div role="tabpanel" class="tab-pane active" id="info">
	    	<div class="row">
		    	{if $actions|count > 0}
		    	<div class="col-md-3">
		    		<ul class="list-group">
					  	{foreach from=$actions key=task item=name}{if $name != ""}
					  	<a href="{$url}/{$task}" class="list-group-item">{$name}</a>
					  	{/if}{/foreach}
					</ul>
		    	</div>
		    	<div class="col-md-9">
		    	{else}
		    	<div class="col-md-12">
		    	{/if}
		    	{$output}<br />

				{if $h.notes_public}<div class="panel panel-default"><div class="panel-heading">{$lang.HOSTING.INFO}</div><div class="panel-body">{$h.notes_public|nl2br}</div></div><br />{/if}

				<div class="panel panel-default"><div class="panel-heading">{$lang.HOSTING.CONDET}</div><div class="panel-body">
					<div class="table-responsive">
						<table class="table table-bordered table-striped" style="margin-bottom: 0;">
							<tr>
								<th width="40%" style="text-align: right;">{$lang.HOSTING.CONSTART}</th>
								<td width="60%">{dfo d=$h.date m=false}</td>
							</tr>

							{if $runtime}
							<tr>
								<th width="40%" style="text-align: right;">{$lang.HOSTING.CONRUNTIME}</th>
								<td width="60%">{$runtime}</td>
							</tr>
							{/if}

							{if $minruntime}
							<tr>
								<th width="40%" style="text-align: right;">{$lang.HOSTING.CONMINRUNTIME}</th>
								<td width="60%">{$minruntime}</td>
							</tr>
							{/if}

							{if $notper}
							<tr>
								<th width="40%" style="text-align: right;">{$lang.HOSTING.CONNOTPER}</th>
								<td width="60%">{$notper}</td>
							</tr>
							{/if}

							<tr>
								<th width="40%" style="text-align: right;">{$lang.HOSTING.PRICE}</th>
								<td width="60%">{$price}</td>
							</tr>

							{if $nextinv}
							<tr>
								<th width="40%" style="text-align: right;">{$lang.HOSTING.NEXTINV}</th>
								<td width="60%">{$nextinv}</td>
							</tr>
							{/if}
						</table>
					</div>
				</div></div>
		    	</div>
	    	</div>
	    </div>

		{if $cf}
		<div role="tabpanel" class="tab-pane" id="cf">
			<form method="POST" action="{$cfg.PAGEURL}upgrade/{$h.ID}">
			<p style="text-align: justify;">{$lang.HOSTING.CF_INTRO}</p>

			<style>
			.radio, .checkbox {
				margin-top: 0;
				margin-bottom: 0;
			}
			</style>

			{foreach from=$cf item=f}
			<div class="form-group">
				<label>{$f.name|htmlentities}</label>
				{if $f.type == "select"}
				<select class="form-control custom_field" name="cf[{$f.ID}]" data-id="{$f.ID}">
					{foreach from=explode("|", $f.values) item=v}
					<option>{$v}</option>
					{/foreach}
				</select>
				{elseif $f.type == "radio"}
				{foreach from=explode("|", $f.values) item=v key=i}
				<div class="radio">
					<label>
						<input type="radio" name="cf[{$f.ID}]" name="radio{$f.ID}" class="custom_field" data-id="{$f.ID}" value="{$v}"{if $i == 0} checked=""{/if}>
						{$v}
					</label>
				</div>
				{/foreach}
				{elseif $f.type == "check"}
				<div class="checkbox">
					<label>
						<input type="checkbox" name="cf[{$f.ID}]" class="custom_field" data-id="{$f.ID}" value="1"{if $f.checked} checked="" disabled=""{/if}> {$f.name|htmlentities}
					</label>
				</div>
				{else}
				<input type="{$f.type}" name="cf[{$f.ID}]" class="form-control custom_field" data-id="{$f.ID}" value="{$f.default|htmlentities}" min="{$f.minimum}"{if $f.maximum >= 0} max="{$f.maximum}"{/if} required="">
				{/if}
				{if array_key_exists("defcost", $f)}<p class="help-block">{$lang.CONFIGURE.COSTS}: {$f.defcost}</p>{/if}
			</div>
			{/foreach}

			<script>
			$(".custom_field").change(function () {
				var id = $(this).data("id");

				if ($(this).prop("type") == "checkbox") {
					var val = $(this).is(":checked") ? 1 : 0;
				} else {
					var val = $(this).val();
				}

				var hb = $(this).closest(".form-group").find(".help-block");
					
				if (hb.length) {
					hb.html("<i class='fa fa-spinner fa-pulse'></i> {$lang.CONFIGURE.PW}");

					$.post("", {
						"csrf_token": "{ct}",
						"field_id": id,
						"field_val": val
					}, function (r) {
						hb.html("{$lang.CONFIGURE.COSTS}: " + r);
					});
				}
			});
			</script>

			<input type="submit" class="btn btn-primary btn-block" value="{$lang.HOSTING.CF_DO}">
			</form>
		</div>
		{/if}

	    <div role="tabpanel" class="tab-pane" id="settings">
	    	<div class="row">
	    		<div class="col-md-{if $add_domains}6{else}12{/if}">
	    			<div class="panel panel-default">
					  <div class="panel-heading">{$lang.HOSTING.NOTE}</div>
					  <div class="panel-body">
					    <p style="text-align: justify;">{$lang.HOSTING.NOTE_INTRO}</p>

					    <form method="POST" action="{$url}">
					    	<input type="text" name="note" placeholder="{$lang.HOSTING.NOTE_PH}" class="form-control" value="{$h.description|htmlentities}" maxlength="25" />

					    	<input type="submit" class="btn btn-primary btn-block" value="{$lang.HOSTING.NOTE_SAVE}" style="margin-top: 10px;" />
					    </form>
					  </div>
					</div>
	    		</div>

	    		{if $add_domains}
	    		<div class="col-md-6">
	    			<div class="panel panel-default">
					  <div class="panel-heading">{$lang.HOSTING.DOMAIN}</div>
					  <div class="panel-body">
					    <p style="text-align: justify;">{$lang.HOSTING.DOMAIN_INTRO}</p>

					    <form method="POST" action="{$url}">
					    	<input type="text" name="domain" placeholder="{$lang.HOSTING.DOMAIN_PH}" class="form-control" />

					    	<input type="submit" class="btn btn-primary btn-block" value="{$lang.HOSTING.DOMAIN_ADD}" style="margin-top: 10px;" />
					    </form>
					  </div>
					</div>
	    		</div>
	    		{/if}
	    	</div>
	    </div>
		  {if $maxincldomains || $incldomains || $addondomains}
			<div role="tabpanel" class="tab-pane" id="incldomains">
				<div class="table-responsive">
					<table class="table table-bordered table-striped" style="margin-bottom: 0;">
						{if $maxincldomains}
						<tr>
							<th width="50%">{$lang.HOSTING.MAXINCLDOMAINS}</th>
							<td>{$maxincldomains}</td>
						</tr>
						{/if}
						{if $incldomains || $maxincldomains}
						<tr>
							<th width="50%">{$lang.HOSTING.MYINCLDOMAINS}</th>
							<td>
							 {if $incldomainlist|@count}
								<ul style="margin-bottom: 0; padding-left: 20px;">
									{foreach from=$incldomainlist item=domain key=link}
									<li><a href="{$link}" target="_blank">{$domain}</a></li>
									{/foreach}
								</ul>
							 {else}
								{$lang.HOSTING.NOINCLDOMAINS}
							 {/if} 	
							</td>
						</tr>
						{/if}
						{if $addondomains}
						<tr>
							<th width="50%">{$lang.HOSTING.ADDONDOMAINS}</th>
							<td>
							 <ul style="margin-bottom: 0; padding-left: 20px;">
								{foreach from=$addondomainlist item=domain key=link}
								<li>{$domain}</li>
								{/foreach}
							</ul>
							</td>
						</tr>
						{/if}
						{if $maxincldomains}
						<tr>
							<th width="50%">{$lang.HOSTING.OPENINCLDOMAINS}</th>
							<td>{max($maxincldomains - $incldomains, 0)}</td>
						</tr>
						{/if}
					</table>
				</div>
				{if ($maxincldomains - $incldomains) > 0}
				<a href="{$cfg.PAGEURL}incldomain/{$h.ID}" class="btn btn-primary btn-block" style="margin-top: 10px;">{$lang.HOSTING.ORDERINCLDOMAINS}</a>
				{/if}
			</div>
			{/if}	
	    {if !$onetime}
	    <div role="tabpanel" class="tab-pane" id="cancel">
	    	{if $h.cancellation_date > "0000-00-00"}
	    	<p style="text-align: justify;">{$lang.HOSTING.CANCEL_ACTIVE|replace:"%d":{dfo d=$h.cancellation_date m=0}}</p>

	    	<form method="POST">
		    	<div class="checkbox">
		    		<label>
		    			<input type="checkbox" name="cancel_sure" value="yes">
		    			{$lang.HOSTING.CANCEL_SURE}
		    		</label>
		    	</div>

		    	<script>
		    	$("[name=cancel_sure]").change(function(){
		    		if($(this).is(":checked")) $(".btn-cancel").prop("disabled", false);
		    		else $(".btn-cancel").prop("disabled", true);
		    	});
		    	</script>

		    	<input type="submit" class="btn btn-primary btn-block btn-cancel" value="{$lang.HOSTING.CANCEL_REVOKE}" disabled="disabled" />
		    </form>
	    	{else}
	    	<p style="text-align: justify;">{$lang.HOSTING.CANCEL_INTRO}</p>

	    	<form method="POST" action="{$url}">
	    		<div class="form-group">
	    			<label>{$lang.HOSTING.CANCEL_DATE}</label>
		    		<select name="cancel_date" class="form-control">
		    			{foreach from=$canceldates item=d}
		    			<option value="{$d}">{dfo d=$d m=0}</option>
		    			{/foreach}
		    		</select>
		    	</div>

					{if $incldomains > 0}
					<div class="alert alert-warning">
					{$lang.HOSTING.INCLWARNING}
					</div>
					{/if}

		    	<input type="submit" class="btn btn-block btn-primary" value="{$lang.HOSTING.CANCEL_DO}" />
	    	</form>
	    	{/if}
	    </div>
	    {/if}

			{if $switchingProducts|count}
	    <div role="tabpanel" class="tab-pane" id="switch">
				<div id="switch_product_res" style="display: none;"></div>
	    	<p style="text-align: justify;">{$lang.HOSTING.SWITCH_INTRO}</p>

	    	<form method="POST" id="switch_product_form">
	    		<div class="form-group">
	    			<label>{$lang.HOSTING.NEW_PRODUCT}</label>
		    		<select name="switch_product" class="form-control">
		    			{foreach from=$switchingProducts item=p key=id}
		    			<option value="{$id}">{$p.name|htmlentities} - {$lang.HOSTING.NEW_PRICE}: {$p.fPrice} - {if $p.toPay >= 0}{$lang.HOSTING.PAYNOW}: {$p.fToPay}{else}{$lang.HOSTING.GETNOW} {$p.fToPay}{/if}</option>
		    			{/foreach}
		    		</select>
		    	</div>

					<input type="submit" class="btn btn-block btn-primary" id="switch_product_btn" value="{$lang.HOSTING.SWITCH_DO}" />
	    	</form>
	    </div>

			<script type="text/javascript">{literal}
			var changing_product = false;

			function changeProd(e) {
				e.preventDefault();
				
				if (changing_product) {
					return;
				}
				changing_product = true;

				$("#switch_product_btn").val("{/literal}{$lang.HOSTING.PW}{literal}").prop("disabled", true);

				$.post("", {
					"switch_product": $("[name=switch_product]").val(),
					"csrf_token": "{/literal}{ct}{literal}"
				}, function (r) {
					$("#switch_product_res").slideUp(function() {
						$("#switch_product_res").html(r).slideDown();
					});
					$("#switch_product_btn").val("{/literal}{$lang.HOSTING.SWITCH_DO}{literal}").prop("disabled", false);
					changing_product = false;
				});
			}

			$("#switch_product_form").submit(changeProd);
			$("#switch_product_btn").click(changeProd);
			{/literal}</script>
	    {/if}
	  </div>
	</div>
</div>