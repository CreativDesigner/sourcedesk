<div class="container">
    <h1>{if $step == "pricing"}{$lang.DOMAINS.PRICING} <small>{if !$own_pricing}{$lang.DOMAINS.PRICING_DEFAULT}{else}{$lang.DOMAINS.MY_PRICING}{/if}</small><span class="pull-right" style="font-size: 16pt; padding-top: 10px;"><a href="{$cfg.PAGEURL}domains/pricing.csv" target="_blank"><i class="fa fa-file-excel-o"></i></a> <a href="{$cfg.PAGEURL}domains/pricing.xml" target="_blank"><i class="fa fa-file-code-o"></i></a> <a href="{$cfg.PAGEURL}domains/pricing.json" target="_blank"><i class="fa fa-file-text-o"></i></a></span>{else if $step == "robot"}{$lang.DOMAINS.ROBOT}{else if $step == "dyndns"}{$lang.DOMAINS.DYNDNS}{else}{$lang.DOMAINS.TITLE}{/if}</h1><hr>

    {if $step != "pricing" && $step != "robot" && $step != "api" && $step != "dyndns"}
    {literal}<style>
    .bs-wizard {margin-top: 40px;}
	.bs-wizard {border-bottom: solid 1px #e0e0e0; padding: 0 0 10px 0;}
	.bs-wizard > .bs-wizard-step {padding: 0; position: relative;}
	.bs-wizard > .bs-wizard-step + .bs-wizard-step {}
	.bs-wizard > .bs-wizard-step .bs-wizard-stepnum {color: #595959; font-size: 16px; margin-bottom: 5px;}
	.bs-wizard > .bs-wizard-step .bs-wizard-info {color: #999; font-size: 14px;}
	.bs-wizard > .bs-wizard-step > .bs-wizard-dot {position: absolute; width: 30px; height: 30px; display: block; background: #fce5dc; top: 45px; left: 50%; margin-top: -15px; margin-left: -15px; border-radius: 50%;}
	.bs-wizard > .bs-wizard-step > .bs-wizard-dot:after {content: ' '; width: 14px; height: 14px; background: #bf3e11; border-radius: 50px; position: absolute; top: 8px; left: 8px; }
	.bs-wizard > .bs-wizard-step > .progress {position: relative; border-radius: 0px; height: 8px; box-shadow: none; margin: 20px 0;}
	.bs-wizard > .bs-wizard-step > .progress > .progress-bar {width:0px; box-shadow: none; background: #fce5dc;}
	.bs-wizard > .bs-wizard-step.complete > .progress > .progress-bar {width:100%;}
	.bs-wizard > .bs-wizard-step.active > .progress > .progress-bar {width:50%;}
	.bs-wizard > .bs-wizard-step:first-child.active > .progress > .progress-bar {width:0%;}
	.bs-wizard > .bs-wizard-step:last-child.active > .progress > .progress-bar {width: 100%;}
	.bs-wizard > .bs-wizard-step.disabled > .bs-wizard-dot {background-color: #f5f5f5;}
	.bs-wizard > .bs-wizard-step.disabled > .bs-wizard-dot:after {opacity: 0;}
	.bs-wizard > .bs-wizard-step:first-child  > .progress {left: 50%; width: 50%;}
	.bs-wizard > .bs-wizard-step:last-child  > .progress {width: 50%;}
	.bs-wizard > .bs-wizard-step.disabled a.bs-wizard-dot{ pointer-events: none; }
	</style>{/literal}

    <div class="row bs-wizard hidden-xs" style="border-bottom:0;">
        <div class="col-xs-4 bs-wizard-step {if $step == "extensions" || $step == "configure"}complete{else}active{/if}">
          <div class="text-center bs-wizard-stepnum">{$lang.DOMAINS.STEP1}</div>
          <div class="progress"><div class="progress-bar"></div></div>
          <a href="#" onclick="return false;" class="bs-wizard-dot"></a>
        </div>

        <div class="col-xs-4 bs-wizard-step {if $step == "extensions"}active{else if $step == "configure"}complete{else}disabled{/if}">
          <div class="text-center bs-wizard-stepnum">{$lang.DOMAINS.STEP2}</div>
          <div class="progress"><div class="progress-bar"></div></div>
          <a href="#" onclick="return false;" class="bs-wizard-dot"></a>
        </div>

        <div class="col-xs-4 bs-wizard-step {if $step == "configure"}active{else}disabled{/if}">
          <div class="text-center bs-wizard-stepnum">{$lang.DOMAINS.STEP3}</div>
          <div class="progress"><div class="progress-bar"></div></div>
          <a href="#" onclick="return false;" class="bs-wizard-dot"></a>
        </div>
    </div><br />
	{/if}

    {if $step == "configure"}
    <form method="POST"><div class="input-group input-group-lg input-group-box col-md-8 col-md-offset-2">
        <input type="text" class="form-control" placeholder="{$lang.DOMAINS.CTA}" value="{if isset($smarty.post.domain)}{$smarty.post.domain|htmlentities}{/if}" name="domain">
        <span class="input-group-btn">
            <button type="submit" class="btn btn-primary">{$lang.DOMAINS.SEARCH}</button>
        </span>
    </div></form><br />

    <div class="row" id="configure"><div class="col-md-8 col-md-offset-2">{foreach from=$domains item=domain key=i}<div class="panel panel-default domain_panel" data-i="{$i}" id="panel-{$i}">
      <div class="panel-heading"><span class="domain_waiting"></span><span class="domain_waiting2"></span><span id="domain-{$i}">{$domain}</span></div>
      <div class="panel-body" style="display: none;">
        <div class="alert alert-danger" id="error-{$i}" style="display: none;"></div>

        <ul class="nav nav-tabs" role="tablist">
            <li class="active"><a href="#owner-{$i}" aria-controls="owner-{$i}" role="tab" data-toggle="tab">{$lang.DOMAINS.OWNERC}</a></li>
            <li><a href="#admin-{$i}" aria-controls="admin-{$i}" role="tab" data-toggle="tab">{$lang.DOMAINS.ADMINC}</a></li>
            {if $user && $user.domain_contacts}<li><a href="#tech-{$i}" aria-controls="tech-{$i}" role="tab" data-toggle="tab">{$lang.DOMAINS.TECHC}</a></li>
            <li><a href="#zone-{$i}" aria-controls="zone-{$i}" role="tab" data-toggle="tab">{$lang.DOMAINS.ZONEC}</a></li>{else}
            <li><a href="#info-{$i}" aria-controls="info-{$i}" role="tab" data-toggle="tab">{$lang.DOMAINS.TECHC}/{$lang.DOMAINS.ZONEC}</a></li>{/if}
            <li><a href="#dns-{$i}" aria-controls="dns-{$i}" role="tab" data-toggle="tab">{$lang.DOMAINS.NSS}</a></li>
            {if array_key_exists($domain, $privacy)}<li><a href="#privacy-{$i}" aria-controls="privacy-{$i}" role="tab" data-toggle="tab">{$lang.DOMAINS.PRIVACY}</a></li>{/if}
            <li><a href="#transfer-{$i}" aria-controls="transfer-{$i}" id="tbtn-{$i}" role="tab" data-toggle="tab" style="display: none;">{$lang.DOMAINS.TRANSFER}</a></li>
        </ul>

        <br /><div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="owner-{$i}">
                <div class="row">
                    <div class="col-sm-6 col-xs-12">
                        <input type="text" name="owner-firstname-{$i}" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{if $user}{$user.firstname|htmlentities}{/if}" class="form-control" />
                    </div>

                    <div class="col-sm-6 col-xs-12">
                        <input type="text" name="owner-lastname-{$i}" placeholder="{$lang.DOMAINS.LASTNAME}" value="{if $user}{$user.lastname|htmlentities}{/if}" class="form-control" />
                    </div>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <div class="col-sm-12">
                        <input type="text" name="owner-company-{$i}" placeholder="{$lang.DOMAINS.COMPANY}" value="{if $user}{$user.company|htmlentities}{/if}" class="form-control" />
                    </div>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <div class="col-sm-12">
                        <input type="text" name="owner-street-{$i}" placeholder="{$lang.DOMAINS.ADDRESS}" value="{if $user}{$user.street|htmlentities} {$user.street_number|htmlentities}{/if}" class="form-control" />
                    </div>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <div class="col-sm-2 col-xs-12">
                        <select name="owner-country-{$i}" class="form-control">
                            {foreach from=$countries item=country key=id}
                            <option{if $user && $user.country == $id} selected="selected"{/if}>{$country}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="col-sm-2 col-xs-12">
                        <input type="text" name="owner-postcode-{$i}" placeholder="{$lang.DOMAINS.POSTCODE}" value="{if $user}{$user.postcode|htmlentities}{/if}" class="form-control" />
                    </div>

                    <div class="col-sm-8 col-xs-12">
                        <input type="text" name="owner-city-{$i}" placeholder="{$lang.DOMAINS.CITY}" value="{if $user}{$user.city|htmlentities}{/if}" class="form-control" />
                    </div>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <div class="col-sm-4">
                        <input type="text" name="owner-telephone-{$i}" placeholder="{$lang.DOMAINS.PHONE}" value="{if $user}{$user.telephone|htmlentities}{/if}" class="form-control" />
                    </div>
                    <div class="col-sm-4">
                        <input type="text" name="owner-telefax-{$i}" placeholder="{$lang.DOMAINS.FAX}" value="{if $user}{$user.fax|htmlentities}{/if}" class="form-control" />
                    </div>
                    <div class="col-sm-4">
                        <input type="text" name="owner-email-{$i}" placeholder="{$lang.DOMAINS.MAIL}" value="{if $user}{$user.mail|htmlentities}{/if}" class="form-control" />
                    </div>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="admin-{$i}">
                <div class="radio">
                  <label>
                    <input type="radio" name="admin-option-{$i}" class="admin-option" data-i="{$i}" value="same" checked="checked">
                    {$lang.DOMAINS.COPYOWNER}
                  </label>
                </div>
                <div class="radio">
                  <label>
                    <input type="radio" name="admin-option-{$i}" class="admin-option" data-i="{$i}" value="new">
                    {$lang.DOMAINS.NEWSET}
                  </label>
                </div>

                <span class="new" id="new-{$i}" data-i="{$i}" style="display: none;"><br />
                    <div class="row">
                        <div class="col-sm-6 col-xs-12">
                            <input type="text" name="admin-firstname-{$i}" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{if $user}{$user.firstname|htmlentities}{/if}" class="form-control" />
                        </div>

                        <div class="col-sm-6 col-xs-12">
                            <input type="text" name="admin-lastname-{$i}" placeholder="{$lang.DOMAINS.LASTNAME}" value="{if $user}{$user.lastname|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-12">
                            <input type="text" name="admin-company-{$i}" placeholder="{$lang.DOMAINS.COMPANY}" value="{if $user}{$user.company|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-12">
                            <input type="text" name="admin-street-{$i}" placeholder="{$lang.DOMAINS.ADDRESS}" value="{if $user}{$user.street|htmlentities} {$user.street_number|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-2 col-xs-12">
                            <select name="admin-country-{$i}" class="form-control">
                                {foreach from=$countries item=country key=id}
                                <option{if $user && $user.country == $id} selected="selected"{/if}>{$country}</option>
                                {/foreach}
                            </select>
                        </div>

                        <div class="col-sm-2 col-xs-12">
                            <input type="text" name="admin-postcode-{$i}" placeholder="{$lang.DOMAINS.POSTCODE}" value="{if $user}{$user.postcode|htmlentities}{/if}" class="form-control" />
                        </div>

                        <div class="col-sm-8 col-xs-12">
                            <input type="text" name="admin-city-{$i}" placeholder="{$lang.DOMAINS.CITY}" value="{if $user}{$user.city|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-4">
                            <input type="text" name="admin-telephone-{$i}" placeholder="{$lang.DOMAINS.PHONE}" value="{if $user}{$user.telephone|htmlentities}{/if}" class="form-control" />
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="admin-telefax-{$i}" placeholder="{$lang.DOMAINS.FAX}" value="{if $user}{$user.fax|htmlentities}{/if}" class="form-control" />
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="admin-email-{$i}" placeholder="{$lang.DOMAINS.EMAIL}" value="{if $user}{$user.mail|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                </span>
            </div>
            <div role="tabpanel" class="tab-pane" id="info-{$i}" style="text-align: justify;">
            {if $user}
            {$lang.DOMAINS.FREELI|replace:"%u":$user.ID}
            {else}
            {$lang.DOMAINS.FREENLI}
            {/if}
            </div>
            <div role="tabpanel" class="tab-pane" id="tech-{$i}">
                <div class="radio">
                  <label>
                    <input type="radio" name="tech-option-{$i}" class="tech-option" data-i="{$i}" id="tech-option-{$i}" value="isp" checked="checked">
                    {$lang.DOMAINS.OURDATA|replace:"%p":$cfg.PAGENAME}
                  </label>
                </div>
                <div class="radio">
                  <label>
                    <input type="radio" name="tech-option-{$i}" class="tech-option" data-i="{$i}" id="tech-option-{$i}" value="same">
                    {$lang.DOMAINS.COPYADMIN}
                  </label>
                </div>
                <div class="radio">
                  <label>
                    <input type="radio" name="tech-option-{$i}" class="tech-option" data-i="{$i}" id="tech-option-{$i}" value="new">
                    {$lang.DOMAINS.NEWSET}
                  </label>
                </div>

                <span class="new2" id="new2-{$i}" data-i="{$i}" style="display: none;"><br />
                    <div class="row">
                        <div class="col-sm-6 col-xs-12">
                            <input type="text" name="tech-firstname-{$i}" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{if $user}{$user.firstname|htmlentities}{/if}" class="form-control" />
                        </div>

                        <div class="col-sm-6 col-xs-12">
                            <input type="text" name="tech-lastname-{$i}" placeholder="{$lang.DOMAINS.LASTNAME}" value="{if $user}{$user.lastname|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-12">
                            <input type="text" name="tech-company-{$i}" placeholder="{$lang.DOMAINS.COMPANY}" value="{if $user}{$user.company|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-12">
                            <input type="text" name="tech-street-{$i}" placeholder="{$lang.DOMAINS.ADDRESS}" value="{if $user}{$user.street|htmlentities} {$user.street_number|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-2 col-xs-12">
                            <select name="tech-country-{$i}" class="form-control">
                                {foreach from=$countries item=country key=id}
                                <option{if $user && $user.country == $id} selected="selected"{/if}>{$country}</option>
                                {/foreach}
                            </select>
                        </div>

                        <div class="col-sm-2 col-xs-12">
                            <input type="text" name="tech-postcode-{$i}" placeholder="{$lang.DOMAINS.POSTCODE}" value="{if $user}{$user.postcode|htmlentities}{/if}" class="form-control" />
                        </div>

                        <div class="col-sm-8 col-xs-12">
                            <input type="text" name="tech-city-{$i}" placeholder="{$lang.DOMAINS.CITY}" value="{if $user}{$user.city|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-4">
                            <input type="text" name="tech-telephone-{$i}" placeholder="{$lang.DOMAINS.PHONE}" value="{if $user}{$user.telephone|htmlentities}{/if}" class="form-control" />
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="tech-telefax-{$i}" placeholder="{$lang.DOMAINS.FAXR}" value="{if $user}{$user.fax|htmlentities}{/if}" class="form-control" />
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="tech-email-{$i}" placeholder="{$lang.DOMAINS.EMAIL}" value="{if $user}{$user.mail|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                </span>
            </div>
            <div role="tabpanel" class="tab-pane" id="zone-{$i}">
                <div class="radio">
                  <label>
                    <input type="radio" name="zone-option-{$i}" class="zone-option" data-i="{$i}" id="zone-option-{$i}" value="isp" checked="checked">
                    {$lang.DOMAINS.OURDATA|replace:"%p":$cfg.PAGENAME}
                  </label>
                </div>
                <div class="radio">
                  <label>
                    <input type="radio" name="zone-option-{$i}" class="zone-option" data-i="{$i}" id="zone-option-{$i}" value="same">
                    {$lang.DOMAINS.COPYTECH}
                  </label>
                </div>
                <div class="radio">
                  <label>
                    <input type="radio" name="zone-option-{$i}" class="zone-option" data-i="{$i}" id="zone-option-{$i}" value="new">
                    {$lang.DOMAINS.NEWSET}
                  </label>
                </div>

                <span class="new3" id="new3-{$i}" data-i="{$i}" style="display: none;"><br />
                    <div class="row">
                        <div class="col-sm-6 col-xs-12">
                            <input type="text" name="zone-firstname-{$i}" placeholder="{$lang.DOMAINS.FIRSTNAME}" value="{if $user}{$user.firstname|htmlentities}{/if}" class="form-control" />
                        </div>

                        <div class="col-sm-6 col-xs-12">
                            <input type="text" name="zone-lastname-{$i}" placeholder="{$lang.DOMAINS.LASTNAME}" value="{if $user}{$user.lastname|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-12">
                            <input type="text" name="zone-company-{$i}" placeholder="{$lang.DOMAINS.COMPANY}" value="{if $user}{$user.company|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-12">
                            <input type="text" name="zone-street-{$i}" placeholder="{$lang.DOMAINS.ADDRESS}" value="{if $user}{$user.street|htmlentities} {$user.street_number|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-2 col-xs-12">
                            <select name="zone-country-{$i}" class="form-control">
                                {foreach from=$countries item=country key=id}
                                <option{if $user && $user.country == $id} selected="selected"{/if}>{$country}</option>
                                {/foreach}
                            </select>
                        </div>

                        <div class="col-sm-2 col-xs-12">
                            <input type="text" name="zone-postcode-{$i}" placeholder="{$lang.DOMAINS.POSTCODE}" value="{if $user}{$user.postcode|htmlentities}{/if}" class="form-control" />
                        </div>

                        <div class="col-sm-8 col-xs-12">
                            <input type="text" name="zone-city-{$i}" placeholder="{$lang.DOMAINS.CITY}" value="{if $user}{$user.city|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-4">
                            <input type="text" name="zone-telephone-{$i}" placeholder="{$lang.DOMAINS.PHONE}" value="{if $user}{$user.telephone|htmlentities}{/if}" class="form-control" />
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="zone-{$lang.DOMAINS.FAXR}-{$i}" placeholder="Telefax" value="{if $user}{$user.fax|htmlentities}{/if}" class="form-control" />
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="zone-email-{$i}" placeholder="{$lang.DOMAINS.EMAIL}" value="{if $user}{$user.mail|htmlentities}{/if}" class="form-control" />
                        </div>
                    </div>
                </span>
            </div>
            <div role="tabpanel" class="tab-pane" id="dns-{$i}">
                <div class="radio">
                  <label>
                    <input type="radio" name="dns-option-{$i}" class="dns-option" data-i="{$i}" id="dns-option-{$i}" value="isp" checked="checked">
                    {$lang.DOMAINS.OURNS|replace:"%p":$cfg.PAGENAME}
                  </label>
                </div>
                <div class="radio">
                  <label>
                    <input type="radio" name="dns-option-{$i}" class="dns-option" data-i="{$i}" id="dns-option-{$i}" value="own">
                    {$lang.DOMAINS.OWNNS}
                  </label>
                </div>

                <span id="own-{$i}" class="own" data-i="{$i}" style="display: none;"><br /><label>{$lang.DOMAINS.NS|replace:"%n":"1"}</label>
                <input type="text" name="ns1-{$i}" placeholder="{$cfg.NS1}" class="form-control" />

                <br /><label>{$lang.DOMAINS.NS|replace:"%n":"2"}</label>
                <input type="text" name="ns2-{$i}" placeholder="{$cfg.NS2}" class="form-control" />

                <br /><label>{$lang.DOMAINS.NS|replace:"%n":"3"}</label>
                <input type="text" name="ns3-{$i}" placeholder="{$lang.DOMAINS.NSOPTIONAL}" class="form-control" />

                <br /><label>{$lang.DOMAINS.NS|replace:"%n":"4"}</label>
                <input type="text" name="ns4-{$i}" placeholder="{$lang.DOMAINS.NSOPTIONAL}" class="form-control" />

                <br /><label>{$lang.DOMAINS.NS|replace:"%n":"5"}</label>
                <input type="text" name="ns5-{$i}" placeholder="{$lang.DOMAINS.NSOPTIONAL}" class="form-control" />

                <br /><small>{$lang.DOMAINS.GLUE}</small></span>

                <span id="isp-{$i}" class="isp" data-i="{$i}">
                {if $hosting_contracts|count}
                <br /><label>{$lang.DOMAINS.HOSTING_CONTRACT}</label>
                <select class="form-control" id="hosting_contract_{$i}">
                    <option value="#" selected="">{$lang.DOMAINS.CHOOSE_HC}</option>
                    {foreach from=$hosting_contracts key=id item=hc}
                    <option value="{$id}#{$hc.ip}">{$hc.desc}</option>
                    {/foreach}
                </select>

                <script>
                $("#hosting_contract_{$i}").change(function() {
                    var ex = $(this).val().split("#");
                    var ip = ex[1];
                    if (ip) {
                        $("[name=ipv4-{$i}]").prop("disabled", true).val(ip);
                        $("[name=ipv6-{$i}]").prop("disabled", true).val("");
                    } else {
                        $("[name=ipv4-{$i}]").prop("disabled", false);
                        $("[name=ipv6-{$i}]").prop("disabled", false);
                    }
                });
                </script>
                {/if}
                
                <br /><label>{$lang.DOMAINS.IP4}</label>
                <input type="text" name="ipv4-{$i}" placeholder="5.9.7.9" class="form-control" />

                <br /><label>{$lang.DOMAINS.IP6}</label>
                <input type="text" name="ipv6-{$i}" placeholder="{$lang.DOMAINS.NSOPTIONAL}" class="form-control" />

                <br /><small>{$lang.DOMAINS.ZONECUSTOM}</small></span>
            </div>
            {if array_key_exists($domain, $privacy)}
            <div role="tabpanel" class="tab-pane privacy" id="privacy-{$i}" data-i="{$i}" style="text-align: justify;">
                {$lang.DOMAINS.PRIVACY_INTRO}

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="privacy-{$i}" class="checkbox" />
                        {$lang.DOMAINS.PRIVACY_CHECK|replace:"%u":$cfg.PAGEURL|replace:"%d":$domain|replace:"%p":$privacy.$domain}
                    </label>
                </div>
            </div>
            {/if}
            <div role="tabpanel" class="tab-pane transfer" id="transfer-{$i}" data-i="{$i}" style="text-align: justify;">
                {$lang.DOMAINS.TRANSFER_INTRO}

                <br /><br /><label>{$lang.DOMAINS.TRANSFER_AUTH}</label>
                <input type="text" name="auth-{$i}" class="form-control" />

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="ok-{$i}" class="checkbox" />
                        {$lang.DOMAINS.TRANSFER_AGREE}
                    </label>
                </div>
            </div>
        </div>
      </div>
    </div>{/foreach}
    
    {if $alternatives|@count}
    <div class="panel panel-info">
        <div class="panel-heading">{$lang.DOMAINS.SUGGESTIONS}</div>
        <div class="panel-body" style="margin-bottom: -10px;">
            <div class="row">
                {foreach from=$alternatives item=sug}
                <div class="col-md-6" style="margin-bottom: 10px;">
                    {$sug|htmlentities}
                </div>
                {/foreach}
            </div>
        </div>
    </div>
    {/if}
    
    <a href="#" class="btn btn-primary btn-block" id="buy">{$lang.DOMAINS.BUY}</a></div></div>

    <div id="finished" class="price-plan" style="display: none; padding-top: 20px;">
        <center>
            <h2 class="title"><i class="fa fa-check"></i> {$lang.DOMAINS.DONE1}</h2>
            <p class="intro">{$lang.DOMAINS.DONE2|replace:"%u":$cfg.PAGEURL}</p>
        </center>
    </div><br />

    <script>
    $(".domain_panel").each(function() {
        var w = $(this).find('.domain_waiting2');
        var i = $(this).data("i");
        var p = $(this).find('.panel-body');
        var d = $("#domain-" + i).html();
        w.html('<i class="fa fa-spinner fa-spin"></i> ');

        $.get("{$cfg.PAGEURL}domains/check", {
            tld: d,
        }, function(r) {
            var data = JSON.parse(r);
            if(data.status == "n") {
                $("#tbtn-" + i).show();
                w.html('<span class="label label-warning">{$lang.DOMAINS.STATUSN}</span> <span class="label label-default">' + data.price + '</span> ');
                p.slideDown();
            } else if(data.status == "y") {
                w.html('<span class="label label-success">{$lang.DOMAINS.STATUSY}</span> <span class="label label-default">' + data.price + '</span> ');
                p.slideDown();
            } else {
                w.html('<span class="label label-danger">{$lang.DOMAINS.STATUSE}</span> ');
            }
        });
    });

    $(".dns-option").change(function() {
        if($(this).val() == "own"){
            $("#isp-" + $(this).data("i")).hide();
            $("#own-" + $(this).data("i")).show();
        } else {
            $("#own-" + $(this).data("i")).hide();
            $("#isp-" + $(this).data("i")).show();
        }
    });

    $(".admin-option").change(function() {
        if($(this).val() == "new"){
            $("#new-" + $(this).data("i")).show();
        } else {
            $("#new-" + $(this).data("i")).hide();
        }
    });

    $(".tech-option").change(function() {
        if($(this).val() == "new"){
            $("#new2-" + $(this).data("i")).show();
        } else {
            $("#new2-" + $(this).data("i")).hide();
        }
    });

    $(".zone-option").change(function() {
        if($(this).val() == "new"){
            $("#new3-" + $(this).data("i")).show();
        } else {
            $("#new3-" + $(this).data("i")).hide();
        }
    });

    $("#buy").click(function(e) {
        e.preventDefault();

        if ($(this).attr("disabled")) {
            return;
        }

        $(this).attr("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> {$lang.DOMAINS.WAIT}');

        var has_error = 0;
        $('.domain_panel').each(function(){
            var i = $(this).data("i");
            var d = $("#domain-" + i).html();
            $(this).find('.domain_waiting').html('<i class="fa fa-spinner fa-spin"></i> ');

            var owner = new Array($("[name='owner-firstname-" + i + "']").val(), $("[name='owner-lastname-" + i + "']").val(), $("[name='owner-company-" + i + "']").val(), $("[name='owner-street-" + i + "']").val(), $("[name='owner-country-" + i + "']").val(), $("[name='owner-postcode-" + i + "']").val(), $("[name='owner-city-" + i + "']").val(), $("[name='owner-telephone-" + i + "']").val(), $("[name='owner-telefax-" + i + "']").val(), $("[name='owner-email-" + i + "']").val());

            if($("[name='admin-option-" + i + "']:checked").val() == "same") var admin = owner;
            else var admin = new Array($("[name='admin-firstname-" + i + "']").val(), $("[name='admin-lastname-" + i + "']").val(), $("[name='admin-company-" + i + "']").val(), $("[name='admin-street-" + i + "']").val(), $("[name='admin-country-" + i + "']").val(), $("[name='admin-postcode-" + i + "']").val(), $("[name='admin-city-" + i + "']").val(), $("[name='admin-telephone-" + i + "']").val(), $("[name='admin-telefax-" + i + "']").val(), $("[name='admin-email-" + i + "']").val());

            var tech = new Array();
            if($("#tech-" + i).length > 0){
                if($("[name='tech-option-" + i + "']:checked").val() == "same") var tech = admin;
                else if($("[name='tech-option-" + i + "']:checked").val() == "new") var tech = new Array($("[name='tech-firstname-" + i + "']").val(), $("[name='tech-lastname-" + i + "']").val(), $("[name='tech-company-" + i + "']").val(), $("[name='tech-street-" + i + "']").val(), $("[name='tech-country-" + i + "']").val(), $("[name='tech-postcode-" + i + "']").val(), $("[name='tech-city-" + i + "']").val(), $("[name='tech-telephone-" + i + "']").val(), $("[name='tech-telefax-" + i + "']").val(), $("[name='tech-email-" + i + "']").val());
                else var tech = "isp";
            }

            var zone = new Array();
            if($("#zone-" + i).length > 0){
                if($("[name='zone-option-" + i + "']:checked").val() == "same") var zone = tech;
                else if($("[name='zone-option-" + i + "']:checked").val() == "new") var zone = new Array($("[name='zone-firstname-" + i + "']").val(), $("[name='zone-lastname-" + i + "']").val(), $("[name='zone-company-" + i + "']").val(), $("[name='zone-street-" + i + "']").val(), $("[name='zone-country-" + i + "']").val(), $("[name='zone-postcode-" + i + "']").val(), $("[name='zone-city-" + i + "']").val(), $("[name='zone-telephone-" + i + "']").val(), $("[name='zone-telefax-" + i + "']").val(), $("[name='zone-email-" + i + "']").val());
                else var zone = "isp";
            }

            if($("[name='dns-option-" + i + "']:checked").val() == "isp") var ns = new Array($("[name='ipv4-" + i + "']").val(), $("[name='ipv6-" + i + "']").val());
            else var ns = new Array($("[name='ns1-" + i + "']").val(), $("[name='ns2-" + i + "']").val(), $("[name='ns3-" + i + "']").val(), $("[name='ns4-" + i + "']").val(), $("[name='ns5-" + i + "']").val());

            var transfer = new Array();
            if($("#transfer-" + i).length > 0) var transfer = new Array($("[name='auth-" + i + "']").val(), $("[name='ok-" + i + "']").prop('checked'));
            var privacy = $("[name='privacy-" + i + "']").prop('checked');

            $.post('{$cfg.PAGEURL}domains/cart', {
                domain: d,
                owner: owner,
                admin: admin,
                tech: tech,
                zone: zone,
                ns: ns,
                transfer: transfer,
                privacy: privacy,
                hosting_contract: $("#hosting_contract_{$i}").val(),
                "csrf_token": "{ct}",
            }, function(r){
                if(r == "ok"){
                    $("#panel-" + i).addClass('panel-success').removeClass('domain_panel').find('.panel-body').slideUp();
                } else {
                    has_error = 1;
                    $("#error-" + i).show().html(r);
                }

                $("#panel-" + i).find('.domain_waiting').html('');
            });
        });


        $(document).ajaxStop(function(){
            if(has_error > 0){
                $("#buy").attr("disabled", false).html('{$lang.DOMAINS.BUY}');
            } else {
                $("#configure").slideUp(function() {
                    $("#finished").slideDown();
                });
            }
        });
    });

    // Copy clicked
    </script>
    {/if}

    {if $step == "extensions"}
    <div class="row"><div class="col-md-8 col-md-offset-2"><form method="POST"><div class="input-group input-group-lg input-group-box">
        <input type="text" class="form-control" placeholder="{$lang.DOMAINS.CTA}" value="{if isset($smarty.post.domain)}{$smarty.post.domain|htmlentities}{/if}" name="domain">
        <span class="input-group-btn">
            <button type="submit" class="btn btn-primary">{$lang.DOMAINS.SEARCH}</button>
        </span>
    </div></form><br />

    <form method="POST"><input type="hidden" value="{if isset($smarty.post.domain)}{$smarty.post.domain|htmlentities}{/if}" name="domain"><input type="hidden" name="sld" id="sld" value="{$smarty.post.domain|htmlentities}" /><div class="panel panel-default">
        <div class="panel-heading">{$lang.DOMAINS.POPULAR}</div>
        <div class="panel-body tld_row">
            {foreach from=$pricing item=i}
                <label style="font-weight: normal;"><input type="checkbox" name="tld[]" value="{$i.tld}" id="checkbox_{$i.tld}" class="check_tld" style="display: none;" />
                <i class="fa fa-spinner fa-spin" id="spinner_{$i.tld}"></i>
                <span class="tld" id="tld_{$i.tld}" data-tld="{$i.tld}">.{$i.tld}</span></label> <a href="{$cfg.PAGEURL}tld/{$i.tld}" target="_blank"><i class="fa fa-info"></i></a>
                <br />
            {/foreach}
        </div>
    </div>

    <script>
    $(".tld").each(function() {
        var sld = $("#sld").val();
        var tld = $(this).data('tld');

        $.get("{$cfg.PAGEURL}domains/check", {
            sld: sld,
            tld: tld,
        }, function(r) {
            var data = JSON.parse(r);

            if(data.status == "y"){
                $("#spinner_" + tld).hide();
                $("#tld_" + tld).append(' <span class="label label-success">{$lang.DOMAINS.STATUSY}</span> <span class="label label-default">' + data.price + '</span>');
                $("#checkbox_" + tld).show();
            } else if(data.status == "n"){
                $("#spinner_" + tld).hide();
                $("#tld_" + tld).append(' <span class="label label-warning">{$lang.DOMAINS.STATUSN}</span> <span class="label label-default">' + data.price + '</span>');
                $("#checkbox_" + tld).show();
            } else {
                $("#spinner_" + tld).css('color', 'red').removeClass('fa-spin fa-spinner').addClass('fa-times');
                $("#tld_" + tld).css('color', 'red');
            }
        });
    });

    $(".check_tld").change(function() {
        if($(".tld_row").find('.check_tld:checked').length > 0) $(".order").prop("disabled", false);
        else $(".order").prop("disabled", true);
    });
    </script>

    <input type="submit" value="{$lang.DOMAINS.GOCONFIG}" class="btn btn-primary btn-block order" disabled="disabled" /></form></div></div>
    {/if}

    {if $step == "overview"}<div class="text-center price-plan" style="padding: 0;">
        <h2 class="title">{$lang.DOMAINS.INTRO|replace:"%p":$cfg.PAGENAME}</h2>
        <p class="intro">{$lang.DOMAINS.INTRO2|replace:"%c":$count}</p>
    </div>

    {if isset($error)}<div class="alert alert-danger col-md-6 col-md-offset-3">{$error}</div>{/if}

    <form method="POST"><div class="input-group input-group-lg input-group-box col-md-6 col-md-offset-3">
        <input type="text" class="form-control" placeholder="{$lang.DOMAINS.CTA}" value="{if isset($smarty.post.domain)}{$smarty.post.domain|htmlentities}{/if}" name="domain">
        <span class="input-group-btn">
            <button type="submit" class="btn btn-primary">{$lang.DOMAINS.SEARCH}</button>
        </span>
    </div></form><br /><br />{/if}

    {if $step == "overview" || $step == "pricing"}{if $tax > 0}
    	<small>{if $net}<a href="{$cfg.PAGEURL}domains{if $step == "pricing"}/pricing{/if}">{$lang.DOMAINS.GROSS}</a>{else}<a href="{$cfg.PAGEURL}domains{if $step == "pricing"}/pricing{/if}/net">{$lang.DOMAINS.NET}</a>{/if}</small>

    	<small class="pull-right">{if $net}{$lang.DOMAINS.ISNET}{else}{if $logged_in}
    	{$lang.DOMAINS.TAX1|replace:"%t":$tax} {if $tax_name}{$tax_name}{else}{$lang.DOMAINS.TAX2}{/if}{if $tax_country} {$lang.DOMAINS.TAX3|replace:"%c":$tax_country}{/if}.
    	{else}
    	{$lang.DOMAINS.TAX4|replace:"%t":$tax} {if $tax_name}{$tax_name}{else}{$lang.DOMAINS.TAX2}{/if}{if $tax_country} {$lang.DOMAINS.TAX3|replace:"%c":$tax_country}{/if}. {$lang.DOMAINS.TAX5}
    	{/if}{/if}</small><br />
    {else if $cfg.TAXES}
    	<small class="pull-right">{if $logged_in}
    	{$lang.DOMAINS.TAX6}
    	{else}
    	{$lang.DOMAINS.TAX7} {$lang.DOMAINS.TAX5}
    	{/if}</small><br />
    {/if}
  	<div class="table-responsive">
		<table class="table table-striped">
            {if $step == "pricing"}
            <tr>
                <td colspan="10"><center>
                    <a href="{$cfg.PAGEURL}domains/pricing{if $net}/net{/if}" class="btn {if !$begin}btn-primary{else}btn-default{/if} btn-xs">{$lang.DOMAINS.ALL}</a>
                    {for $i=65 to 90}
                        <a href="{$cfg.PAGEURL}domains/pricing{if $net}/net{/if}/{$i|chr}" class="btn {if $begin == strtolower(chr($i))}btn-primary{else}btn-default{/if} btn-xs">{$i|chr}</a>
                    {/for}
                </center></td>
            </tr>
            {/if}

            {assign "hasfreessl" "0"}
            {assign "hasprivacy" "0"}
            {assign "hasauth2" "0"}
            {foreach from=$pricing item=d}
                {if $d.freessl}{assign "hasfreessl" "1"}{/if}
                {if $d.privacy_raw >= 0}{assign "hasprivacy" "1"}{/if}
                {if $d.auth2}{assign "hasauth2" "1"}{/if}
            {/foreach}

			<tr>
                {if $hasfreessl}<th style="width: 20px;"></th>{/if}
                {if $hasprivacy}<th style="width: 20px;"></th>{/if}
                {if $hasauth2}<th style="width: 20px;"></th>{/if}
    			<th style="width: 13%;">{$lang.DOMAINS.DOMAIN}</th>
    			<th style="text-align: right; width: 10%;">{$lang.DOMAINS.PERIOD}</th>
    			<th style="text-align: right; width: 17%;">{$lang.DOMAINS.REG2}</th>
    			<th style="text-align: right; width: 17%;">{$lang.DOMAINS.TRANS}</th>
                <th style="text-align: right; width: 17%;">{$lang.DOMAINS.RENEW}</th>
    			<th style="text-align: right; width: 17%;">{$lang.DOMAINS.TRADE}</th>
    		</tr>

    		{foreach from=$pricing key=i item=d}
    		<tr>
                {if $hasfreessl}<td>{if $d.freessl}<a href="{$cfg.PAGEURL}freessl" target="_blank"><img src="{$raw_cfg.PAGEURL}images/ssl.png" alt="{$lang.DOMAINS.SSL}" title="{$lang.DOMAINS.SSL}" style="height: 20px; width: auto;" /></a>{/if}</td>{/if}
                {if $hasprivacy}<td>{if $d.privacy_raw >= 0}<a href="#" data-toggle="modal" data-target="#privacy_{$i}"><img src="{$raw_cfg.PAGEURL}images/privacy.png" alt="{$lang.DOMAINS.PRIVACY2}" title="{$lang.DOMAINS.PRIVACY2}" style="height: 20px; width: auto;" /></a>{/if}</td>{/if}
                {if $hasauth2}<td>{if $d.auth2}<a href="{$cfg.PAGEURL}auth2/{$d.tld}" target="_blank"><img src="{$raw_cfg.PAGEURL}images/auth2.png" alt="{$lang.DOMAINS.AUTH2}" title="{$lang.DOMAINS.AUTH2}" style="height: 20px; width: auto;" /></a>{/if}</td>{/if}
    			<td><a href="{$cfg.PAGEURL}tld/{$d.tld}">.{$d.tld}</a></td>
    			<td style="text-align: right;">{$d.period} {if $d.period == 1}{$lang.DOMAINS.P1}{else}{$lang.DOMAINS.PX}{/if}</td>
    			<td style="text-align: right;">{$d.register}</td>
    			<td style="text-align: right;">{$d.transfer}</td>
                <td style="text-align: right;">{$d.renew}</td>
    			<td style="text-align: right;">{$d.trade}</td>
    		</tr>
    		{/foreach}
		</table>
	</div>

    {foreach from=$pricing key=i item=d}{if $d.privacy_raw >= 0}
    <div class="modal fade" id="privacy_{$i}" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Schlie&szlig;en"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{$lang.DOMAINS.PRIVACY2}</h4>
          </div>
          <div class="modal-body">
            <p style="text-align: justify;">
                {$lang.DOMAINS.PV1}<br /><br />{$lang.DOMAINS.PV2}<br /><br />{if $d.privacy_raw == 0}{$lang.DOMAINS.PV3|replace:"%t":$d.tld}{else}{$lang.DOMAINS.PV4|replace:"%t":$d.tld|replace:"%p":$d.privacy}{/if}<br /><br /><small>{$lang.DOMAINS.PV5}</small>
            </p>
          </div>
        </div>
      </div>
    </div>
    {/if}{/foreach}
    {/if}

	{if $step == "overview"}<span style="float: left; padding-bottom: 20px;"><a href="{$cfg.PAGEURL}domains/pricing">{$lang.DOMAINS.PRICINGFULL} &raquo;</a><br /><a href="{$cfg.PAGEURL}domains/api">{$lang.DOMAINS.API} &raquo;</a></span>{/if}

    {if $step == "dyndns"}
    <p style="text-align: justify;">{$lang.DOMAINS.DYN1}

    <br /><div class="row">
        <div class="col-md-4 col-xs-12">
            <img src="{$raw_cfg.PAGEURL}images/dyn1.png" class="img-responsive" title="{$lang.DOMAINS.DYN2}" alt="{$lang.DOMAINS.DYN2}" style="margin-bottom: 10px;" />
            <small>{$lang.DOMAINS.DYN2}</small>
        </div>
        <div class="col-md-4 col-xs-12">
            <img src="{$raw_cfg.PAGEURL}images/dyn2.png" class="img-responsive" title="{$lang.DOMAINS.DYN3}" alt="{$lang.DOMAINS.DYN3}" style="margin-bottom: 10px;" />
            <small>{$lang.DOMAINS.DYN3}</small>
        </div>
        <div class="col-md-4 col-xs-12">
            <img src="{$raw_cfg.PAGEURL}images/dyn3.png" class="img-responsive" title="{$lang.DOMAINS.DYN4}" alt="{$lang.DOMAINS.DYN4}" style="margin-bottom: 10px;" />
            <small>{$lang.DOMAINS.DYN4}</small>
        </div>
    </div>
    {/if}

	{if $step == "robot"}
    {if isset($success)}
    <div class="alert alert-success">{$success}</div>
    {/if}
    {if isset($error)}
    <div class="alert alert-danger">{$error}</div>
    {/if}
	<p style="text-align: justify;">{$lang.DOMAINS.ROBOT1}</p>

    <div id="myCarousel" style="margin-top: 20px;" class="carousel slide" data-ride="carousel">
      <!-- Indicators -->
      <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
        <li data-target="#myCarousel" data-slide-to="2"></li>
        <li data-target="#myCarousel" data-slide-to="3"></li>
        <li data-target="#myCarousel" data-slide-to="4"></li>
        <li data-target="#myCarousel" data-slide-to="5"></li>
        <li data-target="#myCarousel" data-slide-to="6"></li>
        <li data-target="#myCarousel" data-slide-to="7"></li>
      </ol>

      <!-- Wrapper for slides -->
      <div class="carousel-inner" role="listbox">
        <div class="item active" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/1.png">
          <div class="carousel-caption">
            <h3>Domainansicht</h3>
            <p>Alle Informationen auf einen Blick</p>
          </div>
        </div>

        <div class="item" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/2.png">
          <div class="carousel-caption">
            <h3>Massenaktionen</h3>
            <p>Viele gleiche Aktionen schnell durchf&uuml;hren</p>
          </div>
        </div>

        <div class="item" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/3.png">
          <div class="carousel-caption">
            <h3>Registrierung</h3>
            <p>Schnelle Registrierung ohne viel Aufwand</p>
          </div>
        </div>

        <div class="item" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/4.png">
          <div class="carousel-caption">
            <h3>Benutzer</h3>
            <p>Verkaufen Sie Domains an Endkunden weiter</p>
          </div>
        </div>

        <div class="item" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/5.png">
          <div class="carousel-caption">
            <h3>Zone erstellen</h3>
            <p>Einfach und f&uuml;r Profis</p>
          </div>
        </div>

        <div class="item" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/6.png">
          <div class="carousel-caption">
            <h3>Zonenverwaltung</h3>
            <p>Intuitiv und schnell</p>
          </div>
        </div>

        <div class="item" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/7.png">
          <div class="carousel-caption">
            <h3>Handles</h3>
            <p>Beliebig viele Handles f&uuml;r sich selbst und Kunden verwalten</p>
          </div>
        </div>

        <div class="item" style="height: 600px; overflow: hidden;">
          <img src="{$raw_cfg.PAGEURL}modules/addons/domaingate/images/8.png">
          <div class="carousel-caption">
            <h3>Abrechnung</h3>
            <p>Auf einen Blick eine &uuml;bersichtliche und detaillierte Abrechnung f&uuml;r Benutzer</p>
          </div>
        </div>
      </div>
    </div><br />

    {if !$logged_in}
    <div class="alert alert-info">{$lang.DOMAINS.ROBOT2|replace:"%u":$cfg.PAGEURL}</div>
    {else}
    {if !$user.domain_api}
    <div class="alert alert-info">{$lang.DOMAINS.ROBOT3|replace:"%u":$cfg.PAGEURL}</div>
    {else}
    {if empty($user.domaingate_user)}
    <p style="text-align: justify">{$lang.DOMAINS.ROBOT4}</p>

    <form method="POST" action="{$cfg.PAGEURL}domains/robot" class="form-inline">
        <input type="password" name="pw" class="form-control" placeholder="kL1oPq,a">
        <input type="password" name="pw2" class="form-control" placeholder="{$lang.DOMAINS.ROBOT9}">
        <input type="submit" class="btn btn-primary" value="{$lang.DOMAINS.ROBOT13}">
    </form><br />
    {else}
    <p style="text-align: justify">{$lang.DOMAINS.ROBOT5}</p>
    <form method="POST" action="{$cfg.PAGEURL}domains/robot">
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <tr>
                <th width="40%">{$lang.DOMAINS.ROBOT6}</th>
                <td><a href="{$url}" target="_blank">{$url}</a></td>
            </tr>

            <tr>
                <th width="40%">{$lang.DOMAINS.ROBOT7}</th>
                <td>{$user.domaingate_user}</td>
            </tr>

            <tr>
                <th width="40%" style="vertical-align: middle;">{$lang.DOMAINS.ROBOT8}</th>
                <td>
                    <input type="password" name="npw" class="form-control input-sm" placeholder="kL1oPq,a">
                </td>
            </tr>

            <tr>
                <th width="40%" style="vertical-align: middle;">{$lang.DOMAINS.ROBOT9}</th>
                <td>
                    <input type="password" name="npw2" class="form-control input-sm" placeholder="kL1oPq,a">
                </td>
            </tr>
        </table>
    </div>
    <input type="submit" class="btn btn-primary btn-block" style="margin-top: -10px;" value="{$lang.DOMAINS.ROBOT10}">
    </form><br />

    <small><a href="{$cfg.PAGEURL}domains/robot/delete" onclick="return confirm('{$lang.DOMAINS.ROBOT12}');" style="color: red">{$lang.DOMAINS.ROBOT11}</a></small><br /><br />
    {/if}
    {/if}
    {/if}
	{/if}

    {if $step == "api"}
    {if $user.domain_api}
    <p style="text-align: justify;">{$lang.DOMAINS.PREPAID1}{if $user.postpaid > 0} {$lang.DOMAINS.PREPAID2}{/if}</p>

    <b>{$lang.DOMAINS.USERID}</b>: {$user.ID}<br />
    <b>{$lang.DOMAINS.APIKEY}</b>: {$user.api_key|htmlentities} &nbsp;<a href="{$cfg.PAGEURL}domains/api/reset"><i class="fa fa-refresh"></i></a><br />
    {if $user.postpaid > 0}<br />{/if}<b>{$lang.DOMAINS.BILLING}</b>: {if $user.postpaid <= 0}<font color="red">{$lang.DOMAINS.PREPAID}</font><br /><br />{else}<font color="green">{$lang.DOMAINS.POSTPAID}</font><br /><b>{$lang.DOMAINS.LIMIT}</b>: {$postpaid_limit}<br /><b>{$lang.DOMAINS.USED}</b>: {$postpaid_used}<br /><b>{$lang.DOMAINS.LEFT}</b>: {$postpaid_left}<br /><br />{/if}

    <a href="https://wiki.sourceway.de/index.php?title=API" target="_blank"><i class="fa fa-file-pdf-o"></i> {$lang.DOMAINS.APIDOC}</a><br />
    <a href="{$raw_cfg.PAGEURL}files/system/WHMCS-Domain-Module.zip" target="_blank"><i class="fa fa-file-archive-o"></i> {$lang.DOMAINS.WHMCS1}</a><br />
    <a href="{$raw_cfg.PAGEURL}files/system/WHMCS-Domain-Import.zip" target="_blank"><i class="fa fa-file-archive-o"></i> {$lang.DOMAINS.WHMCS2}</a> <a href="#" data-toggle="popover" title="{$lang.DOMAINS.WHMCS3}" data-content="{$lang.DOMAINS.WHMCS4}"><i class="fa fa-question-circle"></i></a><br />
    <a href="{$cfg.PAGEURL}domains/pricing.csv" target="_blank"><i class="fa fa-file-excel-o"></i> {$lang.DOMAINS.CSV1}</a> <a href="#" data-toggle="popover" title="{$lang.DOMAINS.CSV2}" data-content="{$lang.DOMAINS.CSV3|replace:"%c":$currency}"><i class="fa fa-question-circle"></i></a>

    <script>
    $(function () {
      $('[data-toggle="popover"]').popover();
    });
    </script>
    {else}
    {if $logged_in}
    <p style="text-align: justify; margin-bottom: 20px;">{$lang.DOMAINS.API1|replace:"%p":$cfg.PAGENAME|replace:"%n":$needed}</p>

    {if $user.credit < 100}<div class="alert alert-warning" style="margin-bottom: 10px;">{$lang.DOMAINS.API2|replace:"%m":$missing}</div>{/if}

    {if $user.credit >= 100}<a href="{$cfg.PAGEURL}domains/api/activate" class="btn btn-primary btn-block" style="margin-bottom: 10px;">{$lang.DOMAINS.API3}</a>{else}<a href="{$cfg.PAGEURL}credit" target="_blank" class="btn btn-default btn-block" style="margin-bottom: 10px;">{$lang.DOMAINS.API4}</a>{/if}

    <small>{$lang.DOMAINS.API5}</small>
    {else}
    <p style="text-align: justify;">{$lang.DOMAINS.API6}</p>

    <p style="text-align: justify;">{$lang.DOMAINS.API7}</p>

    <p style="text-align: justify;">{$lang.DOMAINS.API8}<br></p>
    <div class="row" style="margin-bottom: 10px;">
        <div class="col-sm-6">
            <a href="#" onclick="return false;" data-toggle="modal" data-target="#login-modal" class="btn btn-primary btn-block">{$lang.GENERAL.LOGIN}</a>
        </div>
        <div class="col-sm-6">
            <a href="{$cfg.PAGEURL}register" class="btn btn-default btn-block">{$lang.REGISTER.TITLE}</a>
        </div>
    </div>

    <small>{$lang.DOMAINS.API9|replace:"%n":$needed}<br />{$lang.DOMAINS.API10}</small>
    {/if}
    {/if}
    {/if}

    {if $currencies|@count > 1}
	{if $step == "pricing" || $step == "overview"}<form method="POST" class="form-inline" style="float: right; display: inline; padding-bottom: 20px;"><select name="currency" class="form-control" onchange="form.submit()"><option disabled>- {$lang.GENERAL.CHOOSE_CURRENCY} -</option>{foreach from=$currencies item=info key=code}<option value="{$code}"{if $myCurrency == $code} selected="selected"{/if}>{$info.name}</option>{/foreach}</select></form>{/if}
    {/if}
</div>

<br /><div class="container"><small>{$lang.DOMAINS.PRICING_HINT}</small></div>