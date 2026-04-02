<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.STAT_CUSTOMERS.TITLE}</h1>
	
		<h3>{$lang.STAT_CUSTOMERS.T1}</h3>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C1}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$countries item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C2}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$currencies item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C3}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$languages item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		
		<h3>{$lang.STAT_CUSTOMERS.T2}</h3>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C4}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$gender item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C5}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$ages item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<h3>{$lang.STAT_CUSTOMERS.T3}</h3>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C6}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$types item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C7}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$groups item=stat}
					<tr>
						<td>{$stat.0}</td>
						<td style="text-align: center;">{$stat.1}</td>
						<td style="text-align: center;">{$stat.2}</td>
					<tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>{$lang.STAT_CUSTOMERS.C8}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.CUSTOMERS}</th>
						<th width="20%" style="text-align: center;">{$lang.STAT_CUSTOMERS.PERCENT}</th>
					</tr>
				</thead>

				<tbody>
					{foreach from=$properties item=stat}
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