<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.TOP10.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="row">
	<div class="col-md-3">
		<div class="list-group">
			<a class="list-group-item{if $tab == "products"} active{/if}" href="./?p=stat_top10">{$lang.TOP10.PRODUCTS}</a>
			<a class="list-group-item{if $tab == "licenses"} active{/if}" href="./?p=stat_top10&tab=licenses">{$lang.TOP10.LICENSES}</a>
			<a class="list-group-item{if $tab == "invoices"} active{/if}" href="./?p=stat_top10&tab=invoices">{$lang.TOP10.CUSTOMERS}</a>
			<a class="list-group-item{if $tab == "credit"} active{/if}" href="./?p=stat_top10&tab=credit">{$lang.TOP10.CREDIT}</a>
		</div>
	</div>

	<div class="col-md-9">

		{if $tab == "products"}
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="60%">{$lang.TOP10.PRODUCT}</th>
					<th width="20%">{if isset($smarty.get.order)}<a href="./?p=stat_top10">{$lang.TOP10.LICENSES}</a>{else}{$lang.TOP10.LICENSES}{/if}</th>
					<th width="20%">{if !isset($smarty.get.order)}<a href="./?p=stat_top10&order=profit">{$lang.TOP10.PROFIT}</a>{else}{$lang.TOP10.PROFIT}{/if}</th>
				</tr>

				{$tableContents}
			</table>
		</div>
		{else if $tab == "credit"}
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="60%">{$lang.TOP10.CLIENT}</th>
					<th>{$lang.TOP10.CREDIT}</th>
				</tr>

				{$tableContents}
			</table>
		</div>
		{else if $tab == "licenses"}
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th>{$lang.TOP10.CLIENT}</th>
					<th width="20%">{if isset($smarty.get.order)}<a href="./?p=stat_top10&tab=licenses">{$lang.TOP10.LICENSES}</a>{else}{$lang.TOP10.LICENSES}{/if}</th>
					<th width="20%">{if !isset($smarty.get.order)}<a href="./?p=stat_top10&tab=licenses&order=profit">{$lang.TOP10.INCOMING}</a>{else}{$lang.TOP10.INCOMING}{/if}</th>
				</tr>

				{$tableContents}
			</table>
		</div>
		{else if $tab == "invoices"}
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th>{$lang.TOP10.CLIENT}</th>
					<th width="20%">{if !isset($smarty.get.order)}<a href="./?p=stat_top10&tab=invoices&order=num">{$lang.TOP10.NUMBER}</a>{else}{$lang.TOP10.NUMBER}{/if}</th>
					<th width="20%">{if isset($smarty.get.order)}<a href="./?p=stat_top10&tab=invoices">{$lang.TOP10.SUM}</a>{else}{$lang.TOP10.SUM}{/if}</th>
				</tr>

				{$tableContents}
			</table>
		</div>
		{else}
		<div class="alert alert-danger">{$lang.GENERAL.SUBPAGE_NOT_FOUND}</div>
		{/if}
	</div>
</div>