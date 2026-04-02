<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$base = "install_step_1";

if (isset($_POST['step1'])) {
    // If database settings are given, try to connect
    require_once __DIR__ . "/../../lib/Database.php";
    $dbTry = @new DB($_POST['host'], $_POST['user'], $_POST['password']);
    if ($dbTry->connect_errno || empty($_POST['database']) || $_POST['database'] == "information_schema" || $_POST['database'] == "performance_schema") {
        // If connection failed, give an error message
        ?>
		<div class="alert alert-danger"><b><?=__("error");?></b> <?=__("info");?><br /><br /><i><?=$dbTry->connect_error ?: "No database specified";?></i></div>
		<?php
} else {
        $dbTry->set_charset("UTF8");
        @$dbTry->query("CREATE DATABASE `" . $dbTry->real_escape_string($_POST['database']) . "`");
        if (!@$dbTry->select_db($_POST['database'])) {
            // If database selection failed, give an error message
            ?>
			<div class="alert alert-danger"><b><?=__("error");?></b> <?=__("info");?><br /><br /><i><?=$dbTry->error ?: "Invalid database specified";?></i></div>
			<?php
} else {
            // If connection was successful, continue to the next step and set database credentials to session
            $_SESSION['step'] = 2;

            $_SESSION['config']['host'] = $_POST['host'];
            $_SESSION['config']['user'] = $_POST['user'];
            $_SESSION['config']['pw'] = $_POST['password'];
            $_SESSION['config']['db'] = $_POST['database'];
            $_SESSION['license_key'] = $_POST['license_key'];
        }
    }
}
?>

<p style="text-align: justify;"><?=__("intro");?></p>

<form role="form" method="POST">
  <div class="form-group">
	<label style="margin-bottom: 0;"><?=__("db_type");?></label><br />
	<?=__("mysql");?>
  </div>
	<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">
  <div class="form-group">
	<label><?=__("db_host");?></label>
	<input type="text" class="form-control" name="host" value="<?=isset($_POST['host']) ? $_POST['host'] : "localhost";?>" placeholder="<?=__("db_host_ph");?>">
  </div>
  <div class="form-group">
	<label><?=__("db_user");?></label>
	<input type="text" class="form-control" name="user" value="<?=isset($_POST['user']) ? $_POST['user'] : "";?>">
  </div>
  <div class="form-group">
	<label><?=__("db_password");?></label>
	<input type="password" class="form-control" name="password" value="<?=isset($_POST['password']) ? $_POST['password'] : "";?>">
  </div>
  <div class="form-group">
	<label><?=__("db_name");?></label>
	<input type="text" class="form-control" name="database" value="<?=isset($_POST['database']) ? $_POST['database'] : "";?>" placeholder="<?=__("db_name_ph");?>">
  </div>
  <div class="form-group">
	<label><?=__("license_key");?></label>
	<input type="text" class="form-control" name="license_key" value="<?=isset($_POST['license_key']) ? $_POST['license_key'] : "";?>" placeholder="<?=__("license_key_ph");?>">
	<p class="help-block" style="text-align: justify;"><?=__("license_key_hint");?></p>
  </div>
  <center><button type="submit" name="step1" class="btn btn-primary btn-block"><?=__("continue");?></button></center>
</form>