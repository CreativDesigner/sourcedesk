<div class="container">
	<h1>{$lang.NAV.FILES}</h1><hr/>

	{$lang.FILES.INTRO}<br /><br />

	{if $files|count <= 0}<i>{$lang.FILES.NOTHING}</i>{else}

	<div class="table-responsive">
		<table class="table table-bordered">
			<tr>
				<th>{$lang.FILES.FILENAME}</th>
				<th width="30px"></th>
			</tr>
			
			{foreach from=$files item=path key=name}
			<tr>
				<td>{$name|htmlentities}</td>
				<td width="30px"><a href="{$cfg.PAGEURL}file/{$path|urlencode}" target="_blank"><i class="fa fa-download"></i></a></td>
			</tr>
			{/foreach}
		</table>
	</div>

	{/if}
</div><br />