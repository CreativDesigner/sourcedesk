<?PHP

/***************************************************************************\
*
*	Copyright (c) 2013 deltra Business Software GmbH & Co. KG
*	http://www.deltra.de
*
\***************************************************************************/
/***************************************************************************\
*
*	In dieser Datei haben Sie die M�glichkeit ein nicht unterst�tztes Shop-
*	system anzupassen.
*
*	Informationen diesbez�glich k�nnen Sie der Schnittstellenbeschreibung
*	der ERP Shopanbindung entnehmen. Diese finden Sie im Handbuch
*	vom ERP im Bereich "Anhang - Schnittstellenbeschreibung Webshop-
*	modul
*
*	F�r individuelle Anpassungen an der ERP Shopanbindung kann unser
*	technischer Support weder Ausk�nfte erteilen noch Hilfestellung leisten.
*
*	Die Firma deltra Software �bernimmt keine Haftung f�r Probleme die
*	mit einer angepassten Version der Shopanbindung entstehen.
*
*	Mit der Anpassung der ERP Shopanbindung stimmen Sie diesen Bedingungen
*	automatisch zu.
*
\***************************************************************************/
/***************************************************************************\
*
*	Bitte beachten Sie bei der Anpassung, dass generell nur �nderungen an
*	dieser Datei "individuell.php" notwendig sind.
*
*	Tipp:
*	
*	Beachten Sie bei individuellen Anpassungen unsere Pflichtfelder.
*	(Sehen Sie hierzu auch die Schnittstellenbeschreibung im Handbuch ein)
* 
\***************************************************************************/

function starten() {}

function ende() {}

function row_ueberpruefen($row) {
    return $row;
}

function daten_holen() {
    global $datakind, $ErgebnisArray, $db, $CFG;

    $datakind = "2";
    $ErgebnisArray = [];

    $sql = $db->query("SELECT * FROM invoices WHERE orgamax = 0 ORDER BY ID ASC");
    while ($row = $sql->fetch_object()) {
        $user = (object) [
            "firstname" => "",
            "lastname" => "",
            "city" => "",
            "street" => "",
            "street_number" => "",
            "postcode" => "",
            "salutation" => "",
            "company" => "",
            "mail" => "",
            "telephone" => "",
            "fax" => "",
            "vatid" => "",
        ];

        $sql3 = $db->query("SELECT * FROM clients WHERE ID = {$row->client}");
        if ($sql3->num_rows) {
            $user = $sql3->fetch_object();
        }

        $sql2 = $db->query("SELECT * FROM invoiceitems WHERE invoice = {$row->ID}");
        while ($row2 = $sql2->fetch_object()) {
            $artnr = 0;
            if ($row2->relid) {
                $artnr = $db->query("SELECT product FROM client_products WHERE ID = {$row2->relid}")->fetch_object()->product ?: 0;
            }

            $ErgebnisArray[] = [
                "BestellnummerShop" => $row->ID,
                "orderID" => $row->ID,
                "Bestelldatum" => $row->date,
                "KundennummerWebshop" => $row->client,
                "Artikelnummer" => $artnr,
                "Menge" => $row2->qty,
                "Firmenname" => $user->company,
                "PersonAnrede" => $user->salutation == "FEMALE" ? "Frau" : "Herr",
                "PersonNachname" => $user->lastname,
                "PersonVorname" => $user->firstname,
                "Strasse" => $user->street . " " . $user->street_number,
                "Postleitzahl" => $user->postcode,
                "Ort" => $user->city,
                "Email" => $user->mail,
                "Telefon" => $user->telephone,
                "Fax" => $user->fax,
                "Umsatzsteueridentnummer" => $user->vatid,
                "BestellwertBrutto" => $row2->amount * $row2->qty,
                "abweichenderArtikeltext" => substr(strip_tags($row2->description), 0, 255),
                "abweichenderEinzelpreisBrutto" => $row2->amount,
            ];
        }
    }

    return $datakind;
}

function status_aendern($id) {
    global $db, $CFG;
    $db->query("UPDATE invoices SET orgamax = 1 WHERE ID = " . intval($id));
}