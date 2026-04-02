<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.BIRTHDAYS.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="row">
	<div class="col-md-3">
		<div class="list-group">
			<a class="list-group-item{if $tab == "soon"} active{/if}" href="./?p=birthdays">{$lang.BIRTHDAYS.SOON}</a>
			<a class="list-group-item{if $tab == "done"} active{/if}" href="./?p=birthdays&tab=done">{$lang.BIRTHDAYS.DONE}</a>
			<a class="list-group-item{if $tab == "settings"} active{/if}" href="./?p=birthdays&tab=settings">{$lang.BIRTHDAYS.SETTINGS}</a>
		</div>
	</div>

	<div class="col-md-9">
	
	{if $tab == "soon"}

	{$th}
	
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th>{$lang.BIRTHDAYS.BIRTHDAY}</th>
				<th>{$lang.BIRTHDAYS.CUSTOMER}</th>
			</tr>
			
			{foreach from=$birthdays item=b}
				<tr>
					<td>{$b.date} ({$b.years})</td>
					<td>{$b.customer}</td>
				</tr>
			{foreachelse}
				<tr>
					<td colspan="5"><center>{$lang.BIRTHDAYS.NO_SOON}</center></td>
				</tr>
			{/foreach}
		</table>
	</div>

	{$tf}
	
	{else if $tab == "done"}

	{$th}
	
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th>{$lang.BIRTHDAYS.BIRTHDAY}</th>
				<th>{$lang.BIRTHDAYS.CUSTOMER}</th>
			</tr>
			
			{foreach from=$birthdays item=b}
				<tr>
					<td>{$b.date} ({$b.years})</td>
					<td>{$b.customer}</td>
				</tr>
			{foreachelse}
				<tr>
					<td colspan="5"><center>{$lang.BIRTHDAYS.NO_DONE}</center></td>
				</tr>
			{/foreach}
		</table>
	</div>

	{$tf}

	{else if $tab == "settings"}
	{if isset($birthday_msg)}{$birthday_msg}{/if}

	<form accept-charset="UTF-8" role="form" method="post">
	    <div class="checkbox">
			<label>
				<input type="checkbox" name="cronjob_active" value="1"{if (isset($smarty.post.cronjob_active)) || (!isset($smarty.post.cronjob_active) && $cronjob_active)} checked="checked"{/if}> {$lang.BIRTHDAYS.ACTIVE}
			</label>
  		</div>

		<div class="form-group">
			<label>{$lang.BIRTHDAYS.EMAILS}</label><br />
			{foreach from=$languages item=language key=key}
				<input type="button" class="btn btn-primary" value="{$language}" data-toggle="modal" data-target="#modal_{$key}" />
				<div class="modal fade" id="modal_{$key}" tabindex="-1" role="dialog">
				  <div class="modal-dialog">
				    <div class="modal-content">
				      <div class="modal-header">
				        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
				        <h4 class="modal-title">{$language}</h4>
				      </div>
				      <div class="modal-body">
				 	    <input type="text" name="subject[{$key}]" class="form-control" value="{if isset($smarty.post.subject.$key)}{$smarty.post.subject.$key}{else}{$subject.$key}{/if}" placeholder="{$lang.BIRTHDAYS.SUBJECT}" /><br />
				 	    <textarea class="form-control" name="text[{$key}]" placeholder="{$lang.BIRTHDAYS.TEXT}" style="width:100%; resize:none; height:250px;">{if isset($smarty.post.text.$key)}{$smarty.post.text.$key}{else}{$text.$key}{/if}</textarea>
				      </div>
				    </div>
				  </div>
				</div>
			{/foreach}
			<p class="help-block">{$lang.BIRTHDAYS.EMAILS_HINT}</p>
		</div>

		<div class="form-group">
			<label>{$lang.BIRTHDAYS.VOUCHER}</label>
			<select name="birthday_voucher" class="form-control">
				<option value="0">- {$lang.BIRTHDAYS.NO_VOUCHER} -</option>
			{foreach from=$vouchers item=code key=id}
				<option value="{$id}"{if (isset($smarty.post.birthday_voucher) && $smarty.post.birthday_voucher == $id) || (!isset($smarty.post.birthday_voucher) && $cfg.BIRTHDAY_VOUCHER == $id)} selected="selected"{/if}>{$code}</option>
			{/foreach}
			</select>
			<p class="help-block">{$lang.BIRTHDAYS.VOUCHER_HINT}</p>
		</div>

	    <center><button type="submit" name="birthday_save" class="btn btn-primary btn-block">{$lang.GENERAL.SAVE}</button><br /></center>
	 </form>
	{else}
	<div class="alert alert-danger">{$lang.GENERAL.SUBPAGE_NOT_FOUND}</div>
	{/if}
</div></div>