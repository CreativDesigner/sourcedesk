<?php
// Class for Backup purposes

// Abstract class for defining the required methods for different backup methods
abstract class Backup
{

    protected $tmp_dir = null;
    private $log_txt = null;

    // Constructur creates a temporary directory and set the first log entry
    public function __construct($tmp = 1)
    {
        $this->log("Backup gestartet");

        // Only make a temp directory when it is needed
        if ($tmp) {
            $this->tmp_dir = __DIR__ . "/backup_tmp/";
            if (!is_dir($this->tmp_dir)) {
                $res = mkdir($this->tmp_dir);
                if ($res) {
                    $this->log("Temporäres Verzeichnis angelegt");
                } else {
                    $this->log("Temporäres Verzeichnis konnte nicht angelegt werden");
                    die("Temporäres Verzeichnis konnte nicht angelegt werden.");
                }
            }
        }
    }

    // Destructur removes the temporary directory and saves the log into database

    final public function log($log)
    {
        // If any log entry is set, append it, otherwise fill the log property first time
        if ($this->log_txt === null) {
            $this->log_txt = $log;
        } else {
            $this->log_txt .= "\n$log";
        }

    }

    // This method sets a new log entry

    public function __destruct()
    {
        global $CFG;

        $db = ObjectStorage::$db;

        if ($this->tmp_dir !== null) {
            rmdir($this->tmp_dir);
            $this->log("Temporaeres Verzeichnis entfernt");
        }

        $this->log("Backup beendet");
        $db->query("INSERT INTO backup_log (`time`, `log`) VALUES (" . time() . ", '" . $db->real_escape_string($this->log_txt) . "')");
    }

    // Do a database backup

    final public function dump()
    {
        // We need database backup class to get dump
        $dump = new BackupDatabase();

        return $dump->get();
    }

    // Method to have the possibility to add directory recursive into zip file
    // Needs reference of ZIP file stream, directory path, relative path for ZIP file
    // Optional is last parameter, which enables recursion
    public function addDir(&$zip, $dir, $rel, $recursive = 0)
    {
        // Try to open specified directory
        if ($handle = opendir($dir)) {
            $i = 0;

            // Go through each file of this directory
            while (($file = readdir($handle)) !== false) {
                // Do not backup some directories/files
                $doNotBackup = [
                    "backups",
                    "wkhtmltoimage",
                    "cookies",
                ];

                foreach ($doNotBackup as $n) {
                    if (strpos($file, $n) !== false) {
                        continue 2;
                    }
                }

                // Do not backup files which name only contains points
                if (trim(str_replace(".", "", $file)) != "") {
                    // If the current entry is a file, add it to the ZIP file
                    if (is_file($dir . "/" . $file)) {
                        $zip->addFile($dir . "/" . $file, $rel . "/" . $file);
                    }

                    // If the current entry is a directory, call the current method again recursive (only if recursion is active)
                    else if (is_dir($dir . "/" . $file) && $recursive) {
                        $this->addDir($zip, $dir . "/" . $file, $rel . "/" . $file, true);
                    }

                }
            }
        }

        // Close handle for the dir
        closedir($handle);
    }

    // Abstract function for sending the backup to requested backup method
    abstract public function send();

}

// Class for do backup into filesystem
class FilesystemBackup extends Backup
{

    private $dir = null;
    private $written = null;

