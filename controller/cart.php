<?php
// Global some variables for security reasons
global $cart, $db, $user, $_GET, $maq, $CFG, $raw_cfg, $nfo, $var, $lang, $transactions, $cur, $languages, $pars, $f2b, $session, $sec, $tfa, $captcha, $val, $gateways, $addons, $dfo, $provisioning, $paymentReference;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$gateways->insertJavaScript();
$var['gateways'] = $gateways->get();
$gateways->makePayment();

foreach ($var['gateways'] as $k => $obj) {
    if (!$obj->isActive()) {
        unset($var['gateways'][$k]);
    } else if ($var['logged_in'] && !$obj->canPay($user)) {
        unset($var['gateways'][$k]);
    }
}

if ($var['logged_in'] && $user->get()['orders_active'] === "0") {
    $tpl = "error";
    $title = $lang['ERROR']['TITLE'];
    $var['error'] = $lang['CART']['ORDERS_DISABLED'];
    return;
}

$var['cust_source'] = $db->query("SELECT 1 FROM client_fields WHERE ID = 100 AND active = 1")->num_rows;
$var['cust_source_duty'] = $db->query("SELECT 1 FROM client_fields WHERE ID = 100 AND duty = 1")->num_rows;
$var['cso'] = [];

foreach (unserialize($CFG['CUST_SOURCE']) as $cs) {
    $mycs = $cs[$CFG['LANG']] ?? "";
    if (!empty($mycs)) {
        $var['cso'][] = $mycs;
    }
}

$var['hasTos'] = $var['hasWithdrawal'] = $var['hasPrivacy'] = 0;
$var['smsverify'] = $CFG['SMS_VERIFY_ORDERS'] && ($CFG['SMS_VERIFY'] !== "") ? true : false;

if ($var['logged_in']) {
    $curTos = $db->query("SELECT ID FROM terms_of_service ORDER BY ID DESC")->fetch_object()->ID ?: 1;
    if ($user->get()['tos'] >= $curTos) {
        $var['hasTos'] = 1;
    }

    $var['hasWithdrawal'] = $user->get()['withdrawal_rules'];
    $var['hasPrivacy'] = $user->get()['privacy_policy'];

    if ($user->get()['telephone_verified']) {
        $var['smsverify'] = false;
    }
}

$tpl = "cart";
$title = $lang['CART']['TITLE'];
$var['loginRedirect'] = "cart";
$var['step'] = 1;
$var['extaffcode'] = "";
if (empty($_SESSION['card']) || !is_array($_SESSION['card'])) {
    $_SESSION['card'] = array();
}

$block = false;
if ($var['logged_in']) {
    $cart->__construct($user->get()['ID']);
    if ($CFG['TAXES']) {
        $tax = $user->getVAT();
        $block = $tax === false;
    }
} else {
    $cart->rebuild();
}

// Remove voucher
if (in_array("removevoucher", array_keys($_GET)) && $cart->removeVoucher()) {
    $var['voucher_ok'] = $lang['CART']['VOUCHER_DELETED'];
    if ($var['logged_in']) {
        $cart->__construct($user->get()['ID']);
    }

    $cart->piwik();
}

// Tax system
$var['b2b'] = false;

if ($var['logged_in']) {
    $country = intval($user->get()['country']);
    $var['b2b'] = (bool) !empty($user->get()['company']);
} else {
    if (isset($_POST['country']) && is_numeric($_POST['country']) && $db->query("SELECT * FROM client_countries WHERE active = 1 AND ID = " . intval($_POST['country']))->num_rows == 1) {
        $_SESSION['country'] = $_POST['country'];
    }

    $country = isset($_SESSION['country']) ? intval($_SESSION['country']) : intval($CFG['DEFAULT_COUNTRY']);
}

$countrySql = $db->query("SELECT * FROM client_countries WHERE ID = " . $country);
if ($countrySql->num_rows == 1) {
    $countryInfo = $countrySql->fetch_object();
}

if (!$var['logged_in']) {
    $tax = array($countryInfo->tax, $countryInfo->percent);
}

if (!$CFG['TAXES']) {
    $tax = array("", 0);
}

function calculateVat($price, $calculate = 1, $vat = 19)
{
    if ($calculate == 1) {
        $newPrice = $price + (($price / 100) * $vat);
        return round($newPrice, 2);
    } elseif ($calculate == 2) {
        $newPrice = ($price * 100) / (100 + $vat);
        return round($newPrice, 2);
    } else {
        $newPrice = (($price * 100) / (100 + $vat)) * ($vat / 100);
        return round($newPrice, 2);
    }
}

$tempVat = TempVat::rate($countryInfo->alpha2, $tax[1]);
if ($tempVat) {
    $tax[1] = $tempVat;
    $countryInfo->percent = $tempVat;
}

$var['tax_info'] = $tax;

if (!$var['logged_in'] && $CFG['TAXES']) {
    $sql = $db->query("SELECT * FROM client_countries WHERE active = 1 ORDER BY name ASC");
    $var['countries'] = array();
    while ($row = $sql->fetch_object()) {
        $var['countries'][$row->ID] = $row->name;
    }

}

// Buy string
if (isset($pars[0]) && $pars[0] == "buy" && isset($pars[1]) && isset($pars[2])) {
    $_GET['buy'] = $pars[1];
    $_GET['amount'] = $pars[2];
}

// Add voucher
if (isset($_POST['add_voucher']) && $cart->getVoucher() === false) {
    try {
        $sql = $db->query("SELECT * FROM vouchers WHERE code = '" . $db->real_escape_string($_POST['code']) . "'");
        if ($sql->num_rows != 1) {
            throw new Exception($lang['CART']['WRONG_VOUCHER']);
        }

        $info = $sql->fetch_object();

        if (($info->user != 0 && !$var['logged_in']) || ($info->ID == $CFG['BIRTHDAY_VOUCHER'] && !$var['logged_in'])) {
            throw new Exception($lang['CART']['VOUCHER_WRONG_USER_LOGIN']);
        }

        if (($info->user != 0 && $info->user != $user->get()['ID']) || $info->ID == $CFG['BIRTHDAY_VOUCHER']) {
            throw new Exception($lang['CART']['VOUCHER_WRONG_USER']);
        }

        if ($info->active != 1) {
            throw new Exception($lang['CART']['VOUCHER_NOT_ACTIVE']);
        }

        if ($info->valid_from > time() && $info->valid_from > 0) {
            throw new Exception($lang['CART']['VOUCHER_NOT_VALID_YET']);
        }

        if ($info->valid_to < time() && $info->valid_to > 0) {
            throw new Exception($lang['CART']['VOUCHER_NOT_VALID_ANYMORE']);
        }

        if ($info->uses > $info->max_uses && $info->max_uses >= 0) {
            throw new Exception($lang['CART']['VOUCHER_USED']);
        }

        if (!$cart->checkVoucherUsage($_POST['code'])) {
            throw new Exception($lang['CART']['VOUCHER_YOU_USED']);
        }

        $var['voucher_ok'] = $lang['CART']['VOUCHER_OK'];
        $cart->addVoucher($info->ID);
        if ($var['logged_in']) {
            $cart->__construct($user->get()['ID']);
        }

        $cart->piwik();
        unset($_POST);
    } catch (Exception $ex) {
        $var['voucher_error'] = $ex->getMessage();
    }
}

// Update quantity of a product if it is passed within GET
if (isset($_GET['qty']) && is_numeric($_GET['qty']) && $_GET['qty'] >= 0 && isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
    $id = $db->real_escape_string($_GET['id']);
    $qty = $db->real_escape_string($_GET['qty']);
    $cart->changeQty($id, $qty);
    $cart->piwik();
    $cart->__destruct();
}

// Check if user wished to delete something
if (isset($_GET['delete'])) {
    if ($_GET['delete'] == "all") {
        $cart->null();
        $cart->piwik();
        $cart->__destruct();
    } else {
        $cart->removeElement($_GET['delete']);
        $cart->piwik();
        $cart->__destruct();
    }
}

// Init a new cart for this user to get real time informationen
$var['cart'] = $cart->get();
$var['cart_count'] = $cart->count();

$res = 0;
$sum = 0.00;
$cartitems = array();

