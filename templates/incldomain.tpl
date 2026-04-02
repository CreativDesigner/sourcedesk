<div id="content">
	<div class="container">
		<h1>{$lang.INCLDOMAIN.TITLE} <small>{$pName}</small></h1><hr>
        <p style="text-align: justify;">{$lang.INCLDOMAIN.INTRO}</p>
        
        <form method="POST" id="add_domain">
            <div id="add_domain_fail" style="display: none; margin-bottom: 10px;" class="alert alert-danger"></div>
        
            <div class="row">
                <div class="col-md-9">
                    <input type="text" name="sld" placeholder="{$lang.INCLDOMAIN.DOMAIN}" class="form-control input-lg" style="height: 46px;">
                </div>

                <div class="col-md-3">
                    <select name="tld" class="form-control input-lg" style="height: 46px;">
                        {foreach from=$tlds item=tld}
                        <option>.{$tld}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <input type="text" name="authcode" class="form-control" placeholder="{$lang.INCLDOMAIN.AUTHCODE}" style="margin-top: 10px;">

            <input type="submit" class="btn btn-primary btn-block" id="add_domain_btn" value="{$lang.INCLDOMAIN.REG}" style="margin-top: 10px;">
            <span id="add_domain_doing" style="display: none; font-size: 24px;"><center style="margin-top: 10px;"><i class="fa fa-spinner fa-pulse"></i> {$lang.INCLDOMAIN.PLEASEWAIT}</center></span>
        </form>

        <script>
        var doing = 0;

        $("#add_domain").submit(function(e) {
            e.preventDefault();

            if (doing) {
                return;
            }
            doing = 1;

            $("#add_domain_btn").hide();
            $("#add_domain_doing").show();
            
            $("#add_domain_fail").slideUp(function() {
                $("#add_domain_fail").html("");

                $.post("", $("#add_domain").serialize(), function(r) {
                    if (r == "ok") {
                        window.location = "{$cfg.PAGEURL}hosting/{$h.ID}";
                    } else {
                        doing = 0;
                        $("#add_domain_fail").html(r).slideDown();
                        $("#add_domain_btn").show();
                        $("#add_domain_doing").hide();
                    }
                });
            });
        });
        </script>
	</div>
</div>