<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(66)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "monitoring_announcements");} else {

    $l = $lang['MONITORING'];
    title($l['ANNOUNCEMENTS']);

    $priorities = [
        "default" => $l['ANORMAL'],
        "warning" => $l['AHIGH'],
        "danger" => $l['ACRITICAL'],
    ];

    $stati = [
        "0" => $l['STATUS0'],
        "1" => $l['STATUS1'],
        "2" => $l['STATUS2'],
    ];

    $db->query("ALTER TABLE monitoring_announcements MODIFY ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT");

    if (isset($_GET['add']) && $ari->check(67)) {
        ?>
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['ACREATE'];?> <a href="?p=monitoring_announcements" class="pull-right"><i class="fa fa-mail-reply"></i></a></h1>

        <?php
if (isset($_POST['title'])) {
            try {
                if (empty($_POST['title'])) {
                    throw new Exception($l['AERR1']);
                }

                if (empty($_POST['message'])) {
                    throw new Exception($l['AERR2']);
                }

                $start = -1;
                if (!empty($_POST['start'])) {
                    $start = strtotime($_POST['start']);
                    if ($start === false) {
                        throw new Exception($l['AERR3']);
                    }
                }

                $until = -1;
                if (!empty($_POST['until'])) {
                    $until = strtotime($_POST['until']);
                    if ($until === false) {
                        throw new Exception($l['AERR4']);
                    }
                }

                if (empty($_POST['priority']) || !array_key_exists($_POST['priority'], $priorities)) {
                    throw new Exception($l['AERR5']);
                }

                $status = intval($_POST['status'] ?? 0);
                if ($status < 0) {
                    $status = 0;
                }
                if ($status > 2) {
                    $status = 2;
                }

                $sql = $db->prepare("INSERT INTO monitoring_announcements (title, start, until, message, last_changed, priority, status) VALUES (?,?,?,?,?,?,?)");
                $sql->bind_param("siisisi", $_POST['title'], $start, $until, $_POST['message'], $t = time(), $_POST['priority'], $status);
                if (!$sql->execute()) {
                    throw new Exception($l['ERR3']);
                }
                $sql->close();

                echo '<div class="alert alert-success">' . $l['ACREATED'] . '</div>';
                unset($_POST);
            } catch (Exception $ex) {
                echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label><?=$l['ATITLE'];?></label>
                <input type="text" name="title" value="<?=isset($_POST['title']) ? htmlentities($_POST['title']) : "";?>" placeholder="<?=$l['ATITLEP'];?>" class="form-control">
            </div>

            <div class="form-group">
                <label><?=$l['MSG'];?></label>
                <textarea name="message" class="form-control" style="resize: vertical; width: 100%; height: 200px;"><?=isset($_POST['message']) ? htmlentities($_POST['message']) : "";?></textarea>
            </div>

            <div class="form-group" style="position: relative;">
                <label><?=$l['ASTART'];?></label>
                <input type="text" name="start" class="form-control datetimepicker" value="<?=!empty($_POST['start']) ? htmlentities($_POST['start']) : "";?>" placeholder="<?=$l['OPTIONAL'];?>">
            </div>

            <div class="form-group" style="position: relative;">
                <label><?=$l['AEND'];?></label>
                <input type="text" name="until" class="form-control datetimepicker" value="<?=!empty($_POST['until']) ? htmlentities($_POST['until']) : "";?>" placeholder="<?=$l['OPTIONAL'];?>">
            </div>

            <div class="form-group">
                <label><?=$l['PRIORITY'];?></label>
                <select name="priority" class="form-control">
                    <?php foreach ($priorities as $k => $v) {?>
                    <option value="<?=$k;?>"<?=!empty($_POST['priority']) && $_POST['priority'] == $k ? ' selected=""' : '';?>><?=$v;?></option>
                    <?php }?>
                </select>
            </div>

            <div class="form-group">
                <label><?=$l['STATUS'];?></label>
                <select name="status" class="form-control">
                    <?php foreach ($stati as $k => $v) {?>
                    <option value="<?=$k;?>"<?=!empty($_POST['status']) && $_POST['status'] == $k ? ' selected=""' : '';?>><?=$v;?></option>
                    <?php }?>
                </select>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="<?=$l['AADD'];?>">
        </form>
    </div>
</div>
<?php
} else if (!empty($_GET['edit']) && $ari->check(67) && $db->query("SELECT 1 FROM monitoring_announcements WHERE ID = " . intval($_GET['edit']))->num_rows == 1) {
        $info = $db->query("SELECT * FROM monitoring_announcements WHERE ID = " . intval($_GET['edit']))->fetch_object();
        ?>
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['AEDIT'];?> <small><?=htmlentities($info->title);?></small><a href="?p=monitoring_announcements" class="pull-right"><i class="fa fa-mail-reply"></i></a></h1>

        <?php
if (isset($_POST['title'])) {
            try {
                if (empty($_POST['title'])) {
                    throw new Exception($l['AERR1']);
                }

                if (empty($_POST['message'])) {
                    throw new Exception($l['AERR2']);
                }

                $start = -1;
                if (!empty($_POST['start'])) {
                    $start = strtotime($_POST['start']);
                    if ($start === false) {
                        throw new Exception($l['AERR3']);
                    }
                }

                $until = -1;
                if (!empty($_POST['until'])) {
                    $until = strtotime($_POST['until']);
                    if ($until === false) {
                        throw new Exception($l['AERR4']);
                    }
                }

                if (empty($_POST['priority']) || !array_key_exists($_POST['priority'], $priorities)) {
                    throw new Exception($l['AERR5']);
                }

                $status = intval($_POST['status'] ?? 0);
                if ($status < 0) {
                    $status = 0;
                }
                if ($status > 2) {
                    $status = 2;
                }

                $sql = $db->prepare("UPDATE monitoring_announcements SET title = ?, start = ?, until = ?, message = ?, last_changed = ?, priority = ?, status = ? WHERE ID = ?");
                $sql->bind_param("siisisii", $_POST['title'], $start, $until, $_POST['message'], $t = time(), $_POST['priority'], $status, $info->ID);
                if (!$sql->execute()) {
                    throw new Exception($l['AERR3']);
                }
                $sql->close();

                echo '<div class="alert alert-success">' . $l['AEDSUC'] . '</div>';
                $info = $db->query("SELECT * FROM monitoring_announcements WHERE ID = " . intval($_GET['edit']))->fetch_object();
                unset($_POST);
            } catch (Exception $ex) {
                echo '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label><?=$l['ATITLE'];?></label>
                <input type="text" name="title" value="<?=isset($_POST['title']) ? htmlentities($_POST['title']) : htmlentities($info->title);?>" placeholder="<?=$l['ATITLEP'];?>" class="form-control">
            </div>

            <div class="form-group">
                <label><?=$l['MSG'];?></label>
                <textarea name="message" class="form-control" style="resize: vertical; width: 100%; height: 200px;"><?=isset($_POST['message']) ? htmlentities($_POST['message']) : htmlentities($info->message);?></textarea>
            </div>

            <div class="form-group" style="position: relative;">
                <label><?=$l['ASTART'];?></label>
                <input type="text" name="start" class="form-control datetimepicker" value="<?=!empty($_POST['start']) ? htmlentities($_POST['start']) : ($info->start >= 0 ? $dfo->format($info->start, true, true, "") : "");?>" placeholder="<?=$l['OPTIONAL'];?>">
            </div>

            <div class="form-group" style="position: relative;">
                <label><?=$l['AEND'];?></label>
                <input type="text" name="until" class="form-control datetimepicker" value="<?=!empty($_POST['until']) ? htmlentities($_POST['until']) : ($info->until >= 0 ? $dfo->format($info->until, true, true, "") : "");?>" placeholder="<?=$l['OPTIONAL'];?>">
            </div>

            <?php
$prio = $info->priority;
        if (!empty($_POST['priority'])) {
            $prio = $_POST['priority'];
        }
        ?>
            <div class="form-group">
                <label><?=$l['PRIORITY'];?></label>
                <select name="priority" class="form-control">
                    <?php foreach ($priorities as $k => $v) {?>
                    <option value="<?=$k;?>"<?=$prio == $k ? ' selected=""' : '';?>><?=$v;?></option>
                    <?php }?>
                </select>
            </div>

            <?php
$status = $info->status;
        if (!empty($_POST['status'])) {
            $status = $_POST['status'];
        }
        ?>
            <div class="form-group">
                <label><?=$l['STATUS'];?></label>
                <select name="status" class="form-control">
                    <?php foreach ($stati as $k => $v) {?>
                    <option value="<?=$k;?>"<?=$status == $k ? ' selected=""' : '';?>><?=$v;?></option>
                    <?php }?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <input type="submit" class="btn btn-primary btn-block" value="<?=$l['AEDIT'];?>">
                </div>
                <div class="col-md-6">
                    <a href="?p=monitoring_announcements&del=<?=$info->ID;?>" class="btn btn-danger btn-block"><?=$l['ADEL'];?></a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
} else if (!empty($_GET['del']) && $ari->check(67) && $db->query("SELECT 1 FROM monitoring_announcements WHERE ID = " . intval($_GET['del']))->num_rows == 1) {
        $db->query("DELETE FROM monitoring_announcements WHERE ID = " . intval($_GET['del']));
        header('Location: ?p=monitoring_announcements');
        exit;
    } else {
        ?>
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><small><a href="?p=monitoring"><?=$l['MONITORING'];?></a></small> <?=$l['ANNOUNCEMENTS'];?><?php if ($ari->check(67)) {?><a href="?p=monitoring_announcements&add=1" class="pull-right"><i class="fa fa-plus-circle"></i></a><?php }?></h1>

        <?php
$t = new Table("SELECT * FROM monitoring_announcements", [
            "title" => [
                "name" => $l['ATITLE'],
                "type" => "like",
            ],
            "priority" => [
                "name" => $l['PRIORITY'],
                "type" => "select",
                "options" => $priorities,
            ],
            "status" => [
                "name" => $l['STATUS'],
                "type" => "select",
                "options" => $stati,
            ],
        ], ["ID", "DESC"], "monitoring_announcements");
        echo $t->getHeader();

        ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <tr>
                    <th><?=$t->orderHeader("title", $l['ATITLE']);?></th>
                    <th><?=$t->orderHeader("start", $l['ASTART']);?></th>
                    <th><?=$t->orderHeader("until", $l['AEND']);?></th>
                    <th><?=$t->orderHeader("priority", $l['PRIORITY']);?></th>
                    <th><?=$t->orderHeader("status", $l['STATUS']);?></th>
                    <th><?=$t->orderHeader("last_changed", $l['LASTCHANGE']);?></th>
                </tr>

                <?php
$sql = $t->qry("ID DESC");
        if ($sql->num_rows) {
            while ($row = $sql->fetch_object()) {
                ?>
                        <tr>
                            <td><?php if ($ari->check(67)) {?><a href="?p=monitoring_announcements&edit=<?=$row->ID;?>"><?php }?><?=htmlentities($row->title);?><?=$ari->check(67) ? '</a>' : '';?></td>
                            <td><?=$row->start >= 0 ? $dfo->format($row->start) : "-";?></td>
                            <td><?=$row->until >= 0 ? $dfo->format($row->until) : "-";?></td>
                            <td><span class="label label-<?=htmlentities($row->priority);?>"><?=$priorities[$row->priority];?></span></td>
                            <td><span class="label label-<?=["0" => "warning", "1" => "success", "2" => "default"][$row->status];?>"><?=$stati[$row->status];?></span></td>
                            <td><?=Ticket::formatTime($row->last_changed);?></td>
                        <?php
}
        } else {
            ?>
                    <tr><td colspan="6"><center><?=$l['ANT'];?></center></td></tr>
                    <?php
}
        ?>
            </table>
        </div>
        <?=$t->getFooter();?>
    </div>
</div>
<?php }}?>