// Iterate all cart items from database and set it into @var cartitems
// Also, we check if there are any reseller products and we calculate the sum
$factor = $var['logged_in'] ? $user->getRaw()['pricelevel'] / 100 : 1;
foreach ($cart->get() as $ID => $item) {
    if (!empty($item['additional'])) {
        $additional = unserialize($item['additional']);

        if (isset($additional['domain']) && !empty($additional['domain'][1])) {
            if (!empty($item['desc'])) {
                $item['desc'] .= "<br />";
            }

            $item['desc'] .= "{$lang['CONFIGURE']['DOMAIN']}: " . htmlentities($additional['domain'][1]);

            if ($additional['domain'][0] != "own") {
                $domain = $additional['domain'][1];
                $ex = explode(".", $domain);
                $sld = array_shift($ex);
                $tld = implode(".", $ex);

                $normalAmount = 0;
                $sql = $db->query("SELECT register, transfer FROM domain_pricing WHERE tld = '" . $db->real_escape_string(ltrim($tld, ".")) . "'");
                if ($row = $sql->fetch_object()) {
                    $normalAmount = $row->{$additional['domain'][0]};
                    $normalAmount = $cur->convertAmount(null, $normalAmount, null) * (1 + $tax[1] / 100);
                }

                $amount = $var['logged_in'] ? $user->addTax($user->getDomainPrice($tld, $additional['domain'][0])) : $normalAmount;

                $cartitems[] = [
                    "qty" => 1,
                    "ID" => rand(10000000, 99999999),
                    "typ" => $additional['domain'][0] == "register" ? "domain_reg" : "domain_id",
                    "raw_amount" => $cur->convertAmount(null, $amount, null, true, true),
                    "amount" => $nfo->format($cur->convertAmount(null, $amount, null, true, true)),
                    "f_amount" => $cur->infix($nfo->format($cur->convertAmount(null, $amount, null, true, true))),
                    "sum" => $nfo->format($cur->convertAmount(null, $amount, null, true, true)),
                    "f_sum" => $cur->infix($nfo->format($cur->convertAmount(null, $amount, null, true, true))),
                    "oldprice" => $cur->convertAmount(null, $amount, null, true, true),
                    "oldprice_f" => $cur->infix($nfo->format($cur->convertAmount(null, $amount, null, true, true))),
                    "oldsum_f" => $cur->infix($nfo->format($cur->convertAmount(null, $amount, null, true, true))),
                    "desc" => "",
                    "setup" => 0,
                    "billing" => "annually",
                    "f_setup" => $cur->infix($nfo->format($cur->convertAmount(null, 0, null, true, true))),
                    "setup_sum" => $cur->realAmount(0),
                    "f_setup_sum" => $cur->infix($nfo->format($cur->convertAmount(null, 0, null, true, true))),
                    "type" => serialize(["domain" => $additional['domain'][1]]),
                    "ignore" => true,
                    "prepaid" => $item['prepaid'] ?? false,
                    "prorata" => $item['prorata'] ?? false,
                ];

                $sum += $cur->convertAmount(null, $amount, null, true, true);
            }
        }

        if (isset($additional['domains']) && is_array($additional['domains']) && count($additional['domains']) > 0) {
            if (!empty($item['desc'])) {
                $item['desc'] .= "<br /><br />";
            }

            $i = 1;
            foreach ($additional['domains'] as $domain => $auth) {
                $item['desc'] .= "<i>{$lang['CONFIGURE']['INCLCART']}: </i>" . htmlentities($domain);
                if ($i < count($additional['domains'])) {
                    $item['desc'] .= "<br />";
                }

                $i++;
            }
        }

        if (isset($additional['customfields']) && is_array($additional['customfields']) && count($additional['customfields']) > 0) {
            if (!empty($item['desc'])) {
                $item['desc'] .= "<br /><br />";
            }

            $i = 0;
            foreach ($additional['customfields'] as $cf => $v) {
                $i++;
                $cfSql = $db->query("SELECT name, type FROM products_cf WHERE ID = " . intval($cf));
                if ($cfSql->num_rows != 1) {
                    continue;
                }

                $cfInfo = $cfSql->fetch_object();

                if ($cfInfo->type == "check") {
                    $v = $lang['GENERAL'][$v ? "YES" : "NO"];
                }

                $item['desc'] .= "<i>{$cfInfo->name}: </i>" . htmlentities($v);
                if ($i < count($additional['customfields'])) {
                    $item['desc'] .= "<br />";
                }

            }
        }
    }

    if ($item['type'] == "product" && $item['relid']) {
        $sql = $db->query("SELECT * FROM products WHERE ID = " . intval($item['relid']));
        if ($sql->num_rows == 1) {
            $prod = $sql->fetch_object();
            $description = $prod->description;
            if (is_array($description = @unserialize($description))) {
                $description = $description[$CFG['LANG']];

                if (!empty($description)) {
                    if (!empty($item['desc'])) {
                        $item['desc'] .= "<br /><br />";
                    }

                    $item['desc'] .= nl2br($description);
                }
            }

            if ($item['variant'] !== "") {
                $variants = @unserialize($prod->variants);
                if (is_array($variants) && array_key_exists($item['variant'], $variants)) {
                    $variant = $variants[$item['variant']];
                    $prod->billing = $variant["billing"];

                    if (!empty($variant['ct1'])) {
                        $prod->ct = $variant["ct1"] . " " . $variant["ct2"];
                    }

                    if (!empty($variant['mct1'])) {
                        $prod->mct = $variant["mct1"] . " " . $variant["mct2"];
                    }

                    if (!empty($variant['np1'])) {
                        $prod->np = $variant["np1"] . " " . $variant["np2"];
                    }
                }
            }

            if ($prod->ct || $prod->mct || $prod->np) {
                if (!empty($item['desc'])) {
                    $item['desc'] .= "<br />";
                }

                if ($prod->ct) {
                    $item['desc'] .= "<br />" . format_ctime($prod->ct) . " " . $lang['CONFIGURE']['CT'];
                }

                if ($prod->mct) {
                    $item['desc'] .= "<br />" . format_ctime($prod->mct) . " " . $lang['CONFIGURE']['MCT'];
                }

                if ($prod->np) {
                    $item['desc'] .= "<br />" . format_ctime($prod->np) . " " . $lang['CONFIGURE']['NP'];
                }
            }
        }
    }

    $prorata = 0;
    $oprorata = 0;
    if ($item['prorata'] ?? false) {
        $breakdown = [
            "monthly" => "30",
            "quarterly" => "90",
            "semiannually" => "180",
            "annually" => "360",
            "biennially" => "720",
            "triennially" => "1080",
        ];

        if (array_key_exists($item['billing'], $breakdown)) {
            $prorata = ($item['amount'] / $breakdown[$item['billing']]) * (date("t") - date("d") + 1);
            $oprorata = ($item['oldprice'] / $breakdown[$item['billing']]) * (date("t") - date("d") + 1);
        } else {
            $item['prorata'] = false;
        }
    }

    $len = in_array($item['billing'], ["minutely", "hourly"]) ? max(2, strlen(substr(strrchr(rtrim($item['amount'], "0"), "."), 1))) : 2;
    $cartitems[] = array(
        "qty" => $item["qty"],
        "ID" => $ID,
        "typ" => $item['type'],
        "relid" => $item['relid'],
        "name" => unserialize($item['name'])[$CFG['LANG']],
        "raw_amount" => round($cur->convertAmount(null, $item['amount'], null, false, true), $len),
        "amount" => $nfo->format(round($cur->convertAmount(null, $item['amount'], null, false, true), $len), $len),
        "f_amount" => $cur->infix($nfo->format(round($cur->convertAmount(null, $item['amount'], null, false, true), $len), $len)),
        "sum" => $nfo->format(round($cur->convertAmount(null, $item['amount'], null, false, true) * $item['qty'], $len), $len),
        "f_sum" => $cur->infix($nfo->format(round($cur->convertAmount(null, $item['amount'], null, false, true) * $item['qty'], $len), $len)),
        "oldprice" => round($cur->convertAmount(null, $item["oldprice"], null, false, true), $len),
        "oldprice_f" => $cur->infix($nfo->format(round($cur->convertAmount(null, $item["oldprice"], null, false, true), $len), $len)),
        "oldsum_f" => $cur->infix($nfo->format(round($cur->convertAmount(null, $item["oldprice"], null, false, true) * $item['qty'], $len), $len)),
        "type" => $item['license'],
        "desc" => isset($item['desc']) ? $item['desc'] : "",
        "setup" => $cur->realAmount($item['setup'], true), "billing" => $item['billing'],
        "f_setup" => $cur->infix($nfo->format($cur->convertAmount(null, $item['setup'], null, true, true))),
        "setup_sum" => $cur->realAmount($item['setup']) * $item['qty'],
        "f_setup_sum" => $cur->infix($nfo->format($cur->convertAmount(null, $item['setup'], null, true, true) * $item['qty'])),
        "ptype" => $item['ptype'],
        "additional" => $item["additional"],
        "variant" => $item["variant"],
        "prepaid" => $item['prepaid'] ?? false,
        "prorata" => $item['prorata'] ?? false,
        "prorata_breakdown" => $cur->infix($nfo->format($cur->convertAmount(null, $prorata))),
        "prorata_breakdown_raw" => $cur->convertAmount(null, $prorata),
        "prorata_breakdown_opraw" => $cur->convertAmount(null, $oprorata),
        "prorata_date" => $dfo->format(date("Y-m-t"), false, false),
    );

    if ($item['prorata'] ?? false) {
        $item['amount'] = $prorata;
    }

    $sum += $item['qty'] * round($cur->realAmount($item['amount'], false), $len);
    if ($item['setup'] != 0) {
        $sum += $cur->realAmount($item['setup']) * $item['qty'];
    }

}

// We assign a few variables to template
$var['res'] = $res;
$var['cartitems'] = $cartitems;
$var['sum'] = round($sum, 2);
$var['sum_f'] = $cur->infix($nfo->format($cur->convertAmount(null, round($sum, 2), null)));
$var['buyed'] = 0;
$var['block'] = $block;
if ($cart->getVoucher() !== false) {
    $var['voucher'] = (array) $cart->getVoucher();
}

