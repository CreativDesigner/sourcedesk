<?php
global $CFG, $db, $var, $_GET, $dfo, $lang, $ari, $gateways;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['PAYMENT_LOG']['TITLE']);
menu("payments");

if ($ari->check(43)) {
    // Define available gateways with options
    $gateways = $gateways->get();
    foreach ($gateways as $k => $v) {
        if (!$v->isActive() || !$v->haveLog()) {
            unset($gateways[$k]);
        }
    }

    ksort($gateways);

    $gateway = !empty($_GET['gateway']) ? $_GET['gateway'] : array_keys($gateways)[0];

    // Check if gateway exists and is active
    if (isset($gateways[$gateway])) {
        // Check if log should be truncated
        if (isset($_GET['a']) && $_GET['a'] == "truncate") {
            $db->query("DELETE FROM gateway_logs WHERE gateway = '$gateway'");
            $var['truncate_ok'] = $lang['PAYMENT_LOG']['TRUNCATED'];
            alog("payment_log", "truncate_made", $gateway);
        }

        // Check if entries should be deleted
        if (isset($_POST['delete_selected']) && is_array($_POST['log'])) {
            $d = 0;
            foreach ($_POST['log'] as $id) {
                $db->query("DELETE FROM gateway_logs WHERE gateway = '$gateway' AND ID = " . intval($id) . " LIMIT 1");
                if ($db->affected_rows > 0) {
                    $d++;
                    alog("payment_log", "entry_deleted", intval($id));
                }
            }

            if ($d == 1) {
                $var['truncate_ok'] = $lang['PAYMENT_LOG']['ONE_DELETED'];
            } else if ($d > 0) {
                $var['truncate_ok'] = str_replace("%x", $d, $lang['PAYMENT_LOG']['X_DELETED']);
            }

        }

        $tpl = "payment_log";
        $var['gateway_info'] = $gateways[$gateway];
        $var['gateways'] = $gateways;

        $t = new Table("SELECT * FROM gateway_logs WHERE gateway = '$gateway'", [], ["time", "DESC"], "gateway_logs");
        $var['th'] = $t->getHeader();
        $var['tf'] = $t->getFooter();

        $var['table_order'] = [
            $t->orderHeader("time", $lang["PAYMENT_LOG"]["TIME"]),
        ];

        // Iterate through log entries
        $var['log'] = array();
        $gateSql = $t->qry("time DESC");
        while ($log = $gateSql->fetch_object()) {
            $logData = nl2br($log->log);
            $data = nl2br(htmlentities(trim(str_replace(array("Array", "(", ")"), "", $log->data))));
            $furtherData = "";

            $logLines = count(explode("<br />", $logData));
            $dataLines = count(explode("<br />", $data));
            if ($dataLines > $logLines) {
                $ex = explode("<br />", $data);
                $data = "";

                for ($i = 0; $i < $logLines; $i++) {
                    $data .= array_shift($ex) . "<br />";
                }

                $data = rtrim($data, "<br />");
                $furtherData = implode("<br />", $ex);
            }

            $var['log'][] = array(
                'ID' => $log->ID,
                'time' => $dfo->format($log->time),
                'data' => $data,
                'further' => $furtherData,
                'log' => $logData,
            );
        }

        $var['additionalJS'] = 'function expandEntry(id){
			$(".entryExpand").show();
			$(".entryExpand_" + id).hide();
			$(".entryFurther").hide(500);
			$(".entryFurther_" + id).show(500, function () {
				window.scrollTo(0, $("#p" + id).position().top);
			});
		}';
    } else {
        alog("payment_log", "entry_deleted", intval($id));
        $tpl = "error";
    }
} else {
    alog("payment_log", "wrong_gateway", $gateway);
    $tpl = "error";
}
