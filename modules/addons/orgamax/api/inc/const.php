<?php
/*
 *  stellt Text-Ressourcen in Form von Konstanten bereit
 */
class DeltraConst
{
    // Files
	const FILE_NOT_FOUND_CONFIG = "Die Datei config.php konnte auf Ihrem System nicht gefunden werden. Bitte überprüfen Sie ob die Datei auf Ihrem Server vorhanden ist.";
	const FILE_NOT_FOUND_FUNCTIONS = "Die Datei functions.php konnte auf Ihrem System nicht gefunden werden. Bitte überprüfen Sie ob die Datei auf Ihrem Server vorhanden ist.";

	const REQUIREMENTS_NOT_INCLUDED = "Die benötigten Dateien konnten nicht eingebunden werden.";


    // Shopsystems
	const WEBSHOPS_IS_EMPTY = "Es sind keine Webshopsysteme in der Konfiguration hinterlegt. \r\n\r\nBitte aktualisieren Sie die Konfigurationsdatei";
	const WEBSHOPS_IS_NOT_ARRAY = "Die in der Konfiguration registrierten Webshopsysteme sind in einem nicht gültigen Format hinterlegt.";

    const SHOPSYSTEM_NOT_VALID = "Die von Ihnen gewählte Schnittstelle konnte leider nicht gefunden werden. \r\n\r\nBitte überprüfen Sie, ob der von Ihnen gewählte Webshop in der Konfiguration der Webshop-Anbindung hinterlegt ist.";
	const SHOPSYSTEM_NOT_FOUND = "Es trat ein Fehler beim Einlesen der Datensätze auf: \r\nDie benötigten Dateien für Ihr Shopsystem wurden nicht gefunden. \r\n\r\nBitte überprüfen Sie Ihre Webshop-Einstellungen im ERP.";
	const SHOPSYSTEM_CONFIG_NOT_FOUND = "Die Konfigurationsdatei Ihres Webshopsystems konnte nicht gefunden werden. Bitte überprüfen Sie, ob die Datei vorhanden ist und ob der korrekte Pfad angegeben wurde.";


    // Requests
	const REQUEST_PARAMETER_ID_NOT_VALID = "Der übergebene Parameter \"id\" besitzt keinen gültigen Wert.";


    // Setup
    const SETUP_SHOPSYSTEM_NOT_VALID = "Das von Ihnen angegebene Shopsystem wird leider nicht unterstützt. Bitte wenden Sie sich an den Support.";

    // SQL Fehler
	const SQL_EXECUTION_ERROR = "Beim Versuch, das Query auszuführen, traten Fehler auf.";

	// Modules
	const MODULE_OPENSSL = "Das Modul \"OpenSSL\" konnte nicht geladen werden.";
    
    /**
     * Zahlungsarten / Lieferarten
     */
    // Konstanten f?r osCommerce / xt:Commerce / Gambio Zahlungsarten
    const ORGAMAX_OS_ZAHLUNGSART_CC = "Kreditkarte";
    const ORGAMAX_OS_ZAHLUNGSART_BANKTRANSFER = "Lastschrift";
    const ORGAMAX_OS_ZAHLUNGSART_COD = "Nachnahme";
    const ORGAMAX_OS_ZAHLUNGSART_CASH = "Barzahlung";
    const ORGAMAX_OS_ZAHLUNGSART_EUSTANDARDTRANSFER = "EU Bankeinzug/Lastschrift";
    const ORGAMAX_OS_ZAHLUNGSART_SEPA = "SEPA Bankeinzug/Lastschrift";
    const ORGAMAX_OS_ZAHLUNGSART_INVOICE = "Rechnung";
    const ORGAMAX_OS_ZAHLUNGSART_IPAYMENT = "iPayment";
    const ORGAMAX_OS_ZAHLUNGSART_IPAYMENTELV = "iLastschrift";
    const ORGAMAX_OS_ZAHLUNGSART_LUUPWS = "LuuPay";
    const ORGAMAX_OS_ZAHLUNGSART_MONEYBOOKERS = "Moneybookers.com";
    const ORGAMAX_OS_ZAHLUNGSART_MONEYORDER = "Scheck/Vorkasse";
    const ORGAMAX_OS_ZAHLUNGSART_PAYPAL = "PayPal";
	const ORGAMAX_OS_ZAHLUNGSART_PAYPALNG = "PayPal";
	const ORGAMAX_OS_ZAHLUNGSART_PAYPAL3 = "PayPal";
    const ORGAMAX_OS_ZAHLUNGSART_USO_GIROPAY_MODUL = "Giropay";
    const ORGAMAX_OS_ZAHLUNGSART_USO_GP_MODUL = "Global Paycard";
    const ORGAMAX_OS_ZAHLUNGSART_USO_KREDITKARTE_MODUL = "Keditkarte International";
    const ORGAMAX_OS_ZAHLUNGSART_USO_LASTSCHRIFT_AT_MODUL = "Lastschrift Österreich";
    const ORGAMAX_OS_ZAHLUNGSART_USO_LASTSCHRIFT_DE_MODUL = "Lastschrift Deutschland";
    const ORGAMAX_OS_ZAHLUNGSART_USO_VORKASSE_MODUL = "Vorkasse";
    const ORGAMAX_OS_ZAHLUNGSART_WORLDPAY_MODUL = "Secure Credit Card Payment";
    const ORGAMAX_OS_ZAHLUNGSART_PN_SOFORTUEBERWEISUNG = "sofortueberweisung.de";
    // Konstanten f?r osCommerce / xt:Commerce / Gambio Lieferarten
    const ORGAMAX_OS_LIEFERART_DP = "Deutsche Post";
    const ORGAMAX_OS_LIEFERART_AP = "Österreichische Post AG";
    const ORGAMAX_OS_LIEFERART_CHP = "Schweizerische Post";
    const ORGAMAX_OS_LIEFERART_CHRONOPOST = "Chronopost Zone Rates";
    const ORGAMAX_OS_LIEFERART_DHL = "DHL";
    const ORGAMAX_OS_LIEFERART_DPD = "DPD";
    const ORGAMAX_OS_LIEFERART_FEDEXEU = "FedEx Express Europa";
    const ORGAMAX_OS_LIEFERART_FLAT = "Pauschale Versandkosten";
    const ORGAMAX_OS_LIEFERART_FREEAMOUNT = "Versandkostenfrei";
    const ORGAMAX_OS_LIEFERART_FREE = "Versandkostenfrei";
    const ORGAMAX_OS_LIEFERART_ITEM = "Versandkosten pro St?ck";
    const ORGAMAX_OS_LIEFERART_SELFPICKUP = "Selbstabholung";
    const ORGAMAX_OS_LIEFERART_TABLE = "Versandkosten nach Preis/Gewicht";
    const ORGAMAX_OS_LIEFERART_UPS = "United Parcel Service Standard";
    const ORGAMAX_OS_LIEFERART_UPSE = "United Parcel Service Express";
    const ORGAMAX_OS_LIEFERART_ZONES = "Unversicherter Versand";
    const ORGAMAX_OS_LIEFERART_ZONESE = "Versicherter Versand";

