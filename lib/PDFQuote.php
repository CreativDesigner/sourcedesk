<?php

if (!class_exists("TCPDF")) {
    require_once __DIR__ . "/tcpdf/tcpdf.php";
}

class QuotePDFDriver extends TCPDF
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

class PDFQuote
{
    protected $info;
    protected $recipient;

    public function __construct($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM client_quotes WHERE ID = " . intval($id));
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

    public function getVat()
    {
        return $this->info->vat;
    }

    public function getMail()
    {
        return $this->recipient[9] ?: "";
    }

    public function getName()
    {
        return $this->recipient[0] . " " . $this->recipient[1];
    }

    public function getLanguage()
    {
        return $this->recipient[7];
    }

    public function getSum()
    {
        $items = unserialize($this->info->items);
        $sum = 0;
        foreach ($items as $i) {
            $sum += $i[2];
        }

        return $sum;
    }

    public function getDate()
    {
        return $this->info->date;
    }

    public function getValid()
    {
        return $this->info->valid;
    }

    public function getClient()
    {
        return $this->info->client;
    }

    public function getItems()
    {
        return unserialize($this->info->items);
    }

    public function output($path = "", $clientarea = false)
    {
        global $dfo, $db, $CFG, $cur, $nfo, $pdfColor;

        if (empty($this->info)) {
            die("Quote not found.");
        }

        if (!$clientarea) {
            require __DIR__ . "/../languages/" . basename($this->recipient[7]) . ".php";
        } else {
            $lang = $GLOBALS['lang'];
        }

        $nr = $this->info->ID;
        while (strlen($nr) < $CFG['MIN_QUOLEN']) {
            $nr = "0" . $nr;
        }

        $prefix = $CFG['OFFER_PREFIX'];
        $date = strtotime($this->getDate());
        $prefix = str_replace("{YEAR}", date("Y", $date), $prefix);
        $prefix = str_replace("{MONTH}", date("m", $date), $prefix);
        $prefix = str_replace("{DAY}", date("d", $date), $prefix);

        $nr = $prefix . $nr;

        $pdf = new QuotePDFDriver(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setTitle($nr);

        $color = ltrim($CFG['PDF_COLOR'], "#");
        $pdfColor = array('color' => array(
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ));

        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetRightMargin(18);
        $pdf->AddPage();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($CFG['PAGENAME']);

        $banks = explode(",", $CFG['PDF_BANK']);
        $pdf->setAutoPageBreak(true, count($banks) * 5 + 20);

        $pdf->SetLineStyle($pdfColor);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(1.4, 105, 9, 105);
        $pdf->Line(1.4, 210, 9, 210);

        $logo = file_exists(__DIR__ . "/../themes/invoice-logo.jpg") ? __DIR__ . "/../themes/invoice-logo.jpg" : (file_exists(__DIR__ . "/../themes/invoice-logo.png") ? __DIR__ . "/../themes/invoice-logo.png" : "");
        if ($logo) {
            $pdf->setJPEGQuality(100);
            $pdf->setImageScale(1.67);
            $pdf->Image($logo, 0, 10, 0, 0, '', '', '', false, 300, 'R');
            $pdf->setImageScale(1);
        }

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

        $pdf->SetXY(135, 87);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $lang['QUOTE']['NR'] . ": " . $nr, 0, 1, 'L');

        $pdf->SetXY(135, 92);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $lang['GENERAL']['DATE'] . ": " . $dfo->format($this->info->date, false), 0, 1, 'L');

        $pdf->SetXY(135, 97);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, html_entity_decode($lang['QUOTE']['VALID']) . ": " . $dfo->format($this->info->valid, false), 0, 1, 'L');

