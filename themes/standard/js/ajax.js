// Credit transfer form
function doCreditTransfer() {
  $("#credit_transfer_amount_group").removeClass("has-error");
  $("#credit_transfer_recipient_group").removeClass("has-error");
  $("#credit_transfer_hide_times").hide();
  $("#credit_transfer_hide_button").hide();
  $("#credit_transfer_submit_button").html(please_wait);
  $("#credit_transfer_submit_button").prop('disabled', true);

  setTimeout(function () {
    $.post(
      "./credit",
      { action: "transfer_credit", amount: $("#credit_transfer_amount").val(), recipient: $("#credit_transfer_recipient").val(), "csrf_token": $("#csrf_token").val() },
      function (response, status) {
        var res = response.trim();
        if(res == "ok"){
          creditTransferred = true;
          $("#credit_transfer_submit_button").html(transfered_credit);
          $("#credit_transfer_submit_button").addClass('btn-success');
          setTimeout(function () {
            location.reload();
          }, 2000);
        } else if(res == "amount") {
          $("#credit_transfer_hide_times").show();
          $("#credit_transfer_hide_button").show();
          $("#credit_transfer_submit_button").html(transfer_credit);
          $("#credit_transfer_amount_group").addClass("has-error");
        } else if(res == "recipient") {
          $("#credit_transfer_hide_times").show();
          $("#credit_transfer_hide_button").show();
          $("#credit_transfer_submit_button").html(transfer_credit);
          $("#credit_transfer_recipient_group").addClass("has-error");
        } else {
          $("#credit_transfer_hide_times").show();
          $("#credit_transfer_hide_button").show();
          $("#credit_transfer_amount_group").addClass("has-error");
          $("#credit_transfer_submit_button").html(transfer_credit);
          $("#credit_transfer_recipient_group").addClass("has-error");
        }
      }
    ); }
    , 1000);
}

$( "#credit_transfer_amount" ).keyup(function() {
  $("#credit_transfer_amount_group").removeClass("has-error");
  $("#credit_transfer_submit_button").prop('disabled', false);
});

$( "#credit_transfer_recipient" ).keyup(function() {
  $("#credit_transfer_recipient_group").removeClass("has-error");
  $("#credit_transfer_submit_button").prop('disabled', false);
});

// Tooltip
$(document).ready(function(){
  $('[data-toggle="tooltip"]').tooltip({
      placement : 'top'
  });
});

// Note expanding
function expand_note(id) {
  $("#n" + id + "_unexpanded").hide();
  $("#n" + id + "_expanded_link").show();
  $("#n" + id + "_expanded").slideDown();
  return false;
}

function unexpand_note(id) {
  $("#n" + id + "_unexpanded").show();
  $("#n" + id + "_expanded_link").hide();
  $("#n" + id + "_expanded").slideUp();
  return false;
}

// Search
$(function () {
    $('a[href="#search"]').on('click', function(event) {
        event.preventDefault();
        if(!$('#search').hasClass('open')){
            $('#search').show();
            $('#search').addClass('open');
            $('#search > form > input[type="search"]').focus();
        } else {
            $('#search').removeClass('open');
        }
    });
    
    $('#search').on('click keyup', function(event) {
        if (event.target == this || event.keyCode == 27) {
            $(this).removeClass('open');
        }
    });
});

// Login button
$(document).ready(function () {
  if($("#login-button").length){
    $("#login-button").attr("href", "#");
    $("#login-button").attr("data-toggle", "modal");
    $("#login-button").attr("data-target", "#login-modal");
  }

  if($("#register-button").length){
    $("#register-button").attr("href", "#");
    $("#register-button").attr("data-toggle", "modal");
    $("#register-button").attr("data-target", "#signup-modal");
  }
});

// Close top alert
$("#top-alert-dismiss").click(function (e) {
  e.preventDefault();
  $(".top-alert").fadeOut(400, function () {
    $(".top-alert-nav").removeClass("top-alert-nav");
    $(".top-alert-content").removeClass("top-alert-content");
    document.cookie="hide_top_msg=1";
  });
});

// Checkbox code
function check_all (state) {
    $('.checkbox').prop('checked', state);
}

function toggle () {
    var state = true;

    $('.checkbox:checkbox').each(function (i){
        if(!$(this).prop('checked')){
            state = false;
            return false;
        }
    });

    $('#checkall').prop('checked', state);
}

// Lightbox
$(document).delegate('*[data-toggle="lightbox"]', 'click', function(event) {
    event.preventDefault();
    $(this).ekkoLightbox();
}); 

// Modal
$('.modal-blur').on('shown.bs.modal', function (e) {
    $('.body-blur').css('-webkit-filter', 'blur(5px)');
});
$('.modal-blur').on('hidden.bs.modal', function (e) {
    if($(".modal-backdrop").length == 0)
      $('.body-blur').css('-webkit-filter', '');
});