<?php

// Class for applying micropatches

class Micropatch
{
    public static function avail()
    {
        global $CFG, $db;

        $res = @file_get_contents("https://sourceway.de/de/sourcedesk_mp/" . urlencode($CFG['LICENSE_KEY']) . "/" . urlencode($CFG['PATCHLEVEL']));
        if (!$res) {
            return false;
        }

        $res = @json_decode($res, true);
        if (!is_array($res)) {
            return false;
        }

        return intval(max(array_keys($res)));
    }

    public static function apply()
    {
        global $CFG, $db;

        $res = @file_get_contents("https://sourceway.de/de/sourcedesk_mp/" . urlencode($CFG['LICENSE_KEY']) . "/" . urlencode($CFG['PATCHLEVEL']));
        if (!$res) {
            return false;
        }

        $res = @json_decode($res, true);
        if (!is_array($res)) {
            return false;
        }

        $rsa = new phpseclib\Crypt\RSA;
        $rsa->load(Update::signKey());

        foreach ($res as $id => $file) {
            $file = base64_decode($file);

            if ($rsa->verify(hash("sha512", substr($file, 512)), substr($file, 0, 512))) {
                file_put_contents($path = __DIR__ . "/micropatch.zip", substr($file, 512));

                $zip = new ZipArchive;
                $zip->open($path);

                if (is_dir($dir = __DIR__ . "/micropatch")) {
                    Update::deleteDirectory($dir);
                }

                mkdir($dir);
                $zip->extractTo($dir);
                $zip->close();
                unlink($path);

                if (file_exists($dir . "/dump.sql")) {
                    $db->multi_query(str_replace("%p", "", file_get_contents($dir . "/dump.sql")));
                    unlink($dir . "/dump.sql");
                }

                foreach (Update::glob_recursive($dir . "/*") as $f) {
                    if ($f == realpath($dir . "/dump.sql")) {
                        continue;
                    }

                    $rel = ltrim(substr($f, strlen($dir)), DIRECTORY_SEPARATOR);
                    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $rel, file_get_contents($f));
                }

                Update::deleteDirectory($dir);
            }
        }

        $patchlevel = intval(max(array_keys($res)));
        if ($patchlevel > intval($CFG['PATCHLEVEL'])) {
            $db->query("UPDATE settings SET `value` = '$patchlevel' WHERE `key` = 'patchlevel'");
        }
        return true;
    }
}
