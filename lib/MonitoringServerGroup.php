<?php

class MonitoringServerGroup {
    public function __construct($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM monitoring_server_groups WHERE ID = " . intval($id));
        if ($sql->num_rows === 1) {
            while ($row = $sql->fetch_array()) {
                foreach ($row as $k => $v) {
                    $this->$k = $v;
                }
            }
        }
    }

    public static function getInstance($id)
    {
        $obj = new MonitoringServerGroup($id);
        return $obj->ID == $id ? $obj : false;
    }

    public function getLeastFullServer() {
        $least = PHP_INT_MAX;
        $server = null;

        foreach (MonitoringServer::getAllByGroup($this) as $obj) {
            $num = $obj->countContracts();
            if ($num < $least) {
                $least = $num;
                $server = $obj;
            }
        }

        return $server;
    }
}