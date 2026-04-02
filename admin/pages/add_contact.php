<?php
$l = $lang['ADD_CONTACT'];
$cl = $lang['CONTACT'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(10) || empty($_GET['user']) || !($uI = User::getInstance($_GET['user'], "ID"))) {require __DIR__ . "/error.php";if (!$ari->check(10)) {
    alog("general", "insufficient_page_rights", "add_contact");
}
} else {
    ?>
<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE'];?> <small><?=$uI->getfName();?></small></h1>

		<?php
$choosed = isset($_POST['mail_templates']) && is_array($_POST['mail_templates']) ? $_POST['mail_templates'] : [];
    foreach ($choosed as &$v) {
        $v = intval($v);
    }
    unset($v);

    $countries = array();
    $sql = $db->query("SELECT ID, name FROM client_countries ORDER BY ID = {$uI->get()['country']} DESC, name ASC, ID ASC");
    while ($row = $sql->fetch_object()) {
        $countries[$row->ID] = $row->name;
    }

    $currencies = array();
    $sql = $db->query("SELECT currency_code, name FROM currencies ORDER BY currency_code = '{$uI->get()['currency']}' DESC, currency_code ASC, name ASC");
    while ($row = $sql->fetch_object()) {
        $currencies[$row->currency_code] = $row->name;
    }

    if (isset($_POST['firstname'])) {
        try {
            foreach ($_POST as $k => $v) {
                if (!is_array($v)) {
                    $n = "p_" . strtolower($k);
                    $$n = $db->real_escape_string($v);
                }
            }

            if (empty($p_firstname)) {
                throw new Exception($lang['QUOTE']['ERR3']);
            }

            if (empty($p_lastname)) {
                throw new Exception($lang['QUOTE']['ERR4']);
            }

            if (!array_key_exists($p_country, $countries)) {
                throw new Exception($lang['QUOTE']['ERR10']);
            }

            if (!empty($p_mail) && !filter_var($p_mail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception($lang['QUOTE']['ERR9']);
            }

            if (!array_key_exists($p_language, $languages)) {
                throw new Exception($lang['QUOTE']['ERR11']);
            }

            if (!array_key_exists($p_currency, $currencies)) {
                throw new Exception($l['ERR1']);
            }

            $rights = implode(",", array_values(isset($_POST['rights']) && is_array($_POST['rights']) ? $_POST['rights'] : []));

            $data = array(
                "client" => $uI->get()['ID'],
                "mail" => $p_mail,
                "firstname" => $p_firstname,
                "lastname" => $p_lastname,
                "company" => $p_company,
                "street" => $p_street,
                "street_number" => $p_street_number,
                "postcode" => $p_postcode,
                "city" => $p_city,
                "country" => $p_country,
                "telephone" => $p_telephone,
                "language" => $p_language,
                "currency" => $p_currency,
                "mail_templates" => implode(",", $choosed),
                "type" => $p_type,
                "rights" => $rights,
            );
            if (!($contact = Contact::create($data))) {
                throw new Exception($l['ERR2']);
            }

            echo '<div class="alert alert-success">' . $l['SUC'] . '</div>';

            alog("general", "contact_created", $contact->get("ID"));

            unset($_POST);
        } catch (Exception $ex) {
            echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
        }
    }

    ?>

		<form method="POST">

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$lang['QUOTE']['FN'];?></label>
						<input type="text" name="firstname" placeholder="<?=$l['FNP'];?>" value="<?=isset($_POST['firstname']) ? $_POST['firstname'] : "";?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$lang['QUOTE']['LN'];?></label>
						<input type="text" name="lastname" placeholder="<?=$l['LNP'];?>" value="<?=isset($_POST['lastname']) ? $_POST['lastname'] : "";?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$lang['QUOTE']['CP'];?></label>
						<input type="text" name="company" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['company']) ? $_POST['company'] : "";?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label>Position</label>
						<input type="text" name="type" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['type']) ? $_POST['type'] : "";?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-10">
					<div class="form-group">
						<label><?=$lang['QUOTE']['ST'];?></label>
						<input type="text" name="street" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['street']) ? $_POST['street'] : "";?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-2">
					<div class="form-group">
						<label><?=$lang['QUOTE']['SN'];?></label>
						<input type="text" name="street_number" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['street_number']) ? $_POST['street_number'] : "";?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="form-group">
						<label><?=$lang['QUOTE']['PC'];?></label>
						<input type="text" name="postcode" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['postcode']) ? $_POST['postcode'] : "";?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-7">
					<div class="form-group">
						<label><?=$lang['QUOTE']['CT'];?></label>
						<input type="text" name="city" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['city']) ? $_POST['city'] : "";?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-3">
					<div class="form-group">
						<label><?=$l['COUNTRY'];?></label>
						<select name="country" class="form-control">
						<?php foreach ($countries as $key => $name) {?>
						<option value="<?=$key;?>"<?php if (isset($_POST['country']) && $_POST['country'] == $key) {
        echo ' selected="selected"';
    }
        ?>><?=$name;?></option>
						<?php }?>
						</select>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$lang['QUOTE']['EM'];?></label>
						<input type="text" name="mail" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['mail']) ? $_POST['mail'] : "";?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$l['TEL'];?></label>
						<input type="text" name="telephone" placeholder="<?=$l['OPT'];?>" value="<?=isset($_POST['telephone']) ? $_POST['telephone'] : "";?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$l['LANG'];?></label>
						<select name="language" class="form-control">
						<?php foreach ($languages as $key => $name) {?>
						<option value="<?=$key;?>"<?php if ((isset($_POST['language']) && $_POST['language'] == $key) || (!isset($_POST['language']) && $_POST['language'] == $uI->get()['language'])) {
        echo ' selected="selected"';
    }
        ?>><?=$name;?></option>
						<?php }?>
						</select>
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$l['CURRENCY'];?></label>
						<select name="currency" class="form-control">
						<?php foreach ($currencies as $key => $name) {?>
						<option value="<?=$key;?>"<?php if (isset($_POST['currency']) && $_POST['currency'] == $key) {
        echo ' selected="selected"';
    }
        ?>><?=$name;?></option>
						<?php }?>
						</select>
					</div>
				</div>
			</div>

			<div class="panel panel-default">
				<div class="panel-heading"><?=$l['EMTPL'];?></div>
				<div class="panel-body" style="margin-top: -10px;">
					<div class="row">
						<?php
