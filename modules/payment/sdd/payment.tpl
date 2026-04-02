<div id="content">
    <div class="container">
        <h1>{$glang.NAME}</h1><hr />

        {if $suc}
        <div class="alert alert-success">{$glang.SUC1}</div>
        {$glang.SUC2}
        {else}
        {if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

        {$glang.CINTRO}

        <br /><br />
        <form method="POST"><div class="table-responsive">
        	<table class="table table-bordered table-striped" style="margin-bottom: 0;">
	        	<tr>
	        		<th width="30%" style="text-align: right;">{$glang.AMOUNT}</th>
	        		<td width="70%">{$amount}</td>
	        		<input type="hidden" name="amount" value="{$rawamount}" />
	        	</tr>

	        	<tr>
	        		<th width="30%" style="text-align: right; vertical-align: middle;">{$glang.ACCOUNT}</th>
	        		<td width="70%">
	        			<select name="account" class="form-control input-sm">
	        				{foreach from=$accounts item=v key=k}
	        				<option value="{$k}"{if isset($smarty.post.account) && $smarty.post.account == $k} selected="selected"{/if}>{$v}</option>
	        				{/foreach}
	        			</select>
	        		</td>
	        		<input type="hidden" name="token" value="{$token}" />
	        	</tr>
	        </table>
        </div>

        <div class="checkbox">
        	<label>
        		<input type="checkbox" id="confirm">
        		{$glang.CONFIRM}
        	</label>
        </div>

        <input type="submit" class="btn btn-block btn-primary" id="submit" value="{$glang.DO}" disabled="disabled" /></form>

        <script>
        $("#confirm").click(function(){
        	$("#submit").prop("disabled", !$(this).is(":checked"));
        });
        </script>
        {/if}
    </div>
</div>
