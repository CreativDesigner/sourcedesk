<div id="content">
    <div class="container">
        <h1>{$lang.ABUSE.TITLE} <small>{$abuse.subject|htmlentities}</small></h1><hr />

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body" style="margin-bottom: -25px;">
                    <h5 class="card-title">
                        {$lang.ABUSE.REPORT}
                    </h5>
                            <label>
                            {$lang.ABUSE.RID}
                            </label><br />
                                #{$abuse.ID}<br /><br />
                                
                            <label>
                                {$lang.ABUSE.STATUS}
                            </label><br />
                                <span class="label label-{if $abuse.status == "open"}warning{else}success{/if}">
                                    {if $abuse.status == "open"}{$lang.ABUSE.OPEN}{else}{$lang.ABUSE.CLOSED}{/if}
                                </span>
                                <br /><br />

                            <label>
                                {$lang.ABUSE.TIME}
                            </label><br />
                            {dfo d=$abuse.time}<br /><br />

                        {if $abuse.deadline != "0000-00-00 00:00:00"}
                        <div style="color: red;">
                            <label>
                                <i class="fa fa-exclamation-triangle"></i> {$lang.ABUSE.DEADLINE}
                            </label><br />
                            {dfo d=$abuse.deadline}<br /><br />
                        </div>
                        {/if}

                        {if $service}
                            <label>
                                 {$lang.ABUSE.SERVICE}
                            </label><br />
                            {$service|htmlentities}<br /><br />
                        {/if}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card"><div class="card-body">
                    <h5 class="card-title">
                        {$lang.ABUSE.MESSAGES}
                    </h5>
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
                </div></div>
            </div>
        </div>
    </div>
</div><br />