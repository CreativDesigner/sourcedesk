<div class="container">
	<h1>{$pname}{if !empty($h.description)} <small>{$h.description|htmlentities}</small>{/if}</h1><hr>

	{if isset($suc)}<div class="alert alert-success">{$suc}</div>{/if}
	{if isset($err)}<div class="alert alert-danger">{$err}</div>{/if}

	<div>
	  <ul class="nav nav-tabs nav-justified" role="tablist">
	    <li class="nav-item"><a class="nav-link active" href="#info" aria-controls="info" role="tab" data-toggle="tab">{$lang.HOSTING.INFO}</a></li>
	    <li class="nav-item"><a class="nav-link" href="#settings" aria-controls="settings" role="tab" data-toggle="tab">{$lang.HOSTING.SETTINGS}</a></li>
		  {if $maxincldomains}<li class="nav-item"><a class="nav-link" href="#incldomains" aria-controls="incldomains" role="tab" data-toggle="tab">{$lang.HOSTING.INCLDOMAINS}</a></li>{/if}	
			{if $switchingProducts|count}<li class="nav-item"><a class="nav-link" href="#switch" aria-controls="switch" role="tab" data-toggle="tab">{$lang.HOSTING.SWITCH}</a></li>{/if}
	    {if !$onetime && !$p.autodelete && $h.cancellation_allowed}<li class="nav-item"><a class="nav-link" href="#cancel" aria-controls="cancel" role="tab" data-toggle="tab">{$lang.HOSTING.CANCEL}</a></li>{/if}
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
		    	{$output}
		    	</div>
	    	</div>
	    </div>

	    <div role="tabpanel" class="tab-pane" id="settings">
	    	<div class="row">
	    		<div class="col-md-{if $add_domains}6{else}12{/if}">
	    			<div class="card">
					  <div class="card-body">
					  <h5 class="card-title">{$lang.HOSTING.NOTE}</h5>
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
	    			<div class="card">
					  <div class="card-body">
					  <h5 class="card-title">{$lang.HOSTING.DOMAIN}</h5>
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
		  {if $maxincldomains}
			<div role="tabpanel" class="tab-pane" id="incldomains">
				<div class="table-responsive">
					<table class="table table-bordered table-striped" style="margin-bottom: 0;">
						<tr>
							<th width="50%">{$lang.HOSTING.MAXINCLDOMAINS}</th>
							<td>{$maxincldomains}</td>
						</tr>
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
						<tr>
							<th width="50%">{$lang.HOSTING.OPENINCLDOMAINS}</th>
							<td>{$maxincldomains - $incldomains}</td>
						</tr>
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