<?php
global $var, $db, $CFG, $cur, $nfo, $user, $pars, $cart, $lang, $raw_cfg, $addons, $provisioning;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['DOMAINS']['TITLE'];
$tpl = "domains";

if (array_key_exists("domaingate", $addons->get())) {
    $dga = $addons->get()["domaingate"];
}

if (isset($pars[0]) && $pars[0] == "pricing.json") {
    header("Content-Type: application/json");
    echo DomainPricing::getJson();
    exit;
}

if (isset($pars[0]) && $pars[0] == "pricing.xml") {
    header("Content-Type: application/xml");
    echo DomainPricing::getXml();
    exit;
}

if (isset($pars[0]) && $pars[0] == "pricing.csv") {
    header("Content-Type: application/csv");
    echo DomainPricing::getCsv();
    exit;
}

if (isset($pars[0]) && $pars[0] == "privacy" && !empty($pars[1])) {
    $r = DomainHandler::getRegistrarByTld($pars[1]);
    if (false === $r) {
        $ex = explode(".", $pars[1]);
        $sld = array_shift($ex);
        $tld = implode(".", $ex);
        $r = DomainHandler::getRegistrarByTld($tld);
    }

    if (method_exists($r, "getPrivacyRules")) {
        header('Location: ' . $raw_cfg['PAGEURL'] . 'files/system/' . $r->getPrivacyRules());
        exit;
    } else {
        $tpl = "error";
        $title = $lang['ERROR']['TITLE'];
    }
}

