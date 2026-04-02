<?php
global $lang, $db, $CFG, $pars, $var, $user, $val;

User::status();

$l = $lang['CONTACTS'];

$title = $l['TITLE'];
$tpl = "contacts";

$section = $var['section'] = $pars[0] ?? "";

if ($section == "add") {
    if (isset($_POST['firstname'])) {
        try {
            if (empty($_POST['firstname'])) {
                throw new Exception;
            }

            if (empty($_POST['lastname'])) {
                throw new Exception;
            }

            if (empty($_POST['mail']) || !$val->email($_POST['mail'])) {
                throw new Exception;
            }

            $sql = $db->prepare("INSERT INTO client_contacts (`firstname`, `lastname`, `mail`, `client`) VALUES (?,?,?,?)");
            $sql->bind_param("sssi", $_POST['firstname'], $_POST['lastname'], $_POST['mail'], $cid = $user->get()['ID']);
            $sql->execute();
            $sql->close();

            $iid = $db->insert_id;

            $user->log("Kontakt #$iid angelegt");

            header('Location: ' . $CFG['PAGEURL'] . 'contacts/' . $iid);
            exit;
        } catch (Exception $ex) {
            $var['err'] = $lang['CONTACTS']['ADDERR'];
        }
    }
} else {
    $var['contacts'] = [];
    $sql = $db->query("SELECT * FROM client_contacts WHERE client = " . $user->get()['ID']);
    while ($row = $sql->fetch_assoc()) {
        $var['contacts'][$row['ID']] = $row;
    }

    if (!empty($section) && is_numeric($section) && array_key_exists($section, $var['contacts'])) {
        $var['c'] = $var['contacts'][$section];

        if (($pars[1] ?? "") == "del") {
            $db->query("DELETE FROM client_contacts WHERE ID = " . intval($section));
            $user->log("Kontakt #$section gelöscht");
            header('Location: ' . $CFG['PAGEURL'] . 'contacts');
            exit;
        }

        $var['countries'] = [];
        $sql = $db->query("SELECT `ID`, `name` FROM client_countries WHERE active = 1 ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            $var['countries'][$row->ID] = $row->name;
        }

        $choosed = isset($_POST['mail_templates']) && is_array($_POST['mail_templates']) ? $_POST['mail_templates'] : explode(",", $var['c']['mail_templates']);
        foreach ($choosed as &$v) {
            $v = intval($v);
        }
        unset($v);

        if (isset($_POST['firstname'])) {
            $c = new Contact($section);

            foreach($_POST as $k => $v){
                $n = "p_" . strtolower($k);
                $$n = $db->real_escape_string($v);
            }

            if(empty($p_firstname)) $p_firstname = $c->get("firstname");
            if(empty($p_lastname)) $p_lastname = $c->get("lastname");
            if(!array_key_exists($p_country, $var['countries'])) $p_country = array_shift(array_keys($var['countries']));
            if(empty($p_mail) || !filter_var($p_mail, FILTER_VALIDATE_EMAIL)) $p_mail = $c->get("mail");
            if(!array_key_exists($p_language, $var['langs'])) $p_language = array_shift(array_keys($var['langs']));
            if(!array_key_exists($p_currency, $var['currencies'])) $p_currency = array_shift(array_keys($var['currencies']));

            $myRights = array_values(isset($_POST['rights']) && is_array($_POST['rights']) ? $_POST['rights'] : []);
            $rights = "";
            $allowed = ["tickets", "invoices", "quotes", "products", "domains", "emails"];
            foreach ($myRights as $right) {
                if (in_array($right, $allowed)) {
                    $rights .= $right . ",";
                }
            }
            unset($right);
            $rights = rtrim($rights, ",");

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
            
            $c->save($data);

            $user->log("Kontakt #$section bearbeitet");

            header('Location: ' . $CFG['PAGEURL'] . 'contacts');
            exit;
        }

        $section = $var['section'] = "edit";
        $var['rights'] = explode(",", $var['c']['rights']);

        ob_start();
        ?>
        <div class="row" style="margin-top: -10px;">
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
        <?php
        $var['tHtml'] = ob_get_clean();
    }
}