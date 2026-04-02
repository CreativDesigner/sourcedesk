<?php
$l = $lang['DOMAIN_LOG'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(13) || empty($_GET['d'])) {require __DIR__ . "/error.php";if (!$ari->check(13)) {
    alog("general", "insufficient_page_rights", "domain_log");
}
} else {
    ?>
<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE'];?> <small><?=htmlentities($_GET['d']);?></small></h1>

        <?php
if (!empty($_GET['i']) && is_object($sql = $db->query("SELECT * FROM domain_log WHERE domain = '" . $db->real_escape_string($_GET['d']) . "' AND ID = " . intval($_GET['i']))) && $sql->num_rows == 1) {
        $row = $sql->fetch_object();
        ?>
        <b><?=$l['REQ'];?></b>
        <pre><?=htmlentities($row->request);?></pre>
        <b><?=$l['RES'];?></b>
        <pre><?=htmlentities($row->response);?></pre>
        <?php
} else {

        $t = new Table("SELECT * FROM domain_log WHERE domain = '" . $db->real_escape_string($_GET['d']) . "'", [], ["time", "DESC"]);
        echo $t->getHeader();
        ?>
		<div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th><?=$t->orderHeader("time", $l['TIME']);?></th>
                    <th><?=$t->orderHeader("registrar", $l['REGISTRAR']);?></th>
                    <th><?=$t->orderHeader("url", $l['URL']);?></th>
                    <th width="30px"></th>
                </tr>

                <?php
$sql = $t->qry("`time` DESC");
        if ($sql->num_rows == 0) {?>
                <tr>
                    <td colspan="4">
                        <center>
                            <?=$l['NT'];?>
                        </center>
                    </td>
                </tr>
                <?php } else {
            while ($row = $sql->fetch_object()) {
                ?>
                <tr>
                    <td><?=$dfo->format($row->time, true, true);?></td>
                    <td><?=@htmlentities(DomainHandler::getRegistrarNames()[$row->registrar] ?: $row->registrar);?></td>
                    <td><?=htmlentities($row->url);?></td>
                    <td>
                        <center>
                            <a href="?p=domain_log&amp;d=<?=urlencode($row->domain);?>&amp;i=<?=$row->ID;?>">
                                <i class="fa fa-arrow-right"></i>
                            </a>
                        </center>
                    </td>
                </tr>
                <?php }}?>
            </table>
        </div>
        <?php echo $t->getFooter();} ?>
	</div>
</div>
<?php }?>