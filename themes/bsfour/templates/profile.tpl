<div class="container">

    <fieldset>


      <!-- Form Name -->
      <h1>{$lang.PROFILE.TITLE}</h1><hr>

  {if ($user.street == "" || $user.postcode == "" || $user.city == "" || $user.country == 0 || isset($country_problem)) && (!isset($p_alert) || $p_alert == "")}<div class="alert alert-info">{$lang.PROFILE.INCOMPLETE}</div>{/if}

  {if isset($p_alert) && $p_alert != ""}{$p_alert}{/if}
      <div class="pull-right" style="width: 120px;">
        <span style="position: absolute;">
          <a href="#" data-toggle="modal" data-target="#avatar">
            <img src="{$avatar}" style="border-radius: 50%;" alt="{$user.firstname} {$user.lastname}" title="{$user.firstname} {$user.lastname}">
          </a>
        </span>
      </div>

      <div class="modal fade" tabindex="-1" role="dialog" id="avatar">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">{$lang.PROFILE.AVATAR}</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="POST">
            <div class="modal-body">
              <div class="radio">
                <label>
                  <input type="radio" name="avatar" value="none"{if $user.avatar == "none"} checked{/if}>
                  {$lang.PROFILE.NONEAVATAR}
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="avatar" value=""{if $user.avatar == ""} checked{/if}>
                  {$lang.PROFILE.GRAVATAR}
                </label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">{$lang.GENERAL.SAVE}</button>
            </div>
            </form>
          </div>
        </div>
      </div>
