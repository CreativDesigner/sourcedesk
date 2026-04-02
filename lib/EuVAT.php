<?php

class EuVATSoapClient extends SoapClient
{
    public function __construct($wsdl, $options = null)
    {
        if (isset($options['connection_timeout'])) {
            $s_options = array(
                'http' => array(
                    'timeout' => $options['connection_timeout'],
                ),
            );
            $options['stream_context'] = stream_context_create($s_options);
        }
        parent::__construct($wsdl, $options);
    }
}

class EuVAT
{
    private $info;
    private $available = true;

    public function __construct($id)
    {
        try {
            $client = new EuVATSoapClient("https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl", array("connection_timeout" => "3"));

            if ($client) {
                $params = array("countryCode" => substr($id, 0, 2), "vatNumber" => substr($id, 2));
                $this->info = $client->checkVat($params);
            }
        } catch (SoapFault $e) {
            $this->available = false;
            $this->info = (object) ["countryCode" => substr($id, 0, 2)];
        }
    }

    public function isValid()
    {
        return !$this->available || (isset($this->info->valid) && $this->info->valid === true) ? true : false;
    }

    public function getCompany()
    {
        return isset($this->info->name) ? $this->info->name : null;
    }

    public function getAddress()
    {
        return isset($this->info->address) ? $this->info->address : null;
    }

    public function getCountry()
    {
        return isset($this->info->countryCode) ? $this->info->countryCode : null;
    }
}
