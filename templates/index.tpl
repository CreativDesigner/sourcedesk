<div class="jumbotron">
      <div class="container">
        <h1>{$lang.INDEX.HEADLINE|replace:"%p":$cfg.PAGENAME}</h1>
        <p>{$intro|nl2br}</p>
      </div>
    </div>

    <div class="container">
    	<div class="row">
    		<div class="col-md-6">
    		    <ul class="list-group">
				    <li class="list-group-item active">{$lang.INDEX.NEW_PRODUCTS}</li>
				    {foreach from=$np item=p}<li class="list-group-item">{$p.name} <span style="float:right"><a href="{$cfg.PAGEURL}product/{$p.ID}">{$lang.INDEX.INFO}</a></span></li>
				    {foreachelse}<li class="list-group-item"><center>{$lang.INDEX.NOTHING}</center></li>
				    {/foreach}
				</ul>
    		</div>
    		<div class="col-md-6">
    			<ul class="list-group">
    				<li class="list-group-item active">{$lang.INDEX.POPULAR_PRODUCTS}</li>
				    {foreach from=$pp item=p}<li class="list-group-item">{$p.name} <span style="float:right"><a href="{$cfg.PAGEURL}product/{$p.ID}">{$lang.INDEX.INFO}</a></span></li>
				    {foreachelse}<li class="list-group-item"><center>{$lang.INDEX.NOTHING}</center></li>
				    {/foreach}
				</ul>
			</div>
		</div>
    </div>