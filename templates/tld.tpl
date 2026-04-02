<div id="content">
	<div class="container">
		<h1>.{$tld}</h1><hr>

        {if $text}
        <div class="panel panel-default">
        <div class="panel-body">
        <style>
        .float-right {
            float: right;
        }
        </style>
        <p>{$text}</p>
        <small>{$lang.TLD.SOURCE}: <a href="{$source}" target="_blank">{$source}</a></small>
        </div>
        </div>
        {/if}
        
        <form method="POST" id="tld_form" action="{$cfg.PAGEURL}domains"><div class="input-group input-group-lg input-group-box">
            <input type="text" class="form-control" placeholder="{$lang.TLD.SEARCH}" name="domain">
            <span class="input-group-addon">
                .{$tld}
            </span>
            <span class="input-group-btn">
                <button disabled="" type="submit" class="btn btn-primary">{$lang.DOMAINS.SEARCH}</button>
            </span>
        </div></form><br />

        <script>
        $("#tld_form").submit(function() {
            $("#tld_form").find("[type=text]").val($("#tld_form").find("[type=text]").val() + ".{$tld}");
        });

        $(document).ready(function() {
            $("#tld_form").find("[type=submit]").prop("disabled", false);
        });
        </script>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="40%">{$lang.TLD.RUNTIME}</th>
                    <td>{$d.period} {if $d.period == 1}{$lang.TLD.YEAR}{else}{$lang.TLD.YEARS}{/if}</td>
                </tr>

                <tr>
                    <th>{$lang.TLD.REG}</th>
                    <td>{$reg}</td>
                </tr>

                <tr>
                    <th>{$lang.TLD.KK}</th>
                    <td>{$kk}</td>
                </tr>

                <tr>
                    <th>{$lang.TLD.RENEW}</th>
                    <td>{$renew}</td>
                </tr>

                <tr>
                    <th>{$lang.TLD.TRADE}</th>
                    <td>{$trade}</td>
                </tr>

                <tr>
                    <th>{$lang.TLD.PRIVACY}</th>
                    <td>{$privacy}</td>
                </tr>

                <tr>
                    <th>{$lang.TLD.DOMAIN_LOCK}</th>
                    <td>{if $d.domain_lock}{$lang.TLD.AVAILABLE}{else}{$lang.TLD.NOTAVAILABLE}{/if}</td>
                </tr>
            </table>
        </div>
	</div>
</div><br />