<?php
global $lang, $ari, $var, $tpl, $CFG, $db;

$l = $lang['SOCIAL_MEDIA'];

if ($ari->check(68)) {
    menu("cms");
    title($l['TITLE']);

    $tpl = "social_media";
    $var['l'] = $l;

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case "save_sm":
                unset($_POST['action']);

                foreach ($_POST as $k => $v) {
                    $k = "sm_" . $k;
                    if (array_key_exists(strtoupper($k), $CFG)) {
                        $CFG[strtoupper($k)] = $v;
                        $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string(encrypt($v)) . "' WHERE `key` = '" . $db->real_escape_string($k) . "'");
                    }
                }

                $var['suc'] = $l['SAVED'];
                break;

            case "fb_post":
                if (SocialMedia::facebookPost($_POST['fb_post'])) {
                    $var['suc'] = $l['FB_OK'];
                } else {
                    $var['err'] = $l['FAIL'];
                }
                break;

            case "twitter_post":
                if (SocialMedia::twitterPost($_POST['twitter_post'])) {
                    $var['suc'] = $l['TWITTER_OK'];
                } else {
                    $var['err'] = $l['FAIL'];
                }
                break;
        }
    }

    $var['cfg'] = $CFG;
}
