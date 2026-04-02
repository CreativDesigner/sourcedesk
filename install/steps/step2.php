<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$base = "install_step_2";

$sec = new Security;
$CFG['HASH'] = $sec->generatePassword(32); // For encryption

if (isset($_POST['step2'])) {
    // If administrator data is given, check it and save it to session if all is okay
    class InstallEx extends Exception
    {}

    try {
        $_SESSION['admin'] = [];

        // Check if any name is set for admin
        if (!isset($_POST['name']) || trim($_POST['name']) == "") {
            throw new InstallEx(__("error_name"));
        }

        $_SESSION['admin']['name'] = trim($_POST['name']);

        // Check if valid email is set for admin
        if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InstallEx(__("error_email"));
        }

        $_SESSION['admin']['email'] = trim($_POST['email']);

        // Check if the username is set
        if (!isset($_POST['username']) || trim($_POST['username']) == "") {
            throw new InstallEx(__("error_user"));
        }

        $_SESSION['admin']['username'] = trim($_POST['username']);

        // Generate global salt and set hashing method
        $_SESSION['config']['global_salt'] = $CFG['GLOBAL_SALT'] = encrypt($sec->generateSalt());

        // Generate admin salt
        $_SESSION['admin']['salt'] = $sec->generateSalt();

        // Check if password is at least 8 characters long
        if (!isset($_POST['password']) || strlen($_POST['password']) < 8) {
            throw new InstallEx(__("error_pw"));
        }

        $_SESSION['admin']['password'] = $_POST['password'];

        // Continue to next step
        $_SESSION['step'] = 3;
    } catch (InstallEx $ex) {
        // If an error occured, show it to user
        ?><div class="alert alert-danger"><b><?=__("error");?></b> <?=$ex->getMessage();?></div><?php
}
}
?>

<p style="text-align: justify;"><?=__("intro");?></p>

<form role="form" method="POST">
  <div class="form-group">
	<label><?=__("name");?></label>
	<input type="text" class="form-control" name="name" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>" placeholder="<?=__("name_ph");?>">
  </div>
  <div class="form-group">
	<label><?=__("email");?></label>
	<input type="text" class="form-control" name="email" value="<?=isset($_POST['email']) ? $_POST['email'] : "";?>" placeholder="<?=__("email_ph");?>">
  </div>
  <div class="form-group">
	<label><?=__("user");?></label>
	<input type="text" class="form-control" name="username" value="<?=isset($_POST['username']) ? $_POST['username'] : "";?>" placeholder="<?=__("user_ph");?>">
  </div>
  <div class="form-group">
	<label><?=__("password");?></label>
	<input type="password" class="form-control" name="password" value="">
  </div>
  <center><button type="submit" name="step2" class="btn btn-primary btn-block"><?=__("next");?></button> <br /> <button type="submit" name="start" class="btn btn-xs btn-warning" onclick="return confirm('<?=__("reset_pt");?>');"><?=__("reset");?></button></center>
</form>