<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.SYSTEMUPDATE.TITLE}</h1>

		{if isset($update_hint)}{$update_hint}{/if}

		<form method="POST">
			<div class="row">
				<div class="col-lg-2 col-lg-offset-4 col-md-4 col-md-offset-2 col-sm-6 col-xs-6">
					<div class="panel panel-default">
						<div class="panel-heading" style="text-align: right;"><b>{$lang.SYSTEMUPDATE.YOUR_VERSION}</b></div>
						<div class="panel-body" style="text-align: right;"><h1 style="margin: 0;">{$cfg.VERSION|htmlentities}</h1></div>
					</div>
				</div>

				<div class="col-lg-2 col-md-4 col-sm-6 col-xs-6">
					<div class="panel panel-primary">
						<div class="panel-heading"><b>{$lang.SYSTEMUPDATE.CURRENT_VERSION}</b></div>
						<div class="panel-body"><h1 style="margin: 0;">{$cfg.ACTUAL_VERSION|htmlentities}</h1></div>
					</div>
				</div>
			</div>

			<center>
				{if $stage == "0"}
				<a href="#" id="auto_update" class="btn btn-primary">{$lang.SYSTEMUPDATE.DO_UPDATE}</a>
				<a href="#" id="manual_update" class="btn btn-info">{$lang.SYSTEMUPDATE.MANUAL_UPDATE}</a>
				{/if}
				<input type="submit" name="actual_version" value="{$lang.SYSTEMUPDATE.GET_ISSUE}" class="btn btn-default" />
				<a href="#" id="micropatch" class="btn btn-default">{$lang.SYSTEMUPDATE.MICROPATCH}</a>
			</center>
		</form>
	</div>
</div>

<script type="text/javascript">
var doing = 0;

$("#micropatch").click(function(e) {
	e.preventDefault();

	if (doing) {
		return;
	}
	doing = 1;

	$(window).bind('beforeunload', function() {
		return '{$lang.SYSTEMUPDATE.WARNING}';
	});

	$("#update_hint").removeClass("alert-warning").addClass("alert-info").html("<b>{$lang.SYSTEMUPDATE.DONOTCLOSE}</b> {$lang.SYSTEMUPDATE.DOING}<br /><br />{$lang.SYSTEMUPDATE.CURRENTLEVEL}: <span id='mpstep1'><i class='fa fa-spinner fa-spin'></i></span><br />{$lang.SYSTEMUPDATE.AVAILABLELEVEL}: <span id='mpstep2'><i class='fa fa-ellipsis-h'></i></span><br />{$lang.SYSTEMUPDATE.UPGRADINGLEVEL}: <span id='mpstep3'><i class='fa fa-ellipsis-h'></i></span>");

	$.post("?p=update", {
		"mpstep": "1",
		"csrf_token": "{ct}",
	}, function(r) {
		$("#mpstep1").html(r);
		$("#mpstep2").find("i").removeClass("fa-ellipsis-h").addClass("fa-spinner fa-spin");
	
		$.post("?p=update", {
			"mpstep": "2",
			"csrf_token": "{ct}",
		}, function(r) {
			$("#mpstep2").html(r);
			$("#mpstep3").find("i").removeClass("fa-ellipsis-h").addClass("fa-spinner fa-spin");
	
			$.post("?p=update", {
				"mpstep": "3",
				"csrf_token": "{ct}",
			}, function(r) {
				doing = 0;
				$(window).unbind('beforeunload');

				if (r == "ok") {
					$("#mpstep3").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");
				} else {
					$("#mpstep3").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
				}
			});									
		});
	});
});

