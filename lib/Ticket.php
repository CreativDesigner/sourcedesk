<?php

class Ticket
{
    protected $info;

    public function __construct($id)
    {
        global $db, $CFG;

        if (is_object($id)) {
            $this->info = $id;
            return;
        }

        $sql = $db->query("SELECT * FROM support_tickets WHERE ID = " . $id);
        if ($sql->num_rows == 1) {
            $this->info = $sql->fetch_object();
        }

    }

    public function notify($t = "new")
    {
        global $raw_cfg;

        if (($ntf = AdminNotification::getInstance($t == "answer" ? "Ticket-Antwort" : "Neues Ticket")) !== false) {
            $la = $this->getLastAnswerObj();

            if ($this->info->customer && $user = User::getInstance($this->info->customer, "ID")) {
                $ntf->set("sender", "<a href=\"" . $raw_cfg['PAGEURL'] . "admin/?p=customers&edit=" . $this->info->customer . "\" target=\"_blank\">" . htmlentities($user->get()['name']) . "</a>");
            } else {
                $ntf->set("sender", trim(explode("<", $la ? $la->sender : $this->info->sender)[0]));
            }

            $ntf->set("subject", "<a href=\"" . $raw_cfg['PAGEURL'] . "admin/?p=support_ticket&id=" . $this->info->ID . "\" target=\"_blank\">" . htmlentities($this->info->subject) . "</a>");
            $ntf->set("department", $this->getDepartmentName());
            $ntf->send();
        }
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getDepartmentName()
    {
        global $db, $CFG;

        if ($this->info->dept < 0) {
            $sql = $db->query("SELECT name FROM admins WHERE ID = " . abs($this->info->dept) . " LIMIT 1");
            if ($sql->num_rows != 1) {
                return $this->info->dept;
            }

            return $sql->fetch_object()->name;
        }

        $sql = $db->query("SELECT name FROM support_departments WHERE ID = {$this->info->dept} LIMIT 1");
        if ($sql->num_rows != 1) {
            return $this->info->dept;
        }

        return $sql->fetch_object()->name;
    }

    public function getDepartmentInfo()
    {
        global $db, $CFG;

        if ($this->info->dept < 0) {
            return false;
        }

        $sql = $db->query("SELECT * FROM support_departments WHERE ID = {$this->info->dept} LIMIT 1");
        if ($sql->num_rows != 1) {
            return false;
        }

        return $sql->fetch_object();
    }

    public function getLastAnswerObj()
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM support_ticket_answers WHERE ticket = {$this->info->ID} AND staff >= 0 ORDER BY `time` DESC LIMIT 1");
        if ($sql->num_rows != 1) {
            return false;
        }

        return $sql->fetch_object();
    }

    public function getLastAnswer()
    {
        global $db, $CFG;

        $sql = $db->query("SELECT `time` FROM support_ticket_answers WHERE ticket = {$this->info->ID} AND staff >= 0 ORDER BY `time` DESC LIMIT 1");
        if ($sql->num_rows != 1) {
            return strtotime($this->info->created);
        }

        return strtotime($sql->fetch_object()->time);
    }

    public function hasRead()
    {
        global $adminInfo;

        $arr = explode(",", $this->info->admins_read);
        return in_array($adminInfo->ID, $arr);
    }

    public function read()
    {
        global $adminInfo, $db, $CFG;

        if (!is_object($this->info)) {
            return;
        }

        if ($this->hasRead()) {
            return;
        }

        $this->info->admins_read .= "," . $adminInfo->ID;
        $this->info->admins_read = trim($this->info->admins_read, ",");
        $db->query("UPDATE support_tickets SET admins_read = '" . $db->real_escape_string($this->info->admins_read) . "' WHERE ID = {$this->info->ID}");
    }

    public static function formatTime($time)
    {
        global $lang;

        $s = time() - $time;

        if ($s < 60) {
            return $lang['TICKET_CLASS']['T1'];
        } else if ($s <= 3600) {
            $i = round($s / 60);
            if ($i == 1) {
                return $lang['TICKET_CLASS']['T2'];
            }

            return str_replace("%m", $i, $lang['TICKET_CLASS']['T3']);
        } else if ($s <= 86400) {
            $i = round($s / 3600);
            if ($i == 1) {
                return $lang['TICKET_CLASS']['T4'];
            }

            return str_replace("%h", $i, $lang['TICKET_CLASS']['T5']);
        } else {
            $i = round($s / 86400);
            if ($i == 1) {
                return $lang['TICKET_CLASS']['T6'];
            }

            return str_replace("%d", $i, $lang['TICKET_CLASS']['T7']);
        }
    }

