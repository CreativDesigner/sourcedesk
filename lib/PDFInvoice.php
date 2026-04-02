<?php
// Class for generating an invoice

// Include the TCPDF library for generating the invoice
require_once __DIR__ . "/tcpdf/tcpdf.php";

class PDFInvoice
{
    private $pdf;
    private $append = array();

    // Constructor sets design properties
    public function __construct()
    {
        $this->pdf = new MyPDFInvoice(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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

            if ($user->get()['inv_tthof']) {
                $company = $user->get()['company'] ?: ($user->get()['firstname'] . " " . $user->get()['lastname']);
                $firstname = $user->get()['inv_tthof'];
                $lastname = "";
            } else {
                $company = $user->get()['company'];
                $firstname = $user->get()['firstname'];
                $lastname = $user->get()['lastname'];
            }

            $street_number = $user->get()['inv_street_number'] ?: $user->get()['street_number'];
            $street = $user->get()['inv_street'] ?: $user->get()['street'];
            $postcode = $user->get()['inv_postcode'] ?: $user->get()['postcode'];
            $city = $user->get()['inv_city'] ?: $user->get()['city'];
            $countryInfo = $db->query("SELECT * FROM client_countries WHERE ID = " . $user->get()['country'] . " LIMIT 1")->fetch_object();
            $country = $countryInfo->name;
            $currency = $user->getCurrency();
            if ($CFG['TAXES'] && $CFG['EU_VAT']) {
                $vatid = $user->get()['vatid'];
            }

            if ($CFG['TAXES']) {
                $ptax = $user->getVAT(null, $inv->getDate());
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
                $street_number = $persistentInfo['street_number'] = $user->get()['inv_street_number'] ?: $user->get()['street_number'];
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
                    $persistentInfo['ptax'] = $user->getVAT(isset($vatid) ? $vatid : null, $inv->getDate());
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
        $this->pdf->SetTitle(ucfirst(strtolower(str_replace(" ", "", $lang['INVOICE']['INVOICE']))) . " " . $inv->getInvoiceNo());

        $banks = explode(",", $CFG['PDF_BANK']);
        $this->pdf->setAutoPageBreak(true, count($banks) * 5 + 20);

        $logo = file_exists(__DIR__ . "/../themes/invoice-logo.jpg") ? __DIR__ . "/../themes/invoice-logo.jpg" : (file_exists(__DIR__ . "/../themes/invoice-logo.png") ? __DIR__ . "/../themes/invoice-logo.png" : "");
        if ($logo) {
            $this->pdf->setJPEGQuality(100);
            $this->pdf->setImageScale(1.67);
            $this->pdf->Image($logo, 0, 10, 0, 00, '', '', '', false, 300, 'R');
            $this->pdf->setImageScale(1);
        }

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
        $this->pdf->Cell(0, 0, $lang['INVOICE']['DATE'] . ': ' . $dfo->format(strtotime($inv->getDate()), false), 0, 1, 'L');
        $this->pdf->SetX(135);
        $this->pdf->Cell(0, 0, html_entity_decode($lang['INVOICE']['DUE']) . ': ' . $dfo->format(strtotime($inv->getDueDate()), false), 0, 1, 'L');

        $this->pdf->SetXY(24, 110);
        $this->pdf->SetFont("helvetica", 'B', 12);
        $this->pdf->Cell(0, 0, str_replace(" ", "", $lang['INVOICE']['INVOICE']) . ' ' . $inv->getInvoiceNo(), 0, 1, 'L');

        // Insert Bezahlcode
        if (isset($gateways->get()['transfer']) && $gateways->get()['transfer']->getSettings()['code_invoice'] === "true" && file_exists(__DIR__ . "/phpqrcode/phpqrcode.php") && $inv->getStatus() == "0") {
            require_once __DIR__ . "/phpqrcode/phpqrcode.php";
            $qrContent = "bank://singlepaymentsepa?name=" . urlencode(strtoupper($gateways->get()['transfer']->getSettings()['account_holder'])) . "&reason=" . $inv->getInvoiceNo() . "&iban=" . str_replace(" ", "", $gateways->get()['transfer']->getSettings()['iban']) . "&bic=" . str_replace(" ", "", $gateways->get()['transfer']->getSettings()['bic']) . "&amount=" . urlencode(str_replace(".", ",", $inv->getAmount()));

            ob_start();
            QRcode::png($qrContent, null);
            $qrCode = ob_get_contents();
            ob_end_clean();

            $this->pdf->SetFont('helvetica', '', 7);
            $this->pdf->Image('@' . $qrCode, 22, 3.8, 30);
            $this->pdf->SetXY(23.5, 32);
            $this->pdf->Cell(0, 0, $lang['INVOICE']['QR_CODE'], 0, 1, 'L');
        }

        $this->pdf->SetXY(24, 120);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 0, empty($company) ? $lang['INVOICE']['GREETINGS'] . " " . $firstname . " " . $lastname . "," : $lang['INVOICE']['GREETINGS_COMPANY'] . ",", 0, 1, 'L');

        $this->pdf->Ln(5);
        $this->pdf->SetX(24);
        $this->pdf->Cell(0, 0, lcfirst(html_entity_decode($lang['INVOICE']['INTRO1'])), 0, 1, 'J');

        $this->pdf->SetX(24);
        $this->pdf->Cell(0, 0, (html_entity_decode($lang['INVOICE']['INTRO2'])), 0, 1, 'L');

        $this->pdf->SetFont('helvetica', 'B', 10);

        $this->pdf->SetXY(38.2, 144);
        $this->pdf->Cell(0, 0, $lang['INVOICE']['DESC'], 0, 1, 'L');

        $this->pdf->SetXY(170, 144);
        $this->pdf->Cell(0, 0, $lang['INVOICE']['PRICE'], 0, 1, 'R');

        $this->pdf->Line(25.2, 151, 190.8, 151);

        $this->pdf->SetY(150);
        $this->pdf->SetX(24);

        $this->pdf->SetFont("helvetica", '', 10);

        // Insert items
        $table = '<table>';
        foreach ($inv->getItems() as $item) {
            if (!($item instanceof InvoiceItem)) {
                continue;
            }

            $articleName = str_replace(array("<br>", "<br/>", "<br >"), "<br />", nl2br($item->getDescription()));
            if ($item->getDescription() == "special_credit") {
                $articleName = $lang['INVOICE']['SPECIAL_CREDIT'];
            }

            $qtyUnit = $nfo->format($item->getQty(), strlen(substr(strrchr($item->getQty(), "."), 1))) . " " . $item->getUnit();

            $table .= '<tr><td></td></tr>';
            $table .= '<tr><td width="40px" align="left">' . $qtyUnit . '</td><td width="367px">' . $articleName . '</td><td width="64px" align="right">' . $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $item->getAmount() * $item->getQty(), $currency)), $currency) . (!$item->getTax() ? '<br />' . $lang['INVOICE']['TAXFREE'] : '') . '</td></tr>';
        }
        $table .= '</table>';
        $this->pdf->SetX(25);
        $this->pdf->writeHTML($table, true, false, false, false, '');
        $this->pdf->Line(25.2, $this->pdf->getY(), 190.8, $this->pdf->getY());
        $this->pdf->Ln(4);

        // Calculate total amount
        $total = $cur->convertAmount($cur->getBaseCurrency(), $inv->getAmount(), $currency);
        $total2 = $cur->convertAmount($cur->getBaseCurrency(), $inv->getAmount(true), $currency);

        // Tax output
        if (!is_array($tax) || $tax[1] == 0 || empty($tax[0])) {
            $this->pdf->SetX(27);
            $this->pdf->SetFont("helvetica", 'B', 10);
            $this->pdf->Cell(0, 0, $lang['INVOICE']['INVOICE_AMOUNT'] . ':                       ', 0, 1, 'R');

            $this->pdf->SetXY(146, $this->pdf->GetY() - 5);
            $this->pdf->SetFont("helvetica", 'B', 10);
            $this->pdf->Cell(0, 0, $cur->infix($nfo->format($total), $currency), 0, 1, 'R');
        } else {
            $steuer = $nfo->format($this->calculateVat($total2, 3, $tax[1]));
            $netto = $total - $this->calculateVat($total2, 3, $tax[1]);

            $this->pdf->SetX(27);
            $this->pdf->Cell(0, 0, $lang['INVOICE']['NET'] . ':                       ', 0, 1, 'R');

            $this->pdf->SetXY(146, $this->pdf->GetY() - 5);
            $this->pdf->Cell(0, 0, $cur->infix($nfo->format($netto), $currency), 0, 1, 'R');

            $this->pdf->Ln(2);
            $this->pdf->SetFont("helvetica", '', 10);
            $this->pdf->SetX(27);
            $this->pdf->Cell(0, 0, $tax[0] . " (" . $nfo->format($tax[1]) . '%):                       ', 0, 1, 'R');

            $this->pdf->SetXY(146, $this->pdf->GetY() - 5);
            $this->pdf->Cell(0, 0, $cur->infix($steuer, $currency), 0, 1, 'R');

            $this->pdf->Ln(2);
            $this->pdf->SetX(27);
            $this->pdf->SetFont("helvetica", "B", 10);
            $this->pdf->Cell(0, 0, $lang['INVOICE']['INVOICE_AMOUNT'] . ':                       ', 0, 1, 'R');

            $this->pdf->SetXY(146, $this->pdf->GetY() - 5);
            $this->pdf->SetFont("helvetica", 'B', 10);
            $this->pdf->Cell(0, 0, $cur->infix($nfo->format($total), $currency), 0, 1, 'R');
        }

        // Set footer text
        $html = $lang['INVOICE']['FOOTER' . $inv->getStatus()];

        $this->pdf->SetFont(PDF_FONT_NAME_MAIN, '', 10);
        $this->pdf->ln(3);
        $this->pdf->SetX(25);
        $this->pdf->writeHTML(str_replace("'", '"', $html), true, false, false, false, '');

        // Set reverse charge text
        if (!is_array($tax) && $tax !== false) {
            if (!empty($vatid)) {
                $html = $lang['INVOICE']['REVERSE'];
                $html = str_replace("%id", $vatid, $html);
            } else {
                $html = $lang['INVOICE']['REVERSE_WOID'];
            }

            $names = array(
                "DE" => "Steuerschuldnerschaft des Leistungsempfängers",
                "BE" => "Autoliquidation",
                "FR" => "Autoliquidation",
                "LU" => "Autoliquidation",
                "NL" => "Btw verlegd",
                "BG" => "обратно начисляване",
                "DK" => "omvendt betalingspligt",
                "EE" => "pöördmaksustamine",
                "FI" => "käännetty verovelvollisuus",
                "EL" => "Αντίστροφη επιβάρυνση",
                "CY" => "Αντίστροφη επιβάρυνση",
                "GB" => "Reverse Charge",
                "IE" => "Reverse Charge",
                "IT" => "inversione contabile",
                "LV" => "nodokļa apgrieztā maksā–ana",
                "LT" => "Atvirk–tinis apmokestinimas",
                "MT" => "Inverżjoni tal-ħlas",
                "PL" => "odwrotne obciążenie",
                "PT" => "Autoliquidação",
                "RO" => "taxare inversă",
                "SE" => "Omvänd betalningsskyldighet",
                "SK" => "prenesenie daňovej povinnosti",
                "SI" => "Reverse Charge",
                "ES" => "inversión del sujeto pasivo",
                "CZ" => "daň odvede zákazník",
                "HU" => "fordított adózás",
            );

            if (!isset($names[$countryInfo->alpha2])) {
                $names[$countryInfo->alpha2] = "Reverse Charge";
            }

            $html = str_replace("%n", $names[$countryInfo->alpha2], $html);

            if (in_array($countryInfo->alpha2, array("BG", "EL", "CY", "LV", "MT", "PL", "PT", "RO", "CZ", "HU"))) {
                $this->pdf->setFontSubsetting(true);
                $this->pdf->SetFont("freeserif", '', 11);
            } else {
                $this->pdf->SetFont(PDF_FONT_NAME_MAIN, '', 10);
            }
            $this->pdf->SetX(25);
            $this->pdf->writeHTML($html, true, false, false, false, '');
        }

        if ($inv->getDate() <= "2015-12-31") {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->SetX(24);
            $this->pdf->Cell(0, 0, html_entity_decode($lang['INVOICE']['SMALL_BUSINESS1']), 0, 1, 'J');

            $this->pdf->SetX(24);
            $this->pdf->Cell(0, 0, html_entity_decode($lang['INVOICE']['SMALL_BUSINESS2']), 0, 1, 'L');
        }

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
            return $this->pdf->Output($file . $suffix, $type);
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

class MyPDFInvoice extends TCPDF
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
