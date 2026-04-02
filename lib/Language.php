<?php

// Class for handling languages

class Language
{

    public static function getLanguageFiles()
    {
        $files = [];

        foreach (glob(__DIR__ . "/../languages/*.php") as $file) {
            if (strpos($file, "admin.") === false && strpos($file, ".custom.php") === false) {
                $file = substr(ltrim(basename($file), "."), 0, -4);
                $files[] = $file;
            }
        }

        return $files;
    }

    public static function getClientLanguages()
    {
        global $db, $CFG;

        $langs = [];

        foreach (self::getLanguageFiles() as $file) {
            $sql = $db->query("SELECT active FROM languages WHERE language = '" . $db->real_escape_string($file) . "'");
            if ($sql->num_rows) {
                if ($sql->fetch_object()->active) {
                    $langs[] = $file;
                }
            } else {
                $db->query("INSERT INTO languages (language) VALUES ('" . $db->real_escape_string($file) . "')");
            }
        }

        return $langs;
    }

    public static function getAdminLanguages()
    {
        $files = [];

        foreach (glob(__DIR__ . "/../languages/*.php") as $file) {
            if (strpos($file, "admin.") !== false && strpos($file, "custom.php") === false) {
                $file = substr(ltrim(basename($file), "."), 6, -4);
                $files[] = $file;
            }
        }

        return $files;
    }

    public static function getBrowser($languages, $accept)
    {
        // HTTP_ACCEPT_LANGUAGE is defined in
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
        // pattern to find is therefore something like this:
        //    1#( language-range [ ";" "q" "=" qvalue ] )
        // where:
        //    language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
        //    qvalue         = ( "0" [ "." 0*3DIGIT ] )
        //            | ( "1" [ "." 0*3("0") ] )
        preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
            "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
            $accept, $hits, PREG_SET_ORDER);

        // default language (in case of no hits) is the first in the array
        $bestlang = $languages[0];
        $bestqval = 0;

        foreach ($hits as $arr) {
            // read data from the array of this hit
            $langprefix = strtolower($arr[1]);
            if (!empty($arr[3])) {
                $langrange = strtolower($arr[3]);
                $language = $langprefix . "-" . $langrange;
            } else {
                $language = $langprefix;
            }

            $qvalue = 1.0;
            if (!empty($arr[5])) {
                $qvalue = floatval($arr[5]);
            }

            // find q-maximal language
            if (in_array($language, $languages) && ($qvalue > $bestqval)) {
                $bestlang = $language;
                $bestqval = $qvalue;
            }
            // if no direct hit, try the prefix only but decrease q-value
            // by 10% (as http_negotiate_language does)
            else if (in_array($langprefix, $languages)
                && (($qvalue * 0.9) > $bestqval)
            ) {
                $bestlang = $langprefix;
                $bestqval = $qvalue * 0.9;
            }
        }
        return $bestlang;
    }

}
