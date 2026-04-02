<?php
global $ari, $db, $CFG, $proj, $dfo, $lang;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if ($ari->check(31)) {
    $sql = $db->query("SELECT * FROM projects WHERE ID = " . intval($_GET['id']));
    if ($sql->num_rows != 1) {
        $tpl = "error";
    } else {
        $proj = $sql->fetch_object();

        require_once __DIR__ . "/../../lib/tcpdf/tcpdf.php";
        class ProjectTimePdf extends TCPDF
        {
            public function Header()
            {
                global $proj, $lang;

                $image_file = file_exists(__DIR__ . "/../../themes/invoice-logo.jpg") ? __DIR__ . "/../../themes/invoice-logo.jpg" : (file_exists(__DIR__ . "/../../themes/invoice-logo.png") ? __DIR__ . "/../../themes/invoice-logo.png" : "");
                $this->Image($image_file, 15.5, 9, '', 8, '', '', 'T', false, 300, '', false, false, 0, false, false, false);

                $this->SetFont('helvetica', 'B', 15);
                $this->setXY(55, 6.5);
                $this->Cell(0, 5, $proj->name, 0, false, 'R', 0, '', 0, false, 'T', 'M');

                $this->SetFont('helvetica', '', 10);
                $this->setXY(55, 13);
                $this->Cell(0, 5, $lang['PROJECTPDF']['TITLE'], 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }

            public function Footer()
            {
                global $lang, $dfo;
                $this->SetY(-13);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 5, $lang['PROJECTPDF']['SITE1'] . ' ' . $this->getAliasNumPage() . ' ' . $lang['PROJECTPDF']['SITE2'] . ' ' . $this->getAliasNbPages(), 0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->Cell(0, 5, $lang['PROJECTPDF']['CREATED'] . ' ' . $dfo->format(time(), true, true), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }

            public static function init($positions)
            {
                global $CFG, $proj, $lang;

                $pdf = new ProjectTimePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor($CFG['PAGENAME']);
                $pdf->SetTitle($proj->name . " - " . $lang['PROJECTPDF']['TITLE']);

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

                $tbl = '<table><tr><th style="font-weight: bold; width: 30%;">' . $lang['PROJECTPDF']['EX'] . '</th><th style="font-weight: bold; width: 25%;">' . $lang['PROJECTPDF']['ST'] . '</th><th style="font-weight: bold; width: 20%;">' . $lang['PROJECTPDF']['EN'] . '</th><th style="font-weight: bold; text-align: right; width: 25%;">' . $lang['PROJECTPDF']['MI'] . '</th></tr>';
                foreach ($positions as $p) {
                    $tbl .= '<tr><td>' . mb_strimwidth($p[0], 0, 30, "...") . '</td><td>' . $p[1] . '</td><td>' . $p[2] . '</td><td style="text-align: right;">' . $p[3] . '</td></tr>';
                }

                $tbl .= '</table>';
                $pdf->writeHTML($tbl, true, false, false, false, '');

                return $pdf;
            }
        }

        $arr = $admins = $tasks = [];

        $sql = $db->query("SELECT * FROM project_tasks WHERE project = {$proj->ID}");
        while ($row = $sql->fetch_object()) {
            $tasks[$row->ID] = $row->name;
        }

        $sql = $db->query("SELECT * FROM admins");
        while ($row = $sql->fetch_object()) {
            $admins[$row->ID] = $row->name;
        }

        $tasks[$proj->ID / -1] = "-";

        $sql = $db->query("SELECT * FROM project_times WHERE task IN (" . implode(",", array_keys($tasks)) . ")");
        while ($row = $sql->fetch_object()) {
            array_push($arr, [htmlentities($tasks[$row->task]), $dfo->format($row->start, true, true), $dfo->format($row->end, true, true), htmlentities($admins[$row->admin])]);
        }

        alog("project", "time_pdf_generated", $proj->ID);
        $pdf = ProjectTimePdf::init($arr);
        $pdf->output($lang['PROJECTPDF']['TITLE'] . "-" . $proj->ID, "I");
    }
} else {
    alog("general", "insufficient_page_rights", "project_time_pdf");
    $tpl = "error";
}
