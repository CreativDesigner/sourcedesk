<?php
// Class for customer contacts

class Contact
{
    protected $info;

    public function __construct($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM client_contacts WHERE ID = " . intval($id));
        if ($sql->num_rows == 1) {
            $this->info = $sql->fetch_object();
        }

    }

    public static function create($data)
    {
        global $db, $CFG;

        $fields = $values = "";
        foreach ($data as $k => $v) {
            $fields .= "`" . $db->real_escape_string($k) . "`, ";
            $values .= "'" . $db->real_escape_string($v) . "', ";
        }
        $fields = rtrim($fields, ", ");
        $values = rtrim($values, ", ");

        $sql = $db->query("INSERT INTO client_contacts ($fields) VALUES ($values)");
        if (!$sql) {
            if ($db->errno) {
                throw new Exception($db->error);
            }

            return false;
        }

        return new Contact($db->insert_id);
    }

    public function save($data)
    {
        global $db, $CFG;

        $fields = "";
        foreach ($data as $k => $v) {
            $fields .= "`" . $db->real_escape_string($k) . "` = '" . $db->real_escape_string($v) . "', ";
        }

        $fields = rtrim($fields, ", ");

        $sql = $db->query("UPDATE client_contacts SET $fields WHERE ID = {$this->info->ID}");
        if (!$sql) {
            return false;
        }

        return true;
    }

    public function get($k)
    {
        if (isset($this->info->$k)) {
            return $this->info->$k;
        }

        return "";
    }
}
