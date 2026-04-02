      <div class="container">
        <h1>{$lang.MAINTENANCE.TITLE}</h1><hr />
        <p style="text-align: justify;">{if isset($reason)}{$reason}{else}{$lang.MAINTENANCE.TEXT|replace:"%n":$cfg.PAGENAME}{/if}<br /><br/><a href="{$cfg.PAGEURL}maintenance" class="btn btn-primary">{$lang.MAINTENANCE.TRY_AGAIN}</a></p>
      </div>