<?php
/***************************************************************************\
*
*	Copyright (c) 2013 deltra Buisness Software GmbH & Co. KG
*	http://www.deltra.de
*
\***************************************************************************/

// Benoetigte Dateien einbinden:
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
	// Request-Parameter ermitteln
	$webshop = htmlentities($_REQUEST['shp_system']);
	
	// Schnittstelle des Shopsystems einbetten
	includeShopSystemIfValid($webshop) or die(xml_error_ausgeben(DeltraResources::getText("SHOPSYSTEM_NOT_FOUND"),__FILE__, __FUNCTION__, __LINE__));
	
	// Verschiedene Funktionschecks
	$functionCheckResponse = "";
	$callName = htmlentities($_REQUEST['CallName']);
	if ($callName == "check_function")
	{	
		$functionCheckResponse = function_exists($_REQUEST["function_name"]);
	}
	else if ($callName == "check_version_scf")
	{
		$functionCheckResponse = $GLOBALS['VERSION_SCF'];
	}
	else if ($callName == "check_version")
	{
		$functionCheckResponse = $GLOBALS['VERSION'];
	}

	die ($functionCheckResponse);
}
?>