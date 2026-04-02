<?php
define('BYPASS_AUTH', true);
require __DIR__ . "/init.php";

if (!function_exists('__')) {function __($a, $b) {global $lang;return $lang[strtoupper($a)][strtoupper($b)];}}

if (isset($_GET['language'])) {
	$new_language = $db->real_escape_string(basename($_GET['language']));
	if (file_exists(__DIR__ . "/../languages/admin.$new_language.php")) {
		require __DIR__ . "/../languages/admin.$new_language.php";
		$session->set("admin_language", $new_language);
		$CFG['LANG'] = $new_language;
	}
} else if (is_string($session->get("admin_language")) && file_exists(__DIR__ . "/../languages/admin." . basename($session->get("admin_language")) . ".php")) {
	require __DIR__ . "/../languages/admin." . basename($session->get("admin_language")) . ".php";
	$CFG['LANG'] = basename($session->get("admin_language"));
}

if ((isset($_COOKIE['admin_auth']) && $_COOKIE['admin_auth'] != "" && $_COOKIE['admin_auth'] != ".") || (isset($_SESSION['credentials']) && count(explode(":", $_SESSION['credentials'])) > 1)) {
	header('Location: ./index.php');
	exit;
}

$ip = ip();

$step = "1";
if (in_array("reset", array_keys($_GET))) {
	$session->remove("resetAdmin")->remove("resetHash");
	header('Location: ./password.php');
	exit;
} else if (isset($_POST['reset'])) {
	try {
		if (empty($_POST['user']) || empty($_POST['email'])) {
			throw new Exception();
		}

		if (!$val->email($_POST['email'])) {
			throw new Exception();
		}

		$sql = $db->query("SELECT * FROM admins WHERE username = '" . $db->real_escape_string($_POST['user']) . "' AND email = '" . $db->real_escape_string($_POST['email']) . "'");
		if ($sql->num_rows != 1) {
			alog("login_password", "log_not_found", $_POST['user'], $_POST['email']);
			throw new Exception();
		}

		$info = $sql->fetch_object();
		$rights = unserialize($info->rights);
		if (!in_array("55", $rights)) {
			alog("login_password", "log_no_rights", $_POST['user']);
			throw new Exception();
		}

		alog("login_password", "log_ok", $_POST['user']);

		// Set code
		$code = substr(hash("sha512", uniqid("", true)), 0, 16);
		$session->set('resetAdmin', $info->ID)->set('resetHash', $code);

		// Send email
		$mtObj = new MailTemplate("Passwort zurücksetzen");
		$titlex = $mtObj->getTitle($CFG['LANG']);
		$mail = $mtObj->getMail($CFG['LANG'], $info->name);
		
		$id = $maq->enqueue([
			"c" => $code,
		], $mtObj, $info->email, $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", 0, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));
		$maq->send(1, $id, true);
		$maq->replace($id, Array($code));

		$step = "2";
		$msg = "<div class='alert alert-info'>" . __("login_password", "ok") . "</div>";
		$tfa = !empty($info->tfa) && $info->tfa != "none";
	} catch (Exception $ex) {
		$msg = "<div class='alert alert-danger'>" . __("login_password", "fail") . "</div>";
	}
} else if ($session->get("resetAdmin") !== false) {
	$sql = $db->query("SELECT rights, tfa, ID, username FROM admins WHERE ID = '" . $db->real_escape_string($session->get('resetAdmin')) . "'");
	if ($sql->num_rows == 1) {
		$info = $sql->fetch_object();
		$rights = unserialize($info->rights);
		if (in_array("55", $rights)) {
			$step = "2";
			$msg = "<div class='alert alert-info'>" . __("login_password", "ok") . "</div>";
			$tfa = !empty($info->tfa) && $info->tfa != "none";

			if (isset($_POST['set'])) {
				try {
					if (empty($_POST['code']) || strlen($session->get("resetHash")) != 16 || strtoupper($session->get("resetHash")) != strtoupper(trim($_POST['code']))) {
						alog("login_password", "log_wrong_code");
						throw new Exception("code");
					}
					$codeCorrect = true;

					if (empty($_POST['new']) || empty($_POST['new2']) || $_POST['new'] != $_POST['new2']) {
						throw new Exception("repeat");
					}

					if (strlen($_POST['new']) < 8) {
						throw new Exception("length");
					}

					$salt = $sec->generateSalt();
					$password = $db->real_escape_string($sec->adminHash($_POST['new'], $salt, $_POST['pw_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1));
					$db->query("UPDATE admins SET password = '$password', salt = '$salt' WHERE ID = " . $info->ID . " LIMIT 1");
					$session->remove("resetAdmin")->remove("resetHash");
					alog("login_password", "log_done");

					if (!$tfa) {
						$session->set("credentials", $info->username . ":" . $password);
						header('Location: ./index.php');
					} else {
						$session->set("pw_reset", "1");
						header('Location: ./login.php?usr=' . $info->username);
					}
					exit;
				} catch (Exception $ex) {
					$msg = "<div class='alert alert-danger'>" . __("login_password", "error_" . $ex->getMessage()) . "</div>";
				}
			}
		}
	}
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">

    <title><?=__("login_password", "title");?> | <?=$CFG['PAGENAME'];?> <?=__("general", "admin_area");?></title>

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
                        <h3 class="panel-title"><?=$CFG['PAGENAME'];?> <small><?=__("login_password", "title");?></small></h3>
                    </div>
                    <div class="panel-body">
                        <?=isset($msg) ? $msg : "";?>
                        <form role="form" method="POST" id="password_reset">
                            <fieldset>
                                <?php if ($step == "1") {?>
                                <div class="form-group">
                                    <input class="form-control" placeholder="<?=__("login", "user");?>" name="user" value="<?=isset($_POST['user']) ? htmlentities($_POST['user']) : "";?>" type="text" autofocus>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="<?=__("login_password", "email");?>" name="email" value="<?=isset($_POST['email']) ? htmlentities($_POST['email']) : "";?>" type="text">
                                </div>
                                <input type="submit" name="reset" class="btn btn-lg btn-warning btn-block" value="<?=__("login_password", "do");?>">
                                <?php } else {?>
                                <div class="form-group">
                                    <input class="form-control" placeholder="<?=__("login_password", "code");?>" name="code" value="<?=isset($_POST['code']) ? htmlentities($_POST['code']) : "";?>" type="text"<?=!isset($codeCorrect) ? " autofocus" : "";?>>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="<?=__("login_password", "new");?>"
                                           id="new" name="new"
                                           type="password"<?=isset($codeCorrect) ? " autofocus" : "";?>>
                                    <input type="hidden" id="new_hashed" value=""/>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="<?=__("login_password", "new2");?>"
                                           id="new2" name="new2" type="password">
                                    <input type="hidden" id="new2_hashed" value=""/>
                                </div>
                                <?=$tfa ? "<div class='alert alert-warning'>" . __("login_password", "tfa") . "</div>" : "";?>
                                    <input type="hidden" id="pw_type" name="pw_type" value="plain"/>
									<?=CSRF::html(); ?>
                                <input type="submit" name="set" class="btn btn-lg btn-success btn-block" value="<?=__("login_password", "do2");?>">
                                <a href="./password.php?reset" class="btn btn-sm btn-warning btn-block"><?=__("login_password", "restart");?></a>
                                <?php }?>
                            </fieldset>
                        </form>
                    </div>
                </div>

                <center><?=__("login_password", "ip");?>: <?=$ip;?><br /><a href="login.php"><?=__("login_password", "back");?></a></center>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="res/js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="res/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="res/js/plugins/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script
        src="res/js/sb-admin-2.js"></script><?php if ($CFG['HASH_METHOD_ADMIN'] != "plain" && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1) {?>

        <!-- Clientside hashing -->
        <script type="text/javascript"
                src="<?=$raw_cfg['PAGEURL'];?>lib/crypt/<?=rtrim($CFG['HASH_METHOD_ADMIN'], "salt");?>.js"></script>
        <script type="text/javascript">var hash_method = '<?=$CFG["HASH_METHOD_ADMIN"];?>';</script>
        <script src="res/js/crypto.js"></script>
    <?php }?>
</body>

</html>