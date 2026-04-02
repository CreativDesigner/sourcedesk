<?PHP
/***************************************************************************\
*
*	Copyright (c) 2013 deltra Business Software GmbH & Co. KG
*	http://www.deltra.de
*
\***************************************************************************/

/***************************************************************
 * Include Functions
 ***************************************************************/
/**
 * überprüft ob ein gültiger Webshop vorliegt und bindet deren Funktionen ein.
 *
 * @param $webshop
 * @return bool
 */
function includeShopSystemIfValid($webshop)
{
    // PHP Version < 5.3.0
    $dir = dirname(__FILE__);

	if(!isValidWebshop($webshop))
	{
		die(xml_error_ausgeben(DeltraResources::getText("SHOPSYSTEM_NOT_VALID"),__FILE__, __FUNCTION__, __LINE__));
	}
    $inc_datei = $dir . "/shops/" . $webshop . ".php";

	// Benötigte Dateien einbinden:
	if (file_exists($inc_datei)) 
	{
		try
		{
			include($inc_datei);
			return true;
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
			return false;
		}
	}
	else 
	{
		return false;
	}
}


/***************************************************************
 * Validation Functions
 ***************************************************************/
/**
 * überprüft, ob die für den Shop Connector benötigten Extensions installiert sind und gibt die noch benötigten Extensions in Form eines Arrays zurück.
 * @internal param $extension
 * @return array
 */
function checkExtensions()
{
    $required = array();
    $extensionList = Extensions::getList();
    foreach($extensionList as $extension)
    {
        if(!extension_loaded($extension))
        {
            $required[] = $extension;
        }
    }
    return $required;
}

/**
 * überprüft, ob ein Webshop mit dem Namen aus dem übergebenen Parameter existiert
 * @param $webshop
 * @return bool
 */
function isValidWebshop($webshop)
{
	$webshops = array();
    $shopInfo = Shopsystems::getList();
	foreach($shopInfo as $shopKey => $shopData)
	{
        $webshops[] = $shopKey;
	}

    if(empty($webshops))
	{
		die(DeltraResources::getText("WEBSHOPS_IS_EMPTY"));
	}
	else if(!is_array($webshops))
    {
        die(DeltraResources::getText("WEBSHOPS_IS_NOT_ARRAY"));
    }

    $result = (in_array($webshop, $webshops)) ? true : false;
    return $result;
}


/***************************************************************
 * Format Functions
 ***************************************************************/
/**
 *    entfernt die HTML-Tags aus dem übergebenen String und wandelt diesen um.
 * @param $string
 * @return string
 */
function formatString($string)
{
    $string = strip_tags($string);
    $string = mb_convert_encoding($string, "HTML-ENTITIES", "UTF-8");
	$string = html_entity_decode($string, ENT_COMPAT, "HTML-ENTITIES");
    return $string;
}


/***************************************************************
* XML Export Functions
***************************************************************/

$XML_Response = '';
$XMLphpfehler = '';
$XMLDoc = '';
$XMLResult = '';

function xml_error_ausgeben($meldung, $datei, $funktion, $zeile)
{
	$XMLphpfehler='<?xml version="1.0" encoding="utf-8"?>'."\n".
	'<orgamax_phperror>'."\n".
	'<message>'.utf8_encode($meldung).'</message>'."\n".
	'<phpfile>'.utf8_encode($datei).'</phpfile>'."\n".
	'<function>'.utf8_encode($funktion).'</function>'."\n".
	'<line>'.utf8_encode($zeile).'</line>'."\n".
	'<errortime>'.utf8_encode(date("Y-m-d H:i:s")).'</errortime>'."\n".
	'</orgamax_phperror>';
	return $XMLphpfehler;
}// Fehler ausgeben

function xml_success_ausgeben($meldung)
{
	$XMLphpfehler='<?xml version="1.0" encoding="utf-8"?>'."\n".
	'<orgamax_phpsuccess>'."\n".
	'<message>'.utf8_encode($meldung).'</message>'."\n".
	'<status>' . utf8_encode('Erfolgreich') . '</status>'."\n".
	'<time>'.utf8_encode(date("Y-m-d H:i:s")).'</time>'."\n".
	'</orgamax_phpsuccess>';
	return $XMLphpfehler;
}// "Success" ausgeben

