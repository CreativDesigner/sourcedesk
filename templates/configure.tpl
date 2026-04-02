<div class="container">
<h1>{$lang.CONFIGURE.TITLE} <small>{$product_name}</small></h1><hr />

{if $incldomains > 0}
<p style="text-align: justify;">{if $incldomains == 1}{$lang.CONFIGURE.INTRO_ONE}{else}{$lang.CONFIGURE.INTRO_MULTIPLE|replace:"%d":$incldomains}{/if}</p>

{if $smarty.session.configure.domains|@count > 0}
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <tr>
            <th>{$lang.CONFIGURE.DOMAIN}</th>
            <th>{$lang.CONFIGURE.TYPE}</th>
            <th>{$lang.CONFIGURE.AUTH}</th>
            <th width="35px"></th>
        </tr>

        {foreach from=$smarty.session.configure.domains item=authcode key=domain}
        <tr>
            <td>{$domain|htmlentities}</td>
            <td>{if empty($authcode)}{$lang.CONFIGURE.REG}{else}{$lang.CONFIGURE.TRANS}{/if}</td>
            <td>{if empty($authcode)}<i>({$lang.CONFIGURE.NN})</i>{else}{$authcode|htmlentities}{/if}</td>
            <th><center><a href="#" class="remove_domain" data-domain="{$domain}"><i class="fa fa-times"></i></a></center></th>
        </tr>
        {/foreach}
    </table>
</div>
{/if}

{if $smarty.session.configure.domains|@count > 0 && $incldomains - $smarty.session.configure.domains|@count <= 0}
<div class="alert alert-info">{$lang.CONFIGURE.NO_MORE|replace:"%pageurl%":$raw_cfg.PAGEURL}</div>
{else}
<form method="POST" id="add_domain">
    <div class="row">
        <div class="col-md-7">
            <input type="text" name="sld" placeholder="{$lang.CONFIGURE.DP}" class="form-control input-lg" style="height: 46px;">
        </div>

        <div class="col-md-2">
            <select name="tld" class="form-control input-lg" style="height: 46px;">
                {foreach from=$tlds item=tld}
                <option>{$tld}</option>
                {/foreach}
            </select>
        </div>

        <div class="col-md-3">
            <input type="submit" id="add_domain_btn" class="btn btn-primary btn-block" value="{$lang.CONFIGURE.AD}" style="height: 46px;">
        </div>
    </div>
    
    <p style="margin-bottom: 0; color: red; display: none;" class="help-block"></p>
</form><br />
{/if}
{/if}

{if $domain_choose}
{$lang.CONFIGURE.DCR}

<div class="radio" style="margin-top: 25px;">
    <label>
        <input type="radio" name="domain_choose" value="register" checked="">
        {$lang.CONFIGURE.DCREG}
    </label>
</div>

<div class="dcc" id="dcc_register" style="display: none;">
    <div class="row">
        <div class="col-md-10">
            <input type="text" name="dc_register_sld" class="form-control" placeholder="{$lang.CONFIGURE.WISHDOM}">
        </div>
        <div class="col-md-2">
            <select name="dc_register_tld" class="form-control">
                {foreach from=$alltlds item=tld}
                <option value="{$tld}">.{$tld}</option>
                {/foreach}
            </select>
        </div>
    </div><br />
</div>

<div class="radio">
    <label>
        <input type="radio" name="domain_choose" value="transfer">
        {$lang.CONFIGURE.DCTRANS}
    </label>
</div>

<div class="dcc" id="dcc_transfer" style="display: none;">
    <div class="row">
        <div class="col-md-10">
            <input type="text" name="dc_transfer_sld" class="form-control" placeholder="{$lang.CONFIGURE.YOURDOM}">
        </div>
        <div class="col-md-2">
            <select name="dc_transfer_tld" class="form-control">
                {foreach from=$alltlds item=tld}
                <option value="{$tld}">.{$tld}</option>
                {/foreach}
            </select>
        </div>
    </div><br />
</div>

<div class="radio">
    <label>
        <input type="radio" name="domain_choose" value="own">
        {$lang.CONFIGURE.OWNDOM}
    </label>
</div>

<div class="dcc" id="dcc_own" style="display: none;">
    <input type="text" name="dc_own" class="form-control" placeholder="{$lang.CONFIGURE.YOURDOM}">
    <br />
</div>

<script>
function dcShow() {
    $(".dcc").hide();
    $("[name=domain_choose]").each(function() {
        if ($(this).is(":checked")) {
            $("#dcc_" + $(this).val()).show();
        }
    });
}

$(document).ready(dcShow);
$("[name=domain_choose]").change(dcShow);
</script><br />
{/if}

