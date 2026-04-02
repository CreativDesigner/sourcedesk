<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$addonlang.NAME}</h1>

        <div id="doing" style="display: none;">
            {$addonlang.DOING}<br /><br />
            {$addonlang.STEP1}: <span><i class="fa fa-spinner fa-spin" id="step1"></i></span><br />

            <span id="wtd"></span>

            <br /><a href="?p=whmcs_import" id="restart" class="btn btn-warning btn-block" style="display: none;">{$addonlang.RESTART}</a>

            <div id="finish" style="display: none;" class="alert alert-success">{$addonlang.FINISH}</div>
        </div>

		<p id="intro">{$addonlang.INTRO}</p>

        <form class="form-horizontal" id="import_form" style="margin-top: 20px;">
            <input style="opacity: 0;position: absolute;">
            <input type="password" autocomplete="new-password" style="display: none;">

            <div class="form-group">
                <label for="db_host" class="col-sm-2 control-label">{$addonlang.DBHOST}</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="db_host" placeholder="localhost" value="{if isset($smarty.session.whmcs_import_db_host)}{$smarty.session.whmcs_import_db_host|strip_tags}{else}localhost{/if}">
                </div>
            </div>

            <div class="form-group">
                <label for="db_host" class="col-sm-2 control-label">{$addonlang.DBUSER}</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="db_user" placeholder="whmcs" value="{if isset($smarty.session.whmcs_import_db_user)}{$smarty.session.whmcs_import_db_user|strip_tags}{/if}">
                </div>
            </div>

            <div class="form-group">
                <label for="db_host" class="col-sm-2 control-label">{$addonlang.DBPASSWORD}</label>
                <div class="col-sm-10">
                    <input type="password" class="form-control" id="db_password" placeholder="E57k3BYT8r" value="{if isset($smarty.session.whmcs_import_db_password)}{$smarty.session.whmcs_import_db_password|strip_tags}{/if}">
                </div>
            </div>

            <div class="form-group">
                <label for="db_host" class="col-sm-2 control-label">{$addonlang.DBNAME}</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="db_name" placeholder="whmcs" value="{if isset($smarty.session.whmcs_import_db_name)}{$smarty.session.whmcs_import_db_name|strip_tags}{/if}">
                </div>
            </div>

            <div class="form-group">
                <label for="db_host" class="col-sm-2 control-label">{$addonlang.WTI}</label>
                <div class="col-sm-10">
                    {foreach from=$addonlang.IMPORT key=k item=n}
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" class="import" value="{$k}" checked="checked"{if $k == "clients"} disabled="disabled"{/if} />
                            {$n}
                        </label>
                    </div>
                    {/foreach}
                </div>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$addonlang.DO}" />
        </form>

        <script type="text/javascript">
        function doImport() {
            var did = 0;

            $(".import").each(function() {
                if ($(this).is(":checked")) {
                    did = 1;
                    $(this).prop("checked", false);
                    $("#wtd").append("<br />" + $(this).parent().text().trim() + ": <span><i class='fa fa-spinner fa-spin' id='" + $(this).val() + "'></i></span>");

                    var id = "#" + $(this).val();

                    $.post("", {
                        "step": $(this).val(),
                        "csrf_token": "{ct}",
                        "db_host": $("#db_host").val(),
                        "db_user": $("#db_user").val(),
                        "db_password": $("#db_password").val(),
                        "db_name": $("#db_name").val(),
                    }, function(r) {
                        if (r.substr(0, 2) == "ok") {
                            $(id).removeClass("fa-spinner fa-spin").addClass("fa-check").parent().css("color", "green").append(" " + r.substr(2));
                            doImport();
                        } else {
                            $(id).removeClass("fa-spinner fa-spin").addClass("fa-times").parent().css("color", "red").append(" " + r);
                            $("#wtd").append("<br />");
                            dc();
                            $("#restart").show();
                        }
                    });

                    return false;
                }
            });

            if (!did) {
                dc();
                $("#wtd").append("<br />");
                $("#finish").slideDown();
            }
        }

        $("#import_form").submit(function(e) {
            e.preventDefault();
            dnc();

            $("#intro").slideUp();
            $("#import_form").slideUp(function() {
                $("#doing").slideDown();

                $.post("", {
                    "step": "checkDb",
                    "csrf_token": "{ct}",
                    "db_host": $("#db_host").val(),
                    "db_user": $("#db_user").val(),
                    "db_password": $("#db_password").val(),
                    "db_name": $("#db_name").val(),
                }, function(r) {
                    if (r != "ok") {
                        dc();
                        $("#step1").removeClass("fa-spinner fa-spin").addClass("fa-times").parent().css("color", "red").append(" " + r);
                        $("#restart").show();
                    } else {
                        $("#step1").removeClass("fa-spinner fa-spin").addClass("fa-check").parent().css("color", "green");
                        doImport();
                    }
                });
            });
        });

        function dnc() {
            $(window).bind('beforeunload', function() {
                return '{$addonlang.CLOSING}';
            });
        }

        function dc() {
            $(window).unbind('beforeunload');
        }
        </script>
	</div>
	<!-- /.col-lg-12 -->
</div>