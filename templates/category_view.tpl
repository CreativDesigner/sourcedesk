{if $header}<div class="container">

<h1>{$title}</h1><hr>{/if}

{include $file articles=$articles row=$row cat=$cat}

{if $currency}{if $currencies|@count > 1}<form method="POST" class="form-inline" style="float: right; display: inline; padding-bottom: 20px;"><select name="currency" class="form-control" onchange="form.submit()"><option disabled>- {$lang.GENERAL.CHOOSE_CURRENCY} -</option>{foreach from=$currencies item=info key=code}<option value="{$code}"{if $myCurrency == $code} selected="selected"{/if}>{$info.name}</option>{/foreach}</select></form>{/if}{/if}

{if $header}</div>{/if}