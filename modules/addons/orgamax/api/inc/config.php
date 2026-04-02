<?php
require __DIR__ . "/../sourcedesk.php";

/***************************************************************************\
*
*	Copyright (c) 2013 deltra Buisness Software GmbH & Co. KG
*	http://www.deltra.de
*
\***************************************************************************/

/**
 * Sicherheitskonstanten
 */
define("SCHLUESSEL", ""); // #change_KEY
define("VERSCHLUESSELN", ""); // #change_CRYPT
define('VEKTOR', 'jxpPjWLPIlLXWxrc');
define("OMX_AGENT", $key); // #change_AGENT
define("SHOW_INI_WARNINGS", false); // zum Abschalten der Warnungen:    define("SHOW_INI_WARNINGS", false);
/**
 * Sicherheitskonstanten Ende
 */

// Versionsnummer der Anbindung
$GLOBALS['VERSION'] = '19.05.31';

// Im Setup ausgewählter Shop
$GLOBALS["SETUP_SHOP"] = "individuell"; // #change_SETUP_SHOP

/**
 * Shop Config Referenzen
 */
//Pfad zur magento API
define('REF_MAGE', '../app/Mage.php');
// Pfad zur osCommerce / xt:Commerce / Gambio Config
define('ORGAMAX_OS_CONFIG', dirname(__FILE__) . '/../../includes/configure.php');
//Pfad zur Veyton Config
define('VEYTON_CONFIG', dirname(__FILE__) . '/../../conf/config.php');
//Pfad zur virtueMart Config
define('ORGAMAX_VIRTUEMART_CONFIG', '');	// #vmConfig
//PrestaShop Config Pfad
define('ORGAMAX_PS_CONFIG', dirname(__FILE__) . '/../../config/config.inc.php');
/**
 * Shop Config Referenzen Ende
 */

/**
 * Allgemeine Einstellungen
 */
	//Wie soll die Artikelnummer aus dem Shop behandelt werden:
	// 1 = Die Artikelnummer im Webshop ist identisch mit der Artikelnummer im ERP
	// 2 = Die Artikelnummer im Webshop unterscheidet sich vn der Artikelnummer im ERP
	$GLOBALS['artno_handling'] = 1;

	// Variabel für Artikelimport
	$GLOBALS['articles_language'] = 2; // #change_ARTICLES_LANGUAGE

	/**
	 * Diese Option steuert ob Varianten mit berücksichtigt werden sollen
	 * 0 = aus
	 * 1 = an
	 *
	 * Gambio GX3:
	 * 0 = aus
	 * 1 = Attribute (alt)
	 * 2 = Eigenschaften (Generierte Variantennummer mit ID)
	 * 3 = Eigenschaften (Eigene Variantennummer / Eigenverantwortung)
	 */
	$GLOBALS['attributes_import'] = 0; // #change_ATTRIBUTES_IMPORT

	/**
	 * Kundenkommentare zu Bestellungen importieren
	 *
	 * unterstützte Systeme: Shopware 3.5.6
	 * Wichtig: Aus technischen Gründen können vorerst nur Kommentare mit einer Länge von 255 Zeichen importiert werden.
	 */
	$GLOBALS['customercomment_import'] = 0;

	/**
	 * Status in den Bestellungen geändert werden sollen
	 * Prestashop: 3
	 */
	$GLOBALS["aendern_zu"] = 0;		// #change_STATUS
	
	/**
	 * Länder-ISO-Code von dem Land,
	 * von dem die Steuern genutzt werden sollen
	 */
	$GLOBALS['ISO_CODE'] = 'DE';

/**
 * Allgemeine Einstellungen Ende
 */

/**
 * Gambio Tax-Zone
 *
 * Die ID der Steuerzone von der die Steuersätze
 * für den Im- und Export genutzt werden sollen
 */
$GLOBALS['TAX_ZONE_ID'] = 5;
 
// Konstanten für veyton: Artikelsprache
$GLOBALS['ARTIKELSPRACHE_VEYTON'] = 'de';

// Konstante für TablePrefix (veyton, presta)
define ('DB_PREFIX','xt'); // #change_DB_PREFIX

//magento Shop
/**
 * pending = 0
 * processing = 1
 * complete = 2
 * closed = 3
 * canceled = 4
 * holded = 5
 */
//VEYTON Shop
/**
 * veyton besitzt 3 Basis-Orderstatus
 * Offen 		  		  (Open) 	-> 16
 * In Bearbeitung 		  (Pending) -> 17
 * Zahlung erhalten		  () 		-> 23
 * Zahlung storniert	  ()		-> 32
 * Versandt				  (Shipped) -> 33
 * Storniert              ()        -> 34
 */
//alle anderen Shops
$GLOBALS["DEFAULT_ORDERS_STATUS"] = 1; // #change_DEFAULT_ORDERS_STATUS

