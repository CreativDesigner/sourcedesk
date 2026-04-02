<?php

// Class for performing updates

class Update
{
    private static function performSqlUpdate()
    {
        global $db, $CFG;

        $ds = new DatabaseStructure();
        $ds->init();
        $ds->deploy($db);
        $hash = $db->real_escape_string($ds->calcHash());
        $db->query("UPDATE settings SET `value` = '$hash' WHERE `key` = 'db_hash'");

        return true;
    }

    public static function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    public static function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, self::glob_recursive($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags));
        }
        foreach ($files as $k => &$v) {
            if (is_dir($v)) {
                unset($files[$k]);
            } else {
                $v = realpath($v);
            }
        }
        return $files;
    }

    public static function unzipArchive($manual = false)
    {
        $f = __DIR__ . "/update.zip";
        $d = __DIR__ . "/update";

        if (!file_exists($f)) {
            return false;
        }

        $zip = new ZipArchive;
        if ($zip->open($f) !== true) {
            return false;
        }

        if (is_dir($d)) {
            self::deleteDirectory($d);
        }

        mkdir($d);
        $zip->extractTo($d);
        $zip->close();
        unlink($f);

        return true;
    }

    public static function permCheck($manual = false)
    {
        foreach (self::glob_recursive(__DIR__ . "/update/*") as $f) {
            if ($f == realpath(__DIR__ . "/update/dump.sql")) {
                continue;
            }

            $rel = ltrim(substr($f, strlen(__DIR__ . "/update")), DIRECTORY_SEPARATOR);
            $ex = explode(DIRECTORY_SEPARATOR, $rel);
            $file = array_pop($ex);

            for ($i = 1; $i <= count($ex); $i++) {
                if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($ex, 0, $i)))) {
                    if (!mkdir(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($ex, 0, $i)))) {
                        return false;
                    }
                } else if (!is_writable(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($ex, 0, $i)))) {
                    return false;
                }
            }

            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $rel)) {
                if (!is_writable(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $rel)) {
                    return false;
                }
            } else {
                if (!file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $rel, "test")) {
                    return false;
                }
                if (!is_writable(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $rel)) {
                    return false;
                }
                if (!unlink(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $rel)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function autoUpdate($manual = false)
    {
        $disabledLanguages = [];
        foreach (glob(__DIR__ . "/../languages/.*.php") as $f) {
            $f = basename($f);
            $ex = explode(".", 2);
            if ($ex[1] != "php") {
                continue;
            }

            array_push($disabledLanguages, $ex[0]);
        }

        foreach (self::glob_recursive(__DIR__ . "/update/*") as $f) {
            if ($f == realpath(__DIR__ . "/update/dump.sql")) {
                continue;
            }

            $rel = ltrim(substr($f, strlen(__DIR__ . "/update")), DIRECTORY_SEPARATOR);
            file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $rel, file_get_contents($f));
        }

        foreach ($disabledLanguages as $dl) {
            $newPath = __DIR__ . "/../languages/$dl.php";
            $newPath2 = __DIR__ . "/../languages/$dl.admin.php";

            $oldPath = __DIR__ . "/../languages/.$dl.php";
            $oldPath2 = __DIR__ . "/../languages/.$dl.admin.php";

            if (file_exists($newPath)) {
                @unlink($oldPath);
                @rename($newPath, $oldPath);
            }

            if (file_exists($newPath2)) {
                @unlink($oldPath2);
                @rename($newPath2, $oldPath2);
            }
        }

        self::performSqlUpdate();

        self::deleteDirectory(__DIR__ . "/update");

        return true;
    }

    public static function getUpdate($manual = false)
    {
        global $CFG;

        $file = file_get_contents("https://sourceway.de/de/sourcedesk_update/" . urlencode($CFG['LICENSE_KEY']) . "/" . urlencode($CFG['VERSION']) . "?manual=" . intval($manual));
        if (substr($file, 0, 5) != "hiyu:") {
            return false;
        }

        file_put_contents(__DIR__ . "/update.zip", substr($file, 5));

        return file_get_contents(__DIR__ . "/update.zip") == substr($file, 5);
    }

    public static function verifySig($manual = false)
    {
        if (!file_exists(__DIR__ . "/update.zip")) {
            return false;
        }

        $c = file_get_contents(__DIR__ . "/update.zip");

        $rsa = new phpseclib\Crypt\RSA;
        $rsa->load(self::signKey());
        $res = $rsa->verify(hash("sha512", substr($c, 512)), substr($c, 0, 512));

        if ($res) {
            file_put_contents(__DIR__ . "/update.zip", substr($c, 512));
        }

        return $res;
    }

    public static function manualDownload()
    {
        $f = __DIR__ . "/update.zip";

        if (!file_exists($f)) {
            die("Update not found");
        }

        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=haseDESK-Update.zip");
        header("Content-length: " . filesize($f));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($f);
        unlink($f);
        exit;
    }

    final public static function signKey()
    {
        return "-----BEGIN PUBLIC KEY----- MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA2sfCX4vWIEJT+ao+6THH dqEMM5jxbit82KXFiMMaFwLci01/KL3pI6Ui9bD9y/pCt+uWljrAGferGBQl2nDo zpm/VwdHwEm+Sfe7EBlCpdMWAjZgMx+G+LdshOcVzrZ02eiSC0Dr3EX6ma7gkg/Y 79cAiCBcn8ojce6teLQuAkejwo3KDErUcmhcMuqEq7RjFoePxyYrQtIN1GDc/cjc YgxFn8cXbOlVr/+zbvDqKZKPHjuT0nPjAAiRGMh3OHjdBlNsTpuhzuxqnGjfDHy3 4Q0rXUjGqIhk30zyuhVVZ9Clo9/pWlL+Zlm7JBPxt56f4gtCif4lJeFSfADLEJmk vOIW8z50P6s41bErFnFkfLlZQDy9A9PzjYnRrAHqM/ZoEoCegZtbT7OFO4WDzJB6 Uim7VYV2vTgslRj4KyVKYIMwuvVONwTAZTINVy6Q9h9mshXMGpWlGVAZlqHs5viV Cm/NUrWN2Uj92VMIhc59UtOYgGxouY9XswPJgdNZFu7J7jSJ9WRVUpzF51nNirAs FNa4Bw3DwESbMV8ZtWGZDsp9BNium3Hknsr5vWN1M3E0V9ZMCqPx0IBkG8FGtPJ5 D4UTGBO4btFp9OxFfrJYBzOJ44iH9Oer7lydUWJZ4ZR9eEFI1SF4ugskle1HcuIP AwZzAS7ytAF8HTkXZUqgaBUCAwEAAQ== -----END PUBLIC KEY-----";
    }
}
