{foreach from=$articles item=a}
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
{foreachelse}
<div class="alert alert-info">{$lang.CAT.NOTHING}</div>
{/foreach}