<?php
// This file prints the actual version of the system
echo Versioning::getVersion();

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

exit;