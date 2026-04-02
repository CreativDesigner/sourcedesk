<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.STAT_ORDERS.TITLE} <small>{$month}.{$year}</small><span class="pull-right"><a href="?p=stat_orders&month={$prevmonth}&year={$prevyear}"><i class="fa fa-arrow-circle-o-left"></i></a>{if isset($nextmonth)}<a href="?p=stat_orders&month={$nextmonth}&year={$nextyear}">{/if}<i class="fa fa-arrow-circle-o-right" style="margin-left: 5px;"></i>{if isset($nextmonth)}</a>{/if}</span></h1>
	
		{foreach from=$res key=cat item=products}
		{if !empty($cat)}<h3>{$cat|htmlentities}</h3>{/if}
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_ORDERS.PRODUCT}</th>
						<th width="20%"><center>{$lang.STAT_ORDERS.SELLS}</center></th>
						<th width="20%"><center>{$lang.STAT_ORDERS.VALUE}</center></th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$products key=name item=stat}
					<tr>
						<td>{$name|htmlentities}</td>
						<td><center>{$stat.0}</center></td>
						<td><center>{$stat.1}</center></td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		{/foreach}
	</div>
</div>