<div id="content">
    <div class="container">
        <h1>{$lang.ABUSE.TITLE} <small>{$abuse.subject|htmlentities}</small></h1><hr />

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        {$lang.ABUSE.REPORT}
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <label class="col-xs-5 control-label">
                                <span class="pull-right">
                                    {$lang.ABUSE.RID}
                                </span>
                            </label>
                            <div class="col-xs-7 control-label">
                                #{$abuse.ID}
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-xs-5 control-label">
                                <span class="pull-right">
                                    {$lang.ABUSE.STATUS}
                                </span>
                            </label>
                            <div class="col-xs-7 control-label">
                                <span class="label label-{if $abuse.status == "open"}warning{else}success{/if}">
                                    {if $abuse.status == "open"}{$lang.ABUSE.OPEN}{else}{$lang.ABUSE.CLOSED}{/if}
                                </span>
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-xs-5 control-label">
                                <span class="pull-right">
                                    {$lang.ABUSE.TIME}
                                </span>
                            </label>
                            <div class="col-xs-7 control-label">
                                {dfo d=$abuse.time}
                            </div>
                        </div>

                        {if $abuse.deadline != "0000-00-00 00:00:00"}
                        <div class="row" style="color: red;">
                            <label class="col-xs-5 control-label">
                                <span class="pull-right">
                                    <i class="fa fa-exclamation-triangle"></i> {$lang.ABUSE.DEADLINE}
                                </span>
                            </label>
                            <div class="col-xs-7 control-label">
                                {dfo d=$abuse.deadline}
                            </div>
                        </div>
                        {/if}

                        {if $service}
                        <div class="row">
                            <label class="col-xs-5 control-label">
                                <span class="pull-right">
                                    {$lang.ABUSE.SERVICE}
                                </span>
                            </label>
                            <div class="col-xs-7 control-label">
                                {$service|htmlentities}
                            </div>
                        </div>
                        {/if}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {$lang.ABUSE.MESSAGES}
                    </div>
                    <div class="panel-body">
                        <form method="POST">
                            <textarea name="answer" class="form-control" style="width: 100%; height: 150px; resize: vertical; margin-bottom: 10px;"></textarea>
                            <input type="submit" class="btn btn-primary btn-block" value="{$lang.ABUSE.ANSWER}">
                        </form>
                        <hr />

                        {foreach from=$messages item=msg key=i}
                            <h3>{$lang.ABUSE.{$msg.author|strtoupper}} <small>{dfo d=$msg.time}</small></h3>

                            {$msg.text|htmlentities|nl2br}

                            {if $i + 1 < $messages|count}
                            <hr />
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div><br />