/**
 * Einstellung nur für Prestashop notwendig, da jede Zahlungsmethode standardmäßig einen eigenen Bestellstatus hat
 * Hier alle Stati eintragen, die abgeholt werden sollen.
 * Nummern können eingesehen werden in: 		'Dashboard > Bestellungen > Status'
 */
$GLOBALS["DEFAULT_ORDERS_STATUS_PRESTASHOP"] = array(1, 2, 4, 8, 9, 10, 11, 12);

/**
 * Zahlungsarten / Lieferarten
 */
// Konstanten f?r osCommerce / xt:Commerce / Gambio Zahlungsarten
define('ORGAMAX_OS_ZAHLUNGSART_CC', 'Kreditkarte');
define('ORGAMAX_OS_ZAHLUNGSART_BANKTRANSFER', 'Bankeinzug/Lastschrift');
define('ORGAMAX_OS_ZAHLUNGSART_COD', 'Nachnahme');
define('ORGAMAX_OS_ZAHLUNGSART_CASH', 'Barzahlung');
define('ORGAMAX_OS_ZAHLUNGSART_EUSTANDARDTRANSFER', 'EU Bankeinzug/Lastschrift');
define('ORGAMAX_OS_ZAHLUNGSART_INVOICE', 'Rechnung');
define('ORGAMAX_OS_ZAHLUNGSART_IPAYMENT', 'iPayment');
define('ORGAMAX_OS_ZAHLUNGSART_IPAYMENTELV', 'iLastschrift');
define('ORGAMAX_OS_ZAHLUNGSART_LUUPWS', 'LuuPay');
define('ORGAMAX_OS_ZAHLUNGSART_MONEYBOOKERS', 'Moneybookers.com');
define('ORGAMAX_OS_ZAHLUNGSART_MONEYORDER', 'Scheck/Vorkasse');
define('ORGAMAX_OS_ZAHLUNGSART_PAYPAL', 'PayPal');
define('ORGAMAX_OS_ZAHLUNGSART_USO_GIROPAY_MODUL', 'Giropay');
define('ORGAMAX_OS_ZAHLUNGSART_USO_GP_MODUL', 'Global Paycard');
define('ORGAMAX_OS_ZAHLUNGSART_USO_KREDITKARTE_MODUL', 'Keditkarte International');
define('ORGAMAX_OS_ZAHLUNGSART_USO_LASTSCHRIFT_AT_MODUL', 'Lastschrift ?sterreich');
define('ORGAMAX_OS_ZAHLUNGSART_USO_LASTSCHRIFT_DE_MODUL', 'Lastschrift Deutschland');
define('ORGAMAX_OS_ZAHLUNGSART_USO_VORKASSE_MODUL', 'Vorkasse');
define('ORGAMAX_OS_ZAHLUNGSART_WORLDPAY_MODUL', 'Secure Credit Card Payment');
define('ORGAMAX_OS_ZAHLUNGSART_PN_SOFORTUEBERWEISUNG', 'sofortueberweisung.de');
// Konstanten f?r osCommerce / xt:Commerce / Gambio Lieferarten
define('ORGAMAX_OS_LIEFERART_DP', 'Deutsche Post');
define('ORGAMAX_OS_LIEFERART_AP', '�sterreichische Post AG');
define('ORGAMAX_OS_LIEFERART_CHP', 'Schweizerische Post');
define('ORGAMAX_OS_LIEFERART_CHRONOPOST', 'Chronopost Zone Rates');
define('ORGAMAX_OS_LIEFERART_DHL', 'DHL');
define('ORGAMAX_OS_LIEFERART_DPD', 'DPD');
define('ORGAMAX_OS_LIEFERART_FEDEXEU', 'FedEx Express Europa');
define('ORGAMAX_OS_LIEFERART_FLAT', 'Pauschale Versandkosten');
define('ORGAMAX_OS_LIEFERART_FREEAMOUNT', 'Versandkostenfrei');
define('ORGAMAX_OS_LIEFERART_FREE', 'Versandkostenfrei');
define('ORGAMAX_OS_LIEFERART_ITEM', 'Versandkosten pro St?ck');
define('ORGAMAX_OS_LIEFERART_SELFPICKUP', 'Selbstabholung');
define('ORGAMAX_OS_LIEFERART_TABLE', 'Versandkosten nach Preis/Gewicht');
define('ORGAMAX_OS_LIEFERART_UPS', 'United Parcel Service Standard');
define('ORGAMAX_OS_LIEFERART_UPSE', 'United Parcel Service Express');
define('ORGAMAX_OS_LIEFERART_ZONES', 'Unversicherter Versand');
define('ORGAMAX_OS_LIEFERART_ZONESE', 'Versicherter Versand');

