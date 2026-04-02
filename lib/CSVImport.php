<?php

/**
 * Class for doing CSV import of transactions
 *
 * Only for static use
 */
class CSVImport
{
    /**
     * Method for parsing an easy line format
     *
     * Requires the entered data
     *
     * Returns [0 => ["user", "amount", "status"], ...]
     */
    public static function manualImport($lines = "", $sendCustomerEmail = true)
    {
        global $db, $CFG, $maq, $nfo, $transactions;
        $importStatus = array();

        // Explode the whole passed lines by breaks
        $lines = explode("\n", $lines);

        // Iterate through the lines
        foreach ($lines as $l) {
            $thisImport = array();

            // Explode the items in the lines and check for the count
            $ex = explode(";", $l);
            $date = date("Y-m-d");
            if (count($ex) != 2) {
                if (count($ex) != 3) {
                    continue;
                }

                $date = date("Y-m-d", strtotime($ex[2]));
            }

            // Get the amount of the transaction
            $amount = trim($nfo->phpize($ex[1]));

            // Status check
            if (!is_numeric($amount) || $amount <= 0) {
                // Amount is invalid
                $thisImport["status"] = "invalid_amount";
                $thisImport["amount"] = $amount;
                $thisImport["user"] = self::getUserLink($ex[0]);
            } else if (self::getUserLink($ex[0]) == $ex[0]) {
                // User does not exist
                $thisImport["status"] = "invalid_user";
                $thisImport["amount"] = $amount;
                $thisImport["user"] = $ex[0];
            } else {
                $hash = $ex[0];
                $ex[0] = self::getUserId($ex[0]);
                // Try to insert the transaction
                $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($amount) . "' WHERE ID = '" . $db->real_escape_string($ex[0]) . "' LIMIT 1");
                $transactions->insert("transfer", 0, $amount, $ex[0], self::getCashboxSubject($hash), 1, 0, $date);

                // Send email to customer if wished
                if ($sendCustomerEmail) {
                    $sql = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($ex[0]) . "' LIMIT 1");
                    $userinfo = $sql->fetch_object();

                    $fAmount = $nfo->format($amount);

                    $mtObj = new MailTemplate("Guthabenaufladung");
                    $titlex = $mtObj->getTitle($CFG['LANG']);
                    $mail = $mtObj->getMail($CFG['LANG'], $userinfo->firstname . " " . $userinfo->lastname);

                    $maq->enqueue([
                        "amount" => $fAmount . ' €', # TODO
                        "processor" => "Überweisung", # TODO
                    ], $mtObj, $userinfo->mail, $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userinfo->ID, true, 0, 0, $mtObj->getAttachments($CFG['LANG']));

                    $uI = User::getInstance($userinfo->mail);
                    $uI->applyCredit();
                }

                $thisImport["status"] = "ok";
                $thisImport["amount"] = $amount;
                $thisImport["user"] = self::getUserLink($ex[0]);
            }

