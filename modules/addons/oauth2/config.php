<?php
global $CFG;

$connection = array('dsn' => "mysql:host={$CFG['DB']['HOST']};dbname={$CFG['DB']['DATABASE']}", 'username' => $CFG['DB']['USER'], 'password' => $CFG['DB']['PASSWORD']);
$storage = new OAuth2\Storage\Pdo($connection);
$server = new OAuth2\Server($storage);

$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
