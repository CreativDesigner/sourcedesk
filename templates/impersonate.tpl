<div id="content">
	<div class="container">
		<h1>{$title}</h1><hr>
        
        {if "products"|in_array:$rights}
        <h3>{$lang.IMPERSONATE.PRODUCTS}</h3>
        
        {if $hosting|@count == 0}
        <i>{$lang.IMPERSONATE.NO_PRODUCTS}</i>
        {else}
        <div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th>{$lang.PRODUCTS.CONTRACT_DATE}</th>
					<th>{$lang.PRODUCTS.PRODUCT}</th>
					<th>{$lang.PRODUCTS.DESCRIPTION}</th>
					<th>{$lang.PRODUCTS.RECURRING}</th>
					<th>{$lang.PRODUCTS.STATUS}</th>
					<th width="30px"></th>
				</tr>

				{foreach from=$hosting key=id item=h}
				<tr>
					<td>{dfo d=$h.0 m=0}</td>
					<td>{$h.1}</td>
					<td>{if $h.2}{$h.2|htmlentities}{else}<i>{$lang.PRODUCTS.NO_DESC}</i>{/if}</td>
					<td>{$h.3}{if $h.4 == "monthly"} {$lang.CART.MONTHLY}{else if $h.4 == "quarterly"} {$lang.CART.QUARTERLY}{else if $h.4 == "semiannually"} {$lang.CART.SEMIANNUALLY}{else if $h.4 == "annually"} {$lang.CART.ANNUALLY}{else if $h.4 == "biennially"} {$lang.CART.BIENNIALLY}{else if $h.4 == "trinnially"} {$lang.CART.TRINNIALLY}{/if}</td>
					<td><div class="label label-{if $h.5 == 0 || $h.5 == -3}danger{else if $h.5 == 1}success{else if $h.5 == -1}warning{else if $h.5 == -2}default{/if}">{if $h.5 == 0}{$lang.PRODUCTS.HS_LOCK}{else if $h.5 == 1}{$lang.PRODUCTS.HS_OK}{else if $h.5 == -2}{$lang.PRODUCTS.HS_CANCEL}{else if $h.5 == -1}{if $h.payment}{$lang.PRODUCTS.HS_PAY}{else}{$lang.PRODUCTS.HS_WAIT}{/if}{else}{$lang.PRODUCTS.HS_ERROR}{/if}</div>{if $h.6 != "0000-00-00" && $h.5 != -2} ({$lang.PRODUCTS.CANCELLED|replace:"%d":{dfo d=$h.6 m=0}}){/if}</td>
					<td>{if $h.5 == 1}<a href="{$cfg.PAGEURL}hosting/{$id}"><i class="fa fa-wrench"></i></a>{/if}</td>
				</tr>
				{/foreach}
			</table>
		</div>
        {/if}
        {/if}

        {if "domains"|in_array:$rights}
        <h3>{$lang.IMPERSONATE.DOMAINS}</h3>
        
        {if $domains|@count == 0}
        <i>{$lang.IMPERSONATE.NO_DOMAINS}</i>
        {else}
        <script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.10.15/sorting/date-de.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.1.0/js/responsive.bootstrap.min.js"></script>
		<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css">
		<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.1.0/css/responsive.bootstrap.min.css">

		<table id="domain-table" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
	        <thead>
	            <tr>
	                <th>{$lang.PRODUCTS.DOMAIN}</th>
	                <th>{$lang.PRODUCTS.EXPIRATION}</th>
	                <th>{$lang.PRODUCTS.RECURRING}</th>
	                <th>{$lang.PRODUCTS.STATUS}</th>
	            </tr>
	        </thead>
	        <tbody>
	        	{foreach from=$domains item=d}
	            <tr>
	                <td><a href="{$cfg.PAGEURL}domain/{implode('/', explode('.', $d->domain))}">{$d->domain}</a></td>
	                <td>{if $d->expiration == "0000-00-00"}<i>{$lang.PRODUCTS.UNKNOWN}</i>{else}{dfo d=$d->expiration m=0}{/if}</td>
	                <td>{$cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $d->recurring)))}{if $d->inclusive} <span class="label label-success">{$lang.CONFIGURE.INCLCART}</span>{/if}</td>
	                <td><div class="label label-{if $d->customer_wish > 0}danger{else}{if $d->status == "REG_WAITING" || $d->status == "KK_WAITING"}warning{else if $d->status == "REG_OK" || $d->status == "KK_OK"}success{else if $d->status == "KK_OUT" || $d->status == "EXPIRED" || $d->status == "TRANSIT" || $d->status == "DELETED"}default{else if $d->status == "KK_ERROR" || $d->status == "REG_ERROR"}danger{/if}{/if}">{if array_key_exists($d->status, $lang.PRODUCTS.DOMAINSTATUS)}{if $d->payment}{$lang.PRODUCTS.HS_PAY}{else}{$lang.PRODUCTS.DOMAINSTATUS.{$d->status}}{/if}{else if $d->customer_wish == 1}{$lang.PRODUCTS.DOMAIN_DELETE}{else if $d->customer_wish > 1}{$lang.PRODUCTS.DOMAIN_TRANSIT}{/if}</div></td>
	            </tr>
	            {/foreach}
	        </tbody>
        </table>

        <script>
		$(document).ready(function() {
		    $('#domain-table').DataTable({
		    	language: {
				    "sEmptyTable":      "{$lang.DATATABLES.0}",
				    "sInfo":            "{$lang.DATATABLES.1}",
				    "sInfoEmpty":       "{$lang.DATATABLES.2}",
				    "sInfoFiltered":    "{$lang.DATATABLES.3}",
				    "sInfoPostFix":     "",
				    "sInfoThousands":   "{$lang.DATATABLES.4}",
				    "sLengthMenu":      "{$lang.DATATABLES.5}",
				    "sLoadingRecords":  "{$lang.DATATABLES.6}",
				    "sProcessing":      "{$lang.DATATABLES.7}",
				    "sSearch":          "{$lang.DATATABLES.8}",
				    "sZeroRecords":     "{$lang.DATATABLES.9}",
				    "oPaginate": {
				        "sFirst":       "{$lang.DATATABLES.10}",
				        "sPrevious":    "{$lang.DATATABLES.11}",
				        "sNext":        "{$lang.DATATABLES.12}",
				        "sLast":        "{$lang.DATATABLES.13}"
				    },
				    "oAria": {
				        "sSortAscending":  "{$lang.DATATABLES.14}",
				        "sSortDescending": "{$lang.DATATABLES.15}"
				    }
				},
				"columnDefs": [
					{
						"type": "de_date",
						"targets": 1,
					},
				],
		    });
		});
        </script>
        {/if}
        {/if}

        {if "tickets"|in_array:$rights}
        <h3>{$lang.IMPERSONATE.TICKETS}<a href="{$cfg.PAGEURL}tickets/add?owner={$uid}" class="pull-right"><i class="fa fa-plus-circle"></i></a></h3>
        
        {if $t|@count == 0}
        <i>{$lang.IMPERSONATE.NO_TICKETS}</i>
        {else}
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="15%">{$lang.TICKETS.CREATED}</th>
                    <th width="15%">{$lang.TICKETS.DEPT}</th>
                    <th>{$lang.TICKETS.SUBJECT}</th>
                    <th width="15%">{$lang.TICKETS.PRIORITY}</th>
                    <th width="15%">{$lang.GENERAL.STATUS}</th>
                    <th width="15%">{$lang.TICKETS.LASTANSWER}</th>
                </tr>

                {foreach from=$t item=i}
                <tr>
                    <td>{dfo d=$i.created}</td>
                    <td>{$i.t->getDepartmentName()}</td>
                    <td><a href="{$i.url}">{$i.subject|htmlentities}</a></td>
                    <td>{$i.t->getPriorityStr()}</td>
                    <td>{$i.t->getStatusStr()}</td>
                    <td>{$i.t->getLastAnswerStr()}</td>
                </tr>
                {/foreach}
            </table>
        </div>
        {/if}
        {/if}

        {if "invoices"|in_array:$rights}
        <h3>{$lang.IMPERSONATE.INVOICES}</h3>
        
        {if count($invoices) == 0}
		<i>{$lang.IMPERSONATE.NO_INVOICES}</i>
		{else}
		<div class="table-responsive">
			<table class="table table-bordered table-striped">
				<tr>
					<th>{$lang.INVOICES.ID}</th>
					<th>{$lang.INVOICES.DATE}</th>
					<th>{$lang.INVOICES.DUE}</th>
					<th>{$lang.INVOICES.AMOUNT}</th>
					<th>{$lang.INVOICES.STATE}</th>
				</tr>

				{foreach from=$invoices item=inv}
				<tr>
					<td>{$inv->getInvoiceNo()} <a href="{$cfg.PAGEURL}impersonate/{$inv->getClient()}/invoices/{$inv->getId()}" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
					<td>{dfo d=strtotime($inv->getDate()) m=false}</td>
					<td>{dfo d=strtotime($inv->getDueDate()) m=false}</td>
					<td>{infix n={nfo i={conva n=$inv->getAmount()}}}</td>
					<td>{if $inv->getStatus() == 0}<font color="red">{$lang.INVOICES.UNPAID}</font>{else if $inv->getStatus() == 1}<font color="green">{$lang.INVOICES.PAID}</font>{else}{$lang.INVOICES.CANCELLED}{/if}</td>
				</tr>
				{/foreach}
			</table>
		</div>
        {/if}
        {/if}

        {if "quotes"|in_array:$rights}
        <h3>{$lang.IMPERSONATE.QUOTES}</h3>
        
        {if count($quotes) == 0}
		<i>{$lang.IMPERSONATE.NO_QUOTES}</i>
		{else}
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
					<td>{$q.1} <a href="{$cfg.PAGEURL}impersonate/{$uid}/quotes/{$q.0}" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>
					<td>{$q.2}</td>
					<td>{$q.3}</td>
					<td>{$q.4}</td>
					<td><font color="orange">{$lang.INVOICES.OPENQUOTE}</font></td>
				</tr>
				{/foreach}
			</table>
		</div>
        {/if}
        {/if}

        {if "emails"|in_array:$rights}
        <h3>{$lang.IMPERSONATE.EMAILS}</h3>
        
        {if $mails|count <= 0}<i>{$lang.IMPERSONATE.NO_EMAILS}</i>{else}

	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th>{$lang.MAILS.DATE}</th>
				<th>{$lang.MAILS.SUBJECT}</th>
			</tr>
			
			{foreach from=$mails item=mail key=id}
			<tr>
				<td>{dfo d=$mail.sent s=1}</td>
				<td><a href="{$mail.url}" target="_blank">{$mail.subject|htmlentities}</a></td>
			</tr>
			{/foreach}
		</table>
	</div>

	{/if}
        {/if}
	</div>
</div><br />