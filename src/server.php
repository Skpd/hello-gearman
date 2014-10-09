<?php

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use HelloGearman\Server;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(new HttpServer(new WsServer(new Server([
    new \HelloGearman\Command\Ping(),
    new \HelloGearman\Command\Fetch(),
]))), 8080);

$server->run();