<?php

/*
 * Class for get system problems and
 * optimizing MySQL database
 *
 * Class is usable without having any object
 */

class SystemStatus
{
    // Method for optimize database overflow
    public static function optimizeDb()
    {
        // Establishing a MySQLi connection
        global $db;

        $sql = $db->query("SHOW TABLE STATUS");
        if ($sql->num_rows >= 1) {
            while ($row = $sql->fetch_array()) {
                $db->query("CHECK TABLE `" . $row[0] . "` FAST QUICK");
            }

            $db->query("FLUSH TABLES");
        }
    }

    // Method for check system relationships
    public static function check($existing = false)
    {
        self::checkWhois($existing);
        self::checkDownloads($existing);
        self::checkFiles($existing);
        self::checkVersions($existing);
        self::checkBugtracker($existing);
    }

    // Internal method for checking download files existence
    private static function checkDownloads($existing)
    {
        // Establishing a MySQLi connection
        global $db, $CFG;

        // Legacy
        $db->query("DELETE FROM system_status WHERE type = 'download' AND relship = '" . $db->real_escape_string(basename($filename)) . "'");
    }

    // Internal method for checking files existence
    private static function checkFiles($existing)
    {
        // Establishing a MySQLi connection
        global $db, $CFG;

        // Selecting all file names from client files
        if (!$existing) {
            $sql = $db->query("SELECT filepath FROM client_files");
        } else {
            $sql = $db->query("SELECT relship FROM system_status WHERE type = 'file'");
        }

        while ($file = $sql->fetch_object()) {
            if (!$existing) {
                $filename = $file->filepath;
            } else {
                $filename = $file->relship;
            }

            if (file_exists(__DIR__ . "/../files/customers/" . basename($filename))) {
                $db->query("DELETE FROM system_status WHERE type = 'file' AND relship = '" . $db->real_escape_string(basename($filename)) . "'");
            } else if (!$existing) {
                $db->query("INSERT INTO system_status (type, relship) VALUES ('file', '" . $db->real_escape_string(basename($filename)) . "')");
            }

        }

        // Selecting all file names from project files
        if (!$existing) {
            $sql = $db->query("SELECT files FROM projects WHERE files != '' AND files != 'a:0:{}'");
            while ($project = $sql->fetch_object()) {
                $files = unserialize($project->files);
                foreach ($files as $file) {
                    if (file_exists(__DIR__ . "/../files/projects/" . basename($file))) {
                        $db->query("DELETE FROM system_status WHERE type = 'pfile' AND relship = '" . $db->real_escape_string(basename($file)) . "'");
                    } else {
                        $db->query("INSERT INTO system_status (type, relship) VALUES ('pfile', '" . $db->real_escape_string(basename($file)) . "')");
                    }

                }
            }
        } else {
            $sql = $db->query("SELECT relship FROM system_status WHERE type = 'pfile'");
            while ($file = $sql->fetch_object()) {
                $filename = $file->relship;
                if (file_exists(__DIR__ . "/../files/projects/" . basename($filename))) {
                    $db->query("DELETE FROM system_status WHERE type = 'pfile' AND relship = '" . $db->real_escape_string(basename($filename)) . "'");
                }

            }
        }
    }

    // Internal method for checking bugtracker file existence

    private static function checkVersions($existing)
    {
        // Establishing a MySQLi connection
        global $db, $CFG;

        // Legacy
        $db->query("DELETE FROM system_status WHERE type = 'version'");
    }

    // Internal method for checking versioning file existence

    private static function checkBugtracker($existing)
    {
        // Establishing a MySQLi connection
        global $db, $CFG;

        // Legacy
        $db->query("DELETE FROM system_status WHERE type = 'bugtrack'");
    }

    // Internal method for checking for domain availibility
    private static function checkWhois($existing)
    {
        // Establishing a MySQLi connection
        global $db, $CFG;

        $hasActive = false;
        $hasWhois = false;

        foreach (DomainHandler::getRegistrars() as $reg) {
            if ($reg->isActive()) {
                $hasActive = true;

                if ($reg->hasAvailibilityStatus()) {
                    $hasWhois = true;
                }
            }
        }

        if (!$hasActive || $hasWhois) {
            $db->query("DELETE FROM system_status WHERE type = 'Kein WHOIS möglich'");
        } else if (!$existing) {
            $db->query("INSERT INTO system_status (type, relship) VALUES ('Kein WHOIS möglich', 'Weiteres Domain-Modul aktivieren')");
        }
    }

}
