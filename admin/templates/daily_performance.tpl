<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.DAILY_PERFORMANCE.TITLE} <small>{$month}.{$year}</small><span class="pull-right"><a href="?p=daily_performance&month={$prevmonth}&year={$prevyear}"><i class="fa fa-arrow-circle-o-left"></i></a>{if isset($nextmonth)}<a href="?p=daily_performance&month={$nextmonth}&year={$nextyear}">{/if}<i class="fa fa-arrow-circle-o-right" style="margin-left: 5px;"></i>{if isset($nextmonth)}</a>{/if}</span></h1>
	
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{$lang.DAILY_PERFORMANCE.DATE}</th>
                        <th>{$lang.DAILY_PERFORMANCE.NEWCUST}</th>
                        <th>{$lang.DAILY_PERFORMANCE.NEWCONT}</th>
                        <th>{$lang.DAILY_PERFORMANCE.NEWINVO}</th>
                        <th>{$lang.DAILY_PERFORMANCE.INVOICEAMOUNT}</th>
                        <th>{$lang.DAILY_PERFORMANCE.NEWTICK}</th>
                        <th>{$lang.DAILY_PERFORMANCE.NEWANSW}</th>
                    </tr>
                </thead>

                <tbody>
                    {foreach from=$res key=date item=stat}
                    <tr>
                        <td>{$date}</td>
                        <td>{$stat.0}</td>
                        <td>{$stat.1}</td>
                        <td>{$stat.2}</td>
                        <td>{$stat.3}</td>
                        <td>{$stat.4}</td>
                        <td>{$stat.5}</td>
                    </tr>
                    {/foreach}

                    <tr>
                        <th>{$lang.DAILY_PERFORMANCE.SUM}</th>
                        <th>{$sum.0}</th>
                        <th>{$sum.1}</th>
                        <th>{$sum.2}</th>
                        <th>{$sum.3}</th>
                        <th>{$sum.4}</th>
                        <th>{$sum.5}</th>
                    </tr>
                </tbody>
            </table>
	</div>
</div>