    // Constructor
    public function __construct()
    {
        // Global some variables for security reasons
        global $CFG;

        // Initialize the constructor of @class Backup (it is not necessary to create temporary directory)
        parent::__construct(0);

        // Backup file path should be exist in the database
        if (!isset($CFG['BACKUP_FILE_PATH'])) {
            parent::log("Verzeichnis-Variable nicht gefunden");
            die("Verzeichnis-Variable nicht gefunden");
        }

        $dir = $CFG['BACKUP_FILE_PATH'];

        // If backup directory is empty, we use the standard path
        if ($dir == "") {
            $dir = realpath(__DIR__ . "/../files/backups");
        }

        // Check if directory exist
        if (!is_dir($dir)) {
            parent::log("Verzeichnis existiert nicht");

            // If directory does not exist, try to create it
            $res = mkdir($dir);
            if ($res) {
                parent::log("Verzeichnis wurde erstellt");
            } else {
                parent::log("Verzeichnis konnte nicht erstellt werden");
                die("Verzeichnis konnte nicht erstellt werden");
            }
        }

        // Append date to directory
        $dir = $dir . "/" . date("Y-m-d-H-i-s");

        // Try to make directory with date
        if (!mkdir($dir)) {
            parent::log("Backup-Verzeichnis konnte nicht erstellt werden");
            die("Backup-Verzeichnis konnte nicht erstellt werden");
        } else {
            parent::log("Backup-Verzeichnis angelegt");
        }

        // Save full directory as property and set that norhing was writed yet
        $this->dir = $dir;
        $this->written = false;
    }

    // Destructor deletes backup directory if nothing was written and call the destructor of @class Backup
    public function __destruct()
    {
        if (!$this->written && is_dir($this->dir)) {
            rmdir($this->dir);
            parent::log("Leeres Backup-Verzeichnis geloescht");
        }

        parent::__destruct();
    }

    // Method to save the dump, will only be called if database should be backed up
    public function saveDump()
    {
        // Try to open handle for writing dump
        $handle = fopen($this->dir . "/dump.sql", "w");
        if (!$handle) {
            parent::log("SQL-Dump konnte nicht erstellt werden");
            return false;
        }

        // Write dump
        $res = fwrite($handle, parent::dump());

        // Check if dump was written and set @property written as true if so
        if ($res !== false) {
            $this->written = true;
            parent::log("SQL-Dump geschrieben");
        } else {
            parent::log("In die Datei fuer den SQL-Dump konnte nicht geschrieben werden");
        }

        // Close handle
        fclose($handle);
    }

    // Method to save the files within this backup method, only will be called if file backup is active
    public function saveFiles()
    {
        // Define the start path and create a new ZipArchive
        $dir = realpath(__DIR__ . "/../");
        $zip = new ZipArchive;

        // Try to create the ZIP file
        if ($zip->open($this->dir . "/files.zip", ZIPARCHIVE::CREATE) !== true) {
            parent::log("ZIP-Datei konnte nicht erstellt werden");
            return false;
        }

        // Add a dir with recursion and use / as file base
        $this->addDir($zip, $dir, "", 1);

        // Close the ZIP file and log it
        $zip->close();

        parent::log("ZIP-Datei erstellt");
        $this->written = true;
    }

    // Method send does not have anything to do, only return true
    public function send()
    {
        global $CFG;

        $ret = intval($CFG['BACKUP_RETENTION']);
        if ($ret > 0) {
            $dir = $dir . "/" . date("Y-m-d-H-i-s");
            foreach (glob($this->dir . "/../") as $dir) {
                if (!is_dir($dir)) {
                    continue;
                }
                $dir = realpath($dir);

                $date = str_replace("-", " ", basename($dir));
                if (($time = strtotime($date)) === false) {
                    continue;
                }

                if ($time > strtotime("-$ret days")) {
                    continue;
                }

                Update::deleteDirectory($dir);
            }
        }

        return true;
    }

}

// Method to backup to a FTP server
class FTPBackup extends Backup
{

    private $con = null; // Connection stream
    private $fil = null;
    private $dbd = null;
    private $pat = null;

