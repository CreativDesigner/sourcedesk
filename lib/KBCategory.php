<?php

class KBCategory
{
    public function __construct($id)
    {
        global $db, $CFG;

        $id = intval($id);
        $sql = $db->query("SELECT * FROM knowledgebase_categories WHERE ID = $id");
        while ($row = $sql->fetch_assoc()) {
            foreach ($row as $k => $v) {
                $this->$k = $v;
            }
        }
    }

    public function getQuestions($onlyPublic = false)
    {
        global $db, $CFG;

        $where = "";
        if ($onlyPublic) {
            $where = " AND status = 1";
        }

        $res = [];
        $sql = $db->query("SELECT ID FROM knowledgebase WHERE category = {$this->ID}$where ORDER BY `order` ASC");
        while ($row = $sql->fetch_object()) {
            $res[$row->ID] = new KBQuestion($row->ID);
        }

        return $res;
    }

    public static function getAll($lang = "")
    {
        global $db, $CFG;

        $res = [];

        $where = "";
        if (!empty($lang)) {
            $where = " WHERE language = '" . $db->real_escape_string($lang) . "' AND status = 1";
        }

        $sql = $db->query("SELECT ID FROM knowledgebase_categories$where ORDER BY `order` ASC");
        while ($row = $sql->fetch_object()) {
            $res[] = new KBCategory($row->ID);
        }

        return $res;
    }
}