function export_protokoll_erzeugen($Datum, $SFVersion, $SCVersion, $Status, $Datensaetze_Gesamt, $Datensaetze_Uebergeben, $ProtokollData)
{
	$XML_Response = '<?xml version="1.0" encoding="utf-8"?>';
	$XML_Response .= '<Importprotokoll Datum="'.$Datum.'" SFVersion="'.$SFVersion.'" SCVersion="'.$SCVersion.'">';
	$XML_Response .= '<ImportStatus>'.$Status.'</ImportStatus>';
	$XML_Response .= '<Datensaetze_Gesamt>'.$Datensaetze_Gesamt.'</Datensaetze_Gesamt>';
	$XML_Response .= '<Datensaetze_Uebergeben>'.$Datensaetze_Uebergeben.'</Datensaetze_Uebergeben>';
	
	foreach($ProtokollData as $PRow)
	{
		$XML_Response .= '<Datensatz>';
		$XML_Response .= '<DatensatzNummer>'.$PRow["DatensatzNummer"].'</DatensatzNummer>';
		$XML_Response .= '<Status>'.$PRow["Status"].'</Status>';
		$XML_Response .= '<Fehlermeldung>'.$PRow["Fehlermeldung"].'</Fehlermeldung>';
		$XML_Response .= '</Datensatz>';
	}
	$XML_Response .= '</Importprotokoll>';
	
	return $XML_Response;
}

