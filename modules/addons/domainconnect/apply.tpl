<div class="container">
    <h1>{$addonlang.TITLE}</h1><hr>

    <div id="consent">
        {if $warn_phishing}
        <div class="alert alert-warning">{$addonlang.PHISHING_WARNING}</div>
        {/if}

        {if $sp_logo}
        <center><img src="{$sp_logo}" alt="{$service_provider}" title="{$service_provider}"></center><br />
        {/if}

        <center><p style="max-width: 70%;">{$addonlang.INTRO|replace:"%s":$service_provider|replace:"%d":$domain}</p></center><br />

        <center>
            <a href="#" class="btn btn-success" id="dc_confirm">{$addonlang.CONFIRM}</a>
            <a href="#" class="btn btn-danger" id="dc_cancel">{$addonlang.CANCEL}</a>
        </center>

        {if $records|@count}<br />
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                {foreach from=$records item=record}
                    <tr>
                        {foreach from=$record item=column}
                        <td>{$column|htmlentities}</td>
                        {/foreach}
                    </tr>
                {/foreach}
            </table>
        </div>
        {/if}
    </div>

    <div id="doing" style="display: none;">
        <i class="fa fa-spinner fa-spin"></i> {$addonlang.PLEASEWAIT}
    </div>

    <script>
    var redirect_url = "{$redirect_url}";

    $("#dc_confirm").click(function(e) {
        e.preventDefault();

        $("#consent").slideUp(function() {
            $("#doing").slideDown();
        });

        $.post("", {
            "apply": true,
            "csrf_token": "{ct}"
        }, function() {
            if (redirect_url) {
                window.location = redirect_url + "success=1";
            } else {
                window.close();
            }
        });
    });

    $("#dc_cancel").click(function(e) {
        e.preventDefault();

        if (redirect_url) {
            window.location = redirect_url + "error=access_denied";
        } else {
            window.close();
        }
    });
    </script>
</div><br />
