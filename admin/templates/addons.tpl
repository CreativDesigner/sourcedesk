<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.ADDON_MANAGEMENT.TITLE} <span class="pull-right"><a href="#" data-toggle="modal" data-target="#__registered"><i class="fa fa-info-circle"></i></a></span></h1>

		<div class="modal fade" tabindex="-1" role="dialog" id="__registered">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-body">
			  	<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th>Hook point</th>
							<th>Addon</th>
							<th>Method</th>
						</tr>

						{foreach from=$registeredHooks item=hook}
						<tr>
							<td>{$hook.point}</td>
							<td>{$hook.addon}</td>
							<td>{$hook.method}</td>
						</tr>
						{foreachelse}
						<tr>
							<td colspan="3"><center>No hooks</center></td>
						</tr>
						{/foreach}
					</table>
				</div>

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th>Admin page</th>
							<th>Addon</th>
							<th>Method</th>
						</tr>

						{foreach from=$adminPages item=page}
						<tr>
							<td>{$page.page}</td>
							<td>{$page.addon}</td>
							<td>{$page.method}</td>
						</tr>
						{foreachelse}
						<tr>
							<td colspan="3"><center>No admin pages</center></td>
						</tr>
						{/foreach}
					</table>
				</div>

				<div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th>Admin menu</th>
							<th>Addon</th>
							<th>URL</th>
						</tr>

						{foreach from=$adminMenu item=menu}
						<tr>
							<td>{$menu.name}</td>
							<td>{$menu.addon}</td>
							<td>{$menu.url}</td>
						</tr>
						{foreachelse}
						<tr>
							<td colspan="3"><center>No menu entries</center></td>
						</tr>
						{/foreach}
					</table>
				</div>

				<div class="table-responsive">
					<table class="table table-bordered table-striped" style="margin-bottom: 0;">
						<tr>
							<th>Client page</th>
							<th>Addon</th>
							<th>Method</th>
						</tr>

						{foreach from=$clientPages item=page}
						<tr>
							<td>{$page.page}</td>
							<td>{$page.addon}</td>
							<td>{$page.method}</td>
						</tr>
						{foreachelse}
						<tr>
							<td colspan="3"><center>No client pages</center></td>
						</tr>
						{/foreach}
					</table>
				</div>
			  </div>
			</div>
		  </div>
		</div>

		{if isset($msg)}{$msg}{/if}

		<p style="text-align: justify;">{$lang.ADDON_MANAGEMENT.INTRO}</p>

		<form method="POST">
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th>{$lang.ADDONS.NAME}</th>
					<th>{$lang.ADDONS.VERS}</th>
					<th>{$lang.ADDONS.MANU}</th>
					<th></th>
				</tr>

				{foreach from=$addons item=obj key=name}
				<tr>
					<td><input type="checkbox" class="checkbox" name="addon[]" value="{$name}" onchange="javascript:toggle();" /></td>
					<td>{$obj->getInfo('name')}</td>
					<td>{$obj->getInfo('version')}</td>
					<td>{if is_string($obj->getInfo('url'))}<a href="{$obj->getInfo('url')}" target="_blank">{/if}{$obj->getInfo('company')}{if is_string($obj->getInfo('url'))}</a>{/if}</td>
					<td>{if $obj->isActive()}<a href="#" onclick="return false;" data-toggle="modal" data-target="#{$name}" class="btn btn-primary btn-xs">{$lang.ADDONS.SET}</a> <a href="./?p=addons&deactivate={$name}" class="btn btn-warning btn-xs">{$lang.ADDONS.DEA}</a>{else}<a href="./?p=addons&activate={$name}" class="btn btn-success btn-xs">{$lang.ADDONS.ACT}</a>{if method_exists($obj, 'delete')} <a href="./?p=addons&delete={$name}" class="btn btn-danger btn-xs">{$lang.ADDONS.DEL}</a>{/if}{/if}</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="5"><center>{$lang.ADDONS.NOT}</center></td>
				</tr>
				{/foreach}
			</table>
		</div>
		Selektierte:
			<input type="submit" name="activate_selected" value="{$lang.ADDONS.ACT}" class="btn btn-success" />
			<input type="submit" name="deactivate_selected" value="{$lang.ADDONS.DEA}" class="btn btn-warning" />
			<input type="submit" name="delete_selected" value="{$lang.ADDONS.DEL}" class="btn btn-danger" />
		</form>
		{foreach from=$addons item=obj key=name}
		<div class="modal fade" tabindex="-1" role="dialog" id="{$name}">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		      	<button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
		      	<h4 class="modal-title">{$obj->getInfo('name')} {$lang.ADDONS.CON}</h4>
		      </div>
		      <form method="POST">
		      <div class="modal-body">
		      	{foreach from=$obj->getSettings() item=info key=oname}
						{if $info.type != "checkbox"}
	      		<div class="form-group">
		      		<label>{$info.label}</label>
		      		<input name="{$name}[{$oname}]" value="{$obj->getOption($oname)|htmlentities}"{if isset($info.placeholder)} placeholder="{$info.placeholder}"{/if} class="form-control" type="{$info.type}"{if (isset($info.readonly) && $info.readonly)} disabled=""{/if} />
		      		{if !empty($info.help)}<p class="help-block">{$info.help}</p>{/if}
		      	</div>
						{else}
						<input type="hidden" name="{$name}[{$oname}]" value="0">
						<div class="checkbox">
							<label>
								<input type="checkbox" name="{$name}[{$oname}]" value="1"{if $obj->getOption($oname) == "1"} checked="checked"{/if}>
								{$info.label}
							</label>
						</div>
						{/if}
		      	{/foreach}
		      	{if ($obj->adminPages()|is_array && count($obj->adminPages()) > 0) || ($obj->getWidgets()|is_array && count($obj->getWidgets()) > 0)}{assign "access" $obj->getOption("access")|unserialize}<div class="form-group">
					<label>{$lang.CUSTOMERS.ACCESSRIGHTS}</label><input type="hidden" name="{$name}[access][]" value="0">
					<div class="row">
						{foreach from=$admins item=aname key=aid}
			      		<div class="col-md-4">
				      		<div class="checkbox" style="margin-top: 0;">
							  <label style="font-weight: normal;">
							    <input type="checkbox" name="{$name}[access][]" value="{$aid}"{if is_array($access) && $aid|in_array:$access} checked="checked"{/if}>
							    {$aname}
							  </label>
							</div>
						</div>
						{/foreach}
					</div>
					<p class="help-block" style="margin-top: 0;">{$lang.ADDONS.ARH}</p>
				</div>{/if}
		      </div>
		      <div class="modal-footer">
		      	<input type="button" value="{$lang.GENERAL.CLOSE}" class="btn btn-default" data-dismiss="modal" />
		      	<input type="submit" name="save" value="{$lang.ADDONS.SAVE}" class="btn btn-primary" />
		      </div>
		      </form>
		    </div>
		  </div>
		</div>
		{/foreach}
	</div>
</div>
