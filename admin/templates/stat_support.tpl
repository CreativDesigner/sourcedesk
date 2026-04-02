<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.STAT_SUPPORT.TITLE} <small>{$month}.{$year}</small><span class="pull-right"><a href="?p=stat_support&month={$prevmonth}&year={$prevyear}"><i class="fa fa-arrow-circle-o-left"></i></a>{if isset($nextmonth)}<a href="?p=stat_support&month={$nextmonth}&year={$nextyear}">{/if}<i class="fa fa-arrow-circle-o-right" style="margin-left: 5px;"></i>{if isset($nextmonth)}</a>{/if}</span></h1>

		<h3>{$lang.STAT_SUPPORT.RATINGS}</h3>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_SUPPORT.RATING}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_SUPPORT.TICKETS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_SUPPORT.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$ratings item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>