if (isset($pars[0]) && $pars[0] == "cart") {
    $errors = array();

    try {
        $domain = strtolower($_POST['domain']);
        $ex = explode(".", $domain);
        $sld = array_shift($ex);
        $tld = implode(".", $ex);

        if (empty($sld) || empty($tld)) {
            throw new Exception($lang['DOMAINS']['ERR1']);
        }

        $reg = DomainHandler::getRegistrarByTld($tld);
        if (!$reg || !$reg->isActive()) {
            throw new Exception($lang['DOMAINS']['ERR2']);
        }

        if ($db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string($sld . "." . $tld) . "' AND status IN ('REG_WAITING', 'KK_WAITING')")->num_rows > 0) {
            throw new Exception($lang['DOMAINS']['ERR3']);
        }

        $status = DomainHandler::availibilityStatus(strtolower($_POST['domain']), $reg);

        if ($status === true) {
            $status = true;
        } else if ($status === false) {
            $status = false;
        } else {
            throw new Exception($lang['DOMAINS']['ERR4']);
        }

        // Owner-C
        if (empty($_POST['owner']) || !is_array($_POST['owner']) || count($_POST['owner']) != 10) {
            throw new Exception(str_replace("%h", $lang['DOMAINS']['OWNERC'], $lang['DOMAINS']['ERR5']));
        }

        $countries = array();
        $sql = $db->query("SELECT alpha2, name FROM client_countries WHERE active = 1 ORDER BY alpha2 ASC");
        while ($row = $sql->fetch_object()) {
            $countries[$row->alpha2] = $row->name;
        }

        if (empty($_POST['owner'][0])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR6']);
        }

        if (empty($_POST['owner'][1])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR7']);
        }

        if (empty($_POST['owner'][3])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR8']);
        }

        if (empty($_POST['owner'][4]) || !array_key_exists($_POST['owner'][4], $countries)) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR9']);
        }

        if (empty($_POST['owner'][5])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR10']);
        }

        if (empty($_POST['owner'][6])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR11']);
        }

        if (empty($_POST['owner'][7])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR12']);
        }

        $_POST['owner'][7] = str_replace(".", "", $_POST['owner'][7]);
        if (substr($_POST['owner'][7], 0, 2) == "00") {
            $_POST['owner'][7] = "+" . ltrim($_POST['owner'][7], "0");
            $_POST['owner'][7] = substr($_POST['owner'][7], 0, 3) . "." . substr($_POST['owner'][7], 3);
        } else if (substr($_POST['owner'][7], 0, 1) == "0") {
            $_POST['owner'][7] = "+49." . ltrim($_POST['owner'][7], "0");
        } else if (substr($_POST['owner'][7], 0, 1) == "+") {
            $_POST['owner'][7] = substr($_POST['owner'][7], 0, 3) . "." . substr($_POST['owner'][7], 3);
        } else {
            $_POST['owner'][7] = "+49.0" . $_POST['owner'][7];
        }

        if (!empty($_POST['owner'][8])) {
            $_POST['owner'][8] = str_replace(".", "", $_POST['owner'][8]);
            if (substr($_POST['owner'][8], 0, 2) == "00") {
                $_POST['owner'][8] = "+" . ltrim($_POST['owner'][8], "0");
                $_POST['owner'][8] = substr($_POST['owner'][8], 0, 3) . "." . substr($_POST['owner'][8], 3);
            } else if (substr($_POST['owner'][8], 0, 1) == "0") {
                $_POST['owner'][8] = "+49." . ltrim($_POST['owner'][8], "0");
            } else if (substr($_POST['owner'][8], 0, 1) == "+") {
                $_POST['owner'][8] = substr($_POST['owner'][8], 0, 3) . "." . substr($_POST['owner'][8], 3);
            } else {
                $_POST['owner'][8] = "+49.0" . $_POST['owner'][8];
            }
        }

        if (empty($_POST['owner'][9])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR13']);
        }

        if (!filter_var($_POST['owner'][9], FILTER_VALIDATE_EMAIL)) {
            array_push($errors, "<b>" . $lang['DOMAINS']['OWNERC'] . ":</b> " . $lang['DOMAINS']['ERR14']);
        }

        // Admin-C
        if (empty($_POST['owner']) || !is_array($_POST['owner']) || count($_POST['owner']) != 10) {
            throw new Exception(str_replace("%h", $lang['DOMAINS']['ADMINC'], $lang['DOMAINS']['ERR5']));
        }

        if (empty($_POST['admin'][0])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR6']);
        }

        if (empty($_POST['admin'][1])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR7']);
        }

        if (empty($_POST['admin'][3])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR8']);
        }

        if (empty($_POST['admin'][4]) || !array_key_exists($_POST['admin'][4], $countries)) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR9']);
        }

        if (empty($_POST['admin'][5])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR10']);
        }

        if (empty($_POST['admin'][6])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR11']);
        }

        if (empty($_POST['admin'][7])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR12']);
        }

        $_POST['admin'][7] = str_replace(".", "", $_POST['admin'][7]);
        if (substr($_POST['admin'][7], 0, 2) == "00") {
            $_POST['admin'][7] = "+" . ltrim($_POST['admin'][7], "0");
            $_POST['admin'][7] = substr($_POST['admin'][7], 0, 3) . "." . substr($_POST['admin'][7], 3);
        } else if (substr($_POST['admin'][7], 0, 1) == "0") {
            $_POST['admin'][7] = "+49." . ltrim($_POST['admin'][7], "0");
        } else if (substr($_POST['admin'][7], 0, 1) == "+") {
            $_POST['admin'][7] = substr($_POST['admin'][7], 0, 3) . "." . substr($_POST['admin'][7], 3);
        } else {
            $_POST['admin'][7] = "+49.0" . $_POST['admin'][7];
        }

        if (!empty($_POST['admin'][8])) {
            $_POST['admin'][8] = str_replace(".", "", $_POST['admin'][8]);
            if (substr($_POST['admin'][8], 0, 2) == "00") {
                $_POST['admin'][8] = "+" . ltrim($_POST['admin'][8], "0");
                $_POST['admin'][8] = substr($_POST['admin'][8], 0, 3) . "." . substr($_POST['admin'][8], 3);
            } else if (substr($_POST['admin'][8], 0, 1) == "0") {
                $_POST['admin'][8] = "+49." . ltrim($_POST['admin'][8], "0");
            } else if (substr($_POST['admin'][8], 0, 1) == "+") {
                $_POST['admin'][8] = substr($_POST['admin'][8], 0, 3) . "." . substr($_POST['admin'][8], 3);
            } else {
                $_POST['admin'][8] = "+49.0" . $_POST['admin'][8];
            }
        }

        if (empty($_POST['admin'][9])) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR13']);
        }

        if (!filter_var($_POST['admin'][9], FILTER_VALIDATE_EMAIL)) {
            array_push($errors, "<b>" . $lang['DOMAINS']['ADMINC'] . ":</b> " . $lang['DOMAINS']['ERR14']);
        }

        // Tech-C
        if ($var['logged_in'] && $user->get()['domain_contacts'] && $_POST['tech'] != "isp") {
            if (empty($_POST['tech']) || !is_array($_POST['tech']) || count($_POST['tech']) != 10) {
                throw new Exception(str_replace("%h", $lang['DOMAINS']['TECHC'], $lang['DOMAINS']['ERR5']));
            }

            if (empty($_POST['tech'][0])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR6']);
            }

            if (empty($_POST['tech'][1])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR7']);
            }

            if (empty($_POST['tech'][3])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR8']);
            }

            if (empty($_POST['tech'][4]) || !array_key_exists($_POST['tech'][4], $countries)) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR9']);
            }

            if (empty($_POST['tech'][5])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR10']);
            }

            if (empty($_POST['tech'][6])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR11']);
            }

            if (empty($_POST['tech'][7])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR12']);
            }

            if (empty($_POST['tech'][8])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR15']);
            }

            $_POST['tech'][7] = str_replace(".", "", $_POST['tech'][7]);
            if (substr($_POST['tech'][7], 0, 2) == "00") {
                $_POST['tech'][7] = "+" . ltrim($_POST['tech'][7], "0");
                $_POST['tech'][7] = substr($_POST['tech'][7], 0, 3) . "." . substr($_POST['tech'][7], 3);
            } else if (substr($_POST['tech'][7], 0, 1) == "0") {
                $_POST['tech'][7] = "+49." . ltrim($_POST['tech'][7], "0");
            } else if (substr($_POST['tech'][7], 0, 1) == "+") {
                $_POST['tech'][7] = substr($_POST['tech'][7], 0, 3) . "." . substr($_POST['tech'][7], 3);
            } else {
                $_POST['tech'][7] = "+49.0" . $_POST['tech'][7];
            }

            if (!empty($_POST['tech'][8])) {
                $_POST['tech'][8] = str_replace(".", "", $_POST['tech'][8]);
                if (substr($_POST['tech'][8], 0, 2) == "00") {
                    $_POST['tech'][8] = "+" . ltrim($_POST['tech'][8], "0");
                    $_POST['tech'][8] = substr($_POST['tech'][8], 0, 3) . "." . substr($_POST['tech'][8], 3);
                } else if (substr($_POST['tech'][8], 0, 1) == "0") {
                    $_POST['tech'][8] = "+49." . ltrim($_POST['tech'][8], "0");
                } else if (substr($_POST['tech'][8], 0, 1) == "+") {
                    $_POST['tech'][8] = substr($_POST['tech'][8], 0, 3) . "." . substr($_POST['tech'][8], 3);
                } else {
                    $_POST['tech'][8] = "+49.0" . $_POST['tech'][8];
                }
            }

            if (empty($_POST['tech'][9])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR13']);
            }

            if (!filter_var($_POST['tech'][9], FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "<b>" . $lang['DOMAINS']['TECHC'] . ":</b> " . $lang['DOMAINS']['ERR14']);
            }

        } else {
            $_POST['tech'] = unserialize($CFG['WHOIS_DATA']);
        }

        // Zone-C
        if ($var['logged_in'] && $user->get()['domain_contacts'] && $_POST['zone'] != "isp") {
            if (empty($_POST['zone']) || !is_array($_POST['zone']) || count($_POST['zone']) != 10) {
                throw new Exception(str_replace("%h", $lang['DOMAINS']['ZONEC'], $lang['DOMAINS']['ERR5']));
            }

            if (empty($_POST['zone'][0])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR6']);
            }

            if (empty($_POST['zone'][1])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR7']);
            }

            if (empty($_POST['zone'][3])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR8']);
            }

            if (empty($_POST['zone'][4]) || !array_key_exists($_POST['zone'][4], $countries)) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR9']);
            }

            if (empty($_POST['zone'][5])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR10']);
            }

            if (empty($_POST['zone'][6])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR11']);
            }

            if (empty($_POST['zone'][7])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR12']);
            }

            if (empty($_POST['zone'][8])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR15']);
            }

            $_POST['zone'][7] = str_replace(".", "", $_POST['zone'][7]);
            if (substr($_POST['zone'][7], 0, 2) == "00") {
                $_POST['zone'][7] = "+" . ltrim($_POST['zone'][7], "0");
                $_POST['zone'][7] = substr($_POST['zone'][7], 0, 3) . "." . substr($_POST['zone'][7], 3);
            } else if (substr($_POST['zone'][7], 0, 1) == "0") {
                $_POST['zone'][7] = "+49." . ltrim($_POST['zone'][7], "0");
            } else if (substr($_POST['zone'][7], 0, 1) == "+") {
                $_POST['zone'][7] = substr($_POST['zone'][7], 0, 3) . "." . substr($_POST['zone'][7], 3);
            } else {
                $_POST['zone'][7] = "+49.0" . $_POST['zone'][7];
            }

            if (!empty($_POST['zone'][8])) {
                $_POST['zone'][8] = str_replace(".", "", $_POST['zone'][8]);
                if (substr($_POST['zone'][8], 0, 2) == "00") {
                    $_POST['zone'][8] = "+" . ltrim($_POST['zone'][8], "0");
                    $_POST['zone'][8] = substr($_POST['zone'][8], 0, 3) . "." . substr($_POST['zone'][8], 3);
                } else if (substr($_POST['zone'][8], 0, 1) == "0") {
                    $_POST['zone'][8] = "+49." . ltrim($_POST['zone'][8], "0");
                } else if (substr($_POST['zone'][8], 0, 1) == "+") {
                    $_POST['zone'][8] = substr($_POST['zone'][8], 0, 3) . "." . substr($_POST['zone'][8], 3);
                } else {
                    $_POST['zone'][8] = "+49.0" . $_POST['zone'][8];
                }
            }

            if (empty($_POST['zone'][9])) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR13']);
            }

            if (!filter_var($_POST['zone'][9], FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "<b>" . $lang['DOMAINS']['ZONEC'] . ":</b> " . $lang['DOMAINS']['ERR14']);
            }

        } else {
            $_POST['zone'] = unserialize($CFG['WHOIS_DATA']);
        }

        // Nameserver
        if (isset($_POST['ns']) && is_array($_POST['ns']) && count($_POST['ns']) == 2) {
            if (empty($_POST['ns'][0])) {
                $_POST['ns'][0] = $CFG['DEFAULT_IP'];
            }

            if (!filter_var($_POST['ns'][0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                array_push($errors, $lang['DOMAINS']['ERR16']);
            }

            if (!empty($_POST['ns'][1]) && !filter_var($_POST['ns'][1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                array_push($errors, $lang['DOMAINS']['ERR17']);
            }

        } else if (isset($_POST['ns']) && is_array($_POST['ns']) && count($_POST['ns']) == 5) {
            foreach ($_POST['ns'] as $i => $n) {
                if (empty($n)) {
                    if ($i < 2) {
                        array_push($errors, "<b>" . $lang['DOMAINS']['ERR18'] . " " . ($i + 1) . ":</b> " . $lang['DOMAINS']['ERR19']);
                    }

                    continue;
                }

                $ip = gethostbyname($n);
                if (!$ip || $ip == $n) {
                    array_push($errors, "<b>" . $lang['DOMAINS']['ERR18'] . " " . ($i + 1) . ":</b> " . $lang['DOMAINS']['ERR20']);
                }

            }
        } else {
            throw new Exception($lang['DOMAINS']['ERR21']);
        }

        // Transfer
        if ($status === false) {
            if (empty($_POST['transfer'][0])) {
                array_push($errors, $lang['DOMAINS']['ERR22']);
            }

            if (!isset($_POST['transfer'][1]) || $_POST['transfer'][1] == "false") {
                array_push($errors, $lang['DOMAINS']['ERR23']);
            }

        }

        // Privacy
        if (!isset($_POST['privacy']) || $_POST['privacy'] == "false") {
            $_POST['privacy'] = "0";
        } else {
            $_POST['privacy'] = "1";
        }
    } catch (Exception $ex) {
        $errors = array($ex->getMessage());
    }

    if (count($errors) == 0) {
        $cart->add(0, $status ? "domain_reg" : "domain_in", serialize($_POST));
        die("ok");
    } else {
        $str = "";
        foreach ($errors as $e) {
            $str .= "<li>$e</li>";
        }

        die($str);
    }
}

$var['step'] = "overview";
$var['net'] = false;
$dp = 2;
if (isset($pars[0]) && $pars[0] == "pricing") {
    $var['step'] = "pricing";
    if (isset($pars[1]) && $pars[1] == "net") {
        $var['net'] = true;
        $dp = 4;
    }

} else {
    if (isset($pars[0]) && $pars[0] == "net") {
        $var['net'] = true;
        $dp = 4;
    }

}

$var['robot_active'] = isset($dga) && $dga->isActive();
if (isset($pars[0]) && $pars[0] == "robot" && $dga->isActive()) {
    $var['step'] = "robot";
    $var['url'] = $dga->getUrl();

    if ($var['logged_in'] && empty($user->get()['domaingate_user']) && isset($_POST['pw']) && isset($_POST['pw2'])) {
        if ($_POST['pw'] != $_POST['pw2']) {
            $var['error'] = $lang['DOMAINS']['DGERR1'];
        } else if (strlen($_POST['pw']) < 8 || ctype_alpha($_POST['pw'])) {
            $var['error'] = $lang['DOMAINS']['DGERR2'];
        } else {
            $r = $dga->createAccount($user->get()['name'], $user->get()['mail'], $_POST['pw'], $user->get()['ID'], $user->get()['api_key']);
            if (!$r) {
                $var['error'] = $lang['DOMAINS']['DGERR3'];
            } else {
                $var['success'] = $lang['DOMAINS']['DGSUC1'];
                $user->set(array("domaingate_user" => $user->get()['mail']));
            }
        }
    }

    if ($var['logged_in'] && !empty($user->get()['domaingate_user']) && isset($pars[1]) && $pars[1] == "delete") {
        if ($dga->deleteAccount($user->get()['domaingate_user'])) {
            $var['success'] = $lang['DOMAINS']['DGSUC3'];
            $user->set(array("domaingate_user" => ""));
        } else {
            $var['error'] = $lang['DOMAINS']['DGERR3'];
        }
    }

    if ($var['logged_in'] && !empty($user->get()['domaingate_user']) && isset($_POST['npw']) && isset($_POST['npw2'])) {
        if ($_POST['npw'] != $_POST['npw2']) {
            $var['error'] = $lang['DOMAINS']['DGERR1'];
        } else if (strlen($_POST['npw']) < 8 || ctype_alpha($_POST['npw'])) {
            $var['error'] = $lang['DOMAINS']['DGERR2'];
        } else {
            $r = $dga->updateAccount($user->get()['domaingate_user'], $_POST['npw'], $user->get()['ID'], $user->get()['api_key']);
            if (!$r) {
                $var['error'] = $lang['DOMAINS']['DGERR3'];
            } else {
                $var['success'] = $lang['DOMAINS']['DGSUC2'];
            }
        }
    }

    if ($var['logged_in']) {
        $var['user'] = $user->get();
    }

}

if (isset($pars[0]) && $pars[0] == "dyndns") {
    $var['step'] = "dyndns";
}

if (isset($pars[0]) && $pars[0] == "api") {
    $var['step'] = "api";
    $var['needed'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), 100)));
    if ($var['logged_in']) {
        $var['missing'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), 100 - $user->get()['credit'])));
        $var['postpaid_limit'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $user->get()['postpaid'])));

        $used = $db->query("SELECT SUM(amount) AS s FROM invoicelater WHERE user = " . $user->get()['ID'] . " AND paid = 0")->fetch_object()->s;
        $inv = $user->getInvoices(0);
        foreach ($inv as $i) {
            $used += $i->getAmount();
        }

        $var['postpaid_used'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $used)));
        $var['postpaid_left'] = $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $user->get()['postpaid'] - $used)));
        $var['currency'] = $cur->getCurrent()->getName();
    }

    if (isset($pars[1]) && $pars[1] == "activate" && $var['logged_in'] && $user->get()['credit'] >= 100) {
        $user->set(array("domain_api" => "1"));
        header('Location: ' . $CFG['PAGEURL'] . 'domains/api');
        exit;
    }

    if (isset($pars[1]) && $pars[1] == "reset" && $var['logged_in'] && $user->get()['domain_api']) {
        $user->set(array("api_key" => ""));
        header('Location: ' . $CFG['PAGEURL'] . 'domains/api');
        exit;
    }
}

