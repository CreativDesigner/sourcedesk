<?php
// Class for checking system requirements

class SystemRequirements
{
    private static $warnings;

    public static function phpValues()
    {
        return [
            "memory_limit" => ["32M", "64M"],
            "upload_max_filesize" => ["0M", "128M"],
            "post_max_size" => ["1M", "129M"],
            "max_execution_time" => ["30s", "120s"],
            "max_input_time" => ["30s", "60s"],
        ];
    }

    public static function check()
    {
        global $db;
        self::$warnings = [];

        if (substr($_SERVER['SERVER_SOFTWARE'], 0, 6) != "Apache") {
            self::throwWarning("We recommend Apache as webserver");
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            self::throwWarning("We do not recommend using Windows");
        }

        if (phpversion() < '7.0') {
            self::throwFatal("You need at least PHP 7.0");
        }

        if (!function_exists("mysqli_connect") || !class_exists("MySQLi")) {
            self::throwFatal("PHP MySQLi extension is not installed");
        }

        if (!ini_get('allow_url_fopen')) {
            self::throwFatal("PHP is not allowed to make outgoing network connections (allow_url_fopen)");
        }

        if (!function_exists("curl_init")) {
            self::throwFatal("PHP cURL extension is not installed");
        }

        if (!function_exists("bcadd")) {
            self::throwFatal("PHP bcmath is not installed");
        }

        if (!extension_loaded("sockets")) {
            self::throwFatal("PHP sockets are not enabled");
        }

        if (!function_exists('imagettftext') && php_sapi_name() != "cli") {
            self::throwFatal("PHP freetype support is not enabled");
        }

        if (!extension_loaded("openssl")) {
            self::throwFatal("OpenSSL support not available");
        }

        if (!is_writable(__DIR__ . "/../files")) {
            self::throwFatal("Directory <i>files</i> is not writeable");
        }

        if (!is_writable(__DIR__ . "/../templates/compiled")) {
            self::throwFatal("Directory <i>templates/compiled</i> is not writeable");
        }

        if (!is_writable(__DIR__ . "/../admin/templates/compiled")) {
            self::throwFatal("Directory <i>admin/templates/compiled</i> is not writeable");
        }

        if (!class_exists("SoapClient")) {
            self::throwWarning("PHP SOAP extension is not installed");
        }

        if (!function_exists("xmlrpc_encode_request")) {
            self::throwWarning("PHP XML-RPC extension is not installed");
        }

        if (!function_exists("imap_open")) {
            self::throwWarning("PHP IMAP extension is not installed");
        }

        if (!function_exists("mailparse_msg_parse")) {
            self::throwWarning("PHP mailparse extension is not installed");
        }

        if (!function_exists("simplexml_load_file")) {
            self::throwWarning("PHP SimpleXML extension is not installed");
        }

        if (!class_exists("ZipArchive")) {
            self::throwWarning("PHP ZIP extension is not installed");
        }

        if (empty($CFG['WKHTMLTOIMAGE'])) {
            $CFG['WKHTMLTOIMAGE'] = "/usr/bin/wkhtmltoimage";
        }

        if (!file_exists($CFG['WKHTMLTOIMAGE'])) {
            self::throwWarning("Wkhtmltoimage is not available ({$CFG['WKHTMLTOIMAGE']})");
        }

        if (is_object($db)) {
            $modes = explode(",", $db->query("SELECT @@sql_mode AS m")->fetch_object()->m);
            if (in_array("STRICT_TRANS_TABLES", $modes)) {
                self::throwFatal("SQL mode must not contain STRICT_TRANS_TABLES");
            }
        }

        foreach (self::phpValues() as $val => $req) {
            $min = $req[0];
            $rec = $req[1];

            $set = ini_get($val);
            $unit = substr($min, -1);

            if ($unit == "M") {
                $set = self::convertSize($set);
            }

            $set = intval($set);

            if (php_sapi_name() != "cli") {
                if ($set < 0) {
                    continue;
                }

                if ($set < intval($min)) {
                    self::throwFatal("PHP setting '$val' is lower than $min (currently: $set$unit)");
                }

                if ($set < intval($rec)) {
                    self::throwWarning("PHP setting '$val' is recommended to be at least $rec (currently: $set$unit)");
                }
            }
        }
    }

    public static function getWarningList()
    {
        if (!is_array(self::$warnings) || !count(self::$warnings)) {
            return "";
        }

        $html = "<ul>";

        foreach (self::$warnings as $msg) {
            $html .= "<li>" . $msg . "</li>";
        }

        return $html . "</ul>";
    }

    private static function throwFatal($msg)
    {
        global $CFG, $raw_cfg;
        ?>
        <!DOCTYPE html>
        <html>
            <head>
                <title>System requirements :: <?=$CFG['PAGENAME'];?></title>
                <link rel="shortcut icon" href="<?=$raw_cfg['PAGEURL'];?>themes/favicon.ico" type="image/x-icon" />

                <style>
                body {
                    background: #f3f3f3;
                }

                .box {
                    margin: auto;
                    margin-top: 50px;
                    max-width: 500px;
                    width: 100%;
                    -webkit-box-shadow: 3px 3px 8px 4px #ccc;
                    -moz-box-shadow: 3px 3px 8px 4px #ccc;
                    box-shadow: 3px 3px 8px 4px #ccc;
                    border: black 1px solid;
                    border-radius: 10px;
                    line-height: 1.4;
                    text-align: center;
                    font-family: 'Lucida Grande', sans-serif;
                }

                .box h4 {
                    margin: 0;
                    padding: 10px 0;
                    border-bottom: black 1px solid;
                }

                .box p {
                    margin: 10px 0;
                    padding: 5px;
                    padding-bottom: 10px;
                    border-bottom: black 1px solid;;
                }

                .box footer {
                    font-size: 10px;
                    padding-bottom: 10px;
                }
                </style>
            </head>
            <body>
                <div class="box">
                    <h4>System requirements not met</h4>
                    <p>
                        The following system requirement is not met:<br /><br />
                        <i><?=$msg;?></i><br /><br />
                        Please tell this error message your server administrator. If you have any questions, please feel free to contact us.
                    </p>
                    <footer>&copy; Copyright sourceDESK <?=date("Y");?></footer>
                </div>
            </body>
        </html>
        <?php
exit;
    }

    private static function throwWarning($msg)
    {
        if (!is_array(self::$warnings)) {
            self::$warnings = [];
        }

        array_push(self::$warnings, $msg);
    }

    private static function convertSize($size)
    {
        $bytes = trim($size);

        if (is_numeric(substr($bytes, -1))) {
            return round(intval($bytes) / 1000000);
        }

        $unit = substr($bytes, -1);
        $bytes = intval($bytes);

        switch ($unit) {
            // Intentional fall-through
            case "G":
                $bytes *= 1000;

            case "M":
                $bytes *= 1000;

            case "K":
                $bytes *= 1000;
        }

        return round($bytes / 1000000);
    }
}
