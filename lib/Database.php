<?php

class DB
{
    private $db;
    public $bugs = true;

    public function __construct($h, $u, $p, $d = "")
    {
        $this->db = new MySQLi($h, $u, $p, $d);
        $this->starttime = microtime(true);
    }

    public function __destruct()
    {
        global $sqlTime, $sqlCalls;
        $this->db->close();
        if (!empty($_SESSION['admin']) && isset($_GET['sql_debug'])) {
            echo '<script>console.log("SQL calls: ' . $sqlCalls . '");console.log("SQL time: ' . substr($sqlTime, 0, 8) . 's");console.log("Execution time: ' . substr(microtime(true) - $this->starttime, 0, 8) . 's");</script>';
        }
    }

    private function time($t)
    {
        global $sqlTime, $sqlCalls;
        if (isset($sqlTime)) {
            $sqlTime += $t;
        } else {
            $sqlTime = $t;
        }

        if (isset($sqlCalls)) {
            $sqlCalls++;
        } else {
            $sqlCalls = 1;
        }

    }

    public function __call($f, $a)
    {
        $s = microtime(true);
        $r = call_user_func_array(array($this->db, $f), $a);
        $t = microtime(true) - $s;
        if (isset($_GET['sql_debug']) && !empty($_SESSION['admin'])) {
            echo '<script>console.log("DB::' . $f . ' - ' . implode("-", $a) . ' - ' . substr($t, 0, 8) . 's");</script>';
        }

        $this->time($t);

        $args = [];
        if ($f != "real_connect" && $f != "__construct") {
            $i = 1;
            foreach ($a as $v) {
                $args["arg" . $i++] = $v;
            }
        }

        if ($this->bugs) {
            if ($this->db->connect_errno) {
                sourceDESK_bug(array_merge([
                    "db_connect_errno" => $this->db->connect_errno,
                    "db_connect_error" => $this->db->connect_error,
                    "function" => $f,
                ], $args));
            }

            if ($this->db->errno && $this->db->errno != 1227) {
                sourceDESK_bug(array_merge([
                    "db_errno" => $this->db->errno,
                    "db_error" => $this->db->error,
                    "function" => $f,
                ], $args));
            }
        }

        return $r;
    }

    public function __get($n)
    {
        return $this->db->$n;
    }

    public function __set($n, $v)
    {
        $this->db->$n = $v;
    }

    public function getUnderlyingDriver()
    {
        return $this->db;
    }
}
