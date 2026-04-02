<div id="content">
	<div class="container">
		<h1>{$lang.INVOICES.TITLE}</h1><hr>

		<p style="text-align: justify;">{$lang.INVOICES.INTRO}</p>

		{if $waiting_amount}<div class="alert alert-warning">{$lang.INVOICES.OPEN|replace:"%a":$waiting_amount_f|replace:"%d":$waiting_amount_d}</div>{/if}

		{if count($invoices) == 0}
		<i>{$lang.INVOICES.NOTHING}</i>
		{else}
		{if isset($suc)}<div class="alert alert-success">{$suc}</div>{/if}
		<form method="POST">
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th width="10px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
					<th>{$lang.INVOICES.ID}</th>
					<th>{$lang.INVOICES.DATE}</th>
					<th>{$lang.INVOICES.DUE}</th>
					<th>{$lang.INVOICES.AMOUNT}</th>
					<th>{$lang.INVOICES.STATE}</th>
				</tr>

				{foreach from=$invoices item=inv}
				<tr>
					<td><input type="checkbox" class="checkbox" name="invoices[]" value="{$inv->getId()}" onchange="javascript:toggle();" /></td>
					<td>{$inv->getInvoiceNo()} <a href="{$cfg.PAGEURL}invoices/{$inv->getId()}" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
					<td>{dfo d=strtotime($inv->getDate()) m=false}</td>
					<td>{dfo d=strtotime($inv->getDueDate()) m=false}</td>
					<td>{infix n={nfo i={conva n=$inv->getAmount()}}}</td>
					<td>{if $inv->getStatus() == 0}<font color="red">{$lang.INVOICES.UNPAID}</font> <a href="{$cfg.PAGEURL}invoices/pay/{$inv->getId()}"><i class="fa fa-money"></i></a>{else if $inv->getStatus() == 1}<font color="green">{$lang.INVOICES.PAID}</font>{else}{$lang.INVOICES.CANCELLED}{/if}</td>
				</tr>
				{/foreach}
			</table>
		</div>

		{$lang.GENERAL.SELECTED}: <input type="submit" name="pay" value="{$lang.INVOICES.PAY}" class="btn btn-primary" /> <input type="submit" name="download" value="{$lang.INVOICES.DOWNLOAD}" class="btn btn-default" /> <input type="submit" name="send" value="{$lang.INVOICES.SEND}" class="btn btn-default" />
		</form>
		{/if}

		{if $quotes|@count}<br />
		<h1>{$lang.INVOICES.OPEN_QUOTES}</h1><hr>

		<p style="text-align: justify;">{$lang.INVOICES.OQ_INTRO}</p>

		<div class="table-responsive">
			<table class="table table-bordered table-striped" style="margin-bottom: 0;">
				<tr>
					<th>{$lang.INVOICES.QID}</th>
					<th>{$lang.INVOICES.DATE}</th>
					<th>{$lang.INVOICES.VALID}</th>
					<th>{$lang.INVOICES.AMOUNT}</th>
					<th>{$lang.INVOICES.STATE}</th>
				</tr>

				{foreach from=$quotes item=q}
				<tr>
					<td>{$q.1} <a href="{$cfg.PAGEURL}invoices/quote/{$q.0}" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
					<td>{$q.2}</td>
					<td>{$q.3}</td>
					<td>{$q.4}</td>
					<td><font color="orange">{$lang.INVOICES.OPENQUOTE}</font> <a onclick="return confirm('{$lang.INVOICES.CONFIRM}');" href="{$cfg.PAGEURL}invoices/accept/{$q.0}"><i class="fa fa-check"></i></a></td>
				</tr>
				{/foreach}
			</table>
		</div>
		{/if}
	</div>
</div><br />