<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.STAT_FORECAST.TITLE}</h1>
	
        <div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_FORECAST.MONTH}</th>
						<th width="30%" style="text-align: center;">{$lang.STAT_FORECAST.EXPECTED}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$expected item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>