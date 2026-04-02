<?php

class Pingen extends LetterProvider
{
    protected $short = "pingen";
    protected $name = "Pingen";
    protected $version = "1.2";

    public function getSettings()
    {
        return array(
            "key" => array("type" => "password", "name" => $this->getLang("key")),
        );
    }

    public function sendLetter($pdfPath, $color = true, $country = "DE", $type = 1)
    {
        if (!array_key_exists($type, $this->getTypes())) {
            return false;
        }

        $this->modifyPdf($pdfPath);

        $data = [
            "file" => class_exists("CURLFile") ? new CURLFile($pdfPath) : "@" . $pdfPath,
            "send" => true,
            "speed" => $type,
            "color" => $color ? 1 : 0,
            "duplex" => 1,
            "rightaddress" => 0,
        ];

        if ($type == 3) {
            $data = [
                "file" => class_exists("CURLFile") ? new CURLFile($pdfPath) : "@" . $pdfPath,
                "send" => false,
                "rightaddress" => 0,
            ];
        }

        $url = "https://api.pingen.com/document/upload/token/{$this->options->key}/";
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $r = json_decode(curl_exec($c), true);
        curl_close($c);

        return $r['error'] === false;
    }

    public function getTypes()
    {
        return array(
            "1" => $this->getLang("l1"),
            "2" => $this->getLang("l2"),
            "3" => $this->getLang("l3"),
        );
    }

    protected function modifyPdf($pdfPath)
    {
        require_once __DIR__ . "/../../../lib/fpdi/fpdi.php";

        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(14);

        $pageCount = $pdf->setSourceFile($pdfPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $pageId = $pdf->importPage($i);
            $pdf->addPage();
            $pdf->useTemplate($pageId);

            $pdf->Line(0, 0, 0, 297);
        }

        $pdf->Output($pdfPath, "F");
    }
}
