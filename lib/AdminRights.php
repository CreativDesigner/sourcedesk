<?php

// Class for admin right system

class AdminRights
{

    // Make whitelist password on construct
    public function __construct()
    {
        global $CFG, $db, $sec;

        if (empty($CFG['ADMIN_WHITELIST_PW'])) {
            if (!($sec instanceof Security)) {
                $sec = new Security;
            }

            $newPw = $sec->generatePassword(8, false, "d");
            $newPwEscaped = encrypt($db->real_escape_string($newPw));
            $db->query("UPDATE settings SET `value` = '$newPwEscaped' WHERE `key` = 'admin_whitelist_pw' LIMIT 1");
            $CFG['ADMIN_WHITELIST_PW'] = $newPw;
        }

        $this->rights = [];
        $sql = $db->query("SELECT ID, rights FROM admins");
        while ($row = $sql->fetch_object()) {
            $this->rights[$row->ID] = unserialize($row->rights);
        }

    }

    // Returns all available rights with name
    public static function get()
    {
        global $CFG, $adminInfo;

        $lang = $CFG['LANG'];
        if (is_object($adminInfo)) {
            $lang = $adminInfo->language;
        }

        if (file_exists($path = __DIR__ . "/../languages/admin." . basename($lang) . ".php")) {
            require $path;
        } else {
            require __DIR__ . "/../languages/admin.english.php";
        }

        return $lang['ADMIN_RIGHTS'];
    }

    // Check right for admin @var i
    public function check($r, $i = 0)
    {
        $arr = $this->getArray($i);
        return in_array($r, $arr);
    }

    // Method to get array with right IDs
    public function getArray($i = 0)
    {
        global $db, $adminInfo, $CFG;
        if ($i == 0) {
            $i = (int) $adminInfo->ID;
        }

        return array_key_exists($i, $this->rights) ? $this->rights[$i] : [];
    }

    // Method to check for IP blocking

    public function otpAccess($otp)
    {
        global $db, $CFG, $session;

        if ($otp != $CFG['ADMIN_WHITELIST_PW'] || !trim($CFG['ADMIN_WHITELIST_PW'])) {
            return false;
        }

        $session->set('admin_otp', time() + 86400);
        $db->query("UPDATE settings SET `value` = '' WHERE `key` = 'admin_whitelist_pw' LIMIT 1");
        return true;
    }

    // Method to check one time password

    public function otpCheck()
    {
        return !$this->accessAllowed(false, false);
    }

    // Method to check if OTP is used

    public function accessAllowed($allowed = false, $otp = true)
    {
        global $db, $CFG, $session;

        if (!is_array($allowed)) {
            $allowed = unserialize($CFG['ADMIN_WHITELIST']);
        }

        if (!is_array($allowed) || count($allowed) == 0) {
            return true;
        }

        if ($session->get('admin_otp') != null && $session->get('admin_otp') > time() && $otp) {
            return true;
        }

        $ip = ip();

        foreach ($allowed as $aip) {
            if (filter_var($aip, FILTER_VALIDATE_IP) === false) {
                $aip = gethostbyname($aip);
            }

            if ($aip == $ip || ($aip == "127.0.0.1" && $ip == "::1")) {
                return true;
            }

        }

        return false;
    }
}
