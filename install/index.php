<?php
error_reporting(E_ERROR);

define("SOURCEDESK", true);

// Start session for saving information
session_start();

function sourceDESK_bug($data)
{}

// Language system
$base = "install";
if (!function_exists('__')) {function __($a)
    {global $lang, $base;return $lang[strtoupper($base)][strtoupper($a)];}}

$languages = array();
$handle = opendir(__DIR__ . "/../languages/");
while ($datei = readdir($handle)) {
    $ex = explode(".", $datei);
    if ($ex[count($ex) - 1] != "php" || substr($datei, 0, 1) == "." || substr($datei, 0, 6) == "admin.") {
        continue;
    }

    require __DIR__ . "/../languages/" . $datei;
    $languages[$ex[0]] = $lang['NAME'];
}

// Change language
if (isset($_GET['language']) && isset($languages[$_GET['language']])) {
    require __DIR__ . "/../languages/" . basename($_GET['language']) . ".php";
    $_SESSION['language'] = basename($_GET['language']);
} else if (isset($_SESSION['language']) && isset($languages[$_SESSION['language']])) {
    require __DIR__ . "/../languages/" . basename($_SESSION['language']) . ".php";
} else {
    $browserLang = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($browserLang as $lang) {
        if (substr($lang, 0, 2) == "de") {
            @include __DIR__ . "/../languages/deutsch.php";
            $_SESSION['language'] = "deutsch";
            break;
        }
    }

    if (!is_array($lang)) {
        if (!include (__DIR__ . "/../languages/english.php")) {
            die("English language file missing!");
        }
    }
}

// Define autoload for classes
function class_loader($class_name)
{
    $path = "";
    $ex = explode('\\', $class_name);
    foreach ($ex as $part) {
        $path .= "/" . $part;
    }

    if (file_exists(__DIR__ . '/../lib/' . $path . ".php")) {
        include_once __DIR__ . '/../lib/' . $path . ".php";
    }

}

spl_autoload_register('class_loader');

// Check system requirements
SystemRequirements::check();

// DB init
if (!empty($_GET['dbinit']) && file_exists(__DIR__ . "/../config.php")) {
    require_once __DIR__ . "/../lib/Database.php";
    $db = new DB($_SESSION['config']['host'], $_SESSION['config']['user'], $_SESSION['config']['pw'], $_SESSION['config']['db']);
    if ($db->connect_errno) {
        die($db->connect_error);
    }
    $db->set_charset("UTF8");

    $ds = new DatabaseStructure();
    $ds->init();
    $ds->deploy($db);

    $_SESSION['step'] = 1;
    unset($_SESSION['config']);
    unset($_SESSION['admin']);
    $finished = true;
    die("ok");
}

if (!file_exists(__DIR__ . "/../config.php")) {
    // If the user wants to begin again with installation, reset all data and set step to first
    if (isset($_REQUEST['start'])) {
        $_SESSION['step'] = 1;
        unset($_SESSION['config']);
        unset($_SESSION['admin']);
    }

    // Check if mod_rewrite is enabled
    if (array_key_exists('HTTP_MOD_REWRITE', $_SERVER) || !function_exists("apache_get_modules") || in_array('mod_rewrite', apache_get_modules())) {

        // Require the current step (if nothing is set, use the first)
        // Use output buffering for displaying the right step in template below
        $step = isset($_SESSION['step']) ? $_SESSION['step'] : 1;
        ob_start();
        require __DIR__ . "/steps/step" . $step . ".php";
        $con = ob_get_contents();
        ob_end_clean();
        $base = "install";

        // If the step was changed within the required step file, require the new step
        if (isset($_SESSION['step']) && $_SESSION['step'] != $step) {
            ob_start();
            require __DIR__ . "/steps/step" . $_SESSION['step'] . ".php";
            $con = ob_get_contents();
            ob_end_clean();
            $base = "install";
            $step = $_SESSION['step'];
        }

    } else {

        // If mod_rewrite is not available, show an error page
        // Output buffering is used to get content into @var con
        $step = "_mod_rewrite";
        ob_start();
        require __DIR__ . "/steps/step" . $step . ".php";
        $con = ob_get_contents();
        ob_end_clean();
        $base = "install";

    }

} else {
    // If the base system is already installed, give an error page
    // Output buffering is used to get content into @var con
    $step = "_installed";
    ob_start();
    require __DIR__ . "/steps/step" . $step . ".php";
    $con = ob_get_contents();
    ob_end_clean();
    $base = "install";
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=__("browser_title");?></title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="../admin/res/fonts/awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
	<div class="container">

    <li class="dropdown" style="list-style: none; margin-top: 25px; margin-bottom: 0;">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fa fa-language"></i></a> &nbsp;
        <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?=__("change_language");?></a>
        <ul class="dropdown-menu">
            <?php foreach ($languages as $k => $v) {
    $_GET['language'] = $k;
    ?>
            <li<?php if ($v == $lang['NAME']) {
        echo " class='active'";
    }
    ?>><a href="?<?=http_build_query($_GET);?>"><?=$v;?></a></li>
            <?php }?>
        </ul>
    </li>

	<div class="page-header" style="margin-top: 0;">
	  <h1><?=__("title");?> <small><?=__("subtitle");?></small></h1>
	</div><?php if (!isset($finished)) {?>
    <?php
$warnings = SystemRequirements::getWarningList();
    if ($step != "_installed" && $warnings) {
        echo '<div class="alert alert-warning">Your server does not meet our recommended settings:' . $warnings . '</div>';
    }
    ?>

    <div class="row form-group">
        <div class="col-xs-12">
            <ul class="nav nav-pills nav-justified thumbnail setup-panel" style="margin-bottom: 5px;">
                <li class="<?=$step == 1 ? "active" : "disabled";?>"><a href="#step-1" onclick="return false;">
                    <h4 class="list-group-item-heading"><?=__("step1");?></h4>
                    <p class="list-group-item-text"><?=__("db");?></p>
                </a></li>
                <li class="<?=$step == 2 ? "active" : "disabled";?>"><a href="#step-2" onclick="return false;">
                    <h4 class="list-group-item-heading"><?=__("step2");?></h4>
                    <p class="list-group-item-text"><?=__("admin");?></p>
                </a></li>
				<li class="<?=$step == 3 ? "active" : "disabled";?>"><a href="#step-3" onclick="return false;">
                    <h4 class="list-group-item-heading"><?=__("step3");?></h4>
                    <p class="list-group-item-text"><?=__("security");?></p>
                </a></li>
            </ul>
        </div>
	</div><?php }?>

	<!-- CONTENT START -->
	<?=$con;?>
	<!-- CONTENT END -->

	<hr>
	<footer>
		<center><p>&copy; Copyright <a href="https://sourceway.de/" target="_blank">sourceWAY.de</a> <?=date("Y");?></p></center>
	</footer>
	</div>
  </body>
</html>