    // Constructor tries to open FTP connection
    public function __construct()
    {
        // Global @var CFG for security reasons
        global $CFG;

        // Call the constructor of @class Backup
        parent::__construct();

        // Require config, maybe credentials are set there
        require __DIR__ . "/../config.php";

        // Try to explode the port from FTP host or use standard (21)
        $ex = explode(":", $CFG['BACKUP_FTP_HOST']);
        if (!$ex || count($ex) == 1) {
            $port = 21;
        } else {
            $port = $ex[1];
        }

        // If encryption is specified to use, create SSL connection stream, otherwise create normal connection stream
        if ($CFG['BACKUP_FTP_ENCRYPTION'] != "ssl") {
            $this->con = ftp_connect($CFG['BACKUP_FTP_HOST'], $port);
        } else {
            $this->con = ftp_ssl_connect($CFG['BACKUP_FTP_HOST'], $port);
        }

        // Break script if FTP connection cannot be etablished
        if (!$this->con) {
            parent::log("Verbindung zu FTP-Server fehlschlagen");
            die("Verbindung zu FTP-Server fehlgeschlagen");
        }

        // Break script if FTP credentials are wrong
        if (!ftp_login($this->con, $CFG['BACKUP_FTP_USER'], $CFG['BACKUP_FTP_PASSWORD'])) {
            $e = "FTP-Server akzeptiert Zugangsdaten nicht";
            parent::log($e);
            die($e);
        }

        // Set the connection to passive to prevent port problems
        ftp_pasv($this->con, true);

        // Build the database path
        $pat = $CFG['BACKUP_FTP_PATH'];
        $this->pat = $pat . "/" . date("d.m.Y-H:i:s");

        $this->fil = false;
        $this->dbd = false;
    }

    // Method to save the dump, is only called if database is specified to be backed up
    public function saveDump()
    {
        // Open database dump file
        $handle = fopen($this->tmp_dir . "/dump.sql", "w");
        if (!$handle) {
            parent::log("SQL-Dump konnte nicht erstellt werden");
            return false;
        }

        // Write dump data from parent class into dump file
        $res = fwrite($handle, parent::dump());

        // Break script if it could not write into file stream
        if ($res !== false) {
            $this->written = true;
            parent::log("SQL-Dump lokal geschrieben");
        } else {
            parent::log("In die Datei fuer den SQL-Dump konnte nicht geschrieben werden");
        }

        // Close stream
        fclose($handle);

        // Set that database dump has been saved
        $this->dbd = true;
    }

    // Method to save files from file system, it is only called if files are specified to be backed up
    public function saveFiles()
    {
        // Set main directory and create new ZIP archive
        $dir = realpath(__DIR__ . "/../");
        $zip = new ZipArchive;

        // Try to create a new ZIP archive
        if ($zip->open($this->tmp_dir . "/files.zip", ZIPARCHIVE::CREATE) !== true) {
            parent::log("ZIP-Datei konnte nicht erstellt werden");
            return false;
        }

        // Add directory to ZIP file recursively
        $this->addDir($zip, $dir, "", 1);

        // Close the ZIP file stream
        if (!$zip->close()) {
            parent::log("ZIP-Datei konnte nicht abgeschlossen werden");
            return false;
        }

        parent::log("ZIP-Datei erstellt");

        // Set that the file system was backed up
        $this->fil = true;
    }

    // Delete FTP directory recursively
    private function ftp_rdel($handle, $path)
    {
        if (@ftp_delete($handle, $path) === false) {
            if ($children = @ftp_nlist($handle, $path)) {
                foreach ($children as $p) {
                    ftp_rdel($handle, $p);
                }

            }
            @ftp_rmdir($handle, $path);
        }
    }