{if $variants|count > 1}
{if $incldomains > 0 || $domain_choose}<hr style="margin-top: 0;" />{/if}
<p style="text-align: justify;">{$lang.CONFIGURE.HAS_VARIANTS}</p>

<select name="variant" class="form-control" style="margin-bottom: 10px;">
    {foreach from=$variants key=k item=v}
    <option value="{$k}">
        {$v.price}
        {if $v.setup}- {$v.setup}{/if}
        {if $v.ct}- {$v.ct}{/if}
        {if $v.mct}- {$v.mct}{/if}
        {if $v.np}- {$v.np}{/if}
    </option>
    {/foreach}
</select>
{/if}

{if $cf|count > 0}
{if $incldomains > 0 || $domain_choose || $variants|count > 1}<hr style="margin-top: 0;" />{/if}
<p style="text-align: justify;">{$lang.CONFIGURE.INTRO_CF}</p>

<style>
.radio, .checkbox {
    margin-top: 0;
}
</style>

{foreach from=$cf item=f}
<div class="form-group">
    <label>{$f.name|htmlentities}</label>
    {if $f.type == "select"}
    <select class="form-control custom_field" data-id="{$f.ID}">
        {foreach from=explode("|", $f.values) item=v}
        <option>{$v}</option>
        {/foreach}
    </select>
    {elseif $f.type == "radio"}
    {foreach from=explode("|", $f.values) item=v key=i}
    <div class="radio">
        <label>
            <input type="radio" name="radio{$f.ID}" class="custom_field" data-id="{$f.ID}" value="{$v}"{if $i == 0} checked=""{/if}>
            {$v}
        </label>
    </div>
    {/foreach}
    {elseif $f.type == "check"}
    <div class="checkbox">
        <label>
            <input type="checkbox" class="custom_field" data-id="{$f.ID}" value="1"> {$f.name|htmlentities}
        </label>
    </div>
    {else}
    <input type="{$f.type}" class="form-control custom_field" data-id="{$f.ID}" value="{$f.default}" min="{$f.minimum}"{if $f.maximum >= 0} max="{$f.maximum}"{/if} required="">
    {/if}
    {if array_key_exists("defcost", $f)}<p class="help-block">{$lang.CONFIGURE.COSTS}: {$f.defcost}</p>{/if}
</div>
{/foreach}
{/if}

<script>
$(".custom_field").change(function () {
    var id = $(this).data("id");

    if ($(this).prop("type") == "checkbox") {
        var val = $(this).is(":checked") ? 1 : 0;
    } else {
        var val = $(this).val();
    }

    var hb = $(this).closest(".form-group").find(".help-block");
        
    if (hb.length) {
        hb.html("<i class='fa fa-spinner fa-pulse'></i> {$lang.CONFIGURE.PW}");

        $.post("", {
            "csrf_token": "{ct}",
            "field_id": id,
            "field_val": val
        }, function (r) {
            hb.html("{$lang.CONFIGURE.COSTS}: " + r);
        });
    }
});
</script>

<input type="submit" id="cart_btn" class="btn btn-primary btn-block" value="{$lang.CONFIGURE.IC}">
</div><br />

<div class="modal fade" id="authModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{$lang.CONFIGURE.TT}</h4>
      </div>
      <div class="modal-body">
        <p style="text-align: justify;">{$lang.CONFIGURE.TH}</p>
        
        <input type="text" name="authcode" class="form-control" placeholder="{$lang.CONFIGURE.AUTH}" style="margin-top: 20px;">
        
        <div class="checkbox" style="margin-bottom: 0;">
            <label>
                <input type="checkbox" name="confirm">
                {$lang.CONFIGURE.TC}
            </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.CONFIGURE.TA}</button>
        <button type="button" class="btn btn-primary" id="transfer_btn" disabled="">{$lang.CONFIGURE.TD}</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="missingModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{$lang.CONFIGURE.NET}</h4>
      </div>
      <div class="modal-body">
        <p style="text-align: justify; margin-bottom: 0;">{$lang.CONFIGURE.NEI}</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.CONFIGURE.NEN}</button>
        <button type="button" class="btn btn-primary" id="continue_btn">{$lang.CONFIGURE.NEY}</button>
      </div>
    </div>
  </div>
</div>