// Konstanten f?r virtueMart Lieferart
define('ORGAMAX_VM_LIEFERART_STD', 'Flex');
define('ORGAMAX_VM_LIEFERART_DPD', 'DPD');
define('ORGAMAX_VM_LIEFERART_DP', 'Deutsche Post');
define('ORGAMAX_VM_LIEFERART_FDXFIP', 'FedEx International Priority');
define('ORGAMAX_VM_LIEFERART_UPSUWE', 'UPS WorldWide Express');
define('ORGAMAX_VM_LIEFERART_DHLDPE', 'DHL Worldwide Priority Express');
define('ORGAMAX_VM_LIEFERART_UPSUWP', 'UPS WorldWide Express Plus');
define('ORGAMAX_VM_LIEFERART_FedEx', 'FedEx');
define('ORGAMAX_VM_LIEFERART_AP', '�sterreichische Post AG');
define('ORGAMAX_VM_LIEFERART_DHL', 'DHL');
define('ORGAMAX_VM_LIEFERART_UPSUSD', 'UPS WorldWide Saver');
define('ORGAMAX_VM_LIEFERART_USPS', 'United States Postal Service');
define('ORGAMAX_VM_LIEFERART_UPS', 'United Parcel Service Standard');

//Konsten f�r virtueMart Zahlungsart
define('ORGAMAX_ZAHLUNGSART_PO', 'Bestellung');
define('ORGAMAX_ZAHLUNGSART_BL', 'Bankeinzug/Lastschrift');
define('ORGAMAX_ZAHLUNGSART_COD', 'Nachnahme');
define('ORGAMAX_ZAHLUNGSART_AN', 'Kreditkarte');
define('ORGAMAX_ZAHLUNGSART_PP', 'PayPal');
define('ORGAMAX_ZAHLUNGSART_PM', 'PayMate');
define('ORGAMAX_ZAHLUNGSART_WP', 'WorldPay');
define('ORGAMAX_ZAHLUNGSART_2CO', '2Checkout');
define('ORGAMAX_ZAHLUNGSART_NOCHEX', 'NoChex');
define('ORGAMAX_ZAHLUNGSART_EWAY', 'eWay');
define('ORGAMAX_ZAHLUNSART_PN', 'Pay-Me-Now');
define('ORGAMAX_ZAHLUNGSART_ECK', 'eCheck.net');
define('ORGAMAX_ZAHLUNGSART_IK', 'iKobo');
define('ORGAMAX_ZAHLUNGSART_IT','iTransact');
define('ORGAMAX_ZAHLUNGSART_PFP', 'Verisign PayFlow Pro');
define('ORGAMAX_ZAHLUNGSART_EPAY', 'Dankort/PBS via ePay');
define('ORGAMAX_ZAHLUNGSART_PSB', 'PaySbuy');
/**
 * Zahlungsarten / Lieferarten Ende
 */

/**
 * Export Status
 */
define('EXPORT_SUCCESS','EXPORT_SUCCESS');
define('EXPORT_ERROR','EXPORT_ERROR');
define('EXPORT_ABORT','EXPORT_ABORT');
define('EXPORT_ROLLBACK','EXPORT_ROLLBACK');
/**
 * Export Status Ende
 */

/**
 * Fehlermeldungen
 */
$GLOBALS['err_msg']['shop_cfg'] = "Es trat ein Fehler beim Einlesen der Datensätze auf:". chr(13) . chr(10) . chr(13) . chr(10) ."Die Konfigurationsdatei vom Webshop konnte nicht geladen werden.". chr(13) . chr(10) . chr(13) . chr(10) ."Eine Verbindung zu Datenbank schlug daher fehl.";
$GLOBALS['err_msg']['shop_file'] = "Es trat ein Fehler beim Einlesen der Datensätze auf:". chr(13) . chr(10) . chr(13) . chr(10)."Die benötigten Dateien für Ihr Shopsystem wurden nicht gefunden.". chr(13) . chr(10) . chr(13) . chr(10)."Bitte überprüfen Sie Ihre Webshopeinstellungen im ERP.";
$GLOBALS['err_msg']['datatype_1_assoc'] = "Es trat ein Fehler beim Einlesen der Datensätze auf:". chr(13) . chr(10) . chr(13) . chr(10)."Die angegebene Query liefert keine gültigen Ergebnisse.". chr(13) . chr(10) . chr(13) . chr(10)."Bitte korrigieren Sie die Query oder kontaktieren Sie den technischen Support.";
$GLOBALS['err_msg']['datatype_2_array'] = "Es trat ein Fehler beim Einlesen der Datensätze auf:". chr(13) . chr(10) . chr(13) . chr(10)."Das angegebene Ergebnisarray liefert keine Egebnisse.". chr(13) . chr(10) . chr(13) . chr(10)."Bitte korrigieren Sie die Datenquelle oder kontaktieren Sie den technischen Support.";
$GLOBALS['err_msg']['mcrypt'] = "Es trat ein Fehler beim Einlesen der Datensätze auf:". chr(13) . chr(10) . chr(13) . chr(10)."Die Verschlüsselungsbibliothek 'mcrypt' wird von PHP nicht eingebunden.". chr(13) . chr(10)."Eine Verschlüsselung ist nur möglich, wenn die mcrypt Erweiterung in PHP aktiviert ist.";

?>