<?php
global $db, $CFG, $pars, $nfo, $cur, $var, $user, $lang;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['AUTH2']['TITLE'];
$tpl = "auth2";

if (isset($_POST['tld'])) {
    header('Location: ' . $CFG['PAGEURL'] . 'auth2/' . basename($_POST['tld']));
    exit;
}

$var['limit'] = $var['logged_in'] ? $user->getLimit() : 0;

$var['tlds'] = array();
$sql = $db->query("SELECT tld FROM domain_auth2 ORDER BY tld ASC");
while ($row = $sql->fetch_object()) {
    array_push($var['tlds'], $row->tld);
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

if (isset($pars[0]) && is_object($sql = $db->query("SELECT * FROM domain_auth2 WHERE tld = '" . $db->real_escape_string($pars[0]) . "'")) && $sql->num_rows == 1) {
    $info = $sql->fetch_object();
    $var['a2i'] = (array) $info;
    $var['a2i']['price_f'] = $cur->infix($nfo->format(round($cur->convertAmount($cur->getBaseCurrency(), $var['a2i']['price']) * (1 + $tax / 100), 2)), $cur->getBaseCurrency());
    $var['a2i']['price_t'] = round($cur->convertAmount($cur->getBaseCurrency(), $var['a2i']['price']) * (1 + $tax / 100), 2);

    if (isset($_POST['sld'])) {
        try {
            if (!$var['logged_in'] || $user->get()['credit'] < $var['a2i']['price_t']) {
                throw new Exception($lang['AUTH2']['ENEC']);
            }

            if ($_POST['token'] != $_SESSION['a2token'] || empty($_SESSION['a2token'])) {
                throw new Exception($lang['AUTH2']['EREQ']);
            }

            $reg = DomainHandler::getAuthTwoByTld($info->tld);
            if (false === $reg || !(DomainHandler::getRegistrarByTld($info->tld) instanceof DomainRegistrar)) {
                throw new Exception($lang['AUTH2']['ETEC']);
            }

            if (DomainHandler::availibilityStatus($_POST['sld'] . "." . $info->tld, DomainHandler::getRegistrarByTld($info->tld)) !== false) {
                throw new Exception($lang['AUTH2']['EAVA']);
            }

            if (!$reg->requestAuthTwo($_POST['sld'] . "." . $info->tld)) {
                $user->log("[" . $_REQUEST['domain'] . "] AuthInfo2-Anforderung fehlgeschlagen");
                throw new Exception($lang['AUTH2']['ETEC']);
            }

            $inv = new Invoice;
            $inv->setClient($user->get()['ID']);
            $inv->setDate(date("Y-m-d"));
            $inv->setDueDate();
            $inv->setStatus(0);

            $item = new InvoiceItem;
            $item->setDescription("<b>" . $_POST['sld'] . "." . $info->tld . "</b><br />" . $lang['AUTH2']['TITLE']);
            $item->setAmount($var['a2i']['price_t']);

            $inv->addItem($item);
            $inv->applyCredit(false);
            $inv->send();

            $user->log("[" . $_REQUEST['domain'] . "] AuthInfo2 angefordert");

            $var['suc'] = true;
        } catch (Exception $ex) {
            $var['err'] = $ex->getMessage();
        }
    }
}

$_SESSION['a2token'] = uniqid();
