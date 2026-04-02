<?PHP
/***************************************************************************\
*
*	Copyright (c) 2013 deltra Buisness Software GmbH & Co. KG
*	http://www.deltra.de
*
\***************************************************************************/
header("Content-type: text/xml; charset=utf-8");

#Ben�tigte Dateien einf�gen
if (file_exists("inc/requirements.php"))
{
    require_once("inc/requirements.php");
}
else
{
    die("Die Datei <b>requirements.php</b> konnte nicht gefunden werden. <br><br> Bitte &uuml;berpr&uuml;fen Sie, ob diese im Verzeichnis \"inc\" der Webshop-Anbindung
    vorhanden ist.");
}

if($_REQUEST && $_SERVER['HTTP_USER_AGENT']==OMX_AGENT)
{
	// Request-Parameter ermitteln
	$webshop = htmlentities($_REQUEST['shp_system']);
	$id = htmlentities($_REQUEST['id']);
	is_numeric($id) or die(xml_error_ausgeben(DeltraResources::getText("REQUEST_PARAMETER_ID_NOT_VALID"), __FILE__, __FUNCTION__, __LINE__));
	
	// Schnittstelle des Shopsystems einbetten
	includeShopSystemIfValid($webshop) or die(xml_error_ausgeben(DeltraResources::getText("SHOPSYSTEM_NOT_FOUND"),__FILE__, __FUNCTION__, __LINE__));
	
	// start-Funktion in der Schnittstelle zum Shopsystem aufrufen
	starten();
	
	status_aendern($id);
	ende();
} // Gibt es POST Variablen und wurde der Client erkannt
else
{
	# Startseite laden:
	$shp_startseite = $_SERVER['HTTP_HOST'];
	header("Location: $shp_startseite");
} // Falsche Zugriffsart
?>