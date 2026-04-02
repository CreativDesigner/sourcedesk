<?php

/**
 * Get important informations for the OnlineShopConnector
 * @return array
 */
function getSystemInfos()
{
	include "config.php";
	$infos = array();
	$infos["osc_version"] = $GLOBALS['VERSION'] ?: 'undefined';
	$infos["php_version"] = phpversion();
	$infos["openssl_available"] = extension_loaded("openssl");
	$infos["mysqli_available"] = extension_loaded("mysqli");
	$infos["shop_system"] = null;
	$infos["mysql_version"] = null;
	$infos["mysql_error"] = null;
	
	if ($GLOBALS["SETUP_SHOP"] && !empty($GLOBALS["SETUP_SHOP"]))
	{
		$infos["shop_system"] = $GLOBALS["SETUP_SHOP"];
		
		if (file_exists(__DIR__."/shops/".$GLOBALS["SETUP_SHOP"].".php"))
		{
			require_once "shops/".$GLOBALS["SETUP_SHOP"].".php";
			
			starten();
			try
			{
				if (mysqli_connect_errno())
					$infos["mysql_error"] = mysqli_connect_error();
				else
					$infos["mysql_version"] = mysqli_get_server_info($GLOBALS['sql_con']);
			}
			finally
			{
				ende();
			}
		}
	}

	return $infos;
}
