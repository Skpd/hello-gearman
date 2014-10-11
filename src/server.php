<?php

use HelloGearman\Job\ProcessRequest;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use HelloGearman\Server;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$output = new SplFileObject('/home/dmskpd/valid_emails_2.dat', 'a');
$input  = new SplFileObject('/home/dmskpd/Downloads/google_5000000.txt', 'r');

$client = new GearmanClient();
$client->addServer();

$server = new Server($client);

$wsServer = IoServer::factory(new HttpServer(new WsServer($server)), 8080);

$wsServer->loop->addPeriodicTimer(1, function () use ($server) {
    $queue = msg_get_queue(ProcessRequest::QUEUE_ID);
    $type = null;
    /** @var \HelloGearman\Response\Response $message */
    $message = null;

    if (msg_receive($queue, ProcessRequest::MESSAGE_ID, $type, 1024*1024, $message, true, MSG_IPC_NOWAIT)) {
        foreach ($server->getClients() as $client) {
            if ($message->getClientId() === null || spl_object_hash($client) === $message->getClientId()) {
                $client->send($message);
            }
        }
    }
});

$wsServer->run();