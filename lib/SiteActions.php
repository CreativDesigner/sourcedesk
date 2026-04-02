<?php
/**
 * This actions will be performed on each request
 * Think of very thin actions
 */

// Important functions
require __DIR__ . "/functions.php";

if (substr(phpversion(), 0, 1) < "7") {
    die("PHP 7 or greated required");
}

// Legacy MySQL
$db->query("SET @@global.sql_mode = ''");

// Fix version
if (file_exists(__DIR__ . "/../install/req/version.dist.txt") && $CFG['VERSION'] != file_get_contents(__DIR__ . "/../install/req/version.dist.txt")) {
    $version = $db->real_escape_string(file_get_contents(__DIR__ . "/../install/req/version.dist.txt"));
    $db->query("UPDATE `settings` SET `value` = '$version' WHERE `key` = 'version' OR `key` = 'actual_version' OR `key` = 'tin_modal'");
    $CFG['version'] = $raw_cfg['VERSION'] = $version;
}

// Define autoload for classes
require_once __DIR__ . "/autoload.php";

// System requirement check
SystemRequirements::check();

// Database update
$ds = new DatabaseStructure();
$ds->init();
$hash = $ds->calcHash();

if ($ds->calcHash() != $CFG['DB_HASH']) {
    $db->query("UPDATE settings SET `value` = '$hash' WHERE `key` = 'db_hash'");
    $ds->deploy($db);
    $hash = $db->real_escape_string($hash);
} else if (!empty($_GET['db_structure_update']) || !empty($_GET['update_db_structure'])) {
    $ds->deploy($db);
    die("<b>DB structure updated</b>");
}

// Create important objects
$transactions = new Transactions;
$nfo = new NumberFormat;
$session = new Session;
$val = new Validate;
$tfa = new GoogleAuthenticator;
$sec = new Security;
$maq = new MailQueue;
$f2b = new Fail2Ban;
$captcha = new Captcha;
$dfo = new DateFormat;
$cur = new CurrencyManager;
$cms = new CMS;
$age = new MinAge;

// Delete old mails
MailQueue::clean();

// Decrypt all encrypted configuration variables
$encrypted = array("backup_ftp_password", "global_salt", "admin_whitelist_pw", "recaptcha_private", "facebook_secret", "twitter_secret", "smtp_password", "ses_secret", "telephone_log", "gitlab_key", "telegram_token", "github_user", "github_key", "sm_fb_id", "sm_fb_secret", "sm_twitter_ck", "sm_twitter_cs", "sm_twitter_at", "sm_twitter_ats", "sm_fb_page_id", "sm_fb_key");
foreach ($encrypted as $v) {
    $CFG[strtoupper($v)] = decrypt($CFG[strtoupper($v)]);
    $raw_cfg[strtoupper($v)] = decrypt($raw_cfg[strtoupper($v)]);
}

// Check for mail sender
if (empty($CFG['MAIL_SENDER']) && !empty($CFG['PAGEMAIL'])) {
    $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($CFG['PAGEMAIL']) . "' WHERE `key` = 'mail_sender'");
    $CFG['MAIL_SENDER'] = $CFG['PAGEMAIL'];
}
if (empty($CFG['PAGEMAIL']) && !empty($CFG['MAIL_SENDER'])) {
    $db->query("UPDATE settings SET `value` = '" . $db->real_escape_string($CFG['MAIL_SENDER']) . "' WHERE `key` = 'pagemail'");
    $CFG['PAGEMAIL'] = $CFG['MAIL_SENDER'];
}

// Encrypt product_provisioning table
if ($CFG['PROVISIONING_ENCODED'] == "0") {
    $CFG['PROVISIONING_ENCODED'] = "1";
    $db->query("UPDATE settings SET `value` = '1' WHERE `key` = 'provisioning_encoded'");

    $updateStmt = $db->prepare("UPDATE product_provisioning SET value = ? WHERE module = ? AND pid = ? AND setting = ?");

    $sql = $db->query("SELECT * FROM product_provisioning");
    while ($row = $sql->fetch_object()) {
        $row->value = encrypt($row->value);
        $updateStmt->bind_param("ssis", $row->value, $row->module, $row->pid, $row->setting);
        $updateStmt->execute();
    }

    $updateStmt->close();
}

