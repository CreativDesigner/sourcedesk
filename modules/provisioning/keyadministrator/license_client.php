<?php
/**
 * Client fuer die XML-RPC Schittstelle des Keyadministrators
 * API-Version 1.1
 *
 * Setzt XML-RPC for PHP voraus
 * http://phpxmlrpc.sourceforge.net/
 *
 * @author Kristof Nidzwetzki
 * @version 1.1
 *
 */

class LicenseException extends Exception
{}

class LicenseClient
{

    /**
     * Benutzername
     *
     * @var string $username
     */
    protected $username;
    /**
     * Passwort
     *
     * @var string $password
     */
    protected $password;
    /**
     * Client
     *
     * @var string $client
     */
    protected $client;

    /**
     * Debug (Optional)
     *
     * @var int $debug
     */
    protected $debug = 0;

    /**
     * Funktion __construct
     *
     * @access public
     * @param string $username Benutzername
     * @param string $password Passwort
     * @param string $host Hostname
     * @param string $uri Service-URI
     * @param string $port Port
     *
     */
    public function __construct($username, $password, $host, $uri, $port)
    {
        $this->username = $username;
        $this->password = $password;

        if ($port == 443) {
            $this->client = new xmlrpc_client($uri, $host, $port, 'https');
        } else {
            $this->client = new xmlrpc_client($uri, $host, $port);
        }

        $this->client->setSSLVerifyPeer(0);
        $this->client->setSSLVerifyHost(0);
        $this->client->setDebug(0);
    }