{if $stage == "0"}
$("#auto_update").click(function(e) {
	e.preventDefault();

	if (doing) {
		return;
	}

	if (confirm("{$lang.SYSTEMUPDATE.CONFIRMUPD}")) { 
		doing = 1;

		$(window).bind('beforeunload', function() {
			return '{$lang.SYSTEMUPDATE.WARNING}';
		});

		$("#update_hint").removeClass("alert-warning").addClass("alert-info").html("<b>{$lang.SYSTEMUPDATE.DONOTCLOSE}</b> {$lang.SYSTEMUPDATE.DOING}<br /><br />{$lang.SYSTEMUPDATE.DOWNLOADING}: <span id='step1'><i class='fa fa-spinner fa-spin'></i></span><br />{$lang.SYSTEMUPDATE.VERIFYING}: <span id='step2'><i class='fa fa-ellipsis-h'></i></span><br />{$lang.SYSTEMUPDATE.UNZIPPING}: <span id='step3'><i class='fa fa-ellipsis-h'></i></span><br />{$lang.SYSTEMUPDATE.RIGHTS}: <span id='step4'><i class='fa fa-ellipsis-h'></i></span><br />{$lang.SYSTEMUPDATE.CHANGES}: <span id='step5'><i class='fa fa-ellipsis-h'></i></span>");

		$.post("?p=update", {
			"step": "1",
			"csrf_token": "{ct}",
		}, function(r) {
			if (r == "ok") {
				$("#step1").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");
				$("#step2").find("i").removeClass("fa-ellipsis-h").addClass("fa-spinner fa-spin");
		
				$.post("?p=update", {
					"step": "2",
					"csrf_token": "{ct}",
				}, function(r) {
					if (r == "ok") {
						$("#step2").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");
						$("#step3").find("i").removeClass("fa-ellipsis-h").addClass("fa-spinner fa-spin");
		
						$.post("?p=update", {
							"step": "3",
							"csrf_token": "{ct}",
						}, function(r) {
							if (r == "ok") {
								$("#step3").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");
								$("#step4").find("i").removeClass("fa-ellipsis-h").addClass("fa-spinner fa-spin");

								$.post("?p=update", {
									"step": "4",
									"csrf_token": "{ct}",
								}, function(r) {
									if (r == "ok") {
										$("#step4").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");
										$("#step5").find("i").removeClass("fa-ellipsis-h").addClass("fa-spinner fa-spin");

										$.post("?p=update", {
											"step": "5",
											"csrf_token": "{ct}",
										}, function(r) {
											if (r == "ok") {
												$("#step5").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");

												$(window).unbind('beforeunload');
												location.reload();								
											} else {
												$("#step5").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
												doing = 0;
												$(window).unbind('beforeunload');
											}
										});									
									} else {
										$("#step4").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
										doing = 0;
										$(window).unbind('beforeunload');
									}
								});
							} else {
								$("#step3").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
								doing = 0;
								$(window).unbind('beforeunload');
							}
						});
					} else {
						$("#step2").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
						doing = 0;
						$(window).unbind('beforeunload');
					}
				});	
			} else {
				$("#step1").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
				doing = 0;
				$(window).unbind('beforeunload');
			}
		});
	}
});

$("#manual_update").click(function(e) {
	e.preventDefault();

	if (doing) {
		return;
	}
	doing = 1;

	$("#update_hint").removeClass("alert-warning").addClass("alert-info").html("{$lang.SYSTEMUPDATE.DOING2}<br /><br />{$lang.SYSTEMUPDATE.DOWNLOADING}: <span id='step1'><i class='fa fa-spinner fa-spin'></i></span><br />{$lang.SYSTEMUPDATE.VERIFYING}: <span id='step2'><i class='fa fa-ellipsis-h'></i></span>");

	$.post("?p=update", {
		"step": "1",
		"manual": "1",
		"csrf_token": "{ct}",
	}, function(r) {
		if (r == "ok") {
			$("#step1").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");
			$("#step2").find("i").removeClass("fa-ellipsis-h").addClass("fa-spinner fa-spin");
	
			$.post("?p=update", {
				"step": "2",
				"manual": "1",
				"csrf_token": "{ct}",
			}, function(r) {
				if (r == "ok") {
					$("#step2").css("color", "green").find("i").removeClass("fa-spinner fa-spin").addClass("fa-check");
			
					$("#update_hint").html($("#update_hint").html() + "<br /><br />{$lang.SYSTEMUPDATE.MANDOWN}<br /><br /><a href='?p=update&download=1' onclick='if (confirm(\"{$lang.SYSTEMUPDATE.CONFIRMUPD}\")) { $(this).hide(); return true; } else { return false; }' target='_blank' id='mandown' class='btn btn-primary'>{$lang.SYSTEMUPDATE.DP}</a> <a href='?p=update' class='btn btn-default'>{$lang.SYSTEMUPDATE.CHECK}</a>");
				} else {
					$("#step2").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
					doing = 0;
				}
			});	
		} else {
			$("#step1").css("color", "red").find("i").removeClass("fa-spinner fa-spin").addClass("fa-times");
			doing = 0;
		}
	});
});
{/if}
</script>