// Fix updated date for support tickets
if (empty($CFG['TICKETS_UPDATED'])) {
    $CFG['TICKETS_UPDATED'] = "1";
    $db->query("INSERT INTO settings (`key`, `value`) VALUES ('tickets_updated', '1')");

    $sql = $db->query("SELECT ID FROM support_tickets");
    while ($row = $sql->fetch_object()) {
        $t = new Ticket($row->ID);
        $time = $t->getLastAnswer();
        $db->query("UPDATE support_tickets SET updated = '" . date("Y-m-d H:i:s", $time) . "' WHERE ID = {$row->ID}");
    }
}

// Check for folders
if (!array_key_exists("FOLDERS_CREATED", $CFG)) {
    foreach (["avatars", "email_templates", "downloads", "projects", "backups", "emails", "quotes", "invoice_attachments", "sepa_mandates", "calls", "invoices", "system", "contracts", "letter_attachments", "uploads", "customers", "notes", "versions", "domains", "product_images"] as $dir) {
        if (!is_dir($path = (__DIR__ . "/../files/" . $dir))) {
            mkdir($path);
        }
    }

    $db->query("INSERT INTO settings (`key`, `value`) VALUES ('folders_created', '1');");
}

if (!array_key_exists("TICKET_AF_CREATED", $CFG)) {
    foreach (["tickets"] as $dir) {
        if (!is_dir($path = (__DIR__ . "/../files/" . $dir))) {
            mkdir($path);
        }
    }

    $db->query("INSERT INTO settings (`key`, `value`) VALUES ('ticket_af_created', '1');");
}

// Make sure that all users have an api key for reseller access
$sql = $db->query("SELECT ID FROM clients WHERE api_key = ''");
while ($r = $sql->fetch_object()) {
    // For all users that have not a key, generate one
    $key = md5(uniqid(mt_rand(), true));
    $db->query("UPDATE clients SET api_key = '$key' WHERE ID = " . $r->ID);
}

// Migrate old services
$db->bugs = false;
if ($db->query("SELECT 1 FROM services")) {
    $sql = $db->query("SELECT * FROM services");
    if ($sql->num_rows) {
        $sql2 = $db->prepare("INSERT INTO products (`name`, `status`, `price`, `description`, `billing`, `module`, `welcome_mail`, `old_service`) VALUES (?,?,?,?,'onetime','project',?,?)");

        while ($row = $sql->fetch_object()) {
            $name = @unserialize($row->name) ?: [$CFG['LANG'] => $name];
            $mail = @unserialize($row->mail) ?: [];

            $eid = 0;
            if (count($mail) && !empty(array_values($mail)[0])) {
                $title = $db->real_escape_string(serialize($name));
                $content = $db->real_escape_string(serialize($mail));
                $name2 = $db->real_escape_string(array_values($name)[0]);

                $db->query("INSERT INTO email_templates (`name`, `title`, `content`, `category`, `foreign_name`, `vars`) VALUES ('$name2', '$title', '$content', 'Eigene', '$name', '')");
                $eid = $db->insert_id;
            }

            $sql2->bind_param("sidsii", $name3 = serialize($name), $row->active, $row->price, $row->description, $eid, $row->ID);
            $sql2->execute();

            $pid = $db->insert_id;

            $db->query("INSERT INTO product_provisioning (`module`, `pid`, `setting`, `value`) VALUES ('project', $pid, 'project', '" . $db->real_escape_string(encrypt(array_values($name)[0])) . "')");
            $db->query("INSERT INTO product_provisioning (`module`, `pid`, `setting`, `value`) VALUES ('project', $pid, 'template', '" . $db->real_escape_string(encrypt(strval($row->project_template))) . "')");

            $db->query("DELETE FROM services WHERE ID = {$row->ID}");
        }

        $sql2->close();
    } else {
        $db->query("DROP TABLE services");
    }
}

$db->bugs = true;

