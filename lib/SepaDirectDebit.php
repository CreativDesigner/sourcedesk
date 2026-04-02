<?php

class SepaDirectDebit
{
    public static function active()
    {
        global $CFG;
        $g = unserialize($CFG['ACTIVE_GATEWAYS']);
        return is_array($g) && in_array("sdd", $g);
    }

    public static function mandate($id)
    {
        global $db, $CFG;
        $sql = $db->query("SELECT * FROM client_sepa WHERE ID = " . intval($id));
        if ($sql->num_rows != 1) {
            return false;
        }

        return new SepaMandate($sql->fetch_object());
    }

    public static function transaction($id)
    {
        global $db, $CFG;
        $sql = $db->query("SELECT * FROM client_transactions WHERE ID = " . intval($id));
        if ($sql->num_rows != 1) {
            return false;
        }

        return new SepaTransaction($sql->fetch_object());
    }

    public static function clientMandates($id)
    {
        global $db, $CFG;
        $status = false;
        $sql = $db->query("SELECT ID FROM client_sepa WHERE client = " . intval($id) . " AND status = 1 ORDER BY ID DESC");
        while ($row = $sql->fetch_object()) {
            $mandate = self::mandate($row->ID);
            if ($mandate->expired()) {
                $status = "expired";
            } else {
                return true;
            }
        }
        return $status;
    }

    public static function mandateByClient($id)
    {
        global $db, $CFG;

        $u = User::getInstance($id, "ID");
        if (false === $u || $u->get()['sepa_fav'] == 0) {
            return false;
        }

        $mandate = self::mandate($u->get()['sepa_fav']);
        if ($mandate !== false && $mandate->isActive()) {
            return $mandate;
        }

        return false;
    }

    public static function getPaymentDate()
    {
        global $db, $CFG;

        $days = intval(decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'prenotification_days'")->fetch_object()->value) ?: "14");
        $time = strtotime("+$days days");

        while (date("N", $time) >= 6) {
            $time += 86400;
        }

        return $time;
    }

    public static function create($mandate, $amount, $reference = "")
    {
        global $db, $CFG, $maq, $nfo, $dfo, $cur;

        if (!($mandate instanceof SepaMandate)) {
            return false;
        }

        $subject = "sdd|" . $mandate->getID() . "-" . ($mandate->getLastTID() + 1);

        $sql = $db->prepare("INSERT INTO client_transactions (`user`, `time`, `subject`, `amount`, `sepa_done`, `payment_reference`) VALUES (?, ?, ?, ?, ?, ?)");
        $sql->bind_param("iisdis", $a = $mandate->getUser(), $b = time(), $subject, $amount, $c = 0, $reference);
        $sql->execute();

        $sql = $db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'prenotification' ORDER BY ID DESC LIMIT 1");
        if ($sql->num_rows == 1) {
            $id = decrypt($sql->fetch_object()->value);

            if (!$id) {
                return true;
            }

            $user = new User($mandate->getUser(), "ID");
            $language = $user->getLanguage();
            $name = $user->get()['name'];
            $df = $user->getDateFormat();

            $mtObj = new MailTemplate($id);

            if (!$mtObj->isInit()) {
                return;
            }

            $title = $mtObj->getTitle($language);
            $mail = $mtObj->getMail($language, $name);

            $maq->enqueue([
                "ci" => decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'ci'")->fetch_object()->value) ?: "",
                "reference" => $mandate->getID(),
                "iban" => substr($mandate->getIBAN(), 0, -5) . "*****",
                "bic" => $mandate->getBIC(),
                "duedate" => $dfo->format(self::getPaymentDate(), 0, 0, '', $df),
                "amount" => $cur->infix($nfo->format($cur->convertAmount($cur->getBaseCurrency(), $amount, $user->getCurrency()), 2, 0, $user->getNumberFormat()), $user->getCurrency()),
            ], $mtObj, $user->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($language));
        }

        return true;
    }
}

class SepaMandate
{
    protected $info;

    public function __construct($info)
    {
        $this->info = $info;
    }

    public function getLastTID()
    {
        global $db, $CFG;

        $sql = $db->query("SELECT subject FROM client_transactions WHERE subject LIKE 'sdd|" . $db->real_escape_string($this->info->ID) . "-%' ORDER BY LENGTH(subject) DESC, subject DESC LIMIT 1");
        if ($sql->num_rows != 1) {
            return 0;
        }

        return explode("-", $sql->fetch_object()->subject)[1];
    }

    public function getID()
    {
        return $this->info->ID;
    }

    public function getUser()
    {
        return $this->info->client;
    }

    public function getUserObj()
    {
        return User::getInstance($this->getUser(), "ID");
    }

    public function getIBAN()
    {
        return $this->info->iban;
    }

    public function getBIC()
    {
        return $this->info->bic;
    }

    public function getStatus()
    {
        return $this->info->status;
    }

    public function getAccountHolder()
    {
        return $this->info->account_holder;
    }

    public function getStripe()
    {
        return $this->info->stripe;
    }

    public function isActive()
    {
        if ($this->info->status != 1) {
            return false;
        }

        return !$this->expired();
    }

    public function setStripe($s)
    {
        global $db, $CFG;
        $this->info->stripe = $s;
        $db->query("UPDATE client_sepa SET stripe = '" . $db->real_escape_string($s) . "' WHERE ID = " . $this->info->ID);
    }

