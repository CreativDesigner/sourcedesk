<?php
$l = $lang['CONTACT'];
$al = $lang['ADD_CONTACT'];
$ql = $lang['QUOTE'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

if(!$ari->check(10) || empty($_GET['edit']) || !is_object($c = new Contact($_GET['edit'])) || $c->get("ID") != $_GET['edit'] || !($uI = User::getInstance($c->get('client'), "ID"))){ require __DIR__ . "/error.php"; if(!$ari->check(10)) alog("general", "insufficient_page_rights", "contact"); } else {
?>
<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$l['TITLE']; ?> <small><?=htmlentities($c->get("firstname") . " " . $c->get("lastname")); ?></small></h1>

		<?php
		$choosed = isset($_POST['mail_templates']) && is_array($_POST['mail_templates']) ? $_POST['mail_templates'] : explode(",", $c->get("mail_templates"));
		foreach ($choosed as &$v) {
			$v = intval($v);
		}
		unset($v);

		$countries = Array();
		$sql = $db->query("SELECT ID, name FROM client_countries ORDER BY ID = {$uI->get()['country']} DESC, name ASC, ID ASC");
		while($row = $sql->fetch_object()) $countries[$row->ID] = $row->name;

		$currencies = Array();
		$sql = $db->query("SELECT currency_code, name FROM currencies ORDER BY currency_code = '{$uI->get()['currency']}' DESC, currency_code ASC, name ASC");
		while($row = $sql->fetch_object()) $currencies[$row->currency_code] = $row->name;

		if(isset($_POST['firstname'])){
			try {
				foreach($_POST as $k => $v){
					$n = "p_" . strtolower($k);
					$$n = $db->real_escape_string($v);
				}

				if(empty($p_firstname)) throw new Exception($ql['ERR3']);
				if(empty($p_lastname)) throw new Exception($ql['ERR4']);
				if(!array_key_exists($p_country, $countries)) throw new Exception($ql['ERR10']);
				if(!empty($p_mail) && !filter_var($p_mail, FILTER_VALIDATE_EMAIL)) throw new Exception($ql['ERR9']);
				if(!array_key_exists($p_language, $languages)) throw new Exception($ql['ERR11']);
				if(!array_key_exists($p_currency, $currencies)) throw new Exception($al['ERR1']);

				$rights = implode(",", array_values(isset($_POST['rights']) && is_array($_POST['rights']) ? $_POST['rights'] : []));

				$data = Array(
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
				if(!$c->save($data)) throw new Exception($l['ERR1']);

				echo '<div class="alert alert-success">' . $l['SUC'] . '</div>';
				unset($_POST);
				$c = new Contact($_GET['edit']);

				alog("customer", "contact_changed", $c->get("ID"));
			} catch (Exception $ex) {
				echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
			}
		}

		?>

		<form method="POST">

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$ql['FN']; ?></label>
						<input type="text" name="firstname" placeholder="<?=$al['FNP']; ?>" value="<?=isset($_POST['firstname']) ? $_POST['firstname'] : $c->get("firstname"); ?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$ql['LN']; ?></label>
						<input type="text" name="lastname" placeholder="<?=$al['LNP']; ?>" value="<?=isset($_POST['lastname']) ? $_POST['lastname'] : $c->get("lastname"); ?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$ql['CP']; ?></label>
						<input type="text" name="company" placeholder="<?=$al['OPTIONAL']; ?>" value="<?=isset($_POST['company']) ? $_POST['company'] : $c->get("company"); ?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$ql['OP']; ?></label>
						<input type="text" name="type" placeholder="<?=$al['OPTIONAL']; ?>" value="<?=isset($_POST['type']) ? $_POST['type'] : $c->get("type"); ?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-10">
					<div class="form-group">
						<label><?=$ql['ST']; ?></label>
						<input type="text" name="street" placeholder="<?=$al['OPTIONAL']; ?>" value="<?=isset($_POST['street']) ? $_POST['street'] : $c->get("street"); ?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-2">
					<div class="form-group">
						<label><?=$ql['SN']; ?></label>
						<input type="text" name="street_number" placeholder="<?=$al['OPTIONAL']; ?>" value="<?=isset($_POST['street_number']) ? $_POST['street_number'] : $c->get("street_number"); ?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="form-group">
						<label><?=$ql['PC']; ?></label>
						<input type="text" name="postcode" placeholder="<?=$al['OPTIONAL']; ?>" value="<?=isset($_POST['postcode']) ? $_POST['postcode'] : $c->get("postcode"); ?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-7">
					<div class="form-group">
						<label><?=$ql['CT']; ?></label>
						<input type="text" name="city" placeholder="<?=$al['OPTIONAL']; ?>" value="<?=isset($_POST['city']) ? $_POST['city'] : $c->get("city"); ?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-3">
					<div class="form-group">
						<label><?=$al['COUNTRY']; ?></label>
						<select name="country" class="form-control">
						<?php foreach($countries as $key => $name){ ?>
						<option value="<?=$key; ?>"<?php if((isset($_POST['country']) && $_POST['country'] == $key) || (!isset($_POST['country']) && $key == $c->get('country'))) echo ' selected="selected"'; ?>><?=$name; ?></option>
						<?php } ?>
						</select>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$ql['EM']; ?></label>
						<input type="text" name="mail" placeholder="<?=$al['OPTIONAL']; ?>" value="<?=isset($_POST['mail']) ? $_POST['mail'] : $c->get("mail"); ?>" class="form-control" />
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$al['TEL']; ?></label>
						<input type="text" name="telephone" placeholder="<?=$al['OPTIONAL']; ?>Optional" value="<?=isset($_POST['telephone']) ? $_POST['telephone'] : $c->get("telephone"); ?>" class="form-control" />
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><?=$al['LANG']; ?></label>
						<select name="language" class="form-control">
						<?php foreach($languages as $key => $name){ ?>
						<option value="<?=$key; ?>"<?php if((isset($_POST['language']) && $_POST['language'] == $key) || (!isset($_POST['language']) && $key == $c->get("language")))  echo ' selected="selected"'; ?>><?=$name; ?></option>
						<?php } ?>
						</select>
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$al['CURRENCY']; ?></label>
						<select name="currency" class="form-control">
						<?php foreach($currencies as $key => $name){ ?>
						<option value="<?=$key; ?>"<?php if((isset($_POST['currency']) && $_POST['currency'] == $key) || (!isset($_POST['currency']) && $key == $c->get("currency"))) echo ' selected="selected"'; ?>><?=$name; ?></option>
						<?php } ?>
						</select>
					</div>
				</div>
			</div>

			<div class="panel panel-default">
				<div class="panel-heading"><?=$al['EMTPL']; ?></div>
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
						while ($row = $sql->fetch_object()) { ?>
						<div class="col-md-6">
							<div class="checkbox">
								<label>
									<input type="checkbox" class="mt_checkall" data-category="<?=htmlentities($row->category); ?>" />
									<b><?=htmlentities($row->category); ?></b>
								</label>
							</div>

							<?php
							$sql2 = $db->query("SELECT ID, name FROM email_templates WHERE category = '" . $db->real_escape_string($row->category) . "' ORDER BY name ASC");
							$all = true;
							while ($e = $sql2->fetch_object()) { if (in_array($e->name, $ignore)) continue; if (!in_array($e->ID, $choosed)) $all = false; ?>
							<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
								<label>
									<input type="checkbox" class="mt_checkbox" name="mail_templates[]" value="<?=$e->ID; ?>" data-category="<?=htmlentities($row->category); ?>"<?=in_array($e->ID, $choosed) ? ' checked=""' : ''; ?> />
									<?=htmlentities($e->name); ?>
								</label>
							</div>
							<?php } if ($all) { ?><script>$(".mt_checkall[data-category=<?=htmlentities($row->category); ?>]").prop("checked", true);</script><?php } ?>
						</div>
						<?php } ?>
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
				<div class="panel-heading"><?=$l['RIGHTS']; ?></div>
					<?php
					$rights = explode(",", $c->get("rights"));

					if (isset($_POST['rights']) && is_array($_POST['rights'])) {
						$rights = $_POST['rights'];
					}
					?>
					<div class="panel-body">
						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="tickets"<?=in_array("tickets", $rights) ? ' checked=""' : ''; ?> />
								<?=$l['RIGHT1']; ?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="invoices"<?=in_array("invoices", $rights) ? ' checked=""' : ''; ?> />
								<?=$l['RIGHT2']; ?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="quotes"<?=in_array("quotes", $rights) ? ' checked=""' : ''; ?> />
								<?=$l['RIGHT3']; ?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="products"<?=in_array("products", $rights) ? ' checked=""' : ''; ?> />
								<?=$l['RIGHT4']; ?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="domains"<?=in_array("domains", $rights) ? ' checked=""' : ''; ?> />
								<?=$l['RIGHT5']; ?>
							</label>
						</div>

						<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
							<label>
								<input type="checkbox" name="rights[]" value="emails"<?=in_array("emails", $rights) ? ' checked=""' : ''; ?> />
								<?=$l['RIGHT6']; ?>
							</label>
						</div>
					</div>
				</div>

			<input type="submit" class="btn btn-primary btn-block" value="<?=$l['SAVE']; ?>" />
		</form>
	</div>
</div>
<?php } ?>