            $importStatus[] = $thisImport;
        }

        return $importStatus;
    }

    /**
     * Method for getting user link by ID
     *
     * Requires the user ID
     *
     * Returns the profile link if found
     */
    protected static function getUserLink($userId = 0)
    {
        global $db, $CFG;

        if ($CFG['CASHBOX_ACTIVE'] && !is_numeric($userId)) {
            $hash = $db->real_escape_string(trim(str_replace($CFG['CASHBOX_PREFIX'], "", trim($userId))));
            $sql = $db->query("SELECT * FROM `cashbox` WHERE hash = '$hash'");
            if ($sql->num_rows != 1) {
                return $userId;
            }

            $userId = $sql->fetch_object()->user;
        }

        $sql = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($userId) . "' LIMIT 1");
        if ($sql->num_rows == 1) {
            $uInfo = $sql->fetch_object();
        }

        if (!isset($uInfo)) {
            return $userId;
        } else {
            return "<a href=\"?p=customers&edit=" . $userId . "\" target=\"_blank\">" . $uInfo->firstname . " " . $uInfo->lastname . "</a>";
        }

    }

    /**
     * Method for getting user ID
     *
     * Requires the user ID / cashbox hash
     *
     * Returns the user ID if found
     */
    protected static function getUserId($userId = 0)
    {
        global $db, $CFG;

        if ($CFG['CASHBOX_ACTIVE'] && !is_numeric($userId)) {
            $hash = $db->real_escape_string(trim(str_replace($CFG['CASHBOX_PREFIX'], "", trim($userId))));
            $sql = $db->query("SELECT * FROM `cashbox` WHERE hash = '$hash'");
            if ($sql->num_rows != 1) {
                return $userId;
            }

            $userId = $sql->fetch_object()->user;
        }

        return $userId;
    }

    /**
     * Method for getting a Cashbox subject
     *
     * Requires the Cashbox hash
     *
     * Returns the subject if found
     */
    protected static function getCashboxSubject($hash)
    {
        global $db, $CFG;

        if (!$CFG['CASHBOX_ACTIVE']) {
            return "";
        }

        $hash = $db->real_escape_string(trim(str_replace($CFG['CASHBOX_PREFIX'], "", trim($hash))));
        $sql = $db->query("SELECT * FROM `cashbox` WHERE hash = '$hash'");
        if ($sql->num_rows != 1) {
            return "";
        }

        return trim($sql->fetch_object()->subject);
    }

    /**
     * Method for doing the import
     *
     * Requires handle of CSV file and bank key
     *
     * Returns ["done" => ["count", "amount"], "undone" => ["count", "amount"]]
     */
    public static function doImport($handle = false, $bank = false, $sendCustomerEmail = true, $sendAdminEmail = false, $cc = null, $cc_wait = false)
    {
        global $db, $CFG, $transactions, $maq, $nfo, $dfo, $cur;

        try {
            // Define variables
            $done = $doneAmount = $undone = $undoneAmount = 0;

            // Check for handle validity
            if (!$handle) {
                return false;
            }

            // Get the parsing engine for the used bank
            $bankObj = new BankCSV($handle);
            $csvData = $bankObj->getCsvData($bank);

            // Iterate through the entries
            foreach ($csvData as $payment) {
                // Check for transaction ID validity
                $transactionId = $payment['transactionId'];
                if (strlen(trim($transactionId)) <= 0) {
                    continue;
                }

                // Check if transaction was already parsed
                $transactionSql = $db->query("SELECT ID FROM csv_import WHERE transactionId = '" . $db->real_escape_string($transactionId) . "'");
                if ($transactionSql->num_rows > 0) {
                    continue;
                }

                // Get the payment amount and check for its validity
                $amount = $payment['amount'];
                if (!is_numeric($amount) || $amount <= 0) {
                    if (is_numeric($amount)) {
                        $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', 0, -1)");

                        if ($sendAdminEmail) {
                            self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde aufgrund des negativen Betrages nicht weiter im System verarbeitet.", $cc, $cc_wait);
                        }

                    }

                    continue;
                }

                // Check if the transaction is already in the system
                if (count($transactions->get(array("subject" => "transfer|$transactionId"))) > 0) {
                    continue;
                }

                $description = $payment['subject'];

                // Check for the prefix
                // If it is to short, set it to a value which is not found
                $prefix = "";
                $prefixSql = $db->query("SELECT `value` FROM `gateway_settings` WHERE `gateway` = 'transfer' AND `setting` = 'prefix'");
                if ($prefixSql->num_rows == 1) {
                    $prefix = decrypt($prefixSql->fetch_object()->value);
                }

                if (strlen(trim($prefix)) < 3) {
                    $prefix = "not_in_transaction";
                    while (stripos($description, $prefix) !== false) {
                        $prefix .= "not_in_transaction";
                    }

                }

                // Search for the position of unique identifier
                $position = stripos($description, $prefix); // Customer number
                $position3 = stripos($description, $CFG['INVOICE_PREFIX']); // Invoice number
                $position4 = strlen($CFG['CASHBOX_PREFIX']) >= 2 ? stripos($description, $CFG['CASHBOX_PREFIX']) : false; // Cashbox hash
                if ($position === false && $position3 === false && $position4 === false && stripos($description, "Sofort") === false) {
                    $undone++;
                    $undoneAmount += $amount;
                    $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', 0, 0)");

                    if ($sendAdminEmail) {
                        self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde als unerledigt in das System importiert.", $cc, $cc_wait);
                    }

                    continue;
                }

                // Get position of the unique identifier ID
                if ($position !== false) {
                    $newPosition = $position + strlen($prefix);
                } else if ($position3 !== false) {
                    $newPosition = $position3 + strlen($CFG['INVOICE_PREFIX']);
                } else {
                    $newPosition = $position4 + strlen($CFG['CASHBOX_PREFIX']);
                }

                // Get the client / related ID from the transaction subject
                $substr = substr($description, $newPosition);
                $clientId = "";
                for ($i = 0; $i < (strlen($substr) + $position); $i++) {
                    if ((is_numeric(substr($substr, $i, 1))) || ($position === false && $position3 === false && $i < 8)) {
                        $clientId .= substr($substr, $i, 1);
                    } else {
                        break;
                    }

                }

                // If the ID from the transaction subject was not the client ID, get the client ID from the related ID
                if ($position === false) {
                    if ($position3 !== false) {
                        // Select from invoices
                        $clientId = abs(intval(ltrim($clientId, "0")));
                        $sql = $db->query("SELECT client FROM invoices WHERE ID = $clientId");
                        if ($sql->num_rows != 1) {
                            $clientId = "";
                        } else {
                            $invoiceId = $clientId;
                            $clientId = $sql->fetch_object()->client;
                        }
                    } else if ($position4 !== false) {
                        // Select from cashbox
                        $hash = $db->real_escape_string(substr(trim($clientId), 0, 8));
                        $sql = $db->query("SELECT * FROM cashbox WHERE `hash` = '$hash'");
                        if ($sql->num_rows != 1) {
                            $clientId = "";
                        } else {
                            $clientId = $sql->fetch_object()->user;
                            $cashboxInfo = $sql->fetch_object();
                        }
                    }
                }

                // Check if transaction was already inserted within a Sofort transaction
                if (stripos($description, "Sofort") !== false) {
                    $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', " . intval($clientId) . ", -1)");
                    $db->query("DELETE FROM sofort_open_transactions WHERE user = " . intval($clientId) . " AND amount = '" . $db->real_escape_string($amount) . "' LIMIT 1");

                    if ($sendAdminEmail) {
                        self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde nicht weiter im System verarbeitet, da eine Gutschrift bereits im Rahmen der Sofort-Überweisung erfolgt ist.", $cc, $cc_wait);
                    }

                    continue;
                }

                // Check if transaction was already inserted within a paysafecard transaction
                if (stripos($payment["sender"], "Prepaid Services Company") !== false) {
                    $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', " . intval($clientId) . ", -1)");

                    if ($sendAdminEmail) {
                        self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde nicht weiter im System verarbeitet, da eine Gutschrift bereits im Rahmen einer paysafecard-Zahlung erfolgt ist.", $cc, $cc_wait);
                    }

                    continue;
                }

                // Check if transaction was already inserted within a PayPal transaction
                if (stripos($payment["sender"], "PayPal Europe S.a.r.l. et") !== false) {
                    $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', " . intval($clientId) . ", -1)");

                    if ($sendAdminEmail) {
                        self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde nicht weiter im System verarbeitet, da eine Gutschrift bereits im Rahmen einer PayPal-Zahlung erfolgt ist.", $cc, $cc_wait);
                    }

                    continue;
                }

                // Check if client ID was found (probably on base of invoice/service ID)
                if ($clientId == "") {
                    $undone++;
                    $undoneAmount += $amount;
                    $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', 0, 0)");

                    if ($sendAdminEmail) {
                        self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde als unerledigt in das System importiert.", $cc, $cc_wait);
                    }

                    continue;
                }

                // External invoices
                if ($clientId == 0 && isset($invoiceId)) {
                    $sql = $db->query("SELECT * FROM invoices WHERE client = 0 AND ID = " . intval($invoiceId));
                    if ($sql->num_rows == 0) {
                        $undone++;
                        $undoneAmount += $amount;
                        $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', 0, 0)");

                        if ($sendAdminEmail) {
                            self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde als unerledigt in das System importiert.", $cc, $cc_wait);
                        }

                        continue;
                    } else {
                        $info = $sql->fetch_object();

                        $inv = new Invoice;
                        $inv->load($info->ID);
                        $inv->setPaidAmount($inv->getPaidAmount() + $amount);
                        if ($inv->getOpenAmount() <= 0) {
                            $inv->setStatus(1);
                        }

                        if ($inv->getOpenAmount() > 0) {
                            $msg = "Die Zahlung wurde auf die externe Rechnung angewandt. Es ist noch ein Restbetrag von " . $cur->infix($nfo->format($inv->getOpenAmount()), $cur->getBaseCurrency()) . " offen.";
                        } else if ($inv->getOpenAmount() < 0) {
                            $msg = "Die Zahlung wurde auf die externe Rechnung angewandt. Diese ist nun mit " . $cur->infix($nfo->format($inv->getOpenAmount() / -1), $cur->getBaseCurrency()) . " überzahlt!";
                        } else {
                            $msg = "Die Zahlung wurde auf die externe Rechnung angewandt. Diese ist nun vollständig bezahlt.";
                        }

                        $inv->save();

                        $done++;
                        $doneAmount += $amount;
                        $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', 0, 1)");

                        if ($sendAdminEmail) {
                            self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

$msg", $cc, $cc_wait);
                        }

                        continue;
                    }
                }

                // Check if specified client exists
                $uSql = $db->query("SELECT firstname, lastname FROM clients WHERE ID = '" . $db->real_escape_string($clientId) . "' LIMIT 1");
                if ($uSql->num_rows <= 0) {
                    $undone++;
                    $undoneAmount += $amount;
                    $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', 0, 0)");

                    if ($sendAdminEmail) {
                        self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde als unerledigt in das System importiert.", $cc, $cc_wait);
                    }

                    continue;
                }

                // Do the transaction
                $db->query("UPDATE clients SET credit = credit + '" . $db->real_escape_string($amount) . "' WHERE ID = '" . $db->real_escape_string($clientId) . "' LIMIT 1");
                $transactions->insert("transfer", $transactionId, $amount, $clientId, isset($cashboxInfo) ? trim($cashboxInfo->subject) : "", 1);

                // Get information about the user
                $sql = $db->query("SELECT * FROM clients WHERE ID = '" . $db->real_escape_string($clientId) . "' LIMIT 1");
                $userinfo = $sql->fetch_object();

                // Send an email to the client if wished
                if ($sendCustomerEmail) {
                    $doneAmount += $amount;

                    $betrag = $nfo->format($amount);

                    $mtObj = new MailTemplate("Guthabenaufladung");
                    $titlex = $mtObj->getTitle($CFG['LANG']);
                    $mail = $mtObj->getMail($CFG['LANG'], $userinfo->firstname . " " . $userinfo->lastname);

                    $maq->enqueue([
                        "amount" => $betrag . ' €', # TODO
                        "processor" => "Überweisung", # TODO
                    ], $mtObj, $userinfo->mail, $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $userinfo->ID, false, 0, 0, $mtObj->getAttachments($CFG['LANG']));
                }

                $cashboxSentence = "";
                if (isset($cashboxInfo)) {
                    $cashboxSentence = " Die Zahlung wurde per Cashbox geleistet.";
                }

                if ($sendAdminEmail) {
                    self::sendAdminEmail("Es wurde eine neue Transaktion bei der Bank gefunden:

ID: $transactionId
Datum: " . $dfo->format(strtotime($payment['time']), 0) . "
Absender: " . utf8_encode($payment['sender']) . "
VWZ: " . utf8_encode($payment['subject']) . "
Betrag: " . $cur->infix($nfo->format($amount), $cur->getBaseCurrency()) . "

Die Transaktion wurde dem Benutzer " . $userinfo->firstname . " " . $userinfo->lastname . " als Guthaben gutgeschrieben.$cashboxSentence", $cc, $cc_wait);
                }

                $uI = User::getInstance($userinfo->ID, "ID");
                $uI->applyCredit();

                $done++;
                $db->query("INSERT INTO csv_import (`transactionId`, `time`, `amount`, `sender`, `subject`, `clientId`, `done`) VALUES ('" . $db->real_escape_string($transactionId) . "', '" . strtotime($payment["time"]) . "','" . $db->real_escape_string($amount) . "', '" . $db->real_escape_string($payment["sender"]) . "', '" . $db->real_escape_string($payment["subject"]) . "', '" . $db->real_escape_string($clientId) . "', 1)");
            }

            return array("done" => array("count" => $done, "amount" => $doneAmount), "undone" => array("count" => $undone, "amount" => $undoneAmount));
        } catch (BankCSV_Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Method to set transaction import email to admin
     *
     * Requires the text and a CC email address if wished
     *
     * Returns nothing
     */
    protected static function sendAdminEmail($text, $cc = false, $cc_wait = null)
    {
        global $CFG, $maq;

        $maq->enqueue([], null, $CFG['PAGEMAIL'], "<Bank> Neue Transaktion", $text, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">");

        if ($cc) {
            if (!is_array($cc)) {
                $ex = explode(",", $cc);
                if (count($ex) > 0) {
                    $cc = array();
                    foreach ($ex as $email) {
                        $cc[] = trim($email);
                    }

                }
            }

            if (!is_array($cc) && filter_var($cc, FILTER_VALIDATE_EMAIL) !== false) {
                $maq->enqueue([], null, $cc, "<Bank> Neue Transaktion <Kopie>", $text, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">");
            } else if (is_array($cc)) {
                foreach ($cc as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                        $maq->enqueue([], null, $email, "<Bank> Neue Transaktion <Kopie>", $text, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", 0, !$cc_wait, 0, $cc_wait);
                    }
                }

            }
        }
    }
}
