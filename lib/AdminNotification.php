<?php

// Class for admin notifications

class AdminNotification
{
    public $data = array();
    public $template = null;

    public function __construct($template)
    {
        if ($template instanceof MailTemplate) {
            $this->template = $template;
        }

    }

    public static function getInstance($name)
    {
        global $db, $CFG;

        if (!is_string($name) || !(($template = new MailTemplate($name)) instanceof MailTemplate) || !$template->isInit()) {
            return false;
        }

        return new AdminNotification($template);
    }

    public function set($k, $v)
    {
        if (is_string($k) || is_array($v)) {
            $this->data[$k] = $v;
        }

        return $this;
    }

    public function send()
    {
        global $db, $CFG, $maq, $val, $languages, $raw_cfg;
        $this->set("pageurl", $raw_cfg['PAGEURL']);
        $templateName = trim($this->template->getName());

        $sql = $db->query("SELECT * FROM admins");
        while ($row = $sql->fetch_object()) {
            if (!$val->email($row->email)) {
                continue;
            }

            if (empty($row->language) || !isset($languages[$row->language])) {
                $row->language = $CFG['LANG'];
            }

            $notifications = unserialize($row->notifications);
            if (false === $notifications || !is_array($notifications) || !in_array($templateName, $notifications)) {
                continue;
            }

            $short = $this->template->getName();
            if ($row->language != "deutsch") {
                $short = $this->template->getForeignName();
            }

            if ($short) {
                Notification::create($short, $row->ID);
            }

            $title = $this->template->getTitle($row->language);
            $mail = $this->template->getMail($row->language, $row->name);

            $ex = explode("--Telegram--", $mail);
            $mail = $ex[0];

            foreach ($this->data as &$v) {
                if (is_array($v)) {
                    if (isset($v[$row->language]) && is_string($v[$row->language])) {
                        $v = $v[$row->language];
                    } else {
                        continue;
                    }

                }
            }
            unset($v);

            $maq->enqueue($this->data, null, $row->email, $title, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">");
        }

        if (in_array($templateName, unserialize($CFG['TELEGRAM_NOTIFICATIONS']))) {
            $mail = $this->template->getContent($CFG['LANG']);
            $ex = explode("--Telegram--", $mail);
            if (count($ex) == 1) {
                $mail = $ex[0];
            } else {
                $mail = array_pop($ex);
            }

            foreach ($this->data as $k => $v) {
                if (is_array($v)) {
                    if (isset($v[$CFG['LANG']]) && is_string($v[$CFG['LANG']])) {
                        $v = $v[$CFG['LANG']];
                    } else {
                        continue;
                    }

                }

                $mail = str_replace("%" . $k . "%", $v, $mail);
            }

            Telegram::sendMessage($mail);
        }
    }
}
