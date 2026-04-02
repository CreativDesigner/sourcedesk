{if $header}<div class="container">

<h1>{$title}</h1><hr>{/if}

{if $col == "1"}
{foreach from=$articles item=a}
<div class="card">
  <div class="card-body">
    <h5 class="card-title">{$a.name}</h3>
    <p>{$a.description}</p>
    {$a.link}
</div>
{foreachelse}
<div class="alert alert-info">{$lang.CAT.NOTHING}</div>
{/foreach}
{else}
{foreach from=$articles item=a}
{if $row == 1}
<div class="row">
{/if}

<div class="col-md-6">
<div class="card">
  <div class="card-body">
    <h5 class="card-title">{$a.name}</h3>
    <p>{$a.description}</p>
    {$a.link}
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
{/if}

{if $currency}{if $currencies|@count > 1}<form method="POST" class="form-inline" style="float: right; display: inline; padding-bottom: 20px;"><select name="currency" class="form-control" onchange="form.submit()"><option disabled>- {$lang.GENERAL.CHOOSE_CURRENCY} -</option>{foreach from=$currencies item=info key=code}<option value="{$code}"{if $myCurrency == $code} selected="selected"{/if}>{$info.name}</option>{/foreach}</select></form>{/if}{/if}

{if $header}</div>{/if}