if (is_array($tax) && $CFG['TAXES']) {
    $var['tax'] = calculateVat($cur->convertAmount(null, $sum, null), 3, $countryInfo->percent);
    $var['tax_f'] = $cur->infix($nfo->format($var['tax']));

    $tempVat = TempVat::rate($countryInfo->alpha2, $countryInfo->percent);
    if ($tempVat) {
        $countryInfo->percent = $tempVat;
    }

    $var['country_tax'] = $countryInfo;
}

// Create new account if guest order is confirmed
if (
    isset($pars[0]) &&
    $pars[0] == "confirm" &&
    isset($pars[1]) &&
    is_numeric($pars[1]) &&
    isset($pars[2]) &&
    is_object($sql = $db->query("SELECT info FROM guest_orders WHERE ID = " . intval($pars[1]) . " AND hash = '" . $db->real_escape_string($pars[2]) . "'")) &&
    $sql->num_rows == 1 &&
    $info = unserialize($sql->fetch_object()->info)
) {
    if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($info['email']) . "'")->num_rows > 0) {
        $tpl = "error";
        $title = $lang['ERROR']['TITLE'];
        return;
    }

    // Get client IP address
    $ip = ip();

    // Affiliate
    $affiliate = 0;
    if (!empty($_COOKIE['affiliate']) && is_numeric($_COOKIE['affiliate']) && User::getInstance($_COOKIE['affiliate'], "ID")) {
        $affiliate = intval($_COOKIE['affiliate']);
    }

    // Get standard newsletter
    $sql = $db->query("SELECT ID FROM newsletter_categories WHERE standard = 1");
    $nl = array();
    while ($row = $sql->fetch_object()) {
        array_push($nl, $row->ID);
    }

    $nl = $db->real_escape_string(implode("|", $nl));
    if (!$info['newsletter']) {
        $nl = "";
    }

    $cgroup = 0;
    if ($CFG['DEFAULT_CGROUP'] && $db->query("SELECT 1 FROM client_groups WHERE ID = " . intval($CFG['DEFAULT_CGROUP']))->num_rows) {
        $cgroup = intval($CFG['DEFAULT_CGROUP']);
    }

    // If all is correct, we insert the new user into database and get the new ID for further use
    $limit = doubleval($CFG['POSTPAID_DEF']);
    $db->query("INSERT INTO clients (`cgroup`, `mail`, `salutation`, `firstname`, `lastname`, `registered`, `last_login`, `last_ip`, `affiliate`, newsletter, postpaid, cust_source) VALUES ($cgroup, '" . $db->real_escape_string($info['email']) . "', '" . $db->real_escape_string($info['salutation']) . "', '" . $db->real_escape_string($info['firstname']) . "', '" . $db->real_escape_string($info['lastname']) . "', '" . time() . "', '" . time() . "', '" . $db->real_escape_string($ip) . "', $affiliate, '$nl', $limit, '" . $db->real_escape_string($info['cust_source'] ?? "") . "')");
    $newID = $db->insert_id;
    $db->query("INSERT INTO ip_logs (time, user, ip) VALUES (" . time() . ", " . $newID . ", '" . $db->real_escape_string($ip) . "')");

    // We will assign all mail sent before to the customers email
    $db->query("UPDATE client_mails SET user = $newID WHERE recipient = '" . $db->real_escape_string($info['email']) . "'");

    // User instance
    $user = new User($info['email']);

    // Set user data
    $data = array(
        "company" => $info['company'],
        "street" => $info['street'],
        "street_number" => $info['street_number'],
        "postcode" => $info['postcode'],
        "city" => $info['city'],
        "country" => $info['country'],
        "telephone" => $info['telephone'],
        "birthday" => date("Y-m-d", strtotime($info['birthday'])),
        "website" => $info['website'],
        "vatid" => $info['vatid'],
        "coordinates" => $info['coordinates'],
        "tos" => $db->query("SELECT ID FROM terms_of_service ORDER BY ID DESC")->fetch_object()->ID ?: 1,
        "withdrawal_rules" => 1,
        "privacy_policy" => 1,
    );
    $user->set($data);

    // Set custom fields
    foreach ($info as $key => $value) {
        if (substr($key, 0, 3) == "cf_") {
            if (array_key_exists(substr($key, 3), $var['cf']) && !$var['cf'][substr($key, 3)][3]) {
                $user->setField(substr($key, 3), $value);
            }
        }
    }

    $user->saveChanges("client", false);
    $user->autoScore();

    // Set user password
    $data = array();
    $data['salt'] = $sec->generateSalt();
    $data['pwd'] = $sec->hash($info['pwh'] ?: $info['pw'], $data['salt'], isset($info['pwh']));
    $user->set($data);
    $user->resetChanges();

    // Set the session
    if (!$CFG['USER_CONFIRMATION']) {
        $session->set('mail', $user->get()['mail']);

        if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
            $session->set('pwd', md5($user->get()['pwd']));
        } else {
            $session->set('pwd', $user->get()['pwd']);
        }

    }

    // Import cart contents
    $cart = new Cart($user->get()['ID']);
    $cart->importSession();

    // Insert into log and enqueue a mail to new customer
    $mtObj = new MailTemplate("Konto wurde aktiviert");

    $titlex = $mtObj->getTitle($CFG['LANG']);
    $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

    $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

    $user->log("Hat sich registriert (per Bestellung)");

    // Send admin notification/s
    if (($ntf = AdminNotification::getInstance("Neuer Kunde")) !== false) {
        $ntf->set("name", $user->get()['name']);
        $ntf->set("email", $user->get()['mail']);
        $ntf->set("cid", $user->get()['ID']);
        $ntf->send();
    }

    // Delete database row
    $db->query("DELETE FROM guest_orders WHERE ID = " . intval($pars[1]) . " AND hash = '" . $db->real_escape_string($pars[2]) . "'");

    // Redirect
    $_SESSION['card']['ok'] = 1;
    if ($CFG['USER_CONFIRMATION']) {
        header('Location: ' . $CFG['PAGEURL'] . 'cart/confirmation');
    }

    if (count($cart->get()) > 0) {
        header('Location: ' . $CFG['PAGEURL'] . 'cart/payment');
    } else {
        header('Location: ' . $CFG['PAGEURL']);
    }

    exit;
}

// Step
if (count($cartitems) > 0 && isset($pars[0]) && $pars[0] == "data") {
    $var['step'] = 2;
}
if (isset($_SESSION['card']['ok']) && $_SESSION['card']['ok'] && isset($pars[0]) && $pars[0] == "payment" && $var['logged_in'] && !IdentifyProxy::is()) {
    $var['step'] = 3;
}
if (isset($pars[0]) && $pars[0] == "confirmation" && !$var['logged_in'] && !IdentifyProxy::is()) {
    $var['step'] = 2;
    $var['waiting_conf'] = true;
}

