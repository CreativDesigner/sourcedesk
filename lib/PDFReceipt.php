<?php

class PDFReceipt
{
    protected $info;
    protected $user;

    public function __construct($info, $user = null)
    {
        $this->info = $info;

        if ($user == null) {
            $user = User::getInstance($this->info->user, "ID")->get();
        }

        $this->user = $user;
    }

    public function output()
    {
        global $dfo, $nfo, $cur, $lang, $gateways, $db, $CFG;

        if ($this->info->waiting) {
            die("Not fulfilled yet.");
        }

        require_once __DIR__ . "/tcpdf/tcpdf.php";
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setTitle($lang['RECEIPT']['FILE'] . " " . $this->info->ID);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetRightMargin(18);
        $pdf->AddPage();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($CFG['PAGENAME']);
        $pdf->setAutoPageBreak(false, 0);

        $logo = file_exists(__DIR__ . "/../themes/invoice-logo.jpg") ? __DIR__ . "/../themes/invoice-logo.jpg" : (file_exists(__DIR__ . "/../themes/invoice-logo.png") ? __DIR__ . "/../themes/invoice-logo.png" : "");
        if ($logo) {
            $pdf->setJPEGQuality(100);
            $pdf->setImageScale(1.67);
            $pdf->Image($logo, 0, 10, 0, 0, '', '', '', false, 300, 'R');
            $pdf->setImageScale(1);
        }

        $color = ltrim($CFG['PDF_COLOR'], "#");
        $pdf->SetLineStyle(array('color' => array(
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        )));
        $pdf->SetLineWidth(13.5);
        $pdf->Line(0, 0, 0, 297);

        $pdf->SetLineWidth(0.2);
        $pdf->Line(1.4, 105, 9, 105);
        $pdf->Line(1.4, 210, 9, 210);

        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetXY(135, 33);
        $pdf->SetFont('helvetica', '', 9);
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

            $pdf->SetX(135);
            $pdf->Cell(0, 0, $l, 0, 1, 'L');
        }

        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTMLCell(0, 0, 24, 60, "<u>" . $CFG['PDF_SENDER'] . "</u>");
        $pdf->SetLineStyle(array('color' => array(0, 0, 0)));

        $pdf->SetXY(24, 66);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 0, !empty($this->user['company']) ? $this->user['company'] : $this->user['firstname'] . " " . $this->user['lastname'], 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);

        if (!empty($this->user['company'])) {
            $pdf->SetX(24);
            $pdf->Cell(0, 0, "c/o " . $this->user['firstname'] . " " . $this->user['lastname'], 0, 1, 'L');
        }

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $this->user['street'] . " " . $this->user['street_number'], 0, 1, 'L');

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $this->user['postcode'] . " " . $this->user['city'], 0, 1, 'L');

        $sql = $db->query("SELECT name FROM client_countries WHERE ID = " . $this->user['country']);
        $country = $sql->num_rows == 1 ? $sql->fetch_object()->name : "";

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $country, 0, 1, 'L');

        $pdf->SetXY(135, 100);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $lang['INVOICE']['CNR'] . ": " . $CFG['CNR_PREFIX'] . $this->user['ID'], 0, 1, 'L');
        $pdf->SetX(135);
        $pdf->Cell(0, 0, $lang['RECEIPT']['DATE'] . ": " . $dfo->format($this->info->time, false), 0, 1, 'L');

        $pdf->SetXY(24, 110);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 0, $lang['RECEIPT']['TITLE'], 0, 1, 'L');

        $pdf->SetXY(24, 120);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, empty($this->user['company']) ? $lang['RECEIPT']['SALUT1'] . $this->user['firstname'] . " " . $this->user['lastname'] . $lang['RECEIPT']['SALUT2'] : $lang['RECEIPT']['SALUT3'], 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->SetX(24);
        $pdf->Cell(0, 0, $lang['RECEIPT']['INTRO'], 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->SetX(24);
        $pdf->Cell(0, 0, $lang['GENERAL']['DATE'] . ": " . $dfo->format($this->info->time, false), 0, 1, 'L');

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $lang['INVOICES']['AMOUNT'] . ": " . $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $this->info->amount, User::getInstance($this->user['ID'], "ID")->getCurrency())), User::getInstance($this->user['ID'], "ID")->getCurrency()), 0, 1, 'L');

        $gateway = array_shift(explode("|", $this->info->subject));

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $lang['RECEIPT']['PM'] . ": " . (array_key_exists($gateway, $gateways->get()) ? html_entity_decode($gateways->get()[$gateway]->getLang('TRANSACTION')) : html_entity_decode($gateway)), 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->SetX(24);
        $pdf->Cell(0, 0, $lang['RECEIPT']['END1'], 0, 1, 'L');

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $lang['RECEIPT']['END2'], 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->SetX(24);
        $pdf->Cell(0, 0, $lang['RECEIPT']['END3'], 0, 1, 'L');

        $pdf->SetX(24);
        $pdf->Cell(0, 0, str_replace("%p", $CFG['PAGENAME'], $lang['RECEIPT']['END4']), 0, 1, 'L');

        $pdf->SetXY(24, 180);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 0, $lang['RECEIPT']['END5'], 0, 1, 'L');

        // Print footer
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(150, 150, 150);

        $banks = explode(",", $CFG['PDF_BANK']);
        $ibans = explode(",", $CFG['PDF_IBAN']);
        $bics = explode(",", $CFG['PDF_BIC']);

        $yDiff = 5;
        $y = 287 - (count($banks) - 1) * $yDiff;

        foreach ($banks as $k => $bank) {
            $pdf->SetXY(24, $y);
            $pdf->Cell(0, 0, trim($bank), 0, 1, 'L');

            $pdf->SetXY(85, $y);
            $pdf->Cell(0, 0, trim($ibans[$k]), 0, 1, 'L');

            $pdf->SetY($y);
            $pdf->Cell(0, 0, trim($bics[$k]), 0, 1, 'R');

            $y += $yDiff;
        }

        $pdf->output($lang['RECEIPT']['FILE'] . "-" . $this->info->ID . ".pdf", "I");
    }
}
