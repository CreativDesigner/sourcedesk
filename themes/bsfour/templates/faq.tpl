<div id="content">
	<div class="container">
    <h1>{$lang.FAQ.TITLE}</h1><hr>

    <div class="accordion" id="accordion" role="tablist">
      {foreach from=$faq item=f key=i}<div class="card">
        <div class="card-header" role="tab" id="heading{$i}">
          <h2 class="mb-0">
            <button class="btn btn-link{if $i != 0} collapsed{/if}" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse{$i}" aria-expanded="{if $i == 0}true{else}false{/if}" aria-controls="collapse{$i}">
              {$f.q}
            </button>
          </h2>
        </div>
        <div id="collapse{$i}" class="collapse{if $i == 0} show{/if}" role="tabpanel" aria-labelledby="heading{$i}">
          <div class="card-body">
            {$f.a}
          </div>
        </div>
      </div>{foreachelse}<div class="alert alert-info">{$lang.FAQ.NOTHING}</div>{/foreach}
    </div>
  </div>
</div>