if ($var['step'] == 2) {
    $ci = $db->real_escape_string(serialize($cart->get()));

    // Get required system fields
    $arr = $fieldVars = array(
        "firstname" => "Vorname",
        "lastname" => "Nachname",
        "company" => "Firma",
        "street" => "Straße",
        "street_number" => "Hausnummer",
        "postcode" => "Postleitzahl",
        "city" => "Ort",
        "country" => "Land",
        "telephone" => "Telefonnummer",
        "birthday" => "Geburtstag",
        "website" => "Webseite",
        "vatid" => "USt-IdNr.",
    );

    $var['duty_fields'] = array();
    $var['ro_fields'] = array();

    foreach ($arr as $db2 => $field) {
        $info = $db->query("SELECT * FROM client_fields WHERE name = '" . $db->real_escape_string($field) . "' AND active = 1 AND system > 0 AND customer > 0");
        if ($info->num_rows != 1) {
            continue;
        }

        $info = $info->fetch_object();

        if ($info->duty > 0) {
            $var['duty_fields'][] = $db2;
        }

        if ($info->customer < 2) {
            $var['ro_fields'][] = $db2;
        }

    }

    // Get custom duty fields
    $var['cf'] = array();
    $sql = $db->query("SELECT * FROM client_fields WHERE `system` = 0 AND active = 1 AND customer > 0 AND duty > 0 ORDER BY position ASC, name ASC, ID ASC");
    while ($row = $sql->fetch_object()) {
        $value = "";
        if (isset($_POST['reg_cf'][$row->ID])) {
            $value = $_POST['reg_cf'][$row->ID];
        } else if (isset($_SESSION['card']['cf_' . $row->ID])) {
            $value = $_SESSION['card']['cf_' . $row->ID];
        } else if ($var['logged_in']) {
            $value = $user->getField($row->ID);
        }

        $var['cf'][$row->ID] = array($row->name, $value, $row->regex, $row->customer != 2);
    }

    // Check if vatid
    $var['vatid'] = $CFG['TAXES'] && $CFG['EU_VAT'] && $db->query("SELECT 1 FROM client_fields WHERE name = 'USt-IdNr.' AND active = 1 AND system > 0")->num_rows;

    // Print countries to template
    $countries = array();
    $sql = $db->query("SELECT ID, name FROM client_countries WHERE active = 1 ORDER BY name ASC");
    while ($r = $sql->fetch_object()) {
        $countries[$r->ID] = $r->name;
    }

    $var['countries'] = $countries;

    // Get country
    $var['country'] = isset($_SESSION['country']) ? intval($_SESSION['country']) : ($var['logged_in'] ? $user->get()['country'] : intval($CFG['DEFAULT_COUNTRY']));

    // Insert customer data
    if ($var['logged_in']) {
        foreach (array_merge($var['duty_fields'], array("firstname", "lastname", "company")) as $key) {
            if (!isset($_SESSION['card'][$key])) {
                $_SESSION['card'][$key] = $user->get()[$key];
            }
        }

    }

    // Handle POST data
    try {
        if (isset($_POST['action']) && $_POST['action'] == "cont") {
            $_SESSION['card']['ok'] = 0;

            if (IdentifyProxy::is()) {
                throw new Exception($lang['GENERAL']['BLOCKED']);
            }

            // Check captcha
            if (empty($_SESSION['card']['captcha']) && $captcha->verify()) {
                $_SESSION['card']['captcha'] = 1;
            }

            // Check token
            if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['cart_token']) {
                throw new Exception($lang['CART']['EXPIRED']);
            }

            if (!$var['logged_in'] && isset($_POST['customer']) && $_POST['customer'] == "login") {
                // Get POST variables into local variables
                $mail = $_POST['login_email'];
                $pwd = $_POST['login_password'];

                // Try to build a user object
                $user = new User($mail);

                // Check if any mail was submitted
                if (trim($_POST['login_email']) == "" || !filter_var($_POST['login_email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception($lang['LOGIN']['NO_MAIL']);
                }

                // Check if user exists
                if (!$user || $user->get() == null) {
                    $f2b->failedLogin();
                    throw new Exception($lang['LOGIN']['WRONG_CREDENTIALS']);
                }

                // Check if the password is correct
                if ($sec->hash($pwd, $user->get()['salt']) != $user->get()['pwd'] && ($CFG['CLIENTSIDE_HASHING'] != 1 || $sec->hash($pwd, $user->get()['salt'], true) != $user->get()['pwd'])) {
                    $f2b->failedLogin();
                    $user->log("Login-Versuch (Falsches Passwort)");
                    $user->set(array("failed_login" => time()));
                    throw new Exception($lang['LOGIN']['WRONG_CREDENTIALS']);
                }

                // Check if the user account is locked by an administrator
                if ($user->get()['locked'] == 1) {
                    $user->log("Login-Versuch (Benutzer gesperrt)");
                    throw new Exception($lang['LOGIN']['LOCKED']);
                }

                // Check if user confirmation is required
                if ($CFG['USER_CONFIRMATION'] == 1 && $user->get()['confirmed'] != 1) {
                    $user->log("Login-Versuch (Benutzer noch nicht frei)");
                    throw new Exception($lang['LOGIN']['UNFREE']);
                }

                // Check 2FA
                if (!empty($user->get()['tfa']) && $user->get()['tfa'] != "none") {
                    if (!$tfa->verifyCode($user->get()['tfa'], $_POST['login_otp'], 2) || $db->query("SELECT * FROM client_tfa WHERE user = " . $user->get()['ID'] . " AND code = '" . $db->real_escape_string($_POST['login_otp']) . "'")->num_rows != 0) {
                        // Send notification email
                        $mtObj = new MailTemplate("Zwei-Faktor-Code fehlerhaft");

                        $titlex = $mtObj->getTitle($CFG['LANG']);
                        $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

                        $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                        // Log action
                        $user->log("Falschen 2FA-Code eingegeben");

                        // Fail2Ban log
                        $f2b->failedLogin();

                        throw new Exception($lang['TFA']['WRONG']);
                    } else {
                        $code = $db->real_escape_string($_POST['login_otp']);

                        // Code is correct, mark it as used and do not ask for further codes this session
                        $db->query("INSERT INTO client_tfa (user, code, time) VALUES (" . $user->get()['ID'] . ", '$code', " . time() . ")");
                        $session->set('tfa', true);

                        // Send notification email
                        $mtObj = new MailTemplate("Zwei-Faktor-Code richtig");

                        $titlex = $mtObj->getTitle($CFG['LANG']);
                        $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

                        $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                        // Log action
                        $user->log("Richtigen 2FA-Code eingegeben");
                    }
                }

                // Set the session
                $session->set('mail', $mail);

                if ($CFG['HASH_METHOD'] == "plain" || $CFG['HASH_METHOD'] == "none" || $CFG['HASH_METHOD'] == "") {
                    $session->set('pwd', md5($user->get()['pwd']));
                } else {
                    $session->set('pwd', $user->get()['pwd']);
                }

                // Set the last login time to now and log into activity log
                $user->set(array('last_login' => time(), "failed_login" => 0));

                // Check if the user wants a login notification
                if ($user->get()['login_notify']) {
                    // If so, send the user an email
                    $mtObj = new MailTemplate("Login-Benachrichtigung");
                    $userLang = isset($user->get()['language']) && trim($user->get()['language']) != "" && file_exists(__DIR__ . "/../" . $user->get()['language'] . ".php") ? $user->get()['language'] : $CFG['LANG'];

                    $titlex = $mtObj->getTitle($userLang);
                    $mail = $mtObj->getMail($userLang, $user->get()['name']);

                    $maq->enqueue([], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($userLang));
                }

                $addons->runHook("CustomerLogin", [
                    "user" => $user,
                    "source" => "cart",
                ]);

                // Log action
                $user->log("Hat sich eingeloggt");

                // Save checkboxes
                if (empty($_POST['terms']) || $_POST['terms'] != "yes") {
                    $_SESSION['card']['terms'] = "no";
                } else {
                    $_SESSION['card']['terms'] = "yes";
                }

                if (empty($_POST['privacy']) || $_POST['privacy'] != "yes") {
                    $_SESSION['card']['privacy'] = "no";
                } else {
                    $_SESSION['card']['privacy'] = "yes";
                }

                if (empty($_POST['withdrawal']) || $_POST['withdrawal'] != "yes") {
                    $_SESSION['card']['withdrawal'] = "no";
                } else {
                    $_SESSION['card']['withdrawal'] = "yes";
                }

                if (empty($_POST['cancel']) || $_POST['cancel'] != "yes") {
                    $_SESSION['card']['cancel'] = "no";
                } else {
                    $_SESSION['card']['cancel'] = "yes";
                }

                // Import cart contents
                $cart = new Cart($user->get()['ID']);
                $cart->null();
                $cart->importSession();

                // Template vars
                header('Location: ' . $CFG['PAGEURL'] . 'cart/data');
                exit;
            }

            $_SESSION['card']['salutation'] = isset($_POST['reg_salutation']) && $_POST['reg_salutation'] == "FEMALE" ? "FEMALE" : (isset($_POST['reg_salutation']) && $_POST['reg_salutation'] == "DIVERS" ? "DIVERS" : "MALE");

            if (empty($_POST['reg_firstname'])) {
                throw new Exception($lang['CART']['EFIRSTNAME']);
            }

            $_SESSION['card']['firstname'] = $_POST['reg_firstname'];

            if (empty($_POST['reg_lastname'])) {
                throw new Exception($lang['CART']['ELASTNAME']);
            }

            $_SESSION['card']['lastname'] = $_POST['reg_lastname'];

            $_SESSION['card']['company'] = $_POST['reg_company'];

            if (!$var['logged_in']) {
                if (empty($_POST['reg_email'])) {
                    throw new Exception($lang['CART']['EEMAIL']);
                }

                if (!$val->email($_POST['reg_email'])) {
                    throw new Exception($lang['CART']['IEMAIL']);
                }

                if ($db->query("SELECT 1 FROM clients WHERE mail = '" . $db->real_escape_string($_POST['reg_email']) . "'")->num_rows > 0) {
                    throw new Exception($lang['CART']['ALREG']);
                }

                $_SESSION['card']['email'] = $_POST['reg_email'];

                if (empty($_POST['reg_pw1']) || strlen($_POST['reg_pw1']) < 8 || (isset($_POST['reg_pwl']) && $_POST['reg_pwl'] < 8)) {
                    throw new Exception($lang['CART']['EPW1']);
                }

                if ($_POST['reg_pw1'] != $_POST['reg_pw2']) {
                    throw new Exception($lang['CART']['EPW2']);
                }

                $_SESSION['card']['pw' . (isset($_POST['reg_pwl']) ? "h" : "")] = $_POST['reg_pw1'];
            }

            if (in_array("street", $var['duty_fields']) && empty($_POST['reg_street'])) {
                throw new Exception($lang['CART']['ESTREET']);
            }

            if (in_array("street", $var['duty_fields'])) {
                $_SESSION['card']['street'] = $_POST['reg_street'];
            }

            if (in_array("street_number", $var['duty_fields']) && empty($_POST['reg_street_number'])) {
                throw new Exception($lang['CART']['ESN']);
            }

            if (in_array("street_number", $var['duty_fields'])) {
                $_SESSION['card']['street_number'] = $_POST['reg_street_number'];
            }

            if (in_array("postcode", $var['duty_fields']) && empty($_POST['reg_postcode'])) {
                throw new Exception($lang['CART']['EPC']);
            }

            if (in_array("postcode", $var['duty_fields'])) {
                $_SESSION['card']['postcode'] = $_POST['reg_postcode'];
            }

            if (in_array("city", $var['duty_fields']) && empty($_POST['reg_city'])) {
                throw new Exception($lang['CART']['ECITY']);
            }

            if (in_array("city", $var['duty_fields'])) {
                $_SESSION['card']['city'] = $_POST['reg_city'];
            }

            if (in_array("country", $var['duty_fields']) && (empty($_POST['reg_country']) || !array_key_exists($_POST['reg_country'], $var['countries']))) {
                throw new Exception($lang['CART']['ECOUN']);
            }

            if (in_array("country", $var['duty_fields'])) {
                $_SESSION['card']['country'] = $_POST['reg_country'];
            }

            if (in_array("telephone", $var['duty_fields']) && empty($_POST['reg_telephone'])) {
                throw new Exception($lang['CART']['ETEL1']);
            }

            if (!empty($_POST['telefon']) && !is_numeric(str_replace(array("+", " ", "-", "/"), array("00", "", "", ""), $_POST['telephone']))) {
                throw new Exception($lang['CART']['ETEL2']);
            }

            if (in_array("telephone", $var['duty_fields'])) {
                $_SESSION['card']['telephone'] = $_POST['reg_telephone'];
            }

            if (in_array("birthday", $var['duty_fields']) && empty($_POST['reg_birthday'])) {
                throw new Exception($lang['CART']['EBD1']);
            }

            if (!empty($_POST['reg_birthday']) && (strtotime($_POST['reg_birthday']) === false || strtotime($_POST['reg_birthday']) > time())) {
                throw new Exception($lang['CART']['EBD2']);
            }

            if (in_array("birthday", $var['duty_fields'])) {
                $_SESSION['card']['birthday'] = $_POST['reg_birthday'];
            }

            if (in_array("website", $var['duty_fields']) && empty($_POST['reg_website'])) {
                throw new Exception($lang['CART']['EWS']);
            }

            if (in_array("website", $var['duty_fields'])) {
                $_SESSION['card']['website'] = $_POST['reg_website'];
            }

            if ($var['vatid'] && in_array("vatid", $var['duty_fields']) && empty($_POST['reg_vatid'])) {
                throw new Exception($lang['CART']['EVAT1']);
            }

            if ($var['vatid'] && !empty($_POST['reg_vatid']) && is_object($obj = new EuVAT($_POST['reg_vatid'])) && !$obj->isValid()) {
                throw new Exception($lang['CART']['EVAT2']);
            }

            if (in_array("vatid", $var['duty_fields'])) {
                $_SESSION['card']['vatid'] = $_POST['reg_vatid'];
            }

            $_SESSION['card']['newsletter'] = !empty($_POST['reg_newsletter']);

            $_SESSION['card']['coordinates'] = "";
            if (isset($_SESSION['card']['city']) && isset($_SESSION['card']['postcode']) && isset($_SESSION['card']['street_number']) && in_array("city", $var['duty_fields']) && in_array("postcode", $var['duty_fields'])) {
                $country = "";
                if (array_key_exists($_POST['reg_country'], $var['countries'])) {
                    $country = ", " . $var['countries'][$_POST['reg_country']];
                }

                $loc = GeoLocation::getLocation($_SESSION['card']['street'] . " " . $_SESSION['card']['street_number'] . ", " . $_SESSION['card']['postcode'] . " " . $_SESSION['card']['city'] . $country);
                if ($loc) {
                    $_SESSION['card']['coordinates'] = serialize($loc);
                }

            }

            if (!$var['logged_in'] && isset($_POST['customer']) && $_POST['customer'] == "new" && empty($_SESSION['card']['captcha'])) {
                throw new Exception($lang['CART']['ECAPT']);
            }

            if (!$var['logged_in'] && $var['cust_source']) {
                $pcs = $_POST['cust_source'] ?? "";
                $cso = $var['cso'];
                if (!$var['cust_source_duty']) {
                    $cso[] = "";
                }

                if (!in_array($pcs, $cso)) {
                    throw new Exception($lang['CUST_SOURCE']['FAIL']);
                }

                $_SESSION['card']['cust_source'] = $pcs;
            }

            if (empty($_POST['terms']) || $_POST['terms'] != "yes") {
                $_SESSION['card']['terms'] = "no";
                throw new Exception($lang['CART']['ETERMS']);
            } else {
                $_SESSION['card']['terms'] = "yes";
            }

            if (empty($_POST['withdrawal']) || $_POST['withdrawal'] != "yes") {
                $_SESSION['card']['withdrawal'] = "no";
                throw new Exception($lang['CART']['EWITHDRAWAL']);
            } else {
                $_SESSION['card']['withdrawal'] = "yes";
            }

            if (empty($_POST['privacy']) || $_POST['privacy'] != "yes") {
                $_SESSION['card']['privacy'] = "no";
                throw new Exception($lang['CART']['EPRIVACY']);
            } else {
                $_SESSION['card']['privacy'] = "yes";
            }

            if (empty($_POST['cancel']) || $_POST['cancel'] != "yes") {
                $_SESSION['card']['cancel'] = "no";
                throw new Exception($lang['CART']['ECANCEL']);
            } else {
                $_SESSION['card']['cancel'] = "yes";
            }

            foreach ($var['cf'] as $id => $info) {
                $name = $info[0];
                $regex = $info[2];

                if (empty($_POST['reg_cf'][$id])) {
                    throw new Exception(str_replace("%f", $name, $lang['CART']['EFIELD']));
                }

                if (!empty($info[2]) && !preg_match($info[2], $_POST['reg_cf'][$id])) {
                    throw new Exception(str_replace("%f", $name, $lang['PROFILE']['CF_ERROR']));
                }

                $_SESSION['card']['cf_' . $id] = $_POST['reg_cf'][$id];
                if ($var['logged_in']) {
                    $user->setField($id, $_POST['reg_cf'][$id]);
                }

            }

            if (!$var['logged_in']) {
                $hash = $sec->generatePassword(32, false, "ld");
                $info = $db->real_escape_string(serialize($_SESSION['card']));

                $db->query("INSERT INTO guest_orders (time, hash, info, cart) VALUES (" . time() . ", '$hash', '$info', '$ci')");
                $id = $db->insert_id;

                $link = $CFG['PAGEURL'] . "cart/confirm/$id/$hash";

                $headers = array();
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-type: text/plain; charset=utf-8";
                $headers[] = "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">";

                $mtObj = new MailTemplate("Gast-Bestellung");
                $titlex = $mtObj->getTitle($CFG['LANG']);
                $mail = $mtObj->getMail($CFG['LANG'], $_POST['reg_firstname'] . " " . $_POST['reg_lastname']);

                $maq->enqueue([
                    "link" => $link,
                ], $mtObj, $_POST['reg_email'], $titlex, $mail, implode("\r\n", $headers), 0, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                $var['waiting_mail'] = 1;
            } else {
                foreach ($_SESSION['card'] as $k => $v) {
                    if (is_array($v)) {
                        continue;
                    }

                    if (!array_key_exists($k, $user->get())) {
                        continue;
                    }

                    if (in_array($k, $var['ro_fields'])) {
                        continue;
                    }

                    if ($k == "newsletter" && empty($v)) {
                        continue;
                    }

                    if ($k == "birthday" && !empty($v)) {
                        $v = date("Y-m-d", strtotime($v));
                    }

                    $user->set(array($k => $v));
                }

                $user->saveChanges();
                $_SESSION['card']['ok'] = 1;
                header('Location: ' . $CFG['PAGEURL'] . 'cart/payment');
                exit;
            }
        }
    } catch (Exception $ex) {
        $var['error'] = $ex->getMessage();
    }

    // Get Captcha
    if (empty($_SESSION['card']['captcha']) && !$var['logged_in']) {
        $myCaptcha = $captcha->get();
        if ($session->get('captcha_solved_reg') == 1) {
            unset($myCaptcha);
        }

        if (is_array($myCaptcha) && isset($myCaptcha['type']) && $myCaptcha['type'] == "text") {
            $var['captchaText'] = $myCaptcha['value'];
        } else if (isset($myCaptcha['type']) && $myCaptcha['type'] == "modal") {
            $var['captchaModal'] = $myCaptcha['value'];
        } else if (isset($myCaptcha['type']) && $myCaptcha['type'] == "code") {
            $var['captchaCode'] = $myCaptcha['value'];
        }

        if (isset($myCaptcha['exec'])) {
            $var['captchaExec'] = $myCaptcha['exec'];
        }

    }

    // Token
    $var['token'] = $_SESSION['cart_token'] = $sec->generatePassword(32, false, "ld");
}

if ($var["logged_in"]) {
    $var['credit'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), abs($user->get()['credit']))));
    $var['credit_raw'] = $cur->convertAmount($cur->getBaseCurrency(), $user->get()['credit']);
    $var['sum'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), abs($sum))));
    $var['sum_raw'] = $cur->convertAmount($cur->getBaseCurrency(), $sum);
    $var['sum_raw2'] = $sum;
    $var['rest'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), abs($user->get()['credit'] - $sum))));
    $var['rest_raw'] = $cur->convertAmount($cur->getBaseCurrency(), $user->get()['credit'] - $sum);
    $var['rest_raw2'] = $user->get()['credit'] - $sum;
    $var['payment_amount'] = $nfo->format($var['rest_raw'] / -1);
    $var['currencyObj'] = $cur->getCurrent();
    $var['user_credit_f'] = $nfo->format($cur->convertAmount(null, $user->get()['credit'], null));
    $var['credit_f'] = $cur->infix($var['user_credit_f']);
    $var['special_f'] = $cur->infix($nfo->format($cur->convertAmount(null, $user->get()['special_credit'], null)));
    $var['normal_f'] = $cur->infix($nfo->format($cur->convertAmount(null, $user->get()['credit'] - $user->get()['special_credit'], null)));
    $var['additionalJS'] = "$('[data-toggle=\"popover\"]').popover();";
    $var['autPay'] = "";

    if ($user->autoPaymentStatus()) {
        $gateway = $user->get()['auto_payment_provider'];
        if (array_key_exists($gateway, $gateways->get(true))) {
            $gateway = $gateways->get(true)[$gateway];
            if (is_string($gateway->getLang("frontend_name"))) {
                $var['autPay'] = $gateway->getLang("frontend_name");
            } else {
                $var['autPay'] = $gateway->getLang("name");
            }
        }
    }
}

