<!DOCTYPE html>
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

    <!-- CSS files -->
    <link rel="stylesheet" href="{cdnurl}themes/standard/css/style.min.css">
    <link rel="stylesheet" href="{cdnurl}themes/standard/css/search.css">
    <link rel="stylesheet" href="{cdnurl}themes/standard/css/sweetalert.css">
    <link rel="stylesheet" href="{cdnurl}themes/standard/css/ekko-lightbox.min.css">
    <link rel="stylesheet" href="{cdnurl}themes/standard/css/custom.css">
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
  <div class="navbar navbar-default navbar-fixed-top{if !isset($smarty.cookies.hide_top_msg) && $cfg.TOP_ALERT_TYPE != "" && $cfg.TOP_ALERT_TYPE != "none" && $cfg.TOP_ALERT_MSG != ""} top-alert-nav{/if}" role="navigation">
      <div class="container">
        <div class="navbar-header">
          {if !$maintenance}<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">{$lang.NAV.NAVIGATION}</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>{/if}
          <a class="navbar-brand" href="{$cfg.PAGEURL}">{$cfg.PAGENAME}</a>
        </div>
        {if !$maintenance}<div class="navbar-collapse collapse">
        <ul class="nav navbar-nav">
          <li style="padding: 0 10px;" class="visible-xs">
              <form method="POST" action="{$cfg.PAGEURL}search">
                  <input type="text" name="searchword" class="form-control" placeholder="{$lang.GENERAL.ENTER_SEARCHWORD}" style="margin-bottom: 8px;" />
              </form>
          </li>
          {$menu_code}  
        </ul>

        <ul class="nav navbar-nav navbar-right">
          <li class="nav-item{if basename($tpl) == "cart.tpl"} active{/if}"><a href="{$cfg.PAGEURL}cart"><i class="fa fa-shopping-cart"></i>&nbsp;&nbsp;{$cart_count}</a></li>

{if $langs|@count > 1}
          <li class="nav-item hidden-sm hidden-md hidden-lg"><a href="#" data-toggle="modal" data-target="#languageModal">{$lang.GENERAL.LANGUAGE}</a></li>

          <li class="nav-item dropdown hidden-xs"><a class="dropdown-toggle" data-toggle="dropdown"><img src="{$raw_cfg.PAGEURL}languages/icons/{$cfg.LANG}.png" title="{$lang.NAME}" alt="{$lang.NAME}" height="20" /></a>
  <ul class="dropdown-menu">
    {foreach from=$langs key=id item=name}<li{if $cfg.LANG == $id} class="active"{/if}><a href="?lang={$id}"><img src="{$raw_cfg.PAGEURL}languages/icons/{$id}.png" title="{$name}" alt="{$name}" height="10" /> {$name}</a></li>{/foreach}
  </ul>
</li>{/if}{if $logged_in == 0}<li class="nav-item hidden-xs"><a href="{$cfg.PAGEURL}login" id="login-button">{$lang.NAV.LOGIN}</a></li>
<li class="nav-item hidden-sm hidden-md hidden-lg"><a href="{$cfg.PAGEURL}login">{$lang.NAV.LOGIN}</a></li>
          {if $cfg.ALLOW_REG}<li class="nav-item hidden-xs"><a href="{$cfg.PAGEURL}register" id="register-button">{$lang.NAV.REGISTER}</a></li>
