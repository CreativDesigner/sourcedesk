<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.HEADER}</h1>

		<form method="POST" class="form-inline">
			<input type="text" name="client_id" placeholder="{$l.CID}" class="form-control">
			<input type="text" name="client_secret" value="{$secret}" class="form-control" readonly="readonly">
			<input type="text" name="redirect_uri" placeholder="{$l.URL}" class="form-control">
			<input type="submit" class="btn btn-primary" value="{$l.CREATE}">
		</form><br />

		<div class="table-responsive">
      <table class="table table-bordered table-striped">
        <tr>
          <th>{$l.CID}</th>
          <th>{$l.URL}</th>
          <th width="28px"></th>
        </tr>

        {foreach from=$clients item=c}
        <tr>
          <td>{$c.client_id|htmlentities}</td>
          <td>{$c.redirect_uri|htmlentities}</td>
          <td><a href="?p=oauth2config&d={$c.client_id|urlencode}"><i class="fa fa-times"></i></a></td>
        </tr>
        {foreachelse}
        <tr>
          <td colspan="3"><center>{$l.NT}</center></td>
        </tr>
        {/foreach}
      </table>
    </div>
	</div>
	<!-- /.col-lg-12 -->
</div>
