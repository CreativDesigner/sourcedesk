<?php
require_once __DIR__ . "/fpdi/fpdi.php";

class PDFConcat extends FPDI
{
    public $files = array();

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function concat()
    {
        foreach ($this->files as $file) {
            $pagecount = $this->setSourceFile($file);
            for ($i = 1; $i <= $pagecount; $i++) {
                $this->setPrintHeader(false);
                $this->setPrintFooter(false);
                $tplidx = $this->ImportPage($i);
                $s = $this->getTemplatesize($tplidx);
                $this->AddPage('P', array($s['w'], $s['h']));
                $this->useTemplate($tplidx);
                $this->setPrintHeader(false);
                $this->setPrintFooter(false);
            }
        }
    }
}
