<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Feature
 */

namespace jtl\Connector\Feature\Base;

use \jtl\Connector\Feature\Exporter\IExporter;
use \jtl\Connector\Feature\Importer\IImporter;
use \jtl\Connector\Feature\Manager;

/**
 * Producer interface
 *
 * @author David Spickers <david.spickers@jtl-software.de>
 */
interface IProducer
{

    public function setManager(Manager $manager);

    public function getManager();

    public function import(IImporter $importer);

    public function export(IExporter $writer);

    public function transform(IImporter $from, IExporter $to);
}
