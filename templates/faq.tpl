<div id="content">
	<div class="container">
    <h1>{$lang.FAQ.TITLE}</h1><hr>

    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
      {foreach from=$faq item=f key=i}<div class="panel panel-default">
        <div class="panel-heading" role="tab" id="heading{$i}">
          <h4 class="panel-title">
            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse{$i}" aria-expanded="{if $i == 0}true{else}false{/if}" aria-controls="collapse{$i}">
              {$f.q}
            </a>
          </h4>
        </div>
        <div id="collapse{$i}" class="panel-collapse collapse{if $i == 0} in{/if}" role="tabpanel" aria-labelledby="heading{$i}">
          <div class="panel-body">
            {$f.a}
          </div>
        </div>
      </div>{foreachelse}<div class="alert alert-info">{$lang.FAQ.NOTHING}</div>{/foreach}
    </div>
  </div>
</div>