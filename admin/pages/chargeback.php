<?php
$l = $lang['CHARGEBACK'];
title($l['TITLE']);
menu("customers");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(16)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "chargeback");} else {

    $id = intval($_GET['id'] ?? 0);
    $sql = $db->query("SELECT * FROM client_transactions WHERE ID = $id AND amount > 0 AND chargeback = 0");
    if (!$sql->num_rows) {
        require __DIR__ . "/error.php";
    } else {
        $info = $sql->fetch_object();
        $user = User::getInstance($info->user, "ID");

        if (!$user) {
            require __DIR__ . "/error.php";
        } else {
            if (isset($_POST['mail'])) {
                $ex = explode("|", $info->subject, 2);
                $transactions->insert($ex[0], $ex[1], $info->amount / -1, $info->user);
                $iid = $db->insert_id;
                $user->set(["credit" => $user->get()['credit'] - $info->amount]);

                $db->query("UPDATE client_transactions SET chargeback = $iid WHERE ID = $info->ID");

                $fee = doubleval($nfo->phpize($_POST['fee']));
                if ($fee) {
                    $transactions->insert("chargebackfee", $info->ID, $fee / -1, $info->user);
                    $user->set(["credit" => $user->get()['credit'] - $fee]);
                }

                if (!empty($_POST['mail'])) {
                    $vars = [
                        "date" => $dfo->format($info->time, false, false, "-", $user->getDateFormat()),
                        "subject" => Transactions::subject($info->subject),
                        "amount" => $cur->infix($nfo->format($info->amount, 2, 0, $user->getNumberFormat()), $cur->getBaseCurrency()),
                        "fee" => $cur->infix($nfo->format($fee, 2, 0, $user->getNumberFormat()), $cur->getBaseCurrency()),
                    ];

                    $mtObj = new MailTemplate($_POST['mail']);
                    $titlex = $mtObj->getTitle($user->getLanguage());
                    $mail = $mtObj->getMail($user->getLanguage(), $user->get()['name']);

                    $maq->enqueue($vars, $mtObj, $user->get()['mail'], $titlex, $mail, "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($user->getLanguage()));
                }

                header('Location: ?p=customers&edit=' . $info->user . '&tab=transactions');
                exit;
            }

            ?>
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<form role="form" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th><?=$l['TIME'];?></th>
                            <td><?=$dfo->format($info->time);?></td>
                        </tr>

                        <tr>
                            <th><?=$l['USER'];?></th>
                            <td><a href="?p=customers&edit=<?=$info->user;?>"><?=$user->getfName();?></a></td>
                        </tr>

                        <tr>
                            <th><?=$l['SUBJECT'];?></th>
                            <td><?=Transactions::subject($info->subject);?></td>
                        </tr>

                        <tr>
                            <th><?=$l['AMOUNT'];?></th>
                            <td><?=$cur->infix($nfo->format($info->amount), $cur->getBaseCurrency());?></td>
                        </tr>
                    </table>
                </div>

                <div class="form-group">
                    <label><?=$l['MAIL'];?></label>
                    <select name="mail" class="form-control">
                        <option value="0"><?=$l['NOMAIL'];?></option>
                        <?php
$sql = $db->query("SELECT ID, `name`, `foreign_name` FROM email_templates WHERE (`name` LIKE 'Rückbuchung%' OR `name` LIKE '%Chargeback' OR `foreign_name` LIKE 'Rückbuchung%' OR `foreign_name` LIKE '%Chargeback') AND `active` = 1");
            while ($row = $sql->fetch_object()) {
                if ($lang['ISOCODE'] != "de") {
                    $row->name = $row->foreign_name;
                }

                echo '<option value="' . $row->ID . '">' . htmlentities($row->name) . '</option>';
            }
            ?>
                    </select>
                    <span class="help-block"><?=$l['HINTMAIL'];?></span>
                </div>

                <div class="form-group">
                    <label><?=$l['FEE'];?></label>
                    <div class="input-group">
                        <?php
if ($cur->getPrefix()) {
                echo '<span class="input-group-addon">' . $cur->getPrefix() . '</span>';
            }
            ?>
                        <input type="text" name="fee" class="form-control" placeholder="<?=$nfo->placeholder();?>">
                        <?php
if ($cur->getSuffix()) {
                echo '<span class="input-group-addon">' . $cur->getSuffix() . '</span>';
            }
            ?>
                    </div>
                </div>

  <button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['DO'];?></button></form>

<?php }}}?>