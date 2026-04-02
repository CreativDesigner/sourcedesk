<?php
$l = $lang['ADD_CUSTOMER'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(10)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "add_customer");} else {?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>
		<?php
$activeFields = array();
    $sql = $db->query("SELECT name FROM client_fields WHERE active = 1");
    while ($row = $sql->fetch_object()) {
        array_push($activeFields, $row->name);
    }

    if (isset($_POST['submit'])) {

        class CustomerException extends Exception
        {}

        try {

            if (!isset($_POST['firstname'])) {
                throw new CustomerException($lang['QUOTE']['ERR3']);
            }

            $firstname = $_POST['firstname'];

            if (!isset($_POST['lastname'])) {
                throw new CustomerException($lang['QUOTE']['ERR4']);
            }

            $lastname = $_POST['lastname'];

            $email = $_POST['email'];
            if (!$val->email($email)) {
                throw new CustomerException($lang['QUOTE']['ERR9']);
            }

            $cpassword = $_POST['cpassword'];
            if (strlen($cpassword) < 8) {
                throw new CustomerException($lang['ADD_ADMIN']['ERR4']);
            }

            $_POST['salt'] = $sec->generateSalt();
            $cpassword = $sec->hash($cpassword, $salt);

            $company = trim($_POST['company']);

            $escapeVar = array("firstname", "lastname", "email", "company", "salt", "salutation", "telephone", "street", "street_number", "postcode", "city", "country", "language", "currency");
            foreach ($escapeVar as $k) {
                $$k = $db->real_escape_string($_POST[$k]);
            }

            $cpassword = $db->real_escape_string($cpassword);
            $limit = doubleval($CFG['POSTPAID_DEF']);

            $cgroup = 0;
            if ($CFG['DEFAULT_CGROUP'] && $db->query("SELECT 1 FROM client_groups WHERE ID = " . intval($CFG['DEFAULT_CGROUP']))->num_rows) {
                $cgroup = intval($CFG['DEFAULT_CGROUP']);
            }

            if (!$db->query("INSERT INTO clients (`cgroup`, `firstname`, `lastname`, `mail`, `pwd`, `company`, `registered`, `salt`, `salutation`, `telephone`, `street`, `street_number`, `postcode`, `city`, `country`, `language`, `currency`, postpaid) VALUES ($cgroup, '$firstname', '$lastname', '$email', '$cpassword', '$company', " . time() . ", '$salt', '$salutation', '$telephone', '$street', '$street_number', '$postcode', '$city', '$country', '$language', '$currency', $limit)")) {
                throw new CustomerException($l['FAIL'] . "<br />" . htmlentities($db->error));
            }

            $cid = $db->insert_id;

            $addons->runHook("CustomerCreated", [
                "user" => User::getInstance($cid, "ID"),
            ]);

            unset($_POST);

            alog("general", "customer_created", "$firstname $lastname", $cid);

            ?>
		<div class="alert alert-success">
		<?=$l['SUC'];?> <a href="?p=customers&edit=<?=$cid;?>"><?=$l['PROFILE'];?></a> | <a href="?p=customers"><?=$l['OVERVIEW'];?></a>
		</div>
		<?php

        } catch (CustomerException $ex) {

            ?>
		<div class="alert alert-danger">
		<b><?=$lang['GENERAL']['ERROR'];?></b> <?=$ex->getMessage();?>
		</div>
		<?php

        }

    }

    if (!isset($_POST['submit']) || isset($ex)) {?>
			<form role="form" method="POST">
<div class="row">
<div class="col-sm-2"><div class="form-group">
    <select class="form-control" name="salutation">
        <?php $salutation = $_POST['salutation'] ?? "MALE";?>
        <option value="MALE"<?=$salutation == "MALE" ? ' selected=""' : '';?>><?=$lang['CUSTOMERS']['MALE'];?></option>
        <option value="FEMALE"<?=$salutation == "FEMALE" ? ' selected=""' : '';?>><?=$lang['CUSTOMERS']['FEMALE'];?></option>
        <option value="DIVERS"<?=$salutation == "DIVERS" ? ' selected=""' : '';?>><?=$lang['CUSTOMERS']['DIVERS'];?></option>
        <option value=""<?=$salutation == "" ? ' selected=""' : '';?>><?=$lang['CUSTOMERS']['NA'];?></option>
    </select>
</div></div>

<div class="col-sm-5">
<div class="form-group">
		<input class="form-control" placeholder="<?=$lang['QUOTE']['FN'];?>" name="firstname" type="text" value="<?php if (isset($_POST['firstname'])) {
        echo htmlentities($_POST['firstname']);
    }
        ?>">
	</div>
</div>

<div class="col-sm-5">
	<div class="form-group">
		<input class="form-control" placeholder="<?=$lang['QUOTE']['LN'];?>" name="lastname" type="text" value="<?php if (isset($_POST['lastname'])) {
            echo htmlentities($_POST['lastname']);
        }
        ?>">
	</div>
</div>
</div>

<div class="form-group input-group">
	<span class="input-group-addon">
		<i class="fa fa-suitcase">
		</i>
	</span>
	<input class="form-control" placeholder="<?=$lang['QUOTE']['CP'];?>" name="company" type="text" value="<?=isset($_POST['company']) ? htmlentities($_POST['company']) : "";?>">