$ignored = explode(",", $u->exclude_mail_templates);

    $ignore = [
        "Bereits registriert",
        "E-Mailadresse geändert",
        "Benutzerdaten geändert",
        "E-Mailänderung (neue Adresse)",
        "E-Mailänderung (alte Adresse)",
        "E-Mailänderung storniert",
        "Gast-Bestellung",
        "Neues Passwort",
        "Neuregistrierung",
        "Passwort angefordert",
        "Passwort vergessen?",
        "Passwort zurückgesetzt",
        "Registrierung per sozialem Login",
        "Zwei-Faktor aktiviert",
        "Zwei-Faktor deaktiviert",
    ];

    $sql = $db->query("SELECT category FROM email_templates WHERE admin_notification = 0 AND category != 'System' AND category != 'Administrator' AND category != 'Reseller' GROUP BY category ORDER BY category = 'Eigene' ASC, category ASC");
    while ($row = $sql->fetch_object()) {?>
						<div class="col-md-6">
							<div class="checkbox">
								<label>
									<input type="checkbox" class="mt_checkall" data-category="<?=htmlentities($row->category);?>" />
									<b><?=htmlentities($row->category);?></b>
								</label>
							</div>

							<?php
$sql2 = $db->query("SELECT ID, name FROM email_templates WHERE category = '" . $db->real_escape_string($row->category) . "' ORDER BY name ASC");
        $all = true;
        while ($e = $sql2->fetch_object()) {if (in_array($e->name, $ignore)) {
            continue;
        }
            if (!in_array($e->ID, $choosed)) {
                $all = false;
            }
            ?>
							<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
								<label>
									<input type="checkbox" class="mt_checkbox" name="mail_templates[]" value="<?=$e->ID;?>" data-category="<?=htmlentities($row->category);?>"<?=in_array($e->ID, $choosed) ? ' checked=""' : '';?> />
									<?=htmlentities($e->name);?>
								</label>
							</div>
							<?php }if ($all) {?><script>$(".mt_checkall[data-category=<?=htmlentities($row->category);?>]").prop("checked", true);</script><?php }?>
						</div>
						<?php }?>
					</div>
				</div>
			</div>
			<input type="hidden" name="mail_templates[]" value="0">

			<script>
			$(".mt_checkall").click(function(e) {
				$(".mt_checkbox[data-category=" + $(this).data("category") + "]").prop("checked", e.target.checked);
			});

			$(".mt_checkbox").click(function(e) {
				var cat = $(this).data("category");
				var chk = true;

				$(".mt_checkbox[data-category=" + cat + "]").each(function() {
					if (!$(this).is(":checked")) {
						chk = false;
					}
				});

				$(".mt_checkall[data-category=" + cat + "]").prop("checked", chk);
			});
			</script>

<div class="panel panel-default">
				<div class="panel-heading"><?=$cl['RIGHTS'];?></div>
					<?php
$rights = [];

    if (isset($_POST['rights']) && is_array($_POST['rights'])) {
        $rights = $_POST['rights'];
    }
    ?>
					<div class="panel-body">
						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="tickets"<?=in_array("tickets", $rights) ? ' checked=""' : '';?> />
								<?=$cl['RIGHT1'];?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="invoices"<?=in_array("invoices", $rights) ? ' checked=""' : '';?> />
								<?=$cl['RIGHT2'];?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="quotes"<?=in_array("quotes", $rights) ? ' checked=""' : '';?> />
								<?=$cl['RIGHT3'];?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="products"<?=in_array("products", $rights) ? ' checked=""' : '';?> />
								<?=$cl['RIGHT4'];?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="domains"<?=in_array("domains", $rights) ? ' checked=""' : '';?> />
								<?=$cl['RIGHT5'];?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="emails"<?=in_array("emails", $rights) ? ' checked=""' : '';?> />
								<?=$cl['RIGHT6'];?>
							</label>
						</div>
					</div>
				</div>

			<input type="submit" class="btn btn-primary btn-block" value="<?=$l['ADD'];?>" />
		</form>
	</div>
</div>
<?php }?>