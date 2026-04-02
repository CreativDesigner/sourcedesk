<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.NAME}</h1>

        <div id="sdstat" class="alert alert-info" style="display: none;"></div>

		<p style="text-align: justify;">{$l.INTRO}</p>

        <a href="#" id="sync_clients" class="btn btn-default sdact">{$l.SYNCC}</a> <a href="#" id="sync_invoices" class="btn btn-default sdact">{$l.SYNCI}</a>

        <script>
        var doing = 0;
        $(".sdact").click(function(e) {
            e.preventDefault();

            if (doing) {
                return false;
            }
            doing = 1;

            var act = $(this).attr("id");
            $(".sdact").attr("disabled", true); 

            $("#sdstat").slideUp(function() {
                $("#sdstat").html('<i class="fa fa-spinner fa-pulse"></i> {$l.PW}').slideDown();

                $.get("?p=fastbill&" + act + "=1", function (r) {
                    doing = 0;
                    $(".sdact").attr("disabled", false);
                    $("#sdstat").html(r);
                });
            });
        });
        </script>
	</div>
	<!-- /.col-lg-12 -->
</div>