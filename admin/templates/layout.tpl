<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="description" content="">
    <meta name="author" content="">

    <title>{if $currentPageTitle}{$currentPageTitle|htmlentities}{else}{$lang.GENERAL.ADMIN_AREA}{/if} :: {$cfg.PAGENAME}</title>

    <!-- Pace -->
    <link href="res/css/pace.css" rel="stylesheet">
    <script src="res/js/pace.min.js"></script>

    <!-- Bootstrap Core CSS -->
    <link href="res/css/bootstrap.min.css" rel="stylesheet">
    <link href="res/css/jasny-bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="res/css/custom.css?ver=20200326" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="res/css/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Colorpicker CSS -->
    <link href="res/css/plugins/colorpicker.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="res/css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="res/fonts/awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link rel="shortcut icon" href="{$cfg.PAGEURL}themes/favicon.ico" type="image/x-icon" />

    <!-- Dark mode -->
    <link href="{if isset($smarty.cookies.darkmode) && $smarty.cookies.darkmode}res/css/dark.css{/if}" rel="stylesheet" type="text/css" id="darkmode">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
    @-moz-document url-prefix() {
      fieldset { display: table-cell; }
    }
    </style>

    <script src="res/js/jquery.js"></script>
    <script src="res/js/jquery.blockUI.js"></script>

    {if $admin_color}
    <style>
    a {
        color: {$admin_color};
    }

    a:hover, a:active, a:focus {
        color: {$admin_color2};
    }

    .btn-primary:disabled {
        background-color: {$admin_color2};
        border-color: {$admin_color2};
    }

    .btn-primary, .list-group-item.active, .panel-primary > .panel-heading {
        background-color: {$admin_color};
        border-color: {$admin_color};
    }

    .panel-primary {
        border-color: {$admin_color};
    }

    .nav-pills > li.active > a {
        background-color: {$admin_color};
    }

    .nav-pills > li.active > a:hover {
        background-color: {$admin_color2};
    }

    .btn-primary:hover, .list-group-item.active:hover, .btn-primary:focus, .list-group-item.active:focus, .open>.dropdown-toggle.btn-primary {
        background-color: {$admin_color2};
        border-color: {$admin_color2};
    }

    .label-primary {
        background-color: {$admin_color};
    }
    </style>
    {/if}
</head>

