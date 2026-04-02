<?php

if (file_exists(__DIR__ . "/deutsch.php")) {
	require __DIR__ . "/deutsch.php";
} else if (file_exists(__DIR__ . "/.deutsch.php")) {
	require __DIR__ . "/.deutsch.php";
} else {
	die("No normal german language file!");
}

?>