<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Example\Controller
 */

namespace jtl\Connector\Example\Controller;

use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\Customer as CustomerModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\Result\Action;

class Customer extends DataController
{
    public function pull(QueryFilter $filter)
    {
        global $db, $CFG, $lang;

        $action = new Action();
        $action->setHandled(true);

        $customers = [];

        $sql = $db->query("SELECT * FROM clients");
        while ($row = $sql->fetch_object()) {
            $customer = (new CustomerModel)->setId(new Identity($row->ID))
                ->setCustomerNumber($row->ID)
                ->setCompany($row->company)
                ->setStreet($row->street . " " . $row->street_number)
                ->setZipCode($row->postcode)
                ->setCity($row->city)
                ->setCountryIso(\User::getInstance($row->ID, "ID")->get()['country_alpha2'])
                ->setPhone($row->telephone)
                ->setFirstName($row->firstname)
                ->setLastName($row->lastname)
                ->setFax($row->fax)
                ->setAccountCredit(doubleval($row->credit))
                ->setEMail($row->mail)
                ->setCreationDate(new \DateTime(date("Y-m-d H:i:s", $row->registered)))
                ->setHasCustomerAccount(true)
                ->setIsActive(true);

            $customers[] = $customer;
        }

        $action->setResult($customers);
        return $action;
    }

    public function statistic(QueryFilter $queryFilter)
    {
        global $db, $CFG;

        $action = new Action();
        $action->setHandled(true);

        $action->setResult([
            "available" => $db->query("SELECT 1 FROM clients")->num_rows,
            "controllerName" => "customer",
        ]);

        return $action;
    }
}
