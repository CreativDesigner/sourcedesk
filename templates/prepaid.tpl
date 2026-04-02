<div id="content">
	<div class="container">
		<h1>{$lang.PREPAID.TITLE}</h1><hr>
        {if $invoice}<div class="alert alert-success">{$lang.PREPAID.SUCCESS}</div>{else}<p>{$lang.PREPAID.INTRO}</p>{/if}

        <center>
        <div style="max-width: 550px; margin-top: 20px;">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="40%">{$lang.PREPAID.PRODUCT}</th>
                    <td><a href="{$cfg.PAGEURL}hosting/{$cid}">#{$cid}</a></td>
                </tr>

                {if !$invoice}
                <tr>
                    <th>{$lang.PREPAID.OLDD}</th>
                    <td>{$oldd}</td>
                </tr>
                {/if}

                <tr>
                    <th>{$lang.PREPAID.EXTENDING}</th>
                    <td>{$pp.0} {$lang.PREPAID.DAYS}</td>
                </tr>

                <tr>
                    <th>{$lang.PREPAID.NEWD}</th>
                    <td>{$newd}</td>
                </tr>

                <tr>
                    <th>{$lang.PREPAID.COSTS}</th>
                    <td>{infix n={nfo i=$pp.1}}</td>
                </tr>

                {if $invoice}
                <tr>
                    <th>{$lang.PREPAID.INVOICE}</th>
                    <td><a href="{$invoice_link}" target="_blank">{$invoice}</a></td>
                </tr>
                {/if}
            </table>
        </div>
        {if !$invoice}
        <form method="POST">
        <input type="submit" class="btn btn-primary btn-block" value="{$lang.PREPAID.BUY}">
        <input type="hidden" name="do" value="now">
        </form>
        {/if}
        </div>
        </center>
	</div>
</div><br />