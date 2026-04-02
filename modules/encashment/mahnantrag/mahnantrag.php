<?php

class Mahnantrag extends Encashment
{
    protected $short = "mahnantrag";
    protected $name = "Mahnantrag";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "bundesland" => array("type" => "text", "name" => $this->getLang("county")),
            "companyname" => array("type" => "text", "name" => $this->getLang("company")),
            "street" => array("type" => "text", "name" => $this->getLang("street")),
            "postcode" => array("type" => "text", "name" => $this->getLang("postcode")),
            "city" => array("type" => "text", "name" => $this->getLang("city")),
            "rechtsform" => array("type" => "text", "name" => $this->getLang("legal")),
            "gname" => array("type" => "text", "name" => $this->getLang("gname")),
            "gstreet" => array("type" => "text", "name" => $this->getLang("gstreet")),
            "gpostcode" => array("type" => "text", "name" => $this->getLang("gpostcode")),
            "gcity" => array("type" => "text", "name" => $this->getLang("gcity")),
            "vordruck" => array("type" => "text", "name" => $this->getLang("vordruck")),
            "iban" => array("type" => "text", "name" => $this->getLang("iban")),
            "bic" => array("type" => "text", "name" => $this->getLang("bic")),
        );
    }

    public function newClaim($debtor, $claim)
    {
        // Sitzung starten
        $ch = curl_init("https://www.online-mahnantrag.de/omahn/Mahnantrag?_ts=4069338-1486835817990&Command=makeViewDocumentAndShowBundesland");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: cookieTest=Ihr%20Browser%20unterstuetzt%20Cookies%21"));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $res = curl_exec($ch);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        // Bundesland setzen
        $post = array(
            "Command" => "saveBundesland",
            "Reload" => "false",
            "Bundesland" => $this->options->bundesland,
            "_ts" => "1486835893566",
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: cookieTest=Ihr%20Browser%20unterstuetzt%20Cookies%21; JSESSIONID=" . $cookies['JSESSIONID'], "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36"));
        curl_setopt($ch, CURLOPT_URL, "https://www.online-mahnantrag.de/omahn/Mahnantrag");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Antragsart setzen
        $post = array(
            "Command" => "saveAntragsart",
            "Reload" => "false",
            "_Antragsart" => "barcode",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Antragsteller-Art wählen
        $post = array(
            "Command" => "leereViewDocumentASundShowAS",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Antragsteller-Daten setzen
        $c = array();
        $i = 0;
        while (strlen($this->options->companyname) > 0) {
            $c[$i] = substr($this->options->companyname, 0, 35);
            $this->options->companyname = substr($this->options->companyname, 35);
            $i++;
        }
        while ($i < 4) {
            $c[$i] = "";
            $i++;
        }

        $post = array(
            "Command" => "saveAntragstellerFirma",
            "_ts" => "1486836652141",
            "Changed" => "true",
            "_bName1" => $c[0],
            "_bName2" => $c[1],
            "_bName3" => $c[2],
            "_bName4" => $c[3],
            "_bStr" => $this->options->street,
            "_bPLZ" => $this->options->postcode,
            "_bOrt" => $this->options->city,
            "_bNation" => "",
            "_bRechtsform" => $this->options->rechtsform,
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Gesetzlicher Vertreter
        $post = array(
            "Command" => "saveAntragstellerGV",
            "_ts" => "1486836652141",
            "Changed" => "true",
            "_gvGmbHUndCoKGMM" => "false",
            "_gvVertreterFunktion" => "52",
            "_gvName1" => $this->options->gname,
            "_gvStr" => $this->options->gstreet,
            "_gvPLZ" => $this->options->gpostcode,
            "_gvOrt" => $this->options->gcity,
            "_gvNation" => "",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Drei Schritte überspringen
        $post = array(
            "Command" => "mehrAntragsteller",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        $post = array(
            "Command" => "showAGEinstieg",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        $post = array(
            "Command" => "saveErklaerungAuslaendischeRechtsformAS",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Antragsgegner ist natürliche Person
        $post = array(
            "Command" => "saveAntragsgegnerNatPers",
            "_ts" => "1486836652141",
            "Changed" => "true",
            "_bAnrede" => "1",
            "_bName1" => $debtor->firstname,
            "_bName2" => $debtor->lastname,
            "_bStr" => $debtor->street . " " . $debtor->street_number,
            "_bPLZ" => $debtor->postcode,
            "_bOrt" => $debtor->city,
            "_bNation" => ($debtor->country != "DE" ? $debtor->country : ""),
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Zwei Schritte überspringen
        $post = array(
            "Command" => "mehrAntragsgegner",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        $post = array(
            "Command" => "showVerfahrensart",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Reguläres Mahnverfahren
        $post = array(
            "Command" => "saveVerfahrensArt",
            "_bVerfahrensArt" => "NMV",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Ansprüche
        $post = array(
            "Command" => "saveSonstigerAnspruch",
            "Changed" => "true",
            "_bBegruendung" => "Rechnung " . $claim->invoice . " ({$claim->reason})",
            "counter" => "143",
            "_bVon" => date("d.m.Y", strtotime($claim->date)),
            "_bBis" => date("d.m.Y"),
            "_bBetrag" => number_format($claim->amount, 2, ',', '') . " EUR",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        $post = array(
            "Command" => "holeSonstigerAnspruch",
            "_ts" => "1486836652141",
            "_Index" => "-1",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        $post = array(
            "Command" => "saveSonstigerAnspruch",
            "Changed" => "true",
            "_bBegruendung" => "Mahngebühren aus Rechnung " . $claim->invoice,
            "counter" => "159",
            "_bVon" => date("d.m.Y", strtotime("+14 days", strtotime($claim->lastnotice))),
            "_bBis" => "11.02.2017",
            "_bBetrag" => number_format($claim->latefee, 2, ',', '') . " EUR",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Ansprüche beendet
        $post = array(
            "Command" => "anspruchWeiter",
            "MehrAbtretungZinsenAnspruch" => "Weiter",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Sonstige Kosten
        $post = array(
            "Command" => "saveAuslagenNebenforderungen",
            "Changed" => "true",
            "_ts" => "1486836652141",
            "_bVordruckPorto" => number_format(str_replace(",", ".", $this->options->vordruck), 2, ',', '') . " EUR",
            "_bSonstBetrag" => "0,00 EUR",
            "liste1" => "Sonstiger",
            "_bSonstBezeichnung" => "",
            "_bMahnKosten" => "0,00 EUR",
            "_bMahnKostenZins" => "0,000 %",
            "_bMahnKostenZinsVon" => "",
            "_bMahnKostenZinsBis" => "",
            "_bAuskunftsKosten" => "0,00 EUR",
            "_bAuskunftsKostenZins" => "0,000 %",
            "_bAuskunftsKostenZinsVon" => "",
            "_bAuskunftsKostenZinsBis" => "",
            "_bBankruecklastKosten" => "0,00 EUR",
            "_bBankruecklastKostenZins" => "0,000 %",
            "_bBankruecklastKostenZinsVon" => "",
            "_bBankruecklastKostenZinsBis" => "",
            "_bInkassoKosten" => "0,00 EUR",
            "_bInkassoKostenZins" => "0,000 %",
            "_bInkassoKostenZinsVon" => "",
            "_bInkassoKostenZinsBis" => "",
            "_bVorgerAnwaltsverguetung" => "0,00 EUR",
            "_bVorgerAnwaltsverguetungZins" => "0,000 %",
            "_bVorgerAnwaltsverguetungZinsVon" => "",
            "_bVorgerAnwaltsverguetungZinsBis" => "",
            "_bVorgerStreitwert" => "0,00 EUR",
            "_bVorgerMinderungsbetrag" => "0,00 EUR",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Allgemeine Angaben
        $post = array(
            "Command" => "showAllgemeineAngaben",
            "Changed" => "true",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        $post = array(
            "Command" => "saveAllgemeineAngaben",
            "Changed" => "true",
            "_ts" => "1486836652141",
            "_bAntragstellerGeschaeftszeichen" => "",
            "_bErklaerungVorGegenLeistung1" => "true",
            "_bErklaerungVorGegenLeistung2" => "",
            "_bErklaerungStreitVerfahren" => "",
            "_bKostenstatus" => " ",
            "_bProzesskostenhilfe" => " ",
            "_bGerichtsGeschaeftsnummer1" => "",
            "_bGerichtsGeschaeftsnummer2" => "",
            "_bGerichtsGeschaeftsnummer3" => "",
            "_bGerichtsGeschaeftsnummer4" => "",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Bankverbindung
        $post = array(
            "Command" => "saveBankVerbindung",
            "Changed" => "true",
            "_ts" => "1486836652141",
            "_bIban" => str_replace(" ", "", $this->options->iban),
            "_bBic" => $this->options->bic,
            "_bKontoZuordnung" => "1",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // Übersicht bestätigen
        $post = array(
            "Command" => "saveMBAntragUebersicht",
            "MGChecked" => "false",
            "_MGNr" => "0",
            "FehlerDa" => "false",
            "_ts" => "1486836652141",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $res = curl_exec($ch);

        // PDF laden
        curl_setopt($ch, CURLOPT_URL, "https://www.online-mahnantrag.de/omahn/Mahnantrag?_ts=4081851-1486839663670&Command=barcodeMBNative");
        curl_setopt($ch, CURLOPT_POST, false);
        $res = curl_exec($ch);
        curl_close($ch);

        if (strpos($res, "/PDF /Text") === false) {
            return array(false, $this->getLang("FAIL"));
        }

        $filename = "Mahnantrag-" . time() . "-" . rand(10000000, 99999999) . ".pdf";
        file_put_contents(__DIR__ . "/tmp/" . $filename, $res);

        return array(true, $filename);
    }

    public function claimStatus($id)
    {
        if (!file_exists(__DIR__ . "/tmp/" . basename($id))) {
            return '<font color="red">' . $this->getLang("FNF") . '</font>';
        }

        return '<a href="../modules/encashment/mahnantrag/tmp/' . $id . '" target="_blank">' . $this->getLang("DL") . '</a>';
    }
}