if (!empty($_POST['domain'])) {
    $ex = explode(".", strtolower($_POST['domain']));

    $sld = $ex[0];
    $fsld = str_replace(array("-", "ä", "ö", "ü"), "a", $sld);

    if (substr($sld, 0, 1) == "-" || substr($sld, -1) == "-" || !ctype_alnum($fsld)) {
        $var['error'] = $lang['DOMAINS']['ERR24'];
    } else if (count($ex) > 1) {
        $sld = array_shift($ex);
        $tld = implode(".", $ex);
        if ($db->query("SELECT * FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'")->num_rows != 1) {
            $var['error'] = $lang['DOMAINS']['ERR25'];
        } else {
            if ($db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string($sld . "." . $tld) . "' AND status IN ('REG_WAITING', 'KK_WAITING')")->num_rows > 0) {
                $var['error'] = $lang['DOMAINS']['ERR26'];
            } else {
                $reg = DomainHandler::getRegistrarByTld($tld);
                if (!$reg) {
                    $var['error'] = $lang['DOMAINS']['ERR27'];
                } else if (DomainHandler::availibilityStatus($sld . "." . $tld, $reg) !== null) {
                    $_POST['sld'] = $sld;
                    $_POST['tld'] = $tld;
                } else {
                    $var['error'] = $lang['DOMAINS']['ERR28'];
                }
            }
        }
    } else {
        $var['step'] = "extensions";
    }
}

