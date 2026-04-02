<div id="content">
	<div class="container">
		<h1>{$lang.NAV.PROJECTS}</h1><hr>
		<p>{$lang.PROJECTS.INTRO}</p>
		{if $projects|@count == 0}
		<i>{$lang.PROJECTS.NOTHING}</i>
		{else}
		<br /><div class="table table-responsive">
			<table class="table table-bordered">
				<tr>	
					<th width="70px" style="text-align: center;">#</th>
					<th>{$lang.PROJECTS.NAME}</th>
					<th>{$lang.PROJECTS.DUE}</th>
					<th>{$lang.PROJECTS.STATUS}</th>
					{if $show && $show_files}
					<th width="55px"></th>
					{elseif $show || $show_files}
					<th width="30px"></th>
					{/if}
				</tr>
				
				{foreach from=$projects item=s}
				<tr>
					<td style="text-align: center;">{$cfg.PNR_PREFIX}{$s.ID}</td>
					<td>{$s.name|htmlentities}</td>
					<td>{$s.due}</td>
					<td>{if $s.status == 1}<font color="green">{$lang.PROJECTS.DONE}</font>{elseif $s.status == 2}<font color="red">{$lang.PROJECTS.WAITING}</font>{elseif $s.status == 3}<font color="red">{$lang.PROJECTS.INFORMATION}</font>{else}{if $s.overdue}<font color="red">{$lang.PROJECTS.OVERDUE}</font>{else}<font color="orange">{$lang.PROJECTS.PROCESS}</font>{/if}{/if}</td>
					{if $show || $show_files}
					{if $show && $show_files}
					<td width="55px"></th>
					{elseif $show || $show_files}
					<td width="30px"></th>
					{/if}
						{if $show && isset($s.tasks)}
							<a href="#" data-toggle="modal" data-target="#project_{$s.ID}"><i class="fa fa-info-circle"></i></a>

							<div class="modal fade" id="project_{$s.ID}" tabindex="-1" role="dialog">
							  <div class="modal-dialog">
							    <div class="modal-content">
							      <div class="modal-header">
							        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
							        <h4 class="modal-title" id="myModalLabel">{$s.name}</h4>
							      </div>
							      <div class="modal-body">
										{if $s.description}{$s.description|htmlentities|nl2br}<br /><br />{/if}
											
								    {if $s.tasks|count > 0}
								    	<ul>
								        {foreach from=$s.tasks item=t}
								        	<li>{$t.name|htmlentities}{if $t.status == 1} <font color="green">({$lang.PROJECTS.DONE})</font>{/if}</li>
								        {/foreach}
								        </ul>
							        {else}
							        	<i>{$lang.PROJECTS.NO_TASKS}</i>
							        {/if}
							      </div>
							      <div class="modal-footer">
							        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
							      </div>
							    </div>
							  </div>
							</div>
						{/if}

						{if $show_files && isset($s.files)}
							{if $show && isset($s.tasks)}&nbsp;{/if}<a href="#" data-toggle="modal" data-target="#files_{$s.ID}"><i class="fa fa-download"></i></a>

							<div class="modal fade" id="files_{$s.ID}" tabindex="-1" role="dialog">
							  <div class="modal-dialog">
							    <div class="modal-content">
							      <div class="modal-header">
							        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
							        <h4 class="modal-title" id="myModalLabel">{$lang.PROJECTS.FILES}: {$s.name}</h4>
							      </div>
							      <div class="modal-body">
								   	{if $s.files|@count > 0}
								    <ul>
							        {foreach from=$s.files item=f}
							        	<li><a href="{$cfg.PAGEURL}projects/{$s.ID}/{$f|urlencode}" target="_blank">{$f|substr:9|htmlentities}</a></li>
							        {/foreach}
							        </ul>
							        {else}
							        <i>{$lang.PROJECTS.NO_FILES}</i>
							       	{/if}
							      </div>
							      <div class="modal-footer">
							        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
							      </div>
							    </div>
							  </div>
							</div>
						{/if}
					</td>{/if}
				</tr>
				{/foreach}
			</table></div>
		{/if}
	</div>
</div>