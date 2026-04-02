<!doctype html>
<!--[if IE 8]> <html lang="{$lang.ISOCODE}" class="ie8"> <![endif]-->  
<!--[if IE 9]> <html lang="{$lang.ISOCODE}" class="ie9"> <![endif]-->  
<!--[if !IE]><!--> <html lang="{$lang.ISOCODE}"> <!--<![endif]-->  
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="author" content="{$cfg.PAGENAME}">
    <meta name="robots" content="index, follow">

    {foreach from=$cfg.SEO|unserialize item=info key=iso}
    {if $info.desc|trim != ""}<meta name="description" content="{$info.desc}" lang="{$iso}">{/if}
    {if $info.keywords|trim != ""}<meta name="keywords" content="{$info.keywords}" lang="{$iso}">{/if}
    {/foreach}
    
    <title>{$title} :: {$cfg.PAGENAME}</title>

    <link rel="stylesheet" href="{cdnurl}themes/bsfour/css/bootstrap.min.css">
    <link rel="stylesheet" href="{cdnurl}themes/bsfour/css/custom.css">
    <link rel="stylesheet" href="{cdnurl}themes/standard/css/sweetalert.css">
    <link rel="stylesheet" href="{cdnurl}themes/standard/css/ekko-lightbox.min.css">
    <link rel="stylesheet" href="{cdnurl}themes/standard/fonts/awesome/css/font-awesome.min.css">
    <link rel="shortcut icon" href="{cdnurl}themes/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="{cdnurl}themes/apple-touch-icon.png" />

    {$meta_tags}

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <script src="{cdnurl}themes/standard/js/jquery-1.11.1.min.js"></script>
    {$paymentJS}
  </head>
  <body{if !isset($smarty.cookies.hide_top_msg) && $cfg.TOP_ALERT_TYPE != "" && $cfg.TOP_ALERT_TYPE != "none" && $cfg.TOP_ALERT_MSG != ""} class="top-alert-content"{/if}>
    {if !isset($smarty.cookies.hide_top_msg) && $cfg.TOP_ALERT_TYPE != "" && $cfg.TOP_ALERT_TYPE != "none" && $cfg.TOP_ALERT_MSG != ""}<div class="alert alert-{$cfg.TOP_ALERT_TYPE} top-alert">{if !$maintenance}<button type="button" class="close" id="top-alert-dismiss" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>{/if}{$top_alert}</div>{/if}
    {if !$hideHeader}
    <nav class="navbar navbar-expand-lg navbar-light bg-light"><div class="container">
        <a class="navbar-brand" href="{$cfg.PAGEURL}">{$cfg.PAGENAME}</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{$lang.NAV.NAVIGATION}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
            {$menu_code_bs4}
            </ul>

            <ul class="navbar-nav navbar-right">
                <li class="nav-item{if basename($tpl) == "cart.tpl"} active{/if}"><a href="{$cfg.PAGEURL}cart" class="nav-link"><i class="fa fa-shopping-cart"></i>&nbsp;&nbsp;{$cart_count}</a></li>
                {if !$logged_in}
                <li class="nav-item{if basename($tpl) == "login.tpl"} active{/if}"><a href="{$cfg.PAGEURL}login" class="nav-link">{$lang.NAV.LOGIN}</a></li>
                {if $cfg.ALLOW_REG}<li class="nav-item{if basename($tpl) == "register.tpl"} active{/if}"><a href="{$cfg.PAGEURL}register" class="nav-link">{$lang.NAV.REGISTER}</a></li>{/if}
                {else}
                {if $tfa_open}
                <li class="nav-item"><a href="{$cfg.PAGEURL}logout" class="nav-link">{$lang.NAV.LOGOUT}</a></li>
                {else}
                <li class="nav-item{if basename($tpl) == "dashboard.tpl"} active{/if}"><a href="{$cfg.PAGEURL}dashboard" class="nav-link">{$lang.NAV.DASHBOARD}</a></li>
                {/if}
                {/if}
                {if $langs|@count > 1}
                <li class="nav-item"><a href="#" data-toggle="modal" data-target="#languageModal" class="nav-link"><img src="{$raw_cfg.PAGEURL}languages/icons/{$cfg.LANG}.png" title="{$lang.NAME}" alt="{$lang.NAME}" height="20" /></a></li>
                {/if}
            </ul>
        </div>
    </div></nav>
    {/if}

    {if isset($global_alert)}<br /><div class="container">
        <div class="alert alert-success">{$global_alert}</div>
    {assign "alert" "1"}
    </div>{/if}
    
    {if isset($global_error)}{if !isset($alert)}<br />{/if}<div class="container">
        <div class="alert alert-danger">{$global_error}</div>
    {assign "alert" "1"}
    </div>{/if}

    {if isset($global_info)}{if !isset($alert)}<br />{/if}<div class="container">
        <div class="alert alert-info">{$global_info}</div>
    {assign "alert" "1"}
    </div>{/if}

  {if isset($tos) && $tos == 1}{if !isset($alert)}<br />{/if}<div class="container">
    <div class="alert alert-warning">{$lang.GENERAL.TERMS_NOT_ACCEPTED}</div>
    {assign "alert" "1"}
  </div>{/if}

  {if isset($wr) && $wr == 1}{if !isset($alert)}<br/>{/if}
      <div class="container">
      <div class="alert alert-warning">{$lang.GENERAL.WR_NOT_ACCEPTED}</div>
      {assign "alert" "1"}
      </div>{/if}

  {if isset($privacy) && $privacy == 1}{if !isset($alert)}<br/>{/if}
      <div class="container">
      <div class="alert alert-warning">{$lang.GENERAL.PP_NOT_ACCEPTED}</div>
      {assign "alert" "1"}
      </div>{/if}
    