    public function getLastAnswerStr()
    {
        $time = $this->getLastAnswer();
        return self::formatTime($time);
    }

    public function getPriorityStr()
    {
        global $lang;

        return array(
            "1" => '<font color="red">' . $lang['TICKET_CLASS']['P1'] . '</font>',
            "2" => '<font color="orange">' . $lang['TICKET_CLASS']['P2'] . '</font>',
            "3" => '<font color="black">' . $lang['TICKET_CLASS']['P3'] . '</font>',
            "4" => '<font color="lime">' . $lang['TICKET_CLASS']['P4'] . '</font>',
            "5" => '<font color="green">' . $lang['TICKET_CLASS']['P5'] . '</font>',
        )[$this->info->priority];
    }

    public static function getPriorityText($do = true)
    {
        global $lang;

        $arr = array(
            "1" => $lang['TICKET_CLASS']['P1'],
            "2" => $lang['TICKET_CLASS']['P2'],
            "3" => $lang['TICKET_CLASS']['P3'],
            "4" => $lang['TICKET_CLASS']['P4'],
            "5" => $lang['TICKET_CLASS']['P5'],
        );

        if ($do && isset($this)) {
            return $arr[$this->info->priority];
        }

        return $arr;
    }

    public function getPriorityColor()
    {
        return array(
            "1" => 'red',
            "2" => 'orange',
            "3" => 'black',
            "4" => 'lime',
            "5" => 'green',
        )[$this->info->priority];
    }

    public static function getStatusNames()
    {
        global $lang;

        return [
            "0" => $lang['TICKET_CLASS']['S0'],
            "1" => $lang['TICKET_CLASS']['S1'],
            "2" => $lang['TICKET_CLASS']['S2'],
            "3" => $lang['TICKET_CLASS']['S3'],
        ];
    }

    public function getStatusStr()
    {
        $status = $this->info->status;

        if ($this->info->fake_status >= 0 && !defined("ADMIN_AREA")) {
            $status = $this->info->fake_status;
        }

        return array(
            "0" => '<font color="red">' . self::getStatusNames()["0"] . '</font>',
            "1" => '<font color="orange">' . self::getStatusNames()["1"] . '</font>',
            "2" => '<font color="blue">' . self::getStatusNames()["2"] . '</font>',
            "3" => '<font color="green">' . self::getStatusNames()["3"] . '</font>',
        )[$status];
    }

    public function getSenderStr()
    {
        if (!$this->info->customer) {
            return htmlentities(trim(array_shift(explode("<", $this->info->sender)), " <>"));
        }

        $user = User::getInstance($this->info->customer, "ID", true);
        if (!$user) {
            return htmlentities(trim(array_shift(explode("<", $this->info->sender)), " <>"));
        }

        return '<a href="?p=customers&edit=' . $this->info->customer . '">' . $user->getfName() . '</a>';
    }

    public function getUser()
    {
        return User::getInstance($this->info->customer, "ID", true);
    }

    public function getGreeting()
    {
        global $lang;

        $sender = htmlentities(trim(array_shift(explode("<", $this->info->sender)), " <>"));

        if ($this->info->customer) {
            $user = User::getInstance($this->info->customer, "ID", true);
            if ($user) {
                return $user->getSalutation();
            }
        }

        if ((date("H") > 10 || (date("H") == 10 && date("i") > 45)) && date("H") <= 17) {
            return $lang['TICKET_CLASS']['DAY'] . " " . $sender . ",";
        } else if (date("H") >= 18 && date("H") <= 23) {
            return $lang['TICKET_CLASS']['AFTERNOON'] . " " . $sender . ",";
        } else {
            return $lang['TICKET_CLASS']['MORNING'] . " " . $sender . ",";
        }
    }

    public function getURL()
    {
        global $CFG;

        return $CFG['PAGEURL'] . "ticket/" . $this->info->ID . "/" . substr(hash("sha512", $CFG['HASH'] . "ticketview" . $this->info->ID . "ticketview" . $CFG['HASH']), -16);
    }

    public function getSubject()
    {
        return $this->info->subject ?? "";
    }

