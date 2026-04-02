<?php
// Class for handling notifications

class Notification {
    public static function create($text, $id = 0) {
        global $adminInfo, $db, $CFG;

        if (!$id) {
            $id = is_object($adminInfo) ? ($adminInfo->ID ?? 0) : 0;
            if (!$id) {
                return false;
            }
        }

        $id = intval($id);
        $text = $db->real_escape_string($text);

        $db->query("INSERT INTO notifications (`admin`, `text`) VALUES ($id, '$text')");
        return true;
    }
}