<body>
    {if $cfg.LICENSE_KEY}
    <div class="feedback left hidden-xs hidden-sm">
        <div class="tooltips">
            <div class="btn-group dropup">
                <button type="button" class="btn btn-warning dropdown-toggle btn-circle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-bug fa-2x" title="{$lang.BUGREPORT.TITLE}"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-right dropdown-menu-form">
                <li>
                    <div class="report">
                    <h2 class="text-center">{$lang.BUGREPORT.TITLE}</h2>
                    <form class="doo" method="post" action="./report_bug.php">
                        <div class="col-sm-12">
                            <textarea required name="comment" class="form-control" placeholder="{$lang.BUGREPORT.HINT}"></textarea>
                            <input name="screenshot" type="hidden" class="screen-uri">
                            <span class="screenshot pull-right"><i class="fa fa-camera cam" title="{$lang.BUGREPORT.SS}"></i></span>
                        </div>
                        <div class="col-sm-12 clearfix">
                            <input type="hidden" name="sourcedesk_license" value="{$cfg.LICENSE_KEY}">
                            <input type="hidden" name="sourcedesk_url" value="">
                            <button class="btn btn-primary btn-block">{$lang.BUGREPORT.SEND}</button>
                        </div>
                    </form>
                    </div>
                    <div class="loading text-center hideme">
                    <h2>{$lang.BUGREPORT.PW}</h2>
                    <h2><i class="fa fa-refresh fa-spin"></i></h2>
                    </div>
                    <div class="reported text-center hideme">
                    <h2>{$lang.BUGREPORT.OK1}</h2>
                    <p>{$lang.BUGREPORT.OK2}</p>
                        <div class="col-sm-12 clearfix">
                        <button class="btn btn-success btn-block do-close">{$lang.GENERAL.CLOSE}</button>
                        </div>
                    </div>
                    <div class="failed text-center hideme">
                    <h2>{$lang.BUGREPORT.FAIL1}</h2>
                    <p>{$lang.BUGREPORT.FAIL2}</p>
                        <div class="col-sm-12 clearfix">
                        <button class="btn btn-danger btn-block do-close">{$lang.GENERAL.CLOSE}</button>
                        </div>
                    </div>
                </li>
                </ul>
            </div>
        </div>
    </div>
    {/if}

    <div class="darkmode left hidden-xs hidden-sm">
        <button type="button" class="btn btn-info btn-circle{if isset($smarty.cookies.darkmode) && $smarty.cookies.darkmode} active{/if}" id="darkmode_btn">
            <i class="fa fa-moon-o fa-2x" title="{$lang.GENERAL.DARKMODE}"></i>
        </button>
    </div>

    <div class="frontend left hidden-xs hidden-sm">
        <a href="{$cfg.PAGEURL}" target="_blank" class="btn btn-default btn-circle">
            <i class="fa fa-globe fa-2x"></i>
        </a>
    </div>

    <div class="wiki left hidden-xs hidden-sm">
        <a href="https://wiki.sourceway.de" target="_blank" class="btn btn-default btn-circle">
            <i class="fa fa-book fa-2x"></i>
        </a>
    </div>

    <div id="wrapper">
        <!-- Navigation -->
        {assign var="something" value=(("58"|in_array:$admin_rights && $waiting_testimonials > 0) || ($bugs > 0 && "28"|in_array:$admin_rights) || $payments > 0 || ($domain_wishes > 0 && "24"|in_array:$admin_rights) || $support > 0 || $abuse > 0 || $sepa > 0 || $open_projects > 0 || ($crit + $crit2 + $crit3 + $crit4 + $waiting_emails + $cronhang > 0))}
        <nav class="navbar navbar-default navbar-static-top navbar-fixed-top" role="navigation" style="margin-bottom: 0;{if $cfg.PAGENAME|substr:0:4 == 'dev@'} background-color: khaki;{/if}">
            <div class="navbar-header">
                <button type="button" class="notbarbut navbar-toggle{if (isset($smarty.cookies.admin_sidebar) && $smarty.cookies.admin_sidebar == "off") || $hideSidebar} navbar-toggle-hidden{/if}" data-toggle="collapse" data-target=".navbar-collapse"{if $something} style="border-color: orange;"{/if}>
                    <span class="sr-only">{$lang.GENERAL.TOGGLE_NAV}</span>
                    <span class="icon-bar notbarrow"{if $something} style="background-color: orange;"{/if}></span>
                    <span class="icon-bar notbarrow"{if $something} style="background-color: orange;"{/if}></span>
                    <span class="icon-bar notbarrow"{if $something} style="background-color: orange;"{/if}></span>
                </button>
                <a class="navbar-brand" href="./">{if file_exists("res/img/logo.svg")}<img src="res/img/logo.svg" style="margin-top: -11px; height: 40px;" alt="{$cfg.PAGENAME}" title="{$cfg.PAGENAME}" />{elseif file_exists("res/img/logo.png")}<img src="res/img/logo.png" style="margin-top: -8px; height: 35px;" alt="{$cfg.PAGENAME}" title="{$cfg.PAGENAME}" />{else}{$cfg.PAGENAME}{/if}</a>
            </div>
            <!-- /.navbar-header -->

            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" id="online_status"><span style="font-size: 30pt; line-height: 0; vertical-align: middle; color: {if $online_status == 1}green{elseif $online_status == 2}orange{else}red{/if};" id="status_bull" class="change_status">&bull;</span><span class="status_changing" style="display: none;"><i class="fa fa-spinner fa-spin"></i></span></a>
                    <ul class="dropdown-menu">
                        <li class="change_status"><a href="#" class="online_status" data-status="1">
                            <span style="font-size: 30pt; line-height: 0; vertical-align: middle; color: green;">&bull;</span>
                            {$lang.STATUS.ONLINE}
                        </a></li>
                        <li class="change_status"><a href="#" class="online_status" data-status="2">
                            <span style="font-size: 30pt; line-height: 0; vertical-align: middle; color: orange;">&bull;</span>
                            {$lang.STATUS.BUSY}
                        </a></li>
                        <li class="change_status"><a href="#" class="online_status" data-status="0">
                            <span style="font-size: 30pt; line-height: 0; vertical-align: middle; color: red;">&bull;</span>
                            {$lang.STATUS.AWAY}
                        </a></li>
                        <li style="display: none;" class="status_changing"><a href="#" onclick="return false;"><i class="fa fa-spinner fa-spin"></i> {$lang.STATUS.PW}</a></li>
                    </ul>
                </li>

                {$topbar}

                {if $sso|@count > 0}
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" style="padding-left: 10px; padding-right: 10px;">
                        <i class="fa fa-sign-in fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu">
                    {foreach from=$sso item=name key=link}
                    <li><a href="{$link}">{$name}</a></li>
                    {/foreach}
                    </ul>
                </li>
                {/if}

                <li>
                    {if $open_reminders == 1}
                    <a href="./?p=reminder" style="padding-left: 10px; padding-right: 10px;"><font color="orange"><i class="fa fa-bell fa-fw"></i> <span class="hidden-md hidden-lg">1</span><span class="hidden-xs hidden-sm">{$lang.TOP.ONE_REMINDER}</span></font></a>
                    {elseif $open_reminders > 0}
                    <a href="./?p=reminder" style="padding-left: 10px; padding-right: 10px;"><font color="orange"><i class="fa fa-bell fa-fw"></i> <span class="hidden-md hidden-lg">{$open_reminders}</span><span class="hidden-xs hidden-sm">{$lang.TOP.X_REMINDERS|replace:"%c":$open_reminders}</span></font></a>
                    {else}
                    <a href="./?p=reminder" style="padding-left: 10px; padding-right: 10px;"><i class="fa fa-bell fa-fw"></i> <span class="hidden-xs hidden-sm">{$lang.TOP.REMINDERS}</span></a>
                    {/if}
                </li>

                {$topMenu}

                {if "31"|in_array:$admin_rights}
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" style="padding-left: 10px; padding-right: 10px;">
                        <span id="project_top_menu"{if isset($working_project)} style="color: orange;"{/if}><i class="fa fa-clock-o fa-fw"></i><span class="hidden-xs hidden-sm"> <span id="project_menu_title"{if isset($working_project)} style="display: none;"{/if}>{$lang.MENU.PROJECTS}</span><span id="project_menu_time">{$working_project.project_timef}</span>{if isset($working_project)}<input type="hidden" id="working_rawtime" value="{$working_project.project_time}" />{/if}</span></span>
                    </a>
                    <ul class="dropdown-menu dropdown-tasks">
                        <li style="margin-bottom: -3px;{if !isset($working_project)} display: none;{/if}" class="project_top_task">
                            <a href="?p=view_project&id={if isset($working_project)}{$working_project.project}{else}###ID###{/if}" style="vertical-align: middle !important;" id="top_task_url">
                                <div>
                                    <p style="margin-bottom: 5px; margin-top: 5px;">
                                        <strong id="top_task_title">{if isset($working_project)}{$working_project.project_name}{/if}</strong>
                                        <span class="pull-right" id="top_task_stop" onclick="pauseTaskTime({if isset($working_project)}{$working_project.ID}{else}###ID###{/if}); return false;"><i class="fa fa-pause" style="color: orange;"></i></span>
                                    </p>
                                </div>
                            </a>
                        </li><li class="divider project_top_task"{if !isset($working_project)} style="display: none;"{/if}></li>
                        {foreach from=$due_projects key=id item=info}
                        <li>
                            <a href="?p=view_project&amp;id={$id}">
                                <div>
                                    <p>
                                        <strong>{if $info.star}<i class="fa fa-star" style="color: #EAC117;"></i>{/if} {$info.name|htmlentities}</strong>
                                        <span class="pull-right text-muted">{$info.percent}%</span>
                                    </p>
                                    <div class="progress progress-striped active">
                                        <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{$info.percent}" aria-valuemin="0" aria-valuemax="100" style="width:{$info.percent}%;">
                                            <span class="sr-only">{$info.percent}%</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li><li class="divider"></li>
                        {foreachelse}
                        <li>
                            <a href="#">
                                <div>
                                    <p>
                                        <center>{$lang.TOP.NO_PROJECTS}</center>
                                    </p>
                                </div>
                            </a>
                        </li>
                        <li class="divider"></li>
                        {/foreach}
                        <li>
                            <a class="text-center" href="?p=projects">
                                <strong>{$lang.TOP.PROJECT_OVERVIEW}</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                    <!-- /.dropdown-tasks -->
                </li>
                {/if}

                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" style="padding-left: 10px; padding-right: 10px;">
                        <img src="../files/avatars/{if $ownAvatar}{$ownAvatar|htmlentities}{else}none.png{/if}" title="{$adminInfo.name|htmlentities}" alt="{$adminInfo.name|htmlentities}" style="border-radius: 50%; height: 18px; width: 18px;" />
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="?p=admin"><i class="fa fa-user fa-fw"></i> {$lang.TOP.PROFILE}</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#notesModal"><i class="fa fa-file-text fa-fw"></i> {$lang.TOP.NOTES}</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#languageModal"><i class="fa fa-flag fa-fw"></i> {$lang.TOP.LANGUAGE}</a></li>
                        <li><a href="?p=api"><i class="fa fa-code fa-fw"></i> {$lang.TOP.API}</a></li>
                        {if "6"|in_array:$admin_rights || "5"|in_array:$admin_rights}<li><a href="?p=password"><i class="fa fa-lock fa-fw"></i> {$lang.TOP.CREDENTIALS}</a>
                        <li><a href="?p=my_settings"><i class="fa fa-wrench fa-fw"></i> {$lang.TOP.SETTINGS}</a>
                        </li>{/if}
                        <li><a href="?a=logout"><i class="fa fa-sign-out fa-fw"></i> {$lang.TOP.LOGOUT}</a></li>
                        {if $otherAdmins|@count > 0}
                        <li class="divider"></li>
                        {foreach from=$otherAdmins key=id item=name}
                        <li><a href="?p=switch_admin&id={$id}"><font style="font-size: 30pt; line-height: 0; color: {if $name.1 == 1}green{elseif $name.1 == 2}orange{else}red{/if}; position: relative; top: 9px;">&bull;</font> {$name.0|strip_tags}</a></li>
                        {/foreach}
                        {/if}
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->

                {if $sa|@count > 0}
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" style="padding-left: 10px; padding-right: 10px;">
                        <i class="fa fa-building fa-fw"></i> <span class="hidden-sm hidden-xs">{$cfg.PAGENAME|htmlentities}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        {foreach from=$sa item=s}
                        <li><a href="?p=sa&to={$s.0|urlencode}&user={$s.1|urlencode}"><img src="{$s.0|rtrim:"/"}/themes/favicon.ico" height="16px" alt="{$cfg.PAGENAME|htmlentities}" title="{$s.2|htmlentities}"> {$s.2|htmlentities}</a></li>
                        {/foreach}
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
                {/if}

                <li>
                    <div class="shortcut{if $shortcut} shortcut-active{/if}">
                        <a href="#" onclick="shortcut(); return false;" style="padding-right: 10px;"><i id="shortcut_icon" class="fa fa-star{if !$shortcut}-o{/if}"{if $shortcut} style="color: #EAC117;"{/if}></i></a>
                    </div>
                </li>
            </ul>
            <!-- /.navbar-top-links -->

            {if (isset($smarty.cookies.admin_sidebar) && $smarty.cookies.admin_sidebar == "off") || $hideSidebar}{literal}
            <script type="text/javascript">
            var hide_sidebar = true;
            </script>
            {/literal}{/if}

            <div class="navbar-default navbar-collapse sidebar collapse" id="mainnav" role="navigation" style="{if $hideSidebar}display: none; {/if}position: fixed; overflow-y: auto; top: 0;">
                <div class="sidebar-nav{if (isset($smarty.cookies.admin_sidebar) && $smarty.cookies.admin_sidebar == "off") || $hideSidebar} sidebar-nav-hidden{/if}">
                    <ul class="nav navbar" id="side-menu">

                        {if "39"|in_array:$admin_rights}<li>
                            <a class="hidden-xs"><form method="GET"><div class="row"><div class="col-sm-10"><input type="hidden" name="p" value="search"><div class="custom-search-form">
                                <input type="text" id="searchword" name="searchword" value="{if isset($smarty.get.searchword)}{$smarty.get.searchword|htmlentities}{/if}" placeholder="{$lang.MENU.SEARCH}" class="form-control"><span class="input-group-btn">
                                <button class="btn btn-default" type="button" onclick="return searchReset();" style="display: none;">
                                    <i class="fa fa-times"></i>
                                </button>
                            </span></div></div><div class="col-sm-2" style="padding-left: 0;"><button type="button" class="btn btn-default" onclick="hideSidebar();"><i class="fa fa-angle-double-left"></i></button></div></div></form></a>

                            <a class="hidden-sm hidden-md hidden-lg"><form method="GET"><input type="hidden" name="p" value="search"><div class="input-group custom-search-form">
                                <input type="text" name="searchword" value="{if isset($smarty.get.searchword)}{$smarty.get.searchword|htmlentities}{/if}" placeholder="{$lang.MENU.SEARCH}" class="form-control"><span class="input-group-btn">
                                <button class="btn btn-default" type="submit">
                                    <i class="fa fa-search"></i>
                                </button>
                            </span></div></form></a>
                        </li>{/if}

                        <li class="side-entry">
                            <a href="./"><i class="fa fa-dashboard fa-fw"></i> {$lang.MENU.DASHBOARD}</a>
                        </li>

                        {if $shortcuts|@count > 0}<li class="side-entry">
                            <a href="#"><i class="fa fa-star fa-fw"></i> {$lang.QUICKLINKS.NAV}</a>
                            <ul class="nav nav-second-level collapse">
                                {foreach from=$shortcuts item=info}<li><a href="?{$info.url}"><span id="shortcut-{$info.ID}">{$info.text|htmlentities}</span><input style="display: none; color: black;" type="text" id="shortcut-{$info.ID}-edit" value="{$info.text|htmlentities}" onclick="return false;" onkeydown="if (event.keyCode == 13) { change_shortcut({$info.ID}); return false; }" /> <i class="fa fa-pencil fa-fw" onclick="edit_shortcut({$info.ID}); return false;" id="shortcut-{$info.ID}-edit-link"></i><i class="fa fa-times fa-fw" id="shortcut-{$info.ID}-remove-link" onclick="{if $shortcut == $info.ID}shortcut();{else}delete_shortcut({$info.ID});{/if} $(this).closest('li').remove(); return false;"></i></a></li>{/foreach}
                            </ul>
                        </li>{/if}

                        {assign var="show_customers_rights" value=","|explode:"7,10,21,22,18,15,49,53,54,57"}
                        {assign "show_customers" "0"}

                        {foreach from=$show_customers_rights item=right}
                            {if $right|in_array:$admin_rights}
                                {assign "show_customers" "1"}
                            {/if}
                        {/foreach}

                        {if $show_customers}
                        <li class="side-entry">
                            <a href="#" id="menu_customers"><i class="fa fa-users fa-fw"></i> {$lang.MENU.CUSTOMERS} {if ("58"|in_array:$admin_rights && $waiting_testimonials > 0) || ($domain_wishes > 0 && "24"|in_array:$admin_rights)}<span class="label label-warning">{if "58"|in_array:$admin_rights && "13"|in_array:$admin_rights}{$domain_wishes + $waiting_testimonials}{elseif "58"|in_array:$admin_rights}{$waiting_testimonials}{elseif "13"|in_array:$admin_rights}{$domain_wishes}{/if}</span>{/if}</a>
                            <ul class="nav nav-second-level collapse">
                                {if "7"|in_array:$admin_rights}<li>
                                    <a href="./?p=customers"> {$lang.MENU.LIST}</a>
                                </li>{/if}
                                {if "10"|in_array:$admin_rights}<li>
                                    <a href="./?p=add_customer"> {$lang.MENU.ADD}</a>
                                </li>{/if}
                                {if "7"|in_array:$admin_rights}<li>
                                    <a href="./?p=quotes"> {$lang.MENU.QUOTES}</a>
                                </li>{/if}
                                {if "7"|in_array:$admin_rights}<li>
                                    <a href="./?p=letters"> {$lang.MENU.LETTERS}</a>
                                </li>{/if}
                                {if "8"|in_array:$admin_rights}<li>
                                    <a href="./?p=usermap"> {$lang.USERMAP.TITLE}</a>
                                </li>{/if}
                                {if "57"|in_array:$admin_rights}<li>
                                    <a href="./?p=birthdays"> {$lang.BIRTHDAYS.TITLE}</a>
                                </li>{/if}
                                {if "21"|in_array:$admin_rights}<li>
                                    <a href="./?p=newsletter"> {$lang.MENU.NEWSLETTER}</a>
                                </li>{/if}
                                {if "18"|in_array:$admin_rights}<li>
                                    <a href="./?p=cart"> {$lang.MENU.CARTS}</a>
                                </li>{/if}
                                {if "58"|in_array:$admin_rights}<li>
                                    <a href="./?p=testimonials"> {$lang.TESTIMONIALS.TITLE} {if $waiting_testimonials > 0}<span class="label label-warning">{$waiting_testimonials}</span>{/if}</a>
                                </li>{/if}
                                {if "53"|in_array:$admin_rights}<li>
                                    <a href="?p=blacklist"> {$lang.BLACKLIST.NAME}</a>
                                </li>{/if}
                                {if "13"|in_array:$admin_rights}<li>
                                    <a href="?p=dns"> {$lang.DOMAINS.TITLE} {if $domain_wishes > 0}<span class="label label-warning">{$domain_wishes}</span>{/if}</a>
                                </li>{/if}
                                {if "15"|in_array:$admin_rights}<li>
                                    <a href="?p=transactions"> {$lang.MENU.TRANSACTIONS}</a>
                                </li>{/if}
                                {if "49"|in_array:$admin_rights}<li>
                                    <a href="?p=logs"> {$lang.MENU.LOGS}</a>
                                </li>{/if}
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>
                        {/if}

                        {assign var="show_products_rights" value=","|explode:"24,25,45,28,59,64"}
                        {assign "show_products" "0"}

                        {foreach from=$show_products_rights item=right}
                            {if $right|in_array:$admin_rights}
                                {assign "show_products" "1"}
                            {/if}
                        {/foreach}

                         {if $show_products}<li class="side-entry">
                            <a  href="#" id="menu_products"><i class="fa fa-shopping-cart fa-fw"></i> {$lang.MENU.PRODUCTS} {if ($bugs > 0 && "28"|in_array:$admin_rights) || ($lack_wishes > 0 && "59"|in_array:$admin_rights) || ($crit5 > 0 && "24"|in_array:$admin_rights) || ($lack_wishes > 0 && "59"|in_array:$admin_rights)}<span class="label label-warning">!</span>{/if}</a>
                            <ul class="nav nav-second-level collapse">
                                {if "24"|in_array:$admin_rights}<li>
                                    <a href="./?p=products"> {$lang.MENU.MANAGE}</a>
                                </li>{/if}
                                {if "25"|in_array:$admin_rights}<li>
                                    <a href="./?p=add_product"> {$lang.MENU.ADD}</a>
                                </li>{/if}
                                {if "45"|in_array:$admin_rights}<li>
                                    <a href="./?p=categories"> {$lang.MENU.CATEGORIES}</a>
                                </li>{/if}
                                {if "24"|in_array:$admin_rights}<li>
                                    <a href="./?p=bundles"> {$lang.MENU.BUNDLES}</a>
                                </li>{/if}
                                {if "24"|in_array:$admin_rights}<li>
                                    <a href="./?p=domains"> {$lang.MENU.DOMAINS}</a>
                                </li>{/if}
                                {if "59"|in_array:$admin_rights}<li>
                                    <a href="./?p=wishlist"> {$lang.MENU.WISHLIST} {if $lack_wishes > 0}<span class="label label-warning">{$lack_wishes}</span>{/if}</a>
                                </li>{/if}
                                {if "24"|in_array:$admin_rights}<li>
                                    <a href="./?p=offers"> {$lang.MENU.OFFERS} {if $crit5 > 0}<span class="label label-warning">{$crit5}</span>{/if}</a>
                                </li>{/if}
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>{/if}

                        {if "50"|in_array:$admin_rights || "69"|in_array:$admin_rights}
                        <li class="side-entry">
                        <a href="#" id="menu_cms"><i class="fa fa-newspaper-o fa-fw"></i> {$lang.MENU.CMS}</a>
                            <ul class="nav nav-second-level collapse">
                                {if "50"|in_array:$admin_rights}
                                <li>
                                    <a href="?p=cms_menu">{$lang.MENU.MENU}</a>
                                </li>
                                <li>
                                    <a href="?p=cms_pages">{$lang.MENU.PAGES}</a>
                                </li>
                                <li>
                                    <a href="?p=cms_blog">{$lang.MENU.BLOG}</a>
                                </li>
                                <li>
                                    <a href="?p=cms_faq">{$lang.MENU.FAQ}</a>
                                </li>
                                <li>
                                    <a href="?p=forum">{$lang.FORUM.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=knowledgebase">{$lang.KNOWLEDGEBASE.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=cms_links">{$lang.CMS_LINKS.TITLE}</a>
                                </li>
                                {/if}
                                {if "69"|in_array:$admin_rights || "69"|in_array:$admin_rights}
                                <li>
                                    <a href="?p=social_media">{$lang.SOCIAL_MEDIA.TITLE}</a>
                                </li>
                                {/if}
                            </ul>
                        </li>
                        {/if}


                        {if "30"|in_array:$admin_rights}<li class="side-entry">
                            <a href="./?p=projects"><i class="fa fa-clock-o fa-fw"></i> {$lang.MENU.PROJECTS} {if $open_projects > 0}<span class="label label-warning">{$open_projects}</span>{/if}</a>
                        </li>{/if}

                        {if "66"|in_array:$admin_rights}<li class="side-entry">
                            <a href="./?p=monitoring"><i class="fa fa-server fa-fw"></i> {$lang.MENU.MONITORING} {if $monitoring > 0}<span class="label label-danger">{$monitoring}</span>{/if}</a>
                        </li>{/if}

                        {assign "show_payments" "0"}
                        {if "13"|in_array:$admin_rights}
                            {assign "show_payments" "1"}
                        {elseif "41"|in_array:$admin_rights}
                            {assign "show_payments" "1"}
                        {elseif "56"|in_array:$admin_rights}
                            {assign "show_payments" "1"}
                        {elseif "60"|in_array:$admin_rights}
                            {assign "show_payments" "1"}
                        {elseif "43"|in_array:$admin_rights && $show_logs}
                            {assign "show_payments" "1"}
                        {elseif "40"|in_array:$admin_rights}
                            {assign "show_payments" "1"}
                        {elseif isset($sepa)}
                            {assign "show_payments" "1"}
                        {/if}

                        {if $show_payments}<li class="side-entry">
                            <a  href="#" id="menu_payments"><i class="fa fa-credit-card fa-fw"></i> {$lang.MENU.PAYMENTS} {if $payments > 0 || $sepa > 0}<span class="label label-warning">{$payments + $sepa}</span>{/if}</a>
                            <ul class="nav nav-second-level collapse">
                                {if "33"|in_array:$admin_rights && $wire_active}<li>
                                    <a href="./?p=payments">{$lang.MENU.WIRE} {if $payments > 0}<span class="label label-warning">{$payments}</span>{/if}</a>
                                </li>{/if}
                                {if isset($sepa)}<li>
                                    <a href="./?p=sepa">{$lang.MENU.SEPA} {if $sepa > 0}<span class="label label-warning">{$sepa}</span>{/if}</a>
                                </li>
                                <li>
                                    <a href="./?p=customers_sepa">{$lang.MENU.SEPA2}</a>
                                </li>{/if}
                                {if "40"|in_array:$admin_rights}<li>
                                    <a href="./?p=fibu">{$lang.FIBU.TITLE}</a>
                                </li>{/if}
                                {if "60"|in_array:$admin_rights}
                                    <li>
                                        <a href="./?p=suppliers">{$lang.MENU.SUPPLIERS}</a>
                                    </li>
                                {/if}
                                {if "56"|in_array:$admin_rights}<li>
                                    <a href="./?p=vouchers">{$lang.VOUCHERS.TITLE}</a>
                                </li>{/if}
                                {if !$cfg.NO_INVOICING}
                                {if "16"|in_array:$admin_rights}<li>
                                    <a href="./?p=external_invoices">{$lang.MENU.EINVOICES}</a>
                                </li>{/if}
                                {if "16"|in_array:$admin_rights}<li>
                                    <a href="./?p=open_invoices">{$lang.MENU.OINVOICES}</a>
                                </li>{/if}
                                {/if}
                                {if "13"|in_array:$admin_rights}
                                    <li>
                                        <a href="./?p=open_orders">{$lang.MENU.OORDERS}</a>
                                    </li>
                                {/if}
                                {if "43"|in_array:$admin_rights && $show_logs}<li>
                                    <a href="./?p=payment_log">{$lang.PAYMENT_LOG.TITLE}</a>
                                </li>{/if}
                                {if "41"|in_array:$admin_rights}<li>
                                    <a href="./?p=gateways">{$lang.MENU.GWSET}</a>
                                </li>{/if}
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>{/if}

                        {if "40"|in_array:$admin_rights}
                        <li class="side-entry">
                        <a href="#" id="menu_statistics"><i class="fa fa-area-chart fa-fw"></i> {$lang.MENU.STATISTICS}</a>
                            <ul class="nav nav-second-level collapse">
                                <li>
                                    <a href="?p=daily_performance">{$lang.DAILY_PERFORMANCE.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=analytics">{$lang.ANALYTICS.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_transactions">{$lang.MENU.TRANSACTIONS}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_top10">{$lang.TOP10.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_liabilities">{$lang.MENU.LIABILITIES}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_debtors">{$lang.MENU.DEBTORS}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_financial">{$lang.FINANCIAL_OVERVIEW.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_orders">{$lang.STAT_ORDERS.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_customers">{$lang.STAT_CUSTOMERS.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=domain_revenue">{$lang.DOMAIN_REVENUE.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_forecast">{$lang.STAT_FORECAST.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=stat_support">{$lang.STAT_SUPPORT.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=cust_source&stat=1">{$lang.CUST_SOURCE.TITLE}</a>
                                </li>
                                {if !$cfg.NO_INVOICING}
                                <li>
                                    <a href="?p=stat_invoices">{$lang.INVOICE_EXPORT.TITLE}</a>
                                </li>
                                {if $cfg.TAXES}
                                <li>
                                    <a href="?p=zm">{$lang.MENU.ZM}</a>
                                </li>
                                <li>
                                    <a href="?p=tax">{$lang.MENU.TAX}</a>
                                </li>
                                {/if}
                                {/if}
                                <li>
                                    <a href="?p=availibility">{$lang.MENU.AVAILIBILITY}</a>
                                </li>
                            </ul>
                        </li>
                        {/if}

                        {if "65"|in_array:$admin_rights || "68"|in_array:$admin_rights}
                        <li class="side-entry">
                        {if empty($supportLink)}
                        <a href="#" id="menu_support"><i class="fa fa-envelope fa-fw"></i> {$lang.MENU.SUPPORT} {if $support + $abuse > 0}<span class="label label-warning">{$support + $abuse}</span>{/if}</a>
                            <ul class="nav nav-second-level collapse">
                                {if $support > 0}
                                <li>
                                    <a href="?p=support_next">{$lang.MENU.NEXT_TICKET} {if $support > 0}<span class="label label-warning">{$support}</span>{/if}</a>
                                </li>
                                {/if}
                                <li>
                                    <a href="?p=support_tickets">{$lang.MENU.ALLT} {if $support > 0}<span class="label label-warning">{$support}</span>{/if}</a>
                                </li>
                                <li>
                                    <a href="?p=support_tickets&dept={$adminInfo.ID / -1}">{$lang.MENU.MYT} {if $support_my > 0}<span class="label label-warning">{$support_my}</span>{/if}</a>
                                </li>
                                {foreach from=$support_depts key=id item=i}
                                <li>
                                    <a href="?p=support_tickets&dept={$id}">{$i.0} {if $i.1 > 0}<span class="label label-warning">{$i.1}</span>{/if}</a>
                                </li>
                                {/foreach}
                                {if "61"|in_array:$admin_rights}<li>
                                    <a href="?p=support_config">{$lang.MENU.TS}</a>
                                </li>{/if}
                                {if "68"|in_array:$admin_rights}<li>
                                    <a href="?p=abuse">{$lang.ABUSE.TITLE} {if $abuse > 0}<span class="label label-warning">{$abuse}</span>{/if}</a>
                                </li>{/if}
                            </ul>
                        {else}
                        {$supportLink}
                        {/if}
                        </li>
                        {/if}

                        {if count($addon_menu) > 0 || "44"|in_array:$admin_rights}
                        <li class="side-entry">
                        <a href="#" id="menu_addons"><i class="fa fa-puzzle-piece fa-fw"></i> {$lang.MENU.ADDONS}{if !empty($addonlabel)} <span class="label label-warning">{$addonlabel}</span>{/if}</a>
                            <ul class="nav nav-second-level collapse">
                                {if "44"|in_array:$admin_rights}
                                <li>
                                    <a href="?p=marketplace">{$lang.MARKETPLACE.TITLE}</a>
                                </li>
                                <li>
                                    <a href="?p=addons">{$lang.ADDON_MANAGEMENT.MENU}</a>
                                </li>
                                {/if}
                                {foreach from=$addon_menu item=page key=name}<li>
                                    <a href="?p={if is_array($page)}{$page.0}{else}{$page}{/if}">{$name}{if is_array($page) && !empty($page.1)} <span class="label label-warning">{$page.1}</span>{/if}</a>
                                </li>
                                {/foreach}
                            </ul>
                        </li>
                        {/if}

                        {assign var="show_settings_rights" value=","|explode:"34,47,35,48,36,37,42,38"}
                        {assign "show_settings" "0"}

                        {foreach from=$show_settings_rights item=right}
                            {if $right|in_array:$admin_rights}
                                {assign "show_settings" "1"}
                            {/if}
                        {/foreach}

                        {if $show_settings}<li class="side-entry">
                            <a  href="#" id="menu_settings"><i class="fa fa-cog fa-fw"></i> {$lang.MENU.SYSTEM} {if $crit + $crit2 + $crit3 + $crit4 + $waiting_emails + $cronhang > 0}<span class="label label-warning">!</span>{/if}</a>
                            <ul class="nav nav-second-level collapse">
                                {if "34"|in_array:$admin_rights}<li>
                                    <a href="./?p=settings"> {$lang.MENU.SS} {if $crit + $crit2 + $crit3 + $cronhang > 0}<span class="label label-warning">!</span>{/if}</a>
                                </li>{/if}
                                {if "47"|in_array:$admin_rights}<li>
                                    <a href="./?p=mail_queue"> {$lang.MENU.MAIL} {if $waiting_emails > 0}<span class="label label-warning">{$waiting_emails}</span>{/if}</a>
                                </li>{/if}
                                {if "36"|in_array:$admin_rights || "37"|in_array:$admin_rights || "42"|in_array:$admin_rights}<li>
                                    <a href="./?p=texts"> {$lang.TEXTS.TITLE}</a>
                                </li>{/if}
                                {if "35"|in_array:$admin_rights}<li>
                                    <a href="./?p=admins"> {$lang.MENU.ADMINS}</a>
                                </li>{/if}
                                {if "52"|in_array:$admin_rights}<li>
                                    <a href="./?p=admin_log"> {$lang.ADMIN_LOG.TITLE}</a>
                                </li>{/if}
                                {if "48"|in_array:$admin_rights}<li>
                                    <a href="./?p=backup"> {$lang.MENU.BACKUPS}</a>
                                </li>{/if}
                                {if "34"|in_array:$admin_rights}<li>
                                    <a href="./?p=update"> {$lang.SYSTEMUPDATE.TITLE} {if $crit4 > 0}<span class="label label-warning">!</span>{/if}</a>
                                </li>{/if}
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>{/if}
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
        </nav>

        <a style="position: absolute; top: 60px; left: -7px; padding-right: 3px;{if !((isset($smarty.cookies.admin_sidebar) && $smarty.cookies.admin_sidebar == "off") || $hideSidebar)} display: none;{/if}" class="btn btn-default" id="show-sidebar"><i class="fa fa-angle-double-right"></i></a>

        <div style="margin-top:51px;" class="body_wrapper">
        <div id="page-wrapper"{if $devLicense} style="padding-bottom: 50px; padding-left: 0;"{/if}{if (isset($smarty.cookies.admin_sidebar) && $smarty.cookies.admin_sidebar == "off") || $hideSidebar} class="page-wrapper-hidden"{/if}>

        {if $devLicense}
        <!-- Do not remove! -->
        <div style="position: fixed; bottom: 0; height: 50px; background-color: #fcf6e3; width: 100%; z-index: 1000; padding: 0 30px; font-size: 28px; padding-top: 6px;">
            <b>DEVELOPMENT LICENSE!</b> <span class="hidden-sm hidden-xs">DO NOT USE IN PRODUCTION</span>
        </div><div style="padding-left: 30px;">
        <!-- Do not remove! -->
        {/if}
        {if !empty($admin_hint)}<br /><div class="alert alert-info" style="margin-bottom: -15px;">{$admin_hint}</div>{/if}
        {if $user_session}<span id="user_session"><br /><div class="alert alert-info" style="margin-bottom: -20px;">{$lang.GENERAL.LOGGEDIN} {$user_session}. [ <a href="{$cfg.PAGEURL}" target="_blank">{$lang.GENERAL.TO_FRONTEND}</a> | <a href="#" id="user_logout">{$lang.GENERAL.DO_LOGOUT}</a> ]</div></span>{/if}
        {if isset($hide_content)}<div id="content_before"></div><div id="content_hidden" style="display: none;">{/if}
        {if !isset($content)}
            {if !empty($tpl)}{include file=$tpl}{/if}<br />
        {else}
            <!-- OLD START -->
            {$content}<br />
            <!-- OLD END -->
        {/if}
        {if isset($hide_content)}</div>{/if}
        </div></div>
        {if $devLicense}</div>{/if}
    </div>
    <!-- /#wrapper -->

    {if $tin && (basename($tpl) == "index.tpl" || basename($tpl) == "update.tpl")}
    <!-- tin modal -->
    <div class="modal" id="tinModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="tinModalLabel">{$lang.GENERAL.TIN}</h4>
                </div>
                <div class="modal-body">
                    {$tin}
                </div>
                <div class="modal-footer" style="text-align: left;">
                    <div class="checkbox" style="margin: 0;">
                        <label>
                            <input type="checkbox" id="tinHide">
                            {$lang.GENERAL.TINI}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- tin modal end -->

    <script>
    $(document).ready(function() {
        $("#tinModal").modal("show");

        $("#tinHide").change(function() {
            $(".checkbox").html("<i class='fa fa-spinner fa-pulse'></i> {$lang.GENERAL.PLEASE_WAIT}");
            $.post("", {
                "hide_tin_modal": "1",
                "csrf_token": "{ct}",
            }, function(r) {
                if (r == "ok") {
                    $(".checkbox").html("<span style='color: green'><i class='fa fa-check'></i> {$lang.GENERAL.TINO}</span>");
                }
            });
        });
    });
    </script>
    {/if}

    <!-- language modal -->
    <div class="modal fade" id="languageModal" tabindex="-1" role="dialog" aria-hidden="true">
      <form method="POST">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="languageModalLabel">{$lang.TOP.LANGUAGE}</h4>
          </div>
          <div class="modal-body">
            <select class="form-control" name="new_language">
                {foreach from=$admin_languages key=slug item=name}
                <option value="{$slug}"{if $name == $lang.NAME} selected="selected"{/if}>{$name}</option>
                {/foreach}
            </select>

            {foreach from=$smarty.post key=k item=v}
                {if $k != "new_language" && is_string($v)}
                <input type="hidden" name="{$k|replace:'"':"'"}" value="{$v|htmlentities}">
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

    <!-- notes modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="languageModalLabel">{$lang.TOP.NOTES}</h4>
          </div>
          <div class="modal-body">
            <textarea id="myNotes" class="form-control" style="height: 300px; resize: none; width: 100%;">{$admin_info.notes|htmlentities}</textarea><br />
            <button type="button" onClick="saveNotes();" id="saveNotesButton" class="btn btn-primary btn-block">{$lang.GENERAL.SAVE}</button>
          </div>
        </div>
      </div>
    </div>
    <!-- notes modal end -->

    <!-- ajax model start -->
    <div class="modal fade" id="ajaxModal" tabindex="-1" role="dialog" aria-labelledby="ajaxModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="ajaxModalLabel">{$lang.GENERAL.PLEASEWAIT}</h4>
          </div>
          <div class="modal-body" id="ajaxModalContent">
            <i class="fa fa-spinner fa-spin"></i> {$lang.GENERAL.LOADING}
          </div>
        </div>
      </div>
    </div>
    <!-- ajax model end -->

    <input type="hidden" id="csrf_token" value="{ct}" />

    <!-- Bootstrap Core JavaScript -->
    <script src="res/js/bootstrap.min.js"></script>
    <script src="res/js/jasny-bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="res/js/plugins/metisMenu/metisMenu.min.js"></script>

    <!-- SweetAlert -->
    <link href="res/css/plugins/swal/sweetalert.css" rel="stylesheet">
    <script src="res/js/plugins/swal/sweetalert.min.js"></script>

    <!-- Colorpicker JS -->
    <script src="res/js/plugins/colorpicker.min.js"></script>

    <!-- Datetimepicker -->
    <script src="res/js/plugins/moment.min.js"></script>
    <link href="res/js/plugins/datetime/datetime.css" rel="stylesheet">
    <script src="res/js/plugins/datetime/datetime.js"></script>

    <!-- Trumbowyg -->
    <script src="res/js/trumbowyg/trumbowyg.min.js"></script>
    <link rel="stylesheet" href="res/js/trumbowyg/ui/trumbowyg.min.css">
    <script src="res/js/trumbowyg/plugins/colors/trumbowyg.colors.min.js"></script>
    <link rel="stylesheet" href="res/js/trumbowyg/plugins/colors/ui/trumbowyg.colors.min.css">
    <script src="res/js/trumbowyg/plugins/pasteimage/trumbowyg.pasteimage.min.js"></script>
    <script type="text/javascript" src="res/js/trumbowyg/langs/de.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="res/js/sb-admin-2.js"></script>{if $cfg.HASH_METHOD_ADMIN != "plain" && $cfg.CLIENTSIDE_HASHING_ADMIN == 1}

    <!-- Clientside hashing -->
    <script type="text/javascript" src="{$cfg.PAGEURL}lib/crypt/{$cfg.HASH_METHOD_ADMIN|rtrim:"salt"}.js"></script>
    <script type="text/javascript">var hash_method = '{$cfg.HASH_METHOD_ADMIN}';</script>
    <script src="res/js/crypto.js"></script>
    {/if}

    <!-- Additional JS files/code -->
    <script src="res/js/html2canvas.min.js"></script>
    {if isset($customJSFiles) && is_array($customJSFiles)}{foreach from=$customJSFiles item=file}
    <script type="text/javascript" src="res/js/custom/{$file}.js?ver=20200907"></script>
    {/foreach}{/if}
    {literal}<script type="text/javascript">
        function navbarScroll() {
            $('.sidebar').css('height', $(window).height() - $('.navbar-fixed-top').height());
            $('.sidebar').css('max-height', $(window).height() - $('.navbar-fixed-top').height());
        }

        navbarScroll();

        $(window).resize(function(){
            navbarScroll();
        });

        function saveNotesResponse(text) {
            $("#saveNotesButton").prop("disabled", true);

            if(text == "saved"){
                $("#saveNotesButton").html('{/literal}{$lang.GENERAL.SAVED}{literal}');
                $("#saveNotesButton").addClass("btn-success");
            } else {
                $("#saveNotesButton").html('{/literal}{$lang.GENERAL.SAVE_FAILED}{literal}');
                $("#saveNotesButton").addClass("btn-danger");
            }

            setTimeout(function() {
                $("#saveNotesButton").removeClass("btn-success btn-danger");
                $("#saveNotesButton").html("{/literal}{$lang.GENERAL.SAVE}{literal}");
                $("#saveNotesButton").prop("disabled", false);
            }, 1750);
        }

        {/literal}{if $smarty.get.p != "search"}{literal}
        var search = "";
        function doSearch() {
            $.post("?p=quick_search", {
                searchword: $("#searchword").val(),
                csrf_token: {/literal}"{ct}"{literal},
            }, function(r) {
                $(".search-result").remove();
                $("#side-menu").append(r);
                search = $("#searchword").val();
            });
        }

        function searchReset() {
            $("#searchword").val("");
            $(".custom-search-form").removeClass("input-group").find(".btn").hide();
            $(".side-entry").show();
            $(".search-result").remove();
        }

        $("#searchword").keyup(function() {
            if(typeof typingTimer != 'undefined') clearTimeout(typingTimer);
            if($("#searchword").val() == ""){
                $(".custom-search-form").removeClass("input-group").find(".btn").hide();
                $(".search-result").remove();
                $(".side-entry").show();
            } else {
                $(".side-entry").hide();
                $(".custom-search-form").addClass("input-group").find(".btn").show();
                if($("#searchword").val() != search) typingTimer = setTimeout(function(){ doSearch(); }, 300);
            }
        });

        $('.summernote').trumbowyg({
            autogrow: true,
            lang: 'de',
            btns: [
                ['viewHTML'],
                ['formatting'],
                ['foreColor', 'backColor'],
                'btnGrp-semantic',
                ['superscript', 'subscript'],
                ['link'],
                ['insertImage'],
                'btnGrp-justify',
                'btnGrp-lists',
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen']
            ],
        });
        {/literal}{/if}{if isset($additionalJS)}{$additionalJS}
        {/if}{if $menuToOpen != ""}{literal}$(document).ready(function(){$("#menu_{/literal}{$menuToOpen}{literal}").click();});{/literal}{/if}{literal}

        $.blockUI.defaults.message = "<h1 style=\"margin-top: 10px;\">{/literal}{$lang.GENERAL.PLEASEWAIT}{literal}...</h1>";

        if (Notification.permission !== 'denied' || Notification.permission === "default") {
            Notification.requestPermission(function (permission) {
                if (permission === "granted") {
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('./res/js/service-worker.js');
                    }
                }
            });
        }
    </script>{/literal}

    {$adminFooter}
</body>

</html>