</div>

<div class="form-group input-group">
	<span class="input-group-addon">
		<i class="glyphicon glyphicon-inbox" style="width: 14px;"></i>
	</span>
	<input class="form-control" placeholder="<?=$l['EMAIL'];?>" name="email" type="text" value="<?=isset($_POST['email']) ? htmlentities($_POST['email']) : "";?>">
</div>

<?php if (in_array("Telefonnummer", $activeFields)) {?><div class="form-group input-group">
<span class="input-group-addon">
	<i class="fa fa-phone" style="width:14px;"></i>
</span>
<input class="form-control" placeholder="<?=$lang['ADD_CONTACT']['TEL'];?>" name="telephone" type="text" value="<?=isset($_POST['telephone']) ? htmlentities($_POST['telephone']) : "";?>">
</div>
<?php }?>

<div class="form-group input-group">
	<span class="input-group-addon">
		<i class="glyphicon glyphicon-lock">
		</i>
	</span>
	<input class="form-control" placeholder="<?=$l['PASSWORD'];?>" name="cpassword" type="text" value="<?php if (isset($_POST['cpassword'])) {
            echo htmlentities($_POST['cpassword']);
        } else {
            echo Security::generatePassword(12, false, "lud");
        }
        ?>" autocomplete="false">
</div>

<?php if (in_array("Straße", $activeFields) || in_array("Hausnummer", $activeFields)) {?><div class="row">
<div class="col-sm-<?=in_array("Hausnummer", $activeFields) ? "10" : "12";?>">
<div class="form-group">
		<input class="form-control" placeholder="<?=$lang['QUOTE']['ST'];?>" name="street" type="text" value="<?=isset($_POST['street']) ? htmlentities($_POST['street']) : "";?>">
	</div>
</div>

<div class="col-sm-<?=in_array("Straße", $activeFields) ? "2" : "12";?>">
	<div class="form-group">
		<input class="form-control" placeholder="<?=$lang['QUOTE']['SN'];?>" maxlength="25" name="street_number" type="text" value="<?=isset($_POST['street_number']) ? htmlentities($_POST['street_number']) : "";?>">
	</div>
</div>
</div><?php }?>

<?php if (in_array("Postleitzahl", $activeFields) || in_array("Ort", $activeFields)) {?>
<div class="row">
<div class="col-sm-<?=in_array("Ort", $activeFields) ? "2" : "12";?>">
<div class="form-group">
		<input class="form-control" placeholder="<?=$lang['QUOTE']['PC'];?>" name="postcode" maxlength="10" type="text" value="<?=isset($_POST['postcode']) ? htmlentities($_POST['postcode']) : "";?>">
	</div>
</div>

<div class="col-sm-<?=in_array("Postleitzahl", $activeFields) ? "10" : "12";?>">
	<div class="form-group">
		<input class="form-control" placeholder="<?=$lang['QUOTE']['CT'];?>" name="city" type="text" value="<?=isset($_POST['city']) ? htmlentities($_POST['city']) : "";?>">
	</div>
</div>
</div><?php }?>

<?php
$count = 2;
        if (in_array("Land", $activeFields)) {
            $count++;
        }

        $md = 12 / $count;
        ?>
<div class="row">
<?php if (in_array("Land", $activeFields)) {?>
<div class="col-md-<?=$md;?>">
<div class="form-group">
	<select class="form-control" name="country">
<option value="0"><?=$l['CC'];?></option>
<?php $sql = $db->query("SELECT ID, name FROM client_countries WHERE active = 1 ORDER BY name ASC");
            while ($r = $sql->fetch_object()) {?>
<option value="<?=$r->ID;?>" <?php if ((!empty($_POST['country']) && $r->ID == $_POST['country']) || (!isset($_POST['country']) && $r->ID == $CFG['DEFAULT_COUNTRY'])) {
                echo "selected";
            }
                ?>><?=$r->name;?></option>
<?php }?>
</select>
</div>
</div>
<?php }?>

<div class="col-md-<?=$md;?>">
<div class="form-group">
	<select class="form-control" name="language">
<option value="0"><?=$l['CL'];?></option>
<?php foreach ($languages as $k => $v) {?>
<option value="<?=$k;?>" <?php if ((!empty($_POST['language']) && $k == $_POST['language']) || (!isset($_POST['language']) && $k == $CFG['LANG'])) {
            echo "selected";
        }
            ?>><?=$v;?></option>
<?php }?>
</select>
</div>
</div>

<div class="col-md-<?=$md;?>">
<div class="form-group">
	<select class="form-control" name="currency">
<option value="0"><?=$l['CCU'];?></option>
<?php $sql = $db->query("SELECT * FROM currencies ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
<option value="<?=$row->currency_code;?>" <?php if ((isset($_POST['currency']) && $row->currency_code == $_POST['currency']) || (empty($_POST['currency']) && $cur->getBaseCurrency() == $row->currency_code)) {
            echo "selected";
        }
            ?>><?=$row->name;?></option>
<?php }?>
</select>
</div>
</div>
</div>

  <button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['ADD'];?></button></form><?php }?>
		</div>
		<!-- /.col-lg-12 -->
	</div>           <?php }?>