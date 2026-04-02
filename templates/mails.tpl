<div class="container">
	<h1>{$lang.NAV.MAILS}</h1><hr/>

	{$lang.MAILS.INTRO}{if $cfg.MAIL_LEADTIME > 0} {$lang.MAILS.DELETE|replace:"%m":$cfg.MAIL_LEADTIME}{/if}<br /><br />

	{if $mails|count <= 0}<i>{$lang.MAILS.NOTHING}</i>{else}

	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th>{$lang.MAILS.DATE}</th>
				<th>{$lang.MAILS.SUBJECT}</th>
			</tr>
			
			{foreach from=$mails item=mail key=id}
			<tr>
				<td>{dfo d=$mail.sent s=1}</td>
				<td><a href="{$mail.url}" target="_blank">{$mail.subject|htmlentities}</a></td>
			</tr>
			{/foreach}
		</table>
	</div>

	{/if}
</div><br />