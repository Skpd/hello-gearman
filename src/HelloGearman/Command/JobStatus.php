<?php

namespace HelloGearman\Command;

use GearmanClient;
use HelloGearman\Response\Response;
use HelloGearman\Server;
use Ratchet\ConnectionInterface;

class JobStatus implements CommandInterface
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
        $status = $this->client->jobStatus($workload);

        $response = new Response(
            'job-status',
            [
                'handle'      => $workload,
                'known'       => $status[0],
                'running'     => $status[1],
                'numerator'   => $status[2],
                'denominator' => $status[3],
            ]
        );
        $from->send($response);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'job-status';
    }
}