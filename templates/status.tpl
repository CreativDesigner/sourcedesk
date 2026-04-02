{if !$archive}
<div class="container">
    <h1>{$lang.STATUS.TITLE}{if $archiveNum} <small><a href="{$cfg.PAGEURL}status/archive">{$lang.STATUS.ARCHIVE}</a></small>{/if}</h1><hr>
    <p style="text-align: justify">{$lang.STATUS.INTRO}</p>

    {foreach from=$status item=s}
    <div class="panel panel-{if $s.status}success{else}{$s.priority|htmlentities}{/if}">
        <div class="panel-heading">{if $s.status}<span class="label label-success">{$lang.STATUS.RESOLVED}</span>{else}<span class="label label-default">{$lang.STATUS.INPROGRESS}</span>{/if} {$s.title|htmlentities} <small>{$lang.STATUS.LAST_CHANGED}: {dfo d=$s.last_changed}</small></div>
        <div class="panel-body">
            {if $s.start >= 0 && $s.until >= 0}
            <b>{dfo d=$s.start} - {dfo d=$s.until}</b><br /><br />
            {else if $s.start >= 0}
            <b>{$lang.STATUS.SINCE} {dfo d=$s.start}</b><br /><br />
            {else if $s.until >= 0}
            <b>{$lang.STATUS.UNTIL} {dfo d=$s.until}</b><br /><br />
            {/if}

            {$s.message|htmlentities|nl2br}
        </div>
    </div>
    {/foreach}

    {if $servers|count}
    <ul class="list-group" style="margin-bottom: 0; margin-top: 15px;">
        {foreach from=$servers item=s}
        <li class="list-group-item">
            <b>{$s.0}</b><span class="pull-right">{$s.1}</span><br />{$lang.STATUS.LAST}: {$s.2}
        </li>
        {/foreach}
    </ul>
    {else}
    <i>{$lang.STATUS.NOTHING}</i>
    {/if}
</div>
{else}
<div class="container">
    <h1>{$lang.STATUS.TITLE} <small>{$lang.STATUS.ARCHIVE}</small><a href="{$cfg.PAGEURL}status" class="pull-right"><i class="fa fa-reply"></i></a></h1><hr>
    {foreach from=$status item=s}
    <div class="panel panel-{if $s.status}success{else}{$s.priority|htmlentities}{/if}">
        <div class="panel-heading">{if $s.status}<span class="label label-success">{$lang.STATUS.RESOLVED}</span>{else}<span class="label label-default">{$lang.STATUS.INPROGRESS}</span>{/if} {$s.title|htmlentities} <small>{$lang.STATUS.LAST_CHANGED}: {dfo d=$s.last_changed}</small></div>
        <div class="panel-body">
            {if $s.start >= 0 && $s.until >= 0}
            <b>{dfo d=$s.start} - {dfo d=$s.until}</b><br /><br />
            {else if $s.start >= 0}
            <b>{$lang.STATUS.SINCE} {dfo d=$s.start}</b><br /><br />
            {else if $s.until >= 0}
            <b>{$lang.STATUS.UNTIL} {dfo d=$s.until}</b><br /><br />
            {/if}

            {$s.message|htmlentities|nl2br}
        </div>
    </div>
    {/foreach}
</div>
{/if}