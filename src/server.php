<?php

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use HelloGearman\Server;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$client = new GearmanClient();
$client->addServer();

$client->setCreatedCallback(function (GearmanTask $task, ConnectionInterface $connection) {
    $response = new \HelloGearman\Response\Response(
        'task-created',
        [
            'created' => microtime(1),
            'handle'  => $task->jobHandle(),
            'unique'  => $task->unique(),
            'data'    => $task->data(),
        ]
    );
    $connection->send($response);
});

$client->setStatusCallback(function (GearmanTask $task, ConnectionInterface $connection) {
    $response = new \HelloGearman\Response\Response(
        'task-status',
        [
            'handle' => $task->jobHandle(),
            'unique' => $task->unique(),
            'status' => [$task->taskNumerator(), $task->taskDenominator()],
            'data'   => $task->data(),
        ]
    );
    $connection->send($response);
});

$client->setCompleteCallback(function (GearmanTask $task, ConnectionInterface $connection) {
    $response = new \HelloGearman\Response\Response(
        'task-complete',
        [
            'handle' => $task->jobHandle(),
            'unique' => $task->unique(),
            'data'   => $task->data(),
        ]
    );
    $connection->send($response);
});

$server = IoServer::factory(new HttpServer(new WsServer(new Server([
    new \HelloGearman\Command\Ping(),
    new \HelloGearman\Command\Fetch(),
    new \HelloGearman\Command\DoBackground($client),
    new \HelloGearman\Command\JobStatus($client),
]))), 8080);

$server->run();