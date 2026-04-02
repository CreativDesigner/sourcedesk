<div id="content">
	<div class="container">
        {if $ti.rating != -1}
        <div class="modal fade" id="ratingModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{$lang.TICKET.TY}</h4>
                    </div>
                    <div class="modal-body" style="text-align: justify;">
                        {$lang.TICKET.TYT}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.TICKET.NT}</button>
                        <a href="{$cfg.PAGEURL}testimonials/add" target="_blank" class="btn btn-primary">{$lang.TICKET.RU}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="badModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{$lang.TICKET.TY}</h4>
                    </div>
                    <div class="modal-body" style="text-align: justify;">
                        {$lang.TICKET.TYTN}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
                    </div>
                </div>
            </div>
        </div>
        {/if}

	    <h1>
            {$ti.subject|htmlentities} <small>{if $ti.status == 0}<span class="label label-warning">{$lang.TICKET.WAITING}</span>{else if $ti.status == 1}<span class="label label-warning">{$lang.TICKET.WORKING}</span>{else if $ti.status == 2}<span class="label label-success">{$lang.TICKET.ANSWERED}</span>{else}<span class="label label-default">{$lang.TICKET.CLOSED}</span>{/if}</small>
            {if $ti.rating != -1}
            <span class="pull-right">
                <a href="#" id="goodRating" style="color: {if $ti.rating == 1}green{else}grey{/if};"><i class="fa fa-smile-o"></i></a>
                <a href="#" id="badRating" style="color: {if $ti.rating == 2}red{else}grey{/if};"><i class="fa fa-frown-o"></i></a>

                <style>
                #goodRating:hover {
                    color: green !important;
                }

                #badRating:hover {
                    color: red !important;
                }
                </style>
                <script>
                $(document).ready(function(){
                    {if isset($ratingModal)}
                    $("#ratingModal").modal("toggle");
                    {else if isset($badModal)}
                    $("#badModal").modal("toggle");
                    {/if}

                    function rate(r){
                        $.post("", {
                            "rating": r,
                            "csrf_token": "{ct}",
                        }, function(r){
                            if(r == 1){
                                $("#badRating").css("color", "grey");
                                $("#goodRating").css("color", "green");
                                $("#ratingModal").modal("toggle");
                            } else if(r == 2){
                                $("#badRating").css("color", "red");
                                $("#goodRating").css("color", "grey");
                                $("#badModal").modal("toggle");
                            }
                        });
                    }

                    $("#goodRating").click(function(e){
                        e.preventDefault();
                        rate(1);
                    });

                    $("#badRating").click(function(e){
                        e.preventDefault();
                        rate(2);
                    });
                });
                </script>
            </span>
            {/if}
        </h1><hr />

        {if $ti.status != 3}
        <div class="alert alert-warning">{$lang.TICKET.CLOSE1} <a href="{$cfg.PAGEURL}ticket/{$pars.0}/{$pars.1}/close">{$lang.TICKET.CLOSE2}</a>{$lang.TICKET.CLOSE3}</div>
        {else}
        <div class="alert alert-warning">{$lang.TICKET.TCLOSED}</div>
        {/if}

        {if !empty($errormsg)}
        <div class="alert alert-danger">{$errormsg}</div>
        {elseif !empty($sucmsg)}
        <div class="alert alert-success">{$sucmsg}</div>
        {/if}

        {if $upgrades|count && !$ti.upgrade_id}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{$lang.TICKET.PREMIUM}</h5>
                <div class="row">
                    {foreach from=$upgrades item=ug}
                    <div class="col-md-{if $upgrades|count % 3 == 0}4{elseif $upgrades|count % 2 == 0}6{else}12{/if}" style="text-align: center;">
                        <i class="fa fa-{$ug.icon} fa-4x"></i>
                        
                        <h2 style="margin-top: 10px; margin-bottom: 20px;">{$ug.name|htmlentities}</h2>
                        <h4>{$ug.price_formatted}</h4>
                        
                        <a href="#" onclick="orderUpgrade({$ug.ID}, '{$ug.name|htmlentities}', '{$ug.price_formatted}', {if $ug.link}1{else}0{/if}); return false;" class="btn btn-primary btn-block">{$lang.TICKET.ORDER_PREMIUM}</a>
                        {if $ug.link}<a href="{$ug.link}" class="btn btn-default btn-block">{$lang.TICKET.PREMIUM_INFO}</a>{/if}
                    </div>
                    {/foreach}
                </div>
            </div>
        </div>

        <script>{literal}
        function orderUpgrade(id, name, price, info) {
            {/literal}
            {if !$logged_in}
            {literal}
            alert('{/literal}{$lang.TICKET.UPGRADE_GUEST}{literal}');
            {/literal}
            {else}
            {literal}
            if (info) {
                var msg = "{/literal}{$lang.TICKET.ORDER_WITH_INFO}{literal}";
            } else {
                var msg = "{/literal}{$lang.TICKET.ORDER_WITHOUT_INFO}{literal}";
            }

            msg = msg.replace("%u", name);
            msg = msg.replace("%p", price);

            if (confirm(msg)) {
                window.location = "?order_upgrade=" + id;
            }
            {/literal}
            {/if}
            {literal}
        }
        {/literal}</script><br />
        {elseif $ugn}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{$lang.TICKET.PREMIUM}</h5>
                {$lang.TICKET.IS_PREMIUM|replace:"%u":$ugn}
            </div>
        </div><br />
        {/if}

        <div class="card">
            <div class="card-body">
                <a href="#" id="openAnswerPanel"><h5 class="card-title" style="margin-bottom: -4px;">{$lang.TICKET.ANSWER}</h5></a>
            <div id="answerPanel" style="display: none;"><br />
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>{$lang.TICKET.TEXT}</label>
                        <textarea name="answer" class="form-control" style="resize: vertical; height: 150px; width: 100%;">{if isset($smarty.post.answer)}{$smarty.post.answer}{/if}</textarea>
                    </div>

                    <div class="form-group">
                        <label>{$lang.TICKET.ATTACHMENTS}</label>
                        <input type="file" name="attachments[]" multiple="multiple" class="form-control">
                    </div>

                    <input type="submit" class="btn btn-primary btn-block" value="{$lang.TICKET.SEND}">
                </form>
            </div>
            </div>
        </div>

        <script>
        $(document).ready(function(){
            var answer = 0;
            $("#openAnswerPanel").click(function(e){
                e.preventDefault();
                if(answer){
                    answer = 0;
                    $("#answerPanel").slideUp();
                } else {
                    answer = 1;
                    $("#answerPanel").slideDown();
                }
            });
        });
        </script>

        {foreach from=$a item=d}<br />
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{if $d.1}{$lang.TICKET.STAFF}{else}{$d.3}{/if} <span style="font-size: 8pt; font-weight: normal;">{$d.0}</span></h5>
                {$d.2|nl2br}

                {if count($d.4) > 0}<hr />
                {assign "i" "0"}
                {foreach from=$d.4 key=id item=at}
                {if $i != 0}<br />{/if}
                <i class="fa fa-paperclip"></i> <a href="{$cfg.PAGEURL}ticket/{$pars.0}/{$pars.1}/{$id}" target="_blank">{$at.0}</a> ({$at.1} KB)
                {assign "i" "1"}
                {/foreach}
                {/if}
            </div>
        </div>
        {/foreach}
	</div>
</div>