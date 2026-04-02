<?php
if (isset($_POST['email'])) {
    $mail = $db->real_escape_string(trim($_POST['email']));
    $pwd  = $db->real_escape_string($_POST['password']);

    $sql = $db->query("SELECT `password` FROM client_customers WHERE uid = {$resellerInfo->ID} AND mail = '$mail'");
    if ($sql->num_rows) {
        $hash = $sql->fetch_object()->password;
    } else {
        $hash = hash("sha512", "");
    }

    if (empty($pwd) || hash("sha512", $pwd) != $hash) {
        $error = true;
    } else {
        $_SESSION['reseller_login'] = $mail;
        header('Location: ./');
        exit;
    }
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
            <h3><?=$pageName; ?></h3>
            <div class="panel panel-default">
                <div class="panel-body"><form method="POST">
                    <div class="input-group<?=$error ?? false ? ' has-error' : ''; ?>">
                        <span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="john.doe@example.com" value="<?=htmlentities($_POST['email'] ?? ""); ?>">
                    </div>

                    <div class="input-group<?=$error ?? false ? ' has-error' : ''; ?>" style="margin-top: 10px;">
                        <span class="input-group-addon"><i class="fa fa-lock fa-fw"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="i7fa6d4pXo">
                    </div>

                    <button type="submit" class="btn btn-success btn-block" style="margin-top: 10px;"><i class="fa fa-arrow-circle-right fa-lg"></i></button>
                </form></div>
            </div>
        </div>
    </div>
</div>