    // Method to send backup to the FTP server
    public function send()
    {
        global $CFG;

        // Try to make the directory for the current backup (date and time) on the FTP server
        @ftp_mkdir($this->con, $this->pat);

        // Check if file system was backed up
        if ($this->fil) {
            // Try to upload the ZIP archive
            if (!@ftp_put($this->con, $this->pat . "/files.zip", $this->tmp_dir . "/files.zip", FTP_ASCII)) {
                parent::log("Fehler beim Hochladen der ZIP-Datei");
            } else {
                parent::log("ZIP-Datei hochgeladen");
            }

            // Delete the ZIP file from local file system
            if (file_exists($this->tmp_dir . "/files.zip")) {
                unlink($this->tmp_dir . "/files.zip");
            }

            parent::log("ZIP-Datei lokal geloescht");
        }

        // Check if database was backed up
        if ($this->dbd) {
            // Try to upload database dump
            if (!@ftp_put($this->con, $this->pat . "/dump.sql", $this->tmp_dir . "/dump.sql", FTP_ASCII)) {
                parent::log("Fehler beim Hochladen des Dumps");
            } else {
                parent::log("Dump hochgeladen");
            }

            // Delete the dump from local file system
            unlink($this->tmp_dir . "/dump.sql");
            parent::log("Dump lokal geloescht");
        }

        $ret = intval($CFG['BACKUP_RETENTION']);
        if ($ret > 0) {
            $path = $CFG['BACKUP_FTP_PATH'];
            ftp_pasv($this->con, true);
            $list = ftp_nlist($this->con, $path);

            foreach ($list as $dir) {
                $date = str_replace("-", " ", basename($dir));
                if (($time = strtotime($date)) === false) {
                    continue;
                }

                if ($time > strtotime("-$ret days")) {
                    continue;
                }

                $this->ftp_rdel($this->con, $dir);
            }
        }

        @ftp_close($this->con);

        return true;
    }

}

// Class for making database backups
class BackupDatabase
{

    private $dump = null;

    // Constructor makes database backup
    final public function __construct()
    {
        $this->make();
    }

    // Method returns dump as string

    final private function make()
    {
        global $db;

        $this->dump = "# Datenbank-Backup: haseDESK\r\n";

        $tables = $this->get_tables();
        foreach ($tables as $table) {
            if (substr($table, 0, 6) == "TABLE ") {
                continue;
            }

            $this->dump .= "\n# ----------------------------------------------------------\n#\n";
            $this->dump .= "# Struktur der Tabelle '$table'\n#\n";
            $this->dump .= $this->get_def($table);
            $this->dump .= "\n\n";

            $this->dump .= "\n# ----------------------------------------------------------\n#\n";
            $this->dump .= "#\n# Daten der Tabelle '$table'\n#\n";
            $this->dump .= $this->get_content($table);
            $this->dump .= "\n\n";
        }
    }

    // Method to get all tables of the database in an array

    final private function get_tables()
    {
        global $db, $CFG;

        $tableList = array();
        $res = $db->getUnderlyingDriver()->query("SHOW TABLES");

        while ($cRow = $res->fetch_array()) {
            $tableList[] = $cRow[0];
        }

        return $tableList;
    }

    // Method to concat all database tables and their content into dump string

    final private function get_def($table)
    {
        global $db;
        return stripslashes($db->query("SHOW CREATE TABLE `" . $db->real_escape_string($table) . "`")->fetch_row()[1] . ";");
    }

    // Method to build structure of each table

    final private function get_content($table)
    {
        global $db;

        if (in_array($table, [
            "domain_log",
        ])) {
            return "";
        }

        $content = "";

        $count = $db->getUnderlyingDriver()->query("SELECT COUNT(*) c FROM $table")->fetch_object()->c;

        for ($offset = 0; $offset < $count; $offset += 1000) {
            $result = $db->getUnderlyingDriver()->query("SELECT * FROM $table LIMIT $offset,1000");

            while ($row = $result->fetch_row()) {
                $insert = "INSERT INTO $table VALUES (";
                for ($j = 0; $j < $db->field_count; $j++) {
                    if (!isset($row[$j])) {
                        $insert .= "NULL,";
                    } else if ($row[$j] != "") {
                        $insert .= "'" . addslashes($row[$j]) . "',";
                    } else {
                        $insert .= "'',";
                    }

                }
                $insert = preg_replace("/,$/", "", $insert);
                $insert .= ");\n";
                $content .= $insert;
            }
        }

        return $content;
    }

    // Method to build content of each table

    final public function get()
    {
        return $this->dump;
    }

}
