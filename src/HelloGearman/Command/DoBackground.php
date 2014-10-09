<?php

namespace HelloGearman\Command;

use GearmanClient;
use HelloGearman\Response\Response;
use HelloGearman\Server;
use Ratchet\ConnectionInterface;

class DoBackground implements CommandInterface
{
    /** @var GearmanClient */
    private $client;

    public function __construct(GearmanClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param ConnectionInterface $from
     * @param string $workload
     * @param Server $server
     * @return mixed
     */
    public function run(Server $server, $workload, ConnectionInterface $from = null)
    {
        switch (isset($workload['priority']) ?: 0) {
            case 1:
                $method = 'doHighBackground';
                break;
            case -1:
                $method = 'doLowBackground';
                break;
            default:
                $method = 'doBackground';
                break;
        }

        $handle = $this->client->$method($workload['name'], serialize($workload['data']));

        $response = new Response(
            'job-queued',
            [
                'created' => microtime(1),
                'handle'  => $handle,
            ]
        );
        $from->send($response);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'do-background';
    }
}