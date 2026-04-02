<?php
$l = $lang['API'];
title($l['TITLE']);

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (isset($_GET['generate'])) {
    do {
        $api_key = $sec->generatePassword(64, false, "ld");
        if ($db->query("SELECT 1 FROM admins WHERE api_key = '" . $db->real_escape_string($api_key) . "'")->num_rows > 0) {
            continue;
        }

        $db->query("UPDATE admins SET api_key = '" . $db->real_escape_string($api_key) . "' WHERE ID = " . $adminInfo->ID . " LIMIT 1");
        alog("api", "key_generated");
        break;
    } while (true);

    header('Location: ?p=api');
    exit;
}
?>

<div class="row">
	<div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?></h1>

        <p style="text-align: justify;"><?=$l['INTRO'];?></p>

        <?php if (strlen($adminInfo->api_key) != 64) {?>
        <div class="alert alert-warning"><?=$l['NAY'];?></div>
        <?php } else {?>
        <?=$l['URL'];?>: <code><?=rtrim($raw_cfg['PAGEURL'], "/") . "/admin/api.php";?></code><br />
        <?=$l['KEY'];?>: <code><?=$adminInfo->api_key;?></code> <a href="?p=api&generate=1"><i class="fa fa-undo"></i></a><br /><br />
        <?=$l['SH'];?><br /><br />
        <?php }?>

        <div class="alert alert-info"><?=$l['AD'];?></div>
    </div>
</div>