<?php
global $var, $db, $CFG, $pars, $lang, $user, $cur, $nfo;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$tld = ltrim($pars[0] ?? "", ".");

$sql = $db->query("SELECT * FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
if (!$sql->num_rows) {
    $title = $lang['ERROR']['TITLE'];
    $tpl = "error";
} else {
    $title = ".$tld";
    $tpl = "tld";

    $info = $sql->fetch_object();
    $var['tld'] = $tld;
    $var['d'] = (array) $info;

    $var['text'] = "";
    $res = @json_decode(file_get_contents("https://" . strtolower($lang['ISOCODE']) . ".wikipedia.org/w/api.php?format=json&action=parse&prop=sections&page=.$tld&section=0&prop=text"), true);
    if (is_array($res) && array_key_exists("parse", $res) && array_key_exists("text", $res["parse"]) && array_key_exists("*", $res["parse"]["text"])) {
        $var['text'] = $res["parse"]["text"]["*"];
        $var['text'] = str_replace('href="/', 'target="_blank" href="https://' . strtolower($lang['ISOCODE']) . '.wikipedia.org/', $var['text']);
        $var['text'] = str_replace('href="htt', 'target="_blank" href="htt', $var['text']);

        $var['source'] = "https://" . strtolower($lang['ISOCODE']) . ".wikipedia.org/wiki/.$tld";
    }

    if ($CFG['TAXES']) {
        if ($var['logged_in']) {
            $taxa = $user->getVAT();
            $tax = is_array($taxa) ? $taxa[1] : 0;
            $tax_name = is_array($taxa) ? $taxa[0] : "";
        } else {
            $sql = $db->query("SELECT * FROM client_countries WHERE ID = " . $CFG['DEFAULT_COUNTRY']);
            if ($sql->num_rows == 1) {
                $info2 = $sql->fetch_object();
                $tax = $info2->percent;
                $tax_name = $info2->tax;
                $tax_country = $info2->name;

                if ($tempVat = TempVat::rate($info2->alpha2, $tax)) {
                    $tax = $tempVat;
                }
            } else {
                $tax = 0;
            }
        }
    } else {
        $tax = 0;
    }

    // Override pricing
    if ($var['logged_in']) {
        $s = $db->query("SELECT * FROM domain_pricing_override WHERE user = " . $user->get()['ID'] . " AND tld = '" . $tld . "'");
        if ($s->num_rows == 1) {
            $info = $s->fetch_object();
        }
    }

    $var['reg'] = $cur->infix($nfo->format(ceil($cur->convertAmount($cur->getBaseCurrency(), $info->register) * 100 * (1 + $tax / 100)) / 100));
    $var['kk'] = $cur->infix($nfo->format(ceil($cur->convertAmount($cur->getBaseCurrency(), $info->transfer) * 100 * (1 + $tax / 100)) / 100));
    $var['renew'] = $cur->infix($nfo->format(ceil($cur->convertAmount($cur->getBaseCurrency(), $info->renew) * 100 * (1 + $tax / 100)) / 100));
    $var['trade'] = $info->trade == 0 ? $lang['TLD']['FREE'] : $cur->infix($nfo->format(ceil($cur->convertAmount($cur->getBaseCurrency(), $info->trade) * 100 * (1 + $tax / 100)) / 100));
    $var['privacy'] = $info->privacy == -1 ? $lang['TLD']['NOTAVAILABLE'] : ($info->privacy == 0 ? $lang['TLD']['FREE'] : $cur->infix($nfo->format(ceil($cur->convertAmount($cur->getBaseCurrency(), $info->privacy) * 100 * (1 + $tax / 100)) / 100)));
}
