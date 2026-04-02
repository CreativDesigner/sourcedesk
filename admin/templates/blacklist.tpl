<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.BLACKLIST.NAME}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="row">
	<div class="col-md-3">
		<div class="list-group">
			<a class="list-group-item{if $tab == "ips"} active{/if}" href="./?p=blacklist">{$lang.BLACKLIST.LOCKED_IPS}</a>
			<a class="list-group-item{if $tab == "f2b"} active{/if}" href="./?p=blacklist&tab=f2b">{$lang.BLACKLIST.FAIL2BAN_SETTINGS}</a>
			<a class="list-group-item{if $tab == "mails"} active{/if}" href="./?p=blacklist&tab=mails">{$lang.BLACKLIST.LOCKED_MAILS}</a>
		</div>
	</div>

	<div class="col-md-9">
	
	{if $tab == "ips"}

	{if isset($ip_msg)}{$ip_msg}{/if}
	
	<form method="POST" class="form-inline">
		<input type="text" name="new_ip" value="{if isset($smarty.post.new_ip)}{$smarty.post.new_ip}{/if}" placeholder="127.0.0.1" class="form-control">
		<input type="text" name="reason" value="{if isset($smarty.post.reason)}{$smarty.post.reason}{/if}" placeholder="{$lang.BLACKLIST.REASON}" class="form-control">
		<input type="submit" name="add_ip" value="{$lang.BLACKLIST.ADD_IP}" class="btn btn-primary">
	</form>
	
	<br />
	
	<form method="POST">
	
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
					<th>{$lang.BLACKLIST.IP}</th>
					<th>{$lang.BLACKLIST.SINCE}</th>
					<th>{$lang.BLACKLIST.UNTIL}</th>
					<th>{$lang.BLACKLIST.REASON}</th>
				</tr>
				
				{foreach from=$locked_ips item=ip}
					<tr>
						<td width="30px"><input type="checkbox" class="checkbox" onchange="javascript:toggle();" name="delete_ip[{$ip.type}_{$ip.id}]" value="true"></td>
						<td>{$ip.ip|htmlentities}</td>
						<td>{$ip.since}</td>
						<td>{$ip.until}</td>
						<td>{$ip.reason}</td>
					</tr>
				{foreachelse}
					<tr>
						<td colspan="5"><center>{$lang.BLACKLIST.NO_IPS}</center></td>
					</tr>
				{/foreach}
			</table>
		</div>
		
		{if $locked_ips|count > 0}<input type="submit" name="delete_ips" value="{$lang.BLACKLIST.DELETE}" class="btn btn-warning"> <input type="submit" name="delete_all_ips" value="{$lang.BLACKLIST.DELETE_ALL}" class="btn btn-danger">{/if}
	
	</form>
	
	{else if $tab == "mails"}

	{if isset($mail_msg)}{$mail_msg}{/if}
	
	<form method="POST" class="form-inline">
		<input type="text" name="new_mail" value="{if isset($smarty.post.new_mail)}{$smarty.post.new_mail}{/if}" placeholder="i@mail.com {$lang.BLACKLIST.OR} mail.com" class="form-control">
		<input type="submit" name="add_mail" value="{$lang.BLACKLIST.ADD_MAIL}" class="btn btn-primary">
	</form>
	
	<br />
	
	<form method="POST">
	
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
					<th>{$lang.BLACKLIST.EMAIL}</th>
					<th>{$lang.BLACKLIST.SINCE}</th>
				</tr>
				
				{foreach from=$locked_mails item=mail}
					<tr>
						<td width="30px"><input type="checkbox" class="checkbox" onchange="javascript:toggle();" name="delete_mail[{$mail.id}]" value="true"></td>
						<td>{$mail.email|htmlentities}</td>
						<td>{$mail.since}</td>
					</tr>
				{foreachelse}
					<tr>
						<td colspan="5"><center>{$lang.BLACKLIST.NO_MAILS}</center></td>
					</tr>
				{/foreach}
			</table>
		</div>
		
		{if $locked_mails|count > 0}<input type="submit" name="delete_mails" value="{$lang.BLACKLIST.DELETE}" class="btn btn-warning"> <input type="submit" name="delete_all_mails" value="{$lang.BLACKLIST.DELETE_ALL}" class="btn btn-danger">{/if}
	
	</form>

	{else if $tab == "f2b"}
	{if isset($f2b_msg)}{$f2b_msg}{/if}

	<form accept-charset="UTF-8" role="form" method="post">
	    <div class="checkbox">
			<label>
				<input type="checkbox" name="fail2ban_active" value="1"{if (isset($smarty.post.fail2ban_active)) || (!isset($smarty.post.f2b_save) && $cfg.FAIL2BAN_ACTIVE)} checked="checked"{/if}> {$lang.BLACKLIST.F2B_ACTIVATE}
			</label>
  		</div>

		<div class="form-group">
			<label>{$lang.BLACKLIST.F2B_MAX}</label>
			<input type="text" name="fail2ban_failed" value="{if isset($smarty.post.fail2ban_failed)}{$smarty.post.fail2ban_failed}{else}{$cfg.FAIL2BAN_FAILED}{/if}" placeholder="{$lang.BLACKLIST.F2B_MAXP}" class="form-control">
		</div>

		<div class="form-group">
			<label>{$lang.BLACKLIST.F2B_TIME}</label>
			<input type="text" name="fail2ban_locked" value="{if isset($smarty.post.fail2ban_locked)}{$smarty.post.fail2ban_locked}{else}{$cfg.FAIL2BAN_LOCKED}{/if}" placeholder="{$lang.BLACKLIST.F2B_TIMEP}" class="form-control">
				</div>
	    <center><button type="submit" name="f2b_save" class="btn btn-primary btn-block">{$lang.GENERAL.SAVE}</button><br /></center>
	 </form>
	{else}
	<div class="alert alert-danger">{$lang.GENERAL.SUBPAGE_NOT_FOUND}</div>
	{/if}
</div></div>