// Send failed login notifications
$sql = $db->query("SELECT ID FROM clients WHERE `failed_login` > 0 AND `failed_login` <= " . (time() - 1800) . " AND `failed_login` - `failed_login_mail` >= 86400 AND locked = 0");
while ($r = $sql->fetch_object()) {
    $userInstance = new User($r->ID, "ID");
    $userInstance->set(array("failed_login_mail" => time()));

    $headers = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/plain; charset=utf-8";
    $headers[] = "From: " . $CFG['PAGENAME'] . " <" . $CFG['MAIL_SENDER'] . ">";

    $mailLang = !empty($userInstance->get()['language'] && isset($languages[$userInstance->get()['language']])) ? $userInstance->get()['language'] : $CFG['LANG'];
    $mtObj = new MailTemplate("Passwort vergessen?");
    $titlex = $mtObj->getTitle($mailLang);
    $mail = $mtObj->getMail($mailLang, $userInstance->get()['name']);

    $CFG['DATE_FORMAT'] = unserialize($raw_cfg['DATE_FORMAT'])[$mailLang];

    $maq->enqueue([
        "link" => $CFG['PAGEURL'] . "login?send_password=" . urlencode($userInstance->get()['mail']) . "&h=" . substr(hash("sha512", $CFG['HASH'] . $userInstance->get()['mail']), 0, 5),
        "time" => $dfo->format($userInstance->get()['failed_login']),
    ], $mtObj, $userInstance->get()['mail'], $titlex, $mail, implode("\r\n", $headers), $userInstance->get()['ID'], false, 0, 0, $mtObj->getAttachments($mailLang));
    $CFG['DATE_FORMAT'] = unserialize($raw_cfg['DATE_FORMAT'])[$CFG['LANG']];
    $userInstance->log("Login-Erinnerung gesendet");
}

// Save database connection ressource permanently
ObjectStorage::$db = $db;

// Initialize CSRF
CSRF::init();

// License system
// CHANGES ARE NOT PERMITTED!
$ex = explode("|", $CFG['LICENSE_ID']);
$brandingRequired = count($ex) != 6 || !in_array($ex[0], [267, 268]);

if (count($ex) == 9 && boolval($ex[7])) {
    $brandingRequired = false;
}
// CHANGES ARE NOT PERMITTED!

function ci($id)
{
    global $CFG;

    $u = User::getInstance($id, "ID");
    if (!is_object($u)) {
        return "";
    }

    return ($CFG['CNR_PREFIX'] ?: "#") . $id . " " . htmlentities($u->get()['firstname']) . " " . htmlentities($u->get()['lastname']);
}

function ii($id)
{
    global $CFG;

    $inv = new Invoice;
    if (!$inv->load($id)) {
        return "";
    }

    return $inv->getInvoiceNo();
}

// Migrate software products
$sql = $db->query("SELECT * FROM products WHERE type = 'SOFTWARE'");
while ($row = $sql->fetch_object()) {
    $data = [
        "file" => $row->file,
        "bugtracker_dept" => $row->bugtracker_dept,
        "licensing_active" => $row->licensing_active ? "yes" : "no",
        "licensing_reset" => $row->licensing_reset ? "yes" : "no",
        "licensing_secret" => $row->licensing_secret,
        "licensing_cache" => $row->licensing_cache,
        "licensing_reissue" => $row->licensing_reissue,
        "key_additional" => $row->key_additional,
    ];

    $db->query("UPDATE products SET `type` = 'HOSTING', `module` = 'software' WHERE ID = {$row->ID}");

    foreach ($data as $k => $v) {
        $k = $db->real_escape_string($k);
        $v = $db->real_escape_string(encrypt($v));
        $db->query("INSERT INTO product_provisioning (`module`, `pid`, `setting`, `value`) VALUES ('software', {$row->ID}, '$k', '$v')");
    }
}

// Migrate software contracts
$sql = $db->query("SELECT * FROM client_products WHERE type != 'h'");
while ($row = $sql->fetch_object()) {
    $data = [];
    $sql2 = $db->query("SELECT `setting`, `value` FROM product_provisioning WHERE pid = {$row->product} AND module = 'software'");
    while ($row2 = $sql2->fetch_object()) {
        $data[$row2->setting] = decrypt($row2->value);
    }

    $data["key_additional"] = $row->key_additional;
    $data = $db->real_escape_string(encrypt(serialize($data)));

    $db->query("UPDATE client_products SET `type` = 'h', `module` = 'software', `module_settings` = '$data' WHERE ID = {$row->ID}");
}

// Check for existing .htaccess file
if (!file_exists(__DIR__ . "/../.htaccess")) {
    @copy(__DIR__ . "/../install/req/htaccess.dist", __DIR__ . "/../.htaccess");
}

// Fill server ID for client products
$sql = $db->query("SELECT ID, module_settings FROM client_products WHERE server_id < 0");
while ($row = $sql->fetch_object()) {
    $ms = @unserialize(decrypt($row->module_settings));
    $sid = 0;

    if ($ms) {
        $sid = intval($ms['_mgmt_server'] ?? 0);
    }

    $db->query("UPDATE client_products SET server_id = $sid WHERE ID = {$row->ID}");
}
