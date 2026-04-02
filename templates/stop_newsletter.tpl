<div class="container">
	<h1>{$lang.STOP_NEWSLETTER.TITLE}</h1><hr />

	{if $step == 0}
		<p style="text-align: justify;">{$lang.STOP_NEWSLETTER.GREETINGS|replace:"%n":{{$newsuser.firstname|htmlentities}|cat:" "|cat:{$newsuser.lastname|htmlentities}}}</p>
		
		<p style="text-align: justify;">{$lang.STOP_NEWSLETTER.INTRO|replace:"%m":{$newsuser.mail|htmlentities}} <font class="text-primary">{$lang.STOP_NEWSLETTER.REQUEST}</font></p>

		<form method="POST">
			<div class="row">
				{foreach from=$nl item=name key=id}
				<div class="col-md-4">
					<div class="checkbox">
						<label>
							<input type="checkbox" name="nl[{$id}]" value="1">
							{$name|htmlentities}
						</label>
					</div>
				</div>
				{/foreach}
			</div><br />
		
			<p style="text-align: justify;">{$lang.STOP_NEWSLETTER.THANKS}</p>
	
			<input type="submit" name="stop" value="{$lang.STOP_NEWSLETTER.DO}" class="btn btn-primary btn-block">
		</form>
	{else if $step == 1}
		<div class="alert alert-success">{$lang.STOP_NEWSLETTER.DONE}</div>

		{$lang.STOP_NEWSLETTER.DONE_BACK}<br /><br />

		{$lang.STOP_NEWSLETTER.DONE_HINT|replace:"%m":{$newsuser.mail|htmlentities}}
	{else}
		<div class="alert alert-danger">{$lang.STOP_NEWSLETTER.FAILED}</div>

		<p style="text-align: justify;">{$lang.STOP_NEWSLETTER.REASONS}</p>
		<ul>
			<li>{$lang.STOP_NEWSLETTER.REASON_1}</li>
			<li>{$lang.STOP_NEWSLETTER.REASON_2}</li>
			<li>{$lang.STOP_NEWSLETTER.REASON_3}</li>
			<li>{$lang.STOP_NEWSLETTER.REASON_4}</li>
		</ul>
		<p style="text-align: justify;">{$lang.STOP_NEWSLETTER.FAILED_TODO}</p>
	{/if}    
</div>