    // Konstanten f?r virtueMart Lieferart
    const ORGAMAX_VM_LIEFERART_STD = "Flex";
    const ORGAMAX_VM_LIEFERART_DPD = "DPD";
    const ORGAMAX_VM_LIEFERART_DP = "Deutsche Post";
    const ORGAMAX_VM_LIEFERART_FDXFIP = "FedEx International Priority";
    const ORGAMAX_VM_LIEFERART_UPSUWE = "UPS WorldWide Express";
    const ORGAMAX_VM_LIEFERART_DHLDPE = "DHL Worldwide Priority Express";
    const ORGAMAX_VM_LIEFERART_UPSUWP = "UPS WorldWide Express Plus";
    const ORGAMAX_VM_LIEFERART_FedEx = "FedEx";
    const ORGAMAX_VM_LIEFERART_AP = "Österreichische Post AG";
    const ORGAMAX_VM_LIEFERART_DHL = "DHL";
    const ORGAMAX_VM_LIEFERART_UPSUSD = "UPS WorldWide Saver";
    const ORGAMAX_VM_LIEFERART_USPS = "United States Postal Service";
    const ORGAMAX_VM_LIEFERART_UPS = "United Parcel Service Standard";

    //Konsten f?r virtueMart Zahlungsart
    const ORGAMAX_ZAHLUNGSART_PO = "Bestellung";
    const ORGAMAX_ZAHLUNGSART_BL = "Bankeinzug/Lastschrift";
    const ORGAMAX_ZAHLUNGSART_COD = "Nachnahme";
    const ORGAMAX_ZAHLUNGSART_AN = "Kreditkarte";
    const ORGAMAX_ZAHLUNGSART_PP = "PayPal";
    const ORGAMAX_ZAHLUNGSART_PM = "PayMate";
    const ORGAMAX_ZAHLUNGSART_WP = "WorldPay";
    const ORGAMAX_ZAHLUNGSART_2CO = "2Checkout";
    const ORGAMAX_ZAHLUNGSART_NOCHEX = "NoChex";
    const ORGAMAX_ZAHLUNGSART_EWAY = "eWay";
    const ORGAMAX_ZAHLUNSART_PN = "Pay-Me-Now";
    const ORGAMAX_ZAHLUNGSART_ECK = "eCheck.net";
    const ORGAMAX_ZAHLUNGSART_IK = "iKobo";
    const ORGAMAX_ZAHLUNGSART_IT = "iTransact";
    const ORGAMAX_ZAHLUNGSART_PFP = "Verisign PayFlow Pro";
    const ORGAMAX_ZAHLUNGSART_EPAY = "Dankort/PBS via ePay";
    const ORGAMAX_ZAHLUNGSART_PSB = "PaySbuy";
    /**
     * Zahlungsarten / Lieferarten Ende
     */

    /**
     * Export Status
     */
    const EXPORT_SUCCESS = "EXPORT_SUCCESS";
    const EXPORT_ERROR = "EXPORT_ERROR";
    const EXPORT_ABORT = "EXPORT_ABORT";
    const EXPORT_ROLLBACK = "EXPORT_ROLLBACK";
    /**
     * Export Status Ende
     */
}

?>