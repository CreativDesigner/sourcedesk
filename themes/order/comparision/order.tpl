{if $articles|@count}
<div class="row">

{if $articles|count >= 4 && $articles|count % 4 == 0}
{assign "width" "3"}
{elseif $articles|count >= 3 && $articles|count % 3 == 0}
{assign "width" "4"}
{elseif $articles|count >= 2 && $articles|count % 2 == 0}
{assign "width" "6"}
{else}
{assign "width" "12"}
{/if}

{foreach from=$articles item=a}
<div class="col-md-{$width}">
    <div class="panel panel-default">
        <div class="panel-body">
            <center>
            <h3 style="margin-top: 0;">{infix n={nfo i={$a.price}}}</h3>
            {assign "langkey" $a.billing|strtoupper}
            {$lang.CART.$langkey|ucfirst}
            {foreach from=$a.parameters key=k item=v}
            <hr />
            <i class="fa fa-check"></i> <b>{$v}</b> {$k}
            {/foreach}
            {if $a.parsed_desc}<hr />{$a.parsed_desc}{/if}
            </center>
        </div>
        <div class="panel-footer">
            {$a.link|replace:"<a":"<a class=\"btn-primary btn btn-block\""}
        </div>
    </div>
</div>
{/foreach}

</div>
{else}
<div class="alert alert-info">{$lang.CAT.NOTHING}</div>
{/if}