    /**
     * Funktion Ping
     *
     * Verbindungstest
     *
     * @access public
     * @throws LicenseException
     * @param string $content payload
     * @return string payload
     */
    public function ping($content)
    {
        $xmlrpc_msg = new xmlrpcmsg('ping', array(new xmlrpcval($content, 'string')));
        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion testConnection
     *
     * Verbindungstest
     *
     * @access public
     */
    public function testConnection()
    {
        $content = "abcde";
        $result = $this->ping($content);
        return $result == $content;
    }

    /**
     * Funktion Listlicenses
     *
     * Listet alle vorhandenen Lizenzen eines Accounts auf. Optional kann als Parameter der Lizenztyp
     * mitgegeben werden, wodurch nur Lizenzen eines bestimmten Typs angezeigt werden.
     *
     * @access public
     * @throws LicenseException
     * @param string $type Angabe des Lizenztyps (Optional)
     * @param boolean $subclients Lizenzen von Subclients anzeigen
     * @param boolean $archive Nur gekuendigte Lizenzen anzeigen
     * @return Array mit Informationen ueber die Lizenz. @see API-DOC
     */
    public function listLicenses($type = '', $subclients = false, $archive = false)
    {
        $xmlrpc_msg = new xmlrpcmsg('list', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($type, 'string'),
            new xmlrpcval($subclients, 'boolean'),
            new xmlrpcval($archive, 'boolean'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion viewLicense
     *
     * Zeigt alle Informationen einer bestimmten Lizenz an
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @return Ein Array mit allen Informationen ueber die Lizenz. @see API-DOC
     */
    public function viewLicense($license)
    {
        $xmlrpc_msg = new xmlrpcmsg('view', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Zeigt alle Features einer bestimmten Lizenz an
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @return Ein Array mit allen Lizenz-Informationen ueber die Lizenz. @see API-DOC
     */
    public function viewFeatures($license)
    {
        $xmlrpc_msg = new xmlrpcmsg('viewFeatures', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Download eines Lizenz-Files
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @return Lizenz-File sofern fuer diesen Lizenz-Typ angeboten @see API-DOC
     */
    public function retrieveLicense($license)
    {
        $xmlrpc_msg = new xmlrpcmsg('retrieveLicense', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Zusaetliche Features zu einer bestimmten Lizenz anzeigen
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @return Zusaetzliche Features (array KEY -> Desc) sofern fuer diesen Lizenz-Typ
     * angeboten @see API-DOC
     */
    public function getAdditionalFeatures($license)
    {
        $xmlrpc_msg = new xmlrpcmsg('getAdditionalFeatures', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Moegliche Upgrades zu einer bestimmten Lizenz anzeigen
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @return Lizenz-Upgrades (array KEY -> Desc) sofern fuer diesen Lizenz-Typ angeboten @see API-DOC
     */
    public function getAvailableUpgrades($license)
    {
        $xmlrpc_msg = new xmlrpcmsg('getAvailableUpgrades', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Upgrade einer Lizenz durchfuehren
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @param string $upgrd Upgrade oder neues Feature
     * @param string $billing Billing
     *
     * @return Result Info als String @see API-DOC
     */
    public function upgradeLicense($license, $upgrade, $billing)
    {
        $xmlrpc_msg = new xmlrpcmsg('upgradeLicense', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
            new xmlrpcval($upgrade, 'string'),
            new xmlrpcval($billing, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion resetLicense
     *
     * Setzt eine Lizenz zurueck
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @return Ein Array mit alle Informationen ueber die Lizenz. @see API-DOC
     */
    public function resetLicense($license)
    {
        $xmlrpc_msg = new xmlrpcmsg('reset', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion transferLicense
     *
     * Eine Lizenz auf einen anderen Account uebertragen
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @param string $toAccount neuer Account
     * @return Ein Array mit alle Informationen ueber die Lizenz. @see API-DOC
     */
    public function transferLicense($license, $newAccount)
    {
        $xmlrpc_msg = new xmlrpcmsg('transferLicense', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
            new xmlrpcval($newAccount, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Listet alle Lizenzen auf, die mit diesem Account bestellt werden koennen
     *
     * @access public
     * @throws LicenseException
     * @return Ein Array mit Informationen ueber die bestellbaren Lizenzen. @see API-DOC
     */
    public function getAvailableLicenses()
    {
        $xmlrpc_msg = new xmlrpcmsg('getAvailableLicenses', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion editLicense
     *
     * Editiert einen bestehenden Kommentar oder fuegt einen einer Lizenz hinzu.
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @param string $comment Kommentar
     * @return Ein Array mit alle Informationen ueber die Lizenz. @see API-DOC
     */
    public function editLicense($license, $comment)
    {
        $xmlrpc_msg = new xmlrpcmsg('edit', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
            new xmlrpcval($comment, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion deleteLicense
     *
     * Kuendigt eine bestehende Lizenz zu einem bestimmten Datum. Durch setzen des Datum
     * '0000-00-00' wird die Kuendigung aufgehoben
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @param string $date Datum wann die Lizenz geloescht werden soll
     * @return Ein Array mit alle Informationen ueber die Lizenz. @see API-DOC
     */
    public function deleteLicense($license, $date)
    {
        $xmlrpc_msg = new xmlrpcmsg('delete', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
            new xmlrpcval($date, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion orderLicense
     *
     * Bestellen eine neuen Lizenz
     *
     * @access public
     * @throws LicenseException
     * @param string $type Lizenztyp
     * @return Ein Array mit alle Informationen ueber die Lizenz. @see API-DOC
     */
    public function orderLicense($type)
    {
        $xmlrpc_msg = new xmlrpcmsg('new', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($type, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion getStatistics
     *
     * Erstellt eine Statistik ueber die genutzten Lizenzen
     *
     * @access public
     * @throws LicenseException
     * @param string $subclient Account fuer den die Lizenz erstellt werden soll
     * @param boolean $includeSubclients subclients mit in die Statistik einbeziehen
     * @return Ein Array der angeforderten Statistik. @see API-DOC
     */
    public function getStatistics($subclient, $includeSubclients)
    {
        $xmlrpc_msg = new xmlrpcmsg('statistics', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($subclient, 'string'),
            new xmlrpcval($includeSubclients, 'boolean'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion setBindState
     *
     * Setzt den Bind-Status für eine Plesk-Lizenz
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @param string $ip IP-Adresse
     * @param bool $bind Bind-Status
     * @return Status. @see API-DOC
     */
    public function setBindState($license, $ip, $bind = true)
    {
        $xmlrpc_msg = new xmlrpcmsg('setBindState', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
            new xmlrpcval($ip, 'string'),
            new xmlrpcval($bind, 'boolean'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion getBindState
     *
     * Ruft den Bind-Status für eine Plesk-Lizenz ab
     *
     * @access public
     * @throws LicenseException
     * @param string $license Lizenznummer
     * @return PleskBindingResult. @see API-DOC
     */
    public function getBindState($license)
    {
        $xmlrpc_msg = new xmlrpcmsg('getBindState', array(
            new xmlrpcval($this->username, 'string'),
            new xmlrpcval($this->password, 'string'),
            new xmlrpcval($license, 'string'),
        ));

        $xml_result = $this->dispatch_xmlrpc($xmlrpc_msg);

        if ($this->isFault($xml_result)) {
            throw new LicenseException($xml_result->faultString(), $xml_result->faultCode());
        }

        return $this->decodeResult($xml_result);
    }

    /**
     * Funktion dispatch_xmlrpc
     *
     * Diese Funktion dient der Ausfuehrung der XML-Anfrage.
     *
     * @access private
     * @param string $xmlrpc_msg
     * @return xmlrpcresp - xml response
     */
    private function dispatch_xmlrpc($xmlrpc_msg)
    {
        $xmlrpc_resp = $this->client->send($xmlrpc_msg);

        if ($this->debug) {
            print_r($xmlrpc_resp);
            print "<br><br>";
        }

        if ($xmlrpc_resp == false && $die_on_error) {
            die('Unable to send request');
        }

        return $xmlrpc_resp;
    }

    /**
     * Funktion isFault
     *
     * Testen ob es bei der Ausfuehrung zu einem Fehler gekommen ist
     *
     * @access private
     * @param xmlrpcresp $xml_result
     * @param string $print_error boolean - Ausgabe der Fehlermeldung - Optional
     * @return boolean - Fehler aufgetreten oder nicht
     */
    private function isFault($xml_result, $print_error = false)
    {
        if ($xml_result->faultCode()) {

            if ($print_error) {
                print('Got fault code: '+$xml_result->faultCode() . ' ' . $xml_result->faultString());
            }

            return true;
        }
        return false;
    }

    /**
     * Funktion decodeResulte
     *
     * @access private
     * @param xmlrpcresp $xml_result
     * @return Antwort der XML-RPC Funktion
     *
     */
    private function decodeResult($xml_result)
    {
        return php_xmlrpc_decode($xml_result->value());
    }
}
