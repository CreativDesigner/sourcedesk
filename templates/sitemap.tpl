<div class="container">
	<h1>{$lang.SITEMAP.TITLE}<span class="pull-right"><small><a href="{$cfg.PAGEURL}sitemap/xml"><i class="fa fa-file-code-o"></i></a></small></span></h1><hr>

	<ul>
        {if class_exists("Sitemap")}
        {foreach from=Sitemap::sites() key=u item=n}
        {if is_array($n)}
        <li><a href="{rtrim($u, "/")}">{$n.0}</a></li>
        <ul>
            {foreach from=$n.1 key=u2 item=n2}
            <li><a href="{rtrim($u2, "/")}">{$n2}</a></li>
            {/foreach}
        </ul>
        {else}
        <li><a href="{rtrim($u, "/")}">{$n}</a></li>
        {/if}
        {/foreach}
        {/if}
    </ul>
</div>