<div class="container">
    <h1>{$lang.CREDIT.PAYIN}{if $user.credit > 0} <small><a href="#" data-toggle="modal" data-target="#transfer_credit">{$lang.CREDIT.TRANSFER}</a></small>{/if}</h1><hr>

    {if $user.credit > 0}
    <div class="modal fade" id="transfer_credit" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">{$lang.CREDIT.TRANSFER_CREDIT}</h4>
            <button id="credit_transfer_hide_times" type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
          </div>
          <form onsubmit="return false;" />
          <div class="modal-body">
            <div class="input-group" id="credit_transfer_amount_group">
              {if $currencyObj->getPrefix()|trim != ""}<div class="input-group-prepend"><span class="input-group-text">{$currencyObj->getPrefix()}</span></div> {/if}<input type="text" value="{$user_credit_f}" class="form-control" placeholder="{nfo i=100}" id="credit_transfer_amount" />{if $currencyObj->getSuffix()|trim != ""} <div class="input-group-append"><span class="input-group-text">{$currencyObj->getSuffix()}</span></div>{/if}
            </div>

            <div class="form-group" id="credit_transfer_recipient_group" style="margin-top: 10px;">
              <input type="text" placeholder="{$lang.CREDIT.MAIL_RECIPIENT}" class="form-control" id="credit_transfer_recipient" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal" id="credit_transfer_hide_button">{$lang.GENERAL.CLOSE}</button>
            <button type="submit" class="btn btn-primary" onclick="javascript:doCreditTransfer(); return false;" id="credit_transfer_submit_button" disabled="true">{$lang.CREDIT.TRANSFER_CREDIT}</button>
          </div>
          </form>
        </div>
      </div>
    </div>

    <script type="text/javascript">
        var transfer_credit = "{$lang.CREDIT.TRANSFER_CREDIT}";
        var transfered_credit = "{$lang.CREDIT.TRANSFERED_CREDIT}";
        var please_wait = "{$lang.GENERAL.PLEASE_WAIT}";
    </script>
    {/if}

    {if $global !== false}{$global}{/if}
    {if isset($smarty.get.cancel)}
    <div class="alert alert-danger">{$lang.CREDIT.PAYIN_ERROR}</div>
    {elseif isset($smarty.get.okay)}
    <div class="alert alert-success">{$lang.CREDIT.PAYIN_SUCCESS}{if ($cart_count) > 0}<br /><b>{$lang.CREDIT.PAYIN_CARTHINT|replace:'%s':$cart_link|replace:'%e':'</a>'}</b>{/if}</div>
    {elseif isset($smarty.get.waiting)}
    <div class="alert alert-info">{$lang.CREDIT.PAYIN_WAITING}</div>
    {/if}

    <div class="row">
        <div class="col-lg-4 col-md-4">
            <div class="card">
                <div class="card-body" style="margin-bottom: -10px;">
                    <div style="text-align: right; margin-top: -10px;">
                      <div style="font-size: 40px;">{$credit_f}</div>
                      <h6 class="card-subtitle mb-2 text-muted" style="margin-top: 5px;">{$lang.CREDIT.CREDIT}</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-4">
            <div style="width: 20%; display: inline-block; float: left; height: 100%; color: darkgrey; font-size: 60px; margin-top: 8px; padding-left: 5px;">
                =
            </div>
            <div class="card" style="width: 80%; display: inline-block; float: right;">
                <div class="card-body" style="margin-bottom: -10px;">
                  <div style="text-align: right; margin-top: -10px;">
                  <div style="font-size: 40px;">{$normal_f}</div>
                  <h6 class="card-subtitle mb-2 text-muted" style="margin-top: 5px;">{$lang.CREDIT.NORMAL}</h6>
                </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-4">
            <div style="width: 20%; display: inline-block; float: left; height: 100%; color: darkgrey; font-size: 60px; margin-top: 8px; padding-left: 5px;">
                +
            </div>
            <div class="card" style="width: 80%; display: inline-block; float: right;">
                <div class="card-body" style="margin-bottom: -10px;">
                  <div style="text-align: right; margin-top: -10px;">
                  <div style="font-size: 40px;">{$special_f}</div>
                  <h6 class="card-subtitle mb-2 text-muted" style="margin-top: 5px;">{$lang.CREDIT.SPECIAL} <a title="{$lang.CREDIT.SPECIAL_HELP}"><i class="fa fa-question"></i></a></h6>
                </div>
                </div>
            </div>
        </div>
    </div>

    <br />
    <div class="accordion" id="paygat">
      {assign var=i value=0}
      {foreach from=$gateways key=gateway item=obj}
      {if $obj->isActive()}
      <div class="card">
        <div class="card-header" id="pg_head_{$i}">
          <h2 class="mb-0">
            <button style="width: 100%;" class="btn btn-link" data-toggle="collapse" data-parent="#paygat" href="#pg_col_{$i}" aria-expanded="true" aria-controls="pg_col_{$i}">
              <span style="float: left;">{if $obj->getLang('frontend_name')|is_string}{$obj->getLang('frontend_name')}{else}{$obj->getLang('name')}{/if}</span>
              <span style="float: right;">
              {$obj->getFeeString()|htmlentities}
              </span>
            </button>
          </h2>
        </div>
        <div id="pg_col_{$i}" class="collapse{if $i == 0} show{/if}" role="tabpanel" aria-labelledby="pg_head_{$i}">
          <div class="card-body">
            {$obj->getPaymentForm($amount)}
          </div>
        </div>
      </div>
      {assign var=i value=$i+1}
      {/if}
      {/foreach}
    </div>

    {if $i == 0}<p><i>{$lang.CREDIT.NO_GATEWAYS}</i></p>{else if isset($cashbox)}<br /><small>{if $cashbox != "locked"}{if $cashbox == "inactive"}<a href="{$cfg.PAGEURL}credit/cashbox/1" class="btn btn-primary btn-sm">{$lang.CREDIT.CASHBOX_ACTIVATE}</a>{else}{$lang.CREDIT.CASHBOX_LINK}: <a href="{$cashbox}" target="_blank">{$cashbox}</a> <a href="{$cfg.PAGEURL}credit/cashbox/0" class="btn btn-primary btn-sm">{$lang.CREDIT.CASHBOX_DEACTIVATE}</a>{/if}{else}<font color="red">{$lang.CREDIT.CASHBOX_LOCKED}</font>{/if}</small>{/if}