function GetSingleTag($TagName, $value,  $length = 0)
{
 if ((!is_null($value)) and ($value != ''))
 {
  $Res = "<$TagName>";
  if((phpversion() >= '4.4.3' && phpversion() < 5) || (phpversion() >= '5.1.3'))
  {
		$xml_value = (mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8'));
	  if ($length > 0)
	  {
	   $Res .= "<![CDATA[".mb_substr($xml_value, 0, $length, "UTF-8")."]]>";
	  } // Auf l�nge Trimmen und encoden
	  else 
	  {
			$Res .= "<![CDATA[".mb_substr($xml_value, 0, mb_strlen($xml_value, 'UTF-8'), 'UTF-8')."]]>";
	  } // Die l�nge darf nicht ge�ndert werden + encoden
  }
  else
  {
		$xml_value = (mb_detect_encoding($value) == 'UTF-8') ? $value : utf8_encode($value);
		if($length > 0)
		{
			$Res .= "<![CDATA[".mb_substr($xml_value, 0, $length, "UTF-8")."]]>";
		}
		else
		{
			$Res .= "<![CDATA[".mb_substr($xml_value, 0, mb_strlen($xml_value, 'UTF-8'), 'UTF-8')."]]>";
		}
  }
  $Res .= "</$TagName>";
  return $Res;
 } // Value hat einen Wert
 else 
 {
  return "<$TagName/>";
 } // leeres Tag
} // Get Single Tag

function ArtikelEintragen($row, $XMLDoc)
{
	$row = artRow_ueberpruefen($row);
	
	$XMLDoc .= '<row>';
	$XMLDoc .= GetSingleTag('Artikelnummer', (isset($row['Artikelnummer'])) ? $row["Artikelnummer"] : "", 32) . "\n" .
	GetSingleTag('ArtikelnummerWebshop', (isset($row['ArtikelnummerWebshop'])) ? $row["ArtikelnummerWebshop"] : "", 32) . "\n" .
	GetSingleTag('Artikelbeschreibung', (isset($row['Artikelbeschreibung'])) ? $row["Artikelbeschreibung"] : "", 1024) . "\n" .
	GetSingleTag('MwStCode', (isset($row["MwStCode"])) ? $row["MwStCode"] : "") . "\n" .
	GetSingleTag('MwStValue', (isset($row['MwStValue'])) ? $row["MwStValue"] : "") . "\n" . 
	GetSingleTag('Einheit', (isset($row["Einheit"])) ? $row["Einheit"] : "", 20) . "\n" .
	GetSingleTag('Artikelkategorie',(isset( $row["Artikelkategorie"])) ?  $row["Artikelkategorie"] : "") . "\n" .
	GetSingleTag('Gewicht', (isset($row["Gewicht"])) ? $row["Gewicht"] : "") . "\n" .
	GetSingleTag('Volumen', (isset($row["Volumen"])) ? $row["Volumen"] : "") . "\n" .
	GetSingleTag('Anmerkungen', (isset($row["Anmerkungen"])) ? strip_tags($row["Anmerkungen"]) : "", 250) . "\n" .
	GetSingleTag('ArtikelpreisNetto', (isset($row["ArtikelpreisNetto"])) ? $row["ArtikelpreisNetto"] : "") . "\n" .
	GetSingleTag('ArtikelpreisBrutto', (isset($row["ArtikelpreisBrutto"])) ? $row["ArtikelpreisBrutto"] : "") . "\n" .
	GetSingleTag('Einkaufspreis', (isset($row["Einkaufspreis"])) ? $row["Einkaufspreis"] : "") . "\n" .
	GetSingleTag('Artikelbild', (isset($row["Artikelbild"])) ? $row["Artikelbild"] : "") . "\n" .
	GetSingleTag('IndividuellesFeld1', (isset($row["IndividuellesFeld1"])) ? $row["IndividuellesFeld1"] : "", 50) . "\n" .
	GetSingleTag('IndividuellesFeld2', (isset($row["IndividuellesFeld2"])) ? $row["IndividuellesFeld2"] : "", 50) . "\n" .
	GetSingleTag('IndividuellesFeld3', (isset($row["IndividuellesFeld3"])) ? $row["IndividuellesFeld3"] : "", 50) . "\n" .
	GetSingleTag('IndividuellesFeld4', (isset($row["IndividuellesFeld4"])) ? $row["IndividuellesFeld4"] : "", 50) . "\n" . 
	GetSingleTag('IndividuellesFeld5', (isset($row["IndividuellesFeld5"])) ? $row["IndividuellesFeld5"] : "", 50) . "\n" ;
	$XMLDoc .= '</row>';
	
	return $XMLDoc;
	
} // Artikel Eintragen

function PositionEintragen($row, $XMLDoc)
{
	$row= row_ueberpruefen($row);
	$Feldliste = array_keys($row);
	
	ini_set('error_reporting',"E_ALL & ~E_NOTICE & ~E_WARNING");
	
	if ($row['orderID'] != $GLOBALS['lastorderID'])
	{
		if ($GLOBALS['lastorderID'] != -1)
		{
			$XMLDoc .= '</Bestellvorgang>';
		} // den letzten Bestellvorgang schlie�en
		$GLOBALS['lastorderID'] = $row['orderID'];
		$XMLDoc .= '<Bestellvorgang>'."\n";
		/* Hier die den Buchungskopf erzeugen */
		$XMLDoc .= '<BestellnummerShop>'.$row["BestellnummerShop"].'</BestellnummerShop>' . "\n" .
		GetSingleTag('Bestelldatum', $row["Bestelldatum"]) . "\n" .
		GetSingleTag('Wunschlieferdatum', $row["Wunschlieferdatum"]) . "\n" .
		GetSingleTag('Lieferart', $row["Lieferart"], 50) . "\n" .
		GetSingleTag('Zahlungsart', $row["Zahlungsart"], 50) . "\n" .
		GetSingleTag('BestellwertBrutto', $row["BestellwertBrutto"]) . "\n" .
		GetSingleTag('ZusatzfeldBestellung1', $row["ZusatzfeldBestellung1"], 50) . "\n" .
		GetSingleTag('ZusatzfeldBestellung2', $row["ZusatzfeldBestellung2"], 50) . "\n" .
		GetSingleTag('ZusatzfeldBestellung3', $row["ZusatzfeldBestellung3"], 50) . "\n" .
		GetSingleTag('ZusatzfeldBestellung4', $row["ZusatzfeldBestellung4"], 50) . "\n" .
		GetSingleTag('ZusatzfeldBestellung5', $row["ZusatzfeldBestellung5"], 50) . "\n" .
		GetSingleTag('AnmerkungenBestellung', strip_tags($row["AnmerkungenBestellung"]), 255) . "\n" .
		'<Kundendaten>' . "\n" .
		'<Kunde>' . "\n" .
		GetSingleTag('Kundennummer', $row["Kundennummer"]) . "\n" .
		GetSingleTag('KundennummerWebshop', $row["KundennummerWebshop"]) . "\n" .
		GetSingleTag('Firmenname', $row["Firmenname"], 50) . "\n" .
		GetSingleTag('Firmenzusatzname', $row["Firmenzusatzname"], 50) . "\n" .
		GetSingleTag('PersonAnrede', $row["PersonAnrede"], 30) . "\n" .
		GetSingleTag('PersonGeschlecht', $row["PersonGeschlecht"], 1) . "\n" .
		GetSingleTag('PersonTitel', $row["PersonTitel"], 30) . "\n" .
		GetSingleTag('PersonNachname', $row["PersonNachname"], 50) . "\n" .
		GetSingleTag('PersonVorname', $row["PersonVorname"], 50) . "\n" .
		GetSingleTag('Strasse', $row["Strasse"], 50) . "\n" .
		GetSingleTag('Postleitzahl', $row["Postleitzahl"], 10) . "\n" .
		GetSingleTag('Ort', $row["Ort"], 50) . "\n" .
		GetSingleTag('Laendercode', $row["Laendercode"], 5) . "\n" .
		GetSingleTag('Land', $row["Land"], 50) . "\n" .
		GetSingleTag('Email', $row["Email"], 50) . "\n" .
		GetSingleTag('Telefon', $row["Telefon"], 40) . "\n" .
		GetSingleTag('Fax', $row["Fax"], 40) . "\n" .
		GetSingleTag('Umsatzsteueridentnummer', $row["Umsatzsteueridentnummer"], 20) . "\n" .
		GetSingleTag('ZusatzfeldKunde1', $row["ZusatzfeldKunde1"], 50) . "\n" .
		GetSingleTag('ZusatzfeldKunde2', $row["ZusatzfeldKunde2"], 50) . "\n" .
		GetSingleTag('ZusatzfeldKunde3', $row["ZusatzfeldKunde3"], 50) . "\n" .
		GetSingleTag('ZusatzfeldKunde4', $row["ZusatzfeldKunde4"], 50) . "\n" .
		GetSingleTag('ZusatzfeldKunde5', $row["ZusatzfeldKunde5"], 50) . "\n" .
		'</Kunde>' . "\n" .
		'<Kontodaten>' . "\n" .
		GetSingleTag('BankkontoInhaber', $row["BankkontoInhaber"], 27) . "\n" .
		GetSingleTag('Bankkontonummer', $row["Bankkontonummer"], 20) . "\n" .
		GetSingleTag('BankkontoBLZ', $row["BankkontoBLZ"], 15) . "\n" .
		GetSingleTag('BankkontoBankName', $row["BankkontoBankName"], 40) . "\n" .
		GetSingleTag('BankkontoIBAN', $row["BankkontoIBAN"], 34) . "\n" .
		GetSingleTag('BankkontoBIC', $row["BankkontoBIC"], 11) . "\n" .	
		'</Kontodaten>' . "\n" .
		'<AbweichendLieferung>' . "\n" .
		GetSingleTag('abweichendLieferungFirmenname', $row["abweichendLieferungFirmenname"], 50) . "\n" .
		GetSingleTag('abweichendLieferungFirmenzusatz', $row["abweichendLieferungFirmenzusatz"], 50) . "\n" .
		GetSingleTag('abweichendLieferungPersAnrede', $row["abweichendLieferungPersAnrede"], 30) . "\n" .
		GetSingleTag('abweichendLieferungPersGeschl', $row["abweichendLieferungPersGeschl"], 1) . "\n" .
		GetSingleTag('abweichendLieferungPersTitel', $row["abweichendLieferungPersTitel"], 30) . "\n" .
		GetSingleTag('abweichendLieferungPersNachname', $row["abweichendLieferungPersNachname"], 50) . "\n" .
		GetSingleTag('abweichendLieferungPersVorname', $row["abweichendLieferungPersVorname"], 50) . "\n" .
		GetSingleTag('abweichendLieferungStrasse', $row["abweichendLieferungStrasse"], 50) . "\n" .
		GetSingleTag('abweichendLieferungPostleitzahl', $row["abweichendLieferungPostleitzahl"], 10) . "\n" .
		GetSingleTag('abweichendLieferungOrt', $row["abweichendLieferungOrt"], 50) . "\n" .
		GetSingleTag('abweichendLieferungLaendercode', $row["abweichendLieferungLaendercode"], 5) . "\n" .
		GetSingleTag('abweichendLieferungLand', $row["abweichendLieferungLand"], 50) . "\n" .
		GetSingleTag('abweichendLieferungEmail', $row["abweichendLieferungEmail"], 50) . "\n" .
		GetSingleTag('abweichendLieferungTelefon', $row["abweichendLieferungTelefon"], 40) . "\n" .
		GetSingleTag('abweichendLieferungFax', $row["abweichendLieferungFax"], 40) . "\n" .
		'</AbweichendLieferung>' . "\n" .
		'<AbweichendRechnung>' . "\n" .
		GetSingleTag('abweichendRechnungFirmenname', $row["abweichendRechnungFirmenname"], 50) . "\n" .
		GetSingleTag('abweichendRechnungFirmenzusatz', $row["abweichendRechnungFirmenzusatz"], 50) . "\n" .
		GetSingleTag('abweichendRechnungPersAnrede', $row["abweichendRechnungPersAnrede"], 30) . "\n" .
		GetSingleTag('abweichendRechnungPersGeschl', $row["abweichendRechnungPersGesch"], 1) . "\n" .
		GetSingleTag('abweichendRechnungPersTitel', $row["abweichendRechnungPersTitel"], 30) . "\n" .
		GetSingleTag('abweichendRechnungPersNachname', $row["abweichendRechnungPersNachname"], 50) . "\n" .
		GetSingleTag('abweichendRechnungPersVorname', $row["abweichendRechnungPersVorname"], 50) . "\n" .
		GetSingleTag('abweichendRechnungStrasse', $row["abweichendRechnungStrasse"], 50) . "\n" .
		GetSingleTag('abweichendRechnungPostleitzahl', $row["abweichendRechnungPostleitzahl"], 10) . "\n" .
		GetSingleTag('abweichendRechnungOrt', $row["abweichendRechnungOrt"], 50) . "\n" .
		GetSingleTag('abweichendRechnungLaendercode', $row["abweichendRechnungLaendercode"], 5) . "\n" .
		GetSingleTag('abweichendRechnungLand', $row["abweichendRechnungLand"], 50) . "\n" .
		GetSingleTag('abweichendRechnungEmail', $row["abweichendRechnungEmail"], 50) . "\n" .
		GetSingleTag('abweichendRechnungTelefon', $row["abweichendRechnungTelefon"], 40) . "\n" .
		GetSingleTag('abweichendRechnungFax', $row["abweichendRechnungFax"], 40) . "\n" .
		'</AbweichendRechnung>' . "\n" .
		'<Frachtkosten>' . "\n" .
		GetSingleTag('FrachtkostenNetto', $row["FrachtkostenNetto"]) . "\n" .
		GetSingleTag('FrachtkostenBrutto', $row["FrachtkostenBrutto"]) . "\n" .
		GetSingleTag('FrachtkostenMwStProzent', $row["FrachtkostenMwStProzent"]) . "\n" .
		'</Frachtkosten>' . "\n" .
		'</Kundendaten>' . "\n" .
		'<Zuschlagkosten>' . "\n" .
		GetSingleTag('ZuschlagkostenNetto1', $row["ZuschlagkostenNetto1"]) . "\n" .
		GetSingleTag('ZuschlagkostenBrutto1', $row["ZuschlagkostenBrutto1"]) . "\n" .
		GetSingleTag('ZuschlagkostenMwStProzent1', $row["ZuschlagkostenMwStProzent1"]) . "\n" .
		GetSingleTag('ZuschlagkostenNetto2', $row["ZuschlagkostenNetto2"]) . "\n" .
		GetSingleTag('ZuschlagkostenBrutto2', $row["ZuschlagkostenBrutto2"]) . "\n" .
		GetSingleTag('ZuschlagkostenMwStProzent2', $row["ZuschlagkostenMwStProzent2"]) . "\n" .
		GetSingleTag('ZuschlagkostenNetto3', $row["ZuschlagkostenNetto3"]) . "\n" .
		GetSingleTag('ZuschlagkostenBrutto3', $row["ZuschlagkostenBrutto3"]) . "\n" .
		GetSingleTag('ZuschlagkostenMwStProzent3', $row["ZuschlagkostenMwStProzent3"]) . "\n" .
		'</Zuschlagkosten>' . "\n";
	} // Dies ist die erste Position einer neuen Buchung
	/* Hier die eigentliche Buchungsposition eintragen */
	$XMLDoc .= '<BestellArtikel>' . "\n" .
	GetSingleTag('Positionsnummer', $row["Positionsnummer"]) . "\n" .
	GetSingleTag('Artikelnummer', $row["Artikelnummer"], 32) . "\n" .
	GetSingleTag('ArtikelnummerShop', $row["ArtikelnummerShop"], 32) . "\n" .
	GetSingleTag('Menge', $row["Menge"]) . "\n" .
	GetSingleTag('abweichenderEinzelpreisNetto', $row["abweichenderEinzelpreisNetto"]) . "\n" .
	GetSingleTag('abweichenderEinzelpreisBrutto', $row["abweichenderEinzelpreisBrutto"]) . "\n" .
	GetSingleTag('abweichendeMwStProzent', $row["abweichendeMwStProzent"]) . "\n" .
	GetSingleTag('abweichenderArtikeltext', $row["abweichenderArtikeltext"], 255) . "\n" .
	GetSingleTag('RabattProzent', $row["RabattProzent"]) . "\n" .
	GetSingleTag('ZusatzfeldPosition1', $row["ZusatzfeldPosition1"], 50) . "\n" .
	GetSingleTag('ZusatzfeldPosition2', $row["ZusatzfeldPosition2"], 50) . "\n" .
	GetSingleTag('ZusatzfeldPosition3', $row["ZusatzfeldPosition3"], 50) . "\n" .
	GetSingleTag('ZusatzfeldPosition4', $row["ZusatzfeldPosition4"], 50) . "\n" .
	GetSingleTag('ZusatzfeldPosition5', $row["ZusatzfeldPosition5"], 50) . "\n" .
	'</BestellArtikel>' . "\n";
	return $XMLDoc;
} // Position Eintragen

/***************************************************************
* XML Import Functions
***************************************************************/

  function IsDataRow($aRow)
  {
    return preg_match('/<([\w]+[^>])*>[^<]*<\/([\w]+[^>])\1>/', $aRow);
  } // Is Data Row
	
  function GetTagName($aRow)
  {
    preg_match('/<([\w]+[^>])*>/', $aRow, $treffer);
    return $treffer[1];
  } // Get Tag Name
	
  function GetTagValue($aRow)
  {
    preg_match('/>[^<]*<\//', $aRow, $treffer);
    return rtrim(ltrim($treffer[0], '>'), '</');
  } // Get Tag Name
	
  function InputXML2Array($aInputXML)
  {
    $RowNo = -1;
    $Data = explode("\n", $aInputXML);
    foreach ($Data as $Row)
    {
      if (strpos($Row, '<row>') !== false)
      {
        $RowNo++;
      } // Ein neuer Datensatz beginnt
      else if (($RowNo > -1) && (IsDataRow($Row)))
      {
        $Res[$RowNo][GetTagName($Row)] = utf8_decode(GetTagValue($Row));
      } // Ein Datenelement wurde gefunden
    } // Alle Zeilen des XML Dokuments durchlaufen
    return $Res;
  } // Input XML to Array

/***************************************************************
* Div Functions
***************************************************************/
/**
 * liest bestimmte Werte aus der php.ini und gibt diese als Array zurück
 * @return array
 */
function getImportantIniValues()
{
	$iniSettings = array(
		"error_reporting" => ini_get("error_reporting"),
		"display_errors" => ini_get("display_errors"),
		"display_start_up_errors" => ini_get("display_start_up_errors"),
		"register_globals" => ini_get("register_globals"),
		"magic_quotes_gpc" => ini_get("magic_quotes_gpc"),
		"magic_quotes_runtime" => ini_get("magic_quotes_runtime")
	);
	return $iniSettings;
}

/**
 * liest Werte mithilfe von getImportantIniValues() aus der php.ini und dumped diese
 */
function dumpImportantIniValues()
{
	$iniValues = getImportantIniValues();
	var_dump($iniValues);
}

function WriteXMLResult($whole_amount, $amount_successfully_created)
{	
	$xmlResult = '<?xml version="1.0" encoding="utf-8"?>';
	$xmlResult .= '<Exportprotokoll>'.'';
	$xmlResult .= '<Export_Status>'. '';
	if($amount_successfully_created == 0)
	{
		$xmlResult .= 'ROLLBACK';
	}
	else
	{
		$xmlResult .= 'SUCCESS';
	}
	$xmlResult .= '</Export_Status>'. '';
	$xmlResult .= '<Anzahl_Datensaetze_Gesamt>'.'';
	$xmlResult .= $whole_amount;
	$xmlResult .= '</Anzahl_Datensaetze_Gesamt>'.'';
	$xmlResult .= '<Anzahl_Datensaetze_Erfolgreich_Uebergeben>'.'';
	$xmlResult .= $amount_successfully_created;
	$xmlResult .= '</Anzahl_Datensaetze_Erfolgreich_Uebergeben>'.'';
	$xmlResult .= '</Exportprotokoll>'.'';
	
	echo $xmlResult;
}

/** Elemente in DOM einf�gen  */
function Add_NewDocElement($root, $parent, $name, $value) {
    $parent->appendChild($root->createElement($name))->appendChild((empty($value)) ? $root->createTextNode($value) : $root->createCDATASection($value));
}

function Protokolleintrag_hinzufuegen($DatensatzNummer, $Status, $Fehlermeldung)
{
	$Protokoll_Row["DatensatzNummer"] = $DatensatzNummer;
	$Protokoll_Row["Status"] = $Status;
	$Protokoll_Row["Fehlermeldung"] = $Fehlermeldung;
	$GLOBALS["Statusprotokoll"][] = $Protokoll_Row;
}
  
function LaenderkuerzelISO2OMX($aISOKuerzel)
{
     $Res = $aISOKuerzel;
     switch ($aISOKuerzel)
     {
          case 'AF': // Afganistan
                $Res = 'AFG';
                break;
          case 'EG': // �gipten
                $Res = 'ET';
                break;
          case 'AD': // Andorra
                $Res = 'AND';
                break;
          case 'AO': // Angola
                $Res = 'ANG';
               break;
          case 'AR': // Argentinien
                $Res = 'RA';
                break;
          case 'ET': // �thopien
                $Res = 'ETH';
                break;
          case 'AU': // Australien
                $Res = 'AUS';
                break;
          case 'BH': // Bahrain
                $Res = 'BRN';
                break;
          case 'BB': // Barbados
                $Res = 'BDS';
                break;
          case 'BY': // Wei�rusland
                $Res = 'BLR';
                break;
          case 'BE': // Belgien
                $Res = 'B';
                break;
          case 'BZ': // Belize
                $Res = 'BH';
                break;
          case 'BJ': // Benin
                $Res = 'DY';
                break;
          case 'BO': // Bolivien
                $Res = 'BOL';
                break;
          case 'BA': // Bosnien-Herzigovina
                $Res = 'BIH';
                break;
          case 'BW': // Botsuana
                $Res = 'RB';
                break;
          case 'GG': // Guernsey
                $Res = 'GBG';
                break;
          case 'JE': // Jersey
                $Res = 'GBJ';
                break;
          case 'BN': // Brunei
                $Res = 'BRU';
                break;
          case 'CL': // Chile
                $Res = 'RCH';
                break;
          case 'CN': // China
                $Res = 'RC';
                break;
          case 'DE': // Deutschland
                $Res = 'D';
                break;
          case 'DM': // Dominica
                $Res = 'WD';
                break;
          case 'DO': // Dominikanische Republik
                $Res = 'DOM';
                break;
          case 'SV': // El Salvador
                $Res = 'ES';
                break;
          case 'EE': // Estland
                $Res = 'EST';
                break;
          case 'FO': // Far�er Inseln
                $Res = 'FR';
                break;
          case 'FJ': // Fidschi
                $Res = 'FJI';
                break;
          case 'FI': // Finland
                $Res = 'FIN';
                break;
          case 'FR': // Frankreich
                $Res = 'F';
                break;
          case 'GM': // Ganbia
                $Res = 'WAG';
                break;
          case 'GI': // Gibraltar
                $Res = 'GBZ';
                break;
          case 'GD': // Grenada
                $Res = 'WG';
                break;
          case 'GT': // Guatemala
                $Res = 'GCA';
                break;
          case 'GY': // Guyiana
                $Res = 'GUY';
                break;
          case 'HT': // Haiti
                $Res = 'RH';
                break;
          case 'IN': // Indien
                $Res = 'IND';
                break;
          case 'ID': // Indonesien
                $Res = 'RI';
                break;
          case 'IQ': // Irak
                $Res = 'IRQ';
                break;
          case 'IE': // Irland
                $Res = 'IRL';
                break;
          case 'IT': // Italien
                $Res = 'I';
                break;
          case 'JM': // Jamaika
                $Res = 'JA';
                break;
          case 'JP': // Japan
                $Res = 'J';
                break;
          case 'YE': // Jemen
                $Res = 'ADN';
                break;
          case 'JO': // Jordanien
                $Res = 'HKJ';
                break;
          case 'KH': // Kambodscha
                $Res = 'K';
                break;
          case 'CA': // Kanada
                $Res = 'CDN';
                break;
          case 'QA': // Katar
                $Res = 'Q';
                break;
          case 'KE': // Kenia
                $Res = 'EAH';
                break;
          case 'CD': // Kongo
                $Res = 'RCB';
                break;
          case 'KR': // S�dkorea
                $Res = 'ROK';
                break;
          case 'CU': // Kuba
                $Res = 'C';
                break;
          case 'KW': // Kuwait
                $Res = 'KWT';
                break;
          case 'LA': // Laos
                $Res = 'LAO';
                break;
          case 'LB': // Libanon
                $Res = 'RL';
                break;
          case 'LI': // Liechtenstein
                $Res = 'FL';
                break;
          case 'LU': // Luxemburg
                $Res = 'L';
                break;
          case 'MG': // Madagaskar
                $Res = 'RM';
                break;
          case 'MY': // Malaysia
                $Res = 'MAL';
                break;
          case 'ML': // Mali
                $Res = 'RMM';
                break;
          case 'MT': // Malta
                $Res = 'M';
                break;
          case 'MR': // Mauretanien
                $Res = 'RIM';
                break;
          case 'MU': // Mauritius
                $Res = 'MS';
                break;
          case 'MX': // Mexico
                $Res = 'MEX';
                break;
          case 'MZ': // Mosambik
                $Res = 'MOC';
                break;
          case 'MM': // Myanmar (Burma)
                $Res = 'BUR';
                break;
          case 'NA': // Namibia
                $Res = 'NAM';
                break;
          case 'NI': // Nicaragua
                $Res = 'NIC';
               break;
          case 'AN': //Niederl�ndische Antillen
                $Res = 'NA';
                break;
          case 'NE': // Niger
                $Res = 'RN';
                break;
          case 'NG': // Nigeria
                $Res = 'WAN';
                break;
          case 'NO': // Norwegen
                $Res = 'N';
                break;
          case 'AT': // �sterreich
                $Res = 'A';
                break;
          case 'PG': // Papua-Neuginea
                $Res = 'PNG';
                break;
          case 'PH': // Phillippinen
                $Res = 'RP';
                break;
          case 'PT': // Portugal
                $Res = 'P';
                break;
          case 'ZA': // Pretoria
                $Res = 'PR';
                break;
          case 'RW': // Ruanda
                $Res = 'RWA';
                break;
          case 'RU': // Russland
                $Res = 'RUS';
                break;
          case 'ZM': // Sambia
                $Res = 'RNR';
                break;
          case 'SM': // San Marino
                $Res = 'RSM';
                break;
          case 'SE': // Sweden
                $Res = 'S';
                break;
          case 'SC': // Seychellen
                $Res = 'SY';
                break;
          case 'SL': // Sierra Leona
                $Res = 'WAL';
                break;
          case 'SG': // Singapur
                $Res = 'SGP';
                break;
          case 'SI': // Slowenien
                $Res = 'SLO';
                break;
          case 'SO': // Somalia
                $Res = 'SP';
                break;
          case 'ES': // Spanien
                $Res = 'E';
                break;
          case 'LK': // Sri Lanka (Ceylon)
                $Res = 'CL';
                break;
          case 'LC': // St. Lucia
                $Res = 'WL';
                break;
          case 'VC': // St- Vincent
                $Res = 'WV';
                break;
          case 'SR': // Surinam
                $Res = 'SME';
                break;
          case 'SZ': // Sqasiland
                $Res = 'SD';
                break;
          case 'SY': // Syrien
                $Res = 'SYR';
                break;
          case 'TZ': // Tansania
                $Res = 'EAT';
                break;
          case 'TH': // Thailand
                $Res = 'T';
                break;
          case 'UG': // Uganda
                $Res = 'EAU';
                break;
          case 'HU': // Ungarn
                $Res = 'H';
                break;
          case 'US': // USA
                $Res = 'USA';
                break;
          case 'UY': // Uruguay
                $Res = 'ROU';
                break;
          case 'VA': // Vatikansadt
                $Res = 'V';
                break;
          case 'VE': // Venezuela
                $Res = 'YV';
                break;
          case 'ZR': // Zaire
                $Res = 'ZRE';
                break;
          case 'CF': // Zentralafrikanische Republik
                $Res = 'RCA';
                break;
     } // switch
     return $Res;
} // L�nderk�rzel ISO zu ERP

/***************************************************************
 * MySqli Functions
 ***************************************************************/

/**
 * Gibt direkt den Wert einer Zelle aus einem MySqli_Result zurück
 *
 * @param mysqli_result $res
 * @param int $row
 * @param int|mixed $field
 * @return bool|mixed Inhalt der Zelle, falls nicht vorhanden oder ungültig, false
 */
function mysqli_result(\mysqli_result $res, $row=0, $field=0)
{
	$numrows = @mysqli_num_rows($res);
	if (!$numrows || $row >= $numrows || $row < 0) return false;
	mysqli_data_seek($res, $row);
	$resrow = is_numeric($field) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
	if (!isset($resrow[$field])) return false;
	return $resrow[$field];
}

/**
 * Gibt den Wert der ersten Spalte der ersten Zeile für eine Abfrage aus
 *
 * @param mysqli $con
 * @param string $query
 * @return bool|mixed Inhalt der Zelle, falls nicht vorhanden oder ungültig, false
 */
function mysqli_scalar(\mysqli $con, $query="")
{
	$result = @mysqli_query($con, $query);
	return mysqli_result($result);
}

/***************************************************************
 * Encryption Functions
 ***************************************************************/

/**
 * Verschlüsselt den per Parameter übergebenen String mit dem per Parameter
 * übergebenen Schlüssel und vordefiniertem IV
 * 	@param string $string 	'zu verschlüsselnder Kontext'
 * 	@param string $key 		'Passwort, mit dem verschlüsselt wird'
 * 	@return string 			'verschlüsselter String'
 */
function encryptString($string, $key)
{
	if (extension_loaded('openssl'))
	{
		$zeroPaddedString = zeroPadOpenSSL($string);
		$cipher = getCipher($key);
		$encryptedData = openssl_encrypt($zeroPaddedString, $cipher, $key, OPENSSL_ZERO_PADDING, VEKTOR);

		return $encryptedData;
	}
	else
	{
		die (xml_error_ausgeben(DeltraResources::getText("MODULE_OPENSSL"), __FILE__, __FUNCTION__, __LINE__));
	}
}

/**
 * Zero-Padded die Daten für die openssl_encrypt Methode
 * @param string $data	'Zu paddende Daten'
 * @return string
 */
function zeroPadOpenSSL($data)
{
	$pad = 16;
	$pad = $pad - (strlen($data) % $pad);
	$data .= str_repeat("\0", $pad);

	return $data;
}

/**
 * Ermittelt je nach länge des Keys den passenden
 * Cipher, wie mcrypt dies getan hätte
 * @param string $key	'Der Key der zur Verschlüsselung verwendet werden soll'
 * @return string		'OpenSSL AES-CBC-Cipher'
 */
function getCipher($key)
{
	$CIPHER_SMALL = "aes-128-cbc";
	$CIPHER_LARGE = "aes-256-cbc";

	if (strlen($key) > 16)
		return $CIPHER_LARGE;

	return $CIPHER_SMALL;
}

?>