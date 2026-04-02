<?php

// Class for stopping bruteforce attacks

class Fail2Ban
{

    // Constructor checks if client should be banned
    public function __construct()
    {
        $this->checkClient();
    }

    // Will be called if any login activity failed

    private function checkClient()
    {
        global $CFG, $db, $_GET;

        $this->clearDatabase();

        if (basename($_GET['p']) != "locked") {
            if ($this->getFailed($this->clientIP()) >= $CFG['FAIL2BAN_FAILED'] && $CFG['FAIL2BAN_ACTIVE']) {
                if (isset($_POST['ajax'])) {
                    die("blocked");
                }

                header('Location: ' . $CFG['PAGEURL'] . 'locked');
                exit;
            }

            if (false !== $this->getBlacklist($this->clientIP())) {
                if (isset($_POST['ajax'])) {
                    die("blocked");
                }

                header('Location: ' . $CFG['PAGEURL'] . 'locked');
                exit;
            }
        }
    }

    // This method returns the clients IP address

    private function clearDatabase()
    {
        global $db, $CFG;

        $db->query("DELETE FROM fail2ban WHERE until <= " . time());
    }

    // This method clears all old records from database

    public function getFailed($ip)
    {
        if (($rslt = $this->getInfo($ip)) === false) {
            return 0;
        }

        return $rslt->failed;
    }

    // This method returns the failed tries of an IP

    private function getInfo($ip)
    {
        global $db, $CFG;

        $stmt = $db->query("SELECT * FROM fail2ban WHERE ip = '" . $db->real_escape_string($ip) . "'");
        if ($stmt->num_rows != 1) {
            return false;
        }

        return $stmt->fetch_object();
    }

    // This method returns the time until a IP is locked

    public function clientIP()
    {
        return ip();
    }

    // Method returns an object for an IP address

    public function getBlacklist($ip)
    {
        global $db, $CFG;

        $blacklist = $db->query("SELECT * FROM blacklist_ip WHERE ip = '" . $db->real_escape_string($ip) . "' LIMIT 1");
        if ($blacklist->num_rows != 1) {
            return false;
        }

        return $blacklist->fetch_object();
    }

    // Method to get blacklist information

    public function failedLogin()
    {
        global $db, $CFG, $session;

        if ($session->get('admin') == 1) {
            return;
        }

        $clientIP = $this->clientIP();
        $tries = $this->getFailed($clientIP);
        $until = time() + (60 * $CFG['FAIL2BAN_LOCKED']);

        if ($tries > 0) {
            $db->query("UPDATE fail2ban SET failed = " . ++$tries . ", until = $until WHERE ip = '" . $db->real_escape_string($clientIP) . "' LIMIT 1");
        } else {
            $db->query("INSERT INTO fail2ban (`ip`, `failed`, `until`) VALUES ('" . $db->real_escape_string($clientIP) . "', 1, $until)");
        }

        $this->checkClient();
    }

    // Method to check if client should be locked out

    public function getUntil($ip)
    {
        if (($rslt = $this->getInfo($ip)) === false) {
            return 0;
        }

        return $rslt->until;
    }

}
