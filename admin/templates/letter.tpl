{assign "pl" $lang.LETTER_EDIT}
{assign "ql" $lang.QUOTE}
{assign "cl" $lang.LETTER_CREATE}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$pl.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($success)}<div class="alert alert-success">{$success}</div>{/if}
{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

<form accept-charset="UTF-8" role="form" method="post" enctype="multipart/form-data">
	<div class="form-group" style="position: relative;">
		<label>{$ql.DATE}</label>
		<input type="text" name="date" value="{if isset($smarty.post.date)}{$smarty.post.date|htmlentities}{else}{dfo d=$letter.date m=0}{/if}" placeholder="{dfop}" class="form-control datepicker">
	</div>

	<div class="form-group">
		<label>{$ql.CUST}</label><br />
		{if $user}<a href="?p=customers&edit={$user.ID}">{$user.fname}</a>{else}<i>Keine Zuordnung</i>{/if}
	</div>

	<div class="form-group">
		<label>{$ql.ADDRESS}</label><br />
		<div class="row">
			<div class="col-md-4">
				<input type="text" name="firstname" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname|htmlentities}{else}{$r.0|htmlentities}{/if}" class="form-control" placeholder="{$ql.FN}" />
			</div>

			<div class="col-md-4">
				<input type="text" name="lastname" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname|htmlentities}{else}{$r.1|htmlentities}{/if}" class="form-control" placeholder="{$ql.LN}" />
			</div>

			<div class="col-md-4">
				<input type="text" name="company" value="{if isset($smarty.post.company)}{$smarty.post.company|htmlentities}{else}{$r.8|htmlentities}{/if}" class="form-control" placeholder="{$ql.CP}" />
			</div>
		</div>

		<div class="row" style="margin-top: 10px;">
			<div class="col-md-10">
				<input type="text" name="street" value="{if isset($smarty.post.street)}{$smarty.post.street|htmlentities}{else}{$r.2|htmlentities}{/if}" class="form-control" placeholder="{$ql.ST}" />
			</div>

			<div class="col-md-2">
				<input type="text" name="street_number" value="{if isset($smarty.post.street_number)}{$smarty.post.street_number|htmlentities}{else}{$r.3|htmlentities}{/if}" class="form-control" placeholder="{$ql.SN}" />
			</div>
		</div>

		<div class="row" style="margin-top: 10px;">
			<div class="col-md-4">
				<input type="text" name="postcode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode|htmlentities}{else}{$r.4|htmlentities}{/if}" class="form-control" placeholder="{$ql.PC}" />
			</div>

			<div class="col-md-8">
				<input type="text" name="city" value="{if isset($smarty.post.city)}{$smarty.post.city|htmlentities}{else}{$r.5|htmlentities}{/if}" class="form-control" placeholder="{$ql.CT}" />
			</div>
		</div>

		<div class="row" style="margin-top: 10px;">
			<div class="col-md-6">
				<select name="country" class="form-control">
					{foreach from=$countries item=name key=id}
					<option value="{$id}"{if (!isset($smarty.post.country) && $r.6 == $name) || (isset($smarty.post.country) && $smarty.post.country == $id)} selected="selected"{/if}>{$name}</option>
					{/foreach}
				</select>
			</div>

			<div class="col-md-6">
				<select name="language" class="form-control">
					{foreach from=$languages item=name key=id}
					<option value="{$id}"{if (!isset($smarty.post.language) && $r.7 == $id) || (isset($smarty.post.language) && $smarty.post.language == $id)} selected="selected"{/if}>{$name}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label>{$cl.SUBJECT}</label>
		<input type="text" name="subject" value="{if isset($smarty.post.subject)}{$smarty.post.subject|htmlentities}{else}{$letter.subject|htmlentities}{/if}" placeholder="{$cl.SUBJECTP}" class="form-control">
	</div>

	<div class="form-group">
		<label>{$cl.TEXT}</label>
		<textarea name="text" class="form-control summernote" style="resize: none; height: 200px;">{if isset($smarty.post.subject)}{$smarty.post.subject|htmlentities}{else}{$letter.text|htmlentities|nl2br}{/if}</textarea>
	</div>

	<div class="form-group">
		<label>{$cl.PDF}</label>
		{if $attached}
		<span id="hasa"><br />
		<a href="#" onclick="removeAttachment(); return false;"><i class="fa fa-times"></i> {$pl.DEL}</a>
		<input type="hidden" name="dnra" value="1">
		</span>
		{/if}

		<input type="file"{if !$attached} name="attachment"{else} style="display: none;"{/if} class="form-control" id="newa">
	</div>

	<div class="form-group">
	<label>{$cl.STATUS}</label><br />
		<label class="radio-inline">
			<input type="radio" name="sent" value="0"{if (!isset($smarty.post.sent) && $letter.sent == 0) || (isset($smarty.post.sent) && $smarty.post.sent == "0")} checked{/if}>
			{$cl.STATUS1}
		</label>
		<label class="radio-inline">
			<input type="radio" name="sent" value="1"{if (!isset($smarty.post.sent) && $letter.sent == 1) || (isset($smarty.post.sent) && $smarty.post.sent == "1")} checked{/if}>
			{$cl.STATUS2}
		</label>
	</div>

    <button type="submit" class="btn btn-primary btn-block">{$pl.DO}</button>
</form>

<script>
function removeAttachment() {
	$("#hasa").remove();
	$("#newa").show().attr("name", "attachment");
}
</script>