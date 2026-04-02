{assign "pl" $lang.QUOTE}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$pl.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($success)}<div class="alert alert-success">{$success}</div>{/if}
{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

<div class="row">
	<div class="col-md-9 fix-width">
		<div class="panel panel-primary">
			<div class="panel-heading">{$pl.QUOTE}</div>
			<div class="panel-body">
				<form accept-charset="UTF-8" role="form" method="post">
					<div class="form-group" style="position: relative;">
						<label>{$pl.DATE}</label>
						<input type="text" name="date" value="{if isset($smarty.post.date)}{$smarty.post.date|htmlentities}{else}{dfo d=$quote.date m=0}{/if}" placeholder="{dfop}" class="form-control datepicker">
					</div>

					<div class="form-group" style="position: relative;">
						<label>{$pl.VALID}</label>
						<input type="text" name="valid" value="{if isset($smarty.post.valid)}{$smarty.post.valid|htmlentities}{else}{dfo d=$quote.valid m=0}{/if}" placeholder="{dfop}" class="form-control datepicker">
					</div>

					<div class="form-group">
						<label>{$pl.CUST}</label><br />
						{if $user}<a href="?p=customers&edit={$user.ID}">{$user.firstname|htmlentities} {$user.lastname|htmlentities}</a>{else}<i>{$pl.NA}</i>{/if}
					</div>

					<div class="form-group">
						<label>{$pl.ADDRESS}</label><br />
						<div class="row">
							<div class="col-md-4">
								<input type="text" name="firstname" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname|htmlentities}{else}{$recipient.0|htmlentities}{/if}" class="form-control" placeholder="{$pl.FN}" />
							</div>

							<div class="col-md-4">
								<input type="text" name="lastname" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname|htmlentities}{else}{$recipient.1|htmlentities}{/if}" class="form-control" placeholder="{$pl.LN}" />
							</div>

							<div class="col-md-4">
								<input type="text" name="company" value="{if isset($smarty.post.company)}{$smarty.post.company|htmlentities}{else}{$recipient.8|htmlentities}{/if}" class="form-control" placeholder="{$pl.CP}" />
							</div>
						</div>

						<div class="row" style="margin-top: 10px;">
							<div class="col-md-10">
								<input type="text" name="street" value="{if isset($smarty.post.street)}{$smarty.post.street|htmlentities}{else}{$recipient.2|htmlentities}{/if}" class="form-control" placeholder="{$pl.ST}" />
							</div>

							<div class="col-md-2">
								<input type="text" name="street_number" value="{if isset($smarty.post.street_number)}{$smarty.post.street_number|htmlentities}{else}{$recipient.3|htmlentities}{/if}" class="form-control" placeholder="{$pl.SN}" />
							</div>
						</div>

						<div class="row" style="margin-top: 10px;">
							<div class="col-md-4">
								<input type="text" name="postcode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode|htmlentities}{else}{$recipient.4|htmlentities}{/if}" class="form-control" placeholder="{$pl.PC}" />
							</div>

							<div class="col-md-8">
								<input type="text" name="city" value="{if isset($smarty.post.city)}{$smarty.post.city|htmlentities}{else}{$recipient.5|htmlentities}{/if}" class="form-control" placeholder="{$pl.CT}" />
							</div>
						</div>

						<div class="row" style="margin-top: 10px;">
							<div class="col-md-4">
								<input type="email" name="mail" value="{if isset($smarty.post.mail)}{$smarty.post.mail|htmlentities}{else}{$recipient.9|htmlentities}{/if}" class="form-control" placeholder="{$pl.EM}" />
							</div>

							<div class="col-md-4">
								<select name="country" class="form-control">
									{foreach from=$countries item=name key=id}
									<option value="{$id}"{if (isset($smarty.post.country) && $smarty.post.country == $id) || (!isset($smarty.post.country) && $name == $recipient.6)} selected="selected"{/if}>{$name}</option>
									{/foreach}
								</select>
							</div>

							<div class="col-md-4">
								<select name="language" class="form-control">
									{foreach from=$languages item=name key=id}
									<option value="{$id}"{if (isset($smarty.post.language) && $smarty.post.language == $id) || (!isset($smarty.post.language) && $id == $recipient.7)} selected="selected"{/if}>{$name}</option>
									{/foreach}
								</select>
							</div>
						</div>
					</div>

					<div class="form-group">
						<label>{$pl.INTRO}</label>
						<textarea name="intro" class="form-control summernote" style="resize: none; height: 100px;">{if isset($smarty.post.intro)}{$smarty.post.intro|htmlentities}{else}{$quote.intro|htmlentities|nl2br}{/if}</textarea>
					</div>

					<div class="form-group">
						<label>{$pl.DUR}</label>
						<div class="radio">
							<label>
							<input type="radio" name="duration" value="1"{if (!isset($smarty.post.duration) && $quote.duration) || ( isset($smarty.post.duration) && $smarty.post.duration)} checked=""{/if}>
							{$pl.DUR1}
							</label>
						</div>
						<div class="radio">
							<label>
							<input type="radio" name="duration" value="0"{if (!isset($smarty.post.duration) && !$quote.duration) || ( isset($smarty.post.duration) && !$smarty.post.duration)} checked=""{/if}>
							{$pl.DUR2}
							</label>
						</div>
					</div>

					<script>
					var product_quote = 0;

					function duration() {
						$(".select_product").unbind("click").click(function(e) {
							e.preventDefault();
							product_quote = $(this).data("id");
							$("#choose_product").modal("show");
						});

						var duration = 0;
						$("[name=duration]").each(function() {
							if($(this).is(":checked")) {
								duration = $(this).val();
							}
						});

						if (duration === "1") {
							$(".duration").show();
							$(".col-md-10").removeClass("col-md-10").addClass("col-md-8");
							$(".col-md-9").each(function() {
								if (!$(this).hasClass("fix-width")) {
									$(this).removeClass("col-md-9").addClass("col-md-7");
								}
							});
						} else {
							$(".duration").hide();
							$(".col-md-8").removeClass("col-md-8").addClass("col-md-10");
							$(".col-md-7").removeClass("col-md-7").addClass("col-md-9");
						}
					}

					function commit() {
						var s = $("#chprse").val().split("-");
						$("[name='invoiceitem_description[" + product_quote + "]']").val(s[0].trim());
						$("[name='invoiceitem_amount[" + product_quote + "]']").val(s[1].trim());
						$("#choose_product").modal("hide");
					}

					$(document).ready(function() {
						duration();

						$("#chprbt").click(function(e) {
							e.preventDefault();
							commit();
						});
						$("#chprfo").submit(function(e) {
							e.preventDefault();
							commit();
						});
					});

					$("[name=duration]").change(duration);
					</script>

					<div class="form-group">
						<label>{$pl.POS}</label>

						<div style="margin-top: -15px;">
						<div id="invoiceitem_template" style="display: none; margin-top: 15px;">
							<div class="row">
								<div class="col-sm-8 resize-col-small">
									<div class="input-group">
										<span class="input-group-addon"><a href="#" class="select_product" data-id="#ID#"><i class="fa fa-cubes"></i></a></span>
										<textarea class="form-control" style="resize: vertical; height: 34px;" name="invoiceitem_description[#ID#]" placeholder="{$pl.OP} #ID#"></textarea>
									</div>
								</div>

								<div class="col-sm-2 duration">
									<input type="text" class="form-control" name="invoiceitem_time[#ID#]" placeholder="{$pl.DURA}" />
								</div>

								<div class="col-sm-2">
									<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
										{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
										<input type="text" class="form-control" name="invoiceitem_amount[#ID#]" placeholder="{nfop}" />
										{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
									</div>
								</div>

								<div class="col-sm-1 delete-col" style="display: none;">
									<input type="button" class="btn btn-danger btn-block delete_position" value="{$pl.DEL}">
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

						{for $i=1 to $positions}{assign var="i2" value=$i-1}<div class="invoiceitem" id="invoiceitem_{$i}" style="margin-top: 15px;">
							<div class="row">
								<div class="col-sm-{if $positions < 2}8{else}7{/if} resize-col-small">
									<div class="input-group">
										<span class="input-group-addon"><a href="#" class="select_product" data-id="{$i}"><i class="fa fa-cubes"></i></a></span>
										<textarea class="form-control" style="resize: vertical; height: 34px;" name="invoiceitem_description[{$i}]" placeholder="{$pl.OP} {$i}">{if isset($smarty.post.invoiceitem_description.$i)}{$smarty.post.invoiceitem_description.$i|htmlentities}{else}{$items.$i2.0|htmlentities}{/if}</textarea>
									</div>
								</div>

								<div class="col-sm-2 duration">
									<input type="text" class="form-control" name="invoiceitem_time[{$i}]" value="{if isset($smarty.post.invoiceitem_time.$i)}{$smarty.post.invoiceitem_time.$i|htmlentities}{else}{$items.$i2.1|htmlentities}{/if}" placeholder="{$pl.DURA}" />
								</div>

								<div class="col-sm-2">
									<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
										{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
										<input type="text" class="form-control" name="invoiceitem_amount[{$i}]" value="{if isset($smarty.post.invoiceitem_amount.$i)}{$smarty.post.invoiceitem_amount.$i|htmlentities}{else}{nfo i=$items.$i2.2}{/if}" placeholder="{nfop}" />
										{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
									</div>
								</div>

								<div class="col-sm-1 delete-col"{if $positions < 2} style="display: none;"{/if}>
									<input type="button" class="btn btn-danger btn-block delete_position" value="{$pl.DEL}">
								</div>
							</div>
						</div>{/for}
					</div>

						<br /><a href="#" onclick="addRow(); duration(); return false;"><i class="fa fa-plus-square-o"></i> {$pl.ADD}</a>
					</div>

					<div class="form-group">
						<label>{$pl.STAGES}</label>

						<div style="margin-top: -15px;">
						<div id="stage_template" style="display: none; margin-top: 15px;">
							<div class="row">
								<div class="col-md-6">
									<div class="input-group">
										{if $pl.STAGEBEF}<span class="input-group-addon">{$pl.STAGEBEF}</span>{/if}
										<input type="text" class="form-control" name="stage_days[#ID#]" value="0" />
										{if $pl.STAGEAFT}<span class="input-group-addon">{$pl.STAGEAFT}</span>{/if}
									</div>
								</div>

								<div class="col-md-5">
									<div class="input-group">
										<input type="text" class="form-control" name="stage_percent[#ID#]" placeholder="{$pl.PERCENT}" />
										<span class="input-group-addon">%</span>
									</div>
								</div>

								<div class="col-md-1">
									<input type="button" class="btn btn-danger btn-block" onclick="deleteStage(#ID#);" value="{$pl.DEL}">
								</div>
							</div>
						</div>

						{for $i=0 to ($stages|count - 1)}<div class="stage" id="stage_{$i}" style="margin-top: 15px;">
							<div class="row">
								<div class="col-md-6">
									<div class="input-group">
										{if $pl.STAGEBEF}<span class="input-group-addon">{$pl.STAGEBEF}</span>{/if}
										<input type="text" class="form-control" name="stage_days[{$i}]" value="{if isset($smarty.post.stage_days.$i)}{$smarty.post.stage_days.$i|htmlentities}{else}{$stages.$i.0}{/if}" />
										{if $pl.STAGEAFT}<span class="input-group-addon">{$pl.STAGEAFT}</span>{/if}
									</div>
								</div>

								<div class="col-md-{if $i == 0}6{else}5{/if}">
									<div class="input-group">
										<input type="text" class="form-control" name="stage_percent[{$i}]" value="{if isset($smarty.post.stage_percent.$i)}{$smarty.post.stage_percent.$i|htmlentities}{else}{$stages.$i.1}{/if}" placeholder="{$pl.PERCENT}" />
										<span class="input-group-addon">%</span>
									</div>
								</div>

								{if $i > 0}
								<div class="col-md-1">
									<input type="button" class="btn btn-danger btn-block" onclick="deleteStage({$i});" value="{$pl.DEL}">
								</div>
								{/if}
							</div>
						</div>{/for}
						</div>

						<br /><a href="#" onclick="addStage(); return false;"><i class="fa fa-plus-square-o"></i> {$pl.ADD_STAGE}</a>
					</div>

					<div class="form-group">
						<label>{$pl.EXTRO}</label>
						<textarea name="extro" class="form-control summernote" style="resize: none; height: 100px;">{if isset($smarty.post.extro)}{$smarty.post.extro}{else}{$quote.extro|nl2br}{/if}</textarea>
					</div>

					<div class="form-group">
						<label>{$pl.TERMS}</label>
						<textarea name="terms" class="form-control summernote" style="resize: none; height: 100px;">{if isset($smarty.post.terms)}{$smarty.post.terms}{else}{$quote.terms|nl2br}{/if}</textarea>
					</div>

					<div class="checkbox">
						<label>
						<input type="checkbox" name="no_vat" value="yes"{if (isset($smarty.post.no_vat) && $smarty.post.no_vat == "yes") || (!isset($smarty.post.terms) && !$quote.vat)} checked="checked"{/if}> {$pl.NO_VAT}
						</label>
					</div>

					<button type="submit" name="save_invoice" class="btn btn-primary btn-block">{$pl.SAVE}</button>
				</form>
			</div>
		</div>
	</div>

	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">{$pl.FILES}<a href="#" data-toggle="modal" data-target="#uploadInvoiceFile" class="pull-right"><i class="fa fa-plus"></i></a></div>
			<div class="panel-body" style="text-align: justify;">
				{if $files|@count}
					<ul style="margin-bottom: 0;">
					{foreach from=$files item=file}
					<li>
						<a href="?p=quote&id={$smarty.get.id|intval}&download_file={$file|urlencode}" target="_blank">{$file|htmlentities}</a>
						<a href="?p=quote&id={$smarty.get.id|intval}&delete_file={$file|urlencode}" class="pull-right"><i class="fa fa-times"></i></a>
					</li>
					{/foreach}
					</ul>
				{else}
					<i>{$pl.NF}</i>
				{/if}
			</div>
		</div>

		<div class="modal fade" id="uploadInvoiceFile" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<form method="POST" enctype="multipart/form-data" role="form">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title">{$pl.UPLF}</h4>
						</div>
						<div class="modal-body">
							<div class="form-group" style="margin-bottom: 0;">
								<input type="file" class="form-control" name="upload_files[]" multiple>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
							<button type="submit" class="btn btn-primary">{$pl.UPLF}</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>