<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['STAT_DEBTORS'];

title($l['TITLE']);
menu("statistics");

if (!$ari->check(40)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "stat_debtors");} else {

    if (isset($_POST['save_other'])) {
        $other = $db->real_escape_string($nfo->phpize($_POST['other']));
        $db->query("UPDATE settings SET `value` = '$other' WHERE `key` = 'debtors_other' LIMIT 1");
        if ($db->affected_rows > 0) {
            $suc = $l['SUC'];
            alog("general", "debtors_other", $other, $CFG['DEBTORS_OTHER']);
            $CFG['DEBTORS_OTHER'] = $other;
        }
    }

// Get open invoices
    $inv = new Invoice;
    $to = date("Y-m-d", $to);

    $openInvoices = 0;
    $sql = $db->query("SELECT ID FROM invoices WHERE status = 0");
    while ($row = $sql->fetch_object()) {
        $inv->load($row->ID);
        $openInvoices += $inv->getAmount();
    }

    $credit = $db->query("SELECT SUM(credit) FROM clients WHERE credit < 0")->fetch_array()['SUM(credit)'] / -1;
    $affiliate_credit = $db->query("SELECT SUM(affiliate_credit) FROM clients WHERE affiliate_credit < 0")->fetch_array()['SUM(affiliate_credit)'] / -1;
    ?>

<style>
@media only screen and (min-width : 992px){
	#liability_borders {
		border-left: 1.5px solid #eee;
	}
}
</style>

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?=$l['TITLE'];?></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>

    <?php if (isset($suc)) {
        echo "<div class='alert alert-success'>$suc</div>";
    }
    ?>

    <div class="row">
	    <div class="col-md-<?=$CFG['AFFILIATE_ACTIVE'] ? "3" : "4";?>">
	    	<center><h3><?=$l['CREDIT'];?></h3> <?php if ($credit <= 0) {?><font color="red"><?=$cur->infix($nfo->format($credit), $cur->getBaseCurrency());?></font><?php } else {?><a href="#" data-toggle="modal" data-target="#credit"><font color="red"><?=$cur->infix($nfo->format($credit), $cur->getBaseCurrency());?></font></a></center><div class="modal fade" id="credit" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['CREDIT'];?></h4>
      </div>
      <div class="modal-body">
        <ul>
        <?php
$sql = $db->query("SELECT ID, credit FROM clients WHERE credit < 0 ORDER BY credit ASC");
        while ($cus = $sql->fetch_object()) {
            echo '<li><a href="./?p=customers&edit=' . $cus->ID . '">' . User::getInstance($cus->ID, "ID")->getfName() . '</a>: <font color="red">' . $cur->infix($nfo->format($cus->credit / -1), $cur->getBaseCurrency()) . '</font></li>';
        }

        ?>
        </ul>
      </div>
    </div>
  </div>
</div><center><?php }?></center>
	    </div>

        <?php if ($CFG['AFFILIATE_ACTIVE']) {?>
        <div class="col-md-<?=$CFG['AFFILIATE_ACTIVE'] ? "3" : "4";?>">
            <center><h3><?=$l['AFC'];?></h3> <?php if ($affiliate_credit <= 0) {?><?=$cur->infix($nfo->format($affiliate_credit), $cur->getBaseCurrency());?><?php } else {?><a href="#" data-toggle="modal" data-target="#affiliate_credit"><font color="red"><?=$cur->infix($nfo->format($affiliate_credit), $cur->getBaseCurrency());?></font></a></center><div class="modal fade" id="affiliate_credit" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['AFC'];?></h4>
      </div>
      <div class="modal-body">
        <ul>
        <?php
$sql = $db->query("SELECT ID, affiliate_credit FROM clients WHERE affiliate_credit > 0 ORDER BY affiliate_credit DESC");
        while ($cus = $sql->fetch_object()) {
            echo '<li><a href="./?p=customers&edit=' . $cus->ID . '">' . User::getInstance($cus->ID, "ID")->getfName() . '</a>: ' . $cur->infix($nfo->format($cus->affiliate_credit), $cur->getBaseCurrency()) . '</li>';
        }

        ?>
        </ul>
      </div>
    </div>
  </div>
</div><center><?php }?></center>
        </div>
        <?php }?>

        <div class="col-md-<?=$CFG['AFFILIATE_ACTIVE'] ? "3" : "4";?>" id="liability_borders">
            <center><h3><?=$l['INVOICES'];?></h3> <?php if ($openInvoices <= 0) {?><font color="red"><?=$cur->infix($nfo->format($openInvoices), $cur->getBaseCurrency());?></font><?php } else {?><a href="#" data-toggle="modal" data-target="#invoices"><font color="red"><?=$cur->infix($nfo->format($openInvoices), $cur->getBaseCurrency());?></font></a></center><div class="modal fade" id="invoices" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['INVOICES'];?></h4>
      </div>
      <div class="modal-body">
        <ul>
        <?php
$hasOpenInvoices = array();
        $sql = $db->query("SELECT ID FROM invoices WHERE status = 0");
        while ($row = $sql->fetch_object()) {
            $inv->load($row->ID);
            if (isset($hasOpenInvoices[$inv->getClient()])) {
                $hasOpenInvoices[$inv->getClient()] += $inv->getAmount();
            } else {
                $hasOpenInvoices[$inv->getClient()] = $inv->getAmount();
            }

        }

        // Sort by amount descending
        arsort($hasOpenInvoices);

        foreach ($hasOpenInvoices as $user => $amount) {
            $sql = $db->query("SELECT ID FROM clients WHERE ID = $user LIMIT 1");
            if ($sql->num_rows == 1) {
                $uInfo = $sql->fetch_object();
            }

            if (!isset($uInfo)) {
                $cus = "<i>{$l['UK']}</i>";
            } else {
                $cus = "<a href='./?p=customers&edit=$user'>" . User::getInstance($uInfo->ID, "ID")->getfName() . "</a>";
            }

            echo "<li>$cus: <font color='red'>" . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "</font></li>";
        }
        ?>
        </ul>
      </div>
    </div>
  </div>
</div><center><?php }?></center>
        </div>

	    <div class="col-md-<?=$CFG['AFFILIATE_ACTIVE'] ? "3" : "4";?>" id="liability_borders">
	    	<center><h3><?=$l['OTHER'];?></h3> <form class="form-inline" method="POST" role="form"><div class="form-group"><div class="input-group"><?php $curObj = new Currency($cur->getBaseCurrency());if (trim($curObj->getPrefix()) != "") {?><span class="input-group-addon" id="basic-addon2"><?=$curObj->getPrefix();?></span> <?php }?><input type="text" style="color: red;" name="other" class="form-control" value="<?=$nfo->format($CFG['DEBTORS_OTHER']);?>" placeholder="100,00"><?php if (trim($curObj->getSuffix()) != "") {?> <span class="input-group-addon" id="basic-addon2"><?=$curObj->getSuffix();?></span><?php }?></div></div>&nbsp;<button type="submit" class="btn btn-primary" name="save_other"><?=$l['SAVE'];?></button></form></center>
	    </div>
	</div>

	<hr />

	<center><h2><?=$l['SUM'];?></h2> <font color="red"><?=$cur->infix($nfo->format($credit + $openInvoices + $CFG['DEBTORS_OTHER'] + $affiliate_credit), $cur->getBaseCurrency());?></font></center>

<?php }?>