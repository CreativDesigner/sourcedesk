<?php
// Class for generating delivery notes

// Include the TCPDF library for generating the invoice
require_once __DIR__ . "/tcpdf/tcpdf.php";

class PDFDeliveryNote
{
    private $pdf;
    private $append = array();

    // Constructor sets design properties
    public function __construct()
    {
        $this->pdf = new MyPDFDeliveryNote(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    }

    // Method to add an invoice to the PDF
    public function add(Invoice $inv)
    {
        // Global some variables for security reasons
        global $CFG, $nfo, $db, $lang, $dfo, $cur, $gateways;

        // Get an object for the user
        if ($inv->getClient() != "0") {
            $user = User::getInstance($inv->getClient(), "ID");
            if (!$user) {
                return false;
            }
        }

        // Persistent invoice
        if (!empty($inv->getClientData())) {
            $persistentInfo = unserialize($inv->getClientData());
        } else {
            $persistentInfo = false;
        }

        if (false === $persistentInfo || !is_array($persistentInfo)) {
            if (!isset($user)) {
                return false;
            }

            $company = $user->get()['company'];
            $firstname = $user->get()['firstname'];
            $lastname = $user->get()['lastname'];
            $street_number = $user->get()['street_number'];
            $street = $user->get()['street'];
            $postcode = $user->get()['postcode'];
            $city = $user->get()['city'];
            $countryInfo = $db->query("SELECT * FROM client_countries WHERE ID = " . $user->get()['country'] . " LIMIT 1")->fetch_object();
            $country = $countryInfo->name;
            $currency = $user->getCurrency();
            if ($CFG['TAXES'] && $CFG['EU_VAT']) {
                $vatid = $user->get()['vatid'];
            }

            if ($CFG['TAXES']) {
                $ptax = $user->getVAT();
                if ($ptax === false) {
                    return false;
                }

            } else {
                $ptax = array("", 0);
            }

            $arr = array("company" => $company, "firstname" => $firstname, "lastname" => $lastname, "street" => $street, "street_number" => $street_number, "postcode" => $postcode, "city" => $city, "country" => $country, "currency" => $currency, "ptax" => $ptax, "vatid" => $vatid);
            $inv->setClientData(serialize($arr));
            $inv->save();
        } else {
            foreach ($persistentInfo as $k => $v) {
                $$k = $v;
            }

            if ((!isset($firstname) || !isset($lastname)) && isset($user)) {
                $firstname = $persistentInfo['firstname'] = $user->get()['firstname'];
                $lastname = $persistentInfo['lastname'] = $user->get()['lastname'];
                $inv->setClientData(serialize($persistentInfo));
                $inv->save();
            }

            if (!isset($street_number) && isset($user)) {
                $street_number = $persistentInfo['street_number'] = $user->get()['street_number'];
                $inv->setClientData(serialize($persistentInfo));
                $inv->save();
            }

            if (!isset($currency) && isset($user)) {
                $currency = $user->getCurrency();
                $persistentInfo['currency'] = $currency;
                $inv->setClientData(serialize($persistentInfo));
                $inv->save();
            }

            if (!isset($ptax) && isset($user)) {
                if ($CFG['TAXES']) {
                    $persistentInfo['ptax'] = $user->getVAT(isset($vatid) ? $vatid : null);
                } else {
                    $persistentInfo['ptax'] = array("", 0);
                }

                $inv->setClientData(serialize($persistentInfo));
                $inv->save();
            } else {
                $CFG['TAXES'] = true;
            }

            if (!isset($vatid) && $CFG['TAXES'] && $CFG['EU_VAT'] && isset($user)) {
                $vatid = $user->get()['vatid'];
                $persistentInfo['vatid'] = $vatid;
                $inv->setClientData(serialize($persistentInfo));
                $inv->save();
            }
        }

        $countryInfo = $db->query("SELECT * FROM client_countries WHERE name = '$country'")->fetch_object();

        $tax = $ptax;

        // Initialize PDF and insert first page
        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(true);
        $this->pdf->SetRightMargin(18);
        $this->pdf->AddPage();
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor($CFG['PAGENAME']);
        $this->pdf->SetTitle(ucfirst(strtolower(str_replace(" ", "", $lang['INVOICE']['DELNOTE']))) . " " . $inv->getShortNo());

        $banks = explode(",", $CFG['PDF_BANK']);
        $this->pdf->setAutoPageBreak(true, count($banks) * 5 + 20);

        $logo = file_exists(__DIR__ . "/../themes/invoice-logo.jpg") ? __DIR__ . "/../themes/invoice-logo.jpg" : (file_exists(__DIR__ . "/../themes/invoice-logo.png") ? __DIR__ . "/../themes/invoice-logo.png" : "");
        if ($logo) {
            $this->pdf->setJPEGQuality(100);
            $this->pdf->setImageScale(1.67);
            $this->pdf->Image($logo, 0, 10, 0, 0, '', '', '', false, 300, 'R');
            $this->pdf->setImageScale(1);
        }

        $color = ltrim($CFG['PDF_COLOR'], "#");
        $this->pdf->SetTextColor($color[0], $color[1], $color[2]);
        $this->pdf->SetXY(135, 33);
        $this->pdf->SetFont('helvetica', '', 9);

        $ex = explode("\n", $CFG['PDF_ADDRESS']);
        foreach ($ex as $l) {
            if (false !== ($pos = strpos($l, "%"))) {
                $identifier = substr($l, $pos + 1);
                $pos2 = strpos($identifier, "%");
                if (false !== $pos2) {
                    $identifier = substr($identifier, 0, $pos2);
                    $ex = explode(".", strtoupper($identifier));
                    if (count($ex) == 2 && array_key_exists($ex[0], $lang) && array_key_exists($ex[1], $lang[$ex[0]])) {
                        $l = substr($l, 0, $pos) . html_entity_decode($lang[$ex[0]][$ex[1]]) . substr($l, $pos + $pos2 + 2);
                    }
                }
            }

            $this->pdf->SetX(135);
            $this->pdf->Cell(0, 0, $l, 0, 1, 'L');
        }

        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->writeHTMLCell(0, 0, 24, 60, "<u>" . $CFG['PDF_SENDER'] . "</u>");
        $this->pdf->SetLineStyle(array('color' => array(0, 0, 0)));

        $this->pdf->SetXY(24, 66);
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(0, 0, !empty($company) ? $company : $firstname . " " . $lastname, 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 9);

        if (!empty($company)) {
            $this->pdf->SetX(24);
            $this->pdf->Cell(0, 0, "c/o " . $firstname . " " . $lastname, 0, 1, 'L');
        }

        $this->pdf->SetX(24);
        $this->pdf->Cell(0, 0, $street . " " . $street_number, 0, 1, 'L');

        $this->pdf->SetX(24);
        $this->pdf->Cell(0, 0, $postcode . " " . $city, 0, 1, 'L');

        $this->pdf->SetX(24);
        $this->pdf->Cell(0, 0, $country, 0, 1, 'L');

        $this->pdf->SetXY(135, 95);
        $this->pdf->SetFont('helvetica', '', 10);
        if (isset($user)) {
            $this->pdf->Cell(0, 0, $lang['INVOICE']['CNR'] . ': ' . $CFG['CNR_PREFIX'] . $user->get()['ID'], 0, 1, 'L');
        }

        $this->pdf->SetX(135);
        $this->pdf->Cell(0, 0, $lang['INVOICE']['ORDER'] . ': ' . $dfo->format(strtotime($inv->getDate()), false), 0, 1, 'L');
        $this->pdf->SetX(135);
        $this->pdf->Cell(0, 0, html_entity_decode($lang['INVOICE']['DELIVERY']) . ': ' . $dfo->format(strtotime($inv->getDeliveryDate()), false), 0, 1, 'L');

        $this->pdf->SetXY(24, 110);
        $this->pdf->SetFont("helvetica", 'B', 12);
        $this->pdf->Cell(0, 0, str_replace(" ", "", $lang['INVOICE']['DELNOTE']) . ' ' . $inv->getShortNo(), 0, 1, 'L');

        $this->pdf->SetXY(24, 120);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 0, empty($company) ? $lang['INVOICE']['GREETINGS'] . " " . $firstname . " " . $lastname . "," : $lang['INVOICE']['GREETINGS_COMPANY'] . ",", 0, 1, 'L');

        $this->pdf->Ln(5);
        $this->pdf->SetX(24);
        $this->pdf->Cell(0, 0, lcfirst(html_entity_decode($lang['INVOICE']['DELIVERYINTRO'])), 0, 1, 'L');

        $this->pdf->SetFont('helvetica', 'B', 10);

        $this->pdf->SetXY(24, 140);
        $this->pdf->Cell(0, 0, $lang['INVOICE']['POSITION'], 0, 1, 'L');

        $this->pdf->SetXY(34, 140);
        $this->pdf->Cell(0, 0, $lang['INVOICE']['DESC'], 0, 1, 'L');

        $this->pdf->SetXY(170, 140);
        $this->pdf->Cell(0, 0, $lang['INVOICE']['QTY'], 0, 1, 'R');

        $this->pdf->Line(25.2, 147, 190.8, 147);

        $this->pdf->SetY(149);
        $this->pdf->SetX(24);

        $this->pdf->SetFont("helvetica", '', 10);

        // Insert items
        $i = 1;
        $table = '<table>';
        $count = 0;
        foreach ($inv->getItems() as $item) {
            if (!($item instanceof InvoiceItem)) {
                continue;
            }

            $articleName = str_replace(array("<br>", "<br/>", "<br >"), "<br />", nl2br($item->getDescription()));
            if ($item->getDescription() == "special_credit") {
                $articleName = $lang['INVOICE']['SPECIAL_CREDIT'];
            }

            $qtyUnit = $nfo->format($item->getQty(), strlen(substr(strrchr($item->getQty(), "."), 1))) . " " . $item->getUnit();

            $table .= '<tr><td width="29px" align="left">' . $i . '</td><td width="352px">' . $articleName . '</td><td width="90px" align="right">' . $qtyUnit . '</td></tr>';
            $table .= '<tr><td></td></tr>';
            $count += count(explode("<br />", $articleName)) + 1;
            $i++;
        }
        $table .= '</table>';
        $this->pdf->SetX(25);
        $this->pdf->writeHTML($table, true, false, false, false, '');

        if ($inv->getAttachment() && file_exists(__DIR__ . "/../files/invoice_attachments/{$inv->getAttachment()}")) {
            array_push($this->append, realpath(__DIR__ . "/../files/invoice_attachments/{$inv->getAttachment()}"));
        }

        return true;
    }

