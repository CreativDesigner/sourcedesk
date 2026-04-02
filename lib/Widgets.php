<?php
// Class for handling admin widgets

class Widgets
{
    public static function get()
    {
        global $lang, $addons;

        // System widgets
        $arr = array(
            "sales_figures" => array($lang['DASHBOARD']['PROFIT'], self::sales_figures()),
            "system_stats" => array($lang['DASHBOARD']['SYSTEM_STATS'], self::system_stats()),
            "support" => array($lang['DASHBOARD']['TICKETS'], self::tickets()),
            "finance" => array($lang['DASHBOARD']['FINANCE'], self::finance()),
            "sourceway_news" => array($lang['DASHBOARD']['SOURCEWAY_NEWS'], self::sourceway_news()),
            "admin_notes" => array($lang['DASHBOARD']['ADMIN_NOTES'], self::admin_notes()),
            "waiting_products" => array($lang['DASHBOARD']['WAITING_PRODUCTS'], self::waiting_products()),
            "cancellations" => array($lang['DASHBOARD']['CANCELLATIONS'], self::cancellations()),
            "last_activity" => array($lang['DASHBOARD']['LAST_ACTIVITY'], self::last_activity()),
            "waiting_confirmation" => array($lang['DASHBOARD']['WAITING_CONFIRMATION'], self::waiting_confirmation()),
            "waiting_transaction" => array($lang['DASHBOARD']['WAITING_TRANSACTION'], self::waiting_transaction()),
            "failed_domains" => array($lang['DASHBOARD']['FAILED_DOMAINS'], self::failed_domains()),
            "due_files" => array($lang['DASHBOARD']['DUE_FILES'], self::due_files()),
            "updates" => array($lang['DASHBOARD']['UPDATES'], self::updates()),
        );

        // Addon widgets
        $arr = array_merge($arr, $addons->getWidgets());

        return $arr;
    }

