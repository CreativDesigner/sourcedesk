<ul class="list-group" style="margin-bottom: 0;">
    <?php
foreach ($contracts as $contract) {
    $info = @json_decode(file_get_contents(rtrim($sdUrl, "/") . "/api/" . $resellerInfo->ID . "/" . $resellerInfo->api_key . "/hosting/info?id=" . $contract->ID), true);
    if (!$info || ($info['code'] ?? "500") != "100") {
        continue;
    }
    $info = $info['data'];
    ?>
    <a href="?p=contract&id=<?=$contract->ID;?>" class="list-group-item">#<?=$contract->ID;?> <?=htmlentities($info['name']);?><?=$info['cancellation_date'] != "0000-00-00" ? ' <span class="label label-default">' . $info['cancellation_date'] . '</span>' : '';?></a>
    <?php }?>
</ul>