<?php
if (empty($_GET['id']) || empty($_GET['pw'])) {
	exit;
}

define('BYPASS_AUTH', true);
require __DIR__ . "/init.php";

$sql = $db->query("SELECT rights FROM admins WHERE ID = " . intval($_GET['id']) . " AND `password` = '" . $db->real_escape_string($_GET['pw']) . "' LIMIT 1");
if ($sql->num_rows != 1) {
	exit;
}

$rights = unserialize($sql->fetch_object()->rights);
if (!is_array($rights) || !in_array(1, $rights) || !in_array(8, $rights)) {
	exit;
}

alog("general", "phone_directory_got");
?>
<IPPhoneDirectory>
	<?php
$sql = $db->query("SELECT firstname, lastname, company, telephone FROM clients WHERE telephone != '' ORDER BY firstname ASC, lastname ASC");
while ($row = $sql->fetch_object()) {
	$row->telephone = trim(str_replace(Array("-", "/", " "), "", $row->telephone));
	$row->telephone = str_replace("+", "00", $row->telephone);
	?>
	<DirectoryEntry>
		<Name><?=$row->firstname . " " . $row->lastname . (!empty($row->company) ? " ({$row->company})" : "");?></Name>
		<Telephone><?=$row->telephone;?></Telephone>
	</DirectoryEntry>
	<?php }?>
</IPPhoneDirectory>