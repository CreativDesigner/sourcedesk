<div class="container">
    <h1>{$l.TITLE} <small>{if $username}<span class="label label-success">{$l.OK}</span>{else}<span class="label label-default">{$l.NOK}</span>{/if}</small></h1><hr>

    {if $username}
    <p style="text-align: justify">{$l.OKH|replace:"%u":$username}</p>
    <a href="{$u}" class="btn btn-primary btn-block">Zur&uuml;ck zum Forum</a>
    {else}
    {if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}
    <p style="text-align: justify">{$l.NOKH|replace:"%s":('<a href="'|cat:$u|cat:'" target="_blank">')|replace:"%e":'</a>'}</p>

    <form method="POST">
	    <input type="text" name="assign" value="{if isset($smarty.post.assign)}{$smarty.post.assign|htmlentities}{else if isset($smarty.get.username)}{$smarty.get.username|htmlentities}{/if}" placeholder="{$l.UNP}" class="form-control" />

	    <input type="submit" class="btn btn-primary btn-block" value="{$l.NOKG}" style="margin-top: 10px;" />
	</form>
    {/if}
</div><br />