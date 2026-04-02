<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['PASSWORD'];
title($l['TITLE']);

if (!$ari->check(6) && !$ari->check(5)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "password");} else {?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?></h1>
	<?php
if (isset($_POST['change_2fa']) && $_POST['code'] != "" && $adminInfo->tfa != "none" && $ari->check(5)) {
    $adminId = $adminInfo->ID;

    if ($db->query("SELECT * FROM admin_tfa WHERE user = $adminId AND code = '" . $db->real_escape_string($_POST['code']) . "'")->num_rows == 0 && $tfa->verifyCode($adminInfo->tfa, $_POST['code'], 2)) {
        $db->query("UPDATE admins SET tfa = 'none' WHERE ID = '" . $adminInfo->ID . "' LIMIT 1");
        echo '<div class="alert alert-success">' . $l['SUC1'] . '</div>';
        $db->query("DELETE FROM  admin_tfa WHERE user = " . $adminInfo->ID);
        $adminInfo->tfa = "none";
        alog("general", "tfa_deactivated");
    } else {
        echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $l['ERR1'] . '</div>';
    }
}

    if (isset($_POST['second_2fa']) && $_POST['code'] != "" && $adminInfo->tfa != "none" && $ari->check(5)) {
        $adminId = $adminInfo->ID;

        if ($db->query("SELECT * FROM admin_tfa WHERE user = $adminId AND code = '" . $db->real_escape_string($_POST['code']) . "'")->num_rows == 0 && $tfa->verifyCode($adminInfo->tfa, $_POST['code'], 2)) {
            $code = $sec->generatePassword(8, false, "ld");
            $stfa = hash("sha512", $code . $CFG['SALT']);
            $db->query("UPDATE admins SET tfa_second = '$stfa', tfa_valid = " . (time() + 600) . " WHERE ID = '" . $adminInfo->ID . "' LIMIT 1");
            echo '<div class="alert alert-info">' . $l['SUC2'] . ' <b>' . $code . '</b></div>';
            $db->query("INSERT INTO admin_tfa (`user`, `code`, `time`) VALUES (" . $adminInfo->ID . ", '" . $db->real_escape_string($_POST['code']) . "', " . time() . ")");
            alog("general", "temp_2fa_generated");
        } else {
            echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $l['ERR1'] . '</div>';
        }
    }

    if (isset($_POST['change_2fa']) && $_POST['code'] != "" && $_POST['secret'] != "" && $adminInfo->tfa == "none" && $ari->check(5)) {
        if ($secret = $tfa->verifyCode($_POST['secret'], $_POST['code'], 2, null, true)) {
            $db->query("UPDATE admins SET tfa = '" . $db->real_escape_string($secret) . "' WHERE ID = '" . $adminInfo->ID . "' LIMIT 1");
            $db->query("INSERT INTO admin_tfa (`user`, `code`, `time`) VALUES (" . $adminInfo->ID . ", '" . $db->real_escape_string($_POST['code']) . "', " . time() . ")");
            echo '<div class="alert alert-success">' . $l['SUC3'] . '</div>';
            alog("general", "tfa_activated");
            $adminInfo->tfa = $secret;

            $tfaCdt = ":" . $adminInfo->tfa;
            $session->set("credentials", $adminInfo->username . ":" . $adminInfo->password . $tfaCdt);

            // Change his cookie
            if (isset($_COOKIE['admin_auth']) && $db->query("SELECT ID FROM admin_cookie WHERE user = '" . $adminInfo->ID . "' AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' LIMIT 1")->num_rows == 1) {
                // Update cookie in database
                $auth = $db->real_escape_string($adminInfo->password . $tfaCdt);
                $db->query("UPDATE admin_cookie SET auth = '$auth' WHERE user = '" . $adminInfo->ID . "' AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' LIMIT 1");
            }
        } else {
            echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $l['ERR2'] . '</div>';
        }
    }

    if (isset($_POST['change']) && $ari->check(6)) {
        $ex = explode(":", $session->get('credentials'));
        $salt = $sec->generateSalt();
        if ($sec->adminHash($_POST['old_pwd'], $adminInfo->salt, $_POST['pw_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1) == $ex[1]) {
            if ($_POST['pwd'] == $_POST['pwd2'] && strlen($_POST['pwd']) >= 8) {
                $salt = $sec->generateSalt();
                $pwd = $sec->adminHash($_POST['pwd'], $salt, $_POST['pw_type'] == "hashed" && $CFG['CLIENTSIDE_HASHING_ADMIN'] == 1);
                $db->query("UPDATE admins SET password = '" . $db->real_escape_string($pwd) . "', salt = '" . $db->real_escape_string($salt) . "' WHERE username = '" . $adminInfo->username . "' LIMIT 1");

                $tfaCdt = "";
                if (isset($ex[2])) {
                    $tfaCdt = ":" . $ex[2];
                }

                $session->set("credentials", $adminInfo->username . ":" . $pwd . $tfaCdt);

                // Change his cookie
                if (isset($_COOKIE['admin_auth']) && $db->query("SELECT ID FROM admin_cookie WHERE user = '" . $adminInfo->ID . "' AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' LIMIT 1")->num_rows == 1) {
                    // Update cookie in database
                    $auth = $db->real_escape_string($pwd . $tfaCdt);
                    $db->query("UPDATE admin_cookie SET auth = '$auth' WHERE user = '" . $adminInfo->ID . "' AND string = '" . $db->real_escape_string(hash("sha512", $_COOKIE['admin_auth'])) . "' LIMIT 1");
                }

                alog("general", "password_changed");

                echo '<div class="alert alert-success">' . $l['SUC4'] . '</div>';
                unset($_POST);
            } else {
                echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $l['ERR3'] . '</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $l['ERR4'] . '</div>';
        }
    }

    ?></div>
        <?php if ($ari->check(6)) {?><div class="col-md-12">
        <div class="panel panel-primary">
        <div class="panel-heading"><?=$l['CPW'];?></div>
        <div class="panel-body">
                <form accept-charset="UTF-8" role="form" method="post" id="change_password">
      <fieldset>
		<div class="form-group">
            <input class="form-control" placeholder="<?=$l['OPW'];?>" name="old_pwd" id="old_pwd" type="password"
                   required="" <?=isset($_POST['old_pwd']) ? 'value="' . $_POST['old_pwd'] . '"' : "";?>>
            <input type="hidden" id="old_pwd_hashed" value=""/>
        </div>
        <div class="form-group">
            <input class="form-control" placeholder="<?=$l['NPW'];?>" name="pwd" id="pwd" type="password"
                   required="" <?=isset($_POST['pwd']) ? 'value="' . $_POST['pwd'] . '"' : "";?>>
            <input type="hidden" id="pwd_hashed" value=""/>
        </div>
        <div class="form-group">
            <input class="form-control" placeholder="<?=$l['NPW2'];?>" name="pwd2" id="pwd2" type="password"
                   required="" <?=isset($_POST['pwd2']) ? 'value="' . $_POST['pwd2'] . '"' : "";?>>
            <input type="hidden" id="pwd2_hashed" value=""/>
        </div>
		<div class="form-group" style="margin-bottom: 0;">
            <input type="hidden" name="pw_type" id="pw_type" value="plain"/>
          <button type="submit" name="change" class="btn btn-primary btn-block">
		  <?=$l['CPW'];?>
          </button>
        </div>
      </fieldset>
    </form></div></div></div><?php }?>

    <?php if ($ari->check(5)) {?>
    <div class="col-md-6">
    	<div class="panel panel-default">
        	<div class="panel-heading"><?=$l['2FA'];?></div>
        	<div class="panel-body">
        		<?php if ($adminInfo->tfa == "none") {?>
        			<?php
if (isset($_POST['secret']) && $_POST['secret'] != "" && strlen($_POST['secret']) == 32) {
        $sec = $_POST['secret'];
    } else {
        $sec = $tfa->createSecret(32);
    }

        $qrc = $tfa->getQRCodeGoogleUrl($adminInfo->username, $sec, urlencode($CFG['PAGENAME'] . " " . $l['TFASUFF']));
        ?>
					<div><img src="<?=$qrc;?>" alt="<?=$sec;?>" title="<?=$sec;?>" style="float: left; margin-right: 20px;">

					<p style="text-align:justify;"><?=$l['TFAI'];?></p>

						<form method="post">
					      <fieldset>
					        <div class="form-group input-group">
					          <span class="input-group-addon">
					            <i class="glyphicon glyphicon-lock">
					            </i>
					          </span>
					          <input class="form-control" placeholder="<?=$l['TFAC'];?>" name="code" type="text" value="" required="">
							  <input type="hidden" name="secret" value="<?=$sec;?>">
					        </div>
					        <div class="form-group">
					          <button type="submit" name="change_2fa" class="btn btn-default btn-block">
					            <?=$l['TFAA'];?>
					          </button>
					        </div>
					      </fieldset>
					    </form>
					</div>
        		<?php } else {?>
        			<p style="text-align:justify;"><?=$l['TFAISA'];?></p>
					 <form method="post">
				      <fieldset>
				        <div class="form-group input-group">
				          <span class="input-group-addon">
				            <i class="glyphicon glyphicon-lock">
				            </i>
				          </span>
				          <input class="form-control" placeholder="2FA-Code" name="code" type="text" value="" required="">
				        </div>
				        <div class="form-group" style="margin-bottom: 0;">
									<div class="row">
										<div class="col-md-6">
											<button type="submit" name="second_2fa" class="btn btn-primary btn-block">
												<?=$l['TFAZC'];?>
											</button>
										</div>

										<div class="col-md-6">
											<button type="submit" name="change_2fa" class="btn btn-warning btn-block">
												<?=$l['TFADEA'];?>
											</button>
										</div>
				        </div>
				      </fieldset>
				    </form>
        		<?php }?>
        	</div>
        </div>
    </div>
   	<?php }?>

		<div class="col-md-<?=$ari->check(5) ? "6" : "12";?>">
    	<div class="panel panel-default">
        	<div class="panel-heading"><?=$l['SA'];?> <span class="pull-right"><a href="#" id="add_sa"><i class="fa fa-plus"></i></a></span></div>
        	<div class="panel-body">
						<?php
if (isset($_POST['action']) && $_POST['action'] == "save_sa") {
        if (!is_array($_POST['url'])) {
            $_POST['url'] = [];
        }

        if (!is_array($_POST['user'])) {
            $_POST['user'] = [];
        }

        if (!is_array($_POST['login2'])) {
            $_POST['login2'] = [];
        }

        $sa = [];
        foreach ($_POST['url'] as $i => $url) {
            $user = array_key_exists($i, $_POST['user']) ? $_POST['user'][$i] : "";
            if (empty($url) || empty($user)) {
                continue;
            }

            array_push($sa, [$url, $user, "", array_key_exists($i, $_POST['login2']) && $_POST['login2'][$i] ? true : false]);
        }

        $adminInfo->sa = serialize($sa);
        $db->query("UPDATE admins SET sa = '" . $db->real_escape_string($adminInfo->sa) . "' WHERE ID = " . $adminInfo->ID);
        alog("general", "single_auth_saved");
        echo '<div class="alert alert-success" style="margin-bottom: 10px;">' . $l['SASAVED'] . '</div>';
    }
    ?>

        		<p style="text-align:justify;"><?=$l['SAI'];?></p>

						<form method="POST">
							<div id="sa_rows">
								<?php
$sa = unserialize($adminInfo->sa) ?: [];

    foreach ($sa as $k => $v) {
        if (!is_array($v) || count($v) < 2 || empty($v[0]) || empty($v[1])) {
            unset($sa[$k]);
        }
    }

    if (count($sa) == 0) {
        ?>
									<div class="row">
										<div class="col-xs-4">
											<input type="text" name="url[]" value="" placeholder="<?=$l['SAU'];?>" class="form-control">
										</div>

										<div class="col-xs-4" style="padding-left: 0;">
											<input type="text" name="user[]" value="" placeholder="<?=$l['SAN'];?>" class="form-control">
										</div>

										<div class="col-xs-4" style="padding-left: 0;">
											<select name="login2[]" class="form-control">
												<option value="1"><?=$l['SAL1'];?></option>
												<option value="0"><?=$l['SAL0'];?></option>
											</select>
										</div>
									</div>
									<?php
} else {
        $i = 0;
        foreach ($sa as $v) {
            ?>
										<div class="row"<?=$i > 0 ? ' style="margin-top: 10px;"' : '';?>>
											<div class="col-xs-4">
												<input type="text" name="url[]" value="<?=htmlentities($v[0]);?>" placeholder="<?=$l['SAU'];?>" class="form-control">
											</div>

											<div class="col-xs-4" style="padding-left: 0;">
												<input type="text" name="user[]" value="<?=htmlentities($v[1]);?>" placeholder="<?=$l['SAN'];?>" class="form-control">
											</div>

											<div class="col-xs-4" style="padding-left: 0;">
												<select name="login2[]" class="form-control">
													<option value="1"><?=$l['SAL1'];?></option>
													<option value="0"<?=!($v[3] ?? false) ? ' selected=""' : '';?>><?=$l['SAL0'];?></option>
												</select>
											</div>
										</div>
										<?php
$i++;
        }
    }
    ?>
							</div>

							<div class="row" style="display: none; margin-top: 10px;" id="sa_template">
								<div class="col-xs-4">
									<input type="text" name="url[]" value="" placeholder="<?=$l['SAU'];?>" class="form-control">
								</div>

								<div class="col-xs-4" style="padding-left: 0;">
									<input type="text" name="user[]" value="" placeholder="<?=$l['SAN'];?>" class="form-control">
								</div>

								<div class="col-xs-4" style="padding-left: 0;">
									<select name="login2[]" class="form-control">
										<option value="1"><?=$l['SAL1'];?></option>
										<option value="0"><?=$l['SAL0'];?></option>
									</select>
								</div>
							</div>

							<script>
							$("#add_sa").click(function(e){
								e.preventDefault();
								var c = $("#sa_template").clone().show();
								$("#sa_rows").append(c);
							});
							</script>

							<input type="hidden" name="action" value="save_sa">
							<input type="submit" class="btn btn-primary btn-block" value="<?=$l['SAS'];?>" style="margin-top: 10px;">
						</form>
        	</div>
        </div>
    </div>

    </div><?php }?>