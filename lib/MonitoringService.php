<?php

interface MonitoringService {
    public static function getName();
    public static function getSettings();
    public static function check($settings);
}

class MonitoringPing implements MonitoringService {
    public static function getName() {
        return "Ping";
    }

    public static function getSettings() {
        return [
            "IP-Adresse",
        ];
    }

    public static function check($settings) {
        $ping = new Ping($settings["IP-Adresse"]);
        return $ping->ping() !== false ? true : "Ping failed";
    }
}

class MonitoringHttp implements MonitoringService {
    public static function getName() {
        return "HTTP";
    }

    public static function getSettings() {
        return [
            "URL",
            "Erwartete Antwort",
        ];
    }

    public static function check($settings) {
        @$res = file_get_contents($settings["URL"]);
        if ($res === false) {
            return trim(explode(":", error_get_last()["message"], 2)[1]);
        }

        if (substr($settings["Erwartete Antwort"], 0, 1) . substr($settings["Erwartete Antwort"], -1) == "%%") {
            $settings["Erwartete Antwort"] = trim($settings["Erwartete Antwort"], "%");

            if (strpos($res, $settings["Erwartete Antwort"]) === false) {
                return "Unexpected answer: '" . htmlentities($res) . "'";
            }
        } else {
            if ($res != $settings["Erwartete Antwort"]) {
                return "Unexpected answer: '" . htmlentities($res) . "'";
            }
        }

        return true;
    }
}

class MonitoringPort implements MonitoringService {
    public static function getName() {
        return "Port";
    }

    public static function getSettings() {
        return [
            "IP-Adresse",
            "Port",
        ];
    }

    public static function check($settings) {
        if (!@fsockopen($settings["IP-Adresse"], $settings["Port"], $errno, $errstr, 5)) {
            return $errstr;
        }

        return true;
    }
}

class MonitoringFtp implements MonitoringService {
    public static function getName() {
        return "FTP";
    }

    public static function getSettings() {
        return [
            "Host",
            "Port",
            "Benutzername",
            "Passwort",
        ];
    }

    public static function check($settings) {
        $ftp = @ftp_connect($settings["Host"], $settings["Port"], 2);

        if (!$ftp) {
            return trim(error_get_last()["message"]);
        }

        if (empty($settings["Benutzername"])) {
            return true;
        }

        if (!ftp_login($ftp, $settings["Benutzername"], $settings["Passwort"])) {
            return trim(explode(":", error_get_last()["message"], 2)[1]);
        }

        return true;
    }
}

class MonitoringPop implements MonitoringService {
    public static function getName() {
        return "POP3";
    }

    public static function getSettings() {
        return [
            "Host",
            "Port",
            "Benutzername",
            "Passwort",
        ];
    }

    public static function check($settings) {
        $mbox = @imap_open("{" . $settings['Host'] . ":" . $settings['Port'] . "/pop3}INBOX", $settings["Benutzername"], $settings["Passwort"]);

        if(!$mbox) {
            return trim(explode(":", error_get_last()["message"], 2)[1]);
        }

        @imap_close($mbox);

        return true;
    }
}

class MonitoringImap implements MonitoringService {
    public static function getName() {
        return "IMAP";
    }

    public static function getSettings() {
        return [
            "Host",
            "Port",
            "Benutzername",
            "Passwort",
        ];
    }

    public static function check($settings) {
        $mbox = @imap_open("{" . $settings['Host'] . ":" . $settings['Port'] . "/imap}INBOX", $settings["Benutzername"], $settings["Passwort"]);

        if(!$mbox) {
            return trim(explode(":", error_get_last()["message"], 2)[1]);
        }

        @imap_close($mbox);

        return true;
    }
}

class MonitoringSmtp implements MonitoringService {
    public static function getName() {
        return "SMTP";
    }

    public static function getSettings() {
        return [
            "Host",
            "Port",
            "Benutzername",
            "Passwort",
        ];
    }

    public static function check($settings) {
        $mail = new PHPMailer(true);
        $mail->SMTPAuth = true;
        $mail->Username = $settings["Benutzername"];
        $mail->Password = $settings["Passwort"];
        $mail->Host = $settings["Host"];
        $mail->Port = $settings["Port"];
        $mail->Timeout = 2;

        try {
            if(!$mail->SmtpConnect()) {
                return "Connection failed";
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        return true;
    }
}