    public function escalate($rule)
    {
        global $db, $CFG, $raw_cfg;

        if (in_array($rule->ID, explode(",", $this->info->escalations))) {
            return false;
        }

        $vars = [
            "ID" => $this->info->ID,
            "url" => $raw_cfg['PAGEURL'] . "admin/?p=support_ticket&id=" . $this->info->ID,
            "sender" => $this->info->sender,
            "subject" => $this->info->subject,
        ];

        if ($rule->new_department !== "") {
            $db->query("UPDATE support_tickets SET dept = '" . $db->real_escape_string($rule->new_department) . "' WHERE ID = " . $this->info->ID);
        }

        if ($rule->new_status !== "") {
            $db->query("UPDATE support_tickets SET status = '" . $db->real_escape_string($rule->new_status) . "' WHERE ID = " . $this->info->ID);
        }

        if ($rule->new_priority !== "") {
            $db->query("UPDATE support_tickets SET priority = '" . $db->real_escape_string($rule->new_priority) . "' WHERE ID = " . $this->info->ID);
        }

        if (!empty($rule->realtime_notification)) {
            foreach ($vars as $k => $v) {
                $rule->realtime_notification = str_replace("%$k%", $v, $rule->realtime_notification);
            }

            Telegram::sendMessage($rule->realtime_notification);
        }

        if (!empty($rule->webhook_url)) {
            foreach ($vars as $k => $v) {
                $rule->webhook_url = str_replace("%$k%", $v, $rule->webhook_url);
            }

            file_get_contents($rule->webhook_url);
        }

        $e = $db->real_escape_string(empty($this->info->escalations) ? $rule->ID : $this->info->escalations . "," . $rule->ID);

        $db->query("UPDATE support_tickets SET escalations = '$e' WHERE ID = " . $this->info->ID);
        return true;
    }

    public static function escalateTickets()
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM support_escalations");
        while ($rule = $sql->fetch_object()) {
            $qry = "SELECT ID FROM support_tickets WHERE ";
            $c = 0;

            if ($rule->department !== "") {
                $qry .= "dept IN (" . $rule->department . ")";
                $c++;
            }

            if ($rule->status !== "") {
                if ($c) {
                    $qry .= " AND ";
                }
                $qry .= "status IN (" . $rule->status . ")";
                $c++;
            }

            if ($rule->upgrade !== "") {
                if ($c) {
                    $qry .= " AND ";
                }
                $qry .= "upgrade_id IN (" . $rule->upgrade . ")";
                $c++;
            }

            if ($rule->priority !== "") {
                if ($c) {
                    $qry .= " AND ";
                }
                $qry .= "priority IN (" . $rule->priority . ")";
                $c++;
            }

            if ($rule->cgroup !== "") {
                if ($c) {
                    $qry .= " AND ";
                }
                $qry .= "customer > 0";
                $c++;
            }

            if (!$c) {
                $qry .= "1";
            }

            $ticketSql = $db->query($qry);
            while ($ticket = $ticketSql->fetch_object()) {
                $ticket = new Ticket($ticket->ID);

                if ($rule->cgroup !== "") {
                    if (!in_array($ticket->getUser()->get()['cgroup'], explode(",", $rule->cgroup))) {
                        continue;
                    }
                }

                if ($ticket->getLastAnswer() > time() - 60 * $rule->time_elapsed) {
                    continue;
                }

                $ticket->escalate($rule);
            }
        }
    }

    public function html()
    {
        global $db, $CFG, $lang;

        $subject = htmlentities($this->info->subject);
        if (!$subject) {
            $subject = $lang['SUPPORT_TICKETS']['NOSUBJECT'];
        }

        if (!$this->info->upgrade_id) {
            return $subject;
        }

        $sql = $db->query("SELECT color, name FROM support_upgrades WHERE ID = {$this->info->upgrade_id}");
        if (!$sql->num_rows) {
            return $subject;
        }

        $info = $sql->fetch_object();
        return "<span style=\"background-color: {$info->color};\">$subject</span> <small>(" . htmlentities($info->name) . ")</small>";
    }

    public function resetUpgrade()
    {
        global $db, $CFG;

        if (!$this->info->upgrade_id) {
            return true;
        }

        $sql = $db->query("SELECT valid FROM support_upgrades WHERE ID = {$this->info->upgrade_id}");
        if (!$sql->num_rows) {
            return true;
        }

        $valid = $sql->fetch_object()->valid;
        if ($valid != "answer") {
            return true;
        }

        $this->info->upgrade_id = 0;
        $this->info->priority = $this->info->upgrade_prio_before;
        $db->query("UPDATE support_tickets SET upgrade_id = 0, priority = upgrade_prio_before WHERE ID = {$this->info->ID}");
        return true;
    }
}