// We check if user wants to buy his cart content
if (isset($_GET['buy']) && isset($_GET['amount']) && $var['logged_in'] && !$block && !$var['smsverify']) {
    try {
        // Get voucher code from cart to insert it into table
        $voucherInfo = $cart->getVoucher();
        if ($voucherInfo !== false) {
            $voucher = $voucherInfo->ID;
        }

        // If the user have to be a reseller and is not, we decline the order
        if ($res == 1 && $user->get()['reseller'] != 1) {
            throw new Exception("");
        }

        // We change the formated amount into PHP readable and check it with the amount parameter
        $amount = round(floatval($_GET['amount']), 2);
        $sum = round($sum, 2);
        if ($amount != $sum) {
            throw new Exception("");
        }

        // We check if the quantity of elements is the same
        if ($_GET['buy'] != count($cartitems) || $_GET['buy'] <= 0) {
            throw new Exception("");
        }

        // If the user has not enough credit, we say payment is required
        $paymentRequired = 0;
        if ($sum > 0 && $user->getLimit() < $sum && !$CFG['NO_INVOICING']) {
            $paymentRequired = -1;
        }
        $paymentQueries = [];

        // Automatic payment
        $paymentReference = time() . "-" . uniqid(rand(100000, 999999));
        if ($var['rest_raw2'] < 0 && $var['autPay'] && $user->autoPayment($var['rest_raw2'] / -1)) {
            $paymentRequired = 0;
        }

        // Get the total discount
        $discount = $cart->getVoucherDiscount();

        // The cart is emptied, because we have all necessary information in @var cartitems and passed all checks
        $cartitems_bck = $cart->get();
        $cart->null();
        $_SESSION['card']['ok'] = 0;

        // This array contains all products in all languages
        $buyedProducts = array();
        foreach ($languages as $k => $v) {
            $buyedProducts[$k] = "";
        }

        $invoiceItems = array();
        $affiliate = 0;
        $extaffamount = 0;

        // We iterate all cartitems again
        foreach ($cartitems as $product) {
            // We iterate each cart item as often as the quantity the customer wish to buy
            for ($i = 0; $i < $product['qty']; $i++) {
                if ($product['typ'] == "product") {
                    $sql = $db->query("SELECT * FROM products WHERE status = 1 AND ID = " . $product['relid']);
                    if ($sql->num_rows != 1) {
                        continue;
                    }

                    $info = $sql->fetch_object();
                    $orderDomArr = [];

                    // Get module settings
                    $module_settings = array();
                    $sql = $db->query("SELECT setting, value FROM product_provisioning WHERE module = '" . $db->real_escape_string($info->module) . "' AND pid = " . intval($info->ID));
                    while ($row = $sql->fetch_object()) {
                        $module_settings[$row->setting] = decrypt($row->value);
                    }

                    $module_settings = $db->real_escape_string(encrypt(serialize($module_settings)));

                    // Handle custom fields
                    $product['additional'] = unserialize($product['additional']);
                    if (!is_array($product['additional'])) {
                        $product['additional'] = [];
                    }

                    if (!is_array($product['additional']['customfields'])) {
                        $product['additional']['customfields'] = [];
                    }

                    $cf = serialize($product['additional']['customfields']);

                    // Handle main domain
                    $description = "";
                    if (array_key_exists("domain", $product['additional']) && !empty($product['additional']['domain'][1])) {
                        $description = strtolower($product['additional']['domain'][1]);
                        $dType = $product['additional']['domain'][0];

                        array_push($orderDomArr, $description);

                        if (in_array($dType, ["register", "transfer"])) {
                            $domain = $description;

                            $ex = explode(".", $domain);
                            $sld = array_shift($ex);
                            $tld = implode(".", $ex);
                            $reg = DomainHandler::getRegistrarByTld($tld);
                            if ($reg && $reg->isActive()) {
                                $reg = $reg->getShort();

                                $dueToday = $user->addTax($user->getDomainPrice($tld, $dType));
                                $renew = $user->getDomainPrice($tld, "renew");
                                $trade = $user->getDomainPrice($tld, "trade");
                                $privacy = $user->getDomainPrice($tld, "privacy");

                                $type = $dType == "register" ? "domain_reg" : "domain_kk";
                                $info = [
                                    "owner" => [
                                        $user->get()['firstname'],
                                        $user->get()['lastname'],
                                        $user->get()['company'],
                                        $user->get()['street'] . " " . $user->get()['street_number'],
                                        $user->get()['country_alpha'],
                                        $user->get()['postcode'],
                                        $user->get()['city'],
                                        $user->get()['telephone'] ?: "+49.1234567890",
                                        $user->get()['fax'],
                                        $user->get()['mail'],
                                    ],
                                    "admin" => [
                                        $user->get()['firstname'],
                                        $user->get()['lastname'],
                                        $user->get()['company'],
                                        $user->get()['street'] . " " . $user->get()['street_number'],
                                        $user->get()['country_alpha'],
                                        $user->get()['postcode'],
                                        $user->get()['city'],
                                        $user->get()['telephone'] ?: "+49.1234567890",
                                        $user->get()['fax'],
                                        $user->get()['mail'],
                                    ],
                                    "tech" => unserialize($CFG['WHOIS_DATA']),
                                    "zone" => unserialize($CFG['WHOIS_DATA']),
                                    "ns" => [
                                        $CFG['DEFAULT_IP'],
                                        "",
                                    ],
                                    "privacy" => "0",
                                ];

                                if ($dType == "transfer") {
                                    $info['transfer'] = ["", true];
                                }

                                $info = serialize($info);

                                $db->query("INSERT INTO domains (`inclusive_id`, `user`, `domain`, `reg_info`, `recurring`, `status`, `registrar`, `trade`, `privacy_price`, `privacy`, `expiration`, `created`, `payment`) VALUES (0, " . $user->get()['ID'] . ", '" . $db->real_escape_string($domain) . "', '" . $db->real_escape_string($info) . "', $renew, '" . ($type == "domain_reg" ? "REG_WAITING" : "KK_WAITING") . "', '" . $db->real_escape_string($reg) . "', $trade, $privacy, 0, '" . date("Y-m-d", strtotime("+1 year")) . "', '" . date("Y-m-d") . "', $paymentRequired)");
                                $iid = $db->insert_id;
                                array_push($paymentQueries, "UPDATE domains SET payment = %payment% WHERE ID = $iid");
                            }
                        }
                    }

                    // Variant handling
                    if ($product['variant'] !== "") {
                        $variants = @unserialize($info->variants);
                        if (is_array($variants) && array_key_exists($product['variant'], $variants)) {
                            $variant = $variants[$product['variant']];
                            $info->billing = $variant["billing"];
                            $info->ct = $variant["ct1"] . " " . $variant["ct2"];
                            $info->mct = $variant["mct1"] . " " . $variant["mct2"];
                            $info->np = $variant["np1"] . " " . $variant["np2"];
                        }
                    }

                    // Create customer product
                    if ($info->billing != "" && $info->billing != "onetime") {
                        $billarr = array(
                            "monthly" => "1 month",
                            "quarterly" => "3 months",
                            "semiannually" => "6 months",
                            "annually" => "1 year",
                            "biennially" => "2 years",
                            "trinnially" => "3 years",
                        );
                        $bill = $bill2 = date("Y-m-d", strtotime("+" . $billarr[$info->billing]));
                    } else {
                        $bill = $bill2 = "0000-00-00";
                    }

                    $cd = '0000-00-00';
                    if ($info->autodelete) {
                        $cd = date("Y-m-d", strtotime("+" . $info->autodelete . " days"));
                    }

                    $firstInvoice = $product["raw_amount"];
                    $undiscountedFI = $product["oldprice"];

                    if ($product['prorata']) {
                        $bill = date("Y-m-d", strtotime("+1 day", strtotime($product['prorata_date'])));
                        $bill2 = date("Y-m-d", strtotime($product['prorata_date']));
                        $firstInvoice = $product['prorata_breakdown_raw'];
                        $undiscountedFI = $product['prorata_breakdown_opraw'];
                    }

                    $price = doubleval($cur->convertBack($product['raw_amount'], null, false, false));
                    $discount -= doubleval($cur->convertBack($product['raw_amount'], null, false, false));
                    $description = $db->real_escape_string($description);
                    $db->query("INSERT INTO client_products (`payment`, `date`, `user`, `product`, `active`, `type`, `description`, `billing`, `module`, `module_settings`, `last_billed`, `ct`, `mct`, `np`, `price`, `cf`, `cancellation_date`, `prepaid`) VALUES ($paymentRequired, " . time() . ", {$user->get()['ID']}, {$product['relid']}, -1, 'h', '$description', '{$info->billing}', '{$info->module}', '$module_settings', '" . $bill . "', '{$info->ct}', '{$info->mct}', '{$info->np}', $price, '" . $db->real_escape_string($cf) . "', '$cd', {$info->prepaid})");
                    $iid = $db->insert_id;
                    array_push($paymentQueries, "UPDATE client_products SET payment = %payment% WHERE ID = $iid");

                    $bill = $bill2;

                    if ($info->new_cgroup >= 0) {
                        $user->set(["cgroup" => $info->new_cgroup, "cgroup_before" => $user->get()['cgroup'], "cgroup_contract" => $iid]);
                    }

                    $invDesc = "<b>" . $product['name'] . "</b>";
                    if ($info->desc_on_invoice) {
                        $invDesc .= "<br />" . (@unserialize($info->description) ? unserialize($info->description)[$CFG['LANG']] : $info->description);
                    }

                    $invCf = [];
                    foreach ($product['additional']['customfields'] as $fieldId => $fieldVal) {
                        $cfSql = $db->query("SELECT name FROM products_cf WHERE ID = " . intval($fieldId));
                        if ($cfSql->num_rows) {
                            $invCf[$cfSql->fetch_object()->name] = $fieldVal;
                        }
                    }

                    if (count($invCf)) {
                        $invDesc .= "<br />";
                        foreach ($invCf as $fieldName => $fieldVal) {
                            $invDesc .= "<br />" . $fieldName . ": " . $fieldVal;
                        }
                    }

                    if ($bill != "0000-00-00") {
                        $invDesc .= "<br /><br />" . $dfo->format(time(), false, false) . " - " . $dfo->format($bill, false, false);
                    }

                    // Now we create the first invoice item
                    $item = new InvoiceItem;
                    $item->setDescription($invDesc);
                    $item->setAmount($undiscountedFI);
                    $item->setRelid($iid);
                    array_push($invoiceItems, $item);

                    // Voucher discount
                    if ($undiscountedFI != $firstInvoice) {
                        $vd = "";
                        if ($var['voucher']) {
                            $vd = " " . $var['voucher']['code'];
                        }

                        $itemAmount = $firstInvoice - $undiscountedFI;
                        $item = new InvoiceItem;
                        $item->setDescription($lang['CART']['VOUCHERDISCOUNT'] . $vd);
                        $item->setAmount($itemAmount);
                        $item->setRelid($iid);
                        array_push($invoiceItems, $item);
                    }

                    // Create invoice item for setup fee/discount
                    if ($product['setup'] != 0) {
                        $item = new InvoiceItem;
                        $item->setDescription("<b>" . $product['name'] . "</b><br />" . ($product['setup'] > 0 ? $lang['CART']['SETUP'] : $lang['CART']['DISCOUNT']));
                        $item->setAmount($product['setup']);
                        $item->setRelid($iid);
                        array_push($invoiceItems, $item);
                    }

                    // Add to external affiliate amount
                    $grossValue = $cur->convertBack($cur->convertBack($product['amount']) + $cur->convertBack($nfo->phpize($product['setup'])));
                    $netValue = calculateVat($grossValue, 2, $countryInfo->percent);
                    if ($netValue > 0 && !in_array($product['relid'], unserialize($CFG['EXT_AFFILIATE_EX']))) {
                        $extaffamount += $netValue;
                    }

                    // Remove stock
                    $db->query("UPDATE products SET available = available - 1 WHERE ID = " . $product['relid']);

                    // Affiliate
                    $percent = $info->affiliate > -1 ? $info->affiliate : ($CFG['AFFILIATE_COMMISSION']);
                    if ((is_double($percent) || is_numeric($percent)) && $percent > 0) {
                        $affiliate += $cur->convertBack($product['oldprice'] ?: $nfo->phpize($product['amount'])) * $percent / 100;
                    }

                    // Handle inclusive domains
                    if (is_array($product['additional']) && array_key_exists("domains", $product['additional']) && is_array($product['additional']['domains']) && count($product['additional']['domains']) > 0) {
                        foreach ($product['additional']['domains'] as $domain => $authcode) {
                            $domain = strtolower($domain);
                            array_push($orderDomArr, $domain);

                            $ex = explode(".", $domain);
                            $sld = array_shift($ex);
                            $tld = implode(".", $ex);
                            $reg = DomainHandler::getRegistrarByTld($tld);
                            if (!$reg || !$reg->isActive()) {
                                continue;
                            } else {
                                $reg = $reg->getShort();
                            }

                            $renew = $user->getDomainPrice($tld, "renew");
                            $trade = $user->getDomainPrice($tld, "trade");
                            $privacy = $user->getDomainPrice($tld, "privacy");

                            $type = empty($authcode) ? "domain_reg" : "domain_kk";
                            $info = [
                                "owner" => [
                                    $user->get()['firstname'],
                                    $user->get()['lastname'],
                                    $user->get()['company'],
                                    $user->get()['street'] . " " . $user->get()['street_number'],
                                    $user->get()['country_alpha'],
                                    $user->get()['postcode'],
                                    $user->get()['city'],
                                    $user->get()['telephone'] ?: "+49.1234567890",
                                    $user->get()['fax'],
                                    $user->get()['mail'],
                                ],
                                "admin" => [
                                    $user->get()['firstname'],
                                    $user->get()['lastname'],
                                    $user->get()['company'],
                                    $user->get()['street'] . " " . $user->get()['street_number'],
                                    $user->get()['country_alpha'],
                                    $user->get()['postcode'],
                                    $user->get()['city'],
                                    $user->get()['telephone'] ?: "+49.1234567890",
                                    $user->get()['fax'],
                                    $user->get()['mail'],
                                ],
                                "tech" => unserialize($CFG['WHOIS_DATA']),
                                "zone" => unserialize($CFG['WHOIS_DATA']),
                                "ns" => [
                                    $CFG['DEFAULT_IP'],
                                    "",
                                ],
                                "privacy" => "0",
                            ];

                            if (!empty($authcode)) {
                                $info['transfer'] = [$authcode, true];
                            }

                            $info = serialize($info);

                            $db->query("INSERT INTO domains (`payment`, `inclusive_id`, `user`, `domain`, `reg_info`, `recurring`, `status`, `registrar`, `trade`, `privacy_price`, `privacy`, `expiration`, `created`) VALUES ($paymentRequired, $iid, " . $user->get()['ID'] . ", '" . $db->real_escape_string($domain) . "', '" . $db->real_escape_string($info) . "', $renew, '" . ($type == "domain_reg" ? "REG_WAITING" : "KK_WAITING") . "', '" . $db->real_escape_string($reg) . "', $trade, $privacy, 0, '" . date("Y-m-d", strtotime("+1 year")) . "', '" . date("Y-m-d") . "')");
                            $iid2 = $db->insert_id;
                            array_push($paymentQueries, "UPDATE domains SET payment = %payment% WHERE ID = $iid2");
                        }
                    }

                    // Insert into buyed products array
                    foreach (unserialize($info->name) as $l => $n) {
                        $orderDoms = "";
                        if (count($orderDomArr)) {
                            $orderDoms = " (" . implode(", ", $orderDomArr) . ")";
                        }

                        if (isset($buyedProducts[$l])) {
                            $buyedProducts[$l] .= $n . $orderDoms . '
';
                        }
                    }
                } else if ($product['typ'] == "domain_reg" || $product['typ'] == "domain_in") {
                    $ignore = array_key_exists("ignore", $product) ? boolval($product["ignore"]) : false;
                    $info = unserialize($product['type']);
                    $info['domain'] = strtolower($info['domain']);

                    $ex = explode(".", $info['domain']);
                    $sld = array_shift($ex);
                    $tld = implode(".", $ex);

                    if (!$ignore) {
                        $reg = DomainHandler::getRegistrarByTld($tld);
                        if (!$reg || !$reg->isActive()) {
                            $reg = "";
                        } else {
                            $reg = $reg->getShort();
                        }

                        $renew = $user->getDomainPrice($tld, "renew");
                        $trade = $user->getDomainPrice($tld, "trade");
                        $privacy = $user->getDomainPrice($tld, "privacy");

                        $ps = 0;
                        if (isset($info['privacy']) && $info['privacy'] == "1") {
                            $ps = 1;
                            $renew += $privacy;
                        }

                        $addonId = 0;
                        // Add addon domains to contract
                        if (!empty($info['hosting_contract'])) {
                            $ex = explode("#", $info['hosting_contract']);
                            if (count($ex == 2) && is_numeric($contractId = $ex[0])) {
                                $contractSql = $db->query("SELECT * FROM client_products WHERE user = " . $user->get()['ID'] . " AND active = 1 AND ID = " . intval($contractId));
                                if ($contractSql->num_rows) {
                                    $contractInfo = $contractSql->fetch_object();
                                    $addonId = $contractInfo->ID;

                                    if (!empty($contractInfo->module) && array_key_exists($contractInfo->module, $provisioning->get()) && method_exists($mod = $provisioning->get()[$contractInfo->module], "AssignDomain")) {
                                        $mod->AssignDomain($contractInfo->ID, $info['domain']);
                                    }
                                }
                            }
                        }

                        $db->query("INSERT INTO domains (`payment`, `user`, `domain`, `reg_info`, `recurring`, `status`, `registrar`, `trade`, `privacy_price`, `privacy`, `expiration`, `created`, `addon_id`) VALUES ($paymentRequired, " . $user->get()['ID'] . ", '" . $db->real_escape_string($info['domain']) . "', '" . $db->real_escape_string($product['type']) . "', $renew, '" . ($product['typ'] == "domain_reg" ? "REG_WAITING" : "KK_WAITING") . "', '" . $db->real_escape_string($reg) . "', $trade, $privacy, $ps, '" . date("Y-m-d", strtotime("+1 year")) . "', '" . date("Y-m-d") . "', $addonId)");
                        $iid = $db->insert_id;
                        array_push($paymentQueries, "UPDATE domains SET payment = %payment% WHERE ID = $iid");
                    }

                    $period = 1;
                    $periodSql = $db->query("SELECT `period` FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
                    if ($periodSql->num_rows) {
                        $period = max(1, intval($periodSql->fetch_object()->period));
                    }

                    $from = $dfo->format(time(), false, false);
                    $to = $dfo->format(strtotime("+$period year, -1 day"), false, false);

                    // Now we create the invoice item
                    $item = new InvoiceItem;
                    $item->setDescription("<b>" . $info['domain'] . "</b><br />" . ($product['typ'] == "domain_reg" ? $lang['CART']['TYPE_REG'] : $lang['CART']['TYPE_IN']) . "<br /><br />$from - $to");
                    $item->setAmount($product['raw_amount']);
                    array_push($invoiceItems, $item);

                    // Email
                    foreach ($languages as $k => $v) {
                        $buyedProducts[$k] .= $info['domain'] . '
';
                    }
                } else if ($product['typ'] == "bundle") {
                    // Select details
                    $sql = $db->query("SELECT * FROM product_bundles WHERE ID = " . $product['relid']);
                    if ($sql->num_rows != 1) {
                        continue;
                    }

                    $info = $sql->fetch_object();

                    // Affiliate
                    $percent = $info->affiliate > -1 ? $info->affiliate : ($CFG['AFFILIATE_COMMISSION']);
                    if ((is_double($percent) || is_numeric($percent)) && $percent > 0) {
                        $affiliate += $cur->convertBack($product['oldprice'] ?: $nfo->phpize($product['amount'])) * $percent / 100;
                    }

                    // Insert the product licenses
                    $org = unserialize($info->products);
                    foreach ($org as $pid) {
                        $db->query("INSERT INTO client_products (`payment`, `date`, `user`, `product`, `active`, `type`, `version`) VALUES ($paymentRequired, " . time() . ", " . $user->get()['ID'] . ", " . intval($pid) . ", 1, 'e', '')");
                        $iid = $db->insert_id;
                        array_push($paymentQueries, "UPDATE client_products SET payment = %payment% WHERE ID = $iid");

                        $info123 = $db->query("SELECT new_cgroup FROM  WHERE ID = " . intval($pid))->fetch_object();
                        if ($info123->new_cgroup >= 0) {
                            $user->set(["cgroup" => $info123->new_cgroup, "cgroup_before" => $user->get()['cgroup'], "cgroup_contract" => $db->insert_id]);
                        }
                    }

                    // Now we create the invoice item
                    $item = new InvoiceItem;
                    $item->setDescription($lang['CART']['TYPE_BUNDLE'] . ": " . unserialize($info->name)[$CFG['LANG']]);
                    $item->setAmount($product['raw_amount']);
                    array_push($invoiceItems, $item);

                    // Change sell number
                    $db->query("UPDATE product_bundles SET sells = sells + 1 WHERE ID = " . $product['relid']);

                    // Insert into buyed products array
                    foreach ($languages as $k => $v) {
                        $buyedProducts[$k] = unserialize($info->name)[$CFG['LANG']] . '
';
                    }

                }
            }
        }

        // As soon as the order is processed, the invoice is created and the sum is taken from customer credit
        if ($sum > 0) {
            $inv = new Invoice;
            $inv->setDate(date("Y-m-d"));
            $inv->setClient($user->get()['ID']);
            $inv->setDueDate();
            if (!empty($voucher)) {
                $inv->setVoucher($voucher);
            }

            foreach ($invoiceItems as $item) {
                $inv->addItem($item);
            }

            $inv->save();

            $user->tryToClear();

            $inv->send();

            if ($paymentRequired) {
                foreach ($paymentQueries as $q) {
                    $db->query(str_replace("%payment%", $inv->getId(), $q));
                }
            }
            $var['payment_required'] = $paymentRequired;

            $var['invoice'] = $inv->getId();

            $paymentReference = $db->real_escape_string($paymentReference);
            $no = $db->real_escape_string($inv->getInvoiceNo());
            $db->query("UPDATE client_transactions SET payment_reference = '$no' WHERE payment_reference = '$paymentReference'");
        } else {
            $var['invoice'] = 0;
            $paymentRequired = 0;
            $inv = null;

            $paymentReference = $db->real_escape_string($paymentReference);
            $no = $db->real_escape_string(str_replace("%d", $dfo->format(time(), false, false, false), $lang['CART']['PAYMENT_REFERENCE']));
            $db->query("UPDATE client_transactions SET payment_reference = '$no' WHERE payment_reference = '$paymentReference'");
        }

        // We get now a few information about our current cart and the user for template engine
        $var['user'] = $user->get();
        $var['cart'] = $cart->get();
        $var['cart_count'] = 0;
        $var['buyed'] = 1;

        // We send a mail for the completed order
        $mtObj = new MailTemplate("Bestellbestätigung");

        $titlex = $mtObj->getTitle($CFG['LANG']);
        $mail = $mtObj->getMail($CFG['LANG'], $user->get()['name']);

        $maq->enqueue([
            "amount" => $cur->infix($nfo->format($cur->convertAmount(null, $sum, null))),
            "order" => $buyedProducts[$user->getLanguage()],
        ], $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

        // The order is logged into user log
        $user->log("Einkauf über " . $cur->infix($nfo->format($sum), $cur->getBaseCurrency()) . " getätigt");

        // Hook
        $addons->runHook("OrderPlaced", [
            "user" => $user,
            "invoice" => $inv,
        ]);

        // If a voucher was used, we set it to the database
        if ($voucherInfo !== false) {
            $db->query("UPDATE vouchers SET uses = uses + 1 WHERE ID = " . $voucherInfo->ID . " LIMIT 1");
        }

        // Send admin notification/s
        foreach ($buyedProducts as &$v) {
            $v = trim($v);
        }

        if (($ntf = AdminNotification::getInstance("Neue Bestellung")) !== false) {
            $ntf->set("items", $buyedProducts);
            $ntf->set("customer", $user->get()['name']);
            $ntf->set("cid", $user->get()['ID']);
            $ntf->set("clink", $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $user->get()['ID']);

            // Get NFO by language
            $amount = array();
            foreach (unserialize($raw_cfg['NUMBER_FORMAT']) as $l => $f) {
                $amount[$l] = $cur->infix($nfo->format($sum, 2, 0, $f), $cur->getBaseCurrency());
            }

            $ntf->set("amount", $amount);

            $ntf->send();
        }

        // Book affiliate
        if ($affiliate > 0 && $user->get()['affiliate'] > 0 && $CFG['AFFILIATE_ACTIVE']) {
            $db->query("UPDATE clients SET affiliate_credit = affiliate_credit + $affiliate WHERE ID = {$user->get()['affiliate']} LIMIT 1");
            $db->query("INSERT INTO client_affiliate (time, user, affiliate, amount) VALUES (" . time() . ", {$user->get()['ID']}, {$user->get()['affiliate']}, $affiliate)");
        }

        // External affiliate code
        if ($extaffamount > 0 && ($user->get()['affiliate'] == 0 || $affiliate == 0 || !$CFG['AFFILIATE_ACTIVE'])) {
            $var['extaffcode'] = str_replace(array("%net%", "%custno%", "%invoiceno%"), array($extaffamount, $user->get()['ID'], $var['invoice']), $CFG['EXT_AFFILIATE']);
        }

        // Piwik eCommerce
        $cart->piwik($cartitems_bck, isset($inv) ? $inv->getInvoiceNo() : "FO-" . rand(100000, 999999));
    } catch (Exception $ex) {
        // If anything goes wrong, we display a generic error message

        $var['buyed'] = 2;
    }
}

foreach ($var['cartitems'] as &$item) {
    $item['extension'] = "";
    foreach ($addons->runHook("CartDisplayRowExtension", $item) as $l) {
        if (!empty($l)) {
            $item['extension'] = $l;
        }
    }

}

$var['moduleFooterMessage'] = "";
foreach ($addons->runHook("CartDisplayFooterMessage") as $l) {
    if (!empty($l)) {
        $var['moduleFooterMessage'] .= $l;
    }
}
