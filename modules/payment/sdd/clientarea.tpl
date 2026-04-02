<div id="content">
    <div class="container">
        <h1>{$glang.NAME}</h1><hr />

        <p style="text-align: justify;">{$glang.OVERVIEW}</p>

        {if isset($suc)}<div class="alert alert-success">{$suc}</div>{/if}
        {if isset($err)}<div class="alert alert-danger">{$err}</div>{/if}

        <div class="table-responsive">
        	<table class="table table-bordered table-striped">
        		<tr>
        			<th width="30px"></th>
        			<th width="10%">{$glang.DATE}</th>
        			<th width="10%">{$glang.REFERENCE}</th>
        			<th>{$glang.ACCOUNTHOLDER}</th>
        			<th width="20%">{$glang.IBAN}</th>
        			<th width="15%">{$glang.BIC}</th>
        			<th width="10%">{$glang.STATUS}</th>
        		</tr>

        		{foreach from=$accounts item=acc key=id}
        		<tr>
        			<td>{if $acc->isActive()}<a href="{$cfg.PAGEURL}credit/pay/sdd/fav/{$id}">{/if}<i class="fa fa-star{if $user.sepa_fav != $id}-o{/if}"{if $user.sepa_fav == $id} style="color: rgb(234, 193, 23);"{/if}></i>{if $acc->isActive()}</a>{/if}</td>
        			<td>{dfo d=$acc->getDate() m=false}</td>
        			<td>{$acc->getID()}</td>
        			<td>{$acc->getAccountHolder()}</td>
        			<td>{$acc->getIBAN()}</td>
        			<td>{$acc->getBIC()}</td>
        			<td>
        				{if $acc->expired()}
        				<font color="red">{$glang.EXPIRE}</font>
        				{else if $acc->getStatus() == 0}
        				<font color="orange">{$glang.WAITING}</font> <a href="{$cfg.PAGEURL}credit/pay/sdd/{$id}" target="_blank"><i class="fa fa-file-pdf-o"></i></a>
        				{else if $acc->getStatus() == 1}
        				<font color="green">{$glang.ACTIVE}</font> <a href="{$cfg.PAGEURL}credit/pay/sdd/cancel/{$id}" onclick="return confirm('{$glang.REVOKE_CONFIRM}');"><i class="fa fa-times"></i></a>
        				{else}
        				<font color="red">{$glang.DEACTIVATED}</font>
        				{/if}
        			</td>
        		</tr>
        		{foreachelse}
        		<tr>
        			<td colspan="7"><center>{$glang.NOTHING}</center></td>
        		</tr>
        		{/foreach}
        	</table>
        </div>

        <a href="#" data-toggle="modal" data-target="#add" class="btn btn-primary btn-block">{$glang.ADDACCOUNT}</a><br /><br />

		<div class="modal fade" id="add" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content"><form method="POST">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title">{$glang.ADDACCOUNT}</h4>
		      </div>
		      <div class="modal-body">
		      	<div class="form-group">
		      		<label>{$glang.ACCOUNTHOLDER}</label>
		      		<input type="text" name="account_holder" placeholder="{$glang.AHP}" value="{if isset($smarty.post.account_holder)}{$smarty.post.account_holder}{else}{if !empty($user.company)}{$user.company}{else}{$user.name}{/if}{/if}" class="form-control" />
		      	</div>

		      	<div class="form-group">
		      		<label>{$glang.IBAN}</label>
		      		<input type="text" name="iban" placeholder="DE12 3456 7890 1234 5678 90" value="{if isset($smarty.post.iban)}{$smarty.post.iban}{/if}" class="form-control" />
		      	</div>

		      	<div class="form-group">
		      		<label>{$glang.BIC}</label>
		      		<input type="text" name="bic" placeholder="ABCDEFGHXXX" value="{if isset($smarty.post.bic)}{$smarty.post.bic}{/if}" class="form-control" />
		      	</div>

				{if $sdd_verification == "checkbox"}
				<div class="checkbox">
					<label>
						<input type="checkbox" name="checkbox"{if !empty($smarty.post.checkbox)} checked=""{/if} value="1">
						{$glang.CHECKTEXT|replace:"%p":$cfg.PAGENAME|replace:"%d":$sdd_days|replace:"%g":$sdd_ci}
					</label>
				</div>
				{/if}
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
		        <button type="submit" class="btn btn-primary">{$glang.ADDACCOUNT}</button>
		      </div>
		    </form></div>
		  </div>
		</div>
    </div>
</div>