<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.YEARLY_INVOICE.TITLE} <small><a href="?p=customers&edit={$smarty.get.cid}">{$cname}</a></small></h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<p>{$lang.YEARLY_INVOICE.INTRO}</p>

{if $years|@count}
{foreach from=$years item=year}
<a href="?p=yearly_invoice&cid={$smarty.get.cid}&year={$year}" target="_blank" class="btn btn-default">{$year}</a> 
{/foreach}
{else}
<i>{$lang.YEARLY_INVOICE.NT}</i>
{/if}