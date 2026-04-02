{assign "ql" $lang.QUOTE}
{assign "el" $lang.EINVOICE}
{assign "pl" $lang.INVOICE_EDIT}
{assign "rl" $lang.RECURRING_INVOICE}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$pl.CREATER}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($success)}<div class="alert alert-success">{$success}</div>{/if}
{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

<form accept-charset="UTF-8" role="form" method="post">
	<div class="form-group">
		<label>{$ql.CUST}</label><br />
		<a href="?p=customers&edit={$user.ID}">{$user.name|htmlentities}</a>
	</div>

	<div class="form-group" style="position: relative;">
		<label>{$pl.FIRSTI}</label>
		<input type="text" name="first" value="{if isset($smarty.post.first)}{$smarty.post.first|htmlentities}{else}{dfop}{/if}" placeholder="{dfop}" class="form-control datepicker">
		<p class="help-block">{$pl.FIRSTIH}</p>
	</div>

	<div class="form-group">
		<label>{$rl.INTERVAL}</label>
		<div class="row">
			<div class="col-md-6 col-sm-6 col-xs-6">
				<input type="text" name="interval1" value="{if isset($smarty.post.interval1)}{$smarty.post.interval1|htmlentities}{else}1{/if}" placeholder="1" class="form-control" />
			</div>

			<div class="col-md-6 col-sm-6 col-xs-6">
				<select name="interval2" class="form-control">
					<option value="day"{if isset($smarty.post.interval2) && $smarty.post.interval2 == "day"} selected="selected"{/if}>{$rl.DAY}</option>
					<option value="week"{if isset($smarty.post.interval2) && $smarty.post.interval2 == "week"} selected="selected"{/if}>{$rl.WEEK}</option>
					<option value="month"{if isset($smarty.post.interval2) && $smarty.post.interval2 == "month"} selected="selected"{/if}>{$rl.MONTH}</option>
					<option value="year"{if isset($smarty.post.interval2) && $smarty.post.interval2 == "year"} selected="selected"{/if}>{$rl.YEAR}</option>
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label>{$rl.MAXNUM}</label>
		<input type="number" class="form-control" name="limit_invoices" placeholder="{$rl.UNLIMITED}" value="{if isset($smarty.post.limit_invoices) && $smarty.post.limit_invoices !== ""}{$smarty.post.limit_invoices|intval}{/if}">
	</div>

	<div class="form-group" style="position: relative;">
		<label>{$rl.MAXDATE}</label>
		<input type="text" class="form-control datepicker" name="limit_date" placeholder="{$rl.UNLIMITED}" value="{if !empty($smarty.post.limit_date)}{dfo d=$smarty.post.limit_date m=0}{/if}">
	</div>

	<div class="form-group">
		<label>{$rl.POSITION}</label>

		<div class="row">
			<div class="col-sm-10">
				<div class="input-group">
					<span class="input-group-addon"><a href="#" class="select_product"><i class="fa fa-cubes"></i></a></span>
					<input type="text" class="form-control" name="description" value="{if isset($smarty.post.description)}{$smarty.post.description|htmlentities}{/if}" placeholder="{$rl.DESC}" />
				</div>
			</div>

			<div class="col-sm-2">
				<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
					{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
					<input type="text" class="form-control" name="amount" value="{if isset($smarty.post.amount)}{$smarty.post.amount|htmlentities}{/if}" placeholder="{nfop}" />
					{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="show_period" value="yes"{if !isset($smarty.post.status) || (isset($smarty.post.show_period) && $smarty.post.show_period == "yes")} checked="checked"{/if}> {$rl.SP}
	    </label>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="net" value="yes"{if (isset($smarty.post.net) && $smarty.post.net == "yes")} checked="checked"{/if}> {$el.NET}
	    </label>
	</div>

	<div class="form-group">
	<label>{$rl.STATUS}</label><br />
		<label class="radio-inline">
			<input type="radio" name="status" value="1"{if (!isset($smarty.post.status)) || (isset($smarty.post.status) && $smarty.post.status == "1")} checked{/if}>
			{$rl.STATUS1}
		</label>
		<label class="radio-inline">
			<input type="radio" name="status" value="0"{if isset($smarty.post.status) && $smarty.post.status == "0"} checked{/if}>
			{$rl.STATUS2}
		</label>
	</div>

    <button type="submit" name="add_invoice" class="btn btn-primary btn-block">{$pl.DOCR}</button>
</form>

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

<script>
$(document).ready(function() {
	function commit() {
		var s = $("#chprse").val().split("-");
		$("[name='amount']").val(s.pop());
		$("[name='description']").val(s.join("-").trim());
		$("#choose_product").modal("hide");
	}

	$(".select_product").unbind("click").click(function(e) {
		e.preventDefault();
		$("#choose_product").modal("show");
	});
	$("#chprbt").click(function(e) {
		e.preventDefault();
		commit();
	});
	$("#chprfo").submit(function(e) {
		e.preventDefault();
		commit();
	});
});
</script>