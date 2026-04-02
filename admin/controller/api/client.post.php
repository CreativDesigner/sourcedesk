<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

parse_str(file_get_contents("php://input"), $in);

if (empty($in['firstname']) || empty($in['lastname'])) {
    throw new Exception("Please provide full name.");
}

if (empty($in['mail']) || !$val->email($in['mail'])) {
    throw new Exception("Invalid email address.");
}

$pwd = isset($in['pwd']) ? $in['pwd'] : Security::generatePassword(12);

$salt = $sec->generateSalt();
$cpassword = $sec->hash($pwd, $salt);

if (strlen($pwd) < 8) {
    throw new Exception("Password should have at least 8 characters.");
}

// Get standard newsletter
$sql = $db->query("SELECT ID FROM newsletter_categories WHERE standard = 1");
$nl = array();
while ($row = $sql->fetch_object()) {
    array_push($nl, $row->ID);
}

$nl = $db->real_escape_string(implode("|", $nl));

// Create database entry
$salt = $db->real_escape_string($salt);
$cpassword = $db->real_escape_string($cpassword);
$firstname = $db->real_escape_string($in['firstname']);
$lastname = $db->real_escape_string($in['lastname']);
$mail = $db->real_escape_string($in['mail']);
$limit = doubleval($CFG['POSTPAID_DEF']);

$cgroup = 0;
if ($CFG['DEFAULT_CGROUP'] && $db->query("SELECT 1 FROM client_groups WHERE ID = " . intval($CFG['DEFAULT_CGROUP']))->num_rows) {
    $cgroup = intval($CFG['DEFAULT_CGROUP']);
}

if (!$db->query("INSERT INTO clients (`cgroup`, `firstname`, `lastname`, `mail`, `pwd`, `registered`, `salt`, newsletter, postpaid) VALUES ($cgroup, '$firstname', '$lastname', '$mail', '$cpassword', " . time() . ", '$salt', '$nl', $limit)")) {
    throw new Exception("Customer creation failed.");
}

$user = User::getInstance($db->insert_id, "ID");

if (!$user) {
    throw new Exception("Technical error occured.");
}

$addons->runHook("CustomerCreated", [
    "user" => $user,
]);

// Add additional data
$hadPwd = isset($in['pwd']);
unset($in['firstname'], $in['lastname'], $in['mail'], $in['pwd']);

$user->set($in);
$user->saveChanges("api");

// Status
$ret = ["id" => $user->get()['ID']];
if (!$hadPwd) {
    $ret["pwd"] = $pwd;
}

die(json_encode($ret));