        $pdf->SetXY(24, 100);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 0, $lang['QUOTE']['TITLE'], 0, 1, 'L');

        $pdf->SetXY(24, 110);
        $pdf->SetFont('helvetica', '', 10);

        if (empty($this->recipient[8])) {
            $pdf->Cell(0, 0, $lang['INVOICE']['GREETINGS'] . " " . $this->recipient[0] . " " . $this->recipient[1] . "!", 0, 1, 'L');
        } else {
            $pdf->Cell(0, 0, $lang['INVOICE']['GREETINGS_COMPANY'] . "!", 0, 1, 'L');
        }

        $pdf->SetXY(24, 120);
        $ex = explode("<br />", nl2br($this->info->intro));
        foreach ($ex as $i => $t) {
            $pdf->SetX(25);
            $pdf->SetMargins(25, 10.00125, 18);
            $pdf->writeHTML(trim($t), false, false, false, false, 'L');
            $pdf->Ln(4);
        }

        $pdf->Ln(6);
        $pdf->SetX(24);

        $pdf->SetFont('helvetica', 'B', 10);
        $y = $pdf->getY();
        $pdf->SetXY(24, $y);
        $pdf->Cell(0, 0, $lang['INVOICE']['DESC'], 0, 1, 'L');

        if ($this->info->duration) {
            $pdf->SetXY(130, $y);
            $pdf->Cell(0, 0, $lang['QUOTE']['DURATION'], 0, 1, 'L');
        }

        $pdf->SetXY(170, $y);
        $pdf->Cell(0, 0, $lang['INVOICE']['PRICE'], 0, 1, 'R');

        $pdf->Line(25.2, $y + 6.5, 190.8, $y + 6.5);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->Ln(4.5);
        $table = '<table>';
        $count = 0;
        $items = unserialize($this->info->items);
        $total = 0;
        $time = 0;
        $offset = $this->info->duration ? 390 : 470;
        foreach ($items as $i) {
            $articleName = str_replace(array("<br>", "<br/>", "<br >"), "<br />", nl2br($i[0]));

            if ($this->info->duration) {
                $time += $i[1];
                $table .= '<tr><td width="300px" align="left">' . $articleName . '</td><td width="108px" align="left">' . $i[1] . " " . $lang['QUOTE']['DAY' . ($i[0] != 1 ? "S" : "")] . '</td><td width="62px" align="right">' . $cur->infix($nfo->format($i[2]), $cur->getBaseCurrency()) . '</td></tr>';
            } else {
                $table .= '<tr><td width="408px" align="left">' . $articleName . '</td><td width="62px" align="right">' . $cur->infix($nfo->format($i[2]), $cur->getBaseCurrency()) . '</td></tr>';
            }

            $table .= '<tr><td></td></tr>';
            $count += count(explode("<br />", $articleName)) + 1;
            $total += $i[2];

            foreach (explode("<br />", $articleName) as $line) {
                $size = imagettfbbox(10, 0, __DIR__ . "/tcpdf/fonts/helvetica.ttf", $line);
                if (is_array($size)) {
                    $size = $size[2];
                    while ($size > $offset) {
                        $size -= $offset;
                        $count++;
                    }
                }
            }
        }
        $table .= '</table>';
        $pdf->SetX(25);
        $pdf->writeHTML($table, true, false, false, false, '');
        $pdf->Line(25.2, $pdf->getY(), 190.8, $pdf->getY());

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(27, $pdf->getY() + 2);
        $pdf->Cell(0, 0, $lang['QUOTE']['SUM'] . ':                             ', 0, 1, 'R');

        $pdf->SetX(146);
        $pdf->Ln(-4);
        $pdf->Cell(0, 0, $cur->infix($nfo->format($total), $cur->getBaseCurrency()), 0, 1, 'R');
        $pdf->SetFont('helvetica', '', 10);

        if ($this->info->duration) {
            $pdf->Ln(1);
            $pdf->SetX(27);
            $pdf->Cell(0, 0, $lang['QUOTE']['SUMTIME'] . ':                             ', 0, 1, 'R');

            $pdf->Ln(-4);
            $pdf->SetX(146);
            $pdf->Cell(0, 0, $time . " " . $lang['QUOTE']['DAY' . ($time != 1 ? "S" : "")], 0, 1, 'R');
        }

        $pdf->Ln(4);
        $ex = explode("<br />", str_replace(["<br>", "<br/>"], "<br />", nl2br($this->info->extro)));
        foreach ($ex as $i => $t) {
            $pdf->writeHTML(trim($t), false, false, false, false, '');
            $pdf->Ln(4);
        }

        $pdf->Ln(6);
        $pdf->SetFont('helvetica', '', 8);
        $ex = explode("<br />", str_replace(["<br>", "<br/>"], "<br />", nl2br($this->info->terms)));
        foreach ($ex as $i => $t) {
            $pdf->writeHTML(trim($t), false, false, false, false, '');
        }

        if (empty($path)) {
            $pdf->output($nr . ".pdf", "I");
        } else {
            $pdf->output($path, "F");
        }

    }
}
