<?PHP
/***************************************************************************\
*
*	Copyright (c) 2013 deltra Buisness Software GmbH & Co. KG
*	http://www.deltra.de
*
\***************************************************************************/

#Benötigte Dateien einbinden:
if (file_exists("inc/requirements.php"))
{
    require_once("inc/requirements.php");
}
else
{
    die("Die Datei <b>requirements.php</b> konnte nicht gefunden werden. <br><br> Bitte &uuml;berpr&uuml;fen Sie, ob diese im Verzeichnis \"inc\" der Webshop-Anbindung
    vorhanden ist.");
}

if (($_REQUEST) && $_SERVER['HTTP_USER_AGENT']==OMX_AGENT)
{
    //2011-03-03,HM,der Webserver soll tatsächlich utf-8 liefern!!!
    header("Content-Type: text/plain; charset=utf-8");
	
    // Shopsystem ermitteln
    $webshop = htmlentities($_REQUEST['shp_system']);
	includeShopSystemIfValid($webshop) or die(xml_error_ausgeben(DeltraResources::getText("SHOPSYSTEM_NOT_FOUND"),__FILE__, __FUNCTION__, __LINE__));

	# Start-Funktion aufrufen
	starten();

	# Beginn vom Verarbeiten der Daten
	$GLOBALS['lastorderID'] = -1;
	$XMLDoc = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>'."\n".'<OrderNotification>'."\n";

    # Query / Array / XML-Doc holen und ausf�hren
	daten_holen(); 
	if ($GLOBALS['datakind'] == 1)
	{
		/* Verbindung zur DB aufbauen */
		@$qRes = mysqli_query($GLOBALS['sql_con'], $GLOBALS['query']);

		if ($qRes) {
			while ($row = mysqli_fetch_assoc($qRes))
			{
				$XMLDoc = PositionEintragen($row, $XMLDoc);
			} // F�r jeden Eintrag in der Tabelle
		} // Wenn Query ausgef�hrt werden konnte
		else {die (xml_error_ausgeben(DeltraResources::getText("SQL_EXECUTION_ERROR") . chr(13) . chr(10) . chr(13) . chr(10) . mysqli_error($GLOBALS['sql_con']) ."",__FILE__, __FUNCTION__, __LINE__));} // Wenn Query nicht ausgef?hrt werden konnte
	} // Ein Query wurde geliefert
	elseif ($GLOBALS['datakind'] == 2)
	{
		foreach ($GLOBALS['ErgebnisArray'] as  $index => $row)
		{
			$XMLDoc = PositionEintragen($row, $XMLDoc);
		} // Für jedes Arrayelement
	} // Ein Ergebnisarray wurde geliefert
	elseif ($GLOBALS['datakind'] == 3)
	{
		$XMLDoc = $GLOBALS['XMLDoc'];

	} // XMLDoc wurde komplett von der Magento- oder Shopware-Schnittstelle geliefert
	if ((($GLOBALS['datakind'] == 1) and (mysqli_num_rows($qRes) > 0)) or (($GLOBALS['datakind'] == 2) and (is_array($GLOBALS['ErgebnisArray']) > 0)))
	{
		$XMLDoc .= '</Bestellvorgang>';
	} // Bestellvorgang nur dann schließen, wenn es auch einen gab.
	
	if (($GLOBALS['datakind'] == 1) or ($GLOBALS['datakind'] == 2))
	{
		$XMLDoc .= '</OrderNotification>';
	}
	
	if (strlen(VERSCHLUESSELN) > 0)
		$XMLDoc = encryptString($XMLDoc, SCHLUESSEL);

	// Daten ausgeben
	echo  $XMLDoc;

	# Ende-Funktion aufrufen
	ende();
} // Gibt es POST Variablen und wurde der Client erkannt
else
{
	# Startseite laden:
	$shp_startseite = $_SERVER['HTTP_HOST'];
	header("Location: http://$shp_startseite");

}// Falsche Zugriffsart
?>