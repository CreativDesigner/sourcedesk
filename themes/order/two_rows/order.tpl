{foreach from=$articles item=a}
{if $row == 1}
<div class="row">
{/if}

<div class="col-md-6">
<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">{$a.name}</h3>
  </div>
  <div class="panel-body" style="text-align:justify;">
    {$a.description}
  </div>
  <div class="panel-footer">
    {$a.link}
  </div>
</div>
</div>

{if $row == 1}
{assign "row" "0"}
{assign "changed" "1"}
{else}
</div>
{assign "row" "1"}
{/if}
{foreachelse}
<div class="alert alert-info">{$lang.CAT.NOTHING}</div>
{/foreach}

{if $row == 0 && $changed}
</div>
{/if}