    // Method to calculate the tax
    private function calculateVat($price, $calculate = 1, $vat = 19)
    {
        if ($calculate == 1) {
            $newPrice = $price + (($price / 100) * $vat);
            return round($newPrice, 2);
        } elseif ($calculate == 2) {
            $newPrice = ($price * 100) / (100 + $vat);
            return round($newPrice, 2);
        } else {
            $newPrice = (($price * 100) / (100 + $vat)) * ($vat / 100);
            return round($newPrice, 2);
        }
    }

    // Method to output the invoice
    public function output($file, $type = "D", $exit = true, $suffix = ".pdf")
    {
        if (count($this->append) == 0) {
            $this->pdf->Output($file . $suffix, $type);
        } else {
            $tmp = rand(100000, 999999);
            $files = array(__DIR__ . "/c{$tmp}.pdf");
            foreach ($this->append as $f) {
                array_push($files, $f);
            }

            $this->pdf->Output(__DIR__ . "/c{$tmp}.pdf", "F");
            $c = new PDFConcat;
            $c->setFiles($files);
            $c->concat();
            unlink(__DIR__ . "/c{$tmp}.pdf");
            $c->output($file . $suffix, $type);
        }

        if ($exit) {
            exit;
        }

    }
}

class MyPDFDeliveryNote extends TCPDF
{
    public function Header()
    {
        global $CFG;

        $color = ltrim($CFG['PDF_COLOR'], "#");
        $this->SetLineStyle(array('color' => array(
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        )));
        $this->SetLineWidth(13.5);
        $this->Line(0, 0, 0, 297);

        $this->SetLineWidth(0.2);
        $this->Line(1.4, 105, 9, 105);
        $this->Line(1.4, 210, 9, 210);
    }

    public function Footer()
    {
        global $CFG;

        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(150, 150, 150);

        $banks = explode(",", $CFG['PDF_BANK']);
        $ibans = explode(",", $CFG['PDF_IBAN']);
        $bics = explode(",", $CFG['PDF_BIC']);

        $yDiff = 5;
        $y = 287 - (count($banks) - 1) * $yDiff;

        foreach ($banks as $k => $bank) {
            $this->SetXY(24, $y);
            $this->Cell(0, 0, trim($bank), 0, 1, 'L');

            $this->SetXY(85, $y);
            $this->Cell(0, 0, trim($ibans[$k]), 0, 1, 'L');

            $this->SetY($y);
            $this->Cell(0, 0, trim($bics[$k]), 0, 1, 'R');

            $y += $yDiff;
        }
    }
}
