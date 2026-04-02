<div id="content">
    <div class="container">
        <h1>{$lang.PREORDER.TITLE}</h1><hr />

        <p style="text-align:justify;">{$lang.PREORDER.INTRO|replace:"%p":$product|replace:"%a":$amount} {if $recurring}{$lang.PREORDER.INTRO3} {/if}{$lang.PREORDER.INTRO2}</p>

        {if $done}
        <div class="alert alert-success">{$lang.PREORDER.DONE}</div>
        {else}
        {if $raw_amount > $user.credit}
        <div class="alert alert-warning">{$lang.PREORDER.NOTENOUGH|replace:"%u":$topup}</div>
        {else}
        <a href="{$cfg.PAGEURL}preorder/{$ident}/do" class="btn btn-primary btn-block">{$lang.PREORDER.DO}</a>
        {/if}
        {/if}
    </div>
</div><br />