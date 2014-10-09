<?php

namespace HelloGearman\Command;

use HelloGearman\Server;
use Ratchet\ConnectionInterface;

interface CommandInterface
{
    /**
     * @param ConnectionInterface $from
     * @param string $workload
     * @param Server $server
     * @return mixed
     */
    public function run(Server $server, $workload, ConnectionInterface $from = null);

    /**
     * @return string
     */
    public function __toString();
}