<script>
function cart(){
    var cf = {};
    $(".custom_field").each(function(){
        var t = $(this);

        if (t.attr("type") == "checkbox") {
            cf[t.data("id")] = t.is(":checked") ? "1" : "0";
        } else if (t.attr("type") == "radio") {
            if (t.is(":checked")) {
                cf[t.data("id")] = t.val();
            }
        } else {
            cf[t.data("id")] = t.val();
        }
    });

    var dc = {};
    $("[name=domain_choose]").each(function() {
        if ($(this).is(":checked")) {
            dc = {};
            dc.type = $(this).val();

            if ($(this).val() == "own") {
                dc.domain = $("[name=dc_own]").val();
            } else {
                dc.domain = $("[name=dc_" + $(this).val() + "_sld]").val() + "." + $("[name=dc_" + $(this).val() + "_tld]").val();
            }
        }
    });

    $.post("", {
        "cart": "1",
        "custom_fields": cf,
        "domain_choose": dc,
        "variant": $("[name=variant]").val(),
        "csrf_token": "{ct}",
    }, function(r){
        if(r == "ok"){
            window.location = "{$cfg.PAGEURL}cart";
        } else {
            $("[name=tld]").prop("disabled", false);
            $("[name=sld]").prop("disabled", false);
            $("#add_domain_btn").prop("disabled", false);
            $("#cart_btn").val("{$lang.CONFIGURE.IC}").prop("disabled", false);
            $("#missingModal").modal("hide");
            alert("{$lang.CONFIGURE.TECH}");
        }
    });
}

$("#continue_btn").click(function(){
    $(this).prop("disabled", true).html("{$lang.CONFIGURE.PW}");
    cart();
});

$("#cart_btn").click(function(e){
    e.preventDefault();
    
    $("[name=tld]").prop("disabled", true);
    $("[name=sld]").prop("disabled", true);
    $("#add_domain_btn").prop("disabled", true);
    $(this).val("{$lang.CONFIGURE.OMP}").prop("disabled", true);

    {if ($incldomains > 0 && $smarty.session.configure.domains|@count == 0) || $incldomains - $smarty.session.configure.domains|@count > 0}
    $("#missingModal").modal("toggle");
    {else}
    cart();
    {/if}
});

$("#missingModal").on("hidden.bs.modal", function(){
    $("[name=tld]").prop("disabled", false);
    $("[name=sld]").prop("disabled", false);
    $("#add_domain_btn").prop("disabled", false);
    $("#cart_btn").val("{$lang.CONFIGURE.IC}").prop("disabled", false);
});

var doing_transfer = 0;
$("#transfer_btn").click(function(){
    if(doing_transfer) return;
    doing_transfer = 1;
    $("#transfer_btn").prop("disabled", true).html("{$lang.CONFIGURE.PW}");

    $.post("", {
        "tld": $("[name=tld]").val(),
        "sld": $("[name=sld]").val(),
        "authcode": $("[name=authcode]").val(),
        "csrf_token": "{ct}",
    }, function(r){
        if(r == "ok"){
            location.reload();
        } else {
            doing_transfer = 0;
            $("#transfer_btn").prop("disabled", false).html("{$lang.CONFIGURE.TD}");
            alert("{$lang.CONFIGURE.TECH}");
        }
    });
});

function checkAuth(){
    $("#transfer_btn").prop("disabled", true);
    if(doing_transfer) return;
    if($("[name=authcode]").val().length < 5) return;
    if(!$("[name=confirm]").is(":checked")) return;
    $("#transfer_btn").prop("disabled", false);
}

$("[name=confirm]").click(function(){
    checkAuth();
});

$("[name=authcode]").keyup(function(){
    checkAuth();
});

$("[name=authcode]").change(function(){
    checkAuth();
});

$("#authModal").on("hidden.bs.modal", function(){
    $("[name=tld]").prop("disabled", false);
    $("[name=sld]").prop("disabled", false);
    $("#add_domain_btn").val("{$lang.CONFIGURE.AD}").prop("disabled", false);
});

$("#add_domain_btn").click(function(e){
    e.preventDefault();
    $(".help-block").slideUp();
    $("[name=tld]").prop("disabled", true);
    $("[name=sld]").prop("disabled", true);
    $(this).val("{$lang.CONFIGURE.CA}").prop("disabled", true);

    $.post("", {
        "tld": $("[name=tld]").val(),
        "sld": $("[name=sld]").val(),
        "csrf_token": "{ct}",
    }, function(r){
        if(r == "ok"){
            location.reload();
        } else if(r == "auth"){
            $("#authModal").modal("toggle");
            $("#add_domain_btn").val("{$lang.CONFIGURE.MIR}");
        } else {
            $(".help-block").html(r).slideDown();
            $("[name=tld]").prop("disabled", false);
            $("[name=sld]").prop("disabled", false);
            $("#add_domain_btn").val("{$lang.CONFIGURE.AD}").prop("disabled", false);
        }
    });
});

$(".remove_domain").click(function(e){
    e.preventDefault();
    var t = $(this);

    t.find("i").removeClass("fa-times").addClass("fa-spinner fa-spin");
    
    $.post("", {
        "remove": t.data("domain"),
        "csrf_token": "{ct}",
    }, function(r){
        if(r == "ok") location.reload();
        t.find("i").addClass("fa-times").removeClass("fa-spinner fa-spin");
    });
});
</script>