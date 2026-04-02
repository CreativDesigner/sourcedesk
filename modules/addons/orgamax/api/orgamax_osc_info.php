<?php
	if (file_exists("inc/systeminfo.php"))
	{
		require_once("inc/systeminfo.php");
	}
	else
	{
		die("Die Datei <b>systeminfo.php</b> konnte nicht gefunden werden. <br><br> Bitte &uuml;berpr&uuml;fen Sie, ob diese im Verzeichnis \"inc\" der Webshop-Anbindung vorhanden ist.");
	}

	header("Content-Type: application/json; charset=utf-8");

	$infos = getSystemInfos();
	echo json_encode($infos, JSON_PRETTY_PRINT);