<li class="nav-item hidden-sm hidden-md hidden-lg"><a href="{$cfg.PAGEURL}register">{$lang.NAV.REGISTER}</a></li>{/if}{else}
            {if !$tfa_open}
            <li class="dropdown {if basename($tpl) == "profile.tpl" || basename($tpl) == "dashboard.tpl" || basename($tpl) == "affiliate.tpl" || basename($tpl) == "products.tpl" || basename($tpl) == "tickets.tpl" || basename($tpl) == "projects.tpl" || basename($tpl) == "bugtracker.tpl" || basename($tpl) == "mails.tpl" || basename($tpl) == "file.tpl" || basename($tpl) == "help.tpl" || basename($tpl)|strstr:"credit" || basename($tpl) == "invoices.tpl"}active{/if}">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">{$lang.NAV.ACCOUNT}</a>
                <ul class="dropdown-menu" role="menu">
                  <li {if basename($tpl) == "dashboard.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}dashboard">{$lang.NAV.DASHBOARD}</a></li>
                  <li class="divider"></li>
                  <li {if basename($tpl) == "products.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}products">{$lang.NAV.PRODUCTS}</a></li>
                  <li {if basename($tpl) == "projects.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}projects">{$lang.NAV.PROJECTS}</a></li>
                  <li {if basename($tpl) == "invoices.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}invoices">{$lang.NAV.INVOICES}{if $user.open_invoices != 0} <div class="label label-{if $user.open_invoices < 0}success{else}danger{/if}">{infix n={nfo i={$user.open_invoices|abs}}}</div>{/if}</a></li>
                  <li {if basename($tpl) == "credit.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}credit">{$lang.NAV.CREDIT}{if $user.credit != 0} <div class="label label-{if $user.credit > 0}success{else}danger{/if}">{$user.credit_converted}</div>{/if}</a></li>
                  <li {if basename($tpl) == "tickets.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}tickets">{$lang.TICKETS.TITLE}</a></li>
                  <li class="divider"></li>
                  <li {if basename($tpl) == "profile.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}profile">{$lang.NAV.PROFILE}</a></li>
                  <li {if basename($tpl) == "file.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}file">{$lang.NAV.FILES}</a></li>
                  <li {if basename($tpl) == "mails.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}mails">{$lang.NAV.MAILS}</a></li>
                  {if $cfg.AFFILIATE_ACTIVE}<li {if basename($tpl) == "affiliate.tpl"}class="active"{/if}><a href="{$cfg.PAGEURL}affiliate">{$lang.NAV.AFFILIATE}</a></li>{/if}
                  <li class="divider"></li>
                  <li><a href="{$cfg.PAGEURL}logout{if $smarty.get.p != "" && $smarty.get.p != "index"}?redirect_to={$smarty.get.p}{foreach from=$smarty.get item=v key=k}{if $k != "p"}&{$k}={$v|urlencode}{/if}{/foreach}{/if}">{$lang.NAV.LOGOUT}</a></li>
                </ul>
            </li> 
            {else}
            <li><a href="{$cfg.PAGEURL}logout{if $smarty.get.p != "" && $smarty.get.p != "index"}?redirect_to={$smarty.get.p}{foreach from=$smarty.get item=v key=k}{if $k != "p"}&{$k}={$v|urlencode}{/if}{/foreach}{/if}">{$lang.NAV.LOGOUT}</a></li> 
            {/if}
            {/if}

            <li class="nav-item hidden-xs"><a href="#search"><i class="fa fa-search"></i></a></li>
        </ul>
        </div><!--/.navbar-collapse -->{/if}
      </div>
    </div>
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
    
    {if !empty($tpl)}{include file="$tpl"}{/if}<br />
  
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

{if isset($addedToCart)}
    <!-- cart modal -->
    <script>
    $(document).ready(function(){
      $("#cart").modal("show");
    });
    </script>
    <div class="modal modal-blur" id="cart" tabindex="-1" role="dialog" aria-hidden="true">
      <form method="POST">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{$lang.CARTMODAL.TITLE}</h4>
          </div>
          <div class="modal-body">
            <div class="alert alert-success" style="margin-bottom: 10px;">{$lang.CARTMODAL.TEXT}</div>
            <div class="row">
              <div class="col-md-6">
                <a href="{$cfg.PAGEURL}cart" class="btn btn-primary btn-block">{$lang.CARTMODAL.CART}</a>
              </div>
              <div class="col-md-6">
                <input type="button" class="btn btn-default btn-block" value="{$lang.CARTMODAL.CONTINUE}" onclick="$('#cart').modal('hide');">
              </div>
            </div>
          </div>
        </div>
      </div>
      </form>
    </div>
    <!-- cart modal end -->
    {/if}

    <!-- discount modal -->
    <div class="modal modal-blur" id="discount" tabindex="-1" role="dialog" aria-hidden="true">
      <form method="POST">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{$lang.OFFERS.LONG}</h4>
          </div>
          <div class="modal-body">
            {if !$offers}
            <div class="well" style="margin-bottom: 0;">
                <p style="text-align: center;">{$lang.OFFERS.NOTHING}</p>
            </div>
            {else}
            {foreach from=$offers item=offer}
            <div class="well">
                <div class="row">
                    <div class="col-md-8">
                        <h4 style="margin: 0;">{$offer.title}</h4>
                        {$lang.OFFERS.UNTIL} {$offer.end}
                    </div>
                    <div class="col-md-4">
                        <a href="{$offer.url}" class="btn btn-primary btn-block">{$offer.price}</a>
                    </div>
                </div>
            </div>
            {/foreach}
            <small>{$lang.OFFERS.DISCLAIMER}</small>
            {/if}
          </div>
        </div>
      </div>
      </form>
    </div>
    <!-- discount modal end -->

{if $langs|@count > 1}
  <!-- language modal -->
  <div class="modal fade" id="languageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <form method="POST">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title" id="languageModalLabel">{$lang.GENERAL.LANGUAGE}</h4>
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
    <!-- Login Modal -->
    <div class="modal modal-login" id="login-modal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 id="loginModalLabel" class="modal-title text-center">{$lang.GENERAL.LOGIN_AT|replace:"%n":$cfg.PAGENAME}</h4>
                </div>
                <div class="modal-body">
                    {if $ca_disabled}
                        <div class="alert alert-danger">{$lang.GENERAL.BLOCKED}</div>
                    {else}
                    <div class="login-form-container">
                        <form method="POST" action="{$cfg.PAGEURL}login{if $smarty.get.p != "" && $smarty.get.p != "index"}?redirect_to={$smarty.get.p}{/if}" class="login-form" id="modal-login-form">                
                            <div class="form-group email">
                                <label class="sr-only" for="login-email">{$lang.INDEX.MAIL}</label>
                                <input id="login-email" name="email" type="email" required="" value="{if isset($smarty.post.email)}{$smarty.post.email|htmlentities}{/if}" class="form-control login-email" placeholder="{$lang.GENERAL.MAIL}">
                            </div><!--//form-group-->
                            <div class="form-group password">
                                <label class="sr-only">{$lang.GENERAL.YOUR_PW}</label>
                                {if $cfg.HASH_METHOD != "plain" && $cfg.CLIENTSIDE_HASHING == 1}<input id="modal-login-password" type="password" class="form-control login-password" placeholder="{$lang.GENERAL.PW}">
                                <input type="hidden" name="password" value="" id="modal-login-hashed" />{else}
                                <input id="login-password" type="password" name="password" class="form-control login-password" placeholder="{$lang.GENERAL.PW}">
                                {/if}
                            </div><!--//form-group-->
                            <div class="checkbox remember">
                                <label>
                                    <input type="checkbox" name="cookie" value="1" {if isset($smarty.post.cookie)}checked{/if}> {$lang.GENERAL.SET_COOKIE}
                                </label>
                            </div><!--//checkbox-->
                            {if isset($loginRedirect)}<input type="hidden" name="redirect_to" value="{$loginRedirect}" />
                            {/if}<button type="submit" name="login" id="modal-login-do" class="btn btn-block btn-primary">{$lang.GENERAL.LOGIN}</button>
                            <button type="submit" name="pwreset" class="btn btn-block btn-sm btn-warning">{$lang.GENERAL.GET_PW}</button>
                           	{if $cfg.FACEBOOK_LOGIN || $cfg.TWITTER_LOGIN}
<br /><div class="row">
    {if $cfg.FACEBOOK_LOGIN}
    <div class="col-sm-{if $cfg.TWITTER_LOGIN}6{else}12{/if}">
        <a href="{$cfg.PAGEURL}social_login/facebook" class="btn btn-block" style="background-color: #4060A5; border: none; color: white;">{$lang.GENERAL.FACEBOOK}</a>
    </div>
    {/if}
    {if $cfg.TWITTER_LOGIN}
    <div class="col-sm-{if $cfg.FACEBOOK_LOGIN}6{else}12{/if}">
        <a href="{$cfg.PAGEURL}social_login/twitter" class="btn btn-block" style="background-color: #00ABE3; border: none; color: white;">{$lang.GENERAL.TWITTER}</a>
    </div>
    {/if}
</div>
{/if}
                        </form>
                    </div><!--//login-form-container-->
                    {/if}
                </div><!--//modal-body-->
            </div><!--//modal-content-->
        </div><!--//modal-dialog-->
    </div><!--//modal-->
    {if $cfg.ALLOW_REG}
    <!-- Signup Modal -->
    <div class="modal modal-signup" id="signup-modal" tabindex="-1" role="dialog" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 id="signupModalLabel" class="modal-title text-center">{$lang.GENERAL.NEW_AT|replace:"%n":$cfg.PAGENAME}</h4>
                    <p class="intro text-center">{$lang.GENERAL.REGISTER_TIME}</p>
                </div>
                <div class="modal-body">
                    <div class="login-form-container">
                        <form class="login-form" method="POST" action="{$cfg.PAGEURL}register">                
                            <div class="form-group email">
                                <label class="sr-only" for="signup-email">{$lang.INDEX.MAIL}</label>
                                <input id="signup-email" type="email" class="form-control login-email" name="email" placeholder="{$lang.GENERAL.MAIL}">
                            </div><!--//form-group-->
                            <button type="submit" class="btn btn-block btn-primary">{$lang.GENERAL.NEXTSTEP}</button>
{if $cfg.FACEBOOK_LOGIN || $cfg.TWITTER_LOGIN}
<br /><div class="row">
    {if $cfg.FACEBOOK_LOGIN}
    <div class="col-sm-{if $cfg.TWITTER_LOGIN}6{else}12{/if}">
        <a href="{$cfg.PAGEURL}social_login/facebook" class="btn btn-block" style="background-color: #4060A5; border: none; color: white;">{$lang.GENERAL.FACEBOOK}</a>
    </div>
    {/if}
    {if $cfg.TWITTER_LOGIN}
    <div class="col-sm-{if $cfg.FACEBOOK_LOGIN}6{else}12{/if}">
        <a href="{$cfg.PAGEURL}social_login/twitter" class="btn btn-block" style="background-color: #00ABE3; border: none; color: white;">{$lang.GENERAL.TWITTER}</a>
    </div>
    {/if}
</div>
{/if}
                        </form>
                    </div><!--//login-form-container-->
                </div><!--//modal-body-->
            </div><!--//modal-content-->
        </div><!--//modal-dialog-->
    </div><!--//modal-->
{/if}
    <input type="hidden" id="csrf_token" value="{ct}" />

    <!-- Search dialog -->
    <div id="search">
        <form method="POST" action="{$cfg.PAGEURL}search">
            <input name="searchword" type="search" value="{if isset($smarty.request.searchword)}{$smarty.request.searchword|htmlentities}{/if}" placeholder="{$lang.GENERAL.ENTER_SEARCHWORD}" autocomplete="off" />
        </form>
    </div>
    <!--//search-->

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="{cdnurl}themes/standard/js/bootstrap.min.js"></script>
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
                    <h4 class="modal-title">{$browser_language_title}</h4>
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
            button: {background: "#428bca"},
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