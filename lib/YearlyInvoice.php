<?php
// Class for generating yearly invoices

require_once __DIR__ . "/tcpdf/tcpdf.php";

class YearlyInvoice extends TCPDF
{
    protected $year, $user;

    public static function init($uid, $year)
    {
        global $CFG, $db, $lang, $dfo, $nfo, $cur;
        $l = $lang['YEARLY_INVOICE'];

        $pdf = new YearlyInvoice(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->year = $year;
        $pdf->user = User::getInstance($uid, "ID");

        $pdf->SetCreator("haseDESK");
        $pdf->SetAuthor($CFG['PAGENAME']);
        $pdf->SetTitle($l['FILENAME'] . " " . $year);
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        $tbl = '<table><tr><th style="font-weight: bold; width: 15%;">' . $l['DATE'] . '</th><th style="font-weight: bold; width: 55%;">' . $l['POSITION'] . '</th><th style="font-weight: bold; text-align: left; width: 15%;">' . $l['AMOUNT'] . '</th><th style="font-weight: bold; text-align: right; width: 15%;">' . $l['STATUS'] . '</th></tr>';

        $inv = new Invoice;
        $sql = $db->query("SELECT ID FROM invoices WHERE client = $uid AND status IN (0,1) AND YEAR(date) = $year ORDER BY date ASC");
        while ($row = $sql->fetch_object()) {
            if (!$inv->load($row->ID)) {
                continue;
            }

            foreach ($inv->getItems() as $item) {
                $status = "<font style=\"color: " . ($inv->getStatus() ? "green" : "red") . "\">" . ($inv->getStatus() ? $l['PAID'] : $l['UNPAID']) . "</font>";
                $tbl .= '<tr><td>' . $dfo->format($inv->getDate(), false, false) . '</td><td>' . $item->getDescription() . '</td><td style="text-align: left;">' . $cur->infix($nfo->format($item->getAmount() * $item->getQty()), $cur->getBaseCurrency()) . '</td><td style="text-align: right;">' . $status . '</td></tr>';
            }
        }
        $tbl .= '</table>';

        $pdf->writeHTML($tbl, true, false, false, false, '');
        return $pdf;
    }

    public function Header()
    {
        global $lang;
        $l = $lang['YEARLY_INVOICE'];

        $image_file = file_exists(__DIR__ . "/../themes/invoice-logo.jpg") ? __DIR__ . "/../themes/invoice-logo.jpg" : (file_exists(__DIR__ . "/../themes/invoice-logo.png") ? __DIR__ . "/../themes/invoice-logo.png" : "");
        $this->Image($image_file, 15.5, 9, 35, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);

        $this->SetFont('helvetica', 'B', 15);
        $this->setXY(55, 6.5);
        $this->Cell(0, 5, $l['FILENAME'] . " " . $this->year, 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $this->SetFont('helvetica', '', 10);
        $this->setXY(55, 13);
        $this->Cell(0, 5, $this->user->get()['name'], 0, false, 'L', 0, '', 0, false, 'T', 'M');
    }

    public function Footer()
    {
        global $lang;
        $l = $lang['YEARLY_INVOICE'];

        $this->SetY(-13);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 5, $l['PAGE'] . ' ' . $this->getAliasNumPage() . ' ' . $l['OF'] . ' ' . $this->getAliasNbPages(), 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $this->Cell(0, 5, $l['GENERATED'] . ' ' . date("d.m.Y"), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}
