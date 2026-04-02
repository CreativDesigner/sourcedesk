<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.NAME}</h1>

		<p style="text-align: justify;">{$l.INTRO}</p>

		<div class="checkbox">
			<label>
				<input type="checkbox" id="tl"> {$l.TL}
			</label>
		</div>

		<script>
		$("#tl").click(function(){
			if($(this).is(":checked")){
				$(".btn-success").prop("href", "?p=vcard_export&dl=1&tl=1");
				$(".btn-default").prop("href", "?p=vcard_export&dl=2&tl=1");
			} else {
				$(".btn-success").prop("href", "?p=vcard_export&dl=1");
				$(".btn-default").prop("href", "?p=vcard_export&dl=2");
			}
		});
		</script>

		<a href="?p=vcard_export&dl=1" target="_blank" class="btn btn-success">{$l.ZIP}</a> <a href="?p=vcard_export&dl=2" target="_blank" class="btn btn-default">{$l.VCF}</a>
	</div>
	<!-- /.col-lg-12 -->
</div>