{if isset($locked) && $locked == 1}{if !isset($alert)}<br />{/if}<div class="container">
    <div class="alert alert-warning">{$lang.GENERAL.USER_LOCKED}</div>
  </div>{/if}

{if isset($userNotes) && count($userNotes) > 0}
    {foreach from=$userNotes item=note}
        {if !isset($alert)}<br />{/if}<div class="container">
          {assign var=text value={$note.text|nl2br}}
          {if count("<br />"|explode:$text) == 1}
          <div class="alert alert-{$note.display|replace:"error":"danger"}"><b>{$note.title}</b><br />{$text}</div>
          {else}
          {assign var=exploded value="<br />"|explode:$text}
          <div class="alert alert-{$note.display|replace:"error":"danger"}"><b>{$note.title}</b><br /><div>{$exploded.0}<br /></div><div id="n{$note.ID}_expanded" style="display:none;">{$exploded.0 = null}{array_shift($exploded)}{"<br />"|implode:$exploded}<br /></div><small><a id="n{$note.ID}_unexpanded" href="#" onclick="return expand_note({$note.ID})">{$lang.GENERAL.SHOW_MORE}</a><a href="#" id="n{$note.ID}_expanded_link" style="display:none;" onclick="return unexpand_note({$note.ID})">{$lang.GENERAL.SHOW_LESS}</a></small></div>
          {/if}
        </div>
        {assign "alert" "1"}
    {/foreach}
{/if}

    <noscript><div class="container" style="margin-top:18px;"><div class="alert alert-warning">{$lang.GENERAL.NO_JS}</div></div></noscript>

    {if basename($tpl) != "index.tpl"}<br />{/if}{if !empty($tpl)}{include file="$tpl"}{/if}<br />

    {if !$hideFooter}
    <div class="container">
        <hr style="margin-top: 0;" /><footer>
            <p style="text-align: center;">&copy; Copyright {$smarty.now|date_format:"%Y"} {$cfg.PAGENAME} &nbsp;<span style="color: grey;">&bull;</span>&nbsp; <a
                            href="{$cfg.PAGEURL}newsletter">{$lang.NEWSLETTER.TITLE}</a> &nbsp;<span style="color: grey;">&bull;</span>&nbsp; <a
                            href="{$cfg.PAGEURL}faq">{$lang.FAQ.SHORT}</a> &nbsp;<span style="color: grey;">&bull;</span>&nbsp; <a
                            href="{$cfg.PAGEURL}terms">{$lang.TOS.SHORT}</a> &nbsp;<span style="color: grey;">&bull;</span>&nbsp; <a
                            href="{$cfg.PAGEURL}withdrawal">{$lang.WITHDRAWAL.TITLE}</a> &nbsp;<span style="color: grey;">&bull;</span>&nbsp; <a
                            href="{$cfg.PAGEURL}privacy">{$lang.PRIVACY.TITLE}</a> &nbsp;<span style="color: grey;">&bull;</span>&nbsp; <a
                            href="{$cfg.PAGEURL}imprint">{$lang.IMPRINT.TITLE}</a>{if $branding} &nbsp;<span style="color: grey;">&bull;</span>&nbsp; {$branding}{/if}
            </p>
        </footer>
    </div>
    {elseif $branding}
    <div class="container"><hr style="margin-top: 0;" /><footer><p style="text-align: center;">{$branding}</p></footer></div>
    {/if}

    {if $langs|@count > 1}
  <!-- language modal -->
  <div class="modal fade" id="languageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <form method="POST">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h5 class="modal-title" id="languageModalLabel">{$lang.GENERAL.LANGUAGE}</h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
      <select class="form-control" name="new_language">
        {foreach from=$langs key=slug item=name}
        <option value="{$slug}"{if $name == $lang.NAME} selected="selected"{/if}>{$name}</option>
        {/foreach}
      </select>
      
      {foreach from=$smarty.post key=k item=v}
          {if $k != "new_language"} 
                <input type="hidden" name="{$k|htmlentities}" value="{$v|htmlentities}">
                {/if}
      {/foreach}
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
      <button type="submit" name="change_language" class="btn btn-primary">{$lang.GENERAL.SAVE}</button>
      </div>
    </div>
    </div>
    </form>
    </div>
    <!-- language modal end -->
  {/if}

  <input type="hidden" id="csrf_token" value="{ct}" />

    <script src="{cdnurl}themes/bsfour/js/bootstrap.min.js"></script>
    <script src="{cdnurl}themes/standard/js/ajax.js"></script>
    <script src="{cdnurl}themes/standard/js/sweetalert.min.js"></script>
    <script src="{cdnurl}themes/standard/js/ekko-lightbox.min.js"></script>
    <script>$('[data-toggle="tooltip"]').tooltip();</script>
    {if $cfg.HASH_METHOD != "plain" && $cfg.CLIENTSIDE_HASHING == 1}<script src="{cdnurl}lib/crypt/{$cfg.HASH_METHOD|rtrim:"salt"}.js"></script>
    <script>var hash_method = '{$cfg.HASH_METHOD}';</script>
    <script src="{cdnurl}lib/crypt/core.js"></script>{/if}

    {if isset($browser_language)}
    <!-- Language Switcher Modal -->
    <div class="modal" id="language-switcher" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{$browser_language_title}</h5>
                </div>
                <div class="modal-body">
                    {$browser_language_text}<br /><br />
                    <center><a href="?lang={$browser_language}" class="btn btn-primary">{$browser_language_yes}</a>&nbsp;<a href="?lang={$cfg.LANG}" class="btn btn-default">{$browser_language_no|replace:"%l":$lang.NAME}</a></center>
                </div><!--//modal-body-->
            </div><!--//modal-content-->
        </div><!--//modal-dialog-->
    </div><!--//modal-->

    <!-- Call language switcher modal -->
    <script>
        $('#language-switcher').modal({ backdrop: "static", keyboard: false, show: true });
    </script>
    {/if}

    {if $cfg.COOKIE_ACCEPT}
    <!-- Cookie information -->
    <script src="{cdnurl}themes/standard/js/cookieconsent/cookieconsent.min.js"></script>
    <link rel="stylesheet" href="{cdnurl}themes/standard/js/cookieconsent/cookieconsent.min.css">
    {literal}<script>
    window.cookieconsent.initialise({
        palette:{
            popup: {background: "#eee"},
            button: {background: "#007bff"},
        },
        content: {
            message: '{/literal}{$lang.GENERAL.COOKIE_CONSENT}{literal}',
            dismiss: '{/literal}{$lang.GENERAL.COOKIE_OK}{literal}',
            link: '{/literal}{$lang.PRIVACY.TITLE}{literal}',
            href: '{/literal}{$cfg.PAGEURL}privacy{literal}',
            target: '_blank',
        },
    });
    </script>{/literal}
    {/if}

    {if $cfg.TRACKING|trim != "" && !$is_admin}
    {$cfg.TRACKING}
    {/if}{if $cfg.PIWIK_ECOMMERCE && $pcomm|trim != ""}<script>{$pcomm}</script>{/if}

    {if $additionalJS}<script>{$additionalJS}</script>{/if}
    {$frontendFooter}
  </body>
</html>