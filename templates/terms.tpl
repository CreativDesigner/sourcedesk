<div id="content">
	<div class="container">
			<h1>{$lang.TOS.LONG} {if $new_tos}<small><span class="label label-success">{$lang.TOS.NEW}</span></small>{/if}</h1><hr><p style="text-align:justify;">{$terms}</p>
			{if isset($historyTerms) && $cfg.TERMS_HISTORY}
			<b>{$lang.TOS.OLD}:</b><br />
			<ul>
			{foreach from=$historyTerms key=id item=term}
				<li><a href="#" data-toggle="modal" data-target="#terms_{$id}">{$lang.TOS.FROM|replace:"%d":$term.time}</a></li>
				<div class="modal fade" id="terms_{$id}" tabindex="-1" role="dialog">
				  <div class="modal-dialog modal-lg">
				    <div class="modal-content">
				      <div class="modal-header">
				        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
				        <h4 class="modal-title">{$lang.TOS.FROM|replace:"%d":$term.time}</h4>
				      </div>
				      <div class="modal-body">
				        {$term.terms}
				      </div>
				    </div>
				  </div>
				</div>
			{/foreach}
			</ul><br />
			{/if}
        {if $new_tos}
            <center>
            <form method="POST"><input type="hidden" name="tid" value="{$tid}"><input type="submit" class="btn btn-success btn-block" value="{$lang.TOS.ACCEPT}"><br/>
            </form></center>{/if}
	</div>
</div>