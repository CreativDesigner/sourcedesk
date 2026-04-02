<?php
// Global some variables for security reasons
global $db, $user, $CFG, $nfo, $var, $lang, $dfo, $cur, $pars, $provisioning, $maq, $addons, $raw_cfg;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

User::status();

if (!isset($pars[0]) || !is_numeric($pars[0]) || !is_object($sql = $db->query("SELECT * FROM client_products WHERE ID = " . intval($pars[0]) . " AND type = 'h' AND active = 1 AND user = {$user->get()['ID']}")) || $sql->num_rows != 1) {
    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
} else {
    $l = $lang['INCLDOMAIN'];
    $title = $l['TITLE'];
    $tpl = "incldomain";

    $info = $sql->fetch_object();
    $pSql = $db->query("SELECT * FROM products WHERE ID = {$info->product}");
    if ($pSql->num_rows != 1) {
        $title = $lang['ERROR']['TITLE'];
        $tpl = "error";
    }
    $pInfo = $pSql->fetch_object();
    if (!empty($info->name)) {
        $pInfo->name = serialize([$CFG['LANG'] => $info->name]);
    }

    $onetime = $var['onetime'] = empty($info->billing) || $info->billing == "onetime";

    $m = null;
    $modules = $provisioning->get();
    if (array_key_exists($info->module, $modules)) {
        $m = $modules[$info->module];
    }

    $var['h'] = (array) $info;
    $var['p'] = (array) $pInfo;
    $var['pName'] = unserialize($pInfo->name)[$CFG['LANG']];
    $var['maxincldomains'] = intval($pInfo->incldomains);

    $sql = $db->query("SELECT domain FROM domains WHERE inclusive_id = {$info->ID}");
    $var['incldomains'] = $sql->num_rows;

    $var['tlds'] = [];

    $tlds = implode(",", unserialize($pInfo->incltlds));
    $sql = $db->query("SELECT tld FROM domain_pricing WHERE ID IN ($tlds) ORDER BY tld ASC");
    while ($row = $sql->fetch_object()) {
        array_push($var['tlds'], ltrim($row->tld, "."));
    }

    if (($var['maxincldomains'] - $var['incldomains']) > 0 && count($var['tlds'])) {
        if (isset($_POST['sld'])) {
            if (empty($_POST['sld'])) {
                die($l['ERR1']);
            }

            if (!in_array(ltrim($_POST['tld'], "."), $var['tlds'])) {
                die($l['ERR2']);
            }

            if (strpos($_POST['sld'], ".") !== false) {
                die($l['ERR2']);
            }

            $domain = strtolower($_POST['sld']) . "." . strtolower(ltrim($_POST['tld'], "."));
            if ($db->query("SELECT 1 FROM domains WHERE domain LIKE '" . $db->real_escape_string($domain) . "' AND status IN ('REG_WAITING', 'KK_WAITING')")->num_rows > 0) {
                die($l['ERR3']);
            }

            $reg = DomainHandler::getRegistrarByTld(strtolower(ltrim($_POST['tld'], ".")));
            if (!$reg || !$reg->isActive()) {
                die($l['ERR3']);
            }

            $status = DomainHandler::availibilityStatus($domain, $reg);

            if ($status === true) {
                if (!empty($_POST['authcode'])) {
                    die($l['ERR4']);
                }
            } else if ($status === false) {
                if (empty($_POST['authcode'])) {
                    die($l['ERR5']);
                }
            } else {
                die($l['ERR3']);
            }

            $contact = [
                $user->get()['firstname'],
                $user->get()['lastname'],
                $user->get()['company'],
                $user->get()['street'] . " " . $user->get()['street_number'],
                $user->get()['country_alpha2'],
                $user->get()['postcode'],
                $user->get()['city'],
                $user->get()['telephone'],
                $user->get()['fax'],
                $user->get()['mail'],
            ];

            $data = [
                'owner' => $contact,
                'admin' => $contact,
                'tech' => unserialize($CFG['WHOIS_DATA']),
                'zone' => unserialize($CFG['WHOIS_DATA']),
                'ns' => [
                    $CFG['DEFAULT_IP'],
                    '',
                ],
                'domain' => $domain,
                'privacy' => '0',
            ];

            if ($status === false) {
                $data['transfer'] = [
                    $_POST['authcode'],
                    true,
                ];
            }

            $data = serialize($data);
            $uid = $user->get()['ID'];
            $recurring = $user->getDomainPrice($_POST['tld'], "renew");
            $status = $status ? "REG_WAITING" : "KK_WAITING";
            $registrar = $reg->getShort();

            $sql = $db->prepare("INSERT INTO domains (`user`, `domain`, `reg_info`, `recurring`, `created`, `expiration`, `status`, `registrar`, `inclusive_id`) VALUES (?,?,?,?,?,?,?,?,?)");
            $sql->bind_param("issdssssi", $uid, $domain, $data, $recurring, $created = date("Y-m-d"), $expiration = date("Y-m-d", strtotime("+1 year")), $status, $registrar, $info->ID);
            $sql->execute();
            $sql->close();

            if (($ntf = AdminNotification::getInstance("Neue Bestellung")) !== false) {
                $ntf->set("items", $domain);
                $ntf->set("customer", $user->get()['name']);
                $ntf->set("cid", $user->get()['ID']);
                $ntf->set("clink", $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $user->get()['ID']);
    
                // Get NFO by language
                $amount = array();
                foreach (unserialize($raw_cfg['NUMBER_FORMAT']) as $l => $f) {
                    $amount[$l] = $cur->infix($nfo->format(0, 2, 0, $f), $cur->getBaseCurrency());
                }
    
                $ntf->set("amount", $amount);
    
                $ntf->send();
            }

            die("ok");
        }
    } else {
        $title = $lang['ERROR']['TITLE'];
        $tpl = "error";
    }
}
