<?php
/**
 * Sofort Library Factory
 *
 * @author SOFORT AG (integration@sofort.com)
 *
 * @copyright 2010-2014 SOFORT AG
 *
 * @license Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * @license http://www.gnu.org/licenses/lgpl.html
 *
 * @version SofortLib 2.1.1
 *
 * @link http://www.sofort.com/ official website
 */
class SofortLibFactory {

	/**
	 * Defines and includes the DataHandler
	 *
	 * @param string $configKey
	 * @return XmlDataHandler
	 */
	static public function getDataHandler($configKey) {
		require_once __DIR__ . '/xmlDataHandler.php';

		return new XmlDataHandler($configKey);
	}

	/**
	 * Defines the Http Connection to be used
	 *
	 * @param string $data
	 * @param string|bool $url
	 * @param array|bool $headers
	 * @return SofortLibHttpCurl|SofortLibHttpSocket
	 */
	static public function getHttpConnection($data, $url = false, $headers = false) {
		if (function_exists('curl_init')) {
			require_once __DIR__ . '/sofortLibHttpCurl.inc.php';

			return new SofortLibHttpCurl($data, $url, $headers);
		} else {
			require_once __DIR__ . '/sofortLibHttpSocket.inc.php';

			return new SofortLibHttpSocket($data, $url, $headers);
		}
	}

	/**
	 * Defines and includes the logger
	 *
	 * @return FileLogger
	 */
	static public function getLogger() {
		require_once __DIR__ . '/fileLogger.php';

		return new FileLogger();
	}
}