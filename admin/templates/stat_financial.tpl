<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.FINANCIAL_OVERVIEW.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="table-responsive">
	<table class="table table-bordered table-striped">

		{foreach from=$accounts item=a}
		<tr>
			<td>{$a.0|htmlentities} ({$a.1|htmlentities})</td>
			<td>{if $a.3 >= 0}<font color="green">+ {$a.2}</font>{else}<font color="red">- {$a.2}</font>{/if}</td>
		</tr>
		{/foreach}

		<tr>
			<th>{$lang.FINANCIAL_OVERVIEW.GIRO}</th>
			<th>{if ($cfg.BANK_CREDIT) >= 0}<font color="green">+ {infix n={nfo i=($cfg.BANK_CREDIT)} c='base'}</font>{else}<font color="red">- {infix n={nfo i=($cfg.BANK_CREDIT) / -1} c='base'}</font>{/if}</th>
		</tr>

		{if $liabilities != 0}<tr>
			<td><a href="?p=stat_liabilities">{$lang.FINANCIAL_OVERVIEW.LIABILITIES}</a></td>
			<td>{if $liabilities >= 0}<font color="red">- {infix n={nfo i=$liabilities} c='base'}</font>{else}<font color="green">+ {infix n={nfo i=$liabilities/-1} c='base'}</font>{/if}</td>
		</tr>{/if}

		<tr>
			<th>{$lang.FINANCIAL_OVERVIEW.AVAILABLE}</th>
			<th>{if ($cfg.BANK_CREDIT - $liabilities) >= 0}<font color="green">+ {infix n={nfo i=($cfg.BANK_CREDIT - $liabilities)} c='base'}</font>{else}<font color="red">- {infix n={nfo i=($cfg.BANK_CREDIT - $liabilities) / -1} c='base'}</font>{/if}</th>
		</tr>

		{if $debtors != 0}<tr>
			<td><a href="?p=stat_debtors">{$lang.FINANCIAL_OVERVIEW.DEBTORS}</a></td>
			<td>{if $debtors >= 0}<font color="green">+ {infix n={nfo i=$debtors} c='base'}</font>{else}<font color="red">- {infix n={nfo i=$debtors/-1} c='base'}</font>{/if}</td>
		</tr>

		{if $cfg.STEM_AUTO > 0}<tr>
			<td>{$lang.FINANCIAL_OVERVIEW.STEM}</td>
			{assign var="stem_auto" value=($debtors * $cfg['STEM_AUTO'] / -100)}
			<td>{if $stem_auto >= 0}{assign var="stem_auto" value=0}<font color="green">+ {infix n={nfo i=0} c='base'}</font>{else}<font color="red">- {infix n={nfo i=$stem_auto/-1} c='base'}</font>{/if}</td>
		</tr>{else}{assign var="stem_auto" value=0}{/if}

		<tr>
			<th>{$lang.FINANCIAL_OVERVIEW.SUM}</th>
			<th>{if $cfg.BANK_CREDIT - $liabilities + $debtors + $stem_auto >= 0}<font color="green">+ {infix n={nfo i=$cfg.BANK_CREDIT - $liabilities + $debtors + $stem_auto} c='base'}</font>{else}<font color="red">- {infix n={nfo i=($cfg.BANK_CREDIT - $liabilities + $debtors + $stem_auto) / -1} c='base'}</font>{/if}</th>
		</tr>{/if}
	</table>
</div>