{assign "pl" $lang.INVOICE_EDIT}
{assign "ql" $lang.QUOTE}
{assign "el" $lang.EINVOICE}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$pl.TITLE} {if is_object($inv)}<small>{$inv->getInvoiceNo()|htmlentities}</small>{/if}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($success)}<div class="alert alert-success">{$success}</div>{/if}
{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

<div class="row">
	<div class="col-md-9">
		<div class="panel panel-primary">
			<div class="panel-heading">
				{$pl.SUBT}
			</div>
			<div class="panel-body">
				{if $edithint}
				<div class="alert alert-danger" id="edithint">{$el.EDITHINT} <button id="edithint_remove" class="btn btn-xs btn-primary"><i class="fa fa-check"></i></button></div>
				{/if}

				<form accept-charset="UTF-8" role="form" method="post">
					<div class="form-group">
						<label>{$ql.CUST}</label>
						<input type="text" class="form-control customer-input editprev"{if $edithint} disabled=""{/if} placeholder="{$pl.SEARCHC}" value="{$ci}">
						<input type="hidden" name="client" value="{if is_object($inv)}{$inv->getClient()}{/if}">
						<div class="customer-input-results"></div>
					</div>

					<div class="form-group" style="position: relative;">
						<label>{$el.DATE}</label>
						<input type="text" name="date" value="{if isset($smarty.post.date)}{$smarty.post.date|htmlentities}{else}{$date|htmlentities}{/if}" placeholder="{dfop}" class="form-control datepicker editprev"{if $edithint} disabled=""{/if}>
					</div>

					<div class="form-group" style="position: relative;">
						<label>{$el.DD}</label>
						<input type="text" name="deliverydate" value="{if isset($smarty.post.deliverydate)}{$smarty.post.deliverydate|htmlentities}{else}{$deliverydate|htmlentities}{/if}" placeholder="{dfop}" class="form-control datepicker editprev"{if $edithint} disabled=""{/if}>
					</div>

					<div class="form-group" style="position: relative;">
						<label>{$el.DUE}</label>
						<input type="text" name="duedate" value="{if isset($smarty.post.duedate)}{$smarty.post.duedate|htmlentities}{else}{$duedate|htmlentities}{/if}" placeholder="{dfop}" class="form-control datepicker editprev"{if $edithint} disabled=""{/if}>
					</div>

					<div class="form-group">
						<label>{$el.POSITIONS}</label>

						<div style="margin-top: -15px;">
						<div id="invoiceitem_template" style="display: none; margin-top: 15px;">
							<div class="row">
								<div class="col-sm-1">
									<input type="text" class="form-control editprev"{if $edithint} disabled=""{/if} name="invoiceitem_qty[#ID#]" value="{nfo i=1}" placeholder="{nfo i=1}" />
								</div>

								<div class="col-sm-1">
									<input type="text" class="form-control editprev"{if $edithint} disabled=""{/if} name="invoiceitem_unit[#ID#]" value="x" placeholder="x" />
								</div>

								<div class="col-sm-7 resize-col-xs">
									<div class="input-group">
										<span class="input-group-addon"><a href="#" class="select_product" data-id="#ID#"><i class="fa fa-cubes"></i></a></span>
										<input type="text" class="form-control editprev"{if $edithint} disabled=""{/if} name="invoiceitem_description[#ID#]" placeholder="{$el.POSITION} #ID#" />
									</div>
								</div>

								<div class="col-sm-2">
									<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
										{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
										<input type="text" class="form-control editprev"{if $edithint} disabled=""{/if} name="invoiceitem_amount[#ID#]" placeholder="{nfop}" />
										{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
									</div>
								</div>

								<div class="col-sm-1">
									<div class="checkbox input-group-addon" style="margin-bottom: 0; height: 34px; border: 1px solid #ccc; border-radius: 4px; padding: 0;">
										<label>
											<input type="checkbox" name="invoiceitem_tax[#ID#]" value="1" checked="" class="editprev"{if $edithint} disabled=""{/if}>
											{$pl.TAXED}
										</label>
									</div>
								</div>

								<div class="col-sm-1 delete-col" style="display: none;">
									<input type="button" class="btn btn-danger btn-block delete_position editprev"{if $edithint} disabled=""{/if} value="{$ql.DEL}">
								</div>
							</div>
						</div>

						{for $i=1 to $positions}<div class="invoiceitem" id="invoiceitem_{$i}" style="margin-top: 15px;">
							<div class="row">
								<div class="col-sm-1">
									<input type="text" class="form-control editprev"{if $edithint} disabled=""{/if} name="invoiceitem_qty[{$i}]" value="{if isset($smarty.post.invoiceitem_qty.$i)}{$smarty.post.invoiceitem_qty.$i|htmlentities}{else}{$pq.$i|htmlentities}{/if}" placeholder="{nfo i=1}" />
								</div>

								<div class="col-sm-1">
									<input type="text" class="form-control editprev"{if $edithint} disabled=""{/if} name="invoiceitem_unit[{$i}]" value="{if isset($smarty.post.invoiceitem_unit.$i)}{$smarty.post.invoiceitem_unit.$i|htmlentities}{else}{$pu.$i|htmlentities}{/if}" placeholder="x" />
								</div>

								<div class="col-sm-{if $positions > 1}6{else}7{/if} resize-col-xs">
									<div class="input-group">
										<span class="input-group-addon"><a href="#" class="select_product" data-id="{$i}"><i class="fa fa-cubes"></i></a></span>
										{if isset($smarty.post.invoiceitem_description.$i)}{assign "val" $smarty.post.invoiceitem_description.$i|htmlentities}{else}{assign "val" $pd.$i|replace:"<br/>":"\n"|replace:"<br />":"\n"|replace:"<br>":"\n"|htmlentities}{/if}
										<textarea class="form-control editprev" style="resize: vertical; height: {14 + (20 * max(1, count(explode("\n", $val))))}px;"{if $edithint} disabled=""{/if} name="invoiceitem_description[{$i}]" placeholder="{$el.POSITION} {$i}">{$val}</textarea>
									</div>
								</div>

								<div class="col-sm-2">
									<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
										{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
										<input type="text" class="form-control editprev"{if $edithint} disabled=""{/if} name="invoiceitem_amount[{$i}]" value="{if isset($smarty.post.invoiceitem_amount.$i)}{$smarty.post.invoiceitem_amount.$i|htmlentities}{else}{$pa.$i|htmlentities}{/if}" placeholder="{nfop}" />
										{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
									</div>
								</div>

								<div class="col-sm-1">
									<div class="checkbox input-group-addon" style="margin-bottom: 0; height: 34px; border: 1px solid #ccc; border-radius: 4px; padding: 0;">
										<label>
											<input type="checkbox" name="invoiceitem_tax[{$i}]" value="1" {if array_key_exists($i, $pt)} checked=""{/if} class="editprev"{if $edithint} disabled=""{/if}>
											{$pl.TAXED}
										</label>
									</div>
								</div>

								<div class="col-sm-1 delete-col"{if $positions < 2} style="display: none;"{/if}>
									<input type="button" class="btn btn-danger btn-block delete_position editprev"{if $edithint} disabled=""{/if} value="{$ql.DEL}">
								</div>
							</div>
						</div>{/for}
						</div>

						<br /><a href="#" onclick="addRow(); return false;"><i class="fa fa-plus-square-o"></i> {$el.ADD}</a>
					</div>

					<div class="checkbox">
						<label>
						<input type="checkbox" name="no_reminders" value="yes"{if (!isset($smarty.post.date) && $noReminders) || (isset($smarty.post.no_reminders) && $smarty.post.no_reminders == "yes")} checked="checked"{/if} class="editprev"{if $edithint} disabled=""{/if}> {$el.NR}
						</label>
					</div>

					<div class="checkbox">
						<label>
						<input type="checkbox" name="net" value="yes"{if (isset($smarty.post.net) && $smarty.post.net == "yes")} checked="checked"{/if} class="editprev"{if $edithint} disabled=""{/if}> {$el.NET}
						</label>
					</div>

					<button type="submit" name="save_invoice" class="btn btn-primary btn-block editprev"{if $edithint} disabled=""{/if}>{$el.SAVE}</button>
				</form>
			</div>
		</div>
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

	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">{$pl.FILES}<a href="#" data-toggle="modal" data-target="#uploadInvoiceFile" class="pull-right"><i class="fa fa-plus"></i></a></div>
			<div class="panel-body" style="text-align: justify;">
				{if $files|@count}
					<ul style="margin-bottom: 0;">
					{foreach from=$files item=file}
					<li>
						<a href="?p=invoice&id={$smarty.get.id|intval}&download_file={$file|urlencode}" target="_blank">{$file|htmlentities}</a>
						<a href="?p=invoice&id={$smarty.get.id|intval}&delete_file={$file|urlencode}" class="pull-right"><i class="fa fa-times"></i></a>
					</li>
					{/foreach}
					</ul>
				{else}
					<i>{$pl.NOFILES}</i>
				{/if}
			</div>
		</div>

		<div class="modal fade" id="uploadInvoiceFile" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<form method="POST" enctype="multipart/form-data" role="form">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title">{$pl.UPLOAD}</h4>
						</div>
						<div class="modal-body">
							<div class="form-group" style="margin-bottom: 0;">
								<input type="file" class="form-control" name="upload_files[]" multiple>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
							<button type="submit" class="btn btn-primary">{$pl.UPLOAD}</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>