</div><br />

{if $automated_gateways|@count}
<div class="container">
    <h1>{$lang.CREDIT.AUTOMATED_PAYMENTS} <small>{if $automated_payments}<span class="label label-success pull-right">{$lang.CREDIT.APS1}</span>{else}<span class="label label-warning pull-right">{$lang.CREDIT.APS0}</span>{/if}</small></h1><hr>
    
    {if !$automated_payments}
    <form method="POST">
        <select name="automated_gateway" class="form-control" onchange="form.submit();">
            <option value="" selected="" disabled="">{$lang.CREDIT.APPC}</option>
            {foreach from=$automated_gateways key=key item=obj}
            <option value="{$key}"{if !empty($smarty.request.automated_gateway) && $smarty.request.automated_gateway == $key} selected=""{/if}>{if ($obj->getLang('frontend_name')|is_string)}{$obj->getLang('frontend_name')}{else}{$obj->getLang('name')}{/if}</option>
            {/foreach}
        </select><br />
    </form>
    {/if}

    {$apcode}
</div><br />
{/if}

<div class="container">
    <h1>{$lang.CREDIT.TRANS}</h1><hr><p>{$lang.CREDIT.TRANSACTIONS}</p>
    <div class="table table-responsive" style="margin-bottom:0;"><table class="table table-bordered">
        <tr>
            <th>{$lang.GENERAL.DATE}</th>
            <th>{$lang.GENERAL.DESCRIPTION}</th>
            <th>{$lang.CREDIT.AMOUNT}</th>
            <th>{$lang.CREDIT.SALDO}</th>
            <th width="30px"></th>
        </tr>
        {foreach from=$trans item=v}
        <tr>
            <td>{$v.time}</td>
            <td>{if !empty($v.cashbox)}<a href="#" onclick="return false;" data-toggle="tooltip" data-original-title="{$lang.CASHBOX.TITLE}: {$v.cashbox|htmlentities}">{/if}{$v.subject|strip_tags}{if !empty($v.cashbox)}</a>{/if}{if $v.waiting} <small><font color="red">({$lang.CREDIT.WAITING})</font></small>{/if}</td>
            <td>{$v.amount_f}</td>
            <td>{$v.saldo_f}</td>
            <td>{if $v.deposit && !$v.waiting}<a href="{$cfg.PAGEURL}credit/receipt/{$v.ID}" target="_blank"><i class="fa fa-file-pdf-o"></i></a>{/if}</td>
        </tr>
        {foreachelse}
        <tr>
            <td colspan="5"><center>{$lang.CREDIT.NOTHING}</center></td>
        </tr>
        {/foreach}
    </table></div>

    {if $currencies|@count > 1}
    <form method="POST" class="form-inline" style="float: right; padding-bottom: 20px;"><select name="currency" class="form-control" onchange="form.submit()"><option disabled>- {$lang.GENERAL.CHOOSE_CURRENCY} -</option>{foreach from=$currencies item=info key=code}<option value="{$code}"{if $myCurrency == $code} selected="selected"{/if}>{$info.name}</option>{/foreach}</select></form>
    {/if}
</div>
