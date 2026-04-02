
{assign "ql" $lang.QUOTE}
{assign "el" $lang.EINVOICE}
{assign "pl" $lang.INVOICE_EDIT}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$pl.CREATE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($success)}<div class="alert alert-success">{$success}</div>{/if}
{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

<form accept-charset="UTF-8" role="form" method="post">
	<div class="form-group">
		<label>{$ql.CUST}</label><br />
		<a href="?p=customers&edit={$user.ID}">{$user.name}</a>
	</div>

	<div class="form-group" style="position: relative;">
		<label>{$el.DATE}</label>
		<input type="text" name="date" value="{if isset($smarty.post.date)}{$smarty.post.date|htmlentities}{else}{dfop}{/if}" placeholder="{dfop}" class="form-control datepicker">
	</div>

	<div class="form-group" style="position: relative;">
		<label>{$el.DUE}</label>
		<input type="text" name="duedate" value="{if isset($smarty.post.duedate)}{$smarty.post.duedate|htmlentities}{else}{$duedate}{/if}" placeholder="{dfop}" class="form-control datepicker">
	</div>

	<div class="form-group">
		<label>{$el.POSITIONS}</label>

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
				  <div class="input-group">
						<span class="input-group-addon"><a href="#" class="select_product" data-id="#ID#"><i class="fa fa-cubes"></i></a></span>
						<textarea class="form-control" style="resize: vertical; height: 34px;"{if $edithint} disabled=""{/if} name="invoiceitem_description[#ID#]" placeholder="{$el.POSITION} #ID#"></textarea>
					</div>
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
							{$el.TAXED}
						</label>
					</div>
				</div>

				<div class="col-sm-1 delete-col" style="display: none;">
					<input type="button" class="btn btn-danger btn-block delete_position" value="{$ql.DEL}">
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

				<div class="col-sm-{if $positions > 1 || empty($smarty.post)}7{else}8{/if} resize-col-xs">
				  <div class="input-group">
						<span class="input-group-addon"><a href="#" class="select_product" data-id="{$i}"><i class="fa fa-cubes"></i></a></span>
						{if isset($smarty.post.invoiceitem_description.$i)}{assign "val" $smarty.post.invoiceitem_description.$i|htmlentities}{else}{assign "val" ""}{/if}
						<textarea class="form-control" style="resize: vertical; height: {14 + (20 * max(1, count(explode("\n", $val))))}px;" name="invoiceitem_description[{$i}]" placeholder="{$el.POSITION} {$i}">{$val}</textarea>
					</div>
				</div>

				<div class="col-sm-2">
					<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
						{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
						<input type="text" class="form-control" name="invoiceitem_amount[{$i}]" value="{if isset($smarty.post.invoiceitem_amount.$i)}{$smarty.post.invoiceitem_amount.$i|htmlentities}{/if}" placeholder="{nfop}" />
						{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
					</div>
				</div>

				<div class="col-sm-1">
					<div class="checkbox input-group-addon" style="margin-bottom: 0; height: 34px; border: 1px solid #ccc; border-radius: 4px;">
						<label>
							<input type="checkbox" name="invoiceitem_tax[{$i}]" value="1" {if isset($smarty.post.invoiceitem_tax.$i) || !isset($smarty.post.invoiceitem_amount.$i) || isset($byUrl)} checked=""{/if}>
							{$el.TAXED}
						</label>
					</div>
				</div>

				<div class="col-sm-1 delete-col"{if $positions < 2} style="display: none;"{/if}>
					<input type="button" class="btn btn-danger btn-block delete_position" value="{$ql.DEL}">
				</div>
			</div>
		</div>{/for}
		</div>

		<br /><a href="#" onclick="addRow(); return false;"><i class="fa fa-plus-square-o"></i> {$el.ADD}</a>
	</div>

	<div class="modal fade" tabindex="-1" role="dialog" id="choose_product">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-body">
					<div id="chprfo">
						<div class="row">
							<div class="col-md-7">
								<select id="chprse" class="form-control">
									{foreach from=$products item=product}
									<option data-desc="{$product.1|htmlentities}">{$product.0|htmlentities}</option>
									{/foreach}
								</select>
							</div>
							<div class="col-md-5">
								<a href="#" class="btn btn-success btn-block" id="chprbt"><i class="fa fa-check"></i></a>
							</div>
						</div>
					</div>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->

	<div class="form-group">
	<label>{$el.STATUS}</label><br />
		<label class="radio-inline">
			<input type="radio" name="status" value="0"{if (!isset($smarty.post.status)) || (isset($smarty.post.status) && $smarty.post.status == "0")} checked{/if}>
			{$el.STATUS1}
		</label>
		<label class="radio-inline">
			<input type="radio" name="status" value="1"{if isset($smarty.post.status) && $smarty.post.status == "1"} checked{/if}>
			{$el.STATUS2}
		</label>
		<label class="radio-inline">
			<input type="radio" name="status" value="3"{if isset($smarty.post.status) && $smarty.post.status == "3"} checked{/if}>
			{$el.STATUS3}
		</label>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="send_invoice" value="yes"{if isset($smarty.post.send_invoice) && $smarty.post.send_invoice == "yes"} checked="checked"{/if}> {$el.SVE}
	    </label>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="no_reminders" value="yes"{if isset($smarty.post.no_reminders) && $smarty.post.no_reminders == "yes"} checked="checked"{/if}> Mahnungen deaktivieren
	    </label>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="net" value="yes"{if (isset($smarty.post.net) && $smarty.post.net == "yes")} checked="checked"{/if}> {$el.NET}
	    </label>
	</div>

	<input type="hidden" name="projctrl" value="{$projctrl}" />

    <button type="submit" name="add_invoice" class="btn btn-primary btn-block">{$pl.DOC}</button>
</form>
