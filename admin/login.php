<?php
define('BYPASS_AUTH', true);
require __DIR__ . "/init.php";

if (!function_exists('__')) {function __($a, $b)
    {global $lang;return $lang[strtoupper($a)][strtoupper($b)];}}

// Build admin parameters
$notAllowed = array("language", "incorrect", "tfa", "usr", "c");
$parameters = array();
foreach ($_GET as $k => $v) {
    if (!in_array($k, $notAllowed)) {
        $parameters[$k] = $v;
    }
}

if (isset($_GET['language'])) {
    $new_language = $db->real_escape_string(basename($_GET['language']));
    if (file_exists(__DIR__ . "/../languages/admin.$new_language.php")) {
        require __DIR__ . "/../languages/admin.$new_language.php";
        $session->set("admin_language", $new_language);
    }
} else if (is_string($session->get("admin_language")) && file_exists(__DIR__ . "/../languages/admin." . basename($session->get("admin_language")) . ".php")) {
    require __DIR__ . "/../languages/admin." . basename($session->get("admin_language")) . ".php";
}

if ((isset($_COOKIE['admin_auth']) && $_COOKIE['admin_auth'] != "" && $_COOKIE['admin_auth'] != ".") || (isset($_SESSION['credentials']) && count(explode(":", $_SESSION['credentials'])) > 1)) {
    header('Location: ./index.php' . rtrim("?" . http_build_query($parameters), "?"));
    exit;
}

if (isset($_GET['tfa'])) {
    $ga = $tfa;
    $tfaCode = $ga->getCode($_GET['tfa']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">

    <title><?=__("login", "title");?> | <?=$CFG['PAGENAME'];?> <?=__("general", "admin_area");?></title>

    <!-- Bootstrap Core CSS -->
    <link href="res/css/bootstrap.min.css" rel="stylesheet">

    <link href="res/css/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">
	<link rel="shortcut icon" href="<?=$CFG['PAGEURL'];?>themes/favicon.ico" type="image/x-icon" />

    <!-- Custom CSS -->
    <link href="res/css/sb-admin-2.css" rel="stylesheet">
    <link href="res/css/custom.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="res/fonts/awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>
    <div class="language_switcher">
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fa fa-language"></i></a> &nbsp;
            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?=__("login", "change_language");?></a>
            <ul class="dropdown-menu">
                <?php foreach ($adminLanguages as $k => $v) {?>
                <li<?php if ($v == $lang['NAME']) {
    echo " class='active'";
}
    ?>><a href="?language=<?=$k;?>"><?=$v;?></a></li>
                <?php }?>
            </ul>
        </li>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?=$CFG['PAGENAME'];?> <small><?=__("general", "admin_area");?></small><i class="fa fa-spinner fa-pulse pull-right" id="initSpin"></i></h3>
                    </div>
                    <div class="panel-body" id="tfaForm" style="display: none;">
                        <form method="POST">
                            <div class="alert alert-danger" style="display: none;" id="tfaError"><?=__("login", "tfa_wrong");?></div>
                            <div class="form-group">
                                <input class="form-control" placeholder="<?=__("login", "tfa");?>" name="2fa" value="<?=isset($tfaCode) ? htmlentities($tfaCode) : "";?>" type="password">
                            </div>

                            <input type="submit" id="tfa-button" class="btn btn-lg btn-success btn-block" value="<?=__("login", "do");?>">
                        </form>
                    </div>
                    <div class="panel-body" id="doingLogin" style="display: none;">
                        <center>
                            <img src="./res/img/gears.gif" style="max-width: 110px;" title="<?=__("general", "pleasewait");?>..." alt="<?=__("general", "pleasewait");?>..."><br />
                            <h2><?=__("general", "pleasewait");?>...</h2>
                        </center>
                    </div>
                    <div class="panel-body" id="loginForm" style="display: none;">
                        <div class="alert alert-danger" id="login_error" style="display: none;"><?=__("login", "wrong");?></div>
                        <?php if ($session->get("pw_reset") !== false) {$session->remove("pw_reset");?><div class="alert alert-success"><?=__("login_password", "done");?></div><?php } else {?>
						<noscript><div class="alert alert-warning"><?=__("login", "js");?></div></noscript>
						<?php }?>
                        <form role="form" id="login-form" method="POST" action="index.php<?=rtrim("?" . http_build_query($parameters), "?");?>">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="<?=__("login", "user");?>" name="user" value="<?=isset($_GET['usr']) && $_GET['usr'] != "" ? htmlentities($_GET['usr']) : "";?>" type="text" <?php if (!isset($_GET['usr']) || $_GET['usr'] == "") {
    echo 'autofocus';
}
?>>
                                </div>
                                <div class="input-group" style="margin-bottom: 15px;">
                                    <input class="form-control" id="password" placeholder="<?=__("login", "password");?>" name="password" type="password" <?php if (isset($_GET['usr']) && $_GET['usr'] != "") {
    echo 'autofocus';
}
?>>
                                    <span class="input-group-addon"><a href="./password.php"><i class="fa fa-envelope-o"></i></a></span>
                                </div>
                                <div class="checkbox">
                                <label>
                                <input name="cookie" type="checkbox" value="true" <?=isset($_GET['c']) ? "checked" : "";?>> <?=__("login", "remember");?>
                                </label>
                                </div>
                                <?=CSRF::html();?>
                                <input type="hidden" name="login" value="do" />
                                <input type="submit" name="login" id="login-button" class="btn btn-lg btn-success btn-block" value="<?=__("login", "do");?>">
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
            <?php
foreach ($addons->runHook("AdminLoginHtml", []) as $res) {
    echo $res;
}
?>
        </div>
    </div>

    <!-- jQuery -->
    <script src="res/js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="res/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="res/js/plugins/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="res/js/sb-admin-2.js"></script><?php if ($CFG['HASH_METHOD_ADMIN'] != "plain" && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1) {?>

    <!-- Clientside hashing -->
    <script type="text/javascript" src="<?=$raw_cfg['PAGEURL'];?>lib/crypt/<?=rtrim($CFG['HASH_METHOD_ADMIN'], "salt");?>.js"></script>
    <script type="text/javascript">var hash_method = '<?=$CFG["HASH_METHOD_ADMIN"];?>';</script>
    <script src="res/js/crypto.js?ver=201811011920"></script>
    <?php }?>

    <script>
    $(document).ready(function() {
        setTimeout(function() {
            <?=!empty($_GET['incorrect']) ? '$("#login_error").show();' : ''; ?>

            $("#loginForm").slideDown(function() {
                $("#initSpin").hide();
            });
        }, 500);
    });
    </script>
</body>

</html>