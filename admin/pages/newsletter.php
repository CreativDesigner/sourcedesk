<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['NEWSLETTER'];
title($l['TITLE']);
menu("customers");

if (!$ari->check(21)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "newsletter");} else { $tab = isset($_GET['tab']) ? $_GET['tab'] : "send";?>
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header"><?=$l['TITLE'];?></h1>

      <div class="row">
    <div class="col-md-3">
            <div class="list-group">
            <a class="list-group-item<?=$tab == 'send' ? ' active' : '';?>" href="?p=newsletter"><?=$l['TAB1'];?></a>
            <a class="list-group-item<?=$tab == 'archive' ? ' active' : '';?>" href="?p=newsletter&tab=archive"><?=$l['TAB2'];?></a>
            </div>

            <div class="list-group">
            <a class="list-group-item<?=$tab == 'recipients' ? ' active' : '';?>" href="?p=newsletter&tab=recipients"><?=$l['TAB3'];?></a>
            <a class="list-group-item<?=$tab == 'recipients_ext' ? ' active' : '';?>" href="?p=newsletter&tab=recipients_ext"><?=$l['TAB4'];?></a>
            </div>

            <div class="list-group">
            <a class="list-group-item<?=$tab == 'categories' ? ' active' : '';?>" href="?p=newsletter&tab=categories"><?=$l['TAB5'];?></a>
            </div>
        </div>

    <div class="col-md-9">

		<?php
if ($tab == "send") {

    ?>
    <form method="GET">
      <select name="lang" class="form-control" onchange="this.form.submit()">
        <option value=""><?=$l['PCL'];?></option>
        <?php foreach ($languages as $key => $name) {
        $std = "";
        if ($key == $CFG['LANG']) {
            $std = " OR language = ''";
        }

        $customer = $db->query("SELECT COUNT(ID) as num FROM clients WHERE (language = '" . $db->real_escape_string($key) . "'$std) AND newsletter != '' AND locked = 0")->fetch_object()->num;
        $customer += $db->query("SELECT COUNT(*) as num FROM newsletter WHERE (language = '" . $db->real_escape_string($key) . "'$std) AND hash = ''")->fetch_object()->num;
        ?>
        <option value="<?=$key;?>"<?=$customer == 0 ? " disabled" : "";?><?=isset($_GET['lang']) && $_GET['lang'] == $key && $customer > 0 ? " selected='selected'" : "";?>><?=$name;?> [<?=$customer;?> <?=$l['RECIPIENTS'];?>]</option>
        <?php }?>
      </select>
      <input type="hidden" name="p" value="newsletter">
    </form><br />
    <?php

    $chosenLang = isset($_GET['lang']) ? $_GET['lang'] : "";
    $std = "";
    if ($chosenLang == $CFG['LANG']) {
        $std = " OR language = ''";
    }

    if (isset($languages[$chosenLang]) && ($db->query("SELECT COUNT(ID) as num FROM clients WHERE (language = '" . $db->real_escape_string($chosenLang) . "'$std) AND newsletter = 1 AND locked = 0")->fetch_object()->num > 0 || $db->query("SELECT COUNT(ID) as num FROM newsletter WHERE (language = '" . $db->real_escape_string($chosenLang) . "'$std) AND conf_time > 0")->fetch_object()->num > 0)) {
        class NewsletterException extends Exception
            {}

        if (isset($_POST['submit'])) {
            try {
                if (!isset($_POST['title']) || trim($_POST['title']) == "") {
                    throw new NewsletterException($l['ERR1']);
                }

                if (empty($_POST['time'])) {
                    $time = time();
                } else if (strtotime($_POST['time']) === false) {
                    throw new NewsletterException($l['ERR2']);
                } else {
                    $time = strtotime($_POST['time']);
                }

                if ($time < time()) {
                    $time = time();
                }

                if (!isset($_POST['newsletter'])) {
                    throw new NewsletterException($l['ERR3']);
                }

                $choosedcats = array();
                if (is_array($_POST['cat'])) {
                    foreach ($_POST['cat'] as $id => $null) {
                        array_push($choosedcats, $id);
                    }
                }

                if (count($choosedcats) == 0) {
                    throw new NewsletterException($l['ERR4']);
                }

                $db->query("INSERT INTO client_newsletters (`time`, `language`, `subject`, `text`) VALUES (" . $time . ", '" . $db->real_escape_string($chosenLang) . "', '" . $db->real_escape_string($_POST['title']) . "', '" . $db->real_escape_string($_POST['newsletter']) . "')");
                $newsletterId = $db->insert_id;

                $where = "";

                if (isset($_POST['account_status'])) {
                    if ($_POST['account_status'] == "1") {
                        $where .= " AND locked = 0";
                    } else if ($_POST['account_status'] == "0") {
                        $where .= " AND locked = 1";
                    }

                }

                if (isset($_POST['account_type'])) {
                    if ($_POST['account_type'] == "b2c") {
                        $where .= " AND company = ''";
                    } else if ($_POST['account_type'] == "b2b") {
                        $where .= " AND company != ''";
                    }

                }

                if (isset($_POST['cgroup']) && $_POST['cgroup'] >= 0) {
                    $where .= " AND cgroup = " . intval($_POST['cgroup']);
                }

                $mails = 0;
                $sql = $db->query("SELECT mail, firstname, lastname, ID, newsletter FROM clients WHERE (language = '" . $db->real_escape_string($chosenLang) . "'$std)" . $where);
                if ($sql->num_rows > 0) {
                    while ($user = $sql->fetch_object()) {
                        if ($_POST['product'] == "0") {
                            if ($db->query("SELECT 1 FROM client_products WHERE user = {$user->ID} AND active = 1")->num_rows > 0) {
                                continue;
                            }

                        } else if ($_POST['product'] > "0") {
                            if ($db->query("SELECT 1 FROM client_products WHERE user = {$user->ID} AND active = 1 AND product = " . intval($_POST['product']))->num_rows == 0) {
                                continue;
                            }

                        }

                        if ($_POST['domains'] == "1") {
                            if ($db->query("SELECT 1 FROM domains WHERE user = {$user->ID} AND status IN ('KK_OK', 'REG_OK', 'KK_WAITING', 'REG_WAITING', 'KK_ERROR', 'REG_ERROR')")->num_rows == 0) {
                                continue;
                            }

                        } else if ($_POST['domains'] == "0") {
                            if ($db->query("SELECT 1 FROM domains WHERE user = {$user->ID} AND status IN ('KK_OK', 'REG_OK', 'KK_WAITING', 'REG_WAITING', 'KK_ERROR', 'REG_ERROR')")->num_rows > 0) {
                                continue;
                            }

                        }

                        if ($_POST['server'] > "0") {
                          if ($db->query("SELECT 1 FROM client_products WHERE user = {$user->ID} AND active = 1 AND server_id = " . intval($_POST['server']))->num_rows == 0) {
                            continue;
                          }
                        }

                        $lists = explode("|", $user->newsletter);
                        if ($_POST['newsletter_abo'] == "1") {
                            foreach ($lists as $id) {
                                if (!in_array($id, $choosedcats)) {
                                    continue;
                                }

                                $stop_url = $CFG['PAGEURL'] . "stop_newsletter/" . $user->ID . "/" . substr(hash("sha512", $CFG['HASH'] . $user->ID . $user->mail), 0, 10);
                                $maq->enqueue([], null, $user->mail, $_POST['title'], str_replace(array('%name%', '%stop%'), array($user->firstname . " " . $user->lastname, $stop_url), $_POST['newsletter']), "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->ID, false, $newsletterId, 0, [], $time);
                                $mails++;
                                break;
                            }
                        } else if ($_POST['newsletter_abo'] == "0") {
                            if (count($lists) == 0) {
                                $stop_url = $CFG['PAGEURL'] . "stop_newsletter/" . $user->ID . "/" . substr(hash("sha512", $CFG['HASH'] . $user->ID . $user->mail), 0, 10);
                                $maq->enqueue([], null, $user->mail, $_POST['title'], str_replace(array('%name%', '%stop%'), array($user->firstname . " " . $user->lastname, $stop_url), $_POST['newsletter']), "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->ID, false, $newsletterId, 0, [], $time);
                                $mails++;
                            }
                        } else if ($_POST['newsletter_abo'] == "-1") {
                            $stop_url = $CFG['PAGEURL'] . "stop_newsletter/" . $user->ID . "/" . substr(hash("sha512", $CFG['HASH'] . $user->ID . $user->mail), 0, 10);
                            $maq->enqueue([], null, $user->mail, $_POST['title'], str_replace(array('%name%', '%stop%'), array($user->firstname . " " . $user->lastname, $stop_url), $_POST['newsletter']), "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", $user->ID, false, $newsletterId, 0, [], $time);
                            $mails++;
                        }
                    }
                }

                if (isset($_POST['external'])) {
                    $sql = $db->query("SELECT email, name, lists FROM newsletter WHERE (language = '" . $db->real_escape_string($chosenLang) . "'$std) AND hash = ''");
                    if ($sql->num_rows > 0) {
                        while ($user = $sql->fetch_object()) {
                            $lists = explode("|", $user->lists);
                            foreach ($lists as $id) {
                                if (!in_array($id, $choosedcats)) {
                                    continue;
                                }

                                $stop_url = $CFG['PAGEURL'] . "newsletter";
                                $maq->enqueue([], null, $user->email, $_POST['title'], str_replace(array('%name%', '%stop%'), array($user->name, $stop_url), $_POST['newsletter']), "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">", 0, false, $newsletterId, 0, [], $time);
                                $mails++;
                                break;
                            }
                        }
                    }
                }

                if ($mails == 0) {
                    $db->query("DELETE FROM client_newsletters WHERE ID = $newsletterId LIMIT 1");
                    throw new NewsletterException($l['ERR5']);
                }

                $db->query("UPDATE client_newsletters SET recipients = $mails WHERE ID = $newsletterId LIMIT 1");
                if ($mails > 1) {
                    $mails = str_replace("%m", $mails, $l['SUCX']);
                } else if ($mails == 1) {
                    $mails = $l['SUC1'];
                }

                alog("newsletter", "sent", $newsletterId, $_POST['title']);

                ?>
            <div class="alert alert-success">
            <?=$mails;?>
            </div>
            <?php
} catch (NewsletterException $ex) {
                ?>
            <div class="alert alert-danger">
            <b><?=$lang['GENERAL']['ERROR'];?></b> <?=$ex->getMessage();?>
            </div>
            <?php
}
        }

        if (!isset($_POST['submit']) || isset($ex)) {
            $headerTemplate = new MailTemplate("Header");
            $footerTemplate = new MailTemplate("Footer");

            $lang_terms = $headerTemplate->getContent($chosenLang);
            $lang_terms .= "\n\n\n\n";
            $lang_terms .= $footerTemplate->getContent($chosenLang);
            ?>
          <form method="POST">

          <div class="row" style="margin-top: -20px;">
            <?php
$sql = $db->query("SELECT * FROM newsletter_categories ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {?>
            <div class="col-md-4">
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="cat[<?=$row->ID;?>]" value="1"<?=(isset($_POST['cat']) && array_key_exists($row->ID, $_POST['cat'])) || !isset($_POST['title']) ? " checked=''" : "";?>>
                  <?=$row->name;?>
                </label>
              </div>
            </div>
            <?php }?>
          </div>

          <input type="text" name="title" value="<?=isset($_POST['title']) ? $_POST['title'] : "";?>" id="title" placeholder="<?=$l['SUBJECT'];?>" class="form-control"><br />

					<div class="input-group" style="position: relative;">
						<span class="input-group-addon"><i class="fa fa-clock-o"></i></span>
						<input type="text" name="time" value="<?=isset($_POST['time']) ? $_POST['time'] : "";?>" placeholder="<?=$l['IMMEDIATELY'];?>" class="form-control datetimepicker">
					</div><br />

          <textarea class="form-control" id="text" style="width:100%;height:450px;resize:none;" name="newsletter"><?=isset($_POST['newsletter']) ? $_POST['newsletter'] : ($lang_terms);?></textarea><br />

          <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
            <div class="panel panel-default">
              <div class="panel-heading" role="tab" id="headingTwo">
                <h4 class="panel-title">
                  <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <?=$l['FILTER'];?>
                  </a>
                </h4>
              </div>
              <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                <div class="panel-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><?=$l['AS'];?></label>
                        <select name="account_status" class="form-control">
                          <option value="1"><?=$l['AS1'];?></option>
                          <option value="0"<?=isset($_POST['account_status']) && $_POST['account_status'] == "0" ? ' selected=""' : '';?>><?=$l['AS2'];?></option>
                          <option value="-1"<?=isset($_POST['account_status']) && $_POST['account_status'] == "-1" ? ' selected=""' : '';?>><?=$l['ALL'];?></option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label><?=$l['NA'];?></label>
                        <select name="newsletter_abo" class="form-control">
                          <option value="1"><?=$l['NA1'];?></option>
                          <option value="0"<?=isset($_POST['newsletter_abo']) && $_POST['newsletter_abo'] == "0" ? ' selected=""' : '';?>><?=$l['NA2'];?></option>
                          <option value="-1"<?=isset($_POST['newsletter_abo']) && $_POST['newsletter_abo'] == "-1" ? ' selected=""' : '';?>><?=$l['ALL'];?></option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label><?=$l['CT'];?></label>
                        <select name="account_type" class="form-control">
                          <option value="-1"><?=$l['CT1'];?></option>
                          <option value="b2c"<?=isset($_POST['account_type']) && $_POST['account_type'] == "b2c" ? ' selected=""' : '';?>><?=$l['CT2'];?></option>
                          <option value="b2b"<?=isset($_POST['account_type']) && $_POST['account_type'] == "b2b" ? ' selected=""' : '';?>><?=$l['CT3'];?></option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label><?=$l['CG'];?></label>
                        <select name="cgroup" class="form-control">
                          <option value="-1"><?=$l['CT1'];?></option>
                          <option value="0"><?=$l['NOTASSIGNED'];?></option>
                          <?php
$sql = $db->query("SELECT * FROM client_groups ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {?>
                          <option value="<?=$row->ID;?>"<?=isset($_POST['cgroup']) && $_POST['cgroup'] == $row->ID ? ' selected=""' : '';?>><?=$row->name;?></option>
                          <?php }?>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label><?=$l['AP'];?></label>
                        <select name="product" class="form-control">
                          <option value="-1"><?=$l['CT1'];?></option>
                          <option value="0"><?=$l['NAP'];?></option>
                          <?php
$sql = $db->query("SELECT * FROM products ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {?>
                          <option value="<?=$row->ID;?>"<?=isset($_POST['product']) && $_POST['product'] == $row->ID ? ' selected=""' : '';?>><?=unserialize($row->name) ? unserialize($row->name)[$CFG['LANG']] : $row->name;?></option>
                          <?php }?>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label><?=$l['DOMAINS'];?></label><br />
                        <select name="domains" class="form-control">
                          <option value="-1"><?=$l['CT1'];?></option>
                          <option value="1"<?=isset($_POST['domains']) && $_POST['domains'] == "1" ? ' selected=""' : '';?>><?=$l['NA1'];?></option>
                          <option value="0"<?=isset($_POST['domains']) && $_POST['domains'] == "0" ? ' selected=""' : '';?>><?=$l['NA2'];?></option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label><?=$l['SERVER'];?></label><br />
                        <select name="server" class="form-control">
                          <option value="-1"><?=$l['CT1'];?></option>
                          <?php
$sql = $db->query("SELECT ID, name FROM monitoring_server ORDER BY name ASC");
            while ($row = $sql->fetch_object()) {
                echo '<option value="' . $row->ID . '"' . (isset($_POST['server']) && $_POST['server'] == $row->ID ? ' selected=""' : '') . '>' . htmlentities($row->name) . '</option>';
            }
            ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="external" value="1"<?=isset($_POST['external']) || !isset($_POST['send']) ? ' checked=""' : '';?>>
                      <?=$l['SNTE'];?>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
          <input type="hidden" name="send" value="1" />
          <div class="col-xs-6"><button type="button" class="btn btn-default btn-block" onclick="window.open('<?=$raw_cfg['PAGEURL'];?>email?newsletter=1&subject=' + $('#title').val() + '&text=' + encodeURIComponent($('#text').val()), null);"><?=$l['PREVIEW'];?></button></div>
          <div class="col-xs-6"><button type="submit" class="btn btn-primary btn-block" name="submit"><?=$l['SENDNOW'];?></button></div>
          </div>
          </form>
          <?php
}
    } else {
        ?>
      <?=$l['PCL2'];?>
      <?php
}} else if ($tab == "archive") {?>
    <?php
$t = new Table("SELECT * FROM client_newsletters", [
    "subject" => [
        "name" => $l['SUBJECT'],
        "type" => "like",
    ],
    "language" => [
        "name" => $l['LANGUAGE'],
        "type" => "select",
        "options" => $languages,
    ],
], ["time", "DESC"], "client_newsletters");
    echo $t->getHeader();
    ?>
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <tr>
        <th><?=$t->orderHeader("time", $l['DATE']);?></th>
        <th><?=$t->orderHeader("language", $l['LANGUAGE']);?></th>
        <th><?=$t->orderHeader("subject", $l['SUBJECT']);?></th>
        <th width="30px"><?=$t->orderHeader("recipients", $l['RECIPIENTS']);?></th>
        <th width="30px"><?=$l['SENTC'];?></th>
        <th width="30px"><?=$l['SEENC'];?></th>
      </tr>

      <?php
$sql = $t->qry("time DESC, ID DESC");
    if ($sql->num_rows == 0) {
        ?>
      <tr>
        <td colspan="6"><center><?=$l['NNY'];?></center></td>
      </tr>
      <?php } else {while ($row = $sql->fetch_object()) {?>
      <tr>
        <td><?=$row->time > time() ? '<i class="fa fa-clock-o"></i>' : '';?> <?=date("d.m.Y - H:i", $row->time);?></td>
        <td><?=$languages[$row->language];?></td>
        <td><a href="<?=$raw_cfg['PAGEURL'];?>email?newsletter=1&subject=<?=urlencode($row->subject);?>&text=<?=urlencode(str_replace("%name%", $l['NAMEP'], $row->text));?>" target="_blank"><?=htmlentities($row->subject);?></a></td>
        <td width="30px"><center><?=$row->recipients;?></center></td>
        <td width="30px"><center><?=$db->query("SELECT COUNT(ID) as sum FROM client_mails WHERE newsletter = $row->ID AND sent > 0")->fetch_object()->sum;?></center></td>
        <td width="30px"><center><?=$db->query("SELECT COUNT(ID) as sum FROM client_mails WHERE newsletter = $row->ID AND sent > 0 AND seen = 1")->fetch_object()->sum;?></center></td>
      </tr>
      <?php }}?>
    </table>
    </div>
    <?php echo $t->getFooter();} else if ($tab == "recipients") { ?>
    <form method="GET">
      <select name="lang" class="form-control" onchange="this.form.submit()">
        <option value=""><?=$l['PCL'];?></option>
        <?php foreach ($languages as $key => $name) {
    $std = "";
    if ($key == $CFG['LANG']) {
        $std = " OR language = ''";
    }

    $customer = $db->query("SELECT COUNT(ID) as num FROM clients WHERE (language = '" . $db->real_escape_string($key) . "'$std) AND newsletter = 1 AND locked = 0")->fetch_object()->num;
    ?>
        <option value="<?=$key;?>"<?=$customer == 0 ? " disabled" : "";?><?=isset($_GET['lang']) && $_GET['lang'] == $key && $customer > 0 ? " selected='selected'" : "";?>><?=$name;?> [<?=$customer;?> <?=$l['RECIPIENTS'];?>]</option>
        <?php }?>
      </select>
      <input type="hidden" name="p" value="newsletter">
      <input type="hidden" name="tab" value="recipients">
    </form><br />

    <?php if (!empty($_GET['lang']) && array_key_exists($_GET['lang'], $languages)) {

    if (isset($_GET['delete']) && $db->query("UPDATE clients SET newsletter = 0 WHERE ID = " . intval($_GET['delete'])) && $db->affected_rows) {
        echo '<div class="alert alert-success">' . $l['UNSUBSCRIBED'] . '</div>';
        alog("newsletter", "unsubscribe", $_GET['delete']);
    }
    ?>

    <?php
$std = "";
    if ($_GET['lang'] == $CFG['LANG']) {
        $std = " OR language = ''";
    }

    $t = new Table("SELECT * FROM clients WHERE newsletter = 1 AND (language = '" . $db->real_escape_string($_GET['lang']) . "'$std)", [
        "mail" => [
            "name" => $l['MAIL'],
            "type" => "like",
        ],
    ]);
    echo $t->getHeader();
    ?>
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <tr>
        <th><?=$l['CUST'];?></th>
        <th><?=$l['MAIL'];?></th>
        <th width="20px"></th>
      </tr>

      <?php
$sql = $t->qry("firstname ASC, lastname ASC, mail ASC");
    if ($sql->num_rows == 0) {
        ?>
      <tr>
        <td colspan="3"><center><?=$l['NNAL'];?></center></td>
      </tr>
      <?php } else {while ($row = $sql->fetch_object()) {?>
      <tr>
        <td><a href="?p=customers&edit=<?=$row->ID;?>"><?=User::getInstance($row->ID, "ID")->getfName();?></a></td>
        <td><a href="mailto:<?=$row->mail;?>"><?=$row->mail;?></a></td>
        <td width="20px"><a href="?p=newsletter&tab=recipients&lang=<?=$_GET['lang'];?>&delete=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
      </tr>
      <?php }}?>
    </table>
    </div>
    <?php echo $t->getFooter();} ?>
    <?php } else if ($tab == "categories") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == "add") {
            $name = trim($_POST['name']);
            if ($db->query("SELECT 1 FROM newsletter_categories WHERE name LIKE '" . $db->real_escape_string($name) . "'")->num_rows == 0) {
                $db->query("INSERT INTO newsletter_categories (name) VALUES ('" . $db->real_escape_string($name) . "')");
                alog("newsletter", "catcreate", $name);
                unset($_POST);
            }
        }
    }

    if (isset($_GET['d'])) {
        $db->query("DELETE FROM newsletter_categories WHERE ID = " . intval($_GET['d']));
        alog("newsletter", "catdelete", $_GET['d']);
    }
    if (isset($_GET['star'])) {
        $db->query("UPDATE newsletter_categories SET standard = 1 WHERE ID = " . intval($_GET['star']));
        alog("newsletter", "star", $_GET['star']);
    }
    if (isset($_GET['unstar'])) {
        $db->query("UPDATE newsletter_categories SET standard = 0 WHERE ID = " . intval($_GET['unstar']));
        alog("newsletter", "unstar", $_GET['unstar']);
    }
    ?>
    <p style="text-align: justify;"><?=$l['CATS'];?></p>

    <form method="POST" class="form-inline">
      <input type="hidden" name="action" value="add">
      <input type="text" name="name" class="form-control" value="<?=isset($_POST['name']) ? htmlentities($_POST['name']) : "";?>" placeholder="Name">
      <input type="submit" class="btn btn-primary" value="<?=$l['ADDCAT'];?>">
    </form><br />

    <div class="table-responsive">
      <table class="table table-bordered">
        <tr>
          <th width="30px"></th>
          <th><?=$l['CAT'];?></th>
          <th width="80px"><center><?=$l['INTERN'];?></center></th>
          <th width="80px"><center><?=$l['EXTERN'];?></center></th>
          <th width="28px"></th>
        </tr>

        <?php
$sql = $db->query("SELECT * FROM newsletter_categories ORDER BY name ASC");
    if ($sql->num_rows == 0) {
        echo "<tr><td colspan='3'><center>{$l['NNC']}</center></td></tr>";
    }

    while ($row = $sql->fetch_object()) {
        ?>
          <tr>
            <td><a href="?p=newsletter&tab=categories&<?=$row->standard ? "un" : "";?>star=<?=$row->ID;?>"><i class="fa fa-star<?=$row->standard ? "" : "-o";?>"<?=$row->standard ? ' style="color: rgb(234, 193, 23);"' : '';?>></i></a></td>
            <td><?=htmlentities($row->name);?></td>
            <td><center><?=$db->query("SELECT COUNT(*) AS c FROM clients WHERE newsletter = '{$row->ID}' OR newsletter LIKE '%|{$row->ID}|%' OR newsletter LIKE '{$row->ID}|%' OR newsletter LIKE '%|{$row->ID}'")->fetch_object()->c;?></center></td>
            <td><center><?=$db->query("SELECT COUNT(*) AS c FROM newsletter WHERE lists = '{$row->ID}' OR lists LIKE '%|{$row->ID}|%' OR lists LIKE '{$row->ID}|%' OR lists LIKE '%|{$row->ID}'")->fetch_object()->c;?></center></td>
            <td><a onclick="return confirm('<?=$l['RDC'];?>');" href="?p=newsletter&tab=categories&d=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
          </tr>
          <?php
}
    ?>
      </table>
    </div>
    <?php } else if ($tab == "recipients_ext") {?>
    <form method="GET">
      <select name="lang" class="form-control" onchange="this.form.submit()">
        <option value=""><?=$l['PCL'];?></option>
        <?php foreach ($languages as $key => $name) {
    $std = "";
    if ($key == $CFG['LANG']) {
        $std = " OR language = ''";
    }

    $customer = $db->query("SELECT COUNT(*) as num FROM newsletter WHERE (language = '" . $db->real_escape_string($key) . "'$std) AND hash = ''")->fetch_object()->num;
    ?>
        <option value="<?=$key;?>"<?=$customer == 0 ? " disabled" : "";?><?=isset($_GET['lang']) && $_GET['lang'] == $key && $customer > 0 ? " selected='selected'" : "";?>><?=$name;?> [<?=$customer;?> <?=$l['RECIPIENTS'];?>]</option>
        <?php }?>
      </select>
      <input type="hidden" name="p" value="newsletter">
      <input type="hidden" name="tab" value="recipients_ext">
    </form><br />

    <?php if (!empty($_GET['lang']) && array_key_exists($_GET['lang'], $languages)) {

    if (isset($_GET['delete']) && $db->query("DELETE FROM newsletter WHERE ID = " . intval($_GET['delete'])) && $db->affected_rows) {
        echo '<div class="alert alert-success">' . $l['EXTUN'] . '</div>';
        alog("newsletter", "unsubscribe_external", $_GET['delete']);
    }
    ?>

    <?php
$std = "";
    if ($_GET['lang'] == $CFG['LANG']) {
        $std = " OR language = ''";
    }

    $t = new Table("SELECT * FROM newsletter WHERE hash = '' AND (language = '" . $db->real_escape_string($_GET['lang']) . "'$std)", [
        "email" => [
            "name" => $l['MAIL'],
            "type" => "like",
        ],
    ], ["name", "ASC"], "newsletter_ext");
    echo $t->getHeader();
    ?>
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <tr>
        <th><?=$t->orderHeader("name", $l['NAME']);?></th>
        <th><?=$t->orderHeader("email", $l['MAIL']);?></th>
        <th width="20px"></th>
      </tr>

      <?php
$sql = $t->qry("name ASC, email ASC");
    if ($sql->num_rows == 0) {
        ?>
      <tr>
        <td colspan="3"><center><?=$l['NENA'];?></center></td>
      </tr>
      <?php } else {while ($row = $sql->fetch_object()) {?>
      <tr>
        <td><a href="#" class="recipientInfo" data-id="<?=$row->ID;?>"><?=htmlentities($row->name);?></a></td>
        <td><a href="mailto:<?=$row->email;?>"><?=$row->email;?></a></td>
        <td width="20px"><a href="?p=newsletter&tab=recipients_ext&lang=<?=$_GET['lang'];?>&delete=<?=$row->ID;?>"><i class="fa fa-times"></i></a></td>
      </tr>
      <?php }}?>
    </table>
    </div><?=$t->getFooter();?>

    <script>
    $(".recipientInfo").click(function(e){
      e.preventDefault();
      var id = $(this).data("id");
      $('#ajaxModal').modal('show');

      $.post("?p=ajax", {
          "action": "external_newsletter_recipient_details",
          "recipientid": id,
          csrf_token: "<?=CSRF::raw();?>",
      }, function(r){
          r = $.parseJSON(r);
          $("#ajaxModalLabel").html(r[0]);
          $("#ajaxModalContent").html(r[1]);
      });
    });
    </script>
    <?php }?>
    <?php } else {echo "<div class='alert alert-danger'>{$l['PNF']}</div>";}?>
		</div>
		<!-- /.col-lg-12 -->
	</div>
</div></div><?php }?>
