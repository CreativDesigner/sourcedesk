<?php

class KBQuestion
{
    public function __construct($id)
    {
        global $db, $CFG;

        $id = intval($id);
        $sql = $db->query("SELECT * FROM knowledgebase WHERE ID = $id");
        while ($row = $sql->fetch_assoc()) {
            foreach ($row as $k => $v) {
                $this->$k = $v;
            }
        }

        $this->category_name = "";
        $sql = $db->query("SELECT `name` FROM knowledgebase_categories WHERE ID = " . intval($this->category));
        if ($sql->num_rows) {
            $this->category_name = $sql->fetch_object()->name;
        }
    }

    public static function getPop($lang = "")
    {
        global $db, $CFG;

        $sql = $db->query("SELECT q.ID FROM knowledgebase q WHERE q.status = 1 AND EXISTS(SELECT 1 FROM knowledgebase_categories c WHERE c.ID = q.category AND c.language = '" . $db->real_escape_string($lang) . "') ORDER BY q.views DESC LIMIT 10");
        $q = [];

        while ($row = $sql->fetch_object()) {
            $q[] = new KBQuestion($row->ID);
        }

        return $q;
    }

    public function getSatisfaction()
    {
        global $nfo;

        if ($this->ratings == 0) {
            return "-";
        }

        return $nfo->format(round($this->positive / $this->ratings * 100), 0) . " %";
    }
}
