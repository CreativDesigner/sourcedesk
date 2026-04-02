<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['SUPPORT_TICKETS'];

title($l['TITLE']);
menu("support");

$my_depts = array($adminInfo->ID / -1);
$sql = $db->query("SELECT dept FROM support_department_staff WHERE staff = " . intval($adminInfo->ID));
while ($row = $sql->fetch_object()) {
    $ds = $db->query("SELECT ID FROM support_departments WHERE ID = " . $row->dept);
    while ($sd = $ds->fetch_object()) {
        array_push($my_depts, $sd->ID);
    }
}

$selDept = empty($_GET['dept']) ? "IN (" . implode(",", $my_depts) . ")" : "= " . intval($_GET['dept']);
$inDept = implode(",", $my_depts);

if (!empty($_GET['dept']) && !in_array($_GET['dept'], $my_depts)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "support_tickets");
} else {

    $s = isset($_GET['s']) && is_numeric($_GET['s']) && $_GET['s'] >= 0 && $_GET['s'] <= 3 ? $_GET['s'] : "0";

    if (!empty($_GET['dept'])) {
        $name = $_GET['dept'] > 0 ? $db->query("SELECT name FROM support_departments WHERE ID = " . intval($_GET['dept']))->fetch_object()->name : $adminInfo->name;
        title($_GET['dept'] > 0 ? $name : $l['MY_TICKETS']);
    } else {
        $name = $lang['MENU']['ALLT'];
        title($name);
    }
    ?>

<div class="row">
	<div class="col-md-12">
<h1 class="page-header"><?=$l['TITLE'];?><?php if (!empty($_GET['dept'])) {?> <small><?=$name;?></small><?php }?><a href="?p=new_ticket<?php if (!empty($_GET['dept'])) {?>&dept=<?=intval($_GET['dept']);?><?php }?>" class="pull-right"><i class="fa fa-plus-circle"></i></a></h1>

		<?php
if (isset($_POST['tickets']) && is_array($_POST['tickets'])) {
        if (isset($_POST['status']) && is_numeric($_POST['status']) && $_POST['status'] >= 0 && $_POST['status'] <= 3) {
            $d = 0;
            foreach ($_POST['tickets'] as $t) {
                $db->query("UPDATE support_tickets SET status = " . intval($_POST['status']) . " WHERE ID = " . intval($t) . " AND dept IN ($inDept) AND status = " . intval($s));
                if ($db->affected_rows) {
                    $d++;
                    alog("support", "status_change", $_POST['status'], $t);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['SUC1O'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC1X']) . '</div>';
            }

        }

        if (isset($_POST['priority']) && is_numeric($_POST['priority']) && $_POST['priority'] >= 1 && $_POST['priority'] <= 5) {
            $d = 0;
            foreach ($_POST['tickets'] as $t) {
                $db->query("UPDATE support_tickets SET priority = " . intval($_POST['priority']) . " WHERE ID = " . intval($t) . " AND dept $selDept AND status = " . intval($s));
                if ($db->affected_rows) {
                    $d++;
                    alog("support", "priority_change", $_POST['priority'], $t);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['SUC2O'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC2X']) . '</div>';
            }

        }

        if (isset($_POST['dept']) && is_numeric($_POST['dept'])) {
            $d = 0;
            foreach ($_POST['tickets'] as $t) {
                $db->query("UPDATE support_tickets SET dept = " . intval($_POST['dept']) . " WHERE ID = " . intval($t) . " AND dept $selDept AND status = " . intval($s));
                if ($db->affected_rows) {
                    $d++;
                    alog("support", "dept_change", $_POST['dept'], $t);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['SUC3O'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC3X']) . '</div>';
            }

        }

        if (isset($_POST['delete'])) {
            $d = 0;
            foreach ($_POST['tickets'] as $t) {
                $db->query("DELETE FROM support_tickets WHERE ID = " . intval($t) . " AND dept $selDept AND status = " . intval($s));
                if ($db->affected_rows) {
                    $sql = $db->query("SELECT ID FROM support_ticket_answers WHERE ticket = $t");
                    while ($row = $sql->fetch_object()) {
                        $sql2 = $db->query("SELECT `file` FROM support_ticket_attachments WHERE message = {$row->ID}");
                        while ($row2 = $sql2->fetch_object()) {
                            if (substr($row2->file, 0, 5) == "file#") {
                                unlink(__DIR__ . "/../../files/tickets/" . basename(substr($row2->file, 5)));
                            }
                        }

                        $db->query("DELETE FROM support_ticket_attachments WHERE message = {$row->ID}");
                    }

                    $db->query("DELETE FROM support_ticket_answers WHERE ticket = $t");

                    $d++;
                    alog("support", "deleted", $t);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['SUC4O'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC4X']) . '</div>';
            }

        }

        if (isset($_POST['block'])) {
            $blocktypes = [];
            if ($_POST['block'] === "1" || $_POST['block'] === "3") {
                array_push($blocktypes, "subject");
            }

            if ($_POST['block'] === "2" || $_POST['block'] === "3") {
                array_push($blocktypes, "email");
            }

            $d = 0;
            foreach ($_POST['tickets'] as $t) {
                $sql = $db->query("SELECT subject, sender FROM support_tickets WHERE ID = " . intval($t) . " AND dept $selDept AND status = " . intval($s));
                if ($sql->num_rows != 1) {
                    continue;
                }

                $info = $sql->fetch_object();

                $email = $info->sender;
                $ex = explode("<", $email);
                if (count($ex) == 2) {
                    $email = rtrim($ex[1], ">");
                } else {
                    $email = $ex[0];
                }
                $subject = $info->subject;

                foreach ($blocktypes as $type) {
                    $sql = $db->query("SELECT 1 FROM support_filter WHERE `field` = '$type' AND `type` = 'is' AND `value` = '" . $db->real_escape_string($$type) . "' AND `action` = 'delete' LIMIT 1");
                    if (!$sql->num_rows) {
                        $db->query("INSERT INTO support_filter (field, type, value, action) VALUES ('$type', 'is', '" . $db->real_escape_string($$type) . "', 'delete')");
                        alog("support", "block", $type, $$type);
                    }
                }

                $db->query("DELETE FROM support_tickets WHERE ID = " . intval($t) . " AND dept $selDept AND status = " . intval($s));
                if ($db->affected_rows) {
                    $d++;
                    alog("support", "block_delete", $t);
                }
            }

            if ($d == 1) {
                echo '<div class="alert alert-success">' . $l['SUC5O'] . '</div>';
            } else if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d, $l['SUC5X']) . '</div>';
            }

        }

        if (isset($_POST['merge'])) {
            asort($_POST['tickets']);
            $new = array_shift($_POST['tickets']);

            $d = 0;
            foreach ($_POST['tickets'] as $t) {
                $db->query("DELETE FROM support_tickets WHERE ID = " . intval($t) . " AND dept $selDept AND status = " . intval($s));
                if ($db->affected_rows) {
                    alog("support", "merge", $new, $t);
                    $db->query("UPDATE support_ticket_answers SET ticket = " . intval($new) . " WHERE ticket = " . intval($t));
                    $d++;
                }
            }

            if ($d > 0) {
                echo '<div class="alert alert-success">' . str_replace("%d", $d + 1, $l['SUC6']) . '</div>';
            }

        }
    }
    ?>

		<div class="row">
			<div class="col-md-3">
				<div class="list-group">
					<a class="list-group-item<?=$s == "0" ? " active" : "";?>" href="./?p=support_tickets<?php if (!empty($_GET['dept'])) {?>&dept=<?=$_GET['dept'];?><?php }?>&s=0"><?=Ticket::getStatusNames()["0"];?> (<?=$db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 0 AND dept $selDept")->fetch_object()->c;?>)</a>
					<a class="list-group-item<?=$s == "1" ? " active" : "";?>" href="./?p=support_tickets<?php if (!empty($_GET['dept'])) {?>&dept=<?=$_GET['dept'];?><?php }?>&s=1"><?=Ticket::getStatusNames()["1"];?> (<?=$db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 1 AND dept $selDept")->fetch_object()->c;?>)</a>
					<a class="list-group-item<?=$s == "2" ? " active" : "";?>" href="./?p=support_tickets<?php if (!empty($_GET['dept'])) {?>&dept=<?=$_GET['dept'];?><?php }?>&s=2"><?=Ticket::getStatusNames()["2"];?> (<?=$db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 2 AND dept $selDept")->fetch_object()->c;?>)</a>
					<a class="list-group-item<?=$s == "3" ? " active" : "";?>" href="./?p=support_tickets<?php if (!empty($_GET['dept'])) {?>&dept=<?=$_GET['dept'];?><?php }?>&s=3"><?=Ticket::getStatusNames()["3"];?> (<?=$db->query("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 3 AND dept $selDept")->fetch_object()->c;?>)</a>
				</div>
			</div>

			<div class="col-md-9">
				<?php
$upgrades = [];
    $sql = $db->query("SELECT ID, name FROM support_upgrades ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        $upgrades[$row->ID] = $row->name;
    }

    $table = new Table("SELECT * FROM support_tickets WHERE status = " . intval($s) . " AND dept $selDept", [
        "subject" => [
            "name" => $l['SUBJECT'],
            "type" => "like",
        ],
        "sender" => [
            "name" => $l['SENDER'],
            "type" => "like",
        ],
        "priority" => [
            "name" => $l['PRIORITY'],
            "type" => "select",
            "options" => [
                "1" => Ticket::getPriorityText(false)["1"],
                "2" => Ticket::getPriorityText(false)["2"],
                "3" => Ticket::getPriorityText(false)["3"],
                "4" => Ticket::getPriorityText(false)["4"],
                "5" => Ticket::getPriorityText(false)["5"],
            ],
        ],
        "upgrade_id" => [
            "name" => $l['UPGRADE'],
            "type" => "select",
            "options" => $upgrades,
        ],
    ], ["updated", "ASC"], "support_tickets");

    echo $table->getHeader();
    ?>

				<form method="POST" id="ticket_form"><div class="table-responsive">
					<table class="table table-bordered table-striped">
						<tr>
							<th width="30px"><input type="checkbox" id="checkall" onclick="javascript:check_all(this.checked);"></th>
							<th width="10%"><?=$table->orderHeader("ID", "#");?></th>
							<th width="10%"><?=$table->orderHeader("created", $l['DATE']);?></th>
							<?php if (empty($_GET['dept'])) {?><th width="10%"><?=$lang['NEW_TICKET']['DEPT'];?></th><?php }?>
							<th width="13%"><?=$l['SENDER'];?></th>
							<th><?=$table->orderHeader("subject", $l['SUBJECT']);?></th>
							<th width="10%"><?=$table->orderHeader("priority", $l['PRIORITY']);?></th>
                            <th width="15%"><?=$table->orderHeader("updated", $l['LASTANSWER']);?></th>
						</tr>

						<?php
$sql = $table->qry("(recall > 0 AND recall < " . time() . ") DESC, status ASC, priority ASC, updated ASC, ID DESC");
    if ($sql->num_rows == 0) {?>
						<tr>
							<td colspan="8"><center><?=$l['NT'];?></center></td>
						</tr>
						<?php }
    while ($row = $sql->fetch_object()) {
        $t = new Ticket($row->ID);
        ?>
						<tr>
							<td><input type="checkbox" class="checkbox" name="tickets[]" onclick="javascript:toggle();" value="<?=$row->ID;?>"></td>
                            <td>T#<?=str_pad($row->ID, 6, "0", STR_PAD_LEFT);?></td>
							<td><?=$dfo->format($row->created);?></td>
							<?php if (empty($_GET['dept'])) {?><td><?=$t->getDepartmentName();?></td><?php }?>
							<td><?=$t->getSenderStr();?></td>
							<td><?php if ($row->recall > 0 && $row->recall <= time()) {
            echo '<i class="fa fa-clock-o"></i> ';
        }
        ?><a<?php if (!$t->hasRead()) {
            echo ' style="font-weight: bold;"';
        }
        ?> href="?p=support_ticket&id=<?=$row->ID;?>"><?=$t->html();?></a></td>
							<td><?=$t->getPriorityStr();?></td>
							<td><?=$t->getLastAnswerStr();?></td>
						</tr>
						<?php }?>
					</table>
				</div>

				<?=$l['SELECTED'];?>: <div class="btn-group">
				  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$l['CS'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<?php if ($s != 0) {?><li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '0' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getStatusNames()["0"];?></a></li><?php }?>
				  	<?php if ($s != 1) {?><li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '1' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getStatusNames()["1"];?></a></li><?php }?>
				  	<?php if ($s != 2) {?><li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '2' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getStatusNames()["2"];?></a></li><?php }?>
				  	<?php if ($s != 3) {?><li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'status', value: '3' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getStatusNames()["3"];?></a></li><?php }?>
				  </ul>
				</div> <div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$l['CP'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'priority', value: '5' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getPriorityText(false)["5"];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'priority', value: '4' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getPriorityText(false)["4"];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'priority', value: '3' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getPriorityText(false)["3"];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'priority', value: '2' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getPriorityText(false)["2"];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'priority', value: '1' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=Ticket::getPriorityText(false)["1"];?></a></li>
				  </ul>
				</div>
				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$l['CD'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<?php
$sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
    $i = 0;
    while ($row = $sql->fetch_object()) {if ($_GET['dept'] == -1 || $_GET['dept'] != $row->ID) {echo "<li><a href=\"#\" onclick=\"$('<input>').attr({ type: 'hidden', name: 'dept', value: '{$row->ID}' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;\">{$row->name}</a></li>";
        $i = 1;}}
    if ($i) {
        ?>
					<li class="divider"></li>
					<?php }
    $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        if ($_GET['dept'] != -1 || $adminInfo->ID != $row->ID) {
            echo "<li><a href=\"#\" onclick=\"$('<input>').attr({ type: 'hidden', name: 'dept', value: '-{$row->ID}' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;\">{$row->name}</a></li>";
        }
    }

    ?>
				  </ul>
				</div>
				<input type="submit" name="merge" value="<?=$l['MERGE'];?>" class="btn btn-warning" />
				<input type="submit" name="delete" value="<?=$l['DEL'];?>" class="btn btn-danger" />
				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    <?=$l['DELBLOCK'];?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'block', value: '1' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=$l['FBSUB'];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'block', value: '2' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=$l['FBSEN'];?></a></li>
				  	<li><a href="#" onclick="$('<input>').attr({ type: 'hidden', name: 'block', value: '3' }).appendTo('#ticket_form'); $('#ticket_form').submit(); return false;"><?=$l['FBBOTH'];?></a></li>
				  </ul>
				</div>
				</form>

				<br /><?=$table->getFooter();?>
			</div>
		</div>
	</div>
</div>
<?php }