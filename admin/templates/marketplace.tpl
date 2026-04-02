<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.TITLE}</h1>
        
        <ul class="nav nav-tabs" role="tablist">
            {for $i=1 to 11}
            {assign key "TAB"|cat:$i}
            <li{if $i == 1} class="active"{/if}><a class="mp_tab_link" data-tab="{$i}" href="#tab{$i}" role="tab" data-toggle="tab">{$l.$key}</a></li>
            {/for}
        </ul><br />

        <div class="tab-content">
            {for $i=1 to 11}
            <div role="tabpanel" class="tab-pane{if $i == 1} active{/if}" id="tab{$i}">
                <div class="mp_waiting" data-tab="{$i}">
                    <i class="fa fa-spinner fa-spin"></i> {$l.PW}
                </div>
            </div>
            {/for}
        </div>
    </div>
</div>

<script>
function mpLoadTab(id) {
    if ($(".mp_waiting[data-tab=" + id + "]").length) {
        $.get("?p=marketplace&tab=" + id, function(r) {
            $("#tab" + id).html(r);
        });
    }
}

$(document).ready(function() {
    mpLoadTab(1);
});

$(".mp_tab_link").click(function() {
    mpLoadTab($(this).data("tab"));
});

function mpInstall(e) {
    e.preventDefault();

    var type = $(this).data("type");
    var name = $(this).data("name");

    var span = $(this).parent();
    span.html('<i class="fa fa-spinner fa-spin"></i> {$l.PW}');

    $.get("?p=marketplace&install=" + type + "&name=" + name, function(r) {
        if (r == "ok") {
            span.html('<i class="fa fa-check" style="color: green;"></i> {$l.DOWNLOADED}');
        } else {
            span.html('<i class="fa fa-times" style="color: red;"></i> {$l.FAIL}');
        }
    });
}
</script>