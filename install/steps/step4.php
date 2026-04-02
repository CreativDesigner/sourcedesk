<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$base = "install_step_4";

try {
    // Establish MySQL connection
    require_once __DIR__ . "/../../lib/Database.php";
    $db = new DB($_SESSION['config']['host'], $_SESSION['config']['user'], $_SESSION['config']['pw'], $_SESSION['config']['db']);
    if ($db->connect_errno) {
        throw new Exception(__("mysql") . "<br /><i>{$db->connect_error}</i>");
    }
    $db->set_charset("UTF8");

    // Replace all settings within config
    $file = file_get_contents(__DIR__ . "/../req/config.dist.php");
    $file = str_replace("%host%", $_SESSION['config']['host'], $file);
    $file = str_replace("%user%", $_SESSION['config']['user'], $file);
    $file = str_replace("%pw%", $_SESSION['config']['pw'], $file);
    $file = str_replace("%db%", $_SESSION['config']['db'], $file);
    $file = str_replace("%gen%", $_SESSION['config']['gen'], $file);

    // Try to copy config to root dir
    if (!file_put_contents(__DIR__ . "/../../config.php", $file)) {
        throw new Exception(__("conf"));
    }

    // Check again if database connection can be etablished
    $db = new DB($_SESSION['config']['host'], $_SESSION['config']['user'], $_SESSION['config']['pw'], $_SESSION['config']['db']);
    $prefix = "";
    if ($db->connect_errno) {
        throw new Exception(__("db"));
    }
    $db->set_charset("UTF8");

    $path = $_SERVER['REQUEST_URI'];
    $ex = explode("/", $path);
    $ex = array_slice($ex, 0, -2);
    $path = implode("/", $ex);
    $path = trim($path, "/");
    if (!empty($path)) {
        $path .= "/";
    }

    function isSecure()
    {
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)
            || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
        ) {
            return true;
        } else {
            return false;
        }
    }

    $pageurl = (isSecure() ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/" . $path;

    $db->query(str_replace("%prefix%", $prefix, file_get_contents(__DIR__ . "/../req/required.dist.sql")));

    $rights = serialize(array_keys(AdminRights::get()));

    $username = $_SESSION['admin']['username'];
    $password = $_SESSION['admin']['password'];
    $salt = $_SESSION['admin']['salt'];
    $name = $_SESSION['admin']['name'];
    $email = $_SESSION['admin']['email'];
    $language = $lang['INTERNAL'];
    $widgets = 'a:2:{s:12:"system_stats";b:1;s:14:"sourceway_news";b:1;}';
    $db->query("INSERT INTO `admins` (username, password, salt, name, email, language, rights, widgets) VALUES ('" . $db->real_escape_string($username) . "', '" . $db->real_escape_string($password) . "', '" . $db->real_escape_string($salt) . "', '" . $db->real_escape_string($name) . "', '" . $db->real_escape_string($email) . "', '" . $db->real_escape_string($language) . "', '" . $db->real_escape_string($rights) . "', '$widgets')");
    ?>
	<style>
	tr > td:first-child{
		font-weight: bold;
	}
	</style>
	<div class="row">
		<div class="col-xs-8 col-xs-offset-2">
			<div id="finished" style="display: none;">
				<table class="table table-striped">
					<tbody>
						<tr>
							<td><?=__("shop");?></td>
							<td><a href="<?=$pageurl;?>" target="_blank"><?=$pageurl;?> <i class="fa fa-external-link"></i></a></td>
						</tr>
						<tr>
							<td><?=__("admin");?></td>
							<td><a href="<?=$pageurl;?>admin" target="_blank"><?=$pageurl;?>admin <i class="fa fa-external-link"></i></a></td>
						</tr>
						<tr>
							<td><?=__("user");?></td>
							<td><?=htmlentities($username);?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div id="doing" style="text-align: center; font-size: 20px;">
				<i class="fa fa-spinner fa-spin"></i> <?=__("dbinit");?>
			</div>
		</div>
	</div>

	<script>
	$(document).ready(function() {
		$(window).bind('beforeunload', function(){
			return '';
		});

		$.get("?dbinit=1", function(r) {
            if (r != "ok") {
                alert(r);
                return;
            }

			$("#doing").slideUp(function() {
				$("#finished").slideDown();
				$(window).unbind('beforeunload');
			});
		});
	});
	</script>
	<?php
} catch (Exception $ex) {
    if (isset($_GET['restart'])) {
        $_SESSION['step'] = 1;
        unset($_SESSION['config']);
        unset($_SESSION['admin']);

        header('Location: ./');
        exit;
    }

    ?><div class="alert alert-danger"><b><?=__("error");?></b> <?=$ex->getMessage();?></div><center><a href="./" class="btn btn-primary"><?=__("retry");?></a> <a href="./?restart=1" class="btn btn-default"><?=__("restart");?></a></center><?php
}

?>