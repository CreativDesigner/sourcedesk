<?php

// Class for performing transaction actions

class Transactions
{

    // Method to insert a new transaction
    public function insert($type, $relid, $amount, $insertUser = 0, $cashbox_subject = "", $deposit = 0, $waiting = 0, $date = "")
    {
        global $db, $user, $CFG, $adminInfo;

        if (empty($date) || !strtotime($date)) {
            $date = time();
        } else {
            $date = strtotime($date);
        }

        // Get user ID from user object if it is not passed
        if ($insertUser == 0 && is_object($user)) {
            $insertUser = $user->get()['ID'];
        } else if ($insertUser != 0) {
            $user = User::getInstance($insertUser, "ID");
        }

        // If no user ID is passed, do not insert the transaction
        if ($insertUser == 0 || !is_numeric($insertUser)) {
            return false;
        }

        // There should be a valid type set
        if (!in_array(trim($type), $this->getTypes())) {
            return false;
        }

        // There should be a valid relationship ID set
        if (trim($relid) == "") {
            return false;
        }

        $subject = $db->real_escape_string(trim($type) . "|" . trim($relid));

        // Check against database for invoice
        $inv = new Invoice;
        if (trim($type) == 'invoice' && !$inv->load(trim($relid))) {
            return false;
        }

        // There should be a valid amount passed
        if (trim($amount) == "" || !is_numeric(trim($amount))) {
            return false;
        }

        // Apply new credit to open invoices if any
        if ($deposit) {
            $user->applyCredit();
        }

        $who = 0;
        if (!empty($adminInfo) && is_object($adminInfo) && !empty($adminInfo->ID)) {
            $who = intval($adminInfo->ID);
        }

        $cashbox_subject = substr($cashbox_subject, 0, 255);
        $sql = $db->query("INSERT INTO client_transactions (user, time, amount, subject, cashbox_subject, deposit, waiting, who) VALUES ('" . $db->real_escape_string($insertUser) . "', " . $date . ", '" . $db->real_escape_string(trim($amount)) . "', '$subject', '" . $db->real_escape_string($cashbox_subject) . "', " . intval($deposit) . ", " . intval($waiting) . ", $who)");
        if ($sql) {
            return true;
        }

        return false;
    }

    // Method which gives the formatted subject
    public static function subject($s)
    {
        global $gateways, $db, $CFG, $lang;

        // Replace language variables
        $ex = explode("|", $s);
        if (count($ex) == 2) {
            switch ($ex[0]) {
                case 'invoice':
                    $inv = new Invoice;
                    $inv->load($ex[1]);
                    $s = $lang['TRANSACTIONS']['INVOICE'] . " " . $inv->getInvoiceNo();
                    break;

                case 'credit_transfer_in':
                case 'credit_transfer_out':
                    $s = $lang['TRANSACTIONS'][strtoupper($ex[0])];

                    $transferSql = $db->query("SELECT mail FROM clients WHERE ID = " . intval($ex[1]) . " LIMIT 1");
                    if ($transferSql->num_rows == 1) {
                        $s .= " " . $transferSql->fetch_object()->mail;
                    } else {
                        $ex = explode(" ", $s);
                        unset($ex[count($ex) - 1]);
                        $s = implode(" ", $ex);
                    }
                    break;

                case 'affiliate':
                    $s = $lang['TRANSACTIONS']['AFFILIATE'];
                    break;

                case 'chargebackfee':
                    $s = $lang['TRANSACTIONS']['CHARGEBACKFEE'];
                    break;

                case 'affiliate_withdrawal':
                    $s = $lang['TRANSACTIONS']['AFFILIATE_WITHDRAWAL'];
                    break;
            }

            if (isset($gateways->get()[$ex[0]])) {
                $s = $gateways->get()[$ex[0]]->getLang('TRANSACTION');
                if (trim($ex[1]) != "" && $ex[1] != "0") {
                    $s .= " (" . $ex[1] . ")";
                }

            }
        }

        return $s;
    }

    // Method which gives the available types
    public function getTypes()
    {
        global $gateways;
        $arr = array(
            'invoice',
            'credit_transfer_in',
            'credit_transfer_out',
            'affiliate',
            'affiliate_withdrawal',
            'chargebackfee',
        );
        return array_merge($arr, array_keys($gateways->get()));
    }

    // Method to get sum of negative transactions
    public function getNegativeSum($user = 0)
    {
        global $db, $CFG;
        $sql = "SELECT SUM(amount) as sum FROM client_transactions WHERE waiting = 0 AND amount < 0";
        if ($user > 0) {
            $sql .= " AND user = " . intval($user);
        }

        return $db->query($sql)->fetch_object()->sum;
    }

    // Method to get sum of positive transactions
    public function getPositiveSum($user = 0)
    {
        global $db, $CFG;
        $sql = "SELECT SUM(amount) as sum FROM client_transactions WHERE waiting = 0 AND amount > 0";
        if ($user > 0) {
            $sql .= " AND user = " . intval($user);
        }

        return $db->query($sql)->fetch_object()->sum;
    }

    // Method to get transactions by criteria
    public function get($params = array(), $entries = 0, $chronology = "time", $desc = 1, $lang = "", $noStem = 0, $pOffset = false)
    {
        global $db, $CFG;

        $res = array();

        // Build parameter string
        $where = "";
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                if ($where == "") {
                    $where = ' WHERE `';
                } else {
                    $where .= ' AND `';
                }

                $where .= $db->real_escape_string($k) . '` = \'';
                $where .= $db->real_escape_string($v) . '\'';
            }
        }

        // Check for stem
        if ($noStem) {
            if ($where == "") {
                $where = ' WHERE (`';
            } else {
                $where .= ' AND (`';
            }

            $where .= "stem` = 0 AND `amount` < 0)";
        }

        // Build limit string
        $limit = "";
        if ($entries > 0 && is_numeric($entries)) {
            $limit = " LIMIT " . intval($entries);
        }

        // Build offset string
        $offset = "";
        if ($pOffset !== false && is_numeric($pOffset)) {
            $offset = " OFFSET " . intval($pOffset);
        }

        // Build order string
        $order = "";
        if (trim($chronology) != "" && ($desc === 1 || $desc === 0)) {
            $order = " ORDER BY `" . $db->real_escape_string($chronology) . "` " . ($desc === 1 ? "DESC" : "ASC");
        }

        if ($chronology == "time") {
            $order .= ", ID " . ($desc === 1 ? "DESC" : "ASC");
        }

        // Fetch transactions
        $sql = $db->query("SELECT * FROM client_transactions$where$order$limit$offset");
        if (!$sql) {
            return false;
        }

        // Get language file
        $myLang = isset($lang) && trim($lang) != "" && file_exists(__DIR__ . "/../languages/$lang.php") ? $lang : $CFG['LANG'];
        require __DIR__ . "/../languages/$myLang.php";

        // Iterate through array for making language decisions
        while ($transaction = $sql->fetch_assoc()) {
            $x++;
            $transaction['raw_subject'] = $transaction['subject'];
            $transaction['subject'] = $this->subject($transaction['subject']);

            $res[] = $transaction;
        }

        return $res;
    }

    // Method to delete a transaction
    public function delete($id)
    {
        global $db, $CFG;

        $sql = $db->query("DELETE FROM client_transactions WHERE ID = '" . $db->real_escape_string($id) . "' LIMIT 1");
        if ($sql) {
            return true;
        }

        return false;
    }

}
