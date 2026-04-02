<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.TEXTS.TITLE}{if $t == "terms" && (!isset($smarty.get.action) || $smarty.get.action != "add")}<a href="?p=texts&t=terms&action=add" class="pull-right"><i class="fa fa-plus-circle"></i></a>{/if}</h1>		
	</div>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="list-group">
			{foreach from=$edit key=k item=v}
			<a class="list-group-item{if $t == $k} active{/if}" href="./?p=texts&t={$k}">{$v.title}</a>
			{/foreach}
		</div>
	</div>
	
	<div class="col-md-9">
		{if isset($done)}<div class="alert alert-success">{$lang.TEXTS.SAVED}</div>{/if}

		{if $t == "terms"}
			{if isset($msg)}{$msg}{/if}

			{if isset($smarty.get.action) && $smarty.get.action == "add"}
			<form role="form" method="POST">
					<label>{$lang.TEXTS.TERMS}</label>
				{foreach from=$admin_languages item=lang_name key=lang_key}
				<a href="#" class="btn btn-default btn-xs{if $lang_key == $cfg.LANG} active{/if}" show-lang="{$lang_key}">{$lang_name}</a>
				{/foreach}<br />

				{foreach from=$admin_languages item=lang_name key=lang_key}
			        <div is-lang="{$lang_key}"{if $lang_key != $cfg.LANG} style="display: none;"{/if}><br />
			          <textarea class="form-control" style="width:100%;height:450px;resize:none;" name="terms_{$lang_key}">{if isset($smarty.post.{"terms_"|cat:$lang_key})}{$smarty.post.{"terms_"|cat:$lang_key}|htmlentities}{/if}</textarea>
			        </div>
			    {/foreach}
			<input type="hidden" name="id" value="new">
			<br /><button type="submit" name="add_terms" class="btn btn-primary btn-block">{$lang.TEXTS.ADD}</button></form><hr/>
			{/if}

			{if isset($smarty.get.edit) && isset($texts)}
			<form role="form" method="POST">
				<label>{$lang.TEXTS.TERMS}</label>
				{foreach from=$admin_languages item=lang_name key=lang_key}
				<a href="#" class="btn btn-default btn-xs{if $lang_key == $cfg.LANG} active{/if}" show-lang="{$lang_key}">{$lang_name}</a>
				{/foreach}<br />

				{foreach from=$admin_languages item=lang_name key=lang_key}
					<div is-lang="{$lang_key}"{if $lang_key != $cfg.LANG} style="display: none;"{/if}><br />			
					  <textarea class="form-control" style="width:100%;height:450px;resize:none;" name="terms_{$lang_key}">{if isset($smarty.post.{"terms_"|cat:$lang_key})}{$smarty.post.{"terms_"|cat:$lang_key}}{elseif isset($texts.$lang_key)}{$texts.$lang_key|htmlentities}{/if}</textarea>
			    </div>
			    {/foreach}
			<input type="hidden" name="id" value="{$smarty.get.edit}">
			<br /><button type="submit" name="save_terms" class="btn btn-primary btn-block">{$lang.GENERAL.SAVE}</button></center></form><hr/>
			{/if}

			<div class="checkbox" style="margin-top: 0;">
				<label>
					<input type="checkbox" id="terms_date"{if $cfg.TERMS_DATE} checked=""{/if}>
					<i class="fa fa-spinner fa-spin" id="terms_date_wait" style="display: none;"></i>
					{$lang.TEXTS.TERMS_DATE}
				</label>
			</div>

			<div class="checkbox">
				<label>
					<input type="checkbox" id="terms_history"{if $cfg.TERMS_HISTORY} checked=""{/if}>
					<i class="fa fa-spinner fa-spin" id="terms_history_wait" style="display: none;"></i>
					{$lang.TEXTS.TERMS_HISTORY}
				</label>
			</div>
			
			<script>
			$("#terms_date").change(function() {
				$(this).hide();
				$("#terms_date_wait").show();

				var state = $(this).is(":checked") ? 1 : 0;

				$.get("?p=texts&terms_date=" + state, function(r) {
					if (r == "ok") {
						$("#terms_date").show();
						$("#terms_date_wait").hide();
					}
				});
			});

			$("#terms_history").change(function() {
				$(this).hide();
				$("#terms_history_wait").show();

				var state = $(this).is(":checked") ? 1 : 0;

				$.get("?p=texts&terms_history=" + state, function(r) {
					if (r == "ok") {
						$("#terms_history").show();
						$("#terms_history_wait").hide();
					}
				});
			});
			</script>

			<div class="table-responsive"><table class="table table-bordered table-striped">
				<tr>
					<th>{$lang.TEXTS.DATE}</th>
					<th>{$lang.TEXTS.TEXT}</th>
					<th width="61px"></th>
				</tr>
				
				{foreach from=$terms key=id item=term}
				<tr>
					<td>{dfo d=$term.time}</td>
					<td>{$term.excerpt}</td>
					<td width="61px"><a href="?p=texts&t=terms&edit={$id}"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;&nbsp;<a href="?p=texts&t=terms&delete={$id}"><i class="fa fa-times fa-lg"></i></a></td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="6"><center>{$lang.TEXTS.NO_TERMS}</center></td>
				</tr>
				{/foreach}
			</table></div>
		{else if $t == "license_texts"}
			<form method="POST">
				<div class="row">
				<div class="col-md-6">
					<h4 style="display: inline;">{$lang.TEXTS.SINGLE}</h4>
					{foreach from=$admin_languages item=lang_name key=lang_key}
					<a href="#" class="btn btn-default btn-xs{if $lang_key == $cfg.LANG} active{/if}" show-lang="{$lang_key}">{$lang_name}</a>
					{/foreach}<br />

		            {foreach from=$single key=language item=text}
		            {if isset($admin_languages.$language)}
								<div is-lang="{$language}"{if $language != $cfg.LANG} style="display: none;"{/if}><br />
		            <input type="text" name="ename_{$language}" class="form-control" placeholder="{$lang.TEXTS.NAME_SINGLE}" value="{if isset($smarty.post.{"ename_"|cat:$language})}{$smarty.post.{"ename_"|cat:$language}}{else}{$text.name}{/if}"><br />
			          <textarea class="form-control" style="width:100%;height:350px;resize:none;" name="e_{$language}">{if isset($smarty.post.{"e_"|cat:$language})}{$smarty.post.{"e_"|cat:$language}|htmlentities}{else}{$text.text|htmlentities}{/if}</textarea>
			    		</div>
			        {else}
			        	<input type="hidden" name="ename_{$language}" value="{$text.name}" />
			        	<input type="hidden" name="e_{$language}" value="{$text.text}" />
			        {/if}
		            {/foreach}
				</div>
					
				<div class="col-md-6">
					<h4 style="display: inline;">{$lang.TEXTS.RESELLER}</h4>
					{foreach from=$admin_languages item=lang_name key=lang_key}
					<a href="#" class="btn btn-default btn-xs{if $lang_key == $cfg.LANG} active{/if}" show-lang2="{$lang_key}">{$lang_name}</a>
					{/foreach}<br />
		            {foreach from=$reseller key=language item=text}
		            {if isset($admin_languages.$language)}
								<div is-lang2="{$language}"{if $language != $cfg.LANG} style="display: none;"{/if}><br />
		                  <input type="text" name="rname_{$language}" class="form-control" placeholder="{$lang.TEXTS.NAME_RESELLER}" value="{if isset($smarty.post.{"rname_"|cat:$language})}{$smarty.post.{"rname_"|cat:$language}}{else}{$text.name}{/if}"><br />
			                <textarea class="form-control" style="width:100%;height:350px;resize:none;" name="r_{$language}">{if isset($smarty.post.{"r_"|cat:$language})}{$smarty.post.{"r_"|cat:$language}|htmlentities}{else}{$text.text|htmlentities}{/if}</textarea>
			              </div>
			        {else}
			        	<input type="hidden" name="rname_{$language}" value="{$text.name}" />
			        	<input type="hidden" name="r_{$language}" value="{$text.text}" />
			        {/if}
		            {/foreach}
				</div>		
				</div>		

				<center>
                    <input type="submit" style="margin-top: 15px;" name="save" value="{$lang.GENERAL.SAVE}"
                           class="btn btn-primary btn-block">
				</center>
			</form>
        {else if $t == "imprint" || $t == "privacy_policy" || $t == "withdrawal_rules"}
			<form method="POST"> 
				{foreach from=$imprint key=lang_key item=info}
					<h3 style="margin-top:0">
						{$info.lang_title}
					</h3>

					<div class="form-group">
						<textarea style="width:100%; height:300px; resize:none;" name="{$info.post_key}" class="form-control">{if isset($smarty.post.{$info.post_key})}{$smarty.post.{$info.post_key}|htmlentities}{else}{$info.imprint|htmlentities}{/if}</textarea>
					</div>
					
					{if $smarty.foreach.foo.last}<hr />{/if}
				{/foreach}

				{if $t == "privacy_policy" || $t == "withdrawal_rules"}
				<div class="checkbox">
					<label>
						<input type="checkbox" name="reconfirm" value="1">
						{$lang.TEXTS.AGAIN}
					</label>
				</div>
				{/if}
				
				<center>
                    <input type="submit" name="save" value="{$lang.GENERAL.SAVE}" class="btn btn-primary btn-block">
					<br /><br />
				</center>
			</form>	
		{/if}
	</div>
</div>