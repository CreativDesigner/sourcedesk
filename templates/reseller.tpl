<div id="content">
    <div class="container">
        <h1>{$title}{if $tab == "customers"}{if $action == "add" || $action == "edit"}<span class="pull-right"><a href="{$cfg.PAGEURL}reseller/customers"><i class="fa fa-reply"></i></a></span>{else}<span class="pull-right"><a href="{$cfg.PAGEURL}reseller/customers/add"><i class="fa fa-plus-circle"></i></a></span>{/if}{/if}</h1><hr>

        {if !empty($suc)}<div class="alert alert-success">{$suc}</div>{/if}
        {if !empty($err)}<div class="alert alert-danger">{$err}</div>{/if}
        
        {if $tab == "customers"}

        {if $action == "add"}

        <form method="POST">
            <div class="form-group">
                <label>{$l.MAIL}</label>
                <input class="form-control" type="email" name="mail" value="{if isset($smarty.post.mail)}{$smarty.post.mail|htmlentities}{/if}">
            </div>

            <div class="form-group">
                <label>{$l.PASSWORD}</label>
                <input class="form-control" type="text" name="password" value="{if isset($smarty.post.password)}{$smarty.post.password|htmlentities}{else}{$defpw}{/if}">
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.ADD}">
        </form>

        {elseif $action == "edit"}

        <form method="POST">
            <div class="form-group">
                <label>{$l.NEWPW}</label>
                <input class="form-control" type="text" name="password" value="{if isset($smarty.post.password)}{$smarty.post.password|htmlentities}{else}{$defpw}{/if}">
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.EDIT}">
        </form>

        {else}

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>{$l.CUSTOMER}</th>
                    <th width="25%" style="text-align: center;">{$l.CONTRACTS}</th>
                    <th width="30%">{$l.ACTIONS}</th>
                </tr>

                {foreach from=$customers item=mail key=id}
                <tr>
                    <td><a href="mailto:{$mail|htmlentities}">{$mail|htmlentities}</a></td>
                    <td style="text-align: center;"><a href="#" onclick="return false;" title="{$contracts.$id|implode:"\n"}" data-toggle="tooltip">{$contracts.$id|count}</a></td>
                    <td>
                        <a href="?login={$id}" target="_blank" class="btn btn-success btn-xs">{$l.LOGIN}</a>
                        <a href="{$cfg.PAGEURL}reseller/customers/edit/{$id}" class="btn btn-primary btn-xs">{$l.EDIT}</a>
                        <a href="?delete={$id}" class="btn btn-danger btn-xs">{$l.DELETE}</a>
                    </td>
                </tr>
                {/foreach}
            </table>
        </div>

        <style>
        .tooltip-inner {
            white-space:pre-wrap;
        }
        </style>

        {/if}

        {elseif $tab == "contracts"}

        <form method="POST">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>{$l.CONTRACT}</th>
                    <th>{$l.CUSTOMER}</th>
                </tr>

                {foreach from=$contracts item=c}
                {if $c.active == 1}
                <tr>
                    <td style="vertical-align: middle;">#{$c.ID} - {$c.name|htmlentities}{if $c.description} - {$c.description|htmlentities}{/if}</td>
                    <td><select name="reseller_customer[{$c.ID}]" class="form-control input-sm"><option value="0">{$l.NA}</option>{foreach from=$customers item=mail key=id}<option value="{$id}"{if $c.reseller_customer == $id} selected=""{/if}>{$mail|htmlentities}</option>{/foreach}</select></td>
                </tr>
                {/if}
                {/foreach}
            </table>
        </div>

        <input type="submit" class="btn btn-primary btn-block" value="{$l.CSAVE}">
        </form>

        {elseif $tab == "config"}

        <form method="POST">
            <div class="form-group">
                <label>{$l.PAGENAME}</label>
                <input type="text" maxlength="35" class="form-control" name="reseller_pagename" value="{if $user.reseller_pagename}{$user.reseller_pagename|htmlentities}{elseif $user.company}{$user.company|htmlentities}{else}{$user.name|htmlentities}{/if}">
            </div>

            <div class="form-group">
                <label>{$l.DEFAULT_URL}</label><br /><a target="_blank" href="{$raw_cfg.PAGEURL}res/?resid={$user.ID}">{$raw_cfg.PAGEURL}res/?resid={$user.ID}</a>
            </div>

            <div class="form-group">
                <label>{$l.OWN_URL}</label><br />{$l.OWN_URL_H}
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.SAVE}">
        </form>

        {/if}
    </div>
</div>