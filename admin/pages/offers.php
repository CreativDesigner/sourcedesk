<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['OFFERS'];
title($l['TITLE']);
menu("products");

if (!$ari->check(24)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "offers");} else {
    $t = isset($_GET['t']) && in_array($_GET['t'], array("soon", "expired")) ? $_GET['t'] : "";

    if (isset($_POST['old']) && isset($_POST['id']) && is_array($_POST['id'])) {
        $d = 0;
        foreach ($_POST['id'] as $id) {
            $db->query("UPDATE offers SET status = 0 WHERE ID = " . intval($id));
            if ($db->affected_rows > 0) {
                $d++;
                alog("offer", "old", $id);
            }
        }

        if ($d == 1) {
            $suc = $l['OP1'];
        } else if ($d > 0) {
            $suc = str_replace("%d", $d, $l['OPX']);
        }

    } else if (isset($_POST['new']) && isset($_POST['id']) && is_array($_POST['id'])) {
        $d = 0;
        foreach ($_POST['id'] as $id) {
            $db->query("UPDATE offers SET status = 1 WHERE ID = " . intval($id));
            if ($db->affected_rows > 0) {
                $d++;
                alog("offer", "new", $id);
            }
        }

        if ($d == 1) {
            $suc = $l['AP1'];
        } else if ($d > 0) {
            $suc = str_replace("%d", $d, $l['APX']);
        }

    } else if (isset($_POST['delete']) && isset($_POST['id']) && is_array($_POST['id'])) {
        $d = 0;
        foreach ($_POST['id'] as $id) {
            $db->query("DELETE FROM offers WHERE ID = " . intval($id));
            if ($db->affected_rows > 0) {
                $d++;
                alog("offer", "del", $id);
            }
        }

        if ($d == 1) {
            $suc = $l['DL1'];
        } else if ($d > 0) {
            $suc = str_replace("%d", $d, $l['DLX']);
        }

    }

    ?>
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?> <a href="?p=new_offer" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>
    </div>
    <!-- /.col-lg-12 -->
</div>

<?php if (!empty($suc)) {
        echo '<div class="alert alert-success">' . $suc . '</div>';
    }
    ?>

<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$t == "soon" ? " active" : "";?>" href="./?p=offers&t=soon"><?=$l['S1'];?> (<?=$db->query("SELECT COUNT(*) AS c FROM offers WHERE start > '" . date("Y-m-d") . "'")->fetch_object()->c;?>)</a>
					<a class="list-group-item<?=$t == "" ? " active" : "";?>" href="./?p=offers"><?=$l['S2'];?> (<?=$db->query("SELECT COUNT(*) AS c FROM offers WHERE start <= '" . date("Y-m-d") . "' AND end >= '" . date("Y-m-d") . "'")->fetch_object()->c;?>)</a>
					<a class="list-group-item<?=$t == "expired" ? " active" : "";?>" href="./?p=offers&t=expired"><?=$l['S3'];?> (<?=$db->query("SELECT COUNT(*) AS c FROM offers WHERE end < '" . date("Y-m-d") . "'")->fetch_object()->c;?>)</a>
				</div>
			</div>
<div class="col-md-9">

<?php
if ($t == "soon") {
        $where = "start > '" . date("Y-m-d") . "'";
        $order = "start ASC, end ASC, title ASC, ID ASC";
        $on = "red";
        $off = "green";
    } else if ($t == "expired") {
        $where = "end < '" . date("Y-m-d") . "'";
        $order = "end DESC, title ASC, ID DESC";
        $on = "red";
        $off = "green";
    } else {
        $where = "start <= '" . date("Y-m-d") . "' AND end >= '" . date("Y-m-d") . "'";
        $order = "end ASC, title ASC, ID ASC";
        $on = "green";
        $off = "red";
    }

    $t = new Table("SELECT * FROM offers WHERE $where", []);
    echo $t->getHeader();
    ?>

<form method="POST">
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="20px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);" /></th>
			<th><?=$l['OFFER'];?></th>
			<th><?=$l['TIMEFRAME'];?></th>
			<th><?=$l['OP'];?></th>
			<th><?=$l['AP'];?></th>
		</tr>

		<?php
$sql = $t->qry($order);
    if ($sql->num_rows == 0) {
        ?>
		<tr>
			<td colspan="5"><center><?=$l['NT'];?></center></td>
		</tr>
		<?php } else {while ($row = $sql->fetch_object()) {?>
		<tr>
			<td><input type="checkbox" name="id[]" value="<?=$row->ID;?>" class="checkbox" onchange="javascript:toggle();" /></td>
			<td><a href="<?=unserialize($row->url)[$CFG['LANG']];?>" target="_blank"><?=unserialize($row->title)[$CFG['LANG']];?></a></td>
			<td><?=$dfo->format($row->start, false) . " - " . $dfo->format($row->end, false);?></td>
			<td<?php if ($row->status == 0) {
        echo ' style="font-weight: bold; color: ' . $off . ';"';
    }
        ?>><?=unserialize($row->old_price)[$CFG['LANG']];?></td>
			<td<?php if ($row->status == 1) {
            echo ' style="font-weight: bold; color: ' . $on . ';"';
        }
        ?>><?=unserialize($row->price)[$CFG['LANG']];?></td>
		</tr>
		<?php }}?>
	</table>
</div>

<?=$l['SELECTED'];?>: <input type="submit" name="old" class="btn btn-default" value="<?=$l['A1'];?>" /> <input type="submit" name="new" class="btn btn-default" value="<?=$l['A2'];?>" /> <input type="submit" name="delete" class="btn btn-danger" value="<?=$l['A3'];?>" /></form>
<br /><?=$t->getFooter();?>
</div></div>
<?php }?>