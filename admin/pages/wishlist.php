<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['WISHLIST'];

title($l['TITLE']);
menu("products");

if (!$ari->check(59)) {require __DIR__ . "/error.php";if (!$ari->check(59)) {
    alog("general", "insufficient_page_rights", "wishlist");
}
} else {

    $tab = isset($_GET['t']) ? $_GET['t'] : "entries";

    function wishcolor($a)
    {
        $a = substr($a, 0, 1);

        if ($a == 0) {
            return "red";
        }

        if ($a == 4) {
            return "black";
        }

        if ($a != 7 && $a != 8) {
            return "orange";
        }

        return "green";
    }

    function wishstatus($a)
    {
        global $l;

        $b = @substr($a, 1);
        $a = substr($a, 0, 1);

        switch ($a) {
            case "0":
            case "":
                return $l['S1'];
                break;

            case "1":
                return $l['S2'];
                break;

            case "2":
                return str_replace("%b", $b, $l['S3']);
                break;

            case "3":
                return str_replace("%b", $b, $l['S4']);
                break;

            case "4":
                return $l['S5'];
                break;

            case "5":
                return $l['S6'];
                break;

            case "6":
                return $l['S7'];
                break;

            case "7":
                return $l['S8'];
                break;

            case "8":
                return $l['S9'];
                break;
        }
    }

    if ($tab == "comments" && isset($_GET['delete']) && $db->query("DELETE FROM wishlist_comments WHERE ID = " . intval($_GET['delete'])) && $db->affected_rows) {
        $suc = $l['SUC1'];
        alog("wishlist", "comment_delete", $_GET['delete']);
    }

    if ($tab == "comments" && isset($_GET['ack'])) {
        $db->query("UPDATE wishlist_comments SET ack = 1 WHERE ID = " . intval($_GET['ack']));
        alog("wishlist", "comment_ack", $_GET['ack']);
    }

    if ($tab == "entries" && isset($_GET['delete']) && $db->query("DELETE FROM wishlist WHERE ID = " . intval($_GET['delete'])) && $db->affected_rows) {
        $db->query("DELETE FROM wishlist_comments WHERE wish = " . intval($_GET['delete']));
        $db->query("DELETE FROM wishlist_likes WHERE wish = " . intval($_GET['delete']));
        $db->query("DELETE FROM wishlist_wish_abo WHERE wish = " . intval($_GET['delete']));
        $suc = $l['SUC2'];
        alog("wishlist", "delete", $_GET['delete']);
    }

    if ($tab == "entries" && isset($_GET['entry']) && $db->query("SELECT 1 FROM wishlist WHERE ID = " . intval($_GET['entry']))->num_rows == 1) {
        $entry = $db->query("SELECT * FROM wishlist WHERE ID = " . intval($_GET['entry']))->fetch_object();
        if (!$entry->ack) {
            $db->query("UPDATE wishlist SET ack = 1 WHERE ID = {$entry->ID}");
        }

        if (isset($_GET['dc']) && $db->query("DELETE FROM wishlist_comments WHERE ID = " . intval($_GET['dc']) . " AND wish = " . $entry->ID) && $db->affected_rows) {
            $suc = $l['SUC1'];
            alog("wishlist", "comment_delete", $_GET['dc']);
        }
    }

    $lack = $db->query("SELECT 1 FROM wishlist WHERE ack = 0")->num_rows;
    $lack2 = $db->query("SELECT 1 FROM wishlist_comments WHERE ack = 0")->num_rows;

    if (isset($_POST['date'])) {
        $arr = array();
        if (!empty($_POST['date']) && strtotime($_POST['date']) !== false) {
            $arr['date'] = date("Y-m-d", strtotime($_POST['date']));
        }

        if (!empty($_POST['title'])) {
            $arr['title'] = trim($_POST['title']);
        }

        if (!empty($_POST['description'])) {
            $arr['description'] = trim($_POST['description']);
        }

        if (!empty($_POST['user'])) {
            $arr['user'] = intval($_POST['user']);
        }

        if (!empty($_POST['product'])) {
            $arr['product'] = intval($_POST['product']);
        }

        if (isset($_POST['status'])) {
            $arr['answer'] = $_POST['status'];
        }

        if (isset($arr['answer']) && $arr['answer'] == "4") {
            $arr['answer'] .= trim($_POST['status_' . $arr['answer']]);
        }

        if (isset($arr['answer']) && in_array($arr['answer'], array("2", "3"))) {
            $arr['answer'] .= $nfo->phpize(trim($_POST['status_' . $arr['answer']]));
        }

        $set = "";
        foreach ($arr as $k => $v) {
            $set .= "`$k` = '" . $db->real_escape_string($v) . "', ";
        }

        $set = rtrim($set, ", ");

        if (!empty($set)) {
            $db->query("UPDATE wishlist SET $set WHERE ID = " . intval($_GET['entry']));
            alog("wishlist", "changed", $_GET['entry']);
        }

        if ($_POST['status'] != $entry->answer) {
            $sql = $db->query("SELECT user FROM wishlist_wish_abo WHERE wish = " . $entry->ID);
            while ($row = $sql->fetch_object()) {
                $u = new User($row->user, "ID");
                if ($u->get()['ID'] != $row->user) {
                    continue;
                }

                $mtObj = new MailTemplate("Statusänderung (Abo)");
                $titlex = $mtObj->getTitle($u->getLanguage());
                $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);
                $maq->enqueue([
                    "wish" => $entry->title,
                    "url" => $CFG['PAGEURL'] . "wishlist/wish/" . $entry->ID,
                ], $mtObj, $u->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], false, 0, 0, $mtObj->getAttachments($CFG['LANG']));
            }

            alog("wishlist", "status_change", $entry->ID, $_POST['status'], $entry->answer);
        }

        $entry = $db->query("SELECT * FROM wishlist WHERE ID = " . intval($_GET['entry']))->fetch_object();
    }

    ?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?></h1>
    </div>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="list-group">
			<a class="list-group-item<?=$tab == 'entries' ? ' active' : '';?>" href="./?p=wishlist&t=entries"><?=$l['T1'];?><?php if ($lack > 0) {?> <span class="label label-warning"><?=$lack;?><?php }?></span></a>
			<a class="list-group-item<?=$tab == 'comments' ? ' active' : '';?>" href="./?p=wishlist&t=comments"><?=$l['T2'];?><?php if ($lack2 > 0) {?> <span class="label label-warning"><?=$lack2;?><?php }?></span></a>
		</div>

		<?php if (isset($entry)) {?>
		<div class="list-group">
			<a class="list-group-item" href="./?p=wishlist&t=entries&delete=<?=$entry->ID;?>"><?=$l['T3'];?></span></a>
		</div>
		<?php }?>
	</div>

	<div class="col-md-9">
		<?php if ($tab == "entries") {if (!isset($entry)) {?>
			<?=isset($suc) ? '<div class="alert alert-success">' . $suc . '</div>' : "";?>
			<?php
$t = new Table("SELECT * FROM wishlist", [
        "title" => [
            "name" => $l['TITLE2'],
            "type" => "like",
        ],
        "description" => [
            "name" => $l['DESC'],
            "type" => "like",
        ],
    ]);
        echo $t->getHeader();
        ?>

			<div class="table-responsive">
				<table class="table table-bordered">
					<tr>
						<th><?=$l['DATE'];?></th>
						<th><?=$l['PRODUCT'];?></th>
						<th><?=$l['CUSTOMER'];?></th>
						<th><?=$l['TITLE2'];?></th>
						<th width="50px"><center><i class="fa fa-thumbs-up"></i></center></th>
						<th width="50px"><center><i class="fa fa-envelope-o"></i></center></th>
						<th width="50px"><center><i class="fa fa-comments"></i></center></th>
						<th><?=$l['STATUS'];?></th>
						<th width="20px"></th>
					</tr>

					<?php
$sql = $t->qry("ack ASC, date DESC, ID DESC");
        if ($sql->num_rows == 0) {?>
					<tr><td colspan="9"><center><?=$l['NT1'];?></center></td></tr>
					<?php } else {while ($row = $sql->fetch_object()) {?>
					<tr>
						<td><?=$dfo->format($row->date, false);?></td>
						<td><a href="?p=products&edit=<?=$row->product;?>"><?php $ps = $db->query("SELECT name FROM products WHERE ID = {$row->product}");if ($ps->num_rows != 1) {
            echo '<i>' . $l['UK'] . '</i>';
        } else {
            echo unserialize($ps->fetch_object()->name)[$CFG['LANG']];
        }
            ?></a></td>
						<td><a href="?p=customers&edit=<?=$row->user;?>"><?php $u = new User($row->user, "ID");if ($u->get()['ID'] != $row->user) {
                echo '<i>' . $l['UK'] . '</i>';
            } else {
                echo htmlentities($u->get()['name']);
            }
            ?></a></td>
						<td><?=htmlentities($row->title);?></td>
						<td><center><?=$db->query("SELECT 1 FROM wishlist_likes WHERE wish = {$row->ID}")->num_rows;?></center></td>
						<td><center><?=$db->query("SELECT 1 FROM wishlist_wish_abo WHERE wish = {$row->ID}")->num_rows;?></center></td>
						<td><center><?=$db->query("SELECT 1 FROM wishlist_comments WHERE wish = {$row->ID}")->num_rows;?></center></td>
						<td<?=!$row->ack ? ' style="background-color: khaki;"' : "";?>><font color="<?=wishcolor($row->answer);?>"><?=wishstatus($row->answer);?></font></td>
						<td><a href="?p=wishlist&t=entries&entry=<?=$row->ID;?>"><i class="fa fa-arrow-right"></i></a></td>
					</tr>
					<?php }}?>
				</table>
			</div>
		<?php echo $t->getFooter();} else { ?>
		<?=isset($suc) ? '<div class="alert alert-success">' . $suc . '</div>' : "";?>
		<div class="row">
			<form method="POST"><div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['DATA'];?></div>
					<div class="panel-body">
						<div class="form-group">
							<label><?=$l['DATE'];?></label>
							<input type="text" name="date" class="form-control" value="<?=$dfo->format($entry->date, false);?>" />
						</div>

						<div class="form-group">
							<label><?=$l['TITLE2'];?></label>
							<input type="text" name="title" class="form-control" value="<?=htmlentities($entry->title);?>" />
						</div>

						<div class="form-group">
							<label><?=$l['DESC'];?></label>
							<textarea name="description" class="form-control" style="width: 100%; height: 150px; resize: vertical;"><?=htmlentities($entry->description);?></textarea>
						</div>

						<div class="form-group">
							<label><?=$l['CUSTOMER'];?></label>
							<input type="text" class="form-control customer-input" placeholder="<?=$l['SC'];?>" value="<?=ci($entry->user);?>">
							<input type="hidden" name="user" value="<?=$entry->user;?>">
							<div class="customer-input-results"></div>
						</div>

						<div class="form-group" style="margin-bottom: 0;">
							<label><?=$l['PRODUCT'];?></label>
							<select name="product" class="form-control">
								<?php
$sql = $db->query("SELECT ID, name FROM products");
        while ($row = $sql->fetch_object()) {
            echo '<option value="' . $row->ID . '"' . ($row->ID == $entry->product ? ' selected="selected"' : '') . '>' . unserialize($row->name)[$CFG['LANG']] . '</option>';
        }

        ?>
							</select>
						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['STATUS'];?></div>
					<div class="panel-body">
						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value=""<?=substr($entry->answer, 0, 1) == "0" || empty($entry->answer) ? ' checked="checked"' : '';?>>
						    <?=$l['S21'];?>
						  </label>
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="1"<?=substr($entry->answer, 0, 1) == "1" ? ' checked="checked"' : '';?>>
						    <?=$l['S2'];?>
						  </label>
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="2"<?=substr($entry->answer, 0, 1) == "2" ? ' checked="checked"' : '';?>>
						    <?=$l['S22'];?>
						  </label>

						  <div class="form-group status_2" style="<?=substr($entry->answer, 0, 1) != "2" ? 'display: none;' : "";?>">
						  <div class="input-group">
						  	<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon status_2" style="<?=substr($entry->answer, 0, 1) != "2" ? 'display: none;' : "";?>"><?=$cur->getPrefix();?></span><?php }?>
						  	<input type="text" name="status_2" class="form-control input-sm" style="<?=substr($entry->answer, 0, 1) != "2" ? 'display: none;' : "";?>margin-top: 10px;" value="<?=@substr($entry->answer, 1) ? $nfo->format(substr($entry->answer, 1)) : "";?>" placeholder="<?=$nfo->placeholder();?>" />
						  	<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon status_2" style="<?=substr($entry->answer, 0, 1) != "2" ? 'display: none;' : "";?>"><?=$cur->getSuffix();?></span><?php }?>
						  </div>
						  </div>
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="3"<?=substr($entry->answer, 0, 1) == "3" ? ' checked="checked"' : '';?>>
						    <?=$l['S23'];?>
						  </label>

						  <div class="form-group status_3" style="<?=substr($entry->answer, 0, 1) != "3" ? 'display: none;' : "";?>">
						  <div class="input-group">
						  	<?php if (!empty($cur->getPrefix())) {?><span class="input-group-addon status_3" style="<?=substr($entry->answer, 0, 1) != "3" ? 'display: none;' : "";?>"><?=$cur->getPrefix();?></span><?php }?>
						  	<input type="text" name="status_3" class="form-control input-sm" style="<?=substr($entry->answer, 0, 1) != "3" ? 'display: none;' : "";?>margin-top: 10px;" value="<?=@substr($entry->answer, 1) ? $nfo->format(substr($entry->answer, 1)) : "";?>" placeholder="<?=$nfo->placeholder();?>" />
						  	<?php if (!empty($cur->getSuffix())) {?><span class="input-group-addon status_3" style="<?=substr($entry->answer, 0, 1) != "3" ? 'display: none;' : "";?>"><?=$cur->getSuffix();?></span><?php }?>
						  </div>
						  </div>
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="4"<?=substr($entry->answer, 0, 1) == "4" ? ' checked="checked"' : '';?>>
						    <?=$l['S5'];?>
						  </label>

						  <input type="text" name="status_4" class="form-control input-sm" style="<?=substr($entry->answer, 0, 1) != "4" ? 'display: none;' : "";?>margin-top: 10px;" value="<?=@substr($entry->answer, 1);?>" placeholder="Begr&uuml;ndung" />
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="5"<?=substr($entry->answer, 0, 1) == "5" ? ' checked="checked"' : '';?>>
						    <?=$l['S24'];?>
						  </label>
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="6"<?=substr($entry->answer, 0, 1) == "6" ? ' checked="checked"' : '';?>>
						    <?=$l['S25'];?>
						  </label>
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="7"<?=substr($entry->answer, 0, 1) == "7" ? ' checked="checked"' : '';?>>
						    <?=$l['S26'];?>
						  </label>
						</div>

						<div class="radio" style="margin-top: 0;">
						  <label>
						    <input type="radio" name="status" value="8"<?=substr($entry->answer, 0, 1) == "8" ? ' checked="checked"' : '';?>>
						    <?=$l['S27'];?>
						  </label>
						</div>
					</div>
				</div>

				<script>
				$(document).ready(function() {
					$("[name=status]").click(function() {
						var v = "0";
						$("[name=status]").each(function() {
							if($(this).is(":checked")) v = $(this).val();
						});

						$("[name=status_2]").hide();
						$(".status_2").hide();
						$("[name=status_3]").hide();
						$(".status_3").hide();
						$("[name=status_4]").hide();
						if(v == "2"){
							$("[name=status_2]").show();
							$(".status_2").show();
						}
						if(v == "3"){
							$("[name=status_3]").show();
							$(".status_3").show();
						}
						if(v == "4") $("[name=status_4]").show();
					});
				});
				</script>

				<input type="submit" value="<?=$l['SAVE'];?>" class="btn btn-primary btn-block" />
			</div></form>
<?php
if (!empty($_POST['our_comment'])) {
            $fields = "wish, user, time, message, author, ack";
            $vs = array($entry->ID, 0, time(), $_POST['our_comment'], "admin", 1);
            $values = "";
            foreach ($vs as $v) {
                $values .= "'" . $db->real_escape_string($v) . "', ";
            }

            $values = rtrim($values, ", ");

            $db->query("INSERT INTO wishlist_comments ($fields) VALUES ($values)");

            $sql = $db->query("SELECT user FROM wishlist_wish_abo WHERE wish = " . $entry->ID);
            while ($row = $sql->fetch_object()) {
                $u = new User($row->user, "ID");
                if ($u->get()['ID'] != $row->user) {
                    continue;
                }

                $mtObj = new MailTemplate("Neuer Kommentar (Abo)");
                $titlex = $mtObj->getTitle($u->getLanguage());
                $mail = $mtObj->getMail($u->getLanguage(), $u->get()['name']);

                $maq->enqueue([
                    "author" => $CFG['PAGENAME'],
                    "wish" => $db->query("SELECT title FROM wishlist WHERE ID = " . $entry->ID)->fetch_object()->title,
                    "url" => $CFG['PAGEURL'] . "wishlist/wish/" . $entry->ID,
                ], $mtObj, $u->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $u->get()['ID'], false, 0, 0, $mtObj->getAttachments($CFG['LANG']));
            }
        }
        ?>
			<form method="POST"><div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['T2'];?><span class="pull-right label label-default"><?=$c = $db->query("SELECT user FROM wishlist_comments WHERE wish = " . $entry->ID)->num_rows;?></span></div>
					<div class="panel-body">
						<?php
$sql = $db->query("SELECT * FROM wishlist_comments WHERE wish = " . $entry->ID . " ORDER BY time ASC, ID ASC");
        if ($sql->num_rows == 0) {
            echo "<i>{$l['NCT']}</i>";
        }

        $i = 0;
        while ($row = $sql->fetch_object()) {
            $u = new User($row->user, "ID");
            if ($u->get()['ID'] == $row->user) {
                $row->author = '<a href="?p=customers&edit=' . $row->user . '">' . htmlentities($row->author) . '</a></li>';
            }

            ?>
							<small><?=$row->author != "admin" ? $row->author : $CFG['PAGENAME'] . " <span class='label label-primary'>{$l['OFFICIAL']}</span>";?> // <?=$dfo->format($row->time);?> // <a href="?p=wishlist&t=entries&entry=<?=$entry->ID;?>&dc=<?=$row->ID;?>"><?=$l['DEL'];?></a></small><br />
							<?=nl2br(htmlentities($row->message));?>
							<?php
$i++;
            if ($i < $c) {
                echo "<hr />";
            }

        }
        ?>

						<form method="POST">
							<textarea name="our_comment" class="form-control" style="width: 100%; height: 120px; resize: vertical; margin-bottom: 10px; margin-top: 25px;"></textarea>
							<input type="submit" class="btn btn-primary btn-block" value="<?=$l['MAKECOM'];?>">
						</form>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['LIKES'];?><span class="pull-right label label-default"><?=$db->query("SELECT user FROM wishlist_likes WHERE wish = " . $entry->ID)->num_rows;?></span></div>
					<div class="panel-body">
						<?php
$sql = $db->query("SELECT user FROM wishlist_likes WHERE wish = " . $entry->ID);
        if ($sql->num_rows == 0) {
            echo "<i>{$l['NLY']}</i>";
        } else {
            echo "<ul style=\"margin-bottom: 0;\">";
        }

        while ($row = $sql->fetch_object()) {
            $u = new User($row->user, "ID");
            if ($u->get()['ID'] != $row->user) {
                echo '<li><i>' . $l['UK'] . '</i></li>';
            } else {
                echo '<li><a href="?p=customers&edit=' . $row->user . '">' . htmlentities($u->get()['name']) . '</a></li>';
            }

        }
        if ($sql->num_rows != 0) {
            echo "</ul>";
        }

        ?>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading"><?=$l['ABOS'];?><span class="pull-right label label-default"><?=$db->query("SELECT user FROM wishlist_wish_abo WHERE wish = " . $entry->ID)->num_rows;?></span></div>
					<div class="panel-body">
						<?php
$sql = $db->query("SELECT user FROM wishlist_wish_abo WHERE wish = " . $entry->ID);
        if ($sql->num_rows == 0) {
            echo "<i>{$l['NAY']}</i>";
        } else {
            echo "<ul style=\"margin-bottom: 0;\">";
        }

        while ($row = $sql->fetch_object()) {
            $u = new User($row->user, "ID");
            if ($u->get()['ID'] != $row->user) {
                echo '<li><i>' . $l['UK'] . '</i></li>';
            } else {
                echo '<li><a href="?p=customers&edit=' . $row->user . '">' . htmlentities($u->get()['name']) . '</a></li>';
            }

        }
        if ($sql->num_rows != 0) {
            echo "</ul>";
        }

        ?>
					</div>
				</div>
			</div></form>
		</div>
		<?php }} else if ($tab == "comments") {?>
		<?=isset($suc) ? '<div class="alert alert-success">' . $suc . '</div>' : "";?>
		<?php
$t = new Table("SELECT * FROM wishlist_comments", [
        "author" => [
            "name" => $l['AUTHOR'],
            "type" => "like",
        ],
    ]);
        echo $t->getHeader();
        ?>
		<div class="table-responsive">
			<table class="table table-bordered">
				<tr>
					<th><?=$l['TIME'];?></th>
					<th><?=$l['WISH'];?></th>
					<th><?=$l['AUTHOR'];?></th>
					<th><?=$l['MSG'];?></th>
					<th width="28px"></th>
					<th width="28px"></th>
				</tr>

				<?php
$sql = $t->qry("ack ASC, time DESC, ID DESC");
        if ($sql->num_rows == 0) {?>
				<tr><td colspan="6"><center><?=$l['NCAA'];?></center></td></tr>
				<?php } else {while ($row = $sql->fetch_object()) {?>
				<tr>
					<td style="vertical-align: middle;"><?=$dfo->format($row->time);?></td>
					<td style="vertical-align: middle;"><a href="?p=wishlist&entry=<?=$row->wish;?>"><?php $ps = $db->query("SELECT title FROM wishlist WHERE ID = {$row->wish}");if ($ps->num_rows != 1) {
            echo '<i>' . $l['UK'] . '</i>';
        } else {
            echo htmlentities($ps->fetch_object()->title);
        }
            ?></a></td>
					<td style="vertical-align: middle;"><?php if ($shouldLink = ($row->user && User::getInstance($row->user, "ID"))) {?><a href="?p=customers&edit=<?=$row->user;?>"><?php }?><?=$row->author != "admin" ? $row->author : $CFG['PAGENAME'] . " <span class='label label-primary'>{$l['OFFICIAL']}</span>";if ($shouldLink) {?></a><?php }?></td>
					<td<?=!$row->ack ? ' style="background-color: khaki;"' : "";?>><?=nl2br(htmlentities($row->message));?></td>
					<td style="vertical-align: middle;"><?php if (!$row->ack) {?><a href="?p=wishlist&t=comments&ack=<?=$row->ID;?>"><i class="fa fa-check"></i></a><?php }?></td>
					<td style="vertical-align: middle;"><a href="?p=wishlist&t=comments&delete=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
				</tr>
				<?php }}?>
			</table>
		</div>
		<?php echo $t->getFooter();} else { ?>
			<div class="alert alert-danger"><?=$l['PNF'];?></div>
		<?php }?>
	</div>
</div>

<?php }?>