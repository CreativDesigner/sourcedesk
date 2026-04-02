<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SEARCH'];
title($l['TITLE']);

if (!$ari->check(39) || !isset($_GET['searchword']) || $_GET['searchword'] == "") {require __DIR__ . "/error.php";if (!$ari->check(39)) {
    alog("general", "insufficient_page_rights", "search");
}
} else {

    $search = array("kunden", "contacts", "invoices", "domains", "tickets", "products", "licenses", "projects", "ips");
    $erg = array();

    alog("general", "search", $_GET['searchword']);

    function search_ips()
    {
        global $_GET, $erg, $db, $CFG, $dfo, $l;
        $sw = $db->real_escape_string($_GET['searchword']);
        $sw = "%" . $sw . "%";

        $sql = $db->query("SELECT * FROM ip_logs WHERE ip LIKE '$sw'");
        if ($sql->num_rows > 0) {
            $erg[$l['IPS']] = "<tr><th>{$l['IP']}</th><th>{$l['FO']}</th><th>{$l['CUST']}</th></tr>";
            while ($r = $sql->fetch_object()) {
                $cus = $db->query("SELECT ID FROM clients WHERE ID = " . $r->user);
                if ($cus->num_rows == 1) {
                    $cusInfo = $cus->fetch_object();
                }

                if (!isset($cusInfo)) {
                    $cus = "<i>{$l['DELETED']}</i>";
                } else {
                    $cus = "<a href=\"?p=customers&edit=" . $r->user . "\">" . User::getInstance($cusInfo->ID, "ID")->getfName() . "</a>";
                }

                $erg[$l['IPS']] .= "<tr><td>" . $r->ip . "</td><td>" . $dfo->format($r->time) . "</td><td>$cus</td></tr>";
            }
        }
    }

    function search_licenses()
    {
        global $_GET, $erg, $db, $nfo, $dfo, $CFG, $cur, $l;
        $sw = $db->real_escape_string($_GET['searchword']);
        $sw = "%" . $sw . "%";

        if (substr($sw, 0, strlen($CFG['INVOICE_PREFIX'])) == $CFG['INVOICE_PREFIX']) {
            $sw = substr($sw, strlen($CFG['INVOICE_PREFIX']));
        }

        while (substr($sw, 0, 1) == "0") {
            $sw = substr($sw, 1);
        }

        $ex = explode("_", $sw);
        if (count($ex) == 2 && $ex[0] = "product") {
            $sql = $db->query("SELECT * FROM client_products WHERE product = '" . $db->real_escape_string($ex[1]) . "'");
        } else {
            $sql = $db->query("SELECT * FROM client_products WHERE ID = '" . trim($sw, "%") . "'");
        }

        if ($sql->num_rows > 0) {
            $erg[$l['CP']] = "<tr><th>{$l['ID']}</th><th>{$l['DATE']}</th><th>{$l['PRODUCT']}</th><th>{$l['CUST']}</th><th>{$l['PRICE']}</th></tr>";
            while ($r = $sql->fetch_object()) {
                if (!empty($r->name)) {
                    $product = $r->name;
                } else {
                    $sql3 = $db->query("SELECT name FROM products WHERE ID = " . $r->product);
                    if ($sql3->num_rows == 1) {
                        $product = unserialize($sql3->fetch_object()->name)[$CFG['LANG']];
                    } else {
                        $product = "<i>{$l['UNKNOWN']}</i>";
                    }

                }

                if ($r->active == 0) {
                    $product .= " <font color=\"red\">({$l['LOCKED']})</font>";
                }

                if ($r->active == -1) {
                    $product .= " <font color=\"orange\">({$l['WAITING']})</font>";
                }

                if ($r->active == -2) {
                    $product .= " <font color=\"grey\">({$l['CANCELLED']})</font>";
                }

                $sql2 = $db->query("SELECT ID FROM clients WHERE ID = " . $r->user);
                if ($sql2->num_rows == 1) {
                    $uInfo = $sql2->fetch_object();
                    $name = User::getInstance($uInfo->ID, "ID")->getfName();
                    $cust = "<a href=\"?p=customers&edit=" . $r->user . "\">$name</a>";
                } else {
                    $cust = "<i>{$l['UNKNOWN']}</i>";
                }

                $billing = "";
                if ($r->billing == "monthly") {
                    $billing = " " . $l['MONTHLY'];
                } else if ($r->billing == "quarterly") {
                    $billing = " " . $l['QUARTERLY'];
                } else if ($r->billing == "semiannually") {
                    $billing = " " . $l['SEMIANNUALLY'];
                } else if ($r->billing == "annually") {
                    $billing = " " . $l['ANNUALLY'];
                } else if ($r->billing == "biennially") {
                    $billing = " " . $l['BIENNIALLY'];
                } else if ($r->billing == "trinnially") {
                    $billing = " " . $l['TRINNIALLY'];
                }

                $erg[$l['CP']] .= "<tr><td>" . $r->ID . "</td><td>" . $dfo->format($r->date, false) . "</td><td>" . $product . "</td><td>" . $cust . "<td>" . $cur->infix($nfo->format($r->price), $cur->getBaseCurrency()) . $billing . "</td></tr>";
            }
        }
    }

    function search_products()
    {
        global $_GET, $erg, $db, $nfo, $CFG, $cur, $l;
        $sw = $db->real_escape_string($_GET['searchword']);
        $sw = "%" . $sw . "%";

        $sql = $db->query("SELECT * FROM products WHERE name LIKE '$sw' OR file LIKE '$sw'");
        if ($sql->num_rows > 0) {
            $erg[$l['PRODUCTS']] = "<tr><th>{$l['NAME']}</th><th>{$l['LICENSES']}</th><th>{$l['EARNING']}</th><th>{$l['SP']}</th></tr>";
            while ($r = $sql->fetch_object()) {
                $licensesCount = 0;
                $gewinn = 0.00;

                $sql2 = $db->query("SELECT * FROM client_products WHERE product = " . $r->ID);
                while ($r2 = $sql2->fetch_object()) {
                    $licensesCount++;
                    $gewinn += $r2->price;
                }

                $erg[$l['PRODUCTS']] .= "<tr><td><a href=\"?p=products&id=" . $r->ID . "\">" . unserialize($r->name)[$CFG['LANG']] . "</a></td><td>" . $licensesCount . "</td><td>" . $cur->infix($nfo->format($gewinn), $cur->getBaseCurrency()) . "</td><td>" . $cur->infix($nfo->format($r->price), $cur->getBaseCurrency()) . "</td></tr>";
            }
        }
    }

    function search_domains()
    {
        global $_GET, $erg, $db, $CFG, $l, $lang;
        $sw = $db->real_escape_string($_GET['searchword']);
        $sw = "%" . $sw . "%";

        $s = array(
            "REG_WAITING" => array("orange"),
            "KK_WAITING" => array("orange"),
            "REG_OK" => array("limegreen"),
            "KK_OK" => array("limegreen"),
            "KK_OUT" => array(""),
            "EXPIRED" => array(""),
            "DELETED" => array(""),
            "TRANSIT" => array(""),
            "KK_ERROR" => array("red"),
            "REG_ERROR" => array("red"),
        );

        foreach ($s as $k => &$v) {
            $v[1] = $lang['DOMAIN_STATUS'][$k];
        }
        unset($v);

        $sql = $db->query("SELECT * FROM domains WHERE domain LIKE '$sw'");
        if ($sql->num_rows > 0) {
            $erg[$l['DOMAINS']] = "<tr><th>{$l['DOMAIN']}</th><th>{$l['CUST']}</th><th>{$l['STATUS']}</th></tr>";
            while ($row = $sql->fetch_object()) {
                $cust = $db->query("SELECT ID FROM clients WHERE ID = {$row->user}");
                if ($cust->num_rows != 1) {
                    continue;
                }

                $cust = $cust->fetch_object();

                $erg[$l['DOMAINS']] .= "<tr><td><a href=\"?p=domain&d={$row->domain}&u=" . $cust->ID . "\">{$row->domain}</a></td><td><a href=\"?p=customers&edit=" . $cust->ID . "\">" . User::getInstance($cust->ID, "ID")->getfName() . "</a></td><td><font color='{$s[$row->status][0]}'>{$s[$row->status][1]}</font></td></tr>";
            }
        }
    }

    function search_kunden()
    {
        global $_GET, $erg, $db, $CFG, $l;
        $sw = $db->real_escape_string($_GET['searchword']);

        if (strtolower(substr($sw, 0, strlen($CFG['CNR_PREFIX']))) == strtolower($CFG['CNR_PREFIX'])) {
            $sw = substr($sw, strlen($CFG['CNR_PREFIX']));
        }
        $sw = "%" . $sw . "%";

        $sql = $db->query("SELECT * FROM clients WHERE ID = '$sw' OR CONCAT(firstname, ' ', lastname) LIKE '$sw' OR mail LIKE '$sw' OR company LIKE '$sw' OR telephone LIKE '$sw'");
        if ($sql->num_rows > 0) {
            $erg[$l['CUSTOMERS']] = "<tr><th>{$l['NAME']}</th><th>{$l['COMPANY']}</th><th>{$l['MAIL']}</th></tr>";
            while ($r = $sql->fetch_object()) {
                $erg[$l['CUSTOMERS']] .= "<tr><td><a href=\"?p=customers&edit=" . $r->ID . "\">" . User::getInstance($r->ID, "ID")->getfName() . "</a></td><td>" . (trim($r->company) == "" ? "<i>keine</i>" : htmlentities($r->company)) . "</td><td><a href=\"mailto:" . $r->mail . "\">" . $r->mail . "</a></td></tr>";
            }

        }
    }

    function search_contacts()
    {
        global $_GET, $erg, $db, $CFG, $l;
        $sw = $db->real_escape_string($_GET['searchword']);
        $sw = "%" . $sw . "%";

        $sql = $db->query("SELECT * FROM client_contacts WHERE CONCAT(firstname, ' ', lastname) LIKE '$sw' OR mail LIKE '$sw' OR company LIKE '$sw' OR telephone LIKE '$sw'");
        if ($sql->num_rows > 0) {
            $erg[$l['CONTACTS']] = "<tr><th>{$l['CUST']}</th><th>{$l['NAME']}</th><th>{$l['COMPANY']}</th><th>{$l['MAIL']}</th></tr>";
            while ($r = $sql->fetch_object()) {
                $cust = User::getInstance($r->client, "ID");
                $custName = $cust ? $cust->getfName() : "-";

                $erg[$l['CONTACTS']] .= "<tr><td><a href=\"?p=customers&edit=" . $r->client . "\">" . $custName . "</a></td><td>" . htmlentities($r->firstname . " " . $r->lastname) . "</td><td>" . (trim($r->company) == "" ? "<i>keine</i>" : htmlentities($r->company)) . "</td><td><a href=\"mailto:" . $r->mail . "\">" . $r->mail . "</a></td></tr>";
            }
        }
    }

    function search_projects()
    {
        global $_GET, $erg, $db, $nfo, $CFG, $dfo, $cur, $l;
        $sw = $db->real_escape_string($_GET['searchword']);
        $sw = "%" . $sw . "%";

        $sql = $db->query("SELECT * FROM projects WHERE name LIKE '$sw' ORDER BY status ASC, due ASC, name ASC");
        if ($sql->num_rows > 0) {
            $erg[$l['PROJECTS']] = "<tr><th>{$l['DUE']}</th><th>{$l['NAME']}</th><th>{$l['CUST']}</th><th>{$l['ADMIN']}</th><th>{$l['LOAN']}</th></tr>";
            while ($r = $sql->fetch_object()) {
                $style = "";
                if ($r->status == 1) {
                    $style = "background-color:palegreen !important;";
                } else if (strtotime($r->due) < time()) {
                    $style = "background-color: khaki !important;";
                }

                if ($r->user != 0) {
                    $sql3 = $db->query("SELECT ID FROM clients WHERE ID = " . $r->user);
                    if ($sql3->num_rows != 1) {
                        $cus = "<i>{$l['UNKNOWN']}</i>";
                    } else {
                        $uInfo = $sql3->fetch_object();
                        $cusName = User::getInstance($uInfo->ID, "ID")->getfName();
                        $cus = "<a href=\"?p=customers&edit=" . $r->user . "\">" . $cusName . "</a>";
                    }
                } else {
                    $cus = "<i>{$l['NA']}</i>";
                }

                if ($r->admin == 0) {
                    $adm = "<i>{$l['NA']}</i>";
                } else if ($db->query("SELECT name FROM admins WHERE ID = " . $r->admin . " LIMIT 1")->num_rows == 1) {
                    $adm = $db->query("SELECT name FROM admins WHERE ID = " . $r->admin . " LIMIT 1")->fetch_object()->name;
                } else {
                    $adm = "<i>{$l['UNKNOWN']}</i>";
                }

                $url = "?p=view_project&id=" . $r->ID;

                $erg[$l['PROJECTS']] .= "<tr><td style=\"$style\">" . $dfo->format(strtotime($r->due), false) . "</td><td style=\"$style\"><a href=\"$url\">" . $r->name . "</a></td><td style=\"$style\">$cus</td><td style=\"$style\">$adm</td><td style=\"$style\">" . $cur->infix($nfo->format($r->entgelt), $cur->getBaseCurrency()) . "</td></tr>";
            }
        }
    }

    function search_tickets()
    {
        global $erg, $db, $CFG, $adminInfo, $dfo, $l;

        $sw = $db->real_escape_string(trim($_GET['searchword']));

        $depts = [$adminInfo->ID / -1];

        $sql = $db->query("SELECT dept FROM support_department_staff WHERE staff = {$adminInfo->ID}");
        while ($d = $sql->fetch_object()) {
            array_push($depts, $d->dept);
        }

        $ss = "subject LIKE '%$sw%' OR sender LIKE '%$sw%'";

        if (substr($sw, 0, 2) == "T#") {
            $sw = substr($sw, 2);
        }

        if (strval(intval($sw)) == $sw) {
            $ss = "ID = " . intval($sw);
        }

        $sql = $db->query("SELECT * FROM support_tickets WHERE ($ss) AND dept IN (" . implode(",", $depts) . ")");
        if ($sql->num_rows > 0) {
            $erg[$l['SUPPORT_TICKETS']] = "<tr><th width='30px'></th><th>{$l['DATE']}</th><th>{$l['DEPT']}</th><th>{$l['SENDER']}</th><th>{$l['SUBJECT']}</th><th>{$l['STATUS']}</th><th>{$l['LASTANSWER']}</th></tr>";

            $res = [];
            while ($i = $sql->fetch_object()) {ob_start();
                $t = new Ticket($i);?>
		<tr>
			<td><center><span style="font-size: 30pt; line-height: 0; vertical-align: middle; color: <?=$t->getPriorityColor($i->priority);?>;">•</span></center></td>
			<td><?=$dfo->format($i->created);?></td>
			<td><?=$t->getDepartmentName();?></td>
			<td><?=$t->getSenderStr();?></td>
			<td><?php if ($i->recall > 0 && $i->recall <= time()) {
                    echo '<i class="fa fa-clock-o"></i> ';
                }
                ?><a<?php if (!$t->hasRead()) {
                    echo ' style="font-weight: bold;"';
                }
                ?> href="?p=support_ticket&id=<?=$i->ID;?>"><?=$i->subject ? htmlentities($i->subject) : $l['NOSUBJECT'];?></a></td>
			<td><?=$t->getStatusStr();?></td>
			<td><?=$t->getLastAnswerStr();?></td>
		</tr>
		<?php $res[$t->getLastAnswer() . $i->ID] = ob_get_contents();
                ob_end_clean();}

            krsort($res);
            foreach ($res as $html) {
                $erg[$l['SUPPORT_TICKETS']] .= $html;
            }
        }
    }

    function search_invoices()
    {
        global $_GET, $erg, $db, $CFG, $dfo, $cur, $nfo, $l;
        $sw = $db->real_escape_string($_GET['searchword']);

        if (strtolower(substr($sw, 0, strlen($CFG['INVOICE_PREFIX']))) == strtolower($CFG['INVOICE_PREFIX'])) {
            $sw = substr($sw, strlen($CFG['INVOICE_PREFIX']));
        }

        $sw = "%" . $sw . "%";

        $sql = $db->query("SELECT * FROM invoices WHERE ID LIKE '$sw' OR customno LIKE '$sw'");
        if ($sql->num_rows > 0) {
            $ath = $CFG['TAXES'] ? "<th>{$l['NET']}</th><th>{$l['GROSS']}</th>" : "<th>{$l['AMOUNT']}</th>";
            $erg[$l['INVOICES']] = "<tr><th>#</th><th>{$l['CUST']}</th><th>{$l['DATE']}</th><th>{$l['DUE']}</th>$ath<th>{$l['STATUS']}</th></tr>";
            while ($r = $sql->fetch_object()) {
                if ($r->client == 0) {
                    $data = unserialize($r->client_data);
                    $reseller = $data['firstname'] . " " . $data['lastname'] . (!empty($data['company']) ? " ({$data['company']})" : "");
                } else {
                    $sql3 = $db->query("SELECT ID FROM clients WHERE ID = " . $r->client);
                    if ($sql3->num_rows == 1) {
                        $rInfo = $sql3->fetch_object();
                    }

                    if (!isset($rInfo)) {
                        $reseller = "<i>{$l['UNKNOWN']}</i>";
                    } else {
                        $reseller = "<a href=\"?p=customers&edit=" . $r->client . "\">" . User::getInstance($rInfo->ID, "ID")->getfName() . "</a>";
                    }

                }

                $inv = new Invoice;
                $inv->load($r->ID);

                $id = !empty($inv->getCustomNo()) ? $inv->getCustomNo() : $inv->getId();
                $date = $dfo->format(strtotime($inv->getDate()), false);
                $due = $dfo->format(strtotime($inv->getDueDate()), false);

                $atd = "<td>" . $cur->infix($nfo->format($inv->getAmount()), $cur->getBaseCurrency()) . "</td>";
                if ($CFG['TAXES']) {
                    $atd = "<td>" . $cur->infix($nfo->format($inv->getNet()), $cur->getBaseCurrency()) . "</td>" . $atd;
                }

                if ($inv->getStatus() == "0") {
                    $status = '<font color="red">' . $l['UNPAID'] . '</font>';
                } else if ($inv->getStatus() == "1") {
                    $status = '<font color="green">' . $l['PAID'] . '</font>';
                } else {
                    $status = $l['CANCELLED'];
                }

                $lbl = "";
                if (!empty($inv->getAttachment())) {
                    $lbl = '<span class="label label-primary">' . array_shift(explode(".", $inv->getAttachment())) . '</span> ';
                }

                $erg[$l['INVOICES']] .= "<tr><td>$lbl$id <a href=\"?p=invoices&id={$inv->getId()}\" target=\"_blank\"><i class=\"fa fa-file-pdf-o\"></i></a></td><td>$reseller</td><td>$date</td><td>$due</td>$atd<td>$status</td></tr>";
            }
        }
    }

    foreach ($search as $s) {
        $arg = "search_$s";
        $arg();
    }

    $addons->runHook("AdminSearch", [
        "searchword" => $_GET['searchword'],
    ]);

    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?php if (explode("_", $_GET['searchword'])[0] == "product") {?><?=$l['LICENSES2'];?><?php } else {?><?=htmlentities($_GET['searchword']);?> <small><?=$l['SEARCHRESULTS'];?></small><?php }?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (count($erg) == 0) {?>
	<font color="orange"><?=str_replace("%s", htmlentities($_GET['searchword']), $l['NORES']);?></font>
	<?php } else {
        foreach ($erg as $cat => $tbl) {
            ?>
	<?php if (explode("_", $_GET['searchword'])[0] != "product") {?><h4><?=$cat;?></h4><?php }?>
	<div class="table-responsive">
	<table class="table table-bordered table-striped">
	<?=$tbl;?>
	</table>
	</div>
	<?php }}?>

<?php }?>