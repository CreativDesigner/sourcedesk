function saveNotes() {
    $.ajax({
        url : './?p=ajax',
        data : { action : 'save_notes', text : $("#myNotes").val(), csrf_token: $("#csrf_token").val() },
        dataType : 'JSON',
        type : 'POST',
        cache: false,
        success : function(succ) {
            saveNotesResponse(succ.responseText);
        },
        error : function(err) {
            saveNotesResponse(err.responseText);
        }
    });
}

function check_all (state) {
    $('.checkbox').prop('checked', state);
}

function toggle () {
    var state = true;

    $('.checkbox').each(function (i){
        if(!$(this).prop('checked')){
            state = false;
            return false;
        }
    });

    $('#checkall').prop('checked', state);
}

$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip({
        placement : 'top'
    });

    $('[data-toggle="tooltip_right"]').tooltip({
        placement : 'right',
        width: '100',
    });

    $('[show-lang]').click(function(e) {
        e.preventDefault();
        $('[show-lang]').removeClass('active');
        $(this).addClass('active');
        $('[is-lang]').hide();
        $('[is-lang=' + $(this).attr("show-lang") + ']').show();
    });

    $('[show-lang2]').click(function(e) {
        e.preventDefault();
        $('[show-lang2]').removeClass('active');
        $(this).addClass('active');
        $('[is-lang2]').hide();
        $('[is-lang2=' + $(this).attr("show-lang2") + ']').show();
    });
});

function alert(txt) {
    swal({   
        title: "",
        text: txt,
        type: 'info',
        confirmButtonText: 'Schließen',
    });
}

var isShortcut = $(".shortcut").hasClass('shortcut-active');

function shortcut() {
    var pars = window.location.search.replace("?", "");
    
    $.post("./?p=ajax", {action: "toggle_shortcut", parameters: pars, csrf_token: $("#csrf_token").val()}, function (response) {
        if(response !== "true" && response !== "false"){
            alert(response);
        } else {
            isShortcut = response == "true";
            mark_shortcut(response == "true");
        }
    });
}

function mark_shortcut(status) {
    if(status) $('#shortcut_icon').removeClass('fa-star-o').addClass('fa-star').css('color', '#EAC117');
    else $('#shortcut_icon').addClass('fa-star-o').removeClass('fa-star').css('color', '');
}

$(".shortcut").mouseover(function () {
    if(!isShortcut) mark_shortcut(!isShortcut);
});

$(".shortcut").mouseleave(function () {
    if(!isShortcut) mark_shortcut(isShortcut);
});

function delete_shortcut(id) {
    $.post("./?p=ajax", {action: "delete_shortcut", id: id, csrf_token: $("#csrf_token").val()});
}

function edit_shortcut(id) {
    $("#shortcut-" + id + "-edit-link").hide();
    $("#shortcut-" + id + "-remove-link").hide();
    $("#shortcut-" + id).hide();
    $("#shortcut-" + id + "-edit").show();
}

function change_shortcut(id) {
    var newText = $("#shortcut-" + id + "-edit").val();
    $.post("./?p=ajax", {action: "change_shortcut", id: id, text: newText, csrf_token: $("#csrf_token").val()});
    $("#shortcut-" + id).html(newText);

    $("#shortcut-" + id + "-edit-link").show();
    $("#shortcut-" + id + "-remove-link").show();
    $("#shortcut-" + id).show();
    $("#shortcut-" + id + "-edit").hide();
}

function hideSidebar(){
    document.cookie="admin_sidebar=off";
    $('.sidebar-nav').addClass('sidebar-nav-hidden');
    $('#page-wrapper').addClass('page-wrapper-hidden');
    $('.navbar-toggle').addClass('navbar-toggle-hidden');
    $('#mainnav').hide();
    $('#show-sidebar').show().click(function(e){
        e.preventDefault();
        $('#page-wrapper').removeClass('page-wrapper-hidden');
        $('.sidebar-nav').removeClass('sidebar-nav-hidden');
        $('.navbar-toggle').removeClass('navbar-toggle-hidden');
        $('#show-sidebar').hide().off("click");
        $('#mainnav').show();
        document.cookie="admin_sidebar=on";
    });
}

$(document).ready(function() {
    if(typeof hide_sidebar !== 'undefined')
        hideSidebar();
});

$('.datetimepicker').datetimepicker({
    "format": 'DD.MM.YYYY HH:mm:ss',
    "locale": "de",
});
$('.datepicker').datetimepicker({
    "format": 'DD.MM.YYYY',
    "locale": "de",
});

$('.online_status').click(function(e) {
    e.preventDefault();
    var status = $(this).data("status");
    
    $(".change_status").hide();
    $(".status_changing").show();

    $.post('?p=ajax', {
        "action": "online_status",
        "status": status,
        csrf_token: $("#csrf_token").val(),
    }, function(r) {
        if(r == "red" || r == "green" || r == "orange") {
            $("#status_bull").css('color', r);
            $(".change_status").show();
            $(".status_changing").hide();
        }
    });
});

$('#user_logout').click(function(e) {
    e.preventDefault();
    $.post('?p=ajax', {
        "action": "user_logout",
        csrf_token: $("#csrf_token").val(),
    }, function(r) {
        $("#user_session").slideUp();
    });
});

var ajaxModalLabel = $("#ajaxModalLabel").html();
var ajaxModalContent = $("#ajaxModalContent").html();

