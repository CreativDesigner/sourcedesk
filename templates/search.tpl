<div class="container">
	<h1 class="hidden-xs">{$lang.GENERAL.SEARCH}{if isset($searchword)} <small><a href="#search">{$searchword|htmlentities}</a></small>{/if}</h1><h1 class="visible-xs">{$lang.GENERAL.SEARCH}{if isset($searchword)} <small>{$searchword|htmlentities}</small>{/if}</h1><hr/>

	{if !isset($searchword)}
	<i>{$lang.SEARCH.NO_PROVIDED}</i>
	<form method="POST" style="margin-top: 20px;">
		<input type="text" name="searchword" value="" placeholder="{$lang.GENERAL.ENTER_SEARCHWORD}" class="form-control" />
		<input type="submit" value="{$lang.SEARCH.DO}" style="margin-top: 10px;" class="btn btn-primary btn-block" />
	</form>
	{else}
		{if count($results) > 0}
			<i>{if count($results) == 1}{$lang.SEARCH.ONE_RESULT}{else}{$lang.SEARCH.X_RESULTS|replace:"%x":count($results)}{/if}</i><br /><br />

			<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
			{foreach from=$results item=res key=i}
			 <div class="panel panel-default">
			    <div class="panel-heading" role="tab" id="heading{$i}">
			      <h4 class="panel-title">
			        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse{$i}" aria-expanded="true" aria-controls="collapse{$i}">
			          {$res.name}
			        </a>
			      </h4>
			    </div>
			    <div id="collapse{$i}" class="panel-collapse collapse{if $i == 0} in{/if}" role="tabpanel" aria-labelledby="heading{$i}">
			      <div class="panel-body" style="text-align: justify;">
			        {$res.description|nl2br}<br /><br /><a href="{$res.url}" class="btn btn-primary btn-block" target="_blank">{$res.btn}</a>
			      </div>
			    </div>
			  </div>
			{/foreach}
			</div>
		{else}
			{if strlen(trim($searchword)) < 3}
				<i>{$lang.SEARCH.3C}</i>
				<form method="POST" style="margin-top: 20px;">
					<input type="text" name="searchword" value="{$searchword|htmlentities}" placeholder="{$lang.GENERAL.ENTER_SEARCHWORD}" class="form-control" />
					<input type="submit" value="{$lang.SEARCH.DO}" style="margin-top: 10px;" class="btn btn-primary btn-block" />
				</form>
			{elseif $blacklist == true}
				<i>{$lang.SEARCH.IV}</i>
				<form method="POST" style="margin-top: 20px;">
					<input type="text" name="searchword" value="{$searchword|htmlentities}" placeholder="{$lang.GENERAL.ENTER_SEARCHWORD}" class="form-control" />
					<input type="submit" value="{$lang.SEARCH.DO}" style="margin-top: 10px;" class="btn btn-primary btn-block" />
				</form>
			{else}
				<i>{$lang.SEARCH.NT}</i>
				<form method="POST" style="margin-top: 20px;">
					<input type="text" name="searchword" value="{$searchword|htmlentities}" placeholder="{$lang.GENERAL.ENTER_SEARCHWORD}" class="form-control" />
					<input type="submit" value="{$lang.SEARCH.DO}" style="margin-top: 10px;" class="btn btn-primary btn-block" />
				</form>
			{/if}
		{/if}
	{/if}
</div><br />