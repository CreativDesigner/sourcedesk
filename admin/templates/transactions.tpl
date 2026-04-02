<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.TRANSACTIONS.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($success)}<div class="alert alert-success">{$success}</div>{/if}
{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

<div style="float: left;">
	{if $cfg.STEM_AUTO > 0}<form method="GET">
		<input type="hidden" name="p" value="transactions" />
		<input type="hidden" name="page" value="{$apage}" />
		<select name="filter" onchange="form.submit()">
			<option value="">{$lang.TRANSACTIONS.NO}</option>
			<option value="stem"{if isset($filter) && $filter == "stem"} selected="selected"{/if}>{$lang.TRANSACTIONS.NO_STEM}</option>
		</select>
	</form>{else}
	{if $count == 1}{$lang.LOGS.ONE_ENTRY}{else}{$lang.LOGS.X_ENTRIES|replace:"%x":{nfo i=$count d=0}}{/if}
	{/if}
</div>

<div style="float: right;">
	{if $pages > 1}<form method="GET">{/if}
	{$lang.ADMIN_LOG.PAGING|replace:"%s":$pages}
	{if $pages == 1}1{else}
	<input type="hidden" name="p" value="transactions" />
	<select name="page" onchange="form.submit()">
		{for $i=1 to $pages}
		<option{if $apage == $i} selected="selected"{/if}>{$i}</option>
		{/for}
	</select>
	{if isset($filter)}<input type="hidden" name="filter" value="{$filter}" />{/if}
	{/if}
	{$lang.ADMIN_LOG.PAGING_2|replace:"%s":$pages}
	{if $pages > 1}</form>{/if}
</div><br /><br />

<div class="table table-responsive">
<table class="table table-bordered table-striped">

	<tr>
		{if $cfg.STEM_AUTO > 0 && isset($smarty.get.filter) && $smarty.get.filter == "stem"}<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>{/if}
		<th>{$lang.LOGS.DATE}</th>
		<th>{$lang.LOGS.CUSTOMER}</th>
		<th>{$lang.TRANSACTIONS.SUBJECT}</th>
		<th>{$lang.TRANSACTIONS.AMOUNT}</th>
	</tr>
	
	<form method="POST">
	{foreach from=$transactions item=transaction key=id}
	<tr>
		{if $cfg.STEM_AUTO > 0 && isset($smarty.get.filter) && $smarty.get.filter == "stem"}<td><input type="checkbox" class="checkbox" onchange="javascript:toggle();" name="trans[]" value="{$id}"></td>{/if}
		<td>{$transaction.date}</td>
		<td>{$transaction.customer}</td>
		<td>{if !empty($transaction.cashbox)}<a href="#" onclick="return false;" data-toggle="tooltip" data-original-title="{$lang.GENERAL.CASHBOX}: {$transaction.cashbox|htmlentities}">{/if}{$transaction.subject|htmlentities}{if !empty($transaction.cashbox)}</a>{/if}{if $transaction.refundable} <a href="?p=transactions&page={$apage}{if isset($filter)}&filter={$filter}{/if}&refund={$transaction.ID}" onclick="return confirm('{$lang.TRANSACTIONS.REFUNDCONFIRM}');"><i class="fa fa-rotate-left"></i></a>{/if}{if $transaction.waiting} <small><font color="red">({$lang.TRANSACTIONS.WAITING})</font></small>{/if}{if $transaction.stem == 0 && $cfg.STEM_AUTO > 0} <a href="./?p=transactions&page={$apage}{if isset($filter)}&filter={$filter}{/if}&stem={$id}"><i class="fa fa-legal"></i></a> <a href="./?p=transactions&page={$apage}{if isset($filter)}&filter={$filter}{/if}&ignore_stem={$id}"><i class="fa fa-ban"></i></a>{/if}</td>
		<td>{$transaction.amount}{if $transaction.deposit} <a href="?p=customers&receipt={$transaction.ID}" target="_blank"><i class="fa fa-file-pdf-o"></i></a>{/if}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6"><center>{if isset($smarty.get.filter) && $smarty.get.filter == "stem" && $cfg.STEM_AUTO > 0}{$lang.TRANSACTIONS.STEM_DONE}{else}{$lang.TRANSACTIONS.NO_ENTRIES}{/if}</center></td>
	</tr>
	{/foreach}

</table>
</div>{if $cfg.STEM_AUTO > 0 && isset($smarty.get.filter) && $smarty.get.filter == "stem" && $transactions|@count > 0}{$lang.GENERAL.SELECTED}: <input type="submit" name="stem_selected" value="{$lang.TRANSACTIONS.STEM_SELECTED}" class="btn btn-success" /> <input type="submit" name="ignore_selected" value="{$lang.TRANSACTIONS.IGNORE_SELECTED}" class="btn btn-warning" /><br /><br />{/if}</form>

<center><a href="?p=transactions&page={$apage - 1}{if isset($filter)}&filter={$filter}{/if}" class="btn btn-default"{if ($apage - 1) <= 0} disabled="disabled"{/if}>{$lang.ADMIN_LOG.PREVIOUS}</a> <a href="?p=transactions&page={$apage + 1}{if isset($filter)}&filter={$filter}{/if}" class="btn btn-default"{if ($apage + 1) > $pages} disabled="disabled"{/if}>{$lang.ADMIN_LOG.NEXT}</a></center>