<?php

namespace HelloGearman\Command;

use HelloGearman\Response\Response;
use HelloGearman\Server;
use Ratchet\ConnectionInterface;

class Fetch implements CommandInterface
{
    /**
     * @param ConnectionInterface $from
     * @param string $workload
     * @param Server $server
     * @return mixed
     */
    public function run(Server $server, $workload, ConnectionInterface $from = null)
    {
        $response = new Response('fetch', '<pre><code>' . file_get_contents($workload) . '</code></pre>');
        $from->send($response);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'fetch';
    }
}