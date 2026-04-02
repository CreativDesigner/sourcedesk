{assign "pl" $lang.EINVOICE}
{assign "cl" $lang.QUOTE}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$pl.TITLE} {if is_object($inv)}<small>{$inv->getInvoiceNo()}</small>{/if}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($success)}<div class="alert alert-success">{$success}</div>{/if}
{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

<form accept-charset="UTF-8" role="form" method="post">
	{if $edithint}
	<div class="alert alert-warning">{$pl.EDITHINT}</div>
	{/if}

	<div class="form-group" style="position: relative;">
		<label>{$pl.DATE}</label>
		<input type="text" name="date" value="{if isset($smarty.post.date)}{$smarty.post.date|htmlentities}{else}{$date|htmlentities}{/if}" placeholder="{dfop}" class="form-control datepicker">
	</div>

	<div class="form-group" style="position: relative;">
			<label>{$pl.DD}</label>
			<input type="text" name="deliverydate" value="{if isset($smarty.post.deliverydate)}{$smarty.post.deliverydate|htmlentities}{else}{$deliverydate|htmlentities}{/if}" placeholder="{dfop}" class="form-control datepicker">
		</div>

	<div class="form-group" style="position: relative;">
		<label>{$pl.DUE}</label>
		<input type="text" name="duedate" value="{if isset($smarty.post.duedate)}{$smarty.post.duedate|htmlentities}{else}{$duedate|htmlentities}{/if}" placeholder="{dfop}" class="form-control datepicker">
	</div>

	<div class="form-group">
		<label>{$pl.CN}</label>
		<input type="text" name="customno" value="{if isset($smarty.post.customno)}{$smarty.post.customno|htmlentities}{elseif is_object($inv)}{$inv->getCustomNo()|htmlentities}{/if}" placeholder="{$pl.OPTIONAL}" class="form-control">
	</div>

	<div class="form-group">
		<label>{$pl.CD}</label><br />
		<div class="row">
			<div class="col-md-4">
				<input type="text" name="firstname" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname|htmlentities}{else}{$ii.firstname|htmlentities}{/if}" class="form-control" placeholder="{$cl.FN}" />
			</div>

			<div class="col-md-4">
				<input type="text" name="lastname" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname|htmlentities}{else}{$ii.lastname|htmlentities}{/if}" class="form-control" placeholder="{$cl.LN}" />
			</div>

			<div class="col-md-4">
				<input type="text" name="company" value="{if isset($smarty.post.company)}{$smarty.post.company|htmlentities}{else}{$ii.company|htmlentities}{/if}" class="form-control" placeholder="{$cl.CP}" />
			</div>
		</div>

		<div class="row" style="margin-top: 10px;">
			<div class="col-md-10">
				<input type="text" name="street" value="{if isset($smarty.post.street)}{$smarty.post.street|htmlentities}{else}{$ii.street|htmlentities}{/if}" class="form-control" placeholder="{$cl.ST}" />
			</div>

			<div class="col-md-2">
				<input type="text" name="street_number" value="{if isset($smarty.post.street_number)}{$smarty.post.street_number|htmlentities}{else}{$ii.street_number|htmlentities}{/if}" class="form-control" placeholder="{$cl.SN}" />
			</div>
		</div>

		<div class="row" style="margin-top: 10px;">
			<div class="col-md-4">
				<input type="text" name="postcode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode|htmlentities}{else}{$ii.postcode|htmlentities}{/if}" class="form-control" placeholder="{$cl.PC}" />
			</div>

			<div class="col-md-4">
				<input type="text" name="city" value="{if isset($smarty.post.city)}{$smarty.post.city|htmlentities}{else}{$ii.city|htmlentities}{/if}" class="form-control" placeholder="{$cl.CT}" />
			</div>

			<div class="col-md-4">
				<select name="icountry" class="form-control">
					{foreach from=$countries item=name key=id}
					<option value="{$id}"{if (isset($smarty.post.icountry) && $smarty.post.icountry == $id) || (!isset($smarty.post.icountry) && $ii.country == $name)} selected="selected"{/if}>{$name}</option>
					{/foreach}
				</select>
			</div>
		</div>

		<div class="row" style="margin-top: 10px;">
			<div class="col-md-4">
				<input type="text" name="email" value="{if isset($smarty.post.email)}{$smarty.post.email|htmlentities}{else}{$ii.email|htmlentities}{/if}" class="form-control" placeholder="{$cl.EM}" />
			</div>

			<div class="col-md-4">
				<input type="text" name="vatid" value="{if isset($smarty.post.vatid)}{$smarty.post.vatid|htmlentities}{else}{$ii.vatid|htmlentities}{/if}" class="form-control" placeholder="{$pl.VAT}" />
			</div>

			<div class="col-md-4">
				<select name="language" class="form-control">
					{foreach from=$languages item=name key=id}
					<option value="{$id}"{if (isset($smarty.post.language) && $smarty.post.language == $id) || (!isset($smarty.post.language) && is_object($inv) && $inv->getLanguage() == $id)} selected="selected"{/if}>{$name}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label>{$pl.BILL}</label>
		<div class="row">
			<div class="col-md-4">
				<input type="text" name="ptax0" value="{if isset($smarty.post.ptax0)}{$smarty.post.ptax0|htmlentities}{else}{$ii.ptax.0|htmlentities}{/if}" class="form-control" placeholder="{$pl.TAX}" />
			</div>

			<div class="col-md-4"><div class="input-group">
				<input type="text" name="ptax1" value="{if isset($smarty.post.ptax1)}{$smarty.post.ptax1|htmlentities}{else}{nfo i=$ii.ptax.1}{/if}" class="form-control" placeholder="{$pl.TAXR}" />
				<span class="input-group-addon">%</span>
			</div></div>

			<div class="col-md-4">
				<select name="currency" class="form-control">
					{foreach from=$currencies item=name key=id}
					<option value="{$id}"{if (isset($smarty.post.currency) && $smarty.post.currency == $id) || (!isset($smarty.post.currency) && $ii.currency == $id)} selected="selected"{/if}>{$name}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<div class="row">
			<div class="col-md-6">
				<label>{$pl.PAID}</label>
				<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
					{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
					<input type="text" class="form-control" name="paid_amount" placeholder="{nfop}" value="{if isset($smarty.post.paid_amount)}{$smarty.post.paid_amount|htmlentities}{elseif is_object($inv)}{nfo i=$inv->getPaidAmount()}{/if}" />
					{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
				</div>
			</div>

			<div class="col-md-6">
				<label>{$pl.LF}</label>
				<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
					{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
					<input type="text" class="form-control" name="latefee" placeholder="{nfop}" value="{if isset($smarty.post.latefee)}{$smarty.post.latefee|htmlentities}{elseif is_object($inv)}{nfo i=$inv->getLateFees()}{/if}" />
					{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label>{$pl.POSITIONS}</label>

		<div style="margin-top: -15px;">
		<div id="invoiceitem_template" style="display: none; margin-top: 15px;">
			<div class="row">
				<div class="col-sm-1">
					<input type="text" class="form-control" name="invoiceitem_qty[#ID#]" value="{nfo i=1}" placeholder="{nfo i=1}" />
				</div>

				<div class="col-sm-1">
					<input type="text" class="form-control" name="invoiceitem_unit[#ID#]" value="x" placeholder="x" />
				</div>

				<div class="col-sm-7 resize-col-xs">
					<input type="text" class="form-control" name="invoiceitem_description[#ID#]" placeholder="{$pl.POSITION} #ID#" />
				</div>

				<div class="col-sm-2">
					<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
						{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
						<input type="text" class="form-control" name="invoiceitem_amount[#ID#]" placeholder="{nfop}" />
						{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
					</div>
				</div>

				<div class="col-sm-1">
					<div class="checkbox input-group-addon" style="margin-bottom: 0; height: 34px; border: 1px solid #ccc; border-radius: 4px;">
						<label>
							<input type="checkbox" name="invoiceitem_tax[#ID#]" value="1" checked="">
							{$pl.TAXED}
						</label>
					</div>
				</div>

				<div class="col-sm-1 delete-col" style="display: none;">
					<input type="button" class="btn btn-danger btn-block delete_position" value="{$cl.DEL}">
				</div>
			</div>
		</div>

		{for $i=1 to $positions}<div class="invoiceitem" id="invoiceitem_{$i}" style="margin-top: 15px;">
			<div class="row">
				<div class="col-sm-1">
					<input type="text" class="form-control" name="invoiceitem_qty[{$i}]" value="{if isset($smarty.post.invoiceitem_qty.$i)}{$smarty.post.invoiceitem_qty.$i|htmlentities}{else}{$pq.$i|htmlentities}{/if}" placeholder="{nfo i=1}" />
				</div>

				<div class="col-sm-1">
					<input type="text" class="form-control" name="invoiceitem_unit[{$i}]" value="{if isset($smarty.post.invoiceitem_unit.$i)}{$smarty.post.invoiceitem_unit.$i|htmlentities}{else}{$pu.$i|htmlentities}{/if}" placeholder="x" />
				</div>

				<div class="col-sm-{if $positions > 1}6{else}7{/if} resize-col-xs">
					<input type="text" class="form-control" name="invoiceitem_description[{$i}]" value="{if isset($smarty.post.invoiceitem_description.$i)}{$smarty.post.invoiceitem_description.$i|htmlentities}{else}{$pd.$i|htmlentities}{/if}" placeholder="{$pl.POSITION} {$i}" />
				</div>

				<div class="col-sm-2">
					<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
						{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
						<input type="text" class="form-control" name="invoiceitem_amount[{$i}]" value="{if isset($smarty.post.invoiceitem_amount.$i)}{$smarty.post.invoiceitem_amount.$i|htmlentities}{else}{$pa.$i|htmlentities}{/if}" placeholder="{nfop}" />
						{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
					</div>
				</div>

				<div class="col-sm-1">
					<div class="checkbox input-group-addon" style="margin-bottom: 0; height: 34px; border: 1px solid #ccc; border-radius: 4px;">
						<label>
							<input type="checkbox" name="invoiceitem_tax[{$i}]" value="1" {if array_key_exists($i, $pt)} checked=""{/if}>
							{$pl.TAXED}
						</label>
					</div>
				</div>

				<div class="col-sm-1 delete-col"{if $positions < 2} style="display: none;"{/if}>
					<input type="button" class="btn btn-danger btn-block delete_position" value="{$cl.DEL}">
				</div>
			</div>
		</div>{/for}
		</div>

		<br /><a href="#" onclick="addRow(); return false;"><i class="fa fa-plus-square-o"></i> {$pl.ADD}</a>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="no_reminders" value="yes"{if (!isset($smarty.post.date) && $noReminders) || (isset($smarty.post.no_reminders) && $smarty.post.no_reminders == "yes")} checked="checked"{/if}> {$pl.NR}
	    </label>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="net" value="yes"{if (isset($smarty.post.net) && $smarty.post.net == "yes")} checked="checked"{/if}> {$pl.NET}
	    </label>
	</div>

    <button type="submit" name="save_invoice" class="btn btn-primary btn-block">{$pl.SAVE}</button>
</form>