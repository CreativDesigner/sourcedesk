<?php

class EuroTreuhandInkasso extends Encashment
{
    protected $short = "eti";
    protected $name = "EuroTreuhand Inkasso";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "username" => array("type" => "text", "name" => $this->getLang("username")),
            "password" => array("type" => "password", "name" => $this->getLang("password")),
        );
    }

    public function newClaim($debtor, $claim)
    {
        $data = array(
            "sno" => "ETI004",
            "suname" => $this->options->username,
            "supass" => $this->options->password,
            "test" => "0",

            "salutation" => $debtor->salutation == "MALE" ? "Herr" : "Frau",
            "firstname" => empty($debtor->company) ? $debtor->firstname : "",
            "lastname" => empty($debtor->company) ? $debtor->lastname : $debtor->company,
            "street" => $debtor->street,
            "house" => $debtor->street_number,
            "zip" => $debtor->postcode,
            "city" => $debtor->city,
            "country" => $debtor->country_alpha2,
            "phone" => $debtor->telephone,
            "email" => $debtor->mail,

            "creditorworkid" => $claim->invoice,
            "mainclaim" => $claim->amount,
            "overduefines" => $claim->latefee,
            "contractdate" => date("d.m.Y", strtotime($claim->date)),
            "invoicedate" => date("d.m.Y", strtotime($claim->date)),
            "invoicenumber" => $claim->invoice,
            "claimreason" => 5,
            "overduenoticedate" => date("d.m.Y", strtotime($claim->lastnotice)),
        );

        if (!empty($debtor->company)) {
            $data['contactfirstname'] = $debtor->firstname;
            $data['contactlastname'] = $debtor->lastname;
        }

        $url = "https://www.eurotreuhandinkasso-service.de/?" . http_build_query($data);
        $res = file_get_contents($url);
        parse_str($res, $pRes);

        if ($pRes['Zustand'] !== "0") {
            return [false, isset($pRes['Zustandstext']) ? htmlentities($pRes['Zustandstext']) : ""];
        }

        return [true, ""];
    }

    public function claimStatus($id)
    {
        return $this->getLang("STATUS");
    }
}
