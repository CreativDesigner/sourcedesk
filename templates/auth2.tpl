<div id="content">
    <div class="container">
        <h1>{$lang.AUTH2.TITLE}</h1><hr />

        {if isset($suc)}
        <div class="alert alert-success" style="text-align: justify;">{$lang.AUTH2.OK}</div>
        <p style="text-align: justify;">{$lang.AUTH2.OK2}</p>
        {else}
        <p style="text-align: justify;">{$lang.AUTH2.INFO}</p>

        <form method="POST">
            <select name="tld" class="form-control" onchange="form.submit()">
                <option value="" selected="selected" disabled="disabled">{$lang.AUTH2.PC}</option>
                {foreach from=$tlds item=tld}<option value="{$tld}"{if isset($a2i) && $a2i.tld == $tld} selected="selected"{/if}>.{$tld|htmlentities}</option>{/foreach}
            </select>
        </form>

        {if isset($a2i)}
            <p style="text-align: justify; margin-top: 10px;">{$lang.AUTH2.COSTS|replace:"%t":$a2i.tld|replace:"%p":$a2i.price_f}</p>

            {if !$logged_in}
                <div class="alert alert-warning">{$lang.AUTH2.NLI}</div>
            {else}
                <p style="text-align: justify;">{$lang.AUTH2.INTRO2}</p>

                <form method="POST">
                    {if isset($err)}<div class="alert alert-danger" style="margin-bottom: 10px;"><b>{$lang.GENERAL.ERROR}</b> {$err}</div>{/if}

                    <div class="input-group">
                        <input type="text" name="sld" value="{if isset($smarty.post.sld)}{$smarty.post.sld}{/if}" placeholder="{$lang.AUTH2.PH}" class="form-control" />
                        <span class="input-group-addon">.{$a2i.tld|htmlentities}</span>
                    </div>

                    {if $limit < $a2i.price_t}
                    <div class="alert alert-info" style="margin-top: 10px; margin-bottom: 0; text-align: justify;">{$lang.AUTH2.NEC|replace:"%u":$cfg.PAGEURL}</div>
                    {else}
                    <div class="alert alert-warning" style="margin-top: 10px; margin-bottom: 0; text-align: justify;">{$lang.AUTH2.COSTS2|replace:"%p":$a2i.price_f}</div>
                    {/if}

                    <input type="hidden" name="token" value="{$smarty.session.a2token}" />

                    <input type="submit" value="{$lang.AUTH2.DO}" class="btn btn-primary btn-block" style="margin-top: 10px;"{if $user.credit < $a2i.price_t} disabled="disabled"{/if} />
                </form>
            {/if}
        {/if}
        {/if}
    </div>
</div>