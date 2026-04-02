<?php
global $ari, $var, $db, $CFG, $dfo, $nfo, $adminInfo, $lang, $cur, $user;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['INVOICE_EXPORT']['TITLE']);
menu("statistics");

// Check admin rights
if ($ari->check(40)) {
    $tpl = "stat_invoices";

    if (isset($_POST['download'])) {
        try {
            // Validate form inputs
            $from = $_POST['from'];
            $until = $_POST['until'];

            if (empty($from)) {
                $from = "01.01.1900";
            }

            $from = strtotime($from);
            $until = strtotime($until);

            if (!$from || !$until) {
                throw new Exception('DATERANGE_INVALID');
            }

            // Get excluded datasets
            $exclude = array();
            if (isset($_POST['exclude'])) {
                $ex = explode(",", $_POST['exclude']);
                if (is_array($ex)) {
                    foreach ($ex as $v) {
                        $v = trim($v);
                        if (strlen($CFG['INVOICE_PREFIX']) > 0 && substr($v, 0, strlen($CFG['INVOICE_PREFIX'])) == $CFG['INVOICE_PREFIX']) {
                            array_push($exclude, intval(ltrim(substr($v, strlen($CFG['INVOICE_PREFIX'])), "0")));
                        }

                        if (is_numeric($v)) {
                            array_push($exclude, ltrim($v, "0"));
                            array_push($exclude, ltrim($v, "0"));
                        }
                    }
                }
            }

            // Get customers
            $customers = isset($_POST['all_customers']) && $_POST['all_customers'] == "yes" ? "all" : array_values($_POST['customers']);

            // Make sort
            $allowedFields = array("`date` ASC", "`date` DESC", "`ID` ASC", "`ID` DESC", "`client` ASC", "`client` DESC");
            $sort = $_POST['sort'];
            if (!isset($allowedFields[$sort])) {
                throw new Exception('INVALID_SORT');
            }

            $from = date("Y-m-d", $from);
            $until = date("Y-m-d", $until);
            $sql = "SELECT * FROM invoices WHERE date BETWEEN '$from' AND '$until' ";

            if (count($exclude) > 0) {
                $sql .= "AND ID NOT IN (" . implode(",", $exclude) . ") ";
            }

            if ($customers != "all") {
                $sql .= "AND client IN (" . implode(",", $customers) . ") ";
            }

            $sql .= "ORDER BY " . $allowedFields[$sort];

            $adminlang = $lang;
            require __DIR__ . "/../../languages/" . $CFG['LANG'] . ".php";

            $res = $db->query($sql);
            $inv = new Invoice;
            $invoices = [];
            while ($row = $res->fetch_object()) {
                if (!$inv->load($row->ID)) {
                    continue;
                }

                array_push($invoices, $row->ID);
                alog("INVOICE_EXPORT", "downloaded", $row->ID);
            }

            if (!count($invoices)) {
                throw new Exception('NO_RESULTS');
            }

            switch ($_POST['format']) {
                case "pdf":
                    $pdf = new PDFInvoice;
                    foreach ($invoices as $id) {
                        $inv->load($id);
                        $pdf->add($inv);
                    }

                    $pdf->output($adminlang['INVOICE_EXPORT']['TITLE'], "I");
                    exit;
                    break;

                case "zip":
                    $path = __DIR__ . "/export-" . time() . ".zip";

                    $zip = new ZipArchive;
                    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                    foreach ($invoices as $id) {
                        $pdf = new PDFInvoice;
                        $inv->load($id);
                        $pdf->add($inv);
                        $zip->addFromString($inv->getId() . ".pdf", $pdf->output("", "S"));
                    }
                    $zip->close();

                    header("Content-type: application/zip");
                    header("Content-Disposition: attachment; filename=export.zip");
                    header("Content-length: " . filesize($path));
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    readfile($path);

                    unlink($path);
                    exit;
                    break;

                case "csv":
                    header("Content-type: text/csv");
                    header("Content-Disposition: attachment; filename=export.csv");

                    $sql = $db->query("SHOW COLUMNS FROM invoices");
                    $cols = [
                        "amount",
                        "net_amount",
                        "included_tax",
                    ];

                    $csv = fopen("php://output", "w");

                    while ($row = $sql->fetch_object()) {
                        if (!in_array($row->Field, ["client_data"])) {
                            array_push($cols, $row->Field);
                        }
                    }

                    fputcsv($csv, $cols, ',', '"', "\0");
                    $cols = array_splice($cols, 3);

                    foreach ($invoices as $id) {
                        $info = $db->query("SELECT * FROM invoices WHERE ID = " . $id)->fetch_object();
                        $inv->load($id);

                        $rows = [
                            $inv->getAmount(),
                            $inv->getNet(),
                            $inv->getTaxAmount(),
                        ];

                        foreach ($cols as $col) {
                            $rows[] = $info->$col;
                        }

                        fputcsv($csv, $rows, ',', '"', "\0");
                    }

                    fclose($csv);
                    exit;
                    break;

                case "xml":
                    $sql = $db->query("SHOW COLUMNS FROM invoices");
                    $cols = [];

                    while ($row = $sql->fetch_object()) {
                        if (!in_array($row->Field, ["client_data"])) {
                            array_push($cols, $row->Field);
                        }
                    }

                    $xml = "<invoices>";

                    foreach ($invoices as $id) {
                        $info = $db->query("SELECT * FROM invoices WHERE ID = " . $id)->fetch_object();
                        $inv->load($id);

                        $xml .= "<amount>" . $inv->getAmount() . "</amount><net_amount>" . $inv->getNet() . "</net_amount><included_tax>" . $inv->getTaxAmount() . "</included_tax>";

                        foreach ($cols as $col) {
                            $xml .= "<" . $col . ">" . $info->$col . "</" . $col . ">";
                        }
                    }

                    $xml .= "</invoices>";

                    header("Content-type: text/xml");
                    header("Content-Disposition: attachment; filename=export.xml");
                    echo $xml;
                    exit;
                    break;
            }

            $lang = $adminlang;
        } catch (Exception $ex) {
            $lang = $adminlang;
            $var['error'] = $lang['INVOICE_EXPORT'][$ex->getMessage()];
        }
    }

    $var['customers'] = array();
    $sql = $db->query("SELECT * FROM clients ORDER BY firstname ASC, lastname ASC");
    while ($row = $sql->fetch_object()) {
        $var['customers'][$row->ID] = $row->firstname . " " . $row->lastname . (!empty($row->company) ? " (" . $row->company . ")" : "") . " - " . $row->mail;
    }

} else {
    alog("general", "insufficient_page_rights", "stat_invoices");
    $tpl = "error";
}
