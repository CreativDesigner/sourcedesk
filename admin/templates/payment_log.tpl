<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{if is_object($gateway_info)}{$gateway_info->getLang('name')}{/if} <small>{$lang.PAYMENT_LOG.TITLE}{if !isset($truncate_ok)} | <a href="?p=payment_log&gateway={$smarty.get.gateway}&a=truncate">{$lang.PAYMENT_LOG.TRUNCATE}</a>{/if}</small></h1>

        <form method="GET"><input type="hidden" name="p" value="payment_log" /><select class="form-control" name="gateway" onchange="form.submit()">{foreach from=$gateways key=k item=g}<option value="{$k}"{if $g->getLang('name') == $gateway_info->getLang('name')} selected="selected"{/if}>{$g->getLang('name')}</option>{/foreach}</select></form><br />

		{if isset($truncate_ok)}
		<div class="alert alert-success">{$truncate_ok}</div>
		{/if}

		{$th}

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th>{$table_order.0}</th>
					<th>{$lang.PAYMENT_LOG.DATA}</th>
					<th>{$lang.PAYMENT_LOG.LOG}</th>
				</tr>
				<form method="POST">
				{foreach from=$log item=r}
				<tr>
					<td><input type="checkbox" class="checkbox" name="log[]" value="{$r.ID}" onchange="javascript:toggle();" /></td>
					<td>{$r.time}</td>
					<td id="p{$r.ID}">{$r.data|htmlentities|nl2br}{if !empty($r.further)} <a href="#" class="entryExpand entryExpand_{$r.ID}" onclick="expandEntry({$r.ID}); return false;">[+]</a> <div class="entryFurther entryFurther_{$r.ID}" style="display: none;">{$r.further|htmlentities|nl2br}</div>{/if}</td>
					<td>{$r.log|htmlentities|nl2br}</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="4"><center>{$lang.PAYMENT_LOG.NOTHING}</center></td>
				</tr>
				{/foreach}
			</table>
		</div>{$lang.GENERAL.SELECTED}: <input type="submit" name="delete_selected" value="{$lang.GENERAL.DELETE}" class="btn btn-danger" /></form>

		{$tf}
	</div>
</div>