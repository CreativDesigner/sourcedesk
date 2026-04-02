{assign "pl" $lang.LATERINVOICE}
{assign "ql" $lang.QUOTE}
{assign "rl" $lang.RECURRING_INVOICE}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$pl.TITLE}</h1>
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

	<div class="form-group">
		<label>{$rl.DESC}</label>
		<input type="text" name="description" class="form-control" value="{if isset($smarty.post.description)}{$smarty.post.description|htmlentities}{/if}">
	</div>

	<div class="form-group">
		<label>{$pl.AMOUNT}</label>
		<div class="{if !empty($cur_prefix) || !empty($cur_suffix)}input-group{/if}">
		{if !empty($cur_prefix)}<span class="input-group-addon">{$cur_prefix}</span>{/if}
		<input type="text" name="amount" class="form-control" value="{if isset($smarty.post.amount)}{$smarty.post.amount|htmlentities}{/if}">
		{if !empty($cur_suffix)}<span class="input-group-addon">{$cur_suffix}</span>{/if}
		</div>
	</div>

	<div class="checkbox">
		<label>
			<input type="checkbox" name="paid" value="yes"{if isset($smarty.post.paid) && $smarty.post.paid == "yes"} checked="checked"{/if}> {$pl.PAID}
		</label>
	</div>

	<div class="checkbox">
	    <label>
	      <input type="checkbox" name="net" value="yes"{if (isset($smarty.post.net) && $smarty.post.net == "yes")} checked="checked"{/if}> {$pl.NET}
	    </label>
	</div>

    <button type="submit" name="add_invoice" class="btn btn-primary btn-block">{$pl.SAVE}</button>
</form>