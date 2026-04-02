<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['STAT_LIABILITIES'];

title($l['TITLE']);
menu("statistics");

if (!$ari->check(40)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "stat_liabilities");} else {

    $additionalJS = '
        function saveStemResponse(text) {
            $("#saveStemButton").prop("disabled", true);

            if(text == "saved"){
                $("#saveStemButton").html("' . $lang['GENERAL']['SAVED'] . '");
                $("#saveStemButton").addClass("btn-success");
            } else {
                $("#saveStemButton").html("' . $lang['GENERAL']['SAVE_FAILED'] . '");
                $("#saveStemButton").addClass("btn-danger");
            }

            setTimeout(function() {
                $("#saveStemButton").html("' . $lang['GENERAL']['SAVE'] . '");
                $("#saveStemButton").removeClass("btn-success btn-danger");
                $("#saveStemButton").prop("disabled", false);
            }, 1750);
        }

        function saveStem() {
            $.ajax({
                url : "./?p=ajax",
                data : { action : "save_stem", stem_auto : $("#stem_auto").val(), csrf_token: "' . CSRF::raw() . '" },
                dataType : "JSON",
                type : "POST",
                cache: false,
                success : function(succ) {
                    saveStemResponse(succ.responseText);
                },
                error : function(err) {
                    saveStemResponse(err.responseText);
                }
            });
        }';

    if (isset($_POST['save_stem'])) {
        $stem = $db->real_escape_string($nfo->phpize($_POST['stem']));
        $db->query("UPDATE settings SET `value` = '$stem' WHERE `key` = 'stem' LIMIT 1");
        if ($db->affected_rows > 0) {
            $suc = $l['SUC1'];
            alog("general", "stem", $other, $CFG['STEM']);
            $CFG['STEM'] = $stem;
        }
    }

    if (isset($_POST['save_loan'])) {
        $loan = $db->real_escape_string($nfo->phpize($_POST['loan']));
        $db->query("UPDATE settings SET `value` = '$loan' WHERE `key` = 'loan' LIMIT 1");
        if ($db->affected_rows > 0) {
            $suc = $l['SUC2'];
            alog("general", "loan", $other, $CFG['LOAN']);
            $CFG['LOAN'] = $loan;
        }
    }

    $credit = $db->query("SELECT SUM(credit) FROM clients WHERE credit > 0")->fetch_array()['SUM(credit)'];
    $affiliate_credit = $db->query("SELECT SUM(affiliate_credit) FROM clients WHERE affiliate_credit > 0")->fetch_array()['SUM(affiliate_credit)'];
    ?>

<style>
@media only screen and (min-width : 992px){
	#liability_borders {
		border-left: 1.5px solid #eee; border-right: 1px solid #eee;
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
	    	<center><h3><?=$l['CREDIT'];?></h3> <?php if ($credit <= 0) {?><?=$cur->infix($nfo->format($credit), $cur->getBaseCurrency());?><?php } else {?><a href="#" data-toggle="modal" data-target="#credit"><?=$cur->infix($nfo->format($credit), $cur->getBaseCurrency());?></a></center><div class="modal fade" id="credit" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?=$l['CREDIT'];?></h4>
      </div>
      <div class="modal-body">
        <ul>
        <?php
$sql = $db->query("SELECT ID, credit FROM clients WHERE credit > 0 ORDER BY credit DESC");
        while ($cus = $sql->fetch_object()) {
            echo '<li><a href="./?p=customers&edit=' . $cus->ID . '"">' . User::getInstance($cus->ID, "ID")->getfName() . '</a>: ' . $cur->infix($nfo->format($cus->credit), $cur->getBaseCurrency()) . '</li>';
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
            <center><h3><?=$l['AFC'];?></h3> <?php if ($affiliate_credit <= 0) {?><?=$cur->infix($nfo->format($affiliate_credit), $cur->getBaseCurrency());?><?php } else {?><a href="#" data-toggle="modal" data-target="#affiliate_credit"><?=$cur->infix($nfo->format($affiliate_credit), $cur->getBaseCurrency());?></a></center><div class="modal fade" id="affiliate_credit" tabindex="-1" role="dialog">
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
            echo '<li><a href="./?p=customers&edit=' . $cus->ID . '"">' . User::getInstance($cus->ID, "ID")->getfName() . '</a>: ' . $cur->infix($nfo->format($cus->affiliate_credit), $cur->getBaseCurrency()) . '</li>';
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
	    	<center><h3><?=$l['STEM'];?> <a href="#" data-toggle="modal" data-target="#stem_options"><i class="fa fa-gear"></i></a></h3> <form class="form-inline" method="POST" role="form"><div class="form-group"><div class="input-group"><?php $curObj = new Currency($cur->getBaseCurrency());if (trim($curObj->getPrefix()) != "") {?><span class="input-group-addon" id="basic-addon2"><?=$curObj->getPrefix();?></span> <?php }?><input type="text" name="stem" class="form-control" value="<?=$nfo->format($CFG['STEM']);?>" placeholder="100,00"><?php if (trim($curObj->getSuffix()) != "") {?> <span class="input-group-addon" id="basic-addon2"><?=$curObj->getSuffix();?></span><?php }?></div></div>&nbsp;<button type="submit" class="btn btn-primary" name="save_stem"><?=$l['SAVE'];?></button></form></center>
	    </div>

        <!-- stem modal -->
        <div class="modal fade" id="stem_options" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="languageModalLabel"><?=$l['STEMT'];?></h4>
              </div>
              <div class="modal-body">
                <input id="stem_auto" class="form-control" type="text" value="<?=$CFG['STEM_AUTO'];?>" placeholder="25">
                <p class="help-block"><?=$l['STEMH'];?></p>
                <button type="submit" onClick="saveStem();" id="saveStemButton" class="btn btn-primary btn-block"><?=$l['SAVE'];?></button>
              </div>
            </div>
          </div>
        </div>
        <!-- stem modal end -->

	    <div class="col-md-<?=$CFG['AFFILIATE_ACTIVE'] ? "3" : "4";?>">
	    	<center><h3><?=$l['LOAN'];?></h3> <form class="form-inline" method="POST" role="form"><div class="form-group"><div class="input-group"><?php if (trim($curObj->getPrefix()) != "") {?><span class="input-group-addon" id="basic-addon2"><?=$curObj->getPrefix();?></span> <?php }?><input type="text" name="loan" class="form-control" value="<?=$nfo->format($CFG['LOAN']);?>" placeholder="500,00"><?php if (trim($curObj->getSuffix()) != "") {?> <span class="input-group-addon" id="basic-addon2"><?=$curObj->getSuffix();?></span><?php }?></div></div>&nbsp;<button type="submit" class="btn btn-primary" name="save_loan"><?=$l['SAVE'];?></button></form></center>
	    </div>
	</div>

	<hr />

	<center><h2><?=$l['SUM'];?></h2> <?=$cur->infix($nfo->format($credit + $CFG['STEM'] + $CFG['LOAN'] + $affiliate_credit), $cur->getBaseCurrency());?></center>

<?php }?>