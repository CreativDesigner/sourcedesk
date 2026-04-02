<div id="content">
    <div class="container">
        <h1>{$lang.AFFILIATE.TITLE}</h1><hr />

        {if isset($suc)}<div class="alert alert-success">{$suc}</div>{/if}

        <div class="row">
            <div class="col-lg-4 col-md-4">
                <div class="card">
                    <div class="card-body" style="margin-bottom: -10px;">
                        <div style="text-align: right; margin-top: -10px;">
                            <div style="font-size: 40px;">{$credit_f}</div>
                            <h6 class="card-subtitle mb-2 text-muted" style="margin-top: 5px;">{$lang.AFFILIATE.CREDIT}</h6>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-4">
                <div class="card">
                    <div class="card-body" style="margin-bottom: -10px;">
                        <div style="text-align: right; margin-top: -10px;">
                            <div style="font-size: 40px;">{$free_f}</div>
                            <h6 class="card-subtitle mb-2 text-muted" style="margin-top: 5px;">{$lang.AFFILIATE.FREE}</h6>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-4">
                <div class="card">
                    <div class="card-body" style="margin-bottom: -10px;">
                        <div style="text-align: right; margin-top: -10px;">
                            <div style="font-size: 40px;">{$waiting_f}</div>
                            <h6 class="card-subtitle mb-2 text-muted" style="margin-top: 5px;">{$lang.AFFILIATE.WAITING}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {if $free > 0}
        <form method="POST">
            {if $free < $min}
            <div class="alert alert-warning">{$lang.AFFILIATE.NOTENOUGH|replace:"%m":$min}</div>
            {/if}
            <div class="radio">
              <label>
                <input type="radio" name="withdraw_method" value="credit" checked>
                {$lang.AFFILIATE.W1}
              </label>
            </div>

            {if $free >= $min}
            <div class="radio">
              <label>
                <input type="radio" name="withdraw_method" value="cashout">
                {$lang.AFFILIATE.W2}
              </label>
            </div>
            {/if}

            <input type="hidden" name="action" value="withdraw">
            <input type="submit" value="{$lang.AFFILIATE.WITHDRAW}" class="btn btn-primary btn-block">
        </form>
        {/if}

        <h1{if $free > 0} style="margin-top: 40px;"{/if}>{$lang.AFFILIATE.LINKS}</h1><hr />
        <p style="text-align: justify;">{$lang.AFFILIATE.LINK|replace:"%u":$user.ID|replace:"%p":$cfg.PAGEURL|replace:"%d":$cfg.AFFILIATE_DAYS|replace:"%m":$min_f|replace:"%c":$cfg.AFFILIATE_COOKIE}</p>
        
        <h1 style="margin-top: 30px;">{$lang.AFFILIATE.PROVISIONS}</h1><hr />
        <p style="text-align: justify;">{$lang.AFFILIATE.PINTRO}</p>

        <form method="POST">
            <select name="product" class="form-control" onchange="form.submit();">
                <option value="" selected="selected" disabled="disabled">{$lang.AFFILIATE.CHOOSE}</option>
                {foreach from=$products item=name key=id}
                <option value="{$id}"{if isset($smarty.post.product) && $smarty.post.product == $id} selected="selected"{/if}>{$name|htmlentities}</option>
                {/foreach}
            </select>
        </form>

        {if isset($ptext)}<p style="text-align: justify; margin-top: 10px;">{$ptext}</p>{/if}

        <h1 style="margin-top: 30px;">{$lang.AFFILIATE.CUSTOMERS}</h1><hr />
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="20%">{$lang.AFFILIATE.REG}</th>
                    <th>{$lang.AFFILIATE.CUST}</th>
                    <th width="15%">{$lang.AFFILIATE.WAIT}</th>
                    <th width="15%">{$lang.AFFILIATE.CANCELLED}</th>
                    <th width="15%">{$lang.AFFILIATE.FINISHED}</th>
                </tr>

                {foreach from=$clients item=c}
                <tr>
                    <td>{$c.0}</td>
                    <td>{$c.1|htmlentities}</td>
                    <td>{$c.2}</td>
                    <td>{$c.3}</td>
                    <td>{$c.4}</td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="5"><center>{$lang.AFFILIATE.NOTHING}</center></td>
                </tr>
                {/foreach}
            </table>
        </div>
    </div>
</div><br />