<div class="container">
	{if !isset($smarty.get.until)}
	<h1>{$lang.LOCKED.BANNED}</h1><hr />
	{else}
	<h1>{$lang.LOCKED.TEMPORARY} <small>{$lang.LOCKED.FAILED_LOGIN}</small></h1><hr />
	{/if}
	
	{if !isset($until)}{$lang.LOCKED.INTRO|replace:"%p":$cfg.PAGENAME}{else}{$lang.LOCKED.INTRO_TEMPORARY}{/if}<br /><br />
	{$lang.LOCKED.IP}: {$ip}
	{if isset($until)}
	<br />{$lang.LOCKED.UNTIL}: {$until}<br />
	{$lang.LOCKED.SERVER}: {$server}
	{else}
	<br />{$lang.LOCKED.REASON}: {$reason|htmlentities}
	{/if}
	<br /><br />
	{$lang.LOCKED.FOOTER}<br /><br />
	<a href="{$cfg.PAGEURL}" class="btn btn-primary">{$lang.LOCKED.RELOAD_PAGE}</a>
</div>