<?php

class PDFLetter
{
    protected $info;
    protected $recipient;

    public function __construct($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM client_letters WHERE ID = " . intval($id));
        if ($sql->num_rows == 1) {
            $this->info = $sql->fetch_object();
            $this->recipient = unserialize($this->info->recipient);
        }
    }

    public function wasFound()
    {
        return (bool) $this->recipient;
    }

    public function getCountry()
    {
        return in_array($this->recipient[6], array("DE", "Deutschland", "Germany", "Deutsch")) ? "DE" : $this->recipient[6];
    }

    public function output($path = "")
    {
        global $dfo, $db, $CFG;

        if (empty($this->info)) {
            die("Letter not found.");
        }

        require __DIR__ . "/../languages/" . basename($this->recipient[7]) . ".php";

        $pdf = new MyPDFLetter(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setTitle("Brief " . $this->info->ID);

        $banks = explode(",", $CFG['PDF_BANK']);

        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetRightMargin(18);
        $pdf->AddPage();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($CFG['PAGENAME']);
        $pdf->setAutoPageBreak(true, count($banks) * 5 + 10);

        $logo = file_exists(__DIR__ . "/../themes/invoice-logo.jpg") ? __DIR__ . "/../themes/invoice-logo.jpg" : (file_exists(__DIR__ . "/../themes/invoice-logo.png") ? __DIR__ . "/../themes/invoice-logo.png" : "");
        if ($logo) {
            $pdf->setJPEGQuality(100);
            $pdf->setImageScale(1.67);
            $pdf->Image($logo, 0, 10, 0, 0, '', '', '', false, 300, 'R');
            $pdf->setImageScale(1);
        }

        $pdf->SetTextColor(0, 0, 0);
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
        $pdf->Cell(0, 0, !empty($this->recipient[8]) ? $this->recipient[8] : $this->recipient[0] . " " . $this->recipient[1], 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);

        if (!empty($this->recipient[8])) {
            $pdf->SetX(24);
            $pdf->Cell(0, 0, "c/o " . $this->recipient[0] . " " . $this->recipient[1], 0, 1, 'L');
        }

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $this->recipient[2] . " " . $this->recipient[3], 0, 1, 'L');

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $this->recipient[4] . " " . $this->recipient[5], 0, 1, 'L');

        $pdf->SetX(24);
        $pdf->Cell(0, 0, $this->recipient[6], 0, 1, 'L');

        $pdf->SetXY(135, 90);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $lang['GENERAL']['DATE'] . ": " . $dfo->format($this->info->date, false), 0, 1, 'L');

        $pdf->SetXY(24, 100);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 0, $this->info->subject, 0, 1, 'L');

        $pdf->SetXY(24, 110);
        $pdf->SetFont('helvetica', '', 10);

        $ex = explode("<br />", nl2br($this->info->text));
        foreach ($ex as $i => $t) {
            $pdf->SetX(25);
            $pdf->SetMargins(25, 10.00125, 18);
            $pdf->writeHTML(trim($t), false, false, false, false, '');
            $pdf->Ln(4);
        }

        // Print footer
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(150, 150, 150);

        $yDiff = 5;
        $y = 287 - (count($banks) - 1) * $yDiff;

        $append = __DIR__ . "/../files/letter_attachments/{$this->info->ID}.pdf";
        if (file_exists($append)) {
            $tmp = rand(100000, 999999);
            $files = array(__DIR__ . "/c{$tmp}.pdf", $append);
            $pdf->Output(__DIR__ . "/c{$tmp}.pdf", "F");
            $c = new PDFConcat;
            $c->setFiles($files);
            $c->concat();
            $pdf = $c;
            @unlink(__DIR__ . "/c{$tmp}.pdf");
        }

        if (empty($path)) {
            $pdf->output("Brief-" . $this->info->ID . ".pdf", "I");
        } else {
            $pdf->output($path, "F");
        }

        if (file_exists($append)) {
            @unlink(__DIR__ . "/c{$tmp}.pdf");
        }
    }
}

if (!class_exists("TCPDF")) {
    require_once __DIR__ . "/tcpdf/tcpdf.php";
}

class MyPDFLetter extends TCPDF
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
