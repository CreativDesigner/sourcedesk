<?php

// Function for formatting ctime
function format_ctime($t)
{
    global $lang;

    $ex = explode(" ", $t, 2);
    $i = intval($ex[0]);
    $u = $ex[1];

    switch ($u) {
        case "days":
            return $i . " " . $lang['PRODUCTS']['DAY' . ($i > 1 ? "S" : "")];

        case "months":
            return $i . " " . $lang['PRODUCTS']['MONTH' . ($i > 1 ? "S" : "")];
    }

    return $i . " " . $lang['PRODUCTS']['YEAR' . ($i > 1 ? "S" : "")];
}

// Functions for getting IP of client
function ip()
{
    global $db, $CFG;

    if (!array_key_exists("IP_HEADER", $CFG)) {
        $db->query("INSERT INTO settings (`key`, `value`) VALUES ('ip_header', 'REMOTE_ADDR')");
        $CFG['IP_HEADER'] = "REMOTE_ADDR";
    }

    if (empty($h = $CFG['IP_HEADER']) || !array_key_exists($h, $_SERVER)) {
        $h = "REMOTE_ADDR";
    }

    return $_SERVER[$h];
}

// Function for formatting a time in seconds user friendly
function formatTime($time, $isBilled = null)
{
    global $lang;

    if (floor($time / 3600) > 0) {
        $str = floor($time / 3600) . (floor($time / 3600) == 1 ? " " . $lang['TIME']['1H'] : " " . $lang['TIME']['XH']);
        $minutes = round(($time - floor($time / 3600) * 3600) / 60);
        if ($minutes > 0) {
            $str .= ", " . $minutes . ($minutes == 1 ? " " . $lang['TIME']['1M'] : " " . $lang['TIME']['XM']);
        }

    } else if (floor($time / 60) > 0) {
        $str = round($time / 60) . (round($time / 60) == 1 ? " " . $lang['TIME']['1M'] : " " . $lang['TIME']['XM']);
    } else {
        $str = $time . ($time == 1 ? " " . $lang['TIME']['1S'] : " " . $lang['TIME']['XS']);
    }

    if ($isBilled) {
        return "<font color='green'>$str</font>";
    } else if (!$isBilled && $isBilled !== null) {
        return "<font color='red'>$str</font>";
    } else {
        return $str;
    }

}

// Functions for making URL automatically clickable
function _make_url_clickable_cb($matches)
{
    $ret = '';
    $url = $matches[2];

    if (empty($url)) {
        return $matches[0];
    }

    // removed trailing [.,;:] from URL
    if (in_array(substr($url, -1), array('.', ',', ';', ':')) === true) {
        $ret = substr($url, -1);
        $url = substr($url, 0, strlen($url) - 1);
    }
    return $matches[1] . "<a target=\"_blank\" href=\"$url\" rel=\"nofollow\">$url</a>" . $ret;
}

function _make_web_ftp_clickable_cb($matches)
{
    $ret = '';
    $dest = $matches[2];
    $dest = 'http://' . $dest;

    if (empty($dest)) {
        return $matches[0];
    }

    // removed trailing [,;:] from URL
    if (in_array(substr($dest, -1), array('.', ',', ';', ':')) === true) {
        $ret = substr($dest, -1);
        $dest = substr($dest, 0, strlen($dest) - 1);
    }
    return $matches[1] . "<a target=\"_blank\" href=\"$dest\" rel=\"nofollow\">$dest</a>" . $ret;
}

function _make_email_clickable_cb($matches)
{
    $email = $matches[2] . '@' . $matches[3];
    return $matches[1] . "<a target=\"_blank\" href=\"mailto:$email\">$email</a>";
}

function make_clickable($ret)
{
    $ret = ' ' . $ret;
    // in testing, using arrays here was found to be faster
    $ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_url_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_web_ftp_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $ret);

    // this one is not in an array because we need it to run last, for cleanup of accidental links within links
    $ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
    $ret = trim($ret);
    return $ret;
}

function unsl($arr)
{
    global $CFG;

    $uns = unserialize($arr);
    if (!is_array($uns) || !array_key_exists($CFG['LANG'], $uns)) {
        return $arr;
    } else {
        return $uns[$CFG['LANG']];
    }
}
