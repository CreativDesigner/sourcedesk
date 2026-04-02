<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.TITLE} <small>{$tname|htmlentities}</small></h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<div class="checkbox" style="margin-top: 0;">
    <label>
        <i class="fa fa-spinner fa-spin" id="ns_set_change" style="display: none;"></i>
        <input type="checkbox" id="ns_set"{if $ns_set} checked=""{/if}>
        {$l.NS_SET}
    </label>
</div>

<script>
$("#ns_set").change(function() {
    $("#ns_set").hide();
    $("#ns_set_change").show();

    $.post("", {
        csrf_token: "{ct}",
        ns_set: $(this).is(":checked") ? "1" : "0",
    }, function() {
        $("#ns_set").show();
        $("#ns_set_change").hide();
    });
});
</script>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <tr>
            <th width="25%">{$l.SUBDOMAIN}</th>
            <th width="10%">{$l.TYPE}</th>
            <th>{$l.CONTENT}</th>
            <th width="10%">{$l.TTL}</th>
            <th width="10%">{$l.PRIORITY}</th>
            <th width="10%">{$l.HIDDEN}</th>
            <th width="30px"></th>
            <th width="30px"></th>
        </tr>

        {foreach from=$records item=r}
        <form method="POST">
            <input type="hidden" name="id" value="{$r.ID}">
            <tr>
                <td><input type="text" name="name" value="{$r.name|htmlentities}" class="form-control input-xs"></td>
                <td><input type="text" name="type" value="{$r.type|htmlentities}" class="form-control input-xs"></td>
                <td><input type="text" name="content" value="{$r.content|htmlentities}" class="form-control input-xs"></td>
                <td><input type="text" name="ttl" value="{$r.ttl|htmlentities}" class="form-control input-xs"></td>
                <td><input type="text" name="priority" value="{$r.priority|htmlentities}" class="form-control input-xs"></td>
                <td><select class="form-control" name="hidden"><option value="0">{$l.NO}</option><option value="1"{if $r.hidden} selected=""{/if}>{$l.YES}</option></select></td>
                <td><input type="submit" class="btn btn-primary btn-block" value="{$l.SAVE}"></td>
                <td><input type="button" class="btn btn-danger btn-block del-tpl-rec" data-id="{$r.ID}" value="{$l.DELETE}"></td>
            </tr>
        </form>
        {/foreach}

        <form method="POST">
            <input type="hidden" name="id" value="-1">
            <tr>
                <td><input type="text" name="name" class="form-control input-xs"></td>
                <td><input type="text" name="type" class="form-control input-xs"></td>
                <td><input type="text" name="content" class="form-control input-xs"></td>
                <td><input type="text" name="ttl" value="3600" class="form-control input-xs"></td>
                <td><input type="text" name="priority" value="0" class="form-control input-xs"></td>
                <td><select class="form-control" name="hidden"><option value="0">{$l.NO}</option><option value="1">{$l.YES}</option></select></td>
                <td colspan="2"><input type="submit" class="btn btn-success btn-block" value="{$l.ADD}"></td>
            </tr>
        </form>
    </table>
</div>

<script>
$(".del-tpl-rec").click(function() {
    var id = $(this).data("id");
    $(this).parent().parent().remove();

    $.post("", {
        csrf_token: "{ct}",
        del: id,
    }, function() {});
});
</script>