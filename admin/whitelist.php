<?php
define('BYPASS_AUTH', true);
require __DIR__ . "/init.php";

if (!function_exists('__')) {function __($a, $b) {global $lang;return $lang[strtoupper($a)][strtoupper($b)];}}

// Build admin parameters
$notAllowed = Array("language", "incorrect", "tfa", "usr", "c");
$parameters = Array();
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

if (isset($_POST['admin_otp'])) {
	if (!$ari->otpAccess($_POST['admin_otp'])) {
		$error = __("whitelist", "wrong");
		alog("whitelist", "wrong_code", $_POST['admin_otp']);
	} else {
		alog("whitelist", "correct_code", $_POST['admin_otp']);
	}
} else if (isset($_POST['mysql_pw'])) {
	if ($_POST['mysql_pw'] != $CFG['DB']['PASSWORD']) {
		$error = __("whitelist", "mysql");
		alog("whitelist", "wrong_pw", $_POST['mysql_pw']);
	} else {
		$db->query("UPDATE settings SET `value` = '' WHERE `key` = 'admin_whitelist' LIMIT 1");
		$CFG['ADMIN_WHITELIST'] = "";
		alog("whitelist", "correct_pw");
	}
}

$ari->__construct();
if ($ari->accessAllowed()) {
	header('Location: ./index.php' . rtrim("?" . http_build_query($parameters), "?"));
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">

    <title><?=__("whitelist", "title");?> | <?=$CFG['PAGENAME'];?> <?=__("general", "admin_area");?></title>

    <!-- Bootstrap Core CSS -->
    <link href="res/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
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
                        <h3 class="panel-title"><?=$CFG['PAGENAME'];?> <small><?=__("whitelist", "title");?></small></h3>
                    </div>
                    <div class="panel-body">
                        <?=isset($error) ? "<div class=\"alert alert-danger\">$error</div>" : "";?>

                        <p id="admin_otp_hint" style="text-align: justify;"><?=__("whitelist", "info");?></p>

                        <small id="admin_otp_link"><a href="#" onclick="showOtp(); return false;"><?=__("whitelist", "show_otp");?></a> - <a href="#" onclick="showReset(); return false;"><?=__("whitelist", "clear");?></a> - <a href="<?=$CFG['PAGEURL'];?>"><?=__("whitelist", "page");?></a></small>

                        <div id="admin_otp" style="display: none; text-align: justify;">
                            <?=__("whitelist", "otp");?>
                            <form method="POST" style="margin-top:10px;">
                            <input type="text" class="form-control" name="admin_otp" placeholder="12345678" />
                            <?=CSRF::html(); ?>
                            <input type="submit" class="btn btn-success btn-block" style="margin-top:5px;" value="<?=__("whitelist", "otp_do");?>" />
                            </form>

                            <span style="float:right; margin-top:5px;"><small><a href="#" onclick="reset(); return false;"><?=__("whitelist", "back");?></a></small></span>
                        </div>

                        <div id="reset_whitelist" style="display: none; text-align: justify;">
                            <?=__("whitelist", "db");?>
                            <form method="POST" style="margin-top:10px;">
                            <input type="text" class="form-control" name="mysql_pw" placeholder="w44MVlQ0iS" />
                            <?=CSRF::html(); ?>
                            <input type="submit" class="btn btn-warning btn-block" style="margin-top:5px;" value="<?=__("whitelist", "clear_do");?>" />
                            </form>

                            <span style="float:right; margin-top:5px;"><small><a href="#" onclick="reset(); return false;"><?=__("whitelist", "back");?></a></small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="res/js/jquery.js"></script>
    <script type="text/javascript">
    function showOtp () {
        $("#admin_otp_hint").hide();
        $("#admin_otp_link").hide();
        $("#admin_otp").show();
    }

    function showReset () {
        $("#admin_otp_hint").hide();
        $("#admin_otp_link").hide();
        $("#reset_whitelist").show();
    }

    function reset() {
        $("#admin_otp_hint").show();
        $("#admin_otp_link").show();
        $("#reset_whitelist").hide();
        $("#admin_otp").hide();
    }
    </script>

    <!-- Bootstrap Core JavaScript -->
    <script src="res/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="res/js/plugins/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="res/js/sb-admin-2.js"></script>

</body>

</html>