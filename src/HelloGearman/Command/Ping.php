<?php

namespace HelloGearman\Command;

use HelloGearman\Response\Response;
use HelloGearman\Server;
use Ratchet\ConnectionInterface;

class Ping implements CommandInterface
{
    /**
     * @param ConnectionInterface $from
     * @param string $workload
     * @param Server $server
     * @return mixed
     */
    public function run(Server $server, $workload, ConnectionInterface $from = null)
    {
        echo date(DATE_ATOM) . ": Received ping from " . spl_object_hash($from) . ': ' . (abs(microtime(1) - $workload) * 1000) . ' ms' . PHP_EOL;

        $response = new Response('pong', microtime(1));
        $from->send($response);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'ping';
    }
}