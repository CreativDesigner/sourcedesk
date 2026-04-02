<div id="content">
	<div class="container">
			{if $new == 1}<h1>{$lang.BUGTRACKER.TITLE} <small><a href="{$cfg.PAGEURL}bugtracker">{$lang.BUGTRACKER.BACK_TO_LIST}</a></small></h1><hr><p>{$lang.BUGTRACKER.INTRO|replace:"%n":$pname}</p>
			{if isset($error)}<div class="alert alert-danger"><b>{$lang.GENERAL.ERROR}</b> {$error}</div>{/if}
			<form role="form" method="POST" enctype="multipart/form-data">
  <div class="form-group">
    <label>{$lang.GENERAL.PRODUCT}</label>
    {if $products|count <= 0}<br /><i>{$lang.BUGTRACKER.NOTHING}</i>{else}
    <select name="pid" class="form-control">
      <option value="0">{$lang.GENERAL.PLEASE_CHOOSE}</option>
      {foreach from=$products item=info}
          <option value="{$info.product}"{if $smarty.post.pid == $info.product} selected="selected"{/if}>{$info.name|htmlentities}</option>
      {/foreach}
    </select>
    {/if}
  </div>
  <div class="form-group">
    <label>{$lang.GENERAL.DESCRIPTION}</label>
    <textarea class="form-control" name="description" style="height:130px;">{if isset($smarty.post.description)}{$smarty.post.description|htmlentities}{/if}</textarea>
  </div>
  <div class="form-group">
    <label>{$lang.BUGTRACKER.STEPS_REPRODUCE}</label>
    <textarea class="form-control" name="reproduce" style="height:130px;">{if isset($smarty.post.reproduce)}{$smarty.post.reproduce|htmlentities}{/if}</textarea>
  </div>
  <div class="form-group">
    <label>{$lang.BUGTRACKER.FILES}</label>
    <input type="hidden" name="MAX_FILE_SIZE" value="3145728" />
    <input type="file" name="files[]" multiple="multiple">
    <p class="help-block">{$lang.BUGTRACKER.FILES_HINT}</p>
  </div>
  <input type="hidden" name="aid" value="{$aid}">
  <button name="submit" type="submit" class="btn btn-primary btn-block">{$lang.BUGTRACKER.SEND}</button>
</form>{else}
<h1>{$lang.BUGTRACKER.FILED}{if !$new} <small><a href="{$cfg.PAGEURL}bugtracker/report">{$lang.BUGTRACKER.REPORT_NEW}</a></small>{/if}</h1><hr>
{if $success}<div class="alert alert-success">{$lang.BUGTRACKER.SENT}</div>{/if}
<div class="table table-responsive">
  <table class="table table-bordered">
    <tr>
      <th width="10%">#</th>
      <th>{$lang.GENERAL.DATE}</th>
      <th>{$lang.GENERAL.PRODUCT}</th>
      <th>{$lang.GENERAL.STATUS}</th>
    </tr>

    {foreach from=$bugs item=b}
    <tr>
      <td width="10%"><a href="{$b.url}">{$b.ticket}</a></td>
      <td>{$b.date}</td>
      <td>{$b.product|htmlentities}</td>
      <td>{$b.done}</td>
    </tr>
    {foreachelse}
    <tr>
      <td colspan="4">
        <center>{$lang.BUGTRACKER.NOTHING}</center>
      </td>
    </tr>
    {/foreach}
  </table>
	</div>{/if}
</div></div><br />