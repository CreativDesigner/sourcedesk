<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.VOUCHERS.TITLE}{if $tab != "create"}<a class="pull-right" href="./?p=vouchers&tab=create"><i class="fa fa-plus-circle"></i></a>{/if}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="row">
	<div class="col-md-3">
		<div class="list-group">
			<a class="list-group-item{if $tab == "active"} active{/if}" href="./?p=vouchers">{$lang.VOUCHERS.ACTIVE}</a>
			<a class="list-group-item{if $tab == "inactive"} active{/if}" href="./?p=vouchers&tab=inactive">{$lang.VOUCHERS.INACTIVE}</a>
		</div>
	</div>

	<div class="col-md-9">
	
	{if $tab == "active" && !isset($edit)}

	{if isset($list_msg)}{$list_msg}{/if}
	
	{$th}

	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
				<th>{$lang.VOUCHERS.VOUCHER}</th>
				<th>{$lang.VOUCHERS.VALUE}</th>
				<th>{$lang.VOUCHERS.USES}</th>
				<th>{$lang.VOUCHERS.VALIDITY}</th>
				<th width="50px"></th>
			</tr>
			
			<form method="POST">
			{foreach from=$vouchers item=voucher}
				<tr>
					<td><input type="checkbox" class="checkbox" name="voucher[]" value="{$voucher.ID}" onchange="javascript:toggle();" /></td>
					<td>{$voucher.code|htmlentities} &nbsp; <a href="./?p=vouchers&tab=active&pause={$voucher.ID}"><i class="fa fa-pause"></i></a></td>
					<td>{$voucher.value_f}</td>
					<td>{$voucher.uses}{if $voucher.max_uses >= 0} / {$voucher.max_uses}{/if}</td>
					<td>{dfo d=$voucher.valid_to m=0}</td>
					<td><a href="./?p=vouchers&tab=active&edit={$voucher.ID}"><i class="fa fa-pencil-square-o"></i></a>&nbsp;<a onclick="return confirm('{$lang.VOUCHERS.DELETE_REALLY}')" href="./?p=vouchers&tab=active&delete={$voucher.ID}"><i class="fa fa-times fa-lg"></i></a></td>
				</tr>
			{foreachelse}
				<tr>
					<td colspan="6"><center>{$lang.VOUCHERS.NOTHING}</center></td>
				</tr>
			{/foreach}
		</table>
	</div>{$lang.GENERAL.SELECTED}: <input type="submit" name="deactivate_selected" value="{$lang.VOUCHERS.DEACTIVATE}" class="btn btn-warning" /> <input type="submit" name="delete_selected" value="{$lang.GENERAL.DELETE}" class="btn btn-danger" /></form>

	{$tf}

	{else if $tab == "inactive" && !isset($edit)}

	<ul class="nav nav-pills nav-justified">
	    <li{if $filter == "none"} class="active"{/if}><a href="./?p=vouchers&tab=inactive">{$lang.VOUCHERS.ALL}</a></li>
	    <li{if $filter == "expired"} class="active"{/if}><a href="./?p=vouchers&tab=inactive&filter=expired">{$lang.VOUCHERS.EXPIRED}</a></li>
	    <li{if $filter == "waiting"} class="active"{/if}><a href="./?p=vouchers&tab=inactive&filter=waiting">{$lang.VOUCHERS.WAITING}</a></li>
	    <li{if $filter == "used"} class="active"{/if}><a href="./?p=vouchers&tab=inactive&filter=used">{$lang.VOUCHERS.USED}</a></li>
		<li{if $filter == "deactivated"} class="active"{/if}><a href="./?p=vouchers&tab=inactive&filter=deactivated">{$lang.VOUCHERS.DEACTIVATED}</a></li>
	</ul><br />

	{if isset($list_msg)}{$list_msg}{/if}
	
	{$th}

	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
				<th>{$lang.VOUCHERS.VOUCHER}</th>
				<th>{$lang.VOUCHERS.VALUE}</th>
				<th>{$lang.VOUCHERS.USES}</th>
				<th>{$lang.VOUCHERS.VALIDITY}</th>
				<th width="50px"></th>
			</tr>
			
			<form method="POST">
			{foreach from=$vouchers item=voucher}
				<tr>
					<td><input type="checkbox" class="checkbox" name="voucher[]" value="{$voucher.ID}" onchange="javascript:toggle();" /></td>
					<td>{$voucher.code|htmlentities}{if $voucher.active == 0} &nbsp; <a href="./?p=vouchers&tab=inactive&filter={$filter}&resume={$voucher.ID}"><i class="fa fa-play"></i></a>{/if}</td>
					<td>{$voucher.value_f}</td>
					<td>{$voucher.uses}{if $voucher.max_uses >= 0} / {$voucher.max_uses}{/if}</td>
					<td>{if $voucher.valid_from > 0}{dfo d=$voucher.valid_from m=0} - {/if}{dfo d=$voucher.valid_to m=0}</td>
					<td><a href="./?p=vouchers&tab=inactive&filter={$filter}&edit={$voucher.ID}"><i class="fa fa-pencil-square-o"></i></a>&nbsp;<a onclick="return confirm('{$lang.VOUCHERS.DELETE_REALLY}')" href="./?p=vouchers&tab=inactive&filter={$filter}&delete={$voucher.ID}"><i class="fa fa-times fa-lg"></i></a></td>
				</tr>
			{foreachelse}
				<tr>
					<td colspan="6"><center>{$lang.VOUCHERS.NOTHING_I}</center></td>
				</tr>
			{/foreach}
		</table>
	</div>{$lang.GENERAL.SELECTED}: <input type="submit" name="activate_selected" value="{$lang.VOUCHERS.ACTIVATE}" class="btn btn-warning" /> <input type="submit" name="delete_selected" value="{$lang.GENERAL.DELETE}" class="btn btn-danger" /></form>

	{$tf}

	{else if $tab == "create" && !isset($edit)}
	{if isset($create_msg)}{$create_msg}{/if}

	<form accept-charset="UTF-8" role="form" method="post">
		<div class="form-group">
			<label>{$lang.VOUCHERS.CODE}</label>
			<input type="text" name="voucher_code" value="{if isset($smarty.post.voucher_code)}{$smarty.post.voucher_code|htmlentities}{else}{$generated_code}{/if}" placeholder="{$lang.VOUCHERS.CODE}" class="form-control">
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.VALUE}</label>
			<input type="text" name="voucher_value" style="margin-bottom:5px;" value="{if isset($smarty.post.voucher_value)}{$smarty.post.voucher_value|htmlentities}{/if}" placeholder="{nfo i=100}" class="form-control">

			<label class="radio-inline">
				<input type="radio" name="voucher_type" value="percentage"{if !isset($smarty.post.voucher_type) || $smarty.post.voucher_type == "percentage"} checked{/if}>
				{$lang.VOUCHERS.PERCENTAGE}
				</label>
			<label class="radio-inline">
				<input type="radio" name="voucher_type" value="fixed"{if isset($smarty.post.voucher_type) && $smarty.post.voucher_type != "percentage"} checked{/if}>
				{$lang.VOUCHERS.FIXED}
			</label>
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.MAX_USES}</label>
			<input type="text" name="voucher_max_uses" value="{if isset($smarty.post.voucher_max_uses)}{$smarty.post.voucher_max_uses|htmlentities}{else}-1{/if}" placeholder="100" class="form-control">
			<p class="help-block">{$lang.VOUCHERS.MAX_USES_HINT}</p>
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.MAX_PER_USER}</label>
			<input type="text" name="voucher_max_per_user" value="{if isset($smarty.post.voucher_max_per_user)}{$smarty.post.voucher_max_per_user|htmlentities}{else}-1{/if}" placeholder="1" class="form-control">
			<p class="help-block">{$lang.VOUCHERS.MAX_PER_USER_HINT}</p>
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.USER}</label>
			<input type="text" class="form-control customer-input" placeholder="{$lang.VOUCHERS.ALL}" value="{$ci}">
			<input type="hidden" name="voucher_user" value="{$cid}">
			<div class="customer-input-results"></div>
		</div>

		<div class="form-group">
		    <label>{$lang.VOUCHERS.VALIDITY_PERIOD}</label>
		    <div class="input-group" style="position: relative;">
		      <div class="input-group-addon">{$lang.VOUCHERS.FROM}</div>
		      <input type="text" class="form-control datepicker" name="voucher_valid_from" placeholder="{dfo d=$smarty.now m=false}" value="{if isset($smarty.post.voucher_valid_from)}{$smarty.post.voucher_valid_from|htmlentities}{else}{dfo d=$smarty.now m=false}{/if}">
		    </div>
		    
		    <div class="input-group" style="margin-top: 5px; position: relative;">
		      <div class="input-group-addon">{$lang.VOUCHERS.TO}</div>
		      <input type="text" class="form-control datepicker" name="voucher_valid_to" value="{if isset($smarty.post.voucher_valid_to)}{$smarty.post.voucher_valid_to|htmlentities}{/if}" placeholder="{dfo d=($smarty.now + 86400 * 5) m=false}">
		    </div>
    	</div>

    	<div class="form-group">
		    <label>{$lang.VOUCHERS.VALID_FOR}</label>
		    <div class="checkbox" style="margin-top: 0;">
			  <label>
			    <input type="checkbox" name="voucher_valid_for_all" onchange="javascript:validForAll(this.checked);" value="yes"{if !isset($smarty.post.create_voucher) || $smarty.post.voucher_valid_for_all == "yes"} checked{/if}>
			    {$lang.VOUCHERS.VALID_FOR_ALL}
			  </label>
			</div>

			<div id="#valid_chooser"{if !isset($smarty.post.create_voucher) || $smarty.post.voucher_valid_for_all == "yes"} style="display: none;"{/if}>
				<select name="voucher_valid_for[]" class="form-control" multiple style="width: 100%; height: 100px;">
					{foreach from=$valid_for_possibilities key=key item=text}
					<option value="{$key}"{if is_array($smarty.post.voucher_valid_for) && $key|in_array:$smarty.post.voucher_valid_for} selected="selected"{/if}>{$text}</option>
					{/foreach}
				</select>
				<p class="help-block">{$lang.VOUCHERS.VALID_FOR_HINT}</p>
			</div>
    	</div>

    	<script type="text/javascript">
    		function validForAll(checked) {
    			if(checked)
    				document.getElementById("#valid_chooser").style.display = "none";
    			else
    				document.getElementById("#valid_chooser").style.display = "block";
    		}
    	</script>

		<div class="form-group">
		<label>{$lang.VOUCHERS.STATUS}</label><br />
			<label class="radio-inline">
				<input type="radio" name="voucher_active" value="1"{if !isset($smarty.post.voucher_type) || $smarty.post.voucher_type == "1"} checked{/if}>
				{$lang.VOUCHERS.OACTIVE}
			</label>
			<label class="radio-inline">
				<input type="radio" name="voucher_active" value="0"{if isset($smarty.post.voucher_type) && $smarty.post.voucher_type != "1"} checked{/if}>
				{$lang.VOUCHERS.OINACTIVE}
			</label>
		</div>

	    <center><button type="submit" name="create_voucher" class="btn btn-primary btn-block">{$lang.VOUCHERS.CREATE}</button><br /></center>
	 </form>
	{else if !isset($edit)}
	<div class="alert alert-danger">{$lang.GENERAL.SUBPAGE_NOT_FOUND}</div>
	{else}
	{if isset($edit_msg)}{$edit_msg}{/if}
	<form accept-charset="UTF-8" role="form" method="post">
		<div class="form-group">
			<label>{$lang.VOUCHERS.CODE}</label>
			<input type="text" name="voucher_code" value="{if isset($smarty.post.voucher_code)}{$smarty.post.voucher_code|htmlentities}{else}{$data.code|htmlentities}{/if}" placeholder="{$lang.VOUCHERS.CODE}" class="form-control">
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.VALUE}</label>
			<input type="text" name="voucher_value" style="margin-bottom:5px;" value="{if isset($smarty.post.voucher_value)}{$smarty.post.voucher_value|htmlentities}{else}{nfo i=$data.value}{/if}" placeholder="{nfo i=100}" class="form-control">

			<label class="radio-inline">
				<input type="radio" name="voucher_type" value="percentage"{if (!isset($smarty.post.voucher_type) && $data.type == "percentage") || (isset($smarty.post.voucher_type) && $smarty.post.voucher_type == "percentage")} checked{/if}>
				{$lang.VOUCHERS.PERCENTAGE}
				</label>
			<label class="radio-inline">
				<input type="radio" name="voucher_type" value="fixed"{if (!isset($smarty.post.voucher_type) && $data.type != "percentage") || (isset($smarty.post.voucher_type) && $smarty.post.voucher_type != "percentage")} checked{/if}>
				{$lang.VOUCHERS.FIXED}
			</label>
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.MAX_USES}</label>
			<input type="text" name="voucher_max_uses" value="{if isset($smarty.post.voucher_max_uses)}{$smarty.post.voucher_max_uses|htmlentities}{else}{$data.max_uses|htmlentities}{/if}" placeholder="100" class="form-control">
			<p class="help-block">{$lang.VOUCHERS.MAX_USES_HINT}</p>
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.MAX_PER_USER}</label>
			<input type="text" name="voucher_max_per_user" value="{if isset($smarty.post.voucher_max_per_user)}{$smarty.post.voucher_max_per_user|htmlentities}{else}{$data.max_per_user|htmlentities}{/if}" placeholder="1" class="form-control">
			<p class="help-block">{$lang.VOUCHERS.MAX_PER_USER_HINT}</p>
		</div>

		<div class="form-group">
			<label>{$lang.VOUCHERS.USER}</label>
			<input type="text" class="form-control customer-input" placeholder="{$lang.VOUCHERS.ALL}" value="{$ci}">
			<input type="hidden" name="voucher_user" value="{$cid}">
			<div class="customer-input-results"></div>
		</div>

		<div class="form-group">
		    <label>{$lang.VOUCHERS.VALIDITY_PERIOD}</label>
		    <div class="input-group" style="position: relative;">
		      <div class="input-group-addon">{$lang.VOUCHERS.FROM}</div>
		      <input type="text" class="form-control datepicker" name="voucher_valid_from" placeholder="{dfo d=$smarty.now m=false}" value="{if isset($smarty.post.voucher_valid_from)}{$smarty.post.voucher_valid_from|htmlentities}{else}{dfo d=$data.valid_from m=false}{/if}">
		    </div>
		    
		    <div class="input-group" style="margin-top: 5px; position: relative;">
		      <div class="input-group-addon">{$lang.VOUCHERS.TO}</div>
		      <input type="text" class="form-control datepicker" name="voucher_valid_to" value="{if isset($smarty.post.valid_to)}{$smarty.post.valid_to|htmlentities}{else}{dfo d=$data.valid_to m=false}{/if}" placeholder="{dfo d=($smarty.now + 86400 * 5) m=false}">
		    </div>
    	</div>

    	<div class="form-group">
		    <label>{$lang.VOUCHERS.VALID_FOR}</label>
		    <div class="checkbox" style="margin-top: 0;">
			  <label>
			    <input type="checkbox" name="voucher_valid_for_all" onchange="javascript:validForAll(this.checked);" value="yes"{if (isset($smarty.post.voucher_valid_for_all) && $smarty.post.voucher_valid_for_all == "yes") || (!isset($smarty.post.edit_voucher) && $data.valid_for == "all")} checked{/if}>
			    {$lang.VOUCHERS.VALID_FOR_ALL}
			  </label>
			</div>

			<div id="valid_chooser"{if (isset($smarty.post.voucher_valid_for_all) && $smarty.post.voucher_valid_for_all == "yes") || (!isset($smarty.post.edit_voucher) && $data.valid_for == "all")} style="display: none;"{/if}>
				<select name="voucher_valid_for[]" class="form-control" multiple style="width: 100%; height: 100px;">
					{foreach from=$valid_for_possibilities key=key item=text}
					<option value="{$key}"{if (is_array($smarty.post.voucher_valid_for) && $key|in_array:$smarty.post.voucher_valid_for) || (!isset($smarty.post.voucher_valid_for) && is_array(unserialize($data.valid_for)) && $key|in_array:unserialize($data.valid_for))} selected="selected"{/if}>{$text}</option>
					{/foreach}
				</select>
				<p class="help-block">{$lang.VOUCHERS.VALID_FOR_HINT}</p>
			</div>
    	</div>

    	{literal}<script type="text/javascript">
    		function validForAll(checked) {
    			if(checked)
    				document.getElementById("valid_chooser").style.display = "none";
    			else
    				document.getElementById("valid_chooser").style.display = "block";
    		}
    	</script>{/literal}

		<div class="form-group">
		<label>{$lang.VOUCHERS.STATUS}</label><br />
			<label class="radio-inline">
				<input type="radio" name="voucher_active" value="1"{if (!isset($smarty.post.voucher_active) && $data.active == "1") || (isset($smarty.post.voucher_active) && $smarty.post.voucher_active == "1")} checked{/if}>
				{$lang.VOUCHERS.OACTIVE}
			</label>
			<label class="radio-inline">
				<input type="radio" name="voucher_active" value="0"{if (!isset($smarty.post.voucher_active) && $data.active == "0") || (isset($smarty.post.voucher_active) && $smarty.post.voucher_active == "0")} checked{/if}>
				{$lang.VOUCHERS.OINACTIVE}
			</label>
		</div>

	    <center><button type="submit" name="edit_voucher" class="btn btn-primary btn-block">{$lang.VOUCHERS.EDIT}</button><br /></center>
	 </form>
	{/if}
</div></div>