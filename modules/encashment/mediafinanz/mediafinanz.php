<?php

class Mediafinanz extends Encashment
{
    protected $short = "mediafinanz";
    protected $name = "Mediafinanz";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "mid" => array("type" => "text", "name" => $this->getLang("mid")),
            "mkey" => array("type" => "password", "name" => $this->getLang("mkey")),
            "hint" => array("name" => $this->getLang("hint"), "help" => $this->getLang("HINTH"), "type" => "hint"),
        );
    }

    public function newClaim($debtor, $claim)
    {
        $auth = array(
            "clientId" => $this->options->mid,
            "licenceKey" => md5('c9cdbcb1e6399106939bcbc2a7ca9215' . $this->options->mkey),
            "sandbox" => false,
        );

        $options = array(
            "trace" => 1,
            "compression" => true,
            "exceptions" => true,
        );

        try {
            $soap = new SoapClient('https://soap.mediafinanz.de/encashment204.wsdl', $options);

            $debt = array(
                "address" => "@",
                "firstname" => $debtor->firstname,
                "lastname" => $debtor->lastname,
                "company" => $debtor->company,
                "co" => "",
                "street" => $debtor->street . " " . $debtor->street_number,
                "postcode" => $debtor->postcode,
                "city" => $debtor->city,
                "country" => $debtor->country_alpha2,
                "telephone1" => $debtor->telephone,
                "telephone2" => "",
                "fax" => "",
                "email" => $debtor->mail,
            );

            if (!empty($debtor->ID)) {
                $debt['id'] = $debtor->ID;
            }

            if (strtotime($debtor->birthday) !== false && $debtor->birthday != "0000-00-00") {
                $debt['dateOfBirth'] = $debtor->birthday;
            }

            $claim = array(
                "invoiceid" => $claim->invoice,
                "type" => "3",
                "reason" => $claim->reason,
                "originalValue" => $claim->amount,
                "overdueFees" => $claim->latefee,
                "dateOfOrigin" => $claim->date,
                "dateOfLastReminder" => $claim->lastnotice,
                "note" => $claim->note,
            );

            foreach ($claim as $key => &$value) {
                $value = utf8_encode($value);
            }

            foreach ($debt as $key => &$value) {
                $value = utf8_encode($value);
            }

            $res = $soap->newClaim($auth, $claim, $debt);

            if (!empty($res->fileNumber)) {
                return array(true, $res->fileNumber);
            }

            $text = $this->getLang("TECERR");
            if (is_array($res->errorList)) {
                if (count($res->errorList) == 1) {
                    $text = $res->errorList[0];
                } else {
                    $text = "<ul>";
                    foreach ($res->errorList as $e) {
                        $text .= "<li>$e</li>";
                    }

                    $text .= "</ul>";
                }
            }

            return array(false, $text);
        } catch (SoapFault $ex) {
            return array(false, $ex->getMessage());
        }
    }

    public function claimStatus($id)
    {
        $auth = array(
            "clientId" => $this->options->mid,
            "licenceKey" => md5('c9cdbcb1e6399106939bcbc2a7ca9215' . $this->options->mkey),
            "sandbox" => false,
        );

        $options = array(
            "trace" => 1,
            "compression" => true,
            "exceptions" => true,
        );

        try {
            $soap = new SoapClient('https://soap.mediafinanz.de/encashment204.wsdl', $options);

            $res = $soap->getClaimStatus($auth, array("fileNumber" => $id));
            return $res->statusText ?: false;
        } catch (SoapFault $ex) {
            return $ex->getMessage();
        }
    }
}
