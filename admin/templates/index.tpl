<style>
@media (min-width: 1440px) {
  .col-lg-6 {
    width: 50%;
    float: left;
  }
}

@media (max-width: 1439px) {
  .col-lg-6 {
    width: 100%;
  }
}
</style>

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.DASHBOARD.TITLE} <span style="font-size: 16pt; float: right;"><a href="#" data-toggle="modal" data-target="#manageWidgets"><i class="fa fa-cog"></i></a></span></h1>
		<noscript><div class="alert alert-warning">{$lang.DASHBOARD.JS_WARNING}</div></noscript>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="alert alert-success">{$lang.DASHBOARD.HELLO}, {$admin_info.name|strip_tags}!</div>

{assign "shown" "0"}
{foreach from=$myWidgets item=k key = i}
  {assign w $widgets.$k}
	{if $w.1 && in_array($k, $myWidgets)}
	{if !$twocols || $shown % 2 == 0}
  {assign "open" "1"}
  <div class="row">
  {/if}
		<div class="col-lg-{if $twocols}6{else}12{/if}">
			<div class="panel panel-default">
				<div class="panel-heading">
					<a href="#" class="widget_link" data-widget="{$k}" style="color: #333; text-decoration: none;"><i class="pull-right fa fa-caret-{if in_array($k, $hiddenWidgets)}up{else}down{/if} fa-lg"></i>{$w.0}</a>
				</div>
				<!-- /.panel-heading -->
				<div class="panel-body" id="widget-body-{$k}"{if in_array($k, $hiddenWidgets)} style="display: none;"{/if}>
          <!-- Widget content start -->
					{$w.1}
          <!-- Widget content end -->
				</div>
				<!-- /.panel-body -->
			</div>
			<!-- /.panel -->
		</div>
  {if !$twocols || $shown % 2 == 1}
  {assign "open" "0"}
  </div>
	<!-- /.row -->
  {/if}
  {assign var="shown" value=($shown+1)}
	{/if}
{/foreach}

{if $open}</div>{/if}

<div class="modal fade" id="manageWidgets" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{$lang.DASHBOARD.MANAGE_WIDGETS}</h4>
      </div>
      <form method="POST">
      <div class="modal-body">
        <div class="checklist">
          {foreach from=$myWidgets item=k}
          {if $widgets.$k.1 !== false && array_key_exists($k, $widgets)}
          {assign w $widgets.$k}
          <div class="checkbox">
          	<label>
          		<input type="checkbox" name="widgets[]" value="{$k}"{if in_array($k, $myWidgets)} checked=""{/if}>
          		{if isset($w.2)}{$w.2|strip_tags} - {/if}{$w.0|strip_tags}
          	</label>
            <span style="float: right">
              <button type="button" class="btn btn-default btn-xs move-up"><i class="fa fa-arrow-circle-up"></i></button>
              <button type="button" class="btn btn-default btn-xs move-down"><i class="fa fa-arrow-circle-down"></i></button>
            </span>
          </div>
          {/if}
          {/foreach}
          {foreach from=$widgets item=w key=k}
          {if !in_array($k, $myWidgets) && $w.1 !== false}
          <div class="checkbox">
            <label>
              <input type="checkbox" name="widgets[]" value="{$k}"{if in_array($k, $myWidgets)} checked=""{/if}>
              {if isset($w.2)}{$w.2|strip_tags} - {/if}{$w.0|strip_tags}
            </label>
            <span style="float: right">
              <button type="button" class="btn btn-default btn-xs move-up"><i class="fa fa-arrow-circle-up"></i></button>
              <button type="button" class="btn btn-default btn-xs move-down"><i class="fa fa-arrow-circle-down"></i></button>
            </span>
          </div>
          {/if}
          {/foreach}
          <div class="checkbox" style="margin-top: 20px; margin-bottom: 0;">
            <label>
              <input type="checkbox" name="twocols" value="1"{if $twocols} checked=""{/if}>
              {$lang.GENERAL.TWOCOLS}
            </label>
          </div>
        </div>
        <script>
        $(".move-up").click(function(){
          $(this).parents('.checkbox').insertBefore($(this).parents('.checkbox').prev());
        });

        $(".move-down").click(function(){
          $(this).parents('.checkbox').insertAfter($(this).parents('.checkbox').next());
        });
        </script>
      </div>
      <div class="modal-footer">
      	<input type="hidden" name="action" value="widgets">
        <button type="submit" class="btn btn-primary">{$lang.GENERAL.SAVE}</button>
      </div>
      </form>
    </div>
  </div>
</div>
