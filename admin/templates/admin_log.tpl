<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.ADMIN_LOG.TITLE}</h1>

		{$th}

		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th>{$table_order.0}</th>
					<th>{$table_order.1}</th>
					<th>{$lang.ADMIN_LOG.ACTION}</th>
					<th>{$table_order.2}</th>
				</tr>

				{foreach from=$logs key=id item=log}
				<tr>
					<td>{$log.time}</td>
					<td><a href="?p=admin&id={$log.adminId}">{$log.admin|htmlentities}</a></td>
					<td>{$log.action|htmlentities}</td>
					<td>{$log.ip}</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="4"><center>{$lang.ADMIN_LOG.NOTHING}</center></td>
				</tr>
				{/foreach}
			</table>
		</div>

		{$tf}
	</div>
</div>