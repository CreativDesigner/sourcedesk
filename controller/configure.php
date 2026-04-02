<?php
// Global some variables for security reasons
global $db, $var, $CFG, $lang, $pars, $cart, $user, $cur, $nfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

// Check if product exists
$sql = $db->query("SELECT * FROM products WHERE ID = " . intval($pars[0]));
if ($sql->num_rows != 1) {
    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
} else {
    $title = $lang['CONFIGURE']['TITLE'];
    $tpl = "configure";

    if (empty($_SESSION['configure'])) {
        $_SESSION['configure'] = ["domains" => [], "customfields" => [], "variant" => ""];
    }

    $pi = $sql->fetch_object();

    if (($pi->only_verified == 1 && !$user->get()['verified']) || $pi->available == 0) {
        $tpl = "error";
        $title = $lang['ERROR']['TITLE'];
        return;
    }

    if (trim($pi->customer_groups) != "") {
        if (!$var['logged_in']) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            return;
        } else if ($var['logged_in'] && !in_array($user->get()['cgroup'], explode(",", $pi->customer_groups))) {
            $tpl = "error";
            $title = $lang['ERROR']['TITLE'];
            return;
        }
    }

    if ($pi->incldomains > 0) {
        $var['tlds'] = [];

        $tlds = unserialize($pi->incltlds);
        foreach ($tlds as $id) {
            $sql = $db->query("SELECT tld FROM domain_pricing WHERE ID = " . intval($id));
            if ($sql->num_rows != 1) {
                continue;
            }

            $di = $sql->fetch_object();
            array_push($var['tlds'], "." . ltrim($di->tld, "."));
        }

        if (isset($_POST['remove'])) {
            if (array_key_exists($_POST['remove'], $_SESSION['configure']['domains'])) {
                unset($_SESSION['configure']['domains'][$_POST['remove']]);
                die("ok");
            }
            die("nok");
        }

        if (isset($_POST['sld'])) {
            if (array_key_exists(trim($_POST['sld'], ".") . "." . trim($_POST['tld'], "."), $_SESSION['configure']['domains'])) {
                die($lang['CONFIGURE']['EXISTS']);
            }

            if ($pi->incldomains - count($_SESSION['configure']['domains']) < 1) {
                die($lang['CONFIGURE']['END']);
            }

            if (empty($_POST['sld'])) {
                die($lang['CONFIGURE']['SLD']);
            }

            if (strpos($_POST['sld'], ".") !== false) {
                die($lang['CONFIGURE']['NA']);
            }

            if (empty($_POST['tld'])) {
                die($lang['CONFIGURE']['TLD']);
            }

            if (!in_array($_POST['tld'], $var['tlds'])) {
                die($lang['CONFIGURE']['NA']);
            }

            $reg = DomainHandler::getRegistrarByTld(trim($_POST['tld'], "."));
            $s = DomainHandler::availibilityStatus(trim($_POST['sld'], ".") . "." . trim($_POST['tld'], "."), $reg);

            if ($s === false) {
                if (!empty($_POST['authcode']) && strlen($_POST['authcode']) >= 5) {
                    $_SESSION['configure']['domains'][trim($_POST['sld'], ".") . "." . trim($_POST['tld'], ".")] = $_POST['authcode'];
                    die("ok");
                }
                die("auth");
            }

            if ($s === true) {
                $_SESSION['configure']['domains'][trim($_POST['sld'], ".") . "." . trim($_POST['tld'], ".")] = "";
                die("ok");
            }

            die($lang['CONFIGURE']['INVALID']);
        }
    }

    $var['cf'] = [];
    $sql = $db->query("SELECT * FROM products_cf WHERE product = " . intval($pars[0]));
    while ($row = $sql->fetch_assoc()) {
        $o = unserialize($row['options']);
        unset($row['options']);
        foreach ($o as $k => $v) {
            $row[$k] = $v;
        }

        switch ($row['type']) {
            case "number":
                $row['defcost'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $row['default'] * $row['amount'])));

                if (isset($_POST['field_id']) && $_POST['field_id'] == $row['ID']) {
                    $onetime = "";
                    if (array_key_exists("onetime", $row) && $row['onetime']) {
                        $onetime = " " . $lang['CONFIGURE']['ONETIME_ONLY'];
                    }

                    die($cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $_POST['field_val'] * $row['amount']))) . $onetime);
                }
                break;

            case "select":
            case "radio":
                $row['defcost'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), array_shift(explode("|", $row['costs'])))));

                if (isset($_POST['field_id']) && $_POST['field_id'] == $row['ID']) {
                    $onetime = "";
                    if (array_key_exists("onetime", $row) && $row['onetime']) {
                        $onetime = " " . $lang['CONFIGURE']['ONETIME_ONLY'];
                    }

                    $ex = explode("|", $row['values']);
                    $index = array_search($_POST['field_val'], $ex) ?: 0;

                    die($cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), explode("|", $row['costs'])[$index]))) . $onetime);
                }
                break;

            case "check":
                $row['defcost'] = $cur->infix($nfo->format(0));

                if (isset($_POST['field_id']) && $_POST['field_id'] == $row['ID']) {
                    $onetime = "";
                    if (array_key_exists("onetime", $row) && $row['onetime']) {
                        $onetime = " " . $lang['CONFIGURE']['ONETIME_ONLY'];
                    }

                    die($cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $_POST['field_val'] ? $row['costs'] : 0))) . $onetime);
                }
                break;
        }

        if (array_key_exists("onetime", $row) && $row['onetime']) {
            $row['defcost'] .= " " . $lang['CONFIGURE']['ONETIME_ONLY'];
        }

        $var['cf'][$row['ID']] = $row;
    }

    if (isset($_POST['field_id'])) {
        die("-");
    }

    $variants = @unserialize($pi->variants);

    if (count($var['cf']) == 0 && $pi->incldomains == 0 && !$pi->domain_choose && (!is_array($variants) || !count($variants))) {
        header('Location: ' . $CFG['PAGEURL'] . 'cart?add_product=' . $pars[0]);
        exit;
    }

    $hasSetup = false;

    function format_variant($d)
    {
        global $nfo, $cur, $lang, $currencies, $hasSetup;

        $ret = [
            "price" => "",
            "setup" => "",
            "ct" => "",
            "mct" => "",
            "np" => "",
        ];

        $price = $d["price"];
        $isBase = false;
        $code = "";

        foreach ($currencies as $k => $v) {
            if ($v["ID"] == $d["currency"]) {
                $code = $k;
                $isBase = boolval($d["base"]);
                break;
            }
        }

        if (!$isBase && $code) {
            $price = $cur->convertAmount($code, $price, $cur->getBaseCurrency());
        }

        $ret["price"] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $price))) . " " . $lang['CART'][strtoupper(in_array($d["billing"], ["onetime", "monthly", "quarterly", "semiannually", "annually", "biennially", "trinnially", "hourly", "minutely"]) ? $d["billing"] : "onetime")];

        if ($d["setup"] > 0 || $hasSetup) {
            $hasSetup = true;
            $ret["setup"] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $d["setup"]))) . " " . $lang['CART']['SETUP'];
        }

        $ex = explode(" ", $d["ct"], 2);
        if ($ex[0]) {
            $ret["ct"] = format_ctime($d["ct"]) . " " . $lang['CONFIGURE']['CT'];
        }

        $ex = explode(" ", $d["mct"], 2);
        if ($ex[0]) {
            $ret["mct"] = format_ctime($d["mct"]) . " " . $lang['CONFIGURE']['MCT'];
        }

        $ex = explode(" ", $d["np"], 2);
        if ($ex[0]) {
            $ret["np"] = format_ctime($d["np"]) . " " . $lang['CONFIGURE']['NP'];
        }

        return $ret;
    }

    $pi->price = Product::getClientPrice($pi->price, $pi->tax);
    $pi->setup = Product::getClientPrice($pi->setup, $pi->tax);

    $var['variants'] = [];
    if (is_array($variants)) {
        $var['variants'][] = format_variant((array) $pi);

        foreach ($variants as $k => $v) {
            $var['variants']["v" . $k] = format_variant([
                "price" => Product::getClientPrice($v['price'], $pi->tax),
                "currency" => $v['currency'],
                "billing" => $v['billing'],
                "setup" => Product::getClientPrice($v['setup'], $pi->tax),
                "ct" => $v['ct1'] . " " . $v['ct2'],
                "mct" => $v['mct1'] . " " . $v['mct2'],
                "np" => $v['np1'] . " " . $v['np2'],
            ]);
        }
    }

    if ($hasSetup) {
        $var['variants'] = [];
        if (is_array($variants)) {
            $var['variants'][] = format_variant((array) $pi);

            foreach ($variants as $k => $v) {
                $var['variants']["v" . $k] = format_variant([
                    "price" => Product::getClientPrice($v['price'], $pi->tax),
                    "currency" => $v['currency'],
                    "billing" => $v['billing'],
                    "setup" => Product::getClientPrice($v['setup'], $pi->tax),
                    "ct" => $v['ct1'] . " " . $v['ct2'],
                    "mct" => $v['mct1'] . " " . $v['mct2'],
                    "np" => $v['np1'] . " " . $v['np2'],
                ]);
            }
        }
    }

    foreach ($_REQUEST as $k => $v) {
        if (substr($k, 0, 4) != "conf") {
            continue;
        }

        $k = substr($k, 4);
        if (!is_numeric($k)) {
            continue;
        }

        if (!array_key_exists($k, $var['cf'])) {
            continue;
        }

        if (!is_array($_POST['custom_fields'])) {
            $_POST['custom_fields'] = [];
        }

        $_POST['custom_fields'][$k] = $v;
        $_POST['cart'] = "redirect";
    }

    if (isset($_POST['cart'])) {
        $cf = isset($_POST['custom_fields']) && is_array($_POST['custom_fields']) ? $_POST['custom_fields'] : [];

        $_SESSION['configure']['customfields'] = [];

        foreach ($var['cf'] as $f) {
            $p = $cf[$f['ID']] ?? $f['default'];

            if ($f['type'] == "number") {
                $v = intval($p);
                if (is_numeric($f['maximum']) && $f['maximum'] >= 0) {
                    $v = min($v, $f['maximum']);
                }

                if (is_numeric($f['minimum'])) {
                    $v = max($v, $f['minimum']);
                }
            } else if ($f['type'] == "select") {
                if (in_array($p, explode("|", $f['values']))) {
                    $v = $p;
                } else {
                    $v = explode("|", $f['values'])[0];
                }
            } else if ($f['type'] == "radio") {
                if (in_array($p, explode("|", $f['values']))) {
                    $v = $p;
                } else {
                    $v = explode("|", $f['values'])[0];
                }
            } else if ($f['type'] == "check") {
                $v = boolval($p);
            } else if ($f['type'] == "text") {
                $v = strval($p);
            }

            $_SESSION['configure']['customfields'][$f['ID']] = $v;
        }

        if (!$pi->incldomains && $pi->domain_choose) {
            if (!array_key_exists("domain_choose", $_POST)) {
                die("fail");
            }

            $dc = $_POST["domain_choose"];
            $type = array_key_exists("type", $dc) ? $dc["type"] : "";
            $domain = array_key_exists("domain", $dc) ? $dc["domain"] : "";

            if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
                die("fail");
            }

            $ex = explode(".", $domain);
            $sld = array_shift($ex);
            $tld = implode(".", $ex);

            if (empty($sld) || empty($tld)) {
                die("fail");
            }

            switch ($type) {
                case "own":
                    break;

                case "register":
                case "transfer":
                    $reg = DomainHandler::getRegistrarByTld($tld);
                    if (!$reg || !$reg->isActive()) {
                        die("fail");
                    }

                    if ($db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string($sld . "." . $tld) . "' AND status IN ('REG_WAITING', 'KK_WAITING')")->num_rows > 0) {
                        die("fail");
                    }

                    $status = DomainHandler::availibilityStatus(strtolower($domain), $reg);
                    $expected = $dc["type"] == "register" ? true : false;

                    if ($status !== $expected) {
                        return false;
                    }
                    break;

                default:
                    die("fail");
                    break;
            }

            $_SESSION['configure']['domain'] = [$type, $domain];
        }

        $_SESSION['configure']['variant'] = $_POST['variant'] ?? "";

        if ($cart->add($pars[0], "product", "h", serialize($_SESSION['configure']))) {
            $cart->__destruct();
            $cart = $var['logged_in'] ? new Cart($user->get()['ID']) : new VisitorCart;
            $var['cart'] = $cart->get();
            $var['cart_count'] = $cart->count();
            $cart->piwik();

            $_SESSION['configure']['domains'] = [];
            $_SESSION['configure']['domain'] = "";
            $_SESSION['configure']['customfields'] = [];

            if ($_POST['cart'] == "redirect") {
                header('Location: ' . $CFG['PAGEURL'] . 'cart');
                exit;
            }

            die("ok");
        }

        die("fail");
    }

    @$var['product_name'] = unserialize($pi->name)[$CFG['LANG']] ?: $pi->name;
    $var['incldomains'] = $pi->incldomains;
    $var['domain_choose'] = $pi->domain_choose && !$pi->incldomains;

    $var['alltlds'] = [];
    $sql = $db->query("SELECT tld FROM domain_pricing ORDER BY top DESC, tld ASC");
    while ($row = $sql->fetch_object()) {
        array_push($var['alltlds'], $row->tld);
    }
}