$(".invoiceDetails").click(function(e){
    e.preventDefault();
    var id = $(this).data("id");
    $('#ajaxModal').modal('show');
    
    $.post("?p=ajax", {
        "action": "invoice_details",
        "invoiceid": id,
        csrf_token: $("#csrf_token").val(),
    }, function(r){
        r = $.parseJSON(r);
        $("#ajaxModalLabel").html(r[0]);
        $("#ajaxModalContent").html(r[1]);
    });
});

$('#ajaxModal').on('hidden.bs.modal', function (e) {
    $("#ajaxModalLabel").html(ajaxModalLabel);
    $("#ajaxModalContent").html(ajaxModalContent);
});

$(".widget_link").click(function(e){
    e.preventDefault();
    var w = $(this).data("widget");

    var b = $("#widget-body-" + w);
    if(b.is(":visible")){
        b.slideUp();
        $(this).find("i").removeClass("fa-caret-down").addClass("fa-caret-up");
        $.post("", {
            "hide": w,
            csrf_token: $("#csrf_token").val(),
        });
    } else {
        b.slideDown();
        $(this).find("i").addClass("fa-caret-down").removeClass("fa-caret-up");
        $.post("", {
            "show": w,
            csrf_token: $("#csrf_token").val(),
        });
    }
});

(function ( $ ) {
    $.fn.feedback = function(success, fail) {
    	self=$(this);
		self.find('.dropdown-menu-form').on('click', function(e){e.stopPropagation()})

		self.find('.screenshot').on('click', function(){
			self.find('.cam').removeClass('fa-camera fa-check').addClass('fa-refresh fa-spin');
			html2canvas($(document.body), {
				onrendered: function(canvas) {
					self.find('.screen-uri').val(canvas.toDataURL("image/png"));
					self.find('.cam').removeClass('fa-refresh fa-spin').addClass('fa-check');
				}
			});
		});

		self.find('.do-close').on('click', function(){
			self.find('.dropdown-toggle').dropdown('toggle');
			self.find('.reported, .failed').hide();
			self.find('.report').show();
			self.find('.cam').removeClass('fa-check').addClass('fa-camera');
		    self.find('.screen-uri').val('');
		    self.find('textarea').val('');
		});

		failed = function(){
			self.find('.loading').hide();
			self.find('.failed').show();
			if(fail) fail();
		}

		self.find('form').on('submit', function(){
			self.find('.report').hide();
            self.find('.loading').show();
            self.find('[name=sourcedesk_url]').val(window.location.href);
			$.post($(this).attr('action'), $(this).serialize(), null, 'json').done(function(res){
				if(res.result == 'success'){
					self.find('.loading').hide();
					self.find('.reported').show();
					if(success) success();
				} else failed();
			}).fail(function(){
				failed();
			});
			return false;
		});
	};
}( jQuery ));

$(document).ready(function () {
    editprevUndisabled = false;
    $('.feedback').feedback();

    $("#edithint_remove").click(function(e) {
        e.preventDefault();
        $(this).remove();
        $("#edithint").removeClass("alert-danger").addClass("alert-warning");
        $(".editprev").prop("disabled", false);
        editprevUndisabled = true;
    });

    $("#darkmode_btn").click(function() {
        if (!$(this).hasClass("active")) {
            $(this).addClass("active");
            $("#darkmode").attr("href", "res/css/dark.css");
            document.cookie = "darkmode=1";
        } else {
            $(this).removeClass("active");
            $("#darkmode").attr("href", "");
            document.cookie = "darkmode=0";
        }

        $(this).blur();
    });

    function customer_search() {
        $(".customer-input-results").show().html("<i class='fa fa-spinner fa-pulse fa-fw'></i> Bitte warten...");

        $(".customer-input").each(function() {
            var div = $(this).parent().find(".customer-input-results");

            if ($(this).val().trim() == "") {
                div.hide();
                $(this).parent().find("[type=hidden]").val("0").trigger("change");
            } else {
                $.post("?p=ajax", {
                    action: 'customer_search',
                    searchword: $(this).val().trim(),
                    csrf_token: $("#csrf_token").val(),
                    ajax_req: "1",
                }, function(r) {
                    if (r == "no_session") {
                        window.location = "./login.php";
                    } else {
                        div.html(r);
                    }
                });
            }
        });
    }

    var cst = undefined;

    $(".customer-input").on("keyup", function() {
        clearTimeout(cst);
        cst = setTimeout(customer_search, 400);
    });

    function invoice_search() {
        $(".invoice-input-results").show().html("<i class='fa fa-spinner fa-pulse fa-fw'></i> Bitte warten...");

        $(".invoice-input").each(function() {
            var div = $(this).parent().find(".invoice-input-results");

            if ($(this).val().trim() == "") {
                div.hide();
                $(this).parent().find("[type=hidden]").val("0").trigger("change");
            } else {
                $.post("?p=ajax", {
                    action: 'invoice_search',
                    searchword: $(this).val().trim(),
                    csrf_token: $("#csrf_token").val(),
                    ajax_req: "1",
                }, function(r) {
                    if (r == "no_session") {
                        window.location = "./login.php";
                    } else {
                        div.html(r);
                    }
                });
            }
        });
    }

    var ist = undefined;

    $(".invoice-input").on("keyup", function() {
        clearTimeout(ist);
        ist = setTimeout(invoice_search, 400);
    });
});

$('[data-toggle="popover"]').popover()