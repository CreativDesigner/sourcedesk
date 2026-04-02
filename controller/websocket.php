<?php
global $CFG, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (php_sapi_name() != "cli") {
    die("Error: Only allowed from CLI.");
}

if (!$CFG['WEBSOCKET_ACTIVE']) {
    die("Error: Websocket is not active.");
}

$port = $CFG['WEBSOCKET_PORT'];
if (empty($port) || !is_numeric($port) || $port > 65536 || $port < 20) {
    die("Error: Invalid port defined.");
}

$connection = @fsockopen("127.0.0.1", $port);
if (is_resource($connection)) {
    die("Error: This port is already in use. Maybe the websocket is already running?");
}

$ssl = array();
$prefix = "ws";

if (!empty($CFG['WEBSOCKET_PEM'])) {
    file_put_contents(__DIR__ . "/../modules/websocket/.ssl.pem", decrypt($CFG['WEBSOCKET_PEM']));
    
    $ssl = array(
        'server_ssl_local_cert' => __DIR__ . "/../modules/websocket/.ssl.pem",
        'server_ssl_allow_self_signed' => true,
        'server_ssl_verify_peer' => false,
    );

    $prefix = "wss";
}

$server = new \Wrench\BasicServer($prefix . '://0.0.0.0:' . $port, array(
    'allowed_origins' => explode(",", $CFG['WEBSOCKET_AO']),
    'connection_manager_options' => array(
        'socket_master_options' => $ssl,
    ),
));

require __DIR__ . "/../modules/websocket/ConnTestApplication.php";
$server->registerApplication('test', new \Websocket\ConnTestApplication());

$addons->runHook("Websocket", array("server" => $server));

$server->run();

@unlink(__DIR__ . "/../modules/websocket/ssl.pem.php");

exit;