    private static function updates()
    {
        global $db, $CFG, $ari, $lang;

        if (!$ari->check(66)) {
            return false;
        }

        if (isset($_GET['updates_widget_do'])) {
            $where = "";
            if ($_GET['updates_widget_do'] != "all") {
                $where = " AND `server` = " . intval($_GET['updates_widget_do']);
            }

            $db->query("UPDATE monitoring_updates SET status = 'waiting' WHERE status = 'new'$where");
            die("ok");
        }

        $sql = $db->query("SELECT GROUP_CONCAT(package) packages, COUNT(package) updates, `server`, `name` FROM monitoring_updates u, monitoring_server s WHERE u.server = s.ID AND status = 'new' GROUP BY server HAVING COUNT(package) > 0 ORDER BY COUNT(package) DESC");
        if (!$sql->num_rows) {
            return "";
        }

        ob_start();
        ?>
		<div class="table-responsive">
			<table class="table table-striped table-bordered" style="margin-bottom: 0;">
				<tr>
					<th><?=$lang['MONITORING']['SERVER'];?></th>
					<th width="180px"><center><?=$lang['DASHBOARD']['OPEN_UPDATES'];?></center></th>
					<th width="250px" style="text-align: right;"><a href="#" id="updates_widget_do_all"><?=$lang['DASHBOARD']['DOALLUPD'];?></a></th>
				</tr>
				<?php while ($srv = $sql->fetch_object()) {?>
				<tr>
					<td><a href="?p=monitoring&id=<?=$srv->server;?>"><?=htmlentities($srv->name);?></a></td>
					<td><center><a href="#" onclick="return false;" data-toggle="tooltip" title="<?=htmlentities($srv->packages);?>"><?=number_format($srv->updates, 0, ',', '.');?></a></center></td>
					<td style="text-align: right;" class="updates_widget_third_row"><a href="#" class="updates_widget_do" data-id="<?=$srv->server;?>"><?=$lang['DASHBOARD']['DOUPD'];?></a></td>
				</tr>
				<?php }?>
			</table>
		</div>

		<script>
		$("#updates_widget_do_all").click(function(e){
			e.preventDefault();
			var p = $(this).parent();
			p.html("<i class='fa fa-spinner fa-spin'></i> <?=$lang['DASHBOARD']['UPDDOING'];?>");
			$(".updates_widget_third_row").html("<i class='fa fa-spinner fa-spin'></i> <?=$lang['DASHBOARD']['UPDDOING'];?>");

			$.get("?updates_widget_do=all", function(r){
				if(r == "ok"){
					p.html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$lang['DASHBOARD']['UPDOK'];?></span>");
					$(".updates_widget_third_row").html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$lang['DASHBOARD']['UPDOK'];?></span>");
				} else {
                    p.html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$lang['DASHBOARD']['UPDNOK'];?></span>");
                    $(".updates_widget_third_row").html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$lang['DASHBOARD']['UPDNOK'];?></span>");
				}
			})
		});

		$(".updates_widget_do").click(function(e){
			e.preventDefault();
			var i = $(this).data("id");
			var p = $(this).parent();
			p.html("<i class='fa fa-spinner fa-spin'></i> <?=$lang['DASHBOARD']['UPDDOING'];?>");

			$.get("?updates_widget_do=" + i, function(r){
				if(r == "ok"){
					p.html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$lang['DASHBOARD']['UPDOK'];?></span>");
				} else {
					p.html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$lang['DASHBOARD']['UPDNOK'];?></span>");
				}
			})
		});
		</script>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function sourceway_news()
    {
        global $lang, $dfo;

        if (isset($_GET['sourceway_news'])) {
            @$xml = simplexml_load_string(file_get_contents("https://sourceway.de/de/blog/rss"));
            if (!$xml) {
                die("Loading news failed");
            }

            @$items = $xml->channel->item;
            if (!$items || !is_object($items[0])) {
                die($lang['DASHBOARD']['SOURCEWAY_NEWS_NT']);
            }

            $i = 0;
            foreach ($items as $item) {
                if ($i++) {
                    echo "<br />";
                }

                $date = $dfo->format($item->pubdate, "", false, false);
                echo "$date &nbsp; <a href=\"{$item->link}\" target=\"_blank\">{$item->title}</a>";
            }

            exit;
        }

        ob_start();
        ?>
        <div id="sourceway_news_container">
            <i class="fa fa-spinner fa-spin"></i> <?=$lang['GENERAL']['PLEASEWAIT'];?>...
        </div>

        <script>
        $(document).ready(function() {
            $.get("?sourceway_news=1", function(r) {
                $("#sourceway_news_container").html(r);
            });
        });
        </script>
        <?php
$res = ob_get_contents();
        ob_end_clean();

        return $res;
    }

    private static function admin_notes()
    {
        global $CFG, $lang, $db;

        $notes = decrypt($CFG['ADMIN_NOTES']);

        if (isset($_POST['shared_admin_notes'])) {
            $notes = $_POST['shared_admin_notes'];
            $enc = $db->real_escape_string(encrypt($notes));
            $db->query("UPDATE settings SET `value` = '$enc' WHERE `key` = 'admin_notes'");
        }

        ob_start();
        ?>
        <form method="POST">
            <textarea name="shared_admin_notes" class="form-control" style="width: 100%; height: 250px; resize: vertical;"><?=htmlentities($notes);?></textarea>
            <input type="submit" class="btn btn-primary btn-block" value="<?=$lang['GENERAL']['SAVE'];?>" style="margin-top: 10px;">
        </form>
        <?php
$res = ob_get_contents();
        ob_end_clean();

        return $res;
    }

    private static function due_files()
    {
        global $db, $CFG, $dfo, $lang;
        $wl = $lang['WIDGETS'];

        $advance = 7;
        $starting = time() + 60 * 60 * 24 * $advance;
        $files = [];

        $sql = $db->query("SELECT user, filename, expire FROM client_files WHERE expire > -1 AND expire <= $starting");
        while ($row = $sql->fetch_object()) {
            $files["u" . $row->user . "f" . $row->filename] = $row->expire;
        }

        $sql = $db->query("SELECT ID, files_expiry FROM projects WHERE files_expiry != ''");
        while ($row = $sql->fetch_object()) {
            $fe = @unserialize($row->files_expiry);
            if (is_array($fe)) {
                foreach ($fe as $f => $e) {
                    $f = substr($f, 9);
                    if ($e > 0 && $e <= $starting) {
                        $files["p" . $row->ID . "f" . $f] = $e;
                    }
                }
            }
        }

        asort($files);

        if (count($files) == 0) {
            return "";
        }

        ob_start();
        ?>
		<div class="table-responsive">
			<table class="table table-striped table-bordered" style="margin-bottom: 0;">
				<tr>
					<th><?=$wl['FILE'];?></th>
					<th><?=$wl['OBJECT'];?></th>
					<th><?=$wl['EXPIRE'];?></th>
				</tr>

				<?php foreach ($files as $k => $v) {
            $type = substr($k, 0, 1);
            $id = substr($k, 1, strpos($k, "f") - 1);
            $file = substr($k, strpos($k, "f") + 1);

            if ($type == "u") {
                $url = "?p=customers&edit=" . $id;
                $user = User::getInstance($id, "ID");
                $objn = $user ? htmlentities($user->get()['name']) : "<i>" . $wl["UNKNOWNC"] . "</i>";
            } else if ($type == "p") {
                $url = "?p=view_project&id=" . $id;
                $sql = $db->query("SELECT name FROM projects WHERE ID = $id");
                $objn = $sql->num_rows ? htmlentities($sql->fetch_object()->name) : "<i>" . $wl["UNKNOWNP"] . "</i>";
            }

            if (date("Y-m-d", $v) > date("Y-m-d")) {
                $days = ceil(($v - time()) / 86400);

                if ($days > 1) {
                    $timeout = '<font color="green">' . $wl['IN1'] . $days . $wl['IN2'] . '</font>';
                } else {
                    $timeout = '<font color="orange">' . $wl['TOMORROW'] . '</font>';
                }
            } else if (date("Y-m-d", $v) == date("Y-m-d")) {
                $timeout = '<font color="red">' . $wl['TODAY'] . '</font>';
            } else {
                $days = floor((time() - $v) / 86400);
                if ($days > 1) {
                    $timeout = '<font color="red">' . $wl['SINCE1'] . $days . $wl['SINCE2'] . '</font>';
                } else {
                    $timeout = '<font color="red">' . $wl['YESTERDAY'] . '</font>';
                }
            }

            $obj = "<a href='$url'>$objn</a>";
            ?>
				<tr>
					<td><?=htmlentities(substr($file, 0, 32));?></td>
					<td><?=$obj;?></td>
					<td><?=$timeout;?></td>
				</tr>
				<?php }?>
			</table>
		</div>
		<?php
$html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    private static function tickets()
    {
        global $CFG, $db, $adminInfo, $lang, $dfo;
        $wl = $lang['WIDGETS'];

        $depts = [$adminInfo->ID / -1];

        $sql = $db->query("SELECT dept FROM support_department_staff WHERE staff = {$adminInfo->ID}");
        while ($d = $sql->fetch_object()) {
            array_push($depts, $d->dept);
        }

        $sql = $db->query("SELECT * FROM support_tickets WHERE dept IN (" . implode(",", $depts) . ") AND status < 2 ORDER BY (recall > 0 AND recall < " . time() . ") DESC, status ASC, priority ASC, updated ASC, ID DESC");
        if ($sql->num_rows == 0) {
            return "";
        }

        ob_start();
        ?>
		<div class="table-responsive">
			<table class="table table-striped table-bordered" style="margin-bottom: 0;">
				<tr>
					<th width="40px"><a href="?p=support_next" class="btn btn-default btn-xs"><i class="fa fa-play fa-xs"></i></a></th>
					<?php if (substr($adminInfo->widgets, 0, 2) != "1|") {?><th style="vertical-align: middle;" width="12%"><?=$wl['DATE'];?></th><?php }?>
					<?php if (substr($adminInfo->widgets, 0, 2) != "1|") {?><th style="vertical-align: middle;" width="12%"><?=$wl['DEPT'];?></th><?php }?>
					<th style="vertical-align: middle;" width="<?=substr($adminInfo->widgets, 0, 2) != "1|" ? '15' : '30';?>%"><?=$wl['SENDER'];?></th>
					<th style="vertical-align: middle;"><?=$wl['SUBJECT'];?></th>
					<th style="vertical-align: middle;" width="15%"><?=$wl['LASTANSWER'];?></th>
				</tr>

                <?php
$lastStatus = null;
        while ($i = $sql->fetch_object()) {$t = new Ticket($i);
            if ($lastStatus != $i->status) {
                if ($lastStatus !== null) {
                    ?>
                        </table></div><div class="table-responsive" style="margin-top: 15px; opacity: 0.6;">
			<table class="table table-striped table-bordered" style="margin-bottom: 0;">
				<tr>
                    <th width="40px"><?php if ($i->status == 0) {?><a href="?p=support_next" class="btn btn-default btn-xs"><i class="fa fa-play fa-xs"></i></a><?php }?></th>
					<?php if (substr($adminInfo->widgets, 0, 2) != "1|") {?><th style="vertical-align: middle;" width="12%"><?=$wl['DATE'];?></th><?php }?>
					<?php if (substr($adminInfo->widgets, 0, 2) != "1|") {?><th style="vertical-align: middle;" width="12%"><?=$wl['DEPT'];?></th><?php }?>
					<th style="vertical-align: middle;" width="<?=substr($adminInfo->widgets, 0, 2) != "1|" ? '15' : '30';?>%"><?=$wl['SENDER'];?></th>
					<th style="vertical-align: middle;"><?=$wl['SUBJECT'];?></th>
					<th style="vertical-align: middle;" width="15%"><?=$wl['LASTANSWER'];?></th>
				</tr>
                        <?php
}
                $lastStatus = $i->status;
            }
            ?>
				<tr>
					<td><center><span style="font-size: 30pt; line-height: 0; vertical-align: middle; color: <?=$t->getPriorityColor($i->priority);?>;">•</span></center></td>
					<?php if (substr($adminInfo->widgets, 0, 2) != "1|") {?><td><?=$dfo->format($i->created);?></td><?php }?>
					<?php if (substr($adminInfo->widgets, 0, 2) != "1|") {?><td><?=$t->getDepartmentName();?></td><?php }?>
					<td><?=$t->getSenderStr();?></td>
					<td><?php if ($i->recall > 0 && $i->recall <= time()) {
                echo '<i class="fa fa-clock-o"></i> ';
            }
            ?><a<?php if (!$t->hasRead()) {
                echo ' style="font-weight: bold;"';
            }
            ?> href="?p=support_ticket&id=<?=$i->ID;?>"><?=$t->html();?></a></td>
					<td><?=$t->getLastAnswerStr();?></td>
				</tr>
				<?php }?>
			</table>
		</div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function sales_figures()
    {
        global $cur, $nfo, $ari, $lang;
        if (!$ari->check(2)) {
            return false;
        }

        // Function to get the profit since a UNIX timestamp
        function reingewinn($to)
        {
            global $db, $CFG;

            $inv = new Invoice;
            $to = date("Y-m-d", $to);

            return $db->query("SELECT SUM(p.amount * p.qty) as a FROM invoices i INNER JOIN invoiceitems p ON p.invoice = i.ID WHERE i.status = 1 AND i.date >= '$to'")->fetch_object()->a;
        }

        $profit = array(
            'today' => $cur->infix($nfo->format(reingewinn(strtotime(date("d") . "." . date("m") . "." . date("Y")))), $cur->getBaseCurrency()),
            'month' => $cur->infix($nfo->format(reingewinn(strtotime("01." . date("m") . "." . date("Y")))), $cur->getBaseCurrency()),
            'year' => $cur->infix($nfo->format(reingewinn(strtotime("01.01." . date("Y")))), $cur->getBaseCurrency()),
            'sum' => $cur->infix($nfo->format(reingewinn("0")), $cur->getBaseCurrency()),
        );

        ob_start();
        ?>
		<div class="row" style="margin-top: -5px;"><center>
			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$profit['today'];?></div>
				<div><?=$lang['DASHBOARD']['TODAY'];?></div>
			</div>

			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$profit['month'];?></div>
				<div><?=$lang['DASHBOARD']['MONTH'];?></div>
			</div>

			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$profit['year'];?></div>
				<div><?=$lang['DASHBOARD']['YEAR'];?></div>
			</div>

			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$profit['sum'];?></div>
				<div><?=$lang['DASHBOARD']['SUM'];?></div>
			</div>
		</center></div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function system_stats()
    {
        global $cur, $nfo, $ari, $lang, $db, $CFG;
        if (!$ari->check(2)) {
            return false;
        }

        ob_start();
        ?>
		<div class="row" style="margin-top: -5px;"><center>
			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$db->query("SELECT COUNT(*) AS c FROM clients WHERE locked = 0")->fetch_object()->c;?></div>
				<div><?=$lang['DASHBOARD']['CUSTOMERS'];?></div>
			</div>

			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$db->query("SELECT COUNT(*) AS c FROM products WHERE status = 1")->fetch_object()->c;?></div>
				<div><?=$lang['DASHBOARD']['PRODUCTS'];?></div>
			</div>

			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$db->query("SELECT COUNT(*) AS c FROM client_products WHERE active = 1")->fetch_object()->c;?></div>
				<div><?=$lang['DASHBOARD']['SERVICES'];?></div>
			</div>

			<div class="col-xs-12 col-md-3">
				<div class="huge scale"><?=$db->query("SELECT COUNT(*) AS c FROM domains WHERE status = 'REG_OK' OR status = 'KK_OK'")->fetch_object()->c;?></div>
				<div><?=$lang['DASHBOARD']['DOMAINS'];?></div>
			</div>
		</center></div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function finance()
    {
        global $cur, $nfo, $ari, $lang, $db, $CFG;
        if (!$ari->check(40)) {
            return false;
        }

        $inv = new Invoice;
        $openInvoices = $db->query("SELECT SUM(p.amount) as a FROM invoices i INNER JOIN invoiceitems p ON p.invoice = i.ID WHERE i.status = 0")->fetch_object()->a;

        $accounts = $db->query("SELECT SUM(balance) as a FROM payment_accounts WHERE balance != 0")->fetch_object()->a;

        ob_start();
        ?>
		<div class="row" style="margin-top: -5px;"><center>
			<div class="col-xs-12 col-md-4">
				<div class="huge scale" style="color: green;"><?=$cur->infix($nfo->format($accounts), $cur->getBaseCurrency());?></div>
				<div><?=$lang['DASHBOARD']['BANK'];?></div>
			</div>

			<div class="col-xs-12 col-md-4">
				<div class="huge scale" style="color: red;"><?=$cur->infix($nfo->format($CFG['STEM'] + $CFG['LOAN'] + $db->query("SELECT SUM(credit) FROM clients WHERE credit > 0")->fetch_array()['SUM(credit)'] + $db->query("SELECT SUM(affiliate_credit) FROM clients WHERE affiliate_credit > 0")->fetch_array()['SUM(affiliate_credit)']), $cur->getBaseCurrency());?></div>
				<div><?=$lang['DASHBOARD']['LIABILITIES'];?></div>
			</div>

			<div class="col-xs-12 col-md-4">
				<div class="huge scale" style="color: orange;"><?=$cur->infix($nfo->format($db->query("SELECT SUM(credit) FROM clients WHERE credit < 0")->fetch_array()['SUM(credit)'] / -1 + $openInvoices + $db->query("SELECT SUM(affiliate_credit) FROM clients WHERE affiliate_credit < 0")->fetch_array()['SUM(affiliate_credit)'] / -1), $cur->getBaseCurrency());?></div>
				<div><?=$lang['DASHBOARD']['DEBTORS'];?></div>
			</div>
		</center></div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function last_activity()
    {
        global $db, $CFG, $ari, $lang;
        if (!$ari->check(49)) {
            return false;
        }

        ob_start();

        // Select the last 10 activity log entries
        $activities = array();
        $activitySql = $db->query("SELECT * FROM client_log ORDER BY time DESC LIMIT 10");
        while ($entry = $activitySql->fetch_object()) {
            if (!($user = User::getInstance($entry->user, "ID"))) {
                continue;
            }

            $activities[] = array(
                'link' => "?p=customers&edit=" . $entry->user,
                'action' => $entry->action,
                'time' => self::time_userfriendly($entry->time),
                'user' => $user,
            );
        }

        ?>
		<div class="list-group" style="margin-bottom: 0;">
			<?php foreach ($activities as $a) {?>
			<a href="<?=$a['link'];?>" class="list-group-item">
				<?=strip_tags($a['action']);?>
				<span class="pull-right text-muted small"><em><?=$a['time'];?></em></span>
                <br /><span class="text-muted small"><?=$a['user']->getfName();?></span>
			</a>
			<?php }if (count($activities) == 0) {?>
			<li class="list-group-item">
				<center><i><?=$lang['DASHBOARD']['ACTIVITY_EMPTY'];?></i></center>
			</li>
			<?php }?>
		</div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function waiting_products()
    {
        global $db, $CFG, $ari, $lang, $dfo;
        if (!$ari->check(13)) {
            return false;
        }

        // Select waiting products
        $sql = $db->query("SELECT ID, product, name, `date`, error FROM client_products WHERE active = -1 AND type = 'h' AND payment = 0 ORDER BY date ASC");
        if ($sql->num_rows == 0) {
            return "";
        }

        ob_start();
        ?>
		<div class="list-group" style="margin-bottom: 0;">
			<?php while ($row = $sql->fetch_object()) {
            if (!empty($row->name)) {
                $name = $row->name;
            } else {
                $sql2 = $db->query("SELECT name FROM products WHERE ID = " . $row->product);
                if ($sql2->num_rows != 1) {
                    continue;
                }

                $name = unserialize($sql2->fetch_object()->name)[$CFG['LANG']];
            }
            ?>
				<a href="?p=hosting&id=<?=$row->ID;?>" class="list-group-item">
					<?=$name;?><?php if ($row->error) {
                echo ' <span class="label label-danger">' . $lang['ERROR']['TITLE'] . '</span>';
            }
            ?>
					<span class="pull-right text-muted small"><em><?=$dfo->format($row->date);?></em></span>
				</a>
			<?php }?>
		</div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function failed_domains()
    {
        global $db, $CFG, $ari, $lang, $dfo;
        $wl = $lang['WIDGETS'];

        if (!$ari->check(13)) {
            return false;
        }

        if (!empty($_GET['ignore_failed_domain'])) {
            $db->query("UPDATE domains SET ignore_failed = 1 WHERE ID = " . intval($_GET['ignore_failed_domain']) . " LIMIT 1");
        }

        // Select failed domains
        $sql = $db->query("SELECT ID, domain, created, user FROM domains WHERE status IN ('REG_ERROR', 'KK_ERROR') AND ignore_failed = 0 ORDER BY created DESC");
        if ($sql->num_rows == 0) {
            return "";
        }

        ob_start();
        $rows = [];
        ?>
		<div class="row">
			<div class="col-md-10 col-xs-9" style="padding-right: 0;">
				<div class="list-group" style="margin-bottom: 0;">
					<?php while ($row = $sql->fetch_object()) {
            array_push($rows, $row);
            $date = date("d.m.Y", strtotime($row->created));
            if ($date == date("d.m.Y")) {
                $date = "Heute";
            }

            if ($date == date("d.m.Y", strtotime("-1 day"))) {
                $date = "Gestern";
            }

            ?>
						<a href="?p=domain&d=<?=urlencode($row->domain);?>&u=<?=$row->user;?>" class="list-group-item" style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
							<?=htmlentities($row->domain);?>
							<span class="pull-right text-muted small"><em><?=$date;?></em></span>
						</a>
					<?php }?>
				</div>
			</div>

			<div class="col-md-2 col-xs-3" style="padding-left: 0;">
				<div class="list-group" style="margin-bottom: 0;">
					<?php foreach ($rows as $row) {?>
						<a href="?ignore_failed_domain=<?=$row->ID;?>" class="list-group-item" style="color: orange; font-weight: bold; border-top-left-radius: 0; border-bottom-left-radius: 0; border-left: none;">
							<center><?=$wl['IGNORE'];?></center>
						</a>
					<?php }?>
				</div>
			</div>
		</div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function waiting_confirmation()
    {
        global $db, $CFG, $ari, $lang;
        if (!$ari->check(2)) {
            return false;
        }

        if (!$CFG['USER_CONFIRMATION']) {
            return false;
        }

        // Select waiting confirmations
        $var['waiting_conf'] = array();
        $sql = $db->query("SELECT * FROM clients WHERE locked = 0 AND confirmed = 0 ORDER BY registered DESC");
        while ($r = $sql->fetch_object()) {
            $uI = User::getInstance($r->ID, "ID");
            $var['waiting_conf'][] = array(
                'id' => $r->ID,
                'link' => "?p=customers&edit=" . $r->ID,
                'name' => $uI->getfName() . (!empty($r->companyname) ? " ({$r->companyname})" : ""),
                'time' => self::time_userfriendly($r->registered),
            );
        }

        if (count($var['waiting_conf']) == 0) {
            return "";
        }

        ob_start();
        ?>
		<div class="list-group" style="margin-bottom: 0;">
			<?php foreach ($var['waiting_conf'] as $c) {?>
				<a href="<?=$c['link'];?>" class="list-group-item">
					<?=strip_tags($c['name']);?>
					<span class="pull-right text-muted small"><em><?=$c['time'];?></em></span>
				</a>
			<?php }?>
		</div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    private static function waiting_transaction()
    {
        global $db, $CFG, $ari, $lang, $cur, $transactions, $nfo;
        if (!$ari->check(15)) {
            return false;
        }

        $var['waiting_transaction'] = array();
        $sql = $db->query("SELECT * FROM client_transactions WHERE waiting > 0 ORDER BY time DESC");
        while ($r = $sql->fetch_object()) {
            $var['waiting_transaction'][] = array(
                'link' => "?p=customers&edit=" . $r->user . "&tab=transactions",
                'name' => $transactions->subject($r->subject) . ' <span class="label label-' . ($r->amount > 0 ? 'success' : 'danger') . '">' . $cur->infix($nfo->format($r->amount), $cur->getBaseCurrency()) . '</span>',
                'time' => self::time_userfriendly($r->time),
            );
        }

        if (count($var['waiting_transaction']) == 0) {
            return "";
        }

        ob_start();
        ?>
		<div class="list-group" style="margin-bottom: 0;">
			<?php foreach ($var['waiting_transaction'] as $c) {?>
				<a href="<?=$c['link'];?>" class="list-group-item">
					<?=strip_tags($c['name']);?>
					<span class="pull-right text-muted small"><em><?=$c['time'];?></em></span>
				</a>
			<?php }?>
		</div>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    // Function to format a time userfriendly
    public static function time_userfriendly($ts)
    {
        global $dfo, $lang;
        $wl = $lang['WIDGETS'];

        $t = time();
        if ($ts + 60 > $t) {
            $x = (int) ($t - $ts);
            if ($x != 1) {
                return $wl['PREFIXSINCE'] . $x . $wl['SECONDSSINCE'];
            } else {
                return $wl['PREFIXSINCE'] . $x . $wl['SECONDSINCE'];
            }

        }

        if ($ts + 3600 > $t) {
            $x = (int) ($t / 60 - $ts / 60);
            if ($x != 1) {
                return $wl['PREFIXSINCE'] . $x . $wl['MINUTESSINCE'];
            } else {
                return $wl['PREFIXSINCE'] . $x . $wl['MINUTESINCE'];
            }

        }

        $day = date("j");
        if ($day == 1) {
            $month = date("n");
            if ($month == 1) {
                $month = 12;
                $year = date("Y") - 1;
                $day = 31;
            } else {
                $month--;
                $year = date("Y");
                $day = date("t", mktime(0, 0, 0, $month, 1, $year));
            }
        } else {
            $day--;
            $year = date("Y");
            $month = date("n");
        }

        $lastDayBegin = mktime(0, 0, 0, $month, $day, $year);
        $lastDayEnd = mktime(23, 59, 59, $month, $day, $year);

        if ($ts > $lastDayEnd) {
            $x = (int) ($t / 3600 - $ts / 3600);
            if ($x != 1) {
                return $wl['PREFIXSINCE'] . $x . $wl['HOURSSINCE'];
            } else {
                return $wl['PREFIXSINCE'] . $x . $wl['HOURSINCE'];
            }

        }

        if ($ts >= $lastDayBegin && $ts <= $lastDayEnd) {
            return $wl['YESTERDAY'];
        }

        return $dfo->format($ts, false);
    }

    private static function cancellations()
    {
        global $db, $CFG, $ari, $lang, $dfo;

        if (!$ari->check(13)) {
            return false;
        }

        $sql = $db->query("SELECT ID, user, product, cancellation_date FROM client_products WHERE cancellation_date != '0000-00-00' AND cancellation_date >= '" . date("Y-m-d") . "' ORDER BY cancellation_date ASC LIMIT 10");
        if (!$sql->num_rows) {
            return "";
        }

        ob_start();
        ?>
		<div class="table-responsive">
			<table class="table table-striped table-bordered" style="margin-bottom: 0;">
				<tr>
					<th><?=$lang['CANCELLATIONS']['CONTRACT'];?></th>
					<th><?=$lang['CANCELLATIONS']['USER'];?></th>
					<th><?=$lang['CANCELLATIONS']['DATE'];?></th>
				</tr>
				<?php while ($con = $sql->fetch_object()) {
            $pName = "";
            $pSql = $db->query("SELECT `name` FROM products WHERE ID = {$con->product}");
            if ($pSql->num_rows) {
                $pInfo = $pSql->fetch_object();
                $pArr = @unserialize($pInfo->name);
                if (is_array($pArr) && array_key_exists($CFG['LANG'], $pArr)) {
                    $pName = $pArr[$CFG['LANG']];
                }
            }

            $uI = User::getInstance($con->user, "ID");
            ?>
				<tr>
					<td><a href="?p=hosting&id=<?=$con->ID;?>">#<?=$con->ID;?> <?=htmlentities($pName);?></a></td>
        <td><?php if ($uI) {?><a href="?p=customers&edit=<?=$con->user;?>"><?=$uI->getfName();?></a><?php } else {?>-<?php }?></td>
					<td><?=$dfo->format($con->cancellation_date, false, false, false);?></td>
				</tr>
				<?php }?>
			</table>
		</div>

		<script>
		$("#updates_widget_do_all").click(function(e){
			e.preventDefault();
			var p = $(this).parent();
			p.html("<i class='fa fa-spinner fa-spin'></i> <?=$lang['DASHBOARD']['UPDDOING'];?>");
			$(".updates_widget_third_row").html("<i class='fa fa-spinner fa-spin'></i> <?=$lang['DASHBOARD']['UPDDOING'];?>");

			$.get("?updates_widget_do=all", function(r){
				if(r == "ok"){
					p.html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$lang['DASHBOARD']['UPDOK'];?></span>");
					$(".updates_widget_third_row").html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$lang['DASHBOARD']['UPDOK'];?></span>");
				} else {
                    p.html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$lang['DASHBOARD']['UPDNOK'];?></span>");
                    $(".updates_widget_third_row").html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$lang['DASHBOARD']['UPDNOK'];?></span>");
				}
			})
		});

		$(".updates_widget_do").click(function(e){
			e.preventDefault();
			var i = $(this).data("id");
			var p = $(this).parent();
			p.html("<i class='fa fa-spinner fa-spin'></i> <?=$lang['DASHBOARD']['UPDDOING'];?>");

			$.get("?updates_widget_do=" + i, function(r){
				if(r == "ok"){
					p.html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$lang['DASHBOARD']['UPDOK'];?></span>");
				} else {
					p.html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$lang['DASHBOARD']['UPDNOK'];?></span>");
				}
			})
		});
		</script>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }
}
