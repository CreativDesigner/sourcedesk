<div id="content">
	<div class="container">
		<h1>{$lang.UPGRADE.TITLE}</h1><hr>

        <p>{$lang.UPGRADE.INTRO}</p>

        {if !empty($suc)}
        <div class="alert alert-success">{$lang.UPGRADE.SUC}</div>
        {else}
        <br />
        <center>
            <div style="max-width: 700px;">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th colspan="2">{$lang.UPGRADE.NEW}</th>
                        </tr>

                        {foreach from=$changes key=k item=v}
                        <tr>
                            <td width="50%">{$k|htmlentities}</td>
                            <td>{$v|htmlentities}</td>
                        </tr>
                        {/foreach}

                        <tr>
                            <th colspan="2">{$lang.UPGRADE.COSTS}</th>
                        </tr>

                        <tr>
                            <td>{$lang.UPGRADE.ONETIME}</td>
                            <td>{infix n={nfo i=$addcost}}</td>
                        </tr>

                        <tr>
                            <td>{$lang.UPGRADE.RECURRING}</td>
                            <td>{infix n={nfo i=$addcost_recur}}</td>
                        </tr>

                        <tr>
                            <th>{$lang.UPGRADE.DUETODAY}</td>
                            <th>{infix n={nfo i=$due_now}}</th>
                        </tr>
                    </table>
                </div>

                {if $can_pay}
                <form method="POST">
                    <input type="hidden" name="apply" value="now">
                    {foreach from=$smarty.post.cf key=k item=v}
                    <input type="hidden" name="cf[{$k|htmlentities}]" value="{$v|htmlentities}">
                    {/foreach}
                    <input type="submit" class="btn btn-primary btn-block" value="{$lang.UPGRADE.DOIT}">
                </form>
                {else}
                <div class="alert alert-warning">{$lang.UPGRADE.NEC}</div>
                {/if}
            </div>
        </center>
        {/if}
    </div>
</div>