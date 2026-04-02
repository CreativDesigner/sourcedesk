<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['OPEN_ORDERS'];
title($l['TITLE']);
menu("payments");

if (!$ari->check(13)) {require __DIR__ . "/error.php";if (!$ari->check(16)) {
    alog("general", "insufficient_page_rights", "open_orders");
}
} else {

    if (isset($_GET['id'])) {
        $sql = $db->query("SELECT info, cart FROM guest_orders WHERE ID = " . intval($_GET['id']));
        if ($sql->num_rows == 1) {
            alog("order", "details_viewed", $_GET['id']);

            $i = $sql->fetch_object();
            $d = (object) unserialize($i->info);
            $c = (object) unserialize($i->cart);
            foreach ($d as &$v) {
                $v = htmlentities($v);
            }

            $name = $d->firstname . " " . $d->lastname . (!empty($d->company) ? " ({$d->company})" : "");

            ob_start();
            ?>
    <div class="table-responsive" style="margin-bottom: 0;">
      <table class="table table-bordered table-striped" style="margin-bottom: 0;">
        <tr>
          <th><?=$l['FIELD'];?></th>
          <th><?=$l['CONTENT'];?></th>
        </tr>

        <tr>
          <td><?=$l['MAIL'];?></td>
          <td><a href="mailto:<?=$d->email;?>"><?=$d->email;?></a></td>
        </tr>

        <tr>
          <td><?=$l['ADDRESS'];?></td>
          <td><?=$d->street . " " . $d->street_number;?>, <?=$d->postcode . " " . $d->city;?></td>
        </tr>
      </table>
    </div>

    <?php if (!empty($c)) {?>
    <div class="table-responsive" style="margin-bottom: 0; margin-top: 10px;">
      <table class="table table-bordered table-striped" style="margin-bottom: 0;">
        <tr>
          <th><?=$l['PRODUCT'];?></th>
          <th><?=$l['PRICE'];?></th>
        </tr>

        <?php foreach ($c as $p) {$productname = unserialize($p['name']) ? unserialize($p['name'])[$CFG['LANG']] : $p['name'];?>
        <tr>
          <td><?=$p['qty'] . "x " . htmlentities($productname);?></td>
          <td><?=$cur->infix($nfo->format($p['sum']), $cur->getBaseCurrency());?></td>
        </tr>
        <?php }?>
      </table>
    </div>
    <?php
}
            $info = ob_get_contents();
            ob_end_clean();
        } else {
            $name = $l['ERROR'];
            $info = $l['ERR1'];
        }

        die(json_encode([$name, $info]));
    }
    ?>

<div class="row">
	<div class="col-md-12">
		<h1 class="page-header"><?=$l['TITLE'];?></h1>

    <?php
if (isset($_GET['d'])) {
        $db->query("DELETE FROM guest_orders WHERE ID = " . intval($_GET['d']));
        if ($db->affected_rows) {
            echo '<div class="alert alert-success">' . $l['CANCELLED'] . '</div>';
            alog("order", "deleted", $_GET['d']);
        }
    }

    $t = new Table("SELECT * FROM guest_orders", [], ["time", "DESC"], "open_orders");

    echo $t->getHeader();
    ?>

		<form method="POST" id="invoice_form"><div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tr>
						<th>#</th>
						<th><?=$t->orderHeader("time", $l['DATE']);?></th>
						<th><?=$l['CUST'];?></th>
						<th width="30px"></th>
						<th width="30px"></th>
					</tr>

					<?php
$sql = $t->qry("time DESC");
    while ($row = $sql->fetch_object()) {$d = (object) unserialize($row->info);if (empty($d)) {
        continue;
    }
        foreach ($d as &$v) {
            $v = htmlentities($v);
        }
        ?>
					<tr>
						<td><?=$row->ID;?></td>
						<td><?=date("d.m.Y - H:i:s", $row->time);?></td>
						<td><a href="#" class="infoModal" data-id="<?=$row->ID;?>"><?=$d->firstname . " " . $d->lastname . (!empty($d->company) ? " ({$d->company})" : "");?></a></td>
						<td>
							<a href="<?=$CFG['PAGEURL'];?>cart/confirm/<?=$row->ID;?>/<?=$row->hash;?>" onclick="return confirm('<?=$l['CREATE_ACCOUNT'];?>');" target="_blank"><i class="fa fa-check"></i></a>
						</td>
            <td>
							<a href="?p=open_orders&d=<?=$row->ID;?>" onclick="return confirm('<?=$l['RD'];?>');"><i class="fa fa-times"></i></a>
						</td>
					</tr>
					<?php }if ($sql->num_rows == 0) {?>
					<tr>
						<td colspan="12"><center><?=$l['NT'];?></center></td>
					</tr>
					<?php }?>
				</table>
			</div>
      </form>

      <?=$t->getFooter();?>

      <div class="modal fade" id="infoModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title" id="infoModalTitle"><?=$l['PW'];?></h4>
            </div>
            <div class="modal-body" id="infoModalBody">
              <i class="fa fa-spinner fa-spin"></i> <?=$l['PW2'];?>
            </div>
          </div>
        </div>
      </div>

      <script>
      var orgTitle = $("#infoModalTitle").html();
      var orgBody = $("#infoModalBody").html();

      $(".infoModal").click(function(e){
        e.preventDefault();
        $("#infoModalTitle").html(orgTitle);
        $("#infoModalBody").html(orgBody);
        $("#infoModal").modal("show");

        $.get("?p=open_orders&id=" + $(this).data("id"), function(r){
          r = JSON.parse(r);
          $("#infoModalTitle").html(r[0]);
          $("#infoModalBody").html(r[1]);
        });
      })
      </script>
<?php }
