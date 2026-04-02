<div style="padding: 30px; text-align: center;">
    {if !empty($err)}<div class="alert alert-danger">{$err|htmlentities}</div>{/if}

    <img src="{$avatar}"{if $admin.ID == $adminInfo.ID} id="my_avatar"{/if} alt="{$admin.name|htmlentities}" title="{$admin.name|htmlentities}" style="border-radius: 50%; width: 200px; height: 200px;{if $admin.ID == $adminInfo.ID} cursor: pointer;{/if}" />
    {if $admin.ID && $adminInfo.ID}
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="new_avatar" style="display: none;" />
    </form>
    {/if}
    <h3 style="margin-top: 25px;">
        <span data-toggle="tooltip" title="{if $admin.online == 2}{$lang.STATUS.BUSY}{else if $admin.online == 1}{$lang.STATUS.ONLINE}{else}{$lang.STATUS.AWAY}{/if}" style="font-size: 30pt; line-height: 0; vertical-align: middle; color: {if $admin.online == 2}orange{else if $admin.online == 1}green{else}red{/if};">•</span>
        {$admin.name|htmlentities}
    </h3>{$admin.email|htmlentities}
    {if $adminInfo.ID != $admin.ID && "63"|in_array:$admin_rights}
        <br /><br />
        <a href="?p=switch_admin&id={$admin.ID}" class="btn btn-info">{$lang.ADMIN.LOGIN}</a>
    {/if}
</div>

{if "52"|in_array:$admin_rights && $log|@count > 0}
<div class="panel panel-default">
    <div class="panel-heading">
        {$lang.ADMIN.LA}
    </div>
    <div class="panel-body">
        <div class="list-group" style="margin-bottom: 0;">
            {foreach from=$log item=l}
            <div class="list-group-item">
                {$l.0}
                <span class="pull-right text-muted small"><em>{$l.1}</em></span>
            </div>
            {/foreach}
        </div>
    </div>
</div>
{/if}

{if $admin.ID == $adminInfo.ID}
<script>
$("#my_avatar").click(function() {
    $("[name=new_avatar]").click();
});

$("[name=new_avatar]").change(function() {
    $(this).parent().submit();
});
</script>
{/if}