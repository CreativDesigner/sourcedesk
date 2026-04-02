<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['CUST_SOURCE'];
title($l['TITLE']);

if (empty($_GET['stat'])) {
    menu("settings");

    if (!$ari->check(34)) {require __DIR__ . "/error.php";
        alog("general", "insufficient_page_rights", "cust_source");} else {
        ?>
        <div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE'];?> <a href="?p=settings&tab=cfields" class="pull-right"><i class="fa fa-reply"></i></a></h1>

<?php
if (isset($_POST['save'])) {
            $cs = is_array($_POST['cust_source'] ?? null) ? $_POST['cust_source'] : [];

            foreach ($cs as $k => $v) {
                foreach ($v as $n) {
                    if (empty($n)) {
                        unset($cs[$k]);
                        continue 2;
                    }
                }
            }

            $CFG['CUST_SOURCE'] = serialize($cs);
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($CFG['CUST_SOURCE']) . "' WHERE `key` = 'cust_source'");
            echo '<div class="alert alert-success">' . $l['SAVED'] . '</div>';
        }
        ?>

<form method="POST">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="rowTable">
                <tr>
                    <?php foreach ($languages as $key => $name) {
            echo '<th>' . htmlentities($name) . '</th>';
        }
        ?>
                    <th width="30px"></th>
                </tr>

                <?php
$rows = unserialize($CFG['CUST_SOURCE']);
        $i = 0;
        foreach ($rows as $i => $row) {
            echo '<tr>';
            foreach ($languages as $key => $name) {
                $val = htmlentities($row[$key] ?? "");
                echo '<td><input type="text" name="cust_source[' . $i . '][' . $key . ']" value="' . $val . '" class="form-control input-sm"></td>';
            }
            echo '<td><center><a href="#" class="delRow" style="color: red;"><i class="fa fa-times-circle"></i></a></center></td>';
            echo '</tr>';
        }
        ?>
            </table>
        </div>

        <a href="#" id="addRow" style="color: green;"><i class="fa fa-plus-circle"></i> <?=$l['ADD_ROW'];?></a>
<div id="rowTemplate" style="display: none;">
    <table>
            <?php
echo '<tr>';
        foreach ($languages as $key => $name) {
            echo '<td><input type="text" name="cust_source[#ID#][' . $key . ']" value="" class="form-control input-sm"></td>';
        }
        echo '<td><center><a href="#" class="delRow" style="color: red;"><i class="fa fa-times-circle"></i></a></center></td>';
        echo '</tr>';
        ?>
    </table>
</div>
<input type="hidden" name="save" value="1">
<br /><br /><input type="submit" class="btn btn-primary btn-block" value="<?=$l['SAVE'];?>">
</form>
        <script>
        var new_i = <?=++$i;?>;

        function bindDel() {
            $(".delRow").unbind("click").click(function(e) {
                e.preventDefault();
                $(this).parent().parent().parent().remove();
            });
        }

        $("#addRow").click(function(e) {
            e.preventDefault();
            var html = $("#rowTemplate").clone().html();
            html = html.split("#ID#").join(new_i++);
            $("#rowTable > tbody").append($(html).find("tr"));
            bindDel();
        });

        bindDel();
        </script>
	</div>
	<!-- /.col-lg-12 -->
</div>
<?php }} else {
    menu("statistics");

    if (!$ari->check(40)) {require __DIR__ . "/error.php";
        alog("general", "insufficient_page_rights", "cust_source");} else {
        ?>
            <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$l['TITLE'];?></h1>

            <?php
$cso = [];
        $sum = $db->query("SELECT COUNT(*) c FROM clients")->fetch_object()->c;
        foreach (unserialize($CFG['CUST_SOURCE']) as $cs) {
            $to = [];

            foreach ($cs as $n) {
                if (!empty($n)) {
                    $to[] = $db->real_escape_string($n);
                }
            }

            if (!count($to)) {
                continue;
            }

            $in = "('" . implode("','", $to) . "')";
            $cso[$cs[$CFG['LANG']]] = $db->query("SELECT COUNT(*) c FROM clients WHERE cust_source IN $in")->fetch_object()->c;
        }

        $cso["-"] = $db->query("SELECT COUNT(*) c FROM clients WHERE cust_source = ''")->fetch_object()->c;
        arsort($cso);
        ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <?php foreach ($cso as $name => $count) {?>
                    <tr>
                        <th><?=htmlentities($name);?></th>
                        <td><?=$count;?></td>
                        <td><?=$nfo->format($count / $sum * 100);?> %</td>
                    </tr>
                    <?php }?>
                </table>
            </div>
        </div>
        </div>
<?php
}}?>