<form id="profile-form" role="form" method="POST">
      <div class="form-group">
        <label>{$lang.PROFILE.CUSTOMER_ID}</label>
        <div>
          <input type="text" value="{$cfg.CNR_PREFIX|htmlentities}{$user.ID|htmlentities}" class="form-control" readonly="readonly" style="max-width:100px;">
        </div>
      </div>

      <div class="form-group">
        <label>{$lang.PROFILE.TELEPHONE_PIN}</label>
        <div>
          <input type="text" value="{$pin|htmlentities}" class="form-control" readonly="readonly" style="max-width:100px;">
        </div>
      </div>

      <div class="form-group">
        <label>{$lang.PROFILE.SALUTATION}</label>
        <div>
          <select name="salutation" class="form-control" style="max-width:100px;"{if "salutation"|in_array:$ro_fields} readonly="readonly"{/if}>
            <option value="MALE">{$lang.PROFILE.MALE}</option>
            <option value="FEMALE"{if (isset($smarty.post.salutation) && $smarty.post.salutation == "FEMALE") || (!isset($smarty.post.salutation) && $user.salutation == "FEMALE")} selected=""{/if}>{$lang.PROFILE.FEMALE}</option>
            <option value="DIVERS"{if (isset($smarty.post.salutation) && $smarty.post.salutation == "DIVERS") || (!isset($smarty.post.salutation) && $user.salutation == "DIVERS")} selected=""{/if}>{$lang.PROFILE.DIVERS}</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="textinput">{$lang.PROFILE.NAME}</label>
        <div>
          <div class="row">
            <div class="col-sm-6">
              <input type="text" placeholder="{$lang.PROFILE.FIRSTNAME_P}" name="p_firstname" value="{if isset($smarty.post.p_firstname)}{$smarty.post.p_firstname|htmlentities}{else}{$user.firstname|htmlentities}{/if}" class="form-control"{if "firstname"|in_array:$ro_fields} readonly="readonly"{/if}>
            </div>

            <div class="col-sm-6">
              <input type="text" placeholder="{$lang.PROFILE.LASTNAME_P}" name="p_lastname" value="{if isset($smarty.post.p_lastname)}{$smarty.post.p_lastname|htmlentities}{else}{$user.lastname|htmlentities}{/if}" class="form-control"{if "lastname"|in_array:$ro_fields} readonly="readonly"{/if}>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="textinput">{$lang.PROFILE.NICKNAME}</label>
        <div>
         <input type="text" placeholder="{$lang.PROFILE.NICKNAME_P}" name="p_nickname" value="{if isset($smarty.post.p_nickname)}{$smarty.post.p_nickname|htmlentities}{else}{$user.nickname|htmlentities}{/if}" class="form-control"{if !empty($user.nickname)} readonly="readonly"{/if}>
        </div>
      </div>

  <div class="form-group">
        <label for="textinput">{$lang.PROFILE.COMPANY}</label>
        <div>
          <input type="text" placeholder="{$lang.PROFILE.COMPANY_P}" name="p_company" value="{if isset($smarty.post.p_company)}{$smarty.post.p_company|htmlentities}{else}{$user.company|htmlentities}{/if}" class="form-control"{if "company"|in_array:$ro_fields} readonly="readonly"{/if}>
        </div>
      </div>

      <!-- Text input-->
      <div class="form-group">
        <label for="textinput">{$lang.PROFILE.MAIL}</label>
        <div>
          <input type="email"{if "mail"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="{$lang.PROFILE.MAIL_P}" value="{if isset($smarty.post.p_mail)}{$smarty.post.p_mail|htmlentities}{else}{$user.mail|htmlentities}{/if}" name="p_mail" class="form-control">
    {if $open > 0}<br /><div class="alert alert-info">{if $open == 1}{$lang.PROFILE.ONE_OPEN_CHANGE}{else}{$lang.PROFILE.X_OPEN_CHANGES|replace:"%x":$open}{/if} [ <a href="{$cfg.PAGEURL}profile/cancel">{$lang.PROFILE.CANCEL_CHANGES}</a> ]</div>{/if}
        </div>
      </div>
  {if "street"|in_array:$fields || "street_number"|in_array:$fields}<div class="form-group">
        <label for="textinput"{if !("street"|in_array:$duty_fields) && !("street_number"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.STREET}</label>
        <div>
          <div class="row">
            {if "street"|in_array:$fields}<div class="col-sm-{if "street_number"|in_array:$fields}10{else}12{/if}">
              <input type="text"{if "street"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="{$lang.PROFILE.STREET_P}" name="p_street" value="{if isset($smarty.post.p_street)}{$smarty.post.p_street|htmlentities}{else}{$user.street|htmlentities}{/if}" class="form-control">
            </div>{/if}

            {if "street_number"|in_array:$fields}<div class="col-sm-{if "street"|in_array:$fields}2{else}12{/if}">
              <input type="text"{if "street_number"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="{$lang.PROFILE.STREET_NUMBER_P}" maxlength="25" name="p_street_number" value="{if isset($smarty.post.p_street_number)}{$smarty.post.p_street_number|htmlentities}{else}{$user.street_number|htmlentities}{/if}" class="form-control">
            </div>{/if}
          </div>
        </div>
      </div>{/if}

  {if "city"|in_array:$fields || "postcode"|in_array:$fields}<div class="form-group">
        <label for="textinput"{if !("postcode"|in_array:$duty_fields) && !("city"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.CITY}</label>
        <div>
          <div class="row">
            {if "postcode"|in_array:$fields}<div class="col-sm-{if "city"|in_array:$fields}2{else}12{/if}">
              <input type="text"{if "postcode"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="{$lang.PROFILE.POSTCODE_P}" maxlength="10" name="p_postcode" value="{if isset($smarty.post.p_postcode)}{$smarty.post.p_postcode|htmlentities}{else}{$user.postcode|htmlentities}{/if}" class="form-control">
            </div>{/if}

            {if "city"|in_array:$fields}<div class="col-sm-{if "postcode"|in_array:$fields}10{else}12{/if}">
              <input type="text"{if "city"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="{$lang.PROFILE.CITY_P}" name="p_city" value="{if isset($smarty.post.p_city)}{$smarty.post.p_city|htmlentities}{else}{$user.city|htmlentities}{/if}" class="form-control">
            </div>{/if}
        </div>
      </div>
</div>{/if}

  {if "country"|in_array:$fields || "postcode"|in_array:$fields}<div class="form-group">
        <label for="textinput"{if !("country"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.COUNTRY}</label>
        <div>
    <select name="country" class="form-control"{if "country"|in_array:$ro_fields} readonly="readonly"{/if}>
    <option value="0">- {$lang.PROFILE.COUNTRY_P} -</option>
    {foreach from=$countries item=name key=id}
    <option value="{$id}" {if isset($smarty.post.country)}{if $smarty.post.country == $id}selected{/if}{else}{if $user.country == $id}selected{/if}{/if}>{$name}</option>
    {/foreach}
    </select>
        </div>
      </div>{/if}

  {if $cfg.TAXES && $cfg.EU_VAT && "vatid"|in_array:$fields}<div class="form-group">
      <label for="textinput"{if !("vatid"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.VATID}</label>
      <div>
        <input type="text"{if "vatid"|in_array:$ro_fields} readonly="readonly"{/if} name="p_vatid" placeholder="{$lang.PROFILE.VATID_P}" class="form-control" value="{if isset($smarty.post.p_vatid)}{$smarty.post.p_vatid|htmlentities}{else}{$user.vatid|htmlentities}{/if}">
      </div>
    </div>{/if}


  {if "telephone"|in_array:$fields}
  <div id="smsverifyFail" class="alert alert-danger" style="display: none;"></div>

  <div class="form-group">
        <label for="textinput"{if !("telephone"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.TELEPHONE}</label>
        <div>
          {if $smsverify}<div class="input-group">{/if}
          <input type="text"{if "telephone"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="" name="p_telephone" value="{if isset($smarty.post.p_telephone)}{$smarty.post.p_telephone|htmlentities}{else}{$user.telephone|htmlentities}{/if}" class="form-control">
          {if $smsverify}
          <span class="input-group-addon">
            {if !$user.telephone_verified}<a href="#" id="verifyPhone" data-toggle="tooltip" title="{$lang.PROFILE.VERIFY}">{/if}
            <i class="fa fa-check fa-fw" id="verifyCheck"{if $user.telephone_verified} style="color: green;"{/if}></i>
            {if !$user.telephone_verified}</a>{/if}
          </span>
          </div>{/if}
        </div>
      </div>

  {if $smsverify}
  <div id="smsverifyContainer" style="display: none;" class="alert alert-success">{$lang.PROFILE.SMS_CODE_ENTER}<br /><center><div class="input-group" style="max-width: 250px; margin-top: 10px;"><input type="text" id="sms_code" class="form-control input-lg" placeholder="00000000" style="text-align: center; letter-spacing: 2px;"><span class="input-group-addon"><i id="sms_code_status" class="fa fa-arrow-left fa-fw"></i></center></div>

  <script>
  var doingSms = 0;

  $("#sms_code").keyup(function() {
    $("#sms_code_status").addClass("fa-arrow-left").removeClass("fa-spinner fa-spin fa-times").css("color", "");

    var code = $(this).val().trim();
    if (code.length == 8) {
      $(this).prop("disabled", true);
      $("#sms_code_status").removeClass("fa-arrow-left").addClass("fa-spinner fa-spin");

      $.post("", {
        "sms_code": $(this).val(),
        "csrf_token": "{ct}"
      }, function (r) {
        if (r == "ok") {
          window.location = "./profile";
        } else {
          $("#sms_code_status").removeClass("fa-spinner fa-spin").addClass("fa-times").css("color", "red");
          alert(r);
          $("#sms_code").prop("disabled", false).val("");
        }
      });
    }
  });

  $("#verifyPhone").click(function(e) {
    e.preventDefault();

    if (doingSms) {
      return;
    }
    doingSms = 1;

    $(this).find("i").removeClass("fa-check").addClass("fa-spinner fa-spin");
    $("#smsverifyContainer").slideUp();

    $("#smsverifyFail").slideUp(function() {
      $.post("", {
        "sms_verify": $("[name=p_telephone]").val(),
        "csrf_token": "{ct}"
      }, function(r) {
        if (r == "ok") {
          $("#sms_code_status").addClass("fa-arrow-left").removeClass("fa-spinner fa-spin fa-times").css("color", "");
          $("#smsverifyContainer").slideDown();
        } else {
          $("#smsverifyFail").html(r).slideDown();
        }
        
        doingSms = 0;
        $("#verifyPhone").find("i").addClass("fa-check").removeClass("fa-spinner fa-spin");
      });
    });
  });
  </script>
  {/if}
  {/if}

  {if "fax"|in_array:$fields}
  <div class="form-group">
    <label for="textinput"{if !("fax"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.FAX}</label>
    <div>
      <input type="text"{if "fax"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="" name="p_fax" value="{if isset($smarty.post.p_fax)}{$smarty.post.p_fax|htmlentities}{else}{$user.fax|htmlentities}{/if}" class="form-control">
    </div>
  </div>
  {/if}

  {if "birthday"|in_array:$fields}<div class="form-group">
        <label for="textinput"{if !("birthday"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.BIRTHDAY}</label>
        <div>
          <input type="text"{if "birthday"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="{dfo m="0" d=$smarty.now}" name="p_birthday" value="{if isset($smarty.post.p_birthday)}{$smarty.post.p_birthday|htmlentities}{else if $user.birthday != '0000-00-00'}{dfo m="0" d=$user.birthday}{/if}" class="form-control">
        </div>
      </div>{/if}

  {if "website"|in_array:$fields}<div class="form-group">
        <label for="textinput"{if !("website"|in_array:$duty_fields)} style="font-weight: normal;"{/if}>{$lang.PROFILE.WEBSITE}</label>
        <div>
          <input type="text"{if "website"|in_array:$ro_fields} readonly="readonly"{/if} placeholder="http://www.website.com/" name="p_website" value="{if isset($smarty.post.p_website)}{$smarty.post.p_website|htmlentities}{else}{$user.website|htmlentities}{/if}" class="form-control" autocomplete="off">
        </div>
      </div>{/if}

  {foreach from=$cf item=c key=id}
  <div class="form-group">
      <label for="textinput"{if !$c.3} style="font-weight: normal;"{/if}>{$c.0}</label>
      <div>
        <input type="text"{if $c.2} readonly="readonly"{/if} placeholder="" name="fields[{$id}]" value="{if isset($smarty.post.fields.$id)}{$smarty.post.fields.$id|htmlentities}{else}{$c.1|htmlentities}{/if}" class="form-control">
      </div>
    </div>
  {/foreach}

  <input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

      <!-- Text input-->
      <div class="form-group">
        <label for="textinput">{$lang.PROFILE.PASSWORD}</label>
        <div>
          <input type="text" style="display: none;">
          <input type="password" placeholder="{$lang.PROFILE.NEW_PASSWORD}" name="p_pwd" id="profile-pwd" class="form-control" autocomplete="off">
          <input type="hidden" id="profile-pwd-hashed" value="" />
          <input type="hidden" id="password_type" name="password_type" value="plain" />
        </div>
      </div>

      <div class="form-group">
        <label style="font-weight: normal;">{$lang.PROFILE.API_KEY}</label>
        <div>
          <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text">
              <a href="#" id="apiKeyEye"><i class="fa fa-eye"></i></a>
            </span></div>
            <input id="apiKeyField" type="password" value="{$user.api_key|htmlentities}" class="form-control" readonly="readonly">
            <span class="input-group-append"><span class="input-group-text">
              <a href="#" id="apiKeyReset"><i class="fa fa-undo"></i></a>
            </span></span>
          </div>
        </div>
      </div>

      <style>
      .fa-spin-reverse {
        -webkit-animation: fa-spin-reverse 2s infinite linear;
        animation: fa-spin-reverse 2s infinite linear;
      }
      @-webkit-keyframes fa-spin-reverse {
        100% {
          -webkit-transform: rotate(0deg);
          transform: rotate(0deg);
        }
        0% {
          -webkit-transform: rotate(359deg);
          transform: rotate(359deg);
        }
      }
      @keyframes fa-spin-reverse {
        100% {
          -webkit-transform: rotate(0deg);
          transform: rotate(0deg);
        }
        0% {
          -webkit-transform: rotate(359deg);
          transform: rotate(359deg);
        }
      }
      </style>
      <script>
      $("#apiKeyEye").click(function(e) {
        e.preventDefault();

        var i = $(this).find("i");
        var f = $("#apiKeyField");
        if (i.hasClass("fa-eye-slash")) {
          f.attr("type", "password");
          i.removeClass("fa-eye-slash").addClass("fa-eye");
        } else {
          f.attr("type", "text");
          i.addClass("fa-eye-slash").removeClass("fa-eye");
        }
      });

      $("#apiKeyReset").click(function(e) {
        e.preventDefault();

        var i = $(this).find("i");
        var f = $("#apiKeyField");
        if (!i.hasClass("fa-spin-reverse")) {
          i.addClass("fa-spin-reverse");

          $.get("?reset_api_key=1", function(r) {
            i.removeClass("fa-spin-reverse");
            f.val(r);
          });
        }
      });
      </script>

      {if count($nl) > 0}
      <div class="form-group">
        <label for="checkbox" style="font-weight: normal;">{$lang.PROFILE.NEWSLETTER}</label>
        <div>
          <div class="row">
            {foreach from=$nl key=id item=name}
            <div class="col-md-4">
              <div class="checkbox">
                <label>
                <input type="checkbox" name="nl[{$id}]" value="1"{if (in_array($id, explode("|", $user.newsletter)) && !isset($smarty.post.p_submit)) || (isset($smarty.post.p_submit) && isset($smarty.post.nl) && array_key_exists($id, $smarty.post.nl))} checked=""{/if}>
                {$name|htmlentities}
                </label>
              </div>
            </div>
            {/foreach}
          </div>
        </div>
      </div>
      {/if}

  <div class="form-group">
  <div>
    <div class="checkbox">
    <label>
      <input name="login_notify" type="checkbox" value="1" {if isset($smarty.post.p_submit)}{if isset($smarty.post.login_notify) && $smarty.post.login_notify == "1"}checked{/if}{else}{if $user.login_notify == 1}checked{/if}{/if}> {$lang.PROFILE.SEND_LOGIN_NOTIFY}
    </label>
    </div>

    {if $cfg.SOCIAL_LOGIN_TOGGLE}<div class="checkbox">
    <label>
      <input name="social_login" type="checkbox" value="1" {if isset($smarty.post.p_submit)}{if isset($smarty.post.social_login) && $smarty.post.social_login == "1"}checked{/if}{else}{if $user.social_login == 1}checked{/if}{/if}> {$lang.PROFILE.SOCIAL_LOGIN}
    </label>
    </div>{/if}
  </div>
  </div>

      <div class="form-group">
          <div class="pull-right">
            <button type="reset" class="btn btn-default">{$lang.PROFILE.RESET}</button>
            <button type="submit" name="p_submit" class="btn btn-primary">{$lang.PROFILE.SAVE}</button>
          </div>
        </div>
      </div><br />

    </fieldset>
  </form>
</div>

  <div class="container">

<form class="form-horizontal" role="form" method="POST">
    <fieldset>

      <!-- Form Name -->
      <h1>{$lang.PROFILE.2FA_TITLE}</h1><hr>
{$tfa_alert}{if $tfa == 0}
<div style="padding: 20px;">
<form accept-charset="UTF-8" role="form" id="login-form" method="post">
  <fieldset>
<div class="form-group" style="margin-bottom:0;">
  <p style="text-align:justify;margin-top:-30px;">
  <div class="row">
<div class="col-md-4">
<img src="{$qrc}" alt="{$sec}" title="{$sec}" style="float: left; margin-right: 15px;">
</div>
<div class="col-md-8">
<p style="text-align:justify; margin-top:2px;">{$lang.PROFILE.2FA_INACTIVE}</p>
</div>
</div></p>
</div>
    <div class="form-group input-group">
      <span class="input-group-prepend">
        <span class="input-group-text">
        <i class="fa fa-mobile-phone">
        </i>
        </span>
      </span>
      <input class="form-control" placeholder="{$lang.PROFILE.2FA_CODE}" name="code" type="text" value="" required="">
  <input type="hidden" name="secret" value="{$sec|htmlentities}">
    </div>
    <div class="form-group">
      <button type="submit" name="activate2fa" class="btn btn-primary btn-block">
        {$lang.PROFILE.2FA_ACTIVE_BUTTON}
      </button>
    </div>
  </fieldset>
</form>
</div>
</div>{else}
<form accept-charset="UTF-8" role="form" method="post">
  <fieldset>
<div class="form-group" style="margin-bottom:0;">
<p style="text-align:justify;margin-top:10px;padding:0;">{$lang.PROFILE.2FA_ACTIVE}</p>
</div>
    <div class="form-group input-group">
      <span class="input-group-prepend">
      <span class="input-group-text">
        <i class="fa fa-mobile-phone">
        </i>
      </span>
      </span>
      <input class="form-control" placeholder="{$lang.PROFILE.2FA_CODE}" name="code" type="text" value="" required="">
  <input type="hidden" name="secret" value="{$sec|htmlentities}">
    </div>
    <div class="form-group">
      <button type="submit" name="deactivate2fa" class="btn btn-primary btn-block">
        {$lang.PROFILE.2FA_INACTIVE_BUTTON}
      </button>
    </div>
  </fieldset>
</form>
</div></div>{/if}</div>
