<div class="container">
  <h1>{$domain|htmlentities} {if $di}<small><div class="label label-{if $di->status == "REG_WAITING" || $di->status == "KK_WAITING"}warning{else if $di->status == "REG_OK" || $di->status == "KK_OK"}success{else if $di->status == "KK_OUT" || $di->status == "EXPIRED" || $di->status == "TRANSIT" || $di->status == "DELETED"}default{else if $di->status == "KK_ERROR" || $di->status == "REG_ERROR"}danger{/if}">{if $di->payment}{$lang.PRODUCTS.HS_PAY}{else}{$lang.PRODUCTS.DOMAINSTATUS.{$di->status}}{/if}</div></small>{/if}</h1><hr>
  {if $di}
  {if $di->payment}
  <div class="alert alert-warning" style="text-align: justify;">{$lang.DOMAIN.WAIT_PAYMENT}</div>
  {else if $di->status == "REG_WAITING"}
  <div class="alert alert-info" style="text-align: justify;">{$lang.DOMAIN.REG_WAITING|replace:"%d":($domain|htmlentities)}{if $di->sent} {$lang.DOMAIN.SENT}{/if} {$lang.DOMAIN.EMAILINCOMING}</div>
  {else if $di->status == "KK_WAITING"}
  <div class="alert alert-info" style="text-align: justify;">{$lang.DOMAIN.KK_WAITING|replace:"%d":$domain}{if $di->sent} {$lang.DOMAIN.SENT}{/if} {$lang.DOMAIN.EMAILINCOMING}</div>
  {else if $di->status == "KK_OUT"}
  <div class="alert alert-warning" style="text-align: justify;">{$lang.DOMAIN.KK_OUT|replace:"%d":($domain|htmlentities)|replace:"%p":$cfg.PAGENAME}</div>
  {else if $di->status == "EXPIRED"}
  <div class="alert alert-warning" style="text-align: justify;">{$lang.DOMAIN.EXPIRED|replace:"%d":($domain|htmlentities)}</div>
  {else if $di->status == "TRANSIT"}
  <div class="alert alert-warning" style="text-align: justify;">{$lang.DOMAIN.TRANSIT|replace:"%d":($domain|htmlentities)}</div>
  {else if $di->status == "DELETED"}
  <div class="alert alert-warning" style="text-align: justify;">{$lang.DOMAIN.DELETED|replace:"%d":($domain|htmlentities)}</div>
  {else if $di->status == "KK_ERROR"}
  <div class="alert alert-danger" style="text-align: justify;">{$lang.DOMAIN.KK_ERROR|replace:"%d":($domain|htmlentities)|replace:"%p":$cfg.PAGENAME}</div>

  <form method="POST">
 	<input type="hidden" name="action" value="restart_kk">
 	<div class="form-group">
 		<label>{$lang.DOMAINS.TRANSFER_AUTH}</label>
 		<input type="text" class="form-control" name="authcode" value="{$di->reg_info.transfer.0|htmlentities}" />
 	</div>
 	<input type="submit" value="{$lang.DOMAIN.KK_RESTART}" class="btn btn-primary btn-block" />
  </form>
  {else if $di->status == "REG_ERROR"}
  <div class="alert alert-danger" style="text-align: justify;">{$lang.DOMAIN.REG_ERROR|replace:"%d":($domain|htmlentities)}</div>

  <form method="POST">
 	<input type="hidden" name="action" value="restart_reg">
 	<input type="submit" value="{$lang.DOMAIN.REG_RESTART}" class="btn btn-primary btn-block" />
  </form>
  {else}
  {if $di->customer_wish > 0}
  <div class="alert alert-danger" style="text-align: justify;">{$lang.DOMAIN.WISH|replace:"%d":($domain|htmlentities)|replace:"%w1":$customer_when|replace:"%w2":$customer_when2} {if $di->customer_wish == 1}{$lang.DOMAIN.WISH1}{else}{$lang.DOMAIN.WISH2} ({if $di->customer_wish == 3}{$lang.DOMAIN.WISH4}{else}{$lang.DOMAIN.WISH3}{/if}){/if} {$lang.DOMAIN.WISH5}.</div>
  <p style="text-align: justify;">{$lang.DOMAIN.WISH6}</p>
  <a href="#" id="delete-revoke" class="btn btn-success btn-block" style="margin-top: 15px;">{$lang.DOMAIN.WISH7}</a>
  <script>
  $("#delete-revoke").click(function(e){
  	e.preventDefault();
  	$.get("?delete_revoke=1", function(r){
  		if(r == "ok")
  			location.reload();
  	})
  });
  </script>
  {else}
  {if $err}
  <div class="alert alert-danger" style="text-align: justify;">{$err}</div>
  {else if $suc}
  <div class="alert alert-success" style="text-align: justify;">{$suc}</div>
  {else}
  {if $di->changed == "1"}
  <div class="alert alert-warning" style="text-align: justify;">{$lang.DOMAIN.RST_WAITING}</div>
  {else if $di->changed == "-1"}
  <div class="alert alert-danger" style="text-align: justify;">{$lang.DOMAIN.RST_ERR}</div>
  {/if}
  {/if}

  <div>
	<ul class="nav nav-tabs">
	    <li class="nav-item"><a href="#info" class="nav-link active" data-toggle="tab" role="tab">{$lang.DOMAIN.TAB1}</a></li>
	    <li class="nav-item"><a class="nav-link" href="#ns" data-toggle="tab" role="tab">{$lang.DOMAIN.TAB2}</a></li>
	    {if $di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5)}<li class="nav-item"><a class="nav-link" href="#dns" data-toggle="tab" role="tab">{$lang.DOMAIN.TAB3}</a></li>
	    {if $dyndns}<li class="nav-item"><a class="nav-link" href="#dyndns" data-toggle="tab" role="tab">{$lang.DOMAIN.TAB4}</a></li>{/if}{/if}
	    <li class="nav-item"><a class="nav-link" href="#owner" data-toggle="tab" role="tab">{$lang.DOMAINS.OWNERC}</a></li>
	    <li class="nav-item"><a class="nav-link" href="#admin" data-toggle="tab" role="tab">{$lang.DOMAINS.ADMINC}</a></li>
	    {if $user.domain_contacts}<li class="nav-item"><a class="nav-link" href="#tech" data-toggle="tab" role="tab">{$lang.DOMAINS.TECHC}</a></li>
	    <li class="nav-item"><a class="nav-link" href="#zone" data-toggle="tab" role="tab">{$lang.DOMAINS.ZONEC}</a></li>{/if}
	    {if $di->privacy_price >= 0}<li class="nav-item"><a class="nav-link" href="#privacy" data-toggle="tab" role="tab">{$lang.DOMAIN.TAB5}</a></li>{/if}
	    {if $freessl}<li class="nav-item"><a class="nav-link" href="#ssl" data-toggle="tab" role="tab">{$lang.DOMAINS.SSL}</a></li>{/if}
	    <li class="nav-item"><a class="nav-link" href="#delete" data-toggle="tab" role="tab">{$lang.DOMAIN.TAB6}</a></li>
	</ul>
	<br />
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="info">
	    	<div class="row">
	    		<div class="col-md-6">
	    			<style>
					#screenshot {
						width: 100%;
						height: 450px;
						border: 1px solid black;
						background: url({$raw_cfg.PAGEURL}themes/standard/images/gears.gif) 50% no-repeat;
						border-radius: 5px;
						overflow: hidden;
						object-fit: cover;
						object-position: top;
					}
					</style>

					<img src="{$raw_cfg.PAGEURL}themes/standard/images/clear.gif" id="screenshot"><br /><br />

					<script>{literal}
					var screenshot = $("#screenshot");
					var download = $("<img>");

					download.load(function(){
						screenshot.attr("src", $(this).attr("src"));
					});

					download.attr("src", "{/literal}{$cfg.PAGEURL}domain/{$pars}?image=1{literal}");
					{/literal}</script>
				</div>

	    		<div class="col-md-6">
			    	<form class="form-horizontal">
					  <div class="form-group">
					    <label class="col-sm-6 col-md-8 control-label">{$lang.DOMAIN.RECURRING}</label>
					    <div class="col-sm-6 col-md-4 control-label" style="text-align: left;">
					      {$cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $di->recurring)))}
					    </div>
					  </div>
					  <div class="form-group">
					    <label class="col-sm-6 col-md-8 control-label">{$lang.DOMAIN.EXPIRATION}</label>
					    <div class="col-sm-6 col-md-4 control-label" style="text-align: left;">
					      {if $di->expiration == "0000-00-00"}<i>{$lang.PRODUCTS.UNKNOWN}</i>{else}{dfo d=$di->expiration m=0}{/if}
					    </div>
					  </div>
					  <div class="form-group">
					    <label class="col-sm-6 col-md-8 control-label">{$lang.DOMAIN.AUTORENEW}</label>
					    <div class="col-sm-6 col-md-4 control-label" style="text-align: left;">
					      <span id="renew_yes"{if !$di->auto_renew} style="display: none;"{/if}>{$lang.DOMAIN.ACTIVE} &nbsp; <a href="#" id="renew_pause"><i class="fa fa-pause"></i></a></span>
					      <span id="renew_no"{if $di->auto_renew} style="display: none;"{/if}>{$lang.DOMAIN.INACTIVE} &nbsp; <a href="#" id="renew_play"><i class="fa fa-play"></i></a></span>
					    </div>
					  </div>

					  <script>
					  $("#renew_pause").click(function(e){
					  	e.preventDefault();
					  	$.get("?renew=0", function(r){
					  		if(r == "ok"){
					  			$("#renew_yes").hide();
					  			$("#renew_no").show();
					  		}
					  	});
					  });

					  $("#renew_play").click(function(e){
					  	e.preventDefault();
					  	$.get("?renew=1", function(r){
					  		if(r == "ok"){
					  			$("#renew_yes").show();
					  			$("#renew_no").hide();
					  		}
					  	})
					  });
					  </script>

					  {if $lockAvailable}<div class="form-group">
					    <label class="col-sm-6 col-md-8 control-label">{$lang.DOMAIN.TRANSLOCK}</label>
					    <div class="col-sm-6 col-md-4 control-label" style="text-align: left;">
					      <span id="lock_yes"{if !$di->transfer_lock} style="display: none;"{/if}>{$lang.DOMAIN.ACTIVE} &nbsp; <a href="#" id="lock_pause"><i class="fa fa-pause"></i></a></span>
					      <span id="lock_no"{if $di->transfer_lock} style="display: none;"{/if}>{$lang.DOMAIN.INACTIVE} &nbsp; <a href="#" id="lock_play"><i class="fa fa-play"></i></a></span>
					    </div>
					  </div>

					  <script>
					  $("#lock_pause").click(function(e){
					  	e.preventDefault();
					  	$.get("?lock=0", function(r){
					  		if(r == "ok"){
					  			$("#lock_yes").hide();
					  			$("#lock_no").show();
					  		}
					  	})
					  });

					  $("#lock_play").click(function(e){
					  	e.preventDefault();
					  	$.get("?lock=1", function(r){
					  		if(r == "ok"){
					  			$("#lock_yes").show();
					  			$("#lock_no").hide();
					  		}
					  	})
					  });
					  </script>{/if}

					  {if $user.auth_lock != 1 && $auth_available && ($cfg.CUSTOMER_AUTHCODE || $user.auth_lock == -1)}<div class="form-group">
					    <label class="col-sm-6 col-md-8 control-label">{$lang.DOMAINS.TRANSFER_AUTH}</label>
					    <div class="col-sm-6 col-md-4 control-label" style="text-align: left;">
					      <a href="#" id="auth_fetch">{$lang.DOMAIN.FETCH}</a>
					      <span id="auth_wait" style="display: none;"><i class="fa fa-spinner fa-spin"></i> {$lang.DOMAIN.FETCHING}</span>
					      <span id="auth_fail" style="color: red; display: none;"><i class="fa fa-times"></i> {$lang.DOMAIN.FAILED}</span>
					      <span id="auth_ok" style="display: none;"><i class="fa fa-check" style="color: green;"></i> <span id="auth_code"></span></span>
					    </div>
					  </div>{/if}

					  <script>
					  $("#auth_fetch").click(function(e){
					  	e.preventDefault();
					  	$(this).hide();
					  	$("#auth_wait").show();

					  	$.get("?auth=1", function(r){
					  		$("#auth_wait").hide();
					  		if(r == "fail"){
					  			$("#auth_fail").show();
					  		} else {
					  			$("#auth_ok").show();
					  			$("#auth_code").html(r);
					  		}
					  	})
					  });
					  </script>
					</form>
				</div>
			</div>
	    </div>
	    <div role="tabpanel" class="tab-pane" id="ns">
	    	<form method="POST">
		    		<div class="radio" style="margin-top: 0;">
		                <label>
		                    <input type="radio" name="ns-option" class="ns-option" value="isp"{if $di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5)} checked="checked"{/if}>
		                    {$lang.DOMAINS.OURNS|replace:"%p":$cfg.PAGENAME}
		                </label>
		            </div>
		            <div class="radio">
		                <label>
		                    <input type="radio" name="ns-option" class="ns-option" value="own"{if $di->reg_info.ns|count != 2 && ($di->reg_info.ns|count != 5 || $di->reg_info.ns.0 != $cfg.NS1 || $di->reg_info.ns.1 != $cfg.NS2 || $di->reg_info.ns.2 != $cfg.NS3 || $di->reg_info.ns.3 != $cfg.NS4 || $di->reg_info.ns.4 != $cfg.NS5)} checked="checked"{/if}>
		                    {$lang.DOMAINS.OWNNS}
		                </label>
		            </div><br />

		            <script>
		            $(".ns-option").change(function() {
				        if($(this).val() == "own"){
				            $("#ns-own").show();
				        } else {
				            $("#ns-own").hide();
				        }
				    });
		            </script>

		            <span id="ns-own"{if $di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5)} style="display: none;"{/if}><label>{$lang.DOMAINS.NS|replace:"%n":"1"}</label>
		            <input type="text" name="ns1" placeholder="{$cfg.NS1}" value="{if !($di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5))}{if $di->reg_info.ns|count == 5}{$di->reg_info.ns.0}{/if}{/if}" class="form-control" />

		            <br /><label>{$lang.DOMAINS.NS|replace:"%n":"2"}</label>
		            <input type="text" name="ns2" placeholder="{$cfg.NS2}" value="{if !($di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5))}{if $di->reg_info.ns|count == 5}{$di->reg_info.ns.1}{/if}{/if}" class="form-control" />

		            <br /><label>{$lang.DOMAINS.NS|replace:"%n":"3"}</label>
		            <input type="text" name="ns3" placeholder="{$lang.DOMAINS.NSOPTIONAL}" value="{if !($di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5))}{if $di->reg_info.ns|count == 5}{$di->reg_info.ns.2}{/if}{/if}" class="form-control" />

		            <br /><label>{$lang.DOMAINS.NS|replace:"%n":"4"}</label>
		            <input type="text" name="ns4" placeholder="{$lang.DOMAINS.NSOPTIONAL}" value="{if !($di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5))}{if $di->reg_info.ns|count == 5}{$di->reg_info.ns.3}{/if}{/if}" class="form-control" />

		            <br /><label>{$lang.DOMAINS.NS|replace:"%n":"5"}</label>
		            <input type="text" name="ns5" placeholder="{$lang.DOMAINS.NSOPTIONAL}" value="{if !($di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5))}{if $di->reg_info.ns|count == 5}{$di->reg_info.ns.4}{/if}{/if}" class="form-control" />

		            <br /><small>{$lang.DOMAIN.GLUE}</small><br /><br /></span>
		        
				
	    	<input type="hidden" name="action" value="save_ns" />
	    	<input type="submit" value="{$lang.DOMAIN.NSSAVE}" class="btn btn-primary btn-block" /><br />
	    </form></div>
	    {if $di->reg_info.ns|count == 2 || ($di->reg_info.ns|count == 5 && $di->reg_info.ns.0 == $cfg.NS1 && $di->reg_info.ns.1 == $cfg.NS2 && $di->reg_info.ns.2 == $cfg.NS3 && $di->reg_info.ns.3 == $cfg.NS4 && $di->reg_info.ns.4 == $cfg.NS5)}<div role="tabpanel" class="tab-pane" id="dns">
			<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/responsive.bootstrap.min.js"></script>
			<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css">
			<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.1.0/css/responsive.bootstrap.min.css">

	    	<span id="dns"><div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> {$lang.DOMAIN.NSLOAD1}</div>{$lang.DOMAIN.NSLOAD2}</span>
	    </div>

	    <div role="tabpanel" class="tab-pane" id="dyndns">
	    	<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/responsive.bootstrap.min.js"></script>
			<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css">
			<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.1.0/css/responsive.bootstrap.min.css">

	    	<span id="dyndns"><div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> {$lang.DOMAIN.DYNLOAD1}</div>{$lang.DOMAIN.DYNLOAD2}</span>

	    	<script>
	    	$(document).ready(function(){
	    		$.get("?dns=1", function(r){
	    			var s = r.split("Starting DynDNS");
	    			$("#dns").html(s[0]);
	    			$("#dyndns").html(s[1]);
	    		});
	    	});
	    	</script>
	   	</div>{/if}
	    <div role="tabpanel" class="tab-pane" id="owner">
	    	{if $trade > 0}
	    	<div id="trade_info"><div class="alert alert-info" style="text-align: justify;">{$lang.DOMAIN.TRADE_FEE|replace:"%f":$trade_f}</div>
            <p style="text-align: justify;">{if $user.credit < $trade}{$lang.DOMAIN.TRADE_MISSING|replace:"%p":$cfg.PAGEURL}{else}{$lang.DOMAIN.TRADE_FEE2}{/if}</p>
            {if $user.credit >= $trade}<a href="#" id="trade_btn" class="btn btn-primary btn-block">{$lang.DOMAIN.TRADE_OK}</a>{/if}</div>
            {/if}
	    	<div class="alert alert-danger" id="owner-error" style="display: none; margin-bottom: 10px;"></div>
	    	<div class="alert alert-success" id="owner-success" style="display: none; margin-bottom: 10px;">{$lang.DOMAIN.TRADE_DONE}</div>

	    	<form onsubmit="return false;"{if $trade > 0} style="display: none;" id="trade_form"{/if}>
		    	<div class="row">
	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="owner-firstname" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.owner.0}" class="form-control" />
	                </div>

	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="owner-lastname" placeholder="{$lang.DOMAINS.LASTNAME}" value="{$di->reg_info.owner.1}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="owner-company" placeholder="{$lang.DOMAINS.COMPANY}" value="{$di->reg_info.owner.2}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="owner-street" placeholder="{$lang.DOMAINS.ADDRESS}" value="{$di->reg_info.owner.3}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-2 col-xs-12">
	                    <select name="owner-country" class="form-control">
	                    	{foreach from=$countries item=country key=id}
                            <option{if $di->reg_info.owner.4 == $id} selected="selected"{/if}>{$id}</option>
                            {/foreach}
	                    </select>
	                </div>

	                <div class="col-sm-2 col-xs-12">
	                    <input type="text" name="owner-postcode" placeholder="{$lang.DOMAINS.POSTCODE}" value="{$di->reg_info.owner.5}" class="form-control" />
	                </div>

	                <div class="col-sm-8 col-xs-12">
	                    <input type="text" name="owner-city" placeholder="{$lang.DOMAINS.CITY}" value="{$di->reg_info.owner.6}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-4">
	                    <input type="text" name="owner-telephone" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.owner.7}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="owner-telefax" placeholder="{$lang.DOMAINS.FAX}" value="{$di->reg_info.owner.8}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="owner-email" placeholder="{$lang.DOMAINS.MAIL}" value="{$di->reg_info.owner.9}" class="form-control" />
	                </div>
	            </div>

              <div class="form-group" style="margin-top: 10px;">
                <input type="text" name="owner-remarks" placeholder="{$lang.DOMAINS.REMARKS}" value="{$di->reg_info.owner.10}" class="form-control" />
              </div>

	            <div class="row" style="margin-top: 10px;">
	            	<div class="col-sm-6">
	            		<input type="reset" class="btn btn-default btn-block" value="{$lang.DOMAIN.RESET}">
	            	</div>
	            	<div class="col-sm-6">
	            		<input type="submit" class="btn btn-primary btn-block" value="{if $trade > 0}{$lang.DOMAIN.TRADE_DO}{else}{$lang.DOMAIN.SAVE_DATA}{/if}" id="owner-save">
	            	</div>
	            </div>
            </form>

            <script>
            $("#owner-save").click(function(){
            	$("#owner-success").slideUp();
            	$("#owner-error").slideUp();
            	$("#owner-save").html("<i class='fa fa-spinner fa-spin'></i> {$lang.DOMAIN.SAVING}").prop("disabled", true);
            	var owner = new Array($("[name='owner-firstname']").val(), $("[name='owner-lastname']").val(), $("[name='owner-company']").val(), $("[name='owner-street']").val(), $("[name='owner-country']").val(), $("[name='owner-postcode']").val(), $("[name='owner-city']").val(), $("[name='owner-telephone']").val(), $("[name='owner-telefax']").val(), $("[name='owner-email']").val(), $("[name='owner-remarks']").val());
            	$.post("?{if $trade > 0}trade=owner{else}change=owner{/if}", {
            		data: owner,
					"csrf_token": "{ct}",
            	}, function(r){
            		if(r == "ok"){
            			$("#owner-success").slideDown();
            		} else {
            			$("#owner-error").html("<ul>" + r + "</ul>").slideDown();
            		}
            		$("#owner-save").html("{$lang.DOMAIN.SAVE_DATA}").prop("disabled", false);
            	});
            });

            $("#trade_btn").click(function(e){
            	e.preventDefault();
            	$("#trade_info").slideUp(function(){
            		$("#trade_form").slideDown();
            	});
            });
            </script>
	    </div>
	    <div role="tabpanel" class="tab-pane" id="admin">
	    	<div class="alert alert-danger" id="admin-error" style="display: none; margin-bottom: 10px;"></div>
	    	<div class="alert alert-success" id="admin-success" style="display: none; margin-bottom: 10px;">{$lang.DOMAIN.SAVED_DATA}</div>

	    	<form onsubmit="return false;">
	    		{if $tld != "eu"}
				<a href="#" class="btn btn-default pull-right" id="owner-to-admin">{$lang.DOMAIN.COPYOWNER}</a><br /><br />
				{/if}
		    	<div class="row"{if $tld != "eu"} style="margin-top: 10px;"{/if}>
	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="admin-firstname" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.admin.0}" class="form-control" />
	                </div>

	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="admin-lastname" placeholder="{$lang.DOMAINS.LASTNAME}" value="{$di->reg_info.admin.1}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="admin-company" placeholder="{$lang.DOMAINS.COMPANY}" value="{$di->reg_info.admin.2}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="admin-street" placeholder="{$lang.DOMAINS.ADDRESS}" value="{$di->reg_info.admin.3}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-2 col-xs-12">
	                    <select name="admin-country" class="form-control">
	                    	{foreach from=$countries item=country key=id}
                            <option{if $di->reg_info.admin.4 == $id} selected="selected"{/if}>{$id}</option>
                            {/foreach}
	                    </select>
	                </div>

	                <div class="col-sm-2 col-xs-12">
	                    <input type="text" name="admin-postcode" placeholder="{$lang.DOMAINS.POSTCODE}" value="{$di->reg_info.admin.5}" class="form-control" />
	                </div>

	                <div class="col-sm-8 col-xs-12">
	                    <input type="text" name="admin-city" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.admin.6}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-4">
	                    <input type="text" name="admin-telephone" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.admin.7}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="admin-telefax" placeholder="{$lang.DOMAINS.FAX}" value="{$di->reg_info.admin.8}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="admin-email" placeholder="{$lang.DOMAINS.MAIL}" value="{$di->reg_info.admin.9}" class="form-control" />
	                </div>
	            </div>

              <div class="form-group" style="margin-top: 10px;">
                <input type="text" name="admin-remarks" placeholder="{$lang.DOMAINS.REMARKS}" value="{$di->reg_info.admin.10}" class="form-control" />
              </div>

	            <div class="row" style="margin-top: 10px;">
	            	<div class="col-sm-6">
	            		<input type="reset" class="btn btn-default btn-block" value="{$lang.DOMAIN.RESET}">
	            	</div>
	            	<div class="col-sm-6">
	            		<input type="submit" class="btn btn-primary btn-block" value="{$lang.DOMAIN.SAVE_DATA}" id="admin-save">
	            	</div>
	            </div>
            </form>

            <script>
            $("#admin-save").click(function(){
            	$("#admin-success").slideUp();
            	$("#admin-error").slideUp();
            	$("#admin-save").html("<i class='fa fa-spinner fa-spin'></i> {$lang.DOMAIN.SAVING}").prop("disabled", true);
            	var admin = new Array($("[name='admin-firstname']").val(), $("[name='admin-lastname']").val(), $("[name='admin-company']").val(), $("[name='admin-street']").val(), $("[name='admin-country']").val(), $("[name='admin-postcode']").val(), $("[name='admin-city']").val(), $("[name='admin-telephone']").val(), $("[name='admin-telefax']").val(), $("[name='admin-email']").val(), $("[name='admin-remarks']").val());
            	$.post("?change=admin", {
            		data: admin,
					"csrf_token": "{ct}",
            	}, function(r){
            		if(r == "ok"){
            			$("#admin-success").slideDown();
            		} else {
            			$("#admin-error").html("<ul>" + r + "</ul>").slideDown();
            		}
            		$("#admin-save").html("{$lang.DOMAIN.SAVE_DATA}").prop("disabled", false);
            	});
            });

            $("#owner-to-admin").click(function(e){
            	e.preventDefault();
            	$("[name='admin-firstname']").val($("[name='owner-firstname']").val());
            	$("[name='admin-lastname']").val($("[name='owner-lastname']").val());
            	$("[name='admin-company']").val($("[name='owner-company']").val());
            	$("[name='admin-street']").val($("[name='owner-street']").val());
            	$("[name='admin-country']").val($("[name='owner-country']").val());
            	$("[name='admin-postcode']").val($("[name='owner-postcode']").val());
            	$("[name='admin-city']").val($("[name='owner-city']").val());
            	$("[name='admin-telephone']").val($("[name='owner-telephone']").val());
            	$("[name='admin-telefax']").val($("[name='owner-telefax']").val());
            	$("[name='admin-email']").val($("[name='owner-email']").val());
            	$("[name='admin-remarks']").val($("[name='owner-remarks']").val());
            });
            </script>
	    </div>
	    {if $user.domain_contacts}
	    <div role="tabpanel" class="tab-pane" id="tech">
	    	<div class="alert alert-danger" id="tech-error" style="display: none; margin-bottom: 10px;"></div>
	    	<div class="alert alert-success" id="tech-success" style="display: none; margin-bottom: 10px;">{$lang.DOMAIN.SAVED_DATA}</div>

	    	<form onsubmit="return false;">
		    	    		{if $tld != "eu"}
							<a href="#" class="btn btn-default pull-right" id="owner-to-tech">{$lang.DOMAIN.COPYOWNER}</a>{/if}
				    		<a href="#" class="btn btn-default pull-right" id="admin-to-tech">{$lang.DOMAIN.COPYADMIN}</a><br /><br />
				<div class="row" style="margin-top: 10px;">
	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="tech-firstname" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.tech.0}" class="form-control" />
	                </div>

	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="tech-lastname" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.tech.1}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="tech-company" placeholder="{$lang.DOMAINS.COMPANY}" value="{$di->reg_info.tech.2}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="tech-street" placeholder="{$lang.DOMAINS.ADDRESS}" value="{$di->reg_info.tech.3}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-2 col-xs-12">
	                    <select name="tech-country" class="form-control">
	                    	{foreach from=$countries item=country key=id}
                            <option{if $di->reg_info.tech.4 == $id} selected="selected"{/if}>{$id}</option>
                            {/foreach}
	                    </select>
	                </div>

	                <div class="col-sm-2 col-xs-12">
	                    <input type="text" name="tech-postcode" placeholder="{$lang.DOMAINS.POSTCODE}" value="{$di->reg_info.tech.5}" class="form-control" />
	                </div>

	                <div class="col-sm-8 col-xs-12">
	                    <input type="text" name="tech-city" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.tech.6}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-4">
	                    <input type="text" name="tech-telephone" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.tech.7}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="tech-telefax" placeholder="{$lang.DOMAINS.FAXR}" value="{$di->reg_info.tech.8}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="tech-email" placeholder="{$lang.DOMAINS.MAIL}" value="{$di->reg_info.tech.9}" class="form-control" />
	                </div>
	            </div>

              <div class="form-group" style="margin-top: 10px;">
                <input type="text" name="tech-remarks" placeholder="{$lang.DOMAINS.REMARKS}" value="{$di->reg_info.tech.10}" class="form-control" />
              </div>

	            <div class="row" style="margin-top: 10px;">
	            	<div class="col-sm-6">
	            		<input type="reset" class="btn btn-default btn-block" value="{$lang.DOMAIN.RESET}">
	            	</div>
	            	<div class="col-sm-6">
	            		<input type="submit" class="btn btn-primary btn-block" value="{$lang.DOMAIN.SAVE_DATA}" id="tech-save">
	            	</div>
	            </div>
            </form>

            <script>
            $("#tech-save").click(function(){
            	$("#tech-success").slideUp();
            	$("#tech-error").slideUp();
            	$("#tech-save").html("<i class='fa fa-spinner fa-spin'></i> {$lang.DOMAIN.SAVING}").prop("disabled", true);
            	var tech = new Array($("[name='tech-firstname']").val(), $("[name='tech-lastname']").val(), $("[name='tech-company']").val(), $("[name='tech-street']").val(), $("[name='tech-country']").val(), $("[name='tech-postcode']").val(), $("[name='tech-city']").val(), $("[name='tech-telephone']").val(), $("[name='tech-telefax']").val(), $("[name='tech-email']").val(), $("[name='tech-remarks']").val());
            	$.post("?change=tech", {
            		data: tech,
					"csrf_token": "{ct}",
            	}, function(r){
            		if(r == "ok"){
            			$("#tech-success").slideDown();
            		} else {
            			$("#tech-error").html("<ul>" + r + "</ul>").slideDown();
            		}
            		$("#tech-save").html("{$lang.DOMAIN.SAVE_DATA}").prop("disabled", false);
            	});
            });

            $("#owner-to-tech").click(function(e){
            	e.preventDefault();
            	$("[name='tech-firstname']").val($("[name='owner-firstname']").val());
            	$("[name='tech-lastname']").val($("[name='owner-lastname']").val());
            	$("[name='tech-company']").val($("[name='owner-company']").val());
            	$("[name='tech-street']").val($("[name='owner-street']").val());
            	$("[name='tech-country']").val($("[name='owner-country']").val());
            	$("[name='tech-postcode']").val($("[name='owner-postcode']").val());
            	$("[name='tech-city']").val($("[name='owner-city']").val());
            	$("[name='tech-telephone']").val($("[name='owner-telephone']").val());
            	$("[name='tech-telefax']").val($("[name='owner-telefax']").val());
            	$("[name='tech-email']").val($("[name='owner-email']").val());
            	$("[name='tech-remarks']").val($("[name='owner-remarks']").val());
            });

            $("#admin-to-tech").click(function(e){
            	e.preventDefault();
            	$("[name='tech-firstname']").val($("[name='admin-firstname']").val());
            	$("[name='tech-lastname']").val($("[name='admin-lastname']").val());
            	$("[name='tech-company']").val($("[name='admin-company']").val());
            	$("[name='tech-street']").val($("[name='admin-street']").val());
            	$("[name='tech-country']").val($("[name='admin-country']").val());
            	$("[name='tech-postcode']").val($("[name='admin-postcode']").val());
            	$("[name='tech-city']").val($("[name='admin-city']").val());
            	$("[name='tech-telephone']").val($("[name='admin-telephone']").val());
            	$("[name='tech-telefax']").val($("[name='admin-telefax']").val());
            	$("[name='tech-email']").val($("[name='admin-email']").val());
            	$("[name='tech-remarks']").val($("[name='admin-remarks']").val());
            });
            </script>
	    </div>
	    <div role="tabpanel" class="tab-pane" id="zone">
	    	<div class="alert alert-danger" id="zone-error" style="display: none; margin-bottom: 10px;"></div>
	    	<div class="alert alert-success" id="zone-success" style="display: none; margin-bottom: 10px;">{$lang.DOMAIN.SAVED_DATA}</div>

	    	<form onsubmit="return false;">
		    	{if $tld != "eu"}<a href="#" class="btn btn-default" class="pull-right" id="owner-to-zone">{$lang.DOMAIN.COPYOWNER}</a>{/if}
				    		<a href="#" class="btn btn-default" class="pull-right" id="admin-to-zone">{$lang.DOMAIN.COPYADMIN}</a>
				    		<a href="#" class="btn btn-default" class="pull-right" id="tech-to-zone">{$lang.DOMAIN.COPYTECH}</a><br /><br />
		    	<div class="row" style="margin-top: 10px;">
	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="zone-firstname" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.zone.0}" class="form-control" />
	                </div>

	                <div class="col-sm-6 col-xs-12">
	                    <input type="text" name="zone-lastname" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.zone.1}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="zone-company" placeholder="{$lang.DOMAINS.COMPANY}" value="{$di->reg_info.zone.2}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-12">
	                    <input type="text" name="zone-street" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.zone.3}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-2 col-xs-12">
	                    <select name="zone-country" class="form-control">
	                    	{foreach from=$countries item=country key=id}
                            <option{if $di->reg_info.zone.4 == $id} selected="selected"{/if}>{$id}</option>
                            {/foreach}
	                    </select>
	                </div>

	                <div class="col-sm-2 col-xs-12">
	                    <input type="text" name="zone-postcode" placeholder="{$lang.DOMAINS.POSTCODE}" value="{$di->reg_info.zone.5}" class="form-control" />
	                </div>

	                <div class="col-sm-8 col-xs-12">
	                    <input type="text" name="zone-city" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{$di->reg_info.zone.6}" class="form-control" />
	                </div>
	            </div>
	            <div class="row" style="margin-top: 10px;">
	                <div class="col-sm-4">
	                    <input type="text" name="zone-telephone" placeholder="{$lang.DOMAINS.PHONE}" value="{$di->reg_info.zone.7}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="zone-telefax" placeholder="{$lang.DOMAINS.FAXR}" value="{$di->reg_info.zone.8}" class="form-control" />
	                </div>
	                <div class="col-sm-4">
	                    <input type="text" name="zone-email" placeholder="{$lang.DOMAINS.MAIL}" value="{$di->reg_info.zone.9}" class="form-control" />
	                </div>
	            </div>

              <div class="form-group" style="margin-top: 10px;">
                <input type="text" name="zone-remarks" placeholder="{$lang.DOMAINS.REMARKS}" value="{$di->reg_info.zone.10}" class="form-control" />
              </div>

	            <div class="row" style="margin-top: 10px;">
	            	<div class="col-sm-6">
	            		<input type="reset" class="btn btn-default btn-block" value="{$lang.DOMAIN.RESET}">
	            	</div>
	            	<div class="col-sm-6">
	            		<input type="submit" class="btn btn-primary btn-block" value="{$lang.DOMAIN.SAVE_DATA}" id="zone-save">
	            	</div>
	            </div>
            </form>

            <script>
            $("#zone-save").click(function(){
            	$("#zone-success").slideUp();
            	$("#zone-error").slideUp();
            	$("#zone-save").html("<i class='fa fa-spinner fa-spin'></i> {$lang.DOMAIN.SAVING}").prop("disabled", true);
            	var zone = new Array($("[name='zone-firstname']").val(), $("[name='zone-lastname']").val(), $("[name='zone-company']").val(), $("[name='zone-street']").val(), $("[name='zone-country']").val(), $("[name='zone-postcode']").val(), $("[name='zone-city']").val(), $("[name='zone-telephone']").val(), $("[name='zone-telefax']").val(), $("[name='zone-email']").val(), $("[name='zone-remarks']").val());
            	$.post("?change=zone", {
            		data: zone,
					"csrf_token": "{ct}",
            	}, function(r){
            		if(r == "ok"){
            			$("#zone-success").slideDown();
            		} else {
            			$("#zone-error").html("<ul>" + r + "</ul>").slideDown();
            		}
            		$("#zone-save").html("{$lang.DOMAIN.SAVE_DATA}").prop("disabled", false);
            	});
            });

            $("#owner-to-zone").click(function(e){
            	e.preventDefault();
            	$("[name='zone-firstname']").val($("[name='owner-firstname']").val());
            	$("[name='zone-lastname']").val($("[name='owner-lastname']").val());
            	$("[name='zone-company']").val($("[name='owner-company']").val());
            	$("[name='zone-street']").val($("[name='owner-street']").val());
            	$("[name='zone-country']").val($("[name='owner-country']").val());
            	$("[name='zone-postcode']").val($("[name='owner-postcode']").val());
            	$("[name='zone-city']").val($("[name='owner-city']").val());
            	$("[name='zone-telephone']").val($("[name='owner-telephone']").val());
            	$("[name='zone-telefax']").val($("[name='owner-telefax']").val());
            	$("[name='zone-email']").val($("[name='owner-email']").val());
            	$("[name='zone-remarks']").val($("[name='owner-remarks']").val());
            });

            $("#admin-to-zone").click(function(e){
            	e.preventDefault();
            	$("[name='zone-firstname']").val($("[name='admin-firstname']").val());
            	$("[name='zone-lastname']").val($("[name='admin-lastname']").val());
            	$("[name='zone-company']").val($("[name='admin-company']").val());
            	$("[name='zone-street']").val($("[name='admin-street']").val());
            	$("[name='zone-country']").val($("[name='admin-country']").val());
            	$("[name='zone-postcode']").val($("[name='admin-postcode']").val());
            	$("[name='zone-city']").val($("[name='admin-city']").val());
            	$("[name='zone-telephone']").val($("[name='admin-telephone']").val());
            	$("[name='zone-telefax']").val($("[name='admin-telefax']").val());
            	$("[name='zone-email']").val($("[name='admin-email']").val());
            	$("[name='zone-remarks']").val($("[name='admin-remarks']").val());
            });

            $("#tech-to-zone").click(function(e){
            	e.preventDefault();
            	$("[name='zone-firstname']").val($("[name='tech-firstname']").val());
            	$("[name='zone-lastname']").val($("[name='tech-lastname']").val());
            	$("[name='zone-company']").val($("[name='tech-company']").val());
            	$("[name='zone-street']").val($("[name='tech-street']").val());
            	$("[name='zone-country']").val($("[name='tech-country']").val());
            	$("[name='zone-postcode']").val($("[name='tech-postcode']").val());
            	$("[name='zone-city']").val($("[name='tech-city']").val());
            	$("[name='zone-telephone']").val($("[name='tech-telephone']").val());
            	$("[name='zone-telefax']").val($("[name='tech-telefax']").val());
            	$("[name='zone-email']").val($("[name='tech-email']").val());
            	$("[name='zone-remarks']").val($("[name='tech-remarks']").val());
            });
            </script>
	    </div>
	    {/if}
	    {if $di->privacy_price >= 0}<div role="tabpanel" class="tab-pane" id="privacy" style="text-align: justify;">
	    	{if !$di->privacy}
	    	<div class="alert alert-success" id="privacy_success" style="display: none">{$lang.DOMAIN.WPACTIVATED}</div>

	    	<div id="privacy_do">{$lang.DOMAIN.WPINTRO}

            <div class="alert alert-warning" style="margin-top: 10px;">{$lang.DOMAIN.WPTERMS|replace:"%u":$cfg.PAGEURL|replace:"%d":($domain|htmlentities)} {$lang.DOMAIN.WPCOSTS|replace:"%f":$privacy_money_f}</div>

            {if $privacy_money > $user.credit}
            <div class="alert alert-danger">{$lang.DOMAIN.WPCREDIT|replace:"%u":$cfg.PAGEURL}</div>
            {else}
            <a href="#" id="activate_privacy" class="btn btn-primary btn-block" style="margin-top: -10px;">{$lang.DOMAIN.WPACTIVATE}</a>

            <script>
	    	$("#activate_privacy").click(function(e) {
	    		e.preventDefault();
	    		$.get("?privacy=1", function(r){
	    			if(r == "ok"){
	    				$("#privacy_do").slideUp(function(){
	    					$("#privacy_success").slideDown();
	    				});
	    			}
	    		})
	    	});
	    	</script>
            {/if}</div>{else}
            <div class="alert alert-success" id="privacy_success" style="display: none">{$lang.DOMAIN.WPDEACTIVATED}</div>

	    	<div id="privacy_do">{$lang.DOMAIN.WPAINTRO}

            <div class="alert alert-warning" style="margin-top: 10px;">{$lang.DOMAIN.WPWARNING|replace:"%f":$privacy_money_f}</div>

            <a href="#" id="deactivate_privacy" class="btn btn-warning btn-block" style="margin-top: -10px;">{$lang.DOMAIN.WPDEACTIVATE}</a>

            <script>
	    	$("#deactivate_privacy").click(function(e) {
	    		e.preventDefault();
	    		$.get("?privacy=0", function(r){
	    			if(r == "ok"){
	    				$("#privacy_do").slideUp(function(){
	    					$("#privacy_success").slideDown();
	    				});
	    			}
	    		})
	    	});
	    	</script>
        	</div>{/if}
        </div>{/if}
	    {if $freessl}<div role="tabpanel" class="tab-pane" id="ssl" style="text-align: justify;">
	    	<div id="ssl-waiting"{if !empty($di->csr)} style="display: none;"{/if}>{$lang.DOMAIN.SSLINTRO}</b>

	    	<div class="alert alert-danger" id="ssl-error" style="display: none; margin-top: 10px; margin-bottom: 0px;"></div>

	    	<textarea class="form-control" id="csr" style="resize: none; width: 100%; height: 200px; margin-bottom: 10px; margin-top: 10px;"></textarea>

	    	<a href="#" id="save_csr" class="btn btn-primary btn-block">{$lang.DOMAIN.SSLDO}</a>

	    	<script>
	    	$("#save_csr").click(function(e) {
	    		e.preventDefault();
	    		$("#ssl-error").slideUp();
	    		$("#save_csr").html('<i class="fa fa-spinner fa-spin"></i>{$lang.DOMAIN.SSLDOING}');
	    		$.post("?csr", {
	    			csr: $("#csr").val(),
					"csrf_token": "{ct}",
	    		}, function(r){
	    			if(r.split("|")[0] == "ok"){
	    				$("#ssl-waiting").slideUp(function(){
	    					var s = r.split("|");
	    					if(s.length == 4){
	    						$("#ssl-dns").show();
	    						$("#ssl-dname").html(s[1]);
	    						$("#ssl-dtype").html(s[2]);
	    						$("#ssl-dcontent").html(s[3]);
	    					}
	    					$("#ssl-finished").slideDown();
	    				});
	    			} else {
	    				$("#ssl-error").html(r).slideDown();
	    				$("#save_csr").html("{$lang.DOMAIN.SSLDO}");
	    			}
	    		})
	    	});
	    	</script></div>

	    	<div id="ssl-finished" style="display: none;">
	    		<div class="alert alert-success">{$lang.DOMAIN.SSLMAKE}</div>

	    		<div id="ssl-dns" style="display: none;">
	    		{$lang.DOMAIN.SSLDNS}<br /><br />

	    		{$lang.DOMAIN.SUBDOMAIN}: <span id="ssl-dname"></span><br />
	    		{$lang.DOMAIN.TYPE}: <span id="ssl-dtype"></span><br />
	    		{$lang.DOMAIN.CONTENT}: <span id="ssl-dcontent"></span>
	    		</div>
	    	</div>

	    	{if !empty($di->csr)}
	    	<label>{$lang.DOMAIN.CSR}</label>
	    	<textarea class="form-control" readonly="readonly" onclick="this.focus();this.select()" style="resize: none; width: 100%; height: 200px;">{$di->csr|base64_decode}</textarea>

	    	<br /><label>{$lang.DOMAIN.SSLCERT}</label>
	    	{if empty($di->ssl_cert)}
	    	<div class="alert alert-info">{$lang.DOMAIN.SSLWAIT}</div>
	    	{else}
	    	<textarea class="form-control" readonly="readonly" onclick="this.focus();this.select()" style="resize: none; width: 100%; height: 200px;">{$di->ssl_cert|base64_decode}</textarea>
	    	{/if}
	    	{/if}
	    </div>{/if}
	    <div role="tabpanel" class="tab-pane" id="delete" style="text-align: justify;">
	    	{$lang.DOMAIN.DINTRO|replace:"%d":($domain|htmlentities)}

	    	<div class="radio" style="margin-top: 20px;">
			  <label>
			    <input type="radio" name="delete_action" value="1" checked>
			    {$lang.DOMAIN.D1}
			  </label>
			</div>

			<div class="radio">
			  <label>
			    <input type="radio" name="delete_action" value="2">
			    {$lang.DOMAIN.D2}
			  </label>
			</div>

			<div class="radio">
			  <label>
			    <input type="radio" name="delete_action" value="3">
			    {$lang.DOMAIN.D3}
			  </label>
			</div>

			<div class="checkbox" style="margin-top: 30px;">
			  <label>
			    <input type="checkbox" name="delete_confirm1" value="yes">
			    {$lang.DOMAIN.DC1|replace:'%d':$domain}
			  </label>
			</div>

			<div class="checkbox">
			  <label>
			    <input type="checkbox" name="delete_confirm2" value="yes">
			    {$lang.DOMAIN.DC2}
			  </label>
			</div>

			<div class="checkbox">
			  <label>
			    <input type="checkbox" name="delete_confirm3" value="yes">
			    {$lang.DOMAIN.DC3}
			  </label>
			</div>

			<input type="button" value="{$lang.DOMAIN.WISHDO}" class="btn btn-warning btn-block" id="delete_do">

			<script>
			$("#delete_do").click(function(){
				$("[name='delete_confirm1']").parents(".checkbox").removeClass("has-error");
				$("[name='delete_confirm2']").parents(".checkbox").removeClass("has-error");
				$("[name='delete_confirm3']").parents(".checkbox").removeClass("has-error");

				var fail = 0;
				if(!$("[name='delete_confirm1']").is(":checked")){
					$("[name='delete_confirm1']").parents(".checkbox").addClass("has-error");
					fail = 1;
				}

				if(!$("[name='delete_confirm2']").is(":checked")){
					$("[name='delete_confirm2']").parents(".checkbox").addClass("has-error");
					fail = 1;
				}

				if(!$("[name='delete_confirm3']").is(":checked")){
					$("[name='delete_confirm3']").parents(".checkbox").addClass("has-error");
					fail = 1;
				}

				if(fail == 0){
					$.post("?delete_action=" + $("[name='delete_action']:checked").val(), {
						confirm: "yes",
						"csrf_token": "{ct}",
					}, function(r){
						if(r == "ok")
							location.reload();
					});
				}
			});
			</script>
	    </div>
    </div>
  </div>

  <script>
  $("ul.nav-tabs > li > a").on("shown.bs.tab", function(e) {
	var id = $(e.target).attr("href").substr(1);
	window.location.hash = id;
  });

  $(document).ready(function() {
  	var hash = window.location.hash;
  	$('a[href="' + hash + '"]').click();
  });
  </script>
  {/if}
  {/if}
  {else}
  <div class="alert alert-danger" style="text-align: justify;">{$lang.DOMAIN.NOTFOUND|replace:"%d":($domain|htmlentities)}</div>
  {/if}
</div>
