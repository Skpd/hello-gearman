<?php

namespace HelloGearman\Job;

use GearmanJob;
use HelloGearman\Response\Response;
use HelloGearman\Server;

class ProcessResponse implements JobInterface
{
    private $server;

    function __construct(Server $server)
    {
        $this->server = $server;
    }

    /** @return string */
    public function getName()
    {
        return 'process-response';
    }

    public function doJob(GearmanJob $job)
    {
        /** @var Response $request */
        $response = unserialize($job->workload());

        foreach ($this->server->getClients() as $client) {
            if ($response->getClientId() === null || spl_object_hash($client) === $response->getClientId()) {
                $client->send($response);
            }
        }
    }

}