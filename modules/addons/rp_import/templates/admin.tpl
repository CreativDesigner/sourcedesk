<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$addonlang.NAME}</h1>

        {if !empty($suc)}<div class="alert alert-success">{$suc}</div>{/if}
        {if !empty($err)}<div class="alert alert-danger">{$err}</div>{/if}

		<p id="intro">{$addonlang.INTRO}</p>

        <form class="form-horizontal" style="margin-top: 20px;" method="POST" enctype="multipart/form-data">
            <input style="opacity: 0;position: absolute;">
            <input type="password" autocomplete="new-password" style="display: none;">

            <div class="form-group">
                <label for="csv" class="col-sm-2 control-label">{$addonlang.CSV}</label>
                <div class="col-sm-10">
                    <input type="file" class="form-control" name="csv" id="csv">
                </div>
            </div>

            <input type="submit" class="btn btn-primary btn-block" id="rp_import" value="{$addonlang.DO}" />
        </form>

        <script>
        $("#rp_import").click(function() {
            $("#page-wrapper").block();
        });
        </script>
	</div>
	<!-- /.col-lg-12 -->
</div>