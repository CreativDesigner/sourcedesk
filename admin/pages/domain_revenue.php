<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['DOMAIN_REVENUE'];

title($l['TITLE']);
menu("statistics");

if (!$ari->check(40)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "domain_revenue");} else {

$eksum = $vksum = $rvsum = $sum = 0;
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?></h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th><?=$l['TLD']; ?></th>
                    <th><center><?=$l['ACTIVE']; ?></center></th>
                    <th><?=$l['YEARLYEK']; ?></th>
                    <th><?=$l['YEARLYVK']; ?></th>
                    <th><?=$l['YEARLYRV']; ?></th>
                </tr>

                <?php
                $sql = $db->query("SELECT * FROM domain_pricing ORDER BY `top` DESC, `tld` ASC");
                while ($row = $sql->fetch_object()) {
                    $info = $db->query("SELECT COUNT(*) c, SUM(recurring) r FROM domains WHERE `domain` LIKE '%." . $db->real_escape_string($row->tld) . "'")->fetch_object();

                    $sum += $info->c;
                    
                    $vk = $info->r / $row->period;
                    $vksum += $vk;

                    $ek = $row->renew_ek * $info->c;
                    $eksum += $ek;

                    $rv = $vk - $ek;
                    $rvsum += $rv;
                    ?>
                    <tr>
                        <td><?=$row->tld; ?></td>
                        <td><center><?=$info->c; ?></center></td>
                        <td><?=$cur->infix($nfo->format($ek, 4), $cur->getBaseCurrency()); ?></td>
                        <td><?=$cur->infix($nfo->format($vk, 4), $cur->getBaseCurrency()); ?></td>
                        <td><?=$cur->infix($nfo->format($rv, 4), $cur->getBaseCurrency()); ?></td>
                    </tr>
                    <?php
                }
                ?>

                <tr>
                    <th style="text-align: right"><?=$l['SUM']; ?></th>
                    <th><center><?=round($sum); ?></center></th>
                    <th><?=$cur->infix($nfo->format($eksum, 4), $cur->getBaseCurrency()); ?></th>
                    <th><?=$cur->infix($nfo->format($vksum, 4), $cur->getBaseCurrency()); ?></th>
                    <th><?=$cur->infix($nfo->format($rvsum, 4), $cur->getBaseCurrency()); ?></th>
                </tr>
            </table>
        </div>
    </div>
</div>
<small><?=$l['HINT']; ?></small>

<?php }?>