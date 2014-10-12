<?php

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use HelloGearman\Server;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$client = new GearmanClient();
$client->addServer();

$server = new Server($client);

$worker = new GearmanWorker();
$worker->addServer();
$worker->addOptions(GEARMAN_WORKER_NON_BLOCKING);

$job = new \HelloGearman\Job\ProcessResponse($server);
$worker->addFunction($job->getName(), array($job, 'doJob'));

$wsServer = IoServer::factory(new HttpServer(new WsServer($server)), 8080);
$wsServer->loop->addPeriodicTimer(.001, function () use ($worker) {
    $worker->work();
});
$wsServer->run();