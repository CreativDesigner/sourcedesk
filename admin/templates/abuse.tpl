<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.TITLE}</h1>

		{$th}

        <div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="200px">{$l.TIME}</th>
					<th width="200px">{$l.DEADLINE}</th>
					<th>{$l.SUBJECT}</th>
					<th width="250px">{$l.CUSTOMER}</th>
					<th width="150px">{$l.STATUS}</th>
				</tr>

				{foreach from=$reports item=a}
				<tr>
					<td>{dfo d=$a.time}</td>
					<td>{dfo d=$a.deadline}{if $a.status == "open" && time() >= strtotime($a.deadline)} <i class="fa fa-exclamation-triangle" style="color: red;"></i>{/if}</td>
					<td><a href="?p=abuse&id={$a.ID}">{$a.subject|htmlentities}</a></td>
					<td>{$a.userlink}</td>
					<td>
                        {if $a.status == "open"}
                        <span class="label label-warning">{$l.OPEN}</span>
                        {else}
                        <span class="label label-success">{$l.RESOLVED}</span>
                        {/if}
                    </td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="5"><center>{$l.NOTHING}</center></td>
				</tr>
				{/foreach}
			</table>
		</div>

        {$tf}
	</div>
</div>