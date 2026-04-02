<?php
$id = intval($_GET['id'] ?? 0);

if (!array_key_exists($id, $contracts)) {
    header('Location: ./');
    exit;
}

$info = @json_decode(file_get_contents(rtrim($sdUrl, "/") . "/api/" . $resellerInfo->ID . "/" . $resellerInfo->api_key . "/hosting/info?id=" . $id), true);
if (!$info || ($info['code'] ?? "500") != "100") {
    header('Location: ./');
    exit;
}
$info = $info['data'];
$contract = (array) $contracts[$id];

if (isset($_POST['cancel_date'])) {
    @file_get_contents(rtrim($sdUrl, "/") . "/api/" . $resellerInfo->ID . "/" . $resellerInfo->api_key . "/hosting/cancel?id=" . $id . "&date=" . urlencode($_POST['cancel_date']));
    header('Location: ./?p=contract&id=' . $id);
    exit;
}
?>
<style>
body {
  padding-top: 40px;
  padding-bottom: 40px;
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <h3><a href="./"><i class="fa fa-home"></i></a> <?=$pageName;?> <span class="pull-right"><a href="?p=logout"><i class="fa fa-sign-out"></i></a></h3>

            <div class="panel panel-default">
                <div class="panel-body">
                    <b>#<?=$id;?> <?=$info["name"];?></b>

                    <?php
if (isset($_POST['action']) && array_key_exists($_POST['action'], $info["tasks"])) {
    $ex = explode(",", $info["tasks"][$_POST['action']]);
    $pars = [
        "id" => $id,
        "task" => $_POST['action'],
    ];
    foreach ($ex as $field) {
        $pars[$field] = $_POST[$field] ?? "";
    }
    $res = @json_decode(file_get_contents(rtrim($sdUrl, "/") . "/api/" . $resellerInfo->ID . "/" . $resellerInfo->api_key . "/hosting/task?" . http_build_query($pars)), true);

    echo '<div class="alert alert-info">' . $res["message"] . '</div>';
}
?>

                    <?=$info["output"];?>

                    <?php
if ($info["period"] != "onetime" && $info["period"] != "empty") {
    ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">Kündigung <small>Cancellation</small></div>
                            <div class="panel-body">
                                <?php
if ($info["cancellation_date"] != "0000-00-00") {
        echo "Für diesen Vertrag wurde eine Kündigung eingereicht.<br /><i>For this contract, a cancellation exists.</i><form style=\"margin-top: 10px;\" method=\"POST\"><div class=\"form-group\"><select name=\"cancel_date\" readonly=\"\" class=\"form-control\">";
        echo "<option value=\"0000-00-00\" selected=\"\">{$info['cancellation_date']}</option>";
        echo '</select></div><button type="submit" class="btn btn-primary btn-block">Kündigung zurücknehmen<br /><i>Revoke cancellation</i></button></form>';
    } else {
        $cinfo = @json_decode(file_get_contents(rtrim($sdUrl, "/") . "/api/" . $resellerInfo->ID . "/" . $resellerInfo->api_key . "/hosting/cancel?id=" . $id), true);
        if (!$cinfo || ($cinfo['code'] ?? "500") != "100") {
            header('Location: ./');
            exit;
        }
        $cinfo = $cinfo['data'];

        $dates = $cinfo["dates"] ?? null;
        if (!is_array($dates) || !count($dates)) {
            echo "Internal Server Error";
        } else {
            echo "Bitte wählen Sie ein Datum zur Kündigung.<br /><i>Please choose a cancellation date.</i><form style=\"margin-top: 10px;\" method=\"POST\"><div class=\"form-group\"><select name=\"cancel_date\" class=\"form-control\">";
            foreach ($dates as $date) {
                echo '<option>' . $date . '</option>';
            }
            echo '</select></div><button type="submit" class="btn btn-primary btn-block">Vertrag kündigen<br /><i>Cancel contract</i></button></form>';
        }
    }
    ?>
                            </div>
                        </div>
                        <?php
}

if (count($info['tasks'])) {
    ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">Aktionen <small>Tasks</small></div>
                            <div class="panel-body">
                                <?php foreach ($info['tasks'] as $method => $fields) {?>
                                    <form method="POST" class="form-inline">
                                        <?php
$ex = explode(",", $fields);
        foreach ($ex as $field) {
            if (!empty($field)) {
                echo '<input type="text" name="' . $field . '" class="form-control" placeholder="' . $field . '"> ';
            }
        }

        echo '<input type="submit" name="action" value="' . $method . '" class="btn btn-default">';
        ?>
                                    </form>
                                <?php }?>
                            </div>
                        </div>
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
</div>