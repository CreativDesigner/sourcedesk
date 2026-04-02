  <div class="container">
    <h1>{$lang.ERROR.TITLE}</h1><hr />
    {if isset($error)}{$error}{else}{$lang.ERROR.TEXT|replace:"%m":"<a href='mailto:`$cfg.PAGEMAIL`?subject=Fehler auf `$cfg.PAGENAME`'>`$cfg.PAGEMAIL`</a>"}{/if}<br /><br />{if ($is_admin || php_sapi_name() == "cli") && !empty($debug)}<i>{$debug|htmlentities}</i><br /><br />{/if}
    
    {if !isset($show_back) || $show_back == true}
    
    <script type="text/javascript">
        document.write('<a href="javascript:history.back();window.location = \'{if isset($smarty.server.HTTP_REFERER) || $smarty.server.HTTP_REFERER != ""}{$smarty.server.HTTP_REFERER}{else}{$cfg.PAGEURL}{/if}\';" class="btn btn-primary" role="button">&laquo; {$lang.ERROR.BACK}</a>');  
    </script>
    
    <noscript>
    <a href="{if isset($smarty.server.HTTP_REFERER) && $smarty.server.HTTP_REFERER != ""}{$smarty.server.HTTP_REFERER}{else}{$cfg.PAGEURL}{/if}" class="btn btn-primary" role="button">&laquo; {$lang.ERROR.BACK}</a>
    </noscript>
    
    {/if}
  </div><br />