    public function expired()
    {
        global $db, $CFG;

        $sql = $db->query("SELECT `time` FROM client_transactions WHERE subject LIKE 'sdd|" . $db->real_escape_string($this->info->ID) . "-%' ORDER BY time DESC LIMIT 1");
        if ($sql->num_rows != 1) {
            $date = max(strtotime($this->info->date), strtotime($this->info->last_used));
        } else {
            $date = max($sql->fetch_object()->time, strtotime($this->info->date), strtotime($this->info->last_used));
        }

        return strtotime("+36 months", $date) < time();
    }

    public function getDate()
    {
        return $this->info->date;
    }

    public function downloadPdf()
    {
        global $dfo, $gateways, $CFG, $db;

        $lang = $gateways->get()['sdd']->getLang();

        require_once __DIR__ . "/tcpdf/tcpdf.php";
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setTitle($lang['MANDATE'] . " " . $this->info->ID);

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
            $pdf->Image($logo, 0, 6, 0, 20, '', '', '', false, 300, 'R');
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

        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetXY(24, 60);
        $pdf->SetFont('helvetica', 'B', 9);

        $ex = explode("\n", $CFG['PDF_RECIPIENT']);
        foreach ($ex as $l) {
            if (false !== ($pos = strpos($l, "%"))) {
                $identifier = substr($l, $pos + 1);
                $pos2 = strpos($identifier, "%");
                if (false !== $pos2) {
                    $identifier = strtoupper(substr($identifier, 0, $pos2));
                    if (array_key_exists($identifier, $lang)) {
                        $l = substr($l, 0, $pos) . html_entity_decode($lang[$identifier]) . substr($l, $pos + $pos2 + 2);
                    }
                }
            }

            $pdf->SetX(24);
            $pdf->Cell(0, 0, $l, 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
        }

        $pdf->SetXY(135, 90);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $lang['DATE'] . ": " . $dfo->format($this->info->date, false), 0, 1, 'R');

        $pdf->SetXY(24, 100);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 0, $lang['SUBJECT'], 0, 1, 'L');

        $pdf->SetXY(25, 110);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->writeHTML("<b>" . $lang['CI'] . ":</b> " . (decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'ci'")->fetch_object()->value) ?: ""));

        $pdf->SetX(25);
        $pdf->writeHTML("<b>" . $lang['REFERENCE'] . ":</b> " . $this->getUser() . "-" . $this->getID());
        $pdf->writeHTML("");

        $pdf->SetX(24);
        $pdf->MultiCell(0, 0, str_replace("%p", $CFG['PAGENAME'], $lang['INTRO']));

        $pdf->SetX(24);
        $pdf->MultiCell(0, 0, "");

        $pdf->SetX(24);
        $pdf->MultiCell(0, 0, str_replace("%p", $CFG['PAGENAME'], $lang['HINT']));
        $pdf->MultiCell(0, 0, "");

        if ((decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'prenotification_days'")->fetch_object()->value) ?: "14") < 14) {
            $pdf->SetX(24);
            $pdf->MultiCell(0, 0, str_replace("%d", decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'prenotification_days'")->fetch_object()->value) ?: "14", $lang['PREHINT']));
            $pdf->MultiCell(0, 0, "");
        }

        $pdf->SetX(25);
        $pdf->writeHTML("<b>" . $lang['ACCOUNTHOLDER'] . ":</b> " . $this->getAccountHolder());

        $pdf->SetX(25);
        $pdf->writeHTML("<b>" . $lang['IBAN'] . ":</b> " . $this->getIBAN());

        $pdf->SetX(25);
        $pdf->writeHTML("<b>" . $lang['BIC'] . ":</b> " . $this->getBIC());

        $pdf->SetLineWidth(0.2);
        $pdf->SetLineStyle(array('color' => array(0, 0, 0)));
        $pdf->Line(25, 200, 100, 200);

        $pdf->SetXY(24, 201);
        $pdf->Cell(0, 0, $lang['SIGNATURE'], 0, 1, 'L');

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

        $pdf->output($lang['MANDATE'] . "-" . $this->info->ID . ".pdf", "I");
    }
}

class SepaTransaction
{
    protected $info;

    public function __construct($info)
    {
        $this->info = $info;
    }

    public function getDueDate()
    {
        global $db, $CFG;
        $days = intval(decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'prenotification_days'")->fetch_object()->value) ?: "14");
        return strtotime("+$days days", $this->info->time);
    }

    public function getTID()
    {
        $subject = $this->info->subject;
        return explode("|", $subject)[1];
    }

    public function getMandate()
    {
        $subject = $this->info->subject;
        $info = explode("|", $subject)[1];
        return SepaDirectDebit::mandate(explode("-", $info)[0]);
    }

    public function getAmount()
    {
        return $this->info->amount;
    }

    public function getSubject()
    {
        global $db, $CFG;

        if ($paymentReference = ($this->info->payment_reference ?? "")) {
            $paymentReference .= " ";
        }

        return $this->getTID() . " " . $paymentReference . (decrypt($db->query("SELECT `value` FROM gateway_settings WHERE gateway = 'sdd' AND setting = 'subject'")->fetch_object()->value) ?: "");
    }
}
