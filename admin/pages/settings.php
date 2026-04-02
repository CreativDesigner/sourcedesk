<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SETTINGS'];
title($l['TITLE']);
menu("settings");

if (!$ari->check(34)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "settings");} else {
    $tab = isset($_GET['tab']) ? $_GET['tab'] : "general";

    if ($tab == "license" && isset($_POST['new_license_key'])) {
        $key = $_POST['new_license_key'];

        try {
            $res = sd_licenseCheck($key, "");
        } catch (Exception $ex) {
            die("fail");
        }

        if (!$res[0]) {
            die("fail");
        }

        $db->query("UPDATE `settings` SET `value` = '" . $db->real_escape_string($key) . "' WHERE `key` = 'license_key'");
        $db->query("UPDATE `settings` SET `value` = '" . $db->real_escape_string($res[1]) . "' WHERE `key` = 'license_id'");

        die("ok");
    }

    if ($tab == "salutation" && isset($_GET['save']) && isset($_POST['name']) && isset($_POST['value']) && isset($_POST['pk'])) {
        function saveSal()
        {
            global $db, $CFG;

            $id = intval($_POST['pk']);
            $f = $db->real_escape_string($_POST['name']);
            $v = $db->real_escape_string($_POST['value']);

            $db->query("UPDATE salutations SET `$f` = '$v' WHERE ID = $id");
            exit;
        }

        $v = $_POST['value'];

        switch ($_POST['name']) {
            case "time":
                $ex = explode("-", $v);

                if (count($ex) == 2) {
                    if (strtotime($ex[0]) !== false && strtotime($ex[1]) !== false) {
                        saveSal();
                    }
                }

                if (empty($v)) {
                    saveSal();
                }
                break;

            case "language":
                if (empty($v) || array_key_exists($v, $languages)) {
                    saveSal();
                }
                break;

            case "gender":
                if (empty($v) || in_array($v, ["MALE", "FEMALE", "DIVERS"])) {
                    saveSal();
                }
                break;

            case "cgroup":
                $v = intval($v);
                if (in_array($v, ["0", "-1"]) || $db->query("SELECT 1 FROM client_groups WHERE ID = " . $v)) {
                    saveSal();
                }
                break;

            case "b2b":
                $v = intval($v);
                if (in_array($v, ["0", "-1", "1"])) {
                    saveSal();
                }
                break;

            case "country":
                $v = intval($v);
                if ($v == -1 || $db->query("SELECT 1 FROM client_countries WHERE ID = " . $v)) {
                    saveSal();
                }
                break;

            case "salutation":
                saveSal();
                break;
        }

        http_response_code("403");
        exit;
    }

    if ($tab == "branding" && isset($_GET['save']) && isset($_POST['name']) && isset($_POST['value']) && isset($_POST['pk'])) {
        function saveBra()
        {
            global $db, $CFG;

            $id = intval($_POST['pk']);
            $f = $db->real_escape_string($_POST['name']);
            $v = $db->real_escape_string($_POST['value']);

            $db->query("UPDATE branding SET `$f` = '$v' WHERE ID = $id");
            exit;
        }

        $v = $_POST['value'];

        switch ($_POST['name']) {
            case "host":
                saveBra();
                break;

            case "pageurl":
                $_POST['value'] = rtrim($v, "/") . "/";
                saveBra();
                break;

            case "pagename":
                saveBra();
                break;

            case "pagemail":
                if (filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    saveBra();
                }
                break;

            case "design":
                if (is_dir(__DIR__ . "/../../themes/" . basename($v)) && basename($v) != "order") {
                    $_POST['value'] = basename($v);
                    saveBra();
                }
                break;
        }

        http_response_code("403");
        exit;
    }

    if ($tab == "letters" && isset($_GET['prov']) && array_key_exists($_GET['prov'], LetterHandler::getDrivers())) {
        foreach ($_POST as $k => $v) {
            if (!array_key_exists($k, LetterHandler::getDrivers()[$_GET['prov']]->getSettings())) {
                continue;
            }

            if ($db->query("SELECT 1 FROM letter_providers WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'")->num_rows == 0) {
                $db->query("INSERT INTO letter_providers (provider, setting, `value`) VALUES ('" . $db->real_escape_string($_GET['prov']) . "', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string(encrypt($v)) . "')");
            } else {
                $db->query("UPDATE letter_providers SET `value` = '" . $db->real_escape_string(encrypt($v)) . "' WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'");
            }

        }

        alog("settings", "letter_update");
        die("ok");
    }

    if ($tab == "sms" && isset($_GET['prov']) && array_key_exists($_GET['prov'], SMSHandler::getDrivers())) {
        foreach ($_POST as $k => $v) {
            if (!array_key_exists($k, SMSHandler::getDrivers()[$_GET['prov']]->getSettings())) {
                continue;
            }

            if ($db->query("SELECT 1 FROM sms_providers WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'")->num_rows == 0) {
                $db->query("INSERT INTO sms_providers (provider, setting, `value`) VALUES ('" . $db->real_escape_string($_GET['prov']) . "', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string(encrypt($v)) . "')");
            } else {
                $db->query("UPDATE sms_providers SET `value` = '" . $db->real_escape_string(encrypt($v)) . "' WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'");
            }

        }

        alog("settings", "sms_update");
        die("ok");
    }

    if ($tab == "encashment" && isset($_GET['prov']) && array_key_exists($_GET['prov'], EncashmentHandler::getDrivers())) {
        foreach ($_POST as $k => $v) {
            if (!array_key_exists($k, EncashmentHandler::getDrivers()[$_GET['prov']]->getSettings())) {
                continue;
            }

            if ($db->query("SELECT 1 FROM encashment WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'")->num_rows == 0) {
                $db->query("INSERT INTO encashment (provider, setting, `value`) VALUES ('" . $db->real_escape_string($_GET['prov']) . "', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string(encrypt($v)) . "')");
            } else {
                $db->query("UPDATE encashment SET `value` = '" . $db->real_escape_string(encrypt($v)) . "' WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'");
            }

        }

        alog("settings", "encashment_update");
        die("ok");
    }

    if ($tab == "scoring" && isset($_GET['prov']) && array_key_exists($_GET['prov'], ScoringHandler::getDrivers())) {
        foreach ($_POST as $k => $v) {
            if (!array_key_exists($k, ScoringHandler::getDrivers()[$_GET['prov']]->getSettings()) && substr($k, 0, 10) != "automatic-") {
                continue;
            }

            if ($db->query("SELECT 1 FROM scoring WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'")->num_rows == 0) {
                $db->query("INSERT INTO scoring (provider, setting, `value`) VALUES ('" . $db->real_escape_string($_GET['prov']) . "', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string(encrypt($v)) . "')");
            } else {
                $db->query("UPDATE scoring SET `value` = '" . $db->real_escape_string(encrypt($v)) . "' WHERE provider = '" . $db->real_escape_string($_GET['prov']) . "' AND setting = '" . $db->real_escape_string($k) . "'");
            }

        }

        alog("settings", "scoring_update");
        die("ok");
    }

    if ($tab == "telephone_log" && !empty($_GET['prov'])) {
        $u = unserialize($CFG['TELEPHONE_LOG']);
        if (array_key_exists($_GET['prov'], $u)) {
            $u[$_GET['prov']] = $_POST;
            $CFG['TELEPHONE_LOG'] = serialize($u);
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string(encrypt($CFG['TELEPHONE_LOG'])) . "' WHERE `key` = 'telephone_log'");
        }

        alog("settings", "telephone_log_update");
        die("ok");
    }

    if (isset($_POST['change'])) {
        $updatable = array("cashbox_prefix", "cashbox_active", "tracking", "pagename", "bugtracker_dept", "pageurl", "cdn_urls", "pagemail", "theme", "sms_verify", "sms_verify_orders", "hash_method", "hash_method_admin", "display_errors", "explicit_ssl", "maintenance", "captcha_type", "recaptcha_public", "recaptcha_private", "invoice_prefix", "min_invlen", "min_quolen", "invoice_duedate", "invoice_advance", "top_alert_type", "clientside_hashing", "clientside_hashing_admin", "hsts", "password_history", "affiliate_commission", "affiliate_active", "affiliate_cookie", "cookie_accept", "mogelmail", "user_confirmation", "wtip", "allow_reg", "trim_whitespace", "facebook_id", "facebook_secret", "twitter_id", "twitter_secret", "facebook_login", "twitter_login", "social_login_toggle", "block_proxy", "piwik_ecommerce", "offer_prefix", "offer_intro", "offer_extro", "offer_terms", "affiliate_days", "affiliate_min", "exchange_source", "ext_affiliate", "websocket_ao", "websocket_port", "websocket_active", "websocket_pem", "websocket_key", "gmap_key", "sms_provider", "gitlab_host", "gitlab_key", "github_user", "github_key", "telegram_token", "telegram_chat", "telegram_notifications", "git_type", "branding", "pdf_sender", "pdf_address", "pdf_recipient", "pdf_iban", "pdf_bic", "pdf_bank", "cnr_prefix", "pnr_prefix", "micropatches", "customer_authcode", "no_invoicing", "domain_action_conf", "domain_log", "redirect_login", "ip_header", "csrf_disabled", "breakdown", "admin_color", "invoice_dist_min", "invoice_dist_max", "min_age", "hide_normal_server");
        $encrypted = array("recaptcha_private", "facebook_secret", "twitter_secret", "websocket_key", "websocket_pem", "gitlab_key", "telegram_token", "github_user", "github_key");
        if ($CFG['MASTER']) {
            array_push($updatable, "version", "patchlevel");
        }

        unset($_POST['change']);
        foreach ($_POST as $k => $v) {
            if ($k == "admin_whitelist" && !$ari->otpCheck()) {
                $array = explode(",", $v);
                foreach ($array as $k => &$v) {
                    $v = trim($v);
                    if (!$v) {
                        unset($array[$k]);
                    }

                }

                // Check if current client locks out hisself
                if (count($array) > 0 && !$ari->accessAllowed($array)) {
                    $errormsg = $l['ERR1'];
                    continue;
                }

                $db->query("UPDATE `settings` SET `value` = '" . $db->real_escape_string(serialize($array)) . "' WHERE `key` = 'admin_whitelist' LIMIT 1");

                unset($_POST['admin_whitelist']);
                continue;
            }

            if (is_array($v)) {
                $v = serialize($v);
            }

            $k = $db->real_escape_string($k);
            if (in_array($k, $encrypted)) {
                $v = encrypt($v);
            }

            if ($k == "affiliate_commission") {
                $v = $nfo->phpize($v);
            }

            $v = $db->real_escape_string($v);

            if (in_array($k, $updatable)) {
                $db->query("UPDATE `settings` SET `value` = '$v' WHERE `key` = '$k' LIMIT 1");
            }
        }

        if ($tab == "affiliate") {
            $hidden = array();
            if (isset($_POST['ext_affiliate_ex'])) {
                foreach ($_POST['ext_affiliate_ex'] as $id) {
                    if (is_numeric($id)) {
                        array_push($hidden, $id);
                    }
                }
            }

            $v = $db->real_escape_string(serialize($hidden));
            $db->query("UPDATE `settings` SET `value` = '$v' WHERE `key` = 'ext_affiliate_ex' LIMIT 1");
        }

        if (isset($_POST['action']) && $_POST['action'] == "save_search") {
            $hidden = array();

            if (isset($_POST['hidden_arts'])) {
                foreach ($_POST['hidden_arts'] as $id) {
                    if (is_numeric($id)) {
                        array_push($hidden, "art" . $id);
                    }
                }
            }

            if (isset($_POST['hidden_cats'])) {
                foreach ($_POST['hidden_cats'] as $id) {
                    if (is_numeric($id)) {
                        array_push($hidden, "cat" . $id);
                    }
                }
            }

            if (isset($_POST['hidden_pages'])) {
                foreach ($_POST['hidden_pages'] as $id) {
                    if (is_numeric($id)) {
                        array_push($hidden, "page" . $id);
                    }
                }
            }

            $v = $db->real_escape_string(implode(",", $hidden));
            $db->query("UPDATE `settings` SET `value` = '$v' WHERE `key` = 'search_hidden' LIMIT 1");
        }

        $checkboxes = array();
        if ($tab == "general") {
            array_push($checkboxes, "maintenance", "cookie_accept", "user_confirmation", "trim_whitespace", "mogelmail", "branding", "micropatches", "customer_authcode", "domain_action_conf", "domain_log", "redirect_login", "wtip", "allow_reg", "breakdown", "hide_normal_server");
        }

        if ($tab == "security") {
            array_push($checkboxes, "explicit_ssl", "clientside_hashing", "clientside_hashing_admin", "hsts", "password_history", "block_proxy", "sms_verify_orders");
        }

        if ($tab == "invoices") {
            array_push($checkboxes, "cashbox_active", "no_invoicing");
        }

        if ($tab == "affiliate") {
            array_push($checkboxes, "affiliate_active");
        }

        if ($tab == "social_login") {
            array_push($checkboxes, "facebook_login", "twitter_login", "social_login_toggle");
        }

        if ($tab == "analytics") {
            array_push($checkboxes, "piwik_ecommerce");
        }

        if ($tab == "websocket") {
            array_push($checkboxes, "websocket_active");
        }

        foreach ($checkboxes as $k) {
            if (!isset($_POST[$k])) {
                $db->query("UPDATE `settings` SET `value` = '0' WHERE `key` = '$k' LIMIT 1");
            }
        }

        if (isset($_POST['top_alert_msg'])) {
            $top_alert_msg = array();
            foreach ($languages as $key => $name) {
                if (isset($_POST['top_alert_msg'][$key])) {
                    $top_alert_msg[$key] = $_POST['top_alert_msg'][$key];
                }
            }

            $db->query("UPDATE `settings` SET `value` = '" . $db->real_escape_string(serialize($top_alert_msg)) . "' WHERE `key` = 'top_alert_msg' LIMIT 1");
            unset($_SESSION['top_alert']);
        }

        if (isset($_POST['maintenance_msg'])) {
            $maintenance_msg = array();
            foreach ($languages as $key => $name) {
                if (isset($_POST['maintenance_msg'][$key])) {
                    $maintenance_msg[$key] = $_POST['maintenance_msg'][$key];
                }
            }

            $db->query("UPDATE `settings` SET `value` = '" . $db->real_escape_string(serialize($maintenance_msg)) . "' WHERE `key` = 'maintenance_msg' LIMIT 1");
        }

        if (isset($_POST['sepa_limit'])) {
            $old_limit = $CFG['SEPA_LIMIT'];
            $limit = $nfo->phpize($_POST['sepa_limit']);
            if (is_double($limit) || is_numeric($limit)) {
                if ($limit >= 0) {
                    $db->query("UPDATE `settings` SET `value` = '" . doubleval($limit) . "' WHERE `key` = 'sepa_limit' LIMIT 1");
                    $db->query("UPDATE `clients` SET `sepa_limit` = " . doubleval($limit) . " WHERE `sepa_limit` = " . doubleval($old_limit));
                }
            }
        }

        if (isset($_POST['postpaid_def'])) {
            $old_limit = $CFG['POSTPAID_DEF'];
            $limit = $nfo->phpize($_POST['postpaid_def']);
            if (is_double($limit) || is_numeric($limit)) {
                if ($limit >= 0) {
                    $db->query("UPDATE `settings` SET `value` = '" . doubleval($limit) . "' WHERE `key` = 'postpaid_def' LIMIT 1");
                    $db->query("UPDATE `clients` SET `postpaid` = " . doubleval($limit) . " WHERE `postpaid` = " . doubleval($old_limit));
                }
            }
        }

        alog("settings", "save_all", $tab);

        $msg = $l['SUC'];
    }

    $cfg_sql = $db->query("SELECT * FROM settings");
    while ($c = $cfg_sql->fetch_object()) {
        $CFG[strtoupper($c->key)] = in_array($c->key, $encrypted) ? decrypt($c->value) : $c->value;
    }

    $timezones = DateTimeZone::listAbbreviations();
    $t = array();

    foreach ($timezones as $j => $c) {
        foreach ($c as $k => $v) {
            if (is_string($v['timezone_id']) && count(explode("/", $v['timezone_id'])) == 2 && !in_array(trim($v['timezone_id']), $t)) {
                array_push($t, trim($v['timezone_id']));
            }
        }
    }

    if ($tab == "crons" && isset($_GET['unlock']) && file_exists(__DIR__ . "/../../controller/crons/" . basename($_GET['unlock']) . ".lock")) {
        alog("settings", "delete_lockfile", $_GET['unlock']);
        unlink(__DIR__ . "/../../controller/crons/" . basename($_GET['unlock']) . ".lock");
        $msg = $l['SUC2'];
    }

    $var['cronhang'] = 0;

    foreach (glob(__DIR__ . "/../../controller/crons/*.lock") as $f) {
        $c = file_get_contents($f);
        $ex = explode("\n", $c);
        $l2 = $ex[0];
        $d = substr($l2, 1, 19);
        if (time() - strtotime($d) > 1200) {
            $var['cronhang']++;
        }

    }

    $var['cronhang'] += $db->query("SELECT 1 FROM cronjobs WHERE active = 1 AND last_call < " . time() . " - 10*`intervall` LIMIT 1")->num_rows;

    asort($t);
    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>
<div class="row">
<div class="col-md-3">
<div class="list-group">
<a class="list-group-item<?=$tab == "general" ? " active" : "";?>" href="./?p=settings"><?=$l['T1'];?></a>
<a class="list-group-item<?=$tab == "local" ? " active" : "";?>" href="./?p=settings&tab=local"><?=$l['T2'];?></a>
<a class="list-group-item<?=$tab == "countries" ? " active" : "";?>" href="./?p=settings&tab=countries"><?=$l['T3'];?> <?php if ($db->query("SELECT ID FROM client_countries WHERE active = 1 AND ID = " . $CFG['DEFAULT_COUNTRY'])->num_rows <= 0) {
        echo '<span class="label label-warning">!</span>';
    }
    ?></a>
<a class="list-group-item<?=$tab == "currencies" ? " active" : "";?>" href="./?p=settings&tab=currencies"><?=$l['T4'];?> <?php if ($var['crit2'] > 0) {
        echo '<span class="label label-warning">!</span>';
    }
    ?></a>
<a class="list-group-item<?=$tab == "logos" ? " active" : "";?>" href="./?p=settings&tab=logos"><?=$l['T5'];?></a>
<a class="list-group-item<?=$tab == "branding" ? " active" : "";?>" href="./?p=settings&tab=branding"><?=$l['TB'];?></a>
<a class="list-group-item<?=$tab == "pdf" ? " active" : "";?>" href="./?p=settings&tab=pdf"><?=$l['T6'];?></a>
<a class="list-group-item<?=$tab == "invoices" ? " active" : "";?>" href="./?p=settings&tab=invoices"><?=$l['T7'];?></a>
<a class="list-group-item<?=$tab == "offers" ? " active" : "";?>" href="./?p=settings&tab=offers"><?=$l['T8'];?></a>
<a class="list-group-item<?=$tab == "reminders" ? " active" : "";?>" href="./?p=settings&tab=reminders"><?=$l['T9'];?></a>
<a class="list-group-item<?=$tab == "encashment" ? " active" : "";?>" href="./?p=settings&tab=encashment"><?=$l['T10'];?></a>
<a class="list-group-item<?=$tab == "telephone_log" ? " active" : "";?>" href="./?p=settings&tab=telephone_log"><?=$l['T11'];?></a>
<a class="list-group-item<?=$tab == "scoring" ? " active" : "";?>" href="./?p=settings&tab=scoring"><?=$l['T12'];?></a>
<a class="list-group-item<?=$tab == "letters" ? " active" : "";?>" href="./?p=settings&tab=letters"><?=$l['T13'];?></a>
<a class="list-group-item<?=$tab == "sms" ? " active" : "";?>" href="./?p=settings&tab=sms"><?=$l['T14'];?></a>
<a class="list-group-item<?=$tab == "telegram" ? " active" : "";?>" href="./?p=settings&tab=telegram"><?=$l['T15'];?></a>
<a class="list-group-item<?=$tab == "salutation" ? " active" : "";?>" href="./?p=settings&tab=salutation"><?=$l['TSAL'];?></a>
<a class="list-group-item<?=$tab == "cfields" ? " active" : "";?>" href="./?p=settings&tab=cfields"><?=$l['T16'];?></a>
<a class="list-group-item<?=$tab == "cgroups" ? " active" : "";?>" href="./?p=settings&tab=cgroups"><?=$l['T17'];?></a>
<a class="list-group-item<?=$tab == "security" ? " active" : "";?>" href="./?p=settings&tab=security"><?=$l['T18'];?></a>
<a class="list-group-item<?=$tab == "social_login" ? " active" : "";?>" href="./?p=settings&tab=social_login"><?=$l['T19'];?></a>
<a class="list-group-item<?=$tab == "crons" ? " active" : "";?>" href="./?p=settings&tab=crons"><?=$l['T20'];?> <?php if ($var['cronhang'] > 0) {
        echo '<span class="label label-warning">!</span>';
    }
    ?></a>
<a class="list-group-item<?=$tab == "affiliate" ? " active" : "";?>" href="./?p=settings&tab=affiliate"><?=$l['T21'];?></a>
<a class="list-group-item<?=$tab == "seo" ? " active" : "";?>" href="./?p=settings&tab=seo"><?=$l['T22'];?></a>
<a class="list-group-item<?=$tab == "search" ? " active" : "";?>" href="./?p=settings&tab=search"><?=$l['T23'];?></a>
<a class="list-group-item<?=$tab == "analytics" ? " active" : "";?>" href="./?p=settings&tab=analytics"><?=$l['T24'];?></a>
<a class="list-group-item<?=$tab == "system_status" ? " active" : "";?>" href="./?p=settings&tab=system_status"><?=$l['T25'];?> <?php $faults = $db->query("SELECT * FROM `system_status`")->num_rows;if ($faults > 0) {
        echo '<span class="label label-warning">' . $faults . '</span>';
    }
    ?></a>
<a class="list-group-item<?=$tab == "cache" ? " active" : "";?>" href="./?p=settings&tab=cache"><?=$l['T26'];?></a>
<a class="list-group-item<?=$tab == "increment" ? " active" : "";?>" href="./?p=settings&tab=increment"><?=$l['T27'];?></a>
<a class="list-group-item<?=$tab == "websocket" ? " active" : "";?>" href="./?p=settings&tab=websocket"><?=$l['T28'];?></a>
<?php if ($ari->check(38)) {?><a class="list-group-item<?=$tab == "pma" ? " active" : "";?>" href="./?p=settings&tab=pma"><?=$l['T29'];?></a><?php }?>
<a class="list-group-item<?=$tab == "license" ? " active" : "";?>" href="./?p=settings&tab=license"><?=$l['T30'];?></a>
</div>
</div>
<div class="col-md-9">

<?php if (isset($errormsg)) {
        echo "<div class=\"alert alert-danger\">$errormsg</div>";
    } else if (isset($msg)) {
        echo "<div class=\"alert alert-success\">$msg</div>";
    }
    ?>

<?php if ($tab == "general") {?>
<div>
  <form accept-charset="UTF-8" role="form" id="login-form" method="post">
  		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

      <fieldset>
		<div class="form-group">
			<label><?=$l['PAGENAME'];?></label>
			<input type="text" name="pagename" value="<?=$CFG['PAGENAME'];?>" placeholder="<?=$l['PAGENAMEP'];?>" class="form-control">
		</div>

		<div class="form-group">
			<label><?=$l['URL'];?></label>
			<input type="text" name="pageurl" value="<?=$CFG['PAGEURL'];?>" placeholder="<?=$l['URLP'];?>" class="form-control">
			<p class="help-block"><?=$l['URLH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['CDNURL'];?></label>
			<input type="text" name="cdn_urls" value="<?=$CFG['CDN_URLS'];?>" placeholder="<?=$l['CDNURLP'];?>" class="form-control">
			<p class="help-block"><?=$l['CDNURLH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['PAGEMAIL'];?></label>
			<input type="text" name="pagemail" value="<?=$CFG['PAGEMAIL'];?>" placeholder="<?=$l['PAGEMAILP'];?>" class="form-control">
		</div>

		<div class="form-group">
			<label><?=$l['BUGTRACKER_DEPT'];?></label>
			<select name="bugtracker_dept" class="form-control">
				<?=$dept = $CFG['BUGTRACKER_DEPT'];?>
				<option disabled="disabled" selected="selected"><?=$lang['NEW_TICKET']['PCDEPT'];?></option>
			<?php
$sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . $row->ID . '"' . ($dept == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
        }

        ?>
			<option disabled="disabled"><?=$lang['NEW_TICKET']['PCSTAFF'];?></option>
			<?php
$sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . ($row->ID / -1) . '"' . ($dept / -1 == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
        }

        ?>
			</select>
		</div>

		<div class="form-group">
			<label for="exampleInputEmail1"><?=$l['DESIGN'];?></label>
			<select name="theme" class="form-control">
			<?php
$designs = array();
        $license = array("tempo");

        $handle = opendir(__DIR__ . "/../../themes/");
        while ($datei = readdir($handle)) {
            if (substr($datei, 0, 1) != "." && is_dir(__DIR__ . "/../../themes/" . $datei) && $datei != "order") {
                array_push($designs, strtolower($datei));
            }
        }

        asort($designs);
        foreach ($designs as $k => $v) {
            $ex = array();
            $ex[0] = $v;
            ?>
				<option value="<?=strtolower($ex[0]);?>" <?php if (strtolower($CFG['THEME']) == strtolower($ex[0])) {
                echo "selected";
            }
            ?>><?=ucfirst($ex[0]);if (in_array($ex[0], $license)) {
                echo " {$l['THEMELICENSEREQ']}";
            }
            ?></option>
				<?php
}
        ?>
			</select>
		</div>

		<div class="form-group">
			<label><?=$l['DISPLAYPHPERR'];?></label>
			<select name="display_errors" class="form-control">
			<option value="no" <?php if ($CFG['DISPLAY_ERRORS'] == "no") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['PE0'];?></option>
			<option value="errors" <?php if ($CFG['DISPLAY_ERRORS'] == "errors") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['PE1'];?></option>
			</select>
		</div>

		<div class="form-group">
			<label><?=$l['GMAPKEY'];?></label>
			<input type="text" name="gmap_key" value="<?=$CFG['GMAP_KEY'];?>" placeholder="<?=$l['OPTIONAL'];?>" class="form-control">
		</div>

		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$l['CNRPREFIX'];?></label>
					<input type="text" name="cnr_prefix" value="<?=$CFG['CNR_PREFIX'];?>" placeholder="<?=$l['CNRPREFIXP'];?>" class="form-control">
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$l['PNRPREFIX'];?></label>
					<input type="text" name="pnr_prefix" value="<?=$CFG['PNR_PREFIX'];?>" placeholder="<?=$l['CNRPREFIXP'];?>" class="form-control">
				</div>
			</div>
		</div>

		<div class="form-group">
			<label><?=$l['MIN_AGE'];?></label>
			<input type="text" name="min_age" value="<?=(max(0, intval($CFG['MIN_AGE']))) ?: "";?>" placeholder="<?=$l['MIN_AGEP'];?>" class="form-control">
		</div>

		<?php if ($CFG['MASTER']) {?>
		<div class="form-group">
			<label><?=$l['VERSION'];?></label>
			<input type="text" name="version" value="<?=$CFG['VERSION'];?>" placeholder="<?=$l['VERSIONP'];?>" class="form-control">
			<p class="help-block"><?=$l['VERSIONH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['PATCHLEVEL'];?></label>
			<input type="text" name="patchlevel" value="<?=$CFG['PATCHLEVEL'];?>" placeholder="<?=$l['PATCHLEVELP'];?>" class="form-control">
		</div>
		<?php }?>

		<div class="form-group">
			<label><?=$l['TOPALERT'];?></label>
			<select name="top_alert_type" class="form-control" style="margin-bottom: 5px;">
			<option value="none" <?php if ($CFG['TOP_ALERT_TYPE'] == "none") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['TA0'];?></option>
			<option value="info" <?php if ($CFG['TOP_ALERT_TYPE'] == "info") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['TA1'];?></option>
			<option value="warning" <?php if ($CFG['TOP_ALERT_TYPE'] == "warning") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['TA2'];?></option>
			<option value="danger" <?php if ($CFG['TOP_ALERT_TYPE'] == "danger") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['TA3'];?></option>
			<option value="success" <?php if ($CFG['TOP_ALERT_TYPE'] == "success") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['TA4'];?></option>
			</select>

			<?php foreach ($languages as $key => $name) {

            $uns = unserialize($CFG["TOP_ALERT_MSG"]);
            if (false !== $uns && is_array($uns) && count($uns) > 0 && isset($uns[$key])) {
                $alert = $uns[$key];
            }

            ?>
				<div class="modal fade" id="seo_<?=$key;?>" tabindex="-1" role="dialog">
				  <div class="modal-dialog">
					<div class="modal-content">
					  <div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title"><?=$l['TOPALERT'];?>: <?=$name;?></h4>
					  </div>
					  <div class="modal-body">
						<input type="text" class="form-control" name="top_alert_msg[<?=$key;?>]" placeholder="<?=$l['TOPALERTP'];?>" value="<?=isset($alert) ? htmlentities($alert) : "";?>">
						<p class="help-block"><?=$l['TOPALERTH'];?></p>
					  </div>
					</div>
				  </div>
				</div>

				<a href="#" data-toggle="modal" data-target="#seo_<?=$key;?>" class="btn btn-default"><?=$name;?></a>&nbsp;
			<?php $keywords = $desc = "";
        }?>
		</div>

	  	<div class="checkbox">
		<label>
		<input type="checkbox" name="maintenance" class="mtn_chk" value="1" <?php if ($CFG['MAINTENANCE'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['MAINTENANCE'];?>
		<script>
		$(".mtn_chk").change(function() {
	if($(".mtn_chk").is(":checked"))
		$("#mtn_btn").show();
	else
		$("#mtn_btn").hide();
});
$(".adc_chk").change(function() {
	if($(".adc_chk").is(":checked"))
		$("#adc_btn").show();
	else
		$("#adc_btn").hide();
});
</script>
		</label><span id="mtn_btn"<?php if ($CFG['MAINTENANCE'] != 1) {?> style="display: none;"<?php }?>><?php foreach ($languages as $key => $name) {
            $alert = "";
            $uns = unserialize($CFG["MAINTENANCE_MSG"]);
            if (false !== $uns && is_array($uns) && count($uns) > 0 && isset($uns[$key])) {
                $alert = $uns[$key];
            }

            ?>
				<div class="modal fade" id="maintenance_<?=$key;?>" tabindex="-1" role="dialog">
				  <div class="modal-dialog">
					<div class="modal-content">
					  <div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title"><?=$l['MMTXT'];?>: <?=$name;?></h4>
					  </div>
					  <div class="modal-body">
						<textarea class="form-control summernote" name="maintenance_msg[<?=$key;?>]" style="height: 150px; resize: none;" placeholder="<?=$l['MMTXTP'];?>"><?=isset($alert) ? nl2br($alert) : "";?></textarea>
						<p class="help-block"><?=$l['MMTXTH'];?></p>
					  </div>
					</div>
				  </div>
				</div>

				<a href="#" data-toggle="modal" data-target="#maintenance_<?=$key;?>" class="btn btn-default btn-xs"><?=$name;?></a>&nbsp;
			<?php
}?></span>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="cookie_accept" value="1" <?php if ($CFG['COOKIE_ACCEPT'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['COOKIEHINT'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="trim_whitespace" value="1" <?php if ($CFG['TRIM_WHITESPACE'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['HTMLMIN'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="redirect_login" value="1" <?php if ($CFG['REDIRECT_LOGIN'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['LOGREDIR'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="domain_action_conf" value="1" <?php if ($CFG['DOMAIN_ACTION_CONF'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['DOMACTCON'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="customer_authcode" value="1" <?php if ($CFG['CUSTOMER_AUTHCODE'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['CUSTOMER_AUTHCODE'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="domain_log" value="1" <?php if ($CFG['DOMAIN_LOG'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['DOMLOGCHE'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="breakdown" value="1" <?php if ($CFG['BREAKDOWN'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['BREAKDOWN'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="hide_normal_server" value="1" <?php if ($CFG['HIDE_NORMAL_SERVER'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['HIDE_NORMAL_SERVER'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="wtip" value="1" <?php if ($CFG['WTIP'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['WTIP'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="allow_reg" value="1" <?php if ($CFG['ALLOW_REG'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['ALLOW_REG'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="user_confirmation" value="1" <?php if ($CFG['USER_CONFIRMATION'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['CONFNEW'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="mogelmail" value="1" <?php if ($CFG['MOGELMAIL'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['TRUGMAIL'];?>
		</label>
		</div>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="micropatches" value="1" <?php if ($CFG['MICROPATCHES'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['MICROPATCHES'];?>
		</label>
		</div>
		<?php if (!$brandingRequired) {?>
		<div class="checkbox">
		<label>
		<input type="checkbox" name="branding" value="1" <?php if ($CFG['BRANDING'] == 1) {
            echo "checked";
        }
            ?>> <?=$l['BRANDING'];?>
		</label>
		</div>
		<?php } else {?>
		<input type="hidden" name="branding" value="1">
		<?php }?>
		<div class="form-group">
          <button type="submit" name="change" class="btn btn-primary btn-block">
            <?=$l['SAVEANY'];?>
          </button>
        </div>
      </fieldset>
    </form>
  </div>
<?php } else if ($tab == "logos") {

        if (isset($_POST['which']) && isset($_FILES['img'])) {
            $f = $_FILES['img'];

            try {
                if ($_POST['which'] == "favicon") {
                    if (!in_array($f["type"], ["image/x-icon", "image/icon", "image/vnd.microsoft.icon"])) {
                        throw new Exception($l['UPL1']);
                    }

                    $ex = explode(".", $f["name"]);
                    $ext = strtolower(array_pop($ex));
                    if ($ext != "ico") {
                        throw new Exception($l['UPL1']);
                    }
                } else {
                    if (!in_array($f["type"], ["image/jpeg", "image/jpg", "image/png"])) {
                        throw new Exception($l['UPL2']);
                    }

                    $ex = explode(".", $f["name"]);
                    $ext = strtolower(array_pop($ex));
                    if (!in_array($ext, ["jpg", "png", "jpeg"])) {
                        throw new Exception($l['UPL2']);
                    }
                }

                if ($f["size"] > 500000) {
                    throw new Exception($l['UPL3']);
                }

                $to = [
                    "admin" => __DIR__ . "/../res/img/logo.png",
                    "pdf" => __DIR__ . "/../../themes/invoice-logo.jpg",
                    "touch" => __DIR__ . "/../../themes/apple-touch-icon.png",
                    "email" => __DIR__ . "/../../templates/email/logo.png",
                    "favicon" => __DIR__ . "/../../themes/favicon.ico",
                ];

                if (!array_key_exists($_POST['which'], $to)) {
                    throw new Exception($l['UPL4']);
                }

                if (!move_uploaded_file($f["tmp_name"], $to[$_POST['which']])) {
                    throw new Exception($l['UPL4']);
                }

                if ($_POST['which'] == "admin" && file_exists(__DIR__ . "/../res/img/logo.svg")) {
                    unlink(__DIR__ . "/../res/img/logo.svg");
                }
            } catch (Exception $ex) {
                $logerr = $ex->getMessage();
            }
        }

        if (!isset($logerr)) {?>
<div class="alert alert-info"><i class="fa fa-info-circle"></i> <?=$l['UPL5'];?></div>
<?php } else {?>
<div class="alert alert-danger"><?=$logerr;?></div>
<?php }?>

<form method="POST" enctype="multipart/form-data">
	<input type="file" name="img" style="display: none;" />
	<input type="hidden" name="which" value="" />
</form>

<div class="row">
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['LOGOADMIN'];?> <form method="POST" class="form-inline pull-right" style="display: inline;"><input type="text" name="admin_color" class="form-control input-sm" style="height: 20px; max-width: 100px;" value="<?=htmlentities($CFG['ADMIN_COLOR']);?>" placeholder="<?=$l['ADMIN_COLOR'];?>"> <input type="submit" name="change" class="btn btn-xs btn-primary" style="height: 20px;" value="<?=$lang['GENERAL']['SAVE'];?>"></form></div>
			<div class="panel-body">
				<img src="res/img/logo.png?ver=<?=time();?>" class="logo_click" data-which="admin">
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['LOGOPDF'];?></div>
			<div class="panel-body">
				<img src="../themes/invoice-logo.jpg?ver=<?=time();?>" class="logo_click" data-which="pdf">
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['LOGOTOUCH'];?></div>
			<div class="panel-body">
				<img src="../themes/apple-touch-icon.png?ver=<?=time();?>" class="logo_click" data-which="touch">
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['LOGOFAV'];?></div>
			<div class="panel-body">
				<img src="../themes/favicon.ico?ver=<?=time();?>" class="logo_click" data-which="favicon">
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><?=$l['LOGOMAIL'];?></div>
			<div class="panel-body">
				<img src="../templates/email/logo.png?ver=<?=time();?>" class="logo_click" data-which="email">
			</div>
		</div>
	</div>
</div>

<style>
.logo_click {
	cursor: pointer;
}
</style>

<script>
$(".logo_click").click(function() {
	$("[name=which]").val($(this).data("which"));
    $("[name=img]").click();
});

$("[name=img]").change(function() {
    $(this).parent().submit();
});
</script>
<?php } else if ($tab == "pdf") {

        if (isset($_POST['pdf_color'])) {
            $color = $_POST['pdf_color'];

            if (empty($color)) {
                $color = "#aabbcc";
            } else {
                $color = ltrim($color, "#");

                if (strlen($color) != 6) {
                    $color = "aabbcc";
                } else {
                    for ($i = 0; $i <= 5; $i++) {
                        $c = substr($color, $i, 1);
                        if (!in_array($i, ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f"])) {
                            $color = "aabbcc";
                            break;
                        }
                    }
                }

                $color = "#$color";
            }

            $db->query("UPDATE `settings` SET `value` = '" . $db->real_escape_string($color) . "' WHERE `key` = 'pdf_color' LIMIT 1");
            $CFG['PDF_COLOR'] = $color;
        }

        ?>
<form method="POST">
	<div class="form-group">
		<label><?=$l['PDFCOLOR'];?></label>
		<input type="text" name="pdf_color" value="<?=htmlentities($CFG['PDF_COLOR']);?>" placeholder="#aabbcc" class="form-control" />
	</div>

	<div class="form-group">
		<label><?=$l['PDFADDRESS'];?></label>
		<textarea name="pdf_address" placeholder="<?=$l['PDFADDRESSP'];?>" class="form-control" style="height: 300px; resize: vertical;"><?=htmlentities($CFG['PDF_ADDRESS']);?></textarea>
	</div>

	<div class="form-group">
		<label><?=$l['PDFRECIPIENT'];?></label>
		<textarea name="pdf_recipient" placeholder="<?=$l['PDFADDRESSP'];?>" class="form-control" style="height: 150px; resize: vertical;"><?=htmlentities($CFG['PDF_RECIPIENT']);?></textarea>
	</div>

	<div class="form-group">
		<label><?=$l['PDFSENDER'];?></label>
		<input type="text" name="pdf_sender" value="<?=htmlentities($CFG['PDF_SENDER']);?>" placeholder="<?=$l['PDFSENDERP'];?>" class="form-control" />
	</div>

	<div class="form-group">
		<label><?=$l['PDFBANK'];?></label>
		<input type="text" name="pdf_bank" value="<?=htmlentities($CFG['PDF_BANK']);?>" placeholder="<?=$l['PDFBANKP'];?>" class="form-control" />
		<p class="help-block"><?=$l['SEPWITHCOM'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['PDFIBAN'];?></label>
		<input type="text" name="pdf_iban" value="<?=htmlentities($CFG['PDF_IBAN']);?>" placeholder="DE12 3456 7890 0123 3456 78" class="form-control" />
		<p class="help-block"><?=$l['SEPWITHCOM'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['PDFBIC'];?></label>
		<input type="text" name="pdf_bic" value="<?=htmlentities($CFG['PDF_BIC']);?>" placeholder="MUSTDEDOXXX" class="form-control" />
		<p class="help-block"><?=$l['SEPWITHCOM'];?></p>
	</div>

	<div class="form-group">
		<button type="submit" name="change" class="btn btn-primary btn-block">
		<?=$l['SAVEANY'];?>
		</button>
	</div>
</form>
<?php } else if ($tab == "security") {
        $additionalJS = '
        $("#captcha_type").change(function() {
            if($("#captcha_type").val() == "reCaptcha" || $("#captcha_type").val() == "reCaptchaInvisible")
                $("#recaptcha_keys").show();
            else
                $("#recaptcha_keys").hide();
        });';
        ?>
<div>
  <form accept-charset="UTF-8" role="form" id="login-form" method="post">

      <fieldset>
      	<div class="form-group">
			<label><?=$l['CAPTYPE'];?></label>
			<?php
$availableCaptchas = $captcha->getAvailable();
        $captchaNames = array("noneCaptcha" => $l['CT0'], "calcCaptcha" => $l['CT1'], "reCaptcha" => $l['CT2'], "reCaptchaInvisible" => $l['CT3']);
        if (!in_array($CFG['CAPTCHA_TYPE'], $availableCaptchas)) {
            $CFG['CAPTCHA_TYPE'] = $captcha->getDefault();
        }

        ?>

			<select name="captcha_type" class="form-control" id="captcha_type">
				<?php foreach ($availableCaptchas as $key) {?>
				<option value="<?=$key;?>" <?php if ($CFG['CAPTCHA_TYPE'] == $key) {
            echo "selected=\"selected\"";
        }
            ?>><?=isset($captchaNames[$key]) ? $captchaNames[$key] : $key;?></option>
				<?php }?>
			</select>
		</div>

		<div class="form-group" id="recaptcha_keys"<?php if ($CFG['CAPTCHA_TYPE'] != 'reCaptcha' && $CFG['CAPTCHA_TYPE'] != 'reCaptchaInvisible') {?> style="display: none;"<?php }?>>
			<label><?=$l['RECKEY'];?></label>

			<div class="row">
				<div class="col-md-6">
					<input type="text" name="recaptcha_public" placeholder="<?=$l['RECPU'];?>" value="<?=trim($CFG['RECAPTCHA_PUBLIC']);?>" class="form-control">
				</div>

				<div class="col-md-6">
					<input type="text" name="recaptcha_private" placeholder="<?=$l['RECPR'];?>" value="<?=trim($CFG['RECAPTCHA_PRIVATE']);?>" class="form-control">
				</div>
			</div>

			<p class="help-block"><?=$l['RECLI'];?></p>
		</div>

		<div class="form-group">
	<label><?=$l['SMSVERIFY'];?></label>

	<?php
$prov = SMSHandler::getDriver();

        if (!$prov) {
            ?>
	<br /><?=$l['SMSVERIFY_NOPROV'];?>
	<?php } else {?>
	<select name="sms_verify" class="form-control">
		<option value=""><?=$l['DEACTIVATED'];?></option>
		<?php foreach ($prov->getTypes() as $k => $v) {?>
		<option value="<?=$k;?>"<?=$k == $CFG['SMS_VERIFY'] ? ' selected=""' : '';?>><?=$v;?></option>
		<?php }?>
	</select>
	<?php }?>
</div>

<?php if ($prov) {?>
<div class="checkbox" id="sms_verify_cb" style="display: none; margin-top: -10px;">
	<label>
		<input type="checkbox" name="sms_verify_orders" value="1"<?=$CFG['SMS_VERIFY_ORDERS'] ? ' checked=""' : '';?>>
		<?=$l['SMSVERIFY_ORDERS'];?>
	</label>
</div>

<script>
function svocb() {
	if ($("[name=sms_verify]").val()) {
		$("#sms_verify_cb").show();
	} else {
		$("#sms_verify_cb").hide();
		$("[name=sms_verify_orders]").prop("checked", false);
	}
}

$(document).ready(function() {
	svocb();
	$("[name=sms_verify]").change(svocb);
});
</script>
<?php }?>

		<div class="form-group">
			<label><?=$l['HASHMETHOD'];?></label>
			<select name="hash_method" class="form-control">
			<option value="plain" <?php if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['PLAINTEXT'];?></option>
			<option value="md5" <?php if ($CFG['HASH_METHOD'] == "md5") {
            echo "selected=\"selected\"";
        }
        ?>>MD5</option>
			<option value="sha1" <?php if ($CFG['HASH_METHOD'] == "sha1") {
            echo "selected=\"selected\"";
        }
        ?>>SHA1</option>
			<option value="sha256" <?php if ($CFG['HASH_METHOD'] == "sha256") {
            echo "selected=\"selected\"";
        }
        ?>>SHA256</option>
			<option value="sha512" <?php if ($CFG['HASH_METHOD'] == "sha512") {
            echo "selected=\"selected\"";
        }
        ?>>SHA512</option>
			<option value="sha512salt" <?php if ($CFG['HASH_METHOD'] == "sha512salt") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['SHA512SALT'];?></option>
			</select>
			<p class="help-block"><?=$l['HASHMETHODH'];?></p>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="clientside_hashing" value="1" <?php if ($CFG['CLIENTSIDE_HASHING'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['CSHASH'];?>
				</label>
			</div>
		</div>

		<div class="form-group">
			<label><?=$l['HASHMETHOD2'];?></label>
			<select name="hash_method_admin" class="form-control">
			<option value="plain" <?php if ($CFG['HASH_METHOD_ADMIN'] == "plain") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['PLAINTEXT'];?></option>
			<option value="md5" <?php if ($CFG['HASH_METHOD_ADMIN'] == "md5") {
            echo "selected=\"selected\"";
        }
        ?>>MD5</option>
			<option value="sha1" <?php if ($CFG['HASH_METHOD_ADMIN'] == "sha1") {
            echo "selected=\"selected\"";
        }
        ?>>SHA1</option>
			<option value="sha256" <?php if ($CFG['HASH_METHOD_ADMIN'] == "sha256") {
            echo "selected=\"selected\"";
        }
        ?>>SHA256</option>
			<option value="sha512" <?php if ($CFG['HASH_METHOD_ADMIN'] == "sha512") {
            echo "selected=\"selected\"";
        }
        ?>>SHA512</option>
			<option value="sha512salt" <?php if ($CFG['HASH_METHOD_ADMIN'] == "sha512salt") {
            echo "selected=\"selected\"";
        }
        ?>><?=$l['SHA512SALT'];?></option>
			</select>
			<p class="help-block" style="text-align:justify;"><?=$l['HASHMETHOD2H'];?></p>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="clientside_hashing_admin" value="1" <?php if ($CFG['CLIENTSIDE_HASHING_ADMIN'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['CSHASH'];?>
				</label>
			</div>
		</div>

		<div class="form-group">
			<label><?=$l['CSRFDIS'];?></label>
			<input type="text" value="<?=isset($_POST['csrf_disabled']) ? $_POST['csrf_disabled'] : $CFG['CSRF_DISABLED'];?>" placeholder="<?=$l['CSRFDISP'];?>" class="form-control" name="csrf_disabled" />
			<p class="help-block" style="text-align: justify;"><?=$l['CSRFDISH'];?></p>
 		</div>

		 <div class="form-group">
			<label><?=$l['IP_HEADER'];?></label>
			<input type="text" value="<?=isset($_POST['ip_header']) ? $_POST['ip_header'] : $CFG['IP_HEADER'];?>" placeholder="REMOTE_ADDR" class="form-control" name="ip_header" />
			<p class="help-block" style="text-align: justify;"><?=$l['IP_HEADERH1'];?>: <?=ip();?><br /><?=$l['IP_HEADERH2'];?>: <?=$_SERVER['REMOTE_ADDR'];?></p>
 		</div>

		<?php
if (!$ari->otpCheck()) {?>
		<div class="form-group">
			<label><?=$l['ADMINWHITE'];?></label>
			<input type="text" value="<?=isset($_POST['admin_whitelist']) ? $_POST['admin_whitelist'] : (unserialize($CFG['ADMIN_WHITELIST']) !== false ? implode(", ", unserialize($CFG['ADMIN_WHITELIST'])) : "");?>" placeholder="<?=$l['ADMINWHITEP'];?>" class="form-control" name="admin_whitelist" />
			<p class="help-block" style="text-align: justify;"><?=$l['ADMINWHITEH'];?></p>
 		</div>

 		<div class="form-group">
			<label><?=$l['AWOTP'];?></label>
			<input type="text" value="<?=$CFG['ADMIN_WHITELIST_PW'];?>" class="form-control" readonly />
			<p class="help-block" style="text-align: justify;"><?=$l['AWOTPH'];?></p>
 		</div>
 		<?php
} else {
            echo "<div class='alert alert-info'>{$l['AWOTPF']}</div>";
        }?>

		<div class="checkbox">
		<label>
		<input type="checkbox" name="explicit_ssl" value="1" <?php if ($CFG['EXPLICIT_SSL'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['EXSSL'];?>
		</label>
		</div>

          <div class="checkbox">
              <label>
                  <input type="checkbox" name="password_history" value="1" <?php if ($CFG['PASSWORD_HISTORY'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['PWHIS'];?>
                  <p style="margin-bottom: 0" class="help-block"><?=$l['PWHISH'];?></p>
              </label>
          </div>

		<div class="checkbox">
		<label>
		<input type="checkbox" name="hsts" value="1" <?php if ($CFG['HSTS'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['HSTS'];?>
		<p style="margin-bottom: 0" class="help-block"><?=$l['HSTSH'];?></p>
		</label>
		</div>



		<div class="checkbox">
		<label>
		<input type="checkbox" name="block_proxy" value="1" <?php if ($CFG['BLOCK_PROXY'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['BLOCKPROXY'];?>
		<p class="help-block"><?=$l['BLOCKPROXYH'];?></p>
		</label>
		</div>

		<div class="form-group">
          <button type="submit" name="change" class="btn btn-primary btn-block">
            <?=$l['SAVEANY'];?>
          </button>
        </div>
      </fieldset>
    </form>
  </div>
<?php } else if ($tab == "search") {
        $hidden = explode(",", $CFG['SEARCH_HIDDEN']);
        ?>
<div>
	<form accept-charset="UTF-8" role="form" method="post">

		<fieldset>
			<div class="form-group">
				<label><?=$l['HIDDENCATS'];?></label>
				<select name="hidden_cats[]" multiple class="form-control">
					<?php
$sql = $db->query("SELECT ID, name FROM product_categories");
        $cats = array();
        while ($cat = $sql->fetch_object()) {
            $cats[$cat->ID] = unserialize($cat->name)[$CFG['LANG']];
        }

        natcasesort($cats);
        foreach ($cats as $id => $name) {
            if (in_array("cat" . $id, $hidden)) {
                echo "<option value=\"$id\" selected=\"selected\">$name</option>";
            } else {
                echo "<option value=\"$id\">$name</option>";
            }

        }
        ?>
				</select>
				<p class="help-block"><?=$l['MULTIPLECHOICE'];?></p>
			</div>

			<div class="form-group">
				<label><?=$l['HIDDENARTS'];?></label>
				<select name="hidden_arts[]" multiple class="form-control">
					<?php
$sql = $db->query("SELECT ID, name FROM products");
        $arts = array();
        while ($art = $sql->fetch_object()) {
            $arts[$art->ID] = unserialize($art->name)[$CFG['LANG']];
        }

        natcasesort($arts);
        foreach ($arts as $id => $name) {
            if (in_array("art" . $id, $hidden)) {
                echo "<option value=\"$id\" selected=\"selected\">$name</option>";
            } else {
                echo "<option value=\"$id\">$name</option>";
            }

        }
        ?>
				</select>
				<p class="help-block"><?=$l['MULTIPLECHOICE'];?></p>
			</div>

			<div class="form-group">
				<label><?=$l['HIDDENPAGES'];?></label>
				<select name="hidden_pages[]" multiple class="form-control">
					<?php
$sql = $db->query("SELECT ID, title FROM cms_pages ORDER BY title ASC");
        $pages = array();
        while ($row = $sql->fetch_object()) {
            $pages[$row->ID] = unserialize($row->title)[$CFG['LANG']];
        }

        asort($pages);
        foreach ($pages as $id => $name) {
            if (in_array("page" . $id, $hidden)) {
                echo "<option value=\"{$id}\" selected=\"selected\">{$name}</option>";
            } else {
                echo "<option value=\"{$id}\">{$name}</option>";
            }

        }
        ?>
				</select>
				<p class="help-block"><?=$l['MULTIPLECHOICE'];?></p>
			</div>

			<div class="form-group">
				<input type="hidden" name="action" value="save_search" />
				<button type="submit" name="change" class="btn btn-primary btn-block">
					<?=$l['SAVEANY'];?>
				</button>
			</div>
		</fieldset>
	</form>
</div>
<?php } else if ($tab == "analytics") {?>
<div>
  <form accept-charset="UTF-8" role="form" method="post">

      <fieldset>
		<div class="form-group">
			<label><?=$l['TRACKING'];?></label>
			<textarea name="tracking" placeholder='<script type="text/javascript">...' class="form-control" style="height:300px; resize:none;"><?=$CFG['TRACKING'];?></textarea>
			<p class="help-block"><?=$l['TRACKINGH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['MATOMO'];?></label>
			<div class="checkbox" style="margin-top: -5px;"><label><input type="checkbox" name="piwik_ecommerce" value="1"<?=$CFG['PIWIK_ECOMMERCE'] ? " checked='checked'" : "";?>/> <?=$l['MATOMOD'];?></label></div>
			<p class="help-block" style="margin-top: -10px;"><?=$l['MATOMOH'];?></p>
		</div>

		<div class="form-group">
          <button type="submit" name="change" class="btn btn-primary btn-block">
            <?=$l['SAVEANY'];?>
          </button>
        </div>
      </fieldset>
    </form>
  </div>
<?php } else if ($tab == "local") {

        if (isset($_GET['default']) && in_array($_GET['default'], Language::getClientLanguages())) {
            $db->query("UPDATE `settings` SET `value` = '" . $db->real_escape_string($_GET['default']) . "' WHERE `key` = 'lang' LIMIT 1");
            if ($db->affected_rows == 1) {
                $CFG['LANG'] = $raw_cfg['LANG'] = $_GET['default'];
                echo "<div class='alert alert-success'>{$l['CHANGEDTDL']}</div>";
                alog("settings", "default_language_changed", $_GET['default']);
            }
        }

        if (isset($_POST['save'])) {
            if (is_array($_POST['timezone']) && is_array($_POST['number_format']) && is_array($_POST['date_format'])) {
                $update = array("timezone", "number_format", "date_format");
                foreach ($update as $field) {
                    $value = $db->real_escape_string(serialize($_POST[$field]));
                    $db->query("UPDATE `settings` SET `value` = '$value' WHERE `key` = '$field' LIMIT 1");
                    $CFG[strtoupper($field)] = $raw_cfg[strtoupper($field)] = serialize($_POST[$field]);
                }
                alog("settings", "localization_settings_changed");
                echo "<div class='alert alert-success'>{$l['CHANGEDTCM']}</div>";
            }
        }

        if (isset($_GET['enable']) && in_array($_GET['enable'], Language::getLanguageFiles())) {
            $db->query("UPDATE languages SET active = 1 WHERE language = '" . $db->real_escape_string($_GET['enable']) . "'");
            alog("settings", "language_enabled", $_GET['enable']);
            echo "<div class='alert alert-success'>{$l['LANGENABLED']}</div>";
        }

        if (isset($_GET['disable']) && in_array($_GET['disable'], Language::getClientLanguages())) {
            $db->query("UPDATE languages SET active = 0 WHERE language = '" . $db->real_escape_string($_GET['disable']) . "'");
            alog("settings", "language_disabled", $_GET['disable']);
            echo "<div class='alert alert-success'>{$l['LANGDISABLED']}</div>";
        }

        ?>
<form method="POST">
	<div class="table-responsive"><table class="table table-bordered table-striped">
		<tr>
			<th><?=$l['LANGUAGE'];?></th>
			<th><?=$l['TIMEZONE'];?></th>
			<th><?=$l['NUMBERFORMAT'];?></th>
			<th><?=$l['DATEFORMAT'];?></th>
			<th width="30px"></th>
		</tr>

		<?php asort($languages);foreach ($languages as $k => $v) {
            $oldLang = $lang;
            require __DIR__ . "/../../languages/$k.php";
            $name = $lang['MANAGEMENT_NAME'];
            $lang = $oldLang;
            ?>
		<tr>
			<td><?=$raw_cfg['LANG'] == $k ? "<b>$name</b>" : $name;?><?php if ($raw_cfg['LANG'] != $k && in_array($k, Language::getClientLanguages())) {?> <a href="./?p=settings&tab=local&default=<?=$k;?>"><i class="fa fa-star"></i></a><?php }?></td>
			<td>
				<select name="timezone[<?=$k;?>]" class="form-control">
		            <?php foreach ($t as $z) {?>
		            <option <?php if (unserialize($raw_cfg['TIMEZONE'])[$k] == $z) {
                echo "selected=\"selected\"";
            }
                ?>><?=$z;?></option>
		            <?php }?>
				</select>
			</td>
			<td>
				<select name="number_format[<?=$k;?>]" class="form-control">
					<option value="de" <?php if (unserialize($raw_cfg['NUMBER_FORMAT'])[$k] == "de") {
                echo "selected=\"selected\"";
            }
            ?>>1.234,56</option>
					<option value="de2" <?php if (unserialize($raw_cfg['NUMBER_FORMAT'])[$k] == "de2") {
                echo "selected=\"selected\"";
            }
            ?>>1234,56</option>
					<option value="us" <?php if (unserialize($raw_cfg['NUMBER_FORMAT'])[$k] == "us") {
                echo "selected=\"selected\"";
            }
            ?>>1,234.56</option>
					<option value="us2" <?php if (unserialize($raw_cfg['NUMBER_FORMAT'])[$k] == "us2") {
                echo "selected=\"selected\"";
            }
            ?>>1234.56</option>
				</select>
			</td>
			<td>
				<select name="date_format[<?=$k;?>]" class="form-control">
					<option value="DD.MM.YYYY" <?php if (unserialize($raw_cfg['DATE_FORMAT'])[$k] == "DD.MM.YYYY") {
                echo "selected=\"selected\"";
            }
            ?>><?=date("d.m.Y");?></option>
					<option value="DD/MM/YYYY" <?php if (unserialize($raw_cfg['DATE_FORMAT'])[$k] == "DD/MM/YYYY") {
                echo "selected=\"selected\"";
            }
            ?>><?=date("d/m/Y");?></option>
					<option value="DD-MM-YYYY" <?php if (unserialize($raw_cfg['DATE_FORMAT'])[$k] == "DD-MM-YYYY") {
                echo "selected=\"selected\"";
            }
            ?>><?=date("d-m-Y");?></option>
					<option value="MM/DD/YYYY" <?php if (unserialize($raw_cfg['DATE_FORMAT'])[$k] == "MM/DD/YYYY") {
                echo "selected=\"selected\"";
            }
            ?>><?=date("m/d/Y");?></option>
					<option value="YYYY-MM-DD" <?php if (unserialize($raw_cfg['DATE_FORMAT'])[$k] == "YYYY-MM-DD") {
                echo "selected=\"selected\"";
            }
            ?>><?=date("Y-m-d");?></option>
					<option value="YYYY/MM/DD" <?php if (unserialize($raw_cfg['DATE_FORMAT'])[$k] == "YYYY/MM/DD") {
                echo "selected=\"selected\"";
            }
            ?>><?=date("Y/m/d");?></option>
				</select>
			</td>
			<td style="text-align: center;">
				<?php if ($raw_cfg['LANG'] != $k) {?>
				<?php if (in_array($k, Language::getClientLanguages())) {?>
				<a href="?p=settings&tab=local&disable=<?=$k;?>"><i class="fa fa-pause fa-fw"></i></a>
				<?php } else {?>
				<a href="?p=settings&tab=local&enable=<?=$k;?>"><i class="fa fa-play fa-fw"></i></a>
				<?php }?>
				<?php }?>
			</td>
		</tr>
		<?php }?>
	</table></div>

	<button type="submit" name="save" class="btn btn-primary btn-block">
	    <?=$l['SAVEANY'];?>
	</button>
</form>
<?php } else if ($tab == "invoices") {?>
<div>
  <form accept-charset="UTF-8" role="form" id="login-form" method="post">

      <fieldset>
      	<div class="form-group">
			<label><?=$l['CASHBOX'];?></label>
			<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
			    <label>
			      <input type="checkbox" name="cashbox_active" value="1" <?php if ($CFG['CASHBOX_ACTIVE'] == 1) {
        echo "checked";
    }
        ?>> <?=$l['CASHBOXD'];?>
			    </label>
			</div>
			<p class="help-block"><?=$l['CASHBOXH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['DEFLIMIT'];?></label>
			<input type="text" class="form-control" name="postpaid_def" value="<?=$nfo->format(doubleval($CFG['POSTPAID_DEF']));?>" placeholder="<?=$nfo->placeholder();?>" />
			<p class="help-block"><?=$l['DEFLIMITH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['CBPREFIX'];?></label>
			<input type="text" class="form-control" name="cashbox_prefix" value="<?=$CFG['CASHBOX_PREFIX'];?>" placeholder="<?=$l['CBPREFIXP'];?>" />
			<p class="help-block"><?=$l['CBPREFIXH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['INVPREFIX'];?></label>
			<input type="text" class="form-control" name="invoice_prefix" value="<?=$CFG['INVOICE_PREFIX'];?>" placeholder="<?=$l['INVPREFIXP'];?>" />
			<p class="help-block"><?=$l['INVPREFIXH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['MIN_INVLEN'];?></label>
			<input type="text" class="form-control" name="min_invlen" value="<?=intval($CFG['MIN_INVLEN']);?>" placeholder="6" />
		</div>

		<div class="form-group">
			<label><?=$l['INVDIST'];?></label>
			<div class="row">
				<div class="col-md-6">
					<input type="text" class="form-control" name="invoice_dist_min" value="<?=max(1, intval($CFG['INVOICE_DIST_MIN']));?>" placeholder="1" />
				</div>
				<div class="col-md-6">
					<input type="text" class="form-control" name="invoice_dist_max" value="<?=max(1, intval($CFG['INVOICE_DIST_MAX']));?>" placeholder="1" />
				</div>
			</div>
		</div>

		<div class="form-group">
			<label><?=$l['INVDUE'];?></label>
			<input type="text" class="form-control" name="invoice_duedate" value="<?=$CFG['INVOICE_DUEDATE'];?>" placeholder="14" />
			<p class="help-block"><?=$l['INVDUEH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['INVOICE_ADVANCE'];?></label>
			<input type="text" class="form-control" name="invoice_advance" value="<?=$CFG['INVOICE_ADVANCE'];?>" placeholder="0" />
			<p class="help-block"><?=$l['INVOICE_ADVANCEH'];?></p>
		</div>

		<?php if (SepaDirectDebit::active()) {?>
		<div class="form-group">
			<label><?=$l['SEPALIMIT'];?></label>
			<input type="text" class="form-control" name="sepa_limit" value="<?=$nfo->format($CFG['SEPA_LIMIT']);?>" placeholder="<?=$nfo->placeholder();?>" />
			<p class="help-block"><?=$l['SEPALIMITH'];?></p>
		</div>
		<?php }?>

		<div class="form-group">
			<label><?=$l['NOINV'];?></label>
			<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
			    <label>
			      <input type="checkbox" name="no_invoicing" value="1" <?php if ($CFG['NO_INVOICING'] == 1) {
            echo "checked";
        }
        ?>> <?=$l['NOINVD'];?>
			    </label>
			</div>
			<p class="help-block"><?=$l['NOINVH'];?></p>
		</div>

		<div class="form-group">
          <button type="submit" name="change" class="btn btn-primary btn-block">
            <?=$l['SAVEANY'];?>
          </button>
        </div>
      </fieldset>
    </form>
  </div>
<?php } else if ($tab == "reminders") {
        if (isset($_GET['edit']) && $db->query("SELECT 1 FROM reminders WHERE ID = " . intval($_GET['edit']))->num_rows == 1) {
            if (isset($_POST['name'])) {
                try {
                    $arr = array("name", "days1", "days2", "color", "bold", "countries", "b2c", "b2c_item", "b2c_percent", "b2c_absolute", "b2c_mail", "b2c_admin_mail", "b2b", "b2b_item", "b2b_percent", "b2b_absolute", "b2b_mail", "b2b_admin_mail", "b2b_letter", "b2b_letter_send", "b2b_letter_text", "b2c_letter", "b2c_letter_send", "b2c_letter_text");
                    foreach ($arr as $k) {
                        if (isset($_POST[$k])) {
                            $$k = $_POST[$k];
                        }
                    }

                    if (empty($name)) {
                        throw new Exception($l['REMERR1']);
                    }

                    if ($db->query("SELECT 1 FROM reminders WHERE name = '" . $db->real_escape_string($name) . "' AND ID != " . intval($_GET['edit']))->num_rows > 0) {
                        throw new Exception($l['REMERR2']);
                    }

                    if (!isset($days2) || trim($days2) == "" || !is_numeric($days2) || $days2 < 0) {
                        throw new Exception($l['REMERR3']);
                    }

                    if (empty($days1) || ($days1 != "+" && $days1 != "-")) {
                        throw new Exception($l['REMERR4']);
                    }

                    if (!isset($color) || !in_array($color, array("", "orange", "red", "darkred"))) {
                        throw new Exception($l['REMERR5']);
                    }

                    if (!isset($bold) || !in_array($bold, array("0", "1"))) {
                        throw new Exception($l['REMERR6']);
                    }

                    if (empty($countries) || !is_array($countries)) {
                        throw new Exception($l['REMERR7']);
                    }

                    foreach ($countries as $k => $c) {
                        if ($db->query("SELECT 1 FROM client_countries WHERE ID = " . intval($c))->num_rows != 1) {
                            unset($countries[$k]);
                        }
                    }

                    if (count($countries) == 0) {
                        throw new Exception($l['REMERR7']);
                    }

                    if (isset($b2c) && $b2c == "1") {
                        $b2c = true;

                        if (empty($b2c_item)) {
                            $b2c_percent = 0;
                            $b2c_absolute = 0;
                        }

                        if (!isset($b2c_percent) || doubleval($nfo->phpize($b2c_percent)) != $nfo->phpize($b2c_percent)) {
                            throw new Exception($l['REMERR8']);
                        }

                        if (!isset($b2c_absolute) || doubleval($nfo->phpize($b2c_absolute)) != $nfo->phpize($b2c_absolute)) {
                            throw new Exception($l['REMERR9']);
                        }

                        if (!isset($b2c_mail) || (!empty($b2c_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2c_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR10']);
                        }

                        if (!isset($b2c_admin_mail) || (!empty($b2c_admin_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2c_admin_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR11']);
                        }

                    }

                    if (isset($b2b) && $b2b == "1") {
                        $b2b = true;

                        if (empty($b2b_item)) {
                            $b2b_percent = 0;
                            $b2b_absolute = 0;
                        }

                        if (!isset($b2b_percent) || doubleval($nfo->phpize($b2b_percent)) != $nfo->phpize($b2b_percent)) {
                            throw new Exception($l['REMERR12']);
                        }

                        if (!isset($b2b_absolute) || doubleval($nfo->phpize($b2b_absolute)) != $nfo->phpize($b2b_absolute)) {
                            throw new Exception($l['REMERR13']);
                        }

                        if (!isset($b2b_mail) || (!empty($b2b_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2b_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR14']);
                        }

                        if (!isset($b2b_admin_mail) || (!empty($b2b_admin_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2b_admin_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR15']);
                        }

                    }

                    if ($b2c !== true && $b2b !== true) {
                        throw new Exception($l['REMERR16']);
                    }

                    $stmt = $db->prepare("UPDATE reminders SET `days` = ?, `name` = ?, `color` = ?, `bold` = ?, `countries` = ?, `b2c` = ?, `b2c_mail` = ?, `b2c_admin_mail` = ?, `b2c_item` = ?, `b2c_percent` = ?, `b2c_absolute` = ?, `b2b` = ?, `b2b_mail` = ?, `b2b_admin_mail` = ?, `b2b_item` = ?, `b2b_percent` = ?, `b2b_absolute` = ?, `b2b_letter` = ?, `b2b_letter_send` = ?, `b2b_letter_text` = ?, `b2c_letter` = ?, `b2c_letter_send` = ?, `b2c_letter_text` = ? WHERE `ID` = ?");

                    $stmt->bind_param("issisiiisddiiisddississi", $a = $days1 . $days2, $name, $color, $bold, $b = implode(",", $countries), $c = $b2c ? 1 : 0, $b2c_mail, $b2c_admin_mail, $b2c_item, $d = $nfo->phpize($b2c_percent), $e = $nfo->phpize($b2c_absolute), $f = $b2b ? 1 : 0, $b2b_mail, $b2b_admin_mail, $b2b_item, $g = $nfo->phpize($b2b_percent), $h = $nfo->phpize($b2b_absolute), $b2b_letter, $b2b_letter_send, $b2b_letter_text, $b2c_letter, $b2c_letter_send, $b2c_letter_text, $_GET['edit']);

                    $stmt->execute();

                    alog("settings", "reminder_level_saved", $name, $_GET['edit']);

                    echo '<div class="alert alert-success">' . $l['REMSAVED'] . '</div>';
                    unset($_POST);
                } catch (Exception $ex) {
                    echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $ex->getMessage() . '</div>';
                }
            }

            $i = $db->query("SELECT * FROM reminders WHERE ID = " . intval($_GET['edit']))->fetch_object();

            $arr = array("name", "days1", "days2", "color", "bold", "countries", "b2c", "b2c_item", "b2c_percent", "b2c_absolute", "b2c_mail", "b2c_admin_mail", "b2b", "b2b_item", "b2b_percent", "b2b_absolute", "b2b_mail", "b2b_admin_mail", "b2b_letter", "b2b_letter_send", "b2b_letter_text", "b2c_letter", "b2c_letter_send", "b2c_letter_text");
            foreach ($arr as $k) {
                if (!isset($_POST[$k])) {
                    if ($k == "days1") {
                        $_POST['days1'] = $i->days >= 0 ? "+" : "-";
                    } else if ($k == "days2") {
                        $_POST['days2'] = abs($i->days);
                    } else if ($k == "countries") {
                        $_POST[$k] = explode(",", $i->countries);
                    } else if (strpos($k, "percent") !== false || strpos($k, "absolute") !== false) {
                        $_POST[$k] = $nfo->format($i->$k);
                    } else {
                        $_POST[$k] = $i->$k;
                    }

                }
            }
            ?>
<form accept-charset="UTF-8" role="form" method="post">
  <fieldset>
	<div class="form-group">
		<label><?=$l['REMNAME'];?></label>
		<input type="text" class="form-control" name="name" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>" placeholder="<?=$l['REMNAMEP'];?>" />
	</div>

	<div class="form-group">
		<label><?=$l['REMTIME'];?></label>

		<div class="row">
			<div class="col-sm-3">
				<input type="numeric" class="form-control" name="days2" value="<?=isset($_POST['days2']) ? $_POST['days2'] : "";?>" placeholder="14" />
			</div>

			<div class="col-sm-9">
				<select name="days1" class="form-control">
					<option value="+"><?=$l['REMTIME1'];?></option>
					<option value="-"<?php if (isset($_POST['days1']) && $_POST['days1'] == "-") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMTIME2'];?></option>
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['REMDIS'];?></label>

		<div class="row">
			<div class="col-sm-9">
				<select name="color" class="form-control">
					<option value=""><?=$l['REMDIS0'];?></option>
					<option value="orange" style="color: orange;"<?php if (isset($_POST['color']) && $_POST['color'] == "orange") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS1'];?></option>
					<option value="red" style="color: red;"<?php if (isset($_POST['color']) && $_POST['color'] == "red") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS2'];?></option>
					<option value="darkred" style="color: darkred;"<?php if (isset($_POST['color']) && $_POST['color'] == "darkred") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS3'];?></option>
				</select>
			</div>

			<div class="col-sm-3">
				<select name="bold" class="form-control">
					<option value="0"><?=$l['REMDIS4'];?></option>
					<option value="1"<?php if (isset($_POST['bold']) && $_POST['bold'] == "1") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS5'];?></option>
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['COUNTRIES'];?></label>
		<select class="form-control" name="countries[]" multiple="multiple">
			<?php $sql = $db->query("SELECT * FROM client_countries ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {?>
			<option value="<?=$row->ID;?>"<?php if (isset($_POST['countries']) && is_array($_POST['countries']) && in_array($row->ID, $_POST['countries'])) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
			<?php }?>
		</select>
		<p class="help-block"><?=$l['MULTIPLECHOICE'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['REMB2C'];?></label>

		<script>
		function btc(state){
			var elements = document.getElementsByClassName('b2c');
			Array.prototype.forEach.call(elements, function(e){
				if(state) e.style.display = 'block';
				else e.style.display = 'none';
			})
		}
		</script>

		<div class="checkbox" style="margin-top: 0; padding-top: 0;"><label>
			<input type="checkbox" name="b2c" value="1"<?php if (!isset($_POST['name']) || (isset($_POST['b2c']) && $_POST['b2c'] == "1")) {
                echo ' checked="checked"';
            }
            ?> onchange="btc(this.checked);"> <?=$l['ACTIVATE'];?>
		</label></div>

		<div class="row b2c">
			<div class="col-sm-8">
				<input type="text" class="form-control" name="b2c_item" value="<?=isset($_POST['b2c_item']) ? $_POST['b2c_item'] : "";?>" placeholder="<?=$l['REMFEE'];?>" />
			</div>

			<div class="col-sm-2"><div class="input-group">
				<input type="text" class="form-control" name="b2c_percent" value="<?=isset($_POST['b2c_percent']) ? $_POST['b2c_percent'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<span class="input-group-addon">%</span>
			</div></div>

			<div class="col-sm-2"><div class="input-group">
				<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
				<input type="text" class="form-control" name="b2c_absolute" value="<?=isset($_POST['b2c_absolute']) ? $_POST['b2c_absolute'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
			</div></div>
		</div>

		<div class="row b2c" style="margin-top: 10px;">
			<div class="col-sm-6">
				<select name="b2c_mail" class="form-control">
					<option value=""><?=$l['REMNOCMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2c_mail']) && $_POST['b2c_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>

			<div class="col-sm-6">
				<select name="b2c_admin_mail" class="form-control">
					<option value=""><?=$l['REMNOAMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2c_admin_mail']) && $_POST['b2c_admin_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>
		</div>

		<?php if (LetterHandler::myDrivers()) {?>
		<div class="checkbox b2c"><label>
			<input type="checkbox" name="b2c_letter" value="1"<?php if ((!isset($_POST['name']) && $row->b2c_letter) || (isset($_POST['b2c_letter']) && $_POST['b2c_letter'] == "1")) {
                echo ' checked="checked"';
            }
                ?> onchange="btcl(this.checked);"> <?=$l['CREATELETTER'];?>
		</label></div>

		<textarea placeholder="<?=$l['LETTERTEXT'];?>" class="form-control b2cl b2c" style="resize: none; height: 180px;" name="b2c_letter_text"><?=isset($_POST['b2c_letter_text']) ? $_POST['b2c_letter_text'] : "";?></textarea>

		<select name="b2c_letter_send" class="form-control b2cl b2c" style="margin-top: 10px;">
			<option value=""><?=$l['REMDNSLA'];?></option>
			<?php foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {foreach ($drivObj->getTypes() as $code => $name) {$code = $drivKey . "#" . $code;
                    $name = $drivObj->getName() . " - " . $name;?>
			<option value="<?=$code;?>"<?=isset($_POST['b2c_letter_send']) && $_POST['b2c_letter_send'] === (string) $code ? ' selected="selected"' : "";?>><?=$name;?></option>
			<?php }}?>
		</select>
			<?php }?>

		<script>
		function btcl(state){
			var elements = document.getElementsByClassName('b2cl');
			Array.prototype.forEach.call(elements, function(e){
				if(state) e.style.display = 'block';
				else e.style.display = 'none';
			})
		}
		</script>

		<?php if (isset($_POST['name']) && (!isset($_POST['b2c_letter']) || $_POST['b2c_letter'] != "1")) {?>
		<style>
		.b2cl { display: none; }
		</style>
		<?php }?>

		<?php if (isset($_POST['name']) && (!isset($_POST['b2c']) || $_POST['b2c'] != "1")) {?>
		<style>
		.b2c { display: none; }
		</style>
		<?php }?>
	</div>

	<div class="form-group">
		<label><?=$l['REMB2B'];?></label>

		<script>
		function btb(state){
			var elements = document.getElementsByClassName('b2b');
			Array.prototype.forEach.call(elements, function(e){
				if(state) e.style.display = 'block';
				else e.style.display = 'none';
			})
		}
		</script>

		<?php if (isset($_POST['name']) && (!isset($_POST['b2b']) || $_POST['b2b'] != "1")) {?>
		<style>
		.b2b { display: none; }
		</style>
		<?php }?>

		<div class="checkbox" style="margin-top: 0; padding-top: 0;"><label>
			<input type="checkbox" name="b2b" value="1"<?php if (!isset($_POST['name']) || (isset($_POST['b2b']) && $_POST['b2b'] == "1")) {
                echo ' checked="checked"';
            }
            ?> onchange="btb(this.checked);"> <?=$l['ACTIVATE'];?>
		</label></div>

		<div class="row b2b">
			<div class="col-sm-8">
				<input type="text" class="form-control" name="b2b_item" value="<?=isset($_POST['b2b_item']) ? $_POST['b2b_item'] : "";?>" placeholder="<?=$l['REMFEE'];?>" />
			</div>

			<div class="col-sm-2"><div class="input-group">
				<input type="text" class="form-control" name="b2b_percent" value="<?=isset($_POST['b2b_percent']) ? $_POST['b2b_percent'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<span class="input-group-addon">%</span>
			</div></div>

			<div class="col-sm-2"><div class="input-group">
				<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
				<input type="text" class="form-control" name="b2b_absolute" value="<?=isset($_POST['b2b_absolute']) ? $_POST['b2b_absolute'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
			</div></div>
		</div>

		<div class="row b2b" style="margin-top: 10px;">
			<div class="col-sm-6">
				<select name="b2b_mail" class="form-control">
					<option value=""><?=$l['REMNOCMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2b_mail']) && $_POST['b2b_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>

			<div class="col-sm-6">
				<select name="b2b_admin_mail" class="form-control">
					<option value=""><?=$l['REMNOAMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2b_admin_mail']) && $_POST['b2b_admin_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>
		</div>

		<?php if (LetterHandler::myDrivers()) {?>
		<div class="checkbox b2b"><label>
			<input type="checkbox" name="b2b_letter" value="1"<?php if ((!isset($_POST['name']) && $row->b2b_letter) || (isset($_POST['b2b_letter']) && $_POST['b2b_letter'] == "1")) {
                echo ' checked="checked"';
            }
                ?> onchange="btbl(this.checked);"> <?=$l['CREATELETTER'];?>
		</label></div>

		<textarea placeholder="<?=$l['LETTERTEXT'];?>" class="form-control b2bl b2b" style="resize: none; height: 180px;" name="b2b_letter_text"><?=isset($_POST['b2b_letter_text']) ? $_POST['b2b_letter_text'] : "";?></textarea>

		<select name="b2b_letter_send" class="form-control b2bl b2b" style="margin-top: 10px;">
			<option value=""><?=$l['REMDNSLA'];?></option>
			<?php foreach (LetterHandler::myDrivers() as $drivKey => $drivObj) {foreach ($drivObj->getTypes() as $code => $name) {$code = $drivKey . "#" . $code;
                    $name = $drivObj->getName() . " - " . $name;?>
			<option value="<?=$code;?>"<?=isset($_POST['b2b_letter_send']) && $_POST['b2b_letter_send'] === (string) $code ? ' selected="selected"' : "";?>><?=$name;?></option>
			<?php }}?>
		</select>
			<?php }?>

		<script>
		function btbl(state){
			var elements = document.getElementsByClassName('b2bl');
			Array.prototype.forEach.call(elements, function(e){
				if(state) e.style.display = 'block';
				else e.style.display = 'none';
			})
		}
		</script>

		<?php if (isset($_POST['name']) && (!isset($_POST['b2b_letter']) || $_POST['b2b_letter'] != "1")) {?>
		<style>
		.b2bl { display: none; }
		</style>
		<?php }?>
	</div>

	<div class="form-group">
		<label><?=$l['REMMAILVAR'];?></label><br />
		<?=$l['REMMAILVARLIST'];?>
	</div>

	<div class="form-group">
      <button type="submit" class="btn btn-primary btn-block">
        <?=$l['REMSAVENOW'];?>
      </button>
    </div>
  </fieldset>
</form>
<?php
} else if (in_array("add", array_keys($_GET))) {
            if (isset($_POST['name'])) {
                try {
                    $arr = array("name", "days1", "days2", "color", "bold", "countries", "b2c", "b2c_item", "b2c_percent", "b2c_absolute", "b2c_mail", "b2c_admin_mail", "b2b", "b2b_item", "b2b_percent", "b2b_absolute", "b2b_mail", "b2b_admin_mail");
                    foreach ($arr as $k) {
                        if (isset($_POST[$k])) {
                            $$k = $_POST[$k];
                        }
                    }

                    if (empty($name)) {
                        throw new Exception($l['REMERR1']);
                    }

                    if ($db->query("SELECT 1 FROM reminders WHERE name = '" . $db->real_escape_string($name) . "'")->num_rows > 0) {
                        throw new Exception($l['REMERR2']);
                    }

                    if (!isset($days2) || trim($days2) == "" || !is_numeric($days2) || $days2 < 0) {
                        throw new Exception($l['REMERR3']);
                    }

                    if (empty($days1) || ($days1 != "+" && $days1 != "-")) {
                        throw new Exception($l['REMERR4']);
                    }

                    if (!isset($color) || !in_array($color, array("", "orange", "red", "darkred"))) {
                        throw new Exception($l['REMERR5']);
                    }

                    if (!isset($bold) || !in_array($bold, array("0", "1"))) {
                        throw new Exception($l['REMERR6']);
                    }

                    if (empty($countries) || !is_array($countries)) {
                        throw new Exception($l['REMERR7']);
                    }

                    foreach ($countries as $k => $c) {
                        if ($db->query("SELECT 1 FROM client_countries WHERE ID = " . intval($c))->num_rows != 1) {
                            unset($countries[$k]);
                        }
                    }

                    if (count($countries) == 0) {
                        throw new Exception($l['REMERR7']);
                    }

                    if (isset($b2c) && $b2c == "1") {
                        $b2c = true;

                        if (empty($b2c_item)) {
                            $b2c_percent = 0;
                            $b2c_absolute = 0;
                        }

                        if (!isset($b2c_percent) || doubleval($nfo->phpize($b2c_percent)) != $nfo->phpize($b2c_percent)) {
                            throw new Exception($l['REMERR8']);
                        }

                        if (!isset($b2c_absolute) || doubleval($nfo->phpize($b2c_absolute)) != $nfo->phpize($b2c_absolute)) {
                            throw new Exception($l['REMERR9']);
                        }

                        if (!isset($b2c_mail) || (!empty($b2c_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2c_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR10']);
                        }

                        if (!isset($b2c_admin_mail) || (!empty($b2c_admin_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2c_admin_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR11']);
                        }

                    }

                    if (isset($b2b) && $b2b == "1") {
                        $b2b = true;

                        if (empty($b2b_item)) {
                            $b2b_percent = 0;
                            $b2b_absolute = 0;
                        }

                        if (!isset($b2b_percent) || doubleval($nfo->phpize($b2b_percent)) != $nfo->phpize($b2b_percent)) {
                            throw new Exception($l['REMERR12']);
                        }

                        if (!isset($b2b_absolute) || doubleval($nfo->phpize($b2b_absolute)) != $nfo->phpize($b2b_absolute)) {
                            throw new Exception($l['REMERR13']);
                        }

                        if (!isset($b2b_mail) || (!empty($b2b_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2b_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR14']);
                        }

                        if (!isset($b2b_admin_mail) || (!empty($b2b_admin_mail) && $db->query("SELECT 1 FROM email_templates WHERE category = 'Eigene' AND ID = " . intval($b2b_admin_mail))->num_rows != 1)) {
                            throw new Exception($l['REMERR15']);
                        }

                    }

                    if ($b2c !== true && $b2b !== true) {
                        throw new Exception($l['REMERR16']);
                    }

                    $stmt = $db->prepare("INSERT INTO reminders (`days`, `name`, `color`, `bold`, `countries`, `b2c`, `b2c_mail`, `b2c_admin_mail`, `b2c_item`, `b2c_percent`, `b2c_absolute`, `b2b`, `b2b_mail`, `b2b_admin_mail`, `b2b_item`, `b2b_percent`, `b2b_absolute`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt->bind_param("issisiiisddiiisdd", $a = $days1 . $days2, $name, $color, $bold, $b = implode(",", $countries), $c = $b2c ? 1 : 0, $b2c_mail, $b2c_admin_mail, $b2c_item, $d = $nfo->phpize($b2c_percent), $e = $nfo->phpize($b2c_absolute), $f = $b2b ? 1 : 0, $b2b_mail, $b2b_admin_mail, $b2b_item, $g = $nfo->phpize($b2b_percent), $h = $nfo->phpize($b2b_absolute));

                    $stmt->execute();

                    alog("settings", "reminder_leval_add", $db->insert_id, $name);

                    echo '<div class="alert alert-success">' . $l['REMCREATEDNEW'] . '</div>';
                    unset($_POST);
                } catch (Exception $ex) {
                    echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $ex->getMessage() . '</div>';
                }
            }
            ?>
<form accept-charset="UTF-8" role="form" method="post">
  <fieldset>
	<div class="form-group">
		<label><?=$l['REMNAME'];?></label>
		<input type="text" class="form-control" name="name" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>" placeholder="<?=$l['REMNAMEP'];?>" />
	</div>

	<div class="form-group">
		<label><?=$l['REMTIME'];?></label>

		<div class="row">
			<div class="col-sm-3">
				<input type="numeric" class="form-control" name="days2" value="<?=isset($_POST['days2']) ? $_POST['days2'] : "";?>" placeholder="14" />
			</div>

			<div class="col-sm-9">
				<select name="days1" class="form-control">
					<option value="+"><?=$l['REMTIME1'];?></option>
					<option value="-"<?php if (isset($_POST['days1']) && $_POST['days1'] == "-") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMTIME2'];?></option>
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['REMDIS'];?></label>

		<div class="row">
			<div class="col-sm-9">
				<select name="color" class="form-control">
					<option value=""><?=$l['REMDIS0'];?></option>
					<option value="orange" style="color: orange;"<?php if (isset($_POST['color']) && $_POST['color'] == "orange") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS1'];?></option>
					<option value="red" style="color: red;"<?php if (isset($_POST['color']) && $_POST['color'] == "red") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS2'];?></option>
					<option value="darkred" style="color: darkred;"<?php if (isset($_POST['color']) && $_POST['color'] == "darkred") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS3'];?></option>
				</select>
			</div>

			<div class="col-sm-3">
				<select name="bold" class="form-control">
					<option value="0"><?=$l['REMDIS4'];?></option>
					<option value="1"<?php if (isset($_POST['bold']) && $_POST['bold'] == "1") {
                echo ' selected="selected"';
            }
            ?>><?=$l['REMDIS5'];?></option>
				</select>
			</div>
		</div>
	</div>

	<div class="form-group">
		<label><?=$l['COUNTRIES'];?></label>
		<select class="form-control" name="countries[]" multiple="multiple">
			<?php $sql = $db->query("SELECT * FROM client_countries ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {?>
			<option value="<?=$row->ID;?>"<?php if (isset($_POST['countries']) && is_array($_POST['countries']) && in_array($row->ID, $_POST['countries'])) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
			<?php }?>
		</select>
		<p class="help-block"><?=$l['MULTIPLECHOICE'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['REMB2C'];?></label>

		<script>
		function btc(state){
			var elements = document.getElementsByClassName('b2c');
			Array.prototype.forEach.call(elements, function(e){
				if(state) e.style.display = 'block';
				else e.style.display = 'none';
			})
		}
		</script>

		<div class="checkbox" style="margin-top: 0; padding-top: 0;"><label>
			<input type="checkbox" name="b2c" value="1"<?php if (!isset($_POST['name']) || (isset($_POST['b2c']) && $_POST['b2c'] == "1")) {
                echo ' checked="checked"';
            }
            ?> onchange="btc(this.checked);"> <?=$l['ACTIVATE'];?>
		</label></div>

		<div class="row b2c">
			<div class="col-sm-8">
				<input type="text" class="form-control" name="b2c_item" value="<?=isset($_POST['b2c_item']) ? $_POST['b2c_item'] : "";?>" placeholder="<?=$l['REMFEE'];?>" />
			</div>

			<div class="col-sm-2"><div class="input-group">
				<input type="text" class="form-control" name="b2c_percent" value="<?=isset($_POST['b2c_percent']) ? $_POST['b2c_percent'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<span class="input-group-addon">%</span>
			</div></div>

			<div class="col-sm-2"><div class="input-group">
				<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
				<input type="text" class="form-control" name="b2c_absolute" value="<?=isset($_POST['b2c_absolute']) ? $_POST['b2c_absolute'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
			</div></div>
		</div>

		<div class="row b2c" style="margin-top: 10px;">
			<div class="col-sm-6">
				<select name="b2c_mail" class="form-control">
					<option value=""><?=$l['REMNOCMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2c_mail']) && $_POST['b2c_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>

			<div class="col-sm-6">
				<select name="b2c_admin_mail" class="form-control">
					<option value=""><?=$l['REMNOAMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2c_admin_mail']) && $_POST['b2c_admin_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>
		</div>

		<?php if (isset($_POST['name']) && (!isset($_POST['b2c']) || $_POST['b2c'] != "1")) {?>
		<style>
		.b2c { display: none; }
		</style>
		<?php }?>
	</div>

	<div class="form-group">
		<label><?=$l['REMB2B'];?></label>

		<script>
		function btb(state){
			var elements = document.getElementsByClassName('b2b');
			Array.prototype.forEach.call(elements, function(e){
				if(state) e.style.display = 'block';
				else e.style.display = 'none';
			})
		}
		</script>

		<div class="checkbox" style="margin-top: 0; padding-top: 0;"><label>
			<input type="checkbox" name="b2b" value="1"<?php if (!isset($_POST['name']) || (isset($_POST['b2b']) && $_POST['b2b'] == "1")) {
                echo ' checked="checked"';
            }
            ?> onchange="btb(this.checked);"> <?=$l['ACTIVATE'];?>
		</label></div>

		<div class="row b2b">
			<div class="col-sm-8">
				<input type="text" class="form-control" name="b2b_item" value="<?=isset($_POST['b2b_item']) ? $_POST['b2b_item'] : "";?>" placeholder="<?=$l['REMFEE'];?>" />
			</div>

			<div class="col-sm-2"><div class="input-group">
				<input type="text" class="form-control" name="b2b_percent" value="<?=isset($_POST['b2b_percent']) ? $_POST['b2b_percent'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<span class="input-group-addon">%</span>
			</div></div>

			<div class="col-sm-2"><div class="input-group">
				<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
				<input type="text" class="form-control" name="b2b_absolute" value="<?=isset($_POST['b2b_absolute']) ? $_POST['b2b_absolute'] : $nfo->format(0);?>" placeholder="<?=$nfo->placeholder();?>" />
				<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
			</div></div>
		</div>

		<div class="row b2b" style="margin-top: 10px;">
			<div class="col-sm-6">
				<select name="b2b_mail" class="form-control">
					<option value=""><?=$l['REMNOCMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2b_mail']) && $_POST['b2b_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>

			<div class="col-sm-6">
				<select name="b2b_admin_mail" class="form-control">
					<option value=""><?=$l['REMNOAMAIL'];?></option>
					<?php $sql = $db->query("SELECT ID,name FROM email_templates WHERE category = 'Eigene' ORDER BY name ASC");while ($row = $sql->fetch_object()) {?>
					<option value="<?=$row->ID;?>"<?php if (isset($_POST['b2b_admin_mail']) && $_POST['b2b_admin_mail'] == $row->ID) {
                echo ' selected="selected"';
            }
                ?>><?=$row->name;?></option>
					<?php }?>
				</select>
			</div>
		</div>

		<?php if (isset($_POST['name']) && (!isset($_POST['b2b']) || $_POST['b2b'] != "1")) {?>
		<style>
		.b2b { display: none; }
		</style>
		<?php }?>
	</div>

	<div class="form-group">
		<label><?=$l['REMMAILVAR'];?></label><br />
		<?=$l['REMMAILVARLIST'];?>
	</div>

	<div class="form-group">
		<label><?=$l['REMLETTERSEND'];?></label><br />
		<?=$l['REMLETTERSENDH'];?>
	</div>

	<div class="form-group">
      <button type="submit" class="btn btn-primary btn-block">
        <?=$l['REMCREATENEW'];?>
      </button>
    </div>
  </fieldset>
</form>
<?php
} else {
            if (isset($_GET['delete']) && $db->query("SELECT 1 FROM invoices WHERE reminder = " . intval($_GET['delete']) . " LIMIT 1")->num_rows == 0 && $db->query("DELETE FROM reminders WHERE ID = " . intval($_GET['delete']) . " LIMIT 1") && $db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['REMDELETED'] . '</div>';
                alog("settings", "reminder_level_delete", $_GET['delete']);
            }

            if (isset($_GET['cronjob']) && in_array($_GET['cronjob'], array("0", "1"))) {
                $db->query("UPDATE cronjobs SET active = " . intval($_GET['cronjob']) . " WHERE `key` = 'reminders'");
                alog("settings", "cronjob_status", "reminders", $_GET['cronjob']);
            }
            $cronjob = $db->query("SELECT active FROM cronjobs WHERE `key` = 'reminders'")->fetch_object()->active;

            if (isset($_POST['remind_credit'])) {
                $CFG['REMIND_CREDIT'] = strval(intval($_POST['remind_credit']));
                alog("settings", "remind_credit", $CFG['REMIND_CREDIT']);
                $db->query("UPDATE settings SET `value` = " . $CFG['REMIND_CREDIT'] . " WHERE `key` = 'remind_credit'");
            }
            ?>
<form method="POST" class="form-inline">
<a href="?p=settings&tab=reminders&add" class="btn btn-success"><?=$l['REMADDNEW'];?></a> <a href="?p=settings&tab=reminders&cronjob=<?=$cronjob ? "0" : "1";?>" class="btn btn-<?=$cronjob ? "warning" : "primary";?>"><?=$cronjob ? $l['REMCRON0'] : $l['REMCRON1'];?></a>
	<div class="input-group">
		<span class="input-group-addon"><?=$lang['SETTINGS']['REMIND_CREDIT']; ?></span>
		<select class="form-control" name="remind_credit" onchange="form.submit();">
			<option value="0"><?=$lang['GENERAL']['NEVER'];?></option>
			<option value="1"<?=$CFG['REMIND_CREDIT'] === "1" ? ' selected=""' : '';?>><?=$lang['GENERAL']['MONDAY'];?></option>
			<option value="2"<?=$CFG['REMIND_CREDIT'] === "2" ? ' selected=""' : '';?>><?=$lang['GENERAL']['TUESDAY'];?></option>
			<option value="3"<?=$CFG['REMIND_CREDIT'] === "3" ? ' selected=""' : '';?>><?=$lang['GENERAL']['WEDNESDAY'];?></option>
			<option value="4"<?=$CFG['REMIND_CREDIT'] === "4" ? ' selected=""' : '';?>><?=$lang['GENERAL']['THURSDAY'];?></option>
			<option value="5"<?=$CFG['REMIND_CREDIT'] === "5" ? ' selected=""' : '';?>><?=$lang['GENERAL']['FRIDAY'];?></option>
			<option value="6"<?=$CFG['REMIND_CREDIT'] === "6" ? ' selected=""' : '';?>><?=$lang['GENERAL']['SATURDAY'];?></option>
			<option value="7"<?=$CFG['REMIND_CREDIT'] === "7" ? ' selected=""' : '';?>><?=$lang['GENERAL']['SUNDAY'];?></option>
		</select>
		<span class="input-group-addon"><a href="?p=edit_mail_template&id=8713" target="_blank"><i class="fa fa-pencil"></i></a></span>
	</div>
</form><br />

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th><?=$l['REMLEVEL'];?></th>
		<th><?=$l['REMTIME'];?></th>
		<th><?=$l['REMB2C'];?></th>
		<th><?=$l['REMB2B'];?></th>
		<th width="50px"></th>
	</tr>

	<?php
$sql = $db->query("SELECT * FROM reminders ORDER BY days ASC, name ASC");
            while ($row = $sql->fetch_object()) {?>
	<tr>
		<td><?php if (!empty($row->color)) {
                echo '<font color="' . $row->color . '">';
            }
                ?><?=$row->bold ? "<b>" : "";?><?=$row->name;?><?=$row->bold ? "</b>" : "";?><?php if (!empty($row->color)) {
                    echo '</font>';
                }
                ?></td>
		<td><?php if ($row->days == "0") {echo $l['REMWHENDUE'];} else {?><?=abs($row->days);?> <?=$l['REMDAYS'];?> <?=$row->days > 0 ? $l['REMAFTERDUE'] : $l['REMBEFOREDUE'];?><?php }?></td>
		<td><?php if ($row->b2c) {$b2c = array();if ($row->b2c_mail) {
                    array_push($b2c, $l['REMMAIL']);
                }
                    if ($row->b2c_admin_mail) {
                        array_push($b2c, $l['REMAMAIL']);
                    }
                    if ($row->b2c_letter) {
                        array_push($b2c, $l['REMLETTER']);
                    }
                    if ($row->b2c_percent > 0) {
                        array_push($b2c, $nfo->format($b2c_percent, 2, true) . " %");
                    }
                    if ($row->b2c_absolute > 0) {
                        array_push($b2c, $cur->infix($nfo->format($row->b2c_absolute), $cur->getBaseCurrency()));
                    }
                    ?><?=implode(" // ", $b2c);?><?php } else {?>-<?php }?></td>
		<td><?php if ($row->b2b) {$b2b = array();if ($row->b2b_mail) {
                    array_push($b2b, $l['REMMAIL']);
                }
                    if ($row->b2b_letter) {
                        array_push($b2b, $l['REMLETTER']);
                    }
                    if ($row->b2b_admin_mail) {
                        array_push($b2b, $l['REMAMAIL']);
                    }
                    if ($row->b2b_percent > 0) {
                        array_push($b2b, $nfo->format($b2b_percent, 2, true) . " %");
                    }
                    if ($row->b2b_absolute > 0) {
                        array_push($b2b, $cur->infix($nfo->format($row->b2b_absolute), $cur->getBaseCurrency()));
                    }
                    ?><?=implode(" // ", $b2b);?><?php } else {?>-<?php }?></td>
		<td><a href="?p=settings&tab=reminders&edit=<?=$row->ID;?>"><i class="fa fa-edit"></i></a><?php if ($db->query("SELECT 1 FROM invoices WHERE reminder = {$row->ID} LIMIT 1")->num_rows == 0) {?> <a href="?p=settings&tab=reminders&delete=<?=$row->ID;?>" onclick="return confirm('<?=$l['REMREADEL'];?>');"><i class="fa fa-times fa-lg"></i></a><?php } else {?> <a href="#" onclick="alert('<?=$l['REMDELHASINV'];?>'); return false;"><i class="fa fa-times fa-lg"></i></a><?php }?></td>
	</tr>
	<?php }if ($sql->num_rows == 0) {?>
	<tr>
		<td colspan="5"><center><?=$l['REMNT'];?></center></td>
	</tr>
	<?php }?>
</table>
</div>
<?php }} else if ($tab == "seo") {

        $seo_languages = array();
        $lang_cached = $lang;
        foreach ($languages as $file => $name) {
            unset($lang);
            include __DIR__ . "/../../languages/" . $file . ".php";
            if (isset($lang) && !isset($seo_languages[$lang["ISOCODE"]])) {
                $seo_languages[$lang["ISOCODE"]] = $name;
            }

        }
        $lang = $lang_cached;

        if (isset($_POST['change_seo'])) {
            $seo = array();
            $hi = serialize($_POST['home_intro']);

            foreach ($seo_languages as $iso => $name) {
                $seo[$iso] = array();
                if (isset($_POST["desc"][$iso])) {
                    $seo[$iso]["desc"] = $_POST["desc"][$iso];
                }

                if (isset($_POST["keywords"][$iso])) {
                    $seo[$iso]["keywords"] = $_POST["keywords"][$iso];
                }

            }

            $seo = serialize($seo);
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($seo) . "' WHERE `key` = 'seo' LIMIT 1");
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($hi) . "' WHERE `key` = 'home_intro' LIMIT 1");
            $CFG["SEO"] = $seo;
            $CFG["HOME_INTRO"] = $hi;

            $arr = is_array($_POST['url_rewrite'] ?? "") ? $_POST['url_rewrite'] : [];

            if (array_key_exists("#ID#", $arr)) {
                unset($arr["#ID#"]);
            }

            foreach ($arr as $k => $v) {
                if (empty($v["old"])) {
                    unset($arr[$k]);
                }
            }

            $ur = serialize($arr);
            $db->query("UPDATE settings SET value = '" . $db->real_escape_string($ur) . "' WHERE `key` = 'url_rewrite' LIMIT 1");
            $CFG['URL_REWRITE'] = $ur;

            alog("settings", "seo_changed");

            echo '<div class="alert alert-success">' . $l['SEOSAVED'] . '</div>';
        }

        ?>
<div>
  <form accept-charset="UTF-8" role="form" id="login-form" method="post">

      <fieldset>
		<div class="form-group">
			<label><?=$l['PAGEINFO'];?></label><?php foreach ($seo_languages as $lang_key => $lang_name) {?>
                <a href="#" class="btn btn-default btn-xs<?=$lang_key == strtolower($lang["ISOCODE"]) ? ' active' : '';?>" show-lang="<?=$lang_key;?>"><?=$lang_name;?></a>
                <?php }?><br />
			<?php
$hi = [];

        if (@unserialize($CFG['HOME_INTRO'])) {
            foreach (unserialize($CFG['HOME_INTRO']) as $k => $v) {
                $hi[$k] = $v;
            }
        }

        foreach ($languages as $lang_key => $lang_name) {
            if (!array_key_exists($k, $hi)) {
                $hi[$k] = count($hi) ? array_values($hi)[0] : $CFG['HOME_INTRO'];
            }
        }

        foreach ($seo_languages as $key => $name) {
            $lang_key = $key;
            $uns = unserialize($CFG["SEO"]);

            if (false !== $uns && is_array($uns) && count($uns) > 0 && isset($uns[$key])) {
                $info = $uns[$key];
                if (isset($info["desc"])) {
                    $desc = $info["desc"];
                }

                if (isset($info["keywords"])) {
                    $keywords = $info["keywords"];
                }

            }

            ?>
				<div is-lang="<?=$key;?>"<?=$key != strtolower($lang["ISOCODE"]) ? ' style="display: none;"' : '';?>>
						<div class="form-group">
							<textarea class="form-control" name="home_intro[<?=$lang_key;?>]" placeholder="<?=$l['SEOINTRO'];?>" style="resize: none; width: 100%; height: 100px"><?=$hi[$lang_key];?></textarea>
						</div>

						<div class="form-group">
							<input type="text" class="form-control" name="desc[<?=$key;?>]" placeholder="<?=$l['SEODESC'];?>" value="<?=isset($desc) ? $desc : "";?>">
						</div>

						<div class="form-group">
							<input type="text" class="form-control" name="keywords[<?=$key;?>]" value="<?=isset($keywords) ? $keywords : "";?>" placeholder="<?=$l['SEOWORDS'];?>">
							<p class="help-block"><?=$l['SEPWITHCOM'];?></p>
						</div>
				</div>
			<?php $keywords = $desc = "";
        }?>
		</div>

		<div class="form-group">
			<label><?=$l['URLREWRITE'];?></label> <a href="#" id="urlr_add"><i class="fa fa-plus"></i></a>

			<?php
$url_rewrite = @unserialize($CFG['URL_REWRITE']);
        if (!is_array($url_rewrite)) {
            $url_rewrite = [];
        }
        if (array_key_exists("#ID#", $url_rewrite)) {
            unset($url_rewrite["#ID#"]);
        }

        $i = 0;
        foreach ($url_rewrite as $ur) {
            ?>
			<div class="row"<?=$i ? " style=\"margin-top: 10px;\"" : "";?>>
				<div class="col-md-4">
					<input type="text" name="url_rewrite[<?=$i;?>][new]" value="<?=htmlentities($ur["new"]);?>" placeholder="<?=$l['URLRN'];?>" class="form-control">
				</div>

				<div class="col-md-4">
					<input type="text" name="url_rewrite[<?=$i;?>][old]" value="<?=htmlentities($ur["old"]);?>" placeholder="<?=$l['URLRO'];?>" class="form-control">
				</div>

				<div class="col-md-2">
					<div class="checkbox input-group-addon" style="margin-bottom: 0; height: 34px; border: 1px solid #ccc; border-radius: 4px; padding: 0;">
						<label>
							<input type="checkbox" name="url_rewrite[<?=$i;?>][force]" value="1"<?=@$ur["force"] ? ' checked=""' : '';?>>
							<?=$l['URLRF'];?>
						</label>
					</div>
				</div>

				<div class="col-md-2">
					<button type="button" class="btn btn-danger btn-block urlr_del"><?=$l['URLRD'];?></button>
				</div>
			</div>
			<?php $i++;}?>

			<div id="url_rewrite_new"></div>

			<div class="row url_rewrite_temp" style="display: none;<?=$i ? "margin-top: 10px;" : "";?>">
				<div class="col-md-4">
					<input type="text" name="url_rewrite[#ID#][new]" placeholder="<?=$l['URLRN'];?>" class="form-control">
				</div>

				<div class="col-md-4">
					<input type="text" name="url_rewrite[#ID#][old]" placeholder="<?=$l['URLRO'];?>" class="form-control">
				</div>

				<div class="col-md-2">
					<div class="checkbox input-group-addon" style="margin-bottom: 0; height: 34px; border: 1px solid #ccc; border-radius: 4px; padding: 0;">
						<label>
							<input type="checkbox" name="url_rewrite[#ID#][force]" value="1">
							<?=$l['URLRF'];?>
						</label>
					</div>
				</div>

				<div class="col-md-2">
					<button type="button" class="btn btn-danger btn-block urlr_del"><?=$l['URLRD'];?></button>
				</div>
			</div>
		</div>

		<script>
		var i = <?=strval(intval($i));?>

		function addUrlR() {
			var temp = $(".url_rewrite_temp").clone();
			$(".url_rewrite_temp").css("margin-top", "10px");
			temp.removeClass("url_rewrite_temp").show();
			temp.html(temp.html().replace(/\#ID\#/g, ++i));
			$("#url_rewrite_new").append(temp);
		}
		addUrlR();

		$("#urlr_add").click(function(e) {
			e.preventDefault();
			addUrlR();
		});

		function bindUrlR() {
			$(".urlr_del").unbind("click").click(function(e) {
				e.preventDefault();
				$(this).parent().parent().find("[type=text]").val("");
				$(this).parent().parent().find("[type=checkbox]").prop("checked", false);
			});
		}

		bindUrlR();
		</script>

		<div class="form-group">
          <button type="submit" name="change_seo" class="btn btn-primary btn-block">
            <?=$l['SAVEANY'];?>
          </button>
        </div>
      </fieldset>
    </form>
  </div>
<?php } else if ($tab == "pma" && $ari->check(38)) {?>
		<?=$l['PMAHINT'];?>
	<br /><br />
		<a href="./adminer.php" target="_blank" class="btn btn-primary btn-block"><?=$l['PMADO'];?></a>
<?php } else if ($tab == "crons") {

        $additionalJS = 'function saveCronResponse(text, id, pw) {
            $("#cron_button_" + id).prop("disabled", true);

            if(text != "failed" && text != ""){
                if(pw == "")
                    $("#cron_tpw_" + id).html("<i>' . $l['CPWNS'] . '</i>");
                else
                    $("#cron_tpw_" + id).html(pw);
                $("#cron_link_" + id).attr("href", text);
                $("#cron_button_" + id).val("' . $lang['GENERAL']['SAVED'] . '");
                $("#cron_button_" + id).addClass("btn-success");
            } else {
                $("#cron_button_" + id).val("' . $lang['GENERAL']['SAVE_FAILED'] . '");
                $("#cron_button_" + id).addClass("btn-danger");
            }

            setTimeout(function() {
                $("#cron_button_" + id).val("' . $lang['GENERAL']['SAVE'] . '");
                $("#cron_button_" + id).removeClass("btn-success btn-danger");
                $("#cron_button_" + id).prop("disabled", false);
            }, 1750);
        }

        function saveCron(id) {
            $.ajax({
                url : "./?p=ajax",
                data : { action : "save_cron", cron_id : id, cron_pw : $("#cron_pw_" + id).val(), csrf_token: "' . CSRF::raw() . '", },
                dataType : "JSON",
                type : "POST",
                cache: false,
                success : function(succ) {
                    saveCronResponse(succ.responseText, id, $("#cron_pw_" + id).val());
                },
                error : function(err) {
                    saveCronResponse(err.responseText, id, $("#cron_pw_" + id).val());
                }
            });
        }';

        if (isset($_GET['pause']) || isset($_GET['resume'])) {
            $status = isset($_GET['pause']) ? 0 : 1;
            $id = $status == 0 ? intval($_GET['pause']) : intval($_GET['resume']);
            $db->query("UPDATE cronjobs SET active = $status WHERE ID = $id LIMIT 1");
            if ($db->affected_rows > 0) {
                if ($status == 0) {
                    echo "<div class='alert alert-success'>{$l['CJC0']}</div>";
                } else {
                    echo "<div class='alert alert-success'>{$l['CJC1']}</div>";
                }
                alog("settings", "cronjob_status", $id, $status);
            }
        }

        function readableInt($s)
        {
            global $l;

            if ($s <= 60) {
                return $s . " " . $l['SECONDS'];
            } else if ($s > 60 && $s <= 3600) {
                return ($s / 60) . " " . $l['MINUTES'];
            } else {
                return ($s / 3600) . " " . $l['HOURS'];
            }

        }

        $command = "nohup " . ((PHP_BINARY ?? "") ?: "php ");
        $command .= realpath(__DIR__ . "/../../index.php");
        $command .= " cron _all &";
        ?>
<div class="alert alert-info">
	<?=$l['CRONJOBDAEMON'];?><br /><code><?=$command;?></code>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th><?=$l['CRONJOB'];?></th>
		<th><?=$l['PASSWORD'];?></th>
		<th><?=$l['INTERVAL'];?></th>
		<th><?=$l['LASTCALL'];?></th>
		<th width="47px"></th>
	</tr>
	<?php
$sql = $db->query("SELECT * FROM cronjobs ORDER BY name ASC");
        if ($sql->num_rows > 0) {while ($row = $sql->fetch_object()) {?>
	<tr>
		<td><?=($row->active != 1 ? "<font color='orange'>" : "") . ($lang['ISOCODE'] != 'de' && $row->foreign_name ? $row->foreign_name : $row->name) . ($row->active != 1 ? "</font>" : "");?> &nbsp; <?php if ($row->active == 1) {?><a href="./?p=settings&tab=crons&pause=<?=$row->ID;?>"><i class="fa fa-pause"></i></a><?php } else {?><a href="./?p=settings&tab=crons&resume=<?=$row->ID;?>"><i class="fa fa-play"></i></a><?php }?></td>
		<td id="cron_tpw_<?=$row->ID;?>"><?=trim($row->password) != "" ? htmlentities($row->password) : "<i>{$l['CPWNS']}</i>";?></td>
		<td><?=readableInt($row->intervall);?></td>
		<td><?=$row->last_call != 0 ? date("d.m.Y - H:i:s", $row->last_call) : "<i>{$l['NOTYET']}</i>";?><?php if (file_exists(__DIR__ . "/../../controller/crons/{$row->key}.lock")) {?> <i>(<a href="#" data-toggle="modal" data-target="#cron_<?=$row->key;?>"><?=$l['RUNNING'];?></a> <a href="?p=settings&tab=crons&unlock=<?=$row->key;?>"><i class="fa fa-times"></i></a>)</i><?php }?></td>
		<td><a href="#" data-toggle="modal" data-target="#cron_<?=$row->ID;?>"><i class="fa fa-key"></i></a>&nbsp;<a href="<?=$CFG['PAGEURL'] . "cron?job=" . $row->key . (trim($row->password) != "" ? "&pw=" . $row->password : "");?>" id="cron_link_<?=$row->ID;?>" target="_blank"><i class="fa fa-globe"></i></a></td>
	</tr>

	<div class="modal fade" id="cron_<?=$row->ID;?>" tabindex="-1" role="dialog">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title"><?=$l['CRONJOB'];?>: <?=$row->name;?></h4>
	      </div>
	      <div class="modal-body">
	        <?=$l['CJINTRO'];?><br /><br />
	        <input type="text" id="cron_pw_<?=$row->ID;?>" class="form-control" value="<?=htmlentities($row->password);?>" placeholder="<?=$l['CJPWD'];?>" /><br />
	        <input type="button" onclick="saveCron(<?=$row->ID;?>)" value="<?=$lang['GENERAL']['SAVE'];?>" id="cron_button_<?=$row->ID;?>" class="btn btn-block btn-primary" />
	      </div>
	    </div>
	  </div>
	</div>
	<?php }} else {echo "<tr><td colspan='5'><center>{$l['CJNT']}</center></td></tr>";}?>
</table>
</div>

<?php foreach (glob(__DIR__ . "/../../controller/crons/*.lock") as $f) {$k = substr(basename($f), 0, -5);?>
<div class="modal fade" id="cron_<?=$k;?>" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$l['CRONJOB'];?>: <?=$k;?></h4>
      </div>
      <div class="modal-body">
        <?php
$c = file_get_contents($f);
            if (empty($c)) {
                echo "<i>{$l['CJNLE']}</i>";
            } else {
                echo nl2br(htmlentities($c));
            }

            ?>
      </div>
    </div>
  </div>
</div>
<?php }} else if ($tab == "cache") {?>

<?php
$name = array("templates" => $l['CACHE1'], "templates_admin" => $l['CACHE2']);
        if (isset($_GET['clear']) && array_key_exists($_GET['clear'], $name)) {
            echo '<div class="alert alert-success">' . str_replace("%c", $name[$_GET['clear']], $l['CACHECLEARED']) . '</div>';
            alog("settings", "cache_clear", $_GET['clear']);
        }
        ?>

<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th><?=$l['CACHETYPE'];?></th>
			<th><?=$l['FILES'];?></th>
			<th><?=$l['SIZE'];?></th>
			<th width="28px"></th>
		</tr>

		<?php
function formatBytes($bytes, $precision = 2)
        {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);

            return number_format(round($bytes, $precision), 2, ',', '.') . ' ' . $units[$pow];
        }

        $i = $s = 0;
        foreach (glob(__DIR__ . "/../../templates/compiled/*.php") as $f) {
            if (isset($_GET['clear']) && $_GET['clear'] == "templates") {
                unlink($f);
                continue;
            }

            $i++;
            $s += filesize($f);
        }
        ?>

		<tr>
			<td><?=$l['CACHES1'];?></td>
			<td><?=$i;?></td>
			<td><?=formatBytes($s);?></td>
			<td><a href="?p=settings&tab=cache&clear=templates"><i class="fa fa-ban"></i></a></td>
		</tr>

		<?php
$i = $s = 0;
        foreach (glob(__DIR__ . "/../templates/compiled/*.php") as $f) {
            if (isset($_GET['clear']) && $_GET['clear'] == "templates_admin") {
                unlink($f);
                continue;
            }

            $i++;
            $s += filesize($f);
        }
        ?>

		<tr>
			<td><?=$l['CACHES2'];?></td>
			<td><?=$i;?></td>
			<td><?=formatBytes($s);?></td>
			<td><a href="?p=settings&tab=cache&clear=templates_admin"><i class="fa fa-ban"></i></a></td>
		</tr>
	</table>
</div>

<?php } else if ($tab == "system_status") {

        if (isset($_GET['check'])) {
            alog("settings", "system_check");
            SystemStatus::check(true);
            header('Location: ./?p=settings&tab=system_status&checked');
            exit;
        }

        if (in_array("checked", array_keys($_GET))) {
            echo "<div class='alert alert-success'>{$l['SSCA']}</div>";
        } else {
            echo '<div class="alert alert-info">' . $l['SSCJ'] . '</div>';
        }

        $sql = $db->query("SELECT * FROM system_status ORDER BY ID DESC");

        if ($sql->num_rows > 0) {
            echo "<a href='./?p=settings&tab=system_status&check=1' class='btn btn-primary'>{$l['SSCAN']}</a><br /><br />";
        }

        $types = array(
            "pfile" => $l['SS1'],
            "file" => $l['SS2'],
            "download" => $l['SS3'],
            "version" => $l['SS4'],
            "bugtrack" => $l['SS5'],
        );

        if ($warnings = SystemRequirements::getWarningList()) {
            echo '<div class="alert alert-warning">' . $l['SSCW'] . ': ' . $warnings . '</div>';
        }

        ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
	<tr>
		<th><?=$l['PROBLEM'];?></th>
		<th><?=$l['RELATION'];?></th>
	</tr>
	<?php
if ($sql->num_rows > 0) {while ($row = $sql->fetch_object()) {?>
	<tr>
		<td><?=isset($types[$row->type]) ? $types[$row->type] : $row->type;?></td>
		<td><?=$row->relship;?></td>
	</tr>
	<?php }} else {echo "<tr><td colspan='5'><center>{$l['SSNE']}</center></td></tr>";}?>
</table>
</div>
<?php } else if ($tab == "countries") {?>
<?php
if (isset($_GET['action']) && $_GET['action'] == "taxes") {
        $status = $_GET['status'] == "1" ? 1 : 0;
        $db->query("UPDATE settings SET `value` = '$status' WHERE `key` = 'taxes' LIMIT 1");
        $CFG['TAXES'] = $status;
        alog("settings", "tax_status", $_GET['status']);
    }

        if (isset($_GET['action']) && $_GET['action'] == "eu") {
            $status = $_GET['status'] == "1" ? 1 : 0;
            $db->query("UPDATE settings SET `value` = '$status' WHERE `key` = 'eu_vat' LIMIT 1");
            $CFG['EU_VAT'] = $status;
            alog("settings", "eu_vat_status", $_GET['status']);
        }

        if (isset($_GET['action']) && $_GET['action'] == "add") {
            if (isset($_POST['submit'])) {
                try {
                    foreach ($_POST as $k => $v) {
                        $vari = "post_" . strtolower($k);
                        $$vari = $db->real_escape_string($v);
                    }

                    if (!isset($post_name) || $post_name == "") {
                        throw new Exception($l['TERR1']);
                    }

                    if ($db->query("SELECT ID FROM client_countries WHERE name = '$post_name'")->num_rows > 0) {
                        throw new Exception($l['TERR2']);
                    }

                    if (!isset($post_alpha2) || strlen($post_alpha2) != 2) {
                        throw new Exception($l['TERR3']);
                    }

                    if ($db->query("SELECT ID FROM client_countries WHERE alpha2 = '$post_alpha2'")->num_rows > 0) {
                        throw new Exception($l['TERR4']);
                    }

                    $post_alpha2 = strtoupper($post_alpha2);

                    if ($CFG['TAXES']) {
                        if (!isset($post_tax) || strlen(trim($post_tax)) < 1) {
                            throw new Exception($l['TERR5']);
                        }

                        $post_percent = $nfo->phpize(trim(str_replace("%", "", $nfo->phpize($post_percent))));
                        if (!isset($_POST['percent']) || !is_numeric($post_percent) || $post_percent < 0 || $post_percent > 80) {
                            throw new Exception($l['TERR6']);
                        }

                        if (!isset($post_b2c) || !is_numeric($post_b2c) || $post_b2c < 0 || $post_b2c > 2) {
                            throw new Exception($l['TERR7']);
                        }

                        if (!isset($post_b2b) || !is_numeric($post_b2b) || $post_b2b < 0 || $post_b2b > 3) {
                            throw new Exception($l['TERR8']);
                        }

                        if ($post_b2b == 1 && !$CFG['EU_VAT']) {
                            throw new Exception($l['TERR8']);
                        }

                        $db->query("INSERT INTO client_countries (name, alpha2, tax, percent, b2c, b2b) VALUES ('$post_name', '$post_alpha2', '$post_tax', '$post_percent', '$post_b2c', '$post_b2b')");
                    } else {
                        $db->query("INSERT INTO client_countries (name, alpha2) VALUES ('$post_name', '$post_alpha2')");
                    }

                    alog("settings", "country_added", $db->insert_id, $post_name, $post_alpha2);

                    echo '<div class="alert alert-success">' . $l['COUNTRYADDED'] . '</div>';
                } catch (Exception $exc) {
                    echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $exc->getMessage() . '</div>';
                }
            }

            if (!isset($_POST['submit']) || isset($exc)) {
                $b2c = isset($_POST['b2c']) ? $_POST['b2c'] : 0;
                $b2c = isset($_POST['b2c']) ? $_POST['b2c'] : 0;
                ?>
			<form role="form" method="POST">
  <div class="form-group row">
    <div class="col-sm-10">
    	<label><?=$l['COUNTRY'];?></label>
    	<input type="text" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>" name="name" placeholder="<?=$l['COUNTRYP'];?>" class="form-control">
    </div>

    <div class="col-sm-2">
    	<label><?=$l['ISOCODE'];?></label>
    	<input type="text" value="<?=isset($_POST['alpha2']) ? $_POST['alpha2'] : "";?>" name="alpha2" maxlength="2" placeholder="DE" class="form-control">
    </div>
  </div>
<?php if ($CFG['TAXES']) {?><div class="form-group">
			<label><?=$l['TAXES'];?></label>
			<div class="row"><div class="col-md-8"><input type="text" name="tax" value="<?=isset($_POST['tax']) ? $_POST['tax'] : "";?>" placeholder="<?=$l['TAXNAMEP'];?>" class="form-control"></div>
<div class="col-md-4"> <input type="text" name="percent" value="<?=isset($_POST['percent']) ? $_POST['percent'] : "";?>" placeholder="<?=$l['TAXRATEP'];?>" class="form-control"> </div></div>
		</div>

		<div class="form-group row">
			<div class="col-xs-6">
				<label><?=$l['TAXB2C'];?></label>
				<select name="b2c" class="form-control">
					<option value="0"><?=$l['TAXRC'];?></option>
					<option value="1"<?php if (isset($b2c) && $b2c == "1") {
                    echo ' selected="selected"';
                }
                    ?>><?=$l['TAXNORMAL'];?></option>
					<option value="2"<?php if (isset($b2c) && $b2c == "2") {
                        echo ' selected="selected"';
                    }
                    ?>><?=$l['TAXDECLINE'];?></option>
				</select>
			</div>

			<div class="col-xs-6">
				<label><?=$l['TAXB2B'];?></label>
				<select name="b2b" class="form-control">
					<option value="0"><?=$l['TAXRC'];?></option>
					<?php if ($CFG['EU_VAT']) {?><option value="1"<?php if (isset($b2b) && $b2b == "1") {
                        echo ' selected="selected"';
                    }
                        ?>><?=$l['TAXRCIF'];?></option><?php }?>
					<option value="2"<?php if (isset($b2b) && $b2b == "2") {
                        echo ' selected="selected"';
                    }
                    ?>><?=$l['TAXNORMAL'];?></option>
					<option value="3"<?php if (isset($b2b) && $b2b == "3") {
                        echo ' selected="selected"';
                    }
                    ?>><?=$l['TAXDECLINE'];?></option>
				</select>
			</div>
		</div><?php }?>
  <center><input type="submit" name="submit" class="btn btn-primary" value="<?=$l['ADD_COUNTRY'];?>"></center></form><hr/><?php }}?>
  <?php if (isset($_GET['edit']) && $_GET['edit'] > 0 && $db->query("SELECT * FROM client_countries WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->num_rows == 1) {
            $info = $db->query("SELECT * FROM client_countries WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->fetch_object();

            if (isset($_POST['submit'])) {
                try {
                    foreach ($_POST as $k => $v) {
                        $vari = "post_" . strtolower($k);
                        $$vari = $db->real_escape_string($v);
                    }

                    if (!isset($post_name) || $post_name == "") {
                        throw new Exception($l['TERR1']);
                    }

                    if ($db->query("SELECT ID FROM client_countries WHERE name = '$post_name' AND ID != " . $info->ID)->num_rows > 0) {
                        throw new Exception($l['TERR2']);
                    }

                    if (!isset($post_alpha2) || strlen($post_alpha2) != 2) {
                        throw new Exception($l['TERR3']);
                    }

                    if ($db->query("SELECT ID FROM client_countries WHERE alpha2 = '$post_alpha2' AND ID != " . $info->ID)->num_rows > 0) {
                        throw new Exception($l['TERR4']);
                    }

                    $post_alpha2 = strtoupper($post_alpha2);

                    $tax = "";
                    if ($CFG['TAXES']) {
                        if (!isset($post_tax) || strlen(trim($post_tax)) < 1) {
                            throw new Exception($l['TERR5']);
                        }

                        $post_percent = $nfo->phpize(trim(str_replace("%", "", $post_percent)));
                        if (!isset($_POST['percent']) || !is_numeric($post_percent) || $post_percent < 0 || $post_percent > 80) {
                            throw new Exception($l['TERR6']);
                        }

                        if (!isset($post_b2c) || !is_numeric($post_b2c) || $post_b2c < 0 || $post_b2c > 2) {
                            throw new Exception($l['TERR7']);
                        }

                        if (!isset($post_b2b) || !is_numeric($post_b2b) || $post_b2b < 0 || $post_b2b > 3) {
                            throw new Exception($l['TERR8']);
                        }

                        if ($post_b2b == 1 && !$CFG['EU_VAT']) {
                            throw new Exception($l['TERR8']);
                        }

                        $tax = ", tax = '$post_tax', percent = '$post_percent', b2b = '$post_b2b', b2c = '$post_b2c'";
                    }

                    $db->query("UPDATE client_countries SET name = '$post_name', alpha2 = '$post_alpha2'$tax WHERE ID = " . $info->ID . " LIMIT 1");

                    alog("settings", "country_edit", $post_name, $post_alpha2, $info->ID);

                    echo '<div class="alert alert-success">' . $l['COUNTRYSAVED'] . '</div>';
                    unset($_POST);

                    $info = $db->query("SELECT * FROM client_countries WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->fetch_object();
                } catch (Exception $exc) {
                    echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $exc->getMessage() . '</div>';
                }
            }

            $b2b = isset($_POST['b2b']) ? $_POST['b2b'] : $info->b2b;
            $b2c = isset($_POST['b2c']) ? $_POST['b2c'] : $info->b2c;

            ?>
			<form role="form" method="POST">
  <div class="form-group row">
    <div class="col-sm-10">
    	<label><?=$l['COUNTRY'];?></label>
    	<input type="text" value="<?=isset($_POST['name']) ? $_POST['name'] : $info->name;?>" name="name" placeholder="<?=$l['COUNTRYP'];?>" class="form-control">
    </div>

    <div class="col-sm-2">
    	<label><?=$l['ISOCODE'];?></label>
    	<input type="text" value="<?=isset($_POST['alpha2']) ? $_POST['alpha2'] : $info->alpha2;?>" name="alpha2" maxlength="2" placeholder="DE" class="form-control">
    </div>
  </div>
<?php if ($CFG['TAXES']) {?><div class="form-group">
			<label><?=$l['TAXES'];?></label>
			<div class="row"><div class="col-md-8"><input type="text" name="tax" value="<?=isset($_POST['tax']) ? $_POST['tax'] : $info->tax;?>" placeholder="<?=$l['TAXNAMEP'];?>" class="form-control"></div>
<div class="col-md-4"> <input type="text" name="percent" value="<?=isset($_POST['percent']) ? $_POST['percent'] : $nfo->format($info->percent) . "%";?>" placeholder="<?=$l['TAXRATEP'];?>" class="form-control"> </div></div>
		</div>

		<div class="form-group row">
			<div class="col-xs-6">
				<label><?=$l['TAXB2C'];?></label>
				<select name="b2c" class="form-control">
					<option value="0"><?=$l['TAXRC'];?></option>
					<option value="1"<?php if (isset($b2c) && $b2c == "1") {
                echo ' selected="selected"';
            }
                ?>><?=$l['TAXNORMAL'];?></option>
					<option value="2"<?php if (isset($b2c) && $b2c == "2") {
                    echo ' selected="selected"';
                }
                ?>><?=$l['TAXDECLINE'];?></option>
				</select>
			</div>

			<div class="col-xs-6">
				<label>B2B-Verk&auml;ufe</label>
				<select name="b2b" class="form-control">
					<option value="0"><?=$l['TAXRC'];?></option>
					<?php if ($CFG['EU_VAT']) {?><option value="1"<?php if (isset($b2b) && $b2b == "1") {
                    echo ' selected="selected"';
                }
                    ?>><?=$l['TAXRCIF'];?></option><?php }?>
					<option value="2"<?php if (isset($b2b) && $b2b == "2") {
                    echo ' selected="selected"';
                }
                ?>><?=$l['TAXNORMAL'];?></option>
					<option value="3"<?php if (isset($b2b) && $b2b == "3") {
                    echo ' selected="selected"';
                }
                ?>><?=$l['TAXDECLINE'];?></option>
				</select>
			</div>
		</div>
		<?php }?>
  <center><input type="submit" name="submit" class="btn btn-primary" value="<?=$l['SAVE_COUNTRY'];?>"></center></form><hr/><?php }

        if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM  client_countries WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' LIMIT 1");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['COUNTRYDELETED'] . '</div>';
                alog("settings", "country_delete", $_GET['delete']);
            }
        }

        if (isset($_GET['default']) && $db->query("SELECT ID FROM client_countries WHERE ID = '" . intval($_GET['default']) . "'")->num_rows == 1) {
            $db->query("UPDATE settings SET `value` = '" . intval($_GET['default']) . "' WHERE `key` = 'default_country' LIMIT 1");
            $CFG['DEFAULT_COUNTRY'] = intval($_GET['default']);
            alog("settings", "default_country", $_GET['default']);
            echo '<div class="alert alert-success">' . $l['COUNTRYDEFSET'] . '</div>';
        }

        if (isset($_POST['tax_wise']) && in_array($_POST['tax_wise'], ["soll", "ist"]) && $_POST['tax_wise'] != $CFG['TAX_WISE']) {
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($_POST['tax_wise']) . "' WHERE `key` = 'tax_wise' LIMIT 1");
            $CFG['TAX_WISE'] = $_POST['tax_wise'];
            alog("settings", "tax_wise", $_POST['tax_wise']);
            echo '<div class="alert alert-success">' . $l['TAXWISESET'] . '</div>';
        }

        if (isset($_POST['countries']) && is_array($_POST['countries']) && count($_POST['countries']) > 0) {
            $done = 0;

            $n = 0;
            $o = 1;
            $m = "DEAC";
            if (isset($_POST['activate'])) {
                $n = 1;
                $o = 0;
                $m = "AC";
            }

            foreach ($_POST['countries'] as $id) {
                if ($id == $CFG['DEFAULT_COUNTRY'] && $n != 1) {
                    continue;
                }

                $db->query("UPDATE client_countries SET active = $n WHERE active = $o AND ID = " . intval($id));
                if ($db->affected_rows > 0) {
                    $done++;
                    alog("settings", "country_status", $n, $id);
                }
            }

            if ($done == 1) {
                echo "<div class='alert alert-success'>" . $l['COUNTRY' . $m] . "</div>";
            } else if ($done > 0) {
                echo "<div class='alert alert-success'>" . str_replace("%d", $done, $l['COUNTRYX' . $m]) . "</div>";
            }

        }

        if (!empty($_POST['auto_import'])) {
            $existing = [];

            $sql = $db->query("SELECT ID, alpha2 FROM client_countries");
            while ($row = $sql->fetch_object()) {
                $existing[$row->alpha2] = $row->ID;
            }

            $new = [
                "DE" => ["Deutschland", "Umsatzsteuer", 19, 2, 1],
                "BE" => ["Belgien", "BTW", 21, 1, 1],
                "EL" => ["Griechenland", "FPA", 24, 1, 1],
                "IE" => ["Irland", "VAT", 23, 1, 1],
                "IT" => ["Italien", "IVA", 22, 1, 1],
                "HR" => ["Kroatien", "PDV", 25, 1, 1],
                "LV" => ["Lettland", "PVN", 21, 1, 1],
                "LT" => ["Litauen", "PVM", 21, 1, 1],
                "LU" => ["Luxemburg", "TVA", 17, 1, 1],
                "MT" => ["Malta", "VAT", 18, 1, 1],
                "NL" => ["Niederlande", "Omzetbelasting", 21, 1, 1],
                "AT" => ["Österreich", "Umsatzsteuer", 20, 1, 1],
                "PL" => ["Polen", "VAT", 23, 1, 1],
                "PT" => ["Portugal", "IVA", 23, 1, 1],
                "RO" => ["Rumänien", "TVA", 19, 1, 1],
                "SE" => ["Schweden", "Mervärdeskatt", 25, 1, 1],
                "SK" => ["Slowakei", "DPH", 20, 1, 1],
                "SI" => ["Slowenien", "DDV", 22, 1, 1],
                "ES" => ["Spanien", "IVA", 21, 1, 1],
                "CZ" => ["Tschechische Republik", "DPH", 21, 1, 1],
                "HU" => ["Ungarn", "AFA", 27, 1, 1],
                "GB" => ["Vereinigtes Königreich", "VAT", 20, 1, 1],
                "CY" => ["Zypern", "FPA", 19, 1, 1],
                "BG" => ["Bulgarien", "DDS", 20, 1, 1],
                "DK" => ["Dänemark", "MOMS", 25, 1, 1],
                "EE" => ["Estland", "KMKR", 20, 1, 1],
                "FI" => ["Finnland", "AVL", 24, 1, 1],
                "FR" => ["Frankreich", "TVA", 20, 1, 1],
            ];

            if ($_POST['auto_import'] == "euch") {
                $new = array_merge($new, [
                    "CH" => ["Schweiz", "Keine", 0, 0, 2],
                ]);
            }

            foreach ($existing as $alpha2 => $id) {
                if (!array_key_exists($alpha2, $new)) {
                    $db->query("DELETE FROM client_countries WHERE ID = $id");
                } else {
                    $i = $new[$alpha2];
                    $db->query("UPDATE client_countries SET name = '{$i[0]}', tax = '{$i[1]}', percent = {$i[2]}, b2b = {$i[3]}, b2c = {$i[4]} WHERE ID = $id");
                    unset($new[$alpha2]);
                }
            }

            foreach ($new as $alpha2 => $i) {
                $db->query("INSERT INTO client_countries (alpha2, name, tax, percent, b2b, b2c) VALUES ('$alpha2', '{$i[0]}', '{$i[1]}', {$i[2]}, {$i[3]}, {$i[4]})");
            }

            $deid = $db->query("SELECT ID FROM client_countries WHERE alpha2 = 'DE'")->fetch_object()->ID;
            $CFG['DEFAULT_COUNTRY'] = $deid;
            $db->query("UPDATE settings SET value = $deid WHERE `key` = 'default_country'");

            echo "<div class='alert alert-success'>" . $l['AUTOIMPORTSUC'] . "</div>";
        }
        ?>
			<form method="POST" action="?p=settings&tab=countries" class="form-inline"><a href="?p=settings&tab=countries&action=add" class="btn btn-success"><?=$l['COUNTRYADDNEWONE'];?></a> <?php if ($CFG['TAXES']) {?><a href="?p=settings&tab=countries&action=taxes&status=0" class="btn btn-warning"><?=$l['TAXDE'];?></a>

			<select name="tax_wise" class="form-control" onchange="form.submit()">
				<option value="ist"><?=$l['IST'];?></option>
				<option value="soll"<?=$CFG['TAX_WISE'] == "soll" ? ' selected=""' : '';?>><?=$l['SOLL'];?></option>
			</select>

			<a href="?p=settings&tab=countries&action=eu&status=<?=$CFG['EU_VAT'] ? 0 : 1;?>" class="btn btn-default"><?=$CFG['EU_VAT'] ? $l['EUVATDE'] : $l['EUVATAC'];?></a><?php } else {?><a href="?p=settings&tab=countries&action=taxes&status=1" class="btn btn-warning"><?=$l['TAXAC'];?></a><?php }?>

			<select name="auto_import" class="form-control" onchange="if (confirm('<?=$l['AUTOIMPORTSURE'];?>')) { form.submit() } else { $(this).val(''); }">
				<option value="">- <?=$l['AUTOIMPORT'];?> -</option>
				<option value="eu"><?=$l['EUCOUNTRIES'];?></option>
				<option value="euch"><?=$l['EUCOUNTRIESCH'];?></option>
			</select>
			<br /><br /></form>

			<form method="POST"><div class="table-responsive"><table class="table table-bordered table-striped">
				<tr>
					<th width="20px"><input type="checkbox" id="checkall" onclick="javascript:check_all(this.checked);" /></th>
					<th><?=$l['COUNTRY'];?></th>
					<?php if ($CFG['TAXES']) {?><th><?=$l['TAXES'];?></th><?php }?>
					<th width="61px"></th>
				</tr>

				<?php

        $sql = $db->query("SELECT * FROM client_countries ORDER BY name ASC");
        if ($sql->num_rows == 0) {
            ?>
					<tr>
						<td colspan="6"><center><?=$l['COUNTRYNT'];?></center></td>
					</tr>
				<?php
} else {
            while ($u = $sql->fetch_object()) {
                ?>
						<tr>
							<td><input type="checkbox" name="countries[]" onclick="javascript:toggle();" value="<?=$u->ID;?>" <?=$CFG['DEFAULT_COUNTRY'] == $u->ID ? ' disabled="disabled"' : ' class="checkbox"';?> /></td>
							<td><?=$CFG['DEFAULT_COUNTRY'] == $u->ID ? "<b>$u->name</b>" : (!$u->active ? '<font color="red">' : '') . $u->name . (!$u->active ? '</font>' : '');?><?php if ($CFG['DEFAULT_COUNTRY'] != $u->ID && $u->active) {?> <a href="./?p=settings&tab=countries&default=<?=$u->ID;?>"><i class="fa fa-star"></i></a><?php }?></td>
							<?php if ($CFG['TAXES']) {?><td><?=$u->tax;?> (<?=$nfo->format($u->percent);?> %)</td><?php }?>
							<td width="61px"><a href="?p=settings&tab=countries&edit=<?=$u->ID;?>"><i class="fa fa-pencil fa-lg"></i></a>&nbsp;&nbsp;<a href="?p=settings&tab=countries&delete=<?=$u->ID;?>" onclick="return confirm('<?=$l['COUNTRYRD'];?>');"><i class="fa fa-times fa-lg"></i></a></td>
						</tr>
					<?php
}
        }
        ?>
			</table></div><?=$l['SELECTED'];?>: <input type="submit" name="activate" class="btn btn-success" value="<?=$l['ACTIVATE'];?>" /> <input type="submit" name="deactivate" class="btn btn-warning" value="<?=$l['DEACTIVATE'];?>" /></form>
<?php } else if ($tab == "currencies") {
        if (isset($_GET['auto'])) {
            if ($_GET['auto'] != "1") {
                $auto = 0;
            } else {
                $auto = 1;
            }

            $db->query("UPDATE `cronjobs` SET `active` = '$auto' WHERE `key` = 'currency' LIMIT 1");
            if ($db->affected_rows > 0) {
                alog("settings", "cronjob_status", "currency", $auto);
                if ($auto) {
                    $suc = $l['CURAUT1'];
                } else {
                    $suc = $l['CURAUT0'];
                }

            }
        }

        $active = (bool) $db->query("SELECT ID FROM `cronjobs` WHERE `active` = 1 AND `key` = 'currency' LIMIT 1")->num_rows;
        ?>
<?=isset($suc) ? "<div class=\"alert alert-success\">$suc</div>" : "";?>

				<?php
if (isset($_GET['action']) && $_GET['action'] == "add") {
            if (isset($_POST['submit'])) {
                try {
                    foreach ($_POST as $k => $v) {
                        $vari = "post_" . strtolower($k);
                        $$vari = $db->real_escape_string($v);
                    }

                    if (!isset($post_name) || $post_name == "") {
                        throw new Exception($l['CURERR1']);
                    }

                    if ($db->query("SELECT ID FROM currencies WHERE name = '$post_name'")->num_rows > 0) {
                        throw new Exception($l['CURERR2']);
                    }

                    if (!isset($post_currency_code) || $post_currency_code == "") {
                        throw new Exception($l['CURERR3']);
                    }

                    if ($db->query("SELECT ID FROM currencies WHERE currency_code = '$post_currency_code'")->num_rows > 0) {
                        throw new Exception($l['CURERR4']);
                    }

                    $conversion_rate = $nfo->phpize($post_conversion_rate);
                    if (!isset($post_conversion_rate) || !is_numeric($conversion_rate) || $conversion_rate <= 0) {
                        throw new Exception($l['CURERR6']);
                    }

                    if (!isset($post_round)) {
                        $round = -1;
                    } else {
                        $round = doubleval($nfo->phpize($post_round));
                    }

                    $db->query("INSERT INTO currencies (name, prefix, suffix, conversion_rate, currency_code, round) VALUES ('$post_name', '$post_prefix', '$post_suffix', '$conversion_rate', '$post_currency_code', $round)");

                    alog("settings", "currency_add", $db->insert_id, $post_name);

                    echo '<div class="alert alert-success">' . $l['CURRENCY_ADDED'] . '</div>';
                } catch (Exception $exc) {
                    echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $exc->getMessage() . '</div>';
                }
            }

            if (!isset($_POST['submit']) || isset($exc)) {
                ?>
				<form role="form" method="POST">
				  <div class="form-group">
				    <label><?=$l['CURANDCODE'];?></label>
				    <div class="row">
						<div class="col-md-10"><input type="text" value="<?=isset($_POST['name']) ? $_POST['name'] : "";?>" name="name" placeholder="<?=$l['NAMEOFCUR'];?>" class="form-control"></div>
						<div class="col-md-2"><input type="text" value="<?=isset($_POST['currency_code']) ? $_POST['currency_code'] : "";?>" name="currency_code" placeholder="<?=$l['CURCODEP'];?>" class="form-control"></div>
				  	</div>
				  </div>
				<div class="form-group">
					<label><?=$l['CURPRESUF'];?></label>
					<div class="row"><div class="col-md-6"><input type="text" name="prefix" value="<?=isset($_POST['prefix']) ? $_POST['prefix'] : "";?>" placeholder="<?=$l['CURPREP'];?>" class="form-control"></div>
				<div class="col-md-6"> <input type="text" name="suffix" value="<?=isset($_POST['suffix']) ? $_POST['suffix'] : "";?>" placeholder="<?=$l['CURSUFP'];?>" class="form-control"> </div></div></div>

				<div class="form-group">
					<label><?=$l['CURROUND'];?></label>
					<div class="input-group">
						<span class="input-group-addon">
							<input type="checkbox" id="round"<?=isset($_POST['round']) ? ' checked=""' : '';?> />
						</span>
						<input type="text" class="form-control" id="round_txt" placeholder="<?=$nfo->format(0.01);?>" value="<?=isset($_POST['round']) ? $_POST['round'] : $nfo->format(0);?>"<?=!isset($_POST['round']) ? ' disabled=""' : ' name="round"';?>>
					</div>
				</div>

				<script>
				$("#round").click(function(e) {
					if (e.target.checked) {
						$("#round_txt").prop("disabled", false).attr("name", "round");
					} else {
						$("#round_txt").prop("disabled", true).removeAttr("name");
					}
				});
				</script>

				<?php
try { $prefix = $cur->getPrefix();} catch (CurrencyException $ex) {$prefix = false;}
                try { $suffix = $cur->getSuffix();} catch (CurrencyException $ex) {$suffix = false;}
                ?>
				<div class="form-group">
					<label><?=$l['CUREXRATE'];?></label>
					<?php if (($prefix !== false && !empty($prefix)) || ($suffix !== false && !empty($suffix))) {?><div class="input-group"><?php }?>
						<?php if ($prefix !== false && !empty($prefix)) {?><div class="input-group-addon"><?=$prefix;?></div><?php }?>
						<input type="text" name="conversion_rate" value="<?=isset($_POST['conversion_rate']) ? $_POST['conversion_rate'] : $nfo->format(1, 8, 0);?>" placeholder="<?=$l['PLEASEENTER'];?>" class="form-control">
						<?php if (false !== $suffix && !empty($suffix)) {?><div class="input-group-addon"><?=$suffix;?></div><?php }?>
					<?php if (($prefix !== false && !empty($prefix)) || ($suffix !== false && !empty($suffix))) {?></div><?php }?>
					<p class="help-block"><?=$l['CUREXRATEH'];?></p>
				</div>
			  <center><input type="submit" name="submit" class="btn btn-primary" value="<?=$l['CURADD'];?>"></center></form><hr/>
			  <?php }}?>

	  		<?php
if (isset($_GET['edit']) && $_GET['edit'] > 0 && $db->query("SELECT * FROM currencies WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->num_rows == 1) {
            $info = $db->query("SELECT * FROM currencies WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->fetch_object();

            if (isset($_POST['submit'])) {
                try {
                    foreach ($_POST as $k => $v) {
                        $vari = "post_" . strtolower($k);
                        $$vari = $db->real_escape_string($v);
                    }

                    if (!isset($post_name) || $post_name == "") {
                        throw new Exception($l['CURERR1']);
                    }

                    if ($db->query("SELECT ID FROM currencies WHERE name = '$post_name' AND ID != " . $info->ID)->num_rows > 0) {
                        throw new Exception($l['CURERR2']);
                    }

                    if (!isset($post_currency_code) || $post_currency_code == "") {
                        throw new Exception($l['CURERR3']);
                    }

                    if ($db->query("SELECT ID FROM currencies WHERE currency_code = '$post_currency_code' AND ID != " . $info->ID)->num_rows > 0) {
                        throw new Exception($l['CURERR4']);
                    }

                    if ($info->base == 1) {
                        $post_conversion_rate = 1;
                    } else {
                        $post_conversion_rate = $nfo->phpize($post_conversion_rate);
                        if (!isset($post_conversion_rate) || !is_numeric($post_conversion_rate) || $post_conversion_rate <= 0) {
                            throw new Exception($l['CURERR6']);
                        }

                    }

                    if (!isset($post_round)) {
                        $round = -1;
                    } else {
                        $round = doubleval($nfo->phpize($post_round));
                    }

                    $db->query("UPDATE currencies SET name = '$post_name', prefix = '$post_prefix', suffix = '$post_suffix', currency_code = '$post_currency_code', conversion_rate = '$post_conversion_rate', round = $round WHERE ID = " . $info->ID . " LIMIT 1");

                    alog("settings", "currency_edit", $post_name, $info->ID);

                    echo '<div class="alert alert-success">' . $l['CUREDITED'] . '</div>';
                    unset($_POST);

                    $info = $db->query("SELECT * FROM currencies WHERE ID = '" . $db->real_escape_string($_GET['edit']) . "'")->fetch_object();
                } catch (Exception $exc) {
                    echo '<div class="alert alert-danger"><b>' . $lang['GENERAL']['ERROR'] . '</b> ' . $exc->getMessage() . '</div>';
                }
            }

            if (!isset($_POST['round']) && $info->round >= 0) {
                $_POST['round'] = $nfo->format($info->round);
            }
            ?>
				<form role="form" method="POST">
	  <div class="form-group">
	    <label><?=$l['CURANDCODE'];?></label>
	    <div class="row">
			<div class="col-md-10"><input type="text" value="<?=isset($_POST['name']) ? $_POST['name'] : $info->name;?>" name="name" placeholder="<?=$l['NAMEOFCUR'];?>" class="form-control"></div>
			<div class="col-md-2"><input type="text" value="<?=isset($_POST['currency_code']) ? $_POST['currency_code'] : $info->currency_code;?>" name="currency_code" placeholder="<?=$l['CURCODEP'];?>" class="form-control"></div>
	  	</div>
	  </div>
	<div class="form-group">
				<label><?=$l['CURPRESUF'];?></label>
				<div class="row"><div class="col-md-6"><input type="text" name="prefix" value="<?=isset($_POST['prefix']) ? $_POST['prefix'] : $info->prefix;?>" placeholder="<?=$l['CURPREP'];?>" class="form-control"></div>
	<div class="col-md-6"> <input type="text" name="suffix" value="<?=isset($_POST['suffix']) ? $_POST['suffix'] : $info->suffix;?>" placeholder="<?=$l['CURSUFP'];?>" class="form-control"> </div></div>
			</div>

		<?php if (!$info->base) {?>
		<div class="form-group">
			<label><?=$l['CURROUND'];?></label>
			<div class="input-group">
				<span class="input-group-addon">
					<input type="checkbox" id="round"<?=isset($_POST['round']) ? ' checked=""' : '';?> />
				</span>
				<input type="text" class="form-control" id="round_txt" placeholder="<?=$nfo->format(0.01);?>" value="<?=isset($_POST['round']) ? $_POST['round'] : $nfo->format(0);?>"<?=!isset($_POST['round']) ? ' disabled=""' : ' name="round"';?>>
			</div>
		</div>

		<script>
		$("#round").click(function(e) {
			if (e.target.checked) {
				$("#round_txt").prop("disabled", false).attr("name", "round");
			} else {
				$("#round_txt").prop("disabled", true).removeAttr("name");
			}
		});
		</script>
		<?php }?>

		<?php
try { $prefix = $cur->getPrefix();} catch (CurrencyException $ex) {$prefix = false;}
            try { $suffix = $cur->getSuffix();} catch (CurrencyException $ex) {$suffix = false;}
            ?>
		<div class="form-group">
			<label><?=$l['CUREXRATE'];?></label>
			<?php if ($info->base == 1) {?>
				<br /><?=$prefix . $nfo->format(1, 8) . $suffix;?><p class="help-block"><?=$l['CUREXRATENC'];?></p>
			<?php } else {?>
				<?php if (($prefix !== false && !empty($prefix)) || ($suffix !== false && !empty($suffix))) {?><div class="input-group"><?php }?>
					<?php if ($prefix !== false && !empty($prefix)) {?><div class="input-group-addon"><?=$prefix;?></div><?php }?>
					<input type="text" name="conversion_rate" value="<?=isset($_POST['conversion_rate']) ? $_POST['conversion_rate'] : $nfo->format($info->conversion_rate, 8, 0);?>" placeholder="<?=$l['PLEASEENTER'];?>" class="form-control">
					<?php if (false !== $suffix && !empty($suffix)) {?><div class="input-group-addon"><?=$suffix;?></div><?php }?>
				<?php if (($prefix !== false && !empty($prefix)) || ($suffix !== false && !empty($suffix))) {?></div><?php }?>
				<p class="help-block"><?=$l['CUREXRATEH'];?></p>
			<?php }?>
		</div>
	  <center><input type="submit" name="submit" class="btn btn-primary" value="<?=$l['CUREDIT'];?>"></center></form><hr/><?php }

        if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $db->query("DELETE FROM  currencies WHERE ID = '" . $db->real_escape_string($_GET['delete']) . "' AND base != 1 LIMIT 1");
            if ($db->affected_rows > 0) {
                echo '<div class="alert alert-success">' . $l['CURDELETED'] . '</div>';
                alog("settings", "currency_delete", $_GET['delete']);
            }
        }

        if (isset($_GET['base']) && is_numeric($_GET['base']) && $db->query("SELECT ID FROM currencies WHERE ID = " . intval($_GET['base']) . " AND base = 0 AND conversion_rate = 1 LIMIT 1")->num_rows == 1 && $db->query("UPDATE currencies SET base = 0") && $db->query("UPDATE currencies SET base = 1 WHERE ID = " . $_GET['base'] . " LIMIT 1")) {
            echo '<div class="alert alert-success">' . $l['CURBASECHANGED'] . '</div>';
            alog("settings", "base_currency", $_GET['base']);
        }
        ?>
				<a href="?p=settings&tab=currencies&action=add" class="btn btn-success"><?=$l['ADDNEWCUR'];?></a> <a href="?p=settings&tab=currencies&auto=<?=$active ? 0 : 1;?>" class="btn btn-warning"><?=$active ? $l['CURAUTO0'] : $l['CURAUTO1'];?></a><br /><br />

				<?php
if (isset($_POST['calc']) && isset($_POST['from']) && CurrencyManager::getCurrency($_POST['from']) && isset($_POST['to']) && CurrencyManager::getCurrency($_POST['to']) && $nfo->phpize($_POST['amount'])) {
            ?>
				<div class="alert alert-info"><?=$cur->infix($nfo->format($nfo->phpize($_POST['amount'])), $_POST['from']);?> <?=$l['CUREX1'];?> <?=$cur->infix($nfo->format($cur->convertAmount($_POST['from'], $nfo->phpize($_POST['amount']), $_POST['to'])), $_POST['to']);?>.</div>
				<?php
} else {
            ?>
				<form class="form-inline" method="POST">
				  <div class="form-group">
				    <input type="numeric" name="amount" class="form-control" style="max-width: 100px;" placeholder="<?=$nfo->placeholder();?>" value="<?=$nfo->placeholder();?>">
				    <select name="from" class="form-control" style="max-width: 70px;">
						<?php $selected = isset($_POST['from']) ? $_POST['from'] : $cur->getBaseCurrency();
            $sql = $db->query("SELECT currency_code FROM currencies ORDER BY currency_code ASC");while ($row = $sql->fetch_object()) {?>
						<option<?=$row->currency_code == $selected ? ' selected="selected"' : '';?>><?=$row->currency_code;?></option>
						<?php }?>
				    </select>

				  <span style="margin-left: 5px; margin-right: 5px;"><?=$l['CURTO'];?></span>

				    <select name="to" class="form-control" style="max-width: 70px;">
						<?php $selected = isset($_POST['to']) ? $_POST['to'] : $cur->getBaseCurrency();
            $sql = $db->query("SELECT currency_code FROM currencies ORDER BY currency_code ASC");while ($row = $sql->fetch_object()) {?>
						<option<?=$row->currency_code == $selected ? ' selected="selected"' : '';?>><?=$row->currency_code;?></option>
						<?php }?>
				    </select>
				  	<button type="submit" name="calc" class="btn btn-primary"><?=$l['CURCALC'];?></button>
				</div>
				</form><br />
				<?php }?>

				<div class="table-responsive"><table class="table table-bordered table-striped">
					<tr>
						<th><?=$l['CURRENCY'];?></th>
						<th><?=$l['CURDISPLAY'];?></th>
						<th><?=$l['CURRATE'];?></th>
						<th width="61px"></th>
					</tr>

					<?php

        $sql = $db->query("SELECT * FROM currencies ORDER BY name ASC");
        if ($sql->num_rows == 0) {
            ?>
						<tr>
							<td colspan="6"><center><?=$l['CURNT'];?></center></td>
						</tr>
					<?php
} else {
            $baseSql = $db->query("SELECT * FROM currencies WHERE conversion_rate = 1 AND base = 1 LIMIT 1");
            if ($baseSql->num_rows == 1) {
                $baseInfo = $baseSql->fetch_object();

                $baseCur = $baseInfo->currency_code;
                $basePrefix = $baseInfo->prefix;
                $baseSuffix = $baseInfo->suffix;
            }

            try { $prefix = $cur->getPrefix();} catch (CurrencyException $ex) {$prefix = false;}
            try { $suffix = $cur->getSuffix();} catch (CurrencyException $ex) {$suffix = false;}

            while ($u = $sql->fetch_object()) {
                $style = isset($baseCur) && $baseCur == $u->currency_code ? "background-color:azure !important;" : "";
                ?>
							<tr>
								<td style="<?=$style;?>"><?=$u->name;?> (<?=$u->currency_code;?>) <?php if ((!isset($baseCur) || $baseCur != $u->currency_code) && $u->conversion_rate == 1) {
                    echo "<a href='?p=settings&tab=currencies&base=" . $u->ID . "'><i class='fa fa-star'></i></a>";
                }
                ?></td>
								<td style="<?=$style;?>"><?=$cur->formatAmount(100, 2, 0, $u->currency_code);?></td>
								<td style="<?=$style;?>"><?php if (isset($baseCur) && $baseCur == $u->currency_code) {echo $l['BASECUR'];} else {?><?=$prefix;?><?=$nfo->format($u->conversion_rate, 8);?><?=$suffix;?><?php }?></td>
								<td style="<?=$style;?>" width="61px"><a href="?p=settings&tab=currencies&edit=<?=$u->ID;?>"><i class="fa fa-pencil fa-lg"></i></a><?php if (isset($baseCur) && $baseCur != $u->currency_code) {?>&nbsp;&nbsp;<a href="?p=settings&tab=currencies&delete=<?=$u->ID;?>" onclick="return confirm('<?=$l['CURREADEL'];?>');"><i class="fa fa-times fa-lg"></i></a><?php }?></td>
							</tr>
						<?php
}
        }
        ?>
				</table></div>
<?php } else if ($tab == "social_login") {?>
<p style="text-align: justify;"><?=$l['SLINTRO'];?></p>
<form accept-charset="UTF-8" method="post">
	<fieldset>
		<div class="checkbox" style="margin-top: 0;">
			<label>
				<input type="checkbox" name="facebook_login" id="facebook_login" value="1"<?=$CFG['FACEBOOK_LOGIN'] == "1" ? " checked=\"checked\"" : "";?>>
				<?=$l['SLFB'];?>
			</label>
		</div>

		<div class="form-group facebook">
			<label><?=$l['SLFBAI'];?> <a class="btn btn-default btn-xs" data-toggle="modal" data-target="#facebook_app"><?=$l['SLREQ'];?></a></label>
			<input name="facebook_id" value="<?=$CFG['FACEBOOK_ID'];?>" placeholder="123456789012345" class="form-control" />
		</div>

		<div class="form-group facebook">
			<label><?=$l['SLFBKEY'];?></label>
			<input name="facebook_secret" value="<?=$CFG['FACEBOOK_SECRET'];?>" placeholder="ienv918m2ind91nsl9n21mqjdcn28nsinqi1l" class="form-control" />
		</div>

		<div class="modal fade" tabindex="-1" role="dialog" id="facebook_app">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title"><?=$l['SLREQFB'];?></h4>
		      </div>
		      <div class="modal-body">
		        <ol>
		        	<?=$l['SLFBTEXT'];?>
		        </ol>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->

		<div class="checkbox" style="margin-top: 0;">
			<label>
				<input type="checkbox" name="twitter_login" id="twitter_login" value="1"<?=$CFG['TWITTER_LOGIN'] == "1" ? " checked=\"checked\"" : "";?>>
				<?=$l['SLTW'];?>
			</label>
		</div>

		<div class="form-group twitter">
			<label><?=$l['SLTWID'];?> <a class="btn btn-default btn-xs" data-toggle="modal" data-target="#twitter_app"><?=$l['SLREQ'];?></a></label>
			<input name="twitter_id" value="<?=$CFG['TWITTER_ID'];?>" placeholder="Do2nNFOnqone2938LQ" class="form-control" />
		</div>

		<div class="form-group twitter">
			<label><?=$l['SLTWKEY'];?></label>
			<input name="twitter_secret" value="<?=$CFG['TWITTER_SECRET'];?>" placeholder="NiNImdfiNpfnINinf982389nIDsbgii198UBoniwenfMq" class="form-control" />
		</div>

		<div class="modal fade" tabindex="-1" role="dialog" id="twitter_app">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title"><?=$l['SLREQTW'];?></h4>
		      </div>
		      <div class="modal-body">
		        <ol>
		        	<?=$l['SLTWTEXT'];?>
		        </ol>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->

		<div class="checkbox" style="margin-top: 0;">
			<label>
				<input type="checkbox" name="social_login_toggle" value="1"<?=$CFG['SOCIAL_LOGIN_TOGGLE'] == "1" ? " checked=\"checked\"" : "";?>>
				<?=$l['SLCCD'];?>
			</label>
		</div>

		<style>
		<?php
if (!$CFG['FACEBOOK_LOGIN']) {
        echo ".facebook { display: none; }";
    }

        if (!$CFG['TWITTER_LOGIN']) {
            echo ".twitter { display: none; }";
        }

        $additionalJS .= '$("#facebook_login").change(function(){ if($(this).is(":checked")) $(".facebook").show(); else $(".facebook").hide();});';
        $additionalJS .= '$("#twitter_login").change(function(){ if($(this).is(":checked")) $(".twitter").show(); else $(".twitter").hide();});';
        ?>
		</style>

		<div class="form-group">
          <button type="submit" name="change" class="btn btn-primary btn-block">
            <?=$l['SAVEANY'];?>
          </button>
        </div>
    </fieldset>
</form>
<?php } else if ($tab == "telegram") {?>
<p style="text-align: justify;"><?=$l['TELEGRAMINTRO'];?></p>
<form accept-charset="UTF-8" role="form" id="login-form" method="post">
	<fieldset>
		<div class="form-group">
			<label><?=$l['TGM'];?></label><br />
			<label class="radio-inline radio-messenger">
				<input type="radio" name="messenger" id="telegram"<?=$CFG['TELEGRAM_CHAT'] != "mattermost" ? ' checked=""' : '';?>> Telegram
			</label>
			<label class="radio-inline radio-messenger">
				<input type="radio" name="messenger" id="mattermost"<?=$CFG['TELEGRAM_CHAT'] == "mattermost" ? ' checked=""' : '';?>> Mattermost
			</label>
			<label class="radio-inline radio-messenger">
				<input type="radio" name="messenger" id="slack"<?=$CFG['TELEGRAM_CHAT'] == "slack" ? ' checked=""' : '';?>> Slack
			</label>
			<label class="radio-inline radio-messenger">
				<input type="radio" name="messenger" id="discord"<?=$CFG['TELEGRAM_CHAT'] == "discord" ? ' checked=""' : '';?>> Discord
			</label>
		</div>

		<script>
		chat_id = "<?=$CFG['TELEGRAM_CHAT'];?>";

		$(document).ready(function() {
			function messengerOptions() {
				if ($("#telegram").is(":checked")) {
					$("#token").show().find("label").html("<?=$l['TGBT'];?>");
					$("#chat_id").show();

					if (chat_id == "mattermost" || chat_id == "slack" || chat_id == "discord") {
						chat_id = "";
					}

					$("[name=telegram_chat]").val(chat_id.toString());
				} else if ($("#mattermost").is(":checked")) {
					$("#chat_id").hide();
					$("#token").show().find("label").html("<?=$l['TGWU'];?>");

					if ($("[name=telegram_chat]").val() != "mattermost" && $("[name=telegram_chat]").val() != "slack" && $("[name=telegram_chat]").val() != "discord") {
						chat_id = $("[name=telegram_chat]").val();
					}

					$("[name=telegram_chat]").val("mattermost");
				} else if ($("#slack").is(":checked")) {
					$("#chat_id").hide();
					$("#token").show().find("label").html("<?=$l['TGWU'];?>");

					if ($("[name=telegram_chat]").val() != "mattermost" && $("[name=telegram_chat]").val() != "slack" && $("[name=telegram_chat]").val() != "discord") {
						chat_id = $("[name=telegram_chat]").val();
					}

					$("[name=telegram_chat]").val("slack");
				} else if ($("#discord").is(":checked")) {
					$("#chat_id").hide();
					$("#token").show().find("label").html("<?=$l['TGWU'];?>");

					if ($("[name=telegram_chat]").val() != "mattermost" && $("[name=telegram_chat]").val() != "slack" && $("[name=telegram_chat]").val() != "discord") {
						chat_id = $("[name=telegram_chat]").val();
					}

					$("[name=telegram_chat]").val("discord");
				}
			}

			$(".radio-messenger").change(messengerOptions);

			messengerOptions();

			$("#refresh_tgchats").click(function(e) {
				e.preventDefault();
				var i = $(this).find("i").addClass("fa-spin");

				$.ajax({
					url: "https://api.telegram.org/bot" + $("[name=telegram_token]").val() + "/getUpdates",
					success: function(r) {
						i.removeClass("fa-spin");

						var groups = {};

						if (r.ok) {
							for (var k in r.result) {
								c = r.result[k];
								if (c.message.chat.type == "group") {
									groups[c.message.chat.id] = c.message.chat.title;
								} else if (c.message.chat.type == "private") {
									groups[c.message.chat.id] = c.message.chat.username;
								}
							}

							if (groups.length == 0) {
								$("#telegram_chats").html('<option disabled="" selected=""><?=$l['TGNG'];?></option>');
							} else {
								$("#telegram_chats").html('<option disabled="" selected=""><?=$l['TGCG'];?></option>');

								for (var id in groups) {
									var name = groups[id];
									$("#telegram_chats").html($("#telegram_chats").html() + '<option value="' + id + '">' + name + '</option>');
								}
							}
						} else {
							alert("<?=$l['TGTE'];?>");
						}
					},
					error: function() {
						i.removeClass("fa-spin");
						alert("<?=$l['TGWT'];?>");
					}
				});
			});

			$("#telegram_chats").change(function() {
				$("[name=telegram_chat]").val($(this).val());
			});
		});
		</script>

		<div class="form-group" id="token" style="display: none;">
			<label><?=$l['TGBT'];?></label>
			<input name="telegram_token" value="<?=$CFG['TELEGRAM_TOKEN'];?>" placeholder="<?=$l['TGGEHEIM'];?>" class="form-control" />
		</div>

		<div class="form-group" id="chat_id" style="display: none;">
			<label><?=$l['TGCI'];?></label>
			<div class="input-group" style="margin-bottom: 10px;">
				<span class="input-group-addon"><a href="#" id="refresh_tgchats"><i class="fa fa-refresh"></i></a></span>
				<select id="telegram_chats" class="form-control">
					<option disabled="" selected=""><?=$l['TGPCL'];?></option>
				</select>
			</div>
			<input name="telegram_chat" value="<?=$CFG['TELEGRAM_CHAT'];?>" placeholder="-123456789" class="form-control" />
		</div>

		<div class="form-group">
			<label><?=$l['TGNOTIFICATIONS'];?></label> (<a href="javascript:select_alln()"><?=$l['TGSELALL'];?></a> | <a href="javascript:deselect_alln()"><?=$l['TGDESALL'];?></a>)
		<script type="text/javascript">
		function select_alln() {
			for ( i = 0; i <= document.getElementsByName("telegram_notifications[]").length; i++ ){
				document.getElementsByName("telegram_notifications[]")[i].checked = "checked";
			}
		}

		function deselect_alln() {
			for ( i = 0; i <= document.getElementsByName("telegram_notifications[]").length; i++ ){
				document.getElementsByName("telegram_notifications[]")[i].checked = "";
			}
		}
		</script>
		<?php
$info = new stdClass;
        $info->notifications = unserialize($CFG['TELEGRAM_NOTIFICATIONS']);
        if (!is_array($info->notifications)) {
            $info->notifications = array();
        }

        $i = 1;
        $mailSql = $db->query("SELECT name FROM email_templates WHERE admin_notification = 1");
        while ($row = $mailSql->fetch_object()) {
            $name = trim($row->name);
            if ($i == 1) {
                echo '<div class="row">';
            }

            ?>
			<div class="col-md-4"><label style="font-weight:normal;"><input type="checkbox" <?php if (in_array($name, $info->notifications)) {
                echo "checked=\"checked\"";
            }
            ?> name="telegram_notifications[]" value="<?=$name;?>"> <?=$name;?></label></div>
		<?php if ($i == 3) {
                echo '</div>';
            }
            ?>
		<?php $i++;if ($i == 4) {
                $i = 1;
            }
        }?>
		</div>

		<div class="row">
			<div class="col-md-6">
	      <button type="submit" name="change" class="btn btn-primary btn-block">
	        <?=$l['SAVEANY'];?>
	      </button>
			</div>
			<div class="col-md-6">
				<button type="button" id="sendTelegramTest" class="btn btn-default btn-block">
					<?=$l['TGSENDTEST'];?>
				</button>
			</div>
    </div>

		<script>
		$("#sendTelegramTest").click(function(){
			$.post("?p=ajax&action=telegram_test", {
				"chat": $("[name=telegram_chat]").val(),
				"token": $("[name=telegram_token]").val(),
				csrf_token: "<?=CSRF::raw();?>",
			}, function(r){
				alert(r);
			});
		});
		</script>
  </fieldset>
</form>
<?php } else if ($tab == "cgroups") {
        if (isset($_GET['del']) && $db->query("DELETE FROM client_groups WHERE ID = " . intval($_GET['del'])) && $db->affected_rows) {
            echo '<div class="alert alert-success">' . $l['CGDELETED'] . '</div>';
            alog("settings", "cgroup_del", $_GET['del']);
        }

        if (isset($_GET['default'])) {
            $def = intval($_GET['default']);

            if ($def == 0 || $db->query("SELECT 1 FROM client_groups WHERE ID =$def")->num_rows) {
                $CFG['DEFAULT_CGROUP'] = $def;
                $db->query("UPDATE settings SET `value` = '$def' WHERE `key` = 'default_cgroup'");
                alog("settings", "cgroup_default", $def);
            }

        }

        if (isset($_POST['save_groups'])) {
            foreach ($_POST['name'] as $id => $name) {
                $color = $_POST['color'][$id];

                if (empty($name)) {
                    continue;
                }

                if ($id == "new") {
                    $db->query("INSERT INTO client_groups (`name`, `color`) VALUES ('" . $db->real_escape_string($name) . "', '" . $db->real_escape_string($color) . "')");
                } else if (is_numeric($id)) {
                    $db->query("UPDATE client_groups SET `name` = '" . $db->real_escape_string($name) . "', `color` = '" . $db->real_escape_string($color) . "' WHERE ID = " . intval($id));
                }

            }

            alog("settings", "cgroup_save");

            echo '<div class="alert alert-success">' . $l['CGSAVED'] . '</div>';
        }
        ?>
	<form method="POST">
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="30px"></th>
			<th><?=$l['CGROUP'];?></th>
			<th width="40%"><?=$l['COLOR'];?></th>
			<th width="30px"></th>
		</tr>

		<tr>
				<td><?php if ($CFG['DEFAULT_CGROUP'] != 0) {?><a href="?p=settings&tab=cgroups&default=0"><i class="fa fa-star-o"></i></a><?php } else {?><i class="fa fa-star"></i><?php }?></td>
			<td><?=$l['CGDEF'];?></td>
			<td><i><?=$l['CGNONE'];?></i></td>
			<td></td>
		</tr>

		<?php
$sql = $db->query("SELECT * FROM client_groups ORDER BY name ASC");
        while ($g = $sql->fetch_object()) {
            ?>
			<tr>
				<td><?php if ($CFG['DEFAULT_CGROUP'] != $g->ID) {?><a href="?p=settings&tab=cgroups&default=<?=$g->ID;?>"><i class="fa fa-star-o"></i></a><?php } else {?><i class="fa fa-star"></i><?php }?></td>
				<td><input type="text" name="name[<?=$g->ID;?>]" class="form-control input-sm" value="<?=$g->name;?>"></td>
				<td>
					<div id="color_<?=$g->ID;?>" class="input-group colorpicker-component">
					    <span class="input-group-addon"><i></i></span>
					    <input type="text" value="<?=$g->color;?>" name="color[<?=$g->ID;?>]" class="form-control input-sm" />
					</div>
					<?php
$additionalJS .= "$('#color_{$g->ID}').colorpicker();";
            ?>
				</td>
				<td style="vertical-align: middle;"><a href="?p=settings&tab=cgroups&del=<?=$g->ID;?>"><i class="fa fa-times"></i></a></td>
			</tr>
			<?php
}
        ?>

		<tr>
			<td></td>
			<td><input type="text" name="name[new]" class="form-control input-sm" placeholder="<?=$l['CGNEW'];?>"></td>
			<td>
				<div id="color_new" class="input-group colorpicker-component">
				    <span class="input-group-addon"><i></i></span>
				    <input type="text" value="#333333" name="color[new]" class="form-control input-sm" />
				</div>
				<?php
$additionalJS .= "$('#color_new').colorpicker();";
        ?>
			</td>
			<td></td>
		</tr>
	</table>
</div>
<input type="submit" name="save_groups" value="<?=$l['CGSAVE'];?>" class="btn btn-primary btn-block" /><br />
</form>
<?php } else if ($tab == "salutation") {
        if (isset($_POST['fallback_salutation']) && is_array($_POST['fallback_salutation'])) {
            $CFG['FALLBACK_SALUTATION'] = serialize($_POST['fallback_salutation']);
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($CFG['FALLBACK_SALUTATION']) . "' WHERE `key` = 'fallback_salutation'");
        }

        if (isset($_POST['new_salutation'])) {
            $db->query("INSERT INTO salutations (`salutation`) VALUES ('" . $db->real_escape_string($_POST['new_salutation']) . "')");
        }

        if (isset($_GET['del'])) {
            $id = intval($_GET['del']);
            $db->query("DELETE FROM salutations WHERE ID = $id");
        }
        ?>
<form method="POST" action="?p=settings&tab=salutation">
<label><?=$l['SALFALLBACK'];?></label>

<?php
$salutations = @unserialize($CFG['FALLBACK_SALUTATION']);
        if (!is_array($salutations)) {
            $salutations = [];
        }

        foreach ($languages as $k => $n) {
            $v = $salutations[$k] ?? "";
            ?>
<input type="text" style="margin-bottom: 5px;" name="fallback_salutation[<?=$k;?>]" value="<?=htmlentities($v);?>" placeholder="<?=$n;?>" class="form-control">
<?php }?>

<p class="help-block"><?=$l['NOVARAVA'];?></p>

<input type="submit" class="btn btn-primary btn-block" value="<?=$lang['GENERAL']['SAVE'];?>">
</form>
<hr />
<form method="POST" action="?p=settings&tab=salutation" class="form-inline">
	<input type="text" name="new_salutation" class="form-control" placeholder="<?=$l['SALUTATION'];?>">

	<input type="submit" class="btn btn-success" value="<?=$l['ADDSAL'];?>">
	<p class="help-block">{firstName}, {lastName}, {firstNameFirstLetter}, {lastNameFirstLetter}, {city}</p>
</form>

<script src="res/js/bootstrap.min.js"></script>
<link href="res/xedit/css/bootstrap-editable.css" rel="stylesheet">
<script src="res/xedit/js/bootstrap-editable.min.js"></script>

<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th><?=$l['TIME'];?></th>
			<th><?=$l['LANGUAGE'];?></th>
			<th><?=$l['GENDER'];?></th>
			<th><?=$l['C_GROUP'];?></th>
			<th><?=$l['CUSTOMERTYPE'];?></th>
			<th><?=$l['COUNTRY'];?></th>
			<th><?=$l['SALUTATION'];?></th>
			<th width="28px"></th>
		</tr>

		<?php
function cgroup($id)
        {
            global $db, $CFG, $l;

            if ($id == 0) {
                return $l['NOCG'];
            }

            $sql = $db->query("SELECT name FROM client_groups WHERE ID = " . intval($id));
            return $sql->num_rows ? $sql->fetch_object()->name : "";
        }

        function country($id)
        {
            global $db, $CFG, $l;

            $sql = $db->query("SELECT name FROM client_countries WHERE ID = " . intval($id));
            return $sql->num_rows ? $sql->fetch_object()->name : "";
        }

        $sql = $db->query("SELECT * FROM salutations");
        while ($row = $sql->fetch_object()) {
            $n = "<center>-</center>";
            ?>
			<tr>
				<td><a href="#" class="time" data-name="time" data-pk="<?=$row->ID;?>" data-value="<?=$row->time;?>" data-placeholder="15:00 - 18:00"><?=$row->time ?: $n;?></a></td>
				<td><a href="#" class="language" data-name="language" data-pk="<?=$row->ID;?>" data-value="<?=$row->language;?>"><?=$row->language ? ($languages[$row->language] ?? "") : $n;?></a></td>
				<td><a href="#" class="gender" data-name="gender" data-pk="<?=$row->ID;?>" data-value="<?=$row->gender;?>"><?=$row->gender ? $l[$row->gender] : $n;?></a></td>
				<td><a href="#" class="cgroup" data-name="cgroup" data-pk="<?=$row->ID;?>" data-value="<?=$row->cgroup;?>"><?=$row->cgroup >= 0 ? cgroup($row->cgroup) : $n;?></a></td>
				<td><a href="#" class="ctype" data-name="b2b" data-pk="<?=$row->ID;?>" data-value="<?=$row->b2b;?>"><?=$row->b2b >= 0 ? ($row->b2b == 0 ? $l['B2C'] : $l['B2B']) : $n;?></a></td>
				<td><a href="#" class="country" data-name="country" data-pk="<?=$row->ID;?>" data-value="<?=$row->country;?>"><?=$row->country >= 0 ? country($row->country) : $n;?></a></td>
				<td><a href="#" class="salutation" data-name="salutation" data-pk="<?=$row->ID;?>"><?=htmlentities($row->salutation);?></a></td>
				<td><a href="?p=settings&tab=salutation&del=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
			</tr>
			<?php
}

        if (!$sql->num_rows) {
            echo "<tr><td colspan=\"8\"><center>{$l['NOSAL']}</center></td></tr>";
        }
        ?>
	</table>

	<script>
	$.fn.editable.defaults.mode = 'popup';
	$('.time').editable({
		type: 'text',
		url: '?p=settings&tab=salutation&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.language').editable({
		type: 'select',
		source: [
			{value: '', text: "-"},
			<?php foreach ($languages as $k => $n) {?>
			{value: '<?=$k;?>', text: "<?=$n;?>"},
			<?php }?>
		],
		url: '?p=settings&tab=salutation&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.gender').editable({
		type: 'select',
		source: [
			{value: '', text: "-"},
			{value: 'MALE', text: "<?=$l['MALE'];?>"},
			{value: 'FEMALE', text: "<?=$l['FEMALE'];?>"},
			{value: 'DIVERS', text: "<?=$l['DIVERS'];?>"},
		],
		url: '?p=settings&tab=salutation&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.cgroup').editable({
		type: 'select',
		source: [
			{value: '-1', text: "-"},
			{value: '0', text: "<?=$l['NOCG'];?>"},
			<?php
$sql = $db->query("SELECT ID, name FROM client_groups ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {?>
			{value: '<?=$row->ID;?>', text: "<?=($row->name);?>"},
			<?php }?>
		],
		url: '?p=settings&tab=salutation&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.ctype').editable({
		type: 'select',
		source: [
			{value: '-1', text: "-"},
			{value: '0', text: "<?=$l['B2C'];?>"},
			{value: '1', text: "<?=$l['B2B'];?>"},
		],
		url: '?p=settings&tab=salutation&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.country').editable({
		type: 'select',
		source: [
			{value: '-1', text: "-"},
			<?php
$sql = $db->query("SELECT ID, name FROM client_countries ORDER BY name ASC");
        while ($row = $sql->fetch_object()) {?>
			{value: '<?=$row->ID;?>', text: "<?=($row->name);?>"},
			<?php }?>
		],
		url: '?p=settings&tab=salutation&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.salutation').editable({
		type: 'text',
		url: '?p=settings&tab=salutation&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});
	</script>
</div>
<?php } else if ($tab == "branding") {
        if (isset($_POST['new_host'])) {
            $url = $db->real_escape_string("http://" . $_POST['new_host'] . "/");
            $db->query("INSERT INTO branding (`host`, `pageurl`) VALUES ('" . $db->real_escape_string($_POST['new_host']) . "', '$url')");
        }

        if (isset($_GET['del'])) {
            $id = intval($_GET['del']);
            $db->query("DELETE FROM branding WHERE ID = $id");
        }
        ?>
<form method="POST" action="?p=settings&tab=branding" class="form-inline" style="margin-bottom: 10px;">
	<input type="text" name="new_host" class="form-control" placeholder="<?=$l['HOSTNAME'];?>">

	<input type="submit" class="btn btn-success" value="<?=$l['ADDBRA'];?>">
</form>

<script src="res/js/bootstrap.min.js"></script>
<link href="res/xedit/css/bootstrap-editable.css" rel="stylesheet">
<script src="res/xedit/js/bootstrap-editable.min.js"></script>

<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th><?=$l['HOSTNAME'];?></th>
			<th><?=$l['PAGEURL'];?></th>
			<th><?=$l['PAGENAME'];?></th>
			<th><?=$l['PAGEMAIL'];?></th>
			<th><?=$l['DESIGN'];?></th>
			<th width="28px"></th>
		</tr>

		<tr>
			<td><b><?=$l['CATCHALL'];?></b></td>
			<td><?=$CFG['PAGEURL'];?></td>
			<td><?=$CFG['PAGENAME'];?></td>
			<td><?=$CFG['PAGEMAIL'];?></td>
			<td><?=ucfirst($CFG['THEME']);?></td>
			<td><center>-</center></td>
		</tr>

		<?php
$sql = $db->query("SELECT * FROM branding");
        while ($row = $sql->fetch_object()) {
            ?>
			<tr>
				<td><a href="#" class="host" data-name="host" data-pk="<?=$row->ID;?>" data-value="<?=$row->host;?>" data-placeholder="test.example.com"><?=$row->host;?></a></td>
				<td><a href="#" class="pageurl" data-name="pageurl" data-pk="<?=$row->ID;?>" data-value="<?=$row->pageurl;?>"><?=$row->pageurl ?: $CFG['PAGEURL'];?></a></td>
				<td><a href="#" class="pagename" data-name="pagename" data-pk="<?=$row->ID;?>" data-value="<?=$row->pagename;?>"><?=$row->pagename ?: $CFG['PAGENAME'];?></a></td>
				<td><a href="#" class="pagemail" data-name="pagemail" data-pk="<?=$row->ID;?>" data-value="<?=$row->pagemail;?>"><?=$row->pagemail ?: $CFG['PAGEMAIL'];?></a></td>
				<td><a href="#" class="design" data-name="design" data-pk="<?=$row->ID;?>" data-value="<?=$row->design ?: $CFG['THEME'];?>"><?=ucfirst($row->design ?: $CFG['THEME']);?></a></td>
				<td><a href="?p=settings&tab=branding&del=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
			</tr>
			<?php
}
        ?>
	</table>

	<script>
	$.fn.editable.defaults.mode = 'popup';
	$('.host').editable({
		type: 'text',
		url: '?p=settings&tab=branding&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.pageurl').editable({
		type: 'text',
		url: '?p=settings&tab=branding&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.pagename').editable({
		type: 'text',
		url: '?p=settings&tab=branding&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});

	$('.pagemail').editable({
		type: 'text',
		url: '?p=settings&tab=branding&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});


	<?php
$designs = array();

        $handle = opendir(__DIR__ . "/../../themes/");
        while ($datei = readdir($handle)) {
            if (substr($datei, 0, 1) != "." && is_dir(__DIR__ . "/../../themes/" . $datei) && $datei != "order") {
                array_push($designs, strtolower($datei));
            }
        }

        asort($designs);
        ?>

	$('.design').editable({
		type: 'select',
		source: [
			<?php foreach ($designs as $d) {?>
			{value: '<?=$d;?>', text: "<?=ucfirst($d);?>"},
			<?php }?>
		],
		url: '?p=settings&tab=branding&save=1',
		params: function(params) {
			params.csrf_token = "<?=CSRF::raw();?>";
			return params;
		}
	});
	</script>
</div>
<?php } else if ($tab == "cfields") {?>
<?php if (!isset($_GET['action']) || $_GET['action'] != "create") {

        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            $d = 0;

            if (isset($_POST['activate_selected'])) {
                foreach ($_POST['fields'] as $i) {
                    $i = intval($i);
                    $db->query("UPDATE client_fields SET active = 1 WHERE active = 0 AND ID = $i AND (system != 1 OR ID = 100)");
                    if ($db->affected_rows) {
                        $d++;
                        alog("settings", "cfield_activate", $i);
                    }
                }

                if ($d == 1) {
                    echo '<div class="alert alert-success">' . $l['CFACT'] . '</div>';
                } else if ($d > 1) {
                    echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['CFXACT']) . '</div>';
                }

            }

            if (isset($_POST['deactivate_selected'])) {
                foreach ($_POST['fields'] as $i) {
                    $i = intval($i);
                    $db->query("UPDATE client_fields SET active = 0 WHERE active = 1 AND ID = $i AND (system != 1 OR ID = 100)");
                    if ($db->affected_rows) {
                        $d++;
                        alog("settings", "cfield_deactivate", $i);
                    }
                }

                if ($d == 1) {
                    echo '<div class="alert alert-success">' . $l['CFDEACT'] . '</div>';
                } else if ($d > 1) {
                    echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['CFXDEACT']) . '</div>';
                }

            }

            if (isset($_POST['obligatory_selected'])) {
                foreach ($_POST['fields'] as $i) {
                    $i = intval($i);
                    $db->query("UPDATE client_fields SET duty = 1 WHERE duty = 0 AND ID = $i AND (system != 1 OR ID = 100)");
                    if ($db->affected_rows) {
                        $d++;
                        alog("settings", "cfield_obligatory", $i);
                    }
                }

                if ($d == 1) {
                    echo '<div class="alert alert-success">' . $l['CFOBLI'] . '</div>';
                } else if ($d > 1) {
                    echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['CFXOBLI']) . '</div>';
                }

            }

            if (isset($_POST['optional_selected'])) {
                foreach ($_POST['fields'] as $i) {
                    $i = intval($i);
                    $db->query("UPDATE client_fields SET duty = 0 WHERE duty = 1 AND ID = $i AND (system != 1 OR ID = 100)");
                    if ($db->affected_rows) {
                        $d++;
                        alog("settings", "cfield_optional", $i);
                    }
                }

                if ($d == 1) {
                    echo '<div class="alert alert-success">' . $l['CFOPT'] . '</div>';
                } else if ($d > 1) {
                    echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['CFXOPT']) . '</div>';
                }

            }

            if (isset($_POST['delete_selected'])) {
                foreach ($_POST['fields'] as $i) {
                    $i = intval($i);
                    $db->query("DELETE FROM client_fields WHERE ID = $i AND `system` = 0");
                    if ($db->affected_rows) {
                        $d++;
                        alog("settings", "cfield_delete", $i);
                    }
                }

                if ($d == 1) {
                    echo '<div class="alert alert-success">' . $l['CFDEL'] . '</div>';
                } else if ($d > 1) {
                    echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['CFXDEL']) . '</div>';
                }

            }
        }

        if (isset($_POST['save'])) {
            if (isset($_POST['allfields']) && is_array($_POST['allfields'])) {
                $sql = $db->prepare("UPDATE client_fields SET active = ?, position = ?, duty = ? WHERE (system != 1 OR ID = 100) AND ID = ?");
                $sql2 = $db->prepare("UPDATE client_fields SET customer = ? WHERE ID = ?");

                foreach ($_POST['allfields'] as $i) {
                    $i = intval($i);

                    $sql->bind_param("iiii", $a = isset($_POST['active'][$i]) ? 1 : 0, $b = $_POST['position'][$i] ?: ($i == 100 ? 1 : 0), $d = isset($_POST['duty'][$i]) ? 1 : 0, $i);
                    $sql->execute();

                    $sql2->bind_param("ii", $c = $_POST['rights'][$i] ?: 0, $i);
                    $sql2->execute();
                }

                alog("settings", "cfields_save");

                $sql->close();
                echo '<div class="alert alert-success">' . $l['CFSAVED'] . '</div>';
            }
        }

        if (isset($_POST['save_field']) && isset($_POST['id'])) {
            $name = $_POST['name'];
            $regex = $_POST['regex'];
            if (empty($name)) {
                echo '<div class="alert alert-danger">' . $l['CFNN'] . '</div>';
            } else if (!empty($regex) && false === @preg_match($regex, null)) {
                echo '<div class="alert alert-danger">' . $l['CFWR'] . '</div>';
            } else {
                $sql = $db->prepare("UPDATE client_fields SET name = ?, regex = ? WHERE ID = ?");
                $sql->bind_param("ssi", $name, $regex, $_POST['id']);
                $sql->execute();
                alog("settings", "cfield_save", $_POST['id']);
                echo '<div class="alert alert-success">' . $l['CFSAVED'] . '</div>';
            }
        }

        $modalCode = "";

        ?>
<a href="?p=settings&tab=cfields&action=create" class="btn btn-default"><?=$l['CFCREATE'];?></a><br /><br />
<form method="POST">
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="30px"><input type="checkbox" id="checkall" onchange="javascript:check_all(this.checked);"></th>
			<th><?=$l['CFNAME'];?></th>
			<th width="40px"><?=$l['CFACTIVE'];?></th>
			<th width="40px"><?=$l['CFPOSITION'];?></th>
			<th width="200px"><?=$l['CFCUSTOMER'];?></th>
			<th width="40px"><?=$l['CFOBLI2'];?></th>
			<th width="30px"></th>
		</tr>

		<?php
$sql = $db->query("SELECT * FROM client_fields WHERE system > 0 ORDER BY position ASC, ID ASC");
        while ($row = $sql->fetch_object()) {
            ?>
		<tr>
			<input type="hidden" name="allfields[]" value="<?=$row->ID;?>">
			<td><input type="checkbox" class="checkbox" name="fields[]" value="<?=$row->ID;?>" onchange="javascript:toggle();"></td>
		<td><?=$lang['ISOCODE'] != 'de' && $row->foreign_name ? $row->foreign_name : $row->name;?><?php if ($row->ID == 100) {?> <a href="?p=cust_source"><i class="fa fa-pencil"></i></a><?php }?></td>
			<td><center><input type="checkbox" name="active[<?=$row->ID;?>]" value="1"<?=$row->active ? ' checked="checked"' : "";?><?=$row->system == 1 && $row->ID != 100 ? ' disabled="disabled"' : "";?>></center></td>
			<td><center>-</center></td>
			<td>
				<select class="form-control" name="rights[<?=$row->ID;?>]"<?=$row->system != 2 && $row->customer == 0 ? ' disabled="disabled"' : "";?>>
					<?php if ($row->system == 2 || $row->customer == 0) {?><option value="0"<?=$row->customer == 0 ? ' selected="selected"' : "";?>><?=$l['CFA0'];?></option><?php }?>
					<option value="1"<?=$row->customer == 1 ? ' selected="selected"' : "";?>><?=$l['CFA1'];?></option>
					<option value="2"<?=$row->customer == 2 ? ' selected="selected"' : "";?>><?=$l['CFA2'];?></option>
				</select>
			</td>
			<td><center><input type="checkbox" name="duty[<?=$row->ID;?>]" value="1"<?=$row->duty ? ' checked="checked"' : "";?><?=$row->system == 1 && $row->ID != 100 ? ' disabled="disabled"' : "";?>></td>
			<td><center>-</center></td>
		</tr>
		<?php }?>

		<?php
$sql = $db->query("SELECT * FROM client_fields WHERE `system` = 0 ORDER BY position ASC, name ASC, ID ASC");
        while ($row = $sql->fetch_object()) {
            $modalCode .= '<div class="modal fade" id="field-' . $row->ID . '" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="' . $lang['GENERAL']['CLOSE'] . '"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title">' . $row->name . '</h4>
		      </div>
		      <form method="POST"><div class="modal-body">
		        <div class="form-group">
		        	<label>' . $l['CFNAME'] . '</label>
		        	<input type="text" name="name" value="' . $row->name . '" class="form-control">
		        </div>

		        <div class="form-group">
		        	<label>' . $l['CFVAL'] . '</label>
		        	<input type="text" name="regex" value="' . $row->regex . '" class="form-control">
		        	<p class="help-block">' . $l['CFVALH'] . '</p>
		        </div>
		      </div>
		      <input type="hidden" name="id" value="' . $row->ID . '">
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal">' . $lang['GENERAL']['CLOSE'] . '</button>
		        <button type="submit" name="save_field" class="btn btn-primary">' . $lang['GENERAL']['SAVE'] . '</button>
		      </div></form>
		    </div>
		  </div>
		</div>';
            ?>
		<tr>
			<input type="hidden" name="allfields[]" value="<?=$row->ID;?>">
			<td><input type="checkbox" class="checkbox" name="fields[]" value="<?=$row->ID;?>" onchange="javascript:toggle();"></td>
			<td><?=$row->name;?></td>
			<td><center><input type="checkbox" name="active[<?=$row->ID;?>]" value="1"<?=$row->active ? ' checked="checked"' : "";?>></td>
			<td><center><input type="text" name="position[<?=$row->ID;?>]" value="<?=$row->position;?>" style="text-align: center;" class="form-control"></center></td>
			<td>
				<select class="form-control" name="rights[<?=$row->ID;?>]">
					<option value="0"><?=$l['CFA0'];?></option>
					<option value="1"<?=$row->customer == 1 ? ' selected="selected"' : "";?>><?=$l['CFA1'];?></option>
					<option value="2"<?=$row->customer == 2 ? ' selected="selected"' : "";?>><?=$l['CFA2'];?></option>
				</select>
			</td>
			<td><center><input type="checkbox" name="duty[<?=$row->ID;?>]" value="1"<?=$row->duty ? ' checked="checked"' : "";?>></center></td>
			<td><center><a href="#" data-toggle="modal" data-target="#field-<?=$row->ID;?>"><i class="fa fa-edit"></i></a></center></td>
		</tr>
		<?php }?>
	</table>
</div>
<input type="submit" name="save" value="<?=$lang['GENERAL']['SAVE'];?>" class="btn btn-primary btn-block" />
<br />
<?=$l['SELECTED'];?>: <input type="submit" name="activate_selected" class="btn btn-success" value="<?=$l['ACTIVATE'];?>"> <input type="submit" name="deactivate_selected" class="btn btn-warning" value="<?=$l['DEACTIVATE'];?>"> <input type="submit" name="obligatory_selected" class="btn btn-default" value="<?=$l['CFOBLIMAKE'];?>"> <input type="submit" name="optional_selected" class="btn btn-default" value="<?=$l['OPTIONAL'];?>"> <input type="submit" name="delete_selected" class="btn btn-danger" value="<?=$l['DELETE'];?>">
<br />
</form><?=$modalCode;?><?php } else {

        if (isset($_POST['create'])) {
            $sql = $db->prepare("INSERT INTO client_fields (name, active, position, customer, duty, regex) VALUES (?,?,?,?,?,?)");

            foreach ($_POST as $k => $v) {
                $vari = "p" . ucfirst(strtolower($k));
                $$vari = $v;
            }

            try {
                if (empty($pName)) {
                    throw new Exception($l['CFNERR1']);
                }

                if (!isset($pCustomer) || !in_array($pCustomer, array(0, 1, 2))) {
                    throw new Exception($l['CFNERR2']);
                }

                if (!empty($pRegex) && false === @preg_match($pRegex, null)) {
                    throw new Exception($l['CFNERR3']);
                }

                if (empty($pPosition) || !is_numeric($pPosition)) {
                    $pPosition = 0;
                }

                if (empty($pActive) || $pActive != 1) {
                    $pActive = 0;
                }

                if (empty($pDuty) || $pDuty != 1) {
                    $pDuty = 0;
                }

                $sql->bind_param("siiiis", $pName, $pActive, $pPosition, $pCustomer, $pDuty, $pRegex);
                $sql->execute();

                alog("settings", "cfield_create", $pName, $db->insert_id);

                echo '<div class="alert alert-success">' . $l['CFNOK'] . '</div>';
                unset($_POST);
            } catch (Exception $ex) {
                echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }

        ?>
<form method="post">
	<fieldset>
		<div class="form-group">
			<label><?=$l['CFNAME'];?></label>
			<input type="text" name="name" value="<?=isset($_POST["name"]) ? $_POST['name'] : "";?>" class="form-control">
		</div>

		<div class="form-group">
			<label><?=$l['CFCAC'];?></label>
			<select name="customer" class="form-control">
				<option value="0"><?=$l['CFA0'];?></option>
				<option value="1"<?=isset($_POST['customer']) && $_POST['customer'] == 1 ? ' selected="selected"' : "";?>><?=$l['CFA1'];?></option>
				<option value="2"<?=isset($_POST['customer']) && $_POST['customer'] == 2 ? ' selected="selected"' : "";?>><?=$l['CFA2'];?></option>
			</select>
		</div>

		<div class="form-group">
			<label><?=$l['CFVAL'];?></label>
			<input type="text" name="regex" value="<?=isset($_POST["regex"]) ? $_POST['regex'] : "";?>" class="form-control">
			<p class="help-block"><?=$l['CFVALH'];?></p>
		</div>

		<div class="form-group">
			<label><?=$l['CFPOSITION'];?></label>
			<input type="text" name="position" value="<?=isset($_POST["position"]) ? $_POST['position'] : "0";?>" placeholder="10" class="form-control" style="max-width: 150px;">
		</div>

		<div class="checkbox">
		  	<label>
				<input type="checkbox" name="active" value="1"<?=isset($_POST['active']) || !isset($_POST['create']) ? " checked" : "";?>>
				<?=$l['CFACTIVE'];?>
		  	</label>
		</div>

		<div class="checkbox">
		  	<label>
				<input type="checkbox" name="duty" value="1"<?=isset($_POST['duty']) ? " checked" : "";?>>
				<?=$l['CFOBLIMAKE'];?>
		  	</label>
		</div>
	</fieldset>

	<input type="submit" name="create" class="btn btn-primary btn-block" value="<?=$l['CFADDNOW'];?>">
</form>
<?php }} else if ($tab == "affiliate") {?>
	<form method="post">
		<fieldset>
			<div class="form-group">
				<label><?=$l['AFFSYS'];?></label>

				<div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
					<label>
						<input type="checkbox" name="affiliate_active"
							   value="1"<?=$CFG['AFFILIATE_ACTIVE'] == "1" ? " checked=\"checked\"" : "";?>>
						<?=$l['AFFSYSACT'];?>
					</label>
				</div>
			</div>

			<div class="form-group">
				<label><?=$l['AFFCOM'];?></label>

				<div class="input-group">
					<input type="text" name="affiliate_commission" value="<?=$nfo->format(doubleval($CFG['AFFILIATE_COMMISSION']));?>"
						   class="form-control"/>
					<span class="input-group-addon">%</span>
				</div>

				<p class="help-block"><?=$l['AFFCOMH'];?></p>
			</div>

			<div class="form-group">
				<label><?=$l['AFFDAYS'];?></label>

				<div class="input-group">
					<input type="text" name="affiliate_days" value="<?=intval($CFG['AFFILIATE_DAYS']);?>"
						   class="form-control"/>
					<span class="input-group-addon"><?=$l['DAYS'];?></span>
				</div>

				<p class="help-block"><?=$l['AFFDAYSH'];?></p>
			</div>

			<div class="form-group">
				<label><?=$l['AFFMIN'];?></label>

				<div class="input-group">
					<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon"><?=$cur->getPrefix();?></span><?php }?>
					<input type="text" name="affiliate_min" value="<?=$nfo->format(doubleval($CFG['AFFILIATE_MIN']));?>"
						   class="form-control"/>
					<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon"><?=$cur->getSuffix();?></span><?php }?>
				</div>

				<p class="help-block"><?=$l['AFFMINH'];?></p>
			</div>

			<div class="form-group">
				<label><?=$l['AFFCOO'];?></label>

				<div class="input-group">
					<input type="text" name="affiliate_cookie" value="<?=intval($CFG['AFFILIATE_COOKIE']);?>"
						   class="form-control"/>
					<span class="input-group-addon"><?=$l['DAYS'];?></span>
				</div>

				<p class="help-block"><?=$l['AFFCOOH'];?></p>
			</div>

			<div class="form-group">
				<label><?=$l['AFFEXT'];?></label>
				<textarea name="ext_affiliate" class="form-control" style="resize: none; height: 100px; width: 100%;"><?=htmlentities($CFG['EXT_AFFILIATE']);?></textarea>
				<p class="help-block"><?=$l['AFFEXTH'];?></p>
			</div>

			<div class="form-group">
				<label><?=$l['AFFEXTEX'];?></label>
				<select name="ext_affiliate_ex[]" multiple class="form-control">
					<?php
$hidden = unserialize($CFG['EXT_AFFILIATE_EX']);
        $sql = $db->query("SELECT ID, name FROM products WHERE type = 'HOSTING'");
        $arts = array();
        while ($art = $sql->fetch_object()) {
            $arts[$art->ID] = unserialize($art->name)[$CFG['LANG']];
        }

        natcasesort($arts);
        foreach ($arts as $id => $name) {
            $type = "Hosting";
            if (in_array($id, $hidden)) {
                echo "<option value=\"$id\" selected=\"selected\">$type - $name</option>";
            } else {
                echo "<option value=\"$id\">$type - $name</option>";
            }

        }
        $sql = $db->query("SELECT ID, name FROM products WHERE type != 'HOSTING'");
        $arts = array();
        while ($art = $sql->fetch_object()) {
            $arts[$art->ID] = unserialize($art->name)[$CFG['LANG']];
        }

        natcasesort($arts);
        foreach ($arts as $id => $name) {
            $type = "Software";
            if (in_array($id, $hidden)) {
                echo "<option value=\"$id\" selected=\"selected\">$type - $name</option>";
            } else {
                echo "<option value=\"$id\">$type - $name</option>";
            }

        }
        ?>
				</select>
				<p class="help-block"><?=$l['MULTIPLECHOICE'];?></p>
			</div>
		</fieldset>

		<input type="submit" name="change" value="<?=$l['SAVEANY'];?>" class="btn btn-primary btn-block"/><br/>
	</form>
<?php } else if ($tab == "letters") {

        if (isset($_POST['change_letter_provider'])) {
            $provs = is_array($_POST['letter_provider'] ?: "") ? $_POST['letter_provider'] : [];

            foreach ($provs as $k => $v) {
                if (!array_key_exists($v, LetterHandler::getDrivers())) {
                    unset($provs[$k]);
                }
            }

            $provs = implode("|", $provs);
            $CFG['LETTER_PROVIDER'] = $provs;
            $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($provs) . "' WHERE `key` = 'letter_provider'");
        }
        ?>
<form method="POST"><div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="20px"></th>
			<th width="50%"><?=$l['LETPROV'];?></th>
			<th width="20%"><?=$l['LETVERSION'];?></th>
			<th></th>
		</tr>

		<?php
$provs = explode("|", $CFG['LETTER_PROVIDER']);

        foreach (LetterHandler::getDrivers() as $short => $obj) {?>
		<tr>
			<td><input type="checkbox" name="letter_provider[]" value="<?=$short;?>"<?php if (in_array($short, $provs)) {
            echo ' checked="checked"';
        }
            ?> /></td>
			<td><?=$obj->getName();?></td>
			<td><?=$obj->getVersion();?></td>
			<td><a href="#" data-toggle="modal" data-target="#<?=$short;?>" class="btn btn-default btn-xs"><?=$l['LETCONF'];?></a></td>
		</tr>
		<?php }?>
	</table>
</div>

<input name="change_letter_provider" type="submit" value="<?=$l['LETCHANGE'];?>" class="btn btn-primary btn-block" /></form>

<?php foreach (LetterHandler::getDrivers() as $short => $obj) {?>
<form method="POST"><div class="modal fade" id="<?=$short;?>" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$obj->getName();?></h4>
      </div>
      <div class="modal-body">
        <?php foreach ($obj->getSettings() as $k => $i) {?><div class="form-group">
        	<label><?=$i['name'];?></label>
        	<?php if ($i['type'] != "hint") {?><input type="<?=$i['type'];?>" class="form-control setting-<?=$short;?>" data-setting="<?=$k;?>" value="<?=isset($obj->options->$k) ? $obj->options->$k : $i['default'];?>" autocomplete="off" /><?php if (!empty($i['help'])) {
            echo '<p class="help-block">' . $i['help'] . '</p>';
        }
            ?><?php } else {echo "<br />" . $i['help'];}?>
        </div><?php }?>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary save-letter" onclick="return false;" data-id="<?=$short;?>"><?=$lang['GENERAL']['SAVE'];?></button>
      </div>
    </div>
  </div>
</div></form>
<?php }?>
<script>
$(".save-letter").click(function(e){
	e.preventDefault();
	var prov = $(this).data("id");
	var btn = $(this);
	var data = {
		csrf_token: "<?=CSRF::raw();?>",
	};

	btn.html('<i class="fa fa-spin fa-spinner"></i> <?=$lang['GENERAL']['SAVE'];?>');

	$(".setting-" + prov).each(function(){
		data[$(this).data("setting")] = $(this).val();
	});

	$.post("?p=settings&tab=letters&prov=" + prov, data, function(r){
		if(r == "ok"){
			$("#" + prov).modal("toggle");
			btn.html('<?=$lang['GENERAL']['SAVE'];?>');
		}
	});
});
</script>
<?php } else if ($tab == "sms") {?>
<form method="POST"><div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="20px"></th>
			<th width="50%"><?=$l['LETPROV'];?></th>
			<th width="20%"><?=$l['LETVERSION'];?></th>
			<th></th>
		</tr>

		<?php foreach (SMSHandler::getDrivers() as $short => $obj) {?>
		<tr>
			<td><input type="radio" name="sms_provider" value="<?=$short;?>"<?php if ($CFG['SMS_PROVIDER'] == $short) {
        echo ' checked="checked"';
    }
        ?> /></td>
			<td><?=$obj->getName();?></td>
			<td><?=$obj->getVersion();?></td>
			<td><a href="#" data-toggle="modal" data-target="#<?=$short;?>" class="btn btn-default btn-xs"><?=$l['LETCONF'];?></a></td>
		</tr>
		<?php }?>
	</table>
</div>

<input name="change" type="submit" value="<?=$l['SMSCHANGE'];?>" class="btn btn-primary btn-block" /></form>

<?php foreach (SMSHandler::getDrivers() as $short => $obj) {?>
<form method="POST"><div class="modal fade" id="<?=$short;?>" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$obj->getName();?></h4>
      </div>
      <div class="modal-body">
        <?php foreach ($obj->getSettings() as $k => $i) {?><div class="form-group">
        	<label><?=$i['name'];?></label>
        	<?php if ($i['type'] != "hint") {?><input type="<?=$i['type'];?>" class="form-control setting-<?=$short;?>" data-setting="<?=$k;?>" value="<?=isset($obj->options->$k) ? $obj->options->$k : $i['default'];?>" autocomplete="off" /><?php if (!empty($i['help'])) {
        echo '<p class="help-block">' . $i['help'] . '</p>';
    }
        ?><?php } else {echo "<br />" . $i['help'];}?>
        </div><?php }?>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary save-letter" onclick="return false;" data-id="<?=$short;?>"><?=$lang['GENERAL']['SAVE'];?></button>
      </div>
    </div>
  </div>
</div></form>
<?php }?>
<script>
$(".save-letter").click(function(e){
	e.preventDefault();
	var prov = $(this).data("id");
	var btn = $(this);
	var data = {
		csrf_token: "<?=CSRF::raw();?>",
	};

	btn.html('<i class="fa fa-spin fa-spinner"></i> <?=$lang['GENERAL']['SAVE'];?>');

	$(".setting-" + prov).each(function(){
		data[$(this).data("setting")] = $(this).val();
	});

	$.post("?p=settings&tab=sms&prov=" + prov, data, function(r){
		if(r == "ok"){
			$("#" + prov).modal("toggle");
			btn.html('<?=$lang['GENERAL']['SAVE'];?>');
		}
	});
});
</script>
<?php } else if ($tab == "encashment") {?>
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="50%"><?=$l['ENCCOM'];?></th>
			<th width="20%"><?=$l['LETVERSION'];?></th>
			<th></th>
		</tr>

		<?php foreach (EncashmentHandler::getDrivers() as $short => $obj) {?>
		<tr>
			<td><?=$obj->getName();?></td>
			<td><?=$obj->getVersion();?></td>
			<td><a href="#" data-toggle="modal" data-target="#<?=$short;?>" class="btn btn-default btn-xs"><?=$l['LETCONF'];?></a></td>
		</tr>
		<?php }?>
	</table>
</div>

<?php foreach (EncashmentHandler::getDrivers() as $short => $obj) {?>
<form method="POST"><div class="modal fade" id="<?=$short;?>" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$obj->getName();?></h4>
      </div>
      <div class="modal-body">
        <?php foreach ($obj->getSettings() as $k => $i) {?><div class="form-group">
        	<label><?=$i['name'];?></label>
        	<?php if ($i['type'] != "hint") {?><input type="<?=$i['type'];?>" class="form-control setting-<?=$short;?>" data-setting="<?=$k;?>" value="<?=isset($obj->options->$k) ? $obj->options->$k : $i['default'];?>" autocomplete="off" /><?php if (!empty($i['help'])) {
        echo '<p class="help-block">' . $i['help'] . '</p>';
    }
        ?><?php } else {echo "<br />" . $i['help'];}?>
        </div><?php }?>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary save-encashment" onclick="return false;" data-id="<?=$short;?>"><?=$lang['GENERAL']['SAVE'];?></button>
      </div>
    </div>
  </div>
</div></form>
<?php }?>
<script>
$(".save-encashment").click(function(e){
	e.preventDefault();
	var prov = $(this).data("id");
	var btn = $(this);
	var data = {
		csrf_token: "<?=CSRF::raw();?>",
	};

	btn.html('<i class="fa fa-spin fa-spinner"></i> <?=$lang['GENERAL']['SAVE'];?>');

	$(".setting-" + prov).each(function(){
		data[$(this).data("setting")] = $(this).val();
	});

	$.post("?p=settings&tab=encashment&prov=" + prov, data, function(r){
		if(r == "ok"){
			$("#" + prov).modal("toggle");
			btn.html('<?=$lang['GENERAL']['SAVE'];?>');
		}
	});
});
</script>
<?php } else if ($tab == "telephone_log") {
        if (!empty($_GET['a'])) {
            $u = unserialize($CFG['TELEPHONE_LOG']);
            if (!array_key_exists($_GET['a'], $u) && TelephoneLogHandler::moduleExists($_GET['a'])) {
                $u[$_GET['a']] = array();
                $CFG['TELEPHONE_LOG'] = serialize($u);
                $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string(encrypt($CFG['TELEPHONE_LOG'])) . "' WHERE `key` = 'telephone_log'");
                alog("settings", "telephone_log_activate", $_GET['a']);
            }
        }

        if (!empty($_GET['d'])) {
            $u = unserialize($CFG['TELEPHONE_LOG']);
            if (array_key_exists($_GET['d'], $u)) {
                unset($u[$_GET['d']]);
                $CFG['TELEPHONE_LOG'] = serialize($u);
                $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string(encrypt($CFG['TELEPHONE_LOG'])) . "' WHERE `key` = 'telephone_log'");
                alog("settings", "telephone_log_deactivate", $_GET['d']);
            }
        }
        ?>
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="50%"><?=$l['LETPROV'];?></th>
			<th width="20%"><?=$l['VERSION'];?></th>
			<th></th>
		</tr>

		<?php
$h = new TelephoneLogHandler;
        foreach ($h->get() as $short => $obj) {?>
		<tr>
			<td><?=$obj->getName();?></td>
			<td><?=$obj->getVersion();?></td>
			<td><?php if (!$obj->isActive()) {?><a href="?p=settings&tab=telephone_log&a=<?=$short;?>" class="btn btn-success btn-xs"><?=$l['ACTIVATE'];?></a><?php } else {?><a href="#" data-toggle="modal" data-target="#tlm_<?=$short;?>" class="btn btn-default btn-xs"><?=$l['LETCONF'];?></a> <a href="?p=settings&tab=telephone_log&d=<?=$short;?>" class="btn btn-danger btn-xs"><?=$l['DEACTIVATE'];?></a><?php }?></td>
		</tr>
		<?php }?>
	</table>
</div>

<?php foreach ($h->get() as $short => $obj) {if (!$obj->isActive()) {
            continue;
        }
            ?>
<form method="POST"><div class="modal fade" id="tlm_<?=$short;?>" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$obj->getName();?></h4>
      </div>
      <div class="modal-body">
        <?php foreach ($obj->getSettings() as $k => $i) {?><div class="form-group">
        	<label><?=$i['name'];?></label>
        	<?php if ($i['type'] != "hint") {?><input type="<?=$i['type'];?>" class="form-control setting-<?=$short;?>" data-setting="<?=$k;?>" value="<?=isset($obj->options[$k]) ? $obj->options[$k] : $i['default'];?>" autocomplete="off" /><?php if (!empty($i['help'])) {
                echo '<p class="help-block">' . $i['help'] . '</p>';
            }
                ?><?php } else {echo "<br />" . $i['help'];}?>
        </div><?php }?>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary save-tl" onclick="return false;" data-id="<?=$short;?>"><?=$lang['GENERAL']['SAVE'];?></button>
      </div>
    </div>
  </div>
</div></form>
<?php }?>
<script>
$(".save-tl").click(function(e){
	e.preventDefault();
	var prov = $(this).data("id");
	var btn = $(this);
	var data = {
		csrf_token: "<?=CSRF::raw();?>",
	};

	btn.html('<i class="fa fa-spin fa-spinner"></i> <?=$lang['GENERAL']['SAVE'];?>');

	$(".setting-" + prov).each(function(){
		data[$(this).data("setting")] = $(this).val();
	});

	$.post("?p=settings&tab=telephone_log&prov=" + prov, data, function(r){
		if(r == "ok"){
			$("#" + prov).modal("toggle");
			btn.html('<?=$lang['GENERAL']['SAVE'];?>');
		}
	});
});
</script>
<?php } else if ($tab == "increment") {

        if (isset($_POST['clients'])) {
            foreach ($_POST as $k => $v) {
                if (!in_array($k, array("clients", "invoices", "client_quotes", "projects", "wishlist", "support_tickets", "support_ticket_answers", "client_sepa", "client_products"))) {
                    continue;
                }

                $k = $db->real_escape_string($k);
                $min = $db->query("SELECT ID FROM {$k} ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;
                if (!is_numeric($v) || $v < $min) {
                    continue;
                }

                $db->query("ALTER TABLE {$k} AUTO_INCREMENT = " . intval($v) . ";");
            }

            alog("settings", "auto_increment_saved");

            echo '<div class="alert alert-success">' . $l['AISAVED'] . '</div>';
        }
        ?>
<p style="text-align: justify;"><?=$l['AIINTRO'];?></p>

<form method="POST">
	<div class="form-group">
		<label><?=$l['AICUS'];?></label>
		<input type="text" name="clients" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'clients'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM clients ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AICON'];?></label>
		<input type="text" name="client_products" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'client_products'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM client_products ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AIINV'];?></label>
		<input type="text" name="invoices" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'invoices'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM invoices ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AIQUO'];?></label>
		<input type="text" name="client_quotes" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'client_quotes'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM client_quotes ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AIPRO'];?></label>
		<input type="text" name="projects" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'projects'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM projects ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AISUP'];?></label>
		<input type="text" name="support_tickets" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'support_tickets'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM support_tickets ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AIANS'];?></label>
		<input type="text" name="support_ticket_answers" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'support_ticket_answers'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM support_ticket_answers ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AISEP'];?></label>
		<input type="text" name="client_sepa" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'client_sepa'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM client_sepa ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<div class="form-group">
		<label><?=$l['AIWIS'];?></label>
		<input type="text" name="wishlist" value="<?=$db->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = database() AND TABLE_NAME = 'wishlist'")->fetch_object()->AUTO_INCREMENT;?>" class="form-control">
		<p class="help-block"><?=$l['MINIMUM'];?>: <?=$db->query("SELECT ID FROM wishlist ORDER BY ID DESC LIMIT 1")->fetch_object()->ID + 1;?></p>
	</div>

	<input type="submit" value="<?=$l['AISAVE'];?>" class="btn btn-block btn-primary" />
</form>
<?php } else if ($tab == "websocket") {?>
<p style="text-align: justify;"><?=$l['WSINTRO'];?></p>
<form method="POST">
<div class="checkbox">
	<label>
		<input type="checkbox" name="websocket_active" value="1"<?=$CFG['WEBSOCKET_ACTIVE'] ? ' checked=""' : '';?>>
		<?=$l['WSACT'];?>
	</label>
</div>

<script>
$("[name=websocket_active]").click(function(){
	if($(this).is(":checked"))
		$("#websocket_settings").slideDown();
	else
		$("#websocket_settings").slideUp();
});
</script>

<div id="websocket_settings"<?=$CFG['WEBSOCKET_ACTIVE'] ? '' : ' style="display: none;"';?>>
	<div class="form-group">
		<label><?=$l['WSPORT'];?></label>
		<input type="text" name="websocket_port" value="<?=$CFG['WEBSOCKET_PORT'];?>" placeholder="8000" class="form-control">
		<p class="help-block"><?=$l['WSPORTH'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['WSAO'];?></label>
		<input type="text" name="websocket_ao" value="<?=$CFG['WEBSOCKET_AO'];?>" placeholder="localhost,127.0.0.1,::1" class="form-control">
		<p class="help-block"><?=$l['WSAOH'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['WSCERT'];?></label>
		<textarea name="websocket_pem" class="form-control" style="height: 100px; resize: vertical;"><?=decrypt($CFG['WEBSOCKET_PEM']);?></textarea>
		<p class="help-block"><?=$l['WSCERTH'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['WSCRE'];?></label><br />
		<?=$l['WSCREH'];?>
	</div>

	<div class="form-group">
		<label><?=$l['WSSTA'];?></label><br />
		<span class="websocket_status"><?=$CFG['WEBSOCKET_ACTIVE'] ? '<i class="fa fa-spinner fa-spin"></i> ' . $l['WSWAIT'] : '<font color="red">' . $l['WSOFF'] . '</font>';?></span>
	</div>
</div>

<?php if ($CFG['WEBSOCKET_ACTIVE']) {?>
<script>
$(document).ready(function(){
	var c = new WebSocket('ws<?=$_SERVER['HTTPS'] ? "s" : "";?>://<?=$_SERVER['HTTP_HOST'];?>:<?=$CFG['WEBSOCKET_PORT'];?>/test');

	c.onopen = function () {
	  wsInt = setInterval(function() {
	  	c.send('Ping');
	  }, 500);
	};

	c.onerror = function (error) {
	  $(".websocket_status").html('<font color="red"><?=$l['WSOFF'];?></font>');
	};

	c.onmessage = function (e) {
	  if(e.data == "Pong") {
	  	$(".websocket_status").html('<font color="green"><?=$l['WSON'];?></font>');
		clearInterval(wsInt);
	  }
	};

	setTimeout(function(){
		if($(".websocket_status").html() != '<font color="green"><?=$l['WSON'];?></font>') {
			$(".websocket_status").html('<font color="red"><?=$l['WSOFF'];?></font>');
			clearInterval(wsInt);
		}
	}, 5000);
});
</script>
<?php }?>

<input type="submit" name="change" value="<?=$l['SAVEANY'];?>" class="btn btn-block btn-primary" />
</form>
<?php } else if ($tab == "license") {?>
<p style="text-align: justify;"><?=$l['LICINTRO'];?></p>

<form method="POST">
	<div class="form-group">
		<label><?=$l['LICKEY'];?></label>
		<input type="text" class="form-control" name="license_key" value="<?=$CFG['LICENSE_KEY'];?>" />
		<p class="help-block" id="key_status"><font color="green"><?=$l['LICVALID'];?></font></p>
	</div>

	<input type="submit" id="change_license_key" class="btn btn-primary btn-block" value="<?=$l['LICSAVE'];?>" />
</form>

<script>
var licensedoing = 0;

$("#change_license_key").click(function(e) {
	e.preventDefault();

	if (licensedoing) {
		return;
	}

	licensedoing = 1;

	$(this).hide();
	$("#key_status").html("<i class='fa fa-spinner fa-spin'></i> <?=$l['LICWAIT'];?>");

	$.post("", {
		"new_license_key": $("[name=license_key]").val(),
		"csrf_token": "<?=CSRF::raw();?>",
	}, function(r) {
		if (r == "ok") {
			$("#key_status").html("<font color='green'><i class='fa fa-check'></i> <?=$l['LICOK'];?></font>");
		} else {
			$("#key_status").html("<font color='red'><i class='fa fa-times'></i> <?=$l['LICFAIL'];?></font>");
		}

		licensedoing = 0;
		$("#change_license_key").show();
	})
});
</script>
<?php } else if ($tab == "offers") {?>
<form method="post">
	<div class="form-group">
		<label><?=$l['OFPRE'];?></label>
		<input type="text" class="form-control" name="offer_prefix" value="<?=$CFG['OFFER_PREFIX'];?>" placeholder="AN-" />
		<p class="help-block"><?=$l['OFPREH'];?></p>
	</div>

	<div class="form-group">
			<label><?=$l['MIN_QUOLEN'];?></label>
			<input type="text" class="form-control" name="min_quolen" value="<?=intval($CFG['MIN_QUOLEN']);?>" placeholder="6" />
		</div>

	<div class="form-group">
		<label><?=$l['OFINTRO'];?></label>
		<textarea class="form-control summernote" name="offer_intro" style="height: 100px; resize: none;" placeholder="<?=$l['OFINTROP'];?>"><?=nl2br($CFG['OFFER_INTRO']);?></textarea>
		<p class="help-block"><?=$l['OFINTROH'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['OFEXTRO'];?></label>
		<textarea class="form-control summernote" name="offer_extro" style="height: 100px; resize: none;" placeholder="<?=$l['OFEXTROP'];?>"><?=nl2br($CFG['OFFER_EXTRO']);?></textarea>
		<p class="help-block"><?=$l['OFEXTROH'];?></p>
	</div>

	<div class="form-group">
		<label><?=$l['OFTERMS'];?></label>
		<textarea class="form-control summernote" name="offer_terms" style="height: 100px; resize: none;" placeholder="<?=$l['OFTERMSP'];?>"><?=nl2br($CFG['OFFER_TERMS']);?></textarea>
		<p class="help-block"><?=$l['OFTERMSH'];?></p>
	</div>
	<input type="submit" name="change" value="<?=$l['SAVEANY'];?>" class="btn btn-block btn-primary" />
</form>
<?php } else if ($tab == "scoring") {?>
<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th width="50%"><?=$l['SCOSOU'];?></th>
			<th width="20%"><?=$l['LETVERSION'];?></th>
			<th></th>
		</tr>

		<?php foreach (ScoringHandler::getDrivers() as $short => $obj) {?>
		<tr>
			<td><?=$obj->getName();?></td>
			<td><?=$obj->getVersion();?></td>
			<td><a href="#" data-toggle="modal" data-target="#<?=$short;?>" class="btn btn-default btn-xs"><?=$l['LETCONF'];?></a></td>
		</tr>
		<?php }?>
	</table>
</div>

<?php foreach (ScoringHandler::getDrivers() as $short => $obj) {?>
<form method="POST"><div class="modal fade" id="<?=$short;?>" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="<?=$lang['GENERAL']['CLOSE'];?>"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$obj->getName();?></h4>
      </div>
      <div class="modal-body">
        <?php foreach ($obj->getSettings() as $k => $i) {?><div class="form-group">
        	<label><?=$i['name'];?></label>
        	<?php if ($i['type'] != "hint") {?><input type="<?=$i['type'];?>" class="form-control setting-<?=$short;?>" data-setting="<?=$k;?>" value="<?=isset($obj->options->$k) ? $obj->options->$k : $i['default'];?>" autocomplete="off" /><?php if (!empty($i['help'])) {
        echo '<p class="help-block">' . $i['help'] . '</p>';
    }
        ?><?php } else {echo "<br />" . $i['help'];}?>
        </div><?php }?>

				<?php foreach ($obj->getMethods() as $k => $n) {?>
				<div class="checkbox">
					<label>
						<input type="checkbox" class="checkbox-<?=$short;?>" data-setting="automatic-<?=$k;?>" value="1"<?php $mk = "automatic-" . $k;
        echo $obj->options->$mk ? ' checked=""' : '';?>>
						<?=$l['SCOAUT'];?>: <?=$n;?>
					</label>
				</div>
				<?php }?>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary save-scoring" onclick="return false;" data-id="<?=$short;?>"><?=$lang['GENERAL']['SAVE'];?></button>
      </div>
    </div>
  </div>
</div></form>
<?php }?>
<script>
$(".save-scoring").click(function(e){
	e.preventDefault();
	var prov = $(this).data("id");
	var btn = $(this);
	var data = {
		csrf_token: "<?=CSRF::raw();?>",
	};

	btn.html('<i class="fa fa-spin fa-spinner"></i> <?=$lang['GENERAL']['SAVE'];?>');

	$(".setting-" + prov).each(function(){
		data[$(this).data("setting")] = $(this).val();
	});

	$(".checkbox-" + prov).each(function(){
		if($(this).is(":checked")) data[$(this).data("setting")] = 1;
		else data[$(this).data("setting")] = 0;
	});

	$.post("?p=settings&tab=scoring&prov=" + prov, data, function(r){
		if(r == "ok"){
			$("#" + prov).modal("toggle");
			btn.html('<?=$lang['GENERAL']['SAVE'];?>');
		}
	});
});
</script>
<?php } else {?>
<div class="alert alert-danger"><?=$l['PNF'];?></div>
<?php }?>
</div></div></div></div><?php }?>
