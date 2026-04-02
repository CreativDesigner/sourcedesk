<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.LOGS.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>
<div style="float: left;">
	{if $count == 1}{$lang.LOGS.ONE_ENTRY}{else}{$lang.LOGS.X_ENTRIES|replace:"%x":{nfo i=$count d=0}}{/if}
</div>

<div style="float: right;">
	{if $pages > 1}<form method="GET">{/if}
	{$lang.ADMIN_LOG.PAGING|replace:"%s":$pages}
	{if $pages == 1}1{else}
	<input type="hidden" name="p" value="logs" />
	<select name="page" onchange="form.submit()">
		{for $i=1 to $pages}
		<option{if $apage == $i} selected="selected"{/if}>{$i}</option>
		{/for}
	</select>
	{/if}
	{$lang.ADMIN_LOG.PAGING_2|replace:"%s":$pages}
	{if $pages > 1}</form>{/if}
</div><br /><br />

<div class="table-responsive">
	<table class="table table-bordered table-striped">

		<tr>
			<th>{$lang.LOGS.DATE}</th>
			<th>{$lang.LOGS.CUSTOMER}</th>
			<th>{$lang.LOGS.ACTION}</th>
			<th>{$lang.LOGS.IP}</th>
		</tr>

		{foreach from=$logs item=log}
		<tr>
			<td style="vertical-align: middle">{$log.date}</td>
			<td style="vertical-align: middle">{$log.customer}</td>
			<td style="vertical-align: middle">{$log.action|htmlentities}</td>
			<td style="vertical-align: middle">
				{if !isset($log.location) || ($log.location.country == "" || $log.location.country == "no")}{$log.ip}{else}<a href="#" data-toggle="tooltip" onclick="return false;" data-original-title="{if $log.location.city != "" && $log.location.city != "no"}{$log.location.city|htmlentities}, {/if}{$log.location.country|htmlentities}">{$log.ip}</a>{/if}
				{if !empty($log.ua)}
				<a href="#" data-toggle="tooltip" onclick="return false;" data-original-title="{$log.ua}"><i class="fa fa-desktop"></i></a>
				{/if}
			</td>
		</tr>
		{foreachelse}
		<tr>
			<td colspan="4"><center>{$lang.LOGS.NO_ENTRIES}</center></td>
		</tr>
		{/foreach}

	</table>
</div>

<center><a href="?p=logs&page={$apage - 1}" class="btn btn-default"{if ($apage - 1) <= 0} disabled="disabled"{/if}>{$lang.ADMIN_LOG.PREVIOUS}</a> <a href="?p=logs&page={$apage + 1}" class="btn btn-default"{if ($apage + 1) > $pages} disabled="disabled"{/if}>{$lang.ADMIN_LOG.NEXT}</a></center>