if ($CFG['TAXES']) {
    if ($var['logged_in']) {
        $taxa = $user->getVAT();
        $tax = is_array($taxa) ? $taxa[1] : 0;
        $tax_name = is_array($taxa) ? $taxa[0] : "";
    } else {
        $sql = $db->query("SELECT * FROM client_countries WHERE ID = " . $CFG['DEFAULT_COUNTRY']);
        if ($sql->num_rows == 1) {
            $info = $sql->fetch_object();
            $tax = $info->percent;
            $tax_name = $info->tax;
            $tax_country = $info->name;

            if ($tempVat = TempVat::rate($info->alpha2, $tax)) {
                $tax = $tempVat;
            }
        } else {
            $tax = 0;
        }
    }
} else {
    $tax = 0;
}

if (!empty($_POST['sld']) && (is_string($_POST['tld']) || is_array($_POST['tld']))) {
    $var['domains'] = $var['privacy'] = array();
    if (is_string($_POST['tld'])) {
        $var['domains'][] = $_POST['sld'] . "." . $_POST['tld'];

        $sql = $db->query("SELECT privacy FROM domain_pricing WHERE tld = '" . $db->real_escape_string($_POST['tld']) . "'");
        if ($sql->num_rows == 1) {
            $privacy = $sql->fetch_object()->privacy;

            if ($var['logged_in']) {
                $s = $db->query("SELECT privacy FROM domain_pricing_override WHERE user = " . $user->get()['ID'] . " AND tld = '" . $row->tld . "'");
                if ($s->num_rows == 1) {
                    $info = $s->fetch_object();
                    if ($info->privacy != $privacy) {
                        $privacy = $info->privacy;
                    }

                }
            }

            if ($privacy >= 0) {
                $var['privacy'][$_POST['sld'] . "." . $_POST['tld']] = $cur->infix($nfo->format(ceil($cur->convertAmount($cur->getBaseCurrency(), $privacy) * 100 * (1 + ($var['net'] ? 0 : $tax) / 100)) / 100));
            }

        }
    } else {
        foreach ($_POST['tld'] as $tld) {
            $var['domains'][] = $_POST['sld'] . "." . $tld;

            $sql = $db->query("SELECT privacy FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
            if ($sql->num_rows == 1) {
                $privacy = $sql->fetch_object()->privacy;

                if ($var['logged_in']) {
                    $s = $db->query("SELECT privacy FROM domain_pricing_override WHERE user = " . $user->get()['ID'] . " AND tld = '" . $row->tld . "'");
                    if ($s->num_rows == 1) {
                        $info = $s->fetch_object();
                        if ($info->privacy != $privacy) {
                            $privacy = $info->privacy;
                        }

                    }
                }

                if ($privacy >= 0) {
                    $var['privacy'][$_POST['sld'] . "." . $tld] = $cur->infix($nfo->format(ceil($cur->convertAmount($cur->getBaseCurrency(), $privacy) * 100 * (1 + ($var['net'] ? 0 : $tax) / 100)) / 100));
                }

            }
        }
    }

    $var['alternatives'] = [];
    foreach ($var['domains'] as $dom) {
        list($sld, $tld) = explode(".", $dom, 2);

        $sugLang = $lang['ISOCODE'] == "de" ? "deu" : "eng";
        $sug = @json_decode(file_get_contents("https://sourceway.de/de/suggestdomain?license_key=" . $CFG['LICENSE_KEY'] . "&sld=" . $sld . "&tld=" . $tld . "&lang=" . $sugLang), true);
        if (is_array($sug)) {
            foreach ($sug as $dom) {
                if (!in_array($dom, $var['alternatives'])) {
                    array_push($var['alternatives'], $dom);
                }
            }
        }
    }

    $var['step'] = "configure";

    $var['countries'] = array();
    $sql = $db->query("SELECT alpha2, ID FROM client_countries ORDER BY alpha2 ASC");
    while ($row = $sql->fetch_object()) {
        $var['countries'][$row->ID] = $row->alpha2;
    }

}

$var['tax'] = $nfo->format($tax, 2, true);
$var['tax_name'] = isset($tax_name) ? $tax_name : "";
$var['tax_country'] = isset($tax_country) ? $tax_country : "";
$var['own_pricing'] = false;

$var['hosting_contracts'] = [];
if ($var['logged_in']) {
    $sql = $db->query("SELECT * FROM client_products WHERE user = " . $user->get()['ID'] . " AND active = 1 ORDER BY ID ASC");
    while ($row = $sql->fetch_object()) {
        if (empty($row->module) || !array_key_exists($row->module, $provisioning->get())) {
            continue;
        }

        $mod = $provisioning->get()[$row->module];

        if (!method_exists($mod, "AssignDomain") || !method_exists($mod, "GetIP")) {
            continue;
        }

        $name = "";
        if ($row->name) {
            $name = $row->name;
        } else {
            $pSql = $db->query("SELECT `name` FROM products WHERE ID = {$row->product}");
            if ($pSql->num_rows) {
                $name = $pSql->fetch_object()->name;
                if (@unserialize($name)) {
                    $name = unserialize($name)[$CFG['LANG']] ?? "";
                }
            }
        }

        $var['hosting_contracts'][$row->ID] = [
            "desc" => "#" . $row->ID . " " . htmlentities($name) . ($row->description ? " - " . htmlentities($row->description) : ""),
            "ip" => $mod->GetIP($row->ID) ?: "5.9.7.9",
        ];
    }
}

$sql = $db->query("SELECT * FROM domain_pricing ORDER BY top ASC, tld ASC");
if ($var['step'] == "pricing") {
    $key = $var['net'] ? 2 : 1;
    $begin = "";
    $where = "";
    if (array_key_exists($key, $pars) && !empty($pars[$key]) && strlen($pars[$key]) == 1 && ctype_alpha($pars[$key])) {
        $begin = strtolower(trim($pars[$key]));
        $where = " WHERE tld LIKE '$begin%'";
    }

    $var['begin'] = $begin;

    $sql = $db->query("SELECT * FROM domain_pricing$where ORDER BY tld ASC");
}

$var['pricing'] = $var['full_pricing'] = array();

$regs = DomainHandler::getRegistrars();
$freessl = array();
foreach ($regs as $n => $r) {
    if (method_exists($r, "freessl")) {
        $freessl[] = $n;
    }
}

$rate = $cur->getConversionRate($cur->getBaseCurrency()) * (1 + ($var['net'] && ($pars[0] ?? "") != "check" ? 0 : $tax) / 100);

while ($row = $sql->fetch_object()) {
    $register = $row->register;
    $transfer = $row->transfer;
    $renew = $row->renew;
    $trade = $row->trade;
    $privacy = $row->privacy;

    if ($var['logged_in']) {
        $s = $db->query("SELECT * FROM domain_pricing_override WHERE user = " . $user->get()['ID'] . " AND tld = '" . $row->tld . "'");
        if ($s->num_rows == 1) {
            $info = $s->fetch_object();
            if ($info->register != $register) {
                $register = $info->register;
                $var['own_pricing'] = true;
            }

            if ($info->transfer != $transfer) {
                $transfer = $info->transfer;
                $var['own_pricing'] = true;
            }

            if ($info->renew != $renew) {
                $renew = $info->renew;
                $var['own_pricing'] = true;
            }

            if ($info->trade != $trade) {
                $trade = $info->trade;
                $var['own_pricing'] = true;
            }

            if ($info->privacy != $privacy) {
                $privacy = $info->privacy;
                $var['own_pricing'] = true;
            }
        }
    }

    $aSql = $db->query("SELECT `type`, `price` FROM domain_actions WHERE `start` <= '" . date("Y-m-d H:i:s") . "' AND `end` >= '" . date("Y-m-d H:i:s") . "' AND tld = '" . $db->real_escape_string(ltrim($row->tld, ".")) . "'");
    while ($aRow = $aSql->fetch_object()) {
        $price = [
            "REG" => "register",
            "RENEW" => "renew",
            "KK" => "transfer",
        ][$aRow->type];

        if ($$price > $aRow->price) {
            $$price = $aRow->price;
        }
    }

    $auth2 = $db->query("SELECT price FROM domain_auth2 WHERE tld = '" . $row->tld . "'")->num_rows == 1;

    $array = array(
        "tld" => $row->tld,
        "period" => $row->period,
        "registrar" => $row->registrar,
        "register" => $cur->infix($nfo->format($register * $rate, $dp)),
        "transfer" => $cur->infix($nfo->format($transfer * $rate, $dp)),
        "renew" => $cur->infix($nfo->format($renew * $rate, $dp)),
        "trade" => $cur->infix($nfo->format($trade * $rate, $dp)),
        "auth2" => $auth2,
        "freessl" => in_array($row->registrar, $freessl),
        "privacy" => $cur->infix($nfo->format($privacy * $rate, $dp)),
        "privacy_raw" => $privacy * $rate,
    );

    $var['full_pricing'][] = $array;
    if ($var['step'] == "pricing" || $row->top) {
        $var['pricing'][] = $array;
    }
}
$var['count'] = $db->query("SELECT COUNT(*) as c FROM domain_pricing")->fetch_object()->c;

if (isset($pars[0]) && $pars[0] == "check") {
    if (!isset($_GET['sld'])) {
        $ex = explode(".", $_GET['tld']);
        $_GET['sld'] = array_shift($ex);
        $_GET['tld'] = implode(".", $ex);
    }

    $s = DomainHandler::availibilityStatus($_GET['sld'] . "." . $_GET['tld']);

    if ($s === true) {
        $price = "";
        foreach ($var['full_pricing'] as $row) {
            if (strtolower(trim($row["tld"], ".")) == strtolower(trim($_GET['tld'], "."))) {
                $price = $row["register"];
            }
        }

        die(json_encode(["status" => "y", "price" => $price]));
    } else if ($s === false) {
        $price = "";
        foreach ($var['full_pricing'] as $row) {
            if (strtolower(trim($row["tld"], ".")) == strtolower(trim($_GET['tld'], "."))) {
                $price = $row["transfer"];
            }
        }

        die(json_encode(["status" => "n", "price" => $price]));
    }

    die(json_encode(["status" => "not"]));
}
