<?php

class EmailLetter extends LetterProvider
{
    protected $short = "email";
    protected $name = "E-Mailversand";
    protected $version = "1.0";

    public function getSettings()
    {
        return array(
            "recipient" => array("type" => "text", "name" => $this->getLang("recipient")),
            "subject" => array("type" => "text", "name" => $this->getLang("subject")),
        );
    }

    public function sendLetter($pdfPath, $color = true, $country = "DE", $type = 0)
    {
        global $maq, $CFG;

        if (!array_key_exists($type, $this->getTypes()) || !filter_var($this->options->recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = $maq->enqueue([], null, $this->options->recipient, $this->options->subject, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", 0, false, 0, 0, array("letter.pdf" => $pdfPath));
        if (!$id) {
            return false;
        }

        if (!$maq->send(1, $id, true, false)) {
            return false;
        }

        $maq->delete($id);

        return true;
    }

    public function getTypes()
    {
        return array(
            "0" => $this->getLang("name"),
        );
    }
}
