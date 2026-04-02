<?PHP
/***************************************************************************\
*
*	Copyright (c) 2013 deltra Buisness Software GmbH & Co. KG
*	http://www.deltra.de
*
\***************************************************************************/
header("Content-Type: text/xml; charset=utf-8");

#Ben�tigte Dateien einbinden:
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
	
	# Artikel Webshop -> ERP
	$function = htmlentities($_REQUEST["sync"]);
	if ($function == "shop_to_omx")
	{
		# Start-Funktion aufrufen
		starten();
		# Beginn vom Verarbeiten der Daten:

		$XMLDoc = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>'."\n".'<Artikelimport>'."\n";
		# Query / Array holen und ausf�hren
		if (function_exists('artikeldaten_shop_zu_orgamax'))
		{
			artikeldaten_shop_zu_orgamax();

			if ($GLOBALS['datakind'] == 1)
			{
				/* Verbindung zur DB aufbauen */
				@$qRes = mysqli_query($GLOBALS['sql_con'], $GLOBALS['query']);
				if ($qRes) {
					while ($row = mysqli_fetch_assoc($qRes))
					{
						$XMLDoc = ArtikelEintragen($row, $XMLDoc);
					} // F�r jeden Eintrag in der Tabelle
                    $XMLDoc .= '</Artikelimport>';
				} // Wenn Query ausgef?hrt werden konnte
				else 
				{
					die (xml_error_ausgeben(DeltraResources::getText("SQL_EXECUTION_ERROR") . chr(13) . chr(10) . chr(13) . chr(10) . mysqli_error($GLOBALS['sql_con'])."",__FILE__, __FUNCTION__, __LINE__));
				} // Wenn Query nicht ausgef?hrt werden konnte
			} // Ein Query wurde geliefert
			elseif ($GLOBALS['datakind'] == 2)
			{
				foreach ($GLOBALS['ErgebnisArray'] as  $index => $row)
				{
					$XMLDoc = ArtikelEintragen($row, $XMLDoc);
				} // F?r jedes Arrayelement
                $XMLDoc .= '</Artikelimport>';
			} // Ein Ergebnisarray wurde geliefert
            elseif ($GLOBALS['datakind'] == 3)
            {
                $XMLDoc = $GLOBALS['XMLDoc'];
            }

			if (strlen(VERSCHLUESSELN) > 0)
				$XMLDoc = encryptString($XMLDoc, SCHLUESSEL);

			// Daten ausgeben
			echo  $XMLDoc;

			# Ende-Funktion aufrufen
			ende();
		}
		else
		{
			die (xml_error_ausgeben("Import Funktion f�r Artikeldaten nicht vorhanden",__FILE__, __FUNCTION__, __LINE__));
		}
	}// Artikeldaten aus dem Webshop �bernehmen
	# Artikel ERP -> Webshop
	else if($function == "check_open_orders")
	{
		starten();
		
		if(function_exists('pruefeOffeneBestellungenImShop'))
		{
			pruefeOffeneBestellungenImShop();
		}
		else
		{
			die (xml_error_ausgeben("Import Funktion zum Pr�fen offener Bestellungen ist nicht vorhanden",__FILE__, __FUNCTION__, __LINE__));
		}
		
		ende();
	}
	else if($function == "omx_to_shop")
	{
		
			starten();
			
			if(function_exists('artikeldaten_orgamax_zu_shop'))
			{
				artikeldaten_orgamax_zu_shop();
			}
			else
			{
				die (xml_error_ausgeben("Export Funktion f�r Artikeldaten nicht vorhanden",__FILE__, __FUNCTION__, __LINE__));
			}
			
			ende();
		
	}
	# Artikelliste vom Shop laden
	else if($function == "articlelist_from_shop")
	{
		starten();
		if(function_exists('hole_Artikelliste_fuer_export'))
		{
			hole_Artikelliste_fuer_export();
		}
		else
		{
			die (xml_error_ausgeben("Import Function von Artikellisten nicht vorhanden",__FILE__, __FUNCTION__, __LINE__));
		}
		ende();
	}
	# Lagerbestand -> Webshop
	else if($function == "stockvalue_to_shop")
	{	
		starten();
		
		if(function_exists('setze_lagerbestand_im_shop'))
		{
			setze_lagerbestand_im_shop();
		}
		else
		{
			die (xml_error_ausgeben("Export Funktion f�r Bestellstatus nicht vorhanden",__FILE__, __FUNCTION__, __LINE__));
		}
		
		ende();		
	}
	# Preisliste -> Webshop
	else if($function == "pricelist_to_shop")
	{
		starten();
		
		if(function_exists('setze_Artikelpreise_im_shop'))
		{
			setze_Artikelpreise_im_shop();
		}
		else
		{
			die (xml_error_ausgeben("Export Funktion f�r Preislisten nicht vorhanden",__FILE__, __FUNCTION__, __LINE__));
		}
		
		ende();	
	}
	else if($function == "paging_informationen")
	{
		starten();

		if(function_exists('paging_informationen'))
		{
			paging_informationen();
		}
		else
		{
			die (xml_error_ausgeben("Export Funktion f�r Paging Informationen nicht vorhanden",__FILE__, __FUNCTION__, __LINE__));
		}

		ende();
	}

	// Protokoll ausgeben
	die($XML_Response);
}
?>