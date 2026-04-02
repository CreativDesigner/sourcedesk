<div id="content">
	<div class="container">
		{if $domains|count == 0 && $hosting|count == 0}
		<h1>{$lang.PRODUCTS.PTITLE}</h1><hr><p style="float: left;">{$lang.PRODUCTS.NOTHING}</p>
		{else}
		{if $hosting|count > 0}
		<h1 style="margin-top: 30px;">{$lang.PRODUCTS.HOSTING}</h1><hr><p style="text-align: justify; margin-bottom: 20px;">{$lang.PRODUCTS.HOSTING_INTRO}</p>

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
					<td>{$h.1}{if $h.prepaid} <span class="label label-primary">{$lang.CART.PREPAID}</span>{/if}</td>
					<td>{if $h.2}{$h.2|htmlentities}{else}<i>{$lang.PRODUCTS.NO_DESC}</i>{/if}</td>
					<td>{$h.3}{if $h.4 == "monthly"} {$lang.CART.MONTHLY}{else if $h.4 == "quarterly"} {$lang.CART.QUARTERLY}{else if $h.4 == "semiannually"} {$lang.CART.SEMIANNUALLY}{else if $h.4 == "annually"} {$lang.CART.ANNUALLY}{else if $h.4 == "biennially"} {$lang.CART.BIENNIALLY}{else if $h.4 == "trinnially"} {$lang.CART.TRINNIALLY}{/if}</td>
					<td><div class="label label-{if $h.5 == 0 || $h.5 == -3}danger{else if $h.5 == 1}success{else if $h.5 == -1}warning{else if $h.5 == -2}default{/if}">{if $h.5 == 0}{$lang.PRODUCTS.HS_LOCK}{else if $h.5 == 1}{$lang.PRODUCTS.HS_OK}{else if $h.5 == -2}{$lang.PRODUCTS.HS_CANCEL}{else if $h.5 == -1}{if $h.payment}{$lang.PRODUCTS.HS_PAY}{else}{$lang.PRODUCTS.HS_WAIT}{/if}{else}{$lang.PRODUCTS.HS_ERROR}{/if}</div>{if $h.6 != "0000-00-00" && $h.5 != -2} ({$lang.PRODUCTS.CANCELLED|replace:"%d":{dfo d=$h.6 m=0}}){/if}</td>
					<td>{if $h.5 == 1}<a href="{$cfg.PAGEURL}hosting/{$id}"><i class="fa fa-wrench"></i></a>{/if}</td>
				</tr>
				{/foreach}
			</table>
		</div>
		{/if}

		{if $domains|count > 0}
		<h1 style="margin-top: 30px;">{$lang.PRODUCTS.DOMAINS}</h1><hr><p style="text-align: justify; margin-bottom: 20px;">{$lang.PRODUCTS.DOMAINS_INTRO}</p>

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
	                <td>{if $d->inclusive}<span class="label label-success">{$lang.CONFIGURE.INCLCART}</span>{else}{$cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $d->recurring)))}{/if}</td>
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

		{if $recurring|count > 0}
		<h1 style="margin-top: 30px;">{$lang.PRODUCTS.RECURRING_INVOICES}</h1><hr><p style="text-align: justify; margin-bottom: 20px;">{$lang.PRODUCTS.RECURRING_INTRO}</p>

		<div class="table-responsive">
			<table class="table table-striped table-bordered">
				<tr>
					<th>{$lang.PRODUCTS.FI}</th>
					<th>{$lang.PRODUCTS.LI}</th>
					<th>{$lang.PRODUCTS.NI}</th>
					<th>{$lang.PRODUCTS.PERIOD}</th>
					<th width="40%">{$lang.PRODUCTS.ITEM}</th>
					<th>{$lang.PRODUCTS.RECURRING}</th>
				</tr>

				{foreach from=$recurring item=item}
				<tr>
					<td>{$item.0}</td>
					<td>{$item.1}</td>
					<td>{$item.2}</td>
					<td>{$item.3}</td>
					<td>{$item.4}</td>
					<td>{$item.5}</td>
				</tr>
				{/foreach}
			</table>
		</div>
		{/if}
		{/if}
	</div>
</div><br />