<div class="container">
    <h1>{$lang.NAV.DASHBOARD} <span class="pull-right"><a href="{$cfg.PAGEURL}logout" data-toggle="tooltip" title="{$lang.NAV.LOGOUT}" data-placement="left"><i class="fa fa-sign-out"></i></a></span></h1><hr>

	<div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading"><i class="fa fa-user fa-fw"></i> {$user.name|htmlentities} <span class="pull-right">{if $cfg.CNR_PREFIX}{$cfg.CNR_PREFIX}{else}#{/if}{$user.ID}</span></div>
                <div class="panel-body">
                    {if $user.street}{$user.street|htmlentities} {$user.street_number|htmlentities}<br />{/if}
                    {if $user.city}{$user.postcode|htmlentities} {$user.city|htmlentities}<br />{/if}
                    {if $user.country_name}{$user.country_name|htmlentities}<br />{/if}
                    {if $user.street || $user.city || $user.country_name}<br />{/if}
                    {$lang.PROFILE.MAIL}: {$user.mail|htmlentities}
                    {if $user.telephone}<br />{$lang.INVOICE.TELEPHONE}: {$user.telephone|htmlentities}{/if}
                </div>
                <div class="panel-footer">
                    <a href="{$cfg.PAGEURL}profile" class="btn btn-primary btn-block"><i class="fa fa-pencil fa-fw"></i> {$lang.DASHBOARD.EDIT_DATA}</a>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading"><i class="fa fa-comments-o fa-fw"></i> {$lang.TICKETS.TITLE} <a href="{$cfg.PAGEURL}tickets/add" class="pull-right"><i class="fa fa-plus-square-o fa-fw"></i></a></div>
                <div class="panel-body"{if $tickets|@count} style="padding: 0;"{/if}>
                    {foreach from=$tickets item=ticket}
                    <div class="list-group" style="margin-bottom: 0;">
                        <a class="list-group-item" style="border-radius: 0; border-top: none; border-left: none; border-right: none;" href="{$ticket.url}">
                            <b>{$ticket.subject|htmlentities}</b><br />
                            {$ticket.t->getStatusStr()} - {$ticket.t->getLastAnswerStr()}
                        </a>
                    </div>
                    {foreachelse}
                    {$lang.DASHBOARD.NO_TICKETS}
                    {/foreach}
                </div>
                {if $tickets|@count}
                <div class="panel-footer" style="border-top: none;">
                    <a href="{$cfg.PAGEURL}tickets" class="btn btn-primary btn-block"><i class="fa fa-list fa-fw"></i> {$lang.DASHBOARD.SHOW_ALL}</a>
                </div>
                {/if}
            </div>

            <div class="panel panel-default">
                <div class="panel-heading"><i class="fa fa-phone fa-fw"></i> {$lang.PROFILE.TELEPHONE_PIN} <a href="#" class="pull-right" id="support_pin_link"><i class="fa fa-eye fa-fw"></i></a></div>
                <div class="panel-body" style="padding: 8px;">
                    <center>
                        <font style="font-size: 32px; filter: blur(10px);" id="support_pin">{$user.telephone_pin}</font>
                    </center>
                </div>

                <script>
                $("#support_pin_link").click(function(e) {
                    e.preventDefault();

                    var i = $(this).find("i");
                    var ct = $("#support_pin");

                    if (i.hasClass("fa-eye")) {
                        i.removeClass("fa-eye").addClass("fa-eye-slash");
                        ct.css("filter", "none");
                    } else {
                        i.removeClass("fa-eye-slash").addClass("fa-eye");
                        ct.css("filter", "blur(10px)");
                    }
                });
                </script>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-success">
                <div class="panel-heading"><i class="fa fa-file-text-o fa-fw"></i> {$lang.DASHBOARD.CONTRACTS}</div>
                <div class="panel-body"{if $contracts|@count} style="padding: 0;"{/if}>
                    {foreach from=$contracts item=contract}
                    <div class="list-group" style="margin-bottom: 0;">
                        <a class="list-group-item" style="border-radius: 0; border-top: none; border-left: none; border-right: none;" href="{$cfg.PAGEURL}hosting/{$contract.ID}">
                            <b>{$contract.name|htmlentities}</b><br />
                            {$contract.info}
                        </a>
                    </div>
                    {foreachelse}
                    {$lang.DASHBOARD.NO_CONTRACTS}
                    {/foreach}
                </div>
                {if $hadContracts}
                <div class="panel-footer" style="border-top: none;">
                    <a href="{$cfg.PAGEURL}products" class="btn btn-success btn-block"><i class="fa fa-list fa-fw"></i> {$lang.DASHBOARD.SHOW_ALL}</a>
                </div>
                {/if}
            </div>

            <div class="panel panel-warning">
                <div class="panel-heading"><i class="fa fa-globe fa-fw"></i> {$lang.DASHBOARD.DOMAINS}</div>
                <div class="panel-body"{if $domains|@count} style="padding: 0;"{/if}>
                    {foreach from=$domains item=domain}
                    <div class="list-group" style="margin-bottom: 0;">
                        <a class="list-group-item" style="border-radius: 0; border-top: none; border-left: none; border-right: none;" href="{$cfg.PAGEURL}domain/{$domain.domain|urlencode}">
                            <b>{$domain.domain|htmlentities}</b><br />
                            {$lang.DASHBOARD.EXPIRES} {$domain.expires}
                        </a>
                    </div>
                    {foreachelse}
                    {$lang.DASHBOARD.NO_DOMAINS}
                    {/foreach}
                </div>
                {if $domains|@count}
                <div class="panel-footer" style="border-top: none;">
                    <a href="{$cfg.PAGEURL}products" class="btn btn-warning btn-block"><i class="fa fa-list fa-fw"></i> {$lang.DASHBOARD.SHOW_ALL}</a>
                </div>
                {/if}
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading"><i class="fa fa-institution fa-fw"></i> {$lang.CREDIT.TITLE} <a href="{$cfg.PAGEURL}credit" class="pull-right"><i class="fa fa-plus-square-o fa-fw"></i></a></div>
                <div class="panel-body">
                    <center>
                        <font style="font-size: 32px;">{$user.credit_converted}</font>
                    </center>
                </div>
            </div>

            <div class="panel panel-danger">
                <div class="panel-heading"><i class="fa fa-legal fa-fw"></i> {$lang.DASHBOARD.OVERDUE}</div>
                <div class="panel-body">
                    {if $overdue.num == 1}
                    {$lang.DASHBOARD.OVERDUE_TEXT1|replace:"%a":$overdue.amount}
                    {else if $overdue.num}
                    {$lang.DASHBOARD.OVERDUE_TEXTX|replace:"%n":$overdue.num|replace:"%a":$overdue.amount}
                    {else}
                    {$lang.DASHBOARD.NO_OVERDUE}
                    {/if}
                </div>
                <div class="panel-footer" style="border-top: none;">
                    <a href="{$cfg.PAGEURL}invoices" class="btn btn-danger btn-block"><i class="fa fa-list fa-fw"></i> {$lang.DASHBOARD.SHOW_ALL}</a>
                </div>
            </div>

            <div class="panel panel-default" style="border-bottom: none;">
                <div class="panel-heading"><i class="fa fa-link fa-fw"></i> {$lang.DASHBOARD.MORE}</div>
                <div class="panel-body" style="padding: 0;">
                    <div class="list-group" style="margin-bottom: 0;">
                        <a class="list-group-item" style="border-radius: 0; border-top: none; border-left: none; border-right: none;" href="{$cfg.PAGEURL}projects">
                            {$lang.NAV.PROJECTS}
                        </a>
                    </div>
                    <div class="list-group" style="margin-bottom: 0;">
                        <a class="list-group-item" style="border-radius: 0; border-top: none; border-left: none; border-right: none;" href="{$cfg.PAGEURL}affiliate">
                            {$lang.NAV.AFFILIATE}
                        </a>
                    </div>
                    <div class="list-group" style="margin-bottom: 0;">
                        <a class="list-group-item" style="border-radius: 0; border-top: none; border-left: none; border-right: none;" href="{$cfg.PAGEURL}file">
                            {$lang.NAV.FILES}
                        </a>
                    </div>
                    <div class="list-group" style="margin-bottom: 0; border-bottom: none;">
                        <a class="list-group-item" style="border-radius: 0 0 3px 3px; border-left: none; border-right: none; border-top: none;" href="{$cfg.PAGEURL}mails">
                            {$lang.NAV.MAILS}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>