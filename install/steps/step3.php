<?php
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$base = "install_step_3";

if (isset($_POST['step3'])) {
	// If settings are given, check them and set it to session
	class InstallEx extends Exception {}

	try {
		// Check if security hash mets requirements
		if (!isset($_POST['hash']) || strlen(trim($_POST['hash'])) < 16) {
			throw new InstallEx(__("error_hash"));
		}

		if (is_numeric($_POST['hash']) && strlen(trim($_POST['hash'])) < 32) {
			throw new InstallEx(__("error_hash2"));
		}

		$_SESSION['config']['gen'] = trim($_POST['hash']);

		$_SESSION['config']['hash_method'] = $_POST['hash_method'];
		$_SESSION['config']['clientside_hashing'] = isset($_POST['clientside_hashing']) && $_POST['clientside_hashing'] == "1" ? 1 : 0;

		$sec = new Security;
		$CFG['HASH_METHOD_ADMIN'] = $_SESSION['config']['hash_method'];
		$CFG['GLOBAL_SALT'] = $_SESSION['config']['global_salt'] = $sec->generateSalt();
		$_SESSION['admin']['password'] = $sec->adminHash($_SESSION['admin']['password'], $_SESSION['admin']['salt']);

		// Continue to the next step
		$_SESSION['step'] = 4;
	} catch (InstallEx $ex) {
		// If any error occured, show it to the user
		?><div class="alert alert-danger"><b><?=__("error"); ?></b> <?=$ex->getMessage();?></div><?php
}
}
?>

<p><?=__("intro"); ?></p>

<form role="form" method="POST">
  <div class="form-group">
	<label><?=__("hash"); ?></label>
	<input type="text" class="form-control" id="hash" name="hash" value="<?=isset($_POST['hash']) ? $_POST['hash'] : "";?>" placeholder="">
	<p class="help-block"><?=__("hashex"); ?><br /><?=__("generate"); ?>: <a href="#" onclick="genEas(); return false;"><?=__("easy"); ?></a> | <a href="#" onclick="genMid(); return false;"><?=__("mid"); ?></a> | <a href="#" onclick="genCom(); return false;"><?=__("com"); ?></a> | <a href="#" onclick="genNum(); return false;"><?=__("num"); ?></a></p>
  </div>
  <div class="form-group">
		<label><?=__("chash"); ?></label>
		<select name="hash_method" class="form-control">
		<option value="plain" <?php if (isset($_POST['hash_method']) && $_POST['hash_method'] == "plain") {
	echo "selected=\"selected\"";
}
?>><?=__("plain"); ?></option>
		<option value="md5" <?php if (isset($_POST['hash_method']) && $_POST['hash_method'] == "md5") {
	echo "selected=\"selected\"";
}
?>>MD5</option>
		<option value="sha1" <?php if (isset($_POST['hash_method']) && $_POST['hash_method'] == "sha1") {
	echo "selected=\"selected\"";
}
?>>SHA1</option>
		<option value="sha256" <?php if (isset($_POST['hash_method']) && $_POST['hash_method'] == "sha256") {
	echo "selected=\"selected\"";
}
?>>SHA256</option>
		<option value="sha512" <?php if (!isset($_POST['hash_method']) || $_POST['hash_method'] == "sha512") {
	echo "selected=\"selected\"";
}
?>>SHA512</option>
		</select>
		<p class="help-block"><?=__("chashex"); ?></p>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="clientside_hashing" value="1" <?php if (!isset($_POST['step3']) || isset($_POST['clientside_hashing'])) {
	echo "checked";
}
?>> <?=__("clienthash"); ?>
            </label>
        </div>
	</div>
  <center><button type="submit" name="step3" class="btn btn-primary btn-block"><?=__("do"); ?> &raquo;</button><br /><button type="submit" name="start" class="btn btn-xs btn-warning" onclick="return confirm('<?=__("rso"); ?>');">&laquo; <?=__("so"); ?></button></center>
</form>

<script type="text/javascript">
function genEas() {
    var text = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";

    for (var i = 0; i < 16; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    document.getElementById("hash").value = text;
}

function genMid() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for (var i = 0; i < 32; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    document.getElementById("hash").value = text;
}

function genCom() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!=+#*-_.,<>§$%&/()=?";

    for (var i = 0; i < 64; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    document.getElementById("hash").value = text;
}

function genNum() {
    var text = "";
    var possible = "0123456789";

    for (var i = 0; i < 32; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    document.getElementById("hash").value = text;
}
</script>