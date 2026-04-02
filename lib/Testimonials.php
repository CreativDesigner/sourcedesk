<?php
// Class for handle testimonials

class Testimonials
{
    public static function create()
    {
        global $db, $CFG;
        $db->query("INSERT INTO testimonials (`time`) VALUES (" . time() . ")");
        return new Testimonial($db->insert_id);
    }

    public static function get($order = "ID DESC", $limit = 10, $offset = 0, $active = 1, $lang = "")
    {
        global $db, $CFG;

        if (!is_numeric($limit) || !is_numeric($offset)) {
            return false;
        }

        $where = $active ? " WHERE active = 1" : " WHERE active = 0";

        if (!empty($lang)) {
            if ($lang == "Deutsch") {
                $lang = "de";
            } else {
                $lang = "en";
            }

            $where .= " AND lang = '" . $db->real_escape_string($lang) . "'";
        }

        if ($order == "RANDOM") {
            $order = "ID DESC";
            $qlimit = "";
        } else {
            $qlimit = $limit > 0 ? " LIMIT $limit OFFSET $offset" : "";
        }

        $sql = $db->query("SELECT ID FROM testimonials{$where} ORDER BY $order$qlimit");

        $return = array();

        if ($sql) {
            while ($row = $sql->fetch_object()) {
                array_push($return, new Testimonial($row->ID));
            }
        }
        
        if (count($return) <= $limit) {
            return $return;
        } else {
            $newReturn = [];

            for ($i = 0; $i < $limit && count($return); $i++) {
                $key = array_rand($return);
                array_push($newReturn, $return[$key]);
                unset($return[$key]);
            }

            return $newReturn;
        }
    }

    public static function num($active = 1)
    {
        global $db, $CFG;
        $where = $active ? " WHERE active = 1" : " WHERE active = 0";
        return (int) $db->query("SELECT ID FROM testimonials{$where}")->num_rows;
    }

    public static function average($active = 1)
    {
        global $db, $CFG;
        if (($an = self::num($active)) == 0) {
            return 5;
        }

        $where = $active ? " WHERE active = 1" : "";
        return $db->query("SELECT SUM(rating) as stars FROM testimonials{$where}")->fetch_object()->stars / $an;
    }

    public static function fetch($id)
    {
        return new Testimonial($id);
    }

    public static function best()
    {
        global $db, $CFG;
        return (int) $db->query("SELECT rating FROM testimonials ORDER BY rating DESC LIMIT 1")->fetch_object()->rating;
    }

    public static function worst()
    {
        global $db, $CFG;
        return (int) $db->query("SELECT rating FROM testimonials ORDER BY rating ASC LIMIT 1")->fetch_object()->rating;
    }
}

class Testimonial
{
    private $info;

    public function __construct($id)
    {
        global $db, $CFG;
        $sql = $db->query("SELECT * FROM testimonials WHERE ID = " . intval($id));
        if ($sql->num_rows == 1) {
            $this->old = $this->info = $sql->fetch_object();
        }

    }

    public function save()
    {
        global $CFG, $db;

        $setString = "";
        $setAble = array("rating", "subject", "text", "active", "author");
        foreach ($setAble as $set) {
            if (isset($this->info->$set)) {
                $setString .= "`$set` = '" . $db->real_escape_string($this->info->$set) . "', ";
            }
        }

        $setString = rtrim($setString, ", ");

        $db->query("UPDATE testimonials SET $setString WHERE ID = " . intval($this->info->ID) . " LIMIT 1");
    }

    public function activate($destruct = 0)
    {
        if (!$this->info) {
            return false;
        }

        $this->info->active = 1;
        if ($destruct) {
            $this->save();
        }

        return true;
    }

    public function deactivate()
    {
        $this->info->active = 0;
    }

    public function delete()
    {
        global $db, $CFG;

        if ($db->query("DELETE FROM testimonials WHERE ID = " . intval($this->info->ID) . " LIMIT 1")) {
            $this->info = null;
            return true;
        }

        return false;
    }

    public function isActive() {
        return boolval($this->info->active);
    }

    public function getSubject()
    {
        return !$this->info ?: $this->info->subject;
    }

    public function getRating()
    {
        return !$this->info ?: (int) $this->info->rating;
    }

    public function getText()
    {
        return !$this->info ?: $this->info->text;
    }

    public function getAuthor($name = 1)
    {
        $id = !$this->info ?: $this->info->author;
        if (!$name) {
            return $id;
        }

        $u = new User($id, "ID");
        return $name == 2 ? $u : $u->get()['firstname'] . " " . substr($u->get()['lastname'], 0, 1) . ".";
    }

    public function getAuthorRegYear()
    {
        return date("Y", $this->getAuthor(2)->get()['registered']);
    }

    public function getTimestamp()
    {
        return !$this->info ?: $this->info->time;
    }

    public function getId()
    {
        return !$this->info ?: $this->info->ID;
    }

    public function getAnswer()
    {
        return !$this->info ?: $this->info->answer;
    }

    public function setSubject($subject)
    {
        $subject = substr($subject, 0, 35);
        $this->info->subject = $subject;
    }

    public function setRating($rating)
    {
        if (!is_numeric($rating)) {
            return false;
        }

        if ($rating > 5) {
            $rating = 5;
        } else if ($rating < 0) {
            $rating = 0;
        }

        $this->info->rating = $rating;
    }

    public function setText($text)
    {
        if (!is_string($text)) {
            return false;
        }

        $this->info->text = $text;
    }

    public function setAuthor($id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $this->info->author = $id;
    }
}
