<?php

namespace Websocket;

class ConnTestApplication extends \Wrench\Application\Application {
	public function onData($data, $client) {
		if ($data == "Ping") {
			$client->send("Pong");
		}

	}
}