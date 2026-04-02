<?php

class InsolvenzScoring extends Scoring
{
    protected $short = "insolvenz";
    protected $name = "Insolvenz";
    protected $version = "1.0";

    public function getSettings()
    {
        return array();
    }

    public function getMethods()
    {
        return array(
            "insolvenz" => $this->getLang("CHECK"),
        );
    }

    public function insolvenz($u)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://www.insolvenzbekanntmachungen.de/cgi-bin/bl_suche.pl");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Suchfunktion=uneingeschr&Absenden=Suche+starten&Bundesland=--+Alle+Bundesl%E4nder+--&Gericht=--+Alle+Insolvenzgerichte+--&Datum1=&Datum2=&Name=" . urlencode($u->get()['lastname']) . "&Sitz=" . urlencode($u->get()['city']) . "&Abteilungsnr=&Registerzeichen=--&Lfdnr=&Jahreszahl=--&Registerart=--+keine+Angabe+--&select_registergericht=&Registergericht=--+keine+Angabe+--&Registernummer=&Gegenstand=--+Alle+Bekanntmachungen+innerhalb+des+Verfahrens+--&matchesperpage=10&page=1&sortedby=Datum");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = "Origin: https://www.insolvenzbekanntmachungen.de";
        $headers[] = "Accept-Encoding: gzip, deflate, br";
        $headers[] = "Accept-Language: de-DE,de;q=0.8,en;q=0.6,en-US;q=0.4";
        $headers[] = "Upgrade-Insecure-Requests: 1";
        $headers[] = "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36";
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8";
        $headers[] = "Cache-Control: max-age=0";
        $headers[] = "Referer: https://www.insolvenzbekanntmachungen.de/cgi-bin/bl_suche.pl";
        $headers[] = "Connection: keep-alive";
        $headers[] = "Dnt: 1";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            die('Error:' . curl_error($ch));
        }

        curl_close($ch);

        $found = strpos($res, "Treffer gefunden");

        if ($found === false) {
            return array($this->getLang("NT"), "D", "");
        }

        $details = "<ul>";
        $dom = new DOMDocument;
        @$dom->loadHTML($res);
        $a = $dom->getElementsByTagName("a");
        foreach ($a as $e) {
            if (strpos($e->getAttribute("href"), "bl_aufruf.pl") === false) {
                continue;
            }

            $md = $e->textContent;
            if (str_replace($u->get()['firstname'], "", $md) == $md) {
                continue;
            }

            $link = "https://www.insolvenzbekanntmachungen.de/" . substr($e->getAttribute("href"), 23, -2);
            $details .= "<li><a href=\"$link\" target=\"_blank\">$md</a></li>";
        }
        $details .= "</ul>";

        if ($details == "<ul></ul>") {
            return array($this->getLang("NT"), "D", "");
        }

        return array($this->getLang("FOUND"), "F", $details);
    }
}
