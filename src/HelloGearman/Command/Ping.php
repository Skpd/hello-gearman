<?php

namespace HelloGearman\Command;

use HelloGearman\Response\Response;
use HelloGearman\Server;
use Ratchet\ConnectionInterface;

class Ping implements CommandInterface
{
    private $count = 0;

    /**
     * @param ConnectionInterface $from
     * @param string $workload
     * @param Server $server
     * @return mixed
     */
    public function run(Server $server, $workload, ConnectionInterface $from = null)
    {
        echo microtime(1) . " Sending pong #" . (++$this->count) . PHP_EOL;

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