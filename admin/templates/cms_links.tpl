<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.CMS_LINKS.TITLE}{if $tab != "create"}<a class="pull-right" href="./?p=cms_links&tab=create"><i class="fa fa-plus-circle"></i></a>{/if}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="row">
	<div class="col-md-3">
		<div class="list-group">
			<a class="list-group-item{if $tab == "active"} active{/if}" href="./?p=cms_links">{$lang.CMS_LINKS.ACTIVE}</a>
			<a class="list-group-item{if $tab == "inactive"} active{/if}" href="./?p=cms_links&tab=inactive">{$lang.CMS_LINKS.INACTIVE}</a>
		</div>
	</div>

	<div class="col-md-9">

	{if isset($success)}
	<div class="alert alert-success">{$success}</div>
	{elseif isset($error)}
	<div class="alert alert-danger">{$error}</div>
	{/if}
	
	{if $tab == "active" || $tab == "inactive"}

	{$th}
	
	<form method="POST"><div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th width="10px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
				<th>{$table_order.0}</th>
				<th>{$lang.CMS_LINKS.LINK_TARGET}</th>
				<th>{$table_order.1}</th>
			</tr>

			{foreach from=$links item=link}
			<tr>
				<td><input type="checkbox" class="checkbox" onchange="javascript:toggle();" name="links[]" value="{$link.slug}" /></td>
				<td>{$link.slug}{if $tab == "active"} <a href="{$cfg.PAGEURL}link/{$link.slug}" target="_blank" class="btn btn-default btn-xs">{$lang.CMS_LINKS.CALL}</a>{/if}</td>
				<td><a href="{$link.target}" target="_blank" id="link_{$link.slug}">{$link.target}</a><input type="text" class="form-control input-sm" id="target_{$link.slug}" onkeydown="if (event.keyCode == 13) { save_link('{$link.slug}'); return false; }" value="{$link.target}" style="display: none; max-width: 200px;" /> <i id="btn_{$link.slug}" class="fa fa-pencil" onclick="edit_link('{$link.slug}');"></i></td>
				<td>{$link.calls}</td>
			</tr>
			{foreachelse}
			<tr>
				<td colspan="5"><center>{if $tab != "inactive"}{$lang.CMS_LINKS.NOTHING}{else}{$lang.CMS_LINKS.NOTHING_INACTIVE}{/if}</center></td>
			</tr>
			{/foreach}
		</table></div>

		{$lang.GENERAL.SELECTED}: {if $tab != "inactive"}<input type="submit" name="deactivate" value="{$lang.CMS_LINKS.DEACTIVATE}" class="btn btn-warning" />{else}<input type="submit" name="activate" value="{$lang.CMS_LINKS.ACTIVATE}" class="btn btn-success" />{/if} <input type="submit" name="delete" value="{$lang.CMS_LINKS.DELETE}" class="btn btn-danger" />
	</form>

	{$tf}

	<script type="text/javascript">
		function edit_link(slug) {
			$("#btn_" + slug).hide();
			$("#link_" + slug).hide();
			$("#target_" + slug).show();
		}

		function save_link(slug) {
			$.post("./?p=ajax", {
				action: "save_link",
				slug: slug,
				target: $("#target_" + slug).val(),
				csrf_token: "{ct}",
			}, function() {
				$("#btn_" + slug).show();
				$("#target_" + slug).hide();

				$("#link_" + slug).show().html($("#target_" + slug).val()).prop("href", $("#target_" + slug).val());
			});
		}
	</script>

	{else if $tab == "create"}

	<form method="POST">
		<div class="form-group">
	    	<label>{$lang.CMS_LINKS.CNAME}</label>
	   		<input type="text" name="slug" class="form-control" value="{if isset($smarty.post.slug)}{$smarty.post.slug|htmlentities}{/if}">
	   		<p class="help-block">{$lang.CMS_LINKS.CHINT}</p>
	  	</div>
	  
	  	<div class="form-group">
	    	<label>{$lang.CMS_LINKS.CTARGET}</label>
	   		<input type="text" name="target" class="form-control" value="{if isset($smarty.post.target)}{$smarty.post.target|htmlentities}{/if}" placeholder="https://sourceway.de/">
	  	</div>
	  
	  	<div class="form-group">
			<label>{$lang.CMS_LINKS.CSTATUS}</label><br />
			<label class="radio-inline">
				<input type="radio" name="status" value="1"{if isset($smarty.post.status) && $smarty.post.status == "1"} checked="checked"{/if}>
				{$lang.CMS_LINKS.CACTIVE}
			</label>
			<label class="radio-inline">
				<input type="radio" name="status" value="0"{if isset($smarty.post.status) && $smarty.post.status == "0"} checked="checked"{/if}>
				{$lang.CMS_LINKS.CINACTIVE}
			</label>
		</div>
  
  		<input type="hidden" name="action" value="add" />
  		<input type="submit" value="{$lang.CMS_LINKS.CDO}" class="btn btn-primary btn-block" />
  	</form>
	{else}
	<div class="alert alert-danger">{$lang.GENERAL.SUBPAGE_NOT_FOUND}</div>
	{/if}
</div></div>