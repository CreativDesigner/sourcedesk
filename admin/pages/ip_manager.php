<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (isset($_POST['delete_ip'])) {
    $ip = $db->real_escape_string(html_entity_decode($_POST['delete_ip']));
    $db->query("DELETE FROM ip_addresses WHERE ip = '$ip'");
    if ($db->affected_rows) {
        alog("ip", "deleted", $info->ID, $ip);
        die("ok");
    }

    exit;
}

if (isset($_POST['add_ip'])) {
    $ip = $db->real_escape_string($_POST['add_ip']);
    $db->query("INSERT INTO ip_addresses (`ip`, `product`) VALUES ('$ip', " . intval($info->ID) . ")");
    if ($db->affected_rows) {
        alog("ip", "added", $info->ID, $ip);
        die("ok");
    }

    exit;
}

$l = $lang['IP_MANAGER'];
?>

<span style="font-size: 12px;"><a href="#" id="ip_man_refresh" onclick="load_ip(); return false;"><i class="fa fa-refresh"></i> <?=$l['REFRESH'];?></a><span class="pull-right"><?=$l['HINT'];?></span></span>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="ip_man_table">
        <tr>
            <th><?=$l['IP'];?></th>
            <th width="150px"><center><?=$l['CONTRACT'];?></center></th>
            <th width="30px"></th>
        </tr>

        <tr>
            <td><input type="text" id="ip_new" class="form-control input-sm" placeholder="5.9.7.9"></td>
            <td><center><i><?=$l['NOC'];?></i></center></td>
            <td><center><a href="#" id="add_btn"><i class="fa fa-check fa-fw"></i></a></center></td>
        </tr>

        <?php
$sql = $db->query("SELECT ip, contract FROM ip_addresses WHERE product = " . intval($info->ID) . " ORDER BY ip ASC");
while ($row = $sql->fetch_object()) {?>
        <tr>
            <td><?=htmlentities($row->ip);?></td>
            <td><center><?=$row->contract ? "<a href='?p=hosting&id={$row->contract}' target='_blank'>#{$row->contract}</a>" : "<i>{$l['NOC']}</i>";?></center></td>
            <td><center><a href="#" class="del_btn" data-ip="<?=htmlentities($row->ip);?>"><i class="fa fa-times fa-fw"></i></a></center></td>
        </tr>
        <?php }?>
    </table>
</div>

<script>
function bind_add_btn() {
    $("#add_btn").click(function(e) {
        e.preventDefault();

        $(this).unbind("click");
        $("#ip_new").prop("disabled", true);
        var parent = $(this).parent();
        var orghtml = parent.html();
        $(this).find("i").removeClass("fa-check").addClass("fa-spinner fa-pulse");
        parent.html($(this).html());
        var newip = $("#ip_new").val();

        $.post("?p=product_hosting&id=<?=$info->ID;?>&ip_product=0", {
            "add_ip": newip,
            "csrf_token": "<?=CSRF::raw();?>",
        }, function(r) {
            parent.find("i").removeClass("fa-spinner fa-pulse").addClass("fa-check").css("color", "green");
            setTimeout(function () {
                parent.html(orghtml);
                $("#ip_new").prop("disabled", false).val("");
                bind_add_btn();
            }, 2000);

            if (r == "ok") {
                newip = String(newip).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                $("#ip_man_table").append("<tr><td>" + newip + "</td><td><center><i><?=$l['NOC'];?></i></center></td><td><center><a href=\"#\" class=\"del_btn\" data-ip=\"" + newip + "\"><i class=\"fa fa-times fa-fw\"></i></a></center></td></tr>");
                $(".del_btn").unbind("click");
                bind_del_btn();
            }
        });
    });
}
bind_add_btn();

function bind_del_btn() {
    $(".del_btn").click(function(e) {
        e.preventDefault();

        var row = $(this).parent().parent().parent();
        $(this).find("i").removeClass("fa-times").addClass("fa-spinner fa-pulse");
        $(this).parent().html($(this).html());
        var ip = $(this).data("ip");

        $.post("?p=product_hosting&id=<?=$info->ID;?>&ip_product=0", {
            "delete_ip": ip,
            "csrf_token": "<?=CSRF::raw();?>",
        }, function(r) {
            if (r == "ok") {
                row.remove();
            }
        });
    });
}
bind_del_btn();
</script>