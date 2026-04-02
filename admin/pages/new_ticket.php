<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['NEW_TICKET'];
title($l['TITLE']);
menu("support");

if (!$ari->check(7)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "new_ticket");} else {

    $type = "external";
    $cid = 0;
    if (!empty($_GET['client']) && is_numeric($_GET['client']) && User::getInstance($_GET['client'], "ID")) {
        $type = "internal";
        $cid = intval($_GET['client']);
    }

    if (!empty($_POST['recipient_type']) && in_array($_POST['recipient_type'], ["internal", "external"])) {
        $type = $_POST['recipient_type'];
        $cid = 0;

        if ($type == "internal") {
            $cid = intval($_POST['user']);
        }
    }

    if (isset($_POST['add'])) {
        try {
            foreach ($_POST as $k => $v) {
                $vari = "p_" . strtolower($k);
                $$vari = $db->real_escape_string($v);
            }

            if ($type == "external") {
                $fromc = $_POST['recipient'];
                $cid = 0;
            } else {
                if ($db->query("SELECT ID FROM clients WHERE ID = " . $cid)->num_rows != 1) {
                    throw new Exception($l['ERR1']);
                }

                $u = User::getInstance($cid, "ID");
                $fromc = $u->get()['name'] . " <" . $u->get()['mail'] . ">";
            }

            if (empty($p_subject)) {
                throw new Exception($l['ERR2']);
            }

            $a = [];
            $sql = $db->query("SELECT ID FROM support_departments ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                array_push($a, $row->ID);
            }

            $sql = $db->query("SELECT ID FROM admins ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                array_push($a, $row->ID / -1);
            }

            if (!in_array($p_dept, $a)) {
                throw new Exception($l['ERR3']);
            }

            if (!in_array($p_status, ["0", "1", "2", "3"])) {
                throw new Exception($l['ERR4']);
            }

            if (!in_array($p_priority, ["1", "2", "3", "4", "5"])) {
                throw new Exception($l['ERR5']);
            }

            $db->query("INSERT INTO support_tickets (subject, dept, created, updated, priority, sender, customer, cc, status) VALUES ('" . $db->real_escape_string($p_subject) . "', " . intval($p_dept) . ", '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', " . intval($p_priority) . ", '" . $db->real_escape_string($fromc) . "', $cid, '', " . intval($p_status) . ")");

            alog("ticket", "created", $iid = $db->insert_id, $p_subject);

            $addons->runHook("TicketCreated", [
                "id" => $iid,
                "ticket" => ($t = new Ticket($iid)),
                "url" => $t->getURL(),
                "source" => "admin",
            ]);

            header('Location: ?p=support_ticket&id=' . $iid);
            exit;
        } catch (Exception $ex) {
            $error = "<div class=\"alert alert-danger\"><b>Fehler!</b> " . $ex->getMessage() . "</div>";
        }
    }
    ?>

	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE2'];?></h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<?php if (isset($error)) {
        echo $error;
    }
    ?>
    <form role="form" method="POST" action="?p=new_ticket">

   <div class="form-group">
        <label><?=$l['RECIP'];?></label> <a href="#" class="btn btn-default btn-xs<?=$type == "internal" ? ' active' : '';?> btn-type" data-type="internal">Intern</a> <a href="#" class="btn btn-default btn-xs<?=$type == "external" ? ' active' : '';?> btn-type" data-type="external">Extern</a>
        <input type="text" name="recipient" id="external" value="<?=isset($_POST['recipient']) ? $_POST['recipient'] : "";?>" placeholder="<?=$l['RECIPP1'];?> <?=htmlentities("<");?><?=$l['RECIPP2'];?><?=htmlentities(">");?>" class="form-control"<?=$type != "external" ? ' style="display: none;"' : '';?>>
        <div id="internal"<?=$type != "internal" ? ' style="display: none;"' : '';?>>
            <input type="text" class="form-control customer-input" placeholder="<?=$l['CNA'];?>" value="<?=ci($cid);?>">
            <input type="hidden" name="user" value="<?=$cid;?>">
            <div class="customer-input-results"></div>
        </div>
        <input type="hidden" name="recipient_type" value="<?=$type;?>">
   </div>

   <script>
   $("[data-type]").click(function(e) {
       e.preventDefault();

       $("#external").hide();
       $("#internal").hide();
       $("#" + $(this).data("type")).show();
       $("[name=recipient_type]").val($(this).data("type"));
       $(".btn-type").removeClass("active");
       $(this).addClass("active");
   });
   </script>

   <div class="form-group">
    <label><?=$l['SUBJECT'];?></label>
	<input type="text" name="subject" value="<?=isset($_POST['subject']) ? $_POST['subject'] : "";?>" placeholder="<?=$l['SUBJECTP'];?>" class="form-control">
   </div>

   <div class="form-group">
    <label><?=$l['DEPT'];?></label>
	<select name="dept" class="form-control">
        <?php $dept = isset($_REQUEST['dept']) ? $_REQUEST['dept'] : "";?>
        <option disabled="disabled" selected="selected"><?=$l['PCDEPT'];?></option>
        <?php
$sql = $db->query("SELECT * FROM support_departments ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        echo '<option value="' . $row->ID . '"' . ($dept == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
    }

    ?>
        <option disabled="disabled"><?=$l['PCSTAFF'];?></option>
        <?php
$sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
    while ($row = $sql->fetch_object()) {
        echo '<option value="' . ($row->ID / -1) . '"' . ($dept / -1 == $row->ID ? ' selected="selected"' : '') . '>' . $row->name . '</option>';
    }

    ?>
    </select>
   </div>

   <div class="form-group">
    <label><?=$l['STATUS'];?></label>
	<select name="status" class="form-control">
        <option value="0"<?=isset($_POST['status']) && $_POST['status'] == 0 ? ' selected=""' : '';?>><?=$l['S1'];?></option>
        <option value="1"<?=!isset($_POST['status']) || (isset($_POST['status']) && $_POST['status'] == 1) ? ' selected=""' : '';?>><?=$l['S2'];?></option>
        <option value="2"<?=isset($_POST['status']) && $_POST['status'] == 2 ? ' selected=""' : '';?>><?=$l['S3'];?></option>
        <option value="3"<?=isset($_POST['status']) && $_POST['status'] == 3 ? ' selected=""' : '';?>><?=$l['S4'];?></option>
    </select>
   </div>

   <div class="form-group">
    <label><?=$l['PRIO'];?></label>
	<select name="priority" class="form-control">
        <option value="5"<?=isset($_POST['priority']) && $_POST['priority'] == 5 ? ' selected=""' : '';?>><?=$l['P5'];?></option>
        <option value="4"<?=isset($_POST['priority']) && $_POST['priority'] == 4 ? ' selected=""' : '';?>><?=$l['P4'];?></option>
        <option value="3"<?=!isset($_POST['priority']) || (isset($_POST['priority']) && $_POST['priority'] == 3) ? ' selected=""' : '';?>><?=$l['P3'];?></option>
        <option value="2"<?=isset($_POST['priority']) && $_POST['priority'] == 2 ? ' selected=""' : '';?>><?=$l['P2'];?></option>
        <option value="1"<?=isset($_POST['priority']) && $_POST['priority'] == 1 ? ' selected=""' : '';?>><?=$l['P1'];?></option>
    </select>
   </div>

   <center><button type="submit" class="btn btn-primary btn-block" name="add"><?=$l['ADD'];?></button></center></form>

<?php }?>