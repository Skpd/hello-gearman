<?php

namespace HelloGearman\Job;

use GearmanJob;
use HelloGearman\Command\CommandInterface;
use HelloGearman\Request\Request;

class ProcessRequest implements JobInterface
{
    /** @var CommandInterface[] */
    private $commands;
    /** @var \GearmanClient */
    private $client;

    function __construct(\GearmanClient $client, array $commands)
    {
        $this->client = $client;

        foreach ($commands as $command) {
            $this->commands[(string) $command] = $command;
        }
    }

    /** @return string */
    public function getName()
    {
        return 'process-request';
    }

    public function doJob(GearmanJob $job)
    {
        /** @var Request $request */
        $request = unserialize($job->workload());

        if (isset($this->commands[$request->getCommand()])) {
            $response = $this->commands[$request->getCommand()]->run($request);

            $this->client->doBackground('process-response', serialize($response));
        } else {
            throw new \Exception("Unknown command {$request->getCommand()}");
        }
    }

}